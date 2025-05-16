<?php

require_once("test/resource/communications/ApiCommunicationsResourceTestBase.php");

class GetSmsTest extends ApiCommunicationsResourceTestBase{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setUp(){
		$this->login("till.005", "123");	
		parent::setUp();
	}
	
	public function  testGetSms(){
		global $logger, $currentuser, $currentorg, $cfg;
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/getMessagesByIdSms', true);
		$query_params = array("id"=>"1234");
		$response = $this->communicationsResourceObj->process('v1.1','sms', $data ,$query_params, 'GET');
		
		$this->assertEquals("1234", $response['sms'][0]['id']);
		$this->assertEquals("919401456789", $response['sms'][0]['to']);
		$this->assertEquals("DELIVERED", $response['sms'][0]['status']);
		$this->assertEquals("Hello 123", $response['sms'][0]['message']);
		$this->assertEquals(200,$response['status']['code']);
		$this->assertEquals(4100, $response['sms'][0]['item_status']['code']);
	}
	
	public function tearDown(){
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/getMessagesByIdSms', false);
	}
}
?>