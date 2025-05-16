<?php

require_once("test/resource/communications/ApiCommunicationsResourceTestBase.php");

class SaveTemplatesTest extends ApiCommunicationsResourceTestBase{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setUp(){
		$this->login("till.005", "123");
		
		parent::setUp();
	}
	
	public function testSaveTemplate(){
		global $logger, $currentuser, $currentorg, $cfg;
		$data = array("root"=>array("template"=>array(0=>array("id"=>"-1", "type"=>"EMAIL","title"=>"Issue Voucher Template","subject"=>"Voucher Issual","body"=>"Hi! Mr.firstname {{firstname}} here is ur voucher code {{voucher_code}}", "is_editable"=>"TRUE"))));
		$response = $this->communicationsResourceObj->process('v1.1','template', $data ,$query_params, 'POST');
		$this->assertEquals(200, $response['status']['code']);
		$this->assertEquals(4100, $response['communications']['templates']['template'][0]['item_status']['code']);
		$this->assertEquals("EMAIL", $response['communications']['templates']['template'][0]['type']);
		$this->assertEquals("Issue Voucher Template", $response['communications']['templates']['template'][0]['title']);
		$this->assertEquals("Voucher Issual", $response['communications']['templates']['template'][0]['subject']);
		$this->assertEquals("TRUE", $response['communications']['templates']['template'][0]['is_editable']);
		$this->assertEquals("Hi! Mr.firstname {{firstname}} here is ur voucher code {{voucher_code}}", $response['communications']['templates']['template'][0]['body']['@cdata']);
	}

	public function testSaveTemplate_fail(){
		global $logger, $currentuser, $currentorg, $cfg;
		$data = array("root"=>array("template"=>array(0=>array("id"=>"-1", "type"=>"ESMSE","title"=>"Issue Voucher Template","subject"=>"Voucher Issual","body"=>"Hi! Mr.firstname {{firstname}} here is ur voucher code {{voucher_code}}", "is_editable"=>"TRUE"))));
		$response = $this->communicationsResourceObj->process('v1.1','template', $data ,$query_params, 'POST');
		$this->assertEquals(500, $response['status']['code']);
		$this->assertEquals(4203, $response['communications']['templates']['template'][0]['item_status']['code']);
	}
}
