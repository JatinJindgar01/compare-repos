<?php

	namespace inventoryservice\filters;
	
	require_once 'filters/StandardFilters.php';

	/**
	*  Thrift-entity-specific filters model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class MetaSizeFilters extends StandardFilters {

		public $type = null;
		public $sizeFamily = null;

    	public function toControllerFilters() {
    		
    		$filters = parent::toControllerFilters();

    		if (isset($this -> type))
				$filters['type'] = $this -> type;
			if (isset($this -> sizeFamily))
				$filters['size_family'] = $this -> sizeFamily;
			
			return $filters;
    	}
	}