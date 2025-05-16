<?php

	namespace inventoryservice\filters;
	
	require_once 'filters/StandardFilters.php';

	/**
	*  Thrift filters model for enities that reference themselves; generally in an ancestral relation
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class SelfReferencingFilters extends StandardFilters {

		public $parentId = null;
    	public $parentCodes = null;

    	public function toControllerFilters() {
    		
    		$filters = parent::toControllerFilters();

			if (isset($this -> parentId))
			    $filters['parent_id'] = $this -> parentId;
			if (isset($this -> parentCodes))
			    $filters['parent_code'] = $this -> parentCodes;
			
			return $filters;
    	}
	}