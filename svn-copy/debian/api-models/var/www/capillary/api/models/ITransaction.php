<?php
/**
 * @author cj
 * 
 * The interface defines all the functionalities required on a transction level 
 *
 */
interface ITransaction{
	
	/*  
	 *  The function saves the data in to DB or any other data source,
	 *  all the values need to be set using the corresponding setter methods.
	 *  Updation of transactions is not a feature highly restricted, 
	 *  so be careful about any updation
	 */ 
	public function save();

	/*
	 *  The function loads the data linked to the object, 
	 *  id can be set earlier also  
	 */
	public static function loadById($org_id, $transaction_id);

	/* 
	 * Load all the data into object based on the filters being passed. 
	 * It should optionally decide whether entire dependency tree is required or not
	 */
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0);

	/*
	 * Loads all the lineitems of a transaction to object. 
	 * The setter method has to be used prior to set the transaction id 
	 */
	public function loadLineItems();

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