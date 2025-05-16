<?php
include_once "apiHelper/payment_mode/BasePaymentMode.php";
class PointsPaymentMode extends BasePaymentMode
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
		return PAYMENT_MODE_POINTS;
	}

	/**
	 * this will validate attributes
	 * @see BasePaymentMode::validate()
	 */
	protected function validate()
	{
		parent::validate();
	}
}
?>