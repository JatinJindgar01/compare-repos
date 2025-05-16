<?php
include_once ("models/BaseModel.php");
include_once ("models/filters/TaskLoadFilters.php");
include_once ("exceptions/ApiTaskException.php");

/**
 * @author cj
 *
 */
class TaskMeta extends BaseApiModel{

	protected $task_id;
	protected $description;
	protected $title;
	protected $body;
	protected $start_date;
	protected $end_date;
	protected $expiry_date;
	protected $valid_days_from_create;
	protected $is_memo;
	protected $possible_statuses;
	protected $tags;
	protected $status;
	protected $updated_by;
	protected $updated_on;
	protected $action_type;
	protected $action_template;
	protected $created_by_type;
	protected $created_by_id;
	protected $executable_by_type;
	protected $executable_by_ids;
	protected $execute_by_all;
	protected $customer_target;
	protected $comment;
	
	// an array or all possible task status
	protected static $availableStatuses;
	
	protected $currentuser;
	protected $db_user;

	CONST CACHE_KEY_PREFIX_TASK_STATUS 	= 'TASK_STATUS_AVAILABLE#';
	CONST CACHE_KEY_PREFIX = 'TASK_ID#';
	CONST CACHE_TTL = 3600; // 1 hour
	
	
	public function __construct($current_org_id, $task_id = null)
	{
		parent::__construct($current_org_id);
		$this->task_id = $task_id;
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		
		// db connection
		$this->db_user = new Dbase( 'users' );
		
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
			"task_id",
			"description",
			"title",
			"body",
			"start_date",
			"end_date",
			"expiry_date",
			"valid_days_from_create",
			"is_memo",
			"possible_statuses",
			"tags",
			"status",
			"updated_by",
			"updated_on",
			"action_type",
			"action_template",
			"created_by_type",
			"created_by_id",
			"executable_by_type",
			"executable_by_ids",
			"execute_by_all",
			"customer_target",
			"comment",
		);   
		
		self::$iterableMembers = $local_members;
	}
	
	

	public function getTaskId()
	{
	    return $this->task_id;
	}

	public function setTaskId($task_id)
	{
	    $this->task_id = $task_id;
	}

	public function getDescription()
	{
	    return $this->description;
	}

	public function setDescription($description)
	{
	    $this->description = $description;
	}

	public function getTitle()
	{
	    return $this->title;
	}

	public function setTitle($title)
	{
	    $this->title = $title;
	}

	public function getBody()
	{
	    return $this->body;
	}

	public function setBody($body)
	{
	    $this->body = $body;
	}

	public function getStartDate()
	{
	    return $this->start_date;
	}

	public function setStartDate($start_date)
	{
	    $this->start_date = $start_date;
	}

	public function getEndDate()
	{
	    return $this->end_date;
	}

	public function setEndDate($end_date)
	{
	    $this->end_date = $end_date;
	}

	public function getExpiryDate()
	{
	    return $this->expiry_date;
	}

	public function setExpiryDate($expiry_date)
	{
	    $this->expiry_date = $expiry_date;
	}

	public function getValidDaysFromCreate()
	{
	    return $this->valid_days_from_create;
	}

	public function setValidDaysFromCreate($valid_days_from_create)
	{
	    $this->valid_days_from_create = $valid_days_from_create;
	}

	public function getIsMemo()
	{
	    return $this->is_memo;
	}

	public function setIsMemo($is_memo)
	{
	    $this->is_memo = $is_memo;
	}

	public function getPossibleStatuses()
	{
	    return $this->possible_statuses;
	}

	public function setPossibleStatuses($statuses)
	{
	    $this->possible_statuses = $statuses;
	}

	public function getTags()
	{
	    return $this->tags;
	}

	public function setTags($tags)
	{
	    $this->tags = $tags;
	}

	public function getStatus()
	{
	    return $this->status;
	}

	public function setStatus($status)
	{
	    $this->status = $status;
	}

	public function getUpdatedBy()
	{
	    return $this->updated_by;
	}

	public function setUpdatedBy($updated_by)
	{
	    $this->updated_by = $updated_by;
	}

	public function getUpdatedOn()
	{
	    return $this->updated_on;
	}

	public function setUpdatedOn($updated_on)
	{
	    $this->updated_on = $updated_on;
	}

	public function getActionType()
	{
	    return $this->action_type;
	}

	public function setActionType($action_type)
	{
	    $this->action_type = $action_type;
	}

	public function getActionTemplate()
	{
	    return $this->action_template;
	}

	public function setActionTemplate($action_template)
	{
	    $this->action_template = $action_template;
	}

	public function getCreatedByType()
	{
	    return $this->created_by_type;
	}

	public function setCreatedByType($created_by_type)
	{
	    $this->created_by_type = $created_by_type;
	}

	public function getCreatedById()
	{
	    return $this->created_by_id;
	}

	public function getExecutableByType()
	{
	    return $this->executable_by_type;
	}

	public function setExecutableByType($executable_by_type)
	{
	    $this->executable_by_type = $executable_by_type;
	}

	public function getExecutableByIds()
	{
	    return $this->executable_by_ids;
	}

	public function setExecutableByIds($executable_by_ids)
	{
	    $this->executable_by_ids = $executable_by_ids;
	}

	public function getExecuteByAll()
	{
	    return $this->execute_by_all;
	}

	public function setExecuteByAll($execute_by_all)
	{
	    $this->execute_by_all = $execute_by_all;
	}

	public function getCustomerTarget()
	{
	    return $this->customer_target;
	}

	public function setCustomerTarget($customer_target)
	{
	    $this->customer_target = $customer_target;
	}

	public function getComment()
	{
	    return $this->comment;
	}

	public function setComment($comment)
	{
	    $this->comment = $comment;
	}
	
	public function save()
	{
	
		$columns = array();

		if(isset($this->description))
			$columns["description"]= "'".$this->description."'";
		if(isset($this->title))
			$columns["title"]= "'".$this->title."'";
		if(isset($this->body))
			$columns["body"]= "'".$this->body."'";
		if(isset($this->start_date))
			$columns["start_date"]= "'".$this->start_date."'";
		if(isset($this->end_date))
			$columns["end_date"]= "'".$this->end_date."'";
		if(isset($this->expiry_date))
			$columns["expiry_date"]= "'".$this->expiry_date."'";
		if(isset($this->valid_days_from_create))
			$columns["valid_days_from_create"]= "'".$this->valid_days_from_create."'";
		if(isset($this->is_memo))
			$columns["is_memo"]= $this->is_memo ? 1 : 0 ;

		if(isset($this->possible_statuses))
			$columns["statuses"]= "'".$this->possible_statuses."'";
		if(isset($this->tags))
			$columns["tags"]= "'".$this->tags."'";
		if(isset($this->status))
			$columns["status"]= "'".$this->status."'";
		if(isset($this->action_type))
			$columns["action_type"]= "'".$this->action_type."'";
		if(isset($this->action_template))
			$columns["action_template"]= "'".$this->action_template."'";
		if(isset($this->executable_by_type))
			$columns["executable_by_type"]= "'".$this->executable_by_type."'";
		if(isset($this->executable_by_ids))
			$columns["executable_by_ids"]= "'".$this->executable_by_ids."'";
		if(isset($this->execute_by_all))
			$columns["execute_by_all"]= "'".$this->execute_by_all."'";
		if(isset($this->customer_target))
			$columns["customer_target"]= "'".$this->customer_target."'";
		if(isset($this->comment))
			$columns["comment"]= "'".$this->comment."'";
		
		if(!$this->task_id)
		{
			$this->logger->debug("Task id is not set, so its going to be an insert query");
			if(isset($this->created_by_type))
				$columns["created_by_type"]= "'".$this->created_by_type."'";
			$columns["updated_on"]= "'".Util::getMysqlDateTime($this->joined ? $this->joined : 'now')."'";
			$columns["org_id"] = $this->current_org_id;
			$columns["created_by_id"] = $this->current_user_id;
			$columns["updated_by"] = $this->current_user_id;
			
			$sql = "INSERT INTO user_management.task";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);
		
			$this->logger->debug("Return of saving the new task is $newId");
		
			if($newId > 0)
				$this->task_id = $newId;
		
		}
		else
		{
			$columns["updated_on"]= "'".Util::getMysqlDateTime($this->joined ? $this->joined : 'now')."'";
			$columns["updated_by"] = $this->current_user_id;
				
			$this->logger->debug("Loyalty id is set, so its going to be an update query");
			$sql = "UPDATE user_management.task SET ";
		
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
		
			// remove the extra comma
			$sql=substr($sql,0,-2);
		
			$sql .= " WHERE id = $this->task_id and org_id = $this->current_org_id";
			$newId = $this->db_user->update($sql);
		}
	
		if($newId)
		{
			$this->logger->debug("Saving the task has completed");
			return $newId;
		}
		else
		{
			$this->logger->debug("Saving the task has failed");
			throw new ApiTaskException(ApiTaskException::SAVING_DATA_FAILED);
		}

	}
	
	public function validate()
	{
	}
	
	public static function loadbyId($org_id, $task_id)
	{
		global $logger;
		$logger->debug("Loading from based on task id");
		
		if(!$task_id)
		{
			throw new ApiTaskException(ApiTaskException::FILTER_TASK_INVALID_OBJECT_PASSED);
		}
		
		$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX, $task_id, $org_id );
		if(!$obj = self::loadFromCache($cacheKey))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");
		
			$filters = new TaskLoadFilters();
			$filters->task_id = $task_id;

			try{
				$array = self::loadAll($org_id, $filters, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}
		
			if($array)
			{
				return $array[0];//self::fromArray->($array[0])->toArray()
			}
			throw new ApiTaskException(ApiTaskException::FILTER_NON_EXISTING_ID_PASSED);
		
		}
 		else
 		{
 			$logger->debug("Loading from the Cache was successful. returning");
 			//$obj = self::fromString($org_id, $obj);
 			return $obj;
 		}
	}

	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		global $logger;
		
		if(isset($filters) && !($filters instanceof TaskLoadFilters))
		{
			throw new ApiTaskException(ApiTaskException::FILTER_TASK_INVALID_OBJECT_PASSED);
		}
		
		$sql = "SELECT
				t.id as task_id, 
				t.org_id, 
				t.description, 
				t.title, 
				t.body, 
				t.start_date, 
				t.end_date, 
				t.expiry_date, 
				t.valid_days_from_create, 
				t.is_memo, 
				t.statuses as possible_statuses, 
				t.tags, 
				t.status, 
				t.updated_by, 
				t.updated_on, 
				t.action_type, 
				t.action_template, 
				t.created_by_type, 
				t.created_by_id, 
				t.executable_by_type, 
				t.executable_by_ids, 
				t.execute_by_all, 
				t.customer_target   
				FROM task as t 
				WHERE t.org_id = $org_id";

		if($filters->task_id)
			$sql .= " AND t.id= ".$filters->task_id;
		if($filters->status)
			$sql .= " AND t.status= ".$filters->status;
		if($filters->created_by_type)
			$sql .= " AND t.created_by_type= '".$filters->created_by_type."'";
		if($filters->created_by)
			$sql .= " AND t.created_by= '".$filters->created_by."'";
		if($filters->end_date)
			$sql .= " AND t.end_date<= '".$filters->end_date."'";
		if(isset($filters->is_memo))
			$sql .= " AND t.is_memo<= ".($filters->is_memo ? 1 : 0 );
		if(isset($filters->customer_target))
			$sql .= " AND t.customer_target<= ".($filters->customer_target ? 1 : 0 );
		if(isset($filters->store_target))
			$sql .= " AND t.customer_target<= ".($filters->store_target ? 0 : 1 );
		$sql .= " ORDER BY t.id desc ";
		
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 100;
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		$sql = $sql . " LIMIT $offset, $limit";

		$db_user = new Dbase( 'users' );
		$array = $db_user->query($sql);
		
		if($array)
		{
			$ret = array();
			foreach ( $array as $row)
			{
				$obj = self::fromArray($org_id, $row);
				
				$cacheKey = self::generateCacheKey(TaskMeta::CACHE_KEY_PREFIX, $row["org_id"], $org_id );
				self::saveToCache($cacheKey, $obj->toString());
				
				$ret[] = $obj;
			}
			$logger->debug("Successfully loaded the tasks". count($array). " rows");
			return $ret;
		}
		else
			throw new ApiTaskException(ApiTaskException::NO_TASK_MATCHES);
	}
	
	// to load the possible states of a task for a given org
	public function loadStatusForOrg($org_id = null)
	{
		if($org_id === NULL)
		{
			$org_id = $this->current_org_id;
			$logger->debug("The org id not passed for loading the status, defaulting to the current org");
		}
		
		TaskMeta::$availableStatuses = TaskStatusMap::loadAll($org_id);
	}
	
} 