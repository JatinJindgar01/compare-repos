<?php

/**
 * Description of TaskReminderAddTest
 *
 * @author rohit
 */
class TaskReminderAddTest extends ApiTasksResourceTestBase {
    
    public function testReminderAdd()
    {
        $taskAdd = $this->addRandomTask();
        $addRes = $taskAdd["output"];
        $addRes = $this->addReminder($addRes['tasks']['task'][0]['id'], '2100-01-01 00:00:00', $this->associate['id'], "Reminder", $this->associate['id']);
        $res = $addRes["output"];
        $this->assertEquals($res['status']['code'], '200');
        $response_item = $res['tasks']['reminders']['reminder'][0];
        unset($response_item['item_status']);
        $input = $addRes["input"]["root"]["reminder"][0];
        $this->assertEquals($res["status"]["code"], '200');
        //$this->assertEquals($input, $response_item);
    }
}
