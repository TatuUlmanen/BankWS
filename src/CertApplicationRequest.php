<?php
namespace BankWS;

/**
 * CertApplicationRequest
 *
 * Used to request a new certificate from the server. Otherwise similar to the
 * ApplicationRequest class but the root node is named differently.
 */
class CertApplicationRequest extends ApplicationRequest {
	
	public function __construct($config = array(), $keychain) {
		$config['nodename'] = isset($config['nodename']) ? $config['nodename'] : 'CertApplicationRequest';
		return parent::__construct($config, $keychain);
	}
}
