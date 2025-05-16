<?php

	namespace inventoryservice\filters;
	
	require_once 'filters/StandardFilters.php';

	/**
	*  Thrift-entity-specific filters model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class AttributeFilters extends BaseFilters {

		public $includeChildren = null;
		public $childrenLimit = null;
		public $childrenOffset = null;

    	public function toControllerFilters() {
    		
    		$filters = parent::toControllerFilters();

			if (isset($this -> children))
				$filters['values'] = $this -> children;
			if (isset($this -> childrenLimit))
				$filters['value_limit'] = $this -> childrenLimit;
			if (isset($this -> childrenOffset))
				$filters['value_offset'] = $this -> childrenOffset;
			
			return $filters;
    	}
	}