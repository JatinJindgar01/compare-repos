<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiPaymentModeException extends ApiException{

	// transaction line item errors 25000-25999
	const SAVING_DATA_FAILED				= 28001;
	const VALIDATION_FAILED					= 28002;
	const NO_PAYMENT_MODE_MATCHES			= 28003;
	const NO_PAYMENT_ATTR_MATCHES			= 28004;
	const NO_ORG_PAYMENT_ATTR_MATCHES		= 28005;
	const NO_PAYMENT_ATTR_VALUE_MATCHES		= 28006;
	const NO_ORG_PAYMENT_ATTR_VALUE_MATCHES	= 28007;
	const NO_PAYMENT_MODE_PASSED			= 28008;
	
	const FILTER_ATTR_INVALID_OBJECT_PASSED	= 28010;
	const FILTER_ATTR_ID_NOT_PASSED			= 28011;
	const FILTER_NON_EXISTING_ATTR_ID_PASSED= 28012;
	
	const FILTER_VALUE_INVALID_OBJECT_PASSED= 28013;
	const FILTER_VALUE_ID_NOT_PASSED		= 28014;
	const FILTER_NON_EXISTING_VALUE_ID_PASSED=28015;
	
	const PAYMENT_ATTR_VALUE_INVALID		= 28021;
	const PAYMENT_POSSIBLE_NOT_TYPED		= 28022;
	const DUPLICATE_ORG_PAYMENT_MODE_NAME	= 28023;
	const DUPLICATE_ORG_PAYMENT_ATTR_NAME	= 28024;
	const ATTRIBUTE_INVALID_DATA_TYPE		= 28025;
	
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
				return 'Saving the tender details has failed';
			case self::VALIDATION_FAILED:
				return 'Validation has failed';
			case self::NO_PAYMENT_MODE_MATCHES:
				return 'Payment tender not found';
			case self::NO_PAYMENT_ATTR_MATCHES:
				return 'Payment tender attribute values not found';
			case self::NO_ORG_PAYMENT_ATTR_MATCHES:
				return 'Payment Tender attribute not found for org';
			case self::NO_PAYMENT_ATTR_VALUE_MATCHES:
				return 'Payment Tender attribute value not found';
			case self::NO_ORG_PAYMENT_ATTR_VALUE_MATCHES:
				return 'Payment Tender attribute value not found';
			case self::NO_PAYMENT_MODE_PASSED:
				return 'No tenders passed';
							
			case self::FILTER_ATTR_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search tender attribute';
			case self::FILTER_ATTR_ID_NOT_PASSED:
				return 'Attribute id not passed to search on tender attribute';
			case self::FILTER_NON_EXISTING_ATTR_ID_PASSED:
				return 'Payment tender based on id not found';

			case self::FILTER_VALUE_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search payment mode value';
			case self::FILTER_VALUE_ID_NOT_PASSED:
				return 'Attribute id not passed to search on payment mode value';
			case self::FILTER_NON_EXISTING_VALUE_ID_PASSED:
				return 'Payment mode  value based on id not found';

			case self::DUPLICATE_ORG_PAYMENT_MODE_NAME:
				return 'Duplicate tender name';
			case self::DUPLICATE_ORG_PAYMENT_ATTR_NAME:
				return 'Duplicate tender attribute name';
			case self::ATTRIBUTE_INVALID_DATA_TYPE:
				return 'Invalid data type for payment tender attribute'; 
				
			case self::PAYMENT_ATTR_VALUE_INVALID:
				return 'Payment Attribute value is not valid';
			case self::PAYMENT_POSSIBLE_NOT_TYPED:
				return 'Attributes is not typed, cannot set the possible value';

		}
	}

}