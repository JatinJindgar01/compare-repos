<?php

require_once("test/resource/communications/ApiCommunicationsResourceTestBase.php");

class GetEmailTest extends ApiCommunicationsResourceTestBase{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setUp(){
		$this->login("till.005", "123");
		
		parent::setUp();
	}	
	
	public function  testGetEmail(){
		global $logger, $currentuser, $currentorg, $cfg;
		$query_params = array("id"=>"1234");
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/getMessagesByIdEmail', true);
		$response = $this->communicationsResourceObj->process('v1.1','email', $data ,$query_params, 'GET');
		$this->assertEquals("1234", $response['email'][0]['id']);
		$this->assertEquals("test@test.com", $response['email'][0]['to']);
		$this->assertEquals("testing@testing.com", $response['email'][0]['cc']);
		$this->assertEquals("Hello 123", $response['email'][0]['subject']);
		$this->assertEquals("some one", $response['email'][0]['description']);
		
		$this->assertEquals(200,$response['status']['code']);
		$this->assertEquals(4200, $response['email'][0]['item_status']['code']);
	}
	
	public function tearDown(){
		$context = new \Api\UnitTest\Context('nsadmin');
        $context->set('response/nsadmin/getMessagesByIdEmail', false); 
	}
}
?>