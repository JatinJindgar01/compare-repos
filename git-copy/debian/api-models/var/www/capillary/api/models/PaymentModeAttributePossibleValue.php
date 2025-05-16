<?php
include_once 'models/BaseModel.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'models/PaymentMode.php';
include_once 'models/PaymentModeAttribute.php';
/**
 * class PaymentModeAttributePossibleValue
 *
 */
class PaymentModeAttributePossibleValue extends BaseApiModel
{

	protected static $iterableMembers;

	protected $payment_mode_attribute_possible_value_id;
	protected $payment_mode_id;
	protected $payment_mode_attribute_id;
	protected $value;
	protected $added_on;
	protected $added_by;

	public $paymentModeObj;
	public $paymentModeAttributeObj;

	// caching not required here; will be done in PaymentModeAttributes
// 	const CACHE_KEY_PREFIX_ID = 'PAYMENT_MODES_ATTR_ID';
// 	const CACHE_KEY_PREFIX_NAME = 'PAYMENT_MODES_ATTR_NAME_MODE_ID';
//	CONST CACHE_TTL = 432000; // 5 day

	protected $current_user_id;

	protected $db_master;
	
	public function __construct($org_id =null, $payment_mode_attribute_possible_value_id)
	{
		global $currentuser;
		parent::__construct(null);
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		$this->payment_mode_attribute_possible_value_id = $payment_mode_attribute_possible_value_id;
		
		$this->db_master = new Dbase( 'masters' );
		
		$classname = get_called_class();
		$classname::setIterableMembers();
	}
	
	public static function setIterableMembers()
	{
		$classname = get_called_class();
		$classname::$iterableMembers = array(
				"payment_mode_attribute_possible_value_id",
				"payment_mode_id",
				"payment_mode_attribute_id",
				"value",
				"added_on",
				"added_by",
				
		);
	}

	public function getPaymentModeAttributePossibleValueId()
	{
		return $this->payment_mode_attribute_possible_value_id;
	}
	
	public function setPaymentModeAttributePossibleValueId($payment_mode_attribute_possible_value_id)
	{
		$this->payment_mode_attribute_possible_value_id = $payment_mode_attribute_possible_value_id;
	}
	
	
	public function getPaymentModeAttributeId()
	{
	    return $this->payment_mode_attribute_id;
	}

	public function setPaymentModeAttributeId($payment_mode_attribute_id)
	{
	    $this->payment_mode_attribute_id = $payment_mode_attribute_id;

	    try {
	    	$this->paymentModeAttributeObj = PaymentModeAttributeModel::loadById($payment_mode_attribute_id);
	    } catch (Exception $e) {
	    	$this->logger->debug("Loading the payment mode object has failed");
	    	$this->paymentModeAttributeObj = null;
	    }
	     
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

	public function getValue()
	{
	    return $this->value;
	}

	public function setName($value)
	{
	    $this->value = $value;
	}

	public function getAddedBy()
	{
	    return $this->last_updated_by;
	}

	public function getAddedOn()
	{
	    return $this->last_updated_on;
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
	 * Validates data before saving
	 */
	public function validate()
	{
		throw new ApiPaymentModeException(ApiPaymentModeException::FUNCTION_NOT_IMPLEMENTED);
		//return true;
	}

	/**
	 *
	 *
	 * @return PaymentModeAttributePossibleValue[]
	 * @static
	 * @access public
	 */
	public static function loadAll( $filters = null , $limit = 100, $offset = 0) {

		global $logger; 
		if(isset($filters) && !($filters instanceof PaymentModeLoadFilters))
		{
			throw new ApiPaymentModeException(ApiPaymentModeException::FILTER_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT 
			pmapv.id AS payment_mode_attribute_possible_value_id,
			pmapv.payment_mode_id,
			pmapv.payment_mode_attribute_id as payment_mode_attribute_id,
			pmapv.value,
			pmapv.added_by,
			pmapv.added_on
			FROM masters.payment_mode_attribute_possible_values as pmapv 
			WHERE 1";
		
		if($filters->payment_mode_attribute_possible_value_id)
			$sql .= " AND pmapv.id = $filters->payment_mode_attribute_possible_value_id";
		if($filters->payment_mode_attribute_possible_value)
			$sql .= " AND pmapv.value = '$filters->payment_mode_attribute_possible_value'";
		if($filters->payment_mode_id)
			$sql .= " AND pmapv.payment_mode_id = $filters->payment_mode_id";
		if($filters->payment_mode_attribute_id)
			$sql .= " AND pmapv.payment_mode_attribute_id = $filters->payment_mode_attribute_id";
		$sql .= " ORDER BY pmapv.id DESC ";
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		$sql = $sql . " LIMIT $offset, $limit";
		##print str_replace("\t", " ", $sql);die();
		
		// no filter used here 
		$db_master = new Dbase("masters");
		$rows = $db_master->query($sql);
		
		if($rows)
		{
			$ret = array();
			$cacheStringArr="";
			foreach($rows AS $row)
			{
				$obj = PaymentModeAttributePossibleValue::fromArray(null, $row);
				$ret[] = $obj;
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
	public static function loadById( $id ) {
		
		global $logger;
		
		$logger->debug("Loading from DB");
		$filter = new PaymentModeLoadFilters();
		$filter->payment_mode_attribute_possible_value_id = $id;
		$paymentModeAttrArr = self::loadAll($filter);
		return $paymentModeAttrArr[0];
		
	} 

	/**
	 * @return PaymentModeAttribute
	 * @static
	 * @access public
	 */
	public static function loadByValue( $value, $payment_mode_attribute_id ) {
	
		global $logger;
	
		$logger->debug("Loading from DB");
		$filter = new PaymentModeLoadFilters();
		$filter->payment_mode_attribute_possible_value = $value;
		$filter->payment_mode_attribute_id = $payment_mode_attribute_id;
		$paymentModeAttrArr = self::loadAll($filter);
		return $paymentModeAttrArr[0];
	
	}
	
} // end of PaymentModeAttribute
?>
