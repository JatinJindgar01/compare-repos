<?php

require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerUpdateTest extends ApiCustomerResourceTestBase
{
    private $preservedOrgConfigsData = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function setUp()
    {
        $this->login("till.005", "123");
        parent::setUp();
        $peContext = new \Api\UnitTest\Context('pointsengine');
        $peContext->set("response/constant", true);
    }

    public function tearDown()
    {
        //reseting
        $cm = new ConfigManager();
        foreach($this->preservedOrgConfigsData AS $name => $value)
        {
            $key_value=array();
            $key_value['scope']='ORG';
            $key_value['entity_id']=$this->currentorg->org_id;
            $key_value['value']=$value;

            $cm->setKeyValue($name, $key_value);
        }
    }

    public function testCustomerUpdateFirstName()
    {
        //adding customer --- start
        $rand_number = rand(10000, 99999);
        $customer = array(
            "email" => $rand_number."73customer@capillarytech.com",
            "mobile" => "9188673".$rand_number,
            "external_id" => "EXT_73".$rand_number,
            "firstname" => "Customer"
        );

        $query_params = array("user_id" => "true");
        global $gbl_api_version;
        $gbl_api_version = "v1.1";
        $arr_request_customers = array();
        $arr_request_customers[] = $customer;
        $request_arr = array("root" =>
        array("customer" => $arr_request_customers)
        );

        $response = $this->customerResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");

        $response = $this->customerResourceObj->process("v1.1", "update", $request_arr, $query_params, "POST");

        $this->assertEquals(200, $response['status']['code']);
        $this->assertEquals(1, count($response['customers']['customer']));
        $this->assertEquals(1000, $response['customers']['customer'][0]['item_status']['code']);

        $this->assertEquals(true, isset($response['customers']['customer'][0]['user_id']));

        $customer = array(
            "email" => $rand_number."73customer@capillarytech.com",
            "mobile" => "9188673".$rand_number,
            "external_id" => "EXT_73".$rand_number,
            "firstname" => "UpdatedCustomer"
        );

        $response = $this->customerResourceObj->process("v1.1", "update", $request_arr, $query_params, "POST");
        $this->assertEquals(200, $response['status']['code']);
        $this->assertEquals(1, count($response['customers']['customer']));
        $this->assertEquals(1000, $response['customers']['customer'][0]['item_status']['code']);
        $this->assertEquals(true, isset($response['customers']['customer'][0]['user_id']));
    }
}