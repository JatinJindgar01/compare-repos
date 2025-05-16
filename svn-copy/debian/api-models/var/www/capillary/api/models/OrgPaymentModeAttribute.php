<?php
include_once 'models/BaseModel.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'models/PaymentMode.php';
include_once 'models/OrgPaymentMode.php';
include_once 'models/PaymentModeAttribute.php';
include_once 'models/OrgPaymentModeAttributePossibleValue.php';
/**
 * class PaymentMode
 *
 */
class OrgPaymentModeAttribute extends BaseApiModel
{

	protected static $iterableMembers;

	protected $org_payment_mode_attribute_id;
	protected $payment_mode_attribute_id;
	protected $org_payment_mode_id;
	protected $name;
	protected $is_valid;
	protected $last_updated_by;
	protected $last_updated_on;
	protected $data_type;
	protected $regex;
	protected $default_value;
	protected $error_msg;
	protected $is_pii_data;

	public $paymentModeObj;
	public $paymentModeAttributeObj;
	public $orgPaymentModeObj;

	/**
	 * @var OrgPaymentModeAttributePossibleValue[]
	 */
	public $orgPaymentModeAttributePossibleValueArr;

	const CACHE_KEY_PREFIX_ID = 'API_OPMA_ID';
	const CACHE_KEY_PREFIX_NAME = 'API_OPMA_ATTR_NAME';
	const CACHE_KEY_PREFIX_PM_ATTR_NAME = 'API_OPMA_PMA_NAME';
	const CACHE_KEY_POSSIBLE_VALUE = 'API_OPMA_PSBL';
	CONST CACHE_TTL = 432000; // 5 day

	protected $current_user_id;

	protected $db_master;

	public function __construct($current_org_id)
	{
		global $currentuser;
		parent::__construct($current_org_id);

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
				"org_payment_mode_attribute_id",
				"payment_mode_attribute_id",
				"org_payment_mode_id",
				"name",
				"is_valid",
				"last_updated_by",
				"last_updated_on",
				"data_type",
				"regex",
				"default_value",
				"error_msg",
				"is_pii_data",
		);
	}
	public function getOrgPaymentModeAttributeId()
	{
		return $this->org_payment_mode_attribute_id;
	}

	public function setOrgPaymentModeAttributeId($org_payment_mode_attribute_id)
	{
		$this->org_payment_mode_attribute_id = $org_payment_mode_attribute_id;
	}

	public function getPaymentModeAttributeId()
	{
	    return $this->payment_mode_attribute_id;
	}

	public function setPaymentModeAttributeId($payment_mode_attribute_id)
	{
		$this->paymentModeAttributeObj = null;
	    $this->payment_mode_attribute_id = $payment_mode_attribute_id;

	    try {
	    	if($payment_mode_attribute_id > 0)
	    		$this->paymentModeAttributeObj = PaymentModeAttributeModel::loadById($payment_mode_attribute_id);
	    } catch (Exception $e) {
	    	$this->paymentModeAttributeObj = null;
	    }
	}

	public function getPaymentModeAttributeObj()
	{
		return $this->paymentModeAttributeObj;
	}


	public function getOrgPaymentModeId()
	{
		return $this->org_payment_mode_id;
	}

	public function setOrgPaymentModeId($org_payment_mode_id)
	{
		$this->org_payment_mode_id = $org_payment_mode_id;

		try {
			$this->orgPaymentModeObj = OrgPaymentMode::loadById($this->current_org_id, $org_payment_mode_id);
		} catch (Exception $e) {
			$this->orgPaymentModeObj = null;
		}

	}

	public function getOrgPaymentModeObj()
	{
		return $this->orgPaymentModeObj;
	}

	public function getOrgPaymentModeAttributePossibleValueArr()
	{
		if(!$this->orgPaymentModeAttributePossibleValueArr && $this->data_type == "TYPED")
			$this->loadPossibleValues();

		return $this->orgPaymentModeAttributePossibleValueArr;
	}

	public function setOrgPaymentModeAttributePossibleValueArr($attrs)
	{
		if(is_string($attrs))
			$attrs = $this->decodeFromString($attrs);

		$this->orgPaymentModeAttributePossibleValueArr = array();
		foreach($attrs as $attr)
		{
			if($attr instanceof OrgPaymentModeAttributePossibleValue)
				$this->orgPaymentModeAttributePossibleValueArr[] = $attr;
			else if(is_array($attr))
				$this->orgPaymentModeAttributePossibleValueArr[] = OrgPaymentModeAttributePossibleValue::fromArray($this->current_org_id, $attr);
			else if(is_string($attr))
				$this->orgPaymentModeAttributePossibleValueArr[] = OrgPaymentModeAttributePossibleValue::fromString($this->current_org_id, $attr);
		}
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

	public function getIsPiiData()
    {
    	return $this->is_pii_data;
    }

    public function setIsPiiData($is_pii_data)
    {
    	$this->is_pii_data = $is_pii_data;
    }



	/**
	 *
	 * @return long
	 * @access public
	 */
	public function save() {

		$this->logger->debug("Saving new org payment attributes");

		$columns["is_valid"] = !isset($this->is_valid) || $this->is_valid ? 1 : 0;
		if(isset($this->payment_mode_attribute_id))
			$columns["payment_mode_attribute_id"]= $this->payment_mode_attribute_id;
		if(isset($this->name))
			$columns["label"]= "'".$this->db_master->realEscapeString($this->name)."'";
		if(isset($this->data_type))
			$columns["data_type"]= "'".$this->data_type."'";
		if(isset($this->regex))
			$columns["regex"]= "'".$this->db_master->realEscapeString($this->regex)."'";
		if(isset($this->default_value))
			$columns["default_value"]= "'".$this->db_master->realEscapeString($this->default_value)."'";
		if(isset($this->error_msg))
			$columns["error_msg"]= "'".$this->db_master->realEscapeString($this->error_msg)."'";
		if(isset($this->is_pii_data))
            $columns["is_pii_data"]= $this->is_pii_data;

		$columns["last_updated_by"]= $this->current_user_id;
		$columns["last_updated_on"]= "'".Util::getMysqlDateTime($this->last_updated_on ? $this->last_updated_on : 'now')."'";

		if(!$this->org_payment_mode_attribute_id && !$this->payment_mode_attribute_id)
		{
			$sql = "select id from org_payment_mode_attributes
					WHERE org_id = $this->current_org_id
					AND label = '$this->name' AND payment_mode_attribute_id is null
					AND org_payment_mode_id =  $this->org_payment_mode_id";
			$newId = $this->db_master->query_firstrow($sql);
			$newId = $newId["id"];
			if($newId)
				$this->org_payment_mode_attribute_id = $newId;

		}

		// new payment mode
		if(!$this->org_payment_mode_attribute_id)
		{
			$this->logger->debug("org_payment_mode_attribute_id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["org_payment_mode_id"]= $this->org_payment_mode_id;

			$sql = "INSERT IGNORE INTO org_payment_mode_attributes ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ";
			$sql .= "\n ON DUPLICATE KEY UPDATE is_valid = VALUES(is_valid), is_pii_data = VALUES(is_pii_data),
					label = values(label), data_type = values(data_type),
					regex = values(regex), default_value = values(default_value),error_msg = values(error_msg),
					last_updated_on = values(last_updated_on), last_updated_by = values(last_updated_by)
					 ";

			$newId = $this->db_master->insert($sql);

			$this->logger->debug("Return of saving the payment details $newId");

			if($newId > 0)
				$this->org_payment_mode_attribute_id = $newId;
		}
		else
		{
			$this->logger->debug("Updation is org attribute");
			$sql = "UPDATE org_payment_mode_attributes SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->org_payment_mode_attribute_id and org_id = $this->current_org_id ";
			$newId = $this->db_master->update($sql);

		}

		$cacheKey = $this->generateCacheKey(OrgPaymentModeAttribute::CACHE_KEY_PREFIX_ID, $this->org_payment_mode_attribute_id, $this->current_org_id);
		$cacheKeyName = self::generateCacheKey(self::CACHE_KEY_PREFIX_NAME, strtoupper($this->getName())."##".$this->getOrgPaymentModeId(), $this->current_org_id);
		$cacheKeyAttr = $this->generateCacheKey(OrgPaymentModeAttribute::CACHE_KEY_POSSIBLE_VALUE, $this->org_payment_mode_attribute_id, $this->current_org_id);

		// this will update the cache
		self::deleteValueFromCache($cacheKey);
		self::deleteValueFromCache($cacheKeyName);
		self::deleteValueFromCache($cacheKeyAttr);

		try {

			$obj = OrgPaymentModeAttribute::loadById($this->current_org_id, $this->org_payment_mode_attribute_id);

		} catch (Exception $e) {
			$this->logger->debug("The payment mode is not found");
		}


		foreach ($this->orgPaymentModeAttributePossibleValueArr as $attr)
		{

			try {
				if($obj->getPaymentModeAttributeId())
				{
					$paymentModePossibleValueObj = PaymentModeAttributePossibleValue::loadByValue($attr->getValue(), $obj->getPaymentModeAttributeId());
					$attr->setPaymentModeIdAttributeIdPossibleValueId($paymentModePossibleValueObj->getPaymentModeAttributePossibleValueId());
				}
			} catch (Exception $e) {
			}
			try {
				// set the id so thats its an update
 				$existingValue = OrgPaymentModeAttributePossibleValue::loadByValue($this->current_org_id, $attr->getValue(),$this->org_payment_mode_attribute_id );
 				$attr->setOrgPaymentModeAttributePossibleValueId($existingValue->getOrgPaymentModeAttributePossibleValueId()) ;
 			} catch (Exception $e) {
 			}
 			$this->logger->debug("Value deosnot exists");
 			$attr->setOrgPaymentModeId($this->org_payment_mode_id);
 			$attr->setOrgPaymentModeAttributeId($this->org_payment_mode_attribute_id);
 			$attr->save();
		}


		// update the cache if required
		if($obj && $obj->getDataType() == "TYPED")
		{
			$obj->loadPossibleValues();
		}

	} // end of member function save

	/**
	 * Validates Credit Note data before saving
	 */
	public function validate()
	{

		include_once 'models/validators/PaymentModeValidatorsLoader.php';

		// get the validator
		$validatorsArr = PaymentModeValidatorsLoader::getValidators(__CLASS__, $this->current_org_id, $this);

		// loop for all the validators
		foreach($validatorsArr as $validator)
		{
			$this->logger->debug("Validating on ". get_class($validator));
			$validator->validate();
		}

		//print_r(get_class($this->orgPaymentModeAttributePossibleValueArr[2]->orgPaymentModeAttributeObj));print __FILE__."\n";
		foreach($this->orgPaymentModeAttributePossibleValueArr as $value)
		{
			$value->validate();
		}
		//return true;
	}

	/**
	 *
	 *
	 * @return PaymentModeAttribute[]
	 * @static
	 * @access public
	 */
	public static function loadAll( $org_id, $filters = null ) {

		global $logger;
		if(isset($filters) && !($filters instanceof PaymentModeLoadFilters))
		{
			throw new ApiPaymentModeException(ApiPaymentModeException::FILTER_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT
				opma.id as org_payment_mode_attribute_id,
				opma.payment_mode_attribute_id as payment_mode_attribute_id,
				opma.org_payment_mode_id,
				opma.label as name,
				opma.is_valid,
				opma.last_updated_by,
				opma.last_updated_on,
				opma.data_type,
				opma.regex,
				opma.default_value,
				opma.error_msg,
				opma.is_pii_data,
				pma.name as payment_mode_attribute_name
			FROM masters.org_payment_mode_attributes as opma
			LEFT JOIN masters.payment_mode_attributes as pma
				ON pma.id = opma.payment_mode_attribute_id
			WHERE opma.org_id = $org_id ";
				
		if(!$filters->include_deleted_attributes)
			$sql .= " AND opma.is_valid = 1";
		if($filters->org_payment_mode_attribute_id)
			$sql .= " AND opma.id = $filters->org_payment_mode_attribute_id";
		if($filters->org_payment_mode_id)
			$sql .= " AND opma.org_payment_mode_id = $filters->org_payment_mode_id";
		if($filters->payment_mode_attribute_id)
			$sql .= " AND opma.payment_mode_attribute_id = $filters->payment_mode_attribute_id";
		if($filters->org_payment_mode_attribute_name)
			$sql .= " AND opma.label = '$filters->org_payment_mode_attribute_name'";
		if($filters->payment_mode_attribute_name)
			$sql .= " AND pma.name = '$filters->payment_mode_attribute_name'";
		
		$sql .= " ORDER BY opma.id ASC ";
		## print str_replace("\t", " ", $sql);
		// no filter used here 
		$db_master = new Dbase("masters");
		$rows = $db_master->query($sql);
		$ret = array();
		
		if($rows)
		{
			$cacheStringArr="";
			foreach($rows AS $row)
			{
				$obj = OrgPaymentModeAttribute::fromArray($org_id, $row);
				$ret[] = $obj;

				$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID, $obj->getOrgPaymentModeAttributeId(), $org_id);
				$cacheKeyName = self::generateCacheKey(self::CACHE_KEY_PREFIX_NAME, strtoupper($obj->getName())."##".$obj->getOrgPaymentModeId(), $org_id);
				$cacheKeyPMAttrName = self::generateCacheKey(self::CACHE_KEY_PREFIX_PM_ATTR_NAME, strtoupper($row["payment_mode_attribute_name"])."##".$obj->getOrgPaymentModeId(), $org_id);
				$str = $obj->toString();
				
				self::saveToCache($cacheKey, $str);
				self::saveToCache($cacheKeyName, $str);

				if($row["payment_mode_attribute_name"])
					self::saveToCache($cacheKeyPMAttrName, $str);
				
			}
			
			return $ret;
		}
		throw new ApiPaymentModeException(ApiPaymentModeException::NO_ORG_PAYMENT_ATTR_VALUE_MATCHES);
	} // end of member function loadAll

	
	/**
	 * @return OrgPaymentModeAttribute
	 * @static
	 * @access public
	 */
	public static function loadById( $org_id, $id, $include_deleted_attributes=false ) {
		
		global $logger;
		
		$cacheKey = self::generateCacheKey(OrgPaymentModeAttribute::CACHE_KEY_PREFIX_ID, $id, $org_id);
		
		if(!$obj = self::loadFromCache($org_id, $cacheKey))
		{
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->org_payment_mode_attribute_id = $id;
			if($include_deleted_attributes)
				$filter->include_deleted_attributes = true;
			$paymentModeAttrArr = self::loadAll($org_id, $filter);
			return $paymentModeAttrArr[0];
		}
		
		// returning from
		$logger->debug("Loading from cache");
		return $obj;
		
	} 


	/**
	 * @return OrgPaymentModeAttribute
	 * @static
	 * @access public
	 */
	public static function loadByName( $org_id, $name, $org_payment_mode_id) {
	
		global $logger;
		
		$cacheKey = self::generateCacheKey(OrgPaymentModeAttribute::CACHE_KEY_PREFIX_NAME, strtoupper($name) ."##".$org_payment_mode_id, $org_id);
		
		if(!$obj = self::loadFromCache($org_id, $cacheKey))
		{
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->org_payment_mode_id= $org_payment_mode_id;
			$filter->org_payment_mode_attribute_name = $name;
			$paymentModeAttrArr = self::loadAll($org_id, $filter);
			
			return $paymentModeAttrArr[0];
		}
		
		// returning from
		$logger->debug("Loading from cache");
		return $obj;
	
	}

	/**
	 * @return OrgPaymentModeAttribute
	 * @static
	 * @access public
	 */
	public static function loadByPaymentModeAttributeName( $org_id, $name, $org_payment_mode_id) {
	
		global $logger;
	
		if(!$name || !$org_payment_mode_id || !$org_id)
			throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_ATTR_MATCHES);
		
		$cacheKey = self::generateCacheKey(OrgPaymentModeAttribute::CACHE_KEY_PREFIX_PM_ATTR_NAME, strtoupper($name) ."##".$org_payment_mode_id, $org_id);
		if(!$obj = self::loadFromCache($org_id, $cacheKey))
		{
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->org_payment_mode_id= $org_payment_mode_id;
			$filter->payment_mode_attribute_name = $name;
			$paymentModeAttrArr = self::loadAll($org_id, $filter);
			
			return $paymentModeAttrArr[0];
		}
		// returning from
		$logger->debug("Loading from cache");
		return $obj;
	
	}
	
	
	/**
	 * @return OrgPaymentModeAttributePossibleValue[]
	 */
	public function loadPossibleValues()
	{
		if($this->data_type != "TYPED")
		{
			$this->logger->debug("Its a free flowing text here");
			$this->orgPaymentModeAttributePossibleValueArr = null;
			return $this->orgPaymentModeAttributePossibleValueArr;
		}

		$cacheKey = $this->generateCacheKey(OrgPaymentModeAttribute::CACHE_KEY_POSSIBLE_VALUE, $this->org_payment_mode_attribute_id, $this->current_org_id);
		
		if($str = self::getFromCache($cacheKey))
		{
			if($str == "null")
			{
				$this->logger->debug("No possible values available");
				$this->orgPaymentModeAttributePossibleValueArr = null;
				return $this->orgPaymentModeAttributePossibleValueArr;
			}
				
			$this->logger->debug("cache has the data");
			$array = $this->decodeFromString($str);
			$ret = array();
		
			foreach($array as $row)
			{
				$obj = OrgPaymentModeAttributePossibleValue::fromString($this->current_org_id, $row);
				$ret[] = $obj;
				//$this->logger->debug("data from cache" . $obj->toString());
			}
			
			$this->orgPaymentModeAttributePossibleValueArr = $ret;
			return $this->orgPaymentModeAttributePossibleValueArr;
		}
		
		$this->logger->debug("Going for DB now");
		
		try {
			$filters = new PaymentModeLoadFilters();
			$filters->org_payment_mode_attribute_id = $this->org_payment_mode_attribute_id;
			$this->orgPaymentModeAttributePossibleValueArr = OrgPaymentModeAttributePossibleValue::loadAll($this->current_org_id, $filters);
			
			$cacheStringArr = array();
			foreach($this->orgPaymentModeAttributePossibleValueArr as $obj)
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
			$this->orgPaymentModeAttributePossibleValueArr = null;
			$this->saveToCache($cacheKey, "null");
			$this->logger->debug("No possible values found");
		}
		
	}
} // end of PaymentModeAttribute
?>
