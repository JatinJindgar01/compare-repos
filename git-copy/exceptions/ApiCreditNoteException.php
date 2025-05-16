<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiCreditNoteException extends ApiException{

	// transaction line item errors 35000-35999
	const NO_CREDIT_NOTES_MATCHED				= 35001;

	
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
			case self::NO_CREDIT_NOTES_MATCHED:
				return 'Credit notes notes could not be fetched for the transaction';
			

		}
	}

}
