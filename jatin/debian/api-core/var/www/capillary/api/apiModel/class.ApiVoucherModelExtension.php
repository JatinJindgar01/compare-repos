<?php 

include_once 'apiModel/class.ApiVoucher.php';
include_once 'apiModel/class.ApiVoucherSeriesModelExtension.php';

/**
 * Voucher Model Extension Will will be extending the voucher model
 *  
 * @author pv
 */
class ApiVoucherModelExtension extends ApiVoucherModel
{

	private $current_org_id;
	private $logged_in_user_id;
	private $C_voucher_series_model_extension;

	protected $logger;
	public static $campaigns_database;
	
	public function __construct(){

		global $currentorg, $currentuser, $logger;
		
		parent::ApiVoucherModel();

		$this->logger = $logger;
		
		$this->current_org_id = $currentorg->org_id;
		$this->logged_in_user_id = $currentuser->user_id;

		self::$campaigns_database = new Dbase( 'campaigns' );
		$this->C_voucher_series_model_extension = new ApiVoucherSeriesModelExtension();
	}

	/**
	 * Loads the voucher series if its nit loaded for the voucher object
	 */
	private function loadVoucherSeries(){
		
		if( !$this->C_voucher_series_model_extension->getId() )
			$this->C_voucher_series_model_extension->load( $this->getVoucherSeriesId() );	
	}
	
	/**
	 * returns if the redemption event should be signaled for the series
	 */
	public function shouldSignalRedemptionEvent(){ 

		$this->loadVoucherSeries();
		return $this->C_voucher_series_model_extension->getSignalRedemptionEvent(); 
	}
	
	/**
	 * return the expiry date of the voucher
	 */
	public function getExpiryDate( $date_format = "%d-%m-%Y" ){
		
		$this->loadVoucherSeries();
		$valid_days = $this->C_voucher_series_model_extension->getValidDaysFromCreate();
		$this->logger->debug("valid_days $valid_days, created_date: " . $this->getCreatedDate());
		
		$coupon_expiry_date = Util::getDateByDays( false, $valid_days, 
									$this->getCreatedDate(), 
									"%Y-%m-%d" );
		
		$coupon_expiry_timestamp = Util::deserializeFrom8601($coupon_expiry_date);
		$series_expiry_timestamp = Util::deserializeFrom8601(
								$this->C_voucher_series_model_extension->getValidTillDate());
		
		if($coupon_expiry_timestamp > $series_expiry_timestamp)
		{
			return Util::getDateByDays( false, 0, 
					$this->C_voucher_series_model_extension->getValidTillDate(), 
					$date_format );
		}
		else
		{
			return Util::getDateByDays( false, $valid_days, 
					$this->getCreatedDate(), 
					$date_format );
		}
	}
	
	/**
	 * Returns the voucher id by voucher code
	 * 
	 * @param unknown_type $voucher_code
	 */
	public function getVoucherIdByCode( $voucher_code ){
		
		$safe_voucher_code = Util::mysqlEscapeString( $voucher_code );
		$sql = "
				SELECT `voucher_id`
				FROM `$this->table`
				WHERE `org_id` = '$this->current_org_id' AND `voucher_code` = '$safe_voucher_code'
		";
		
		return $this->database->query_scalar( $sql, true );
	}
	
	/**
	 * loads The voucher object by the voucher code
	 * 
	 * @param unknown_type $voucher_code
	 */
	public function loadByCode( $voucher_code ){
		
		$voucher_id = $this->getVoucherIdByCode( $voucher_code );
		
		$this->load( $voucher_id );
		
		$this->loadVoucherSeries();
	}
	
	
	public function loadById($voucher_id){
		
		$this->load($voucher_id);
		$this->loadVoucherSeries();
	}
	
	
	/**
	 * Number Of Times The Coupon has been used aka redeemed.
	 */
	public function getRedeemedCount(){
		
		$sql = " SELECT COUNT(*) AS `redemption_count` 
				 FROM `voucher` AS `v` 
				 JOIN  `voucher_redemptions` AS `vr` ON ( `v`.`voucher_id` = `vr`.`voucher_id` AND `vr`.`org_id` = '$this->current_org_id' )
				 WHERE `v`.`voucher_id` = '$this->voucher_id'";
		
		$count = ( int ) $this->database->query_scalar ( $sql, false );

		$this->logger->info( 'Number of times Voucher has been redeemed : '. $count );
		
		return $count;
	}
	
	/**
	 * Number Of times the customer has redeemed the voucher
	 * 
	 * @param unknown_type $user_id
	 */
	public function getCustomerRedeemedCount( $user_id ){
		
		$sql = "
				SELECT COUNT(*) 
				FROM `voucher_redemptions` 
				WHERE `voucher_id` = $this->voucher_id AND `used_by` = $user_id
		";

		$count = $this->database->query_scalar ($sql);
		
		$this->logger->info( " The User With Id : $user_id has redeemed voucher id $this->voucher_id count : $count ");
		
		return $count;
	}
	
	/**
	 * 
	 * 
	 * @param unknown_type $user_id
	 * @param unknown_type $redeemed_on
	 * @param unknown_type $store_id
	 * @param unknown_type $bill_number
	 * @param unknown_type $validation_code_used
	 * @param unknown_type $counter_id
	 * @param unknown_type $bill_number
	 */
	public function updateRedemption( $user_id, $redeemed_on, $store_id, $bill_number, 
	$validation_code_used, $counter_id, $bill_amount )
	{
		
		$this->logger->info( 'adding redemption details .' );
		
		$safe_bill_number = Util::mysqlEscapeString($bill_number);
		
		$sql = "
			INSERT INTO `voucher_redemptions` 
			( 
				`org_id`, `voucher_series_id`, `voucher_id`, `used_by`, `used_date`, 
				`used_at_store`, `bill_number`, `validation_code_used`, `counter_id`,
				`bill_amount`
			) 
			VALUES 
			(
				'$this->current_org_id', '$this->voucher_series_id', '$this->voucher_id', '$user_id', 
				'$redeemed_on', '$store_id', '$safe_bill_number', '$validation_code_used', '$counter_id',
				'$bill_amount'
			)
		";
		
		return $this->database->insert( $sql );
	}
	
	/**
	 * @return true/false  
	 */
	public function isReferralVoucher(){
		
		$sql = "
				SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END AS `referral_type` 
				FROM `campaign_referrals`
				WHERE 
				org_id = $this->current_org_id
					AND voucher_series_id = $this->voucher_series_id
					AND referrer_id = $this->issued_to
		";
		
		return ( bool ) $this->database->query_scalar ( $sql );
	}
	
	/**
	 * get the referral count per day & total
	 */
	public function getTotalReferrals(){
		
		$sql = "
				SELECT 
					COUNT(*) AS num_referrals_total, 
					IFNULL(SUM(IF(DATE(`created_on`) = DATE(NOW()), 1, 0)), 0) AS `num_referrals_today`
				FROM `campaign_referrals`
				WHERE 
						org_id = $this->current_org_id 
						AND voucher_series_id = $this->voucher_series_id
				 		AND referrer_id = $this->issued_to		
		";
		
		return $this->database->query( $sql );
	}
	
	/**
	 * get referral redeem count
	 */
	public function getReferralRedeemCount(){
		
		$sql = "
				SELECT CASE WHEN COUNT(*) > 1 THEN 1 ELSE 0 END AS redeem_count 
				FROM `voucher_redemptions` 
				WHERE `voucher_id` = $this->voucher_id AND `used_by` != $this->issued_to";
		
		return ( bool ) $this->database->query_scalar( $sql );
	}
	
	public static function getVoucherAsOptions( $voucher_id ){
		
		$sql = "SELECT `vs`.`description`, `vs`.`id`
				FROM `voucher_series` vs
				WHERE `vs`.`id`	IN ( $voucher_id )"; 

		return self::$campaigns_database->query_hash( $sql, 'description', 'id' );
	}
	
	/**
	 * returns all the vouchers issue to the user till date
	 * 
	 * @param unknown_type $org_id
	 * @param unknown_type $user_id
	 * @param unknown_type $order
	 */
	public static function getAllIssuedVouchersByUser( $org_id, $user_id, $order = false ){
		
		$filter = ($order) ? "ORDER BY v.created_date DESC " : "";

		$sql = "
			SELECT v.voucher_code as coupon_code, vs.description, v.created_date, oe.code as store, v.amount, 
				   v.bill_number, vs.id as coupon_series_id
			FROM voucher v 
			JOIN voucher_series vs ON vs.id = v.voucher_series_id
			JOIN masters.org_entities oe ON oe.id = v.created_by AND oe.org_id = v.org_id
			WHERE v.org_id = '$org_id' AND v.issued_to = '$user_id'
			$filter 
		";		
		
		return self::$campaigns_database->query( $sql );
	}

	/**
	 * moves voucher issual from old to new user
	 * 
	 * @param unknown_type $org_id
	 * @param unknown_type $from_user_id
	 * @param unknown_type $to_user_id
	 */
	public static function moveVoucherIssual( $org_id, $from_user_id, $to_user_id ){
		
		$sql = "
			UPDATE `voucher`
				SET `issued_to` = '$to_user_id',
					`current_user` = '$to_user_id'
			WHERE org_id = $org_id AND (`issued_to` = '$from_user_id' OR `current_user` = '$from_user_id')
		";
		
		return self::$campaigns_database->update( $sql );
	}
	
	/**
	 * moves voucher redemption from old to new user
	 * 
	 * @param unknown_type $org_id
	 * @param unknown_type $from_user_id
	 * @param unknown_type $to_user_id
	 */
	public static function moveVoucherRedemption( $org_id, $from_user_id, $to_user_id ){
		
		$sql = "
			UPDATE `voucher_redemptions` vr, voucher v
				SET `used_by` = '$to_user_id'
			WHERE v.org_id = $org_id AND vr.used_by = '$from_user_id'
					v.voucher_id = vr.voucher_id
		";
		
		return self::$campaigns_database->update( $sql );
	}
	
	/**
	 * 
	 * @param unknown_type $org_id
	 * @param unknown_type $series_id
	 * @param unknown_type $user_ids
	 */
	public static function getAllVouchersInSeriesHash( $org_id, $series_id, $user_ids ){

		$sql = "
				SELECT issued_to, voucher_code 
				FROM voucher 
				WHERE org_id = $org_id 
				AND voucher_series_id = '$series_id'
				AND issued_to IN (".Util::joinForSql($user_ids).")
				ORDER BY voucher_id ASC ";
		
		return self::$campaigns_database->query_hash( $sql, 'issued_to', 'voucher_code' );
		
	}
	
	/**
	 * returns the list of voucher code already issued from the 
	 * supplied list
	 *  
	 * @param unknown_type $codes
	 * @param unknown_type $ORG_ID
	 */
	public static function &getIssuedVoucherCodeFromCodes( &$codes, $org_id ){
		
		$sql = "
				SELECT voucher_code 
				FROM voucher 
				WHERE org_id = '$org_id' AND voucher_code IN (".Util::joinForSql($codes).")";

		return self::$campaigns_database->query_firstcolumn( $sql );
	}
	
	/**
	 * 
	 * @param unknown_type $bulk_sql
	 */
	public function addVoucherInBulk( &$bulk_sql ){
		
		$sql =  "

			INSERT INTO voucher 
			( 
				`voucher_code`,
				`org_id`,
				`created_date`,
				`issued_to`,
				`current_user`,
				`voucher_series_id`,
				`created_by`,
				`test`,
				`amount`,
				`bill_number`,
				`max_allowed_redemptions`,
				`group_id`,
				`issued_at_counter_id`,
				`rule_map`,
				`pin_code`
			) 
			VALUES"; 
		
		$sql .= implode( $bulk_sql, ',' );
		
		return $this->database->insert( $sql, true );
	}
	
	public function isAbsolute(){
		
		 if($this->C_voucher_series_model_extension->getDiscountType() == 'ABS')
		 	return true;
		 else return false;
	}

	public function getCouponValue()
	{
		 return $this->C_voucher_series_model_extension->getDiscountValue();
	}
	
	public function getCouponSeries(){
		return $this->C_voucher_series_model_extension;
	}
	
	public function isRedeemed(){
		
		$count = $this->getRedeemedCount();
		if($count == 0){
			return false;
		}else{
			return true;
		}
	}	
	
	public function getRedemptionInfo(){
		
		$response = array();
		if(!$this->isRedeemed()){
			$response['redeemed'] = 'false';
			$response['redeemed_on'] = '';
			$response['redeemed_at'] = '';
			return $response;
		}
		
		$sql = "SELECT vr.used_date, oe.name  
				 FROM `voucher` AS `v` 
				 JOIN  `voucher_redemptions` AS `vr` ON ( `v`.`voucher_id` = `vr`.`voucher_id` AND `vr`.`org_id` = '$this->current_org_id' )
				 JOIN masters.org_entities oe ON (vr.used_at_store = oe.id AND vr.org_id = oe.org_id ) 
				 WHERE `v`.`voucher_id` = '$this->voucher_id'";
		
		$result = $this->database->query_firstrow($sql);
		
		return array('redeemed' => true, 'redeemed_on' => $result['used_date'], 'redeemed_at' => $result['name']);
		
	}
	
	
	
}

?>