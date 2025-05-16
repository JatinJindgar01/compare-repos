<?php

/**
 * @author cj
 *
 * The abstract class; 
 * the implementation returns the validator for a give class
 */
abstract class BaseValidationLoader{
	
	abstract public static function getValidators($type, $org_id, &$obj);
	
}