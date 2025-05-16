<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiCustomerException extends ApiException{

	// generic errors
	const SAVING_DATA_FAILED				= 20001;
	const VALIDATION_FAILED					= 20002;
	const SAVING_DATA_TO_USERS_FAILED 		= 20003;
	const NO_CUSTOMER_MATCHES				= 20004;
	
	const FILTER_INVALID_OBJECT_PASSED 		= 20010;
	const FILTER_USER_ID_NOT_PASSED			= 20011;
	const FILTER_NON_EXISTING_ID_PASSED 	= 20012;
	const FILTER_EMAIL_NOT_PASSED			= 20013;
	const FILTER_NON_EXISTING_EMAIL_PASSED 	= 20014;
	const FILTER_MOBILE_NOT_PASSED			= 20015;
	const FILTER_NON_EXISTING_MOBILE_PASSED = 20016;
	const FILTER_EXT_ID_NOT_PASSED			= 20017;
	const FILTER_NON_EXISTING_EXT_ID_PASSED = 20018;
	
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
				return 'Saving the customer data to DB has failed';
			case self::VALIDATION_FAILED:
				return 'Validation has failed';
			case self::SAVING_DATA_TO_USERS_FAILED:
				return 'Saving the data to users table failed';
			case self::NO_CUSTOMER_MATCHES:
				return 'No matching customers found with the filter';
			case self::FILTER_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search';
			case self::FILTER_USER_ID_NOT_PASSED:
				return 'User id not passed to search on user_id';
			case self::FILTER_NON_EXISTING_ID_PASSED:
				return 'Users based on user_id not found';
			case self::FILTER_EMAIL_NOT_PASSED:
				return 'Email not passed to search on email';
			case self::FILTER_NON_EXISTING_EMAIL_PASSED:
				return 'Users based on email not found';
			case self::FILTER_MOBILE_NOT_PASSED:
				return 'Mobile not passed to search on mobile';
			case self::FILTER_NON_EXISTING_MOBILE_PASSED:
				return 'Users based on mobile not found';
			case self::FILTER_EXT_ID_NOT_PASSED:
				return 'External id not passed to search on external_id';
			case self::FILTER_NON_EXISTING_EXT_ID_PASSED:
				return 'Users based on external_id not found';
		}
	}

}