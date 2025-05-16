<?php
include_once ("models/BaseTaskEvent.php");

/**
 * @author cj
 *
 * The class defines the store task events
 */
class StoreTaskEvent extends BaseTaskEvent{

	protected $executer_id;
	
	CONST CACHE_KEY_PREFIX_TASK_STATUS 	= 'STORE_TASK_#';
	
	
	public function __construct($current_org_id, $task_event_id = null)
	{
		parent::__construct($current_org_id);
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
				"executer_id",
			);   
		
		self::$iterableMembers = array_unique(self::$iterableMembers ,$local_members);
	}
	
	public function setExecuterId($executer_id)
	{
		$this->executer_id = $executer_id;
	}
	
	public function getExecuterId($executer_id)
	{
		return $this->getExecuterId($executer_id);
	}

	public function save()
	{
		$columns = array();

		if(isset($this->task_id))
			$columns["task_id"]= $this->task_id;
		if(isset($this->store_id))
			$columns["store_id"]= $this->store_id;
		if(isset($this->status))
			$columns["status"]= "'".$this->status."'";
		if(isset($this->executer_id))
			$columns["executer_id"]= "'".$this->executer_id."'";
		
		
		if(!$this->event_id)
		{
			$this->logger->debug("Task id is not set, so its going to be an insert query");
			$columns["updated_on"]= "'".Util::getMysqlDateTime($this->updated_on ? $this->updated_on : 'now')."'";
			$columns["created_on"]= "'".Util::getMysqlDateTime($this->created_on ? $this->created_on : 'now')."'";
			$columns["org_id"] = $this->current_org_id;
			$columns["updated_by"] = $this->current_user_id;
			$columns["updated_by_till_id"] = $this->current_user_id;
			
			$sql = "INSERT INTO user_management.task_status";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);
		
			$this->logger->debug("Return of saving the new task is $newId");
		
			if($newId > 0)
				$this->event_id = $newId;
		
		}
		else
		{
			$columns["updated_on"]= "'".Util::getMysqlDateTime($this->joined ? $this->joined : 'now')."'";
			$columns["updated_by"] = $this->current_user_id;
			$columns["updated_by_till_id"] = $this->current_user_id;
				
			$this->logger->debug("Loyalty id is set, so its going to be an update query");
			$sql = "UPDATE user_management.task_status SET ";
		
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
		
			// remove the extra comma
			$sql=substr($sql,0,-2);
		
			$sql .= " WHERE id = $this->event_id and org_id = $this->current_org_id";
			$newId = $this->db_user->update($sql);
		}
		
		if($newId)
		{
			return $newId;
		}
		else
		{
			$this->logger->debug("Saving the task event has failed");
			throw new ApiTaskException(ApiTaskException::SAVING_DATA_FAILED);
		}
	}
	
	public function validate()
	{
	}
	
	public static function loadbyId($org_id, $event_id)
	{
		global $logger;
		$logger->debug("Loading from based on task id");
		
		if(!$event_id)
		{
			throw new ApiTaskException(ApiTaskException::FILTER_TASK_INVALID_OBJECT_PASSED);
		}
		
		//$cacheKey = $this->generateCacheKey(Tasks::CACHE_KEY_PREFIX_TASK_STATUS, $event_id, $org_id);
		//if(!$obj = self::loadFromCache($org_id, $cacheKey))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");
		
			$filters = new TaskLoadFilters();
			$filters->event_id = $event_id;
		
			try{
				$array = self::loadAll($org_id, $filters, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}
		
			if($array)
			{
				return $array[0];
			}
			throw new ApiTaskException(ApiTaskException::FILTER_NON_EXISTING_ID_PASSED);
		
		}
		// 		else
			// 		{
			// 			$logger->debug("Loading from the Cache was successful. returning");
			// 			$obj = self::fromString($org_id, $obj);
			// 			return $obj;
			// 		}
		
	}

	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof TaskLoadFilters))
		{
			throw new ApiTaskException(ApiTaskException::FILTER_TASK_INVALID_OBJECT_PASSED);
		}
		
		$sql = "SELECT
				ts.id as event_id,
				ts.task_id as task_id,
				ts.store_id,
				ts.executer_id,
				ts.updated_on,
				ts.created_on,
				ts.status
				FROM task_status as ts
				WHERE ts.org_id = $org_id";

		if($filters->event_id)
			$sql .= " AND ts.id= ".$filters->event_id;
		if($filters->task_id)
			$sql .= " AND ts.task_id= ".$filters->task_id;
		$sql .= " ORDER BY ts.id desc ";
		
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 100;
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		$sql = $sql . " LIMIT $offset, $limit";
		
		$array = $this->db_user->query($sql);
		
		if($array)
		{
			foreach ( $array as $row)
			{
				$ret = array();
				$obj = self::fromArray($org_id, $row);
				$ret[] = $obj;
			}
			$this->logger->debug("Successfully loaded the tasks". count($array). " rows");
			return $ret;
		}
		else
			throw new ApiTaskException(ApiTaskException::NO_TASK_MATCHES);
	}
	
} 