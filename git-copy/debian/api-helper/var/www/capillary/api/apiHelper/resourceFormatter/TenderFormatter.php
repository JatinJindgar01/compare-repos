<?php

class TenderFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = array();
		if($array)
		{
			$ret["name"] 		= $array["name"];
			if($this->isFieldIncluded("id"))
				$ret["id"] = $array["payment_mode_id"]; 
			$ret["description"] = $array["description"];

			
			// is tender fields need to be included
			if($this->isFieldIncluded("attributes"))
			{
				$ret["attributes"]["count"] = count($array["attributes"]);
				
				if($array["attributes"])
				{
					$formatterAttr = ResourceFormatterFactory::getInstance("tenderattribute");
					if($this->isFieldIncluded("options"))
						$formatterAttr->setIncludedFields("options");
					if($this->isFieldIncluded("id"))
						$formatterAttr->setIncludedFields("id");
						
					foreach ($array["attributes"] as $attr)
					{
						
						$ret["attributes"]["attribute"][] = $formatterAttr->generateOutput($attr);
					}
				}
				
			}
				
			if($itemStatus = $this->setItemStatus($array))
				$ret["item_status"]	= $itemStatus;
		}
		
		return $ret;
	}
	
	public function readInput($arr){}
	
}