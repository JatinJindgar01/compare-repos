<?php

include_once ("models/validators/BaseValidator.php");

/**
 * @author cj
 *
 * The base class to validate all type of transaction validations
 */
abstract class BaseTransactionValidation extends BaseApiModelValidator{
	
	public function setParams($obj)
	{
		if(! $obj instanceof ITransaction)
			throw new ApiTransactionException(ApiTransactionException::FILTER_INVALID_OBJECT_PASSED);
		
		parent::setParams($obj);
	}
}