<?php
/**
 * Created by IntelliJ IDEA.
 * User: pankaj.gupta
 * Date: 12/3/14
 * Time: 3:13 PM
 * To change this template use File | Settings | File Templates.
 */
require_once('test/resource/stores/ApiStoresResourceTestBase.php');
class StoresTasksTest extends ApiStoresResourceTestBase
{
    private $currentConfigs = array();

    public function __construct(){

        parent::__construct();
    }

    public function testStoresStaffGet(){

        $response = $this->storeResourceObj->process('v1.1', 'tasks', null, null, 'GET');
        $this->assertEquals('500', $response['status']['code']);
    }

    public function setUp(){
        $this->login( "till.005", "123" );
        parent::setUp();
    }

    public function tearDown(){
    }
}
?>