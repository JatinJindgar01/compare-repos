<?php

require_once 'apiModel/class.ApiGoodwillRequestModel.php';
require_once 'apiHelper/ApiCacheHandler.php';

class ApiGoodwillRequestModelExtension extends ApiGoodwillRequestModel
{
	
	protected $settings;
	protected $db_slave;
	
	function __construct()
	{
		parent::__construct();
		$this->settings=new MemberCareRequestSettingsModelExtension();
		$this->db_slave=new Dbase("member_care",true);
	}
	
	private function changeStatus($id,$status,$assoc_id,$approved_value,$updated_comments,$program_id = -1)
	{
		$request_id=$id;
		
		$this->logger->debug("payload: ".implode(" , ",func_get_args()));
		
		$this->load($id);

		$this->beginTransaction();
		
		try{

			$sql="SELECT * FROM member_care.goodwill_requests WHERE org_id=$this->org_id AND request_id=".$id;
                
        	$row=$this->db->query_firstrow($sql);

        	$program_id = $row['program_id'];
			
			if(strtoupper($status)=='APPROVED')
				$assoc_id=$this->doGoodwill($approved_value,$program_id);	
			$this->update($status, $updated_comments, $assoc_id, $approved_value);

			$this->commitTransaction();

			if(strtoupper($status)=='APPROVED')
				$this->sendConfirmation();
				
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
		
		return true;
	}
	
	private function doGoodwill(&$approved_value,$program_id = -1)
	{
		switch($this->base_type)
		{
			
			
			case 'POINTS':
				
				ApiCacheHandler::triggerEvent("points_update", $this->user_id);
				ApiUtil::mcUserUpdateCacheClear($this->user_id);
			        //	
				$approved_value=ltrim(trim($approved_value," "),0);
				if($approved_value{0}==".")
					$approved_value="0".$approved_value;
				if($approved_value<=0 || empty($approved_value) || strlen((float)$approved_value)!=strlen($approved_value))
					throw new Exception('ERR_REQUEST_INVALID_POINTS');
				
				$this->logger->info("finding the till for request");
				$sql="SELECT 1 FROM masters.org_entities WHERE id='{$this->created_by}' AND type='TILL'";
				$till=$this->db->query_firstrow($sql);
                                
                                if(empty($till))
				{
                                    //$a=$this->db->q
                                    $default_till_id = $this->db->query_firstrow("SELECT IFNULL(rsv.value, rs.default_value) AS `value` FROM request_settings as rs LEFT JOIN request_setting_values as rsv on rsv.key_id=rs.id and rsv.org_id='{$this->org_id}' WHERE rs.name='CONF_DEFAULT_POINTS_TILL'");
                                    if($default_till_id==null || empty($default_till_id['value']) || $default_till_id['value']==null ||$default_till_id['value']== NULL){
                                        $till_id=$this->db->query_scalar("SELECT id FROM masters.org_entities WHERE org_id='{$this->org_id}' AND is_active=1 AND type='TILL'");
                                        $this->logger->info("Coudnt get anything from member_care default org, using $till_id as till");
                                    }else{
                                        $this->logger->info("Got till ids ".print_r($default_till_id,true)." as default till from member care");
                                        $till_id=$default_till_id['value'];
                                    }
				}
                                
				else
					$till_id=$this->created_by;
				
				$this->logger->info("using tillid as $till_id");
				
				require_once 'business_controller/points/PointsEngineServiceController.php';
				$pes=new PointsEngineServiceController();
				try{
					$pes->allocateGoodwillPoints($this->user_id, $approved_value, $this->request_id, $till_id,$program_id);
				}catch(Exception $e)
				{
					$this->logger->error("Exception in PE for GW allocation");
					throw new Exception('ERR_REQUEST_APPROVE_POINTS_FAILED');
				}
				$assoc_id='';
				break;
				
				
			case 'COUPON':
				require_once 'helper/coupons/CouponManager.php';
				
				ApiCacheHandler::triggerEvent("coupon_update", $this->user_id);
				
				$series=$this->db->query_firstrow("SELECT 1 FROM luci.voucher_series WHERE series_type='GOODWILL' AND id='$approved_value' AND org_id='$this->org_id'");
				if(empty($series) || strlen((int)$approved_value)!=strlen($approved_value))
					throw new Exception('ERR_REQUEST_INVALID_SERIES');
				
				$isLuciFlowEnabled = Util::isLuciFlowEnabled();
				if ($isLuciFlowEnabled) {
					$response = ApiUtil :: newIssueCoupon($this -> org_id, $this -> user_id, 
						$approved_value, $this -> currentuser -> user_id);

					$success = $response -> success;
					if ($success) {
						$assoc_id = $response -> coupon -> couponCode;
						$this -> logger -> info("Issued Goodwill coupon via LUCI with code: " . $assoc_id);
					} /*else {
						throw new Exception($response -> exceptionCode);
					}*/
				} else {
					$cp_mgr=new CouponManager();
					$params=array(
							'user_id'=>$this->user_id,
							'series_id'=>$approved_value,
							'created_by'=>$this->currentuser->user_id,
							);
					$assoc_id=$cp_mgr->issue($approved_value, $params);
				}

				$this->voucher_code=$assoc_id;
				if(!$assoc_id)
					throw new Exception('ERR_REQUEST_APPROVE_COUPON_FAILED');
				$assoc_id=$this->db->query_scalar("SELECT voucher_id FROM luci.voucher WHERE org_id='$this->org_id' AND voucher_code='$assoc_id'");
				break;
				
			case 'TIER':
				
				ApiCacheHandler::triggerEvent("points_update", $this->user_id);
				ApiUtil::mcUserUpdateCacheClear($this->user_id);
				
				$this->logger->info("finding the till for request");
				$sql="SELECT 1 FROM masters.org_entities WHERE id='{$this->created_by}' AND type='TILL'";
				$till=$this->db->query_firstrow($sql);
                                //
				if(empty($till))
				{
                                    //$a=$this->db->q
                                    $default_till_id = $this->db->query_firstrow("SELECT IFNULL(rsv.value, rs.default_value) AS `value` FROM request_settings as rs LEFT JOIN request_setting_values as rsv on rsv.key_id=rs.id and rsv.org_id='{$this->org_id}' WHERE rs.name='CONF_DEFAULT_TIER_TILL'");
                                    if($default_till_id==null || empty($default_till_id['value']) || $default_till_id['value']==null ||$default_till_id['value']== NULL){
                                        $till_id=$this->db->query_scalar("SELECT id FROM masters.org_entities WHERE org_id='{$this->org_id}' AND is_active=1 AND type='TILL'");
                                        $this->logger->info("Coudnt get anything from member_care default org, using $till_id as till");
                                    }else{
                                        $this->logger->info("Got till ids ".print_r($default_till_id,true)." as default till from member care");
                                        $till_id=$default_till_id['value'];
                                    }
				}
				else
					$till_id=$this->created_by;
				
				$this->logger->info("using tillid as $till_id");
				
				require_once 'business_controller/points/PointsEngineServiceController.php';
				$pes=new PointsEngineServiceController();
				try{
					$pes->renewCustomerSlabByName($this->user_id, $approved_value, $till_id, date('YmdHis'), false, "GOODWILL_RENEWAL");
				}catch(Exception $e){
					$this->logger->error("Exception in PE for GW tier renewal");
					throw new Exception('ERR_REQUEST_APPROVE_POINTS_FAILED');
				}
				$assoc_id='';
				break;
				
		}
		return $assoc_id;
	}
	
	function approve($id,$approved_value,$updated_comments,$base_type,$program_id = -1)
	{
		$request_id=$id;
		
		$base_type=strtoupper($base_type);
		
		$this->logger->info("approving the goodwill request $id. payload: ".implode(",",func_get_args()));
		
		if(!$this->isExists($id,'PENDING'))
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		$this->load($id);
		if($this->base_type!=$base_type)
			throw new Exception('ERR_REQUEST_NOT_FOUND');

		if ($this -> created_by == $this -> currentuser -> user_id && !$this -> is_one_step_change) 
			throw new Exception('ERR_REQUESTER_CANNOT_APPROVE');
		
		return $this->changeStatus($id, 'APPROVED', '', $approved_value, $updated_comments,$program_id);
	}
	
	function reject($id,$updated_comments,$base_type,$program_id = -1)
	{
		$this->logger->info("rejecting the goodwill request $id. payload: ".implode(",",func_get_args()));
		
		if(!$this->isExists($id,'PENDING'))
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		$this->load($id);
		if($this->base_type!=$base_type)
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		
		if(empty($updated_comments))
			throw new Exception('ERR_REQUEST_REJECT_REASON_EMPTY');
		
		return $this->changeStatus($id, 'REJECTED', '-1', '', $updated_comments,$program_id);
	}
	
	function getDetails($id)
	{
		$sql="SELECT r.id AS request_id,r.status,r.params,gr.type AS base_type,gr.reason,gr.comments,gr.assoc_id,gr.approved_value,gr.updated_comments,
				u.firstname,u.lastname,l.external_id,u.mobile,u.email,u.id AS user_id
				FROM member_care.goodwill_requests gr
				JOIN member_care.requests r ON r.org_id=$this->org_id AND r.request_id=gr.request_id
				JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
				JOIN user_management.loyalty l ON l.publisher_id=$this->org_id AND l.user_id=r.user_id
				WHERE r.org_id=$this->org_id AND r.id=$id
				";
		return $this->db->query_row($sql);
	}
	
	function getRequests($status,$type,$base_type,$user_id,$start_date,$end_date,$start_id,$end_id,$limit,$start_limit=null)
	{
		
		$count_sql_prefix="SELECT COUNT(*)
					FROM member_care.goodwill_requests gr
					JOIN member_care.requests r ON r.org_id=$this->org_id AND r.id=gr.request_id
					JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
					WHERE
					";
		
		$sql_prefix="SELECT r.id AS request_id,r.type AS type, r.status,r.params,gr.type AS base_type,gr.reason,gr.comments,gr.assoc_id,gr.approved_value,gr.updated_comments,
				u.firstname,u.lastname,l.external_id,u.mobile,u.email,u.id AS user_id, f.status AS fraud_status,
				r.created_on AS added_on,r.updated_on,
				r.created_by AS added_by_till_code, r.created_by AS added_by_till_name,
				o.code AS added_by_code, o.name AS added_by_name,
				r.updated_by AS updated_by_code, r.updated_by AS updated_by_name, gr.program_id as program_id
				FROM member_care.goodwill_requests gr
				JOIN member_care.requests r ON r.org_id=$this->org_id AND r.id=gr.request_id
				JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
				LEFT OUTER JOIN user_management.loyalty l ON l.publisher_id=$this->org_id AND l.user_id=r.user_id
				LEFT OUTER JOIN user_management.fraud_users f ON f.org_id=$this->org_id AND f.user_id=r.user_id
				LEFT OUTER JOIN masters.org_entity_relations er ON er.child_entity_id=r.created_by AND er.parent_entity_type='STORE' AND er.org_id = $this->org_id 
				LEFT OUTER JOIN masters.org_entities o ON o.id=er.parent_entity_id AND o.org_id = er.org_id 
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
			$conds[]="gr.type='$base_type'";
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
			
		$cond_sql=implode(" AND ",$conds);
		
		if($start_limit)
			$limit_str="$start_limit, $limit";
		else
			$limit_str="$limit";
		
		$order_sql=" ORDER BY r.id DESC LIMIT $limit_str";
		
		$sql="$sql_prefix $cond_sql $order_sql";
		$count_sql="$count_sql_prefix $cond_sql";

		$count=$this->db->query_scalar($count_sql);
		$data=$this->db->query($sql);
		
		include_once 'helper/memory_joiner/impl/MemoryJoinerFactory.php';
		include_once 'helper/memory_joiner/impl/MemoryJoinerType.php';
		
		$key_map = array( "added_by_till_code" => "code", "added_by_till_name" => "name" );
		//$key_map = array( "org" => "{{joiner_concat(name,id)}}" );
		$org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
		$data = $org_entity->prepareReport( $data, $key_map );
		
		$key_map = array( "updated_by_code" => "code", "updated_by_name" => "name" );
		//$key_map = array( "org" => "{{joiner_concat(name,id)}}" );
		$org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
		$data = $org_entity->prepareReport( $data, $key_map );
		
		return array(
					'count'=>$count,
					'data'=>$data
					);
		
	}
	
	
	function getRequestLogs($base_type,$start_date,$end_date,$status,$added_by,$updated_by,$request_id,$pr_customer_id,$is_one_step_change,$start_id,$end_id,$limit)
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
	
		$conds[]="ci.type='$base_type'";
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
		
		if($is_one_step_change)
		{
			if(strtolower($is_one_step_change)=="true")
				$conds[]="r.is_one_step_change=1";
			else
				$conds[]="r.is_one_step_change=0";
		}
			
	
		if(!$limit)
			$limit=10;
	
		if($request_id)
			$conds[]="r.id='".addslashes($request_id)."'";
	
		//LEFT OUTER JOIN masters.admin_users au ON au.id=r.updated_by
		$table_sql="
			FROM member_care.goodwill_requests ci
			JOIN member_care.requests r ON r.org_id=$this->org_id AND r.id=ci.request_id
			JOIN user_management.users u ON u.org_id=$this->org_id AND u.id=r.user_id
			LEFT OUTER JOIN user_management.loyalty l ON l.publisher_id=$this->org_id AND l.user_id=r.user_id
			LEFT OUTER JOIN user_management.fraud_users f ON f.org_id=$this->org_id AND f.user_id=r.user_id
			LEFT OUTER JOIN masters.org_entity_relations er ON er.child_entity_id=r.created_by AND er.parent_entity_type='STORE' AND er.org_id = $this->org_id
			LEFT OUTER JOIN masters.org_entities o ON o.id=er.parent_entity_id AND o.org_id = er.org_id 
			LEFT OUTER JOIN member_care.audit_logs al ON al.org_id='$this->org_id' AND al.created_by=r.updated_by AND al.assoc_id=r.id AND (al.api_method='reject' OR al.api_method='approve') AND al.api_status=200 
			WHERE
		";
//		LEFT OUTER JOIN masters.org_entities e ON e.id=r.created_by		
//		LEFT OUTER JOIN masters.org_entities uo ON uo.id=r.updated_by
//		CONCAT(au.first_name," ",au.last_name) AS updated_by_name, au.mobile AS updated_by_mobile, au.email AS updated_by_email,	
		$select_sql='SELECT r.type AS type, r.id AS request_id,r.status,r.params,ci.type AS base_type,
			u.firstname,u.lastname,l.external_id,u.mobile,u.email,u.id AS user_id, f.status AS fraud_status, r.created_on AS added_on,
			o.code AS added_by_code, o.name AS added_by_name,r.updated_on,
			r.created_by AS added_by_till_code, r.created_by AS added_by_till_name,
			ci.reason,
			ci.comments,
                        ci.assoc_id AS voucher_id,
			ci.updated_comments,
			r.updated_by AS updated_by_oe_name,
			ci.approved_value,
			r.updated_by AS updated_by_name, r.updated_by AS updated_by_mobile, r.updated_by AS updated_by_email,
			al.source_ip AS update_ip_addr, r.is_one_step_change
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
		
		$key_map = array( "updated_by_oe_name" => "name" );
		$org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
		$logs = $org_entity->prepareReport( $logs, $key_map );
		
		$key_map = array( "updated_by_name" => "{{joiner_concat(first_name,last_name)}}", "updated_by_mobile" => "mobile", "updated_by_email" => "email" );
		$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
		$logs = $admin_user->prepareReport( $logs, $key_map );
		
		return array('count'=>$count,'logs'=>$logs);
	
	}
	
	
	public function add($user_id,$base_type,$reason,$comments,$requested_on, $isOneStepChange = false, $approvedValue = null, $program_id = -1)
	{
	
		$this->logger->info("adding a goodwill request of $base_type with $user_id, $reason, $comments");
	
		$this->beginTransaction();
	
		$this->setHash(array('user_id'=>$user_id, 'base_type'=>$base_type, 'reason'=>$reason, 'comments'=>$comments, 'requested_on'=>$requested_on, 'program_id' => $program_id));
	
		try{
				
			$this->insert();
			
			if($this->base_type == 'TIER')
				$this->logger->debug("no notification for base type tier");
			else
				$this->sendNotifications();
				
			$this->commitTransaction();
			$this->logger->info("insert successful");

			$this -> is_one_step_change = $isOneStepChange;
			if ($this -> is_one_step_change) {
				$this -> logger -> debug("Auto approval is set ON; triggering approve($approvedValue)");
				$comments .= '; AUTO APPROVED';
				$this -> approve($this -> request_id, $approvedValue, $comments, $base_type, $program_id); 
			}
				
			return array(
					'id'=>$this->request_id,
					'reason'=>$this->reason,
					'comments'=>$this->comments,
					'user_id'=>$this->user_id,
					'status'=>$this->status,
			);
				
		}catch(Exception $e)
		{
				
			$this->logger->debug("rolling back the transaction as exception occured: ".$e);
			$this->rollbackTransaction();
			throw new Exception('ERR_REQUEST_ADD_FAILED');
		}
	
		//will not reach here!!
		//if reached, fuck the php!!!
		return null;
	
	}
	
	private function getTemplate($type)
	{
	
		switch(strtoupper($type))
		{
			case 'NOTIFICATION':
				switch(strtoupper($this->base_type))
				{
					case 'COUPON':
					case 'POINTS':
						$subject='{{org_name}} - Goodwill {{type}} request for {{customer_name}}';
						$msg= '
						Hi {{admin_name}},
	
						The following are the details of goodwill {{type}} request
	
						Customer Name : {{customer_name}}
						Mobile Number : {{customer_mobile}}
						Email ID : {{customer_email}}
						External ID : {{customer_external_id}}
						Goodwill Reason : {{gw_reason}}
						Comments : {{gw_comments}}
	
						Click here to process this request {{url}}
						';
						$ret=array(
								'subject'=>$subject,
								'msg'=>$msg
								);
						break;
				}
				break;
			case 'CONFIRMATION':
				switch(strtoupper($this->base_type))
				{
					case 'POINTS':
						$subject=$this->getSettingsKeyValue("GW_POINTS_CONFIRM_EMAIL_TEMPLATE_SUBJECT",true);
						$ret['email']['subject']=empty($subject)?'You have received points':$subject;
						$ret['email']['msg']=$this->getSettingsKeyValue("GW_POINTS_CONFIRM_EMAIL_TEMPLATE",true);
						$ret['sms']=$this->getSettingsKeyValue('GW_POINTS_CONFIRM_SMS_TEMPLATE',true);
						break;
					case 'COUPON':
						$ret['email']['subject']="";
						$ret['email']['msg']="";
						$v_series=new ApiVoucherSeriesModel();
						$v_series->load($this->approved_value);
						$template=$v_series->getSmsTemplate();
						$ret['sms']=$template;
						break;
				}
				break;
		}
	
		return $ret;
		
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
				'COUPON'=>'COUPON',
				'POINTS'=>'POINTS',
		);
		if(!isset($map[$this->base_type]))
			return;
	
		try{
			$recipient_ids=json_decode($this->settings->getValue("GW_{$map[$this->base_type]}_NOTIFY"));
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
		
		$in_conf=parse_ini_file('/etc/capillary/cheetah-config/config.ini',true);
		
		$intouch_url=$in_conf['server-region']['url'];
		$url=array(
				'COUPON'=>'memberCare/goodwill/Coupon',
				'POINTS'=>'memberCare/goodwill/Point'
		);
		
		$payload=array(
				'org_name'=>$this->currentorg->name,
				'customer_name'=>$customer->getName(),
				'customer_mobile'=>$customer->mobile,
				'customer_email'=>$customer->email,
				'customer_external_id'=>$customer->external_id,
				'type'=>ucfirst(str_replace('id','ID',strtolower(str_replace('_',' ',$this->base_type)))),
				'gw_comments'=>$this->comments,
				'gw_reason'=>$this->reason,
				'url'=>"<a href=\"{$intouch_url}/{$url[$this->base_type]}\">{$intouch_url}/{$url[$this->base_type]}</a>",
		);

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
		
		$this->logger->info("confirmation mail/sms to be sent");
		$map=array(
				'COUPON'=>'COUPON',
				'POINTS'=>'POINTS',
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
		);
		
		if($this->base_type=="POINTS")
			$payload['goodwill_points']=$this->approved_value;
		else if($this->base_type=="COUPON")
		{
			require_once 'apiModel/class.ApiVoucherModelExtension.php';
			$cp=new ApiVoucherModelExtension();
			$cp->loadByCode($this->voucher_code);
			$payload['coupon_code']=$this->voucher_code;
			$payload['coupon_expiry_date']=$cp->getExpiryDate();
		}
		
		$template=$this->getTemplate('CONFIRMATION');
		
		$this->logger->debug("template for confirmation".print_r($template,true));
		
		if(!empty($template['email']['msg']))
		{
			$subject=$template['email']['subject'];
			$msg=$this->deTemplatize($template['email']['msg'], $payload);
		
			$recipients=array();
		
			$recipients[]=$customer->email;
		
			$this->logger->info("sending confirmation email to ".implode(",",$recipients));
		
			foreach($recipients as $rec)
			{
				if(!empty($rec))
					Util::sendEmail($rec, $subject, $msg, $this->org_id);
			}
		
		}else
			$this->logger->info("skipping confirmation by email as template is empty");
		
		if(!empty($template['sms']))
		{
			$msg=$template['sms'];
			$msg=$this->deTemplatize($template['sms'], $payload);
				
			$recipients=array();
		
			$recipients[]=$customer->mobile;
		
			$this->logger->info("sending confirmation mobile to ".implode(",",$recipients));
		
			foreach($recipients as $rec)
			{
				if(!empty($rec))
					Util::sendSms($rec, $msg, $this->org_id);
			}
		
		}else
			$this->logger->info("skipping confirmation by sms as template is empty");
		
	}
}
