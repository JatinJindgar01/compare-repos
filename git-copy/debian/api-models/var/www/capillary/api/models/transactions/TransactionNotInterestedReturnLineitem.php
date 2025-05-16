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

class TransactionNotInterestedReturnLineitem extends BaseApiModel{

	protected $notes;
	protected $currentUser;
	protected $currentOrg;
	protected $transactionId;

	protected $logger;
	protected $db;
	private $itemCodeToIdMapping;
	

	CONST CACHE_KEY_PREFIX = 'NOT_INTERESTED_TXN#';
	
	public function __construct($currentOrg, $transactionId = null, $itemCodeToIdMapping){
		
		global $logger, $currentuser;
	 	$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		$this->currentOrg = $currentOrg;
		$this->current_org_id = $currentOrg->org_id;

		$this -> transactionId = $transactionId;
		
		$this->logger = $logger;

		$this->itemCodeToIdMapping = $itemCodeToIdMapping;

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
	public function save($lineitem){
		$this->logger->debug("lineitem for Transaction :: $this->transactionId && for org :: $this->current_org_id" . json_encode($lineitem) );
				
		if(!$this ->transactionId){
			$this->logger->debug("Transaction id is not setted in constructure.");

			throw new ApiTransactionException(ApiTransactionException::SAVING_DATA_FAILED);	
		}
/*
 `org_id` bigint(20) NOT NULL,
  `not_interested_return_bill_id` bigint(20) NOT NULL,
  `serial` int(11) NOT NULL,
  `item_code` varchar(20)  NOT NULL,
  `rate` double NOT NULL,
  `qty` double NOT NULL,
  `value` double NOT NULL,
   `discount_value` double,
   `amount` double NOT NULL,
  `lineitem_id` int(11) DEFAULT NULL, 
`added_on` datetime NOT NULL,

   [serial] => 1
   [item_code] => item-001
   [qty] => 50
   [rate] => 10
   [discount] => 11
   [value] => 1000
   [amount] => 1000
   [lineitem_id] => 12


*/

  		$insertColms = array(
  							"serial" => "serial", 
  							"item_code" => "item_code", 
  							"rate" => "rate", 
  							"qty" => "qty", 
  						 	"value" => "value",
  						 	"discount_value" => "discount", 
  						 	"amount" => "amount",
  						 	"description" => "description"
  						);


  		foreach ($insertColms as $key => $value) {

  			if(isset($lineitem[$value]))
				$columns[$key]= "'". $lineitem[$value]."'";
  			
  		}

		//if(!isset($lineitem["id"]))
		if(1)
		{
			$itemCode = strtoupper($columns["item_code"]);
			if($this->itemCodeToIdMapping){
				$columns["lineitem_id"] = $this->itemCodeToIdMapping[str_replace("'", "", $itemCode )]['id'];
				$this->logger->debug("lineitem_id :: from itemCodeToIdMapping : " . "for ". strtoupper($columns["item_code"]) 
						." this obj: ".json_encode($this->itemCodeToIdMapping["ITEM-001"])
						." full obj".json_encode($this->itemCodeToIdMapping));
				
				if(!isset($columns["amount"])){
					$columns["amount"] = $this->itemCodeToIdMapping[str_replace("'", "", $itemCode )]['amount'];
					$lineitem["amount"] = $this->itemCodeToIdMapping[str_replace("'", "", $itemCode )]['amount'];
				}

				if(!isset($columns["rate"]))
					$columns["rate"] = $this->itemCodeToIdMapping[str_replace("'", "", $itemCode )]['rate'];


				if(!isset($columns["discount_value"]))
					$columns["discount_value"] = $this->itemCodeToIdMapping[str_replace("'", "", $itemCode )]['discount_value'];

				if(!isset($columns["qty"]))
					$columns["qty"] = "'". (intval($lineitem['amount'])+intval($columns['discount_value']) )/ intval($columns['rate']) ."'" ;

				if(!isset($columns["value"]))
					$columns["value"] = "'". intval((intval($lineitem['amount'])+intval($columns['discount_value']) )) . "'";

				$this->logger->debug("computed values of lineitem: " . json_encode($columns));


			}
			$columns["org_id"]= $this->current_org_id;
			
			$columns["added_on"]= "'".date("Y-m-d H:i:s")."'";
			$columns["not_interested_return_bill_id"]= $this ->transactionId;

			$this->logger->debug("User id is not set, so its going to be an insert query");
		
			$sql = "INSERT INTO user_management.not_interested_return_bill_lineitems ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db->insert($sql);
				
			$this->logger->debug("Return of saving the new user is $newId");
				
			if($newId > 0)
				$this->lineItemId = $newId;
		}
		else
		{
			$this->logger->debug("User id is set, so its going to be an update query");
			$sql = "UPDATE user_management.not_interested_return_bill_lineitems SET ";
		
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
				
			$sql=substr($sql,0,-2);
				
			$sql .= "WHERE id = $this->lineItemId";
			$newId = $this->db->update($sql);
		}
		
		if($newId)
			return $newId;
		
		throw new ApiTransactionException(ApiTransactionException::SAVING_DATA_FAILED);
		
		
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
	public static function loadById($org_id, $transaction_id = null){
		//Not need now

		
	}
	
	/*
	 * Load all the data into object based on the filters being passed.
	 * It should optionally decide whether entire dependency tree is required or not
	 * 
	 * TODO: add more filters
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0){
		//Not need now
		
	}
	
	/*
	 * Loads all the lineitems of the transaction to object.
	* The setter method has to be used prior to set the transaction id
	*/
	public function loadLineItems(){
		//Not need now
	}
	
	/*
	 * set the array from an array received from the select query
	*/
	public static function fromArray($org_id, $array){
		//Not need now
	}

	/**
	 * initiate the respective class on demand
	 * @param $memberName - the object need to be initialized
	 */
	protected function initiateDependentObject($memberName){
		//Not need now
	
	}
}
