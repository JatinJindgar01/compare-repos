<?php
/**
 * @author cj
 * 
 * The interface defines all the functionalities required on a customer level 
 *
 */
interface ICustomer{

	/*  
	 *  The function saves the data in to DB or any other data source,
	 *  all the values need to be set using the corresponding setter methods.
	 *  This can update the existing record if the id is already set.
	 */ 
	public function save();

	/*
	 *  The function loads the data linked to the object using the primary key 
	 */
	public static function loadById($org_id, $user_id); // if user id is not passed, $this->user_id will be used
	public static function loadByMobile($org_id, $mobile);
	public static function loadByEmail($org_id, $email);
	public static function loadByExternalId($org_id, $external_id);
	/* 
	 * Load all the data into object based on the filters being passed. 
	 * It should optionally decide whether entire dependency tree is required or not
	 */
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0);

	/*
	 * Loads all the transactions of the customer to object. 
	 * The setter method has to be used prior to set the customer id 
	 */
	public function loadTransactions( $limit=100, $offset = 0);
	
	public function validate();
	
	/*
	 * Functions to format the data to correcponding struct  
	 */
	public static function fromArray($org_id, $array);
	public static function fromXml($org_id, $string);
	public function toXml();
	public static function fromJson($org_id, $string);
	public function toJson();
}