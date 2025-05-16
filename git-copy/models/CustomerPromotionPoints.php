<?php

include_once "models/BaseAwardedPoints.php";

/**
 * @author cj
 *
 * The class to hold all the customer promotion points
 */
class CustomerPromotionPoints extends BaseAwardedPoints{
	
	protected $promotion_id;
	CONST CACHE_KEY_PREFIX_CUSTOMER_PROMO 	= 'POINTS_CUST_PROMO_USER_ID#';
		
	public function __construct($org_id, $user_id)
	{
		parent::__construct($org_id, $user_id);
	
	}

	public static function setIterableMembers()
	{
	
		$local_members = array(
				"promotion_id",
		);
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	
	public function getPromotionId()
	{
		return $this->promotion_id;
	}
	
	// TODO : impletement the function
	public function loadAll()
	{
	
		if($obj = $this->loadFromCache(self::CACHE_KEY_PREFIX_CUSTOMER_PROMO. $this->current_org_id ."##" .$this->user_id ))
		{
			
		}
		else
		{
			// load the customer points from string
		}
	}
	
	
}