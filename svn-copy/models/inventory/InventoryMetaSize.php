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
class InventoryMetaSize extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;

	protected $inventory_meta_size_id;
	protected $code;
	protected $name;
	protected $description;
	protected $size_family;
	protected $type;
	protected $parent_meta_size;
	
	protected $validationErrorArr;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'INV_META_SIZE_ID';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($code = null, $inventory_meta_size_id = null)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		$this->logger = $logger;
		
		if($code)
			$this->code = $code;
		if($inventory_meta_size_id)
			$this->inventory_meta_size_id = $inventory_meta_size_id;

		// db connection
		$this->db = new Dbase( 'product' );
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"inventory_meta_size_id",
				"code",
				"name",
				"description",
				"size_family",
				"type",
				"parent_meta_size"
				);
	}
	public function getInventoryMetaSizeId()
	{
	    return $this->inventory_meta_size_id;
	}

	public function setInventorySizeId($inventory_meta_size_id)
	{
	    $this->inventory_meta_size_id = $inventory_meta_size_id;
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
	
	public function setType($type)
	{
		$this->type = $type;
	}
	
	public function getType($type)
	{
		return $this->type;
	}
	
	public function setParentMetaSize($parent_meta_size){
		if($parent_meta_size instanceof InventoryMetaSize )
			$this->parent_meta_size = $parent_meta_size;
		else if(is_array($parent_meta_size))
			$this->parent_meta_size = InventoryMetaSize::fromArray(null, $parent_meta_size);
		else if(is_string($parent_meta_size))
			$this->parent_meta_size = InventoryMetaSize::fromString(null, $parent_meta_size);
	}
	
	public function getParentMetaSize(){
		return $this->parent_meta_size; 
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
 		if(!$this->validate())
 		{
 			$this->logger->debug("Validation has failed, returning now");
 			throw new ApiInventoryException(ApiInventoryException::VALIDATION_FAILED);
 		}
		
		if(isset($this->code))
			$columns["code"]= "'".addslashes($this->code)."'";
		if(isset($this->description))
			$columns["description"]= "'".addslashes($this->description)."'";
		if(isset($this->name))
			$columns["name"] = "'".addslashes($this->name)."'";
		if(isset($this->parent_meta_size))
			$columns["parent_meta_size_id"]= "'".$this->parent_meta_size->getParentMetaSizeId()."'";

		// new row
		if(!$this->inventory_meta_size_id)
		{
			$this->logger->debug("Inventory meta size id is not set, so its going to be an insert query");
			
			$sql = "INSERT IGNORE INTO meta_sizes ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db->insert($sql);

			$this->logger->debug("Return of saving the inventory masters is $newId");

			if($newId > 0)
				$this->inventory_meta_size_id = $newId;
		}
		else
		{
			$this->logger->debug("inventory meta id is set, so its going to be an update query");
			$sql = "UPDATE meta_sizes SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->inventory_meta_size_id";
			$newId = $this->db->update($sql);
			if($newId)
				$newId = $this->inventory_meta_size_id;
		}

		if($newId)
			$this->inventory_meta_size_id = $newId;
		
		if($this->inventory_meta_size_id>0){
			$cacheKey = $this->generateCacheKey(self::CACHE_KEY_PREFIX, $this->inventory_meta_size_id, null);
			//$cacheKeyCode = $this->generateCacheKey(self::CACHE_KEY_PREFIX, $this->code.$this->size_family.$this->type, null);
			try{
				$this->deleteValueFromCache($cacheKey);
				//$this->deleteValueFromCache($cacheKeyCode);
			} catch(Exception $e){
				$this->logger->debug($cacheKey." not found to delete");
			}
			
			$obj = self::loadById($this->inventory_meta_size_id);
			$this->saveToCache($cacheKey, $obj->toString());
			//$this->saveToCache($cacheKeyCode, $obj->toString());
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
		if(!$this->code){
			throw new ApiInventoryException(ApiInventoryException::CODE_NOT_PASSED);
		}
		if(!$this->size_family){
			throw new ApiInventoryException(ApiInventoryException::SIZE_FAMILY_NOT_PASSED);
		}
		if(!$this->type){
			throw new ApiInteractionException(ApiInventoryException::TYPE_NOT_PASSED);
		}
		if(!$this->parent_meta_size->getInventoryMetaSizeId()){
			throw new ApiInventoryException(ApiInventoryException::PARENT_ID_NOT_PASSED);
		}
		return true;
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($inventory_meta_size_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on meta size id");

		if(!$inventory_meta_size_id)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ID_NOT_PASSED);
		}
		
		$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX, $inventory_meta_size_id, null);
		$obj = self::loadFromCache($cacheKey); 
		if(!$obj)
		//if(!$this->loadFromCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$this->inventory_attribute_id))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeGenericLoadFilters();
			$filters->id = $inventory_meta_size_id;
			try{
				$array = self::loadAll( $filters, 1);
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
			$logger->debug("Cacheed obj returned");
			return $obj;
		}
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadByCode($code = null)
	{
		global $logger;
		$logger->debug("Loading from based on meta size code");
	
		if(!$code)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_CODE_NOT_PASSED);
		}
		
		$cacheKey = $this->generateCacheKey(self::CACHE_KEY_PREFIX, $code, null);
		if(!$this->loadFromCache(null, $cachekey))
		//if(!$this->loadFromCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$this->inventory_attribute_id))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeGenericLoadFilters();
			$filters->code = $code;
			try{
				$array = self::loadAll($filters, 1);
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
			//return from cache
		}
	}
	
	
	/*
	public static function loadForOrg($org_id)
	{
		global $logger;
		$cacheKey = self::generateCacheKey(InventoryAttribute::CACHE_KEY_PREFIX, "", $org_id);
		
		if($str = self::getFromCache($cacheKey))
		{
			$logger->debug("Reading from cache is successful");
			$array = self::decodeFromString($str);
			foreach($array as $row)
			{
				$obj = InventoryAttribute::fromString($org_id, $row);
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
	*/
	
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
	public static function loadAll($filters = null, $limit=200, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof InventoryAttributeGenericLoadFilters))
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_INVALID_OBJECT_PASSED);
		}

		$logger->debug("Get all users based on the filters");
		
		$sql = "SELECT m.id as inventory_meta_size_id,
		m.code as code,
		m.name as name,
		m.description as description,
		m.size_family as size_family,
		m.type as type,
		m.parent_id as parent_meta_size";
		
		$sql .= " FROM meta_sizes as m ";
		$sql .= " WHERE '1' ";
		
		if($filters->id)
			$sql .= " AND m.id = ".$filters->id;
		if($filters->code)
			$sql .= " AND m.code = '".$filters->code."'";
		if($filters->type)
			$sql .= " AND m.type = '".$filters->type."'";
		if($filters->size_family)  
			$sql .= " AND m.size_family = '".$filters->size_family."'";
			
		if($filters->parent_id)
			$sql .= " AND m.parent_id = ".$filters->parent_id;

		$sql .= " ORDER BY m.code asc ";
		
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
				$obj = InventoryMetaSize::fromArray(null, $row);
				if($row["parent_meta_size"] > 0 )
				{
					$obj->setParentMetaSize(self::loadById($row["parent_meta_size"]));
					//$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE,$obj->getCode().$obj->getType().$obj->getSizeFamily(), null);
					$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getInventoryMetaSizeId(), null);
					//$obj->saveToCache($key, $obj);
					$obj->saveToCache($key2, $obj);	
				}
				
				$ret[] = $obj;

			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiInventoryException(ApiInventoryException::NO_META_SIZE_MATCHES);
		$logger->debug("No matches found");
	}

}
