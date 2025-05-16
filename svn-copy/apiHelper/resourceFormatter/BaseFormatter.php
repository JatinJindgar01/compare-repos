<?php

/**
 * @author cj
 *
 * the base class defines all the functionality 
 * The file interacts between input xml/json and generate an assoc array to be passed by controllers and vice versa 
 */
abstract class BaseApiFormatter{
	
	// if optionally including are needed
	protected $includedFieldsArr = array();
	
	abstract public function readInput($arr);
	
	abstract public function generateOutput($arr);

	public function getIncludedFields()
	{
		return $this->includedFieldsArr;
	}
	
	public function setIncludedFields($arr)
	{
		if(!isset($this->includedFieldsArr))
			$this->includedFieldsArr = array();
		$valuesArr = array();
		if(is_array($arr))
		{
			foreach($arr as $value)
				$this->includedFieldsArr[] = strtolower($value);
		}
		else
		{
			$this->includedFieldsArr[] = strtolower($arr);
		}
	}
	
	protected function isFieldIncluded($field)
	{
		return in_array(strtolower($field), $this->includedFieldsArr);
	}
	protected function setItemStatus($arr)
	{
		$ret = array();
		if($arr["message"])
		{
			$ret = array(
					"success"	=> $arr["success"] ? true : false,
					"code"		=> $arr["code"] ? $arr["code"] : 200,
					"message"	=> $arr["message"],
					);
		}

		return $ret;
	}
}