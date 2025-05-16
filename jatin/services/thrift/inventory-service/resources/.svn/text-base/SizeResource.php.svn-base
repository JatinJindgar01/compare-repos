<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/SizeFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift Size resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class SizeResource extends Resource {

		public function getByIds($orgId, $ids, $uniqueId, $includeIds) {
			
			$this -> logger -> debug("SizeResource -> getByIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\SizeFilters();
			$filters -> ids = $ids;

			return $this -> get($orgId, $filters, $includeIds);
		}

		public function getByName($orgId, $name, $uniqueId, $includeId) {
			
			$this -> logger -> debug("SizeResource -> getByName() :: Unique ID = '$uniqueId'");

			$filters = new filters\SizeFilters();
			$filters -> name = $name;

			$sizes = $this -> get($orgId, $filters, $includeId);
			return $sizes [0];
		}

  		public function getAll($orgId, $uniqueId, is\SizeFilters $thriftFilters, $includeIds) {
			
			$this -> logger -> debug("SizeResource -> getAll() :: Unique ID = '$uniqueId'");

			$filters = new filters\SizeFilters();
			$filters -> canonicalName = $thriftFilters -> canonicalName;
			$filters -> type = $thriftFilters -> type;
			$filters -> sizeFamily = $thriftFilters -> sizeFamily;
			$filters -> name = $thriftFilters -> name;
			$filters -> limit = $thriftFilters -> limit;
			$filters -> offset = $thriftFilters -> offset;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$this -> logger -> debug("SizeResource -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('SizeResource -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('SizeResource -> get() :: Include IDs? ' . var_export($includeIds, true));

			$sizes = $queryParams = $result = array();
			
			$sizeFormatter = \ResourceFormatterFactory::getInstance('inventorysize');
			if($includeIds)
				$sizeFormatter -> setIncludedFields('id');		

            $currentorg -> org_id = $orgId;
			$GLOBALS['currentorg'] = $currentorg;

			$inventoryCtrlr = new \ApiInventoryController();
			try {
				$queryFilters = $filters -> toControllerFilters();

				if (! empty($filters -> ids)) {
					$ids = $filters -> ids;

					if (is_array($ids)) {
						foreach ($ids as $id) {
							$queryFilters['id'] = $id;

							$fetchedSizes = $inventoryCtrlr -> getSizes($queryFilters);
							$result [] = $fetchedMetaSizes [0];
						}
					}
				} else if (! empty($filters -> name)) {
					$queryFilters['name'] = $filters -> name;

					$result = $inventoryCtrlr -> getSizes($queryFilters);
				} else {
					$result = $inventoryCtrlr -> getSizes($queryFilters);
				}
				$this -> logger -> debug('SizeResource -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('SizeResource -> get() :: Result is empty!'); 	
					$this -> logger -> debug('SizeResource -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_SIZE_FAILURE'];
					$code = \ErrorCodes::$product['ERR_SIZE_FAILURE'];
					$this -> logger -> debug("SizeResource -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("SizeResource -> get() :: Thrift exception code: $code");	
					throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
				}
					
				foreach ($result as $entry) {
					$size = $sizeFormatter -> generateOutput($entry);					

					$size ['canonicalName'] = $size ['canonical_name'];
					$size ['sizeFamily'] = $size ['size_family'];
					$size ['parentCanonicalName'] = $size ['parent_canonical_name'];
					
					$sizes [] = new is\Size($size); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('SizeResource -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('SizeResource -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_SIZE_FAILURE'];
				$code = \ErrorCodes::$product['ERR_SIZE_FAILURE'];
				$this -> logger -> debug("SizeResource -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("SizeResource -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('SizeResource -> get() :: Thrift response: ' . print_r($sizes, true));
			return $sizes;
		}
	}