<?php

	namespace inventoryservice\filters;
	
	require_once 'filters/BaseFilters.php';

	/**
	*  Thrift-color-specific filters model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class ProductFilters extends StandardFilters {

		public $ids = null;
		public $skus = null;

    	public function toControllerFilters() {
    		
    		$filters = parent::toControllerFilters();

    		if (isset($this -> ids))
				$filters['id'] = $this -> ids;
			if (isset($this -> skus))
				$filters['sku'] = $this -> skus;
			
			return $filters;
    	}
	}