<?php
namespace BankWS;

/**
 * SoapRequest
 *
 * Main message container and transportation method.
 */
class SoapRequest {
	
	private $bodyTemplate;
	private $securityToken;
	private $policy;
	private $client;
	private $message;
	private $applicationRequest;
	private $keychain;
	
	private $options = array(
		'Base64EncodeApplicationRequest' => true,
		'To'                 => false, # Receiving address
		'CustomerId'         => false,
		'Environment'        => false,
		'CACert'             => false, 
		'Command'            => false, # Requested action, etc. getUserInfo or getCertificate
		'Timestamp'          => false,
		'SenderId'           => false, # Usually customer id
		'RequestId'          => false, # Unique ID
		'BodyTemplate'       => false,
		'ApplicationRequest' => false,
		'ApplicationRequestValues' => false
	);

	public function __construct($options = null, $keychain = null) {
		
		$this->keychain = $keychain;
		
		if(is_array($options)) {
			
			# All requests demand a timestamp, add if not defined
			if(!isset($options['Timestamp'])) {
				$options['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
			}
			
			# Extend default options with custom ones
			foreach($options as $key => $value) {
				if(isset($this->options[$key])) {
					$this->options[$key] = $value;
				}
			}
			
			if(is_array($options['ApplicationRequestValues'])) {
				foreach($options['ApplicationRequestValues'] as $key => $value) {
					if(empty($this->options[$key])) {
						$this->options[$key] = $value;
					}
				}
			}
			
			$this->options['ApplicationRequest']->setBase64Encode($options['Base64EncodeApplicationRequest']);
			
			unset($this->options['ApplicationRequestValues']);
		}
		
		$this->applicationRequest = $this->options['ApplicationRequest'];
		
		$this->message = new SoapMessage($this->getBody());
		
	}
	
	/**
	 * Builds and sends the SoapRequest, returning SoapResponse
	 */
	public function request() {
		
		# Debug ApplicationRequest by defining BWS_APP_REQ_DEBUG in code
		if(defined('BWS_APP_REQ_DEBUG')) {
			BankWS::outputXML($this->applicationRequest->getXML());
		}
		
		$xml = $this->message->getXML();
		
		# Debug SoapRequest by defining BWS_SOAP_REQ_DEBUG in code
		if(defined('BWS_SOAP_REQ_DEBUG')) {
			BankWS::outputXML($xml);
		}
		
		$c = curl_init($this->options['To']);
		
		curl_setopt($c, CURLOPT_POST,           true);
		curl_setopt($c, CURLOPT_POSTFIELDS,     $xml);
		curl_setopt($c, CURLOPT_CAINFO,         $this->options['CACert']);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($c, CURLOPT_HTTPHEADER,     array('Content-type: text/xml', 'SOAPAction: '.$this->options['Command']));
		
		$response = curl_exec($c);
		
		# Debug response by defining BWS_RESPONSE_DEBUG in code
		if(defined('BWS_RESPONSE_DEBUG')) {
			BankWS::outputXML($response);
		}
		
		# Check for errors during transport
		if(curl_errno($c) > 0) {
			throw new Exception(curl_errno($c), curl_error($c));
		}
		
		# Response has to have an Envelope element to be considered as proper SOAP response
		if(!preg_match('~<.*?:Envelope~i', $response)) {
			throw new Exception(null, 'Invalid response (no Envelope element found)');
		}
		
		$request  = BankWS::outputXML($xml, true);
		$response = BankWS::outputXML($response, true);
		
		# Build the SoapResponse. Might fail if the response was empty or not XML.
		try {
			$soapResponse = new SoapResponse($response, $this->keychain);
			$soapResponse->xml->request            = $request;
			$soapResponse->xml->applicationRequest = $this->applicationRequest->getXML();
			$soapResponse->xml->response           = $response;
		} catch(Exception $e) {
			$e->request            = $request;
			$e->applicationRequest = $this->applicationRequest->getXML();
			$e->response           = $response;
			throw $e;
		}
		
		return $soapResponse;
	}
	
	/**
	 * Get the SOAP message body (<soapenv:Body> element contents)
	 */
	public function getBody() {
		
		$options = $this->options;
		
		# The body template file contains "{variable}" definitions, which will be
		# replaced with the corresponding keys from the options.
		if(isset($options['ApplicationRequest'])) {
			$options['ApplicationRequest'] = trim(preg_replace('~^<\?xml.*?>~', '', (string)$options['ApplicationRequest']));
		}
		
		# Build keys, so that "keyname" becomes "{keyname}"
		$keys   = array_map(function($v) { return '{'.$v.'}'; }, array_keys($options));
		$values = array_values($options);
		# Remove array values, will cause notice and are not needed
		foreach($values as $key => $value) {
			if(is_array($value)) {
				unset($keys[$key]);
				unset($values[$key]);
			}
		}
		
		# Get template file if available. These are just XML files that contain
		# variable definitions.
		if(is_readable($this->options['BodyTemplate'])) {
			$template = file_get_contents($this->options['BodyTemplate']);
		} else {
			$template = null;
		}
		
		return str_replace($keys, $values, $template);
	}
	
	/**
	 * Sign the SOAP request
	 */
	public function sign($options = array()) {
		
		$defaults = array(
			'private_key' => $this->keychain->getKeyPath('CustomerSigningPrivate'),
			'public_key'  => $this->keychain->getKeyPath('CustomerSigningPublic')
		);
		
		# Extend the options with defaults
		foreach($defaults as $key => $value) {
			if(!isset($options[$key])) {
				$options[$key] = $value;
			}
		}
		
		$this->message->sign(array(
			'private_key' => $options['private_key'],
			'public_key'  => $options['public_key']
		));
	}
}
