<?php
//TODO: referes to cheetah
require_once 'common.php';
//TODO: referes to cheetah
require_once 'helper/ShopbookLogger.php';
require_once 'apiHelper/Task.php';
require_once "apiModel/class.ApiStoreTaskModelExtension.php";

class ApiStoreTaskController extends ApiBaseController{
	
	private $taskMgr;
	private $db;
	private $storeTaskModelExtension;
	
	public function __construct(){
		
		parent::__construct();
		
		$this->taskMgr = new TaskMgr();
		$this->db = new Dbase("users");
		$this->storeTaskModelExtension = new ApiStoreTaskModelExtension(); 
	}
	
	/**
	 *
	 * @param unknown_type $assoc
	 * @param unknown_type $all
	 * @param unknown_type $start_date
	 * @param unknown_type $end_date
	 * @param unknown_type $batch_size
	 * @return NULL|multitype:
	 */
	public function getTasks( $all = false, $assoc_ids = array(), $start_date ='', $end_date = '', $batch_size = 50, $customer_ids = array(), $status = '', $include_completed_task = false )
	{
		$store_id = null;
		
		if($all === false)
		{
			$C_storeController = new ApiStoreController();
			$store_id = $C_storeController->getBaseStoreId();
		}
		
		return $this->storeTaskModelExtension->getTasks($store_id, $assoc_ids, $start_date, $end_date, $batch_size, $customer_ids, $status, $include_completed_task );
		
	}
	
	public function getTaskEntriesByTaskId($task_id)
	{	
		return $this->storeTaskModelExtension->getTaskEntriesByTaskId($task_id);
	}
	
	//TODO haven't used start_date and end_date for filtering.
	//TODO haven't done executable_by filter
	public function getTasksMetadata($assoc = false , $action_type = '', $created_by = '',
			$created_by_type = '', $execute_by_all = false, $customer_target = '',
			$start_id = '', $end_id = '', $start_date = '', $end_date = '', $batch_size = '',
			$include_completed_task = false)
	{
		return $this->storeTaskModelExtension->getTaskMetadata($assoc, $action_type, $created_by,
			$created_by_type, $execute_by_all , $customer_target,
			$start_id, $end_id, $start_date, $end_date, $batch_size, $include_completed_task);
	}
	
	public function addTask( $task )
	{
		$result = array();
		$id = -1;
		
		//integrated Create Task and Memo in one function, no need of tasks/memo api
		$row = $this->getRowFromTask($task);
		$is_memo = false;
		if(isset($row['is_memo']) && $row['is_memo'])
		{
			$this->logger->debug("creating memo");
			$is_memo = true;
			$id = $this->taskMgr->createMemo($row);
		}
		else
		{
			$this->logger->debug("creating Task");
			$id = $this->taskMgr->createTask($row);
		}
		
		
		if( !$id || $id <=0 )
			throw new Exception("ERR_TASK_ADD_FAILURE");
		
		$this->logger->debug("Task has been created successfully, Task id: $id");
		//reminder will be created ony if reminder->create == true and task is not type of memo
		if(!$is_memo && isset($task['reminder']) && isset($task['reminder']['create']) && strtolower($task['reminder']['create']) == 'true')
		{
			
			//if task is store level, means executable by type is store and executable by all is false
			//then reminders will not be created 
			if( isset($task['executable_by_type']) && strtolower($task['executable_by_type']) == 'store' 
					&& isset($task['execute_by_all']) && strtolower($task['execute_by_all']) == 'false' )
				$this->logger->debug("store level task, not going to create reminders");
			else 
			{
				$this->logger->debug("trying to create Reminders for Task Entries");
				$entries = $this->getTaskEntriesByTaskId($id);
				if(is_array($entries))
				{
					$reminder_last_id = $this->addRemindersForEntries($id, $entries, $task['reminder']['time'], $task['reminder']['template']);
					if( !$reminder_last_id || $reminder_last_id <=0 )
					{
						$this->logger->error("Reminders addition failed (reminder last id: $reminder_last_id)");
						//TODO: throw Exception if reminders are not added successfully
					}
				}
			}
		}
		
		return $id;
	}
	
	public function updateEntryStatus( $data )
	{		
		$id = -1;

		$customer_id = null;
		$status = null;
		$task_id = null;
		
		if(isset($data['id']))
			$task_id = $data['id'];
		if(isset($data['status']))
			$status = $data['status'];
		if(isset($data['customer_id']))
			$customer_id = $data['customer_id'];
		if(isset($data['associate_id']))
			$assoc_id = $data['associate_id'];
		if(isset($data['store_id']))
			$store_id = $data['store_id'];
		
		if (!$status || empty($status))
		{
		 	throw new Exception('ERR_TASK_NO_VALID_STATUS');
		}
			
		if (!$task_id || empty($task_id))
			throw new Exception('ERR_TASK_ID_INVALID');
				
		// TODO: need to specify associ_id as parameter for the updateTaskStatus
		$this->logger->debug("Updating Task Status using task_id: $task_id, status: $status, associate_id: $assoc_id, customer_id: $customer_id");
		global $currentuser;
		if ($currentuser -> getType() === 'ADMIN_USER') {
			if (empty($store_id)) {
				throw new Exception('ERR_TASK_SET_STORE_ID_WHEN_ADMIN_USER');
			} else {
				$success = $this -> taskMgr -> updateTaskStatus($task_id, $status, $assoc_id, $customer_id, $store_id);
			}
		} else {
			$success = $this -> taskMgr -> updateTaskStatus($task_id, $status, $assoc_id, $customer_id);
		}
		
		if($success)
		{
			if($this->isTaskEntriesComplete($task_id))
			{
				$this->closeTaskMetadata($task_id);
			}
			else 
			{
				$this->openTaskMetadata($task_id);
			}
			return $task_id;
		}
		else
			return false;
	}
	
	function isTaskEntriesComplete($task_id)
	{
		$tasks = $this->storeTaskModelExtension->getTaskEntriesByTaskId($task_id, false, 5);
		if(!$tasks || count($tasks) <= 0)
			return true;
		else
			return false;
	}
	
	/**
	 * updates task, that is metadata.
	 * @param unknown_type $task
	 */
	public function updateTask($task)
	{
		$row = $this->getRowFromTask($task, true);
		$id = $this->taskMgr->updateTask($task['id'], $row);
		return $id;
	}
	
	//TODO: not specified yet, table is also need to create.
	public function getTaskUpdateLog($task_id, $task_entry_id)
	{
		$result = array();
		
		if(empty($task_id) || $task_id <= 0)
			throw new Exception("ERR_TASK_ID_INVALID");
		
		if(!$this->isTaskExist($task_id))
			throw new Exception("ERR_TASK_ID_INVALID");
		
		return $this->storeTaskModelExtension->getTaskUpdateLog($task_id, $task_entry_id);
	}
	
	public function addStatusMapping($internal_status, $external_status)
	{
                $external_status = Util::mysqlEscapeString($external_status);
                
		if(empty($internal_status))
		{
			$this->logger->error("Value(Internal Status) is Empty");
			return -1;
		}
		if(empty($external_status))
		{
			$this->logger->error("Label(External Status) is Empty");
			return -1;
		}
		if(count($external_status) > 15)
		{
			throw new Exception("ERR_TASK_NO_VALID_STATUS");
		}
		
		$possible_internal_array = array('OPEN','COMPLETE','NONE');
		if( ! in_array($internal_status, $possible_internal_array ))
		{
			$this->logger->error("$internal_status is not in ".print_r($possible_internal_array, true));	
			throw new Exception("ERR_TASK_INVALID_INTERNAL_STATUS");
		}
		
		$sql = "SELECT id FROM task_statuses 
					WHERE org_id = $this->org_id 
					AND internal_status = '$internal_status'
					AND status = '$external_status'";
		$id = $this->db->query_scalar($sql);
		
		if( $id > 0 )
		{
			$this->logger->debug("Status is already exist, not going to add");
			return $id;
		}
		
		$sql = " 
				INSERT INTO task_statuses (org_id, internal_status, status) 
					VALUES ($this->org_id, '$internal_status', '$external_status')
				";
		
		$id = $this->db->insert($sql);
		return $id > 0 ? $id : -1 ;
	}
	
	public function getStatusMapping()
	{
		return $this->storeTaskModelExtension->getStatusMapping();
	}
	
	public function getStatusMappingInHash($key = 'label', $value = 'value')
	{
		return $this->storeTaskModelExtension->getStatusMappingInHash($key, $value);
	}
	
	public function getReminders($task_id = -1, $only_for_current_store = false, $start_date = '' , $end_date = '' , $batch_size = 10)
	{
		return $this->storeTaskModelExtension->getReminders($task_id , $only_for_current_store , $start_date , $end_date , $batch_size );
	}
	
	public function addReminder($task_id, $assoc_id, $remindee_id, $template, $store_id, $time = '')
	{
		return $this->storeTaskModelExtension->addReminder($task_id, $assoc_id, $remindee_id, $template, $store_id, $time);
	}
	
	public function addRemindersForEntries($task_id, $entries, $time, $template)
	{
		return $this->storeTaskModelExtension->addRemindersForEntries($task_id, $entries, $time, $template);
	}
	
	public function addMemo($memo)
	{
		$row = $this->getRowFromTask($memo);
		$memo_row = Task::createMemo($row);
		$task_id = $this->taskMgr->createMemo($memo_row);
		return $task_id;
	}
	
	public function getMemos($start_date = '', $end_date = '', $batch_size = 10)
	{
		$date_filter = "";
		if(!empty($start_date))
		{
			$date_filter .= " AND time > '$start_date' ";
		}
		if(!empty($end_date))
		{
			$date_filter .= " AND time < '$end_date' ";
		}
		if(!empty($batch_size) && $batch_size > 0)
		{
			$batch_size = (integer) $batch_size;
			$limit_filter = " LIMIT $batch_size";
		}
		//TODO: need to define
		$sql = "";
		
		$result = $this->db->query($sql);
		if($result && count($result) > 0)
			return $result;
		else
			return null;
	}
	
	public function isTaskExist($task_id)
	{
		return $this->storeTaskModelExtension->isTaskExist($task_id);
	}
	
	private function getRowFromTask($task, $is_update = false)
	{
		$row = array();
		$row['org_id'] = $this->org_id;
		if(isset($task['title']))
			$row['title'] = Util::mysqlEscapeString( $task['title'] );
		if(isset($task['body']))
			$row['body'] = Util::mysqlEscapeString( $task['body'] );

		if(isset($task['start_date']))
		{
			$start_date_timestamp = Util::deserializeFrom8601($task['start_date']);
			if(!empty($start_date_timestamp))
				$row['start_date'] = Util::getMysqlDateTime($start_date_timestamp);
			else if( ! $is_update )
				$row['start_date'] = Util::getMysqlDateTime(time());
		}
		if(isset($task['end_date']))
		{
			$end_date_timestamp = Util::deserializeFrom8601($task['end_date']);
			if(!empty($end_date_timestamp))
				$row['end_date'] = Util::getMysqlDateTime($end_date_timestamp);
			else if( ! $is_update )
				$row['end_date'] = Util::getMysqlDateTime( time() + (7 * 24 * 60 * 60));
		}
		if(isset($task['expiry_date']))
		{
			$expiry_date_timestamp = Util::deserializeFrom8601($task['expiry_date']);
			if(!empty($expiry_date_timestamp))
				$row['expiry_date'] = Util::getMysqlDateTime($expiry_date_timestamp);
			else if( !$is_update )
				$row['expiry_date'] = Util::getMysqlDateTime( time() + (14 * 24 * 60 * 60));
		}
		
		/* Task Add, End and Expiry date boundary check begin*/
		if( $start_date_timestamp < $end_date_timestamp ){
			
			$this->logger->debug( "Task Add: Start date less than End date - continue" );
			if( $end_date_timestamp < $expiry_date_timestamp ){
				
				$this->logger->debug( "Task Add: End date less than Expiry date - continue" );
			}
			else{
				
				$this->logger->error( "Task Add: End date not less than Expiry date " );
				throw new Exception("ERR_TASK_EXPIRY_DATE_NOT_IN_BOUNDARY");
			}
		}
		else{
			
			$this->logger->error( "Task Add: Start date not less than End date " );
			throw new Exception("ERR_TASK_END_DATE_NOT_IN_BOUNDARY");
		}
		/* Task Add, End and Expiry date boundary check End*/

		if($task['possible_statuses'])
		{
			$row['statuses'] = $task['possible_statuses'];
		}
		else if(!$is_update)
		{
			$this->logger->debug("Possible statuses not passed.Making a query to obtain all statuses");
			$status_mapping = $this->getStatusMapping();
				
			$status_mapping_count = count( $status_mapping );
			if( $status_mapping_count == 0 )
			{
				$this->logger->error("No status present for the organization ");
				throw new Exception('ERR_TASK_NO_VALID_STATUS');
			}
			else
			{
				$this->logger->debug("Possible statuses for the request".print_r( $status_mapping , true ));
				$mapping = array();
				foreach( $status_mapping as $statuses )
				{
					$mapping[] = $statuses['label'];
				}
			
				$mapping = array_values(array_unique( $mapping ));
				$this->logger->debug("Possible unique statuses for the request".print_r( $status_mapping , true ));
				$row['statuses'] = implode( ',', $mapping);
			}
		}
		
		if(isset($task['tags']))
			$row['tags'] = $task['tags'];

		if(isset($task['type']) && strtolower($task['type']) == 'memo')
			$row['is_memo'] = true;
		
		if(isset($task['target_type']))
		{
			if(strtolower($task['target_type']) == 'customer')
			{     
				$row['customer_target'] = true;
			}
		}
		
		$customer_ids_valid = false;
		if(isset($task['customer_ids']) && !empty($task['customer_ids']))
		{
			$row['customer_ids'] = $task['customer_ids'];
			$customer_ids_valid = true;
		}
		
		if(isset($task['selected_audience_groups']) && !empty($task['customer_ids']))
		{
			$row['selected_audience_groups'] = $task['selected_audience_groups'];
			$customer_ids_valid = true;
		}
		
		// validating customer type tasks.
		if (!$customer_ids_valid)
		{
			if( $row['customer_target'] )
			{
				$this->logger->error("Target type is customer, please give valid customer identifier.");
				throw new Exception("Target Type is customer without customer ids or audiance groups");
			}
		}
		
		if(isset($task['action']))
		{
			if(isset($task['action']['type']))
				$row['action_type'] = $task['action']['type'];
			if(isset($task['action']['template']))
				$row['action_template'] = Util::mysqlEscapeString( $task['action']['template'] );
		}
		
		if(isset($task['creator']))
		{
			if(isset($task['creator']['type']))
				$row['created_by_type'] = $task['creator']['type'];
			if(isset($task['creator']['id']))
			{	
				$row['created_by_id'] = $task['creator']['id'];			//need to remove from request, it should directly take from store_id
				$row['updated_by'] = $task['creator']['id'];
			}
		}
		
		if(isset($task['executable_by_type']))
		{
			$row['executable_by_type'] = strtolower($task['executable_by_type']) != 'store' ? 'cashier' : 'store' ;
		}
		else if( ! $is_update )
			$row['executable_by_type'] = 'cashier';
		
		if(isset($task['executable_by_ids']))
			$row['executable_by_ids'] = $task['executable_by_ids'];
		
		if(isset($task['execute_by_all']))
		{
			$row['execute_by_all'] = strtolower($task['execute_by_all'])  ;
		}
		else if( ! $is_update )
			$row['execute_by_all'] = false;
		
		if(isset($task['associate_id']))
			$row['updated_by'] = $task['associate_id'];
		
		$should_create_reminders = false;
		if(isset($task['reminder']) && isset($task['reminder']['create']) && !empty($task['reminder']['create']))
		{
			if(strtolower($task['reminder']['create']) == 'true')
			{
				$should_create_reminders = true;
				$reminder = $task['reminder'];
				//TODO: validate reminder
			}
		}
		
		if(isset($task['comment']))
			$row['comment'] = $task['comment'];
		if(isset($task['description']))
			$row['description'] = $task['description'];
		if(isset($task['valid_days_from_create']))
			$row['valid_days_from_create'] = $task['valid_days_from_create'];

		return $row;
	}
	
	public function getCurrnetStoreProfile()
	{
		return $this->currentuser;
	}
	
	public function getTaskById( $task_id )
	{
		return Task::getFromId($task_id, $this->org_id);
	}
	
	public function getNotRegisteredCustomers()
	{
		return $this->taskMgr->getNotRegisteredCustomers();
	}
	
	/**
	 * If all tasks entries are closed/completed, 
	 * this will close task metadata as well. 
	 */
	public function closeTaskMetadata($task_id)
	{
		$task = Task::getFromId($task_id, $this->org_id);
		$status_mapping = $this->storeTaskModelExtension->getStatusMappingInHash("id", array("label", "value"));
		$possible_statuses = $task->statuses;
		$possible_statuses_ids = explode(",", $possible_statuses);
		$complete_status_id = null;
		foreach($possible_statuses_ids AS $status_id)
		{
			if($status_mapping[$status_id]['value'] == 'COMPLETE')
			{
				$complete_status_id = $status_id;
				break;
			}
		}
		if($complete_status_id != null && $complete_status_id > 0)
		{
			$row = array("status" => $complete_status_id);
			$task->updateRow($row);
			$updated_success = $task->save();
		}
		else
		{
			$this->logger->error("COMPLETE status is not in possible_statuses of task metadata");
		}
	}
	
	/**
	 * If some of the tasks are yet to complete, 
	 * this will open task metadata as well. 
	 */
	public function openTaskMetadata($task_id)
	{
		$task = Task::getFromId($task_id, $this->org_id);
		$status_mapping = $this->storeTaskModelExtension->getStatusMappingInHash("id", array("label", "value"));
		$possible_statuses = $task->statuses;
		$possible_statuses_ids = explode(",", $possible_statuses);
		$open_status_id = null;
		foreach($possible_statuses_ids AS $status_id)
		{
			if($status_mapping[$status_id]['value'] == 'OPEN')
			{
				$open_status_id = $status_id;
				break;
			}
		}
		if($open_status_id != null && $open_status_id > 0)
		{
			$row = array("status" => $open_status_id);
			$task->updateRow($row);
			$updated_success = $task->save();
		}
		else
		{
			$this->logger->error("OPEN status is not in possible_statuses of task metadata");
		}
	}
}
