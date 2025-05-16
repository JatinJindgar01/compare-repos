<?php
include_once ("models/BaseCustomerTransaction.php");
include_once ("models/TransactionReturnCustomer.php");
/**
 * @author cj
 *
 * The loyalty transaction specific operations will be triggered from here
 * The data will be flowing to user_management.loyalty_log
 * The transaction will be linked with a loyalty customer
 *
 */

class ReturnedTransaction extends BaseCustomerTransaction{

	protected $loyalty_log_id;
	protected $parent_loyalty_log_id; // the transaction to which it belongs in the case of mixed transaction
	protected $notes;
	protected $credit_note;	
	protected $points;
	protected $type;
	protected $delivery_status;
	
	public $parentTransaction;
	
	
	CONST CACHE_KEY_PREFIX = 'RETURN_BILL#';
	CONST CACHE_KEY_PREFIX_LOYALTY_LOG_ID = 'RETURN_BILL_LOYALTY_LOG_ID#';
	CONST CACHE_KEY_PREFIX_PARENT_TXN_ID  = 'RETURN_BILL_PARENT_ID#';
	
	public function __construct($current_org_id, $transction_id = null, $user_id = null)
	{
		parent::__construct($current_org_id, $transction_id, $user_id);
		
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
				"loyalty_id",
				"notes",
				"points",
				"credit_note",
				"type",
				'delivery_status', 
				"parent_loyalty_log_id",
		);
	
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	
	public function getLoyaltyLogId()
	{
	    return $this->loyalty_log_id;
	}

	public function setLoyaltyLogId($loyalty_log_id)
	{
		$this->loyalty_log_id = $loyalty_log_id;
	}
	
	public function getNotes()
	{
	    return $this->notes;
	}

	public function setNotes($notes)
	{
	    $this->notes = $notes;
	}

	public function getCreditNote()
	{
	    return $this->credit_note;
	}

	public function setCreditNote($credit_note)
	{
	    $this->credit_note = $credit_note;
	}

	public function getType()
	{
	    return $this->type;
	}

	public function setType($type)
	{
	    $this->type = $type;
	}

	public function getDeliveryStatus() {
	    return $this -> delivery_status;
	}

	public function setDeliveryStatus($deliveryStatus) {
	    $this -> delivery_status = $deliveryStatus;
	}

	public function getPoints()
	{
		return $this->points;
	}
	
	public function setPoints($points)
	{
		$this->points = $points;
	}
	
	public function setUserId($user_id)
	{
		parent::setUserId($user_id);
		
		//$this->initiateDependentObject('customer');
		try {
			$this->customer= TransactionReturnCustomer::loadById($this->current_org_id, $user_id);
		} catch (ApiCustomerException $e) {
			if($e->getCode() == ApiCustomerException::NO_CUSTOMER_MATCHES)
			{
				throw new ApiTransactionException(ApiTransactionException::CUSTOMER_NOT_FOUND);
			}
		}
	}

	public function getParentTransaction()
	{
		return $this->parentTransaction;
	}

	// the parent transaction id refers to the mixed transaction it was part of
	public function getParentLoyaltyLogId()
	{
		return $this->parent_loyalty_log_id;
	}
	
	// the parent transaction id refers to the mixed transaction it was part of
	public function setParentLoyaltyLogIdId($transactionId)
	{
		$this->parent_loyalty_log_id = $transactionId;
	}

	/*
	 *  The function saves the data in to DB or any other data source 
	*/
	public function save()
	{
// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			throw new ApiTransactionException(ApiTransactionException::VALIDATION_FAILED);
// 		}

		if(isset($this->user_id))
			$columns["user_id"]= $this->user_id;
		if(isset($this->transaction_number))
			$columns["bill_number"]= "'".$this->transaction_number."'";
		if(isset($this->credit_note))
			$columns["credit_note"]= "'".$this->credit_note."'";
		if(isset($this->transaction_amount))
			$columns["amount"]= $this->transaction_amount;
		if(isset($this->points))
			$columns["points"]= $this->points;
		if(isset($this->type))
			$columns["type"]= "'".$this->type."'";
		/*if(isset($this -> delivery_status))
			$columns["delivery_status"] = "'" . $this -> delivery_status . "'";*/
		if(isset($this->store_id))
			$columns["store_id"]= $this->store_id ? $this->store_id : $this->current_user_id;
		if(isset($this->transaction_date))
			$columns["returned_on"]= "'". Util::getMysqlDateTime($this->transaction_date)."'";
		if(isset($this->loyalty_log_id))
			$columns["loyalty_log_id"]= $this->loyalty_log_id;
		if(isset($this->parent_loyalty_log_id))
			$columns["parent_loyalty_log_id"]= $this->parent_loyalty_log_id;
		if(isset($this->notes))
			$columns["notes"]= "'".$this->notes."'";
		// new user

		if(!$this->transaction_id)
		{
			$columns["org_id"] = $this->current_org_id;
			$columns["added_on"] = "NOW()";
			$this->logger->debug("User id is not set, so its going to be an insert query");
		
			$sql = "INSERT INTO user_management.returned_bills ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);
				
			$this->logger->debug("Return of saving the new user is $newId");
				
			if($newId > 0)
				$this->transaction_id = $newId;
		}
		else
		{
			$this->logger->debug("User id is set, so its going to be an update query");
			$sql = "UPDATE user_management.returned_bills SET ";
		
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
				
			$sql=substr($sql,0,-2);
				
			$sql .= "WHERE id = $this->transaction_id";
			$newId = $this->db_user->update($sql);
		}

		if (isset($newId) && $newId > 0) {

			$safeDeliveryStatus = Util::mysqlEscapeString($this -> delivery_status);
			if (! empty($safeDeliveryStatus)) {
				// Continue to insert into the `transaction_delivery_status` table
				$statusSql = "INSERT INTO `user_management`.`transaction_delivery_status` " . 
								"SET `transaction_id` = " . $newId . ", " . 
										"`transaction_type` = 'RETURN', " . 
										"`delivery_status` = '" . $safeDeliveryStatus . "', " . 
										"`updated_by` = " . $columns["store_id"] . " " .  
							 "ON DUPLICATE KEY UPDATE " . 
							 		"`delivery_status` = '" . $safeDeliveryStatus . "', " . 
										"`updated_by` = " . $columns["store_id"];

				// Using Dbase -> update() instead of insert() to be able to run ON DUPLICATE KEY UPDATE
				$newDeliveryStatusId = $this -> db -> update($statusSql);
				$this -> logger -> debug('Transaction Return :: Delivery-status ID: ' . $newDeliveryStatusId);

				if (isset($newDeliveryStatusId) && $newDeliveryStatusId > 0) {
					// Continue to insert into the `transaction_delivery_status_changelog` table
					$statusLogSql = "INSERT INTO `user_management`.`transaction_delivery_status_changelog` " . 
										"SET `transaction_id` = " . $newId . ", " . 
											"`transaction_type` = 'RETURN', " . 
											"`delivery_status` = '" . $safeDeliveryStatus . "', " . 
											"`updated_by` = " . $store_id;
					$newDeliveryStatusLogId = $this -> db -> insert($statusLogSql);
					$this -> logger -> debug('Transaction Return :: Delivery-status-changelog ID: ' . $newDeliveryStatusLogId);

				}
			}

			self::saveToCache(self::CACHE_KEY_PREFIX.$this->current_org_id."##".$this->transaction_id, "");
			$obj = self::loadById($this->transaction_id);
			self::saveToCache(self::CACHE_KEY_PREFIX.$this->current_org_id."##".$this->transaction_id, $obj);
			//$this->loadById($this->transaction_id);
			
			return $newId;
		} else {
			throw new ApiTransactionException(ApiTransactionException::SAVING_DATA_FAILED);
		}
		
	}
	
	/*
	 * Validate the data before saving to DB/ for insert and update
	 * TODO: add the validators here
	*/
	public function validate()
	{
		return true;
	}
	
	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	 */
	public static function loadById($org_id, $transaction_id = null)
	{
		global $logger;
		$logger->debug("Loading from based on transaction id");
		
		if(!$transaction_id)
		{
			$logger->debug("The transaction id is not set yet");
			throw new ApiTransactionException(ApiTransactionException::FILTER_TRANSACTION_ID_NOT_PASSED);
		}
		
		
		if(!$obj = self::loadFromCache(self::CACHE_KEY_PREFIX.$org_id."##".$transaction_id))
		{
			$filters = new TransactionLoadFilters();
			$filters->transaction_id =  $transaction_id;
			try{
				$array = self::loadAll($org_id, $filters, 1);
			}catch(Exception $e){
				$logger->debug("Id based search has failed");
			}
			
			if($array[0])
			{
				return $array[0];
			}
			throw new ApiTransactionException(ApiTransactionException::FILTER_NON_EXISTING_TRANSACTION_ID_PASSED);
				
		}
		else
		{
			$logger->debug("Loading from cache");
			return $obj;
		}
		
	}

	/*
	 *  The function loads the return transactions in a given mixed txn
	*/
	public static function loadByParentLoyaltyLogId($org_id, $transaction_id = null)
	{
		global $logger;
		$logger->debug("Loading from based on transaction id");
	
		if(!$transaction_id)
		{
			$logger->debug("The transaction id is not set yet");
			throw new ApiTransactionException(ApiTransactionException::FILTER_TRANSACTION_ID_NOT_PASSED);
		}
		
		$cacheKey = self::generateCacheKey(ReturnedTransaction::CACHE_KEY_PREFIX_PARENT_TXN_ID, $transaction_id, $org_id);
		if($str = self::getFromCache($cacheKey))
		{
			$logger->debug("cache has the data");
			$array = self::decodeFromString($str);
			$ret = array();
			foreach($array as $row)
			{
				$obj = ReturnedTransaction::fromString($org_id, $row);
				$ret[] = $obj;
				//$logger->debug("data from cache" . $obj->toString());
			}
			return $ret;
		}

		else
		{
			$filters = new TransactionLoadFilters();
			$filters->parent_transaction_id =  $transaction_id;
			
			try{
				$array = self::loadAll($org_id, $filters, 1);
				
				foreach($array as $obj)
					$cacheStringArr[] = $obj->toString();
					
				if($cacheStringArr)
				{
					$str = self::encodeToString($cacheStringArr);
					self::saveToCache($cacheKey, $str);
						
				}
			}catch(Exception $e){
				$logger->debug("orginal txn Id based search has failed");
			}
			
			return  $array;
		}

	}

	
	/*
	 *  The function loads the return transactions in a given mixed txn
	*/
	public static function loadByLoyaltyLogId($org_id, $transaction_id = null)
	{
		global $logger;
		$logger->debug("Loading from based on transaction id");
	
		if(!$transaction_id)
		{
			$logger->debug("The transaction id is not set yet");
			throw new ApiTransactionException(ApiTransactionException::FILTER_TRANSACTION_ID_NOT_PASSED);
		}
		
		$cacheKey = self::generateCacheKey(ReturnedTransaction::CACHE_KEY_PREFIX_LOYALTY_LOG_ID, $transaction_id, $org_id);
		if($str = self::getFromCache($cacheKey))
		{
			$logger->debug("cache has the data");
			$array = self::decodeFromString($str);
			$ret = array();
			foreach($array as $row)
			{
				$obj = ReturnedTransaction::fromString($org_id, $row);
				$ret[] = $obj;
				//$logger->debug("data from cache" . $obj->toString());
			}
			return $ret;
		}
		

		else
		{
			$filters = new TransactionLoadFilters();
			$filters->orginal_loyalty_log_id =  $transaction_id;
			try{
				$array = self::loadAll($org_id, $filters, 1);
				
				foreach($array as $row)
				{
					$cacheStringArr[] = $obj->toString();
				}
					
				if($cacheStringArr)
				{
					$str = self::encodeToString($cacheStringArr);
					self::saveToCache($cacheKey, $str);
						
				}
			}catch(Exception $e){
				$logger->debug("orginal txn Id based search has failed");
			}
			
			return  $array;
		}
	}
	
	/*
	 * Load all the data into object based on the filters being passed.
	 * It should optionally decide whether entire dependency tree is required or not
	 * 
	 * TODO: add more filters
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof TransactionLoadFilters))
		{
			throw new ApiTransactionException(ApiTransactionException::FILTER_INVALID_OBJECT_PASSED);
		}
		
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
		IFNULL(fu.status, l.loyalty_status) as fraud_status,
		
		rb.id as transaction_id,
		rb.bill_number as transaction_number,
		rb.credit_note as credit_note,
		rb.amount as transaction_amount,
		rb.points as points,
		rb.store_id as store_id,
		rb.returned_on as transaction_date,
		rb.loyalty_log_id as loyalty_log_id,
		rb.parent_loyalty_log_id as parent_loyalty_log_id, 
		rb.type as type,
		tds.delivery_status, 
		rb.notes as notes,
		rb.added_on as added_on
		FROM user_management.users as u
		INNER JOIN user_management.returned_bills as rb
		ON u.id = rb.user_id AND rb.org_id = u.org_id
		INNER JOIN user_management.loyalty as l
		ON l.publisher_id = u.org_id AND l.user_id = u.id
		LEFT OUTER JOIN transaction_delivery_status AS tds 
			ON tds.transaction_id = rb.id 
			AND tds.transaction_type = 'RETURN' 
		LEFT JOIN masters.org_entities as oe
		ON oe.id = l.last_updated_by and oe.org_id = u.org_id and oe.type = 'TILL'
		LEFT JOIN user_management.fraud_users as fu
		ON fu.org_id = u.org_id AND fu.user_id = u.id
		WHERE u.org_id = $org_id";
		
		if($filters->transaction_id)
			$sql .= " AND rb.id= ".$filters->transaction_id;
        if($filters->start_id)
            $sql .= " AND rb.id >= ".$filters->start_id;
		if($filters->user_id)
			$sql .= " AND u.id= ".$filters->user_id;
		if($filters->loyalty_id)
			$sql .= " AND l.id= ".$filters->loyalty_id;
		if($filters->store_id)
			$sql .= " AND rb.store_id = ".$filters->store_id;
		if($filters->max_transaction_date)
			$sql .= " AND rb.returned_on <= '".date('Y-m-d 23:59:59', strtotime($filters->max_transaction_date))."'";
		if($filters->min_transaction_date)
			$sql .= " AND rb.returned_on >= '".date('Y-m-d 00:00:00', strtotime($filters->min_transaction_date))."'";
		if($filters->parent_transaction_id)
			$sql .= " AND rb.parent_loyalty_log_id = ".$filters->parent_transaction_id;
		if($filters->orginal_loyalty_log_id)
			$sql .= " AND rb.loyalty_log_id = ".$filters->orginal_loyalty_log_id;
        if($filters->transaction_number)
            $sql .= " AND rb.bill_number = '$filters->transaction_number'";
        if($filters->max_transaction_amount)
            $sql .= " AND rb.bill_amount <= " . $filters->max_transaction_amount;
        if($filters->min_transaction_amount)
            $sql .= " AND rb.bill_amount >= " . $filters->min_transaction_amount;
        if($filters->entered_by_id)
            $sql .= " AND rb.store_id = " . $filters->entered_by_id;
        if(! empty($filters->entered_by_ids))
            $sql .= " AND rb.store_id IN (" . implode(",", $filters->entered_by_ids) . ")";
		
		$sql .= " ORDER BY rb.id desc ";
			
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;

		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;

		$sql = $sql . " LIMIT $offset, $limit";
		//print str_replace("\t", " ", $sql);
		$db = new Dbase( 'users' );
		$array = $db->query($sql);
		
		if($array)
		{
			$ret = array();
			foreach($array as $row)
			{
				$obj = ReturnedTransaction::fromArray($org_id, $row);
				$ret[] = $obj;
			}
			return $ret;

		}
		throw new ApiTransactionException(ApiTransactionException::NO_TRANSACTION_MATCHES);
	}
	
	/*
	 * Loads all the lineitems of the transaction to object.
	* The setter method has to be used prior to set the transaction id
	*/
	public function loadLineItems()
	{
		include_once ("models/ReturnedLineitem.php");
		
		$filters = new LineitemLoadFilters();
		$filters->transaction_id = $this->transaction_id;
		return $this->lineitemsLinked = ReturnedLineitem::loadAll($this->current_org_id, $filters);
	}
	
	/*
	 * set the array from an array received from the select query
	*/
	public static function fromArray($org_id, $array)
	{
		global $logger;

		//loading the current object
		$logger->debug("Loading the tranasaction details");
		$obj = parent::fromArray($org_id, $array);
		
		//initiate the object
		$obj->loadCustomer();
		
		return $obj;
	}
	
	/*
	 * To load the customer details for teh current user
	 */
	public function loadCustomer()
	{
		$this->logger->debug("Loading the customer details");
		
		//initiate the object
		//$this->initiateDependentObject('customer');
		
		$this->customer = TransactionReturnCustomer::loadById($this->current_org_id, $this->user_id, 1);
	}

	public function loadParentTransaction()
	{
		include_once ("models/LoyaltyTransaction.php");
		$this->parentTransaction = LoyaltyTransaction::loadById($this->parent_transaction_id);
		
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
				
			case 'customer':
				if(!$this->if(!$this->lineitem instanceof ILineItem) instanceof ILineItem)
				{
					include_once 'models/TransactionReturnCustomer.php';
					$this->customer = new TransactionReturnCustomer($this->current_org_id);
					$this->logger->debug("Loaded the member");
				}
				break;
	
			case 'lineitem':
				if(!$this->lineitem instanceof ILineItem)
				{
					include_once 'models/ReturnedLineitem.php';
					$this->lineitem = new ReturnedLineitem($this->current_org_id);
					$this->logger->debug("Loaded the member");
				}
				break;
	
			default:
				$this->logger->debug("Requested member could not be resolved trying parent");
				parent::initiateDependentObject($memberName);
		}
	
	}
}
