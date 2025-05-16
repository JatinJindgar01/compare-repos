<?php

class InventoryColorFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
			$ret = array();
			$ret["pallette"] = str_pad(dechex($array["pallette"]), 6, "0", STR_PAD_LEFT);
			$ret["name"] 		= $array["name"];

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