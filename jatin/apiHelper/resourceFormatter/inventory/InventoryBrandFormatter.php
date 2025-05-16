<?php

class InventoryBrandFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
			$ret = array();
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] 	= $array["inventory_brand_id"];
				$ret["parent_id"] 	= $array["parentBrand"]["inventory_brand_id"];
			}
			
			if($this->isFieldIncluded("basic"))
			{
				$ret["label"] 	= $array["name"];
				$ret["name"] 	= $array["code"];
			}
			else {
				$ret["label"] 	= $array["name"];
				$ret["name"] 	= $array["code"];
			$ret["description"] = $array["description"];
			
			// parent details
			$ret["parent_label"] = $array["parentBrand"]["name"];
			$ret["parent_name"] = $array["parentBrand"]["code"];
			
                        if($this->isFieldIncluded("brand_hierarchy"))
                        {
                            $ret["parents"]["brand"] = array();
                            foreach($array["brand_hierarchy"] as $parent)
                            {
                                $ret["parents"]["brand"][] = array("id" => $parent["inventory_brand_id"],
                                                                   "name" => $parent["code"], 
                                                                   "label" => $parent["name"],
                                                                   "level" => $parent["level"],
                                                                   "description" => $parent["description"]
                                                                     );
                            }
                        }
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
		
		$ret["name"] 	= $array["label"];
		$ret["code"] 	= $array["name"];
		$ret["description"] = $array["description"];
			
		// parent details
		$array["parentBrand"] = array();
		$ret["parentBrand"]["code"] = $array["parent_name"];
		
		return $ret;
	}
	
	
}