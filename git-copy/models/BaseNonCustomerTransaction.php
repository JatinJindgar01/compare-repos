<?php
include_once ("models/BaseTransaction.php");

/**
 * @author cj
 *
 * The loyalty transaction specific operations will be triggered from here
 * The data will be flowing to user_management.loyalty_log or return transaction or so.
 *
*/

abstract class BaseNonCustomerTransaction extends BaseTransaction {

	public function __construct($current_org_id, $transction_id = null)
	{
		parent::__construct($current_org_id, $transction_id);
	}
	
}