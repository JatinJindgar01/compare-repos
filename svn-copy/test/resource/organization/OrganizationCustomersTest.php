<?php
/**
 * Created by IntelliJ IDEA.
 * User: pankaj.gupta
 * Date: 22/1/14
 * Time: 11:08 PM
 * To change this template use File | Settings | File Templates.
 */

require_once('test/resource/organization/ApiOrganizationResourceTestBase.php');

class OrganizationCustomersTest extends ApiOrganizationResourceTestBase
{
    private $currentConfigs = array();

    public function __construct(){

        parent::__construct();
    }

    public function testOrganizationCustomersFraud(){
        $entity = array("type" => "fraud");

        $response = $this->organizationResourceObj->process('v1.1', 'customers', null, $entity, 'GET');
        $this->assertEquals('200', $response['status']['code']);
    }

    public function setUp(){
        $this->login( "till.005", "123" );
        parent::setUp();
    }

    public function tearDown(){
    }
}
?>