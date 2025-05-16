<?php

include_once 'models/validators/BaseLineitemValidator.php';

/**
 * @author cj
 *
 * Org Payment mode Id validator
 */
class OrgPaymentModeIdValidator extends BaseApiModelValidator{

	protected $errorLevel = VALIDATION_ERROR_LEVEL_EXCEPTION;

	protected function doAction()
	{
		include_once 'models/OrgPaymentMode.php';
		
		$this->setException(new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES));		
		$ret = true;
		if($this->obj->getOrgPaymentModeId())
		{
			try {
				$objById = OrgPaymentMode::loadById($this->current_org_id, $this->obj->getOrgPaymentModeId());
			
				if($this->obj->getPaymentModeId() && $this->obj->getPaymentModeId() != $objById->getPaymentModeId())
				{
					$this->logger->debug("Payment mode not matches");
					$ret = false;
				}
			} catch (Exception $e) {
				$this->logger->debug("Org payment mode not found");
				$ret = false;
			}
		}
		
		if($ret && $this->obj->getLabel())
		{
			try {
				$objByName = OrgPaymentMode::loadByLabel($this->current_org_id, $this->obj->getLabel());
			} catch (Exception $e) {
				
			}
			
			if($objByName)
			{
				if($this->obj->getPaymentModeId() && $this->obj->getPaymentModeId() != $objByName->getPaymentModeId())
				{
					$this->setException(new ApiPaymentModeException(ApiPaymentModeException::DUPLICATE_ORG_PAYMENT_MODE_NAME));
					$this->logger->debug("Payment tender name not matches");
					$ret = false;
				}
			}
		}
		
		return $ret;
		
	}

}