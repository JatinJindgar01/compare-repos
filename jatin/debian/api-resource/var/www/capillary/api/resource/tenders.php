<?php

require_once "resource.php";
include_once 'apiHelper/resourceFormatter/ResourceFormatterFactory.php';
/**
 * Handles all tenders related api calls.
 * 	- Get tenders
 *  - Get tender attributes
 *  - Save new tenders
 *
 * @author cj
 */

class TendersResource extends BaseResource{

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

				case 'get' :
					$includeAttributes = in_array(strtolower($query_params["attributes"]), array(1, "1", true, "true"), true) ? true : false;
					// for getting the tenders
 					if($query_params["attribute_name"] && $query_params["name"]
 							&& !( isset($query_params["attributes"]) && !$includeAttributes ))
 					{
 						$query_params['tender_name'] = $query_params["name"]; 
 						$query_params["name"] = $query_params["attribute_name"];
 						$result = $this->getTenderAttributes($query_params );
 					}
 					else//if (!$query_params["attribute_name"])
						$result = $this->getTenders($query_params);
// 					else
// 					{
// 						include_once 'exceptions/ApiException.php';
// 						throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
// 					}
					break;
				
				case 'attributes' :
						
					$result = $this->getTenderAttributes($query_params );
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

	/**
	 * Returns the tender details based on the query params 
	 * @param array $query_params
	 */
	private function getTenders( $query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';
		
		$includeAttributes = in_array(strtolower($query_params["attributes"]), array(1, "1", true, "true"), true) ? true : false;
		$includeOptions = in_array(strtolower($query_params["options"]), array(1, "1", true, "true"), true) ? true : false;
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		
		include_once 'apiController/ApiTenderController.php';
		$tenderController = new ApiTenderController();
		
		$success_count = 0;
		$total_count = 0;
		$paymentTendersArr = array();
	
		if(($query_params['name']))
		{
			$names = array_unique(explode("," , $query_params['name']));
			$this->logger->debug("Going to search by name: ".print_r($names, true));
			
			foreach($names as $name)
			{
				try {
					$tender = $tenderController->getPaymentModes($name, $includeAttributes, $includeOptions);
					$tender = $tender[0];
					$tender["message"] = ErrorMessage::$api[$api_status_code];
					$tender["success"] = true;
					$tender["code"] = 200;
					$paymentTendersArr []= $tender;
					$success_count++;
				} catch (Exception $e) {
					$tender = array();
					$tender["name"] = $name;
					$tender["success"] = false;
					$tender["message"] = $e->getMessage();
					$tender["code"] = $e->getCode();
					$paymentTendersArr []= $tender;
				}
			}
		}
		else
		{
			try{
				$paymentTendersArr = $tenderController->getPaymentModes(null, $includeAttributes, $includeOptions);
				$success_count = count($paymentTendersArr);
			} catch (Exception $e) {
				throw new Exception(ErrorMessage::$api["FAIL"] . ", " . $e->getMessage(), ErrorCodes::$api["FAIL"]);
			}
				
		}
	
		if($success_count == 0)
			$api_status_code = "FAIL";
		else if( $names && $success_count < count($names) )
			$api_status_code = "PARTIAL_SUCCESS";
	
		$formatter = ResourceFormatterFactory::getInstance("tender");
		
		$ret = array();
		foreach($paymentTendersArr as $paymentTender)
		{
			// include based on request
			if($includeAttributes)
				$formatter->setIncludedFields("attributes");
			if($includeOptions)
				$formatter->setIncludedFields("options");
			if($includeIds)
				$formatter->setIncludedFields("id");
			
			$item = $formatter->generateOutput($paymentTender);
			$ret[] = $item; 
		}
		
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api["SUCCESS"] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
	
		return  array(
				"status" => $api_status,
				"tenders" => array(
						"count" => $success_count,
						"tender" => $ret
				)
		);
	
	}
	
	/**
	 * @param unknown_type $query_params
	 * 
	 *  get the attrbutes
	 */
	private function getTenderAttributes($query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';
		
		$includeOptions = in_array(strtolower($query_params["options"]), array(1, "1", true, "true"), true) ? true : false;
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		
		include_once 'apiController/ApiTenderController.php';
		$tenderController = new ApiTenderController();
		
		$params = array();
		$query_params['name'] ? $attribute_name =  explode(",", $query_params['name']) : "";
		$tender_name = $query_params['tender_name'];
		
		$items = array();
		$success_count = 0;
		$total_count = 0;
		$paymentAttrsArr = array();
		
		if($attribute_name)
		{
			foreach($attribute_name as $name)
			{
				try {
					$attribute = $tenderController->getPaymentAttributes($tender_name, $name, $includeOptions);
					$attribute = $attribute[0];
					$attribute["message"] = ErrorMessage::$api[$api_status_code];
					$attribute["success"] = true;
					$attribute["code"] = 200;
					$paymentAttrsArr []= $attribute;
					$success_count++;
				} catch (Exception $e) {
					$attribute = array();
					$attribute["tender_name"] = $tender_name;
					$attribute["name"] = $name;
					$attribute["success"] = false;
					$attribute["message"] = $e->getMessage();
					$attribute["code"] = $e->getCode();
					$paymentAttrsArr []= $attribute;
				}
			}
		}
		else
		{
			try{
				$paymentAttrsArr = $tenderController->getPaymentAttributes($tender_name, null, $includeOptions);
				$success_count = count($paymentAttrsArr);
			} catch (Exception $e) {
				throw new Exception(ErrorMessage::$api["FAIL"] . ", " . $e->getMessage(), ErrorCodes::$api["FAIL"]);
			}
		}
		
		if($success_count == 0)
			$api_status_code = "FAIL";
		else if( $attribute_name && $success_count < count($attribute_name) )
			$api_status_code = "PARTIAL_SUCCESS";
		
		$formatter = ResourceFormatterFactory::getInstance("tenderattribute");
		
		$ret = array();
		foreach($paymentAttrsArr as $attribute)
		{
			$formatter->setIncludedFields("tender");
			// include based on request
			if($includeOptions)
				$formatter->setIncludedFields("options");
			if($includeIds)
				$formatter->setIncludedFields("id");
				
			$item = $formatter->generateOutput($attribute);
			$ret[] = $item;
		}
		
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api["SUCCESS"] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		return  array(
				"status" => $api_status,
				"attributes" => array(
						"count" => $success_count,
						"attribute" => $ret
				)
		);
		
	}
	
	/**
	 * Checks if the system supports the version passed as input
	 *
	 * @param $version
	 */

	public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1','v1.1'))){
			return true;
		}
		return false;
	}

	public function checkMethod($method)
	{
		if(in_array(strtolower($method), array('attributes', 'get' )))
		{
			return true;
		}
		return false;
	}

}
