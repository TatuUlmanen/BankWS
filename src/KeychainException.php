<?php
namespace BankWS;

/**
 * Exception
 *
 * Custom general exception for showing more informative and appropriate error messages
 */
class KeychainException extends Exception {
	
	public function __construct($text) {
		$this->errorType = 'KeychainError';
		parent::__construct(null, $text);
	}
}
