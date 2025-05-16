<?php
include_once ('exceptions/ApiException.php');
/**
 * @author capillary
 * The class defines all the erros thrown from the API and their corresponding message
 * The function should define all the errors that need to communicate between obj
 */
class ApiInventoryException extends ApiException{

	// transaction line item errors 25000-25999
	const SAVING_DATA_FAILED				= 25001;
	const VALIDATION_FAILED					= 25002;
	const NO_ATTR_MATCHES					= 25003;
	const NO_VALUE_MATCHES					= 25004;
	const NO_ITEM_MATCHES					= 25005;
	const CYCLE_DETECTED					= 25006;
	const MAX_ATTR_DEPTH_EXCEED				= 25007;
	
	
	const FILTER_ATTR_INVALID_OBJECT_PASSED	= 25010;
	const FILTER_ATTR_ID_NOT_PASSED			= 25011;
	const FILTER_NON_EXISTING_ATTR_ID_PASSED= 25012;
	const FILTER_NON_EXISTING_ATTR_NAME_PASSED = 25021;
	const FILTER_ATTR_NAME_NOT_PASSED       = 25022;
	
	const FILTER_VALUE_INVALID_OBJECT_PASSED= 25013;
	const FILTER_VALUE_ID_NOT_PASSED		= 25014;
	const FILTER_NON_EXISTING_VALUE_ID_PASSED=25015;
	const FILTER_VALUE_CODE_NOT_PASSED		= 25023;
	const FILTER_VALUE_NAME_NOT_PASSED		= 25024;
	
	const FILTER_ITEM_INVALID_OBJECT_PASSED	= 25016;
	const FILTER_ITEM_ID_NOT_PASSED			= 25017;
	const FILTER_NON_EXISTING_ITEM_PASSED	= 25018;
	const FILTER_ITEM_SKU_NOT_PASSED		= 25019;
	const FILTER_NON_EXISTING_ITEM_SKU_PASSED=25020;
	
	
	const FILTER_CODE_NOT_PASSED = 25025;
	const FILTER_PARENT_ID_NOT_PASSED = 25026;
	const FILTER_ID_NOT_PASSED = 25027;
	const FILTER_NON_EXISTING_ID_PASSED = 25028;
	const FILTER_NON_EXISTING_CODE_PASSED = 25032;
	
	const CODE_NOT_PASSED = 25028;
	const PARENT_ID_NOT_PASSED = 25029;
	const FILTER_INVALID_OBJECT_PASSED = 25030;
	
	const NO_STYLE_MATCHES = 25031;
	const NO_SIZE_MATCHES = 25032;
	const NO_META_SIZE_MATCHES = 25033;
	const FILTER_TYPE_NOT_PASSED = 25034;
	const CODE_LENGTH_EXCEEDED = 25035;
	
	const VALUE_NAME_NOT_PASSED = 25036;
	const NAME_NOT_PASSED = 25037;
	const IS_ENUM_NOT_PASSED = 25038;
	const INVALID_ATTRIBUTE_EXTRACTION_RULE_TYPE = 25039;
	const INVALID_ATTRIBUTE_TYPE = 25040;
	const INVALID_ATTRIBUTE_ENUM = 25041; 
	const SKU_LENGTH_EXCEEDED = 25042;
	
	
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
				return 'Saving the inventory to DB has failed';
			case self::VALIDATION_FAILED:
				return 'Validation has failed';
			case self::NO_ATTR_MATCHES:
				return 'Inventory Attibutes not found';
			case self::NO_VALUE_MATCHES:
				return 'Inventory attribute values not found';
			case self::NO_ITEM_MATCHES:
				return 'Inventory items not found';
			case self::CYCLE_DETECTED:
				return 'Cycle detected while attribute updation';
			case  self::MAX_ATTR_DEPTH_EXCEED:
				return 'Max depth for attribute creation exceeded';
				
			case self::FILTER_ATTR_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search inventory attribute';
			case self::FILTER_ATTR_ID_NOT_PASSED:
				return 'Attribute id not passed to search on inventory attribute';
			case self::FILTER_NON_EXISTING_ATTR_ID_PASSED:
				return 'Inventory attribute based on id not found';

			case self::FILTER_VALUE_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search inventory attribute value';
			case self::FILTER_VALUE_ID_NOT_PASSED:
				return 'Attribute id not passed to search on inventory attribute';
			case self::FILTER_NON_EXISTING_VALUE_ID_PASSED:
				return 'Inventory value based on id not found';

			case self::FILTER_ITEM_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search inventory';
			case self::FILTER_ITEM_ID_NOT_PASSED:
				return 'Item id not passed to search on inventory';
			case self::FILTER_NON_EXISTING_ITEM_PASSED:
				return 'Item based on id not found';
			case self::FILTER_ITEM_SKU_NOT_PASSED:
				return 'Item id not passed to search on inventory';
			case self::FILTER_NON_EXISTING_ITEM_SKU_PASSED:
				return 'Item based on sku not found';
			case self::FILTER_NON_EXISTING_ATTR_NAME_PASSED:
				return 'Inventory attribute based on name not found';
			case self::FILTER_ATTR_NAME_NOT_PASSED:
				return 'Attribute name not passed to search inventory attribute';
  			case self::FILTER_VALUE_NAME_NOT_PASSED:
				return 'Attribute value name not passed to save';
			case self::FILTER_VALUE_CODE_NOT_PASSED:
				return 'Attribute value code not passed to save';
			case self::FILTER_CODE_NOT_PASSED:
				return 'Code not passed to search';
			case self::FILTER_PARENT_ID_NOT_PASSED:
				return 'Parent meta size id not passed to search';
			case self::CODE_NOT_PASSED:
				return 'Name not passed to save';
			case self::PARENT_ID_NOT_PASSED:
				return 'Parent id not passed to save';
			case self::FILTER_NON_EXISTING_ID_PASSED:
				return 'Item based on id not found ';
			case self::FILTER_NON_EXISTING_CODE_PASSED:
				return 'Item based on code not found ';
			case self::FILTER_ATTR_INVALID_OBJECT_PASSED:
				return 'Invalid filter object passed to search';
			case self::NO_STYLE_MATCHES:
				return 'Styles not found.';
			case self::NO_SIZE_MATCHES:
				return 'Sizes not found';
			case self::NO_META_SIZE_MATCHES:
				return 'Meta size not found';
			case self::FILTER_TYPE_NOT_PASSED:
				return 'Type not passed to search';
			case self::CODE_LENGTH_EXCEEDED:
				return 'Name passed exceeds the max length';
			case self::NAME_NOT_PASSED:
				return 'Name not passed to save';
			case self::IS_ENUM_NOT_PASSED:
				return 'Is enum not passed to save';
			case self::VALUE_NAME_NOT_PASSED:
				return 'Value name not passed to save';
			case self::INVALID_ATTRIBUTE_TYPE:
				return 'Invalid attribute type passed to save';
			case self::INVALID_ATTRIBUTE_EXTRACTION_RULE_TYPE:
				return 'Invalid attribute extraction rule type passed to save';
			case self::INVALID_ATTRIBUTE_ENUM:
				return 'Invalid attribute enum passed' ;
			case self::SKU_LENGTH_EXCEEDED:
				return 'Sku passed exceeds the max length';
		}
	}

}
