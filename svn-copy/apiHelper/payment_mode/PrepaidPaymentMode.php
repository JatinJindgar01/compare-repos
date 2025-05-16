<?php
include_once "apiHelper/payment_mode/BasePaymentMode.php";
class PrepaidPaymentMode extends BasePaymentMode
{	
	public function __construct()
	{
		parent::__construct();
	}

	public function setParams($params)
	{
		parent::setParams($params);
		//TODO: set attributes here
	}

	public function getPaymentType()
	{
		return PAYMENT_MODE_PREPAID;
	}

	/**
	 * This will validate attributes as well
	 * @see BasePaymentMode::validate()
	 */
	public function validate()
	{
		parent::validate();
	}
}
?>