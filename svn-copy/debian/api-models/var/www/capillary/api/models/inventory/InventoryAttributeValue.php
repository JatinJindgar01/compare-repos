<?php
include_once 'models/filters/InventoryAttributeValueLoadFilters.php';
include_once 'exceptions/ApiInventoryException.php';
include_once 'models/BaseModel.php';
/**
 * @author class
 *
 * The defines all the inventory attribues records in the DB. The table is more of a meta data
 * The linked table is user_management.inventory_item_attribute_values
 */
class InventoryAttributeValue extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $inventory_attribute_id;
	protected $inventory_attribute_value_id;
	protected $value_name;
	protected $value_code;
	protected $inventory_attribute_name;
	protected $validationErrorArr;
	

	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'INV_ATTR_VALUE_ID';

	public function __construct($current_org_id, $inventory_attribute_value_id)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		// setting the loggers
		$this->logger = &$logger;

		// current org
		$this->current_org_id = $current_org_id;
		if($inventory_attribute_value_id> 0)
			$this->inventory_attribute_value_id = $inventory_attribute_value_id;

		// db connection
		$this->db_user = new Dbase( 'product' );

	}
	
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"inventory_attribute_id",
				"inventory_attribute_value_id",
				"value_name",
				"value_code",
				"inventory_attribute_name",
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

	public function getInventoryAttributeValueId()
	{
		return $this->inventory_attribute_value_id;
	}

	public function setInventoryAttributeValueId($inventory_attribute_value_id)
	{
		$this->inventory_attribute_value_id = $inventory_attribute_value_id;
	}

	public function getValueName()
	{
		return $this->value_name;
	}

	public function setValueName($value_name)
	{
		$this->value_name = $value_name;
	}

	public function getValueCode()
	{
		return $this->value_code;
	}

	public function setValueCode($value_code)
	{
		$this->value_code = $value_code;
	}
	
	public function setInventoryAttributeName( $inventory_attribute_name )
	{
		$this->inventory_attribute_name = $inventory_attribute_name;
	}
	
	public function getInventoryAttributeName()
	{
		return $this->inventory_attribute_name;
	}

	public function getValidationErrorArr()
	{
		return $this->validationErrorArr;
	}
	
	public function save()
	{
// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			throw new ApiInventoryException(ApiInventoryException::VALIDATION_FAILED);
// 		}
		if(isset($this->inventory_attribute_id))
			$columns["attribute_id"]= $this->inventory_attribute_id;
		if(isset($this->value_name) &&  $this->value_name)
			$columns["value_name"]= "'".$this->value_name."'";
		else 
			throw new ApiInventoryException(ApiInventoryException::FILTER_VALUE_NAME_NOT_PASSED);
		if(isset($this->value_code) && $this->value_code)
			$columns["value_code"]= "'".$this->value_code."'";
		else 
			throw new ApiInventoryException(ApiInventoryException::FILTER_VALUE_CODE_NOT_PASSED);

		// new user
		if(!$this->inventory_attribute_value_id)
		{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"] = $this->current_user_id;
			$columns["added_on"]= "NOW()";

			$sql = "INSERT IGNORE INTO inventory_item_attribute_values ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);

			$this->logger->debug("Return of saving the inventory masters is $newId");

			if($newId > 0)
				$this->inventory_attribute_value_id = $newId;
		}
		else
		{
			$this->logger->debug("unvetory attrs id is set, so its going to be an update query");
			$sql = "UPDATE inventory_item_attribute_values SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= "WHERE id = $this->inventory_attribute_value_id";
			$newId = $this->db_user->update($sql);
			if($newId)
				$newId = $this->inventory_attribute_value_id;
		}

		if($newId)
		{
			$key = self::generateCacheKey(self::CACHE_KEY_PREFIX,$this->inventory_attribute_value_id, $this->current_org_id);
			$this->deleteValueFromCache($key);
			return true;
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
		if(!$this->value_name)
		{
			throw new ApiInventoryException(ApiInventoryException::VALUE_NAME_NOT_PASSED);
		}
		
		return true;
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($org_id, $inventory_attribute_value_id)
	{
		global $logger;
		$logger->debug("Loading from based on attr value id");

		if(!$inventory_attribute_value_id)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_VALUE_ID_NOT_PASSED);
		}
		$cachekey = self::generateCacheKey(self::CACHE_KEY_PREFIX, $inventory_attribute_value_id, $org_id);	
		$obj = self::loadFromCache($org_id, $cachekey);
		if(!self::loadFromCache($org_id, $cachekey))
		//if(!$this->loadFromCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$this->inventory_attribute_id))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeValueLoadFilters();
			$filters->inventory_attribute_value_id = $inventory_attribute_value_id;
			try{
				$array = self::loadAll($org_id, $filters, 1);

			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];

			}
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_VALUE_ID_PASSED);

		}
		else{
			$logger->debug("Loading from cache successful");
			return $obj;
		}
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadByAttrIdName($org_id, $attr_id, $name)
	{
		global $logger;
		$logger->debug("Loading from based on attr id/ value");
	
		if(!$attr_id || !$name)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_VALUE_ID_NOT_PASSED);
		}
	
		//if(!$this->loadFromCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$this->inventory_attribute_id))
		{
			//$logger->debug("Loading from the Cache has failed, fetching from DB now");
	
			$filters = new InventoryAttributeValueLoadFilters();
			$filters->inventory_attribute_id = $attr_id;
			$filters->value_name = $name;
			try{
				$array = self::loadAll($org_id, $filters, 1);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}
	
			if($array)
			{
				return $array[0];
	
			}
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_VALUE_ID_PASSED);
	
		}
	}
	
	/*
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof InventoryAttributeValueLoadFilters))
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_VALUE_INVALID_OBJECT_PASSED);
		}


		$logger->debug("Get all users based on the filters");

		$sql = "SELECT
		iav.id as inventory_attribute_value_id,
		iav.attribute_id as inventory_attribute_id,
		iav.value_name as value_name,
		iav.value_code as value_code,
		iia.name as inventory_attribute_name
		FROM inventory_item_attribute_values iav
		INNER JOIN inventory_item_attributes as iia
		ON iia.id = iav.attribute_id and iia.org_id = iav.org_id
		WHERE iav.org_id = $org_id
		";

		$idenitifersql = array();
		if($filters->inventory_attribute_id)
			$sql .= " AND iav.attribute_id= ".$filters->inventory_attribute_id;
		if($filters->inventory_attribute_value_id)
			$sql .= " AND iav.id= ".$filters->inventory_attribute_value_id;
		if($filters->value_name)
			$sql .= " AND iav.value_name= '".$filters->value_name."'";
		if($filters->value_code)
			$sql .= " AND iav.value_code= '".$filters->value_code."'";

		$sql .= " ORDER BY iav.id desc ";

		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 200;

		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;

		$sql = $sql . " LIMIT $offset, $limit";

		// TODO: add more filters here
		$db = new Dbase('product');
		$array = $db->query($sql);

		if($array)
		{

			$ret = array();
			foreach($array as $row)
			{
				$obj = InventoryAttributeValue::fromArray($org_id, $row);
				$ret[] = $obj;
				$key = self::generateCacheKey(self::CACHE_KEY_PREFIX,$obj->getInventoryAttributeValueId(), $org_id);
				$obj->saveToCache($key, $obj);
				
				// adding to cache
				//$this->saveToCache(LoyaltyCustomer::CACHE_KEY_PREFIX.$obj->getUserId(), $obj->toString());
			}
				
			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiInventoryException(ApiInventoryException::NO_VALUE_MATCHES);
		$logger->debug("No matches found");

	}


}
