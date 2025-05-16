<?php

include_once 'models/validators/BaseValidatorsLoader.php';

/**
 * @author cj
 *
 * the implementation returns the validator for a give class
 */
class TransactionValidatorsLoader extends BaseValidationLoader{
	
	static function getValidators($type, $org_id, &$obj)
	{
		switch(strtolower($type))
		{
			case 'loyaltytransaction' :
				return $this->getLoyaltyTransactionValidators();

			default:
				throw new ApiTransactionException(ApiTransactionException::FILTER_INVALID_OBJECT_PASSED);
		}
		
	}
		
	private function getLoyaltyTransactionValidators($org_id, &$obj)
	{
		$validator = array();
		return $validators;
	}
}