<?php
include_once('test/resource/ApiResourceTestBase.php');
include_once('resource/tasks.php');

class ApiTasksResourceTestBase extends ApiResourceTestBase
{
        protected $associate;
        protected $store;
        protected $added_task_id;
        
	public function __construct()
	{
		parent::__construct();
        }
        
        public function setUp()
        {
                $this->login( "till.005", "123" );
                $entityType = array("type" => "store");
                $orgRes = new OrganizationResource();
                $storeRes = new StoreResource();
                $orgEntities = $orgRes->process('v1.1', 'entities', null, $entityType, 'GET');
                $this->stores = $orgEntities['organization']['entities']['entity'];
                $this->store_ids = array();
                foreach($this->stores as $store)
                {
                    $this->store_ids[] = $store['id'];
                }
                //print_r($this->store);
                $associateRes = $storeRes->process('v1.1', 'staff', null, array("type" => "associate"), 'GET');
                $this->associate = $associateRes['store']['staff']['user'][0];
                if(empty($this->associate))
                {
                    throw new Exception("[Task Add UnitTest] No associate added.");
                }
                $this->status = "OPEN";
                $mapping = array('root' => array(
                    "status" => array(
                        array(
                        "label" => $this->status,
                        "value" => $this->status
                        )
                    )
                ));
                $this->tasksResourceObj = new TaskResource();
                $new_status_mapping_res = $this->tasksResourceObj->process('v1.1','statusmapping', $mapping, null, 'POST');
	}
        
        protected function addRandomTask($override = array())
        {
            $entry = array("customer_id" => "", "associate_id" => $this->associate['id'], "status" => "OPEN");
            $task = array("id" => -1,
                           "local_id" => 123,
                           "title" => "test task " . rand(1, 9999),
                           "body" => "task " . rand(1, 9999),
                           "start_date" => "20" . rand(14,99) . "-01-01 00:00:00",
                           "end_date" => "21" . rand(10, 99) . "-01-01 00:00:00",
                           "expiry_date" => "22". rand(10, 99) . "-01-01 00:00:00",
                           "type" => "MEMO",
                           "action" => array("type" => "SMS", "template" => "Hi"),
                           "entries" => array("entry" => array($entry)),
                           "creator" => array("type" => "cashier", "id" => $this->associate['id']),
                           "execute_by_all" => "true",
                           "executable_by_type" => "store",
                           "executable_by_ids" => implode(",", $this->store_ids)
                          );
            $task = array_merge($task, $override);
            $req = array("root" => array("task" => array($task)));
            $this->tasksResourceObj = new TaskResource();
            $ret = $this->tasksResourceObj->process("v1.1", "add", $req, null,"POST");
            return array("input" => $task, "output" => $ret);
        }
        
        protected function getTask($query_params)
        {
            $ret = $this->tasksResourceObj->process("v1.1", "get", null, $query_params,"POST");
            return $ret;
        }
        
        protected function addTaskStatus($label, $status)
        {
            $mapping = array('root' => array(
            "status" => array(
                array(
                "label" => $label,
                "value" => $status
                )
                )
            ));
            $this->tasksResourceObj = new TaskResource();
            $res = $this->tasksResourceObj->process('v1.1','statusmapping', $mapping, null, 'POST');
            return $res;
        }
        
        protected function getTaskStatus($query_params)
        {
            $ret = $this->tasksResourceObj->process("v1.1", "statusmapping", null, $query_params,"GET");
            return $ret;
        }
        
        protected function addReminder($task_id, $time, $created_by, $template, $remindee_id)
        {
            $data = array("root" => array(
                "reminder" => array(
                    array( "task_id" => $task_id,
                            "local_id" => "",
                            "time" => $time,
                            "created_by" => $created_by,
                            "template" => $template,
                            "remindee_id" => $remindee_id
                        ))
                )
            );
            $res = $this->tasksResourceObj->process('v1.1','reminder', $data, null, 'POST');
            return array("input" => $data, "output" => $res);
        }
}

?>
