<?php

define(VC_SIZE_TOTAL, '32');
define(VC_SIZE_ORG_ID, '24');
define(VC_SIZE_MOBILE, '24');
define(VC_SIZE_PURPOSE, '3');
define(VC_SIZE_TIME_FIXED, '5');
define(VC_SIZE_TIME_RANDOM, '8');# 1 day is 86400 secs -> 17 bits
define(VC_TIME_DISCARD_RIGHT, '10'); # 10 bits of seconds is 17.0677 mins
define(VC_MAX_CODE_LEN, '30');

define(VC_TIME_RANDOM_REPEAT, '4');
define(VC_TIME_RANDOM, '32');
define(VC_PURPOSE_REGISTRATION, '0');
define(VC_PURPOSE_BILLING, '1');
define(VC_PURPOSE_REDEMPTION, '2');
define(VC_PURPOSE_LOGIN, '3');

class ValidationCode extends Base {
	private $logger;
	private $maxCodeBitMap;
	private $maxCodeBitMapHex;
	
	public function __construct() {
		global $logger;
		parent::__construct();
		$this->logger = $logger;
		$this->maxCodeBitMap = pow(2, VC_MAX_CODE_LEN) - 1; # this is applied to call except Mobile Code
		$this->maxCodeBitMapHex = "3fffffff";
		$this->logger->debug("Max Code: $this->maxCodeBitMap, hex=".$this->toHex($this->maxCodeBitMap));		
	}
	
	private function toBinary($base10_number) {
		return base_convert($base10_number, 10, 2);
	}
	
	private function fromBinary($binary_number) {
		return base_convert($binary_number, 2, 10);
	}
	
	private function fromHexToBase36($hex) {
		return base_convert($hex, 16, 36);
	}
	
	private function fromBase36ToHex($b36) {
		return base_convert($b36, 36, 16);
	}
	
	private function toBase36($base10_number) {
		return  base_convert($base10_number, 10, 36); 
	}
	
	private function fromBase36($base36_number) {
		return  base_convert($base36_number, 36, 10); 
	}
	
	private function fromHex($hex_number) {
		return base_convert($hex_number, 16, 10);
	}
	
	private function toHex($dec_number) {
		return base_convert($dec_number, 10, 16);
	}
	
	private function makeCode($str) {
		return sprintf("%08s", $str);
	}
	
	/**
	 * Converts code. Step 1 : convert code to hex. Step 2 : strAnd(codehex, maxCodeBitMapHex). Step 3 : return makeCode($codehex_after_str_and) 
	 * @param unknown_type $code
	 * @param unknown_type $code_name
	 */
	private function convertCode($code, $code_name){
		
		$this->logger->debug("Converting $code_name code : $code ");
		
		$codehex = $this->toHex($code);
		
		$this->logger->debug("$code_name hex before bit masking: ".$codehex." Max Code Bitmap Hex: ".$this->maxCodeBitMapHex);
		
		$codehex = $this->strAnd(array($this->maxCodeBitMapHex, $codehex));
		$this->logger->debug("$code_name code hex after bit masking: ".$codehex);
		
		$conv = $this->makeCode($codehex);
		
		$this->logger->debug("After Converting. $code_name code : $code. Converted code : $conv");
				
		return $conv;
	}
	
	private function findMobileCode($mobile, $email, $external_id) {
		if ($mobile == false || $mobile == "") $mobile = $external_id;
		if ($mobile == false || $mobile == "") $mobile = $email;
		$md5 = md5((string)$mobile);
		
		$hex_code = substr($md5, -8, 8);
		$code = $this->fromHex($hex_code);
		
//		echo "Computed MD5 - ".$md5;
//		echo "\nMD5(KK) - ".md5("919748088727")."\nMD5(Modu) - ".md5((string)919903606590);
//		echo "\nHexCode: $hex_code\nMobile Code: $code: ";
		
		//return $this->makeCode($this->toHex($code));		
		return $this->convertCode($code, 'Mobile');
	}
	
	private function findOrgCode($org, $purpose, $time_fixed) {
		$org_id_bits = ($org->org_id % (1 << VC_SIZE_ORG_ID)) << (VC_SIZE_PURPOSE + VC_SIZE_TIME_FIXED) ;
		$purpose_bits = ($purpose % (1 << VC_SIZE_PURPOSE)) << VC_SIZE_TIME_FIXED;
		$time_bits = $time_fixed;
		$code1 = $org_id_bits | $purpose_bits | $time_bits;
		
		$this->logger->debug(<<<EOSTR
Org ID Bits    : $org_id_bits
Purpose Bits   : $purpose_bits
Time Bits      : $time_bits
Code1          : $code1
EOSTR
);

		//return $this->makeCode($this->toHex($code1));
		return $this->convertCode($code1, 'Org');

	}
	
	private function findRandomTimeCode($time_random) {
		$code3 = 0;
		for ($i = 0; $i < VC_TIME_RANDOM_REPEAT; $i++) {
			$bits = $time_random << ($i * VC_SIZE_TIME_RANDOM);
			$code3 = $code3 | $bits;
		}
		//$code3 = $this->makeCode($this->toHex($code3));
		//return $code3;

		return $this->convertCode($code3, 'Random Time');
	}
	
	private function findAdditionalBitsCode($additional_bits, $store_id){
		$code4 = $additional_bits;
		//$store = UserProfile::getById($store_id);
		$config_manager = new ConfigManager();
		if ($config_manager->getKey(CONF_VALIDATION_IS_STORE_ID_INCLUDED)) {
			$code4 += $store_id;
		}	
		
		return $this->convertCode($code4, 'Additional Bits');
	}
	
	private function strXor(array $arr, $blocksize = 1, $len = 8) {
		$res = "";
		
		$log = "Doing XOR of: ";
		for ($i = 0; $i < count($arr); $i++) {
			if (count($arr[$i]) > $len) $arr[$i] = substr($arr[$i], -$len, $len);
			$arr[$i] = sprintf("%08s", $arr[$i]);
			$log .= "\n$i. ".$arr[$i];
		}
		
		for ($i = 0; $i < $len; $i += $blocksize) {
			$accumulator = "0";
			$log .= "\n";
			for ($j = 0; $j< count($arr); $j++) {
				
				$char = $this->fromHex($arr[$j][$i]);
				$log .= "$char ";
				$accumulator = (string) ((int)$accumulator ^ (int)($char));		
			}
			$log .= "=$accumulator";
			$res .= (string) $this->toHex($accumulator);
		}
		
		$log .= "\nR. $res";
		$this->logger->debug($log);
		return (string) $res;
	}
	
	private function strAnd(array $arr, $blocksize = 1, $len = 8) {
		$res = "";
		$log .= "Doing AND of: ";
		for ($i = 0; $i < count($arr); $i++) {
			if (count($arr[$i]) > $len) $arr[$i] = substr($arr[$i], -$len, $len);
			$arr[$i] = sprintf("%08s", $arr[$i]);
			$log .= "\n$i. ".$arr[$i];
		}
		$log .= "\n--------\n";
		
		for ($i = 0; $i < $len; $i += $blocksize) {
			$accumulator = 15;
			$log .= "\n Bit ".(8-$i).". -> ";
			for ($j = 0; $j< count($arr); $j++) {
				
				$char = $this->fromHex($arr[$j][$i]);
				$log .= "$char ";
				$accumulator = (string) ((int)$accumulator & (int)($char));		
			}
			$log .= "=$accumulator";
			$res .= (string) $this->toHex($accumulator);
		}
		
		$log .= "\nR. $res";
		$this->logger->debug($log);
		return (string) $res;
	}
	

	
	/**
	 * Issue a validation code for the user
	 * @param $org OrgProfile object for this org
	 * @param $mobile Mobile number for this user
	 * @param $purpose Purpose of code
	 * @param $issue_time Time of issue
	 * @param $store_id ID of the store/issuing user
	 * @param $additional_bits any additional bits for validation
	 * @return string Validation Code
	 */
	public function issueValidationCode($org, $mobile, $external_id, $email, $purpose, $issue_time, $store_id, $additional_bits = 0) {
		
		if ($issue_time == false) $issue_time = time();
		if ( !empty( $mobile ) && ( (!Util::shouldBypassMobileValidation() && !Util::checkMobileNumberNew($mobile, array(), false)) 
			|| !Util::isMobileNumberValid($mobile)) && $external_id == '' ) return false;
		
		$time = $issue_time >> VC_TIME_DISCARD_RIGHT;
		$time_random = $time % (1 << VC_SIZE_TIME_RANDOM);
		$time_fixed = ($time >> VC_SIZE_TIME_RANDOM) % (1 << VC_SIZE_TIME_FIXED);
		
		
		$code1 = $this->findOrgCode($org, $purpose, $time_fixed);
		
		$code2 = $this->findMobileCode($mobile, $email, $external_id);
		
		$code3 = $this->findRandomTimeCode($time_random);
		
		$code4 = $this->findAdditionalBitsCode($additional_bits, $store_id);
		
		$code = $this->fromHexToBase36($this->strXor(array($code1, $code2, $code3, $code4)));
		
		
		
		$this->logger->debug(<<<EOSTR
Issue Time    : $issue_time

OrgID         : $org->org_id
Time Fixed    : $time_fixed
Code 1        : $code1

Mobile        : $mobile
Code 2        : $code2

Time Random   : $time_random
Code 3        : $code3

Additional Bits :  $additional_bits , Store id : $store_id 
Code 4		  : $code4

Code          : $code

EOSTR
);

		return strtoupper($code);
	}
	
	function checkUserPassword( $username, $password, $org_id ){
		
		$auth = new Auth();
		
		if( Util::checkEmailAddress( $username ) )
			return $auth->loginByEmail( $username, $password, $org_id );
		else
			return $auth->loginByExternalIdPwdHash( $username, $password, $org_id );
	}
	
	/**
	 * Check the validation code
	 * @param $code Code to check
	 * @param $org OrgProfile of the org in question
	 * @param $mobile Mobile number of the user
	 * @param $purpose Purpose of login
	 * @param $time_now Current time
	 * @param $store_id ID of the store
	 * @param $additional_bits any additional bits for validation
	 * @return bool True on Validated
	 */
	function checkValidationCode($code, $org, $mobile, $external_id, $purpose, $time_now, $store_id, $additional_bits = 0) {
		if(Util::shouldBypassValidationCodeVerification())
		{
			$this->logger->debug("Mock Mode for validation code is enabled,
					so skiping validation code verification");
			return true;
		}
		else
		{
			$this->logger->debug("Mock Mode is not enabled, going to verify validation code");
		}
		if ( ((!Util::shouldBypassMobileValidation() && !Util::checkMobileNumberNew($mobile, array(), false))
				|| !Util::isMobileNumberValid($mobile) ) && $external_id == '') return false;
		
		$hexCode = $this->fromBase36ToHex($code);
		$mobile_code = $this->findMobileCode($mobile,$external_id);
		
		if ($time_now == false) $time_now = time();
		
		$time = $time_now >> VC_TIME_DISCARD_RIGHT;
		$time_random = $time % (1 << VC_SIZE_TIME_RANDOM);
		$time_fixed = ($time >> VC_SIZE_TIME_RANDOM) % (1 << VC_SIZE_TIME_FIXED);
		
		$time_random_hex = $this->findRandomTimeCode($time_random);
		$time_random_hex_decr = $this->findRandomTimeCode($time_random - 1); # IF the voucher was issued before the 17 min cycle
		
		$code1 = $this->findOrgCode($org, $purpose, $time_fixed);
		$code4 = $this->findAdditionalBitsCode($additional_bits, $store_id);
		
		$code_3 = $this->strXor(array($hexCode, $mobile_code, $code1, $code4));
		
		
		
		$this->logger->debug(<<<EOSTR
Time Random           : $time_random
Mobile code           : $mobile_code
OrgCode               : $code1
Additional Bits Code  : $code4
RandomTime(From code) : $code_3
RandomTime(Calculated): $time_random_hex   
RandomTime(Calc-Decr) : $time_random_hex_decr

EOSTR
);
		
//		echo "code_3 = $code_3, Time Random Hex $time_random_hex";
		
		if (($time_random_hex == $code_3) || ($time_random_hex_decr == $code_3))
			return true;
			
		return false;
	}
}
?>
