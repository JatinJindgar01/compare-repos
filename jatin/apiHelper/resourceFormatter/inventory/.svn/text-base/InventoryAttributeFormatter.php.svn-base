<?php

class InventoryAttributeFormatter extends BaseApiFormatter{
	
	public function generateOutput($array)
	{
		$ret = null;
		if($array)
		{
			$ret = array();
			if($this->isFieldIncluded("id"))
			{
				$ret["id"] = $array["attribute"]["inventory_attribute_id"];
			}
			
			if($this->isFieldIncluded("basic"))
			{
				$ret["name"] 		= $array["attribute"]["name"];
				$ret["label"] = $array["attribute"]["label"];
				$ret["is_enum"] = $array["attribute"]["is_enum"];
				$ret["type"] = $array["attribute"]["type"];
				$ret["extraction_rule_type"] = $array["attribute"]["extraction_rule_type"];
				$ret["extraction_rule_data"] = $array["attribute"]["extraction_rule_data"];
				$ret["is_soft_enum"] = $array["attribute"]["is_soft_enum"];
				$ret["use_in_dump"] = $array["attribute"]["use_in_dump"];
				$ret["default_attribute_value_name"] = $array["default_attribute_value_name"];
			}
			//Attribute values API needed to expose only a few fields
			elseif($this->isFieldIncluded("attributevalue"))
			{
				$ret["name"] 		= $array["attribute"]["name"];
				$ret["label"] = $array["attribute"]["label"];
				$ret["type"] = $array["attribute"]["type"];
				if($this->isFieldIncluded("basic"))
				{
				$ret["is_enum"] = $array["attribute"]["is_enum"];
				$ret["type"] = $array["attribute"]["type"];
				$ret["extraction_rule_type"] = $array["attribute"]["extraction_rule_type"];
				$ret["extraction_rule_data"] = $array["attribute"]["extraction_rule_data"];
				$ret["is_soft_enum"] = $array["attribute"]["is_soft_enum"];
				$ret["use_in_dump"] = $array["attribute"]["use_in_dump"];
				}
				$ret["possible_values"] = array();
				$ret["possible_values"]["value"] = array();
			} 
			
			else{	
				//return all in attribtues post
				$ret["name"] 		= $array["attribute"]["name"];
				$ret["label"] 		= $array["attribute"]["label"];
				$ret["is_enum"] = $array["attribute"]["is_enum"];
				$ret["extraction_rule_type"] = $array["attribute"]["extraction_rule_type"];
				$ret["extraction_rule_data"] = $array["attribute"]["extraction_rule_data"];
				$ret["type"] = $array["attribute"]["type"];
				$ret["is_soft_enum"] = $array["attribute"]["is_soft_enum"];
				$ret["use_in_dump"] = $array["attribute"]["use_in_dump"];
				$ret["default_attribute_value_name"] = $array["default_attribute_value_name"];
				$attr_values = array();
				$attr_values['value'] = array();
				foreach($array["attribute_values"] as $attr_value)
				{
					$attr_val_arr = array();
				//In case of exception
					if($attr_value["name"]){
						$attr_val_arr['name'] = $attr_value['name'];
						$attr_val_arr['label'] = $attr_value['label'];
						$attr_values['value'][] = $attr_val_arr;
					}
					else{
						$attr_val_arr['name'] = $attr_value['value_name'];
						$attr_val_arr['label'] = $attr_value['value_code'];
						$attr_values['value'][] = $attr_val_arr;
					}
				}	
				$ret["values"] = $attr_values;
			}
			
			if($this->isFieldIncluded("attributevalue")){
				$formatterAttrVal = ResourceFormatterFactory::getInstance("inventoryattributevalue");
					if($this->isFieldIncluded("id"))
						$formatterAttrVal->setIncludedFields("id");
				
				if($array["possible_values"]){
					foreach ($array["possible_values"] as $value) {
							$ret["possible_values"]["value"][] = $formatterAttrVal->generateOutput($value);
					}
				}
				else{
					$ret["possible_values"] = null;
				}
			}
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
		$ret["name"] = $array["name"];
		$ret["label"] = $array["label"];
		$ret["is_enum"] = $array["is_enum"];
		$ret["extraction_rule_type"] = $array["extraction_rule_type"];
		$ret["extraction_rule_data"] = $array["extraction_rule_data"];
		$ret["type"] = $array["type"];
		$ret["is_soft_enum"] = $array["is_soft_enum"];
		$ret["use_in_dump"] = $array["use_in_dump"];
		$ret["default_attribute_value_name"] = $array["default_attribute_value_name"];
		$ret["values"] = $array["values"];
		return $ret;
	}
	
	
}
