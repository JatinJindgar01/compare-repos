<?php
include_once "apiHelper/payment_mode/BasePaymentMode.php";
class GiftCardPaymentMode extends BasePaymentMode
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
		return PAYMENT_MODE_GIFT_CARD;
	}

	/**
	 * This will validate attributes as well
	 * @see BasePaymentMode::validate()
	 */
	protected function validate()
	{
		parent::validate();
	} 
}
?>