<?php

require_once("test/resource/communications/ApiCommunicationsResourceTestBase.php");

class SendSmsTest extends ApiCommunicationsResourceTestBase{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setUp(){
		$this->login("till.005", "123");
		
		parent::setUp();
		
		$rand_number = rand(10000, 99999);
        
        $customer1 = array(
                        "email" => $rand_number."92customer@capillarytech.com",
                        "mobile" => "92677".$rand_number,
                        "external_id" => "EXT_92".$rand_number,
                        "firstname" => "TestCustomer1"
        );
		$customer2 = array(
                        "email" => $rand_number."92customer@capillarytech.com",
                        "mobile" => "92677".$rand_number,
                        "external_id" => "EXT_92".$rand_number,
                        "firstname" => "TestCustomer1"
        );
		
		$this->customer_add_response1 = $this->addCustomerTest($customer1, $query_params);
		$this->customer_add_response2 = $this->addCustomerTest($customer1, $query_params);
		
		$this->customer_add_response1 = $this->customer_add_response1['customers']['customer'][0];
		$this->customer_add_response2 = $this->customer_add_response2['customers']['customer'][0];
	}
	
	public function testSendSms_fail(){
		global $logger, $currentuser, $currentorg, $cfg;
		$response = $this->communicationsResourceObj->process('v1.1', 'sms', null, null, 'POST');
		$this->assertEquals(500, $response['code']);
	}
		
	public function testSendSms_sendSingle(){
		global $logger, $currentuser, $currentorg, $cfg;
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/sendSms', true);
		$data = array("root" => array("sms" => array(0 => array("to"=>$this->customer_add_response1["mobile"],"body"=>"Testing sms","scheduled_time"=>"","sender"=>""))));
		$response = $this->communicationsResourceObj->process('v1.1','sms', $data ,$query_params, 'POST');
		$this->assertEquals(200, $response['status']['code']);
		$this->assertEquals(4100, $response['sms'][0]['item_status']['code']);
	}
	
	public function testSendSms_sendMultiple(){
		global $logger, $currentuser, $currentorg, $cfg;
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/sendSms', true);
		$data = array("root" => array("sms" => array(0 => array("to"=>$this->customer_add_response1["mobile"],"body"=>"Testing sms","scheduled_time"=>"","sender"=>""),
									1 => array("to"=>$this->customer_add_response2["mobile"],"body"=>"Testing sms 3","scheduled_time"=>"","sender"=>""))));
		$response = $this->communicationsResourceObj->process('v1.1','sms', $data ,$query_params, 'POST');
		$this->assertEquals(200, $response['status']['code']);
		$this->assertEquals(4100, $response['sms'][0]['item_status']['code']);
		$this->assertEquals(4100, $response['sms'][1]['item_status']['code']);
	}
	
	public function tearDown(){
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/sendSms', false);
	}
}
