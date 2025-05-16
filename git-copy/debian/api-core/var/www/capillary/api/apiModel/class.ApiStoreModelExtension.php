<?php

include_once 'apiModel/class.ApiStore.php';

/**
 *
 * @author prakhar
 *
 *This class extends the org entity.
 *
 *Store model Class Represents
 * all the store, store unit, store template level info
 * all the complicated joins of the stores
 * shgould happen in here
 */
class ApiStoreModelExtension extends ApiStoreModel{

    private $pl_db;
	/**
	 * CONSTRUCTOR
	 */
	public function __construct( ){

		parent::ApiStoreModel();
                $this->pl_db = new Dbase("performance");
	}

	private function getStoreInfoDetailsSqlQuery( $where_clause, $org_id )
	{
		$sql = "
		
			SELECT
				`oe`.`id`,
				`oe`.`code` AS `store_code`, `oe`.`name` AS `store_name`, `oe`.`description` AS `store_description`,
				`oe`.`admin_type` AS `store_type`,
				`sc`.`name` AS `currency_name`, `sc`.`iso_code` as `currency_code`,
				`sc`.`symbol` AS `currency_symbol`,
				`sc`.`iso_code` AS `currency_code_alpha`,
				`sc`.`iso_code_numeric` AS `currency_code_numeric`,
				`sl`.`language`, `sl`.`iso_code` as `language_code`, slc.code as language_locale,
				`st`.`coordinates` AS `timezone_coordinates`,
				`st`.`timezone` AS `timezone_label`, `st`.`timezone_offset` AS `timezone_offset`,
				`st`.`std_offset`, `st`.`summer_offset`, `s`.`mobile`, `s`.`email`, `s`.`land_line`,
				`s`.`lat` AS `Latitude`, `s`.`long` AS `Longitude`, `s`.`external_id`, `s`.`external_id_1`, `s`.`external_id_2`,
				`c`.`name` AS `country_name`,  `c`.`code` AS `country_code`, `sd`.`state_name`,`cd`.`city_name`,`ad`.`area_name`,
				`stp`.`s_name` AS `name_template_sms`, `stp`.`s_email` AS `email_template_sms`,
				`stp`.`s_mobile` AS `mobile_template_sms`, `stp`.`e_name` AS `name_template_email`,
				`stp`.`e_mobile` AS `mobile_template_email`, `stp`.`e_email` AS `email_template_email`
				
			FROM `org_entities` AS `oe`
			JOIN `stores` AS `s` ON ( `s`.`id` = `oe`.`id` )
				
			LEFT OUTER JOIN `supported_currencies` AS `sc` ON ( `sc`.`id` = `oe`.`currency_id` )
			LEFT OUTER JOIN `supported_languages` AS `sl` ON ( `sl`.`id` = `oe`.`language_id` )
			LEFT JOIN countries AS slc ON slc.id=sl.country_id
			LEFT OUTER JOIN `supported_timezones` AS `st` ON ( `st`.`id` = `oe`.`time_zone_id` )
				
			LEFT OUTER JOIN `city_details` AS `cd` ON ( `cd`.`id` = `s`.`city_id` )
			LEFT OUTER JOIN `countries` AS `c` ON ( `cd`.`country_id` = `c`.`id` )
			LEFT OUTER JOIN `area_details` AS `ad` ON ( `ad`.`id` = `s`.`area_id` )
			LEFT OUTER JOIN `state_details` AS `sd` ON ( `sd`.`id` = `cd`.`state_id` )
				
			LEFT OUTER JOIN `store_templates` AS `stp` ON ( `stp`.`store_id` = `oe`.`id` )
				
			WHERE $where_clause AND `oe`.`org_id` = $org_id AND `oe`.`type` = 'STORE'
		";
		return $sql;
	}
	
	public function getOrgEntityByType( $org_id, $type )
	{
		$sql = "SELECT oe.id AS id, oe.org_id AS org_id, oe.type AS type, oe.is_active AS is_active
					FROM org_entities AS oe
					WHERE oe.org_id = $org_id AND oe.type = '$type'";
		
		$db = new Dbase( 'masters', True );
		return $db->query( $sql );
	}
	
	public function getThinClients( $org_id, $zone_ids )
	{
		$sql = "SELECT oer.org_id AS org_id, oer.child_entity_id AS thin_client_id 
					FROM org_entity_relations oer 
					WHERE child_entity_type = 'TILL' AND parent_entity_type = 'STR_SERVER' AND org_id = $org_id";

		$db = new Dbase( 'masters', True );
		return $db->query( $sql );
	}
	
	public function getChildrenByType( $org_id, $parent_ids, $type )
	{
		$sql_array = $this->getSqlArray( $parent_ids );
		
		$sql = "SELECT oer.child_entity_id
					FROM org_entity_relations AS oer
					WHERE oer.org_id = $org_id AND oer.parent_entity_id IN $sql_array AND oer.child_entity_type = '$type';";
		
		$db = new Dbase( 'masters', True );
		
		if ( ! $db )
			return;
		return $db->query( $sql );
	}
	
	public function getEntityDetails( $org_id, $entity_ids )
	{
		$sql_array = $this->getSqlArray( $entity_ids );
		
		$sql = "SELECT oe.id AS id, oe.org_id AS org_id, oe.type AS type, oe.is_active AS is_active
					FROM org_entities AS oe
					WHERE oe.org_id = $org_id AND oe.id IN $sql_array;";
		
		$db = new Dbase( 'masters', True );
		
		if ( ! $db )
			return;
		return $db->query( $sql );
	}
	
	public function getIdFromCode ( $org_id, $entity_code )
	{
		$sql = "SELECT `oe`.`id` 
					FROM `org_entities` AS `oe`
					WHERE `oe`.`org_id` = $org_id AND `oe`.`code` = $entity_code ;";
		
		$db = new Dbase( 'masters', True );
		return $db->query( $sql );
	}
	
	private function getSqlArray( $arr )
	{
		if ( count($arr) <= 0 )
			return "()";
		else if ( count($arr) == 1 )
			return "( $arr[0] )";
		
		$str = "( $arr[0]";
		
		for ( $i = 1; $i < count($arr) ; $i++ )
			if ( !empty( $arr[$i] ) )
				$str.= ", $arr[$i]";
		$str .= " )";
		
		return $str;
	}
	
	/* Returns the parent-child mapping for the array of child ids passed */
	public function getChildToParentMapping( $ids_array, $parent_type, $org_id )
	{
		$sql_array = $this->getSqlArray( $ids_array );
		
		$sql = "SELECT oer.parent_entity_id AS parent_id, oer.child_entity_id AS child_id 
					FROM org_entity_relations AS oer
					WHERE oer.child_entity_id IN ".$sql_array." AND oer.parent_entity_type = '$parent_type';";

		$db = new Dbase( 'masters', True );
		if ( ! $db )
			return;
		
		$temp = $db->query( $sql );
		$result = array();
		foreach( $temp as $row )
		{
			$index = $row['child_id'];
			$result[$index] = $row['parent_id'];
		}
		
		return $result;
	}
	
	public function check_thin_clients( $till_ids, $org_id )
	{
		$sql_array = $this->getSqlArray( $till_ids );
		
		$sql = "SELECT id, org_id, parent_entity_id, parent_entity_type, child_entity_id, child_entity_type
					FROM org_entity_relations
					WHERE child_entity_id IN ".$sql_array." AND org_id = $org_id AND parent_entity_type = 'STR_SERVER';";
		
		$db = new Dbase( 'masters', True );
		if ( ! $db )
			return;
		
		return $db->query( $sql );
	}
	
	public function getDistinctComponents( $entity_type, $report_type, $org_id )
	{
		if ( $entity_type == 'TILL' || $entity_type == 'TILLS' )
		{
			$table = "till_diagnostics_";
			if ( $report_type == 'upload' || $report_type == 'UPLOAD' )
			{
				$attr = "upload_type";
				$table .= "bulk_upload";
			}
			else // if ( $report_type == 'sync' || $report_type == 'SYNC' )
			{
				$attr = "sync_type";
				$table .= "sync_report";
			}
		}
		else if ( $entity_type == 'STR_SERVER' || $entity_type == 'STORE_SERVER' )
		{
			$table = "store_server_";
			if ( $report_type == 'upload' || $report_type == 'UPLOAD' )
			{
				$attr = "upload_type";
				$table .= "bulk_upload";
			}
			else // if ( $report_type == 'sync' || $report_type == 'SYNC' )
			{
				$attr = "log_sync_type";
				$table .= "sync_logs";
			}
		}
		
		$sql = "SELECT DISTINCT($attr)
					FROM ".$table.
					" WHERE org_id = $org_id";
		
		$db = new Dbase( 'performance', True );
		if ( ! $db)
			return;
		
		return $db->query( $sql );
	}
	
	public function entityInfo( $id_array, $org_id )
	{
		$sql_array = $this->getSqlArray( $id_array );
		
		$sql = "SELECT oe.id AS id, oe.code AS code, oe.name AS name
					FROM org_entities AS oe
					WHERE oe.id IN ".$sql_array." AND oe.org_id = $org_id";
		
		$db = new Dbase( 'masters', True );
		if ( !db )
			return;
		
		$temp = $db->query( $sql );
		$result = array();
		foreach ( $temp as $row )
		{
			$index = $row['id'];
			$result[$index] = $row;
		}
		
		return $result;
	}
	
	public function till_diagnostics( $tills, $org_id, $params )
	{
		$sql_array = $this->getSqlArray( $tills );
		
		// verify that the column for sorting sent as query param actually exists in the DB schema
		/* if sort_by param belongs to { full_sync_date, version_update_pending }
		 * 		then, $sortie = <the required field> 
		 * else $sortie = <default_sorting field>	*/
		if ( $params['sort_by'] && isset( $params['sort_by'] ) )
			$sortie = "td".$params['sort_by'];
		else 
			$sortie = "td.till_id";
		
		if ( $params['start_id'] && isset( $params['start_id'] ) )
			$start_id = $params['start_id'];
		else 
			$start_id = 0;
		
		if ( $params['limit'] && isset( $params['limit'] ) )
			$limit = $params['limit'];
		else 
			$limit = 200;

		$sql = "SELECT * FROM (SELECT td.id AS id, td.till_id AS till_id, td.org_id AS org_id, td.last_login AS last_login, 
										td.current_binary_version AS current_till_version, 
										td.available_binary_version AS available_till_version, @r := @r+1 AS sno 
								FROM till_diagnostics td 
										JOIN ( SELECT MAX(till_diagnostics.id) AS max_id 
												FROM till_diagnostics 
												GROUP BY till_diagnostics.till_id, till_diagnostics.org_id ) max_td
										ON td.id = max_td.max_id, 
									(SELECT @r := 0) AS ranker
								WHERE td.till_id IN $sql_array AND td.org_id = $org_id 
								ORDER BY $sortie ASC ) AS source
					WHERE source.sno > $start_id 
					LIMIT $limit;";
		
		$db = new Dbase("performance", True);
		if ( ! $db )
			return;
		
		return $db->query( $sql );
	}
	
	public function td_sync_status( $td_ids, $org_id )
	{
		$sql_array = $this->getSqlArray( $td_ids );
		
		$sql = "SELECT tdsr.id AS id, tdsr.till_diagnostics_fkey AS td_fkey, tdsr.org_id AS org_id, tdsr.is_full_sync AS is_full_sync, 
									tdsr.sync_type AS sync_type, tdsr.sync_status AS sync_status
        			FROM till_diagnostics_sync_report  tdsr
        					JOIN ( SELECT MAX(id) AS max_id 
        									FROM till_diagnostics_sync_report 
        									GROUP BY till_diagnostics_fkey, sync_type, is_full_sync ) max_tdsr
					        ON tdsr.id = max_tdsr.max_id
    				WHERE tdsr.till_diagnostics_fkey IN $sql_array AND tdsr.org_id = $org_id";
		
		$db = new Dbase("performance", True);
		if ( ! $db )
			return;
		
		return $db->query( $sql );
	}
	
	public function td_bulk_upload( $td_ids, $org_id )
	{
		$sql_array = $this->getSqlArray( $td_ids );
		
		$sql = "SELECT tdbu.id AS id, tdbu.till_diagnostics_fkey AS td_fkey, tdbu.upload_type AS upload_type, tdbu.status AS upload_status, 
									tdbu.org_id AS org_id
        			FROM till_diagnostics_bulk_upload tdbu
            				JOIN ( SELECT MAX(id) AS max_id 
            								FROM till_diagnostics_bulk_upload 
            								GROUP BY till_diagnostics_fkey, upload_type ) AS max_tdbu
            				ON tdbu.id = max_tdbu.max_id
    				WHERE tdbu.till_diagnostics_fkey IN $sql_array AND tdbu.org_id = $org_id";
		
		$db = new Dbase("performance", true);
		return $db->query( $sql );
	}
	
	public function store_server_health( $ss_array, $org_id, $params )
	{
		$sql_array = $this->getSqlArray( $ss_array );
		
		// verify that the column for sorting sent as query param actually exists in the DB schema
		/* if sort_by param belongs to { full_sync_date, version_update_pending }
		 * 		then, $sortie = <the required field>
		 * else $sortie = <default_sorting field>	*/
		if ( $params['sort_by'] && isset( $params['sort_by'] ) )
			$sortie = "ssh".$params['sort_by'];
		else
			$sortie = "ssh.store_id";
		
		if ( $params['start_id'] && isset( $params['start_id'] ) )
			$start_id = $params['start_id'];
		else
			$start_id = 0;
		
		if ( $params['limit'] && isset( $params['limit'] ) )
			$limit = $params['limit'];
		else
			$limit = 200;
		
		$sql = "SELECT * FROM (SELECT ssh.id AS id, ssh.store_id AS ss_id, ssh.org_id AS org_id, 
									ssh.last_login AS last_login, ssh.current_binary_version AS current_binary_version, 
									ssh.available_binary_version AS available_binary_version, ssh.up_time AS up_time, 
									ssh.last_transaction_time AS last_transaction, @r := @r+1 AS sno
			        			FROM store_server_health AS ssh
			            				JOIN ( SELECT MAX(id) AS max_id FROM store_server_health GROUP BY store_id, org_id ) max_ssh
			                            ON ssh.id = max_ssh.max_id,
			                        (SELECT @r := 0 ) as ranker
			    				WHERE ssh.store_id IN $sql_array AND ssh.org_id = $org_id 
								ORDER BY $sortie ASC ) AS source
                    WHERE source.sno > $start_id
					LIMIT $limit;";
		
		$db = new Dbase('performance', True);
		if ( ! $db )
			return;
		
		return $db->query( $sql );
	}
	
	public function ssh_sync_log( $ssh_ids_array, $org_id )
	{
		$sql_array = $this->getSqlArray( $ssh_ids_array );
		
		$sql = "SELECT sl.id AS id, sl.ss_health_fkey AS ssh_fkey, sl.log_sync_type AS sync_type, sl.sync_status, sl.is_full_sync, 
									sl.org_id AS org_id
        			FROM store_server_sync_logs AS sl
            				JOIN ( SELECT MAX(id) AS max_id 
            								FROM store_server_sync_logs 
            								GROUP BY ss_health_fkey, log_sync_type, is_full_sync ) max_sl
           					ON sl.id = max_sl.max_id
    				WHERE sl.ss_health_fkey IN $sql_array AND sl.org_id = $org_id";
		
		$db = new Dbase('performance', True);
		if ( ! $db )
			return;
		
		return $db->query( $sql );
	}
	
	public function ssh_bulk_upload( $ssh_ids_array, $org_id )
	{
		$sql_array = $this->getSqlArray( $ssh_ids_array );
		
		$sql = "SELECT MAX(ssbu.id) AS id, ssbu.ss_health_fkey AS ssh_fkey, ssbu.upload_type AS upload_type, ssbu.status AS status,
						ssbu.org_id AS org_id
			        FROM store_server_bulk_upload AS ssbu
			        WHERE ssbu.ss_health_fkey IN ".$sql_array." AND ssbu.org_id = $org_id
			        GROUP BY ssbu.ss_health_fkey, ssbu.upload_type;";
		
		$db = new Dbase('performance', True);
		if ( ! $db )
			return;
		
		return $db->query( $sql );
	}
	
	/**
	 * Returns the complete info of the stores
	 *
	 * @param unknown_type $org_id
	 * @param unknown_type $entity_id
	 */
	public function getStoreInfoDetailsByStoreId( $org_id, $store_id ){

		$where_clause = " `oe`.`id` = $store_id ";
		
		$sql = $this->getStoreInfoDetailsSqlQuery($where_clause, $org_id); 

		return $this->database->query( $sql );
	}
	
	public function getStoreInfoDetailsByStoreCode( $org_id, $store_code ){
	
		$where_clause = " `oe`.`code` = '$store_code' ";
		$sql = $this->getStoreInfoDetailsSqlQuery($where_clause, $org_id);
	
		return $this->database->query( $sql );
	}
	
	public function getStoreInfoDetailsByExternalId( $org_id, $external_id ){
	
		$where_clause = " `s`.`external_id` = '$external_id' ";
		$sql = $this->getStoreInfoDetailsSqlQuery($where_clause, $org_id);
	
		return $this->database->query( $sql );
	}

	/**
	 * Finsd out if any store is using the same mobile number
	 *
	 * @param $mobile
	 * @param $store_id
	 */
	public function isMobileTaken( $mobile, $store_id ){

		$sql = "
				SELECT `id`
				FROM `$this->table`
				WHERE `mobile` = '$mobile' AND `id` != '$store_id' 
		";

		return $this->database->query_scalar( $sql, false );
	}

	/**
	 * Finsd out if any store is using the same $email address
	 *
	 * @param $email
	 * @param $store_id
	 */
	public function isEmailTaken( $email, $store_id ){

		$sql = "
				SELECT `id`
				FROM `$this->table`
				WHERE `email` = '$email' AND `id` != '$store_id' 
		";

		return $this->database->query_scalar( $sql, false );
	}

	public function addBulkStoreUnits ( $store_unit ){

		$sql = "
				
				INSERT INTO store_units 
				(
				 `id`,	
				 `org_id`,
				 `store_id`,
				 `parent_id`, 
				 `client_type`, 
				 `is_active`, 
				 `last_updated_by`, 
				 `last_updated_on`
				  )
				VALUES  $store_unit  ";
		
		$this->database->insert( $sql );
		
	}
	
	/**
	 * Returns till level info
	 * @param unknown_type $org_id
	 * @param unknown_type $store_till_id
	 */
	public function getStoreUnitInfoDetails( $org_id, $store_till_id ){

		$sql = "
		
			SELECT 
					`oe`.`code` AS `code`, `oe`.`name` AS `name`, `oe`.`description` AS `description`, 
					`oe`.code as username, `oes`.`code` AS `parent_code`, `oes`.`name` AS `parent_store`, `su`.`client_version_num`, `su`.`compile_time`,
					`su`.`svn_revision`, `su`.`established_on`, `su`.`mac_addr`, 
					CASE WHEN `su`.`disable_mac_addr_check` = 0 THEN 'DISABLED' ELSE 'ENABLED' END AS `mac_adrress_check`
					
			FROM `org_entities` AS `oe`
			
			JOIN `store_units` AS `su` ON ( `su`.`id` = `oe`.`id` )
			JOIN `org_entities` AS `oes` ON ( `su`.`store_id` = `oes`.`id` )
			WHERE `oe`.`id` = '$store_till_id' AND `oe`.`org_id` = '$org_id' AND `oe`.`type` IN ( 'TILL', 'STR_SERVER' ) 
		"; 
//			JOIN `loggable_users` AS `lu` ON ( `lu`.`ref_id` = `oe`.`id` )

		return $this->database->query( $sql, true );

	}



	/**
	 *
	 * @param unknown_type $store_id
	 * @param unknown_type $external_id
	 */
	public function isExternalIdUsed( $org_id, $store_id, $external_id ){

		$sql = "
			SELECT `id`
			FROM `$this->table`
			WHERE `id` != '$store_id' AND `org_id` = '$org_id' AND `external_id` = '$external_id'  
		";

		return $this->database->query_scalar( $sql, true );
	}

	/**
	 * Updates The Till Level Deployment Files Set At Organization Level
	 */
	public function updateDeploymentFiles( $org_id, $till_id, $user_id ){

		$sql = "
				INSERT INTO `client_file_mappings` ( `org_id`, `mapping_type`, `file_type`, `file_id`, `store_id`, `created_time`, `created_by` )
				SELECT `org_id`, `mapping_type`, `file_type`, `file_id`, '$till_id', NOW(), '$user_id'
				FROM `client_file_mappings`
				WHERE `org_id` = '$org_id' AND `store_id` = -1
		";

		return $this->database->update( $sql );
	}

	public function getAllStores($org_id)
	{
		$valid_stores=array();
		$sql= "SELECT username,store_id from stores where org_id=$org_id";
		$database = new Dbase( 'users' );
		$res= $database->query($sql);
		foreach($res as $k=>$v)
		$valid_stores[$v['username']]=$v['store_id'];
		return $valid_stores;
	}

	/**
	 * Add Terminal to the stores
	 *
	 * @param unknown_type $till_id
	 * @param unknown_type $org_id
	 * @deprecated : table need to be depreated and not used in api
	 */
	public function addShopbookStoreForUser( $till_id, $org_id ){

		$sql = "
		
			INSERT INTO `user_management`.`stores` ( 
				`store_id`, 
				`tag`, 
				`username`, 
				`lastname`, 
				`passwordhash`, 
				`secretq`, 
				`secreta`, 
				`email`, 
				`mobile`,
				`last_login`,
				`org_id`,
				`is_inactive`,
				`password_validity`,
				`replace_inactive_by`
			) 
			SELECT 
				`oe`.`id` AS `store_id`, 
				'org' AS `tag`, 
				`lu`.`username` AS `username`, 
				'' AS `lastname`,
				`lu`.`password` AS `passwordhash`,
				`lu`.`secret_question` AS `secretq`,
				`lu`.`secret_answer` AS `secreta`,
				`s`.`email` AS `email`,
				`s`.`mobile` AS `mobile`,
				`lu`.`last_login` AS `last_login`,
				`oe`.`org_id` AS `org_id`,
				( case when ( `oe`.`is_active` = 1 ) THEN 0 ELSE 1 END ) AS `is_inactive`,
				`lu`.`password_validity` AS `password_validity`,
				0 AS `replace_inactive_by` 
				
				FROM `masters`.`org_entities` AS `oe` 
				JOIN `masters`.`store_units` AS `su` ON ( ( `oe`.`id` = `su`.`id` ) AND ( `oe`.`org_id` = `su`.`org_id` ) ) 
				JOIN `masters`.`loggable_users` AS `lu` ON ( ( `lu`.`org_id` = `su`.`org_id` ) AND ( `su`.`id` = `lu`.`ref_id` ) )
				JOIN `masters`.`stores` AS `s` ON (  (`su`.`org_id` = `s`.`org_id`) and (`su`.`store_id` = `s`.`id`) )
				WHERE `oe`.`org_id` = '$org_id' AND `oe`.`type` = 'TILL' AND `oe`.`id` = '$till_id' AND `lu`.`type` = 'TILL'
			ON DUPLICATE KEY UPDATE
				`store_id` = VALUES( `store_id` ), 
				`tag` = VALUES( `tag` ), 
				`username` = VALUES( `username` ),  
				`lastname` = VALUES( `lastname` ), 
				`passwordhash` = VALUES( `passwordhash` ), 
				`secretq` = VALUES( `secretq` ), 
				`secreta` = VALUES( `secreta` ), 
				`email` = VALUES( `email` ), 
				`mobile` = VALUES( `mobile` ),
				`last_login` = VALUES( `last_login` ),
				`org_id` = VALUES( `org_id` ),
				`is_inactive` = VALUES( `is_inactive` ),
				`password_validity` = VALUES( `password_validity` ),
				`replace_inactive_by` = VALUES( `replace_inactive_by` )
		"; 

		$status = $this->database->update( $sql );

		if( !$status ){
				
			$msg = 'Till addition with With Id :'.$till_id.' Failed For org id :'.$org_id;
			Util::sendEmail( 'errorsniffer@capillary.co.in', $msg, $msg, $org_id );
		}
	}

	/**
	 * Add Terminal to the stores
	 *
	 * @param unknown_type $till_id
	 * @param unknown_type $org_id
	 */
	public function addShopbookStoreForTillUser( $store_id , $tills , $org_id , $contact_filter = array() ){

		if( count( $contact_filter ) == 0 ){
			$filter = " `s`.`firstname` = `oe`.`name` , `s`.`lastname` = '' ";
		}else{
			$filter = " `s`.`mobile` = '".$contact_filter['mobile']."' , `s`.`email` = '".$contact_filter['email']."' ";
		}
		
		$sql = "
		
			UPDATE `user_management`.`stores` AS `s`, `masters`.`org_entities` AS `oe`
			SET $filter
			WHERE `oe`.`org_id` = '$org_id' AND `s`.`store_id` IN ( $tills ) AND `oe`.`type` = 'STORE' AND `oe`.`id`  In ( $store_id )
		"; 

		$status = $this->database->update( $sql );

		if( !$status ){
				
			$msg = 'Store Updation With Id :'.$store_id.' and Tills: '.$tills.' Failed For org id :'.$org_id;
			Util::sendEmail( 'errorsniffer@capillary.co.in', $msg, $msg, $org_id );
		}

	}

	/**
	 * updating the store details in the stores table based on the temporary id
	 * @param unknown_type $store_details
	 * @param unknown_type $external_ids
	 */
	public function addStoreDetails( $store_details, $external_ids ){

		$temp_table = 'stores'.time();
		$sql = "
				CREATE TABLE `tempdb`.`$temp_table` (
			  
			  `id` int(11) NOT NULL,
			  `org_id` int(11) NOT NULL,
			  `city_id` int(11) NOT NULL,
			  `area_id` int(11) DEFAULT NULL,
			  `mobile` varchar(15) NOT NULL,
			  `email` varchar(50) NOT NULL,
			  `is_active` tinyint(1) NOT NULL,
			  `lat` varchar(50) NOT NULL,
			  `long` varchar(50) NOT NULL,
			  `external_id` varchar(50) NOT NULL,
			  `last_updated_by` int(11) NOT NULL,
			  `last_updated_on` datetime NOT NULL
				
			  ) ENGINE = MYISAM ;";


		$this->database->update( $sql );

		$sql =	"
						INSERT INTO `tempdb`.`$temp_table`
						VALUES  $store_details ";

		$this->database->insert( $sql );
		
		$sql = " UPDATE
					  `masters`.`stores` AS `s`, 
					  `tempdb`.`$temp_table` AS `sd`
					  
					SET `s`.`org_id` = `s`.`org_id` , `s`.`city_id` = `sd`.`city_id`, `s`.`area_id`= `sd`.`area_id`, `s`.`mobile` = `sd`.`mobile` 
						, `s`.`email` = `sd`.`email` , `s`.`is_active` = `sd`.`is_active`, `s`.`lat` = `sd`.`lat`, `s`.`long` = `sd`.`long`,
				`s`.`last_updated_by` = `sd`.`last_updated_by` , `s`.`last_updated_on` =  `sd`.`last_updated_on`

				WHERE `sd`.`external_id` = `s`.`external_id`";
			
		$this->database->update( $sql );

		$sql = " TRUNCATE TABLE `tempdb`.`$temp_table` ";
		$this->database->update( $sql );
		
		$sql = "DROP TABLE `tempdb`.`$temp_table`";
		
		return $this->database->update( $sql );
		
	}


	public function addParentForStore( $entities_relations ){
			
		
	}


	/**
	 * fetches external ids
	 * @param unknown_type $external_id
	 */
	public function getIdbyExternalIds( $external_id, $org_id ){
		
		$sql = "
				SELECT `id` FROM 
				`masters`.`stores`
				WHERE `external_id` = '$external_id' AND `org_id` = '$org_id' ";

		return $this->database->query_scalar( $sql );
		
	}

	/**
	 * add org entities in bulk
	 * @param unknown_type $org_entities
	 */
	public function addOrgEntities( $org_entities ){

		$sql =  "

			INSERT INTO org_entities 
			( 
				org_id,
				type,
				code,
				name
			) 
			VALUES 
			$org_entities " ;

		return $this->database->insert( $sql );

	}

	/**
	 * 
	 * 
	 * @param unknown_type $store_id
	 */
	public function getCodeById( $store_id, $org_id ){
		
		$sql = "
				SELECT `code`
				FROM `org_entities`
				WHERE id = '$store_id'
				AND `org_id` = '$org_id' 
				";

		return $this->database->query( $sql );
	
	}
	
	/**
	 * insert in template are 
	 * @param $template_data
	 */
	public function updateStoreTemplate( $template_data ){

		$sql =  "

			INSERT INTO store_templates 
			( 
				store_id,
				org_id,
				last_updated_by,
				last_updated_on 

			) 

			VALUES 
			$template_data
			";

			return $this->database->insert( $sql );

	}
	
	
	/**
	 * 
	 * @param unknown_type $batch_data data for inserting store with org_entities 
	 */
	public function UpdateStoreWithEntityIds( $batch_data ){

		$sql =  "

			INSERT INTO stores 
			( 
				id,
				org_id,
				is_active,
				external_id,
				last_updated_by,
				last_updated_on 

			) 

			VALUES 
			$batch_data
			";

			return $this->database->insert( $sql );
				
	}
	/**
	 * Add Terminal to the stores
	 *
	 * @param unknown_type $till_id
	 * @param unknown_type $org_id
	 */
	public function updateStatus( $till_id , $org_id , $is_inactive ){

		if( $is_inactive )
		$is_inactive = 0;
		else
		$is_inactive = 1;
			
		$sql = "
		
			UPDATE `user_management`.`stores` AS `s`
			SET `s`.`is_inactive` = $is_inactive
			WHERE `s`.`org_id` = '$org_id' AND `s`.`store_id` = $till_id
		"; 
		$status = $this->database->update( $sql );

		return $status;
	}
	
	public function getAllStoresWithExternalId($org_id){
		$valid_stores=array();
		$sql= "SELECT external_id,id from stores where org_id=$org_id";
		$database = new Dbase( 'masters' );
		$res= $database->query($sql);
		foreach($res as $k=>$v)
			$valid_stores[$v['external_id']]=$v['id'];
		return $valid_stores;
	}
	
	function updateChildTillsWithAdminType( $store_id , $admin_type , $child_tills )
	{
		$sql = " UPDATE `masters`.`org_entities`
				 SET `admin_type` = '$admin_type'
				 WHERE `id` IN 
				 ( $child_tills )
				";
		return $this->database->update( $sql );
		
				
	}
	
	/**
	 * 
	 * Add Customer Feedback for store.
	 * @param unknown_type $query_data
	 */
	public function storeCustomerFeedback( $query_data ){
		
		$sql = "INSERT INTO store_management.`check_in_feedback` (
																 `org_id` ,
																 `user_id`,
																 `store_id`,
																 `last_updated_on`
																)
				VALUES $query_data";

		return $this->database->insert( $sql );
	}
	
	public function getStoreFeedbackCount( $store_id , $params , $org_id ){
		
		$sql = "SELECT count(`id`) AS 'no_of_feedback' FROM store_management.`check_in_feedback` 
				WHERE `store_id` = $store_id 
				AND `org_id` = $org_id
				AND $params;
			  ";
		
		return $this->database->query_firstrow( $sql );
	}
	
	/**
	 * 
	 * It will update the child tills details of store
	 * @param array $store_details
	 * @param array $child_tills
	 */
	public function updateChildTillsWithStoreDetails( $store_details , $child_tills ){
		
		$admin_type = $store_details['admin_type'];
		$time_zone_id = $store_details['time_zone_id'];
		$currency_id = $store_details['currency_id'];
		$language_id = $store_details['language_id'];
		
		$sql = " UPDATE `masters`.`org_entities`
				 SET `admin_type` = '$admin_type',
				 `time_zone_id` = '$time_zone_id',
				 `currency_id` = '$currency_id',
				 `language_id` = '$language_id'
				 WHERE `id` IN 
				 ( $child_tills )
				";
		return $this->database->update( $sql );
	}
	
	/**
	 * 
	 * It will update the entity timezone based on entity type and org_id
	 * @param array $store_details
	 * @param array $child_tills
	 */
	public function updateTimeZonesByEntityType( $entity_type , $org_id , $time_zone_id ){
		
		$sql = " UPDATE `masters`.`org_entities`
				 	SET `time_zone_id` = '$time_zone_id' 
				 WHERE `org_id` = '$org_id' AND `type` = '$entity_type'	";
		
		return $this->database->update( $sql );
	}
	
	/**
	 * inactive all the childrens of the entity
	 * @param string $child_type
	 * @param array $child_ids
	 * @param int $org_id
	 * @param boolean $is_active
	 */
	public function updateChildStatus( $child_type , $child_ids , $org_id , $is_active = false ){

		$is_active_status = 1;
		
		if( !$is_active )
			$is_active_status = 0;
			
		$child_ids = Util::joinForSql( $child_ids );
		
		$sql = " UPDATE `masters`.`org_entities` AS `e`
					SET `e`.`is_active` = '$is_active_status'
				 WHERE `e`.`org_id` = '$org_id' AND `e`.`type` = '$child_type' AND `e`.`id` IN ( $child_ids ) ";
		 
		return $this->database->update( $sql );
	}
	
/**
	 * Store Template Values
	 * @param unknown_type $store_till_id
	 * @param unknown_type $org_id
	 */
	public function getStoreTemplateJoinDetails( $store_till_id , $org_id ){
		
		$sql = "SELECT  
					   oe2.name AS store_name,    
					   oe2.name AS base_store_name,   
				 	   s.mobile AS store_number,   
				       s.mobile AS base_store_number,   
				       s.land_line AS store_land_line,   
				       s.email AS store_email,   
				       s.external_id as store_external_id,   
				       s.external_id_1 as store_external_id_1,   
				       s.external_id_2 as store_external_id_2,  
					   st.s_name AS sms_store_name,
				       st.s_email AS sms_email, 
					   st.s_mobile AS sms_mobile, 
					   st.s_land_line AS sms_land_line, 
					   st.s_add AS sms_address, 
					   st.s_extra AS sms_extra, 
					   st.e_name AS email_store_name, 
					   st.e_email AS email_email, 
					   st.e_mobile AS email_mobile, 
					   st.e_land_line AS email_land_line, 
					   st.e_add AS email_address, 
					   st.e_extra AS email_extra 
				FROM masters.org_entities oe  
				JOIN masters.store_units su ON su.id = oe.id 
				JOIN masters.stores s ON su.store_id = s.id   
				JOIN masters.org_entities oe2 ON ( oe2.id = s.id )  
				JOIN masters.store_templates st ON st.store_id = su.store_id 
				WHERE  su.id = $store_till_id AND su.org_id = $org_id
				GROUP BY oe.id" ;
  		
  		return $this->database->query_firstrow( $sql );
  		
	}

	/**
	 * checks if code exists or not
	 *
	 * @param unknown_type $org_id
	 * @param unknown_type $code
	 * @param unknown_type $entity_id
	 */
	public function isStoreTillCodeExists( $code, $entity_id = false ){
	
		$entity_id = ( int ) $entity_id;
	
		$sql = "
		SELECT `code`
		FROM `masters`.`org_entities`
		WHERE `type` = 'TILL' AND `id` != '$entity_id' AND `code` = '$code'
		";
		
		return $this->database->query_scalar( $sql, true );
	}

    /**
     * inserts client log metadata in db
     * @param $params
     * @return mixed
     */
    public function addStorePerf($params)
    {
        $sql = " INSERT INTO user_management.clientlog_meta (org_id, entity_id, logged_time, uploaded_time, file_handle,
                file_size, file_signature, client_ip, file_name, file_type) VALUES (" . $params['org_id'] . "," . $params['user_id'] .
            ", " . $params['logged_time'] . "," . $params['uploaded_time'] . ", '" . $params['file_upload_handle'] . "', " .
            $params['logfile_size'] . ", '" . $params['logfile_sha1'] . "' ," . " INET_ATON('" . $params['client_ip'] .
            "' ), '".$params['logfile_name']."', '".$params['file_type']. "' )";
        $this->logger->debug("Adding store perormance log metadata to db");
        $this->logger->debug("Firing query : " . $sql);
        return $this->database->insert($sql);
    }
    
    public function getClientLogFileMetadataForTill( $org_id, $till_id, $logged_time = '' , $uploaded_time = '' ){
    	
    	$sql = " SELECT * FROM user_management.clientlog_meta 
    					WHERE org_id = $org_id
    					AND till_id = $till_id ";
    	
    	if( $logged_time ){
    		
    		$sql .= "AND logged_time = '$logged_time' ";
    	}
    	
    	if( $uploaded_time ){
    		
    		$sql .= "AND uploaded_time = '$uploaded_time' ";
    	}
    	return $this->database->query($sql);
    }
    
    public function getStoreAttributeLastModifiedDate($org_id, $store_id)
    {
    	$sql = "SELECT MAX(`last_updated`) FROM `user_management`.`stores_info`
    				WHERE org_id = '$org_id' AND store_id = '$store_id'";
    	return $this->database->query_scalar($sql);
    }



    /**
     * @param $store_id
     * @param $org_id
     * @param $up_time
     * @param $request_processed
     * @param $os
     * @param $os_platform
     * @param $processor
     * @param $system_ram
     * @param $db_size
     * @param $lan_speed
     * @param $last_transaction_time
     * @param $avg_mem_usage
     * @param $peak_mem_usage
     * @param $avg_cpu_usage
     * @param $peak_cpu_usage
     * @param $last_updated_at
     * @return mixed
     */
    public function insertStoreServPerf($store_id, $org_id, $up_time, $request_processed, $os, $os_platform, $processor,
                                        $system_ram, $db_size, $lan_speed, $last_transaction_time, $avg_mem_usage,
                                        $peak_mem_usage, $avg_cpu_usage, $peak_cpu_usage, $ss_last_txn_to_svr,
                                        $ss_last_regn_to_svr, $ss_report_generation_time, $ss_last_login,
                                        $ss_last_fullsync, $ss_curr_version, $ss_available_version){
        $sql = "INSERT INTO performance_logs.store_server_health (store_id, org_id, up_time, requests_processed, os,
                os_platform, processor, system_ram, db_size, lan_speed, last_transaction_time, avg_mem_usage, peak_mem_usage,
                avg_cpu_usage, peak_cpu_usage,  last_txn_to_svr, last_regn_to_svr, report_generation_time,
                last_login, last_fullsync, current_binary_version, available_binary_version, last_updated_at) VALUES
                ($store_id, $org_id, $up_time, $request_processed,
                '$os', '$os_platform', $processor, $system_ram, $db_size, $lan_speed, '$last_transaction_time',
                $avg_mem_usage, $peak_mem_usage, $avg_cpu_usage, $peak_cpu_usage, '$ss_last_txn_to_svr',
                '$ss_last_regn_to_svr', '$ss_report_generation_time', '$ss_last_login', '$ss_last_fullsync',
                '$ss_curr_version', '$ss_available_version', NOW() )";
        return $this->pl_db->insert($sql);
    }

    /**
     * @param $sync_records
     * @return mixed
     */
    public function insertStoreServerSyncLog($sync_records){
        if(empty($sync_records))
            return true;
        $sql = "INSERT INTO performance_logs.store_server_sync_logs (ss_health_fkey, log_sync_type, sync_status,
                last_full_sync_time, last_delta_sync_time,
                read_time, file_size, unzipping_time, indexing_time, request_id, is_full_sync, avg_mem_usage, peak_mem_usage,
                avg_cpu_usage, peak_cpu_usage, last_updated_at, org_id) VALUES ". $sync_records;
        return $this->pl_db->insert($sql);
    }

    /**
     * @param $till_reports
     * @return mixed
     */
    public function insertStoreServerTillRep($till_reports){
        if(empty($till_reports))
            return true;
        $sql = "INSERT INTO performance_logs.store_server_till_reports (username, ss_health_fkey, last_request,
                requests_sent, responses_received, avg_time_taken_per_call, last_updated_at, org_id) VALUES ".$till_reports;
        return $this->pl_db->insert($sql);
    }

    public function insertStoreSvrSQLSvrHlth($sql_svr_health){
        if(empty($sql_svr_health))
            return true;
        $sql = "INSERT INTO performance_logs.store_server_sql_svr_health (ss_health_fkey, is_alive,
                last_query_exec_time, average_cpu_time, active_connection_count, intouch_db_size, total_db_size,
                avg_disk_io, os, os_platform, processor, system_ram, last_updated_at, org_id) VALUES ".$sql_svr_health;
        return $this->pl_db->insert($sql);
    }

    public function insertStoreSvrWCFStats($wcf_report){
        if(empty($wcf_report))
            return true;
        $sql = "INSERT INTO performance_logs.store_server_wcf_report (ss_health_fkey, requests_sent,
                last_request, requests_received, version, inserted_at, org_id) VALUES ".$wcf_report;
        return $this->pl_db->insert($sql);
    }

    public function addStoreSvrBulkUpload($bulk_upload){
        if(empty($bulk_upload))
            return true;
        $sql = "INSERT INTO performance_logs.store_server_bulk_upload (ss_health_fkey, status, sync_time,
        upload_type, org_id) VALUES ".$bulk_upload;
        return $this->pl_db->insert($sql);
    }

    public function insertTillErrorRep($till_reports){
        if(empty($till_reports))
            return true;
        $sql = "INSERT INTO performance_logs.till_error_report (till_id, org_id, code,
                count, description, last_occurrence, inserted_at ) VALUES ".$till_reports;
        return $this->pl_db->insert($sql);
    }

    public function insertTillDiagnostics($till_reports){
        if(empty($till_reports))
            return true;
        $sql = "INSERT INTO performance_logs.till_diagnostics (till_id, org_id, from_, to_, last_login, last_fullsync,
                                       integration_mode, current_binary_version, available_binary_version,
                                       update_skip_count, last_binary_update, avg_mem_usage, peak_mem_usage,
                                       avg_cpu_usage, peak_cpu_usage) VALUES " . $till_reports;
        return $this->pl_db->insert($sql);
    }

    public function insertTillDiagnosticsBulkUpload($bulk_upload){
        if(empty($bulk_upload))
            return true;
        $sql = "INSERT INTO performance_logs.till_diagnostics_bulk_upload (till_diagnostics_fkey, status, sync_time,
        upload_type, org_id) VALUES ".$bulk_upload;
        return $this->pl_db->insert($sql);
    }

    public function insertTillDiagnosticSyncLogs($value_str){
        if(empty($value_str))
            return true;
        $sql = "INSERT INTO performance_logs.till_diagnostics_sync_report (till_diagnostics_fkey, sync_type,
                sync_status, last_sync_time, last_delta_sync_time, read_time, file_size, unzipping_time, indexing_time,
                request_id,
                is_full_sync, avg_mem_usage, peak_mem_usage, avg_cpu_usage, peak_cpu_usage, org_id) VALUES " . $value_str;
        return $this->pl_db->insert($sql);
    }

    public function insertTillDiagnosticsSystemDetails($system_details){
        if(empty($system_details))
            return true;
        $sql = "INSERT INTO performance_logs.till_diagnostics_system_info (till_diagnostics_fkey, os, os_platform,
                processor, processor_family, system_ram, db_size, sqlite_version, framework_version, heartbeat_success,
                heartbeat_failure, till_time, server_time, proxy_enabled, timezone, org_id) VALUES ".$system_details;
        return $this->pl_db->insert($sql);
    }

}

?>
