<?php
include_once "apiHelper/payment_mode/BasePaymentMode.php";
class FoodCouponPaymentMode extends BasePaymentMode
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
		return PAYMENT_MODE_FOOD_COUPON;
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