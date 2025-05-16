<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/MetaSizeFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift MetaSize resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class MetaSizeResource extends Resource {

		public function getByIds($orgId, $ids, $uniqueId, $includeIds) {
			
			$this -> logger -> debug("MetaSizeResource -> getByIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\MetaSizeFilters();
			$filters -> ids = $ids;

			return $this -> get($orgId, $filters, $includeIds);
		}

  		public function getAll($orgId, $uniqueId, is\MetaSizeFilters $thriftFilters, $includeIds) {
			
			$this -> logger -> debug("MetaSizeResource -> getAll() :: Unique ID = '$uniqueId'");

			$filters = new filters\MetaSizeFilters();
			$filters -> type = $thriftFilters -> type;
			$filters -> sizeFamily = $thriftFilters -> sizeFamily;
			$filters -> limit = $thriftFilters -> limit;
			$filters -> offset = $thriftFilters -> offset;

			return $this -> get($orgId, $filters, $includeIds);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$this -> logger -> debug("MetaSizeResource -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('MetaSizeResource -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('MetaSizeResource -> get() :: Include IDs? ' . var_export($includeIds, true));

			$metaSizes = $queryParams = $result = array();
			
			$metaSizeFormatter = \ResourceFormatterFactory::getInstance('inventorymetasize');
			if($includeIds)
				$metaSizeFormatter -> setIncludedFields('id');		

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
							$fetchedMetaSizes = $inventoryCtrlr -> getMetaSizes($queryFilters);
							$result [] = $fetchedMetaSizes [0];
						}
					}
				} else {
					$result = $inventoryCtrlr -> getMetaSizes($queryFilters);
				}
				$this -> logger -> debug('MetaSizeResource -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('MetaSizeResource -> get() :: Result is empty!'); 	
					$this -> logger -> debug('MetaSizeResource -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_META_SIZE_FAILURE'];
					$code = \ErrorCodes::$product['ERR_META_SIZE_FAILURE'];
					$this -> logger -> debug("MetaSizeResource -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("MetaSizeResource -> get() :: Thrift exception code: $code");	
					throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
				}
					
				foreach ($result as $entry) {
					$metaSize = $metaSizeFormatter -> generateOutput($entry);					

					$metaSize ['description'] = $metaSize ['decription'];   // Typo
					$metaSize ['sizeFamily'] = $metaSize ['size_family'];
					$metaSize ['parentMetaSizeName'] = $metaSize ['parent_meta_size'];
					
					$metaSizes [] = new is\MetaSize($metaSize); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('MetaSizeResource -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('MetaSizeResource -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_META_SIZE_FAILURE'];
				$code = \ErrorCodes::$product['ERR_META_SIZE_FAILURE'];
				$this -> logger -> debug("MetaSizeResource -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("MetaSizeResource -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('MetaSizeResource -> get() :: Thrift response: ' . print_r($metaSizes, true));
			return $metaSizes;
		}
	}