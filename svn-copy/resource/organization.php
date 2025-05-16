<?php

require_once "resource.php";
include_once 'apiHelper/resourceFormatter/ResourceFormatterFactory.php';
include_once 'apiController/ApiSearchController.php';
/**
 * Exposes all information for an organization as API's. 
 * This would be used internally as well by social/conquest/Intouch UI later for making the core platform as a SOA.
 * 
 * stores : fetches list of stores
 * configs : fetches configs for the organization
 * statistics : statistics like number of customers/Avg basket size
 * topitems : fetches the top selling items 
 * customers : fetch list of customers
 * customfields : custom fields for an organization
 *
 * @author pigol
 */

/**
 * @SWG\Resource(
 *     apiVersion="1.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/organization",
 *     basePath="http://{{INTOUCH_ENDPOINT}}/v1.1"
 * )
 */
class OrganizationResource extends BaseResource{

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
				
				case 'configs' :
						
					$result = $this->configs( $query_params, $http_method, $data );
					break;
					
				case 'statistics' :

					$result = $this->statistics($query_params);
					break;
					
				case 'livestats':
					$result = $this->liveStats($query_params);
					break;
						
				case 'topitems' :
				
					$result = $this->topItems($query_params);
					break;				

				case 'customers' :

					$result = $this->customers($query_params);
					break;

				case 'customfields' :
						
					$result = $this->customFields($query_params);
					break;
						
				case 'entities' : 
					
					$result = $this->entities($query_params);
					break;
					
				case 'get' :
					
					$result = $this->get($query_params);
					break;
					
				case 'tenders':
					// get methods
					if(strtoupper($http_method) == 'GET') 
					{
						if($query_params["attribute_name"])
							$result = $this->getTenderAttributes($query_params);
						else
							$result = $this->getTenders($query_params);
					}
					
					else if(strtoupper($http_method) == 'POST')
					{
						$result = $this->saveTenders($data, $query_params);
					}	
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
					break;
                                    
                                case 'triggers' :
                                        $result = $this->triggers($http_method, $data, $query_params);
                                        break;
				
				case 'prefix':
					$result = $this -> orgPrefix($http_method, $data, $query_params);
					break;

				case 'products':
					$result = $this->products($data, $query_params);
					break;
				
				case 'currencies':
					$result = $this->currencies($data, $query_params);
                                    
				default :
					$e = new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
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
	 * Fetches configs for the organization
	 * @param array $query_params
	 */
	 	/**
         * @SWG\Api(
         * path="/organization/configs.{format}",
		 
         * @SWG\Operation(
		 *     nickname = "Get Organization Configs",
         *     method="GET", summary="Returns all configurations of the organization as key, value pair",
	  	 *     @SWG\Parameter(name = "name", type = "string", paramType = "query", description = ""),
		       @SWG\Parameter(name = "scope", type = "string", paramType = "query", description = ""),
		       @SWG\Parameter(name = "module", type = "string", paramType = "query", description = ""),
		       @SWG\Parameter(name = "value_type", type = "string", paramType = "query", description = ""),
		       @SWG\Parameter(name = "return_all", type = "string", paramType = "query", description = "")
        * ))
        */
        
                /**
         * @SWG\Model(
         * id = "ConfigsRequest",
         * @SWG\Property(name = "configs", type = "configList" )
         * )
         */
		
		/**
         * @SWG\Model(
         * id = "configList",
         * @SWG\Property(name = "config", type = "array", items = "$ref:config" )
         * )
         */
         
        /**
         * @SWG\Model(
         * id = "config",
                 * @SWG\Property(name = "name", type = "string", required = true ),
                 * @SWG\Property(name = "scope", type = "string", required = false ),
                 * @SWG\Property(name = "entity_id", type = "string", required = false ),
                 * @SWG\Property(name = "value", type = "string", required = true )
         * )
         * */

        /**
         * @SWG\Model(
         * id = "ConfigsRoot",
         * @SWG\Property( name = "root", type = "ConfigsRequest" )
         * )
         */
        
        /**
         * @SWG\Api(
         * path="/organization/configs.{format}",
        * @SWG\Operation(
        *     method="POST", summary="Add/update organization config(s)",
         * @SWG\Parameter(
         * name = "request",paramType="body", type="ConfigsRoot")
        * ))
        */
	private function configs( $query_params, $http_method, $data )
	{
		global $currentorg;
		$organization_controller = new ApiOrganizationController();
		$config_manager = new ConfigManager();
		$api_status_code = 'SUCCESS';
		
		switch ( $http_method ){
				
			case 'GET':
				
				if( isset( $query_params['scope'] ) && !in_array(
				strtoupper( $query_params['scope'] ),
				ConfigKey::getValidScopes()
				) ){
		
					$this->logger->error( "Invalid Scope for config : ".$scope );
					$status_code = "ERR_ORG_CONFIG_INVALID_SCOPE" ;
					$api_status_code = 'FAIL';
				}else{
					$include_all = isset($query_params['return_all']) ? filter_var(true, FILTER_VALIDATE_BOOLEAN) : false;
					$arr = $organization_controller->getOrgConfigs(
							$query_params['name'],
							$query_params['scope'],
							$query_params['module'],
							$query_params['value_type'],
							$include_all );
				}
				if( $query_params['name'] ){
					$status_code = 'ERR_ORG_CONFIG_FOUND';
					$configs_found = array();
					//Populate item status for the items
					foreach( $arr as $key => $value ){
		
						$configs_found[] = $value['key'];
						$item_status = array(
								'code' => ErrorCodes::$organization[$status_code],
								'success' => $status_code == 'ERR_ORG_CONFIG_NOT_FOUND' ? false : true,
								'messsage' => ErrorMessage::$organization[$status_code]
						);
						$arr[$key]['item_status'] = $item_status ;
					}
					$names = explode( ",", $query_params['name'] );
					$configs_not_found = array_udiff( $names, $configs_found, 'strcasecmp' );
					foreach ( $configs_not_found as $config ){
		
						$status_code = 'ERR_ORG_CONFIG_NOT_FOUND';
						$item_status = array(
								'key' => $config,
								'item_status' => array(
										'code' => ErrorCodes::$organization[$status_code],
										'success' => $status_code == 'ERR_ORG_CONFIG_NOT_FOUND' ? false : true,
										'messsage' => ErrorMessage::$organization[$status_code]
								)
						);
						array_push( $arr, $item_status );
					}
				}
				$api_status = array(
						"success" => $api_status_code == 'SUCCESS' ? true : false,
						"code" => ErrorCodes::$api[$api_status_code],
						"message" => $api_status_code == 'SUCCESS' ? 'SUCCESS' : ErrorMessage::$organization[$status_code],
				);
				if(count($arr) > 0){
		
					$arr = array("configs" => array("config" => $arr));
				}
				return array(
						"status" => $api_status,
						"organization" => $arr
				);
				break;
		
			case 'POST':
		
				$this->logger->debug( "Organization Configs: POST" );
				
				if(!is_array($data['root']['organization'])){
					$this->logger->info("empty organization data!");
					$response = array(
							'status' =>  array(
									'success' => 'false' ,
									'code' => ErrorCodes::$api[ 'INVALID_INPUT' ],
									'message' => ErrorMessage::$api[ 'INVALID_INPUT' ]
							),
							'organization' => array( 'configs' => array() )
					);
					return $response;
				}
				
				$configs = $data['root']['organization'][0]['configs'];
				$this->logger->debug( "Configs :".print_r( $configs, true ) );
				$config_count = count( $configs );
				foreach( $configs as $config ){
		
					$item_status_code = "ERR_ORG_CONFIG_ADD_SUCCESS";
					try{
		
						$name = $config['name'];
						$scope = empty( $config['scope'] ) ? 'ORG' : $config['scope'];
						$value = $config['value'];
						if( !strcasecmp( 'ORG', $scope ) && 
								isset( $config['entity_id'] ) &&
								$currentorg->org_id!= $config['entity_id'] ){
							
							$this->logger->error( "Invalid Entity Id passed ".$entity_id );
							Util::addApiWarning( "Invalid Entity Id" );
							throw new Exception( 'ERR_ORG_CONFIG_ADD_FAIL' );
						}
						$entity_id = strcasecmp( 'ORG', $scope ) == 0  ? 
							$currentorg->org_id : ( isset( $config['entity_id'] ) ? $config['entity_id'] : -1 );
						$this->logger->debug( "Adding value $value for config key $name, scope $scope, entity $entity_id" );
						$organization_controller->addUpdateOrgConfigs( $name, $scope, $value, $entity_id );
						$item_status_code = 'ERR_ORG_CONFIG_ADD_SUCCESS';
						$item = $config;
						$item['value'] = $config_manager->getKeyForEntity( $name, $entity_id );
						$item['entity_id'] = $entity_id;
					}catch( Exception $e ){
							
						$error_count++;
						$item_status_code = $e->getMessage();
						$this->logger->error( "Exception while adding config key value" );
						$this->logger->error( "Exception : ".$e );
						$item = $config;
					}
					$api_warnings = Util::getApiWarnings();
					$item['item_status'] = array(
							'success' => ( $item_status_code == "ERR_ORG_CONFIG_ADD_SUCCESS" ) ? true : false,
							'code' => ( !ErrorCodes::$organization[ $item_status_code ] ) ?
							"ERR_ORG_CONFIG_ADD_FAIL" : ErrorCodes::$organization[ $item_status_code ],
							'message' => ( !ErrorMessage::$organization[ $item_status_code ] ) ?
							ErrorMessage::$organization[ "ERR_ORG_CONFIG_ADD_FAIL" ].", $item_status_code" :
							ErrorMessage::$organization[ $item_status_code ].", $api_warnings"
					);
					$item['item_status']['message'] = rtrim( trim( $item['item_status']['message'] ), ',' ); 
					$items['config'][] = $item;
				}
				if( $error_count > 0 ){
		
					$status_code = ( $config_count == $error_count )? 'FAIL' : 'PARTIAL_SUCCESS';
				}else{
		
					$status_code = 'SUCCESS';
				}
				$status_success = ( $status_code == 'FAIL' )? 'false' : 'true';
				$response = array(
						'status' =>  array(
								'success' => $status_success ,
								'code' => ErrorCodes::$api[ $status_code ],
								'message' => ErrorMessage::$api[ $status_code ]
						),
						'organization' => array( 'configs' => $items )
				);
				return $response;
				break;
		}
	}
	
	/**
	 * Returns Statistics like number of customers/Avg basket size
	 * @param array $query_params
	 */
	 /**
         * @SWG\Api(
         * path="/organization/statistics.{format}",
         * @SWG\Operation(
         *     method="GET", summary="Returns Statistics like number of customers/Avg basket size"
        * ))
        */
	private function statistics( $query_params)
	{
                global $currentorg;
                $cackeKey = "o".$currentorg->org_id. "_".CacheKeysPrefix::$orgStatisticsXML;
                try {
                    $memcache = MemcacheMgr::getInstance();
                    $cacheResult = $memcache->get($cackeKey);
                    $this->logger->debug("Fetching cached response");
                    return json_decode($cacheResult, true);
                } catch (Exception $ex) {
                    $this->logger->debug("No cached response found");
                }
                
		$api_status_code = 'SUCCESS';
		$api_status = array(
				"success" => "true",
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		$C_organizationController = new ApiOrganizationController();
		
		$statistics = $C_organizationController->getStatistics();
		
		$ret = array(
						"status" => $api_status,
						"organization" => array(
								"statistics" => $statistics
						)
				);
                
                try
                {
                    $memcache = MemcacheMgr::getInstance();
                    $cacheResult = $memcache->set($cackeKey, json_encode($ret), CacheKeysTTL::$orgStatisticsXML );
	}
                catch (Exception $ex) {
                }
                return $ret;
	}

	private function liveStats( $query_params)
	{
		global $currentorg;
		$cackeKey = "o".$currentorg->org_id. "_Perf_".CacheKeysPrefix::$orgPerformanceXML;
		try {
			$memcache = MemcacheMgr::getInstance();
			$cacheResult = $memcache->get($cackeKey);
			$this->logger->debug("Fetching cached response");
			return json_decode($cacheResult, true);
		} catch (Exception $ex) {
			$this->logger->debug("No cached response found");
		}
	
		$api_status_code = 'SUCCESS';
		$api_status = array(
				"success" => "true",
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
	
		$C_organizationController = new ApiOrganizationController();
	
		$performance = $C_organizationController->getOrgPerformance();
		foreach($performance as $k=>&$v){
			$v["id"] = $k;
			$v["type"] = "ORG";
			$v["report_time"] = date('Y-m-d H:i:s');
		}
	
		$ret = array(
				"status" => $api_status,
				
				"entities" => array(
						"entity" => array_values($performance)
				)
		);
	
		try
		{
			$memcache = MemcacheMgr::getInstance();
			$cacheResult = $memcache->set($cackeKey, json_encode($ret), CacheKeysTTL::$orgPerformanceXML );
		}
		catch (Exception $ex) {
		}
		return $ret;
	}
	
	
	
	/**
	 * Fetches the top selling items. 
	 * @param array $query_params
	 */
	 /**
         * @SWG\Api(
         * path="/organization/topitems.{format}",
         * @SWG\Operation(
         *     method="GET", summary="Fetches the top selling items"
        * ))
        */
	private function topItems( $query_params)
	{
		$api_status_code = "SUCCESS";
		
		/*$C_config_manager = new ConfigManager();
		$topitems = $C_config_manager->getKey("CONF_ORG_TOPITEMS");*/

		$C_inventoryController = new ApiInventoryController();
		
		$items = $C_inventoryController->getTopItems();
		
		/*if(isset($topitems) && is_array($topitems))
		{
			foreach($topitems as $id)
			{
				try
				{
					//$item = $C_inventoryController->getProductById($id);
					
				}
				catch(Exception $e)
				{
					$this->logger->error("ERROR: ".$e->getMessage());
					$item = array(
								"id" => $id,
								"sku" => "-1",
								"price" => "",
								"img_url" => "",
								"in_stock" => "",
								"description" => ""
							);
				}
				array_push($items, $item);
			}
		}*/
		
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api["SUCCESS"] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);

		return 	array(
						"status" => $api_status,
						"organization" => array(
								"item" => $items
						)
				);
	}

	/**
	 * Fetch list of customers
	 * @param array $query_params
	 */
	 /**
         * @SWG\Api(
         * path="/organization/customers.{format}",
         * @SWG\Operation(
         *     method="GET", summary="Fetches configs for the organization",
         *    @SWG\Parameter(
         *    name = "type",
         *    type = "string",
	  	 *    enum="['fraud', 'normal']",
         *    paramType = "query",
         *    description = "Type 'normal' to fetch normal customers and type 'fraud' to fetch fraud customers. Without the type parameter it fetches all the normal customers "
         *    )
        * ))
        */
	private function customers( $query_params )
	{
		if(isset($query_params['type']))
		{
			$type = strtolower($query_params['type']);
		}
		else
		{
			$type = 'normal';
		}
		$this->logger->debug("query param 'type': $type");
		switch($type)
		{
			case 'fraud':
				$this->logger->debug("going to fetch fraud customers");
				$customers = $this->getFraudCustomer($query_params);
				break;
			case 'normal':
				$this->logger->debug("going to fetch normal customer list");
				$customers = array();
				break;
			default:
				throw new Exception(ErrorMessage::$api['INVALID_INPUT'].', '. 
						ErrorMessage::$organization['ERR_CUSTOMER_TYPE_INVALID'],
						ErrorCodes::$api['INVALID_INPUT']);
		}
		return $customers;
	}
	
	private function getFraudCustomer($query_params)
	{
		//setting default values if given param is not passed
		$fraud_status = isset($query_params['fraud_status']) ? 
							strtoupper($query_params['fraud_status']) : null;
		$order = isset($query_params['order']) ? $query_params['order'] : null;
		$sort = isset($query_params['sort']) ? $query_params['sort'] : "id";
		$start_id = isset($query_params['start_id']) ? $query_params['start_id'] : null;
		$end_id = isset($query_params['end_id']) ? $query_params['end_id'] : null;
		$user_id = isset($query_params['user_id']) ? $query_params['user_id'] : null;
		$start_date = isset($query_params['start_date']) ? $query_params['start_date'] : null;
		$end_date = isset($query_params['end_date']) ? $query_params['end_date'] : null;
		$limit = isset($query_params['limit']) ? $query_params['limit'] : 10;
		//Delta and compressed is not implemented yet
		$store_id = isset($query_params['store_id']) ? $query_params['store_id'] : null;
		$delta = isset($query_params['delta']) ? $query_params['delta'] : null;
		$compressed = isset($query_params['compressed']) ? $query_params['compressed'] : null;
		
		$api_status_key = 'SUCCESS';
		
		$customerController = new ApiCustomerController();  
		$customers = $customerController->getFrausUsers(
				$fraud_status, $order, $sort, 
				$start_id, $end_id, $start_date, 
				$end_date, $store_id, $limit);
		$return_customers = array();
		
		foreach($customers AS $customer)
		{
			$tmp_customer = array();
			$tmp_customer['user_id'] = $customer['user_id'];
			$tmp_customer['mobile'] = $customer['mobile'];
			$tmp_customer['email'] = $customer['email'];
			$tmp_customer['external_id'] = $customer['external_id'];
			$tmp_customer['fraud_status'] = $customer['status'];
			$return_customers[] = $tmp_customer;
		}
		
		return array(
					"status" => array(
								"success" => ErrorCodes::$api['SUCCESS'] == ErrorCodes::$api[$api_status_key],
								"code" => ErrorCodes::$api['SUCCESS'] ,
								"message" => ErrorMessage::$api['SUCCESS']
							),
					"customers" => array(
							"customer" => $return_customers
							)
				);
	}
	
	/**
	 * Custom Fields for an organization
	 * @param array $query_params
	 */
	 
	  /**
	     * @SWG\Api(
	     * path="/organization/customfields.{format}",
	     * @SWG\Operation(
	     *   nickname = "Get Organization Custom Fields",
	     *   method="GET", summary="Returns Custom field Details for given Scope of the organization.",
	     *   @SWG\Parameter(name = "scope", type = "string", paramType = "query", description = "")
	     * ))
	   */
       
	private function customfields( $query_params){
		
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = "SUCCESS";
		$organization_controller = new ApiOrganizationController();
		
		$scope_array = StringUtils::strexplode( ',' , $query_params[ 'scope' ] );

        global $error_count;
        $error_count = 0;
		$scope_count = 0;
		$response = array();
        $include_disabled = strtolower($query_params['include_disabled']) == 'true' ? true : false;
		
		if( !isset($query_params[ 'scope' ]) || $query_params[ 'scope' ] == '' ){
		
				$error_key = "ERR_ORG_CUSTOM_FIELD_GET_SUCCESS";
				++$scope_count;
				$this->logger->debug( "Getting custom field details for all scopes for org : $organization_controller->org_id " );

                $fields = $organization_controller->customfields("");
                if($include_disabled)
                {
				    $dis_fields = $organization_controller->customfields("", $include_disabled);
                    if(! empty($dis_fields))
                        if(! empty($fields))
                            $fields = array_merge($fields, $dis_fields);
                        else
                            $fields = $dis_fields;
                }
				$result = array();
				$result['field'] = array();
				foreach($fields as $item_data)
				{
					$temp_field = array();
					$temp_field['name'] = $item_data['name'];
					$temp_field['label'] = $item_data['label'];
					$temp_field['type'] = $item_data['type'];
					$temp_field['datatype'] = $item_data['datatype'];
					$temp_field['default'] = $item_data['default'];
					$temp_field['phase'] = $item_data['phase'];
					$temp_field['position'] = $item_data['position'];
					$temp_field['rule'] = $item_data['rule'];
					$temp_field['regex'] = $item_data['regex'];
					$temp_field['error'] = $item_data['error'];
					$temp_field['options'] = preg_replace("/\\\\u([a-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V', hexdec('U$1')))", $item_data['attrs'] );
					$temp_field['scope'] = $item_data['scope'];
					$temp_field['is_mandatory'] = $item_data['is_compulsory'];
					$temp_field['is_updatable'] = $item_data['is_updatable'];
					$temp_field['is_disabled'] = $item_data['is_disabled'];
				    $temp_field['disabled_at_server'] = $item_data['disable_at_server'];
					array_push($result['field'],$temp_field);
				}
				array_push( $response, $result );
			}else{
		
				foreach( $scope_array as $key => $scope ){
			
					++$scope_count;
					try{
						
						if(!$scope){
						
							throw new Exception("ERR_ORG_SCOPE_NOT_FOUND");
						}
						
						$error_key = "ERR_ORG_CUSTOM_FIELD_GET_SUCCESS";
						$this->logger->debug( "Getting custom field details for scope :$scope ");
						$fields = $organization_controller->customfields( $scope );
                        if($include_disabled)
                        {
                            $dis_fields = $organization_controller->customfields($scope, $include_disabled);
                            if(! empty($dis_fields))
                                if(! empty($fields))
                                    $fields = array_merge($fields, $dis_fields);
                                else
                                    $fields = $dis_fields;
                        }

						if(!$fields){
							
							$this->logger->error( "Invalid Scope: ".$scope );
							throw new Exception( "ERR_ORG_SCOPE_NOT_FOUND" );
						}
						
						$result['field'] = array();
						
						foreach($fields as $item_data)
						{
							$temp_field = array();
							$temp_field['name'] = $item_data['name'];
							$temp_field['label'] = $item_data['label'];
							$temp_field['type'] = $item_data['type'];
							$temp_field['datatype'] = $item_data['datatype'];
							$temp_field['default'] = $item_data['default'];
							$temp_field['phase'] = $item_data['phase'];
							$temp_field['position'] = $item_data['position'];
							$temp_field['rule'] = $item_data['rule'];
							$temp_field['regex'] = $result['regex'];
							$temp_field['error'] = $result['error'];
							$temp_field['options'] = preg_replace("/\\\\u([a-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V', hexdec('U$1')))", $item_data['attrs'] );
							$temp_field['scope'] = $item_data['scope'];
							$temp_field['is_mandatory'] = $item_data['is_compulsory'];
							$temp_field['is_disabled'] = $item_data['is_disabled'];
                            $temp_field['disabled_at_server'] = $item_data['disable_at_server'];
						
							array_push($result['field'],$temp_field);
						}
						
					}Catch( Exception $e ){
					
						++$error_count;
						$error_key = $e->getMessage();
						$this->logger->error( "OrganizationResource::CustomFields()  Error: ".ErrorMessage::$organization[ $e->getMessage() ]);
					
						//adding scope name just in case of exception to know which item status failed
						$result = array( 'scope' => $scope );
					}
					
				$result[ 'item_status' ][ 'success' ] = ( $error_key == "ERR_ORG_CUSTOM_FIELD_GET_SUCCESS" ) ? 'true' : 'false' ;
				$result[ 'item_status' ][ 'code' ] = ErrorCodes::$organization[ $error_key ];
				$result[ 'item_status' ][ 'message' ] = ErrorMessage::$organization[ $error_key ] ;
				$arr_item_status_codes[] = $result['item_status']['code'];
				array_push( $response , $result);
				}
			}
		//Status
		$status = 'SUCCESS';
		if( $scope_count == $error_count ){
				
			$status = 'FAIL';
		}
		else if( ( $error_count < $scope_count ) && ( $error_count > 0 ) ){
				
			$status = 'PARTIAL_SUCCESS';
		}
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		
		$root[ 'status' ][ 'success' ] = ($status == 'SUCCESS' || $status == 'PARTIAL_SUCCESS') ? 'true' : 'false';
		$root[ 'status' ][ 'code' ] = ErrorCodes::$api[$status];
		$root[ 'status' ][ 'message' ] = ErrorMessage::$api[$status];
		$root[ 'organization' ]['custom_fields'] = $response;
			
		return $root;
	}
	
	 /**
     * @SWG\Api(
     * 		path = "/organization/entities.{format}",
     * 		@SWG\Operation(
     *     		method = "GET", summary = "Fetches organization-entities",
     *			description = "API Response is limited by an entity-count of 200.", 
     *     		@SWG\Parameter(
     *     			name = "type", type = "string", required = true, 
  	 *    			enum = "['TILL', 'STR_SERVER', 'STORE', 'ZONE', 'CONCEPT']",
     *    			paramType = "query", description = "Entity list provided based on the type specified"
     *    		), 
     *     		@SWG\Parameter(
     *     			name = "id", type = "integer", required = false, paramType = "query",
     * 				description = "Filter based on IDs; Accepts mutiple vaues in comma-separated format"
     *    		), 
     *     		@SWG\Parameter(
     *     			name = "include_parent", type = "boolean", required = false, paramType = "query",
     * 				description = "Include information about each entity's parent"
     *    		), 
     *     		@SWG\Parameter(
     *     			name = "sub_entities_count", type = "integer", format="int32", required = false, enum = "[0, 1]", 
     * 				paramType = "query", description = "Include counts for every type of child of each entity"
     *    		), 
     *     		@SWG\Parameter(
     *     			name = "details", type = "string", required = false, enum = "['basic']", paramType = "query",
     * 				description = "If details=basic is provided, then info of each entity's country, language, currency and timezone shall not be provided"
     *    		), 
     *     		@SWG\Parameter(
     *     			name = "exclude_locale", type = "integer", format="int32", required = false, enum = "[0, 1]", 
     * 				paramType = "query", description = "If exclude_locale is set to true, only basic info (i.e. IDs) of 
     *					each entity's country, language, currency and timezone shall be provided"
     *    		), 
     *     		@SWG\Parameter(
     *     			name = "modified_since", type = "date", required = false, paramType = "query", 
     * 				description = "If modified_since (format 'yyyy-MM-dd') is set, only entities added/modified past the date shall be provided"
     *    		), 
     *     		@SWG\Parameter(
     *     			name = "start_id", type = "integer", format="int64", required = false, paramType = "query",
     * 				description = "For paginated requests, set offset with this parameter"
     *    		), 
     *     		@SWG\Parameter(
     *     			name = "limit", type = "integer", required = false, paramType = "query",
     * 				description = "For paginated requests, set limit with this parameter. Limit must not be greater than 200."
     *    		)
     * 		)
     *	)
     */
	private function entities($query_params)
	{
		global $gbl_item_status_codes, $currentorg;
		$arr_item_status_codes = array();
		if(!isset($query_params['type']) || empty($query_params['type']))
		{
			$this->logger->debug("Type is not passed or empty");
			return array(
					"status" => array(
							"success" => ErrorCodes::$api["FAIL"] ==
								ErrorCodes::$api["SUCCESS"]? true: false,
							"code" => ErrorCodes::$api["FAIL"],							
							"message" => "type is not Sent, Please send type STORE/TILL/ZONE"
							)
				);
		}
		$filters = array();
		if(strtolower($query_params['details']) == 'basic'){
			$filters["exclude_locale"] = 1;
			$filters["details"] = 'basic'; 
		}
		if($query_params["include_parent"] )
			$filters["include_parent"] = $query_params["include_parent"];
		if($query_params["parent_zone_code"] )
			$filters["parent_zone_code"] = $query_params["parent_zone_code"];
		if($query_params["parent_zone_id"] )
			$filters["parent_zone_id"] = $query_params["parent_zone_id"];
		if($query_params["parent_concept_code"] )
			$filters["parent_concept_code"] = $query_params["parent_concept_code"];
		if($query_params["parent_concept_id"] )
			$filters["parent_concept_id"] = $query_params["parent_concept_id"];
		if($query_params["sub_entities_count"] )
			$filters["sub_entities_count"] = $query_params["sub_entities_count"];
		if($query_params["start_id"] )
			$filters["start_id"] = $query_params["start_id"];
		if($query_params["limit"] )
			$filters["limit"] = $query_params["limit"];
		$cackeKey = "o".$currentorg->org_id. "_".CacheKeysPrefix::$orgEntityXML .strtoupper(($query_params['type']));
		
		$type = explode(",",$query_params['type']);
		$type = $type[0];
		
		$ids = isset($query_params['id']) && $query_params['id'] !== '' ? $query_params['id'] : false;
		
		if($ids !== false)
		{
			$ids = explode(",", $ids);
			$str_ids = "'". implode("','", $ids) ."'";
		}
		else if (!$filters)
		{
			try {
				$memcache = MemcacheMgr::getInstance();
				$cacheResult = $memcache->get($cackeKey);
				if($cacheResult)
					return json_decode($cacheResult, true);
			} catch (Exception $e) {
			}
		}
				
		
		$type = strtoupper($type);
		
		$api_status_code = "SUCCESS";
		
		$entities = array();
        global $error_count;
        $error_count = 0;
		$C_organization_controller = new ApiOrganizationController();
		
		if($type == "STORE")
		{
			$stores = $C_organization_controller->getStoresAsEntities($str_ids);
			$entities = array();
				
			if($ids === false)
			{
				$entities = $stores;
			}
			if($ids !== false )
			{
				if( !$stores || !is_array($stores))
				{
					$stores = array();
				}
				foreach($ids as $id)
				{
					if(isset($stores[$id]))
					{
						$stores[$id]['item_status'] =
						array(
								"code" => ErrorCodes::$organization['ERR_ENTITY_SEARCH_SUCCESS'],
								"success" => true,
								"message" => ErrorMessage::$organization['ERR_ENTITY_SEARCH_SUCCESS']
						);
						$arr_item_status_codes[] = $stores[$id]['item_status']['code'];
						array_push($entities, $stores[$id]);
					}
					else
					{
						$error_count++;
						$entity = array(
								'id' => $id,
								'item_status' =>
								array(
										"code" => ErrorCodes::$organization['ERR_ENTITY_SEARCH_FAIL'],
										"success" => false,
										"message" => ErrorMessage::$organization['ERR_ENTITY_SEARCH_FAIL']
								)
						);
						$arr_item_status_codes[] = $entity['item_status']['code'];
						array_push($entities, $entity);
					}
				}
			}
		}
		else if($type == "TILL")
		{
			if($query_params["parent_id"] )
				$filters["store_id"] = intval($query_params["parent_id"]);
					
			$tills = $C_organization_controller->getTills($str_ids, $filters);
			$entities = array();
			
			if( $ids === false )
			{
				$entities = $tills;
			}
			if( $ids !== false )
			{
				if( !$tills || !is_array($tills) )
				{
					$tills = array();
				}
				foreach($ids as $id)
				{
					if(isset($tills[$id]))
					{
						$tills[$id]['item_status'] =
						array(
								"code" => ErrorCodes::$organization['ERR_ENTITY_SEARCH_SUCCESS'],
								"success" => true,
								"message" => ErrorMessage::$organization['ERR_ENTITY_SEARCH_SUCCESS']
						);
						$arr_item_status_codes[] = $tills[$id]['item_status']['code'];
						array_push($entities, $tills[$id]);
					}
					else
					{
						$error_count++;
						$entity = array(
								'id' => $id,
								'item_status' =>
								array(
										"code" => ErrorCodes::$organization['ERR_ENTITY_SEARCH_FAIL'],
										"success" => false,
										"message" => ErrorMessage::$organization['ERR_ENTITY_SEARCH_FAIL']
								)
						);
						$arr_item_status_codes[] = $entity['item_status']['code'];
						array_push($entities, $entity);
					}
				}
			}
		}
		else if($type == "ZONE")
		{
			$zones = $C_organization_controller->getZones($str_ids, $filters);
			$zone_parent_mapping = $C_organization_controller->getEntityParentMapping( $query_params['id'], 'ZONE' );
			
			$entities = array();
			
			if( $ids === false || $filters)
			{
				$entities = $zones;
			}
			else if( $ids !== false )
			{
				if( !$zones || !is_array($zones) )
				{
					$zones = array();
				}
				foreach($ids as $id)
				{
					if(isset($zones[$id]))
					{
						$zones[$id]['item_status'] = 
							array(
									"code" => ErrorCodes::$organization['ERR_ENTITY_SEARCH_SUCCESS'],
									"success" => true,
									"message" => ErrorMessage::$organization['ERR_ENTITY_SEARCH_SUCCESS']
								);
						$zones[$id]['parent_id'] = $zone_parent_mapping[$id];
						$arr_item_status_codes[] = $zones[$id]['item_status']['code'];
						array_push($entities, $zones[$id]);
					}
					else
					{
						$error_count++;
						$entity = array(
								'id' => $id,
								'item_status' =>
									array(
											"code" => ErrorCodes::$organization['ERR_ENTITY_SEARCH_FAIL'],
											"success" => false,
											"message" => ErrorMessage::$organization['ERR_ENTITY_SEARCH_FAIL']
									)
								);
						$arr_item_status_codes[] = $entity['item_status']['code'];
						array_push($entities, $entity);
					}
				}
			}
		}
		else if($type == 'CONCEPT')
		{
			$concepts = $C_organization_controller->getConcepts($str_ids, $filters);
			$entities = array();
				
			if( $ids === false)
			{
				$entities = $concepts;
			}
			else
			{
				if( !$concepts || !is_array($concepts) )
				{
					$concepts = array();
				}
				foreach($ids as $id)
				{
					if(isset($concepts[$id]))
					{
						$concepts[$id]['item_status'] =
						array(
								"code" => ErrorCodes::$organization['ERR_ENTITY_SEARCH_SUCCESS'],
								"success" => true,
								"message" => ErrorMessage::$organization['ERR_ENTITY_SEARCH_SUCCESS']
						);
						$arr_item_status_codes[] = $concepts[$id]['item_status']['code'];
						array_push($entities, $concepts[$id]);
					}
					else
					{
						$error_count++;
						$entity = array(
								'id' => $id,
								'item_status' =>
								array(
										"code" => ErrorCodes::$organization['ERR_ENTITY_SEARCH_FAIL'],
										"success" => false,
										"message" => ErrorMessage::$organization['ERR_ENTITY_SEARCH_FAIL']
								)
						);
						$arr_item_status_codes[] = $entity['item_status']['code'];
						array_push($entities, $entity);
					}
				}
			}
		}
		else
		{
			$this->logger->debug("Type is not passed or empty");
			return array(
					"status" => array(
							"success" => ErrorCodes::$api["FAIL"] ==
							ErrorCodes::$api["SUCCESS"]? true: false,
							"code" => ErrorCodes::$api["FAIL"],
							"message" => "type is not Valid, Please send type STORE/TILL/ZONE/CONCEPT"
					)
			);
		}
		
		if(($error_count == count($ids) && $error_count > 0)
				|| (!$ids && count($entities) == 0))
		{
			$api_status_code = "FAIL";
		}
		else if($error_count > 0)
		{
			$api_status_code = "PARTIAL_SUCCESS";
		}
		
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		
		$api_status = array(
				"code" => ErrorCodes::$api[$api_status_code],
				"success" => ErrorCodes::$api[$api_status_code] ==
				ErrorCodes::$api["SUCCESS"]? true: false,
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		if( !$ids && count($entities) == 0)
		{
			$api_status['message'] .= ", No entities found for provided entity type."; 
			return array(
					"status" => $api_status
			);
		}

        $new_entities = array();
        foreach ($entities as $entity){
        	if($filters["exclude_locale"] != 1 ){
            $base_currency_symb = $entity['currency_symbol'];
            $base_currency_code = $entity['currency_code'];
            unset($entity['currency_symbol']);
            unset($entity['currency_code']);
            $currency = array("symbol" => $base_currency_symb, "label" => $base_currency_code);
            $entity['currencies'] = array("base_currency" => $currency);


            $base_tz_label = $entity['timezone_label'];
            $base_tz_offset = $entity['timezone_offset'];
            unset($entity['timezone_label']);
            unset($entity['timezone_offset']);
            $base_timezone = array("label" => $base_tz_label, "offset" => $base_tz_offset);
            $entity['timezones'] = array("base_timezone" => $base_timezone);

            $base_language_code = $entity['language_code'];
            $base_language_locale = $entity['language_locale'];
            unset($entity['iso_code']);
            unset($entity['language']);
            $language = array("lang" => $base_language_code, "locale" => $base_language_locale);
            $entity['languages'] = array("base_language" => $language);

        	}
            //unset($entity['time_zone_id']);
            //unset($entity['currency_id']);
            //unset($entity['language_id']);
            array_push($new_entities, $entity);
        }

		$ret = array(
					"status" => $api_status,
					"organization" => array(
								"entities" => array(
											"entity" => $new_entities
										)
							)
				);
		
		try {
			if(!$ids && !$filters)
			{
				$memcache = MemcacheMgr::getInstance();
				$cacheResult = $memcache->set($cackeKey, json_encode($ret), CacheKeysTTL::$orgEntityXML );
			}
		} catch (Exception $e) {
		}
		
		return $ret;
	}
	
        /**
        * @SWG\Api(
        * path="/organization/get.{format}",
        * @SWG\Operation(
         * nickname = "org_get",
        *     method="GET", summary="Get organization details"))
        * */
        
        /**
         * @param type $query_params
         * @return type
         */
	private function get($query_params)
	{
		$api_status_code = "SUCCESS";
		
		$organization_controller = new ApiOrganizationController();
		
		$organization = $organization_controller->getOrganizationBasicDetails();

        $supported_currencies = $organization_controller->getOrgSupportedCurrenciesISO();
        $currency = array();
        if (is_array($supported_currencies)) {
            foreach ($supported_currencies as $supported_currency) {
                array_push($currency, $supported_currency);
            }
        }

        $base_currency_symb = $organization['currency_symbol'];
        $base_currency_code = $organization['currency_code'];
        unset($organization['currency_symbol']);
        //unset($organization['currency']);
        unset($organization['currency_code']);
        $b_currency = array("symbol" => $base_currency_symb, "label" => $base_currency_code);
        $organization['currencies'] = array("base_currency" => $b_currency, "supported_currencies" => array('currency' => $currency));


        $supported_timezones = $organization_controller->getOrgSupportedTimeZoneISO();
        $timezone = array();
        if (is_array($supported_timezones)) {
            foreach ($supported_timezones as $sup_tz) {
                array_push($timezone, $sup_tz);
            }
        }

        $base_tz_label = $organization['timezone_label'];
        $base_tz_offset = $organization['timezone_offset'];
        unset($organization['timezone_label']);
        $base_timezone = array("label" => $base_tz_label, "offset" => $base_tz_offset);
        $organization['timezones'] = array("base_timezone" => $base_timezone, "supported_timezones" => array('timezone' => $timezone));
        $organization['timezone'] = $base_tz_label;

        $supported_languages = $organization_controller->getOrgSupportedLanguagesISO();
        $language = array();
        if (is_array($supported_languages)) {
            foreach ($supported_languages as $sup_lang) {
                array_push($language, $sup_lang);
            }
        }

        $base_language = $organization['language_code'];
        $base_lang_code = $organization['language_locale'];
        $lang = array("lang" => $base_language, "locale" => $base_lang_code);
        $organization['languages'] = array("base_language" => $lang, "supported_languages" => array('language' => $language));

        $supported_countries = $organization_controller->getSupportedCountriesISO();
        $country = array();
        if (is_array($supported_countries)) {
            foreach ($supported_countries as $sup_count) {
                array_push($country, $sup_count);
            }
        }
        $base_country = $organization['country'];
        $base_country_code = $organization['country_code'];
        $country_b = array("name" => $base_country, "code" => $base_country_code);
        $organization['countries'] = array("base_country" => $country_b, "supported_countries" =>  array('country' => $country));
		
		$api_status = array(
					"success" => ErrorCodes::$api[$api_status_code] == 
									ErrorCodes::$api["SUCCESS"] ? "true": "false",
					"code" => ErrorCodes::$api[$api_status_code],
					"message" => ErrorMessage::$api[$api_status_code]
				);
		
		return array(
					"status" => $api_status,
					"organization" => $organization
				);
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
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1",true, "true"), true) ? true : false;
		 
		include_once 'apiController/ApiTenderController.php';
		$tenderController = new ApiTenderController();
		
		$params = array();
// 		if($query_params['name'] == "")
// 			unset($query_params['name']);
		if(($query_params['name']))
		{
			$names = array_unique(explode("," , $query_params['name']));
			$this->logger->debug("Going to search by name: ".print_r($names, true));
		}

		$success_count = 0;
		$total_count = 0;
		$paymentTendersArr = array();
	
		if($names)
		{
			foreach($names as $name)
			{
				try {
					$tender = $tenderController->getOrgPaymentModes($name, $includeAttributes, $includeOptions);
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
				$paymentTendersArr = $tenderController->getOrgPaymentModes(null, $includeAttributes, $includeOptions);
				$success_count = count($paymentTendersArr);
				} catch (Exception $e) {
					throw new Exception(ErrorMessage::$api["FAIL"] . ", " . $e->getMessage(), ErrorCodes::$api["FAIL"]);
				}
		}
	
		if($success_count == 0)
			$api_status_code = "FAIL";
		else if( $names && $success_count < count($names) )
			$api_status_code = "PARTIAL_SUCCESS";
	
		$formatter = ResourceFormatterFactory::getInstance("orgtender");
		
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
				"organization"=> array(
					"tenders" => array(
							"count" => $success_count,
							"tender" => $ret
							),
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
		$query_params['name'] ? $attribute_name =  explode(",", $query_params['attribute_name']) : "";
		$tender_name = $query_params['name'];
		
		$items = array();
		$success_count = 0;
		$total_count = 0;
		$paymentAttrsArr = array();
		
		if($attribute_name)
		{
			foreach($attribute_name as $name)
			{
				try {
					$attribute = $tenderController->getOrgPaymentAttributes($tender_name, $name, $includeOptions);
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
		
		$formatter = ResourceFormatterFactory::getInstance("orgtenderattribute");
		
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
				"organization"=> array(
					"attributes" => array(
							"count" => $success_count,
							"attribute" => $ret
							)
				)
		);
		
	}
	
	private function saveTenders($data, $query_params)
	{
		global $gbl_item_status_codes;
		$flag = true;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';
		
		include_once 'apiController/ApiTenderController.php';
		$tenderController = new ApiTenderController();
		
		$success_count = 0;
		$total_count = 0;
		$paymentTendersArr = array();

		// these two lines are for safe handling in XML parsing
		//$tendersInputArr = $data["root"]["organization"] ? $data["root"]["organization"] : $data["root"]["tenders"][0]["tender"];
		$input = $data["root"]["organization"];
		$input = $input[0] && $input[0]["tenders"] ?$input[0] :$input; 
		$tendersInputArr = $input["tenders"]["tender"] ? $input["tenders"]["tender"] : $data["root"]["organization"]["tenders"][0]["tender"];
		
		$tendersInputArr = $tendersInputArr[0] ? $tendersInputArr : array($tendersInputArr) ;
		 
		if($tendersInputArr)
		{
			$formatter = ResourceFormatterFactory::getInstance("orgtender");
			
			$regex_length = strlen($tendersInputArr[0]['attributes']['attribute']['regex']);
			$error_msg_length = strlen($tendersInputArr[0]['attributes']['attribute']['error_msg']);
			$default_value_length = strlen($tendersInputArr[0]['attributes']['attribute']['default_value']);
			
			
			if ($regex_length > 255 || $error_msg_length > 255 || $default_value_length > 255) {
				$this->logger->debug("Text field (regex, error_msg or default_value) character limit exceeeded.");
				$api_status_code = 'INVALID_INPUT';
				$api_status = array(
						"success" => false,
						"code" => ErrorCodes::$api[$api_status_code],
						"message" => ErrorMessage::$api[$api_status_code]
				);
				$success_count = 0;
				$ret = array();
				$flag = false;
			}
			else {
				foreach($tendersInputArr as $tenderInput)
				{
					$type = strtoupper($tenderInput['attributes']['attribute']['data_type']);
					$default_value = $tenderInput['attributes']['attribute']['default_value'];
					
					switch ($type){
						case 'INT':
							if (!is_numeric($default_value))
							{
								$this->logger->debug("@@@p_change *Default_value type mismatch* ");
								$flag = false;
							}
							break;
							
						case 'FLOAT' :
							if (!is_float($default_value))
							{
								$this->logger->debug("@@@p_change *Default_value type mismatch* ");
								$flag = false;
							}
							break;
							
						case 'STRING':
							if (!is_string($default_value))
							{
								$this->logger->debug("@@@p_change *Default_value type mismatch* ");
								$flag = false;
							}
							break;
					}
					if (!$flag)
						break;
					
					try {
						$tenderFormattedArr = $formatter->readInput($tenderInput);
						$status = $tenderController->saveOrgPaymentModes($tenderFormattedArr);
						$tender = $tenderController->getOrgPaymentModes($tenderInput["name"]);
						$tender = $tender[0];
						$tender["message"] = ErrorMessage::$api[$api_status_code];
						$tender["success"] = true;
						$tender["code"] = 200;
						$paymentTendersArr []= $tender;
						$success_count++;
					} catch (Exception $e) {
						if(strtolower($tenderInput["action"])!= 'delete')
						{
							$tender = array();
							$tender["label"] = $tenderInput["name"];
							$tender["payment_mode_name"] = $tenderInput["canonical_name"];
							$tender["success"] = false;
							$tender["message"] = $e->getMessage();
							$tender["code"] = $e->getCode();
							$paymentTendersArr []= $tender;
						}
						else
						{
							$tender = array();
							$tender["label"] = $tenderInput["name"];
							$tender["success"] = true;
							$tender["message"] = "Tender deleted successfully";
							$tender["code"] = $e->getCode();
							$success_count++;
							$paymentTendersArr []= $tender;
						}
					}
				}
			}
		}
		else
		{
			include_once 'exceptions/ApiPaymentModeException.php';
			throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_PASSED);
		}
		
		if ($flag == false)
		{
			//return false;
			$api_status_code = 'INVALID_INPUT';
			$api_status = array(
					"success" => false,
					"code" => ErrorCodes::$api[$api_status_code],
					"message" => ErrorMessage::$api[$api_status_code]
			);
			$success_count = 0;
			$ret = array();
		}
		
		else{
			if($success_count == 0)
				$api_status_code = "FAIL";
			else if( $names && $success_count < count($names) )
				$api_status_code = "PARTIAL_SUCCESS";
			
			$ret = array();
			foreach($paymentTendersArr as $paymentTender)
			{
				// include based on request
				if($includeAttributes)
					$formatter->setIncludedFields("attributes");
				if($includeOptions)
					$formatter->setIncludedFields("options");
					
				if($paymentTender["success"])
					$item = $formatter->generateOutput($paymentTender);
				else
					$item = $paymentTender;
				$ret[] = $item;
			}
			
			$gbl_item_status_codes = implode(",", $arr_item_status_codes);
			$api_status = array(
					"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api["SUCCESS"] ? true : false,
					"code" => ErrorCodes::$api[$api_status_code],
					"message" => ErrorMessage::$api[$api_status_code]
			);
		}

		return  array(
				"status" => $api_status,
				"organization"=> array(
					"tenders" => array(
							"count" => $success_count,
							"tender" => $ret
							)
				)
		);
		
	}

	private function products($data, $query_params){
			
		$config_manager = new ConfigManager();
		$organization_controller = new ApiOrganizationController();
		$return_file = false;
		//batch size to fetch from solr
		$rows = 5000;
		
		if(isset($query_params['rows']) && $query_params['rows'])
			$rows = $query_params['rows'];
		
		if($query_params["start_date"] && $query_params["end_date"]){
			$filter = "(added_on:RANGE:".$query_params["start_date"].";".$query_params["end_date"].")";
		}
		
		if($query_params["start_date"] && !$query_params["end_date"]){
			$filter = "(added_on:GREATER:".$query_params["start_date"].")";
		}
		
		if(!$query_params["start_date"] && $query_params["end_date"]){
			$filter = "(added_on:LESS:".$query_params["end_date"].")";
		}
		
		//limit the number of products returned
		if(isset($query_params["limit"]) && $query_params["limit"]){
			$limit = $query_params["limit"];
		}
		
		//start index in solr, not id in the dbase
		if(isset($query_params["start"]) && $query_params["start"]){
			$start = $query_params["start"];			
		}
		else {
			$start = 0;
		}
		
		
		//order matters
		if(isset($query_params["delta"]) && $query_params["delta"] == "true"){
			$window=$config_manager->getKey('CONF_LOYALTY_SYNC_WINDOW_DAYS_CUSTOMERS');
			$window_filter = date('Y-m-d', strtotime('-0'.$window.' days'));
			$filter = "(added_on:GREATER:".$window_filter.")";
			
		}
		//delta > dump > start_date/end_date
		if(isset($query_params["dump"]) && $query_params["dump"] == "true" )
			$filter = "(sku:EQUALS:*)";
		
		if($query_params["dump"] == "true" || $query_params["delta"] == "true")
			$return_file = true;
		
		if(!$filter && (array_key_exists('start', $query_params) || array_key_exists('limit', $query_params)))
			$filter = "(sku:EQUALS:*)";
			
		if($return_file)
		{
			$file_ext = 'xml.gz';
			$cache_time = (isset(FileCachedApis::$file_cached_apis["organization"]["products"]['cache_time'])) ? FileCachedApis::$file_cached_apis["organization"]["products"]['cache_time'] : 0;
			$cache_file_manager = new CacheFileManager('organization', 'products', $cache_time, '', $file_ext , $query_params);
			$cache_file_path = $cache_file_manager->getCacheFilePath();
			$file_id = $cache_file_manager->createCacheFileEntry();
		}
		else{
			if( !$limit || $limit > 500 )
			$limit = 500;
		}
		
		$result = $organization_controller->getInventoryProducts($rows, $filter, $start, $cache_file_path, $return_file, $limit);
		
		if($return_file)
			return $cache_file_manager;
		
		return $result;
	}
	
	public function currencies($data, $query_params)
	{
		global $currentorg;
		$api_status_code = "SUCCESS";
		$ret = array();
		$org_model = new OrganizationModelExtension($currentorg->org_id);
        $org_model->load($currentorg->org_id);
        //$this->base_currency = SupportedCurrency::loadById();
		$organization_controller = new ApiOrganizationController();
		try{
			$currencies = $organization_controller->getCurrencies($query_params);
			$formatter = ResourceFormatterFactory::getInstance("orgcurrency");
			$item_status_code = 'ERR_CURRENCY_SUCCESS';
			foreach($currencies as $currency){
				if($currency["currency"]["supported_currency_id"] == $org_model->getBaseCurrency() ){
					$currency["base_currency"] = true;
				} else{
					$currency["base_currency"] = false;
				}
				$currency["item_status"] = array();
				$currency["item_status"]["message"] = ErrorMessage::$organization[$item_status_code];
				$currency["item_status"]["success"] = true;
				$currency["item_status"]["code"] = ErrorCodes::$organization[$item_status_code];
				$success_count++;
				$ret["currency"][] = $formatter->generateOutput($currency);
			}
		} catch (Exception $e){
			$item_status_code = 'ERR_CURRENCY_FAILURE';
			$currency["item_status"] = array();
			$currency["item_status"]["message"] = ErrorMessage::$organization[$item_status_code];
			$currency["item_status"]["success"] = false;
			$currency["item_status"]["code"] = ErrorCodes::$organization[$item_status_code];
			$ret["currency"][] = $currency;
		}
		
		if($success_count == 0)
			$api_status_code = "FAIL";
		else if( $values && $success_count < count($values) )
		$api_status_code = "PARTIAL_SUCCESS";
		
		$status = array(
			"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
			"code" => ErrorCodes::$api[$api_status_code],
			"message" => ErrorMessage::$api[$api_status_code]
		);
		
		
		
		$output = array(
			'status' => $status,
			'organization' => array(
				"count" => count($ret["currency"]),
				'currencies' => $ret)
		);
		return $output;
	}
	/**
	 * Checks if the system supports the version passed as input
	 *
	 * @param $version
	 */

	public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1', 'v1.1'))){
			return true;
		}
		return false;
	}

	public function checkMethod($method)
	{
		if(in_array(strtolower($method), array( 'configs', 'statistics', 'topitems', 'customers', 'customfields', 'entities', 'get' , 'tenders', 'triggers', 'prefix', 'products', 'currencies', 'livestats')))
		{
			return true;
		}
		return false;
	}
        
        private function triggers($http_method, $data, $query_params)
        {
            $orgController = new ApiOrganizationController();
            
            if(strtoupper($http_method) == 'GET')
            {
            	try
            	{
                	if(isset($query_params['id']))
                	{
                    	$this->logger->debug("Fetching source mapping id: " . $query_params['id']);
                    	$triggers = $orgController->getSourceMappings($query_params['id']);
                	}
                	else 
                	{
                    	$triggers = $orgController->getSourceMappings();
                	}
 					$this->logger->debug("no of triggers " . print_r(count($triggers['trigger']), true));
                	if (empty($triggers['trigger']) || $triggers==null) {
                		
               			$response = array(
                			"status" => array(
                    		"code" => ErrorCodes::$api["SUCCESS"],
                    		"success" => true,
                    		"message" => ErrorMessage::$api['SUCCESS']
                    		),
                		"triggers" => ''
                		);	
                		}
                	else {
                		$response = array(
                			"status" => array(
                    		"code" => ErrorCodes::$api["SUCCESS"],
                    		"success" => true,
                    		"message" => ErrorMessage::$api['SUCCESS']
                    		),
                			"triggers" => $triggers
                			);
            			}
                	return $response;
            	}
            	catch (Exception $ex){
            		$error_code = $ex->getMessage();
            		$response = array(
                        "status" => array(
                        "success"=>false,
                        "code" => ErrorCodes::$organization[$error_code],
                         "message" => ErrorMessage::$organization[$error_code]
                        )
                    	);	
            		return $response;
            		}
            	}
            elseif (strtoupper($http_method) == 'POST') {
            {
                try
                {
                    $this->logger->debug("Trigger to add/update " . print_r($data["root"]["trigger"], true));
                    $trigger = $orgController->addOrUpdateTrigger($data["root"]["trigger"]);
                    $add_or_update = isset($data["root"]["trigger"]["id"]) ? "updated" : "added";
                    $trigger["trigger"][0]["item_status"] = array("success" => true,
                                                                "code" => 200,
                                                                "message" => "Successfully $add_or_update trigger"
                                                                );
                    $response = array(
                        "status" => array(
                        "code" => ErrorCodes::$api["SUCCESS"],
                        "success" => true,
                        "message" => ErrorMessage::$api['SUCCESS']
                        ),
                        "triggers" => $trigger
                        );
                    
                } 
                catch (Exception $ex) {
                    $error_code = $ex->getMessage();
                    $trigger = $data["root"]["trigger"];
                    $item_status = array("success" => false,
                                        "code" => ErrorCodes::$organization[$error_code],
                                        "message" => ErrorMessage::$organization[$error_code]
                                        );
                    $trigger[0]['item_status'] = $item_status;
                    $response = array(
                        "status" => array(
                        "code" => ErrorCodes::$api["FAIL"],
                        "success" => false,
                        "message" => ErrorMessage::$api['FAIL']
                        ),
                        "triggers" => array("trigger" => $trigger)
                    );
                }
            }
            return $response;
        }
        }

    private function orgPrefix($http_method, $data, $query_params) {
        $response = $orgPrefix = array();
        
        $orgController = new ApiOrganizationController();
        
        if (strtoupper($http_method) == 'GET') {
            $this -> logger -> debug('Fetching org-prefix for this organization ');
            
            $orgPrefixObj = $orgController -> getOrgPrefix();

            if (isset($orgPrefixObj)) {
	            $orgPrefix = $orgPrefixObj -> toArray();
	            $orgPrefix['value'] = $orgPrefix['prefix'];
	            unset($orgPrefix['prefix']);

		        $response = array(
		            'status' => array(
		                'code' => ErrorCodes::$api['SUCCESS'],
		                'success' => true,
		                'message' => ErrorMessage::$api['SUCCESS']
		            ),
		            'prefix' => $orgPrefix
		        ); 
		    } else {
	            $itemStatus = array(
	            	'success' => false,
	                'code' => ErrorCodes::$organization['ERR_SOURCE_MAPPING_ORG_PREFIX_NOT_FOUND'],
	                'message' => ErrorMessage::$organization['ERR_SOURCE_MAPPING_ORG_PREFIX_NOT_FOUND']
	            );
	            $orgPrefix['item_status'] = $itemStatus;
	            $response = array(
	                'status' => array(
	                    'code' => ErrorCodes::$api['FAIL'],
	                    'success' => false,
	                    'message' => ErrorMessage::$api['FAIL']
	                ),
	                'prefix' => $orgPrefix
	            );
	        }
        } elseif (strtoupper($http_method) == 'POST') {
            $this -> logger -> debug('Add/Update Org-prefix for this organization');

            try {
                $orgPrefix = $orgController -> postOrgPrefix($data['root']['prefix']) -> toArray();
                $orgPrefix['value'] = $orgPrefix['prefix'];
            	unset($orgPrefix['prefix']);

                $addedOrUpdated = isset($data['root']['prefix']['id']) ? 'updated' : 'added';
                $orgPrefix['item_status'] = array(
					'success' => true,
                    'code' => 200,
                    'message' => "Successfully $addedOrUpdated organiztion-prefix"
                );
                $response = array(
                    'status' => array(
	                    'code' => ErrorCodes::$api['SUCCESS'],
	                    'success' => true,
	                    'message' => ErrorMessage::$api['SUCCESS']
	                ),
                	'prefix' => $orgPrefix
                );
            } catch (Exception $ex) {
                $errorCode = $ex -> getMessage();
                $orgPrefix = $data['root']['prefix'];
                $itemStatus = array(
                	'success' => false,
                    'code' => ErrorCodes::$organization[$errorCode],
                    'message' => ErrorMessage::$organization[$errorCode]
                );
                $orgPrefix['item_status'] = $itemStatus;
                $response = array(
                    'status' => array(
	                    'code' => ErrorCodes::$api['FAIL'],
	                    'success' => false,
	                    'message' => ErrorMessage::$api['FAIL']
                    ),
                    'prefix' => $orgPrefix
                );
            }
        }
    	return $response;
    }
}