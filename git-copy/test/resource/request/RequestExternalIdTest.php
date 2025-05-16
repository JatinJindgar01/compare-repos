<?php

require_once('test/resource/request/ApiRequestResourceTestBase.php');

class RequestExternalIdTest extends ApiRequestResourceTestBase
{
	
	private $customer;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function testReqAddExternalId_1()
	{
		
		$customer=$this->addCustomer();
		req_data_cache::set('customer',$customer);
		$new_external_id="91974".rand(1234567,9999999);
		
		req_data_cache::set('new_external_id', $new_external_id);
		
		$post['root']['request'][0]=array(
				'customer'=>array('external_id'=>$customer['external_id']),
				'old_value'=>$customer['external_id'],
				'new_value'=>$new_external_id,
				'type'=>'change_identifier',
				'base_type'=>'external_id',
		);
		$query=$this->setAutoApprove(false);
		
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
		
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
		
	}
	
	public function testReqAddExternalId_2()
	{
		$new_external_id=req_data_cache::get('new_external_id');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("external_id"=>$new_external_id),"GET");
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(1012,$ret['customers']['customer'][0]['item_status']['code']);
	}
	
	public function testReqAddExternalId_3()
	{
		$new_external_id=req_data_cache::get('new_external_id');
		$customer=req_data_cache::get('customer');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("external_id"=>$customer['external_id']),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
		$this->assertFalse($new_external_id==$ret['customers']['customer'][0]['external_id']);
	
	}
	
	public function testReqAddExternalId_4()
	{
		
		$request_id=req_data_cache::get("request_id");
		$new_external_id=req_data_cache::get('new_external_id');
		$customer=req_data_cache::get('customer');
		
		$post['root']['request'][0]=array(
				'type'=>'change_identifier',
				'base_type'=>'external_id',
				'id'=>$request_id
		);
		
		$ret=$this->requestResourceObj->process("v1.1","approve",$post,array(),"POST");
		
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddExternalId_5()
	{
		$new_external_id=req_data_cache::get('new_external_id');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("external_id"=>$new_external_id),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
	}

	
	public function testReqAddExternalId_6()
	{
	
		$customer=$this->addCustomer();
		req_data_cache::set('customer',$customer);
		$new_external_id="91974".rand(1234567,9999999);
		
		req_data_cache::set('new_external_id', $new_external_id);
	
		$post['root']['request'][0]=array(
				'customer'=>array('external_id'=>$customer['external_id']),
				'old_value'=>$customer['external_id'],
				'new_value'=>$new_external_id,
				'type'=>'change_identifier',
				'base_type'=>'external_id',
		);
		$query=$this->setAutoApprove();
	
		$ret=$this->requestResourceObj->process("v1.1", "add", $post, $query, 'POST');
	
		req_data_cache::set("request_id", $ret['requests']['request'][0]['id']);
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(9000,$ret['requests']['request'][0]['item_status']['code']);
	
	}
	
	public function testReqAddExternalId_7()
	{
		$new_external_id=req_data_cache::get('new_external_id');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("external_id"=>$new_external_id),"GET");
	
		$this->assertEquals(200,$ret['status']['code']);
		$this->assertEquals(1000,$ret['customers']['customer'][0]['item_status']['code']);
	}
	
	public function testReqAddExternalId_8()
	{
		$new_external_id=req_data_cache::get('new_external_id');
		$customer=req_data_cache::get('customer');
	
		$ret=$this->customerResourceObj->process("v1.1","get",array(),array("external_id"=>$customer['external_id']),"GET");
	
		$this->assertEquals(500,$ret['status']['code']);
		$this->assertEquals(1012,$ret['customers']['customer'][0]['item_status']['code']);
	
	}
	
	public function setUp()
	{
		$this->login("vimal.till", "123");
		parent::setUp();
	}
	
}