<?php

require_once("test/resource/communications/ApiCommunicationsResourceTestBase.php");

class SendEmailTest extends ApiCommunicationsResourceTestBase{
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
                        "mobile" => "9192677".$rand_number,
                        "external_id" => "EXT_92".$rand_number,
                        "firstname" => "TestCustomer1"
        );
		$customer2 = array(
                        "email" => $rand_number."93customer@capillarytech.com",
                        "mobile" => "9193677".$rand_number,
                        "external_id" => "EXT_93".$rand_number,
                        "firstname" => "TestCustomer2"
        );
		$customer3 = array(
                        "email" => $rand_number."93customer@capillarytech.com",
                        "mobile" => "9193677".$rand_number,
                        "external_id" => "EXT_93".$rand_number,
                        "firstname" => "TestCustomer3"
        );
		$customer4 = array(
                        "email" => $rand_number."92customer@capillarytech.com",
                        "mobile" => "9192677".$rand_number,
                        "external_id" => "EXT_92".$rand_number,
                        "firstname" => "TestCustomer4"
        );
        
        $query_params = array("user_id" => "true");
		
        $this->customer_add_response1 = $this->addCustomerTest($customer1, $query_params);
		$this->customer_add_response2 = $this->addCustomerTest($customer2, $query_params);
		$this->customer_add_response3 = $this->addCustomerTest($customer3, $query_params);
		$this->customer_add_response4 = $this->addCustomerTest($customer4, $query_params);
		
		$this->customer_add_response1 = $this->customer_add_response1['customers']['customer'][0];
		$this->customer_add_response2 = $this->customer_add_response2['customers']['customer'][0];
		$this->customer_add_response3 = $this->customer_add_response3['customers']['customer'][0];
		$this->customer_add_response4 = $this->customer_add_response4['customers']['customer'][0];
		}
	
	
	public function testSendEmail_fail(){
		global $logger, $currentuser, $currentorg, $cfg;
		$response = $this->communicationsResourceObj->process('v1.1', 'email', null, null, 'POST');
		$this->assertEquals(500, $response['code']);
	}
	
	public function testSendEmail_invalidEmailTo(){
		global $logger, $currentuser, $currentorg, $cfg;
		$data = array("root" => array("email"=>array(0=> array("to"=>"avsaca", "cc"=>"", "bcc"=>"","from"=>"","subject"=>"","body"=>"","attachment"=>array("file_name"=>"","file_type"=>"","file_data"=>""), "scheduled_time"=>""))));
		$response = $this->communicationsResourceObj->process('v1.1', 'email', $data, $query_params, 'POST');
		$this->assertEquals(500, $response['status']['code']);
		$this->assertEquals(4208, $response['email'][0]['item_status']['code']);
	}
	
	public function testSendEmail_invalidEmailCc(){
		global $logger, $currentuser, $currentorg, $cfg;
		$data = array("root" => array("email"=>array(0=> array("to"=>"customer@gmail.com", "cc"=>"asdasdad", "bcc"=>"","from"=>"","subject"=>"","body"=>"","attachment"=>array("file_name"=>"","file_type"=>"","file_data"=>""), "scheduled_time"=>""))));
		$response = $this->communicationsResourceObj->process('v1.1', 'email', $data, $query_params, 'POST');
		$this->assertEquals(500, $response['status']['code']);
		$this->assertEquals(4208, $response['email'][0]['item_status']['code']);
	}
	
	public function testSendEmail_invalidEmailBcc(){
		
		global $logger, $currentuser, $currentorg, $cfg;
		$data = array("root" => array("email"=>array(0=> array("to"=>$this->customer_add_response1["email"], "cc"=>$this->customer_add_response2["email"], "bcc"=>"12343","from"=>"","subject"=>"","body"=>"","attachment"=>array("file_name"=>"","file_type"=>"","file_data"=>""), "scheduled_time"=>""))));
		$response = $this->communicationsResourceObj->process('v1.1', 'email', $data, $query_params, 'POST');
		$this->assertEquals(500, $response['status']['code']);
		$this->assertEquals(4208, $response['email'][0]['item_status']['code']);
	}
	
	public function testSendEmail_sendTo(){
		global $logger, $currentuser, $currentorg, $cfg;
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/sendEmail', true);
		$data = array("root" => array("email"=>array(0=> array("to"=>$this->customer_add_response1["email"], "cc"=>"", "bcc"=>"","from"=>"","subject"=>"Test Subject","body"=>"This is the Body","attachment"=>array("file_name"=>"","file_type"=>"","file_data"=>""), "scheduled_time"=>""))));
		$response = $this->communicationsResourceObj->process('v1.1', 'email', $data, $query_params, 'POST');
		$this->assertEquals(200, $response['status']['code']);
		$this->assertEquals(4200, $response['email'][0]['item_status']['code']);
	}
	
	public function testSendEmail_sendCc(){
		global $logger, $currentuser, $currentorg, $cfg;
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/sendEmail', true);
		$data = array("root" => array("email"=>array(0=> array("to"=>$this->customer_add_response1["email"], "cc"=>$this->customer_add_response2["email"], "bcc"=>$this->customer_add_response3["email"],"from"=>"","subject"=>"Test Subject","body"=>"This is the Body","attachment"=>array("file_name"=>"","file_type"=>"","file_data"=>""), "scheduled_time"=>""))));
		$response = $this->communicationsResourceObj->process('v1.1', 'email', $data, $query_params, 'POST');
		$this->assertEquals(200, $response['status']['code']);
		$this->assertEquals(4200, $response['email'][0]['item_status']['code']);
	}
	
	public function testSendEmail_sendBcc(){
		global $logger, $currentuser, $currentorg, $cfg;
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/sendEmail', true);
		$data = array("root" => array("email"=>array(0=> array("to"=>$this->customer_add_response1["email"], "cc"=>$this->customer_add_response2["email"], "bcc"=>$this->customer_add_response3["email"],"from"=>"","subject"=>"Test Subject","body"=>"This is the Body","attachment"=>array("file_name"=>"","file_type"=>"","file_data"=>""), "scheduled_time"=>""),
		 							1=> array("to"=>$this->customer_add_response4["email"], "cc"=>array($this->customer_add_response1["email"], $this->customer_add_response3["email"]), "bcc"=>$this->customer_add_response2["email"],"from"=>"","subject"=>"Test Subject2","body"=>"This is the Body of the second","attachment"=>array("file_name"=>"","file_type"=>"","file_data"=>""), "scheduled_time"=>""))));
		$response = $this->communicationsResourceObj->process('v1.1', 'email', $data, $query_params, 'POST');
		$this->assertEquals(200, $response['status']['code']);
		$this->assertEquals(4200, $response['email'][0]['item_status']['code']);
		$this->assertEquals(4200, $response['email'][1]['item_status']['code']);
	}

	public function tearDown(){
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/sendEmail', false);
	}
	
}
?>