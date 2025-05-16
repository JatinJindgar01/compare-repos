<?php

include_once "models/BaseAwardedPoints.php";

/**
 * @author cj
 *
 * The class to hold all the bill promotion points
 */
class BillPromotionPoints extends BaseAwardedPoints{
	
	protected $transaction_id;
	protected $promotion_id;
		
	public function __construct($org_id, $user_id, $transction_id)
	{
		parent::__construct($org_id, $user_id);
	
		$this->transaction_id = $transction_id;
	}

	public static function setIterableMembers()
	{
	
		$local_members = array(
				"transaction_id",
				"promotion_id"
		);
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	
	public function getTransactionId()
	{
		return $this->transaction_id;
	}
	
	public function setTransactionId($transaction_id)
	{
		$this->transaction_id = $transaction_id;
	}

	public function getPromotionId()
	{
		return $this->promotion_id;
	}
	
	// TODO : impletement the function
	public function loadAll()
	{
	
	}
	
	
}