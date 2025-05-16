<?php

include_once "apiHelper/Task.php";
define( 'CUSTOMER_TARGET_FALSE' , 0 );
define( 'MEMO_TASK_TRUE', 1 );

class ApiStoreTaskModelExtension extends Task 
{
	private $db_master ;
	private $db_user;
	private $currentorg;
	private $currentuser;
	private $org_id;
	private $logger;
	
	public function __construct(){
		
		$this->db_user = new Dbase( 'users' );
		
		global $currentorg, $currentuser, $logger;
	
		$this->currentorg = $currentorg;
		$this->currentuser = $currentuser;
		$this->org_id = $currentorg->org_id;
		$this->logger = &$logger;
	}	
	
	public function getTasks( $store_id = null, $assoc_ids = array(), $start_date ='', $end_date = '', $batch_size = 50, $customer_ids = array(), $status = '', $include_completed_task = false )
	{
		if( $store_id !== null )
		{
			$store_filter1 = " AND ts.store_id = $store_id ";
			$store_filter2 = " AND tsc.store_id = $store_id ";
		}
		
		if( is_array($assoc_ids) && count($assoc_ids) > 0)
		{
			$str_assoc_ids = implode(",", $assoc_ids);
			$assoc_filter1 = " AND ts.executer_id IN ( $str_assoc_ids ) ";
			$assoc_filter2 = " AND tsc.updated_by IN ( $str_assoc_ids ) ";
		}
			
		$date_filter1 = "";
		$date_filter2 = "";
		if(!empty($start_date))
		{
			$date_filter1 .= " AND t.start_date > '$start_date' ";
			$date_filter2 .= " AND t2.start_date > '$start_date' ";
		}
		if(!empty($end_date))
		{
			$date_filter1 .= " AND t.start_date < '$end_date' ";
			$date_filter2 .= " AND t2.start_date < '$end_date' ";
		}
		if(!empty($batch_size) && $batch_size > 0)
		{
			$batch_size = (integer) $batch_size;
			//$limit_filter = " LIMIT $batch_size";
		}
		else
		{
			$batch_size = 50;
			//$limit_filter = " LIMIT $batch_size";
		}
		
		$include_first_clause = true;
		
		if(count($customer_ids ) > 0)
		{
			$customer_ids_str = implode(",", $customer_ids );
			$customer_filter2 = " AND tsc.customer_id IN( $customer_ids_str )";
			$include_first_clause = false;
		}
		else 
		{
			$customer_filter2 = "";
		}
		
		if( !empty( $status ) ){
			
			$status_filter1 = " AND tss1.status = '$status' ";
			$status_filter2 = " AND tss2.status = '$status' ";
		}
		if(!$include_completed_task)
		{
			$exclude_completed_task_filter1 = " AND tss1.internal_status != 'COMPLETE'";
			$exclude_completed_task_filter2 = " AND tss2.internal_status != 'COMPLETE'";
		}
		
		$sql1 = "
				SELECT * FROM 
				(
					SELECT
						@counter := IF(@temp_task_id != t.id, 1,@counter+1) AS count, 
						@temp_task_id := t.id AS temp_task_id,
						t.id as id,
                                                t.description as description,
                                                t.valid_days_from_create as valid_days_from_create,
						ts.id as entry_id,
						ts.executer_id AS associate_id,
						CONCAT(assoc.firstname , ' ' , assoc.lastname) AS associate_name,
						ts.store_id AS store_id,
						NULL AS customer_id,
						tss1.status as status,
						t.title AS title,
						t.body AS body,
						ts.updated_on as updated_on,
						ts.created_on AS created_on,
						'CASHIER' as type,
						ts.updated_by_till_id AS updated_by_till
					FROM (SELECT @counter := 0, @temp_task_id := 0) AS temp_declare, task AS t
					JOIN task_status AS ts
						ON 		t.id = ts.task_id
						AND t.org_id = ts.org_id
					JOIN task_statuses AS tss1
						ON tss1.id = ts.status
						AND tss1.org_id = ts.org_id
					LEFT JOIN masters.associates AS assoc 
					 	ON assoc.id = ts.executer_id
					 	AND assoc.org_id = ts.org_id
					WHERE t.org_id = $this->org_id
						AND t.expiry_date > NOW()
						$exclude_completed_task_filter1
						$store_filter1
						$assoc_filter1
						$date_filter1
						$status_filter1
				) AS ts 
				WHERE ts.count < $batch_size ";
		//--SET @counter:=0, @temp_task_id:=0;
		$sql2 ="	
				SELECT * FROM  
				(
					SELECT 
						@counter := IF(@temp_task_id != t2.id, 1,@counter+1) AS count, 
						@temp_task_id := t2.id AS temp_task_id,
						t2.id AS id,
                                                t2.description as description,
                                                t2.valid_days_from_create as valid_days_from_create,
						tsc.id as entry_id,
						tsc.updated_by AS associate_id,
						CONCAT(assoc1.firstname , ' ' , assoc1.lastname) AS associate_name,
						tsc.store_id AS store_id,
						tsc.customer_id AS customer_id,
						tss2.status as status,
						tsc.title AS title,
						tsc.body AS body,
						tsc.updated_on as updated_on,
						tsc.created_on AS created_on,
						'CUSTOMER' as type,
						tsc.updated_by_till_id AS updated_by_till
					FROM (SELECT @counter := 0, @temp_task_id := 0) AS temp_declare, task AS t2
					JOIN task_status_customer AS tsc
						ON	 	t2.id = tsc.task_id
						AND t2.org_id = tsc.org_id
					JOIN task_statuses AS tss2
						ON tss2.id = tsc.status
						AND tss2.org_id = tsc.org_id
					LEFT JOIN masters.associates AS assoc1
					 	ON assoc1.id = tsc.updated_by
					 	AND assoc1.org_id = tsc.org_id
					WHERE t2.org_id = $this->org_id
						AND t2.expiry_date > NOW()
						AND DATE_ADD(tsc.created_on, INTERVAL t2.valid_days_from_create DAY) > DATE(NOW())
						$exclude_completed_task_filter2
						$store_filter2
						$assoc_filter2
						$customer_filter2
						$date_filter2
						$status_filter2
				) AS tcs 
				WHERE tcs.count < $batch_size 	
				"; 
		
		$sql  = $sql2;
		
		if($include_first_clause){
			$this->logger->debug("Including First query");
			$sql = $sql . " UNION $sql1";
		}

		$result = $this->db_user->query($sql);
			
		if(!$result || count($result) <= 0)
			return null;
		else
			return $result;
	}
	
	public function getTaskEntriesByTaskId($task_id, $include_completed_task = true, $limit = 1000)
	{
		if(!$include_completed_task)
		{
			$exclude_completed_task_filtered1 = " AND tss1.internal_status != 'COMPLETE'";
			$exclude_completed_task_filtered2 = " AND tss2.internal_status != 'COMPLETE'";
		}
		
		$sql = "
			(
				SELECT
					ts.id as entry_id,
					ts.executer_id AS associate_id,
					CONCAT(assoc.firstname , ' ' , assoc.lastname) AS associate_name,
					ts.store_id AS store_id,
					NULL AS customer_id,
					tss1.status as status,
					'CASHIER' as type
				FROM task AS t
				JOIN task_status AS ts
					ON 		t.id = ts.task_id
					AND t.org_id = ts.org_id
				JOIN task_statuses AS tss1
					ON tss1.id = ts.status
					AND tss1.org_id = ts.org_id
				LEFT JOIN masters.associates AS assoc 
				 	ON assoc.id = ts.executer_id
				 	AND assoc.org_id = ts.org_id
				WHERE t.org_id = $this->org_id
					AND t.id = $task_id
					$exclude_completed_task_filtered1
					LIMIT $limit
			)
			UNION
			(
				SELECT
					tsc.id as entry_id,
					tsc.updated_by AS associate_id,
					CONCAT(assoc1.firstname , ' ' , assoc1.lastname) AS associate_name,
					tsc.store_id AS store_id,
					tsc.customer_id AS customer_id,
					tss2.status as status,
					'CUSTOMER' as type
				FROM task AS t2
				JOIN task_status_customer AS tsc
					ON	 	t2.id = tsc.task_id
					AND t2.org_id = tsc.org_id
				JOIN task_statuses AS tss2
					ON tss2.id = tsc.status
					AND tss2.org_id = tsc.org_id
				LEFT JOIN masters.associates AS assoc1
				 	ON assoc1.id = tsc.updated_by
				 	AND assoc1.org_id = tsc.org_id
				WHERE t2.org_id = $this->org_id
					AND t2.id = $task_id
					$exclude_completed_task_filtered2
					LIMIT $limit
			)
		";
		$result = $this->db_user->query($sql);
			
		if(!$result || count($result) <= 0)
			return null;
		else
			return $result;
	}
	
	public function getTaskMetadata($assoc = false , $action_type = '', $created_by = '',
			$created_by_type = '', $execute_by_all = false, $customer_target = '',
			$start_id = '', $end_id = '', $start_date = '', $end_date = '', $batch_size = '',
			$include_completed_task = false)
	{
		$order_by_filter = " ORDER BY t.id DESC ";
			
		//$executable_by_filter = " AND executable_by_ids LIKE '%$this->logged_in_store_id%'";
		/*$executable_by_filter = " AND ( ( executable_by_type = 'store'
		 AND FIND_IN_SET( '$this->logged_in_store_id' , executable_by_ids ) > 0 )
				OR executable_by_type = 'cashier' )
		";*/
			
		if(!empty($assoc) && $assoc == true)
		{
			$assoc_filter = " AND t.executable_by_type = 'cashier' ";
			$assoc_filter2 = " AND t2.executable_by_type = 'cashier' ";
		}
		
		if(!empty($action_type))
		{
			$action_type_filter = " AND t.action_type = '$action_type' ";
			$action_type_filter2 = " AND t2.action_type = '$action_type' ";
		}
		if(!empty($created_by))
		{
			$created_by_filter = " AND t.created_by_id = $created_by";
			$created_by_filter2 = " AND t2.created_by_id = $created_by";
		}
		if(!empty($created_by_type))
		{
			$created_by_type_filter = " AND t.created_by_type = '$created_by_type'";
			$created_by_type_filter2 = " AND t2.created_by_type = '$created_by_type'";
		}
			
		if(!empty($execute_by_all))
		{
			$execute_by_all_filter = " AND t.execute_by_all = $execute_by_all ";
			$execute_by_all_filter2 = " AND t2.execute_by_all = $execute_by_all ";
		}
		if(!empty($customer_target))
		{
			$customer_target_filter = " AND t.customer_target = $customer_target ";
			$customer_target_filter2 = " AND t2.customer_target = $customer_target ";
		}
			
		if(!empty($start_id))
		{
			$id_filter = " AND t.id > $start_id ";
			$order_by_filter = " ORDER BY t.id ASC ";
			$id_filter2 = " AND t2.id > $start_id ";
			$order_by_filter2 = " ORDER BY t2.id ASC ";
		}
		else if(!empty($end_id))
		{
			$id_filter = " AND t.id < $end_id ";
			$order_by_filter = " ORDER BY t.id DESC ";
			$id_filter2 = " AND t2.id < $end_id ";
			$order_by_filter2 = " ORDER BY t2.id DESC ";
		}
			
		$batch_size = (int) $batch_size;
		if(!empty($batch_size) && $batch_size > 0)
		{
			$limit_filter = "LIMIT $batch_size";
		}
			
		$date_filter = "";
		if(!empty($start_date))
		{
			$date_filter .= " AND t.start_date > '$start_date' ";
			$date_filter2 .= " AND t2.start_date > '$start_date' ";
		}
		if(!empty($end_date))
		{
			$date_filter .= " AND t.start_date < '$end_date' ";
			$date_filter2 .= " AND t2.start_date < '$end_date' ";
		}
		
		if(!$include_completed_task)
		{
			$exclude_completed_task = " AND ts.internal_status != 'COMPLETE'";
			$exclude_completed_task2 = " AND ts2.internal_status != 'COMPLETE'";
		}
		
		$sql = "
			(
				SELECT
					t.id, t.title, t.body, t.start_date, t.end_date, t.expiry_date,
					t.action_type, t.action_template,
					CASE
						WHEN t.customer_target = ".CUSTOMER_TARGET_FALSE."
						THEN true
						ELSE false
					END AS cashier_task,
					CASE
						WHEN t.is_memo = ".MEMO_TASK_TRUE."
						THEN 'MEMO'
						ELSE 'TASK'
					END AS task_type,
					t.customer_target AS customer_task,
					t.execute_by_all ,
					t.created_by_type,
					t.created_by_id,
					t.statuses as possible_statuses,
					t.tags as tags,
					t.description,
					t.valid_days_from_create,
					CONCAT( a.firstname, ' ', a.lastname) as created_by_name
				FROM task AS t
				JOIN masters.associates AS a 
					ON a.id = t.created_by_id
					AND a.org_id = t.org_id
				JOIN task_statuses AS ts
					ON ts.org_id = t.org_id
					AND ts.id = t.status
					WHERE t.org_id = $this->org_id
					AND t.expiry_date > DATE(NOW())
					AND t.created_by_type != 'admin_user'
					$exclude_completed_task
					$executable_by_filter
					$assoc_filter
					$action_type_filter
					$execute_by_all_filter
					$created_by_filter
					$created_by_type_filter
					$customer_target_filter
					$id_filter
					$order_by_filter
					$limit_filter
		)
		UNION
		(
			SELECT
					t2.id, t2.title, t2.body, t2.start_date, t2.end_date, t2.expiry_date,
					t2.action_type, t2.action_template,
					CASE
						WHEN t2.customer_target = ".CUSTOMER_TARGET_FALSE."
						THEN true
						ELSE false
					END AS cashier_task,
					CASE
						WHEN t2.is_memo = ".MEMO_TASK_TRUE."
						THEN 'MEMO'
						ELSE 'TASK'
					END AS task_type,
					t2.customer_target AS customer_task,
					t2.execute_by_all ,
					t2.created_by_type,
					t2.created_by_id,
					t2.statuses as possible_statuses,
					t2.tags AS tags,
					t2.description,
					t2.valid_days_from_create,
					t2.created_by_id AS created_by_name
				FROM task AS t2
				JOIN task_statuses AS ts2
					ON ts2.org_id = t2.org_id
					AND ts2.id = t2.status
				WHERE t2.org_id = $this->org_id
					AND t2.expiry_date > DATE(NOW())
					AND t2.created_by_type = 'admin_user'
					$exclude_completed_task2
					$executable_by_filter2
					$assoc_filter2
					$action_type_filter2
					$execute_by_all_filter2
					$created_by_filter2
					$created_by_type_filter2
					$customer_target_filter2
					$id_filter2
					$order_by_filter2
					$limit_filter
		)";
		//CONCAT( au.first_name, ' ', au.last_name ) AS created_by_name
		//JOIN masters.admin_users AS au
		//ON au.id = t2.created_by_id
		//	JOIN `masters`.`loggable users` AS `lu` ON (
		//`lu`.`org_id` = `t`.`org_id` AND `lu`.`ref_id` = `t`.`created_by_id` AND `lu`.`type` = 'ASSOCIATE' )
		
		//TODO: here task can come batch_size * 2 because of UNION
		$sql = "SELECT * FROM ( $sql ) AS temp_table ORDER BY id DESC $limit_filter";
		$result = $this->db_user->query($sql);
		
		if ($result ['created_by_type'] == 'admin_user')  {
			include_once 'helper/memory_joiner/impl/MemoryJoinerFactory.php';
			include_once 'helper/memory_joiner/impl/MemoryJoinerType.php';
			
			$key_map = array( "created_by_name" => "{{joiner_concat(first_name,last_name)}}" );
			$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
			$result = $admin_user->prepareReport($result, $key_map );
		}
		
		return $result;
	}
	
	public function getStatusMapping()
	{
		$sql = "
			SELECT id , internal_status as value, status as label
			FROM task_statuses WHERE
			org_id = $this->org_id
		";
		$result = $this->db_user->query($sql);
		if($result && count($result) > 0)
			return $result;
		else
			return array();
	}
	
	public function getStatusMappingInHash($key = 'label', $value = 'value')
	{
		$sql = "
			SELECT id, internal_status as value, status as label
				FROM task_statuses 
				WHERE org_id = $this->org_id
		";
		$result = $this->db_user->query_hash($sql, $key, $value);
		if($result && count($result) > 0)
			return $result;
		else
			return array();
	}
	
	public function getReminders($task_id = -1, $only_for_current_store = false, $start_date = '' , $end_date = '' , $batch_size = 10)
	{
		$date_filter = "";
		$date_filter2 = "";
		if(!empty($task_id) && $task_id > 0)
		{
			$task_id_filter = " AND tr.task_id = $task_id";
			$task_id_filter2 = " AND tr2.task_id = $task_id";
		}
			
		if(!empty($start_date))
		{
			$date_filter .= " AND tr.time > '$start_date' ";
			$date_filter2 .= " AND tr2.time > '$start_date' ";
		}
		if(!empty($end_date))
		{
			$date_filter .= " AND tr.time < '$end_date' ";
			$date_filter2 .= " AND tr2.time < '$end_date' ";
		}
		if(!empty($batch_size) && $batch_size > 0)
		{
			$batch_size = (integer) $batch_size;
			$limit_filter = " LIMIT $batch_size";
			$limit_filter2 = " LIMIT $batch_size";
		}
		$sql = "
				(
				SELECT tr.id, tr.task_id, tr.created_by, tr.remindee_id, tr.time,
					tr.template, 
					CASE 
						WHEN ts.id IS NULL
						THEN tsc.id
						ELSE ts.id
					END AS entry_id
					FROM task_reminder AS tr
					JOIN task as t 
						ON t.id = tr.task_id 
						AND t.org_id = tr.org_id
					LEFT JOIN task_status AS ts 
						ON ts.executer_id = tr.remindee_id
						AND ts.org_id = tr.org_id
						AND ts.task_id = tr.task_id
					LEFT JOIN task_status_customer AS tsc
						ON tsc.updated_by = tr.remindee_id
						AND tsc.org_id = tr.org_id
						AND tsc.task_id = tr.task_id
					WHERE tr.org_id = $this->org_id
					AND t.expiry_date > NOW()
					AND (
						ts.id IS NOT NULL 
						OR tsc.id IS NOT NULL
					)
					$task_id_filter
					$date_filter
					$limit_filter
				)
				UNION
				(
				SELECT tr2.id, tr2.task_id, tr2.created_by, tr2.remindee_id, tr2.time,
					tr2.template, 
					CASE 
						WHEN ts2.id IS NULL
						THEN tsc2.id
						ELSE ts2.id
					END AS entry_id
					FROM task_reminder AS tr2
					JOIN task as t2 
						ON t2.id = tr2.task_id 
						AND t2.org_id = tr2.org_id
					LEFT JOIN task_status AS ts2 
						ON ts2.store_id = tr2.store_id
						AND ts2.org_id = tr2.org_id
						AND ts2.task_id = tr2.task_id
					LEFT JOIN task_status_customer AS tsc2
						ON tsc2.store_id = tr2.store_id
						AND tsc2.org_id = tr2.org_id
						AND tsc2.task_id = tr2.task_id
					WHERE tr2.org_id = $this->org_id
					AND t2.executable_by_type = 'store'
					AND t2.execute_by_all = 0
					AND t2.expiry_date > NOW()
					AND (
						ts2.id IS NOT NULL 
						OR tsc2.id IS NOT NULL
					)
					$task_id_filter2
					$date_filter2
					$limit_filter2
				)
				";
		
		$result = $this->db_user->query($sql);
		if($result && count($result) > 0)
			return $result;
		else
			return null;
	}
	
	public function addReminder($task_id, $assoc_id, $remindee_id, $template, $store_id, $time = '' )
	{
		if(empty($task_id) || $task_id <= 0)
			throw new Exception("ERR_TASK_ID_INVALID");
		
		if(!$this->isTaskExist($task_id))
			throw new Exception("ERR_TASK_ID_INVALID");
		
		if(empty($remindee_id) || $remindee_id <= 0)
			throw new Exception('ERR_TASK_INVALID_PARAMS');
		
		if(empty($time))
			$time = Util::getCurrentTimeForStore($store_id);
		
		$sql = "INSERT INTO task_reminder(task_id, org_id, created_by, remindee_id, time, template, store_id)
		VALUES($task_id, $this->org_id, $assoc_id, $remindee_id, '$time', '$template', $store_id)";
		
		$id = $this->db_user->insert($sql);
		
		return $id > 0 ? $id : -1;
	}
	
	/**
	 * 
	 * @param unknown_type $task_id 
	 * @param unknown_type $entries: should be array of the Task Entry/Status, which must contain associate_id of the status. 
	 * @param unknown_type $time: time of the reminder.
	 * @param unknown_type $template 
	 * @throws Exception
	 */
	public function addRemindersForEntries($task_id, $entries, $time, $template)
	{
		if(empty($task_id) || $task_id <= 0)
			throw new Exception("ERR_TASK_ID_INVALID");
		
		if(!$this->isTaskExist($task_id))
			throw new Exception("ERR_TASK_ID_INVALID");
		
		$values_arr = array();
		if(is_array($entries))
		{
			foreach($entries as $entry)
			{
				if(empty($entry['associate_id']) || $entry['associate_id'] <= 0)
					throw new Exception('Invalid Associate Id found while creating reminders for entries');
				$store_id = $entry['store_id'];
				$safe_template = Util::mysqlEscapeString($template);
				$values_arr[] = " ($task_id, $this->org_id, ".$entry['associate_id']. "," . $entry['associate_id'] ." , '$time', '$safe_template', $store_id )";
			}
				
				
			$values = "VALUES ".implode(",", $values_arr);
				
				
			//TODO: add actual store id, but here it should be associate id.
			/*$store_id = $this->currentuser->user_id;
		
			if(empty($time))
				$time = Util::getCurrentTimeForStore($store_id);*/
		
			$sql = "INSERT INTO task_reminder(task_id, org_id, created_by, remindee_id, time, template, store_id)	$values";
		
			$id = $this->db_user->insert($sql);
		}
		return $id > 0 ? $id : -1;
	}
	
	public function isTaskExist($task_id)
	{
		$sql = "SELECT count(*) FROM task WHERE org_id = $this->org_id AND id = $task_id";
		
		$count = $this->db_user->query_scalar($sql);
		
		return $count > 0 ;
	}
	
	public function getTaskUpdateLog($task_id, $task_entry_id = NULL)
	{
		if($task_entry_id != NULL || !empty($task_entry_id))
		{
			$task_entry_filter = " AND tul.task_entry_id = $task_entry_id";
		}
		$sql = " SELECT tul.task_id, tul.task_entry_id AS entry_id , 
				tul.id AS task_status_id, tul.customer_id, 
				tul.updated_by AS associate_id, tul.store_id, 
				tul.updated_time, ts.status AS new_status
			FROM task_update_log AS tul
			JOIN task_statuses AS ts
		        ON ts.org_id = tul.org_id 
		        AND ts.id = tul.updated_status
			WHERE tul.task_id = $task_id 
				AND tul.org_id = $this->org_id
				$task_entry_filter";
		
		$result = $this->db_user->query($sql);
		
		if(!$result || count($result) <= 0)
			return null;
		
		return $result;
	}
}

?>
