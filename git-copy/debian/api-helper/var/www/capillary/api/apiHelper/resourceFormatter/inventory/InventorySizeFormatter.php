<?php

class InventorySizeFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
			$ret = array();
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] = $array["inventory_size_id"];
			}
			
			if($this->isFieldIncluded("basic"))
			{
				$ret["name"] = $array["code"];
				$ret["label"] = $array["name"];
				$ret["type"] = $array["meta_size"]["type"];	
			}
			else
			{
				$ret["name"] 		= $array["code"];
				$ret["label"] = $array["name"];
				$ret["canonical_name"] = $array["meta_size"]["code"];	
				// is tender fields need to be included
				$ret["description"] = $array["meta_size"]["description"];
				$ret["size_family"] = $array["meta_size"]["size_family"];
				$ret["type"] = $array["meta_size"]["type"];
				$ret["parent_canonical_name"] = $array["meta_size"]["parent_meta_size"]["code"];
				#$ret["canonical_parent"]["canonical_code"] = $array["meta_size"]["parent_meta_size"]["canonical_code"];
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
		$ret["meta_size"] = array();
		$ret["meta_size"]["code"] = $array["canonical_name"];
		$ret["meta_size"]["size_family"] = $array["size_family"];
		$ret["meta_size"]["type"] = $array["type"];
		return $ret;
	}
	
	
}