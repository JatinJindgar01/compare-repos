<?php

require_once('test/resource/request/ApiRequestResourceTestBase.php');

class RequestMobileTest extends ApiRequestResourceTestBase
{
	
	private $customer;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function testReqAddMobile_1()
	{
		
		$customer=$this->addCustomer();
		req_data_cache::set('customer',$customer);
		$new_mobile="91974".rand(1234567,9999999);
		
		req_data_cache::set('new_mobile', $new_mobile);
		
		$post['root']['request'][0]=array(
				'customer'=>array('mobile'=>$customer['mobile']),
				'old_value'=>$customer['mobile'],
				'new_value'=>$new_mobile,
				'type'=>'change_identifier',
				'base_type'=>'mobile',
		);
		$query=$this->setAutoApprove(false);
		
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
		
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
		
	}
	
	public function testReqAddMobile_2()
	{
		$new_mobile=req_data_cache::get('new_mobile');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("mobile"=>$new_mobile),"GET");
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(1012,$ret['customers']['customer'][0]['item_status']['code']);
	}
	
	public function testReqAddMobile_3()
	{
		$new_mobile=req_data_cache::get('new_mobile');
		$customer=req_data_cache::get('customer');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("mobile"=>$customer['mobile']),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
		$this->assertFalse($new_mobile==$ret['customers']['customer'][0]['mobile']);
	
	}
	
	public function testReqAddMobile_4()
	{
		
		$request_id=req_data_cache::get("request_id");
		$new_mobile=req_data_cache::get('new_mobile');
		$customer=req_data_cache::get('customer');
		
		$post['root']['request'][0]=array(
				'type'=>'change_identifier',
				'base_type'=>'mobile',
				'id'=>$request_id
		);
		
		$ret=$this->requestResourceObj->process("v1.1","approve",$post,array(),"POST");
		
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddMobile_5()
	{
		$new_mobile=req_data_cache::get('new_mobile');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("mobile"=>$new_mobile),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
	}

	
	public function testReqAddMobile_6()
	{
		
		$old_customer=req_data_cache::get("customer");
		req_data_cache::set("old_customer",$old_customer);
	
		$customer=$this->addCustomer();
		req_data_cache::set('customer',$customer);
		$new_mobile="91974".rand(1234567,9999999);
		
		req_data_cache::set('new_mobile', $new_mobile);
	
		$post['root']['request'][0]=array(
				'customer'=>array('mobile'=>$customer['mobile']),
				'old_value'=>$customer['mobile'],
				'new_value'=>$new_mobile,
				'type'=>'change_identifier',
				'base_type'=>'mobile',
		);
		$query=$this->setAutoApprove();
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddMobile_7()
	{
		$new_mobile=req_data_cache::get('new_mobile');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("mobile"=>$new_mobile),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
	}
	
	public function testReqAddMobile_8()
	{
		$new_mobile=req_data_cache::get('new_mobile');
		$customer=req_data_cache::get('customer');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("mobile"=>$customer['mobile']),"GET");
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(1012,$ret['customers']['customer'][0]['item_status']['code']);
	
	}
	
	public function testReqAddMobile_9()
	{
	
		$old_customer=req_data_cache::get("old_customer");
		$customer=req_data_cache::get("customer");

		$mobile=$customer['mobile'];
		
		$new_mobile=$customer['mobile'];
	
	
		$post['root']['request'][0]=array(
				'customer'=>array('id'=>$customer['id']),
				'old_value'=>$mobile,
				'new_value'=>$new_mobile,
				'type'=>'change_identifier',
				'base_type'=>'mobile',
		);
		$query=$this->setAutoApprove();
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(9031,$ret['requests']['request'][0]['item_status']['code']);
	
	}

	public function testReqAddMobile_10()
	{
	
		$old_customer=req_data_cache::get("old_customer");
		$customer=req_data_cache::get("customer");

		$mobile=$customer['mobile'];
		
		$new_mobile=$customer['mobile']."asdasdasdaf@";
	
	
		$post['root']['request'][0]=array(
				'customer'=>array('id'=>$customer['id']),
				'old_value'=>$mobile,
				'new_value'=>$new_mobile,
				'type'=>'change_identifier',
				'base_type'=>'mobile',
		);
		$query=$this->setAutoApprove();
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(9012,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddMobile_11()
	{
	
		$old_customer=req_data_cache::get("customer");
		req_data_cache::set("old_customer",$old_customer);
	
		$customer=$this->addCustomer();
		req_data_cache::set('customer',$customer);
		$new_mobile="91974".rand(1234567,9999999);
	
		req_data_cache::set('new_mobile', $new_mobile);
	
		$post['root']['request'][0]=array(
				'customer'=>array('mobile'=>$customer['mobile']),
				'old_value'=>$customer['mobile'],
				'new_value'=>$new_mobile,
				'type'=>'change_identifier',
				'base_type'=>'mobile',
		);
		$query=$this->setAutoApprove(false);
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddMobile_12()
	{
		$request_id=req_data_cache::get("request_id");
		$post['root']['request'][0]=array(
				'type'=>'change_identifier',
				'base_type'=>'mobile',
				'id'=>$request_id,
		);
		
		$ret=$this->requestResourceObj->process("v1.1", "reject", $post, $query, 'POST');
		
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(9032,$ret['requests']['request'][0]['item_status']['code']);
		
	}
	
	public function testReqAddMobile_13()
	{
		$request_id=req_data_cache::get("request_id");
		$post['root']['request'][0]=array(
				'type'=>'change_identifier',
				'base_type'=>'mobile',
				'id'=>$request_id,
				'updated_comments'=>'ut'
		);
		$query=$this->setAutoApprove();
	
		$ret=$this->requestResourceObj->process("v1.1", "reject", $post, $query, 'POST');
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddMobile_14()
	{
		$request_id=req_data_cache::get("request_id");
		$post['root']['request'][0]=array(
				'type'=>'change_identifier',
				'base_type'=>'mobile',
				'id'=>$request_id,
		);
	
		try{
			$ret=$this->requestResourceObj->process("v1.1", "appprove", $post, $query, 'POST');
		}catch(Exception $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testReqAddMobile_15()
	{
		$request_id=req_data_cache::get("request_id");
		$post['root']['request'][0]=array(
				'type'=>'change_identifier',
				'base_type'=>'mobile',
				'id'=>$request_id,
		);
	
		$ret=$this->requestResourceObj->process("v1.1", "approve", $post, $query, 'POST');
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(9013,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
	}
	
}