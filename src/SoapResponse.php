<?php
namespace BankWS;

/**
 * SoapResponse
 * 
 * Response received from SoapRequest.
 */
class SoapResponse extends Response {
	
	public $header;
	public $applicationResponse;
	public $xml;
	public $command;
	public $operation;
	
	private $keychain;
	
	public function __construct($xml = null, $keychain = null) {
		# Initialize variables
		$this->keychain = $keychain;
		$this->xml = (object)array('request' => null, 'applicationRequest' => null, 'response' => null);
		$this->header = new SoapResponseHeader();
		# Load XML if given
		if(!is_null($xml)) {
			$this->load($xml);
		}
	}
	
	/**
	 * Loads and parses the given XML string
	 */
	public function load($xml) {
		
		$dom = new DOMWrapper();
		$dom->loadXML($xml);
		parent::validate($dom);
		
		$responseHeaderNodes = $dom->getElementsByTagName('ResponseHeader');
		
		if(empty($responseHeaderNodes)) {
			throw new Exception(null, 'No response header found');
		}
		
		$responseHeaderNode = reset($responseHeaderNodes);
		
		# Get all elements in the header
		$responseHeaders = $responseHeaderNode->getElementsByTagName('*');
		
		# Loop through all header elements
		$i = 0;
		foreach($responseHeaders as $header) {
			# Normalize the node name, so that it always contains : even with no namespace, the explode and get the actual tagname
			list(,$key) = explode(':', strpos($header->getNodeName(), ':') !== false ? $header->getNodeName() : ':'.$header->getNodeName());
			$value = $header->getNodeValue();
			# Append to the header
			$this->header->$key = $value;
		}
		
		# Find the applicationResponse element, it's the one after ResponseHeader element.
		$applicationResponse = $responseHeaderNode->getNextSibling();
		
		# Nordea might have whitespace characters between ResponseHeader and ApplicationResponse elements
		if($applicationResponse->getNodeName() == '#text') {
			$applicationResponse = $applicationResponse->getNextSibling();
		}
		
		# Get the node name. It should be always ApplicationResponse or CertApplicationRepons
		list($applicationResponseNodeName) = array_reverse(explode(':', $applicationResponse->getNodeName()));
		
		if(!is_null($applicationResponse)) {
			
			$applicationResponseString = $applicationResponse->getInnerXML();
			
			# Try to decode, some content may be decoded, some not
			if($base64_decoded = base64_decode($applicationResponseString)) {
				# If it's decoded, there's an extra layer of wrapping, remove it
				$tmp_dom = new DOMWrapper();
				$tmp_dom->loadXML($base64_decoded);
				list($applicationResponseNodeName) = array_reverse(explode(':', $tmp_dom->doc->documentElement->nodeName));
				$applicationResponseString = $tmp_dom->getOuterXML();
			} else {
				$applicationResponseString = sprintf('<%s>%s</%s>', $applicationResponseNodeName, $applicationResponseString, $applicationResponseNodeName);
			}
			
			$applicationResponseType   = __NAMESPACE__.'\\'.$applicationResponseNodeName;
			# Build the application response
			$this->applicationResponse = new $applicationResponseType($applicationResponseString, $this->keychain);
		} else {
			throw new Exception(null, 'ApplicationResponse element not found in response');
		}
	}
}
