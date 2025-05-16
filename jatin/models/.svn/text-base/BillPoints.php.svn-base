<?php

include_once "models/BaseAwardedPoints.php";

/**
 * @author cj
 *
 * All the points to be allocated on the bill 
 */
class BillPoints extends BaseAwardedPoints{
	
	protected $transaction_id;
	
	public function __construct($org_id, $user_id, $transction_id)
	{
		parent::__construct($org_id, $user_id);
		
		$this->transaction_id = $transction_id;
	}

	public static function setIterableMembers()
	{
	
		$local_members = array(
				"transaction_id"
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

	// TODO : impletement teh function 
	public function loadAll()
	{
		
	}
}