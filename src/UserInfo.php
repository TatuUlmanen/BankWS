<?php
namespace BankWS;

if(!defined('BWS_ROOT_PATH')) {
	die('No direct access allowed.');
}

/**
 * UserInfo
 *
 * Handles response from GetUserInfo requests
 */ 
class UserInfo {
	
	public $userFileTypes;
	public $soapResponse;
	
	public function __construct(SoapResponse $soapResponse) {
		
		$this->soapResponse = $soapResponse;		
		$userFileTypes = array();
		
		# Parse available filetypes for a user into a more accessible format
		foreach($soapResponse->applicationResponse->UserFileTypes as $_userFileType) {
			$userFileType = array();
			foreach($_userFileType[1] as $node) {
				# Node[0] is the key, $node[1] the value
				if($node[0] == 'FileTypeServices') {
					$fileTypeServices = array();
					foreach($node[1] as $_fileTypeService) {
						$fileTypeService = array();
						foreach($_fileTypeService[1] as $child) {
							$fileTypeService[$child[0]] = $child[1];
						}
						$fileTypeServices[] = $fileTypeService;
					}
					$userFileType[$node[0]] = $fileTypeServices;
				} else {
					$userFileType[$node[0]] = $node[1];
				}
			}
			$userFileTypes[$userFileType['FileType']] = $userFileType;
		}
		
		$this->userFileTypes = $userFileTypes;
	}
	
}
