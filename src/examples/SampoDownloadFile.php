<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId'     => '6A0823',
		'keyFolder'  => '/var/www/system/keys/',
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Sampo, $bankHandlerConfig);
	
	if(isset($_GET['FileType']) && isset($_GET['FileReference'])) {
		$fileReferences = array($_GET['FileReference']);
		$content = $bankHandler->downloadFile(array(
			'FileReferences' => $fileReferences,
			'FileType' => $_GET['FileType']
		));
	} else {
		$fileList = $bankHandler->downloadFileList(array());
		
		foreach($fileList as $file) {
			$fileReferences[] = $file['FileReference'];
		}
		
		$content = $bankHandler->downloadFile(array(
			'FileReferences' => $fileReferences,
		));
	}
	echo $content;	
} catch(BankWS\Exception $e) {
	echo $e->getMessage();
}

