<?php

include_once 'models/validators/BaseValidatorsLoader.php';

/**
 * @author cj
 *
 * the implementation returns the validator for a give class
 */
class PaymentModeValidatorsLoader extends BaseValidationLoader{
	
	static function getValidators($type, $org_id, &$obj)
	{
		switch(strtolower($type))
		{
			case 'paymentmodeattributevalue' :
				return self::getPaymentModeAttributeValueValidators($org_id, &$obj);

			case 'paymentmodedetails':
				return self::getPaymentModeDetailsValidators($org_id, &$obj);
				
			case 'orgpaymentmodeattributepossiblevalue':
				return self::getOrgPaymentModeAttributePossibleValue($org_id, &$obj);

			case 'orgpaymentmode':
				return self::getOrgPaymentModeValidators($org_id, &$obj);

			case 'orgpaymentmodeattribute':
				return self::getOrgPaymentModeAttrValidators($org_id, &$obj);
				
			default:
				throw new ApiPaymentModeException(ApiPaymentModeException::VALIDATION_FAILED);
		}
		
		
	}
		
	// to load all the  validators for the payment mode attributes
	private function getPaymentModeAttributeValueValidators($org_id, &$obj)
	{
		$validatorObjArr = array(); 
		
		// value validator
		include_once 'models/validators/PaymentAttributeValueValidator.php';
		$validator = new PaymentAttributeValueValidator($org_id);
		$validator->setParams($obj);
		$validatorObjs[] = $validator;

		return $validatorObjs;
	}
	
	private function getPaymentModeDetailsValidators($org_id, &$obj)
	{
		$validatorObjArr = array();
		
		return array();
		// value validator
		include_once 'models/validators/PaymentModeAmountValidator.php';
		$validator = new PaymentModeAmountValidator($org_id);
		$validator->setParams($obj);
		$validatorObjs[] = $validator;
		
		return $validatorObjs;
	}
	
	private function getOrgPaymentModeAttributePossibleValue($org_id, &$obj)
	{
		$validatorObjArr = array();
		
		// value validator
		include_once 'models/validators/OrgPaymentAttributePossibleValueValidator.php';
		$validator = new OrgPaymentAttributePossibleValueValidator($org_id);
		$validator->setParams($obj);
		$validatorObjs[] = $validator;
		
		return $validatorObjs;
	}

	private function getOrgPaymentModeValidators($org_id, &$obj)
	{
		$validatorObjArr = array();
		
		// value validator
		include_once 'models/validators/OrgPaymentModeIdValidator.php';
		$validator = new OrgPaymentModeIdValidator($org_id);
		$validator->setParams($obj);
		$validatorObjs[] = $validator;
		
		return $validatorObjs;
	}
	
	private function getOrgPaymentModeAttrValidators($org_id, &$obj)
	{
		$validatorObjArr = array();
		
		// value validator
		include_once 'models/validators/OrgPaymentModeAttributeIdValidator.php';
		$validator = new OrgPaymentModeAttributeIdValidator($org_id);
		$validator->setParams($obj);
		$validatorObjs[] = $validator;
		

		include_once 'models/validators/OrgPaymentModeAttributeDataTypeValidator.php';
		$validator = new OrgPaymentModeAttributeDataTypeValidator($org_id);
		$validator->setParams($obj);
		$validatorObjs[] = $validator;
		
		return $validatorObjs;
	}
	
}