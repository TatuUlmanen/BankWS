<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

class Keychain {
	
	private $bankHandler;
	private $keychain = array();
	private $keychainFolder;
	
	function __construct($bankHandler, $keychainFolder) {
		$this->bankHandler = $bankHandler;
		$this->keychainFolder = rtrim($keychainFolder, '/').'/';
	}
	
	function addKey($name, $key) {
		if(strpos($name, '-') !== false) {
			throw new KeychainException("Illegal character '-' in key name");
		}
		file_put_contents($this->getKeyPath($name, false), $key);
	}
	
	function removeKey($name) {
		$path = $this->getKeyPath($name);
		if(file_exists($path)) {
			return unlink($path);
		} else {
			return false;
		}
	}
	
	function getKey($name) {
		$path = $this->getKeyPath($name);
		if(file_exists($path)) {
			return file_get_contents($path);
		} else {
			return false;
		}
	}
	
	public function getKeyPath($name, $only_if_exists = true) {
		$name = pathinfo($name, PATHINFO_FILENAME);
		if($name == 'SSLCertificate') {
			$path = BWS_ROOT_PATH.'keys/'.$this->bankHandler.'-SSLCertificate.cer';
		} else {
			$path = $this->keychainFolder.$this->bankHandler.'-'.$name.'.cer';
		}
		return !$only_if_exists || file_exists($path) ? $path : false;
	}
}
