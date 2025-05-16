<?php

class OrganizationCurrencyFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
				$ret = array();
				$ret["id"] = $array["currency"]["supported_currency_id"];
				$ret["name"] 		= $array["currency"]["name"];
				$ret["symbol"] = $array["currency"]["symbol"];
				$ret["iso_code"] = $array["currency"]["iso_code"];
				$ret["iso_code_numeric"] = $array["currency"]["iso_code_numeric"];
				$ret["base_currency"] = $array["base_currency"];
				if($itemStatus = $this->setItemStatus($array["item_status"]))
				$ret["item_status"]	= $itemStatus;	
			
		}
		
		return $ret;
	}
	
	/* (non-PHPdoc)
	 * @see BaseApiFormatter::readInput()
	 */
	public function readInput($array){
	}
	
	
}