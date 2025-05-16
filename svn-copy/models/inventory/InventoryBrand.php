<?php
include_once 'models/filters/InventoryAttributeGenericLoadFilters.php';
include_once 'exceptions/ApiInventoryException.php';
include_once 'models/BaseModel.php';
/**
 * @author class
 *
 * The defines all the inventory brands records in the DB. The table is more of a meta data
 * The linked table is product_management.products
 */
class InventoryBrand extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $inventory_brand_id;
	protected $code;
	protected $name;
	protected $description;
	protected $added_by;
	protected $added_on;
	protected $parentBrandHeirarchy;
	CONST MAX_LEVEL = 5;
	
	/**
	 * Parent brand id
	 * @var InventoryBrand
	 */
	protected $parentBrand;

	protected $validationErrorArr;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'INV_BRAND_ID';
	const CACHE_KEY_PREFIX_CODE = 'INV_BRAND_CODE';
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
				"inventory_brand_id",
				"code",
				"name",
				"description",
				"parentBrand",
				"added_by",
				"added_on",
				);
	}
	public function getInventoryBrandId()
	{
	    return $this->inventory_brand_id;
	}

	public function setInventoryBrandId($inventory_brand_id)
	{
	    $this->inventory_brand_id = $inventory_brand_id;
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

	public function setAddedBy($added_by)
	{
	    $this->added_by = $added_by;
	}

	public function getAddedOn()
	{
	    return $this->added_on;
	}

	public function setAddedOn($added_on)
	{
	    $this->added_on = $added_on;
	}

	public function getParentBrand()
	{
	    return $this->parentBrand;
	}

	public function setParentBrandById($parent_brand_id)
	{
		if($parent_brand_id)
		{
	    	$this->setParentBrand(InventoryBrand::loadById($this->current_org_id, $parent_brand_id));
		}
	}
	
	public function setParentBrand($parentBrand)
	{
		if($parentBrand instanceof InventoryBrand )
			$this->parentBrand = $parentBrand;
		else if(is_array($parentBrand))
			$this->parentBrand = InventoryBrand::fromArray($this->current_org_id, $parentBrand);
		else if(is_string($parentBrand))
			$this->parentBrand = InventoryBrand::fromString($this->current_org_id, $parentBrand);
	}

	/**
	 * Enter description here ...
	 * @return ArrayObject(InventoryBrand)
	 */
	public function getParentBrandHeirarchy() {
		
		if(!$this->parentBrandHeirarchy)
		{
                        global $logger;
			$parent = $this;
			$this->parentBrandHeirarchy = array();
			$parentIds = array();
                        $parent = $this;
			while ($parent = $parent->getParentBrand())
			{
				// loops exists;
                                $logger->debug("RRDL" . print_r($parent->getInventoryBrandId(), true));
				if(in_array($parent->getInventoryBrandId(), $parentIds))
				{
					break;
				}
				$parentIds[] = $parent->getInventoryBrandId();
				array_unshift( $this->parentBrandHeirarchy,  $parent);
				if(count($parentIds) > InventoryBrand::MAX_LEVEL)
				{
					break;
				} 
			}
		}
		return $this->parentBrandHeirarchy;
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
		if($this->parentBrand instanceof InventoryBrand && $this->parentBrand->getInventoryBrandId())
			$columns["parent_id"]= "'".$this->parentBrand->getInventoryBrandId()."'";

		// new user
		if(!$this->inventory_brand_id)
		{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"]= $this->current_user_id;
			$columns["added_on"]= "NOW()";
			
			$sql = "INSERT IGNORE INTO brands ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db->insert($sql);

			$this->logger->debug("Return of saving the inventory brands is $newId");

			if($newId > 0)
				$this->inventory_brand_id = $newId;
		}
		else
		{
			$this->logger->debug("unvetory attrs id is set, so its going to be an update query");
			$sql = "UPDATE brands SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->inventory_brand_id";
			$newId = $this->db->update($sql);
			if($newId)
				$newId = $this->inventory_brand_id;
		}

		if($newId)
		{
			$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$this->code, $this->current_org_id);
			$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$this->inventory_brand_id, $this->current_org_id);
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
		$this->logger->debug("Starting the validation of the product category");
		
		// 
		if($this->parentBrand instanceof InventoryBrand && $this->parentBrand->getInventoryBrandId()> 0 )
		{
			
			$allParents = $this->parentBrand->getParentBrandHeirarchy();
			$allParents[] = $this->parentBrand;

			// we have to restrict the max level to which we can have categories
			if(count($allParents) > InventoryBrand::MAX_LEVEL)
				throw new ApiInventoryException(ApiInventoryException::MAX_ATTR_DEPTH_EXCEED);
				
			// loop detection
			foreach ($allParents as $parent)
			{
				if($parent->getCode() == $this->code || ($parent->getParentBrand() && $parent->getParentBrand()->getCode() == $this->code))
				{
					throw new ApiInventoryException(ApiInventoryException::CYCLE_DETECTED);
				}
			}
		}
		
		if(!$this->code && !$this->inventory_brand_id){
			throw new ApiInventoryException(ApiInventoryException::CODE_NOT_PASSED);
		}
		
		if($this->code && strlen($this->code)>50)
			throw new ApiInventoryException(ApiInventoryException::CODE_LENGTH_EXCEEDED);
		
		return true;
	}

	/**
	 * The function loads the data linked to the object, based on the id set using setter method
	 * @param unknown_type $org_id
	 * @param unknown_type $inventory_brand_id
	 * @throws ApiInventoryException
	 * @return InventoryBrand
	 */
	public static function loadById($org_id, $inventory_brand_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on attr id");

		if(!$inventory_brand_id )
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_ID_NOT_PASSED);
		}

		$key = self::generateCacheKey(self::CACHE_KEY_PREFIX,$inventory_brand_id, $org_id);
		$obj = self::loadFromCache($org_id, $key);
		if(!$obj)
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeGenericLoadFilters();
			$filters->id = $inventory_brand_id;
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
			$logger->debug("Cacheed obj returned");
			return $obj;
		}
	}

	/**
	 * Gets the brand by code
	 * @param unknown_type $org_id
	 * @param unknown_type $inventory_brand_id
	 * @throws ApiInventoryException
	 * @return InventoryBrand
	 */
	public static function loadByCode($org_id, $code = null)
	{
		global $logger;
		$logger->debug("Loading from based on code");
	
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
	public static function loadAll($org_id, $filters = null,  $limit=200, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof InventoryAttributeGenericLoadFilters))
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_INVALID_OBJECT_PASSED);
		}


		$logger->debug("Get all users based on the filters");

		$sql = "SELECT
		b.id as inventory_brand_id,
		b.code as code,
		b.name as name,
		b.description as description,
		b.parent_id as parent_id,
		b.added_by as added_by,
		b.added_on as added_on
		";		
		$sql .= " FROM brands b ";
		
		$sql .= " WHERE b.org_id = $org_id ";

		$idenitifersql = array();
		if($filters->id)
			$sql .= " AND b.id= ".$filters->id;
		if($filters->code)
			$sql .= " AND b.code= '".$filters->code."'";
		if($filters->parent_id)
			$sql .= " AND b.parent_id IN (".$filters->parent_id.")";

		$sql .= " ORDER BY b.code asc ";
		
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
                $logger->debug("RQQ: " . $sql);
		$array = $db->query($sql);

		if($array)
		{

			$ret = array();
			foreach($array as $row)
			{
				$obj = self::fromArray($org_id, $row);
				if($row["parent_id"] > 0 )
				{
					$obj->setParentBrand(InventoryBrand::loadById($org_id, $row["parent_id"]));
				}
				$ret[] = $obj;
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$obj->getCode(), $org_id);
				$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getInventoryBrandId(), $org_id);
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
