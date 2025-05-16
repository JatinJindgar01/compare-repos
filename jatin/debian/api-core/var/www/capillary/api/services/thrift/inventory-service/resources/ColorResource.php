<?php
	
	namespace inventoryservice\resources;

	require_once 'resources/Resource.php';
	require_once 'filters/ColorFilters.php';
	require_once 'exceptions/ApiInventoryException.php';

	use \inventoryservice as is; 
	use \inventoryservice\filters as filters;

	/**
	*  Thrift Color resource model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class ColorResource extends Resource {

		public function getByPalette($orgId, $palette, $uniqueId, $includeId) {
			
			$this -> logger -> debug("ColorResource -> getByPalette() :: Unique ID = '$uniqueId'");

			$filters = new filters\ColorFilters();
			$filters -> palette = $palette;
			$this -> logger -> debug('ColorResource -> getByPalette() :: Filters constructed = ' . json_encode($filters, true));

			$colors = $this -> get($orgId, $filters, $includeId);
			return $colors [0];
		}

  		public function getAll($orgId, $uniqueId, is\LimitFilters $thriftFilters, $includeIds) {
			
			$this -> logger -> debug("ColorResource -> getAll() :: Unique ID = '$uniqueId'");

			$filters = new filters\ColorFilters();
			$filters -> limit = $thriftFilters -> limit;
			$filters -> offset = $thriftFilters -> offset;

			return $this -> get($orgId, $filters, $includeIds);
  		}

		protected function get($orgId, $filters, $includeIds = false, $includeHierarchy = false, $includeChildren = false) {

			$this -> logger -> debug("ColorResource -> get() :: Org ID passed = $orgId");
			$this -> logger -> debug('ColorResource -> get() :: Filters passed = ' . json_encode($filters, true));
			$this -> logger -> debug('ColorResource -> get() :: Include IDs? ' . var_export($includeIds, true));

			$colors = $queryParams = $result = array();
			
			$colorFormatter = \ResourceFormatterFactory::getInstance('inventorycolor');
			if($includeIds)
				$colorFormatter -> setIncludedFields('id');		

            $currentorg -> org_id = $orgId;
			$GLOBALS['currentorg'] = $currentorg;

			$inventoryCtrlr = new \ApiInventoryController();
			try {
				$queryFilters = $filters -> toControllerFilters();

				if (! empty($filters -> palette)) {
					$queryFilters['pallette'] = $filters -> palette;
					
					$result = $inventoryCtrlr -> getColors($queryFilters);
				} else {
					$result = $inventoryCtrlr -> getColors($queryFilters);
				}
				$this -> logger -> debug('ColorResource -> get() :: Result: ' . json_encode($result));

				if (empty($result)) {
					$this -> logger -> debug('ColorResource -> get() :: Result is empty!'); 	
					$this -> logger -> debug('ColorResource -> get() :: Triggering a thrift exception '); 	

					$msg = \ErrorMessage::$product['ERR_COLOR_FAILURE'];
					$code = \ErrorCodes::$product['ERR_COLOR_FAILURE'];
					$this -> logger -> debug("ColorResource -> get() :: Thrift exception message: $msg");	
					$this -> logger -> debug("ColorResource -> get() :: Thrift exception code: $code");	
					throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
				}
					
				foreach ($result as $entry) {
					$color = $colorFormatter -> generateOutput($entry);
					$color['palette'] = $color['pallette'];   // Typo
					$colors [] = new is\Color($color); 
				}
			} catch (\Exception $e) {
				$this -> logger -> error('ColorResource -> get() :: Exception caught! ' . 
					'Code: ' . $e -> getCode() . 
					'; Message: ' . $e -> getMessage());
				$this -> logger -> debug('ColorResource -> get() :: Triggering a thrift exception..'); 	

				$msg = \ErrorMessage::$product['ERR_COLOR_FAILURE'];
				$code = \ErrorCodes::$product['ERR_COLOR_FAILURE'];
				$this -> logger -> debug("ColorResource -> get() :: Thrift exception message: $msg");	
				$this -> logger -> debug("ColorResource -> get() :: Thrift exception code: $code");	
				throw new is\InventoryServiceException(array(
						'message' => $msg, 'code' => $code, 'stackTraceStr' => null));
			}

			$this -> logger -> debug('ColorResource -> get() :: Thrift response: ' . print_r($colors, true));
			return $colors;
		}
	}