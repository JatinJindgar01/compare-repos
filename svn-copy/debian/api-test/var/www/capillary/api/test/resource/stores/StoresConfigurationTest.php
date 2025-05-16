<?php
/**
 * Created by IntelliJ IDEA.
 * User: pankaj.gupta
 * Date: 12/3/14
 * Time: 3:13 PM
 * To change this template use File | Settings | File Templates.
 */
require_once('test/resource/stores/ApiStoresResourceTestBase.php');
class StoresConfigurationTest extends ApiStoresResourceTestBase
{
    private $currentConfigs = array();

    public function __construct(){

        parent::__construct();
    }

    public function testStoresConfigurationGet(){
        $store = array(
            'code' => 'store123'
        );

        $response = $this->storeResourceObj->process('v1.1', 'configurations', null, $store, 'GET');
        $this->assertEquals('200', $response['status']['code']);
    }

    public function testStoresConfigurationTzGet(){
        $store = array(
            'code' => 'store123'
        );

        $response = $this->storeResourceObj->process('v1.1', 'configurations', null, $store, 'GET');
        $this->assertNotNull('200', $response['store']['configurations']['time_zone_offset']);
    }

    public function setUp(){
        $this->login( "till.005", "123" );
        parent::setUp();
    }

    public function tearDown(){
    }
}
?>