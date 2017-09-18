<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

class NordeaBankHandler extends BankHandler {
	
	const WEB_SERVICE_ADDRESS  = 'https://filetransfer.nordea.com/services/CorporateFileService';
	const CERT_SERVICE_ADDRESS = 'https://filetransfer.nordea.com/services/CertificateService';
	
	const WEB_SERVICE_TEST_ADDRESS  = 'https://filetransfer.nordea.com/services/CorporateFileService';
	const CERT_SERVICE_TEST_ADDRESS = 'https://filetransfer.nordea.com/services/CertificateService';
	
	const FILETYPE_REFERENCE_PAYMENTS = 'KTL';
	const FILETYPE_FINVOICE_SEND      = 'LAHLASKUT';
	const FILETYPE_FINVOICE_RECEIVE   = 'HAELASKUT';
	const FILETYPE_FINVOICE_FEEDBACK  = 'HYLLASKUT';
	
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
			'Environment'       => array('required' => true,  'nillable' => false, 'default' => 'PRODUCTION', 'values' => array('TEST', 'PRODUCTION')),
			# Mandatory in DownloadFile, ignored in others
			'FileReferences'    => array('required' => false, 'nillable' => false, 'default' => null),
			'UserFilename'      => array('required' => false, 'nillable' => false, 'default' => null),
			# Optional in GetUserInfo
			'TargetId'          => array('required' => true,  'nillable' => false, 'default' => $this->config->targetId),
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
			#'Compression'       => array('required' => false, 'nillable' => false, 'default' => 'false'),
			#'CompressionMethod' => array('required' => false, 'nillable' => false, 'default' => 'GZIP'),
			'Service'           => array('required' => false, 'nillable' => false, 'default' => 'service'),
			'Content'           => array('required' => true,  'nillable' => false, 'default' => null),
			'TransferKey'       => array('required' => false, 'nillable' => false, 'default' => null),
			'SerialNumber'      => array('required' => false, 'nillable' => false, 'default' => null),
			'HMAC'              => array('required' => true,  'nillable' => false, 'default' => null)
		);
	}
	
	public function getUserInfo($config = null) {
		
		$applicationRequestValues = array(
			'Command' => 'GetUserInfo',
			'TargetId' => null
		);
		
		$applicationRequestValues = $this->updateConfig($applicationRequestValues, $config);
		
		$soapResponse = $this->request($applicationRequestValues);
		$userInfo     = new UserInfo($soapResponse);
		
		return $userInfo;
	}
	
	public function getCertificate($config = null) {
	
		$options = array(
			'CN' => 'WINETTO OY',
			'initCode' => false
		);
		
		$options = $this->updateConfig($options, $config);
		
		$csr = BankWS::createCSR(array(
			'password'  => $this->config->testmode ? '1234567890' : $options['initCode'],
			'keylength' => 1024,
			'serialNumber' => $this->config->customerId,
			'C'         => 'FI',
			'ST'        => '',
			'L'         => '',
			'O'         => '',
			'OU'        => '',
			'CN'        => $options['CN'],
			'email'     => ''
		));
		
		$applicationRequestValues = array(
			'Content'      => base64_encode($csr['csr']),
			'HMAC'         => $csr['hmac']
		);
		
		$soapResponse = $this->request($applicationRequestValues, array(
			'bodyTemplate'                => BWS_ROOT_PATH.'templates/NordeaCertApplicationRequest.xml',
			'to'                          => self::CERT_SERVICE_ADDRESS,
			'applicationRequestType'      => 'CertApplicationRequest',
			'applicationRequestNamespace' => 'http://filetransfer.nordea.com/xmldata/',
			'signContent'                 => false
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
