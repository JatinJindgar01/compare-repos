<?php
/**
 * @author cj
 *
 * Class define all the filter avail
 */

class PaymentModeLoadFilters
{
	public $payment_mode_id;
	public $org_payment_mode_id;
	public $org_payment_mode_label;
	public $org_payment_mode_name;
	public $payment_mode_attribute_id;
	public $payment_mode_attribute_name;
	public $org_payment_mode_attribute_id;
	public $org_payment_mode_attribute_name;
	public $payment_mode_attribute_possible_value;
	public $payment_mode_attribute_possible_value_id;
	public $org_payment_mode_attribute_possible_value;
	public $org_payment_mode_attribute_possible_value_id;
	
	public $payment_mode_details_id;
	public $ref_type;
	public $ref_id;
	
	public $payment_mode_attribute_value_id;
	public $payment_mode_attribute_value;
	public $include_deleted_org_payment_modes;
	public $include_deleted_attributes;
}