<?php

class InventoryMetaSizeFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
			$ret = array();
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] = $array["inventory_meta_size_id"];
			}
			
			$ret["name"] 		= $array["code"];
			$ret["label"]  = $array["name"];
			$ret["decription"] = $array["description"];
			$ret["size_family"] = $array["size_family"];
			$ret["type"] = $array["type"];
			$ret["parent_meta_size"] = $array["parent_meta_size"]["code"];
			// is tender fields need to be included
			#$ret["canonical_parent"]["canonical_code"] = $array["meta_size"]["parent_meta_size"]["canonical_code"];
			if($itemStatus = $this->setItemStatus($array["item_status"]))
				$ret["item_status"]	= $itemStatus;
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