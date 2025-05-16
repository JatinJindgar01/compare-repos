<?php
/**
 * Created by IntelliJ IDEA.
 * User: pankaj.gupta
 * Date: 9/1/14
 * Time: 3:58 PM
 * To change this template use File | Settings | File Templates.
 */

require_once('test/resource/organization/ApiOrganizationResourceTestBase.php');

class OrganizationCustomFieldsTest extends ApiOrganizationResourceTestBase
{
    private $currentConfigs = array();

    public function __construct(){

        parent::__construct();
    }

    public function testOrganizationCustomFieldsCFGet1(){
        $query_params = array(
            'scope' => 'loyalty_registration' );
        $response = $this->organizationResourceObj->process('v1.1', 'customfields', null, $query_params, 'GET');
        $this->assertEquals('200', $response['status']['code']);
    }

    public function tearDown(){
    }

    public function setUp()
    {
        $this->login("till.005", "123");
        parent::setUp();
        $peContext = new \Api\UnitTest\Context('pointsengine');
        $peContext->set("response/constant", true);
    }
}
?>