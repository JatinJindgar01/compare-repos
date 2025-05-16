<?php
include_once ("models/BaseNonCustomerTransaction.php");
/**
 * @author cj
 *
 * The loyalty transaction specific operations will be triggered from here
 * The data will be flowing to user_management.loyalty_log
 * The transaction will be linked with a loyalty customer
 *
 */

class NotInterestedTransaction extends BaseNonCustomerTransaction{

	protected $notes;
	protected $delivery_status;
	CONST CACHE_KEY_PREFIX = 'NOT_INTERESTED_TXN#';
	
	public function __construct($current_org_id, $transction_id = null)
	{
		parent::__construct($current_org_id, $transction_id);
		
	}
	
	public static function setIterableMembers()
	{
	
		$local_members = array(
				"notes", 
				'delivery_status'
		);

		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}

	public function getNotes()
	{
		return $this->notes;
	}

	public function setNotes($notes)
	{
		$this->notes = $notes;
	}

	public function getDeliveryStatus() {
	    return $this -> delivery_status;
	}

	public function setDeliveryStatus($deliveryStatus) {
	    $this -> delivery_status = $deliveryStatus;
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

		if(isset($this->transaction_number))
			$columns["bill_number"]= "'". $this->transaction_number."'";
		if(isset($this->transaction_amount))
			$columns["bill_amount"]= $this->transaction_amount;
		if(isset($this->notes))
			$columns["reason"]= "'".$this->notes."'";
		if(isset($this->transaction_date))
			$columns["billing_time"]= "'". Util::getMysqlDateTime($this->transaction_date)."'";
		if(isset($this->outlier_status))
			$columns["outlier_status"]= "'".$this->outlier_status."'";
		// new user
		if(!$this->transaction_id)
		{
			$columns["org_id"]= $this->current_org_id;
			$columns["entered_by"]= $this->current_user_id;
			
			$this->logger->debug("User id is not set, so its going to be an insert query");
		
			$sql = "INSERT INTO user_management.loyalty_not_interested_bills ";
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
			$sql = "UPDATE user_management.loyalty_not_interested_bills SET ";
		
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
				
			$sql=substr($sql,0,-2);
				
			$sql .= "WHERE id = $this->transaction_id";
			$newId = $this->db_user->update($sql);
		}
		
		if($newId)
		{
			//$this->loadById($this->transaction_id);
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
		
		//if(!$obj = self::loadFromCache($transaction_id))
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
// 		else
// 		{
// 			$logger->debug("Loading from cache");
// 			return $obj;
// 		}
		
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
		nbl.id as transaction_id, 
		nbl.bill_number as transaction_number, 
		nbl.billing_time as transaction_date,
		nbl.reason as notes,
		nbl.bill_amount as transaction_amount, 
		'' as gross_amount,
		'' as discount,
		nbl.outlier_status as outlier_status,
		tds.delivery_status, 
		nbl.entered_by as store_id 
		FROM user_management.loyalty_not_interested_bills as nbl
		LEFT OUTER JOIN transaction_delivery_status AS tds 
			ON tds.transaction_id = nbl.id 
			AND tds.transaction_type = 'NOT_INTERESTED' 
		WHERE nbl.org_id = $org_id";
		
		if($filters->transaction_id)
			$sql .= " AND nbl.id= ".$filters->transaction_id;
        if($filters->start_id)
            $sql .= " AND nbl.id >= ".$filters->start_id;
		if($filters->store_id)
			$sql .= " AND nvl.entered_by = ".$filters->store_id;
		if($filters->outlier_status)
			$sql .= " AND nbl.outlier_status = '".$filters->outlier_status."'";
		if($filters->max_transaction_date)
			$sql .= " AND nbl.billing_time <= '".date('Y-m-d 23:59:59', strtotime($filters->max_transaction_date))."'";
		if($filters->min_transaction_date)
			$sql .= " AND nbl.billing_time >= '".date('Y-m-d 00:00:00', strtotime($filters->min_transaction_date))."'";
		if($filters->transaction_number)
			$sql .= " AND nbl.bill_number = '$filters->transaction_number'";
        if($filters->max_transaction_amount)
            $sql .= " AND nbl.bill_amount <= " . $filters->max_transaction_amount;
        if($filters->min_transaction_amount)
            $sql .= " AND nbl.bill_amount >= " . $filters->min_transaction_amount;
        if($filters->entered_by_id)
            $sql .= " AND nbl.entered_by = " . $filters->entered_by_id;
        if(! empty($filters->entered_by_ids))
            $sql .= " AND nbl.entered_by IN (" . implode(",", $filters->entered_by_ids) . ")";
        if(! $filters->include_retro)
		    $sql .= " AND nbl.outlier_status != 'RETRO'";
		$sql .= " ORDER BY nbl.id desc ";
			
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
				$obj = NotInterestedTransaction::fromArray($org_id, $row);
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
		//$this->initiateDependentObject('lineitem');
		
		if(!$this->transaction_id)
		{
			$this->logger->debug("Transaction id not set ");
			throw new ApiTransactionException(ApiTransactionException::FILTER_TRANSACTION_ID_NOT_PASSED);
		}
		include_once ("models/NotInterestedLineitem.php");
		
		$filters = new LineitemLoadFilters();
		$filters->transaction_id = $this->transaction_id;
		$this->lineitemsLinked = NotInterestedLineitem::loadAll($this->current_org_id, $filters);
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
		
		return $obj;
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
			case 'lineitem':
				if(!$this->lineitem instanceof ILineItem)
				{
					include_once 'models/NotInterestedLineitem.php';
					$this->lineitem = new NotInterestedLineitem($this->current_org_id);
					$this->logger->debug("Loaded the member");
				}
				break;
				
			default:
				$this->logger->debug("Requested member could not be resolved trying parent");
				parent::initiateDependentObject($memberName);
		}
	
	}
}
