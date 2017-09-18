<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '1000016580',
		'keyFolder'  => '../keys/',
		'testmode'   => true
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::OP, $bankHandlerConfig);
	
	if(isset($_GET['FileType']) && isset($_GET['FileReference'])) {
		$fileReferences = array($_GET['FileReference']);
		$content = $bankHandler->downloadFile(array(
			'FileReferences' => $fileReferences,
			'FileType' => $_GET['FileType']
		));
	} else {
		$fileList = $bankHandler->downloadFileList(array(
			'FileType' => 'pain.001.001.02'
		));
		
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

