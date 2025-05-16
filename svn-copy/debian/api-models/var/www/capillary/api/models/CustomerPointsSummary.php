<?php

/**
 * @author cj
 *
 * The class handles all the customer level point summary etc on the customer level
 */
class CustomerPointsSummary{
	
	private $logger;

	protected $current_org_id;
	protected $user_id;
	protected $current_points;
	protected $lifetime_points;
	protected $expired_points;
	protected $redeemed_points;
	protected $returned_points;
	protected $last_updated_on;
	protected $last_awarded_on;
	
	protected $pointsService;
	
	CONST CACHE_TTL = 3600; // 1 hour
	CONST CACHE_KEY_PREFIX	= 'POINTS_SUMMARY_USER_ID#';
	
	
	public function __construct($org_id, $user_id = null)
	{
		global $logger;
		$this->logger = $logger;
		
		$this->current_org_id = $org_id;
		$this->user_id = 0;
	}

	public function getUserId()
	{
	    return $this->user_id;
	}

	public function setUserId($user_id)
	{
	    $this->user_id = $user_id;
	}

	public function getCurrentPoints()
	{
	    return $this->current_points;
	}

	public function getLifetimePoints()
	{
	    return $this->lifetime_points;
	}

	public function getExpiredPoints()
	{
	    return $this->expired_points;
	}

	public function getRedeemedPoints()
	{
	    return $this->redeemed_points;
	}

	public function getReturnedPoints()
	{
	    return $this->returned_points;
	}

	public function getLastUpdatedOn()
	{
	    return $this->last_updated_on;
	}

	public function getLastAwardedOn()
	{
	    return $this->last_awarded_on;
	}
	
	public function loadSummary()
	{

		$this->logger->debug("Get the data from the pe service");
		
		if(!$this->user_id )
			throw ApiException::UNKNOWN_ERROR;
		
		if(!$obj = $this->loadFromCache(CustomerPointsSummary::CACHE_KEY_PREFIX.$this->current_org_id."##".$this->user_id))
		{
		
			$this->initiateDependentObject("pointsService");
		
			$summary = $this->pointsService->getPointsSummaryForCustomer($org_Id, $user_id);
			
			$this->setDataFromPEAndSaveToCache($summary);
			
			$obj = new Slab();
			$obj->setSerialNumber($summary->slabSerialNumber);
			$obj->setName($summary->slabName);
			$obj->setDescription($summary->slabDescription);
			//$obj->saveToCache(CustomerSlab::CACHE_KEY_PREFIX.$this->current_org_id."##".$this->user_id, $obj->toString());
			
			$obj1 = new Slab();
			$obj1->setSerialNumber($summary->nextSlabSerialNumber);
			$obj1->setName($summary->nextSlabName);
			$obj1->setDescription($summary->nextSlabDescription);
			$str = json_encode(array(
						"current" => $obj->toString(),
						"next" => $obj1->toString(),
					));
			
			$obj1->saveToCache(CustomerSlab::CACHE_KEY_PREFIX.$this->current_org_id."##".$this->user_id, $str);
				
		}
		else
		{
			return;
		}						
	}
	
	public function setDataFromPEAndSaveToCache($summary)
	{
		$this->current_points  = $summary->currentPoints;
		$this->lifetime_points = $summary->cumulativePoints;
		$this->expired_points  = $summary->pointsExpired;
		$this->redeemed_points = $summary->pointsRedeemed;
		$this->returned_points = $summary->pointsReturned;
		$this->last_updated_on = $summary->lastUpdatedOn;
		$this->last_awarded_on = $summary->lastAwardedOn;
			
		// save the data to cache
		$this->saveToCache(CustomerPointsSummary::CACHE_KEY_PREFIX.$this->current_org_id."##".$this->user_id, $this->toString());
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