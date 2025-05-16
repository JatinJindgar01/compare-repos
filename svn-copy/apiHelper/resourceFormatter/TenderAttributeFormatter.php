<?php

class TenderAttributeFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = array();
		if($array)
		{
			if($this->isFieldIncluded("tender"))
			{
				$ret["tender_name"] 	= $array["payment_mode_name"];
				if($this->isFieldIncluded("id"))
					$ret["tender_id"] 	= $array["payment_mode_id"];
				
			}
			if($this->isFieldIncluded("id"))
				$ret["id"] 		= $array["payment_mode_attribute_id"];
			
			$ret["name"] 		= $array["name"];
			$ret["data_type"] 	= $array["data_type"] == 'TYPED' ? 'ENUM' :  $array["data_type"];
			$ret["regex"] 		= array( '@cdata' => ($array["regex"] ? $array["regex"] : '') );
			$ret["default_value"]= $array["default_value"];
			$ret["error_msg"] 	= $array["error_msg"];
			
			if($this->isFieldIncluded("options"))
			{
				$ret["options"]["count"] = count($array["possible_values"]);
				
				if($array["possible_values"])
				{
					foreach ($array["possible_values"] as $val)
					{
						if($this->isFieldIncluded("id"))
							$ret["options"]["option"][] = array( "value" => $val["value"], "id" => $val["payment_mode_attribute_possible_value_id"]);
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
	
	public function readInput($arr){}
	
}