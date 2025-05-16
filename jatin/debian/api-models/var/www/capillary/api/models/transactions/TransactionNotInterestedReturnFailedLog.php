<?php
include_once 'models/BaseModel.php';


/**
 * @author rahul
 *
 * The loyalty transaction specific operations will be triggered from here
 * The data will be flowing to user_management.loyalty_log
 * The transaction will be linked with a loyalty customer
 *
 */

class TransactionNotInterestedReturnFailedLog extends BaseApiModel{

	protected $notes;
	protected $currentUser;
	protected $transactionId;

	protected $logger;
	protected $db;
	

	CONST CACHE_KEY_PREFIX = 'NOT_INTERESTED_TXN#';
	
	public function __construct($currentOrg, $transactionId = null){
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		$this->currentOrg = $currentOrg;
		$this->current_org_id = $currentOrg->org_id;

		$this -> transactionId = $transactionId;
		
		$this->logger = $logger;

		// db connection
		$this->db = new Dbase( 'users' );
		
	}
	
	public static function setIterableMembers(){
	
		$local_members = array(
				"notes"
		);

		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
	}

	public function getNotes(){
		return $this->notes;
	}

	public function setNotes($notes){
		$this->notes = $notes;
	}
	
	/*
	 *  The function saves the data in to DB or any other data source 
	 */
	public function save($transaction){
/*
  `org_id` bigint(20) NOT NULL, done
  `bill_number` varchar(255) DEFAULT NULL,
  `amount` float NOT NULL, done
  `entered_by` int(11) NOT NULL,
  `returned_on` timestamp NOT NULL,
  `loyalty_not_interested_bill_id` bigint(20) DEFAULT NULL, //returns **************
   *`parent_loyalty_not_interested_bill_id` bigint(20) DEFAULT NULL, //for mixed transactions - during which return has happened
`previous_till_id` int(11) NOT NULL,
`previous_bill_date` timestamp NOT NULL,
   `type` enum('FULL','LINE_ITEM','AMOUNT') NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
`outlier_status` enum('NORMAL','INTERNAL','FRAUD','OUTLIER','TEST','FAILED','OTHER','RETRO') NOT NULL DEFAULT 'NORMAL',
  `added_on` timestamp NOT NULL,
  `auto_update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

   [bill_client_id] => 1121
    [type] => not_interested_return
    [number] => payment1
    [purchase_time] => 2013-10-07
    [return_type] => AMOUNT
    [amount] => 10000
    [billing_time] => 2013-10-08 00:00:00
    [gross_amount] => 1000
    [discount] => 0
    [transaction_number] => payment1


*/

  	$insertColms = array(
  							"bill_number" => "transaction_number", 
  							"amount" => "amount", 
  							"returned_on" => "", 
  							"loyalty_not_interested_bill_id" => "loyalty_not_interested_bill_id", 
  						 	"parent_loyalty_not_interested_bill_id" => "old_bill_id",
  						 	"previous_till_id" => "previous_till_id", 
  						 	"type" => "return_type", 
  						 	"reason" => "notes", 
  						 	"outlier_status" => "outlier_status" 
  						);


  		foreach ($insertColms as $key => $value) {

  			if(isset($transaction[$value]))
				$columns[$key]= "'". $transaction[$value]."'";
  			
  		}

  		if(count($transaction["line_items"]["line_item"]) > 0 ){
  			$this->logger->debug("Line items " . json_encode($transaction["line_items"]["line_item"]) );
  			$columns["lineitem_info"] = "'". json_encode($transaction["line_items"]["line_item"])  ."'";
  		}

  		

  		if ($transaction["purchase_time"])
  			$columns["previous_bill_date"]= "'". date("Y-m-d H:i:s", strtotime($transaction["purchase_time"])) ."'";


		if(!$this->transaction_id){
			$columns["org_id"]= $this->current_org_id;
			
			if ($this -> current_user_id)
				$columns["entered_by"]= $this->current_user_id;
			
			$columns["added_on"]= "'".date("Y-m-d H:i:s")."'";
			$columns["date"]= "'".date("Y-m-d H:i:s")."'";

			$this->logger->debug("User id is not set, so its going to be an insert query");
		
			$sql = "INSERT INTO user_management.not_interested_return_bills_failed_log ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db->insert($sql);
				
			$this->logger->debug("Return of saving the new user is $newId");
				
			if($newId > 0)
				$this->transaction_id = $newId;
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
		
		
		$filters = new TransactionLoadFilters();
		$filters->transaction_id =  $transaction_id;
		try{
			$array = self::loadAll($org_id, $filters, 1);
		}catch(Exception $e){
			$logger->debug("Id based search has failed");
		}
		
		if($array[0])
			return $array[0];
		
		throw new ApiTransactionException(ApiTransactionException::FILTER_NON_EXISTING_TRANSACTION_ID_PASSED);
				
		

		
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
		nbl.returned_on as transaction_date,
		nbl.notes as notes,
		nbl.amount as transaction_amount, 
		'' as gross_amount,
		'' as discount,
		nbl.outlier_status as outlier_status, 
		nbl.entered_by as store_id 
		FROM user_management.not_interested_return_bills as nbl
		WHERE nbl.org_id = $org_id";
		
		$insertColms = array(
							"id" => "transaction_id",
  							"bill_number" => "transaction_number", 
  							"amount" => "amount", 
  							"returned_on" => "", 
  							"loyalty_not_interested_bill_id" => "loyalty_not_interested_bill_id", 
  						 	"parent_loyalty_not_interested_bill_id" => "parent_loyalty_not_interested_bill_id",
  						 	"previous_till_id" => "previous_till_id", 
  						 	"previous_bill_date" => "previous_bill_date", 
  						 	"type" => "type", 
  						 	"notes" => "notes", 
  						 	"outlier_status" => "outlier_status" 
  						);


  		foreach ($insertColms as $key => $value) {

  			if(isset($transaction[$value]))
				$sql .= " AND nbl.$key= '".$transaction[$value]."'";
  			
  		}

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
		if($array){
			$ret = array();
			foreach($array as $row){
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
