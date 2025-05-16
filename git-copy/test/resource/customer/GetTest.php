<?php

require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class GetTest extends ApiCustomerResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function testGet_1()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile=rand(1234567890,9987654321);
    
    	$customer = array(
    			"mobile" => $mobile,
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', null, $customer, 'GET');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }

    public function testGet_2()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$email="ut".rand(1234567890,9987654321)."@ut.com";
    
    	$customer = array(
    			"email" => $email,
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', null, $customer, 'GET');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($email, $ret['customers']['customer'][0]['email']);
    }
    
    public function testGet_3()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$email="ut".rand(492444,424242525)."@ut.com";
    	$mobile=rand(4874849,29488584848);
    
    	$customer = array(
    			'email'=>$email,
    			'mobile'=>$mobile
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', null, $customer, 'GET');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testGet_4()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$external_id="ut".rand(4874849,29488584848);
    
    	$customer = array(
    			'external_id'=>$external_id
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', null, $customer, 'GET');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($external_id, $ret['customers']['customer'][0]['external_id']);
    }
    
    public function testGet_5()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$id=rand(487484955,29488584848);
    
    	$customer = array(
    			'id'=>$id
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', null, $customer, 'GET');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($id, $ret['customers']['customer'][0]['id']);
    }
    
    public function testGet_6()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$id=rand(487484955,29488584848);
    
    	$customer = array(
    			'mobile'=>"$id,9740798372"
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', null, $customer, 'GET');
    	$this->assertEquals(201, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($id, $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals("919740798372", $ret['customers']['customer'][1]['mobile']);
    }
    
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
	}
}
