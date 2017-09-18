<?php

require_once('../BankWS.php');

try {
	/*
	Esimerkkivastaus $file:
	
	FileReference       1043469
	UserFilename        STMV001.FMV8034L.WEBSER.VB1204.PS
	ParentFileReference 0815
	FileType            0250
	FileTimestamp       2011-08-15T19:59:25.741+03:00
	Status              NEW
	ForwardedTimestamp  2011-08-15T19:59:25.741+03:00
	Deletable           false
	CustomerNumber      05880836
	ModifiedTimestamp   2011-08-15T19:59:25.745+03:00
	SourceId            A
	Environment         PRODUCTION
	*/
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '12345678',
		'keyFolder' => '../keys/'
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Handelsbanken, $bankHandlerConfig);
	$fileList = $bankHandler->downloadFileList();
	
	?><table cellspacing="0">
		<?php foreach($fileList as $headers): ?>
		<tr>
			<?php foreach($headers as $header => $value): ?>
			<th><?php echo $header; ?></th>
			<?php endforeach; ?>
		</tr>
		<?php break; endforeach; ?>
		<?php foreach($fileList as $file): ?>
		<tr>
			<?php foreach($file as $key => $value): ?>
			<td><?php echo $value; ?></td>
			<?php endforeach; ?>
			<td><a href="HandelsbankenDownloadFile.php?FileType=<?php echo $file['FileType']; ?>&amp;FileReference=<?php echo $file['FileReference']; ?>" target="_blank">Download</a></td>
		</tr>
		<?php endforeach; ?>
	</table><?php
} catch(Exception $e) {
	echo 'Exception: '.$e->getMessage();
}

