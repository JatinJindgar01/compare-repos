<?php
include_once ('exceptions/ApiException.php');
/**
 * @author cj
 * The class defines all the erros thrown from the points engine service
 */
class ApiPointsServiceException extends ApiException{

	// transaction line item errors 26000-26999
	const POINTS_ENGINE_DISABLED		= 26001;
	
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
			case self::POINTS_ENGINE_DISABLED:
				return 'Points engine disabled for the org';
		}
	}

}