<?php

require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class ReferralInviteTest extends ApiCustomerResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function testInvite_1()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile=rand(1234567890,9987654321);
    
    	$customer = array(
    			"mobile" => $mobile,
    			'campaign_token' => "3958565"
    	);
    
    	$data['root']=array('customer'=>$customer);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    }

    public function testInvite_2()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$email="ut_".rand(1234567890,9987654321)."@ut.com";
    
    	$customer = array(
    			"email" => $email,
    			'campaign_token' => "3958565"
    	);
    
    	$data['root']=array('customer'=>$customer);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($email, $ret['customers']['customer'][0]['email']);
    }
    
    public function testInvite_3()
    {
    	
    	global $logger, $cfg, $currentuser, $currentorg;
    	
    	$GLOBALS['referral_E_ReferralType']=array('EMAIL'=>0,'MOBILE'=>1);
    	
    	$email="vimal@vimal.com";
    	$customer=array(
    					'email'=>$email,
    					'referrals'=>array(
    							'referral_type'=>array(array(
    									'type'=>'EMAIL',
    									'referral'=>array(array(
    											'id'=>424234,
    											'name'=>'Dexter',
    											'identifier'=>'adasd@c as fsa24sd invalid email  @ dasdd.com.asdiasfm',
    											'invited_on'=>'2013-09-12T13:30:21+5:30'
    											)
    									))
    									)
    							)
    			);
    	
    	$data['root']['customer']=array($customer);
    	
    	
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($email, $ret['customers']['customer'][0]['email']);
    	$this->assertEquals('919740798372', $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals(1701,$ret['customers']['customer'][0]['referrals']['referral_type'][0]['referral'][0]['status']['code']);
    	$this->assertEquals('true',$ret['status']['success']);
    	 
    }


    public function testInvite_4()
    {
    	 
    	global $logger, $cfg, $currentuser, $currentorg;
    	 
    	$GLOBALS['referral_E_ReferralType']=array('EMAIL'=>0,'MOBILE'=>1);

    	$reContext = new \Api\UnitTest\Context('referral');
    	$reContext->set("response/constant",1001);
    	
    	$email="vimal@vimal.com";
    	$customer=array(
    			'email'=>$email,
    			'referrals'=>array(
    					'referral_type'=>array(array(
    							'type'=>'EMAIL',
    							'referral'=>array(array(
    									'id'=>424234,
    									'name'=>'Dexter',
    									'identifier'=>'dexter@mpd.com',
    									'invited_on'=>'2013-09-12T13:30:21+5:30'
    							)
    							))
    					)
    			)
    	);
    	 
    	$data['root']['customer']=array($customer);
    	 
    	 
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(200, $ret['status']['code']);
    	$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($email, $ret['customers']['customer'][0]['email']);
    	$this->assertEquals('919740798372', $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals(100,$ret['customers']['customer'][0]['referrals']['referral_type'][0]['referral'][0]['status']['code']);
    	$this->assertEquals('true',$ret['status']['success']);
    
    }
    

    public function testInvite_5()
    {
    
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$GLOBALS['referral_E_ReferralType']=array('EMAIL'=>0,'MOBILE'=>1);
    
    	$reContext = new \Api\UnitTest\Context('referral');
    	$reContext->set("invite/exception",1001);
    	 
    	$mobile="919740798372";
    	$customer=array(
    			'mobile'=>$mobile,
    			'referrals'=>array(
    					'referral_type'=>array(array(
    							'type'=>'EMAIL',
    							'referral'=>array(array(
    									'id'=>424234,
    									'name'=>'Dexter',
    									'identifier'=>'dexter@mpd.com',
    									'invited_on'=>'2013-09-12T13:30:21+5:30'
    							)
    							))
    					)
    			)
    	);
    
    	$data['root']['customer']=array($customer);
    
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1202, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals('false',$ret['status']['success']);
    
    }
    

    public function testInvite_6()
    {
    
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$GLOBALS['referral_E_ReferralType']=array('EMAIL'=>0,'MOBILE'=>1);
    
    	$reContext = new \Api\UnitTest\Context('referral');
    	$reContext->set("invite/exception",1002);
    	 
    	$mobile="919740798372";
    	$customer=array(
    			'mobile'=>$mobile,
    			'referrals'=>array(
    					'referral_type'=>array(array(
    							'type'=>'EMAIL',
    							'referral'=>array(array(
    									'id'=>424234,
    									'name'=>'Dexter',
    									'identifier'=>'dexter@mpd.com',
    									'invited_on'=>'2013-09-12T13:30:21+5:30'
    							)
    							))
    					)
    			)
    	);
    
    	$data['root']['customer']=array($customer);
    
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1203, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals('false',$ret['status']['success']);
    
    }
    

    public function testInvite_7()
    {
    
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$GLOBALS['referral_E_ReferralType']=array('EMAIL'=>0,'MOBILE'=>1);
    
    	$reContext = new \Api\UnitTest\Context('referral');
    	$reContext->set("invite/exception",1003);
    	 
    	$mobile="919740798372";
    	$customer=array(
    			'mobile'=>$mobile,
    			'referrals'=>array(
    					'referral_type'=>array(array(
    							'type'=>'EMAIL',
    							'referral'=>array(array(
    									'id'=>424234,
    									'name'=>'Dexter',
    									'identifier'=>'dexter@mpd.com',
    									'invited_on'=>'2013-09-12T13:30:21+5:30'
    							)
    							))
    					)
    			)
    	);
    
    	$data['root']['customer']=array($customer);
    
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1204, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals('false',$ret['status']['success']);
    
    }
    

    public function testInvite_8()
    {
    
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$GLOBALS['referral_E_ReferralType']=array('EMAIL'=>0,'MOBILE'=>1);
    
    	$reContext = new \Api\UnitTest\Context('referral');
    	$reContext->set("invite/exception",1004);
    
    	$mobile="919740798372";
    	$customer=array(
    			'mobile'=>$mobile,
    			'referrals'=>array(
    					'referral_type'=>array(array(
    							'type'=>'EMAIL',
    							'referral'=>array(array(
    									'id'=>424234,
    									'name'=>'Dexter',
    									'identifier'=>'dexter@mpd.com',
    									'invited_on'=>'2013-09-12T13:30:21+5:30'
    							)
    							))
    					)
    			)
    	);
    
    	$data['root']['customer']=array($customer);
    
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1205, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals('false',$ret['status']['success']);
    
    }

    public function testInvite_9()
    {
    
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$GLOBALS['referral_E_ReferralType']=array('EMAIL'=>0,'MOBILE'=>1);
    
    	$reContext = new \Api\UnitTest\Context('referral');
    	$reContext->set("invite/exception",1005);
    
    	$mobile="919740798372";
    	$customer=array(
    			'mobile'=>$mobile,
    			'referrals'=>array(
    					'referral_type'=>array(array(
    							'type'=>'EMAIL',
    							'referral'=>array(array(
    									'id'=>424234,
    									'name'=>'Dexter',
    									'identifier'=>'dexter@mpd.com',
    									'invited_on'=>'2013-09-12T13:30:21+5:30'
    							)
    							))
    					)
    			)
    	);
    
    	$data['root']['customer']=array($customer);
    
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1206, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals('false',$ret['status']['success']);
    
    }

    public function testInvite_10()
    {
    
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$GLOBALS['referral_E_ReferralType']=array('EMAIL'=>0,'MOBILE'=>1);
    
    	$reContext = new \Api\UnitTest\Context('referral');
    	$reContext->set("invite/exception",10);
    
    	$mobile="919740798372";
    	$customer=array(
    			'mobile'=>$mobile,
    			'referrals'=>array(
    					'referral_type'=>array(array(
    							'type'=>'EMAIL',
    							'referral'=>array(array(
    									'id'=>424234,
    									'name'=>'Dexter',
    									'identifier'=>'dexter@mpd.com',
    									'invited_on'=>'2013-09-12T13:30:21+5:30'
    							)
    							))
    					)
    			)
    	);
    
    	$data['root']['customer']=array($customer);
    
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1222, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
    	$this->assertEquals('false',$ret['status']['success']);
    
    }

    public function testInvite_11()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile=rand(1234567890,9987654321);
    
    	$customer = array(
    			'campaign_token' => "3958565"
    	);
    
    	$data['root']=array('customer'=>$customer);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1015, $ret['customers']['customer'][0]['item_status']['code']);
    }

    public function testInvite_12()
    {
    	global $logger, $cfg, $currentuser, $currentorg;
    
    	$mobile=rand(1234567890,9987654321);
    
    	$customer = array(
    			'id'=>495950,
    			'campaign_token' => "3958565"
    	);
    
    	$data['root']=array('customer'=>$customer);
    
    	$ret = $this->customerResourceObj->process('v1.1', 'referrals', $data, null, 'POST');
    	$this->assertEquals(500, $ret['status']['code']);
    	$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
    	$this->assertEquals($customer['id'], $ret['customers']['customer'][0]['id']);
    }
    
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
		$reContext = new \Api\UnitTest\Context('referral');
		$reContext->set("invite/exception",false);
	}
}
