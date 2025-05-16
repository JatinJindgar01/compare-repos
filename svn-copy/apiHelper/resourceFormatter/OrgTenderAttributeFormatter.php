<?php

class OrgTenderAttributeFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = array();
		if($array)
		{
			if($this->isFieldIncluded("tender"))
			{
				$ret["canonical_tender_name"] = $array["payment_mode_name"];
				$ret["tender_name"] = $array["org_payment_mode_name"];
				if($this->isFieldIncluded("id"))
				{
					$ret["tender_id"] 	= $array["org_payment_mode_id"];
					$ret["canonical_tender_id"] = $array["payment_mode_id"];
				}
					
			}
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] 	= $array["org_payment_mode_attribute_id"];
				$ret["canonical_id"] 	= $array["payment_mode_attribute_id"];
			}
			$ret["name"] 		= $array["name"];
			$ret["canonical_name"] 	= $array["payment_mode_attribute_name"];
			$ret["data_type"] 	= $array["data_type"] == 'TYPED' ? 'ENUM' :  $array["data_type"];
			$ret["regex"] 		= array( '@cdata' => ($array["regex"] ? $array["regex"] : '') );
			$ret["default_value"] = $array["default_value"];
			$ret["error_msg"] 	= $array["error_msg"];
			$ret["is_pii_data"] = $array["is_pii_data"];
			
			if($this->isFieldIncluded("options"))
			{
				$ret["options"]["count"] = count($array["possible_values"]);
				
				if($array["possible_values"])
				{
					foreach ($array["possible_values"] as $val)
					{
						if($this->isFieldIncluded("id"))
							$ret["options"]["option"][] = array( "value" => $val["value"], "id" => $val["org_payment_mode_attribute_possible_value_id"]);
						else
							$ret["options"]["option"][] = array( "value" => $val["value"]);
						
					}
				}
			} 
			if($itemStatus = $this->setItemStatus($array))
				$ret["item_status"]	= $itemStatus;
		}
		
		return $ret;
	}
	
	public function readInput($array){
		
		$ret = array();
		
		$ret["org_payment_mode_name"] 	= $array["tender_name"];
		$ret["name"] 		= $array["name"];
		$ret["payment_mode_attribute_name"] 	= $array["canonical_name"];
		$ret["data_type"] 	= $array["data_type"] == 'ENUM' ?  'TYPED' : $array["data_type"] ;
		$ret["regex"] 		= $array["regex"];
		$ret["default_value"] = $array["default_value"];
		$ret["error_msg"] 	= $array["error_msg"];
		$ret["is_pii_data"] = $array["is_pii_data"];
		
		if(strtolower($array["action"]) == 'delete')
			$ret["is_valid"] = 0;
		
		if($array["options"] && $array["options"]["option"])
		{
			foreach($array["options"]["option"] as $option)
			{
				$optionAssoc = array();
				$optionAssoc["value"] = $option["value"];
				
				if(strtolower($option["action"]) == 'delete')
					$optionAssoc["is_valid"] = 0;
				
				$ret["possible_values"][] =$optionAssoc;
			}
		}
		
		return $ret;
		
	}
	
}