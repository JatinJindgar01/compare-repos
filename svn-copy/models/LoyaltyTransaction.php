<?php
include_once ("models/BaseCustomerTransaction.php");
include_once ("models/LoyaltyCustomer.php");
/**
 * @author cj
 *
 * The loyalty transaction specific operations will be triggered from here
 * The data will be flowing to user_management.loyalty_log
 * The transaction will be linked with a loyalty customer
 *
 */

class LoyaltyTransaction extends BaseCustomerTransaction{

	protected $loyalty_id;
	protected $counter_id;
	protected $notes;
	protected $customFields;
	protected $delivery_status;

	protected $returnedTransactionsArr;
	
	CONST CACHE_KEY_PREFIX = 'LOYALTY_LOG#';
	CONST CACHE_KEY_ATTR_LINEITEM_PREFIX = "LINEITEM_LOYALTY_LOG#";
	
	public function __construct($current_org_id, $transction_id = null, $user_id = null)
	{
		parent::__construct($current_org_id, $transction_id, $user_id);
		
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
				"loyalty_id",
				"notes",
				'delivery_status', 
				"counter_id"
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
	 * @return 
	 */
	public function getNotes()
	{
	    return $this->notes;
	}

	/**
	 * 
	 * @param $notes
	 */
	public function setNotes($notes)
	{
	    $this->notes = $notes;
	}

	/**
	 *
	 * @return
	 */
	public function getCounterId()
	{
		return $this->counter_id;
	}
	
	/**
	 *
	 * @param $notes
	 */
	public function setCounterId($counter_id)
	{
		$this->counter_id = $counter_id;
	}
	
	public function getDeliveryStatus() {
	    return $this -> delivery_status;
	}

	public function setDeliveryStatus($deliveryStatus) {
	    $this -> delivery_status = $deliveryStatus;
	}

	public function setUserId($user_id)
	{
		parent::setUserId($user_id);
		
		//$this->initiateDependentObject('customer');
		try {
			$this->customer= LoyaltyCustomer::loadById($this->current_org_id, $user_id);
		} catch (ApiCustomerException $e) {
			if($e->getCode() == ApiCustomerException::NO_CUSTOMER_MATCHES)
			{
				throw new ApiTransactionException(ApiTransactionException::CUSTOMER_NOT_FOUND);
			}
		}
	}
	/*
	 *  The function saves the data in to DB or any other data source 
	*/
	public function save()
	{
		if(!$this->customer->getLoyaltyId())
		{
			try {
				$this->loadCustomer();
			} catch (Exception $e) {
				throw new ApiTransactionException(ApiTransactionException::CUSTOMER_NOT_FOUND);
			}
		}
		
		$columns["loyalty_id"]= $this->customer->getLoyaltyId();
		$columns["user_id"]= $this->customer->getUserId();
		$columns["org_id"]= $this->current_org_id;

		if(isset($this->transaction_number))
			$columns["bill_number"]= "'". $this->transaction_number."'";
		if(isset($this->transaction_date))
			$columns["date"]= "'". Util::getMysqlDateTime($this->transaction_date)."'";
		if(isset($this->transaction_amount))
			$columns["bill_amount"]= $this->transaction_amount;
		if(isset($this->gross_amount))
			$columns["bill_gross_amount"]= $this->gross_amount;
		if(isset($this->discount))
			$columns["bill_discount"]= $this->discount;
		if(isset($this->notes))
			$columns["notes"]= "'".$this->notes."'";
		if(isset($this->outlier_status))
			$columns["outlier_status"]= "'".$this->outlier_status."'";
		
		$columns["entered_by"]= $this->current_user_id;
		$columns["counter_id"]= $this->counter_id ? $this->counter_id :  $this->current_user_id;

// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			throw new ApiTransactionException(ApiTransactionException::VALIDATION_FAILED);
// 		}
		
		$ret = true;
		try{
			// validate all the line items
			foreach($this->lineitemsLinked as $lineitem)
			{
				$ret &= $lineitem->validate();
			}
		}catch (ApiException $e){
			throw new ApiTransactionException(ApiTransactionException::VALIDATION_FAILED);
		}
		
		// new user
		if(!$this->transaction_id)
		{
			$this->logger->debug("User id is not set, so its going to be an insert query");
		
			$sql = "INSERT INTO user_management.loyalty_log ";
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
			$sql = "UPDATE user_management.loyalty_log SET ";
		
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
				
			$sql=substr($sql,0,-2);
				
			$sql .= "WHERE id = $this->transaction_id";
			$newId = $this->db_user->update($sql);
		}
		
		if($newId)
		{
			// save the line items
			foreach ($this->lineitemsLinked as $lineitem)
			{
				$lineitem->setTransactionId($this->transaction_id);
				$save = $lineitem->save();
				$this->logger->debug("saving the line item ". $lineitem->toString(). " with response as ". var_export($save, true));
			}
			
			$cacheKey = $this->generateCacheKey(LoyaltyTransaction::CACHE_KEY_PREFIX, $this->transaction_id, $this->current_org_id);
			$this->saveToCache($cacheKey, "");
			$obj = self::loadById($this->current_org_id, $this->transaction_id);
			$this->saveToCache($cacheKey, $obj->toString());
			return;
		}
		else
		{
			throw new ApiTransactionException(ApiTransactionException::SAVING_DATA_FAILED);
		}
		
	}
	
	/*
	 * Validate the data before saving to DB/ for insert and update
	 * TODO: add the validators here
	*/
	public function validate()
	{
		$ret = true;
		
		// validate transationt date
		$ret &= $this->validateTransactionDate();
		
		// validate amount
		$ret &= $this->validateTransactionAmount();
		
		// validate discount
		$ret &= $this->validateDiscount();

		// validate gross - discount = txn amt
		$ret &= $this->validateGrossDiscountAmount();
		
		// sum line item
		$ret &= $this->validateGrossAmountLineItemAmt();
		
		return $ret; // $this->validationErrorArr ? false : true;
	}

	protected function validateGrossAmountLineItemAmt()
	{
		$lineitemAmtArr = array();
		foreach($this->lineitemsLinked as $lineitem)
		{
			if($lineitem instanceof LoyaltyLineitem)
				$lineitemAmtArr []= $lineitem->getGrossAmount();
		}
	
	
		/************* gross - discount = amount  *************/
		include_once 'apiHelper/DataValueValidator.php';
		$ret = true;
		$this->initiateDependentObject('configMgr');
		
		// check for negative qty
		if($this->configMgr->getKey("API_VALIDATION_TXN_LI_SUM_AMOUNT"))
		{
			$this->logger->debug("check if the sum(lineitem amt) = gross amount");
			if(!DataValueValidator::validateSum($lineitemAmtArr, $this->gross_amount))
			{
				$validationError[] = "Gross amount - discount != value";
				$ret = false;
			}
		}
		
		return $ret;
		
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
		$cacheKey = self::generateCacheKey(LoyaltyTransaction::CACHE_KEY_PREFIX, $transaction_id, $org_id);
		if(!$obj = self::loadFromCache($cacheKey))
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
			$obj = self::fromString($org_id, $obj);
			return $obj;
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
		ll.id as transaction_id, 
		ll.bill_number as transaction_number, 
		ll.date as transaction_date, 
		ll.notes as notes,
		ll.bill_amount as transaction_amount, 
		ll.bill_gross_amount as gross_amount, 
		ll.bill_discount as discount,
		ll.outlier_status as outlier_status,
		tds.delivery_status, 
		ll.entered_by as store_id, 
		ll.counter_id as counter_id
		FROM user_management.users as u
		INNER JOIN user_management.loyalty as l
		ON u.id = l.user_id AND l.publisher_id = u.org_id
		INNER JOIN user_management.loyalty_log as ll
		ON ll.org_id = u.org_id AND ll.user_id = u.id
		LEFT OUTER JOIN transaction_delivery_status AS tds 
			ON tds.transaction_id = ll.id 
			AND tds.transaction_type = 'REGULAR' 
		LEFT JOIN masters.org_entities as oe
		ON oe.id = l.last_updated_by and oe.org_id = u.org_id and oe.type = 'TILL'
		LEFT JOIN user_management.fraud_users as fu
		ON fu.org_id = u.org_id AND fu.user_id = u.id
		WHERE u.org_id = $org_id";
		
		if($filters->transaction_id)
			$sql .= " AND ll.id= ".$filters->transaction_id;
        if($filters->start_id)
            $sql .= " AND ll.id >= ".$filters->start_id;
		if($filters->user_id)
			$sql .= " AND u.id= ".$filters->user_id;
		if($filters->loyalty_id)
			$sql .= " AND l.id= ".$filters->loyalty_id;
		if($filters->store_id)
			$sql .= " AND ll.entered = ".$filters->store_id;
		if($filters->outlier_status)
			$sql .= " AND ll.outlier_status = '".$filters->outlier_status."'";
		if($filters->max_transaction_date)
			$sql .= " AND ll.date <= '".date('Y-m-d 23:59:59', strtotime($filters->max_transaction_date))."'";
		if($filters->min_transaction_date)
			$sql .= " AND ll.date >= '".date('Y-m-d 00:00:00', strtotime($filters->min_transaction_date))."'";
		if($filters->transaction_number)
			$sql .= " AND ll.bill_number = '$filters->transaction_number'";
        if($filters->max_transaction_amount)
            $sql .= " AND ll.bill_amount <= " . $filters->max_transaction_amount;
        if($filters->min_transaction_amount)
            $sql .= " AND ll.bill_amount >= " . $filters->min_transaction_amount;
        if($filters->entered_by_id)
            $sql .= " AND ll.entered_by = " . $filters->entered_by_id;
        if(! empty($filters->entered_by_ids))
            $sql .= " AND ll.entered_by IN (" . implode(",", $filters->entered_by_ids) . ")";
		
		$sql .= " ORDER BY ll.date desc ";
			
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;

		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;

		$sql = $sql . " LIMIT $offset, $limit";
		
		$db = new Dbase( 'users' );
		$array = $db->query($sql);
		if($array)
		{
			$ret = array();
			foreach($array as $row)
			{
				$obj = LoyaltyTransaction::fromArray($org_id, $row);
				$cacheKey = self::generateCacheKey(LoyaltyTransaction::CACHE_KEY_PREFIX, $obj->getTransactionId(), $org_id);
				$obj->saveToCache($cacheKey, $obj->toString());
				$ret[] = $obj;
			}
			return $ret;

		}
		throw new ApiTransactionException(ApiTransactionException::NO_TRANSACTION_MATCHES);
	}

	/*
	 * To line items to a bill and to save it 
	 */
	public function attachLineItems($lineitemObj)
	{
		// add the line item to transaction
		if($lineitemObj instanceof ILineItem)
			$this->lineitemsLinked[] = $lineitemObj;
		else
			throw new Exception(ApiLineitemException::INVALID_LINE_ITEM_TYPE);
	}
	
	/*
	 * Loads all the lineitems of the transaction to object.
	* The setter method has to be used prior to set the transaction id
	*/
	public function loadLineItems()
	{
		//$this->initiateDependentObject('lineitem');
		if(!$this->transaction_id)
		{
			$this->logger->debug("Transaction id not set ");
			throw new ApiTransactionException(ApiTransactionException::FILTER_TRANSACTION_ID_NOT_PASSED);
		}
		
		//TODO:: add the caching here
		include_once ("models/LoyaltyLineitem.php");
		$ret = array();
		$cacheKey = $this->generateCacheKey(LoyaltyTransaction::CACHE_KEY_ATTR_LINEITEM_PREFIX, $this->transaction_id, $this->current_org_id);
		if($str = $this->getFromCache($cacheKey))
		{
			$this->logger->debug("LI - Data found in cache");
			$array = $this->decodeFromString($str);
			foreach($array as $row)
			{
				$obj = LoyaltyLineitem::fromString($this->current_org_id, $row);
				$ret[] = $obj;
				//$this->logger->debug("data from cache" . $obj->toString());
			}
				
			$this->lineitemsLinked = $ret;
			return $this->lineitemsLinked;
		}
		
		else 
		{
			$this->logger->debug("LI - Reading from DB");
			$filters = new LineitemLoadFilters();
			$filters->transaction_id = $this->transaction_id;
			try {
				$this->lineitemsLinked = LoyaltyLineitem::loadAll($this->current_org_id, $filters);
				$cacheStringArr = array();
				foreach($this->lineitemsLinked as $li)
				{
					$cacheStringArr[] = $li->toString();
				}
				$str = $this->encodeToString($cacheStringArr);
				$this->saveToCache($cacheKey, $str);
				
			} catch (Exception $e) {
				$this->lineitemsLinked = null;
			}
			
			
			return $this->lineitemsLinked;
		}
	}
	
	public function loadReturnTransactions()
	{
		if(!$this->transaction_id)
		{
			$this->logger->debug("Transaction id not set ");
			throw new ApiTransactionException(ApiTransactionException::FILTER_TRANSACTION_ID_NOT_PASSED);
		}
		//TODO:: add the caching here
		include_once ("models/ReturnedTransaction.php");
		
		$filters = new TransactionLoadFilters();
		$filters->parent_transaction_id = $this->transaction_id;
		$this->returnedTransactionsArr = ReturnedTransaction::loadAll($this->current_org_id, $filters);
		
	}
	
	/*
	 * To load the customer details for teh current user
	 */
	public function loadCustomer()
	{
		$this->logger->debug("Loading the customer details");
		
		//initiate the object
		//$this->initiateDependentObject('customer');
		
		$this->customer = LoyaltyCustomer::loadById($this->current_org_id, $this->user_id, 1);
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
				if(!$this->transaction instanceof ITransaction)
				{
					include_once 'models/LoyaltyCustomer.php';
					$this->customer = new LoyaltyCustomer($this->current_org_id);
					$this->logger->debug("Loaded the member");
				}
				break;
	
				
			case 'customfields':
				if(!$this->customFields instanceof CustomField)
				{
					include_once 'models/CustomField.php';
					$this->customFields = new CustomField($this->current_org_id, $this->user_id, 'LOYALTY_TRANSACTION');
					$this->logger->debug("Loaded the member");
				}
				break;
	
			default:
				$this->logger->debug("Requested member could not be resolved trying parent");
				parent::initiateDependentObject($memberName);
		}
	
	}
}
