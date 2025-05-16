<?php
/**
 * Created by IntelliJ IDEA.
 * User: pankaj.gupta
 * Date: 9/1/14
 * Time: 3:58 PM
 * To change this template use File | Settings | File Templates.
 */

require_once('test/resource/organization/ApiOrganizationResourceTestBase.php');

class OrganizationStatisticsTest extends ApiOrganizationResourceTestBase
{
    private $currentConfigs = array();

    public function __construct(){

        parent::__construct();
    }

    public function testOrganizationStatisticsGet1(){
        $response = $this->organizationResourceObj->process('v1.1', 'statistics', null, null, 'GET');
        $this->assertEquals('200', $response['status']['code']);
    }

    public function tearDown(){
    }
}
?>