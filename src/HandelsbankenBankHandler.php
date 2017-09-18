<?php
namespace BankWS;

class HandelsbankenBankHandler extends SamlinkBankHandler {
	
	const FILETYPE_REFERENCE_PAYMENTS = '0250';
	const FILETYPE_FINVOICE_SEND      = '0800';
	const FILETYPE_FINVOICE_RECEIVE   = '0820';
	const FILETYPE_FINVOICE_FEEDBACK  = '0810';
	
}
