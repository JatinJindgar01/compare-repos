<?php
include_once ("models/BaseRedemption.php");

class CouponRedemption extends BaseRedemption
{
	protected $amount;
	protected $coupon_id;
	protected $series_id;
	
	
	protected $db_users;
	
	CONST CACHE_KEY_PREFIX_COUPON_REDEEM_USER_ID = "CACHE_COUPON_REDEEM_USER_ID#";
	
	function __construct($org_id)
	{
		parent::__construct(REDEMPTION_TYPE_COUPON, $org_id);
		$this->db_users = new Dbase("campaigns");
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
				"amount",
				"coupon_id",
				"series_id"
		);
		
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	
	public function getPoints()
	{
	    return $this->points;
	}

	public function setPoints($points)
	{
	    $this->points = $points;
	}
	
	public static function loadByCouponId($coupon_id)
	{
		global $logger;
		$logger->debug("Loading from user id");
		
		if(!$coupon_id)
		{
			throw new ApiRedemptionException(ApiRedemptionException::FILTER_NO_COUPON_ID_PASSED);
		}
		
		$filters = new RedemptionLoadFilter();
		$filters->coupon_id = $coupon_id;
		
		$array = self::loadAll($org_id, $filters);
		
		return $array;
	}
	
	public static function loadByUserId($user_id)
	{
		global $logger;
		$logger->debug("Loading from user id");
	
		if(!$user_id)
		{
			throw new ApiRedemptionException(ApiRedemptionException::FILTER_NO_USER_ID_PASSED);
		}
	
		$filters = new RedemptionLoadFilter();
		$filters->user_id = $user_id;
		
		$array = self::loadAll($org_id, $filters);
	
		return $array;
	}
	
	//TODO: need to add coupon_id
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof RedemptionLoadFilter))
		{
			throw new ApiRedemptionException(ApiRedemptionException::NO_FILTER_PASSED);
		}
		
		$columns = array();
		$columns[] = "vr.id";
		$columns[] = "vr.org_id";
		$columns[] = "vr.used_by AS redeemed_by";
		$columns[] = "vr.voucher_id as coupon_id";
		$columns[] = "vr.validation_code_used AS validation_code";
		$columns[] = "vr.bill_number AS transaction_number";
		$columns[] = "vr.details AS notes";
		$columns[] = "vr.used_at_store AS redeemed_at";
		$columns[] = "vr.used_date AS redemption_date";
		$columns[] = "vr.voucher_series_id AS series_id";
		
		$sql = "SELECT ".implode(", ",$columns)." FROM voucher_redemptions AS vr WHERE vr.org_id = $org_id";
		
		if($filters->user_id)
			$filter_sql[] = " vr.used_by = $filters->user_id";
		if($filters->coupon_id)
			$filter_sql[] = " vr.voucher_id = $filters->coupon_id";
		
		//TODO for now using AND condition
		$sql = $filter_sql ? ($sql . "AND ( ".implode(" AND ", $filter_sql) . " ) ") : $sql;
		
		$sql .= " ORDER BY vr.id desc ";
		
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		$sql = $sql . " LIMIT $offset, $limit";
		
		$db = new Dbase( 'campaigns' );
		$array = $db->query($sql);
		
		if($array)
		{
			$ret = array();
			foreach($array as $row)
			{
				$classname= get_called_class();
				$obj = $classname::fromArray($org_id, $row);
				$ret[] = $obj;
				
				if($obj->getId())
					$obj->saveToCache(BaseRedemption::CACHE_KEY_PREFIX_ID.$org_id."##".$obj->getId(), $obj->toString());
			}
			return $ret;
		}
		
		throw new ApiRedemptionException(ApiRedemptionException::NO_REDEMPTION_FOUND);
		return false;
	}
}
?>