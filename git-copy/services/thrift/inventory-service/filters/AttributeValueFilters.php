<?php

	namespace inventoryservice\filters;
	
	require_once 'filters/StandardFilters.php';

	/**
	*  Thrift-entity-specific filters model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class AttributeValueFilters extends BaseFilters {

		public $ids = null;
		public $names = null;
		public $parentId = null;
		public $parentName = null;

    	public function toControllerFilters() {
    		
    		$filters = parent::toControllerFilters();

			if (isset($this -> ids))
				$filters['id'] = $this -> ids;
			if (isset($this -> names))
				$filters['name'] = $this -> names;
			if (isset($this -> parentId))
				$filters['attribute_id'] = $this -> parentId;
			if (isset($this -> parentName))
				$filters['code'] = $this -> parentName;
			
			return $filters;
    	}
	}