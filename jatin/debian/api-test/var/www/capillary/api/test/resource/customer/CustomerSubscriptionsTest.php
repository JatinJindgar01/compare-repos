<?php
 
require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerSubscriptionsTest extends ApiCustomerResourceTestBase
{
	private $preservedOrgConfigsData = array();
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setUp()
	{
		$this->login("till.005", "123");
		parent::setUp();
	}
	
	public function tearDown(){	}
	
	public function testCustomerSubscriptionsGetInvalidCustomer(){
		
		$rand_number = rand( 10000, 99999 );
		$moible = "9177777".$rand_number;
		$query_params = array( 
				"user_id" => "true",
				"mobile" => $moible 
				);
		
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		$customer_subscription_response = $this->customerResourceObj->process(
				"v1.1", "subscriptions", null, $query_params, "GET" );
		$item_status = $customer_subscription_response['subscriptions']['subscription'][0]['item_status'];
		$this->assertEquals( 500, $customer_subscription_response['status']['code'] );
		$this->assertEquals( 1011, $item_status['code'] );
	}
	
	public function testCustomerSubscriptionsAddInvalidCustomer() {
		
		//Adding a new customer 
		$rand_number = rand( 10000, 99999 );
		$mobile = "9177777".$rand_number;
		$subscriptions_data = array( 
				"root" => array(
						"subscription" => array(
								"mobile" => $mobile,
								"priority" => "BULK",
								"scope" => "ALL",
								"channel" => "EMAIL",
								"is_subscribed" => 0 
								)
						) 
				); 
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		
		$customer_subscriptions_add_response = $this->customerResourceObj->process(
				"v1.1", "subscriptions", $subscriptions_data, null, "POST" );
		$item_status = $customer_subscriptions_add_response['subscriptions']['subscription'][0]['item_status'];
		$this->assertEquals( 500, $customer_subscriptions_add_response['status']['code'] );
		$this->assertEquals( 1101, $item_status['code'] );
	}
	
	public function testCustomerSubscriptionsGetValidCustomer(){
		
		//Adding a new customer
		$rand_number = rand( 10000, 99999 );
		$mobile = "9190369".$rand_number;
		$customer = array(
				"email" => $rand_number."69customer@capillarytech.com",
				"mobile" => "9190369".$rand_number,
				"external_id" => "EXT_69".$rand_number,
				"firstname" => "Customer"
		);
		
		$query_params = array( "user_id" => "true" );
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		
		$customer_add_response = $this->addCustomerTest( $customer, $query_params );
		
		//Adding subscriptions to the customer
		$subscriptions_data = array( 
				"root" => array(
						"subscription" => array(
								array(
									"mobile" => $mobile,
									"priority" => "BULK",
									"scope" => "ALL",
									"channel" => "sms",
									"is_subscribed" => 0
									)
								 
								)
						) 
				); 
		$response = $this->customerResourceObj->process( 
				$gbl_api_version, 'subscriptions', $subscriptions_data, null, 'POST' );
		$this->assertEquals( 200, $response['status']['code'] );
		$this->assertEquals( 1000, $response['subscriptions']['subscription'][0]['item_status']['code'] );
	}
}