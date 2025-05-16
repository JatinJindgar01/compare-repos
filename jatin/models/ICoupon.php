<?php
/**
 * 
 * @author Kartik
 * Interface which abstracts out coupon model and its functionality
 *
 */
interface ICoupon
{
	
	/*
	 *  The function loads the data linked to the object using the primary key
	*/
	public static function loadById($org_id, $id);
	public static function loadBycode($org_id, $code);
	
	/*
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0);
	
	//will calculate expiry date and return
	public function getExpiryDate();
	//will calculate discount amount and return
	public function getDiscountAmount();
	
	//for now this can return array
	//TODO: need to add filters like limit, order etc
	public function loadRedemptionDetails();
	
	//this will redeem this coupon (before transaction redemption)
	//TODO: user_id will come in constructor
	public function redeem($user_id, $transaction_number = NULL, $transaction_amount = NULL, $date_time = NULL, $validation_code = NULL);
	
	public function isRedeemable($user_id, $bill_amount = NULL);
	
	/*
	 * Functions to format the data to correcponding struct
	*/
	public static function fromArray($org_id, $array);
	public static function fromXml($org_id, $string);
	public function toXml();
	public static function fromJson($org_id, $string);
	public function toJson();
}
?>