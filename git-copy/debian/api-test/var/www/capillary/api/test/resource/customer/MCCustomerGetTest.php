<?php



require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class MCCustomerGetTest extends ApiCustomerResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function testMCCGet_1()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    	);
    
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(isset($ret['customers']['customer'][0]['registered_store']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_2()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    	);
    	 
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(isset($ret['customers']['customer'][0]['registered_store']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    
    public function testMCCGet_3()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    	);
    	 
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(!isset($ret['customers']['customer'][0]['registered_till']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_4()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    	);
    	 
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(isset($ret['customers']['customer'][0]['registered_till']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    
    public function testMCCGet_5()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    	);
    	 
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(!isset($ret['customers']['customer'][0]['fraud_details']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_6()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(isset($ret['customers']['customer'][0]['fraud_details']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_7()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'ndnc_status' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(isset($ret['customers']['customer'][0]['ndnc_status']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_8()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'ndnc_status' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(!isset($ret['customers']['customer'][0]['ndnc_status']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }

    
    public function testMCCGet_9()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'optin_status' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(isset($ret['customers']['customer'][0]['optin_status']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_10()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'optin_status' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(!isset($ret['customers']['customer'][0]['optin_status']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    
    public function testMCCGet_11()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'expiry_schedule' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(isset($ret['customers']['customer'][0]['expiry_schedule']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_12()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'expiry_schedule' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(!isset($ret['customers']['customer'][0]['expiry_schedule']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    
    public function testMCCGet_13()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'slab_history' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(isset($ret['customers']['customer'][0]['slab_history']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_14()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'slab_history' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(!isset($ret['customers']['customer'][0]['slab_history']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_15()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'points_summary' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(isset($ret['customers']['customer'][0]['points_summary']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_16()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'points_summary' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(!isset($ret['customers']['customer'][0]['points_summary']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }

    
    public function testMCCGet_17()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'expired_points' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(isset($ret['customers']['customer'][0]['expired_points']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
    public function testMCCGet_18()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile='919740798372';
    	$query = array(
    			"mobile" => $mobile,
    			'expired_points' => 'true'
    	);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertFalse(!isset($ret['customers']['customer'][0]['expired_points']));
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }
    
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
	}
}
