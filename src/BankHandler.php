<?php
namespace BankWS;

/**
 * BankHandler
 *
 * Base class for all bank handlers. Includes methods common to all bank handlers.
 */
abstract class BankHandler {
	
	const FILETYPE_REFERENCE_PAYMENTS = false;
	const FILETYPE_FINVOICE_SEND      = false;
	const FILETYPE_FINVOICE_RECEIVE   = false;
	const FILETYPE_FINVOICE_FEEDBACK  = false;
	
	protected $bankHandlerName = null;
	protected $config = array();
	protected $schema = array();
	
	public $requestId = null;
	
	public function __construct(BankHandlerConfig $config, $keychain) {
		# Generate a new request ID, ready for the next request
		$this->requestId = BankWS::generateRequestId();
		
		$this->keychain = $keychain;
		$this->config   = $config;
		# Get bank name from classname (e.g. BankWS\NordeaBankHandler -> Nordea)
		$this->bankHandlerName = preg_replace('~BankWS\\\\(.*?)BankHandler$~', '$1', get_class($this));
	}
	
	# Get User Info, e.g. available filetypes
	abstract public function getUserInfo($config = null);
	
	# Create a private key and associated CSR, get public key from bank
	abstract public function getCertificate($config = null);
	
	# Download a list of available files by filetype
	public function downloadFileList($config = null) {
		
		$class = get_class($this);
		$applicationRequestValues = array(
			'Command'      => 'DownloadFileList',
			'StartDate'    => null,
			'EndDate'      => null,
			'Status'       => 'ALL',
			'FileType'     => $class::FILETYPE_REFERENCE_PAYMENTS,
		);
		
		$applicationRequestValues = $this->updateConfig($applicationRequestValues, $config);
		$soapResponse = $this->request($applicationRequestValues);
		
		return new FileList($soapResponse);
	}
	
	# Download a single file or several files concatenated
	public function downloadFile($config = null) {
		
		$class = get_class($this);
		if(empty($config['FileReferences'])) {
			throw new Exception(null, 'At least one FileReference must be set when downloading files');
		}
		
		foreach($config['FileReferences'] as &$fileReference) {
			$fileReference = array('key' => 'FileReference', 'value' => $fileReference);
		} unset($fileReference);
		
		$applicationRequestValues = array(
			'Command'        => 'DownloadFile',
			'FileReferences' => $config['FileReferences'],
			'FileType'       => $class::FILETYPE_REFERENCE_PAYMENTS
		);
		
		$applicationRequestValues = $this->updateConfig($applicationRequestValues, $config);
		
		$soapResponse = $this->request($applicationRequestValues);
		return base64_decode($soapResponse->applicationResponse->Content);
	}
	
	public function uploadFile($config = null) {
		$class = get_class($this);
		$applicationRequestValues = array(
			'Command'  => 'UploadFile',
			'FileType' => $class::FILETYPE_REFERENCE_PAYMENTS,
			'Content'  => null
		);
		
		$applicationRequestValues = $this->updateConfig($applicationRequestValues, $config);
		
		$soapResponse = $this->request($applicationRequestValues);
		
		return $soapResponse;
	}
	
	protected function updateConfig($master, $config) {
		if(is_array($master) && is_array($config)) {
			foreach($master as $key => $value) {
				if(isset($config[$key])) {
					$master[$key] = $config[$key];
				}
			}
		}
		return $master;
	}
	
	/**
	 * Validates given ApplicationRequest values against schema array.
	 * throws Exception
	 * returns schema compliant array of ApplicationRequest values
	 */
	protected function validateSchema($schema, $values) {
		
		if(empty($schema)) {
			throw new SchemaValidationException("Schema is empty");
		}
		
		$keys = array_keys($schema);
		$applicationRequestValues = array();
		
		# Check for extra elements that don't exist in the schema
		foreach($values as $key => $value) {
			if(!in_array($key, $keys)) {
				throw new SchemaValidationException("Field '".$key."' not defined in schema");
			}
		}
		
		foreach($schema as $key => $field) {
			
			$value = isset($values[$key]) ? $values[$key] : null;
			
			if(is_null($value) && !$field['nillable']) {
				$value = $field['default'];
			}
			
			if(empty($value)) {
				if(!$field['required'] && !$field['nillable']) {
					# Not required but can't be null if present -> remove if null
					continue;
				} elseif($field['required'] && !$field['nillable']) {
					# Required field that cannot be null
					throw new SchemaValidationException("Field '".$key."' can not be null");
				} elseif($field['required'] && $field['nillable']) {
					# Required but can be null
					$applicationRequestValues[$key] = '';
				}
			} else {
				# Check if the value must conform to given options
				if(isset($field['values']) && !empty($field['values']) && !in_array($value, $field['values'])) {
					throw new SchemaValidationException("Value of field '".$key."' does not match any of the possible values: '".implode("', '", $field['values'])."'");
				}
				$applicationRequestValues[$key] = $value;
			}
		}
		
		return $applicationRequestValues;
	}
	
	/**
	 * Wrapper for sending the SoapRequest.
	 * Takes in the fields used to build the ApplicationRequest and additional config data.
	 */
	protected function request($applicationRequestValues, $config = array()) {
		
		$class = get_class($this);
		
		$defaults = array(
			'command'        => null,
			'bodyTemplate'   => null,
			'to'             => $this->config->testmode ? $class::WEB_SERVICE_TEST_ADDRESS : $class::WEB_SERVICE_ADDRESS,
			'signContent'    => true,
			'encryptContent' => false,
			'applicationRequestType'      => 'ApplicationRequest',
			'applicationRequestNamespace' => 'http://bxd.fi/xmldata/'
		);
		
		$config = $this->updateConfig($defaults, $config);
		# Check schema existence for current request type
		if(!isset($this->schema[$config['applicationRequestType']])) {
			throw new SchemaValidationException("Schema for ".$config['applicationRequestType']." not found");
		}
		
		# Validate ApplicationRequest values against schema
		$applicationRequestValues = $this->validateSchema($this->schema[$config['applicationRequestType']], $applicationRequestValues);
		
		# Create ApplicationRequest
		$applicationRequestType = __NAMESPACE__.'\\'.$config['applicationRequestType'];
		$applicationRequest	= new $applicationRequestType(array(
			'namespace' => $config['applicationRequestNamespace']
		), $this->keychain);
		
		# Add XML fields
		$applicationRequest->setValues($applicationRequestValues);
		
		$templateBase = in_array($this->bankHandlerName, array('Aktia', 'Handelsbanken', 'SP', 'POP')) ? 'Samlink' : $this->bankHandlerName;
		$config['bodyTemplate'] = isset($config['bodyTemplate']) ? $config['bodyTemplate'] : __DIR__.'/../templates/'.$templateBase.$config['applicationRequestType'].'.xml';
		
		# Sign ApplicationRequest if needed
		if($config['signContent']) {
			$applicationRequest->sign();
		}
		
		# Encrypt ApplicationRequest if needed (used by Sampo)
		if($config['encryptContent']) {
			$applicationRequest->encrypt(array(
				'publicKey' => $this->keychain->getKeyPath('BankEncryptionPublic')
			));
		}
		
		if(isset($applicationRequestValues['Command'])) {
			$command = strtolower($applicationRequestValues['Command'][0]).substr($applicationRequestValues['Command'], 1).'in';
		} elseif(!empty($config['command'])) {
			$command = $config['command'];
		} else {
			throw new Exception(null, 'Command not defined');
		}
		
		if(!is_readable($config['bodyTemplate'])) {
			throw new Exception(null, 'Template file '.$config['bodyTemplate'].' cannot be read.');
		}
		
		# Create SoapRequest
		$soapRequest = new SoapRequest(array(
			'Base64EncodeApplicationRequest' => true,
			'ApplicationRequest' => $applicationRequest,
			'SenderId'           => $this->config->customerId,
			'CustomerId'         => $this->config->customerId,
			'RequestId'          => $this->requestId,
			'Command'            => $command,
			'BodyTemplate'       => $config['bodyTemplate'],
			'To'                 => $config['to'],
			'CACert'             => $this->keychain->getKeyPath('SSLCertificate'),
			'ApplicationRequestValues' => $applicationRequestValues
		), $this->keychain);
		
		# Sign SoapRequest if needed
		if($config['signContent']) {
			$soapRequest->sign();
		}
		
		# Get results
		return $soapRequest->request();
	}
}
