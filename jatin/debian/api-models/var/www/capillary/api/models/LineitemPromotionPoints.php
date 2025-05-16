<?php

include_once "models/BaseAwardedPoints.php";

/**
 * @author cj
 *
 * All the points to be allocated on the line item level 
 */
class LineitemPoints extends BaseAwardedPoints{
	
	protected $transaction_id;
	protected $lineitem_id;

	public function __construct($org_id, $user_id, $lineitem_id, $transction_id = null)
	{
		parent::__construct($org_id, $user_id);
	
		$this->transaction_id = $transction_id;
		$this->lineitem_id = $lineitem_id;
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
				"lineitem_id",
				"transaction_id"
		);
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	
	public function getLineitemId()
	{
	    return $this->lineitem_id;
	}

	public function setLineitemId($lineitem_id)
	{
	    $this->lineitem_id = $lineitem_id;
	}

	public function getTransactionId()
	{
	    return $this->transaction_id;
	}

	public function setTransactionId($transaction_id)
	{
	    $this->transaction_id = $transaction_id;
	}

	// TODO : impletement the function 
	public function loadAll()
	{
		
	}
}