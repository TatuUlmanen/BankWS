<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '1H4518',
		'keyFolder'  => '../keys/',
		'testmode'   => false
	));
	
	#define('BWS_SOAP_REQ_DEBUG', true);
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Sampo, $bankHandlerConfig);
	
	$cert = $bankHandler->getBankCertificate();
	
	var_dump($cert);
} catch(Exception $e) {
	echo $e->getMessage();
}

