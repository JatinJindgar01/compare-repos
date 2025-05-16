<?php
include_once "models/ICacheable.php";

define("REDEMPTION_TYPE_POINTS", "POINTS");
define("REDEMPTION_TYPE_COUPON", "COUPON");

abstract class BaseRedemption extends BaseApiModel implements ICacheable
{
	private $type;
	protected $logger;
	
	protected $current_org_id;
	protected $current_user_id;
	protected $currentuser;
	
	protected static $iterableMembers = array();
	
	CONST CACHE_TTL = 3600; // 1 hour
	CONST CACHE_KEY_PREFIX_ID = "CACHE_COUPON_SERIES_ID#";
	
	protected $redemption_date; //redemption time
	protected $redeemed_by;		//user_id
	protected $redeemed_at;		//TILL where redemption is done
	protected $transaction_number;
	protected $validation_code;
	protected $notes;			//description in case of coupon
	
	public function __construct($type, $org_id)
	{
		global $logger, $currentuser;
		
		$this->logger = $logger;
		$this->currentuser = $currentuser;
		$this->current_user_id = $currentuser->user_id;
		$this->current_org_id = $current_org_id;
		
		$className = get_called_class();
		$className::setIterableMembers();
	}
	
	public static function setIterableMembers()
	{
		BaseRedemption::$iterableMembers = array(
					"redemption_date",
					"redeemed_by",
					"redeemed_at",
					"transaction_number",
					"transaction_amount",
					"validation_code",
					"notes"
				);
	}
	
	//abstract function loadByUserId($org_id, $user_id);
	//abstract function loadAll($org_id, $filters = null, $limit=100, $offset = 0);
	
	public function getType()
	{
	    return $this->type;
	}

	public function setType($type)
	{
	    $this->type = $type;
	}

	public function getRedemption_date()
	{
	    return $this->redemption_date;
	}

	public function setRedemption_date($redemption_date)
	{
	    $this->redemption_date = $redemption_date;
	}

	public function getRedeemed_by()
	{
	    return $this->redeemed_by;
	}

	public function setRedeemed_by($redeemed_by)
	{
	    $this->redeemed_by = $redeemed_by;
	}

	public function getRedeemed_at()
	{
	    return $this->redeemed_at;
	}

	public function setRedeemed_at($redeemed_at)
	{
	    $this->redeemed_at = $redeemed_at;
	}

	public function getTransaction_number()
	{
	    return $this->transaction_number;
	}

	public function setTransaction_number($transaction_number)
	{
	    $this->transaction_number = $transaction_number;
	}

	public function getValidation_code()
	{
	    return $this->validation_code;
	}

	public function setValidation_code($validation_code)
	{
	    $this->validation_code = $validation_code;
	}

	public function getNotes()
	{
	    return $this->notes;
	}

	public function setNotes($notes)
	{
	    $this->notes = $notes;
	}
} 
?>
