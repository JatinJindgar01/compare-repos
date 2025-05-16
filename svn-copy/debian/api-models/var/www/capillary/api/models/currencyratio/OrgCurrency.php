<?php
include_once 'exceptions/ApiCurrencyRatioException.php';
include_once 'models/BaseModel.php';
include_once 'models/currencyratio/SupportedCurrency.php';
/**
 * @author class
 *
 * The defines all the supported currencies records in the DB. The table is more of a meta data
 * The linked table is user_management.inventory_item_attributes
 */
class OrgCurrency extends BaseApiModel{

	protected $db_user;
	protected $logger;
	#protected $current_user_id;
	protected $current_org_id;

	protected $org_currency_id;
	protected $currency;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'CUR_ORG_ID';
	const CACHE_KEY_PREFIX_SUPP_CUR = 'CUR_ORG_SUPP_CUR';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($current_org_id, $org_currency_id = null)
	{
		global $logger, $currentuser;
		#$this->currentuser = &$currentuser;
		#$this->current_user_id = $currentuser->user_id;
		$this->current_org_id = $current_org_id;

		$this->logger = $logger;

		// db connection
		$this->db = new Dbase( 'masters' );
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"org_currency_id",
				"currency"
				);
	}
	public function getOrgCurrencyId()
	{
	    return $this->org_currency_id;
	}

	public function setOrgCurrencyId($org_currency_id)
	{
	    $this->supported_currency_id = $org_currency_id;
	}

	public function getCurrency()
	{
	    return $this->currency;
	}

	public function setCurrency($currency)
	{
		if($currency instanceof SupportedCurrency )
			$this->currency = $currency;
		else if(is_array($currency))
			$this->currency = SupportedCurrency::fromArray($this->current_org_id, $currency);
		else if(is_string($currency))
			$this->currency = SupportedCurrency::fromString($this->current_org_id, $currency);
	}
	
	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($org_id, $org_currency_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on org currency id");

		if(!$org_currency_id)
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_ID_NOT_PASSED);
		}
		
		$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX, $org_currency_id, $org_id);
		$obj = self::loadFromCache($org_id, $cacheKey);
		if(!$obj)
		//if(!$this->loadFromCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$this->inventory_attribute_id))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			try{
				$array = self::loadAll($org_id, $org_currency_id, 1);
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
	
	public static function loadBySupportedCurrencyId($org_id, $supported_currency_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on org currency id");

		if(!$supported_currency_id)
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_ID_NOT_PASSED);
		}
		
		$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_SUPP_CUR, $supported_currency_id, $org_id);
		$obj = self::loadFromCache($org_id, $cacheKey);
		if(!$obj)
		//if(!$this->loadFromCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$this->inventory_attribute_id))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			try{
				$array = self::loadAll($org_id, null, $supported_currency_id, 1);
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
	public static function loadAll($org_id, $org_currency_id = null, $supported_currency_id = null, $limit=200, $offset = 0)
	{
		global $logger;


		$logger->debug("Get all currencies");
		
		$sql = "SELECT c.id as org_currency_id,
		c.currency_id as currency_id ";
		
		$sql .= " FROM org_currencies as c ";
		
		$sql .= " WHERE c.org_id = $org_id ";
		
		if($org_currency_id)
			$sql .= " AND c.id = ".$org_currency_id;
		if($supported_currency_id)
			$sql .= " AND c.currency_id = ".$supported_currency_id;
		
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
				if($row["currency_id"] >0 )
				
				{
					
					$obj->setCurrency(SupportedCurrency::loadById($row["currency_id"]));
				}
				
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getOrgCurrencyId(), $org_id);
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_SUPP_CUR,$row["currency_id"], $org_id);
				$obj->saveToCache($key, $obj);	
				$ret[] = $obj;
			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiCurrencyException(ApiCurrencyException::NO_ORG_CURRENCY_MATCHES);
		$logger->debug("No matches found");
	}

}
