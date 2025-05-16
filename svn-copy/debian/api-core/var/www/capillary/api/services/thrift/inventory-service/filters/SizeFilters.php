<?php

	namespace inventoryservice\filters;
	
	require_once 'filters/StandardFilters.php';

	/**
	*  Thrift-entity-specific filters model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class SizeFilters extends MetaSizeFilters {

		public $canonicalName = null;
		/* Due to the inconsistency in the query-params filter array for getSizes() in the 
		ApiInventoryController - filter field is not code but name */
		public $name = null;

    	public function toControllerFilters() {
    		
    		$filters = parent::toControllerFilters();

    		if (isset($this -> canonicalName))
				$filters['canonical_name'] = $this -> canonicalName;
			if (isset($this -> name))
				$filters['name'] = $this -> name;
			
			return $filters;
    	}
	}