<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

/**
 * ApplicationResponse
 * 
 * Specifies the content to be returned inside SoapResponse. Implements iterator interface.
 */
class ApplicationResponse extends Response implements \Iterator {
	
	protected $response = array();
	protected $dom;
	protected $keychain;
	private $position = 0;
	
	public function __construct($xml, $keychain) {
		$this->keychain = $keychain;
		$this->response = $this->load($xml);
	}
	
	/**
	 * Loads and parses the ApplicationResponse XML.
	 * Called recursively on multidimensional XML fragments.
	 */
	public function load($xml, $root = true) {
		
		$dom = new DOMWrapper();
		$dom->loadXML($xml);
		
		
		# If this element is the starting point, save the document element for later use
		if($root) {
			$this->dom = $dom;
		}
		
		$response     = array();
		$responseBody = $dom->getDocumentElement();
		
		if($root) {
			parent::validate($dom);
		}
		
		# Iterate through all immediate child objects of the root element
		foreach($responseBody->getChildNodes() as $child) {
			
			# Normalize node name, strip namespace
			list(,$key) = explode(':', strpos($child->getNodeName(), ':') !== false ? $child->getNodeName() : ':'.$child->getNodeName());
			# Don't process Signature element or text nodes, not needed
			if($key == 'Signature' || $key == '#text') continue;
			
			# Get inner content of the child element
			$value = trim($child->getInnerXML());
			
			# If the child appears to contain more XML, recursively parse the child
			if(!empty($value) && $value[0] == '<') {
				# Add containing tag to the child's innerXML - in essence, get child's outerXML
				$value = sprintf('<%s>%s</%s>', $key, $value, $key);
				# Parse the XML
				$value = $this->load($value, false);
			}
			
			$response[] = array($key, $value);
		}
		return $response;
	}
	
	# Gets a raw array representation of the response, in situations where iteration is not versatile enough.
	public function getArray($array = null) {
		
		$array = is_array($array) ? $array : $this->response;
		$response = array();
		
		$keys = array();
		
		foreach($array as $key => $value) {
			$keys[] = $value[0];
		}
		
		$numeric_keys = count(array_unique($keys)) == 1 && count($keys) > 1;
		
		foreach($array as $key => $value) {
			$response[$numeric_keys ? $key : $value[0]] = is_array($value[1]) ? $this->getArray($value[1]) : $value[1];
		}
		
		return $response;
	}
	
	public function __set($key, $value) {
		$this->response[] = array($key, $value);
	}
	
	public function __get($key) {
		foreach($this->response as $response) {
			if($response[0] == $key) {
				return $response[1];
			}
		}
		return null;
	}
	
	public function current() {
		return $this->response[$this->position][1];
	}
	
	public function key() {
		return $this->response[$this->position][0];
	}
	
	public function rewind() {
		$this->position = 0;
	}
	
	public function next() {
		++$this->position;
	}
	
	public function valid() {
		return isset($this->response[$this->position]);
	}
}
