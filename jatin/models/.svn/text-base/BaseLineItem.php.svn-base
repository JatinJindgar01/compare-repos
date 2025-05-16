<?php
include_once 'models/BaseModel.php';
include_once ("models/ILineItem.php");
include_once ("models/ICacheable.php");
include_once ("models/filters/LineitemLoadFilters.php");
include_once ("exceptions/ApiLineitemException.php");

/**
 * @author cj
 * 
 * The base class for all the transaction.  
 * All the other transactions line items including 
 * the normal, return, emi, not-interested etc should be extending the class
 *
 */
abstract class BaseLineItem extends BaseApiModel implements  ICacheable,ILineItem {
	
	protected $db_user;
	protected $logger; 
	protected $current_user_id;
	protected $current_org_id;
	protected $cache_key_prefix;
	protected $configMgr;
	
	//id of the line item
	protected $transaction_id;
	protected $lineitem_id;
	protected $rate;
	protected $qty;
	protected $gross_amount;
	protected $discount;
	protected $transaction_amount;
	protected $item_code;
	protected $item_id;
	protected $description;
	protected $store_id;
	protected $last_updated_on;
	protected $validationErrorArr;
	
	protected static $iterableMembers;
	
	// parent transaction id, appropriate object need to be loaded as pre-line item
	protected $transaction;
	CONST CACHE_TTL = 18000; // 5 hour
	
	public function __construct($current_org_id, $lineitem_id = null)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		
		// setting the loggers
		$this->logger = &$logger;
		
		// current org
		$this->current_org_id = $current_org_id;
		
		// db connection 
		$this->db_user = new Dbase( 'users' );

		if($lineitem_id>0)
			$this->lineitem_id = $lineitem_id;
	}

	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"lineitem_id",
				"rate",
				"qty",
				"gross_amount",
				"discount",
				"transaction_amount",
				"description", 
				"item_code",
				"item_id",
				"transaction_id",
				"store_id",
				"last_updated_on"
		);
		
	}
	
	/**
	 * 
	 * @return 
	 */
	public function getLineitemId()
	{
	    return $this->lineitem_id;
	}

	/**
	 * 
	 * @param $user_id
	 */
	public function setLineitemId($lineitem_id)
	{
	    $this->lineitem_id = $lineitem_id;
	}

	/**
	 *
	 * @return
	 */
	public function getTransactionId()
	{
		return $this->transaction_id;
	}
	
	/**
	 *
	 * @param $user_id
	 */
	public function setTransactionId($transaction_id)
	{
		$this->transaction_id = $transaction_id;
	}
	
	
	/**
	 * 
	 * @return 
	 */
	public function getRate()
	{
	    return $this->rate;
	}

	/**
	 * 
	 * @param $rate
	 */
	public function setRate($rate)
	{
	    $this->rate = $rate;
	}

	/**
	 * 
	 * @return 
	 */
	public function getQty()
	{
	    return $this->qty;
	}

	/**
	 * 
	 * @param $qty
	 */
	public function setQty($qty)
	{
	    $this->qty = $qty;
	}

	/**
	 * 
	 * @return 
	 */
	public function getGrossAmount()
	{
	    return $this->gross_amount;
	}

	/**
	 * 
	 * @param $gross_amount
	 */
	public function setGrossAmount($gross_amount)
	{
	    $this->gross_amount = $gross_amount;
	}

	/**
	 * 
	 * @return 
	 */
	public function getDiscount()
	{
	    return $this->discount;
	}

	/**
	 * 
	 * @param $discount
	 */
	public function setDiscount($discount)
	{
	    $this->discount = $discount;
	}

	/**
	 * 
	 * @return 
	 */
	public function getTransactionAmount()
	{
	    return $this->transaction_amount;
	}

	/**
	 * 
	 * @param $transaction_amount
	 */
	public function setTransactionAmount($transaction_amount)
	{
	    $this->transaction_amount = $transaction_amount;
	}

	/**
	 * 
	 * @return 
	 */
	public function getItemCode()
	{
	    return $this->item_code;
	}

	/**
	 * 
	 * @param $item_code
	 */
	public function setItemCode($item_code)
	{
	    $this->item_code = $item_code;
	}

	/**
	 * 
	 * @return 
	 */
	public function getItemId()
	{
	    return $this->item_id;
	}
	public function getDescription()
	{
	    return $this->description;
	}

	public function setDescription($description)
	{
	    $this->description = $description;
	}

	public function getStoreId()
	{
		return $this->store_id;
	}
	
	public function getLastUpdatedOn()
	{
		return $this->last_updated_on;
	}
	
	public function getValidationErrorArr()
	{
		return $this->validationErrorArr;
	}
	/*  
	 *  The function saves the data in to DB or any other data source for a line items,
	 *  All the values need to be set using the corresponding setter methods.
	 *  This can update the existing record if the id is already set. 
	 *  The list of updatable fields need to be checked well in advance; by default, updates should be avoided
	 */ 
	public function save()
	{
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
	}

	/*
	 * Validate the data before saving to DB/ for insert and update
	 */
	public function validate()
	{
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
	}
	
	/*
	 *  The function loads the data linked to the object, based on the id set using setter method 
	 */
	public static function loadById($org_id, $id)
	{
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
	}

	/* 
	 * Load all the data into object based on the filters being passed. 
	 * It should optionally decide whether entire dependency tree is required or not
	 */
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
	}

	public function validateTransactionAmount()
	{
		include_once 'apiHelper/DataValueValidator.php';
		$this->initiateDependentObject('configMgr');
	
		$ret = true;
		/************* amount validation  *************/
		// check for negative amount
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_NEGATIVE_TXN_AMOUNT"))
		{
			$this->logger->debug("check if the amt is negative");
			if(!DataValueValidator::validateZeroPositive($this->transaction_amount))
			{
				$validationError[] = "Amount is negative";
				$ret = false;
			}
	
		}
	
		// check for negative amount
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_ZERO_TXN_AMOUNT"))
		{
			$this->logger->debug("check if the amt is zero");
			if(!DataValueValidator::validateNonZero($this->transaction_amount))
			{
				$validationError[] = "Amount is zero";
				$ret = false;
			}
	
		}
		return $ret;
	}
	
	
	public function validateGrossAmount()
	{
		include_once 'apiHelper/DataValueValidator.php';
		$this->initiateDependentObject('configMgr');
		
		$ret = true;
		/************* gross amount validation  *************/
		// check for negative amount
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_NEGATIVE_GROSS_AMOUNT"))
		{
			$this->logger->debug("check if the gross amt is negative");
			if(!DataValueValidator::validateZeroPositive($this->gross_amount))
			{
				$validationError[] = "Gross amount is negative";
				$ret = false;
			}
		
		}
		
		// check for negative amount
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_ZERO_GROSS_AMOUNT"))
		{
			$this->logger->debug("check if the gross amt is zero");
			if(!DataValueValidator::validateNonZero($this->transaction_amount))
			{
				$validationError[] = "Gross amount is zero";
				$ret = false;
			}
		
		}
		return $ret;
		
	}
	
	public function validateDiscount()
	{
		/************* discount validation  *************/
		include_once 'apiHelper/DataValueValidator.php';
		$this->initiateDependentObject('configMgr');
	
		$ret = true;
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_NEGATIVE_DISCOUNT"))
		{
			$this->logger->debug("check if the Discount is negative");
			if(!DataValueValidator::validateZeroPositive($this->discount))
			{
				$validationError[] = "Discount is negative";
				$ret = false;
			}
	
		}
	
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_ZERO_DISCOUNT"))
		{
			$this->logger->debug("check if the qty is zero");
			if(!DataValueValidator::validateNonZero($this->discount))
			{
				$validationError[] = "Discount is zero";
				$ret = false;
			}
	
		}
		return $ret;
	}
		
	protected function validateQty()
	{
		/************* qty validation  *************/
		include_once 'apiHelper/DataValueValidator.php';
		$ret = true;
		$this->initiateDependentObject('configMgr');
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_NEGATIVE_QTY"))
		{
			$this->logger->debug("check if the qty is negative");
			if(!DataValueValidator::validateZeroPositive($this->qty))
			{
				$validationError[] = "Quantity is negative";
				$ret = false;
			}
	
		}
	
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_ZERO_QTY"))
		{
			$this->logger->debug("check if the qty is zero");
			if(!DataValueValidator::validateNonZero($this->qty))
			{
				$validationError[] = "Quantity is zero";
				$ret = false;
			}
		}
	
		return $ret;
	}

	protected function validateRate()
	{
		/************* rate validation  *************/
		include_once 'apiHelper/DataValueValidator.php';
		$ret = true;
		$this->initiateDependentObject('configMgr');
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_NEGATIVE_RATE"))
		{
			$this->logger->debug("check if the rate is negative");
			if(!DataValueValidator::validateZeroPositive($this->rate))
			{
				$validationError[] = "Rate is negative";
				$ret = false;
			}
	
		}
	
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_LI_ALLOW_ZERO_RATE"))
		{
			$this->logger->debug("check if the rate is zero");
			if(!DataValueValidator::validateNonZero($this->rate))
			{
				$validationError[] = "Rate is zero";
				$ret = false;
			}
		}
	
		return $ret;
	}
	
	protected function validateRateQtyGross()
	{
		/************* rate * qty = gross *************/
		include_once 'apiHelper/DataValueValidator.php';
		$ret = true;
		$this->initiateDependentObject('configMgr');

		// check for negative qty
		if($this->configMgr->getKey("API_VALIDATION_LI_RATE_QTY_GROSS"))
		{
			$this->logger->debug("check if the rate*qty = value");
			if(!DataValueValidator::validateMultiplication(array($this->qty, $this->rate), $this->gross_amount))
			{
				$validationError[] = "Rate*quantity not equal to gross amount";
				$ret = false;
			}
		}
	
		return $ret;
	}
	
	protected function validateGrossDiscountAmount()
	{
		/************* gross - discount = amount  *************/
		include_once 'apiHelper/DataValueValidator.php';
		$ret = true;
		$this->initiateDependentObject('configMgr');
		
		// check for negative qty
		if($this->configMgr->getKey("API_VALIDATION_LI_GROSS_DISCOUNT_AMOUNT"))
		{
			$this->logger->debug("check if the rate*qty = value");
			if(!DataValueValidator::validateDifference(array($this->gross_amount, $this->discount), $this->transaction_amount))
			{
				$validationError[] = "Gross amount - discount != value";
				$ret = false;
			}
		}
		
		return $ret;
	}
	/**
	 * initiate the respective class on demand
	 * @param $memberName - the object need to be initialized
	 */
	protected function initiateDependentObject($memberName)
	{
		$this->logger->debug("Lazy loading the $memberName object");
		switch(strtolower($memberName))
		{
			case 'configmgr':
				if(!$this->configMgr instanceof ConfigManager)
				{
					$this->configMgr = new ConfigManager($this->current_org_id);
					$this->logger->debug("Loaded config manager");
				}
				break;
		}
	}
	
}