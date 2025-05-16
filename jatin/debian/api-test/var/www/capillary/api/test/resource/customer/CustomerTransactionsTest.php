<?php
/**
 * Created by IntelliJ IDEA.
 * User: pankaj.gupta
 * Date: 22/1/14
 * Time: 4:13 PM
 * To change this template use File | Settings | File Templates.
 */

require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerTransactionsTest extends ApiCustomerResourceTestBase
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

        $ret = $this->customerResourceObj->process('v1.1', 'transactions', null, $customer, 'GET');
        $this->assertEquals(200, $ret['status']['code']);
        $this->assertEquals($mobile, $ret['customer']['mobile']);
        $this->assertNotNull($ret['customer']['transactions']);
    }

    public function setUp()
    {
        $this->login("till.005", "123");
        parent::setUp();
    }
}

?>