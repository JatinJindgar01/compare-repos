<?php

/**
 * Description of TaskStatusAddTest
 *
 * @author rohit
 */

require_once('test/resource/tasks/ApiTasksResourceTestBase.php');
include_once('resource/tasks.php');


class TaskStatusGetTest extends ApiTasksResourceTestBase {
    
    public function testTaskStatusGet()
    {
        $addRes = $this->addTaskStatus("OPEN" . rand(1,1000000), "OPEN");
        $res = $this->getTaskStatus(array());
        $added_status = $addRes['tasks']['task_statuses']['status'][0];
        unset($added_status['item_status']);
        $this->assertTrue(in_array($added_status, $res['tasks']['task_statuses']['status']));
    }
}
