<?php
include_once ("models/BaseLineItem.php");
include_once ("models/ReturnedTransaction.php");
include_once ("models/filters/ApiLineitemLoadFilters.php");
include_once ("exceptions/ApiLineitemException.php");

/**
 * @author cj
 *
 * The class for all the line item return.
 *
*/
class ReturnedLineitem extends BaseLineItem {


	protected $user_id;
	protected $serial_number;
	
	public $loyalty_log_id;
	public $loyalty_lineitem_id;
	
	public $parentTransaction;
	public $parentLineitem;
	
	protected static $iterableMembers;
	CONST CACHE_KEY_PREFIX = "RETURN_LINEITEM_ID#";

	public function __construct($current_org_id, $lineitem_id = null)
	{
		parent::__construct($current_org_id, $lineitem_id);
	}

	public static function setIterableMembers()
	{
	
		$local_members = array(
				"user_id",
				"serial_number",
				"loyalty_log_id",
				"loyalty_lineitem_id"
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

	public function getSerialNumber()
	{
		return $this->serial_number;
	}
	
	public function setSerialNumber($serial_number)
	{
		$this->serial_number = $serial_number;
	}
	
	public function getLoyaltyLogId()
	{
		return $this->loyalty_log_id;
	}

	public function setLoyaltyLogId($loyalty_log_id)
	{
		$this->loyalty_log_id = $loyalty_log_id;
	}

	public function getLoyaltyLineitemId()
	{
		return $this->loyalty_lineitem_id;
	}

	public function setLoyaltyLineitemId($loyalty_lineitem_id)
	{
		$this->loyalty_lineitem_id = $loyalty_lineitem_id;
	}



	/*
	 *  The function saves the data in to DB or any other data source for a line items,
	*  All the values need to be set using the corresponding setter methods.
	*  This can update the existing record if the id is already set.
	*  The list of updatable fields need to be checked well in advance; by default, updates should be avoided
	*/
	public function save()
	{
// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			throw new ApiLineitemException(ApiLineitemException::VALIDATION_FAILED);
// 		}

		$columns = array();

		if(!isset($this->transaction_id))
		{
			//$this->initiateDependentObject('transaction');
			try{
				$this->transaction = ReturnedTransaction::loadById($this->current_org_id, $this->transaction_id);
			}catch(ApiTransactionException $e)
			{
				if($e->getCode() == ApiTransactionException::NO_TRANSACTION_MATCHES)
				{
					throw new ApiLineitemException(ApiLineitemException::TRANSACTION_NOT_FOUND);
				}
			}
			
			$columns["user_id"] = $this->transaction->getUserId();
		}

		if(isset($this->transaction_id))
			$columns["loyalty_log_id"]= $this->loyalty_log_id;

		if(isset($this->loyalty_lineitem_id))
			$columns["loyalty_log_id"]= $this->loyalty_lineitem_id;
		
		if(isset($this->transaction_id))
			$columns["return_bill_id"]= $this->transaction_id;
		
		if(isset($this->serial_number))
			$columns["serial"]= "'".$this->serial_number."'";

		if(isset($this->item_code))
			$columns["item_code"]= "'".$this->item_code."'";

		if(isset($this->rate))
			$columns["rate"]= $this->rate;

		if(isset($this->qty))
			$columns["qty"]= $this->qty;

		if(isset($this->gross_amount))
			$columns["value"]= $this->gross_amount;

		if(isset($this->discount))
			$columns["discount_value"]= $this->discount;

		if(isset($this->transaction_amount))
			$columns["amount"]= $this->transaction_amount;

		if(isset($this->item_id))
			$columns["inventory_item_id"]= $this->item_id;

		if(isset($this->outlier_status))
			$columns["outlier_status"]= $this->outlier_status;

		$columns["store_id"]= $this->current_user_id;

		// new user
		if(!$this->lineitem_id)
		{
			$this->logger->debug("User id is not set, so its going to be an insert query");

			$sql = "INSERT INTO user_management.returned_bills_lineitems ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);

			$this->logger->debug("Return of saving the new lineitem is $newId");

			if($newId > 0)
				$this->lineitem_id = $newId;
		}
		else
		{
			$this->logger->debug("User id is set, so its going to be an update query");
			throw ApiLineitemException(ApiLineitemException::FUNCTION_NOT_IMPLEMENTED);
			
// 			$sql = "UPDATE user_management.returned_bills_lineitems SET ";

// 			// formulate the update query
// 			foreach($columns as $key=>$value)
// 				$sql .= " $key = $value, ";

// 			$sql=substr($sql,0,-1);
// 			$sql .= "WHERE id = $this->lineitem_id";
// 			$newId = $this->db_user->update($sql);
		}

		if($newId)
		{
			self::saveToCache(self::CACHE_KEY_PREFIX.$this->current_org_id."##".$this->lineitem_id, "");
			$obj = self::loadById($this->current_org_id, $this->lineitem_id);
			self::saveToCache(self::CACHE_KEY_PREFIX.$this->current_org_id."##".$this->lineitem_id, $obj);
			return true;
		}
		else
		{
			throw new ApiLineitemException(ApiLineitemException::SAVING_DATA_FAILED);
		}
		
	}

	/*
	 * Validate the data before saving to DB/ for insert and update
	*/
	public function validate()
	{

		return true;
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	 */
	public static function loadById($org_id, $lineitem_id)
	{
		global $logger; 

		$logger->debug("Loading from based on line item id");

		if(!$lineitem_id)
		{
			$logger->debug("The line item id is not passed");
			throw new ApiLineitemException(ApiLineitemException::FILTER_LINEITEM_ID_NOT_PASSED);
		}

		if(!$obj = self::loadFromCache($ineitem_id))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");
			$filters = new LineitemLoadFilters();
			$filters->lineitem_id = $lineitem_id;
			
			try{
				$array = self::loadAll($org_id, $filters, 1);
			}catch(Exception $e){
				$this->logger->debug("Id based search has failed");
			}
				
			if($array[0])
			{
				return $array[0];
			}
			throw new ApiLineitemException(ApiLineitemException::FILTER_NON_EXISTING_LINEITEM_ID_PASSED);

		}
		else
		{
			$this->logger->debug("Loading from the Cache was successful. returning");
			return $obj;
		}
			
			
	}

	/*
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof LineitemLoadFilters))
		{
			throw new ApiLineitemException(ApiLineitemException::FILTER_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT
		rbl.id as lineitem_id,
		rbl.user_id as user_id,
		rbl.return_bill_id as transaction_id,
		rbl.serial as serial_number,
		rbl.item_code as item_code,
		rbl.rate as rate,
		rbl.qty as qty,
		rbl.value as gross_amount,
		rbl.discount_value as discount,
		rbl.amount as transaction_amount,
		rbl.lbl_id as loyalty_lineitem_id,
		rbl.loyalty_log_id as loyalty_log_id,
		rbl.points as points
		FROM user_management.returned_bills_lineitems as rbl
		WHERE rbl.org_id = $org_id ";
		
		if($filters->lineitem_id)
			$sql .= " AND rbl.id in (".$filters->lineitem_id.")";
		if($filters->user_id)
			$sql .= " AND rbl.user_id= ".$filters->user_id;
		if($filters->transaction_id)
			$sql .= " AND rbl.return_bill_id = ".$filters->transaction_id." ";
		if($filters->item_code)
			$sql .= " AND rbl.item_code = '".$filters->item_code."' ";
		if($filters->item_id)
			$sql .= " AND rbl.inventory_item_id = ".$filters->item_id." ";
		if($filters->parent_transaction_id)
			$sql .= " AND rbl.loyalty_log_id = ".$filters->parent_transaction_id." ";
		if($filters->parent_lineitem_id)
			$sql .= " AND rbl.lbl_id = ".$filters->parent_lineitem_id." ";
		
		$sql .= " ORDER BY rbl.return_bill_id desc, rbl.id asc ";

		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		$sql = $sql . " LIMIT $offset, $limit";
		
		// TODO: add more filters here
		$db = new Dbase( 'users' );
		$array = $db->query($sql);
		
		if($array)
		{
		
			$ret = array();
			foreach($array as $row)
			{
				$obj = ReturnedLineitem::fromArray($org_id, $row);
				$ret[] = $obj;
			}
				
			return $ret;
		
		}
		
		throw new ApiLineitemException(ApiLineitemException::NO_LINEITEM_MATCHES);
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
					$this->transaction = new ReturnedTransaction($this->current_org_id, $this->user_id, $this->logger);
					$this->logger->debug("Loaded the member");
				}
				break;

			default:
				$this->logger->debug("Requested member could not be resolved trying parent");
				parent::initiateDependentObject($memberName);
		}
	}

}
