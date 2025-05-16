<?php

include_once 'models/validators/BaseLineitemValidator.php';

/**
 * @author cj
 *
 * Line item qty validator
 */
class PaymentAttributeValueValidator extends BaseApiModelValidator{

	protected $errorLevel = VALIDATION_ERROR_LEVEL_WARNING;

	protected function doAction()
	{
		include_once 'models/OrgPaymentModeAttribute.php';
		include_once 'apiHelper/DataValueValidator.php';
		
		$this->setException(new ApiPaymentModeException(ApiPaymentModeException::PAYMENT_ATTR_VALUE_INVALID));		
		$ret = true;
		
		//get the attribute forst
		$attribute = OrgPaymentModeAttribute::loadById($this->current_org_id, $this->obj->getOrgPaymentModeAttributeId());
		
		$this->logger->debug("The validation is of type - ". $attribute->getDataType());

		// based on the type the validation need to be selected
		switch(strtoupper($attribute->getDataType()))
		{
			case 'STRING': 
				// regex check comes here; if no regex accept everything 
				if($attribute->getRegex())
					$ret = DataValueValidator::validateRegex($attribute->getRegex(), $this->obj->getValue());
				else
					$ret = true;
				break;

			case 'INT'   : 
				$ret = DataValueValidator::validateInteger($this->obj->getValue());
				break;
			case 'FLOAT' :
				$ret = DataValueValidator::validateFloat($this->obj->getValue());
				break;
			case 'BOOL'  : 
				$ret = DataValueValidator::validateBoolean($this->obj->getValue());
				break;
				
			case 'DATE'  :
				$ret = DataValueValidator::validateDateTime($this->obj->getValue());
				break;

				// enum 
			case 'TYPED' :
				// the predefined set of values can be set over here
				$possibleValues = $attribute->loadPossibleValues();
				
				$valuesArr = array();
				// generate a list of all possible value
				foreach($possibleValues as $possibleValue)
					$valuesArr[] = $possibleValue->getValue();

				$ret = DataValueValidator::validateInList($this->obj->getValue(), $valuesArr, false);
				break;
		}
				
		return $ret;
		
	}

}