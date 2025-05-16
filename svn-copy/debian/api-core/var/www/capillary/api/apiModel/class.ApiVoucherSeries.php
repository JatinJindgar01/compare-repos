<?php

/*
*
* -------------------------------------------------------
* CLASSNAME:        VoucherSeries
* GENERATION DATE:  29.11.2011
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  voucher_series
* FOR MYSQL DB:     campaigns
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiVoucherSeriesModel
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $org_id;
	protected $description;
	protected $tag;
	protected $series_type;
	protected $client_handling_type;
	protected $discount_code;
	protected $valid_till_date;
	protected $valid_days_from_create;
	protected $max_create;
	protected $max_redeem;
	protected $transferrable;
	protected $any_user;
	protected $same_user_multiple_redeem;
	protected $allow_referral_existing_users;
	protected $multiple_use;
	protected $is_validation_required;
	protected $created_by;
	protected $num_issued;
	protected $num_redeemed;
	protected $created;
	protected $last_used;
	protected $series_code;
	protected $sms_template;
	protected $disable_sms;
	protected $info;
	protected $allow_multiple_vouchers_per_user;
	protected $do_not_resend_existing_voucher;
	protected $mutual_exclusive_series_ids;
	protected $store_ids_json;
	protected $dvs_enabled;
	protected $dvs_expiry_date;
	protected $priority;
	protected $terms_and_condition;
	protected $signal_redemption_event;
	protected $sync_to_client;
	protected $short_sms_template;
	protected $max_vouchers_per_user;
	protected $min_days_between_vouchers;
	protected $max_referrals_per_referee;
	protected $show_pin_code;
	protected $discount_on;
	protected $discount_type;
	protected $discount_value;
	protected $dvs_items;
	protected $redemption_range;
	protected $min_bill_amount;
	protected $max_bill_amount;
	protected $redeem_at_store;
	protected $campaign_id;

	protected $redemption_valid_from;
	protected $min_days_between_redemption;
	protected $max_redemptions_in_series_per_user;
	
	protected $database; // Instance of class database

	protected $table = 'voucher_series';
	private $current_org_id;

	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function ApiVoucherSeriesModel()
	{	
		global $currentorg;
		$this->current_org_id = $currentorg->org_id;
		
		$this->database = new Dbase( 'campaigns' );

	}


	// **********************
	// GETTER METHODS
	// **********************


	function getId()
	{	
		return $this->id;
	}

	function getOrgId()
	{	
		return $this->org_id;
	}

	function getDescription()
	{	
		return $this->description;
	}
	
	function getTag()
	{	
		return $this->tag;
	}
	
	function getCampaignId()
	{	
		return $this->campaign_id;
	}

	function getSeriesType()
	{	
		return $this->series_type;
	}

	function getClientHandlingType()
	{	
		return $this->client_handling_type;
	}

	function getDiscountCode()
	{	
		return $this->discount_code;
	}

	function getValidTillDate()
	{	
		return $this->valid_till_date;
	}

	function getValidDaysFromCreate()
	{	
		return $this->valid_days_from_create;
	}

	function getMaxCreate()
	{	
		return $this->max_create;
	}

	function getMaxRedeem()
	{	
		return $this->max_redeem;
	}

	function getTransferrable()
	{	
		return $this->transferrable;
	}

	function getAnyUser()
	{	
		return $this->any_user;
	}

	function getSameUserMultipleRedeem()
	{	
		return $this->same_user_multiple_redeem;
	}

	function getAllowReferralExistingUsers()
	{	
		return $this->allow_referral_existing_users;
	}

	function getMultipleUse()
	{	
		return $this->multiple_use;
	}

	function getIsValidationRequired()
	{	
		return $this->is_validation_required;
	}

	function getCreatedBy()
	{	
		return $this->created_by;
	}

	function getNumIssued()
	{	
		return $this->num_issued;
	}

	function getNumRedeemed()
	{	
		return $this->num_redeemed;
	}

	function getCreated()
	{	
		return $this->created;
	}

	function getLastUsed()
	{	
		return $this->last_used;
	}

	function getSeriesCode()
	{	
		return $this->series_code;
	}

	function getSmsTemplate()
	{	
		return $this->sms_template;
	}

	function getDisableSms()
	{	
		return $this->disable_sms;
	}

	function getInfo()
	{	
		return $this->info;
	}

	function getAllowMultipleVouchersPerUser()
	{	
		return $this->allow_multiple_vouchers_per_user;
	}

	function getDoNotResendExistingVoucher()
	{	
		return $this->do_not_resend_existing_voucher;
	}

	function getMutualExclusiveSeriesIds()
	{	
		return $this->mutual_exclusive_series_ids;
	}

	function getStoreIdsJson()
	{	
		return $this->store_ids_json;
	}

	function getDvsEnabled()
	{	
		return $this->dvs_enabled;
	}

	function getDvsExpiryDate()
	{	
		return $this->dvs_expiry_date;
	}

	function getPriority()
	{	
		return $this->priority;
	}

	function getTermsAndCondition()
	{	
		return $this->terms_and_condition;
	}

	function getSignalRedemptionEvent()
	{	
		return $this->signal_redemption_event;
	}

	function getSyncToClient()
	{	
		return $this->sync_to_client;
	}

	function getShortSmsTemplate()
	{	
		return $this->short_sms_template;
	}

	function getMaxVouchersPerUser()
	{	
		return $this->max_vouchers_per_user;
	}

	function getMinDaysBetweenVouchers()
	{	
		return $this->min_days_between_vouchers;
	}

	function getMaxReferralsPerReferee()
	{	
		return $this->max_referrals_per_referee;
	}

	function getShowPinCode()
	{	
		return $this->show_pin_code;
	}

	function getDiscountOn()
	{	
		return $this->discount_on;
	}

	function getDiscountType()
	{	
		return $this->discount_type;
	}

	function getDiscountValue()
	{	
		return $this->discount_value;
	}

	function getDvsItems()
	{	
		return $this->dvs_items;
	}

	function getRedemptionRange()
	{	
		return $this->redemption_range;
	}

	function getMinBillAmount()
	{	
		return $this->min_bill_amount;
	}

	function getMaxBillAmount()
	{	
		return $this->max_bill_amount;
	}

	function getRedeemAtStore()
	{	
		return $this->redeem_at_store;
	}

	function getRedemptionValidFrom()
	{	
		return $this->redemption_valid_from;
	}
	
	function getMinDaysBetweenRedemption()
	{	
		return $this->min_days_between_redemption;
	}
	
	function getMaxRedemptionsInSeriesPerUser()
	{	
		return $this->max_redemptions_in_series_per_user;
	}
	
	// **********************
	// SETTER METHODS
	// **********************


	function setId( $id )
	{
		$this->id =  $id;
	}

	function setOrgId( $org_id )
	{
		$this->org_id =  $org_id;
	}

	function setDescription( $description )
	{
		$this->description = $this->database->realEscapeString( $description );
	}

	function setSeriesType( $series_type )
	{
		$this->series_type =  $series_type;
	}

	function setClientHandlingType( $client_handling_type )
	{
		$this->client_handling_type =  $client_handling_type;
	}

	function setDiscountCode( $discount_code )
	{
		$this->discount_code = $this->database->realEscapeString( $discount_code );
	}

	function setValidTillDate( $valid_till_date )
	{
		$this->valid_till_date =  $valid_till_date;
	}

	function setValidDaysFromCreate( $valid_days_from_create )
	{
		$this->valid_days_from_create =  $valid_days_from_create;
	}

	function setMaxCreate( $max_create )
	{
		$this->max_create =  $max_create;
	}

	function setMaxRedeem( $max_redeem )
	{
		$this->max_redeem =  $max_redeem;
	}

	function setTransferrable( $transferrable )
	{
		$this->transferrable =  $transferrable;
	}
	
	function setTag( $tag ){
		$this->tag = $this->database->realEscapeString( $tag );
	}
	
	function setCampaignId( $campaign_id ){
		$this->campaign_id = $campaign_id;
	}

	function setAnyUser( $any_user )
	{
		$this->any_user =  $any_user;
	}

	function setSameUserMultipleRedeem( $same_user_multiple_redeem )
	{
		$this->same_user_multiple_redeem =  $same_user_multiple_redeem;
	}

	function setAllowReferralExistingUsers( $allow_referral_existing_users )
	{
		$this->allow_referral_existing_users =  $allow_referral_existing_users;
	}

	function setMultipleUse( $multiple_use )
	{
		$this->multiple_use =  $multiple_use;
	}

	function setIsValidationRequired( $is_validation_required )
	{
		$this->is_validation_required =  $is_validation_required;
	}

	function setCreatedBy( $created_by )
	{
		$this->created_by =  $created_by;
	}

	function setNumIssued( $num_issued )
	{
		$this->num_issued =  $num_issued;
	}

	function setNumRedeemed( $num_redeemed )
	{
		$this->num_redeemed =  $num_redeemed;
	}

	function setCreated( $created )
	{
		$this->created =  $created;
	}

	function setLastUsed( $last_used )
	{
		$this->last_used =  $last_used;
	}

	function setSeriesCode( $series_code )
	{
		$this->series_code = $this->database->realEscapeString( $series_code );
	}

	function setSmsTemplate( $sms_template )
	{
		$this->sms_template = $this->database->realEscapeString( $sms_template );
	}

	function setDisableSms( $disable_sms )
	{
		$this->disable_sms =  $disable_sms;
	}

	function setInfo( $info )
	{
		$this->info = $this->database->realEscapeString( $info );
	}

	function setAllowMultipleVouchersPerUser( $allow_multiple_vouchers_per_user )
	{
		$this->allow_multiple_vouchers_per_user =  $allow_multiple_vouchers_per_user;
	}

	function setDoNotResendExistingVoucher( $do_not_resend_existing_voucher )
	{
		$this->do_not_resend_existing_voucher =  $do_not_resend_existing_voucher;
	}

	function setMutualExclusiveSeriesIds( $mutual_exclusive_series_ids )
	{
		$this->mutual_exclusive_series_ids = $this->database->realEscapeString( $mutual_exclusive_series_ids );
	}

	function setStoreIdsJson( $store_ids_json )
	{
		$this->store_ids_json = $this->database->realEscapeString( $store_ids_json );
	}

	function setDvsEnabled( $dvs_enabled )
	{
		$this->dvs_enabled =  $dvs_enabled;
	}

	function setDvsExpiryDate( $dvs_expiry_date )
	{
		$this->dvs_expiry_date =  $dvs_expiry_date;
	}

	function setPriority( $priority )
	{
		$this->priority =  $priority;
	}

	function setTermsAndCondition( $terms_and_condition )
	{
		$this->terms_and_condition = $this->database->realEscapeString( $terms_and_condition );
	}

	function setSignalRedemptionEvent( $signal_redemption_event )
	{
		$this->signal_redemption_event =  $signal_redemption_event;
	}

	function setSyncToClient( $sync_to_client )
	{
		$this->sync_to_client =  $sync_to_client;
	}

	function setShortSmsTemplate( $short_sms_template )
	{
		$this->short_sms_template = $this->database->realEscapeString( $short_sms_template );
	}

	function setMaxVouchersPerUser( $max_vouchers_per_user )
	{
		$this->max_vouchers_per_user =  $max_vouchers_per_user;
	}

	function setMinDaysBetweenVouchers( $min_days_between_vouchers )
	{
		$this->min_days_between_vouchers =  $min_days_between_vouchers;
	}

	function setMaxReferralsPerReferee( $max_referrals_per_referee )
	{
		$this->max_referrals_per_referee =  $max_referrals_per_referee;
	}

	function setShowPinCode( $show_pin_code )
	{
		$this->show_pin_code =  $show_pin_code;
	}

	function setDiscountOn( $discount_on )
	{
		$this->discount_on =  $discount_on;
	}

	function setDiscountType( $discount_type )
	{
		$this->discount_type =  $discount_type;
	}

	function setDiscountValue( $discount_value )
	{
		$this->discount_value =  $discount_value;
	}

	function setDvsItems( $dvs_items )
	{
		$this->dvs_items = $this->database->realEscapeString( $dvs_items );
	}

	function setRedemptionRange( $redemption_range )
	{
		$this->redemption_range = $this->database->realEscapeString( $redemption_range );
	}

	function setMinBillAmount( $min_bill_amount )
	{
		$this->min_bill_amount =  $min_bill_amount;
	}

	function setMaxBillAmount( $max_bill_amount )
	{
		$this->max_bill_amount =  $max_bill_amount;
	}

	function setRedeemAtStore( $redeem_at_store )
	{
		$this->redeem_at_store = $this->database->realEscapeString( $redeem_at_store );
	}

	function setRedemptionValidFrom( $redemption_valid_from )
	{	
		$this->redemption_valid_from = $redemption_valid_from;
	}
	
	function setMinDaysBetweenRedemption( $min_days_between_redemption )
	{	
		$this->min_days_between_redemption = $min_days_between_redemption;
	}
	
	function setMaxRedemptionsInSeriesPerUser( $max_redemptions_in_series_per_user )
	{	
		$this->max_redemptions_in_series_per_user = $max_redemptions_in_series_per_user;
	}
	
	// **********************
	// SELECT METHOD / LOAD
	// **********************

	function load( $id )
	{

		$safe_id = Util::mysqlEscapeString($id);
		$sql =  "SELECT * FROM voucher_series WHERE id = '$safe_id'";
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->org_id = $row->org_id;
		$this->tag = stripcslashes($row->tag);
		$this->campaign_id = $row->campaign_id;
		$this->description = stripcslashes($row->description);
		$this->series_type = $row->series_type;
		$this->client_handling_type = $row->client_handling_type;
		$this->discount_code = $row->discount_code;
		$this->valid_till_date = $row->valid_till_date;
		$this->valid_days_from_create = $row->valid_days_from_create;
		$this->max_create = $row->max_create;
		$this->max_redeem = $row->max_redeem;
		$this->transferrable = $row->transferrable;
		$this->any_user = $row->any_user;
		$this->same_user_multiple_redeem = $row->same_user_multiple_redeem;
		$this->allow_referral_existing_users = $row->allow_referral_existing_users;
		$this->multiple_use = $row->multiple_use;
		$this->is_validation_required = $row->is_validation_required;
		$this->created_by = $row->created_by;
		$this->num_issued = $row->num_issued;
		$this->num_redeemed = $row->num_redeemed;
		$this->created = $row->created;
		$this->last_used = $row->last_used;
		$this->series_code = $row->series_code;
		$this->sms_template = stripcslashes($row->sms_template);
		$this->disable_sms = $row->disable_sms;
		$this->info = stripcslashes($row->info);
		$this->allow_multiple_vouchers_per_user = $row->allow_multiple_vouchers_per_user;
		$this->do_not_resend_existing_voucher = $row->do_not_resend_existing_voucher;
		$this->mutual_exclusive_series_ids = $row->mutual_exclusive_series_ids;
		$this->store_ids_json = $row->store_ids_json;
		$this->dvs_enabled = $row->dvs_enabled;
		$this->dvs_expiry_date = $row->dvs_expiry_date;
		$this->priority = $row->priority;
		$this->terms_and_condition = stripcslashes($row->terms_and_condition);
		$this->signal_redemption_event = $row->signal_redemption_event;
		$this->sync_to_client = $row->sync_to_client;
		$this->short_sms_template = stripcslashes($row->short_sms_template);
		$this->max_vouchers_per_user = $row->max_vouchers_per_user;
		$this->min_days_between_vouchers = $row->min_days_between_vouchers;
		$this->max_referrals_per_referee = $row->max_referrals_per_referee;
		$this->show_pin_code = $row->show_pin_code;
		$this->discount_on = $row->discount_on;
		$this->discount_type = $row->discount_type;
		$this->discount_value = $row->discount_value;
		$this->dvs_items = $row->dvs_items;
		$this->redemption_range = $row->redemption_range;
		$this->min_bill_amount = $row->min_bill_amount;
		$this->max_bill_amount = $row->max_bill_amount;
		$this->redeem_at_store = $row->redeem_at_store;
		$this->redemption_valid_from = $row->redemption_valid_from;
		$this->min_days_between_redemption = $row->min_days_between_redemption;
		$this->max_redemptions_in_series_per_user = $row->max_redemptions_in_series_per_user;
		
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement

		$sql =  "

			INSERT INTO voucher_series 
			( 
				org_id,
				description,
				series_type,
				client_handling_type,
				discount_code,
				valid_till_date,
				valid_days_from_create,
				max_create,
				max_redeem,
				transferrable,
				any_user,
				same_user_multiple_redeem,
				allow_referral_existing_users,
				multiple_use,
				is_validation_required,
				created_by,
				num_issued,
				num_redeemed,
				created,
				last_used,
				series_code,
				sms_template,
				disable_sms,
				info,
				allow_multiple_vouchers_per_user,
				do_not_resend_existing_voucher,
				mutual_exclusive_series_ids,
				store_ids_json,
				dvs_enabled,
				dvs_expiry_date,
				priority,
				terms_and_condition,
				signal_redemption_event,
				sync_to_client,
				short_sms_template,
				max_vouchers_per_user,
				min_days_between_vouchers,
				max_referrals_per_referee,
				show_pin_code,
				discount_on,
				discount_type,
				discount_value,
				dvs_items,
				redemption_range,
				min_bill_amount,
				max_bill_amount,
				redeem_at_store,
				redemption_valid_from,
				min_days_between_redemption,
				max_redemptions_in_series_per_user,
				tag,
				campaign_id
			) 
			VALUES 
			( 
				'$this->org_id',
				'$this->description',
				'$this->series_type',
				'$this->client_handling_type',
				'$this->discount_code',
				'$this->valid_till_date',
				'$this->valid_days_from_create',
				'$this->max_create',
				'$this->max_redeem',
				'$this->transferrable',
				'$this->any_user',
				'$this->same_user_multiple_redeem',
				'$this->allow_referral_existing_users',
				'$this->multiple_use',
				'$this->is_validation_required',
				'$this->created_by',
				'$this->num_issued',
				'$this->num_redeemed',
				'$this->created',
				'$this->last_used',
				'$this->series_code',
				'$this->sms_template',
				'$this->disable_sms',
				'$this->info',
				'$this->allow_multiple_vouchers_per_user',
				'$this->do_not_resend_existing_voucher',
				'$this->mutual_exclusive_series_ids',
				'$this->store_ids_json',
				'$this->dvs_enabled',
				'$this->dvs_expiry_date',
				'$this->priority',
				'$this->terms_and_condition',
				'$this->signal_redemption_event',
				'$this->sync_to_client',
				'$this->short_sms_template',
				'$this->max_vouchers_per_user',
				'$this->min_days_between_vouchers',
				'$this->max_referrals_per_referee',
				'$this->show_pin_code',
				'$this->discount_on',
				'$this->discount_type',
				'$this->discount_value',
				'$this->dvs_items',
				'$this->redemption_range',
				'$this->min_bill_amount',
				'$this->max_bill_amount',
				'$this->redeem_at_store',
				'$this->redemption_valid_from',
				'$this->min_days_between_redemption',
				'$this->max_redemptions_in_series_per_user',
				'$this->tag',
				'$this->campaign_id'
			)";
		
		return $this->id = $this->database->insert( $sql );

	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO voucher_series 
			( 
				id,
				org_id,
				description,
				series_type,
				client_handling_type,
				discount_code,
				valid_till_date,
				valid_days_from_create,
				max_create,
				max_redeem,
				transferrable,
				any_user,
				same_user_multiple_redeem,
				allow_referral_existing_users,
				multiple_use,
				is_validation_required,
				created_by,
				num_issued,
				num_redeemed,
				created,
				last_used,
				series_code,
				sms_template,
				disable_sms,
				info,
				allow_multiple_vouchers_per_user,
				do_not_resend_existing_voucher,
				mutual_exclusive_series_ids,
				store_ids_json,
				dvs_enabled,
				dvs_expiry_date,
				priority,
				terms_and_condition,
				signal_redemption_event,
				sync_to_client,
				short_sms_template,
				max_vouchers_per_user,
				min_days_between_vouchers,
				max_referrals_per_referee,
				show_pin_code,
				discount_on,
				discount_type,
				discount_value,
				dvs_items,
				redemption_range,
				min_bill_amount,
				max_bill_amount,
				redeem_at_store ,
				redemption_valid_from,
				min_days_between_redemption,
				max_redemptions_in_series_per_user,
				tag,
				campaign_id
			) 

			VALUES 
			( 
				'$this->id',
				'$this->org_id',
				'$this->description',
				'$this->series_type',
				'$this->client_handling_type',
				'$this->discount_code',
				'$this->valid_till_date',
				'$this->valid_days_from_create',
				'$this->max_create',
				'$this->max_redeem',
				'$this->transferrable',
				'$this->any_user',
				'$this->same_user_multiple_redeem',
				'$this->allow_referral_existing_users',
				'$this->multiple_use',
				'$this->is_validation_required',
				'$this->created_by',
				'$this->num_issued',
				'$this->num_redeemed',
				'$this->created',
				'$this->last_used',
				'$this->series_code',
				'$this->sms_template',
				'$this->disable_sms',
				'$this->info',
				'$this->allow_multiple_vouchers_per_user',
				'$this->do_not_resend_existing_voucher',
				'$this->mutual_exclusive_series_ids',
				'$this->store_ids_json',
				'$this->dvs_enabled',
				'$this->dvs_expiry_date',
				'$this->priority',
				'$this->terms_and_condition',
				'$this->signal_redemption_event',
				'$this->sync_to_client',
				'$this->short_sms_template',
				'$this->max_vouchers_per_user',
				'$this->min_days_between_vouchers',
				'$this->max_referrals_per_referee',
				'$this->show_pin_code',
				'$this->discount_on',
				'$this->discount_type',
				'$this->discount_value',
				'$this->dvs_items',
				'$this->redemption_range',
				'$this->min_bill_amount',
				'$this->max_bill_amount',
				'$this->redeem_at_store',
				'$this->redemption_valid_from',
				'$this->min_days_between_redemption',
				'$this->max_redemptions_in_series_per_user',
				'$this->tag',
				'$this->campaign_id'
			)";
		
		return $this->database->update( $sql );


	}
	
	
	/**
	*
	*@param $id
	*/
	function update( $id )
	{

		$sql = " 
			UPDATE voucher_series 
			SET  
				org_id = '$this->org_id',
				description = '$this->description',
				series_type = '$this->series_type',
				client_handling_type = '$this->client_handling_type',
				discount_code = '$this->discount_code',
				valid_till_date = '$this->valid_till_date',
				valid_days_from_create = '$this->valid_days_from_create',
				max_create = '$this->max_create',
				max_redeem = '$this->max_redeem',
				transferrable = '$this->transferrable',
				any_user = '$this->any_user',
				same_user_multiple_redeem = '$this->same_user_multiple_redeem',
				allow_referral_existing_users = '$this->allow_referral_existing_users',
				multiple_use = '$this->multiple_use',
				is_validation_required = '$this->is_validation_required',
				created_by = '$this->created_by',
				num_issued = '$this->num_issued',
				num_redeemed = '$this->num_redeemed',
				created = '$this->created',
				last_used = '$this->last_used',
				series_code = '$this->series_code',
				sms_template = '$this->sms_template',
				disable_sms = '$this->disable_sms',
				info = '$this->info',
				allow_multiple_vouchers_per_user = '$this->allow_multiple_vouchers_per_user',
				do_not_resend_existing_voucher = '$this->do_not_resend_existing_voucher',
				mutual_exclusive_series_ids = '$this->mutual_exclusive_series_ids',
				store_ids_json = '$this->store_ids_json',
				dvs_enabled = '$this->dvs_enabled',
				dvs_expiry_date = '$this->dvs_expiry_date',
				priority = '$this->priority',
				terms_and_condition = '$this->terms_and_condition',
				signal_redemption_event = '$this->signal_redemption_event',
				sync_to_client = '$this->sync_to_client',
				short_sms_template = '$this->short_sms_template',
				max_vouchers_per_user = '$this->max_vouchers_per_user',
				min_days_between_vouchers = '$this->min_days_between_vouchers',
				max_referrals_per_referee = '$this->max_referrals_per_referee',
				show_pin_code = '$this->show_pin_code',
				discount_on = '$this->discount_on',
				discount_type = '$this->discount_type',
				discount_value = '$this->discount_value',
				dvs_items = '$this->dvs_items',
				redemption_range = '$this->redemption_range',
				min_bill_amount = '$this->min_bill_amount',
				max_bill_amount = '$this->max_bill_amount',
				redeem_at_store = '$this->redeem_at_store',
				redemption_valid_from = '$this->redemption_valid_from',
				min_days_between_redemption = '$this->min_days_between_redemption',
				max_redemptions_in_series_per_user = '$this->max_redemptions_in_series_per_user',
				tag='$this->tag',
				campaign_id='$this->campaign_id'
				
			WHERE id = $id 
			AND org_id = $this->current_org_id ";

		return $result = $this->database->update($sql);

	}

	/**
	*
	*Returns the hash array for the object
	*
	*/
	function getHash(){

		$hash = array();
 
		$hash['id'] = $this->id;
		$hash['org_id'] = $this->org_id;
		$hash['description'] = $this->description;
		$hash['series_type'] = $this->series_type;
		$hash['client_handling_type'] = $this->client_handling_type;
		$hash['discount_code'] = $this->discount_code;
		$hash['valid_till_date'] = $this->valid_till_date;
		$hash['valid_days_from_create'] = $this->valid_days_from_create;
		$hash['max_create'] = $this->max_create;
		$hash['max_redeem'] = $this->max_redeem;
		$hash['transferrable'] = $this->transferrable;
		$hash['any_user'] = $this->any_user;
		$hash['same_user_multiple_redeem'] = $this->same_user_multiple_redeem;
		$hash['allow_referral_existing_users'] = $this->allow_referral_existing_users;
		$hash['multiple_use'] = $this->multiple_use;
		$hash['is_validation_required'] = $this->is_validation_required;
		$hash['created_by'] = $this->created_by;
		$hash['num_issued'] = $this->num_issued;
		$hash['num_redeemed'] = $this->num_redeemed;
		$hash['created'] = $this->created;
		$hash['last_used'] = $this->last_used;
		$hash['series_code'] = $this->series_code;
		$hash['sms_template'] = $this->sms_template;
		$hash['disable_sms'] = $this->disable_sms;
		$hash['info'] = $this->info;
		$hash['allow_multiple_vouchers_per_user'] = $this->allow_multiple_vouchers_per_user;
		$hash['do_not_resend_existing_voucher'] = $this->do_not_resend_existing_voucher;
		$hash['mutual_exclusive_series_ids'] = $this->mutual_exclusive_series_ids;
		$hash['store_ids_json'] = $this->store_ids_json;
		$hash['dvs_enabled'] = $this->dvs_enabled;
		$hash['dvs_expiry_date'] = $this->dvs_expiry_date;
		$hash['priority'] = $this->priority;
		$hash['terms_and_condition'] = $this->terms_and_condition;
		$hash['signal_redemption_event'] = $this->signal_redemption_event;
		$hash['sync_to_client'] = $this->sync_to_client;
		$hash['short_sms_template'] = $this->short_sms_template;
		$hash['max_vouchers_per_user'] = $this->max_vouchers_per_user;
		$hash['min_days_between_vouchers'] = $this->min_days_between_vouchers;
		$hash['max_referrals_per_referee'] = $this->max_referrals_per_referee;
		$hash['show_pin_code'] = $this->show_pin_code;
		$hash['discount_on'] = $this->discount_on;
		$hash['discount_type'] = $this->discount_type;
		$hash['discount_value'] = $this->discount_value;
		$hash['dvs_items'] = $this->dvs_items;
		$hash['redemption_range'] = $this->redemption_range;
		$hash['min_bill_amount'] = $this->min_bill_amount;
		$hash['max_bill_amount'] = $this->max_bill_amount;
		$hash['redeem_at_store'] = $this->redeem_at_store;
		$hash['redemption_valid_from'] = $this->redemption_valid_from;
		$hash['min_days_between_redemption'] = $this->min_days_between_redemption;
		$hash['max_redemptions_in_series_per_user'] = $this->max_redemptions_in_series_per_user;
		$hash['tag']=$this->tag;
		$hash['campaign_id']=$this->campaign_id;
		
		return $hash;
	}
} // class : end

?>