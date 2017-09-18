<?php
namespace BankWS;

/**
 * GetBankCertificateRequest
 *
 * Wrapper for CertApplicationRequest. Identical in implementation but Sampo has
 * named its requests differently.
 */
class GetBankCertificateRequest extends CertApplicationRequest {
	
	public function __construct($config = array(), $keychain) {
		$config['nodename'] = isset($config['nodename']) ? $config['nodename'] : 'GetBankCertificateRequest';
		$config['namespace'] = 'http://danskebank.dk/PKI/PKIFactoryService/elements';
		$config['namespace_prefix'] = 'elem';
		return parent::__construct($config, $keychain);
	}
	
	# The data is encrypted, no base64_encoding is necessary.
	# Overwrite base function that would return base64 data.
	public function __toString() {
		return $this->document->saveXML();
	}
}
