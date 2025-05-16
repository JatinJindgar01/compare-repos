<?php 
include_once 'apiModel/class.ApiStoreModelExtension.php';
//TODO: referes to cheetah
include_once 'base_model/class.StoreUnit.php';
//TODO: referes to cheetah
include_once 'base_model/class.StoreTemplate.php';
//TODO: referes to cheetah
include_once 'model_extension/class.LoggableUserModelExtension.php';
include_once 'apiController/ApiBaseController.php';

define(SUCCESS, 1000);
define(ERR_STORE_ID_REQUIRE, 'The Store Has To Be Selected' );

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
class ApiStoreServerController extends ApiEntityController{
	
	private $StoreUnitModel;
	private $StoreModelExtension;
	private $LoggableUserModelExt;
			
	public function __construct( ){
		
		global $store_error_responses, $store_error_keys;
		 
		parent::__construct( 'STR_SERVER', $store_error_responses, $store_error_keys );
		
		$this->StoreUnitModel = new StoreUnitModel();
		$this->StoreModelExtension = new ApiStoreModelExtension();
		$this->LoggableUserModelExt = new LoggableUserModelExtension();
	}

	/**
	 * add the new entity
	 * 
	 * @param array $store_server_details
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
	public function add( $store_server_details , $parent_id , $parent_type){
		
		return parent::add( $store_server_details , $parent_id , $parent_type);
	}

	/**
	 * update store details
	 * 
	 * @param array $store_server_details
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
	public function update( $store_server_id, $store_server_details ){
		
		return parent::update( $store_server_id, $store_server_details );
	}	
	
	/**
	 * Returns the store as option for using in select box
	 * Remove all the store id passed to the function
	 * 
	 * @params $store_server_ids
	 * @return array( name => store_server_id )
	 */
	public function getStoreServerAsOptions( $store_server_ids = array() ){
		
		return $this->getEntityAsOptions( $store_server_ids );
	}
		
	/**
	 * 
	 * @param $store_server_ids
	 */
	public function getStoreServerByIdsAsOptions( $store_server_ids ){
		
		return $this->getEntityByIdsAsOptions( $store_server_ids );		
	}
	
	/**
	 * CONTRACT
	 * array(
	 * 'store_id' => value,
	 * 'parent_id' => value,
	 * 'established_on' => value,
	 * 'store_server_prefix' => value,
	 * 'disable_mac_addr_check' => value
	 * )
	 * 
	 * @param $store_unit_id
	 * @param $store_unit_details
	 */
	public function updateStoreServer( $store_server_id, array $store_server_details ) {
		
		extract( $store_server_details );
		
		if( $store_id == -1 )
			return 'STORE NEEDS TO BE SELECTED';
		
			
		$this->StoreUnitModel->load( $store_server_id );
		
		$this->StoreUnitModel->setStoreId( $store_id );
		$this->StoreUnitModel->setParentId( $parent_id );
		$this->StoreUnitModel->setEstablishedOn( $established_on );
		$this->StoreUnitModel->setStoreServerPrefix( $store_server_prefix );
		$this->StoreUnitModel->setDisableMacAddrCheck( $disable_mac_addr_check );
		$this->StoreUnitModel->setLastUpdatedBy( $this->user_id );
		$this->StoreUnitModel->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );

		$this->logger->debug('resetting the parent of the store server');
		parent::addStoreUnitParents( $store_id, 'STORE', $store_server_id);
		
		$status = $this->StoreUnitModel->update( $store_server_id );
		
		if( $status )
			return 'SUCCESS';
			
		return 'SOME ERROR OCCURED';
	}

	/**
	 * Returns till ids
	 * 
	 * @return array( till_ids )
	 */
	public function getStoreTerminalsByStoreServerId( $store_server_id ){
		
		return $this->getChildrenEntityByType( $store_server_id, 'TILL');
	}
	
	/**
	 * Return the orgEntity Object
	 * @param unknown_type $zone_id
	 */
	public function getDetails( $store_server_id ){
		
		return $this->getDetailsById( $store_server_id );
	}		
	
	/**
	 * Returns complete info for the stores
	 * 
	 * @param unknown_type $store_id
	 */
	public function getInfoDetails( $store_server_id ){
		
		$this->logger->debug( 'Fetching Store Server Details For Id : '.$store_server_id );
		return $this->StoreModelExtension->getStoreUnitInfoDetails( $this->org_id, $store_server_id );	
	}
	
	/**
	 * CONTRACT
	 * array(
	 * 'password' => value,
	 * 'confirm_password' => value,
	 * 'secret_question' => value,
	 * 'secret_answer' => value,
	 * )
	 * 
	 * The login details
	 * 
	 * @param int $id
	 * @param array $credential_details
	 */
	public function updateLoginCredentials( $store_server_id, array $credential_details, $existing_password ){
		
		extract( $credential_details );
		
		try {
			
			$this->StoreUnitModel->load( $store_server_id );
			$client_type = $this->StoreUnitModel->getClientType();
			
			$this->LoggableUserModelExt->loadByRefId( $store_server_id, 'STR_SERVER' );

			if( $password && $existing_password != md5( $password ) ){
				
				$this->LoggableUserModelExt->validatePassword( $password, $confirm_password );
				$this->LoggableUserModelExt->setPassword( md5( $password ) );
			}
			
			$this->LoggableUserModelExt->setSecretQuestion( $secret_question );
			$this->LoggableUserModelExt->setSecretAnswer( $secret_answer );
			
			$this->LoggableUserModelExt->setLastUpdatedBy( $this->user_id );
			$this->LoggableUserModelExt->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
			
			$id = $this->LoggableUserModelExt->getId();
			
			$status = $this->LoggableUserModelExt->update( $id );
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
	 * 'secret_question' => value,
	 * 'secret_answer' => value,
	 * )
	 * 
	 * The login details
	 * 
	 * @param int $id
	 * @param array $credential_details
	 */
	public function addLoginCredentials( $store_server_id, array $credential_details ){
		
		extract( $credential_details );
		
		try {
			
			$this->LoggableUserModelExt->validateUserName( $username );
			$this->LoggableUserModelExt->validatePassword( $password, $confirm_password );
			
			$this->StoreUnitModel->load( $store_server_id );

			$this->LoggableUserModelExt->setRefId( $store_server_id );
			$this->LoggableUserModelExt->setOrgId( $this->org_id );
			$this->LoggableUserModelExt->setUsername( $username );
			$this->LoggableUserModelExt->setPassword( md5( $password ) );
			$this->LoggableUserModelExt->setSecretQuestion( $secret_question );
			$this->LoggableUserModelExt->setSecretAnswer( $secret_answer );
			$this->LoggableUserModelExt->setIsActive( 1 );
			
			$client_type = $this->StoreUnitModel->getClientType();
			$this->LoggableUserModelExt->setType( 'STR_SERVER' );
			$this->LoggableUserModelExt->setLastUpdatedBy( $this->user_id );
			
			$thirty_day_validity = Util::getDateByDays( false, 30 );
			$this->LoggableUserModelExt->setPasswordValidity( $thirty_day_validity );
			$this->LoggableUserModelExt->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
			
			$id = $this->LoggableUserModelExt->insert();
			if( $id ) return 'SUCCESS';
			
			return 'FAILURE';

		}catch ( Exception $e ){
			
			return $e->getMessage();
		}
	}

	/**
	 * desable login for the store units like terminal and store server
	 * @param unknown_type $store_unit_id
	 * @param unknown_type $status
	 */
	public function setActiveStatus( $store_server_id, $is_active ){
		
		$status = parent::setActiveStatus( $store_server_id , $is_active );
		if( $status != 'SUCCESS' ) return 'FAILURE'; 

		$this->StoreUnitModel->load( $store_server_id );
		$client_type = $this->StoreUnitModel->getClientType();
	
		$this->LoggableUserModelExt->loadByRefId( $store_server_id, $client_type );

		$this->LoggableUserModelExt->setIsActive( $is_active );
		
		$this->LoggableUserModelExt->setLastUpdatedBy( $this->user_id );
		$this->LoggableUserModelExt->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
		
		$loggable_id = $this->LoggableUserModelExt->getId();
		
		$status = $this->LoggableUserModelExt->update( $loggable_id );
		if( $status ) return 'SUCCESS';
		
		return 'FAILURE';
	}
	
	/**
	 * returns parent store
	 */
	public function getParentStore(){
		
		return $this->OrgEntityModelExtension->getParentEntities( 'STORE' );
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
	public function addParentZone( $parent_zone_id, $child_store_server_id ){
		
		try{

			$this->addParentEntity( $parent_zone_id, 'ZONE', $child_store_server_id, 'STR_SERVER' );
			
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
	public function addParentConcept( $parent_concept_id, $child_store_server_id ){
		
		try{

			$this->addParentEntity( $this->org_id, $parent_concept_id, 'CONCEPT', $child_store_server_id, 'STR_SERVER' );
			
			return 'SUCCESS';
		}catch( Exception $e ){
			
			return $e->getMessage();
		}
	}

	/**
	 * Assigns the parent storet for the widget
	 * @param unknown_type $parent_store_id
	 * @param unknown_type $child_store_server_id
	 */
	public function addParentStore( $parent_store_id, $child_store_server_id ){
		
		try{

			$this->addParentEntity( $this->org_id, $parent_store_id, 'STORE', $child_store_server_id, 'STR_SERVER' );
			
			return 'SUCCESS';
		}catch( Exception $e ){
			
			return $e->getMessage();
		}
	}
	
	/**
	 * 
	 * @param unknown_type $entity_type
	 */
	public function getAll( $include_inactive = false ){
		
		return parent::getAll( 'STR_SERVER', $include_inactive );
	}
	
/**
	 * get all the inactive zones for particular organization
	 */
	public function getAllInActive(){
		return parent::getAllInActive( 'STR_SERVER' );
	}
	
	/**
	 * 
	 * @param unknown_type $str_server_ids
	 * @param unknown_type $include_inactive
	 */
	public function getByIds( $str_server_ids, $include_inactive = false ){
		
		return parent::getByIds( $str_server_ids, 'STR_SERVER', $include_inactive );
	}
	
	/**
	 * Returns the managers for the entities
	 * @param unknown_type $entity_id
	 */
	public function getManagers( $entity_id ){
		
		return parent::getManagers( $entity_id );
	}

	/**
	 * 
	 * @param $store_server_id
	 */
	public function loadLoggableUserByStoreServerId( $store_server_id ){
		
		$this->LoggableUserModelExt->loadByRefId( $store_server_id, $this->entity_type );
		
		return $this->LoggableUserModelExt->getHash();
	}
	
	/**
	 * returns back the store server hash
	 * @param $store_server_id
	 */
	public function load( $store_server_id ){
		
		$this->StoreUnitModel->load( $store_server_id );
		
		return $this->StoreUnitModel->getHash();
	}
	
	    /**
     * @return timezone of STR_SERVER from hierarchy of STORE->STORE-SERVER -> ZONE/CONCEPT ->ORG
     */
    public function getStoreServerTimezoneFromHierarchy($id){
        $this->logger->debug("timezone not available for this storeserver. Need to fetch from store entity for : " . $id);

        $key = "timezone";
        $details = $this->getEntityDetailsFromCache($id, $this->org_id);
        {
        	if($details[$key])
        		return $details[$key];
        }
        
        $api_entity_Controller = new ApiEntityController('STR_SERVER');
        $entity_resolver = new EntityResolver($id, 'STR_SERVER');

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
}

?>