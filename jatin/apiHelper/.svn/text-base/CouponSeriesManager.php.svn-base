<?php 
//TODO: referes to cheetah
include_once 'model_extension/campaigns/class.VoucherSeriesModelExtension.php';
include_once 'helper/coupons/uploader/impl/CouponUploaderFactory.php';
/**
 * Coupon series manages the series methods
 * 
 * @author pv
 */
class CouponSeriesManager{
	
	private $logger;
	private $org_id;
	private $user_id;
	private $currentorg;
	private $C_config_manager;
	public $C_voucher_series_model_extension ;
	public $C_voucher_redemption_model;
	
	/*************** CONSTRUCTORS ***********************/
	public function __construct( ){

		global $logger, $currentorg, $currentuser;
		
		$this->logger = &$logger;
		$this->currentorg = $currentorg;
		$this->org_id = &$currentorg->org_id;
		$this->user_id = &$currentuser->user_id;
		$this->C_config_manager = new ConfigManager();
		$this->C_voucher_series_model_extension = new ApiVoucherSeriesModelExtension();

	}

	/**
	 * loads voucher series by id
	 * @param unknown_type $id
	 */
	public function loadById( $id ){
		
		$this->C_voucher_series_model_extension->load( $id );
	}

	/**
	 * Checks if series is voucher can be redeemed by
	 * any user
	 */
	public function isAnyUserRdemptionValid(){
		
		return $this->C_voucher_series_model_extension->getAnyUser();
	}
	
	/**
	 * Is multiple use of voucher is allowed or not
	 */
	public function isMultipleUseAllowed(){
		
		return $this->C_voucher_series_model_extension->getMultipleUse();
	}
	
	/**
	 * Checks if same user can redeem the voucher multiple times.
	 */
	public function isSameUserMultipleRedemptionsAllowed(){
		
		return $this->C_voucher_series_model_extension->getSameUserMultipleRedeem();
	}
	
	/**
	 * maximum number of times voucher can be redeemed by a users
	 */
	public function getMaxAllowedRedemptions(){
		
		return $this->C_voucher_series_model_extension->getMaxRedeem();
	}
	
	/**
	 * Checks The voucher series date validity
	 * 
	 * @param unknown_type $now
	 */
	public function isDateValid( $date = '' ) {
		
		if( $date == '' ) $date = time();
		
		$valid_till_date = strtotime($this->C_voucher_series_model_extension->getValidTillDate());
		$this->logger->debug( "valid_till = $valid_till_date, now_ts = $now, date: $date" );

		if ( $date < $valid_till_date )	 return true;
			
		$this->logger->debug( "Check If Same Day : If So validate" );

		//allow if the same day as well
		//get diff in number of seconds and then divide by number of seconds in a day		
		$daydiff = floor( abs( $date - $valid_till_date ) / ( 60 * 60 * 24 ) );
		if( $daydiff == 0 )	return true;		

		$this->logger->debug( "Returns Not Valid" );
		
		return false;
	}
	
	/**
	 * Checks if redemption is allowed or not
	 */
	public function isRedemptionValid() {
		
		if ( $this->C_voucher_series_model_extension->getMaxRedeem() == -1 ) return true;

		return $this->C_voucher_series_model_extension->isRedemptionLimitValid();
	}

	/**
	 * Checks if bill amount is valid
	 * 
	 * @param unknown_type $bill_amount
	 */
	public function isBillAmountValid( $bill_amount = 0 ){
		
		$bill_amount = (int) $bill_amount;
		$min_amount = $this->C_voucher_series_model_extension->getMinBillAmount();
		$max_amount = $this->C_voucher_series_model_extension->getMaxBillAmount() ;
    	if( $bill_amount > 0 ){
    		 
            if( $min_amount > 0 && $bill_amount < $min_amount ){
            	
                    return false;
            }

            if( $max_amount > 0 && $bill_amount > $max_amount ){
            	
                    return false;
            }
		}
		
		return true;
	}
	
	/**
	 * Is stores lies in selected field
	 */
	public function isStoreValid(){

		$json_stores = $this->C_voucher_series_model_extension->getRedeemAtStore();
		$stores = json_decode( $json_stores, true );
		
		if( in_array( '-1', $stores ) ){
			
			return true;
		}
		
		if( in_array( $this->user_id, $stores ) ){
			
			return true;
		}
		
		return false;
	}

	/**
	 * has customer already used all avaliable codes to him
	 */
	public function isMaxRedemptionPerUserValid( $user_id ){

		$max_allowed_redemption = $this->C_voucher_series_model_extension->getMaxRedemptionsInSeriesPerUser();
		if( $max_allowed_redemption == -1 ) return true;
		
		$redemption_count = 
			$this->C_voucher_series_model_extension->getVoucherRedemptionCountForUser( $user_id );
		if( $max_allowed_redemption > $redemption_count ) return true;
				
		return false;
	}

	/**
	 * has customer already used all avaliable codes to him
	 */
	public function isRedemptionGapValid( $user_id ){

		$gap_between_redemption = $this->C_voucher_series_model_extension->getMinDaysBetweenRedemption();
		if( $max_allowed_redemption == -1 ) return true;
		
		$last_redeemed_at = 
			$this->C_voucher_series_model_extension->getLastRedeemedDateForUser( $user_id );
		
		$this->logger->debug( "Allowed timestamp ". $last_redeemed_at );
		if( !$last_redeemed_at ) return true;	
		$allowed_timestamp = strtotime( Util::getDateByDays( false, $gap_between_redemption, $last_redeemed_at ) );
		$this->logger->debug( "Allowed timestamp ". $allowed_timestamp );
		$now = strtotime( date( "Y-m-d" ) );
		
		if( ( $allowed_timestamp - $now ) <= 0 ) return true;
				
		return false;
	}

	/**
	 * valid date is true or not
	 */
	public function isRedemptionDateValid( ){

		$redemption_valid_from_date = $this->C_voucher_series_model_extension->getRedemptionValidFrom();
		$redemption_valid_from_date_ts = strtotime( $redemption_valid_from_date );
				
		if( time( ) >= $redemption_valid_from_date_ts ) return true;

		return false;
	}
	
	/**
	 * Update the redemption count for the voucher series
	 */
	public function updateSeriesRedeemCount( ) {
		
		$redeem_count = $this->C_voucher_series_model_extension->getNumRedeemed();
		$status = $this->C_voucher_series_model_extension->updateSeriesRedeemCount();
		$final_redeem_count = $this->C_voucher_series_model_extension->getNumRedeemed();

		$this->logger->info( 'Rdemption count updated by one from '.$redeem_count .' to '.$final_redeem_count);
	}
	
	/**
	 * Sees if the series is transferrable
	 */
	public function isTransferrable(){
		
		$this->logger->info( 'Checks if the series is transferrable or not');
		return $this->C_voucher_series_model_extension->getTransferrable();
	}
	
	/**
	 * updates the voucher series details !!! Please read the contract for the
	 * array that needs to be sent in hash format
	 * CONTRACT(
	 * 
	 *	 'description' => VALUE,
 	 *	 'series_type' => VALUE,
 	 *	 'client_handling_type' => VALUE,
 	 *	 'discount_code' => VALUE,
 	 *	 'valid_till_date' => VALUE,
 	 *	 'valid_days_from_create' => VALUE,
 	 *	 'max_create' => VALUE,
 	 *	 'max_redeem' => VALUE,
 	 *	 'transferrable' => VALUE,
 	 *	 'any_user' => VALUE,
 	 *	 'same_user_multiple_redeem' => VALUE,
 	 *	 'allow_referral_existing_users' => VALUE,
 	 *	 'multiple_use' => VALUE,
 	 *	 'is_validation_required' => VALUE,
 	 *	 'created_by' => VALUE,
 	 *	 'num_issued' => VALUE,
 	 *	 'num_redeemed' => VALUE,
 	 *	 'created' => VALUE,
 	 *	 'last_used' => VALUE,
 	 *	 'series_code' => VALUE,
 	 *	 'sms_template' => VALUE,
 	 *	 'info' => VALUE,
 	 *	 'allow_multiple_vouchers_per_user' => VALUE,
 	 *	 'mutual_exclusive_series_ids' => VALUE,
 	 *	 'store_ids_json' => VALUE,
 	 *	 'dvs_enabled' => VALUE,
 	 *	 'dvs_expiry_date' => VALUE,
 	 *	 'priority' => VALUE,
 	 *	 'terms_and_condition' => VALUE,
 	 *	 'signal_redemption_event' => VALUE,
 	 *	 'sync_to_client' => VALUE,
 	 *	 'short_sms_template' => VALUE,
 	 *	 'max_vouchers_per_user' => VALUE,
 	 *	 'min_days_between_vouchers' => VALUE,
 	 *	 'max_referrals_per_referee' => VALUE,
 	 *	 'show_pin_code' => VALUE,
 	 *	 'discount_on' => VALUE,
 	 *	 'discount_type' => VALUE,
 	 *	 'discount_value => VALUE,
 	 *	 'do_not_resend_existing_voucher' => VALUE,
 	 *   'disable_sms' => VALUE,
 	 *   'dvs_items' => VALUE,
 	 *   'campaign_id' => VALUE,
 	 *   'max_redemptions_in_series_per_user' => VALUE,
	 *   'min_days_between_redemption' => VALUE,
	 *   'redemption_valid_from' => VALUE
	 * )
	 */
	public function updateDetails( $series_details ){
		
		$this->isSeriesCodeExists( $series_details['series_code'] );
		
		//Note : Special handling do_not_resend_existing_voucher : have to be reversed as in ui it has been reveresed
		$series_details['do_not_resend_existing_voucher'] = ( $series_details['do_not_resend_existing_voucher'] == false ) ? ( true ) : ( false );
		
		//Setting the details for the voucher series
		$this->C_voucher_series_model_extension->setDescription( $series_details['description'] );
		$this->C_voucher_series_model_extension->setSeriesType( $series_details['series_type'] );
		$this->C_voucher_series_model_extension->setClientHandlingType( $series_details['client_handling_type'] );
		$this->C_voucher_series_model_extension->setDiscountCode( $series_details['discount_code'] );
		$this->C_voucher_series_model_extension->setValidTillDate( $series_details['valid_till_date'] );
		$this->C_voucher_series_model_extension->setValidDaysFromCreate( $series_details['valid_days_from_create'] );
		$this->C_voucher_series_model_extension->setMaxCreate( $series_details['max_create'] );
		$this->C_voucher_series_model_extension->setMaxRedeem( $series_details['max_redeem'] );
		$this->C_voucher_series_model_extension->setTransferrable( $series_details['transferrable'] );
		$this->C_voucher_series_model_extension->setAnyUser( $series_details['any_user'] );
		$this->C_voucher_series_model_extension->setSameUserMultipleRedeem( $series_details['same_user_multiple_redeem'] );
		$this->C_voucher_series_model_extension->setAllowReferralExistingUsers( $series_details['allow_referral_existing_users'] );
		$this->C_voucher_series_model_extension->setMultipleUse( $series_details['multiple_use'] );
		$this->C_voucher_series_model_extension->setIsValidationRequired( $series_details['is_validation_required'] );
		$this->C_voucher_series_model_extension->setLastUsed( $series_details['last_used'] );
		$this->C_voucher_series_model_extension->setSeriesCode( $series_details['series_code'] );
		$this->C_voucher_series_model_extension->setSmsTemplate( $series_details['sms_template'] );
		$this->C_voucher_series_model_extension->setInfo( $series_details['info'] );
		$this->C_voucher_series_model_extension->setAllowMultipleVouchersPerUser( $series_details['allow_multiple_vouchers_per_user'] );
		$this->C_voucher_series_model_extension->setRedemptionRange( $series_details['redemption_range'] );
		
		//mutually exclusive series is stored in json format		
		$this->C_voucher_series_model_extension->setMutualExclusiveSeriesIds( json_encode( $series_details['mutual_exclusive_series_ids'] ) );
		
		//store till is stored in json format
		$this->C_voucher_series_model_extension->setStoreIdsJson( json_encode( $series_details['store_ids_json'] ) );
		
		$this->C_voucher_series_model_extension->setDvsEnabled( $series_details['dvs_enabled'] );
		$this->C_voucher_series_model_extension->setDvsExpiryDate( $series_details['dvs_expiry_date'] ); 
		$this->C_voucher_series_model_extension->setPriority( $series_details['priority'] );
		$this->C_voucher_series_model_extension->setTermsAndCondition( $series_details['terms_and_condition'] );
		$this->C_voucher_series_model_extension->setSignalRedemptionEvent( $series_details['signal_redemption_event'] );
		$this->C_voucher_series_model_extension->setSyncToClient( $series_details['sync_to_client'] );
		$this->C_voucher_series_model_extension->setShortSmsTemplate( $series_details['short_sms_template'] );
		$this->C_voucher_series_model_extension->setMaxVouchersPerUser( $series_details['max_vouchers_per_user'] );
		$this->C_voucher_series_model_extension->setMinDaysBetweenVouchers( $series_details['min_days_between_vouchers'] );
		$this->C_voucher_series_model_extension->setMaxReferralsPerReferee( $series_details['max_referrals_per_referee'] );
		$this->C_voucher_series_model_extension->setShowPinCode( $series_details['show_pin_code'] );
		$this->C_voucher_series_model_extension->setDiscountOn( $series_details['discount_on'] );
		$this->C_voucher_series_model_extension->setDiscountType( $series_details['discount_type'] );
		$this->C_voucher_series_model_extension->setDiscountValue( $series_details['discount_value'] );
		$this->C_voucher_series_model_extension->setMinBillAmount($series_details['min_bill_amount']);
		$this->C_voucher_series_model_extension->setMaxBillAmount($series_details['max_bill_amount']);
		$this->C_voucher_series_model_extension->setRedeemAtStore( $series_details['redeem_stores'] );
		
		//the latest entrants
		$this->C_voucher_series_model_extension->setDoNotResendExistingVoucher( $series_details['do_not_resend_existing_voucher'] );
		$this->C_voucher_series_model_extension->setDisableSms( $series_details['disable_sms'] );
		$this->C_voucher_series_model_extension->setDvsItems( $series_details['dvs_items'] );
		
		$this->C_voucher_series_model_extension->setCampaignId( $series_details['campaign_id'] );
		
		$this->C_voucher_series_model_extension->setMaxRedemptionsInSeriesPerUser( $series_details['max_redemptions_in_series_per_user'] );
		$this->C_voucher_series_model_extension->setMinDaysBetweenRedemption( $series_details['min_days_between_redemption'] );
		$this->C_voucher_series_model_extension->setRedemptionValidFrom( $series_details['redemption_valid_from'] );
		
		//update the series
		$this->C_voucher_series_model_extension->update( $this->C_voucher_series_model_extension->getId() );
	}
	
	/**
	 * Creates the voucher series and loads the object :)
	 * array that needs to be sent in hash format
	 * CONTRACT(
	 * 
	 *	 'description' => VALUE,
 	 *	 'series_type' => VALUE,
 	 *	 'client_handling_type' => VALUE,
 	 *	 'discount_code' => VALUE,
 	 *	 'valid_till_date' => VALUE,
 	 *	 'valid_days_from_create' => VALUE,
 	 *	 'max_create' => VALUE,
 	 *	 'max_redeem' => VALUE,
 	 *	 'transferrable' => VALUE,
 	 *	 'any_user' => VALUE,
 	 *	 'same_user_multiple_redeem' => VALUE,
 	 *	 'allow_referral_existing_users' => VALUE,
 	 *	 'multiple_use' => VALUE,
 	 *	 'is_validation_required' => VALUE,
 	 *	 'created_by' => VALUE,
 	 *	 'num_issued' => VALUE,
 	 *	 'num_redeemed' => VALUE,
 	 *	 'created' => VALUE,
 	 *	 'last_used' => VALUE,
 	 *	 'series_code' => VALUE,
 	 *	 'sms_template' => VALUE,
 	 *	 'info' => VALUE,
 	 *	 'allow_multiple_vouchers_per_user' => VALUE,
 	 *	 'mutual_exclusive_series_ids' => VALUE,
 	 *	 'store_ids_json' => VALUE,
 	 *	 'dvs_enabled' => VALUE,
 	 *	 'dvs_expiry_date' => VALUE,
 	 *	 'priority' => VALUE,
 	 *	 'terms_and_condition' => VALUE,
 	 *	 'signal_redemption_event' => VALUE,
 	 *	 'sync_to_client' => VALUE,
 	 *	 'short_sms_template' => VALUE,
 	 *	 'max_vouchers_per_user' => VALUE,
 	 *	 'min_days_between_vouchers' => VALUE,
 	 *	 'max_referrals_per_referee' => VALUE,
 	 *	 'show_pin_code' => VALUE,
 	 *	 'discount_on' => VALUE,
 	 *	 'discount_type' => VALUE,
 	 *	 'discount_value => VALUE,
 	 *	 'do_not_resend_existing_voucher' => VALUE,
 	 *   'disable_sms' => VALUE,
 	 *   'dvs_items' => VALUE,
 	 *   'campaign_id' => VALUE,
 	 *   'max_redemptions_in_series_per_user' => VALUE,
	 *   'min_days_between_redemption' => VALUE,
	 *   'redemption_valid_from' => VALUE
	 * )
	 */
	public function create( $series_details ){
		
		$this->isSeriesCodeExists( $series_details['series_code'] );
		
		//Note : Special handling do_not_resend_existing_voucher : have to be reversed as in ui it has been reveresed
		$series_details['do_not_resend_existing_voucher'] = ( $series_details['do_not_resend_existing_voucher'] == false ) ? ( true ) : ( false );
		
		$this->C_voucher_series_model_extension->setOrgId( $this->org_id );
		
		//Setting the details for the voucher series
		$this->C_voucher_series_model_extension->setDescription( $series_details['description'] );
		$this->C_voucher_series_model_extension->setTag( $series_details['tag'] );
		$this->C_voucher_series_model_extension->setSeriesType( $series_details['series_type'] );
		$this->C_voucher_series_model_extension->setClientHandlingType( $series_details['client_handling_type'] );
		$this->C_voucher_series_model_extension->setDiscountCode( $series_details['discount_code'] );
		$this->C_voucher_series_model_extension->setValidTillDate( $series_details['valid_till_date'] );
		$this->C_voucher_series_model_extension->setValidDaysFromCreate( $series_details['valid_days_from_create'] );
		$this->C_voucher_series_model_extension->setMaxCreate( $series_details['max_create'] );
		$this->C_voucher_series_model_extension->setMaxRedeem( $series_details['max_redeem'] );
		$this->C_voucher_series_model_extension->setTransferrable( $series_details['transferrable'] );
		$this->C_voucher_series_model_extension->setAnyUser( $series_details['any_user'] );
		$this->C_voucher_series_model_extension->setSameUserMultipleRedeem( $series_details['same_user_multiple_redeem'] );
		$this->C_voucher_series_model_extension->setAllowReferralExistingUsers( $series_details['allow_referral_existing_users'] );
		$this->C_voucher_series_model_extension->setMultipleUse( $series_details['multiple_use'] );
		$this->C_voucher_series_model_extension->setIsValidationRequired( $series_details['is_validation_required'] );
		$this->C_voucher_series_model_extension->setCreatedBy( $series_details['created_by'] );
		$this->C_voucher_series_model_extension->setNumIssued( $series_details['num_issued'] );
		$this->C_voucher_series_model_extension->setNumRedeemed( $series_details['num_redeemed'] );
		$this->C_voucher_series_model_extension->setCreated( $series_details['created'] );
		$this->C_voucher_series_model_extension->setLastUsed( $series_details['last_used'] );
		$this->C_voucher_series_model_extension->setSeriesCode( $series_details['series_code'] );
		$this->C_voucher_series_model_extension->setSmsTemplate( $series_details['sms_template'] );
		$this->C_voucher_series_model_extension->setInfo( $series_details['info'] );
		$this->C_voucher_series_model_extension->setAllowMultipleVouchersPerUser( $series_details['allow_multiple_vouchers_per_user'] );
		
		//stores in json ecoded form : for exclusive series.
		$this->C_voucher_series_model_extension->setMutualExclusiveSeriesIds( json_encode( $series_details['mutual_exclusive_series_ids'] ) );
		$this->C_voucher_series_model_extension->setRedemptionRange(  $series_details['redemption_range'] ) ;
		$this->C_voucher_series_model_extension->setMaxBillAmount($series_details['max_bill_amount']);
		$this->C_voucher_series_model_extension->setMinBillAmount($series_details['min_bill_amount']);
		
		// stores store till ids in json encoded format
		$this->C_voucher_series_model_extension->setStoreIdsJson( json_encode( $series_details['store_ids_json'] ) );
		
		$this->C_voucher_series_model_extension->setDvsEnabled( $series_details['dvs_enabled'] );
		$this->C_voucher_series_model_extension->setDvsExpiryDate( $series_details['dvs_expiry_date'] ); 
		$this->C_voucher_series_model_extension->setPriority( $series_details['priority'] );
		$this->C_voucher_series_model_extension->setTermsAndCondition( $series_details['terms_and_condition'] );
		$this->C_voucher_series_model_extension->setSignalRedemptionEvent( $series_details['signal_redemption_event'] );
		$this->C_voucher_series_model_extension->setSyncToClient( $series_details['sync_to_client'] );
		$this->C_voucher_series_model_extension->setShortSmsTemplate( $series_details['short_sms_template'] );
		$this->C_voucher_series_model_extension->setMaxVouchersPerUser( $series_details['max_vouchers_per_user'] );
		$this->C_voucher_series_model_extension->setMinDaysBetweenVouchers( $series_details['min_days_between_vouchers'] );
		$this->C_voucher_series_model_extension->setMaxReferralsPerReferee( $series_details['max_referrals_per_referee'] );
		$this->C_voucher_series_model_extension->setShowPinCode( $series_details['show_pin_code'] );
		$this->C_voucher_series_model_extension->setDiscountOn( $series_details['discount_on'] );
		$this->C_voucher_series_model_extension->setDiscountType( $series_details['discount_type'] );
		$this->C_voucher_series_model_extension->setDiscountValue( $series_details['discount_value'] );
		$this->C_voucher_series_model_extension->setRedeemAtStore( $series_details['redeem_stores']);
		
		//the latest entrants
		$this->C_voucher_series_model_extension->setDoNotResendExistingVoucher( $series_details['do_not_resend_existing_voucher'] );
		$this->C_voucher_series_model_extension->setDisableSms( $series_details['disable_sms'] );
		$this->C_voucher_series_model_extension->setDvsItems( $series_details['dvs_items'] );
		
		$this->C_voucher_series_model_extension->setCampaignId( $series_details['campaign_id'] );
		
		$this->C_voucher_series_model_extension->setMaxRedemptionsInSeriesPerUser( $series_details['max_redemptions_in_series_per_user'] );
		$this->C_voucher_series_model_extension->setMinDaysBetweenRedemption( $series_details['min_days_between_redemption'] );
		$this->C_voucher_series_model_extension->setRedemptionValidFrom( $series_details['redemption_valid_from'] );
		
		//create the series
		$this->C_voucher_series_model_extension->insert( );
		
		if( !$this->C_voucher_series_model_extension->getId() ) 
			throw new Exception( 'Please try again later series could not be created.' );
	}
	
	/**
	 * Checks if the series code is uniquely defined or not
	 * 
	 * @param unknown_type $series_code
	 * @param unknown_type $id
	 */
	public function isSeriesCodeExists( $series_code, $id = false ){
		
		$count = $this->C_voucher_series_model_extension->getCountBySeriesCode( $series_code, $id );
		
		if( $count > 0 ) throw new Exception( 'The Series Code Already Exists. Please Provide Other Code', -1000 );
	}
	
	/**
	 * returns the voucher series details in 
	 * hash array
	 */
	public function getDetails(){
		
		return $this->C_voucher_series_model_extension->getHash();
	}
	
	/**
	 * returns the voucher series as option for the organization
	 * @param $exclude_expired for expiry checking
	 */
	public function getSeriesAsOptions( $exclude_expired = false ){
		
		$this->logger->info( 'Returns the voucher series as options!!!');
		
		$options = array();
		
		if( !$exclude_expired )
			$voucher_series = $this->C_voucher_series_model_extension->getAll( );
		else
			$voucher_series = $this->C_voucher_series_model_extension->getCouponSeriesAsOptionsWithExpiryCheck();
			
		foreach( $voucher_series as $row ){
			
			//include description and the expiry date for the series
			$key = $row['description'].' ( Expires : '.$row['valid_till_date'].' )';
			
			$options[ $key ] = $row['id'];
		}
		 
		return $options;
	}
	
	/**
	 *Check if the user already has a voucher in any one of the selected mutually exclusive voucher series
	 *if found retunrs true else returns false
	 *
	 *@param $user_id User id for whom the checking has to be done
	 *@return True if a voucher is present in any of the series. False if no series has been selected or no voucher exists for the current user in any of the series
	 */
	public function isVoucherPresentInMutuallyExclusiveSeries( $user_id ){
		
		$this->logger->info( 'Checks if the user has been issued a voucher from exclusive series');
		
		foreach( $this->getMutualExclusiveSeries() as $me_vs_id ){

			$coupon_series = new CouponSeriesManager();
			$coupon_series->loadById( $me_vs_id );
			
			if( $coupon_series->getNumberOfVouchersForUser( $user_id ) > 0 ){
				
				$this->logger->info( 'Voucher has been issued to user from series : '.$me_vs_id );
				return true;
			}				
		}
		
		$this->logger->info( 'Nothing was found !!! ' );
		return false;
	}
	
	/**
	 * get the mutually exclusive series
	 */
	public function getMutualExclusiveSeries(){
		
		$this->logger->info( 'Checks if series has been defined as mutually exclusive to this series ' );
		$exclusive_series_ids_json = $this->C_voucher_series_model_extension->getMutualExclusiveSeriesIds();
		
		$mutual_exclusive_series_ids = array();
		return json_decode( $exclusive_series_ids_json, true );
	}
	
	/**
	 * Returns the number of vouchers issued to user for the series
	 * @param $user_id
	 */
	public function getNumberOfVouchersForUser( $user_id ){

		$this->logger->info( 'Checks the number of voucher issued to the user from the series' );
		return $this->C_voucher_series_model_extension->getNumberOfVouchersForUser( $user_id );
	}
	
	/**
	 * Returns the voucher last issued in the series for the customers
	 * 
	 * 
	 * @param unknown_type $user_id
	 */
	public function getLastIssuedVoucherForCustomer( $user_id ){

		$this->logger->info( 'get last issued voucher from the series' );

		return $this->C_voucher_series_model_extension->getLastIssuedVoucherForCustomer( $user_id );
	}
	
	/**
	 * 
	 */
	public function getVoucherRedemptionId( $user_id ){
		
		return $this->C_voucher_series_model_extension->getVoucherRedemptionId( $user_id );
	}
	
	/**
	 * Difference between issual of the two vouchers
	 * 
	 * @param unknown_type $user_id
	 */
	public function getDaysFromLastVoucherForUser( $user_id ){

		$this->logger->info( 'days between issual of the vouchers from series' );
		
		return $this->getDaysFromLastVoucherForUser($user_id );
	}
	
	/**
	 * Checks if the creation of new voucher in the series is valid
	 */
	public function isCreationValid(){
		
		$this->logger->info( 'check creation is valid or not by checking max creation of the voucher' );
		$max_create = $this->C_voucher_series_model_extension->getMaxCreate();
		
		if ( $max_create == -1 ) return true;

		$num_issued = $this->C_voucher_series_model_extension->getNumIssued();
		$max_create = $this->C_voucher_series_model_extension->getMaxCreate();
		
		if( $num_issued < $max_create ) return true;

		$this->logger->info( 'num issued has exceeded max create by '.$num_issued - $max_create );
		return false;
	}
	
	/**
	 * Increase the voucher issual count
	 */
	public function increaseVoucherIssueCount( $count = 1 ){
		
		$this->logger->info( 'num issued count has to be increased by one' );
		
		$num_issued = $this->C_voucher_series_model_extension->getNumIssued();
		$this->C_voucher_series_model_extension->setNumIssued( $num_issued + $count );
		
		$series_id = $this->C_voucher_series_model_extension->getId(); 
		$this->C_voucher_series_model_extension->update( $series_id );
	}
	
	/**
	 * help text for the coupon series fields
	 */
	public function getHelpTextForSeriesFields(){
		
		$help_texts = array(
									'description' => '',
									'info' => 'Complete description of the series. Less than 100 chars',
									'series_type' => 'CAMPAIGNVOUCHER => Bulk SMS Campaigns <br> Dynamic Vouchering => DVS <br> Alliance => External Gift vouchers',
									'client_handling_type' => '',
									'discount_code' => 'Code to be applied in POS',
									'series_code' => 'Unique code for DVS Rules',
									'valid_till_date' => 'Does not allow vouchers issual, redemption etc on after this date',
									'valid_days_from_create' => 'Number of days till which a voucher is valid after creation',
									'discount_on' => 'Discount is to be given on',
									'discount_type' => 'Whether its is a percentage(PERC) or absolute(ABS) value',
									'discount_value' => 'Will be used as percentage or absolute value based on above selected value',
									'max_create' => 'Set the maximum number of coupons that can be issued',
									'max_redeem' => 'Set the maximum number of coupons can be redeemed',
									'priority' => 'Higher value => Higher Priority<br>Used for selection of vouchers in case of a limit during DVS',
									'signal_redemption_event' => 'If selected, signals a voucher redemption event',
									'transferrable' => 'Allow transfer of voucher from one person to another',
									'any_user' => 'If selected, can be redeemed by anyone',
									'multiple_use' => 'Allows redemption of a voucher multiple times',
									'same_user_multiple_redeem' => 'Allows redemption of a voucher multiple times by the same customer',
									'allow_referral_existing_users' => 'Allows referral of already existing customers in !nTouch',
									'max_referrals_per_referee' => '-1 for no limit. Maximum number of times a referee can be referred by any referrer',
									'allow_multiple_vouchers_per_user' => 'Same customer can have multiple vouchers in the same series',
									'do_not_resend_existing_voucher' => 'If above is unchecked, and this is checked, If a customer already has a voucher, it is not resent',
									'max_vouchers_per_user' => 'Set the maximum number of coupons that can be issued per customer',
									'min_days_between_vouchers' => 'Set the validity of the coupons series from the date of coupon creation',
									'is_validation_required' => '',
									'disable_sms' => 'If selected, sms will not be sent',
									'sms_template' => 'Choose from : {{voucher_code}} {{cust_name}} {{voucher_expiry_date}} {{bill_store_name}} {{bill_store_number}}',
									'short_sms_template' => 'Choose from : {{voucher_code}} {{cust_name}} {{voucher_expiry_date}} {{bill_store_name}} {{bill_store_number}}. Used when multiple sms have to clubbed together during DVS',
									'terms_and_condition' => 'terms_and_condition',
									'mutual_exclusive_series_id' => 'If a Voucher is issued for a user in any one of the above selected series, Voucher will not be issued in this series',
									'dvs_enabled' => 'Include in DVS ?',
									'store_ids' => 'Do not select any store id DVS is disabled. Series will be used in the selected stores for DVS. CTRL A selects all',			
									'dvs_expiry_date' => 'Above option should be selected',
									'sync_to_client' => 'Sends the vouchers to the client',
									'show_pin_code' => 'Whether to display the pin code on successful redemption',
						      		'dvs_items' => "Add Text to be printed on paper based DVS Vouchers of this series, Multiple items to be separated by ; (semi-colon)",
									'outbound_sms_template' => '<b>Available message tags:</b> <br>{{cust_name}}<br>{{voucher_code}}<br>{{voucher_expiry_date}}<br>{{bill_store_name}}<br>{{bill_store_number}}',
									'max_redemptions_in_series_per_user' => 'Set the maximum number of times a customer can a redeem a coupon',
	 								'min_days_between_redemption' => 'Set the time period within which a coupon has to be redeemed to avail for multiple redemption',
	 								'redemption_valid_from' => ''
							);
		
			return $help_texts;
	}

	/**
	 * Returns the client handling type for the series at client side
	 */
	public function getClientTypeHandlingAsOptions(){
		
		$client_handling_types = array (
									 'Using Discount Code To Be Used At Cashier' => 'DISC_CODE',
									 'Use Voucher and PIN Code At Cashier' => 'DISC_CODE_PIN',
									);
	
		return $client_handling_types;							
	}
	
	/**
	 * The default values to be picked for the creation of 
	 * the coupon series
	 */
	public function getDefaultValues(){
		
		$params = array();
		
		//description
		$params['description'] = 'Series Description';
		$params['info'] = 'Series Name';
		
		$params['series_type'] = 'CAMPAIGN';
		$params['client_handling_type'] = 'DISC_CODE';
		$params['discount_code'] = 'XYZ123';
		
		//validity
		$params['series_code'] = 'auto generated';
		$params['valid_till_date'] = Util::getDateByDays( false, 31 );
		$params['valid_days_from_create'] = 30;
		
		//discount
		$params['discount_on'] = 'BILL';		
		$params['discount_type'] = 'ABS';
		$params['discount_value'] = 0;

		//radios
		$params['max_create'] = -1;
		$params['max_redeem'] = -1;
		$params['priority'] = 0;
		$params['signal_redemption_event'] = true;
		$params['transferrable'] = false;
		$params['any_user'] = true;
		$params['multiple_use'] = true;
		$params['same_user_multiple_redeem'] = false;
		$params['allow_referral_existing_users'] = false;
		$params['max_referrals_per_referee'] = -1;
		$params['allow_multiple_vouchers_per_user'] = false;

		//do_not_resend_existing_voucher : special handling : UI text is reversed
		$params['do_not_resend_existing_voucher'] = true;
		$params['max_vouchers_per_user'] = -1;
		$params['min_days_between_vouchers'] = -1;
		$params['is_validation_required'] = false;
		$params['disable_sms'] = false;
		$params['sms_template'] = "Hello {{cust_name}}, your voucher code is {{voucher_code}}";
		$params['short_sms_template'] = "Hello {{cust_name}}, your voucher code is {{voucher_code}}";
		
		$params['mutual_exclusive_series_id'] = false;

		$params['sync_to_client'] = true;
		$params['show_pin_code'] = false;
		
		//options for redemtion
		$params['range_dow'] = "-1";
		$params['range_dom'] = "-1";
		$params['range_hours'] = "-1";
		
		//options for dvs enabled and dvs expiry date
		$params['dvs_enabled'] = false;
		$params['store_ids'] = array( -1 );
		//add store options for selection
		$params['dvs_expiry_date'] = Util::getDateByDays( false, 31 );
    	$params['dvs_items'] = false;
    	$params['max_bill_amount'] = '0';
    	$params['min_bill_amount'] = '0';
    	
    	$params['max_redemptions_in_series_per_user'] = '-1';
    	$params['min_days_between_redemption'] = '-1';
    	$params['redemption_valid_from'] = date('Y-m-d');
    	
    	//Tag default value. Used in DVS
    	$params['tag']="TAG123";
    	
    	return $params;
	}
	
	/**
	 * Same The Create function : for the Contract consult the function
	 * 
	 * This functions check the default & passed value. If not passed the params
	 * is stored with the default values for the creation.
	 * 
	 * @param unknown_type $params
	 */
	public function prepareAndCreate( $params, $series_id = false ){
		$this->logger->debug('@@@PARAMS:-'.print_r( $params , true ));
		//The Main Problem will happen for the default values
		//so creatin the whole new function for default handling
		$default_values = $this->getDefaultValues();

		//description
		$params['description'] =  $params['info'] ;
		$params['tag'] = $params['tag'];
		
		$params['range_dom'] = Util::valueOrDefault( $params['range_dom'], array($default_values['range_dom']));
		$params['range_hours'] = Util::valueOrDefault( $params['range_hours'], array($default_values['range_hours']));
		$params['range_dow'] = Util::valueOrDefault( $params['range_dow'], array($default_values['range_dow']));
		
		
		$redemption_range = array('dom'=>$params['range_dom'] , 
								  'dow'=>$params['range_dow'] ,
								  'hours'=> $params['range_hours']
								 );
		
		$params['redeem_stores'] = Util::valueOrDefault( $params['redeem_stores'], $default_values['store_ids']  );
		$params['redemption_range'] =  json_encode( $redemption_range) ;
		$params['redeem_stores'] = json_encode( $params['redeem_stores'] );

		$params['series_type'] = Util::valueOrDefault( $params['series_type'], $default_values['series_type'] );
		$params['client_handling_type'] = Util::valueOrDefault( $params['client_handling_type'], $default_values['client_handling_type'] );
		$params['discount_code'] = Util::valueOrDefault( $params['discount_code'], $default_values['discount_code'] );
		
		//validity
		if( !isset( $params['series_code'] ) ){
			
			$series_code = Util::generateRandomCode( 8, true );
			$params['series_code'] = Util::valueOrDefault( $params['series_code'], $series_code );
		}
		
		$params['valid_till_date'] = ( isset( $params['valid_till_date'] ) ) ? 
			( $params['valid_till_date'] ) : ( $default_values['valid_till_date'] );
			
		$params['valid_days_from_create'] = ( isset( $params['valid_days_from_create'] ) ) ? 
			( $params['valid_days_from_create'] ) : ( $default_values['valid_days_from_create'] );
		
		//discount
		$params['discount_on'] = Util::valueOrDefault( $params['discount_on'], $default_values['discount_on'] );		
		$params['discount_type'] = Util::valueOrDefault( $params['discount_type'], $default_values['discount_type'] );
		$params['discount_value'] = Util::valueOrDefault( $params['discount_value'], $default_values['discount_value'] );

		//radios
		$params['max_create'] = Util::valueOrDefault( $params['max_create'], $default_values['max_create'] );
		$params['max_redeem'] = Util::valueOrDefault( $params['max_redeem'], $default_values['max_redeem'] );
		$params['priority'] = Util::valueOrDefault( $params['priority'], $default_values['priority'] );
	
		$params['transferrable'] = Util::valueOrDefault( $params['transferrable'], $default_values['transferrable'] );

		$params['same_user_multiple_redeem'] = Util::valueOrDefault( $params['same_user_multiple_redeem'], 
			$default_values['same_user_multiple_redeem'] 
		);
		$params['allow_referral_existing_users'] = Util::valueOrDefault( $params['allow_referral_existing_users'], 
			$default_values['allow_referral_existing_users'] 
		);
		$params['max_referrals_per_referee'] = Util::valueOrDefault( $params['max_referrals_per_referee'], 
			$default_values['max_referrals_per_referee'] 
		);
		$params['allow_multiple_vouchers_per_user'] = Util::valueOrDefault( $params['allow_multiple_vouchers_per_user'], 
			$default_values['allow_multiple_vouchers_per_user'] 
		);
		//do_not_resend_existing_voucher
//		$params['do_not_resend_existing_voucher'] = Util::valueOrDefault( $params['do_not_resend_existing_voucher'], 
//			$default_values['do_not_resend_existing_voucher'] 
//		);
		$params['max_vouchers_per_user'] = Util::valueOrDefault( $params['max_vouchers_per_user'], 
			$default_values['max_vouchers_per_user'] 
		);
		$params['min_days_between_vouchers'] = Util::valueOrDefault( $params['min_days_between_vouchers'], 
			$default_values['min_days_between_vouchers'] 
		);
		$params['is_validation_required'] = Util::valueOrDefault( $params['is_validation_required'], 
			$default_values['is_validation_required'] 
		);
		$params['disable_sms'] = Util::valueOrDefault( $params['disable_sms'], 
			$default_values['disable_sms'] 
		);
		$params['sms_template'] = Util::valueOrDefault( $params['sms_template'], 
			$default_values['sms_template'] 
		);
		$params['short_sms_template'] = Util::valueOrDefault( $params['short_sms_template'], 
			$default_values['short_sms_template'] 
		);
		
		$params['mutual_exclusive_series_id'] = Util::valueOrDefault( $params['mutual_exclusive_series_id'], 
			$default_values['mutual_exclusive_series_id'] 
		);

//		$params['sync_to_client'] = Util::valueOrDefault( $params['sync_to_client'], 
//			$default_values['sync_to_client'] 
//		);
		$params['show_pin_code'] = Util::valueOrDefault( $params['show_pin_code'], 
			$default_values['show_pin_code'] 
		);
		
		//options for dvs enabled and dvs expiry date
		$params['dvs_enabled'] = Util::valueOrDefault( $params['dvs_enabled'], 
			$default_values['dvs_enabled'] 
		);
		//add store options for selection
		$params['store_ids_json'] = Util::valueOrDefault( $params['store_ids'], 
			$default_values['store_ids'] 
		);

		$params['dvs_expiry_date'] = Util::valueOrDefault( $params['dvs_expiry_date'], 
			$default_values['dvs_expiry_date'] 
		);
    	$params['dvs_items'] = Util::valueOrDefault( $params['dvs_items'], 
			$default_values['dvs_items'] 
		);
		
		$params['created_by'] = $this->user_id;
		$params['created'] = date( 'Y-m-d' );
		
		$params['max_redemptions_in_series_per_user'] = Util::valueOrDefault( $params['max_redemptions_in_series_per_user'], 
			$default_values['max_redemptions_in_series_per_user'] );
			
    	$params['min_days_between_redemption'] = Util::valueOrDefault( $params['min_days_between_redemption'], 
			$default_values['min_days_between_redemption'] );
		
    	$params['redemption_valid_from'] = Util::valueOrDefault( $params['redemption_valid_from'], 
			$default_values['redemption_valid_from'] );
    	$this->logger->debug("Inside coupon series manager::".print_r( $params, true));
		try{

			if( $series_id ){
				
				$this->loadById( $series_id );
				$this->updateDetails( $params );
			}else
				$id = $this->create( $params );
				
		}catch( Exception $e ){
			
			if( $e->getCode() == -1000 ){

				$series_details = $this->C_voucher_series_model_extension->getAll();
				$series_code = array();
				foreach( $series_code as $value ){
					
					array_push( $series_code, $value['series_code'] );
				}
				//TODO : loop to get the proper series code
			}
			
			return $e->getMessage();
		}

		return 'SUCCESS';
	}
	
	
	/**
	 * returns the name => label
	 * for creation of the radion buttons in UI
	 * for the OutBound Campaigns
	 */
	public function getOutBoundRadioFormLabels(){
		
		return array(
		
			'signal_redemption_event' => 'Signal Coupon Redemption Event ?',
			'transferrable' => 'Is the Coupon Series Transferrable',
			'any_user' => 'Can anybody use the coupon ?',
			'multiple_use' => 'Can the coupon be redeemed more than once ?',
			'same_user_multiple_redeem' => 'Can a customer redeem a coupon multiple times?',
			'allow_multiple_vouchers_per_user' => 'Allow issuing more than one coupon per customer?',
			'do_not_resend_existing_voucher' => 'Resend existing coupon ?',
			'is_validation_required' => 'Is validation required to redeem coupon?',
			'disable_sms' => 'Disable SMS Sending ?',
			'sync_to_client' => 'Sync To Client ?',
			'show_pin_code' => 'Show Pin Code ?'
		);
	} 
	
	/**
	 * returns the name => label
	 * for creation of the radion buttons in UI
	 * for limit wala combo types name => label
	 */
	public function getOutBoundComboFormLabels(){
		
		return array(
		
			'max_create' => 'Limit max number of coupons to be issued?',
			'max_redeem' => 'Limit max number of coupons that can be redeemed?',
			'max_vouchers_per_user' => 'Limit max number of coupons to be issued per customer?',
			'min_days_between_vouchers' => 'Set the validity of the coupons to be issued?',
			'max_redemptions_in_series_per_user' => 'Limit max number of times a customer can redeem a coupon?',
			'min_days_between_redemption' => 'Min Days Between Redemption'
		);
	} 
	
	/**
	 * check if the code is duplicate or not
	 * 
	 * @param unknown_type $row
	 * @param unknown_type $already_read_vouchers
	 * @param unknown_type $params
	 * @param unknown_type $upload_status
	 * @param unknown_type $return_response
	 */
	private function checkDuplicateForUpload( $row, $params, $already_read_vouchers, $upload_status, &$return_response ){
		
		//If duplicate report it as such.
		if
		(
			isset( $already_read_vouchers[$row['voucher_code']] )
			 && 
			!$params['generate_codes']
		)
		{
			$upload_status[$row['voucher_code']] = 'duplicate';
			array_push
			(
				$return_response, 
				array
				(
					'mobile' => $row['mobile'], 
					'voucher_code' => $row['voucher_code'], 
					'pincode' => $row['pin_code'], 
					'status' => 'duplicate_voucher in the same series'
				)
			);
			
			return true;
		}
		
		return false;
	}

	/**
	 * 
	 * @param unknown_type $row
	 * @param unknown_type $params
	 * @param unknown_type $coupon_series_details
	 * @param unknown_type $CountryDetails
	 * @param unknown_type $return_response
	 * @param unknown_type $valid_mobiles
	 * @param unknown_type $already_read_vouchers
	 */
	private function checkMobileForUpload
	( 
		$row, $params, $coupon_series_details, 
		$CountryDetails, &$return_response, 
		&$valid_mobiles, &$already_read_vouchers 
	)
	{
		
		// Now checking fcorrect mobile number
		if( $coupon_series_details['client_handling_type'] != 'DISC_CODE_PIN' )	{
			
			if( !$params['user_null'] ){
				
				$mobile = $row['mobile'];
				 
				if( !Util::checkMobileNumber( $row['mobile'] ) ){
					array_push( 
						$return_response, 
						array(
								'mobile'=> $mobile, 
								'voucher_code' => $row['voucher_code'], 
								'pincode' => $row['pin_code'], 
								'status' => 'mobile_number_invalid'
						)
					);
					return;
				}
				
				if( $params['add_customer_id'] )
					array_push($valid_mobiles, $row['mobile']);
			}
		}
		
		//If mobile is valid too, then add the prefixes now.
		$already_read_vouchers[ $row['voucher_code'] ] = '1';
	}

	/**
	 * add to upload list
	 * 
	 * $batch_upload_params['vid'] = $vid;
	 * $batch_upload_params['row'] = $row;
	 * $batch_upload_params['params'] = $params;
	 * $batch_upload_params['mobile_cust_id_mapping'] = $mobile_cust_id_mapping;
	 * $batch_upload_params['is_offline_redemption_enabled'] = $is_offline_redemption_enabled;
	 * 
	 * @param $return_response
	 * @param $sent_to_upload_list
	 */
	private function addCouponsToUploadList( $batch_upload_params, &$return_response, &$sent_to_upload_list )
	{

		$vid = $batch_upload_params['vid'] ;
		$row = $batch_upload_params['row'] ;
		$params = $batch_upload_params['params'] ;
		$mobile_cust_id_mapping = $batch_upload_params['mobile_cust_id_mapping'] ;
		$is_offline_redemption_enabled = $batch_upload_params['is_offline_redemption_enabled'] ;
		
		if( $params['hifen'] ){

			if( $params['add_customer_id'] && !$params['user_null'] ){

				if( isset( $mobile_cust_id_mapping[$row['mobile']] ) ){

					$row['voucher_code'] = $mobile_cust_id_mapping[$row['mobile']].$row['voucher_code'];
				}else{
					
					$row['voucher_code'] = $mobile_cust_id_mapping[$row['mobile']].$row['voucher_code'];
					array_push(
						$return_response, 
						array(
							'mobile'=> $row['mobile'], 
							'voucher_code' => $row['voucher_code'], 
							'pincode' => $row['pin_code'], 
							'status' => 'Not a loyalty user'
						)
					);
					
					continue;
				}
			}
			
			$row['voucher_code'] = $params['voucher_prefix'].'-'.$row['voucher_code'];
			if( $is_offline_redemption_enabled ){
				
				$row['voucher_code'] = $vid.'-'.$row['voucher_code'];
			}
			array_push($sent_to_upload_list, $row['voucher_code']);
		}else{
			
			if($params['add_customer_id'] && !$params['user_null']){
				
				if(isset($mobile_cust_id_mapping[$row['mobile']])){
				
					$row['voucher_code'] = $mobile_cust_id_mapping[$row['mobile']].$row['voucher_code'];
				}else{
					
					$row['voucher_code'] = $mobile_cust_id_mapping[$row['mobile']].$row['voucher_code'];
					array_push(
						$return_response, 
						array(
							'mobile'=> $row['mobile'], 
							'voucher_code' => $row['voucher_code'], 
							'pincode' => $row['pin_code'], 
							'status' => 'Not a loyalty user'
						)
					);
					continue;
				}
			}
			
			$row['voucher_code'] = $params['voucher_prefix'].$row['voucher_code'];
			if($is_offline_redemption_enabled){
				
				$row['voucher_code'] = $vid.$row['voucher_code'];
			}
			
			array_push($sent_to_upload_list, $row['voucher_code']);
		}
	}
	
	/**
	 * Sent to upload list for the normal vouchers
	 * @param $batch_upload_params
	 * CONTRACT(
	 * 
	 *  $batch_upload_params['vid'] = $vid;
	 *  $batch_upload_params['row'] = $row;
	 *  $batch_upload_params['params'] = $params;
	 *  $batch_upload_params['mobile_cust_id_mapping'] = $mobile_cust_id_mapping;
	 *  $batch_upload_params['is_offline_redemption_enabled'] = $is_offline_redemption_enabled;
	 *  
	 * )
	 * @param unknown_type $return_response
	 * @param unknown_type $sent_to_upload_list
	 */
	private function addDiscPinCoupons( $batch_upload_params, &$return_response, &$sent_to_upload_list )
	{
		
		$vid = $batch_upload_params['vid'] ;
		$row = $batch_upload_params['row'] ;
		$params = $batch_upload_params['params'] ;
		$mobile_cust_id_mapping = $batch_upload_params['mobile_cust_id_mapping'] ;
		$is_offline_redemption_enabled = $batch_upload_params['is_offline_redemption_enabled'] ;
		
		if($params['hifen']){
			
			if($params['add_customer_id'] && !$params['user_null']){
				
				if(isset($mobile_cust_id_mapping[$row['mobile']])){
				
					$row['voucher_code'] = $mobile_cust_id_mapping[$row['mobile']].'-'.$row['voucher_code'];
				}else{
					
					$row['voucher_code'] = $mobile_cust_id_mapping[$row['mobile']].'-'.$row['voucher_code'];
					array_push(
						$return_response, 
						array(
							'mobile'=> $row['mobile'], 
							'voucher_code' => $row['voucher_code'], 
							'pincode' => $row['pin_code'], 
							'status' => 'Not a loyalty user'
						)
					);
					
					continue;
				}
			}
			if($is_offline_redemption_enabled){
			
				$row['voucher_code'] = $vid.'-'.$row['voucher_code'];
			}
			array_push( $sent_to_upload_list, $row['voucher_code'] );
		}else{
	
			if($params['add_customer_id'] && !$params['user_null']){
				if(isset($mobile_cust_id_mapping[$row['mobile']])){
				
					$row['voucher_code'] = $mobile_cust_id_mapping[$row['mobile']].$row['voucher_code'];
				}else{
					
					$row['voucher_code'] = $mobile_cust_id_mapping[$row['mobile']].$row['voucher_code'];
					array_push(
								$return_response, 
								array(
										'mobile'=> $row['mobile'], 
										'voucher_code' => $row['voucher_code'], 
										'pincode' => $row['pin_code'], 
										'status' => 'Not a loyalty user'
								)
					);
					
					continue;
				}
			}
			if($is_offline_redemption_enabled)
				$row['voucher_code'] = $vid.$row['voucher_code'];
				
			array_push($sent_to_upload_list, $row['voucher_code']);
		}		
	}
	
	/**
	 * 
	 * @param unknown_type $data
	 * @param unknown_type $row
	 * @param unknown_type $params
	 * @param unknown_type $mobile_array
	 * @param unknown_type $voucher_codes
	 * @param unknown_type $row_count
	 */
	private function pushCouponCodes( $data, $row, $params, &$mobile_array, &$voucher_codes, &$row_count ){

		foreach( $data as $row ){
			
			if( !$params['user_null'] ){
				
				array_push( $mobile_array , $row['mobile'] );
			}
			if( !$params['generate_codes'] ){
				
				array_push( $voucher_codes , $row['voucher_code'] );
			}
			
			$row_count++;
		}
	}
	
	/**
	 * 
	 * @param $data
	 * @param $params
	 * @param $batch_params
	 * @param $return_data
	 * @param $ins_data
	 * CONRACT(
	 *	$batch_params['mobile_array'] = $mobile_array ;
	 *  $batch_params['voucher_codes'] = $voucher_codes ;
	 *	$batch_params['ins_data'] = $ins_data ;
	 *	$batch_params['return_data'] = $return_data ;
	 *	$batch_params['voucher_codes'] = $voucher_codes ;
	 *	$batch_params['duplicate_list'] = $duplicate_list ;
	 *	$batch_params['duplicates'] = $duplicates ;
	 *	$batch_params['user_mobile_mapping'] = $user_mobile_mapping ;
	 *	$batch_params['created_date'] = $created_date ;
	 *	$batch_params['created_by'] = $created_by ;
	 *	$batch_params['currentuser'] = $currentuser ;
	 *	$batch_params['coupon_series_details'] = $coupon_series_details ;
	 *	$batch_params['voucher_codes_generated'] = $voucher_codes_generated ;
	 *  $batch_params['coupon_manager'] = $C_coupon_manager ;
	 * )
	 * 
	 */
	private function createBulkUploadBatch(  $data, $params, $batch_params, array &$return_data, array &$ins_data ){
		
		$org_id = $this->org_id;
		$C_coupon_manager = $batch_params['coupon_manager'];
		$mobile_array = $batch_params['mobile_array'];
		$voucher_codes = $batch_params['voucher_codes'];
		$voucher_codes = $batch_params['voucher_codes'];
		$duplicate_list = $batch_params['duplicate_list'];
		$duplicates = $batch_params['duplicates'];
		$user_mobile_mapping = $batch_params['user_mobile_mapping'];
	 	$created_date = $batch_params['created_date'];
	 	$created_by = $batch_params['created_by'] ;
	 	$currentuser = $batch_params['currentuser'] ;
	 	$coupon_series_details  = $batch_params['coupon_series_details'];
	 	$voucher_codes_generated  = $batch_params['voucher_codes_generated'];
	 	$voucher_series_id = $coupon_series_details['id'];
	 	$j = 0;
		
		foreach( $data as $line ){

			$mobile = $line['mobile'];
			$pin_code = $line['pin_code'];
			$voucher_code = $line['voucher_code'];
			$pin_code = $pin_code == '' ? 'NULL' : "$pin_code";
			//If the series is disc pin type then assign the issued to and current user to -1 each.
 
			if( $coupon_series_details['client_handling_type'] == 'DISC_CODE_PIN' ){
				
				$issued_to = -1;
				$current_user = -1;
				$disc_code_pin = true;
				if( in_array( $voucher_code, $duplicate_list ) ){
					array_push(
						$return_data, 
						array(
							'mobile'=> 'NA', 
							'voucher_code' => $voucher_code, 
							'pincode' => $pin_code, 
							'status' => 'duplicate_voucher'
						)
					);
					continue;
				}
			}else{
				
				if( $params['user_null'] ){
					
					//if user is null assign the current user as the user.
					$line['mobile'] = $currentuser->mobile;
					//$issued_to = $current_user;
					$user_mobile_mapping[$line['mobile']] = $current_user;
				}else{
		
					if( !( isset( $user_mobile_mapping[$line['mobile']] ) ) ){
						
						array_push( 
							$return_data, 
							array(
									'mobile'=> $mobile, 
									'voucher_code' => $voucher_code, 
									'pincode' => $pin_code, 
									'status' => 'user_invalid'
							)
						);
						
						continue;
					}
				}
				
				//Assign the vouchers generated to each.
				if($params['generate_codes']){
					
					$line['voucher_code'] = $voucher_codes_generated[$j];
					$j++;
				}else{
					
					//if a duplicate exits then report it as duplicate.
					if( isset( $duplicates[$line['voucher_code']] ) ){
						
						array_push( 
									$return_data, 
									array(
										'mobile'=> $mobile, 
										'voucher_code' => $voucher_code, 
										'pincode' => $pin_code, 
										'status' => 'duplicate_voucher'
									)
								);
								
						continue;
					}
				}
			}
			
			//If the voucher nor the user has no problems at all then upload.
			if( $disc_code_pin ){

				array_push(
							$return_data, 
							array( 
									'mobile'=> 'NA',
									'voucher_code' => $voucher_code, 
									'pincode' => $pin_code, 
									'status' => 'normal' 
							) 
				);

				$bulk_create_array = array(
				
					'voucher_code' => $voucher_code,
					'org_id' => $org_id,
					'created_date' => $created_date,
					'issued_to' => -1,
					'current_user' => -1,
					'voucher_series_id' => $voucher_series_id,			
					'created_by' => $created_by,
		 			'pin_code' => $pin_code
				);
				
				$C_coupon_manager->createBulkSqlArray( $ins_data, $bulk_create_array );
				
			}else{
				
				$voucher_code = $line['voucher_code'];
				array_push(
							$return_data, 
							array(
									'mobile'=> $mobile,
									'voucher_code' => $voucher_code, 
									'pincode' => $pin_code, 
									'status' => 'normal'
							)
						);
						
				$bulk_create_array = array(
				
					'voucher_code' => $voucher_code,
					'org_id' => $org_id,
					'created_date' => $created_date,
					'issued_to' => $user_mobile_mapping[$line['mobile']],
					'current_user' => $user_mobile_mapping[$line['mobile']],
					'voucher_series_id' => $voucher_series_id,			
					'created_by' => $created_by,
		 			'pin_code' => $pin_code
				);

				$C_coupon_manager->createBulkSqlArray( $ins_data, $bulk_create_array );
			}
		}
	}
	
	/**
	 * Insert vouchers according to the following parameters provided.
	 * @param $data data read from the file ex: Array(  array('voucher_code'=>'...', 'pin_code'=>'...', 'mobile' =>'...').... )
	 * @param $params the parameters selected on the form.
	 */
	private function insertVouchers( $data , $params, CampaignModelExtension $campaign_model_extension ){
		
		$row_count = 0;

		$coupon_series_details = $this->getDetails();
		$vid = $coupon_series_details['id'];

		$currentuser = StoreProfile::getById( $params['store_id'] );
		$created_by = $this->user_id;

		$created_date = date( 'Y-m-d' );
		$mobile_array = array();
		$voucher_codes = array();
		$ins_data = array();
		$return_data = array();
		$org = $this->currentorg;
		$org_id = $this->org_id;
		$voucher_codes = array();
		$duplicate_list = array();
		$duplicates = array();
		$user_mobile_mapping = array();
		$C_coupon_manager = new CouponManager();
		$is_offline_redemption_enabled = $this->C_config_manager->getKey( CONF_LOYALTY_ENABLE_OFFLINE_REDEMPTION );

		//Check if the series of the disc pin type.
		if ( $coupon_series_details['client_handling_type'] == 'DISC_CODE_PIN' )
			$disc_code_pin = true;
		else
			$disc_code_pin = false;

		if( !$disc_code_pin ){
			
			$this->pushCouponCodes( $data, $row, $params, $mobile_array, $voucher_codes, $row_count );

			//Check if the user exists with a given mobile number.
			if( !$params['user_null'] ){
				
				$result = $campaign_model_extension->checkUsersByMobile( $mobile_array );
				
				foreach( $result as $line ){
					
					$user_mobile_mapping[$line['mobile']] = $line['id'];
				}
			}else{
				
				$user = $current_user;
			}	
			
			//Check for the duplicates.
			if( !$params['generate_codes'] ){

				//Check for the duplicate codes.
				$duplicate_list = CouponManager::getIssuedVoucherCodeFromCodes( $voucher_codes , $org_id );
			}else{
				
				$prefix = $params['voucher_prefix'];
				//Generate voucher codes given the series type and number of codes to be generated.
				$issual_params['prefix'] = $prefix; 
				$issual_params['row_count'] = $row_count;
				$issual_params['hifen'] = $params['hifen'];
				$issual_params['is_offline_redemption_enabled'] = $is_offline_redemption_enabled;
				
				$voucher_codes_generated = $C_coupon_manager->issue( $vid, $issual_params, 'upload' );
			}

			//Making a hash of duplicates for easy search.
			foreach($duplicate_list as $code){
				
				$duplicates[$code] = '1';
			}
		}else{

			foreach( $data as $row ){
			
				array_push($voucher_codes, $row['voucher_code']);
			}
			
			$duplicate_list = CouponManager::getIssuedVoucherCodeFromCodes( $voucher_codes , $org_id );
		}

		$j = 0;
		$batch_params['mobile_array'] = $mobile_array ;
		$batch_params['voucher_codes'] = $voucher_codes ;
		$batch_params['voucher_codes'] = $voucher_codes ;
		$batch_params['duplicate_list'] = $duplicate_list ;
		$batch_params['duplicates'] = $duplicates ;
		$batch_params['user_mobile_mapping'] = $user_mobile_mapping ;
	 	$batch_params['created_date'] = $created_date ;
	 	$batch_params['created_by'] = $created_by ;
	 	$batch_params['currentuser'] = $currentuser ;
	 	$batch_params['coupon_series_details'] = $coupon_series_details ;
	 	$batch_params['voucher_codes_generated'] = $voucher_codes_generated ;
		$batch_params['coupon_manager'] = $C_coupon_manager;
		
		$this->createBulkUploadBatch( $data, $params, $batch_params, $return_data, $ins_data );

		//If there is no data for insertion return false along with the status of all records.
		if (count($ins_data) == 0 ){

			return array( false, $return_data );
		}else{
			
			$result = $C_coupon_manager->addVoucherInBulk( $ins_data );
			return array( $result , $return_data );
		}
	}
	
	/**
	 * 
	 * @param unknown_type $params
	 * @param unknown_type $data
	 */
	private function uploadSanitizedCoupons( $params, $data, $campaign_model_extension, &$return_response ){
		
		if ( $params['confirm'] ){
			
			//This returns the report of the data which has been passed on for insertion.
			list( $status, $responses ) = $this->insertVouchers( $data , $params, $campaign_model_extension );

			;	//$status is the return response from an sql insert.
			if( $status )
				$status = 'SUCCESS';
			else
				$status = 'Error creating vouchers in this batch. Possible duplicates.';
				
			$this->logger->debug( "The upload status is ".$status );
			$uploaded_vouchers = array();
			//Check if the vouchers uploaded, really did get uploaded or not.
			
			$sent_to_upload_list = array();

			foreach( $data as $row ){
				
				array_push( $sent_to_upload_list, $row['voucher_code']);
			}

			$uploaded_vouchers = CouponManager::getIssuedVoucherCodeFromCodes( $sent_to_upload_list , $this->org_id );

			$upload_status = array();
			
			foreach( $uploaded_vouchers as $voucher ){
				
				//Just making a hash to avoid the in_array search for voucher.
				$upload_status[$voucher] = '1';
			}
			
			$count_uploaded = 0;
			
			foreach ( $responses as $line ){

				// Checking the responses if the voucher code which has been sent in to insert has been uploaded or not.
				// If not, just change the status as couldn't upload the voucher.
				if( !isset( $upload_status[$line['voucher_code']] ) ){
					
					$line['status'] = 'couldnt upload';
				}else{

					$count_uploaded++;
				}
				
				if($params['user_null'])
				$line['mobile'] = 'N/A';
				array_push( $return_response, $line );
			}
			
			$this->increaseVoucherIssueCount( $count_uploaded );
		}
		
		return $status;
	}
	
	/**
	 * Upload coupon refactored
	 */
	
	public function getDetailsForDownload($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		$table = $uploader->getTempTableName();
		$sql = "
				SELECT * FROM $table
				WHERE status = 0
				";
		return array('temp_table'=>$table,'sql'=>$sql,'database'=>'Temp');
	}
	
	public function getValidRecordsCount($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		return $uploader->getValidRecordsCount();
	}
	
	public function getErrorRecordsCount($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		return $uploader->getErrorRecordsCount();
	}
	
	public function prepare($params , $filename , $import_type)
	{
		$coupon_series_details = $this->getDetails();
		//die(print_r($coupon_series_details));
		$vid = $coupon_series_details['id'];
		$campaign_id = $coupon_series_details['campaign_id'];
		$client_handling_type = $coupon_series_details['client_handling_type'];
		//return file_id;
		//init
		$this->logger->debug("Import type = $import_type");
		$uploader = CouponUploaderFactory::getUploaderClass($import_type);
		$uploader->setParams($params);
		$uploader->setCampaignId($campaign_id);
		$uploader->setFileName($filename);
		$uploader->setVoucherSeriesId($vid);
		$uploader->setCouponSeriesDetails($coupon_series_details);
		//purges data into campaign_files_history table
		$file_id = $uploader->purge();
		
		if($file_id)
		{
			$this->logger->debug("File Id = $file_id");
		}
		else
		{
			$this->logger->debug("Purge data failed. Throwing exception");
			throw new Exception("Error purging upload details");
		}
		//$campaignStatusMgr = new CampaignStatus($file_id);
			
		$uploader->prepare();
		$uploader->validate();
			
		$valid_count = $uploader->getValidRecordsCount();
// 		if($valid_count == 0)
// 		{
// 			throw new Exception("No valid records to import");
// 		}
		//}
		//		catch(Exception $e)
		//	{
		//	$this->logger->error("Caught exception in prepare = ".$e->getMessage());
			//throw new Exception("Failed preparing the import");
			//	}
			//$preview_data = $uploader->preview(100);
			return $file_id;
	}
	
	public function upload($file_id)
	{
		try{
			$uploader = $this->loadUsingFileDetails($file_id);
			//$group_id = 1;
			$uploader->upload();
			$valid_count = $uploader->getValidRecordsCount();
			$this->increaseVoucherIssueCount( $valid_count);
			
			//$this->generateErrorReport($uploader, $group_id);
		}catch(Exception $e)
		{
			$this->logger->error("Caught exception while uploading. error = ".$e->getMessage());
			throw new Exception($e->getMessage());
			
		}
		return true;
	}
	
	public function preview($file_id, $limit = 10)
	{
		try{
			$uploader = $this->loadUsingFileDetails($file_id);
			//$group_id = 1;
			$preview_data = $uploader->preview($limit);
			
			//$this->generateErrorReport($uploader, $group_id);
		}catch(Exception $e)
		{
			$this->logger->error("Caught exception while preview. error = ".$e->getMessage());
			throw new Exception($e->getMessage());
			
		}
		return $preview_data;
	}
	
	private function loadUsingFileDetails($file_id)
	{
		$file_details = $this->getUploadDetails($file_id);
		$import_type = $file_details['import_type'];
		$campaign_id = $file_details['campaign_id'];
		$vsid = $file_details['vsid'];
		$params = $file_details['params'];
		$temp_table_name = $file_details['temp_table_name'];
		$this->logger->debug("Import Type = $import_type");
		$uploader = CouponUploaderFactory::getUploaderClass($import_type);
	
		//init
		$uploader->setCampaignId($campaign_id);
		$uploader->setVoucherSeriesId($vsid);
		$uploader->setTempTableName($temp_table_name);
		//$params = $uploader->getParams();
	
		return $uploader;
	}
	
	private function getUploadDetails($file_id)
	{
		$db = new Dbase('campaigns');
		$sql = "
				SELECT * FROM campaigns.coupon_upload_history
				WHERE id = $file_id
				";
		$result = $db->query($sql);
		return $result[0];
	}
	
	/**
	 * Upload the coupons into the series
	 * 
	 * @param $params
	 * @param $filename
	 * @param $campaign_model_extension
	 */
	public function uploadCoupons( $params , $filename , $campaign_model_extension ){
		
		$coupon_series_details = $this->getDetails();
		$vid = $coupon_series_details['id'];

		// Set the batch size.
		$file_batch_size = 1000;

		$col_mapping['mobile'] = $params['mob_num'];
		$col_mapping['voucher_code'] = $params['voucher_code'];
		$col_mapping['pin_code'] = $params['pin_code'];
		$settings['header_rows_ignore'] = $params['header_rows_ignore'];
		$settings['footer_rows_ignore'] = $params['footer_rows_ignore'];

		$this->logger->debug("The parameters has been mapped with the column");
		
		$responses = array();
		$batch_response = array();
		$return_response = array();
		$duplicate_response = array();
		$already_read_vouchers = array();
		$spreadsheet = new Spreadsheet();

		$org_id = $this->org_id;

		$start_time = gettimeofday(true);
		$CountryDetails = $this->currentorg->getCountryDetailsForMobileCheck(  );
		
		//check for offline redemption enabled
		$is_offline_redemption_enabled = $this->C_config_manager->getKey( CONF_LOYALTY_ENABLE_OFFLINE_REDEMPTION );
			
		//Batch wise reading the file and uploading.
		while( 
				( 
					$processed_data = 
						$spreadsheet->LoadCSVLowMemory( $filename, $file_batch_size, false, $col_mapping, $settings )  
				) 
					!= 
				(
					false
				) 
			)
		{
			$rowcount = 0;
			$data = array();
			$valid_mobiles = array();
			$sent_to_upload_list = array();
			$already_read_vouchers = array();
			$already_read_vouchers_list = array();
			//Checking for duplicates in the same batch. Across batches its taken care of as the prev ones will already be in the db.
			foreach( $processed_data as $row )
			{
				
				$duplicate = $this->checkDuplicateForUpload
									( 
										$row, $params, $already_read_vouchers, 
										$upload_status, $return_response 
									);
				if( !$duplicate )
				{
					
					$this->checkMobileForUpload
							(
								$row, $params, $coupon_series_details, 
								$CountryDetails, $return_response, 
								$valid_mobiles, $already_read_vouchers
							);
				}
			}

			//modify the vouchers accordingly to have prefix and if its offline-redeemable.
			if( $params['add_customer_id'] && !$params['user_null'] ){

				$mobile_cust_id_mapping = $campaign_model_extension->getExternalId( $valid_mobiles );
			}	
			$batch_upload_params['vid'] = $vid;
			$batch_upload_params['row'] = $row;
			$batch_upload_params['params'] = $params;
			$batch_upload_params['mobile_cust_id_mapping'] = $mobile_cust_id_mapping;
			$batch_upload_params['is_offline_redemption_enabled'] = $is_offline_redemption_enabled;
			$i = 0;
			foreach( $processed_data as $row ){
				
				//New loop
				$batch_upload_params['row'] = $row;
				if( isset( $already_read_vouchers[$row['voucher_code']] ) ){
					
					if( $coupon_series_details['client_handling_type'] != 'DISC_CODE_PIN' ){

						$this->addCouponsToUploadList( $batch_upload_params, $return_response, $sent_to_upload_list );
					}else{
						
						$this->addDiscPinCoupons( $batch_upload_params, $return_response, $sent_to_upload_list );
					}

					$row['voucher_code'] = $sent_to_upload_list[$i] ;
					array_push( $data, $row );
					array_push( $already_read_vouchers_list, $row['voucher_code'] );
					$i++;
				}
			}
			unset( $batch_upload_params );
			//Only the distinct vouchers are uploaded after weeding out the duplicates.
			$status = $this->uploadSanitizedCoupons( $params, $data, $campaign_model_extension, $return_response );
		}
		
		$end_time = gettimeofday(true);
		$time_taken = $end_time - $start_time; // <- total time taken for the upload.

		$this->logger->debug("Time taken is ".$time_taken);

		//Export the response as csv.
		if( count( $return_response ) > 0 ){
			
			$duplicate = new Table();
			$duplicate->importArray( $return_response );
			//$spreadsheet->loadFromTable( $duplicate )->download( "duplicate_vouchers", "csv" );
			$spreadsheet->loadFromTable( $duplicate )->download( "coupons_upload_report", "csv" );
		}

		return $status;
	}

	/**
	 * returns the number of un used vouchers
	 * 
	 */
	public function getNumberOfUnUsedVoucher(){
		
		return $this->C_voucher_series_model_extension->getNumberOfUnUsedVoucher();
	}

	/**
	 * returns the 1st of lot of unused vouchers
	 */
	public function getFirstUnUsedVoucher(){
		
		return $this->C_voucher_series_model_extension->getFirstUnUsedVoucher();
	}
	
	
	public function getActiveCouponSeries( $exclude_expired ){
	
		$this->logger->info( 'Returning the active voucher series ');
	
		$coupon_series = $this->C_voucher_series_model_extension->getCouponSeriesWithExpiryCheck($exclude_expired);
		
		return $coupon_series;
	
	}
	
	public function getCouponSeries ( $series_id = '' , $exclude_expired = ''){
	
		$this->logger->info("Returning voucher series for ids : ".$series_id );
	
		$coupon_series = $this->C_voucher_series_model_extension->getCouponSeriesInfoById( $series_id );
	     
		return $coupon_series;
	}
	
	/**
	 * It will check for redemption range is valid or not
	 */
	public function isRedemptionRangeValid(){

		$redemption_range_string = $this->C_voucher_series_model_extension->getRedemptionRange();
		// Check if Redemption Range is present or not
		if( !$redemption_range_string )
			return true;
			
		$date_format = date( 'd N H' );
		$range = explode( ' ' , $date_format );
		$redemption_range = json_decode( $redemption_range_string , true );
		$is_dom = false;
		$is_dow = false;
		$is_hours = false;
		
		//Check DOM check
		if( $range[0] ){
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
		 
}
?>
