<?php
/**
 * @author cj
 * 
 * The interface defines all the functionalities required on a transction line item level 
 *
 */
interface ILineItem{
	
	/*  
	 *  The function saves the data in to DB or any other data source,
	 *  all the values need to be set using the corresponding setter methods.
	 *  Updation of lineitem is not a feature restricted for now, 
	 *  so be careful about any updation
	 */ 
	public function save();

	/*
	 *  The function loads the data linked to the object, 
	 */
	public static function loadById($org_id, $lineitem_id);

	/* 
	 * Load all the data into object based on the filters being passed. 
	 * It should optionally decide whether entire dependency tree is required or not
	 */
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0);
	
	public function validate();
	/*
	 * all the setter and getter methods
	 */

	/*
	 * Formmating of the string to object and vice versa
	 */
	public static function fromArray($org_id, $array);
	public static function fromXml($org_id, $string);
	public function toXml();
	public static function fromJson($org_id, $string);
	public function toJson();
	
} 