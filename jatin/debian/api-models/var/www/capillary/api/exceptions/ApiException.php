<?php

/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiException extends Exception{

	// generic errors in the range of 10000 - 10999
	const UNKNOWN_ERROR			   = 10000;
	const FUNCTION_NOT_IMPLEMENTED = 10001;
	const LAZY_LOAD_CLASS_NOT_FOUND= 10002;
	

	public function __construct($error_code)
	{
		$className = get_called_class();
		parent::__construct($className::getErrorMessage($error_code), $error_code);
	}

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
			case self::UNKNOWN_ERROR:
				return 'Unknown error';

			case self::FUNCTION_NOT_IMPLEMENTED:
				return 'Function is not implemented';
				
			case self::LAZY_LOAD_CLASS_NOT_FOUND:
				return 'Lazy loading of the class has failed';
				
			default:
				return 'Undefined error with code #'.$error_code;
		}
	}

}