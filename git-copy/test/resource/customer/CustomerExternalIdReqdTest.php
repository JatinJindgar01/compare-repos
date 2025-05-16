<?php
 
require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerExternalIdReqdTest extends ApiCustomerResourceTestBase
{
	private $preservedOrgConfigsData = array();
	private $cm;
	
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setUp()
	{
		global $currentorg ;
		$this->currentorg = $currentorg;
		$this->login("till.005", "123");
		$this->cm = new ConfigManager($this->currentorg->org_id);
		parent::setUp();
		$peContext = new \Api\UnitTest\Context('pointsengine');
		$peContext->set("response/constant", true);
		
		$newOrgConfigData = array(
				"CONF_USERS_IS_EMAIL_REQUIRED" => 1,
				"CONF_USERS_IS_EXTERNAL_ID_REQUIRED" => 1,
				"CONF_USERS_IS_MOBILE_OR_EXTERNAL_ID_REQUIRED" => 1,
				"CONF_USERS_IS_MOBILE_REQUIRED" => 1,
				"CONF_REGISTRATION_PRIMARY_KEY" => "MOBILE",
				"CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH" => 50,
				"CONF_LOYALTY_ALLOW_EXTERNAL_ID_UPDATE" => 1,
				"CONF_ALLOW_EMAIL_UPDATE" => 0,
				"CONF_ALLOW_MOBILE_UPDATE" => 0,
				"CONF_LOYALTY_ALLOW_EXTERNAL_ID_UPDATE" => 0,
		);
		
		foreach($newOrgConfigData AS $key => $value)
		{
			$current_value = $this->cm->getKeyValueForOrg($key, $this->currentorg->org_id);
			//$current_value = $cf->getConfigKey($key);
			if(!isset($this->preservedOrgConfigsData[$key]))
				$this->preservedOrgConfigsData[$key] = $current_value;
		}
	}
	
	public function tearDown()
	{
		global $currentorg;
		$currentorg = $this->currentorg;
		
		//reseting
		foreach($this->preservedOrgConfigsData AS $name => $value)
		{
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=$this->currentorg->org_id;
			$key_value['value']=$value;
	
			//$this->cm->setKeyValue($name, $key_value);
		}
	}
	
	public function testNewUser()
	{
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		
		$this->setConfigKeyValue("CONF_USERS_IS_EMAIL_REQUIRED", 0);
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 1);
		$this->setConfigKeyValue("CONF_USERS_IS_MOBILE_REQUIRED", 0);
		$this->setConfigKeyValue("CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH", 50);
		//$this->setConfigKeyValue("CONF_REGISTRATION_PRIMARY_KEY", REGISTRATION_IDENTIFIER_);
		
		
		//adding customer --- start
		$rand_number = substr(round(microtime(true)), 2); // returns 8 digits like 65910002
		$extId = "EXT_".$rand_number;
		$customer = array(
				"email" => $rand_number."ut@capillarytech.com",
				"mobile" => "9188".$rand_number,
				"external_id" => $extId,
				"firstname" => "Customer"
		);
		// external id not passed, bt required for new user
		$customer["external_id"] = null;
 		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1043);
		
		
		// create user with external without setting extid config
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 0);
		$customer["external_id"] = $extId;
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		
		
		// add with config on
		$customer = array(
				"external_id" => $extId."reqd_on",
				"firstname" => "Customer"
		);
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 1);
		// external id not passed, bt required for new user
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		
	}

	public function testUpdatingExistingCustomer()
	{
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		
		$this->setConfigKeyValue("CONF_USERS_IS_EMAIL_REQUIRED", 0);
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 1);
		$this->setConfigKeyValue("CONF_USERS_IS_MOBILE_REQUIRED", 0);
		$this->setConfigKeyValue("CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH", 50);
		
		//$this->setConfigKeyValue("CONF_REGISTRATION_PRIMARY_KEY", REGISTRATION_IDENTIFIER_);
		
		
		//adding customer --- start
		$rand_number = substr(round(microtime(true)), 2); // returns 8 digits like 65910002
		$extId = "EXT_".$rand_number;
		$customer = array(
				"email" => $rand_number."ut@capillarytech.com",
				"mobile" => "9188".$rand_number,
				"external_id" => $extId,
				"firstname" => "Customer"
		);
		
		// create user with external without setting extid config
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 0);
		$customer["external_id"] = null;
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$userId1 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		
		// update bt no ext id even now
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 1);
		$customer["external_id"] = null;
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		$userId2 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($userId1, $userId2);

		// update bt no ext id even now
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 1);
		$customer["external_id"] = $extId;
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		$userId3 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($userId1, $userId3);
		
	}
	
	public function testExternalIdDuplication()
	{

		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		
		$this->setConfigKeyValue("CONF_USERS_IS_EMAIL_REQUIRED", 0);
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 1);
		$this->setConfigKeyValue("CONF_USERS_IS_MOBILE_REQUIRED", 0);
		$this->setConfigKeyValue("CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH", 50);
		$this->setConfigKeyValue("CONF_LOYALTY_ALLOW_EXTERNAL_ID_UPDATE", 1);
		$this->setConfigKeyValue("CONF_REGISTRATION_PRIMARY_KEY", REGISTRATION_IDENTIFIER_EXTERNAL_ID);
		
		
		//adding customer --- start
		$rand_number = substr(round(microtime(true)), 2); // returns 8 digits like 65910002
		$extId = "EXT_".$rand_number;
		$customer = array(
				"email" => $rand_number."ut@capillarytech.com",
				"external_id" => $extId,
				"firstname" => "Customer"
		);
		
		// create user with external external id setting extid config
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$userIdByExtId = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		
		// update the email as primary key external id
		$customer["email"] = "2_".$customer["email"]; 		
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		$userId2 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($userIdByExtId, $userId2);
		
		// email could not be re-used
		$this->setConfigKeyValue("CONF_REGISTRATION_PRIMARY_KEY", REGISTRATION_IDENTIFIER_EXTERNAL_ID);
		$customer["email"] = str_replace("2_", "", $customer["email"]);
		$customer["external_id"] = "2_".$customer["external_id"];
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		$this->assertContains("External Id is Primary Key, External Id can not be updated", $customer_add_response["customers"]["customer"][0]["item_status"]["message"]);
		$userId2 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($userIdByExtId, $userId2);
		
		// update extrnal id with email
		$this->setConfigKeyValue("CONF_REGISTRATION_PRIMARY_KEY", REGISTRATION_IDENTIFIER_EMAIL);
		$customer["external_id"] = microtime(true);
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		$userId2 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($userIdByExtId, $userId2);

		// update external id with null
		$customer["external_id"] = null;
		$this->setConfigKeyValue("CONF_REGISTRATION_PRIMARY_KEY", REGISTRATION_IDENTIFIER_EMAIL);
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		$userId2 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($userIdByExtId, $userId2);
		$this->assertNotNull($customer_add_response["customers"]["customer"][0]["external_id"]);
	}
	
	//TODO: implement the camapign users test cases
	public function atestCampaignUsers()
	{
		
	}
	
	// external id not updatable throw
	public function testNonUpdatableUserIds()
	{
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
	
		$this->setConfigKeyValue("CONF_USERS_IS_EMAIL_REQUIRED", 0);
		$this->setConfigKeyValue("CONF_USERS_IS_EXTERNAL_ID_REQUIRED", 1);
		$this->setConfigKeyValue("CONF_USERS_IS_MOBILE_REQUIRED", 0);
		$this->setConfigKeyValue("CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH", 50);
		$this->setConfigKeyValue("CONF_REGISTRATION_PRIMARY_KEY", REGISTRATION_IDENTIFIER_EMAIL);
		$this->setConfigKeyValue("CONF_ALLOW_EMAIL_UPDATE", 0);
		$this->setConfigKeyValue("CONF_ALLOW_MOBILE_UPDATE", 0);
		$this->setConfigKeyValue("CONF_LOYALTY_ALLOW_EXTERNAL_ID_UPDATE", 0);
	
		//adding customer --- start
		$rand_number = substr(round(microtime(true)), 2); // returns 8 digits like 65910002
		$extId = "EXT_".$rand_number;
		$customer = array(
				"email" => $rand_number."ut@capillarytech.com",
				"mobile" => "9188".$rand_number,
				"external_id" => $extId,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$userId1 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
	

		$customer["external_id"] = $extId."_new";
		$customer["mobile"] += 1;
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$userId2 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($userId1, $userId2);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		$this->assertContains("Mobile can not be updated", $customer_add_response["customers"]["customer"][0]["item_status"]["message"]);
		$this->assertContains("External id can not be updated", $customer_add_response["customers"]["customer"][0]["item_status"]["message"]);
		
		$this->setConfigKeyValue("CONF_REGISTRATION_PRIMARY_KEY", REGISTRATION_IDENTIFIER_MOBILE);
		$customer["mobile"] -= 1;
		$customer["email"] = "new_".$customer["email"];
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params, false);
		$userId3 = $customer_add_response["customers"]["customer"][0]["user_id"];
		$this->assertEquals($userId1, $userId3);
		$this->assertEquals($customer_add_response["customers"]["customer"][0]["item_status"]["code"], 1000);
		$this->assertContains("External id can not be updated", $customer_add_response["customers"]["customer"][0]["item_status"]["message"]);
		$this->assertContains("Email can not be updated", $customer_add_response["customers"]["customer"][0]["item_status"]["message"]);
		
	}
	
	
	public function setConfigKeyValue($key, $value)
	{
		global $currentorg;
		$currentorg = $this->currentorg;
		
		$key_value=array();
		$key_value['scope']='ORG';
		$key_value['entity_id']=$this->currentorg->org_id;
		$key_value['value']=$value;
		$this->cm->setKeyValue($key, $key_value);
		
	}
	
}