<?php

require_once('test/resource/request/ApiRequestResourceTestBase.php');

class RequestAddTest extends ApiRequestResourceTestBase
{
	
	private $customer;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function testReqAdd_1()
	{
		$customer=$this->addCustomer();
		req_data_cache::set('customer',$customer);
	
		$post['root']['request'][0]=array(
				'customer'=>array('mobile'=>$customer['mobile']),
				'old_value'=>$customer['mobile'],
				'new_value'=>'91900'.rand(1234567,9999999),
				'type'=>'change_identifier',
				'base_type'=>'mobile',
		);
		$query=array('client_auto_approve'=>'false');
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAdd_2()
	{
		$customer=req_data_cache::get("customer");

		$new_value='91900'.rand(1234567,9999999);
		req_data_cache::set('new_mobile',$new_value);
		$post['root']['request'][0]=array(
				'customer'=>array('mobile'=>$customer['mobile']),
				'old_value'=>$customer['mobile'],
				'new_value'=>$new_value,
				'type'=>'change_identifier',
				'base_type'=>'mobile',
		);
		$_REQUEST=$_GET=$query=array('client_auto_approve'=>'true');
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAdd_3()
	{
		$customer=req_data_cache::get("customer");
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array('mobile'=>$customer['mobile']),'GET');
		$this->assertEquals(500,$ret['status']['code']);
	}
	
	public function testReqAdd_4()
	{
		$customer=req_data_cache::get("new_mobile");
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array('mobile'=>$customer,'GET'));
		$this->assertEquals(200,$ret['status']['code']);
	}
	
	
	public function testReqAdd_5()
	{
		$customer=req_data_cache::get("customer");
	
		$new_value=rand(1234567,9999999)."svs@captech.com";
		req_data_cache::set('new_email',$new_value);
		$post['root']['request'][0]=array(
				'customer'=>array('email'=>$customer['email']),
				'old_value'=>$customer['email'],
				'new_value'=>$new_value,
				'type'=>'change_identifier',
				'base_type'=>'email',
		);
		$_REQUEST=$_GET=$query=array('client_auto_approve'=>'true');
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAdd_6()
	{
		$customer=req_data_cache::get("customer");
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array('email'=>$customer['email']),'GET');
		$this->assertEquals(500,$ret['status']['code']);
	}
	
	public function testReqAdd_7()
	{
		$customer=req_data_cache::get("new_email");
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array('email'=>$customer,'GET'));
		$this->assertEquals(200,$ret['status']['code']);
	}
	
	
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
	}
	
}
	