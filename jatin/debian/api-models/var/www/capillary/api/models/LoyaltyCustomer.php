<?php
include_once ("models/BaseCustomer.php");

/**
 * @author cj
 *
 * The loyalty customer specific operations will be triggered from here
 * The data will be flowing to user_management.users, user_management.extd_user_profile (deprecated)
 *
*/

class LoyaltyCustomer extends BaseCustomer {

	/*
	 * The variables to uses table
	*/

	protected $loyalty_id;
	protected $current_points;
	protected $lifetime_points;
	protected $slab_name;
	protected $slab_number;
	protected $external_id;
	protected $joined;
	protected $registered_store_id;
	protected $base_store_id;
	protected $last_updated_by;
	protected $last_updated_by_username;
	protected $last_updated_on;
	protected $fraud_status;

	protected $transaction;
	protected $customFields;
	protected $pointsAwards;

	protected $transactionsLinked;
	protected $customFieldsLinked;
	protected $pointsAwardsLinked;

	CONST CACHE_KEY_PREFIX_USER_ID 	= 'LOYALTY_USER_ID#';
	CONST CACHE_KEY_PREFIX_EXT_ID	= 'LOYALTY_USER_EXT_ID#';
	CONST CACHE_KEY_PREFIX_EMAIL 	= 'LOYALTY_USER_EMAIL#';
	CONST CACHE_KEY_PREFIX_MOBILE 	= 'LOYALTY_USER_MOBILE#';

	public function __construct($current_org_id, $user_id = null)
	{
		parent::__construct($current_org_id, $user_id);
	}
	
	public static function setIterableMembers()
	{

		$local_members = array(
				"loyalty_id",
				"current_points",
				"lifetime_points",
				"slab_name",
				"slab_number",
				"external_id",
				"joined",
				"registered_store_id",
				"base_store_id",
				"last_updated_by",
				"last_updated_by_username",
				"last_updated_on",
				"fraud_status",
		
				//"transactionsLinked",
				//"customFieldValuesLinked"
		);
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	/**
	 *
	 * @return
	 */
	public function getLoyaltyId()
	{
		return $this->loyalty_id;
	}

	/**
	 *
	 * @param $loyalty_id
	 */
	public function setLoyaltyId($loyalty_id)
	{
		$this->loyalty_id = $loyalty_id;
	}

	/**
	 *
	 * @return
	 */
	public function getCurrentPoints()
	{
		return $this->current_points;
	}

	/**
	 *
	 * @return
	 */
	public function getLifetimePoints()
	{
		return $this->lifetime_points;
	}

	/**
	 *
	 * @return
	 */
	public function getSlabName()
	{
		return $this->slab_name;
	}

	/**
	 *
	 * @return
	 */
	public function getSlabNumber()
	{
		return $this->slab_number;
	}

	/**
	 *
	 * @return
	 */
	public function getExternalId()
	{
		return $this->external_id;
	}

	/**
	 *
	 * @param $external_id
	 */
	public function setExternalId($external_id)
	{
		$this->external_id = $external_id;
	}

	/**
	 *
	 * @return
	 */
	public function getJoined()
	{
		return $this->joined;
	}

	/**
	 *
	 * @param $joined
	 */
	public function setJoined($joined)
	{
		$this->joined = $joined;
	}

	/**
	 *
	 * @return
	 */
	public function getRegisteredStoreId()
	{
		return $this->registeredStoreId;
	}

	/**
	 *
	 * @param $registered_store_id
	 */
	public function setRegisteredStoreId($registered_store_id)
	{
		$this->registered_store_id = $registered_store_id;
	}

	/**
	 *
	 * @return
	 */
	public function getBaseStoreId()
	{
		return $this->base_store_id;
	}

	/**
	 *
	 * @param $base_store_id
	 */
	public function setBaseStoreId($base_store_id)
	{
		$this->base_store_id = $base_store_id;
	}

	/**
	 *
	 * @return
	 */
	public function getLastUpdatedBy()
	{
		return $this->last_updated_by;
	}

	/**
	 *
	 * @return
	 */
	public function getLastUpdatedOn()
	{
		return $this->last_updated_on;
	}

	/**
	 *
	 * @return
	 */
	public function getLastUpdatedByUsername()
	{
		return $this->last_updated_by_username;
	}

	/**
	 *
	 * @return
	 */
	public function getFraudStatus()
	{
		return $this->fraud_status;
	}

	/**
	 *
	 * @return the linked trasactions as array of LoyaltyTransaction
	 */
	public function getTransactions()
	{
		return $this->transactionsLinked;
	}

	/**
	 *
	 * @return the linked trasactions as array of LoyaltyTransaction
	 */
	public function getCustomFields()
	{
		return $this->customFieldsLinked;
	}

	public function getPointsAwards()
	{
		return $this->pointsAwardsLinked;
	}
	
	/*
	 *  The function saves the data in to DB or any other data source,
	*  all the values need to be set using the corresponding setter methods.
	*  This can update the existing record if the id is already set.
	*/
	public function save()
	{
		try {
			parent::save();
		} catch (ApiException $e) {
			$this->logger->debug("Validation has failed at the parent, returning now");
			throw new ApiCustomerException(ApiCustomerException::SAVING_DATA_TO_USERS_FAILED);
		}

		$columns = array();

		if(isset($this->external_id))
			$columns["external_id"]= "'".$this->external_id."'";

		if(isset($this->current_points))
			$columns["loyalty_points"]= floor($this->current_points);

		if(isset($this->lifetime_points))
			$columns["lifetime_points"]= floor($this->lifetime_points);

		if(isset($this->slab_name))
			$columns["slab_name"]= floor($this->slab_name);

		if(isset($this->slab_number))
			$columns["slab_number"]= floor($this->slab_number);

		// last updated on
		$columns["last_updated"]= "NOW()";

		// last updated by
		$columns["last_updated_by"]= $this->current_user_id;

		// new user
		if(!$this->loyalty_id)
		{
			$this->logger->debug("Loyalty id is not set, so its going to be an insert query");

			$columns["joined"]= "'".Util::getMysqlDateTime($this->joined ? $this->joined : 'now')."'";
			$columns["registered_by"]= $this->current_user_id;
			$columns["base_store"]= $this->current_user_id;
			$columns["publisher_id"]= $this->current_org_id;
			$columns["user_id"] = $this->user_id;

			$sql = "INSERT INTO user_management.loyalty ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);

			$this->logger->debug("Return of saving the new user is $newId");

			if($newId > 0)
				$this->loyalty_id = $newId;

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

			$sql .= " WHERE id = $this->loyalty_id";
			$newId = $this->db_user->update($sql);
		}

		if($newId)
		{
			// update the cache
			$cacheKey = $this->generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_USER_ID, $this->user_id, $this->current_org_id);
			$this->saveToCache($cacheKey, "");
			
			$obj=LoyaltyCustomer::loadById($this->current_org_id, $this->user_id);
			$this->saveToCache($cacheKey, $obj->toString());
			
			if($obj->getEmail())
			{
				$cacheKeyEmail = $this->generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_EMAIL, $obj->getEmail(), $this->current_org_id);
				$obj->saveToCache($cacheKeyEmail, $obj->toString());
			}
			if($obj->getExternalId())
			{
				$cacheKeyExtId = $this->generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_EXT_ID, $obj->getExternalId(), $this->current_org_id);
				$obj->saveToCache($cacheKeyExtId, $obj->toString());
			}
			if($obj->getMobile())
			{
				$cacheKeyMobile = $this->generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_MOBILE, $obj->getMobile(), $this->current_org_id);
				$obj->saveToCache($cacheKeyMobile, $obj->toString());
			}
				
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
		if(!$this->loyalty_id)
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
		global $logger;
		$logger->debug("Loading from based on user id");

		if(!$user_id)
		{
			throw new ApiCustomerException(ApiCustomerException::FILTER_USER_ID_NOT_PASSED);
		}

		$cacheKey = self::generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_USER_ID, $user_id, $org_id);
		if(!$obj = self::loadFromCache($cacheKey))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new CustomerLoadFilters();
			$filters->user_id = $user_id;
			try{
				$array = self::loadAll($org_id, $filters, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];//self::fromArray->($array[0])->toArray()
			}
			throw new ApiCustomerException(ApiCustomerException::FILTER_NON_EXISTING_ID_PASSED);

		}
		else
		{
			$logger->debug("Loading from the Cache was successful. returning");
			$obj = self::fromString($org_id, $obj);
			return $obj;
		}
	}

	/*
	 *  The function loads the data linked to the object based on email,
	*/
	public static function loadByMobile($org_id, $mobile)
	{
		global $logger;
		$logger->debug("Loading from based on user id");

		if(!$mobile)
		{
			throw new ApiCustomerException(ApiCustomerException::FILTER_MOBILE_NOT_PASSED);
		}

		$cacheKeyMobile = self::generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_MOBILE, $mobile, $org_id);
		if(!$obj = self::loadFromCache($cacheKeyMobile))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new CustomerLoadFilters();
			$filters->mobile = $mobile;
			try{
				$array = self::loadAll($org_id, $filters, 1);

			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiCustomerException(ApiCustomerException::FILTER_NON_EXISTING_MOBILE_PASSED);

		}
		else
		{
			$logger->debug("Loading from the Cache was successful. returning");
			$obj = self::fromString($org_id, $obj);
			return $obj;
		}
	}


	/*
	 *  The function loads the data linked to the object based on email,
	*/
	public static function loadByEmail($org_id, $email)
	{
		global $logger;
		
		$logger->debug("Loading from based on user id");
		
		if(!$email)
		{
			throw new ApiCustomerException(ApiCustomerException::FILTER_MOBILE_NOT_PASSED);
		}
		$cacheKeyEmail = self::generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_EMAIL, $email, $org_id);
		if(!$obj = self::loadFromCache($cacheKeyEmail))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new CustomerLoadFilters();
			$filters->email = $email;
			try{
				$array = self::loadAll($org_id, $filters, 1);

			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiCustomerException(ApiCustomerException::FILTER_NON_EXISTING_EMAIL_PASSED);

		}
		else
		{
			$logger->debug("Loading from the Cache was successful. returning");
			$obj = self::fromString($org_id, $obj);
			return $obj; 
		}
	}

	/*
	 *  The function loads the data linked to the object based on email,
	*/
	public static function loadByExternalId($org_id, $external_id)
	{
		global $logger;
		$logger->debug("Loading from based on user id");
		
		if(!$external_id)
		{
			throw new ApiCustomerException(ApiCustomerException::FILTER_EXT_ID_NOT_PASSED);
		}
		
		$cacheKeyExtId = self::generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_EXT_ID, $external_id, $org_id);
		if(!$obj = self::loadFromCache($cacheKeyExtId))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new CustomerLoadFilters();
			$filters->external_id = $external_id;
			try{
				$array = self::loadAll($org_id, $filters, 1);

			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiCustomerException(ApiCustomerException::FILTER_NON_EXISTING_EXT_ID_PASSED);

		}
		else
		{
			$logger->debug("Loading from the Cache was successful. returning");
			$obj = self::fromString($org_id, $obj);
			return $obj; 
		}
	}


	/*
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof CustomerLoadFilters))
		{
			throw new ApiCustomerException(ApiCustomerException::FILTER_INVALID_OBJECT_PASSED);
		}
			
		// TODO : add fraud users clause
		$sql = "SELECT
		u.id as user_id,
		u.firstname,
		u.lastname,
		u.email,
		u.mobile,
		u.last_login,
		l.id as loyalty_id,
		l.external_id as external_id,
		l.registered_by as registered_store_id,
		l.base_store as base_store_id,
		l.last_updated_by,
		l.last_updated,
		oe.name as last_update_by_username,
		IFNULL(fu.status, l.loyalty_status) as fraud_status
		FROM user_management.users as u
		INNER JOIN user_management.loyalty as l
		ON u.id = l.user_id AND l.publisher_id = u.org_id
		LEFT JOIN masters.org_entities as oe
		ON oe.id = l.last_updated_by and oe.org_id = u.org_id and oe.type = 'TILL'
		LEFT JOIN user_management.fraud_users as fu
		ON fu.org_id = u.org_id AND fu.user_id = u.id
		WHERE u.org_id = $org_id
		";

		$idenitifersql = array();
		if($filters->user_id)
			$idenitifersql[] = " u.id= ".$filters->user_id;
		if($filters->mobile)
			$idenitifersql[] = " u.mobile = '".$filters->mobile."' ";
		if($filters->email)
			$idenitifersql[] = " u.email = '".$filters->email."' ";
		if($filters->external_id)
			$idenitifersql[] = " l.external_id = '".$filters->external_id."' ";

		$sql = $idenitifersql ? ($sql . "AND ( ".implode(" OR ", $idenitifersql) . ") ") : $sql;
		$sql .= " ORDER BY l.id desc ";
			
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;

		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;

		//print (str_replace("\t"," ", $sql))."\n\n";
		$sql = $sql . " LIMIT $offset, $limit";

		$db = new Dbase( 'users' );
		$array = $db->query($sql);

		if($array)
		{
			$ret = array();
			foreach($array as $row)
			{
				$classname= get_called_class();
				$obj = $classname::fromArray($org_id, $row);
				$ret[] = $obj;

				// adding to cache
				$cacheKey = self::generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_USER_ID, $obj->getUserId(), $org_id);
				$obj->saveToCache($cacheKey, $obj->toString());
				if($obj->getEmail())
				{
					$cacheKeyEmail = self::generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_EMAIL, $obj->getEmail(), $org_id);
					$obj->saveToCache($cacheKeyEmail, $obj->toString());
				}
				if($obj->getExternalId())
				{
					$cacheKeyExtId = self::generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_EXT_ID, $obj->getExternalId(), $org_id);
					$obj->saveToCache($cacheKeyExtId, $obj->toString());
				}
				if($obj->getMobile())
				{
					$cacheKeyMobile = self::generateCacheKey(LoyaltyCustomer::CACHE_KEY_PREFIX_MOBILE, $obj->getMobile(), $org_id);
					$obj->saveToCache($cacheKeyMobile, $obj->toString());
				}
			}
				
			return $ret;

		}

		throw new ApiCustomerException(ApiCustomerException::NO_CUSTOMER_MATCHES);

	}

	/*
	 * Loads all the transactions of the customer to object.
	* The setter method has to be used prior to set the customer id
	*/
	public function loadTransactions($limit=100, $offset = 0)
	{
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
		$this->transactionsLinked = LoyaltyTransaction::loadAll($this->current_org_id, $filters, $limit, $offset);
		return;
	}

	/*
	 * Loads all the transactions of the customer to object.
	* The setter method has to be used prior to set the customer id
	*/
	public function loadCustomFields()
	{
		// load the dependene class if required
		$this->initiateDependentObject('customFields');

		if(!$this->user_id)
		{
			$this->logger->debug("User id not set ");
			throw new ApiCustomerException(ApiCustomerException::FILTER_USER_ID_NOT_PASSED);
		}

		$this->customFieldsLinked = CustomField::loadForAssocId($this->current_user_id, $this->user_id, 'LOYALTY_REGISTRATION');
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
					include_once 'models/LoyaltyTransaction.php';
					$this->transaction = new LoyaltyTransaction($this->current_org_id, $this->user_id, $this->logger);
					$this->logger->debug("Loaded the member");
				}
				break;

			case 'customfields':
				if(!$this->customFields instanceof CustomField)
				{
					include_once 'models/CustomField.php';
					$this->customFields = new CustomField($this->current_org_id, $this->user_id, $this->logger);
					$this->logger->debug("Loaded the member");
				}
				break;
				
			default:
				$this->logger->debug("Requested member could not be resolved trying parent");
				parent::initiateDependentObject($memberName);
		}

	}
}
