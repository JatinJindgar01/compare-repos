<?php

/**
 * Model extension for Change Identifier request model
 * 
 * @author vimal
 */

require_once 'apiModel/class.ApiChangeIdentifierRequestModel.php';
require_once 'model_extension/member_care/class.MemberCareRequestSettingsModelExtension.php';
include_once 'helper/member_care/MemberCareCacheMgr.php';
include_once 'apiHelper/ApiCacheHandler.php';
include_once 'apiHelper/ApiUtil.php';


class ApiChangeIdentifierRequestModelExtension extends ApiChangeIdentifierRequestModel
{
	
	protected $settings;
	protected $db_slave;
	
	protected $client_auto_approve;
	
	function __construct()
	{
		parent::__construct();
		$this->settings=new MemberCareRequestSettingsModelExtension();
		$this->db_slave=new Dbase("member_care",true);
	}
	
	private function changeStatus($id,$status,$updated_comments="")
	{
		
		$this->logger->debug("payload: ".implode(" , ",func_get_args()));
		$this->update($status, $updated_comments);
		return true;
	}
	
	function approve($id,$base_type)
	{
		$request_id=$id;
		
		$base_type=strtoupper($base_type);
		
		$this->logger->info("approving the change identifier request $id");
		
		if(!$this->isExists($id,'PENDING'))
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		$this->load($request_id);
		
		if($this->base_type!=$base_type)
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		if ($this -> created_by == $this -> currentuser -> user_id && !$this -> is_one_step_change)
			throw new Exception('ERR_REQUESTER_CANNOT_APPROVE');

		$this->validate();
		
		$this->beginTransaction();
		
		try{
			
			$this->changeIdentifier();
			
			$status=$this->changeStatus($id, 'APPROVED');

			//post processsing
			$this -> postAdd();
			$this->postApprove();
			
			$this->sendConfirmation();
			
			$this->commitTransaction();
			
		}catch(Exception $e)
		{
			$this->logger->error("Exception occurred while updating db : $e");
			$this->logger->info("rolling back the transaction");
			$this->rollbackTransaction();
			if(!in_array($e->getMessage(),array_keys(ErrorCodes::$request)))
				throw new Exception('ERR_REQUEST_APPROVE_FAILED');
			else 
				throw new Exception($e->getMessage());
		}
		
		$this->postApproveCommit();
			
		return $status;
		
	}
	
	function reject($id,$updated_comments,$base_type)
	{
		$this->logger->info("rejecting the change identifier request $id");
		
		if(!$this->isExists($id,'PENDING'))
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		$this->load($id);
		if($this->base_type!=$base_type)
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		if(empty($updated_comments))
			throw new Exception('ERR_REQUEST_REJECT_REASON_EMPTY');
		
		try{

			$this->beginTransaction();
			
			$ret=$this->changeStatus($id, 'REJECTED', $updated_comments);
			
			//post processing
			$this->postReject();
			
			$this->commitTransaction();
			
		}catch(Exception $e)
		{
			$this->rollbackTransaction();
			throw new Exception('ERR_REQUEST_REJECT_FAILED');
		}

		$this->postRejectCommit();
	}
	
	
	function getDetails($id)
	{
		$sql="SELECT r.id AS request_id,r.status,r.params,ci.type AS base_type,ci.reason,ci.comments,ci.assoc_id,ci.approved_value,ci.updated_comments,
				u.firstname,u.lastname,l.external_id,u.mobile,u.email,u.id AS user_id,
				ci.updated_comments
				FROM member_care.change_identifier ci
				JOIN member_care.requests r ON r.org_id=$this->org_id AND r.request_id=ci.request_id
				JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
				JOIN user_management.loyalty l ON l.publisher_id=$this->org_id AND l.user_id=r.user_id
				WHERE r.org_id=$this->org_id AND r.id=$id
				";
		return $this->db->query_firstrow($sql);
	}
	
	function getRequests($status,$type,$base_type,$user_id,$start_date,$end_date,$start_id,$end_id,$limit,$start_limit=null)
	{
		
		$count_sql_prefix="SELECT count(*)
				FROM member_care.change_identifier_requests ci
				JOIN member_care.requests r ON r.org_id=$this->org_id AND r.id=ci.request_id
				JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
				WHERE ";
		
		$sql_prefix="SELECT r.type AS type, r.id AS request_id,r.status,r.params,ci.type AS base_type,
				IF(ci.type!='MOBILE_REALLOC',ci.old_value,ci.entity) AS old_value, IF(ci.type!='MOBILE_REALLOC',ci.new_value,'') AS new_value,
				u.firstname,u.lastname,l.external_id,u.mobile,u.email,u.id AS user_id, f.status AS fraud_status, r.created_on AS added_on,
				o.id AS added_by_id, o.code AS added_by_code, o.name AS added_by_name,r.updated_on,
				r.created_by AS added_by_till_id, r.created_by AS added_by_till_code, r.created_by AS added_by_till_name,
				ci.updated_comments,
				r.updated_by AS updated_by_id, r.updated_by AS updated_by_code, r.updated_by AS updated_by_name
				,ci.status AS job_status,
				sr.firstname AS survivor_firstname,sr.lastname AS survivor_lastname,srl.external_id AS survivor_external_id,sr.mobile AS survivor_mobile,sr.email AS survivor_email,sr.id AS survivor_user_id,
				oc.firstname AS oc_firstname,oc.lastname AS oc_lastname,ocl.external_id AS oc_external_id,oc.mobile AS oc_mobile,oc.email AS oc_email,oc.id AS oc_user_id
				FROM member_care.change_identifier_requests ci
				JOIN member_care.requests r ON r.org_id=$this->org_id AND r.id=ci.request_id
				JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
				LEFT OUTER JOIN user_management.loyalty l ON l.publisher_id=$this->org_id AND l.user_id=r.user_id
				LEFT OUTER JOIN user_management.fraud_users f ON f.org_id=$this->org_id AND f.user_id=r.user_id
				LEFT OUTER JOIN masters.org_entity_relations er ON er.child_entity_id=r.created_by AND er.parent_entity_type='STORE'
				LEFT OUTER JOIN masters.org_entities o ON o.id=er.parent_entity_id
				LEFT OUTER JOIN user_management.users sr ON sr.id=ci.new_value AND sr.org_id=$this->org_id
				LEFT OUTER JOIN user_management.loyalty srl ON srl.user_id=sr.id AND srl.publisher_id=$this->org_id
				LEFT OUTER JOIN user_management.users oc ON oc.id=ci.old_value AND oc.org_id=$this->org_id
				LEFT OUTER JOIN user_management.loyalty ocl ON ocl.user_id=oc.id AND ocl.publisher_id=$this->org_id
				WHERE 
				";
				//LEFT OUTER JOIN masters.org_entities e ON e.id=r.created_by
				//LEFT OUTER JOIN masters.org_entities uo ON uo.id=r.updated_by
		
		$conds=array("r.org_id=$this->org_id AND ci.org_id=$this->org_id");

		if($status)
			$conds[]="r.status='$status'";
		if($type)
			$conds[]="r.type='$type'";
		if($base_type)
			$conds[]="ci.type='$base_type'";
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
	
	function getRequestLogs($base_type,$start_date,$end_date,$status,$added_by,$updated_by,$request_id,$approval_type,$pr_customer_id,$sec_customer_id,$is_one_step_change,$start_id,$end_id,$limit)
	{
		
		$base_type=strtoupper($base_type);
		$this->isBaseTypeValid($base_type);
		
		if($updated_by)
		{
			$updated_by=addslashes($updated_by);
			//$updated_by=$this->db_slave->query_scalar("SELECT id FROM masters.org_entities WHERE id='$updated_by' OR code='$updated_by'");
			$data=ShardedDbase::queryAllShards("SELECT id FROM masters.org_entities  
							WHERE (id='$updated_by' OR code='$updated_by') 
							AND (type = 'ADMIN_USER' OR org_id = $this->org_id) ", true);
			$updated_by = isset($data[0], $data[0]['id']) ? $data[0]['id'] : NULL;
		}
		
		if($added_by)
		{
			$added_by=addslashes($added_by);
			//$added_by=$this->db_slave->query_scalar("SELECT id FROM masters.org_entities WHERE id='$added_by' OR code='$added_by'");
			$data=ShardedDbase::queryAllShards("SELECT id FROM masters.org_entities 
							WHERE (id='$added_by' OR code='$added_by')
							AND (type = 'ADMIN_USER' OR org_id = $this->org_id) ", true);
			$added_by = isset($data[0], $data[0]['id']) ? $data[0]['id'] : NULL;
		}
		
		$conds=array();
		
		$conds[]="ci.org_id = $this->org_id AND ci.type='$base_type'";
		$conds[]="r.created_on>='".date("Y-m-d H:i:s",strtotime($start_date))."'";
		$conds[]="r.created_on<='".date("Y-m-d 23:59:59",strtotime($end_date))."'";
		
		if($status)
		{
			$r_status=array();
			foreach(explode(",",$status) as $st)
			{
				if(in_array(strtoupper($st),array('PENDING','APPROVED','REJECTED')))
					$r_status[]=$st;
			}
			if(!empty($r_status))
				$conds[]="r.status IN ('".implode("','",$r_status)."')";
		}
		
		if($added_by)
			$conds[]="r.created_by='$added_by'";
		
		if($updated_by)
			$conds[]="r.updated_by='$updated_by'";
		
		if($pr_customer_id)
			$conds[]="r.user_id='$pr_customer_id'";
		
		if($sec_customer_id)
			$conds[]="ci.sec_user_id='$sec_customer_id'";
		
		if($is_one_step_change)
		{
			if(strtolower($is_one_step_change)=="true")
				$conds[]="r.is_one_step_change=1";
			else
				$conds[]="r.is_one_step_change=0";
		}
		
		if(!$limit)
			$limit=10;
		
		if($approval_type)
		{
			$trans_map=array('CLIENT'=>'QUERY_PARAM','CONFIG'=>'CONFIG','CONFIG_DISABLED'=>'DISABLED','CLIENT_DISABLED'=>'QUERY_DISABLED');
			$ats=array();
			foreach(explode(",",$approval_type) as $at)
			{
				if(in_array(strtoupper($at),array('CLIENT', 'CONFIG', 'CONFIG_DISABLED', 'CLIENT_DISABLED')))
					$ats[]=addslashes($trans_map[$at]);
			}
			$conds[]="ci.auto_approve_type IN ('".implode("','",$ats)."')";
		}
		
		if($request_id)
			$conds[]="r.id='".addslashes($request_id)."'";
		//LEFT OUTER JOIN masters.admin_users au ON au.id=r.updated_by
		$table_sql="
				FROM member_care.change_identifier_requests ci
				JOIN member_care.requests r ON r.org_id=$this->org_id AND r.id=ci.request_id
				JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
				LEFT OUTER JOIN user_management.loyalty l ON l.publisher_id=$this->org_id AND l.user_id=r.user_id
				LEFT OUTER JOIN user_management.fraud_users f ON f.org_id=$this->org_id AND f.user_id=r.user_id
				LEFT OUTER JOIN masters.org_entity_relations er ON er.child_entity_id=r.created_by AND er.parent_entity_type='STORE'
				LEFT OUTER JOIN masters.org_entities o ON o.id=er.parent_entity_id
				LEFT OUTER JOIN user_management.users sr ON sr.id=ci.new_value AND sr.org_id=$this->org_id
				LEFT OUTER JOIN user_management.loyalty srl ON srl.user_id=sr.id AND srl.publisher_id=$this->org_id
				LEFT OUTER JOIN member_care.audit_logs al ON al.org_id='$this->org_id' AND al.created_by=r.updated_by AND al.assoc_id=r.id AND (al.api_method='reject' OR al.api_method='approve') AND al.api_status=200
				WHERE
		";
		//LEFT OUTER JOIN masters.org_entities uo ON uo.id=r.updated_by
		//LEFT OUTER JOIN masters.org_entities e ON e.id=r.created_by
		
		//CONCAT(au.first_name," ",au.last_name) AS updated_by_name, au.mobile AS updated_by_mobile, au.email AS updated_by_email,
		$select_sql='SELECT r.type AS type, r.id AS request_id,r.status,r.params,ci.type AS base_type,
				IF(ci.type!="MOBILE_REALLOC",ci.old_value,ci.entity) AS old_value, IF(ci.type!="MOBILE_REALLOC",ci.new_value,"") AS new_value,
				u.firstname,u.lastname,l.external_id,u.mobile,u.email,u.id AS user_id, f.status AS fraud_status, r.created_on AS added_on,
				o.code AS added_by_code, o.name AS added_by_name,r.updated_on,
				r.created_by AS added_by_till_code, r.created_by AS added_by_till_name,
				ci.updated_comments,
				r.updated_by AS updated_by_oe_name,r.updated_by AS updated_by_oe_code,
				r.updated_by AS updated_by_name, r.updated_by AS updated_by_mobile, r.updated_by AS updated_by_email,
				ci.status AS job_status,
				sr.firstname AS sec_firstname,sr.lastname AS sec_lastname,srl.external_id AS sec_external_id,sr.mobile AS sec_mobile,sr.email AS sec_email,sr.id AS sec_user_id,
				al.source_ip AS update_ip_addr,
				r.is_one_step_change,ci.auto_approve_type AS approval_type
				';
		
		$conds_sql=implode(" AND ",$conds);
		$sql="SELECT count(1) AS l $table_sql $conds_sql";
		
		$count=$this->db_slave->query_scalar($sql);

		
		if($start_id)
			$conds[]="r.id>='".addslashes($start_id)."'";
		
		if($end_id)
			$conds[]="r.id<='".addslashes($end_id)."'";
		
		$this->logger->debug("Conds for the sql: ".print_r($conds,true));
		$conds_sql=implode(" AND ",$conds);
		
		$order_sql=" ORDER BY r.id DESC limit $limit";
		
		$sql="$select_sql $table_sql $conds_sql $order_sql";
		$this->logger->debug("final sql: $sql");
		$logs=$this->db_slave->query($sql);
		
		include_once 'helper/memory_joiner/impl/MemoryJoinerFactory.php';
		include_once 'helper/memory_joiner/impl/MemoryJoinerType.php';
		
		$key_map = array( "added_by_till_code" => "code", "added_by_till_name" => "name" );
		$org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
		$logs = $org_entity->prepareReport( $logs, $key_map );
		
		$key_map = array( "updated_by_oe_name" => "name", "updated_by_oe_code" => "code" );
		$org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
		$logs = $org_entity->prepareReport( $logs, $key_map );
		
		$key_map = array( "updated_by_name" => "{{joiner_concat(first_name,last_name)}}", "updated_by_mobile" => "mobile", "updated_by_email" => "email" );
		$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
		$logs = $admin_user->prepareReport( $logs, $key_map );
		
		return array('count'=>$count,'logs'=>$logs);
		
	}
	
	
	protected function changeIdentifier()
	{
		$this->logger->info("Changing the identifier $this->base_type from $this->old_value to $this->new_value");
	
		switch($this->base_type)
		{
			case "EMAIL":
				$this->changeEmail();
				break;
			case "MOBILE":
				$this->changeMobile();
				break;
			case "EXTERNAL_ID":
				$this->changeExternalId();
				break;
			case "MERGE":
				$this->doMerge();
				break;
		}
	
	}
	
	private function changeEmail()
	{	
                $user_by_email = UserProfile::getByEmail($this->new_value);
                $nullifyExisiting=false;

                if( !empty( $user_by_email ) && $user_by_email->is_merged && $user_by_email->user_id == $this->user_id) {
                    $this->logger->info("Change request is approved to fetch identifier from the merge victim");
                    $nullifyExisiting=true;
                } 
                else if(!empty( $user_by_email ) && $user_by_email->getLoyaltyId() == -1) {
                    $this->logger->info("Change request is raised to fetch identifier from campaign user");
                    $nullifyExisiting=true;
                }

                if($nullifyExisiting) {
                    $this->logger->info("updating victim identifier to null");
                    $sql="UPDATE user_management.users SET email=null WHERE email='$this->new_value'
                                AND org_id=$this->org_id";
                    $ret = $this->db->update($sql);
                    if(!$ret || $this->db->getAffectedRows()!=1)
                        throw new Exception('sql failed or invalid affected rows: '.$this->db->getAffectedRows());
                } 

		$this->logger->info("updating users table");
		$sql="UPDATE user_management.users SET email='$this->new_value'
				WHERE id=$this->user_id AND org_id=$this->org_id";
		$ret=$this->db->update($sql);
		if(!$ret || $this->db->getAffectedRows()!=1)
			throw new Exception('sql failed or invalid affected rows: '.$this->db->getAffectedRows());
		
//		$this->logger->info("updating extd user profile table");
//		$sql="UPDATE user_management.extd_user_profile SET email='$this->new_value'
//				WHERE user_id=$this->user_id AND org_id=$this->org_id";
//		$ret=$this->db->update($sql);
//		if(!$ret || $this->db->getAffectedRows()!=1)
//			throw new Exception('sql failed or invalid affected rows: '.$this->db->getAffectedRows());
		
		$this->logger->info("updating loyalty last updated time");
		$sql="UPDATE user_management.loyalty SET last_updated=NOW()
				WHERE user_id=$this->user_id AND publisher_id=$this->org_id";
		$ret=$this->db->update($sql);
		
	}
	
	private function changeExternalId()
	{
                $user_by_external_id = UserProfile::getByExternalId($this->new_value);
                if( !empty( $user_by_external_id ) && $user_by_external_id->is_merged && $user_by_external_id->user_id == $this->user_id) {
                    $this->logger->info("Change request is approved to fetch identifier from the merge victim");
                    $this->logger->info("updating victim identifier to null");
                    $sql="UPDATE user_management.loyalty SET external_id=null WHERE external_id='$this->new_value'
                                AND publisher_id=$this->org_id";
                    $ret = $this->db->update($sql);
                    if(!$ret || $this->db->getAffectedRows()!=1)
                        throw new Exception('sql failed or invalid affected rows: '.$this->db->getAffectedRows());
                }
	
		$this->logger->info("updating loyalty (external id, last updated time)");
		$sql="UPDATE user_management.loyalty SET external_id='$this->new_value', last_updated=NOW()
				WHERE user_id=$this->user_id AND publisher_id=$this->org_id";
		$ret=$this->db->update($sql);
		if(!$ret || $this->db->getAffectedRows()!=1)
			throw new Exception('sql failed or invalid affected rows: '.$this->db->getAffectedRows());
	
	}
	
	private function changeMobile()
	{
		$user_by_mobile = UserProfile::getByMobile($this->new_value);
                $nullifyExisiting=false;

                if( !empty( $user_by_mobile ) && $user_by_mobile->is_merged && $user_by_mobile->user_id == $this->user_id) {
                    $this->logger->info("Change request is approved to fetch identifier from the merge victim");
                    $nullifyExisiting=true;
                }
                else if(!empty( $user_by_mobile ) && $user_by_mobile->getLoyaltyId() == -1) {
                    $this->logger->info("Change request is raised to fetch identifier from campaign user");
                    $nullifyExisiting=true;
                }

                if($nullifyExisiting) {
                    $this->logger->info("updating victim identifier to null");
                    $sql="UPDATE user_management.users SET mobile=null WHERE mobile='$this->new_value'
                                AND orG_id=$this->org_id";
                    $ret = $this->db->update($sql);
                    if(!$ret || $this->db->getAffectedRows()!=1)
                        throw new Exception('sql failed or invalid affected rows: '.$this->db->getAffectedRows());
                }

		$this->logger->info("updating users table");
		$sql="UPDATE user_management.users SET mobile='$this->new_value'
				WHERE id=$this->user_id AND org_id=$this->org_id";
		$ret=$this->db->update($sql);
		if(!$ret || $this->db->getAffectedRows()!=1)
			throw new Exception('sql failed or invalid affected rows: '.$this->db->getAffectedRows());
		
		$this->logger->info("updating extd user profile table");
//		$sql="UPDATE user_management.extd_user_profile SET mobile='$this->new_value'
//				WHERE user_id=$this->user_id AND org_id=$this->org_id";
//		$ret=$this->db->update($sql);
//		if(!$ret || $this->db->getAffectedRows()!=1)
//			throw new Exception('sql failed or invalid affected rows: '.$this->db->getAffectedRows());
		
		$this->logger->info("updating loyalty last updated time");
		$sql="UPDATE user_management.loyalty SET last_updated=NOW()
				WHERE user_id=$this->user_id AND publisher_id=$this->org_id";
		$ret=$this->db->update($sql);
		
	}
	
	private function doMerge()
	{
		
		try{
			$from_user=UserProfile::getById($this->old_value);
			$from_user->load(true);
			
			$sql="INSERT INTO user_management.merge_customers_log (org_id,from_user_id,to_user_id,from_user_mobile,from_user_external_id,reason,merged_by,merged_on,details)
									VALUES('{$this->org_id}','{$this->old_value}','{$this->new_value}','{$from_user->mobile}','{$from_user->external_id}','merge by request {$this->request_id}','{$this->currentuser->user_id}',NOW(),'')";
			
			$mcl_id=$this->db->insert($sql);
			if(!$mcl_id)
				throw new Exception('sql failed');
			
		}catch(Exception $e)
		{
			throw new Exception('insert into merge_customers_log failed');
		}
		
		$input = array(
				'refId' => $this->request_id,
				'sessionId' => empty($_SERVER['UNIQUE_ID'])?rand(100000,999999999):$_SERVER['UNIQUE_ID'],
				'orgId'=> $this->org_id,
				'fromCustomerId'=> $this->old_value,
				'toCustomerId'=> $this->new_value,
				'requestedOnTimestamp'=> time()*1000,
				'tillId'=>-1,
				'mergedBy'=> $this->currentuser->user_id
		);
		
		try {
			$client = new AsyncClient("customer-merge", "customermerge");
			$payload = json_encode($input, true);
			$this->logger->debug("payload for job customer merge : " . $payload);
			$j = new Job($payload,Priority::URGENT);
			$job_id = $client->submitJob($j);
		} catch (Exception $e) {
			$this->logger->error("Error submitting job to beanstalk for customer merge : " . $e->getMessage());
			throw $e;
		}
		
		$sql="UPDATE member_care.change_identifier_requests SET status='PROCESSING'
				WHERE org_id='$this->org_id' AND request_id='{$this->request_id}'";
		$this->db->update($sql);
		
	}
	
	protected function isAutoApprove()
	{
		
		if(isset($this->client_auto_approve))
		{
			if($this->client_auto_approve)
			{
				$this->logger->info("client_auto_approve payload param in request is set to true");
				$this->setAutoApproveLog('QUERY_PARAM');
				return true;
			}
			else
			{
				$this->logger->info("auto approve disabled by client_auto_approve in payload");
				$this->setAutoApproveLog('QUERY_DISABLED');
				return false;
			}
		}
		
		if(isset($_REQUEST['client_auto_approve']))
		{
			$this->logger->info("client_auto_approve query param is set to {$_REQUEST['client_auto_approve']}");
			if(strtolower($_REQUEST['client_auto_approve'])=="true")
			{
				$this->logger->info("auto approving by client_auto_approve");
				$this->setAutoApproveLog('QUERY_PARAM');
				return true;
			}
			else
			{
				$this->logger->info("auto approve disabled by client_auto_approve");
				$this->setAutoApproveLog('QUERY_DISABLED');
				return false;
			}
		}
		
		$map=array(
				'EMAIL'=>'EMAIL',
				'MOBILE'=>'MOBILE',
				'EXTERNAL_ID'=>'EXTID',
				'ADDRESS'=>'ADDRESS',
				'MERGE'=>'MERGE',
				'MOBILE_REALLOC' => 'MOBILEREALLOC',
				);
		try{
		$setting_value=$this->settings->getValue("CI_{$map[$this->base_type]}_AUTO_APPROVE");
		}catch(Exception $e)
		{
			$this->logger->error("Auto approve config not found");
			$this->setAutoApproveLog('DISABLED');
			return false;
		}
		
		if($setting_value=="1")
		{
			$this->setAutoApproveLog('CONFIG');
			return true;
		}
		
		$this->setAutoApproveLog('DISABLED');
		return false;
		
	}
	
	private function setAutoApproveLog($status)
	{
		$this->logger->debug("setting the auto approve track log - $status");
		$sql="UPDATE member_care.change_identifier_requests SET auto_approve_type='$status' WHERE id='$this->cir_id'";
		$this->db->update($sql);
	}
	
	private function prepareInsert()
	{
		
		$this->logger->info("preparing the insert params");
		
		switch($this->base_type)
		{
			case 'MOBILE_REALLOC':
				
				$c2_is_existing=false;
				if($this->new_value)
					$c2_is_existing=true;
				else
					$this->new_value="";
				
				$this->logger->debug("nullifying the old customer");
				$success=$this->db->update("UPDATE user_management.users SET mobile=NULL WHERE id='$this->user_id' AND org_id='$this->org_id'");
				if(!$success || $this->db->getAffectedRows()!=1)
				{
					$this->logger->error("setting the old customer mobile to NULL failed! throwing exception. affected rows:".$this->db->getAffectedRows());
					throw new Exception('sql failed');
				}
				
				if(!$c2_is_existing)
				{
					$params['c2_is_existing']=false;
					$sql="INSERT INTO user_management.users (org_id,mobile) VALUES('$this->org_id','$this->old_value')";
					$user_id=$this->db->insert($sql);
					if(!$user_id)
					{
						$this->logger->error("insert into users table failed. Affected rows:".$this->db->getAffectedRows());
						throw new Exception('sql failed');
					}
				}
				else 
				{
					$params['c2_is_existing']=true;
					$user_id=$this->new_value;
					$c2_mobile=$this->db->query_scalar("SELECT mobile FROM user_management.users WHERE id='$user_id' AND org_id='$this->org_id'");
					$this->logger->debug("c2 mobile is $c2_mobile");
					$params['c2_mobile']=$c2_mobile;
					$sql="UPDATE user_management.users SET mobile='$this->old_value' WHERE id=$user_id AND org_id=$this->org_id";
					$update=$this->db->update($sql);
					if(!$update || $this->db->getAffectedRows()!=1)
					{
						$this->logger->error("c2 case: customer mobile update failed. affected rows:".$this->db->getAffectedRows());
						throw new Exception("sql failed");
					}
				}
				
				$this->params=json_encode($params);
				
				//switching the user to the new user
				$this->entity=$this->old_value;
				$this->sec_user_id=$this->user_id;
				$this->old_value=$this->user_id;
				$this->new_value=$this->user_id=$user_id;
				
				break;
				
			default:
				$this->logger->info("nothing to prepare");
		}
		
	}
	
	
	private function postReject()
	{
		$this->logger->info("In the postReject sequence for the change identifier");
		
		switch($this->base_type)
		{
			
			case 'MOBILE_REALLOC':
				
				$user=$this->db->query_firstrow("SELECT * FROM user_management.users WHERE id='$this->user_id' AND org_id='$this->org_id'");
				$mobile=$user['mobile'];
				
				$params=json_decode($this->params,true);
				
				if($params && $params['c2_is_existing']==true)
				{
					
					if($params['c2_mobile'])
						$c2_mobile="'{$params['c2_mobile']}'";
					else
						$c2_mobile="NULL";
						
					$this->logger->info("c2 was existing when the request raised. reverting mobile to $c2_mobile");
					$update=$this->db->update("UPDATE user_management.users SET mobile=$c2_mobile WHERE id='$this->user_id' AND org_id='$this->org_id'");
					if(!$update || $this->db->getAffectedRows()!=1)
					{
						$this->logger->error("new existing customer mobile update/revert failed. affected rows:".$this->db->getAffectedRows());
						throw new Exception("sql failed");
					}
					
				}
				else
				{
					
					$this->logger->info("nullifying the new customer mobile");
					$update=$this->db->update("UPDATE user_management.users SET mobile=NULL WHERE id='$this->user_id' AND org_id='$this->org_id'");
					if(!$update || $this->db->getAffectedRows()!=1)
					{
						$this->logger->error("new customer mobile update failed. affected rows:".$this->db->getAffectedRows());
						throw new Exception("sql failed");
					}
					
				}
				
				$this->logger->info("Updating fraud status for the customer");

				$sql = "INSERT INTO user_management.fraud_users (`org_id`, `user_id`, `status`, `reason`, `entered_by`, `modified`) VALUES ('{$this->org_id}','{$this->user_id}','CONFIRMED','mobile realloc request rejection','{$this->currentuser->user_id}',NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status),reason=VALUES(reason),entered_by=VALUES(entered_by),modified=VALUES(modified)";

				$update=$this->db->insert($sql);
				if(!$update)
				{
					$this->logger->error("fraud status insert for the user failed. affected rows:".$this->db->getAffectedRows());
					throw new Exception("sql failed");
				}
				
				$this->logger->info("Reverting the mobile to the old customer");
				$sql="UPDATE user_management.users SET mobile='$mobile' WHERE id='$this->old_value' AND org_id='$this->org_id'";
				$update=$this->db->update($sql);
				if(!$update || $this->db->getAffectedRows()!=1)
				{
					$this->logger->error("updating mobile for old customer failed. affected rows:".$this->db->getAffectedRows());
					throw new Exception("sql failed");
				}
				
				break;
				
		}
		
	}
	
	protected function postApproveCommit()
	{
		$this->clearCache();
		if($this->base_type!='MERGE')
		{
			$this->logger->info("Updating solr for the user update");
			try{
				require_once 'apiController/ApiCustomerController.php';
				$user=UserProfile::getById($this->user_id);
				$user->load(true);
				$cnt=new ApiCustomerController();
				$cnt->pushCustomerToSolr($user);
			}catch(Exception $e)
			{
				$this->logger->error("Error in updating customer in solr".$e->getMessage());
			}
		}
	}
	
	protected function postRejectCommit()
	{
		
		$this->clearCache();
		
		switch($this->base_type)
		{
			case 'MOBILE_REALLOC':
				$this->logger->info("Updating solr for the users old and new update");
				try{
						
					require_once 'apiController/ApiCustomerController.php';
					$user=UserProfile::getById($this->user_id);
					$user->load(true);
					$cnt=new ApiCustomerController();
					$cnt->pushCustomerToSolr($user);
					$this->logger->info("new customer updated successfully");
					
				}catch(Exception $e)
				{
					$this->logger->error("Error in updating new customer in solr ".$e->getMessage());
				}
					
				try{
							
					$user=UserProfile::getById($this->old_value);
					$user->load(true);
					$cnt=new ApiCustomerController();
					$cnt->pushCustomerToSolr($user);
					$this->logger->info("old customer updated successfully");
						
				}catch(Exception $e)
				{
					$this->logger->error("Error in updating old customer in solr ".$e->getMessage());
				}

				$this->logger->info("registering the user again in subscription service");
				try {
		    		/*include_once 'SubscriptionService/library/services/RegisterUsers.php';
		    		$userSubscriptionRegister = new RegisterUsers();
		    		$userSubscriptionRegister->registerUsersById(array($this->user_id), $this->org_id);*/
		
		    	} catch (Exception $e) {
		    		$this->logger->debug("Calling user subscription failed");
		    	}
			    	
				break;
				
			default:
				$this->logger->info("nothing to do in postRejectCommit");
			    	
		}
		
	}
	
	protected function postAddCommit()
	{
		$this->clearCache();
		switch($this->base_type)
		{
			case 'MOBILE_REALLOC':
				$this->logger->info("Updating solr for the users old and new update");
				try{
	
					require_once 'apiController/ApiCustomerController.php';
					$user=UserProfile::getById($this->user_id);
					$user->load(true);
					$cnt=new ApiCustomerController();
					$cnt->pushCustomerToSolr($user);
					$this->logger->info("new customer updated successfully");
					
					}catch(Exception $e)
					{
						$this->logger->error("Error in updating new customers in solr ".$e->getMessage());
					}
				try{		
					$user=UserProfile::getById($this->old_value);
					$user->load(true);
					$cnt=new ApiCustomerController();
					$cnt->pushCustomerToSolr($user);
					$this->logger->info("old customer updated successfully");
						
				}catch(Exception $e)
				{
					$this->logger->error("Error in updating old customers in solr ".$e->getMessage());
				}
				break;
		}
	
	}
	
	private function updateSubscription($channel,$old_value=false,$new_value=false)
	{
		
		if(!$old_value)
			$old_value=$this->old_value;
		if(!$new_value)
			$new_value=$this->new_value;
			
		require_once 'SubscriptionService/thrift/subscriptionservice.php';
		$sub_srv=new SubscriptionServiceThriftClient();
		
		$this->logger->debug("calling changeTargetValue: $this->org_id, $old_value, $new_value, $channel");
		
		try{
			
			$success=$sub_srv->changeTargetValue($this->org_id,$old_value,$new_value, $channel);
			if(!$success)
				throw new Exception('method returned success not true');
				
			$this->logger->info("subscription service returned success");
			return $success;
				
		}catch(subscriptionservice_SubscriptionServiceException $e)
		{
			$this->logger->error("subscription service thrown exception: ".$e);
			//throw new Exception('ERR_USR_SUBCR_FAILED');
		}catch (Exception $e)
		{
			$this->logger->error("unknown exception happened while subscription update: ".$e);
			//throw new Exception('ERR_USR_SUBCR_FAILED');
		}
		return false;
		
	}
	
	private function postAdd()
	{
		switch($this->base_type)
		{
			case 'MOBILE_REALLOC':
				$mobile = $this -> entity;
				$this -> logger -> info("Subscriptions :: Updating old-customer's mobile from $mobile to 'REALLOC'");
				$success = $this -> updateSubscription('SMS', $mobile, 'REALLOC');

				if ($success) {
					$params = json_decode($this -> params, true);

					$c2AlreadyExists = $params['c2_is_existing'];
					$existingMobile = $params['c2_mobile'];

					if ($c2AlreadyExists) { 
						if (empty($existingMobile)) {
							$this -> logger -> info("Subscriptions :: Register the target-customer under the SMS channel");

							$usersInfo = array(
			    				$this -> user_id => array(
			    					"channel" => "SMS"
		    					)
	    					);

							require_once 'SubscriptionService/library/services/RegisterUsers.php';
				    		$subsRegisterUsers = new RegisterUsers();
				    		$subsRegisterUsers -> registerUsersById($usersInfo, $this -> org_id);
						} else {
							$this -> logger -> info("Subscriptions :: Updating target-customer's mobile from $existingMobile to $mobile");
							$success = $this -> updateSubscription('SMS', $existingMobile, $mobile);
						}
					}
				}
				break;
			default:
				$this->logger->info("nothing to do in postAdd");
		}
	}
	
	private function updateLoyaltyDate()
	{
		$this->logger->info("Setting the last_updated on loyalty");
		$sql="UPDATE user_management.loyalty SET last_updated_by='{$this->currentuser->user_id}',last_updated=NOW() WHERE user_id='$this->user_id' AND publisher_id='$this->org_id'";
		$this->db->update($sql);
		if($this->db->getAffectedRows()>1)
		{
			$this->logger->error("Oops! no of rows affected is greater than 1... rolling back");
			throw new Exception('invalid affected rows: '.$this->db->getAffectedRows());
		}
	}
	
	private function postApprove()
	{
		switch($this->base_type)
		{
			case 'EMAIL':
				$this->updateSubscription('EMAIL');
				break;
			case 'MOBILE':
				$this->updateSubscription('SMS');
				break;
			default:
				$this->logger->info("nothing to do in postApprove");
		}
		$this->updateLoyaltyDate();
	}
	
	
	public function add($base_type,$user_id,$old_value,$new_value,$requested_on,$client_auto_approve=null)
	{
		
		$this->logger->info("adding a change identifier request of $base_type with $user_id, $old_value, $new_value, $client_auto_approve");
		
		$this->beginTransaction();
		
		$this->client_auto_approve=$client_auto_approve;
		
		$this->setHash(
						array('user_id'=>$user_id, 
							 'base_type'=>$base_type, 
							  'old_value'=>$old_value, 
								'new_value'=>$new_value, 
								'requested_on'=>$requested_on,
						)
				);
		
		try{
			
			$this->prepareInsert();
			$this->insert();
			
			$is_auto_approve=$this->isAutoApprove();

            // ingest event here, since the change identifier has been added
            $changeIdentifierEventAttributes = array();
            $changeIdentifierEventAttributes["subtype"] =$base_type; // done to convert this to long
            $changeIdentifierEventAttributes["autoApprove"] = $is_auto_approve;
            $changeIdentifierEventAttributes["userId"] =intval($user_id);
            $changeIdentifierEventAttributes["changestatus"] ="PROCESSING";
            $changeIdentifierEventAttributes["oldValue"] =$old_value;
            $changeIdentifierEventAttributes["newValue"] =$new_value;
            $changeIdentifierEventAttributes["requestId"] =$this->request_id;
            EventIngestionHelper::ingestEventAsynchronously( intval($this->org_id), "changeidentifier",
                "Change identifier request from Intouch PHP API's", $requested_on, $changeIdentifierEventAttributes);


            if($is_auto_approve)
			{
				$this->logger->info("Auto approve is ON, doing the approving");
				$this->changeIdentifier();
				$status=$this->changeStatus($this->request_id, 'APPROVED');
				$this -> postAdd();
				$this->postApprove();

                // ingest event here, since the change identifier has been approved
                $updateIdentifierEventAttributes = array();
                $updateIdentifierEventAttributes["subtype"] =$base_type; // done to convert this to long
                $updateIdentifierEventAttributes["autoApprove"] = true;
                $updateIdentifierEventAttributes["userId"] =intval($user_id);
                $updateIdentifierEventAttributes["changestatus"] ="APPROVED";
                $updateIdentifierEventAttributes["oldValue"] =$old_value;
                $updateIdentifierEventAttributes["newValue"] =$new_value;
                $updateIdentifierEventAttributes["requestId"] =$this->request_id;
                EventIngestionHelper::ingestEventAsynchronously( intval($this->org_id), "updateidentifier",
                    "Update identifier request from Intouch PHP API's", time(), $updateIdentifierEventAttributes);

                $this->sendConfirmation();
			}else
				$this->sendNotifications();
			
			//$this->postAdd();
				
			$this->commitTransaction();
			$this->logger->info("insert successful and transaction committed");
			
			$this->logger->info("post processing after commit");
			$this->postAddCommit();
			if($is_auto_approve)
				$this->postApproveCommit();
			$this->logger->info("post processing done");
				
			return array(
					'id'=>$this->request_id,
					'new_value'=>$this->new_value,
					'old_value'=>$this->old_value,
					'user_id'=>$this->user_id,
					'status'=>$this->status,
					);
			
		}catch(Exception $e)
		{
			$this->logger->debug("rolling back the transaction as exception occured: ".$e);
			$this->rollbackTransaction();
			if(!in_array($e->getMessage(),array_keys(ErrorCodes::$request)))
				throw new Exception('ERR_REQUEST_ADD_FAILED');
			else 
				throw new Exception($e->getMessage());
		}
		
		//will not reach here!! 
		//if reached, fuck the php!!!
		return null;
		
	}
	
	protected function clearCache()
	{
		
		$cache = MemcacheMgr::getInstance();
		
		//reset the merged customer cache list
		if($this->base_type=='MERGE')
		{
			$cache_key="o".$this->org_id."_".CacheKeysPrefix::$mergedCustomers;
			try{
				$cache->delete($cache_key);
				$this->logger->debug("cleared key $cache_key");
			}catch(Exception $e)
			{
				$this->logger->error("error in clearing cache key $cache_key : ".$e);
			}
		}
		
		
		$this->logger->debug("MC: trigger the cache clear for $this->user_id, $this->sec_user_id");
		ApiCacheHandler::triggerEvent('customer_update', $this->user_id);
		ApiUtil::mcUserUpdateCacheClear($this->user_id);
		if($this->sec_user_id){
			ApiCacheHandler::triggerEvent('customer_update', $this->sec_user_id);
			ApiUtil::mcUserUpdateCacheClear($this->sec_user_id);
		}

		$mc_cache=new MemberCareCacheMgr();
		$mc_cache->clear($this->old_value, "search","customer");
			
		if(empty($this->user_hash) && isset($this->user_hash->mobile))
		{
			$this->logger->error("user hash is not available so skipping cache clear");
			return;
		}
		$keys=array();
		$keys[] = "o" . $this->org_id . "_" . CacheKeysPrefix::$userProfileMobile . $this->org_id . "_" . $this->user_hash->mobile;
		$keys[] = "o" . $this->org_id . "_" .CacheKeysPrefix::$userProfileEmail . $this->org_id . "_" . $this->user_hash->email;
		$keys[] = "o" . $this->org_id . "_" . CacheKeysPrefix::$userProfileId . $this->user_hash->user_id;
		
		$this->logger->debug("clearing the cache for keys ".implode(", ",$keys));
		foreach($keys as $key)
		{
			try{
				$cache->delete($key);
				$this->logger->debug("cleared key $key");
			}catch(Exception $e)
			{
				$this->logger->error("error in clearing cache key $key : ".$e);
			}
		}
		
		
	}
	
	protected function sendNotifications()
	{
		
		if(isset($_REQUEST['one_step_change']) && strtolower($_REQUEST['one_step_change'])=="true")
		{
			$this->logger->info("skipping notification send as the request is one step change");
			return;
		}
		
		$this->load($this->request_id);
		
		$this->logger->info("notification mails to be sent");
		$map=array(
				'MERGE'=>'MERGE',
				'EMAIL'=>'EMAIL',
				'MOBILE'=>'MOBILE',
				'EXTERNAL_ID'=>'EXTID',
				);
		if(!isset($map[$this->base_type]))
			return;
		
		try{
			$recipient_ids=json_decode($this->settings->getValue("CI_{$map[$this->base_type]}_NOTIFY"));
		}catch(Exception $e)
		{
			$this->logger->error("Error in getting the settings key : ".$e);
			$this->logger->info("Not throwing exception... continuing..");
			$recipient_ids=array();
		}
		
		if(!is_array($recipient_ids) || empty($recipient_ids))
		{
			$this->logger->info("no recipients set for notification");
			return;
		}
			
		$this->logger->debug("recipient_ids: ".implode(", ",$recipient_ids));
		
		$customer=UserProfile::getById($this->user_id);
		$customer->load(true);
		
		$store=$this->db->query_scalar("SELECT o.name FROM masters.org_entity_relations oe
											JOIN masters.org_entities o ON o.id=oe.parent_entity_id
											WHERE oe.parent_entity_type='STORE' AND oe.child_entity_id='{$this->currentuser->user_id}'"
									);
		if(!$store)
			$store=$this->currentuser->getName();
		
		$in_conf=parse_ini_file('/etc/capillary/cheetah-config/config.ini',true);
		
		$intouch_url=$in_conf['server-region']['url'];
		$url=array(
				'EXTERNAL_ID'=>'memberCare/changeRequests/ExternalID',
				'MERGE'=>'memberCare/changeRequests/Account',
				'EMAIL'=>'memberCare/changeRequests/Email',
				'MOBILE'=>'memberCare/changeRequests/Mobile'
				);
		
		$payload=array(
				'org_name'=>$this->currentorg->name,
				'customer_name'=>$customer->getName(),
				'customer_mobile'=>$customer->mobile,
				'customer_email'=>$customer->email,
				'customer_external_id'=>$customer->external_id,
				'old_value'=>$this->old_value,
				'new_value'=>$this->new_value,
				'store'=>$store,
				'time'=>date('d M Y',strtotime($this->created_on)),
				'type'=>ucfirst(str_replace('id','ID',strtolower(str_replace('_',' ',$this->base_type)))),
				'url'=>"<a href=\"{$intouch_url}/{$url[$this->base_type]}\">{$intouch_url}/{$url[$this->base_type]}</a>",
		);
		
		if($this->base_type=='MERGE')
		{
			$survivor=UserProfile::getById($this->new_value);
			$survivor->load(true);
			$payload=array_merge($payload,
					array(
							'survivor_name'=>$survivor->getName(),
							'survivor_mobile'=>$survivor->mobile,
							'survivor_email'=>$survivor->email,
							'survivor_external_id'=>$survivor->external_id,
							));
		}
		
		include_once "model_extension/class.AdminUserModelExtension.php";
		$recipients = AdminUserModelExtension::getUsersByIds($this->org_id, $recipient_ids);
		//$recipients=$this->db->query("SELECT * FROM masters.admin_users WHERE id IN (".implode(",",$recipient_ids).")");
		foreach($recipients as $recipient)
		{
			if(empty($recipient['email']))
			{
				$this->logger->error("Skipping sending email as email id is empty");
				continue;
			}
			
			$template=$this->getTemplate('NOTIFICATION');
			$payload['admin_name']=$recipient['first_name']." ".$recipient['last_name'];
			
			$subject=$this->deTemplatize($template['subject'], $payload);
			$msg=nl2br($this->deTemplatize($template['msg'], $payload));
			
			Util::sendEmail($recipient['email'], $subject, $msg, $this->currentorg->org_id);
			
		}
		
	}
	
	
	protected function sendConfirmation()
	{
		
		$this->logger->info("confirmation email/sms to be sent");
		
		$map=array(
				'MERGE'=>'MERGE',
				'EMAIL'=>'EMAIL',
				'MOBILE'=>'MOBILE',
				'EXTERNAL_ID'=>'EXTID',
		);
		if(!isset($map[$this->base_type]))
			return;
	
		$customer=UserProfile::getById($this->user_id);
		$customer->load(true);
		
		$payload=array(
				'first_name'=>$customer->first_name,
				'last_name'=>$customer->last_name,
				'email'=>$customer->email,
				'mobile'=>$customer->mobile,
				'external_id'=>$customer->external_id,
				'old_value'=>$this->old_value,
				'new_value'=>$this->new_value,
				'type'=>ucfirst(strtolower($this->base_type)),
				);
	

		if($this->base_type=='MERGE')
		{
			$survivor=UserProfile::getById($this->new_value);
			$survivor->load(true);
			$payload=array_merge($payload,
				array(
						'survivor_first_name'=>$survivor->first_name,
						'survivor_last_name'=>$survivor->last_name,
						'survivor_mobile'=>$survivor->mobile,
						'survivor_email'=>$survivor->email,
						'survivor_external_id'=>$survivor->external_id,
				));
		}
		
		$template=$this->getTemplate('CONFIRMATION');
		
		$this->logger->debug("template for confirmation".print_r($template,true));
		
		if(!empty($template['email']['msg']))
		{
			$subject=$template['email']['subject'];
			$msg=$this->deTemplatize($template['email']['msg'], $payload);
				
			$recipients=array();
		
			if($this->base_type=="EMAIL" || $this->base_type=="MERGE")
			{
				$to_ids=json_decode($this->getSettingsKeyValue("CI_".$map[$this->base_type]."_CONFIRM_IDENTIFIER"),true);
		
				if(in_array('new_id', $to_ids))
				{
					if($this->base_type=="MERGE")
						$recipients[]=$survivor->email;
					else
						$recipients[]=$this->new_value;
				}
		
				if(in_array('old_id', $to_ids))
				{
					if($this->base_type=="MERGE")
						$recipients[]=$customer->email;
					else
						$recipients[]=$this->old_value;
				}
				
			}else
				$recipients[]=$customer->email;
				
			$this->logger->info("sending confirmation email to ".implode(",",$recipients));
				
			foreach($recipients as $rec)
			{
				if(!empty($rec))
					Util::sendEmail($rec, $subject, $msg, $this->org_id);
				else
					$this->logger->error("no recipents to send email");
			}
				
				
		}else
			$this->logger->info("skipping confirmation by email as template is empty");
		
		if(!empty($template['sms']))
		{
			$msg=$this->deTemplatize($template['sms'], $payload);
				
			$recipients=array();
		
			if($this->base_type=="MOBILE" || $this->base_type=="MERGE")
			{
				$to_ids=json_decode($this->getSettingsKeyValue("CI_".$map[$this->base_type]."_CONFIRM_IDENTIFIER"));
		
				if(in_array('new_id', $to_ids))
				{
					if($this->base_type=="MERGE")
						$recipients[]=$survivor->mobile;
					else
						$recipients[]=$this->new_value;
				}
		
				if(in_array('old_id', $to_ids))
				{
					if($this->base_type=="MERGE")
						$recipients[]=$customer->mobile;
					else
						$recipients[]=$this->old_value;
				}

			}else
				$recipients[]=$customer->mobile;
				
			$this->logger->info("sending confirmation sms to ".implode(",",$recipients));
				
			foreach($recipients as $rec)
			{
				if(!empty($rec))
					Util::sendSms($rec, $msg, $this->org_id);
				else
					$this->logger->error("no recipients to send sms");
			}
				
		}else
			$this->logger->info("skipping confirmation by sms as template is empty");
		
	}
	
	
	private function getTemplate($type)
	{
		
		switch($type)
		{
			case 'NOTIFICATION':
				switch(strtoupper($this->base_type))
				{
					case 'EMAIL':
					case 'MOBILE':
					case 'EXTERNAL_ID':
						$subject='{{org_name}} - {{type}} change request by {{customer_name}}';
						$msg= '
								Hi {{admin_name}},

								The following {{type}} change has been requested
								
								Customer Name : {{customer_name}}
								Existing {{type}} : {{old_value}}
								Requested {{type}} : {{new_value}}
								Requested Store : {{store}}
								Requested Time : {{time}}
								
								Click here to process this request {{url}}
						';
						$ret=array('subject'=>$subject,'msg'=>$msg);
						break;
					case 'MERGE':
						$subject='{{org_name}} - Merge account request by {{customer_name}}';
						$msg= '
								Hi {{admin_name}},
								
								The following merge of accounts has been requested
								
								<u>Requested By</u>
								Customer Name : {{customer_name}}
								Mobile : {{customer_mobile}}
								Email : {{customer_email}}
								External ID : {{customer_external_id}}
								
								<u>To be merged with</u>
								Customer Name : {{survivor_name}}
								Mobile : {{survivor_mobile}}
								Email : {{survivor_email}}
								External ID : {{survivor_external_id}}
								
								Click here to process this request {{url}}
								
						';
						$ret=array('subject'=>$subject,'msg'=>$msg);
						break;
				}
				break;
			case 'CONFIRMATION':
				switch(strtoupper($this->base_type))
				{
					case 'EMAIL':
						$subject=$this->getSettingsKeyValue("CI_EMAIL_CONFIRM_EMAIL_TEMPLATE_SUBJECT",true);
						$ret['email']['subject']=empty($subject)?'Email change confirmation':$subject;
						$ret['email']['msg']=$this->getSettingsKeyValue('CI_EMAIL_CONFIRM_EMAIL_TEMPLATE',true);
						$ret['sms']=$this->getSettingsKeyValue('CI_EMAIL_CONFIRM_SMS_TEMPLATE',true);
						break;
					case 'MOBILE':
						$subject=$this->getSettingsKeyValue("CI_MOBILE_CONFIRM_EMAIL_TEMPLATE_SUBJECT",true);
						$ret['email']['subject']=empty($subject)?'Mobile change confirmation':$subject;
						$ret['email']['msg']=$this->getSettingsKeyValue('CI_MOBILE_CONFIRM_EMAIL_TEMPLATE',true);
						$ret['sms']=$this->getSettingsKeyValue('CI_MOBILE_CONFIRM_SMS_TEMPLATE',true);
						break;
					case 'EXTERNAL_ID':
						$subject=$this->getSettingsKeyValue("CI_EXTID_CONFIRM_EMAIL_TEMPLATE_SUBJECT",true);
						$ret['email']['subject']=empty($subject)?'External ID change confirmation':$subject;
						$ret['email']['msg']=$this->getSettingsKeyValue('CI_EXTID_CONFIRM_EMAIL_TEMPLATE',true);
						$ret['sms']=$this->getSettingsKeyValue('CI_EXTID_CONFIRM_SMS_TEMPLATE',true);
						break;
					case 'MERGE':
						$subject=$this->getSettingsKeyValue("CI_MERGE_CONFIRM_EMAIL_TEMPLATE_SUBJECT",true);
						$ret['email']['subject']=empty($subject)?'Account merge confirmation':$subject;
						$ret['email']['msg']=$this->getSettingsKeyValue('CI_MERGE_CONFIRM_EMAIL_TEMPLATE',true);
						$ret['sms']=$this->getSettingsKeyValue('CI_MERGE_CONFIRM_SMS_TEMPLATE',true);
						break;
				}
				break;
		}
		
		return $ret;
	}
	
	public function isMobileReallocPendingForOldCustomer($user_id)
	{
		$sql="SELECT 1 FROM member_care.change_identifier_requests ci
					JOIN member_care.requests r ON r.org_id='$this->org_id' AND r.id=ci.request_id  
					WHERE ci.org_id='$this->org_id' AND old_value='$user_id' AND r.status='PENDING' 
						AND ci.type='MOBILE_REALLOC'";
		$data=$this->db->query_firstrow($sql);
		
		if(empty($data))
			return false;
		
		return true;
	}
	
}
