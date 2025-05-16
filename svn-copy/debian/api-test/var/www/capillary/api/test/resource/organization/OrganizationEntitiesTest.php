<?php
/**
 * Created by IntelliJ IDEA.
 * User: pankaj.gupta
 * Date: 8/1/14
 * Time: 3:58 PM
 * To change this template use File | Settings | File Templates.
 */

require_once('test/resource/organization/ApiOrganizationResourceTestBase.php');

class OrganizationEntitiesTest extends ApiOrganizationResourceTestBase
{
    private $currentConfigs = array();

    public function __construct(){

        parent::__construct();
    }

    public function testOrganizationEntitiesStoreGet1(){
        $entity = array(
            'id' => '12773780',
            'type' => 'STORE'
        );

        $response = $this->organizationResourceObj->process('v1.1', 'entities', null, $entity, 'GET');
        $this->assertEquals('200', $response['status']['code']);
        $this->assertNotNull($response['organization']['entities']['entity']['currencies']['base_currency']['label']);
        $this->assertNotNull($response['organization']['entities']['entity']['languages']['base_language']['locale']);
    }

    public function testOrganizationEntitiesTillGet1(){
        $entity = array(
            'id' => '12773836',
            'type' => 'TILL'
        );
        $response = $this->organizationResourceObj->process("v1.1", "entities", null, $entity,"GET");
        $this->assertEquals('200', $response['status']['code']);
        $this->assertNotNull($response['organization']['entities']['entity']['currencies']['base_currency']['label']);
        $this->assertNotNull($response['organization']['entities']['entity']['languages']['base_language']['locale']);
    }

    public function testOrganizationEntitiesZoneGet1(){
        $entity = array(
            'id' => '12773777',
            'type' => 'ZONE'
        );
        $response = $this->organizationResourceObj->process("v1.1", "entities", null, $entity,"GET");
        $this->assertEquals('200', $response['status']['code']);
        $this->assertNotNull($response['organization']['entities']['entity']['currencies']['base_currency']['label']);
        $this->assertNotNull($response['organization']['entities']['entity']['languages']['base_language']['locale']);
    }

    public function testOrganizationEntitiesConceptGet1(){
        $entity = array(
            'id' => '12773776',
            'type' => 'CONCEPT'
        );
        $response = $this->organizationResourceObj->process("v1.1", "entities", null, $entity,"GET");
        $this->assertEquals('200', $response['status']['code']);
        $this->assertNotNull($response['organization']['entities']['entity']['currencies']['base_currency']['label']);
        $this->assertNotNull($response['organization']['entities']['entity']['languages']['base_language']['locale']);
    }

    public function setUp(){
        $this->login( "till.005", "123" );
        parent::setUp();
    }

    public function tearDown(){
    }
}
?>