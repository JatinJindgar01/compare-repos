<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/SelfReferencingFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift Category resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class CategoryResource extends Resource {

		public function getByIds($orgId, $ids, $uniqueId, $includeIds, $includeChildren, $includeHierarchy) {
			
			$this -> logger -> debug("CategoryResource  -> getByIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\SelfReferencingFilters();
			$filters -> ids = $ids;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy, $includeChildren);
		}

  		public function getByNames($orgId, $names, $uniqueId, $includeIds, $includeChildren, $includeHierarchy) {
			
			$this -> logger -> debug("CategoryResource  -> getByNames() :: Unique ID = '$uniqueId'");

			$filters = new filters\SelfReferencingFilters();
			$filters -> codes = $names;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy, $includeChildren);
  		}

  		public function getAll($orgId, $uniqueId, is\CategoryFilters $thriftFilters, $includeIds, $includeChildren, $includeHierarchy) {
			
			$this -> logger -> debug("CategoryResource  -> getAll() :: Unique ID = '$uniqueId'");

			$filters = new filters\SelfReferencingFilters();
			$filters -> parentId = $thriftFilters -> parentId;
			$filters -> parentCodes = $thriftFilters -> parentNames;
		    $filters -> parentCodes = $thriftFilters -> catalog;
			$filters -> limit = $thriftFilters -> limit;
			$filters -> offset = $thriftFilters -> offset;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy, $includeChildren);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$this -> logger -> debug("CategoryResource  -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('CategoryResource  -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('CategoryResource  -> get() :: Include IDs? ' . var_export($includeIds, true));
			$this -> logger -> debug('CategoryResource  -> get() :: Include children? ' . var_export($includeChildren, true));
			$this -> logger -> debug('CategoryResource  -> get() :: Include hierarchy? ' . var_export($includeHierarchy, true));

			$categories = $queryParams = $result = array();
			
			$categoryFormatter = \ResourceFormatterFactory::getInstance('inventorycategory');
			if($includeIds)
				$categoryFormatter -> setIncludedFields('id'); 
			if($includeChildren)
				$categoryFormatter -> setIncludedFields('values'); 
			if($includeHierarchy) 
                $categoryFormatter -> setIncludedFields('category_hierarchy');		
            
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
							$fetchedCategories = $inventoryCtrlr -> getCategories($queryFilters, $includeChildren);
							$result [] = $fetchedCategories [0];
						}
					}
				} elseif (! empty($filters -> codes)) {
					$codes = $filters -> codes;

					if (is_array($codes)) {
						foreach ($codes as $code) {
							$queryFilters['code'] = $code;
							$fetchedCategories = $inventoryCtrlr -> getCategories($queryFilters, $includeChildren);
							$result [] = $fetchedCategories [0];
						}
					}
				} else {
					$result = $inventoryCtrlr -> getCategories($queryFilters, $includeChildren);
				}
				$this -> logger -> debug('CategoryResource  -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('CategoryResource  -> get() :: Result is empty!'); 	
					$this -> logger -> debug('CategoryResource  -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_CATEGORY_FAILURE'];
					$code = \ErrorCodes::$product['ERR_CATEGORY_FAILURE'];
					$this -> logger -> debug("CategoryResource  -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("CategoryResource  -> get() :: Thrift exception code: $code");	
					throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
				}
					
				foreach ($result as $entry) {
					$thriftCategory = $category = $basicCategory = $parents = null;

					$category = $categoryFormatter -> generateOutput($entry);	
					$this -> logger -> debug('CategoryResource -> get() - FORMATTED :: ' . json_encode($category, true)); 			

					$category['parentCode'] = $category['parent_label'];
					$category['parentName'] = $category['parent_name'];

					if ($includeHierarchy) {
						$parents = $category['parents'];
						unset($category['parents']);

						foreach ($parents as $parent) {
							$thriftParent = new is\BasicCategory($parent [0]);
							$thriftCategory['parents'] [] = $thriftParent;
						}
					}
					if ($includeChildren) {
						$children = $category['values']['value'];
						unset($category['parents']);

						foreach ($children as $child) {
							$thriftChild = new is\BasicCategory($child);
							$thriftCategory['values'] [] = $thriftChild;
						}
					}
					$thriftCategory['basicCategory'] = new is\BasicCategory($category);

					$categories[] = new is\Category($thriftCategory); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('CategoryResource  -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('CategoryResource  -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_CATEGORY_FAILURE'];
				$code = \ErrorCodes::$product['ERR_CATEGORY_FAILURE'];
				$this -> logger -> debug("CategoryResource  -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("CategoryResource  -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('CategoryResource  -> get() :: Thrift response: ' . print_r($categories, true));
			return $categories;
		}
	}