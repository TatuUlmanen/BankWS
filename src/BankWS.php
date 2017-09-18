<?php
namespace BankWS;

/**
 * BankWS
 *
 * Main class for the library, handlers class initialization and contains common functions.
 */
class BankWS {
	
	# Constants for bank names, adds clarity when calling bank handlers and helps with error handling
	const Sampo         = 'Sampo';
	const Nordea        = 'Nordea';
	const OP            = 'OP';
	const Handelsbanken = 'Handelsbanken';
	const POP           = 'POP';
	const SP            = 'SP';
	const Aktia         = 'Aktia';
	
	static $keyFolder   = null;
	
	/**
	 * getBankHandler
	 *
	 * Factory for creating the appropriate BankHandler object according
	 * to the given handler variable and config data.
	 *
	 * Usage:
	 *   $bankHandler = \BankWS::getBankHandler(\BankWS::Nordea, $config);
	 */
	public static function getBankHandler($bankHandler, BankHandlerConfig $config) {
		if(!defined('self::'.$bankHandler)) {
			throw new Exception(null, 'Bank handler \''.$bankHandler.'\' does not exist');
		}
		
		if(!isset($config->keyFolder)) {
			throw new Exception(null, "Key folder not defined!");
		}
		
		self::$keyFolder = $config->keyFolder;
		
		$keychain    = new Keychain($bankHandler, $config->keyFolder);
		$bankHandler = '\BankWS\\'.$bankHandler.'BankHandler';
		
		if(class_exists($bankHandler)) {
			return new $bankHandler($config, $keychain);
		} else {
			throw new Exception(null, 'Unable to load BankHandler \''.$bankHandler.'\'');
		}
	}
	
	/**
	 * createCSR
	 *
	 * Creates a certificate signing request to be sent to the bank.
	 *
	 * returns an array containing the CSR and associated private key that
	 * is stored separately and not sent to the bank.
	 */
	public static function createCSR($options = array()) {
		
		$defaults = array(
			'outform'   => 'DER',
			'writepath' => self::$keyFolder,
			'keylength' => '2048',
			'password'  => '',
			'serialNumber' => '',
			'C'         => 'FI',            # Country name
			'ST'        => 'Pohjois-Savo',  # State or Province Name
			'L'         => 'Kuopio',        # Locality Name (eg. city)
			'O'         => 'NettiTieto Oy', # Organization Name (eg. company)
			'OU'        => '',              # Organizational Unit Name (eg. section)
			'CN'        => 'TEST',          # Common Name (eg, your name or your server's hostname)
			'email'     => ''               # Email Address
		);
		
		# Extend default options with the custom ones
		foreach($options as $key => $option) {
			if(isset($defaults[$key])) {
				$defaults[$key] = $option;
			}
		}
		
		$options = $defaults;
		# Normalize writepath
		
		if(!is_writable($options['writepath'])) {
			throw new Exception(null, 'Unable to write to "'.$options['writepath'].'". Check write permissions.');
		}
		
		# Build array of available config fields,
		# these are to be added to the temporary config file fed to OpenSSL.
		$req_distinquished_name = array();
		foreach(array('serialNumber', 'C', 'ST', 'L', 'O', 'OU', 'CN', 'email') as $key) {
			if($key == 'email') $key == 'emailAddress';
			
			if(isset($options[$key]) && !empty($options[$key])) {
				$req_distinguished_name[$key] = $options[$key];
			}
		}
		
		# Start creating temporary config file that is used in creation of the CSR,
		# see http://www.openssl.org/docs/apps/req.html#CONFIGURATION_FILE_FORMAT
		#$config  = "RANDFILE               = \$ENV::HOME/.rnd\n";
		#$config .= "\n";
		$config = "[ req ]\n";
		$config .= "default_bits           = {$options['keylength']}\n";
		$config .= "prompt                 = no\n";
		$config .= "output_password        = {$options['password']}\n";
		
		if(!empty($req_distinguished_name)) {
			$config .= "distinguished_name     = req_distinguished_name\n";
			$config .= "[ req_distinguished_name ]\n";
			foreach($req_distinguished_name as $key => $value) {
				$config .= "$key = $value\n";
			}
		}
		
		# Build paths, the results have to be written to the disk
		$hash       = md5(time()*rand());
		$configfile = rtrim($options['writepath'], '/').'/'.$hash.'.config';
		$keyfile    = rtrim($options['writepath'], '/').'/'.$hash.'.key';
		$csrfile    = rtrim($options['writepath'], '/').'/'.$hash.'.csr';
		
		# Save config file to disk so it's accessible to OpenSSL
		file_put_contents($configfile, $config);
		
		$commands = array();
		$commands['generatePrivateKeyCommand'] = sprintf('openssl genrsa -out %s %d', $keyfile, $options['keylength']);
		$commands['createCSRCommand']          = sprintf('openssl req -new -config %s -key %s -sha1 -out %s -outform %s', $configfile, $keyfile, $csrfile, $options['outform']);
		
		foreach($commands as $command) {
			system($command, $_output);
		}
		
		unlink($configfile);
		
		# Check if the creation was unsuccessful - no files are written if something was wrong
		if(!is_readable($csrfile) || !is_readable($keyfile)) {
			# Remove files that might be left
			if(file_exists($keyfile)) unlink($keyfile);
			if(file_exists($csrfile)) unlink($csrfile);
			throw new Exception(null, 'CSR creation failed');
		}
		
		# Create HMAC seal, might be used by the bank to verify the authenticity of the CSR
		$hmac = base64_encode(hash_hmac_file('sha1', $csrfile, $options['password'], true));
		
		# Build return array containing everything that was created
		$return = array(
			'private' => file_get_contents($keyfile),
			'csr'     => file_get_contents($csrfile),
			'hmac'    => $hmac
		);
		
		# Remove unneeded files
		unlink($keyfile);
		unlink($csrfile);
		
		return $return;
	}
	
	/**
	 * Utility for debugging XML - outputs the XML with
	 * correct headers so it's rendered correctly in the browser.
	 * Returns the XML if true is passed as the second argument.
	 */
	public static function outputXML($xml, $return = false) {
		if(!$return) {
			header('Content-type: text/xml');
			echo $xml;
			die();
		} else {
			$dom = new \DOMDocument();
			$dom->formatOutput = true;
			$dom->loadXML($xml);
			return $dom->saveXML();
		}
	}
	
	/**
	 * Generates a random request ID
	 */
	public static function generateRequestId() {
		return substr((rand() * time()), 0, 8);
	}
}
