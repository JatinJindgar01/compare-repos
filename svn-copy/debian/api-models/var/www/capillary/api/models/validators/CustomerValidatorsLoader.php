<?php

include_once 'models/validators/BaseValidatorsLoader.php';

/**
 * @author cj
 *
 * the implementation returns the validator for a give class
 */
class CustomerValidatorsLoader extends BaseValidationLoader{
	
	static function getValidators($type, $org_id, &$obj)
	{
		switch(strtolower($type))
		{
			case 'loyaltycustomer' :
				return $this->getLoyaltyCustomerValidators();

			default:
				throw new ApiCustomerException(ApiCustomerException::FILTER_INVALID_OBJECT_PASSED);
		}
		
	}
		
	private function getLoyaltyCustomerValidators($org_id, &$obj)
	{
		$validator = array();
		return $validators;
	}
}