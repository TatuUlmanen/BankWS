<?php

require_once('../BankWS.php');

try {
	$bankHandlerConfig = new \BankWS\BankHandlerConfig(array(
		'customerId' => '11111111',
		'targetId'   => '11111111A1',
		'keyFolder'  => '../keys/',
		'testmode'   => true,
	));
	
	$bankHandler = \BankWS\BankWS::getBankHandler(\BankWS\BankWS::Nordea, $bankHandlerConfig);
	$userInfo = $bankHandler->getUserInfo();
	
	$fileType = isset($_POST['FileType']) ? $_POST['FileType'] : 'KTL';
	
	?><style type="text/css">
	table {
		border-collapse: collapse;
	}
	
	table th {
		padding: 5px;
		text-align: left;
	}
	
	table td {
		border: 1px solid #ccc;
		padding: 5px;
	}
	</style>
	<form method="post" action="NordeaDownloadFileList.php">
		<select name="FileType">
			<?php foreach($userInfo->userFileTypes as $userFileType): ?>
			<option value="<?php echo $userFileType['FileType']; ?>"<?php if($fileType == $userFileType['FileType']) echo ' selected="selected"'; ?>>
				<?php echo $userFileType['FileTypeName']; ?>
			</option>
			<?php endforeach; ?>
		</select>
		<input type="submit" value="Go" />
	</form><?php
	
	try {
		$fileList = $bankHandler->downloadFileList(array(
			'FileType' => $fileType
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
				<td><a href="NordeaDownloadFile.php?FileType=<?php echo $file['FileType']; ?>&amp;FileReference=<?php echo $file['FileReference']; ?>" target="_blank">Download</a></td>
			</tr>
			<?php endforeach; ?>
		</table><?php
	} catch(BankWS\Exception $e) {
		echo '<p>'.$e->getMessage().'</p>';
	}
} catch(Exception $e) {
	echo $e->getMessage();
}
