<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId'     => '11111111',
		'targetId'       => '11111111A1',
		'keyFolder'      => '../keys/'
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Nordea, $bankHandlerConfig);
	$userInfo = $bankHandler->getUserInfo();
	
	echo '<pre>';
	foreach($userInfo->userFileTypes as $userFileType) {
		echo $userFileType['FileType']."\t".$userFileType['FileTypeName']."\t".$userFileType['Direction']."\n";
	}
} catch(BankWS\Exception $e) {
	echo $e->getMessage();
}

