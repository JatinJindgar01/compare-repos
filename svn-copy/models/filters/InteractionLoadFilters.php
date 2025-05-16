<?php
/**
 * @author Kartik
 *
 * The class defines all the filter applicable for Interactions
 */
class InteractionLoadFilters{

	/**
	 * 
	 * @var integer id: nsadmin id if its email or sms
	 */
	public $id;
	public $user_id;
	public $email;
	public $mobile;
	public $limit = 1000;
	public $offset = 0;
}
?>