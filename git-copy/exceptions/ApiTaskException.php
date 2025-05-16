<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiTaskException extends ApiException{

	// transaction line item errors 27000-27999
	const SAVING_DATA_FAILED				= 27001;
	const VALIDATION_FAILED					= 27002;
	const NO_TASK_MATCHES					= 27003;
	const NO_FOUND_STATUS_FOR_ORG			= 27004;
	
	const FILTER_TASK_INVALID_OBJECT_PASSED	= 25010;
	
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
				return 'Saving the task status to DB has failed';
			case self::VALIDATION_FAILED:
				return 'Validation has failed';
			case self::NO_TASK_MATCHES:
				return 'Task not found';
			case self::NO_FOUND_STATUS_FOR_ORG:
				return 'Task status not available';
				
			case self::FILTER_TASK_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search task';
		}
	}

}