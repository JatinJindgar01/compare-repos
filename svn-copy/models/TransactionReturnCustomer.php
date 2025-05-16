<?php
include_once ("models/BaseCustomer.php");

/**
 * @author cj
 *
 * The returned customer specific operations will be triggered from here
 * The data will be flowing to user_management.users, user_management.loyalty
 * 
 * No user should be registered directly here as customer is returning something, 
 * the customer can only be updated
 *
*/

class TransactionReturnCustomer extends BaseCustomer {

	/*
	 * The variables to uses table
	*/

	protected $loyaltyCustomer;

	protected $transactionsLinked;

	public function __construct($current_org_id, $user_id = null)
	{
		parent::__construct($current_org_id, $user_id);
	}
	
	public static function setIterableMembers()
	{

		$local_members = array(
		);
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	
	public function setUserId($user_id)
	{
		parent::setUserId($user_id);
		
		include_once 'models/LoyaltyCustomer.php';
		$this->loyaltyCustomer = LoyaltyCustomer::loadById($this->current_org_id, $user_id);
	}

	/*
	 *  The function saves the data in to DB or any other data source,
	*  all the values need to be set using the corresponding setter methods.
	*  This can update the existing record if the id is already set.
	*/
	public function save()
	{
// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			throw new ApiCustomerException(ApiCustomerException::VALIDATION_FAILED);
// 		}

		if($this->user_id)
		{
			throw new ApiCustomerException(ApiCustomerException::FILTER_USER_ID_NOT_PASSED);
			$this->logger->debug("User id is not passed");
		}
		
		try {
			parent::save();
		} catch (ApiException $e) {
			$this->logger->debug("Validation has failed at the parent, returning now");
			throw new ApiCustomerException(ApiCustomerException::SAVING_DATA_TO_USERS_FAILED);
		}

		$columns = array();

		// last updated on
		$columns["last_updated"]= "NOW()";

		// last updated by
		$columns["last_updated_by"]= $this->current_user_id;

		// new user
		if(!$this->user_id)
		{

		}
		else
		{
			$this->logger->debug("Loyalty id is set, so its going to be an update query");
			$sql = "UPDATE user_management.loyalty SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->loyalty_id and publisher_id = $this->current_org_id ";
			$newId = $this->db_user->update($sql);
		}

		if($newId)
		{
			// update the cache
			$this->saveToCache(LoyaltyCustomer::CACHE_KEY_PREFIX_USER_ID.$this->current_org_id."##".$this->user_id, "");
			$obj=LoyaltyCustomer::loadById($this->current_org_id, $this->user_id);
			$this->saveToCache(LoyaltyCustomer::CACHE_KEY_PREFIX_USER_ID.$this->current_org_id."##".$this->user_id, $obj->toString());
			unset($obj);
		}
		else
		{
			throw new ApiCustomerException(ApiCustomerException::SAVING_DATA_FAILED);
		}

	}

	/*
	 * Validate the data before saving to DB/ for insert and update
	*/
	public function validate()
	{
		include_once 'apiHelper/DataValueValidator.php';
		$this->logger->debug("Validating the save to users table");

		//TODO: fill the validators here
		//if(!parent::validate())
		//	return false;

		// new user validate accordingly
		if(!$this->user_id)
		{
			$this->logger->debug("New loyalty customer");

			if($this->email)
				$ret = DataValueValidator::validateEmail($this->email);
				
			if(!$ret)
				return false;

			return true;
		}
			
		//
		else
		{
			$this->logger->debug("Existing loyalty customer");

			return true;
		}
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($org_id, $user_id)
	{
		include_once 'models/LoyaltyCustomer.php';
		return LoyaltyCustomer::loadById($org_id, $user_id);
	}

	/*
	 *  The function loads the data linked to the object based on email,
	*/
	public static function loadByMobile($org_id, $mobile)
	{
		include_once 'models/LoyaltyCustomer.php';
		return LoyaltyCustomer::loadByMobile($org_id, $mobile);

	}


	/*
	 *  The function loads the data linked to the object based on email,
	*/
	public static function loadByEmail($org_id, $email)
	{
		include_once 'models/LoyaltyCustomer.php';
		return LoyaltyCustomer::loadByEmail($org_id, $email);

	}

	/*
	 *  The function loads the data linked to the object based on email,
	*/
	public static function loadByExternalId($org_id, $external_id)
	{
		include_once 'models/LoyaltyCustomer.php';
		return LoyaltyCustomer::loadByExternalId($org_id, $external_id);
	}


	/*
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		include_once 'models/LoyaltyCustomer.php';
		return LoyaltyCustomer::loadAll($org_id, $filters , $limit, $offset );
	}

	/*
	 * Loads all the transactions of the customer to object.
	* The setter method has to be used prior to set the customer id
	*/
	public function loadTransactions($limit=100, $offset = 0)
	{
		include_once 'models/ReturnedTransaction';
		// load the dependene class if required
		//$this->initiateDependentObject('transaction');

		if(!$this->user_id)
		{
			$this->logger->debug("User id not set ");
			throw new ApiCustomerException(ApiCustomerException::FILTER_USER_ID_NOT_PASSED);
		}

		$filters = new TransactionLoadFilters();
		$filters->user_id = $this->user_id;

		// TODO: uncomment the org id setting 
		// LoyaltyTransaction::setOrgId($this->current_org_id);
		$this->transactionsLinked = ReturnedTransaction::loadAll($this->current_org_id, $filters, $limit, $offset);
		return;
	}

	/**
	 * initiate the respective class on demand
	 * @param $memberName - the object need to be initialized
	 */
	protected function initiateDependentObject($memberName)
	{
		$this->logger->debug("Lazy loading the $memberName object");

		switch(strtolower($memberName))
		{
			case 'transaction':
				if(!$this->transaction instanceof ITransaction)
				{
					include_once 'models/ReturnedTransaction.php';
					$this->logger->debug("Loaded the member");
				}
				break;

			default:
				$this->logger->debug("Requested member could not be resolved trying parent");
				parent::initiateDependentObject($memberName);
		}

	}
}
