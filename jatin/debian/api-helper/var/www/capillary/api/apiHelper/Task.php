<?php
include_once 'common.php';

$logger = new ShopbookLogger();

// currently only a place holder for preventing copy paste in common code. inheritence is not implied.
abstract class DBRow{
    static $row, $table; //ovverride
    //private $row;
    protected static $db;
    private $is_from_db = false;
    protected $dirty;
    public function __construct($row){
        if(!is_array($row))
            throw new TaskException("Invalid row specified for construction");

        $this->row = array_merge(static::$row, $row);
        if (self::$db === null)
            self::$db = new Dbase('users');
    }
    public function init(){
        if (self::$db === null)
            self::$db = new Dbase('users');
    }
    public function __get($name){
        if(array_key_exists($name, $this->row))
            return trim($this->row[$name], "'");
        return null;
    }
    public function __set($name, $value){
        if($name == "row") $this->row = $value;
        elseif(array_key_exists($name, $this->row)){
            if($this->is_from_db === true){
                if($this->dirty === null)
                    $this->dirty = array();
                if(!array_key_exists($name, $this->dirty))
                    $this->dirty[$name] = $this->row[$name]; // TODO: handle no change
            }
            $this->row[$name] = $value;
        }
    }

    public function updateRow($row){
        foreach ($row as $key => $value)
            if($key != 'id')
                $this->__set($key, $value);
    }
    // temp. change
    private function quotify(){
        foreach (static::$row as $key => $value) {
            if(preg_match("/^'.*'$/", $value) && (!preg_match("/^'.*'$/", $this->row[$key])))
                $this->row[$key] = "'{$this->row[$key]}'";
        }
    }
    public function insertSqlFragment(){
        //$row = Util::mysqlEscapeArray($this->row);
        return "("
        .join(", ", array_values($this->row))  //pick only for keys in self::row??
        .")";
    }
    // for now returns retval from insert/update rather than DBRow Object
    public function save(){
        $this->quotify(); // can be optimized for insert
        if($this->is_from_db)
            return $this->update();
        else
            return $this->insert();
    }
    protected function insert(){
        return self::insertValues($this->insertSqlFragment());
    }
    protected function update(){
        if($this->is_from_db && count($this->dirty)>0){
            $values = "";
            foreach (array_keys($this->dirty) as $dirty_key) {
                $values .= "$dirty_key={$this->row[$dirty_key]},";
            }
            $columns = array_keys($this->row);

            $where_org_id = "";
            if(in_array("org_id", $columns))
            {
            	global $currentorg;
            	$where_org_id = " AND org_id = $currentorg->org_id";
            }
            $values = rtrim($values, ',');
            $sql = "UPDATE " . static::$table . " SET $values WHERE id = $this->id $where_org_id";
            // TODO: move dirty to $this->row
            return self::$db->update($sql);
        }
    }
    public static function insertValues($values_sql){
    	
        if (gettype($values_sql) == 'array')
            $values_sql = join(", ", $values_sql);
        $sql = "INSERT INTO ". static::$table . " ("
        .join(", ", array_keys(static::$row))
        .") VALUES $values_sql";
        return self::$db->insert($sql);
    }

    public static function getOneFromCond($cond, $org_id = -1){
    	if($org_id != -1)
    		$cond .= " AND org_id = $org_id";
        $rows = self::$db->query("SELECT * FROM ". static::$table . " WHERE $cond LIMIT 1");
        
        if(!is_array($rows[0]))
            throw new Exception("not found $cond");

        $obj = new static($rows[0]);
        $obj->is_from_db = true;
        return $obj;
    }

    public static function getFromId($id, $org_id){
        return self::getOneFromCond("id=$id", $org_id);
    }
    protected function isFromDb(){
        return $this->is_from_db;
    }
}
DBRow::init();
class TaskException extends Exception{}
class InvalidParamException extends TaskException{}
class TaskUpdateException extends TaskException{}
class Task extends DBRow{ 
    public static $table = "task";
    
    public static $row = array(
        'id'     => "0",
        'org_id' => '0',

        'title' => "''",
        'body' => "''",

        'start_date' => "''",
        'end_date' => "''",

        'status' => "0",                 // Completed/Discarded/Archived??
        'updated_by' => '0',
        'updated_on' => 'NOW()',

        'statuses' => "",
        'tags' =>"''",                        // label, priority
        'expiry_date' => "''",
        'is_memo'  => "0",

        'action_type' => "'none'",            // SMS/Email/Call/None
        'action_template' => "''",

        'created_by_type' => "'manager'",        // Manager/Cashier
        'created_by_id' => '0',

        'executable_by_type' => "'store'",     // Store/Cashier
        'executable_by_ids' => "''",      // list of IDs
        'execute_by_all' => "0",         // boolean

        'customer_target' => "0",         // boolean
        'comment' => "''",
    	'description' => "''",
    	'valid_days_from_create' => "1"
        );

    public static function createMemo($row){

        $memo_override = array(
            'is_memo'         => "1",
            'status'          => "1",
            'statuses'        => "",
            'action_type'     => "'none'",
            'action_template' => "''",
            'created_by_type' => "'manager'",
            'execute_by_all'  => "1",
            'customer_target' => "0"
            );

        $row_memo = array_merge($memo_override, $row);
        return new self($row_memo);
    }

    public function statuses($internal_statuses=null){
        return OrgTaskStatuses::getStatuses($this->org_id, $internal_statuses, $this->statuses);
    }
    public function validate(){
        // TODO:
        // other logical?
        
        if(!ctype_digit($this->status))
            throw new TaskException("non-integral status $this->status");
			
        if($this->isFromDb()){
        	
            if (array_key_exists("statuses", $this->dirty) || array_key_exists("executable_by_type", $this->dirty) || array_key_exists("execute_by_all", $this->dirty))
                throw new TaskUpdateException("non updateable fields specified in update");
            // TODO: executable_by_ids change
        }
        else{
            $status_arr = Util::mysqlEscapeArray(explode(",", $this->statuses));
            $status_rows = "SELECT '" . array_shift($status_arr) . "' AS aa UNION SELECT '". join($status_arr, "' UNION SELECT '"). "'";
            //$sql = "SELECT GROUP_CONCAT(ts.status) FROM task_statuses ts JOIN (select sg1.aa from ($status_rows) sg1) AS sg ON (ts.status= sg.aa) WHERE ts.org_id=$this->org_id GROUP BY ts.org_id";
            $sql = "SELECT GROUP_CONCAT(ts.id) FROM task_statuses ts JOIN (select sg1.aa from ($status_rows) sg1) AS sg ON (ts.status= sg.aa) WHERE ts.org_id=$this->org_id GROUP BY ts.org_id";
            $this->statuses = "'" .self::$db->query_scalar($sql). "'";
        }
        return true;
    }
    protected function insert(){
        //OrgTaskStatuses::getStatuses($this->org_id, array(OrgTaskStatuses::ST_OPEN), $this->statuses);
        $sts = $this->statuses(array(OrgTaskStatuses::ST_OPEN));// except
        $this->status = $sts[0]['id'];
        if(!$this->validate())
            return false;
        return parent::insert();
    }
    protected function update(){
        if(!$this->validate())
            return false;
        return parent::update();
    }
    public function test(){
        $sts = $this->statuses(array(OrgTaskStatuses::ST_COMPLETE));

    }
    public function getStatusObject($store_id, $cashier_id=null, $customer_id=null){
        $class = "TaskStatus";
        $cond  = "";
        if($this->execute_by_all == 1 && $this->customer_target != 1){
            if($cashier_id<1)
                throw new TaskException("cashier_id not supplied for execute_by_all task");
            $cond = "AND executer_id = $cashier_id";
        }
        if($this->customer_target==1){
            if($customer_id<1)
                throw new TaskException("customer_id not supplied for customer task");
            $class = "CustomerTaskStatus";
            $cond = "AND customer_id = $customer_id";
        }
        
        $columns = array_keys($this->row);
        
        $org_id = -1;
        if(in_array("org_id", $columns))
        {
        	global $currentorg;
        	//$where_org_id = " AND org_id = $currentorg->org_id";
        	$org_id = $currentorg->org_id;
        }
        return $class::getOneFromCond("task_id = $this->id AND store_id=$store_id $cond", $org_id);
    }
}

class TaskStatus extends DBRow{
    public static $table = "task_status";
    public static $row = array(
        'id' => "0",
        'task_id' => "0",
        'org_id'  => '0',
        'store_id' => '',
        'executer_id' => '0',             // Task::executable_by_ids
        'updated_on' => 'NOW()',
    	'created_on' => 'NOW()',
        'status' => "1",
    	"updated_by_till_id" => 0
        );
}

class CustomerTaskStatus extends DBRow{
    public static $table = "task_status_customer";
    public static $row = array(
        'id' => '0',
        'task_id' => '0',
        'org_id' => '',
        'store_id' => '',
        'customer_id' => '',              // Customer ID
        'updated_by' => '0',
        'updated_on' => 'NOW()',
    	'created_on' => 'NOW()',
        'status' => "1",
    	"updated_by_till_id" => 0,
        "title" => "''",
        "body" => "''"
        );
}

class OrgTaskStatuses extends DBRow{
    const ST_OPEN = 'OPEN';
    const ST_COMPLETE = 'COMPLETE';

    public static $table = "task_statuses";
    public static $row = array(
        'id' => '0',
        'org_id' => '',
        'internal_status' => "''",        // enum
        'status' => "''"
        );

    // internal_statuses = array(ST_OPEN, ST_COMPLETE);
    // task_statuses     = "s1,s2,s3,s4";
    public static function getStatuses($org_id, $internal_statuses=null, $task_statuses=null){
        if($task_statuses)
            $task_statuses = Util::mysqlEscapeString($task_statuses);
        $sql = "SELECT * FROM ". OrgTaskStatuses::$table . " ts"
                . " WHERE ts.org_id = $org_id";
        $sql .=  (($internal_statuses!==null) ? " AND ts.internal_status IN('".join($internal_statuses,"','")."')" : '');
        $sql .=  (($task_statuses !== null) ? " AND FIND_IN_SET(ts.status, '$task_statuses')" : "");
        return self::$db->query($sql);
    }
}

class TaskReminder extends DBRow{
    public static $table = "task_reminder";
    public static $row = array(
        'id' => '0',
        'task_id' => '0',
        'org_id' => '',
        'created_by' => '',
        'remindee_id' => '',
        'time' => '',
        'template' => "''"
        );
}

class TaskUpdateLog extends DBRow{
    public static $table = "task_update_log";
    public static $row = array(
        'id' => '0',
    	'org_id' => '0',
    	'store_id' => '0',
        'task_id' => '0',
    	'task_entry_id' => '0',
        'customer_id' => '',
        'updated_by' => '',
        'updated_status' => "''",
    	'updated_time' => 'NOW()'
        );
}
//class Base{}
//class Dbase{}

class TaskMgr extends Base{

    private $db;
    private $db_master;
    private $org_id;
    private $org;
    private $logged_in_store_id;
    private $logged_in_store;
    private $logger;
    private $store_id;
	private $storeController;
	private $not_registered_customers;
	
    
    function __construct( ) {
        global $logger, $currentorg, $currentuser;
        parent::__construct();
        $this->db = new Dbase('users');
        $this->db_master = new Dbase('masters');
        $this->logger = $logger;

        $this->org = $currentorg;
        $this->org_id = $currentorg->org_id;//0
        $this->logged_in_store = $currentuser;
        $this->logged_in_store_id = $currentuser->user_id;
        
        try
        {
	        $this->storeController = new ApiStoreController();
	        $base_store_id = $this->storeController->getBaseStoreId();
	        $this->store_id = $base_store_id;
        }
        catch(Exception $e)
        {
        	$this->logger->debug("Base Store is not loaded, replacing base store id as -1");
        	$this->store_id = -1;
        }
    }

    function createMemo($row){
        return $this->createTask($row, true);
    }

    function createTask($row, $memo=false){
    	
        $row["title"] = Util::mysqlEscapeString($row["title"]);
        $row["body"] = Util::mysqlEscapeString($row["body"]);
        $row["description"] = Util::mysqlEscapeString($row["description"]);
        
    	$this->validateInput($row, $memo);
    	
    	if(isset($row['customer_ids']))
    	{
    		$customer_ids = $row['customer_ids'];
    		unset($row['customer_ids']);
    	}
    	if(isset( $row['selected_audience_groups']))
    	{
    		$selected_audience_group = $row['selected_audience_groups'];
    		unset($row['selected_audience_groups']);
    	}
        // row is assumed till a form is finalized, atrributes of row can be changed according to form
        $task = (($memo === false) ? new Task($row) : Task::createMemo($row));
        $this->logger->debug("Task Map:".print_r($task, true));
        $task_id=$task->save();
        if ($task->customer_target == 1)
        {
            // create customer task status with target_id, task_id, store=base store?
            if(isset($selected_audience_group))
            {
            	$this->logger->debug("going to create a task entries for selected audiance group");
	        	//TODO: need to create one more function that will create a customer task without audiance group
	        	$assoc_ids = explode(",", $task->executable_by_ids);
	        	$assoc_id = $assoc_ids[0];
	            $this->createCustomerTaskStatus($task_id, $selected_audience_group, $task->status, $assoc_id, $task->start_date);
            }
            else if(isset($customer_ids))
            {
            	$this->logger->debug("going to create a task entries for customers");
            	$customer_ids = explode(",", $customer_ids);
            	$assoc_ids = explode(",", $task->executable_by_ids);
            	$assoc_id = $assoc_ids[0];
            	if(count($customer_ids) <= 0)
            		throw new Exception("No Customer Id passed");
            	$this->createCustomerTaskStatusForIndividuals($task_id, $customer_ids, $task->status, $assoc_id, $task->start_date, $row['title'], $row['body']);
            }
        }
        
        elseif(strtolower($task->executable_by_type) == 'store'){
            // create task status for each store id and updated_by = 0
            // if execbyall foreach store: entry for each cashier
            if($task->execute_by_all == 'true')
            {
            	$this->logger->debug("going to create a task entries for stores and cashiers");
            	$this->createTaskStatusForStoresAndCashiers($task_id, $task->executable_by_ids, $task->status, $task->start_date);
            }
            else
            {
            	$this->logger->debug("going to create a task entries for stores");
            	$this->createTaskStatusForStores($task_id, $task->executable_by_ids, $task->status, $task->start_date);
            }
        }
        elseif(strtolower($task->executable_by_type) == 'cashier'){
        	$this->logger->debug("going to create a task entries for cashiers");
        	$this->createTaskStatusForCashiers($task_id, $task->executable_by_ids, $task->status, $task->start_date);
        
        }
        
        return $task_id;
    }
    
    private function validateInput($row, $memo)
    {
    	if(empty($row['created_by_type']))
    	{
    		$this->logger->error("Created By Type is not passed");
    		throw new Exception("Creator Type is Empty");
    	}
    	$type = strtolower($row['created_by_type']);
    	
    	if($type == 'manager')
    	{
    		//validate created_by_ids for Managers.
    		//TODO: NO special validation for now, it will be implemented in future.
    		$id = $row['created_by_id'];
    		$sql = "SELECT id FROM associates WHERE id = $id AND org_id = $this->org_id";
    		$id_from_db = $this->db_master->query_scalar($sql);
    		if($id_from_db <= 0)
    		{
    			$this->logger->error("Creator Id is Invalid: $id");
    			throw new Exception("Invalid Creator id: $id");
    		}	
    	}
    	else if( $type == 'cashier' )
    	{
    		//validate created_by_ids for cashiers.
    		$id = $row['created_by_id'];
    		$sql = "SELECT id FROM associates WHERE id = $id AND org_id = $this->org_id";
    		$id_from_db = $this->db_master->query_scalar($sql);
    		if($id_from_db <= 0)
    		{
    			$this->logger->error("Creator Id is Invalid: $id");
    			throw new Exception("Invalid Creator id: $id");
    		}
    	}
    	else 
    	{
    		$this->logger->error("Creator Type is not Valid: $type");
    		throw new Exception("Creator Type is Invalid: $type");
    	}
    	
    	if(!isset($row['executable_by_ids']) || empty($row['executable_by_ids']))
    	{
    		$this->logger->debug("Invalid executable_by_ids");
    		throw new Exception("Invalid Executable_by_ids");
    	}
    	
    	//Validating Executable_by Ids
    	if(strtolower($row['executable_by_type']) == 'cashier')
    	{
    			//$cashier_ids = explode("," ,$row['executable_by_ids']);
    			$executable_by_ids = $row['executable_by_ids'];
    			//validating cashier if they are valid or not
    			$sql = "
    						SELECT id FROM associates 
    							WHERE id IN ( $executable_by_ids ) 
    							AND org_id = $this->org_id
    					";
    	}
    	else if(strtolower($row['executable_by_type']) == 'store')
    	{
    		$executable_by_ids = $row['executable_by_ids'];
    		
    		//validating stores if they are valid or not.
    		$sql = "
    				SELECT id FROM org_entities
			    		WHERE id IN ( $executable_by_ids )
			    		AND org_id = $this->org_id
			    		AND type = 'STORE'
    			";	
    	}
    	else 
    	{
    		$this->logger->error("Invalid Executable By Type: ".$row['executable_by_type']);
    		throw new Exception("Invalid Executable By Type");
    	}
    	
    	$executable_by_ids_from_db = $this->db_master->query($sql);
    	$executable_by_count_from_db = count($executable_by_ids_from_db);
    	if(count($executable_by_ids) != $executable_by_count_from_db)
    	{
    		$arr_executable_by_id_from_db = array();
    		if($executable_by_count_from_db == 0)
    		{
    			throw new Exception("Invalid Executable By Id");
    		}

    		foreach($executable_by_ids_from_db as $row)
    		{
    			$arr_executable_by_id_from_db[] = $row['id'];
    		}
    		foreach($cashier_ids as $temp_id)
    		{
    			if(!in_array($temp_id, $arr_executable_by_id_from_db))
    				throw new Exception("Executable By Id '$temp_id' is invalid");
    		}
    	}
    	
    	
    	if($row['is_memo'])
    	{
    		// Validate Necessary Fields for the Memo.
    	}
    	
		//Validate For Normal Cashier Task
	
		//Validate For Customer Task
    	if( isset($row['customer_target']) && $row['customer_target'] && isset($row['customer_ids']))
    	{
    			$customer_ids = array();
    			$registered_customers = array();
    			$this->logger->debug("Validating Customer Ids");
    			$customer_ids_arr = explode(",", $row['customer_ids']);
    			
    			foreach( $customer_ids_arr as $id )
    			{
    				$customer_ids[] = intval($id);
    			}
    			
    			$customer_controller =  new ApiCustomerController();
    			$this->logger->debug(" Obtaining the registered customers from the Customer Ids");
    			$registered_customer_list = $customer_controller->getRegisteredCustomersFromList($customer_ids);
    			
    			if(count($registered_customer_list) == 0)
    			{
    				$this->logger->error( "None of the customers are registered" );
    				throw new Exception('ERR_TASK_CUSTOMER_NOT_REGISTERED');
    			}
    			
    			$not_registered_customers = array_diff($customer_ids, $registered_customer_list);
    			
    			if(count($not_registered_customers) != 0)
    			{		
    					$this->logger->debug("Some of the customers are not registered");
    					$row['customer_ids'] = $registered_customer_list;
    					$this->not_registered_customers = $not_registered_customers;
    					
    			}
    	}
    	
    }

    function updateTask($task_id, $row){
        $task = Task::getFromId($task_id, $this->org_id);
        $task->updateRow($row);
        return $task->save();
    }


    function createReminder($row){

    }

    //store id, cashier id, possibly customer id
    function updateTaskStatus($task_id, $status, $cashier_id, $customer_id=null, $storeId = null){
        //
        $this->logger->debug("fetching task with org_id $this->org_id and task_id: $task_id");
        $task = Task::getFromId($task_id, $this->org_id);
        global $currentuser;
        $this->logger->debug("Update Task Status Param: $task_id, $status, $cashier_id, $customer_id");
        $sts = OrgTaskStatuses::getStatuses($this->org_id, null, $status);
        if($task->customer_target == 1 && $customer_id<1)
            throw new InvalidParamException("customer_id not supplied for customer_task");
		$status_id = $sts[0]['id']; 
        if(count($sts) == 0 || $sts[0]['status'] != $status || 
        		strpos(",$task->statuses,", ",$status_id,") === false)
            throw new InvalidParamException("$status is not a valid state for task $task_id");
        
        $storeController = new ApiStoreController();
        if (empty($storeId)) {
            $store_id = $storeController -> getBaseStoreId();
        } else {
            $stores = $storeController -> getByIds(array($storeId));
            if (empty($stores)) {
                throw new InvalidParamException("'$storeId' is not a valid store");
            }
            $store_id = $stores [0] ['id'];
        }
        
        //$store_id = $this->getStoreIdFromCashier($cashier_id);
        $task_status = $task->getStatusObject($store_id, $cashier_id, $customer_id);
        
        $task_status->status = $sts[0]['id'];
		$task_status->updated_by = $cashier_id;
		$task_status->updated_by_till_id = $currentuser->user_id;
		$task_status->executer_id = $cashier_id;
		$task_status->updated_on = 'NOW()';
        
        //var_dump($task_status);
        // TODO: close task status if all closed
        $retval = $task_status->save();
        $log_row = array('org_id' => $this->org_id , 'store_id' => $store_id, 'task_id'=>$task_id, 'updated_by' => $cashier_id,
                         'updated_status'=>$sts[0]['id'], 'customer_id'=> ($customer_id ?: "null"), 'updated_time' => 'NOW()', "task_entry_id" => $task_status->id);
        $task_log = new TaskUpdateLog($log_row);
        $task_log->save();
        return $retval;
    }

    private function createTaskStatusForStores($task_id, $store_ids, $default_status, $created_on = null){
        $status_sqls = array();
        global $currentuser;

        foreach (explode(",", $store_ids) as $store_id) { // foreach store selected

            $fields = array('org_id' => $this->org_id,'task_id'=>$task_id, 'store_id'=>$store_id, 'status'=>$default_status
            		, "updated_by_till_id" => $currentuser->user_id);
            if($created_on != null)
            	$fields['created_on'] = "'$created_on'";
            $status = new TaskStatus($fields);
            $status_sqls[] = $status->insertSqlFragment();
        }

        TaskStatus::insertValues($status_sqls);
    }


    private function createTaskStatusForStoresAndCashiers($task_id, $store_ids, $default_status, $created_on = null){
		$this->logger->debug("storeids: $store_ids");
		global $currentuser;
        foreach (explode(",", $store_ids) as $store_id) { // foreach store selected
            $start_id = 0;
            $batch_size = 1000;
            while(true){

                $status_sqls = array();
                $cashier_ids=$this->getCashierIdsFromStore($start_id, $batch_size, $store_id);
                if(count($cashier_ids) <= 0) break;

                foreach ($cashier_ids as $cashier_id) { // foreach cashier in current batch of store
                    $start_id = $cashier_id;
                    $fields = array('org_id' => $this->org_id,'task_id'=>$task_id, 'store_id'=>$store_id, 'executer_id'=>$cashier_id, 'status'=>$default_status
                    		, "updated_by_till_id" => $currentuser->user_id);
                    if($created_on != null)
                    	$fields['created_on'] = "'$created_on'";
                    $status = new TaskStatus($fields);
                    $status_sqls[] = $status->insertSqlFragment();
                }

                TaskStatus::insertValues($status_sqls);
            }
        }
    }
    
    private function createTaskStatusForCashiers($task_id, $cashier_ids, $default_status, $created_on = null){
    	   		 
    	$status_sqls = array();
    	global $currentuser;
    	$cashier_ids = explode(",", $cashier_ids);
    	$this->logger->debug("Cashiers Ids: ", print_r($cashier_ids, true));
    	foreach ($cashier_ids as $cashier_id) { // foreach cashier in current batch of store
    		$start_id = $cashier_id;
    		$store_id = $this->getStoreIdFromCashier($cashier_id);
    		$fields = array('org_id' => $this->org_id,'task_id'=>$task_id, 'store_id'=>$store_id, 'executer_id'=>$cashier_id, 'status'=>$default_status
    				, "updated_by_till_id" => $currentuser->user_id);
    		if($created_on != null)
    			$fields['created_on'] = "'$created_on'";
    		$this->logger->debug("Fields to insert statuses are: ".print_r($fields,true));
    		$status = new TaskStatus($fields);
    		$status_sqls[] = $status->insertSqlFragment();
    	}

    	TaskStatus::insertValues($status_sqls);
    }
    

    private function createCustomerTaskStatus($task_id, $selected_audience_groups, $status_id, $assoc_id, $created_on = null){

    	global $currentuser;
    	$start_id = 0;
        $batch_size = 1000;
        //just a loop in here will do
        if( !is_array( $selected_audience_groups ) )
        	$selected_audience_groups = array( $selected_audience_groups );
        	
        $group_handler = array();
        foreach ( $selected_audience_groups as $group_id ){
        		
        	if( $group_id < 1 ) continue;

        	if( !isset( $group_handler[$group_id] ) ){

        		$C_campaign_group_handler = new CampaignGroupBucketHandler($group_id);
        		$group_handler[$group_id] = $C_campaign_group_handler;
        	}
        	
        	$C_campaign_group_handler = $group_handler[$group_id];
        	while(true){
        	
        		$status_sqls = array();
        		$customers = $this->getCustomerIdsFromAudience($start_id, $batch_size, $C_campaign_group_handler);
        		if(count($customers) <= 0) break;
        	
        		foreach ($customers as $customer) {
        			$start_id = $customer['id'];
        			//TODO: Add Customer ID validation, if customer id is not valid then throw Error.
        			$fields = array('org_id' => $this->org_id, 'store_id' => $this->store_id, 'task_id'=>$task_id,
        					'customer_id'=>$customer['user_id'], "status" => $status_id, "updated_by" => $assoc_id
        					, "updated_by_till_id" => $currentuser->user_id); // base store??: TODO
        			if($created_date != null)
        				$fields['created_on'] = "'$created_on'";
        			$status = new CustomerTaskStatus($fields);
        			$status_sqls[] = $status->insertSqlFragment();
        		}
        		CustomerTaskStatus::insertValues($status_sqls);
        		//CustomerTaskStatus::generateInsertSql($status_sqls); //qry
        	}
        }
    }
    
    /**
     * 
     * @param unknown_type $task_id
     * @param unknown_type $customer_ids will be array of Customer ids
     */
    private function createCustomerTaskStatusForIndividuals($task_id, $customer_ids, $status_id, $assoc_id, $created_on = null, $title, $body){
    	global $currentuser;
    	$start_id = 0;
    	$batch_size = 1000;
        $title = Util::mysqlEscapeString($title);
        $body = Util::mysqlEscapeString($body);
    	$status_sqls = array();
    	foreach ($customer_ids as $customer_id) {
    		//TODO: org_id, store_id
    		$fields = array('org_id' => $this->org_id, 'store_id' => $this->store_id, 'task_id'=>$task_id, 
    				'customer_id'=>$customer_id, "status" => $status_id, "updated_by" => $assoc_id
    				, "updated_by_till_id" => $currentuser->user_id, 'title' => "'$title'", 'body' => "'$body'"); // base store??: TODO
    		if($created_on != null)
    			$fields['created_on'] = "'$created_on'";
    		$status = new CustomerTaskStatus($fields);
    		$status_sqls[] = $status->insertSqlFragment();
    	}
    
    	CustomerTaskStatus::insertValues($status_sqls);
    	//CustomerTaskStatus::generateInsertSql($status_sqls);
    }

    private function getCustomerIdsFromAudience($start_id, $batch_size, 
    		CampaignGroupBucketHandler $C_campaign_group_handler)
    {
    	return $C_campaign_group_handler->getCustomerListByLimit($start_id, $batch_size);
    }

    private function getCashierIdsFromStore($start_id, $batch_size, $store_id){

   		$associate_controller = new ApiAssociateController();
   		$associates = $associate_controller->getAssociatesByStoreId($store_id, $start_id, $batch_size);
    	$ids = array();
   		if($associates && count($associates) > 0)
   		{
	    	foreach($associates as $associate)
	    	{
	    		$ids[] = $associate['id'];
	    	}
   		}
    	
    	return $ids;
    }
    
    private function getStoreIdFromCashier($cashier_id)
    {
    	$sql = "SELECT store_id FROM associates WHERE id = $cashier_id AND org_id = $this->org_id";
    	$master_db = new Dbase("masters");
    	return $master_db->query_scalar($sql);
    }
    
    public function getNotRegisteredCustomers()
    {
    	return $this->not_registered_customers;
    }
}
?>
