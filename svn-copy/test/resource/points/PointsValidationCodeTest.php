<?
require_once('test/resource/points/ApiPointsResourceTestBase.php');

class PointsValidationCodeTest extends ApiPointsResourceTestBase
{
	private $oldPointsValidationConfigs = array();
	private $oldGlobals = array();
	private $currentConfigs = array();
	
	public function __construct(){
		
		parent::__construct();
	}

	/**
	 * Test case for invalid identifiers - mobile
	 */
	public function testPointsValidationCode_1(){
		
		//Setting globals and context required for the test case 
		global $cfg, $request_headers, $gbl_country_code;
		$gbl_country_code = false;
		$request_headers = array( X_CAP_CLIENT_COUNTRYCODE => 0 ); 
		$this->oldGlobals = $cfg['capillary_client_user_agents'];
		$cfg['capillary_client_user_agents'] = array( 'ApiUnitTestClient' );
		
		//Calling points validation code
		$rand_invalid_mobile_number = rand(10000, 99999);
		$points = rand( 0, 1000 );
		$query_params = array( 
				'mobile' => $rand_invalid_mobile_number,
				'points' => $points,
				'user_id' => true );
		$this->logger->debug("Going to fetch Validation Code");
		$response = $this->pointsResourceObj->process( 'v1.1', 'validationcode', null, $query_params, 'GET' );
		$this->assertEquals( 500, $response['status']['code'] );
		$this->assertEquals( 802, $response['validation_code']['code']['item_status']['code'] );
		$this->assertEquals( $rand_invalid_mobile_number, $response['validation_code']['code']['mobile'] );
		$this->assertEquals( -1, $response['validation_code']['code']['user_id'] );
		$this->assertEmpty( $response['validation_code']['code']['email'] );
		$this->assertEmpty( $response['validation_code']['code']['external_id'] );
	}
	
	/**
	 * Test case for customer not found in org by mobile
	 */
	public function testPointsValidationCode_2(){
		
		//Setting globals and context required for the test case
		global $cfg, $request_headers, $gbl_country_code, $gbl_api_version;
		$gbl_country_code = true;
		$gbl_api_version = 'v1.1';
		$request_headers = array( X_CAP_CLIENT_COUNTRYCODE => 1 );
		$this->oldGlobals = $cfg['capillary_client_user_agents'];
		$cfg['capillary_client_user_agents'] = array( 'ApiUnitTestClient_no_match' );
		
		//Calling points validation code
		$rand_invalid_mobile_number = rand(100000, 999999);
		$points = rand( 0, 1000 );
		$query_params = array(
				'mobile' => $rand_invalid_mobile_number,
				'points' => $points,
				'user_id' => true );
		$this->logger->debug("Going to fetch Validation Code");
		$response = $this->pointsResourceObj->process( 'v1.1', 'validationcode', null, $query_params, 'GET' );
		$this->assertEquals( 500, $response['status']['code'] );
		$this->assertEquals( 816, $response['validation_code']['code']['item_status']['code'] );
		$this->assertEquals( $rand_invalid_mobile_number, $response['validation_code']['code']['mobile'] );
		$this->assertEquals( -1, $response['validation_code']['code']['user_id'] );
		$this->assertEmpty( $response['validation_code']['code']['email'] );
		$this->assertEmpty( $response['validation_code']['code']['external_id'] );
	}
	
	/**
	 * Test Case for customer not found by email
	 */
	public function testPointsValidationCode_3(){
	
		//Setting globals and context required for the test case
		global $cfg, $request_headers, $gbl_country_code, $gbl_api_version;
		$gbl_country_code = false;
		$gbl_api_version = 'v1.1';
		$request_headers = array( X_CAP_CLIENT_COUNTRYCODE => 0 );
		$this->oldGlobals = $cfg['capillary_client_user_agents'];
		$cfg['capillary_client_user_agents'] = array( 'ApiUnitTestClient' );
	
		//Calling points validation code
		$points = rand( 0, 1000 );
		$email = "unit_test_customer".rand(0,1000)."@capillarytech.com";
		$query_params = array(
				'email' => $email,
				'points' => $points,
				'user_id' => true );
		$this->logger->debug("Going to fetch Validation Code");
		$response = $this->pointsResourceObj->process( 'v1.1', 'validationcode', null, $query_params, 'GET' );
		$this->assertEquals( 500, $response['status']['code'] );
		$this->assertEquals( 816, $response['validation_code']['code']['item_status']['code'] );
		$this->assertEquals( $email, $response['validation_code']['code']['email'] );
		$this->assertEquals( -1, $response['validation_code']['code']['user_id'] );
		$this->assertEmpty( $response['validation_code']['code']['mobile'] );
		$this->assertEmpty( $response['validation_code']['code']['external_id'] );
	}
	
	/**
	 * Test case for validation code generation ( with CONF_OTP_TIME_HASHED_CODE set to 1 )
	 * Success check is currently only with the response returned
	 */
	public function testPointsValidationCode_4(){
	
		//Setting globals and context required for the test case
		global $cfg, $request_headers, $gbl_country_code;
		$gbl_country_code = false;
		$request_headers = array( X_CAP_CLIENT_COUNTRYCODE => 0 );
		$cfg['capillary_client_user_agents'] = array( 'ApiUnitTestClient' );
	
		//registering customer 
		$rand_number = rand(10000, 99999);
		$email = $rand_number."customer@capillarytech.com";
		$mobile = "9190369".$rand_number;
		$external_id = "EXT_".$rand_number;
		$customer = array(
				"email" => $email,
				"mobile" => $mobile,
				"external_id" => $external_id,
				"firstname" => "Customer"
		);
		
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
			
		$this->setNewConfigs( 
				array(
						'CONF_OTP_TIME_HASHED_CODE' => 1,
						)
				 );
		
		//issuing validaiton code
		$points = rand( 10, 1000 );
		$query_params = array(
				'mobile' => $mobile,
				'points' => $points,
				'user_id' => true );
		$response = $this->pointsResourceObj->process( 'v1.1', 'validationcode', null, $query_params, 'GET' );
		
		$this->assertEquals( 200, $response['status']['code'] );
		$this->assertEquals( 200, $response['validation_code']['code']['item_status']['code'] );
		$this->assertEquals( $mobile, $response['validation_code']['code']['mobile'] );
		$this->assertEquals( $email, $response['validation_code']['code']['email'] );
		$this->assertEquals( $external_id, $response['validation_code']['code']['external_id'] );
		
	}
	
	/**
	 * Test case for validation code generation ( with CONF_OTP_TIME_HASHED_CODE set to 0 )
	 * Success check is currently only with the response returned
	 */
	public function testPointsValidationCode_5(){
	
		//Setting globals and context required for the test case
		global $cfg, $request_headers, $gbl_country_code;
		$gbl_country_code = false;
		$request_headers = array( X_CAP_CLIENT_COUNTRYCODE => 0 );
		$cfg['capillary_client_user_agents'] = array( 'ApiUnitTestClient' );
	
		//registering customer
		$rand_number = rand(10000, 99999);
		$email = $rand_number."customer@capillarytech.com";
		$mobile = "9190369".$rand_number;
		$external_id = "EXT_".$rand_number;
		$customer = array(
				"email" => $email,
				"mobile" => $mobile,
				"external_id" => $external_id,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
			
		$this->setNewConfigs(
				array(
						'CONF_OTP_TIME_HASHED_CODE' => 0,
				)
		);
	
		//issuing validaiton code
		$points = rand( 10, 1000 );
		$query_params = array(
				'mobile' => $mobile,
				'points' => $points,
				'user_id' => true );
		$response = $this->pointsResourceObj->process( 'v1.1', 'validationcode', null, $query_params, 'GET' );
	
		$this->assertEquals( 200, $response['status']['code'] );
		$this->assertEquals( 200, $response['validation_code']['code']['item_status']['code'] );
		$this->assertEquals( $mobile, $response['validation_code']['code']['mobile'] );
		$this->assertEquals( $email, $response['validation_code']['code']['email'] );
		$this->assertEquals( $external_id, $response['validation_code']['code']['external_id'] );
	
	}
	
	/* 
	 * Test for generated validation code length, whether it matches the configuraitons set
	 */ 
	public function testPointsValidationCode_6(){
	
		//registering customer
		$rand_number = rand(10000, 99999);
		$email = $rand_number."customer@capillarytech.com";
		$mobile = "9190369".$rand_number;
		$external_id = "EXT_".$rand_number;
		$customer = array(
				"email" => $email,
				"mobile" => $mobile,
				"external_id" => $external_id,
				"firstname" => "Customer"
		);
	
		$query_params = array( "user_id" => "true" );
		$customer_add_response = $this->addCustomerTest( $customer, $query_params );
			
		$length = rand( 0, 50 );
		$this->setNewConfigs(
				array(
						'CONF_OTP_TIME_HASHED_CODE' => 0,
						'OTP_CODE_LENGTH' => $length
				)
		);
	
		//issuing validaiton code
		$points = rand( 10, 1000 );
		$query_params = array(
				'mobile' => $mobile,
				'points' => $points,
				'user_id' => true );
		$response = $this->pointsResourceObj->process( 'v1.1', 'validationcode', null, $query_params, 'GET' );
	
		$this->assertEquals( 200, $response['status']['code'] );
		$this->assertEquals( 200, $response['validation_code']['code']['item_status']['code'] );
		$this->assertEquals( $mobile, $response['validation_code']['code']['mobile'] );
		$this->assertEquals( $email, $response['validation_code']['code']['email'] );
		$this->assertEquals( $external_id, $response['validation_code']['code']['external_id'] );
	
		//Asserting for the length of the validation code generated
		$otp_manager = new OTPManager();
		$user = UserProfile::getByMobile( $mobile );
		$validation_code = $otp_manager->load( $user->user_id, 'POINTS', true, $points );
		$code_length = strlen( $validation_code['code'] );
		
		$this->assertEquals( $length, $code_length );
	}
	
 	/**
	 * Test for generated validation code type, whether it matches to the configuraitons set 
	 */
	public function testPointsValidationCode_7(){
	
		//registering customer
		$rand_number = rand(10000, 99999);
		$email = $rand_number."customer@capillarytech.com";
		$mobile = "9190369".$rand_number;
		$external_id = "EXT_".$rand_number;
		$customer = array(
				"email" => $email,
				"mobile" => $mobile,
				"external_id" => $external_id,
				"firstname" => "Customer"
		);
	
		$query_params = array( "user_id" => "true" );
		$customer_add_response = $this->addCustomerTest( $customer, $query_params );
			
		$length = rand( 0, 50 );
		$this->setNewConfigs(
				array(
						'CONF_OTP_TIME_HASHED_CODE' => 0,
						'OTP_CODE_LENGTH' => $length,
						'OTP_CODE_TYPE' => 'NUMERIC'
				)
		);
	
		//issuing validaiton code
		$points = rand( 10, 1000 );
		$query_params = array(
				'mobile' => $mobile,
				'points' => $points,
				'user_id' => true );
		$response = $this->pointsResourceObj->process( 'v1.1', 'validationcode', null, $query_params, 'GET' );
	
		$this->assertEquals( 200, $response['status']['code'] );
		$this->assertEquals( 200, $response['validation_code']['code']['item_status']['code'] );
		$this->assertEquals( $mobile, $response['validation_code']['code']['mobile'] );
		$this->assertEquals( $email, $response['validation_code']['code']['email'] );
		$this->assertEquals( $external_id, $response['validation_code']['code']['external_id'] );
	
		//Asserting for the length of the validation code generated
		$otp_manager = new OTPManager();
		$user = UserProfile::getByMobile( $mobile );
		$validation_code = $otp_manager->load( $user->user_id, 'POINTS', true, $points );
		$code_length = strlen( $validation_code['code'] );
	
		$this->assertEquals( $length, $code_length );
		$this->assertEquals( true, is_numeric( $validation_code['code']) );
	}
	
	public function setNewConfigs( $newConfigs ){
		
		$cm = new ConfigManager();
		foreach( $newConfigs AS $key => $value ){
			
			$current_value = $cm->getKeyValueForOrg( $key, $this->currentorg->org_id );
			$this->currentConfigs[$key] = $current_value;
			$key_value = array();
			$key_value['scope'] = 'ORG';
			$key_value['entity_id'] = -1;
			$key_value['value'] = $value;
				
			$cm->setKeyValue( $key, $key_value );
			
		}
	}
	
	public function setUp(){
		
		$this->login( "till.005", "123" );
		parent::setUp();
	}
	
	public function tearDown(){
		
		$cm = new ConfigManager();
		foreach( $this->currentConfigs AS $name => $value ){
			
			$key_value = array();
			$key_value['scope'] = 'ORG';
			$key_value['entity_id'] = -1;
			$key_value['value'] = $value;
				
			$cm->setKeyValue( $name, $key_value );
		}
	}
}
?>
