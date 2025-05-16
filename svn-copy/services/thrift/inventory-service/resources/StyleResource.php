<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/StandardFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift Style resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class StyleResource extends Resource {

		public function getByIds($orgId, $ids, $uniqueId, $includeIds) {
			
			$this -> logger -> debug("StyleResource -> getByIds() :: Unique ID = '$uniqueId'");

			$filters = new filters\StandardFilters();
			$filters -> ids = $ids;

			return $this -> get($orgId, $filters, $includeIds);
		}

  		public function getByNames($orgId, $names, $uniqueId, $includeIds) {
			
			$this -> logger -> debug("StyleResource -> getByNames() :: Unique ID = '$uniqueId'");

			$filters = new filters\StandardFilters();
			$filters -> codes = $names;

			return $this -> get($orgId, $filters, $includeIds);
  		}

  		public function getAll($orgId, $uniqueId, is\LimitFilters $thriftFilters, $includeIds) {
			
			$this -> logger -> debug("StyleResource -> getAll() :: Unique ID = '$uniqueId'");

			$filters = new filters\StandardFilters();
			$filters -> limit = $thriftFilters -> limit;
			$filters -> offset = $thriftFilters -> offset;

			return $this -> get($orgId, $filters, $includeIds);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$this -> logger -> debug("StyleResource -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('StyleResource -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('StyleResource -> get() :: Include IDs? ' . var_export($includeIds, true));

			$styles = $queryParams = $result = array();
			
			$styleFormatter = \ResourceFormatterFactory::getInstance('inventorystyle');
			if($includeIds)
				$styleFormatter -> setIncludedFields('id');		

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
							$fetchedStyles = $inventoryCtrlr -> getInventoryStyles($queryFilters);
							$result [] = $fetchedStyles [0];
						}
					}
				} elseif (! empty($filters -> codes)) {
					$codes = $filters -> codes;

					if (is_array($codes)) {
						foreach ($codes as $code) {
							$queryFilters['code'] = $code;
							$fetchedStyles = $inventoryCtrlr -> getInventoryStyles($queryFilters);
							$result [] = $fetchedStyles [0];
						}
					}
				} else {
					$result = $inventoryCtrlr -> getInventoryStyles($queryFilters);
				}
				$this -> logger -> debug('StyleResource -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('StyleResource -> get() :: Result is empty!'); 	
					$this -> logger -> debug('StyleResource -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_STYLE_FAILURE'];
					$code = \ErrorCodes::$product['ERR_STYLE_FAILURE'];
					$this -> logger -> debug("StyleResource -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("StyleResource -> get() :: Thrift exception code: $code");	
					throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
				}
					
				foreach ($result as $entry) {
					$style = $styleFormatter -> generateOutput($entry);
					$styles [] = new is\Style($style); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('StyleResource -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('StyleResource -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_STYLE_FAILURE'];
				$code = \ErrorCodes::$product['ERR_STYLE_FAILURE'];
				$this -> logger -> debug("StyleResource -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("StyleResource -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('StyleResource -> get() :: Thrift response: ' . print_r($styles, true));
			return $styles;
		}
	}