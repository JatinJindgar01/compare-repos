<?php

include_once "apiHelper/payment_mode/BasePaymentMode.php";
include_once "apiHelper/payment_mode/GenericPaymentMode.php";
include_once "apiHelper/payment_mode/CardPaymentMode.php";
include_once "apiHelper/payment_mode/CashPaymentMode.php";
include_once "apiHelper/payment_mode/CheckPaymentMode.php";
include_once "apiHelper/payment_mode/CreditPaymentMode.php";
include_once "apiHelper/payment_mode/DiscountCouponPaymentMode.php";
include_once "apiHelper/payment_mode/ExchangeLineitemPayentMode.php";
include_once "apiHelper/payment_mode/FoodCouponPaymentMode.php";
include_once "apiHelper/payment_mode/GiftCardPaymentMode.php";
include_once "apiHelper/payment_mode/PointsPaymentMode.php";
include_once "apiHelper/payment_mode/PrepaidPaymentMode.php";

include_once "apiModel/class.ApiPaymentModeModelExtension.php";

class PaymentModeFactory
{
	/**
	 * this will return instance of payment mode depending on $mode
	 * @param unknown_type $mode
	 * @param unknown_type $params
	 * @throws Exception
	 */
	function getPaymentModeInstance( $mode , $params)
	{
		global $currentorg;
		$org_id = $currentorg->org_id;
		
		$payment_mode = null;
		$payment_mode_model = new ApiPaymentModeModelExtension();
		$mode = strtoupper($mode);
		$org_payment_mode = $payment_mode_model->getPaymentModeFromLabel($org_id, $mode);
		if(!$org_payment_mode)
		{
			throw new Exception("Payment mode '$mode' is not supported");
		}
		$payment_type = strtoupper($org_payment_mode['type']); 
		switch($payment_type)
		{
			case PAYMENT_MODE_CHECK:
				$payment_mode = new CheckPaymentMode();
				break;
			case PAYMENT_MODE_CARD:
				$payment_mode = new CardPaymentMode();
				break;
			case PAYMENT_MODE_CASH:
				$payment_mode = new CashPaymentMode();
				break;
			case PAYMENT_MODE_GIFT_CARD:
				$payment_mode = new GiftCardPaymentMode();
				break;
			case PAYMENT_MODE_DISCOUNT_COUPON;
				$payment_mode = new DiscountCouponPaymentMode();
				break;
			case PAYMENT_MODE_FOOD_COUPON:
				$payment_mode = new FoodCouponPaymentMode();
				break;
			case PAYMENT_MODE_EXCHANGE_LINEITEM:
				$payment_mode = new ExchangeLineitemPaymentMode();
				break;
			case PAYMENT_MODE_CREDIT:
				$payment_mode = new CreditPaymentMode();
				break;
			case PAYMENT_MODE_PREPAID:
				$payment_mode = new PrepaidPaymentMode();
				break;
			case PAYMENT_MODE_POINTS:
				$payment_mode = new PointsPaymentMode();
				break;
			default:
				$payment_mode = new GenericPaymentMode($payment_type);
				break;
		}
		$payment_mode->setPaymentModeId($org_payment_mode['payment_mode_id']);
		$payment_mode->setOrgPaymentModeId($org_payment_mode['id']);
		$payment_mode->setParams($params);
		return $payment_mode;
	}
}
?>