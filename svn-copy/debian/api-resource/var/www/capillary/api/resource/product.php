<?php

require_once "resource.php";
//TODO: referes to cheetah
require_once "submodule/inventory.php";
require_once "apiController/ApiInventoryController.php";
require_once "models/inventory/InventoryStyle.php";
/**
 * Handles all product/inventory related api calls.
 * 	- Search Product
 *  - Get Product Attributes
 *  - Fetch Product Details
 *
 * @author pigol
 */

/**
 * @SWG\Resource(
 *     apiVersion="1.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/product",
 *     basePath="http://{{INTOUCH_ENDPOINT}}/v1.1"
 * )
 */
class ProductResource extends BaseResource{

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

				case 'add' :
						
					$result = $this->add($data, $query_params );
					break;
				
				case 'attributes' :
					if(strtoupper($http_method) == 'GET')
						$result = $this->attributes($query_params );
					else if(strtoupper($http_method) == 'POST')
					{
						$result = $this->saveAttributes($data, $query_params);
					}
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
					break;
					
				case 'get' :

					$result = $this->get( $query_params );
					break;

				case 'search' :
				
					$result = $this->search( $query_params );
					break;
				
				case 'styles':
					
					if(strtoupper($http_method) == 'GET') 
						$result = $this->getInventoryStyle($query_params);
					
					else if(strtoupper($http_method) == 'POST')
					{
						$result = $this->saveInventoryStyle($data, $query_params);
					}	
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
					break;
				
				case 'sizes':
					
					if(strtoupper($http_method) == 'GET') 
						$result = $this->getInventorySize($query_params);
					
					else if(strtoupper($http_method) == 'POST')
					{
						$result = $this->saveInventorySize($data, $query_params);
					}	
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
					break;
					

				case 'categories':
					if(strtoupper($http_method) == 'GET') 
						$result = $this->getInventoryCategories($query_params);
					
					else if(strtoupper($http_method) == 'POST')
					{
						$result = $this->saveInventoryCategories($data, $query_params);
					}	
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
					break;

				case 'brands':
					if(strtoupper($http_method) == 'GET') 
						$result = $this->getInventoryBrands($query_params);
					
					else if(strtoupper($http_method) == 'POST')
					{
						$result = $this->saveInventoryBrands($data, $query_params);
					}	
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
					break;
				
				case 'meta_sizes':
					if(strtoupper($http_method) == 'GET') 
						$result = $this->getInventoryMetaSizes($query_params);
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
					break;
				
				case 'colors':
					if(strtoupper($http_method) == 'GET') 
						$result = $this->getInventoryColors($query_params);
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
					break;
				
				case 'attribute_values':
					if(strtoupper($http_method) == 'GET') 
						$result = $this->getAttributeValues($query_params);
					else
						throw new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
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
	 * Adds a Product and its attributes
	 * @param array $data
	 * @param array $query_params
	 */
	
	/**
	 * @SWG\Model(
	 * id = "ProdAttr",
	 * @SWG\Property( name = "name", type = "string" ),
	 * @SWG\Property( name = "value", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "BrandObj",
	 * @SWG\Property( name = "name", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "ColorObj",
	 * @SWG\Property( name = "name", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CategoryObj",
	 * @SWG\Property( name = "name", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "StyleObj",
	 * @SWG\Property( name = "name", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "SizeObj",
	 * @SWG\Property( name = "name", type = "string" ),
	 * @SWG\Property( name = "type", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Product",
	 * @SWG\Property( name = "sku", type = "string" ),
	 * @SWG\Property( name = "ean", type = "string" ),
	 * @SWG\Property( name = "price", type = "float" ),
	 * @SWG\Property( name = "description", type = "string" ),
	 * @SWG\Property( name = "long_description", type = "string" ),
	 * @SWG\Property( name = "img_url", type = "string" ),
	 * @SWG\Property( name = "size", type = "SizeObj" ),
	 * @SWG\Property( name = "style", type = "StyleObj" ),
	 * @SWG\Property( name = "category", type = "CategoryObj" ),
	 * @SWG\Property( name = "color", type = "ColorObj" ),
	 * @SWG\Property( name = "brand", type = "BrandObj" ),
	 * @SWG\Property( name = "attributes", type = "array", items = "$ref:ProdAttr" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "AddRoot",
	 * @SWG\Property( name = "root", type = "array", items = "$ref:Product" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/product/add.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Add/update product and its attributes",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="AddRoot")
	 * ))
	 */
	private function add( $data,  $query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		
		$api_status_code = 'SUCCESS';
		
		if(isset($data['root']) && isset($data['root']['product']))
			$input = $data['root']['product'];
		else
		{
			$api_status_code = "FAIL";
			return array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
				); 
		}
		$response = array();
		$success_count = 0;
		foreach($input as $product)
		{
			try{
				$product_status_code = "ERR_PRODUCT_ADD_SUCCESS";
				$inventory_controller = new ApiInventoryController();
				$product = $inventory_controller->addProductToInventory($product);
				$inventory_attributes = array();
				foreach ($product['inventoryAttributes'] as $inv_attr) {
					 $attribute = array();
                     $attribute['name'] = $inv_attr['name'];
                     $attribute['value'] = $inv_attr["inventoryAttributeValue"]["value_name"];
					 $inventory_attributes['attribute'][] = $attribute;
				}
				$product = array('sku'=> $product['item_sku'],
								 'ean'=> $product['item_ean'],
								 'price'=> $product['price'],
								 'description'=> $product['description'],
								 'long_description' => $product['long_description'],
								 'img_url'=> $product['image_url'],
								 'size' => array("name" => $product["size"]["code"]),
								 'style' => array("name" => $product["style"]["code"]),
								 'brand' => array("name" => $product["brand"]["code"]),
								 'color' => array("name" => $product["color"]["name"]),
								 "category" => array("name" => $product["category"]["code"]),
								 'attributes'=> $inventory_attributes,
								 'item_status'=> array(
								 						"success" => (ErrorCodes::$product[$product_status_code] == ErrorCodes::$product["ERR_PRODUCT_ADD_SUCCESS"]) ? true : false,
                                               			"code" => ErrorCodes::$product[$product_status_code],
                                               			"message" => ErrorMessage::$product[$product_status_code]
                                               		  )							 
				);
				$arr_item_status_codes[] = $product['item_status']['status'];
				$success_count++;
			} catch (ApiInventoryException $e){
				$product_status_code = "ERR_PRODUCT_ADD_FAIL";
				$product = array('sku'=> $product['sku'],
								 'ean'=> $product['ean'],
								 'price'=> $product['price'],
								 'description'=> $product['description'],
								 'img_url'=> $product['image_url'],
								 'size_code' => $product["size"]["code"],
								 'style_code' => $product["style"]["code"],
								 'brand_code' => $product["brand"]["code"],
								 'color' => $product["color"]["name"],
								 "category" => $product["category"]["code"],
								 'attributes'=> $inventory_attributes,
								 'item_status'=>array(
									'success'=>false,
									'code' => ErrorCodes::$product[$product_status_code],
									'message' => ErrorMessage::$product[$product_status_code]
								)
						);
			} catch (Exception $e){
				$product_status_code = $e->getMessage();
				$product = array('sku'=> $product['sku'],
								 'ean'=> $product['ean'],
								 'price'=> $product['price'],
								 'description'=> $product['description'],
								 'img_url'=> $product['image_url'],
								 'size_code' => $product["size"]["code"],
								 'style_code' => $product["style"]["code"],
								 'brand_code' => $product["brand"]["code"],
								 'color' => $product["color"]["name"],
								 "category" => $product["category"]["code"],
								 'attributes'=> $inventory_attributes,
								'item_status'=>array(
								'success'=>(ErrorCodes::$product[$product_status_code] == ErrorCodes::$product['ERR_PRODUCT_ADD_SUCCESS'])?true : false,
								'code' => ErrorCodes::$product[$product_status_code],
								'message' => ErrorMessage::$product[$product_status_code]
							)
						);
			}
			array_push($response, $product);
		}

		if($success_count <= 0)
			$api_status_code = "FAIL";
		else if($success_count < count($input))
			$api_status_code = "PARTIAL_SUCCESS";
		
		$status = array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		return 	array(
						"status" => $status,
						"product" => $response
				);
	}
	
	/**
	 * Retrives Attributes of a Product
	 * @param array $query_params
	 */
	/**
	 * @SWG\Api(
	 * path="/product/attributes.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Returns list of attributes of all the products of the organization",
	 *     nickname = "Get Product Attributes"
	 *    )
	 * )
	 */
	private function attributes( $query_params)
	{
		$invController = new ApiInventoryController();
		$formatter = ResourceFormatterFactory::getInstance("inventoryattribute");
		$res = array();
		$api_status_code = 'SUCCESS';

		$success_count = 0;
		$total_count = 0;

		
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
		
		$values = in_array(strtolower($query_params["values"]), array(1, "1", true, "true"), true) ? true : false;
		
		if($values)
			{
			$formatter->setIncludedFields("basic");
			$formatter->setIncludedFields("attributevalue");
			}
		else {
			$formatter->setIncludedFields("basic");
		}
		
		if($query_params["name"])
			$query_params["code"] = $query_params["name"];
		// filter by code or id, specific saeach
		if($query_params["code"] || $query_params["id"])
		{
			$field = null; $values = null;
			if($query_params["id"]){
				$field = "id"; $values = $query_params["id"]; $query_params["code"] = null;
			}
			else {
				$field = "code"; $values = $query_params["code"]; $query_params["id"] = null;
			}
			$filters = $query_params;
			$values = explode(",", $values);

			foreach($values as $value)
			{
				$query_params[$field] = $value;
				try {
					$item_status_code = 'ERR_ATTR_SUCCESS';
					$inventoryValuesArr = $invController->getInventoryAttributes($query_params);
					$cat = $inventoryValuesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = true;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$cat = $formatter->generateOutput($cat);
					$success_count++;
					$res["attribute"][] = $cat;
				} catch (Exception $e) {
					$item_status_code = 'ERR_ATTR_FAILURE';
					$res["attribute"][] = array(
					$field => $value,
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code],
					);
				}

			}
		}
			
		else
		{
			try{
				$inventoryAttributeArr = $invController->getInventoryAttributes($query_params);
	
				if($inventoryAttributeArr)
				{
						
					foreach ($inventoryAttributeArr as $attribute)
					{
						$item_status_code = 'ERR_ATTR_SUCCESS';
						$attribute["item_status"] = array();
						$attribute["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
						$attribute["item_status"]["success"] = true;
						$attribute["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
						$success_count++;
						$res["attribute"][] = $formatter->generateOutput($attribute);
					}
	
				}
			} catch(Exception $e)
			{
				$item_status_code = 'ERR_ATTR_FAILURE';
				$res["attribute"][] = array(
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code],
					);
			}
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
			'status'=>$status,
				
			'product' => array(
				"count" => count($res["attribute"]),
				'attributes' => $res)
		);
		return $output;
		
		/*
		$api_status_code = 'SUCCESS';
		
		
		$C_inventory_controller = new ApiInventoryController();
		
		$attribute_list = $C_inventory_controller->getInventoryAttributes();

		if(is_array($attribute_list) && count($attribute_list) > 0)
		{
			foreach ($attribute_list as &$attribute)
			{
				$attribute['search'] = true;
			}
			foreach($attribute_list as $attribute)
			{
				
			}
			$attributes =  array(
									"attribute" => $attribute_list
								);
		}
		else
			$api_status_code = 'FAIL';
		
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == 200 ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		//NOTE: here we are not returning default attributes like: price, item_sku, description, img_url
		return 	array(
						"status" => $api_status,
						"product" => array(
									"attributes" => $attributes
								)
					);
		*/	
	}
	
	/**
	 * Returns Product based on 'sku' or 'id' that needs to be passed in $query_params
	 * @param array $query_params
	 */
	/**
	 * @SWG\Api(
	 * path="/product/get.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get details of the products",
	 *     nickname = "Get Product details",
	 *    @SWG\Parameter(name = "sku",type = "string",paramType = "query", description = "Product sku code" ),
	 *    @SWG\Parameter(name = "id",type = "string",paramType = "query", description = "Id assigned to product through InTouch" )
	 *    )
	 * )
	 */
	private function get( $query_params)
	{
		
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$C_inventory_controller = new ApiInventoryController();
		
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		
		$api_status_code = 'SUCCESS';

		$includeProductHierarchy =in_array(strtolower($query_params["include_hierarchy"]),
			array(1, "1", true, "true"), true) ? true : false;

		$this->logger->debug("InventoryMasterModels: Include hierarchy :".$includeProductHierarchy);
		$this->logger->debug("InventoryMasterModels: Include hierarchy :".$query_params["include_hierarchy"]);


		$identifier_type = 'id';
		$params = array();
		if(isset($query_params['id']))
		{
            $query_params['id'] = trim($query_params['id'], ",");
			$ids = explode(",", $query_params['id']);
			$identifier_type = 'id';
			$params[$identifier_type] = $ids;
			$this->logger->debug("Going to search by Id: ".print_r($params[$identifier_type], true));
		}
		else if(isset($query_params['sku']))
		{
            $query_params['sku'] = trim($query_params['sku'], ",");
			$skus = explode(",", $query_params['sku']);
			$identifier_type = 'sku';
			$params[$identifier_type] = $skus;
			$this->logger->debug("Going to search by Id: ".print_r($params[$identifier_type], true));
		}
		
		/*if(isset($query_params['id']))
		{
			$ids = explode(",", $query_params['id']);
			$this->logger->debug("Going to search by Id: ".print_r($ids, true));
		}
		else if(isset($query_params['sku']))
		{
			$skus = explode(",", $query_params['sku']);
			$this->logger->debug("Going to search by sku: ".print_r($skus, true));
		}*/
		
		$items = array();
		$success_count = 0;
		$total_count = 0;
		foreach($params[$identifier_type] as $id)
		{
			$item = array();
			$item_status_code = "ERR_PRODUCT_SUCCESS";
			try
			{
				if($identifier_type == 'id')
					$item = $C_inventory_controller->getProductById($id, $includeIds,$includeProductHierarchy);
				else if($identifier_type == 'sku')
					$item = $C_inventory_controller->getProductBySku($id, $includeIds,$includeProductHierarchy);
				$success_count++;
			}
			catch(Exception $e)
			{
				$this->logger->error("ERROR: ".$e->getMessage());
				$item_status_code = "ERR_PRODUCT_FAILURE";
				$item[$identifier_type] = $id;
				$item['price'] = 0;
				$item['img_url'] = "";
				$item['in_stock'] = false;
				$item['description'] = "";
			}
			//$item['id'] = $id;//TODO: ask weather it is required or not.
			
			$item['item_status'] = array(
					"status" => ErrorCodes::$product[ $item_status_code ] ==
					ErrorCodes::$product[ 'ERR_PRODUCT_SUCCESS' ] ? true : false,
					"code" => ErrorCodes::$product[ $item_status_code ],
					"message" => ErrorMessage::$product[ $item_status_code ]
			);
			
			$arr_item_status_codes[] = $item['item_status']['code'];
			array_push($items, $item);
			$total_count++;
		
			unset($item);
		}
		
		if($success_count == 0)
			$api_status_code = "FAIL";
		else if( $success_count < $total_count )
			$api_status_code = "PARTIAL_SUCCESS";
		
		if(isset($C_inventory_controller))
			unset($C_inventory_controller);
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == 
					ErrorCodes::$api["SUCCESS"] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		return  array(
						"status" => $api_status,
						"product" => array(
								"item" => $items
						)
				);
		
	}
	
	/**
	 * Searches and Returns the Product based on the different parameters passed as $query_params
	 * @param array $query_params
	 */
	private function search( $query_params)
	{
		include_once 'apiController/ApiSearchController.php';
	    
	    $search_cntrl = new ApiSearchController('users');
	    $query = $query_params['q'];
	    $start = $query_params['start'];
	    $rows = $query_params['rows'];
            //Only return the distinct field values queried for
            $distinct_filed = $query_params["return_distinct"];
        include_once 'helper/StringUtils.php';
        $is_primary = ( StringUtils::strriposition($query, BaseQueryBuilder::$PRIMARY_SEARCH) === false ) ? false : true;
	    $results = $search_cntrl->searchProducts($query, $start, $rows, $is_primary, $distinct_filed);
	    return $results;
	}		
	
	/**
	 * @SWG\Api(
	 * path="/product/styles.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetch style details",
	 *    @SWG\Parameter(
	 *    name = "name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Style name"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "include_id",
	 *    type = "integer",
	 *    paramType = "query",
	 *	  description = "Include style id in response if set as 1"
	 *    )
	 * ))
	 */
	public function getInventoryStyle($query_params)
	{
		global $gbl_item_status_codes, $currentorg;
		$invController = new ApiInventoryController();
		$api_status_code = 'SUCCESS';

		$success_count = 0;
		$total_count = 0;
		$formatter = ResourceFormatterFactory::getInstance('inventorystyle');
		
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");

		if($query_params["name"])
			$query_params["code"] = $query_params["name"];
		if($query_params["code"] || $query_params["id"])
		{
			$field = null; $values = null;
			if($query_params["id"]){
				$field = "id"; $values = $query_params["id"]; $query_params["code"] = null;
			}
			else {
				$field = "code"; $values = $query_params["code"]; $query_params["id"] = null;
			}
			$filters = $query_params;
			$values = explode(",", $values);

			foreach($values as $value)
			{
				$item_status_code = 'ERR_STYLE_SUCCESS';
				$query_params[$field] = $value;
				try {
					$sizesArr = $invController->getInventoryStyles($query_params);
					$cat = $sizesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = true;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$cat = $formatter->generateOutput($cat);
					$success_count++;
					$res["style"][] = $cat;
				} catch (Exception $e) {
					$item_status_code = 'ERR_STYLE_FAILURE';
					$cat = array($field == "code" ? "name" : $value =>$value);
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = fail;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$res["style"][] = $cat;
				}

			}
		}
		else
		{
			try{
				$sizesArr = $invController->getInventoryStyles($query_params);
				
				if($sizesArr)
				{
						
					foreach ($sizesArr as $size)
					{
						$item_status_code = 'ERR_STYLE_SUCCESS';
						$size["item_status"] = array();
						$size["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
						$size["item_status"]["success"] = true;
						$size["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
						$success_count++;
						$res["style"][] = $formatter->generateOutput($size);
						
					}
	
				}
			}
			catch(Exception $e){
				$item_status_code = 'ERR_STYLE_FAILURE';
			}
		}
		/*
		if(isset($query_params['id']) && $query_params['id'])
		{
			$ids = explode(",", $query_params["id"]);
			try{
				foreach($ids as $id)
				{
					try{
						$obj = $style->loadById($currentorg->org_id, $id);
						$response['style'][] = $obj->toArray();
					}
					catch (Exception $e){
						
					}
					
				}
			}catch (Exception $e){
				$response = array(
						'item_status' => array(
							"success" => (ErrorCodes::$style[$style_status_code] == ErrorCodes::$style["ERR_STYLE_ADD_SUCCESS"]) ? true : false,
		                	"code" => ErrorCodes::$style[$style_status_code],
		                	"message" => ErrorMessage::$style[$style_status_code]
						)
					);
				
			}
		}
		if(count($query_params)<=1){
			try{
				$style_status_code = 'ERR_STYLE_GET_SUCCESS';
				$styleArr = $inventory_controller->getInventoryStyles(null);
				foreach($styleArr as $style)
				{
					$res["item_status"]["success"] = (ErrorCodes::$style[$style_status_code] == ErrorCodes::$style["ERR_STYLE_GET_SUCCESS"]) ? true : false;
					$res["item_status"]["code"] = ErrorCodes::$style[$style_status_code];
					$res["item_status"]["message"] = ErrorMessage::$style[$style_status_code];
					$res = $formatter->generateOutput($style);
					$response[] = $res;
				}
			}
			catch (Exception $e){
				$style_status_code = 'ERR_STYLE_GET_FAIL';
			}	
		}
		*/ 
		

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
			'product' => array(
				"count" => count($res["style"]),
				'styles' => $res)
		);
		return $output;
	}
	
	/**
	 * @SWG\Model(
	 * id = "Style",
	 * @SWG\Property( name = "name", type = "string", description = "Style name" ),
	 * @SWG\Property( name = "label", type = "string", description = "Style label" ),
	 * @SWG\Property( name = "description", type = "string", description = "Description" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Styles",
	 * @SWG\Property( name = "styles", type = "array", items = "$ref:Style" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "StyleProduct",
	 * @SWG\Property( name = "product", type = "Styles" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "StyleRoot",
	 * @SWG\Property( name = "root", type = "StyleProduct" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/product/styles.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Add new or update existing styles",
	 *     nickname = "Post Styles",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="StyleRoot")
	 * ))
	 */
	public function saveInventoryStyle($data, $query_params)
	{
		global $gbl_item_status_codes, $currentorg;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';
		
		if(isset($data['root']) && isset($data['root']['product'][0]['styles']['style']))
			$input=isset($data['root']["product"][0]["styles"]["style"][0])?$data["root"]["product"][0]["styles"]["style"]:array($data["root"]["product"][0]["styles"]["style"]);
		else
		{
			$api_status_code = "FAIL";
			return array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
				); 
		}
		
		$response = array();
		$inventory_controller = new ApiInventoryController();
		foreach($input as $style)
		{	$formatter = ResourceFormatterFactory::getInstance('inventorystyle');
			$errorMsg = "";
			try{
				
				$res = $inventory_controller->saveStyleToInventory($formatter->readInput($style));
				$style_status_code = "ERR_STYLE_ADD_SUCCESS";
				$success_count++;
			} catch(ApiInventoryException $e){
				$style_status_code = "ERR_STYLE_ADD_FAIL";
				$res["name"] = $style["code"];
				$res["description"] = $style["description"];
				$res["label"] = $style["label"];
				$errorMsg = " - " .$e->getMessage();
			} catch (Exception $e){
				$style_status_code = $e->getMessage();
				$res["name"] = $style["name"];
				$res["description"] = $style["description"];
				$res["label"] = $style["label"];
			}
			$res["item_status"]["success"] = (ErrorCodes::$style[$style_status_code] == ErrorCodes::$style["ERR_STYLE_ADD_SUCCESS"]) ? true : false;
			$res["item_status"]["code"] = ErrorCodes::$style[$style_status_code];
			$res["item_status"]["message"] = ErrorMessage::$style[$style_status_code] . $errorMsg;
			$res = $formatter->generateOutput($res);
			$response[]=$res;
		}
		if($success_count <= 0)
			$api_status_code = "FAIL";
		else if($success_count < count($input))
			$api_status_code = "PARTIAL_SUCCESS";
			
		$status = array(
			"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
			"code" => ErrorCodes::$api[$api_status_code],
			"message" => ErrorMessage::$api[$api_status_code]
		);
		
		$output = array(
			'status' => $status,
			'product' => array("styles" => array('style' => $response)));
		return $output;
	}

	/**
	 * @SWG\Model(
	 * id = "Size",
	 * @SWG\Property( name = "name", type = "string", description = "Size name" ),
	 * @SWG\Property( name = "label", type = "string", description = "Size label" ),
	 * @SWG\Property( name = "canonical_name", type = "string", description = "Canonical size name" ),
	 * @SWG\Property( name = "size_family", type = "string", description = "Size family" ),
	 * @SWG\Property( name = "type", type = "string", description = "Size type" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Sizes",
	 * @SWG\Property( name = "sizes", type = "array", items = "$ref:Size" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "SizeProduct",
	 * @SWG\Property( name = "product", type = "Sizes" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "SizeRoot",
	 * @SWG\Property( name = "root", type = "SizeProduct" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/product/sizes.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Add new or update existing sizes",
	 *     nickname = "Post Sizes",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="SizeRoot")
	 * ))
	 */
	public function saveInventorySize($data, $query_params)
	{
		global $gbl_item_status_codes, $currentorg;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';
		$input = $data;
		if(isset($data['root']) && isset($data['root']['product'][0]['sizes']['size']))
			$input=isset($data['root']["product"][0]["sizes"]["size"][0])?$data["root"]["product"][0]["sizes"]["size"]:array($data["root"]["product"][0]["sizes"]["size"]);
		else
		{
			$api_status_code = "FAIL";
			return array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
				); 
		}
		$response = array();
		$inventory_controller = new ApiInventoryController();
		$formatter = ResourceFormatterFactory::getInstance('inventorysize');
		foreach($input as $size)
		{	
			$errorMsg ="";
			try{
				$res = $inventory_controller->saveSizeToInventory($formatter->readInput($size));
				$size_status_code = "ERR_SIZE_ADD_SUCCESS";
				$success_count++;
				$item_code = ErrorCodes::$product[$size_status_code];
				$item_description = ErrorMessage::$product[$size_status_code]; 
			} catch(ApiInventoryException $e){
				$size_status_code = "ERR_SIZE_ADD_FAIL";
				$item_description = ErrorMessage::$product[$size_status_code];
				$item_code = ErrorCodes::$product[$size_status_code];
				$res["name"] = $size["code"];
				$res["label"] = $size["name"];
				$res["meta_size"]["code"] = $size["canonical_code"];
				$res["meta_size"]["type"] = $size["type"];
				$res["meta_size"]["size_family"] = $size["size_family"];
				$errorMsg = " - " .$e->getMessage();
			} catch (Exception $e){
				$res["name"] = $size["code"];
				$res["label"] = $size["name"];
				$res["meta_size"]["code"] = $size["canonical_code"];
				$res["meta_size"]["type"] = $size["type"];
				$res["meta_size"]["size_family"] = $size["size_family"];
				$size_status_code = $e->getMessage();
				$item_code = ErrorCodes::$product[$size_status_code];;
				$item_description = ErrorMessage::$product[$size_status_code];
			}
			$res["item_status"] = array();
			$res["item_status"]["success"] = (ErrorCodes::$product[$size_status_code] == ErrorCodes::$product["ERR_SIZE_ADD_SUCCESS"]) ? true : false;
			$res["item_status"]["code"] = $item_code;
			$res["item_status"]["message"] = $item_description . $errorMsg;
			$res = $formatter->generateOutput($res);
			$response[]=$res;
		}
		if($success_count <= 0)
			$api_status_code = "FAIL";
		else if($success_count < count($input))
			$api_status_code = "PARTIAL_SUCCESS";
			
		$status = array(
			"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
			"code" => ErrorCodes::$api[$api_status_code],
			"message" => ErrorMessage::$api[$api_status_code]
		);
		
		$output = array(
			'status' => $status,
			'product' => array("sizes" => array('size' => $response)));
		return $output;
		
		
	}
	
	/**
	 * @SWG\Api(
	 * path="/product/sizes.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetch size details",
	 *    @SWG\Parameter(
	 *    name = "id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Size id"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "include_id",
	 *    type = "integer",
	 *    paramType = "query",
	 *	  description = "Include size id in response if set as 1"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Size name"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "type",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Size type"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "canonical_name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Canonical name of the size"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "size_family",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Size family"
	 *    )
	 * ))
	 */
	public function getInventorySize($query_params)
	{
		$invController = new ApiInventoryController();
		$formatter = ResourceFormatterFactory::getInstance("inventorysize");
		$res = array();
		$api_status_code = 'SUCCESS';

		$success_count = 0;
		$total_count = 0;

		
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
			
		//if($query_params["name"])
		//	$query_params["code"] = $query_params["name"];
			
		// filter by code or id, specific saeach
		if($query_params["id"])
		{
			$field = null; $values = null;
			if($query_params["id"]){
				$field = "id"; $values = $query_params["id"]; $query_params["code"] = null;
			}
			//else {
			//	$field = "code"; $values = $query_params["name"] ? $query_params["name"] : $query_params["code"] ; $query_params["id"] = null;
			//}
			$filters = $query_params;
			$values = explode(",", $values);

			foreach($values as $value)
			{
				$item_status_code = 'ERR_SIZE_SUCCESS';
				$query_params[$field] = $value;
				try {
					$sizesArr = $invController->getSizes($query_params);
					$cat = $sizesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = true;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$cat = $formatter->generateOutput($cat);
					$success_count++;
					$res["size"][] = $cat;
				} catch (Exception $e) {
					$item_status_code='ERR_SIZE_FAILURE';
					//$cat = array(($field== "code" ? "name" : $field) => $value);
					//$cat = $sizesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = false;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$res["size"][] = $cat;
				}

			}
		}
			
		else
		{
			try{
				$sizesArr = $invController->getSizes($query_params);
	
				if($sizesArr)
				{
					$item_status_code = 'ERR_SIZE_SUCCESS';	
					foreach ($sizesArr as $size)
					{
						$size["item_status"] = array();
						$size["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
						$size["item_status"]["success"] = true;
						$size["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
						$success_count++;
						$res["size"][] = $formatter->generateOutput($size);
					}
	
				}
			} catch(Exception $e)
			{
				$item_status_code='ERR_SIZE_FAILURE';
					//$cat = array(($field== "code" ? "name" : $field) => $value);
					//$cat = $sizesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = false;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$res["size"][] = $cat;
					/*$item_status_code = 'ERR_SIZE_FAILURE';
					$res["size"][] = array(
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code]
					);*/	
			}
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
			'product' => array(
				"count" => count($res["size"]),
				'sizes' => $res)
		);
		
		return $output;
		
	}

	/**
	 * @SWG\Api(
	 * path="/product/categories.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetch category details",
	 *    @SWG\Parameter(
	 *    name = "name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Style name"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "include_id",
	 *    type = "integer",
	 *    paramType = "query",
	 *	  description = "Set as 1 to return category id in response"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "parent_name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Parent category name"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "values",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "To list sub-category values"
	 *    )
	 * ))
	 */
	public function getInventoryCategories($query_params)
	{
		$invController = new ApiInventoryController();
		$formatter = ResourceFormatterFactory::getInstance("inventorycategory");
		$res = array();
		$api_status_code = 'SUCCESS';

		$success_count = 0;
		$total_count = 0;

		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
		
		$includeValues = in_array(strtolower($query_params["values"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeValues)
			$formatter->setIncludedFields("values");
						
		$includeHeirarchy = in_array(strtolower($query_params["include_hierarchy"]), array(1, "1", true, "true"), true) ? true :
 false;
                if($includeHeirarchy)
                    $formatter->setIncludedFields("category_hierarchy");
	
		if($query_params["name"])
			$query_params["code"] = $query_params["name"];
		if($query_params["parent_name"])
			$query_params["parent_code"] = $query_params["parent_name"];
        if($query_params["catalog"])
            $query_params["parent_code"] = $query_params["catalog"];
			
			
		// filter by code or id, specific saeach
		if($query_params["code"] || $query_params["id"])
		{
			$field = null; $values = null;
			if($query_params["id"]){
				$field = "id"; $values = $query_params["id"]; $query_params["code"] = null;
			}
			else {
				$field = "code"; $values = $query_params["code"]; $query_params["id"] = null;
			}
			$filters = $query_params;
			$values = explode(",", $values);

			foreach($values as $value)
			{
				$query_params[$field] = $value;
				try {
					$categoriesArr = $invController->getCategories($query_params, $includeValues);
					$cat = $categoriesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product['ERR_CATEGORY_SUCCESS'];
					$cat["item_status"]["success"] = true;
					$cat["item_status"]["code"] = ErrorCodes::$product['ERR_CATEGORY_SUCCESS'];
					
					$cat = $formatter->generateOutput($cat);
					$success_count++;
					$res["category"][] = $cat;
				} catch (Exception $e) {
					$res["category"][] = array(
					$field => $value,
					"item_status" => array(
						"success" => false,
						"message" => ErrorMessage::$product['ERR_CATEGORY_FAILURE'],
						"code" => ErrorCodes::$product['ERR_CATEGORY_FAILURE'],
						)
					);
				}
			}
		}
			
		else
		{

			$categoriesArr = $invController->getCategories($query_params, $includeValues);

			if($categoriesArr)
			{
					
				foreach ($categoriesArr as $category)
				{
					$category["item_status"]["message"] = ErrorMessage::$product['ERR_CATEGORY_SUCCESS'];
					$category["item_status"]["success"] = true;
					$category["item_status"]["code"] = ErrorCodes::$product['ERR_CATEGORY_SUCCESS'];
					$success_count++;
					$cat = $formatter->generateOutput($cat);
					
					$res["category"][] = $formatter->generateOutput($category);
				}

			}
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
			'product' => array(
				"count" => count($res["category"]),
				'categories' => $res)
		);
		return $output;

	}

	/**
	 * @SWG\Model(
	 * id = "Category",
	 * @SWG\Property( name = "name", type = "string", description = "Category name" ),
	 * @SWG\Property( name = "label", type = "string", description = "Category label" ),
	 * @SWG\Property( name = "description", type = "string", description = "Category description" ),
	 * @SWG\Property( name = "parent_name", type = "string", description = "Parent category name" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Categories",
	 * @SWG\Property( name = "categories", type = "array", items = "$ref:Category" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CategoryProduct",
	 * @SWG\Property( name = "product", type = "Categories" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CategoryRoot",
	 * @SWG\Property( name = "root", type = "CategoryProduct" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/product/categories.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Add new or update existing categories",
	 *     nickname = "Post Category",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="CategoryRoot")
	 * ))
	 */
	public function saveInventoryCategories($data, $query_params)
	{

		global $gbl_item_status_codes, $currentorg;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';

		$formatter = ResourceFormatterFactory::getInstance('inventoryCategory');
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
			
		
		if(isset($data['root']) && isset($data['root']['product'])
			&& isset($data['root']['product'][0]['categories']) )
			$input = $data['root']['product'][0]['categories']['category'];
		else
		{
			$api_status_code = "FAIL";
			return array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
			);
		}

		if(!isset($input[0]))
			$input =  array($input);
		$response = array();
		
		$inventory_controller = new ApiInventoryController();
		foreach($input as $row)
		{
			$errorMsg ="";
			try{

				$res = $inventory_controller->saveCategory($formatter->readInput($row));
				$cat_status_code = "ERR_CATEGORY_ADD_SUCCESS";
				$success_count++;
			} catch(ApiInventoryException $e){
				$cat_status_code = "ERR_CATEGORY_ADD_FAILURE";
				$res["code"] = $row["code"];
				$errorMsg = " - ". $e->getMessage();
				$res["description"] = $row["description"];
			} catch (Exception $e){
				$cat_status_code = "ERR_CATEGORY_ADD_FAILURE";
				$res["code"] = $row["code"];
				$res["description"] = $row["description"];
			}
			$res["item_status"]["success"] = (ErrorCodes::$product[$cat_status_code] == ErrorCodes::$product["ERR_CATEGORY_ADD_SUCCESS"]) ? true : false;
			$res["item_status"]["code"] = ErrorCodes::$product[$cat_status_code];
			$res["item_status"]["message"] = ErrorMessage::$product[$cat_status_code] . $errorMsg;
			$res = $formatter->generateOutput($res);
			$response[]=$res;
		}
		if($success_count <= 0)
		$api_status_code = "FAIL";
		else if($success_count < count($input))
		$api_status_code = "PARTIAL_SUCCESS";
			
		$status = array(
			"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
			"code" => ErrorCodes::$api[$api_status_code],
			"message" => ErrorMessage::$api[$api_status_code]
		);

		$output = array(
			'status' => $status,
			'product' => array("categories" => array("category" => $response))
		);
		return $output;
	}

	/**
	 * @SWG\Api(
	 * path="/product/brands.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetch brand details",
	 *    @SWG\Parameter(
	 *    name = "id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Brand id"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Style name"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "include_id",
	 *    type = "integer",
	 *    paramType = "query",
	 *	  description = "Include id in response if set as 1"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "parent_id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Include parent id"
	 *    )
	 * ))
	 */
	public function getInventoryBrands($query_params)
	{
		$invController = new ApiInventoryController();
		$formatter = ResourceFormatterFactory::getInstance("inventoryBrand");
		$res = array();
		$api_status_code = 'SUCCESS';

		$success_count = 0;
		$total_count = 0;


		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
		
		$includeValues = in_array(strtolower($query_params["values"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeValues)
			$formatter->setIncludedFields("values");
                $includeHeirarchy = in_array(strtolower($query_params["include_hierarchy"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeHeirarchy)
                    $formatter->setIncludedFields("brand_hierarchy");			
			
		if($query_params["name"])
			$query_params["code"] = $query_params["name"];
        if($query_params["parent_name"])
            $query_params["parent_code"] = $query_params["parent_name"];
        if($query_params["catalog"])
            $query_params["parent_code"] = $query_params["catalog"];
			
		// filter by code or id, specific saeach
		if($query_params["code"] || $query_params["id"])
		{
			$field = null; $values = null;
			if($query_params["id"]){
				$field = "id"; $values = $query_params["id"]; $query_params["code"] = null;
			}
			else {
				$field = "code"; $values = $query_params["code"]; $query_params["id"] = null;
			}
			$filters = $query_params;
			$values = explode(",", $values);

			foreach($values as $value)
			{
				$query_params[$field] = $value;
				try {
					$item_status_code = 'ERR_BRAND_SUCCESS';
					$categoriesArr = $invController->getBrands($query_params);
					$cat = $categoriesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = true;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$cat = $formatter->generateOutput($cat);
					$success_count++;
					$res["brand"][] = $cat;
				} catch (Exception $e) {
					$item_status_code = 'ERR_BRAND_FAILURE';
					$cat = array();
					$cat[$field == "code" ? "name" : $field] = $value;
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = FALSE;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$res["brand"][] = $cat;
				}

			}
		}
			
		else
		{
			try{
				
				$categoriesArr = $invController->getBrands($query_params);
	
	
				if($categoriesArr)
				{
					$item_status_code = 'ERR_BRAND_SUCCESS';		
					foreach ($categoriesArr as $category)
					{
						$category["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
						$category["item_status"]["success"] = true;
						$category["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
						$success_count++;
						$cat = $formatter->generateOutput($cat);
						
						$res["brand"][] = $formatter->generateOutput($category);
					}
	
				}
			} catch (Exception $e)
			{
				$item_status_code = 'ERR_BRAND_FAILURE';
				$res["brand"]["item_status"] = array(
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code],
					);
			}
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
			'product' => array(
				"count" => count($res["brand"]),
				'brands' => $res)
		);
		return $output;

	}

	/**
	 * @SWG\Api(
	 * path="/product/colors.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetch color details",
	 *    @SWG\Parameter(
	 *    name = "pallette",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Pallette in hex"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "limit",
	 *    type = "integer",
	 *    paramType = "query",
	 *	  description = "Number of colors"
	 *    )
	 * ))
	 */
	public function getInventoryColors($query_params)
	{
		$invController = new ApiInventoryController();
		$formatter = ResourceFormatterFactory::getInstance("inventorycolor");
		$res = array();
		$api_status_code = 'SUCCESS';
		$success_count = 0;
		$total_count = 0;

		
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
			
		// filter by code or id, specific saeach
		if($query_params["pallette"])
		{
			
			$field = null; $values = null;
			if($query_params["pallette"]){
				$field = "pallette"; $values = $query_params["pallette"];
			}
			
			$filters = $query_params;
			$values = explode(",", $values);

			foreach($values as $value)
			{
				$query_params[$field] = hexdec($value);
				$item_status_code = "ERR_COLOR_SUCCESS";
				try {
					$colorsArr = $invController->getColors($query_params);
					$cat = $colorsArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = true;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$cat = $formatter->generateOutput($cat);
					$success_count++;
					$res["color"][] = $cat;
				} catch (Exception $e) {
					$item_status_code = "ERR_COLOR_FAILURE";
					$res["color"][] = array(
					$field => $value,
					"item_status" => array(
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code],
						)
					);

				}
				
			}
		}
			
		else
		{
			try {
				$colorsArr = $invController->getColors($query_params);	
			} catch (Exception $e) {
			}
			

			if($colorsArr)
			{
					
				foreach ($colorsArr as $color)
				{
					$item_status_code = "ERR_COLOR_SUCCESS";
					$color["item_status"] = array();
					$color["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$color["item_status"]["success"] = true;
					$color["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					$success_count++;
					$res["color"][] = $formatter->generateOutput($color);
				}

			}
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
			'product' => array(
				"count" => count($res["color"]),
				'colors' => $res)
		);
		return $output;
	}
	
	/**
	 * @SWG\Api(
	 * path="/product/meta_sizes.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetch meta-size details",
	 *    @SWG\Parameter(
	 *    name = "include_id",
	 *    type = "integer",
	 *    paramType = "query",
	 *	  description = "Include meta-size id in response if set as 1"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Meta-size name"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "type",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Meta-size type"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "size_family",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Meta-size family"
	 *    )
	 * ))
	 */
	public function getInventoryMetaSizes($query_params)
	{
		$invController = new ApiInventoryController();
		$formatter = ResourceFormatterFactory::getInstance("inventorymetasize");
		$res = array();
		$api_status_code = 'SUCCESS';

		$success_count = 0;
		$total_count = 0;

		
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
			
		// filter by code or id, specific saeach
		if($query_params["id"])
		{
			$field = null; $values = null;
			if($query_params["id"]){
				$field = "id"; $values = $query_params["id"]; $query_params["code"] = null;
			}
			$filters = $query_params;
			$values = explode(",", $values);

			foreach($values as $value)
			{
				$query_params[$field] = $value;
				try {
					$item_status_code = 'ERR_META_SIZE_SUCCESS';
					$metaSizesArr = $invController->getMetaSizes($query_params);
					$cat = $metaSizesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = true;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$cat = $formatter->generateOutput($cat);
					$success_count++;
					$res["meta_size"][] = $cat;
				} catch (Exception $e) {
					$item_status_code = 'ERR_META_SIZE_FAILURE';
					$res["meta_size"][] = array(
					$field => $value,
					"item_status" => array(
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code],
						)
					);
				}

			}
		}
			
		else
		{
			
			try{
				$metaSizesArr = $invController->getMetaSizes($query_params);
	
				if($metaSizesArr)
				{
					$item_status_code = 'ERR_META_SIZE_SUCCESS';
					foreach ($metaSizesArr as $meta_size)
					{
						$size["item_status"] = array();
						$size["item_status"]["message"] = ErrorMessage::$product[$api_status_code];
						$size["item_status"]["success"] = true;
						$size["item_status"]["code"] = $product[$item_status_code];
						$success_count++;
						$res["meta_size"][] = $formatter->generateOutput($meta_size);
					}
	
				}
			} catch(Exception $e)
			{
				$item_status_code = 'ERR_META_SIZE_FAILURE';
				$res["meta_size"][] = array(
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code],
				);
			}
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
			'product' => array(
				"count" => count($res["meta_size"]),
				'meta_sizes' => $res)
		);
		return $output;
	}
	
	/**
	 * @SWG\Model(
	 * id = "Brand",
	 * @SWG\Property( name = "label", type = "string", description = "Brand label" ),
	 * @SWG\Property( name = "name", type = "string", required = true, description = "Brand name" ),
	 * @SWG\Property( name = "description", type = "string", description = "Description" ),
	 * @SWG\Property( name = "parent_name", type = "string", description = "Parent brand name" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Brands",
	 * @SWG\Property( name = "brands", type = "array", items = "$ref:Brand" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "BrandProduct",
	 * @SWG\Property( name = "product", type = "Brands" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "BrandRoot",
	 * @SWG\Property( name = "root", type = "BrandProduct" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/product/brands.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Add new or update existing brands",
	 *     nickname = "Post Brand",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="BrandRoot")
	 * ))
	 */
	public function saveInventoryBrands($data, $query_params)
	{

		global $gbl_item_status_codes, $currentorg;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';

		$formatter = ResourceFormatterFactory::getInstance('inventoryBrand');
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
			
		
		if(isset($data['root']) && isset($data['root']['product'])
			&& isset($data['root']['product'][0]['brands']) )
			$input = $data['root']['product'][0]['brands']['brand'];
		else
		{
			$api_status_code = "FAIL";
			return array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
			);
		}

		if(!isset($input[0]))
			$input =  array($input);
		$response = array();
		$inventory_controller = new ApiInventoryController();
		foreach($input as $row)
		{
			$errorMsg = "";
			try{

				$res = $inventory_controller->saveBrand($formatter->readInput($row));

				$style_status_code = "ERR_BRAND_ADD_SUCCESS";
				$success_count++;
			} catch(ApiInventoryException $e){
				$style_status_code = "ERR_BRAND_ADD_FAILURE";
				$res["code"] = $row["code"];
				$errorMsg = " - ". $e->getMessage();
				$res["description"] = $row["description"];
			} catch (Exception $e){
				$style_status_code = "ERR_BRAND_ADD_FAILURE";
				$res["code"] = $row["code"];
				$res["description"] = $row["description"];
			}
			$res["item_status"]["success"] = (ErrorCodes::$product[$style_status_code] == ErrorCodes::$product["ERR_BRAND_ADD_SUCCESS"]) ? true : false;
			$res["item_status"]["code"] = ErrorCodes::$product[$style_status_code];
			$res["item_status"]["message"] = ErrorMessage::$product[$style_status_code] . $errorMsg;
			$res = $formatter->generateOutput($res);
			$response[]=$res;
		}
		if($success_count <= 0)
		$api_status_code = "FAIL";
		else if($success_count < count($input))
		$api_status_code = "PARTIAL_SUCCESS";
			
		$status = array(
			"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
			"code" => ErrorCodes::$api[$api_status_code],
			"message" => ErrorMessage::$api[$api_status_code]
		);

		$output = array(
			'status' => $status,
			'product' => array("brands" => array("brand" => $response))
		);
		return $output;
	}
	
	/**
	 * @SWG\Api(
	 * path="/product/attribute_values.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetch possible values of the attribute",
	 *    @SWG\Parameter(
	 *    name = "include_id",
	 *    type = "integer",
	 *    paramType = "query",
	 *	  description = "Include attribute-value id in response if set as 1"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "attribute_name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Returns attribute name"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "name",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Fetches attribute value name"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "value_limit",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Sets limit on the number of attribute-values returned"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "value_offset",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = ""
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Fetches id"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "attribute_id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Fetches attribute id"
	 *    )
	 * ))
	 */
	public function getAttributeValues($query_params)
	{
		$invController = new ApiInventoryController();
		$formatter = ResourceFormatterFactory::getInstance("inventoryattribute");
		$res = array();
		$api_status_code = 'SUCCESS';

		$success_count = 0;
		$total_count = 0;

		
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
		$formatter->setIncludedFields("attributevalue");
		if($query_params["attribute_name"])
			$query_params["code"] = $query_params["attribute_name"];
		// filter by code or id, specific saeach
		if($query_params["code"] || $query_params["id"])
		{
			$field = null; $values = null;
			if($query_params["attribute_id"]){
				$field = "attribute_id"; $values = $query_params["attribute_id"]; $query_params["code"] = null;
			}
			else {
				$field = "code"; $values = $query_params["code"]; $query_params["attribute_id"] = null;
			}
			$filters = $query_params;
			$values = explode(",", $values);

			foreach($values as $value)
			{
				$query_params[$field] = $value;
				try {
					$item_status_code = 'ERR_ATTR_VALUE_SUCCESS';
					$inventoryValuesArr = $invController->getAttributeValues($query_params);
					$cat = $inventoryValuesArr[0];
					$cat["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
					$cat["item_status"]["success"] = true;
					$cat["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
					
					$cat = $formatter->generateOutput($cat);
					$success_count++;
					$res["attribute"][] = $cat;
				} catch (Exception $e) {
					$item_status_code = 'ERR_ATTR_VALUE_FAILURE';
					$res["attribute"][] = array(
					$field => $value,
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code],
					);
				}

			}
		}
			
		else
		{
			try{
				$inventoryAttributeArr = $invController->getAttributeValues($query_params);
	
				if($inventoryAttributeArr)
				{
						
					foreach ($inventoryAttributeArr as $attribute)
					{
						$item_status_code = 'ERR_ATTR_VALUE_SUCCESS';
						$attribute["item_status"] = array();
						$attribute["item_status"]["message"] = ErrorMessage::$product[$item_status_code];
						$attribute["item_status"]["success"] = true;
						$attribute["item_status"]["code"] = ErrorCodes::$product[$item_status_code];
						$success_count++;
						$res["attribute"][] = $formatter->generateOutput($attribute);
					}
	
				}
			} catch(Exception $e)
			{
				$item_status_code = 'ERR_ATTR_VALUE_FAILURE';
				$res["attribute"][] = array(
						"success" => false,
						"message" => ErrorMessage::$product[$item_status_code],
						"code" => ErrorCodes::$product[$item_status_code],
					);
			}
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
			'status'=>$status,
				
			'product' => array(
				"count" => count($res["attribute"]),
				'attributes' => $res)
		);
		return $output;
	}

	/**
	 * @SWG\Model(
	 * id = "ValueAttr",
	 * @SWG\Property( name = "name", type = "string", required = true, description = "Attribute value name" ),
	 * @SWG\Property( name = "label", type = "string", description = "Attribute value label" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Attribute",
	 * @SWG\Property( name = "name", type = "string", required = true, description = "Style name" ),
	 * @SWG\Property( name = "label", type = "string", description = "Style label" ),
	 * @SWG\Property( name = "is_enum", type = "boolean", required = true, description = "Specifies whether the attribute is enum" ),
	 * @SWG\Property( name = "extraction_rule_type", type = "string", enum="['UPLOAD', 'POS', 'REGEX', 'USERDEF']" ),
	 * @SWG\Property( name = "extraction_rule_data", type = "string", enum="['String', 'Int', 'Boolean', 'Double']" ),
	 * @SWG\Property( name = "type", type = "string", description = "Attribute type" ),
	 * @SWG\Property( name = "is_soft_enum", type = "string" ),
	 * @SWG\Property( name = "use_in_dump", type = "string" ),
	 * @SWG\Property( name = "default_attribute_value_name", type = "string", description = "Default attribute value name" ),
	 * @SWG\Property( name = "values", type = "array", items = "$ref:ValueAttr" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Attributes",
	 * @SWG\Property( name = "attributes", type = "array", items = "$ref:Attribute" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "AttributeProduct",
	 * @SWG\Property( name = "product", type = "Attributes" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "AttributeRoot",
	 * @SWG\Property( name = "root", type = "AttributeProduct" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/product/attributes.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Add new or update existing attributes and attribute values",
	 *     nickname = "Post Attribute/Attribute_Value",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="AttributeRoot")
	 * ))
	 */
	public function saveAttributes($data)
	{
		global $gbl_item_status_codes, $currentorg;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';

		$formatter = ResourceFormatterFactory::getInstance('inventoryattribute');
		$includeIds = in_array(strtolower($query_params["include_id"]), array(1, "1", true, "true"), true) ? true : false;
		if($includeIds)
			$formatter->setIncludedFields("id");
			
		if(isset($data['root']) && isset($data['root']['product'])
			&& isset($data['root']['product'][0]['attributes']) )
			$input = $data['root']['product'][0]['attributes']['attribute'];
		else
		{
			$api_status_code = "FAIL";
			return array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
			);
		}

		if(!isset($input[0]))
			$input =  array($input);
		$response = array();
		$inventory_controller = new ApiInventoryController();
		foreach($input as $row)
		{
			$errorMsg = "";
			try{
				$res = $inventory_controller->saveAttributes($formatter->readInput($row));
				$style_status_code = "ERR_ATTRIBUTE_ADD_SUCCESS";
				$success_count++;
			} catch(ApiInventoryException $e){
				$style_status_code = "ERR_ATTRIBUTE_ADD_FAILURE";
				$errorMsg = " - ". $e->getMessage();
				$res["attribute"]["name"] = $row["name"];
				$res["attribute"]["is_enum"] = $row["is_enum"];
				$res["attribute"]["extraction_rule_type"] = $row["extraction_rule_type"];
				$res["attribute"]["extraction_rule_data"] = $row["extraction_rule_data"];
				$res["attribute"]["type"] = $row["type"];
				$res["attribute"]["is_soft_enum"] = $row["is_soft_enum"];
				$res["attribute"]["use_in_dump"] = $row["use_in_dump"];
				$res["default_attribute_value_name"] = $row["default_attribute_value_name"];
				$res["attribute_values"] = isset($row["values"]["value"][0])?$row["values"]["value"]:array($row["values"]["value"]);
			} catch (Exception $e){
				$res["attribute"]["name"] = $row["name"];
				$res["attribute"]["is_enum"] = $row["is_enum"];
				$res["attribute"]["extraction_rule_type"] = $row["extraction_rule_type"];
				$res["attribute"]["extraction_rule_data"] = $row["extraction_rule_data"];
				$res["attribute"]["type"] = $row["type"];
				$res["attribute"]["is_soft_enum"] = $row["is_soft_enum"];
				$res["attribute"]["use_in_dump"] = $row["use_in_dump"];
				$res["default_attribute_value_name"] = $row["default_attribute_value_name"];
				$res["attribute_values"] = isset($row["values"]["value"][0])?$row["values"]["value"]:array($row["values"]["value"]);
				$style_status_code = $e->getMessage();
				$item_code = ErrorCodes::$product[$style_status_code];;
				$item_description = ErrorMessage::$product[$style_status_code];
			}
			$res["item_status"]["success"] = (ErrorCodes::$product[$style_status_code] == ErrorCodes::$product["ERR_ATTRIBUTE_ADD_SUCCESS"]) ? true : false;
			$res["item_status"]["code"] = ErrorCodes::$product[$style_status_code];
			$res["item_status"]["message"] = ErrorMessage::$product[$style_status_code] . $errorMsg;
			$res = $formatter->generateOutput($res);
			$response[]=$res;
		}
		if($success_count <= 0)
		$api_status_code = "FAIL";
		else if($success_count < count($input))
		$api_status_code = "PARTIAL_SUCCESS";
			
		$status = array(
			"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
			"code" => ErrorCodes::$api[$api_status_code],
			"message" => ErrorMessage::$api[$api_status_code]
		);

		$output = array(
			'status' => $status,
			'product' => array("attributes" => array("attribute" => $response))
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
		if(in_array(strtolower($version), array('v1','v1.1'))){
			return true;
		}
		return false;
	}

	public function checkMethod($method)
	{
		if(in_array(strtolower($method), array('add', 'attributes', 'get','search', 'styles' , 'categories', 'brands', 'sizes', 'colors', 'meta_sizes', 'attribute_values')))
		{
			return true;
		}
		return false;
	}

}
