<?php

include_once ("models/validators/BaseValidator.php");

/**
 * @author cj
 *
 * The base class to validate all type of Line item validations
 */
abstract class BaseLineitemValidator extends BaseApiModelValidator{
	
	public function setParams($obj)
	{
		if(! $obj instanceof ILineItem)
			throw new ApiLineitemException(ApiLineitemException::FILTER_INVALID_OBJECT_PASSED);
		
		parent::setParams($obj);
	}
}