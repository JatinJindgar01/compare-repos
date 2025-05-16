<?php
/**
 * OTP Manager for handling issual and verification of OTP codes, 
 * based on OTP Configuration that is set for the Org 
 *  * @author manu
 *
 */
include_once 'apiHelper/ValidationCode.php';
class OTPManager{
	
	private $validation_code;
	private $config_manager;
	private $logger;
	private $currentorg;
	private $currentuser;
	private $database;
	private static $valid_scopes = array( 'COUPON', 'POINTS', 'GIFT_CARD' );
	
	public function __construct(){
		
		global $logger, $currentorg, $currentuser;
		
		$this->logger = $logger;
		$this->currentorg = $currentorg;
		$this->currentuser = $currentuser;
		$this->validation_code = new ValidationCode();
		$this->config_manager = new ConfigManager();
		$this->database = new Dbase( 'users' );
		$this->logger->debug( "OTP Manager created" );
	}
	
	/**
	 * Issues a validation code for a User with given scope and stores 
	 * in database with validity time, from the configuration set for the Org
	 * 
	 * @param integer $user_id
	 * @param string $scope
	 * @param string $reference
	 */
	public function issue( $user_id, $scope, $reference = "" ){
		
		$this->logger->debug( " OTP Manager: Validation code issue request with params: 
								user_id - $user_id,
								scope - $scope,
								reference- $reference" );
		if( $this->isValidScope( $scope ) ){
			try{
				
				$this->logger->debug( "Scope $scope is valid, continue with code generation" );
				$OTPcode = $this->generate( $user_id, $reference );
				$valid_upto = $this->config_manager->getKey( "OTP_CODE_VALIDITY" );
				$valid_upto_date = date("Y-m-d H:i:s", strtotime("+$valid_upto minutes"));
				$this->logger->debug( " Check: Code Validity : $valid_upto minutes - valid upto $valid_upto_date" );
				if( $this->insert( $user_id, $OTPcode, $valid_upto_date, $reference, $scope ) ){

					$this->logger->debug( " Insert: OTP record Successful" );
					return $OTPcode;
				}else{

					$this->logger->error( "OTP Manager: Error - OTP record insert failure" );
					return false;
				}
			}catch( Exception $e ){
				
				$this->logger->error( "OTP Manager: Exception - $e" );
				return false;
			}
		}
	}
	
	/**
	 * Verifies the validation code given against a User and Scope, 
	 * and invalidates the code if the request is for redemption
	 * 
	 * @param integer $user_id
	 * @param string $scope
	 * @param string $code
	 * @param string $reference
	 * @param boolean $invalidate
	 */
	public function verify( $user_id, $scope, $code, $reference = "", $invalidate = false ){

		$this->logger->debug( " OTP Manager: Verify validation code with params : 
								user_id - $user_id,
								scope - $scope,
								code - $code,
								reference - $reference,
								invalidate - $invalidate" );
		$user = UserProfile::getById( $user_id );
		$org = new OrgProfile( $this->currentorg->org_id );
		
		//Check whether to run in mock mode
		if( Util::shouldBypassValidationCodeVerification() )
		{
			$this->logger->debug("Check : Mock Mode for validation code is enabled,
					so skiping validation code verification");
			return true;
		}
		
		//Check whether to verify only recent code or all valide recent codes
		if( $this->config_manager->getKey( "OTP_CHECK_ONLY_RECENT_CODE" ) ){

			//Check whether to include reference in verifying code
			if( ( $scope == 'POINTS' ) &&
					( $this->config_manager->getKey( 'CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION' ) ) ){

				$recent_code = $this->load( $user_id, $scope, true, $reference );
			}else{

				$recent_code = $this->load( $user_id, $scope, true );
			}
			$this->logger->debug( " Fetch: Found recent validation code generated for User -"
					.print_r( $recent_code, true ) );
			
			//Check if the loaded recent code is valid i.e not redeemed yet
			if( !$recent_code['is_valid'] ){
			
				$this->logger->debug( "Check: If recent generated OTP code is valid - FALSE" );
				return false;
			}
			$this->logger->debug( "Check : Verify if generated code ".$recent_code['code']." = supplied code $code" );
			if( strcasecmp( $recent_code['code'], $code ) == 0 ) {

				$this->logger->debug( "OTP Manager: Success - Validation code verified" );
				if( $invalidate ){
						
					$this->logger->debug( "OTP Manager: Invalidate request - ( $user_id|$code|$scope )" );
					$this->invalidate( $user_id, $code, $scope );
				}
				return true;
			}else{

				$this->logger->error( "OTP Manager: Failure - Validation code invalid" );
				return false;
			}
		}else{ //Recent code only check is false, continue with loading all unexpired codes

			//Check whether to include reference in verifying code
			if( ( $scope == 'POINTS' ) &&
					( $this->config_manager->getKey( 'CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION' ) ) ){

				$recentOTP_records = $this->load( $user_id, $scope, false, $reference );
			}else{

				$recentOTP_records = $this->load( $user_id, $scope, false );
			}
			$this->logger->debug( " Fetch: Found multiple recent validation codes generated for User -"
					.print_r( $recentOTP_records, true ) );
			$recent_codes = array();
			foreach ( $recentOTP_records as $record ){

				$this->logger->debug( "Check : Verify if generated code ".$record['code']." = supplied code $code" );
				if( strcasecmp( $code, $record['code'] ) == 0  ){

					$this->logger->debug( "OTP Manager: Success - Validation code verified" );
					if( $invalidate ){

						$this->logger->debug( "OTP Manager: Invalidate request - ( $user_id|$code|$scope )" );
						$this->invalidate( $user_id, $code, $scope );
					}
					return true;
				}
			}
			$this->logger->error( "OTP Manager: Failure - Validation code invalid" );
			return false;
		}
	}
	
	/**
	 * Generates a validation code for a given user_id, based on OTP configs
	 * that is set for the Org
	 * 
	 * @param integer $user_id
	 * @param integer $reference
	 * @throws Exception
	 */
	public function generate( $user_id, $reference ){
		
		$this->logger->debug( " OTP Manager: generate validation code " );
		
		//Check whether to use legacy bit shift logic for code generation or not
		if( !$this->config_manager->getKey( "CONF_OTP_TIME_HASHED_CODE" ) ){
			
			$this->logger->debug( " Check : Random code generation - TRUE " );
			$code_length = $this->config_manager->getKey( "OTP_CODE_LENGTH" );
			$code_type = $this->config_manager->getKey( "OTP_CODE_TYPE" );
			$this->logger->debug( " Code Length : $code_length " );
			if( !strcasecmp( $code_type, "ALPHANUMERIC" ) ){
				
				$this->logger->debug( " Check : Alphanumeric/Numeric code generation - ALPHANUMERIC " );
				$random_code = strtoupper( substr( 
						md5( rand( 0, 1000000 ).microtime() ).md5( rand( 0, 1000000 ).microtime() )
						, 0, $code_length ) );
			}elseif ( !strcasecmp( $code_type, "NUMERIC" ) ){
				
				$this->logger->debug( " Check : Alphanumeric/Numeric code generation - NUMERIC " );
				$temp_code = "";
				if( $code_length > 5 ){
					
					$min = pow( 10, 4 );
					$max = pow( 10, 5 ) - 1;
					for( $i = 0; $i < floor( $code_length / 5 ); $i++ ){
						
						$temp_code .= rand( $min, $max );
					}
					if( $code_length % 5 ){
						
						$rem = ( $code_length % 5 ) - 1;
						$min = pow( 10, $rem );
						$max = pow( 10, $rem + 1 ) - 1;
						$temp_code .= rand( $min, $max );
					}
					$random_code = $temp_code;
				}else{
					
					$min = pow( 10, $code_length - 1 );
					$max = pow( 10, $code_length ) - 1;
					$random_code = rand( $min, $max );
				}
			}
			if( !$random_code ){
				
				$this->logger->error( "OTP Manager : Error - Validation code generation failure" );
				throw new Exception( 'ERR_VALIDATION_CODE_FAIL' );
			}else{
				
				$this->logger->debug( "OTP Manager: Success - Validation code generation success" );
				$this->logger->debug( " Generated code ( Random|$code_type|Length-$code_length ) : $random_code" );
				return $random_code;
			}
		}else{ //Random code generation is fasle, continue with legacy bit shitft logic

			$this->logger->debug( " Check : Random code generation - FALSE " );
			$this->logger->debug( " OTP Manager : Using bit-shift generation logic " );
			$user = UserProfile::getById( $user_id );
			if( $this->config_manager->getKey( "CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION" ) ){
				
				$this->logger->debug( "Check : Include points in code generation - TRUE" );
				$additional_bits = $reference;
			}
			$code = $this->validation_code->issueValidationCode( 
					$currentorg, $user->mobile, $user->external_id, $user->email, 2, time(), 
					$this->currentuser->user_id, $additional_bits );
			if( !$code ){
			
				$this->logger->error( "OTP Manager: Error - Validation code generation failure" );
				throw new Exception( 'ERR_VALIDATION_CODE_FAIL' );
			}else{
				
				$this->logger->debug( "OTP Manager: Success - Validation code generation success" );
				$this->logger->debug( "Generated code ( Time Window based Non-Random|Fixed Length ) : $code" );
				return $code;
			}
		}
	}
	
	/**
	 * Invalidates code for the given user and scope, usually during redemption
	 * 
	 * @param integer $user_id
	 * @param string $code
	 * @param string $scope
	 */
	public function invalidate( $user_id, $code, $scope ){
		
		$sql = " UPDATE user_management.validation_code 
				 SET is_valid = FALSE
				 WHERE user_id = $user_id
					 AND code = '$code' 
				 	 AND scope = '$scope' ";
		
		return $this->database->update( $sql );
	}
	
	/**
	 * Loads either only recent or all valid OTP codes for 
	 * given user, scope and reference
	 * 
	 * @param integer $user_id
	 * @param string $scope
	 * @param boolean $recent
	 * @param string $reference
	 */
	public function load( $user_id, $scope, $recent = true, $reference = "" ){
		
		$reference_filter = empty( $reference ) ? "" : " AND reference = '$reference' ";
		if ( $recent ){
			
			$sql = " SELECT * 
				 	 FROM user_management.validation_code 
				 	 WHERE user_id = $user_id 
					 	AND scope = '$scope' 
					 	AND valid_upto > NOW()
					 	AND org_id = ".$this->currentorg->org_id."
					 	$reference_filter
					 ORDER BY id DESC ";
		
			return $this->database->query_firstrow( $sql );
		}else{
		
			$sql = "SELECT *
					FROM user_management.validation_code
					WHERE user_id = $user_id
						AND scope = '$scope'
						AND valid_upto > NOW()
						AND is_valid = TRUE
						AND org_id = ".$this->currentorg->org_id."
						$reference_filter
					ORDER BY id DESC ";
			
			return $this->database->query( $sql );
		}
	}
	
	/**
	 * Load multiple codes for given user and scope
	 * 
	 * @param integer $user_id
	 * @param string $scope
	 */
	public function loadMultiple( $user_id, $scope ){
	
		$sql = " SELECT *
				 FROM user_management.validation_code
				 WHERE user_id = $user_id
					AND scope = $scope
					AND valid_upto > NOW()
					AND is_valid = TRUE
					AND org_id = ".$this->currentorg->org_id."
				ORDER BY id DESC";
	
		return $this->database->query( $sql );
	}
	
	/**
	 * Inserts a OTP record to the table
	 * 
	 * @param integer $user_id
	 * @param string $code
	 * @param string $valid_upto
	 * @param string $reference
	 * @param string $scope
	 */
	public function insert( $user_id, $code, $valid_upto, $reference, $scope ){
		
		$sql = "
				INSERT INTO  user_management.validation_code
				(
						user_id ,
						org_id ,
						code ,
						valid_upto ,
						reference ,
						scope,
						is_valid ,
						added_by ,
						added_on 
				)
				VALUES
				(
						$user_id,
						".$this->currentorg->org_id.",
						'$code',
						'$valid_upto',
						'$reference',
						'$scope',
						1,
						".$this->currentuser->user_id.",
						NOW()
				) 
				ON DUPLICATE KEY UPDATE 
					valid_upto = '$valid_upto',
					reference = '$reference',
					is_valid = 1";
		
		return $this->database->insert( $sql );
	}
	
	/**
	 * Returns valid scopes supported
	 */
	public function getValidScopes(){
		
		$this->logger->debug( "Fetch: Valid Scopes - ".print_r( self::$valid_scopes, true ) );
		return self::$valid_scopes;
	}
	
	/**
	 * Checks if the give scope is valid
	 * @param string $scope
	 */
	public function isValidScope( $scope ){
		
		if( in_array( $scope, self::getValidScopes() ) ){
			
			$this->logger->debug( "Check: Is Scope $scope valid - TRUE" );
			return true;
		}
		return false;
	}
}