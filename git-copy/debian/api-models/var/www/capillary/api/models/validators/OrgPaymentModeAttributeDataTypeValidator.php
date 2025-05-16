<?php

include_once 'models/validators/BaseLineitemValidator.php';

/**
 * @author cj
 *
 * Org Payment mode Id validator
 */
class OrgPaymentModeAttributeDataTypeValidator extends BaseApiModelValidator{

	protected $errorLevel = VALIDATION_ERROR_LEVEL_EXCEPTION;

	protected function doAction()
	{
		include_once 'models/OrgPaymentModeAttribute.php';
		include_once 'apiHelper/DataValueValidator.php';
		
		$this->setException(new ApiPaymentModeException(ApiPaymentModeException::ATTRIBUTE_INVALID_DATA_TYPE));

		$ret = true;
		if($this->obj->getDataType())
		{
			$possibleValuesArr = array('STRING','INT','FLOAT','BOOL','DATE','TYPED');
			$ret = DataValueValidator::validateInList($this->obj->getDataType(), $possibleValuesArr);
		}
		
		return $ret;
		
	}

}