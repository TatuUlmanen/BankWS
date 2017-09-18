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
class Exception extends \Exception {
	
	public $code;
	public $text;
	public $message;
	
	public $request;
	public $applicationRequest;
	public $response;
	
	protected $errorType = 'Error';
	
	public function __construct($code, $text, $originator = null) {
		
		# Get calling function from backtrace
		$trace = $this->getTrace();
		
		$this->code = $code;
		$this->text = $text;
		
		# The class and method that raised the exception
		$this->originator = $trace[0]['class'].'\\'.$trace[0]['function'].'()';
		
		# Build message
		$this->message = preg_replace('~(\s)+~', '$1', rtrim($this->errorType
				.(is_null($this->code)       ? '' : ' '.$this->code)
				.(is_null($this->originator) ? '' : ' in '.$this->originator)
				.(is_null($this->text)       ? '' : ': '.htmlentities($this->text))
			, '. ').'.');
		
		# Error code 12 is "Schema validation failed", usually means
		# the XML fields are in the wrong order. Add a message to clarify this.
		if($this->code == 12) {
			$this->message .= ' Make sure that XML fields are in correct order.';
		}
		
		parent::__construct($this->message);
	}
}
