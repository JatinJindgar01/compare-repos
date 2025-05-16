<?php

class OrgTenderFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = array();
		if($array)
		{
			$ret["name"] 		= $array["label"];
			$ret["canonical_name"] = $array["payment_mode_name"];
			$ret["description"] = $array["payment_mode_description"];
			
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] 	= $array["org_payment_mode_id"];
				$ret["canonical_tender_id"] = $array["payment_mode_id"];
			}
				
			// is tender fields need to be included
			if($this->isFieldIncluded("attributes"))
			{
				$ret["attributes"]["count"] = count($array["attributes"]);
				
				if($array["attributes"])
				{
					$formatterAttr = ResourceFormatterFactory::getInstance("orgtenderattribute");
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
	
	/* (non-PHPdoc)
	 * @see BaseApiFormatter::readInput()
	 */
	public function readInput($array){
		$ret = array();
		
		$ret["label"] = substr($array["name"], 0, 50);
		$ret["payment_mode_name"] = $array["canonical_name"];
		
		if(!$ret["label"] && !$array["canonical_name"])
		{
			include_once 'exceptions/ApiPaymentModeException.php';
			throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);
		}
		
		if(strtolower($array["action"]) == 'delete')
			$ret["is_valid"] = 0;
		
		$attributesArr = $array["attributes"]["attribute"];
		if(!$attributesArr)
			return $ret;
		
		$attributesArr = $attributesArr[0] ? $attributesArr : array($attributesArr) ;
		
		if($attributesArr && $attributesArr[0])
		{
			$formatterAttr = ResourceFormatterFactory::getInstance("orgtenderattribute");
			foreach($attributesArr as $attr)
			{
				if(!$attr["name"] && !$attr["canonical_name"])
					continue;
				
				$attr["tender_name"] = $array["name"];
				$ret["attributes"][] = $formatterAttr->readInput($attr); 
			}
		}
		
		return $ret;
	}
	
	
}