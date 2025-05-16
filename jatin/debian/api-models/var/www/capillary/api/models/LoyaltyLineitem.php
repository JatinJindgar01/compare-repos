<?php
include_once ("models/BaseLineItem.php");
include_once ("models/LoyaltyTransaction.php");

/**
 * @author cj
 *
 * The base class for all the transaction.
 * All the other transactions line items including
 * the normal, return, emi, not-interested etc should be extending the class
 *
*/
class LoyaltyLineitem extends BaseLineItem {


	protected $user_id;
	protected $outlier_status;
	protected $serial_number;

	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'LOYALTY_LINEITEM_#';

	public function __construct($current_org_id, $lineitem_id = null)
	{
		parent::__construct($current_org_id, $lineitem_id);
	}

	public static function setIterableMembers()
	{

		$local_members = array(
				"user_id",
				"outlier_status",
				"serial_number"
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
				$this->transaction = LoyaltyTransaction::loadById($this->current_org_id, $this->transaction_id);
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
			$columns["loyalty_log_id"]= "'".$this->transaction_id."'";

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

		$columns["store_id"]= $this->current_user_id;
		$columns["updated_on"]= "'".Util::getMysqlDateTime("now")."'";

		// new user
		if(!$this->lineitem_id)
		{
			$this->logger->debug("User id is not set, so its going to be an insert query");

			$sql = "INSERT INTO user_management.loyalty_bill_lineitems ";
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
			self::saveToCache(self::CACHE_KEY_PREFIX.$org_id."##".$this->lineitem_id, "");
			$obj = self::loadById($this->current_org_id,$this->lineitem_id);
			self::saveToCache(self::CACHE_KEY_PREFIX.$org_id."##".$obj->getLineitemId(), $obj->toString());
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
		$ret = true;

		// validate amount
		$ret &= $this->validateTransactionAmount();
		
		// validate gross amount
		$ret &= $this->validateGrossAmount();
		
		// validate discount
		$ret &= $this->validateDiscount();
		
		// validate qty
		$ret &= $this->validateQty();
		
		// rate * qty = value check
		$ret &= $this->validateRateQtyGross();
		
		// rate * qty = value check
		$ret &= $this->validateGrossDiscountAmount();
		
		return $this->validationErrorArr ? false : true;
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

		if(!$obj = self::loadFromCache($org_id, self::CACHE_KEY_PREFIX.$org_id."##".$lineitem_id))
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
			$obj = self::fromString($org_id, $obj);
			$logger->debug("Loading from the Cache was successful. returning");
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
		lbl.id as lineitem_id,
		lbl.user_id as user_id,
		lbl.loyalty_log_id as transaction_id,
		lbl.serial as serial_number,
		lbl.item_code as item_code,
		lbl.description as description,
		lbl.rate as rate,
		lbl.qty as qty,
		lbl.value as gross_amount,
		lbl.discount_value as discount,
		lbl.amount as transaction_amount,
		lbl.store_id as store_id,
		lbl.inventory_item_id as item_id,
		lbl.outlier_status as outlier_status,
		lbl.updated_on as last_updated_on
		FROM user_management.loyalty_bill_lineitems as lbl
		WHERE lbl.org_id = $org_id ";

		if($filters->lineitem_id)
			$sql .= " AND lbl.id in (".$filters->lineitem_id.")";
		if($filters->user_id)
			$sql .= " AND lbl.user_id= ".$filters->user_id;
		if($filters->transaction_id)
			$sql .= " AND lbl.loyalty_log_id = ".$filters->transaction_id." ";
		if($filters->item_code)
			$sql .= " AND lbl.item_code = '".$filters->item_code."' ";
		if($filters->item_id)
			$sql .= " AND lbl.inventory_item_id = ".$filters->item_id." ";
		if($filters->outlier_status)
			$sql .= " AND lbl.outlier_status= '".$filters->outlier_status."' ";

		$sql .= " ORDER BY lbl.loyalty_log_id desc, lbl.id asc ";

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
				$obj = self::fromArray($org_id, $row);
				self::saveToCache(self::CACHE_KEY_PREFIX.$org_id."##".$obj->getLineitemId(), $obj->toString());
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
					$this->transaction = new LoyaltyTransaction($this->current_org_id, $this->user_id, $this->logger);
					$this->logger->debug("Loaded the member");
				}
				break;

			case 'configmgr':
				if(!$this->configMgr instanceof ConfigManager)
				{
					$this->configMgr = new ConfigManager($this->current_org_id);
					$this->logger->debug("Loaded config manager");
				}
				break;

			default:
				$this->logger->debug("Requested member could not be resolved trying parent");
				parent::initiateDependentObject($memberName);
		}
	}

}
