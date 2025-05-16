<?php

include_once 'exceptions/ApiCurrencyRatioException.php';
include_once 'models/BaseModel.php';
include_once 'models/currencyratio/SupportedCurrency.php';

/**
 * @author class
 *
 * The defines all the currency ratio records in the DB. The table is more of a meta data
 * The linked table is product_management.products
 */
class CurrencyRatio extends BaseApiModel{

	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $currency_ratio_id;
	protected $ref_id;
	protected $ref_type;
	protected $ratio;
	protected $base_currency;
	protected $transaction_currency;
	protected $value;
	protected $added_on;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX_ID = 'CUR_RATIO_ID';
	const CACHE_KEY_PREFIX_REF_ID = 'CUR_RATIO_REF_ID';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($current_org_id)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		$this->current_org_id = $current_org_id;

		$this->logger = $logger;

		// db connection
		$this->db = new Dbase( 'users' );
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"currency_ratio_id",
				"ref_id",
				"ref_type",
				"ratio",
				"base_currency",
				"transaction_currency",
				"value",
				"added_on",
				"added_by"
				);
	}
	public function getCurrenyRatioId()
	{
	    return $this->currency_ratio_id;
	}

	public function setCurrencyRatioId($currency_ratio_id)
	{
	    $this->currency_ratio_id = $currency_ratio_id;
	}

	public function getRefId()
	{
	    return $this->ref_id;
	}

	public function setRefId($ref_id)
	{
	    $this->ref_id = $ref_id;
	}

	public function getRefType()
	{
	    return $this->ref_type;
	}

	public function setRefType($ref_type)
	{
	    $this->ref_type = $ref_type;
	}

	
	public function getBaseCurrency()
	{
	    return $this->base_currency;
	}

	public function setBaseCurrency($base_currency)
	{
	    if($base_currency instanceof SupportedCurrency )
			$this->base_currency = $base_currency;
		else if(is_array($base_currency))
			$this->base_currency = SupportedCurrency::fromArray(null, $base_currency);
		else if(is_string($base_currency))
			$this->base_currency = SupportedCurrency::fromString(null, $base_currency);
	}

	public function getTransactionCurrency()
	{
	    return $this->transaction_currency;
	}
	
	public function setTransactionCurrency($transaction_currency)
	{
		if($transaction_currency instanceof SupportedCurrency )
			$this->transaction_currency = $transaction_currency;
		else if(is_array($transaction_currency))
			$this->transaction_currency = SupportedCurrency::fromArray(null, $transaction_currency);
		else if(is_string($transaction_currency))
			$this->transaction_currency = SupportedCurrency::fromString(null, $transaction_currency);
	}

	public function getAddedOn()
	{
	    return $this->added_on;
	}

	public function setAddedOn($added_on)
	{
	    $this->added_on = $added_on;
	}
	
	public function setValue($value)
	{
		$this->value = $value;
	}
	
	public function getValue()
	{
		return $this->value;
	}
	
	public function getRatio()
	{
		return $this->ratio;
	}
	
	public function setRatio($ratio)
	{
		$this->ratio = $ratio;
	}

	
	public function save()
	{
 		if(!$this->validate())
 		{
 			$this->logger->debug("Validation has failed, returning now");
 			throw new ApiCurrencyException(ApiCurrencyException::VALIDATION_FAILED);
 		}
		
		if(isset($this->ref_id))
			$columns["ref_id"]= $this->ref_id;
		
		if(isset($this->ref_type))
			$columns["ref_type"]= "'".addslashes($this->ref_type)."'";
		
		if(isset($this->ratio))
			$columns["ratio"]= $this->ratio;
		
		if($this->base_currency instanceof SupportedCurrency && $this->base_currency->getSupportedCurrencyId())
			$columns["base_currency_id"]= $this->base_currency->getSupportedCurrencyId();
		
		if($this->transaction_currency instanceof SupportedCurrency && $this->transaction_currency->getSupportedCurrencyId())
			$columns["transaction_currency_id"]= $this->transaction_currency->getSupportedCurrencyId();
		
		if($this->value)
			$columns["value"] = $this->value;
		if($this->added_on)
			$columns["added_on"] = "'".$this->added_on."'";

		// new user
		if(!$this->currency_ratio_id)
		{
			$this->logger->debug("currency id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"]= $this->current_user_id;
//			$columns["added_on"]= "NOW()";
			
			$sql = "INSERT INTO transaction_currency_log ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db->insert($sql);

			$this->logger->debug("Return of saving the currency ratio is $newId");

			if($newId > 0)
				$this->currency_ratio_id = $newId;
		}
		else
		{
			$this->logger->debug("currency ratio id is set, so its going to be an update query");
			$sql = "UPDATE currency_ratio SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->currency_ratio_id";
			$newId = $this->db->update($sql);
			if($newId)
				$newId = $this->currency_ratio_id;
		}

		if($newId)
		{
			//TODO
			$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID,$this->currency_ratio_id, $this->current_org_id);
			$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX_REF_ID,$this->ref_id.$this->ref_type, $this->current_org_id);
			$this->deleteValueFromCache($key);
			$this->deleteValueFromCache($key2);
			return;
		}
		else
		{
			throw new ApiCurrencyException(ApiCurrencyException::SAVING_DATA_FAILED);
		}

	}

	/*
	 * Validate ann the saves and updates.
	* TODO: add the validators
	*/
	public function validate()
	{
		//TODO
		return true;
	}

	/**
	 * The function loads the data linked to the object, based on the id set using setter method
	 * @param unknown_type $org_id
	 * @param unknown_type $currency_ratio_id
	 * @throws ApiCurrencyException
	 * @return CurrencyRatio
	 */
	public static function loadById($org_id, $currency_ratio_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on currency ratio id");

		if(!$currency_ratio_id )
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_ID_NOT_PASSED);
		}

		$key = self::generateCacheKey(self::CACHE_KEY_PREFIX,$currency_ratio_id, $org_id);
		$obj = self::loadFromCache($org_id, $key);
		if(!$obj)
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new CurrencyRatioFilters();
			$filters->id = $currency_ratio_id;
			try{
				$array = self::loadAll($org_id,  $filters, 1);

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

	public static function loadByRefIdRefType($org_id, $ref_id = null, $ref_type = null )
	{
		global $logger;
		$logger->debug("Loading from based on code");
	
		if(!$ref_id)
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_REF_ID_NOT_PASSED);
		}
		
		if(!$ref_type)
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_REF_TYPE_NOT_PASSED);
		}
	
		$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_REF_ID,$ref_id.$ref_type, $org_id);
		$obj = self::loadFromCache($org_id, $key);
		if(!$obj)
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");
	
			$filters = new CurrencyRatioFilters();
			$filters->ref_id = $ref_id;
			$filters->ref_type = $ref_type;
			try{
				$array = self::loadAll($org_id,  $filters, 1);
	
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}
	
			if($array)
			{
				return $array[0];
			}
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_NON_EXISTING_REF_ID_TYPE_PASSED);
	
		}
		else
		{
			$logger->debug("Cacheed obj returned");
			return $obj;
		} 
	}
	
	public static function loadAll($org_id, $filters = null,  $limit=200, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof CurrencyRatioFilters))
		{
			throw new ApiCurrencyException(ApiCurrencyException::FILTER_ATTR_INVALID_OBJECT_PASSED);
		}


		$logger->debug("Get all results based on the filters");

		$sql = "SELECT
		c.id as currency_ratio_id,
		c.ref_id as ref_id,
		c.ref_type as ref_type,
		c.ratio as ratio,
		c.base_currency_id as base_currency,
		c.transaction_currency_id as transaction_currency,
		c.value as value, 
		c.added_on as added_on
		";		
		$sql .= " FROM transaction_currency_log c ";
		
		$sql .= " WHERE c.org_id = $org_id ";

		$idenitifersql = array();
		if($filters->id)
			$sql .= " AND c.id= ".$filters->id;
		if($filters->ref_id)
			$sql .= " AND c.ref_id= '".$filters->ref_id."'";
		if($filters->ref_type)
			$sql .= " AND c.ref_type= '".$filters->ref_type."'";

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
		$db = new Dbase('users');
		$array = $db->query($sql);

		if($array)
		{

			$ret = array();
			foreach($array as $row)
			{
				$obj = self::fromArray($org_id, $row);
				
				if($row["base_currency"] > 0 )
				{
					$obj->setBaseCurrency(SupportedCurrency::loadById($row["base_currency"]));
				}
				
				if($row["transaction_currency"] > 0 )
				{
					$obj->setTransactionCurrency(SupportedCurrency::loadById($row["transaction_currency"]));
				}
				
				$ret[] = $obj;
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_REF_ID,$row["ref_id"].$row["ref_type"], $org_id);
				$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID,$row["currency_ratio_id"], $org_id);
				$obj->saveToCache($key, $obj);
				$obj->saveToCache($key2, $obj);

			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiCurrencyException(ApiCurrencyException::NO_CURRENCY_RATIO_MATCHES);
		$logger->debug("No matches found");
	}

}
