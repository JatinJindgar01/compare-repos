<?php

class InventoryStyleFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
			$ret = array();
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] = $array["inventory_style_id"];	
			}
			
			if($this->isFieldIncluded("basic"))
			{
				$ret["name"] = $array["code"];
				$ret["label"] = $array["name"];
			}	
			else{
				$ret["name"] 		= $array["code"];
				$ret["label"] = $array["name"];
				$ret["description"] = $array["description"];	
				
				
			// is tender fields need to be included
				
			if($itemStatus = $this->setItemStatus($array["item_status"]))
				$ret["item_status"]	= $itemStatus;
			}
		}
		
		return $ret;
	}
	
	/* (non-PHPdoc)
	 * @see BaseApiFormatter::readInput()
	 */
	public function readInput($array){
		$ret = array();
		
		$ret["code"] = $array["name"];
		$ret["name"] = $array["label"];
		$ret["description"] = $array["description"];
		
		return $ret;
	}
	
	
}