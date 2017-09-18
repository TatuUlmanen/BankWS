<?php
namespace BankWS;

/**
 * CreateCertificateRequest
 *
 * Wrapper for CertApplicationRequest. Identical in implementation but Sampo has
 * named its requests differently.
 */
class CreateCertificateRequest extends CertApplicationRequest {
	
	public function __construct($config = array(), $keychain) {
		$config['namespace_prefix'] = 'tns';
		$config['nodename'] = isset($config['nodename']) ? $config['nodename'] : 'CreateCertificateRequest';
		return parent::__construct($config, $keychain);
	}
	
	# The data is encrypted, no base64_encoding is necessary.
	# Overwrite base function that would return base64 data.
	public function __toString() {
		return trim(preg_replace('/^<\?xml.*?\?>/', '', $this->getXML()));
	}
}
