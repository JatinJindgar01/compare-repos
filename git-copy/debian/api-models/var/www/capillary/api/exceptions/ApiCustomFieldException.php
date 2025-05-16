<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiCustomFieldException extends ApiException{

	// transaction line item errors 23000-23999
	const SAVING_DATA_FAILED				= 23001;
	const VALIDATION_FAILED					= 23002;
	const NO_CUSTOM_FIELD_MATCHES			= 23003;
	const NO_CUSTOM_FIELD_VALUE_MATCHES		= 23004;
	
	const FILTER_INVALID_OBJECT_PASSED 		= 23010;
	const FILTER_CF_ID_NOT_PASSED			= 23011;
	const FILTER_NON_EXISTING_CF_ID_PASSED 	= 23012;
	const FILTER_CF_VALUE_ID_NOT_PASSED		= 23013;
	const FILTER_NON_EXISTING_CF_VALUE_ID_PASSED = 23014;
	const FILTER_CF_ID_ASSOC_ID_NOT_PASSED 	= 23015;
	const FILTER_NON_EXISTING_CF_ID_ASSOC_ID_PASSED = 23016;
	
	/**
	 * @param predefined constant $error_code - the error that was triggered
	 * @return string
	 */
	public static function getErrorMessage($error_code)
	{
		global $logger;

		// log the exception
		$logger->debug("API Exeption triggered with code $error_code ");
		
		switch($error_code) {
			case self::SAVING_DATA_FAILED:
				return 'Saving the transaction to DB has failed';
			case self::VALIDATION_FAILED:
				return 'Validation has failed';
			case self::NO_CUSTOM_FIELD_MATCHES:
				return 'No matching custom fields found with the filter';
			case self::NO_CUSTOM_FIELD_VALUE_MATCHES:
				return 'No matching custom fields values found with the filter';
				
			case self::FILTER_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search';
			case self::FILTER_CF_ID_NOT_PASSED:
				return 'CF id not passed to search on custom field id';
			case self::FILTER_CF_ID_NOT_PASSED:
				return 'Custom field based on id not found';
				
			case self::FILTER_CF_VALUE_ID_NOT_PASSED:
				return 'CF value id not passed to search on custom field value id';
			case self::FILTER_NON_EXISTING_CF_VALUE_ID_PASSED:
				return 'Custom field value based on id not found';
			case self::FILTER_CF_ID_ASSOC_ID_NOT_PASSED:
				return 'CF id/ assoc id not passed to search on custom field value';
			case self::FILTER_NON_EXISTING_CF_ID_ASSOC_ID_PASSED:
				return 'Custom field value based on cf_id/assoc_id not found';

		}
	}

}