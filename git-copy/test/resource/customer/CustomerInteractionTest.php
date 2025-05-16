<?php

require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerInteractionTest extends ApiCustomerResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function testInteraction_1()
    {
        global $logger, $cfg, $currentuser, $currentorg;

        $mobile = "919886652521";

        $customer = array(
            "mobile" => $mobile,
        );

        $ret = $this->customerResourceObj->process('v1.1', 'interaction', null, $customer, 'GET');
        $this->assertEquals(200, $ret['status']['code']);
        $this->assertEquals($mobile, $ret['customer']['mobile']);
    }

    public function setUp()
    {
        $this->login( "till.005", "123" );
        parent::setUp();
    }
}
?>