<?php

    require_once 'exceptions/ApiException.php';
    /**
     * @author jessy
     */
    class ApiIncomingInteractionOrgPrefixModelException extends Exception { 
        
        const NO_ORG_PREFIX_FOUND = 123; 
        const ORG_PREFIX_INSERT_FAILED = 456;
        const ORG_PREFIX_UPDATE_FAILED = 789;
        
        public static function getErrorMessage($errorCode) { 

            global $logger; 
    		$logger -> debug("API Exeption triggered with code $errorCode"); 
    		
    		switch ($errorCode) {
                case self::NO_ORG_PREFIX_FOUND: 
                    return 'No organization-prefix found'; 
                case self::ORG_PREFIX_INSERT_FAILED: 
                    return 'Organization-prefix insert failed'; 
                case self::ORG_PREFIX_UPDATE_FAILED: 
                    return 'Organization-prefix update failed'; 
            }
        }
    }
