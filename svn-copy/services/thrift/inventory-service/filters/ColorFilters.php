<?php

	namespace inventoryservice\filters;
	
	require_once 'filters/BaseFilters.php';

	/**
	*  Thrift-color-specific filters model
	*  @author: Jessy James <jessy.james@capillarytech.com>
	*/
	class ColorFilters extends BaseFilters {

		public $palette = null;

    	public function toControllerFilters() {
    		
    		$filters = parent::toControllerFilters();

    		if (isset($this -> palette))
				$filters['palette'] = $this -> palette;
			
			return $filters;
    	}
	}