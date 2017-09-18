<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '12341234',
		'keyFolder' => '../keys/'
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Handelsbanken, $bankHandlerConfig);
	
	$initCode = '6780653202070105';
	
	$cert = $bankHandler->getCertificate(array(
		'initCode' => $initCode,
	));
	
	echo '<pre>';
	echo $cert['privateKey'];
	echo $cert['publicKey'];
} catch(Exception $e) {
	echo 'Exception: '.$e->getMessage();
}

