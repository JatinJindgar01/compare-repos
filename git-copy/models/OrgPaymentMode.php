<?php
include_once 'models/BaseModel.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'models/PaymentMode.php';

/**
 * class OrgPaymentMode
 *
 */
class OrgPaymentMode extends BaseApiModel
{

	protected static $iterableMembers;

	protected $org_payment_mode_id;
	protected $payment_mode_id;
	protected $payment_mode_name;
	protected $label;
	protected $is_valid;
	protected $last_updated_by;
	protected $last_updated_on;
	
	/**
	 * @var PaymentMode
	 */
	public $paymentModeObj;
	/**
	 * @var OrgPaymentModeAttribute[]
	 */
	public $orgPaymentModeAttributesArr;

	const CACHE_KEY_PREFIX_ID = 'API_OPM_ID';
	const CACHE_KEY_PREFIX_LABEL = 'API_OPM_LBL';
	const CACHE_KEY_PREFIX_NAME = 'API_OPM_NAME';
	const CACHE_KEY_ATTRIBUTES_LIST = 'API_OPM_ATTR';
	CONST CACHE_TTL = 432000; // 5 day
	

	protected $current_user_id;

	protected $db_master;
	
	public function __construct($org_id, $org_payment_mode_id = null)
	{
		global $currentuser;
		parent::__construct($org_id);
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		
		$this->db_master = new Dbase( 'masters' );
		
		if($org_payment_mode_id)
			$this->org_payment_mode_id = $org_payment_mode_id;
		
		$classname = get_called_class();
		$classname::setIterableMembers();
	}
	
	public static function setIterableMembers()
	{
		$classname = get_called_class();
		$classname::$iterableMembers = array(
				"org_payment_mode_id",
				"payment_mode_id",
				"label",
				"is_valid",
				"last_updated_by",
				"last_updated_on",
		);
	}
	
	public function getOrgPaymentModeId()
	{
	    return $this->org_payment_mode_id;
	}

	public function setOrgPaymentModeId($org_payment_mode_id)
	{
	    $this->org_payment_mode_id = $org_payment_mode_id;
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
	    	$this->payment_mode_name = $this->paymentModeObj->getName(); 
	    } catch (Exception $e) {
	    	$this->paymentModeObj = null;
	    }
	}
	
	public function getPaymentModeObj()
	{
		return $this->paymentModeObj;
	}

	public function getPaymentModeName()
	{
		return $this->payment_mode_name;
	}
	
	public function getLabel()
	{
	    return $this->label;
	}

	public function setLabel($label)
	{
	    $this->label = $label;
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

	public function setLastUpdatedBy($last_updated_by)
	{
	    $this->last_updated_by = $last_updated_by;
	}

	public function getLastUpdatedOn()
	{
	    return $this->last_updated_on;
	}

	public function getOrgPaymentModeAttribute()
	{
		if(!$this->orgPaymentModeAttributesArr)
			$this->loadAttributes();
		
		return $this->orgPaymentModeAttributesArr;
	}
	
	public function setOrgPaymentModeAttribute($attrs)
	{
		if(is_string($attrs))
			$attrs = $this->decodeFromString($attrs);
	
		$this->orgPaymentModeAttributesArr = array();
		foreach($attrs as $attr)
		{
			if($attr instanceof OrgPaymentModeAttribute)
				$this->orgPaymentModeAttributesArr[] = $attr;
			else if(is_array($attr))
				$this->orgPaymentModeAttributesArr[] = OrgPaymentModeAttribute::fromArray($this->current_org_id, $attr);
			else if(is_string($attr))
				$this->orgPaymentModeAttributesArr[] = OrgPaymentModeAttribute::fromString($this->current_org_id, $attr);
		}
	}
	
	/**
	 * @access public
	 */
	public function save() {
		
		$this->logger->debug("Saving new org payment attributes");
		
		$columns["is_valid"] = !isset($this->is_valid) || $this->is_valid ? 1 : 0;
		if(isset($this->payment_mode_id))
			$columns["payment_mode_id"]= $this->payment_mode_id;
		if(isset($this->label))
			$columns["label"]= "'".$this->db_master->realEscapeString($this->label)."'";
		$columns["last_updated_by"]= $this->current_user_id;
		$columns["last_updated_on"]= "'".Util::getMysqlDateTime($this->last_updated_on ? $this->last_updated_on : 'now')."'";
		
		// new payment mode
		if(!$this->org_payment_mode_id)
		{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
		
			$sql = "INSERT IGNORE INTO org_payment_modes ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ";
			$sql .= "\n ON DUPLICATE KEY UPDATE is_valid = ".$columns["is_valid"];
			if($this->label)
				$sql .= "\n ,label = VALUES(label) ";
			$newId = $this->db_master->insert($sql);
		
			$this->logger->debug("Return of saving the payment details $newId");
			#print str_replace("\n", " ", $sql);
			if($newId > 0)
				$this->org_payment_mode_id = $newId;
		
			foreach ($this->orgPaymentModeAttributesArr as $attr)
			{
				$attr->setOrgPaymentModeId($this->org_payment_mode_id);
				$attr->save();
			}
		}
		else
		{
			$this->logger->debug("Updation is org attribute");
			$sql = "UPDATE org_payment_modes SET ";
		
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
		
			// remove the extra comma
			$sql=substr($sql,0,-2);
		
			$sql .= " WHERE id = $this->org_payment_mode_id";
			$sql .= " and org_id = $this->current_org_id";
			$newId = $this->db_master->update($sql);
		
			foreach ($this->orgPaymentModeAttributesArr as $attr)
			{
				$attr->setOrgPaymentModeId($this->org_payment_mode_id);
				$attr->save();
			}
		}
		
		$cacheKey = $this->generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_ID, $this->org_payment_mode_id, $this->current_org_id);
		$cacheKeyAttr = $this->generateCacheKey(OrgPaymentMode::CACHE_KEY_ATTRIBUTES_LIST, $this->org_payment_mode_id, $this->current_org_id);
		$cacheKeyLabel = self::generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_LABEL, strtoupper($this->getLabel()), $this->current_org_id);
		$cacheKeyName = self::generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_NAME, strtoupper($this->getPaymentModeName()), $this->current_org_id);
		
		// this will update the cache
		self::deleteValueFromCache($cacheKey);
		self::deleteValueFromCache($cacheKeyLabel);
		self::deleteValueFromCache($cacheKeyName);
		self::deleteValueFromCache($cacheKeyAttr);
		
		try{
			$obj = OrgPaymentMode::loadById($this->current_org_id, $this->org_payment_mode_id);
		} catch (Exception $e) {
			$this->logger->debug("The payment mode is not found");
		}
		
		
	} // end of member function save
	
	/**
	 * Validates ogr payment mode data before saving
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
		
		foreach ($this->orgPaymentModeAttributesArr as $attr)
		{
			$attr->validate();
		}
		
		//return true;
	}

	/**
	 *
	 *
	 * @return CreditNote[]
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
			opm.id AS org_payment_mode_id,
			opm.payment_mode_id as payment_mode_id,
			opm.label as label,
			pm.type as payment_mode_name,
			opm.is_valid as is_valid,
			opm.last_updated_by,
			opm.last_updated_on
			FROM masters.org_payment_modes as opm 
			LEFT JOIN masters.payment_mode as pm  
				ON pm.id = opm.payment_mode_id AND pm.is_valid = 1
			WHERE opm.org_id = $org_id ";
		
		if(!$filters->include_deleted_org_payment_modes)
			$sql .= " AND opm.is_valid = 1";
		if($filters->payment_mode_id)
			$sql .= " AND opm.payment_mode_id = $filters->payment_mode_id";
		if($filters->org_payment_mode_id)
			$sql .= " AND opm.id = $filters->org_payment_mode_id";
		if($filters->org_payment_mode_label)
			$sql .= " AND opm.label = '$filters->org_payment_mode_label'";
		
		// this can add in future the other external ids also
		if($filters->org_payment_mode_name)
			$sql .= " AND '$filters->org_payment_mode_name' IN (pm.type)";
		
		$sql .= " ORDER BY opm.id ASC ";
		
		##print str_replace("\t", " ", $sql);
		// no filter used here 
		$db_master = new Dbase("masters");
		$rows = $db_master->query($sql);
		
		if($rows)
		{
			$ret = array();
			foreach($rows AS $row)
			{
				$obj = OrgPaymentMode::fromArray($org_id, $row);
				$ret[] = $obj;
				$cacheKey = self::generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_ID, $obj->getOrgPaymentModeId(), $org_id);
				$cacheKeyLabel = self::generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_LABEL, strtoupper($obj->getLabel()), $org_id);
				$cacheKeyName = self::generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_NAME, strtoupper($obj->getPaymentModeName()), $org_id);
				$str = $obj->toString();
				self::saveToCache($cacheKey, $str);
				self::saveToCache($cacheKeyLabel, $str);
				self::saveToCache($cacheKeyName, $str);
			}
			
				
			return $ret;
		}
		throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);
	} // end of member function loadAll

	/**
	 *
	 *
	 * @return OrgPaymentMode
	 * @static
	 * @access public
	 */
	public static function loadById( $org_id, $id, $include_deleted_org_payment_modes=false ) {
		
		global $logger;
		
		$cacheKey = self::generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_ID, $id, $org_id);
		if(!$obj = self::loadFromCache($org_id, $cacheKey))
		{
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->org_payment_mode_id = $id;
			$filter->include_deleted_org_payment_modes = $include_deleted_org_payment_modes;
			$paymentModeOrgsArr = self::loadAll($org_id, $filter);
			return $paymentModeOrgsArr[0];
		}
		
		// returning from 
		$logger->debug("Loading from cache");
		return $obj;
	}			

	/**
	 * @param unknown_type $org_id
	 * @param unknown_type $label
	 * @return OrgPaymentMode
	 */
	public static function loadByLabel($org_id, $label)
	{
		global $logger;
		
		$cacheKey = self::generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_LABEL, strtoupper($label), $org_id);
		if(!$obj = self::loadFromCache($org_id, $cacheKey))
		{
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->org_payment_mode_label = $label;
			$paymentModeOrgsArr = self::loadAll($org_id, $filter);
			return $paymentModeOrgsArr[0];
		}
		
		// returning from
		$logger->debug("Loading from cache");
		return $obj;
		
	}
	
	/**
	 * @param unknown_type $org_id
	 * @param unknown_type $label
	 * @return OrgPaymentMode
	 */
	public static function loadByPaymentModeName($org_id, $name)
	{
		global $logger;
	
		$cacheKey = array();
		$cacheKey[] = self::generateCacheKey(OrgPaymentMode::CACHE_KEY_PREFIX_NAME,  strtoupper($name), $org_id);
		$obj =null;
		
		// try for all cache keys 
		foreach($cacheKey as $key)
		{
			if($obj = self::loadFromCache($org_id, $key))
				break;
		}
		
		if(!$obj)
		{
			$logger->debug("Loading from DB");
			$filter = new PaymentModeLoadFilters();
			$filter->org_payment_mode_name = $name;
			$paymentModeOrgsArr = self::loadAll($org_id, $filter);
			return $paymentModeOrgsArr[0];
		}
	
		// returning from
		$logger->debug("Loading from cache");
		return $obj;
	
	}
	
	public function getAttributes()
	{
		if(!$this->orgPaymentModeAttributesArr)
			$this->loadAttributes();
		
		return $this->orgPaymentModeAttributesArr;
	}
	/*
	 * To load all the attibutes available for the payment mode
	 */
	public function loadAttributes()
	{
		include_once 'models/OrgPaymentModeAttribute.php';
		
		$this->orgPaymentModeAttributesArr = null;
		$cacheKey = $this->generateCacheKey(OrgPaymentMode::CACHE_KEY_ATTRIBUTES_LIST, $this->org_payment_mode_id, $this->current_org_id);
	
		if($str = self::getFromCache($cacheKey))
		{
			if($str == "null")
			{
				$this->logger->debug("No attributes available");
				$this->orgPaymentModeAttributesArr = null;
				return $this->orgPaymentModeAttributesArr;
			}
				
			$this->logger->debug("cache has the data");
			$array = $this->decodeFromString($str);
			$ret = array();
			foreach($array as $row)
			{
				$obj = OrgPaymentModeAttribute::fromString($this->current_org_id, $row);
				$ret[] = $obj;
				//$this->logger->debug("data from cache" . $obj->toString());
			}
				
			$this->orgPaymentModeAttributesArr = $ret;
			return $this->orgPaymentModeAttributesArr;
		}
	
		$this->logger->debug("Going for DB now");
	
		try {
			$filters = new PaymentModeLoadFilters();
			$filters->org_payment_mode_id = $this->org_payment_mode_id;
			$this->orgPaymentModeAttributesArr = OrgPaymentModeAttribute::loadAll($this->current_org_id, $filters);
				
			$cacheStringArr = array();
			foreach($this->orgPaymentModeAttributesArr as $obj)
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
			$this->orgPaymentModeAttributesArr = null;
			$this->saveToCache($cacheKey, "null");
			$this->logger->debug("No possible values found");
		}
	
	}
	
} // end of CreditNote
?>
