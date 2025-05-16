<?php
include_once 'helper/MetaDbase.php';
include_once 'exceptions/ApiCurrencyRatioException.php';
include_once 'models/BaseModel.php';
include_once 'models/filters/CurrencyRatioFilters.php';

/**
 * @author class
 *
 * The defines all the currency ratio records in the DB. The table is more of a meta data
 * The linked table is product_management.products
 */
class CurrencyConversion extends BaseApiModel{

	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $currency_conversion_id;
	protected $ratio;
	protected $date;
	protected $is_active;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX_ID = 'CUR_CONVERSION_ID';
	const CACHE_KEY_PREFIX_CURRENCY_ID = 'CUR_CONVERSION_CURRENCY_ID';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct()
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		$this->logger = $logger;

		// db connection
		$this->db = new MetaDbase( 'meta_masters' );
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"currency_conversion_id",
				"currency",
				"ratio",
				"is_active"
				);
	}
	
	public function getCurrenyConversionId()
	{
	    return $this->currency_conversion_id;
	}

	public function setCurrencyConversionId($currency_conversion_id)
	{
	    $this->currency_conversion_id = $currency_conversion_id;
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
			$this->currency = SupportedCurrency::fromArray(null, $currency);
		else if(is_string($currency))
			$this->currency = SupportedCurrency::fromString(null, $currency);
	}
	
	public function getIsActive()
	{
		return $this->is_active;	
	}
	
	public function setIsActive($is_active)
	{
		$this->is_active = $is_active;
	}

	

	/**
	 * The function loads the data linked to the object, based on the id set using setter method
	 * @param unknown_type $org_id
	 * @param unknown_type $currency_ratio_id
	 * @throws ApiCurrencyException
	 * @return CurrencyRatio
	 */
	public static function loadById($currency_conversion_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on currency ratio id");

		if(!$currency_conversion_id )
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_ID_NOT_PASSED);
		}

		$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID,$currency_conversion_id, null);
		$obj = self::loadFromCache(null, $key);
		if(!$obj)
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new CurrencyRatioFilters();
			$filters->id = $currency_conversion_id;
			try{
				$array = self::loadAll( $filters, 1);

			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_NON_EXISTING_ID_PASSED);
		}
		else
		{
			$logger->debug("Cacheed obj returned");
			return $obj;
		}
	}
	
		/**
	 * The function loads the data linked to the object, based on the id set using setter method
	 * @param unknown_type $org_id
	 * @param unknown_type $currency_ratio_id
	 * @throws ApiCurrencyException
	 * @return CurrencyRatio
	 */
	public static function loadByCurrencyId($currency_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on currency ratio id");

		if(!$currency_id )
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_ID_NOT_PASSED);
		}

		$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CURRENCY_ID,$currency_id, null);
		$obj = self::loadFromCache(null, $key);
		if(!$obj)
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new CurrencyRatioFilters();
			$filters->currency = $currency_id;
			try{
				$array = self::loadAll($filters, 1);

			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_NON_EXISTING_ID_PASSED);
		}
		else
		{
			$logger->debug("Cacheed obj returned");
			return $obj;
		}
	}

	
	
	public static function loadAll($filters = null,  $limit=200, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof CurrencyRatioFilters))
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_INVALID_OBJECT_PASSED);
		}


		$logger->debug("Get all results based on the filters");

		$sql = "SELECT
		c.id as currency_conversion_id,
		c.currency_id as currency,
		c.ratio as ratio,
		c.is_active as is_active
		";		
		$sql .= " FROM currency_ratio_inr c ";
		
		$sql .= " WHERE is_active=1 ";

		$idenitifersql = array();
		if($filters->id)
			$sql .= " AND c.id= ".$filters->id;
		if($filters->currency)
			$sql .= " AND c.currency_id = ".$filters->currency;


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
		$db = new MetaDbase('meta_masters');
		$array = $db->query($sql);

		if($array)
		{

			$ret = array();
			foreach($array as $row)
			{
				$obj = self::fromArray(null, $row);
				
				if($row["currency"] > 0 )
				{
					$obj->setCurrency(SupportedCurrency::loadById($row["currency"]));
				}
				
				$ret[] = $obj;
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID,$obj->getCurrenyConversionId(), null);
				$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX_CURRENCY_ID,$row["currency"], null);
				$obj->saveToCache($key, $obj);
				$obj->saveToCache($key2, $obj);

			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiCurrencyException(ApiCurrencyException::NO_CURRENCY_CONVERSION_MATCHES);
		$logger->debug("No matches found");
	}

}
