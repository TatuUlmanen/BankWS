<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '1000016580',
		'keyFolder'  => '../keys/',
		'testmode'   => true
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::OP, $bankHandlerConfig);
	
	$fileList = $bankHandler->downloadFileList(array(
		'FileType' => 'pain.001.001.02'
	));
	
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
			<td><a href="OPDownloadFile.php?FileType=<?php echo $file['FileType']; ?>&amp;FileReference=<?php echo $file['FileReference']; ?>" target="_blank">Download</a></td>
		</tr>
		<?php endforeach; ?>
	</table><?php
} catch(Exception $e) {
	echo 'Exception: '.$e->getMessage();
}

