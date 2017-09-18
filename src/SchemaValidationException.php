<?php
namespace BankWS;

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
