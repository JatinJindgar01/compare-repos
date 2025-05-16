<?php

include_once "controller/ApiParent.php";

class OrgAdminController extends ApiParentController {
	/**
	 * This is the DB Connection to the users db - ideally, there should be no DB object outside of the LoyaltyController
	 * @var unknown_type
	 */

	
	public function __construct($currentuser = false) {

		parent::__construct('users');

		if($currentuser != false){
			$this->currentuser = $currentuser;
			$this->currentorg = $this->currentuser->getProxyOrg();
		}
	
	}
	
	public function getAllCountries($outtype = 'query'){
		$sql = "SELECT * FROM `countries`";
		
		if($outtype == 'query_table')
			return $this->db->query_table($sql);
	
		return $this->db->query($sql);
	}
	
	public function getCountryDetails($country_id){
		$sql = "SELECT * FROM `countries` WHERE `id` = '$country_id'";
		return $this->db->query_firstrow($sql);
	}
	
	public function addOrUpdateCountryDetails($name, $short_name, 
		$mobile_country_code, $mobile_regex, $mobile_length_csv, $country_id = '')
	{
		
		if($country_id != '' && $country_id >= 1){
			$sql = "
				UPDATE `countries`
				SET
				`short_name` = '$short_name',
				`mobile_country_code` = '$mobile_country_code',
				`mobile_regex` = '$mobile_regex',
				`mobile_length_csv` = '$mobile_length_csv',
				`last_updated` = NOW()
				WHERE `id` = '$country_id'
			";
			
			return $this->db->update($sql);
		}
		
		
		$sql = "
			INSERT INTO `countries` (`name`, `short_name`, `mobile_country_code`, `mobile_regex`, `mobile_length_csv`, `last_updated`)
			VALUES ('$name', '$short_name', '$mobile_country_code', '$mobile_regex', '$mobile_length_csv', NOW())
		";
				
		return $this->db->insert($sql);
	}
	
	public function getpageGroupsAsOptions( $module_id = false ){
		
		if( $module_id )
			$filter = " WHERE module_id = '$module_id' ";
			
		$sql = "SELECT `name`, `id`
				FROM `store_management`.`resources`
				$filter
				";
		
		return $this->db->query_hash( $sql, 'name', 'id' );
	}
	
	public function getModulebyId( $module_id ){
		
		$sql = "SELECT * 
				FROM `store_management`.modules
				WHERE id = '$module_id'";
		
		return $this->db->query_firstrow( $sql );
	}
	
	public function createDefaultConcept( $org_id ){
		
		$sql = "
		
			INSERT INTO `masters`.`org_entities` ( `org_id`, `type`, `is_active`, `code`, 
												`name`, `description`, `last_updated_by`, `last_updated_on` )
										VALUES ( '$org_id', 'CONCEPT', '1', 'root', 
												'ROOT', 'This Is The Auto Generated Root Concept', '$this->user_id', NOW() )
		";
		
		$concept_id = $this->db->insert( $sql );
		
		if( $concept_id )
			$this->addConceptRelationship( $org_id, $concept_id, 'CONCEPT' );
	}
	
	private function addConceptRelationship( $org_id, $id, $type ){
		
		$sql = "
		
			INSERT INTO masters.org_entity_relations ( org_id, parent_entity_id, parent_entity_type, child_entity_id, child_entity_type )
			VALUES
				( $org_id, '-1', '$type', '$id', '$type' )";
				
			return $this->db->insert( $sql );	
	}
	
	public function createDefaultZone( $org_id ){
		
		$sql = "
		
			INSERT INTO `masters`.`org_entities` ( `org_id`, `type`, `is_active`, `code`, 
												`name`, `description`, `last_updated_by`, `last_updated_on` )
										VALUES ( '$org_id', 'ZONE', '1', 'root', 
												'ROOT', 'This Is The Auto Generated Root Concept', '$this->user_id', NOW() )
		";
		
		$zone_id = $this->db->insert( $sql );

		$sql = "
			INSERT INTO zones ( id, level, last_updated_by, last_updated_on, reporting_email, reporting_mobile, org_id )
			VALUES ( '$zone_id', 'CITY', $this->user_id, NPW(), 'prakhar@capillary.co.in', '', $this->org_id )		
		";
		$this->db->insert( $sql );
		
		if( $zone_id )
			$this->addConceptRelationship( $org_id, $zone_id, 'ZONE' );
	}

	private function createSuperAdminRole( $org_id ){
		
		$sql = "
			INSERT INTO `masters`.`org_roles`
			(
				org_id, role_name, role_type, parent_role_id, created_by, created_on, last_updated_by, last_updated_on
			)
			VALUES
			(
				'$org_id', 'SUPERUSER', 'ORG', '-1', '$this->user_id', NOW(), '$this->user_id', NOW()
			)
		";
		
		return $this->db->insert( $sql );
	}

	private function createCapillaryPOC( $org_id ){
		
		$sql = "
			INSERT INTO `masters`.`org_roles`
			(
				org_id, role_name, role_type, parent_role_id, created_by, created_on, last_updated_by, last_updated_on
			)
			VALUES
			(
				'$org_id', 'CAP_POC', 'ORG', '-1', '$this->user_id', NOW(), '$this->user_id', NOW()
			)
		";
		
		return $this->db->insert( $sql );
	}
	
	private function createOrgPOC( $org_id ){
		
		$sql = "
			INSERT INTO `masters`.`org_roles`
			(
				org_id, role_name, role_type, parent_role_id, created_by, created_on, last_updated_by, last_updated_on
			)
			VALUES
			(
				'$org_id', 'ORG_POC', 'ORG', '-1', '$this->user_id', NOW(), '$this->user_id', NOW()
			)
		";
		
		return $this->db->insert( $sql );
	}
	
	/**
	 * 
	 * @param unknown_type $org_id
	 */
	public function createDefaultRoles( $org_id ){
		
		$this->createCapillaryPOC( $org_id );
		$this->createOrgPOC( $org_id );
	}
	
	/**
	 * 
	 * @param unknown_type $org_id
	 * @param unknown_type $org_name
	 */
	public function createDefaultAdminUser( $org_id, $org_name ){
		
		$role_id = $this->createSuperAdminRole( $org_id );
		if( $role_id < 1 ) return false;
		
		$name = 'SUPER USER';
		$mobile = $this->currentuser->mobile;
		$email = $this->currentuser->email;
		
		$sql = " INSERT INTO `masters`.`admin_users` ( `role_id`, `org_id`, `reports_to`, `title`,`first_name`, 
													`mobile`, `email`, `is_active`, `created_on`, 
													`last_updated_by`, `last_updated_on`)
													
												VALUES ( '$role_id', '$org_id', '-1', 'Mr.','$name',
														'$mobile', '$email', '1', NOW(), '$this->user_id',
														NOW()
												)";
		
		$user_id = $this->db->insert( $sql );
		
		if( $user_id < 1 ) return false;
		
		$sql = "
		
				INSERT INTO `masters`.`admin_user_roles` ( org_id, admin_user_id, ref_id, type, is_active, 
															last_updated_by, last_updated_on 
														)
													VALUES ( '$org_id', '$user_id', $org_id, 'ORG', '1',
															'$this->user_id', NOW()
													)
		";
		
		return $this->db->insert( $sql );
	}	
}

?>
