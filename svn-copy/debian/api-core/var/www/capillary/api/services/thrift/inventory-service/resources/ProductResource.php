<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/ProductFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift Product resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class ProductResource extends Resource {

		public function getByIds($orgId, $ids, $uniqueId, $includeIds, $includeHierarchy) {
			
			$this -> logger -> debug("ProductResource -> getByIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\ProductFilters();
			$filters -> ids = $ids;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy);
		}

  		public function getBySkus($orgId, $skus, $uniqueId, $includeIds, $includeHierarchy) {
			
			$this -> logger -> debug("ProductResource -> getByNames() :: Unique ID = '$uniqueId'");

			$filters = new filters\ProductFilters();
			$filters -> skus = $skus;

			return $this -> get($orgId, $filters, $includeIds, $includeHierarchy);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$this -> logger -> debug("ProductResource -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('ProductResource -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('ProductResource -> get() :: Include IDs? ' . print_r($includeIds, true));
			$this -> logger -> debug('ProductResource -> get() :: Include hierarchy? ' . print_r($includeHierarchy, true));

			$products = $queryParams = $result = array();
			
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
							$fetchedProducts = $inventoryCtrlr -> getProductById($id, $includeIds, $includeHierarchy);
							$result [] = $fetchedProducts;
						}
					}
				} elseif (! empty($filters -> skus)) {
					$skus = $filters -> skus;

					if (is_array($skus)) {
						foreach ($skus as $sku) {
							$queryFilters['sku'] = $sku;
							$fetchedProducts = $inventoryCtrlr -> getProductBySku($sku, $includeIds, $includeHierarchy);
							$result [] = $fetchedProducts;
						}
					}
				}
				$this -> logger -> debug('ProductResource -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('ProductResource -> get() :: Result is empty!'); 	
					$this -> logger -> debug('ProductResource -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_PRODUCT_FAILURE'];
					$code = \ErrorCodes::$product['ERR_PRODUCT_FAILURE'];
					$this -> logger -> debug("ProductResource -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("ProductResource -> get() :: Thrift exception code: $code");	
					throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
				}
					
				foreach ($result as $entry) {
					//$this -> logger -> debug("ProductResource -> get() :: Entry: " . print_r($entry, true));	

					$productAttrs = array();
					
					$product['id'] = $entry['item_id'];
					$product['sku'] = $entry['sku'];
					$product['ean'] = $entry['item_ean'];
					$product['price'] = $entry['price'];
					$product['description'] = $entry['description'];
					$product['longDescription'] = $entry['long_description'];
					$product['imageUrl'] = $entry['img_url'];
					$product['addedOn'] = $entry['added_on'];

					if (isset($entry['brand'])) {
						$parents = $entry['brand']['parents']['brand'];
						if (! empty($parents)) {
							foreach ($parents as $parent) {
								$thriftParent = new is\BasicBrand($parent);
								$thriftBrand ['parents'] [] = $thriftParent;
							}
							unset($entry['brand']['parents']);
						}
						$thriftBrand ['basicBrand'] = new is\BasicBrand($entry['brand']);
						$product['brand'] = new is\Brand($thriftBrand);
					}	

					if (isset($entry['category'])) {
						$parents = $entry['category']['parents']['category'];
						if (! empty($parents)) {
							foreach ($parents as $parent) {
								$thriftParent = new is\BasicCategory($parent);
								$thriftCategory ['parents'] [] = $thriftParent;
							}
							unset($entry['category']['parents']);
						}
						$thriftCategory ['basicCategory'] = new is\BasicCategory($entry['category']);
						$product['category'] = new is\Category($thriftCategory);
					}

					if (isset($entry['color'])) {
						$color = $entry['color'];
						$color['palette'] = $color['pallette'];   // Typo
						$product['color'] = new is\Color($color); 
					}

					if (isset($entry['size'])) {
						$size = $entry['size'];
						$size['canonicalName'] = $size['canonical_name'];
						$size['sizeFamily'] = $size['size_family'];
						$size['parentCanonicalName'] = $size['parent_canonical_name'];
						$product['size'] = new is\Size($size); 
					}

					if (isset($entry['style'])) 
						$product['style'] = new is\Style($entry['style']);

					foreach ($entry['attributes']['attribute'] as $attribute) {
						$productAttrs [] = new is\ProductAttribute(array(
							'keyId' => $attribute['id'],
							'valueId' => $attribute['value_id'],
							'key' => $attribute['name'], 
							'value' => $attribute['value']
						));
					}
					$product['attributes'] = $productAttrs;

					$products [] = new is\Product($product); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('ProductResource -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('ProductResource -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_PRODUCT_FAILURE'];
				$code = \ErrorCodes::$product['ERR_PRODUCT_FAILURE'];
				$this -> logger -> debug("ProductResource -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("ProductResource -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('ProductResource -> get() :: Thrift response: ' . json_encode($products, true));
			return $products;
		}
	}