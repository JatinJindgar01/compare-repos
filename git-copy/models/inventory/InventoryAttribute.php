<?php
include_once 'models/filters/InventoryAttributeLoadFilters.php';
include_once 'exceptions/ApiInventoryException.php';
include_once 'models/BaseModel.php';
/**
 * @author class
 *
 * The defines all the inventory attribues records in the DB. The table is more of a meta data
 * The linked table is user_management.inventory_item_attributes
 */
class InventoryAttribute extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $inventory_attribute_id;
	protected $name;
	protected $is_enum;
	protected $extraction_rule_type;
	protected $extraction_rule_data;
	protected $type;
	protected $is_soft_enum;
	protected $use_in_dump;
	protected $default_attribute_value_id;
	
	protected $inventoryAttributeValue;

	protected $validationErrorArr;
	
	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'INV_ATTR_ID';
	const CACHE_KEY_PREFIX_NAME = 'INV_ATTR_NAME';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($current_org_id, $inventory_attribute_id = null)
	{
		include_once 'models/inventory/InventoryAttributeValue.php';
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		$this->logger = $logger;

		// current org
		$this->current_org_id = $current_org_id;
		if($inventory_attribute_id > 0 )
			$this->inventory_attribute_id = $inventory_attribute_id;

		// db connection
		$this->db_user = new Dbase( 'product' );
	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"inventory_attribute_id",
				"name",
				"label",
				"is_enum",
				"extraction_rule_type",
				"extraction_rule_data",
				"type",
				"is_soft_enum",
				"use_in_dump",
				"default_attribute_value_id",
				"inventoryAttributeValue",
				);
	}
	
	public function getInventoryAttributeId()
	{
		return $this->inventory_attribute_id;
	}

	public function setInventoryAttributeId($inventory_attribute_id)
	{
		$this->inventory_attribute_id = $inventory_attribute_id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function getLabel()
	{
		return $this->label;
	}
	
	public function setLabel($label)
	{
		$this->label = $label;
	}

	public function getIsEnum()
	{
		return $this->is_enum;
	}

	public function setIsEnum($is_enum)
	{
		$this->is_enum = $is_enum;
	}

	public function getExtractionRuleType()
	{
		return $this->extraction_rule_type;
	}

	public function setExtractionRuleType($extraction_rule_type)
	{
		$this->extraction_rule_type = $extraction_rule_type;
	}

	public function getExtractionRuleData()
	{
		return $this->extraction_rule_data;
	}

	public function setExtractionRuleData($extraction_rule_data)
	{
		$this->extraction_rule_data = $extraction_rule_data;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	public function getIsSoftEnum()
	{
		return $this->is_soft_enum;
	}

	public function setIsSoftEnum($is_soft_enum)
	{
		$this->is_soft_enum = $is_soft_enum;
	}

	public function getImageUrl()
	{
		return $this->image_url;
	}
	
	public function setUseInDump($use_in_dump)
	{
		$this->use_in_dump = $use_in_dump;
	}

	public function getDefaultAttributeValueId()
	{
		return $this->default_attribute_value_id;
	}

	public function setDefaultAttributeValueId($default_attribute_value_id)
	{
		$this->default_attribute_value_id = $default_attribute_value_id;
	}

	public function getValidationErrorArr()
	{
		return $this->validationErrorArr;
	}

	public function getInventoryAttributeValue()
	{
		return $this->inventoryAttributeValue; 
	}

	public function getInventoryAttributeValueString()
	{
		if($this->getInventoryAttributeValue())
			return $this->getInventoryAttributeValue()->getValueName();
		else
			return "";
	}
	
	// it set the attr field, can be passed as string, object or string
	public function setInventoryAttributeValue($obj)
	{
		if($obj instanceof InventoryAttributeValue)
			$this->inventoryAttributeValue = $obj;
		else if(is_array($obj))
			$this->inventoryAttributeValue = InventoryAttributeValue::fromArray($this->current_org_id, $obj);
		else if(is_string($obj))
			$this->inventoryAttributeValue = InventoryAttributeValue::fromString($this->current_org_id, $obj);
	}
	
	public function save()
	{
 		if(!$this->validate())
 		{
 			$this->logger->debug("Validation has failed, returning now");
 			throw new ApiInventoryException(ApiInventoryException::VALIDATION_FAILED);
 		}

		if(isset($this->name))
			$columns["name"]= "'".$this->name."'";
		if(isset($this->label))
			$columns["label"] = "'".$this->label."'";
		if(isset($this->extraction_rule_type))
			$columns["extraction_rule_type"]= "'".$this->extraction_rule_type."'";
		if(isset($this->extraction_rule_data))
			$columns["extraction_rule_data"]= "'".$this->extraction_rule_data."'";
		if(isset($this->is_enum))
			$columns["is_enum"]= $this->is_enum ? 1 : 0;
		if(isset($this->type))
			$columns["type"]= "'".$this->type ."'";
		if(isset($this->is_soft_enum))
			$columns["is_soft_enum"]= $this->is_soft_enum ? 1 : 0;
		if(isset($this->use_in_dump))
			$columns["use_in_dump"]= $this->use_in_dump ? 1 : 0;
		if(isset($this->default_attribute_value_id))
			$columns["default_attribute_value_id"]= $this->default_attribute_value_id;

		// new user
		if(!$this->inventory_attribute_id)
		{
			$this->logger->debug("Item id is not set, so its going touser_management. be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"] = $this->current_user_id;
			$columns["added_on"]= "NOW()";

			$sql = "INSERT IGNORE INTO inventory_item_attributes ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);

			$this->logger->debug("Return of saving the inventory masters is $newId");

			if($newId > 0)
				$this->inventory_attribute_id = $newId;
		}
		else
		{
			$this->logger->debug("unvetory attrs id is set, so its going to be an update query");
			$sql = "UPDATE inventory_item_attributes SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->inventory_attribute_id";
			$newId = $this->db_user->update($sql);
			if($newId)
				$newId = $this->inventory_attribute_id;
		}

		if($newId)
		{
			$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_NAME,$this->name, $this->current_org_id);
			$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$this->inventory_attribute_id, $this->current_org_id);
			$this->deleteValueFromCache($key);
			$this->deleteValueFromCache($key2);
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
		
		if(!$this->name && !$this->inventory_attribute_id){
			throw new ApiInventoryException(ApiInventoryException::NAME_NOT_PASSED);
		}
		
		if($this->name && strlen($this->name)>30)
			throw new ApiInventoryException(ApiInventoryException::CODE_LENGTH_EXCEEDED);
        
        if(!in_array($this->is_enum, array("0", "1")))
		{
			throw new ApiInventoryException(ApiInventoryException::INVALID_ATTRIBUTE_ENUM);
		}

        if($this->extraction_rule_type && !in_array($this->extraction_rule_type, array('UPLOAD','POS','REGEX','USERDEF')))
        {
                throw new ApiInventoryException(ApiInventoryException::INVALID_ATTRIBUTE_EXTRACTION_RULE_TYPE);
        }

        if($this->type && !in_array($this->type, array('String','Int','Boolean','Double')))
        {
                throw new ApiInventoryException(ApiInventoryException::INVALID_ATTRIBUTE_TYPE);
                //$this->type = 'String';
        }
		
		if(!$this->extraction_rule_type)
		{
			$this->extraction_rule_type = 'UPLOAD';
		}
		
		if(!$this->type)
		{
			$this->type = 'String';
		}

		return true;
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($org_id, $inventory_attribute_id = null)
	{
		global $logger; 
		$logger->debug("Loading from based on attr id");

		if(!$inventory_attribute_id)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_ID_NOT_PASSED);
		}
		
		$cachekey = self::generateCacheKey(self::CACHE_KEY_PREFIX, $inventory_attribute_id, $org_id);	
		$obj = self::loadFromCache($org_id, $cachekey);
		if(!self::loadFromCache($org_id, $cachekey))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeLoadFilters();
			$filters->inventory_attribute_id = $inventory_attribute_id;
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
		else{
			$logger->debug("Loading from cache");
			return $obj;
		}
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadByName($org_id, $inventory_attribute_name = null)
	{
		global $logger;
		$logger->debug("Loading from based on attr name");
	
		if(!$inventory_attribute_name)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_NAME_NOT_PASSED);
		}
		
		$cachekey = self::generateCacheKey(self::CACHE_KEY_PREFIX_NAME, $inventory_attribute_name, $org_id);	
		$obj = self::loadFromCache($org_id, $cachekey);
		if(!self::loadFromCache($org_id, $cachekey))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");
	
			$filters = new InventoryAttributeLoadFilters();
			$filters->inventory_attribute_name = $inventory_attribute_name;
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
		else{
			$logger->info("Loading from cache successful");
			return $obj;
		}
		
	}
	

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
			$cacheStringArr[$obj->getInventoryAttributeId()] = $obj->toString();
		}
		
		if($cacheStringArr)
		{
			$logger->debug("saving the attributes to cache");
			$str = self::encodeToString($cacheStringArr);
			$obj->saveToCache($cacheKey, $str);
		}
		return $attrs;
	}
	/*
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	*/
	public static function loadAll($org_id, $filters = null, $limit=200, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof InventoryAttributeLoadFilters))
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_INVALID_OBJECT_PASSED);
		}

		$includesAttrsValues = $filters->item_id > 0  ? true : false ;

		$logger->debug("Get all users based on the filters");

		$sql = "SELECT
		iia.id as inventory_attribute_id,
		iia.name as name,
		iia.label as label,
		iia.is_enum as is_enum,
		iia.extraction_rule_type as extraction_rule_type,
		iia.extraction_rule_data as extraction_rule_data,
		iia.`type` as `type`,
		iia.is_soft_enum as is_soft_enum,
		iia.use_in_dump as use_in_dump,
		iia.default_attribute_value_id as default_attribute_value_id
		";		
		
		if($includesAttrsValues)
		{
			$sql .= " ,iav.id as inventory_attribute_value_id,
			iav.value_name as value_name,
			iav.value_code as value_code ";
		}
		
		$sql .= " FROM inventory_item_attributes iia ";
		
		if($includesAttrsValues)
		{
			$sql .= " LEFT JOIN  inventory_items ii
					 ON ii.org_id = iia.org_id AND ii.item_id = $filters->item_id and ii.attribute_id=iia.id
					LEFT JOIN inventory_item_attribute_values iav 
					 ON iia.id = iav.attribute_id and iia.org_id = iav.org_id and iav.id = ii.attribute_value_id";
		}
		$sql .= " WHERE iia.org_id = $org_id ";

		$idenitifersql = array();
		if($filters->inventory_attribute_id)
			$sql .= " AND iia.id= ".$filters->inventory_attribute_id;
		if($filters->value_code)
			$sql .= " AND iia.value_code= '".$filters->value_code."'";
		if($filters->value_name)
			$sql .= " AND iav.value_name= '".$filters->value_name."'";
		if($filters->inventory_attribute_name)
			$sql .= " AND iia.name= '".$filters->inventory_attribute_name."'";

		$sql .= " ORDER BY iia.id desc ";
		
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 100;

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
				$obj = InventoryAttribute::fromArray($org_id, $row);
				
				if($includesAttrsValues)
				{
					$obj->setInventoryAttributeValue($row);	
				}
				$ret[] = $obj;
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX_NAME,$obj->getName(), $org_id);
				$key2 = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getInventoryAttributeId(), $org_id);
				$obj->saveToCache($key, $obj);
				$obj->saveToCache($key2, $obj);

				// adding to cache
				//$this->saveToCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$obj->getUserId(), $obj->toString());
			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiInventoryException(ApiInventoryException::NO_ATTR_MATCHES);
		$logger->debug("No matches found");
	}

}
