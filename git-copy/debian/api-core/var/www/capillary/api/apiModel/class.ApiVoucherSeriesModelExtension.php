<?php 

include_once 'apiModel/class.ApiVoucherSeries.php';

/**
 * model extension for the voucher series model
 *
 * @author pv
 *
 */
class ApiVoucherSeriesModelExtension extends ApiVoucherSeriesModel{


	private $current_org_id;


	/**
	 * Construct for the voucher series model
	 */
	public function __construct(){

		global $currentorg;

		parent::ApiVoucherSeriesModel();

		$this->current_org_id = $currentorg->org_id;
	}

	/**
	 * is redeem limit still valid
	 */
	public function isRedemptionLimitValid(){

		$sql = "
		SELECT CASE WHEN `num_redeemed` < `max_redeem` THEN 1 ELSE 0 END
		FROM `voucher_series`
		WHERE id = $this->id
		";

		return $this->database->query_scalar( $sql, false );
	}

	/**
	 * Increase redemption count by one
	 */
	public function updateSeriesRedeemCount( ) {

		$sql = "
		UPDATE voucher_series
		SET num_redeemed = num_redeemed + 1
		WHERE id = $this->id
		";

		return $this->database->update( $sql );
	}

	/**
	 * Returns the voucher series configured for the organizations
	 *
	 * @param unknown_type $exclude_expired
	 */
	public function getVoucherSeriesByOrg( $exclude_expired = true ){

		if($exclude_expired)
			$expiry_filter = " AND DATE( valid_till_date ) > DATE( NOW() ) ";

		$sql = "

		SELECT `id`, `description`, `valid_till_date`
		FROM `$this->table`
		WHERE `org_id` = '$this->org_id' $expiry_filter
		ORDER BY `id` DESC
		LIMIT 100
		";

		return $this->database->query( $sql );
	}

	/**
	 * Return sthe count of the vouchers of the users
	 *
	 * @param unknown_type $user_id
	 */
	public function getNumberOfVouchersForUser( $user_id ){

		$sql = "
		SELECT COUNT(*)
		FROM voucher
		WHERE org_id = '$this->org_id' AND  voucher_series_id = '$this->id' AND issued_to = '$user_id'
		";

		return $this->database->query_scalar( $sql );
	}

	/**
	 * Returns the voucher last issued in the series for the customers
	 *
	 *
	 * @param unknown_type $user_id
	 */
	public function getLastIssuedVoucherForCustomer( $user_id ){

		$sql = "
		SELECT voucher_code
		FROM voucher
		WHERE org_id = '$this->current_org_id' AND voucher_series_id = '$this->id'
		AND issued_to = '$user_id'
		ORDER BY `voucher_id` DESC
		";

		return $this->database->query_scalar( $sql );
	}


	/**
	 *
	 * @param unknown_type $user_id
	 */
	public function getVoucherRedemptionId( $user_id ){

		$sql = "
		SELECT `used_date`,`used_at_store`
		FROM `voucher_redemptions`
		WHERE org_id = '$this->current_org_id' AND voucher_series_id = '$this->id'
		AND `used_by` = '$user_id'
		ORDER BY `voucher_id` DESC
		";

		return $this->database->query_firstrow( $sql );
	}
	
	/**
	 *
	 * @param unknown_type $user_id
	 */
	public function getVoucherRedemptionCountForUser( $user_id ){

		$sql = "
			SELECT COUNT( * ) AS redeemed_count
			FROM `voucher_redemptions`
			WHERE org_id = '$this->current_org_id' AND voucher_series_id = '$this->id'
			AND `used_by` = '$user_id'
		";

		return $this->database->query_scalar( $sql );
	}

	/**
	 *
	 * @param unknown_type $user_id
	 */
	public function getLastRedeemedDateForUser( $user_id ){

		$sql = "
			SELECT DATE( used_date ) AS last_used_on
			FROM `voucher_redemptions`
			WHERE org_id = '$this->current_org_id' AND voucher_series_id = '$this->id'
			AND `used_by` = '$user_id'
			ORDER BY id DESC
			LIMIT 1
		";

		return $this->database->query_scalar( $sql );
	}
	
	/**
	 * Difference between issual of the two vouchers
	 *
	 * @param unknown_type $user_id
	 */
	public function getDaysFromLastVoucherForUser( $user_id ){

		$sql = "
		SELECT IFNULL( MIN( DATEDIFF( NOW(), `created_date` ) ), -1 )
		FROM `voucher`
		WHERE org_id = '$this->current_org_id' AND  voucher_series_id = '$this->id' AND issued_to = '$user_id'";

		return $this->database->query_scalar( $sql );
	}

	/**
	 * get all the series
	 */
	public function getAll( ){

		$sql = "
		SELECT *
		FROM `$this->table`
		WHERE `org_id` = '$this->current_org_id'
		";

		return $this->database->query( $sql );
	}

	/**
	 *
	 * @param unknown_type $series_code
	 * @param unknown_type $id
	 */
	public function getCountBySeriesCode( $series_code, $id ){

		$sql = "
		SELECT COUNT( * )
		FROM `$this->table`
		WHERE `org_id` = '$this->current_org_id'
		AND `series_code` = '$series_code'
		AND `id` != $id
		";

		return $this->database->query_scalar( $sql );
	}

	/**
	 * returns Coupon Series as option with or without expiry check
	 * @param unknown_type $exclude_expired
	 */
	public function getCouponSeriesAsOptionsWithExpiryCheck( $exclude_expired = true ) {

		$expiry_filter = "";

		if( $exclude_expired )
			$expiry_filter = " AND DATE(valid_till_date) > DATE(NOW()) ";

		$sql = "
		SELECT *
		FROM `$this->table`
		WHERE `org_id` = '$this->current_org_id' $expiry_filter ORDER BY id DESC
		";

		return $this->database->query( $sql );

	}

	public function getCouponSeriesWithExpiryCheck( $expired ) {
		 
		$expiry_filter = "AND DATE(valid_till_date) > DATE(NOW())";

		if( $expired  )
		{
			$expiry_filter = " ";
		}

		$sql = "
		SELECT *
		FROM `$this->table`
		WHERE `org_id` = '$this->current_org_id' $expiry_filter ORDER BY id DESC
		";

		return $this->database->query( $sql );

	}



	public function getCouponSeriesInfoById($series_id){

		$safe_series_id = Util::mysqlEscapeString($series_id);
		$sql = "
		SELECT *
		FROM `$this->table`
		WHERE `org_id` = '$this->current_org_id' AND id IN ( $safe_series_id )
		";

		return $this->database->query( $sql );

	}

	/**
	 * returns the number of un used vouchers
	 */
	public function getNumberOfUnUsedVoucher(){

		$sql = "
		SELECT COUNT(*)
		FROM voucher
		WHERE org_id = $this->current_org_id
		AND voucher_series_id = $this->id
		AND issued_to = -1
		";

		return $this->database->query_scalar( $sql );
	}

	/**
	 * returns the 1st of lot of unused vouchers
	 */
	public function getFirstUnUsedVoucher(){

		$sql = "
		SELECT voucher_id
		FROM voucher
		WHERE org_id = $this->current_org_id
		AND voucher_series_id = $this->id
		AND issued_to = -1 LIMIT 0,1
		";

		return $this->database->query_scalar( $sql );
	}


	/**
	 * Given the voucher series ids get the count of  vouchers redeemed
	 * @param unknown_type $voucher_series_ids
	 */
	public function getTotalVouchersRedeemedInCampaign($voucher_series_ids)
	{
		$sql = "SELECT count(vr.id) as total_redeemed
		FROM `voucher_redemptions` vr,voucher_series vs
		WHERE vr.org_id=$org_id
		AND vr.`voucher_series_id` IN ($voucher_series_ids)
		AND vr.org_id=vs.org_id
		AND vr.voucher_series_id=vs.id";

		$result = $this->database->query_firstrow($sql);
		return $result['total_redeemed'];
	}

	/**
	 * Given the voucher series ids get the count of  vouchers issued
	 * @param unknown_type $voucher_series_ids
	 */
	public function getTotalVouchersIssuedInCampaign($voucher_series_ids)
	{
		$sql = "SELECT count(vr.id) as total_issued
		FROM `voucher` vr,voucher_series vs
		WHERE vr.org_id=$org_id
		AND vr.`voucher_series_id` IN ($voucher_series_ids)
		AND vr.org_id=vs.org_id
		AND vr.voucher_series_id=vs.id";
			
		$result = $this->database->query_firstrow($sql);
		return $result['total_issued'];
	}

	/**
	 * Given the voucher series id get the count of vouchers issued
	 * @param unknown_type $voucher_series_ids
	 */
	public function getRedeemedCountByVoucherSeries($org_id,$voucher_series_ids, $todayStart, $todayEnd,
			$weekStart, $weekEnd, $monthStart, $monthEnd, $is_series_level=false)
	{
		
		$todayEnd = $todayEnd. " 23:59:59";
		$weekEnd = $weekEnd. " 23:59:59";
		$monthEnd = $monthEnd. " 23:59:59";
		
		$sql = " SELECT IFNULL ( SUM(CASE WHEN vr.used_date >= '$todayStart' and vr.used_date <= '$todayEnd' THEN 1 ELSE 0 END), 0) AS redeemed_today,
		IFNULL ( SUM(CASE WHEN vr.used_date >= '$weekStart' and vr.used_date <= '$weekEnd' THEN 1 ELSE 0 END), 0) AS redeemed_last_week,
		IFNULL ( SUM(CASE WHEN vr.used_date >= '$monthStart' and vr.used_date <= '$monthEnd' THEN 1 ELSE 0 END), 0) AS redeemed_last_month,
		count(vs.id) as total_redeemed";
		if($is_series_level)
			$sql.=",vs.id as id,vs.description as description";
		$sql.=" FROM `voucher_redemptions` vr,voucher_series vs
		WHERE vr.org_id=$org_id
		AND vr.`voucher_series_id` IN ($voucher_series_ids)
		AND vr.org_id=vs.org_id
		AND vr.voucher_series_id=vs.id";
		if($is_series_level)
			$sql.=" GROUP BY vs.id ORDER BY vs.id";

		if($is_series_level)
		{
			$result= $this->database->query($sql);
			$voucher_series_result = array();
			foreach($result as $v)
				$voucher_series_result[$v['id']]=$v;
			return $voucher_series_result;
		}
		else
			return $this->database->query_firstrow($sql);
	}

	/**
	 * Given the voucher series id get the count of vouchers redeeemed
	 * @param unknown_type $voucher_series_ids
	 */
	public function getIssuedCountByVoucherSeries($org_id,$voucher_series_ids, $todayStart, $todayEnd,
			$weekStart, $weekEnd, $monthStart, $monthEnd, $is_series_level=false)
	{
		
		$todayEnd = $todayEnd. " 23:59:59";
		$weekEnd = $weekEnd. " 23:59:59";
		$monthEnd = $monthEnd. " 23:59:59";
		
		$sql = " SELECT IFNULL ( SUM(CASE WHEN vr.created_date >= '$todayStart' and vr.created_date <= '$todayEnd' THEN 1 ELSE 0 END), 0) AS issued_today,
		IFNULL ( SUM(CASE WHEN vr.created_date >= '$weekStart' and vr.created_date <= '$weekEnd' THEN 1 ELSE 0 END), 0) AS issued_last_week,
		IFNULL ( SUM(CASE WHEN vr.created_date >= '$monthStart' and vr.created_date <= '$monthEnd' THEN 1 ELSE 0 END), 0) AS issued_last_month,
		count(vs.id) as total_issued";
		if($is_series_level)
			$sql.=",vs.id,vs.description";
		$sql.=" FROM `voucher` vr,voucher_series vs
		WHERE vr.org_id=$org_id
		AND vr.`voucher_series_id` IN ($voucher_series_ids)
		AND vr.org_id=vs.org_id
		AND vr.voucher_series_id=vs.id";
		if($is_series_level)
			$sql.=" GROUP BY vs.id ORDER BY vs.id";

		if($is_series_level)
		{
			$result= $this->database->query($sql);
			$voucher_series_result = array();
			foreach($result as $v)
				$voucher_series_result[$v['id']]=$v;
			return $voucher_series_result;
		}
		else
			return $this->database->query_firstrow($sql);
	}
}
?>
