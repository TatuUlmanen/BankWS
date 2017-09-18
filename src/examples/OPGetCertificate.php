<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '1000016580',
		'keyFolder'  => '../keys/',
		'testmode'   => true
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::OP, $bankHandlerConfig);
	
	$initCode = '5034422158301155';
	
	$cert = $bankHandler->getCertificate(array(
		'initCode' => $initCode
	));
	
	echo '<pre>';
	echo $cert['CustomerSigningPrivate'];
	echo $cert['CustomerSigningPublic'];
} catch(Exception $e) {
	echo 'Exception: '.$e->getMessage();
}

