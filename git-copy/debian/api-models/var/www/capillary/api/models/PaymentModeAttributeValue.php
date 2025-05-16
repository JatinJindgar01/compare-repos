<?php
include_once 'models/BaseModel.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'models/OrgPaymentModeAttribute.php';
/**
 * class PaymentModeAttributeValue
 *
 */
class PaymentModeAttributeValue extends BaseApiModel
{

	protected static $iterableMembers;

	protected $payment_mode_attribute_value_id;
	protected $org_payment_mode_attribute_id;
	protected $org_payment_mode_id;
	protected $payment_mode_id;
	protected $payment_mode_attribute_id;
	protected $payment_mode_details_id;
	protected $payment_mode_attribute_possible_values_id;
	protected $org_payment_mode_attribute_possible_values_id;
	protected $value;
	protected $added_by;
	protected $added_on;

	public $orgPaymentModeAttributeObj;
	public $orgPaymentModeAttributePossiblevaluesObj;
	// to hold all the attr values link with the payment
	
	// no caching implemented as frequency of reads will be less; 
// 	const CACHE_KEY_PREFIX_ID = 'ORG_PAYMENT_MODES_ID#';
// 	const CACHE_KEY_PREFIX_LABEL = 'ORG_PAYMENT_MODES_LABEL#';
// 	CONST CACHE_TTL = 432000; // 5 day

	protected $current_user_id;

	protected $db_users;
	
	public function __construct($org_id, $payment_details_id = null)
	{
		global $currentuser;
		parent::__construct($org_id);
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		$this->current_org_id = $org_id;
		$this->db_users = new Dbase( 'users' );
		
		if($payment_details_id)
			$this->payment_mode_details_id = $payment_details_id;
		
		$classname = get_called_class();
		$classname::setIterableMembers();
	}

	public static function setIterableMembers()
	{
		$classname = get_called_class();
		$classname::$iterableMembers = array(
				"payment_mode_attribute_value_id",
				"org_payment_mode_attribute_id",
				"org_payment_mode_id",
				"payment_mode_id",
				"payment_mode_attribute_id",
				"payment_mode_details_id",
				"payment_mode_attribute_possible_values_id",
				"org_payment_mode_attribute_possible_values_id",
				"value",
				"added_by",
				"added_on",
		);
	}
	
	public function getPaymentModeAttributeValueId()
	{
	    return $this->payment_mode_attribute_value_id;
	}

	public function setPaymentModeAttributeValueId($payment_mode_attribute_value_id)
	{
	    $this->payment_mode_attribute_value_id = $payment_mode_attribute_value_id;
	}

	public function getOrgPaymentModeAttributeId()
	{
	    return $this->org_payment_mode_attribute_id;
	}

	public function setOrgPaymentModeAttributeId($org_payment_mode_attribute_id)
	{
	    $this->org_payment_mode_attribute_id = $org_payment_mode_attribute_id;
	}

	public function getOrgPaymentModeAttributeObj( $include_deleted_attributes=false )
	{
		if($this->orgPaymentModeAttributeObj)
			return $this->orgPaymentModeAttributeObj;
		try {
			return $this->orgPaymentModeAttributeObj = OrgPaymentModeAttribute::loadById($this->current_org_id, $this->org_payment_mode_attribute_id, $include_deleted_attributes);
		} catch (Exception $e) {
			return $this->orgPaymentModeAttributeObj = null;
		}
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
	}

	public function getPaymentModeAttributeId()
	{
	    return $this->payment_mode_attribute_id;
	}

	public function setPaymentModeAttributeId($payment_mode_attribute_id)
	{
	    $this->payment_mode_attribute_id = $payment_mode_attribute_id;
	}

	public function getPaymentModeDetailsId()
	{
	    return $this->payment_mode_details_id;
	}

	public function setPaymentModeDetailsId($payment_mode_details_id)
	{
	    $this->payment_mode_details_id = $payment_mode_details_id;
	}

	public function getPaymentModeAttributePossibleValuesId()
	{
	    return $this->payment_mode_attribute_possible_values_id;
	}
	
	public function getOrgPaymentModeAttributePossiblevaluesObj()
	{
		if($this->orgPaymentModeAttributePossiblevaluesObj)
			return $this->orgPaymentModeAttributePossiblevaluesObj;
		try {
			return $this->orgPaymentModeAttributePossiblevaluesObj = OrgPaymentModeAttributePossibleValue::loadById($this->current_org_id, $this->org_payment_mode_attribute_possible_values_id);
		} catch (Exception $e) {
			return $this->orgPaymentModeAttributePossiblevaluesObj = null;
		}
	}

	public function setPaymentModeAttributePossibleValuesId($payment_mode_attribute_possible_values_id)
	{
	    $this->payment_mode_attribute_possible_values_id = $payment_mode_attribute_possible_values_id;
	}

	public function getOrgPaymentModeAttributePossibleValuesId()
	{
	    return $this->org_payment_mode_attribute_possible_values_id;
	}

	public function setOrgPaymentModeAttributePossibleValuesId($org_payment_mode_attribute_possible_values_id)
	{
	    $this->org_payment_mode_attribute_possible_values_id = $org_payment_mode_attribute_possible_values_id;
	}

	public function getValue()
	{
	    return $this->value;
	}

	public function setValue($value)
	{
	    $this->value = $value;
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
	 * @access public
	 */
	public function save() {

		$this->logger->debug("Saving new attribute value");
		
		$columns["org_payment_mode_id"]= $this->org_payment_mode_id;
		$columns["payment_mode_details_id"]= $this->payment_mode_details_id;
		
		if(isset($this->payment_mode_id))
			$columns["payment_mode_id"]= $this->payment_mode_id;
		if(isset($this->org_payment_mode_attribute_id))
			$columns["org_payment_mode_attribute_id"]= $this->org_payment_mode_attribute_id;
		if(isset($this->payment_mode_attribute_id))
			$columns["payment_mode_attribute_id"]= $this->payment_mode_attribute_id;
		if(isset($this->payment_mode_attribute_possible_values_id))
			$columns["payment_mode_attribute_possible_values_id"]= $this->payment_mode_attribute_possible_values_id;
		if(isset($this->org_payment_mode_attribute_possible_values_id))
			$columns["org_payment_mode_attribute_possible_values_id"]= $this->org_payment_mode_attribute_possible_values_id;
		if(isset($this->value))
			$columns["value"]= "'".$this->db_users->realEscapeString($this->value)."'";
		if(isset($this->is_soft_enum))
			$columns["is_soft_enum"]= $this->is_soft_enum ? 1 : 0;
		if(isset($this->use_in_dump))
			$columns["use_in_dump"]= $this->use_in_dump ? 1 : 0;
		if(isset($this->default_attribute_value_id))
			$columns["default_attribute_value_id"]= $this->default_attribute_value_id;
		
		// new user
		if(!$this->payment_mode_attribute_value_id)
		{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"]= $this->current_user_id;
			$columns["added_on"]= "'".Util::getMysqlDateTime($this->added_on ? $this->added_on : 'now')."'";
		
			$sql = "INSERT IGNORE INTO  payment_mode_attribute_values ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_users->insert($sql);
		
			$this->logger->debug("Return of saving the inventory masters is $newId");
		
			if($newId > 0)
				$this->payment_mode_attribute_value_id = $newId;
			
			return;
		}
		else
		{
			$this->logger->debug("The item already exists");
			throw new ApiPaymentModeException(ApiPaymentModeException::SAVING_DATA_FAILED);
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
	}

	/**
	 *
	 *
	 * @return CreditNote[]
	 * @static
	 * @access public
	 */
	public static function loadAll( $org_id, $filters = null, $limit =100, $offset = 0 ) {

		global $logger; 
		if(isset($filters) && !($filters instanceof PaymentModeLoadFilters))
		{
			throw new ApiPaymentModeException(ApiPaymentModeException::FILTER_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT 
			pmdv.id AS payment_mode_attribute_value_id,
			pmdv.org_payment_mode_attribute_id,
			pmdv.org_payment_mode_id,
			pmdv.payment_mode_id,
			pmdv.payment_mode_attribute_id,
			pmdv.payment_mode_details_id,
			pmdv.payment_mode_attribute_possible_values_id,
			pmdv.org_payment_mode_attribute_possible_values_id,
			pmdv.value,
			pmdv.added_on,
			pmdv.added_by
			FROM payment_mode_attribute_values as pmdv 
			WHERE pmdv.org_id = $org_id ";
		
		if($filters->payment_mode_attribute_value_id)
			$sql .= " AND pmdv.id = $filters->payment_mode_attribute_value_id";
		if($filters->org_payment_mode_attribute_id)
			$sql .= " AND pmdv.org_payment_mode_attribute_id = $filters->org_payment_mode_attribute_id";
		if($filters->org_payment_mode_id)
			$sql .= " AND pmdv.org_payment_mode_id = $filters->org_payment_mode_id";
		if($filters->payment_mode_attribute_id)
			$sql .= " AND pmdv.payment_mode_attribute_id = $filters->payment_mode_attribute_id";
		if($filters->payment_mode_details_id)
			$sql .= " AND pmdv.payment_mode_details_id = $filters->payment_mode_details_id";
		if($filters->payment_mode_attribute_possible_values_id)
			$sql .= " AND pmdv.payment_mode_attribute_possible_values_id = $filters->payment_mode_attribute_possible_values_id";
		if($filters->payment_mode_attribute_value)
			$sql .= " AND pmdv.value = $filters->payment_mode_attribute_value";
		
		$sql .= " ORDER BY pmdv.id DESC ";
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		$sql = $sql . " LIMIT $offset, $limit";
		
		#print str_replace("\t", " ", $sql);
		// no filter used here 
		$db_users = new Dbase("users");
		$rows = $db_users->query($sql);
		
		if($rows)
		{
			$ret = array();
			foreach($rows AS $row)
			{	
				$obj = PaymentModeAttributeValue::fromArray($org_id, $row);
				$ret[] = $obj;
			}
			
			return $ret;
		}
		throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);
	} // end of member function loadAll


	public static function loadById($org_id, $id)
	{
		global $logger;

		$logger->debug("Loading from DB");
		$filter = new PaymentModeLoadFilters();
		$filter->payment_mode_attribute_value_id = $id;
		$paymentDetailsArr = self::loadAll($org_id, $filter);
		return $paymentDetailsArr[0];
		
	}
	
	public static function loadByPaymentDetail($org_id, $payment_details_id)
	{

		global $logger;
		
		$logger->debug("Loading from DB");
		$filter = new PaymentModeLoadFilters();
		$filter->payment_mode_details_id = $payment_details_id;
		$paymentDetailsArr = self::loadAll($org_id, $filter);

		return $paymentDetailsArr;
		
	}
	
} // end of CreditNote
?>
