<?php

class InventoryCategoryFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
			$ret = array();
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] 	= $array["inventory_category_id"];
				$ret["parent_id"] 	= $array["parentCategory"]["inventory_category_id"];
			}
			
			if($this->isFieldIncluded("basic"))
			{
				$ret["name"] 	= $array["code"];
				$ret["label"] 	= $array["name"];
			}
			else
			{
				$ret["name"] 	= $array["code"];
				$ret["label"] 	= $array["name"];
				$ret["description"] = $array["description"];
			
			// parent details
			$ret["parent_name"] = $array["parentCategory"]["code"];
			$ret["parent_label"] = $array["parentCategory"]["name"];
			
				
			// is tender fields need to be included
			if($this->isFieldIncluded("values"))
			{
				$ret["values"]["count"] = count($array["values"]);
				
				if($array["values"])
				{
					foreach($array["values"] as $value)
					{
						$x = array(
							"name" => $value["code"],
							"label" => $value["name"],
							"description" => $value["description"],
							);
						if($this->isFieldIncluded("id"))
							$x["id"] = $value["inventory_category_id"];
						$ret["values"]["value"][] =  $x;
					} 
				}
			}
				
                        if($this->isFieldIncluded("category_hierarchy"))
                        {
                            $ret["parents"]["category"] = array();
                            foreach($array["category_hierarchy"] as $parent)
                            {
                                $ret["parents"]["category"][] = array("id" => $parent["inventory_category_id"],
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
		$array["parentCategory"] = array();
		$ret["parentCategory"]["code"] = $array["parent_name"];
		
/*		if($array["values"])
		{
			if($array["values"] && !$array["values"]["value"][0])
				$array["values"]["value"] = array($array["values"]["value"]);
			
			foreach($array["values"]["value"] as $value)
			{
				$ret["values"][] = array(
							"name" => $value["label"],
							"code" => $value["name"],
							"description" => $value["description"],
				);

			}
		}
*/		
		return $ret;
	}
	
	
}