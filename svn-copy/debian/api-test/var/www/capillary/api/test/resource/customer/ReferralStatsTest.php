<?php

require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class ReferralStatsTest extends ApiCustomerResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }

	public function testGetStats_1()
	{
		global $logger, $cfg, $currentuser, $currentorg;
		
		$mobile=rand(1234567890,9987654321);
		$query = array(
				"mobile" => $mobile,
				'campaign_token' => "3958565"
				);
		
		
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(500, $ret['status']['code']);
		$this->assertEquals(1012, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['mobile']);
	}

	public function testGetStats_2()
	{
		$reContext = new \Api\UnitTest\Context('referral');
		$reContext->set("response/constant",true);
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'only_referral_code'=>'true'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['mobile']);
		$this->assertTrue(isset($ret['customer']['referral_code']));
		$this->assertTrue(empty($ret['customer']['invitees']));
	
	}

	public function testGetStats_3()
	{
		$reContext = new \Api\UnitTest\Context('referral');
		$reContext->set("response/constant",true);
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'only_referral_code'=>'false',
				'user_id'=>'true'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['mobile']);
		$this->assertTrue(isset($ret['customer']['referral_code']));
		$this->assertTrue(!empty($ret['customer']['invitees']));
		$this->assertTrue(isset($ret['customer']['id']));
	
	}

	public function testGetStats_4()
	{
		$reContext = new \Api\UnitTest\Context('referral');
		$reContext->set("response/constant",true);
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['mobile']);
		$this->assertTrue(isset($ret['customer']['referral_code']));
		$this->assertTrue(!empty($ret['customer']['invitees']));
	
	}

	public function testGetStats_5()
	{
		$reContext = new \Api\UnitTest\Context('referral');
		$reContext->set("response/constant",false);
		$reContext->set("code/exception",1001);
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'campaign_token'=>'234124'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(500, $ret['status']['code']);
		$this->assertEquals(1202, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['mobile']);
	
	}

	public function testGetStats_6()
	{
		$reContext = new \Api\UnitTest\Context('referral');
		$reContext->set("response/constant",false);
		$reContext->set("stats/exception",1001);
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'campaign_token'=>'234124'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(500, $ret['status']['code']);
		$this->assertEquals(1202, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['mobile']);
	
	}


	public function testGetStats_7()
	{
		global $logger, $cfg, $currentuser, $currentorg;
	
		$mobile=rand(1234567890,9987654321);
		$query = array(
				"external_id" => $mobile,
				'campaign_token' => "3958565"
		);
	
	
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(500, $ret['status']['code']);
		$this->assertEquals(1012, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['external_id']);
	}

	public function testGetStats_8()
	{
		global $logger, $cfg, $currentuser, $currentorg;
	
		$mobile=rand(1234567890,9987654321);
		$query = array(
				"id" => $mobile,
				'campaign_token' => "3958565"
		);
	
	
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(500, $ret['status']['code']);
		$this->assertEquals(1012, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['id']);
	}

	public function testGetStats_9()
	{
		global $logger, $cfg, $currentuser, $currentorg;
	
		$mobile=rand(1234567890,9987654321);
		$query = array(
				'campaign_token' => "3958565"
		);
	
		$exc=false;
		try{
			$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		}catch(Exception $e)
		{
			$exc=true;
		}
		$this->assertTrue($exc);
	}

	public function testGetStats_10()
	{
		$reContext = new \Api\UnitTest\Context('referral');
		$reContext->set("response/constant",true);
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'only_referral_code'=>'false',
				'user_id'=>'false'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'referrals', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customer']['item_status']['code']);
		$this->assertEquals($mobile, $ret['customer']['mobile']);
		$this->assertTrue(isset($ret['customer']['referral_code']));
		$this->assertTrue(!empty($ret['customer']['invitees']));
		$this->assertTrue(!isset($ret['customer']['id']));
	
	}
	
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
		$reContext = new \Api\UnitTest\Context('referral');
		$reContext->set("code/exception",false);
		$reContext->set("stats/exception",false);
	}
}
