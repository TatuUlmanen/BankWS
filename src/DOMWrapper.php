<?php
namespace BankWS;

/**
 * DOMWrapper
 *
 * Helper class for creating XML fragments.
 */
class DOMWrapper extends \DOMDocument {
	
	public $obj;
	public $doc;
	
	function __construct(\DOMNode $obj = null, \DOMDocument $doc = null) {
		# Doc references to the original document, obj to the current element inside that document
		$this->doc = is_null($doc) ? new \DOMDocument : $doc;
		$this->obj = is_null($obj) ? $this->doc->documentElement : $obj;
		parent::__construct();
	}
	
	public function loadXML($xml) {
		$this->doc->loadXML($xml);
		$this->obj = $this->doc->documentElement;
	}
	
	public function saveXML() {
		return $this->doc->saveXML();
	}
	
	/**
	 * Helper function for appending and element with value to the current object
	 */
	function append($tag, $content = null, $ns = null, $ns_url = null) {
		if(is_null($content)) {
			if(is_null($ns)) {
				$element = $this->doc->createElement($tag);
			} else {
				$element = $this->doc->createElementNS($ns_url, $ns.':'.$tag);
			}
		} else {
			if(is_null($ns)) {
				$element = $this->doc->createElement($tag, $content);
			} else {
				$element = $this->doc->createElementNS($ns_url, $ns.':'.$tag, $content);
			}
		}
		
		if(is_null($this->obj)) {
			$newnode = $this->doc->appendChild($element);
			$this->obj = $newnode;
		} else {
			$newnode = $this->obj->appendChild($element);
		}
		
		return new DOMWrapper($newnode, $this->doc);
	}
	
	function output() {
		$this->obj->formatOutput = true;
		echo htmlentities($this->obj->saveXML());
	}
	
	function setAttribute($key, $value) {
		$this->obj->setAttribute($key, $value);
		return $this;
	}
	
	function setAttributeNS($namespaceURI, $qualifiedName, $value) {
		$this->obj->setAttributeNS($namespaceURI, $qualifiedName, $value);
	}
	
	public function getElementsByTagName($tag) {
		$_elements = $this->obj->getElementsByTagName($tag);
		$elements = array();
		$i = 0;
		while($element = $_elements->item($i++)) {
			$elements[] = new DOMWrapper($element, $this->doc);
		}
		return $elements;
	}
	
	public function getElementByTagName($tag) {
		$elements = $this->getElementsByTagName($tag);
		return reset($elements);
	}
	
	public function getNodeName() {
		return $this->obj->nodeName;
	}
	
	public function getNodeValue() {
		return $this->obj->nodeValue;
	}
	
	public function getTextContent() {
		return $this->obj->textContent;
	}
	
	public function getParentNode() {
		return new DOMWrapper($this->obj->parentNode, $this->doc);
	}
	
	public function getPrevSibling() {
		return new DOMWrapper($this->obj->prevSibling, $this->doc);
	}
	
	public function getNextSibling() {
		return new DOMWrapper($this->obj->nextSibling, $this->doc);
	}
	
	public function getDocumentElement() {
		return new DOMWrapper($this->doc->documentElement, $this->doc);
	}
	
	public function getChildNodes() {
		$childs = array();
		foreach($this->obj->childNodes as $child) {
			$childs[] = new DOMWrapper($child, $this->doc);
		}
		return $childs;
	}
	
	public function getOuterXML() {
		return $this->obj->ownerDocument->saveXML($this->obj);
	}
	
	/**
	 * Gets the innerHTML of the tag, removing namespace definitions
	 */
	public function getInnerXML() { 
		$innerHTML = ''; 
		$children = $this->obj->childNodes;
		foreach ($children as $child) { 
			$innerHTML .= $child->ownerDocument->saveXML($child); 
		} 
		return preg_replace('~<(/)?([^<> ]*?:)([^<>]*?)>~', '<$1$3>', $innerHTML);
	}
	
	/**
	 * Alias to getInnerXML()
	 */
	public static function getInnerHTML($node) {
		return self::getInnerXML($node);
	}
	
	/**
	 * Returns the DOM as an array
	 */
	public function toArray() {
		return self::xmlToArray($this->doc);
	}
	
	public static function xmlToArray(\DOMDocument $document) {
		$root = $document->documentElement;
		return self::xmlToArrayParser($root);
	}
	
	public static function xmlToArrayParser(\DOMNode $node) {
		
		$return = array(
			'name' => $node->nodeName,
			'value' => null,
			'children' => array()
		);
		
		if($node->childNodes->length > 0) {
			if($node->childNodes->item(0)->nodeType == XML_TEXT_NODE) {
				$return['value'] = $node->nodeValue;
			} else {		
				foreach($node->childNodes as $child) {
					$return['children'][] = self::xmlToArrayParser($child);
				}
			}
		}
		
		return $return;
	}
}
