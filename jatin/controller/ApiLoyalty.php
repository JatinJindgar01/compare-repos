<?php

include_once "controller/ApiParent.php";
include_once "apiHelper/OTPManager.php";
//TODO: referes to cheetah
include_once "model/loyalty.php";
include_once 'models/SourceMappingModule.php';
include_once 'models/SourceMappingAction.php';
include_once 'models/SourceMapping.php';
include_once 'models/IncomingMobileInteraction.php';
include_once "apiHelper/Errors.php";
include_once 'models/IncomingInteractionActions.php';
include_once 'models/SourceMapping.php';

class LoyaltyController extends ApiParentController {
	/**
	 * This is the DB Connection to the users db - ideally, there should be no DB object outside of the LoyaltyController
	 * @var unknown_type
	 */

	var $listenersManager;
	var $loyalty_mod;
	var $mlm;
	
	var $testing;

	public $loyaltyModel;
	private $inventory; //inventory sub module
	private $pl_db;
        
	public function __construct(LoyaltyModule $loy_m, $currentuser = false,$testing = false) {

		parent::__construct('users');

		if($currentuser != false){
			$this->currentuser = $currentuser;
			$this->currentorg = $this->currentuser->getProxyOrg();
		}


		$this->listenersManager = new ListenersMgr($this->currentorg);
		$this->loyalty_mod = $loy_m;
		//$this->mlm = new MLMSubModule($this->loyalty_mod);

		$this->testing = $testing;
		$this->loyaltyModel = new LoyaltyModel($this, $testing);
		$this->inventory = new InventorySubModule();
                $this->pl_db = new Dbase("performance");
	}

	/*
	 * All Get Methods Are Grouped Below...
	 *
	 */
	/**
	 * Returns the value for the organization given the key
	 * @param $config_key The key for which the value is required
	 * @return unknown_type
	 */
	public function getConfiguration($config_key) {
		return $this->currentorg->getConfigurationValue($config_key);
	}

	private function getConfigurationValue($conf_key, $default) {
		
		return $this->currentorg->getConfigurationValue($conf_key, $default);
	}
	
	/**
	 * Get the loyalty id for the user
	 * @param $org_id organization id
	 * @param $user_id user id for whom the loyalty id is required
	 * @return loyalty id
	 */
	function getLoyaltyId($org_id, $user_id) {
		if ($user_id == '')
		return false;
		return $this->db->query_scalar("SELECT id FROM `loyalty` WHERE `publisher_id` = '$org_id' AND `user_id` = '$user_id'");
	}

	/**
	 * @deprecated Try to avoid
	 * Get a random user from the organization
	 * @return unknown_type
	 */
	function getUserId(){

		$org_id = $this->currentorg->org_id;
		return $this->db->query_firstcolumn("SELECT user_id FROM loyalty WHERE publisher_id = '$org_id' ");

	}

	/**
	 * Get the user id for the given loyalty id
	 * @param $loyalty_id
	 * @return user_id for the loyalty id
	 */
	function getUserIdFromLoyaltyId($loyalty_id) {
		$org_id = $this->currentorg->org_id;
                
                //dont fire the query unnecesarrily
                if($loyalty_id > 0){                
		        return $this->db->query_scalar("SELECT user_id FROM loyalty WHERE publisher_id = $org_id AND id = $loyalty_id LIMIT 1");
                }
	}

	/**
	 * Get the user id from the email id, from the extended user profile
	 * @param $email
	 * @return user_id
	 */
	function getUserIdFromEmailId($email){
		$org_id = $this->currentorg->org_id;
		$ret_arr =  $this->db->query_firstrow("SELECT id AS user_id FROM users WHERE `email` = '$email' AND `org_id` = $org_id LIMIT 0,1");
		return $ret_arr;
	}

	/**
	 * Get the loyalty points for the given loyalty id
	 * @param $loyalty_id
	 * @return unknown_type
	 */
	function getBalance($loyalty_id) {
		$details = $this->getLoyaltyDetailsForLoyaltyID($loyalty_id);
		return $details['loyalty_points'];
	}



	/**
	 * Get the lifetime points and lifetime purchases for the user
	 * @param UserProfile $user
	 * @return array($lifetime_points, $lifetime_purchase)
	 */
	function getLifetimePointsAndPurchasesForUser(UserProfile $user) {

		$org_id = $this->currentorg->org_id;
		$res = $this->db->query_firstrow("SELECT lifetime_points, lifetime_purchases from loyalty where user_id = $user->user_id and publisher_id = $org_id");
		$lifetime_points = $res['lifetime_points'];
		$lifetime_purchase = $res['lifetime_purchases'];
		return array($lifetime_points, $lifetime_purchase);
	}

	/**
	 * Gives the number of bills and visits for the user.
	 * @param $user_id
	 * @return array('num_of_bills' => 5, 'num_of_visits' => 2)
	 */
	function getNumberOfVisitsAndBillsForUser($user_id, $datetime = ''){

		$org_id = $this->currentorg->org_id;
		$num_bills_n_days = $this->currentorg->getConfigurationValue(CONF_LOYALTY_NUM_BILLS_IN_DAYS_LISTENER, 7);

		$datetime = $datetime == '' ? 'NOW()' : "'$datetime'";
		
		$sql = "SELECT 
					COUNT(*) as num_of_bills, 
					COUNT(DISTINCT( DATE( `date` ) )) as num_of_visits,
					SUM(IF( DATE( `date` ) = DATE( $datetime ), 1, 0 )) as num_of_bills_today,
					SUM(IF( ( DATEDIFF( $datetime, `date` ) BETWEEN 0 AND ($num_bills_n_days - 1) ), 1, 0 )) as num_of_bills_n_days					 
			"
			." FROM `loyalty_log` "
			." WHERE `org_id` = $org_id AND `user_id` = $user_id "
			." GROUP BY user_id LIMIT 0,1";
			

		return $this->db->query_firstrow($sql);
	}

	/**
	 * Get all loyalty details for the loyalty id
	 * @param $loyalty_id
	 * @return unknown_type
	 */
	function getLoyaltyDetailsForLoyaltyID($loyalty_id){

		$this->loyaltyModel->getFirstRowResult();
		return $this->loyaltyModel->getLoyaltyDetailsById( $loyalty_id );
	}
        
        /**
         * Get loyalty Type of User given userId and orgId
         */
        function getLoyaltyTypeForUserId($user_id,$org_id){
            $this->logger->debug("orgId   and user_id in ApiLoyalty is $org_id and $user_id");
            $row = $this->db->query_firstrow("SELECT * FROM loyalty WHERE publisher_id = $org_id AND user_id = $user_id LIMIT 1");
            $this->logger->debug("result from db:  $row");
            if($row['type']==null){
                $this->logger->debug("loyalty type from db is null.Setting it to false and returning");
                $row['type']='false';
            }
            return $row['type'];
        }

        /**
	 * Get all loyalty details for the user id
	 * @param $user_id
	 * @return unknown_type
	 */
	function getLoyaltyDetailsForUserID($user_id){
		$org_id = $this->currentorg->org_id;
		return $this->db->query_firstrow("SELECT * FROM loyalty WHERE publisher_id = $org_id AND user_id = $user_id LIMIT 1");
	}

	function getCustomFieldDataForJavaClient($user_id){
		
		global $currentorg;
		$org_id = $currentorg->org_id;
		
		$cf = new CustomFields();
		
		$cf_hash = $cf->getCustomFields($org_id, 'query_hash', 'id', 'name');
		
		$this->loyaltyModel->getHashResult('cf_id', 'value');
		$return = $this->loyaltyModel->getCustomFieldValuesHash($user_id);
		
		$cf_return = array();
		
		foreach($return as $cf_id => $cf_value){
			$cf_values = json_decode($cf_value);
			
			if(is_array($cf_values))
				$cf_values = implode(',', $cf_values);
			
			if(isset($cf_hash[$cf_id]))
				$cf_return[$cf_hash[$cf_id]] = $cf_values;
		}
		
		return $cf_return;
	}
	
	function getCustomFieldData($user_id){
		
		global $currentorg;
		$org_id = $currentorg->org_id;
		
		$cf = new CustomFields();
		
		$cf_hash = $cf->getCustomFields($org_id, 'query_hash', 'id', 'name', 'loyalty_registration');
		
		$this->loyaltyModel->getHashResult('cf_id', 'value');
		$return = $this->loyaltyModel->getCustomFieldValuesHash($user_id);
		
		$cf_return = array();
		
		foreach($return as $cf_id => $cf_value){
			/*$cf_values = json_decode($cf_value);
            $cf_values = implode(',', $cf_values);*/
            if(isset($cf_hash[$cf_id]))
            {
                array_push($cf_return, array('field_name' => $cf_hash[$cf_id], 'field_value' => $cf_value));
            }
        }
		
		return $cf_return;
	}
	
	/**
	 * Get cashier_id from cashier_code
	 * 
	 * @param $org_id
	 * @param $cashier_code
	 * @param $store_id
	 */
	function getCashierId( $org_id , $cashier_code , $store_id )
	{
			$sql = "SELECT `cashier_id`  
					FROM `masters`.`cashiers`
					WHERE `org_id` = $org_id 
					AND `cashier_code` = $cashier_code
					AND `store_id` = $store_id";

			return $this->db->query_firstrow( $sql );
	}
	
	/**
	 * Maps transactions made to a cashier_id
	 * 
	 * @param unknown_type $cashier_id
	 * @param unknown_type $loyalty_log_id
	 */
	function InsertCashierTransaction( $cashier_id , $loyalty_log_id )
	{
				$sql = "INSERT INTO  `user_management`.`cashier_transactions` (`cashier_id` , `loyalty_log_id`)
						VALUES ('$cashier_id',  '$loyalty_log_id');";
				
				return $this->db->insert( $sql );
	}
	
	/**
	 * Get awarded points for the loyalty id
	 * @param $loyalty_id
	 * @param $type query/query_table
	 * @return unknown_type
	 */
	function getAwardedPointsForUserID($user_id, $type = 'query'){

		$org_id = $this->currentorg->org_id;
		
		$sql = "
			SELECT a.*, oe.code as store 
			FROM awarded_points_log a 
			JOIN masters.org_entities oe ON oe.id = a.awarded_by AND oe.org_id = a.org_id
			WHERE a.org_id = $org_id AND a.user_id = '$user_id' 
			ORDER BY id DESC
		";

		if($type == 'query')
		return $this->db->query($sql);
			
		if($type == 'query_table')
		return $this->db->query_table($sql,'points');
	}

	/**
	 * Get the external id for the given loyalty id
	 * @param $loyalty_id
	 * @return external_id
	 */
	function getExternalIdForLoyaltyID($loyalty_id){
		$details = $this->getLoyaltyDetailsForLoyaltyID($loyalty_id);
		return $details['external_id'];
	}

	/**
	 * Get the customers given the mobile number 
	 * @param $query mobile_no or first/last name 
	 * @return An array of users who match the query
	 */
	function getRegisteredUsersByMobile($query) {
		
		$org_id = $this->currentorg->org_id;
		
		if(strlen($query) < 5)
			return array();
			
		//Search with country code prefix as mobile
		if(ctype_digit("".$query)){			

			
			//get the default country code
			$defaultCountryDetails = $this->currentorg->getDetailsForDefaultCountry();
			$defaultCountryCode = $defaultCountryDetails['mobile_country_code'];
			$sql= "SELECT DISTINCT u.id as user_id , IFNULL(u.mobile, 'No Mobile') as value, IFNULL(TRIM(CONCAT(u.firstname,' ', u.lastname)),'No Name Provided') as info "
			. " FROM users u "
			. " WHERE u.org_id = '$org_id' "
			. " AND u.`mobile` LIKE '$defaultCountryCode$query%' "
			. " LIMIT 0, 10";
// 			. " JOIN extd_user_profile e ON e.org_id = u.org_id AND e.user_id = u.id "
			if(!$arr)
				$arr = $this->db->query($sql);
			else
				$arr = array_merge($arr, $this->db->query($sql));
			
			
			
			$sql= "SELECT DISTINCT e.user_id, IFNULL(u.mobile, 'No Mobile') as value, IFNULL(TRIM(CONCAT(u.firstname,' ', u.lastname)),'No Name Provided') as info "
			. " FROM users u "
			. " WHERE u.org_id = '$org_id' "
			. " AND u.`mobile` LIKE '$query%'"
			. " LIMIT 0, 10";
//			. " JOIN extd_user_profile e ON e.org_id = u.org_id AND e.user_id = u.id "
			
			if(!$arr)
				$arr = $this->db->query($sql);
			else
				$arr = array_merge($arr, $this->db->query($sql));
			
			
			/*
			//TODO change usage of country code by checking what the query begins with
			
			$country_code_filter = "";
			if(Util::isInternationalOrg()
			&& $this->currentorg->getConfigurationValue(CONF_CLIENT_INTERNATIONAL_ENABLED, false)
			){
				$country_code = $this->currentorg->getConfigurationValue(CONF_CLIENT_COUNTRY_CODE, '91');
				$country_code_filter = " OR u.`mobile` LIKE '$country_code$query%' ";
			}
			
			
			$sql= "SELECT DISTINCT e.user_id, IFNULL(u.mobile, 'No Mobile') as value, IFNULL(TRIM(CONCAT(u.firstname,' ', u.lastname)),'No Name Provided') as info "
			. " FROM users u "
			. " WHERE u.org_id = '$org_id' "
			. " AND (u.`mobile` LIKE '91$query%' $country_code_filter OR u.`mobile` LIKE '$query%' ) ";
			if(!$arr)
				$arr = $this->db->query($sql);
			else
				$arr = array_merge($arr, $this->db->query($sql));
			*/
		}

		return $arr;
	}
	/**
	 * Get the customers given the first name or last name or email id or mobile number or external id
	 * @param $query mobile_no or first/last name or email.
	 * @return An array of users who match the query
	 */
	public function getRegisteredUsersByMobileOrNameOrExternalId($query) {
		$org_id = $this->currentorg->org_id;
		
		if(strlen($query) < 5)
			return array();

		//search by mobile
		$arr = $this->getRegisteredUsersByMobile($query);
		
		//Search by external ID
		$sql= "SELECT l.user_id, IFNULL(l.external_id, 'No ExternalId') as value, IFNULL(TRIM(CONCAT(u.firstname,' ', u.lastname)),'No Name Provided') as info "		
		. " FROM loyalty l "
		. " JOIN users u ON l.publisher_id = u.org_id AND l.user_id = u.id "
		. " WHERE l.publisher_id = '$org_id' "
		. " 	AND l.external_id LIKE '$query%' "
		. " LIMIT 0, 10";
		if(!$arr)
			$arr = $this->db->query($sql);
		else
			$arr = array_merge($arr, $this->db->query($sql));

		//TODO : split the query into parts and then search
		//Search by firstname
		if(!is_numeric($query)){
			$sql= "SELECT DISTINCT e.user_id, IFNULL(u.mobile, 'No Mobile') as value, IFNULL(TRIM(CONCAT(u.firstname,' ', u.lastname)),'No Name Provided') as info "
			. " FROM users u "
			. " WHERE u.org_id = '$org_id' "
			. " AND u.`firstname` LIKE '$query%'"
			. " LIMIT 0, 10";
			//			. " JOIN users e ON e.org_id = u.org_id AND e.user_id = u.id "
			
			if(!$arr)
				$arr = $this->db->query($sql);
			else
				$arr = array_merge($arr, $this->db->query($sql));
		}
			

		//Search by lastname
		if(!is_numeric($query)){
			$sql= "SELECT DISTINCT e.user_id, IFNULL(u.mobile, 'No Mobile') as value, IFNULL(TRIM(CONCAT(u.firstname,' ', u.lastname)),'No Name Provided') as info "
			. " FROM users u "
			. " WHERE u.org_id = '$org_id' "
			. " AND e.`lastname` LIKE '$query%' "
			. " LIMIT 0, 10";
			//			. " JOIN extd_user_profile e ON e.org_id = u.org_id AND e.user_id = u.id "
			
			if(!$arr)
				$arr = $this->db->query($sql);
			else
				$arr = array_merge($arr, $this->db->query($sql));
		}
		
		if( !is_numeric( $query )){
			$sql= "SELECT DISTINCT e.user_id, IFNULL(u.email, 'No Email') as value, IFNULL(TRIM(CONCAT(u.firstname,' ', u.lastname)),'No Name Provided') as info "
					. " FROM users u "
					. " WHERE u.org_id = '$org_id' "
					. " AND u.`email` LIKE '$query%'"
					. " LIMIT 0, 10";
					//. " JOIN extd_user_profile e ON e.org_id = u.org_id AND e.user_id = u.id "
					
			if(!$arr)
				$arr = $this->db->query($sql);
			else
				$arr = array_merge($arr, $this->db->query($sql));
		}
		return $arr;
	}



	/**
	 * Calculate the points for the user, given the bill amount, based on slabs percentages etc
	 * @param UserProfile $user User Profile object of the user for whom the calculation has to be done
	 * @param array $current_transaction Current transaction details, based on which the points are calculated.
	 * 			Currently array('amount' => $amount)
	 * @return points for the user
	 */
	function calculatePoints(UserProfile $user, array $current_transaction) {
		$org_id = $this->currentorg->org_id;
		$use_slabs = $this->getConfiguration(CONF_LOYALTY_ENABLE_SLABS);

		if (!$use_slabs) {
			//no slabs .. use direct percentage
			$percent = $this->getConfiguration(CONF_LOYALTY_RULES_CALCULATION_PERCENT);
		
		}else {
			
			//run using slabs
			list($slab, $slab_number) = $this->getSlabInformationForUser($user);
			
			if ($slab == false) {

				$slablist = json_decode($this->getConfiguration(CONF_LOYALTY_SLAB_LIST), true);
				$slab = $slablist[0];
			}
			
			//Check in the seasonal slabs before using the global values
			$percent = 0;
			if(!$this->getSeasonalSlabPercentage($slab, $percent)){			
				$percentages = json_decode($this->getConfiguration(CONF_LOYALTY_SLAB_POINTS_PERCENT), true);
				$percent = $percentages[$slab];
			}
		}
		$points = 0;
		if ($percent > 0) {
			$points = $percent * $current_transaction['amount'] / 100;
		}
		return round($points); //points are rounded
	}



	/**
	 * Gets the list of slabs that have been declared for this organization
	 * @return array of Slab names. Empty array if none or disabled
	 */
	function getSlabsForOrganization() {
		
		if (!$this->areSlabsEnabled()) return array();
		$config_manager =  new ConfigManager();
		$slablist = $config_manager->getKey(CONF_LOYALTY_SLAB_LIST);

		if ($slablist == false || !is_array($slablist)) {
			$slablist = array();
		}
		return $slablist;
	}

	/**
	 * Gets the list of slab percentages that have been declared for this organization
	 * @return array of Slab percentages. Empty array if none or disabled
	 */
	function getSlabPointsPercentages() {
		if (!$this->areSlabsEnabled()) return array();
		$slablist = json_decode($this->getConfiguration(CONF_LOYALTY_SLAB_POINTS_PERCENT, false), true);
		if ($slablist == false || !is_array($slablist)) {
			$slablist = array();
		}
		return $slablist;
	}

	/**
	 * 
	 * @param $bill_number
	 */
	function getAwardPointDetailsByBillNumber( $bill_number, $user_id, $entered_by ){
		
		$sql = "
		
			SELECT *
			FROM awarded_points_log
			WHERE org_id = '$this->org_id' AND ref_bill_number = '$bill_number' AND awarded_by = $entered_by AND user_id = $user_id
		";
		
		return $this->db->query_firstrow( $sql );
	}
	
	/**
	 * Award extra points to the user
	 * @param $user_id User ID to which the points are awarded
	 * @param $org_id Organization
	 * @param $awarded_points Points to be awarded (int)
	 * @param $ref_bill_number string Bill Number for X-Reference
	 * @param $notes Any notes
	 * @param $donated_by : which customer donated the points to family
	 * @param $family_id : which family Id was it
	 * @return unknown_type
	 */
	function awardPoints($user_id, $org_id, $awarded_points, $ref_bill_number, $notes = '', $awarded_time = '', $donated_by = -1, $family_id = -1 ){

		$loyalty_id = $this->getLoyaltyId($org_id, $user_id);
		if ($loyalty_id == false) return false;

		$me = $this->currentuser->user_id;

		$awarded_time = $awarded_time == '' ? date('Y-m-d H:i:s') : $awarded_time;

		$sql = "INSERT INTO awarded_points_log (
									org_id, user_id, loyalty_id, awarded_points, ref_bill_number, `notes`, awarded_by, awarded_time, donated_by, family_id ) "
		. " VALUES ('$org_id', '$user_id', '$loyalty_id', $awarded_points, '$ref_bill_number', '$notes', $me, '$awarded_time', $donated_by, $family_id ) ";

		$ret2 = $this->updateLoyaltyDetails($loyalty_id, $awarded_points, $awarded_points, 0, $awarded_time, $me);

		return $this->db->update($sql) && $ret2;
	}


	/**
	 * Upgrade the slab for the user manually. No checking is done.
	 * @param $from_slab Existing Slab Name for the user
	 * @param $to_slab New Slab Name to which the user will be put into
	 * @param $slab_number Slab number of the new slab
	 * @param $user_id User id for whom the slab has to be changed
	 * @param $publisher_id
	 * @param $upgrade_bonus_points Points to be added to the existing points for the user
	 * @param $bill_number
	 * @param $note
	 * @return unknown_type
	 */
	function manualupgradeSlab($from_slab, $to_slab, $slab_number, $user_id, $publisher_id, $upgrade_bonus_points, $bill_number,$note = 'auto'){
		if (!$this->areSlabsEnabled()) return false;

		$loyalty_id = $this->getLoyaltyId($publisher_id, $user_id);
		if ($loyalty_id == false) return false;

		$me = $this->currentuser->user_id;

		$logsql = "INSERT INTO slab_upgrade_log (user_id, org_id, loyalty_id, from_slab_name, to_slab_name, upgrade_bonus_points, ref_bill_number, upgraded_by, upgrade_time, notes) "
		. " VALUES ($user_id, $publisher_id, $loyalty_id, '$from_slab', '$to_slab', $upgrade_bonus_points, '$bill_number', $me, NOW(),'$note')";

		$this->db->insert($logsql);

		$sql = "UPDATE `loyalty` SET slab_name = '$to_slab' ,slab_number = $slab_number, "
		. " `loyalty_points` = `loyalty_points` + $upgrade_bonus_points, lifetime_points = lifetime_points + $upgrade_bonus_points "
		. " where user_id = $user_id and publisher_id = $publisher_id";
		return $this->db->update($sql);
	}

	/**
	 * Upgrade the slab for the user. Upgrades only if Current slab number is less than the 'to' slab number
	 * @param $from_slab Existing Slab Name for the user
	 * @param $to_slab New Slab Name to which the user will be put into
	 * @param $user_id User id for whom the slab has to be changed
	 * @param $publisher_id
	 * @param $upgrade_bonus_points Points to be added to the existing points for the user
	 * @param $bill_number
	 * @param $note
	 * @return unknown_type
	 */
	function upgradeSlab($from_slab, $to_slab, $user_id, $publisher_id, $upgrade_bonus_points, $bill_number, $note = 'Automatic Slab Upgrade', $upgrade_date = ''){
		if (!$this->areSlabsEnabled()) return false;

		$loyalty_id = $this->getLoyaltyId($publisher_id, $user_id);
		$me = $this->currentuser->user_id;

		$slab_list = $this->getSlabsForOrganization();
		if(count($slab_list)==0) return false;

		$upgrade_date = $upgrade_date == '' ? date('Y-m-d H:i:s') : $upgrade_date;
		
		//find slab number ..
		foreach ($slab_list as $k =>$value) {

			if($to_slab == $value)
			$to_key = $k;
			if($from_slab == $value)
			$from_key = $k;

		}
		if($from_key < $to_key) // don't do unless its an upgrade
		{			
			
			$this->awardPoints($user_id, $publisher_id, $upgrade_bonus_points, $bill_number, "Slab Upgrade: $from_slab -> $to_slab. $note", $upgrade_date);

			$logsql = "INSERT INTO slab_upgrade_log (user_id, org_id, loyalty_id, from_slab_name, to_slab_name, upgrade_bonus_points, ref_bill_number, upgraded_by, upgrade_time, notes) "
			. " VALUES ($user_id, $publisher_id, $loyalty_id, '$from_slab', '$to_slab', $upgrade_bonus_points, '$bill_number', $me, '$upgrade_date','$note')";

			$this->db->insert($logsql);

			$sql = "UPDATE `loyalty` SET slab_name = '$to_slab' ,slab_number = $to_key, `last_updated` = '$upgrade_date' WHERE user_id = $user_id and publisher_id = $publisher_id";
			return $this->db->update($sql);
		}
		else	return false;

	}

	/**
	 * Are Slabs enabled for the current organization
	 * @return unknown_type
	 */
	function areSlabsEnabled() {
		$configmanager = new ConfigManager();
		return $configmanager->getKey(CONF_LOYALTY_ENABLE_SLABS);
	}

	/**
	 * Is MLM enabled for the current organization
	 * @return unknown_type
	 */
	function isMLMEnabled() {
		return $this->currentorg->getConfigurationValue(CONF_MLM_ENABLED, false);
	}

	/**
	 * Get information about which slab the user is in.
	 * @param $user
	 * @return list($slab_name, $slab_number)
	 */
	public function getSlabInformationForUser(UserProfile $user) {
		//removed as CONF_LOYALTY_ENABLE_SLABS check removed in ListenersMgr::preprocessSuppliedData
		//if (!$this->currentorg->getConfigurationValue(CONF_LOYALTY_ENABLE_SLABS, false)) return;
		$org_id = $this->currentorg->org_id;
		$res = $this->db->query_firstrow("SELECT slab_name, slab_number from loyalty where user_id = $user->user_id and publisher_id = $org_id");
		$slab_name = $res['slab_name'];
		$slab_number = $res['slab_number'];

		if ($slab_name == false) {
			$slab_list = $this->getSlabsForOrganization();
			$slab_name = $slab_list[0];
			$slab_number = 0;
		}
		return array($slab_name, $slab_number);
	}


	function setRedemptionVoucherSeries($series_id) {
		return $this->currentorg->set(LOYALTY_REDEMPTION_VOUCHER_SERIES_KEY, $series_id);
	}
	function getRedemptionVoucherSeries() {
		return $this->currentorg->get(LOYALTY_REDEMPTION_VOUCHER_SERIES_KEY);

	}



	/**
	 * Issue a Validation Code to this user. Also sends out the sms using the template in LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE
	 * @param $user Customer to which the voucher has to be issued
	 * @return unknown_type
	 */
	function issueValidationCode(UserProfile $user = NULL, $points = 0) {
		if ($user == false) return ERR_LOYALTY_USER_NOT_REGISTERED;

		$vc = new ValidationCode();
		$additional_bits = 0;
		if($this->currentorg->getConfigurationValue(CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION, false))
			$additional_bits = $points;
		$vch = $vc->issueValidationCode($this->currentorg, $user->mobile, UserProfile::getExternalId($user->user_id), VC_PURPOSE_REDEMPTION, time(), $this->currentuser->user_id, $additional_bits);
		$loyalty_id = $this->getLoyaltyId($this->currentorg->org_id, $user->user_id);
		$balance = $this->getBalance($loyalty_id);
		$sms_template = Util::valueOrDefault($this->currentorg->get(LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE), LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE_DEFAULT);
		$args = array('validation_code' => $vch, 'total_points' => $balance);
		$sms = Util::templateReplace($sms_template, $args);
		
		Util::sendSms($user->mobile, $sms, $this->currentorg->org_id, MESSAGE_PRIORITY,
						false, '', false, false, array(), 
						$user->user_id, $user->user_id, 'VALIDATION' );
		return $vch;
	}


 	/*
 	 * @param $online_mode: The client needs to distinguish between redemption
 	 * validation codes in an offline/online situation. This in case online_mode
 	 * is true, we add an extra bit during validation code checking.
 	 */
	function isPointsRedeemable(UserProfile $user, $loyalty_id, $points, $bill_number, 
		$validation_code, $check_validation_code_errors = true, $offline_mode = false, 
                $store_id = '', $family_details = array(), $redeem = false, 
                $authorize_redemption_by_missed_call = false, $expire_missed_calls = false, $loyalty_log_id = 0, $notes = "",
                $skip_validation = false, $is_redeemable_call = false){

		global $counter_id,$currentorg;
		$cm = new ConfigManager();	
		$org_id = $this->currentorg->org_id;

		$this->logger->debug("Checking For Redemption : points to redeem = $points, bill_number = $bill_number, validation_Code=$validation_code user=$user->user_id");
	
		
		//Get the loyalty details
		$loyalty_details = $this->getLoyaltyDetailsForLoyaltyID($loyalty_id);
		if(!$loyalty_details || !$user)
			return ERR_LOYALTY_USER_NOT_REGISTERED;
		
				/*if ($is_redeemable_call){
					$this->logger->debug("isredeemaable call; no validation code check");
				}
			
                else */
                if($skip_validation && 
                		($cm->getKey(CONF_LOYALTY_IS_REDEMPTION_VALIDATION_REQUIRED, true)|| $is_redeemable_call))
                {
                    $can_skip_validation_code = $this->canSkipValidationCode();
                    
                    //
                    if($is_redeemable_call && !$cm->getKey('CONF_ALLOW_POINTS_REMEPTION_VALIDATION_OVERRIDE')){
                    	$this->logger->debug("Skipping validation code check");
                    }
                    
                    else if($can_skip_validation_code === true)
                    {
                        $this->logger->debug("Skipping validation code check");
                    }
                    else
                    {
                        //Return error
                        return $can_skip_validation_code;
                    }
                }
                // during is redeemable we done need to have the validaton code check
                else
                {
                    if($authorize_redemption_by_missed_call === true && $cm->getKey(CONF_LOYALTY_IS_REDEMPTION_VALIDATION_REQUIRED, true) && $cm->getKey('CONF_AUTHORIZE_REDEMPTION_WITH_MISSED_CALL'))
                    {
                        $isAuthorized = $this->isAuthorizedByMissedCall($user, $expire_missed_calls);
                        $this->logger->debug("isAuthorized is $isAuthorized");
                        $this->logger->debug("ERR_LOYALTY_MISSED_CALL_NOT_RECIEVED is " . ERR_LOYALTY_MISSED_CALL_NOT_RECIEVED);
                        if ($isAuthorized === ERR_LOYALTY_MISSED_CALL_NOT_RECIEVED)
                        {
                            $this->logger->debug("Could not authenticate using missed call");
                            return $isAuthorized;
                        }
                        else
                        {
                            $check_validation_code_errors = false;
                        }
                    }
                    else if($authorize_redemption_by_missed_call === true && $cm->getKey(CONF_LOYALTY_IS_REDEMPTION_VALIDATION_REQUIRED, true) && !$cm->getKey('CONF_AUTHORIZE_REDEMPTION_WITH_MISSED_CALL'))
                    {
                        $this->logger->debug("Authorization by missed call is disabled. Returning " . ERR_LOYALTY_MISSED_CALL_REDEMPTION_DISABLED);
                        return ERR_LOYALTY_MISSED_CALL_REDEMPTION_DISABLED;
                    }
                    //Check if validation code is required and has been supplied
                    if ($check_validation_code_errors == true && $cm->getKey(CONF_LOYALTY_IS_REDEMPTION_VALIDATION_REQUIRED, true) && $validation_code == ''  && !$this->currentuser->org->isAdminOrg()) {
                            return ERR_LOYALTY_INVALID_VALIDATION_CODE;
                    }

                    //If validation code has been supplied, check if its valid
                    if($check_validation_code_errors == true && $validation_code!='')
                    {
                            $org = new OrgProfile($org_id);
                            $valid = new ValidationCode();
                            $additional_bits = 0;
                            if($this->currentorg->getConfigurationValue(CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION, false))
                                    $additional_bits = $points;

                            if ($offline_mode) $additional_bits += BITS_FOR_OFFLINE_MODE;

                            $store_id = ($store_id != '') ? $store_id : $this->currentuser->user_id;
                            $store_id = $counter_id > 0 ? $counter_id : $store_id;


                            $time_in_zone = Util::getCurrentTimeInTimeZone('Europe/London');
                            $redeem_time = strtotime($time_in_zone);
                            $this->logger->debug("VTU $time $time_in_zone " . date('Y-m-d H:i:s', $time_in_zone));


                            /* $check = $valid->checkValidationCode($validation_code, $org, 
                                    $user->mobile, UserProfile::getExternalId($user->user_id), 
                                    VC_PURPOSE_REDEMPTION, $redeem_time, $store_id, 
                                    $additional_bits); */
                            $otp_manager = new OTPManager();
                            $check = $otp_manager->verify( $user->user_id, 'POINTS', $validation_code, $points, $redeem );

                            if($check == false)
                                    return ERR_LOYALTY_INVALID_VALIDATION_CODE;
                    }
                }
		if(Util::isPointsEngineActive())
		{
			$this->logger->debug("Points engine is active, it will do remaining checks...");
			try{
				 
				$event_client = new EventManagementThriftClient();
				$org_id = $this->currentorg->org_id;
				$store_id = $this->currentuser->user_id;
				$bill_number = "";

				$r_time = Util::getMysqlDateTime($redemption_time);

				$timeInMillis = strtotime($r_time);
				if($timeInMillis == -1 || !$timeInMillis )
				{
					throw new Exception("Cannot convert '$r_time' to timestamp", -1, null);
				}
				$timeInMillis = $timeInMillis * 1000;
				
				if(Util::isEMFActive())
				{
					$emf_controller = new EMFServiceController();
					//this will always be false,
					$commit = false;
					if($bill_number)
					{
						$loyalty_log_id = $this->db->query_first("SELECT ll.id as loyalty_log_id FROM loyalty_log as ll
							WHERE ll.org_id = $org_id AND ll.bill_number='$bill_number' and ll.loyalty_id = $loyalty_id
							AND ll.date >= '".  date( 'Y-m-d h:i:s', $timeInMillis - 300 )."' and ll.date <= '".  date( 'Y-m-d h:i:s', $timeInMillis + 300 )."' ");
						
						$loyalty_log_id = $loyalty_log_id ? $loyalty_log_id["loyalty_log_id"] : null;
						 
						
					}
					else
						$loyalty_log_id = null;
					$emf_result = $emf_controller->pointsRedemptionEvent ($org_id, $user->user_id, $points,
    						$store_id, $bill_number, $timeInMillis, $commit, 
							$loyalty_log_id, $validation_code, $notes, $reference_id=-1 );
					//Used points redemption event for EMF instead of isPointsRedeemableEvent 
// 					$emf_result = $emf_controller-> pointsRedemptionEvent(
// 							$org_id, $user->user_id, $points, $store_id, 
// 							$bill_number, $timeInMillis, $commit);
					
					$coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
					$this->listenersManager->issuedVoucherDetails($coupon_ids);
				}
				else
				{
					$event_client = new EventManagementThriftClient();
					$result = $event_client->isPointsRedeemableEvent(
							$org_id, $user->user_id, $points, $store_id,
							$bill_number, $timeInMillis);
					$this->logger->debug("Points Engine call result: " . print_r($result, true));
	
					$evaluation_id = $result->evaluationID;
				}
			}
			catch (emf_EMFException $emfEx) {
			
				$this->logger->error("Exception in Points Engine");
				$this->logger->error("Error code: " . $emfEx->statusCode . " Message: " . $emfEx->errorMessage );
				//convert EMF Error codes is not there because all error codes are same as Points Engine
				$errorCode = Util::convertPointsEngineErrorCode( $emfEx->statusCode );
				if(ErrorCodes::$points[-($emfEx->statusCode)])
					return -($emfEx->statusCode);
			}
			catch (eventmanager_EventManagerException $ex) {

				$this->logger->error("Exception in Points Engine");
				$this->logger->error("Error code: " . $ex->statusCode . " Message: " . $ex->errorMessage );
				$errorCode = Util::convertPointsEngineErrorCode( $ex->statusCode );
			} catch(Exception $ex){

				$this->logger->error("Error in signalling isPointsRedeemable Event");
				$this->logger->error("Error Code: " . $ex->getCode() . " Error Message: " . $ex->getMessage());
				$errorCode = $ex->getCode();
				$errorCode =  empty( $errorCode ) ? Util::convertPointsEngineErrorCode( $e->statusCode ) : $ex->getCode() ;
			}
			if($result != null || $evaluation_id > 0){

				return ERR_LOYALTY_SUCCESS;
			}else{
                                $this->logger->debug("Failed due to errors");
				return $errorCode;
			}
		}

		/*
		 * If Slab based thresholds are enabled, 
		 * We should use the configs set for the slab of the customer
		 * This values can be set in loyalty/slabbasedredemptionconfiguration
		 * 
		 * The same keys are stored, suffixed with the slab number.
		 * eg: CONF_LOYALTY_POINTS_REDEMPTION_MIN0, CONF_LOYALTY_POINTS_REDEMPTION_MIN2
		 *  
		 * */
		//Check if we have to use slab based configs
		$use_slab_based_thresholds = $this->currentorg->getConfigurationValue(CONF_LOYALTY_POINTS_REDEMPTION_USE_SLAB_THRESHOLDS, false);
		$current_slab_number = "";
		if($use_slab_based_thresholds){
			//get slab information for user
			list($current_slab_name, $current_slab_number) = $this->getSlabInformationForUser($user);
		}
		
		//Check if all Thresholds are met
		//In case of the family points thresholds will 
		//be the summation of the family head user &
		//the user who came to redeemed his points
		$balance = $loyalty_details['loyalty_points'];
		$life_time_points = $loyalty_details['lifetime_points'];
		$lifetime_purchases = $loyalty_details['lifetime_purchases'];
		if( $family_details['id'] ){
			
			//load family details & to the user details
			$balance += $family_details['loyalty_points'];
			$life_time_points += $family_details['lifetime_points'];
			$lifetime_purchases += $family_details['lifetime_purchases'];
		}
		
		//Check If points to be redeemed is less than CONF_LOYALTY_POINTS_REDEMPTION_MIN  
		$min_points_to_redeem = $this->currentorg->getConfigurationValue(CONF_LOYALTY_POINTS_REDEMPTION_MIN.$current_slab_number, 0);
		if($points < $min_points_to_redeem)
			return ERR_LOYALTY_INSUFFICIENT_REDEMPTION_POINTS;
		
		//Check If current points is less than CONF_LOYALTY_POINTS_REDEMPTION_CURRENT_POINTS_MIN  
		$min_current_points_req = $this->currentorg->getConfigurationValue(CONF_LOYALTY_POINTS_REDEMPTION_CURRENT_POINTS_MIN.$current_slab_number, 0);
		if( $balance < $min_current_points_req)
			return ERR_LOYALTY_INSUFFICIENT_CURRENT_POINTS;
			
		//Check If lifetime points is less than CONF_LOYALTY_POINTS_REDEMPTION_LIFETIME_POINTS_MIN  
		$min_lifetime_points_req = $this->currentorg->getConfigurationValue(CONF_LOYALTY_POINTS_REDEMPTION_LIFETIME_POINTS_MIN.$current_slab_number, 0);
		if( $life_time_points < $min_lifetime_points_req)
			return ERR_LOYALTY_INSUFFICIENT_LIFETIME_POINTS;

		//Check If lifetime purchases is less than CONF_LOYALTY_POINTS_REDEMPTION_LIFETIME_PURCHASE_MIN  
		$min_lifetime_purchase_req = $this->currentorg->getConfigurationValue(CONF_LOYALTY_POINTS_REDEMPTION_LIFETIME_PURCHASE_MIN.$current_slab_number, 0);
		if( $lifetime_purchases < $min_lifetime_purchase_req)
			return ERR_LOYALTY_INSUFFICIENT_LIFETIME_PURCHASE;

		//Check If points being redeemed is divisible by CONF_LOYALTY_POINTS_REDEMPTION_DIVISIBLE_BY  
		$divisible_by = $this->currentorg->getConfigurationValue(CONF_LOYALTY_POINTS_REDEMPTION_DIVISIBLE_BY.$current_slab_number, 1);
		if(($points % $divisible_by) != 0)
			return ERR_LOYALTY_REDEMPTION_POINTS_NOT_DIVISIBLE;
			
		//Check if the points to be redeemed are less than the current points
		if ( $balance < $points)
			return ERR_LOYALTY_INSUFFICIENT_POINTS;
			
		//everything fine
		return ERR_LOYALTY_SUCCESS;
	}

	/*
	 * This function is supposed to update the details of a points redemption
	 * that was done offline via the mobile action. When points redemption
	 * happens through the cell phone API, details such as the bill number,
	 * bill notes are not present. These are entered locally
	 * on the client and update later via this API action.
	 */
 	function updatePointsRedemptionDetails(UserProfile $user, $loyalty_id, $points_redeemed, $bill_number,
					$validation_code, $notes, $store_id, $redemption_time) {

		$org_id = $this->currentorg->org_id;
		$user_id = $user->user_id;

 		// table is deprecated
// 		$org_id = $this->currentorg->org_id;
// 		$user_id = $user->user_id;

// 		$redemption_time = Util::getMysqlDateTime($redemption_time);

// 		$sql = "
// 			UPDATE `loyalty_redemptions`
// 			SET `notes` = '$notes',
// 				`bill_number` = '$bill_number'
// 			WHERE `org_id` = '$org_id' AND `user_id` = '$user_id' AND `loyalty_id` = '$loyalty_id'
// 					AND `points_redeemed` = '$points_redeemed' AND `voucher_code` = '$validation_code'
// 					AND `entered_by` = '$store_id' AND (TIMESTAMPDIFF(MINUTE, `date`, '$redemption_time') <= 15)
// 		";
// 		$this->db->update($sql);

		return ERR_LOYALTY_SUCCESS;
 	}

	/**
	 * Redeem points. check for the following conditions:
	 * 1. If the user is registered with the loyalty program
	 * 2. If the user has enough points balance
	 * 3. If the user has a valid validation code
	 * @param $user Customer whose points are being redeemed
	 * @param $loyalty_id Loyalty ID - used to ensure the user is registered
	 * @param $points Points to be redeemed
	 * @param $bill_number Bill Number to be used as reference
	 * @param $validation_code Validation code for redemption
	 * @param $notes Notes for Redemption
	 * @param $entered_by Store which entered the redemption
 	 * 
 	 * @param $offline_mode Redeem points offline. This validates whether the client is supposed 
 	 * can make a valid redemption and if successful redeems the points of the
 	 * client. The validation of the client etc., happens at the client. The
 	 * missing details are updated later at the server.
	 */
	function redeemPoints(UserProfile $user, $loyalty_id, $points, $bill_number,
		$validation_code, $notes, $entered_by, $redeem_time = '', $offline_mode = false, $authorize_redemption_by_missed_call = false, $skip_validation = false) {
		
		global $counter_id;
		
		$org_id = $this->currentorg->org_id;
		$counter_id = isset($counter_id) ? $counter_id : -1;
			
		if ($redeem_time == '') $redeem_time = Util::getCurrentTimeForStore($this->currentuser->user_id);
		$redeem_timestamp = Util::deserializeFrom8601($redeem_time);
		
		//Check if we are allowed to redeem by the customer
		$disallow_fraud_statuses = json_decode($this->currentorg->getConfigurationValue(CONF_FRAUD_STATUS_CHECK_REDEMPTION, json_encode(array())), true);
		//get the fraud status for the customer
		$customer_fraud_status = $this->getFraudStatus($user->user_id);
		if(count($disallow_fraud_statuses) > 0 && strlen($customer_fraud_status) > 0 && in_array($customer_fraud_status, $disallow_fraud_statuses))
			return ERR_LOYALTY_FRAUD_USER;

		/**
		 * For Family Account 
		 */

                /**

                Commenting out the family bull shit

		$family_head_id = false;
		if( $this->currentorg->getConfigurationValue( CONF_ENABLE_CUSTOMER_FAMILY, false ) ){

			$family = Family::getByMember( $user->user_id );
			$family_head_id = $family->family_head;
			
			$family_details = $this->getLoyaltyDetailsForUserID( $family_head_id );
		}
		
                ***/
 		
 		$ret = $this->isPointsRedeemable(
                                $user, $loyalty_id, $points, $bill_number, 
                                $validation_code, true, $offline_mode, $entered_by, 
                                $family_details, 
                                $invalidate = false, $authorize_redemption_by_missed_call, false, $loyalty_log_id, $notes, $skip_validation );

 		if($ret != ERR_LOYALTY_SUCCESS ) return $ret;
		
		$this->logger->debug("
								Trying to Redeem : points to redeem = $points, 
								bill_number = $bill_number, 
								validation_Code=$validation_code, 
								user=$user->user_id
							");
		
		//Redeem The Points By Deducting The Points :
		// 1. Add loyalty redemption details for the user.& family.
		// 1. Deduct The Points 1st From The User.
		// 2. Remaining Points are needed to be deducted from family account.
		$ret = true;
		$remaining_balance = 0;
		$points_to_deduct = $points;
		$customer_details = $this->getLoyaltyDetailsForUserID( $user->user_id );		
		
                if( $family_head_id ){
		/****** family has been planned.. people are using ***** now ****	

			$customer_balance = $customer_details['loyalty_points'];
			
			if( $points > $customer_balance ){

				$points_to_deduct = $customer_balance;
				$remaining_balance = $points - $customer_balance;

				//ADD REDEMPTION FOR FAMILY
				$this->loyaltyModel->InsertResult();
				$loyalty_redemption_id = $this->loyaltyModel->addRedemptionDetails( 
										$family_details['id'], $remaining_balance, $validation_code, $notes, $bill_number, $entered_by, $family_details['user_id'], $counter_id 
									);
			
				if ($loyalty_redemption_id == false) return ERR_LOYALTY_UNKNOWN;

				$this->loyaltyModel->updateTable();
				$ret = $this->loyaltyModel->deductLoyaltyPointsForCustomer( $remaining_balance, $entered_by, $family_details['id'] );
			}
			
			if( $ret ){
				
				//ADD CUSTOMER REDEMPTION
				$this->loyaltyModel->InsertResult();
				$loyalty_redemption_id = $this->loyaltyModel->addRedemptionDetails( 
										$loyalty_id, $points_to_deduct, $validation_code, $notes, $bill_number, $entered_by, $user->user_id, $counter_id 
									);

				if ($loyalty_redemption_id == false) return ERR_LOYALTY_UNKNOWN;
				
				$this->loyaltyModel->updateTable();
				$ret = $this->loyaltyModel->deductLoyaltyPointsForCustomer( $points_to_deduct, $entered_by, $loyalty_id );
				if( !$ret && $family_head_id )
					$ret = $this->loyaltyModel->deductLoyaltyPointsForCustomer( -$remaining_balance, $entered_by, $family_details['id'] );
			}
                 *****/
                
		}else{
                

			//update if no family is enabled
			$this->loyaltyModel->InsertResult();
			$loyalty_redemption_id = $this->loyaltyModel->addRedemptionDetails( 
										$loyalty_id, $points, $validation_code, $notes, $bill_number, 
										$entered_by, $user->user_id, $counter_id, $redeem_time 
									);
			
			if ($loyalty_redemption_id == false) return ERR_LOYALTY_UNKNOWN;


			// ingest event here, since the redemption id has been generated
			$redemptionEventAttributes = array();
			$redemptionEventAttributes["customerId"] =intval($user->user_id); // done to convert this to long
			$redemptionEventAttributes["billNumber"] = $bill_number;
			$redemptionEventAttributes["redemptionId"] = $loyalty_redemption_id;
			$redemptionEventAttributes["entityId"] =  intval($this->currentuser->user_id); // $transaction[""];

			EventIngestionHelper::ingestEventAsynchronously( intval($this->currentorg->org_id), "pointredemption",
				"Point redemption event from the Intouch PHP API's", $redeem_timestamp, $redemptionEventAttributes);


			$this->loyaltyModel->updateTable();
			$ret = $this->loyaltyModel->deductLoyaltyPointsForCustomer( $points, $entered_by, $loyalty_id );
		}
		
		if( !$ret ) return ERR_LOYALTY_UNKNOWN;
                
		$this->logger->debug("updated points");

		//Enables bill Wise Point Deduction For Credit-Debit Report
		$bill_wise_redemption = $this->currentorg->getConfigurationValue(CONF_LOYALTY_ENABLE_SPLIT_REDEEM_POINTS, false);
		if($loyalty_redemption_id && $bill_wise_redemption)
			$this->debitPointsForCustomer($loyalty_id, $points, $loyalty_redemption_id, $redeem_timestamp, $entered_by);

		$params = array();
		$this->loadRedemptionEventListenersParams( $params, $customer_details, $points_to_deduct, $bill_number, $validation_code, $redeem_timestamp, $notes );
		$this->loadFamilyRedemptionEventListenersParams( $params, $family_details, $customer_details, $points, $remaining_balance );	

		$ret = $this->listenersManager->signalListeners(EVENT_LOYALTY_REDEMPTION, $params);
		return $loyalty_redemption_id;
	}

	/**
	 * loads the params for the user to be suppplied as
	 * supplied data for the listeners execution.
	 * }
	 * @param unknown_type $params
	 */
	private function loadRedemptionEventListenersParams( &$params, $customer_details, $points, $bill_number, $validation_code, $redeem_time, $notes ){
		
		$params['user_id'] = $customer_details['user_id'];
		$params['total_points'] = $customer_details['loyalty_points'];
		$params['redeemed_points'] = $points;
		$params['bill_number'] = $bill_number;
		$params['validation_code'] = $validation_code;
		$params['redeemed_time'] = Util::getMysqlDateTime($redeem_time);
		$params['notes'] = $notes;

		$visits_and_bills = $this->getNumberOfVisitsAndBillsForUser($customer_details['user_id'], $datetime );
		$params['num_of_bills'] = $visits_and_bills['num_of_bills'];
		$params['num_of_visits'] = $visits_and_bills['num_of_visits'];
		$params['num_of_bills_today'] = $visits_and_bills['num_of_bills_today'];
		$params['num_of_bills_n_days'] = $visits_and_bills['num_of_bills_n_days'];
	}
	
	/**
	 * loads the params for the family to be suppplied as
	 * supplied data for the listeners execution.
	 *  
	 * @param $params
	 */
	private function loadFamilyRedemptionEventListenersParams( &$params, $family_details, $customer_details , $points, $remaining_balance ){
		
		if( !$family_details['id'] ) return;
		
		$params['family_user_id'] = $family_details['user_id'];
		$params['family_points'] = $family_details['loyalty_points'];
		$params['family_redeemed_points'] = $remaining_balance;
		$params['redeemed_points_family_included'] = $points;

		$visits_and_bills = $this->getNumberOfVisitsAndBillsForUser( $family_details['user_id'], $datetime);
		$params['family_member_num_of_bills'] = $visits_and_bills['num_of_bills'];
		$params['family_member_num_of_visits'] = $visits_and_bills['num_of_visits'];
		$params['family_member_num_of_bills_today'] = $visits_and_bills['num_of_bills_today'];
		$params['family_member_num_of_bills_n_days'] = $visits_and_bills['num_of_bills_n_days'];
	}
	
	function debitPointsForCustomer($loyalty_id, $points, $loyalty_redemption_id, $redeem_time, $entered_by, $trans = false){

		$redeem_time = Util::getMysqlDateTime($redeem_time);
		$org_id = $this->currentorg->org_id;
		
		//create temporary tables for loyalty log and awarded points table
		$temp_table = "split_redemption_".$entered_by.'_'.$loyalty_id.'_'.time();
		
		$sql = "
			CREATE TABLE `user_management`.`$temp_table` (
			 `primary_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`id` INT NOT NULL ,
			`points` INT NOT NULL ,
			`left_amount` INT NOT NULL ,
			`entered_by` INT NOT NULL ,
			`type` ENUM( 'bill', 'awarded' ) NOT NULL,
			`date` DATETIME NOT NULL
			)		
		";
		$this->db->update($sql);
		
		//insert loyalty bills
		$sql = " 
				INSERT INTO $temp_table(`id`, `points`, `left_amount`, `entered_by`, `type`, `date`)
					SELECT `id`, `points`, `points` - `redeemed` AS `left_amount`, `entered_by`, 'bill', `date`
					FROM `loyalty_log`
					WHERE `org_id` = '$org_id' AND `loyalty_id` = '$loyalty_id' AND `points` - `redeemed` - `expired` > 0 
					ORDER BY `date` ASC
				";
		$res_loyalty_log = $this->db->update($sql);
		
		//insert awarded points
		$sql = " 
				INSERT INTO $temp_table(`id`, `points`, `left_amount`, `entered_by`, `type`, `date`)
					SELECT `id`, `awarded_points`, `awarded_points` - `redeemed_points` AS `left_amount`, `awarded_by`, 'awarded', `awarded_time`
					FROM `awarded_points_log`
					WHERE `org_id` = '$org_id' AND `loyalty_id` = '$loyalty_id' AND `awarded_points` - `redeemed_points` - `expired_points` > 0
					ORDER BY `awarded_time` ASC
				";
		$res_awarded_log = $this->db->update($sql);

		if(!$res_awarded_log && !$res_loyalty_log)
			return;

		do{
			
			$sql = " SELECT SUM(`left_amount`) AS `total_loyalty_points` 
						FROM $temp_table
						ORDER BY `date` ASC
					";
			$total_loyalty_points = $this->db->query_scalar($sql);
			
			if(!$total_loyalty_points){
				$points = false;
				$this->logger->debug(" The Total Points Available Has Become Zero : Time to return");
			}

			$sql = "
 
					SELECT `primary_id`, `id`, `points`, `left_amount`, `entered_by`, `type`
					FROM `$temp_table`
					ORDER BY `date` ASC			
			";
			$res = $this->db->query_firstrow($sql);

			$primary_id = $res['primary_id'];
			$sql = " DELETE FROM `$temp_table` WHERE `primary_id` = $primary_id";
			$this->db->update($sql);
			
			$points = $this->splitRedeemPoints($points, $res, $loyalty_id, $redeem_time, $loyalty_redemption_id, $entered_by);

		}while($points > 0);
		
		$sql = " DROP TABLE `$temp_table`";
		$this->db->update($sql);
	}

	function splitRedeemPoints($points, $res, $loyalty_id, $redeem_time, $loyalty_redemption_id, $entered_by){
		
		$org_id = $this->currentorg->org_id;
		$store_id = $entered_by;
		
		$log_id = $res['id'];
		$points_gained_on_bill = $res['points'];
		$points_left = $res['left_amount'];
		$points_awarded_at = $res['entered_by'];
		$type = $res['type'];
		
		$this->logger->debug("log_id : $log_id, points gained : $points_gained_on_bill, 
								left_amount : $points_left, awarded_points : $points_awarded_at, store : $store_id, type : $type");
		
		if($points > $points_left ){
			
			$points_to_deduct = $points_left;
			$points = $points - $points_to_deduct;
			$this->logger->debug("deduct_1 : points to deduct : $points_to_deduct, points_left : $points");
		}else{
			
			$points_to_deduct = $points ;
			$points = false;
			$this->logger->debug("deduct_2 : points to deduct : $points_to_deduct, points_left : $points");
		}

		if($type == 'bill'){

			$sql = " UPDATE `loyalty_log` SET `redeemed` = `redeemed` + '$points_to_deduct' WHERE `id` = '$log_id' ";
		}elseif($type == 'awarded'){

			$sql = " UPDATE `awarded_points_log` SET `redeemed_points` = `redeemed_points` + '$points_to_deduct' WHERE `id` = '$log_id' ";
		}
		$res = $this->db->update($sql);
			
		if($res){
			
			$sql = "
			 INSERT INTO `points_split_history` 
			 	(`org_id`, `loyalty_id`, `reference_id`, `loyalty_redemption_id`, 
			 	`debited_at`, `redeemed_at`, `points_debited`, `debited_date`, `type`) 
			 	
			 VALUES
			 	(
			 		'$org_id', '$loyalty_id', '$log_id', '$loyalty_redemption_id',
			 		'$points_awarded_at', '$store_id', '$points_to_deduct', '$redeem_time', '$type'
			 	)  ";
			
			$this->db->insert($sql);
			
			return $points;
		}else{
			
			$this->logger->debug(" Log Id : $log_id : Some Error Occured Please Check for type : $type");
			return false;
		}
	}

	/**
	 * Create a new user in the loyalty
	 * @param $org_id
	 * @param $user_id
	 * @param $loyalty_points
	 * @param $joined
	 * @param $last_updated
	 * @param $registered_by
	 * @param $last_updated_by
	 * @param $slab_name
	 * @param $slab_number
	 * @return Loyalty ID for the user
	 */
	function addUserToLoyalty(
				$org_id, $user_id, $loyalty_points, $joined, $last_updated, $registered_by, $last_updated_by, $slab_name, $slab_number
			){

		global $counter_id;

		if($slab_name == 'NULL')
		$slab_name = '';

                if(Util::isPointsEngineActive())
                {
                   $this->logger->debug("Points engine active, setting points = 0, slab_number = 0, slab_name = '' for org: $org_id, user: $user_id, $joined");      
                   $loyalty_points = 0;                        
                   $slab_number = 0;
                   $slab_name = '';
                }

		$sql = "INSERT INTO `loyalty` ( `publisher_id`, `user_id`, `loyalty_points`, `joined`, `last_updated`, `registered_by`, `last_updated_by`, `slab_name`, `slab_number`, 
			`counter_id`, `base_store` ) VALUES ( '$org_id', '$user_id', $loyalty_points, '$joined', '$last_updated', '$registered_by', '$last_updated_by', '$slab_name', 
			 $slab_number, '$counter_id', '$registered_by') ON DUPLICATE KEY UPDATE `user_id` = VALUES( `user_id` )";

		$this->logger->debug("Executing query: $sql");		
		$id =  $this->db->insert($sql);
		
		return $id;
	}


	/**
	 * Add this user into our loyalty program and update its user details. Also triggers the right listeners.
	 * It does the following actions:
	 * 1. Register in the loyalty program, or if already registered, retrieve the loyalty_id
	 * 2. Update all the user profile details
	 * 3. Update the External ID to match the new value
	 * 4. Trigger listeners in case its a new registration
	 * @param $user
	 * @return Response ERROR CODE
	 */
	function registerInLoyaltyProgram(UserProfile $user, $external_id, $firstname, $lastname, $email , $joined_date , $signal_loyalty_registration_event = true 
                                    , $validation_code = '', $customer_info = null) {
                global $logger;
		$firstname = $this->db->realEscapeString( $firstname );
		$lastname = $this->db->realEscapeString( $lastname );
		$email = $this->db->realEscapeString( $email );
		
		$org_id = $this->currentorg->org_id;
		$current_user_id = $this->currentuser->user_id;

                $new_reg = false;

		if ($user == false) return ERR_LOYALTY_USER_NOT_REGISTERED;

		if($validation_code !='')
		{

			$org = new OrgProfile($org_id);
			$valid = new ValidationCode();
			$code = $valid->checkValidationCode($validation_code,$org,$user->mobile,UserProfile::getExternalId($user->user_id),VC_PURPOSE_REGISTRATION,time(),-1);
			if($code == false)return ERR_LOYALTY_INVALID_VALIDATION_CODE;
		}

		if ($this->getConfigurationValue(CONF_USERS_IS_EMAIL_REQUIRED, true) && $email == '' ) {
			return ERR_LOYALTY_REGISTRATION_FAILED;
		}

		if ($this->getConfigurationValue(CONF_USERS_IS_EXTERNAL_ID_REQUIRED, true) && $external_id == '' ) {
			return ERR_LOYALTY_REGISTRATION_FAILED;
		}

		$slab_name = "NULL";
		$slab_number = "NULL";
		$use_slabs = $this->getConfigurationValue(CONF_LOYALTY_ENABLE_SLABS, false);
		if ($use_slabs) {
			$slablist = $this->getSlabsForOrganization();
			if (count($slablist) > 0) {
				$slab_name = $slablist[0];
				$slab_number = "0";
			}
		}

		$ret_arr = $this->getUserIdFromEmailId($email);

		//$count = $ret_arr['count'];
		$existing_email_user_id = $ret_arr['user_id'];

		if($existing_email_user_id){
			if($this->getConfigurationValue(CONF_USERS_IS_EMAIL_UNIQUE, true) && ($existing_email_user_id != $user->user_id))
			return ERR_LOYALTY_PROFILE_UPDATE_FAILED;
		}
		
		//Check if user exists.
		$loyalty_id = $this->getLoyaltyId($org_id, $user->user_id);
		if(!$loyalty_id){
                        $joined_date_sql = Util::getMysqlDateTime($joined_date);
                        $loyalty_points = 0;  
                        $logger->debug("customer_info: " . print_r($customer_info, true));  
                        if($customer_info != null){
                                $loyalty_points = (int)$customer_info->current_points;
                        } 
                        $logger->debug("setting loyalty points for customer: $loyalty_points"); 
			$id = $this->addUserToLoyalty($org_id, $user->user_id, $loyalty_points, $joined_date_sql, $joined_date_sql, $current_user_id, $current_user_id, $slab_name, $slab_number);
		}
		
		if ($id) {
			$loyalty_id = $id;
			$new_reg = true;
		} else {

			$loyalty_id = $this->getLoyaltyId($org_id, $user->user_id);
		}
		
		if (!$loyalty_id)
			return ERR_LOYALTY_REGISTRATION_FAILED;

		$ret_status_1 = true;
		if($new_reg === true || $this->getConfigurationValue(CONF_LOYALTY_ALLOW_EXTERNAL_ID_UPDATE, false))
			$ret_status_1 = $this->updateExternalId($loyalty_id, $external_id);

		$eup = new ExtendedUserProfile($user, $this->currentorg);
		$eup->setFirstName($firstname);
		$eup->setLastName($lastname);
		$eup->setEmail($email);
                if($customer_info != null){
                        $sex = (string)$customer_info->sex;
                        $birthday = (string)$customer_info->birthday;
                        $address = (string)$customer_info->address;       

                        $eup->setSex($sex);
                        $eup->setBirthday($birthday);
                        $eup->setAddress($address);
                }

		$ret_status_2 = $eup->save();

		//update the first and last name if its not a new registration
		if($new_reg === false){			
			$sql = "
				UPDATE users
				SET firstname = '$firstname',
					lastname = '$lastname'
				WHERE `id` = '".$user->user_id."'
			";			
			$this->db->update($sql);
		}
		
		if ($ret_status_1 == false || $ret_status_2 == false) return ERR_LOYALTY_PROFILE_UPDATE_FAILED;
		
		$this->addCustomFieldsForUser($customer_info, $user);
	
		//if its not a new registration, the listener should not be activated
		if ($new_reg === true && $signal_loyalty_registration_event) {
			$err = $this->listenersManager->signalListeners( EVENT_LOYALTY_REGISTRATION, array( 'user_id' => $user->user_id ) );
			//Error in listener is not an error in registration
			//if (false == $err) return ERR_LOYALTY_LISTENER; 
		}
		
		return $loyalty_id;
	}
	
        /**
	  this function adds the custom fields of the newly registered user
          @params:-
              1) customer xml object     
              2) user profile object
              3) loyalty id of the customer                   

        **/
	private function addCustomFieldsForUser($customer_info, $user)
	{        

                //store the custom field information for the customers
                $this->logger->debug("Adding the custom fields for user: $user->user_id");
                if(!$customer_info || !method_exists($customer_info, 'xpath'))
                {
                   $this->logger->debug("Customer Info object is null...returning\n");
                   return true;
                }
                
                $cf_data = array();
                foreach($customer_info->xpath('custom_fields_data/custom_data_item') as $cfd){
                        $cf_name = (string) $cfd->field_name;
                        $cf_value_json = (string) $cfd->field_value;
                        array_push($cf_data, array('field_name' => $cf_name, 'field_value' => $cf_value_json));
                }
                $assoc_id = $user->user_id;
                if(count($cf_data) > 0)
                {
                        $cf = new CustomFields();
                        $cf->addCustomFieldDataForAssocId($assoc_id, $cf_data);
                }
                
                $this->logger->debug("Custom fields addition done.. returning back");
        }
	
	
		/**
	 * Add this user into our loyalty program and update its user details. Also triggers the right listeners.
	 * It does the following actions:
	 * 1. Register in the loyalty program, or if already registered, retrieve the loyalty_id
	 * 2. Update all the user profile details
	 * 3. Update the External ID to match the new value
	 * 4. Trigger listeners in case its a new registration
	 * @param $user
	 * @return Response ERROR CODE
	 */
	function UpdateLoyaltyUserApi( UserProfile $user , $external_id = null , $firstname = null , $lastname = null , $email = null 
								  , $validation_code = '' , $customer_info = null ) 
    {
	 	
        global $logger;
		$firstname = $this->db->realEscapeString( $firstname );
		$lastname = $this->db->realEscapeString( $lastname );
		$email = $this->db->realEscapeString( $email );
		
		$org_id = $this->currentorg->org_id;
		$current_user_id = $this->currentuser->user_id;

		if ( $user == false ) return ERR_LOYALTY_USER_NOT_REGISTERED;
				
		if( $validation_code != '' )
		{
			$org = new OrgProfile( $org_id );
			$valid = new ValidationCode();
			$code = $valid->checkValidationCode( $validation_code , 
												 $org ,
												 $user->mobile , 
												 UserProfile::getExternalId($user->user_id) ,
												 VC_PURPOSE_REGISTRATION,time(),
												 -1
											   );
			
			if( $code == false ) return ERR_LOYALTY_INVALID_VALIDATION_CODE;
		}
/*
	//	if ($this->getConfigurationValue(CONF_USERS_IS_EMAIL_REQUIRED, true) && $email == '' ) 
//		{
	//		return ERR_EMAIL_REQUIRED_FAILED;
		//	return ERR_LOYALTY_REGISTRATION_FAILED;
//		}


		$ret_arr = $this->getUserIdFromEmailId( $email );

		$existing_email_user_id = $ret_arr['user_id'];

		if($existing_email_user_id)
		{
			if($this->getConfigurationValue(CONF_USERS_IS_EMAIL_UNIQUE, true) && ($existing_email_user_id != $user->user_id))
			return ERR_LOYALTY_PROFILE_UPDATE_FAILED;
		}
	*/	
		
		//Check if user exists.
		$loyalty_id = $this->getLoyaltyId($org_id, $user->user_id);

		if ( !$loyalty_id )
			return ERR_LOYALTY_USER_NOT_REGISTERED;

		$ret_status_1 = true;
		if($this->getConfigurationValue( CONF_LOYALTY_ALLOW_EXTERNAL_ID_UPDATE , false ) && $external_id )
				$ret_status_1 = $this->updateExternalId( $loyalty_id , $external_id );

		$eup = new ExtendedUserProfile( $user , $this->currentorg );
		
		if( $firstname )
    		$eup->setFirstName( $firstname );
		if( $lastname )
    		$eup->setLastName( $lastname );
		if( $email )
    		$eup->setEmail( $email );
    	
		if( $customer_info->sex )
    	{  
    		$sex = (string)$customer_info->sex;
    		$eup->setSex($sex);
    	}
    	if( $customer_info->birthday )
    	{
    		$birthday = (string)$customer_info->birthday;
    		$eup->setBirthday($birthday);
        } 
	    if( $customer_info->address )  
	    {
	    	$address = (string)$customer_info->address;       
       		$eup->setAddress($address);
	    } 
    	
		$ret_status_2 = $eup->save();

		//update the first and last name if its not a new registration

		if( $firstname && $lastname )
		{
			$sql = "
				UPDATE users
				SET firstname = '$firstname',
					lastname = '$lastname'
				WHERE `id` = '".$user->user_id."'
			";			
			$this->db->update($sql);
		}
		
		if ($ret_status_1 == false || $ret_status_2 == false) return ERR_LOYALTY_PROFILE_UPDATE_FAILED;
		
		return $loyalty_id;
	}
	
	
	public function getlastfewTransactions( $loyalty_id )
	{
		
		$org_id = $this->currentorg->org_id;
		$sql = "SELECT ll.*, oe.code as store 
			FROM `loyalty_log` ll 
			JOIN masters.org_entities oe ON oe.id =  ll.entered_by AND oe.org_id = ll.org_id 
			WHERE ll.`loyalty_id` = '$loyalty_id' AND ll.org_id = $org_id
			ORDER BY `date` DESC LIMIT 10
		";
		$table = $this->db->query($sql);
		return $table;
	}
	

	public function updateExternalId($loyalty_id, $external_id) {
		$this->logger->debug('@Inside Update External Id : '.$external_id);
		$org_id = $this->currentorg->org_id;
		$external_id = trim($external_id);
		$update_str = $external_id && strlen($external_id) > 0 ? " = '$external_id' " : " = NULL ";

		$sql = "UPDATE loyalty SET external_id $update_str, `last_updated` = NOW() WHERE id = '$loyalty_id' AND publisher_id = '$org_id'";
		return $this->db->update($sql);
	}

	public function updateCurrentPoints($loyalty_id, $current_points) {
		$org_id = $this->currentorg->org_id;
		$this->logger->debug("Updating loyalty points for loyalty_id = $loyalty_id to $current_points");
		$safe_current_points = Util::mysqlEscapeString($current_points);
		$sql = "UPDATE loyalty SET `loyalty_points` = '$safe_current_points', `last_updated` = NOW() WHERE id = '$loyalty_id' AND publisher_id = '$org_id'";
		return $this->db->update($sql);
	}

	public function updateLifetimePoints($loyalty_id, $lifetime_points) {
		$org_id = $this->currentorg->org_id;
		$this->logger->debug("Updating lifetime points for loyalty_id = $loyalty_id to $lifetime_points");
		$sql = "UPDATE loyalty SET `lifetime_points` = '$lifetime_points', `last_updated` = NOW() WHERE id = '$loyalty_id' AND publisher_id = '$org_id'";
		return $this->db->update($sql);
	}

	public function getcustomerstable($org_id, $filter, $type ='table'){

		if($type == 'table')
		return $this->db->query_table("SELECT * FROM `loyalty` WHERE `publisher_id` = '$org_id' $filter LIMIT 20 ", 'customersTable');
		else
		return $this->db->query("SELECT * FROM `loyalty` WHERE `publisher_id` = '$org_id' $filter LIMIT 20 ");

	}

	function getlastfewTranscation( $loyalty_id, $type = 'table' )
	{
		
		$org_id = $this->currentorg->org_id;
		$sql = "SELECT ll.*, oe.code as store 
			FROM `loyalty_log` ll 
			JOIN masters.org_entities oe ON oe.id =  ll.entered_by AND oe.org_id = ll.org_id 
			WHERE ll.`loyalty_id` = '$loyalty_id' AND ll.org_id = $org_id
			ORDER BY `date` DESC
		";
		if($type == 'table')
		$table = $this->db->query_table($sql,'transaction');
		else
		$table = $this->db->query($sql);
		return $table;
	}

	function getBillsOnDate($loyalty_id, $date){
		$org_id = $this->currentorg->org_id;
		$sql = "SELECT ll.* 
			FROM `loyalty_log` ll 
			WHERE ll.`loyalty_id` = '$loyalty_id' AND ll.org_id = $org_id
				AND DATE(`date`) = DATE('$date')
			ORDER BY `date` DESC
		";
		return $this->db->query($sql);
	}
	
	function getlastfewRedemtion($loyalty_id, $type = 'table')
	{
		// not used now, so removing it now :)
		if($type == 'table')
			$table = null;
		
		$org_id = $this->currentorg->org_id;
		$sql = "
			SELECT lr.bill_number, validation_code as voucher_code, points_redeemed, redemption_time as date, notes, lr.till_id as store 
			FROM points_redemption_summary lr
			INNER JOIN `org_participation` as op on op.org_id = lr.org_id and lr.program_id = op.program_id 
			WHERE lr.loyalty_id = '$loyalty_id' AND lr.org_id = $org_id 
			ORDER BY lr.`date` DESC
		";

		$db_warehouse = new Dbase ("warehouse");
		$table = $db_warehouse->query($sql);

		if($table)
		{
			$userId = array();
			foreach($table as $row)
				$userId[] = $row["store"];
			
			$db_masters = new Dbase("masters", true);
			$sql = "SELECT id, code as store from org_entities as oe where oe.id in (". implode(",", $userId).") and org_id = ". $org_id ;
			$stores = $db_masters->query_hash($sql, "id", array("store"));
			
			foreach($table as $key=>$row)
				$table[$key]["store"] = $stores[$row[$key]["store"]]["id"];
		}
		
		
		
		return $table;
	}

	function getVoucherRedemtionHistory($user_id, $type = 'table')
		{
			$org_id = $this->currentorg->org_id;
			$sql = "
				SELECT DISTINCT CONCAT(`eup`.`firstname`,' ',`eup`.`lastname` ) AS Customer, vs.discount_code as Voucher_Code
				, oe.code as used_at_store, vr.used_date as redeemed_on, vs.description as Voucher_details
				FROM `campaigns`.`voucher_redemptions` vr
				JOIN `user_management`.users eup ON (eup.id = vr.used_by AND eup.org_id = vr.org_id) 
				JOIN masters.org_entities oe ON oe.id =  vr.used_at_store AND oe.org_id = vr.org_id 
				JOIN `campaigns`.`voucher_series` vs ON vs.id = vr.voucher_series_id
				WHERE vr.used_by = $user_id AND vr.org_id = $org_id
				ORDER BY vr.used_date DESC
			";
			if($type == 'table')
			$table = $this->db->query_table($sql,'voucher_redemption');
			else
			$table = $this->db->query($sql);
			return $table;
		}
		
		
	function InsertCancelledBills($user_id, $loyalty_id, $bill_number, $me, $entered_time){

		$org_id = $this->currentorg->org_id;

		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$sql = "INSERT INTO cancelled_bills (org_id, user_id, loyalty_id, bill_number, entered_by, entered_time) "
		. "VALUES ($org_id, $user_id, $loyalty_id, '$safe_bill_number', $me, '$entered_time')";

		$ret = $this->db->insert($sql);

		return $ret;


	}

	/**
	 * @param unknown_type $bill_number
	 * @param unknown_type $bill_amount
	 * @param unknown_type $not_interested_reason
	 * @param unknown_type $billing_time
	 * @param unknown_type $store_id
	 * @param $lineitems array of elements containing the line_item details. required : (serial, item_code, description, rate, qty, value, discount_value, amount, store_id)
	 */
	function addNotInterestedBill($bill_number, $bill_amount, $not_interested_reason, $billing_time, $store_id, $line_items){
		
		global $counter_id;
		$counter_id = isset($counter_id) ? $counter_id : -1;
		
		$org_id = $this->currentorg->org_id;

		//Default value of this config will be false
		$config_key = 'CONF_DISALLOW_DUPLICATE_NON_LOYALTY_BILLS';

		$check_duplicate_bill = ConfigManager::getKeyValueForOrg($config_key, $this->org_id);

		if($check_duplicate_bill)
		{
			if($this->isNotInterestedBillDuplicate($bill_number))
				return ERR_LOYALTY_DUPLICATE_BILL_NUMBER;
		}
		
		$billing_time = Util::getMysqlDateTime($billing_time);

		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$safe_not_interested_reason = Util::mysqlEscapeString($not_interested_reason);
		$sql = "INSERT INTO loyalty_not_interested_bills (org_id, bill_number, bill_amount, reason, billing_time, entered_by) "
		. " VALUES ($org_id, '$safe_bill_number', '$bill_amount', '$safe_not_interested_reason', '$billing_time', '$store_id')";

		$not_interested_bill_id = $this->db->insert($sql);
		
		if ($not_interested_bill_id) {
			
			//Add the line items
			foreach($line_items as $nib_li){				
				$this->addNotInterestedBillLineItem($not_interested_bill_id, $nib_li);				
			}			
			
			return ERR_LOYALTY_SUCCESS;
		}

		return ERR_LOYALTY_UNKNOWN;


	}

	function addLoyaltyTrackerInfo($num_bills, $sales, $footfall_count, $date, $store_id = false,
		$captured_regular_bills = false, $captured_not_interested_bills = false,
		$captured_enter_later_bills = false, $captured_pending_enter_later_bills = false
	){

		$org_id = $this->currentorg->org_id;
		$store_id = ($store_id == false) ? $this->currentuser->user_id : $store_id;

		$date = Util::getMysqlDate($date);
		
		
		if(is_numeric($num_bills) && $num_bills >= 0)
		{
			$num_bills = intval($num_bills);
			$insert_num_bill_sql = ", num_bills";
			$values_num_bill_sql = ", $num_bills";
			$update_num_bill_sql = ", num_bills = VALUES(num_bills)"; 
		}
		if(is_numeric($sales) && $sales >= 0)
		{
			$sales = floatval($sales);
			$insert_sales_sql = ", sales";
			$values_sales_sql = ", $sales";
			$update_sales_sql = ", sales = VALUES(sales)";
		}
		if( is_numeric($footfall_count) && $footfall_count >= 0)
		{
			$footfall_count = intval($footfall_count);
			$insert_footfall_count_sql = ", footfall_count";
			$values_footfall_count_sql = ", $footfall_count";
			$update_footfall_count_sql = ", footfall_count = VALUES(footfall_count)";
		}
		if(is_numeric($captured_regular_bills) && $captured_regular_bills >= 0)
		{
			$captured_regular_bills = intval($captured_regular_bills);
			$insert_captured_regular_bill_sql = ", captured_regular_bills";
			$values_captured_regular_bill_sql = ", $captured_regular_bills";
			$update_captured_regular_bill_sql = ", captured_regular_bills = VALUES(captured_regular_bills)";
		}
		if(is_numeric($captured_not_interested_bills ) && $captured_not_interested_bills >= 0)
		{
			$captured_not_interested_bills = intval($captured_not_interested_bills);
			$insert_captured_not_interested_bills_sql = ", captured_not_interested_bills";
			$values_captured_not_interested_bills_sql = ", $captured_not_interested_bills";
			$update_captured_not_interested_bills_sql = ", captured_not_interested_bills = VALUES(captured_not_interested_bills)";
		}
		
		if(is_numeric($captured_enter_later_bills) && $captured_enter_later_bills >= 0)
		{
			$captured_enter_later_bills = intval($captured_enter_later_bills);
			$insert_captured_enter_later_bills_sql = ", captured_enter_later_bills";
			$values_captured_enter_later_bills_sql = ", $captured_enter_later_bills";
			$update_captured_enter_later_bills_sql = ", captured_enter_later_bills = VALUES(captured_enter_later_bills)";
		}
		
		if(is_numeric($captured_pending_enter_later_bills) && $captured_pending_enter_later_bills >= 0)
		{
			$captured_pending_enter_later_bills = intval($captured_pending_enter_later_bills);
			$insert_captured_pending_enter_later_bills_sql = ", captured_pending_enter_later_bills";
			$values_captured_pending_enter_later_bills_sql = ", $captured_pending_enter_later_bills";
			$update_captured_pending_enter_later_bills_sql = ", captured_pending_enter_later_bills = VALUES(captured_pending_enter_later_bills)";
		}

		$insert_sql = "INSERT INTO `loyalty_tracker` (org_id, store_id, tracker_date 
								$insert_num_bill_sql
								$insert_sales_sql
								$insert_footfall_count_sql
								$insert_captured_regular_bill_sql
								$insert_captured_not_interested_bills_sql
								$insert_captured_enter_later_bills_sql
								$insert_captured_pending_enter_later_bills_sql) 
							VALUES($org_id, $store_id, '$date'
								$values_num_bill_sql
								$values_sales_sql
								$values_footfall_count_sql
								$values_captured_regular_bill_sql
								$values_captured_not_interested_bills_sql
								$values_captured_enter_later_bills_sql 
								$values_captured_pending_enter_later_bills_sql
							)
							ON DUPLICATE KEY UPDATE store_id = $store_id
								$update_num_bill_sql
								$update_sales_sql
								$update_footfall_count_sql
								$update_captured_regular_bill_sql
								$update_captured_not_interested_bills_sql
								$update_captured_enter_later_bills_sql
								$update_captured_pending_enter_later_bills_sql
							";

		if ($this->db->update($insert_sql)) {
			return ERR_LOYALTY_SUCCESS;
		} 

		return ERR_LOYALTY_UNKNOWN;	

	}


	function getSalesAboveMaxLimit($fromdate,$to, $type = 'table'){

		$org_id = $this->currentorg->org_id;
		$allowed_sum = $this->currentorg->getConfigurationValue(CONF_LOYALTY_MAX_BILL_AMOUNT,'100000');

		$sql = "SELECT u.username,bill_number,TRUNCATE((bill_amount),2) AS bill_amts,DATE(ll.date) AS on_date" .
				" FROM loyalty_log ll JOIN `users` u ON ll.entered_by = `u`.`id`" .
				" WHERE TRUNCATE((bill_amount),2) > '$allowed_sum' AND DATE(`date`) BETWEEN DATE('$fromdate') AND DATE('$to') AND `ll`.`org_id` = '$org_id'";

		if ($type == 'table'){
			$total_sums = $this->db->query_table($sql);
		}

		else {
			$total_sums = $this->db->query($sql);
		}

		return array($allowed_sum,$total_sums);

	}

	function setLastStatementSentTime($user_id){
		$org_id = $this->currentorg->org_id;
		$sql = "UPDATE loyalty SET last_statement_sent = NOW() WHERE user_id = $user_id AND publisher_id = $org_id";
		return $this->db->update($sql);
	}

	function getCustomersForSkus($skulist,$startdate,$enddate, $outtype = 'query', $attrs = array()){

		$org_id = $this->currentorg->org_id;
		$sql = "
			SELECT TRIM(CONCAT(u.firstname, ' ', u.lastname)) AS customer_name, 
			u.id, u.mobile AS customer_mobile, oe.code AS billed_at_store, ll.date, 
			ll.bill_number, ll.bill_amount, ll.points, lbl.item_code, lbl.amount AS item_amount       
			FROM loyalty_bill_lineitems lbl 
			JOIN loyalty_log ll ON lbl.loyalty_log_id = ll.id 
			JOIN users u ON lbl.user_id = u.id AND u.org_id = lbl.org_id 
			JOIN masters.org_entities oe ON oe.id =  lbl.store_id AND oe.org_id = lbl.org_id
			WHERE lbl.org_id = $org_id AND DATE(ll.date) BETWEEN DATE('$startdate') AND DATE('$enddate')  
			AND lbl.item_code IN (".Util::joinForSql($skulist).")";
//			LEFT OUTER JOIN extd_user_profile eup ON eup.user_id = lbl.user_id AND eup.org_id = lbl.org_id  

		if($outtype == 'query_table')
		return $this->db->query_table($sql, $attrs['name']);

		return $this->db->query($sql);
	}
	/*
	 * @Deprecated
	 */
	function getNumberOfUsersbyAgegroups($startAge,$endAge){

		return false;
	}


	/**
	 * Get number of registered users in the current year for the given month
	 * @param $month
	 * @return unknown_type
	 */
	function getNumberOfRegistrationsForMonth($month){
		$org_id = $this->currentorg->org_id;

		$users = $this->db->query_scalar("select  COUNT(*)  from loyalty where MONTH(joined) = '$month' AND (YEAR(NOW())=YEAR(joined))  AND publisher_id = $org_id");

		return $users;
	}

	function getNumberOfUsersbyPoints($startPoints,$endPoints){

		$org_id = $this->currentorg->org_id;
		$users = $this->db->query_scalar("select  COUNT(*)  from loyalty where loyalty_points BETWEEN '$startPoints' and '$endPoints' AND publisher_id = '$org_id'");;

		return $users;

	}

	// TODO: deprecate the function 
    function getLoyaltyCustomerByMobile($mobile) {

        $org_id = $this->currentorg->org_id;

        $sql = "
            SELECT u.id, u.username, u.firstname, u.lastname, u.mobile, u.email,
                    loyalty_points, external_id, l.id as loyalty_id, null as sex,
                    null as birthday, null as anniversary, null as spouse_birthday, null as address,
                    l.slab_number, l.slab_name, l.lifetime_points,
                    l.lifetime_purchases, e.age_group, l.joined as joined_date,
                    l.last_updated
            FROM `loyalty` l
            JOIN `users` u ON l.user_id = u.id  AND u.org_id = l.publisher_id
            WHERE
                l.publisher_id = $org_id AND
                u.mobile = '$mobile'
                AND u.org_id = $org_id 
        ";
        //    JOIN `extd_user_profile` e ON e.org_id = l.publisher_id AND u.id = e.user_id
        
        $res = $this->db->query_firstrow($sql);

        return $res;
    }

	/**
	 * this function does a simple query for the loyalty cusotmers and returns the query result
	 */
	function getLoyaltyCustomersByOrg($add_window_check = false)
	{
		$org_id = $this->currentorg->org_id;
		
		$window_filter = "";
		if($add_window_check){
			$window_days = $this->currentorg->getConfigurationValue(CONF_LOYALTY_SYNC_WINDOW_DAYS_CUSTOMERS, 15);
			$window_filter = " AND ( DATEDIFF(NOW(), l.`last_updated`) <= $window_days ) ";
		}
		
		$sql = "SELECT 
						u.id, username, u.firstname, u.lastname, u.mobile, u.email, 
						loyalty_points, external_id, l.id as loyalty_id, null as sex, 
						null as birthday, null as anniversary, null as spouse_birthday, null as address, 
						l.slab_number, l.slab_name, l.lifetime_points, l.lifetime_purchases, 
						null as age_group, l.joined as joined_date, l.last_updated,
            	CASE WHEN ndnc.status != 'NDNC' THEN 0 ELSE 1 END AS ndnc_enabled
				FROM `loyalty` l
                JOIN `users` u ON l.user_id = u.id AND u.org_id = l.publisher_id 
                LEFT OUTER JOIN `users_ndnc_status` ndnc ON u.org_id = ndnc.org_id AND u.id = ndnc.user_id  
                WHERE l.publisher_id = $org_id $window_filter
					AND (u.mobile IS NOT NULL OR l.external_id IS NOT NULL)
        ";
		//                JOIN `extd_user_profile` e ON e.org_id = l.publisher_id AND u.id = e.user_id
		
                $db = new Dbase('users', TRUE);
		return $db->query($sql);

	}
	/**
	 * get the result set for customers in batches, keep on fetching till the result size 
	 */
	function getLoyaltyCustomersByOrgInFile($file_name, $add_window_check = false){

		$org_id = $this->currentorg->org_id;
		//list($temp_file_name, $http) = Util::getTemporaryFile('xml-serialize', 'xml');
		$this->logger->debug("using file for XML Serialization in loyalty: $file_name");
		$fh = fopen($file_name,'w');
		fwrite($fh,"<?xml version='1.0' encoding='ISO-8859-1'?>\n<root>\n");
		fwrite($fh,"<customers>\n");
		fclose($fh);
		$batch_size = 30000;
		$result_size = 30000;
		$max_customer_id = 0;
		//keep on querying while you get full batch size of rows
		//for every batch append it to the raw xml file

		while($result_size == $batch_size)
		{
			list($result_size, $max_customer_id) = $this->getLimitedLoyaltyCustomersByOrg($file_name, $batch_size, $max_customer_id, $add_window_check);
			$this->logger->debug("Processed $result_size customers Upto Customer Id $max_customer_id");
		}
		$fh = fopen($file_name,'a');
		fwrite($fh,"</customers>\n");
		fwrite($fh,"\n</root>");
		fclose($fh);
	}
	
	/**
	 * reads a batch of data from the database and appends it to a file
	 * @param $fh file handle to which this batch is to be written
	 * @param $size size of the batch to be read
	 */
	private function getLimitedLoyaltyCustomersByOrg($file_name, $size, $max_cust_id, $add_window_check = false){

		$org_id = $this->currentorg->org_id;
		
		$window_filter = "";
		if($add_window_check){
			$window_days = $this->currentorg->getConfigurationValue(CONF_LOYALTY_SYNC_WINDOW_DAYS_CUSTOMERS, 15);
			$delta_base = strtotime(date("Y-m-d") . " -$window_days days");
			$window_filter = " AND l.`last_updated` >= '".Util::getMysqlDate($delta_base) ."' ";
		}
		
		$sql = "
		SELECT  l.user_id as `id`, username, u.firstname, u.lastname, u.mobile, 
				u.email, loyalty_points, external_id, l.id as loyalty_id, null as sex, 
				null as birthday, null as anniversary, null as spouse_birthday, null as address, l.slab_number, 
				l.slab_name, l.lifetime_points, l.lifetime_purchases, null as age_group, 
				l.joined AS joined_date, l.last_updated,
        CASE WHEN ndnc.status != 'NDNC' THEN 0 ELSE 1 END AS ndnc_enabled 
		FROM `loyalty` l
		JOIN `users` u ON u.id = l.user_id AND u.org_id = l.publisher_id 
    LEFT OUTER JOIN `users_ndnc_status` ndnc ON u.id = ndnc.user_id AND u.org_id = ndnc.org_id
		WHERE l.publisher_id = $org_id AND l.user_id > $max_cust_id
		  AND (u.mobile IS NOT NULL OR l.external_id IS NOT NULL)
		  $window_filter
		ORDER BY l.user_id
		LIMIT $size
		";
//		JOIN `extd_user_profile` e ON e.org_id = l.publisher_id AND l.user_id = e.user_id
                
                //opening a readonly connection for returning customers 
                //for an org        
                $db = new Dbase( 'users', TRUE ); 
		$customer_data = $db->query($sql, false);
		$count = count($customer_data);
		$xml = new Xml();

		$converted = 0;
		$file_write_batch_size = 1000;
		$serialized = "";
		$xml = new Xml();
		$xml->setupSerializer(false, 'item');
		$fh = fopen($file_name,'a');
		foreach($customer_data as &$customer){
			
			$converted++;
			
			$serialized .= $xml->serializeXml($customer)."\n";
			
			if($converted == $count || $converted % $file_write_batch_size == 0){
				fwrite($fh,"$serialized");
				$serialized = "";
			}
			
			if($converted == $count)
				$max_cust_id = $customer['id'];
			
			$customer = false;
		}
		
		fclose($fh);
		
		return array($count, $max_cust_id);
	}
	/**
	 * Get the last 1000 bills for the organisation, ordered by loyalty log descending
	 * @return unknown_type
	 */
	function getBillsDetailsByOrg(){

		$org_id = $this->currentorg->org_id;

		$sql = "SELECT  ll.`loyalty_id`, bill_number, ll.`points`, ll.`date`, `notes`, `bill_amount`, `entered_by` "
		. " FROM loyalty_log ll "
		. "JOIN loyalty l ON ll.loyalty_id = l.id "
		. " WHERE l.publisher_id = $org_id "
		. " ORDER BY ll.id DESC LIMIT 1000";

		return $this->db->query($sql);
	}

	/**
	 * Get the last 1000 redemption for the organisation, ordered by Redemption ID descending
	 * @return unknown_type
	 * @deprecated 
	 */
//  	function getRedemptionsByorg(){
// 		// loyalty redemptions tables is no more in use
// 		return array();

//  	}

	/**
	 * @param $user_id
	 * @param $type query / table
	 * @return unknown_type
	 */
	function getissuedvouchers($user_id, $type = 'query', $testing = false){

		$dbname = Util::dbName('campaigns', $testing);

		$sql = "
			SELECT 
				v.voucher_id, v.voucher_code, v.created_date AS issued_on, 
				vs.description AS series, oe.code as store,
				CASE WHEN `vr`.`used_date` IS NULL THEN 'N-A' ELSE `vr`.`used_date` END AS `redeemed_on`,
				CASE WHEN `vr`.`used_at_store` IS NULL THEN 'N-A' ELSE `vrs`.`username` END AS `redeemed_by`,
                                DATE_ADD(v.created_date, INTERVAL vs.valid_days_from_create DAY) AS valid_till  
			FROM $dbname.voucher v 
			JOIN $dbname.voucher_series vs ON v.voucher_series_id = vs.id
			LEFT OUTER JOIN $dbname.`voucher_redemptions` AS `vr` ON (
				`vr`.`org_id` = `v`.`org_id` AND `v`.`voucher_id` = `vr`.`voucher_id`
			)
			LEFT OUTER JOIN stores vrs ON vrs.store_id = vr.used_at_store
			JOIN masters.org_entities oe ON oe.id =  v.created AND oe.org_id = v.org_id
			WHERE v.issued_to = $user_id AND v.org_id = ".$this->currentorg->org_id;

		if($type == 'table')
		return $this->db->query_table($sql,'vouchers');
			
		return $this->db->query($sql);
	}
	
	

	function markOutlierBillAsDone($outlier_bill_id,$outlier_status = 'MARK_DONE'){

		$sql = " UPDATE `loyalty_log_outliers` SET `outlier_status` = '$outlier_status' WHERE `id` = '$outlier_bill_id' ";
		
		return $this->db->update($sql);
	}
	
	/*
	 * Hack of string replace is written to make the similar xml as passed by the client to server.
	 * 
	 *  Xml seriealize doesn't provide the same xml on applying it on xml type object as it gives on parsing it.
	 * 
	 */
	function insertOutlierBill($user_id, $loyalty_id, $billing_points, $bill_amount, $notes, $bill_number, $billing_time, $entered_by){

		global $add_bill_details;
		$org_id = $this->currentorg->org_id;
		
		$bills = array($add_bill_details);
		$xml = new Xml();
		$xml->setupSerializer(false, 'item');
		
		foreach($bills as $bills)
			$serialized .= $xml->serializeXml($bills)."\n";
		
		$serialized = ltrim($serialized,"<item>\n");
		$serialized = rtrim($serialized,"\n");
		$serialized = rtrim($serialized,"</item>");
		
		$serialized = str_replace('<line_item>','',$serialized);
		$serialized = str_replace('</line_item>','',$serialized);
		$serialized = str_replace('<item>','<line_item>',$serialized);
		$serialized = str_replace('</item>','</line_item>',$serialized);
		$serialized = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $serialized);
		$serialized .= "<ignore_max_bill_amount>1</ignore_max_bill_amount>";
		$serialized = "<root>\n<bill>\n".$serialized;
		$serialized .= "\n</bill>\n</root>";
		
		$safe_notes = Util::mysqlEscapeString($notes);
		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$sql = "
		
			INSERT INTO `loyalty_log_outliers` 
				(`loyalty_id`, `org_id`, `user_id`, `bill_number`, `points`, `date`, `notes`, `bill_amount`, `entered_by`, `outlier_status`,`xml`) 
			VALUES ('$loyalty_id', '$org_id', '$user_id', '$safe_bill_number', '$billing_points', '$billing_time', '$safe_notes', '$bill_amount', '$entered_by', 'OUTLIER','$serialized')
		";
		
		$this->db->update($sql);
	}
	
	function getOutlierXmlByOutlierId($loyalty_log_outlier_id){
		
		$sql = " SELECT `xml` " .
				" FROM loyalty_log_outliers l " .
				" WHERE l.id = '$loyalty_log_outlier_id' ";
			
		return $this->db->query_firstrow($sql);
		
	}
	
	function getOutlierBillDetails($start_date, $end_date,$outlier_type = 'OUTLIER'){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = " SELECT l.id AS `internal_bill_id`,TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, 
				u.id as user_id, u.mobile AS customer_mobile, ly.joined AS customer_joined_on, l.points, l.bill_number, 
				l.bill_amount, l.date, l.notes, oe.code AS entered_by " .
				" FROM loyalty_log_outliers l " .
				" JOIN users u ON l.user_id = u.id AND l.org_id = u.org_id " .
				" JOIN masters.org_entities oe ON oe.id =  l.entered_by AND oe.org_id = l.org_id ".
				" JOIN loyalty ly ON ly.publisher_id = l.org_id AND ly.id = l.loyalty_id  ".
				" WHERE l.org_id = $org_id AND l.date BETWEEN DATE('$start_date') AND DATE('$end_date') AND `outlier_status` = '$outlier_type' ";
			
		return $this->db->query_table($sql);
	
	}
	/**
	 * Get Bill line items for bill
	 * @param $bill_id Loyalty log id
	 * @param $type query / table
	 * @return unknown_type
	 */
	function getBillLineitemDetails($bill_id, $type = 'query'){
		$this->logger->debug("fetching bill line item details");
        $org_id = $this->currentorg->org_id;
		$sql = "SELECT 
					id, loyalty_log_id, user_id, org_id, 
					serial, item_code, description, rate, qty, value, 
					discount_value, amount, store_id, inventory_item_id, 
					outlier_status, updated_on, mapped_on, 
					'REGULAR' AS type, NULL AS bill_number 
				FROM loyalty_bill_lineitems WHERE loyalty_log_id = '$bill_id' AND org_id = '$org_id'  
				ORDER BY serial ASC ";
//				AND outlier_status = 'NORMAL'
		
		$sql2 = "SELECT 
					IFNULL(rbl.id, rb.id) AS id,
					rb.parent_loyalty_log_id AS loyalty_log_id,
					rb.user_id,
					rb.org_id, 
					rbl.serial,
					rbl.item_code,
					NULL AS description,
					rbl.rate AS rate,
					rbl.qty AS qty,
					rbl.value AS value,
					rbl.discount_value AS discount_value,
					IFNULL(rbl.amount, rb.amount) AS amount,
					rb.store_id,
					NULL AS inventory_item_id,
					'NORMAL' AS outlier_status,
					returned_on AS updated_on,
					NULL AS mapped_on,
					rb.type,
					rb.bill_number 
					FROM returned_bills AS rb 
					LEFT JOIN returned_bills_lineitems AS rbl 
						ON rbl.return_bill_id = rb.id 
						AND rbl.org_id = rb.org_id
					WHERE rb.parent_loyalty_log_id = $bill_id 
					AND	rb.org_id = $org_id ORDER BY serial";
		
		$union_query = "( $sql ) UNION ( $sql2 )";
		
		$query = $union_query;
		global $gbl_api_version;
		if(strtolower($gbl_api_version) == 'v1')
		{
			$this->logger->debug("API version is v1, not fetching return bill information");
			$query = $sql;
		}
		
		if ($type == 'table')
			return($this->db->query_table($query));
			
		return $this->db->query($query);
	}

	/**
	 * Get Bill line items for bill
	 * @param $bill_id Loyalty log id
	 * @param $type query / table
	 * @return unknown_type
	 */
	function getBillAndLineitemDetails( $bill_id ){
		
		$this->logger->debug("fetching bill line item details");
        $org_id = $this->currentorg->org_id;

        $sql = "SELECT lbl.*, ll.bill_number 
				FROM loyalty_log AS ll
				JOIN loyalty_bill_lineitems AS lbl ON ( ll.org_id = lbl.org_id AND ll.id = lbl.loyalty_log_id  )
				WHERE ll.id = '$bill_id' AND ll.org_id = '$org_id' 
				ORDER BY serial ASC ";
//				AND lbl.outlier_status = 'NORMAL' 
			
		return $this->db->query($sql);
	}

    /**
     * Get not interested Bill line items for bill
     * @param $bill_id Loyalty log id
     * @param $type query / table
     * @return unknown_type
     */
    function getNotInterestedBillAndLineitemDetails( $bill_id ){

        $this->logger->debug("fetching bill line item details");
        $org_id = $this->currentorg->org_id;

        $sql = "SELECT lbl.*, ll.bill_number
				FROM loyalty_not_interested_bills AS ll
				JOIN loyalty_not_interested_bill_lineitems AS lbl ON ( ll.org_id = lbl.org_id AND ll.id = lbl.not_interested_bill_id  )
				WHERE ll.id = '$bill_id' AND ll.org_id = '$org_id'
				ORDER BY serial ASC ";
//				AND lbl.outlier_status = 'NORMAL'

        return $this->db->query($sql);
    }

    /**
     * Get returned Bill line items for bill
     * @param $bill_id Loyalty log id
     * @param $type query / table
     * @return unknown_type
     */
    function getReturnedBillAndLineitemDetails( $bill_id ){

        $this->logger->debug("fetching bill line item details");
        $org_id = $this->currentorg->org_id;

        $sql = "SELECT lbl.*, ll.bill_number
				FROM returned_bills AS ll
				JOIN returned_bills_lineitems AS lbl ON ( ll.org_id = lbl.org_id AND ll.id = lbl.return_bill_id  )
				WHERE ll.id = '$bill_id' AND ll.org_id = '$org_id'
				ORDER BY serial ASC ";
		//		AND lbl.outlier_status = 'NORMAL'
        
        return $this->db->query($sql);
    }

	/**
	 * Pass an array of item codes of the bill line items 
	 * and return back with the detail of each item
	 * @param $items_array
	 */
	
	function getAttributesForItems($items_array){
		global $logger;
		$db_product = new Dbase('product');
		$org_id = $this->currentorg->org_id;
		$items_list = implode(',', $items_array);
		
        $sql = "SELECT ims.item_sku, GROUP_CONCAT(iia.name SEPARATOR ';') AS attributes, 
                GROUP_CONCAT(iiav.value_name SEPARATOR ';') as vals FROM `inventory_masters` ims 
                INNER JOIN inventory_item_attributes iia ON ims.org_id = iia.org_id 
                INNER JOIN inventory_item_attribute_values iiav ON iiav.org_id = iia.org_id AND iia.id = iiav.attribute_id 
                INNER JOIN inventory_items ii ON ii.org_id = iiav.org_id AND ii.attribute_id = iia.id AND 
                ii.attribute_value_id = iiav.id AND ims.id = ii.item_id 
                WHERE ims.org_id = $org_id AND ims.item_sku IN ( $items_list ) GROUP BY ims.item_sku";

		//error_log("query: $sql");
        $logger->debug("Fetching the attributes of each inventory item:  query \n\n $sql");
                if(! empty($items_list))
                    $result = $db_product->query($sql, true);
                else
                    $result = array();
			
		$ret_array = array();

		$result = 
		( is_array( $result ) )? ( $result ):( array() );
		
		foreach($result as $row){
			$attributes_array = explode(';', $row['attributes']);
			$values_array = explode(';', $row['vals']);
			$attributes = array();
			for($i=0; $i < sizeof($attributes_array); ++$i){
				$attributes[$attributes_array[$i]] = $values_array[$i];
			}
			$ret_array[$row['item_sku']] = $attributes;	
		}
		
		$logger->debug("returning with array" . print_r($ret_array, true));
		return $ret_array;
	}
	
	
	
	function deleteBill($bill_id){
		$org_id = $this->currentorg->org_id;
		$loyalty_id = $this->db->query_scalar("SELECT loyalty_id FROM loyalty_log WHERE org_id = '$org_id' AND id = '$bill_id'");
		$bill_amount = $this->db->query_scalar("SELECT bill_amount FROM loyalty_log WHERE org_id = '$org_id' AND id = '$bill_id'");
		$this->db->update("UPDATE loyalty SET lifetime_purchases = lifetime_purchases - $bill_amount WHERE publisher_id = '$org_id' AND id = '$loyalty_id'");
		$this->db->update("DELETE FROM loyalty_log WHERE org_id = '$org_id' AND id = '$bill_id'");
	}

	/**
	 * Sends a sms to the customer. Template for the SMS used must be set in LOYALTY_TEMPLATE_SENDPOINTS. Otherwise will use LOYALTY_TEMPLATE_SENDPOINTS_DEFAULT
	 * @param $name
	 * @param $mobile
	 * @param $balance
	 * @param $testing
	 * @return unknown_type
	 */
	function sendbalance($name, $mobile, $balance,$testing = false){
		$org_id = $this->currentorg->org_id;
		$msg_template = Util::valueOrDefault($this->currentorg->get(LOYALTY_TEMPLATE_SENDPOINTS), LOYALTY_TEMPLATE_SENDPOINTS_DEFAULT);
		$options = array('name' => $name, 'mobile_number' => $mobile, 'total_points' => $balance);
		$this->logger->info("Sending message to $e->getMobile(): [Template: $msg_template] with options: ".print_r($options, true));

		if($testing = false){
			return Util::sendSms($mobile, Util::templateReplace($msg_template, $options), $org_id, MESSAGE_PRIORITY);
		}else{
			return Util::templateReplace($msg_template, $options);
		}

	}

	/**
	 * @param $bill_number Apply bill number filter. Pass 'false' to ignore
	 * @param $awarded_by Apply store filter. Pass 'false' to ignore
	 * @param $user_id Apply user id filter. Pass 'false' to ignore
	 * @param $on_date Check only on a particular date 
	 * @return Count of the bills.
	 */
	function getNumberOfAwardedPoints($bill_number = false, $awarded_by = false, $user_id = false, $on_date = false){

		$org_id = $this->currentorg->org_id;

		$bill_filter = " ";
		if($bill_number != false)
			$bill_filter = " AND ref_bill_number LIKE '$bill_number' ";
			
		$user_filter = " ";
		if($user_id != false)
			$user_filter = " AND user_id = $user_id ";

		$store_filter = " ";
		if($entered_by != false)
			$store_filter =  " AND `awarded_by` = '$entered_by' ";

		$on_date_filter = "";
		if($on_date){
			$on_date_next = Util::getNextDate($on_date);
			$on_date_filter = " AND (`awarded_time` BETWEEN DATE('$on_date') AND DATE('$on_date_next'))";	
		}
		
		return $this->db->query_scalar("
			SELECT COUNT(*) 
			FROM `awarded_points_log`
			WHERE org_id = '$org_id' 
				$store_filter 
				$bill_filter 
				$user_filter			 
				$on_date_filter
			");

	}
	
	/**
	 * @param $bill_number Apply bill number filter. Pass 'false' to ignore
	 * @param $entered_by Apply store filter. Pass 'false' to ignore
	 * @param $user_id Apply user id filter. Pass 'false' to ignore
	 * @param $use_days_filter Restrict count to CONF_LOYALTY_BILL_NUMBER_UNIQUE_IN_DAYS or 30 (default) days
	 * @param $on_date Check only on a particular date 
	 * @return Count of the bills.
	 */
	function getNumberOfBills(
				$bill_number = false, 
				$entered_by = false, 
				$user_id = false, 
				$use_days_filter = false, 
				$on_date = false,
				$bill_date = false
				){

		$org_id = $this->currentorg->org_id;

		$bill_filter = " ";
		if($bill_number != false)
		{
			$safe_bill_number = Util::mysqlEscapeString($bill_number);
			$bill_filter = " AND bill_number = '$safe_bill_number' ";
		}			
		$user_filter = " ";
		if($user_id != false)
			$user_filter = " AND user_id = $user_id ";

		$store_filter = " ";
		if($entered_by != false)
		{
			if(!is_array($entered_by))
				$entered_by=array($entered_by);
			$entered_by_str=implode("','",$entered_by);
			$store_filter =  " AND `entered_by` IN ('$entered_by_str') ";
		}

		$days_filter = " ";
		$not_interested_days_filter = " ";
		if($use_days_filter){
			$days = $this->currentorg->getConfigurationValue(CONF_LOYALTY_BILL_NUMBER_UNIQUE_IN_DAYS, 30);
			
			$new_date = DateUtil::subDays(DateUtil::getCurrentDateWithStartTime(), $days);
			$days_filter = " AND (`date` >= '$new_date') ";
			$not_interested_days_filter = " AND (`billing_time` >= '$new_date') ";
		}

		if( $bill_date ){

			$days = $this->currentorg->getConfigurationValue(CONF_LOYALTY_BILL_NUMBER_UNIQUE_IN_DAYS, 30);

			$new_date = DateUtil::subDays( DateUtil::getDateWithStartTime($bill_date) , $days);
			$days_filter = " AND (`date` >=  '$new_date') ";
			$not_interested_days_filter = " AND (`billing_time` >= '$new_date') ";
		}
		
		$on_date_filter = "";
		$not_interested_on_date_filter = "";
		if($on_date){
			$on_date_next = Util::getNextDate($on_date);
			$start_date = DateUtil::getDateWithStartTime($on_date);
			$end_date = DateUtil::getDateWithEndTime($on_date_next);
			$on_date_filter = " AND (`date` BETWEEN '$start_date' AND '$end_date')";
			$not_interested_on_date_filter = " AND (`billing_time` BETWEEN '$start_date' AND '$end_date')";
		}
		
		if($user_id != false)
		{
			//if user_id is passed it wont search duplication in not interested
			$not_interested_count = 0;
		}
		else
		{
			$not_interested_sql = "SELECT COUNT(*) FROM loyalty_not_interested_bills 
					WHERE org_id = $org_id
					    AND outlier_status not in ('RETRO','DELETED')
						$store_filter 
						$bill_filter 
						$not_interested_days_filter 
						$not_interested_on_date_filter";
			$not_interested_count = $this->db->query_scalar($not_interested_sql);
		}
		$regular_bill_sql = "SELECT COUNT(*) FROM loyalty_log 
					WHERE org_id = $org_id 
					AND outlier_status != 'DELETED'
						$store_filter 
						$bill_filter 
						$user_filter 
						$days_filter 
						$on_date_filter";
		$regular_bill_count = $this->db->query_scalar($regular_bill_sql);
		
		return $not_interested_count + $regular_bill_count;

	}

	/**
	 * @param $bill_number Apply bill number filter. Pass 'false' to ignore
	 * @param $entered_by Apply store filter. Pass 'false' to ignore
	 * @param $limit Number of rows to get. Pass 'false' to ignore
	 * @param $type table or res(default)
	 * @return Count of the bills.
	 */
	function getBillDetails($bill_number = false, $entered_by = false, $user_id = false, $limit = false, $outtype = 'query'){

		$org_id = $this->currentorg->org_id;

		$bill_filter = " ";
		if($bill_number != false)
		{
			$safe_bill_number = Util::mysqlEscapeString($bill_number);
			$bill_filter = " AND bill_number = '$safe_bill_number' ";
		}
		$store_filter = " ";
		if($entered_by != false)
		$store_filter =  " AND `entered_by` = '$entered_by' ";

		$user_filter = " ";
		if($user_id != false)
		$user_filter = " AND user_id = $user_id ";
			
		$limit_filter = " ";
		if($limit != false && $limit > 0)
		$limit_filter = " LIMIT 0, $limit ";
			
		$sql = "SELECT * FROM loyalty_log WHERE org_id = '$org_id' $store_filter $bill_filter $user_filter  ORDER BY id DESC $limit_filter";

		if($outtype == 'query_table')
		return $this->db->query_table($sql);

		if($outtype == 'query' && $limit == 1)
		return $this->db->query_firstrow($sql);

		return $this->db->query($sql);
	}

	function getBill($bill_id){
		
		$org_id = $this->currentorg->org_id;
		// p_change: added "oe.id as till_id"
		$sql = "SELECT ll.*, oe.code as store, oe.id as till_id, tds.delivery_status 
					FROM loyalty_log as ll 
					LEFT OUTER JOIN transaction_delivery_status AS tds 
						ON tds.transaction_id = ll.id 
						AND tds.transaction_type = 'REGULAR' 
					JOIN masters.org_entities oe ON oe.id =  ll.entered_by AND oe.org_id = ll.org_id
					WHERE ll.org_id = $org_id AND ll.id = $bill_id ";
		
		return $this->db->query_firstrow($sql);
	}
	
	function setBillNumber($bill_id, $bill_number){
		
		$org_id = $this->currentorg->org_id;
		
		$bill_old = $this->db->query_scalar("SELECT bill_number FROM loyalty_log WHERE `id` = '$bill_id' AND org_id = '$org_id'");
		$ret1 = $this->db->update("
			UPDATE loyalty_log 
			SET `bill_number` = '$bill_number'
				WHERE `id` = '$bill_id' AND org_id = '$org_id'
		");
		$store_id = $this->db->query_scalar("SELECT entered_by FROM loyalty_log WHERE `id` = '$bill_id' AND org_id = '$org_id'");

		$user_id = $this->db->query_scalar("SELECT user_id FROM loyalty_log WHERE `id` = '$bill_id' AND org_id = '$org_id'");
		$this->db->update("
			UPDATE raymond_report 
			SET `bill_no` = '$bill_number'
				WHERE `store_id` = '$store_id' AND user_id = '$user_id' and bill_no = '$bill_old'
		");

		return $ret1;
	}
	
	/**
	 * @param $loyalty_log_id Loyalty log id which has to be updated
	 * @param $bill_number Bill number of the bill
	 * @param $points
	 * @param $bill_amount
	 * @param $notes
	 * @param $datetime
	 * @param $entered_by
	 * @return unknown_type
	 */
	function updateBillDetails($loyalty_log_id, $bill_number, $points, $bill_amount = '', $notes = '', $datetime = '', $entered_by = ''){

		
		$bill_amount_update = $bill_amount != '' ? " bill_amount='$bill_amount', " : "";
		$notes_update = $notes != '' ? "notes=CONCAT('$notes', '\n', `notes`), " : "";
		$date_update = $datetime != '' ? "`date` = '$datetime', " : "";
		$entered_by_update = $entered_by != '' ? "entered_by = '$entered_by', " : "";
		
                if(Util::isPointsEngineActive())
                {
                   $this->logger->debug("Points engine is active...setting points to 0 for loyalty_log_id $loyalty_log_id, bill number: $bill_number");        
                   $points = 0;          
                }

		//points is being updated last someone might forget comma above
		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$ret1 = $this->db->update("
			UPDATE loyalty_log 
			SET 
				$bill_amount_update 
				$notes_update
				$date_update
				$entered_by_update
				points='$points'				
				WHERE `bill_number` = '$safe_bill_number' AND id = '$loyalty_log_id'
		");

		return $ret1;
	}

	/**
	 * 
	 * Adds the breakup of the payment details in the bill_payment_details table
	 * 
	 * @param $payment_details Array containing the payments breakup
	 * @param $loyalty_log_id Id of the bill
	 * 
	 * @author pigol
	 */
	
	function insertBillPaymentDetails($payment_details, $loyalty_log_id)
	{
		global $currentorg;
		
		if(sizeof($payment_details) > 0)
		{
			$this->logger->debug("Inserting bill payment details: " . print_r($payment_details, true));
			$payment_types = Util::getPaymentTypes();
			$this->logger->debug("payment types: " . print_r($payment_types, true) );
			$sql = "INSERT INTO bill_payment_details(org_id, loyalty_log_id, type, submitted_type, amount, notes) VALUES";
			foreach($payment_details as $p){
				$type = $p['type'];
				$value = $p['value'];
				$safe_type = Util::mysqlEscapeString($type);
				$safe_value = Util::mysqlEscapeString($value);

				$type_id = -1;
				if(in_array($type, array_keys($payment_types)))
				{
					$type_id = $payment_types[$type];	
				}
				
				$value_string .= "($currentorg->org_id, $loyalty_log_id, $type_id, '$safe_type', $safe_value, ''),";
			}
			
			
			$sql = $sql . " " . rtrim($value_string, ',');
			$db = new Dbase('users');
			$res = $db->insert($sql);
			$affected_rows = $db->getAffectedRows();
			if($affected_rows != sizeof($payment_details))
			{
				$to = 'errorsniffer@dealhunt.in';
				$message = "Error in updating bill payment types";
				$body = "Error in adding bill payment details, apache thread: " . $_SERVER['UNIQUE_ID'];
				Util::sendEmail($to, $message, $body);
			}
			
			return $affected_rows;
		}else{
			$this->logger->debug("No payment details passed.. skipping");
			return 0;
		}
	}
	
	
	/**
	 * Updates the inventory information for the line item
	 * 
	 * @param $line_item_params : Parameters of the line item
	 * @param $inventory_info : Inventory information for the line items
	 * 
	 * @author pigol
	 */
	
	function addInventoryInformation($line_item_params, $inventory_info,$isWSCall=false, $should_load_attributes = true)
	{
		$this->logger->debug("Adding inventory information for the line item");
		global $currentorg;	
		
		$line_item = array();
		$line_item['price'] = $line_item_params['rate'];
		$line_item['item_code'] = $line_item_params['item_code'];
		$line_item['inventory_info'] = $inventory_info;
		return $this->inventory->addItemToInventory($line_item,$isWSCall, $should_load_attributes);
	}
	
	
	/**
	 * Insert bill details. Updates on duplicate entry
	 * @param $loyalty_id
	 * @param $points
	 * @param $datetime
	 * @param $notes
	 * @param $bill_amount
	 * @param $bill_number
	 * @param $entered_by
	 * @param $org_id
	 * @param $user_id
	 * @return loyalty log id
	 */
        function insertBillDetails(
                        $loyalty_id, $points, $datetime, $notes, $bill_amount, 
                        $bill_number, $entered_by, $org_id, $user_id, 
                        $bill_gross_amount, $bill_discount
                        ){

		global $counter_id;
		$counter_id = isset($counter_id) ? $counter_id : -1;
	
                if(Util::isPointsEngineActive())
                {
                    $this->logger->debug("Points engine active for $org_id, setting points to zero");
                    $points = 0;        
                }
	
        $safe_bill_number = Util::mysqlEscapeString($bill_number);
        $safe_notes = Util::mysqlEscapeString($notes);
                
		$loyalty_log_id = $this->db->insert(
			"INSERT INTO `loyalty_log` (`loyalty_id`, `points`, `date`, `notes`, `bill_amount`, "
			. " `bill_number`, `entered_by`, `org_id`, `user_id`, `counter_id`, `bill_gross_amount`, `bill_discount` ) "
			. " VALUES ('$loyalty_id', '$points', '$datetime', '$safe_notes', '$bill_amount', "
			. " '$safe_bill_number', '$entered_by', '$org_id', '$user_id', '$counter_id', '$bill_gross_amount', '$bill_discount' ) "
			. " ON DUPLICATE KEY UPDATE points = '$points', `bill_amount` = '$bill_amount', `notes` = CONCAT(`notes`, '\n', '$safe_notes'), "
			. " `date` = '$datetime', `entered_by` = '$entered_by', `counter_id`  = '$counter_id'");

			return $loyalty_log_id;
	}

	/**
	 * Update loyalty details for a user
	 * @param $loyalty_id
	 * @param $points_to_add Points will be added to existing value
	 * @param $lifetime_points_to_add  Points will be added to existing value
	 * @param $lifetime_purchases_to_add  Amount will be added to existing value
	 * @param $last_updated (Datetime)
	 * @param $last_updated_by Store id which is making the update
	 * @return unknown_type
	 */
	function updateLoyaltyDetails($loyalty_id, $points_to_add, $lifetime_points_to_add, $lifetime_purchases_to_add, $last_updated, $entered_by){

		# Update points in the main loyalty table

                if(Util::isPointsEngineActive())
                {
                   $this->logger->debug("Points Engine is active.. not updating the loyalty table");     
                   return true;     
                }        

		$ret = $this->db->update("
			UPDATE `loyalty` 
			SET 
				`loyalty_points` = `loyalty_points` + '$points_to_add',
				`lifetime_points` = `lifetime_points` + '$lifetime_points_to_add', 
				`lifetime_purchases` = `lifetime_purchases` + $lifetime_purchases_to_add, 
				`last_updated` = '$last_updated', 
				`last_updated_by` = '$entered_by' 
			WHERE publisher_id = $this->org_id AND `id` = '$loyalty_id'");

		return $ret;

	}

	/**
	 * Update loyalty details for a user
	 * @param $loyalty_id
	 * @param $points_to_add Points will be added to existing value
	 * @param $lifetime_points_to_add  Points will be added to existing value
	 * @param $lifetime_purchases_to_add  Amount will be added to existing value
	 * @param $last_updated (Datetime)
	 * @param $last_updated_by Store id which is making the update
	 * @return unknown_type
	 */
	function updateLoyaltyDetailsByUserId( $user_id, $points_to_add, $lifetime_points_to_add, $lifetime_purchases_to_add, $last_updated, $entered_by){

		# Update points in the main loyalty table
		$ret = $this->db->update("
			UPDATE `loyalty` 
			SET 
				`loyalty_points` = `loyalty_points` + '$points_to_add',
				`lifetime_points` = `lifetime_points` + '$lifetime_points_to_add', 
				`lifetime_purchases` = `lifetime_purchases` + $lifetime_purchases_to_add, 
				`last_updated` = '$last_updated', 
				`last_updated_by` = '$entered_by' 
			WHERE  `publisher_id` = '$this->org_id' AND `user_id` = '$user_id'");

		return $ret;

	}
	
	/**
	 * Set loyalty details for a user
	 * @param $loyalty_id
	 * @param $points_to_set
	 * @param $lifetime_points_to_set
	 * @param $lifetime_purchases_to_set
	 * @param $slab_name_to_set
	 * @param $slab_num_to_set
	 * @return unknown_type
	 */
	function setLoyaltyDetails($loyalty_id, $points_to_set, $lifetime_points_to_set, 
		$lifetime_purchases_to_set, $slab_name_to_set, $slab_num_to_set,
		$external_id)
	{

		$store_id = $this->currentuser->user_id;
		
		# Update points in the main loyalty table
		$ret = $this->db->update("
			UPDATE `loyalty` 
			SET 
				`loyalty_points` = '$points_to_set',
				`lifetime_points` = '$lifetime_points_to_set', 
				`lifetime_purchases` = '$lifetime_purchases_to_set', 
				`last_updated` = NOW(),
				`slab_name` = '$slab_name_to_set',
				`slab_number` = '$slab_num_to_set', 
				`last_updated_by` = '$store_id' 
			WHERE `id` = '$loyalty_id'
		");

		if($ret)
			$this->updateExternalId($loyalty_id, $external_id);
		
		return $ret;

	}

	/**
	 * Removes any points awarded on the bill
	 * @param unknown_type $user_id
	 * @param unknown_type $bill_number
	 * @param unknown_type $notes
	 * @return string|string
	 */
	function removeAwardedPointsOnBill($user_id, $bill_number, $notes){
		
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;

                //////
                if(Util::isPointsEngineActive())
                {
                    $this->logger->debug("Points engine is active, bypassing points update on $bill_number for $user_id, notes: $notes");    
                    return true;    
                }                
                /////

        $safe_bill_number = Util::mysqlEscapeString($bill_number);
		$sql = "
			UPDATE `loyalty` l, `awarded_points_log` a
			SET l.loyalty_points = l.loyalty_points - a.awarded_points,
				l.lifetime_points = l.lifetime_points - a.awarded_points,
				l.last_updated_by = '$store_id',
				l.last_updated = NOW()
			WHERE 
				l.publisher_id = '$org_id' AND a.org_id = l.publisher_id
				AND l.user_id = '$user_id' AND a.user_id = l.user_id
				AND a.ref_bill_number = '$safe_bill_number'
		";
		$ret = $this->db->update($sql);
		
		if($ret){
			
			//update the awarded points
			$sql = "
				UPDATE `awarded_points_log` a
				SET a.awarded_points = 0,
					a.notes = CONCAT('$notes', ' . ', a.notes),
					a.awarded_by = '$store_id',
					a.awarded_time = NOW()
				WHERE 
					a.org_id = '$org_id'
					AND a.user_id = '$user_id'
					AND a.ref_bill_number = '$safe_bill_number'
			";
			$this->db->update($sql);
			
		}
		

		//Ignore errors here as there might or might not be any awarded poitns on the bill
		return true;
	}
	
	/**
	 * Removes any points awarded on the bill
	 * @param unknown_type $user_id
	 * @param unknown_type $bill_number
	 * @param unknown_type $notes
	 * @return string|string
	 */
	function removeSlabUpgradePointsOnBill($user_id, $bill_number, $notes){
		
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		
                /////
                if(Util::isPointsEngineActive())                        
                {
                    $this->logger->debug("Points engine active, bypassing points update on $bill_number, for $user_id, notes: $notes");    
                    return true;    
                }
                ////

        $safe_bill_number = Util::mysqlEscapeString($bill_number);
		$sql = "
			UPDATE `loyalty` l, `slab_upgrade_log` s
			SET l.loyalty_points = l.loyalty_points - s.upgrade_bonus_points,
				l.lifetime_points = l.lifetime_points - s.upgrade_bonus_points,
				l.last_updated_by = '$store_id',
				l.last_updated = NOW()
			WHERE 
				l.publisher_id = '$org_id' AND s.org_id = l.publisher_id
				AND l.user_id = '$user_id' AND s.user_id = l.user_id
				AND s.loyalty_id = l.id
				AND s.ref_bill_number = '$safe_bill_number'
		";
		$ret = $this->db->update($sql);
		
		if($ret){
			
			//update the awarded points
			$sql = "
				UPDATE `slab_upgrade_log` s
				SET s.upgrade_bonus_points = 0,
					s.notes = CONCAT('$notes', ' . ', s.notes),
					s.upgraded_by = '$store_id',
					s.upgrade_time = NOW()
				WHERE 
					s.org_id = '$org_id'
					AND s.user_id = '$user_id'
					AND s.ref_bill_number = '$safe_bill_number'
			";
			$this->db->update($sql);
			
		}
		

		//Ignore errors here as there might or might not be any slab upgrades on the bill
		return true;
	}
	
	/**
	 * Function that adds the line items to the params
	 * @param $loyalty_log_id ID of the loyalty Log
	 * @param $user UserProfile object of the customer
	 * @param $lineitem_params array containing the line_item details
	 * @return integer Line Item ID
	 */
	function addBillingLineItem($loyalty_log_id, UserProfile $user, array $lineitem_params) {
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;

		$sql = " INSERT INTO `loyalty_bill_lineitems` (`loyalty_log_id`, `serial`, `user_id` , `org_id` , `item_code` , `description` , "
		. " `rate` , `qty` , `value` , `discount_value` , `amount` , `store_id` ) "
		. " VALUES ('$loyalty_log_id', '$lineitem_params[serial]', '$user->user_id', '$org_id', '$lineitem_params[item_code]', '$lineitem_params[description]', "
		. " '$lineitem_params[rate]', '$lineitem_params[qty]', '$lineitem_params[value]', '$lineitem_params[discount_value]', '$lineitem_params[amount]', '$store_id' ) "
		. " ON DUPLICATE KEY UPDATE item_code = '$lineitem_params[item_code]', description = '$lineitem_params[description]', `rate` = '$lineitem_params[rate]', "
		. " `qty` = '$lineitem_params[qty]', `value` = '$lineitem_params[value]', `discount_value` = '$lineitem_params[discount_value]', "
		. " `amount` = '$lineitem_params[amount]', `store_id` = '$store_id' ";

		$lineitem_id = $this->db->insert($sql);
		return $lineitem_id;
	}

        /**
           Add multiple billing line items in parallel     
           loyalty_log_id : bill id
           user: userprofile object                

        **/
        public function addMultipleBillLineItems($loyalty_log_id, UserProfile $user, $line_items)
        {
            
             if(count($line_items) < 1)              
             {
                $this->logger->debug("No line items in the bill");
                return array();
             }   

             $this->logger->debug("Adding multiple line items for bill $loyalty_log_id, user: $user->user_id\n");   
             $org_id = $this->currentorg->org_id;
             $store_id = $this->currentuser->user_id;   
        
             $sql = "INSERT INTO loyalty_bill_lineitems(loyalty_log_id, serial, user_id, org_id, item_code, description, 
                                                        rate, qty, value, discount_value, amount, store_id) VALUES";
             $suffix_sql = "";   
             foreach($line_items as $li)   
             {    
               $serial = $li['serial']; 
               $item_code = $li['item_code'];
               $description = $li['description'];
               $rate = $li['rate'];
               $qty = $li['qty'];
               $value = $li['value'];
               $discount_value = $li['discount_value'];
		if(empty($discount_value))
			$discount_value = 0;
               $amount = $li['amount'];
                 
               $suffix_sql .= "(
                                $loyalty_log_id, $serial, $user->user_id, $org_id, '$item_code', '$description', $rate, $qty, 
                                $value, $discount_value, $amount, $store_id
                               ),";                  
             }   
                
             $sql = $sql . rtrim($suffix_sql, ',');        
             $db = new Dbase('users');
             $res = $db->insert($sql);   

             $this->logger->debug("No of items added: $res");

             $sql = "SELECT * FROM loyalty_bill_lineitems WHERE org_id = $org_id AND store_id = $store_id AND loyalty_log_id = $loyalty_log_id";   
             return $db->query($sql);   
        }


	/**
	 * Function that adds the line items to the not interested bill
	 * @param $not_interested_bill_id Bill id of the not interested bill
	 * @param $lineitem_params array containing the line_item details. required : (serial, item_code, description, rate, qty, value, discount_value, amount, store_id)
	 * @return integer Line Item ID
	 */
	function addNotInterestedBillLineItem($not_interested_bill_id, array $lineitem_params) {
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;

		$serial = $lineitem_params['serial'];
		$item_code = Util::mysqlEscapeString($lineitem_params['item_code']);
		$description = Util::mysqlEscapeString($lineitem_params['description']);
		$rate  = $lineitem_params['rate'];
		$qty = $lineitem_params['qty'];
		$value = $lineitem_params['value'];
		$discount_value = $lineitem_params['discount_value'];
		$amount = $lineitem_params['amount'];
		
		
		$sql = " INSERT INTO `loyalty_not_interested_bill_lineitems` (`org_id`, `not_interested_bill_id`, `serial`, `item_code`, `description`, "
		. " `rate`, `qty`, `value`, `discount_value`, `amount`, `store_id`) "
		. " VALUES ('$org_id', '$not_interested_bill_id', '$serial', '$item_code', '$description', "
		. " '$lineitem_params[rate]', '$lineitem_params[qty]', '$lineitem_params[value]', '$lineitem_params[discount_value]', '$lineitem_params[amount]', '$store_id' ) ";

		$lineitem_id = $this->db->insert($sql);

		return $lineitem_id;
	}
	
	/**
	 * Inventory Check For Bill Line items
	 * 
	 * 
	 */
	function getBillLineItemsRefferenceStats($type = 'query',$count = true,$start_date = '',$end_date = '',$stores = array() ){
		
		$org_id = $this->currentorg->org_id;
		$end_date = Util::getNextDate($end_date);
		if($count){
			$select_filter = "SELECT COUNT(`lbl`.`id`) AS `total_line_items`, COUNT(`inventory_item_id`) AS referenced, 
				SUM(CASE WHEN inventory_item_id IS NULL THEN 1 ELSE 0 END) non_referenced ";
		}else{
			$select_filter = "SELECT `u`.`username` As `store_name`,`lbl`.`item_code` AS `non_referenced_line_items_code`,`lbl`.`description`,DATE(`ll`.`date`) AS `bill_date` ";
			$join_filter .= " JOIN `users` AS `u` ON ( `u`.`id` = `lbl`.`store_id` AND `u`.`tag` = 'org' )";
			$filter = " AND inventory_item_id IS NULL ";
		}
		if($start_date != '' && $end_date != ''){
			$join_filter .= " JOIN `loyalty_log` AS `ll` ON ( `ll`.`id` = `lbl`.`loyalty_log_id` AND ll.org_id = lbl.org_id )";
			$filter .= " AND `ll`.`date` BETWEEN '$start_date' AND '$end_date' ";
		}
		if(count($stores) > 0){
			$stores = Util::joinForSql($stores);
			$filter .= " AND `lbl`.`store_id` IN ($stores) ";
		} 
		//query to count no of items referenced and not referenced in bill line items
		$sql = " $select_filter" .
				"FROM loyalty_bill_lineitems AS `lbl` " .
				$join_filter .
				"WHERE `lbl`.org_id = $org_id $filter";
		
		$db = new DBase('users', true);
		if($type == 'query')
			return $db->query($sql);
		elseif($type == 'query_table')
			return $db->query_table($sql);
	}
	
	/**
	 * Add promotion bill detail
	 * @param UserProfile $loyalty_log_id
	 * @param array $user
	 * @param $promotional_details
	 * @return unknown_type
	 */
	function addPromotionalDetails($loyalty_log_id, UserProfile $user, array $promotional_details){
		$org_id = $this->currentorg->org_id;

		foreach($promotional_details as $promo_detail){
			$series_id = $promo_detail['series_id'];
			$detail = $promo_detail['promo_detail'];
			
			$safe_series_id = Util::mysqlEscapeString($series_id);
			$safe_detail = Util::mysqlEscapeString($detail);
			
			if($series_id != '' && $series_id != -1){
				$sql = "INSERT INTO loyalty_promotional_campaign_bills (org_id, user_id, loyalty_log_id, series_id, details) VALUES ($org_id, $user->user_id, $loyalty_log_id, $safe_series_id, '$safe_detail')";
				$this->db->insert($sql);
			}
		}
	}

	/**
	 * Change the mobile number for this user
	 * @param $user_id User ID whose number has to be changed
	 * @param $old_mobile
	 * @param $new_mobile
	 * @return unknown_type
	 */

	function changeMobileNumber(UserProfile $user, $old_mobile, $new_mobile) {
		$me = $this->currentuser->user_id;
		$org_id = $this->currentorg->org_id;
		$user_id = $user->user_id;
		
		if (!Util::checkMobileNumber($old_mobile) && (strlen($user->mobile) > 0) ) 
			return false;
		
		if ($new_mobile != NULL && !Util::checkMobileNumber($new_mobile)) 
			return false;
		
		if ($old_mobile == $new_mobile) 
			return true;
		
		if ($user->mobile != $old_mobile) 
			return false;



		# Handle the case when the mobile number is already set as the new number
		if ($new_mobile != false) {
			$existing_new_mobile_user = UserProfile::getByMobile($new_mobile);
			while ($existing_new_mobile_user != false) {

				$this->changeMobileNumber($existing_new_mobile_user, $new_mobile, NULL);
				$existing_new_mobile_user = UserProfile::getByMobile($new_mobile);

			}
		}

		# Case when the old mobile record doesn't exist in the DB
		$insert_sql = " INSERT INTO mobile_number_change_log (org_id, user_id, old_mobile, new_mobile, reported_by, reporting_time, validated_by, validation_time) "
		. " VALUES ($org_id, '$user_id', '$old_mobile', '$new_mobile', '$me', NOW(), '$me', NOW())
			ON DUPLICATE KEY UPDATE 
				`new_mobile` = '$new_mobile', 
				`reported_by` = '$me', 
				`reporting_time` = NOW(),
				`validated_by` = '$me', 
				validation_time = NOW()
		";

		$ret1 = $this->db->update($insert_sql);
		$new_mobile_str = count($new_mobile) > 0 ? "'$new_mobile'" : " (NULL) ";

		if($ret1){

			# Update the number in the main users DB
			$old_mobile_filter = strlen($old_mobile) > 0 ? " AND mobile LIKE '$old_mobile' " : " AND (mobile LIKE '' OR mobile IS NULL) ";
			$main_db_sql = "UPDATE users SET mobile = $new_mobile_str WHERE `id` = '$user_id' AND org_id = $org_id $old_mobile_filter";
			$ret3 = $this->db->update($main_db_sql);

			//change in extd_user_profile; not required
			//$update_sql = "UPDATE extd_user_profile SET mobile = $new_mobile_str WHERE user_id = '$user_id' AND org_id = $org_id $old_mobile_filter";
			//$ret4 = $this->db->update($update_sql);

			//change in mlm referrals
			if(strlen($old_mobile) > 0){
				$update_sql = "UPDATE mlm_referrals SET referee_mobile = $new_mobile_str WHERE referee_mobile LIKE '$old_mobile' AND org_id = $org_id";
				$ret5 = $this->db->update($update_sql);
			}
			
			//change in campaign referrals
			if(strlen($old_mobile) > 0){
				$update_sql = "UPDATE campaigns.campaign_referrals SET referee_mobile = $new_mobile_str WHERE referee_mobile LIKE '$old_mobile' AND org_id = $org_id";
				$ret6 = $this->db->update($update_sql);
			}
			
			//change in extd_user_profile
			$update_sql = "UPDATE loyalty SET last_updated = NOW() WHERE user_id = '$user_id' AND publisher_id = $org_id";
			$ret7 = $this->db->update($update_sql);
		}

		//ret2 not included as its not being used
		//ret5 and ret6 not used.. as they mightbe equal to 0 in case no referrals were made to that number
		return $ret1 && $ret3 ;//&& $ret4
	}

	function addMobileChangeRequest( UserProfile $user, $old_mobile, $new_mobile, $type = 'MOBILE', $status_msg = 'MOBILE NUMBER' ){
		
		$org_id = $this->currentorg->org_id;
		$user_id = $user->user_id;
		$store_id = $this->currentuser->user_id;
		$sql = "
			INSERT INTO `mobile_number_change_request_log` (`org_id`, `user_id`, `old_mobile`, `new_mobile`, `requested_by`, `request_time`, `type` )
				VALUES ('$org_id', '$user_id', '$old_mobile', '$new_mobile', '$store_id', NOW(), '$type' )
			ON DUPLICATE KEY 
				UPDATE `new_mobile` = '$new_mobile', `requested_by` = '$store_id', `request_time` = NOW()
		";

		if(!$this->db->insert($sql))
			return false;
		
		$subject = "$status_msg change request : From $old_mobile to $new_mobile. Store : ".$this->currentuser->username;
		
		$eup = new ExtendedUserProfile($user, $this->currentorg);
		$client_info = "Customer Name : ".$eup->getName()."<br>";
		$client_info .= "Email Id : ".$eup->getEmail()."<br>";
		$loyalty_details = $this->getLoyaltyDetailsForUserID($user_id);
		$client_info .= "External Id : ".$loyalty_details['external_id']."<br>";
		$client_info .= "Loyalty Id : ".$loyalty_details['id']."<br> User Id : $user_id <br>";
		
		$msg = "$status_msg  Change Request from !nTouch Client :<br> From $old_mobile to $new_mobile "
			." <br> Store : ".$this->currentuser->username
			." <br> Store Contact : ".$this->currentuser->mobile
			." <br> Organization : ".$this->currentorg->name;
		$msg .= "<br>    $client_info";

		$recepient = $this->getConfigurationValue(MAIL_IDS_FOR_MOBILE_CHANGE_REQUEST, array('anant.choubey@dealhunt.in','ankit.jain@dealhunt.in'));
		
		if($recepient){
			
			$recepient = explode(',', $recepient);
		}else
			return true;
		
		Util::sendEmail($recepient, $subject, $msg, $org_id, null, 0, 
				-1 , array(), 0, true, $user->user_id,  $user->user_id, 'GENERAL');
		
		return true;
	}
	
	function getMobileChangeRequestLog($changed = 0, $outtype = 'query'){
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			SELECT TRIM(CONCAT(u.firstname, ' ', u.lastname)) as customer, u.mobile as current_mobile, 
					l.external_id, c.user_id, c.id as request_id, s.username as requesting_store, 
					c.old_mobile as from_mobile, c.new_mobile as to_mobile, c.request_time, 
					c.last_changed_on as status_updated_last
					
			FROM `mobile_number_change_request_log` c
			JOIN `loyalty` l ON l.publisher_id = c.org_id AND l.user_id = c.user_id
			JOIN `users` s ON s.id = c.requested_by
			WHERE c.org_id = $org_id AND c.changed = '$changed' AND c.type = 'MOBILE'
		";
//			JOIN `extd_user_profile` eup ON eup.org_id = c.org_id AND eup.user_id = c.user_id
		
		if($outtype == 'query_table')
			return $this->db->query_table($sql);
		
		return $this->db->query($sql);
	}

	function getExternalChangeRequestLog($changed = 0, $outtype = 'query',$table = 'table'){
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			SELECT TRIM(CONCAT(u.firstname, ' ', u.lastname)) as customer, l.external_id as current_card, 
					c.user_id, c.id as request_id, s.username as requesting_store, 
					c.old_mobile as from_card, c.new_mobile as to_card, c.request_time, 
					c.last_changed_on as status_updated_last
					
			FROM `mobile_number_change_request_log` c
			JOIN `loyalty` l ON l.publisher_id = c.org_id AND l.user_id = c.user_id
			JOIN `users` s ON s.id = c.requested_by
			WHERE c.org_id = $org_id AND c.changed = '$changed' AND c.type = 'EXTERNAL_ID'
		";
//			JOIN `extd_user_profile` eup ON eup.org_id = c.org_id AND eup.user_id = c.user_id
		
		if($outtype == 'query_table')
			return $this->db->query_table($sql,$table);
		
		return $this->db->query($sql);
	}
	
	function changeMobileChangeRequestLogStatus($user_id, $request_id, $changed = 1){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			UPDATE `mobile_number_change_request_log`
				SET `changed` = '$changed', `last_changed_on` = NOW()
			WHERE `org_id` = '$org_id' AND `id` = '$request_id' AND `user_id` = '$user_id';
		";
		
		return $this->db->update($sql);
	}

	
	function addOfflinePointsRedeemRequest(UserProfile $user, $loyalty_id, $points_to_be_redeemed, $bill_number, $error_msg, $loyalty_details_before, $done = 0, $extra = ''){
		
		$org_id = $this->currentorg->org_id;
		$user_id = $user->user_id;
		$cust_mobile = $user->mobile;
		$store_id = $this->currentuser->user_id;
		$store_name = $this->currentuser->username;
		$store_contact = $this->currentuser->mobile;

		if($done != 0)
			$done = 1;

		//log the request
		$sql = "
			INSERT INTO `redeem_points_offline_requests` (`org_id`, `user_id`, `bill_number`, `points_to_be_redeemed`, `requested_by`, `requested_on`, `done`, `error_msg`)
				VALUES ('$org_id', '$user_id', '$bill_number', '$points_to_be_redeemed', '$store_id', NOW(), '$done', '$error_msg')
		";
		if(!$this->db->insert($sql))
			return false;
		
			
		$subject = "Offline Points Redemption Request : Customer Mobile : $cust_mobile,Bill : '$bill_number', Points to be redeemed: '$points_to_be_redeemed'. Store : $store_name ($store_contact) ";
		
		$eup = new ExtendedUserProfile($user, $this->currentorg);
		$loyalty_details = $this->getLoyaltyDetailsForUserID($user_id);
		$client_info = "
		<br>Customer Name : ".$eup->getName()."
		<br>Email Id : ".$eup->getEmail()."		
		<br>External Id : ".$loyalty_details['external_id']."
		<br>Loyalty Id : ".$loyalty_details['id']."
		<br>User Id : $user_id
		<br>Points to be Redeemed : $points_to_be_redeemed 
		<br>Loyalty Points  (Before Redemption) : ".$loyalty_details_before['loyalty_points']."
		<br>Lifetime Points (Before Redemption) : ".$loyalty_details_before['lifetime_points']."
		<br>Lifetime Purchases (Before Redemption) : ".$loyalty_details_before['lifetime_purchases']."
		";
		
		$client_info .= "
		
			<br>EXTRA : $extra
		";
		
		if($error_msg == $this->loyalty_mod->getResponseErrorMessage(ERR_LOYALTY_SUCCESS)){
			$client_info .= "
			<br>Loyalty Points  (After Redemption) : ".$loyalty_details['loyalty_points']."
			<br>Lifetime Points (After Redemption) : ".$loyalty_details['lifetime_points']."
			<br>Lifetime Purchases (After Redemption) : ".$loyalty_details['lifetime_purchases'];
		}
		
		$msg = $subject."<br>";		
		$msg .= " 
			<br>Error Status : $error_msg 
			<br>Customer Info :  $client_info
		";

		$to = array(
			'anant.choubey@dealhunt.in',
			'vinod@dealhunt.in',
			'ulhas@dealhunt.in',
			'ankit.jain@dealhunt.in'
		);
		
		Util::sendEmail($to, $subject, $msg, $org_id, '', 0, 
				-1 , array(), 0, true, $user_id,  $user_id, 'POINTS');
		
		return true;
	}
	
	function getRedeemPointsOfflineRequestsLog($done = 0, $outtype = 'query'){
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			SELECT TRIM(CONCAT(u.firstname, ' ', u.lastname)) as customer, u.mobile , l.external_id, r.user_id, r.id as request_id, r.points_to_be_redeemed, r.bill_number, r.requested_on, r.requested_by, rs.username as requesting_store, rs.mobile as store_contact, r.error_msg, r.modified_on as status_last_updated, ms.username as status_last_updated_by, IFNULL(rr.msg, '-') as clms_status
			FROM `redeem_points_offline_requests` r
			JOIN `loyalty` l ON l.publisher_id = r.org_id AND l.user_id = r.user_id
			JOIN `users` rs ON rs.id = r.requested_by			
			LEFT OUTER JOIN `users` ms ON ms.id = r.modified_by
			LEFT OUTER JOIN `raymond_redeem` rr ON rr.store_id = r.requested_by AND rr.user_id = r.user_id AND rr.bill_no = r.bill_number
			WHERE r.org_id = $org_id AND r.done = '$done'
			ORDER BY r.id DESC
		";
//			JOIN `extd_user_profile` eup ON eup.org_id = r.org_id AND eup.user_id = r.user_id
		
		if($outtype == 'query_table')
			return $this->db->query_table($sql);
		
		return $this->db->query($sql);
	}
	
	function changeRedeemPointsOfflineRequestLogStatus($user_id, $request_id, $done = 1){
		
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		
		$sql = "
			UPDATE `redeem_points_offline_requests`
				SET `done` = '$done', `modified_on` = NOW(), `modified_by` = '$store_id'
			WHERE `org_id` = '$org_id' AND `id` = '$request_id' AND `user_id` = '$user_id';
		";
		
		return $this->db->update($sql);
	}
	
	
	
	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getLoyaltyLogTableDump($startdate, $enddate, $zone_selected = false, $stores = array(), 
					$outtype = 'query', $attrs = array(),$cat_id = false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";
		$am = new AdministrationModule();
		if($zone_selected != false){

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`l`.`entered_by`', $zone_filter));
		}

		$store_filter = "";
		if($stores){

			$store_filter = " AND l.entered_by IN (".Util::joinForSql($stores).")";
		}
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$primary_key_filter = '';
		if($outtype == 'query_table')
			$primary_key_filter = '{{where}}';
		
		$sql = "SELECT l.id,l.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.user_id, 
					u.mobile AS customer_mobile, ly.joined AS customer_joined_on, l.points, 
					l.bill_number, l.bill_amount, l.bill_discount, l.date, l.notes, 
					oe.code AS entered_by, oe2.code AS entered_by_counter " .
				" FROM loyalty_log l " .
				" JOIN users u ON l.user_id = u.id AND l.org_id = u.org_id " .
				" JOIN masters.org_entities oe ON oe.id =  l.entered_by AND oe.org_id = l.org_id ".
				" JOIN loyalty ly ON ly.publisher_id = l.org_id AND ly.id = l.loyalty_id  ".
				" LEFT JOIN masters.org_entities oe2 ON oe2.id =  l.counter_id AND oe2.org_id = l.org_id ".
		$zone_filter . $cat_filter .
				" WHERE l.org_id = $org_id AND l.date BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter $primary_key_filter";

		$limit_sql = $sql . ' LIMIT 100';
	
                /**
                   Instantiating a readonly connection to user management
                   since its a report     
                **/
                $db = new Dbase('users');         
		if($outtype == 'query_table')
			return array($sql,false,'l.id');

		return array($sql,$db->query($limit_sql),false);
	}
	
	
	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $min_amount
	 * @param $max_amount
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getLoyaltyLogTableDumpForOutlier($startdate, $enddate, $min_amount, $max_amount, $zone_selected = false, $stores = array(), $include_statuses = '', $outtype = 'query', $attrs = array()){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";
		$am = new AdministrationModule();
		if($zone_selected != false){

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`l`.`entered_by`', $zone_filter));
		}

		$store_filter = "";
		if(count($stores) > 0){

			$store_filter = " AND l.entered_by IN (".Util::joinForSql($stores).")";
			
		}
		
		$status_filter = "";
		if(count($include_statuses) > 0){
			$status_filter = " AND l.outlier_status IN (".Util::joinForSql($include_statuses).")";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$sql = "SELECT l.id as loyalty_log_id, l.outlier_status, l.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname))
				 AS customer, l.user_id, u.mobile AS customer_mobile, ly.joined AS customer_joined_on,
				 l.points, l.bill_number, l.bill_amount, l.date, l.notes, oe.code AS entered_by, oe.id as store_id " .
				" FROM loyalty_log l " .
				" JOIN users u ON l.user_id = u.id AND l.org_id = u.org_id " .
				" JOIN masters.org_entities oe ON oe.id =  l.entered_by AND oe.org_id = l.org_id ".
				" JOIN loyalty ly ON ly.publisher_id = l.org_id AND ly.id = l.loyalty_id  ".
				$zone_filter .
				" WHERE l.org_id = $org_id 
					AND l.date BETWEEN DATE('$startdate') AND DATE('$enddate') 
					$store_filter
					$status_filter
					AND (l.bill_amount <= '$min_amount' AND l.bill_amount >= '$max_amount') 
				";

		if($outtype == 'query_table')
		return $this->db->query_table($sql, $attrs['name']);
			
		return $this->db->query($sql);
	}
	
	
	function getBillsWithoutLineItemsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
					$attrs = array(),$cat_id = false,$store_selected = false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`ll`.`entered_by`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND ll.entered_by IN (".Util::joinForSql($store_selected).") ";
			
		}
		
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$primary_key_filter='';
		if($outtype == 'query_table')
			$primary_key_filter = '{{where}}';
			
		$sql = "SELECT ll.id,ll.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.id as user_id, 
					u.mobile AS customer_mobile, ly.joined AS customer_joined_on, ll.points, 
					ll.bill_number, ll.bill_amount, ll.bill_discount, ll.date, ll.notes, 
					oe.code AS entered_by, oe2.code AS entered_by_counter " .
				" FROM loyalty_log ll " .
				" JOIN users u ON ll.user_id = u.id AND ll.org_id = u.org_id " .
				" JOIN masters.org_entities oe ON oe.id =  ll.entered_by AND oe.org_id = ll.org_id ".
				" JOIN loyalty ly ON ly.publisher_id = ll.org_id AND ly.id = ll.loyalty_id  " .
				" LEFT OUTER JOIN loyalty_bill_lineitems lbl ON (lbl.loyalty_log_id = ll.id AND lbl.org_id = ll.org_id)".
				" LEFT JOIN masters.org_entities oe2 ON oe2.id =  ll.counter_id AND oe2.org_id = ll.org_id " .
				$zone_filter .
				$cat_filter .
				" WHERE ll.org_id = $org_id AND ll.date BETWEEN DATE('$startdate') AND DATE('$enddate') AND lbl.id IS NULL $store_filter $primary_key_filter ";

		$limit_sql = $sql . ' LIMIT 100';
		if($outtype == 'query_table')
		return array($sql,false,'ll.id');

		return array($sql,$this->db->query($limit_sql),false);
	}
	
	function getNotInterestedBillsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', $attrs = array(),$store_selected=false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`nbl`.`entered_by`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND nbl.`entered_by` IN (".Util::joinForSql($store_selected).") ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$primary_key_filter = '';
		if( $outtype == 'query_table' )
			$primary_key_filter='{{where}}';
			
		$sql = "SELECT nbl.id,nbl.bill_number, nbl.bill_amount, nbl.reason, nbl.billing_time, oe.code as username " .
				"FROM `loyalty_not_interested_bills` nbl " .
				"JOIN masters.org_entities oe ON oe.id =  nbl.entered_by AND oe.org_id = nbl.org_id ".
				$zone_filter .
				"WHERE nbl.org_id = $org_id AND nbl.billing_time BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter $primary_key_filter ";
		
		$limit_sql = $sql . ' LIMIT 100';
		if($outtype == 'query_table')
		return array($sql,false,'nbl.id');

		return array($sql,$this->db->query($limit_sql),false);
	}
	
	function getNotInterestedBillsWithLineItemsTableDump($startdate, $enddate, $zone_selected = false, 
				$outtype = 'query', $attrs = array(),$store_selected=false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`nbli`.`store_id`', $zone_filter));
		}
		
		$store_filter="";
		if($store_selected != false){
			$store_filter = " AND nbli.store_id IN (".Util::joinForSql($store_selected).") ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$primary_key_filter = '';
		if( $outtype == 'query_table' )
			$primary_key_filter='{{where}}';
			
		$sql = "SELECT nbli.id,nbl.bill_number, nbl.bill_amount, nbl.reason, nbl.billing_time, nbli.item_code, nbli.description, nbli.qty, nbli.amount, oe.code as username " .
				"FROM `loyalty_not_interested_bill_lineitems` nbli " .
				"JOIN `loyalty_not_interested_bills` nbl ON nbl.id = nbli.not_interested_bill_id " .
				"JOIN masters.org_entities oe ON oe.id =  nbli.store_id AND oe.org_id = nbli.org_id " .
				$zone_filter .
				"WHERE nbli.org_id = $org_id AND nbl.billing_time BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter $primary_key_filter ";
		
		$limit_sql = $sql . ' LIMIT 100';
		if($outtype == 'query_table')
		return array($sql,false,'nbli.id');

		return array($sql,$this->db->query($limit_sql),false);
	}
	

	function getLoyaltyTrackerTableDump($startdate,$enddate, $zone_selected = false,$store_selected = false, $outtype = 'query', $attrs = array()){

		$org_id = $this->currentorg->org_id;
		$am = new AdministrationModule();
		$zone_filter = "";

		$use_zones = true;
		if($zone_selected == false){
			$use_zones = false;
		}else{
			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);
		}
		$tempoptions = NULL;

		$store_filter = " ";
		if($store_selected != false){
			$store_filter = $am->getModifiedStoreFilter($store_selected, "`lt`.`store_id`");
		}
			
		$sql = "SELECT oe.code AS store, `lt`.`tracker_date`, `lt`.`num_bills`, `lt`.`sales`, `lt`.`footfall_count`
				FROM loyalty_tracker lt "
		." JOIN masters.org_entities oe ON oe.id =  lt.store_id AND oe.org_id = ld.org_id "
		. " ".(($use_zones == true) ? (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`lt`.`store_id`', $zone_filter)) : (" "))
		." WHERE `lt`.`org_id` = $org_id $store_filter "
		." AND `lt`.`tracker_date` BETWEEN DATE('$startdate') AND DATE('$enddate') "
		." ORDER BY `lt`.`tracker_date` DESC";

		if($outtype == 'query_table'){
			$lt_tab = $this->db->query_table($sql, $attrs['name']);
			$totalrow = $lt_tab->getColumnsSums(array('store', 'tracker_date'));
			$totalrow['store'] = 'TOTAL';
			$lt_tab->addRow(array());
			$lt_tab->addRow($totalrow);
		}

		else $lt_tab = $this->db->query($sql);

		return $lt_tab;

	}


	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 * @deprecated: not used
	 */
// 	function getLoyaltyRedemptionsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
// 					$attrs = array(),$cat_id = false,$store_selected=false){

// 		$org_id = $this->currentorg->org_id;

// 		$zone_filter = "";
// 		$am = new AdministrationModule();
// 		if($zone_selected != false){

// 			//create a filter out of the sub zones for the zone selected
// 			$zone_filter = $am->createZoneFilter($zone_selected);

// 			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`l`.`entered_by`', $zone_filter));
// 		}
		
// 		$store_filter="";
// 		if($store_selected != false){
// 			$store_filter = " AND l.entered_by IN (".Util::joinForSql($store_selected).") ";
// 		}
		
// 		if($cat_id){
// 			$database_msging = Util::dbName('msging',$this->testing);
// 			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
// 						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
// 		}
		
// 		$enddate = Util::getNextDate($enddate);
		
// 		$primary_key_filter='';
// 		if($outtype == 'query_table')
// 			$primary_key_filter='{{where}}';
// 		// A temporary fix remove it for org_id = 0
// 		$sql = "SELECT l.id,l.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.user_id, 
// 					u.mobile AS customer_mobile, l.points_redeemed, l.voucher_code, l.bill_number, 
// 					l.notes, l.date, oe.code AS entered_by, oe2.code AS entered_by_counter " .
// 				" FROM loyalty_redemptions l " .
// 				" JOIN extd_user_profile u ON l.user_id = u.user_id AND l.org_id = u.org_id " .
// 				" JOIN masters.org_entities oe ON oe.id =  l.entered_by AND oe.org_id = nbl.org_id ".
// 				" LEFT JOIN masters.org_entities oe2 ON oe2.id =  l.counter_id AND oe2.org_id = nbl.org_id ".
// 				$zone_filter .
// 				$cat_filter .
// 				" WHERE l.org_id = $org_id AND l.date BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter $primary_key_filter ";

// 		$limit_sql = $sql . ' LIMIT 100';
// 		if($outtype == 'query_table')
// 		return array($sql,false,'l.id');

// 		return array($sql,$this->db->query($limit_sql),false);
// 	}

	/**  @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getMlmRefferalsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', $attrs = array(),$cat_id = false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`m`.`referred_at_store`', $zone_filter));
		}
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `eupr`.`org_id` AND `s`.`subscriberId` = `eupr`.`id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$sql = "SELECT TRIM(CONCAT(eupr.firstname, ' ', eupr.lastname)) AS referrer, eupr.mobile AS referrer_mobile, eupr.id as user_id AS referrer_user_id, referee_mobile, referee_email, referral_date, num_reminders, IFNULL(joined_date, '-') as referee_joining_date, IFNULL(referee_id_joined, '-') as referee_id, u.username AS referred_at_storename " .
				" FROM mlm_referrals m " .
				" JOIN users eupr ON eupr.id = m.referrer_id AND eupr.org_id = m.org_id " .
				" JOIN stores u ON u.store_id = m.referred_at_store AND u.org_id = m.org_id ".
				$zone_filter  .
				$cat_filter .
				" WHERE m.org_id = $org_id AND m.referral_date BETWEEN DATE('$startdate') AND DATE('$enddate')";

		if($outtype == 'query_table')
		return array($sql,$this->db->query_table($sql, $attrs['name']));

		return array($sql,$this->db->query($sql));
	}


	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getMlmAwardedPointsTableDump($startdate, $enddate, $outtype = 'query', $attrs = array(),$cat_id = false){

		$org_id = $this->currentorg->org_id;

		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$sql = "SELECT m.*	, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.mobile AS customer_mobile " .
				" FROM mlm_awarded_points m " .
				" JOIN users u ON m.user_id = u.id AND m.org_id = u.org_id " .
				$cat_filter .
				" WHERE m.org_id = $org_id AND m.awarded_time BETWEEN DATE('$startdate') AND DATE('$enddate')";

		if($outtype == 'query_table')
		return array($sql,$this->db->query_table($sql, $attrs['name']));

		return array($sql,$this->db->query($sql));
	}

	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getCanceledBillsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
				$attrs = array(),$cat_id = false,$store_selected = false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`c`.`entered_by`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND `c`.`entered_by` IN (".Util::joinForSql( $store_selected ).") ";
		}
		
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);

		$sql = "SELECT TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, c.user_id, c.loyalty_id, 
					u.mobile AS customer_mobile, l.joined AS customer_joined_on, 
					l.loyalty_points as current_points, c.bill_number as canceled_bill_number, 
					c.entered_time as canceled_on, oe.code AS entered_by " .
				" FROM `cancelled_bills` c " .
				" JOIN users u ON c.user_id = u.id AND c.org_id = u.org_id " .
				" JOIN masters.org_entities oe ON oe.id =  c.entered_by AND oe.org_id = c.org_id".
				" JOIN loyalty l ON l.publisher_id = c.org_id AND l.id = c.loyalty_id  ".
				$zone_filter .
				$cat_filter .
				" WHERE c.org_id = $org_id AND c.entered_time BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter";

		if($outtype == 'query_table')
		return array($sql,$this->db->query_table($sql, $attrs['name']));

		return array($sql,$this->db->query($sql));
	}
	
	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getReturnedBillsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
					$attrs = array(),$cat_id = false,$store_selected = false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`r`.`store_id`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND `r`.`store_id` IN (".Util::joinForSql( $store_selected ).") ";
		}
		
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$sql = "SELECT TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, r.user_id, l.id as loyalty_id, 
					u.mobile AS customer_mobile, l.joined AS customer_joined_on, 
					l.loyalty_points as current_points, r.bill_number as returned_bill_number, 
					r.credit_note, r.amount as returned_amount, r.points as points_for_returned_amount, 
					r.returned_on, oe.code AS returned_at " .
				" FROM `returned_bills` r " .
				" JOIN users u ON r.user_id = u.id AND r.org_id = u.org_id " .
				" JOIN masters.org_entities oe ON oe.id =  r.store_id AND oe.org_id = r.org_id" .
				" JOIN loyalty l ON l.publisher_id = r.org_id AND l.user_id = r.user_id  ".
				$zone_filter .
				$cat_filter .
				" WHERE r.org_id = $org_id AND r.returned_on BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter";

		if($outtype == 'query_table')
		return array($sql,$this->db->query_table($sql, $attrs['name']));

		return array($sql,$this->db->query($sql));
	}
	
	function getUnsubscribedEmailTableDump( $outtype, $tablename ){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = "SELECT CONCAT( `u`.`firstname`, ' ', `u`.`lastname`) AS 'Name' , 
					u.`email`
				FROM `nsadmin`.`email_sending_rules` es
				JOIN `user_management`.`users` u
					ON u.`email` = es.`email` AND u.`org_id` = es.`org_id`
				WHERE es.`org_id` = '$org_id' 
					AND es.`sending_rule` NOT IN ( 'NONE' );
			";
		
		if($outtype == 'query_table')
			return array($sql,$this->db->query_table( $sql, $tablename ) );

		return array($sql,$this->db->query($sql,false));
		
	}
	
	/**
	 * fetch all unsubscribed mobile for org
	 * @param type, table name
	 */
	public function getUnsubscribedMobileTableDump( $outtype , $tablename ){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = "SELECT CONCAT( `u`.`firstname`, ' ', `u`.`lastname`) AS 'Name' , 
					u.`mobile`
				FROM `nsadmin`.`sms_sending_rules` ss
				JOIN `user_management`.`users` u
					ON u.`mobile` = ss.`mobile_number` AND u.`org_id` = ss.`org_id`
				WHERE ss.`org_id` = '$org_id'
					AND ss.`sending_rule` NOT IN ( 'NONE' );
			";
		
		if( $outtype == 'query_table' )
			return array( $sql , $this->db->query_table( $sql , $tablename ) );
		
		return array( $sql , $this->db->query( $sql ,false ) );
	}
		
	/**
	 * TODO : Check this code out. Some how have to make it de coupled
	 * It will return skipped users list for whome sms is not sent for some reason
	 * @param $outtype
	 * @param $tablename
	 */
	function getSkippedUsersDump( $startdate , $enddate , $outtype = 'query' , $attrs = array()  ){
		
		$org_id = $this->currentorg->org_id;
		
		$primary_key_filter='';
		if( $outtype == 'query_table' )
			$primary_key_filter='{{where}}';
			
		$sql = "
				SELECT i.id , cb.`name` AS 'Campaign Name' , gd.`group_label` AS 'Group Name' , 
					   CONCAT( u.`firstname` , ' ' , u.`lastname` ) AS 'Customer Name' , u.`mobile` , u.`email` , 
					   i.`reason` , i.`last_updated_on` AS 'Skipped Date' 
				FROM `msging`.`inbox_skipped` i
						JOIN `msging`.`outboxes` o ON o.`messageId` = i.`outbox_id` AND o.`publisherId` = $org_id
						JOIN `user_management`.`users` u ON u.`id` = i.`user_id` AND u.`org_id` = $org_id
        				JOIN `msging`.`group_details` gd ON gd.`group_id` = o.`categoryIds` AND gd.`org_id` = $org_id
        				JOIN `campaigns`.`campaigns_base` cb ON cb.`id` = gd.`campaign_id` AND cb.`org_id` = $org_id
				WHERE i.`org_id` = $org_id AND DATE(i.`last_updated_on`) BETWEEN '$startdate' AND '$enddate' $primary_key_filter
		";
		
		if( $outtype == 'query_table' )
			return array( $sql , false , 'i.id' );

		return array( $sql , $this->db->query($sql) , false );
	}
	
	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getMlmProcessedPointsTableDump($startdate, $enddate, $outtype = 'query', $attrs = array()){

		$org_id = $this->currentorg->org_id;

		$enddate = Util::getNextDate($enddate);
		
		$sql =  "SELECT * " .
				" FROM mlm_bills_processed " .
				" WHERE org_id = $org_id AND processed_time BETWEEN DATE('$startdate') AND DATE('$enddate')";

		if($outtype == 'query_table')
		return array($sql,$this->db->query_table($sql, $attrs['name']));

		return array($sql,$this->db->query($sql));
	}
	/**
	 * @param $oldmobile
	 */
	function getNewMobileNumberForOldMobile($oldmobile){

		$org_id = $this->currentorg->org_id;

		if (!Util::checkMobileNumber($oldmobile)) return "Invalid Mobile";

		$new = $this->db->query_scalar("SELECT new_mobile FROM mobile_number_change_log WHERE old_mobile LIKE '$oldmobile' AND org_id = $org_id LIMIT 1");

		if ($this->db->getAffectedRows() == 0) $new = "Not recorded";

		return $new;
	}
	function getCustomerDetailsByShopDate($startdate,$enddate){
		
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		$enddate = Util::getNextDate($enddate);
		$sql = " SELECT CONCAT(`eup`.`firstname`,`eup`.`lastname`) AS `name`,CONCAT(`mobile`,'		') AS `mobile_number`,`l`.`loyalty_points`,`l`.`lifetime_points`,`l`.`lifetime_purchases` ,count(*) AS `number_of_bills`" .
				" FROM `loyalty` AS `l` " .
				" JOIN `users` AS `eup` ON (`eup`.`id` = `l`.`user_id` AND `l`.`publisher_id` = `eup`.`org_id`)" .
				" JOIN `loyalty_log` AS `ll` ON ( `ll`.`org_id` = `l`.`publisher_id` AND `ll`.`loyalty_id` = `l`.`id` )" .
				" WHERE `ll`.`org_id` = '$org_id'  AND `ll`.`date` BETWEEN DATE('$startdate') AND DATE('$enddate') AND `ll`.`entered_by` = '$store_id'" .
				" GROUP BY `ll`.`user_id`";
				
		return $this->db->query_table($sql);
	}
	
	function getLoyaltyDetailsByMobile( $mobile, $org_id ){
		
		if(Util::checkMobileNumber($mobile)){
			$this->loyaltyModel->getFirstRowResult();
			return $this->loyaltyModel->getLoyaltyDetailsByMobile( $mobile, $org_id, '`l`.*' );
		}
		else
			return false;
		
	}

	function getLoyaltyDetailsByEmail( $email, $org_id ){
		
		if(Util::checkEmailAddress($email)){
			
			$this->loyaltyModel->getFirstRowResult();
			return $this->loyaltyModel->getLoyaltyDetailsByEmail($email, $org_id, '`l`.*' );
		}
		else
			return false;
	}
	
	
	function getLoyaltyDetailsByExternalId( $external_id, $org_id ){
		
		$this->loyaltyModel->getFirstRowResult();
		return $this->loyaltyModel->getLoyaltyDetailsByExternalId($external_id, $org_id, '`l`.*' );
		
	}
	
	/**
	 * gives user id by checking old_mobile number of user and current number of
	 * users
	 * @param string $mobile
	 * $$outtype
	 *
	 */
	function getUserIdByMobile($oldmobile, $outtype = 'query'){

		$org_id = $this->currentorg->org_id;

		# Search in "old" numbers whose new number hasn't been updated first
		$sql = " SELECT user_id AS id, '$oldmobile' as old_mobile  FROM mobile_number_change_log WHERE old_mobile LIKE '$oldmobile' AND org_id = $org_id AND (new_mobile IS NULL OR LENGTH(new_mobile) = 0)"
		. " UNION "
		. " SELECT id, '$oldmobile' AS old_mobile FROM users WHERE mobile LIKE '$oldmobile' AND org_id = $org_id";

		if($outtype == 'query_table')
		return $this->db->query_table($sql);

		return $this->db->query($sql);
	}
	/**
	 * @param integer $mobile
	 * gives no of user by mobile number string
	 */
	function getNumberOfUsersWithMobile($mobile){

		$org_id = $this->currentorg->org_id;

		return $this->db->query_scalar("SELECT COUNT(*) FROM users WHERE mobile LIKE '$mobile' AND org_id = $org_id");

	}

	/**
	 * Get a ExtendedUserProfile object for the user id
	 * @param $user_id
	 * @return ExtendedUserProfile object
	 */
	function getExtdUserProfileObjectByUserid($user_id){
		$eup = new ExtendedUserProfile(UserProfile::getById($user_id), $this->currentorg);
		return $eup;
	}

	function getNameAndEmailByMobile($mobile){

		$org_id = $this->currentorg->org_id;
		if(!Util::checkMobileNumber($mobile))
			return ;
			
		$sql = " SELECT 
					CONCAT(`firstname`,' ',`lastname`) AS `name`,
					`email`
					FROM `users` AS `eup`
					WHERE `org_id` = '$org_id' AND `mobile` = '$mobile'";
		
		return $this->db->query_firstrow($sql);
	}
	/**
	 * Get the mobile number of the user id
	 * @param $user_id
	 * @return unknown_type
	 */
	function getMobileForUserId($user_id){
		return $this->db->query_scalar("SELECT mobile FROM users WHERE org_id = ".$this->currentorg->org_id." AND id = '$user_id'");
	}


	function createFilterForm(Form $filterForm) {

		$org_id = $this->currentorg->org_id;

		$am = new AdministrationModule();
		$store_filter = $am->getStoresForOrg($org_id);

		$filterForm->addField('text', 'points_gt', 'Points between', '', '');
		$filterForm->addField('text', 'points_lt', '... and', '', '');
		$filterForm->addField('text', 'lifetime_points_gt', 'Lifetime Points between', '', '');
		$filterForm->addField('text', 'lifetime_points_lt', '... and', '', '');
		$filterForm->addField('datepicker', 'last_txn_after', 'Last Transacton Date after');
		$filterForm->addField('datepicker', 'last_txn_before', '... before');
		$filterForm->addField('datepicker', 'joined_after', 'Joined after');
		$filterForm->addField('datepicker', 'joined_before', '... before');
		$filterForm->addField('text', 'lifetimepurchase_gt', 'Lifetime Purchases between ', '', '');
		$filterForm->addField('text', 'lifetimepurchase_lt', '...  and..  ', '', '');
		$filterForm->addField('select', 'slab_name', 'In Slab', '', array('list_options' => array_merge(array('ALL'), $this->getSlabsForOrganization())));
		$filterForm->addField('text', 'birthday_interval', 'Birthday in the next (Days)', '');
		$filterForm->addField('text', 'anniversary_interval', 'Anniversary in the next (Days)', '');
		$stores = array_flip($this->currentorg->getOrganizationalUsers());
		$filterForm->addField('multiplebox', 'registered_by', 'Registered at Store', '', array('multiple' => true, 'list_options' => $store_filter));
		$filterForm->addField('multiplebox', 'last_transacted_by', 'Last Transacted at Store', '', array('multiple' => true, 'list_options' => $store_filter));
			

	}

	/**
	 * @param $params Params of the form created by createFilterForm
	 * @param $outtype If 'sql' is passed then the sql is returned (default),If query/query_table is passed then the query is executed and returned accordingly
	 * @param $attrs Any attributes to be passed based on the outtype
	 * @return unknown_type
	 */
	function evaluateFilterParamsToSql($params,$outtype = 'query',$directInsert = false,$cat_id = false) {

		$org_id = $this->currentorg->org_id;

		$filter = "  ";
		$filter .= $params['points_gt'] != '' ? ' AND `loyalty_points` >= '.$params['points_gt'] : '';
		$filter .= $params['points_lt'] != '' ? ' AND `loyalty_points` <= '.$params['points_lt'] : '';
		$filter .= $params['lifetime_points_gt'] != '' ? ' AND `lifetime_points` >= '.$params['lifetime_points_gt'] : '';
		$filter .= $params['lifetime_points_lt'] != '' ? ' AND `lifetime_points` <= '.$params['lifetime_points_lt'] : '';
		$filter .= $params['last_txn_after'] != '' ? " AND DATE(`last_updated`) >= '".Util::getMysqlDate(strtotime($params['last_txn_after']))."' " : "";
		$filter .= $params['last_txn_before'] != '' ? " AND DATE(`last_updated`) <= '".Util::getMysqlDate(strtotime($params['last_txn_before']))."' " : "";
		$filter .= $params['joined_after'] != '' ? " AND DATE(`joined`) >= '".Util::getMysqlDate(strtotime($params['joined_after']))."' " : "";
		$filter .= $params['joined_before'] != '' ? " AND DATE(`joined`) <= '".Util::getMysqlDate(strtotime($params['joined_before']))."' " : "";
		$filter .= $params['lifetimepurchase_gt'] != '' ? ' AND `lifetime_purchases` >= '.$params['lifetimepurchase_gt'] : '';
		$filter .= $params['lifetimepurchase_lt'] != '' ? ' AND `lifetime_purchases` <= '.$params['lifetimepurchase_lt'] : '';
		$filter .= $params['birthday_interval'] != '' ? " AND MOD( ( (DAYOFYEAR(`birthday`) - DAYOFYEAR(NOW())) + 365), 365) < ".$params['birthday_interval'] : "";
		$filter .= $params['anniversary_interval'] != '' ? " AND MOD( ( (DAYOFYEAR(`anniversary`) - DAYOFYEAR(NOW())) + 365), 365) < ".$params['anniversary_interval'] : "";
		$filter .= $params['slab_name'] != 'ALL' ? ' AND `slab_name` LIKE \''.$params['slab_name'].'\'' : '';

		$reg_by = $params['registered_by'];
		if (count($reg_by) > 0) {
			$reg_str = Util::joinForSql($reg_by);
			$filter .=  " AND `registered_by` IN ($reg_str)" ;
		}
		$last_tran_at = $params['last_transacted_by'];
		if (count($last_tran_at) > 0) {
			$tran_str = Util::joinForSql($last_tran_at);
			$filter .= " AND `last_updated_by` IN ($tran_str)" ;
		}

		//			//$filter .= Util::valueOrDefault(' AND ')
		if(!$directInsert){

			$select =  "SELECT e.id as user_id, TRIM(CONCAT(e.firstname,' ',e.lastname)) as fullname,e.mobile, e.email, null as address, null as birthday, " .
						" null as anniversary, null as spouse_birthday,e.mobile, " .
						" l.loyalty_points, l.slab_name, l.lifetime_purchases, l.lifetime_points," .
						" l.last_updated, l.joined, r.username as 'Registered BY', t.username as 'Last Transacted AT' ";

		}else{

			$select = "SELECT e.id as user_id,'$org_id' ,'$cat_id' ,TRIM(CONCAT(e.firstname,' ',e.lastname)) " ;
		}

		$sql = 	 $select .
				" FROM `loyalty` l " .
				" LEFT JOIN `users` e ON e.id = l.user_id and e.org_id = l.publisher_id" .
				" JOIN `stores` r ON r.store_id = l.registered_by" .
				" JOIN `stores` t ON t.store_id = l.last_updated_by" .
				" WHERE e.org_id = $org_id AND e.id IS NOT NULL $filter ";



		if($outtype == 'query')
		return $this->db->query($sql);
		elseif($outtype == 'query_table')
		return $this->db->query_table($sql);
		elseif($outtype == 'sql')
		return $sql;

	}

	/**
	 * moves the rule up used by move rule up action
	 *
	 * @param integer $rule_id
	 *
	 */
	function moveRuleUpByRuleId($rule_id){

		$org_id = $this->currentorg->org_id;
		$find = $this->db->query("SELECT id, `order` FROM loyalty_rules WHERE org_id = '$org_id' ORDER BY `order` DESC");

		$other = -1;
		$found = -1;
		foreach ($find as $row) {
			$found = $row;
			if ($row['id'] == $rule_id) break;
			$other = $row;
			$found = -1; # Set it to -1 to mark completition
		}

		if ($other != -1 && $found != -1) {
			$this->db->update("UPDATE loyalty_rules SET `order` = '$other[order]' WHERE id = $found[id]");
			$this->db->update("UPDATE loyalty_rules SET `order` = '$found[order]' WHERE id = $other[id]");
		}

	}
	/**
	 * @param ineteger $rule_id
	 * deletes loyaty rules by org_id and rule_id
	 */
	function deleteLoyaltyRuleByRuleId($rule_id){
		$org_id = $this->currentorg->org_id;
		$this->db->update("DELETE FROM loyalty_rules WHERE org_id = '$org_id' AND id = '$rule_id'");
	}

	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getAwardedPointsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
				$attrs = array(),$cat_id = false,$store_selected=false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`al`.`awarded_by`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND `al`.`awarded_by` IN (".Util::joinForSql( $store_selected ).") ";
		}

		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);

			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$primary_key_filter = '';
		if($outtype == 'query_table')
			$primary_key_filter='{{where}}';
			
		$sql = "SELECT  al.id, al.`user_id` , al.`loyalty_id`, 
						TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.mobile AS customer_mobile,
						al.awarded_points, al.ref_bill_number, al.notes, `s`.`username` AS `Awarded Store`,
						`al`.`awarded_time`
						
				 FROM awarded_points_log al 
				 JOIN users u ON al.user_id = u.id AND al.org_id = u.org_id 				 
				 JOIN `stores` AS `s` ON ( `s`.`org_id` = `al`.`org_id` AND `s`.`store_id` = `al`.`awarded_by` ) 
				 $zone_filter
				 $cat_filter
				WHERE al.org_id = $org_id AND al.awarded_time BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter $primary_key_filter ";

		$limit_sql = $sql . ' LIMIT 100';
		
		if($outtype == 'query_table')
			return array( $sql , false , 'al.id' );

		return array( $sql , $this->db->query($limit_sql) , false );
	}

	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 *
	 * Reffereal details
	 */
	function getCampaignReferralsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', $attrs = array(),$cat_id = false,$store_selected=false){
			
		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`c`.`store_id`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND c.store_id IN (".Util::joinForSql( $store_selected ).") ";
		}

		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `ur`.`org_id` AND `s`.`subscriberId` = `ur`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$database = Util::dbName('campaigns',$this->testing);

		$enddate = Util::getNextDate($enddate);

		$sql = "SELECT TRIM(CONCAT(ur.firstname,' ',ur.lastname)) AS referrer, ur.id AS referrer_user_id, 
					ur.mobile AS referrer_mobile, us.username AS referred_at_store, vs.description, v.voucher_code, 
					c.referee_name, c.referee_mobile, c.referee_email, c.created_on AS referred_on, c.num_reminders, 
					IFNULL(c.last_reminded, 'No reminders sent') AS last_reminded_on, 
					IFNULL( l.joined, 'Not Joined Yet') AS referee_joined_status " .
				" FROM $database.campaign_referrals c " .
				" JOIN users ur ON c.org_id = ur.org_id AND c.referrer_id = ur.id " .
				" JOIN stores us ON c.org_id = us.org_id AND c.store_id = us.store_id AND us.tag = 'org' " .
				" JOIN $database.voucher_series vs ON c.org_id = vs.org_id AND c.voucher_series_id = vs.id " .
				" JOIN $database.voucher v ON v.org_id = c.org_id AND c.voucher_id = v.voucher_id AND c.voucher_series_id = v.voucher_series_id " .
				" LEFT JOIN users ujr ON c.org_id = ujr.org_id AND ujr.mobile = c.referee_mobile ".
				" LEFT JOIN loyalty l ON ujr.org_id = l.publisher_id AND ujr.id = l.user_id ".
				$zone_filter . 
				$cat_filter .
				" WHERE c.org_id = $org_id AND c.created_on BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter";

		$limit_sql = $sql . ' LIMIT 100';
		
		if($outtype == 'query_table')
			return array( $sql , false );
		
		return array( $sql , $this->db->query($limit_sql) );
	}

	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getLoyaltyBillLineItemsTableDump($startdate, $enddate, $zone_selected = false, 
				$outtype = 'query', $attrs = array(),$cat_id = false,$store_selected=false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`lbl`.`store_id`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND `lbl`.`store_id` IN (".Util::joinForSql( $store_selected ).") ";
		}
		
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$primary_key_filter = '';
		if($outtype == 'query_table')
			$primary_key_filter = '{{where}}';
			
		$sql = "SELECT lbl.id as line_item_id, ll.id as bill_id, ll.bill_number, ll.date, lbl.item_code, 
					lbl.description, lbl.rate, lbl.qty, lbl.value, lbl.discount_value, lbl.amount, 
					oe.code AS entered_by, ll.user_id, oe2.code AS entered_by_counter, ll.loyalty_id, 
					TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, 
					u.mobile AS customer_mobile " .
				" FROM loyalty_bill_lineitems lbl " .
				" JOIN loyalty_log ll ON lbl.loyalty_log_id = ll.id AND lbl.org_id = ll.org_id " .
				" JOIN users u ON ll.user_id = u.id AND ll.org_id = u.org_id " .
				" JOIN masters.org_entities oe ON oe.id =  ll.entered_by AND oe.org_id = ll.org_id ".
				" LEFT JOIN masters.org_entities oe2 ON oe2.id =  ll.counter_id AND oe2.org_id = ll.org_id " .
				$zone_filter . 
				$cat_filter . 
				" WHERE lbl.org_id = $org_id AND `ll`.`date` BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter $primary_key_filter ";

		$limit_sql = $sql . ' LIMIT 100';
		if($outtype == 'query_table')
			return array( $sql, false,'lbl.id');

		return array( $sql, $this->db->query( $limit_sql ),false);
	}

	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $column 'joined' or 'last_updated'
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 *
	 * Loyaty customers table dump
	 */
	function getLoyaltyTableDump($startdate, $enddate, $column, $zone_selected = false, $outtype = 'query', 
				$attrs = array(),$cat_id = false,$store_selected=false){

		$org_id = $this->currentorg->org_id;

		$store_col = 'registered_by';
		$date_col = 'joined';
		if($column == 'last_updated'){
			$store_col = 'last_updated_by';
			$date_col = 'last_updated';
		}
			

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter("`l`.$store_col", $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND l.$store_col IN (".Util::joinForSql( $store_selected ).") ";
		}
		
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);

		$primary_key_filter = '';
		if($outtype == 'query_table')
			$primary_key_filter = '{{where}}';
			
		$sql = "SELECT l.id AS loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, 
					u.id as user_id, u.mobile, u.email, null as address, null as birthday, null as age_group, 
					null as anniversary, null as sex, null as spouse_birthday, l.loyalty_points, l.slab_number, 
					l.slab_name, l.lifetime_points, l.lifetime_purchases, l.external_id, l.joined, l.last_updated ,
					oe.code AS reg_by, oe2.code AS last_updated_by, oe3.code AS reg_by_counter " .
				" FROM loyalty l " .
				" JOIN users u ON l.user_id = u.id AND l.publisher_id = u.org_id " .
				" JOIN masters.org_entities oe ON oe.id =  l.registered_by AND oe.org_id = u.org_id" .
				" JOIN masters.org_entities oe2 ON oe2.id =  l.last_updated_by AND oe2.org_id = u.org_id" .
				" JOIN masters.org_entities oe3 ON oe3.id =  l.counter_id AND oe3.org_id = u.org_id " .
				$zone_filter.
				$cat_filter.
				" WHERE l.publisher_id = $org_id AND l.$date_col BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter $primary_key_filter ";

		$limit_sql = $sql . ' LIMIT 100';
		if($outtype == 'query_table')
			return array( $sql, false , 'l.id' );

		return array( $sql, $this->db->query($limit_sql) , false );
	}


	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
	function getLoyaltyRepeatBillsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
					$attrs = array(),$cat_id = false,$store_selected=false){

		$org_id = $this->currentorg->org_id;

		$zone_filter = "";

		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('l.entered_by', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND l.entered_by IN (".Util::joinForSql( $store_selected ).") ";
		}
		
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);

		$primary_key_filter='';
		if($outtype == 'query_table')
			$primary_key_filter='{{where}}';
			
		$sql = "SELECT l.id,l.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.id as user_id, 
					u.mobile AS customer_mobile, ly.joined AS customer_joined_on, l.points, 
					l.bill_number, l.bill_amount, l.date, l.notes, 
					oe.code AS entered_by, oe2.code AS entered_by_counter "
		. " FROM loyalty_log AS l "
		. " JOIN loyalty AS ly ON `l`.`org_id` = `ly`.`publisher_id` AND `l`.`user_id` = `ly`.`user_id`  AND DATE(`l`.`date`) > DATE(`ly`.`joined`) "
		. " JOIN users u ON l.user_id = u.id AND l.org_id = u.org_id "
		. " JOIN masters.org_entities oe ON oe.id =  l.entered_bu AND oe.org_id = l.org_id "
		. " LEFT JOIN masters.org_entities oe2 ON oe2.id =  l.counter_id AND oe2.org_id = l.org_id "
		. $zone_filter
		. $cat_filter
		. " WHERE `l`.`org_id` = $org_id AND `l`.`date` BETWEEN DATE( '$startdate' ) AND DATE( '$enddate' ) $store_filter $primary_key_filter ";

		$limit_sql = $sql . ' LIMIT 100';
		if($outtype == 'query_table')
		return array($sql,false,'l.id');

		return array($sql,$this->db->query($limit_sql),false);
	}

	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 *
	 * Gives Refferer DETAILS
	 * @Deprecated
	 */
	function getMLMReferralSuccessTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', $attrs = array(),$cat_id = false){

//	$org_id = $this->currentorg->org_id;
//
//	$zone_filter = "";
//
//	if($zone_selected != false){
//
//		$am = new AdministrationModule();
//
//		//create a filter out of the sub zones for the zone selected
//		$zone_filter = $am->createZoneFilter($zone_selected);
//
//		$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`mr`.`referred_at_store`', $zone_filter));
//	}
//	if($cat_id){
//		$database_msging = Util::dbName('msging',$this->testing);
//		$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
//					( `s`.`publisherId` = `eup_main`.`org_id` AND `s`.`subscriberId` = `eup_main`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
//	}
//	
//	$enddate = Util::getNextDate($enddate);
//	
//	$sql = "SELECT TRIM(CONCAT(eup_r.firstname, ' ', eup_r.lastname)) AS referrer_name, eup_r.user_id AS referrer_user_id, eup_r.mobile AS referrer_mobile, eup_r.address AS referrer_address, TRIM(CONCAT(eup_main.firstname, ' ', eup_main.lastname)) AS customer_name, eup_main.mobile AS cutomer_mobile, eup_main.address AS cutomer_address, mu.mlm_code, mu.subtree_size, mr.referral_date, mr.last_reminded, l.joined AS joined_on, l.slab_name, l.lifetime_purchases, u_rs.username AS referred_at_store_name, u_js.username AS joined_at_store_name, u_ls.username AS last_shopped_at_store_name " .
//			" FROM (SELECT * FROM mlm_referrals mr_sub WHERE mr_sub.org_id = $org_id GROUP BY referrer_id, referee_mobile) mr " .
//			" JOIN extd_user_profile eup_main ON eup_main.org_id = mr.org_id AND mr.referee_id_joined = eup_main.user_id " .
//			" JOIN loyalty l ON l.publisher_id = mr.org_id AND eup_main.user_id = l.user_id " .
//			" JOIN users u_rs ON u_rs.org_id = mr.org_id AND u_rs.id = mr.referred_at_store " .
//			" JOIN users u_js ON u_js.org_id = l.publisher_id AND u_js.id = l.registered_by " .
//			" JOIN users u_ls ON u_ls.org_id = l.publisher_id AND u_ls.id = l.last_updated_by " .
//			" JOIN extd_user_profile eup_r ON eup_r.org_id = mr.org_id AND mr.referrer_id = eup_r.user_id " .
//			" JOIN mlm_users mu ON mu.org_id = mr.org_id AND mu.user_id = eup_main.user_id ".
//			$zone_filter.
//			$cat_filter .
//			" WHERE mr.org_id = $org_id  AND `mr`.`referee_id_joined` IS NOT NULL AND `l`.`joined` BETWEEN DATE('$startdate') AND DATE('$enddate') AND mr.referrer_id < mr.referee_id_joined ORDER BY `mr`.`referral_date`";
//
//	if($outtype == 'query_table')
//	return array($sql,$this->db->query_table($sql, $attrs['name']));
//
//	return array($sql,$this->db->query($sql));
	}

	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 *
	 * Gives The Table Dump For MLM users
	 * @deprecated
	 */
	function getMLMUsersTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', $attrs = array(),$cat_id = false){
//
//	$org_id = $this->currentorg->org_id;
//
//	$zone_filter = "";
//
//	if($zone_selected != false){
//
//		$am = new AdministrationModule();
//
//		//create a filter out of the sub zones for the zone selected
//		$zone_filter = $am->createZoneFilter($zone_selected);
//
//		$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`m`.`added_by`', $zone_filter));
//	}
//	if($cat_id){
//		$database_msging = Util::dbName('msging',$this->testing);
//		$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
//					( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
//	}
//	
//	$enddate = Util::getNextDate($enddate);
//	
//	$sql = "SELECT m.*, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.mobile AS customer_mobile " .
//			" FROM mlm_users m " .
//			" JOIN extd_user_profile u ON m.user_id = u.user_id AND m.org_id = u.org_id ".
//			$zone_filter.
//			$cat_filter.
//			" WHERE m.org_id = $org_id AND m.joined BETWEEN DATE('$startdate') AND DATE('$enddate')";
//
//	if($outtype == 'query_table')
//	return array($sql,$this->db->query_table($sql, $attrs['name']));
//
//	return array($sql,$this->db->query($sql));
	}
	
	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 *
	 * Gives The Table Dump For Loyalty Promotional Details
	 */
	function getLoyaltyPromotionalDetailsTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
					$attrs = array(),$cat_id = false,$store_selected=false){

		$org_id = $this->currentorg->org_id;
		$zone_filter = "";
		$database_campaigns = Util::dbName('campaigns',$this->testing);
		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);
			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`l`.`entered_by`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND l.entered_by IN (".Util::joinForSql( $store_selected ).") ";
		}
		
		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}
		
		$enddate = Util::getNextDate($enddate);
		
		$sql = "SELECT l.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.id as user_id, 
					u.mobile AS customer_mobile, vs.description as voucher_series_assoc, 
					lp.details as promo_details, ly.joined AS customer_joined_on, l.points, 
					l.bill_number, l.bill_amount, l.date, l.notes, 
					oe.code AS entered_by, oe2.code AS entered_by_counter " .
				" FROM `loyalty_promotional_campaign_bills` lp " .
				" JOIN `$database_campaigns`.`voucher_series` vs ON vs.id = lp.series_id " .
				" JOIN loyalty_log l ON l.id = lp.loyalty_log_id " .
				" JOIN users u ON l.user_id = u.id AND l.org_id = u.org_id " .				" JOIN loyalty ly ON ly.publisher_id = l.org_id AND ly.id = l.loyalty_id  ".
				" JOIN masters.org_entities oe ON oe.id =  l.entered_bu AND oe.org_id = l.org_id ".
				" LEFT JOIN masters.org_entities oe2 ON oe2.id =  l.counter_id AND oe2.org_id = l.org_id ".
				$zone_filter . 
				$cat_filter .
				" WHERE l.org_id = $org_id AND l.date BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter";

		if($outtype == 'query_table')
		return array($sql,$this->db->query_table($sql, $attrs['name']));

		return array($sql,$this->db->query($sql));		
	}
	
	
	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 *
	 * Gives The Table Dump For Loyalty Expiry Details
	 * @deprecated
	 */
// 	function getExpiredLoyaltyInfo($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
// 				$attrs = array(),$cat_id = false,$store_selected = false){
		
// 		$org_id = $this->currentorg->org_id;

// 		$zone_filter = "";

// 		if($zone_selected != false){

// 			$am = new AdministrationModule();

// 			//create a filter out of the sub zones for the zone selected
// 			$zone_filter = $am->createZoneFilter($zone_selected);

// 			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`eli`.`last_updated_by`', $zone_filter));
// 		}
		
// 		$store_filter = "";
// 		if($store_selected != false){

// 			$store_filter = " AND `eli`.`last_updated_by` IN (".Util::joinForSql( $store_selected ).") ";
// 		}
		
// 		if($cat_id){
// 			$database_msging = Util::dbName('msging',$this->testing);
// 			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
// 						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
// 		}
		
// 		$enddate = Util::getNextDate($enddate);
		
// 		$sql = "SELECT TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.user_id, 
// 					u.mobile AS customer_mobile, eli.loyalty_current_points as points_when_expired, 
// 					l.loyalty_points as points_now, l.lifetime_points, l.lifetime_purchases,
// 		 			l.joined AS customer_joined_on, eli.expiry_checked_on, eli.last_updated_on, 
// 		 			oe3.code AS registered_by, oe.code AS last_updated_by, 
// 		 			oe2.code AS entered_by, oe4.code AS reg_by_counter " .
// 				" FROM `expired_loyalty_info_log` eli " .
// 				" JOIN loyalty l ON l.publisher_id = eli.org_id AND l.user_id = eli.user_id ".
// 				" JOIN extd_user_profile u ON eli.user_id = u.user_id AND eli.org_id = u.org_id " .
// 				" JOIN stores s1 ON eli.last_updated_by = s1.store_id AND eli.org_id = s1.org_id " .
// 				" JOIN stores s2 ON eli.entered_by = s2.store_id " .
// 				" JOIN stores s3 ON l.registered_by = s3.store_id AND l.publisher_id = s3.org_id " .
// 				" LEFT OUTER JOIN stores s4 ON l.counter_id = s4.store_id AND l.publisher_id = s4.org_id ".
// 				JOIN masters.org_entities oe ON oe1.id =  l.last_updated_by AND oe.org_id = l.publisher_id
// 				JOIN masters.org_entities oe2 ON oe2.id =  l.last_updated_by AND oe2.org_id = l.publisher_id
// 				JOIN masters.org_entities oe3 ON oe3.id =  l.last_updated_by AND oe3.org_id = u.org_id
// 				LEFT JOIN masters.org_entities oe4 ON oe4.id =  l.last_updated_by AND oe4.org_id = u.org_id
// 				$zone_filter . 
// 				$cat_filter .
// 				" WHERE eli.org_id = $org_id AND eli.expiry_checked_on BETWEEN DATE('$startdate') AND DATE('$enddate')
// 				$store_filter";

// 		if($outtype == 'query_table')
// 		return array($sql,$this->db->query_table($sql, $attrs['name']));

// 		return array($sql,$this->db->query($sql));
		
// 	}
	
	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 *
	 * Gives The Table Dump for Custom Fields ( Registration Scope by default)
	 * @deprecated
	 */
// 	function getCustomFieldsTableDump($startdate, $enddate, $scope = false, $zone_selected = false, $outtype = 'query_table', 
// 					$attrs = array(),$cat_id = false,$store_selected=false){

// 		$org_id = $this->currentorg->org_id;
// 		$view_in_table = $attrs['show_table'];
		
// 		$limit = "";
// 		if( $view_in_table )
// 			$limit = " LIMIT ".$attrs['show_table'];
			
// 		$enddate_next = util::getNextDate($enddate);
// 		if( !$scope ){
			
// 			$scope = LOYALTY_CUSTOM_REGISTRATION;
// 		}
// 		$zone_filter = "";
		
// 		if($zone_selected != false){

// 			$am = new AdministrationModule();

// 			//create a filter out of the sub zones for the zone selected
// 			$zone_filter = $am->createZoneFilter($zone_selected);

// 			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`l`.`registered_by`', $zone_filter));
// 		}
		
// 		$store_filter = "";
// 		if($store_selected != false){

// 			$store_filter = " AND l.registered_by IN (".Util::joinForSql( $store_selected ).") ";
// 		}
		
// 		$cf = new CustomFields();
// 		$option = $cf->getCustomFieldsByScope($org_id, $scope );
		
// 		$cf_ids = array();
// 		foreach( $option AS $o ){
			
// 			array_push($cf_ids, $o['id']);
// 		} 
				
// 		//********************//
// 		if($cf_ids){
// 			$cf_ids = implode(',', $cf_ids);

// 			$select_filter = " , GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
// 								GROUP_CONCAT( CASE WHEN `cfd`.`value` IS NULL THEN '[\"NA\"]' ELSE `cfd`.`value` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Value`";
			
// 			$join_filter = "
// 								JOIN `custom_fields` AS `cf` ON 		
// 									( `cf`.`org_id` = `l`.`publisher_id` AND `cf`.`id` IN ( $cf_ids )  )
// 								LEFT OUTER JOIN `custom_fields_data` AS `cfd` ON 
// 									( `cfd`.`org_id` = `l`.`publisher_id` AND `l`.`user_id` = `cfd`.`assoc_id` AND `cf`.`id` = `cfd`.`cf_id` )
							
// 									";
// 			$group_filter = "GROUP BY `u`.`user_id`";
			
// 		}

// 		if($cat_id){
// 			$database_msging = Util::dbName('msging',$this->testing);
// 			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
// 						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
// 		}
		
// 		$order_filter='';
// 		$primary_key_filter='';
// 		if($outtype == 'query_table'){
// 			$primary_key_filter=' {{where}} ';
// 			$order_filter=' {{order}} ';	 
// 		}

// 		$sql = "SELECT l.id AS loyalty_id,TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, 
// 					 u.user_id, u.mobile, l.loyalty_points, l.slab_number, l.slab_name, 
// 					 l.lifetime_points, l.lifetime_purchases, u.email, u.address, u.birthday, 
// 					 u.anniversary, u.sex, u.spouse_birthday, l.external_id,
// 					 s1.username AS reg_by, s2.username AS reg_by_counter, 
// 					 l.joined, l.last_updated $select_filter
// 				 FROM loyalty l 
// 				 JOIN extd_user_profile u ON l.user_id = u.user_id AND l.publisher_id = u.org_id 
// 				 JOIN stores s1 ON l.registered_by = s1.store_id AND l.publisher_id = s1.org_id " .
// 				" LEFT OUTER JOIN stores s2 ON l.counter_id = s2.store_id AND l.publisher_id = s2.org_id ".
// 				$join_filter . 
// 				$zone_filter.
// 				$cat_filter .
// 				" WHERE l.publisher_id = $org_id AND `l`.`joined` BETWEEN '$startdate' AND '$enddate_next' $primary_key_filter
// 				$store_filter $group_filter $order_filter $limit";

// 		if( !$view_in_table && $outtype == 'query_table' )
// 			return array( $sql , array() , 'l.id' );
		
// 		$query = $this->db->query($sql);
		
// 		$custom_value = array();
// 		foreach($query as $q){

// 				$store_data = array(
// 					'loyalty_id' => $q['loyalty_id']
// 				);
				
// 				//custom field name and the counts are in group concat format 
// 				$cf_names = explode('^*^', $q['Custom-Field-Name']);
// 				$cf_value = explode('^*^', $q['Custom-Field-Value']);
				
// 				for($i = 0; $i < count($cf_names); $i++){					
// 					//Store as ret_val_<cf_index>
// 					$cf_value[$i] = json_decode($cf_value[$i], true);
// 					$index = 'ret_val_'.$i;
// 					$store_data[$index] =  implode(',' ,$cf_value[$i]);
					
// 				}

// 				array_push($custom_value, $store_data);
// 				$custom_field_names = $cf_names;
// 		}
		
		
// 		$table = new Table('custom');
// 		$table->importArray($query);
// 		$table->removeHeader('Custom-Field-Name');
// 		$table->removeHeader('Custom-Field-Value');
// 		$table->removeHeader('loyalty_id');
// 		$table->removeHeader('user_id');			 		 
				
// 		$q_new = array(
// 			'name' => $custom_field_names,
// 			'ret' => $custom_value
// 		);						
	
// 		function addRow($row,$params){
			
// 			foreach($params['ret'] as $param){
// 				if($param['loyalty_id'] == $row['loyalty_id']){
// 					$return_val_all = array();
// 					for($i = 0; $i < count($params['name']); $i++){
// 						$val = "ret_val_".$i;
// 						$return_val_all[$i] = $param[$val];
// 					}
// 					return $return_val_all;
// 				}
// 			}
// 		}

// 		if(count($q_new['name']) != 0){

// 			$table->addManyFieldsByMap($q_new['name'], 'addRow' ,$q_new);
// 		}

// 		if( $outtype == 'query_table' )
// 			return array( $sql , $table);
// 		else
// 			return array( $sql , $table->getData(),false);
// 	}

	/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 *
	 * Gives The Table Dump for Custom Fields ( Registration Scope by default)
	 * @deprecated
	 */
// 	function getTransactionCustomFieldsTableDump($startdate, $enddate, $scope = false, $zone_selected = false, 
// 					$outtype = 'query_table', $attrs = array(),$cat_id = false,$store_selected=false){

// 		$org_id = $this->currentorg->org_id;
		
// 		$view_in_table = $attrs['show_table'];
		
// 		$limit = "";
// 		if( $view_in_table )
// 			$limit = " LIMIT ".$attrs['show_table'];
			
// 		$enddate_next = util::getNextDate($enddate);
// 		$zone_filter = "";
		
// 		if($zone_selected != false){

// 			$am = new AdministrationModule();

// 			//create a filter out of the sub zones for the zone selected
// 			$zone_filter = $am->createZoneFilter($zone_selected);

// 			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`l`.`registered_by`', $zone_filter));
// 		}
		
// 		$store_filter = "";
// 		if($store_selected != false){

// 			$store_filter = " AND `l`.`registered_by` IN (".Util::joinForSql( $store_selected ).") ";
// 		}
		
// 		$cf = new CustomFields();
// 		$option = $cf->getCustomFieldsByScope($org_id, $scope );
		
// 		$cf_ids = array();
// 		foreach( $option AS $o ){
			
// 			array_push($cf_ids, $o['id']);
// 		} 
				
// 		//********************//
// 		if($cf_ids){
// 			$cf_ids = implode(',', $cf_ids);

// 			$select_filter = " , GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
// 								GROUP_CONCAT( CASE WHEN `cfd`.`value` IS NULL THEN '[\"NA\"]' ELSE `cfd`.`value` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Value`";
			
// 			$join_filter = "
// 								JOIN `custom_fields` AS `cf` ON 		
// 									( `cf`.`org_id` = `l`.`publisher_id` AND `cf`.`id` IN ( $cf_ids )  )
// 								LEFT OUTER JOIN `custom_fields_data` AS `cfd` ON 
// 									( `cfd`.`org_id` = `ll`.`org_id` AND `ll`.`id` = `cfd`.`assoc_id` AND `cf`.`id` = `cfd`.`cf_id` )
							
// 									";
// 			$group_filter = "GROUP BY ll.id";
			
// 		}

// 		if($cat_id){
// 			$database_msging = Util::dbName('msging',$this->testing);
// 			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
// 						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
// 		}

// 		$order_filter='';
// 		$primary_key_filter='';
// 		if($outtype == 'query_table'){
// 			$primary_key_filter=' {{where}} ';
// 			$order_filter=' {{order}} ';	 
// 		}
		
// 		$sql = "SELECT ll.id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, 
// 					  u.mobile, l.loyalty_points, l.slab_number, l.slab_name, 
// 					  u.email,  l.external_id, ll.bill_number, ll.date, s1.username AS entered_by ,
// 					  l.joined, ll.id AS loyalty_log_id $select_filter
// 				 FROM loyalty_log AS ll 
// 				 JOIN loyalty l ON ( ll.loyalty_id = l.id AND ll.org_id = l.publisher_id  )
// 				 JOIN extd_user_profile u ON  l.publisher_id = u.org_id AND l.user_id = u.user_id 
// 				 JOIN stores s1 ON ll.entered_by = s1.store_id AND ll.org_id = s1.org_id " .
// 				$join_filter . 
// 				$zone_filter.
// 				$cat_filter .
// 				" WHERE ll.org_id = $org_id AND `ll`.`date` BETWEEN '$startdate' AND '$enddate_next' $primary_key_filter
// 				$store_filter $group_filter $order_filter $limit";

// 		if( !$view_in_table && $outtype == 'query_table' )
// 			return array( $sql , array() , 'll.id');
			
// 		$query = $this->db->query($sql);
		
// 		$custom_value = array();
// 		foreach($query as $q){

// 				$store_data = array(
// 					'loyalty_log_id' => $q['loyalty_log_id']
// 				);
				
// 				//custom field name and the counts are in group concat format 
// 				$cf_names = explode('^*^', $q['Custom-Field-Name']);
// 				$cf_value = explode('^*^', $q['Custom-Field-Value']);
				
// 				for($i = 0; $i < count($cf_names); $i++){					
// 					//Store as ret_val_<cf_index>
// 					$cf_value[$i] = json_decode($cf_value[$i], true);
// 					$index = 'ret_val_'.$i;
// 					$store_data[$index] =  is_array($cf_value[$i]) ? implode(',' ,$cf_value[$i]) : $cf_value[$i];
					
// 				}

// 				array_push($custom_value, $store_data);
// 				$custom_field_names = $cf_names;
// 		}
		
// 		$table = new Table('custom');
// 		$table->importArray($query);
// 		$table->removeHeader('Custom-Field-Name');
// 		$table->removeHeader('Custom-Field-Value');
// 		$table->removeHeader('loyalty_id');
// 		$table->removeHeader('user_id');			 		 
				
// 		$q_new = array(
// 			'name' => $custom_field_names,
// 			'ret' => $custom_value
// 		);						
	
// 		function addRow($row,$params){
			
// 			foreach($params['ret'] as $param){
// 				if($param['loyalty_log_id'] == $row['loyalty_log_id']){
// 					$return_val_all = array();
// 					for($i = 0; $i < count($params['name']); $i++){
// 						$val = "ret_val_".$i;
// 						$return_val_all[$i] = $param[$val];
// 					}
// 					return $return_val_all;
// 				}
// 			}
// 		}

// 		if(count($q_new['name']) != 0){

// 			$table->addManyFieldsByMap($q_new['name'], 'addRow' ,$q_new);
// 		}

// 		if( $outtype == 'query_table' )
// 			return array( $sql , $table );
// 		else
// 			return array( $sql , $table->getData() , false);
// 	}
	
	/**
	 * 
	 * @param unknown_type $startdate
	 * @param unknown_type $enddate
	 * @param unknown_type $scope
	 * @param unknown_type $zone_selected
	 * @param unknown_type $outtype
	 * @param unknown_type $attrs
	 * @param unknown_type $cat_id
	 * @param unknown_type $store_selected
	 * @return multitype:multitype: string |Ambigous <multitype:, unknown>|multitype:string Table |multitype:string NULL
	 * 
	 * Gives The Table Dump for Custom Fields ( Voucher Redemption Scope by default)
	 */
	function getVoucherRedemptionCustomFieldsTableDump($startdate, $enddate, $scope = false, $zone_selected = false, 
					$outtype = 'query_table', $attrs = array(),$cat_id = false,$store_selected=false){

		$org_id = $this->currentorg->org_id;
		
		$view_in_table = $attrs['show_table'];
		
		$limit = "";
		if( $view_in_table )
			$limit = " LIMIT ".$attrs['show_table'];
			
		$enddate_next = util::getNextDate($enddate);
		$zone_filter = "";
		
		if($zone_selected != false){

			$am = new AdministrationModule();

			//create a filter out of the sub zones for the zone selected
			$zone_filter = $am->createZoneFilter($zone_selected);

			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('vr.`used_at_store`', $zone_filter));
		}
		
		$store_filter = "";
		if($store_selected != false){

			$store_filter = " AND vr.`used_at_store` IN (".Util::joinForSql( $store_selected ).") ";
		}
		
		$cf = new CustomFields();
		$option = $cf->getCustomFieldsByScope($org_id, $scope );
		
		$cf_ids = array();
		foreach( $option AS $o ){
			
			array_push($cf_ids, $o['id']);
		} 
				
		//********************//
		if($cf_ids){
			$cf_ids = implode(',', $cf_ids);

			$select_filter = " , GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
								GROUP_CONCAT( CASE WHEN `cfd`.`value` IS NULL THEN '[\"NA\"]' ELSE `cfd`.`value` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Value`";
			
			$join_filter = "
								JOIN `user_management`.`custom_fields` AS `cf` ON 		
									( `cf`.`org_id` = `vr`.`org_id` AND `cf`.`id` IN ( $cf_ids )  )
								LEFT OUTER JOIN `user_management`.`custom_fields_data` AS `cfd` ON 
									( `cfd`.`org_id` = `vr`.`org_id` AND `vr`.`id` = `cfd`.`assoc_id` AND `cf`.`id` = `cfd`.`cf_id` )
							
									";
			$group_filter = "GROUP BY vr.id";
			
		}

		if($cat_id){
			$database_msging = Util::dbName('msging',$this->testing);
			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
						( `s`.`publisherId` = `eup_r`.`org_id` AND `s`.`subscriberId` = `eup_r`.`id` AND `s`.`categoryId` IN ($cat_id) ) ";
		}

		$order_filter='';
		$primary_key_filter='';
		if($outtype == 'query_table'){
			$primary_key_filter=' {{where}} ';
			$order_filter=' {{order}} ';	 
		}
		
		$sql = "SELECT vr.id as vr_id, TRIM(CONCAT(eup_r.firstname, ' ', eup_r.lastname)) AS customer_name, 
					eup_r.id AS customer_user_id, eup_r.mobile AS customer_mobile, v.voucher_code, 
					vr.used_date AS used_on, vs.description,u_rs.username AS used_at_store, 
					IF( LENGTH(vr.bill_number) = 0 OR vr.bill_number IS NULL, 'Not Entered', vr.bill_number) AS bill_no, 
					vr.bill_amount as bill_amount,
					ll.bill_gross_amount, ll.bill_discount, 
					( SELECT MAX(ls.date) 
						FROM user_management.loyalty_log ls 
						WHERE ls.org_id = v.org_id 
						AND ls.user_id = vr.used_by and ls.date < vr.used_date ) AS prev_bill_on, 
					vr.sales_nextbill, vr.sales_sameday $select_filter
				FROM `campaigns`.`voucher_redemptions` vr 					
				JOIN `campaigns`.`voucher` v ON vr.voucher_id = v.voucher_id AND v.voucher_series_id = vr.voucher_series_id AND `v`.`test` = '0'
				JOIN `campaigns`.`voucher_series` vs ON `v`.`voucher_series_id` = `vs`.`id` 
				LEFT JOIN `user_management`.`loyalty_log` ll ON ll.org_id = v.org_id AND ll.user_id = vr.used_by AND ll.bill_number = vr.bill_number 
				JOIN `user_management`.`users` eup_r ON vr.used_by = eup_r.id AND eup_r.org_id = v.org_id 					
				JOIN `user_management`.`stores` u_rs ON u_rs.store_id = vr.used_at_store AND u_rs.org_id = vr.org_id ".
				$join_filter . 
				$zone_filter.
				$cat_filter . 
				" WHERE vr.org_id = $org_id AND `vr`.`used_date` BETWEEN '$startdate' AND '$enddate_next' $primary_key_filter 
				$store_filter $group_filter $order_filter $limit";
		
		if( !$view_in_table && $outtype == 'query_table' )
			return array( $sql , array() , 'vr.id');
			
		$query = $this->db->query($sql);
		
		$custom_value = array();
		foreach($query as $q){

				$store_data = array(
					'vr_id' => $q['vr_id']
				);
				
				//custom field name and the counts are in group concat format 
				$cf_names = explode('^*^', $q['Custom-Field-Name']);
				$cf_value = explode('^*^', $q['Custom-Field-Value']);
				
				for($i = 0; $i < count($cf_names); $i++){					
					//Store as ret_val_<cf_index>
					$cf_value[$i] = json_decode($cf_value[$i], true);
					$index = 'ret_val_'.$i;
					$store_data[$index] =  is_array($cf_value[$i]) ? implode(',' ,$cf_value[$i]) : $cf_value[$i];
					
				}

				array_push($custom_value, $store_data);
				$custom_field_names = $cf_names;
		}
		
		$table = new Table('custom');
		$table->importArray($query);
		$table->removeHeader('Custom-Field-Name');
		$table->removeHeader('Custom-Field-Value');
				
		$q_new = array(
			'name' => $custom_field_names,
			'ret' => $custom_value
		);						
	
		function addRow($row,$params){
			
			foreach($params['ret'] as $param){
				if($param['vr_id'] == $row['vr_id']){
					$return_val_all = array();
					for($i = 0; $i < count($params['name']); $i++){
						$val = "ret_val_".$i;
						$return_val_all[$i] = $param[$val];
					}
					return $return_val_all;
				}
			}
		}

		if(count($q_new['name']) != 0){

			$table->addManyFieldsByMap($q_new['name'], 'addRow' ,$q_new);
		}

		if( $outtype == 'query_table' )
			return array( $sql , $table );
		else
			return array( $sql , $table->getData() , false);
	}
	
/**
	 * 
	 * @param unknown_type $startdate
	 * @param unknown_type $enddate
	 * @param unknown_type $scope
	 * @param unknown_type $zone_selected
	 * @param unknown_type $outtype
	 * @param unknown_type $attrs
	 * @param unknown_type $cat_id
	 * @param unknown_type $store_selected
	 * @return multitype:multitype: string |Ambigous <multitype:, unknown>|multitype:string Table |multitype:string NULL
	 * 
	 * Gives The Table Dump for Custom Fields ( Voucher Redemption Scope by default)
	 * @deprecated
	 */
// 	function getPointsRedemptionCustomFieldsTableDump($startdate, $enddate, $scope = false, $zone_selected = false, 
// 					$outtype = 'query_table', $attrs = array(),$cat_id = false,$store_selected=false){

// 		$org_id = $this->currentorg->org_id;
		
// 		$view_in_table = $attrs['show_table'];
		
// 		$limit = "";
// 		if( $view_in_table )
// 			$limit = " LIMIT ".$attrs['show_table'];
			
// 		$enddate_next = util::getNextDate($enddate);
// 		$zone_filter = "";
		
// 		if($zone_selected != false){

// 			$am = new AdministrationModule();

// 			//create a filter out of the sub zones for the zone selected
// 			$zone_filter = $am->createZoneFilter($zone_selected);

// 			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('l.`entered_by`', $zone_filter));
// 		}
		
// 		$store_filter = "";
// 		if($store_selected != false){

// 			$store_filter = " AND l.`entered_by` IN (".Util::joinForSql( $store_selected ).") ";
// 		}
		
// 		$cf = new CustomFields();
// 		$option = $cf->getCustomFieldsByScope($org_id, $scope );
		
// 		$cf_ids = array();
// 		foreach( $option AS $o ){
			
// 			array_push($cf_ids, $o['id']);
// 		} 
				
// 		//********************//
// 		if($cf_ids){
// 			$cf_ids = implode(',', $cf_ids);

// 			$select_filter = " , GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
// 								GROUP_CONCAT( CASE WHEN `cfd`.`value` IS NULL THEN '[\"NA\"]' ELSE `cfd`.`value` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Value`";
			
// 			$join_filter = "
// 								JOIN `custom_fields` AS `cf` ON 		
// 									( `cf`.`org_id` = `l`.`org_id` AND `cf`.`id` IN ( $cf_ids )  )
// 								LEFT OUTER JOIN `custom_fields_data` AS `cfd` ON 
// 									( `cfd`.`org_id` = `l`.`org_id` AND `l`.`id` = `cfd`.`assoc_id` AND `cf`.`id` = `cfd`.`cf_id` )
							
// 									";
// 			$group_filter = "GROUP BY l.id";
			
// 		}

// 		if($cat_id){
// 			$database_msging = Util::dbName('msging',$this->testing);
// 			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `s` ON 
// 						( `s`.`publisherId` = `u`.`org_id` AND `s`.`subscriberId` = `u`.`user_id` AND `s`.`categoryId` IN ($cat_id) ) ";
// 		}

// 		$order_filter='';
// 		$primary_key_filter='';
// 		if($outtype == 'query_table'){
// 			$primary_key_filter=' {{where}} ';
// 			$order_filter=' {{order}} ';	 
// 		}
		
// 		$sql = " SELECT l.id as lr_id,l.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.user_id, 
// 					u.mobile AS customer_mobile, l.points_redeemed, l.voucher_code, l.bill_number, 
// 					l.notes, l.date, s1.username AS redeemed_at_store $select_filter 
// 				FROM loyalty_redemptions l 
// 				JOIN extd_user_profile u ON l.user_id = u.user_id AND l.org_id = u.org_id 
// 				JOIN masters.org_entities oe ON oe.id = l.entered_by AND (oe.org_id = l.org_id  OR oe.org_id=0) ".
// 				$join_filter . 
// 				$zone_filter.
// 				$cat_filter .
// 				" WHERE l.org_id = $org_id AND l.date BETWEEN '$startdate' AND '$enddate_next' $primary_key_filter 
// 				$store_filter $group_filter $order_filter $limit ";
		
// 		if( !$view_in_table && $outtype == 'query_table' )
// 			return array( $sql , array() , 'l.id');
			
// 		$query = $this->db->query($sql);
		
// 		$custom_value = array();
// 		foreach($query as $q){

// 				$store_data = array(
// 					'lr_id' => $q['lr_id']
// 				);
				
// 				//custom field name and the counts are in group concat format 
// 				$cf_names = explode('^*^', $q['Custom-Field-Name']);
// 				$cf_value = explode('^*^', $q['Custom-Field-Value']);
				
// 				for($i = 0; $i < count($cf_names); $i++){					
// 					//Store as ret_val_<cf_index>
// 					$cf_value[$i] = json_decode($cf_value[$i], true);
// 					$index = 'ret_val_'.$i;
// 					$store_data[$index] =  is_array($cf_value[$i]) ? implode(',' ,$cf_value[$i]) : $cf_value[$i];
					
// 				}

// 				array_push($custom_value, $store_data);
// 				$custom_field_names = $cf_names;
// 		}
		
// 		$table = new Table('custom');
// 		$table->importArray($query);
// 		$table->removeHeader('Custom-Field-Name');
// 		$table->removeHeader('Custom-Field-Value');
				
// 		$q_new = array(
// 			'name' => $custom_field_names,
// 			'ret' => $custom_value
// 		);						
	
// 		function addRow($row,$params){
			
// 			foreach($params['ret'] as $param){
// 				if($param['lr_id'] == $row['lr_id']){
// 					$return_val_all = array();
// 					for($i = 0; $i < count($params['name']); $i++){
// 						$val = "ret_val_".$i;
// 						$return_val_all[$i] = $param[$val];
// 					}
// 					return $return_val_all;
// 				}
// 			}
// 		}

// 		if(count($q_new['name']) != 0){

// 			$table->addManyFieldsByMap($q_new['name'], 'addRow' ,$q_new);
// 		}

// 		if( $outtype == 'query_table' )
// 			return array( $sql , $table );
// 		else
// 			return array( $sql , $table->getData() , false);
// 	}
	
	function isBillCancelled($loyalty_id, $bill_number){
		$org_id = $this->currentorg->org_id;

		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$sql = "SELECT COUNT(*) FROM `cancelled_bills` WHERE loyalty_id = $loyalty_id AND bill_number = '$safe_bill_number' AND org_id = $org_id";
		$count = $this->db->query_scalar($sql);

		return $count > 0;
	}

	function isNotInterestedBillDuplicate( $bill_number){
		
		$org_id = $this->currentorg->org_id;
		
		$safe_bill_number = Util::mysqlEscapeString( $bill_number );
		$sql = "SELECT COUNT(*) FROM `loyalty_not_interested_bills` 
					WHERE bill_number = '$safe_bill_number' 
					AND org_id = $org_id";
		
		$count = $this->db->query_scalar($sql);
		return $count > 0;
		
	}
	
	function getDNDForUser($user_id){
		return false;
// 		$org_id = $this->currentorg->org_id;
		
// 		$nsadmin = new NSAdminThriftClient();
// 		$db = new Dbase('users');
// 		$sending_rules = array();
		
//  		$sql = "
// 				SELECT email,mobile 
// 				FROM user_management.users
// 				WHERE org_id = '$org_id' AND id = '$user_id'
//  			";
 		
// 		$res = $db->query_firstrow($sql);
		
// 		$this->logger->debug("Getting sending rules for user_id:$user_id  ");
		
// 		$mobile = $res['mobile'];
// 		$email = $res['email'];
		
// 		if($mobile)
// 		{
// 			$this->logger->debug("Getting sms sending rules for $mobile");
// 			$sms_rule = $nsadmin->getSmsSendingRule($mobile, $org_id);
// 			$sending_rules = array_merge($sending_rules,array("sms_sending_rule" => $sms_rule ));
// 		}
// 		if($email)
// 		{
// 			$this->logger->debug("Getting sms sending rules for $email");
// 			$email_rule = $nsadmin->getEmailSendingRule($email, $org_id);
// 			$sending_rules = array_merge($sending_rules,array("email_sending_rule" => $email_rule));
// 		}
		
// 		$this->logger->debug("Sending rules for user_id:$user_id  are". print_r($sending_rules, true));
// 		return $sending_rules;
	}
	
	function setDNDForUser($user_id, $email_dnd_option, $mobile_dnd_option){

		$org_id = $this->currentorg->org_id;

		$nsadmin = new NSAdminThriftClient();
		$db = new Dbase('users');
			
		$sql = "
		SELECT email,mobile
		FROM user_management.users
		WHERE org_id = '$org_id' AND id = '$user_id'
		";
			
		$res = $db->query_firstrow($sql);
		$this->logger->debug("Setting sending rules for user_id:$user_id  ");

		$mobile = $res['mobile'];
		$email = $res['email'];

		include_once 'business_controller/UserSubscriptionController.php';
		$usC = new UserSubscriptionController($org_id);

		if($mobile)
		{
			switch ($mobile_dnd_option)
			{
				case "ALL": $mobile_trans =0 ; $mobile_bulk = 0; break;
				CASE "NOBULK": $mobile_trans =1; $mobile_bulk = 0; break;
				case "NOPERSONALIZED": $mobile_trans =0 ; $mobile_bulk = 1; break;
				case "NONE": $mobile_trans =1 ; $mobile_bulk = 1; break;
			}
			$this->logger->debug("Setting sms sending rules for $mobile");
			if(isset($mobile_trans))
			{
				if($mobile_trans == 1)
					$usC->subscribeUser($user_id, "SMS", "TRANS");
				else
					$usC->unSubscribeUser($user_id, "SMS", "TRANS");
				$mobile_status = true;
			}

			if(isset($mobile_bulk))
			{
				if($mobile_bulk == 1)
					$usC->subscribeUser($user_id, "SMS", "BULK");
				else
					$usC->unSubscribeUser($user_id, "SMS", "BULK");
				$mobile_status = true;
			}

			//$mobile_status = $nsadmin->addSmsSendingRule($mobile, $org_id , $mobile_dnd_option);
		}
		if($email)
		{
			switch ($email_dnd_option)
			{
				case "ALL": $email_trans =0 ; $email_bulk = 0; break;
				CASE "NOBULK": $email_trans =1 ; $email_bulk = 0; break;
				case "NOPERSONALIZED": $email_trans =0 ; $email_bulk = 1; break;
				case "NONE": $email_trans =1 ; $email_bulk = 1; break;
			}

			if(isset($email_trans))
			{
				if($email_trans == 1)
					$usC->subscribeUser($user_id, "EMAIL", "TRANS");
				else
					$usC->unSubscribeUser($user_id, "EMAIL", "TRANS");
				$email_status = true;
			}

			if(isset($email_bulk))
			{
				if($email_bulk == 1)
					$usC->subscribeUser($user_id, "EMAIL", "BULK");
				else
					$usC->unSubscribeUser($user_id, "EMAIL", "BULK");
				$email_status = true;
			}

				
			$this->logger->debug("Setting email sending rules for $email");
			//$email_status = $nsadmin->addEmailSendingRule($email, $org_id , $email_dnd_option);
		}
		if($mobile_status == true || $email_status == true)
		{
			$status = true;
		}
		else {
			$status = false;
		}
		return $status;
	}

	function moveDNDForUser($from_user_id, $to_user_id){
		return true; 
// 		$org_id = $this->currentorg->org_id;
		
// 		$nsadmin_db = new Dbase('nsadmin');
		
// 		$sql = "
// 				UPDATE `sending_rules`
// 					SET `user_id` = '$to_user_id'
// 				WHERE org_id = '$org_id' AND `user_id` = '$from_user_id'
// 			";
// 		$nsadmin_db->update($sql);
		
// 		$sql = "
// 				DELETE 
// 				FROM `sending_rules`
// 				WHERE org_id = '$org_id' AND `user_id` = '$from_user_id'
// 			";
		
// 		return $nsadmin_db->update($sql);
	}
	
	
	function moveGroupsAndSubscriptionsForUser($from_user_id, $to_user_id){}
	
	function getPurchaseHistoryForApi($user_id, $ignore_store_id_filter = false){
		
		$org_id = $this->currentorg->org_id;

		$show_redeemed = '';
		$show_points = '';
		$store_id_filter = '';
		
		if($this->currentorg->getConfigurationValue(CONF_CLIENT_ONLY_STORE_PURCHASE_HISTORY, false)){
			$store_id = $this->currentuser->user_id;
			$store_id_filter = " AND ll.entered_by = '$store_id' ";
		}
		
	    $historystr = $this->currentorg->getConfigurationValue(CONF_CLIENT_PURCHASE_HISTORY_FIELDS, true);
  
		if( $this->currentorg->getConfigurationValue(ENABLE_POINTS_ON_BILL_FOR_PURCHASE_HISTORY, true) ){
			
			$show_points = ' `ll`.`points`, ';
		}
		if( $this->currentorg->getConfigurationValue(SHOW_REDEEMED_EXPIRED_POINTS_FOR_PURCHASE_HISTORY, false) ){
			
			$show_redeemed = ' `ll`.`redeemed`, `ll`.`expired`, ';
		}
		if( $this->currentorg->getConfigurationValue(SHOW_BILL_NOTES_FOR_PURCHASE_HISTORY, false) ){
			
			$show_bill_notes = ' `ll`.`notes`, ';
		}		
		if($ignore_store_id_filter)
			$store_id_filter = "";
		
		$this->loyaltyModel->getQueryResult();
		$result = $this->loyaltyModel->getCustomerBillsForPurchaseHistory( 
					$show_points, $show_redeemed, $user_id, $store_id_filter, $show_bill_notes );
		
	    //generating the fields configured in the purchase history for the user  
	    if(sizeof($historystr) || Util::isBCBGorg($org_id) )
	    {
	      $fieldString = trim($historystr, '[]');
	     	 
	      $this->loyaltyModel->getQueryResult();
	      $itemRes = $this->loyaltyModel->getInventoryDetailsForPurchaseHistory( $fieldString, $user_id, $store_id_filter );
	
		global $logger;
		$logger->debug( 'hello_pv'.print_r( $itemRes, true ). ' store '.$this->currentuser->username );
	      $indexArray = array();
	      foreach($itemRes as $row){
	        $indexArray[$row['bill_number']] = $row;
	      }
	      
	      //collating the two results
	      foreach($result as &$row){
	          $bill = $row['bill_number'];
	          if(isset($indexArray[$bill])){
	            $row['item_attributes'] = $indexArray[$bill]['attribute_name'];
	            $row['attribute_values'] = $indexArray[$bill]['attribute_values'];
	          }else{
	            $row['item_attributes'] = ' --';
	            $row['attribute_values'] = ' --';
	          }
	      }
	    }
	    return $result;
	}
	
	function getInventoryDetailsForBill( $bill_id ){
		
		$this->loyaltyModel->getQueryResult();
		return $this->loyaltyModel->getInventoryDetailsForBill( $bill_id );
	}

	function getPurchaseHistoryForMobileApi($user_id, $ignore_store_id_filter = false){
		
		$org_id = $this->currentorg->org_id;

		$show_redeemed = '';
		$show_points = '';
		$store_id_filter = '';
		
		if($this->currentorg->getConfigurationValue(CONF_CLIENT_ONLY_STORE_PURCHASE_HISTORY, false)){
			$store_id = $this->currentuser->user_id;
			$store_id_filter = " AND ll.entered_by = '$store_id' ";
		}
		
	    $historystr = $this->currentorg->getConfigurationValue(CONF_CLIENT_PURCHASE_HISTORY_FIELDS, true);
  
		if( $this->currentorg->getConfigurationValue(ENABLE_POINTS_ON_BILL_FOR_PURCHASE_HISTORY, true) ){
			
			$show_points = ' `ll`.`points`, ';
		}
		if( $this->currentorg->getConfigurationValue(SHOW_REDEEMED_EXPIRED_POINTS_FOR_PURCHASE_HISTORY, false) ){
			
			$show_redeemed = ' `ll`.`redeemed`, `ll`.`expired`, ';
		}
		if($ignore_store_id_filter)
			$store_id_filter = "";
		
		$this->loyaltyModel->getQueryResult();
		$result = $this->loyaltyModel->getCustomerBillsForPurchaseHistoryForMobile( $show_points, $show_redeemed, $user_id, $store_id_filter );
		
	    //generating the fields configured in the purchase history for the user  
	    if(sizeof($historystr))
	    {
	      //collating the two results
	      foreach($result as &$row){
		  	  $id = $row['id'];

			  $row['line_items'] = $this->db->query ("SELECT item_code, qty, rate FROM loyalty_bill_lineitems WHERE org_id = $org_id AND loyalty_log_id = $id");

	          unset($row['id']);
	          unset($row['item_code']);
	          unset($row['item_desc']);
	          unset($row['item_price']);
	      }
	    }
	    return $result;
	}
	
	function getPurchaseHistoryForJavaClient($user_id, $ignore_store_id_filter = false){
		
		$org_id = $this->currentorg->org_id;
		
		$show_redeemed = '';
		$show_points = '';
		$store_id_filter = '';
		
		if($this->currentorg->getConfigurationValue(CONF_CLIENT_ONLY_STORE_PURCHASE_HISTORY, false)){
			$store_id = $this->currentuser->user_id;
			$store_id_filter = " AND ll.entered_by = '$store_id' ";
		}
  
		if( $this->currentorg->getConfigurationValue(ENABLE_POINTS_ON_BILL_FOR_PURCHASE_HISTORY, true) ){
			
			$show_points = ' `ll`.`points`, ';
		}
		if( $this->currentorg->getConfigurationValue(SHOW_REDEEMED_EXPIRED_POINTS_FOR_PURCHASE_HISTORY, false) ){
			
			$show_redeemed = ' `ll`.`redeemed`, `ll`.`expired`, ';
		}
		if($ignore_store_id_filter)
			$store_id_filter = "";
			
		
		$this->loyaltyModel->getQueryResult();
		$result = $this->loyaltyModel->getCustomerBillsForPurchaseHistoryForJavaClient($show_points, $show_redeemed, $user_id, $store_id_filter);
		
		$bills = array();
		foreach($result as $row){
			$bill_number = $row['bill_number'];
			if(!isset($bills[$bill_number]))
				$bills[$bill_number] = array();
			$line_item = $row['item_code'].' \t '.$row['item_description'].' \t '.$row['item_rate'].' \t '.$row['item_quantity'].' \t '.$row['item_amount'];
			array_push($bills[$bill_number], $line_item);
		}
		
		$bill_numbers = array_keys($bills);
		$customer_purchase_history = array();
		foreach($bill_numbers as $key)
			array_push($customer_purchase_history, $key.' \n '.implode(' \n ', $bills[$key]));
		
		$customer_purchase_history = implode('\n', $customer_purchase_history);
		
		return $customer_purchase_history;
		
	}
	
	function getVouchersOfUserForJavaClient($user_id){
		
		global $currentorg;
		$user = UserProfile::getById($user_id);
		
		$org_id = $currentorg->org_id;
		$vouchers_list = Voucher::getVouchers($org_id, $user_id, true);
		
		
		$vouchers = array();
		
		$count = 0;
		foreach($vouchers_list as $voucher){
			$v = Voucher::getVoucherFromCode($voucher['voucher_code'], $org_id);
			if($v){
				$redeemable = $v->isRedeemable($user, $currentorg);
				if($redeemable == VOUCHER_ERR_SUCCESS && $count < 5){
					$count++;
					array_push($vouchers, array('voucher' => array('display_string' => $voucher['description'], 'voucher_code' => $voucher['voucher_code'])));
				}
			}
				
		}
		
		return $vouchers;
	}
	
	function getVouchersOfUser($user_id){
		
		global $currentorg;
		$user = UserProfile::getById($user_id);
		
		$org_id = $currentorg->org_id;
		$vouchers_list = Voucher::getVouchers($org_id, $user_id, true);
		
		
		$vouchers = array();
		
		foreach($vouchers_list as $voucher){
			$v = Voucher::getVoucherFromCode($voucher['voucher_code'], $org_id);
			if($v){
				$redeemable = $v->isRedeemable($user, $currentorg);
				if($redeemable == VOUCHER_ERR_SUCCESS){
					array_push($vouchers, array('voucher_code' => $voucher['voucher_code']));
				}
			}
				
		}
		
		return $vouchers;
	}
	
	function sendReturnBillEmail(UserProfile $user, $bill_number, $bill_amount, $returned_items)
	{
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		$user_id = $user->user_id;
		
		$subject = "Return Bill Request : From ".$user->getNiceString().".Bill Number : $bill_number  Store : ".$this->currentuser->username;
		
		$eup = new ExtendedUserProfile($user, $this->currentorg);
		$client_info = "Customer Name : ".$eup->getName()."<br>";
		$client_info .= "Customer Mobile : ".$eup->getMobile()."<br>";
		$client_info .= "Email Id : ".$eup->getEmail()."<br>";
		$loyalty_details = $this->getLoyaltyDetailsForUserID($user_id);
		$client_info .= "External Id : ".$loyalty_details['external_id']."<br>";
		$client_info .= "Loyalty Id : ".$loyalty_details['id']."<br> User Id : $user_id <br>";
		
		$msg = "Return Bill Request from !nTouch Client : Bill Number $bill_number "
			." <br> Store : ".$this->currentuser->username
			." <br> Store Contact : ".$this->currentuser->mobile
			." <br> Organization : ".$this->currentorg->name;
		$msg .= "<br>    $client_info";

		//Add the return bill details
		$msg .= "<br> Return Bill Details ";
		$msg .= "<br> Bill Number : $bill_number ";
		$msg .= "<br> Bill Amount : $bill_amount ";
		foreach($returned_items as $li)
		{
			$item_code = $li['item_code'];
			$qty = $li['qty'] > 0 ? $li['qty'] : 0;
			$rate = $li['rate']> 0 ? $li['rate'] : 0;
			$msg .= "<br> LineItem : ItemCode $item_code, Qty : $qty, Rate : $rate ";
		}
		
		$recepient = $this->getConfigurationValue(MAIL_IDS_FOR_RETURN_BILL_REQUEST, false);
		
		if($recepient){			
			$recepient = explode(',', $recepient);
		}else
			return true;
		
		Util::sendEmail($recepient, $subject, $msg, $org_id, '', 0,
				-1 , array(), 0, true, $user_id,  $user_id, 'TRANSACTION');
	}
	
	function addReturnBill($user_id, $bill_number, $credit_note, &$amount, 
				&$points, $returned_time, &$loyalty_log_id = 0, $returned_items = array(), 
				$return_type = 'FULL', $deliveryStatus, $notes = '', &$return_bill_id = -1,
						   &$outlier_status = TRANS_OUTLIER_STATUS_NORMAL, $bill_date = null,$nonLoyal=false){

		global $currentorg;
		$input_return_type = $return_type;
		/* This is because the payment_mode_details tables has ref_type in index */
		$paymentModeRefType = 'REGULAR';

		$cm = new ConfigManager($this->org_id);
		$allowReturn =  $cm->getKeyValueForOrg( "CONF_LOYALTY_IS_RETURN_TRANSACTION_SUPPORTED", $this->org_id);
		$allowReturnLineitem =  $cm->getKeyValueForOrg( "CONF_LOYALTY_IS_RETURN_TRANSACTION_LINE_ITEM_SUPPORTED", $this->org_id);
		$allowReturnAmount =  $cm->getKeyValueForOrg( "CONF_LOYALTY_IS_RETURN_TRANSACTION_AMOUNT_SUPPORTED", $this->org_id);
		$allowReturnFull =  $cm->getKeyValueForOrg( "CONF_LOYALTY_IS_RETURN_TRANSACTION_FULL_SUPPORTED", $this->org_id);
		$allowPointsReturn =  $cm->getKeyValueForOrg( "CONF_POINTS_RETURN_ENABLED", $this->org_id);
		
// 		$allowReturn =  $this->currentorg->getConfigurationValue( "CONF_LOYALTY_IS_RETURN_TRANSACTION_SUPPORTED");
// 		$allowReturnLineitem =  $this->currentorg->getConfigurationValue( "CONF_LOYALTY_IS_RETURN_TRANSACTION_LINE_ITEM_SUPPORTED");
// 		$allowReturnAmount =  $this->currentorg->getConfigurationValue( "CONF_LOYALTY_IS_RETURN_TRANSACTION_AMOUNT_SUPPORTED");
// 		$allowReturnFull =  $this->currentorg->getConfigurationValue( "CONF_LOYALTY_IS_RETURN_TRANSACTION_FULL_SUPPORTED");
// 		$allowPointsReturn =  $this->currentorg->getConfigurationValue( "CONF_POINTS_RETURN_ENABLED");
		
		$allowReturn = isset($allowReturn) ? $allowReturn : 1;
		$allowReturnLineitem = isset($allowReturnLineitem) ? $allowReturnLineitem : 1;
		$allowReturnAmount = isset($allowReturnAmount) ? $allowReturnAmount : 1;
		$allowReturnFull = isset($allowReturnFull) ? $allowReturnFull : 1;
		$allowPointsReturn = isset($allowPointsReturn) ? $allowPointsReturn : 1;
		$allowReturnOfNonExistingTxn = 1;
		
		if(!$allowReturn)
		{
			$this->logger->debug("Return transaction is not supported");
			throw new Exception("ERR_RETURN_TRANSACTION_NOT_SUPPORTED");
		}
		
		else if(!$allowReturnLineitem && $return_type == TYPE_RETURN_BILL_LINE_ITEM)
		{
			$this->logger->debug("Return Line item transaction is not supported");
			throw new Exception("ERR_RETURN_LINEITEM_TRANSACTION_NOT_SUPPORTED");
		}

		else if(!$allowReturnAmount && $return_type == TYPE_RETURN_BILL_AMOUNT)
		{
			$this->logger->debug("Return amount transaction is not supported");
			throw new Exception("ERR_RETURN_AMOUNT_TRANSACTION_NOT_SUPPORTED");
		}
		
		else if(!$allowReturnFull && $return_type == TYPE_RETURN_BILL_FULL)
		{
			$this->logger->debug("Return full transaction is not supported");
			throw new Exception("ERR_RETURN_FULL_TRANSACTION_NOT_SUPPORTED");
		}
		
		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$safe_credit_note = Util::mysqlEscapeString($credit_note);
		$safe_notes = Util::mysqlEscapeString($notes);
		$safe_bill_date = $bill_date ? Util::getMysqlDateTime($bill_date) : null;
		$safeDeliveryStatus = Util::mysqlEscapeString($deliveryStatus);
 
		$allowWithoutTransactioNumber = $this->currentorg->getConfigurationValue( "CONF_LOYALTY_IS_RETURN_BILL_NUMBER_REQUIRED");
		$allowWithoutTransactioNumber = isset($allowWithoutTransactioNumber) ? $allowWithoutTransactioNumber : 1;
		#$allowWithoutTransactioNumber = 1;
		if(!$allowWithoutTransactioNumber && !$safe_bill_number)
		{
			$this->logger->debug("Return transaction number is empty");
			throw new Exception("ERR_RETURN_TRANSACTION_NUMBER_EMPTY");
		}
					
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;		
		
// 		if(strlen($safe_bill_number) == 0 && $this->getConfiguration(CONF_LOYALTY_IS_RETURN_BILL_NUMBER_REQUIRED))
// 		{
// 			$this->logger->error("bill number is not valid: ERR_LOYALTY_INVALID_BILL_NUMBER");
// 			throw new Exception("ERR_LOYALTY_INVALID_BILL_NUMBER");
// 		}
		
		//Check if that bill number has been already returned when bill_number is not empty
		$returned_type = null;
		$return_bill_count = 0;$returned_bills = array();
		if($safe_bill_number)
		{
			$sql = "
			SELECT * FROM `loyalty_log`
			WHERE org_id = $org_id AND user_id = $user_id
			AND bill_number = '$safe_bill_number' ";
			
			if($safe_bill_date > 1990)
			{
				$this->logger->debug("Date is also passed to filter");
				$sql .= " AND `date` >= '".date('Y-m-d 00:00:00', strtotime($safe_bill_date)) ."' AND `date` <= '".date('Y-m-d 23:59:59', strtotime($safe_bill_date))."'";
			}
			
			$sql .= " ORDER BY date DESC";
			$bills = $this->db->query($sql);
			
			// if bills is not found after passing the bill number and bill is mandatory for return
			if(count($bills) == 0 && !$allowReturnOfNonExistingTxn && $safe_bill_number)
			{
				$this->logger->error("Bill not found: ERR_LOYALTY_INVALID_BILL_NUMBER");
				throw new Exception('ERR_LOYALTY_BILL_NUMBER_NOT_EXIST');
			}
			
			
			//There should be atleast one bill where the amount being returned is less than the bill amount
			$found = false;
			$return_time_invalid = false;
			$loyalty_log = null;
			if($bills)
			{
				foreach($bills as $b){
					if($b['bill_amount'] >= $amount || $input_return_type == TYPE_RETURN_BILL_FULL){
			
						$addbill_time = $b['date'];
						$addbill_timestamp = Util::deserializeFrom8601($addbill_time);
						$returnbill_timestamp = Util::deserializeFrom8601($returned_time);
						if($addbill_timestamp > $returnbill_timestamp)
						{
							$this->logger->debug("Returned time is less than add bill time: Returned_time: $returned_time, AddedTime: $addbill_timestamp");
							$return_time_invalid = true;
							continue;
						}
						$return_time_invalid = false;
						$found = true;
						$loyalty_log_id = $b['id'];
						$loyalty_log = $b;
						break;
					}
				}
			
				if($return_time_invalid)
				{
					$this->logger->error("Returned Time is less than bill time: ERR_LOYALTY_INVALID_RETURN_BILL_TIME");
					throw new Exception('ERR_LOYALTY_INVALID_RETURN_BILL_TIME');
				}
			
				//if there is not such bill, reject the bill
				if(!$found)
				{
					$this->logger->error("Invalid Bill Amount: ERR_LOYALTY_INVALID_BILL_AMOUNT");
					throw new Exception("ERR_LOYALTY_INVALID_BILL_AMOUNT");
				}
			}
				
			if($loyalty_log_id > 0 )
				$sql = "SELECT * FROM `returned_bills` WHERE org_id = $org_id AND user_id = $user_id AND `loyalty_log_id` = $loyalty_log_id";
			else
				$sql = "SELECT * FROM `returned_bills` WHERE org_id = $org_id AND user_id = $user_id AND `bill_number` = '$safe_bill_number'";
			$returned_bills = $this->db->query($sql);
			
			$return_bill_count = count($returned_bills);
			if($return_bill_count > 0)
			{
				$returned_type = $returned_bills[ $return_bill_count - 1 ]['type'];
				$input_return_type = $return_type;
				if($input_return_type === TYPE_RETURN_BILL_AMOUNT)
				{
					$this->logger->error("Bill is already returned and".
							" trying to return bill with type: $return_type");
					if($returned_type === TYPE_RETURN_BILL_LINE_ITEM)
						throw new Exception("ERR_ALREADY_RETURNED_AND_NEW_TYPE_AMOUNT");
					else if($returned_type === TYPE_RETURN_BILL_FULL)
						throw new Exception("ERR_ALREADY_RETURNED_AND_OLD_TYPE_FULL");
				}
				else if($input_return_type === TYPE_RETURN_BILL_FULL)
				{
					$this->logger->error("Bill is already returned and".
							" trying to return bill with type: $return_type");
					throw new Exception("ERR_ALREADY_RETURNED_AND_NEW_TYPE_FULL");
				}else if($input_return_type === TYPE_RETURN_BILL_LINE_ITEM)
				{
					$this->logger->error("Bill is already returned and".
							" trying to return bill with type: $return_type");
					if($returned_type === TYPE_RETURN_BILL_AMOUNT)
						throw new Exception("ERR_ALREADY_RETURNED_AND_NEW_TYPE_LINEITEM_OLD_AMOUNT");
				}
				
				if($returned_type === TYPE_RETURN_BILL_FULL)
				{
					$this->logger->error("Bill is already returned  with type: $returned_type");
					throw new Exception("ERR_ALREADY_RETURNED_AND_OLD_TYPE_FULL");
				}
			}
		}
		$total_returned_amount = 0;
		foreach($returned_bills as $return_bill )
		{
			$total_returned_amount += $return_bill['amount'];
		}
		$this->logger->debug("total returned amount: $total_returned_amount");
		//Check if there is a bill with amount more than the amount being returned
		$new_return_items = array();
		$new_return_lineitem_pe_data = array();
		$pe_lineitems = array();
		
		// to set the return line items
		if((count($returned_items > 0) || $return_type === TYPE_RETURN_BILL_FULL) && $loyalty_log)
		{
			//Populating lineitems from db.
			$this->logger->debug("fetching lineitems from db the loyalty_log_id: $loyalty_log_id");
			$sql = " SELECT 
					id, UPPER(item_code) as item_code, 
					qty, rate, discount_value, value, amount, serial 
					FROM loyalty_bill_lineitems 
						WHERE org_id = $org_id 
						AND loyalty_log_id = $loyalty_log_id 
						AND user_id = $user_id";
			
			//if return type is full, it should fetch all lineitems from db, 
			//and insert into return return_bill_lineitems
			if($return_type === TYPE_RETURN_BILL_FULL )
			{
				$this->logger->debug("ReturnType is: $return_type, fetching lineitems from db");
				$bill_lineitems = $this->db->query_hash($sql, "id", array("id", "item_code", "qty", "rate", "discount_value", "value", "amount", "serial"));
				$new_return_items = $bill_lineitems;				
				$new_return_lineitem_pe_data = array_values($bill_lineitems);
			}
			else if($return_type === TYPE_RETURN_BILL_AMOUNT )
			{
				$this->logger->debug("ReturnType is: $return_type, Ignoring passed lineitems, and calling return bill amount");
				$new_return_items = array();
				$new_return_lineitem_pe_data = array();
			}
			else
			{
				$merged_regular_items = $bill_lineitems = $this->db->query_hash($sql, "item_code", array("id", "item_code", "qty", "rate", "amount", "discount_value"));
				
				//merging returned items.
				$merged_returned_items = array();
				foreach ($returned_items as $key=>$item)
				{
					$temp_item_code = strtoupper($item['item_code']);
					if(!$item['discount_value'])
					{
						// any of the fields on date os passed
						if( $item['amount'] || $item['value'] || $item['qty'] || $item['rate'])
							$item['discount_value'] = $returned_items[$key]['discount_value'] = 0;

						// default the value as 0 if all values are zero/notset
						else
							$item['discount_value'] = $returned_items[$key]['discount_value'] = $merged_regular_items[$temp_item_code]["discount_value"];
					}
					if(!$item['qty'])
						$item['qty'] = $returned_items[$key]['qty'] = $merged_regular_items[$temp_item_code]['qty'];
					if(!$item['rate'])
						$item['rate'] = $returned_items[$key]['rate'] = $merged_regular_items[$temp_item_code]['rate'];
					if(!$item['value'])
						$item['value'] = $returned_items[$key]['value'] = $item['qty'] * $item['rate'];
					if(!$item['amount'])
						$item['amount'] = $returned_items[$key]['amount'] = $item['value'] - $item['discount_value'];
						
					if(isset($merged_returned_items[$temp_item_code]))
					{
						$this->logger->debug("item_code: ".$item['item_code']." repeated, ".
								"merging (".$merged_returned_items[$temp_item_code]['qty']."+".$item['qty'].")");
						// @@@@ uncommented by cj @@@ 
						$merged_returned_items[$temp_item_code]['qty'] += $item['qty'];
						$merged_returned_items[$temp_item_code]['amount'] += $item['amount'];
						// TODO : whats is happening here?
						//if( isset($merged_returned_items[$temp_item_code]['qty'] ) )
						//	$merged_returned_items[$temp_item_code] = array( $merged_returned_items[$temp_item_code] );
						//$merged_returned_items[$temp_item_code][] = $item; 
					}
					else
					{
						$merged_returned_items[$temp_item_code] = $item;
					}
				}
				
				$return_bill_lineitem_sql = "SELECT rbl.lbl_id, upper(rbl.item_code) as item_code, 
							SUM(rbl.qty) AS qty, SUM(rbl.amount) as amount
							FROM returned_bills_lineitems AS rbl
							WHERE rbl.org_id = $org_id
							AND rbl.loyalty_log_id = $loyalty_log_id
							AND rbl.user_id = $user_id
							GROUP BY rbl.lbl_id";
					
				$returned_lbl_rows = $this->db->query_hash($return_bill_lineitem_sql, "lbl_id", array("lbl_id", "item_code", "qty", "amount"));
				#print str_replace("\t"," ", $return_bill_lineitem_sql);
				#print_r($returned_lbl_rows);
				$this->logger->debug("ReturnType is: $return_type, fetching lineitems from db and rewriting rate and id");
				
				#$bill_lineitems = $this->db->query($sql);

				$merged_returned_items = array();
				foreach ($returned_lbl_rows as $item)
				{
					$temp_item_code = strtoupper($item['item_code']);
					if(count($returned_lbl_rows) > 0 && isset($returned_lbl_rows[$item['id']]))
					{
						$this->logger->debug("deducting qty from returned qty");
						$item['qty'] = $item['qty'] - $returned_lbl_rows[$item['id']]['qty']; 
					}
					if(isset($merged_returned_items[$temp_item_code]))
					{
						$this->logger->debug("item_code: ".$item['item_code']." repeated, ".
								"merging (".$merged_returned_items[$temp_item_code]['qty']."+".$item['qty'].")");
						// @@@ uncommented by cj @@@
						$merged_returned_items[$temp_item_code]['qty'] += $item['qty'];
						$merged_returned_items[$temp_item_code]['amount'] += $item['amount'];
						//if( isset($merged_returned_items[$temp_item_code]['qty'] ) )
						//	$merged_regular_items[$temp_item_code] = array( $merged_regular_items[$temp_item_code] );
						//$merged_regular_items[$temp_item_code][] = $item;
					}
					else
					{
						$merged_returned_items[$temp_item_code] = $item;
					}
				}

 				foreach($returned_items as $item)
 				{
 					$temp_item_code = strtoupper($item["item_code"]);
 					if(isset($merged_returned_items[$temp_item_code]))
 					{
 						$merged_returned_items[$temp_item_code]['qty'] += $item['qty'];
 						$merged_returned_items[$temp_item_code]['amount'] += $item['amount'];
 					}
 					else
 						$merged_returned_items[$temp_item_code] = $item;
					
 				}
 					
 				//$bill_lineitems = $merged_regular_items;
				// @@@ commented by cj
				#$returned_items = $merged_returned_items;
				$new_derived_amount = 0;

				foreach ($returned_items as $temp_item_code1 => $item)
				{
					// if some data is not passed; get that from exist
					$temp_item_code = $item['item_code'];
					$temp_item_code_in_upper = strtoupper($temp_item_code);
					$temp_lineitem = $bill_lineitems[$temp_item_code_in_upper];
					if(!isset($returned_items[$temp_item_code1]))
						$returned_items[$temp_item_code1] = array();
					$returned_items[$temp_item_code1]["id"] = $temp_lineitem["id"];
					$returned_items[$temp_item_code1]["qty"] = $item["qty"] ? $item["qty"] : $temp_lineitem["qty"];
					$returned_items[$temp_item_code1]["rate"] = $item["rate"] ? $item["rate"] : $temp_lineitem["rate"];

					if(!$item["value"])
					{
						if($returned_items[$temp_item_code1]["rate"] && $returned_items[$temp_item_code1]["qty"])
							$returned_items[$temp_item_code1]["value"] = $returned_items[$temp_item_code1]["rate"] * $returned_items[$temp_item_code1]["qty"];
						else
							$returned_items[$temp_item_code1]["value"] = $item["value"] ? $item["value"] : $temp_lineitem["value"];
					}

					if(!$item["amount"])
					{
						if($returned_items[$temp_item_code1]["value"])
							$returned_items[$temp_item_code1]["amount"] = $returned_items[$temp_item_code1]["value"] - $item["discount_value"];
						else
							$returned_items[$temp_item_code1]["amount"] = $item["amount"] ? $item["amount"] : $temp_lineitem["amount"];
					}

					//print "\n".$merged_regular_items[$temp_item_code_in_upper]['amount'] ."x". $merged_returned_items[$temp_item_code_in_upper]['amount']."\n";
					if($merged_regular_items[$temp_item_code_in_upper]['qty'] < $merged_returned_items[$temp_item_code_in_upper]['qty'])
					{
						$this->logger->error("returned qty is more than purchased qty for item_code: $temp_item_code
								( Pruchased Qty: ". $temp_lineitem['qty'] .", Returned Qty: ".$item['qty']." )");
								//ERR_RETURNED_ITEM_QTY_INVALID is defined in Errors.php
						throw new Exception(ERR_RETURNED_ITEM_QTY_INVALID);
					}
					
					if($this->compareWithPrecision($merged_regular_items[$temp_item_code_in_upper]['amount'],$merged_returned_items[$temp_item_code_in_upper]['amount']))
					{
						$this->logger->error("returned qty is more than purchased qty for item_code: $temp_item_code
								( Pruchased Amunt: ". $merged_regular_items[$temp_item_code_in_upper]['amount'] .", Returned Qty: ".$merged_returned_items[$temp_item_code_in_upper]['amount']." )");
										//ERR_RETURNED_ITEM_QTY_INVALID is defined in Errors.php
						throw new Exception(ERR_RETURNED_ITEM_AMOUNT_INVALID);
					}
					
					array_push($new_return_lineitem_pe_data,
						array(
						"id" => $temp_lineitem['id'],
						"qty" => $returned_items[$temp_item_code1]["qty"],
						"rate" => $returned_items[$temp_item_code1]["rate"],
					));

						
					/*
					//$temp_item_code = $item['item_code'];
					if(isset($bill_lineitems[$temp_item_code]))
					{
						if(count($bill_lineitems[$temp_item_code]) > 0 && !isset($bill_lineitems[$temp_item_code]['qty']))
						{
							//totaling qty of returned lineitems
							$total_qty = 0;
							if(isset($item['qty']))
							{
								$total_qty = $item['qty'];
							}
							else
							{
								foreach ($item as $temp_item)
								{
									$total_qty += intval($temp_item['qty']); 
								}
							}
							$temp_lineitems = $bill_lineitems[$temp_item_code];
							//sort $temp_lineitems array by qty
							$temp_count =count($temp_lineitems); 
						    for ($i=0; $i<$temp_count; $i++) {
						        for ($j=0; $j<$temp_count-1-$i; $j++) {
						            if ($temp_lineitems[$j+1]['qty'] > $temp_lineitems[$j]['qty']) {
						            	$tmp_item = $temp_lineitems[$j+1];
						            	$temp_lineitems[$j+1] = $temp_lineitems[$j];
						            	$temp_lineitems[$j] = $tmp_item;
						            }
						        }
							}

							//extracting qty if two or more line item has same item code. 
							foreach($temp_lineitems as $temp_lineitem)
							{	
								if($total_qty <= 0)
									break;
								$total_qty = $total_qty - $temp_lineitem['qty'];
								if( $total_qty <= 0 )
								{
									$temp_lineitem['qty'] = $total_qty + $temp_lineitem['qty']; 
								}
								$this->logger->debug("adding (".$temp_lineitem['qty']." x ". $temp_lineitem['rate']. ") into derived amount");
								$new_derived_amount += ( intval($temp_lineitem['qty']) * doubleval($temp_lineitem['rate']) );
								//if there are multiple lineitems with same item code, it will consider that only for once.
								if(!in_array( $temp_lineitem['id'], $new_return_lineitem_pe_data))
								{
									array_push($new_return_items, $temp_lineitem);
									array_push($new_return_lineitem_pe_data,
									array(
										"id" => $temp_lineitem['id'],
										"qty" => $temp_lineitem['qty']
										));
								}
							}
							if($total_qty > 0)
							{
								$this->logger->error("returned qty is than ".
										"purchased qty by $total_qty for item_code: $temp_item_code");
										//ERR_RETURNED_ITEM_QTY_INVALID is defined in Errors.php
								throw new Exception(ERR_RETURNED_ITEM_QTY_INVALID);
							}
						}
						else
						{
							$temp_lineitem = $bill_lineitems[$temp_item_code];
							
							//validates qty.
							if( $item['qty'] > $temp_lineitem['qty'] )
							{
								$this->logger->error("returned qty is more than purchased qty for item_code: $temp_item_code
										( Pruchased Qty: ". $temp_lineitem['qty'] .", Returned Qty: ".$item['qty']." )");
								//ERR_RETURNED_ITEM_QTY_INVALID is defined in Errors.php
								throw new Exception(ERR_RETURNED_ITEM_QTY_INVALID);
							}
							$temp_lineitem['qty'] = $item['qty'];
							$this->logger->debug("adding (".$item['qty']." x ". $item['rate']. ") into derived amount");
							$new_derived_amount += ( intval($item['qty']) * doubleval($item['rate']) );
							//if there are multiple lineitems with same item code, it will consider that only for once.
							if(!in_array( $temp_lineitem['id'], $new_return_lineitem_pe_data))
							{
								array_push($new_return_items, $temp_lineitem);
								array_push($new_return_lineitem_pe_data, 
										array(
												"id" => $temp_lineitem['id'],
												"qty" => $temp_lineitem['qty'],
												"rate" => $temp_lineitem['rate'],
												
											 ));
							}
						}
					}
					else
					{
						$this->logger->error(
								"item_code: $temp_item_code not found in
								loyalty_bill_lineiteam for loyalty_log_id: $loyalty_log_id");
						array_push($new_return_items, array("item_code" => $item['item_code'], 
													"qty" => $item['qty'],
													
													"rate" => $item['rate'],
													"value" => $item['value'],
													"discount_value" => $item['discount_value'],
													"amount" => $item['amount'],
													
								));
					}*/
				}
				$new_return_items = $returned_items;
				
				// consider the amount as the line item sum
				if($returned_items)
				{
					$this->logger->debug("Recalculating the return amount");
					$amount = 0 ; 
					foreach($returned_items as $key=>$item)
						$amount += $item["amount"];
				}
			}
		}
		
		// if nor bill is available, use the return bill as such
		if(!$loyalty_log_id)
		{
			$new_return_items = $returned_items;
			if($return_type == TYPE_RETURN_BILL_AMOUNT)
				$new_return_items = null;
		}
		
		$this->logger->debug("Lineitems that are going to be returned : ".
				print_r($new_return_lineitem_pe_data, true));
		
		// TODO: this seems to be useless type change; need to remove  - cj
		if( $return_type !== TYPE_RETURN_BILL_AMOUNT && $loyalty_log_id
				&& count($new_return_items) === 0 ) //$new_return_lineitem_pe_data
		{
			$this->logger->error("no Lineitem is passed for return type: $return_type,
					changing return type from $return_type to ".TYPE_RETURN_BILL_AMOUNT);
			$return_type = TYPE_RETURN_BILL_AMOUNT;
		}

		if($input_return_type == TYPE_RETURN_BILL_FULL && $loyalty_log)
		{
			$this->logger->debug("return_type is $return_type,".
					" overriding passed amount: $amount".
					" with new derived amount: $loyalty_log[bill_amount]");
			$amount = $loyalty_log["bill_amount"] + 0;
		}

		else if($return_type !== TYPE_RETURN_BILL_LINE_ITEM)
		{
			$this->logger->debug("return_type is $return_type,".
					" overriding passed amount: $amount".
					" with new derived amount: $new_derived_amount");
			$amount = $amount ? $amount : intval($new_derived_amount);
		}
		
		if($return_type === TYPE_RETURN_BILL_AMOUNT && $loyalty_log)
		{
			if($returned_type != null && $returned_type !== TYPE_RETURN_BILL_AMOUNT)
			{
				$this->logger->error("bill is already returned with type $returned_type, 
									you can't return amount");
				throw new Exception("ERR_ALREADY_RETURNED_AND_NEW_TYPE_AMOUNT");
			}
			
			if( ($total_returned_amount + $amount) > $loyalty_log['bill_amount'])
			{
				$this->logger->error("Amount: $amount can not be returned,"
						."$total_returned_amount is already returned from total Amount: "
						.$loyalty_log['bill_amount']);
				throw new Exception("ERR_ALREADY_RETURNED_AND_MORE_AMOUNT");
			}
		}
		$points = 0;
		$loyalty_log_id = intval($loyalty_log_id);

		$sql = "INSERT INTO `returned_bills`(
						org_id, user_id, bill_number,
						credit_note, amount, points,
						store_id, returned_on, `loyalty_log_id`,
						`type`, `notes`, `added_on`)
					VALUES ($org_id, $user_id, '$safe_bill_number',
						'$safe_credit_note', '$amount', '$points',
						$store_id, '$returned_time', $loyalty_log_id,
						'$return_type', '$safe_notes', NOW()) ";
		
		$return_bill_id = $this->db->insert($sql);
		 
		if (isset($return_bill_id) && $return_bill_id <= 0 ) {
			$this->logger->error("Insert query failed, throwing Generic exception");
			$this -> rollbackTransaction();
			throw new Exception("ERR_LOYALTY_BILL_ADDITION_FAILED");
		} else {
			if (! empty($safeDeliveryStatus)) {
				// Continue to insert into the `transaction_delivery_status` table
				$statusSql = "INSERT INTO `user_management`.`transaction_delivery_status` " . 
								"SET `transaction_id` = " . $return_bill_id . ", " . 
										"`transaction_type` = 'RETURN', " . 
										"`delivery_status` = '" . $safeDeliveryStatus . "', " . 
										"`updated_by` = " . $store_id . " " .  
							 "ON DUPLICATE KEY UPDATE " . 
							 			"`delivery_status` = '" . $safeDeliveryStatus . "', " . 
										"`updated_by` = " . $store_id;

				// Using Dbase -> update() instead of insert() to be able to run ON DUPLICATE KEY UPDATE
				$newDeliveryStatusId = $this -> db -> update($statusSql);
				$this -> logger -> debug('Transaction Return :: Delivery-status ID: ' . $newDeliveryStatusId);

				if (isset($newDeliveryStatusId) && $newDeliveryStatusId > 0) {
					// Continue to insert into the `transaction_delivery_status_changelog` table
					$statusLogSql = "INSERT INTO `user_management`.`transaction_delivery_status_changelog` " . 
										"SET `transaction_id` = " . $return_bill_id . ", " . 
											"`transaction_type` = 'RETURN', " . 
											"`delivery_status` = '" . $safeDeliveryStatus . "', " . 
											"`updated_by` = " . $store_id;
					$newDeliveryStatusLogId = $this -> db -> insert($statusLogSql);
					$this -> logger -> debug('Transaction Return :: Delivery-status-changelog ID: ' . $newDeliveryStatusLogId);
				} 
			} 
		}
		$lineitem_insert_values = array();
		
		if(count($new_return_items) > 0){
				
			//insert the lineitems
			foreach($new_return_items as $li)
			{
				$item_code = $li['item_code'];
				 
				$qty = $li['qty'] > 0 ? $li['qty'] : 0;
				$rate = $li['rate']> 0 ? $li['rate'] : 0;

 				$value = $li['value']> 0 ? $li['value'] : $rate * $qty;
 				$discount_value = $li['discount_value']> 0 ? $li['discount_value'] : 0;
 				$lbl_amount = $li['amount']> 0 ? $li['amount'] : $value - $discount_value;
 				$serial = $li['serial']> 0 ? $li['serial'] : 0;
				
//  				$value = 0; //dummy
//  				$discount_value = 0;//dummy
//  				$lbl_amount = 0;//dummy
//  				$serial = 0; //dummy
				$returned_points = 0;
				
				if(isset($li['id']))
				{
					$lbl_id = $li['id'];
				}
				else
				{
					$lbl_id = 'NULL';
				}
		
				$values_str = "($return_bill_id, $org_id, $user_id,
									'$serial', '$item_code', '$rate',
									'$qty', '$value', '$discount_value',
									'$lbl_amount', '$lbl_id', $loyalty_log_id, 0)";
		
				array_push($lineitem_insert_values, $values_str);
			}
				
			$values_str = implode("," , $lineitem_insert_values);
				
			$sql = "INSERT INTO `returned_bills_lineitems` (
						`return_bill_id`, `org_id`, `user_id`,
						`serial`, `item_code`, `rate`,
						`qty`, `value`, `discount_value`,
						`amount`, `lbl_id`, `loyalty_log_id`, `points`)
						VALUES $values_str ";

			$ret2 = $this->db->insert($sql);
		}
		
		//lineitem level return Call to event manager
		$outlier_status = strtoupper($loyalty_log['outlier_status']); 
		if(Util::canCallPointsEngine() && $allowPointsReturn && $loyalty_log_id
				&& $outlier_status != TRANS_OUTLIER_STATUS_OUTLIER) {
			
				
			$return_timer = new Timer("return_bill_lineitems_points_engine");
			$return_timer->start();
				
			try{
				$this->logger->debug("pigol: Trying to contact event manager for return bill lineitem event");
					
				//COMPILE
				$event_client = new EventManagementThriftClient();
					
				$return_date = Util::getMysqlDateTime($returned_time);
				$time_in_millis = strtotime($return_date);
				if($time_in_millis == -1 || !$time_in_millis )
				{
					throw new Exception("Cannot convert '$returned_time' to timestamp", -1, null);
				}
				$time_in_millis = $time_in_millis * 1000;
					
				$till_id = $store_id;
				//Update the old tables from the points engine view
				$pesC = new PointsEngineServiceController();
				$this->logger->debug("fetching bill details from points engine before calling commit call");
				try {
					$previous_points_details = $pesC->getBillPointsDetails($org_id, $user_id, $loyalty_log_id);	
				} catch (Exception $e) {
				}
				
				
				if($return_type === TYPE_RETURN_BILL_AMOUNT)
				{

					if(Util::canCallEMF())
					{
						try{
							$emf_controller = new EMFServiceController();
							$commit = Util::isEMFActive();

							$this->logger->debug("Making returnAmountEvent call to EMF");
							$emf_result = $emf_controller->returnAmountEvent(
									$org_id,
									$user_id,
									$loyalty_log_id,
									$amount,
									$till_id,
									$time_in_millis,
									$commit,
									$this->getTenderDetailsForPE($loyalty_log_id, $paymentModeRefType),$nonLoyal
									);
							$coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
							$lm = new ListenersMgr($currentorg);
							$lm->issuedVoucherDetails($coupon_ids);
							if($commit && $emf_result !== null )
							{
								$points = $pesC->updateForReturnBillAmountTransaction(
										$org_id, $user_id, $loyalty_log_id, $time_in_millis);
							}
						}
						catch(Exception $e)
						{
							$this->logger->error("Error while making returnAmountEvent to EMF: ".$e->getMessage());
							if(Util::isEMFActive())
							{
								$this->logger->debug("Rethrowing EMF Exception");
								throw $e;
							}
						}
					}
					if(!Util::isEMFActive())
					{	
						$this->logger->debug("return type is: $return_type, 
								making call to returnBillAmountEvent for points engine");
						$result = $event_client->returnAmountEvent($org_id, $user_id, $loyalty_log_id, 
										$amount, $till_id, $time_in_millis, $commit, $this->getTenderDetailsForPE($loyalty_log_id, $paymentModeRefType));
					}
				}
				else
				{
					
					if(Util::canCallEMF())
					{
						try{
							$emf_controller = new EMFServiceController();
							$commit = Util::isEMFActive();
							$this->logger->debug("Making returnLineitemsEvent call to EMF");
							$emf_result = $emf_controller->returnLineitemsEvent(
									$org_id,
									$user_id,
									$loyalty_log_id,
									$new_return_lineitem_pe_data,
									$till_id,
									$time_in_millis,
									$commit,
									$this->getTenderDetailsForPE($loyalty_log_id, $paymentModeRefType),$nonLoyal
									);
							$coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
							$lm = new ListenersMgr($currentorg);
							$lm->issuedVoucherDetails($coupon_ids);
							
							if($commit && $emf_result !== null )
							{
								$point_awarded = $pesC->updateForReturnBillLineitemsTransaction(
										$org_id, $user_id, $loyalty_log_id, $time_in_millis);
									
								$points = $point_awarded->pointsReturned;
							}
						}
						catch(Exception $e)
						{
							$this->logger->error("Error while making returnBillLineitemsEvent to EMF: ".$e->getMessage());
							if(Util::isEMFActive())
							{
								$this->logger->debug("Rethrowing EMF Exception");
								throw $e;
							}
						}
					}
					if(!Util::isEMFActive())
					{
						$this->logger->debug("return type is: $return_type,
								making call to returnBillLineitemsEvent for points engine");
						$result = $event_client->returnBillLineitemsEvent($org_id, $user_id, $loyalty_log_id, 
										$new_return_lineitem_pe_data, $till_id, $time_in_millis);
					}
				}
				$evaluation_id = $result->evaluationID;
				$effects_vec = $result->eventEffects;
				$this->logger->debug("evaluation_id: $evaluation_id, effects: " . print_r($effects_vec, true));
					
				//COMMIT
				if(!Util::isEMFActive() && $result != null && $evaluation_id > 0) {
					$this->logger->debug("Calling commit on evaluation_id: $evaluation_id");
						
					$commit_result = $event_client->commitEvent($result);
					$this->logger->debug("Commit result on evaluation_id: ".$commit_result->evaluationID);
					$this->logger->debug("Commit result on effects: ".print_r($commit_result, true));
		
					$point_awarded = null;
					try {
						
						if($return_type == TYPE_RETURN_BILL_AMOUNT)
						{
							$points = $pesC->updateForReturnBillAmountTransaction(
							$org_id, $user_id, $loyalty_log_id, $time_in_millis);
						}
						else
						{
							$point_awarded = $pesC->updateForReturnBillLineitemsTransaction(
							$org_id, $user_id, $loyalty_log_id, $time_in_millis);
								
							$points = $point_awarded->pointsReturned;
						}
					} catch (Exception $e) {
						$this->logger->debug("Failed to get teh points ");
						$points = false;
					}
					
				}
					if( $points === false ){
						
						$points = 0;
					}
					//populating side effect
					if($points > 0)
					{
						//should update points object
						$previous_pointAwarded = $previous_points_details->pa;
						$previous_points = $previous_pointAwarded->pointsReturned;
						$points = $points - $previous_points;
						$points = floor($points);
						global $listener;
						$listener = !is_array( $listener ) ? array() : $listener ;
						//TODO: how to populate gross points and updated_loyalty_points
						//TODO: for now fetching gross points and loyalty points fetched from db,
						//TODO: need to verify from piyush.
						$sql = "SELECT id, user_id, publisher_id, loyalty_points, lifetime_points 
									FROM loyalty
									WHERE publisher_id = $org_id 
										AND user_id = $user_id";
						$row = $this->db->query_firstrow($sql);
						
						$pointsListenerFound = false;
						foreach($listener as $index=>$pointsListener)
							if($pointsListener['type'] == 'points')
							{
								$pointsListenerFound = true;
								break;
							}
								
							
						if($pointsListenerFound)
						{
							$this->logger->debug("Listener for points already exists");
							$listener[$index]['awarded_points'] += (-1 * $points);
							$listener[$index]['total_points'] = $row['loyalty_points'];
							$listener[$index]['gross_points'] = $row['loyalty_points'];
							$listener[$index]['updated_loyalty_points'] = $row['loyalty_points'];
						}
						else 
						{
							$this->logger->debug("New entry for the points side-effect. Adding listener");
							$side_effect = array();
							$side_effect['type'] = 'points';
							$side_effect['awarded_points'] = -1 * $points;
							$side_effect['total_points'] = $row['loyalty_points'];
							$side_effect['gross_points'] = $row['loyalty_points'];
							$side_effect['updated_loyalty_points'] = $row['loyalty_points'];
							array_push($listener , $side_effect);
						}
					}
					
					if(!$point_awarded || $point_awarded->pal == null || !is_array($point_awarded->pal))
						$this->logger->info("Unable to access lineitem level points, return type: $return_type");
					else
					{
						foreach ( $point_awarded->pal as $pal)
						{
							$pe_lineitems[$pal->lineItemId] = $pal;
						}
					}
					
			}catch(Exception $e){
					
				$this->logger->error("Exceptamountion thrown in return bill lineitem event, code: " . $e->getCode()
						. " Message: " . $e->getMessage());
				
				if( !isset($commit_result) || $commit_result === null
						|| ( Util::isEMFActive() && ( isset($emf_result) || $emf_result === null ) )){
					
					$this->logger->debug("Commit Failure from points engine, removing return bill");
					// return bills is now replayable
					/*$sql = "DELETE FROM returned_bills WHERE org_id = $org_id AND id = $return_bill_id";
					$rows = $this->db->update($sql);
					$this->logger->debug("deleted return bill with id: $return_bill_id");
					
					$sql = "DELETE FROM returned_bills_lineitems WHERE org_id = $org_id AND return_bill_id = $return_bill_id";
					$rows = $this->db->update($sql);
					$this->logger->debug("deleted returned bill lineitems with return bill id: $return_bill_id");
					$return_bill_id = -1;
					throw new Exception("ERR_RETURN_BILL_POINTS_COULD_NOT_BE_DEDUCTED");*/
					
				}
			} // end point engine call
				
			$return_timer->stop();
				
			$ef_time += $return_timer->getTotalElapsedTime();
				
			$this->logger->debug("pigol: return bill amount/lineitem timer: " . $return_timer->getTotalElapsedTime());
			unset($return_timer);
				
		}
		if($points > 0 && $allowPointsReturn)
		{
			//TODO: need to add queries for updating lines.
			$sql = "UPDATE `returned_bills` SET points = $points WHERE id = $return_bill_id";
			
			$updated_rows = $this->db->update($sql);
			$lineitem_insert_values = array();
			if($updated_rows > 0  && count($new_return_items) > 0)
			{
				//insert the lineitems
				$this->logger->debug("updating returned points for lineitems");
				$temp_pal = $previous_pointAwarded->pal;
				foreach($temp_pal as $temp_li)
				{
					$pe_lineitems[$temp_li->lineItemId]->pointsReturned = 
						$pe_lineitems[$temp_li->lineItemId]->pointsReturned -
						$temp_li->pointsReturned;   
				}
				foreach($new_return_items as $li)
				{		
					if(isset($li['id']))
					{
						$lbl_id = $li['id'];
						//$pe_lineitems[$lbl_id] will be set only if points engine is active.
						if(isset($pe_lineitems[$lbl_id]))
						{
							$returned_points = $pe_lineitems[$lbl_id]->pointsReturned;
							$sql = "
									UPDATE returned_bills_lineitems SET points = FLOOR($returned_points) 
										WHERE loyalty_log_id = $loyalty_log_id
										AND return_bill_id = $return_bill_id
										AND lbl_id = $lbl_id
										AND org_id = $org_id";
							
							$num_row_affected = $this->db->update($sql);
						}
						else
						{
							$this->logger->error(
									"lineitem is not populated in
									bill details fetched from
									PointsEngine(lbl_id:$lbl_id)");
						}
					}
					else
					{
						$this->logger->debug("lbl_id not found: skiping updating for item_code: ".$li['item_code']);
					}
				}
			}
			else
			{
				$this->logger->debug("return bill type: $return_type, not updating lineitems");
			}
		}
		
		//throw new Exception("ERR_LOYALTY_BILL_ADDITION_FAILED");
		return true;
	}
	
	function getFraudUsers($action = 'gen', $exclude_statuses = array(), $include_statuses = array()){
		$org_id = $this->currentorg->org_id;
                
                // Sorting to construct consistant cache keys
                asort($exclude_statuses);
                asort($include_statuses);
		$cacheKey = "o" . $org_id . "_" . CacheKeysPrefix::$apiFraudUsers . "_ex#";
                foreach($exclude_statuses as $e)
                {
                    $cacheKey .= substr($e, 0, 2);
                }
                $cacheKey .= "_in#";
                foreach($include_statuses as $e)
                {
                    $cacheKey .= substr($e, 0, 2);
                }
		$cm = MemcacheMgr::getInstance();
                try {
                    $result = json_decode($cm->get($cacheKey), true);
                    if($result != null)
                        return $result;
                } catch (Exception $ex) {
                    $this->logger->debug("Failed fetching cache key.");
                }
                $column_list = "*";
		
		if($action == 'api')
			$column_list = " user_id, status ";
		
		$exclude_status_filter = "";
		if(count($exclude_statuses) > 0)
			$exclude_status_filter = " AND `status` NOT IN (".Util::joinForSql($exclude_statuses).")";

		$include_status_filter = "";
		if(count($include_statuses) > 0)
			$include_status_filter = " AND `status` IN (".Util::joinForSql($include_statuses).")";
			
		$sql = "
			SELECT $column_list
			FROM `fraud_users`
			WHERE `org_id` = $org_id
				$exclude_status_filter
				$include_status_filter
		";
		
                $db = new Dbase('users', true);
		$result = $db->query($sql);
                try {
                    $cm->set($cacheKey, json_encode($result, true), CacheKeysTTL::$apiFraudUsers);
                } catch (Exception $e) {
                    $this->logger->debug("Failed setting cache key.");
                }

                return $result;
	}
	
	function getStoreAttributesForApi(){
		
		$attributes = array(
			array('attribute' => array('name' => 'region', 'type' => 'String')),
			array('attribute' => array('name' => 'city', 'type' => 'String')),
			array('attribute' => array('name' => 'state', 'type' => 'String')),
			array('attribute' => array('name' => 'size', 'type' => 'Double')),
			array('attribute' => array('name' => 'type_of_area', 'type' => 'String')),
			array('attribute' => array('name' => 'tier_of_city', 'type' => 'String')),
			array('attribute' => array('name' => 'location', 'type' => 'String')),
			array('attribute' => array('name' => 'store_address_line1', 'type' => 'String')),
			array('attribute' => array('name' => 'store_address_line2', 'type' => 'String')),
			array('attribute' => array('name' => 'store_name', 'type' => 'String')),
			array('attribute' => array('name' => 'mobile', 'type' => 'String')),
			array('attribute' => array('name' => 'email', 'type' => 'String')),
			array('attribute' => array('name' => 'store_last_name', 'type' => 'String'))		
		);
		
		return $attributes;
	}
	
	function getStoreAttributeValuesForApi(){
		
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		
		$attributes = $this->getStoreAttributesForApi();
		
		$columns = array();		
		foreach($attributes as $a){
			$a = $a['attribute'];
			$c = '`si`.`'.$a['name'].'`';
			if($a['name'] == 'store_name') // make first name as storename
				$c = '`u`.`firstname` AS store_name';
			if($a['name'] == 'store_address_line1') // make address_1 as store_address_line1
				$c = '`si`.`address` AS store_address_line1';
			if($a['name'] == 'store_address_line2') // make address_2 as store_address_line2
				$c = '`si`.`address_2` AS store_address_line2';
			if( $a['name'] == 'store_last_name' )
				$c = '`u`.`lastname` AS `store_last_name`';
			if( $a['name'] == 'mobile' )
				$c = '`u`.`mobile`';
			if( $a['name'] == 'email' )
				$c = '`u`.`email`';		
			array_push($columns, $c);
		}
		
		$sql = "
			SELECT '$store_id' as store_id, ".implode(',', $columns)."
			FROM `stores_info` si
			JOIN `stores` u ON si.store_id = u.store_id
			WHERE si.org_id = '$org_id' AND si.`store_id` = '$store_id'
		";
		
		return $this->db->query($sql);
	}
	
	function updatePointsAfterExpiry($expiry_days){
		
		if(!$this->currentorg->get(CONF_LOYALTY_POINTS_EXPIRY_ENABLED))
			return false;
		
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		
		//Move points of customer who havent visited in the last few days into expired log table
		$sql = "
		INSERT INTO `expired_loyalty_info_log` (`org_id`, `user_id`, `loyalty_current_points`, `points_expired`, `last_updated_by`, `last_updated_on`, `entered_by`, `expiry_checked_on`)	
			SELECT `publisher_id`, `user_id`, `loyalty_points`, `loyalty_points`, `last_updated_by`, `last_updated`, '$store_id' as store_id, NOW() as expiry_checked_on
			FROM `loyalty` 
			WHERE `publisher_id` = '$org_id'
			AND DATEDIFF( NOW(), `last_updated`) > $expiry_days			
			AND `loyalty_points` > 0
		";
		
		$ret = $this->db->insert($sql);
		
		if($ret){
			
			//Set points of customer who havent visited in the last few days to 0
			$sql = "
				UPDATE `loyalty`
					SET `loyalty_points` = 0,
						`last_updated` = NOW()
				WHERE `publisher_id` = '$org_id' 
				AND DATEDIFF( NOW(), `last_updated`) > $expiry_days
				AND `loyalty_points` > 0
			";
			
			$ret2 = $this->db->update($sql);
			
			if($ret2)
				return $ret2;
		}
		
		return 0;
	}

	function mailOutlierDetails(UserProfile $user, $loyalty_id, $points, $bill_amount, $notes, $bill_number, $entered_by, $ignore_points, $datetime = ''){
		
		$org_id = $this->currentorg->org_id;
		$org_name = $this->currentorg->name;

		$subject = "Outlier Details For Customer For Organization : $org_name";

		$body = "(
			Customer_id : $user->user_id <br\> 
			Points : $points <br\> 
			Bill Amount : $bill_amount <br\> 
			Cachier Notes : $notes
			Bill Number : $bill_number <br\> 
			store_id : $entered_by <br\>
			bill date : $datetime )";

		$recepient = $this->getConfigurationValue(MAIL_IDS_FOR_OUTLIER_REPORT, false);

		if($recepient){
			
			$recepient = explode(',', $recepient);
		}else
			return;
		
		Util::sendEmail($recepient, $subject, $body, $org_id);
	}
   	

	/**
	 * 
	 * @param $sms_template
	 * @param $enddate
	 * @param $send_sms
	 * @param $email_subject
	 * @param $email_body
	 * @param $send_email
	 * @param $min_points
	 * @param $max_points
	 * @unused in API
	 */
// 	function sendSplitExpiryReminderSMS( 
// 				$sms_template, $enddate, $send_sms, $email_subject, 
// 				$email_body, $send_email, $has_redeemed 
// 	)
// 	{
		
// 	    global $logger ;

// 	    $logger->debug('in split points action');	
		
// 	    $org_id = $this->currentorg->org_id;

// 	    $store_id = $this->currentuser->user_id;

// 	    if($send_email)
// 	    	$filter = " AND `eup`.`email` IS NOT NULL
// 						AND `eup`.`email` NOT LIKE '%test%'
// 						AND `eup`.`email` NOT LIKE '%unknown%'
// 						AND `eup`.`email` != ''	    	
// 	    	";

// 	    if($send_sms)
// 	    	$filter = "
	    	
// 	    			AND `eup`.`mobile` != ''
// 	    			AND `eup`.`mobile` IS NOT NULL
// 	    	";
	    	
// 	     if( $has_redeemed ){
	     	
// 	     	$points_filter = " AND `lp`.`user_id` IS NOT NULL ";	
// 	     }else{
	     	
// 	     	$points_filter = " AND `lp`.`user_id` IS NULL ";
// 	     }
	     
// 	    	$sql = " 
// 				SELECT `eup`.`mobile` , `eup`.`email`, 
// 					CONCAT(`eup`.`firstname`, ' ',`eup`.`lastname`) AS `fullname`,
// 					`ll`.`user_id`, 
// 					(SUM(`ll`.`points`) - SUM(`ll`.`redeemed`) - SUM(`ll`.`expired`) ) AS `expiry_points`,
// 					`l`.`loyalty_points` 
				
// 				FROM `loyalty_log` `ll` 
// 				JOIN `extd_user_profile` `eup` ON `eup`.`user_id` = `ll`.`user_id` AND `eup`.`org_id` = `ll`.`org_id`
// 				JOIN `loyalty` AS `l` ON (  lp.org_id = l.publisher_id AND lp.user_id = l.user_id ) 
// 				LEFT OUTER JOIN `loyalty_redemptions` AS lp ON ( lp.loyalty_id = l.id ) 
// 		        WHERE 
// 		        	`ll`.`org_id` = '$org_id' 
// 		        	AND `ll`.`date` < '$enddate' 
// 		        	AND `ll`.`points` > 0 
// 		        	AND `ll`.`points` > (`ll`.`redeemed` + `ll`.`expired`) 
// 	    			$filter  
// 	    			$points_filter
// 		        GROUP BY ll.user_id";
		
// 		$res = $this->db->query($sql);

// 		foreach($res as $row){
			
// 			$msg = Util::templateReplace($sms_template, $row, array('fullname' => 'Customer'));
// 			$subject = Util::templateReplace($email_subject, $row, array('fullname' => 'Customer'));
// 			$body = Util::templateReplace($email_body, $row, array('fullname' => 'Customer'));

// 			if($send_email)
// 				Util::sendEmail($row['email'], $subject, $body, $org_id);

// 			if($send_sms)
// 				Util::sendSms($row['mobile'], $msg, $org_id, MESSAGE_PERSONALIZED);	
			
// 			$logger->debug(" msg : $msg email : $email_body number : ".$row['mobile']);
// 		}
		
// 		return $res;
// 	}


	/**
	 * Send points expiry reminder
	 * @param $sms_template Tags accepted : fullname, points, expiry_in_days, expiry_date, store_name, store_number
	 * @param $not_shopped_days
	 * @param $expiry_after_days
	 * @param $send_sms
	 */
// 	function sendExpiryReminderSMS($sms_template, $not_shopped_days, $expiry_after_days, $send_sms = false){
		
// 		if(!$this->currentorg->get(CONF_LOYALTY_POINTS_EXPIRY_ENABLED))
// 			return false;
		
// 		$org_id = $this->currentorg->org_id;
// 		$store_id = $this->currentuser->user_id;
		
// 		//Find customers whose last visit was more than $not_shopped_days but less than $expiry_after_days ago
// 		$sql = "
// 			SELECT TRIM(CONCAT(eup.firstname, ' ', eup.lastname)) as fullname, eup.mobile, 
// 					l.loyalty_points as points, l.`last_updated`, DATEDIFF( NOW(), l.`last_updated`) AS days_since_last_visit, 
// 					($expiry_after_days - DATEDIFF( NOW(), l.`last_updated`)) as expiry_in_days, 
// 					DATE(DATE_ADD(l.`last_updated`, INTERVAL $expiry_after_days DAY)) AS expiry_date, 
// 					oe.code as store_name, null as store_number
// 			FROM `loyalty` l
// 			JOIN `extd_user_profile` eup ON eup.org_id = l.publisher_id AND eup.user_id = l.user_id
// 			JOIN masters.org_entities oe ON oe.id = l.registered_by AND oe.org_id = l.publisher_id 
// 			WHERE l.`publisher_id` = '$org_id'
// 			AND DATEDIFF( NOW(), l.`last_updated`) BETWEEN $not_shopped_days AND ($expiry_after_days - 1)			
// 			AND l.`loyalty_points` > 0
// 		";
		

// 		$res = $this->db->query_table($sql);
		
// 		function SMSText(array $row, $params){
			
// 			$msg = Util::templateReplace($params['sms_template'], $row, array('fullname' => 'Customer'));
// 			$status = "";
// 			if($params['send_sms']){
// 				$ret = Util::sendSms($row['mobile'], $msg, $params['org_id'], MESSAGE_PRIORITY);
// 				$status = ($ret == true) ? 'Sent' : 'Not Sent';				
// 			}else
// 				$status = "Preview";
			
// 			return "$status-$msg";
// 		}
		
// 		$res->addFieldByMap('Msg Sent', 'SMSText', array('sms_template' => $sms_template, 'send_sms' => $send_sms, 'org_id' => $org_id));
		
// 		return $res;
		
// 	}
	
	
	/**
	 * Sets the bill outlier status
	 * @param $bill_status array of rows. Each row is an assoc array of bill_id,outlier_status,store_id,user_id,bill_number
	 */
	function setBillOutlierStatus($bill_status){
		$org_id = $this->currentorg->org_id;
		$count = 0;
		foreach($bill_status as $b){
			
			$id = $b['bill_id'];
			$outlier_status = $b['outlier_status'];
			$store_id = $b['store_id'];
			$user_id = $b['user_id'];
			$bill_number = $b['bill_number'];
			
			$sql = "
				UPDATE `loyalty_log`
				SET `outlier_status` = '$outlier_status'
				WHERE `id` = '$id' AND `org_id` = '$org_id' 
					AND `entered_by` = '$store_id' AND `user_id` = '$user_id'
					AND `bill_number` LIKE '$bill_number'
			";
			
			if($this->db->update($sql))
				$count++;
		}
		
		return $count;
	}
	
	
	/**
	 * @param unknown_type $user_id
	 * @param unknown_type $outtype
	 * 
	 * @deprecated : 
	 */
// 	function getLoyaltyRedemptions($user_id, $outtype = 'query'){
		
// 		$org_id = $this->currentorg->org_id;

// 		$sql = "SELECT l.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, 
// 					u.user_id, u.mobile AS customer_mobile, l.points_redeemed, l.voucher_code, 
// 					l.bill_number, l.notes, l.date, s1.username AS entered_by " .
// 				" FROM loyalty_redemptions l " .
// 				" JOIN extd_user_profile u ON l.user_id = u.user_id AND l.org_id = u.org_id " .
// 				" JOIN stores s1 ON l.entered_by  = s1.store_id AND (l.org_id = s1.org_id OR s1.org_id = '0')".
// 				" WHERE l.org_id = $org_id AND l.user_id = '$user_id' ";
		
// 		if($outtype == 'query_table')
// 			return $this->db->query_table($sql);
			
// 		return $this->db->query($sql);
// 	}
	
	function getSeasonalSlabConfig($id){
		$org_id = $this->currentorg->org_id;
		
		$sql = "SELECT * FROM `seasonal_slabs` WHERE id = '$id' AND org_id = $org_id LIMIT 0,1";
		return $this->db->query_firstrow($sql);
	}
	
	function setSeasonalSlabConfiguration($period_from, $period_to, $stores, $zones, $params, $id = ''){
		
		$org_id = $this->currentorg->org_id;
		$store_id  = $this->currentuser->user_id;
		
		$stores_json = json_encode($stores);
		
		$params = json_encode($params);
		
		if($id == ''){
			//insert
			$sql = "
				INSERT INTO `seasonal_slabs` (`org_id`, `period_from`, `period_to`, `for_stores_json`, `in_zones`, `params`, `added_by`, `last_modified`) 
				VALUES ('$org_id', '$period_from', '$period_to', '$stores_json', '$zones', '$params', '$store_id', NOW());
			";
			return $this->db->insert($sql);
			
		}else{
			//update
			$sql = "
				UPDATE `seasonal_slabs` 
				SET `period_from` = '$period_from',
					`period_to` = '$period_to',
					`for_stores_json` = '$stores_json',
					`in_zones` = '$zones',
					`params` = '$params',
					`added_by` = '$store_id',
					`last_modified` = NOW()
				WHERE `id` = '$id' AND `org_id` = '$org_id'
			";
			return $this->db->update($sql);
			
		}
		
	}
	
	function getSeasonalSlabConfigurations($outtype = 'query', $date = ''){
		
		return array();
		$org_id = $this->currentorg->org_id;
		
		$date_filter = "";
		if($date != ''){
			$date_filter = " AND ( DATE( '$date' ) BETWEEN DATE( `period_from` ) AND DATE( `period_to` ) )";
		}
		
		$sql = "
			SELECT a.id, period_from, period_to, for_stores_json, in_zones, params, oe.code as added_by, a.last_modified			
			FROM `seasonal_slabs` a
			JOIN masters.org_entities oe ON oe.id = a.added_by AND oe.org_id = a.org_id
			WHERE a.org_id = '$org_id' 
				$date_filter
		";
		
		if($outtype == 'query_table')
			return $this->db->query_table($sql);
			
		return $this->db->query($sql);
	}
	
	/**
	 * Get the seasonal slab running for the store for today (if any)
	 * @param unknown_type $slab Customers slab for the percentage is to be found out
	 * @param unknown_type $percent Percent is set if seasonal config is found
	 * Returns false, if no seasonal config found. 
	 */
	function getSeasonalSlabPercentage($slab, &$percent){

		$date = date('Y-m-d');
		$store_id = $this->currentuser->user_id;
		
		$slabs = $this->getSeasonalSlabConfigurations('query', $date);
		
		if(count($slabs) == 0)
			return false;
		
		//Take the percentage from first row which satisfied the store/zone execution condition
		foreach($slabs as $s){
			
			//check if executable in stores
			$stores = $s['for_stores_json'];
			$stores = strlen($stores) > 0 ? json_decode($stores, true) : array();
			if(!$this->checkIfExecutableForStore($store_id, $stores))
				continue; //not executable for this store
			
			$zone = $s['in_zones'];
			if(!$this->checkIfExecutableInZone($store_id, $zone))
				continue;
			
			//Executable in the zone and store..  use the current slab info
			$slabs_alt = json_decode($s['params'], true);
			$percent = $slabs_alt[Util::uglify($slab)];
				
			return true;
				
		}
		
		return false;
	}
	
	function checkIfExecutableForStore($store, $stores){

		if(count($stores) > 0){
			if(in_array($store, $stores))
				return true;
			else
				return false;
		}
		
		return true; // exec by default
	}
	
	function checkIfExecutableInZone($store_id, $zone_selected){
		
		if($zone_selected == '' || $zone_selected == '-2')
			return true; //Ignore zone

		//Zone options : Load only if a zone is selected
		$am = new AdministrationModule();
		
		$zone_filter = $am->createZoneFilter($zone_selected);
		$zone_filter = $am->getModifiedZoneFilter('`u`.`id`', $zone_filter);	
		$zone_filter = " JOIN stores_zone z ON 1 = 1 $zone_filter";
		
		$sql = "SELECT * FROM users u $zone_filter WHERE id = '$store_id'";
		$s = $this->db->query($sql);
		
		return (count($s) > 0) ? true : false;
		
	}
	
	function getFraudStatus($user_id){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = "SELECT `status` FROM `fraud_users` WHERE org_id = '$org_id' AND user_id = '$user_id'";
		return $this->db->query_scalar($sql);
		
	}
	
	
	function moveAwardedPoints($from_user_id, $to_user_id, $sure = false){
		
		if(!$sure)
			return false;
		
		$org_id = $this->currentorg->org_id;
		
		$to_loyalty_id = $this->getLoyaltyId($org_id, $to_user_id);
		
		$sql = "
		UPDATE `awarded_points_log`
			SET `user_id` = '$to_user_id', `loyalty_id` = '$to_loyalty_id'
		WHERE org_id = '$org_id' AND `user_id` = '$from_user_id' 
		";
		
		return $this->db->update($sql);	
		
	}
	
	function moveCancelledBills($from_user_id, $to_user_id, $sure = false){
		
		if(!$sure)
			return false;
		
		$org_id = $this->currentorg->org_id;
		
		$to_loyalty_id = $this->getLoyaltyId($org_id, $to_user_id);
		
		$sql = "
		UPDATE `cancelled_bills`
			SET `user_id` = '$to_user_id', `loyalty_id` = '$to_loyalty_id'
		WHERE org_id = '$org_id' AND `user_id` = '$from_user_id' 
		";
		
		return $this->db->update($sql);	
		
	}
	
	function moveCustomFieldsData($from_user_id, $to_user_id, $sure = false){
		
		if(!$sure)
			return false;
		
		$cf = new CustomFields();
		$org_id = $this->currentorg->org_id;
		
		return $cf->moveCustomFieldsData($org_id, $from_user_id, $to_user_id, $sure);
	}
	
	function moveBills($from_user_id, $to_user_id, $sure = false){
		
		if(!$sure)
			return false;
		
		$org_id = $this->currentorg->org_id;
		
		$to_loyalty_id = $this->getLoyaltyId($org_id, $to_user_id);

		
		//move the loyalty promotional bill
		$sql = "
		UPDATE `loyalty_promotional_campaign_bills`
			SET `user_id` = '$to_user_id'
		WHERE org_id = '$org_id' AND `user_id` = '$from_user_id' 
		";		
		$this->db->update($sql);

		//move the line items
		$sql = "
		UPDATE `loyalty_bill_lineitems` lbl, loyalty_log ll
			SET lbl.`user_id` = '$to_user_id'
		WHERE lbl.org_id = '$org_id' AND lbl.`user_id` = '$from_user_id'
			  AND ll.id = lbl.loyalty_log_id AND ll.org_id = '$org_id' AND ll.user_id = '$from_user_id'  
		";		
		$this->db->update($sql);
		
		//move the bills
		$sql = "
		UPDATE `loyalty_log`
			SET `user_id` = '$to_user_id', `loyalty_id` = '$to_loyalty_id'
		WHERE org_id = '$org_id' AND `user_id` = '$from_user_id' 
		";
		
		return $this->db->update($sql);		
	}
	
	function movePointsRedemptions($from_user_id, $to_user_id, $sure = false){
		
		if(!$sure)
			return false;
		
		// the table loyalty redemptions is deprecated, so always true :) - cj
		return true;
	}
	
	function moveLoyaltyInfo($from_user_id, $to_user_id, $sure = false){
		
		if(!$sure)
			return false;
		
		$org_id = $this->currentorg->org_id;
		
		$from_customer_details = $this->getLoyaltyDetailsForUserID($from_user_id);
		$loyalty_points = $from_customer_details['loyalty_points'];
		$lifetime_points = $from_customer_details['lifetime_points'];
		$lifetime_purchases = $from_customer_details['lifetime_purchases'];
		$joined = $from_customer_details['joined'];
		 
		$to_customer_details = $this->getLoyaltyDetailsForUserID($to_user_id);
		$to_joined = $to_customer_details['joined'];
		
		$joined = ( strtotime( $joined ) < strtotime( $to_joined ) )?( $joined ): ( $to_joined );
		
		$up_sql = "
		UPDATE `user_management`.`loyalty`
			SET `loyalty_points` = `loyalty_points` + '$loyalty_points',
				`lifetime_points` = `lifetime_points` + '$lifetime_points',
				`lifetime_purchases` = `lifetime_purchases` + '$lifetime_purchases',
				`joined` = '$joined',
				`last_updated` = NOW()
		WHERE `publisher_id` = '$org_id' AND `user_id` = '$to_user_id' 
		";
		$this->db->update($up_sql);

		$sql = "
		UPDATE `user_management`.`loyalty`
			SET `loyalty_points` = 0,
				`lifetime_points` = 0,
				`lifetime_purchases` = 0,
				`last_updated` = NOW()
		WHERE `publisher_id` = '$org_id' AND `user_id` = '$from_user_id' 
		";
		$this->db->update($sql);
		
	}
	
	function moveContactInfo($from_user_id, $to_user_id, $sure = false){
		
		if(!$sure)
			return false;
		
		$org_id = $this->currentorg->org_id;
		
		
		//Move the mobile if the 'to' customer does not have any
		$from_user = UserProfile::getById($from_user_id);
		$to_user = UserProfile::getById($to_user_id);
		
		$from_user_mobile = $from_user->mobile;
		$to_user_mobile = $to_user->mobile;
	
		$this->logger->debug( "from user mobile : ".$from_user_mobile ." to user mobile ".$to_user_mobile );	
		//check if the 'to' customer has a valid mobile..
		//  if not, then try to put the mobile of the from customer


		//set the external id of the 'from' customer..  if the 'to' customer doesn have one
		//collect the details before clearing
		$from_customer_loyalty_details = $this->getLoyaltyDetailsForUserID($from_user_id);
		$external_id = $from_customer_loyalty_details['external_id'];

		//check external id exists or not
		$to_customer_loyalty_details = $this->getLoyaltyDetailsForUserID($to_user_id);
		$to_external_id = $to_customer_loyalty_details['external_id'];
		
		//check if mobile update is required
		$clear_from_mobile = false;
		if( Util::checkMobileNumber( $to_user_mobile ) ){
			
			$clear_from_mobile = false;
			$update_to_customer_mobile = $to_user_mobile;//no updates required
		}elseif( Util::checkMobileNumber( $from_user_mobile ) ){
			
			$clear_from_mobile = true;
			$update_to_customer_mobile = $from_user_mobile;
		}
		
		//check if external id update is required
		$clear_from_external_id = false;
		if( $to_external_id ){
			
			$clear_from_external_id = false;
			$update_to_customer_external_id = $to_external_id;
		}elseif ( $external_id ){

			$clear_from_external_id = true;
			$update_to_customer_external_id = $external_id;
		}
		
			
		//clear the mobile for from customer
		//if no mobile clearance is required it means we are retaining the
		 
		$sql = "
			UPDATE `users` u 
			SET 
				u.mobile = NULL
			WHERE 
				u.org_id = '$org_id' AND 
				u.id = '$from_user_id'  
		";
		
		$is_updated = $this->db->update($sql);

		if( $is_updated ){
		
			$sql = "
				UPDATE `users` u 
					SET u.mobile = '$update_to_customer_mobile'
						
				WHERE 
					u.org_id = '$org_id' AND 
					u.id = '$to_user_id' 
			";
			
			$is_contact_changed = $this->db->update($sql);
		}
		
		//clear the external id
		//same thing if no clearance of external id is required then we are retaining the value
			
			//Clear the mobiles and external_id of the 'from' customer..  so that its not possible to search
			$sql = "
				UPDATE loyalty AS l
					SET 
						l.external_id = NULL,
						l.`last_updated` = NOW()
				WHERE 
 						l.publisher_id = '$org_id' AND l.user_id = '$from_user_id'
			";
			
			$is_updated = $this->db->update($sql);
			
			if( $is_updated ){
			
				$sql = "
					UPDATE `loyalty` l
					SET 
						l.external_id = '$update_to_customer_external_id',				
						l.`last_updated` = NOW()
					WHERE 
						l.publisher_id = '$org_id' AND l.user_id = '$to_user_id'
				";
				
				$is_contact_changed = $this->db->update($sql);
			}
	}
	
	function addMergeCustomerRequest($from_user_id, $to_user_id, $from_user_mobile, $from_user_external_id, $reason, $details){
		$org_id = $this->currentorg->org_id;
		
		$merged_by = $this->currentuser->user_id;
		
		$sql = "
			INSERT INTO `merge_customers_log` (`org_id`, `from_user_id`, `to_user_id`, `from_user_mobile`, `from_user_external_id`, `reason`, `merged_by`, `merged_on`, `details`) 
			VALUES ('$org_id', '$from_user_id', '$to_user_id', '$from_user_mobile', '$from_user_external_id', '$reason', '$merged_by', NOW(), '$details')
		";
		return $this->db->insert($sql);		
	}


	function getPointExpiryDaysAndDateForUsers($user_ids, $expiry_after_days, $org_id){

		$sql = "
			SELECT `l`.`user_id`, ($expiry_after_days - DATEDIFF( NOW(), l.`last_updated`)) as expiry_in_days,
					DATE(DATE_ADD(l.`last_updated`, INTERVAL $expiry_after_days DAY)) AS expiry_date
			FROM `loyalty` l
			WHERE l.`publisher_id` = '$org_id'
			 AND `l`.`user_id` IN (".Util::joinForSql($user_ids).")
			ORDER BY `l`.`user_id`
		";

		return $this->db->query($sql);

	}
	
	function setSlabForAll($slab_number, $to_slab_name){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			UPDATE `loyalty`
			SET
				`slab_name` = '$to_slab_name',
				`last_updated` = NOW()
			WHERE `publisher_id` = '$org_id' AND `slab_number` = '$slab_number'
		";
		return $this->db->update($sql);
		
	}
	
	function setDefaultSlabForCustomersWithoutSlab($to_slab_name){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			UPDATE `loyalty`
			SET
				`slab_number` = '0',
				`slab_name` = '$to_slab_name',
				`last_updated` = NOW()
			WHERE `publisher_id` = '$org_id' AND `slab_name` IS NULL
		";
		return $this->db->update($sql);
	}
	
	function expirePointsForUser($user_id, $date, $store){
		
		$end_date = Util::getNextDate($date);
		
		$sql = " 
		
			SELECT `id`, `points`, `points` - `redeemed` - `expired` AS `expired_points`
			FROM `loyalty_log`
			WHERE `org_id` = '93' ANd `user_id` = '$user_id' AND `date` < '$end_date' AND `points` - `redeemed` - `expired` > 0
		";
		
		$res = $this->db->query($sql);
		$sum = 0;
		
		foreach($res as $r){
			
			$id = $r['id'];
			$expired = $r['expired_points'];
			
			if($expired > 0){
				$sql = "  
				
					UPDATE `loyalty_log`
					SET `expired` = `expired` + '$expired'
					WHERE `id` = '$id'
				";
				$this->db->update($sql);
				$sum += $expired;
			}
		
		}
		
		if($sum > 0){

			$sql = "
			
	 		INSERT INTO `user_management`.`expired_loyalty_info_log` 
	 		(
	 		`org_id` ,`user_id` ,`loyalty_current_points` , `points_expired`, `last_updated_by` ,`last_updated_on` ,`entered_by` ,`expiry_checked_on`
			)
			VALUES (
			'93', '$user_id', $points, '$sum', '$store', NOW(), '$store', NOW()
			) 
			";
			
			$res = $this->db->insert($sql);
			
			$sql = "
				UPDATE `loyalty`
				SET `loyalty_points` = `loyalty_points` - $sum
				WHERE `publisher_id` = '93' AND `user_id` = '$user_id'
			";
			
			$res = $this->db->update($sql);
			
		}
		return;
	}
	
	function storeMissedCallInfo($org_id, $mobile_num, $user_id){
		
		$sql = "
			INSERT INTO `missed_call_numbers`(`org_id`, `mobile_number`, `user_id`, `called_on`)
			VALUES ('$org_id', '$mobile_num', '$user_id', NOW())
			ON DUPLICATE KEY UPDATE `user_id` = '$user_id', `called_on`= NOW()
		";
		return $this->db->insert($sql);
		
	}
	
	/**
	 * Get all missed calls given
	 * Does not send those who are already registered 
	 * @param $after All missed calls On or after that date
	 */
	function getMissedCallInfo($after){
		
		$org_id = $this->currentorg->org_id;
		
		//not being used now
		$existing_user_id_filter = " AND (user_id < 0 OR user_id IS NULL) ";
		
		$sql = "
			SELECT `id`, `mobile_number`, `called_on` 
			FROM `missed_call_numbers`
			WHERE org_id = $org_id AND DATE(`called_on`) >= DATE('$after')
			ORDER BY `called_on` DESC
		";
		$res = $this->db->query($sql);
		
		$final_res = array();
		
		foreach($res as $row){
			
			$mob = $row['mobile_number'];
			$called_on = $row['called_on'];
			$id = $row['id'];
			
			//Send only valid mobile numbers
			if(Util::checkMobileNumber($mob)){
				
				$user = UserProfile::getByMobile($mob);
				if($user){
					
					//Customer already present, no need to send to the client
					
					$user_id = $user->user_id;
					
					//update the user id against the entry
					$sql = "
					UPDATE `missed_call_numbers`
					SET user_id = '$user_id'
					WHERE `id` = '$id' AND `org_id` = '$org_id' 
					";
					$this->db->update($sql);
					
					//continue;
				}
				
				array_push($final_res, array(
					'mobile_number' => $mob,
					'called_on' => $called_on
				));				
			}
			
			//exlude if mobile number is not right
		}
		
		return $final_res;
	}
	
	/**
	 * Steps :
	 * 
	 * 1) Insert expired info log
	 * 2) update loyalty by expiry info log
	 * 3) update split
	 * 4) update loyalty log 
	 * 
	 * @param unknown_type $date
	 */
	function expireSplitPoints($date){
		
		$this->loyaltyModel->setEndDate($date);
				
		//get max id before updation
		$this->loyaltyModel->getScalarResult();
		$max_expiry_id = $this->loyaltyModel->getMaxIdForExpiryInfoLog();

		//get sql for insertion
		$this->loyaltyModel->getSqlResult();
		$sql = $this->loyaltyModel->getSplitPointsCustomerLevelExpiry();
		
		//insert points to expire
		$this->loyaltyModel->InsertResult();
		$ret = $this->loyaltyModel->insertIntoExpiryInfoLog( $sql );
		if(!$ret){
			$this->logger->debug(' Expiry Log Couldn\'t Be Updated ');
			return false;
		}
		//update loyalty points
		$this->loyaltyModel->updateTable();
		$ret = $this->loyaltyModel->updateLoyaltyByExpiryInfo( $max_expiry_id );
		if(!$ret){
			$this->logger->debug(' Loyalty Points Log Couldn\'t Be Updated ');
			return false;
		}
		//update split
		$this->loyaltyModel->InsertResult();
		$this->loyaltyModel->updateSplitByExpiredBills( $max_expiry_id );
		if(!$ret){
			$this->logger->debug(' Split Was Not Updated Be Updated Can\'t return back from here');
			return false;
		}
		
		//update loyalty log
		$this->loyaltyModel->updateTable();
		$ret = $this->loyaltyModel->updateLoyaltyLogForExpiry();
		if(!$ret){
			$this->logger->debug(' Loyalty Log was not updated ');
			return false;
		}
		
	}
	
	public function getCustomerExpiryDetails( $user_id, &$widgets ){
		
		$expiry_points_date = $this->currentorg->getConfigurationValue(DATE_TOCONSIDER_FOR_EXPIRY_POINTS_FOR_PURCHASE_HISTORY, false);
		
		$this->loyaltyModel->getScalarResult();
		$this->loyaltyModel->setEndDate( $expiry_points_date );
		$response = ( int ) $this->loyaltyModel->getPointsToExpireByDate( $user_id );

		$expiry_data = "NOTE : $response points will be expired on the 1st of next month ";
		
		$widget = array(
			'widget_name' => $expiry_data,
			'widget_code' => $user_id,
			'widget_data' => $expiry_data 
		);
		array_push($widgets, $widget);
	}
	
	/*
	 * Raymond function to check the last slab upgrade, so that the slab can be reset
	 */
	public function raymondCheckLastSlabUpgradeTime($user_id, $from_slab, $upgrade_date)
	{
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			SELECT 1 AS is_within
			FROM `slab_upgrade_log` 
			WHERE `org_id` = '$org_id'
				AND DATE(`upgrade_time`) = DATE('$upgrade_date')
				AND `user_id` = '$user_id'
				AND `from_slab_name` = '$from_slab'
				AND `notes` = 'Raymond First Bill Slab Upgrade'
			LIMIT 0,1
		";	
		
		return $this->db->query_scalar($sql);		
	}
	
	public function raymondCheckFirstBillTimeDiff($user_id, $upgrade_date, $diff_mins)
	{
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			SELECT TIMESTAMPDIFF(MINUTE, `date`, '$upgrade_date') BETWEEN 0 AND $diff_mins AS is_within
			FROM `loyalty_log` 
			WHERE `org_id` = '$org_id'
				AND DATE(`date`) = DATE('$upgrade_date')
				AND `user_id` = '$user_id'
			ORDER BY `date` ASC
			LIMIT 0,1
		";	
		
		return $this->db->query_scalar($sql);		
	}
	
	/*
	 * Raymond function to remove all the slab upgrade logs and 
	 * points awarded due to slab upgrade
	 */
	public function raymondRemoveSlabUpgradeAndAwardPoints($user_id){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			DELETE FROM `slab_upgrade_log` 
			WHERE `org_id` = '$org_id'
				AND `user_id` = '$user_id'
		";
		$res1 = $this->db->update($sql);
		
		$sql = "
			DELETE FROM `awarded_points_log`
			WHERE `org_id` = '$org_id'
				AND `user_id` = '$user_id'
				AND `notes` LIKE '%Slab Upgrade%'
		";
		$res2 = $this->db->update($sql);
		
		return $res1 && $res2;
		
	}
	
	public function setSlabForUser($user_id, $slab_name, $slab_no){
		
		$org_id = $this->currentorg->org_id;
		
		$sql = "
			UPDATE loyalty
			SET `slab_name` = '$slab_name',
				`slab_number` = '$slab_no'
			WHERE  
				publisher_id = '$org_id'
				AND `user_id` = '$user_id'
		";
		
		return $this->db->update($sql);
	}
	

    public function insertPerformanceCounter($perf_counters) {

        return true;
    }
    
    public function getCountriesLastModifiedDate()
    {
    	$sql = "
    		SELECT MAX(`last_updated`) FROM `masters`.`countries`
    	";
    	
    	return $this->db->query_scalar($sql);
    }

    public function getStoreAttributesLastModifiedDate()
    {
    	$org_id = $this->currentorg->org_id;
    	$store_id = $this->currentuser->user_id;
    	
    	$sql = "
    		SELECT MAX(`last_updated`) FROM `stores_info` WHERE org_id = '$org_id' AND store_id = '$store_id'
    	";
    	
    	return $this->db->query_scalar($sql);
    }
    
    private function createRegistrationValidationForm( &$validation_form ){
    	
		$validation_form->addField('hidden', 'shop', '', $shop);
		$validation_form->addField('text', 'mobile', 'Mobile Number', '', '', Util::$mobile_pattern, 'Mobile number should look like 9874400500 or 919874400500');
		
		$this->loyalty_mod->data['loyalty_register_code_form'] = $validation_form;
    }
    
    private function validateRegistrationValidationForm( &$validation_form ){
    	
		if( $validation_form->isValidated() ){
			
			$params = $validation_form->parse();
			$mobile = $params['mobile'];
			Util::checkMobileNumber($mobile);
			$user = UserProfile::getByMobile($mobile);

			if($user!=false){
				
				$msg = "Error in registering: Perhaps already registered";
				Util::redirect( 'loyalty', "details/$user->user_id", true, $msg, Util::genUrl('loyalty', 'add') );
				
			}else{
				
				$valid = new ValidationCode();
				$ret = $valid->issueValidationCode( $this->currentorg, $mobile,'what is your mobile number?',VC_PURPOSE_REGISTRATION,time(),-1 );
				
				if($ret!=false){
					
					Util::sendSms($mobile, "Registration Validation code is $ret", $this->currentorg->org_id, MESSAGE_PRIORITY);
					$this->flash("Validation code issued and sent by SMS to $mobile");
				}else
					$this->flash("Error");
			}
		}
    }
    
    public function getRegistrationAgeAndSexOption(){
    	
		$sex_options = array('Male' => 'm', 'Female' => 'f');
		$age_config = $this->getConfiguration(CONF_CLIENT_AGE_SLAB, '');

		if($age_config == '')
			$age_options = array('Below 18' => '0-18','18-25' => '18-25','25-Above');
		else{
			$ages = explode(',',$age_config);
			for($i = 0; $i < count($ages) - 1 ; $i++){
				$key = $ages[$i] . '-' . $ages[$i+1];
				$age_options[$key] = $key; 
			}
			$key = $ages[$i] . '-Above' ;
			$age_options[$key] = $key; 
		}
    	
		return array( $sex_options, $age_options );
    }
    
    private function createRegistrationForm( &$form, CustomFields $customFields, $sex_options, $age_options, $mobile, $user_id ){
    	
		if ($this->getConfiguration( CONF_LOYALTY_IS_REGISTRATION_VALIDATION_REQUIRED )){
			
			$mobileattr = array( 'readonly' => 'readonly' );
			$form->addField( 'text', 'validation_code', 'Validation Code','', '', '/[a-z0-9]+/i', 'Can not be empty' );
		}	
	
		$form->addField( 'text', 'mobile', 'Mobile Number(*)', $mobile, $mobileattr );	
		$form->addField( 'text', 'firstname', 'First Name(*)','', '', '/[a-z]+/i', 'Can not be empty');
		$form->addField( 'text', 'lastname', 'Last Name(*)', '', '', '/[a-z]+/i', 'Can not be empty');
		$form->addField( 'text', 'email', 'Email Address', '', '', Util::$optional_email_pattern, 'Email has to be valid');
		$form->addField( 'textarea', 'address', 'Address');
		$form->addField( 'datepicker', 'birthday', 'Birthday');
		$form->addField( 'radio', 'sex', 'Gender', '', $sex_options);
		$form->addField( 'datepicker', 'anniversary', 'Anniversary');
		$form->addField( 'datepicker', 'spouse_birthday', 'Spouse Birthday');
		$form->addField( 'select', 'age_group', 'Age Group', $age_options);
		
		$customFields->addCustomFieldsFormForAssocID( $form, $this->currentorg->org_id, LOYALTY_CUSTOM_REGISTRATION, $user_id );
		
		if ($this->getConfiguration(CONF_USERS_USE_EXTERNAL_ID)) 
			$form->addField('text', 'external_id', 'External ID / Card #');
	
		if ($this->isMLMEnabled()) {
			
			$form->addField('text', 'referrer_mobile', 'Referrer Mobile Number', '', '', Util::$mobile_pattern, 'Should be a valid mobile number');
			$this->loyalty_mod->js->addAutoSuggest( $form->getFieldName('referrer_mobile'), 'loyalty', 'getRegisteredUsersByMobileOrNameOrExternalId' );
		}
    }
    
    public function createRegisterForm( $customFields, &$registration_form, $mobile = '' ){
    	
		$this->logger->info("Register form");
		
		if ($this->getConfiguration( CONF_LOYALTY_IS_REGISTRATION_VALIDATION_REQUIRED )){
	
			$validation_form = new Form('loyalty_validation', 'post');
			
			$this->createRegistrationValidationForm( $validation_form );
			$this->validateRegistrationValidationForm( $validation_form );
		}
	
		list( $sex_options, $age_options ) = $this->getRegistrationAgeAndSexOption();
	
		$this->createRegistrationForm( $registration_form, $customFields, $sex_options, $age_options, $mobile, $user_id );
    	
		return $validation_form ;
    }

    public function validateRegisterForm( $registration_form, $customFields, $module_to_redirect = 'loyalty', $action_to_redirect = 'register' ){
    	
		if ( $registration_form->isValidated() ) {
			//register this person
	
			$params = $registration_form->parse();
			
			$mobile = $params['mobile'];
			$firstname = $params['firstname'];
			$lastname = $params['lastname'];
			$email = $params['email'];
			$address = $params['address'];
			$sex = $params['sex'];
			$spousebitrhday = $params['spouse_birthday'];
			$birthday = $params['birthday'];
			$anniversary = $params['anniversary'];
			$validationcode = $params['validation_code'];
			
			$external_id = $this->getConfiguration( CONF_USERS_USE_EXTERNAL_ID ) ? $params['external_id'] : NULL;
			$age_group = $params['age_group'];
	
			$user = UserProfile::getByMobile($mobile);
			if( $user ){

				$user_id = $user->user_id;
				$loyalty_id = $this->getLoyaltyId( $this->currentorg->org_id, $user_id );

				if ( $loyalty_id ){

					$msg = "Error in registering: Perhaps already registered";
					
					if( $module_to_redirect == 'loyalty' )
						Util::redirect( 'loyalty', "details/$user_id", true, $msg, Util::genUrl('loyalty', 'add') );
					else
						Util::redirect( $module_to_redirect, $action_to_redirect, false );
				}
			}else{
				
				$auth = Auth::getInstance();
				$this->logger->info( "User $mobile doesn't exist. Auto-registering" );
				$user_id = $auth->registerAutomaticallyByMobile( $this->currentorg, $mobile, $firstname, $lastname, $email );
				$user = UserProfile::getByMobile( $mobile );
			}
	
			if( $user_id ){
				
				$customFields->processCustomFieldsFormForAssocID($registration_form, $this->currentorg->org_id, LOYALTY_CUSTOM_REGISTRATION, $user_id);
				$id = $this->registerInLoyaltyProgram(
									$user,$external_id,$firstname,$lastname,$email,time(),true,$validationcode);
							
				$this->logger->info("Registered $user->user_id for loyalty program. Error code received: $id");
			}
			if ( $id > 0 && $user_id ) {

				$msg = "Registered successfully" ;
				$this->flash( $msg );
	
				if( $module_to_redirect != 'loyalty' )
					Util::redirect( $module_to_redirect, $action_to_redirect, false, $msg );
					
				if ($this->isMLMEnabled()) {
					$referrer = $params['referrer_mobile'] ? UserProfile::getByMobile( $params['referrer_mobile'] ) : NULL;
					//$this->mlm->addToMLM( $user, $referrer, true );
				}
	
				$base_store = new BaseStore( false, $id );
				$base_store->populateRegisteredStore( $this->currentuser->user_id );
			}else {
				$this->flash( "Error: ".$this->loyalty_mod->getResponseErrorMessage( $id ) );
			}
		}
    }
    
    public function validateOrderForm( Form $order_form ){
    	
    	if( $order_form->isValidated() ){
    		
    		$params = $order_form->parse();
    		
    		$order = $params['order'];
    		$json_array = array();
    		
    		foreach( $order as $o ){
    			
    			array_push( $json_array, $o );
    		}
    		
    		if( count( $json_array ) > 0 ){
    			
    			$json = json_encode( $json_array );
    			$this->currentorg->set( BASE_STORE_FORMULA_ORDER, $json );
    		}
    	}
    }
    
    public function validateStoreConfigForm( Form $store_config_form, array $inactive_stores ){
    	
    	$batch_array = array();
    	
		if( $store_config_form->isValidated() ){
			
			$params = $store_config_form->parse();
			
			foreach ( $inactive_stores as $value ){
				
				$replaced_store = $params[$value['store_id']];
				
				$store_id = $value['store_id'];
				array_push( $batch_array, "( '$store_id' ,'$replaced_store' )" ); 
			}
			
			$batch = implode( ',', $batch_array );
		}
    	
		$this->loyaltyModel->InsertResult();
		return $this->loyaltyModel->addStoreToReplaceForInactiveStores( $batch );
    }
    
    public function changeBaseStore( $loyalty_id ){
    	
    	$set_conf = json_decode( $this->getConfiguration( BASE_STORE_FORMULA_ORDER, false ), true );
    	
    	foreach( $set_conf as $rule_number ){

    		$this->logger->debug( ' check...' );
    		$baseStore = new BaseStore( $rule_number, $loyalty_id );
    		$baseStoreRule = $baseStore->loadRuleClass();
    		
    		list( $status, $go_to_next_step ) = $baseStoreRule->execute();
    		
    		if( !$status )
    			$this->logger->debug( ' Some Problem Occured Please Wait...' );
    			
    		if( $status && !$go_to_next_step ){
    			
				$baseStore = new BaseStore( 5, $loyalty_id );
    			$baseStoreRule = $baseStore->loadRuleClass();
    			break;    			
    		}
    			
    	}
    }
    
    public function validateBaseStoreForm( Form $repotingform ){
    	
    	if( $repotingform->isValidated() ){
    		
    		$params = $repotingform->parse();
    		
    		$mobile = $params['mobile'];
    		
    		if( $mobile ){
    			
    			$this->loyaltyModel->getScalarResult();
    			$loyalty_id = $this->loyaltyModel->getLoyaltyDetailsByMobile( $mobile );
    		}
    		
    		$start_date = $params['start_date'];
    		$end_date = $params['end_date'];
    		
    		if( $start_date > $end_date || !$loyalty_id )
    			Util::redirect( 'loyalty', 'basestore', 'check date or mobile' );
    			
    		$this->loyaltyModel->getQueryTableResult( 'result' );
    		return $this->loyaltyModel->getBaseChangeLog( $loyalty_id, $start_date, $end_date );
    	}
    	
    	
    }
    
    public function processBaseStore(){
    	
    	//get last 31 days loyalty log bills
    	$start_date = Util::getDateByDays( true, 31 );
    	$end_date = Util::getDateByDays( false, 1 );
    	
    	$this->loyaltyModel->getScalarResult();
    	$count = $this->loyaltyModel->getBillCountByDateRange( $start_date, $end_date );
    	
    	$loop = ceil( $count % 500 );
    	for( $i = 0 ; $i < $loop ; $i++ ){
    		
    		$limit = " $i, 500 ";
    		
    		$this->loyaltyModel->getQueryResult();
    		$bills = $this->loyaltyModel->getBillDetailsByDateRange( $start_date, $end_date, $limit );
    		
    		$loyalty_id = $bills['loyalty_id'];
    		$store_id = $bills['entered_by'];
    		
			$base_store = new BaseStore( false, $loyalty_id );
			$base_store->updateLoyaltyDetailsByBills( $store_id );
			$this->loyaltyController->changeBaseStore( $loyalty_id );
    		
    	}
    	
    }
    
	/**
	 * @param unknown_type $org_id
	 * @param unknown_type $filter
	 * @param unknown_type $type
	 * @deprecated
	 */
	public function getPerformanceTable($org_id, $filter, $type ='table'){

		if($type == 'table')
			return $this->pl_db->query_table("SELECT `s`.`username`,`p`.`store_id`,`p`.`counter_id`, `p`.`binary_version`, `p`.`counter_name`, `p`.`counter_date`, `p`.`min_time_taken`, `p`.`max_time_taken`, `p`.`total_time_taken`, `p`.`count` FROM `performance_logs`.`performance_counters_store` AS `p`,`user_management`.`stores` AS `s` where `p`.`store_id`=`s`.`store_id` and `s`.`org_id`='$org_id' $filter ", 'loginfoTable');
		else
			return $this->pl_db->query("SELECT `s`.`username`,`p`.`store_id`,`p`.`counter_id`, `p`.`binary_version`, `p`.`counter_name`, `p`.`counter_date`, `p`.`min_time_taken`, `p`.`max_time_taken`, `p`.`total_time_taken`, `p`.`count` FROM `performance_logs`.`performance_counters_store` AS `p`,`user_management`.`stores` AS `s` where `p`.`store_id`=`s`.`store_id` and `s`.`org_id`='$org_id' $filter ");
		
	}
	
	protected function getLoyaltyAndUserId( $mobile, $email = false, $external_id = false){
		
		//First check using the mobile 
		$loyalty_details = $this->getLoyaltyDetailsByMobile( $mobile, $this->currentorg->org_id );
		
		$this->logger->info( "Loyalty Details: ID:".$loyalty_details['id'].", User: ". $loyalty_details['user_id']);
		//If not use the external id
		if(!$loyalty_details && $external_id)
			$loyalty_details = $this->getLoyaltyDetailsByExternalId($external_id, $org_id);
		
		//Else use the email to check
		if(!$loyalty_details && $email)
			$loyalty_details = $this->getLoyaltyDetailsByEmail($email, $this->currentorg->org_id);
		
		
		if( count( $loyalty_details ) > 0 && $loyalty_details){
			
			return array( $loyalty_details['id'], $loyalty_details['user_id'] );
			
		}else{
			
			//Check if atleast the user exists and try each of the cases if that is available through one of those.
			
			if(Util::checkMobileNumber($mobile)) $user = UserProfile::getByMobile( $mobile, false );
			
			//if(!$user && $external_id) $user = UserProfile::getByExternalId($external_id);
			
			if(!$user && Util::checkEmailAddress($email)) $user = UserProfile::getByEmail($email);
			
			if($user) $user_id = $user->user_id;
			
			return array( false, $user_id );
		}	
	}
	
	protected function registerUserId( $mobile, $firstname, $lastname, $email = false, $external_id = false){
		
		$auth = Auth::getInstance();
		
		$this->logger->info( "User $mobile doesn't exist. Auto-registering" );
		
    $this->logger->debug("Registering by mobile: $mobile");
		$user_id = $auth->registerAutomaticallyByMobile( $this->currentorg, $mobile, $firstname, $lastname, $email );
		
    $this->logger->debug("*** found user_id $user_id ****");

		if(!$user_id && $external_id ){
      $this->logger->debug("***** Registering by external id: $external_id *****");  
			$user_id = $auth->registerAutomaticallyByExternalId($this->currentorg, $external_id, $first_name, $last_name, $email, $mobile);
    }			

		if(!$user_id && Util::checkEmailAddress($email)){
			$this->logger->debug("***** registering by email id: $email *****");
      $user_id = $auth->registerAutomaticallyByEmail( $this->currentorg, $email, $firstname, $lastname, $mobile );
    }		
		
    $this->logger->debug("***** user_id $user_id *****");
		return $user_id;
	}
	
	function updateLoyaltyUser( $user_id, $firstname, $lastname, $email = false, $external_id = false ){
		
		
		$user = UserProfile::getById( $user_id );
		if( !$user ) return false;
		$e = new ExtendedUserProfile( $user, $this->currentorg );
		
		$user->updateProfile( $firstname, $lastname, $email );
		
		//mail the updation details for some fuck up that has happened
		$subject = 'Store 21 update';
		$body = '<ul>'; 
		$send_email = false;
		if( ( strlen( $firstname ) > 0 || strlen( $lastname ) > 0 ) && ( $firstname != $e->getFirstName()  || $lastname != $e->getLastName() ) ){
			
			$send_email = true;
			$body .= ' <li>Name Changed From : '.$e->getFullName().' To : '.$firstname.' '.$lastname.'</li>';
		}	
		if( $e->getEmail() != $email ){
			
			$send_email = true;
			$body .= ' <li>Email Changed From : '.$e->getEmail().' To : '.$email.'</li>';
		}
			
		$existing_external_id = UserProfile::getExternalId( $user_id );
		if( $existing_external_id != $external_id ){
			
			$send_email = true;
			$body .= ' <li>External Id Changed From : '. UserProfile::getExternalId( $user_id ).' To : '.$external_id.'</li>';
		}
		
		$body .= ' <li>Updated By : '.$this->currentuser->username.'</li>';
		$body .= ' </ul> ';
		
		$intouh_emails = 'ukops@capillary.co.in ';

		if( $this->currentorg->org_id == 189 && $send_email )
			Util::sendEmail( $intouh_emails, $subject, $body, 189 );
		
		if( strlen( $firstname ) > 0 )
			$e->setFirstName( $firstname );
		if( strlen( $lastname ) > 0 )
			$e->setLastName( $lastname );
		
		if( $email && Util::checkEmailAddress( $email ) ) $e->setEmail($email);
		
		if( $external_id ) UserProfile::setExternalId($user_id, $external_id);
		
		$e->save();
	}
	
	public function updateCustomerForJavaClient( $customer ){
		
		$user_id = (int) $customer->customer_id;
		$customer_name = (string) $customer->customer_name;
		$external_id = (string) $customer->external_id;
		if(strlen($external_id) == 0) $external_id = (string) $customer->externalId;
		$email = (string)$customer->email;
		
		list( $firstname, $lastname ) = array('', '');
		
		if($customer_name != '' || $customer_name != null)
			list( $firstname, $lastname ) = Util::parseName( $customer_name );
		
		
		$customer_mobile = (string)$customer->mobile;
		
		$user = UserProfile::getById( $user_id );
		$old_mobile = $user->mobile;
		
		if($customer_mobile && $customer_mobile != '' && Util::checkMobileNumber($customer_mobile))
			$this->changeMobileNumber($user, $old_mobile, $customer_mobile);
		
		$user = UserProfile::getById( $user_id );
		if(!$user) return false;
		
		$e = new ExtendedUserProfile( $user, $this->currentorg );
	
		//mail the updation details for some fuck up that has happened
		$subject = 'Store 21 update';
		$body  = ' <ul><li> Mobile Changed From : '.$old_mobile .' to '.$customer_mobile.'</li>';
		$body .= ' <li>Name Changed From : '.$e->getFullName().' To : '.$customer_name.'</li>';
		$body .= ' <li>Email Changed From : '.$e->getEmail().' To : '.$email.'</li>';
		$body .= ' <li>External Id Changed From : '. UserProfile::getExternalId( $user_id ).' To : '.$external_id.'</li>';
		$body .= ' <li>Updated By : '.$this->currentuser->username.'</li>';
		$body .= ' </ul> ';
		
		$intouh_emails = '
			sandeep.prakash@capillary.co.in, keshav.kunwar@dealhunt.in, 
			pavan.prasad@capillary.co.in, ramkumar@capillary.co.in, vinay.hm@capillary.co.in ';
		
//		Util::sendEmail( $intouh_emails, $subject, $body, 189 );
		
		$user->updateProfile( $firstname, $lastname, $email );
		
		$e->setFirstName( $firstname );
		$e->setLastName( $lastname );
		
		if($email && Util::checkEmailAddress($email)) $e->setEmail($email);
		
		if($external_id) UserProfile::setExternalId($user_id, $external_id);
		
		return $e->save();
	}
	
	public function getLoyaltyIdByMobileForAddBills( $mobile, $name, $customer_info, $billing_time ){
		
                global $logger;
		list( $firstname, $lastname ) = array( '', '' );
		
		if( $name != '' && $name != null )
			list( $firstname, $lastname ) = Util::parseName( $name );
		
                $new_customer = true;

		$firstname = ucwords( strtolower( $firstname ) );
		$lastname = ucwords( strtolower( $lastname ) );
		
		$loyalty_id = $loyalty_details['id'];
		$user_id = $loyalty_details['user_id'];
		$email = (string)$customer_info->email;
		$external_id = (string)$customer_info->external_id;
		
		list( $loyalty_id, $user_id ) = $this->getLoyaltyAndUserId( $mobile, $email, $external_id );
		
		$logger->debug("loyaltyid: $loyalty_id, user_id $user_id");
		
		if( $loyalty_id && $user_id ){
			
			$logger->debug("user exists, updating profile");
			$this->updateLoyaltyUser( $user_id, $firstname, $lastname, $email, $external_id );
			$new_customer = false;
		}else{
			if( !$user_id ){
				
        		$logger->debug("registering user in users table");
				$user_id = $this->registerUserId( $mobile, $firstname, $lastname , $email, $external_id);
			}
			if( $user_id ){

				$user = UserProfile::getById( $user_id );
				$logger->debug("*** customer info: " . print_r($customer_info, true));

				$loyalty_id = $this->registerInLoyaltyProgram( $user, $external_id, $firstname, $lastname, $email ,$billing_time, true, '', $customer_info);
        		$logger->debug("registering in loyalty program with loyalty_id: $loyalty_id");
			}

                     $new_customer = true;   
		}

		$base_store = new BaseStore( false, $loyalty_id );
		$base_store->populateRegisteredStore( $this->currentuser->user_id );
		
		return array($loyalty_id, $user_id, $new_customer);
	}
	
	public function addUser($mobile, $name, $email,$external_id, $billing_time, $customer_info = null)
	{
		global $logger;
		list( $firstname, $lastname ) = array('', '');
		
		if($name != '' && $name != null)
			list( $firstname, $lastname ) = Util::parseName( $name );
		
		$firstname = ucwords(strtolower($firstname));
		$lastname = ucwords(strtolower($lastname));
		
        $logger->debug("registering user in users table");
		$user_id = $this->registerUserId( $mobile, $firstname, $lastname , $email, $external_id);
		if($user_id) 
		{
			$user = UserProfile::getById( $user_id );
			$logger->debug("*** customer info: " . print_r($customer_info, true));
       		$loyalty_id = $this->registerInLoyaltyProgram( $user, $external_id, $firstname,
       		 $lastname, $email ,$billing_time, true, '', $customer_info);
        	$logger->debug("registering in loyalty program with loyalty_id: $loyalty_id");
		}

		$base_store = new BaseStore( false, $loyalty_id );
		$base_store->populateRegisteredStore( $this->currentuser->user_id );
		
		return array($loyalty_id, $user_id);
	}
	
	public function getLoyaltyStatus($loyalty_id){
		
		if($loyalty_id < 0 )
			$key = ERR_LOYALTY_REGISTRATION_FAILED;
		else if($loyalty_id == -4000)
			$key = ERR_LOYALTY_LISTENER;
		
		$item_status = array(
			'key' => $this->loyalty_mod->getResponseErrorKey($key),
			'message' => $this->loyalty_mod->getResponseErrorMessage($key)
		);
		
		return $item_status;
		
	}

	public function GetNumRegsAndBillsTodayForStore( $store_id = '' )
	{
		
		global $logger;

		if(!$store_id)
			$store_id = $this->user_id;

		$mem_cache_manager = MemcacheMgr::getInstance();

		$bills_cache_key = "o".$this->org_id."_". sprintf("%s_o%d_s%d_d%d", CacheKeysPrefix::$loyaltyStoreBillsCounterKey, 
							 $this->org_id, $store_id, date('d'));
		$regs_cache_key  = "o".$this->org_id."_". sprintf("%s_o%d_s%d_d%d", CacheKeysPrefix::$loyaltyStoreRegsCounterKey, 
							 $this->org_id, $store_id, date('d'));
		$bills_ttl = CacheKeysTTL::$loyaltyStoreBillsCounterKey;
		$regs_ttl  = CacheKeysTTL::$loyaltyStoreRegsCounterKey;
		
		try{

			$num_bills = $mem_cache_manager->get($bills_cache_key);
			$num_regs  = $mem_cache_manager->get($regs_cache_key);
			$logger->debug("Getting NumRegsAndBillsTodayForStore from cache. key=$bills_cache_key, $regs_cache_key val=$num_bills, $num_regs");
			return array($num_regs, $num_bills);
		}
		catch (Exception $e){
			
			// not in cache OR srvr err
			$logger->debug("Getting NumRegsAndBillsTodayForStore from db");
			$this->loyaltyModel->getScalarResult();
			$num_regs_today = $this->loyaltyModel->getNumRegsTodayForStore( $store_id );
			$this->loyaltyModel->getScalarResult();
			$num_bills_today = $this->loyaltyModel->getNumBillsTodayForStore( $store_id );
			
			try{
				// put in cache
				if($e->getCode() !== 41414){
					$mem_cache_manager->set($regs_cache_key, $num_regs_today, $regs_ttl);
					$mem_cache_manager->set($bills_cache_key, $num_bills_today, $bills_ttl);
				}
			}
			catch(Exception $e1){
				$this->logger->error($e1->getMessage());
			}

			//if(($e->getCode() == 41414) && ($num_regs != $num_regs_today || $num_bills != $num_bills_today))
				//Util::sendEmail("gaurav.m@dealhunt.in", "NumRegsAndBillsTodayForStore cache-db mismatch", "reqid=$_SERVER[UNIQUE_ID]; store=$store_id; res=$num_regs, $num_bills; num_regs_today=$num_regs_today; num_bills_today=$num_bills_today", $this->org_id);

			return array($num_regs_today, $num_bills_today);
		}
		
	}
	
	public function getstorereportfordaterange($from_date, $to_date)
	{
		$store_id = $this->currentuser->user_id;

		//get loyalty bills based info
		$store_report_info = $this->loyaltyModel->getLoyaltyBillsBasedStoreReportInfo($store_id, $from_date, $to_date);
		
		//get non loyalty bills based info
		$store_report_info = array_merge($store_report_info, 
			$this->loyaltyModel->getNonLoyaltyBillsBasedStoreReportInfo($store_id, $from_date, $to_date));
			
		//get loyalty profile based info
		$store_report_info = array_merge($store_report_info, 
			$this->loyaltyModel->getLoyaltyProfileBasedStoreReportInfo($store_id, $from_date, $to_date));
		
		return $store_report_info;
	}
	
	/**
	  Adding this function separately and not using the getLoyaltyIdForAddBills
	  as that tries to set the base store etc which is not needed.
	**/
	public function registerWebCustomer( $mobile, $name, $customer_info ){
	
		$new_customer = false;
		list( $firstname, $lastname ) = Util::parseName( $name );
		$loyalty_id = $loyalty_details['id'];
		$user_id = $loyalty_details['user_id'];
		$email = (string)$customer_info->email;
		$external_id = (string)$customer_info->external_id;
		
		list( $loyalty_id, $user_id ) = $this->getLoyaltyAndUserId( $mobile, $email, $external_id );
		
		if( $loyalty_id && $user_id ){
			$this->updateLoyaltyUser( $user_id, $firstname, $lastname, $email, $external_id );
		}else{
			$user_id = $this->registerUserId( $mobile, $firstname, $lastname , $email, $external_id);
			
			if($user_id) {
				$user = UserProfile::getById( $user_id );
				$loyalty_id = $this->registerInLoyaltyProgram( $user, $external_id, $firstname, $lastname, $email ,time());
				$new_customer = true;
			}			
		}
	
		return array($loyalty_id, $user_id, $new_customer);
	}

  //simply returns the listener id to be used with signalSingleListener method
  public function getListenerId($listener_name){
    $sql = "SELECT id FROM listeners WHERE org_id = " . $this->currentorg->org_id . " AND listener_name = '$listener_name' AND end_time > NOW() ORDER BY id DESC LIMIT 1";
    return $this->db->query_scalar($sql);   
  } 

  /**
   * @author nayan
   * This method is used to process external request and call appropriate listener for loyalty listener.
   */
  public function processExternalRequestForApi( $mobile , $request ){
  			
			$customer_first_name = trim((string)$request->customer_first_name);
			$customer_last_name = trim((string)$request->customer_last_name);
			$customer_email = trim((string)$request->customer_email);
			$req_context = trim((string)$request->req_context);
			$req_context_info = trim((string)$request->req_context_info);
  			  	
  			if(	$mobile	)
				$user = UserProfile::getByMobile( $mobile );
			else
				$user = UserProfile::getByEmail( $customer_email );	
			
			$user_id = $user->user_id;
			
			$this->logger->debug('Step 1: User ID:'.$user_id);
			
			if( !$user_id ){
				$mobile = Util::checkMobileNumber($mobile) ? $mobile : '';
				$customer_email = Util::checkEmailAddress($customer_email) ? $customer_email : '';
				
				$this->logger->debug('Step 2: User ID:'.$user_id.' ,Email ID:'.$customer_email);
				$user_id = $this->registerUserId( $mobile, $customer_first_name, $customer_last_name ,$customer_email);
			}
			
		    //Always call
			$this->logger->debug("Customer will be added in Loyalty Program through Listener by firing the External Event...");
			         	
		    $supplied_data['user_id'] = $user_id;
		    $supplied_data['store_id'] = $this->currentuser->user_id;
			$supplied_data['firstname'] = $customer_first_name;
			$supplied_data['lastname'] = $customer_last_name;
			$supplied_data['req_context'] = $req_context;
			$supplied_data['req_context_info'] = $req_context_info;
			$supplied_data['email_id'] = $customer_email;
			//call the listener to register user in loyalty program
			$status = $this->listenersManager->signalListeners(EVENT_EXTERNAL,$supplied_data);
			$this->logger->debug("Signalled the listener");
			return $status;
  }
  
	/*
	 * JUNK CODE TODO : REMOVE LATER
	 * */
 	public function getfacebookcheckins()
	{
		//TOOD : Add store id filter
		$fb_db = new Dbase('facebook');
		$sql = "
			SELECT fbc.*, fbu.user_id
			FROM `fb_checkin` fbc
			LEFT OUTER JOIN `fb_user` fbu ON fbu.org_id = fbc.org_id AND fbc.fb_user_id = fbu.fb_node_id 
			WHERE fbc.org_id = $this->org_id
		";
		return $fb_db->query($sql);	
	}
  
		/**
	 * @param $startdate
	 * @param $enddate
	 * @param $zone_selected
	 * @param $outtype
	 * @param $attrs
	 * @return unknown_type
	 */
// 	function getSlabUpgradeTableDump($startdate, $enddate, $zone_selected = false, $outtype = 'query', 
// 				$attrs = array(),$cat_id = false,$store_selected=false){

// 		$org_id = $this->currentorg->org_id;

// 		$zone_filter = "";
// 		$am = new AdministrationModule();
// 		if($zone_selected != false){

// 			//create a filter out of the sub zones for the zone selected
// 			$zone_filter = $am->createZoneFilter($zone_selected);

// 			$zone_filter = (" JOIN stores_zone z ON 1 = 1 ".$am->getModifiedZoneFilter('`sl`.`upgraded_by`', $zone_filter));
// 		}
		
// 		$store_filter="";
// 		if($store_selected != false){
// 			$store_filter = " AND sl.upgraded_by IN (".Util::joinForSql($store_selected).") ";
// 		}
		
// 		if($cat_id){
// 			$database_msging = Util::dbName('msging',$this->testing);
// 			$cat_filter = " JOIN `$database_msging`.`subscriptions` AS `su` ON 
// 						( `su`.`publisherId` = `u`.`org_id` AND `su`.`subscriberId` = `u`.`user_id` AND `su`.`categoryId` IN ($cat_id) ) ";
// 		}
		
// 		$enddate = Util::getNextDate($enddate);
		
// 		// A temporary fix remove it for org_id = 0
// 		$sql = " SELECT sl.loyalty_id, TRIM(CONCAT(u.firstname,' ',u.lastname)) AS customer, u.user_id, 
// 					u.mobile AS customer_mobile, sl.from_slab_name, sl.to_slab_name, sl.ref_bill_number AS reference_bill_number, 
// 					sl.notes, sl.upgrade_time, s.username AS upgraded_by, sl.upgrade_bonus_points" .
// 				" FROM slab_upgrade_log sl " .
// 				" JOIN extd_user_profile u ON sl.user_id = u.user_id AND sl.org_id = u.org_id " .
// 				" JOIN stores s ON sl.upgraded_by  = s.store_id".
// 				$zone_filter .
// 				$cat_filter .
// 				" WHERE sl.org_id = $org_id AND sl.upgrade_time BETWEEN DATE('$startdate') AND DATE('$enddate') $store_filter";
	
// 		$limit_sql = $sql . ' LIMIT 100';	
// 		if($outtype == 'query_table')
// 		return array($sql,$this->db->query_table($limit_sql, $attrs['name']));

// 		return array($sql,$this->db->query($limit_sql));
// 	}

	/**
	  Function for adding the vouchers which have already been  	
	  redeemed for this bill
	**/
	public function addRedeemedVouchersForBill($redeemed_vouchers, $bill_number, $bill_amount, $user_id, $billing_time)	
	{
		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$isql = "
			INSERT INTO luci.voucher_redemptions(org_id, voucher_series_id, voucher_id, used_by, used_date, used_at_store,
								 bill_number, counter_id, bill_amount)
			VALUES 
			";
		$org_id = $this->currentorg->org_id;
		$sql = "";
		foreach($redeemed_vouchers as $vc)
		{
			$safe_vc = Util::mysqlEscapeString($vc);
		   $voucher = Voucher::getVoucherFromCode($safe_vc, $org_id);
		   if( $voucher && $voucher->getVoucherId() > 0)  //voucher actually exists	 	 
		   {		
		      $this->logger->debug("populating for voucher: $vc");	
		      $vch_series = $voucher->getVoucherSeries();
		      $series_id = $vch_series->id;
		      $voucher_id = $voucher->getVoucherId();
		      $store_id = $this->currentuser->user_id;
				
		      $sql .= "($org_id, $series_id, $voucher_id, $user_id, '$billing_time', $store_id, '$safe_bill_number', $store_id, $bill_amount),";	
		   }	
		}

		$sql = rtrim($sql, ",");	
		$isql = $isql . $sql;

		$this->logger->debug("Final voucher insert query: $isql");

		$db = new Dbase('campaigns');
		$db->insert($isql);
 	}
 	
	public function updateLineItemReferencesForBill($bill_id){
        return true;
    }
	
	function getCustomFieldsFeedbackReport( $startdate, $enddate, $scope = false, $outtype = 'query_table', 
					$attrs = array(),$cat_id = false,$store_selected=false){

		$org_id = $this->currentorg->org_id;
		
		$view_in_table = $attrs['show_table'];
		
		$limit = "";
		if( $view_in_table )
			$limit = " LIMIT ".$attrs['show_table'];
			
		$enddate_next = util::getNextDate($enddate);
		
		$cf = new CustomFields();
		$option = $cf->getCustomFieldsByScope($org_id, $scope );
		
		$cf_ids = array();
		foreach( $option AS $o ){
			
			array_push($cf_ids, $o['id']);
		} 
				
		//********************//
		if($cf_ids){
			$cf_ids = implode(',', $cf_ids);

			$select_filter = " , GROUP_CONCAT( `cf`.`name` ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Name`, 
								GROUP_CONCAT( CASE WHEN `cfd`.`value` IS NULL THEN '[\"NA\"]' ELSE `cfd`.`value` END  ORDER BY `cf`.`id` SEPARATOR '^*^') AS `Custom-Field-Value`";
			
			$join_filter = "
								JOIN `custom_fields` AS `cf` ON 		
									( `cf`.`org_id` = `u`.`org_id` AND `cf`.`id` IN ( $cf_ids )  )
								JOIN `custom_fields_data` AS `cfd` ON 
									( `cfd`.`org_id` = `u`.`org_id` AND `u`.`id` = `cfd`.`assoc_id` AND `cf`.`id` = `cfd`.`cf_id` 
									  AND `cfd`.`modified` BETWEEN DATE('$startdate') AND DATE('$enddate') )
							
									";
			$group_filter = "GROUP BY u.id";
		}

		$order_filter='';
		$primary_key_filter='';
		if($outtype == 'query_table'){
			$primary_key_filter=' {{where}} ';
			$order_filter=' {{order}} ';	 
		}
		
		$sql = " SELECT u.id as user_id,CONCAT(u.`firstname`,u.`lastname`) AS customer_name,
				 	   u.`mobile` AS customer_mobile , u.`email` AS customer_email $select_filter
				 FROM user_management.`users` u 
				 $join_filter  
				 WHERE u.org_id = $org_id $primary_key_filter 
				 $group_filter $order_filter $limit ";
		
		if( !$view_in_table && $outtype == 'query_table' )
			return array( $sql , array() , 'u.id');
			
		$query = $this->db->query($sql);
		
		$custom_value = array();
		foreach($query as $q){
				$store_data = array(
					'user_id' => $q['user_id']
				);
				//custom field name and the counts are in group concat format 
				$cf_names = explode('^*^', $q['Custom-Field-Name']);
				$cf_value = explode('^*^', $q['Custom-Field-Value']);
						
				for($i = 0; $i < count($cf_names); $i++){					
					//Store as ret_val_<cf_index>
					$cf_value[$i] = json_decode($cf_value[$i], true);
					$index = 'ret_val_'.$i;
					$store_data[$index] =  is_array($cf_value[$i]) ? implode(',' ,$cf_value[$i]) : $cf_value[$i];
					
				}
				array_push($custom_value, $store_data);
				$custom_field_names = $cf_names;
		}
				
		$table = new Table('custom');
		$table->importArray($query);
		$table->removeHeader('Custom-Field-Name');
		$table->removeHeader('Custom-Field-Value');
				
		$q_new = array(
			'name' => $custom_field_names,
			'ret' => $custom_value
		);						
		
		function addRow($row,$params){
			
			foreach($params['ret'] as $param){
				if($param['user_id'] == $row['user_id']){
					$return_val_all = array();	
					for($i = 0; $i < count($params['name']); $i++){
						$val = "ret_val_".$i;
						$return_val_all[$i] = $param[$val];
					}
					return $return_val_all;
				}
			}
		}
		
		if(count($q_new['name']) != 0){
			$table->addManyFieldsByMap($q_new['name'], 'addRow' ,$q_new);
		}

		if( $outtype == 'query_table' )
			return array( $sql , $table );
		else
			return array( $sql , $table->getData() , false);
			
	}
	
	public function loadInventoryInfoForLineitems($lineitems)
	{
		$arr_attributes = array();
		$arr_attr_values = array();
		foreach($lineitems AS $li)
		{
			$inventoryInfo = $li->getInventoryInfo();
			foreach($inventoryInfo AS $key => $value)
			{
				$arr_attr_values[] = $value;
				if(isset($arr_attributes[$key]))
					continue;
				$arr_attributes[$key] = $key;
			}
		}
		$this->inventory->loadAttaibutesForInventoryInfo($arr_attributes);
		$this->inventory->loadAttributeValuesForInventoryInfo($arr_attr_values);
	}

	//gets all the return bills during a loyalty transaction
	public function getReturnsInLoyaltyTransaction($user_id, $loyalty_log_id)
	{
		if (empty($user_id))
			return array(); 
		if (empty($loyalty_log_id))
			return array(); 
		
		$org_id = $this->currentorg->org_id;
		$sql = "SELECT
				rb.id AS return_bill_id,                  
				rb.bill_number AS bill_number,           
				rb.credit_note AS credit_notes,          
				rb.amount AS bill_amount,
				rb.points AS points,      
				rb.store_id AS store_id,             
				rb.returned_on AS returned_on,  
				rb.loyalty_log_id AS loyalty_log_id,       
				rb.parent_loyalty_log_id as parent_loyalty_log_id,
				rb.type as type,
				tds.delivery_status, 
				rb.notes as notes,
				
				rbl.id AS return_lineitem_id,
				rbl.serial AS serial,
				rbl.item_code AS item_code,
				rbl.rate AS rate,
				rbl.qty AS qty,
				rbl.value AS value,
				rbl.discount_value AS lineitem_discount_value,
				rbl.amount AS lineitem_amount,
				rbl.points AS lineitem_points
				
				FROM returned_bills AS rb 
				LEFT OUTER JOIN transaction_delivery_status AS tds 
					ON tds.transaction_id = rb.id 
					AND tds.transaction_type = 'RETURN' 
				LEFT JOIN returned_bills_lineitems AS rbl 
				ON rbl.return_bill_id = rb.id
				
				WHERE rb.org_id = $org_id
				AND rb.parent_loyalty_log_id = $loyalty_log_id
				AND rb.user_id = $user_id 
				ORDER by rb.id, rbl.id ";
		
		$returnedItemsArr = $this->db->query($sql);
		
		$this->logger->debug("Fetched the returned ".count($returnedItemsArr)." line items");
		$ret = array();	
		foreach($returnedItemsArr as $lineitem)
		{
			$bill_id = $lineitem["return_bill_id"]; 
			$ret[$bill_id]["return_bill_id"] 	= $lineitem["return_bill_id"]; 
			$ret[$bill_id]["bill_number"] 		= $lineitem["bill_number"];
			$ret[$bill_id]["credit_notes"] 		= $lineitem["credit_notes"];
			$ret[$bill_id]["bill_amount"] 		= $lineitem["bill_amount"];
			$ret[$bill_id]["points"] 			= $lineitem["points"];
			$ret[$bill_id]["store_id"] 			= $lineitem["return_bill_id"];
			$ret[$bill_id]["returned_on"] 		= $lineitem["returned_on"];
			$ret[$bill_id]["loyalty_log_id"] 	= $lineitem["loyalty_log_id"];
			$ret[$bill_id]["parent_loyalty_log_id"] = $lineitem["parent_loyalty_log_id"];
			$ret[$bill_id]["type"] 				= $lineitem["type"];
			$ret[$bill_id]["notes"] 			= $lineitem["notes"];

			if($lineitem["return_lineitem_id"])
			{
				$this->logger->debug("The bill has line item info $bill_id");
				$info = array();
			
				$info["return_lineitem_id"] = $lineitem["return_lineitem_id"];
				$info["serial"] 			= $lineitem["serial"];
				$info["item_code"] 			= $lineitem["item_code"];
				$info["rate"] 				= $lineitem["rate"];
				$info["qty"] 				= $lineitem["qty"];
				$info["value"] 				= $lineitem["value"];
				$info["lineitem_discount_value"] = $lineitem["lineitem_discount_value"];
				$info["lineitem_amount"] 	= $lineitem["lineitem_amount"];
				$info["lineitem_points"] 	= $lineitem["lineitem_point"];
				
				$ret[$bill_id]["lineitems"][] = $info;
			}
			else
				$this->logger->debug("The return bill #$bill_id has no line items");
		}
		
		$this->logger->debug("The count of the return bill are ".count($ret));
		// return all the returns bills
		return array_values($ret);
	}
        
        public function isAuthorizedByMissedCall(UserProfile $user, $mark_used = false)
        {
            $cm = new ConfigManager();
            $otp_validity_interval = $cm->getKey('OTP_CODE_VALIDITY');
            
            if(! $user->mobile)
                return ERR_LOYALTY_MISSED_CALL_NOT_RECIEVED;
            
           try 
            {
                $this->logger->debug("Loading action authorize_redemption");
                $authorizeRedemptionAction = IncomingInteractionActions::loadByCode("authorize_redemption");
            }
            catch (Exception $e)
            {
                $this->logger->debug("No action authorize_redemption");
                return ERR_LOYALTY_MISSED_CALL_NOT_RECIEVED;
            }  
            
            try
            {
                $this->logger->debug("Loading source mappings mapped to loyalty::authorizeByMissedCallAction");
                $authorizeByMissedCallSourceMappings = SourceMapping::loadByAction($this->currentorg->org_id, $authorizeRedemptionAction->getId());
            }
            catch (Exception $e)
            {
                $this->logger->debug("No source mappings for authorizeRedemptionWithMissedCall");
                return ERR_LOYALTY_MISSED_CALL_NOT_RECIEVED;
            }
            
            try
            {
                $this->logger->debug("Loading recent interactions of the user");
                $recentUnusedInteractions = IncomingMobileInteraction::loadUnusedFromIntervalBySenderInInterval($user->mobile, $otp_validity_interval);
            }
            catch (Exception $e)
            {
                $this->logger->debug("No recent missed calles recieved");
                return ERR_LOYALTY_MISSED_CALL_NOT_RECIEVED;
            }
            
            $ret = ERR_LOYALTY_MISSED_CALL_NOT_RECIEVED;
            foreach($authorizeByMissedCallSourceMappings as $mapping)
            {
                foreach($recentUnusedInteractions as &$interaction)
                {
                    if(in_array($mapping->getId(), explode(',', $interaction->getTriggeredMappings())))
                    {
                        $this->logger->debug("Authorizing by missed call");
                        $ret = true;
                        if($mark_used)
                        {
                            $this->logger->debug("Marking interaction as used");
                            $interaction->setIsUsed(1);
                            $interaction->save();
                        }
                    }
                }
            }
            $this->logger->debug("returning $ret");
            
            return $ret;  
        }
        
        private function canSkipValidationCode()
        {
            $cm = new ConfigManager();
            $headers = apache_request_headers();
            if($cm->getKey('CONF_ALLOW_POINTS_REMEPTION_VALIDATION_OVERRIDE') != true)
            {
                return ERR_LOYALTY_SKIP_VALIDATION_NOT_ALLOWED;
            }
            if(empty($headers['X-CAP-CLIENT-SIGNATURE']))
            {
                return ERR_LOYALTY_CLIENT_SIGNATURE_MISSING;
            }
            return true;
        }
        
        function getRedemptions($org_id, $user_id){
        	$org_id = $org_id;
        	$sql = "SELECT *
        	FROM loyalty_redemptions lr
        	WHERE org_id = $org_id AND user_id = $user_id
        	GROUP BY entered_by, date
        	ORDER BY entered_by, date";
        	return $this->db->query($sql);
        }
        
        /**
         * get the  tender details and format if for EMF call 
         * @param unknown_type $loyalty_log_id
         */
        function getTenderDetailsForPE($loyalty_log_id, $transactionType = null)
        {
        	include_once ("models/PaymentModeDetails.php");
        	try{
				$filters = new PaymentModeLoadFilters();
				$filters->ref_id = $loyalty_log_id;
				if (isset($transactionType)) {
					$filters -> ref_type = $transactionType;
				}
				$tenders = PaymentModeDetails::loadAll($this->org_id, $filters, 100, 0, true);
				$tendersArr = array();
				foreach($tenders as $tender){
					try{
						$tender->loadPaymentModeAttributeValues();
						foreach($tender->getPaymentModeAttributeValues() as $attr_values){
							$attr_values->getOrgPaymentModeAttributeObj(true);
						}
					} catch (Exception $e){
						$this->logger->debug("Attribute and Attribute values not found for payment details");
					}
					$tendersArr[] = $tender;
				}
				$this->logger->debug("Successfully fetched the payment details");

				$ret = array();
				foreach($tendersArr as $paymentDetails)
				{
					if($paymentDetails->getAmount())
					{
						$arr = $paymentDetails->toArray();
						$arr["payment_mode_name"] = $paymentDetails->getOrgPaymentModeObj()->getPaymentModeObj()->getName();
						$arr["org_payment_mode_name"] = $paymentDetails->getOrgPaymentModeObj()->getLabel();
						//$tenderObj->tenderName 	= $payment_details_row["payment_mode_name"];
						//$tenderObj->orgTenderName = $payment_details_row["org_payment_mode_name"];

						$arr["attributes"] = array();
						foreach($paymentDetails->getPaymentModeAttributeValues() as $value)
						{
							if($value->getPaymentModeAttributeValueId())
							{
								$valueAssoc = $value->toArray();

								$valueAssoc["org_payment_mode_attribute_name"] = $value->getOrgPaymentModeAttributeObj()->getName();
								$valueAssoc["data_type"] = $value->getOrgPaymentModeAttributeObj()->getDataType();
								$arr["attributes"][] = $valueAssoc;
							}
						}

						if($paymentDetails->getOrgPaymentModeId() > 0)
						$ret[] = $arr;
					}
				}
				return $ret;
        	} catch (ApiPaymentModeException $e){
				$this->logger->debug("Could not find payment details");
				return array();
			}
        	
        	
        }
        
        
        public static function compareWithPrecision($bill_amount,$return_amount){
            if($bill_amount>$return_amount){
                return false;
            }
            if($return_amount-$bill_amount>0.01){
                return true;
            }
            return false;
        }
}
?>
