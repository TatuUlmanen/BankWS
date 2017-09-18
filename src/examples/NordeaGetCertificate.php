<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '1234567890',
		'testmode'   => true,
		'keyFolder'  => '../keys/'
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Nordea, $bankHandlerConfig);
	
	$cert = $bankHandler->getCertificate(array(
		'initCode' => 1234567890
	));
	
	echo '<pre>';
	echo $cert['CustomerSigningPrivate'];
	echo $cert['CustomerSigningPublic'];
} catch(Exception $e) {
	echo $e->getMessage();
}

