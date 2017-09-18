<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

/**
 * Exception
 *
 * Custom general exception for showing more informative and appropriate error messages
 */
class SchemaValidationException extends Exception {
	
	public function __construct($text) {
		$this->errorType = 'SchemaValidationError';
		parent::__construct(null, $text);
	}
}
