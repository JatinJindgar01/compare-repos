<?php

/**
 * @author cj
 *
 * Base class which handles all the awarded points of all levels 
 */
abstract class BaseAwardedPoints{
	
	protected $user_id;
	protected $current_org_id;
	protected $logger;
	
	protected $awarded_points;
	protected $redeemed_points;
	protected $returned_points;
	protected $expired_points;
	protected $awarded_on;
	protected $awarded_by_id;
	protected $awarded_by_user;
	protected $expiry_date;
	
	protected $category_name;
	protected $category_id;
	
	// sdk to load the points
	protected $pointsService;
	
	protected static $iterableMembers = array();
	CONST CACHE_TTL = 3600; // 1 hour
	CONST CACHE_KEY_PREFIX	= 'POINTS_AWARDED_USER_ID#';
	
	public function __construct($org_id, $user_id){
		
		global $logger;
		$this->logger = $logger;
		
		$this->user_id = $user_id;
		$this->current_org_id = $org_id;
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"awarded_points",
				"redeemed_points",
				"returned_points",
				"expired_points",
				"awarded_on",
				"awarded_by_id",
				"awarded_by_user",
				"expiry_date",
				"category_name",
				"category_id",
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

	public function getAwardedPoints()
	{
	    return $this->awarded_points;
	}

	public function setAwardedPoints($awarded_points)
	{
	    $this->awarded_points = $awarded_points;
	}

	public function getRedeemedPoints()
	{
	    return $this->redeemed_points;
	}

	public function getReturnedPoints()
	{
	    return $this->returned_points;
	}

	public function getExpiredPoints()
	{
	    return $this->expired_points;
	}

	public function getAwardedOn()
	{
	    return $this->awarded_on;
	}

	public function getAwardedById()
	{
	    return $this->awarded_by_id;
	}

	public function getAwardedByUser()
	{
	    return $this->awarded_by_user;
	}

	public function getExpiryDate()
	{
	    return $this->expiry_date;
	}
	
	public function getCategoryId()
	{
		return $this->category_id;
	}
	
	public function getCategoryName()
	{
		return $this->category_name;
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