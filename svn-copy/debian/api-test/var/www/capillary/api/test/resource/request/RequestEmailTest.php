<?php

require_once('test/resource/request/ApiRequestResourceTestBase.php');

class RequestEmailTest extends ApiRequestResourceTestBase
{
	
	private $customer;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function testReqAddEmail_1()
	{
		
		$customer=$this->addCustomer();
		req_data_cache::set('customer',$customer);
		$new_email=rand(1234567,9999999).$customer['email'];
		
		req_data_cache::set('new_email', $new_email);
		
		$post['root']['request'][0]=array(
				'customer'=>array('mobile'=>$customer['mobile']),
				'old_value'=>$customer['email'],
				'new_value'=>$new_email,
				'type'=>'change_identifier',
				'base_type'=>'email',
		);
		$query=$this->setAutoApprove(false);
		
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
		
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
		
	}
	
	public function testReqAddEmail_2()
	{
		$new_email=req_data_cache::get('new_email');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("email"=>$new_email),"GET");
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(1012,$ret['customers']['customer'][0]['item_status']['code']);
	}
	
	public function testReqAddEmail_3()
	{
		$new_email=req_data_cache::get('new_email');
		$customer=req_data_cache::get('customer');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("email"=>$customer['email']),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
		$this->assertFalse($new_email==$ret['customers']['customer'][0]['email']);
	
	}
	
	public function testReqAddEmail_4()
	{
		
		$request_id=req_data_cache::get("request_id");
		$new_email=req_data_cache::get('new_email');
		$customer=req_data_cache::get('customer');
		
		$post['root']['request'][0]=array(
				'type'=>'change_identifier',
				'base_type'=>'email',
				'id'=>$request_id
		);
		
		$ret=$this->requestResourceObj->process("v1.1","approve",$post,array(),"POST");
		
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddEmail_5()
	{
		$new_email=req_data_cache::get('new_email');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("email"=>$new_email),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
	}

	
	public function testReqAddEmail_6()
	{
	
		$customer=$this->addCustomer();
		req_data_cache::set('customer',$customer);
		$new_email=rand(1234567,9999999).$customer['email'];
	
		req_data_cache::set('new_email', $new_email);
	
		$post['root']['request'][0]=array(
				'customer'=>array('mobile'=>$customer['mobile']),
				'old_value'=>$customer['email'],
				'new_value'=>$new_email,
				'type'=>'change_identifier',
				'base_type'=>'email',
		);
		$query=$this->setAutoApprove();
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddEmail_7()
	{
		$new_email=req_data_cache::get('new_email');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("email"=>$new_email),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
	}
	
	public function testReqAddEmail_8()
	{
		$new_email=req_data_cache::get('new_email');
		$customer=req_data_cache::get('customer');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("email"=>$customer['email']),"GET");
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(1012,$ret['customers']['customer'][0]['item_status']['code']);
	
	}
	
	public function testReqAddEmail_9()
	{
	
		$old_customer=req_data_cache::get("old_customer");
		$customer=req_data_cache::get("customer");
	
		$mobile=$customer['email'];
	
		$new_mobile=$customer['email'];
	
	
		$post['root']['request'][0]=array(
				'customer'=>array('id'=>$customer['id']),
				'old_value'=>$mobile,
				'new_value'=>$new_mobile,
				'type'=>'change_identifier',
				'base_type'=>'email',
		);
		$query=$this->setAutoApprove();
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(9031,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddEmail_10()
	{
	
		$old_customer=req_data_cache::get("old_customer");
		$customer=req_data_cache::get("customer");
	
		$mobile=$customer['email'];
	
		$new_mobile=$customer['email']."asdasdasdaf@";
	
	
		$post['root']['request'][0]=array(
				'customer'=>array('id'=>$customer['id']),
				'old_value'=>$mobile,
				'new_value'=>$new_mobile,
				'type'=>'change_identifier',
				'base_type'=>'email',
		);
		$query=$this->setAutoApprove();
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(9001,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
	}
	
}