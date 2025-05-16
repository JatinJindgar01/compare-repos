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
class ApiSourceMappingModelException extends Exception{
    
    const FILTER_INVALID_OBJECT_PASSED = 33001;
    const NO_SOURCE_MAPPING_FOUND = 33002;
    const MAPPING_UPDATE_FAILED = 33003;
    const MAPPING_INSERT_FAILED = 33004;
    
    public static function getErrorMessage($error_code)
	{
		global $logger;
		
		// log the exception
		$logger->debug("API Exeption triggered with code $error_code ");
		
		switch($error_code) {
                    case self::FILTER_INVALID_OBJECT_PASSED:
                        return 'Invalid filter object passed';
                    case self::NO_SOURCE_MAPPING_ACTION_FOUND:
                        return 'No source mapping found';
                    case self::MAPPING_UPDATE_FAILED:
                        return 'Source mapping updation failed';
                    case self::MAPPING_INSERT_FAILED:
                        return 'Source mapping insertion failed';
                }
        }
        
}
