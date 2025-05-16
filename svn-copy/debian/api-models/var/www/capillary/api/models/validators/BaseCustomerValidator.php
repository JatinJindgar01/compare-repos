<?php

include_once ("models/validators/BaseValidator.php");

/**
 * @author cj
 *
 * The base class to validate all type of customer validations
 */
abstract class BaseCustomerValidation extends BaseApiModelValidator{
	
	public function setParams($obj)
	{
		if(! $obj instanceof ICustomer)
			throw new ApiCustomerException(ApiCustomerException::FILTER_INVALID_OBJECT_PASSED);
		
		parent::setParams($obj);
	}
}