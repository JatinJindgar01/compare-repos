<?php
include_once ("models/BaseLineItem.php");
include_once ("models/NonInterestedTransaction.php");
include_once ("models/filters/ApiLineitemLoadFilters.php");
include_once ("exceptions/ApiLineitemException.php");

/**
 * @author cj
 *
 * The base class for all the non-interetsed loyalty transactions .
 * All the other line items including
 *
*/
class NotInterestedLineitem extends BaseLineItem {


	protected $outlier_status;
	protected $serial_number;
	
	protected static $iterableMembers;

	public function __construct($current_org_id, $lineitem_id = null)
	{
		parent::__construct($current_org_id, $lineitem_id);
	}

	public static function setIterableMembers()
	{
	
		$local_members = array(
				"outlier_status",
				"serial_number"
		);
			
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}
	
	public function getOutlierStatus()
	{
		return $this->outlier_status;
	}

	public function setOutlierStatus($outlier_status)
	{
		$this->outlier_status = $outlier_status;
	}

	public function getSerialNumber()
	{
		return $this->serial_number;
	}

	public function setSerialNumber($serial_number)
	{
		$this->serial_number = $serial_number;
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
				$this->transaction = NonInterestedTransaction::loadById($this->current_org_id, $this->transaction_id);
			}catch(ApiTransactionException $e)
			{
				if($e->getCode() == ApiTransactionException::NO_TRANSACTION_MATCHES)
				{
					throw new ApiLineitemException(ApiLineitemException::TRANSACTION_NOT_FOUND);
				}
			}
		}


		if(isset($this->transaction_id))
			$columns["not_interested_bill_id"]= "'".$this->transaction_id."'";

		if(isset($this->serial_number))
			$columns["serial"]= "'".$this->serial_number."'";

		if(isset($this->item_code))
			$columns["item_code"]= "'".$this->item_code."'";

		if(isset($this->description))
			$columns["description"]= "'".$this->description."'";

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

		if(isset($this->item_id))
			$columns["inventory_item_id"]= "'".$this->item_id."'";
		
		$columns["store_id"]= $this->current_user_id;

		// new user
		if(!$this->lineitem_id)
		{
			$this->logger->debug("User id is not set, so its going to be an insert query");

			$sql = "INSERT INTO user_management.loyalty_not_interested_bill_lineitems ";
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
			$this->logger->debug("line item updation is not allowed");
			throw ApiLineitemException(ApiLineitemException::FUNCTION_NOT_IMPLEMENTED);

// 			$sql = "UPDATE user_management.loyalty_bill_lineitems SET ";

// 			// formulate the update query
// 			foreach($columns as $key=>$value)
// 				$sql .= " $key = $value, ";

// 			$sql=substr($sql,0,-1);
// 			$sql .= "WHERE id = $this->lineitem_id";
// 			$newId = $this->db_user->update($sql);
		}

		if($newId)
		{
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

		//if(!$obj = self::loadFromCache($lineitem_id))
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
// 		else
// 		{
// 			$this->logger->debug("Loading from the Cache was successful. returning");
// 			return $obj;
// 		}
			
			
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
		nibl.id as lineitem_id,
		nibl.not_interested_bill_id as transaction_id,
		nibl.serial as serial_number,
		nibl.item_code as item_code,
		nibl.description as description,
		nibl.rate as rate,
		nibl.qty as qty,
		nibl.value as gross_amount,
		nibl.discount_value as discount,
		nibl.amount as transaction_amount,
		nibl.store_id as store_id,
		nibl.inventory_item_id as item_id,
		nibl.outlier_status as outlier_status,
		nibl.auto_update_time as last_updated_on
		FROM user_management.loyalty_not_interested_bill_lineitems as nibl
		WHERE nibl.org_id = $org_id ";
		
		if($filters->lineitem_id)
			$sql .= " AND nibl.id in (".$filters->lineitem_id.")";
		if($filters->transaction_id)
			$sql .= " AND nibl.not_interested_bill_id = ".$filters->transaction_id." ";
		if($filters->item_code)
			$sql .= " AND nibl.item_code = '".$filters->item_code."' ";
		if($filters->item_id)
			$sql .= " AND nibl.inventory_item_id = ".$filters->item_id." ";
		if($filters->outlier_status)
			$sql .= " AND nibl.outlier_status= '".$filters->outlier_status."' ";
		
		$sql .= " ORDER BY nibl.not_interested_bill_id desc, nibl.id asc ";

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
				$obj = NotInterestedLineitem::fromArray($org_id, $row);
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
					include_once 'models/NotInterestedTransaction.php';
					$this->transaction = new NotInterestedTransaction($this->current_org_id, $this->user_id, $this->logger);
					$this->logger->debug("Loaded the member");
				}
				break;

			default:
				$this->logger->debug("Requested member could not be resolved trying parent");
				parent::initiateDependentObject($memberName);
		}
	}

}
