<?php
include_once 'models/BaseModel.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'models/OrgPaymentMode.php';
include_once 'models/PaymentModeAttributeValue.php';

/**
 * class PaymentModeDetails
 *
 */
class PaymentModeDetails extends BaseApiModel
{

	protected static $iterableMembers;

	protected $payment_mode_details_id;
	protected $ref_type;
	protected $ref_id;
	protected $payment_mode_id;
	protected $org_payment_mode_id;
	protected $amount;
	protected $notes;
	protected $added_by;
	protected $added_on;

	// to hold all the attr values link with the payment
	protected  $paymentModeAttributeValuesArr;
	
	public $paymentModeObj;
	public $orgPaymentModeObj;

	// no caching implemented as frequency of reads will be less
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
				"payment_mode_details_id",
				"ref_type",
				"ref_id",
				"payment_mode_id",
				"org_payment_mode_id",
				"amount",
				"notes",
				"added_by",
				"added_on",
				"orgPaymentModeObj",
				"paymentModeAttributeValuesArr"
		);
	}
	public function getPaymentModeDetailsId()
	{
	    return $this->payment_mode_details_id;
	}

	public function setPaymentModeDetailsId($payment_mode_details_id)
	{
	    $this->payment_mode_details_id = $payment_mode_details_id;
	}

	public function getRefType()
	{
	    return $this->refType;
	}

	public function setRefType($ref_type)
	{
	    $this->ref_type = $ref_type;
	}

	public function getRefId()
	{
	    return $this->ref_id;
	}

	public function setRefId($ref_id)
	{
	    $this->ref_id = $ref_id;
	}

	public function getPaymentModeId()
	{
	    return $this->payment_mode_id;
	}

	public function setPaymentModeId($payment_mode_id)
	{
		$this->paymentModeObj = null;
	    $this->payment_mode_id = $payment_mode_id;
	    
	    try {
	    	if($payment_mode_id > 0 )
	    		$this->paymentModeObj = PaymentMode::loadById($payment_mode_id);
	    } catch (Exception $e) {
	    	$this->paymentModeObj = null;
	    }
	}

	public function getOrgPaymentModeId()
	{
	    return $this->org_payment_mode_id;
	}

	public function setOrgPaymentModeId($org_payment_mode_id, $include_deleted_attributes=false)
	{
	    $this->org_payment_mode_id = $org_payment_mode_id;
	    
	    try {
	    	$this->orgPaymentModeObj = OrgPaymentMode::loadById($this->current_org_id, $org_payment_mode_id, $include_deleted_attributes);
	    } catch (Exception $e) {
	    	$this->orgPaymentModeObj = null;
	    }
	     
	}

	public function getOrgPaymentModeObj()
	{
		if(!$this->orgPaymentModeObj)
			$this->setOrgPaymentModeId($this->org_payment_mode_id);
		
		return $this->orgPaymentModeObj;
	}
	
	public function getAmount()
	{
	    return $this->amount;
	}

	public function setAmount($amount)
	{
	    $this->amount = $amount;
	}

	public function getNotes()
	{
	    return $this->notes;
	}

	public function setNotes($notes)
	{
	    $this->notes = $notes;
	}

	public function getAddedBy()
	{
	    return $this->addedBy;
	}

	public function getAddedOn()
	{
	    return $this->added_on;
	}

	public function getPaymentModeAttributeValues()
	{
		return $this->paymentModeAttributeValuesArr;
	}
	
	public function setPaymentModeAttributeValues($attrs)
	{
		if(is_string($attrs))
			$attrs = $this->decodeFromString($attrs);
	
		$this->paymentModeAttributeValuesArr = array();
		foreach($attrs as $attr)
		{
			if($attr instanceof PaymentModeAttributeValue)
				$this->paymentModeAttributeValuesArr[] = $attr;
			else if(is_array($attr))
				$this->paymentModeAttributeValuesArr[] = PaymentModeAttributeValue::fromArray($this->current_org_id, $attr);
			else if(is_string($attr))
				$this->paymentModeAttributeValuesArr[] = PaymentModeAttributeValue::fromString($this->current_org_id, $attr);
		}
	}

	/**
	 * @access public
	 */
	public function save() {
		
		$this->logger->debug("Saving new payment details");
		
		$columns["org_payment_mode_id"]= $this->org_payment_mode_id;
		$columns["ref_id"]= $this->ref_id;
		$columns["ref_type"]= "'".$this->ref_type."'";
		
		if(isset($this->payment_mode_id))
			$columns["payment_mode_id"]= $this->payment_mode_id;
		if(isset($this->amount))
			$columns["amount"]= $this->amount;
		if(isset($this->notes))
			$columns["notes"]= "'".$this->db_users->realEscapeString($this->notes)."'";

		// new payment mode
		if(!$this->payment_mode_attribute_value_id)
		{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"]= $this->current_user_id;
			$columns["added_on"]= "'".Util::getMysqlDateTime($this->added_on ? $this->added_on : 'now')."'";
		
			$sql = "INSERT IGNORE INTO  payment_mode_details ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_users->insert($sql);
		
			$this->logger->debug("Return of saving the payment details $newId");
		
			if($newId > 0)
				$this->payment_mode_details_id = $newId;
			
			foreach ($this->paymentModeAttributeValuesArr as $attr)
			{
				$attr->setPaymentModeDetailsId($this->payment_mode_details_id);
				$attr->save();
			}
			return;
		}
		else
		{
			$this->logger->debug("Updation is not allowed ");
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
		
		foreach($this->paymentModeAttributeValuesArr as $attr)
			$attr->validate();
	}

	/**
	 *
	 *
	 * @return CreditNote[]
	 * @static
	 * @access public
	 */
	public static function loadAll( $org_id, $filters = null, $limit =100, $offset = 0, $include_deleted_org_payment_modes=false ) {

		global $logger; 
		if(isset($filters) && !($filters instanceof PaymentModeLoadFilters))
		{
			throw new ApiPaymentModeException(ApiPaymentModeException::FILTER_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT 
			pmd.id AS payment_mode_details_id,
			pmd.ref_id as ref_id,
			pmd.ref_type as ref_type,
			pmd.org_payment_mode_id as org_payment_mode_id,
			pmd.payment_mode_id as payment_mode_id,
			pmd.amount,
			pmd.notes,
			pmd.added_by,
			pmd.added_on
			FROM payment_mode_details as pmd 
			WHERE pmd.org_id = $org_id ";
		
		if($filters->payment_mode_details_id)
			$sql .= " AND pmd.id = $filters->payment_mode_details_id";
		if($filters->ref_id)
			$sql .= " AND pmd.ref_id = $filters->ref_id";
		if($filters->ref_type)
			$sql .= " AND pmd.ref_type = '$filters->ref_type'";
		if($filters->payment_mode_id)
			$sql .= " AND pmd.payment_mode_id = '$filters->payment_mode_id'";
		if($filters->org_payment_mode_id)
			$sql .= " AND pmd.org_payment_mode_id = '$filters->org_payment_mode_id'";
		
		$sql .= " ORDER BY pmd.id ASC ";
		
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
				$obj = PaymentModeDetails::fromArray($org_id, $row, $include_deleted_org_payment_modes);
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
		$filter->payment_mode_details_id = $id;
		$paymentDetailsArr = self::loadAll($org_id, $filter);
		return $paymentDetailsArr[0];
		
	}
	
	public static function loadByReference($org_id, $ref_id, $ref_type = 'REGULAR')
	{

		global $logger;
		
		$logger->debug("Loading from DB");
		$filter = new PaymentModeLoadFilters();
		$filter->ref_id = $ref_id;
		$filter->ref_type = $ref_type;
		$paymentDetailsArr = self::loadAll($org_id, $filter);

		return $paymentDetailsArr;
		
	}
	
	public function loadPaymentModeAttributeValues()
	{
		$filter = new PaymentModeLoadFilters();
		$filter->payment_mode_details_id = $this->payment_mode_details_id;
		$attrs = PaymentModeAttributeValue::loadAll($this->current_org_id, $filter);
		$this->logger->debug("loading the value");
		$this->setPaymentModeAttributeValues($attrs);
	}

	
} // end of CreditNote
?>
