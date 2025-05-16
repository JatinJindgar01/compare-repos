<?php

include_once 'models/validators/BaseLineitemValidator.php';

/**
 * @author cj
 *
 * Org Payment mode Id validator
 */
class OrgPaymentModeAttributeIdValidator extends BaseApiModelValidator{

	protected $errorLevel = VALIDATION_ERROR_LEVEL_EXCEPTION;

	protected function doAction()
	{
		include_once 'models/OrgPaymentModeAttribute.php';
		
		$this->setException(new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_ATTR_MATCHES));		
		$ret = true;
		
		try {
			$objById = OrgPaymentModeAttribute::loadById($this->current_org_id, $this->obj->getOrgPaymentModeAttributeId());
		} catch (Exception $e) {}

		if($objById && $this->obj->getOrgPaymentModeAttributeId() && $this->obj->getOrgPaymentModeAttributeId()!= $objById->getOrgPaymentModeAttributeId())
		{
			$this->logger->debug("Payment mode attribute id does not match");
			$ret = false;
		}

		try {
			$objByName = OrgPaymentModeAttribute::loadById($this->current_org_id, $this->obj->getOrgPaymentModeAttributeName());
		} catch (Exception $e) {}
		
		if($ret && $objByName && $this->obj->getOrgPaymentModeAttributeId() && $this->obj->getOrgPaymentModeAttributeId()!= $objByName->getOrgPaymentModeAttributeId())
		{
			$this->setException(new ApiPaymentModeException(ApiPaymentModeException::DUPLICATE_ORG_PAYMENT_ATTR_NAME));
			$this->logger->debug("Payment mode attribute id does not match");
			$ret = false;
		}
		
		return $ret;
		
	}

}