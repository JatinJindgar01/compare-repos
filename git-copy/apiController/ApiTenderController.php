<?php
require_once 'apiController/ApiBaseController.php';
require_once 'apiHelper/Errors.php';

class ApiTenderController extends ApiBaseController
{
	function __construct()
	{
		parent::__construct();
	}

	function getPaymentModes($name, $loadAttributes = true, $loadPossibleValues = true)
	{
		require_once 'models/PaymentMode.php';
		$objArr = array(); $ret = array();
		if($name)
			$objArr []= PaymentMode::loadByName($name);
		else
			$objArr = PaymentMode::loadAll();

		foreach($objArr as $obj)
		{
			$item = $obj->toArray();

			if($loadAttributes)
			{
				try {
					$item["attributes"] = array();
					$attributes = $obj->getPaymentModeAttribute();
					foreach($attributes as $attribute)
					{
						$attr = $attribute->toArray();
						$attr["payment_mode_name"] = $attribute->getPaymentModeObj()->getName();
						if($loadPossibleValues)
						{

							$values = $attribute->getPaymentModeAttributePossibleValueArr();
							$valueArr = array();
							foreach($values as $values)
								$valueArr[] = $values->toArray();
							$attr["possible_values"] = $valueArr;
						}
							
						$item["attributes"][] = $attr;
					}
				} catch (Exception $e) {
					$this->logger->debug("No possible values for the attibute");
				}
			}
			
			$ret[] = $item;
		}

		return $ret;
	}

	/**
	 * @param string $tender_name
	 * @param string $attribute_name
	 * @param boolean $includeOptions
	 *
	 * 	returns the attribute name
	 */
	public function getPaymentAttributes($payment_mode_name =null, $attribute_name = null, $loadPossibleValues = null)
	{
		
		include_once 'models/PaymentMode.php';
		include_once 'models/PaymentModeAttribute.php';
		
		$payment_mode = PaymentMode::loadByName($payment_mode_name);
		
		$ret = array();
		$filters = new PaymentModeLoadFilters();
		$filters->payment_mode_attribute_name = $attribute_name;
		
		if($attribute_name)
			$paymentAttrs = array(PaymentModeAttributeModel::loadByName($attribute_name, $payment_mode->getPaymentModeId()));
		else
		{
			$paymentAttrs = $payment_mode->getPaymentModeAttribute();
		}
		
		foreach($paymentAttrs as $attribute)
		{
			$attr = $attribute->toArray();
			$attr["payment_mode_name"] = $attribute->getPaymentModeObj()->getName();

			if($loadPossibleValues)
			{

				try {

					$values = $attribute->getPaymentModeAttributePossibleValueArr();
					$valueArr = array();
					foreach($values as $values)
						$valueArr[] = $values->toArray();
					$attr["possible_values"] = $valueArr;
				} catch (Exception $e) {
					$attr["possible_values"] = null;

				}

			}

			$ret[] = $attr;
		}
		return $ret;
	}

	public function getOrgPaymentModes($name, $loadAttributes = true, $loadPossibleValues = true)
	{
		require_once 'models/OrgPaymentMode.php';
		$objArr = array(); $ret = array();
		if($name)
			$objArr []= OrgPaymentMode::loadByLabel($this->currentorg->org_id, $name);
		else
			$objArr = OrgPaymentMode::loadAll($this->currentorg->org_id);

		foreach($objArr as $obj)
		{
			$item = $obj->toArray();
			$item["payment_mode_name"] =  $obj->getPaymentModeName();
			$item["payment_mode_description"] =  $obj->getPaymentModeObj()->getDescription();
			if($loadAttributes)
			{
				try {
					$item["attributes"] = array();
					$attributes = $obj->getAttributes();

					foreach($attributes as $attribute)
					{
						$attr = $attribute->toArray();
						// get the payment mode name if possible
						if($attribute->getPaymentModeAttributeObj())
							$attr["payment_mode_attribute_name"] = $attribute->getPaymentModeAttributeObj()->getName();//getPaymentModeAttributeObj();
						else
							$attr["payment_mode_attribute_name"] = null;
						$attr["payment_mode_name"] = $item["payment_mode_name"];
						$attr["org_payment_mode_name"] = $item["name"];

						if($loadPossibleValues)
						{
							$values = $attribute->getOrgPaymentModeAttributePossibleValueArr();
							
							$valueArr = array();
							foreach($values as $values)
								$valueArr[] = $values->toArray();
							$attr["possible_values"] = $valueArr;
						}

						$item["attributes"][] = $attr;
					}
				} catch (Exception $e) {
					//print $e->getMessage();
				}
			}

			$ret[] = $item;
		}
		return $ret;
	}

	public function getOrgPaymentModesByName($paymentModeName, $loadAttributes = true, $loadPossibleValues = true)
	{
		require_once 'models/OrgPaymentMode.php';
		$objArr = array(); $ret = array();

		$objArr []= OrgPaymentMode::loadByName($this->currentorg->org_id, $paymentModeName);
	
		foreach($objArr as $obj)
		{
			$item = $obj->toArray();
			$item["payment_mode_name"] =  $obj->getPaymentModeName();
			$item["payment_mode_description"] =  $obj->getPaymentModeObj()->getDescription();
			if($loadAttributes)
			{
				try {
					$item["attributes"] = array();
					$attributes = $obj->getAttributes();
	
					foreach($attributes as $attribute)
					{
						$attr = $attribute->toArray();
						// get the payment mode name if possible
						if($attribute->getPaymentModeAttributeObj())
							$attr["payment_mode_attribute_name"] = $attribute->getPaymentModeAttributeObj()->getName();//getPaymentModeAttributeObj();
						else
							$attr["payment_mode_attribute_name"] = null;
						$attr["payment_mode_name"] = $item["payment_mode_name"];
						$attr["org_payment_mode_name"] = $item["name"];
	
						if($loadPossibleValues)
						{
							$values = $attribute->getOrgPaymentModeAttributePossibleValueArr();
								
							$valueArr = array();
							foreach($values as $values)
								$valueArr[] = $values->toArray();
							$attr["possible_values"] = $valueArr;
						}
	
						$item["attributes"][] = $attr;
					}
				} catch (Exception $e) {
					//print $e->getMessage();
				}
			}
	
			$ret[] = $item;
		}
		return $ret;
	}
	
	/**
	 * @param string $tender_name
	 * @param string $attribute_name
	 * @param boolean $includeOptions
	 *
	 * 	returns the attribute name
	 */
	public function getOrgPaymentAttributes($payment_mode_name =null, $attribute_name = null, $loadPossibleValues = null)
	{
		include_once 'models/OrgPaymentModeAttribute.php';
		
		$payment_mode = OrgPaymentMode::loadByLabel($this->currentorg->org_id, $payment_mode_name);
		
		$ret = array();
		$filters = new PaymentModeLoadFilters();
		$filters->payment_mode_attribute_name = $attribute_name;

		if($attribute_name)
		{
			$paymentAttrs = array(OrgPaymentModeAttribute::loadByName($this->currentorg->org_id, $attribute_name, $payment_mode->getOrgPaymentModeId()));
		}
		else
		{
			$paymentAttrs = $payment_mode->getOrgPaymentModeAttribute();
		}

		foreach($paymentAttrs as $attribute)
		{
			$attr = $attribute->toArray();

			if($attribute->getPaymentModeAttributeObj())
				$attr["payment_mode_attribute_name"] = $attribute->getPaymentModeAttributeObj()->getName();//getPaymentModeAttributeObj();
			else
				$attr["payment_mode_attribute_name"] = null;
				
			$attr["payment_mode_name"] = $attribute->getOrgPaymentModeObj()->getPaymentModeName();
			$attr["org_payment_mode_name"] = $attribute->getOrgPaymentModeObj()->getLabel();

			if($loadPossibleValues)
			{

				try {

					$values = $attribute->getOrgPaymentModeAttributePossibleValueArr();
					$valueArr = array();
					foreach($values as $values)
						$valueArr[] = $values->toArray();
					$attr["possible_values"] = $valueArr;
				} catch (Exception $e) {
					$attr["possible_values"] = null;

				}

			}

			$ret[] = $attr;
		}
		return $ret;
	}

	public function saveOrgPaymentModes($orgPaymentModeArr)
	{
		$this->logger->debug("Saving the org payment mode");
		include_once 'models/OrgPaymentMode.php';
	 
		// format the input
		$orgPaymentModeObj = $this->getOrgPaymentModeFromArray($orgPaymentModeArr);
		
		// validate the input
		$orgPaymentModeObj->validate();
		
		// save the value
		$orgPaymentModeObj->save();
	}

	/**
	 * @param array $orgPaymentModeArr
	 * @return OrgPaymentMode
	 */
	private function getOrgPaymentModeFromArray($orgPaymentModeArr)
	{
		$orgPaymentModeObj = OrgPaymentMode::fromArray($this->org_id, $orgPaymentModeArr);
		try {
			if($orgPaymentModeArr["payment_mode_name"])
			{
				$orgPaymentModeByName = OrgPaymentMode::loadByPaymentModeName($this->org_id, $orgPaymentModeArr["payment_mode_name"]);
				$orgPaymentModeObj->setOrgPaymentModeId($orgPaymentModeByName->getOrgPaymentModeId());
				$orgPaymentModeObj->setPaymentModeId($orgPaymentModeByName->getPaymentModeId());
				$this->logger->debug("Org payment mode exist already ; looks to be an update");
			}
			else 
				throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);

		} catch (Exception $e) {
			if($orgPaymentModeArr["payment_mode_name"])
			{
				$this->logger->debug("Org payment mode doesnot exist; fetching the payment mode name");
				$paymentMode = PaymentMode::loadByName($orgPaymentModeArr["payment_mode_name"]);
				$orgPaymentModeObj->setPaymentModeId($paymentMode->getPaymentModeId());
			}
			else
			{
				$this->logger->debug("Payment mode name not specified; have one last try with org payment mode name");
				$orgPaymentModeByName = OrgPaymentMode::loadByLabel($this->org_id, $orgPaymentModeArr["label"]);
				$orgPaymentModeObj->setOrgPaymentModeId($orgPaymentModeByName->getOrgPaymentModeId());
				$orgPaymentModeObj->setPaymentModeId($orgPaymentModeByName->getPaymentModeId());
					
			}
		}
		
		if($orgPaymentModeArr["attributes"])
		{
			$attrs = array();
			foreach($orgPaymentModeArr["attributes"] as $orgPaymentMode)
			{
				$attrs = $this->getOrgPaymentModeAttributesFromArray($orgPaymentModeArr["attributes"], $orgPaymentModeObj);
			}
			$orgPaymentModeObj->setOrgPaymentModeAttribute($attrs);
		}
		return $orgPaymentModeObj;
	}

	/**
	 * @param unknown_type $attributeArr
	 * @return multitype:OrgPaymentModeAttribute
	 */
	private function getOrgPaymentModeAttributesFromArray($attributeArr, $orgPaymentModeObj)
	{
		include_once 'models/OrgPaymentModeAttribute.php';
		include_once 'models/PaymentModeAttributePossibleValue.php';
		
		$this->logger->debug("The attributes are passed");
		$org_payment_mode_id = $orgPaymentModeObj->getOrgPaymentModeId();
		$payment_mode_id = $orgPaymentModeObj->getPaymentModeId();
		
		$orgPaymentModeByName = null;
		$attributesObjArr = array();
		foreach($attributeArr as $attribute)
		{
			$attributesObj = OrgPaymentModeAttribute::fromArray($this->org_id, $attribute);
			try {
				$orgPaymentModeByName = OrgPaymentModeAttribute::loadByPaymentModeAttributeName($this->org_id, $attribute["payment_mode_attribute_name"], $org_payment_mode_id);
				$attributesObj->setPaymentModeAttributeId($orgPaymentModeByName->getPaymentModeAttributeId());
				$attributesObj->setOrgPaymentModeAttributeId($orgPaymentModeByName->getOrgPaymentModeAttributeId());
				$attributesObj->setOrgPaymentModeId($orgPaymentModeByName->getOrgPaymentModeId());
				$this->logger->debug("Org payment mode exist already ; looks to be an update");
			} catch (Exception $e) {
				
				try {
					if($attribute["payment_mode_attribute_name"] && $payment_mode_id)
					{
						$this->logger->debug("Org payment mode doesnot exist; fetching the payment mode name");
						$paymentModeByName = PaymentModeAttributeModel::loadByName($attribute["payment_mode_attribute_name"], $payment_mode_id);
						$attributesObj->setPaymentModeAttributeId($paymentModeByName->getPaymentModeAttributeId());
					}
					else
					{
						$this->logger->debug("Payment mode name not specified; have one last try with org payment mode attr name");
						$orgPaymentModeByName = OrgPaymentModeAttribute::loadByName($this->org_id, $attribute["name"], $org_payment_mode_id);
						$attributesObj->setPaymentModeAttributeId($orgPaymentModeByName->getPaymentModeAttributeId());
						$attributesObj->setOrgPaymentModeAttributeId($orgPaymentModeByName->getOrgPaymentModeAttributeId());
						$attributesObj->setOrgPaymentModeId($orgPaymentModeByName->getOrgPaymentModeId());
					}
					} catch (Exception $e) {
						
						$this->logger->debug("New org level attribute creation");
					}
					
			}
			 
			if($attribute["possible_values"] && 
				(($attributesObj->getDataType() && $attributesObj->getDataType()== "TYPED") 
						|| ($orgPaymentModeByName && $orgPaymentModeByName->getDataType()== "TYPED")))
			{
				$possibleValuesObjArr = array();
				foreach($attribute["possible_values"] as $value)
				{
					
					$possibleValueObj = new OrgPaymentModeAttributePossibleValue($this->org_id);
					$possibleValueObj->setValue($value["value"]);
					if(isset($value["is_valid"]))
						$possibleValueObj->setIsValid($value["is_valid"] ? 1 : 0);
					
					$possibleValueObj->setOrgPaymentModeId($org_payment_mode_id);
					
					// has an org level payment mode already created, updation in progress
					if($orgPaymentModeByName && $orgPaymentModeByName->getOrgPaymentModeAttributeId())
					{
						$possibleValueObj->setOrgPaymentModeAttributeId($attributesObj->getOrgPaymentModeAttributeId());
					}
					else 
					{
						// new attribute also, no id
						$possibleValueObj->orgPaymentModeAttributeObj = $attributesObj;
					}
					
					$possibleValueObj->setOrgPaymentModeAttributeId($attributesObj->getOrgPaymentModeAttributeId());
					
					if($attributesObj->getPaymentModeAttributeId())
					{
						try {
							$paymentModePossibleValueObj = PaymentModeAttributePossibleValue::loadByValue($possibleValueObj->getValue(), $attributesObj->getPaymentModeAttributeId());
							$possibleValueObj->setPaymentModeIdAttributeIdPossibleValueId($paymentModePossibleValueObj->getPaymentModeAttributePossibleValueId());
						} catch (Exception $e) {
							$this->logger->debug("Tender possible value doesnot exists");
						}
						
					}
					$possibleValuesObjArr[] = $possibleValueObj;
				}
				
			}
			
			$attributesObj->setOrgPaymentModeAttributePossibleValueArr($possibleValuesObjArr);
			
			$attributesObjArr[] = $attributesObj;
		}
		return $attributesObjArr;
	}

}
