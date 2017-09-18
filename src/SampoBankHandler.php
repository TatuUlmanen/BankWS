<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

class SampoBankHandler extends BankHandler {
	
	const WEB_SERVICE_ADDRESS  = 'https://businessWS.sampopankki.fi/edifileservice/edifileservice.asmx';
	const CERT_SERVICE_ADDRESS = 'https://businessWS.sampopankki.fi/ra/pkiservice.asmx';
	
	const WEB_SERVICE_TEST_ADDRESS  = 'https://businessWS.sampopankki.fi/edifileservice/edifileservice.asmx';
	const CERT_SERVICE_TEST_ADDRESS = 'https://businessWS.sampopankki.fi/ra/pkiservice.asmx';
	
	const FILETYPE_STATEMENT          = 'TAPN';
	const FILETYPE_REFERENCE_PAYMENTS = 'VIPN';
	const FILETYPE_FINVOICE_SEND      = 'FILL';
	const FILETYPE_FINVOICE_RECEIVE   = false;
	const FILETYPE_FINVOICE_FEEDBACK  = false;
	
	public function __construct($config, $keychain) {
		
		parent::__construct($config, $keychain);
		
		$this->schema['ApplicationRequest'] = array(
			'CustomerId'        => array('required' => true,  'nillable' => false, 'default' => $this->config->customerId),
			'Command'           => array('required' => true,  'nillable' => false, 'default' => null, 'values' => array('UploadFile', 'DownloadFileList', 'DownloadFile', 'DeleteFile', 'GetUserInfo')),
			'Timestamp'         => array('required' => true,  'nillable' => false, 'default' => gmdate('c')),
			'StartDate'         => array('required' => false, 'nillable' => false, 'default' => null),
			'EndDate'           => array('required' => false, 'nillable' => false, 'default' => null),
			'Status'            => array('required' => false, 'nillable' => false, 'default' => null, 'values' => array('ALL', 'NEW', 'DLD')),
			'ServiceId'         => array('required' => false, 'nillable' => false, 'default' => null),
			'Environment'     => array('required' => true,  'nillable' => false, 'default' => $this->config->testmode ? 'TEST' : 'PRODUCTION', 'values' => array('TEST', 'PRODUCTION')),
			'FileReferences'    => array('required' => false, 'nillable' => false, 'default' => null),
			# Mandatory in UploadFile, ignored in others
			'UserFileName'      => array('required' => false, 'nillable' => false, 'default' => null),
			'TargetId'          => array('required' => false, 'nillable' => false, 'default' => $this->config->targetId),
			'ExecutionSerial'   => array('required' => false, 'nillable' => false, 'default' => null),
			# Must contain 'false' if present - 'true' not implemented yet
			'Encryption'        => array('required' => false, 'nillable' => false, 'default' => null),
			'Compression'       => array('required' => false, 'nillable' => false, 'default' => 'false'),
			'CompressionMethod' => array('required' => false, 'nillable' => false, 'default' => 'GZIP'),
			'SoftwareId'        => array('required' => true,  'nillable' => false, 'default' => $this->config->softwareId),
			'FileType'          => array('required' => false, 'nillable' => false, 'default' => null),
			'Content'           => array('required' => false, 'nillable' => false,  'default' => null)
		);
		
		$this->schema['CreateCertificateRequest'] = array(
			'CustomerId'           => array('required' => true,  'nillable' => false, 'default' => $this->config->customerId),
			'KeyGeneratorType'     => array('required' => false, 'nillable' => false, 'default' => 'software', 'values' => array('HSM', 'software')),
			'EncryptionCertPKCS10' => array('required' => true,  'nillable' => false, 'default' => null),
			'SigningCertPKCS10'    => array('required' => true,  'nillable' => false, 'default' => null),
			'Timestamp'            => array('required' => true,  'nillable' => false, 'default' => gmdate('c')),
			'RequestId'            => array('required' => true, 'nillable' => false, 'default' => $this->requestId),
			'Environment'          => array('required' => false, 'nillable' => false, 'default' => $this->config->testmode ? 'customertest' : 'production', 'values' => array('customertest', 'production')),
			'PIN'                  => array('required' => true,  'nillable' => false, 'default' => $this->config->testmode ? 1234 : null),
		);
		
		$this->schema['GetBankCertificateRequest'] = array(
			'BankRootCertificateSerialNo' => array('required' => false, 'nillable' => false, 'default' => null),
			'Timestamp'                   => array('required' => true,  'nillable' => false, 'default' => gmdate('c')),
			'RequestId'                   => array('required' => true, 'nillable' => false, 'default' => $this->requestId),
		);
	}
	
	public function getUserInfo($config = null) {
		throw new Exception(null, 'getUserInfo not implemented in Sampo EDI Web Services');
	}
	
	public function downloadFileList($config = null) {
		
		$applicationRequestValues = array(
			'Command'   => 'DownloadFileList',
			'StartDate' => null,
			'EndDate'   => null,
			'Status'    => 'ALL',
			'FileType'  => null
		);
		
		$applicationRequestValues = $this->updateConfig($applicationRequestValues, $config);		
		$soapResponse = $this->request($applicationRequestValues, array(
			'encryptContent' => true,
			'encryptionKey'  => $this->keychain->getKeyPath('BankEncryptionPublic')
		));
		
		return new FileList($soapResponse);	
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
		
		# Sampo supports requesting only one file at a time, fetching all requested files as separate
		# requests for compliance with other BankHandlers.
		foreach($config['FileReferences'] as $fileReference) {
			$applicationRequestValues['FileReferences'] = array(array('key' => 'FileReference', 'value' => $fileReference));
			$soapResponse = $this->request($applicationRequestValues, array(
				'encryptContent' => true,
				'encryptionKey'  => $this->keychain->getKeyPath('BankEncryptionPublic')
			));
			$response .= base64_decode($soapResponse->applicationResponse->Content);
		}
		
		return $response;		
	}
	
	public function uploadFile($config = null) {
	
		$applicationRequestValues = array(
			'Command' => 'UploadFile',
			'FileType' => self::FILETYPE_REFERENCE_PAYMENTS,
			'Content' => null
		);
		
		$applicationRequestValues = $this->updateConfig($applicationRequestValues, $config);
		
		$soapResponse = $this->request($applicationRequestValues, array(
			'encryptContent' => true,
			'encryptionKey'  => $this->keychain->getKeyPath('BankEncryptionPublic')
		));
		
		return $soapResponse;
	}
	
	public function getCertificate($config = null) {
		
		if(!$encryptionKey = $this->keychain->getKeyPath('BankEncryptionPublic')) {
			$bankCertificates = $this->getBankCertificate();
			foreach($bankCertificates as $name => $key) {
				$this->keychain->addKey($name, $key);
			}
			$encryptionKey = $this->keychain->getKeyPath('BankEncryptionPublic');
		}
		
		$options = array(
			'CN'       => '',
			'initCode' => $this->config->testmode ? 1234 : null
		);
		
		$options = $this->updateConfig($options, $config);
		
		# Sampo requires two certificates, one of signing and the other for encrypting
		$encryptionCSR = BankWS::createCSR(array(
			'password'  => $options['initCode'],
			'keylength' => 2048,
			'C'         => 'FI',
			'ST'        => '',
			'L'         => '',
			'O'         => '',
			'OU'        => '',
			'CN'        => $options['CN'],
			'email'     => ''
		));
		
		$signingCSR = BankWS::createCSR(array(
			'password'  => $options['initCode'],
			'keylength' => 2048,
			'C'         => 'FI',
			'ST'        => '',
			'L'         => '',
			'O'         => '',
			'OU'        => '',
			'CN'        => $options['CN'],
			'email'     => ''
		));
		
		$applicationRequestValues = array(
			'EncryptionCertPKCS10' => base64_encode($encryptionCSR['csr']),
			'SigningCertPKCS10'    => base64_encode($signingCSR['csr']),
			'PIN'                  => $options['initCode']
		);
		
		$soapResponse = $this->request($applicationRequestValues, array(
			'command'                => 'CreateCertificateIn',
			'bodyTemplate'           => BWS_ROOT_PATH.'templates/SampoCreateCertificateRequest.xml',
			'to'                     => self::CERT_SERVICE_ADDRESS,
			'applicationRequestType' => 'CreateCertificateRequest',
			'applicationRequestNamespace' => 'http://danskebank.dk/PKI/PKIFactoryService/elements',
			'signContent'            => false,
			'encryptContent'         => true,
			'encryptionKey'          => $encryptionKey
		));
		
		$certs = $soapResponse->applicationResponse->getCertificates();
		
		$this->keychain->addKey('CustomerSigningPrivate', $signingCSR['private']);
		$this->keychain->addKey('CustomerSigningPublic',  $certs['SigningCert']);
		
		$this->keychain->addKey('CustomerEncryptionPrivate', $encryptionCSR['private']);
		$this->keychain->addKey('CustomerEncryptionPublic',  $certs['EncryptionCert']);
		
		return array(
			'CustomerSigningPrivate'    => $signingCSR['private'],
			'CustomerSigningPublic'     => $certs['SigningCert'],
			'CustomerEncryptionPrivate' => $encryptionCSR['private'],
			'CustomerEncryptionPublic'  => $certs['EncryptionCert'],
			'SoapRequest'               => $soapResponse->xml->request,
			'ApplicationRequest'        => $soapResponse->xml->applicationRequest,
			'SoapResponse'              => $soapResponse->xml->response
		);
	}
	
	# "Once the customer has a valid root certificate, he uses the GetBankCertificates
	# operation to fetch the encryption and signing bank certificates."
	public function getBankCertificate($config = null) {
		
		if(!$this->keychain->getKeyPath('RootCertificate')) {
			
			$rootpath = $this->keychain->getKeyPath('RootCertificate', false);
			
			if(file_exists(BWS_ROOT_PATH.'keys/Sampo-RootCertificate.cer')) {
				copy(BWS_ROOT_PATH.'keys/Sampo-RootCertificate.cer', $rootpath);
			}
			
			if(!$this->keychain->getKeyPath('RootCertificate')) {
				throw new Exception(null, 'Sampo root certificate not found at '.$this->keychain->getKeyPath('RootCertificate'));
			}
		}
		
		$cert = openssl_x509_parse(file_get_contents($this->keychain->getKeyPath('RootCertificate')));
		$applicationRequestValues = array(
			'BankRootCertificateSerialNo' => $cert['serialNumber']
		);
		
		$soapResponse = $this->request($applicationRequestValues, array(
			'command'                => 'GetBankCertificateIn',
			'bodyTemplate'           => BWS_ROOT_PATH.'templates/SampoGetBankCertificateRequest.xml',
			'to'                     => self::CERT_SERVICE_ADDRESS,
			'applicationRequestType' => 'GetBankCertificateRequest',
			'applicationRequestNamespace' => 'http://danskebank.dk/PKI/PKIFactoryService/elements',
			'signContent'            => false,
			'encryptContent'         => false
		));
		
		$returnKeys = array(
			'BankEncryptionCert' => 'BankEncryptionPublic',
			'BankSigningCert'    => 'BankSigningPublic',
			'BankRootCert'       => 'BankRootPublic'
		);
		
		$return = array();
		$response = $soapResponse->applicationResponse->getArray();
		foreach($returnKeys as $key => $value) {
			if(!isset($response[$key])) {
				throw new Exception(null, 'Certificate '.$key.' not found in GetBankCertificateResponse');
			}
			$return[$value] = "-----BEGIN CERTIFICATE-----\n".trim(chunk_split($response[$key], 64))."\n-----END CERTIFICATE-----";
			$this->keychain->addKey($value, $return[$value]);
		}
		
		return $return;
	}
	
	public function renewCertificate($config = null) {
		
		# "In test mode, Any PKCS#10 request that starts with 'M' gives a valid response.
		# Any other value in the PKCS#10 request gives return code 10 suggesting a problem
		# with the PKCS#10 requests.
		if($this->config->testmode) {
			$encryptionCSR['csr'][0] = 'M';
			$signingCSR['csr'][0] = 'M';
		}
	}
	
	
}
