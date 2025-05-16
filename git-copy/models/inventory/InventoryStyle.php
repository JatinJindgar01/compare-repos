<?php
include_once 'models/filters/InventoryAttributeGenericLoadFilters.php';
include_once 'exceptions/ApiInventoryException.php';
include_once 'models/BaseModel.php';
/**
 * @author class
 *
 * The defines all the inventory attribues records in the DB. The table is more of a meta data
 * The linked table is user_management.inventory_item_attributes
 */
class InventoryStyle extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $inventory_style_id;
	protected $code;
	protected $name;
	protected $description;
	
	protected $validationErrorArr;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'INV_STYLE_ID';
	const CACHE_KEY_PREFIX_CODE = 'INV_STYLE_CODE';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($current_org_id, $code = null, $inventory_style_id = null)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		$this->current_org_id = $current_org_id;

		$this->logger = $logger;

		// db connection
		$this->db = new Dbase( 'product' );
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"inventory_style_id",
				"code",
				"name",
				"description"
				);
	}
	public function getInventoryStyleId()
	{
	    return $this->inventory_style_id;
	}

	public function setInventoryStyleId($inventory_style_id)
	{
	    $this->inventory_style_id = $inventory_style_id;
	}

	public function getCode()
	{
	    return $this->code;
	}

	public function setCode($code)
	{
	    $this->code = $code;
	}

	public function getDescription()
	{
	    return $this->description;
	}

	public function setDescription($description)
	{
	    $this->description = $description;
	}

	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function save()
	{
		if(isset($this->code))
			$columns["code"]= "'".addslashes($this->code)."'";
		if(isset($this->description))
			$columns["description"]= "'".addslashes($this->description)."'";
		if(isset($this->name))
			$columns["name"] = "'".addslashes($this->name)."'";
		
		if(!$this->inventory_style_id){
			$columns["org_id"] = $this->current_org_id;
			$columns["added_by"] = $this->current_user_id;
			$columns["added_on"]= "NOW()";
			
			$sql = "INSERT INTO `styles` ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ";
			$sql .= " ON DUPLICATE KEY UPDATE";
			$sql .= " code=VALUES(code)";
			$sql .= ", description=VALUES(description)";
			$sql .= ", name=VALUES(name);";
			$newId = $this->db->insert($sql);
		}
		else{
			$this->logger->debug("invetory style id is set, so its going to be an update query");
			$sql = "UPDATE styles SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->inventory_style_id";
			$newId = $this->db->update($sql);
			if($newId)
				$newId = $this->inventory_style_id;
		}
		
		if($newId>0)
			$this->inventory_style_id = $newId;
		
		if($this->inventory_style_id>0){
			$cacheKey = $this->generateCacheKey(InventoryStyle::CACHE_KEY_PREFIX, $this->inventory_style_id, $this->current_org_id);
			$cacheKeyCode = $this->generateCacheKey(InventoryStyle::CACHE_KEY_PREFIX, $this->code, $this->current_org_id);
			try{
				$this->deleteValueFromCache($cacheKey);
				$this->deleteValueFromCache($cacheKeyCode);
			} catch(Exception $e){
				$this->logger->debug($cacheKey." not found to delete");
			}

			$obj = self::loadById($this->current_org_id, $this->inventory_style_id);
			$this->saveToCache($cacheKey, $obj->toString());
			$this->saveToCache($cacheKeyCode, $obj->toString());
			return $obj;
		}
		else
		{
			throw new ApiInventoryException(ApiInventoryException::SAVING_DATA_FAILED);
		}

	}

	/*
	 * Validate ann the saves and updates.
	* TODO: add the validators
	*/
	public function validate()
	{

		if(!$this->code && !$this->inventory_style_id){
			throw new ApiInventoryException(ApiInventoryException::CODE_NOT_PASSED);
		}
		
		if($this->code && strlen($this->code)>50)
			throw new ApiInventoryException(ApiInventoryException::CODE_LENGTH_EXCEEDED);
		
		return true;
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($org_id, $inventory_style_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on style id");

		if(!$inventory_style_id)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ID_NOT_PASSED);
		}
		
		$cachekey = self::generateCacheKey(self::CACHE_KEY_PREFIX, $inventory_style_id, $org_id);
		$obj = self::loadFromCache($org_id, $cachekey);
		if(!self::loadFromCache($org_id, $cachekey))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeGenericLoadFilters();
			$filters->id = $inventory_style_id;
			try{
				$array = self::loadAll($org_id,  $filters, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_ID_PASSED);

		}
		else{
			$logger->debug("Returning from cache");
			return $obj;
		}
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadByCode($org_id, $code = null)
	{
		global $logger;
		$logger->debug("Loading from based on style code");
	
		if(!$code)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_CODE_NOT_PASSED);
		}
		
		$cachekey = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE, $code, $org_id);	
		$obj = self::loadFromCache($org_id, $cachekey);
		if(!self::loadFromCache($org_id, $cachekey))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeGenericLoadFilters();
			$filters->code = $code;
			try{
				$array = self::loadAll($org_id,  $filters, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_CODE_PASSED);

		}
		else{
			$logger->debug("Cache obj returned");
			return $obj;
			//return from cache
		}
	}
	

	public static function loadForOrg($org_id)
	{
		global $logger;
		$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX, "", $org_id);
		
		if($str = self::getFromCache($cacheKey))
		{
			$logger->debug("Reading from cache is successful");
			$array = self::decodeFromString($str);
			foreach($array as $row)
			{
				$obj = InventoryStyle::fromString($org_id, $row);
				$ret[] = $obj;
				//$logger->debug("data from cache" . $obj->toString());
			}
			return $ret;
		}
		
		$logger->debug("Reading from cache has failed");
		$attrs = self::loadAll($org_id);
		$cacheStringArr = array();
		foreach($attrs as $obj)
		{
			$cacheStringArr[$obj->getInventoryBrandId()] = $obj->toString();
		}
		
		if($cacheStringArr)
		{
			$logger->debug("saving the attributes to cache");
			$str = self::encodeToString($cacheStringArr);
			$obj->saveToCache($cacheKey, $str);
		}
		return $attrs;
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
	public static function loadAll($org_id, $filters = null, $limit=200, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof InventoryAttributeGenericLoadFilters))
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_INVALID_OBJECT_PASSED);
		}


		$logger->debug("Get all users based on the filters");
		
		$sql = "SELECT s.id as inventory_style_id,
		s.code as code,
		s.name as name,
		s.description as description";
		
		$sql .= " FROM styles as s ";
		$sql .= " WHERE s.org_id = $org_id ";
		
		if($filters->id)
			$sql .= " AND s.id = ".$filters->id;
		if($filters->code)
			$sql .= " AND s.code = '".$filters->code."'";
		

		$sql .= " ORDER BY s.code asc ";
		
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
		$db = new Dbase('product');
		$array = $db->query($sql);

		if($array)
		{

			$ret = array();
			foreach($array as $row)
			{
				$obj = InventoryStyle::fromArray($org_id, $row);
				$ret[] = $obj;
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$obj->getCode(), $org_id);
				$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getInventoryStyleId(), $org_id);
				$obj->saveToCache($key, $obj);
				$obj->saveToCache($key2, $obj);

			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiInventoryException(ApiInventoryException::NO_STYLE_MATCHES);
		$logger->debug("No matches found");
	}

}
