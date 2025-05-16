<?php
include_once 'exceptions/ApiCurrencyRatioException.php';
include_once 'models/BaseModel.php';
include_once 'models/filters/CurrencyRatioFilters.php';

/**
 * @author class
 *
 * The defines all the supported currencies records in the DB. The table is more of a meta data
 * The linked table is user_management.inventory_item_attributes
 */
class SupportedCurrency extends BaseApiModel{

	protected $db_user;
	protected $logger;
	#protected $current_user_id;
	#protected $current_org_id;

	protected $supported_currency_id;
	protected $name;
	protected $symbol;
	protected $iso_code;
	protected $iso_code_numeric;
	
	protected $validationErrorArr;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'CUR_SUPP_ID';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($supported_currency_id = null)
	{
		global $logger;
		#$this->currentuser = &$currentuser;
		#$this->current_user_id = $currentuser->user_id;
		#$this->current_org_id = $current_org_id;

		$this->logger = $logger;

		// db connection
		$this->db = new Dbase( 'masters' );
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"supported_currency_id",
				"name",
				"symbol",
				"iso_code",
				"iso_code_numeric"
				);
	}
	public function getSupportedCurrencyId()
	{
	    return $this->supported_currency_id;
	}

	public function setSupportedCurrencyId($supported_currency_id)
	{
	    $this->supported_currency_id = $supported_currency_id;
	}

	public function getName()
	{
	    return $this->name;
	}

	public function setName($name)
	{
	    $this->name = $name;
	}

	public function getIsoCode()
	{
		return $this->iso_code;
	}
	
	public function setIsoCode($iso_code)
	{
		$this->iso_code = $iso_code;
	}
	
	public function getIsoCodeNumeric()
	{
		return $this->iso_code_numeric;
	}
	
	public function setIsoCodeNumeric($iso_code_numeric)
	{
		$this->iso_code_numeric = $iso_code_numeric;
	}
	
	public function getSymbol()
	{
		return $this->symbol;
	}
	
	public function setSymbol($symbol)
	{
		$this->symbol = $symbol;
	}
	
	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($supported_currency_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on currency id");

		if(!$supported_currency_id)
		{
			throw new ApiInventoryException(ApiCurrencyException::FILTER_CURRENCY_NOT_PASSED);
		}
		
		$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX, $supported_currency_id, null);
		$obj = self::loadFromCache(null, $cacheKey);
		if(!$obj)
		//if(!$this->loadFromCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$this->inventory_attribute_id))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			try{
				$array = self::loadAll($supported_currency_id, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiInventoryException(ApiCurrencyException::FILTER_NON_EXISTING_ID_PASSED);

		}
		else{
			$logger->debug("Cacheed obj returned");
			return $obj;
		}
	}
	
	/**
	 * Enter description here ...
	 * @param unknown_type $org_id
	 * @param unknown_type $filters
	 * @param unknown_type $limit
	 * @param unknown_type $offset
	 * @throws ApiInventoryException
	 * @return ArrayObject(InventoryBrand)
	 * 
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	 */
	public static function loadAll($supported_currency_id = null, $limit=200, $offset = 0)
	{
		global $logger;


		$logger->debug("Get all currencies");
		
		$sql = "SELECT c.id as supported_currency_id,
		c.name as name,
		c.symbol as symbol,
		c.iso_code as iso_code,
		c.iso_code_numeric as iso_code_numeric";
		
		$sql .= " FROM supported_currencies as c ";
		$sql .= " WHERE 1 ";
		
		if($supported_currency_id)
			$sql .= " AND c.id = ".$supported_currency_id;

		$sql .= " ORDER BY c.id asc ";
		
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;

		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;

		$sql = $sql . " LIMIT $offset, $limit";

		//print str_replace("\t", " ", $sql);
		// TODO: add more filters here
		$db = new Dbase('masters');
		$array = $db->query($sql);

		if($array)
		{

			$ret = array();
			foreach($array as $row)
			{
				$obj = self::fromArray(null, $row);
				
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getSupportedCurrencyId(), null);
				$obj->saveToCache($key, $obj);	
				$ret[] = $obj;

			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiCurrencyException(ApiCurrencyException::NO_SUPPORTED_CURRENCY_MATCHES);
		$logger->debug("No matches found");
	}

}
