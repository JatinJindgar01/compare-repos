<?php

require_once 'apiModel/class.ApiChangeIdentifierRequestModelExtension.php';
require_once 'apiModel/class.ApiTransactionRequestModelExtension.php';
require_once 'apiModel/class.ApiGoodwillRequestModelExtension.php';
require_once 'apiController/ApiBaseController.php';
require_once 'apiHelper/Errors.php';
require_once 'cheetah/thrift/pointsengineservice.php';

require_once "helper/Util.php";
require_once "resource/coupon.php";

/**
 * Controller class for request resource
 * 
 * @author vimal
 */

class ApiRequestController extends ApiBaseController
{
	
	protected $goodwill;
	protected $change_identifier;
	private $transactionRequest;
	
	function __construct()
	{
		parent::__construct();
		$this->goodwill=new ApiGoodwillRequestModelExtension();
		$this->change_identifier=new ApiChangeIdentifierRequestModelExtension();
		$this->transactionRequest = new ApiTransactionRequestModelExtension();
		$this->settings=new MemberCareRequestSettingsModelExtension();
	}
	
	function getRequests($status,$type,$base_type,$user_id,$start_date,$end_date,$return_user_id=false,$start_id=null,$end_id=null,$limit=50,$start_limit=null)
	{

		if(!$limit)
			$limit=50;
		
		$goodwill_requests=$this->goodwill->getRequests($status, $type, $base_type, $user_id, $start_date, $end_date, $start_id, $end_id, $limit, $start_limit);
		
		$ret['count']=0;
		$ret['rows']=0;
		
		if($goodwill_requests['count']!=0)
		{
			$ret['count']=$goodwill_requests['count'];
			$ret['rows']=count($goodwill_requests['data']);
		}
		$ret['goodwill']=array();
		foreach($goodwill_requests['data'] as $request)
		{
			$req=array(
					'id'=>$request['request_id'],
					'type'=>$request['type'],
					'status'=>$request['status'],
					'base_type'=>$request['base_type'],
					'reason'=>$request['reason'],
					'comments'=>$request['comments'],
					'program_id'=>$request['program_id'],
					'customer'=>array(
							'firstname'=>$request['firstname'],
							'lastname'=>$request['lastname'],
							'email'=>$request['email'],
							'mobile'=>$request['mobile'],
							'external_id'=>$request['external_id'],
							'fraud_status'=>$request['fraud_status']==null?'NONE':$request['fraud_status'],
							'id'=>$request['user_id'],
							),
					'assoc_id'=>$request['assoc_id'],
					'approved_value'=>$request['approved_value'],
					'updated_comments'=>$request['updated_comments'],
					'added_on'=>date("c",strtotime($request['added_on'])),
					'last_action_on'=>strtotime($request['updated_on'])>0?date("c",strtotime($request['updated_on'])):'',
					'added_by'=>array(
							'code'=>$request['added_by_code'],
							'name'=>$request['added_by_name'],
							'till'=>array(
									'code'=>$request['added_by_till_code'],
									'name'=>$request['added_by_till_name']
									),
							),
					'updated_by'=>array(
							'code'=>$request['updated_by_code'],
							'name'=>$request['updated_by_name']
							),
			);
			if(!$return_user_id)
				unset($req['customer']['id']);
			$ret['goodwill'][]=$req;
		}
		
		$ci_requests=$this->change_identifier->getRequests($status, $type, $base_type, $user_id, $start_date, $end_date, $start_id, $end_id, $limit, $start_limit);
		if($ci_requests['count']!=0)
		{
			$ret['count']=$ci_requests['count'];
			$ret['rows']=count($ci_requests['data']);
		}
		$ret['change_identifier']=array();
		foreach($ci_requests['data'] as $request)
		{
			$req=array(
					'id'=>$request['request_id'],
					'type'=>$request['type'],
					'status'=>$request['status'],
					'base_type'=>$request['base_type'],
					'new_value'=>$request['new_value'],
					'old_value'=>$request['old_value'],
					'customer'=>array(
							'firstname'=>$request['firstname'],
							'lastname'=>$request['lastname'],
							'email'=>$request['email'],
							'mobile'=>$request['mobile'],
							'external_id'=>$request['external_id'],
							'fraud_status'=>$request['fraud_status']==null?'NONE':$request['fraud_status'],
							'id'=>$request['user_id']
					),
					'updated_comments'=>$request['updated_comments'],
					'added_on'=>date("c",strtotime($request['added_on'])),
					'last_action_on'=>strtotime($request['updated_on'])>0?date("c",strtotime($request['updated_on'])):'',
					'added_by'=>array(
							'id' => $request['added_by_id'],
							'code'=>$request['added_by_code'],
							'name'=>$request['added_by_name'],
							'till'=>array(
									'id' => $request['added_by_till_id'],
									'code'=>$request['added_by_till_code'],
									'name'=>$request['added_by_till_name']
									),
							),
					'updated_by'=>array(
							'id' => $request['updated_by_id'],
							'code'=>$request['updated_by_code'],
							'name'=>$request['updated_by_name']
							)
			);
			if(strtoupper($base_type)=='MERGE')
			{
				$req['target_customer']=array(
						'firstname'=>$request['survivor_firstname'],
						'lastname'=>$request['survivor_lastname'],
						'email'=>$request['survivor_email'],
						'mobile'=>$request['survivor_mobile'],
						'external_id'=>$request['survivor_external_id'],
						'id'=>$request['survivor_user_id'],
						);
				$req['job_status']=$request['job_status'];
			}
			if(strtoupper($base_type)=='MOBILE_REALLOC')
			{
				$req['old_customer']=array(
						'firstname'=>$request['oc_firstname'],
						'lastname'=>$request['oc_lastname'],
						'email'=>$request['oc_email'],
						'mobile'=>$request['oc_mobile'],
						'external_id'=>$request['oc_external_id'],
						'id'=>$request['oc_user_id'],
						);
			}
			if(!$return_user_id)
			{
				unset($req['customer']['id']);
				unset($re['target_customer']['id']);
			}
			$ret['change_identifier'][]=$req;
		}
		
		$retro_requests=$this->transactionRequest->getRequests($status, $type, $base_type, $user_id, $start_date, $end_date, $start_id, $end_id, $limit, $start_limit);
		if($retro_requests['count']!=0)
		{
			$ret['count']=$retro_requests['count'];
			$ret['rows']=count($retro_requests['data']);
		}
		$ret['retro']=array();
		
				
		foreach($retro_requests['data'] as $request)
		{
			$req=array(
					'id'=>$request['request_id'],
					'type'=>$request['type'],
					'status'=>$request['status'],
					'base_type'=>$request['base_type'],
					/*'new_value'=>$request['new_value'],*/
					'old_value'=>$request['transaction_id'], 
					'customer'=>array(
							'firstname'=>$request['firstname'],
							'lastname'=>$request['lastname'],
							'email'=>$request['email'],
							'mobile'=>$request['mobile'],
							'external_id'=>$request['external_id'],
							'fraud_status'=>$request['fraud_status']==null?'NONE':$request['fraud_status'],
							'id'=>$request['user_id'], 
							'registered_on' => $request['registered_on']
					),
					'transaction' => array(
							'number' => $request["bill_number"],
							'amount' => $request["bill_amount"],
							'billing_time' => $request["billing_time"],
							'till_id' => $request["billing_till_id"],
							'till_code' => $request["bill_till_code"],
							'till_name' => $request["bill_till_name"],
						),
					'reason'=>$request['reason'],	
					'updated_comments'=>$request['updated_comments'],
					'added_on'=>date("c",strtotime($request['added_on'])),
					'last_action_on' => strtotime($request['updated_on']) > 0 ? 
										date("c", strtotime($request ['updated_on'])) : '',
					'added_by'=>array(
							'code'=>$request['added_by_code'],
							'name'=>$request['added_by_name'],
							'till'=>array(
									'code'=>$request['added_by_till_code'],
									'name'=>$request['added_by_till_name']
									),
							),
					'updated_by'=>array(
							'code'=>$request['updated_by_code'],
							'name'=>$request['updated_by_name']
							)
			);

			if(!$return_user_id)
			{
				unset($req['customer']['id']);
			}
			$ret['retro'][]=$req;
		}
		
		if (strtoupper($base_type) == 'RETRO') {
			unset($ret ['goodwill']);
			unset($ret ['change_identifier']);
		}
		return $ret;
		
	}
	
	function addRequest($payload)
	{
	
		$type=$payload['type']=strtoupper($payload['type']);
		

               if(empty($payload['requested_on']) || !DateUtil::deserializeFrom8601($payload['requested_on']))
                        $payload['requested_on']=null;
               else if(DateUtil::deserializeFrom8601($payload['requested_on'])>(time()+SECONDS_OF_A_DAY))
                        throw new Exception('ERR_REQUEST_DATE_FUTURE');
			
		
		switch($type)
		{
			case 'CHANGE_IDENTIFIER':
				return $this->addChangeIdentifierRequest($payload);
				break;
			case 'GOODWILL':
				return $this->addGoodwillRequest($payload);
				break;
			case 'TRANSACTION_UPDATE':
				return $this->addRetroTransactionRequest($payload);
			default:
				throw new Exception('ERR_INVALID_REQUEST_TYPE');
		}
	}
	
	private function get_user($query_params,$optional=false)
	{
	
		$identifier=null;
		//find the identifier;
		foreach(array('email','mobile','external_id','id') as $id)
			if(isset($query_params[$id]) && !empty($query_params[$id]))
				$identifier=$id;

		
		if($identifier==null && !$optional)
			throw new Exception(ErrorMessage::$api['INVALID_INPUT'].", ".
					ErrorMessage::$customer['ERR_NO_IDENTIFIER'] ,
					ErrorCodes::$api['INVALID_INPUT']);
		else if(!$identifier)
			return false;
		
		$identifier_value=$query_params[$identifier];
		
		try{
			$user=UserProfile::getByData(array($identifier=>$identifier_value));
			$status = $user->load(true);
		}catch(Exception $e)
		{
			$user=false;
			if(!$optional)
				throw $e;
		}
			
		return $user;
	
	}
	
	private function addChangeIdentifierRequest($payload)
	{
		
		$base_type=strtoupper($payload['base_type']);
		
		$customer=$payload['customer'];
		
		$old_value=$payload['old_value'];
		$new_value=$payload['new_value'];
		
		$misc_info=$payload['misc_info'];
		
		$user_id=$payload['user_id'];
		
		if(isset($payload['client_auto_approve']))
		{
			if(strtolower($payload['client_auto_approve'])=="true")
				$client_auto_approve=true;
			else
				$client_auto_approve=false;
		}
		else
			$client_auto_approve=null;
		
		if($base_type=='MERGE')
		{
			$survivor=$this->get_user($payload['misc_info']['target_customer'],true);
			if(!$survivor)
				throw new Exception('ERR_CIR_INVALID_TARGET_USER');
			
			$old_value=$user_id;
			$new_value=$survivor->user_id;
		}
		
		if ($base_type=="MOBILE_REALLOC")
		{
				
			if(isset($payload['misc_info']['target_customer']))
			{
				$mr_target=$this->get_user($payload['misc_info']['target_customer'],true);
				if(!$mr_target)
					throw new Exception('ERR_CIR_INVALID_TARGET_USER');
					
				$new_value=$mr_target->user_id;
				
			}
			else
				$new_value=null;
		}

		$ret=$this->change_identifier->add($base_type,$user_id,$old_value,$new_value, $payload['requested_on'],$client_auto_approve);

        return array(
				'id'=>$ret['id'],
				'status'=>$ret['status'],
				'type'=>'CHANGE_IDENTIFIER',
				'base_type'=>$base_type,
				'customer'=>array(),
				'old_value'=>$ret['old_value'],
				'new_value'=>$ret['new_value']
				);
		
	}
	
	private function addGoodwillRequest($payload)
	{
		$base_type=strtoupper($payload['base_type']);
		
		$customer=$payload['customer'];
		
		$misc_info=$payload['misc_info'];
		
		$user_id=$payload['user_id'];
		
		$reason=$payload['reason'];
		
		$comments=$payload['comments'];

		$program_id=$payload['program_id'];

		$isOneStepChange = false;
		$incentiveType = $incentiveValue = null;
		if (isset($payload['is_one_step_change'])) {
			if ($payload['is_one_step_change']) {
					$isOneStepChange = true;

				switch(strtoupper($payload['base_type'])) {
					case 'COUPON':
						$incentiveType = 'series_id';
						$incentiveValue = $payload['series_id'];
						break;
					case 'POINTS':
						$incentiveType = 'points';
						$incentiveValue = $payload['points'];
						break;
					case 'TIER':
						$incentiveType = 'tier_name';
						$incentiveValue = $payload['tier_name'];
						break;
				}

				if (empty($incentiveValue)) 
					throw new Exception('ERR_GW_AUTO_APPROVAL_INVALID_INCENTIVE');
			} else {
				$isOneStepChange = false;
			}
		}

		$ret = $this -> goodwill -> add($user_id, $base_type, $reason, $comments, $payload['requested_on'], $isOneStepChange, $incentiveValue,$program_id);
		
		$response = array(
				'id'=>$ret['id'],
				'status'=>$ret['status'],
				'type'=>'GOODWILL',
				'base_type'=>$base_type,
				'customer'=>array(),
				'reason'=>$ret['reason'],
				'comments'=>$ret['comments']
				);		
		if ($isOneStepChange)
			$response [$incentiveType] = $incentiveValue;
		return $response;
	}
	
	private function addRetroTransactionRequest($payload){

		$customer    = $payload['customer'];
		$referenceId = $payload['reference_id'];
		$misc_info   = $payload['misc_info'];
		
		$this -> transactionRequest -> setHash($payload);
		$response = $this->transactionRequest->add();
		
		$requestHash = $this -> transactionRequest -> getHash(); 
		return array(
			'reference_id' => $referenceId, 
			'id'           => $requestHash['request_id'],
			'status'       => $requestHash['status'],
			'type'         => $requestHash['type'],
			'base_type'    => $requestHash['base_type'],
			'customer'     => $customer,
			'reason'       => $requestHash['reason'],
			'comments'     => $requestHash['comments'], 
			'misc_info'    => $misc_info
				);
	}

	
	function rejectRequest($payload)
	{
	
		$type=strtoupper($payload['type']);
	
		switch($type)
		{
			case 'CHANGE_IDENTIFIER':
				return $this->rejectChangeIdentifierRequest($payload);
				break;
			case 'GOODWILL':
				return $this->rejectGoodwillRequest($payload);
				break;
			case 'TRANSACTION_UPDATE':
				return $this->rejectTransactionUpdateRequest($payload);
			default:
				throw new Exception('ERR_INVALID_REQUEST_TYPE');
		}
	}
	
	function rejectChangeIdentifierRequest($payload)
	{
		$payload['base_type']=strtoupper($payload['base_type']);
		$id=$payload['id'];
		$updated_comments=$payload['updated_comments'];
		$this->change_identifier->reject($id, $updated_comments, strtoupper($payload['base_type']));
		$hash=$this->change_identifier->load($id);
		return array(
				'id'=>$id,
				'type'=>"CHANGE_IDENTIFIER",
				'base_type'=>$payload['base_type'],
				'status'=>$hash['status'],
				'old_value'=>$hash['old_value'],
				'new_value'=>$hash['new_value'],
				'status'=>$hash['status'],
				'updated_comments'=>$hash['updated_comments'],
		);
	}
	
	function rejectGoodwillRequest($payload)
	{
		$payload['base_type']=strtoupper($payload['base_type']);
		$id=$payload['id'];
		$this->goodwill->reject($id, $payload['updated_comments'],strtoupper($payload['base_type']),$payload['program_id']);
		$hash=$this->goodwill->load($id);
		return array(
				'id'=>$id,
				'type'=>$hash['type'],
				'base_type'=>$hash['base_type'],
				'status'=>$hash['status'],
				'assoc_id'=>$hash['assoc_id'],
				'approved_value'=>$hash['approved_value'],
				'updated_comments'=>$hash['updated_comments'],
		);
	}
	


	function rejectTransactionUpdateRequest($payload)
	{
		$id = $payload['id'];
		$this -> transactionRequest -> reject($id, $payload);
		
		$requestHash = $this -> transactionRequest -> getHash(); 
		return array(
			'id'           		=> $requestHash['request_id'],
			'status'       		=> $requestHash['status'],
			'type'         		=> $requestHash['type'],
			'base_type'    		=> $requestHash['base_type'],
			'reason'           	=> $requestHash['reason'],
			'old_value'    		=> $requestHash['transaction_id'],
			'new_value' 		=> '-1',
			'updated_comments'  => $requestHash['comments'], 
		);
	}
	
	
	function approveRequest($payload)
	{
	
		$payload['type']=$type=strtoupper($payload['type']);
		$payload['base_type']=strtoupper($payload['base_type']);
	
		switch($type)
		{
			case 'CHANGE_IDENTIFIER':
				return $this->approveChangeIdentifierRequest($payload);
				break;
			case 'GOODWILL':
				return $this->approveGoodwillRequest($payload);
				break;
			case 'TRANSACTION_UPDATE':
				return $this->approveRetroTransaction($payload);
			default:
				throw new Exception('ERR_INVALID_REQUEST_TYPE');
		}
	}
	
	private function approveChangeIdentifierRequest($payload)
	{
		$id=$payload['id'];
		$this->change_identifier->approve($id,strtoupper($payload['base_type']));
		$hash=$this->change_identifier->load($id);

        // ingest event here, since the change identifier has been approved
        $updateIdentifierEventAttributes = array();
        $updateIdentifierEventAttributes["subtype"] =$hash['base_type']; // done to convert this to long
        // $updateIdentifierEventAttributes["autoApprove"] = true;
        $updateIdentifierEventAttributes["userId"] =intval($this->user_id);
        $updateIdentifierEventAttributes["changestatus"] ="APPROVED";
        $updateIdentifierEventAttributes["oldValue"] =$hash['old_value'];
        $updateIdentifierEventAttributes["newValue"] =$hash['new_value'];
        $updateIdentifierEventAttributes["requestId"] =$payload['id'];
        EventIngestionHelper::ingestEventAsynchronously( intval($this->org_id), "updateidentifier",
            "Update identifier request from Intouch PHP API's", time(), $updateIdentifierEventAttributes);


        return array(
					'id'=>$id,
					'type'=>$hash['type'],
					'base_type'=>$hash['base_type'],
					'status'=>$hash['status'],
					'old_value'=>$hash['old_value'],
					'new_value'=>$hash['new_value'],
				);
	}
	
	private function approveGoodwillRequest($payload)
	{
		$id=$payload['id'];
		
		switch(strtoupper($payload['base_type']))
		{
			case 'COUPON':
				$approved_value=$payload['series_id'];
				break;
			case 'POINTS':
				$approved_value=$payload['points'];
				break;
			case 'TIER':
				$approved_value=$payload['tier_name'];
				break;
		}
		
		$this->goodwill->approve($id, $approved_value, $payload['updated_comments'], $payload['base_type'],$payload['program_id']);
		$hash=$this->goodwill->load($id);
		return array(
				'id'=>$id,
				'type'=>$hash['type'],
				'base_type'=>$hash['base_type'],
				'status'=>$hash['status'],
				'assoc_id'=>$hash['assoc_id'],
				'approved_value'=>$hash['approved_value'],
				'updated_comments'=>$hash['updated_comments'],
		);
	}
	
	private function approveRetroTransaction($payload){
		
		$id = $payload['id'];
		$this -> transactionRequest -> approve($id, $payload);
		
		$requestHash = $this -> transactionRequest -> getHash(); 
		return array(
			'id'           		=> $requestHash['request_id'],
			'status'       		=> $requestHash['status'],
			'type'         		=> $requestHash['type'],
			'base_type'    		=> $requestHash['base_type'],
			'reason'           	=> $requestHash['reason'],
			'old_value'    		=> $requestHash['transaction_id'],
			'new_value' 		=> $requestHash['loyalty_log_id'],
			'updated_comments'  => $requestHash['comments'], 
		);
	}
	
	private function translateAutoApprovalType($type)
	{
		
		$trans_map=array('CLIENT'=>'QUERY_PARAM','CONFIG'=>'CONFIG','CONFIG_DISABLED'=>'DISABLED','CLIENT_DISABLED'=>'QUERY_DISABLED');

		return array_search($type, $trans_map);
		
	}
	
	function getRequestLogs($type,$base_type,$start_date,$end_date,$status,$updated_by,$added_by,$request_id,$approval_type,$pr_customer_id,$sec_customer_id,$return_user_id,$is_one_step_change,$start_id,$end_id,$limit)
	{
		$type=strtoupper($type);
		$base_type=strtoupper($base_type);
		
		if(!strtotime($start_date) || !strtotime($end_date))
			throw new Exception('ERR_REQUEST_INVALID_DATE');
		
		switch($type)
		{
			case 'CHANGE_IDENTIFIER':
				
				$logs=$this->change_identifier->getRequestLogs($base_type, $start_date, $end_date, $status, $added_by, $updated_by, $request_id, $approval_type, $pr_customer_id, $sec_customer_id, $is_one_step_change,$start_id, $end_id,$limit);
				$ret['count']=$logs['count'];
                                if($logs['count']==0){
                                    $ret['rows']=$logs['count'];
                                }else{
                                    $ret['rows']=count($logs['logs']);
                                }
				$ret['change_identifier']=array();
				foreach($logs['logs'] as $request)
				{
					$req=array(
							'id'=>$request['request_id'],
							'customer'=>array(
									'firstname'=>$request['firstname'],
									'lastname'=>$request['lastname'],
									'email'=>$request['email'],
									'mobile'=>$request['mobile'],
									'external_id'=>$request['external_id'],
									'fraud_status'=>$request['fraud_status']==null?'NONE':$request['fraud_status'],
									'id'=>$request['user_id']
							),
							'type'=>$request['type'],
							'base_type'=>$request['base_type'],
							'status'=>$request['status'],
							'old_value'=>$request['old_value'],
							'new_value'=>$request['new_value'],
							'updated_comments'=>$request['updated_comments'],
							'one_step_change'=>$request['is_one_step_change']=="1"?"true":"false",
							'approval_type'=>$this->translateAutoApprovalType($request['approval_type']),
							'logs'=>array(
									'added_by'=>array(
											'till'=>array(
													'code'=>$request['added_by_till_code'],
													'name'=>$request['added_by_till_name'],
													),
											'store'=>array(
													'code'=>$request['added_by_code'],
													'name'=>$request['added_by_name'],
													),
											'time'=>date('c',strtotime($request['added_on'])),
											),
									'updated_by'=>array(
											'user'=>array(
													'name'=>!empty($request['updated_by_name'])?$request['updated_by_name']:$request['updated_by_oe_name'],
													'mobile'=>$request['updated_by_mobile'],
													'email'=>$request['updated_by_email'],
													),
											'ip'=>$request['update_ip_addr'],
											'time'=>strtotime($request['updated_on'])>0?date("c",strtotime($request['updated_on'])):"",
											),
									),
					);
					if(in_array($base_type, array('MERGE','MOBILE_REALLOC')))
					{
						$req['target_customer']=array(
								'firstname'=>$request['sec_firstname'],
								'lastname'=>$request['sec_lastname'],
								'email'=>$request['sec_email'],
								'mobile'=>$request['sec_mobile'],
								'external_id'=>$request['sec_external_id'],
								'id'=>$request['sec_user_id'],
								);
						if($base_type=='MERGE')
							$req['job_status']=$request['job_status'];
					}
					if(!$return_user_id)
					{
						unset($req['customer']['id']);
						unset($req['target_customer']['id']);
					}
					$ret['change_identifier'][]=$req;
				}
				
				break;
				
			case 'GOODWILL':

				
				$logs=$this->goodwill->getRequestLogs($base_type, $start_date, $end_date, $status, $added_by, $updated_by, $request_id, $pr_customer_id, $is_one_step_change, $start_id, $end_id,$limit);
				$ret['count']=$logs['count'];
                                if($logs['count']==0){
                                    $ret['rows']=$logs['count'];
                                }else{
                                    $ret['rows']=count($logs['logs']);
                                }
				$ret['goodwill']=array();
                                //now get voucher_code for voucher_id by calling luci
                                $couponIds = array();
                                foreach($logs['logs'] as $request)
				{
                                    $voucher_id = $request['voucher_id'];
                                    if($voucher_id > 0){
                                        $couponIds[] = $voucher_id;
                                    }
                                }
                                $coupon_resource = new CouponResource();
                                $coupon_details_list = array();
                                $isLuciFlowEnabled = Util::isLuciFlowEnabled();
                                if ($isLuciFlowEnabled && sizeof($couponIds)>0) {
                                    $coupon_details_list = $coupon_resource->newGet('coupon_id', $couponIds, false, 'v2', null);
                                }
                                foreach ($logs['logs'] as $request){
                                    if( strtolower($coupon_details_list['status']['success'])=='false'){
                                        $request['voucher_code'] = null;
                                    }else{
                                        $looked_coupon_count_with_matching_id=0;
                                        foreach ($coupon_details_list['coupons']['coupon'] as $coupon_details){
                                            if($coupon_details['id'] == $request['voucher_id']){
                                                $looked_coupon_count_with_matching_id += 1;
                                                $request['voucher_code'] = $coupon_details['code'];
                                                break;
                                            }
                                        }
                                        if($looked_coupon_count_with_matching_id == 0){
                                            $request['voucher_code'] = null;
                                        }
                                    }
                                }
                                
				foreach($logs['logs'] as $request)
				{   
					$req=array(
							'id'=>$request['request_id'],
							'customer'=>array(
									'firstname'=>$request['firstname'],
									'lastname'=>$request['lastname'],
									'email'=>$request['email'],
									'mobile'=>$request['mobile'],
									'external_id'=>$request['external_id'],
									'fraud_status'=>$request['fraud_status']==null?'NONE':$request['fraud_status'],
									'id'=>$request['user_id']
							),
							'type'=>$request['type'],
							'base_type'=>$request['base_type'],
							'status'=>$request['status'],
							'reason'=>$request['reason'],
							'comments'=>$request['comments'],
							'updated_comments'=>$request['updated_comments'],
							'one_step_change'=>$request['is_one_step_change']=="1"?"true":"false",
							'points'=>$request['approved_value'],		
                                                        'coupon'=>$request['voucher_code'],
							'logs'=>array(
									'added_by'=>array(
											'till'=>array(
													'code'=>$request['added_by_till_code'],
													'name'=>$request['added_by_till_name'],
													),
											'store'=>array(
													'code'=>$request['added_by_code'],
													'name'=>$request['added_by_name'],
													),
											'time'=>date('c',strtotime($request['added_on'])),
											),
									'updated_by'=>array(
											'user'=>array(
													'name'=>!empty($request['updated_by_name'])?$request['updated_by_name']:$request['updated_by_oe_name'],
													'mobile'=>$request['updated_by_mobile'],
													'email'=>$request['updated_by_email'],
													),
											'ip'=>$request['update_ip_addr'],
											'time'=>strtotime($request['updated_on'])>0?date("c",strtotime($request['updated_on'])):"",
											),
									),
					);
					if(!$return_user_id)
					{
						unset($req['customer']['id']);
						unset($req['target_customer']['id']);
					}
					
					switch($request['base_type'])
					{
						case 'COUPON':
							unset($req['points']);
							break;
						case 'POINTS':
							unset($req['coupon']);
							break;
					}
					
					$ret['goodwill'][]=$req;
				}
				
				break;
				
			case 'TRANSACTION_UPDATE':

				$logs = $this -> transactionRequest -> getRequestLogs($base_type, $start_date, $end_date, $status, 
					$added_by, $updated_by, $request_id, $pr_customer_id, $is_one_step_change, $start_id, $end_id, $limit);
				$ret['count'] = $logs['count'];
				if($logs['count']==0){
                                    $ret['rows']=$logs['count'];
                                }else{
                                    $ret['rows']=count($logs['logs']);
                                }
				$ret['change_identifier'] = array();
				foreach ($logs['logs'] as $request) {
					$req = array(
						'id' 				=> $request['request_id'],
						'customer' 			=> array(
								'id' 			=> $request['user_id'], 
								'firstname' 	=> $request['firstname'],
								'lastname' 		=> $request['lastname'],
								'email' 		=> $request['email'],
								'mobile' 		=> $request['mobile'],
								'external_id' 	=> $request['external_id'],
								'registered_on' => $request['registered_on'], 
								'fraud_status' 	=> empty($request['fraud_status']) ? 'NONE' : $request['fraud_status']
						),
						'type' 				=> $request['type'],
						'base_type' 		=> $request['base_type'],
						'status' 			=> $request['status'],
						'old_value' 		=> $request['old_value'],
						'new_value' 		=> $request['new_value'],
						'transaction_number'=> $request['bill_number'], 
						'updated_comments' 	=> $request['updated_comments'],
						'one_step_change' 	=> ($request['is_one_step_change']) ? "true" : "false",
						'logs'				=> array(
							'added_by' 	=> array(
								'till' 	=> array(
										'code' => $request['added_by_till_code'],
										'name' => $request['added_by_till_name'],
								),
								'store' => array(
										'code' => $request['added_by_code'],
										'name' => $request['added_by_name'],
								),
								'time' 	=> date('c', strtotime($request['added_on'])),
							),
							'updated_by' => array(
								'user'	=> array(
									'code' 		=> (! empty($request['updated_by_au_code'])) ? 
														$request['updated_by_au_code']: $request['updated_by_oe_code'],
									'name' 		=> (! empty($request['updated_by_au_name'])) ? 
														$request['updated_by_au_name']: $request['updated_by_oe_name'],
									'mobile' 	=> $request['updated_by_au_mobile'],
									'email' 	=> $request['updated_by_au_email']
								),
								'ip'	=> $request['update_ip_addr'],
								'time'	=> strtotime($request['updated_on']) > 0 ? date("c", strtotime($request['updated_on'])) : "",
							),
						)
					);
					$ret['retro'][] = $req;
				}				

				break;
			default:
				throw new Exception('ERR_INVALID_REQUEST_TYPE');
		}
		
		return $ret;
		
	}
	
}
