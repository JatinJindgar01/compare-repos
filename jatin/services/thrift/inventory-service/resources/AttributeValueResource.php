<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/AttributeValueFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift AttributeValue resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class AttributeValueResource extends Resource {

		public function getByIdsAndParentIds($orgId, $ids, $parentIds, $uniqueId, $includeIds) {
			
			$this -> logger -> debug("AttributeValueResource -> getByIdsAndParentIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\AttributeValueFilters();
			$filters -> ids = implode(', ', $ids);
			$filters -> parentIds = $parentIds;

			return $this -> get($orgId, $filters, $includeIds);
		}

		public function getByIdsAndParentNames($orgId, $ids, $parentNames, $uniqueId, $includeIds) {

			$this -> logger -> debug("AttributeValueResource -> getByIdsAndParentNames() :: Unique ID = '$uniqueId'");

			$filters = new filters\AttributeValueFilters();
			$filters -> ids = implode(', ', $ids);
			$filters -> parentNames = $parentNames;

			return $this -> get($orgId, $filters, $includeIds);
		}

		public function getByNamesAndParentIds($orgId, $names, $parentIds, $uniqueId, $includeIds) {

			$this -> logger -> debug("AttributeValueResource -> getByNamesAndParentIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\AttributeValueFilters();
			$filters -> names = implode(', ', $names);
			$filters -> parentIds = $parentIds;

			return $this -> get($orgId, $filters, $includeIds);
		}

		public function getByNamesAndParentNames($orgId, $names, $parentNames, $uniqueId, $includeIds) {

			$this -> logger -> debug("AttributeValueResource -> getByNamesAndParentNames() :: Unique ID = '$uniqueId'");

			$filters = new filters\AttributeValueFilters();
			$filters -> names = implode(', ', $names);
			$filters -> parentNames = $parentNames;

			return $this -> get($orgId, $filters, $includeIds);
		}
		
  		public function getAll($orgId, $uniqueId, is\LimitFilters $thriftFilters, $includeIds) {
			
			$this -> logger -> debug("AttributeValueResource -> getAll() :: Unique ID = '$uniqueId'");

			$filters = new filters\AttributeValueFilters();
			$filters -> limit = $thriftFilters -> limit;
			$filters -> offset = $thriftFilters -> offset;

			return $this -> get($orgId, $filters, $includeIds);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$this -> logger -> debug("AttributeValueResource -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('AttributeValueResource -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('AttributeValueResource -> get() :: Include IDs? ' . var_export($includeIds, true));

			$attributeValues = $queryParams = $result = array();
			
			$attributeFormatter = \ResourceFormatterFactory::getInstance('inventoryattribute');
			if($includeIds)
				$attributeFormatter -> setIncludedFields('id');		
            $attributeFormatter -> setIncludedFields('attributevalue');			

            $currentorg -> org_id = $orgId;
			$GLOBALS['currentorg'] = $currentorg;

			$inventoryCtrlr = new \ApiInventoryController();
			try {
				$queryFilters = $filters -> toControllerFilters();

				if (! empty($filters -> ids) || ! empty($filters -> names) ) { 
					if (! empty($filters -> parentIds)) {
						$this -> logger -> debug('AttributeValueResource -> get() :: 1: ');
						$ids = $filters -> parentIds;

						if (is_array($ids)) {
							foreach ($ids as $id) {
								$queryFilters['attribute_id'] = $id;
								$fetchedAttributeValues = $inventoryCtrlr -> getAttributeValues($queryFilters);
								$result [] = $fetchedAttributeValues [0];
							}
						}
					} elseif (! empty($filters -> parentNames)) {
						$this -> logger -> debug('AttributeValueResource -> get() :: 2: ');
						$codes = $filters -> parentNames;

						if (is_array($codes)) {
							foreach ($codes as $code) {
								$queryFilters['code'] = $code;
								$fetchedAttributeValues = $inventoryCtrlr -> getAttributeValues($queryFilters);
								$result [] = $fetchedAttributeValues [0];
							}
						}
					}
				} else {
					$this -> logger -> debug('AttributeValueResource -> get() :: 3: ');
					$result = $inventoryCtrlr -> getAttributeValues($queryFilters);
				}
				$this -> logger -> debug('AttributeValueResource -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('AttributeValueResource -> get() :: Result is empty!'); 	
					$this -> logger -> debug('AttributeValueResource -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_ATTR_VALUE_FAILURE'];
					$code = \ErrorCodes::$product['ERR_ATTR_VALUE_FAILURE'];
					$this -> logger -> debug("AttributeValueResource -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("AttributeValueResource -> get() :: Thrift exception code: $code");	
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
							'name' => $value['value_name'], 
							'label' => $value['value_code']
						));
					}
					$attribute['possibleAttributeValues'] = $attributeValues;

					$this -> logger -> debug("AttributeValueResource -> get() :: Attribute: " . var_export($attribute, true));	
					$attributes [] = new is\Attribute($attribute); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('AttributeValueResource -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('AttributeValueResource -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_ATTR_VALUE_FAILURE'];
				$code = \ErrorCodes::$product['ERR_ATTR_VALUE_FAILURE'];
				$this -> logger -> debug("AttributeValueResource -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("AttributeValueResource -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('AttributeValueResource -> get() :: Thrift response: ' . print_r($attributeValues, true));
			return $attributes;
		}
	}