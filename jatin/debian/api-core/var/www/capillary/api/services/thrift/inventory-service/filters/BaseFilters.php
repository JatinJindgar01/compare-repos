<?php

	namespace inventoryservice\filters;

	/**
	*  Parent model for all resource-specific filter models 
	*  @author: Jessy <jessy.james@capillarytech.com>
	*/
	class BaseFilters {
			
    	public $limit; 
    	public $offset;

    	public function toControllerFilters() {
    		$filters = array();

    		if (isset($this -> limit))
				$filters['limit'] = $this -> limit;
			if (isset($this -> offset))
				$filters['offset'] = $this -> offset;

			return $filters;
    	}
	}