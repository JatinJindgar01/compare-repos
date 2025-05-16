
<?php
include_once 'helper/coupons/CouponManager.php';

define("VOUCHER_ERR_NOT_TRANSFERRABLE", "-10, Voucher is not meant for this user");
define("VOUCHER_ERR_ALREADY_USED", "-20, Voucher is already used");
define("VOUCHER_ERR_VCH_SERIES_VALID_DATE", "-30, Voucher Series / Campaign has expired");
define("VOUCHER_ERR_VALID_CREATE_DAYS", "-40, Voucher has expired");
define("VOUCHER_ERR_REDEMPTIONS", "-50, Campaign over, it was first come first serve");
define("VOUCHER_ERR_INVALID_ORG", "-60, Invalid organization");
define("VOUCHER_ERR_CANNOT_REDEEEM_MULTIPLE", "-70, Cannot redeem same voucher multiple times");
define("VOUCHER_ERR_BILL_NUMBER_NEEDED", "-80, Bill number needed for redemption");
define("VOUCHER_ERR_INVALID_VOUCHER_CODE", "-90, Voucher code does not exist");
define("VOUCHER_ERR_TEST_VOUCHER_CODE", "-100, Voucher is a test voucher. Cannot be redeemed");
define("VOUCHER_ERR_INVALID_USER", "-110, Invalid User");
define("VOUCHER_ERR_UNABLE_TO_CREATE", '-120, Unable to create voucher');
define("VOUCHER_ERR_NOT_ISSUED_TO_CUSTOMER", '-130, Voucher not issued to this customer');
define("VOUCHER_ERR_COMMUNICATION", '-140, Unable to communicate information to customer');
define("VOUCHER_ERR_VALIDATION_REQUIRED", '-150, Validaiton required');
define("VOUCHER_ERR_VALIDATION_NOT_REQUIRED", '-160, Validaiton not required');
define("VOUCHER_ERR_MAX_REDEMPTIONS_REACHED", '-170, Maximum number of redemptions reached');
define("VOUCHER_ERR_VCH_INVALID_REDEMPTION_RANGE", '-180, Invalid Redemption Range For this Voucher Series');
define("VOUCHER_ERR_UNKNOWN", "-1000, Unknown error");
define("VOUCHER_ERR_SUCCESS", "0, Success!");
define("VOUCHER_ERR_NO_MISSED_CALL", "-189, No missed call received from this user");
define("VOUCHER_ERR_INVALID_BILL_AMOUNT", "-2123, Invalid bill amount");
define("VOUCHER_ERR_INVALID_STORE", "-1111, Invalid Store");
define("VOUCHER_ERR_ALLOWED_REDEMPTION_EXAUSTED", "-2222, Allowed redemption exausted");
define("VOUCHER_ERR_INVALID_REDEMPTION_GAP", "-2223, Allowed redemption gap is invalid");
define("VOUCHER_ERR_INVALID_REDEMPTION_DATE", "-2224, Allowed redemption date is invalid");
define("VOUCHER_ERR_FRAUD_USER", "-101, Fraud User");

$GLOBALS["vouchers_error_message"] = array (
	'VOUCHER_ERR_SUCCESS' => 'Success!',
	'VOUCHER_ERR_NOT_TRANSFERRABLE' => 'Voucher is not meant for this user',
	'VOUCHER_ERR_ALREADY_USED' => 'Voucher is already used',
	'VOUCHER_ERR_VCH_SERIES_VALID_DATE' => 'Voucher Series / Campaign has expired',
	'VOUCHER_ERR_VALID_CREATE_DAYS' => 'Voucher has expired',
	'VOUCHER_ERR_REDEMPTIONS' => 'Campaign over, it was first come first serve',
	'VOUCHER_ERR_INVALID_ORG' => 'Invalid organization',
	'VOUCHER_ERR_CANNOT_REDEEEM_MULTIPLE' => 'Cannot redeem same voucher multiple times',
	'VOUCHER_ERR_BILL_NUMBER_NEEDED' => 'Bill number needed for redemption',
	'VOUCHER_ERR_INVALID_VOUCHER_CODE' => 'Voucher code does not exist',
	'VOUCHER_ERR_TEST_VOUCHER_CODE' => 'Voucher is a test voucher. Cannot be redeemed',
	'VOUCHER_ERR_INVALID_USER' => 'Invalid User',
	'VOUCHER_ERR_UNABLE_TO_CREATE' => 'Unable to create voucher',
	'VOUCHER_ERR_NOT_ISSUED_TO_CUSTOMER' => 'Voucher not issued to this customer',
	'VOUCHER_ERR_COMMUNICATION' => 'Unable to communicate information to customer',
	'VOUCHER_ERR_VALIDATION_REQUIRED' => 'Validaiton required',
	'VOUCHER_ERR_VALIDATION_NOT_REQUIRED' => 'Validaiton not required',
	'VOUCHER_ERR_MAX_REDEMPTIONS_REACHED' => 'Maximum number of redemptions reached',
	'VOUCHER_ERR_UNKNOWN' => 'Unknown error',
	'VOUCHER_ERR_VCH_INVALID_REDEMPTION_RANGE' => 'Invalid Redemption Range For Voucher Series',
        'VOUCHER_ERR_NO_MISSED_CALL' => 'No Missed call received from the user',
        'VOUCHER_ERR_INVALID_BILL_AMOUNT' => 'Invalid Bill Amount',
		'VOUCHER_ERR_INVALID_STORE' => 'Invalid Store'    ,
    'VOUCHER_ERR_ALLOWED_REDEMPTION_EXAUSTED' => '-2222, Allowed redemption exausted',
    'VOUCHER_ERR_INVALID_REDEMPTION_GAP' => '-2223, Allowed redemption gap is invalid',
    'VOUCHER_ERR_INVALID_REDEMPTION_DATE' => '-2224, Allowed redemption date is invalid'
);

$GLOBALS["vouchers_error_keys"] = array (
	VOUCHER_ERR_SUCCESS => 'VOUCHER_ERR_SUCCESS',
	VOUCHER_ERR_NOT_TRANSFERRABLE => 'VOUCHER_ERR_NOT_TRANSFERRABLE',
	VOUCHER_ERR_ALREADY_USED => 'VOUCHER_ERR_ALREADY_USED',
	VOUCHER_ERR_VCH_SERIES_VALID_DATE => 'VOUCHER_ERR_VCH_SERIES_VALID_DATE',
	VOUCHER_ERR_VALID_CREATE_DAYS => 'VOUCHER_ERR_VALID_CREATE_DAYS',
	VOUCHER_ERR_REDEMPTIONS => 'VOUCHER_ERR_REDEMPTIONS',
	VOUCHER_ERR_INVALID_ORG => 'VOUCHER_ERR_INVALID_ORG',
	VOUCHER_ERR_CANNOT_REDEEEM_MULTIPLE => 'VOUCHER_ERR_CANNOT_REDEEEM_MULTIPLE',
	VOUCHER_ERR_BILL_NUMBER_NEEDED => 'VOUCHER_ERR_BILL_NUMBER_NEEDED',
	VOUCHER_ERR_INVALID_VOUCHER_CODE => 'VOUCHER_ERR_INVALID_VOUCHER_CODE',
	VOUCHER_ERR_TEST_VOUCHER_CODE => 'VOUCHER_ERR_TEST_VOUCHER_CODE',
	VOUCHER_ERR_INVALID_USER => 'VOUCHER_ERR_INVALID_USER',
	VOUCHER_ERR_UNABLE_TO_CREATE => 'VOUCHER_ERR_UNABLE_TO_CREATE',
	VOUCHER_ERR_NOT_ISSUED_TO_CUSTOMER => 'VOUCHER_ERR_NOT_ISSUED_TO_CUSTOMER',
	VOUCHER_ERR_COMMUNICATION => 'VOUCHER_ERR_COMMUNICATION',
	VOUCHER_ERR_VALIDATION_REQUIRED => 'VOUCHER_ERR_VALIDATION_REQUIRED',
	VOUCHER_ERR_VALIDATION_NOT_REQUIRED => 'VOUCHER_ERR_VALIDATION_NOT_REQUIRED',
	VOUCHER_ERR_MAX_REDEMPTIONS_REACHED => 'VOUCHER_ERR_MAX_REDEMPTIONS_REACHED',
	VOUCHER_ERR_UNKNOWN => 'VOUCHER_ERR_UNKNOWN',
	VOUCHER_ERR_VCH_INVALID_REDEMPTION_RANGE => 'VOUCHER_ERR_VCH_INVALID_REDEMPTION_RANGE',
  VOUCHER_ERR_NO_MISSED_CALL => 'VOUCHER_ERR_NO_MISSED_CALL',
  VOUCHER_ERR_INVALID_BILL_AMOUNT => 'VOUCHER_ERR_INVALID_BILL_AMOUNT',
	VOUCHER_ERR_INVALID_STORE => 'VOUCHER_ERR_INVALID_STORE' ,
    VOUCHER_ERR_ALLOWED_REDEMPTION_EXAUSTED => 'VOUCHER_ERR_ALLOWED_REDEMPTION_EXAUSTED',
    VOUCHER_ERR_INVALID_REDEMPTION_GAP => 'VOUCHER_ERR_INVALID_REDEMPTION_GAP',
    VOUCHER_ERR_INVALID_REDEMPTION_DATE => 'VOUCHER_ERR_INVALID_REDEMPTION_DATE'
);

//hack for 15143. wtf, some are refering by macro constants and some by key names. Making it to work for both
foreach($GLOBALS["vouchers_error_message"] as $code=>$value)
	$GLOBALS["vouchers_error_keys"][$code]=$GLOBALS["vouchers_error_keys"][constant($code)];
foreach($GLOBALS["vouchers_error_message"] as $code=>$value)
	$GLOBALS["vouchers_error_message"][constant($code)]=$GLOBALS["vouchers_error_keys"][$code];


/**
 * Maintains a series of vouchers. Contains common properties that hold across all vouchers.
 *
 * Note: Num_issued, num_redeemed don't get increment on an old object of this type.
 * A fresh object has to be created for the effect to be seen
 * @author kmehra
 *
 */
class VoucherSeries {
	public	$id;
	public $org_id;
	public $description;	
	public $valid_till_date;
	public $valid_days_from_create;
	public $max_create;		# Total number of vouchers that can be issued
	public $max_redeem;		# Total number of vouchers that can be redeemed
	public $transferrable;
	public $any_user;
	public $same_user_multiple_redeem;
	public $allow_referral_existing_users;
	public $max_referrals_per_referee;
	public $allow_multiple_vouchers_per_user;
	public $do_not_resend_existing_voucher;
	public $multiple_use;
	public $is_validation_required;
	public $created_by;
	private $num_redeemed;	# Number of vouchers that have been redeemeed
	private $num_issued;		# Number of vouchers that have been issued
	private $created;		# date, readonly
	private $last_used;		# date, readonly
	private $series_type;   # CAMPAIGN, DVS, or ALLIANCE
	private $discount_code; # Discount Code for the cashier window
	private $series_code;
	private $client_handling_type;
	private $disable_sms;
	private $sms_template;
	private $mutual_exclusive_series_ids;
	private $store_ids_json;
	private $dvs_enabled;
	private $dvs_expiry_date;
	public $info; 
	private $priority;
	private $terms_and_condition;
	private $signal_redemption_event;
	private $sync_to_client;
	private $short_sms_template;
	private $max_vouchers_per_user;
	private $min_days_between_vouchers;
	private $show_pin_code;
	private $discount_on;
	private $discount_type;
    private $discount_value;
    public $dvs_items; 
    public $redemption_range;
    private $logger;

    private $min_bill_amount;
    private $max_bill_amount;
   
	private $db;

	public function __construct($series_id) {
		global $logger;
		$this->logger = $logger;
		$this->id = $series_id;
		$this->db = new Dbase('campaigns');
		$this->load();
	}

	private function load() {

		$this->logger->debug("Loading Voucher Series: $this->id");

                global $currentorg;
        
                $res = array();
                $cache_select = true;
                $cache = MemcacheMgr::getInstance();
                $key = "o".$currentorg->org_id."_".CacheKeysPrefix::$voucherSeries . $this->id;

                try{
                   $cached_series = $cache->get($key);
                   $res = json_decode($cached_series, true);     
                   $this->logger->debug("Loaded voucher series $this->id from cache");     
                }catch(Exception $e){
                    $this->logger->debug("Error in loading from cache");    
                    $cache_select = false;    
                }

                if(!$cache_select)
                {
                	$safe_id = Util::mysqlEscapeString($this->id);
		        $sql = "SELECT * from voucher_series WHERE org_id = $currentorg->org_id AND id = '$safe_id' LIMIT 1";
        		$res = $this->db->query_firstrow($sql);
	        	if ($res == false) {
		        	$this->logger->debug("VchSeries not found");
			        $this->id = -1;
        			return false;
	        	}
        
                        $this->logger->debug("Setting the key in memcache");
                        $cached_series = json_encode($res);
                        try{
                            $cache->set($key, $cached_series, CacheKeysTTL::$voucherSeries);                              
                            $this->logger->debug("Set $this->id voucher series in cache");
                        }catch(Exception $e){
                                $this->logger->debug("Error in setting voucher series in cache");
                        }        
                }

		$this->org_id 			= $res['org_id'];
		$this->description 		= $res['description'];
		$this->valid_till_date 	= strtotime($res['valid_till_date']);
		$this->max_create 		= $res['max_create'];
		$this->max_redeem	 	= $res['max_redeem'];
		$this->transferrable 	= $res['transferrable'];
		$this->any_user 		= $res['any_user'];
		$this->same_user_multiple_redeem = $res['same_user_multiple_redeem'];
		$this->allow_referral_existing_users = $res['allow_referral_existing_users'];
		$this->max_referrals_per_referee = $res['max_referrals_per_referee'];
		$this->created_by 		= $res['created_by'];
		$this->valid_days_from_create = $res['valid_days_from_create'];
		$this->multiple_use 	= $res['multiple_use'];
		$this->allow_multiple_vouchers_per_user = $res['allow_multiple_vouchers_per_user'];
		$this->do_not_resend_existing_voucher = $res['do_not_resend_existing_voucher'];
		$this->is_validation_required = $res['is_validation_required'];
		$this->num_redeemed		= $res['num_redeemed'];
		$this->num_issued		= $res['num_issued'];
		$this->created			= $res['created'];
		$this->last_used		= $res['last_used'];
		$this->series_type		= $res['series_type'];
		$this->discount_code	= $res['discount_code'];
		$this->series_code		= $res['series_code'];
		$this->client_handling_type = $res['client_handling_type'];
		$this->disable_sms = $res['disable_sms'];		
		$this->sms_template = $res['sms_template'];
		$this->short_sms_template = $res['short_sms_template'];
		$this->info = $res['info'];
		$exclusive_series_ids_json = $res['mutual_exclusive_series_ids'];
		$this->mutual_exclusive_series_ids = array();
		if(strlen($exclusive_series_ids_json) > 0)
			$this->mutual_exclusive_series_ids = json_decode($exclusive_series_ids_json, true);
		$this->store_ids_json = $res['store_ids_json'];
		$this->dvs_enabled = $res['dvs_enabled'];
		$this->dvs_expiry_date = $res['dvs_expiry_date'];
		$this->priority = $res['priority'];
		$this->terms_and_condition = $res['terms_and_condition'];
		$this->signal_redemption_event = $res['signal_redemption_event'];
		$this->sync_to_client = $res['sync_to_client'];
		$this->max_vouchers_per_user = $res['max_vouchers_per_user'];
		$this->min_days_between_vouchers = $res['min_days_between_vouchers'];
		$this->show_pin_code = $res['show_pin_code'];
		$this->discount_on = $res['discount_on'];
		$this->discount_type = $res['discount_type'];
		$this->discount_value = $res['discount_value'];
    	$this->dvs_items = $res['dvs_items'];
    	$this->redemption_range = $res['redemption_range'];
    	
    	$this->min_bill_amount = $res['min_bill_amount'];
    	$this->max_bill_amount = $res['max_bill_amount'];
    	
    	$this->redeem_at_stores = $res['redeem_at_store'];
	}

	public function isTransferrable() {
		return $this->transferrable == true || $this->transferrable == 1;
	}

	public function isMultipleUseAllowed() {
		return $this->multiple_use == true || $this->multiple_use == 1;
	}

	public function allowMultipleVouchersPerUser(){
		return $this->allow_multiple_vouchers_per_user == true || $this->allow_multiple_vouchers_per_user == 1;
	}
	
	public function doNotResendExistingVoucherToCustomer()
	{
		return $this->do_not_resend_existing_voucher == true || $this->do_not_resend_existing_voucher == 1;
	}
	
	public function isValidationRequired(){
		return $this->is_validation_required == true || $this->is_validation_required == 1;
	}
	
	public function isAnyUser() {
		return $this->any_user == true || $this->any_user == 1;
	}
	
	public function isSameUserMultipleRedemptionsAllowed() {
		return $this->same_user_multiple_redeem == true || $this->same_user_multiple_redeem == 1;
	}

	public function isExistingUserReferralAllowed(){
		return $this->allow_referral_existing_users == true || $this->allow_referral_existing_users == 1; 
	}
	
	public function getMaxNumberOfReferralsPerReferee(){
		return $this->max_referrals_per_referee;		
	}
	
	public function getNumIssued() {
		return $this->db->query_scalar("SELECT num_issued FROM voucher_series WHERE id = '$this->id'");
	}
	public function getCreated() {
		return $this->created;
	}
	public function getLastUsed() {
		return $this->last_used;
	}

	public function getNumRedeemed() {
		return $this->db->query_scalar("SELECT num_redeemed FROM voucher_series WHERE id = '$this->id'");
	}
	
	public function getRedemptionRange(){ return $this->redemption_range; }
	public function getDiscountCode() { return $this->discount_code; }
	public function getSeriesType() { return $this->series_type; }
	public function getSeriesCode() { return $this->series_code; }
	public function getClientHandlingType() { return $this->client_handling_type; }
	public function isSmsSendingDisabled() { return ($this->disable_sms == 1); }
	public function getSMSTemplate() { return stripslashes($this->sms_template); }
	public function getShortSMSTemplate() { return stripslashes($this->short_sms_template); }
	public function getMutualExclusiveSeries() { return $this->mutual_exclusive_series_ids;	}
	public function isDVSEnabled() { return $this->dvs_enabled > 0 ? true : false; }
	public function getDVSExpiryDate(){ return strlen($this->dvs_expiry_date) > 0 ? $this->dvs_expiry_date : false; }
	public function getStoreIds(){	return (strlen($this->store_ids_json) > 0) ? json_decode($this->store_ids_json, true) : array();}
	public function getPriority() { return $this->priority; }
	public function getTermsAndConditions() { return $this->terms_and_condition; }
	public function signalRedemptionEvent() { return ($this->signal_redemption_event == 1 ? true : false); }
	public function syncToClient() { return ($this->sync_to_client == 1 ? true : false); }
	public function showPinCode() { return ($this->show_pin_code == 1 ? true : false); }
	public function getMaxNumberOfVouchersPerUser() {return $this->max_vouchers_per_user; }
	public function getMinNumberOfDaysBetweenVouchers() { return $this->min_days_between_vouchers;  }
	public function getDiscountOn() { return $this->discount_on; }
	public function getDiscountType() { return $this->discount_type; }
	public function getDiscountValue() { return $this->discount_value; }
	public function getMinBillAmount(){ return $this->min_bill_amount; }
	public function getMaxBillAmount() { return $this->max_bill_amount; }
	
	public function isDateValid($now = '') {
		if($now == '')
			$now = time();
		//$this->logger->debug("valid_till = $this->valid_till_date, now_ts=$now");

		if ($now < $this->valid_till_date)	
			return true;
			
		//allow if the same day as well
		//get diff in number of seconds and then divide by number of seconds in a day		
		$daydiff = floor(abs($now - $this->valid_till_date) / (60 * 60 * 24));
		if ($daydiff == 0)	return true;		

		return false;
	}

	/**
	 * It will check for redemption range is valid or not
	 */
	public function isRedemptionRangeValid(){
		
		// Check if Redemption Range is present or not
		if( !$this->redemption_range )
			return true;
			
		$date_format = date( 'd N H' );
		$range = explode( ' ' , $date_format );
		$redemption_range = json_decode( $this->redemption_range , true );
			
		$is_dom = false;
		$is_dow = false;
		$is_hours = false;
		
		//Check DOM check
		if( $range[0] ){
			$redemption_range['dom'] = 
			( is_array( $redemption_range['dom'] ) )? ( $redemption_range['dom'] ):( array() );
			
			$this->logger->debug(' DOM :'.print_r($redemption_range['dom'],true));
			foreach( $redemption_range['dom'] as $range_check ){
				if( ( $range[0] == $range_check ) || $range_check == -1 ){
					$is_dom = true;
					break;
				}
			}
		}
		
		//Check DOW check
		if( $range[1] ){
			$redemption_range['dow'] = 
			( is_array( $redemption_range['dow'] ) )? ( $redemption_range['dow'] ):( array() );
			$this->logger->debug(' DOW :'.print_r($redemption_range['dow'],true));
			foreach( $redemption_range['dow'] as $range_check ){
			
				if( ( $range[1] == $range_check ) || $range_check == -1 ){
					$is_dow = true;
					break;
				}
			}
		}
		
		//Check Hour check
		if( $range[2] ){
			$redemption_range['hours'] = 
			( is_array( $redemption_range['hours'] ) )? ( $redemption_range['hours'] ):( array() );
			$this->logger->debug(' Hours :'.print_r($redemption_range['hours'],true));
			foreach( $redemption_range['hours'] as $range_check ){

				if( ( $range[2] == $range_check ) || $range_check == -1 ){
					$is_hours = true;
					break;
				}
			}	
		}

		if( $is_dom && $is_dow && $is_hours ){
			return true;
		}
		else
			return false;
	}
	
	
	public function isBillAmountValid($bill_amount = 0)
	{
    if($bill_amount > 0){ 
            if($this->min_bill_amount > 0 && $bill_amount < $this->min_bill_amount)
            {
                    return false;
            }

            if($this->max_bill_amount > 0 && $bill_amount > $this->max_bill_amount)
            {
                    return false;
            }
		}
		return true;
	}
	
	
	public function isRedemptionValid() {
		if ($this->max_redeem == -1) return true;

		$sql = "SELECT COUNT(*) FROM voucher_series WHERE id = $this->id AND num_redeemed < max_redeem";
		return $this->db->query_scalar($sql) == 1;
	}

	public function updatePostIssueVoucher($num_issued = 1) {
		return $this->db->update("UPDATE voucher_series SET num_issued = num_issued + '$num_issued' WHERE id = $this->id AND org_id = ".$this->org_id);
	}

	public function updatePostRedeemVoucher() {
		return $this->db->update("UPDATE voucher_series SET num_redeemed = num_redeemed + 1 WHERE id = $this->id AND org_id = ".$this->org_id);
	}

	public function isCreationValid() {
		if ($this->max_create == -1) return true;

		$sql = "SELECT COUNT(*) FROM voucher_series WHERE id = $this->id AND num_issued < max_create";
		return $this->db->query_scalar($sql) == 1;

	}

	public static function createVoucherSeries($org_id, $description, $series_type, $client_handling_type, $discount_code, $series_code, $valid_till_date,
		$valid_days_from_create, $max_create, $max_redeem, $transferrable, $any_user, $same_user_multiple_redeem, $allow_referral_existing_users, $multiple_use,
		$is_validation_required, $disable_sms, $sms_template, $info, $allow_multiple_vouchers_per_user, $do_not_resend_existing_voucher, 
		$mutual_exclusive_series_ids, $store_ids, $dvs_enabled, $dvs_expiry_date,
		$priority, $terms_and_condition, $signal_redemption_event, $sync_to_client, $short_sms_template, $max_vouchers_per_user, $min_days_between_vouchers, 
		$max_referrals_per_referee, $show_pin_code, $discount_on, $discount_type, $discount_value, $creating_user_id, $dvs_items , $redemption_range
	)	
	{				
		$db = new Dbase('campaigns');
		$valid_till = Util::getMysqlDate($valid_till_date);
		//convert mutual_exclusive_series_ids to json
		if(count($mutual_exclusive_series_ids) > 0)
			$mutual_exclusive_series_ids = json_encode($mutual_exclusive_series_ids);

		//convert store_ids to json
		if(count($store_ids) > 0)
			$store_ids_json = json_encode($store_ids);
		else 	
			$store_ids_json = "";
			
		if(!$dvs_enabled)
			$store_ids_json = "";
			
		$sql = "INSERT INTO `voucher_series` (`org_id` , `description`, "
		. " `series_type`, `client_handling_type`, `discount_code`, `series_code`, "
		. " `valid_till_date`, `valid_days_from_create`, `max_create` , `max_redeem` , `transferrable` , `any_user`, `same_user_multiple_redeem`,
			`allow_referral_existing_users`, `multiple_use`, `is_validation_required`, `created_by` , `num_issued`, `num_redeemed`, `created`,
			`disable_sms`, `sms_template`, `info`, `allow_multiple_vouchers_per_user`, `do_not_resend_existing_voucher`, `mutual_exclusive_series_ids`,
		 	`store_ids_json`, `dvs_enabled`, `dvs_expiry_date`, `priority`, `terms_and_condition`, `signal_redemption_event`, `sync_to_client`,
		 	`short_sms_template`, `max_vouchers_per_user`, `min_days_between_vouchers`, `max_referrals_per_referee`,
		 	`show_pin_code`, `discount_on`, `discount_type`, `discount_value`, `dvs_items` , `redemption_range` ) "
		. " VALUES ('$org_id', '$description', '$series_type', '$client_handling_type', '$discount_code', '$series_code', "
		. " '$valid_till', '$valid_days_from_create','$max_create', '$max_redeem', "
		. " '$transferrable', '$any_user', '$same_user_multiple_redeem', '$allow_referral_existing_users', '$multiple_use',
			'$is_validation_required', '$creating_user_id', 0, 0, NOW(), '$disable_sms', '$sms_template', '$info', '$allow_multiple_vouchers_per_user', '$do_not_resend_existing_voucher',
			'$mutual_exclusive_series_ids', '$store_ids_json', '$dvs_enabled', '$dvs_expiry_date', '$priority', '$terms_and_condition',
			'$signal_redemption_event', '$sync_to_client', '$short_sms_template', '$max_vouchers_per_user', '$min_days_between_vouchers',
			'$max_referrals_per_referee', '$show_pin_code', '$discount_on', '$discount_type', '$discount_value', '$dvs_items' , '$redemption_range' )";
		$id = $db->insert($sql);

		return $id;
	}
	
	public static function updateVoucherSeries($voucher_series_id, $org_id, $description, $series_type, $client_handling_type, $discount_code, $series_code, $valid_till_date,
		$valid_days_from_create, $max_create, $max_redeem, $transferrable, $any_user, $same_user_multiple_redeem, $allow_referral_existing_users, $multiple_use, $is_validation_required,
		$disable_sms, $sms_template, $info, $allow_multiple_vouchers_per_user, $do_not_resend_existing_voucher, $mutual_exclusive_series_ids, $store_ids, $dvs_enabled, $dvs_expiry_date, $priority, $terms_and_condition,
		$signal_redemption_event, $sync_to_client, $short_sms_template, $max_vouchers_per_user, $min_days_between_vouchers, 
		$max_referrals_per_referee, $show_pin_code, $discount_on, $discount_type, $discount_value, $creating_user_id, $dvs_items , $redemption_range
	)
	{
		$db = new Dbase('campaigns');
		$valid_till = Util::getMysqlDate($valid_till_date);
		//convert mutual_exclusive_series_ids to json
		if(count($mutual_exclusive_series_ids) > 0)
			$mutual_exclusive_series_ids = json_encode($mutual_exclusive_series_ids);

		//convert store_ids to json
		if(count($store_ids) > 0)
			$store_ids_json = json_encode($store_ids);
		else 	
			$store_ids_json = "";
		
		if(!$dvs_enabled)
			$store_ids_json = "";

                $key = "o".$org_id."_".CacheKeysPrefix::$voucherSeries . $voucher_series_id;                    
                $cache = MemcacheMgr::getInstance();
                global $logger;
                try{
                    $cache->delete($key);    
		    $logger->debug("Invalidated cache key: $key");
                }catch(Exception $e){
                    $logger->error("Error in deleting cache key $key");
                }
	
		$sql = "UPDATE `voucher_series` SET `description` = '$description', "
		. " `series_type` = '$series_type', `client_handling_type` = '$client_handling_type', 
			`discount_code` = '$discount_code', `series_code` = '$series_code',
			`valid_till_date` = '$valid_till', `valid_days_from_create` = '$valid_days_from_create',
			`max_create` = '$max_create', `max_redeem` = '$max_redeem',
			`transferrable` = '$transferrable', `any_user` = '$any_user',
			`same_user_multiple_redeem` = '$same_user_multiple_redeem', 
			`allow_referral_existing_users` = '$allow_referral_existing_users',
			`multiple_use` = '$multiple_use', `is_validation_required` = '$is_validation_required',
			`disable_sms` = '$disable_sms',	`sms_template` = '$sms_template', `info` = '$info', 
			`allow_multiple_vouchers_per_user` = '$allow_multiple_vouchers_per_user',
			`do_not_resend_existing_voucher` = '$do_not_resend_existing_voucher',
			`mutual_exclusive_series_ids` = '$mutual_exclusive_series_ids', 
			`store_ids_json` = '$store_ids_json', `dvs_enabled` = '$dvs_enabled', `dvs_expiry_date` = '$dvs_expiry_date',
			`priority` = '$priority' ,`terms_and_condition` = '$terms_and_condition', `signal_redemption_event` = '$signal_redemption_event',
			`sync_to_client` = '$sync_to_client', `short_sms_template` = '$short_sms_template', `max_vouchers_per_user` = '$max_vouchers_per_user',
			`min_days_between_vouchers` = '$min_days_between_vouchers',
			`max_referrals_per_referee` = '$max_referrals_per_referee',
			`show_pin_code` = '$show_pin_code',
			 `discount_on` = '$discount_on',
			 `discount_type` = '$discount_type',
			 `discount_value` = '$discount_value',
       `dvs_items` = '$dvs_items' ,
       `redemption_range` = '$redemption_range'
			"
		. " WHERE `org_id` = $org_id AND `id` = $voucher_series_id";

		return $db->update($sql);
	}

	/**
	 * For testing purpose
	 */
	public function countNumVouchersIssuedInSeries() {
		return $this->db->query_scalar("SELECT COUNT(*) FROM voucher WHERE org_id ='$this->org_id' AND voucher_series_id = '$this->id'");	# TODO::changed from query_scalar to query
	}
	public function countNumVouchersRedeemedInSeries() {
		return $this->db->query_scalar("SELECT COUNT(*) FROM voucher WHERE org_id ='$this->org_id' AND voucher_series_id = '$this->id' AND used=1");
	}

	public function getOrgId() { return $this->org_id; }
	
	public function getNumberOfVouchersForUser($user_id){
		return $this->db->query_scalar("SELECT COUNT(*) FROM voucher WHERE org_id = '$this->org_id' AND  voucher_series_id = '$this->id' AND issued_to = '$user_id'");
	}

	public function getDaysFromLastVoucherForUser($user_id){
		return $this->db->query_scalar("SELECT IFNULL(MIN(DATEDIFF(NOW(), `created_date`)), -1) FROM voucher WHERE org_id = '$this->org_id' AND  voucher_series_id = '$this->id' AND issued_to = '$user_id'");
	}

	/**
	 *Check if the user already has a voucher in any one of the selected mutually exclusive voucher series
	 *@param $user_id User id for whom the checking has to be done
	 *@return True if a voucher is present in any of the series. False if no series has been selected or no voucher exists for the current user in any of the series
	 */
	public function isVoucherPresentInMutuallyExclusiveSeries($user_id){
		
		$found = false;
		
		$mutual_series = $this->getMutualExclusiveSeries();
		$mutual_series = !is_array( $mutual_series ) ? array() : $mutual_series ;
		foreach( $mutual_series as $me_vs_id ){
			$me_vs = new VoucherSeries($me_vs_id);
			if($me_vs->getNumberOfVouchersForUser($user_id) > 0){
				$found = true;
				break;
			}				
		}
		
		return $found;
	}
	
	
	public function getNumberOfReferralsForReferee($referee_mob){		
		Util::checkMobileNumber($referee_mob);
		return $this->db->query_scalar("SELECT COUNT(*) FROM `campaign_referrals` WHERE `org_id` = '$this->org_id' AND `voucher_series_id` = '$this->id' AND `referee_mobile` = '$referee_mob'");		
	}

	public function getLastVoucherForCustomerInSeries($user_id)
	{
		return 
			$this->db->query_scalar("
				SELECT voucher_code 
				FROM voucher 
				WHERE org_id = '$this->org_id' AND voucher_series_id = '$this->id'
					AND issued_to = '$user_id'
				ORDER BY `voucher_id` DESC
			");
	}
	
	public function isSeriesValidForStore( $user_id )
	{
		$stores = $this->getStoreIds();
		if(count($stores) == 0 || in_array(-1,$stores))
		{
			return true;
		}
		
		if(in_array($user_id,$stores))
		{
			return true;
		}
		else
		{
			$this->logger->debug("voucher series is not valid for the store");
			return false;
		}
	}
}

class Voucher extends Base {
	private  	$series_id;
	private		$issued_to;
	private		$current_user;
	private		$used_by;
	private  	$org_id;
	private  	$logger;
	private		$vch_series;
	private		$voucher_code;
	private		$pin_code;
	private		$created_date;
	private		$used;
	private		$voucher_id;
	private 	$amount;
	private		$test;
	private		$max_allowed_redemptions;
	var			$db;		// TODO::make it private after testing



	/*************** CONSTRUCTORS ***********************/

	/**
	 *  Creates a Voucher Controller Class
	 * @param $org_id
	 * @return
	 */
	public function __construct(){
		global $logger;
		parent::__construct();
		$this->logger = $logger;
		$this->db = new Dbase('campaigns');
		$this->logger->debug("Starting Vouchers Admin");
	}




	private static function initFromSql($sql) {
		$v = new Voucher();
		$data = $v->db->query_firstrow($sql);
		if($data){
			$v->logger->debug("Selected Voucher from id $data[voucher_id] $data[voucher_code]");
			$v->init($data);
			return $v;
		}
		return NULL;
	}

	private function init($data) {
		$this->series_id	= $data['voucher_series_id'];
		$this->vch_series 	= new VoucherSeries($data['voucher_series_id']);
		$this->current_user	= $data['current_user'];
		$this->issued_to	= $data['issued_to'];
		$this->used_by		= $data['used_by'];
		$this->created_date = strtotime($data['created_date']);
		$this->voucher_code = $data['voucher_code'];
		$this->pin_code		= $data['pin_code'];
		$this->org_id 		= $data['org_id'];
		$this->used 		= $data['used'];
		$this->voucher_id 	= $data['voucher_id'];
		$this->amount		= $data['amount'];
		$this->test			= $data['test'];
		$this->max_allowed_redemptions = $data['max_allowed_redemptions'];
	}

	/**
	 * Get the voucher Details from the voucher ID
	 * @param $voucherId
	 * @return array(Voucher Details)
	 */
	public static function getVoucherFromId($voucherId){

		return Voucher::initFromSql("SELECT * from voucher where voucher_id = '$voucherId'");
	}

	/**
	 * Get the voucher Details from the voucher Code
	 * @param $voucherCode
	 * @return array(Voucher Details)
	 */
	public static function getVoucherFromCode($voucherCode, $org_id)
	{
		$safe_voucher_code = Util::mysqlEscapeString($voucherCode);
		return Voucher::initFromSql("SELECT * from voucher where voucher_code = '$safe_voucher_code' AND org_id = $org_id");

	}

	public function getOrgId() { return $this->org_id; }
	public function getUsedBy() { return $this->used_by; }
	public function getCurrentUser() { return $this->current_user; }
	public function getIssuedTo() { return $this->issued_to; }
	public function getVoucherSeries() { return $this->vch_series; }
	public function getVoucherCode() { return $this->voucher_code; }
	public function getPinCode()	{ return $this->pin_code; }
	public function getVoucherId() { return $this->voucher_id; }
	public function getCreatedOn() { return $this->created_date; }
	public function getSeriesId() { return $this->series_id; }
	public function getAmount()	{ return $this->amount; }
	public function isTestVoucher() { return $this->test == 1 ? true : false; }
	public function shouldSignalRedemptionEvent() { return $this->vch_series->signalRedemptionEvent(); }
	public function getMaxAllowedRedemptions() { return $this->max_allowed_redemptions; }
	public function getExpiryDate() 
	{
		$valid_days = $this->vch_series->valid_days_from_create;
		return strftime("%d-%m-%Y", strtotime("+$valid_days days", $this->created_date));
	}
	
	
	/************** METHODS ****************/

	private function voucherCodeExists($voucherCode, $org_id) {
		$ret = $this->db->query_scalar("SELECT COUNT(*) FROM voucher WHERE voucher_code = '$voucherCode' AND org_id = $org_id");
		return $ret > 0;
	}



	/**
	 * Use the specified Voucher. The following rules need to be obeyed:
	 * 1. Current Voucher user must be for this user (or any_user is set in series)
	 * 2. Voucher must not have been used already, or be reusable
	 * 3. It should be within the valid_till date of the series
	 * 4. It should be within create_date + valid_days_from_create
	 * 5. Voucher series should have enough number of valid redemptions(num_use) left
	 * 6. It should belong to the org that is currently logged in
	 *
	 * Note that this method has side-effect. Should not be called multiple times.
	 * TODO: Reusable is not enabled yet
	 *
	 * @param $voucherCode Code of the voucher
	 * @param $user_id User who wants to redeem
	 * @param $org_id Current Organization that wants to redeem
	 * @return VCH_ERR_SUCCESS if Voucher is marked as used successfully. Negative ERROR code otherwise
	 */
	public function isRedeemable( UserProfile $user,  $org, $bill_amount = 0) {
		$user_id = $user->user_id;
		$org_id = $org->org_id;

		$this->logger->info("Checking if voucher redeemable ($this->voucher_code) ID : $this->id (series-$this->series_id for user-$user_id by org-$org_id");

		if($this->isTestVoucher()){
			$this->logger->debug("test voucher");
			return VOUCHER_ERR_TEST_VOUCHER_CODE;
		}
		
		if ( !$this->vch_series->isRedemptionRangeValid() ) {
			$this->logger->debug("redemption range invalid");
			return VOUCHER_ERR_VCH_INVALID_REDEMPTION_RANGE;
		}
		
		if ((!$this->vch_series->isAnyUser()) && ($this->current_user != $user_id)) {
			$this->logger->debug("voucher is not transferrable");
			return VOUCHER_ERR_NOT_TRANSFERRABLE;
		}

		if ((!$this->vch_series->isMultipleUseAllowed()) 
			&& ($this->usedCount() >= 1)) 
		{
			$this->logger->debug("voucher already used");
			return VOUCHER_ERR_ALREADY_USED;
		}

		//get the number of redemptions for user
		$number_of_redemptions_for_user = $this->getRedeemCountForUser($user_id);
		
		if (
			(!$this->vch_series->isSameUserMultipleRedemptionsAllowed()) 
			&& ($number_of_redemptions_for_user >= 1)
		){
			$this->logger->debug("redeeming multiple times");
			return VOUCHER_ERR_CANNOT_REDEEEM_MULTIPLE;
		}
		
		//Check if number of redemptions has exceeded
		if($this->vch_series->isSameUserMultipleRedemptionsAllowed()
			&& $this->getMaxAllowedRedemptions() > 1
			&& $number_of_redemptions_for_user >= $this->getMaxAllowedRedemptions()
		){
			$this->logger->debug("max redemptions reached");
			return VOUCHER_ERR_MAX_REDEMPTIONS_REACHED;
		}
		
		if ($this->vch_series->org_id != $org_id) {

			$this->logger->debug("mismatched org_id");
			return VOUCHER_ERR_INVALID_ORG;
		}

		if (!$this->vch_series->isDateValid()) {
			$this->logger->debug("invalid date for voucher series");
			return VOUCHER_ERR_VCH_SERIES_VALID_DATE;
		}

		$valid_days = $this->vch_series->valid_days_from_create;
		$now = time();
		$limit = strtotime("+$valid_days days", $this->created_date);
		//echo "+$valid_days days -- limit : $limit -- ".date('c',$limit).", now:$now";
    	$this->logger->debug("voucher limit: $limit");
    

		if ($limit < $now) {
			$this->logger->debug("invalid create days");
			return VOUCHER_ERR_VALID_CREATE_DAYS;
		}

		if (!$this->vch_series->isRedemptionValid()) {
			$this->logger->debug("invalid redemption");
			return VOUCHER_ERR_REDEMPTIONS;
		}
		
		if(!$this->vch_series->isBillAmountValid($bill_amount)){
			$this->logger->debug("invalid bill amount");
			return VOUCHER_ERR_INVALID_BILL_AMOUNT;
		}
		
		return VOUCHER_ERR_SUCCESS;	
	}
	
	public function redeemVoucher(UserProfile $user,  $org, 
		$bill_number, $store_id = '', $redeemed_on = '', $validation_code = '', &$redemption_id, 
		$bill_amount = 0
	){	
		
		$coupon_manager = new CouponManager();
		$coupon_manager->loadById( $this->voucher_id );
		
		if(empty($redeemed_on))
			$redeemed_on = Util::getCurrentTimeForStore($store_id);
		
		return $coupon_manager->redeemVoucher( $user, $bill_number, $store_id, 
		$redeemed_on , $validation_code , $redemption_id, $bill_amount 
		);
		
		//===== removing this logic =====//
		global $currentuser, $counter_id;
		
		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$safe_validation_code = Util::mysqlEscapeString($validation_code);
		
		$redeemable = $this->isRedeemable($user, $org, $bill_amount);
		if ($redeemable != VOUCHER_ERR_SUCCESS) return $redeemable;
		$store_id = ($store_id == '') ?  $currentuser->user_id : $store_id;
		$redeemed_on = ($redeemed_on == '') ? 'NOW()' : "'$redeemed_on'";
		$validation_code_used = $validation_code == '' ? "NULL" : "'$safe_validation_code'"; 
		
		$org_id = $this->org_id;
		$series_id = $this->series_id;
		
		
		$insert_sql = "
			INSERT INTO `voucher_redemptions` (`org_id`, `voucher_series_id`, `voucher_id`, `used_by`, `used_date`, 
				`used_at_store`, `bill_number`, `validation_code_used`, `counter_id`, `bill_amount`) 
			VALUES ('$org_id', '$series_id', '$this->voucher_id', '$user->user_id',' $redeemed_on', 
				'$store_id', '$safe_bill_number', '$validation_code_used', '$counter_id', '$bill_amount')
		";
		
		$redemption_id = $this->db->insert($insert_sql);
		
		if($redemption_id) {
			$this->vch_series->updatePostRedeemVoucher();
			$this->used = true;
			$this->logger->debug("Using Voucher code $this->voucher_id: $this->voucher_code");
				
			return VOUCHER_ERR_SUCCESS;
		}
		else return VOUCHER_ERR_UNKNOWN;
	}
	
	/**
	 * This is the new voucher redemption flow where invalidated vouchers
	 * without voucher codes need to be redeemed. 
	 * 
	 * @param $user
	 * @param $org
	 * @params $voucher_series_id
	 * @param $voucher_series_id
	 * @param $bill_number
	 * @param $store_id
	 * @param $redeemed_on
	 * @param $validation_code
	 */
	
	public function redeemInvalidatedVoucher($user, $org, $voucher_series_id, $bill_number, 
						$store_id = -1, $redeemed_on = '', $validation_code = '', &$redemption_id, $bill_amount = 0)
	{
		$this->logger->debug("Trying to redeem an invalidated voucher:
							  $user->user_id, $org->org_id, $voucher_series_id, $bill_number		
							");
		
		$org_id = $org->org_id;
		$user_id = $user->user_id;
		
		$safe_validation_code = Util::mysqlEscapeString($validation_code);
		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		
		$redeemable = $this->isRedeemable($user, $org);
		if ($redeemable != VOUCHER_ERR_SUCCESS) return $redeemable;
		$store_id = ($store_id == '') ?  $currentuser->user_id : $store_id;
		if(empty($redeemed_on))
			$redeemed_on = Util::getCurrentTimeForStore($store_id);
		$redeemed_on = ($redeemed_on == '') ? " NOW() " : "'$redeemed_on'";
		$validation_code_used = $validation_code == '' ? "NULL" : "'$safe_validation_code'"; 
		
		if(!$this->getMissedCallStatus($user)){
			$this->logger->debug("Haven't received any missed call..returning error");
			return VOUCHER_ERR_NO_MISSED_CALL;
		}
		
		if($this->voucher_id  > 0)
		{
			$insert_sql = "
				INSERT INTO `voucher_redemptions` (`org_id`, `voucher_series_id`, `voucher_id`, `used_by`, `used_date`, 
					`used_at_store`, `bill_number`, `validation_code_used`, `counter_id`, `bill_amount`) 
				VALUES ('$org_id', '$this->series_id', $this->voucher_id, '$user_id', '$redeemed_on', 
					'$store_id', '$safe_bill_number', '$validation_code_used', '$counter_id', '$bill_amount')
			";
		
			$redemption_id = $this->db->insert($insert_sql);
		
			if($redemption_id) {
				$this->vch_series->updatePostRedeemVoucher();
				$this->used = true;
				$this->logger->debug("Using Voucher code $this->voucher_id: $this->voucher_code");
				
				return VOUCHER_ERR_SUCCESS;
			}
			else return VOUCHER_ERR_UNKNOWN;
		}
		
		return VOUCHER_ERR_UNABLE_TO_CREATE;
	}
	
	/**
	 * shouldn't this go in Util 
	 * @param unknown_type $user
	 */
	
	private function getMissedCallStatus($user)
	{
		$mobile = $user->mobile;
		 $sql = "SELECT count(*) FROM sms_in lsi WHERE 
		 		 lsi.from = '$mobile' AND time >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
         AND is_used = FALSE";

		 $db = new Dbase('users');
		 $count = $db->query_scalar($sql);
		 if($count == 0){
		 	return false;
		 }else{
        $sql = "UPDATE sms_in lsi SET lsi.is_used = TRUE WHERE lsi.from = '$mobile' AND
                lsi.is_used = FALSE AND lsi.time >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
        $res = $db->update($sql);  
        if(!$res){
           $this->logger->debug("Error in flagging the used missed call");
        }  
       return true;
		 }
	}
	
	/**
	 * transfer voucher from user $from to user $to
	 *	LOGIC NOT IMPLEMENTED YET
	 * @param $voucherCode
	 * @param $from
	 * @param $to
	 * @return return true if successful
	 */
	function transfer($voucherCode, $from , $to){
		if (!$this->vch_series->isTransferrable()) {
			return false;
		}
		$arr = $this->getVoucherFromCode($voucherCode, $this->vch_series->org_id);
		if($this->issued_to == $from){
			$this->db->update("UPDATE voucher SET current_user = $to where voucher_code LIKE '$voucherCode'");
			return true;
		}

		return false;
	}


	function usedCount() {
		$count = $this->db->query_scalar ("SELECT COUNT(*) FROM `voucher`, `voucher_redemptions` as redemptions where voucher.voucher_id = redemptions.voucher_id 
			and voucher.voucher_id = '$this->voucher_id'");

		if ($count)
			return $count;
		else
			return 0;
	}

	function isCampaignReferralVoucher(){
		
		$org_id = $this->org_id;
		$series_id = $this->vch_series->id;
		$referrer_id = $this->issued_to;
		
		$count = $this->db->query_scalar ("SELECT COUNT(*) 
						FROM `campaign_referrals`
						WHERE org_id = $org_id AND voucher_series_id = $series_id
							AND referrer_id = $referrer_id
			");
		
		if($count > 0)
			return true;
		
		return false;
		
	}
	
	
	function getNumOfCampaignReferrals(){
		
		$org_id = $this->org_id;
		$series_id = $this->vch_series->id;
		$referrer_id = $this->issued_to;
		
		$res = $this->db->query_firstrow ("SELECT COUNT(*) as num_referrals_total, IFNULL(SUM(IF(DATE(`created_on`) = DATE(NOW()), 1, 0)), 0) as num_referrals_today
						FROM `campaign_referrals`
						WHERE org_id = $org_id AND voucher_series_id = $series_id
							AND referrer_id = $referrer_id
			");
		
		$num_referrals_total = $res['num_referrals_total'];
		$num_referrals_today = $res['num_referrals_today'];
		
		return array($num_referrals_total, $num_referrals_today);
	}
	
	
	function getReferralRedeemCount(){
		$count = $this->db->query_scalar ("SELECT COUNT(*) FROM `voucher_redemptions` WHERE org_id = $this->org_id AND `voucher_id` = ".$this->voucher_id." AND `used_by` != ".$this->issued_to);

		if ($count)
			return $count;
		else
			return 0;		
	}

	function getRedeemCountForUser($user_id){
		$count = $this->db->query_scalar ("SELECT COUNT(*) FROM `voucher_redemptions` WHERE org_id = $this->org_id AND `voucher_id` = ".$this->voucher_id." AND `used_by` = $user_id");

		if ($count)
			return $count;
		else
			return 0;		
	}
	
	/**
	 * Checks whether the voucher is Valid
	 * @deprecated The validity check is far more complkicated now
	 * @param $voucherCode
	 * @return return true if the Voucher is not used and not expired
	 */
	function isValid($voucherCode){
		$data = $this->db->query_firstrow("SELECT * from voucher where org_id = $this->org_id AND voucher_code LIKE '$voucherCode' AND valid_till_date > NOW()");
		$this->logger->debug("validating voucher $data[voucher_code]");
		if($data){
			$this->logger->debug("Validated voucher $data[voucher_code]");
			if($data['used']==false || $data['used']==0)
			return false;
			else
			return true;
		}
		return false;
	}

	/**
	 * Generates a random Code of specified Length
	 * @param $length
	 * @return returns a string with the random code
	 */
	function generateRandomCode($length = 8, $is_alphanumeric = false)
	{

		return Util::generateRandomCode($length, $is_alphanumeric);
	}



	public function get_org_id($voucher) {		// created for testing
		return $voucher->org_id;
	}
	
	
	public static function issueVoucherFromDiscPinSeries($org_id, $user_id, $series_id){
		
		$coupon_manager = new CouponManager();
		
		$params['user_id'] = $user_id;
		$params['series_id'] = $series_id;
		return $coupon_manager->issue( $series_id, $params, 'disc_pin' );
		
		//================== Removing This Logic======================//
		$vch_series = new VoucherSeries($series_id);
		
		if($vch_series->getClientHandlingType() == 'DISC_CODE_PIN'){

			$v = new Voucher();
			
			//get the number of unused vouchers
			$num_left = 1; /*$v->db->query_scalar("
				SELECT COUNT(*) 
				FROM voucher 
				WHERE org_id = $org_id 
					AND voucher_series_id = $series_id 
					AND issued_to = -1
			");*/
			
			//no vouchers left
			if($num_left < 1)			
				return false;
			
			$voucher_id = $v->db->query_scalar("
				SELECT voucher_id 
				FROM voucher 
				WHERE org_id = $org_id 
					AND voucher_series_id = $series_id 
					AND issued_to = -1 LIMIT 0,1
			");

			$v = Voucher::getVoucherFromId($voucher_id);
			
			if(!$v)
				return false;
			
			$code = $v->getVoucherCode();
			
			$v->db->update("
				UPDATE voucher 
				SET issued_to = $user_id, 
					`current_user` = $user_id,
					`created_date` = NOW()
				WHERE voucher_id = $voucher_id 
			");
			
			$v->logger->debug("Allotting existing Voucher with Code $code and series = $series_id to user-id : $user_id");
			
			$vch_series->updatePostIssueVoucher();		
			
			return $code;
		}
		
		return false;
		
	}

	/**
	 * Vouchers are issued at the store server
	 * No checking is to be done while inserting
	 * @param unknown_type $series_id
	 * @param  $user
	 * @param  $org
	 * @param unknown_type $created_by
	 * @param unknown_type $voucher_code
	 * @param unknown_type $issued_at_counter_id
	 * @param unknown_type $bill_number
	 * @param unknown_type $created_date
	 * @return string|string|string|string|string
	 */
	public static function issueVoucherByStoreServer($series_id,  $user, 
		 $org, $created_by, $voucher_code, $issued_at_counter_id, 
		$bill_number, $created_date = '', $rule_map)
	{
		
		if ($user == false) return false;
		$user_id = $user->user_id;
		$org_id = $org->org_id;
		$v = new Voucher();
		$v->vch_series = new VoucherSeries($series_id);
		if (!$v->vch_series->id == -1) return false;
		if ($v->vch_series->org_id != $org_id) return false;
		if (!$v->vch_series->isDateValid($created_date)) return false;
		
		//if the client handling type is DISC_CODE_PIN, allot an already existing voucher to this user and send back the code
		if($v->vch_series->getClientHandlingType() == 'DISC_CODE_PIN'){
			return Voucher::issueVoucherFromDiscPinSeries($org_id, $user_id, $series_id);
		}
		
		$created_date = ($created_date == '') ? " NOW() " : " '".Util::getMysqlDateTime($created_date)."' ";
	
		# This code is now unique. One problem could occur if another thread creates the same code in the meantime,
		# But we will address that later. It will require either transactions, or code creation using a stored proc

		$v->logger->debug("Creating a new Voucher (Issued By Store Server) with Code $voucher_code and series = $series_id");
		$id = $v->db->insert("INSERT INTO voucher (`voucher_code`, `org_id`, `created_date`, `issued_to`, `current_user`, `voucher_series_id`, `created_by`, `bill_number`, `issued_at_counter_id`, `rule_map` ) "
		." VALUES ('$voucher_code', '$org_id', $created_date, '$user_id','$user_id', '$series_id', '$created_by', '$bill_number', '$issued_at_counter_id', '$rule_map')");

		if ($id == false || $id == NULL) return false;
		
		//update the successful issual
		$v->vch_series->updatePostIssueVoucher();
		
    Util::addRuleStats($series_id, $user_id, $bill_number, $rule_map, true);
     
    return $voucher_code;
	}
	
	/**
	 * 
	 * NOTE : Please modify issueMultipleVouchers accordingly
	 * 
	 * 
	 * A voucher is issued to a particular user. It has to belong to a particular series from which it picks up its properties
	 * This method is typically called at the time of issuing a voucher.
	 *
	 * The following conditions must be satisfied:
	 * - Belong to he same organization as the series
	 * - The series should be able to create more vouchers
	 * Before issuing the voucher, the userID has to be registered within our system. If not registered yet,
	 * call registerAutomaticallyByMobile() before issuing
	 * @param $series_id
	 * @param $user  Object of the user
	 * @param $org
	 * @param $created_by
	 * @param $voucher_code The Voucher code if pre-supplied. Ensuring that its not already present is the callers responsiblity
	 * @param $created_date Voucher creation date
	 * @param $allow_multiple_vouchers Allow more than one voucher per user in the same voucher series. If false (default), takes the config from the series
	 * @param $amount to be associated with the voucher
	 * @param $bill_number to be stored with the voucher
	 * @param $max_allowed_redemptions Maximum number of redemptions to be allowed on this voucher 
	 * @return Voucher Code of the Voucher Generated
	 */
	public static function issueVoucher($series_id, UserProfile $user,  $org,
		 $created_by, $voucher_code = NULL, $created_date = '', 
		 $allow_multiple_vouchers = false, $amount = '', 
		 $bill_number = '', $max_allowed_redemptions = '', 
		 $issued_at_counter_id = '-1',
		 $rule_map = '', $pin_code = ''
	){  
		/*
		* NOTE : Please modify issueMultipleVouchers accordingly
		*/
		global $logger;
        if ($user == false) 
        {
        	$logger->info("Voucher:: user is false. Returning false");
        	return false;
        }
		$user_id = $user->user_id;
		$org_id = $org->org_id;
		$v = new Voucher();
		$v->vch_series = new VoucherSeries($series_id);
		
		if (!$v->vch_series->id == -1)
		{
			$logger->debug("Voucher:: voucher series id is -1"); 
			return false;
		}
		
		if ($v->vch_series->org_id != $org_id)
		{
			$logger->debug("Voucher:: voucher series org_id (".$v->vch_series->org_id.") != $org_id"); 
			return false;
		}
		
	    if (!$v->vch_series->isDateValid($created_date))
	    {
	    	$logger->debug("Voucher:: Invalid date, $created_date");
	    	return false;
	    }
		
	   
	    /*
	     * Store level check for Voucher Series
	    */
	    if(!$v->vch_series->isSeriesValidForStore( $created_by ))
	    {
	    	$logger->debug("Voucher:: voucher series not valid for store, $created_by");
	    	return false;
	    }
	    
		//check if there any mutually exclusive series selected, continue with creation of voucher 
		//only if there is no voucher issued to this user in the set of series selected as exclusive
		if($v->vch_series->isVoucherPresentInMutuallyExclusiveSeries($user_id))
		{
			$logger->debug("Voucher:: voucher present in mutually exclusive series");
			return false;
		}
		
		if(!$allow_multiple_vouchers) // if not specified, get the config from the series
			$allow_multiple_vouchers = $v->vch_series->allowMultipleVouchersPerUser();
		
		if(!$allow_multiple_vouchers){
		
			$code = $v->vch_series->getLastVoucherForCustomerInSeries($user_id);
			
			if ($code)
			{
				//Customer has voucher but config does not allow resending the voucher to the customer
				if($v->vch_series->doNotResendExistingVoucherToCustomer())
				{
					$logger->debug("Voucher:: Resending of voucher to customer not allowed.");
					return false;
				}  
					
				return $code;
			}
		}
		
		

		//Do not allow more than specified vouchers per user 
		if($allow_multiple_vouchers && ($v->vch_series->getMaxNumberOfVouchersPerUser() != -1)
		&& ($v->vch_series->getNumberOfVouchersForUser($user_id) >= $v->vch_series->getMaxNumberOfVouchersPerUser())
		){
			$logger->debug("Voucher:: not allowed more than specified vouchers per user ");
			return false;
		}
    
		//Voucher can be created as per the max number of vouchers per user rule
		//Now check if there is any check on the minimum days between vouchers
		if($allow_multiple_vouchers //multiple vouchers enabled
		&& ($v->vch_series->getMinNumberOfDaysBetweenVouchers() > 0)
		&& ($v->vch_series->getNumberOfVouchersForUser($user_id) >= 1) //customer already has atleast one voucher
		&& ($v->vch_series->getDaysFromLastVoucherForUser($user_id) < $v->vch_series->getMinNumberOfDaysBetweenVouchers()) //check for min number of days between vouchers
		){
			$logger->debug("Voucher:: voucher within 'min Number Of Days Between Voucher' config. returning last voucher for customer in series");
			return $v->vch_series->getLastVoucherForCustomerInSeries($user_id);
		}

		//if the client handling type is DISC_CODE_PIN, allot an already existing voucher to this user and send back the code
		if($v->vch_series->getClientHandlingType() == 'DISC_CODE_PIN'){
			$logger->debug("Voucher:: issuing voucher from DiscPinSeries");
			return Voucher::issueVoucherFromDiscPinSeries($org_id, $user_id, $series_id);
		} 
		
		//check if we are allowed to create a voucher
		if (!$v->vch_series->isCreationValid())
		{
			$logger->debug("Voucher:: voucher creation not allowed");
			return false;
		}
		
		//check for offline redemption enabled
		$is_offline_redemption_enabled = $org->getConfigurationValue(CONF_LOYALTY_ENABLE_OFFLINE_REDEMPTION, false);
		$add_customer_id = $org->getConfigurationValue(CONF_VOUCHER_CODE_PREPEND_EXTERNAL_ID, Util::isMOMnMeOrg($org->org_id));
		
		if ($voucher_code != NULL && $voucher_code != '' && (strlen($voucher_code)>0) ) {
			$code = $voucher_code;
			
			//If offline redemption is enabled, send the voucher code prefixed with series id
			if($is_offline_redemption_enabled)
			{
				$logger->debug("Voucher:: offline redemption enabled");
				$code = $series_id.'-'.$code;
			}
		}
		else {		

			$length = $org->getConfigurationValue(CONF_VOUCHER_CODE_LENGTH, 8);
			$is_alphanumeric = $org->getConfigurationValue(CONF_VOUCHER_CODE_IS_ALPHANUMERIC, false);

			$sql = "SELECT `external_id` FROM `user_management`.`loyalty` WHERE `user_id` = $user_id AND `publisher_id` = $org_id";
			$external_id = $v->db->query_scalar($sql);
			
			do {
				$code = $v->generateRandomCode($length, $is_alphanumeric);
				$code = Util::makeReadable($code);
				if($add_customer_id){
					if($external_id)
						$code = $external_id.$code;					
				}
					
				//If offline redemption is enabled, send the voucher code prefixed with series id
				if($is_offline_redemption_enabled)
					$code = $series_id.'-'.$code;

				$logger->debug("Voucher:: inside do while loop. code = $code org_id = $org_id");
			}while ($v->voucherCodeExists($code, $org_id));

		}
		
		$store_id = $created_by;
		$timezone = StoreProfile::getById($store_id)->getStoreTimeZoneLabel();
		$v->logger->debug("Time zone result: ".print_r($timezone, true));
		$current_date = Util::getCurrentTimeInTimeZone($timezone, null);
		$current_date = empty($current_date) ? ' NOW() ' : "'$current_date'"; 
	
		$created_date = ($created_date == '') ? " $current_date " : " '".Util::getMysqlDateTime($created_date)."' ";
		$max_allowed_redemptions = $max_allowed_redemptions == '' ? 'NULL' : "'$max_allowed_redemptions'";
		$safe_ping_code = Util::mysqlEscapeString($pin_code);
		$pin_code = $pin_code == '' ? 'NULL' : "'$safe_pin_code'";
		
		# This code is now unique. One problem could occur if another thread creates the same code in the meantime,
		# But we will address that later. It will require either transactions, or code creation using a stored proc
  
		$v->logger->debug("Creating a new Voucher with Code $code and series = $series_id");
		$safe_series_id = Util::mysqlEscapeString($series_id);
		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		$safe_code = Util::mysqlEscapeString($code);
		$id = $v->db->insert("INSERT INTO voucher (`voucher_code`, `org_id`, `created_date`, `issued_to`, `current_user`, `voucher_series_id`, `created_by`, `amount`, `bill_number`, `max_allowed_redemptions`, `issued_at_counter_id`, `rule_map`, `pin_code`) "
		." VALUES ('$safe_code', '$org_id', $created_date, '$user_id','$user_id', '$safe_series_id', '$created_by', '$amount', '$safe_bill_number', $max_allowed_redemptions, '$issued_at_counter_id', '$rule_map', $pin_code)");

		if ($id == false || $id == NULL)
		{ 
			$logger->debug("Voucher:: insetion into voucher failed. Returning false");
			return false;
		}
		$logger->debug("Voucher:: update post issue voucher");
		$v->vch_series->updatePostIssueVoucher();

		//Signal the event
		$v->logger->debug('@@Start of Signal Loyalty Issue Voucher Event');
		$v->signalLoyaltyIssueVoucherEvent( $user_id , $code , $series_id , $v->vch_series->description );
		$v->logger->debug('@@End of Signal Loyalty Issue Voucher Event');
		
        Util::addRuleStats($series_id, $user_id, $bill_number, $rule_map, true);

		return $code;

	}

	private static function getAllVouchersInSeriesHash($org_id, $series_id, $user_ids){

		$v = new Voucher();
		
		$sql = "SELECT issued_to, voucher_code 
				FROM voucher 
				WHERE org_id = $org_id AND voucher_series_id = $series_id
				AND issued_to IN (".Util::joinForSql($user_ids).")
				ORDER BY voucher_id ASC 
				";
		
		return $v->db->query_hash($sql, 'issued_to', 'voucher_code');
		
	}
	
	/**
	 * A voucher is issued to a particular user. It has to belong to a particular series from which it picks up its properties
	 * This method is typically called at the time of issuing a voucher.
	 *
	 * The following conditions must be satisfied:
	 * - Belong to he same organization as the series
	 * - The series should be able to create more vouchers
	 * Before issuing the voucher, the userID has to be registered within our system. If not registered yet,
	 * call registerAutomaticallyByMobile() before issuing
	 * @param $series_id Series ID from which the vouchers have to be given
	 * @param $user_ids list of user ids for the voucher has to be issued
	 * @param $org 
	 * @param $created_by The store which is responsible for issuing vouchers
	 * @return A hash map of user_id => voucher_code
	 */
	public static function issueMultipleVouchers($series_id, $user_ids,  $org, $created_by, $group_id){
		
		ini_set('memory_limit', '800M');
		
		$org_id = $org->org_id;
		$dummy_voucher = new Voucher();
		
		$userid_voucher_issued_hash = array();
		$userid_vouchers_temp = array();
		$user_ids_temp = array();
		$voucher_codes_generated = array();
		
		$length = $org->getConfigurationValue(CONF_VOUCHER_CODE_LENGTH, 8);
		$is_alphanumeric = $org->getConfigurationValue(CONF_VOUCHER_CODE_IS_ALPHANUMERIC, false);
		$is_offline_redemption_enabled = $org->getConfigurationValue(CONF_LOYALTY_ENABLE_OFFLINE_REDEMPTION, false);
		
		$prepend_external_id = $org->getConfigurationValue(CONF_VOUCHER_CODE_PREPEND_EXTERNAL_ID, Util::isMOMnMeOrg($org_id));
		
		//Check common stuff first
		$vch_series = new VoucherSeries($series_id);
		if (!$vch_series->id == -1)
			return $userid_voucher_issued_hash;

		if ($vch_series->org_id != $org_id)
			return $userid_voucher_issued_hash;

		if (!$vch_series->isCreationValid())
			return $userid_voucher_issued_hash;

		if (!$vch_series->isDateValid())
			return $userid_voucher_issued_hash;
			
		$allow_multiple_vouchers = $vch_series->allowMultipleVouchersPerUser();
		
		//Get the already existing vouchers in the series
		$already_existing_vouchers_in_series = Voucher::getAllVouchersInSeriesHash($org_id, $series_id, $user_ids);
		$already_existing_vouchers_in_series_flipped = count($already_existing_vouchers_in_series) > 0 ? array_flip($already_existing_vouchers_in_series) : array(); //vouchercode => issued_to
		//$all_vouchers_in_org = Voucher::getAllVouchersInOrgHash($org_id);
		
		$num_new_vouchers_created = 0;
		$new_vouchers_created_sqls = array();
		
		$iteration = 0;
		$db_batch_size = 100; //will create 50 or more random codes and insert into database 
		
		foreach($user_ids as $user_id){

			$iteration++;
			
			//check if there any mutually exclusive series selected, continue with creation of voucher 
			//only if there is no voucher issued to this user in the set of series selected as exclusive
			if($vch_series->isVoucherPresentInMutuallyExclusiveSeries($user_id)){
				$userid_voucher_issued_hash[$user_id] = '';
                                if(!$vch_series->doNotResendExistingVoucherToCustomer()){                       
                                        $userid_voucher_issued_hash[$user_id] = $already_existing_vouchers_in_series[$user_id];
                                }
				if($iteration != count($user_ids)) //If its the last user..  proceed to insert into the database
					continue;
			}
			
			
			if(!$allow_multiple_vouchers){
				if(isset($already_existing_vouchers_in_series[$user_id])){
					if($vch_series->doNotResendExistingVoucherToCustomer())
						$userid_voucher_issued_hash[$user_id] = '';
					else{
						$userid_voucher_issued_hash[$user_id] = $already_existing_vouchers_in_series[$user_id];
					$dummy_voucher->logger->debug("Already Existing in series Voucher with Code ".$userid_voucher_issued_hash[$user_id]." and series = $series_id  for userid $user_id ($iteration)");
                                        }
					if($iteration != count($user_ids)) //If its the last user..  proceed to insert into the database
						continue;				
				}					
			}
			
			//========================================================================
			//TODO : Batch.. Will still work in the old way
			//if the client handling type is DISC_CODE_PIN, allot an already existing voucher to this user and send back the code
			if(
				!isset($userid_voucher_issued_hash[$user_id])
				&& $vch_series->getClientHandlingType() == 'DISC_CODE_PIN'
			){
	
				
				$code = Voucher::issueVoucherFromDiscPinSeries($org_id, $user_id, $series_id);
				
				if(!$code){
					$userid_voucher_issued_hash[$user_id] = '';
					continue;
				}
				
				//add in final and the existing
				$userid_voucher_issued_hash[$user_id] = $code;
				$already_existing_vouchers_in_series[$user_id] = $code;				
				continue;
				
			}
			//========================================================================
			
			//Collect the user id
			if(!in_array($user_id, $user_ids_temp)
			&& !isset($userid_voucher_issued_hash[$user_id])
			) //Avoid duplicates
				array_push($user_ids_temp, $user_id);
			
			//Collect a certain number of userids for whom the vouchers have to be issued
			//If its the last customer..  start creating vouchers.. and insert
			//Also check if there is somebody for whom the vouchers have to be created
			if(
				count($user_ids_temp) > 0
			&&	( count($user_ids_temp) == $db_batch_size 
				  || $iteration == count($user_ids) 
				)
			){

				$voucher_codes_generated = array();
				$voucher_codes_generated_temp = array();
				$new_vouchers_created_sqls = array();
				$userid_vouchers_temp = array();
				
				$dummy_voucher->logger->debug("Trying to generate atleast $db_batch_size random voucher codes for ".count($user_ids_temp)." users");
				
				//collect batch size number of voucher codes atleast for allocation
				while(count($voucher_codes_generated) < $db_batch_size){
				
					//generate the random codes
					do{
						$code = $dummy_voucher->generateRandomCode($length, $is_alphanumeric);
						//If offline redemption is enabled, send the voucher code prefixed with series id
						if ($is_offline_redemption_enabled) {
							$code = $series_id.'-'.$code;
						}
						$code = Util::makeReadable($code);
						
						//Add to the collection
						if(
							!in_array($code, $voucher_codes_generated_temp)//not present in the already generated codes
							&& !isset($already_existing_vouchers_in_series_flipped[$code]) //not present in the series atleast
						)
							array_push($voucher_codes_generated_temp, $code);						
						
					}while(						
						(count($voucher_codes_generated_temp) < (2 * $db_batch_size)) //collect atleast twice the number needed						
					);
					
					
					//Find out how many of them are not present in the vouchers table
					$sql = "SELECT voucher_code FROM voucher WHERE org_id = '$org_id' AND voucher_code IN (".Util::joinForSql($voucher_codes_generated_temp).")";
					$voucher_codes_generated_temp_existing = $dummy_voucher->db->query_firstcolumn($sql);
					
					
					//remove already existing code
					//Code already exists and do not risk it
					if(count($voucher_codes_generated_temp_existing) > 0){
						
						$diff_array = array();
						//add the vouchers which are not present into the voucher codes generated temp
						//not trusting array_diff of php :)
						//$voucher_codes_generated_temp = array_diff($voucher_codes_generated_temp, $voucher_codes_generated_temp_existing);
						foreach($voucher_codes_generated_temp as $vch_code_gen){
							if(!in_array($vch_code_gen, $voucher_codes_generated_temp_existing))
								array_push($diff_array, $vch_code_gen);							
						} 
						
						$voucher_codes_generated_temp = $diff_array;
												
						//continue;						
					}

					//merge to total voucher codes generated
					if(count($voucher_codes_generated_temp) > 0)
						$voucher_codes_generated = array_merge($voucher_codes_generated, $voucher_codes_generated_temp);
				}
				
				//TODO Taking a chance here. as we are checking of voucher uniqueness before addition of the customer external id 
				$user_external_id_hash = array();
				if(Util::isMOMnMeOrg($org_id) && count($user_ids_temp) > 0 && $prepend_external_id)
				{	
					$user_external_id_hash = 
						$dummy_voucher->db->query_hash(
							"
							 SELECT user_id, external_id
							 FROM user_management.loyalty
							 WHERE `publisher_id` = '$org_id'
							 	AND `user_id` IN (".Util::joinForSql($user_ids_temp).")
							", 
							'user_id', 'external_id'
						);
				}
				
				$timezone = StoreProfile::getById($created_by)->getStoreTimeZoneLabel();
				$current_date = Util::getCurrentTimeInTimeZone($timezone, null);
				$current_date = empty($current_date) ? ' NOW() ' : "'$current_date'";
				
				//Now there are as many vouchers needed to allocate it to the customers
				for($i = 0; $i < count($user_ids_temp); $i++){

					$temp_user_id = $user_ids_temp[$i];
					$temp_voucher_code = $voucher_codes_generated[$i];
					
					if(Util::isMOMnMeOrg($org_id) && $prepend_external_id && ($user_external_id_hash[$temp_user_id] != ""))
						$temp_voucher_code = $user_external_id_hash[$temp_user_id].$temp_voucher_code;
					
					$vch_sql = "('$temp_voucher_code', '$org_id', $current_date, '$temp_user_id','$temp_user_id', '$series_id', '$created_by', '0', '', '$group_id')";
					
					array_push($new_vouchers_created_sqls, $vch_sql);
					
					$dummy_voucher->logger->debug("Creating a new Voucher with Code $temp_voucher_code and series = $series_id for user_id $temp_user_id ($i)");
					
					$userid_vouchers_temp[$temp_user_id] = $temp_voucher_code;
					$userid_voucher_issued_hash[$temp_user_id] = ""; //So that there is no points exception
				}
				
			
				//Add to the database
				$sql = " INSERT INTO voucher (`voucher_code`, `org_id`, `created_date`, `issued_to`, `current_user`, `voucher_series_id`, `created_by`, `amount`, `bill_number`, `group_id` ) VALUES ";
				$sql .= implode($new_vouchers_created_sqls, ',');
				//update the users-vouchers in series hash and the series post issue
				if($dummy_voucher->db->update($sql)){//update only if voucher creation is fine			
					$vch_series->updatePostIssueVoucher(count($user_ids_temp));
					
					//Add only on success
					foreach($userid_vouchers_temp as $u => $v){
						$userid_voucher_issued_hash[$u] = $v;
						$already_existing_vouchers_in_series[$u] = $v;
						$already_existing_vouchers_in_series_flipped[$v] = $u;
					}
				}
				
				//Clean up
				$voucher_codes_generated_temp = array();
				$voucher_codes_generated = array();
				$new_vouchers_created_sqls = array();
				$userid_vouchers_temp = array();
				$user_ids_temp = array();
			
			
			}// End of If for batch issual
			
			
			
		}//End of foreach on user ids
		
	
		$dummy_voucher->logger->debug("Done issuing vouchers in bulk. Total ".count($userid_voucher_issued_hash));
		return $userid_voucher_issued_hash;
	}
	


	/**
	 * A wrapper around the VoucherSeries->createVoucherSeries function, so that the Autoload works fine.
	 * @see VoucherSeries::createVoucherSeries()
	 */
	public static function createVoucherSeries($org_id, $seriesdescription, $series_type, $client_handling_type, $discount_code, $series_code, $valid_till_date,
		$valid_days_from_create, $max_create_count, $max_use_count, $transferrable, $any_user, $same_user_multiple_redeem, $allow_referral_existing_users, $multiple_use, 
		$is_validation_required, $disable_sms, $sms_template, $info, $allow_multiple_vouchers_per_user, $do_not_resend_existing_voucher, $mutual_exclusive_series_ids, $store_ids, $dvs_enabled, $dvs_expiry_date, $priority, 
		$terms_and_condition, $signal_redemption_event, $sync_to_client, $short_sms_template, $max_vouchers_per_user, $min_days_between_vouchers, 
		$max_referrals_per_referee, $show_pin_code, $discount_on, $discount_type, $discount_value, $creating_user_id, $dvs_items , $redemption_range
	) {

		return VoucherSeries::createVoucherSeries($org_id, $seriesdescription, $series_type, $client_handling_type, $discount_code, $series_code, $valid_till_date,
			$valid_days_from_create, $max_create_count, $max_use_count, $transferrable, $any_user, $same_user_multiple_redeem, $allow_referral_existing_users, $multiple_use,
			$is_validation_required, $disable_sms, $sms_template, $info, $allow_multiple_vouchers_per_user, $do_not_resend_existing_voucher, $mutual_exclusive_series_ids, $store_ids, $dvs_enabled, $dvs_expiry_date, $priority, 
			$terms_and_condition, $signal_redemption_event, $sync_to_client, $short_sms_template, $max_vouchers_per_user, $min_days_between_vouchers, 
			$max_referrals_per_referee, $show_pin_code, $discount_on, $discount_type, $discount_value, $creating_user_id, $dvs_items , $redemption_range
		);
	}
	
	/**
	 * A wrapper around the VoucherSeries->updateVoucherSeries function, so that the Autoload works fine.
	 * @see VoucherSeries::updateVoucherSeries()
	 */
	public static function updateVoucherSeries($voucher_series_id, $org_id, $seriesdescription, $series_type, $client_handling_type, $discount_code, $series_code, $valid_till_date,
		$valid_days_from_create, $max_create_count, $max_use_count, $transferrable, $any_user, $same_user_multiple_redeem, $allow_referral_existing_users, $multiple_use, $is_validation_required, 
		$disable_sms, $sms_template, $info, $allow_multiple_vouchers_per_user, $do_not_resend_existing_voucher, $mutual_exclusive_series_ids, $store_ids, $dvs_enabled, $dvs_expiry_date, $priority, $terms_and_condition, $signal_redemption_event,
		$sync_to_client, $short_sms_template, $max_vouchers_per_user, $min_days_between_vouchers, 
		$max_referrals_per_referee, $show_pin_code, $discount_on, $discount_type, $discount_value, $creating_user_id, $dvs_items , $redemption_range
	) {

		return VoucherSeries::updateVoucherSeries($voucher_series_id, $org_id, $seriesdescription, $series_type, $client_handling_type, $discount_code, $series_code, $valid_till_date,
			$valid_days_from_create, $max_create_count, $max_use_count, $transferrable, $any_user, $same_user_multiple_redeem, $allow_referral_existing_users, $multiple_use, $is_validation_required,
			$disable_sms, $sms_template, $info, $allow_multiple_vouchers_per_user, $do_not_resend_existing_voucher, $mutual_exclusive_series_ids, $store_ids, $dvs_enabled, $dvs_expiry_date, $priority, $terms_and_condition, $signal_redemption_event,
			$sync_to_client, $short_sms_template, $max_vouchers_per_user, $min_days_between_vouchers, 
			$max_referrals_per_referee, $show_pin_code, $discount_on, $discount_type, $discount_value, $creating_user_id, $dvs_items , $redemption_range
		);
	}

	/**
	 * A wrapper around the VchSeries constructor so that Autoload works
	 * @see VoucherSeries::__construct()
	 */
	public static function getVoucherSeriesById($vch_series_id) {
		global $logger;
		$logger->debug("Finding voucher series");
		$vs = new VoucherSeries($vch_series_id);
		if ($vs->id == -1) return false;
		return $vs;
	}

	public static function getVoucherSeriesAsOptions($org_id, $exclude_expired = true) {
		$db = new Dbase('campaigns');
		
		$expiry_filter = "";
		if($exclude_expired)
			$expiry_filter = " AND DATE(valid_till_date) > DATE(NOW()) ";
		
		$d = $db->query("
			SELECT id, description, valid_till_date 
			FROM voucher_series 
			WHERE org_id = '$org_id' $expiry_filter  
			ORDER BY id DESC
			LIMIT 100
		");
		$options = array();
		foreach ($d as $row) {
			$options[$row['description'].' ( Expires : '.$row['valid_till_date'].' )'] = $row['id'];
		}
		return $options;
	}

	public static function getResponseErrorMessage($err_code) {
		global $vouchers_error_message;
		return $vouchers_error_message[$err_code];
	}
	
	public static function getResponseErrorKey($err_code) {
		global $vouchers_error_keys;
		return $vouchers_error_keys[$err_code];
	}
	
	public static function getVouchers($org_id, $user_id, $order = false) {
		$db = new Dbase('campaigns');
		
		$filter = ($order) ? "ORDER BY v.created_date DESC " : "";
		
		$data = $db->query("
			SELECT v.voucher_code, vs.description, v.created_date, s.username as store, v.amount, v.bill_number, vs.id
			FROM voucher v 
			JOIN voucher_series vs ON vs.id = v.voucher_series_id
			JOIN user_management.users s ON s.id = v.created_by
			WHERE v.org_id = '$org_id' AND v.issued_to = '$user_id'
			$filter 
		");

		return $data;
	}
	
	public static function moveVouchers($org_id, $from_user_id, $to_user_id, $sure = false){
		
		if(!$sure)
			return false;
		
		$db = new Dbase('campaigns');
		
		//move all the redemptions
		$sql = "
			UPDATE `voucher_redemptions` vr, voucher v
				SET `used_by` = '$to_user_id'
			WHERE v.org_id = $org_id AND vr.used_by = '$from_user_id'
					v.voucher_id = vr.voucher_id
		";
		$db->update($sql);		
		
		//move all the vouchers
		$sql = "
			UPDATE `voucher`
				SET `issued_to` = '$to_user_id',
					`current_user` = '$to_user_id'
			WHERE org_id = $org_id AND (`issued_to` = '$from_user_id' OR `current_user` = '$from_user_id')
		";
		return $db->update($sql);
	}
	
	
	public function signalRedemptionEvent($user_id){
		
		global $currentorg;
		
		$user_id = $user_id ? $user_id : $this->getIssuedTo();
		
		//signal the voucher redemption event
		$signal_params = array();
		$signal_params['redeemed_voucher_code'] = $this->getVoucherCode();
		$signal_params['user_id'] = $user_id;
		$signal_params['redemption_voucher_series_id'] = $this->getSeriesId();
		$signal_params['times_redeemed'] = $this->getRedeemCountForUser($user_id);
		$signal_params['max_allowed_redemptions'] = $this->getMaxAllowedRedemptions();
		$lm = new ListenersMgr($currentorg);
		$ret = $lm->signalListeners(EVENT_VOUCHER_REDEMPTION, $signal_params, $this->getSeriesId());
		return $ret;
				
	}

        /**
           Fetches the information about the redemption status of the voucher
           bill number, bill amount, redemption date etc     
        **/
        public function getRedemptionInfo()
        {        
              global $logger, $currentorg;  
        
              $logger->debug("Fetching the redemption info for voucher: " . $this->voucher_id);

              $org_id = $currentorg->org_id;
              $voucher_id = $this->voucher_id;                 

              $sql = "SELECT * FROM voucher_redemptions WHERE org_id = $org_id
                      AND voucher_id = $voucher_id   
                     ";   
                
               $db = new Dbase('campaigns'); 			
               $res = $db->query_firstrow($sql);  
               return $res;
        }
    
    /**
     * Signal the Loyalty Issue Voucher event
     * @param unknown_type $user_id
     */
	public function signalLoyaltyIssueVoucherEvent( $user_id , $vch , $vch_series_id , $description ){
		
		global $currentorg;
		
		//signal the voucher redemption event
		$params = array();
		
		$lm = new ListenersMgr( $currentorg );

		$params['user_id'] = $user_id;
		$params['voucher_code'] = $vch;
		$params['voucher_series_id'] = $vch_series_id;
		$params['description'] = $description;
					
		$ret = $lm->signalListeners( EVENT_LOYALTY_ISSUE_VOUCHER, $params );
		
		return $ret;
	}
}

?>

