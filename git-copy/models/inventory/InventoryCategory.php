<?php
include_once 'models/filters/InventoryAttributeGenericLoadFilters.php';
include_once 'exceptions/ApiInventoryException.php';
//include_once 'models/inventory/InventoryCategoryValue.php';
include_once 'models/BaseModel.php';
/**
 * @author class
 *
 * The defines all the inventory categories records in the DB. The table is more of a meta data
 * The linked table is product_management.categories
 */
class InventoryCategory extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $inventory_category_id;
	protected $code;
	protected $name;
	protected $description;
	protected $added_by;
	protected $added_on;
	
	CONST MAX_LEVEL = 5;
	
	/**
	 * Parent category id
	 * @var InventoryCategory
	 */
	protected $parentCategory;
	
	/**
	 * Enter description here ...
	 * @var ArrayObject(InventoryCategory)
	 */
	protected $parentCategoryHeirarchy;
	
	/**
	 * Array of the inventory attr value args
	 * @var ArrayObject(InventoryAttributeValue)
	 */
	protected $inventoryCategoryValuesArr;

	protected $validationErrorArr;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'INV_CAT_ID';
	const CACHE_KEY_PREFIX_CODE = 'INV_CAT_CODE';
	const CACHE_KEY_PREFIX_VALUES = 'INV_CAT_VALUES';
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
				"parentCategory",
				"added_by",
				"added_on",
				);
	}
	public function getInventoryCategoryId()
	{
	    return $this->inventory_category_id;
	}

	public function setInventoryCategoryId($inventory_category_id)
	{
	    $this->inventory_category_id = $inventory_category_id;
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

	public function getAddedBy()
	{
	    return $this->added_by;
	}

	public function getAddedOn()
	{
	    return $this->added_on;
	}

	/**
	 * Enter description here ...
	 * @return InventoryCategory
	 */
	public function getParentCategory()
	{
	    return $this->parentCategory;
	}

	public function setParentCategory($parentCategory)
	{
	    if($parentCategory instanceof InventoryCategory )
			$this->parentCategory = $parentCategory;
		else if(is_array($parentCategory))
			$this->parentCategory = InventoryCategory::fromArray($this->current_org_id, $parentCategory);
		else if(is_string($parentCategory))
			$this->parentCategory = InventoryCategory::fromString($this->current_org_id, $parentCategory);
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $parentCategoryId
	 */
	public function setParentCategoryById($parentCategoryId)
	{
	    $this->parentCategory = InventoryCategory::loadById($this->current_org_id, $parentCategory);
	}

	public function setParentCategoryByCode($parentCategoryCode)
	{
	    $this->parentCategory = InventoryCategory::loadByCode($this->current_org_id, $parentCategoryCode);
	}
	
	/**
	 * Enter description here ...
	 * @return ArrayObject(InventoryCategory)
	 */
	public function getCategoryValues()
	{
		if(!$this->inventoryCategoryValuesArr)
	    {
	    	$cacheKey = InventoryCategory::generateCacheKey(InventoryCategory::CACHE_KEY_PREFIX_VALUES,$this->inventory_category_id, $this->current_org_id);

	    	if($str = InventoryCategory::getFromCache($cacheKey))
	    	{
	    		$this->logger->debug("Reading from cache is successful");
	    		$array = self::decodeFromString($str);
	    		foreach($array as $row)
	    		{
	    			$obj = InventoryCategory::fromString($this->current_org_id, $row);
	    			$ret[] = $obj;
	    		}
	    		$this->inventoryCategoryValuesArr = $ret;
	    	}

	    	else
	    	{
		    	$filters = new InventoryAttributeGenericLoadFilters();
		    	$filters->parent_id = $this->inventory_category_id;
	    		$this->inventoryCategoryValuesArr = InventoryCategory::loadAll($this->current_org_id, $filters);
	    		
	    		$cacheStringArr = array();
	    		foreach($this->inventoryCategoryValuesArr as $obj)
	    		{
	    			$cacheStringArr[$obj->getCode()] = $obj->toString();
	    		}

	    		if($cacheStringArr)
	    		{
	    			$this->logger->debug("saving the category  values to cache");
	    			$str = InventoryCategory::encodeToString($cacheStringArr);
	    			$obj->saveToCache($cacheKey, $str);
	    		}
	    	}
	    }
	    
	    return $this->inventoryCategoryValuesArr;
	}

	public function setCategoryValues($inventoryCategoryValuesArr)
	{
		if(is_string($inventoryCategoryValuesArr))
			$attrs = $this->decodeFromString($inventoryCategoryValuesArr);
		
		$this->inventoryCategoryValuesArr = array();
		foreach($inventoryCategoryValuesArr as $val)
		{
			if($val instanceof InventoryCategory)
				$this->inventoryCategoryValuesArr[] = $val;
			else if(is_array($val))
				$this->inventoryCategoryValuesArr[] = InventoryCategory::fromArray($this->current_org_id, $val);
			else if(is_string($val))
				$this->inventoryCategoryValuesArr[] = InventoryCategory::fromString($this->current_org_id, $val);
		}
	}
	
	/**
	 * Enter description here ...
	 * @return ArrayObject(InventoryCategory)
	 */
	public function getParentCategoryHeirarchy() {
		
		if(!$this->parentCategoryHeirarchy)
		{
			$parent = $this;
			$this->parentCategoryHeirarchy = array();
			$parentIds = array();
                        $parent = $this;
			while ($parent = $parent->getParentCategory())
			{
				// loops exists;
				if(in_array($parent->getInventoryCategoryId(), $parentIds))
				{
					break;
				}
				$parentIds[] = $parent->getInventoryCategoryId();
				array_unshift( $this->parentCategoryHeirarchy,  $parent);
				if(count($parentIds) > InventoryCategory::MAX_LEVEL)
				{
					break;
				} 
			}
		}
		return $this->parentCategoryHeirarchy;
	}
	public function save()
	{
		// validate the data
		$this->validate();

 		if(isset($this->code))
			$columns["code"]= "'".addslashes($this->code)."'";
		if(isset($this->name))
			$columns["name"]= "'".addslashes($this->name)."'";
		if(isset($this->description))
			$columns["description"]= "'".addslashes($this->description)."'";
		if($this->parentCategory && $this->parentCategory->getInventoryCategoryId())
			$columns["parent_id"]= "'".$this->parentCategory->getInventoryCategoryId()."'";

		// new user
		if(!$this->inventory_category_id)
		{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"]= $this->current_user_id;
			$columns["added_on"]= "NOW()";
			
			$sql = "INSERT IGNORE INTO categories ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db->insert($sql);

			$this->logger->debug("Return of saving the inventory categories is $newId");

			if($newId > 0)
				$this->inventory_category_id = $newId;
		}
		else
		{
			$this->logger->debug("unvetory attrs id is set, so its going to be an update query");
			$sql = "UPDATE categories SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->inventory_category_id";
			$newId = $this->db->update($sql);
			if($newId)
				$newId = $this->inventory_category_id;
		}

		if($newId)
		{
			$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$this->code, $this->current_org_id);
			$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$this->inventory_category_id, $this->current_org_id);
			$key3 = self::generateCacheKey(self::CACHE_KEY_PREFIX,"", $this->current_org_id);
			$this->deleteValueFromCache($key);
			$this->deleteValueFromCache($key2);
			$this->deleteValueFromCache($key3);
		}
		else
		{
			throw new ApiInventoryException(ApiInventoryException::SAVING_DATA_FAILED);
		}
		
		// save the attr values also
		if($this->inventoryCategoryValuesArr)
		{
			foreach ($this->inventoryCategoryValuesArr as $value)
			{
				$value->setCategoryId($newId);
				try {
					$value->save();	
				} catch (Exception $e) {
					$this->logger->debug("Saving failed due the exception - ".$e->getMessage());
				}
				
			}
		}

	}

	/*
	 * Validate ann the saves and updates.
	* TODO: add the validators
	*/
	public function validate()
	{
		$this->logger->debug("Starting the validation of the product category");
		
		if(!$this->code && !$this->inventory_category_id){
			throw new ApiInventoryException(ApiInventoryException::CODE_NOT_PASSED);
		}
		
		// 
		if($this->parentCategory instanceof InventoryCategory)
		{
			$allParents = $this->parentCategory->getParentCategoryHeirarchy();
			$allParents[] = $this->parentCategory;

			// we have to restrict the max level to which we can have categories
			if(count($allParents) > InventoryCategory::MAX_LEVEL)
				throw new ApiInventoryException(ApiInventoryException::MAX_ATTR_DEPTH_EXCEED);
				
			// loop detection
			foreach ($allParents as $parent)
			{
				if($parent->getCode() == $this->code)
				{
					throw new ApiInventoryException(ApiInventoryException::CYCLE_DETECTED);
				}
			}
		}
		
		if($this->code && strlen($this->code)>50)
			throw new ApiInventoryException(ApiInventoryException::CODE_LENGTH_EXCEEDED);
		
		return true;
	}

	/**
	 * The function loads the data linked to the object, based on the id set using setter method
	 * @param unknown_type $org_id
	 * @param unknown_type $id
	 * @throws ApiInventoryException
	 * @return InventoryCategory
	 */
	public static function loadById($org_id, $id)
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
		{
			$logger->debug("Cached obj returned");
			return $obj;
		}
	}

	/**
	 * Gets the category by code
	 * @param unknown_type $org_id
	 * @param unknown_type $code
	 * @throws ApiInventoryException
	 * @return InventoryCategory
	 */
	public static function loadByCode($org_id, $code )
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
		{
			$logger->debug("Cacheed obj returned");
			return $obj;
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
			$cacheStringArr[$obj->getInventoryCategoryId()] = $obj->toString();
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
		c.id as inventory_category_id,
		c.code as code,
		c.name as name,
		c.description as description,
		c.parent_id as parent_id,
		c.added_by as added_by,
		c.added_on as added_on
		";		
		$sql .= " FROM categories c ";
		
		$sql .= " WHERE c.org_id = $org_id ";

		$idenitifersql = array();
		if($filters->id)
			$sql .= " AND c.id IN (".$filters->id.") ";
		if($filters->code)
			$sql .= " AND c.code= '".$filters->code."'";
		if($filters->parent_id)
			$sql .= " AND c.parent_id IN (".$filters->parent_id.")";

		$sql .= " ORDER BY c.parent_id, c.code asc ";
		
		if($limit>0 && $limit<10001)
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
				 
				if($row["parent_id"] > 0)
				{
					$obj->setParentCategory(self::loadById($org_id, $row["parent_id"]));
				}
				$ret[] = $obj;
				
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$obj->getCode(), $org_id);
				$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getInventoryCategoryId(), $org_id);
				$obj->saveToCache($key, $obj);
				$obj->saveToCache($key2, $obj);
			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiInventoryException(ApiInventoryException::NO_ATTR_MATCHES);
		$logger->debug("No matches found");
	}

}
