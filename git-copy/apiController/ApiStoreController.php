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
include_once 'apiController/ApiStoreTillController.php';
include_once 'apiController/ApiOrganizationController.php';
include_once 'apiController/ApiZoneController.php';
include_once 'apiController/ApiConceptController.php';
include_once 'apiController/ApiStoreServerController.php';
include_once 'apiModel/class.ApiLoyaltyTrackerModelExtension.php';
include_once("fileservice_sdk/FileServiceManager.php");

define('SUCCESS', 1000);
define('ERR_STORE_ID_REQUIRE', -1);

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

define(STORELOG_S3_BUCKET, "test");

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
class ApiStoreController extends ApiEntityController{

	private $StoreModel;
	private $EntityResolver;
	private $StoreUnitModel;
	private $StoreTemplateModel;
	private $LoggableUserModelExt;
	private $StoreTillController;
	private $StoreToZoneMapping;
	private $TillToStoreMapping;

    public function __construct()
    {

        global $store_error_responses, $store_error_keys;

        parent::__construct('STORE', $store_error_responses, $store_error_keys);

        $this->StoreModel = new ApiStoreModelExtension();
        $this->StoreUnitModel = new StoreUnitModel();
        $this->StoreTemplateModel = new StoreTemplateModel();
        $this->LoggableUserModelExt = new LoggableUserModelExtension();
        $this->StoreTillController = new ApiStoreTillController();
        $this->StoreToZoneMapping = array();
        $this->TillToStoreMapping = array();
    }

    /**
     * add the new entity
     *
     * @param array $store_details
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
    public function add($store_details)
    {

        return parent::add($store_details);
    }

    /**
     * update store details
     *
     * @param array $store_details
     *
     * CONTRACT
     * array(
     * 'code' => value,
     * 'name' => value,
     * 'admin_type' => value
     * 'description' => value,
     * 'time_zone_id' => value,
     * 'currency_id' => value,
     * 'language_id' => value,
     * )
     */
    public function update($store_id, $store_details)
    {

        $status = parent::update($store_id, $store_details);
        if ($status)
            $child_status = $this->updateChildTills($store_id, $store_details);
        return $status;
    }

    /*
     * updates child till admin type and timezone
     */
    public function updateChildTills($store_id, $store_details)
    {
        $admin_type = $store_details['admin_type'];

        $child_tills = $this->OrgEntityModelExtension->getChildrenEntities('TILL');
        $child_tills = implode(",", $child_tills);

        //Old function which only updates admin_type
        //$this->StoreModel->updateChildTillsWithAdminType( $store_id , $admin_type , $child_tills );
        return $this->StoreModel->updateChildTillsWithStoreDetails($store_details, $child_tills);
    }

    /**
     * Return the orgEntity Object
     * @param unknown_type $zone_id
     */
    public function getDetails($store_id)
    {

        return $this->getDetailsById($store_id);
    }

    /**
     * Returns complete info for the stores
     *
     * @param unknown_type $store_id
     */
    public function getInfoDetails($store_id)
    {

        $this->logger->debug(' Getting Info Details For The Store Id... ');

        $result = $this->StoreModel->getStoreInfoDetailsByStoreId($this->org_id, $store_id);
        if (isset($result) && isset($result[0]['id'])) {
            if (!isset($result[0]['timezone_label']) && !isset($result[0]['timezone_offset'])) {
                $this->logger->debug("timezone not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreTimezoneFromHierarchy($result[0]['id']);
                $result[0]['timezone_label'] = $tz['timezone_label'];
                $result[0]['timezone_offset'] = $tz['timezone_offset'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['currency_symbol']) && !isset($result[0]['currency_code'])) {
                $this->logger->debug("Currency not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreCurrencyFromHierarchy($result[0]['id']);
                $result[0]['currency_symbol'] = $tz['currency_symbol'];
                $result[0]['currency_code'] = $tz['currency_code'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['language_code']) && !isset($result[0]['language_locale'])) {
                $this->logger->debug("Language not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreLanguageFromHierarchy($result[0]['id']);
                $result[0]['language_code'] = $tz['language_code'];
                $result[0]['language_locale'] = $tz['language_locale'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['country_name']) && !isset($result[0]['country_code'])) {
                $this->logger->debug("country not available for this store. Returning org country");
                $oc = new ApiOrganizationController();
                $tz = $oc->getOrgDefaultCountry();
                $result[0]['country_name'] = $tz['country_name'];
                $result[0]['country_code'] = $tz['country_code'];
                //TODO : add source info from where this tz is coming
            }
        }
        return $result;
    }

    public function getCustomFieldsData($storeId)
    {
        $cf = new CustomFields();
        $storeCustomFields = $cf -> getCustomFieldValuesByAssocId($this -> org_id, 'store_custom_fields' , $storeId);

        $out = array();
        $field = array();
        if(! empty($storeCustomFields)) {
            foreach($storeCustomFields as $key => $value) {
                $field['name'] = $key;                
                $decodedValue = json_decode($value);

                if($decodedValue === null ) {
                    $field['value'] = $value; 
                } else if(is_array($decodedValue) 
                    && count($decodedValue) > 0 && empty($decodedValue[0])) {
                    $field['value'] = '';
                } else {
                    $field['value'] = 
                        is_array($decodedValue) ? implode(",", $decodedValue) : $value;
                }

                $out [] = $field;
            }
        }
        return $out;
    }

    
    private function getChildIdFromResult( $arr )
    {
    	foreach ( $arr as $k => $v )
    		$res[] = $v['child_entity_id'];
    	
    	return $res;
    }
    
    public function getSummary( $org_id, $zone_id )
    {
    	$zone_id_arr = explode( ",", $zone_id );
    	
    	if ( $zone_id == 'ALL' || $zone_id == 'all' )
    	{
    		$zones = $this->StoreModel->getOrgEntityByType( $org_id, 'ZONE' );
    		$concepts = $this->StoreModel->getOrgEntityByType( $org_id, 'CONCEPT' );
    		$stores = $this->StoreModel->getOrgEntityByType( $org_id, 'STORE' );
    		$store_server = $this->StoreModel->getOrgEntityByType( $org_id, 'STR_SERVER' );
    		// p_change starts
    		$org_thin_clients_list = $this->StoreModel->getThinClients( $org_id, 'ALL' );
    		$temp_tills = $this->StoreModel->getOrgEntityByType( $org_id, 'TILL' );
    		$tills = array();
    		$thin_clients = array();
    		foreach ( $temp_tills as $generic_till )
    		{
    			$flag_tc = false;
    			foreach ( $org_thin_clients_list as $each_tc )
    			{
    				if ( $generic_till['id'] == $each_tc['thin_client_id'] ){
    					$thin_clients[] = $generic_till;
    					$flag_tc = true;
    					break;
    				}
    			}
    			if ( !$flag_tc )
    				$tills[] = $generic_till;
    		}
    	}
    	else 
    	{
    		$zone_ids = array();
    		foreach ( $zone_id_arr as $item ){
    			$sz_buf = $this->subZonesUnderZone( $item, $org_id );
    			$zone_ids = array_unique( array_merge( $zone_ids, $sz_buf ) );
    		}
    		//$zone_ids = $this->subZonesUnderZone( $zone_id, $org_id );	// p_change
    		/* $zone_id = array($zone_id);
	    	$child_zones = $this->StoreModel->getChildrenByType( $org_id, $zone_id, 'ZONE' );
	    	$zone_ids = array_merge( $zone_id, $this->getChildIdFromResult( $child_zones ) ); */
	    	$temp_concept_ids = $this->StoreModel->getChildrenByType( $org_id, $zone_ids, 'CONCEPT' );
	    	$concept_ids = $this->getChildIdFromResult( $temp_concept_ids );
	    	$temp_store_ids = $this->StoreModel->getChildrenByType( $org_id, $zone_ids, 'STORE' );
	    	$store_ids = $this->getChildIdFromResult( $temp_store_ids );
	    	$temp_till_ids = $this->StoreModel->getChildrenByType( $org_id, $store_ids, 'TILL' );
	    	$till_ids = $this->getChildIdFromResult( $temp_till_ids );
	    	
	    	$store_server_ids_under_zones = $this->StoreModel->getChildrenByType( $org_id, $zone_ids, 'STR_SERVER' );
	    	$store_server_ids_under_stores = $this->StoreModel->getChildrenByType( $org_id, $store_ids, 'STR_SERVER' );
	    	$temp_store_server_ids = array_merge( $store_server_ids_under_zones, $store_server_ids_under_stores);
	    	$store_server_ids = $this->getChildIdFromResult( $temp_store_server_ids );
	    	$store_server_ids = array_unique( $store_server_ids );
	    	
	    	$zones = $this->StoreModel->getEntityDetails( $org_id, $zone_ids );
	    	$concepts = $this->StoreModel->getEntityDetails( $org_id, $concept_ids );
	    	$stores = $this->StoreModel->getEntityDetails( $org_id, $store_ids );
	    	$store_server = $this->StoreModel->getEntityDetails( $org_id, $store_server_ids );
	    	
	    	$org_thin_clients_list = $this->StoreModel->getThinClients( $org_id, $zone_ids );
	    	$temp_tills = $this->StoreModel->getEntityDetails( $org_id, $till_ids );
	    	$tills = array();
	    	$thin_clients = array();
	    	foreach ( $temp_tills as $generic_till )
	    	{
	    		$flag_tc = false;
	    		foreach ( $org_thin_clients_list as $each_tc )
	    		{
	    			if ( $generic_till['id'] == $each_tc['thin_client_id'] ) {
	    				$thin_clients[] = $generic_till;
	    				$flag_tc = true;
	    				break;
	    			}
	    		}
	    		if ( !$flag_tc )
	    			$tills[] = $generic_till;
	    	}
    	}
    	
    	if ( empty( $zones ) )
    		$summary['zone']['total'] = 0;
    	else 
    		$summary['zone']['total'] = count( $zones );
    	$summary['zone']['active'] = 0;
    	$summary['zone']['inactive'] = 0;
    	foreach ( $zones as $each_zone )
    		if ( $each_zone['is_active'] == 1 || $each_zone['is_active'] == true )
    			$summary['zone']['active'] += 1;
    		else if ( $each_zone['is_active'] == 0 || $each_zone['is_active'] == false )
    			$summary['zone']['inactive'] += 1;
    	
    	if ( empty( $concepts ) )
    		$summary['concept']['total'] = 0;
    	else 
    		$summary['concept']['total'] = count( $concepts );
    	$summary['concept']['active'] = 0;
    	$summary['concept']['inactive'] = 0;
    	foreach ( $concepts as $each_concept )
    		if ( $each_concept['is_active'] == 1 || $each_concept['is_active'] == true )
    			$summary['concept']['active'] += 1;
    		else if ( $each_concept['is_active'] == 0 || $each_concept['is_active'] == false )
    			$summary['concept']['inactive'] += 1;
    	
    	if ( empty( $stores ) )
    		$summary['store']['total'] = 0;
    	else 
    		$summary['store']['total'] = count( $stores );
    	$summary['store']['active'] = 0;
    	$summary['store']['inactive'] = 0;
    	foreach ( $stores as $each_store )
    		if ( $each_store['is_active'] == 1 || $each_store['is_active'] == true )
    			$summary['store']['active'] += 1;
    		else if ( $each_store['is_active'] == 0 || $each_store['is_active'] == false )
    			$summary['store']['inactive'] += 1;
    	
    	if ( empty( $store_server ) )
    		$summary['store_server']['total'] = 0;
    	else 
    		$summary['store_server']['total'] = count( $store_server );
    	$summary['store_server']['active'] = 0;
    	$summary['store_server']['inactive'] = 0;
    	foreach ( $store_server as $each_str_server )
    		if ( $each_str_server['is_active'] == 1 || $each_str_server['is_active'] == true )
    			$summary['store_server']['active'] += 1;
    		else if ( $each_str_server['is_active'] == 0 || $each_str_server['is_active'] == false )
    			$summary['store_server']['inactive'] += 1;
    	
    	if ( empty( $tills ) )
    		$summary['till']['total'] = 0;
    	else 
    		$summary['till']['total'] = count( $tills );
    	$summary['till']['active'] = 0;
    	$summary['till']['inactive'] = 0;
    	foreach ( $tills as $each_till )
    		if ( $each_till['is_active'] == 1 || $each_till['is_active'] == true )
    			$summary['till']['active'] += 1;
    		else if ( $each_till['is_active'] == 0 || $each_till['is_active'] == false )
    			$summary['till']['inactive'] += 1;
    		
    	if ( empty( $thin_clients ) )
            $summary['thin_client']['total'] = 0;
        else 
            $summary['thin_client']['total'] = count( $thin_clients );
        $summary['thin_client']['active'] = 0;
        $summary['thin_client']['inactive'] = 0;
        foreach ( $thin_clients as $each_thin_client )
            if ( $each_thin_client['is_active'] == 1 || $each_thin_client['is_active'] == true )
                $summary['thin_client']['active'] += 1;
            else if ( $each_thin_client['is_active'] == 0 || $each_thin_client['is_active'] == false )
                $summary['thin_client']['inactive'] += 1;
    	
    	return $summary;
    }
    
    public function getAllOrgZones( $org_id )
    {
    	$zones = $this->StoreModel->getOrgEntityByType( $org_id, 'ZONE' );
    	
    	$zone_ids = array();
    	foreach ( $zones as $each_zone )
    		$zone_ids[] = $each_zone['id'];
    	
    	return $zone_ids;
    }
    
    public function subZonesUnderZone ( $zone_id , $org_id )
    {
    	$sub_zones = $this->getChildrenEntityByType( $zone_id, 'ZONE' );
    	$sub_zones = array_merge( array($zone_id), $sub_zones );
    	$sub_zones = array_unique( $sub_zones );
    	
    	return $sub_zones;
    }
    
    public function storesUnderZone ( $zones_array, $org_id )
    {
    	if ( !is_array($zones_array) )
    		$zones_array = array($zones_array);
    	
    	$stores = array();
    	$strToZoneMapping = array();
    	foreach ( $zones_array as $each_zone )
    	{
	    	$temp = $this->getChildrenEntityByType( $each_zone, 'STORE');
	    	foreach ( $temp as $each_str )
	    		$this->StoreToZoneMapping[$each_str] = $each_zone;		// array('store' => 'parent_zone')
	    	$stores = array_merge( $stores, $temp );
    	}
    	
    	return array_unique( $stores );
    }

    public function storeServersUnderZone ( $zones_array, $org_id )
    {
        if ( !is_array( $zones_array ) )
            $zones_array = array( $zones_array );

        $store_servers = array();
        foreach ( $zones_array as $each_zone ) 
        {
            $temp = $this->getChildrenEntityByType( $each_zone, 'STR_SERVER' );
            $store_servers = array_merge( $store_servers, $temp );
        }

        return array_unique( $store_servers );
    }
    
    public function storesUnderConcept ( $concept_id, $org_id )
    {
    	$stores = $this->getChildrenEntityByType( $concept_id, 'STORE');
    	return $stores;
    }
    
    public function storeServersUnderStore ( $stores_array, $org_id )
    {
    	if ( !is_array( $stores_array) )
    		$stores_array = array ( $stores_array );
    	
    	$store_servers = array();
    	foreach ( $stores_array as $each_store )
    	{
	    	$temp = $this->getChildrenEntityByType( $store_id, 'STR_SERVER' );
	    	$store_servers = array_merge( $store_servers, $temp );
    	}
    	
    	return array_unique( $store_servers );
    }
    
    public function tillsUnderStore ( $stores_array, $org_id )
    {
    	if ( !is_array($stores_array) )
    		$stores_array = array( $stores_array );
    	
    	$tills = array();
    	$tillToStoreMapping = array();
    	foreach ( $stores_array as $each_store )
    	{
	    	$temp = $this->getChildrenEntityByType( $each_store, 'TILL' );
	    	foreach ( $temp as $each_till )
	    		$this->TillToStoreMapping[$each_till] = $each_store;		// array( 'till' => 'parent_store')
	    	$tills = array_merge( $tills, $temp );
    	}
    	
    	return array_unique( $tills );
    }
    
    public function tillsUnderStoreServer ( $store_servers_array, $org_id )
    {
    	if ( !is_array($store_servers_array) )
    		$store_servers_array = array( $store_servers_array );
    	
    	$tills = array();
    	foreach ( $store_servers_array as $each_store_server )
    	{
	    	$temp = $this->getChildrenEntityByType( $store_server_id, 'TILL' );
	    	$tills = array_merge( $tills, $temp );
    	}
    	
    	return array_unique( $tills );
    }
    
    public function getAllTills( $params )
    {
    	$zones_buffer = $this->StoreModel->getOrgEntityByType( $params['org_id'], 'ZONE' );
    	$zones = $this->retrieveAttributeFromResult( $zones_buffer, 'id' );
    	$stores = $this->storesUnderZone( $zones, $params['org_id'] );
    	$tills = $this->tillsUnderStore( $stores, $params['org_id'] );
    	
    	return $tills;
    }
    
    public function getTillsInZone( $params )
    {
    	// Handle the case of invalid id/code. Use try-catch
    	
    	if ( empty( $params['filter_id']['zone']['id'] ) )
    	{
    		if ( empty( $params['filter_id']['zone']['code'] ) )
    		{
    			return false;		// return a proper error message
    		}
    		else 
    			$zone_id = $this->StoreModel->getIdFromCode( $params['org_id'], $params['filter_id']['zone']['code'] );
    	}
    	else 
    		$zone_id = $params['filter_id']['zone']['id'];
    	
    	$all_sub_zones = $this->subZonesUnderZone( $zone_id, $params['org_id'] );
    	$stores = $this->storesUnderZone( $all_sub_zones, $params['org_id'] );
    	$tills = $this->tillsUnderStore( $stores, $params['org_id'] );
    	
    	return $tills;
    }
    
    public function getTillsInConcept( $params )
    {
    	// Handle the case of invalid id/code. Use try-catch
    	
    	if ( empty( $params['filter_id']['concept']['id'] ) )
    	{
    		if ( empty( $params['filter_id']['concept']['code'] ) )
    		{
    			return false;		// return a proper error message
    		}
    		else
    			$concept_id = $this->StoreModel->getIdFromCode( $params['org_id'], $params['filter_id']['concept']['code'] );
    	}
    	else
    		$concept_id = $params['filter_id']['concept']['id'];
    	
    	$concept_id = $params['filter_id']['concept']['id'];
    	$stores = $this->storesUnderConcept( $concept_id, $params['org_id'] );
    	$tills = $this->tillsUnderStore( $stores, $params['org_id'] );
    	
    	return $tills;
    }
    
    public function getTillsInStore( $params )
    {
    	// Handle the case of invalid id/code. Use try-catch
    	
    	if ( empty( $params['filter_id']['store']['id'] ) )
    	{
    		if ( empty( $params['filter_id']['store']['code'] ) )
    		{
    			return false;		// return a proper error message
    		}
    		else
    			$store_id = $this->StoreModel->getIdFromCode( $params['org_id'], $params['filter_id']['store']['code'] );
    	}
    	else
    		$store_id = $params['filter_id']['store']['id'];
    	
    	$store_id = $params['filter_id']['store']['id'];
    	$tills = $this->tillsUnderStore( $store_id, $params['org_id'] );
    	
    	return $tills;
    }
    
    public function getTillsInStoreServer( $params )
    {
    	// Handle the case of invalid id/code. Use try-catch
    	 
    	if ( empty( $params['filter_id']['store_server']['id'] ) )
    	{
    		if ( empty( $params['filter_id']['store_server']['code'] ) )
    		{
    			return false;		// return a proper error message
    		}
    		else
    			$store_server_id = $this->StoreModel->getIdFromCode( $params['org_id'], $params['filter_id']['store_server']['code'] );
    	}
    	else
    		$store_server_id = $params['filter_id']['store_server']['id'];
    	
    	$store_server_id = $params['filter_id']['store_server']['id'];
    	$tills = $this->tillsUnderStoreServer( $store_server_id, $params['org_id'] );
    }
    
    public function getAllStoreServers( $params )
    {
    	$zones_buffer = $this->StoreModel->getOrgEntityByType( $params['org_id'], 'ZONE' );
    	$zones = $this->retrieveAttributeFromResult( $zones_buffer, 'id' );
    	$stores = $this->storesUnderZone( $zones, $params['org_id'] );
    	
    	$str_srvr_under_zones = $this->storeServersUnderZone( $zones, $params['org_id'] );
    	$str_srvr_under_stores = $this->storeServersUnderStore( $stores, $params['org_id'] );
    	$all_str_servers = array_merge( $str_srvr_under_stores, $str_srvr_under_zones );
    	
    	return array_unique( $all_str_servers );
    }
    
    public function getStoreServersInZone( $params )
    {
    	// Handle the case of invalid id/code. Use try-catch
    	
    	if ( empty( $params['filter_id']['zone']['id'] ) )
    	{
    		if ( empty( $params['filter_id']['zone']['code'] ) )
    		{
    			return false;		// return a proper error message
    		}
    		else
    			$zone_id = $this->StoreModel->getIdFromCode( $params['org_id'], $params['filter_id']['zone']['code'] );
    	}
    	else
    		$zone_id = $params['filter_id']['zone']['id'];
    	
    	$all_sub_zones = $this->subZonesUnderZone( $zone_id, $params['org_id'] );
    	$str_srvr_under_zones = $this->storeServersUnderZone( $all_sub_zones, $params['org_id'] );
    	
    	$stores = $this->storesUnderZone( $all_sub_zones, $params['org_id'] );
    	$str_srvr_under_stores = $this->storeServersUnderStore( $stores, $params['org_id'] );
    	
    	$all_str_servers = array_merge( $str_srvr_under_stores, $str_srvr_under_zones );
    	
    	return array_unique( $all_str_servers );
    }
    
    public function getStoreServersInConcept( $params )
    {
    	// Handle the case of invalid id/code. Use try-catch
    	
    	if ( empty( $params['filter_id']['concept']['id'] ) )
    	{
    		if ( empty( $params['filter_id']['concept']['code'] ) )
    		{
    			return false;		// return a proper error message
    		}
    		else
    			$concept_id = $this->StoreModel->getIdFromCode( $params['org_id'], $params['filter_id']['concept']['code'] );
    	}
    	else
    		$concept_id = $params['filter_id']['concept']['id'];
    	
    	$stores = $this->storesUnderConcept( $concept_id, $params['org_id'] );
    	$store_servers = $this->storeServersUnderStore( $stores, $params['org_id'] );
    	
    	return array_unique( $store_servers );
    }
    
    public function getStoreServersInStore( $params )
    {
    	// Handle the case of invalid id/code. Use try-catch
    	 
    	if ( empty( $params['filter_id']['store']['id'] ) )
    	{
    		if ( empty( $params['filter_id']['store']['code'] ) )
    		{
    			return false;		// return a proper error message
    		}
    		else
    			$store_id = $this->StoreModel->getIdFromCode( $params['org_id'], $params['filter_id']['store']['code'] );
    	}
    	else
    		$store_id = $params['filter_id']['store']['id'];
    	
    	$store_servers = $this->storeServerUnderStore( $store_id, $params['org_id']);;
    	
    	return array_unique( $store_servers );
    }
    
    public function getDistinctComponentTypes( $entity_type, $report_type, $org_id )
    {
    	return $this->StoreModel->getDistinctComponents( $entity_type, $report_type, $org_id );
    }
    
    private function retrieveAttributeFromResult( $arr, $attr )
    {
    	$res = array();
    	foreach ( $arr as $i => $row )
    		$res[] =  $row[$attr];
    	
    	return $res;
    }
    
    public function getTillDetails( $tills_array, $params )
    {
    	$till_diagnostics_data = $this->StoreModel->till_diagnostics( $tills_array, $params['org_id'], $params );
    	$thin_clients_list = $this->StoreModel->check_thin_clients( $tills_array, $params['org_id'] ); 
    	
    	$td_ids = $this->retrieveAttributeFromResult( $till_diagnostics_data, 'id' );
    	
    	if ( strtolower( $params['report_type'] ) != "bulk_upload" )
    		$td_sync_report_data = $this->StoreModel->td_sync_status( $td_ids, $params['org_id'] );
    	
    	if ( ! in_array( strtolower($params['report_type']), array("full_sync", "delta_sync") ) )
    		$td_bulk_upload_data = $this->StoreModel->td_bulk_upload( $td_ids, $params['org_id'] );
    	
    	$till_ids = $this->retrieveAttributeFromResult( $till_diagnostics_data, 'till_id' );
    	
    	$tills_to_strs_map = $this->StoreModel->getChildToParentMapping( $till_ids, 'STORE', $params['org_id'] );	// array( 'till_id' => 'parent_store_id' )
    	
    	$parent_strs = array();			// container for store ids only
    	foreach ( $tills_to_strs_map as $i => $j )
    		$parent_strs[] = $j;
    	$strs_to_zones_mapping = $this->StoreModel->getChildToParentMapping( $parent_strs, 'ZONE', $params['org_id'] );	// array ( 'store_id' => 'parent_zone_id' )
    	$parent_zones = array();		// container for zone ids only
    	foreach ( $strs_to_zones_mapping as $m => $n )
    		$parent_zones[] = $n;
    	
    	// Fetch the code and name of entities (tills, stores, zones) by their ids 
    	$tills_info = $this->StoreModel->entityInfo( $till_ids, $params['org_id'] );
    	$stores_info = $this->StoreModel->entityInfo( $parent_strs, $params['org_id'] );
    	$zones_info = $this->StoreModel->entityInfo( $parent_zones, $params['org_id']);
    	
    	$result = array();			// array of all till details
    	foreach ( $till_diagnostics_data as $row )
    	{
    		$temp = array();		// array containing one till details at a time
    		$temp['id'] = $row['till_id'];
    		
    		$till_id = $temp['id'];
    		$temp['code'] = $tills_info[$till_id]['code'];
    		$temp['name'] = $tills_info[$till_id]['name'];
    		$temp['org_id'] = $row['org_id'];
    		
    		$parent_store_id = $tills_to_strs_map[$till_id];
    		$temp['store_code'] = $stores_info[$parent_store_id]['code'];
    		$temp['store_name'] = $stores_info[$parent_store_id]['name'];
    		
    		$parent_zone_id = $strs_to_zones_mapping[$parent_store_id];
    		$temp['zone_code'] = $zones_info[$parent_zone_id]['code'];
    		$temp['zone_name'] = $zones_info[$parent_zone_id]['name'];
    		
    		$temp['last_login'] = $row['last_login'];
    		
    		$cv['current_till_version'] = $row['current_till_version'];
    		$cv['available_till_version'] = $row['available_till_version'];
    		if ( $cv['available_till_version'] == $cv['current_till_version'] )
    			$cv['version_upto_date'] = 1;
    		else 
    			$cv['version_upto_date'] = 0;
    		$temp['client_version'] = $cv;
    		
    		$temp['is_thin_client'] = 0;
    		foreach ( $thin_clients_list as $item )
    			if ( $temp['id'] == $item['child_entity_id'] )
    			{
    				$temp['is_thin_client'] = 1;
    				break;
    			}
    		
    		foreach ( $td_sync_report_data as $each_sync_rep )
    		{
    			if ( ( $each_sync_rep['td_fkey'] == $row['id'] ) && ( $each_sync_rep['org_id'] == $row['org_id'] ) )
    			{
    				if ( ( $params['report_type'] != "delta_sync" ) && ( $each_sync_rep['is_full_sync'] == 1 || $each_sync_rep['is_full_sync'] == true ) )
    				{
    					$temp['full_sync_status']['component'][] = array(	'type' => $each_sync_rep['sync_type'],
    																		'status' => $each_sync_rep['sync_status']
    																	);
    				}
    				else if ( $params['report_type'] != "full_sync" )
    				{
    					$temp['delta_sync_status']['component'][] = array(	'type' => $each_sync_rep['sync_type'],
    																		'status' => $each_sync_rep['sync_status']
    																	);
    				}
    			}
    		}
    		
    		foreach ( $td_bulk_upload_data as $each_bulk_up )
    		{
    			if ( ( $each_bulk_up['td_fkey'] == $row['id'] ) /* && ( $each_bulk_up['org_id'] == $row['org_id'] ) */ )
    			{
    				$temp['bulk_upload_status']['component'][] = array(	'type' => $each_bulk_up['upload_type'],
    																	'status' => $each_bulk_up['upload_status']
    																); 
    			}
    		}
    		
    		$result[] = $temp;
    	}
    	
    	return $result;
    }
    
    public function getStoreServerDetails( $ss_array, $params )
    {
    	$store_server_health_data = $this->StoreModel->store_server_health( $ss_array, $params['org_id'], $params );
    	
    	$ssh_ids = $this->retrieveAttributeFromResult( $store_server_health_data, 'id' );
    	
    	if ( strtolower( $params['report_type'] ) != "bulk_upload" )
    		$ssh_sync_log_data = $this->StoreModel->ssh_sync_log( $ssh_ids, $params['org_id'] );
    	
    	if ( ! in_array( strtolower($params['report_type']), array("full_sync", "delta_sync") ) )
    		$ssh_bulk_upload_data = $this->StoreModel->ssh_bulk_upload( $ssh_ids, $params['org_id'] );
    	
    	$store_server_ids = $this->retrieveAttributeFromResult( $store_server_health_data, 'ss_id');
    	
    	$str_server_to_store_map = $this->StoreModel->getChildToParentMapping( $store_server_ids, 'STORE', $params['org_id'] );
    	
    	$parent_stores = array();
    	foreach ( $str_server_to_store_map as $i => $j )
    		$parent_stores[] = $j;
    	
    	$stores_to_zones_map = $this->StoreModel->getChildToParentMapping( $parent_stores, 'ZONE', $params['org_id'] );
    	$parent_zones = array();
    	foreach ( $stores_to_zones_map as $m => $n )
    		$parent_zones[] = $n;
    	
    	// Fetch the code and name of entities ( store_servers, stores, zones ) by their respective ids
    	$store_servers_info = $this->StoreModel->entityInfo( $store_server_ids, $params['org_id'] );
    	$stores_info = $this->StoreModel->entityInfo( $parent_stores, $params['org_id'] );
        $zones_info = $this->StoreModel->entityInfo( $parent_zones, $params['org_id'] );
    	
    	$result = array();
    	foreach ( $store_server_health_data as $row )
    	{
    		$temp = array();
    		$temp['id'] = $row['ss_id'];
    		
    		$ss_id = $temp['id'];
    		$temp['code'] = $store_servers_info[$ss_id]['code'];
    		$temp['name'] = $store_servers_info[$ss_id]['name'];
    		$temp['org_id'] = $row['org_id'];
    		
    		$parent_store_id = $str_server_to_store_map[$ss_id];
    		$temp['store_code'] = $stores_info[$parent_store_id]['code'];
    		$temp['store_name'] = $stores_info[$parent_store_id]['name'];
    		
    		$parent_zone_id = $stores_to_zones_map[$parent_store_id];
    		$temp['zone_code'] = $zones_info[$parent_zone_id]['code'];
    		$temp['zone_name'] = $zones_info[$parent_zone_id]['name']; 
    		
    		$temp['last_login'] = $row['last_login'];
    		$temp['last_transaction'] = $row['last_transaction'];
    		
    		$cv['current_store_server_version'] = $row['current_binary_version'];
    		$cv['available_store_server_version'] = $row['available_binary_version'];
    		if ( $cv['current_store_server_version'] == $cv['available_store_server_version'] )
    			$cv['version_upto_date'] = 1;
    		else 
    			$cv['version_upto_date'] = 0;
    		$temp['client_version'] = $cv;
    		
    		$temp['store_center_uptime'] = $row['up_time'];
    		
    		foreach ( $ssh_sync_log_data as $each_sync_log )
    		{
    			if ( ( $each_sync_log['ssh_fkey'] == $row['id'] ) && ( $each_sync_log['org_id'] == $row['org_id'] ) )
    			{
    				if ( ( $params['report_type'] != "delta_sync" ) && ( $each_sync_log['is_full_sync'] == 1 || $each_sync_log['is_full_sync'] == true ) )
    				{
    					$temp['full_sync_status']['component'][] = array(	'type' => $each_sync_log['sync_type'],
    																		'status' => $each_sync_log['sync_status']
    																	);
    				}
    				else if ( $params['report_type'] != "full_sync" )
    				{
    					$temp['delta_sync_status']['component'][] = array(	'type' => $each_sync_log['sync_type'],
    																		'status' => $each_sync_log['sync_status']
    																	);
    				}
    			}
    		}
    		
    		foreach ( $ssh_bulk_upload_data as $each_bulk_upload )
    		{
    			if ( ( $each_bulk_upload['ssh_fkey'] == $row['id'] ) && ( $each_bulk_upload['org_id'] == $row['org_id'] ) )
    			{
    				$temp['bulk_upload_status']['component'][] = array(	'type' => $each_bulk_upload['upload_type'],
    																	'status' => $each_bulk_upload['status']
    																);
    			}
    		}
    		$result[] = $temp;
    	}
    	
    	return $result;
    }
    
    /* p_change ends */

    /**
     * Returns complete info for the stores
     *
     * @param unknown_type $store_code
     */
    public function getInfoDetailsByStoreCode($store_code)
    {

        $this->logger->debug(' Getting Info Details For The Store by Store Code... ');

        $result = $this->StoreModel->getStoreInfoDetailsByStoreCode($this->org_id, $store_code);
        if (isset($result) && isset($result[0]['id'])) {
            if (!isset($result[0]['timezone_label']) && !isset($result[0]['timezone_offset'])) {
                $this->logger->debug("timezone not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreTimezoneFromHierarchy($result[0]['id']);
                $result[0]['timezone_label'] = $tz['timezone_label'];
                $result[0]['timezone_offset'] = $tz['timezone_offset'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['currency_symbol']) && !isset($result[0]['currency_code'])) {
                $this->logger->debug("Currency not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreCurrencyFromHierarchy($result[0]['id']);
                $result[0]['currency_symbol'] = $tz['currency_symbol'];
                $result[0]['currency_code'] = $tz['currency_code'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['language_code']) && !isset($result[0]['language_locale'])) {
                $this->logger->debug("Language not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreLanguageFromHierarchy($result[0]['id']);
                $result[0]['language_code'] = $tz['language_code'];
                $result[0]['language_locale'] = $tz['language_locale'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['country_name']) && !isset($result[0]['country_code'])) {
                $this->logger->debug("country not available for this store. Returning org country");
                $oc = new ApiOrganizationController();
                $tz = $oc->getOrgDefaultCountry();
                $result[0]['country_name'] = $tz['country_name'];
                $result[0]['country_code'] = $tz['country_code'];
                //TODO : add source info from where this tz is coming
            }
        }
        return $result;
    }

    /**
     * Returns complete info for the stores
     *
     * @param unknown_type $external_id
     */
    public function getInfoDetailsByExternalId($external_id)
    {

        $this->logger->debug(' Getting Info Details For The Store By External Id... ');

        $result = $this->StoreModel->getStoreInfoDetailsByExternalId($this->org_id, $external_id);
        if (isset($result) && isset($result[0]['id'])) {
            if (!isset($result[0]['timezone_label']) && !isset($result[0]['timezone_offset'])) {
                $this->logger->debug("timezone not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreTimezoneFromHierarchy($result[0]['id']);
                $result[0]['timezone_label'] = $tz['timezone_label'];
                $result[0]['timezone_offset'] = $tz['timezone_offset'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['currency_symbol']) && !isset($result[0]['currency_code'])) {
                $this->logger->debug("Currency not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreCurrencyFromHierarchy($result[0]['id']);
                $result[0]['currency_symbol'] = $tz['currency_symbol'];
                $result[0]['currency_code'] = $tz['currency_code'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['language_code']) && !isset($result[0]['language_locale'])) {
                $this->logger->debug("Language not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->getStoreLanguageFromHierarchy($result[0]['id']);
                $result[0]['language_code'] = $tz['language_code'];
                $result[0]['language_locale'] = $tz['language_locale'];
                //TODO : add source info from where this tz is coming
            }

            if (!isset($result[0]['country_name']) && !isset($result[0]['country_code'])) {
                $this->logger->debug("country not available for this store. Returning org country");
                $oc = new ApiOrganizationController();
                $tz = $oc->getOrgDefaultCountry();
                $result[0]['country_name'] = $tz['country_name'];
                $result[0]['country_code'] = $tz['country_code'];
                //TODO : add source info from where this tz is coming
            }
        }
        return $result;
    }

    /**
     * Returns the store as option for using in select box
     *
     * @return array( name => store_id )
     */
    public function getStoresAsOptions()
    {

        return $this->getEntityAsOptions();
    }

    /**
     * Returns till ids
     *
     * @return array( till_ids )
     */
    public function getStoreTerminalsByStoreId($store_id)
    {

        return $this->getChildrenEntityByType($store_id, 'TILL');
    }

    /**
     * Returns store server ids
     *
     * @return array( sttr_serever_ids )
     */
    public function getStoreServerByStoreId($store_id)
    {
        return $this->getChildrenEntityByType($store_id, 'STR_SERVER');
    }

    /**
     * loads the store details
     *
     * @param int $store_id
     */
    public function getStoreInfo($store_id)
    {

        $this->StoreModel->load($store_id);

        return $this->StoreModel->getHash();
    }

    /**
     * loads the store details
     *
     * @param int $store_id
     */
    public function getStoreTemplateDetails($store_id)
    {

        $this->StoreTemplateModel->load($store_id);

        return $this->StoreTemplateModel->getHash();

    }

    /**
     * add the new store
     *
     * @param array $store_details
     *
     * CONTRACT
     * array(
     * 'external_id' => value,
     * 'external_id_1' => value,
     * 'external_id_2' => value,
     * )
     */
    public function updateExternalIds($store_id, array $store_details)
    {

        extract($store_details);

        try {

            $this->isExternalIdUsed($store_id, $external_id);
            $this->StoreModel->load($store_id);
            $this->setExternalIdsForStore($store_details);
            $id = $this->StoreModel->update($store_id);

            if ($id)
                return 'SUCCESS';

        } catch (Exception $e) {

            return $e->getMessage();
        }

    }

    /**
     * add the new store
     *
     * @param array $store_details
     *
     * CONTRACT
     * array(
     * 'mobile' => value,
     * 'land_line' => value,
     * 'email' => value,
     * 'external_id' => value,
     * 'external_id_1' => value,
     * 'external_id_2' => value,
     * 'city' => value,
     * 'area' => value
     * )
     */
    public function updateStoreInfo($store_id, array $store_details)
    {

        extract($store_details);

        try {

            $status = true; //Util::checkMobileNumber( $mobile );

            if (!$status)
                throw new Exception('Mobile Is Number Not Valid');

            $this->isMobileTaken($mobile, $store_id);
            $this->isEmailTaken($email, $store_id);

            $this->StoreModel->load($store_id);
            $this->setStoreDetails($store_details);
            $id = $this->StoreModel->update($store_id);

            if ($id)
                return 'SUCCESS';

        } catch (Exception $e) {

            return $e->getMessage();
        }
    }

    /**
     *
     * @param $mobile
     * @param $store_id
     * Verifies If the mobile is bieng used by some other
     * user or not.
     *
     * If so throws exception
     */

    private function isMobileTaken($mobile, $store_id)
    {

        $this->logger->info('Mobile was valid now checking availibility');

        $is_taken = $this->StoreModel->isMobileTaken($mobile, $store_id);

        if ($is_taken) {

            $this->logger->debug('Exception thrown mobile is not availbale');
            throw new Exception('Mobile Number Is Already Taken By Some Other User');
        }
    }

    /**
     *
     * @param $email
     * @param $store_id
     * Verifies If the mobile is bieng used by some other
     * user or not.
     *
     * If so throws exception
     */

    private function isEmailTaken($email, $store_id)
    {

        $this->logger->info('Email was valid now checking availibility');

        $is_taken = $this->StoreModel->isEmailTaken($email, $store_id);

        if ($is_taken) {

            $this->logger->debug('Exception thrown email is not availbale');
            throw new Exception('Email Address Is Already Taken By Some Other User');
        }
    }

    /**
     * add the store lat / long
     *
     * @param array $store_details
     *
     * CONTRACT
     * array(
     * 'lat' => value,
     * 'long' => value,
     * )
     */
    public function updateStoreDemographicInfo($store_id, array $store_details)
    {

        extract($store_details);

        try {

            $this->StoreModel->load($store_id);
            $this->setStoreDemographicDetails($store_details);
            $id = $this->StoreModel->update($store_id);

            if ($id) return 'SUCCESS';

        } catch (Exception $e) {

            return $e->getMessage();
        }

        return 'FAILURE';
    }

    /**
     * @param array $store_details
     *
     * CONTRACT
     * array(
     * 'lat' => value,
     * 'long' => value,
     * )
     */
    private function setStoreDemographicDetails(array $store_details)
    {

        extract($store_details);

        $this->StoreModel->setLat($lat);
        $this->StoreModel->setLong($long);
        $this->StoreModel->setLastUpdatedBy($this->user_id);
        $this->StoreModel->setLastUpdatedOn(date('Y-m-d H:m:s'));
    }

    /**
     *
     * @param unknown_type $store_id
     */
    public function updateStoreTemplateId($store_id)
    {

        $this->StoreTemplateModel->load($store_id);
        $id = $this->StoreTemplateModel->getStoreId();
        if ($id) return;

        $this->StoreTemplateModel->setStoreId($store_id);
        $this->StoreTemplateModel->setLastUpdatedBy($this->user_id);
        $this->StoreTemplateModel->setLastUpdatedOn(date('Y-m-d H:m:s'));

        $this->StoreTemplateModel->insertWithId();
        return;
    }

    /**
     * @param array $zone_details
     *
     * CONTRACT
     * array(
     * 'mobile' => value,
     * 'land_line' => value,
     * 'email' => value,
     * 'external_id' => value,
     * 'external_id_1' => value,
     * 'external_id_2' => value,
     * 'city' => value,
     * 'area' => value
     * )
     */
    private function setStoreDetails(array $store_details)
    {

        extract($store_details);

        $this->StoreModel->setEmail($email);
        $this->StoreModel->setMobile($mobile);
        $this->StoreModel->setOrgId($this->org_id);
        $this->StoreModel->setLandLine($land_line);
        $this->StoreModel->setCityId($city);
        $this->StoreModel->setAreaId($area);
        $this->StoreModel->setLastUpdatedBy($this->user_id);
        $this->StoreModel->setLastUpdatedOn(date('Y-m-d H:m:s'));
    }

    /**
     * @param array $zone_details
     *
     * CONTRACT
     * array(
     * 'external_id' => value,
     * 'external_id_1' => value,
     * 'external_id_2' => value,
     * )
     */
    private function setExternalIdsForStore(array $store_details)
    {

        extract($store_details);

        $this->StoreModel->setOrgId($this->org_id);
        $this->StoreModel->setExternalId($external_id);
        $this->StoreModel->setExternalId1($external_id_1);
        $this->StoreModel->setExternalId2($external_id_2);
        $this->StoreModel->setLastUpdatedBy($this->user_id);
        $this->StoreModel->setLastUpdatedOn(date('Y-m-d H:m:s'));
    }

    /**
     * update SMS templates for the store
     * @param $template
     *
     * CONTRACT
     * array(
     * 's_name' => value,
     * 's_email' => value,
     * 's_mobile' => value,
     * 's_land_line' => value,
     * 's_add' => value,
     * 's_extra' => value
     * )
     */
    public function updateStoreTemplateForSMS($store_id, array $template)
    {

        extract($template);

        if (!Util::checkMobileNumber($s_mobile)) return 'Mobile Is Not A Valid Number';
        $this->StoreTemplateModel->load($store_id);

        $this->StoreTemplateModel->setSName($s_name);
        $this->StoreTemplateModel->setSExtra($s_extra);
        $this->StoreTemplateModel->setSEmail($s_email);
        $this->StoreTemplateModel->setSAdd($s_add);
        $this->StoreTemplateModel->setSMobile($s_mobile);
        $this->StoreTemplateModel->setSLandLine($s_land_line);
        $this->StoreTemplateModel->setLastUpdatedBy($this->user_id);
        $this->StoreTemplateModel->setLastUpdatedOn(date('Y-m-d H:m:s'));

        $status = $this->StoreTemplateModel->update($store_id);

        if ($status)
            return 'SUCCESS';

        return 'SOME ERROR OCCURED';

    }

    /**
     * update EMAIL templates for the store
     * @param $template
     *
     * CONTRACT
     * array(
     * 'e_name' => value,
     * 'e_email' => value,
     * 'e_mobile' => value,
     * 'e_land_line' => value,
     * 'e_add' => value,
     * 'e_extra' => value
     * )
     */
    public function updateStoreTemplateForEMAIL($store_id, array $template)
    {

        extract($template);

        if (!Util::checkMobileNumber($e_mobile)) return 'Mobile Is Not A Valid Number';

        $this->StoreTemplateModel->load($store_id);

        $this->StoreTemplateModel->setEName($e_name);
        $this->StoreTemplateModel->setEExtra($e_extra);
        $this->StoreTemplateModel->setEEmail($e_email);
        $this->StoreTemplateModel->setEAdd($e_add);
        $this->StoreTemplateModel->setEMobile($e_mobile);
        $this->StoreTemplateModel->setELandLine($e_land_line);
        $this->StoreTemplateModel->setLastUpdatedBy($this->user_id);
        $this->StoreTemplateModel->setLastUpdatedOn(date('Y-m-d H:m:s'));

        $status = $this->StoreTemplateModel->update($store_id);

        if ($status)
            return 'SUCCESS';

        return 'SOME ERROR OCCURED';
    }

    /**
     * returns parebt concept
     */
    public function getParentConcept($store_id)
    {

        return parent::getParentEntityByType($store_id, 'CONCEPT');
    }

    /**
     * returns parent zone
     */
    public function getParentZone($store_id)
    {

        return parent::getParentEntityByType($store_id, 'ZONE');
    }

    /**
     * @param unknown_type $org_id
     * @param array $entity_ids
     */
    public static function getUsersEmailMapByStoreIds($org_id, array $entity_ids)
    {

        return ApiEntityController::getUsersEmailMapByEntityIds($org_id, $entity_ids);
    }

    /**
     * Assigns the parent zone for the widget
     * @param unknown_type $parent_zone_id
     * @param unknown_type $child_store_id
     */
    public function addParentZone($parent_zone_id, $child_store_id)
    {

        try {

            $this->addParentEntity($parent_zone_id, 'ZONE', $child_store_id, 'STORE');
            $this->addParentZoneForTills($parent_zone_id, $child_store_id);
            return 'SUCCESS';
        } catch (Exception $e) {

            return $e->getMessage();
        }
    }

    /*
     * Associate Tills of a store
     */

    private function addParentZoneForTills($parent_zone_id, $store_id)
    {

        $store_tills = $this->getStoreTerminalsByStoreId($store_id);

        foreach ($store_tills as $key => $value) {

            $this->addParentEntity($parent_zone_id, 'ZONE', $value, 'TILL');

        }
    }

    /**
     * Assigns the parent concept for the widget
     * @param unknown_type $parent_concept_id
     * @param unknown_type $child_store_id
     */
    public function addParentConcept($parent_concept_id, $child_store_id)
    {

        try {

            $this->addParentEntity($parent_concept_id, 'CONCEPT', $child_store_id, 'STORE');

            return 'SUCCESS';
        } catch (Exception $e) {

            return $e->getMessage();
        }
    }

    /**
     * retuns the managers assigned to the store
     *
     * @param $store_id
     * @return array(
     *                    array( 'admin_users_id' => value ),
     *                    array( 'admin_users_id' => value )
     *            )
     */
    public function getUsersById($store_id)
    {

        $AdminUser = new AdminUserController();
        return $AdminUser->getUserIdsByEntityId($store_id);
    }

    /**
     * retuns the managers assigned to the zone
     *
     * @return array(
     *                    array( 'admin_users_id' => value ),
     *                    array( 'admin_users_id' => value )
     *            )
     */
    public function getUsersByType()
    {

        $AdminUser = new AdminUserController();
        return $AdminUser->getUserIdsByEntityType($this->entity_type);
    }

    /**
     * Returns SUCCESS/FAILURE
     *
     * add the manager to store
     *
     * @param $store_id
     * @param $manager_id
     */
    public function assignManager($store_id, $manager_ids)
    {

        $AdminUser = new AdminUserController();
        return $AdminUser->addUserToEntity($store_id, $manager_ids, 'STORE');
    }

    /**
     *
     * @param unknown_type $entity_type
     */
    public function getAll($include_inactive = false, $admin_type = false)
    {

        $this->logger->debug(" Inside store's get all ");

        //return parent::getAll( 'STORE' , $include_inactive , $admin_type );

        $child_result = parent::getAll('STORE', $include_inactive, $admin_type);

        $entity_ids = array();
        foreach ($child_result as $res)
            array_push($entity_ids, $res['id']);

        $parent_result = ApiOrgEntityModelExtension::getParentStoresWithTills(
            $this->org_id, $entity_ids,
            'ZONE', $include_inactive, $admin_type);

        $parent_concept = ApiOrgEntityModelExtension::getParentStoresWithTills(
            $this->org_id, $entity_ids,
            'CONCEPT', $include_inactive, $admin_type);

        $result = array();

        foreach ($child_result as $res) {

            if ($parent_result[$res['id']] && $parent_concept[$res['id']])
                $data = array_merge($res, $parent_result[$res['id']], $parent_concept[$res['id']]);
            else if ($parent_result[$res['id']])
                $data = array_merge($res, $parent_result[$res['id']],
                    array("parent_concept_code" => "Not Set",
                        "parent_concept_name" => "Not Set"));
            else if ($parent_concept[$res['id']])
                $data = array_merge($res, array("parent_zone_code" => "Not Set",
                    "parent_zone_name" => "Not Set"), $parent_concept[$res['id']]);
            else
                $data = array_merge($res, array("parent_zone_code" => "Not Set",
                    "parent_zone_name" => "Not Set",
                    "parent_concept_code" => "Not Set",
                    "parent_concept_name" => "Not Set"));

            if ($data)
                array_push($result, $data);
        }

        return $result;
    }

    /**
     * get all the inactive zones for particular organization
     */
    public function getAllInActive()
    {

        //return parent::getAllInActive( 'STORE' );

        $child_result = parent::getAllInActive('STORE');

        $entity_ids = array();
        foreach ($child_result as $res)
            array_push($entity_ids, $res['id']);

        $parent_result = ApiOrgEntityModelExtension::getParentStoresWithTills(
            $this->org_id, $entity_ids,
            'ZONE', false, true);

        $parent_concept = ApiOrgEntityModelExtension::getParentStoresWithTills(
            $this->org_id, $entity_ids,
            'CONCEPT', false, true);

        $result = array();

        foreach ($child_result as $res) {

            if ($parent_result[$res['id']] && $parent_concept[$res['id']])
                $data = array_merge($res, $parent_result[$res['id']], $parent_concept[$res['id']]);
            else if ($parent_result[$res['id']])
                $data = array_merge($res, $parent_result[$res['id']],
                    array("parent_concept_code" => "Not Set",
                        "parent_concept_name" => "Not Set"));
            else if ($parent_concept[$res['id']])
                $data = array_merge($res, array("parent_zone_code" => "Not Set",
                    "parent_zone_name" => "Not Set"), $parent_concept[$res['id']]);
            else
                $data = array_merge($res, array("parent_zone_code" => "Not Set",
                    "parent_zone_name" => "Not Set",
                    "parent_concept_code" => "Not Set",
                    "parent_concept_name" => "Not Set"));

            if ($data)
                array_push($result, $data);
        }

        return $result;
    }

    /**
     * set status updation for store
     * @param unknown_type $entity_id
     * @param unknown_type $status
     */
    public function setActiveStatus($entity_id, $status)
    {

        $this->logger->debug("Set active status update : " . $entity_id);

        $result = parent::setActiveStatus($entity_id, $status);

        if ($result == 'SUCCESS' && $status != 1) {

            $child_tills = $this->getChildrenEntityByType($entity_id, 'TILL');
            if (count($child_tills) > 0) {
                $this->StoreModel->updateChildStatus('TILL', $child_tills, $this->org_id, $status);
            }
        }
        return $result;
    }

    /**
     *
     * @param $store_ids
     * @param $include_inactive
     */
    public function getByIds($store_ids, $include_inactive = false)
    {
        $this->logger->debug("Fetching stores by ids " . sizeof($store_ids));
        return parent::getByIds($store_ids, 'STORE', $include_inactive);
    }

    /**
     * Returns the managers for the entities
     * @param unknown_type $entity_id
     */
    public function getManagers($entity_id)
    {

        return parent::getManagers($entity_id);
    }

    /**
     * checks if external id is already taken by other store
     *
     * @param $store_id
     * @param $external_id
     */
    public function isExternalIdUsed($store_id, $external_id)
    {

        if (!$external_id)
            return;

        $used = $this->StoreModel->isExternalIdUsed($this->org_id, $store_id, $external_id);

        if ($used)
            throw new Exception('External Id Has Been Alotted To Other Store');
    }

    /**
     * Add/Update The New Till
     * @param unknown_type $till_id
     */
    public function updateShopbookStore($store_id, $contact_details = array())
    {

        $tills = $this->getChildrenEntityByType($store_id, 'TILL');

        $tills = Util::joinForSql($tills);

        $this->logger->debug('Adding / Updating The Stores For Store Till For User_id : ' . $store_id . ' : org_id : ' . $org_id . ' Tills: ' . $tills);
        $status = $this->StoreModel->addShopbookStoreForTillUser($store_id, $tills, $this->org_id, $contact_details);
        $this->logger->debug('Added / Updated The Stores For Store Till For User_id : ' . $store_id . ' : org_id : ' . $org_id . ' Tills: ' . $tills);
    }

    /**
     * upload Store
     * add org entities and entry in stores table
     * @param unknown_type $params
     * @param unknown_type $filename
     */
    public function uploadStore($params, $filename)
    {

        $file_batch_size = 100; //$params['file_batch_size'];

        //Use a low memory spreadsheet reader
        //create the mapping
        $col_mapping = array();

        //Add a field for each of them
        $col_mapping['external_id'] = 0;
        $col_mapping['name'] = 1;
        $col_mapping['code'] = 2;

        //create the settings
        $settings = array();
        $settings['header_rows_ignore'] = 0;
        $spreadsheet = new Spreadsheet();
        $response = array();
        $batch_response = array();

        //read the data from the uploaded file in batches
        if ($params['confirm'] != false) {

            $org_id = $this->org_id;

            $count = 0;

            while (($processed_data = $spreadsheet->LoadCSVLowMemory($filename, $file_batch_size, false, $col_mapping, $settings)) != false) {

                $rowcount = 0;
                $batch_data = array();
                $org_entities_batch = array();
                $bulk_org_entities = array();
                $external_ids = array();
                $batch_template_data = array();

                foreach ($processed_data as &$row) {

                    $rowcount++;

                    $external_id = $row['external_id'];
                    $code = $row['code'];
                    $name = $row['name'];

                    $this->validateEntityCode($code);
                    $this->validateEntityName($name);

                    $org_entities = "( '$this->org_id', 'STORE', '$code','$name' )";
                    array_push($bulk_org_entities, $org_entities);

                    array_push($external_ids, $external_id);


                }

                unset($bulk_org_entities[0]);
                unset($external_ids[0]);

                $insert_users = implode(',', $bulk_org_entities);

                // inserts in org entities
                $success = $this->StoreModel->addOrgEntities($insert_users);

                $count = count($bulk_org_entities);

                foreach ($external_ids as $external_id) {

                    if ($count && $success) {


                        $insert_users = "( '$success', '$this->org_id', '1','$external_id', $this->user_id, NOW() )";
                        array_push($batch_data, $insert_users);


                        $template_data = "( '$success', $this->org_id, '$this->user_id' , NOW() )";
                        array_push($batch_template_data, $template_data);

                        $this->updateShopbookStore($success);

                        $success++;
                        $count--;
                    }
                }

                $batch_info = implode(',', $batch_data);
                $template_info = implode(',', $batch_template_data);

                // insert in stores and store_template entities id
                $this->StoreModel->UpdateStoreWithEntityIds($batch_info);
                $this->StoreModel->updateStoreTemplate($template_info);
            }

            return $success;

        }

    }

    /**
     * uploadStoreDetails: Upload store details
     * update stores and stores_tempalate
     * @param $params
     * @param $filename
     */
    public function uploadStoreDetails($params, $filename)
    {

        $file_batch_size = 100; //$params['file_batch_size'];

        //Use a low memory spreadsheet reader
        //create the mapping
        $col_mapping = array();

        //Add a field for each of them
        $col_mapping['external_id'] = 0;
        $col_mapping['city_id'] = 1;
        $col_mapping['lat'] = 2;
        $col_mapping['long'] = 3;
        $col_mapping['zone_code'] = 4;
        $col_mapping['concept_code'] = 5;
        $col_mapping['manager_emails'] = 6;
        $col_mapping['mobile'] = 7;
        $col_mapping['timezone'] = 8;
        $col_mapping['state_id'] = 9;

        //create the settings
        $settings['header_rows_ignore'] = 0;
        $settings = array();
        $spreadsheet = new Spreadsheet();
        $response = array();
        $batch_response = array();

        //read the data from the uploaded file in batches
        if ($params['confirm'] != false) {

            $org_id = $this->org_id;

            $count = 0;

            while (($processed_data = $spreadsheet->LoadCSVLowMemory($filename, $file_batch_size, false, $col_mapping, $settings)) != false) {

                $rowcount = 0;
                $batch_data = array();
                $org_entities_batch = array();
                $bulk_store_details = array();
                $external_ids = array();
                $batch_template_data = array();
                $parent_zone_details = array();
                $parent_concept_details = array();

                unset($processed_data[0]);

                Util::removeCacheFile($this->org_id, 'store', 'bulkCreate');
                foreach ($processed_data as &$row) {

                    $rowcount++;

                    $external_id = $row['external_id'];
                    $city_id = $row['city_id'];
                    $state_id = $row['state_id'];
                    $time_zone = $row['timezone'];
                    $lat = $row['lat'];
                    $long = $row['long'];
                    $zone_code = $row['zone_code'];
                    $concept_code = $row['concept_code'];
                    $manager_email = $row['manager_emails'];
                    $mobile = $row['mobile'];

                    $this->logger->debug(" $zone_code.' chal_jana_boss  '.$concept_code ");

                    $insert_users = "('$rowcount', '$this->org_id','$city_id', '$state_id','$mobile', '$manager_email', '1', '$lat', '$long'
										, '$external_id', $this->user_id, NOW() )";

                    array_push($bulk_store_details, $insert_users);

                    try {
                        $status = Util::checkMobileNumber($mobile);
                    } catch (Exception $e) {
                        $row['status'] = 'Invalid mobile';
                    }

                    $zone = $this->getEntityIds($zone_code, 'ZONE');
                    $concept = $this->getEntityIds($concept_code, 'CONCEPT');

                    $store_id = $this->StoreModel->getIdbyExternalIds($external_id, $this->org_id);

                    try {
                        $this->isMobileTaken($mobile, $store_id);
                        $this->isEmailTaken($manager_email, $store_id);

                    } catch (Exception $e) {

                        $row['status'] = "$e->getMessage()";
                    }

                    $zone_details = "('$this->org_id', '$zone', 'ZONE', '$store_id','STORE' )";
                    $concept_details = "('$this->org_id', '$concept', 'CONCEPT', '$store_id','STORE' )";

                    array_push($parent_zone_details, $zone_details);
                    array_push($parent_concept_details, $concept_details);

                    array_push($external_ids, $external_id);

                }


                $store_details = implode(',', $bulk_store_details);

                $parent_zones = implode(',', $parent_zone_details);
                $parent_concepts = implode(',', $parent_concept_details);

                //Store details add in
                $success = $this->StoreModel->addStoreDetails($store_details, $external_ids);

                $sucess_zone = $this->addBulkParentEntity($parent_zones);

                $sucess_concept = $this->addBulkParentEntity($parent_concepts);
            }

            return ($success || $sucess_zone || $sucess_concept);

        }

    }

    /**
     *
     * @param $params updating the details of tills
     * @param $filename
     */
    public function uploadStoreTills($params, $filename)
    {

        $file_batch_size = 100; //$params['file_batch_size'];

        //Use a low memory spreadsheet reader
        //create the mapping
        $col_mapping = array();

        //Add a field for each of them
        $col_mapping['external_id'] = 0;
        $col_mapping['username'] = 1;
        $col_mapping['password'] = 2;
        $col_mapping['code'] = 3;
        $col_mapping['name'] = 4;


        //create the settings
        $settings['header_rows_ignore'] = 0;
        $settings = array();
        $spreadsheet = new Spreadsheet();
        $response = array();
        $batch_response = array();

        //read the data from the uploaded file in batches
        if ($params['confirm'] != false) {

            $org_id = $this->org_id;

            $count = 0;
            // Get country details dump before proceed for checking

            while (($processed_data = $spreadsheet->LoadCSVLowMemory($filename, $file_batch_size, false, $col_mapping, $settings)) != false) {

                $rowcount = 0;
                $batch_data = array();
                $org_entities_batch = array();
                $bulk_store_details = array();
                $external_ids = array();
                $log_user_info = array();
                $bulk_org_entities = array();
                $parent_concept_details = array();
                $parent_zone_details = array();
                $storeids = array();
                $parent_store = array();
                $store_ids = array();
                $store_unit_details = array();

                unset($processed_data[0]);

                foreach ($processed_data as &$row) {

                    $rowcount++;

                    $external_id = trim($row['external_id']);
                    $username = trim($row['username']);
                    $password = md5($row['password']);
                    $code = trim($row['code']);
                    $name = trim($row['name']);

                    $this->LoggableUserModelExt->validateUserName($username);

                    $org_entities = "( '$this->org_id', 'TILL', '$code','$name' )";

                    $this->logger->debug('store tills update');

                    array_push($bulk_org_entities, $org_entities);

                    $store_id = $this->StoreModel->getIdbyExternalIds($external_id, $this->org_id);

                    array_push($storeids, $store_id);


                    array_push($store_ids, $store_id);

                    array_push($external_ids, $external_id);

                }


                // till entities bulk array

                $org_entities = implode(',', $bulk_org_entities);

                //$log_user = implode( ',' , $log_user_info);

                $success = $this->StoreModel->addOrgEntities($org_entities);

                //$log_id = $this->LoggableUserModelExt->addBulkLoggableUser( $log_user );


                foreach ($processed_data as &$row) {

                    $external_id = trim($row['external_id']);
                    $code = trim($row['code']);
                    $name = trim($row['name']);
                    $username = trim($row['username']);
                    $password = md5($row['password']);


                    $till_id = $this->getEntityIds($code, 'TILL');

                    $store_id = $this->StoreModel->getIdbyExternalIds($external_id, $this->org_id);

                    $get_parent_zone = $this->getParentsById($store_id, 'ZONE', $this->org_id);

                    $get_parent_concept = $this->getParentsById($store_id, 'CONCEPT', $this->org_id);


                    // for each parent zone of store add relation with till
                    $this->logger->debug('zones for the store is' . print_r($get_parent_zone, true));

                    foreach ($get_parent_zone as $zone) {

                        $zone_details = "('$this->org_id', '$zone', 'ZONE', '$till_id','TILL' )";
                        array_push($parent_zone_details, $zone_details);
                    }

                    $store_details = "('$this->org_id', '$store_id', 'STORE', '$till_id','TILL' )";
                    array_push($parent_store, $store_details);

                    $log_user_details = "('$this->org_id', 'TILL', '$till_id' ,'$username', '$password','1', '$this->user_id' , NOW() )";
                    array_push($log_user_info, $log_user_details);

                    $store_unit = "('$till_id', '$this->org_id', '$store_id', '$store_id', 'TILL', '1', $this->user_id, NOW() )";

                    array_push($store_unit_details, $store_unit);

                    $this->logger->debug('concepts for the store is' . print_r($get_parent_concept, true));

                    $this->StoreTillController->updateDeploymentFiles($till_id);
                    $this->StoreTillController->addShopbookStore($till_id);

                    foreach ($get_parent_concept as $concept) {

                        $concept_details = "('$this->org_id', '$concept', 'CONCEPT', '$till_id','TILL' )";
                        array_push($parent_concept_details, $concept_details);
                    }

                }

                $log_user = implode(',', $log_user_info);
                $parent_zones = implode(',', $parent_zone_details);
                $parent_concepts = implode(',', $parent_concept_details);
                $parent_store = implode(',', $parent_store);
                $store_unit_details = implode(',', $store_unit_details);

                $log_id = $this->LoggableUserModelExt->addBulkLoggableUser($log_user);
                $success_tills = $this->StoreModel->addBulkStoreUnits($store_unit_details);
                $sucess_store = $this->addBulkParentEntity($parent_store);
                $sucess_zone = $this->addBulkParentEntity($parent_zones);
                $sucess_concept = $this->addBulkParentEntity($parent_concepts);
            }

            return ($success || $sucess_zone || $sucess_concept);

        }

    }

    function getAdminType($store_id)
    {
        $this->OrgEntityModelExtension->load($store_id);
        return $this->OrgEntityModelExtension->getAdminType();
    }

    /**
     *
     * Take customer's feedback and store into check_in_feedback table.
     * @param unknown_type $params
     */
    public function addStoreFeedback($user_id, $store_id)
    {

        $query_data = "('$this->org_id'" . ",'" . $user_id . "','" . $store_id . "','" . date('Y-m-d h:m:s') . "')";

        return $this->StoreModel->storeCustomerFeedback($query_data);

    }

    public function getStoreFeedbackCount($store_id, $type = 'TODAY')
    {

        $today = date('Y-m-d');

        if ($type == 'TODAY')
            $params = "`last_updated_on` BETWEEN '" . date('Y-m-d 00:00:00',
                    strtotime('- 1 day', strtotime($today))) . "'
										 AND '" . date('Y-m-d 23:59:59', strtotime($today)) . "'";

        if ($type == 'WEEKLY')
            $params = "`last_updated_on` BETWEEN '" . date('Y-m-d 00:00:00',
                    strtotime('- 7 day', strtotime($today))) . "'
										 AND '" . date('Y-m-d 23:59:59', strtotime($today)) . "'";

        if ($type == 'MONTH')
            $params = "`last_updated_on` BETWEEN '" . date('Y-m-d 00:00:00',
                    strtotime('- 1 month', strtotime($today))) . "'
										 AND '" . date('Y-m-d 23:59:59', strtotime($today)) . "'";

        return $this->StoreModel->getStoreFeedbackCount($store_id, $params, $this->org_id);
    }

    public function getBaseStoreId()
    {
        $this->logger->debug("fetching BaseStoreInfo for org_id: $this->org_id, till_id: $this->user_id");

        $this->StoreTillController->load($this->user_id);

        $parent_store = $this->StoreTillController->getParentStore();

        return $parent_store[0];
    }

    /**
     *
     * It will update the timezone of all the stores under this organization
     * @param int $time_zone_id
     */
    public function updateStoresTimezone($time_zone_id)
    {

        $this->logger->debug("Start of timezone updation org_id: $this->org_id, entity type: $this->entity_type");

        return $this->StoreModel->updateTimeZonesByEntityType($this->entity_type, $this->org_id, $time_zone_id);
    }

    /**
     * Store Template Values
     * @param unknown_type $store_till_id
     * @param unknown_type $org_id
     */
    public function getStoreTemplateJoinDetails($store_till_id, $org_id)
    {

        return $this->StoreModel->getStoreTemplateJoinDetails($store_till_id, $org_id);
    }

    /**
     * uploads log file from Store
     */
    public function addStoreLogFileDetails($filename, $params)
    {
        //verify file size
        if (!($params['logfile_size'] == filesize($filename))) {
            $this->logger->error("File size in request header : " . $params['logfile_size'] . " File size uploaded : " . filesize($filename));
            unlink($filename);
            throw new Exception("ERR_CLIENTLOG_SIZE_MISMATCH");
        }

        //verify file SHA1
        if (!($params['logfile_sha1'] == sha1_file($filename))) {
            $this->logger->error("sha1 of file in request header : " . $params['logfile_sha1'] . " sha1 of file uploaded : " . sha1_file($filename));
            $this->logger->error("filename : " . $filename);
            unlink($filename);
            throw new Exception("ERR_CLIENTLOG_SIGN_MISMATCH");
        }

        //if its file for store server diagnostics
        if ($params['file_type'] == 'ssdiagnostics') {
            if (strcmp(strtolower($params['upload_type']), 'manual') != 0)
                if (!$this->canUploadSSDiagLogfile())
                    throw new Exception("ERR_SS_DIAG_LOG_NOT_UPLOADABLE");
        }

        $fsObj = new FileServiceManager();
        $ret = $fsObj->upload(STORELOG_S3_BUCKET, basename($filename), $filename);
        if (!$ret) {
            $this->logger->error("Failed to upload file to fileservice : " . $params);
            unlink($filename);
            throw new Exception("ERR_CLIENTLOG_FILESERVICE_UPLOAD_FAIL");
        }
        $this->logger->debug("Log file uploaded successfully to file service : " . $filename);

        $upload_det = $fsObj->getFileDetails();
        $upload_handle = $upload_det['file_handle'];
        $this->logger->debug("Details of upload : " . implode(',', $upload_det));
        $params['file_upload_handle'] = $upload_handle;
        $id = $this->StoreModel->addStorePerf($params);
        if (!$id) {
            $this->logger->error("Failed to insert metadata for logfile in db : " . $params);
            unlink($filename);
            throw new Exception("ERR_CLIENTLOG_META_DBWRITE_FAIL");
        }
        $this->logger->debug("Log file metadata added to db : " . implode(',', $params));

        unlink($filename);
    }

    private function canUploadSSDiagLogfile()
    {
        $cm = new ConfigManager();
        return is_null($cm->getKeyForEntity( 'CONF_CLIENT_STORE_SERVER_DIAG_STATUS', $this->currentuser->user_id ) ) ?
            false : (bool)$cm->getKeyForEntity( 'CONF_CLIENT_STORE_SERVER_DIAG_STATUS', $this->currentuser->user_id ) ;
    }

    public function getLoyaltyReportForStore()
    {
        $org_id = $this->currentorg->org_id;
        $store_till_id = $this->currentuser->user_id;
        $loyaltyTrackerModelExt = new ApiLoyaltyTrackerModelExtension();

        $today = DateUtil::getMysqlDateTime(strtotime("today"));
        $yesterday = DateUtil::getMysqlDateTime(strtotime("today -1 day"));
        $two_days_ago = DateUtil::getMysqlDateTime(strtotime("today -2 day"));
        $first_day_of_week = DateUtil::getMysqlDateTime(strtotime("last sunday"));
        $first_day_of_month = DateUtil::getMysqlDateTime(strtotime(date('m/01/y')));

        $current_date_with_start_time = DateUtil::getCurrentDateWithStartTime();
        $current_date_with_end_time = DateUtil::getCurrentDateWithEndTime();

        $clauses = array(
            "$today" => " {{d}} >= '$current_date_with_start_time' " .
                "AND {{d}} <= '$current_date_with_end_time' ",
            "$yesterday" => "{{d}} >= '" . DateUtil::getDateByDays(true, 1, $current_date_with_start_time, "%Y-%m-%d %H:%M:%S") . "'" .
                "AND {{d}} <= '" . DateUtil::getDateByDays(true, 1, $current_date_with_end_time, "%Y-%m-%d %H:%M:%S") . "'",
            "$two_days_ago" => "{{d}} >= '" . DateUtil::getDateByDays(true, 2, $current_date_with_start_time, "%Y-%m-%d %H:%M:%S") . "'" .
                "AND {{d}} <= '" . DateUtil::getDateByDays(true, 2, $current_date_with_end_time, "%Y-%m-%d %H:%M:%S") . "'",
            'this_week' => "{{d}} >= '$first_day_of_week' AND {{d}} >= '$first_day_of_month'",
            'this_month' => " {{d}} >= '$first_day_of_month'"
        );

        $loyalty_report = array();

        $today_report = $loyaltyTrackerModelExt->getLoyaltyDetailsForStore(
            $org_id, $store_till_id);
        if($today_report!=null && !empty($today_report)){
            $loyalty_report[] = array_merge(array("start_date" => $today,
            "end_date" => DateUtil::getDateWithEndTime($today)), $today_report);
        }else{
            $loyalty_report[] = array_merge(array("start_date" => $today,
            "end_date" => DateUtil::getDateWithEndTime($today),
                "customers"=>null,
                "transactions"=>null,
                "not_interested_transactions"=>null,
                "points_issued"=>null,
                "points_redeemed"=>null),
                    $today_report);
        }
        
        $yesterday_report = $loyaltyTrackerModelExt->getLoyaltyDetailsForStore(
            $org_id, $store_till_id, 1);
        
        if($yesterday_report!=null && !empty($yesterday_report)){
            $loyalty_report[] = array_merge(array("start_date" => $yesterday,
            "end_date" => DateUtil::getDateWithEndTime($yesterday)), $yesterday_report);
        }else{
            $loyalty_report[] = array_merge(array("start_date" => $yesterday,
            "end_date" => DateUtil::getDateWithEndTime($yesterday),
                "customers"=>null,
                "transactions"=>null,
                "not_interested_transactions"=>null,
                "points_issued"=>null,
                "points_redeemed"=>null),
                    $yesterday_report);
        }
        
        $two_days_ago_report = $loyaltyTrackerModelExt->getLoyaltyDetailsForStore(
            $org_id, $store_till_id, 2);
        
        if($two_days_ago_report!=null && !empty($two_days_ago_report)){
            $loyalty_report[] = array_merge(array("start_date" => $two_days_ago,
            "end_date" => DateUtil::getDateWithEndTime($two_days_ago)), $two_days_ago_report);
        }else{
            $loyalty_report[] = array_merge(array("start_date" => $two_days_ago,
            "end_date" => DateUtil::getDateWithEndTime($two_days_ago),
                "customers"=>null,
                "transactions"=>null,
                "not_interested_transactions"=>null,
                "points_issued"=>null,
                "points_redeemed"=>null),
                    $two_days_ago_report);
        }

        $current_week_report = $loyaltyTrackerModelExt->getLoyaltyDetailsForStoreForLastWeekOrMonth(
            $org_id, $store_till_id, false);
        
        $timestamp_week = DateUtil::deserializeFrom8601($first_day_of_week);
        $timestamp_month = DateUtil::deserializeFrom8601($first_day_of_month);
        
        $first_day_of_week_of_month = $timestamp_month > $timestamp_week ? $first_day_of_month : $first_day_of_week;
        
        if($current_week_report!=null && !empty($current_week_report)){
            $loyalty_report[] = array_merge(array("start_date" => $first_day_of_week_of_month,
            "end_date" => DateUtil::getDateWithEndTime($today)), $current_week_report);
        }else{
            $loyalty_report[] = array_merge(array("start_date" => $first_day_of_week_of_month,
            "end_date" => DateUtil::getDateWithEndTime($today),
                "customers"=>null,
                "transactions"=>null,
                "not_interested_transactions"=>null,
                "points_issued"=>null,
                "points_redeemed"=>null),
                    $current_week_report);
        }

        $current_month_report = $loyaltyTrackerModelExt->getLoyaltyDetailsForStoreForLastWeekOrMonth(
            $org_id, $store_till_id, true);
        if($current_week_report!=null && !empty($current_week_report)){
            $loyalty_report[] = array_merge(array("start_date" => $first_day_of_month,
            "end_date" => DateUtil::getDateWithEndTime($today)), $current_month_report);
        }else{
            $loyalty_report[] = array_merge(array("start_date" => $first_day_of_month,
            "end_date" => DateUtil::getDateWithEndTime($today),
                "customers"=>null,
                "transactions"=>null,
                "not_interested_transactions"=>null,
                "points_issued"=>null,
                "points_redeemed"=>null),
                    $current_month_report);
        }
        return $loyalty_report;
    }

    public function getRedemptionReportForStore($start_date = null, $end_date = null)
    {
        $org_id = $this->currentorg->org_id;
        $store_till_id = $this->currentuser->user_id;
        $loyaltyTrackerModelExt = new ApiLoyaltyTrackerModelExtension();
        $result = $loyaltyTrackerModelExt->getRedemptionDetailsForStore($org_id, $store_till_id, $start_date, $end_date);
        return $result;
    }

    public function getLoyaltyReportForStoreByDate($start_date, $end_date)
    {
        $org_id = $this->currentorg->org_id;
        $store_till_id = $this->currentuser->user_id;
        $loyaltyTrackerModelExt = new ApiLoyaltyTrackerModelExtension();

        $result = $loyaltyTrackerModelExt->getLoyaltyDetailsForStore($org_id, $store_till_id, 0, $start_date, $end_date);
        $result = array_merge(array("start_date" => DateUtil::getDateWithStartTime($start_date), "end_date" => DateUtil::getDateWithEndTime($end_date)), $result);
        return $result;
    }

    /**
     * This will save loyalty tracker information of store/report API
     * @param unknown_type $params
     */
    public function saveLoyaltyTrackerReportForStore($params)
    {
        $loyaltyTrackerModelExt = new ApiLoyaltyTrackerModelExtension();
        $loyaltyTrackerModelExt->setOrgId($this->currentorg->org_id);
        $loyaltyTrackerModelExt->setStoreId($this->currentuser->user_id);
        $loyaltyTrackerModelExt->setNumBills($params['total_transactions']);
        $date = $params['date'];
        $loyaltyTrackerModelExt->setTrackerDate($date);
        $loyaltyTrackerModelExt->setSales($params['total_sales']);
        $loyaltyTrackerModelExt->setFootfallCount($params['footfall_count']);
        $loyaltyTrackerModelExt->setCapturedRegularBills($params['transaction_count']['regular']);
        $loyaltyTrackerModelExt->setCapturedNotInterestedBills($params['transaction_count']['not_interested']);
        $loyaltyTrackerModelExt->setCapturedEnterLaterBills($params['transaction_count']['total_enter_later']);
        $loyaltyTrackerModelExt->setCapturedPendingEnterLaterBills($params['transaction_count']['enter_later_for_today']);

        $id = $loyaltyTrackerModelExt->insert();

        return $id;
    }

    public function getStoreAttributeLastModifiedDate()
    {
        $org_id = $this->currentorg->org_id;
        $store_id = $this->currentuser->user_id;
        return $this->StoreModel->getStoreAttributeLastModifiedDate($org_id, $store_id);
    }

    /**
     *
     */
    public function addStoreServerStats($ss_uptime, $ss_request_processed, $ss_os, $ss_os_platform,
                                        $ss_processor, $ss_system_ram, $ss_db_size, $ss_lan_speed,
                                        $ss_last_transaction_time, $ss_avg_mem, $ss_peak_mem, $ss_avg_cpu, $ss_peak_cpu,
                                        $ss_last_txn_to_svr, $ss_last_regn_to_svr, $ss_report_generation_time,
                                        $ss_last_login, $ss_last_fullsync, $ss_curr_version, $ss_available_version)
    {
        $store_id = $this->currentuser->user_id;
        $this->logger->debug("ss_uptime : $ss_uptime, ss_request_processed : $ss_request_processed, ss_os : $ss_os,
                            ss_os_platform : $ss_os_platform, ss_processor : $ss_processor, ss_system_ram : $ss_system_ram,
                            ss_db_size : $ss_db_size, ss_lan_speed : $ss_lan_speed, ss_last_transaction_time : $ss_last_transaction_time,
                            ss_avg_mem : $ss_avg_mem, ss_peak_mem : $ss_peak_mem, ss_avg_cpu : $ss_avg_cpu, ss_peak_cpu : $ss_peak_cpu,
                            store_id : $store_id, org_id : $this->org_id, ss_last_txn_to_svr : $ss_last_txn_to_svr,
                            ss_last_regn_to_svr : $ss_last_regn_to_svr, ss_report_generation_time : $ss_report_generation_time,
                            ss_last_login : $ss_last_login, ss_last_fullsync : $ss_last_fullsync,
                            ss_curr_version : $ss_curr_version, ss_available_version : $ss_available_version");

        if (empty($ss_last_login))
            $ss_last_login = 'NULL';

        if (empty($ss_last_fullsync))
            $ss_last_fullsync = 'NULL';

        if (empty($ss_curr_version))
            $ss_curr_version = 'NULL';

        if (empty($ss_available_version))
            $ss_available_version = 'NULL';

        if (empty($ss_uptime))
            $ss_uptime = 'NULL';

        if (empty($ss_request_processed))
            $ss_request_processed = 'NULL';

        if (empty($ss_os))
            $ss_os = '';

        if (empty($ss_os_platform))
            $ss_os_platform = '';

        if (empty($ss_processor))
            $ss_processor = 'NULL';

        if (empty($ss_system_ram))
            $ss_system_ram = 'NULL';

        if (empty($ss_db_size))
            $ss_db_size = 'NULL';

        if (empty($ss_lan_speed))
            $ss_lan_speed = 'NULL';

        if (empty($ss_last_transaction_time))
            $ss_last_transaction_time = 'NULL';

        if (empty($ss_last_txn_to_svr))
            $ss_last_txn_to_svr = 'NULL';

        if (empty($ss_last_regn_to_svr))
            $ss_last_regn_to_svr = 'NULL';

        if (empty($ss_avg_mem))
            $ss_avg_mem = 'NULL';

        if (empty($ss_peak_mem))
            $ss_peak_mem = 'NULL';

        if (empty($ss_avg_cpu))
            $ss_avg_cpu = 'NULL';

        if (empty($ss_peak_cpu))
            $ss_peak_cpu = 'NULL';

        if (empty($ss_report_generation_time))
            $ss_report_generation_time = 'NULL';

        return $this->StoreModel->insertStoreServPerf($store_id, $this->org_id, $ss_uptime, $ss_request_processed, $ss_os, $ss_os_platform,
            $ss_processor, $ss_system_ram, $ss_db_size, $ss_lan_speed, $ss_last_transaction_time, $ss_avg_mem,
            $ss_peak_mem, $ss_avg_cpu, $ss_peak_cpu, $ss_last_txn_to_svr, $ss_last_regn_to_svr, $ss_report_generation_time,
            $ss_last_login, $ss_last_fullsync, $ss_curr_version, $ss_available_version);
    }

    public function addStoreServerSyncLogs($sync_logs, $ss_health_fkey)
    {
        $sync_types = array('customers', 'custom_fields_data', 'voucher_series', 'vouchers', 'inventory_master',
            'customer_attributes', 'store_attributes', 'loyalty_tracker', 'fraud_users_list', 'task_metadata',
            'tasks', 'associates', 'reminders', 'comm_template', 'stores');
        $value_str = "";

        foreach ($sync_types as $sync_type) {
            if (isset($sync_logs[$sync_type])) {
                $sync_data = $sync_logs[$sync_type];
                $sync_type = strtoupper($sync_type);
                $sync_status = $sync_data['sync_status'];
                $last_full_sync_time = $sync_data['last_full_sync_time'];
                if (empty($last_full_sync_time))
                    $last_full_sync_time = 'NULL';

                $last_delta_sync_time = $sync_data['last_delta_sync_time'];
                if (empty($last_delta_sync_time))
                    $last_delta_sync_time = 'NULL';

                $read_time = $sync_data['read_time'];
                if (empty($read_time))
                    $read_time = 'NULL';

                $file_size = $sync_data['file_size'];
                if (empty($file_size))
                    $file_size = 'NULL';

                $unzipping_time = $sync_data['unzipping_time'];
                if (empty($unzipping_time))
                    $unzipping_time = 'NULL';

                $indexing_time = $sync_data['indexing_time'];
                if (empty($indexing_time))
                    $indexing_time = 'NULL';

                $request_id = $sync_data['request_id'];
                $full_delta = $sync_data['is_full_sync'];

                $avg_mem = $sync_data['memory']['avg_memory_usage'];
                if (empty($avg_mem))
                    $avg_mem = 'NULL';

                $peak_mem = $sync_data['memory']['peak_memory_usage'];
                if (empty($peak_mem))
                    $peak_mem = 'NULL';

                $avg_cpu = $sync_data['cpu']['avg_cpu_usage'];
                if (empty($avg_cpu))
                    $avg_cpu = 'NULL';

                $peak_cpu = $sync_data['cpu']['peak_cpu_usage'];
                if (empty($peak_cpu))
                    $peak_cpu = 'NULL';

                $value_str .= "($ss_health_fkey, '$sync_type', '$sync_status', '$last_full_sync_time',
                                '$last_delta_sync_time', $read_time, $file_size, $unzipping_time,
                                $indexing_time, '$request_id', '$full_delta', $avg_mem, $peak_mem, $avg_cpu, $peak_cpu, NOW(), $this->org_id), ";
            }
        }
        $value_str = rtrim($value_str);
        $value_str = rtrim($value_str, ',');
        return $this->StoreModel->insertStoreServerSyncLog($value_str);
    }

    public function addStoreServerTillReports($till_reports, $ss_health_fkey)
    {
        $value_str = "";
        foreach ($till_reports as $till_report) {
            $username = $till_report['username'];

            $till_id = $till_report['till_id'];
            if (empty($till_id))
                $till_id = 'NULL';

            $last_request = $till_report['last_request'];
            if (empty($last_request))
                $last_request = 'NULL';

            $requests_sent = $till_report['requests_sent'];
            if (empty($requests_sent))
                $requests_sent = 'NULL';

            $requests_recieved = $till_report['requests_recieved'];
            if (empty($requests_recieved))
                $requests_recieved = 'NULL';

            $avg_time_taken_per_call = $till_report['avg_time_taken_per_call'];
            if (empty($avg_time_taken_per_call))
                $avg_time_taken_per_call = 'NULL';

            $value_str .= "('$username', $ss_health_fkey, '$last_request', $requests_sent, $requests_recieved,
                            $avg_time_taken_per_call, NOW(), $this->org_id), ";
            $this->logger->debug("value str : " . $value_str);
        }
        $value_str = rtrim($value_str);
        $value_str = rtrim($value_str, ',');
        return $this->StoreModel->insertStoreServerTillRep($value_str);
    }

    public function addStoreSvrSQLSvrStats($sql_svr_stats, $ss_health_fkey)
    {
        $value_str = "";

        $is_alive = $sql_svr_stats['is_alive'];
        if (empty($is_alive))
            $is_alive = 'NULL';

        $last_query_exec_time = $sql_svr_stats['last_query_exec_time'];
        if (empty($last_query_exec_time))
            $last_query_exec_time = 'NULL';

        $average_cpu_time = $sql_svr_stats['average_cpu_time'];
        if (empty($average_cpu_time))
            $average_cpu_time = 'NULL';

        $active_connection_count = $sql_svr_stats['active_connection_count'];
        if (empty($active_connection_count))
            $active_connection_count = 'NULL';

        $intouch_db_size = $sql_svr_stats['intouch_db_size'];
        if (empty($intouch_db_size))
            $intouch_db_size = 'NULL';

        $total_db_size = $sql_svr_stats['total_db_size'];
        if (empty($total_db_size))
            $total_db_size = 'NULL';

        $avg_disk_io = $sql_svr_stats['avg_disk_io'];
        if (empty($avg_disk_io))
            $avg_disk_io = 'NULL';

        $operating_system = $sql_svr_stats['operating_system'];
        if (empty($operating_system))
            $operating_system = 'NULL';

        $os_platform = $sql_svr_stats['os_platform'];
        if (empty($os_platform))
            $os_platform = 'NULL';

        $processor = $sql_svr_stats['processor'];
        if (empty($processor))
            $processor = 'NULL';

        $system_ram = $sql_svr_stats['system_ram'];
        if (empty($system_ram))
            $system_ram = 'NULL';

        $value_str .= "($ss_health_fkey, $is_alive, '$last_query_exec_time', $average_cpu_time,
                        $active_connection_count, $intouch_db_size, $total_db_size, $avg_disk_io,
                        '$operating_system', '$os_platform', $processor, $system_ram, NOW(), $this->org_id) ";

        return $this->StoreModel->insertStoreSvrSQLSvrHlth($value_str);
    }

    public function addStoreSvrWCFStats($wcf_report, $ss_health_fkey)
    {
        $value_str = "";

        $requests_sent = $wcf_report['requests_sent'];
        if (empty($requests_sent))
            $requests_sent = 0;

        $last_request = $wcf_report['last_request'];
        if (empty($last_request))
            $last_request = 'NULL';

        $requests_received = $wcf_report['requests_received'];
        if (empty($requests_received))
            $requests_received = 0;

        $version = $wcf_report['version'];
        if (empty($version))
            $version = 'NULL';

        $value_str .= "($ss_health_fkey, $requests_sent, '$last_request', $requests_received, '$version', NOW(), $this->org_id) ";

        return $this->StoreModel->insertStoreSvrWCFStats($value_str);
    }

    public function addStoreSvrBulkUpload($bulk_uploads, $ss_health_fkey)
    {
        $upload_types = array('customer', 'bill', 'nibill');
        $value_str = "";

        foreach ($upload_types as $upload_type) {
            if (isset($bulk_uploads[$upload_type])) {
                $upload_data = $bulk_uploads[$upload_type];

                $upload_type = strtoupper($upload_type);
                $upload_status = $upload_data['status'];
                $upload_time = $upload_data['time'];

                if (empty($upload_status))
                    $upload_status = '';

                if (empty($upload_time))
                    $upload_time = 'NULL';

                $value_str .= "($ss_health_fkey, '$upload_status', '$upload_time', '$upload_type', $this->org_id), ";
            }
        }
        $value_str = rtrim($value_str);
        $value_str = rtrim($value_str, ',');
        return $this->StoreModel->addStoreSvrBulkUpload($value_str);
    }

    public function addTillErrorReport($till_error_codes)
    {
        $value_str = "";

        require_once('apiHelper/ClientErrorLog.php');
        $clientLogger = new ClientErrorLog();

        foreach ($till_error_codes as $till_report) {
            $code = $till_report['code'];

            $count = $till_report['count'];

            $description = $till_report['description'];

            $last_occurrence = $till_report['last_occurrence'];

            $value_str .= "($this->user_id, $this->org_id, '$code', $count, '$description', '$last_occurrence', NOW()), ";

            $clientLogger->debug("[" . date("Y m d H:i:s") . "] [$this->user_id] [$code] [$count]");
        }
        $value_str = rtrim($value_str);
        $value_str = rtrim($value_str, ',');
        return $this->StoreModel->insertTillErrorRep($value_str);
    }

    public function addTillDiagnostics($from, $to, $last_login, $last_fullsync, $integration_mode,
                                       $curr_version, $available_version,
                                       $update_skip_count, $last_update_time, $avg_mem_usage, $peak_mem_usage,
                                       $avg_cpu_usage, $peak_cpu_usage)
    {
        $store_id = $this->currentuser->user_id;
        $this->logger->debug("from : $from, to : $to, last_login : $last_login, last_fullsync : $last_fullsync,
                            integration_mode : $integration_mode,
                            curr_version : $curr_version, available_version : $available_version,
                            update_skip_count : $update_skip_count, last_update_time : $last_update_time,
                            avg_mem_usage : $avg_mem_usage, peak_mem_usage : $peak_mem_usage,
                            avg_cpu_usage : $avg_cpu_usage, peak_cpu_usage : $peak_cpu_usage");

        if (empty($from))
            $from = 'NULL';

        if (empty($to))
            $to = 'NULL';

        if (empty($last_login))
            $last_login = 'NULL';

        if (empty($last_fullsync))
            $last_fullsync = 'NULL';

        if (empty($integration_mode))
            $integration_mode = '';

        if (empty($curr_version))
            $curr_version = '';

        if (empty($available_version))
            $available_version = '';

        if (empty($update_skip_count))
            $update_skip_count = 0;

        if (empty($last_update_time))
            $last_update_time = 'NULL';

        if (empty($avg_mem_usage))
            $avg_mem_usage = 0;

        if (empty($peak_mem_usage))
            $peak_mem_usage = 0;

        if (empty($avg_cpu_usage))
            $avg_cpu_usage = 0;

        if (empty($peak_cpu_usage))
            $peak_cpu_usage = 0;

        $value_str = "($this->user_id, $this->org_id, '$from', '$to', '$last_login', '$last_fullsync',
                     '$integration_mode', '$curr_version', '$available_version',
                      $update_skip_count, '$last_update_time', $avg_mem_usage, $peak_mem_usage,
                      $avg_cpu_usage, $peak_cpu_usage)";

        return $this->StoreModel->insertTillDiagnostics($value_str);
    }

    public function addTillDiagnosticsBulkUpload($bulk_uploads, $till_diagnostics_fkey)
    {
        $upload_types = array('customer', 'bill', 'nibill');
        $value_str = "";

        foreach ($upload_types as $upload_type) {
            if (isset($bulk_uploads[$upload_type])) {
                $upload_data = $bulk_uploads[$upload_type];

                $upload_type = strtoupper($upload_type);
                $upload_status = $upload_data['status'];
                $upload_time = $upload_data['time'];
                if (empty($upload_status))
                    $upload_status = '';

                if (empty($upload_time))
                    $upload_time = 'NULL';

                $value_str .= "($till_diagnostics_fkey, '$upload_status', '$upload_time', '$upload_type', $this->org_id), ";
            }
        }
        $value_str = rtrim($value_str);
        $value_str = rtrim($value_str, ',');
        return $this->StoreModel->insertTillDiagnosticsBulkUpload($value_str);
    }

    public function addTillDiagnosticSyncLogs($sync_logs, $till_diagnostics_fkey)
    {
        $sync_types = array('customers', 'custom_fields_data', 'voucher_series', 'vouchers', 'inventory_master',
            'customer_attributes', 'store_attributes', 'loyalty_tracker', 'fraud_users_list',
            'store_tasks', 'tasks', 'associates', 'reminders', 'comm_template', 'stores',
            'store_tasks_entry', 'task_metadata');
        $value_str = "";

        foreach ($sync_logs as $sync_data) {
            $sync_type = $sync_data['sync_type'];
            if (array_search(strtolower($sync_type), $sync_types) >= 0) {
                $sync_status = $sync_data['sync_status'];

                if (isset($sync_data['last_full_sync_time']) && !(empty($sync_data['last_full_sync_time'])))
                    $last_sync_time = $sync_data['last_full_sync_time'];
                else
                    $last_sync_time = 'NULL';

                if (isset($sync_data['last_delta_sync_time']) && !(empty($sync_data['last_delta_sync_time'])))
                    $last_delta_sync_time = $sync_data['last_delta_sync_time'];
                else
                    $last_delta_sync_time = 'NULL';

                if (isset($sync_data['read_time']) && !(empty($sync_data['read_time'])))
                    $read_time = $sync_data['read_time'];
                else
                    $read_time = 0;

                if (isset($sync_data['file_size']) && !(empty($sync_data['file_size'])))
                    $file_size = $sync_data['file_size'];
                else
                    $file_size = 0;

                if (isset($sync_data['unzipping_time']) && !(empty($sync_data['unzipping_time'])))
                    $unzipping_time = $sync_data['unzipping_time'];
                else
                    $unzipping_time = 0;

                if (isset($sync_data['request_id']) && !(empty($sync_data['request_id'])))
                    $request_id = $sync_data['request_id'];
                else
                    $request_id = '';

                if (isset($sync_data['indexing_time']) && !(empty($sync_data['indexing_time'])))
                    $indexing_time = $sync_data['indexing_time'];
                else
                    $indexing_time = 0;

                if (isset($sync_data['is_full_sync']) && !(empty($sync_data['is_full_sync'])))
                    $full_delta = $sync_data['is_full_sync'];
                else
                    $full_delta = '';

                if (isset($sync_data['memory']['avg_usage']) && !(empty($sync_data['memory']['avg_usage'])))
                    $avg_mem = $sync_data['memory']['avg_usage'];
                else
                    $avg_mem = 0;

                if (isset($sync_data['memory']['peak_usage']) && !(empty($sync_data['memory']['peak_usage'])))
                    $peak_mem = $sync_data['memory']['peak_usage'];
                else
                    $peak_mem = 0;

                if (isset($sync_data['cpu']['avg_usage']) && !(empty($sync_data['cpu']['avg_usage'])))
                    $avg_cpu = $sync_data['cpu']['avg_usage'];
                else
                    $avg_cpu = 0;

                if (isset($sync_data['cpu']['peak_usage']) && !(empty($sync_data['cpu']['peak_usage'])))
                    $peak_cpu = $sync_data['cpu']['peak_usage'];
                else
                    $peak_cpu = 0;

                $value_str .= "($till_diagnostics_fkey, '$sync_type', '$sync_status', '$last_sync_time',
                                '$last_delta_sync_time', $read_time, $file_size, $unzipping_time,
                                $indexing_time, '$request_id', '$full_delta', $avg_mem, $peak_mem,
                                $avg_cpu, $peak_cpu, $this->org_id), ";
            }
        }
        $value_str = rtrim($value_str);
        $value_str = rtrim($value_str, ',');
        return $this->StoreModel->insertTillDiagnosticSyncLogs($value_str);
    }

    public function addTillDiagnosticSystemDetails($system_details, $till_diagnostics_fkey)
    {
        if(is_null($system_details)) {
            $this->logger->debug('System details empty. not inserting and returning');
            return true;
        }

        $value_str = "";

        if (isset($system_details['os']) && !(empty($system_details['os'])))
            $os = $system_details['os'];
        else
            $os = '';

        if (isset($system_details['os_platform']) && !(empty($system_details['os_platform'])))
            $os_platform = $system_details['os_platform'];
        else
            $os_platform = '';

        if (isset($system_details['processor']) && !(empty($system_details['processor'])))
            $processor = $system_details['processor'];
        else
            $processor = '';

        if (isset($system_details['processor_family']) && !(empty($system_details['processor_family'])))
            $processor_family = $system_details['processor_family'];
        else
            $processor_family = '';

        if (isset($system_details['system_ram']) && !(empty($system_details['system_ram'])))
            $system_ram = $system_details['system_ram'];
        else
            $system_ram = 0;

        if (isset($system_details['db_size']) && !(empty($system_details['db_size'])))
            $db_size = $system_details['db_size'];
        else
            $db_size = '';

        if (isset($system_details['sqlite_version']) && !(empty($system_details['sqlite_version'])))
            $sqlite_version = $system_details['sqlite_version'];
        else
            $sqlite_version = '';

        if (isset($system_details['framework_version']) && !(empty($system_details['framework_version'])))
            $framework_version = $system_details['framework_version'];
        else
            $framework_version = '';

        if (isset($system_details['heartbeat']['success']) && !(empty($system_details['heartbeat']['success'])))
            $heartbeat_suc = $system_details['heartbeat']['success'];
        else
            $heartbeat_suc = 0;

        if (isset($system_details['heartbeat']['failure']) && !(empty($system_details['heartbeat']['failure'])))
            $heartbeat_fail = $system_details['heartbeat']['failure'];
        else
            $heartbeat_fail = 0;

        if (isset($system_details['till_time']) && !(empty($system_details['till_time'])))
            $till_time = $system_details['till_time'];
        else
            $till_time = 'NULL';

        if (isset($system_details['server_time']) && !(empty($system_details['server_time'])))
            $server_time = $system_details['server_time'];
        else
            $server_time = 'NULL';

        if (isset($system_details['proxy_enabled']) && !(empty($system_details['proxy_enabled'])))
            $proxy_enabled = $system_details['proxy_enabled'];
        else
            $proxy_enabled = 0;

        if (isset($system_details['timezone']) && !(empty($system_details['timezone'])))
            $timezone = $system_details['timezone'];

        $value_str .= "($till_diagnostics_fkey, '$os', '$os_platform', '$processor', '$processor_family', $system_ram,
                        '$db_size', '$sqlite_version', '$framework_version', $heartbeat_suc, $heartbeat_fail,
                        '$till_time', '$server_time', $proxy_enabled, '$timezone', $this->org_id), ";
        $value_str = rtrim($value_str);
        $value_str = rtrim($value_str, ',');
        return $this->StoreModel->insertTillDiagnosticsSystemDetails($value_str);
    }

    /**
     * @return timezone of store from hierarchy of STORE-> -> ZONE/CONCEPT ->ORG
     */
    public function getStoreTimezoneFromHierarchy($id)
    {
        $key = "timezone";
        $details = $this->getEntityDetailsFromCache($id, $this->org_id);
        {
            if ($details[$key])
                return $details[$key];
        }

        $entity_resolver = new EntityResolver($id, 'STORE');
        $zone = $entity_resolver->getParent('ZONE');
        if (isset($zone[0])) {
            $this->logger->debug("Timezone NOT available for store. going to fetch timezone for zone : " . $zone[0]);
            $zone_Controller = new ApiZoneController();
            $tz = $zone_Controller->getZonesTimezoneFromHierarchy($zone[0]);
            $this->setEntityDetailsToCache($id, $this->org_id, array($key => $tz));
            return $tz;
        } else {
            $this->logger->debug("Failed to get Timezone from zone hierarchy. returning org default Timezone");
            $organization_controller = new ApiOrganizationController();
            $tz = $organization_controller->getOrgDefaultTimezone();
            $this->setEntityDetailsToCache($id, $this->org_id, array($key => $tz));
            return $tz;
        }
    }

    public function getStoreCurrencyFromHierarchy($id)
    {
        $key = "currency";
        $details = $this->getEntityDetailsFromCache($id, $this->org_id);
        {
            if ($details[$key])
                return $details[$key];
        }

        $entity_resolver = new EntityResolver($id, 'STORE');
        $zone = $entity_resolver->getParent('ZONE');
        if (isset($zone[0])) {
            $this->logger->debug("Currency NOT available for store. going to fetch Currency for zone : " . $zone[0]);
            $zone_Controller = new ApiZoneController();
            $tz = $zone_Controller->getZonesCurrencyFromHierarchy($zone[0]);
            $this->setEntityDetailsToCache($id, $this->org_id, array($key => $tz));
            return $tz;
        } else {
            $this->logger->debug("Failed to get currency from zone hierarchy. returning org default currency");
            $organization_controller = new ApiOrganizationController();
            $tz = $organization_controller->getOrgDefaultCurrency();
            $this->setEntityDetailsToCache($id, $this->org_id, array($key => $tz));
            return $tz;
        }
    }

    public function getStoreLanguageFromHierarchy($id)
    {
        $key = "language";
        $details = $this->getEntityDetailsFromCache($id, $this->org_id);
        {
            if ($details[$key])
                return $details[$key];
        }

        $entity_resolver = new EntityResolver($id, 'STORE');
        $zone = $entity_resolver->getParent('ZONE');
        if (isset($zone[0])) {
            $this->logger->debug("Language NOT available for store. going to fetch Language for zone : " . $zone[0]);
            $zone_Controller = new ApiZoneController();
            $tz = $zone_Controller->getZonesLanguageFromHierarchy($zone[0]);
            $this->setEntityDetailsToCache($id, $this->org_id, array($key => $tz));
            return $tz;
        } else {
            $this->logger->debug("Failed to get Language from zone hierarchy. returning org default Language");
            $organization_controller = new ApiOrganizationController();
            $tz = $organization_controller->getOrgDefaultLanguage();
            $this->setEntityDetailsToCache($id, $this->org_id, array($key => $tz));
            return $tz;
        }
    }
    
    public function pushToStoreCare($data){

    	include_once 'helper/CurlManager.php';
    	include_once 'helper/EntityResolver.php';
    	 
    	$this->logger->debug("Pushing data to store care");
    	 
    	$curlMgr = new CurlManager();
    	 
    	$parsedFile = parse_ini_file("/etc/capillary/api/api-config.ini");
    	$url = $parsedFile["STORECARE_BASE_URL"]."/storeCare/addDiagnosticsData?";
    	$url .= "&org_id=".$this->currentorg->org_id;
    	 
    	$entityResolver = new EntityResolver($this->currentuser->user_id);
    	 
    	
        $storeId= $entityResolver->getParent("STORE");
        $zoneId= $this->getParentZone($storeId[0]);        
        try {
	        $sm = new ApiEntityController("STORE");
    	    $store_name= $sm->getDetailsById($storeId[0]);
        } catch (Exception $e) {
        }
        
        try {
        	$zm = new ApiEntityController("ZONE");
        	$zone_name = $zm->getDetailsById($zoneId[0]);
        } catch (Exception $e) {
        }
        $url .= "&store_id=".$storeId[0];
        $url .= "&zone_id=".$zoneId[0];
        $url .= "&store_name=".urlencode($store_name["name"]);
        $url .= "&zone_name=".urlencode($zone_name["name"]);
    	    	
    	if($this->currentuser->getType() == 'TILL'){
    		$url .= "&till_id=".$this->currentuser->user_id;
			try{
				$tc = new ApiEntityController("TILL");
				$till_name = $tc->getDetailsById($this->currentuser->user_id);
    			$url .= "&till_name=".urlencode($till_name["name"]);
    			#$url .= "&till_name=".urlencode($this->currentuser->getName());
			} catch (Exception $e){
				$this->logger->debug("Till name not found for ". $this->currentuser->user_id);
			}
    		try{
                $storeserver_id = parent::getParentEntityByType($this->currentuser->user_id, 'STR_SERVER');
				if($storeserver_id){
					$url .= "&source=thin_client";
					$url .= "&store_server_id=".$storeserver_id[0];
					$tc = new ApiEntityController("STR_SERVER");
            		$sc_name = $tc->getDetailsById($storeserver_id[0]);
    				$url .= "&store_server_name=".urlencode($sc_name["name"]);
				}
				else{
					$url .= "&source=till";
				}
			}catch (Exception $e) {
				$storeserver_id=0; 
				$url .= "&source=till";
			}
    		//$url .= "&source=".($storeserver_id? "thin_client" : "till"); // $entityResolver->getParentStoreServer() 
    	}
    	else{
    		$url .= "&source=store_server";
    		$url .= "&store_server_id=".$this->currentuser->user_id;
			$tc = new ApiEntityController("STR_SERVER");
            $sc_name = $tc->getDetailsById($this->currentuser->user_id);
    		$url .= "&store_server_name=".urlencode($sc_name["name"]);
    	}
    	 
    	$data = json_encode($data);
    	$this->logger->debug("The url used will be -> ".$url);
    	try {
    		$curlMgr->setHeader("Content-Type: application/json");
    		$curlMgr->post($url, $data);
    	} catch (Exception $e) {

    		$this->logger->error("Failed to push the data to store care with message" . $e->getMesssage());
    	}
    	 
    	 
    }

    public function pushRecentRequestsAsynchronously($data) {
        
        $this -> logger -> debug("Pushing recent requests data to store care: " . print_r($data, true));

        require_once 'apiHelper/AsyncAPIRequest.php';

        $entityResolver = new EntityResolver($this -> currentuser -> user_id);
        $storeName = $zoneName = '';

        $storeIds = $entityResolver -> getParent('STORE');
        $storeId = $storeIds[0];
        try {
            $storeManager = new ApiEntityController('STORE');
            $storeDetails = $storeManager -> getDetailsById($storeId);
        } catch (Exception $e) {
            $this -> logger -> debug("Store details not found for store " . $storeId); 
        }

        $zoneIds = $this -> getParentZone($storeId); 
        $zoneId = $zoneIds[0];
        try {
            $zoneManager = new ApiEntityController('ZONE');
            $zoneDetails = $zoneManager -> getDetailsById($zoneId);
        } catch (Exception $e) {
            $this -> logger -> debug("Zone details not found for zone ". $zoneId);
        }
        
        $requestData = array();
        $requestData['org_id'] = $this -> currentorg -> org_id;
        $requestData['store_id'] = $storeId;
        $requestData['zone_id'] = $zoneId;
        $requestData['storeName'] = $storeDetails['name'];
        $requestData['zone_name'] = $zoneDetails['name'];

        if ($this -> currentuser -> getType() == 'TILL') {
            $tillId = $this -> currentuser -> user_id;
            $requestData['till_id'] = $tillId;

            try {
                $tillController = new ApiEntityController('TILL');
                $tillDetails = $tillController -> getDetailsById($tillId);

                $requestData['entityIUN'] = $tillDetails['name'];
                $requestData['till_name'] = $tillDetails['name'];
            } catch (Exception $e) {
                $this -> logger -> debug('Till details not found for till ' . $tillId);
            }

            try {
                $storeServerIds = parent::getParentEntityByType($tillId, 'STR_SERVER');
                if ($storeServerIds) {

                    $storeServerId = $storeServerIds[0];
                    $requestData['source'] = 'thin_client';
                    $requestData['store_server_id'] = $storeServerId;

                    try {
                        $storeServerController = new ApiEntityController('STR_SERVER');
                        $storeServerDetails = $storeServerController -> getDetailsById($storeServerId);
                        $requestData ['store_server_name'] = $storeServerDetails['name'];
                    } catch (Exception $e) {
                        $this -> logger -> debug('Store-server details not found for store-server ' . $storeServerId);
                    }
                } else {
                    $requestData['source'] = 'till';
                }
            } catch (Exception $e) {
                $requestData['source'] = 'till';
            }
        } else {
            $requestData['source'] = 'store_server';
            $requestData['store_server_id'] = $this -> currentuser -> user_id;

            try {
                $storeServerController = new ApiEntityController('STR_SERVER');
                $storeServerDetails = $storeServerController -> getDetailsById($storeServerId);
                $requestData['entityIUN'] = $storeServerDetails['name'];
                $requestData['store_server_name'] = $storeServerDetails['name'];
            } catch (Exception $e) {
                $this -> logger -> debug('Store-server details not found for store-server ' . $storeServerId);
            }
        }

        $requestData['lastTransactionTime'] = $data['lastTransactionTime'];
        $requestData['lastTransactionNumber'] = $data['lastTransactionNumber'];
        $requestData['lastTransactionSyncTime'] = $data['lastTransactionSyncTime'];
        $requestData['lastRegistrationTime'] = $data['lastRegistrationTime'] ? $data['lastRegistrationTime'] : "";
        $requestData['lastRegistrationSyncTime'] = $data['lastRegistrationSyncTime'] ? $data['lastRegistrationSyncTime'] : "";

        $post['recentRequests'] = $requestData;

        global $cfg;
        $ipAddress = $cfg['srv']['storecare']['host'];        
        if (! empty($ipAddress)) {
            $port = $cfg['srv']['storecare']['port'];
            $storeCareBaseUrl = 'http://' . $ipAddress;
            if (! empty($port)) {
                $storeCareBaseUrl .= ":" . $port;
            }
            $storeCareBaseUrl .= '/add/recentRequests';
            $this -> logger -> debug("Pushing store diagnostics " . json_encode($post) . " to " . $storeCareBaseUrl);
            
            AsyncAPIRequest::post($storeCareBaseUrl, json_encode($post));
        }
    }
}
?>
