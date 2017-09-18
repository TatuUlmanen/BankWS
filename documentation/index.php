<!DOCTYPE html>
<html>
<head>
	<title>BankWS</title>
	<script type="text/javascript" src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="jquery.tableofcontents.min.js"></script>
	<script type="text/javascript" src="common.js"></script>
	<link rel="stylesheet" type="text/css" href="screen.css" />
	<link rel="stylesheet" type="text/css" href="print.css" media="print" />
</head>
<body>
<div id="sidebar">
	<ul id="toc"></ul>
</div>
<div id="documentation">
	<?php
	require('markdown.php');
	echo Markdown(file_get_contents('BankWS.md'));
	?>
</div>
<p style="clear: both;"></p>
</body>
</html>
