<?php
namespace BankWS;

/**
 * Response
 * 
 * Shared methods for ApplicationResponse and SoapResponse
 */
class Response {
	
	protected function validate($dom) {
		$responseCode = null;
		$responseText = 'Response was empty or no ResponseText was not found';
		
		# Look for response codes and texts
		foreach(array('faultcode', 'ResponseCode', 'ReturnCode') as $responseCodeTag) {
			$responseCodeNodes = $dom->getElementsByTagName($responseCodeTag);
			if(!empty($responseCodeNodes)) {
				$responseCode = $responseCodeNodes[0]->getTextContent();
				if(is_numeric($responseCode)) {
					$responseCode = (int)$responseCode;
				}
				break;
			}
		}
		
		foreach(array('faultstring', 'ResponseText', 'ReturnText') as $responseTextTag) {
			$responseTextNodes = $dom->getElementsByTagName($responseTextTag);
			if(!empty($responseTextNodes)) {
				$responseText = $responseTextNodes[0]->getTextContent();
				break;
			}
		}
		
		# Sampo might give additional information in a special tag
		$additionalReturnTextNodes = $dom->getElementsByTagName('AdditionalReturnText');
		if(!empty($additionalReturnTextNodes)) {
			$responseText .= ': '.$additionalReturnTextNodes[0]->textContent;
		}
		
		# ResponseCode of anything else than 0 means an error has occurred
		if($responseCode !== 0) {
			throw new Exception($responseCode, $responseText);
		}
	}
}
	
	
