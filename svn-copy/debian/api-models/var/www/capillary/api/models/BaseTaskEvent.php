<?php
include_once ("models/BaseModel.php");
include_once ("models/filters/TaskLoadFilters.php");
include_once ("exceptions/ApiTaskException.php");

/**
 * @author cj
 *
 */
class BaseTaskEvent extends BaseApiModel{

	protected $event_id;
	protected $task_id;
	protected $store_id;
	protected $updated_by_till_id;
	protected $created_on;
	protected $updated_on;
	protected $status;

	protected $currentuser;
	protected $db_user;

	
	public function __construct($current_org_id, $task_event_id = null)
	{
		parent::__construct($current_org_id);
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		
		$this->event_id = $task_event_id;
		
		// db connection
		$this->db_user = new Dbase( 'users' );
		
	}
	

	public static function setIterableMembers()
	{
	
		$local_members = array(
				"event_id",
				"task_id",
				"store_id",
				"updated_by_till_id",
				"created_on",
				"updated_on",
				"status",
		);
	
		self::$iterableMembers = $local_members;
	}
	
	public function getEventId()
	{
	    return $this->event_id;
	}

	public function setEventId($event_id)
	{
	    $this->event_id = $event_id;
	}

	public function getTaskId()
	{
	    return $this->task_id;
	}

	public function setTaskId($task_id)
	{
	    $this->task_id = $task_id;
	}

	public function getStoreId()
	{
	    return $this->store_id;
	}

	public function setStoreId($store_id)
	{
	    $this->store_id = $store_id;
	}

	public function getUpdatedByTillId()
	{
	    return $this->updated_by_till_id;
	}

	public function setUpdatedByTillId($updated_by_till_id)
	{
	    $this->updated_by_till_id = $updated_by_till_id;
	}

	public function getCreatedOn()
	{
	    return $this->created_on;
	}

	public function setCreatedOn($created_on)
	{
	    $this->created_on = $created_on;
	}

	public function getUpdatedOn()
	{
	    return $this->updated_on;
	}

	public function setUpdatedOn($updated_on)
	{
	    $this->updated_on = $updated_on;
	}

	public function getStatus()
	{
	    return $this->status;
	}

	public function setStatus($status)
	{
	    $this->status = $status;
	}
} 