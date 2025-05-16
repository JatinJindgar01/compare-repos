<?php
/**
 * 
 * @author kartik
 * this interface defines all functionality required on an interaction level
 */
interface IInteraction{
	
	/**
	 * 
	 * @param integer $org_id organization id of
	 * @param integer $id unique identifiers of interaction like message id of nsadmin
	 */
	public static function loadById($org_id, $id);
	
	/**
	 * 
	 * @param integer $org_id
	 * @param string $reciver_identifier can be email, mobile
	 */
	public static function loadByReceiver($org_id, $reciver_identifier);
	
	/**
	 * 
	 * @param integer $org_id
	 * @param unknown_type $filters
	 */
	public static function loadAll($org_id, $filters = null);
	
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