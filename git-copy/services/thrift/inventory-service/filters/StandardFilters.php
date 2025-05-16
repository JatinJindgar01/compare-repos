<?php

	namespace inventoryservice\filters;
	
	require_once 'filters/BaseFilters.php';

	/**
	*  Thrift-entity-specific filters model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class StandardFilters extends BaseFilters {

		public $ids = array();
		public $codes = null;

    	public function toControllerFilters() {
    		
    		$filters = parent::toControllerFilters();

    		if (isset($this -> ids))
				$filters['id'] = $this -> ids;
			if (isset($this -> codes))
				$filters['code'] = $this -> codes;
			
			return $filters;
    	}
	}