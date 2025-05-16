<?php

/**
 *
 * @author prakhar
 *
 */

include_once "controller/ApiParent.php";

class CCMSStoreController extends ApiParentController {

	/**

	* This is the DB Connection to the stores db - ideally, there should be
	* no DB object outside of the StoresController

	* @var unknown_type

	*/

	public $cm;
	public $testing;
	public $storeModel;
	private $store_db;

	public function __construct( StoreModule $sm, $currentuser = false, $testing = false) {

		parent::__construct('stores');

		if($currentuser != false){

			$this->currentuser = $currentuser;

			$this->currentorg = $this->currentuser->getProxyOrg();

		}

		$this->sm = $sm;
		$this->testing = $testing;
		//$this->storeModel = new ApiStoreModel($this, $testing);
	}


	function getStoresAsOptions($include_inactive = false){
			
		$org_id = $this->currentorg->org_id;
		$inactive_filter = "";
		if(!$include_inactive)
		$inactive_filter = " AND is_inactive = 0 ";

		$sql = "SELECT store_id, username , store_name FROM `stores_info` WHERE org_id = '$org_id'  $inactive_filter";
		$rtn = array();
		foreach ($this->db->query($sql) as $row) {
			$key = $row['username'] . "(" . $row['store_name'] . ")";
			$rtn[$key] = $row['id'];
		}
		ksort($rtn);
		return $rtn;

	}
	 
	function getIssuesByOrgIdAndSpecificFilters( $filter, $include_inactive = false ){

		if( !$include_inactive )
		$filter .= " AND `it`.`is_active` = '1' ";

		$org_id = $this->currentorg->org_id;

		$department_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DEPARTMENT_LABEL, 'DEPARTMENT*');
		$priority_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PRIORITY_LABEL, 'PRIORITY*');
		$status_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_STATUS_LABEL, 'STATUS*');
		$short_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_SHORT_DESC_LABEL, 'Short Description');
			
			
		$sql = " SELECT

						`it`.`id`,
							CONCAT(`u`.`username`) AS `last_updated_by`,
							CONCAT(`u1`.`username`) AS `currently_assigned_to`, 
							`issue_code` AS '$short_desc_label', 
							`priority` AS '$priority_label', 
							`status` AS '$status_label', 
							`department` AS '$department_label', 
							`ticket_code`,
							`created_date` , `reported_by` 
					
					FROM `issue_tracker` AS `it`
					JOIN `user_management`.`users` AS `u` ON ( `u`.`id` = `it`.`assigned_by` )
					JOIN `user_management`.`users` AS `u1` ON ( `u1`.`id` = `it`.`assigned_to` )
					WHERE `it`.`org_id` = '$org_id' $filter
					ORDER BY `it`.`id` DESC";
		return $this->db->query_table($sql, 'search_table');

	}

	function getAllStoreByOrganization(){
			
		$org_id = $this->currentorg->org_id;
			
		$sql = "SELECT `si`.*,`zh`.`zone_code` FROM
					`stores_info` AS `si`
					LEFT OUTER JOIN `stores_zone` AS `z` ON ( `z`.`org_id` = `si`.`org_id` AND `z`.`store_id` = `si`.`store_id` )
					LEFT OUTER JOIN `zones_hierarchy` `zh` ON (  `z`.`org_id` = `zh`.`org_id` AND `zh`.`zone_id` = `z`.`zone_id` )
					WHERE `si`.`org_id` = '$org_id' AND `is_inactive` = 0";
			
		return $this->db->query_table($sql);
			
	}

	function getIssueTrackerHistoryByIssueId($issue_id, $cf_ids = false, $customer_mobile = false, $download_all = false ){
			
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
			
		//labels
		$department_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DEPARTMENT_LABEL, 'DEPARTMENT*');
		$priority_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PRIORITY_LABEL, 'PRIORITY*');
		$status_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_STATUS_LABEL, 'STATUS*');
		$short_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_SHORT_DESC_LABEL, 'Short Description');
		$long_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_LONG_DESC_LABEL, 'Long Description');
		$assigned_to_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_ASSIGNED_TO_LABEL, 'Assigned To');
		$due_date_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DUE_DATE_LABEL, 'Due Date');
			
		//Get Tracker Details
		if($cf_ids){
			$cf_ids = implode(',', $cf_ids);

			$select_filter = " , GROUP_CONCAT(`cf`.`type` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Type`,
									GROUP_CONCAT( CASE WHEN `cf`.`attrs` = '' THEN '[\"NA\"]' ELSE `cf`.`attrs` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Attrs`, 
									GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
									GROUP_CONCAT( CASE WHEN `cfd`.`value` IS NULL THEN '[\"NA\"]' ELSE `cfd`.`value` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Value`";

			$join_filter = "
									JOIN `user_management`.`custom_fields` AS `cf` ON 		
										( `cf`.`org_id` = `it`.`org_id` AND `cf`.`id` IN ( $cf_ids )  )
									LEFT OUTER JOIN `user_management`.`custom_fields_data_log` AS `cfd` ON 
										( `cfd`.`org_id` = `it`.`org_id` AND `it`.`id` = `cfd`.`update_id` AND `cf`.`id` = `cfd`.`cf_id` )
								
										";
			$group_filter = "GROUP BY `it`.`id`";

		}

		if($issue_id){

			if( $download_all )
			$where_filter = " WHERE `tracker_id` IN ( $issue_id ) ";
			else
			$where_filter = " WHERE `tracker_id` = '$issue_id' ";
				
		}elseif($customer_mobile){

			$where_filter = " WHERE `customer_id` = '$customer_mobile' AND `it`.`org_id` = '$org_id'";
		}else{

			$where_filter = " WHERE `assigned_by` = '$store_id' AND `it`.`org_id` = '$org_id'";
		}

		$sql = " SELECT `it`.`tracker_id`,
							`it`.`id`, 
							`issue_code` AS '$short_desc_label', 
							`issue_name` AS '$long_desc_label', 
							`it`.`status` AS '$status_label', 
							`it`.`priority` AS '$priority_label', 
							`it`.`department` AS '$department_label',  
					 		CONCAT(`u`.`firstname`,' ',`u`.`lastname`) AS `last_updated_by`,`ticket_code`,
					 		CONCAT(`u1`.`firstname`,' ',`u1`.`lastname`) AS '$assigned_to_label', 
					 		`due_date` AS '$due_date_label', `last_updated` $select_filter
						FROM `issue_tracker_log` AS `it`
						JOIN `user_management`.`users` AS `u` ON ( `u`.`id` = `it`.`assigned_by` )
						JOIN `user_management`.`users` AS `u1` ON ( `u1`.`id` = `it`.`assigned_to` )
						$join_filter
						$where_filter
						$group_filter
						";
							
						return $this->db->query($sql,'tracker_history');
	}
	
	
	/**
	 * Method that builts and executes the query for fetching issue details based on customer.
	 * @param unknown_type $issue_id - Issue id
	 * @param unknown_type $reported_by - intouch/email/store
	 * @param unknown_type $cf_ids - custom field ids(displayable custom fields
	 *  based on the new config "CUSTOMER_FEEDBACK_REPORT_COLUMNS")
	 * @param unknown_type $customer_id - customer who logged this issue
	 * @param unknown_type $status - status
	 * @param unknown_type $type - type of the issue
	 * @param unknown_type $download_all 
	 * @param unknown_type $report_columns - "CUSTOMER_FEEDBACK_REPORT_COLUMNS" - 
	 * displayable columns from issue_tracker table
	 * does not include custom fields.
	 */
	
	function getIssueTrackerForClient($issue_id, $reported_by = 'INTOUCH', $cf_ids = false,
	 $customer_id = false, $status = 'ALL', $type = 'query', $download_all = false,$report_columns=array())
	 {
			
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;

		//labels
		
		//Get Tracker Details
		if($cf_ids){
			$cf_ids = implode(',', $cf_ids);

			$select_filter = " , GROUP_CONCAT(`cf`.`type` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Type`,
									GROUP_CONCAT( CASE WHEN `cf`.`attrs` = '' THEN '[\"NA\"]' ELSE `cf`.`attrs` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Attrs`, 
									GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
									GROUP_CONCAT( CASE WHEN `cfd`.`value` IS NULL THEN '[\"NA\"]' ELSE `cfd`.`value` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Value`";

			$join_filter = "
									JOIN `user_management`.`custom_fields` AS `cf` ON 		
										( `cf`.`org_id` = `it`.`org_id` AND `cf`.`id` IN ( $cf_ids )  )
									LEFT OUTER JOIN `user_management`.`custom_fields_data` AS `cfd` ON 
										( `cfd`.`org_id` = `it`.`org_id` AND `it`.`id` = `cfd`.`assoc_id` AND `cf`.`id` = `cfd`.`cf_id` )
								
										";
			$group_filter = "GROUP BY `it`.`id`";

		}

		if($issue_id){

			if( $download_all )
			$where_filter = " WHERE `it`.`id` IN ( $issue_id ) ";
			else
			$where_filter = " WHERE `it`.`id` = '$issue_id' ";

		}elseif($customer_id){

			$where_filter = " WHERE `customer_id` = '$customer_id' AND `it`.`org_id` = '$org_id'";
		}else{

			$where_filter = " WHERE `assigned_by` = '$store_id' AND `it`.`org_id` = '$org_id'";
		}
			
		if( $reported_by == 'EMAIL' ){

			$select_reported_by =
						"   `ie`.`name` AS `customer_name`,
							`ie`.`email` AS `email`,";

			$join_reported_by =
					"JOIN `Incoming_email_ids` AS `ie` ON ( `ie`.`id` = `it`.`customer_id` AND `ie`.`org_id` = `it`.`org_id` )";

		}else{

			$select_reported_by = " CONCAT(`eup`.`firstname`, ' ', `eup`.`lastname`) AS `customer_name`,
								`eup`.`mobile`, ";

			$join_reported_by = "JOIN `user_management`.`users` AS `eup` ON
								( `eup`.`org_id` = '$org_id' AND `eup`.`id` = `it`.`customer_id` )";

		}
			
		$status = strtoupper($status);
		$status_filter = '';
		if($status != 'ALL'){

			$status_filter = " AND `status` = '$status' ";
		}
		$select_clause=$this->buildSelectClauseForTicketData($report_columns);
		
		$sql = "SELECT
				$select_reported_by
				$select_clause   $select_filter	
				FROM `issue_tracker` AS `it`
				$join_reported_by
				JOIN `user_management`.`stores` AS `u` ON ( `u`.`store_id` = `it`.`assigned_by` )
				JOIN `user_management`.`stores` AS `u1` ON ( `u1`.`store_id` = `it`.`assigned_to` )
				$join_filter
				$where_filter $status_filter
				$group_filter
				";
		if($type == 'firstrow')
			return $this->db->query_firstrow($sql,'tracker_history');
		return $this->db->query($sql,'tracker_history');
	}
	
	
	/**
	 * method added by susi
	 * This method was added to support configurable display columns in ticket data api action
	 * This method build the columns in the select clause based on configuration.
	 * If the configuration is not present or no value is selected previous set of columns are retained.
	 * @param $report_columns
	 */
	private function buildSelectClauseForTicketData($report_columns)
	{
		//If not configured populate all the columns that were existing earlier.
		if(empty($report_columns))
			$report_columns=array( "id", "issue_code", "issue_name", "status", 'ticket_code',
			"priority","department","assigned_to","assigned_by","due_date");
		
		$department_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DEPARTMENT_LABEL, 'DEPARTMENT*');
		$priority_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PRIORITY_LABEL, 'PRIORITY*');
		$status_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_STATUS_LABEL, 'STATUS*');
		$short_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_SHORT_DESC_LABEL, 'Short Description');
		$long_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_LONG_DESC_LABEL, 'Long Description');
		$assigned_to_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_ASSIGNED_TO_LABEL, 'Assigned To');
		$due_date_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DUE_DATE_LABEL, 'Due Date');
			
		$alias_array=array("issue_code"=> $short_desc_label,
		"issue_name"=> $long_desc_label,
		"status"=> $status_label,
		"priority"=> $priority_label,
		"department" => $department_label,
		"due_date"=>$due_date_label,
		"assigned_to"=>$assigned_to_label,
		"assigned_by"=>"Assigned By",
		);
		
		$selector_array=array("assigned_by"=>"CONCAT(`u`.`firstname`,' ',`u`.`lastname`)",
		"assigned_to" => "CONCAT(`u1`.`firstname`,' ',`u1`.`lastname`)");
		$select_clause='';
		
		foreach($report_columns as $column)
		{
			if(!empty($selector_array[$column]))
				$select_clause.="$selector_array[$column]";
			else
				$select_clause.="`it`.`$column`";
			$select_clause.= " AS ";
			if(!empty($alias_array[$column]))
				$select_clause.="`$alias_array[$column]`";
			else
				$select_clause.="`".Util::beautify($column)."`";
			$select_clause.=",";
		}
		$select_clause=substr($select_clause,0,-1);
		return $select_clause;
	}

	function getIssuesOverview($status, $department){

		$open_status = $status[0];
		$closed_status = $status[count($status) - 1];

		$org_id = $this->currentorg->org_id;

		$department_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DEPARTMENT_LABEL, 'DEPARTMENT*');

		$sql = "
				SELECT 
					`department` AS '$department_label',
					SUM(CASE WHEN `status` != '$closed_status' THEN 1 ELSE 0 END) AS `issues_open`,
					SUM(CASE WHEN `status` = '$closed_status' THEN 1 ELSE 0 END) AS `issues_closed`,
					COUNT(*) AS `total_issues`,
					TRUNCATE(AVG(CASE WHEN `status` = '$closed_status' THEN DATEDIFF(NOW(), `created_date`) ELSE 0 END), 2) AS `Avg Closure Time In Days`,
					SUM(CASE WHEN `status` != '$closed_status' AND DATEDIFF(NOW(), `created_date`) >= 2 THEN 1 ELSE 0 END) AS `Issues Opened > 2 Days`
					 
				FROM `issue_tracker`
				WHERE `org_id` = '$org_id'
				GROUP BY `department`
		
		";

		return $this->db->query_table($sql,'tracker_history');
	}

	function updatePokeTable($poke_to, $msg){

		$user_id = $this->currentuser->user_id;
		$org_id = $this->currentorg->org_id;

		$sql = "
		
			INSERT INTO `poke_table`( `org_id`, `poked_from`, `poked_to`, `message`)
			VALUES ('$org_id', '$user_id', '$poke_to', '$msg')
		";

		return $this->db->insert($sql);

	}

	function getOverViewForStoretype($issue_id){

		$department_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DEPARTMENT_LABEL, 'DEPARTMENT*');
		$priority_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PRIORITY_LABEL, 'PRIORITY*');
		$status_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_STATUS_LABEL, 'STATUS*');
		$short_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_SHORT_DESC_LABEL, 'Short Description');
		$long_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_LONG_DESC_LABEL, 'Long Description');

		$sql = " SELECT
					
					`u1`.`mobile` AS `store_mobile`,
					`u1`.`email` AS `store_email`,
					CONCAT(`u1`.`firstname`, ' ', `u1`.`lastname`) AS `store_name`,
					`issue_code` AS '$short_desc_label', 
					`issue_name` AS '$long_desc_label',
					`it`.`status` AS '$status_label', 
					`it`.`priority` AS '$priority_label', 
					`it`.`department` AS '$department_label',
					`ticket_code`,
					 CONCAT(`u`.`firstname`, ' ', `u`.`lastname`) AS `created_by`, 
					`created_date`
				FROM `issue_tracker` AS `it`
				JOIN `user_management`.`users` AS `u` ON ( `u`.`id` = `it`.`assigned_by` )
				JOIN `user_management`.`users` AS `u1` ON ( `u1`.`id` = `it`.`customer_id` AND `u1`.`org_id` = `it`.`org_id` )
				WHERE `it`.`id` = '$issue_id'
		";

		return $this->db->query_table($sql);

	}

	function getOverViewForEmailType($issue_id){

		$department_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DEPARTMENT_LABEL, 'DEPARTMENT*');
		$priority_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PRIORITY_LABEL, 'PRIORITY*');
		$status_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_STATUS_LABEL, 'STATUS*');
		$short_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_SHORT_DESC_LABEL, 'Short Description');
		$long_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_LONG_DESC_LABEL, 'Long Description');
		$customer_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_CUSTOMER_NAME_LABEL, 'Customer Name');
		$email_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_EMAIL_LABEL, 'Email Id');

		$sql = " SELECT
					
					`name` AS '$customer_label',
					`ie`.`email` AS '$email_label',
					`issue_code` AS '$short_desc_label', 
					`issue_name` AS '$long_desc_label',
					`it`.`status` AS '$status_label', 
					`it`.`priority` AS '$priority_label', 
					`it`.`department` AS '$department_label',
					`ticket_code`,
					 CONCAT(`u`.`firstname`, ' ', `u`.`lastname`) AS `created_by`, 
					`created_date`
				FROM `issue_tracker` AS `it`
				JOIN `user_management`.`users` AS `u` ON ( `u`.`id` = `it`.`assigned_by` )
				JOIN `Incoming_email_ids` AS `ie` ON ( `ie`.`id` = `it`.`customer_id` AND `ie`.`org_id` = `it`.`org_id` )
				WHERE `it`.`id` = '$issue_id'
		";

		return $this->db->query_table($sql);

	}

	function getOverViewForCustomerType($issue_id){

		$department_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_DEPARTMENT_LABEL, 'DEPARTMENT*');
		$priority_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PRIORITY_LABEL, 'PRIORITY*');
		$status_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_STATUS_LABEL, 'STATUS*');
		$short_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_SHORT_DESC_LABEL, 'Short Description');
		$long_desc_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_LONG_DESC_LABEL, 'Long Description');
		$customer_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_CUSTOMER_NAME_LABEL, 'Customer Name');
		$email_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_EMAIL_LABEL, 'Email Id');
		$mobile_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_MOBILE_LABEL, 'Mobile Number');

		$sql = " SELECT
						`eup`.`mobile` AS '$mobile_label',
						CONCAT(`eup`.`firstname`, ' ', `eup`.`lastname`) AS '$customer_label',
						`eup`.`email` AS '$email_label', 
						`issue_code` AS '$short_desc_label', 
						`issue_name` AS '$long_desc_label',
						`it`.`status` AS '$status_label', 
						`it`.`priority` AS '$priority_label', 
						`it`.`department` AS '$department_label',
						`ticket_code`,
						 CONCAT( `u`.`firstname`, ' ', `u`.`lastname` ) AS `created_by`,
						`created_date`
					FROM `issue_tracker` AS `it`
					JOIN `user_management`.`stores` AS `u` ON ( `u`.`store_id` = `it`.`assigned_by` )
					JOIN `user_management`.`users` AS `eup` ON 
						( `eup`.`id` = `it`.`customer_id` AND `eup`.`org_id` = `it`.`org_id` )
					WHERE `it`.`id` IN ( $issue_id ) 
					";

		return $this->db->query_table($sql);

	}

	function createRegistrationForm(&$reg_form, $reg_mobile){

		$reg_form->title = 'Register The Customer';

		$reg_form->addField( 'text', 'firstname', 'First Name' );
		$reg_form->addField( 'text', 'lastname', 'Last Name' );
		$reg_form->addField( 'text', 'mobile', 'Mobile', $reg_mobile );
		$reg_form->addField( 'text', 'email', 'Email Address', '', '', Util::$optional_email_pattern, 'Email has to be valid' );
		$reg_form->addField( 'textarea', 'address', 'Address' );

	}

	function getConstantFieldForIssue($issue_id, $type, $reported_by){

		if($type == 'STORE')
		return $this->getOverViewForStoretype($issue_id);

		if($reported_by == 'EMAIL')
		return $this->getOverViewForEmailType($issue_id);
		else
		return $this->getOverViewForCustomerType($issue_id);

	}

	function addAssignedUserToList($user_id){

		$org_id = $this->currentorg->org_id;

		$sql = "
			INSERT 
			INTO `assigned_to_user`
			VALUES (null, '$org_id', '$user_id')
		";

		return $this->db->update($sql);

	}

	function removeUserFromList($user_id){

		$org_id = $this->currentorg->org_id;

		$sql = "
			DELETE 
			FROM `assigned_to_user`
			WHERE org_id = '$org_id' AND `user_id` = '$user_id'
		";

		$this->db->update($sql);
	}

	function getAssignedOption(){

		$org_id = $this->currentorg->org_id;

		$sql = "
			SELECT CONCAT(`firstname`, ' ', `lastname`) AS `username`, `u`.`store_id` AS `id`
			FROM `user_management`.`stores` AS `u`
			JOIN `assigned_to_user` AS `a` ON ( `a`.`org_id` = `u`.`org_id` AND `a`.`user_id` = `u`.`store_id`)
			WHERE `a`.`org_id` = '$org_id'
		";

		$res = $this->db->query($sql);

		foreach($res as $r){
				
			$ret[$r['username']] = $r['id'];
		}

		return $ret;
	}

	function getIssueObject($issue_id = false, $update_id = false){

		$org_id = $this->currentorg->org_id;

		if($update_id){
				
			$issue = new IssueTracker($org_id, $update_id, true);
		}else
		$issue = new IssueTracker($org_id, $issue_id, false);

		return $issue;
	}

	function generateTicketCode($issue){

		$issue_ticket = $this->currentorg->getConfigurationValue(CONF_ISSUE_TICKET, true);

		if($issue_ticket){
				
			$ticket_code_length = $this->currentorg->getConfigurationValue(CONF_TICKET_CODE_LENGTH, 8);
			$alphanumeric = $this->currentorg->getConfigurationValue(CONF_TICKET_CODE_IS_ALPHANUMERIC, true);
				
			do{

				$ticket_code = Util::generateRandomCode($ticket_code_length, $alphanumeric);
				$ticket_code = Util::makeReadable($ticket_code);

			}while($issue->ticketCodeExists($ticket_code));
				
			$issue->setTicketCode($ticket_code);
		}else
		$issue->setTicketCode(-1);

	}

	function createEmailTicket($id, $subject, $body){

		$org_id = $this->currentorg->org_id;

		$issue = new IssueTracker($org_id);

		$default_priority = $this->currentorg->getConfigurationValue(DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PRIORITY, false);
		$default_status = $this->currentorg->getConfigurationValue(DEFAULT_CUSTOMER_FEEDBACK_EMAIL_STATUS, false);
		$default_department = $this->currentorg->getConfigurationValue(DEFAULT_CUSTOMER_FEEDBACK_EMAIL_DEPARTMENT, false);
		$assigned_to = $this->currentorg->getConfigurationValue(SELECT_DEFAULT_STORE_FOR_FEEDBACK, false);

		$ticket_code = $this->generateTicketCode($issue);

		$issue->setPriority($default_priority);
		$issue->setDepartment($default_department);
		$issue->setStatus($default_status);
		$issue->setCreatedDate(date('Y-m-d'));
		$issue->setCustomerId($id);
		$issue->setReportedBy('EMAIL');
		$issue->setAssignedBy($this->currentuser->user_id);
		$issue->setAssignedTo($assigned_to);
		$issue->setIssueCode($subject);
		$issue->setIssueName($body);
		$issue->setType('CUSTOMER');

		$id = $issue->save();

		if($id)
		return $issue;

		return false;
	}

	function storeMailAndName($sender_name, $sender_email){

		$org_id = $this->currentorg->org_id;

		$sql = "
			INSERT INTO `Incoming_email_ids`
			VALUES (NULL, '$org_id','$sender_name', '$sender_email', NOW())
			ON DUPLICATE KEY UPDATE `name` = '$sender_name', id=LAST_INSERT_ID(id)
		";

		return $this->db->insert($sql);
	}

	function getUserEmailByMailerId($customer_email_id){

		$org_id = $this->currentorg->org_id;

		$sql = "
			SELECT `email` 
			FROM `Incoming_email_ids`
			WHERE `id` = '$customer_email_id' AND `org_id` = '$org_id'
		";

		return $this->db->query_scalar($sql);

	}

	function getCustomFieldName($option){

		$name = array();

		foreach( $option AS $o )
		array_push($name, $o['name']);

		return $name;
	}

	function getCustomFieldIdsAsOptions(CustomFields  $cm){

		$org_id = $this->currentorg->org_id;

		$option = $cm->getCustomFieldsByScope($org_id, CUSTOMER_CUSTOM_FEEDBACK);

		$cf_ids = array();
		foreach( $option AS $o )
		array_push($cf_ids, $o['id']);

		return array($option, $cf_ids);
	}

	function addAjaxConditionsForAddIssue(LoyaltyModule $lm, Form $form){

		$store_check = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PURCHASED_AT, false);
		$org_id = $this->currentorg->org_id;

		if($store_check){

			$cf = new CustomFields();
			$res = $cf->getCustomFieldByName($org_id, $store_check);
			$store_name = $cf->getFieldName($res['name']);
			$email_check= $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PURCHASED_AT_EMAIL, false);
			if($email_check)
			{
				$cf = new CustomFields();
				$email_res = $cf->getCustomFieldByName($org_id, $email_check);
				$email_name = $cf->getFieldName($email_res['name']);
			}
		}

		//Auto Search
		$lm->js->addAutoSuggest($form->getFieldName('customer_id'), 'loyalty', 'getRegisteredUsersByMobile');

		//Auto Fill Up Name
		$lm->js->populateFieldWithAjax(
		$form->getFieldName('customer_id'), $form->getFieldName('customer_name'), Util::genUrl('loyalty', 'getcustomerinfobymobilebyajax'),
		array($form->getFieldName('customer_id')), array('name')
		);

		//Auto Fill Up Email
		$lm->js->populateFieldWithAjax(
		$form->getFieldName('customer_id'), $form->getFieldName('customer_email'), Util::genUrl('loyalty', 'getcustomerinfobymobilebyajax'),
		array($form->getFieldName('customer_id')), array('email')
		);

		if($store_check){

			//add for stores info
			$lm->js->populateFieldWithAjax(
			$form->getFieldName('store_state_skip'), $form->getFieldName('store_city_skip'), Util::genUrl('store', 'getstorecitybystorestates'),
			array($form->getFieldName('store_state_skip')), array(), 'select'
			);

			//fill username by city
			$lm->js->populateFieldWithAjax(
			$form->getFieldName('store_city_skip'), $form->getFieldName('store_username_skip'), Util::genUrl('store', 'getusernamebystorecity'),
			array($form->getFieldName('store_city_skip')), array(), 'select'
			);
				
				
			//Fill username by store id
			$lm->js->populateFieldWithAjax(
			$form->getFieldName('store_username_skip'), $form->getFieldName($store_name), Util::genUrl('store', 'getUserNameByStoreId'),
			array($form->getFieldName('store_username_skip')), array()
			);
				
			//Fill email by user name
			$lm->js->populateFieldWithAjax(
			$form->getFieldName('store_username_skip'), $form->getFieldName($email_name), Util::genUrl('store', 'getEmailIdByUserName'),
			array($form->getFieldName('store_username_skip')), array()
			);
			//$lm->js->tri
		}
	}

	function saveIssueTrackerFields(IssueTracker $issue, $params){

		$issue->setIssueCode($params['issue_code']);
		$issue->setIssueName($params['issue_name']);
		$issue->setAssignedBy( $this->currentuser->user_id );

		$mobile = $params['customer_id'];
		$mobile_provided = $mobile;
		$lm = new LoyaltyModule();
		$org_id = $this->currentorg->org_id;

		if($mobile){
			if(!Util::checkMobileNumber($mobile))
			Util::redirect('store', 'addissue', false, 'Invalid Mobile Number');

			$customer = UserProfile::getByMobile($mobile);
			if(!$customer)
			Util::redirect('store', "addissue/0/0/$mobile_provided", false, 'User Not Registered With Us...');
				
			$loyalty_id = $lm->loyaltyController->getLoyaltyId($org_id, $customer->user_id);
			if (!$loyalty_id)
			Util::redirect('store', "addissue/0/0/$mobile_provided", false, 'User Not Registered In Loyalty Program...');
				
			$customer_id = $customer->user_id;
		}else
		Util::redirect('store', 'addissue', false, 'Invalid Mobile Number');


		$issue->setCustomerId( $customer_id );
		$issue->setCreatedDate( date('Y-m-d') );
		$issue->setReportedBy('INTOUCH');
		$issue->setType('CUSTOMER');

	}

	function signalFeedbackListener($customFields, IssueTracker $issue, Form $form, $option, $on_update, $lm){

		$org_id = $this->currentorg->org_id;
		$issue_id = $issue->getId();

		$customFields->processCustomFieldsFormForAssocID($form, $org_id, CUSTOMER_CUSTOM_FEEDBACK, $issue_id);
		$user_id = $issue->getCustomerId();

		$send_sms = false;

		if($issue->getReportedby() == 'EMAIL'){

			$user = $this->currentuser;
				
			$customer_email_id = $issue->getCustomerId();
			$customer_email = $this->getUserEmailByMailerId($customer_email_id);
		}else{

			$user = UserProfile::getById($user_id);

			if($user){

				$eup = new ExtendedUserProfile($user, $this->currentorg);

				if($eup){

					$customer_email = $eup->getEmail();
					$send_sms = true;
				}

			}else{

				$user = $this->currentuser;
			}
		}

		if( $issue->getType() == 'STORE' ){

			$send_sms = false;
			$customer_email = $user->email;
		}


		$params = array(

				'user_id' => $user->user_id, 
				'store_id' => $this->currentuser->user_id, 
				'ticket_id' => $issue->getId(), 
				'customer_email' => $customer_email, 
				'on_update' => $on_update,
				'send_sms' => $send_sms
		);

		foreach($option AS $o){
				
			$value = $customFields->getCustomFieldValueByFieldName($org_id, CUSTOMER_CUSTOM_FEEDBACK, $issue->getId(), $o['name']);
			$value = json_decode($value, true);
				
			$params['custom_'.$o['name']] = $value[0];
		}

		$ret = $lm->signalListeners(EVENT_CUSTOMER_FEEDBACK, $params);

		return $ret;
	}

	function getStoresStateByOrgAsOptions(){

		$org_id = $this->currentorg->org_id;

		$sql = "

			SELECT `sd`.`id`, `state_name`
			FROM `masters`.`stores` AS `s` 
			JOIN `masters`.`city_details` AS `cd` ON ( `cd`.`id` = `s`.`city_id` ) 
			JOIN `masters`.`state_details` AS `sd` ON ( `sd`.`id` = `cd`.`state_id` )
			WHERE `s`.`org_id` = '$org_id'
		";

		$res = $this->db->query($sql);

		foreach($res as $r){
				
			if($r['state_name'])
			$options[$r['state_name']] = $r['id'];
		}

		return $options;
	}

	function getUserNameByStoreCity( $city_id ){

		$org_id = $this->currentorg->org_id;

		$sql = "
			SELECT 
				`oe`.`name` AS `store_name`,
				`s`.`id` AS `store_id`
			FROM `masters`.`stores` AS `s`
			JOIN `masters`.`org_entities` AS `oe` ON ( `oe`.`id` = `s`.`id` AND `s`.`org_id` = `oe`.`org_id` )
			WHERE `s`.`org_id` = '$org_id' AND `city_id` = '$city_id'
		";

		$res = $this->db->query($sql);

		foreach($res as $r){
				
			if($r['store_name'])
			$options[$r['store_name']] = $r['store_id'];
		}

		return $options;
	}

	function getEmailIdByUserName( $store_id ){

		$org_id = $this->currentorg->org_id;

		$sql = "
			SELECT `email` 
			FROM `masters`.`stores`
			WHERE `org_id` = '$org_id' AND `id` = '$store_id'
		";

		return $this->db->query_firstrow($sql);
	}

	function getUserNameByStoreId( $store_id ){

		$org_id = $this->currentorg->org_id;

		$sql = "
			SELECT `name` AS `user_name`
			FROM `masters`.`org_entities`
			WHERE `org_id` = '$org_id' AND id = '$store_id'
		";

		return $this->db->query_firstrow($sql);
	}


	function getCityByStoresState( $state_id ){

		$org_id = $this->currentorg->org_id;

		$sql = "

			SELECT `cd`.`id`, `city_name`
			FROM `masters`.`city_details` AS `cd`  
			WHERE `cd`.`state_id` = '$state_id'
		";

		$res = $this->db->query($sql);

		foreach($res as $r){
				
			if($r['city_name'])
			$options[$r['city_name']] = $r['id'];
		}

		return $options;

	}

	function addStoreInfoToForm(&$form, $issue_id){

		$store_check = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PURCHASED_AT, false);
		$org_id = $this->currentorg->org_id;

		if($store_check){

			$cf = new CustomFields();
			$res = $cf->getCustomFieldByName($org_id, $store_check);
			$value = $cf->getCustomFieldValueByFieldName($org_id, CUSTOMER_CUSTOM_FEEDBACK, $issue_id, $res['name']);
			if($value)
			$user_name =json_decode($value, false);
				
			$store_label = $res['label'];
			$store_name = $cf->getFieldName($res['name']);
				
			$stores_state = $this->getStoresStateByOrgAsOptions();
				
			$form->addField('select', 'store_state_skip', 'Store State', $stores_state);
			$form->addField('select', 'store_city_skip', 'Store City', array('Select State'));
			$form->addField('select', 'store_username_skip', 'Store User', array('Select User'));
			$form->addField('text', $store_name, $store_label, $user_name[0], array('readonly' => true));
			$email_check= $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_PURCHASED_AT_EMAIL, false);
			if($email_check)
			{
				$cf = new CustomFields();
				$email_res = $cf->getCustomFieldByName($org_id, $email_check);
				$email = $cf->getCustomFieldValueByFieldName($org_id, CUSTOMER_CUSTOM_FEEDBACK, $issue_id, $email_res['name']);
				if($email)
				$email_value = json_decode($email, false);
				$email_label = $email_res['label'];
				$email_name = $cf->getFieldName($email_res['name']);
				$this->logger->debug("Email field details : ".$email_name. '-'.$email_label. '-' .$email_value);
				$form->addField('text', $email_name, $email_label, $email_value[0], array('readonly'=>true));
				$form->addField('separator','email_sep_1','Email');
			}
		}
	}

	function addCustomerInfoToForm(&$form){

		$mobile_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_MOBILE_LABEL, 'Mobile Number');
		$customer_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_CUSTOMER_NAME_LABEL, 'Customer Name');
		$email_label = $this->currentorg->getConfigurationValue(CUSTOMER_FEEDBACK_EMAIL_LABEL, 'Email Id');

		$form->addField('text', 'customer_id', $mobile_label);
		$form->addField('text', 'customer_name', $customer_label, '', array('readonly' => true));
		$form->addField('text', 'customer_email', $email_label, '', array('readonly' => true));
	}

	function getNonUpdatedIssueByTimeInterval( $time_interval ){

		$date_to_consider = Util::getDateByDays( true, $time_interval );

		$this->storeModel->getQueryResult();
		return $this->storeModel->getNonUpdatedIssuesByDateAndOrg( $date_to_consider );
	}

	function createRandomUserForm( &$form ){

		$form->addField( 'text', 'bill_amount_g', 'Bill Amount Greater Than', -1 ,array(), '/\d+/','only digits' );
		$form->addField( 'text', 'bill_amount_l', 'Bill Amount Less Than', -1 ,array(), '/\d+/','only digits' );

		$form->addField( 'datepicker', 'shop_date_g', 'Shoped Date Greater Than' );
		$form->addField( 'datepicker', 'shop_date_l', 'Shoped Date Less Than' );

		$form->addField( 'text', 'entries', 'Entries Required', -1 ,array(), '/\d+/','only digits' );
		$form->addField( 'checkbox', 'confirm', 'Please Confirm You Action' );

	}

	function validateRandomUserForm( &$form ){

		if( $form->isValidated() ){
				
			$params = $form->parse();
				
			$bill_amount_g = $params['bill_amount_g'];
			$bill_amount_l = $params['bill_amount_l'];
				
			$shop_date_g = $params['shop_date_g'];
			$shop_date_l = $params['shop_date_l'];
				
			$entries = $params['entries'];
			$confirm = $params['confirm'];

			$this->storeModel->setStartDate( $shop_date_g );
			$this->storeModel->setEndDate( $shop_date_l );
			if( $confirm ){

				$this->storeModel->InsertResult();
				$this->storeModel->populateUserIdsForRandomUsers( $bill_amount_g, $bill_amount_l );

				$this->storeModel->getQueryResult();
				$result =  $this->storeModel->getRandomUsersWithRegisteredStores( $entries );

				$users = array();
				foreach ( $result as &$r ){

					array_push( $users, $r['id'].','.$this->org_id );
					unset( $r['id'] );
				}

				$user_ids = implode( "),(", $users );
				$this->storeModel->InsertResult();
				$this->storeModel->populateRandomUsers( $user_ids );

				$this->storeModel->updateTable();
				$this->storeModel->emptyTable( 'random_users_to_export' );

				$this->storeModel->InsertResult();
				$this->storeModel->populateExportRandomUsers( $user_ids );

				return $result;
			}
				
		}
	}

	public function getAllIssueIds(){

		$this->storeModel->getQueryResult();
		$issue_array = $this->storeModel->getIssueIds();
		$issues = array();
		foreach( $issue_array as $ia )
		array_push( $issues, $ia['id'] );

		$issue_csv = Util::joinForSql( $issues );

		return $issue_csv;
	}

	public function makeIssueInactiveById( $issue_id, $status , $inactive_status = 0 ){

		$sql = "
				UPDATE
					`issue_tracker`
					SET `is_active` = '$inactive_status'
					WHERE `id` = '$issue_id' AND `status` = '$status' AND `org_id` = '$this->org_id'
		";
		
		return  $this->db->update( $sql );
	}

	/**
	 * create form for downloading of master forms
	 */
	public function createMasterDownloadForm(){

		$form = new Form( 'master', 'post' );

		$store_option = $this->getAssignedOption();
		$store_option['All'] = -1;

		$form->addField( 'separator', 'sep_0', 'created date sep.');
		$form->addField( 'datepicker', 'start_created_date', 'Created Date Between');
		$form->addField( 'datepicker', 'end_created_date', '...And');

		$form->addField( 'separator', 'sep_1', 'last updated sep.');
		$form->addField( 'datepicker', 'start_last_updated_date', 'Last Updated Date Between');
		$form->addField( 'datepicker', 'end_last_updated_date', '...And');

		$form->addField( 'separator', 'sep_2', 'assigned to sep.');
		$form->addField('select', 'assigned_to', 'Assigned To', -1, array('list_options' => $store_option));

		$form->addField( 'separator', 'sep_3', 'inactive filter sep.');
		$form->addField( 'select', 'include_inactive', 'Include Inactive', array( 'Yes' => true, 'No' => false ) );

		$form->addField( 'separator', 'sep_4', 'download.');
		$form->addField( 'checkbox', 'download', 'download as csv' );

		return $form;
	}

	private function getDateFilter( $field_name, $start_date , $end_date, &$filter ){

		if( !$start_date || !$end_date )
		return;
			
		$date_filter = " `$field_name` BETWEEN '$start_date' AND '$end_date' ";
			
		if( $filter )
		$filter .= ' AND '.$date_filter;
		else
		$filter = $date_filter;
	}

	private function getStoreFilter( $field_name, $store_id, &$filter ){

		if( $store_id == -1 )
		return;
			
		$store_filter = " `$field_name` = '$store_id' ";
		if( $filter )
		$filter .= ' AND '.$store_filter;
		else
		$filter = $store_filter;
	}

	public function validateMasterDownloadForm( Form $form ){

		if( $form->isValidated() ){
				
			$params = $form->parse();
			$filter = null;
				
			//1st check
			$start_created_date = $params['start_created_date'];
			$end_created_date = $params['end_created_date'];
			if( $end_created_date < $start_created_date ){

				$this->flash( 'Created End Date Must be greater than the start date ');
				return;
			}
			$this->getDateFilter( 'created_date', $start_created_date, $end_created_date, $filter );
				
			//2nd check
			$start_last_updated_date = $params['start_last_updated_date'];
			$end_last_updated_date = $params['end_last_updated_date'];
			if( $end_last_updated_date < $start_last_updated_date ){

				$this->flash( 'Last Updated End Date Must be greater than the start date ');
				return;
			}
			$this->getDateFilter( 'last_updated', $start_last_updated_date, $end_last_updated_date, $filter );
				
			//3rd check
			$assigned_to = $params['assigned_to'];
			$this->getStoreFilter( 'assigned_to', $assigned_to, $filter );
				
			//4th check
			$include_inactive = $params['include_inactive'];
			$this->getInactiveFilter( $include_inactive, $filter );
				
			if( $filter )
			$filter = 'AND '.$filter;

			$table = $this->renderMasterQueryToTable( $filter );

			if( $params['download'] ){

				$spreadsheet = new Spreadsheet();
				$spreadsheet->loadFromTables(array( $table ) )->download( "Master Sheet", 'csv' );
			}

			return $table;
		}
	}

	private function getInactiveFilter( $include_inactive, &$filter ){

		if( !$include_inactive )
		$inactive_filter = ' `it`.`is_active` = 1 ';
		else
		return;

		if( $filter )
		$filter .= ' AND '.$inactive_filter;
		else
		$filter = $inactive_filter;
	}

	public function getCurrentStatusForAllTickets( $filter ){


		$sql = " SELECT
						CONCAT(`eup`.`firstname`, ' ', `eup`.`lastname`) AS `customer_name`,
						`eup`.`mobile`,
						`ticket_code`, 
						`issue_code` , 
						`issue_name` , 
						`it`.`status` , 
						`it`.`priority` , 
						`it`.`department` ,  
						CASE WHEN `it`.`is_active` = 1 THEN 'VALID_TICKET' ELSE 'TEST_TICKET' END AS `ticket_validity`,
				 		CONCAT(`s`.`firstname`,' ',`s`.`lastname`) AS `last_updated_by`,
				 		CONCAT(`s1`.`firstname`,' ',`s1`.`lastname`) AS `assigned_to`,
				 		CONCAT(`s2`.`firstname`,' ',`s2`.`lastname`) AS `assigned_by`, 
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
					JOIN `user_management`.`stores` AS `s` ON ( `s`.`store_id` = `it`.`assigned_by` )
					JOIN `user_management`.`stores` AS `s1` ON ( `s1`.`store_id` = `it`.`assigned_to` )
					JOIN `user_management`.`stores` AS `s2` ON ( `s2`.`store_id` = `it`.`assigned_by` )
					JOIN `user_management`.`custom_fields` AS `cf` ON (
					 
						`cf`.`org_id` = `it`.`org_id` AND 
						`cf`.`scope` = 'customer_feedback' 
						)
					LEFT OUTER JOIN `user_management`.`custom_fields_data` AS `cfd` ON ( 
						
						`cfd`.`org_id` = `cf`.`org_id` AND 
						`cfd`.`assoc_id` = `it`.`id` AND 
						`cf`.`id` = `cfd`.`cf_id` 
						)
					WHERE `it`.`org_id` = '$this->org_id' $filter
					GROUP BY `it`.`id`
					";

		return $this->db->query( $sql );
	}

	public function renderMasterQueryToTable( $filter ){

		$master_set = $this->getCurrentStatusForAllTickets( $filter );

		$custom_fields = new CustomFields();
		$cf_attrs = $custom_fields->getCustomFieldAttrsAsOption( $this->org_id, 'customer_feedback' );

		$custom_value = array();
		foreach( $master_set as &$row ){

			$store_data = array( 'ticket_code' => $row['ticket_code'] );

			//custom field name and the counts are in group concat format
			$cf_names = explode('^*^', $row['Custom-Field-Name']);
			$cf_value = explode('^*^', $row['Custom-Field-Value']);
			$cf_type = explode('^*^',  $row['Custom-Field-Type']);

			unset( $row['Custom-Field-Name'] );
			unset( $row['Custom-Field-Value'] );
			unset( $row['Custom-Field-Type'] );

			for( $i = 0; $i < count( $cf_names ); $i++ ) {

				//Store as ret_val_<cf_index>
				$custom_values = json_decode( $cf_value[$i], true );

				if( !$custom_values && strlen( $cf_value[$i] ) > 0 ){
						
					$cf_value[$i] = str_replace( array( "\r\n", "\n", "\r"), ' ', $cf_value[$i] );
					$custom_values = json_decode( $cf_value[$i], true );
				}
				$cf_value[$i] = $custom_values;
					
				$index = 'ret_val_'.$i;
					
				$value_to_show = $cf_value[$i];
				if( $cf_type[$i] == 'select' ){

					$name = $cf_names[$i];
					$attrs = json_decode( $cf_attrs[$name], true );
					if( is_array( $attrs ) )
					$attrs = array_flip( $attrs );

					if( $value_to_show == 'NA' ){
						//skip it
					}else if( is_array( $value_to_show ) ){
							
						$values = array();
						foreach( $value_to_show as $value ){

							array_push( $values, $attrs[$value] );
						}
							
						$value_to_show = $values;
					}else{
							
						$value_to_show = $attrs[$value];
					}
				}
					
				if( is_array( $value_to_show ) )
				$value_to_show = implode( ',' , $value_to_show );

				$store_data[$index] =  $value_to_show;
			}

			array_push( $custom_value, $store_data );
			$custom_field_names = $cf_names;
		}

		$table = new Table( 'new_table' );
		$table->importArray( $master_set );

		$q_new = array(
			'name' => $custom_field_names,
			'ret' => $custom_value
		);

		function addRow( $row, $params ){
				
			foreach( $params['ret'] as $param ){

				if( $param['ticket_code'] == $row['ticket_code'] ){
						
					$return_val_all = array();
					for( $i = 0; $i < count( $params['name'] ); $i++ ){

						$val = "ret_val_".$i;
						$return_val_all[$i] = $param[$val];
					}
						
					return $return_val_all;
				}
			}
		}

		if( count( $q_new['name'] ) > 0 ){
				
			$table->addManyFieldsByMap( $q_new['name'], 'addRow' ,$q_new );
		}

		return $table;
	}

}
?>
