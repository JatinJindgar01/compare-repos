<?php

require_once "resource.php";
require_once "apiController/ApiAssociateController.php";
define("CAP_ASSOC_CREDENTIALS_HEADER", "X-Cap-Assoc-Credentials");

/**
 * Handles all the associate related 
 * work and api's 
 *
 * @author pigol
 */

class AssociateResource extends BaseResource{

	function __construct()
	{
		parent::__construct();
	}


	public function process($version, $method, $data, $query_params, $http_method)
	{
		//$this->logger->("POST data: "+$data);
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

				case 'login' :
						
					$result = $this->login( $query_params );
					break;
								
				case 'update':
					$result = $this->update($data, $query_params);
					break;
					
				case 'activity':
					$result = $this->activity($data, $query_params);
					break;
					
				case 'tasks':
					$result = $this->tasks($data, $query_params);
					break;
					
				case 'get':
					$result = $this->get($query_params);
					break;
					
				default :
					$this->logger->error("Should not be reaching here");
						
			}
		}catch(Exception $e){ //We will be catching a hell lot of exceptions as this stage
			$this->logger->error("Caught an unexpected exception, Code:" . $e->getCode()
			. " Message: " . $e->getMessage()
			);
			throw $e;
		}
			
		return $result;
	}

	private function login( $query_params)
	{
		global $gbl_item_status_codes;
		$_HEADERS = apache_request_headers();
		
		$api_status_code = "SUCCESS";
		$item_status_code = "ERR_ASSOC_LOGIN_SUCCESS";
		
		$associate_controller = new ApiAssociateController();
		try
		{
			
			if(!isset($query_params['user']))
				throw new Exception("ERR_ASSOC_LOGIN_NO_IDENTIFIER");
			if(!isset($_HEADERS[CAP_ASSOC_CREDENTIALS_HEADER]))
				throw new Exception("ERR_ASSOC_LOGIN_NO_ASSOC_CREDEINTIAL");
			
			$username = $query_params['user'];
			$password = $_HEADERS[CAP_ASSOC_CREDENTIALS_HEADER];
			
			$this->logger->debug("trying to login with username: $username, password $password");
			
			$result = $associate_controller->login($username, $password);
			
			$associate = array();
			$associate['id'] = $result['id'];
			$associate['code'] = $result['associate_code'];
			$associate['firstname'] = $result['firstname'];
			$associate['lastname'] = $result['lastname'];
			$associate['mobile'] = $result['mobile'];
			$associate['email'] = $result['email'];
			$associate['store_name'] = $result['store_code'];
			$associate['store_id'] = $result['store_id'];
			$associate['last_login'] = $result['last_login'];

		}
		catch(Exception $e)
		{
			$this->logger->error("AssociateReqource::login()  Error: ".$e->getMessage());
			$associate = array(
								"id" => -1,
								"code" => "",
								"firstname" => "",
								"lastname" => "",
								"mobile" => "",
								"email" => "",
								"store_name" => "",
								"store_id" => "",
								"last_login" => ""
							);
			$item_status_code = $e->getMessage();
			$api_status_code = "FAIL";
		}
		
		$item_status = array(
						"success" => ErrorCodes::$associate[$item_status_code] == 
							ErrorCodes::$associate['ERR_ASSOC_LOGIN_SUCCESS'] ? true : false,
						"code" => ErrorCodes::$associate[$item_status_code],
						"message" => ErrorMessage::$associate[$item_status_code]				
					);
		
		$api_status = array(
						"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api['SUCCESS'] ? true : false,
						"code" => ErrorCodes::$api[$api_status_code],
						"message" => ErrorMessage::$api[$api_status_code]				
					);
		
		
		$associate['item_status'] = $item_status;
		$gbl_item_status_codes = $item_status['code'];
		return array(
					"status" => $api_status,
					"associate" => $associate
				);
	}
	
	private function update($data, $query_params)
	{
		global $gbl_item_status_codes;
		$_HEADERS = apache_request_headers();
		$api_status_code = "SUCCESS";
		$item_status_code = "ERR_ASSOC_UPDATE_SUCCESS";
		$associate_controller = new ApiAssociateController();
		try 
		{
			
			if(!isset($query_params['user']))
				throw new Exception("ERR_ASSOC_LOGIN_NO_IDENTIFIER");
			if(!isset($_HEADERS[CAP_ASSOC_CREDENTIALS_HEADER]))
				throw new Exception("ERR_ASSOC_LOGIN_NO_ASSOC_CREDEINTIAL");
			
			$username = $query_params['user'];
			$password = $_HEADERS[CAP_ASSOC_CREDENTIALS_HEADER];
			
			$this->logger->debug("trying to Fetch Info with username: $username, password $password");
				
			$result = $associate_controller->update( $username , $password ,$data);
			$associate = array();
			$associate['id'] = $result['id'];
			$associate['code'] = $result['code'];
			$associate['firstname'] = $result['firstname'];
			$associate['lastname'] = $result['lastname'];
			$associate['mobile'] = $result['mobile'];
			$associate['email'] = $result['email'];
			$associate['store_name'] = $result['store_code'];
			$associate['store_id'] = $result['store_id'];
			$associate['is_active'] = $result['is_active'];	
		}
		catch(Exception $e)
		{
			$this->logger->error("AssociateReqource::Update()  Error: ".$e->getMessage());
			$associate = array(
					"id" => -1,
					"code" => "",
					"firstname" => "",
					"lastname" => "",
					"mobile" => "",
					"email" => "",
					"store_name" => "",
					"store_id" => "",
					"last_login" => ""
			);
			$item_status_code = $e->getMessage();
			$api_status_code = "FAIL";
		}
		
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api['SUCCESS'] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		$item_status = array(
				"success" => ErrorCodes::$associate[$item_status_code] ==
				ErrorCodes::$associate['ERR_ASSOC_UPDATE_SUCCESS'] ? true : false,
				"code" => ErrorCodes::$associate[$item_status_code],
				"message" => ErrorMessage::$associate[$item_status_code]
		);
		
		/*$associate = array(
						"id" => "554",
						"code" => "CodeCheck123",
						"firstname" => "Kartik",
						"lastname" => "Gosiya",
						"mobile" => "8867702348",
						"email" => "gosiya.kartik@gmail.com",
						"store_name" => "SS my store",
						"store_id" => "146",
						"item_status" => $item_status
				);*/
		
		$associate['item_status'] = $item_status;
		$gbl_item_status_codes = $item_status['code']; 
		return array(
					"status" => $api_status, 
					"associate" => $associate
				);
	}
	
	private function activity($data, $query_params)
	{
		global $gbl_item_status_codes;
		$_HEADERS = apache_request_headers();
		$api_status_code = "SUCCESS";
		$item_status_code = "ERR_ASSOC_ACTIVITY_SUCCESS";
		
		$associate_controller = new ApiAssociateController();
		try
		{
				
			if(!isset($query_params['user']))
				throw new Exception("ERR_ASSOC_LOGIN_NO_IDENTIFIER");
			if(!isset($_HEADERS[CAP_ASSOC_CREDENTIALS_HEADER]))
				throw new Exception("ERR_ASSOC_LOGIN_NO_ASSOC_CREDEINTIAL");
				
			$username = $query_params['user'];
			$password = $_HEADERS[CAP_ASSOC_CREDENTIALS_HEADER];
				
			$this->logger->debug("trying to Fetch Info with username: $username, password $password");
			
			$type = null;
			$store_id = null;
			$start_date = null;
			$end_date = null;
			$start_id = null;
			$end_id = null;
			$limit = null;
			
			if( isset($query_params['type']) )
				$type = $query_params['type'];
			if( isset($query_params['store_id']) )
				$store_id = $query_params['store_id'];
			if( isset($query_params['start_date']) )
				$start_date = $query_params['start_date'];
			if( isset($query_params['end_date']) )
				$end_date = $query_params['end_date'];
			if( isset($query_params['start_id']) )
				$start_id = $query_params['start_id'];
			if( isset($query_params['end_id']) )
				$end_id = $query_params['end_id'];
			if( isset($query_params['limit']) )
				$limit = $query_params['limit'];
			
			$associate = $associate_controller->getActivities($username, $password, 
										$type, $store_id, 
										$start_date, $end_date, 
										$start_id, $end_id, $limit);
			
		}
		catch(Exception $e)
		{
			$item_status_code = $e->getMessage();
			$associate = array(
				"id" => -1,
				"code" => ""
			);
			$api_status_code = "FAIL";
		}
		
		
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api['SUCCESS'] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		$item_status = array(
				"success" => ErrorCodes::$associate[$item_status_code] ==
				ErrorCodes::$associate['ERR_ASSOC_ACTIVITY_SUCCESS'] ? true : false,
				"code" => ErrorCodes::$associate[$item_status_code],
				"message" => ErrorMessage::$associate[$item_status_code]
		);
		
		$associate['item_status'] = $item_status;		
		$gbl_item_status_codes = $item_status['code'];
		return array(
				"status" => $api_status,
				"associate" => $associate
		);
	}
	
	public function tasks($data, $query_params)
	{
		/*$associate_controller = new ApiAssociateController();
		$result = $associate_controller->getAssociatesByStoreId($query_params['store_id'], $query_params['start_id'] , $query_params['batch_size']);
		$associates = array();
		$associates['associate'] = $result; 
		if($result)
			return $associates;
		else*/
			return array();
	}
	
	//returns All Associates of the organization.
	public function get($query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';
		
		$associate_controller = new ApiAssociateController();
		
		$start_id = 0;
		$batch_size = 0;
		
		if(isset($query_params['id']) && !empty($query_params['id']))
		{
			$ids = explode( ",", $query_params['id'] );
		}
		else 
		{
			$this->logger->error("id param is not passed");
			$api_status_code = 'INVALID_INPUT';
			throw new Exception(ErrorMessage::$api[$api_status_code], ErrorCodes::$api[$api_status_code]);
		}
		
		$associates = $associate_controller->getAssociateDetailsByIds($ids);
		
		if($associates)
		{
			//$associates = array( "associate" => $associates );
			$ret_associates = array();
			foreach($ids as $id)
			{
				if(isset($associates[$id]))
				{
					$associate = $associates[$id];
					$associate['item_status'] = 
								array(
									"success" => true,
									"code" => ErrorCodes::$associate['ERR_ASSOC_GET_SUCCESS'],
									"message" => ErrorMessage::$associate['ERR_ASSOC_GET_SUCCESS']
								);
				}
				else 
				{
					$api_status_code = "PARTIAL_SUCCESS";
					$associate = array(
							"id" => $id,
							"type" => "",
							"code" => "",
							"firstname" => "",
							"lastname" => "",
							"mobile" => "",
							"email" => "",
							"store_id" => "",
							"store_name" => "",
							"username"=> "",
							"item_status" =>  array(
									"success" => 	false,
									"code" => ErrorCodes::$associate['ERR_ASSOC_GET_FAIL'],
									"message" => ErrorMessage::$associate['ERR_ASSOC_GET_FAIL']
							)
					);
				}
				$arr_item_status_codes[] = $associate['item_status']['code'];
				array_push($ret_associates, $associate);
			}
			$associates = $ret_associates;
		}
		else
		{
			$associates = array();
			foreach($ids as $id)
			{
				$associate = array(
							"id" => $id, 
							"type" => "", 
							"code" => "", 
							"firstname" => "",
							"lastname" => "", 
							"mobile" => "", 
							"email" => "", 
							"store_id" => "", 
							"store_name" => "", 
							"username"=> "",
							"item_status" =>  array(
								"success" => 	false,
								"code" => ErrorCodes::$associate['ERR_ASSOC_GET_FAIL'],
								"message" => ErrorMessage::$associate['ERR_ASSOC_GET_FAIL']
								)
						);
				$arr_item_status_codes[] = $associate['item_status']['code'];
				array_push($associates, $associate);
				$api_status_code = "FAIL";
			}
		}
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
							'success' => ErrorCodes::$api[$api_status_code] ==	
											ErrorCodes::$api['SUCCESS'] ? true : false,
							'code' => ErrorCodes::$api[$api_status_code],
							'message' => ErrorMessage::$api[$api_status_code]
						);
		return array(
					'status' => $api_status,
					'associates' => $associates
				);
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
		if(in_array(strtolower($method), array( 'login', 'update', 'activity', 'tasks', 'get' )))
		{
			return true;
		}
		return false;
	}

}
