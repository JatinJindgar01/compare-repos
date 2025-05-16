<?php
include_once ("models/BaseTransaction.php");

/**
 * @author cj
 *
 * The loyalty transaction specific operations will be triggered from here
 * The data will be flowing to user_management.loyalty_log or return transaction or so.
 *
*/

abstract class BaseCustomerTransaction extends BaseTransaction {
	
	protected $user_id;

	//customer object
	public $customer;
	
	public function __construct($current_org_id, $transction_id = null, $user_id = null)
	{
		parent::__construct($current_org_id, $transction_id);
		if($user_id>0)
			$this->user_id = $user_id;
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
				"customer",
				"user_id"
		);
			
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	
	public function getUserId()
	{
	    return $this->user_id;
	}

	public function setUserId($user_id)
	{
	    $this->user_id = $user_id;
	}
 
	
	abstract public function loadCustomer();

	
	protected function validateTransactionDate()
	{
		$ret = parent::validateTransactionDate();
		
		include_once 'apiHelper/DataValueValidator.php';
		
		$this->initiateDependentObject('configMgr');
	
		// check for negative qty
		if($this->configMgr->getKey("API_VALIDATION_TXN_DATE_BEFORE_DOJ"))
		{
			$this->logger->debug("check if transaction_date is before customer doj");
			if(DataValueValidator::validateDateTimeBefore($this->transaction_date, $this->customer->getJoined()))
			{
				$validationError[] = "Transaction date before Date of joining";
				$ret = false;
			}
		}
	
		return $ret;
	
	}
	
	/* this can be the function calling the customer to update the fields likes
	 * lifetime, loyalty, slab etc
	 * TODO : check the nececity of the function as the child id updating the parent  
	 */ 
	//abstract public function setCustomerDetails();
}