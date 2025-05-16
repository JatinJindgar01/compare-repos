<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/SelfReferencingFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift Brand resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class BrandResource extends Resource {

		public function getByIds($orgId, $ids, $uniqueId, $includeIds, $includeHierarchy) {
			
			$this -> logger -> debug("BrandResource -> getByIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\SelfReferencingFilters();
			$filters -> ids = $ids;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy);
		}

  		public function getByNames($orgId, $names, $uniqueId, $includeIds, $includeHierarchy) {
			
			$this -> logger -> debug("BrandResource -> getByNames() :: Unique ID = '$uniqueId'");

			$filters = new filters\SelfReferencingFilters();
			$filters -> codes = $names;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy);
  		}

  		public function getAll($orgId, $uniqueId, is\BrandFilters $thriftFilters, $includeIds, $includeHierarchy) {
			
			$this -> logger -> debug("BrandResource -> getAll() :: Unique ID = '$uniqueId'");

			$filters = new filters\SelfReferencingFilters();
			$filters -> parentId = $thriftFilters -> parentId;
			$filters -> parentCodes = $thriftFilters -> parentNames;
		    $filters -> parentCodes = $thriftFilters -> catalog;
			$filters -> limit = $thriftFilters -> limit;
			$filters -> offset = $thriftFilters -> offset;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$this -> logger -> debug("BrandResource -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('BrandResource -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('BrandResource -> get() :: Include IDs? ' . var_export($includeIds, true));
			$this -> logger -> debug('BrandResource -> get() :: Include hierarchy? ' . var_export($includeHierarchy, true));

			$brands = $queryParams = $result = array();
			
			$brandFormatter = \ResourceFormatterFactory::getInstance('inventoryBrand');
			if($includeIds)
				$brandFormatter -> setIncludedFields('id');		
			if($includeHierarchy) 
                $brandFormatter -> setIncludedFields('brand_hierarchy');			

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
							$fetchedBrands = $inventoryCtrlr -> getBrands($queryFilters);
							$result [] = $fetchedBrands [0];
						}
					}
				} elseif (! empty($filters -> codes)) {
					$codes = $filters -> codes;

					if (is_array($codes)) {
						foreach ($codes as $code) {
							$queryFilters['code'] = $code;
							$fetchedBrands = $inventoryCtrlr -> getBrands($queryFilters);
							$result [] = $fetchedBrands [0];
						}
					}
				} else {
					$result = $inventoryCtrlr -> getBrands($queryFilters);
				}
				$this -> logger -> debug('BrandResource -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('BrandResource -> get() :: Result is empty!'); 	
					$this -> logger -> debug('BrandResource -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_BRAND_FAILURE'];
					$code = \ErrorCodes::$product['ERR_BRAND_FAILURE'];
					$this -> logger -> debug("BrandResource -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("BrandResource -> get() :: Thrift exception code: $code");	
					throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
				}
					
				foreach ($result as $entry) {
					$thriftBrand = $brand = $basicBrand = $parents = null;

					$brand = $brandFormatter -> generateOutput($entry);
					
					$brand ['parentCode'] = $brand['parent_label'];
					$brand ['parentName'] = $brand['parent_name'];
					
					if ($includeHierarchy) {
						$parents = $brand['parents'];
						unset($brand['parents']);
						
						if (! empty($parents ['brand'][0]['id'])) {
							foreach ($parents as $parent) {
								$thriftParent = new is\BasicBrand($parent [0]);
								$thriftBrand ['parents'] [] = $thriftParent;
							}
						}
					}
					$thriftBrand ['basicBrand'] = new is\BasicBrand($brand);

					$brands [] = new is\Brand($thriftBrand); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('BrandResource -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('BrandResource -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_BRAND_FAILURE'];
				$code = \ErrorCodes::$product['ERR_BRAND_FAILURE'];
				$this -> logger -> debug("BrandResource -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("BrandResource -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('BrandResource -> get() :: Thrift response: ' . print_r($brands, true));
			return $brands;
		}
	}