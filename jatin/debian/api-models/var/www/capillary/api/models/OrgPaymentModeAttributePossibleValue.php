<?php
include_once 'models/BaseModel.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'models/OrgPaymentMode.php';
include_once 'models/PaymentModeAttributePossibleValue.php';
/**
 * class OrgPaymentModeAttributePossibleValue
 *
 */
class OrgPaymentModeAttributePossibleValue extends BaseApiModel
{

	protected static $iterableMembers;

	protected $org_payment_mode_attribute_possible_value_id;
	protected $org_payment_mode_id;
	protected $org_payment_mode_attribute_id;
	protected $payment_mode_attribute_possible_value_id;
	protected $value;
	protected $is_valid;
	protected $added_by;
	protected $added_on;

	public $orgPaymentModeObj;
	public $orgPaymentModeAttributeObj;
	public $paymentModeAttributePossibleValueObj;
	
	// caching will be done only at the OrgPaymentModeAttributes level
 	const CACHE_KEY_PREFIX_VALUE = 'OPMAV_VAL';

	protected $current_user_id;

	protected $db_masters;
	
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
				"org_payment_mode_attribute_possible_value_id",
				"org_payment_mode_id",
				"org_payment_mode_attribute_id",
				"payment_mode_attribute_possible_value_id",
				"value",
				"is_valid",
				"added_by",
				"added_on",
		);
	}
	public function getOrgPaymentModeAttributePossibleValueId()
	{
	    return $this->org_payment_mode_attribute_possible_value_id;
	}

	public function setOrgPaymentModeAttributePossibleValueId($org_payment_mode_attribute_possible_value_id)
	{
	    $this->org_payment_mode_attribute_possible_value_id = $org_payment_mode_attribute_possible_value_id;
	}

	public function getOrgPaymentModeId()
	{
	    return $this->org_payment_mode_id;
	}

	public function setOrgPaymentModeId($org_payment_mode_id)
	{
		$this->orgPaymentModeObj = null;
	    $this->org_payment_mode_id = $org_payment_mode_id;
	    try {
	    	if($org_payment_mode_id > 0)
	    		$this->orgPaymentModeObj = OrgPaymentMode::loadById($this->current_org_id, $org_payment_mode_id);
	    } catch (Exception $e) {
	    	$this->orgPaymentModeObj = null;
	    }
	     
	}

	public function getOrgPaymentModeIdAttributeId()
	{
	    return $this->org_payment_mode_attribute_id;
	}

	public function setOrgPaymentModeAttributeId($org_payment_mode_attribute_id)
	{
		$this->orgPaymentModeAttributeObj  = null;
	    $this->org_payment_mode_attribute_id = $org_payment_mode_attribute_id;
	    
	    try {
	    	if($org_payment_mode_attribute_id > 0)
	    		$this->orgPaymentModeAttributeObj = OrgPaymentModeAttribute::loadById($this->current_org_id, $org_payment_mode_attribute_id);
	    } catch (Exception $e) {
	    	$this->orgPaymentModeAttributeObj = null;
	    }
	     
	}

	public function getPaymentModeIdAttributePossibleValueId()
	{
	    return $this->payment_mode_attribute_possible_value_id;
	}

	public function setPaymentModeIdAttributeIdPossibleValueId($payment_mode_attribute_possible_value_id)
	{
		$this->paymentModeAttributePossibleValueObj = null;
	    $this->payment_mode_attribute_possible_value_id = $payment_mode_attribute_possible_value_id;
	}

	public function getPaymentModeIdAttributeIdPossibleValueObj()
	{
		if(!$this->paymentModeAttributePossibleValueObj)
			$this->loadPaymentModeIdAttributeIdPossibleValueObj();
		
		return $this->payment_mode_attribute_possible_value_id;
	}
	
	public function loadPaymentModeIdAttributeIdPossibleValueObj()
	{
		try {
			if($this->payment_mode_attribute_possible_value_id > 0)
				$this->paymentModeAttributePossibleValueObj = PaymentModeAttributePossibleValue::loadById($this->current_org_id, $this->payment_mode_attribute_possible_value_id);
		} catch (Exception $e) {
			$this->paymentModeAttributePossibleValueObj = null;
		}
	}

	public function getValue()
	{
	    return $this->value;
	}

	public function setValue($value)
	{
	    $this->value = $value;
	}

	public function getIsValid()
	{
		return $this->is_valid;
	}
	
	public function setIsValid($is_valid)
	{
		$this->is_valid = $is_valid;
	}
	
	public function getAddedBy()
	{
	    return $this->added_by;
	}

	public function getAddedOn()
	{
	    return $this->added_on;
	}


	/**
	 *
	 *
	 * @return long
	 * @access public
	 */
	public function save() {

		$this->logger->debug("Saving the new attr values if required");
		
		$columns["org_payment_mode_id"]= $this->org_payment_mode_id;
		$columns["org_payment_mode_attribute_id"]= $this->org_payment_mode_attribute_id;
		$columns["value"]= "'".$this->db_master->realEscapeString($this->value)."'";
		$columns["is_valid"] = !isset($this->is_valid) || $this->is_valid ? 1 : 0; 
		
		if(isset($this->payment_mode_attribute_possible_value_id))
			$columns["payment_mode_attribute_possible_value_id"]= $this->payment_mode_attribute_possible_value_id;
		 
		// new user
		if(!$this->org_payment_mode_attribute_possible_value_id)
		{
			$this->logger->debug("Adding new value to the possible list");
			$columns["added_on"]= "'".Util::getMysqlDateTime($this->added_on ? $this->added_on : 'now')."'";
			$columns["added_by"]= $this->current_user_id;
			$columns["org_id"]= $this->current_org_id;
		
			$sql = "INSERT IGNORE INTO org_payment_mode_attribute_possible_values ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ";
			$sql .= "\n ON DUPLICATE KEY UPDATE is_valid = VALUES(is_valid) ";
			$newId = $this->db_master->insert($sql);
		
			$this->logger->debug("Return of saving the posible value is $newId");
		
			if($newId > 0)
				$this->org_payment_mode_attribute_possible_value_id = $newId;
			else 
			{
				try {
					$objByValue = OrgPaymentModeAttributePossibleValue::loadByValue($this->current_org_id, $this->value, $this->org_payment_mode_attribute_id);
					$this->org_payment_mode_attribute_possible_value_id = $objByValue->getOrgPaymentModeAttributePossibleValueId();
				} catch (Exception $e) {}
				
			}
		}
		else
		{
			$this->logger->debug("Updation is org attribute possible values");
			$sql = "UPDATE org_payment_mode_attribute_possible_values SET ";
			
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
			
			// remove the extra comma
			$sql=substr($sql,0,-2);
			
			$sql .= " WHERE id = $this->org_payment_mode_attribute_possible_value_id";
			$newId = $this->db_master->update($sql);
			
		}

		if($this->org_payment_mode_attribute_possible_value_id)
		{
			return true;
		}
		else
		{
			throw new ApiPaymentModeException(ApiPaymentModeException::SAVING_DATA_FAILED);
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
		
	}

	/**
	 *
	 *
	 * @return PaymentModeAttribute[]
	 * @static
	 * @access public
	 */
	public static function loadAll( $org_id, $filters = null , $limit=100, $offset =0 ) {

		global $logger; 
		if(isset($filters) && !($filters instanceof PaymentModeLoadFilters))
		{
			throw new ApiPaymentModeException(ApiPaymentModeException::FILTER_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT
				opmapv.id as org_payment_mode_attribute_possible_value_id,
				opmapv.org_payment_mode_id as org_payment_mode_id,
				opmapv.org_payment_mode_attribute_id,
				opmapv.payment_mode_attribute_possible_value_id,
				opmapv.value,
				opmapv.added_on,
				opmapv.added_by
			FROM org_payment_mode_attribute_possible_values as opmapv 
			WHERE opmapv.org_id = $org_id and opmapv.is_valid = 1 ";
		
		if($filters->org_payment_mode_attribute_possible_value_id)
			$sql .= " AND opmapv.id = $filters->org_payment_mode_attribute_possible_value_id";
		if($filters->org_payment_mode_attribute_possible_value)
			$sql .= " AND opmapv.value = '$filters->org_payment_mode_attribute_possible_value' ";
		if($filters->org_payment_mode_id)
			$sql .= " AND opmapv.org_payment_mode_id = $filters->org_payment_mode_id";
		if($filters->org_payment_mode_attribute_id)
			$sql .= " AND opmapv.org_payment_mode_attribute_id = $filters->org_payment_mode_attribute_id";
		$sql .= " ORDER BY opmapv.id ASC ";
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		$sql = $sql . " LIMIT $offset, $limit";
		
		## print "\n\n".str_replace("\t", " ", $sql);
		// no filter used here 
		$db_masters = new Dbase("masters");
		$rows = $db_masters->query($sql);
		
		if($rows)
		{
			$ret = array();
			$cacheStringArr="";
			foreach($rows AS $row)
			{
				$obj = OrgPaymentModeAttributePossibleValue::fromArray($org_id, $row);
				$ret[] = $obj;
				
				$cacheKeyValue = self::generateCacheKey(self::CACHE_KEY_PREFIX_VALUE, strtoupper($obj->getValue())."##".$obj->getOrgPaymentModeIdAttributeId(), $org_id);
				$str = $obj->toString();
				
				self::saveToCache($cacheKeyValue, $str);
			}
			return $ret;
		}
		throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);
	} // end of member function loadAll

	/**
	 * @return PaymentModeAttribute
	 * @static
	 * @access public
	 */
	public static function loadById( $org_id, $id ) {
		
		global $logger;
		
		$logger->debug("Loading from DB");
		$filter = new PaymentModeLoadFilters();
		$filter->payment_mode_attribute_id = $id;
		$paymentModeAttrArr = self::loadAll($org_id, $filter);
		return $paymentModeAttrArr[0];
	} 


	/**
	 * @return OrgPaymentModeAttributePossibleValue
	 * @static
	 * @access public
	 */
	public static function loadByValue( $org_id, $value, $org_payment_attr_id) {
	
		global $logger;
		
		$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_VALUE, strtoupper($value) ."##".$org_payment_attr_id, $org_id);
		
		if(!$obj = self::loadFromCache($org_id, $cacheKey))
		{
		
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->org_payment_mode_attribute_possible_value= $value;
			$filter->org_payment_mode_attribute_id = $org_payment_attr_id;
			$paymentModeAttrArr = self::loadAll($org_id, $filter);
			return $paymentModeAttrArr[0];
		}			
		
		// returning from
		$logger->debug("Loading from cache");
		return $obj;
	}
	
} // end of PaymentModeAttribute
?>
