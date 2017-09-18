<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

/**
 * BankHandlerConfig
 *
 * Container for all BankHandler related config information. Instance
 * of this must be supplied to getBankHandler function upon initialization.
 */
class BankHandlerConfig {
	
	public $customerId = false;
	public $targetId   = false;
	public $keyFolder  = false;
	public $testmode   = false;
	public $softwareId = 'BankWS';
	
	public function __construct($config = array()) {
		if(is_array($config)) {
			foreach($config as $key => $value) {
				if(isset($this->$key)) {
					$this->$key = $value;
				}
			}
		}
	}
}
