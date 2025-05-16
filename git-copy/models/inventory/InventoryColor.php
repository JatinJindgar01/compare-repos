<?php
include_once 'models/filters/InventoryAttributeGenericLoadFilters.php';
include_once 'exceptions/ApiInventoryException.php';
include_once 'models/BaseModel.php';
/**
 * @author class
 *
 * The defines all the inventory colors records in the DB. The table is more of a meta data
 * The linked table is product_management.colors
 */
class InventoryColor extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $pallette;
	protected $name;

	protected $validationErrorArr;

	protected static $iterableMembers;
	const CACHE_KEY_PREFIX = 'INV_COLOR_PAL';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($current_org_id, $inventory_attribute_id = null)
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
				"pallette",
				"name",
		);
	}
	public function getPallette()
	{
		return $this->pallette;
	}

	public function setPallette($pallette)
	{
		$this->pallette = $pallette;
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
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
		/*
		 if(!$this->validate())
		 {
		 $this->logger->debug("Validation has failed, returning now");
		 throw new ApiInventoryException(ApiInventoryException::VALIDATION_FAILED);
		 }
		 if(isset($this->pallette))
			$columns["pallette"]= "'".addslashes($this->pallette)."'";
			if(isset($this->name))
			$columns["name"]= "'".addslashes($this->name)."'";

			// new user
			if(!$this->inventory_attribute_id)
			{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["org_id"]= $this->current_org_id;
			$columns["added_by"]= $this->current_user_id;
				
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
			}

			if($newId)
			{
			return;
			}
			else
			{
			throw new ApiInventoryException(ApiInventoryException::SAVING_DATA_FAILED);
			}
			*/
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
	 * @param unknown_type $pallette
	 * @throws ApiInventoryException
	 * @return InventoryColor
	 */
	public static function loadByPallette( $pallette)
	{
		global $logger;
		$logger->debug("Loading from based on attr id");

//		if(!$pallette )
//		{
//			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_ID_NOT_PASSED);
//		}

		//		$key = self::generateCacheKey(self::CACHE_KEY_PREFIX,$pallette);
//		$obj = $this->loadFromCache($key); 
//		if(!$obj)
		//{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryAttributeGenericLoadFilters();
			$filters->id = $pallette;
			try{
				$array = self::loadAll($filters, 1);

			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				return $array[0];
			}
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_ATTR_ID_PASSED);

		//}
		//return $obj;
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $org_id
	 * @param unknown_type $filters
	 * @param unknown_type $limit
	 * @param unknown_type $offset
	 * @throws ApiInventoryException
	 * @return ArrayObject(InventoryColor)
	 *
	 * Load all the data into object based on the filters being passed.
	 * It should optionally decide whether entire dependency tree is required or not
	 */
	public static function loadAll($filters = null,  $limit=200, $offset = 0)
	{
		global $logger;
		if(isset($filters) && !($filters instanceof InventoryAttributeGenericLoadFilters))
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ATTR_INVALID_OBJECT_PASSED);
		}


		$logger->debug("Get all users based on the filters");

		$sql = "SELECT
		c.pallette as pallette,
		c.name as name
		";		
		$sql .= " FROM colors c ";

		$sql .= " WHERE 1 ";

		$idenitifersql = array();
		if($filters->id)
			$sql .= " AND c.pallette= ".$filters->id;

		$sql .= " ORDER BY c.pallette asc ";

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
				$obj = InventoryColor::fromArray($org_id, $row);
				
				$ret[] = $obj;

			}

			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;

		}

		throw new ApiInventoryException(ApiInventoryException::NO_ATTR_MATCHES);
		$logger->debug("No matches found");
	}

}
