<?php

/**
 * @author cj
 *
 */
define ( "VALIDATION_ERROR_LEVEL_WARNING", 		1);
define ( "VALIDATION_ERROR_LEVEL_EXCEPTION", 	2);
abstract class BaseApiModelValidator{
	
	protected $obj;
	protected $errorLevel = VALIDATION_ERROR_LEVEL_EXCEPTION;
	protected $logger;
	protected $current_org_id;
	
	
	// the ApiException which has error message and code
	protected $exception;
	
	// set the logger and the org id
	public function __construct($org_id)
	{
		global $logger; 
		$this->logger = $logger;
		$this->current_org_id = $org_id;
	}
	
	protected function setException($exception)
	{
		if($exception)
			$this->exception = $exception;
		else
			$this->exception = new ApiException(ApiException::UNKNOWN_ERROR);
	}
	/**
	 * @param respective object $obj
	 */
	public function setParams($obj)
	{
		$this->obj = $obj;
	}

	public function setErrorLevel($errorLevel = VALIDATION_ERROR_LEVEL_EXCEPTION)
	{
		$this->errorLevel = $errorLevel;
	}
	
	public function getErrorLevel()
	{
		return $this->errorLevel;
	}
	
	/**
	 * the relevant action need to be spefied here
	 * 
	 * Would return a true or false  
	 */
	abstract protected function doAction();
	
	final public function validate()
	{
		if(!$this->obj)
			throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
		
		$res = $this->doAction();
		
		if(!$res)
		{
			if($this->errorLevel == VALIDATION_ERROR_LEVEL_EXCEPTION)
				throw $this->exception;
			else 
				Util::addApiWarning($this->exception->getMessage());
		}
	}
	
}