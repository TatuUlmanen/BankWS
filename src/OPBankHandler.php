<?php
namespace BankWS;

class OpBankHandler extends BankHandler {
	
	const WEB_SERVICE_ADDRESS  = 'https://wsk.op.fi/services/CorporateFileService';
	const CERT_SERVICE_ADDRESS = 'https://wsk.op.fi/services/OPCertificateService';
	
	const WEB_SERVICE_TEST_ADDRESS  = 'https://wsk.asiakastesti.op.fi/services/CorporateFileService';
	const CERT_SERVICE_TEST_ADDRESS = 'https://wsk.asiakastesti.op.fi/services/OPCertificateService';
	
	const FILETYPE_REFERENCE_PAYMENTS = 'TL';
	const FILETYPE_FINVOICE_SEND      = 'XS';
	const FILETYPE_FINVOICE_RECEIVE   = 'XR';
	const FILETYPE_FINVOICE_FEEDBACK  = 'XI';
	
	public function __construct($config, $keychain) {
		
		parent::__construct($config, $keychain);
		
		$this->schema['ApplicationRequest'] = array(
			'CustomerId'        => array('required' => true,  'nillable' => false, 'default' => $this->config->customerId),
			'Command'           => array('required' => true,  'nillable' => false, 'default' => null, 'values' => array('UploadFile', 'DownloadFileList', 'DownloadFile', 'GetUserInfo')),
			'Timestamp'         => array('required' => true,  'nillable' => false, 'default' => date('c')),
			'StartDate'         => array('required' => false, 'nillable' => false, 'default' => null),
			'EndDate'           => array('required' => false, 'nillable' => false, 'default' => null),
			# Mandatory in DownloadFileList, DownloadFile
			'Status'            => array('required' => false, 'nillable' => false, 'default' => null, 'values' => array('ALL', 'NEW', 'DOWNLOADED')),
			'ServiceId'         => array('required' => false, 'nillable' => false, 'default' => null),
			'Environment'       => array('required' => true,  'nillable' => false, 'default' => $this->config->testmode ? 'TEST' : 'PRODUCTION', 'values' => array('TEST', 'PRODUCTION')),
			# Mandatory in DownloadFile, ignored in others
			'FileReferences'    => array('required' => false, 'nillable' => false, 'default' => null),
			'UserFilename'      => array('required' => false, 'nillable' => false, 'default' => null),
			# Optional in GetUserInfo
			'TargetId'          => array('required' => false, 'nillable' => false, 'default' => $this->config->targetId),
			'ExecutionSerial'   => array('required' => false, 'nillable' => false, 'default' => null),
			# Must contain 'false' if present - 'true' not implemented yet
			'Encryption'        => array('required' => false, 'nillable' => false, 'default' => null),
			'Compression'       => array('required' => false, 'nillable' => false, 'default' => 'false'),
			'CompressionMethod' => array('required' => false, 'nillable' => false, 'default' => 'GZIP'),
			'SoftwareId'        => array('required' => true,  'nillable' => false, 'default' => $this->config->softwareId),
			'FileType'          => array('required' => false, 'nillable' => false, 'default' => null),
			'Content'           => array('required' => false,  'nillable' => false,  'default' => null)
		);
		
		$this->schema['CertApplicationRequest'] = array(
			'CustomerId'        => array('required' => true,  'nillable' => false, 'default' => $this->config->customerId),
			'Timestamp'         => array('required' => true,  'nillable' => false, 'default' => date('c')),
			'Environment'       => array('required' => true,  'nillable' => false, 'default' => $this->config->testmode ? 'TEST' : 'PRODUCTION', 'values' => array('TEST', 'PRODUCTION')),
			'SoftwareId'        => array('required' => true,  'nillable' => false, 'default' => $this->config->softwareId),
			'Command'           => array('required' => false, 'nillable' => false, 'default' => 'GetCertificate'),
			'ExecutionSerial'   => array('required' => false, 'nillable' => false, 'default' => null),
			'Encryption'        => array('required' => false, 'nillable' => false, 'default' => null),
			'EncryptionMethod'  => array('required' => false, 'nillable' => false, 'default' => null),
			'Compression'       => array('required' => true, 'nillable' => false, 'default' => 'false'),
			#'CompressionMethod' => array('required' => false, 'nillable' => false, 'default' => 'GZIP'),
			'Service'           => array('required' => false, 'nillable' => false, 'default' => 'MATU'),
			'Content'           => array('required' => true,  'nillable' => false, 'default' => null),
			'TransferKey'       => array('required' => false, 'nillable' => false, 'default' => null),
			'SerialNumber'      => array('required' => false, 'nillable' => false, 'default' => null)
		);
	}
	
	public function getUserInfo($config = null) {
		throw new Exception(null, 'getUserInfo not implemented in Osuuspankki Web Services');
	}
	
	public function downloadFile($config = null) {
		
		if(!is_array($config['FileReferences']) || empty($config['FileReferences'])) {
			throw new Exception(null, 'A FileReference must be set when downloading files');
		}
		
		$response = '';
		
		$applicationRequestValues = array(
			'Command'        => 'DownloadFile',
			'FileReferences' => null,
			'FileType'       => self::FILETYPE_REFERENCE_PAYMENTS
		);
		
		$applicationRequestValues = $this->updateConfig($applicationRequestValues, $config);
		
		foreach($config['FileReferences'] as $fileReference) {
			$applicationRequestValues['FileReferences'] = array(array('key' => 'FileReference', 'value' => $fileReference));
			$soapResponse = $this->request($applicationRequestValues);
			$response .= base64_decode($soapResponse->applicationResponse->Content);
		}
		
		return $response;		
	}
	
	public function getCertificate($config = null) {
		
		$options = array(
			'writepath' => BWS_ROOT_PATH,
			'initCode' => false
		);
		
		$options = $this->updateConfig($options, $config);
		
		$csr = BankWS::createCSR(array(
			'writepath' => $options['writepath'],
			'password'  => $options['initCode'],
			'CN'        => $this->config->customerId
		));
		
		$applicationRequestValues = array(
			'Content'      => base64_encode($csr['csr']),
			'TransferKey'  => $options['initCode']
		);
		
		$soapResponse = $this->request($applicationRequestValues, array(
			'bodyTemplate'           => BWS_ROOT_PATH.'templates/OpCertApplicationRequest.xml',
			'to'                     => $this->config->testmode ? self::CERT_SERVICE_TEST_ADDRESS : self::CERT_SERVICE_ADDRESS,
			'applicationRequestType' => 'CertApplicationRequest',
			'applicationRequestNamespace' => 'http://op.fi/mlp/xmldata/',
			'signContent'            => false
		));
		
		$certs = $soapResponse->applicationResponse->getCertificates();
		
		$this->keychain->addKey('CustomerSigningPrivate', $csr['private']);
		$this->keychain->addKey('CustomerSigningPublic',  $certs[0]);
		
		return array(
			'CustomerSigningPrivate' => $csr['private'],
			'CustomerSigningPublic'  => $certs[0],
			'SoapRequest'        => $soapResponse->xml->request,
			'ApplicationRequest' => $soapResponse->xml->applicationRequest,
			'SoapResponse'       => $soapResponse->xml->response
		);
	}
}
