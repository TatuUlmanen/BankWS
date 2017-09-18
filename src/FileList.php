<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

/**
 * FileList
 *
 * Handles the response from DownloadFileList requests. Implements iterator interface.
 */
class FileList implements \Iterator {
	
	public $soapResponse;
	private $files;
	private $position = 0;
	
	public function __construct(SoapResponse $soapResponse) {
		$this->soapResponse = $soapResponse;
		$files = array();
		$applicationResponse = $soapResponse->applicationResponse->getArray();
		
		if(isset($applicationResponse['FileDescriptors']) && !empty($applicationResponse['FileDescriptors'])) {
			foreach($applicationResponse['FileDescriptors'] as $fileDescriptor) {
				$files[] = array($fileDescriptor['FileReference'], $fileDescriptor);
			}
		}
		
		$this->files = $files;
	}
	
	public function __set($key, $value) {
		$this->files[] = array($key, $value);
	}
	
	public function __get($key) {
		foreach($this->files as $file) {
			if($file[0] == $key) {
				return $file[1];
			}
		}
		return null;
	}
	
	public function current() {
		return $this->files[$this->position][1];
	}
	
	public function key() {
		return $this->files[$this->position][0];
	}
	
	public function rewind() {
		$this->position = 0;
	}
	
	public function next() {
		++$this->position;
	}
	
	public function valid() {
		return isset($this->files[$this->position]);
	}
}
