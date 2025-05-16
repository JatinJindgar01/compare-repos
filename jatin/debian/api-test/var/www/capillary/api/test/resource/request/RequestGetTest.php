<?php

require_once('test/resource/request/ApiRequestResourceTestBase.php');

class RequestGetTest extends ApiRequestResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function testReqGet_1()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$ret = $this->requestResourceObj->process('v1', 'get', array(), array(), 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(isset($ret['requests']['goodwill']));
    	$this->assertTrue(isset($ret['requests']['change_identifier']));
    	$this->assertEquals(9000,$ret['requests']['item_status']['code']);
    }
    
    public function testReqGet_2()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$query=array("type"=>"change_identifier");
    	
    	$ret = $this->requestResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(empty($ret['requests']['goodwill']));
    	$this->assertTrue(isset($ret['requests']['change_identifier']));
    	$this->assertEquals(9000,$ret['requests']['item_status']['code']);
    }
    
    public function testReqGet_3()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$query=array("type"=>"goodwill");
    
    	$ret = $this->requestResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(isset($ret['requests']['goodwill']));
    	$this->assertTrue(empty($ret['requests']['change_identifier']));
    	$this->assertEquals(9000,$ret['requests']['item_status']['code']);
    }
    
    public function testReqGet_4()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$query=array("type"=>"ssfdgsdgsdg");
    
    	$ret = $this->requestResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(empty($ret['requests']['goodwill']));
    	$this->assertTrue(empty($ret['requests']['change_identifier']));
    	$this->assertEquals(9000,$ret['requests']['item_status']['code']);
    }
    
    public function testReqGet_5()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$query=array("base_type"=>"ssfdgsdgsdg");
    
    	$ret = $this->requestResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(empty($ret['requests']['goodwill']));
    	$this->assertTrue(empty($ret['requests']['change_identifier']));
    	$this->assertEquals(9000,$ret['requests']['item_status']['code']);
    }
    
    public function testReqGet_6()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$query=array("base_type"=>"merge");
    
    	$ret = $this->requestResourceObj->process('v1', 'get', array(), $query, 'GET');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertTrue(empty($ret['requests']['goodwill']));
    	$this->assertTrue(isset($ret['requests']['change_identifier']));
    	if(!empty($ret['requests']['change_identifier']))
    		$this->assertTrue(isset($ret['requests']['change_identifier'][0]['target_customer']));
    	$this->assertEquals(9000,$ret['requests']['item_status']['code']);
    }
    
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
	}
	
}
