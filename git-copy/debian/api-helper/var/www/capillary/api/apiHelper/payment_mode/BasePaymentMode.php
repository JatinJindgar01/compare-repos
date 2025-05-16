<?php
define('PAYMENT_MODE_CHECK', 'CHECK');
define('PAYMENT_MODE_CARD', 'CARD');
define('PAYMENT_MODE_CASH', 'CASH');
define('PAYMENT_MODE_GIFT_CARD', 'GIFT_CARD');
define('PAYMENT_MODE_DISCOUNT_COUPON', 'DISCOUNT_COUPON');
define('PAYMENT_MODE_FOOD_COUPON', 'FOOD_COUPON');
define('PAYMENT_MODE_EXCHANGE_LINEITEM', 'EXCHANGE_LINEITEM');
define('PAYMENT_MODE_CREDIT', 'CREDIT');
define('PAYMENT_MODE_PREPAID', 'PREPAID');
define('PAYMENT_MODE_POINTS', 'POINTS');

include_once 'apiModel/class.ApiPaymentModeModelExtension.php';
include_once 'apiHelper/payment_mode/PaymentModeAttribute.php';

abstract class BasePaymentMode
{
	protected $amount;
	protected $org_id;
	protected $ref_id;
	protected $ref_type;
	protected $notes;
	protected $currentorg, $currentuser, $logger;
	protected $payment_mode_attr;
	
	protected $users_db, $masters_db;
	
	//$id will be populated after inserting into db
	protected $id;
	protected $payment_mode_id, $org_payment_mode_id; 
	
	protected $paymentModelExtension;
	
	//description and label of payment mode
	private $description, $label;
	
	public function __construct()
	{
		global $currentorg, $currentuser, $logger;
		$this->currentorg = $currentorg;
		$this->org_id = $this->currentorg->org_id;
		$this->currentuser = $currentuser;
		$this->logger = $logger;
		
		$this->users_db = new Dbase('users');
		$this->masters_db = new Dbase('masters');
		
		$this->paymentModelExtension = new ApiPaymentModeModelExtension();
	}
	
	/**
	 * sets payment_details from given params
	 * @param unknown_type $params
	 */
	function setParams( $params )
	{
		$this->amount = $params['amount'];
		$this->notes = $params['notes'];
		
		$this->ref_id = $params['ref_id'];
		$this->ref_type = $params['ref_type'];
		$this->org_id = $this->currentorg->org_id;
		
		//Setting valid attributes
		if(isset($params['attributes']))
		{
			$attributes = $params['attributes']['attribute'];
		}
			
		if(count($attributes) <= 0 )
		{
			$this->logger->debug("not validating payment mode attributes, as no attributes are passed");
			return;
		}
		
		$this->setAttributes($attributes);
	}
	
	private function setAttributes ( $attributes )
	{
		$supported_attributes = PaymentModeAttribute::
			getSupportedAttributesForPaymentMode($this->org_id, $this->payment_mode_id);
		$new_payment_mode_attributes = array();
		foreach ($attributes AS $attribute)
		{
			if(isset($supported_attributes[strtoupper($attribute['name'])]))
			{
				$temp_payment_mode_attribute = $supported_attributes[strtoupper($attribute['name'])];
				$temp_payment_mode_attribute->setValue($attribute['value']);
				$new_payment_mode_attributes[strtoupper($attribute['name'])] = $temp_payment_mode_attribute;
				//$new_payment_mode_attributes[] = $temp_payment_mode_attribute;
			}
			else
			{
				$this->logger->error("payment type attribute : ".$attribute['name']." is not supported for this org");
			}
		}
		$this->payment_mode_attr = $new_payment_mode_attributes;
	}
	
	protected function validate()
	{
		$this->logger->debug("Validating payment details and attributes");
		if(!is_numeric($this->amount))
		{
			$error_str = "Payment Value of ".$this->getPaymentType()." payment mode is Invalid. Failed to add ".
					$this->getPaymentType()." payment mode details";
			$this->logger->error($error_str);
			throw new Exception($error_str);
		}
		
		// Validating each Payment Mode Attribute
		foreach($this->payment_mode_attr AS $attribute)
		{
			$attribute->validate();
		}
	}
	
	final function save()
	{
		$this->validate();
		
		$added_by = $this->currentuser->user_id;
		
		$this->id = $this->paymentModelExtension->savePaymentModeDetails(
						$this->org_id, $this->ref_id, $this->ref_type, 
						$this->org_payment_mode_id, $this->payment_mode_id,
						$this->amount, $added_by, $this->notes);
		
		if(!$this->id)
		{
			$this->logger->error("payment details insert failed");
			throw new Exception("Can't insert payment details");
		}
		
		$this->savePaymentAttributeValues();
		
		return $this->id;
	}
	
	private function savePaymentAttributeValues()
	{
		if(count($this->payment_mode_attr) <= 0 )
		{
			$this->logger->debug("No Attributes found for payment, skiping payment attributes");
			return -1;
		}
		$arr_hash_attributes = array();
		foreach($this->payment_mode_attr AS $attribute)
		{
			$arr_hash_attributes[] = $attribute->getHash();
		}
		$added_by = $this->currentuser->user_id;
		$last_inserted_id = $this->paymentModelExtension->savePaymentModeAttributes(
				$this->org_id, $this->org_payment_mode_id, $this->payment_mode_id, 
				$this->id, $added_by, $arr_hash_attributes);
		
		return $last_inserted_id;
	}
	
	abstract function getPaymentType();
	
	public function setPaymentModeId($payment_mode_id)
	{
		$this->payment_mode_id = $payment_mode_id;
	}
	
	public function setOrgPaymentModeId($org_payment_mode_id)
	{
		$this->org_payment_mode_id = $org_payment_mode_id;
	}
	
	function setRefId( $ref_id )
	{
		$this->ref_id = $ref_id;
	}
	
	function setRefType( $ref_type )
	{
		$this->ref_type = $ref_type;
	}
	
	function setLabel($label)
	{
		$this->label = $label;
	}
	
	function setDescription($description)
	{
		$this->description = $description;
	}
}
?>