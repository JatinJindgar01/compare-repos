<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiLineitemException extends ApiException{

	// transaction errors 22000-22999
	const SAVING_DATA_FAILED				= 22001;
	const VALIDATION_FAILED					= 22002;
	const NO_LINEITEM_MATCHES				= 22003;
	const CUSTOMER_NOT_FOUND				= 22004;
	const TRANSACTION_NOT_FOUND				= 22005;
	const INVALID_LINE_ITEM_TYPE			= 22006;
	
	const FILTER_INVALID_OBJECT_PASSED 		= 22010;
	const FILTER_LINEITEM_ID_NOT_PASSED		= 22011;
	const FILTER_NON_EXISTING_LINEITEM_ID_PASSED = 22012;
	
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
			case self::NO_LINEITEM_MATCHES:
				return 'No matching lineitem found with the filter';
			case self::CUSTOMER_NOT_FOUND:
				return 'Customer not found';
			case self::TRANSACTION_NOT_FOUND:
				return 'Transaction not found';
			case self::INVALID_LINE_ITEM_TYPE:
				return 'Invalid lineitem type is passed';
				
			case self::FILTER_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search';
			case self::FILTER_LINEITEM_ID_NOT_PASSED:
				return 'Lineitem id not passed to search on lineitem';
			case self::FILTER_NON_EXISTING_LINEITEM_ID_PASSED:
				return 'Lineitem based on id not found';
				
		}
	}

}