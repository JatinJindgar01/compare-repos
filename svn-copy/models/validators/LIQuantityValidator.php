<?php

include_once 'models/validators/BaseLineitemValidator.php';

/**
 * @author cj
 *
 * Line item qty validator
 */
class LIQuantityValidator extends BaseLineitemValidator{

	protected function doAction()
	{
		
		/************* qty validation  *************/
		include_once 'apiHelper/DataValueValidator.php';
		
		$ret = true;
		$this->configMgr = new ConfigManager($this->current_org_id);
		
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_NEGATIVE_QTY"))
		{
			$this->logger->debug("check if the qty is negative");
			if(!DataValueValidator::validateZeroPositive($this->obj->qty))
			{
				$ret = false;
			}
		
		}
		
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_ZERO_QTY"))
		{
			$this->logger->debug("check if the qty is zero");
			if(!DataValueValidator::validateNonZero($this->obj->qty))
			{
				$validationError[] = "Quantity is zero";
				$ret = false;
			}
		}
		
		return $ret;
		
	}

}