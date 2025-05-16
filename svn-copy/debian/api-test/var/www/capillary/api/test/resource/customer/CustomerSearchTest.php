<?php

require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerSearchTest extends ApiCustomerResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function testCustomerSearchByUserId()
    {
        global $logger, $cfg, $currentuser, $currentorg;
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/customer/constant', true);
        $customer = array(
            "q" => "(user_id:EQUALS:111)"
        );

        $ret = $this->customerResourceObj->process('v1.1', 'search', null, $customer, 'GET');
        $this->assertEquals(200, $ret['status']['code']);
        $this->assertEquals($ret['customer']['results']['item'][0]['user_id'], 111);
        $this->assertEquals($ret['customer']['results']['item'][0]['firstname'], 'First');
        $this->assertEquals($ret['customer']['results']['item'][0]['lastname'], 'LAst Name');
        $this->assertEquals($ret['customer']['results']['item'][0]['gender'], 'M');
        $this->assertEquals($ret['customer']['results']['item'][0]['mobile'], '911001001001');
        $this->assertEquals($ret['customer']['results']['item'][0]['email'], 'email@example.com');
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertEquals($ret['customer']['results']['item'][0]['loyalty_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_purchases'], 4000);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['last_trans_value'], 10);
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertTrue(in_array(array('name' => 'customer_age', 'value' => 100), $ret['customer']['results']['item'][0]['attributes']['attribute']));
        $this->assertEquals($ret['customer']['count'], 100);
        $this->assertEquals($ret['customer']['start'], 0);
        $this->assertEquals($ret['customer']['rows'], 10);
    }

    public function testInvalidSearchQuery()
    {
        $customer = array(
            "q" => "(user_id:ZZZZ:111)"
        );
        
        $ret = $this->customerResourceObj->process('v1.1', 'search', null, $customer, 'GET');
        $this->assertEquals($ret['status']['success'], 'false');
        $this->assertEquals($ret['status']['code'], 400);
        $this->assertEquals($ret['status']['message'], 'Input is invalid, Please check request parameters or input xml/json');
    }
    
    public function testCustomerSearchByUserMobile()
    {
        global $logger, $cfg, $currentuser, $currentorg;
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/customer/constant', true);
        $customer = array(
            "q" => "(mobile:EQUALS:911001001001)"
        );

        $ret = $this->customerResourceObj->process('v1.1', 'search', null, $customer, 'GET');
        $this->assertEquals(200, $ret['status']['code']);
        $this->assertEquals($ret['customer']['results']['item'][0]['user_id'], 111);
        $this->assertEquals($ret['customer']['results']['item'][0]['firstname'], 'First');
        $this->assertEquals($ret['customer']['results']['item'][0]['lastname'], 'LAst Name');
        $this->assertEquals($ret['customer']['results']['item'][0]['gender'], 'M');
        $this->assertEquals($ret['customer']['results']['item'][0]['mobile'], '911001001001');
        $this->assertEquals($ret['customer']['results']['item'][0]['email'], 'email@example.com');
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertEquals($ret['customer']['results']['item'][0]['loyalty_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_purchases'], 4000);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['last_trans_value'], 10);
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertTrue(in_array(array('name' => 'customer_age', 'value' => 100), $ret['customer']['results']['item'][0]['attributes']['attribute']));
        $this->assertEquals($ret['customer']['count'], 100);
        $this->assertEquals($ret['customer']['start'], 0);
        $this->assertEquals($ret['customer']['rows'], 10);
    }

    public function testCustomerSearchByUserExtId()
    {
        global $logger, $cfg, $currentuser, $currentorg;
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/customer/constant', true);
        $customer = array(
            "q" => "(external_id:EQUALS:my_ext_id)"
        );

        $ret = $this->customerResourceObj->process('v1.1', 'search', null, $customer, 'GET');
        $this->assertEquals(200, $ret['status']['code']);
        $this->assertEquals($ret['customer']['results']['item'][0]['user_id'], 111);
        $this->assertEquals($ret['customer']['results']['item'][0]['firstname'], 'First');
        $this->assertEquals($ret['customer']['results']['item'][0]['lastname'], 'LAst Name');
        $this->assertEquals($ret['customer']['results']['item'][0]['gender'], 'M');
        $this->assertEquals($ret['customer']['results']['item'][0]['mobile'], '911001001001');
        $this->assertEquals($ret['customer']['results']['item'][0]['email'], 'email@example.com');
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertEquals($ret['customer']['results']['item'][0]['loyalty_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_purchases'], 4000);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['last_trans_value'], 10);
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertTrue(in_array(array('name' => 'customer_age', 'value' => 100), $ret['customer']['results']['item'][0]['attributes']['attribute']));
        $this->assertEquals($ret['customer']['count'], 100);
        $this->assertEquals($ret['customer']['start'], 0);
        $this->assertEquals($ret['customer']['rows'], 10);
    }
    
    public function testCustomerSearchByUserEmail()
    {
        global $logger, $cfg, $currentuser, $currentorg;
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/customer/constant', true);
        $customer = array(
            "q" => "(email:EQUALS:email@example.com)"
        );

        $ret = $this->customerResourceObj->process('v1.1', 'search', null, $customer, 'GET');
        $this->assertEquals(200, $ret['status']['code']);
        $this->assertEquals($ret['customer']['results']['item'][0]['user_id'], 111);
        $this->assertEquals($ret['customer']['results']['item'][0]['firstname'], 'First');
        $this->assertEquals($ret['customer']['results']['item'][0]['lastname'], 'LAst Name');
        $this->assertEquals($ret['customer']['results']['item'][0]['gender'], 'M');
        $this->assertEquals($ret['customer']['results']['item'][0]['mobile'], '911001001001');
        $this->assertEquals($ret['customer']['results']['item'][0]['email'], 'email@example.com');
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertEquals($ret['customer']['results']['item'][0]['loyalty_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_purchases'], 4000);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['last_trans_value'], 10);
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertTrue(in_array(array('name' => 'customer_age', 'value' => 100), $ret['customer']['results']['item'][0]['attributes']['attribute']));
        $this->assertEquals($ret['customer']['count'], 100);
        $this->assertEquals($ret['customer']['start'], 0);
        $this->assertEquals($ret['customer']['rows'], 10);
    }

    public function testCustomerSearchByKey()
    {
        global $logger, $cfg, $currentuser, $currentorg;
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/customer/constant', true);
        $customer = array(
            "q" => "(key:starts:email@example.co)"
        );

        $ret = $this->customerResourceObj->process('v1.1', 'search', null, $customer, 'GET');
        $this->assertEquals(200, $ret['status']['code']);
        $this->assertEquals($ret['customer']['results']['item'][0]['user_id'], 111);
        $this->assertEquals($ret['customer']['results']['item'][0]['firstname'], 'First');
        $this->assertEquals($ret['customer']['results']['item'][0]['lastname'], 'LAst Name');
        $this->assertEquals($ret['customer']['results']['item'][0]['gender'], 'M');
        $this->assertEquals($ret['customer']['results']['item'][0]['mobile'], '911001001001');
        $this->assertEquals($ret['customer']['results']['item'][0]['email'], 'email@example.com');
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertEquals($ret['customer']['results']['item'][0]['loyalty_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_purchases'], 4000);
        $this->assertEquals($ret['customer']['results']['item'][0]['lifetime_points'], 100);
        $this->assertEquals($ret['customer']['results']['item'][0]['last_trans_value'], 10);
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], 'my_ext_id');
        $this->assertTrue(in_array(array('name' => 'customer_age', 'value' => 100), $ret['customer']['results']['item'][0]['attributes']['attribute']));
        $this->assertEquals($ret['customer']['count'], 100);
        $this->assertEquals($ret['customer']['start'], 0);
        $this->assertEquals($ret['customer']['rows'], 10);
    }
    
    public function testFallback()
    {
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/customer/empty', true);
        
        $customer = array("mobile" => "91955443" . rand(1000, 9999),
                            "email" => rand(1000, 9999) . "@example.com",
                            "external_id" => rand(100000, 999999));
        $ret = $this->addCustomerTest($customer, array(), false);
        $mobile = $customer["mobile"];
        $email = $customer["email"];
        $external_id = $customer["external_id"];
        $search = array("q" => "(mobile:EQUALS:$mobile)");
        $ret = $this->customerResourceObj->process('v1.1', 'search', null, $search, 'GET');
        $this->assertEquals($ret['customer']['results']['item'][0]['mobile'], $mobile);
        $this->assertEquals($ret['customer']['results']['item'][0]['email'], $email);
        $this->assertEquals($ret['customer']['results']['item'][0]['external_id'], $external_id);
    }
    
    public function setUp()
    {
        $this->login( "till.005", "123" );
        parent::setUp();
    }
    
    public function tearDown()
    {
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/customer/constant', false);
        $context->set('response/customer/empty', false);
    }
}
