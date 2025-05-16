<?php

 require_once "resource.php";
 require_once "apiController/ApiRequestController.php";

/**
 * All request related endpoints
 * includes
 * 	+ change identifiers
 *  + goodwill
 *
 * @author vimal
 */

class RequestResource extends BaseResource{

	private $controller;

	function __construct()
	{
		parent::__construct();
		$this->controller=new ApiRequestController();
	}


	public function process($version, $method, $data, $query_params, $http_method)
	{

		if(!$this->checkVersion($version))
		{
			$this->logger->error("Unsupported Version : $version");
			$e = new UnsupportedVersionException(ErrorMessage::$api['UNSUPPORTED_VERSION'], ErrorCodes::$api['UNSUPPORTED_VERSION']);
			throw $e;
		}

		if(!$this->checkMethod($method)){
			$this->logger->error("Unsupported Method: $method");
			$e = new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
			throw $e;
		}

		$result = array();
		try{

			switch(strtolower($method)){
				
				case 'get':
					$result=$this->get($version,$method,$data,$query_params,$http_method);
					return $result;
					
				case 'add':
					$result=$this->add($version,$method,$data,$query_params,$http_method);
					return $result;
					
				case 'reject':
					$result=$this->reject($version,$method,$data,$query_params,$http_method);
					return $result;
					
				case 'approve':
					$result=$this->approve($version,$method,$data,$query_params,$http_method);
					return $result;
					
				case 'logs':
					$result=$this->logs($version,$method,$data,$query_params,$http_method);
					return $result;
							
				default :
					$this->logger->error("WTF with the integration!");

			}
			
		}catch(Exception $e){ 
			$this->logger->error("Caught an unexpected exception, Code:" . $e->getCode(). " Message: " . $e->getMessage());
			throw $e;
		}

		return $result;
	}


	public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1', 'v1.1'))){
			return true;
		}
		return false;
	}

	public function checkMethod($method)
	{
		$available_endpoints=array('get','add','approve','reject','logs');
		
		if(in_array(strtolower($method), $available_endpoints))
			return true;
		return false;
	}
	
	private function get_api_status($count,$success_count)
	{
		
		if($count==$success_count)
			$api_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['SUCCESS'],
					'message'=>ErrorMessage::$api['SUCCESS']
			);
		elseif($success_count==0)
		$api_status=array(
				'success'=>'false',
				'code'=>ErrorCodes::$api['FAIL'],
				'message'=>ErrorMessage::$api['FAIL']
		);
		else
			$api_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['PARTIAL_SUCCESS'],
					'message'=>ErrorMessage::$api['PARTIAL_SUCCESS']
			);
		
		return $api_status;
	}

	private function get_query_params($query_params,$keys)
	{
		
		$clean_query_params=array();
		
		foreach($query_params as $key=>$param)
			$clean_query_params[strtolower($key)]=$param;
		
		$query_params=$clean_query_params;
		
		foreach($keys as $key)
		{
			if(isset($query_params[strtolower($key)]))
				$ret[$key]=$query_params[strtolower($key)];
			else
				$ret[$key]=null;
		}
		
		return $ret;
	}
	
	private function get_user($query_params,$optional=false)
	{

		
		$identifier=null;
		//find the identifier;
		foreach(array('id','mobile','email','external_id','id') as $id)
			if(isset($query_params[$id]) && !empty($query_params[$id]))
				$identifier=$id;
		
		if(!$identifier && !$optional)
			throw new Exception('ERR_NO_IDENTIFIER');
		else if(!$identifier)
			return false;
		
		$identifier_value=$query_params[$identifier];

		$user=UserProfile::getByData(array($identifier=>$identifier_value));
		$status = $user->load(true);
		
		return $user;
		
	}
	
	protected function get($version,$method,$data,$query_params,$http_method)
	{
		
		$query_param_keys=array('start_date','end_date','user_id','email','mobile','external_id','status','type','base_type','source','start_id','end_id','limit',"start_limit");
		
		$query=$this->get_query_params($query_params, $query_param_keys);

		extract($query,EXTR_SKIP);
		
		$resp=array();
		
		$return_user_id=false;
		if(isset($user_id) && strtolower($user_id)=='true')
			$return_user_id=true;
		
		try{
			
			$user=$this->get_user($query_params,true);
			$user_id=null;
			if($user)
				$user_id=$user->user_id;
			
			$requests=$this->controller->getRequests($status,$type,$base_type,$user_id,$start_date,$end_date,$return_user_id,$start_id,$end_id,$limit,$start_limit);
			
			$status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['SUCCESS'],
					'message'=>ErrorMessage::$api['SUCCESS']
					);
			
			$resp=$requests;
			$success_count=1;
			
			$item_status=array(
							'success'=>'true',
							'code'=>ErrorCodes::$request['ERR_REQUEST_GET_SUCCESS'],
							'message'=>ErrorMessage::$request['ERR_REQUEST_GET_SUCCESS']
							);
			
			
		}catch(Exception $e)
		{
			
			$this->logger->error("exception occurred: $e");
			
			$success_count=0;
			
			$item_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$request[$e->getMessage()],
					'message'=>ErrorMessage::$request[$e->getMessage()]
			);
				
			$status=array(
					'success'=>'false',
					'code'=>ErrorCodes::$api['FAIL'],
					'message'=>ErrorMessage::$api['FAIL']
					);
		}
		
		$resp['item_status']=$item_status;
		
		return array(
				'status'=>$this->get_api_status(1, $success_count),
				'requests'=>$resp,
				);
		
		
	}
	
	protected function add($version,$method,$data,$query_params,$http_method)
	{
		
		$payload_set=$data['root']['request'];
		
		$count=count($payload_set);
		$success_count=0;
		
		$resp=array();
		foreach($payload_set as $payload)
		{
			
			// need a new instance everytime!!! you know why! remember the transaction/add..
			$this->controller=new ApiRequestController();
			$single_resp=array_merge(array('id'=>null),$payload);
			
			try{
                                if(array_key_exists("id", $payload['customer']) && !is_numeric($payload['customer']["id"])) {
                                    $payload['customer']["id"] = NULL;
                                }
				$user=$this->get_user($payload['customer']);
				$payload['user_id']=$user->user_id;
				
				if($user->is_merged && strtoupper($payload['type'])=="CHANGE_IDENTIFIER")
				{
					$identifier=strtolower($payload['base_type']);
					if(isset($user->$identifier))
						$payload['old_value']=$user->$identifier;
				}
				
				if (strtoupper($payload["type"]) == "TRANSACTION_UPDATE" || 
					strtoupper($payload["type"]) == "GOODWILL") {
					if($query_params["client_auto_approve"] == "true"){
						$payload["is_one_step_change"] = 1;
					}else{
						$payload["is_one_step_change"] = 0;
					}
				}
				$ret=$this->controller->addRequest($payload);
				
				$single_resp=array_merge(array('reference_id'=>$payload['reference_id']),$ret);
				$single_resp['customer']=array(
						'firstname'=>$user->first_name,
						'lastname'=>$user->last_name,
						'email'=>$user->email,
						'mobile'=>$user->mobile,
						'external_id'=>$user->external_id,
						'id'=>$user->user_id
						);
				
				if(!isset($query_params['user_id']) || $query_params['user_id']!="true")
					unset($single_resp['customer']['id']);
				
				$success_count++;
				
				$status_code='ERR_CIR_ADD_SUCCESS';
				if(strtoupper($payload['type'])=='GOODWILL')
					$status_code='ERR_GW_ADD_SUCCESS';
				if (strtoupper($payload['type']) == 'TRANSACTION_UPDATE') {
					if (strtoupper($payload['base_type']) == 'RETRO') {
						$status_code = 'ERR_RETRO_TXN_ADD_SUCCESS';
					}
				}
				
				$item_status=array(
						'success'=>'true',
						'code'=>ErrorCodes::$request[$status_code],
						'message'=>ErrorMessage::$request[$status_code],
						);
				
			}catch(Exception $e)
			{
				
				$this->logger->error("exception occurred: $e");
				
				$item_status=array(
						'success'=>'false',
						'code'=>ErrorCodes::$request[$e->getMessage()],
						'message'=>ErrorMessage::$request[$e->getMessage()]
						);
				
			}
			
			$single_resp['item_status']=$item_status;
			
			$resp[]=$single_resp;
		
		}
		
		return array(
				'status'=>$this->get_api_status($count, $success_count),
				'requests'=>array('request'=>$resp)
				);
		
	}
	
	protected function approve($version,$method,$data,$query_params,$http_method)
	{
		
		$payload_set=$data['root']['request'];
		
		$count=count($payload_set);
		$success_count=0;
		
		$resp=array();
		
		foreach($payload_set as $payload)
		{
			$single_resp=$payload;
			
			try{
				
					$single_resp=$this->controller->approveRequest($payload);
				
					$status_code='ERR_REQUEST_UPDATE_SUCCESS';
					if (strtoupper($payload['type']) == 'TRANSACTION_UPDATE') {
						if (strtoupper($payload['base_type']) == 'RETRO') {
							$status_code = 'ERR_RETRO_TXN_APPROVE_SUCCESS';
						}
					}
					
					$item_status=array(
									'success'=>'true',
									'code'=>ErrorCodes::$request[$status_code],
									'message'=>ErrorMessage::$request[$status_code],
								);
				
					$success_count++;
					
				}catch(Exception $e)
				{
					
					$this->logger->error("exception occurred: $e");
						
					$item_status=array(
							'success'=>'false',
							'code'=>ErrorCodes::$request[$e->getMessage()],
							'message'=>ErrorMessage::$request[$e->getMessage()]
					);
				
				}
					
				$single_resp['item_status']=$item_status;
					
				$resp[]=$single_resp;
				
				
		}
		
		return array(
				'status'=>$this->get_api_status($count, $success_count),
				'requests'=>array('request'=>$resp)
		);
		
	}
	
	protected function reject($version,$method,$data,$query_params,$http_method)
	{
		
		$payload_set=$data['root']['request'];
		
		$count=count($payload_set);
		$success_count=0;
		
		$resp=array();
		
		foreach($payload_set as $payload)
		{
			$single_resp=$payload;
				
			try{
		
				$single_resp=$this->controller->rejectRequest($payload);
		
				$status_code='ERR_REQUEST_UPDATE_SUCCESS';
				if (strtoupper($payload['type']) == 'TRANSACTION_UPDATE') {
					if (strtoupper($payload['base_type']) == 'RETRO') {
						$status_code = 'ERR_RETRO_TXN_REJECT_SUCCESS';
					}
				}
					
				$item_status=array(
						'success'=>'true',
						'code'=>ErrorCodes::$request[$status_code],
						'message'=>ErrorMessage::$request[$status_code],
				);
		
				$success_count++;
					
			}catch(Exception $e)
			{
		
				$this->logger->error("exception occurred: $e");

				$item_status=array(
						'success'=>'false',
						'code'=>ErrorCodes::$request[$e->getMessage()],
						'message'=>ErrorMessage::$request[$e->getMessage()]
				);
		
			}
				
			$single_resp['item_status']=$item_status;
				
			$resp[]=$single_resp;
		
		
		}
		
		return array(
				'status'=>$this->get_api_status($count, $success_count),
				'requests'=>array('request'=>$resp)
		);
		
		
	}
	
	protected function logs($version,$method,$data,$query_params,$http_method)
	{
		
		$query_param_keys=array('is_one_step_change','start_date','end_date','customer_id','user_id','email','mobile','external_id','status','type','base_type','updated_by','added_by','request_id','approval_type','sec_email','sec_mobile','sec_external_id','sec_customer_id','start_id','end_id','limit');
		
		$query=$this->get_query_params($query_params, $query_param_keys);

		extract($query,EXTR_SKIP);
		
		$resp=array();
		
		$return_user_id=false;
		if((isset($user_id) && strtolower($user_id)=='true') || isset($customer_id) || isset($sec_customer_id))
			$return_user_id=true;
		
		try{
			
			
			$pro_customer=$this->get_user(array('email'=>$email,'mobile'=>$mobile,'external_id'=>$external_id,'id'=>$customer_id),true);
			if(!$pro_customer)
				$pr_customer['id']=null;
			else 
				$pr_customer['id']=$pro_customer->user_id;
			
			$seco_customer=$this->get_user(array('email'=>$sec_email,'mobile'=>$sec_mobile,'external_id'=>$sec_external_id,'id'=>$sec_customer_id),true);
			if(!$seco_customer)
				$sc_customer['id']=null;
			else
				$sc_customer['id']=$seco_customer->user_id;
				
			$requests=$this->controller->getRequestLogs($type,$base_type,$start_date,$end_date,$status,$updated_by,$added_by,$request_id,$approval_type,$pr_customer['id'],$sc_customer['id'],$return_user_id,$is_one_step_change,$start_id,$end_id,$limit);
				
			$status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['SUCCESS'],
					'message'=>ErrorMessage::$api['SUCCESS']
			);
				
			$resp=$requests;
			$success_count=1;
				
			$item_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$request['ERR_REQUEST_GET_SUCCESS'],
					'message'=>ErrorMessage::$request['ERR_REQUEST_GET_SUCCESS']
			);
				
				
		}catch(Exception $e)
		{
					
			$this->logger->error("req resource: exception occurred: $e");
					
			$success_count=0;
				
			$item_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$request[$e->getMessage()],
					'message'=>ErrorMessage::$request[$e->getMessage()]
			);
			
			$status=array(
					'success'=>'false',
					'code'=>ErrorCodes::$api['FAIL'],
					'message'=>ErrorMessage::$api['FAIL']
			);
		}
			
		$resp['item_status']=$item_status;
			
		return array(
				'status'=>$this->get_api_status(1, $success_count),
				'requests'=>$resp,
		);
			
	}
	
}
