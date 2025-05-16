<?php

include_once 'models/validators/BaseValidatorsLoader.php';

/**
 * @author cj
 *
 * the implementation returns the validator for a give class
 */
class LineitemValidatorsLoader extends BaseValidationLoader{
	
	static function getValidators($type, $org_id, &$obj)
	{
		switch(strtolower($type))
		{
			case 'loyaltylineitem' :
				return $this->getLoyaltyLineitemValidators();

			default:
				throw new ApiLineitemException(ApiLineitemException::FILTER_INVALID_OBJECT_PASSED);
		}
		
	}
		
	private function getLoyaltyLineitemValidators($org_id, &$obj)
	{
		$validator = array();
		
		$array []= 'LIQuantityValidator'; 
		return $validators;
	}
}