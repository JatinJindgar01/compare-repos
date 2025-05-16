<?php
include_once 'apiModel/class.ApiPaymentModeModelExtension.php';
class PaymentModeAttribute
{
	private $org_payment_mode_id, $org_payment_mode_attribute_id;
	private $payment_mode_id, $payment_mode_attribute_id;
	private $org_id;
	private $name;
	private $label;
	
	private $value;
	
	function __construct()
	{
		
	}
	
	public function setFields($params)
	{
		$this->org_payment_mode_id = $params['org_payment_mode_id'];
		$this->org_payment_mode_attribute_id = $params['org_payment_mode_attribute_id'];
		$this->payment_mode_id = $params['payment_mode_id'];
		$this->payment_mode_attribute_id = $params['payment_mode_attribute_id'];
		$this->org_id = $params['org_id'];
		$this->name = $params['name'];
		$this->label = $params['label'];
	}
	
	
	public function setValue($value)
	{
		$this->value = $value;
	}
	
	public function getHash()
	{
		$temp_arr = array();
		$temp_arr['org_payment_mode_id'] = $this->org_payment_mode_id;
		$temp_arr['org_payment_mode_attribute_id'] = $this->org_payment_mode_attribute_id;
		$temp_arr['payment_mode_id'] = $this->payment_mode_id;
		$temp_arr['payment_mode_attribute_id'] = $this->payment_mode_attribute_id;
		$temp_arr['org_id'] = $this->org_id;
		$temp_arr['name'] = $this->name;
		$temp_arr['label'] = $this->label;
		$temp_arr['value'] = $this->value;
		return $temp_arr;
	}
	
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * validates value of the attribute, like regex validation, value type validation etc
	 */
	public function validate()
	{
		
	}
	
	/**
	 * returns Array of PaymentModeAttribute for givent payment mode label
	 * @param unknown_type $org_id
	 * @param unknown_type $label
	 * @return Array:PaymentModeAttribute 
	 */
	public static function getSupportedAttributesForPaymentMode($org_id, $payment_mode_id)
	{
		$attributes = array();
		$paymentModeModelExt = new ApiPaymentModeModelExtension();
		$arr_attributes = $paymentModeModelExt->getPaymentModeAttributes($org_id, $payment_mode_id);
		
		foreach($arr_attributes AS $label => $attribute)
		{
			$temp_attribute = new PaymentModeAttribute();
			$temp_attribute->setFields($attribute);
			$attributes[strtoupper($label)] = $temp_attribute;
		}
		
		return $attributes;
	}
}
?>