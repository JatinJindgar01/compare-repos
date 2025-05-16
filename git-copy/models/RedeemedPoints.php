<?php

/**
 * @author cj
 *
 * The object to hold the redemtio of points 
 */
abstract class RedeemedPoints{
	
	protected $user_id;
	protected $current_org_id;
	protected $logger;
	
	protected $redeemed_points;
	protected $redeemed_by_id;
	protected $redeemed_by_user;
	protected $redeemed_on;
	protected $transaction_number;
	protected $transaction_id;
	protected $transaction;
	
	// sdk to load the points
	protected $pointsService;
	
	protected static $iterableMembers = array();
	CONST CACHE_TTL = 3600; // 1 hour
	CONST CACHE_KEY_PREFIX	= 'POINTS_REDEEMED_USER_ID#';
	
	public function __construct($org_id, $user_id){
		
		global $logger;
		$this->logger = $logger;
		
		$this->user_id = $user_id;
		$this->current_org_id = $org_id;
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
			"redeemed_points",
			"redeemed_by_id",
			"redeemed_by_user",
			"redeemed_on",
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

	public function getRedeemedPoints()
	{
	    return $this->redeemed_points;
	}

	public function setRedeemedPoints($redeemed_points)
	{
	    $this->redeemed_points = $redeemed_points;
	}

	public function getRedeemedById()
	{
	    return $this->redeemed_by_id;
	}
	
	public function setRedeemedById($redeemed_by_id)
	{
		$this->redeemed_by_id = $redeemed_by_id;
	}
	
	public function getRedeemedByUser()
	{
	    return $this->redeemed_by_user;
	}

	public function getRedeemedOn()
	{
	    return $this->redeemed_on;
	}

	public function setRedeemedOn($redeemed_on)
	{
	    $this->redeemed_on = $redeemed_on;
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
	public static function loadRedemptionsForCustomer($org_id, $user_id)
	{
		global $logger;
		
		if($objArr = $this->loadFromCache(RedeemedPoints::CACHE_KEY_PREFIX.$org_id."##".$user_id))
		{
			
		}
			
		$logger->debug("loading the redemptions for a customer");
		// intiate the obj
		$this->initiateDependentObject("pointsService");
		
		$deductionsArr = $this->pointsService->getDeductionsForCustomer($orgId, $customerId);
		
		$ret = array();
		foreach($deductionsArr as $deduction)
		{
			if($deduction->type == 'REDEEMED')
			{
				$obj = new RedeemedPoints();
				$obj->redeemed_points = $deduction->pointsDeducted;
				$obj->redeemed_by_id= $deduction->pointsDeductedById;
				$obj->redeemed_by_user= $deduction->pointsDeductedBy;
				$obj->redeemed_on = $deduction->pointsDeductedOn;
				//$obj->transaction_number= $deduction->;
				//$obj->transaction_id= $deduction->;
				//$obj->transaction= $deduction->;
				
				$ret[] = $obj;
			}
		}
		return $obj;
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