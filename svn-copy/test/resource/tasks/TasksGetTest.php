<?php
require_once('test/resource/tasks/ApiTasksResourceTestBase.php');
require_once 'resource/organization.php';
require_once 'resource/stores.php';

class TaskGetTest extends ApiTasksResourceTestBase
{
	
	public function __construct(){
		
		parent::__construct();
                $this->addRandomTask();
	}

        public function testGetTaskByAssoc()
        {
            $ret = $this->getTask(array("assoc_id" => $this->associate['id']));
            $this->assertEquals($ret['status']['code'], '200');
        }
        
        public function testGetAllTasks()
        {
            $ret = $this->getTask(array("all" => 1));
            $this->assertEquals($ret['status']['code'], '200');
        }

        public function setUp(){
		$this->login( "till.005", "123" );
		parent::setUp();
	}
	
	public function tearDown(){
	}
}
?>
