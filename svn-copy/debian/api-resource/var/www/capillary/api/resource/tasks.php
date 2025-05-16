<?php

require_once "resource.php";
require_once "apiHelper/Task.php";
require_once "apiController/ApiStoreTaskController.php";
/**
 * Handles all the Task related 
 * work and api's 
 *
 * @author pigol
 */

class TaskResource extends BaseResource{

	function __construct()
	{
		parent::__construct();
	}


	public function process($version, $method, $data, $query_params, $http_method)
	{
		if(!$this->checkVersion($version))
		{
			$this->logger->error("Unsupported Version : $version");
			$e = new UnsupportedVersionException(ErrorMessage::$api['UNSUPPORTED_VERSION'], ErrorCodes::$api['UNSUPPORTED_VERSION']);
			throw $e;
		}

		if(!$this->checkMethod($method)){
			$this->logger->error("Unsupported Method: $method");
			$e = new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
			throw $e;
		}
		
		$result = array();
		try{
	
			switch(strtolower($method)){

				case 'get' :
					$result = $this->get( $query_params );
					break;
								
				case 'metadata':
					$result = $this->metadata($query_params, $version);
					break;
					
				case 'add':
						$result = $this->add($data);
						break;
						
				case 'update':
						$result = $this->update($data, $query_params);
						break;
				
				case 'log':
						$result = $this->log($query_params);
						break;
						
				case 'statusmapping':
					$result = $this->statusMapping($data, $query_param, $http_method);
					break;
										
				case 'memo':
					$result = $this->memo($data, $query_params, $http_method);
					break;
					
				case 'reminder':
					$result = $this->reminder($data, $query_params, $http_method);
					break;
					
				default :
					$this->logger->error("Should not be reaching here");
						
			}
		}catch(Exception $e){ //We will be catching a hell lot of exceptions as this stage
			$this->logger->error("Caught an unexpected exception, Code:" . $e->getCode()
			. " Message: " . $e->getMessage()
			);
			throw $e;
		}
			
		return $result;
	}

	private function get( $query_params)
	{		
		global $gbl_item_status_codes;
		$api_status_code = "SUCCESS";
		$item_status_code = "ERR_TASK_GET_SUCCESS";

		$all = true;
		$assoc_ids = array();
		$batch_size = 50;
		
		try{
			
			if(isset($query_params['all']))
			{
				$all = (boolean) $query_params['all'];
			}
			
			if(isset($query_params['start_date']))
			{
				$start_date_timestamp = Util::deserializeFrom8601($query_params['start_date']);
				if(!empty($start_date_timestamp))
					$start_date = Util::getMysqlDateTime($start_date_timestamp);
			}
			if(isset($query_params['end_date']))
			{
				$end_date_timestamp = Util::deserializeFrom8601($query_params['end_date']);
				if(!empty($end_date_timestamp))
					$end_date = Util::getMysqlDateTime($end_date_timestamp);
			}
			if(isset($query_params['count']))
			{
				$batch_size = (integer) $query_params['count'];
			}
			if(isset($query_params['customer_id']) && $query_params['customer_id'] > 0)
			{
				$customer_ids = explode(",", $query_params['customer_id']) ;
			}
			if(isset($query_params['assoc_id']) && strlen($query_params['assoc_id']) > 0)
			{
				$assoc_ids = explode(",", $query_params['assoc_id']);
			}
			
			if(isset($query_params['include_completed']) &&
					strtolower($query_params['include_completed']) == 'true')
			{
				$include_completed_tasks = true;
			}
			else
				$include_completed_tasks = false;
			
			$tasks = array();
			
			$taskController = new ApiStoreTaskController();
			$this->logger->debug("CustomerIds1:".print_r($customer_ids, true));
			$temp_tasks = $taskController->getTasks( $all, $assoc_ids, $start_date, 
					$end_date, $batch_size, $customer_ids, $include_completed_tasks);
			
		
			$tasks = array();
			
			if($temp_tasks && is_array($temp_tasks))
			{
			
				foreach($temp_tasks as $item)
				{
					$tasks[] = array(
										'id' => $item['id'],
										'type' => $item['type'],
										'entry_id' => $item['entry_id'],
										'associate_id' => $item['associate_id'],
										'associate_name' => $item['associate_name'],
										'title' => $item['title'],
										'body' => $item['body'],
										'created_on' => $item['created_on'],
										'customer_id' => $item['customer_id'],
										'store_id' => $item['store_id'],
										'updated_by_till' => $item['updated_by_till'],
										'status' => $item['status'],
                                                                                'description' => $item['description'],
                                                                                'valid_days_from_create' => $item['valid_days_from_create']
									); 
				}
			}else{
					
					$this->logger->debug( "Task Get: No Tasks Found" );
					throw new Exception( "ERR_NO_TASK_FOUND" );
			}
				
			//unset($temp_tasks);
			if($tasks && count($tasks) > 0)
				$tasks = array( 'task' => array_values($tasks) );
			
		}catch(Exception $e)
		{
			
			$this->logger->error("TasksResource::get() Exception ".$e->getMessage());
			$item_status_code = $e->getMessage();
			$override_error_message="";
			
			if(!isset(ErrorCodes::$tasks[$item_status_code]))
			{
				$this->logger->error("$item_status_code is not defined as Error Code making it more generic");
				$override_error_message = $item_status_code;
				$item_status_code = 'ERR_TASK_GET_FAILURE';
					
			}
			$item['item_status'] = array(
					"success" => ErrorCodes::$tasks[$item_status_code] !=
					ErrorCodes::$tasks['ERR_TASK_GET_FAILURE'] ? true : false,
					"code" => ErrorCodes::$tasks[$item_status_code],
					"message" => empty($override_error_message) ?
					ErrorMessage::$tasks[$item_status_code] : $override_error_message
			);
			$gbl_item_status_codes = $item['item_status']['code']; 
			array_push($tasks, $item);
		}
		
		$api_status = array(
						"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api['SUCCESS'] ? true : false,
						"code" => ErrorCodes::$api[$api_status_code],
						"message" => ErrorMessage::$api[$api_status_code]				
					);
		
		return array(
					"status" => $api_status,
					"tasks" => $tasks
				);
	}
	
	/**
	 * TODO: should it always return success as api_status?
	 * 
	 * @param unknown_type $query_params
	 * @return 
	 */
	private function metadata($query_params, $version)
	{
		$api_status_code = "SUCCESS";
		
		if(isset($query_params['assoc']))
		{
			$assoc = strtolower($query_params['assoc']); 
		}
		if(isset($query_params['action_type']))
		{
			$action_type = strtolower($query_params['action_type']);
		}
		if(isset($query_params['created_by']))
		{
		 	$created_by = $query_params['created_by'];
		}
		if(isset($query_params['created_by_type']))
		{
			$created_by_type = strtolower($query_params['created_by_type']);
		} 
		if(isset($query_params['execute_by_all']))
		{
			$execute_by_all =  ( strtolower($query_params['execute_by_all']) === 'true' );
		}
		if(isset($query_params['customer_task']))
		{
			$customer_task = (strtolower($query_params['customer_task']) === 'true' );
		}
		if(isset($query_params['start_id']))
		{
			$start_id =  $query_params['start_id'];
		}
		if(isset($query_params['end_id']))
		{
			$end_id = $query_params['end_id'];
		}
		if(isset($query_params['start_date']))
		{
			$start_date_timestamp = Util::deserializeFrom8601($query_params['start_date']);
			if(!empty($start_date_timestamp))
				$start_date = Util::getMysqlDateTime($start_date_timestamp);
		}
		if(isset($query_params['end_date']))
		{
			$end_date_timestamp = Util::deserializeFrom8601($query_params['end_date']);
			if(!empty($end_date_timestamp))
				$end_date = Util::getMysqlDateTime($end_date_timestamp);
		}
		if(isset($query_params['batch_size']))
		{
			$batch_size = (integer)$query_params['batch_size'];
		}
		
		if(isset($query_params['include_completed']) &&
				strtolower($query_params['include_completed']) == 'true')
		{
			$include_completed_task = true;
		}
		else 
		{
			$include_completed_task = false;
		}
		
	$taskController = new ApiStoreTaskController();
		$tasks_arr = $taskController->getTasksMetadata($assoc , $action_type, $created_by, 
    									$created_by_type, $execute_by_all, $customer_task,
    									$start_id, $end_id, $start_date, $end_date, $batch_size,
										$include_completed_task);
		$new_items = array();
		$possible_statuses = $taskController->getStatusMappingInHash('id', array('label','value') );
		$this->logger->debug("Possible Statuses for this org is: ".print_r($possible_statuses, true));
		if($tasks_arr && is_array($tasks_arr))
		{
			foreach($tasks_arr as &$item)
			{
				$new_item = array();
				$possible_status = $item['possible_statuses'];
				$ids = explode(",", $possible_status);
				$statuses = array();
				foreach($ids as $id)
				{
					$temp_status = array();
					$temp_status['label'] = $possible_statuses[$id]['label'];
					$temp_status['value'] = $possible_statuses[$id]['value'];
					$statuses[] = $temp_status;
				}
				$new_item['id'] = $item['id'];
				$new_item['title'] = $item['title'];
				$new_item['body'] = $item['body'];
				$new_item['start_date'] = $item['start_date'];
				$new_item['end_date'] = $item['end_date'];
				$new_item['expiry_date'] = $item['expiry_date'];
				$new_item['action'] = array();
				$new_item['action']['type'] = $item['action_type'];
				$new_item['action']['template'] = array("@cdata" => $item['action_template']);
				$new_item['task_type'] = $item['task_type'];
				$new_item['cashier_task'] = ((integer)$item['cashier_task']) == 1 ? true : false  ;
				$new_item['customer_task'] = ((integer)$item['customer_task']) == 1 ? true : false  ;
				$new_item['execute_by_all'] = $item['execute_by_all'];
				$new_item['creator'] = array();
				$new_item['creator']['type'] = $item['created_by_type'];
				$new_item['creator']['id'] = $item['created_by_id'];
				$new_item['creator']['name'] = $item['created_by_name'];
				$new_item['possible_statuses'] = array();
				$new_item['possible_statuses']['status'] = $statuses;
				$new_item['tags'] = array("@cdata" => $item['tags'] );
				if($version == 'v1.1')
				{
					$new_item['description'] = $item['description'];
					$new_item['valid_days_from_create'] = $item['valid_days_from_create'];
				}
				
				$new_items[] = $new_item;
			}
			$tasks = array("task" =>  $new_items);
		}
		
		$api_status = array(
						"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api['SUCCESS'] ? true : false,
						"code" => ErrorCodes::$api[$api_status_code],
						"message" => ErrorMessage::$api[$api_status_code]				
					); 
		
		return array(
					"status" => $api_status,
					"tasks" => $tasks
				);
	}
	
	private function add($data)
	{
		
		$api_status_code = "SUCCESS";
		$tasks = $data['root']['task'];
		$return_tasks = array();
        global $error_count, $gbl_item_status_codes, $gbl_item_count;
        $arr_item_status_codes = array();
        $error_count = 0;
        $gbl_item_count = count($tasks);
		foreach ($tasks as $task)
		{
			$item_status_code = "ERR_TASK_ADD_SUCCESS";
			$item = array();
			try
			{
				$taskController = new ApiStoreTaskController();
				$id = -1;
				if(isset($task['id']))
					$id = (integer) $task['id'];
				
				if($id > 0)
				{
					$this->logger->debug("Task Id is $id, trying to update Task Entries");
					$response_item = $task;
					if(isset($response_item['reminder']))
						unset($response_item['reminder']);
					if( $id > 0 && isset($task['entries']) && isset($task['entries']['entry']) && is_array($task['entries']['entry']))
					{
						unset($response_item['entries']);
						$response_item['entries'] = array();
						$response_item['entries']['entry'] = array();
						if(isset($task['entries']['entry']['status']))
						{
							$task['entries']['entry'] = array( $task['entries']['entry'] );
						}
						$this->logger->debug("trying to update entries for the task id $id");
						
						foreach($task['entries']['entry'] as $entry)
						{
							$temp_entry['task_id'] = $id;
							$temp_entry['status'] = $entry['status'];
							$temp_entry['customer_id'] = $entry['customer_id'];
							$temp_entry['associate_id'] = $entry['associate_id'];
							try{
								$success = $taskController->updateEntryStatus($temp_entry);
								if($success)
									$entry_id = $entry['entry_id'];
							}catch(Exception $e)
							{
								$entry_id = -1;
								$this->logger->error("Status Update Error: ".$e->getMessage());
								$entry['entry_id'] = -1;
							}
							$response_item['entries']['entry'][] = $entry;
						}
					}
				}
				else 
				{
					$this->logger->debug("Task Id is $id, trying to Add Task");
					$response_item = $task;
					$id = $taskController->addTask($task);
					$this->logger->debug("Task Id is $id, Going to Fetch Task Entries");
					$response_item['entries'] = array();
					if($id > 0)
					{
						$response_item['entries']['entry'] = $taskController->getTaskEntriesByTaskId($id);
						if(isset($response_item['entries']['entry']) && !$response_item['entries']['entry'])
							unset($response_item['entries']['entry']);
						$associate_controller = new ApiAssociateController();
						$associate_details = $associate_controller->getAssociateDetailById($task['creator']['id']);
						$response_item['creator']['name'] = $associate_details['firstname'] 
															. ' ' . $associate_details['lastname'];
					}
					
					if(isset($response_item['reminder']))
						unset($response_item['reminder']);
					//TODO: need to add reminders tag.
					if($id > 0)
					{
						$reminders = $taskController->getReminders($id);
						if($reminders && is_array($reminders))
						{
							$this->logger->debug("reminders found");
							foreach($reminders as $reminder)
							{
								$temp_reminder['id'] = $reminder['id'];
								$temp_reminder['time'] = $reminder['time'];
								$temp_reminder['created_by'] = $reminder['created_by'];
								$temp_reminder['template'] = array("@cdata" => $reminder['template']);
								$temp_reminder['remindee_id'] = $reminder['remindee_id'];
								$response_item['reminders']['reminder'][] = $temp_reminder;
							}
						}
					}
				}
				
				$response_item['id'] = $id;
				$item = array_merge($item, $response_item);
			}
			catch(Exception $e)
			{
				//for now if task addition fails, it will return that task with same info as inputed task.
				
				$item = $task;
				$item['id'] = -1;
				$error_count++;
				$this->logger->error("TasksResource::add() Exception ".$e->getMessage());
				
				$item_status_code = $e->getMessage();
				$override_error_message="";
				if(!isset(ErrorCodes::$tasks[$item_status_code]))
				{
					$this->logger->error("$item_status_code is not defined as Error Code making it more generic");
					$override_error_message = $item_status_code;
					$item_status_code = 'ERR_TASK_ADD_FAILURE';
					
				}
			}
			$notRegisteredCustomers = $taskController->getNotRegisteredCustomers();
			
			if( count( $notRegisteredCustomers ) > 0 )
			{
				$item_status_code = 'ERR_TASK_SOME_CUSTOMERS_NOT_REGISTERED';
			}
			$item['item_status'] = array(
									"success" => (ErrorCodes::$tasks[$item_status_code] ==
											ErrorCodes::$tasks['ERR_TASK_ADD_SUCCESS'] || ErrorCodes::$tasks['ERR_TASK_SOME_CUSTOMERS_NOT_REGISTERED']) ? true : false,
									"code" => ErrorCodes::$tasks[$item_status_code],
									"message" => empty($override_error_message) ? 
											ErrorMessage::$tasks[$item_status_code] : $override_error_message
								);
			$arr_item_status_codes [] = $item['item_status']['code'];
			array_push($return_tasks, $item);
		}
		 
		if($error_count == count($tasks))
		{
			$api_status_code = "FAIL";
		}
		 else if( $error_count > 0 )
		{
			$api_status_code = "PARTIAL_SUCCESS";
		}
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
						"success" => ErrorCodes::$api[$api_status_code] == 
							ErrorCodes::$api['SUCCESS'] ? true : false,
						"code" => ErrorCodes::$api[$api_status_code],
						"message" => ErrorMessage::$api[$api_status_code]				
					); 
		return array(
					"status" => $api_status,
					"tasks" => array(
								"task" => $return_tasks
							) 
				);
	}
	
	private function update($data, $query_params)
	{		
		$api_status_code = "SUCCESS";
		$tasks = $data['root']['task'];
		
		$return_tasks = array();
        global $error_count,$gbl_item_status_codes, $gbl_item_count;
        $arr_item_status_codes = array();
        $error_count = 0;
        $gbl_item_count = count($tasks);
		foreach ($tasks as $task)
		{
			$item_status_code = "ERR_TASK_UPDATE_SUCCSS";
			$item = array();
			try
			{
				$taskController = new ApiStoreTaskController();
				if(isset($task['entry_id']) && $task['entry_id'] > 0)
				{
					$this->logger->debug("Entry id is ".$task['entry_id'].", trying to update status entry");
					$id = $taskController->updateEntryStatus($task, $assoc_info);
					$item['entry_id'] = $task['entry_id'];
					$item['associate_id'] = $task['associate_id'];
					$item['customer_id'] = $task['customer_id'];
					$item['status'] = $task['status'];
				}
				else
				{
					$this->logger->debug("Entry id is not set or less than 0 , trying to update task");
					$id = $taskController->updateTask($task);
					$item['title'] = $task['title'];
					$item['body'] = $task['body'];
					$item['start_date'] = $task['start_date'];
					$item['end_date'] = $task['end_date'];
					$item['expiry_date'] = $task['expiry_date'];
					$item['action_type'] = $task['action_type'];
					$item['action_template'] = $task['action_template'];
					$item['executable_by_ids'] = $task['executable_by_ids'];
					$item['comment'] = $task['comment'];
					$item['tags'] = $task['tags'];
				}
				$item['id'] = $id;
			}
			catch(Exception $e)
			{
				//for now if task is addition fails, it will return that task with same info as inputed task.
				$item['id'] = $task['id'];
				$error_count++;
				$this->logger->error("TasksResource::update() Exception ".$e->getMessage());
				$item_status_code = $e->getMessage();
				if(!isset(ErrorCodes::$tasks[$item_status_code]))
				{
					$this->logger->debug("'$item_status_code' is not an Error Key, making it generic");
					Util::addApiWarning($item_status_code);
					$item_status_code = "ERR_TASK_UPDATE_FAILURE";
				}
			}
			
			$warning = Util::getApiWarnings();
			if(!empty($warning))
				$warning = ", ".$warning;
			$item['item_status'] = array(
										"success" => ErrorCodes::$tasks[$item_status_code] ==
												ErrorCodes::$tasks['ERR_TASK_UPDATE_SUCCSS'] ? true : false,
										"code" => ErrorCodes::$tasks[$item_status_code],
										"message" => ErrorMessage::$tasks[$item_status_code] . $warning 
											
									);
			$arr_item_status_codes[] = $item['item_status']['code'];
			array_push($return_tasks, $item);
		}
		
		if($error_count == count($tasks))
		{
			$api_status_code = "FAIL";
		}
		if( $error_count > 0 )
		{
			$api_status_code = "PARTIAL_SUCCESS";
		}
		$gbl_item_status_codes = implode("," , $arr_item_status_codes);
		$api_status = array(
							"success" => ErrorCodes::$api[$api_status_code] ==
								ErrorCodes::$api['SUCCESS'] ? true : false,
							"code" => ErrorCodes::$api[$api_status_code],
							"message" => ErrorMessage::$api[$api_status_code]
						);
		
		return array(
				"status" => $api_status,
				"tasks" => array(
						"task" => $return_tasks
				)
		);		
	}
	
	private function log($query_params)
	{
		if(isset($query_params['task_id'])  )
		{
			$task_id = $query_params['task_id'];
		}
		
		if(isset($query_params['task_entry_id'])  )
		{
			$entry_ids = $query_params['task_entry_id'];
		}
		
		$api_status_code = "SUCCESS";
		
		$return_tasks = array();
        global $error_count,$gbl_item_status_codes, $gbl_item_count;
        $arr_item_status_codes = array();
        $error_count = 0;
		if(!empty($entry_ids))
			$entry_ids = explode(",", $entry_ids);
		else 
			$entry_ids = array();
		
		if(count($entry_ids) <= 0 || empty($task_id))
		{
			$this->logger->debug("entry id is not passed");
			throw new Exception(ErrorMessage::$api["INVALID_INPUT"], ErrorCodes::$api["INVALID_INPUT"]);
		}
		$gbl_item_count = count($entry_ids);
		foreach ($entry_ids as $entry_id)
		{
			$item_status_code = "ERR_TASK_FETCH_UPDATE_LOG_SUCCESS";
			$item = array();
			try
			{
				$taskController = new ApiStoreTaskController();
				$result = $taskController->getTaskUpdateLog($task_id, $entry_id);
				
				if($result && is_array($result))
				{
					$item = $result;
				}
				else 
				{
					$this->logger->debug("no task found for $task_id, $entry_id");
					throw new Exception("ERR_NO_TASK_FOUND");
				}
				
			}
			catch(Exception $e)
			{
				$temp_item = array();
				$temp_item['task_id'] = $task_id;
				$temp_item['entry_id'] = $entry_id;
				$error_count++;
				$this->logger->error("TasksResource::update() Exception ".$e->getMessage());
				$item_status_code = $e->getMessage();
				$temp_item['item_status'] = array(
						"success" => ErrorCodes::$tasks[$item_status_code] ==
						ErrorCodes::$tasks['ERR_TASK_FETCH_UPDATE_LOG_SUCCESS'] ? true : false,
						"code" => ErrorCodes::$tasks[$item_status_code],
						"message" => ErrorMessage::$tasks[$item_status_code]
				);
				$arr_item_status_codes [] = $temp_item['item_status']['code'];
				$item = array();
				$item[] = $temp_item;
			}
			
			$return_tasks = array_merge($return_tasks, $item);
		}
		
		if($error_count == count($entry_ids))
		{
			$api_status_code = "FAIL";
		}
		if( $error_count > 0 )
		{
			$api_status_code = "PARTIAL_SUCCESS";
		}
		
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] ==
				ErrorCodes::$api['SUCCESS'] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		if(count($return_tasks) > 0)
		{
			$tasks = array("task" => 
							array("log" => $return_tasks)
						);
		} 
		
		return array(
				"status" => $api_status,
				"tasks" => $tasks
		);
		
	}
	
	private function statusMapping($data, $query_param, $http_method)
	{
		$http_method = strtolower($http_method);
		
		if($http_method == "get")
		{
			$result = $this->getStatusMapping($query_params);
		}
		else if($http_method == "post")
		{
			$result = $this->addStatusMapping($data);
		}
		else 
			$result = array();
		
		return $result;
	}

	private function addStatusMapping($data)
	{
		
		$api_status_code = "SUCCESS";
		
		$taskController = new ApiStoreTaskController();
		
		$statuses = $data['root']['status'];
		
		$task_mappings = array();
        global $error_count, $gbl_item_status_codes;
        $arr_item_status_codes = array();
        $error_count = 0;
		foreach($statuses as $status)
		{
			$item = array();
			$item_status_code = "ERR_TASK_STATUS_ADDITION_SUCCESS";
			try
			{
				$internal_status = $status['value'];
				$external_status = $status['label'];
				$id = $taskController->addStatusMapping($internal_status, $external_status);
				
				if($id <= 0)
					throw new Exception("ERR_TASK_STATUS_ADDITION_FAIL");
				$item['id'] = $id;
			}
			catch(Exception $e)
			{
				$item['id'] = -1;
				$this->logger->error("Error while adding StatusMapping ".$e->getMessage());
				$item_status_code = $e->getMessage();
				$error_count++;
			}
			$item = array_merge($item, $status);
			$item['item_status'] = array(
					"success" => ErrorCodes::$tasks[$item_status_code] ==
						ErrorCodes::$tasks['ERR_TASK_STATUS_ADDITION_SUCCESS'] ? true : false,
					"code" => ErrorCodes::$tasks[$item_status_code],
					"message" => ErrorMessage::$tasks[$item_status_code]
			);
			$arr_item_status_codes [] = $item['item_status']['code'];
			array_push($task_mappings, $item);
		}
		
		if($task_mappings)
			$task_mappings = array("status" => $task_mappings);
		
		if($error_count == count($statuses))
		{
			$api_status_code = "FAIL";
		}
		if( $error_count > 0 )
		{
			$api_status_code = "PARTIAL_SUCCESS";
		}
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] ==
					ErrorCodes::$api['SUCCESS'] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		); 
		
		return array(
				"status" => $api_status,
				"tasks" =>array(
							"task_statuses" => $task_mappings
						)
		);
			
	}
	
	private function getStatusMapping($query_params)
	{
		$api_status_code = "SUCCESS";
		
		$taskController = new ApiStoreTaskController();
		
		$task_mappings = $taskController->getStatusMapping();
		
		if($task_mappings)
			$task_mappings = array("status" => $task_mappings);
		
		$api_status = array(
							"success" => ErrorCodes::$api[$api_status_code] ==
									ErrorCodes::$api['SUCCESS'] ? true : false,
							"code" => ErrorCodes::$api[$api_status_code],
							"message" => ErrorMessage::$api[$api_status_code]
						);
		
		return array(
					"status" => $api_status,
					"tasks" => array( "task_statuses" => $task_mappings)
				);
	}
	
	private function memo($data, $query_params, $http_method)
	{
		$result = array();
		$http_method = strtolower($http_method);
		if($http_method == 'get')
		{
			$this->logger->debug("GET Request found, Trying to get the memos");
			$result = $this->getMemo($query_params);
		}
		else if($http_method == 'post')
		{
			$this->logger->debug("POST Request found, Trying to add the memos");
			$result = $this->addMemo($data);
		}
		
		return $result;
	}
	
	private function getMemo($query_params)
	{
		$taskController = new ApiStoreTaskController();
		return array();
	}
	
	private function addMemo($data)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = "SUCCESS";
		
		$taskController = new ApiStoreTaskController();
		
		$memos = $data['root']['memo'];
		$return_memos = array();
		
		foreach ($memos as $memo)
		{
			$item_status_code = "ERR_TASK_FETCH_UPDATE_LOG_SUCCESS";
			$item = $memo;
			try
			{
				$taskController = new ApiStoreTaskController();
				$id = $taskController->addMemo($memo);
				$item['id'] = $id;
			}
			catch(Exception $e)
			{
				$item['id'] = -1;
				$error_count++;
				$this->logger->error("TasksResource::update() Exception ".$e->getMessage());
				$item_status_code = $e->getMessage();
			}
			$item['item_status'] = array(
					"success" => ErrorCodes::$tasks[$item_status_code] ==
					ErrorCodes::$tasks['SUCCESS'] ? true : false,
					"code" => ErrorCodes::$tasks[$item_status_code],
					"message" => ErrorMessage::$tasks[$item_status_code]
			);
			$arr_item_status_codes[] = $item['item_status']['code'];
			array_push($return_memos, $item);
		}
		
		if($error_count == count($memos))
		{
			$api_status_code = "FAIL";
		}
		if( $error_count > 0 )
		{
			$api_status_code = "PARTIAL_SUCCESS";
		}	
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] ==
				ErrorCodes::$api['SUCCESS'] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		$return_memos = array( "memo" => $return_memos );
		return array(
				"status" => $api_status,
				"memos" => $return_memos
		);
		
	}
	
	private function reminder($data, $query_params, $http_method)
	{
		$result = array();
		$http_method = strtolower($http_method);
		if($http_method == 'get')
		{
			$this->logger->debug("GET Request found, Trying to get the reminders");
			$result = $this->getReminder($query_params);
		}
		else if($http_method == 'post')
		{
			$this->logger->debug("POST Request found, Trying to add the reminders");
			$result = $this->addReminder($data);
		}
		
		return $result;
	}
	
	private function getReminder($query_params)
	{
		$start_date = '';
		$end_date = '';
		$batch_size = '';
		
		if(isset($query_params['start_date']))
		{
			$start_date_timestamp = Util::deserializeFrom8601($query_params['start_date']);
			if(!empty($start_date_timestamp))
				$start_date = Util::getMysqlDateTime($start_date_timestamp);
		}
		if(isset($query_params['end_date']))
		{
			$end_date_timestamp = Util::deserializeFrom8601($query_params['end_date']);
			if(!empty($end_date_timestamp))
				$end_date = Util::getMysqlDateTime($end_date_timestamp);
		}
		if(isset($query_params['batch_size']))
		{
			$batch_size = (integer)$query_params['batch_size'];
		}
		
		$taskController = new ApiStoreTaskController();
		
		$result = $taskController->getReminders(-1, false, $start_date, $end_date, $batch_size);
		
		$api_status = array(
							"success" => true,
							"code" => '200',
							"message" => 'SUCCESS'
						);
		if($result)
		{
			$reminders = array( "reminder" => $result );
		}
		return array(
						"status" => $api_status,
						"tasks" => array( "reminders" => $reminders ) 
					);
	}
	
	private function addReminder($data)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = "SUCCESS";
		
		$taskController = new ApiStoreTaskController();
		$associateController = new ApiAssociateController();
		
		$reminders = $data['root']['reminder'];
		$return_reminders = array();
		
		foreach ($reminders as $reminder)
		{
			$item_status_code = "ERR_TASK_REMINDER_ADDITION_SUCCESS";
			$item = $reminder;
			
			try
			{
				$taskController = new ApiStoreTaskController();
				$task_id = $reminder['task_id']; 
				$remindee_id =$reminder['remindee_id'];
				$template = $reminder['template'];
				$assoc_id = $reminder['created_by'];
				//$time = $reminder['time'];
				
				$all_associates = $associateController->getAllAssociate();
				$associates = array();
				foreach ( $all_associates as $associate )
				{
					$associates[$associate['id']] = $associate;
				}
				
				$assoc_ids = array_keys($associates);
				
				if( !in_array($assoc_id, $assoc_ids) ){
					
					$this->logger->error( "Invalid created by id: $assoc_id" );
					throw new Exception( "ERR_TASK_CREATED_BY_ID_INVALID" );
				}
				if( !in_array($remindee_id, $assoc_ids) ){
						
					$this->logger->error( "Invalid remindee id: $remindee_id" );
					throw new Exception( "ERR_TASK_REMINDEE_ID_INVALID" );
				}
				
				$reminder_timestamp = Util::deserializeFrom8601($reminder['time']);
				if(!empty($reminder_timestamp))
					$time = Util::getMysqlDateTime($reminder_timestamp);
				//took base store_id of an associate.
				$store_id = $associates[$assoc_id]['store_id'];
				$id = $taskController->addReminder(
						$task_id, $assoc_id, $remindee_id, 
						$template, $store_id, $time);
				if($id <= 0)
					throw new Exception("ERR_TASK_REMINDER_ADDITION_FAIL");
				
				$item['id'] = $id;
				//TODO: add created_by
				$item['created_by'] = $assoc_id;
				
			}
			catch(Exception $e)
			{
				$item['id'] = -1;
				$error_count++;
				$this->logger->error("TasksResource::reminder() Exception ".$e->getMessage());
				$item_status_code = $e->getMessage();
				
				$override_error_message="";
				if(!isset(ErrorCodes::$tasks[$item_status_code]))
				{
					$this->logger->error("$item_status_code is not defined as Error Code making it more generic");
					$override_error_message = $item_status_code;
					$item_status_code = 'ERR_TASK_REMINDER_ADDITION_FAIL';
						
				}
			}
			$item['item_status'] = array(
					"success" => ErrorCodes::$tasks[$item_status_code] ==
					ErrorCodes::$tasks['ERR_TASK_REMINDER_ADDITION_SUCCESS'] ? true : false,
					"code" => ErrorCodes::$tasks[$item_status_code],
					"message" => empty($override_error_message) ?
					ErrorMessage::$tasks[$item_status_code] : $override_error_message
			);
			$arr_item_status_codes [] = $item['item_status']['code'];
			array_push($return_reminders, $item);
		}
		
		if($error_count == count($reminders))
		{
			$api_status_code = "FAIL";
		}
		if( $error_count > 0 )
		{
			$api_status_code = "PARTIAL_SUCCESS";
		}
		$gbl_item_status_codes = implode(",", $gbl_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] ==
				ErrorCodes::$api['SUCCESS'] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		$return_reminders = array( "reminder" => $return_reminders );
		return array(
				"status" => $api_status,
				"tasks" => array("reminders" => $return_reminders)
		);
		
	}
	
	
	public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1','v1.1'))){
			return true;
		}
		return false;
	}

	public function checkMethod($method)
	{
		if(in_array(strtolower($method), 
				array( 'get', 'metadata', 'add', 'update', 
				'log', 'statusmapping', 'memo', 'reminder' )))
		{
			return true;
		}
		return false;
	}

}
