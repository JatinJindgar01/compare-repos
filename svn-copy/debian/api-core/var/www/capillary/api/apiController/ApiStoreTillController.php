<?php
include_once 'apiModel/class.ApiStoreModelExtension.php';
//TODO: referes to cheetah
include_once 'base_model/class.StoreUnit.php';
//TODO: referes to cheetah
include_once 'base_model/class.StoreTemplate.php';
//TODO: referes to cheetah
include_once 'model_extension/class.LoggableUserModelExtension.php';
include_once 'apiController/ApiBaseController.php';
include_once 'apiController/ApiEntityController.php';

define('SUCCESS', 1000);
define('ERR_STORE_ID_REQUIRE', 'The Store Has To Be Selected' );

$GLOBALS["store_error_responses"] = 
array(
		SUCCESS => 'Operation Successfull',
		ERR_STORE_ID_REQUIRE => 'The Store Has To Be Selected'
);	

$GLOBALS["store_error_keys"] = 
array (
		SUCCESS => 'SUCCESS',
		ERR_STORE_ID_REQUIRE => 'ERR_STORE_ID_REQUIRE'
);

/**
 * The store manager is reponsible for adding,
 * editing, fetching of all the stores.
 * 
 * store template and store units are handled by the
 * this conteoller.
 * 
 * later on store clusters/values will be handled by store controller.
 * 
 * This is the file which handles all the store related query.
 * 
 * @author prakhar
 *
 */
class ApiStoreTillController extends ApiEntityController{
	
	private $StoreUnitModel;
	private $StoreModelExtension;
	private $LoggableUserModelExt;
			
	public function __construct(){
		
		global $store_error_responses, $store_error_keys;
		 
		parent::__construct( 'TILL', $store_error_responses, $store_error_keys );
		
		$this->StoreUnitModel = new StoreUnitModel();
		$this->StoreModelExtension = new ApiStoreModelExtension();
		$this->LoggableUserModelExt = new LoggableUserModelExtension();
	}

	/**
	 * add the new entity
	 */
	public function add($store_till_details , $parent_id , $parent_type ){
		
		$code = $this->StoreModelExtension->isStoreTillCodeExists( $store_till_details['code'] );
		
		if( $code )
			return 'Till with the username ( '.$code.' ) already exists';
		
		return parent::add( $store_till_details , $parent_id , $parent_type );
	}

	/**
	 * update store details
	 * 
	 * @param array $store_till_details
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
	public function update( $store_till_id, $store_till_details ){
		
		return parent::update( $store_till_id, $store_till_details );
	}	
	
	/**
	 * Updates The Till Level Deployment Files Set At Organization Level
	 */
	public function updateDeploymentFiles( $till_id ){
		
		$status = $this->StoreModelExtension->updateDeploymentFiles( $this->org_id, $till_id, $this->user_id );

		if( $status )
			return 'SUCCESS';
			
		return 'SOME ERROR OCCURED';
	}
	
	/**
	 * Returns the store as option for using in select box
	 * 
	 * @return array( name => store_id )
	 */
	public function getStoreTillsAsOptions(){
		
		return $this->getEntityAsOptions();
	}
	
	/**
	 * Return the orgEntity Object
	 * @param unknown_type $zone_id
	 */
	public function getDetails( $till_id ){
		
		$this->logger->debug( 'Fetching Till Details For Id : '.$till_id );
		return $this->getDetailsById( $till_id );
	}	

	/**
	 * Returns complete info for the stores
	 * 
	 * @param unknown_type $store_id
	 */
	public function getInfoDetails( $store_till_id ){
		
		$this->logger->debug( 'Fetching Till Details For Id : '.$store_till_id );
		return $this->StoreModelExtension->getStoreUnitInfoDetails( $this->org_id, $store_till_id );	
	}
	
	public function addBulkStoreUnits ( $store_unit_details ){
		
		return $this->StoreUnitModel->addBulkStoreUnits( $store_unit_details );
		
	}
	/**
	 * CONTRACT
	 * array(
	 * 'store_id' => value,
	 * 'parent_id' => value,
	 * 'established_on' => value,
	 * 'disable_mac_addr_check' => value
	 * )
	 * 
	 * @param $store_unit_id
	 * @param $store_unit_details
	 */
	public function updateStoreTill( $store_till_id, array $store_till_details ) {
		
		extract( $store_till_details );
		
		if( $store_id == -1 )
			return 'STORE NEEDS TO BE SELECTED';

			
		$this->StoreUnitModel->load( $store_till_id );
		
		$this->StoreUnitModel->setStoreId( $store_id );
		$this->StoreUnitModel->setParentId( $parent_id );
		//TODO : add config check
		$this->StoreUnitModel->setDisableMacAddrCheck( $disable_mac_addr_check );
		$this->StoreUnitModel->setIsWebloginEnabled( $store_till_details['is_web_login_enabled'] );
				
		$this->StoreUnitModel->setLastUpdatedBy( $this->user_id );
		$this->StoreUnitModel->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
		
		$status = $this->StoreUnitModel->update( $store_till_id );
		
		$this->addParentStore( $store_id , $store_till_id );
		if( $status )
			return 'SUCCESS';
			
		return 'SOME ERROR OCCURED';
	}
	
	/**
	 * CONTRACT
	 * array(
	 * 'password' => value,
	 * 'confirm_password' => value,
	 * )
	 * 
	 * The login details
	 * 
	 * @param int $id
	 * @param array $credential_details
	 */
	public function updateLoginCredentials( $store_till_id, array $credential_details, $existing_password ){
		
		extract( $credential_details );
		
		try {
			
			
			$this->StoreUnitModel->load( $store_till_id );
			$client_type = $this->StoreUnitModel->getClientType();
			
			$this->LoggableUserModelExt->loadByRefId( $store_till_id, 'TILL' );

			if( $password && $existing_password != md5( $password ) ){
				
				$this->LoggableUserModelExt->validatePassword( $password, $confirm_password );
				$this->LoggableUserModelExt->setPassword( md5( $password ) );
			}
			
			$this->LoggableUserModelExt->setLastUpdatedBy( $this->user_id );
			$this->LoggableUserModelExt->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
			
			$loggable_id = $this->LoggableUserModelExt->getId();
			$status = $this->LoggableUserModelExt->update( $loggable_id );
			if( $status ) return 'SUCCESS';
			
			return 'FAILURE';

		}catch ( Exception $e ){
			
			return $e->getMessage();
		}
	}
	
	/**
	 * CONTRACT
	 * array(
	 * 'username' => value,
	 * 'password' => value,
	 * 'confirm_password' => value,
	 * )
	 * 
	 * The login details
	 * 
	 * @param int $id
	 * @param array $credential_details
	 */
	public function addLoginCredentials( $store_till_id, array $credential_details ){
		
		extract( $credential_details );
		
		try {
			
			$this->LoggableUserModelExt->validateUserName( $username );
			$this->LoggableUserModelExt->validatePassword( $password, $confirm_password );
			
			$this->StoreUnitModel->load( $store_till_id );

			$this->LoggableUserModelExt->setRefId( $store_till_id );
			$this->LoggableUserModelExt->setOrgId( $this->org_id );
			$this->LoggableUserModelExt->setUsername( $username );
			$this->LoggableUserModelExt->setPassword( md5( $password ) );
			$this->LoggableUserModelExt->setIsActive( 1 );
			
			$client_type = $this->StoreUnitModel->getClientType();
			$this->LoggableUserModelExt->setType( 'TILL' );
			$this->LoggableUserModelExt->setLastUpdatedBy( $this->user_id );
			
			$thirty_day_validity = Util::getDateByDays( false, 30 );
			$this->LoggableUserModelExt->setPasswordValidity( $thirty_day_validity );
			$this->LoggableUserModelExt->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
			
			$id = $this->LoggableUserModelExt->insert();
			if( $id ) return 'SUCCESS';
			
			return 'FAILURE';

		}catch ( Exception $e ){
			//die($e->getMessage() . "Exception caught");
			return $e->getMessage();
		}
	}

	/**
	 * desable login for the store units like terminal and store server
	 * @param unknown_type $store_unit_id
	 * @param unknown_type $status
	 */
	public function setActiveStatus( $store_till_id, $is_active ){

		$status = parent::setActiveStatus( $store_till_id , $is_active );
		if( !$status ) return 'FAILURE'; 
		
		$this->StoreUnitModel->load( $store_till_id );
		$client_type = $this->StoreUnitModel->getClientType();
	
		$this->LoggableUserModelExt->loadByRefId( $store_till_id, $client_type );

		$this->LoggableUserModelExt->setIsActive( $is_active );
		
		$this->LoggableUserModelExt->setLastUpdatedBy( $this->user_id );
		$this->LoggableUserModelExt->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
		
		$loggable_id = $this->LoggableUserModelExt->getId();
		
		$status = $this->LoggableUserModelExt->update( $loggable_id );
		if( !$status ) return 'FAILURE';
		
		$status = $this->StoreModelExtension->updateStatus( $store_till_id , $this->org_id , $is_active );
		if( $status ) return 'SUCCESS';
		
		return 'FAILURE';
	}
	
	/**
	 * returns parent store
	 */
	public function getParentStore( ){

		return $this->OrgEntityModelExtension->getParentEntities( 'STORE' );
	}
	
	/**
	 * returns parent store serevr
	 */
	public function getParentStoreServer(){
		
		return $this->OrgEntityModelExtension->getParentEntities( 'STR_SERVER' );
	}
	
	/**
	 * returns parebt concept
	 */
	public function getParentConcept(){
		
		return $this->OrgEntityModelExtension->getParentEntities( 'CONCEPT' );
	}
	
	/**
	 * returns parent zone
	 */
	public function getParentZone(){
		
		return $this->OrgEntityModelExtension->getParentEntities( 'ZONE' );
	}
	
	/**
	 * Assigns the parent zone for the widget
	 * @param unknown_type $parent_zone_id
	 * @param unknown_type $child_store_id
	 */
	public function addParentZone( $parent_zone_id, $child_store_till_id ){
		
		try{

			$this->addParentEntity( $parent_zone_id, 'ZONE', $child_store_till_id, 'TILL' );
			
			return 'SUCCESS';
		}catch( Exception $e ){
			
			return $e->getMessage();
		}
	}
	
	/**
	 * Assigns the parent concept for the widget
	 * @param unknown_type $parent_concept_id
	 * @param unknown_type $child_store_id
	 */
	public function addParentConcept( $parent_concept_id, $child_store_till_id ){

		try{

			$this->addParentEntity( $this->org_id, $parent_concept_id, 'CONCEPT', $child_store_till_id, 'TILL' );
			
			return 'SUCCESS';
		}catch( Exception $e ){
			
			return $e->getMessage();
		}
		
	}

	/**
	 * Assigns the parent store for the widget
	 * @param unknown_type $parent_store_id
	 * @param unknown_type $child_store_server_id
	 */
	public function addParentStore( $parent_store_id, $child_store_till_id ){

		try{

			$this->addParentEntity( $parent_store_id, 'STORE', $child_store_till_id, 'TILL' );
			
			return 'SUCCESS';
		}catch( Exception $e ){
			
			return $e->getMessage();
		}
	}

	/**
	 * Assigns the parent store server for the widget
	 * @param unknown_type $parent_store_id
	 * @param unknown_type $child_store_server_id
	 */
	public function addParentStoreServer( $parent_store_id, $child_store_till_id ){

		try{

			$this->addParentEntity( $this->org_id, $parent_store_id, 'STR_SERVER', $child_store_till_id, 'TILL' );
			
			return 'SUCCESS';
		}catch( Exception $e ){
			
			return $e->getMessage();
		}
	}
	
	/**
	 * 
	 * @param unknown_type $entity_type
	 */
	public function getAll( $include_inactive = false , $admin_type = false ){
		
		//return parent::getAll( 'TILL', $include_inactive );
		
		$child_result = parent::getAll( 'TILL', $include_inactive , $admin_type );
		
		$entity_ids = array();
		foreach( $child_result as $res )
			array_push( $entity_ids , $res['id'] );
		
		$parent_result = ApiOrgEntityModelExtension::getParentStoresWithTills( 
														$this->org_id , $entity_ids , 
														'STORE' , $include_inactive , $admin_type);
		
		$result = array();
		foreach( $child_result as $res ){
			$data = array_merge( $res ,  $parent_result[ $res['id'] ] );
			if( $data )
				array_push( $result , $data );
		}
			
		return $result;
	}
	
/**
	 * get all the inactive zones for particular organization
	 */
	public function getAllInActive(){
		
		//return parent::getAllInActive( 'TILL' );
		$child_result = parent::getAllInActive( 'TILL' );
		
		$entity_ids = array();
		foreach( $child_result as $res )
			array_push( $entity_ids , $res['id'] );
		
		$parent_result = ApiOrgEntityModelExtension::getParentStoresWithTills( 
														$this->org_id , $entity_ids , 
														'STORE' , true , false );
		
		$result = array();
		foreach( $child_result as $res ){
			$data = array_merge( $res , $parent_result[ $res['id'] ] );
			if( $data )
				array_push( $result , $data );
		}
			
		return $result;
	}

	/**
	 * 
	 * @param unknown_type $till_ids
	 * @param unknown_type $include_inactive
	 */
	public function getByIds( $till_ids, $include_inactive = false ){
		
		return parent::getByIds( $till_ids, 'TILL', $include_inactive );
	}
	
	/**
	 * Returns the managers for the entities
	 * @param unknown_type $entity_id
	 */
	public function getManagers( $entity_id ){
		
		return parent::getManagers( $entity_id );
	}
	
	/**
	 * Returns the LoggableUserObject hash
	 * @param $till_id
	 */
	public function loadLoggableUserByTillId( $till_id ){
		
		$this->logger->debug( 'Fetching Loggable Details For Ref Id : '.$till_id );
		
		$this->LoggableUserModelExt->loadByRefId( $till_id, $this->entity_type );
		
		return $this->LoggableUserModelExt->getHash();
	}

	/**
	 * returns back the till hash
	 * @param $till_id
	 */
	public function load( $till_id ){
		
		$this->StoreUnitModel->load( $till_id );
		$this->OrgEntityModelExtension->load( $till_id );
		
		return $this->StoreUnitModel->getHash();
	}
	
	/**
	 * Add/Update The New Till
	 * @param unknown_type $till_id
	 */
	public function addShopbookStore( $till_id ){
		
		$this->logger->debug( 'Adding / Updating The Stores For Store Till For User_id : '.$till_id.' : org_id : '.$org_id );
		$status = $this->StoreModelExtension->addShopbookStoreForUser( $till_id, $this->org_id );
		$this->logger->debug( 'Added / Updated The Stores For Store Till For User_id : '.$till_id.' : org_id : '.$org_id );		
	}
	
	/**
	 * 
	 * It will update the timezone of all the stores tills under this organization
	 * @param int $time_zone_id
	 */
	public function updateStoreTillsTimezone( $time_zone_id ){
		
		$this->logger->debug("Start of tills timezone updation org_id: $this->org_id, entity type: $this->entity_type");
		
		return $this->StoreModelExtension->updateTimeZonesByEntityType( $this->entity_type , $this->org_id , $time_zone_id );
	}

	/**
	 * Get Client Log File Metadata for Till 
	 * @param unknown_type $till_id
	 * @param unknown_type $logged_time
	 * @param unknown_type $uploaded_time
	 */
	public function getClientLogFileMetadataByTill( $till_id, $logged_time, $uploaded_time ){
		
		global $currentorg;
		
		$this->logger->debug( "Loading Client Log File Metadata for till: $till_id, logged_time $logged_time, uploaded_time: $uploaded_time" );
		
		return $this->StoreModelExtension->getClientLogFileMetadataForTill( $currentorg->org_id, $till_id, $logged_time, $uploaded_time );
	}

    /**
     * @return timezone of till from hierarchy of STORE->STORE-SERVER -> ZONE/CONCEPT ->ORG
     */
    public function getTillTimezoneFromHierarchy($id){
        $this->logger->debug("timezone not available for this till. Need to fetch from store entity for : " . $id);

        $key = "timezone";
        $details = $this->getEntityDetailsFromCache($id, $this->org_id);
        {
        	if($details[$key])
        		return $details[$key];
        }
        
        $api_entity_Controller = new ApiEntityController('TILL');
        $entity_resolver = new EntityResolver($id, 'TILL');

        $store = $entity_resolver->getParent('STORE');
        if (isset($store[0])) {
            $tz = $api_entity_Controller->getEntityTimeZone($store[0], $this->org_id);
            if (isset($tz['timezone_label']) || isset($tz['timezone_offset'])) {
                $this->logger->debug("Timezone available for store : " . print_r($tz, true));
                $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
                return $tz;
            } else {
                //timezone of store not set. need to go to store server
                $entity_resolver = new EntityResolver($store[0], 'STORE');
                $this->logger->debug("Timezone NOT available for store. Going to fetch for zone hierarchy");
                $zone = $entity_resolver->getParent('ZONE');
                $this->logger->debug("Timezone NOT available for store. going to fetch timezone for zone : " . $zone[0]);
                $zone_Controller = new ApiZoneController();
                $tz = $zone_Controller->getZonesTimezoneFromHierarchy($zone[0]);
                $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
                return $tz;
            }
        } else {
            $this->logger->debug("Failed to get timezone from store hierarchy. returning org default timezone");
            $organization_controller = new ApiOrganizationController();
            $tz = $organization_controller->getOrgDefaultTimezone();
            $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
            return $tz;
        }

    }

    public function getTillLanguageFromHierarchy($id){
    	
        $this->logger->debug("Language not available for this till. Need to fetch from store entity for : " . $id);

        $key = "language";
        $details = $this->getEntityDetailsFromCache($id, $this->org_id);
        {
        	if($details[$key])
        		return $details[$key];
        }
        
        $api_entity_Controller = new ApiEntityController('TILL');
        $entity_resolver = new EntityResolver($id, 'TILL');

        $store = $entity_resolver->getParent('STORE');
        if (isset($store[0])) {
            $tz = $api_entity_Controller->getEntityLanguage($store[0], $this->org_id);
            if (isset($tz['language_code']) || isset($tz['language_locale'])) {
                $this->logger->debug("Language available for store : " . print_r($tz, true));
                $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
                return $tz;
            } else {
                //timezone of store not set. need to go to store server
                $entity_resolver = new EntityResolver($store[0], 'STORE');
                $this->logger->debug("Language NOT available for store. Going to fetch for zone hierarchy");
                $zone = $entity_resolver->getParent('ZONE');
                $this->logger->debug("Language NOT available for store. going to fetch Language for zone : " . $zone[0]);
                $zone_Controller = new ApiZoneController();
                $tz = $zone_Controller->getZonesLanguageFromHierarchy($zone[0]);
                $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
                return $tz;
            }
        } else {
            $this->logger->debug("Failed to get Language from store hierarchy. returning org default Language");
            $organization_controller = new ApiOrganizationController();
            $tz = $organization_controller->getOrgDefaultLanguage();
            $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
            return $tz;
        }

    }

    public function getTillCurrencyFromHierarchy($id){
        $this->logger->debug("Currency not available for this till. Need to fetch from store entity for : " . $id);

        $key = "currency";
        $details = $this->getEntityDetailsFromCache($id, $this->org_id);
        {
        	if($details[$key])
        		return $details[$key];
        }
        
        $api_entity_Controller = new ApiEntityController('TILL');
        $entity_resolver = new EntityResolver($id, 'TILL');

        $store = $entity_resolver->getParent('STORE');
        if (isset($store[0])) {
            $tz = $api_entity_Controller->getEntityCurrency($store[0], $this->org_id);
            if (isset($tz['currency_symbol']) || isset($tz['currency_code'])) {
                $this->logger->debug("Currency available for store : " . print_r($tz, true));
                $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
                return $tz;
            } else {
                //timezone of store not set. need to go to store server
                $entity_resolver = new EntityResolver($store[0], 'STORE');
                $this->logger->debug("Currency NOT available for store. Going to fetch for zone hierarchy");
                $zone = $entity_resolver->getParent('ZONE');
                $this->logger->debug("Currency NOT available for store. going to fetch Currency for zone : " . $zone[0]);
                $zone_Controller = new ApiZoneController();
                $tz = $zone_Controller->getZonesCurrencyFromHierarchy($zone[0]);
                $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
                return $tz;
            }
        } else {
            $this->logger->debug("Failed to get currency from store hierarchy. returning org default currency");
            $organization_controller = new ApiOrganizationController();
            $tz = $organization_controller->getOrgDefaultCurrency();
            $this->setEntityDetailsToCache($id, $this->org_id, array($key=> $tz));
            return $tz;
        }

    }
}

?>