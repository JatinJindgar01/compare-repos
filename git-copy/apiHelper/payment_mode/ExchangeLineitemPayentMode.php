<?php
include_once "apiHelper/payment_mode/BasePaymentMode.php";
class ExchangeLineitemPaymentMode extends BasePaymentMode
{
	public function __construct()
	{
		parent::__construct();
	}

	public function setParams($params)
	{
		parent::setParams($params);
	}

	public function getPaymentType()
	{
		return PAYMENT_MODE_EXCHANGE_LINEITEM;
	}

	/**
	 * this will validate attributes as well
	 * @see BasePaymentMode::validate()
	 */
	function validate()
	{
		parent::validate();
	}
}
?>