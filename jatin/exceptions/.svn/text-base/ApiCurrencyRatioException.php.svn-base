<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiCurrencyException extends ApiException{
	const FILTER_CURRENCY_NOT_PASSED = 70000;
	const FILTER_NON_EXISTING_ID_PASSED = 70001;
	const NO_SUPPORTED_CURRENCY_MATCHES = 70002;
	const FILTER_REF_TYPE_NOT_PASSED = 70003;
	const SAVING_DATA_FAILED = 70004;
	const NO_CURRENCY_CONVERSION_MATCHES = 70005;
	const NO_CURRENCY_RATIO_MATCHES = 70006;
	const FILTER_ID_NOT_PASSED = 70007;
	const FILTER_REF_ID_NOT_PASSED = 70008;
	const FILTER_NON_EXISTING_REF_ID_TYPE_PASSED = 70009;
	const FILTER_INVALID_OBJECT_PASSED = 70010;
	const NO_ORG_CURRENCY_MATCHES = 70010;
	
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
			case self::FILTER_CURRENCY_NOT_PASSED:
				return 'Invalid currency id passed';
			case self::FILTER_NON_EXISTING_ID_PASSED:
				return 'Non existing currency id passed';
			case self::NO_SUPPORTED_CURRENCY_MATCHES:
				return 'No supported currency ';
			case self::NO_ORG_CURRENCY_MATCHES:
				return 'No org currency matches';
			case self::FILTER_REF_TYPE_NOT_PASSED :
				return 'Ref type not passed';
			case self::SAVING_DATA_FAILED:
				return 'Saving data failed';
			case self::NO_CURRENCY_CONVERSION_MATCHES:
				return 'No currency conversion found for currency';
			case self::NO_CURRENCY_RATIO_MATCHES:
				return 'No currency ratio found for this transaction';
			case self::FILTER_ID_NOT_PASSED:
				return 'Id is not passed';
			case self::FILTER_REF_ID_NOT_PASSED:
				return 'Ref type is not passed';
			case self::FILTER_NON_EXISTING_REF_ID_TYPE_PASSED:
				return 'Non existent ref id and ref type passed';
			case self::FILTER_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed';
		}
	}

}
