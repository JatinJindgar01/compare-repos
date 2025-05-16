<?php 
include_once 'apiController/ApiBaseController.php';
/**
 * It inherits entity controller
 * all the zone operation is done here .
 * At the back it contacts the entity controller to fetch the required values
 *
 * @author prakhar
 *
 */
class ApiZoneController extends ApiEntityController{

	public function  __construct(){

		parent::__construct( 'ZONE' );
	}

	/**
	 * add the new entity
	 *
	 * @param array $zone_details
	 *
	 * CONTRACT
	 * array(
	 * 'code' => value,
	 * 'name' => value,
	 * 'description' => value,
	 * 'time_zone_id' => value,
	 * 'currency_id' => value,
	 * 'language_id' => value,
	 * )
	 */
	public function add( $zone_details , $parent_id = false , $parent_type = false , $validate_code = true ){

		return parent::add( $zone_details , $parent_id , $parent_type , $validate_code );
	}

	/**
	 * update entity details
	 *
	 * @param int $zone_id
	 * @param array $zone_details
	 *
	 * CONTRACT
	 * array(
	 * 'code' => value,
	 * 'name' => value,
	 * 'description' => value,
	 * 'time_zone_id' => value,
	 * 'currency_id' => value,
	 * 'language_id' => value,
	 * )
	 */
	public function update( $zone_id, $zone_details ){

		return parent::update( $zone_id, $zone_details );
	}

	/**
	 * Returns the zone as option for using in select box
	 *
	 * @param : don't inlude the current zone
	 * @return array( name => zone_id )
	 */
	public function getZonesAsOptions( $zone_id = false ){

		$zones = $this->getEntityAsOptions();
			
		if( $zone_id ){

			$zones = array_flip( $zones );
			unset ( $zones[$zone_id] );

			if( $zone_id ){

				$children = $this->getChildrenEntityByType( $zone_id, 'ZONE' );
				foreach( $children as $c )
					unset( $zones[$c] );
			}
			$zones = array_flip( $zones );
		}
			
		return $zones;
	}

	/**
	 * Returns stores ids
	 *
	 * @return array( store_id )
	 */
	public function getStoresByZoneId( $zone_id ){

		return $this->getChildrenEntityByType( $zone_id, 'STORE');
	}

	/**
	 * Returns store till ids
	 *
	 * @return array( store_till_id )
	 */
	public function getStoreTerminalsByZoneId( $zone_id ){

		return $this->getChildrenEntityByType( $zone_id, 'TILL');
	}

	/**
	 * Returns store server ids
	 *
	 * @return array( store_server_id )
	 */
	public function getStoreServerByZoneId( $zone_id ){

		return $this->getChildrenEntityByType( $zone_id, 'STR_SERVER');
	}

	/**
	 * Return the orgEntity Object
	 * @param unknown_type $zone_id
	 */
	public function getDetails( $zone_id ){

		return $this->getDetailsById( $zone_id );
	}

	/**
	 * Return the orgEntity Object
	 * @param unknown_type $zone_id
	 */
	public function getInfoDetails( $zone_id ){

		return parent::getInfoDetails( $zone_id );
	}

	/**
	 * Assigns the parent zone for the widget
	 * @param unknown_type $parent_zone_id
	 * @param unknown_type $parent_zone_id
	 */
	public function addParentZone( $parent_zone_id, $child_zone_id ){

		try{

			$this->addParentEntity( $parent_zone_id, 'ZONE', $child_zone_id, 'ZONE' );
				
			return 'SUCCESS';
		}catch( Exception $e ){
				
			return $e->getMessage();
		}
	}

	/**
	 * Returns the parent zone
	 *
	 * @param unknown_type $child_entity_type
	 * @param unknown_type $entity_id
	 */
	public function getParentZone( $entity_id ){

		return $this->getParentEntityByType( $entity_id, 'ZONE' );
	}

	/**
	 * retuns the managers assigned to the zone
	 *
	 * @param $zone_id
	 * @return array(
	 * 					array( 'admin_users_id' => value ),
	 * 					array( 'admin_users_id' => value )
	 * 			)
	 */
	public function getUsersById( $zone_id ){

		$AdminUser = new AdminUserController();
		return $AdminUser->getUserIdsByEntityId( $zone_id );
	}

	/**
	 * retuns the managers assigned to the zone
	 *
	 * @return array(
	 * 					array( 'admin_users_id' => value ),
	 * 					array( 'admin_users_id' => value )
	 * 			)
	 */
	public function getUsersByType(  ){

		$AdminUser = new AdminUserController();
		return $AdminUser->getUserIdsByEntityType( $this->entity_type );
	}

	/**
	 * Returns SUCCESS/FAILURE
	 *
	 * add the manager to zone
	 *
	 * @param $zone_id
	 * @param $manager_id
	 */
	public function assignManager( $zone_id, $manager_ids ){

		$AdminUser = new AdminUserController();
		return $AdminUser->addUserToEntity( $zone_id, $manager_ids, 'ZONE' );
	}

	/**
	 *
	 * @param unknown_type $entity_type
	 */
	public function getAll( $include_inactive = false ){

		return parent::getAll( 'ZONE', $include_inactive );
	}

	/**
	 * get all the inactive zones for particular organization
	 */
	public function getAllInActive(){
		return parent::getAllInActive( 'ZONE' );
	}

	/**
	 * set status updation for zone
	 * @param unknown_type $entity_id
	 * @param unknown_type $status
	 */
	public function setActiveStatus( $entity_id, $status ){

		$this->logger->debug("Set zone active status update : " . $entity_id);

		$result = parent::setActiveStatus( $entity_id, $status );

		if( $result == 'SUCCESS' && $status != 1 ){
				
			$child_stores = $this->getChildrenEntityByType( $entity_id, 'STORE');
			if( count( $child_stores ) > 0 ){
				$this->logger->debug("update child stores status : " .print_r( $child_stores ,true ) );
				$this->C_storeModel->updateChildStatus( 'STORE' , $child_stores , $this->org_id , $status );
			}
				
			$child_tills = $this->getChildrenEntityByType( $entity_id, 'TILL');
			if( count( $child_tills ) > 0 ){
				$this->logger->debug("update child tills status  : " .print_r( $child_tills ,true ) );
				$this->C_storeModel->updateChildStatus( 'TILL' , $child_tills , $this->org_id , $status );
			}
				
			$child_store_servers = $this->getChildrenEntityByType( $entity_id, 'STR_SERVER');
			if( count( $child_store_servers ) > 0 ){
				$this->logger->debug("update child store server status  : " .print_r( $child_store_servers ,true ) );
				$this->C_storeModel->updateChildStatus( 'STR_SERVER' , $child_store_servers , $this->org_id , $status );
			}
		}
		return $result;
	}
	/**
	 *
	 * @param $zone_ids
	 * @param $include_inactive
	 */
	public function getByIds( $zone_ids, $include_inactive = false ){

		return parent::getByIds( $zone_ids, 'ZONE', $include_inactive );
	}

	/**
	 * Returns the managers for the entities
	 * @param unknown_type $entity_id
	 */
	public function getManagers( $entity_id ){

		return parent::getManagers( $entity_id );
	}


	/**
	 * @param unknown_type $org_id
	 * @param array $entity_ids
	 */
	public static function getUsersEmailMapByZoneIds( $org_id, array $entity_ids ){

		return ApiEntityController::getUsersEmailMapByEntityIds( $org_id, $entity_ids );
	}

	/**
	 * get default zone
	 */
	public function getDefault(){

		return parent::getDefault();
	}

	/**
	 *
	 * @param unknown_type $zone_id
	 */
	public function getReportingEmails( $zone_id ){

		return $this->org_model->getZonalReportingEmail( $zone_id );
	}

	/**
	 *
	 * @param unknown_type $manager_emails
	 */
	public function setReportingEmailIds( $zone_id, $manager_emails ){

		return $this->org_model->setReportingEmailIds( $zone_id, $manager_emails, $this->user_id );
	}

	/**
	 *
	 * It will update the timezone of all the zones under this organization
	 * @param int $time_zone_id
	 */
	public function updateZonesTimezone( $time_zone_id ){

		$this->logger->debug("Start of zones timezone updation org_id: $this->org_id, entity type: $this->entity_type");

		return $this->C_storeModel->updateTimeZonesByEntityType( $this->entity_type , $this->org_id , $time_zone_id );
	}

	/**
	 * @param $zone_id
	 * @return timezone info of zone from zone hierarchy. ZONE->ZONE->...->ORG
	 * TODO : implement caching
	 */
	public function getZonesTimezoneFromHierarchy($zone_id)
	{
		$key = "timezone";
		$details = $this->getEntityDetailsFromCache($zone_id, $this->org_id);
		{
			if($details[$key])
				return $details[$key];
		}

		if (isset($zone_id) && $zone_id > 0) {
			try {
				while (1) {
					$tz = $this->getEntityTimeZone($zone_id, $this->org_id);
					if (isset($tz['timezone_label']) || isset($tz['timezone_offset'])) {
						$this->logger->debug("Timezone available at this zone : " + $zone_id);
						$this->setEntityDetailsToCache($zone_id, $this->org_id, array($key=> $tz));
						return $tz;
					}
					$entity_resolver = new EntityResolver($zone_id);
					$parent = $entity_resolver->getParent('ZONE');
					if (isset($parent[0]) && $parent[0] > 0) {
						$zone_id = $parent[0];
						$this->logger->debug("Going to fetch for parent zone : " . $zone_id);
					} else {
						$this->logger->debug("Parent zone not available. Going to break and ask org for tz");
						break;
					}
				}
			} catch (Exception $e) {
				$this->logger->debug($e->getMessage());
			}
		}
		$this->logger->debug("Failed to get timezone from zone hierarchy. returning org default timezone");
		$organization_controller = new ApiOrganizationController();
		$tz = $organization_controller->getOrgDefaultTimezone();
		$this->setEntityDetailsToCache($zone_id, $this->org_id, array($key=> $tz));
		return $tz;

	}

	public function getZonesLanguageFromHierarchy($zone_id)
	{
		$key = "languages";
		$details = $this->getEntityDetailsFromCache($zone_id, $this->org_id);
		{
			if($details[$key])
				return $details[$key];
		}

		if (isset($zone_id) && $zone_id > 0) {
			try {
				while (1) {
					$tz = $this->getEntityLanguage($zone_id, $this->org_id);
					if (isset($tz['language_code']) || isset($tz['language_locale'])) {
						$this->logger->debug("Language available at this zone : " + $zone_id);
						$this->setEntityDetailsToCache($zone_id, $this->org_id, array($key=> $tz));
						return $tz;
					}
					$entity_resolver = new EntityResolver($zone_id);
					$parent = $entity_resolver->getParent('ZONE');
					if (isset($parent[0]) && $parent[0] > 0) {
						$zone_id = $parent[0];
						$this->logger->debug("Going to fetch Language for parent zone : " . $zone_id);
					} else {
						$this->logger->debug("Parent zone not available. Going to break and ask org for Language");
						break;
					}
				}
			} catch (Exception $e) {
				$this->logger->debug($e->getMessage());
			}
		}
		$this->logger->debug("Failed to get Language from zone hierarchy. returning org default Language");
		$organization_controller = new ApiOrganizationController();
		$tz = $organization_controller->getOrgDefaultLanguage();
		$this->setEntityDetailsToCache($zone_id, $this->org_id, array($key=> $tz));
		return $tz;
	}

	public function getZonesCurrencyFromHierarchy($zone_id)
	{
		$key = "currency";
		$details = $this->getEntityDetailsFromCache($zone_id, $this->org_id);
		{
			if($details[$key])
				return $details[$key];
		}
		 
		if (isset($zone_id) && $zone_id > 0) {
			try {
				while (1) {
					$tz = $this->getEntityCurrency($zone_id, $this->org_id);
					if (isset($tz['currency_symbol']) || isset($tz['currency_code'])) {
						$this->logger->debug("currency available at this zone : " + $zone_id);
						$this->setEntityDetailsToCache($zone_id, $this->org_id, array($key=> $tz));
						return $tz;
					}
					$entity_resolver = new EntityResolver($zone_id);
					$parent = $entity_resolver->getParent('ZONE');
					if (isset($parent[0]) && $parent[0] > 0) {
						$zone_id = $parent[0];
						$this->logger->debug("Going to fetch currency for parent zone : " . $zone_id);
					} else {
						$this->logger->debug("Parent zone not available. Going to break and ask org for currency");
						break;
					}
				}
			} catch (Exception $e) {
				$this->logger->debug($e->getMessage());
			}
		}
		$this->logger->debug("Failed to get currency from zone hierarchy. returning org default currency");
		$organization_controller = new ApiOrganizationController();
		$tz = $organization_controller->getOrgDefaultCurrency();
		$this->setEntityDetailsToCache($zone_id, $this->org_id, array($key=> $tz));
		return $tz;
	}
}

?>