<?php

require_once 'apiModel/class.ApiRequestModel.php';
class ApiTransactionRequestModelExtension extends ApiRequestModel{
	
	protected $new_type;
	protected $old_type;
	protected $base_type;
	protected $transaction_id;
	protected $loyalty_log_id;
	protected $reason;
	protected $comments;
	protected $retro_request_id;
	
	protected $possibleStatuses = array('PENDING', 'APPROVED', 'REJECTED');
	protected $oldTypeEnum = array('NOT_INTERESTED');
	protected $newTypeEnum = array('REGULAR');
	protected $baseTypeEnum = array('RETRO');

	public function __construct(){
		parent::__construct();
		$this->type='TRANSACTION_UPDATE';		
		$this -> db_slave = new Dbase("member_care", true);
	}
	

	public function getHash()
	{
		return array_merge(array(
				'base_type'=>$this->base_type,
				'old_type'=>$this->old_type,
				'new_type'=>$this->new_type,
				'transaction_id'=>$this->transaction_id,
				'loyalty_log_id' => '' . $this -> loyalty_log_id, 
				'reason' => $this -> reason, 
				'comments' => $this -> comments
		),parent::getHash());
		
	}
	

	function setHash($hash)
	{
		
		foreach(array('user_id','base_type','old_type','new_type', 'transaction_id', "id", 'reason', 'comments', 'is_one_step_change') as $hash_key) {
			$$hash_key=$hash[$hash_key];
		}
		
		$this->user_id=$user_id;
		$this->base_type = $base_type;
		$this->old_type=$old_type;
		$this->new_type=$new_type;
		$this->transaction_id=$transaction_id;
		$this -> status = 'PENDING';
		$this -> reason = $reason;
		$this -> comments = $comments;
		$this->is_one_step_change = $is_one_step_change;
		
		if(isset($hash['requested_on']) && !empty($hash['requested_on']))
			$this->created_on=DateUtil::deserializeFrom8601($hash['requested_on'])?DateUtil::deserializeFrom8601($hash['requested_on']):null;

		$this->validate();
		
	}
	
	public function add(){
		
		// Call the parent insert to push into `requests` table
		parent::insert();
		
		// Insert into the `retro_requests` table
		$retroSql = "INSERT INTO `member_care`.`retro_requests` " . 
					"SET `ref_id` = " . $this -> request_id . ", " . 
						"`org_id` = " . $this -> org_id . ", " . 
						"`user_id` = " . $this -> user_id . ", " . 
						"`transaction_id` = " . $this -> transaction_id . ", " . 
						"`base_type` = '" . $this -> base_type . "', " . 
						"`reason` = '" . $this -> reason . "', " . 
						"`comments` = '" . $this -> comments . "'";
		$this -> retro_request_id = $this -> db -> insert($retroSql);

		if (! isset($this -> retro_request_id)) {
			$this -> logger -> error("Insertion of retro-request failed for request_id " . $this -> request_id . "!");
			throw new Exception('SQL Failed');
		} else {
			// Continue to insert into the `retro_status_changelog` table
			$statusLogSql = "INSERT INTO `member_care`.`retro_status_changelog` " . 
								"SET `retro_request_id` = " . $this -> retro_request_id . ", " . 
									"`status` = '" . $this -> status . "', " . 
									"`reason` = '" . $this -> reason . "', " . 
									"`comments` = '" . $this -> comments . "', " . 
									"`updated_by` = " . $this -> currentuser -> user_id;
			$newStatusLogId = $this -> db -> insert($statusLogSql);

			if (! $newStatusLogId) {
				$this -> logger -> error("Insertion of retro-status-log failed for retro_request_id " . $this -> retro_request_id ."!");
				throw new Exception('SQL Failed');
			}
		}

		if($this->is_one_step_change){
			$this->logger->debug("Auto approval is ON, triggering that");
			$this->approve($this->request_id, $this->getHash()); 
		}

		return $this -> retro_request_id;
	}
	
	//TODO
	protected function validate(){
		
		$cm = new ConfigManager();
		$isRetroTxnEnabled = $cm -> getKey("CONF_RETRO_TRANSACTION_ENABLE");
		if (! $isRetroTxnEnabled)
			throw new Exception('ERR_RETRO_NOT_ENABLED');

		$this -> isBaseTypeValid($this -> base_type);

		if (! in_array(strtoupper($this -> old_type), $this -> oldTypeEnum)) {
			$this -> logger -> info('Invalid old_type: ' . $this -> old_type);
			throw new Exception("ERR_RETRO_INVALID_OLD_TXN_TYPE");
		}

		if (! in_array(strtoupper($this -> new_type), $this -> newTypeEnum)) {
			$this -> logger -> info('Invalid new_type: ' . $this -> new_type);
			throw new Exception("ERR_RETRO_INVALID_NEW_TXN_TYPE");
		}

		$requested_on  = date("Y-m-d H:i:s", strtotime($this->created_on ? $this->created_on : "now"));

		// already queued
		$sql = "SELECT r.status FROM member_care.retro_requests rr
				INNER JOIN member_care.requests r on r.org_id = rr.org_id
				WHERE rr.transaction_id  =".$this->transaction_id 
				." AND rr.org_id = ".$this->org_id 
				//." AND r.status != 'REJECTED' "
				;
		// already queued
		$alloc_request=$this->db->query_firstrow($sql);
		if($alloc_request){
			$this->logger->info("The request is already made once");
			throw new Exception ("ERR_RETRO_DUPLICATE_REQUEST");
		}
		
		// invalid transaction id
		$usm_db = new Dbase("users");
		$sql = "SELECT id, billing_time, bill_amount as amount
		FROM loyalty_not_interested_bills  as nib 
		WHERE id = ".$this->transaction_id 
		." AND org_id = ". $this->org_id
		." AND outlier_status != 'RETRO' ";
		$bill_details = $usm_db->query_firstrow($sql);
		
		if(!$bill_details){
			$this->logger->info("The requested bill id is not found ". $this->transaction_id);
			throw new Exception ("ERR_RETRO_INVALID_TRANSACTION");
		}
				
		// not within the date range
		$cm = new ConfigManager();
		$max_window_bill_creation = $cm->getKey("CONF_CLIENT_RETRO_MAX_ALLOWED_AGE_DAYS") + 0 ;
		$max_window_after_registration = $cm->getKey("CONF_CLIENT_RETRO_DELAY_SINCE_REGISTRATION_HOURS") + 0 ;
		$auto_approval_max_amount = $cm->getKey("CONF_AUTO_APPROVE_RETRO_TRANSACTION_MAX_AMOUNT") + 0 ;
		
		if($this->is_one_step_change ){
			if ($bill_details["amount"] > $auto_approval_max_amount) {
				$this->logger->info("The bill amt is greater than". $auto_approval_max_amount);
				throw new Exception ("ERR_RETRO_AUTO_APPROVE_MAX_AMOUNT_EXCEED");
			} 
		}
		
		if(date('Y-m-d H:i', strtotime($bill_details["billing_time"])) < date('Y-m-d H:i', strtotime("$requested_on - $max_window_bill_creation days"))){
			$this->logger->info("The requested bill too old ". $this->transaction_id);
			throw new Exception ("ERR_RETRO_OLD_TRANSACTION");
		}
		
		// registration 
		$sql = "SELECT joined FROM loyalty where publisher_id = $this->org_id and user_id = $this->user_id";
		$user_details = $usm_db->query_firstrow($sql);
		
		if(!$user_details){
			$this->logger->info("The user id doesnot belong to the org" );
			throw new Exception ("ERR_RETRO_INVALID_CUSTOMER");
		}
		if(strtotime($bill_details["billing_time"])  > strtotime($user_details["joined"] . " + $max_window_after_registration hours")){
			$this->logger->info("Billed after registration" );
			throw new Exception ("ERR_RETRO_BEFORE_REGISTRATION");
			
		}
		
		
		return true;
	}
	
	function approve($id, $payload)
	{
		$request_id=$id;
		$this -> reason = $payload["reason"];
		$this -> comments = $payload["updated_comments"];
		$baseType = strtoupper($payload["base_type"]);

		$this->logger->info("approving the retro request $id");

		$this -> isBaseTypeValid($baseType);

		if(!$this->isExists($id,'PENDING'))
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		$this->load($id);		
		
		if($this->created_by == $this->currentuser->user_id && !$this->is_one_step_change){
			throw new Exception('ERR_REQUESTER_CANNOT_APPROVE');
		}
		
		else if($this->is_one_step_change){
			
			$cm = new ConfigManager();
			$auto_approval_max_amount = $cm->getKey("CONF_AUTO_APPROVE_RETRO_TRANSACTION_MAX_AMOUNT") + 0 ;
			
			$usm_db = new Dbase("users");
			$sql = "SELECT id, billing_time, bill_amount as amount
				FROM loyalty_not_interested_bills  as nib 
				WHERE id = ".$this->transaction_id 
			." AND org_id = ". $this->org_id
			." AND outlier_status != 'RETRO' ";
			$bill_details = $usm_db->query_firstrow($sql);
			
			if($bill_details["billing_time"]> $auto_approval_max_amount){
				$this->logger->info("The bill amt is greater than". $auto_approval_max_amount);
				throw new Exception ("ERR_RETRO_AUTO_APPROVE_MAX_AMOUNT_EXCEED");
				
			} 
			
		}
		
		if ($this->base_type != $baseType)
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		return $this -> changeStatus($id, 'APPROVED', $this -> reason, $this -> comments);

	}
	
	protected function isBaseTypeValid($base_type){
		
		if (! in_array (strtoupper($base_type), $this -> baseTypeEnum)) 
			throw new Exception('ERR_INVALID_REQUEST_BASE_TYPE');
	}
	
		
	private function changeStatus($id, $status)
	{
		$this -> logger -> debug("Changing Status; Payload: " . implode(" , ",func_get_args()));

		// Load record from `requests` table
		$this -> logger -> debug("Load record with $id from `requests` table");
		$this -> load($id);
		


			$status = strtoupper($status);
			
			//If in approved state, update transaction from 'loyalty_not_interested_bills' to `loyalty` table
			if ($status == 'APPROVED') {
				$this -> logger -> debug("Update txn $id from 'loyalty_not_interested_bills' to `loyalty` table");
				$res = $this -> doRetroTransaction($id);
				$this -> loyalty_log_id = $res['id'];
			}

			//Update record's status, updated_on, updated_by in `request` table
			$this -> logger -> debug("Update record with $id from `request` table with status " . $this -> status);
			$this -> status = $status;
			$this -> update();

		$this -> beginTransaction();

		try {
			//After updating in `requests` table, update in `retro_requests` table
			$this -> logger -> debug("After updating in `requests` table, update in `retro_requests` table");
			$retroSql = $retroUpdate = null;
			if ($status == 'APPROVED') {
				if (isset($this -> loyalty_log_id)) {
					$retroSql = "UPDATE `member_care`.`retro_requests` " .
									"SET `loyalty_log_id` = " . $this -> loyalty_log_id . ", " . 
										"`reason` = '" . $this -> reason . "', " . 
										"`comments` = '" . $this -> comments . "' " . 
								"WHERE `org_id` = " . $this -> org_id . " AND " . 
										"`ref_id` = " . $id;
					$retroUpdate = $this -> db -> update($retroSql);
				}
//			$this->sendConfirmation();
			} else if ($status == 'REJECTED') {
				$retroSql = "UPDATE `member_care`.`retro_requests` " .
								"SET `reason` = '" . $this -> reason . "', " . 
									"`comments` = '" . $this -> comments . "' " . 
							'WHERE `org_id` = ' . $this -> org_id . 
									' AND `ref_id` = ' . $id;
				$retroUpdate = $this -> db -> update($retroSql);
			}
			if (! $retroUpdate) {
				$this -> logger -> error("Updation of retro-request with ref_id $id failed!");
				throw new Exception('SQL Failed');
			}

			//Also, insert the status change into the `retro_status_changelog` table
			$this -> logger -> debug("Also, insert the status change into the `retro_status_changelog` table");
			$statusLogSql = "INSERT INTO `member_care`.`retro_status_changelog` " . 
								"SET `retro_request_id` = " . $this -> retro_request_id . ", " . 
									"`status` = '" . $this -> status . "', " . 
									"`reason` = '" . $this -> reason . "', " . 
									"`comments` = '" . $this -> comments . "', " . 
									"`updated_by` = " . $this -> currentuser -> user_id;
			$newStatusLogId = $this -> db -> insert($statusLogSql);
			if (! $newStatusLogId) {
				$this -> logger -> error("Insertion of retro-status-log failed for retro_request with ref_id $id!");
				throw new Exception('SQL Failed');
			}
			
			$this -> commitTransaction();
				
		} catch(Exception $e) {
			$this->logger->error("Exception occurred while updating db : $e");
			$this->logger->info("rolling back the transaction");
			$this->rollbackTransaction();
			if(!in_array($e->getMessage(),array_keys(ErrorCodes::$request)))
				throw new Exception('ERR_REQUEST_APPROVE_FAILED');
			else 
				throw new Exception($e->getMessage());
		}
		
		return true;
	}
	
	private function doRetroTransaction($id){

		$this->load($id);
		if($this->status != "PENDING"){
			throw new Exception("ERR_REQUEST_APPROVE_FAILED");
		}
		
		
		if($this->base_type == 'RETRO'){
			$user = UserProfile::getById($this->user_id);
			include_once 'apiController/ApiTransactionController.php';
			$txnController= new ApiTransactionController();
			$res = $txnController->markNotInterestedRegular($this->transaction_id, $user->getHash(), "retro transaction", array(), $this->created_on);
			return $res;
			
		}else{
			$this->logger->debug("Invalid request passed");
			throw new Exception("ERR_REQUEST_APPROVE_FAILED");
		}
		
	}
	
	
	public function load($id){

		parent::load($id);
		
		$sql = "SELECT rr.* FROM member_care.retro_requests rr
				WHERE rr.ref_id =".$id 
				." AND rr.org_id = ".$this->org_id 
				//." AND r.status != 'REJECTED' "
				;
		// already queued
		$alloc_request=$this->db->query_firstrow($sql);
		
		$this->new_type = "REGULAR" ;
		$this->old_type = "NOT_INTERESTED";
		$this->base_type = "RETRO";
		$this->transaction_id = $alloc_request["transaction_id"];
		$this -> retro_request_id = $alloc_request['id'];
		
		return $alloc_request;
		
	}
	
	
		
	function getRequests($status,$type,$base_type,$user_id,$start_date,$end_date,$start_id,$end_id,$limit,$start_limit=null)
	{
		
		$count_sql_prefix="SELECT count(*)
				FROM member_care.retro_requests rr
				JOIN member_care.requests r ON r.org_id=$this->org_id AND r.id=rr.ref_id
				JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
				WHERE ";
		
		$sql_prefix="SELECT r.type AS type, r.id AS request_id,r.status,r.params,
				rr.transaction_id, rr.base_type, rr.loyalty_log_id, rr.reason, rr.comments as updated_comments, 
				u.firstname,u.lastname,l.external_id,u.mobile,u.email,u.id AS user_id, f.status AS fraud_status, r.created_on AS added_on,
				l.joined AS registered_on, 
				o.code AS added_by_code, o.name AS added_by_name,r.updated_on,
				r.created_by AS added_by_till_code, r.created_by AS added_by_till_name,
				r.updated_by AS updated_by_code, r.updated_by AS updated_by_name,
				nib.bill_number, nib.bill_amount, nib.billing_time, nib.entered_by as billing_till_id, oe2.code as bill_till_code, oe2.name as bill_till_name				
				FROM member_care.retro_requests rr
				INNER JOIN member_care.requests r ON r.org_id=$this->org_id AND r.id=rr.ref_id
				INNER JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
				INNER JOIN user_management.loyalty l ON l.publisher_id=$this->org_id AND l.user_id=r.user_id
				INNER JOIN user_management.loyalty_not_interested_bills nib ON nib.org_id=$this->org_id AND nib.id=rr.transaction_id
				INNER JOIN masters.org_entities oe2 ON oe2.id=nib.entered_by and oe2.org_id = $this->org_id
				LEFT OUTER JOIN user_management.fraud_users f ON f.org_id=$this->org_id AND f.user_id=r.user_id
				LEFT OUTER JOIN masters.org_entity_relations er ON er.child_entity_id=r.created_by AND er.parent_entity_type='STORE'
				LEFT OUTER JOIN masters.org_entities o ON o.id=er.parent_entity_id
				WHERE 
				";
				//LEFT OUTER JOIN masters.org_entities e ON e.id=r.created_by
				//LEFT OUTER JOIN masters.org_entities uo ON uo.id=r.updated_by
		
		$conds=array("r.org_id=$this->org_id");

		if($status)
			$conds[]="r.status='$status'";
		if($type)
			$conds[]="r.type='$type'";
		if($base_type)
			$conds[]="rr.base_type='$base_type'";
		if($user_id)
			$conds[]="r.user_id='$user_id'";
		if($start_date && strtotime($start_date))
			$conds[]="r.created_on>='".date("Y-m-d H:i:s",strtotime($start_date))."'";
		if($end_date && strtotime($end_date))
			$conds[]="r.created_on<='".date("Y-m-d 23:59:59",strtotime($end_date))."'";
	
		if($start_id)
			$conds[]="r.id>='".addslashes($start_id)."'";
	
		if($end_id)
				$conds[]="r.id<='".addslashes($end_id)."'";
				
		if(!$limit)
			$limit=10;
		
		$cond_sql=$sql.implode(" AND ",$conds);
		
		$count_sql="$count_sql_prefix $cond_sql";
		$sql="$sql_prefix $cond_sql";
		
		if($start_limit)
			$limit_str="$start_limit, $limit";
		else
			$limit_str="$limit";
		
		$order_sql=" ORDER BY r.id DESC LIMIT $limit_str";
		
		$sql="$sql_prefix $cond_sql $order_sql";
		$count=$this->db->query_scalar($count_sql);
		$data=$this->db->query($sql);
		
		$key_map = array( "added_by_till_code" => "code", "added_by_till_name" => "name" );
		//$key_map = array( "org" => "{{joiner_concat(name,id)}}" );
		$org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
		$data = $org_entity->prepareReport( $data, $key_map );
		
		$key_map = array( "updated_by_code" => "code", "updated_by_name" => "name" );
		//$key_map = array( "org" => "{{joiner_concat(name,id)}}" );
		$org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
		$data = $org_entity->prepareReport( $data, $key_map );
		
		return array(
					'data'=>$data,
					'count'=>$count
		);
		
	}


	function reject($id, $payload)
	{
		$this -> logger -> info("Rejecting TRANSACTION_UPDATE request $id; Payload: " . print_r($payload, true));

		$baseType = strtoupper($payload['base_type']);
		$this -> isBaseTypeValid($baseType);
		$this -> reason = $payload['reason'];
		$this -> comments = $payload['updated_comments'];

		if (empty($this -> reason))
			throw new Exception('ERR_REQUEST_REJECT_REASON_EMPTY');
		if (empty($this -> comments))
			throw new Exception('ERR_REQUEST_REJECT_COMMENTS_EMPTY');
		
		if(!$this->isExists($id,'PENDING'))
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		$this -> load($id);
		if ($this -> base_type != $baseType)
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		return $this -> changeStatus($id, 'REJECTED', $this -> reason, $this -> comments);
	}

	function getRequestLogs($baseType, $startDate, $endDate, $status, $addedBy, $updatedBy, 
		$requestId, $userId, $isOneStepChange, $startId, $endId, $limit) {
	
		$sql = $filtersSql = '';
		$this -> logger -> debug("Function payload => baseType:$baseType, startDate: $startDate, " . 
			"endDate: $endDate, status: $status, addedBy: $addedBy, updatedBy: $updatedBy, requestId: $requestId, " . 
			"userId: $userId, isOneStepChange: $isOneStepChange, startId: $startId, endId: $endId, limit: $limit");

		$this -> isBaseTypeValid($baseType);
	
		if (! empty($updatedBy)) {
			$updatedBy = addslashes($updatedBy);
			$data = ShardedDbase::queryAllShards('masters', 
						"SELECT `id` FROM `masters`.`org_entities` 
						 WHERE (`id` = '$updatedBy' OR `code` = '$updatedBy') 
							AND (`type` = 'ADMIN_USER' OR `org_id` = " . $this -> org_id . ")", true);
			$updatedBy = isset($data[0], $data[0]['id']) ? $data[0]['id'] : NULL;
		}
	
		if($addedBy) {
			$addedBy = addslashes($addedBy);
			$data = ShardedDbase::queryAllShards('masters', 
						"SELECT `id` FROM `masters`.`org_entities` 
						 WHERE (`id` = '$addedBy' OR `code` = '$addedBy') 
						 	AND (`type` = 'ADMIN_USER' OR `org_id` = " . $this -> org_id . ")", true);
			$addedBy = isset($data[0], $data[0]['id']) ? $data[0]['id'] : NULL;
		}
	
		$filters = array();
	
		$filters[] = "rr.base_type = '$baseType'";
		$filters[] = "r.created_on >= '" . date("Y-m-d H:i:s", strtotime($startDate)) . "'";
		$filters[] = "r.created_on <= '" . date("Y-m-d 23:59:59", strtotime($endDate)) . "'";
	
		if ($status) {
			$requestedStatuses = array();
			foreach (explode(",", $status) as $st) {
				if (in_array(strtoupper($st), $this -> possibleStatuses))
					$requestedStatuses[] = $st;
			}

			if (! empty($requestedStatuses))
				$filters[] = "r.status IN ('" . implode("','", $requestedStatuses) . "')";
		} 

		if ($addedBy)
			$filters[] = "r.created_by = '$addedBy'";
	
		if ($updatedBy)
			$filters[] = "r.updated_by = '$updatedBy'";
	
		if ($userId)
			$filters[] = "r.user_id = '$userId'";
		
		if ($isOneStepChange) {
			if (strtolower($isOneStepChange) == "true")
				$filters[] = "r.is_one_step_change = 1";
			else
				$filters[] = "r.is_one_step_change = 0";
		}
			
		if (! isset($limit))
			$limit = 10;
	
		if (isset($requestId))
			$filters[] = "r.id = '" . addslashes($requestId) . "'";
	
		if (isset($startId))
			$filters[] = "r.id >= '" . addslashes($startId) . "'";
	
		if (isset($endId))
			$filters[] = "r.id <= '" . addslashes($endId) . "'";
	
		$filtersSql = implode (" AND ", $filters);
		$orgId = $this -> org_id;
		$sql = 
		"SELECT u.firstname, u.lastname, u.mobile, u.email, u.id AS user_id, f.status AS fraud_status, 
				l.external_id, l.joined AS registered_on, 
				lnib.bill_number,   
				rr.transaction_id AS old_value, rr.loyalty_log_id AS new_value, 
				rr.reason, rr.comments AS updated_comments,
				rr.base_type AS base_type, r.type AS type, r.id AS request_id, r.status, r.params, 
				r.created_on AS added_on, oe.code AS added_by_code, oe.name AS added_by_name, 
				r.created_by AS added_by_till_code, r.created_by AS added_by_till_name, r.is_one_step_change, 
				r.updated_on, r.updated_by AS updated_by_oe_code, r.updated_by AS updated_by_oe_name,
				r.updated_by AS updated_by_au_code, r.updated_by AS updated_by_au_name, 
				r.updated_by AS updated_by_au_mobile, r.updated_by AS updated_by_au_email, 
				al.source_ip AS update_ip_addr
		FROM member_care.retro_requests rr 
			INNER JOIN member_care.requests r ON r.org_id = $orgId AND r.id = rr.ref_id 
			INNER JOIN user_management.users u ON u.org_id = $orgId AND u.id = r.user_id 
			LEFT OUTER JOIN user_management.loyalty l ON l.publisher_id = $orgId AND l.user_id = r.user_id 
			LEFT OUTER JOIN user_management.loyalty_not_interested_bills lnib ON lnib.org_id = $orgId AND lnib.id = rr.transaction_id 
			LEFT OUTER JOIN user_management.fraud_users f ON f.org_id = $orgId AND f.user_id = r.user_id 
			LEFT OUTER JOIN masters.admin_users au ON au.id = r.updated_by 
			LEFT OUTER JOIN masters.org_entity_relations oer ON oer.child_entity_id = r.created_by 
							AND oer.parent_entity_type = 'STORE' AND oer.org_id = $orgId 
			LEFT OUTER JOIN masters.org_entities oe ON oe.id = oer.parent_entity_id AND oe.org_id = oer.org_id  
			LEFT OUTER JOIN member_care.audit_logs al ON al.org_id = '$orgId' AND al.created_by = r.updated_by 
							AND al.assoc_id = r.id 
							AND (al.api_method = 'reject' OR al.api_method = 'approve') 
							AND al.api_status = 200 
		WHERE $filtersSql 
		ORDER BY r.id DESC 
		LIMIT $limit";
	
		$requestLogs = $this -> db_slave -> query($sql);
		$count = $this -> db_slave -> getAffectedRows();
	
		include_once 'helper/memory_joiner/impl/MemoryJoinerFactory.php';
		include_once 'helper/memory_joiner/impl/MemoryJoinerType.php';
		
		$keyMap = array("added_by_till_code" => "code", 
						"added_by_till_name" => "name");
		$orgEntity = MemoryJoinerFactory::getJoinerByType(MemoryJoinerType::$ORG_ENTITY);
		$requestLogs = $orgEntity -> prepareReport($requestLogs, $keyMap);
		
		$keyMap = array("updated_by_oe_code" => "code",
						"updated_by_oe_name" => "name");
		$orgEntity = MemoryJoinerFactory::getJoinerByType(MemoryJoinerType::$ORG_ENTITY);
		$requestLogs = $orgEntity -> prepareReport($requestLogs, $keyMap);

		$keyMap = array("updated_by_au_code" => "id", 
						"updated_by_au_name" => "{{joiner_concat(first_name, last_name)}}", 
						"updated_by_au_mobile" => "mobile", 
						"updated_by_au_email" => "email");
		$adminUser = MemoryJoinerFactory::getJoinerByType(MemoryJoinerType::$ADMIN_USER);
		$requestLogs = $adminUser -> prepareReport($requestLogs, $keyMap);

		return array('count' => $count, 'logs' => $requestLogs);
	}
	
	/* other inherited functions
	 *  - insert
	 *  - load
	 *  - update 
	 */
	
}
