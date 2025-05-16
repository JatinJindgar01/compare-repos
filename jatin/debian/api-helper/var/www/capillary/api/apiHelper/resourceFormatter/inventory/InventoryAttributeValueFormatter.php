<?php

class InventoryAttributeValueFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
			$ret = array();
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] = $array["inventory_attribute_value_id"];
			}
			
			$ret["name"] 		= $array["value_name"];
			$ret["label"] 		= $array["value_code"];
		}
		
		return $ret;
	}
	
	/* (non-PHPdoc)
	 * @see BaseApiFormatter::readInput()
	 */
	public function readInput($array){
		$ret = array();
		return $ret;
	}
	
	
}