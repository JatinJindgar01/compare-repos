<?php
include_once 'models/filters/InventoryAttributeGenericLoadFilters.php';
include_once 'exceptions/ApiInventoryException.php';
include_once 'models/BaseModel.php';
/**
 * @author class
 *
 * The defines all the inventory categories records in the DB. The table is more of a meta data
 * The linked table is product_management.categories
 */
class InventoryCategoryValue extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $inventory_category_value_id;
	protected $code;
	protected $name;
	protected $description;
	protected $category_id;
	
	protected $validationErrorArr;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'INV_CAT_VAL_ID';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($current_org_id)
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
				"inventory_category_id",
				"code",
				"name",
				"description",
				"added_by",
				"added_on",
				);
	}
	public function getInventoryCategoryValueId()
	{
	    return $this->inventory_category_value_id;
	}

	public function setInventoryCategoryValueId($inventory_category_value_id)
	{
	    $this->inventory_category_value_id = $inventory_category_value_id;
	}

	public function getCode()
	{
	    return $this->code;
	}

	public function setCode($code)
	{
	    $this->code = $code;
	}

	public function getName()
	{
	    return $this->name;
	}

	public function setName($name)
	{
	    $this->name = $name;
	}

	public function getDescription()
	{
	    return $this->description;
	}

	public function setDescription($description)
	{
	    $this->description = $description;
	}

	public function getCategoryId()
	{
	    return $this->category_id;
	}

	public function setCategoryId($category_id)
	{
	    $this->category_id = $category_id;
	}
	
	
	public function save()
	{
 		if(!$this->validate())
 		{
 			$this->logger->debug("Validation has failed, returning now");
 			throw new ApiInventoryException(ApiInventoryException::VALIDATION_FAILED);
 		}
		if(isset($this->code))
			$columns["code"]= "'".addslashes($this->code)."'";
		if(isset($this->name))
			$columns["name"]= "'".addslashes($this->name)."'";
		if(isset($this->description))
			$columns["description"]= "'".addslashes($this->description)."'";
		if(isset($this->category_id))
			$columns["category_id"]= "'".$this->category_id."'";

		// new user
		//if(!$this->inventory_category_value_id)
		{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"]= $this->current_user_id;
			$columns["added_on"]= "'" .DateUtil::getCurrentDateTime()."'";
			
			$sql = "INSERT IGNORE INTO category_values ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ";
			$sql .= " ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description) ";
			$newId = $this->db->update($sql);

			$this->logger->debug("Return of saving the inventory categories is $newId");
		}

		if($newId)
		{
			$idObj = InventoryCategory::loadByCode($this->code);
			$this->inventory_category_value_id = $idObj->getInventoryCategoryId();
			
			$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$this->code, $this->current_org_id);
			$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$this->inventory_category_id, $this->current_org_id);
			$key3 = self::generateCacheKey(self::CACHE_KEY_PREFIX,"", $this->current_org_id);
			$this->deleteValueFromCache($key);
			$this->deleteValueFromCache($key2);
			$this->deleteValueFromCache($key3);
			
			return;
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
		return true;
	}

	/**
	 * The function loads the data linked to the object, based on the id set using setter method
	 * @param unknown_type $org_id
	 * @param unknown_type $id
	 * @throws ApiInventoryException
	 * @return InventoryCategory
	 */
	public static function loadById($org_id, $id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on attr id");

		if(!$id )
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_ID_NOT_PASSED);
		}

		$key = self::generateCacheKey(self::CACHE_KEY_PREFIX,$id, $org_id);
		$obj = self::loadFromCache($org_id, $key);
		if(!$obj)
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeGenericLoadFilters();
			$filters->id = $id;
			try{
				$array = self::loadAll($org_id,  $filters, 1);

			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_ATTR_ID_PASSED);
		}
		else
			return $obj;
	}

	/**
	 * Gets the category by code
	 * @param unknown_type $org_id
	 * @param unknown_type $code
	 * @throws ApiInventoryException
	 * @return InventoryCategory
	 */
	public static function loadByCode($org_id, $code = null)
	{
		global $logger;
		$logger->debug("Loading from cache based on code");
	
		if(!$code)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_NAME_NOT_PASSED);
		}
	
		$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$code, $org_id);
		$obj = self::loadFromCache($org_id, $key);
		if(!$obj)
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
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_ATTR_NAME_PASSED);
	
		}
		else
			return $obj; 
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
				$obj = self::fromString($org_id, $row);
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
			$cacheStringArr[$obj->getInventoryCategoryValueId()] = $obj->toString();
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
	 * @return ArrayObject(InventoryCategory) 
	 * 
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	 */
	public static function loadAll($org_id, $filters = null,  $limit=200, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof InventoryAttributeGenericLoadFilters))
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_INVALID_OBJECT_PASSED);
		}


		$logger->debug("Get all users based on the filters");

		$sql = "SELECT
		cv.id as inventory_category_value_id,
		cv.code as code,
		cv.name as name,
		cv.description as description,
		cv.category_id as category_id,
		cv.added_by as added_by,
		cv.added_on as added_on
		";		
		$sql .= " FROM category_values cv ";
		
		$sql .= " WHERE cv.org_id = $org_id ";

		$idenitifersql = array();
		if($filters->id)
			$sql .= " AND cv.id= ".$filters->id;
		if($filters->code)
			$sql .= " AND cv.code= '".$filters->code."'";
		if($filters->parent_id)
			$sql .= " AND cv.category_id = ".$filters->parent_id."";

		$sql .= " ORDER BY cv.code asc ";
		
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
				$obj = self::fromArray($org_id, $row);
				
				if($row->parent_id > 0 )
				{
					$obj->setParentCategory(self::loadById($org_id, $row->parent_id));
					$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$obj->getCode(), $org_id);
					$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getInventryCategoryId(), $org_id);
					$obj->saveToCache($key, $obj);
					$obj->saveToCache($key2, $obj);	
				}
				$ret[] = $obj;

			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiInventoryException(ApiInventoryException::NO_ATTR_MATCHES);
		$logger->debug("No matches found");
	}

}
