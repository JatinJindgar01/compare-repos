<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiSourceMappingActionModelException
 *
 * @author capillary
 */
class IncomingInteractionActionsModelException extends Exception{
    
    const FILTER_INVALID_OBJECT_PASSED = 300001;
    const NO_INCOMING_INTERACTION_ACTION_FOUND = 300002;
    
    public static function getErrorMessage($error_code)
	{
		global $logger;
		
		// log the exception
		$logger->debug("API Exeption triggered with code $error_code ");
		
		switch($error_code) {
                    case self::FILTER_INVALID_OBJECT_PASSED:
                        return 'Invalid filter object passed';
                    case self::NO_INCOMING_INTERACTION_ACTION_FOUND:
                        return 'No interaction action found';
                }
        }
        
}
