<?php
include_once "apiHelper/payment_mode/BasePaymentMode.php";
class GenericPaymentMode extends BasePaymentMode
{
	private $payment_type;	
	public function __construct( $payment_type )
	{
		parent::__construct();
		$this->payment_type = $payment_type;
	}
	
	/**
	 * sets params for Payment Modes, params includes attributes as well
	 * @see BasePaymentMode::setParams()
	 */
	public function setParams($params)
	{
		parent::setParams($params);
	}
	
	public function getPaymentType()
	{
		return $this->payment_type;
	}
	
	/**
	 * This should validate attributes
	 * @see BasePaymentMode::validate()
	 */
	function validate()
	{
		parent::validate();
	}
}
?>