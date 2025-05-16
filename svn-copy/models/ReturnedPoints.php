<?php

/**
 * @author cj
 *
 * The object to hold the return of points 
 */
abstract class ReturnedPoints{
	
	protected $user_id;
	protected $current_org_id;
	protected $logger;
	
	protected $return_id;
	protected $returned_points;
	protected $returned_by_id;
	protected $returned_by_user;
	protected $returned_on;
	protected $transaction_number;
	protected $transaction_id;
	protected $transaction;
	
	// sdk to load the points
	protected $pointsService;
	
	protected static $iterableMembers = array();
	CONST CACHE_TTL = 3600; // 1 hour
	
	public function __construct($org_id, $user_id){
		
		global $logger;
		$this->logger = $logger;
		
		$this->user_id = $user_id;
		$this->current_org_id = $org_id;
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
			"return_id",
			"return_points",
			"return_by_id",
			"return_by_user",
			"return_on",
			"transaction_number",
			"transaction_id",
		);
	
	}
	public function getUserId()
	{
	    return $this->user_id;
	}

	public function setUserId($user_id)
	{
	    $this->user_id = $user_id;
	}

	public function getReturnId()
	{
	    return $this->redemption_id;
	}

	public function getReturnedPoints()
	{
	    return $this->returned_points;
	}

	public function setReturnedPoints($returned_points)
	{
	    $this->returned_points = $returned_points;
	}

	public function getReturnedById()
	{
	    return $this->returned_by_id;
	}
	
	public function setReturnedById($returned_by_id)
	{
		$this->returned_by_id = $returned_by_id;
	}
	
	public function getReturnedByUser()
	{
	    return $this->returned_by_user;
	}

	public function getReturnedOn()
	{
	    return $this->returned_on;
	}

	public function setReturnedOn($returned_on)
	{
	    $this->returned_on = $returned_on;
	}

	public function getTransactionNumber()
	{
	    return $this->transaction_number;
	}

	public function setTransactionNumber($transaction_number)
	{
	    $this->transaction_number = $transaction_number;
	}

	public function getTransactionId()
	{
	    return $this->transaction_id;
	}

	public function setTransactionId($transaction_id)
	{
	    $this->transaction_id = $transaction_id;
	}

	public function getTransaction()
	{
	    return $this->transaction;
	}

	
	// TODO  : call the sdk or thrift to get the info
	public function loadAll()
	{
	
		
	}
	

	/**
	 * initiate the respective class on demand
	 * @param $memberName - the object need to be initialized
	 */
	protected function initiateDependentObject($memberName)
	{
		$this->logger->debug("Lazy loading the $memberName object");
		switch(strtolower($memberName))
		{
			case 'pointsservice':
				include_once 'services/PointsService.php';
				if(!$this->pointsService instanceof PointsService)
				{
					$this->pointsService = new PointsService();
					$this->logger->debug("Loaded pe object");
				}
				break;
		}
	}
	
	
}