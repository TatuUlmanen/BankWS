<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '11111111',
		'targetId'   => '11111111A1',
		'keyFolder'  => '../keys/'
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Nordea, $bankHandlerConfig);
	
	if(isset($_GET['FileType']) && isset($_GET['FileReference'])) {
		$fileReferences = array($_GET['FileReference']);
		$content = $bankHandler->downloadFile(array(
			'FileReferences' => $fileReferences,
			'FileType' => $_GET['FileType']
		));
	} else {
		$fileList = $bankHandler->downloadFileList(array(
			'FileType'  => 'KTL',
			'StartDate' => '2006-03-06',
			'EndDate'   => '2006-03-06'
		));
		
		foreach($fileList as $file) {
			$fileReferences[] = $file['FileReference'];
		}
		
		$content = $bankHandler->downloadFile(array(
			'FileReferences' => $fileReferences,
			'FileType' => 'KTL'
		));
	}
	echo $content;	
} catch(BankWS\Exception $e) {
	echo $e->getMessage();
}

