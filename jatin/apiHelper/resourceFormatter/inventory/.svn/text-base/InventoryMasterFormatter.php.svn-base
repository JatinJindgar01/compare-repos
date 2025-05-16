<?php

class InventoryMasterFormatter extends BaseApiFormatter{
	
	public function generateOutput($array,$includeProductHierarchy = false)
	{
		$ret = array();
		$sizeResourceFormatter = ResourceFormatterFactory::getInstance("inventorysize");
		$sizeResourceFormatter->setIncludedFields("basic");
		
		$styleResourceFormatter = ResourceFormatterFactory::getInstance("inventorystyle");
		$styleResourceFormatter->setIncludedFields("basic");
		
		$brandResourceFormatter = ResourceFormatterFactory::getInstance("inventorybrand");
		if($includeProductHierarchy)
			$brandResourceFormatter->setIncludedFields("brand_hierarchy");
		else
			$brandResourceFormatter->setIncludedFields("basic");
		
		$colorResourceFormatter = ResourceFormatterFactory::getInstance("inventorycolor");
		$colorResourceFormatter->setIncludedFields("basic");
		
		$categoryResourceFormatter = ResourceFormatterFactory::getInstance("inventorycategory");
		if($includeProductHierarchy)
			$categoryResourceFormatter->setIncludedFields("category_hierarchy");
		else
			$categoryResourceFormatter->setIncludedFields("basic");

		
		
		if($this->isFieldIncluded("id"))
		{
				//echo "here";
				$ret["item_id"] 		= $array["item_id"];
				$sizeResourceFormatter->setIncludedFields("id");
				$styleResourceFormatter->setIncludedFields("id");
				$brandResourceFormatter->setIncludedFields("id");
				$colorResourceFormatter->setIncludedFields("id");
				$categoryResourceFormatter->setIncludedFields("id");
				
				#$ret["pallette"] = $array["pallette"];
		}
		if($array)
		{
			
			$ret["sku"] = $array["item_sku"];
			$ret["item_ean"] = $array["item_ean"];
			$ret["price"] = $array["price"];
			$ret["img_url"] = $array["image_url"];
			$ret["in_stock"] = ($array["in_stock"])?"true":"false";
			$ret["description"] = $array["description"];
			$ret["long_description"] = $array["long_description"];
			$ret["size"] = $sizeResourceFormatter->generateOutput($array["size"]);
			$ret["style"] = $styleResourceFormatter->generateOutput($array["style"]);
			$ret["brand"] = $brandResourceFormatter->generateOutput($array["brand"]);
			$ret["color"] = $colorResourceFormatter->generateOutput($array["color"]);
			$ret["category"] = $categoryResourceFormatter->generateOutput($array["category"]);
			$ret["attributes"] = array();
			$inventory_attributes = array();
			foreach ($array["inventoryAttributes"] as $inv_attr) {
				$attribute = array();
				if($this->isFieldIncluded("id"))
					{
						$attribute["id"] = $inv_attr["inventory_attribute_id"];
						$attribute["value_id"] = $inv_attr["inventoryAttributeValue"]["inventory_attribute_value_id"];
					}
                     $attribute['name'] = $inv_attr['name'];
                     $attribute['value'] = $inv_attr["inventoryAttributeValue"]["value_name"];
					 $inventory_attributes['attribute'][] = $attribute;
			}
            if(!empty($inventory_attributes))
                $ret["attributes"] = $inventory_attributes;
            else
                $ret["attributes"] = null;
			$ret["added_on"] = $array["added_on"];
			// is tender fields need to be included
				
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
		
		$ret["code"] = $array["code"];
		$ret["description"] = $array["description"];
		
		return $ret;
	}
	
	
}
