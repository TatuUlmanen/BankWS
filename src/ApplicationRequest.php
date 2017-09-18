<?php
namespace BankWS;

/**
 * ApplicationRequest
 *
 * Specifies the content to be sent inside SoapRequest. Allows signing of content.
 */
class ApplicationRequest {
	
	public $document;
	protected $applicationRequest;
	protected $base64_encoded;
	protected $useBase64Encode;
	
	protected $namespace_uri;
	protected $namespace_prefix;
	protected $keychain;
	
	public function __construct($config = array(), $keychain) {
		$this->keychain = $keychain;
		$this->namespace_uri    = isset($config['namespace']) ? $config['namespace'] : 'http://bxd.fi/xmldata/';
		$this->namespace_prefix = isset($config['namespace_prefix']) ? $config['namespace_prefix'] : null;
		$nodename  = isset($config['nodename'])  ? $config['nodename']  : 'ApplicationRequest';
		$this->document = new DOMWrapper();
		$this->document->preserveWhiteSpace = false;
		if(!is_null($this->namespace_prefix)) {
			$this->applicationRequest = $this->document->append($nodename, null, $this->namespace_prefix, $this->namespace_uri);
		} else {
			$this->applicationRequest = $this->document->append($nodename, null);
			$this->applicationRequest->setAttribute('xmlns', $this->namespace_uri);
		}
		return $this;
	}
	
	public function setValue($key, $value = null, $parent = null) {
		$parent = is_null($parent) ? $this->applicationRequest : $parent;
		if(is_array($key)) {
			foreach($key as $k => $v) {
				$this->setValue($k, $v);
			}
		} else {
			if(is_array($value)) {
				if(!is_null($this->namespace_prefix)) {
					$newparent = $parent->append($key, null, $this->namespace_prefix, $this->namespace_uri);
				} else {
					$newparent = $parent->append($key);
				}
				foreach($value as $row) {
					$this->setValue($row['key'], $row['value'], $newparent);
				}
			} else {
				if(!is_null($this->namespace_prefix)) {
					$parent->append($key, $value, $this->namespace_prefix, $this->namespace_uri);
				} else {
					$parent->append($key, $value);
				}
			}
		}
		
	}
	
	public function setValues($key, $value = null) {
		$this->setValue($key, $value);
	}
	
	public function __toString() {
		return $this->useBase64Encode ? $this->getBase64EncodedContent() : preg_replace('/^<\?xml.*?\?>/', '', $this->getXML());
	}
	
	public function getXML() {
		return $this->document->saveXML();
	}
	
	public function setBase64Encode($bool) {
		$this->useBase64Encode = !!$bool;
	}
	
	public function getBase64EncodedContent() {
		return base64_encode($this->document->saveXML());
	}
	
	public function sign($options = array()) {
		
		$defaults = array(
			'private_key' => $this->keychain->getKeyPath('CustomerSigningPrivate'),
			'public_key'  => $this->keychain->getKeyPath('CustomerSigningPublic'),
			'passphrase'  => null
		);
		
		# Extend the options with defaults
		foreach($defaults as $key => $value) {
			if(!isset($options[$key])) {
				$options[$key] = $value;
			}
		}
		
		if(empty($options['private_key'])) {
			throw new Exception(null, 'Private key filepath used to sign ApplicationRequest empty or not given.');
		}
		
		if(empty($options['public_key'])) {
			throw new Exception(null, 'Public key filepath not given.');
		}
		
		if(!is_readable($options['private_key'])) {
			throw new Exception(null, 'Private key filepath used to sign ApplicationRequest empty or not found ('.$options['privateKey'].').');
		}
		
		if(!is_readable($options['public_key'])) {
			throw new Exception(null, 'Public key filepath not found ('.$options['publicKey'].').');
		}
		
		$objDSig = new \XMLSecurityDSig();
		$objDSig->setCanonicalMethod(\XMLSecurityDSig::EXC_C14N);
		$objDSig->addReference(
			$this->document->doc,
			\XMLSecurityDSig::SHA1,
			array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
			array('force_uri' => true
		));
		
		$objKey = new \XMLSecurityKey(\XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
		if(isset($options['passphrase'])) {
			$objKey->passphrase = $options['passphrase'];
		}
		$objKey->loadKey($options['private_key'], TRUE);
		$publicKey = file_get_contents($options['public_key']);
		
		$objDSig->sign($objKey);
		$objDSig->add509Cert($publicKey);
		$objDSig->appendSignature($this->document->doc->documentElement);
	}
	
	public function encrypt($config) {
		
		if(empty($config['publicKey'])) {
			throw new Exception(null, 'Encryption key file not given.');
		}
		
		$objKey = new \XMLSecurityKey(\XMLSecurityKey::TRIPLEDES_CBC);
		$objKey->generateSessionKey();
		
		$siteKey = new \XMLSecurityKey(\XMLSecurityKey::RSA_1_5, array('type'=>'public'));
		$siteKey->loadKey($config['publicKey'], true, true);
		
		$enc = new \XMLSecEnc();
		$enc->setNode($this->document->doc->documentElement);
		$enc->encryptKey($siteKey, $objKey);
		
		$enc->type = \XMLSecEnc::Element;
		$encNode = $enc->encryptNode($objKey);
	}
}
