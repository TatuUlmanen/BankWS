<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

/**
 * EncryptedData
 *
 * Contains the encrypted response to ApplicationRequest
 */
class EncryptedData extends ApplicationResponse {
	
	public function __construct($xml, $keychain) {
		$this->keychain = $keychain;
		$decrypted = $this->decrypt($xml);
		$this->response = $this->load($decrypted);
	}
	
	public function decrypt($xml) {
		
		$dom = new DOMWrapper();		
		$dom->loadXML($xml);
		
		$objenc = new \XMLSecEnc();
		$encData = $objenc->locateEncryptedData($dom->doc);
		
		if(!$encData) {
			throw new Exception(null, "Cannot locate Encrypted Data");
		}
		
		$objenc->setNode($encData);
		$objenc->type = $encData->getAttribute("Type");
		if (!$objKey = $objenc->locateKey()) {
			throw new Exception(null, "Cannot locate encryption algorithm");
		}
		
		$key = NULL;
		
		if ($objKeyInfo = $objenc->locateKeyInfo($objKey)) {
			if ($objKeyInfo->isEncrypted) {
				$objencKey = $objKeyInfo->encryptedCtx;
				$objKeyInfo->loadKey($this->keychain->getKeyPath('CustomerEncryptionPrivate'), true, false);
				$key = $objencKey->decryptKey($objKeyInfo);
			}
		}
		
		if (! $objKey->key && empty($key)) {
			locateLocalKey($objKey);
		}
		
		if (empty($objKey->key)) {
			$objKey->loadKey($key);
		}
		
		$token = NULL;

		if ($decrypt = $objenc->decryptNode($objKey, TRUE)) {
			$output = NULL;
			if ($decrypt instanceof DOMNode) {
				if ($decrypt instanceof DOMDocument) {	
					$output = $decrypt->saveXML();
				} else {
					$output = $decrypt->ownerDocument->saveXML();
				}
			} else {
				$output = $decrypt;
			}
		}
		
		return $output->ownerDocument->saveXML();
	}
}
