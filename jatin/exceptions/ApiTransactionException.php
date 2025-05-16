<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiTransactionException extends ApiException{

	// transaction line item errors 22000-22999
	const SAVING_DATA_FAILED				= 21001;
	const VALIDATION_FAILED					= 21002;
	const NO_TRANSACTION_MATCHES			= 21003;
	const CUSTOMER_NOT_FOUND				= 21004;
	
	const FILTER_INVALID_OBJECT_PASSED 		= 21010;
	const FILTER_TRANSACTION_ID_NOT_PASSED	= 21011;
	const FILTER_NON_EXISTING_TRANSACTION_ID_PASSED = 21012;
	
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
			case self::NO_TRANSACTION_MATCHES:
				return 'No matching transaction found with the filter';
			case self::CUSTOMER_NOT_FOUND:
				return 'Customer not found';
				
			case self::FILTER_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search';
			case self::FILTER_TRANSACTION_ID_NOT_PASSED:
				return 'Transaction id not passed to search on transaction id';
			case self::FILTER_NON_EXISTING_TRANSACTION_ID_PASSED:
				return 'Transaction based on id not found';
				
		}
	}

}