<?php
include_once ("models/filters/CouponSeriesLoadFilters.php");
include_once ("exceptions/ApiCouponException.php");
include_once ("models/ICacheable.php");

class CouponSeries extends BaseApiModel implements ICacheable
{
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

	protected $campaign_db;
	protected $currentuser;
	protected $logger;
	protected $current_org_id;
	protected $current_user_id;
	
	protected static $iterableMembers = array();
	
	CONST CACHE_TTL = 3600; // 1 hour
	
	CONST CACHE_KEY_PREFIX_ID = "CACHE_COUPON_SERIES_ID#";
	
	public function __construct($current_org_id)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->logger = $logger;
		
		$this->current_user_id = $currentuser->user_id;
		$this->current_org_id = $current_org_id;
		
		$this->db_campaigns = new Dbase( 'campaigns' );
		
		$className = get_called_class();
		$className::setIterableMembers();
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"org_id",
				"description",
				"tag",
				"series_type",
				"client_handling_type",
				"discount_code",
				"valid_till_date",
				"valid_days_from_create",
				"max_create",
				"max_redeem",
				"transferrable",
				"any_user",
				"same_user_multiple_redeem",
				"allow_referral_existing_users",
				"multiple_use",
				"is_validation_required",
				"created_by",
				"num_issued",
				"num_redeemed",
				"created",
				"last_used",
				"series_code",
				"sms_template",
				"disable_sms",
				"info",
				"allow_multiple_vouchers_per_user",
				"do_not_resend_existing_voucher",
				"mutual_exclusive_series_ids",
				"store_ids_json",
				"dvs_enabled",
				"dvs_expiry_date",
				"priority",
				"terms_and_condition",
				"signal_redemption_event",
				"sync_to_client",
				"short_sms_template",
				"max_vouchers_per_user",
				"min_days_between_vouchers",
				"max_referrals_per_referee",
				"show_pin_code",
				"discount_on",
				"discount_type",
				"discount_value",
				"dvs_items",
				"redemption_range",
				"min_bill_amount",
				"max_bill_amount",
				"redeem_at_store",
				"campaign_id",
				"redemption_valid_from",
				"min_days_between_redemption",
				"max_redemptions_in_series_per_user"
			);
	}
	
	
	

	public function getId()
	{
	    return $this->id;
	}

	public function setId($id)
	{
	    $this->id = $id;
	}

	public function getOrgId()
	{
	    return $this->org_id;
	}

	public function setOrgId($org_id)
	{
	    $this->org_id = $org_id;
	}

	public function getDescription()
	{
	    return $this->description;
	}

	public function setDescription($description)
	{
	    $this->description = $description;
	}

	public function getTag()
	{
	    return $this->tag;
	}

	public function setTag($tag)
	{
	    $this->tag = $tag;
	}

	public function getSeriesType()
	{
	    return $this->series_type;
	}

	public function setSeriesType($series_type)
	{
	    $this->series_type = $series_type;
	}

	public function getClientHandlingType()
	{
	    return $this->client_handling_type;
	}

	public function setClientHandlingType($client_handling_type)
	{
	    $this->client_handling_type = $client_handling_type;
	}

	public function getDiscountCode()
	{
	    return $this->discount_code;
	}

	public function setDiscountCode($discount_code)
	{
	    $this->discount_code = $discount_code;
	}

	public function getValidTillDate()
	{
	    return $this->valid_till_date;
	}

	public function setValidTillDate($valid_till_date)
	{
	    $this->valid_till_date = $valid_till_date;
	}

	public function getValidDaysFromCreate()
	{
	    return $this->valid_days_from_create;
	}

	public function setValidDaysFromCreate($valid_days_from_create)
	{
	    $this->valid_days_from_create = $valid_days_from_create;
	}

	public function getMaxCreate()
	{
	    return $this->max_create;
	}

	public function setMaxCreate($max_create)
	{
	    $this->max_create = $max_create;
	}

	public function getMaxRedeem()
	{
	    return $this->max_redeem;
	}

	public function setMaxRedeem($max_redeem)
	{
	    $this->max_redeem = $max_redeem;
	}

	public function getTransferrable()
	{
	    return $this->transferrable;
	}

	public function setTransferrable($transferrable)
	{
	    $this->transferrable = $transferrable;
	}

	public function getAnyUser()
	{
	    return $this->any_user;
	}

	public function setAnyUser($any_user)
	{
	    $this->any_user = $any_user;
	}

	public function getSameUserMultipleRedeem()
	{
	    return $this->same_user_multiple_redeem;
	}

	public function setSameUserMultipleRedeem($same_user_multiple_redeem)
	{
	    $this->same_user_multiple_redeem = $same_user_multiple_redeem;
	}

	public function getAllowReferralExistingUsers()
	{
	    return $this->allow_referral_existing_users;
	}

	public function setAllowReferralExistingUsers($allow_referral_existing_users)
	{
	    $this->allow_referral_existing_users = $allow_referral_existing_users;
	}

	public function getMultipleUse()
	{
	    return $this->multiple_use;
	}

	public function setMultipleUse($multiple_use)
	{
	    $this->multiple_use = $multiple_use;
	}

	public function getIsValidationRequired()
	{
	    return $this->is_validation_required;
	}

	public function setIsValidationRequired($is_validation_required)
	{
	    $this->is_validation_required = $is_validation_required;
	}

	public function getCreatedBy()
	{
	    return $this->created_by;
	}

	public function setCreatedBy($created_by)
	{
	    $this->created_by = $created_by;
	}

	public function getNumIssued()
	{
	    return $this->num_issued;
	}

	public function setNumIssued($num_issued)
	{
	    $this->num_issued = $num_issued;
	}

	public function getNumRedeemed()
	{
	    return $this->num_redeemed;
	}

	public function setNumRedeemed($num_redeemed)
	{
	    $this->num_redeemed = $num_redeemed;
	}

	public function getCreated()
	{
	    return $this->created;
	}

	public function setCreated($created)
	{
	    $this->created = $created;
	}

	public function getLastUsed()
	{
	    return $this->last_used;
	}

	public function setLastUsed($last_used)
	{
	    $this->last_used = $last_used;
	}

	public function getSeriesCode()
	{
	    return $this->series_code;
	}

	public function setSeriesCode($series_code)
	{
	    $this->series_code = $series_code;
	}

	public function getSmsTemplate()
	{
	    return $this->sms_template;
	}

	public function setSmsTemplate($sms_template)
	{
	    $this->sms_template = $sms_template;
	}

	public function getDisableSms()
	{
	    return $this->disable_sms;
	}

	public function setDisableSms($disable_sms)
	{
	    $this->disable_sms = $disable_sms;
	}

	public function getInfo()
	{
	    return $this->info;
	}

	public function setInfo($info)
	{
	    $this->info = $info;
	}

	public function getAllowMultipleVouchersPerUser()
	{
	    return $this->allow_multiple_vouchers_per_user;
	}

	public function setAllowMultipleVouchersPerUser($allow_multiple_vouchers_per_user)
	{
	    $this->allow_multiple_vouchers_per_user = $allow_multiple_vouchers_per_user;
	}

	public function getDoNotResendExistingVoucher()
	{
	    return $this->do_not_resend_existing_voucher;
	}

	public function setDoNotResendExistingVoucher($do_not_resend_existing_voucher)
	{
	    $this->do_not_resend_existing_voucher = $do_not_resend_existing_voucher;
	}

	public function getMutualExclusiveSeriesIds()
	{
	    return $this->mutual_exclusive_series_ids;
	}

	public function setMutualExclusiveSeriesIds($mutual_exclusive_series_ids)
	{
	    $this->mutual_exclusive_series_ids = $mutual_exclusive_series_ids;
	}

	public function getStoreIdsJson()
	{
	    return $this->store_ids_json;
	}

	public function setStoreIdsJson($store_ids_json)
	{
	    $this->store_ids_json = $store_ids_json;
	}

	public function getDvsEnabled()
	{
	    return $this->dvs_enabled;
	}

	public function setDvsEnabled($dvs_enabled)
	{
	    $this->dvs_enabled = $dvs_enabled;
	}

	public function getDvsExpiryDate()
	{
	    return $this->dvs_expiry_date;
	}

	public function setDvsExpiryDate($dvs_expiry_date)
	{
	    $this->dvs_expiry_date = $dvs_expiry_date;
	}

	public function getPriority()
	{
	    return $this->priority;
	}

	public function setPriority($priority)
	{
	    $this->priority = $priority;
	}

	public function getTermsAndCondition()
	{
	    return $this->terms_and_condition;
	}

	public function setTermsAndCondition($terms_and_condition)
	{
	    $this->terms_and_condition = $terms_and_condition;
	}

	public function getSignalRedemptionEvent()
	{
	    return $this->signal_redemption_event;
	}

	public function setSignalRedemptionEvent($signal_redemption_event)
	{
	    $this->signal_redemption_event = $signal_redemption_event;
	}

	public function getSyncToClient()
	{
	    return $this->sync_to_client;
	}

	public function setSyncToClient($sync_to_client)
	{
	    $this->sync_to_client = $sync_to_client;
	}

	public function getShortSmsTemplate()
	{
	    return $this->short_sms_template;
	}

	public function setShortSmsTemplate($short_sms_template)
	{
	    $this->short_sms_template = $short_sms_template;
	}

	public function getMaxVouchersPerUser()
	{
	    return $this->max_vouchers_per_user;
	}

	public function setMaxVouchersPerUser($max_vouchers_per_user)
	{
	    $this->max_vouchers_per_user = $max_vouchers_per_user;
	}

	public function getMinDaysBetweenVouchers()
	{
	    return $this->min_days_between_vouchers;
	}

	public function setMinDaysBetweenVouchers($min_days_between_vouchers)
	{
	    $this->min_days_between_vouchers = $min_days_between_vouchers;
	}

	public function getMaxReferralsPerReferee()
	{
	    return $this->max_referrals_per_referee;
	}

	public function setMaxReferralsPerReferee($max_referrals_per_referee)
	{
	    $this->max_referrals_per_referee = $max_referrals_per_referee;
	}

	public function getShowPinCode()
	{
	    return $this->show_pin_code;
	}

	public function setShowPinCode($show_pin_code)
	{
	    $this->show_pin_code = $show_pin_code;
	}

	public function getDiscountOn()
	{
	    return $this->discount_on;
	}

	public function setDiscountOn($discount_on)
	{
	    $this->discount_on = $discount_on;
	}

	public function getDiscountType()
	{
	    return $this->discount_type;
	}

	public function setDiscountType($discount_type)
	{
	    $this->discount_type = $discount_type;
	}

	public function getDiscountValue()
	{
	    return $this->discount_value;
	}

	public function setDiscountValue($discount_value)
	{
	    $this->discount_value = $discount_value;
	}

	public function getDvsItems()
	{
	    return $this->dvs_items;
	}

	public function setDvsItems($dvs_items)
	{
	    $this->dvs_items = $dvs_items;
	}

	public function getRedemptionRange()
	{
	    return $this->redemption_range;
	}

	public function setRedemptionRange($redemption_range)
	{
	    $this->redemption_range = $redemption_range;
	}

	public function getMinBillAmount()
	{
	    return $this->min_bill_amount;
	}

	public function setMinBillAmount($min_bill_amount)
	{
	    $this->min_bill_amount = $min_bill_amount;
	}

	public function getMaxBillAmount()
	{
	    return $this->max_bill_amount;
	}

	public function setMaxBillAmount($max_bill_amount)
	{
	    $this->max_bill_amount = $max_bill_amount;
	}

	public function getRedeemAtStore()
	{
	    return $this->redeem_at_store;
	}

	public function setRedeemAtStore($redeem_at_store)
	{
	    $this->redeem_at_store = $redeem_at_store;
	}

	public function getCampaignId()
	{
	    return $this->campaign_id;
	}

	public function setCampaignId($campaign_id)
	{
	    $this->campaign_id = $campaign_id;
	}

	public function getRedemptionValidFrom()
	{
	    return $this->redemption_valid_from;
	}

	public function setRedemptionValidFrom($redemption_valid_from)
	{
	    $this->redemption_valid_from = $redemption_valid_from;
	}

	public function getMinDaysBetweenRedemption()
	{
	    return $this->min_days_between_redemption;
	}

	public function setMinDaysBetweenRedemption($min_days_between_redemption)
	{
	    $this->min_days_between_redemption = $min_days_between_redemption;
	}

	public function getMaxRedemptionsInSeriesPerUser()
	{
	    return $this->max_redemptions_in_series_per_user;
	}

	public function setMaxRedemptionsInSeriesPerUser($max_redemptions_in_series_per_user)
	{
	    $this->max_redemptions_in_series_per_user = $max_redemptions_in_series_per_user;
	}
	

	public static function loadById($org_id, $id)
	{
		$filters = new CouponSeriesLoadFilters();
		$filters->id = $id;
		$objs = self::loadAll($org_id, $filters, 1);
		$obj = count($objs) > 0 ? $objs[0] : NULL;
		
		return $obj;
	}
	
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof CouponSeriesLoadFilters))
		{
			throw new ApiCouponException(ApiCouponException::FILTER_NO_SERIES_ID_PASSED);
		}
		
		if($filters->id)
		{
			$logger->debug("trying to load From memcache");
			$obj = self::loadFromCache($org_id, CouponSeries::CACHE_KEY_PREFIX_ID.$org_id."##".$filters->id);
			if($obj)
			{
				$logger->debug("found in memcache");
				return array($obj);
			}
		}
		
		$filter_sql = array();
		
		$sql = "SELECT * FROM voucher_series AS vs
					WHERE vs.org_id = $org_id ";
		
		if($filters->id)
			$filter_sql[] = " vs.id = $filters->id";
		
		//TODO for now using AND condition
		$sql = $filter_sql ? ($sql . "AND ( ".implode(" AND ", $filter_sql) . " ) ") : $sql;
		
		$sql .= " ORDER BY vs.id desc ";
		
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;
		
		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;
		
		//print (str_replace("\t"," ", $sql))."\n\n";
		$sql = $sql . " LIMIT $offset, $limit";
		
		$db = new Dbase( 'campaigns' );
		$array = $db->query($sql);
		
		if($array)
		{
			$ret = array();
			foreach($array as $row)
			{
				$classname= get_called_class();
				$obj = $classname::fromArray($org_id, $row);
				$ret[] = $obj;
				if($obj->getId())
					$obj->saveToCache(CouponSeries::CACHE_KEY_PREFIX_ID.$org_id."##".$obj->getId(), $obj->toString());
			}
			return $ret;
		}
		
		throw new ApiCouponException(ApiCouponException::NO_COUPON_SERIES_FOUND);
		return false;
	}
}
?>
