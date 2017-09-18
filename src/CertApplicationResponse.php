<?php
namespace BankWS;

/**
 * CertApplicationResponse
 *
 * Contains the response to CertApplicationRequest
 */
class CertApplicationResponse extends ApplicationResponse {
	
	/**
	 * Utility to fetch certificates from the response
	 */
	public function getCertificates() {
		# Get array representation of the DOM
		$dom = $this->getArray();
		$certs = array();
		# Certificates are contained within <Certificates> element and are in base64 encoded binary format
		if(isset($dom['Certificates'])) {
			foreach($dom['Certificates'] as $cert) {
				$certs[] = "-----BEGIN CERTIFICATE-----\n".chunk_split($cert['Certificate'], 64, "\n")."-----END CERTIFICATE-----";
			}
		}
		
		# Sampo does things its own way. Keys are not within <Certificates> but as separate named nodes directly under response. 
		foreach(array('EncryptionCert', 'SigningCert', 'CACert') as $key) {
			if(isset($dom[$key])) {
				$certs[$key] = "-----BEGIN CERTIFICATE-----\n".chunk_split($dom[$key], 64, "\n")."-----END CERTIFICATE-----";
			}
		}
		
		return $certs;
	}
}
