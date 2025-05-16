<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/AttributeFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift Attribute resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class AttributeResource extends Resource {

		public function getByIds($orgId, $ids, $uniqueId, $includeIds, $includeChildren, is\ChildrenFilters $thriftFilters) {
			
			$this -> logger -> debug("AttributeResource -> getByIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\AttributeFilters();
			$filters -> ids = $ids;
			$filters -> childrenLimit = $thriftFilters -> childrenLimit;
			$filters -> childrenOffset = $thriftFilters -> childrenOffset;
			
			return $this -> get($orgId, $filters, $includeIds, false, $includeChildren);
		}

  		public function getByNames($orgId, $names, $uniqueId, $includeIds, $includeChildren, is\ChildrenFilters $thriftFilters) {
			
			$this -> logger -> debug("AttributeResource -> getByNames() :: Unique ID = '$uniqueId'");

			$filters = new filters\AttributeFilters();
			$filters -> codes = $names;
			$filters -> childrenLimit = $thriftFilters -> childrenLimit;
			$filters -> childrenOffset = $thriftFilters -> childrenOffset;

			return $this -> get($orgId, $filters, $includeIds, false, $includeChildren);
  		}

  		public function getAll($orgId, $uniqueId, is\LimitFilters $thriftLimitFilters, $includeIds, $includeChildren, is\ChildrenFilters $thriftChildrenFilters) {
			
			$this -> logger -> debug("AttributeResource -> getAll() :: Unique ID = '$uniqueId'");

			$filters = new filters\AttributeFilters();
			$filters -> limit = $thriftLimitFilters -> limit;
			$filters -> offset = $thriftLimitFilters -> offset;
			$filters -> childrenLimit = $thriftChildrenFilters -> childrenLimit;
			$filters -> childrenOffset = $thriftChildrenFilters -> childrenOffset;

			return $this -> get($orgId, $filters, $includeIds, false, $includeChildren);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$filters -> children = $includeChildren;

			$this -> logger -> debug("AttributeResource -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('AttributeResource -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('AttributeResource -> get() :: Include IDs? ' . var_export($includeIds, true));
			$this -> logger -> debug('AttributeResource -> get() :: Include children? ' . var_export($includeChildren, true));

			$attributes = $queryParams = $result = array();
			
			$attributeFormatter = \ResourceFormatterFactory::getInstance('inventoryattribute');
			if($includeIds)
				$attributeFormatter -> setIncludedFields('id');		

			$attributeFormatter -> setIncludedFields("basic");
			if ($includeChildren) 
				$attributeFormatter -> setIncludedFields("attributevalue");
			else 
				$attributeFormatter -> setIncludedFields('basic'); 

            $currentorg -> org_id = $orgId;
			$GLOBALS['currentorg'] = $currentorg;

			$inventoryCtrlr = new \ApiInventoryController();
			try {
				$queryFilters = $filters -> toControllerFilters();
				$this -> logger -> debug('AttributeResource -> get() :: Limit? ' . var_export($queryFilters, true));

				if (! empty($filters -> ids)) {
					$ids = $filters -> ids;

					if (is_array($ids)) {
						foreach ($ids as $id) {
							$queryFilters['id'] = $id;
							$fetchedAttributes = $inventoryCtrlr -> getInventoryAttributes($queryFilters);
							$result [] = $fetchedAttributes [0];
						}
					}
				} elseif (! empty($filters -> codes)) {
					$codes = $filters -> codes;

					if (is_array($codes)) {
						foreach ($codes as $code) {
							$queryFilters['code'] = $code;
							$fetchedAttributes = $inventoryCtrlr -> getInventoryAttributes($queryFilters);
							$result [] = $fetchedAttributes [0];
						}
					}
				} else {
					$result = $inventoryCtrlr -> getInventoryAttributes($queryFilters);
				}
				$this -> logger -> debug('AttributeResource -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('AttributeResource -> get() :: Result is empty!'); 	
					$this -> logger -> debug('AttributeResource -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_ATTR_FAILURE'];
					$code = \ErrorCodes::$product['ERR_ATTR_FAILURE'];
					$this -> logger -> debug("AttributeResource -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("AttributeResource -> get() :: Thrift exception code: $code");	
					throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
				}
					
				foreach ($result as $entry) {
					$attributeValues = array();
					
					$attributeEntry = $attributeFormatter -> generateOutput($entry);

					$attribute['id'] = (int) $attributeEntry['id'];
					$attribute['name'] = $attributeEntry['name'];
					$attribute['label'] = $attributeEntry['label'];
					$attribute['isEnum'] = (boolean) $attributeEntry['is_enum'];
					$attribute['extractionRuleType'] = $GLOBALS['\inventoryservice\E_ExtractionRuleType'][$attributeEntry['extraction_rule_type']];
					$attribute['extractionRuleData'] = $attributeEntry['extraction_rule_data'];
					$attribute['type'] = $GLOBALS['\inventoryservice\E_DataType'][$attributeEntry['type']];
					$attribute['isSoftEnum'] = (boolean) $attributeEntry['is_soft_enum'];
					$attribute['useInDump'] = (boolean) $attributeEntry['use_in_dump'];
					$attribute['defaultAttributeValue'] = new is\AttributeValue(array(
						'id' => $entry['attribute']['default_attribute_value_id'],
						'name' => $attributeEntry['default_attribute_value_name']
					));

					foreach ($entry['possible_values'] as $value) {
						$attributeValues [] = new is\AttributeValue(array(
							'id' => $value['inventory_attribute_value_id'],
							'label' => $value['value_code'], 
							'name' => $value['value_name']
						));
					}
					$attribute['possibleAttributeValues'] = $attributeValues;

					$attributes [] = new is\Attribute($attribute); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('AttributeResource -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('AttributeResource -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_ATTR_FAILURE'];
				$code = \ErrorCodes::$product['ERR_ATTR_FAILURE'];
				$this -> logger -> debug("AttributeResource -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("AttributeResource -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('AttributeResource -> get() :: Thrift response: ' . print_r($attributes, true));
			return $attributes;
		}
	}