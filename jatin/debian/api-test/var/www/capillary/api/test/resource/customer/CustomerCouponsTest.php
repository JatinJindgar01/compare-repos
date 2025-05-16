<?php



require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerCouponsTest extends ApiCustomerResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }

	public function testCoupons_1()
	{
		global $logger, $cfg, $currentuser, $currentorg;
		
		$mobile=rand(1234567890,9987654321);
		$query = array(
				"mobile" => $mobile,
				);
		
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(500, $ret['status']['code']);
		$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
	}

	public function testCoupons_2()
	{
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
	
	}

	public function testCoupons_3()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}

	public function testCoupons_4()
	{
		global $logger, $cfg, $currentuser, $currentorg;
	
		$mobile=rand(1234567890,9987654321);
		$query = array(
				"external_id" => $mobile,
		);
	
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(500, $ret['status']['code']);
		$this->assertEquals(1012, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['external_id']);
	}

	public function testCoupons_5()
	{
		global $logger, $cfg, $currentuser, $currentorg;
	
		$query = array(
		);
	
		$exc=false;
		try{
			$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		}catch(Exception $e)
		{
			$exc=true;
		}
		$this->assertTrue($exc);
	}
	
	public function testCoupons_6()
	{
		global $logger, $cfg, $currentuser, $currentorg;
	
		$query = array(
				"external_id" => 'XYPZ002',
		);
	
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals('XYPZ002', $ret['customers']['customer'][0]['external_id']);
	}
	
	public function testCoupons_7()
	{
		global $logger, $cfg, $currentuser, $currentorg;
	
		$query = array(
				"email" => 'test@example.com',
		);
	
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals('test@example.com', $ret['customers']['customer'][0]['email']);
		$this->assertEquals('1111111', $ret['customers']['customer'][0]['mobile']);
	}
	
	public function testCoupons_8()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'series_code'=>'4323542'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_9()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'status'=>'redeemed;active'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_10()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'status'=>'expired;redeemed'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_11()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'status'=>'redeemed',
				'type'=>'campaign'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_12()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'order_by'=>'created_date',
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_13()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'order_by'=>'valid_till',
				'sort_order'=>'desc',
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_14()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'sort_order'=>'desc',
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_15()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'start_date'=>'2010-01-12'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_16()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'end_date'=>'2029-01-12',
				'start_date'=>'2011-01-01'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(isset($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function testCoupons_17()
	{
	
		$mobile="919740798372";
		$query=array(
				'mobile'=>$mobile,
				'user_id'=>'true',
				'start_date'=>'2111-01-01'
		);
	
		$ret = $this->customerResourceObj->process('v1.1', 'coupons', array(), $query, 'GET');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($mobile, $ret['customers']['customer'][0]['mobile']);
		$this->assertTrue(empty($ret['customers']['customer'][0]['coupons']));
		$this->assertTrue(isset($ret['customers']['customer'][0]['id']));
	
	}
	
	public function setUp()
	{
		$this->login("till.005", "123");
		parent::setUp();
	}
}
