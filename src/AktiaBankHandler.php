<?php
namespace BankWS;

class AktiaBankHandler extends SamlinkBankHandler {
	
	const WEB_SERVICE_ADDRESS  = 'https://aineistopalvelut.aktia.fi/services/CorporateFileService';
	const CERT_SERVICE_ADDRESS = 'https://aineistopalvelut.aktia.fi/wsdl/CertificateService.xml';
	
	const WEB_SERVICE_TEST_ADDRESS  = 'https://aineistopalvelut.aktia.fi/services/CorporateFileService';
	const CERT_SERVICE_TEST_ADDRESS = 'https://aineistopalvelut.aktia.fi/wsdl/CertificateService.xml';
	
}
