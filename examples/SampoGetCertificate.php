<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '6A0823',
		'keyFolder'  => '/var/www/system/keys/',
		'testmode'   => false
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Sampo, $bankHandlerConfig);
	
	$cert = $bankHandler->getCertificate(array(
		'initCode' => '14Y2',
		'CN' => 'EDI WS TATU ULMANEN'
	));
	
	echo '<pre>';
	echo $cert['CustomerSigningPrivate'];
	echo $cert['CustomerSigningPublic'];
	echo "\n\n";
	echo $cert['CustomerEncryptionPrivate'];
	echo $cert['CustomerEncryptionPublic'];
} catch(Exception $e) {
	echo $e->getMessage();
}

