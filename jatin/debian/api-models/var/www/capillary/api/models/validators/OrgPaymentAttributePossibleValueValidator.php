<?php

include_once 'models/validators/BaseLineitemValidator.php';

/**
 * @author cj
 *
 * Line item qty validator
 */
class OrgPaymentAttributePossibleValueValidator extends BaseApiModelValidator{

	protected $errorLevel = VALIDATION_ERROR_LEVEL_WARNING;

	protected function doAction()
	{
		include_once 'models/OrgPaymentModeAttribute.php';
		include_once 'apiHelper/DataValueValidator.php';
		
		$this->setException(new ApiPaymentModeException(ApiPaymentModeException::PAYMENT_ATTR_VALUE_INVALID));		
		$ret = true;
		
		//only for the typed attributes the save should happen
		if($this->obj->orgPaymentModeAttributeObj && $this->obj->orgPaymentModeAttributeObj->getDataType() == 'TYPED')
			$ret = true;
		else 
			$ret = false;
				
		return $ret;
		
	}

}