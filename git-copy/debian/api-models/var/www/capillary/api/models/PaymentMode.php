<?php
include_once 'models/BaseModel.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'exceptions/ApiPaymentModeException.php';

/**
 * class PaymentMode
 *
 */
class PaymentMode extends BaseApiModel
{

	protected static $iterableMembers;

	protected $payment_mode_id;
	protected $name;
	protected $description;
	protected $is_valid;
	protected $added_on;
	protected $added_by;
	
	/**
	 * @var String: The variable hold the payment mode list as received from cache
	 * This reduced the number of hits to memcache and the payment modes are a small list; 
	 *  We might need remove the option if the attr list grows  
	 * 
	 */
	private static $__paymentModesStr;

	/**
	 * @var PaymentModeAttribute
	 */
	public $paymentModeAttributesArr;

	const CACHE_KEY_PREFIX = 'API_PM';
	const CACHE_KEY_ATTRIBUTES_LIST = 'API_PM_ATTR';
	

	protected $current_user_id;

	protected $db_master;
	
	public function __construct()
	{
		global $currentuser;
		parent::__construct(null);
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		
		$this->db_master = new Dbase( 'masters' );
		
		$classname = get_called_class();
		$classname::setIterableMembers();
	}
	
	public static function setIterableMembers()
	{
		$classname = get_called_class();
		$classname::$iterableMembers = array(
				"payment_mode_id",
				"name",
				"description",
				"is_valid",
				"added_on",
				"added_by",
		);
	}


	public function getPaymentModeId()
	{
		return $this->payment_mode_id;
	}
	
	public function setPaymentModeId($payment_mode_id)
	{
		$this->payment_mode_id = $payment_mode_id;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function setDescription($description)
	{
		$this->description = $description;
	}
	
	public function getIsValid()
	{
		return $this->is_valid;
	}
	
	public function setIsValid($is_valid)
	{
		$this->is_valid = $is_valid;
	}
	
	public function getAddedOn()
	{
		return $this->added_on;
	}
	
	public function setAddedOn($added_on)
	{
		$this->added_on = $added_on;
	}
	
	public function getAddedBy()
	{
		return $this->added_by;
	}
	
	public function getPaymentModeAttribute()
	{
		if(!$this->paymentModeAttributesArr)
		{
			$this->loadAttributes();
		}
		return $this->paymentModeAttributesArr;
	}
	
	public function setPaymentModeAttribute($attrs)
	{
		if(is_string($attrs))
			$attrs = $this->decodeFromString($attrs);
		
		$this->paymentModeAttributesArr = array();
		foreach($attrs as $attr)
		{
			if($attr instanceof PaymentModeAttribute)
				$this->paymentModeAttributesArr[] = $attr;
			else if(is_array($attr))
				$this->paymentModeAttributesArr[] = PaymentModeAttribute::fromArray(null, $attr);
			else if(is_string($attr))
				$this->paymentModeAttributesArr[] = PaymentModeAttribute::fromString(null, $attr);
		}
	}
	
	public function loadAttributes()
	{
		include_once 'models/PaymentModeAttribute.php';
		$cacheKey = $this->generateCacheKey(PaymentMode::CACHE_KEY_ATTRIBUTES_LIST, $this->payment_mode_id);
		
		if($str = self::getFromCache($cacheKey))
		{
			if($str == "null")
			{
				$this->logger->debug("No attributes available");
				$this->paymentModeAttributesArr = null;
				return $this->paymentModeAttributesArr;
			}
				
			$this->logger->debug("cache has the data");
			$array = $this->decodeFromString($str);
			$ret = array();
		
			foreach($array as $row)
			{
				$obj = PaymentModeAttributeModel::fromString(null, $row);
				$ret[] = $obj;
				//$this->logger->debug("data from cache" . $obj->toString());
			}
			
			$this->paymentModeAttributesArr = $ret;
			return $this->paymentModeAttributesArr;
		}
		
		$this->logger->debug("Going for DB now");
		
		try {
			$filters = new PaymentModeLoadFilters();
			$filters->payment_mode_id = $this->payment_mode_id;
			$this->paymentModeAttributesArr = PaymentModeAttributeModel::loadAll($filters);
			
			$cacheStringArr = array();
			foreach($this->paymentModeAttributesArr as $obj)
			{
				$cacheStringArr[] = $obj->toString();
			}
		
			if($cacheStringArr)
			{
				$this->logger->debug("saving the attributes to cache");
				$str = $this->encodeToString($cacheStringArr);
					
				$this->saveToCache($cacheKey, $str);
			}
		
		} catch (Exception $e) {
			$this->paymentModeAttributesArr = null;
			$this->saveToCache($cacheKey, "null");
			$this->logger->debug("No attr found");
		}
		
	}
	/**
	 *
	 *
	 * @return long
	 * @access public
	 */
	public function save() {
		// currently it should happen only from UI
		throw new ApiPaymentModeException(ApiPaymentModeException::FUNCTION_NOT_IMPLEMENTED);
	} // end of member function save
	
	/**
	 * Validates Credit Note data before saving
	 */
	public function validate()
	{
		throw new ApiPaymentModeException(ApiPaymentModeException::FUNCTION_NOT_IMPLEMENTED);
		//return true;
	}

	/**
	 *
	 *
	 * @return PaymentMode[]
	 * @static
	 * @access public
	 * 
	 *  Note : as the list of payment mode is going to be very less and filtering options are less 
	 *  will keep the whole object in cache
	 */
	public static function loadAll( $filters = null ) {

		global $logger;
		if(isset($filters) && !($filters instanceof PaymentModeLoadFilters))
		{
			throw new ApiPaymentModeException(ApiPaymentModeException::FILTER_INVALID_OBJECT_PASSED);
		}

		// trying cache
		$cacheKey = self::generateCacheKey(PaymentMode::CACHE_KEY_PREFIX, "", null);
		
		if(PaymentMode::$__paymentModesStr)
		{
			$str = PaymentMode::$__paymentModesStr; 
		}
		else 
		{
			$str = self::getFromCache($cacheKey);
			PaymentMode::$__paymentModesStr = $str;
		}
		
		if($str) // = self::getFromCache($cacheKey)
		{
			
			$logger->debug("cache has the data");
			$array = self::decodeFromString($str);
			$ret = array();
		
			foreach($array as $row)
			{
				$obj = PaymentMode::fromString(null, $row);
				$ret[$obj->getPaymentModeId()] = $obj;
				//$logger->debug("data from cache" . $obj->toString());
			}
				
			return $ret;
		}
		
		
		$sql = "SELECT 
			id AS payment_mode_id,
			`type` AS name,
			`description` AS description,
			`is_valid` AS is_valid,
			`added_on` AS added_on,
			`added_by` AS added_by
			FROM masters.payment_mode as pm 
			WHERE is_valid = 1 
			ORDER BY id ASC ";

		// no filter used here 
		$db_master = new Dbase("masters");
		$rows = $db_master->query($sql);
		
		if($rows)
		{
			$ret = array();
			$cacheStringArr="";
			foreach($rows AS $row)
			{
				$obj = PaymentMode::fromArray(null, $row);
				$ret[$obj->getPaymentModeId()] = $obj;
				$cacheStringArr[$obj->getPaymentModeId()] = $obj->toString();
				
			}
			
			if($cacheStringArr)
			{
				$logger->debug("saving the attributes to cache");
				$str = self::encodeToString($cacheStringArr);
				$cacheKey = self::generateCacheKey(PaymentMode::CACHE_KEY_PREFIX, "", null);
				self::saveToCache($cacheKey, $str);
			}
				
			return $ret;
		}
		throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);
	} // end of member function loadAll

	/**
	 * @return PaymentMode
	 * @static
	 * @access public
	 */
	public static function loadById( $id ) {
		
		global $logger;
		$logger->debug("Getting by Id ");
		 
		$paymentModesArr = self::loadAll(null);
		
		foreach($paymentModesArr as $paymentMode)
		{
			if($paymentMode->getPaymentModeId() == $id)
			{
				$logger->debug("Getting by Id has found the item ");
				return $paymentMode;
			}
		} 
		throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);
	} // end of member function loadById

	/**
	 * @return PaymentMode
	 * @static
	 * @access public
	 */
	public static function loadByName( $name ) {
	
		global $logger;
		$logger->debug("Getting by name");
		$name = strtoupper($name);
		
		$paymentModesArr = self::loadAll(null);
	
		foreach($paymentModesArr as $paymentMode)
		{
			if(strtoupper($paymentMode->getName()) == $name)
			{
				$logger->debug("Getting by Id has found the item ");
				return $paymentMode;
			}
		}
		throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);
	} // end of member function loadByType
	

} // end of PaymentMode
?>
