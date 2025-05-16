<?php

/**
 * Description of TaskStatusAddTest
 *
 * @author rohit
 */

require_once('test/resource/tasks/ApiTasksResourceTestBase.php');
include_once('resource/tasks.php');


class TaskStatusAddTest extends ApiTasksResourceTestBase {
    
    public function testTaskAddStatus()
    {
        $res = $this->addTaskStatus("OPEN" . microtime(true), "OPEN");
        $this->assertEquals($res['status']['code'], '200');
        foreach($mapping['root']['status'][0] as $k => $v)
        {
            $this->assertEquals($res['tasks']['task_statuses']['status'][0][$k], $v);
        }
    }
    
    public function testTaskAddInvalidStatus()
    {
        $res = $this->addTaskStatus("OPEN" . microtime(true), "OPEN" . microtime(true));
        $this->assertEquals($res['status']['code'], '201');
    }
}
