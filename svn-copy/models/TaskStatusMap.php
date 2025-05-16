<?php
include_once ("models/BaseModel.php");
include_once ("exceptions/ApiTaskException.php");
/**
 * @author cj
 * The class defines all the task status for a given org
 */
class TaskStatusMap extends BaseApiModel{

	protected $id;
	protected $org_id;
	protected $status;
	protected $internal_status;
	
	protected $currentuser;
	protected $db_user;

	CONST CACHE_KEY_PREFIX_TASK_STATUS 	= 'TASK_STATUS_AVAILABLE#';
	
	
	public function __construct($current_org_id, $task_id = null)
	{
		parent::__construct($current_org_id);
		$this->id = $task_id;
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		
		// db connection
		$this->db_user = new Dbase( 'users' );
		
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
			"id",
			"status",
			"internal_status",
		);   
		
		self::$iterableMembers = $local_members;
	}
	
	

	public function getId()
	{
	    return $this->id;
	}

	public function setId($id)
	{
	    $this->id = $id;
	}

	public function getOrgId()
	{
	    return $this->org_id;
	}

	public function setOrgId($org_id)
	{
	    $this->org_id = $org_id;
	}

	public function getStatus()
	{
	    return $this->status;
	}

	public function setStatus($status)
	{
	    $this->status = $status;
	}

	public function getInternalStatus()
	{
	    return $this->internal_status;
	}

	public function setInternalStatus($internalStatus)
	{
	    $this->internal_status = $internalStatus;
	}

	
	public function save()
	{
		throw new ApiTaskException(ApiTaskException::FUNCTION_NOT_IMPLEMENTED);// thow new exception
	}
	
	public function validate()
	{
		throw new ApiTaskException(ApiTaskException::FUNCTION_NOT_IMPLEMENTED);// thow new exception
	}
	
	public static function loadbyId($org_id, $status_id)
	{
		$statuses = self::loadAll($org_id);
		
		foreach($statuses as $status)
		{
			if($status->getId() == $status_id)
				return $status;
		}
		
		throw new ApiTaskException(ApiTaskException::NO_FOUND_STATUS_FOR_ORG);
	}

	public static function loadbyInternalStatus($org_id, $internal_status)
	{
		$statuses = self::loadAll($org_id);
	
		foreach($statuses as $status)
			if($status->getInternalStatus() == $internal_status)
				return $status;
	
		else
			throw new ApiTaskException(ApiTaskException::NO_FOUND_STATUS_FOR_ORG);
	}

	public static function loadbyStatus($org_id, $statusName)
	{
		$statuses = self::loadAll($org_id);
	
		foreach($statuses as $status)
			if($status->getStatus() == $statusName)
				return $status;
	
		else
			throw new ApiTaskException(ApiTaskException::NO_FOUND_STATUS_FOR_ORG);
	}
	
	public static function loadAll($org_id)
	{
		global $logger;
		
		$cacheKey = self::generateCacheKey(TaskStatusMap::CACHE_KEY_PREFIX_TASK_STATUS, "", $org_id);
		if($str = self::getFromCache($cacheKey))
		{
			$logger->debug("Reading from cache was successful -- ". $str);
			$array = self::decodeFromString($str);
			$ret = array();
			
			foreach($array as $row)
			{
				$obj = TaskStatusMap::fromString($org_id, $row);
				$ret[$obj->getId()] = $obj;
				//$logger->debug("data from cache" . $obj->toString());
			}
				
			return $ret;
		}
		
		$sql = "SELECT
		ts.id,
		ts.internal_status,
		ts.status
		FROM task_statuses ts
		WHERE org_id = $org_id";
		$db_user = new Dbase( 'users' );
		$statuses = $db_user->query($sql);
		
		if($statuses)
		{
			foreach($statuses as $row)
			{
				$obj = self::fromArray($org_id, $row);
				$ret[$obj->getId()] = $obj;
				$cacheStringArr[$obj->getId()] = $obj->toString();
			}
			
			if($cacheStringArr)
			{
				$logger->debug("saving the status to cache");
				$str =self::encodeToString($cacheStringArr);
			
				$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_TASK_STATUS, "", $org_id);
				self::saveToCache($cacheKey, $str);
			}
		}
		else
		{
			$logger->debug("No status available for the org");
			throw new ApiTaskException(ApiTaskException::NO_FOUND_STATUS_FOR_ORG);
		}	
	}
} 