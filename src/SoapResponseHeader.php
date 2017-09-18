<?php
namespace BankWS;

/**
 * SoapResponseHeader
 *
 * Container for the headers of an SoapResponse message. Implements iterator interface.
 */
class SoapResponseHeader implements \Iterator {
	
	private $headers = array();
	private $position = 0;
	
	public function __set($key, $value) {
		$this->headers[] = array($key, $value);
	}
	
	public function __get($key) {
		foreach($this->headers as $header) {
			if($header[0] == $key) {
				return $header[1];
			}
		}
		return null;
	}
	
	public function current() {
		return $this->headers[$this->position][1];
	}
	
	public function key() {
		return $this->headers[$this->position][0];
	}
	
	public function rewind() {
		$this->position = 0;
	}
	
	public function next() {
		++$this->position;
	}
	
	public function valid() {
		return isset($this->headers[$this->position]);
	}
}
