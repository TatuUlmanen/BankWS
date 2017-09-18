<?php
namespace BankWS;

class SoapMessage
{
	
	private $envelope = null;
	private $header   = null;
	private $body     = null;
	
	public function __construct($contents = null) {
		$this->dom = new \DOMDocument();
		
		# Build soapenv:Envelope
		$this->envelope = $this->dom->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soapenv:Envelope');
		$this->envelope->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:cer', 'http://bxd.fi/CertificateService');
		$this->dom->appendChild($this->envelope);
		
		$this->buildHeader();
		$this->buildBody($contents);
	}
	 
	public function __toString() {
		return $this->getXML();
	}
	
	public function getXML() {
		
		
		$xml = $this->dom->saveXML();
		return $xml;
		$signedInfo     = $this->dom->getElementsByTagName('SignedInfo')->item(0);
		
		$i = -1;
		$xpath = new \DOMXPath($this->dom);
		$placeholders = array();
		
		$elements   = $signedInfo->getElementsByTagName('Reference');
		while($element = $elements->item(++$i)) {
			$id          = ltrim($element->getAttribute('URI'), '#');
			$digestValue = $element->getElementsByTagName('DigestValue')->item(0)->nodeValue;
			$target      = $this->dom->getElementById($id);
			
			$xpath->registerNamespace('wsu', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');
			$target = $xpath->query('//*[@wsu:Id="'.$id.'"]')->item(0);
			
			$c14n   = $target->C14N(true, false);
			$placeholder = 'placeholder-'.$id;
			$target->parentNode->replaceChild(new \DOMText($placeholder), $target);
			$placeholders[$placeholder] = $c14n;
		}
		
		$xml = str_replace(array_keys($placeholders), array_values($placeholders), $this->dom->saveXML());
		return $xml;
	}
	
	public function buildHeader() {
		if(is_null($this->header)) {		
			# Build soapenv:Header
			$this->header = $this->dom->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soapenv:Header');
			$this->envelope->appendChild($this->header);
		}
	}
	
	public function buildBody($contents) {
		if(is_null($this->body)) {
			# Build body content XML
			$body_contents = new \DOMDocument();
			$body_contents->loadXML($contents);
			
			# Build soapenv:Body
			$this->body = $this->dom->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soapenv:Body');
			$this->envelope->appendChild($this->body);
			
			# Populate soapenv:Body with body content XML
			$body_node = $this->dom->importNode($body_contents->documentElement, true);
			$this->body->appendChild($body_node);
		}
	}
	
	public function sign($options) {
		
		$defaults = array(
			'private_key' => null,
			'public_key'  => null
		);
		
		foreach($defaults as $key => $value) {
			if(!isset($options[$key])) {
				$options[$key] = $value;
			}
		}
		
		# Get private key
		$private_key = new \XMLSecurityKey(\XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
		$private_key->loadKey($options['private_key'], true);
		
		# Get public key
		$public_key_rows = file($options['public_key'], FILE_IGNORE_NEW_LINES);
		$public_key = null;
		
		foreach($public_key_rows as $public_key_row) {
			if(substr($public_key_row, 0, 5) != '-----') {
				$public_key .= $public_key_row;
			}
		}
		
		$security = $this->dom->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'wsse:Security');
		$security->setAttributeNS('http://schemas.xmlsoap.org/soap/envelope/', 'soapenv:mustUnderstand', 1);
		
		# BinarySecurityToken
		$binarySecurityToken = $this->dom->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'wsse:BinarySecurityToken');
		$binarySecurityToken->setAttribute('EncodingType', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary');
		$binarySecurityToken->setAttribute('ValueType', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3');
		$binarySecurityToken->appendChild(new \DOMText($public_key));
		
		$security->appendChild($binarySecurityToken);
		
		# Timestamps
		$timestamp = $this->dom->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd', 'wsu:Timestamp');
		
		$created = $this->dom->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd', 'wsu:Created');
		$created->appendChild(new \DOMText(gmdate('Y-m-d\TH:i:s.000\Z')));
		
		$expires = $this->dom->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd', 'wsu:Expires');
		$expires->appendChild(new \DOMText(gmdate('Y-m-d\TH:i:s.000\Z', strtotime('+5 minutes'))));
		
		$timestamp->appendChild($created);
		$timestamp->appendChild($expires);
		
		$security->appendChild($timestamp);
		
		# Signature
		$signature = new \XMLSecurityDSig();
		$signature->setCanonicalMethod(\XMLSecurityDSig::EXC_C14N);
		$signature->addSecurityTokenReference($binarySecurityToken);
		
		$signature->addReference(
			$timestamp,
			\XMLSecurityDSig::SHA1,
			array('http://www.w3.org/2001/10/xml-exc-c14n#'),
			array(
				'force_uri' => true,
				'prefix' => 'wsu',
				'prefix_ns' => 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd'
			)
		);
		
		$signature->addReference(
			$this->body,
			\XMLSecurityDSig::SHA1,
			array('http://www.w3.org/2001/10/xml-exc-c14n#'),
			array(
				'force_uri' => true,
				'prefix' => 'wsu',
				'prefix_ns' => 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd'
			)
		);
		
		$signature->sign($private_key);
		$signature->appendSignature($security);
		
		# Build soapenv:Header if needed
		$this->buildHeader();
		$this->header->appendChild($security);
	}
}
