<?php

/**
 * Excptions thrown from BaseInteractionModel and children
 *
 * @author rohit
 */
class ApiInteractionException extends Exception{
    
    const FILTER_INVALID_OBJECT_PASSED = 29001;
    const NO_INTERACTION_FOUND = 29002;
    const NO_INTERACTION_ID_PASSED = 29003;
    const INTERATION_UPDATE_FAILED = 2004;
    
    public static function getErrorMessage($error_code)
	{
		global $logger;
		
		// log the exception
		$logger->debug("API Exeption triggered with code $error_code ");
		
		switch($error_code) {
                    case self::FILTER_INVALID_OBJECT_PASSED:
                        return 'Invalid filter object passed';
                    case self::NO_INTERACTION_FOUND:
                        return 'No interaction found';
                    case self::NO_INTERACTION_ID_PASSED:
                        return 'No interaction id passed';
                }
        }
}
