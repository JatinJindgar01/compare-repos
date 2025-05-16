<?php

/**
 * Description of TaskAddTest
 *
 * @author rr0hit
 */

require_once('test/resource/tasks/ApiTasksResourceTestBase.php');
include_once('resource/tasks.php');

class TaskAddTest extends ApiTasksResourceTestBase {
    
    public function testTaskAdd()
    {
        $task_creation = $this->addRandomTask();
        $task = $task_creation["input"];
        $ret = $task_creation["output"];
        $this->assertEquals($ret['status']['code'], '200');
        $check_keys = array_keys($task);
        unset($check_keys[array_search('id', $check_keys)]);
        unset($check_keys[array_search('entries', $check_keys)]);
        unset($check_keys[array_search('creator', $check_keys)]);
        foreach ($check_keys as $k)
        {
            $this->assertEquals($task[$k], $ret['tasks']['task'][0][$k]);
        }
        $this->assertEquals($task['creator']['id'], $ret['tasks']['task'][0]['creator']['id']);
    }
    
    public function testTaskAddInvalidCreator()
    {
        $task_creation = $this->addRandomTask($override = array("creator" => array("type" => "cashier", "id" => -1)));
        $task = $task_creation["input"];
        $ret = $task_creation["output"];
        $this->assertEquals($ret['status']['code'], '500');
        $this->assertEquals($ret['tasks']['task'][0]['item_status']['code'], '5201');
    }
    
    public function testTaskAddInvalidStoreId()
    {
        $task_creation = $this->addRandomTask($override = array("executable_by_ids" => -1));
        $task = $task_creation["input"];
        $ret = $task_creation["output"];
        $this->assertEquals($ret['status']['code'], '500');
        $this->assertEquals($ret['tasks']['task'][0]['item_status']['code'], '5201');
    }
    
    public function setUp(){
        $this->login( "till.005", "123" );
	parent::setUp();
    }
	
    public function tearDown(){
    }
}
