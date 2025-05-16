
<?php 

include_once 'apiController/ApiBaseController.php';
//TODO: referes to cheetah
include_once 'base_model/class.OrgRole.php';
include_once 'apiModel/class.ApiOrganizationModelExtension.php';
include_once 'submodule/inventory.php';
////TODO: referes to cheetah
include_once 'base_model/class.CachedFilesModelExtension.php';
////TODO: referes to cheetah
include_once 'base_model/class.OrgDetails.php';
include_once 'models/IncomingInteractionActionParams.php';
include_once 'models/IncomingInteractionActions.php';
include_once 'models/SourceMapping.php';
include_once 'cheetah/thrift/pointsengineruleservice.php';
include_once 'models/IncomingInteractionOrgPrefix.php';
include_once 'exceptions/ApiIncomingInteractionOrgPrefixModelException.php';
/**
 * The main thick controller 
 * 
 * @author Suryajith
 */
class ApiOrganizationController extends ApiBaseController{
	
	/**
	 * ORGANIZATIONS HAS COMPOSITE RELATION WITH
	 * ZONE/CONCEPT/STORE/STORE TILL/STORE SEREVR.
	 * 
	 * Organization has entities which in turn has ZONE/CONCEPT/STORE.
	 */
	private $OrgRole;
	public $ZoneController;
	public $StoreController;
	public $ConceptController;

	public $StoreTillController;
	public $StoreServerController;
	public $config_manager ;

	public $OrgModelExtension;
	public $Org_details_model;
	
	public function __construct(){

		parent::__construct();
		
		$this->OrgRole = new OrgRoleModel();
		$this->ZoneController = new ApiZoneController();
		$this->StoreController = new ApiStoreController();
		$this->ConceptController = new  ApiConceptController();
		$this->StoreTillController = new ApiStoreTillController();
		$this->StoreServerController = new ApiStoreServerController();
		$this->OrgModelExtension = new ApiOrganizationModelExtension();
		$this->OrgModelExtension->load($this->org_id);
		
		$this->config_manager = new ConfigManager() ;
		$this->Org_details_model = new OrgDetailsModel();
	}

	public function &load(){
	
		return $this->org_model;
		
	}
	
	/**
	 * returns hash
	 */
	public function loadHash(){
		
		return $this->org_model->getHash();	
	}
	
	/**
	 * Get the org_profile for the view.
	 */
	public function getOrgProfile(){
		
		$org_profile = $this->org_model->getBaseOrgProfile();
		$org_profile['name'] = $org_profile['name'];
		
		$fiscal_year_options = $this->getFiscalYearAsOptions();
		$fiscal_year_options = array_flip($fiscal_year_options);
		$org_profile['fiscal_year_start'] = $fiscal_year_options[$org_profile['fiscal_year_start']];
		$org_profile['supported_currencies'] = implode('<br>  ', $this->org_model->getAlreadySupportedCurrencyNames());
		
		$org_profile['supported_timezones'] = implode('<br>', $this->org_model->getAlreadySupportedTimeZoneNames());
		
		$org_profile['supported_timezones'] = implode('<br>', $this->org_model->getAlreadySupportedTimeZoneNames());
		
		$org_profile['Organization POC']  = implode ('<br> ', $this->org_model->getAlreadySetOrgPOC());
		
		$org_profile['Capillary POC']  = implode ('<br> ', $this->org_model->getAlreadySetCapPOC());
		
		$org_profile['supported_countries'] = implode('<br>',$this->org_model->getAlreadySupportedCountries());
		
		return array($org_profile);
	}
	
	public function getSummaryProfile(){
		
		$custom = array();
		$custom = $this->org_model->getCreditInfo();
		
		$entities = $this->org_model->getEntitiesCount();
		$org_sum_profile['Stores'] = $entities['store'];//$this->org_model->getStoresCount();
		$org_sum_profile['Zones']  = $entities['zone'];//$this->org_model->getZonesCount();
		$org_sum_profile['Concepts']  = $entities['concept'];//$this->org_model->getConceptCount();
		//$org_sum_profile['Total customers']  = $this->org_model->getOrgCustomer();
		//$org_sum_profile['loyalty customers'] = $this->org_model->getOrgLoyaltyCustomer();
		$org_sum_profile['Point Of Sale '] = $this->org_model->getPOSUsedByOrg();
		$org_sum_profile['ERP Solutions '] = $this->org_model->getERPUSedByORG();
		$org_sum_profile['Bulk SMS credits'] = $custom[0]['bulk_sms_credits'];
		$org_sum_profile['Value SMS credits'] = $custom[0]['value_sms_credits'];		
		$org_sum_profile['User SMS credit'] = $custom[0]['user_credits'];
		
		return array($org_sum_profile);
	}
	
	/*
	 * Contact Point List
	 */	
	public function getCapContactPointList() {
		return	$this->org_model->getCapContactPoint();
	}
	
	
	/*
	 * Get Capillary POC 
	 */
		
	public function getCapillaryPOC(){
		return $this->org_model->getCapillaryPOC();
	}
	
	/*get Default value of Capillary POC
	 *
	 */
	public function getAlreadyOrgPOC(){
		return $this->org_model->getAlreadyOrgPOC();
	}
	
	public function getAlreadyOrgPOCEmailHash( $key_name = 'name' ){

		return $this->org_model->getAlreadyOrgPOCEmailHash( $key_name );
	}
	
	public function getAlreadyCapPOCEmailHash( $key_name = 'name' ){
		return $this->org_model->getAlreadyCapPOCEmailHash( $key_name );
	}
	
	public function getOrgPOCDetails(){
		return $this->org_model->getOrgPocDetails();
	}
	
	public function getAlreadyCapPOC(){
		return $this->org_model->getAlreadyCapPOC();
	}
	/**
	 * 
	 * Get Default Values of Org POC
	 */
	
	
	/*
	 * Get Organization POC 
	 */
	public function getOrgPOC(){
		return $this->org_model->getOrgPOC();
	}
	
/*
	 * Get Parent Organization POC 
	 */
	public function getParentOrgPOC( $parent_org_id ){
		return $this->org_model->getParentOrgPOC( $parent_org_id );
	}
		
	/*
	 * function for setting the POC of ORG
	 */
	public function setOrgPOC($org_poc_id){
		return $this->org_model->setOrgPOC($org_poc_id,  $this->user_id);
	}
	
	public function setCapPOC($cap_poc_id){
		return $this->org_model->setCapPOC($cap_poc_id, $this->user_id);
	}
	
	/* to get base currency,
	 *  base timezone, base language of the organizations
	*/
	
	
	public function getBaseCurrencyId(){
		return $this->org_model->getBaseCurrency();
	}
	
	public function getBaseTimeZoneId(){
		return $this->org_model->getBaseTimeZone();
	}
	
	public function getBaseLanguageId(){
		return $this->org_model->getBaseLanguage();
	}
	/**
	 * Update the set the values to org object and update it in the db.
	 * @param $name
	 * @param $address
	 * @param $fiscal_year
	 * @param $phone
	 */
	public function update( $address, $fiscal_year, $base_language_id){
		
		$org_id = $this->org_model->getId();
		$this->org_model->setAddress( $address );
		$this->org_model->setFiscalYearStart( $fiscal_year );
		$this->org_model->setPhone( $phone );
		//$this->org_model->setBaseLanguageId($base_language_id);
		
		return $this->org_model->update( $org_id );
	}

	/**
	* Update the org details
	* @param $params CONTRACT(
	*	org_log => VALUE,
	*	org_website => VALUE
	* )
	*/
	public function updateOrgDetails( $params ){

		return $this->org_model->updateOrgDetails( $params );
	}	

	/**
	 * Get all the supported timezones as options.
	 * @return a list of timezones
	 */
	public function &getAllTimeZoneAsOptions(){
		
		$timezone_options = $this->org_model->getAllTimeZones();
		//ksort($timezone_options);
		
		return $timezone_options;
	}

	/**
	 * 
	 * Get all the timezones as options by country id
	 * @param unknown_type $country_id
	 */
	public function &getAllTimeZoneAsOptionsByCountryId( $country_id ){
		
		$timezone_options = $this->org_model->getAllTimeZonesByCountryId( $country_id );
		
		return $timezone_options;
	}
	/**
	 * turns the time zone configured for the orgs
	 */
	public function &getOrgSupportedTimeZones(){
		
		$timezones = $this->org_model->getOrgSupportedTimeZone();
		
		return $timezones;
	}

    public function getOrgSupportedTimeZoneISO(){

        return $this->OrgModelExtension->getOrgSupportedTimeZoneISO();
    }
	
	public function &getOrgSupportedLanguages(){
		
		return $this->org_model->getOrgSupportedLanguages();	
	}

    public function getOrgSupportedLanguagesISO(){

        return $this->OrgModelExtension->getOrgSupportedLanguagesISO();
    }
	
	/**
	 * Get All supported countries as options
	 * return a list of countires as option
	 */
	
	public function &getAllCountriesAsOptions(){
		$currency_options = $this->org_model->getAllSupportedCountries();
		ksort($currency_options);
		
		return $currency_options;
	}
	
	public function getReminderDetailsById( $id ){
		
		return $this->org_model->getReminderDetailsById( $id, $this->org_id );
		
	}
	
	public function getReminderByType( $type, $reminder_id ){
		
		return $this->org_model->getReminderByType( $this->org_id , $type, $reminder_id);
		
	}
	
	public function getReminderForReport( ){
		
		return $this->org_model->getReminderForReport( $this->org_id );
		
	}
	
	public function getReminderForReportFtp(){
		
		return $this->org_model->getReminderForReportFtp( $this->org_id );
	}
	
	public function getReminderForReportOrgAdmin(){
	
		return $this->org_model->getReminderForReportOrgAdmin( $this->org_id );
	}
	
	public function getReminderForEmf(){
	
		return $this->org_model->getReminderForEmf( $this->org_id );
	}
	
	public function getReminderForPointsEngine(){
	
		return $this->org_model->getReminderForPointsEngine( $this->org_id );
	}
	
	/**
	 * Get all the currencies as the options
	 * @return a list of currencies
	 */
	public function &getAllCurrenciesAsOptions(){
		
		$currency_options = $this->org_model->getAllSupportedCurrencies();
		ksort($currency_options);
		
		return $currency_options;
	}

	/**
	 * Returns the list of all countries which are supported for this organization
	 * @return List of supported countries for the org
	 */
	
	public function getSupportedCountriesAsOptions(){
		return $this->org_model->getSupportedCountries();	
	}

    public function getSupportedCountriesISO(){
        return $this->OrgModelExtension->getSupportedCountriesISO();
    }
	
	/*	public function getFiscalYearAsOptions(){
		$months = array ("Jan" => 1, "Feb =>2", "March" =>3, "April" => 4, "May" => 5, "June" => 6, "July" => 7, "Aug" => 8, "Sept" => 9, "October" => 10, "Nov" =>11, "Dec" =>12   );
         return $months;   	
	} */
	
	/*
	 *Returns the controller 
	 */
	public function StoreConfig(  $params, $inactive_stores ){
    	
		$batch_array = array();
    	
		foreach ( $inactive_stores as $value ){
				
				$replaced_store = $params[$value['store_id']];
				
				$store_id = $value['store_id'];
				array_push( $batch_array, "( '$store_id' ,'$replaced_store' )" ); 
			}
			
			$batch = implode( ',', $batch_array );
    	
		return $this->org_model->addStoreToReplaceForInactiveStores( $batch );
    }
	
	
	
	/**
	 * This gets already set values so as to populate the default fields.
	 */
	public function getAlreadySetCurrenciesAsOptions(){
		
		return $this->org_model->getAlreadySetCurrencies();	
	}
	
	public function getAlreadySetLanguagesAsOptions(){
		return $this->org_model->getAlreadySetLanguages();
	}
	
	
	public function getOrgSupportedCurrencies(){
		
		return $this->org_model->getOrgSupportedCurrencies();
	}

    public function getOrgSupportedCurrenciesISO(){

        return $this->OrgModelExtension->getOrgSupportedCurrenciesISO();
    }
	/**
	 * This gets already set timezones for the org to populate the default fields.
	 */
	public function getAlreadySetTimezonesAsOptions(){
		
		return $this->org_model->getAlreadySetTimeZones();
	}
	
	/**
	 * Get all the languages as options
	 */
	public function &getAllLanguagesAsOptions(){
		
		$language_options = $this->org_model->getAllSupportedLanguages();
		ksort($language_options);
		
		return $language_options;
	}
	
	/**
	 * Set the default timezone id with the provided input.
	 * @param unknown_type $timezone_id
	 */
	public function setDefaultTimeZoneId($timezone_id){
		
		$org_id = $this->org_model->getId();
		$this->org_model->setDefaultTimeZoneId($timezone_id);
		
		return $this->org_model->update($org_id);
	}
	
	/**
	 * Set the base currency for the org.
	 * @param unknown_type $currency_id
	 */
	public function setBaseCurrencyId($currency_id){
		
		$org_id = $this->org_model->getId();
		$this->org_model->setBaseCurrencyId($currency_id);
		
		return $this->org_model->update($org_id);
	}
	
	/**
	 * Set the default language for the org.
	 * @param unknown_type $language_id
	 */
	public function setBaseLanguageId($language_id){
		
		$org_id = $this->org_model->getId();
		$this->org_model->setBaseLanguageId($language_id);
		
		return $this->org_model->update($org_id);
		
	}
	 public function getFiscalYearAsOptions() {
	 	$months =  array("January" => 1, "February" => 2 ,"March" => 3, "April" => 4, "May" => 5, "June" => 6, "July"=> 7,"August"=>8,"September" => 9, "October" => 10, "Novemeber" =>11, "December" => 12 );
	 	return $months;
	 } 
	
	/**
	 * Set all the supported timezones for that org.
	 * @param $array_timezone_ids
	 */
	public function setSupportedTimeZones($array_timezone_ids){
		
		return $this->org_model->setSupportedTimeZones($array_timezone_ids);
		
	}
	
	/**
	 * Set all the supported currencies by the org.
	 * @param unknown_type $array_currency_ids
	 */
	public function setSupportedCurrencies($array_currency_ids){

		return $this->org_model->setSupportedCurrencies($array_currency_ids);
		}
	
	public function setSupportedLanguages($array_languages_ids){
		
		return $this->org_model->setSupportedLanguages($array_languages_ids);
	}
	
	
	public function setBaseCurrencyInSupported($base_currency,$old_base_currency)
	{
		return $this->org_model->setBaseCurrencyInSupported($base_currency,$old_base_currency);
	}
	
	public function setBaseLanguagesInSupported($base_languages,$old_base_languages)
	{
		return $this->org_model->setBaseLanguagesInSupported($base_languages,$old_base_languages);
	}
	
	
	public function setBaseTimeZoneInSupported($base_timezone, $old_timezone){
		return $this->org_model->setBaseTimeZoneInSupported($base_timezone,$old_timezone);
	}
	
	
	
	/*
	 * Gets all data.
	 */
	public function getAllData(){
		
		return $this->org_model->selectAll( );
	}
	
	/**
	 * Get all the organizations as options based on the filter
	 * which specifies whether to load the inactive orgs or not.
	 * @param $include_inactive
	 */
	public function getOrgAsOptions( $include_inactive = false, $get_all = false ){
		
		$orgs = $this->org_model->getAll( $include_inactive );
		
		$SecurityManager = new SecurityManager();
		
		$access_all = false;
		
		if( $SecurityManager->checkIfGodUser( $this->user_id ) )
			$access_all = true;
		else
			$proxy_orgs = $SecurityManager->getProxyOrgsForUser( $this->user_id );
			
		if( $get_all ) $access_all = true;
		
		$org_options = array();
		foreach( $orgs as $org ){
			
			if( !$get_all ) if( $org['id'] == $this->org_id ) continue;
			$org_options[stripslashes($org['name'])] = $org['id'];
		}		

		if( !$access_all ){

			$org_options = array();
			foreach( $proxy_orgs as $proxy_org ){

				if( $proxy_org['proxy_org_id'] == $this->org_id ) continue;
				$org_options[stripslashes($proxy_org['name'])] = $proxy_org['proxy_org_id'];
			}
		}
		
		return $org_options;
	}

	
	public function getClientCrons(){
		return $this->org_model->getClientCronEntries();
	}	
	
	public function getStoresList(){
		$stores_list = $this->StoreController->getStoresAsOptions();
		return $stores_list;
	}
	
	/**
	 * 
	 * @param unknown_type $type: type of entity
	 */
	public function getEntitiesForOrg( $type ){
		
		return $this->org_model->getEntitiesForOrg( $this->org_id, $type );
	}
	
	/**
	 *  get Area Details
	 */
	public function getAreaDetails( ){
		
		return $this->org_model->getAreaDetails( $this->org_id );
	}
	
	/**
	 *  get city details
	 */
	public function getCityDetails(){
		
		return $this->org_model->getCityDetails( $this->org_id );
	}
	
	public function getOrgId(){
		return $this->org_id;
	}

	public function getUserId(){
		return $this->user_id;
	}
	
	/**
	 * Adding this function for backward compatiblity with the OrgProfile
	 * object
	 */
	
	public function getId(){
		return $this->getOrgId();
	}
	
	public function getCronFilesList($type = ''){
		
		$sf = new StoredFiles($this);
		if($type == 'exe'){
			return $sf->getFilesByTagAsOptions(STORED_FILE_TAG_CLIENT_CRON, NULL, 'EXE');
		}else{
			return $sf->getFilesByTagAsOptions(STORED_FILE_TAG_CLIENT_CRON);
		}
			
	}
	
	
	public function getBaseCurrencyForOrg(){
		
		return $this->org_model->getBaseCurrencyForOrg();
	}
	
	public function listCronFiles(){
		$sf = new StoredFiles($this);
		return $sf->listFilesByTag(STORED_FILE_TAG_CLIENT_CRON);
	}
	
	
	public function addClientCron($type, $name, $start_date, $end_date, $stores_list, $minutes,
								  $hours, $month, $dom, $dow, $cron_file, $exe_file){
		$this->logger->debug("Adding Client Cron");
		$cron_mgr = new ClientCronMgr();
		
		$params = array();
		$params['client_cron_entry_cron_type'] = $type;
		$params['client_cron_entry_name'] = $name;
		$params['client_cron_entry_start_date'] = $start_date;
		$params['client_cron_entry_end_date'] = $end_date;
		$params['client_cron_entry_enabled_at_stores'] = $stores_list;
		
		$params['cmgr_cron_minute'] = $minutes;
		$params['cmgr_cron_hour'] = $hours;
		$params['cmgr_cron_day_of_month'] = $dom;
		$params['cmgr_cron_month'] = $month;
		$params['cmgr_cron_day_of_week'] = $dow;
		//$params['cmgr_cron_month'] 
		return $cron_mgr->addOrUpdateClientCronEntryFromFormParams($params);							  
	}
	

	public function getAssignedOption(){
		
		return $this->org_model->getAssignedOption();
	}


	/**
	 * Returns the list of the client log files for all the stores.
	 * Not adding it the store controller as it is going to be organization level
	 */
	
	public function getStoreLogsInfo(){
		$result = $this->org_model->getClientLogEntries();
		return $result;
	}
	
	/**
	 * Returns the information about which client version is installed 
	 * on how many stores for that organization. 
	 */
	
	public function getClientVersionDetails(){
		return $this->org_model->getClientVersionDetails();
	}
	
	public function getStoreFilesDetails(){
		return $this->org_model->getStoreFilesDetails();
	}
	
	/**
	 * Returns the status of the sms's in the nsadmin table
	 */
	public function getSMSStatus(){
		
		$status_list = array("'RECEIVED'", "'READ'", "'IN_GTW_QUEUE'", "'IN_PRVD_QUEUE'", "'GTW_NOT_FOUND'", "'SENDING'", "'SENT'");
		$sql = "SELECT status, COUNT(*) AS count FROM messages WHERE status IN (" . implode(',', $status_list) . ")
				GROUP BY status";
		$db = new Dbase('nsadmin');
		return $db->query($sql);
	}
	
	public function getMsgingStatus(){

		$db = new Dbase('msging');
		//get message ID the last 5 bulk SMS blasts
		$latestMessageIds = $db->query_firstcolumn("
			SELECT messageId
			FROM `outboxes` 
			WHERE type = 'SMS' 
			AND status = 'closed' 
			ORDER BY messageId DESC 
			LIMIT 5
		");
		
		//get the max 
		$maxInboxIds = $db->query_firstcolumn("
			SELECT MAX(i.id) as max_inbox_id
			FROM msging.inboxes i 
			WHERE i.messageId IN (".Util::joinForSql($latestMessageIds).")
			GROUP BY i.messageId
		");
		
		$result = $db->query("
			SELECT o.messageId, o.messageText, o.numDeliveries as num_messages, o.createdTime, 
				m.sent_time as finished_time, time_to_sec(timediff(m.sent_time, o.createdTime)) as time_taken, 
				ROUND(o.numDeliveries/time_to_sec(timediff(m.sent_time, o.createdTime)),2) as rate_per_second
			FROM nsadmin.messages m
			JOIN msging.inboxes i ON i.id = m.inbox_id
			JOIN msging.outboxes o ON o.messageId = i.messageId AND o.messageId IN (".Util::joinForSql($latestMessageIds).")
			WHERE m.inbox_id IN (".Util::joinForSql($maxInboxIds).")
			ORDER BY o.messageId DESC
		");		
	
		return $result;
	}
	
	
	public function getNSAdminStatus(){

		$sql = "SELECT id, message, priority, status, sent_time, received_time,  
				CONCAT(time_to_sec(timediff(sent_time, received_time)),'s') as time_taken, gateway 
				FROM `messages` WHERE priority = 'HIGH' ORDER BY `id` DESC LIMIT 5";
		$db = new Dbase('nsadmin');
		return $db->query($sql);
		
	}
	
	/**
	 * Returns all the parent roles for the
	 * given role id
	 * @param unknown_type $role_id
	 */
	public function getParentRoles( $role_id ){
		
		if( !$role_id ) return array();
		
		$role_hash = $this->org_model->getAllRolesHash();

		$parent_ids = array();
		
		$continue = true;
		while( $continue ){

			$continue = false;
			$parent_id = $role_hash[$role_id];

			if( $parent_id ){
				
				$continue = true;
				$role_id = $parent_id;
				array_push( $parent_ids, $parent_id );
			}
		}
		
		return $parent_ids;
	}
	
	public function getLoggedInUserId( ){
		
		return $this->currentuser->user_id;
	}
	
	/**
	 * returns all the roles for the organizations
	 * as options
	 */
	public function getOrgRolesAsOptions( $role_id = false ){
		
		$org_roles =  $this->org_model->getAllRoles();
		$this->logger->debug( 'template_ancy within getorgroles'.print_r( $org_roles, true ) );
		
		$options = array();
		
		foreach( $org_roles as $or )
			$options[$or['role_name']] = $or['id'];

		if( $role_id ){
			
			$options = array_flip( $options );
			unset( $options[$role_id] );
			
			//remove its children
			$children = array();
			$roles = array();
			$continue = true;
			$role_ids = $role_id;
			
			while( $continue ){
				
				$child_roles = $this->org_model->getChildRoles( $role_ids );
				$children = array_merge( $children, $child_roles );

				if( count( $child_roles ) > 0 ){
					
					$roles = array();
					foreach( $child_roles as $cr ){
						
						array_push( $roles, $cr['id'] );
					}
					
					$role_ids = Util::joinForSql( $roles );
					
				}else
					$continue = false;
			}
			
			
			foreach( $children as $cr ){
				
				unset( $options[$cr['id']] );
			}
			
			$options = array_flip( $options );
		}

		return $options;
	}

	/**
	 * returns all the roles for the organizations
	 */
	public function getOrgRoles(){
		
		$roles = $this->org_model->getRoleDetails();

		return $roles;
	}
	
	/**
	 * Returns the ord details by role id
	 * @param unknown_type $role_id
	 */
	public function getRoleDetails( $role_id ){

		$this->OrgRole->load( $role_id );
		return $this->OrgRole->getHash();
	}
	
	/**
	 * returns the scope of roles
	 */
	public function getEntitiesAsOptions(){
		
		return array(
		
			'ORGANIZATION LEVEL' => 'ORG',
			'ZONE LEVEL' => 'ZONE',
			'STORE LEVEL' => 'STORE',
			'CONCEPT LEVEL' => 'CONCEPT'
		);
	}
	
	/**
	 * Checks if role type exists
	 * @param unknown_type $role_type
	 */
	public function roleTypeExists( $role_type ){
		
		$options = $this->getEntitiesAsOptions();
		$options = array_values( $options );

		if( !in_array( $role_type, $options ) )
			throw new Exception( 'Role Type Does Not Exists' );
	}
	
	/**
	 * The Roles with the name
	 * 1) ORG_POC
	 * 2) CAP_POC
	 * 3) SUPER_USER
	 * 4) SUPER USER
	 * 5) SUPERUSER
	 * 6) ROOT
	 * 
	 *  is not allowed
	 *  
	 * @param unknown_type $role_name
	 */
	private function isRoleNameAllowed( $role_name ){
		
		$options = array( 'ORG_POC', 'CAP_POC', 'SUPER_USER', 'SUPER USER', 'SUPERUSER', 'ROOT' );
		
		$role_name = strtoupper( $role_name );
		if( in_array( $role_name, $options ) )
			throw new Exception( $role_name .' Is a reserved key word and can not be used' );
	}
	
	/**
	 * Checks if role name already exists
	 * @param unknown_type $role_type
	 */
	public function isRoleNameExists( $role_name, $update_check = false, $role_id = false ){
		
		$roles = $this->org_model->isRoleNameExists( $role_name );
		
		if( count( $roles ) > 0 && !$update_check )
			throw new Exception( 'Role name already exists' );
			
		elseif( count( $roles ) > 1 && $update_check )
			throw new Exception( 'Role name already exists for other role' );
			
		elseif( count( $roles ) == 1 ){
			
			$db_role_id = $roles[0]['id'];
			if( $db_role_id != $role_id )
				throw new Exception( 'Role Name Exists For Other Role' );
		}
	}
	
	/**
	 * CONTRACT :
	 * 
	 * array(
	 * 	'role_name' => value,
	 * 	'parent_role' => value,
	 * 	'role_type' => value
	 * )
	 */
	public function addRole( $role_params ){

		extract( $role_params );
		
		try{
			
			$this->isRoleNameAllowed( $role_name );
			$this->roleTypeExists( $role_type );
			$this->isRoleNameExists( $role_name );
			
			$this->OrgRole->setRoleName( $role_name );
			$this->OrgRole->setParentRoleId( $parent_role );
			$this->OrgRole->setRoleType( $role_type );
			$this->OrgRole->setOrgId( $this->org_id );
			$this->OrgRole->setApprover( $approver );
			
			$this->OrgRole->setCreatedOn( Util::getCurrentDateTime() );
			$this->OrgRole->setCreatedBy( $this->user_id );
			$this->OrgRole->setLastUpdatedBy( $this->user_id );
			$this->OrgRole->setLastUpdatedOn( Util::getCurrentDateTime() );
			
			$this->OrgRole->insert();
		}catch ( Exception $e ){
			
			return $e->getMessage();
		}
		
		return 'SUCCESS';
	}
	
	/**
	 * CONTRACT :
	 * 
	 * array(
	 * 	'role_name' => value,
	 * 	'parent_role' => value,
	 * 	'role_type' => value
	 * )
	 */
	public function updateRole( $role_id, $role_params ){
		
		extract( $role_params );
		
		try{
			
			$this->isRoleNameAllowed( $role_name );
			$this->roleTypeExists( $role_type );
			$this->isRoleNameExists( $role_name, true, $role_id );
			
			$this->OrgRole->load( $role_id );
			
			$this->OrgRole->setRoleName( $role_name );
			$this->OrgRole->setParentRoleId( $parent_role );
			$this->OrgRole->setRoleType( $role_type );
			$this->OrgRole->setApprover( $approver );
			
			$this->OrgRole->setLastUpdatedBy( $this->user_id );
			$this->OrgRole->setLastUpdatedOn( Util::getCurrentDateTime() );
			
			$this->OrgRole->update( $role_id );
		}catch ( Exception $e ){
			
			return $e->getMessage();
		}
		
		return 'SUCCESS';
	}
	
	public function getVoucherSeriesAsOptions(){
		return Voucher::getVoucherSeriesAsOptions($this->org_id);
	}
	
	public function getAllGateways(){
		return $this->org_model->getAllGateways();	
	}
	
	public function getSetGateways(){
		return $this->org_model->getSetGateways();
	}
	
	public function addOrUpdateGateway($gateway_id, $sender_id, $contact_details, $edit = false){
		
		if($edit)
			return $this->org_model->updateGatewayInfo($gateway_id, $sender_id, $contact_details);
		
		return $this->org_model->addGateway($gateway_id, $sender_id, $contact_details);
	}
	
	public function setPriority($gateway_ids){
		
		$rank = 0;
		foreach( $gateway_ids as $gateway){
			$rank++;
			$this->org_model->setPriority($gateway, $rank);
		}
		
		return true;
	}
	
	public function addCreditsToGateway($value_credit, $bulk_credit, $credits){
		
		return $this->org_model->updateCredits((int)$value_credit ,(int)$bulk_credit , (int)$credits);
	}
	
	
	public function addCreditHistory($value_credit, $bulk_credit, $credits){
		return $this->org_model->CreditHistory((int)$value_credit ,(int)$bulk_credit , (int)$credits , $this->user_id);
	}
	
	public function getSetGatewaysForView($gateway_id = false){
		
		$result = $this->org_model->getSetGatewaysForView($gateway_id);
		if($gateway_id)
			return $result[0];
		else
			return $result;
	}
	
	public function createSourceMapping($gateway_name, $incoming_no, $contact_details){
		//TODO: Add the gateway somehow
		return true;
	}
	
	public function setActions(){
	  //TODO : set Action from the mapping

		return true;
	}
	
	public function getSourceMapping(){
		
		return $this->org_model->getSourceMapping();
	}
	
	public function getActionsAsOptions(){
		
		return $this->org_model->getActionsAsOptions();
	}
	
	public function getMappingRow($id){
		
		return $this->org_model->getMappingRow($id);
	}
	
	public function addSourceMapping($params){
		
		return $this->org_model->addSourceMapping($params);
	}
	
	public function updateSourceMapping($id, $params){
		
		return $this->org_model->updateSourceMapping($id, $params);
	}
	
	public function &getAllEnabledModules(){
		
		return $this->org_model->getModules();        
	}
	

	/**
	 * Returns the Inventory Attributes configured for the 
	 * organization
	 * 
	 * @params nil
	 * @return Array of Inventory Item Details
	 * @author pigol
	 */
	
	public function getInventoryAttributesForDisplay(){
		$inventory = new InventorySubModule();
		return $inventory->getNoOfAttributeValues();		
	}
	
	/**
	 * Returns the details of inventory item imports
	 * 
	 * @params nil
	 * @return Array of InventoryItemImportDetails
	 * @author pigol
	 * 
	 */
	
	public function getInventoryItemImportDetails(){
		$inventory = new InventorySubModule();
		return $inventory->getNoOfInventoryItemImportsByDate();
	}

	/**
	 * Returns the details of bill line item stats
	 * 
	 * @params nil
	 * @return array of inventory line item stats
	 * @author pigol	
	 * 
	 */

	public function getInventoryBillLineItemStats(){
		$inventory = new InventorySubModule();

	}
	

	/**
	 * Returns module as options
	 */
	public function getModulesAsOptions(){
		
		$modules = &$this->getAllEnabledModules();

		$options = array();
		foreach ( $modules as $module ){
			
			$options[$module['name']] = $module['id'];
		}
		
		return $options;
	}
	
	/**
	 * 
	 * @param unknown_type $module_id
	 */
	public function getAllActionsByModuleAsOptions( $module_id ){
		
		$SecurityManager = new SecurityManager();
		return $SecurityManager->getActionsByModuleAsOptions( $module_id );
		
	}
	
	
	/**
	 * returns the countries as options
	 */
	public function getCountriesAsOptions(){
		
		return $this->org_model->getCountriesAsOptions();
	}
	
	/**
	 * 
	 * @return the countries list with out short code
	 */
	public function getCountriesListAsOptions(){
		
		return $this->org_model->getCountriesListAsOptions();
	}
	
	/**
	 * returns the country details for the city
	 * @param unknown_type $city_id
	 */
	public function getCountryDetailsByCityId( $city_id ){
		
		return $this->org_model->getCountryDetailsByCityId( $city_id );
	}

	/**
	 * Returns the state details for the city
	 * @param unknown_type $city_id
	 */
	public function	getStateDetailsByCityId( $city_id ){
		
		return $this->org_model->getStateDetailsByCityId( $city_id );
	}

	/**
	 * Returns The City details by id
	 * @param unknown_type $city_id
	 */
	public function getCityDetailsById( $city_id ){
		
		return $this->org_model->getCityDetailsById( $city_id );
	}

	/**
	 * Returns teh area details by id
	 * @param unknown_type $area_id
	 */
	public function getAreaDetailsById( $area_id ){
		
		return $this->org_model->getAreaDetailsById( $area_id );
	}
	
	/**
	 * 
	 * @param unknown_type $slab_number
	 * @param unknown_type $to_slab_name
	 */
	public function setSlabForAll($slab_number, $to_slab_name){
		
		$db = new Dbase('masters');
		$org_id = $this->currentorg->org_id;
		$this->logger->debug('This is the entry in set Slabs area....................................****');
		
		$sql = "
			UPDATE `loyalty`
			SET
				`slab_name` = '$to_slab_name',
				`last_updated` = NOW()
			WHERE `publisher_id` = '$org_id' AND `slab_number` = '$slab_number'
		";
		return $db->query($sql);
		
	}
	
	/*
	 ** This is the set if there are no slab selected **
	 */
	
	function setDefaultSlabForCustomersWithoutSlab($to_slab_name){
		$db = new Dbase('masters');
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			UPDATE `loyalty`
			SET
				`slab_number` = '0',
				`slab_name` = '$to_slab_name',
				`last_updated` = NOW()
			WHERE `publisher_id` = '$org_id' AND `slab_name` IS NULL
		";
		return $db->query($sql);
	}
	
	
	/*
	 * to get seasonal modules
	 */
	
	function getSeasonalSlabConfig( $id ){
		
		if( $id < 1 )
			return array();
			
		return $this->org_model->getSeasonalSlabConfig( $id );
	}
	
	/**
	 * 
	 * @param $period_from
	 * @param $period_to
	 * @param $stores
	 * @param $zones
	 * @param $params
	 * @param $id
	 */
	function setSeasonalSlabConfiguration( $period_from, $period_to, $stores, $zones, $params, $id  ){
		
		try{
			
			$id = $this->org_model->setSeasonalSlabConfiguration($period_from, $period_to, $stores, $zones, $params, $id );
		}catch( Exception $e ){
			
			return array( $e->getMessage(), false );
		}

		return array( 'SUCCESS', $id );
	}
	/**
	 * Gets the list of slabs that have been declared for this organization
	 * @return array of Slab names. Empty array if none or disabled
	 */
	function getSlabsForOrganization() {
		
		
		if (!$this->areSlabsEnabled()) return array();
		$slablist = $this->config_manager->getKey(CONF_LOYALTY_SLAB_LIST);
		if ($slablist == false || !is_array($slablist)) {
			$slablist = array();
		}
		return $slablist;
	}	


	function areSlabsEnabled() {
			return $this->config_manager->getKey(CONF_LOYALTY_ENABLE_SLABS);
	}

	
	public function addcustomsender($gsm, $cdma,$s_label,$replyemail){
		return $this->org_model->CustomSender($gsm, $cdma,$s_label,$replyemail);
	}
	
	public function getCustSenderDetails(){
		return $this->org_model->getCustSenderDetails();
	}
	
	public function getCreditInfo(){
		return $this->org_model->getCreditInfo();
	}

	public function getCreditHistory(){
		return $this->org_model->getCreditHistory();
	}

	public function getCustomSender(){
		return $this->org_model->getCustomSenderDetails();
	}
	
	public function getTrackers(){
		return $this->org_model->getTrackers();
	}

	
	
	/**
	 * Please add whatever sets you want ( Be Creative. Though remember we have gals in our company now :P )
	 */
	public function getSecretQuestions(  ){
		
		$options = array(
		
			'How many stock options do you have?' => 'How many stock options do you have?',
			'Who was your first online date?' => 'Who was your first online date?',
			'What is your World of Warcraft name?' => 'What is your World of Warcraft name?',
			'What was the name of your first startup?' => 'What was the name of your first startup?',
			'What is your Facebook username?' => 'What is your Facebook username?',
			'What was your first email address?' => 'What was your first email address?',
			'When was the last time you went out on a date?' => 'When was the last time you went out on a date?'
		);
		
		
		return $options;
	}
	
	/*
	 * get the seasonal slabs running
	 * 
	 */
	public function getSeasonalSlabs(){
	 return	$this->org_model->getSeasonalSlabs();
	}
	
	/**
	 * Return The id of the 'OTH' Country Code 
	 */
	public function getOtherCountryId(){
		
		return $this->org_model->getOtherCountryId();
	}

	/**
	 * return The organization of the enitity!!!
	 * 
	 * @param unknown_type $entity_id
	 */
	public function getEntitiesOrgById( $entity_id ){
		
		return $this->org_model->getEntitiesOrgById( $entity_id );
	}
	
	/**
	 * return the organization of the admin user
	 * 
	 * @param unknown_type $user_id
	 */
	public function getUsersOrgById( $user_id ){
		
		return $this->org_model->getUsersOrgById( $user_id );
	}
	
	/**
	 * return the organization for roles
	 * 
	 * @param unknown_type $role_id
	 */
	public function getRolesOrgById( $role_id ){
		
		return $this->org_model->getRolesOrgById( $role_id );
	}
	
	/**
	 * Checks Custom Field Org
	 * 
	 * @param unknown_type $custom_field_id
	 */
	public function getCustomFieldOrgById( $custom_field_id ){
		
		$CustomField = new CustomFields();
		
		return $CustomField->getCustomFieldOrgById( $custom_field_id );
	}
	
	/**
	 * Checks Security Group Org
	 * 
	 * @param $security_group_id
	 */
	public function getSecurityGroupOrgById( $security_group_id ){
		
		$SecurityManager = new SecurityManager();
		
		$groups = $SecurityManager->getGroupById( $security_group_id );
		
		return $groups[0]['org_id'];
	}
	
	/**
	 * it will returns parent id of the current organization
	 */
	public function getParentOrgId(){
		
		return $this->org_model->getParentId();
	}
	
	/**
	 * It will set parent org id for the current org
	 * @param unknown_type $parent_id
	 */
	public function setParentOrgId( $parent_id ){
		
		$this->org_model->setParentId( $parent_id );
		
		$result = $this->org_model->update( $this->org_id );
		
		if( !$result )
			throw new Exception('Parent Organization is not set successfully');
	}
	
	////////////////////////////////////// Added for Mailer Configuration ///////////////////////////
	
		
	/**
	 * get Deatailed Reports Header as Option. 
	 */
	function getDetailedReportsHeadersOptions( $mlm_enabled ){
		
		$legend = $this->getDetailedReportslegend( $mlm_enabled );
	
		$selection_list = array();
		foreach($legend as $code => $meaning){
			$selection_list[Util::beautify($code)] = $code;
		}
	
		return $selection_list;
	}
	
	/**
	 *get Detaied Reports Legend. 
	 */
	function getDetailedReportslegend( $mlm_enabled ){
			
		//create a legend for the report
		$legend = array('store' => 'Name of your store',
				'last_login' => 'Last login time',
				'reg_d' => 'Sign-Ups on Date',
				'reg_wtd' => 'Sign-Ups Week Till Date',
				'reg_mtd' => 'Sign-Ups Month Till Date ( '.$this->data['month_selected'].' )',
				'bills_d' => 'No. of Bills through Intouch on Date',
				'bills_wtd' => 'No. of Bills through Intouch Week Till Date',
				'bills_mtd' => 'No. of Bills through Intouch Month Till Date ( '.$this->data['month_selected'].' )',
				'bills_amount_d' => 'Sales on Date',
				'bills_amount_wtd' => 'Sales for Week Till Date',
				'bills_amount_mtd' => 'Sales for Month Till Date ( '.$this->data['month_selected'].' )',
				'total_sales_non_loyalty_d'=>'Total Sales Date(Non Loyalty)',
				'total_sales_non_loyalty_wtd'=>'Total Sales Week Till Date(Non Loyalty)',
				'total_sales_non_loyalty_mtd'=>'Total Sales Month Till Date(Non Loyalty)( '.$this->data['month_selected'].' )',
				'total_sales_d' => 'Total Sales Date',
				'total_sales_wtd' => 'Total Sales Week Till Date',
				'total_sales_mtd' => 'Total Sales Month Till Date( '.$this->data['month_selected'].' )',
				'tracked_bills_d' => 'No. of Bills through Loyalty Tracker on Date',
				'tracked_bills_wtd' => 'No. of Bills through Loyalty Tracker Week Till Date',
				'tracked_bills_mtd' => 'No. of Bills through Loyalty Tracker Month Till Date ( '.$this->data['month_selected'].' )',
				'not_interested_bills_d' => 'No. of Uninterested Bills on Date',
				'not_interested_bills_wtd' => 'No. of Uninterested Week Till Date',
				'not_interested_bills_mtd' => 'No. of Uninterested Month Till Date',
				'rep_bills_d' => 'No. of Bills by Repeat Customers on Date',
				'rep_bills_wtd' => 'No. of Bills by Repeat Customers Week Till Date',
				'rep_bills_mtd' => 'No. of Bills by Repeat Customers Month Till Date ( '.$this->data['month_selected'].' )',
				'rep_sales_d' => 'Sales by Repeat Customers on Date',
				'rep_sales_wtd' => 'Sales by Repeat Customers Week Till Date',
				'rep_sales_mtd' => 'Sales by Repeat Customers Month Till Date ( '.$this->data['month_selected'].' )',
				'redeem_d' => 'No. of points Redeemed on Date',
				'redeem_wtd' => 'No. of points Redeemed Week Till Date',
				'redeem_mtd' => 'No. of points Redeemed Month Till Date ( '.$this->data['month_selected'].' )',
				'tot_pts_d' => 'Total points ( Issued + Awarded ) on Date',
				'tot_pts_wtd' => 'Total points ( Issued + Awarded ) on Week Till Date',
				'tot_pts_mtd' => 'Total points ( Issued + Awarded ) on Month Till Date ( '.$this->data['month_selected'].' )',
				'issued_vouchers_d' => 'No. of Vouchers Issued on Date',
				'issued_vouchers_wtd' => 'No. of Vouchers Issued on Week Till Date',
				'issued_vouchers_mtd' => 'No. of Vouchers Issued on Month Till Date ( '.$this->data['month_selected'].' )',
				'redeemed_vouchers_d' => 'No. of Vouchers Redeemed on Date',
				'redeemed_vouchers_wtd' => 'No. of Vouchers Redeemed on Week Till Date',
				'redeemed_vouchers_mtd' => 'No. of Vouchers Redeemed on Month Till Date ( '.$this->data['month_selected'].' )'
		);
	
		//add it to the report only if mlm is enabled
		if($mlm_enabled){
			$legend['refs_sent_d'] = 'Total Referrals Sent on Date';
			$legend['refs_sent_wtd'] = 'Total Referrals Sent Week Till Date';
			$legend['refs_sent_mtd'] = 'Total Referrals Sent Month Till Date';
	
			$legend['refs_joined_d'] = 'Total Referrals Joined on Date';
			$legend['refs_joined_wtd'] = 'Total Referrals Joined Week Till Date';
			$legend['refs_joined_mtd'] = 'Total Referrals Joined Month Till Date';
		}
	
		return $legend;
	}
		
	/**
	 * get all the summary reports rows. 
	 */
	function getSummaryReportRowsAll(){
		
		$options = array(
				'# new customers',
				'# of recorded bills',
				'# of tracked bills',
				'# of repeat bills',
				'# uninterested bills',
				'# points issued on bill amount',
				'# points awarded (bonus,supgrade etc)',
				'# points redeemed',
				'# SMS sent',
				'# Refs Sent',
				'# Refs Joined',
				'# of vouchers issued',
				'# of vouchers redeemed',
				'Total Sales (Rs)'
		);
		
		return $options;
	}

	public function getOrgDetails(){

		return $this->org_model->getOrgDetails();
	}
	
	/**
	 * return the list of available templates.
	 * @param unknown_type $org_id
	 * @param unknown_type $template_ide template.
	 */
	public function getTemplate( $org_id , $template_id = false ){

		return $this->org_model->getTemplate( $org_id , $template_id );
	}
	
	/**
	 * This will assigne the template to particular ogranization.
	 * @param unknown_type $id
	 */
	public function assignTemplate( $id ){
		
		return $this->org_model->assignTemplate( $id , $this->user_id );
	}
	
	/**
	 * This  will return the assigned template for particular organization.
	 * @param unknown_type $type
	 */
	public function getAssignedTeamplate( $type ){
		
		return $this->org_model->getAssignedTemplate( $type );
	}
	
	/**
	 * This will insert into oganization_service.
	 * @param unknown_type $params
	 */
	public function insertOrgServiceConfig( $params ){
		
		$email = array( 'file_name' => $params['file_name'] , 
						'subject' => $params['subject'], 
						'email_body' => $params['email_body']
					 );
		$email_params = json_encode( $email );
		
		$params['user_id'] = $this->user_id;
		
		return $this->org_model->insertOrgServiceConfig( $params , $email_params );
	}
	
	/**
	 * This method will return email paramter from organization_service.
	 * @param unknown_type $service_id
	 */
	public function getEmailParams( $service_id ){
		
		return $this->org_model->getEmailParams( $service_id );
	}
	
	/**
	 * This will update an Email Params.
	 * @param unknown_type $service_id
	 * @param unknown_type $params
	 */
	public function updateEmailParams( $params ){
	
		$service_id = $params['templates'];
		
		return $this->org_model->updateEmailParams( $service_id, $params , $this->user_id );
	}
	
	public function loadOrganizationServiceTemplate( $type ){
		
		return $this->org_model->loadOrganizationTemplate( $type );
	}

	public function customfields( $scope = "", $include_disabled = false ){
	
		$result = array();
		$org_id = $this->org_id;
		$organization_model_extension = new ApiOrganizationModelExtension();
		$fields = $organization_model_extension->getcustomfields( $org_id, $scope, $include_disabled);
			
		if(!$fields ){
			return false;
		}
	
		/*$customfields['field'] = array();
	
		foreach($fields as $item_data)
		{
			$result['name'] = $item_data['name'];
			$result['label'] = $item_data['label'];
			$result['type'] = $item_data['type'];
			$result['datatype'] = $item_data['datatype'];
			$result['default'] = $item_data['default'];
			$result['phase'] = $item_data['phase'];
			$result['position'] = $item_data['position'];
			$result['rule'] = $item_data['rule'];
			$result['regex'] = $result['regex'];
			$result['error'] = $result['error'];
			$result['options'] = $item_data['attrs'];
			$result['scope'] = $item_data['scope'];
			$result['is_mandatory'] = $item_data['is_compulsory'];
			$result['is_disabled'] = $item_data['is_disabled'];
			
			
				
			array_push($customfields['field'],$result);
		}
	
		return $customfields;*/
		return $fields;
	}
	/*
	 * ========================================== Client Version Mapping =================================================
	 */
	
	/**
	 * Add Intouch Client Version
	 * @param unknown_type $params
	 */
	public function addInTouchClientVersion( $params ){
		
		return $this->org_model->addInTouchClientVersion
								( 
									$params['version'], 
									$params['change_log'], 
									$this->currentuser->user_id 
							    );
	}
	
	/**
	 * returns the detail of each version
	 */
	public function getInTouchClientVersions(){
		
		return $this->org_model->getInTouchClientVersions();
	}
	
	/**
	 * get organization version details
	 */
	public function getOrganizationVersionDetails(){
		
		return $this->org_model->organizationVersionDetails();
	}
	
	/**
	 * setting version for client
	 */
	public function setVersionForClient( $version_id, $stores , $org_id ){
		
		
		$insert_array = array();
		if( is_array( $org_id )){
			
			$this->org_model->updateClientVersion( $stores, $org_id );
						
			$cnt = 0;	
			foreach ( $stores as $s_id ){
					
				array_push ( 
							 $insert_array, 
							"('$org_id[$cnt]', 
							  '$s_id', 
							  '$version_id', 
							  NOW(), 
							  1, 
							  '$this->user_id')"
						 );
				$cnt++;					
			}
				
		}else{
				$this->org_model->updateClientVersion( $stores, $org_id, true );
				
				foreach( $stores as $s_id ){
			
					array_push( 
							   $insert_array, 
									"('$org_id', 
									  '$s_id', 
									  '$version_id', 
							          NOW(), 
							          1, 
							          '$this->user_id')"
							 );
				}
		}
		
		return $this->org_model->setClientVersion( $insert_array );
	}
	
	//////////////////////////////////////////// Setup Assistant Related functions start from here //////////////////////////////////////
	/**
	 * 
	 * @param unknown_type $country_id
	 */
	public function getStateByCountryIdAsOptions( $country_id ){
		
		return $this->org_model->getStateByCountryId( $country_id );
	}
	
	public function getDomainsAsOptions(){
		
		return $this->org_model->getDomainsAsOptions();
	}
	
	public function getCityAsOptions(){
	
		return $this->org_model->getCityDetailsAsOptions();
	}
	
	public function getOrgDetailByOrgId( $org_id ){

		return $this->org_model->getOrgDetailById( $org_id );
	}
	
	///////////////////////////////////////// Clienteling Related Functions start from here ///////////////////////////////////////////////
	
	/**
	 *
	 * @param unknown_type $org_id
	 * @return returns array that contains, 
	 * 			average basket size for the organization, 
	 * 			average amount of the transaction,
	 * 			number of customers of the organization,
	 * 			number of the products of the organization. 
	 */
	public function getStatistics()
	{
		global $currentorg, $cfg;
        $statistics = array();
        $cm = new CurlManager();
        $this->logger->debug("Making call to conquest api");
        $res = $cm->get($cfg["conquest_api_endpoint"].$currentorg->org_id."?key=".$cfg['conquest_api_key'], 0);
        $this->logger->debug("Conquest api response: " . $res);
        $conquest_api_response = json_decode($res, true);
        $this->logger->debug(print_r($conquest_api_response, true));
        
        // TODO : Fix this; first from cache ; then call the api
        if($conquest_api_response["status"]["message"] == "Success")
        {
            $this->logger->debug("Conquest API call successful");
            $statistics['avg_basket'] = $conquest_api_response["organization"]["statistics"]["transaction"]["avgBasketSize"];
            $statistics['avg_transaction_value'] = $conquest_api_response["organization"]["statistics"]["transaction"]["avgValue"];
            $statistics['customers'] = $conquest_api_response["organization"]["statistics"]["customer"]["count"];
            $statistics['products'] = $conquest_api_response["organization"]["statistics"]["inventory"]["count"];
            $statistics['stores'] = $conquest_api_response["organization"]["statistics"]["store"]["count"];
        }
        else
        {
            $this->logger->debug("Conquest API call unsuccessful. Retrieving from db.");
            $statistics['avg_basket'] = $this->getAverageBillLineItem();
            $statistics['avg_transaction_value'] = $this->getAverageTransactionValue();
            $statistics['customers'] = $this->getNumberOfCustomers();
            $statistics['stores'] = $this->getNumberOfStores();
            $statistics['products'] = $this->getNumberOfProducts();
        }
        
        return $statistics;
	}
	
	public function getOrgPerformance(){
		include_once 'apiModel/class.ApiLoyaltyTrackerModelExtension.php';
		$model = new ApiLoyaltyTrackerModelExtension();
		return  $model->getOrgPerformance($this->currentorg->org_id);
	}
	
	/////////////////////////////////////// function for org statistics that works with memcache //////////////////////////////////
	public function getAverageBillLineItem()
	{
		$number_of_bills = $this->getNumberOfBillsForOrg();
		$number_of_bill_lineitems = $this->getNumberOfBillLineItemsForOrg();
		$this->logger->debug("Number of Bills: $number_of_bills");
		$this->logger->debug("Number of lineitems: $number_of_bill_lineitems");
		
		return ($number_of_bill_lineitems / $number_of_bills);
	}

	public function getAverageTransactionValue()
	{
		$bill_count = $this->getNumberOfBillsForOrg();
		$total_amount = $this->getTotalTransactionValue();
		
		return $total_amount / $bill_count;
	}
	
	public function getNumberOfCustomers()
	{
		
		$key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$orgCustomerCount.$this->org_id;
		try
		{
			$memcache = MemcacheMgr::getInstance();
			$number_of_cust = $memcache->get($key);
		}
		catch(Exception $e)
		{
			$this->logger->error("Error in Loading Key [$key] from Memcache trying from database");
			
			$number_of_cust = $this->OrgModelExtension->getOrgCustomer();
			
			Util::setNumberOfCustomersInMemcache($this->org_id, $number_of_cust);
		}
		return $number_of_cust;
	}	
	
	public function getNumberOfStores()
	{
		$key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$orgStoreCount.$this->org_id;
		try
		{
			$memcache = MemcacheMgr::getInstance();
			$number_of_stores = $memcache->get($key);
		}
		catch(Exception $e)
		{
			$this->logger->error("Error in Loading Key [$key] from Memcache trying from database");
		
			$number_of_stores = $this->OrgModelExtension->getStoresCount();
		
			Util::setNumberOfStoresInMemcache($this->org_id, $number_of_stores);
		}
		return $number_of_stores;
	}
	
	public function getNumberOfProducts()
	{
		
		$key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$orgProductCount.$this->org_id;
		try
		{
			$memcache = MemcacheMgr::getInstance();
			$number_of_prod = $memcache->get($key);
		}
		catch(Exception $e)
		{
			$this->logger->error("Error in Loading Key [$key] from Memcache trying from database");
				
			$number_of_prod = $this->OrgModelExtension->getOrgProductCount();
				
			Util::setNumberOfProductsInMemcache($this->org_id, $number_of_prod);
		}
		return $number_of_prod;
	}	
	
	
	
	public function getNumberOfBillsForOrg()
	{
		$key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$orgBillCount.$this->org_id;
		try
		{
			$memcache = MemcacheMgr::getInstance();
			$number_of_bills = $memcache->get($key);
		}
		catch(Exception $e)
		{
			$this->logger->error("Error in Loading Key [$key] from Memcache trying from database");
			 
			$number_of_bills = $this->OrgModelExtension->getNumberOfBills();
			 
			Util::setNumberOfBillsInMemcache($this->org_id, $number_of_bills);
		}
		return $number_of_bills;
	}
	
	public function getNumberOfBillLineItemsForOrg()
	{
		$key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$orgLineItemCount.$this->org_id;
		try
		{
			$memcache = MemcacheMgr::getInstance();
			$number_of_bill_lineitem = $memcache->get($key);
		}
		catch(Exception $e)
		{
			$this->logger->error("Error in Loading Key [$key] from Memcache trying from database");
	
			$number_of_bill_lineitem = $this->OrgModelExtension->getNumberOfBillLineItems();
	
			Util::setNumberOfBillLineitemsInMemcache($this->org_id, $number_of_bill_lineitem);
		}
		return $number_of_bill_lineitem;
	}
	
	public function getTotalTransactionValue()
	{
		$key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$orgBillTotalValue.$this->org_id;
		try
		{
			$memcache = MemcacheMgr::getInstance();
			$total_transaction_value = $memcache->get($key);
		}
		catch(Exception $e)
		{
			$this->logger->error("Error While fetching key[$key] from memcache");
			
			$total_transaction_value = $this->OrgModelExtension->getTotalTransactionValue(); 
			Util::setTotalTransactionValue($this->org_id, $total_transaction_value);
		}
		return $total_transaction_value;
	}
	
	public function getStores(){
	
		$org_model_ext = new ApiOrganizationModelExtension();
		$this->logger->debug( "Getting Stores list for org : $this->org_id" );
		$stores = $org_model_ext->getStores( $this->org_id );
	
		if( !$stores ){
				
			$this->logger->error( "Error in fetching stores from org model extension" );
			return false;
		}
	
		foreach( $stores as $key => $store ){
				
			$stores[$key]['location'] = array(
					'latitude' => $store['lat'],
					'longitude' => $store['long'],
			);
			//remove old key value, since it is grouped under location
			unset( $stores[$key]['lat'] );
			unset( $stores[$key]['long'] );
		}
	
		$result['stores']['store'] = $stores;
		return $result;
	}
	
	public function getStoresAsEntities($ids = ""){
	
		$org_model_ext = new ApiOrganizationModelExtension();
		$this->logger->debug( "Getting Stores list for org : $this->org_id" );
		$stores = $org_model_ext->getStores( $this->org_id, $ids );
	
		if( !$stores ){
	
			$this->logger->error( "Error in fetching stores from org model extension" );
			return false;
		}
	
		foreach( $stores as $key => &$store ){
            //Fetch from hierarchy
            if( !isset($store['timezone_label']) && !isset($store['timezone_offset']) ){
                $this->logger->debug("timezone not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->StoreController->getStoreTimezoneFromHierarchy($store['id']) ;
                $store['timezone_label'] = $tz['timezone_label'];
                $store['timezone_offset'] = $tz['timezone_offset'];
                //TODO : add source info from where this tz is coming
            }

            if( !isset($store['currency_symbol']) && !isset($store['currency_code']) ){
                $this->logger->debug("Currency not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->StoreController->getStoreCurrencyFromHierarchy($store['id']) ;
                $store['currency_symbol'] = $tz['currency_symbol'];
                $store['currency_code'] = $tz['currency_code'];
                //TODO : add source info from where this tz is coming
            }

            if( !isset($store['language_code']) && !isset($store['language_locale']) ){
                $this->logger->debug("Language not available for this store. Need to fetch from zone hierarchy");
                $tz = $this->StoreController->getStoreLanguageFromHierarchy($store['id']) ;
                $store['language_code'] = $tz['language_code'];
                $store['language_locale'] = $tz['language_locale'];
                //TODO : add source info from where this tz is coming
            }
	
			$stores[$key]['location'] = array(
					'latitude' => $store['lat'],
					'longitude' => $store['long'],
			);
			//remove old key value, since it is grouped under location
			unset( $stores[$key]['lat'] );
			unset( $stores[$key]['long'] );
		}
		
		if(empty($ids))
		{
			return $stores;
		}
		
		if(isset($stores) && count($stores) > 0)
		{
			$new_stores = array();
			foreach ($stores as $store)
			{
				$new_stores[$store['id']] = $store;
			}
			return $new_stores;
		}
		else
			return null;
	}
	
	public function getTills( $ids = "", $filters =array()){
		
		$org_model_ext = new ApiOrganizationModelExtension();
		$this->logger->debug("Getting Zone List for Org : $this->org_id");
		$tills = $org_model_ext->getTills($this->org_id, $ids, $filters );

        foreach ($tills as &$till) {
            if (!isset($till['timezone_label']) && !isset($till['timezone_offset'])) {
                $this->logger->debug("Timezone not available for this till. Need to fetch from zone hierarchy");
                $tz = $this->StoreTillController->getTillTimezoneFromHierarchy($till['id']);
                $till['timezone_label'] = $tz['timezone_label'];
                $till['timezone_offset'] = $tz['timezone_offset'];
                //TODO : add source info from where this tz is coming
            }

            if( !isset($till['currency_symbol']) && !isset($till['currency_code']) ){
                $this->logger->debug("Currency not available for this till. Need to fetch from zone hierarchy");
                $tz = $this->StoreTillController->getTillCurrencyFromHierarchy($till['id']) ;
                $till['currency_symbol'] = $tz['currency_symbol'];
                $till['currency_code'] = $tz['currency_code'];
                //TODO : add source info from where this tz is coming
            }

            if( !isset($till['language_code']) && !isset($till['language_locale']) ){
                $this->logger->debug("Language not available for this till. Need to fetch from zone hierarchy");
                $tz = $this->StoreTillController->getTillLanguageFromHierarchy($till['id']) ;
                $till['language_code'] = $tz['language_code'];
                $till['language_locale'] = $tz['language_locale'];
                //TODO : add source info from where this tz is coming
            }

        }
		if(empty($ids))
		{
			return $tills;
		}
		if(isset($tills) && count($tills) > 0)
		{
			$new_tills = array();
			foreach ($tills as $till)
			{
				$new_tills[$till['id']] = $till;
			}
			return $new_tills;
		}
		else 
			return null;
	}
	
	/**
	 * 
	 * @param unknown_type $ids
	 * @return array of zones, index of this array will be zone ids if $ids is not blank.
	 */
	public function getZones( $ids = "" , $filterArr = array() ){
		
		$org_model_ext = new ApiOrganizationModelExtension();
		$this->logger->debug("Getting Zone List for Org : $this->org_id");
		$zones = $org_model_ext->getZones($this->org_id, $ids, $filterArr);

		$db = new Dbase("masters");

        foreach ($zones as &$zone) {
	    	if($filterArr["exclude_locale"] != 1){
            if (!isset($zone['timezone_label']) && !isset($zone['timezone_offset'])) {
                $tz = $this->ZoneController->getZonesTimezoneFromHierarchy($zone['id']);
                $zone['timezone_label'] = $tz['timezone_label'];
                $zone['timezone_offset'] = $tz['timezone_offset'];
                //TODO : add source info from where this tz is coming
            }
            if (!isset($zone['language_code']) && !isset($zone['language_locale'])) {
                $tz = $this->ZoneController->getZonesLanguageFromHierarchy($zone['id']);
                $zone['language_code'] = $tz['language_code'];
                $zone['language_locale'] = $tz['language_locale'];
                //TODO : add source info from where this tz is coming
            }
            if (!isset($zone['currency_symbol']) && !isset($zone['currency_code'])) {
                $tz = $this->ZoneController->getZonesCurrencyFromHierarchy($zone['id']);
                $zone['currency_symbol'] = $tz['currency_symbol'];
                $zone['currency_code'] = $tz['currency_code'];
                //TODO : add source info from where this tz is coming
            }
	        }
	        else{
	        	unset($zone["timezone_label"]);
	        	unset($zone["timezone_offset"]);
	        	unset($zone["language_code"]);
	        	unset($zone["language_locale"]);
	        	unset($zone["currency_symbol"]);
	        	unset($zone["currency_code"]);	
	        }

	        if($filterArr["details"] == 'basic'){
	        	unset($zone["reporting_email"]);
	        	unset($zone["reporting_mobile"]);
	        	unset($zone["level"]);
	        	unset($zone["currencies"]);
	        	unset($zone["timezones"]);
	        	unset($zone["languages"]);
	        	unset($zone["time_zone_id"]);
	        	unset($zone["currency_id"]);
	        	unset($zone["language_id"]);
	        	unset($zone["last_updated_by"]);
	        	unset($zone["last_updated_on"]);
	        	
	        }
	        if($filterArr["include_parent"]){
	        	$zone["parent"] = array(
	        		"id" => $zone["parent_id"],
	        		"code" => $zone["parent_code"],
	        		"name" => $zone["parent_name"],
	        		"description" => $zone["parent_description"],
	        	);
	        	unset($zone["parent_id"]);
	        	unset($zone["parent_code"]);
	        	unset($zone["parent_name"]);
	        	unset($zone["parent_description"]);
	        	
	        }
	        if($filterArr["sub_entities_count"] ){
	        	$sql = "SELECT oe.type as entity_type, count(*) as count
	        		from masters.org_entities oe 
	        		INNER JOIN org_entity_relations as oer
	        		ON oer.org_id = oe.org_id and oer.child_entity_id = oe.id and oe.is_active=1
	        		WHERE oer.parent_entity_id = ". $zone["id"] 
	        		." AND oe.org_id =" .$this->org_id
	        		." GROUP by entity_type ";
	        	$res = $db->query_hash($sql, "entity_type" , array("count"));
	        	 
	        	$zone["sub_entities_count"] = array(
	        		'zone' => $res["ZONE"] + 0,
	        		'concept' => $res["CONCEPT"]+ 0,
	        		'store' => $res["STORE"]+ 0,
	        		'store_server' => $res["STORE_SERVER"]+ 0,
	        		'till' => $res["TILL"]+ 0,
	        		);
	        }
        }
		
		if(empty($ids))
		{
			return $zones;
		}
		
		if(isset($zones) && count($zones) > 0)
		{
			$new_zones = array();
			foreach ($zones as $t_zone)
			{
				$new_zones[$t_zone['id']] = $t_zone;
			}
			return $new_zones;
		}
		else 
			return null;
	}
	
	/* Returns the child_entity_id to parent_entity_id mapping
	 *  */
	public function getEntityParentMapping( $ids, $type )
	{
		$db = new Dbase('masters');
		
		$sql = "SELECT parent_entity_id, child_entity_id FROM org_entity_relations
						WHERE org_id = $this->org_id AND parent_entity_type = '$type' AND child_entity_id IN ($ids)";
		$res = $db->query( $sql );
		
		$new_arr = array();
		foreach ( $res as $item ){
			$buf = $item['child_entity_id'];
			$new_arr[$buf] = $item['parent_entity_id'];
		}
		return $new_arr;
	}
	
	/**
	 *
	 * @param unknown_type $ids
	 * @return array of concept, index of this array will be concept ids if $ids is not blank.
	 */
	public function getConcepts( $ids = "" , $filterArr = array() ){
	
		$org_model_ext = new ApiOrganizationModelExtension();
		$this->logger->debug("Getting Concept List for Org : $this->org_id");
		$concepts = $org_model_ext->getConcepts($this->org_id, $ids, $filters);
	
		/*if(empty($ids))
		{
			return $concepts;
		}*/
	
		$db = new Dbase("masters");
		if(isset($concepts) && count($concepts) > 0)
		{
			$new_concepts = array();
			foreach ($concepts as &$concept)
			{
				if($filterArr["details"] == 'basic'){
					unset($concept["reporting_email"]);
					unset($concept["reporting_mobile"]);
					unset($concept["level"]);
					unset($concept["currencies"]);
					unset($concept["timezones"]);
					unset($concept["languages"]);
					unset($concept["time_zone_id"]);
					unset($concept["currency_id"]);
					unset($concept["language_id"]);
					unset($concept["last_updated_by"]);
					unset($concept["last_updated_on"]);
		        	unset($concept["timezone_label"]);
		        	unset($concept["timezone_offset"]);
		        	unset($concept["language_code"]);
		        	unset($concept["language_locale"]);
		        	unset($concept["currency_symbol"]);
		        	unset($concept["currency_code"]);	
					
				}
				if($filterArr["include_parent"]){
					$concept["parent"] = array(
	        		"id" => $concept["parent_id"],
	        		"code" => $concept["parent_code"],
	        		"name" => $concept["parent_name"],
	        		"description" => $concept["parent_description"],
					);
					unset($concept["parent_id"]);
					unset($concept["parent_code"]);
					unset($concept["parent_name"]);
					unset($concept["parent_description"]);

				}
				if($filterArr["sub_entities_count"] ){
					$sql = "SELECT oe.type as entity_type, count(*) as count
	        		from masters.org_entities oe 
	        		INNER JOIN org_entity_relations as oer
	        		ON oer.org_id = oe.org_id and oer.child_entity_id = oe.id and oe.is_active=1
	        		WHERE oer.parent_entity_id = ". $zone["id"] 
					." AND oe.org_id =" .$this->org_id
					." GROUP by entity_type ";
					$res = $db->query_hash($sql, "entity_type" , array("count"));
	     
					$concept["sub_entities_count"] = array(
	        		'zone' => $res["ZONE"] + 0,
	        		'concept' => $res["CONCEPT"]+ 0,
	        		'store' => $res["STORE"]+ 0,
	        		'store_server' => $res["STORE_SERVER"]+ 0,
	        		'till' => $res["TILL"]+ 0,
					);
				}
				$new_concepts[$concept['id']] = $concept;
			}
			return $new_concepts;
		}
		else
			return null;
	}
	/**
	 * 
	 * Get All Cached Files for the org.
	 */
	public function getCachedFiles( $id = ''){
		
		$cached_model = new CachedFilesModelExtensionModel();
		return $cached_model->getAll( $this->org_id, $id );
	}
	
	/**
	 * update Cached File.
	 */
	public function updateCachedFilesDetails( $params ){

		$cached_model = new CachedFilesModelExtensionModel();
		
		try{
			
			$cached_model->load( $params['id'] );

			$cached_model->setAction($action);
			$cached_model->setCreatedBy( $this->user_id );
			$cached_model->setAction( $params['action'] );
			$cached_model->setCreatedTime( $params['created_time'] );
			$cached_model->setFileExtension( $params['file_extension'] );
			$cached_model->setFileHash( $params['file_hash'] );
			$cached_model->setFileKey( $params['file_key'] );
			$cached_model->setIsLocked( $params['is_locked']);
			$cached_model->setIsValid( $params['is_valid'] );
			$cached_model->setModule( $params['module'] );
			$cached_model->setOrgId( $this->org_id );
			
			$status = $cached_model->update( $params['id'] );
			
			return $status;
			
		}catch(Exception $e ){
			
			return $e->getMessage();
		}
		
	}
	
	/**
	 * returns Basic Details of the current Organization
	 */
	public function getOrganizationBasicDetails()
	{
		return $this->OrgModelExtension->getOrganizationBasicDetails($this->org_id);
	}

	/*
	 * Get Actions for the current organization
	 */
	public function getAllActionsForOrg( ){
		
		$cached_model = new CachedFilesModelExtensionModel();
		$cached_actions = array();
		
		 $result = $cached_model->getAll( $this->org_id );
		 
		$this->logger->debug(" Getting all actions"); 
		foreach( $result as $cached_data )
		{
			 $cached_actions[] = $cached_data['action'] ;
		}
		
		$cached_actions = array_values(array_unique( $cached_actions ));
		
		return $cached_actions;
	}
	
	/*
	 * Obtain the cached files for the search parameters
	 */
	public function getSearchedCachedFiles( $params = array()){
	
		$cached_model = new CachedFilesModelExtensionModel();
		return $cached_model->getSearchedFiles( $this->org_id, $params );
	}
	
	/**
	 * update organization details when base country is
	 * changed from org profile
	 * @param unknown_type $org_id
	 * @param unknown_type $params
	 */
	public function updateOrgDetailsLocation( $country ){
		
		$this->logger->debug( 'updating address for org : '.$this->org_id );
		$this->org_model->setAddress( NULL );
		$status = $this->org_model->update( $this->org_id );
		$this->logger->debug( 'org updated '.print_r( $status , true ) );
		
		$this->Org_details_model->load( $this->org_id );
		$this->Org_details_model->setCountryId( $country );
		$this->Org_details_model->setStateId( -1 );
		$this->Org_details_model->setCityId( -1 );
		$this->Org_details_model->setLocality( '' );
		$this->Org_details_model->setPincode( '' );
		$this->Org_details_model->setUpdatedBy( $this->user_id );
		$this->Org_details_model->setUpdatedOn( 'NOW()' );
		$update_status = $this->Org_details_model->update( $this->org_id );
		
		$this->logger->debug( 'org details updated for org '.$this->org_id );
	}
	
	/**
	 * Returns the details of all countries which are supported for this organization
	 * @return List of supported countries for the org
	 */
	
	public function getSupportedCountriesDetailsAsOptions(){
	
		return $this->org_model->getDetailsForSupportedCountries();
	}
	
	public function getCountriesLastModifiedDate(){
		return $this->OrgModelExtension->getCountryLastModifiedDate();
	}
	
	public function getOrgConfigs( $name, $scope, $module, $value_type, $include_all = false){
	
		global $currentorg;
		$config_manager = new ConfigManager();
		$this->logger->debug( "Organization Configs: GET" );
		if( !$name && !$scope && !$module && !$value_type ){
	
			$this->logger->debug( "Query Params not passed - returning all config keys matching the pattern CONF_" );
			$name_pattern = $include_all ? '' : 'CONF_%';
			$arr = $config_manager->getKeys( $name_pattern, "", "", "" );
		}elseif( isset( $name ) && !empty( $name ) ) {
	
			$this->logger->debug( "Fetching Config keys with names ".$name );
			$names = explode( ",", $name );
			$arr = $config_manager->getKeys( $names, "", "", "" );
				
		}else{
	
			$this->logger->debug( "Query Params passed: ".print_r( $query_params, true ) );
			//TODO: Support for checking multiple scopes
			$scope = ( !$scope )? "" : $scope;
			$module = ( !$module )? "" : $module;
			$value_type = ( !$value_type )? "" : $value_type;
				
			$this->logger->debug( "Fetching Organization configurations satisfying given parameters:" );
			$arr = $config_manager->getKeys( "", $scope, $module, $value_type );
		}
			
		$country_code_config_present = false;
		$country_code_supported_config_present = false;
		foreach ($arr as $index => $kv) {
			if ( ($kv['key'] == CONF_MLM_SLAB_POINTS_PERCENTAGE)
					|| ($kv['key'] == CONF_REPORT_EMAIL_HEADERS)
					|| ($kv['key'] == CONF_SUMMARY_REPORT_EMAIL_HEADERS)
					|| ($kv['key'] == CONF_BILL_IMPORT_FIELD_MAPPING)
					|| ($kv['key'] == CONF_BILL_IMPORT_FIELD_NAME_MAPPING)
					|| ($kv['key'] == CONF_BILL_IMPORT_STORE_MAPPING)
					|| ($kv['key'] == CONF_INVENTORY_FIELD_MAPPING)
					|| ($kv['key'] == CONF_INVENTORY_FIELD_NAME_MAPPING)
					|| ($kv['key'] == CONF_MONDRIAN_STORE_DIMENSION_ROOT)
					|| ($kv['key'] == CONF_ANALYSIS_PRODUCT_CATEGORY_ATTRIBUTE)
					|| ($kv['key'] == CONF_LOYALTY_POINTS_EXPIRY_REMINDER_SMS_TEMPLATE)
					|| ($kv['key'] == CONF_TRACKER_REPORTING_EMAIL)
					|| ($kv['key'] == CONF_TRACKER_PRIORITY_REPORTING_EMAIL)
			)
				$arr[$index]['value'] = '';
			
			if($kv['key'] == CONF_CLIENT_POINT_TO_CURRENCY_RATIO)
			{
				//points to currency ratio from points engine 
				try{
					$key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$pointsToCurrencyRatio.$this->org_id;
					try
						{
							$memcache = MemcacheMgr::getInstance();
							$points_to_currency_ratio = $memcache->get($key);
						} catch (Exception $e){
							$pointsEngineServiceController = new PointsEngineServiceController();
	        				$points_to_currency_ratio = $pointsEngineServiceController->getPointsCurrencyRatio($currentorg->org_id);
							$memcache = MemcacheMgr::getInstance();
							$memcache->set($key, $points_to_currency_ratio, CacheKeysTTL::$pointsToCurrencyRatio);
						}
					
					$arr[$index]['value'] = $points_to_currency_ratio;
					$arr[$index]['modules'] = 'CLIENT';
					$arr[$index]['value_type'] = 'NUMERIC';
					$arr[$index]['scopes'] = 'ORG';
					
				} catch (Exception $e){
					$this->logger->debug("Fetching points to currency ratio from points engine failed");	
				}		
			}
			 
			if($kv['key'] == CONF_CLIENT_DEFAULT_COUNTRY)
				$country_code_config_present = true;
	
			if($kv['key'] == CONF_CLIENT_INTERNATIONAL_COUNTRIES_SUPPORTED)
				$country_code_supported_config_present = true;
		}
	
		if( !$name && !$scope && !$module && !$value_type ){
	
			//Send 91 by default for country code
			if(!$country_code_config_present && $arr )
				array_push( $arr, array(
						'key' => CONF_CLIENT_DEFAULT_COUNTRY,
						'scopes' => 'ORG',
						'value_type' => 'NUMERIC',
						'modules' => 'CLIENT',
						'value' => '1' ) );
			//Send India as default supported country code
			if(!$country_code_supported_config_present && $arr )
				array_push( $arr, array(
						'key' => CONF_CLIENT_INTERNATIONAL_COUNTRIES_SUPPORTED,
						'scopes' => 'ORG',
						'value_type' => 'STRING',
						'modules' => 'CLIENT',
						'value' => json_encode( array( '1' ) ) ) );
				
			global $currentuser;
			$storeController = new ApiStoreController();
			$base_store_id = $storeController->getBaseStoreId();
			$store_details = $storeController->getInfoDetails($base_store_id);
				
			//Sending base store name
			array_push( $arr, array(
					'key' => CONF_CLIENT_STORE_NAME,
					'scopes' => 'ORG',
					'value_type' => 'STRING',
					'modules' => 'CLIENT',
					'value' => $store_details[0]['store_name'] ));
				
			//Sending base store id
			array_push( $arr, array(
					'key' => CONF_CLIENT_STORE_ID,
					'scopes' => 'ORG',
					'value_type' => 'NUMERIC',
					'modules' => 'CLIENT',
					'value' => $base_store_id ));
		}
		return $arr;
	}
	
	public function addUpdateOrgConfigs( $name, $scope, $value, $entity_id ){
	
		global $currentorg;
		$config_manager = new ConfigManager();
		if( $entity_id == -1 || $scope == 'ORG' ){
	
			$entity_id = $currentorg->org_id;
		}else{
			
			$entity_controller = new EntityController( $scope );
			$entity_details = $entity_controller->getDetailsById( $entity_id );
			if( $entity_details['org_id'] != $currentorg->org_id ){
					
				$this->logger->error( "Trying to change config of different Org entity : ".$entity_id );
				Util::addApiWarning( "Invalid Entity Id for Org" );
				throw new Exception( "ERR_ORG_CONFIG_ADD_FAIL" );
			}
		}
		if( !in_array(
				strtoupper( $scope ),
				ConfigKey::getValidScopes()
		) ){
	
			$this->logger->error( "Invalid Scope for config : ".$config['scopes'] );
			throw new Exception( "ERR_ORG_CONFIG_INVALID_SCOPE" );
		}
		if( json_decode( $value ) === NULL ){
	
			$this->logger->error( "Invalid Value for config : ".$config['value'] );
			throw new Exception( "ERR_ORG_CONFIG_INVALID_VALUE" );
		}else{
	
			$decoded_value = json_decode( $value, true );
			$this->logger->debug( "Decoded JSON value : $value" );
			$key_value = array(
					'scope' => $scope,
					'value'  => $decoded_value,
					'entity_id' => $entity_id
			);
			$config_manager->setKeyValue( $name, $key_value );
		}
	}

    public function getOrgDefaultTimezone(){
        return $this->OrgModelExtension->getOrgDefaultTimezone();
    }

    public function getOrgDefaultCountry(){
        return $this->OrgModelExtension->getOrgDefaultCountry();
    }

    public function getOrgDefaultCurrency(){
        return $this->OrgModelExtension->getOrgDefaultCurrency();
    }

    public function getOrgDefaultLanguage(){
        return $this->OrgModelExtension->getOrgDefaultLanguage();
    }

    public function getSupportedIncomingInteractionActions($action_code = false)
    {
        $actions = array('action' => array());
        try
        {
            if($action_code == false)
                $actions_objs = IncomingInteractionActions::loadAll();
            else
            {
                $actions_objs = array(IncomingInteractionActions::loadByCode($action_code));
            }
            foreach($actions_objs as $actions_obj)
            {
                $interaction_type = $actions_obj->getType();
                $item = array('code' => $actions_obj->getCode(),
                              'label' => $actions_obj->getLabel(),
                              'type' => $actions_obj->getType()
                              );
                
                if($interaction_type == 'SMS' || $interaction_type == 'ALL')
                {
                    $item['params'] = array('param' => array());
                    $action_params = array();
                    try{
                        $action_params = IncomingInteractionActionParams::loadByActionId($actions_obj->getId());
                    }
                    catch (Exception $e)
                    {
                        $this->logger->debug("No params for action");
                    }
                    foreach($action_params as $param)
                    {
                        $item['params']['param'][] = array('code' => $param->getCode(), 
                                                        'label' => $param->getLabel(),
                                                        'is_mandatory' => $param->getIsMandatory());
                    }
                }

                include_once "apiHelper/MobileTriggerConfigManager.php";
                $mconf = new MobileTriggerConfigManager();
                $configs = $mconf->getKeys($actions_obj->getId());
                $item['configs']['config'] = $configs;
                $actions['action'][] = $item;
            }
        } catch (Exception $ex) {

        }
        return $actions;
    }
    
    public function getSourceMappings($id = false)
    {
        $triggers = array('trigger' => array());
        try
        {
            if($id)
            {
               $trigger_objs = array(SourceMapping::loadById($this->org_id, $id));
            }
            else
            {
                $trigger_objs = SourceMapping::loadAll($this->org_id);
            }
            
            foreach($trigger_objs as $trigger_obj)
            {
                $item = array(
                              'id' => $trigger_obj->getId(),
                              'type' => $trigger_obj->getType(),
                              'shortcode' => $trigger_obj->getShortcode(),
                              'command' => $trigger_obj->getCommand(),
                              'use_org_prefix' => ($trigger_obj -> getOrgPrefixId() == -1) ? 0 : 1, 
                              'org_prefix' => $trigger_obj -> getOrgPrefix(), 
                              'notes' => $trigger_obj->getNotes(),
                              'whoami' => $trigger_obj->getWhoami(),
					          'till_id' => $trigger_obj->getTillId()
                        );
                if($item['type'] == 'BOTH')
                    $item['type'] = 'ALL';
                $action_id = $trigger_obj->getActionId();
                $action_params = $trigger_obj->getParams();
                if(! empty($action_params))
                    $param_ids = explode(",", $trigger_obj->getParams());
                else
                    $param_ids = array();
                try{
                    $actions_obj = IncomingInteractionActions::loadById($action_id);
                }
                catch(Exception $ex)
                {
                    $this->logger->debug("No action with id $action_id");
                }
                $action = array('code' => $actions_obj->getCode(),
                              'label' => $actions_obj->getLabel());
                $action['params'] = array('param' => array());
                foreach($param_ids as $param_id)
                {
                    try{
                    $param_obj = IncomingInteractionActionParams::loadById($param_id);
                    }
                    catch(Exception $ex)
                    {
                    	
                        $this->logger->debug("No action with id $param_id");
                        return null;
                    }
                    if($param_obj!=null)
                    {
                    	$action['params']['param'][] = array('code' => $param_obj->getCode(), 
                                                        'label' => $param_obj->getLabel(),
                
                	                                        'is_mandatory' => $param_obj->getIsMandatory());
                	}
                	
            }


                include_once "apiHelper/MobileTriggerConfigManager.php";
                $mconf = new MobileTriggerConfigManager($this->org_id);
                $configs = $mconf->getKeys($actions_obj->getId());

                foreach($configs as &$config)
                {
                    $config["value"] = $mconf->getValue($actions_obj->getId(), $config["name"], $trigger_obj->getId());
                    unset($config["default_value"]);
                }
                $item["configs"]["config"] = $configs;
                $item['action'] = $action;
                $triggers['trigger'][] = $item; 
            }
        } catch (Exception $ex) {
        	return null;
        }
        return $triggers;
    }
    
    public function addOrUpdateTrigger($trigger)
    {

        $this->logger->debug("addOrUpdateTrigger:: input: " . print_r($trigger, true));
        $trigger = $trigger[0];
        if(empty($trigger))
            throw new Exception("ERR_ORG_SOURCE_MAPPING_ADD_FAIL");
        $valid_types = array("SMS", "MISSED_CALL", "BOTH");
        
        // Convert to internal
        if($trigger['type'] == 'ALL')
            $trigger['type'] = 'BOTH';
        
        if((! isset($trigger["shortcode"])) || (! is_numeric($trigger["shortcode"])))
        {
            $this->logger->debug("Invalid shortcode");
            throw new Exception("ERR_ORG_SOURCE_MAPPING_INVALID_SHORTCODE");
        }
		if((! isset($trigger["incoming_till_id"])) || (! is_numeric($trigger["incoming_till_id"])))
		{
			$this->logger->debug("incoming_till_id is empty or non numeric");
			throw new Exception("ERR_ORG_SOURCE_MAPPING_INVALID_TILL");
		}else{
			$tmp_user = StoreProfile::getById($trigger["incoming_till_id"]);
			global $currentorg;
			if($tmp_user->org_id != $currentorg->org_id){
				$this->logger->debug("Invalid incoming_till_id , not belongs to current org");
				throw new Exception("ERR_ORG_SOURCE_MAPPING_INVALID_TILL");
			}

		}
        if((! isset($trigger["type"])) || (!in_array($trigger["type"], $valid_types)))
        {
            $this->logger->debug("Invalid type");
            throw new Exception("ERR_ORG_SOURCE_MAPPING_INVALID_TYPE");
        }
        if(! isset($trigger['action']['code']))
        {
            $this->logger->debug("No action code sent");
            throw new Exception("ERR_ORG_SOURCE_MAPPING_INVALID_ACTION");
        }
        if(empty($trigger['command']) && $trigger['type'] == 'SMS')
        {
            $this->logger->debug("No command sent");
            throw new Exception("ERR_ORG_SOURCE_MAPPING_INVALID_COMMAND");
        }

        if ($trigger['use_org_prefix'] || $trigger['use_org_prefix'] == 'true') {        	
        	global $currentorg;
        	try {
                $orgPrefix = IncomingInteractionOrgPrefix::findByOrgId($currentorg -> org_id) -> toArray();
	        	$trigger['org_prefix'] = $orgPrefix['prefix'];
	        	$trigger['org_prefix_id'] = $orgPrefix['id'];
            } catch (ApiIncomingInteractionOrgPrefixModelException $ex) {
            	echo "\n'"; print_r($ex); die("'--");
                throw new Exception('ERR_SOURCE_MAPPING_USE_ORG_PREFIX_NOT_SET');
            }
        } else {
        	$trigger['org_prefix_id'] = -1;
        }
        
        $action_code = $trigger['action']['code'];
        
        try {
            try
            {
                $action = IncomingInteractionActions::loadByCode($action_code);
            }
            catch(Exception $ex)
            {
                $this->logger->debug("Incalid action $action_code");
                throw new Exception("ERR_ORG_SOURCE_MAPPING_INVALID_ACTION");
            }
            $action_id = $action->getId();
            if(($trigger['type'] == 'BOTH' || $trigger['type'] == 'MISSED_CALL') && $action->getType() == 'SMS')
            {
                throw new Exception("ERR_ORG_SOURCE_MAPPING_INCOMAPTIBLE_TYPE");
            }
            try{
                $valid_action_params = IncomingInteractionActionParams::loadByActionId($action->getId());
            }
            catch (Exception $ex)
            {
                $this->logger->info("No params for this action");
            }
            $valid_param_codes = array();
            $mandatory_param_codes = array();
            foreach ($valid_action_params as $param)
            {
                $valid_param_codes[] = $param->getCode();
                
                if($param->getIsMandatory())
                {
                    $mandatory_param_codes[] = $param->getCode();
                }
            }
            
            if(isset($trigger['action']['params']['param']) && !empty($trigger['action']['params']['param']))
            {
                $provided_params = array_key_exists(0, $trigger['action']['params']['param']) ? $trigger['action']['params']['param'] : array($trigger['action']['params']['param']);

                foreach($provided_params as $param)
                {
                    if(! in_array($param['code'], $valid_param_codes))
                    {
                            $this->logger->debug("Invalid parameter passed");
                            throw new Exception("ERR_ORG_SOURCE_MAPPING_INVALID_PARAM");
                    }
                    if(in_array($param['code'], $mandatory_param_codes))
                            $mandatory_param_codes = array_diff($mandatory_param_codes, array($param['code']));
                }
            }
            
            if(! empty($mandatory_param_codes))
            {
                     $this->logger->debug("Mandatory parmas missing");
                    throw new Exception("ERR_ORG_SOURCE_MAPPING_MISSING_MANDATORY_PARAMS");
            }
            
            $trigger_obj = new SourceMapping();
            $trigger_obj->setOrgId($this->org_id);
			$trigger_obj->setTillId($trigger['incoming_till_id']);
            $trigger_obj->setShortcode($trigger['shortcode']);
            if($trigger['type'] == 'SMS')
                $trigger_obj->setCommand($trigger['command']);
            else
                $trigger_obj->setCommand('');
            $trigger_obj -> setOrgPrefixId($trigger['org_prefix_id']);
            $trigger_obj -> setOrgPrefix($trigger['org_prefix']);
            $trigger_obj->setActionId($action_id);
            $trigger_obj->setType($trigger['type']);
            if($trigger['type'] == 'SMS')
            {
                $param_ids = array();
                foreach($provided_params as $param)
                {
                    $p = IncomingInteractionActionParams::loadByActionIdCode($action_id, $param['code']);
                    $param_ids[] = $p->getId();
                }
                if(! empty($param_ids))
                {
                    $param_ids = implode (",", $param_ids);
                    $trigger_obj->setParams ($param_ids);
                }
            }
            if(isset($trigger['notes']))
                $trigger_obj->setNotes ($trigger['notes']);
            if(isset($trigger['whoami']))
                $trigger_obj->setWhoami($trigger['whoami']);
            if(isset($trigger['id']))
                $trigger_obj->setId($trigger['id']);
            
            // Check duplicates
            $existing_triggers = array();
            try
            {
                $existing_triggers = SourceMapping::loadByShortcodeCommand($trigger['shortcode'], $trigger['command']);
            }
            catch(Exception $ex)
            {
                
            }
      
            // Same shortcode command combination not allowed
            foreach($existing_triggers as $m)
            {
                //No trigger with same action unless its an update
                if($trigger['id'] != $m->getId())
                    throw new Exception("ERR_ORG_SOURCE_MAPPING_DUPLICATE_MAPPING");
            }
            
            try
            {
                $id = $trigger_obj->save();
                $id = isset($trigger['id']) ? $trigger['id'] : $id;
                if(!array_key_exists(0,$trigger["configs"]["config"]))
                {
                    $trigger["configs"]["config"] = array($trigger["configs"]["config"]);
                }
                include_once "apiHelper/MobileTriggerConfigManager.php";
                foreach($trigger["configs"]["config"] as $config)
                {
                    $mconf = new MobileTriggerConfigManager($this->org_id);
                    $this->logger->debug("RRMQ " . print_r($config["value"],true));
                    $mconf->setValue($action_id, $config["name"], $id, $config["value"]);
                }
                return $this->getSourceMappings($id);
                
            } catch (Exception $ex) {
                $this->logger->debug($ex->getMessage());
                throw new Exception("ERR_ORG_SOURCE_MAPPING_ADD_FAIL");
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

	public function getInventoryProducts($rows, $filter, $start, $cache_file_path, $return_file=false, $limit=null){
		try{
			$search_controller = new ApiSearchController("users");
			$api_status_code = 'SUCCESS';
			$total_products_fetched=0;
			$count = 0;
			$flag = TRUE;
			$product = array();
			while(True){
				
				if($limit){
					if($total_products_fetched-$limit >= 0){
						break;
					}
					if($limit-$total_products_fetched <= $rows)
						$rows = $limit-$total_products_fetched;
				}
				
				$res = $search_controller->searchProducts($filter, $start, $rows);
								
				if( $flag ){
					$product_count = $res['product']['count'];
					//set the count
					if($limit){
						if($res['product']['count'] > $limit)
							{ 
								if($product_count-$start < $limit)
									$product_count = $product_count-$start;
								else
						 			$product_count = $limit;
							}
						else
							$product_count = (($product_count-$start)>0)? $product_count-$start : 0;
					}
					else{
						//total count
						$product_count = (($product_count-$start)>0)? $product_count-$start : 0;
					}
					//start id
					$start_initial = $start;
							
						//write to a file , create the file
						if( $return_file )
						{
							$this->logger->debug("writing to a file");
							$cache_time = (isset(FileCachedApis::$file_cached_apis[$this->resource][$this->method]['cache_time'])) ? FileCachedApis::$file_cached_apis[$this->resource][$this->method]['cache_time'] : 0;
							$file_ext =  'xml.gz';
							$fh = fopen($cache_file_path,'w');
				            fwrite($fh,"<?xml version='1.0' encoding='ISO-8859-1'?>\n<response>\n");
							fwrite($fh, ApiUtil::transformArrayToXml($res['status'], 'status'));
							fwrite($fh, "\n<organization>");
							fwrite($fh, "\n<products>");
							
							fwrite($fh, "\n<start>".$start_initial."</start>");
							fwrite($fh, "\n<count>".$product_count."</count>\n");
						}
					$flag = FALSE;	
				}
				
				if( $return_file ){
					foreach ($res['product']['results']['item'] as $product) {
						fwrite($fh, ApiUtil::transformArrayToXml($product, 'product'));
						fwrite($fh,"\n");
					}
				}
				else{
					foreach ($res['product']['results']['item'] as $item) {
						$product[] = $item; 
					}
				}
					
				$count = $res['product']['count'];
				$total_products_fetched += $res['product']['rows'];
				$start += $res['product']['rows'];

				if($total_products_fetched-$count >= 0)
					break;
				if($count-$total_products_fetched <= $rows)
	                $rows = $count-$total_products_fetched;
			}
			//footer end file
			if( $return_file ){
				fwrite($fh, "</products>");
				fwrite($fh, "\n</organization>");
				fwrite($fh, "\n</response>");
			}
			
			
		$status = array(
			"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
			"code" => ErrorCodes::$api[$api_status_code],
			"message" => ErrorMessage::$api[$api_status_code]
		);
		
		$result = array(
		'status' => $status,
		'organization'=>array('products' => array("start"=>$start_initial, "count"=>$product_count,"product"=>$product))
		);
		
		return $result;
	
	} catch (Exception $e){
		$this->logger->debug("Failed to fetch products from solr");
		$result = array(
            "status" => array(
            "code" => ErrorCodes::$api["FAIL"],
            "success" => false,
            "message" => ErrorMessage::$api['FAIL']
            ),
            'organization'=>array('products' => array("start"=>$start_initial, "count"=>$product_count,"product"=>$product))
       		);
		}	
	}

	public function getCurrencies($query_params)
	{
		include_once 'models/currencyratio/OrgCurrency.php';
		global $currentorg;
		$org_currency = new OrgCurrency($currentorg->org_id);
		$currencies = $org_currency->loadAll($currentorg->org_id, null,  $query_params["id"], $query_params["limit"], $query_params["offset"]);
		$ret = array();
		foreach($currencies as $currency)
		{
			$ret[] = $currency->toArray();
		}
		return $ret;
		
		
	}

	public function getOrgPrefix() {
		global $currentorg; 
		try {
            return IncomingInteractionOrgPrefix::findByOrgId($currentorg -> org_id);
        } catch (Exception $ex) {
            return NULL;
        }
	}

	public function postOrgPrefix($orgPrefixArr) {
		try {
            $newOrgPrefix = new IncomingInteractionOrgPrefix();
            
            if (isset($orgPrefixArr['id']))
                $newOrgPrefix -> setId($orgPrefixArr['id']);
            
            global $currentorg;
            $newOrgPrefix -> setOrgId($currentorg -> org_id);

            $prefixValue = $orgPrefixArr['value'];
            if (empty($prefixValue))
            	throw new Exception("ERR_SOURCE_MAPPING_ORG_PREFIX_VALUE_NOT_SET");
            
            /* Check if org-prefix is not an existing command in `sms_mapping` */
            try {
            	$existingMappingObjs = SourceMapping::loadByCommand($prefixValue);

            	if (! empty($existingMappingObjs))
	            	throw new Exception("ERR_SOURCE_MAPPING_ORG_PREFIX_EXISTING_COMMAND");
            } catch (ApiSourceMappingModelException $e) {
	            if ($e -> getMessage() == '33002')
	            	$existingMappingObjs = array();
	            else 
	            	throw $e;
            }
            $newOrgPrefix -> setPrefix($prefixValue);

            $id = $newOrgPrefix -> insert();
            global $currentorg;
            return IncomingInteractionOrgPrefix::findById($id);
        }
        catch(ApiIncomingInteractionOrgPrefixModelException $ex) {
            throw $ex;
        }
	}
}
?>
