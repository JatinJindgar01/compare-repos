<?php
include_once 'models/BaseModel.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'exceptions/ApiPaymentModeException.php';
include_once 'models/PaymentMode.php';
/**
 * class PaymentMode
 *
 */
class PaymentModeAttributeModel extends BaseApiModel
{

	protected static $iterableMembers;

	protected $payment_mode_attribute_id;
	protected $payment_mode_id;
	protected $name;
	protected $is_valid;
	protected $last_updated_by;
	protected $last_updated_on;
	protected $data_type;
	protected $regex;
	protected $default_value;
	protected $error_msg;

	public $paymentModeObj;
	
	/**
	 * @var PaymentModeAttributePossibleValue[]
	 */
	public $paymentModeAttributePossibleValueArr;
	
	
	const CACHE_KEY_PREFIX_ID = 'API_PMA_ID';
	const CACHE_KEY_PREFIX_NAME = 'API_PMA_NAME';
	const CACHE_KEY_POSSIBLE_VALUE = 'API_PMA_PSBL';

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
				"payment_mode_attribute_id",
				"payment_mode_id",
				"name",
				"is_valid",
				"last_updated_by",
				"last_updated_on",
				"data_type",
				"regex",
				"default_value",
				"error_msg",
		);
	}
	public function getPaymentModeAttributeId()
	{
	    return $this->payment_mode_attribute_id;
	}

	public function setPaymentModeAttributeId($payment_mode_attribute_id)
	{
	    $this->payment_mode_attribute_id = $payment_mode_attribute_id;
	}

	public function getPaymentModeId()
	{
	    return $this->payment_mode_id;
	}

	public function setPaymentModeId($payment_mode_id)
	{
	    $this->payment_mode_id = $payment_mode_id;
	    
	    try {
	    	$this->paymentModeObj = PaymentMode::loadById($payment_mode_id);
	    } catch (Exception $e) {
	    	$this->logger->debug("Loading the payment mode object has failed");
	    	$this->paymentModeObj = null;
	    }
	     
	}
	
	public function getPaymentModeObj()
	{
		return $this->paymentModeObj;
	}

	public function getName()
	{
	    return $this->name;
	}

	public function setName($name)
	{
	    $this->name = $name;
	}

	public function getIsValid()
	{
	    return $this->is_valid;
	}

	public function setIsValid($is_valid)
	{
	    $this->is_valid = $is_valid;
	}

	public function getLastUpdatedBy()
	{
	    return $this->last_updated_by;
	}

	public function getLastUpdatedOn()
	{
	    return $this->last_updated_on;
	}

	public function getDataType()
	{
	    return $this->data_type;
	}

	public function setDataType($data_type)
	{
	    $this->data_type = $data_type;
	}

	public function getRegex()
	{
	    return $this->regex;
	}

	public function setRegex($regex)
	{
	    $this->regex = $regex;
	}

	public function getDefaultValue()
	{
	    return $this->default_value;
	}

	public function setDefaultValue($default_value)
	{
	    $this->default_value = $default_value;
	}

	public function getErrorMsg()
	{
	    return $this->errorMsg;
	}

	public function setErrorMsg($error_msg)
	{
	    $this->error_msg = $error_msg;
	}

	public function getPaymentModeAttributePossibleValueArr()
	{
		if(!$this->orgPaymentModeAttributePossibleValueArr && $this->data_type == "TYPED")
			$this->loadPossibleValues();
		
		return $this->paymentModeAttributePossibleValueArr;
	}
	
	public function setPaymentModeAttributePossibleValueArr($attrs)
	{
		if(is_string($attrs))
			$attrs = $this->decodeFromString($attrs);
	
		$this->paymentModeAttributePossibleValueArr = array();
		foreach($attrs as $attr)
		{
			if($attr instanceof OrgPaymentModeAttributePossibleValue)
				$this->paymentModeAttributePossibleValueArr[] = $attr;
			else if(is_array($attr))
				$this->paymentModeAttributePossibleValueArr[] = PaymentModeAttributePossibleValue::fromArray(null, $attr);
			else if(is_string($attr))
				$this->paymentModeAttributePossibleValueArr[] = PaymentModeAttributePossibleValue::fromString(null, $attr);
		}
	}
	

	/**
	 * @return OrgPaymentModeAttributePossibleValue[]
	 */
	public function loadPossibleValues()
	{
		include_once 'models/PaymentModeAttributePossibleValue.php';
		if($this->data_type != "TYPED")
		{
			$this->logger->debug("Its a free flowing text here");
			$this->orgPaymentModeAttributePossibleValueArr = null;
			return $this->orgPaymentModeAttributePossibleValueArr;
		}
	
		$cacheKey = $this->generateCacheKey(PaymentModeAttributeModel::CACHE_KEY_POSSIBLE_VALUE, $this->payment_mode_attribute_id);
	
		if($str = self::getFromCache($cacheKey))
		{
			if($str == "null")
			{
				$this->logger->debug("No possible values available");
				$this->paymentModeAttributePossibleValueArr = null;
				return $this->paymentModeAttributePossibleValueArr;
			}
				
			$this->logger->debug("cache has the data");
			
			$array = $this->decodeFromString($str);
			$ret = array();
			
			foreach($array as $row)
			{
				$obj = PaymentModeAttributePossibleValue::fromString(null, $row);
				$ret[] = $obj;
				//$this->logger->debug("data from cache" . $obj->toString());
			}
				
			$this->paymentModeAttributePossibleValueArr = $ret;
			return $this->paymentModeAttributePossibleValueArr;
		}
	
		$this->logger->debug("Going for DB now");
	
		try {
			$filters = new PaymentModeLoadFilters();
			$filters->payment_mode_attribute_id = $this->payment_mode_attribute_id;
			$this->paymentModeAttributePossibleValueArr = PaymentModeAttributePossibleValue::loadAll($filters);
				
			$cacheStringArr = array();
			foreach($this->paymentModeAttributePossibleValueArr as $obj)
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
			$this->paymentModeAttributePossibleValueArr = null;
			$this->saveToCache($cacheKey, "null");
			$this->logger->debug("No possible values found");
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
	 * @return PaymentModeAttribute[]
	 * @static
	 * @access public
	 */
	public static function loadAll( $filters = null ) {

		global $logger; 
		if(isset($filters) && !($filters instanceof PaymentModeLoadFilters))
		{
			throw new ApiPaymentModeException(ApiPaymentModeException::FILTER_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT 
			pma.id AS payment_mode_attribute_id,
			pma.payment_mode_id,
			pma.name,
			pma.is_valid,
			pma.last_updated_by,
			pma.last_updated_on,
			pma.data_type,
			pma.regex,
			pma.default_value,
			pma.error_msg
			FROM masters.payment_mode_attributes as pma 
			WHERE pma.is_valid = 1";
		
		if($filters->payment_mode_id)
			$sql .= " AND pma.payment_mode_id = $filters->payment_mode_id";
		if($filters->payment_mode_attribute_id)
			$sql .= " AND pma.id = $filters->payment_mode_attribute_id";
		if($filters->payment_mode_attribute_name)
			$sql .= " AND pma.name = '$filters->payment_mode_attribute_name'";		 
		$sql .= " ORDER BY pma.id ASC ";
		//print str_replace("\t", " ", $sql);
		// no filter used here 
		$db_master = new Dbase("masters");
		$rows = $db_master->query($sql);
		
		if($rows)
		{
			$ret = array();
			$cacheStringArr="";
			foreach($rows AS $row)
			{
				$obj = PaymentModeAttributeModel::fromArray(null, $row);
				$ret[] = $obj;
				
				$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID, $obj->getPaymentModeAttributeId(), null);
				$cacheKeyName = self::generateCacheKey(self::CACHE_KEY_PREFIX_NAME, $obj->getName()."##".$obj->getPaymentModeId(), null);
				$str = $obj->toString();
				self::saveToCache($cacheKey, $str);
				self::saveToCache($cacheKeyName, $str);
			}
			
			return $ret;
		}
		throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_ATTR_MATCHES);
	} // end of member function loadAll

	/**
	 * @return PaymentModeAttribute
	 * @static
	 * @access public
	 */
	public static function loadById( $id ) {
		
		global $logger;
		
		if(!$id)
			throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);
		
		$cacheKey = self::generateCacheKey(PaymentModeAttributeModel::CACHE_KEY_PREFIX_ID, $id, null);
		if(!$obj = self::loadFromCache(null, $cacheKey))
		{
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->payment_mode_attribute_id = $id;
			$paymentModeAttrArr = self::loadAll($filter);
			return $paymentModeAttrArr[0];
		}
		
		// returning from
		$logger->debug("Loading from cache");
		return $obj;
		
	} 


	/**
	 * @return PaymentModeAttributeModel
	 * @static
	 * @access public
	 */
	public static function loadByName( $name, $payment_mode_id) {
	
		global $logger;
		
		if(!$name || !$payment_mode_id )
			throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_ATTR_MATCHES);
	
		$cacheKey = self::generateCacheKey(PaymentModeAttributeModel::CACHE_KEY_PREFIX_NAME, $name ."##".$payment_mode_id, null);
		if(!$obj = self::loadFromCache(null, $cacheKey))
		{
			
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->payment_mode_id = $payment_mode_id;
			$filter->payment_mode_attribute_name = $name;
			$paymentModeAttrArr = self::loadAll($filter);
			
			return $paymentModeAttrArr[0];
		}
		// returning from
		$logger->debug("Loading from cache");
		return $obj;
	
	}
	
} // end of PaymentModeAttribute
?>
