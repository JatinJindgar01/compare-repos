<?php
/**
 * Created by IntelliJ IDEA.
 * User: pankaj.gupta
 * Date: 22/1/14
 * Time: 4:49 PM
 * To change this template use File | Settings | File Templates.
 */

require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerNotesTest extends ApiCustomerResourceTestBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function testNotes_1()
    {
        global $logger, $cfg, $currentuser, $currentorg;

        $email = "al@test.com";

        $customer = array(
            "email" => $email,
        );

        $ret = $this->customerResourceObj->process('v1.1', 'notes', null, $customer, 'GET');
        $this->assertEquals(200, $ret['status']['code']);
        $this->assertEquals($email, $ret['customer']['email']);
    }

    public function setUp()
    {
        $this->login("till.005", "123");
        parent::setUp();
    }
}

?>