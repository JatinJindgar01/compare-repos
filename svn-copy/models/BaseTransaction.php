<?php
include_once 'models/BaseModel.php';
include_once ("models/ITransaction.php");
include_once ("models/ICacheable.php");
include_once ("models/filters/TransactionLoadFilters.php");
include_once ("exceptions/ApiTransactionException.php");

/**
 * @author cj
 *
 * The base class for all the transaction.
 * All the other transactions including the normal, return, emi, not-interested etc should be extending the class
 *
*/
abstract class BaseTransaction extends BaseApiModel implements  ITransaction, ICacheable  {

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;
	protected $cache_key_prefix;
	CONST CACHE_TTL = 18000; // 5 hour
	

	/*
	 * Properties of a transaction
	*/
	protected $transaction_id;
	protected $gross_amount;
	protected $discount = 0;
	protected $transaction_amount; // after discount
	protected $transaction_number;
	protected $transaction_date;
	protected $store_id;

	protected $validationErrorArr;
	protected $configMgr;
	protected static $iterableMembers;

	// object linked with repective line item
	protected $lineitemsLinked;
    protected $outlier_status;


	public function __construct($current_org_id, $transction_id = null)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		$this->logger = $logger;

		// setting the loggers
		$this->logger = &$logger;

		// current org
		$this->current_org_id = $current_org_id;

		// db connection
		$this->db_user = new Dbase( 'users' );

		if($transction_id> 0)
			$this->transaction_id = $transction_id;
			
		$classname = get_called_class();
		$classname::setIterableMembers();

	}

	public static function setIterableMembers()
	{
		$classname = get_called_class();
		$classname::$iterableMembers = array(
				"transaction_id",
				"gross_amount",
				"discount",
				"transaction_amount",
				"transaction_number",
				"transaction_date",
				"store_id",
                "outlier_status"
		);

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

    public function setOutlierStatus($outlier_status)
    {
        $this->outlier_status = $outlier_status;
    }

	/**
	 *
	 * @return
	 */
	public function getTransactionNumber()
	{
		return $this->transaction_number;
	}

	/**
	 *
	 * @param $transaction_number
	 */
	public function setTransactionNumber($transaction_number)
	{
		$this->transaction_number = $transaction_number;
	}

	/**
	 *
	 * @return
	 */
	public function getTransactionDate()
	{
		return $this->transaction_date;
	}

	/**
	 *
	 * @param $transaction_date
	 */
	public function setTransactionDate($transaction_date)
	{
		$this->transaction_date = $transaction_date;
	}

	/**
	 *
	 * @return
	 */
	public function getStoreId()
	{
		return $this->store_id;
	}

	public function getValidationErrorArr()
	{
		return $this->validationErrorArr;
	}

    public function getOutlierStatus()
    {
        return $this->outlier_status;
    }

	/*
	 *  The function saves the data in to DB or any other data source for a txn,
	*  All the values need to be set using the corresponding setter methods.
	*  This can update the existing record if the id is already set.
	*  The list of updatable fields need to be checked well in advance
	*/
	public function save(){
		throw new ApiTransactionException(ApiTransactionException::FUNCTION_NOT_IMPLEMENTED);
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($org_id, $transaction_id){
		throw new ApiTransactionException(ApiTransactionException::FUNCTION_NOT_IMPLEMENTED);
	}

	/*
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0){
		throw new ApiTransactionException(ApiTransactionException::FUNCTION_NOT_IMPLEMENTED);
	}

	/*
	 * Loads all the lineitems of the transaction to object.
	* The setter method has to be used prior to set the transaction id
	*/
	public function loadLineItems(){
		throw new ApiTransactionException(ApiTransactionException::FUNCTION_NOT_IMPLEMENTED);
	}

	public function validateTransactionAmount()
	{
		include_once 'apiHelper/DataValueValidator.php';
		$this->initiateDependentObject('configMgr');
	
		$ret = true;
		/************* amount validation  *************/
		// check for negative amount
		if(!$this->configMgr->getKey("API_VALIDATION_TXN_ALLOW_NEGATIVE_TXN_AMOUNT"))
		{
			$this->logger->debug("check if the amt is negative");
			if(!DataValueValidator::validateZeroPositive($this->transaction_amount))
			{
				$validationError[] = "Amount is negative";
				$ret = false;
			}
	
		}
	
		// check for negative amount
		if(!$this->configMgr->getKey("API_VALIDATION_TXN_ALLOW_ZERO_TXN_AMOUNT"))
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
		if(!$this->configMgr->getKey("API_VALIDATION_TXN_ALLOW_NEGATIVE_GROSS_AMOUNT"))
		{
			$this->logger->debug("check if the gross amt is negative");
			if(!DataValueValidator::validateZeroPositive($this->gross_amount))
			{
				$validationError[] = "Gross amount is negative";
				$ret = false;
			}
	
		}
	
		// check for negative amount
		if(!$this->configMgr->getKey("API_VALIDATION_TXN_ALLOW_ZERO_GROSS_AMOUNT"))
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
		if(!$this->configMgr->getKey("API_VALIDATION_TXN_ALLOW_NEGATIVE_DISCOUNT"))
		{
			$this->logger->debug("check if the Discount is negative");
			if(!DataValueValidator::validateZeroPositive($this->discount))
			{
				$validationError[] = "Discount is negative";
				$ret = false;
			}
	
		}
	
		// check for negative qty
		if(!$this->configMgr->getKey("API_VALIDATION_TXN_ALLOW_ZERO_DISCOUNT"))
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
	
	protected function validateGrossDiscountAmount()
	{
		/************* gross - discount = amount  *************/
		include_once 'apiHelper/DataValueValidator.php';
		$ret = true;
		$this->initiateDependentObject('configMgr');
	
		// check for negative qty
		if($this->configMgr->getKey("API_VALIDATION_TXN_GROSS_DISCOUNT_AMOUNT"))
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
	
	protected function validateTransactionDate()
	{
		include_once 'apiHelper/DataValueValidator.php';
		$ret = true;
		$this->initiateDependentObject('configMgr');
		
		$minTxnDate = $this->configMgr->getKey("CONF_MIN_BILLING_DATE");
		$validateWithMinTxnDate = $this->configMgr->getKey("API_VALIDATION_TXN_CHECK_MIN_DATE");
		
		$this->logger->debug("The validation on min date is $minTxnDate and check = ".($validateWithMinTxnDate ?  1 : 0));
		if($validateWithMinTxnDate && $minTxnDate 
				&& !DataValueValidator::validateDateTimeBefore($this->transaction_date, $minTxnDate))
		{
			$validationError[] = "Transaction date less than $minTxnDate";
			$ret = false;
		}
		
		// check for negative qty
		if($this->configMgr->getKey("API_VALIDATION_TXN_DATE_BEFORE_DOJ"))
		{
			$this->logger->debug("check if transaction_date is before customer doj");
			if(!DataValueValidator::validateDateTimeBefore($this->transaction_date, $this->cus))
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

    public function getLineItems()
    {
        return $this->lineitemsLinked;
    }

    public function setLineItems($lineItems)
    {
        $this->lineitemsLinked = $lineItems;
    }
	
}