<?php
include_once 'models/BaseModel.php';
include_once 'common.php';
include_once "controller/ApiLoyalty.php";

/**
 * @author rahul
 *
 * The not_intersted_return transaction specific operations will be triggered from here
 * The data will be flowing to user_management.this->parentBill
 * The transaction will be linked with a not_intersted_return customer
 *
 */

class TransactionNotInterestedReturn extends BaseApiModel{

	protected $notes;
	protected $currentUser;
	protected $currentOrg;
	protected $transactionId;

	private $parentLoyaltyNotInterestedBillId;
	private $parentLoyaltyNotInterestedBillTillId;
	private $parentBill;

	protected $logger;
	protected $db;
	private $itemCodeToIdMapping;
	private $acceptNonExistingBills;
	private $newReturnLineitems;
	private $lineItemAmount;
	private $deliveryStatus;

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

	public function getItemCodeToIdMapping(){

		return $this->itemCodeToIdMapping;
	}

	public function getNewReturnLineitems(){
		return $this->newReturnLineitems;
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

	public function getDeliveryStatus() {
		return $this -> deliveryStatus;
	}

	public function setDeliveryStatus($deliveryStatus) {
		$this -> deliveryStatus = $deliveryStatus;
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
	    $update = false;

    	try{
    		if( !$this->validate($transaction) )
    			throw new ApiTransactionException(ApiTransactionException::SAVING_DATA_FAILED);
    	} catch (Exception $e){

    		$this->logger->debug("Exception:  ". $e->getMessage());
    		
			if($e->getMessage() != "ERR_UNABLE_TO_MAP" || (!isset($this->acceptNonExistingBills) || $this->acceptNonExistingBills != 1  ) ){
    			$this->logger->debug("Inserting transation into failed logs");
    			
    			require_once "models/transactions/TransactionNotInterestedReturnFailedLog.php";			
				$transactionNotInterestedReturnFailedLog = new TransactionNotInterestedReturnFailedLog($this->currentOrg);
				
				if($this->parentLoyaltyNotInterestedBillId)
  					$transaction["loyalty_not_interested_bill_id"] = $this->parentLoyaltyNotInterestedBillId ;

  				if($this->parentLoyaltyNotInterestedBillTillId)
  					$transaction["previous_till_id"] = $this->parentLoyaltyNotInterestedBillTillId;
 
				$this->id  = $transactionNotInterestedReturnFailedLog -> save ($transaction);
				
				if($e->getMessage() != "ERR_UNABLE_TO_MAP")
    				throw $e;
    			throw new Exception('ERR_LOYALTY_BILL_NUMBER_NOT_EXIST');

			}
    		else{
    			
    			Util::addApiWarning(" The return transaction does not exists! ");

    		}
    		
    	}
    	if(strtoupper($transaction["return_type"]) == strtoupper(TYPE_RETURN_BILL_FULL) ){

    		$this->logger->debug("Transation is of type FULL, updating amount from". $transaction["amount"] . " to " . $this->parentBill['bill_amount']);

    		$transaction["amount"] = $this->parentBill['bill_amount'];

    	}
    	//lineItemAmount
    	if(strtoupper($transaction["return_type"]) == strtoupper(TYPE_RETURN_BILL_LINE_ITEM) ){

    		$this->logger->debug("Transation is of type lineitem, updating amount from". $transaction["amount"] . " to " . $this->lineItemAmount);

    		$transaction["amount"] = $this->lineItemAmount;

    	}

  		$insertColms = array(
  							"bill_number" => "transaction_number", 
  							"amount" => "amount", 
  							"returned_on" => "billing_time", 
  							"loyalty_not_interested_bill_id" => "loyalty_not_interested_bill_id", 
  						 	"parent_loyalty_not_interested_bill_id" => "old_bill_id",
  						 	"previous_till_id" => "purchase_till_id", 
  						 	"type" => "return_type", 
  						 	"notes" => "notes", 
  						 	"outlier_status" => "outlier_status"
  						);


  		foreach ($insertColms as $key => $value) {

  			if(isset($transaction[$value]))
				$columns[$key]= "'". $transaction[$value]."'";
  			
  		}

  		if($this->parentLoyaltyNotInterestedBillId)
  			$columns["loyalty_not_interested_bill_id"]= "'". $this->parentLoyaltyNotInterestedBillId ."'";

  		if($this->parentLoyaltyNotInterestedBillTillId)
  			$columns["previous_till_id"]= "'". $this->parentLoyaltyNotInterestedBillTillId ."'";

  		if ($transaction["purchase_time"])
  			$columns["previous_bill_date"]= "'". date("Y-m-d H:i:s", strtotime($transaction["purchase_time"])) ."'";

		if(!$this->transaction_id){
			$columns["org_id"]= $this->current_org_id;
			
			if ($this -> current_user_id)
				$columns["entered_by"]= $this->current_user_id;
			
			$columns["added_on"]= "'".date("Y-m-d H:i:s")."'";
			
			if(!isset($columns["returned_on"]))
				$columns["returned_on"]= "'".date("Y-m-d H:i:s")."'";

			$this->logger->debug("User id is not set, so its going to be an insert query");
		
			$sql = "INSERT INTO user_management.not_interested_return_bills ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db->insert($sql);
				
			$this->logger->debug("Return of saving the new user is $newId");
			
			if($newId > 0)
				$this->transaction_id = $newId;
		}
		else
		{
			$update = true;
			$this->logger->debug("User id is set, so its going to be an update query");
			$sql = "UPDATE user_management.not_interested_return_bills SET ";
		
			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
				
			$sql=substr($sql,0,-2);
				
			$sql .= "WHERE id = $this->transaction_id";
			$newId = $this->db->update($sql);
		}
		
		$deliveryStatus = $transaction['delivery_status'];

		if (isset($newId) && $newId > 0) {

			if ($update || ! empty($deliveryStatus)) {
				// Continue to insert into the `transaction_delivery_status` table
				$statusSql = "INSERT INTO `user_management`.`transaction_delivery_status` " . 
									"SET `transaction_id` = " . $newId . ", " . 
											"`transaction_type` = 'NOT_INTERESTED_RETURN', " . 
											"`delivery_status` = '" . $deliveryStatus . "', " . 
											"`updated_by` = " . $this -> current_user_id . " " . 
							 "ON DUPLICATE KEY UPDATE " . 
							 			"`delivery_status` = '" . $deliveryStatus . "', " . 
										"`updated_by` = " . $this -> current_user_id;

				// Using Dbase -> update() instead of insert() to be able to run ON DUPLICATE KEY UPDATE
				$newDeliveryStatusId = $this -> db -> update($statusSql);
				$this -> logger -> debug('Transaction Not-interested: Delivery-status ID: ' . $newDeliveryStatusId);

				// Continue to insert into the `transaction_delivery_status_changelog` table
				$statusLogSql = "INSERT INTO `user_management`.`transaction_delivery_status_changelog` " . 
									"SET `transaction_id` = " . $newId . ", " . 
										"`transaction_type` = 'NOT_INTERESTED_RETURN', " . 
										"`delivery_status` = '" . $deliveryStatus . "', " . 
										"`updated_by` = " . $this -> current_user_id;
				$newDeliveryStatusLogId = $this -> db -> insert($statusLogSql);
				$this -> logger -> debug('Transaction Not-interested: Delivery-status-changelog ID: ' . $newDeliveryStatusId);
			} 

			return $newId;	
		}

		throw new ApiTransactionException(ApiTransactionException::SAVING_DATA_FAILED);
	}
	
	/*
	 * Validate the data before saving to DB/ for insert and update
	 * TODO: add the validators here
	*/
	private function validateType($return_type, $safe_bill_number){

		$cm = new ConfigManager($this->current_org_id);
		
		$allowReturn =  $cm->getKey( "CONF_NON_LOYALTY_IS_RETURN_TRANSACTION_SUPPORTED");
		$this->logger->debug("CONF_NON_LOYALTY_IS_RETURN_TRANSACTION_SUPPORTED : $allowReturn ,for org: $this->current_org_id".strtoupper($return_type) ."===". strtoupper(TYPE_RETURN_BILL_AMOUNT));
		
		$allowReturnLineitem =  $cm->getKey( "CONF_NON_LOYALTY_IS_RETURN_TRANSACTION_LINE_ITEM_SUPPORTED");
		$this->logger->debug("CONF_NON_LOYALTY_IS_RETURN_TRANSACTION_LINE_ITEM_SUPPORTED : $allowReturnLineitem ,for org: $this->current_org_id");

		$allowReturnAmount =  $cm->getKey( "CONF_NON_LOYALTY_IS_RETURN_TRANSACTION_AMOUNT_SUPPORTED");
		$this->logger->debug("CONF_NON_LOYALTY_IS_RETURN_TRANSACTION_AMOUNT_SUPPORTED : $allowReturnAmount ,for org: $this->current_org_id");

		$allowReturnFull =  $cm->getKey( "CONF_NON_LOYALTY_IS_RETURN_TRANSACTION_FULL_SUPPORTED");
		$this->logger->debug("CONF_NON_LOYALTY_IS_RETURN_TRANSACTION_FULL_SUPPORTED : $allowReturnFull ,for org: $this->current_org_id");

		//for non existing bills
		$this->acceptNonExistingBills =  $cm->getKey( "CONF_NON_LOYALTY_ACCEPT_NON_EXISTING_BILL");
		$this->logger->debug("CONF_NON_LOYALTY_ACCEPT_NON_EXISTING_BILL : $acceptNonExistingBills ,for org: $this->current_org_id");
	
		$allowReturnOfNonExistingTxn = 1;


		
		if(!isset($allowReturn) || $allowReturn != 1  ){

			$this->logger->debug("Return transaction is not supported");
			throw new Exception("ERR_RETURN_TRANSACTION_NOT_SUPPORTED");
		}
		
		else if((!isset($allowReturnLineitem) || $allowReturnLineitem != 1) && strtoupper($return_type) == strtoupper(TYPE_RETURN_BILL_LINE_ITEM) ) {

			$this->logger->debug("Return Line item transaction is not supported");
			throw new Exception("ERR_RETURN_LINEITEM_TRANSACTION_NOT_SUPPORTED");
		}

		else if( (!isset($allowReturnAmount) || $allowReturnAmount != 1) && strtoupper($return_type) == strtoupper(TYPE_RETURN_BILL_AMOUNT) ){

			$this->logger->debug("Return amount transaction is not supported");
			throw new Exception("ERR_RETURN_AMOUNT_TRANSACTION_NOT_SUPPORTED");
		}
		
		else if( (!isset($allowReturnFull) || $allowReturnFull != 1) && strtoupper($return_type) == strtoupper(TYPE_RETURN_BILL_FULL) ){
			$this->logger->debug("Return full transaction is not supported");
			throw new Exception("ERR_RETURN_FULL_TRANSACTION_NOT_SUPPORTED");
		}

		$allowWithoutTransactioNumber = $cm->getKey( "CONF_NON_LOYALTY_IS_RETURN_BILL_NUMBER_REQUIRED"); 
		
		if( (!isset($allowWithoutTransactioNumber) || $allowWithoutTransactioNumber != 1) && !$safe_bill_number){

			$this->logger->debug("Return transaction number is empty");
			throw new Exception("ERR_RETURN_TRANSACTION_NUMBER_EMPTY");
		}

		if(!$safe_bill_number){

			$this->logger->error("Bill not found: ERR_UNABLE_TO_MAP");
			throw new Exception('ERR_UNABLE_TO_MAP');
		}

	}

	private function validateParentTransaction($old_bill_number, $purchase_time, $amount, $returned_time, $input_return_type){

		$safe_bill_number = Util::mysqlEscapeString($old_bill_number);
		//$safe_credit_note = Util::mysqlEscapeString($credit_note);
		//$safe_notes = Util::mysqlEscapeString($notes);
		$safe_bill_date = $purchase_time ? Util::getMysqlDateTime($purchase_time) : null;
 
		$org_id = $this->currentOrg->org_id;
		$store_id = $this->currentuser->user_id;		
		

		//Check if that bill number has been already returned when bill_number is not empty
		$returned_type = null;
		$return_bill_count = 0;
		$returned_bills = array();

		if($safe_bill_number){

			$sql = "SELECT * 
						FROM loyalty_not_interested_bills 
						WHERE org_id = '$org_id' 
					AND bill_number = '$safe_bill_number'
					AND outlier_status = 'NORMAL' ";
			
			if($safe_bill_date > 1990){
				$this->logger->debug("Date is also passed to filter");
				$sql .= " AND `billing_time` >= '"
						.date('Y-m-d 00:00:00', strtotime($safe_bill_date)) 
						."' AND `billing_time` <= '"
						.date('Y-m-d 23:59:59', strtotime($safe_bill_date))."'";
			}
			
			$sql .= " ORDER BY billing_time DESC";
			$this->logger->debug("SQL going: -------------" . $sql);	
			$bills = $this->db->query($sql);
			
			// if bills is not found after passing the bill number and bill is mandatory for return
			if( count( $bills) <= 0){
				$this->logger->error("Bill not found: ERR_UNABLE_TO_MAP");
				throw new Exception('ERR_UNABLE_TO_MAP');
			}
			
			if( count( $bills) > 1){
				$this->logger->error("More then one Bills found, so I can not say which bill is right : ERR_LOYALTY_INVALID_BILL_NUMBER");
				throw new Exception('ERR_LOYALTY_BILL_NUMBER_NOT_EXIST');
			}

			//There should be atleast one bill where the amount being returned is less than the bill amount
			$found = false;
			$return_time_invalid = false;
			$this->parentBill = null;

			if($bills)
			{
				foreach($bills as $b){
                                    
                                     $this->logger->debug("ret amount $amount,billAMount ".$b['bill_amount']);
					if(!LoyaltyController::compareWithPrecision($b['bill_amount'],
                                                    $amount)){
                                            

						$return_time_invalid = false;
						$found = true;
						$this -> parentLoyaltyNotInterestedBillId = $b['id'];
						$this -> parentLoyaltyNotInterestedBillTillId = $b['entered_by'];
						$this-> parentBill = $b;
			
						$addbill_time = $b['billing_time'];
						$addbill_timestamp = Util::deserializeFrom8601($addbill_time);
						$returnbill_timestamp = Util::deserializeFrom8601($returned_time);
						if($addbill_timestamp > $returnbill_timestamp)
						{
							$this->logger->debug("Returned time is less than add bill time: Returned_time: $returned_time, AddedTime: $addbill_timestamp");
							//TO-DO
							//$return_time_invalid = true;
							continue;
						}
						
						break;
					}
				}
			
				if($return_time_invalid)
				{
					$this->logger->error("Returned Time is less than bill time: ERR_LOYALTY_INVALID_RETURN_BILL_TIME");
					throw new Exception('ERR_LOYALTY_INVALID_RETURN_BILL_TIME');
				}
			
				//if there is not such bill, reject the bill
				if(!$found && ( strtoupper($input_return_type) != strtoupper(TYPE_RETURN_BILL_LINE_ITEM) ))
				{
					$this->logger->error("Invalid Bill Amount: ERR_LOYALTY_INVALID_BILL_AMOUNT : Found bill amount " . $this->parentBill['bill_amount'] . " < $amount");
					throw new Exception("ERR_LOYALTY_INVALID_BILL_AMOUNT");
				}
			}
			
		}


	}

	private function validateAlreadyReturned($bill_number, $safe_bill_date, $return_type){

			$org_id = $this->currentOrg->org_id;
			$store_id = $this->currentuser->user_id;		
		
				
			//check already return form not n return; parent bill id????

			$sql = "SELECT * FROM `not_interested_return_bills` 
								WHERE org_id = '$org_id' 
									AND `bill_number` = '$bill_number' 
									AND `previous_bill_date` = '$safe_bill_date'";
			
			$returned_bills = $this->db->query($sql);
			
			$return_bill_count = count($returned_bills);
			if($return_bill_count > 0)
			{
				$returned_type = $returned_bills[ $return_bill_count - 1 ]['type'];
				$input_return_type = $return_type;

				//throughing exception when bill with other type of return is present

				if($input_return_type === TYPE_RETURN_BILL_AMOUNT){

					$this->logger->error("Bill is already returned and".
							" trying to return bill with type: $return_type");
					if($returned_type === TYPE_RETURN_BILL_LINE_ITEM)
						throw new Exception("ERR_ALREADY_RETURNED_AND_NEW_TYPE_AMOUNT");
					else if($returned_type === TYPE_RETURN_BILL_FULL)
						throw new Exception("ERR_ALREADY_RETURNED_AND_OLD_TYPE_FULL");
				}

				else if($input_return_type === TYPE_RETURN_BILL_FULL){
					
					$this->logger->error("Bill is already returned and".
							" trying to return bill with type: $return_type");
					throw new Exception("ERR_ALREADY_RETURNED_AND_NEW_TYPE_FULL");
				}

				else if($input_return_type === TYPE_RETURN_BILL_LINE_ITEM){

					$this->logger->error("Bill is already returned and".
							" trying to return bill with type: $return_type");
					if($returned_type === TYPE_RETURN_BILL_AMOUNT)
						throw new Exception("ERR_ALREADY_RETURNED_AND_NEW_TYPE_LINEITEM_OLD_AMOUNT");
				}
				
				if($returned_type === TYPE_RETURN_BILL_FULL){
					$this->logger->error("Bill is already returned  with type: $returned_type");
					throw new Exception("ERR_ALREADY_RETURNED_AND_OLD_TYPE_FULL");
				}

				$total_returned_amount = 0;
				foreach($returned_bills as $return_bill ){

					$total_returned_amount += $return_bill['amount'];
				}

				$this->logger->debug("total returned amount: $total_returned_amount");
				return $total_returned_amount;
			}

			return 0;
		

	}

	public function validateAmount( $transaction, $total_returned_amount, $return_type){

		
		if(LoyaltyController::compareWithPrecision($this->parentBill['bill_amount'],
                                                    $total_returned_amount + $transaction['amount'])){
                    
                    $this->logger->debug(" This is not item return: ". strtoupper($return_type) ." != ". strtoupper(TYPE_RETURN_BILL_LINE_ITEM));
			
			$this->logger->debug("ERR_ALREADY_RETURNED_AND_MORE_AMOUNT : Total amount ".($total_returned_amount + $transaction['amount'])." > transaction amoount" . $this->parentBill['bill_amount']);
			if(strtoupper($return_type)  !=  strtoupper(TYPE_RETURN_BILL_LINE_ITEM))
				throw new Exception("ERR_ALREADY_RETURNED_AND_MORE_AMOUNT");
			throw new Exception(ERR_RETURNED_ITEM_QTY_INVALID);
		}

		//thing done here are 
		//1. get items which are bought
		//2. get items which are already returned
		//3. if items are not then those should be fetched from the line loyality_not_interested_lineitem
		//result invalid quinity and invalid amount
		//changing the bill type if need, that should not be done.

		$new_return_items = array();
		$new_return_lineitem_pe_data = array();
		$pe_lineitems = array();

		$org_id = $this->currentOrg->org_id;
		$store_id = $this->currentuser->user_id;

		$returned_items = $transaction["line_items"]["line_item"];

		$notInterestedBillId = $this -> parentLoyaltyNotInterestedBillId;
		
		// to set the return line items
		if((count($returned_items > 0) || $return_type === TYPE_RETURN_BILL_FULL) && $notInterestedBillId){
			
			//Populating lineitems from db.
			$this->logger->debug("fetching lineitems from db the notInterestedBillId: $notInterestedBillId");
			
			$sql = " SELECT 
					id, upper(item_code) as item_code, 
					qty, rate, discount_value, value, amount, serial, description 
					FROM loyalty_not_interested_bill_lineitems 
						WHERE org_id = '$org_id' 
						AND not_interested_bill_id = '$notInterestedBillId' 
						";
			
			$merged_regular_items = $this->db->query_hash($sql, "item_code", array("id", "item_code", "qty", "rate", "discount_value", "value", "amount", "serial", "description"));
				
				//can be used in item insert to get lineitem_id
			$this->itemCodeToIdMapping = $merged_regular_items;

			//if return type is full, it should fetch all lineitems from db, 
			//and insert into return return_bill_lineitems
			if($return_type === TYPE_RETURN_BILL_FULL ){

				$this->logger->debug("ReturnType is: $return_type, fetching lineitems from db");
				$bill_lineitems = $this->db->query_hash($sql, "id", array("id", "item_code", "qty", "rate", "discount_value", "value", "amount", "serial"));
				$new_return_items = $bill_lineitems;				
				$new_return_lineitem_pe_data = array_values($bill_lineitems);
			}
			else if($return_type === TYPE_RETURN_BILL_AMOUNT ){

				$this->logger->debug("ReturnType is: $return_type, Ignoring passed lineitems, and calling return bill amount");
				$new_return_items = array();
				$new_return_lineitem_pe_data = array();
			}
			else{

				$bill_lineitems =$merged_regular_items;
				//merging returned items.
				$merged_returned_items = array();
				foreach ($returned_items as $key=>$item){

					$temp_item_code = strtoupper($item['item_code']);
					if(!$item['discount_value']){

						// any of the fields on date os passed
						if( $item['amount'] || $item['value'] || $item['qty'] || $item['rate'])
							$item['discount_value'] = $returned_items[$key]['discount_value'] = 0;

						// default the value as 0 if all values are zero/notset
						else
							$item['discount_value'] = $returned_items[$key]['discount_value'] = $merged_regular_items[$temp_item_code]["discount_value"];
					}
					if(!$item['qty'])
						$item['qty'] = $returned_items[$key]['qty'] = $merged_regular_items[$temp_item_code]['qty'];
					if(!$item['rate'])
						$item['rate'] = $returned_items[$key]['rate'] = $merged_regular_items[$temp_item_code]['rate'];
					if(!$item['value'])
						$item['value'] = $returned_items[$key]['value'] = $item['qty'] * $item['rate'];
					if(!$item['amount'])
						$item['amount'] = $returned_items[$key]['amount'] = $item['value'] - $item['discount_value'];
						
					if(isset($merged_returned_items[$temp_item_code]))
					{
						$this->logger->debug("item_code: ".$item['item_code']." repeated, ".
								"merging (".$merged_returned_items[$temp_item_code]['qty']."+".$item['qty'].")");
						$merged_returned_items[$temp_item_code]['qty'] += $item['qty'];
						$merged_returned_items[$temp_item_code]['amount'] += $item['amount'];
						 
					}
					else
					{
						$merged_returned_items[$temp_item_code] = $item;
					}
				}
				
				//now checking for lineitems which are already returned

				$return_bill_lineitem_sql = "SELECT id, upper(item_code) as item_code, 
							SUM(qty) AS qty, SUM(amount) as amount
							FROM  not_interested_return_bill_lineitems 
							WHERE org_id = '$org_id'
							AND not_interested_return_bill_id = '$notInterestedBillId'
							GROUP BY lineitem_id";
					
				$returned_lbl_rows = $this->db->query_hash($return_bill_lineitem_sql, "lineitem_id", array("lineitem_id", "item_code", "qty", "amount"));
				#print str_replace("\t"," ", $return_bill_lineitem_sql);
				#print_r($returned_lbl_rows);
				$this->logger->debug("ReturnType is: $return_type, fetching lineitems from db and rewriting rate and id");
				
				#$bill_lineitems = $this->db->query($sql);

				$merged_returned_items = array();
				foreach ($returned_lbl_rows as $item){

					$temp_item_code = strtoupper($item['item_code']);
					if(count($returned_lbl_rows) > 0 && isset($returned_lbl_rows[$item['id']])){
						
						$this->logger->debug("deducting qty from returned qty");
						$item['qty'] = $item['qty'] - $returned_lbl_rows[$item['id']]['qty']; 
					}
					if(isset($merged_returned_items[$temp_item_code])){

						$this->logger->debug("item_code: ".$item['item_code']." repeated, ".
								"merging (".$merged_returned_items[$temp_item_code]['qty']."+".$item['qty'].")");
						$merged_returned_items[$temp_item_code]['qty'] += $item['qty'];
						$merged_returned_items[$temp_item_code]['amount'] += $item['amount'];
						
					}
					else
					{
						$merged_returned_items[$temp_item_code] = $item;
					}
				}

 				foreach($returned_items as $item){

 					$temp_item_code = strtoupper($item["item_code"]);
 					if(isset($merged_returned_items[$temp_item_code]))
 					{
 						$merged_returned_items[$temp_item_code]['qty'] += $item['qty'];
 						$merged_returned_items[$temp_item_code]['amount'] += $item['amount'];
 					}
 					else
 						$merged_returned_items[$temp_item_code] = $item;
					
 				}
 					
 				$bill_lineitems = $merged_regular_items;
 				$this->logger->debug("List of merged_regular_items " . json_encode($merged_regular_items));
								
				// @@@ commented by cj
				#$returned_items = $merged_returned_items;
				$new_derived_amount = 0;

				foreach ($returned_items as $temp_item_code1 => $item)
				{
					// if some data is not passed; get that from exist
					$temp_item_code = $item['item_code'];
					$temp_item_code_in_upper = strtoupper($temp_item_code);
					$temp_lineitem = $bill_lineitems[$temp_item_code_in_upper];
					if(!isset($returned_items[$temp_item_code1]))
						$returned_items[$temp_item_code1] = array();
					$returned_items[$temp_item_code1]["id"] = $temp_lineitem["id"];
					$returned_items[$temp_item_code1]["qty"] = $item["qty"] ? $item["qty"] : $temp_lineitem["qty"];
					$returned_items[$temp_item_code1]["rate"] = $item["rate"] ? $item["rate"] : $temp_lineitem["rate"];

					if(!$item["value"]){
						if($returned_items[$temp_item_code1]["rate"] && $returned_items[$temp_item_code1]["qty"])
							$returned_items[$temp_item_code1]["value"] = $returned_items[$temp_item_code1]["rate"] * $returned_items[$temp_item_code1]["qty"];
						else
							$returned_items[$temp_item_code1]["value"] = $item["value"] ? $item["value"] : $temp_lineitem["value"];
					}

					if(!$item["amount"])
					{
						if($returned_items[$temp_item_code1]["value"])
							$returned_items[$temp_item_code1]["amount"] = $returned_items[$temp_item_code1]["value"] - $item["discount_value"];
						else
							$returned_items[$temp_item_code1]["amount"] = $item["amount"] ? $item["amount"] : $temp_lineitem["amount"];
					}

					//print "\n".$merged_regular_items[$temp_item_code_in_upper]['amount'] ."x". $merged_returned_items[$temp_item_code_in_upper]['amount']."\n";
					if($merged_regular_items[$temp_item_code_in_upper]['qty'] < $merged_returned_items[$temp_item_code_in_upper]['qty'])
					{
						$this->logger->error("returned qty is more than purchased qty for item_code: $temp_item_code
								( Pruchased Qty: ". $temp_lineitem['qty'] .", Returned Qty: ".$item['qty']." )");
								//ERR_RETURNED_ITEM_QTY_INVALID is defined in Errors.php
						throw new Exception(ERR_RETURNED_ITEM_QTY_INVALID);
					}
					
					if(LoyaltyController::compareWithPrecision($merged_regular_items[$temp_item_code_in_upper]['amount'],$merged_returned_items[$temp_item_code_in_upper]['amount']))
					{
						$this->logger->error("returned qty is more than purchased qty for item_code: $temp_item_code
								( Pruchased Amunt: ". $merged_regular_items[$temp_item_code_in_upper]['amount'] .", Returned Qty: ".$merged_returned_items[$temp_item_code_in_upper]['amount']." )");
										//ERR_RETURNED_ITEM_QTY_INVALID is defined in Errors.php
						throw new Exception(ERR_RETURNED_ITEM_AMOUNT_INVALID);
					}
					
					array_push($new_return_lineitem_pe_data,
						array(
						"id" => $temp_lineitem['id'],
						"qty" => $returned_items[$temp_item_code1]["qty"],
						"rate" => $returned_items[$temp_item_code1]["rate"],
					));

						
					
				}
				$new_return_items = $returned_items;
				
				// consider the amount as the line item sum
				if($returned_items)
				{
					$this->logger->debug("Recalculating the return amount");
					$amount = 0 ; 
					foreach($returned_items as $key=>$item)
						$amount += $item["amount"];
				}
				$this->lineItemAmount = $amount;
			}
		}
		
		// if nor bill is available, use the return bill as such
		if(!$notInterestedBillId)
		{
			$new_return_items = $returned_items;
			if($return_type == TYPE_RETURN_BILL_AMOUNT)
				$new_return_items = null;
		}
		
		// will be used when line items are not given
		$this->logger->debug("Lineitems that are going to be returned : ".
				json_encode($new_return_lineitem_pe_data));
		$this->newReturnLineitems = $new_return_lineitem_pe_data;
		
		// TODO: this seems to be useless type change; need to remove  - cj
		if( $return_type !== TYPE_RETURN_BILL_AMOUNT && $notInterestedBillId
				&& count($new_return_items) === 0 ) //$new_return_lineitem_pe_data
		{
			$this->logger->error("no Lineitem is passed for return type: $return_type,
					changing return type from $return_type to ".TYPE_RETURN_BILL_AMOUNT);
			$return_type = TYPE_RETURN_BILL_AMOUNT;
		}

		if($input_return_type == TYPE_RETURN_BILL_FULL && $this->parentBill){

			$this->logger->debug("return_type is $return_type,".
					" overriding passed amount: $amount".
					" with new derived amount: $this->parentBill[bill_amount]");
			$amount = $this->parentBill["bill_amount"] + 0;
		}

		else if($return_type !== TYPE_RETURN_BILL_LINE_ITEM){
			$this->logger->debug("return_type is $return_type,".
					" overriding passed amount: $amount".
					" with new derived amount: $new_derived_amount");
			$amount = $amount ? $amount : intval($new_derived_amount);
		}
		
		if($return_type === TYPE_RETURN_BILL_AMOUNT && $this->parentBill){
			if($returned_type != null && $returned_type !== TYPE_RETURN_BILL_AMOUNT)
			{
				$this->logger->error("bill is already returned with type $returned_type, 
									you can't return amount");
				throw new Exception("ERR_ALREADY_RETURNED_AND_NEW_TYPE_AMOUNT");
			}
			
			if( ($total_returned_amount + $amount) > $this->parentBill['bill_amount'])
			{
				$this->logger->error("Amount: $amount can not be returned,"
						."$total_returned_amount is already returned from total Amount: "
						.$this->parentBill['bill_amount']);
				throw new Exception("ERR_ALREADY_RETURNED_AND_MORE_AMOUNT");
			}
		}

	}

	public function validateTransactionBoundry($transaction){

		/*
				    <bill_client_id>1121</bill_client_id> 
				    
				    <number>payment10</number> string
				    <notes>asfdasM</notes> string
				    <purchase_time>2013-10-07</purchase_time> date
				    
				    <amount>10000</amount> NZ and +ve
				    <billing_time>2013-10-08</billing_time> date
				    <gross_amount>1000</gross_amount> NZ and +ve
				    <discount>0</discount> +ve
				    <outlier_status>NORMAL</outlier_status> ??? 
				    
				    <line_items>
					    <line_item>
					        <serial>1</serial> int
					    	<item_code>item-001</item_code> should be there
					        <qty>50</qty> NZ and +ve
					        <rate>10</rate> +ve
					        <discount>11</discount> +ve
					        <value>1000</value> NZ and +ve
					        <amount>1000</amount> NZ and +ve
					    </line_item>
				    	<line_item>
				    		<item_code>item-001</item_code>
				             <qty>50</qty>
				            <rate>10</rate>
				             </line_item>
				    </line_items>

				</transaction>


				return_type
				ERR_LOYALTY_BILL_ADDITION_FAILED
		*/

		$allowedReturnTypes = array("AMOUNT","LINE_ITEM","FULL");

		if(!in_array(strtoupper($transaction["return_type"]), $allowedReturnTypes)){
			$this->logger->error("Invalid transaction Return type: ". $transaction["return_type"] );
			throw new Exception("ERR_LOYALTY_BILL_ADDITION_FAILED");

		}
		if (isset($transaction["amount"]) && $transaction["amount"] <= 0){

					$this->logger->error("Invalid transaction attribute : amount");
			throw new Exception("ERR_LOYALTY_INVALID_BILL_AMOUNT");
		}

		if (!isset($transaction["purchase_time"])){

					$this->logger->error("purchased date not set");
			throw new Exception("ERR_LOYALTY_INVALID_RETURN_BILL_TIME");
		}

		/*if (isset($transaction["gross_amount"]) && $transaction["gross_amount"] <= 0){

					$this->logger->error("Invalid attribute : gross_amount");

			throw new Exception("ERR_LOYALTY_INVALID_BILL_AMOUNT");
		}*/
		if (isset($transaction["discount"]) && $transaction["discount"] < 0){

					$this->logger->error("Invalid attribute : discount");
			throw new Exception("ERR_LOYALTY_INVALID_BILL_AMOUNT");
		}

		$addbill_timestamp = Util::deserializeFrom8601($transaction["purchase_time"]);
		
		if(!isset($transaction["billing_time"]))
				$returned_time = date("Y-m-d H:i:s");
		else
			$returned_time = $transaction["billing_time"];

		$this->logger->debug("billing_time :: ". $returned_time);
			

		$returnbill_timestamp = Util::deserializeFrom8601($returned_time);
		if($addbill_timestamp > $returnbill_timestamp)
		{
			$this->logger->error("Returned Time is less than bill time: ERR_LOYALTY_INVALID_RETURN_BILL_TIME $addbill_timestamp !== $returnbill_timestamp");
			throw new Exception('ERR_LOYALTY_INVALID_RETURN_BILL_TIME');
		}

		
		$this->logger->debug("lineitem $key : " . json_encode($transaction["line_items"]) );


		if (isset($transaction["line_items"]) ){
			foreach ($transaction["line_items"]["line_item"] as $key => $value) {

				$this->logger->debug("lineitem $key : " . json_encode($value) );

				if (!isset($value["item_code"])){

					$this->logger->error("Invalid attribute : item_code");
					throw new Exception("ERR_LOYALTY_INVALID_BILL_AMOUNT");
				}

				if (isset($value["amount"]) && $value["amount"] <= 0){

					$this->logger->error("Invalid lineitem attribute : amount = " . $value["amount"]);
					throw new Exception('ERR_LOYALTY_LINEITEM_AMOUNT_NEGATIVE');
				}

				if (isset($value["qty"]) && $value["qty"] <= 0){

					$this->logger->error("Invalid attribute : qty");
					throw new Exception('ERR_LOYALTY_LINEITEM_QTY_NEGATIVE');
				}

				if (isset($value["value"]) && $value["value"] <= 0){

					$this->logger->error("Invalid attribute : value");
					throw new Exception('ERR_LOYALTY_LINEITEM_VALUE_NEGATIVE');
				}

				if (isset($value["discount"]) && $value["discount"] < 0){

					$this->logger->error("Invalid attribute : discount");
					throw new Exception('ERR_LOYALTY_LINEITEM_DISCOUNT_NEGATIVE');
				}

				if (isset($value["rate"]) && $value["rate"] < 0){

					$this->logger->error("Invalid attribute : rate");
					throw new Exception('ERR_LOYALTY_LINEITEM_RATE_NEGATIVE');
				}

				if (isset($value["value"]) && isset($value["qty"]) && isset($value["rate"]) && $value["rate"] < 0){

					if($value["value"] != $value["qty"] * $value["rate"]){
						$this->logger->error("Invalid attribute : rate");
						throw new Exception("ERR_LOYALTY_INVALID_BILL_AMOUNT");
					}
				}

				if (isset($value["amount"]) && isset($value["discount"]) && isset($value["value"]) && isset($value["qty"]) && isset($value["rate"]) && $value["rate"] < 0){

					if($value["amount"] != ($value["qty"] * $value["rate"]) - $value["discount"] ){
						$this->logger->error("Invalid attribute : rate");
						throw new Exception("ERR_LOYALTY_INVALID_BILL_AMOUNT");
					}
				}
				
			}
		}
			
			

	}

	public function validate($transaction){

		global $currentorg;
		$input_return_type = $return_type = $transaction["return_type"];

		$this->logger->debug("validating field boundry");
		
		//validating field boundry
		$this -> validateTransactionBoundry($transaction);

		$this->logger->debug("cheching from org configuration does this type of transaction type is alowed or not");
		
		//cheching from org configuration does this type of transaction type is alowed or not
		$this -> validateType( $return_type, $transaction["number"]);

		$this->logger->debug("Validating Transation parent");

		$this -> validateParentTransaction(
											$transaction["number"], 
											$transaction["purchase_time"], 
											$transaction["amount"],
											$transaction["billing_time"],
											$return_type
											);		
		
		//$safe_bill_number, $safe_bill_date, $return_type
		$totalReturnedAmount = 
			$this -> validateAlreadyReturned($transaction["number"], $transaction["purchase_time"], $return_type);		
		
		$this->logger->debug("Check if there is a bill with amount more than the amount being returned");
		//Check if there is a bill with amount more than the amount being returned
		$this -> validateAmount( $transaction, $totalReturnedAmount, $return_type);

		return true;
		
	}
	public function getByLoyaltyNotInterestedBillId($loyaltyNotInterestedBillId){
		$orgId = $this->current_org_id;

		$returnBills = "SELECT id, bill_number, amount, type, notes, returned_on FROM `not_interested_return_bills` 
								WHERE org_id = '$orgId' 
									AND `loyalty_not_interested_bill_id` = '$loyaltyNotInterestedBillId' 
									";
					
		$returnedBillsrows = $this->db->query_hash($returnBills, "id", array("bill_number", "amount", "type", "notes", "returned_on"));
		$this->logger->debug("bills By $loyaltyNotInterestedBillId " . json_encode($returnedBillsrows));

		foreach ($returnedBillsrows as $key => $value) {
			if( strtoupper($value['type']) != strtoupper(TYPE_RETURN_BILL_AMOUNT) ){
				// get all line items
				$sql = " SELECT 
					id, upper(item_code) as item_code, 
					qty, rate, discount_value, value, amount, serial 
					FROM user_management.not_interested_return_bill_lineitems
						WHERE org_id = '$orgId' 
						AND not_interested_return_bill_id = '$key' 
						";
			
				$items = $this->db->query_hash($sql, "id", array("item_code", "qty", "rate", "amount", "discount_value"));
				$value["line_items"]["line_item"] = $items;

			}
		}

		$this->logger->debug("After line items addition bills By $loyaltyNotInterestedBillId " . json_encode($returnedBillsrows));

		return $returnedBillsrows;


	}

	public function setTransactionAsRetro($returnTransactionId){
		$sql = "UPDATE user_management.not_interested_return_bills SET outlier_status = 'RETRO' WHERE id = $returnTransactionId";
		
		return $this->db->update($sql);

	}
	
	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	 */
	public static function loadById($org_id, $transaction_id = null){
		
		//not needed 

		
	}
	
	/*
	 * Load all the data into object based on the filters being passed.
	 * It should optionally decide whether entire dependency tree is required or not
	 * 
	 * TODO: add more filters
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0){
		//not need rigth now
	}
	
	/*
	 * Loads all the lineitems of the transaction to object.
	* The setter method has to be used prior to set the transaction id
	*/
	public function loadLineItems()	{ //not need rigth now 
	}
	
	/*
	 * set the array from an array received from the select query
	*/
	public static function fromArray($org_id, $array)
	{
		//not need rigth now
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
