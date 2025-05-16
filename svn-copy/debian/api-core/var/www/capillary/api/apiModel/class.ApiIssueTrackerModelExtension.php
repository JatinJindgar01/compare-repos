<?php

include_once 'apiModel/class.ApiIssueTracker.php';

/*
*
* -------------------------------------------------------
* CLASSNAME:        IssueTracker
* GENERATION DATE:  29.06.2011
* CREATED BY:       Suganya TS
* FOR MYSQL TABLE:  import_conf_keys
* FOR MYSQL DB:     import
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiIssueTrackerModelExtension extends ApiIssueTrackerModel
{

	private $user_id;
	private $current_org_id;
	public static $static_database;
	//**********************
	// CONSTRUCTOR METHOD
	//**********************
	public function __construct()
	{	
		global $currentorg, $currentuser;
		
		parent::ApiIssueTrackerModel();
		$this->user_id = $currentuser->user_id;
		$this->current_org_id = $currentorg->org_id;
		
		self::$static_database = new Dbase( 'stores' );
	}
	
	
	/**
	 * Method added to fetch all the columns in the issue_tracker table.
	 * @return array of all columns  mentioned in where clause from issueTracker
	 */
	
	public function getReportColumns()
	{
		$sql="
				SHOW COLUMNS FROM $this->table  
				 WHERE Field IN 
				 (
				 	'id','status','priority','department','assigned_to','issue_code',
				 	'issue_name','ticket_code','assigned_by', 'due_date','created_date',
				 	'reported_by','type','resolved_by'
				 )";
		
		return $this->database->query( $sql );
		
	}

	/**
	 * uyopdates the hitory
	 */
	function updateIssueLog(){
		
		$sql = "
		
			INSERT INTO `issue_tracker_log` 
					(`tracker_id`, `org_id`, `status`, `priority`, `department`, 
						`assigned_to`, `issue_code`, `issue_name`, `customer_id`, 
							`ticket_code`, `assigned_by`, `due_date`, 
								`reported_by`, `resolved_by`)

			VALUES( '$this->id', '$this->org_id', '$this->status', '$this->priority', '$this->department',
						'$this->assigned_to', '$this->issue_code', '$this->issue_name', '$this->customer_id',
							'$this->ticket_code', '$this->assigned_by', '$this->due_date', 
								'$this->reported_by', '$this->resolved_by')
		";	
		
		return $this->database->insert( $sql );
	}

	/**
	 * 
	 * @param unknown_type $issue_id
	 * @param unknown_type $issue_log_id
	 * @param unknown_type $revision_string
	 */
	public function updateRevisionLog( $issue_id, $issue_log_id, $revision_string ){
		
		$revision_string = $this->database->realEscapeString( $revision_string );
		
		$sql = "
		
			INSERT INTO `issue_revision_log` 
					( 
						`org_id`, `issue_id`, `issue_log_id`, 
						`revision_params`, `last_updated_on`, 
						`last_updated_by`
					)

			VALUES
				( 
					'$this->current_org_id', '$issue_id', '$issue_log_id',
					'$revision_string', NOW(), '$this->user_id'	
				)
		";	
		
		return $this->database->insert( $sql );
	}

	/**
	 * Check if the ticket code exists for the organization
	 * 
	 * @param unknown_type $ticket_code
	 * @param unknown_type $org_id
	 */
	public static function isTicketCodeExists( $ticket_code, $org_id ){
		
		$sql = "
				SELECT COUNT(*) 
				FROM `issue_tracker` 
				WHERE `ticket_code` = '$ticket_code' AND org_id = $org_id		
		";
		
		return self::$static_database->query_scalar( $sql );
	}
	
	/**
	 * 
	 * @param unknown_type $customer_id
	 */
	public function isIncomingEmailCustomerExists( $customer_id ){
		
		$sql = "
				SELECT `id`
				FROM `Incoming_email_ids`
				WHERE `org_id` = '$this->current_org_id' AND `id` = '$customer_id'
		";
		
		return $this->database->query_scalar( $sql );
	}
	
	
	
	/**
	 * 
	 * @param unknown_type $filter: filter on which queries are to be run
	 * @param unknown_type $labels : labels for department
	 * @param unknown_type $include_inactive
	 */
	function searchIssueOnFilter( $filter, $labels = array() ,$include_inactive = false ){

		if( !$include_inactive )
		$filter .= " AND `it`.`is_active` = '1' ";

		$department_label = $labels['department'];
		$priority_label = $labels['priority'];
		$status_label = $labels['status'];
		$short_desc_label = $labels['issue_code'];

		$sql = " SELECT
							`issue_code` as '$short_desc_label', 
							`ticket_code`,
							`priority` AS '$priority_label',
							`status` AS '$status_label', 
							`department` AS '$department_label', 
							`it`.`id`,
							CONCAT(`u`.`code`) AS `last_updated_by`,
							CONCAT(`u1`.`code`) AS `currently_assigned_to`,
							CONCAT(`u2`.`code`) AS `created_by`, 
							`created_date` , `reported_by` 
					
					FROM `issue_tracker` AS `it`
					JOIN `masters`.`org_entities` AS `u` ON ( `u`.`id` = `it`.`assigned_by` and u.org_id = it.org_id and u.type = 'TILL')
					JOIN `masters`.`org_entities` AS `u1` ON ( `u1`.`id` = `it`.`assigned_to` and u1.org_id = it.org_id and u1.type = 'TILL' )
					JOIN `masters`.`org_entities` AS `u2` ON ( `u2`.`id` = `it`.`created_by` and u2.org_id = it.org_id and u2.type = 'TILL')
					WHERE `it`.`org_id` = '$this->current_org_id' $filter
					ORDER BY `it`.`id` DESC";
		
		$result =  $this->database->query( $sql );
		
		
		if( !$result )
		{
			//CONCAT(`u1`.`first_name`) AS `currently_assigned_to`,
			//JOIN `masters`.`admin_users` AS `u1` ON ( `u1`.`id` = `it`.`assigned_to` )
				$sql = " SELECT
							`issue_code` as '$short_desc_label', 
							`ticket_code`,
							`priority` AS '$priority_label',
							`status` AS '$status_label', 
							`department` AS '$department_label', 
							`it`.`id`,
							CONCAT(`u`.`code`) AS `last_updated_by`,
							`it`.`assigned_to` AS currently_assigned_to,
							CONCAT(`u2`.`code`) AS `created_by`, 
							`created_date` , `reported_by` 
					
					FROM `issue_tracker` AS `it`
					JOIN `masters`.`org_entities` AS `u` ON ( `u`.`id` = `it`.`assigned_by` and u.org_id = it.org_id and u.type = 'TILL' )
					JOIN `masters`.`org_entities` AS `u2` ON ( `u2`.`id` = `it`.`created_by` and u2.org_id = it.org_id and u2.type = 'TILL' )
					WHERE `it`.`org_id` = '$this->current_org_id' $filter
					ORDER BY `it`.`id` DESC";
		
				$result =  $this->database->query( $sql );
				include_once 'helper/memory_joiner/impl/MemoryJoinerFactory.php';
				include_once 'helper/memory_joiner/impl/MemoryJoinerType.php';
				
				$key_map = array( "currently_assigned_to" => "first_name" );
				$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
				$result = $admin_user->prepareReport( $result, $key_map );
		}	
		
		return $result;
		
		
	}
	
	/**
	 * 
	 * @param $status
	 * @param $department
	 */
	public function getIssuesOverview($status, $department, $department_label ){

		$open_status = $status[0];
		$closed_status = $status[count($status) - 1];


		$sql = "
				SELECT 
					`department` AS '$department_label',
					SUM(CASE WHEN `status` != '$closed_status' THEN 1 ELSE 0 END) AS `issues_open`,
					SUM(CASE WHEN `status` = '$closed_status' THEN 1 ELSE 0 END) AS `issues_closed`,
					COUNT(*) AS `total_issues`,
					TRUNCATE(AVG(CASE WHEN `status` = '$closed_status' THEN DATEDIFF(NOW(), `created_date`) ELSE 0 END), 2) AS `Avg Closure Time In Days`,
					SUM(CASE WHEN `status` != '$closed_status' AND DATEDIFF(NOW(), `created_date`) >= 2 THEN 1 ELSE 0 END) AS `Issues Opened > 2 Days`
					 
				FROM `issue_tracker`
				WHERE `org_id` = '$this->current_org_id'
				GROUP BY `department`
		
		";

		return $this->database->query( $sql );
	}

	/**
	 * 
	 * @param $status
	 * @param $department
	 */
	public function getIssuesCreatorOverview( $status ){

		$open_status = $status[0];
		$closed_status = $status[count($status) - 1];


		$sql = "
				SELECT 
					`oe`.`code` AS `ticket_created_by`,
					`oe`.`type`,
					SUM(CASE WHEN `status` != '$closed_status' THEN 1 ELSE 0 END) AS `issues_open`,
					SUM(CASE WHEN `status` = '$closed_status' THEN 1 ELSE 0 END) AS `issues_closed`,
					COUNT( * ) AS `total_issues_raised`,
					TRUNCATE( 
						AVG( 
							CASE 
								WHEN `status` = '$closed_status' THEN DATEDIFF(NOW(), `created_date`) 
								ELSE 0 
							END
						), 2) AS `Avg Closure Time In Days`,
					SUM( 
						CASE 
							WHEN `status` != '$closed_status' AND DATEDIFF(NOW(), `created_date`) >= 2 
							THEN 1 
							ELSE 0 
						END
					) AS `Issues Opened > 2 Days`,
					`total_issues_raised`
					
				FROM (
				
					SELECT `it`.*,
					COUNT( `ite`.`id` ) AS `total_issues_raised`
					 
					FROM `store_management`.`issue_tracker` AS `it`
					LEFT OUTER JOIN `store_management`.`issue_tracker_escalation` AS `ite` ON (
						`ite`.`org_id` = `it`.`org_id` AND `ite`.`ticket_id` = `it`.`id`	
					) 
					
					WHERE `it`.`org_id` = '$this->current_org_id'
					GROUP BY `it`.`id`
			 	) AS `t`
			 	JOIN `masters`.`org_entities` AS `oe` ON ( `oe`.`org_id` = `t`.`org_id` AND  
			 		`oe`.`id` = `t`.`created_by` )
			 	GROUP BY `t`.`created_by`
		";

		return $this->database->query( $sql );
	}
	
	/**
	 * 
	 * @param unknown_type $filter: filter on which queries are to be run
	 * @param unknown_type $labels : labels for department
	 * @param unknown_type $include_inactive
	 */
	public static function getAllIssues( $filter , $org_id, $include_inactive = false ){

		if( !$include_inactive )
			$filter .= " AND `it`.`is_active` = '1' ";

		$sql = " SELECT
						`it`.`id`,
						`issue_code` ,
						`issue_name` , 
						`priority` , 
						`status` , 
						`department` , 
						`ticket_code`,
						`assigned_to`,
						`assigned_by`,
						`due_date`
					FROM `issue_tracker` AS `it`
					WHERE `it`.`org_id` = '$org_id' $filter
					ORDER BY `it`.`id` DESC";
		
		self::$static_database = new Dbase( 'stores' );
		return self::$static_database->query( $sql );
	}
	
	/**
	 * Stores the mail id and name for the customer
	 * @param unknown_type $sender_name
	 * @param unknown_type $sender_email
	 */
	public function storeEmailAndName( $sender_name, $sender_email ){

		//return the id if exists
		$sql = "
			SELECT `id` 
			FROM  `Incoming_email_ids`
			WHERE `email` = '$sender_email'
		";

		$customer_id =
		$this->database->query_scalar( $sql );
		
		if( !$customer_id ){

			$sql = "
				INSERT INTO `Incoming_email_ids`
				VALUES ( NULL, '$this->current_org_id','$sender_name', '$sender_email', NOW() )
				ON DUPLICATE KEY UPDATE `name` = '$sender_name'
			";

			$customer_id =
			$this->database->insert( $sql );
		}

		return $customer_id;
	}
	
	/**
	 * @param unknown_type $days
	 */
	public function getNotUpdatedIssuesByDays( $days ){
		
		$sql = "
				SELECT `it`.`id`, `it`.`created_date`, `it`.`customer_id`,
					`it`.`created_by`
				FROM `issue_tracker` AS `it`
				LEFT OUTER JOIN `issue_tracker_log` AS `itl` ON 
					( `it`.`id` = `itl`.`tracker_id` AND `it`.`org_id` = `itl`.`org_id` )
				WHERE 
					`it`.`org_id` = '$this->current_org_id' 
					AND `itl`.`id` IS NULL
					AND `is_active` = 1
					AND `created_date` > '2011-10-01'
					HAVING DATEDIFF( DATE( NOW() ), `created_date` ) >= $days
		";
		
		return $this->database->query( $sql );
	}

	/**
	 * @param unknown_type $days
	 * This function takes out the escalation of tickets based
	 * on if the ticket status is closed or not and when it was last updated
	 * 
	 * RULE  
	 * i ) y days past and not CLOSED escalate to user A
	 * ii )z days past and not CLOSED escalate to user B
	 * 
	 * Say : ticket was created on X date. Calculation would be on following way
	 * 1 )  X - DATE( NOW( ) ) == y && status != CLOSED --> pick for escalation.
	 * 2 )  X - DATE( NOW( ) ) == z && status != CLOSED --> pick for escalation.
	 */
	public function getIssuesByDaysClosedStatus( $days, $status ){
		
		$sql = "
				SELECT `it`.`id`, `it`.`created_date`, `it`.`customer_id`,
					`it`.`created_by`
				FROM `issue_tracker` AS `it`
				WHERE 
					`it`.`org_id` = '$this->current_org_id' 
					AND `is_active` = 1
					AND `created_date` > '2011-10-01'
					AND `status` != '$status'
				HAVING DATEDIFF( DATE( NOW() ), `created_date` ) = $days
		";
		
		return $this->database->query( $sql );
	}
	
	/**
	 * CONTRACT 
	 * array(
	 *  'user_id' => VALUE,
	 *  'store_id' => VALUE,
	 *  'ticket_id' => VALUE,
	 *  'escalate_email' => VALUE
	 *  );
	 */
	public function addEscalationRecord( $params ){
		
		extract( $params );
		$sql = "
			INSERT INTO `issue_tracker_escalation` (
				`org_id`, `ticket_id`, `user_id`, `store_id`, `escalated_email`, `last_updated_on`
			)
			VALUES (
				$this->current_org_id, $ticket_id, $user_id, $store_id, '$escalate_email', '$now' 
			)
		";
				
		return $this->database->insert( $sql );
	}
	
	/**
	 * gets not updated issues if any issue crosses its due date 
	 */
	public function getNotUpdatedIssuesAfterDDForCritical(){
		
		$sql = "
				SELECT `it`.`id`, `it`.`created_date`, `it`.`customer_id` , `it`.`assigned_to` , `it`.`due_date`
				FROM `issue_tracker` AS `it`
				LEFT OUTER JOIN `issue_tracker_log` AS `itl` ON 
				( `it`.`id` = `itl`.`tracker_id` AND `it`.`org_id` = `itl`.`org_id` )
				WHERE 
				`it`.`org_id` = '$this->current_org_id' 
				AND `itl`.`id` IS NULL
				AND `it`.`is_active` = 1
				AND `it`.`created_date` > '2011-10-01'
				AND DATE( NOW() ) > `it`.`due_date`
				AND `it`.`mark_critical_on` IS NOT NULL AND DATE(`it`.`mark_critical_on`) != '0000-00-00' 
		";
		
		return $this->database->query( $sql );
	}
	
	/**
	 * gets not updated issues if any issue reaches its due date 
	 */
	public function getNotUpdatedIssuesOnDDForCritical(){
		
		$sql = "
				SELECT `it`.`id`, `it`.`created_date`, `it`.`customer_id` , `it`.`assigned_to` , `it`.`due_date`
				FROM `issue_tracker` AS `it`
				LEFT OUTER JOIN `issue_tracker_log` AS `itl` ON 
				( `it`.`id` = `itl`.`tracker_id` AND `it`.`org_id` = `itl`.`org_id` )
				WHERE 
				`it`.`org_id` = '$this->current_org_id' 
				AND `itl`.`id` IS NULL
				AND `it`.`is_active` = 1
				AND `it`.`created_date` > '2011-10-01'
				AND DATE( NOW() ) = `it`.`due_date`
				AND `it`.`mark_critical_on` IS NOT NULL AND DATE(`it`.`mark_critical_on`) != '0000-00-00'
		";
		
		return $this->database->query( $sql );
	}
	
	/**
	 * 
	 * @param unknown_type $filter: filter created based on earlier parameter
	 */

	public function getCurrentStatusForAllTickets( $filter , $ticket_id = false, $ticket_code = false, $user_id = false, $limit = false ){
	
		$user_filter = '';
		$ticket_id_filter = '';
		$ticket_code_filter = '';
		
		if( $limit ){
			$limit_filter = "LIMIT 1";
		}
		
		if( $user_id ){
			
			$user_filter = " AND `it`.`customer_id`= '$user_id'";
		}
		
		if( $ticket_id ){
			
			$ticket_id_filter = "AND `it`.`id` = $ticket_id";	
		}
		if( $ticket_code ){
			
			$ticket_code_filter = "AND `ticket_code` = '$ticket_code'";
		}

		$sql = " SELECT
						`issue_code`,
						`it`.`id`,  
						CONCAT(`eup`.`firstname`, ' ', `eup`.`lastname`) AS `customer_name`,
						`eup`.`mobile`,
						`ticket_code`, 
						`issue_name` , 
						`customer_id`,
						`it`.`status` , 
						`it`.`priority` , 
						`it`.`department` ,  
						CASE WHEN `it`.`is_active` = 1 THEN 'VALID_TICKET' ELSE 'TEST_TICKET' END AS `ticket_validity`,
				 		`s`.`code` AS `last_updated_by`,
				 		`s1`.`code` AS `assigned_to`,
				 		`s`.`code` AS `assigned_by`,
				 		`s2`.`code` AS `created_by`, 
				 		`due_date`, 
				 		`it`.`created_date` ,
				 		`it`.`last_updated` ,  
						GROUP_CONCAT( `cf`.`type` ORDER BY `cf`.`id` SEPARATOR '^*^' ) AS `Custom-Field-Type`,
													 		
						GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
						
						GROUP_CONCAT( 
										CASE WHEN `cfd`.`value` IS NULL
											 THEN '[\"NA\"]' 
											 ELSE `cfd`.`value` 
											 END  ORDER BY `cf`.`id` SEPARATOR '^*^'
									) AS `Custom-Field-Value`
					FROM `issue_tracker` AS `it`
					JOIN `user_management`.`users` AS `eup` ON ( `eup`.`org_id` = `it`.`org_id` AND `eup`.`id` = `it`.`customer_id` )
					JOIN `masters`.`org_entities` AS `s` ON ( `s`.`id` = `it`.`assigned_by` AND `s`.`org_id` = '$this->current_org_id'  AND s.type = 'TILL')
					JOIN `masters`.`org_entities` AS `s2` ON ( `s2`.`id` = `it`.`created_by` AND `s2`.`org_id` = '$this->current_org_id' AND s2.type = 'TILL')
					JOIN `masters`.`org_entities` AS `s1` ON ( `s1`.`id` = `it`.`assigned_to` AND `s1`.`org_id` = '$this->current_org_id' AND s1.type = 'TILL')
					JOIN `user_management`.`custom_fields` AS `cf` ON (
					 
						`cf`.`org_id` = `it`.`org_id` AND 
						`cf`.`scope` = 'customer_feedback' 
						)
					LEFT OUTER JOIN `user_management`.`custom_fields_data` AS `cfd` ON ( 
						
						`cfd`.`org_id` = `cf`.`org_id` AND 
						`cfd`.`assoc_id` = `it`.`id` AND 
						`cf`.`id` = `cfd`.`cf_id` 
						)
					WHERE `it`.`org_id` = '$this->current_org_id' $filter $ticket_id_filter $ticket_code_filter $user_filter 
					GROUP BY `it`.`id` $limit_filter
					";

		$result =  $this->database->query( $sql );
		
//					JOIN `masters`.`admin_users` AS `au` ON ( `au`.`id` = `it`.`assigned_to` AND `au`.`org_id` = '$this->current_org_id' )
//		CONCAT(`au`.`first_name`,' ',`au`.`last_name`) AS `assigned_to`,		
		if( !$result ) {
					$sql = " SELECT
						`issue_code`,
						`it`.`id`,  
						CONCAT(`eup`.`firstname`, ' ', `eup`.`lastname`) AS `customer_name`,
						`eup`.`mobile`,
						`ticket_code`, 
						`issue_name` , 
						`customer_id`,
						`it`.`status` , 
						`it`.`priority` , 
						`it`.`department` ,  
						CASE WHEN `it`.`is_active` = 1 THEN 'VALID_TICKET' ELSE 'TEST_TICKET' END AS `ticket_validity`,
				 		s.code AS `last_updated_by`,
				 		`it`.`assigned_to` AS `assigned_to`,
				 		s.code AS `assigned_by`,
				 		s2.code AS `created_by`, 
				 		`due_date`, 
				 		`it`.`created_date` ,
				 		`it`.`last_updated` ,  
						GROUP_CONCAT( `cf`.`type` ORDER BY `cf`.`id` SEPARATOR '^*^' ) AS `Custom-Field-Type`,
													 		
						GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
						
						GROUP_CONCAT( 
										CASE WHEN `cfd`.`value` IS NULL
											 THEN '[\"NA\"]' 
											 ELSE `cfd`.`value` 
											 END  ORDER BY `cf`.`id` SEPARATOR '^*^'
									) AS `Custom-Field-Value`
					FROM `issue_tracker` AS `it`
					JOIN `user_management`.`users` AS `eup` ON ( `eup`.`org_id` = `it`.`org_id` AND `eup`.`id` = `it`.`customer_id` )
					JOIN `masters`.`org_entities` AS `s` ON ( `s`.`id` = `it`.`assigned_by` AND `s`.`org_id` = '$this->current_org_id' and s.type = 'TILL')
					JOIN `masters`.`org_entities` AS `s2` ON ( `s2`.`id` = `it`.`created_by` AND `s2`.`org_id` = '$this->current_org_id' and s2.type = 'TILL')
					JOIN `user_management`.`custom_fields` AS `cf` ON (
					 
						`cf`.`org_id` = `it`.`org_id` AND 
						`cf`.`scope` = 'customer_feedback' 
						)
					LEFT OUTER JOIN `user_management`.`custom_fields_data` AS `cfd` ON ( 
						
						`cfd`.`org_id` = `cf`.`org_id` AND 
						`cfd`.`assoc_id` = `it`.`id` AND 
						`cf`.`id` = `cfd`.`cf_id` 
						)
					WHERE `it`.`org_id` = '$this->current_org_id' $filter $ticket_id_filter $ticket_code_filter $user_filter 
					GROUP BY `it`.`id` $limit_filter
					";

			$result =  $this->database->query( $sql );
			include_once 'helper/memory_joiner/impl/MemoryJoinerFactory.php';
			include_once 'helper/memory_joiner/impl/MemoryJoinerType.php';
			
			$key_map = array( "assigned_to" => "{{joiner_concat(first_name,last_name)}}" );
			$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
			$result = $admin_user->prepareReport( $result, $key_map );
		}
		
		return $result;
		
	}
	
	
	/**
	 * Check if same ticket has been added for the customer
	 * 
	 * @param unknown_type $issue_code
	 * @param unknown_type $customer_id
	 */
	public function isIssueCodeExistsForCustomer( $issue_code, $customer_id, $reported_by ){
		
		$sql = "SELECT `id` 
				FROM `issue_tracker` 
				WHERE  `org_id` = '$this->current_org_id' 
				AND `customer_id` = '$customer_id' 
				AND `issue_code` = '$issue_code'
				AND `reported_by` = '$reported_by'";
		
		return $this->database->query_scalar($sql);
		
	}
	
	public function getRevisionDetails( $issue_id = false ){
		
		$issue_filter = '';
		
		if( $issue_id )
			$issue_filter = "AND ir.`issue_id` = '$issue_id'";
		
		$sql = "SELECT ir.`id` , ir.`revision_params` AS 'log' , s.code AS `assigned_by` , 
					   ir.`last_updated_on` AS 'created_date'
					FROM `issue_revision_log` ir
						JOIN `issue_tracker_log` it ON ir.`org_id` = it.`org_id` and ir.`issue_log_id` = it.`id` 
						JOIN `masters`.`org_entities` AS `s` ON ( `s`.`id` = ir.`last_updated_by` s.org_id = it.org_id and s.type = 'TILL')
					WHERE it.`org_id` = '$this->current_org_id'  $issue_filter ";
		
		return $this->database->query( $sql );
	}
/**
	 * Get Stores States by organization wise
	 */
	public function getStoresStateByStoreName( $name ){

		$sql = "

			SELECT `cd`.`id` as 'city_id' , `cd`.`city_name` ,
				   `sd`.`id` as 'state_id', `sd`.`state_name`,
				   `oe`.`id` as 'store_id', `oe`.`name` AS `store_name`
			FROM `masters`.`stores` AS `s` 
				JOIN `masters`.`city_details` AS `cd` ON ( `cd`.`id` = `s`.`city_id` ) 
				JOIN `masters`.`state_details` AS `sd` ON ( `sd`.`id` = `cd`.`state_id` )
				JOIN `masters`.`org_entities` AS `oe` ON ( `oe`.`id` = `s`.`id` AND `s`.`org_id` = `oe`.`org_id` )
			WHERE `s`.`org_id` = '$this->org_id' AND `oe`.`name` = '$name'
		";

		$res = $this->database->query_firstrow($sql);

		return $res;
	}
} // class : end

?>
