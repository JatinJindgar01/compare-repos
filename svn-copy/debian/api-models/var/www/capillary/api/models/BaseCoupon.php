<?php
include_once ("models/ICoupon.php");
include_once ("models/ICacheable.php");
include_once ("models/filters/CouponLoadFilters.php");

class BaseCoupon extends BaseApiModel implements  ICoupon, ICacheable{
	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $id;
	protected $code;
	protected $series_id;
	protected $org_id;
	protected $user_id;
	protected $created_by;
	protected $created_date;
	protected $pin_code;
	protected $current_user;
	protected $bill_number;
	protected $amount;
	protected $loyalty_log;
	protected $max_allowed_redemptions;
	
	protected $series;
	
	//TODO: need to check if we can cache this information
	CONST CACHE_TTL = 3600; // 1 hour
	
	CONST CACHE_KEY_PREFIX_ID = "CACHE_COUPON_ID#";
	CONST CACHE_KEY_PREFIX_CODE = "CACHE_COUPON_CODE#";
	
	protected static $iterableMembers = array();
	
	public function __construct($current_org_id)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
	
		$this->logger = $logger;
	
		// current org
		$this->current_org_id = $current_org_id;
	
		// db connection
		$this->db_campaigns = new Dbase( 'campaigns' );
	
		$className = get_called_class();
		$className::setIterableMembers();
	}
	
	public static function setIterableMembers()
	{
		//TODO: need to add few more members
		self::$iterableMembers = array(
				"id",
				"code",
				"series_id",
				"org_id",
				"user_id",
				"created_by",
				"created_date",
				"pin_code",
				"current_user",
				"bill_number",
				"amount",
				"loyalty_log",
				"max_allowed_redemptions"
		);
	}
	
	public static function loadById($org_id, $id)
	{
		global $logger;
		$logger->debug("Loading from coupon id");
		
		if(!$id)
		{
			throw new ApiCouponException(ApiCouponException::FILTER_ID_NOT_PASSED);
		}
		
		if(!$obj = self::loadFromCache($org_id, BaseCoupon::CACHE_KEY_PREFIX_ID.$org_id."##".$id))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");
		
			$filters = new CouponLoadFilters();
			$filters->id = $id;
			try{
				$array = self::loadAll($org_id, $filters, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}
		
			if($array)
			{
				return $array[0];
			}
			throw new ApiCouponException(ApiCouponException::FILTER_NON_EXISTING_ID_PASSED);
		
		}
		else
		{
			$logger->debug("Loading from the Cache was successful. returning");
			$obj = self::fromString($org_id, $obj);
			return $obj;
		}
	}
	
	public static function loadByCode($org_id, $code)
	{
		global $logger;
		$logger->debug("Loading from based on Coupon Code: $code");
	
		if(!$code)
		{
			throw new ApiCouponException(ApiCouponException::FILTER_CODE_NOT_PASSED);
		}
	
		if(!$obj = self::loadFromCache($org_id, BaseCoupon::CACHE_KEY_PREFIX_CODE.$org_id."##".$code))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");
	
			$filters = new CouponLoadFilters();
			$filters->code = $code;
			try{
				$array = self::loadAll($org_id, $filters, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}
	
			if($array)
			{
				return $array[0];//self::fromArray->($array[0])->toArray()
			}
			throw new ApiCouponException(ApiCouponException::FILTER_NON_EXISTING_CODE_PASSED);
	
		}
		else
		{
			$logger->debug("Loading from the Cache was successful. returning");
			$obj = self::fromString($org_id, $obj);
			return $obj;
		}
	}
	
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof CouponLoadFilters))
		{
			throw new ApiCouponException(ApiCouponException::FILTER_INVALID_OBJECT_PASSED);
		}
		$filter_sql = array();
		$columns = array();
		$columns[] = "v.voucher_id AS id";
		$columns[] = "v.voucher_code AS code";
		$columns[] = "v.org_id AS org_id";
		$columns[] = "v.voucher_series_id AS series_id";
		$columns[] = "v.issued_to AS user_id";
		$columns[] = "v.created_by AS created_by";
		$columns[] = "v.created_date AS created_date";
		$columns[] = "v.pin_code AS pin_code";
		$columns[] = "v.current_user AS current_user";
		$columns[] = "v.bill_number AS bill_number";
		$columns[] = "v.amount AS amount";
		$columns[] = "v.loyalty_log_ref_id AS loyalty_log_id";
		$columns[] = "v.max_allowed_redemptions AS max_allowed_redemptions";
		
		$sql = "SELECT ".implode(", ", $columns)." FROM voucher AS v
					WHERE v.org_id = $org_id ";
		
		if($filters->id)
			$filter_sql[] = " v.voucher_id = $filters->id";
		if($filters->code)
			$filter_sql[] = " v.voucher_code = $filters->code";
		if($filters->user_id)
			$filter_sql[] = " v.issued_to = $filters->user_id";
		
		//TODO for now using AND condition
		$sql = $filter_sql ? ($sql . "AND ( ".implode(" AND ", $filter_sql) . " ) ") : $sql;
		
		$sql .= " ORDER BY v.voucher_id desc ";
		
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		//print (str_replace("\t"," ", $sql))."\n\n";
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
						
				// Need to figure out how to save this into memcache for a customer
				/*if($obj->getUserId())
					$obj->saveToCache(BaseCoupon::CACHE_KEY_PREFIX_USER_ID.$org_id."##".$obj->getUserId(), $obj->toString());
					*/
				if($obj->getId())
					$obj->saveToCache(BaseCoupon::CACHE_KEY_PREFIX_ID.$org_id."##".$obj->getId(), $obj->toString());
				if($obj->getCode())
					$obj->saveToCache(BaseCoupon::CACHE_KEY_PREFIX_CODE.$org_id."##".$obj->getCode(), $obj->toString());
			}
			return $ret;
		}
		
		throw new ApiCouponException(ApiCouponException::NO_COUPONS_FOUND);
		return false;
	}
	
	

	public function getId()
	{
	    return $this->id;
	}

	public function setId($id)
	{
	    $this->id = $id;
	}

	public function getCode()
	{
	    return $this->code;
	}

	public function setCode($code)
	{
	    $this->code = $code;
	}

	public function getSeriesId()
	{
	    return $this->series_id;
	}

	public function setSeriesId($series_id)
	{
	    $this->series_id = $series_id;
	}

	public function getOrgId()
	{
	    return $this->org_id;
	}

	public function setOrgId($org_id)
	{
	    $this->org_id = $org_id;
	}

	public function getUserId()
	{
	    return $this->user_id;
	}

	public function setUserId($user_id)
	{
	    $this->user_id = $user_id;
	}

	public function getCreatedBy()
	{
	    return $this->created_by;
	}

	public function setCreatedBy($created_by)
	{
	    $this->created_by = $created_by;
	}

	public function getCreatedDate()
	{
	    return $this->created_date;
	}

	public function setCreatedDate($created_date)
	{
	    $this->created_date = $created_date;
	}

	public function getPinCode()
	{
	    return $this->pin_code;
	}

	public function setPinCode($pin_code)
	{
	    $this->pin_code = $pin_code;
	}

	public function getCurrentUser()
	{
	    return $this->current_user;
	}

	public function setCurrentUser($current_user)
	{
	    $this->current_user = $current_user;
	}

	public function getBillNumber()
	{
	    return $this->bill_number;
	}

	public function setBillNumber($bill_number)
	{
	    $this->bill_number = $bill_number;
	}

	public function getAmount()
	{
	    return $this->amount;
	}

	public function setAmount($amount)
	{
	    $this->amount = $amount;
	}

	public function getLoyaltyLog()
	{
	    return $this->loyalty_log;
	}

	public function setLoyaltyLog($loyalty_log)
	{
	    $this->loyalty_log = $loyalty_log;
	}

	public function getMaxAllowedRedemptions()
	{
	    return $this->max_allowed_redemptions;
	}

	public function setMaxAllowedRedemptions($max_allowed_redemptions)
	{
	    $this->max_allowed_redemptions = $max_allowed_redemptions;
	}
	
	public function getExpiryDate()
	{
		//TODO calculate expiry date
	}
	//will calculate discount amount and return
	public function getDiscountAmount()
	{
		//TODO: need to check
	}
	
	//for now this can return array
	//TODO: need to add filters like limit, order etc
	public function loadRedemptionDetails()
	{
		
	}
	
	public function getSeriesDetails()
	{
		if($this->series == NULL)
		{
			$this->loadSeries();
		}
		
		return $this->series;
	}
	
	private function loadSeries()
	{
		
	}	
	
	//this will redeem this coupon (before transaction redemption)
	//TODO: user_id will come in constructor
	public function redeem($user_id, $transaction_number = NULL, $transaction_amount = NULL, $date_time = NULL, $validation_code = NULL)
	{
		
	}
	
	public function isRedeemable($user_id, $bill_amount = NULL)
	{
		
	}
	
	public function getRedeemCount()
	{

	}
}	 
?>
