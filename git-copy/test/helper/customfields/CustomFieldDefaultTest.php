<?php

require_once('test/helper/customfields/ApiCustomFieldsTestBase.php');

class CustomFieldDefaultTest extends ApiCustomFieldsTestBase
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
    }

    public function testCustomFieldDefault()
    {
        //adding customer --- start

        $custom_field_1 = array(
            "name" => "dob",
            "value" => ""
        );

        $custom_fields = array(
            "field" => $custom_field_1,
        );

        $rand_number = rand(10000, 99999);


        $customer = array(
            "email" => $rand_number . "73customer@capillarytech.com",
            "mobile" => "9188673" . $rand_number,
            "external_id" => "EXT_73" . $rand_number,
            "firstname" => "Customer",
            "custom_fields" => $custom_fields
        );

        $query_params = array("user_id" => "true");
        global $gbl_api_version;
        $gbl_api_version = "v1.1";
        $arr_request_customers = array();
        $arr_request_customers[] = $customer;
        $request_arr = array("root" =>
            array("customer" => $arr_request_customers)
        );
        $customer_add_response = $this->customerResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");

        $user_id = $customer_add_response['customers']['customer'][0]['user_id'];

        $query_params = array("id" => $user_id);
        $response = $this->customerResourceObj->process("v1.1", "get", "", $query_params, "GET");

        $this->assertEquals(200, $response['status']['code']);
        $this->assertEquals(1, count($response['customers']['customer']));
        $this->assertEquals('dob', $response['customers']['customer'][0]['custom_fields']['field'][0]['name']);
        $this->assertEquals('10-10-1980', $response['customers']['customer'][0]['custom_fields']['field'][0]['value']);
    }

    public function testCustomField1()
    {
        //adding customer --- start

        $custom_field_1 = array(
            "name" => "dob",
            "value" => "19-01-2014"
        );

        $custom_fields = array(
            "field" => $custom_field_1,
        );

        $rand_number = rand(10000, 99999);


        $customer = array(
            "email" => $rand_number . "73customer@capillarytech.com",
            "mobile" => "9188673" . $rand_number,
            "external_id" => "EXT_73" . $rand_number,
            "firstname" => "Customer",
            "custom_fields" => $custom_fields
        );

        $query_params = array("user_id" => "true");
        global $gbl_api_version;
        $gbl_api_version = "v1.1";
        $arr_request_customers = array();
        $arr_request_customers[] = $customer;
        $request_arr = array("root" =>
            array("customer" => $arr_request_customers)
        );
        $customer_add_response = $this->customerResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");

        $user_id = $customer_add_response['customers']['customer'][0]['user_id'];

        $query_params = array("id" => $user_id);
        $response = $this->customerResourceObj->process("v1.1", "get", "", $query_params, "GET");

        $this->assertEquals(200, $response['status']['code']);
        $this->assertEquals(1, count($response['customers']['customer']));
        $this->assertEquals('dob', $response['customers']['customer'][0]['custom_fields']['field'][0]['name']);
        $this->assertEquals('19-01-2014', $response['customers']['customer'][0]['custom_fields']['field'][0]['value']);
    }
}