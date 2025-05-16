<?php
include_once 'models/filters/InventoryMasterLoadFilters.php';
include_once 'exceptions/ApiInventoryException.php';
include_once 'models/BaseModel.php';
include_once 'models/inventory/InventoryCategory.php';
include_once 'models/inventory/InventorySize.php';
include_once 'models/inventory/InventoryColor.php';
include_once 'models/inventory/InventoryBrand.php';
include_once 'models/inventory/InventoryStyle.php';

/**
 * @author class
 *
 * The defines all the inventory masters records in the DB
 * The linked table is product_management.inventory_masters
 */
class InventoryMaster extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $item_id;
	protected $item_sku;
	protected $item_ean;
	protected $price;
	protected $description;
	protected $long_description;
	protected $image_url;
	protected $in_stock;
	protected $added_on;
	protected $size;
	protected $color;
	protected $style;
	protected $category;
	
	protected $validationErrorArr;
	protected static $iterableMembers;

	protected $inventoryAttributes;

	const CACHE_KEY_PREFIX = 'INV_MASTER_ID';
	const CACHE_KEY_SKU_PREFIX = 'INV_MASTER_SKU';
	const CACHE_KEY_ATTR_PREFIX = 'INV_ITEM_ATTR_ITEM_ID';
	const CACHE_TTL = 86400; //60*60*24

	public function __construct($current_org_id, $item_id = null, $sku = null)
	{
		include_once 'models/inventory/InventoryAttribute.php';
		
		global $currentuser;
		parent::__construct($current_org_id);
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		//$this->logger = $logger;
		// current org
		//$this->current_org_id = $current_org_id;

		// db connection
		$this->db_user = new Dbase( 'product' );

		if($sku)
			$this->item_sku = $sku;
		if($item_id > 0 )
			$this->item_id = $item_id;

	}
	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"item_id",
				"item_sku",
				"item_ean",
				"price",
				"description",
				"image_url",
				"added_on",
				"in_stock",
				"long_description",
				"size",
				"color",
				"style",
				"brand",
				"category",
				"inventoryAttributes"
		);

	}

	public function getItemId()
	{
		return $this->item_id;
	}

	public function setItemId($item_id)
	{
		$this->item_id = $item_id;
	}

	public function getItemSku()
	{
		return $this->item_sku;
	}

	public function setItemSku($item_sku)
	{
		$this->item_sku = $item_sku;
	}

	public function getItemEan()
	{
		return $this->item_ean;
	}

	public function setItemEan($item_ean)
	{
		$this->item_ean = $item_ean;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function setPrice($price)
	{
		$this->price = $price;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setDescription($description)
	{
		$this->description = $description;
	}

	public function setImageUrl($imageUrl)
	{
		$this->image_url = $imageUrl;
	}
	
	public function getImageUrl()
	{
		return $this->image_url;
	}
	
	
	public function getAddedOn()
	{
		return $this->added_on;
	}

	public function setAddedOn($added_on)
	{
		$this->added_on = $added_on;
	}

	public function getValidationErrorArr()
	{
		return $this->validationErrorArr;
	}

	public function getInventoryAttributes()
	{
		return $this->inventoryAttributes;
	}
	
	public function getLongDescription()
	{
		return $this->long_description;
	}
	
	public function setLongDescription($long_description)
	{
		$this->long_description = $long_description;
	}
	
	public function setInventoryAttributes($attrs)
	{
		if(is_string($attrs))
			$attrs = $this->decodeFromString($attrs);
		
		$this->inventoryAttributes = array();
		foreach($attrs as $attr)
		{
			if($attr instanceof InventoryAttribute)
				$this->inventoryAttributes[] = $attr;
			else if(is_array($attr))
				$this->inventoryAttributes[] = InventoryAttribute::fromArray($this->current_org_id, $attr);
			else if(is_string($attr))
				$this->inventoryAttributes[] = InventoryAttribute::fromString($this->current_org_id, $attr);
		}
	}
	
	public function getInventoryAttributeValue($attr_id)
	{
		if(!$this->inventoryAttributes)
			$this->loadItemAttributes();
		
		foreach($this->inventoryAttributes as $attr)
		{
			if($attr->getInventoryAttributeId() == $attr_id)
			{
				$this->logger->debug("The requested attribute is found");
				return  $attr;
			}
		}
		
		$this->logger->debug("The attribute is not found");
		throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_ATTR_ID_PASSED);
	}
	
	public function setSize($size)
	{
		
		if($size instanceof InventorySize )
			$this->size = $size;
		else if(is_array($size))
			$this->size = InventorySize::fromArray($this->current_org_id, $size);
		else if(is_string($size))
			$this->size = InventorySize::fromString($this->current_org_id, $size);
	}
	
	public function getSize()
	{
		return $this->size;
	}
	
	public function setStyle($style)
	{
		
		if($style instanceof InventoryStyle )
			$this->style = $style;
		else if(is_array($style))
			$this->style = InventoryStyle::fromArray($this->current_org_id, $style);
		else if(is_string($style))
			$this->style = InventoryStyle::fromString($this->current_org_id, $style);
	}
	
	public function getStyle()
	{
		return $this->style;
	}
	
	public function setColor($color)
	{
		if($color instanceof InventoryColor )
			$this->color = $color;
		else if(is_array($color))
			$this->color = InventoryColor::fromArray($this->current_org_id, $color);
		else if(is_string($color))
			$this->color = InventoryColor::fromString($this->current_org_id, $color);
	}
	
	public function getColor($color)
	{
		return $this->color;
	}
	
	public function getCategory()
	{
		return $this->category;
	}
	
	public function setCategory($category)
	{
		if($category instanceof InventoryCategory )
			$this->category = $category;
		else if(is_array($category))
			$this->category = InventoryCategory::fromArray($this->current_org_id, $category);
		else if(is_string($category))
			$this->category = InventoryCategory::fromString($this->current_org_id, $category);
		
	}
	
	public function getBrand()
	{
		return $this->brand;
	}
	
	public function setBrand($brand)
	{
		if($brand instanceof InventoryBrand )
			$this->brand = $brand;
		else if(is_array($brand))
			$this->brand = InventoryBrand::fromArray($this->current_org_id, $brand);
		else if(is_string($brand))
			$this->brand = InventoryBrand::fromString($this->current_org_id, $brand);
	}
	
	public function save()
	{
// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			return;
// 		}
		
		if(isset($this->item_ean))
			$columns["item_ean"]= "'".$this->item_ean."'";
		if(isset($this->item_sku))
			$columns["item_sku"]= "'".$this->item_sku."'";
		if(isset($this->price))
			$columns["price"]= $this->price;
		if(isset($this->description))
			$columns["description"]= "'".Util::mysqlEscapeString($this->description)."'";
		if(isset($this->image_url))
			$columns["img_url"]= "'".$this->image_url."'";
		if(isset($this->size))
			$columns["size_id"] = $this->size->getInventorySizeId();
		if(isset($this->color))
			$columns["color_pallette"] = $this->color->getPallette();
		if(isset($this->style))
			$columns["style_id"] = $this->style->getInventoryStyleId();
		if(isset($this->category))
			$columns["category_id"] = $this->category->getInventoryCategoryId();
		if(isset($this->brand))
			$columns["brand_id"] = $this->brand->getInventoryBrandId();
		if(isset($this->long_description))
			$columns["long_description"] = "'".Util::mysqlEscapeString($this->getLongDescription())."'";
			
		
		// new user
		if(!$this->item_id)
		{
			$this->logger->debug("Item id is not set, so its going to be an insert query");
			$columns["added_on"]= "'".Util::getMysqlDateTime($this->added_on ? $this->added_on : 'now')."'";
			$columns["org_id"]= $this->current_org_id;

			$sql = "INSERT IGNORE INTO inventory_masters ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);

			$this->logger->debug("Return of saving the inventory masters is $newId");

			if($newId > 0)
				$this->item_id = $newId;
		}
		else
		{
			$this->logger->debug("CF id is set, so its going to be an update query");
			$sql = "UPDATE inventory_masters SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= " WHERE id = $this->item_id";
			$newId = $this->db_user->update($sql);
			if($newId)
				$newId = $this->item_id;
		}

		if($this->item_id && $this->inventoryAttributes)
		{
			$valueClause = array();
			
			foreach($this->inventoryAttributes as $attrObj)
			{
                $valueClause [] = "( $this->current_org_id, $this->item_id, "
                    .$attrObj->getInventoryAttributeId().", "
                    .$attrObj->getInventoryAttributeValue()->getInventoryAttributeValueId().","
                    ."'".$attrObj->getInventoryAttributeValue()->getValueCode()."'"." )";
			}
			if($valueClause)
			{
				try {
					$sql = "INSERT IGNORE INTO inventory_items
								(org_id, item_id, attribute_id, attribute_value_id,`value`) VALUES ";
					$sql .= implode(", " , $valueClause);
					$sql .= " ON DUPLICATE KEY UPDATE attribute_value_id = VALUES(attribute_value_id),`value` = VALUES(`value`) " ;
					$this->db_user->insert($sql);					
				} catch (Exception $e) {
					$this->logger->debug("Saving the attribute of the item has failed for item ". $this->item_id);
				}
			}				
		}

		if($this->item_id)
		{
			$cacheKeyAttr = $this->generateCacheKey(InventoryMaster::CACHE_KEY_ATTR_PREFIX, $this->item_id, $this->current_org_id);
			try{
				$this->deleteValueFromCache($cacheKeyAttr);
				$this->loadItemAttributes();
			} catch(Exception $e){
				$this->logger->debug($cacheKeyAttr." not found to delete");
			}
			
			$cacheKey = $this->generateCacheKey(InventoryMaster::CACHE_KEY_PREFIX, $this->getItemId(), $this->current_org_id);
			$cacheKeySku = $this->generateCacheKey(InventoryMaster::CACHE_KEY_SKU_PREFIX, $this->getItemSku(), $this->current_org_id);
			$this->saveToCache($cacheKey, "");
			$obj = self::loadById($this->current_org_id, $this->item_id);
			$this->saveToCache($cacheKey, $obj->toString());
			$this->saveToCache($cacheKeySku, $obj->toString());
			
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
		if($this->item_sku && strlen($this->item_sku)>30)
		{
			throw new ApiInventoryException(ApiInventoryException::SKU_LENGTH_EXCEEDED);	
		}
		
		return true;
	}


	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($org_id, $item_id)
	{
		global $logger;
		$logger->debug("Loading from based on attr id");

		if(!$item_id)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ITEM_ID_NOT_PASSED);
		}

		$cacheKey = self::generateCacheKey(InventoryMaster::CACHE_KEY_PREFIX, $item_id, $org_id);
		if(!$obj = self::loadFromCache($org_id, $cacheKey ))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");

			$filters = new InventoryMasterLoadFilters();
			$filters->item_id = $item_id;
			try{
				$array = self::loadAll($org_id, $filters, 1, 0, true);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}

			if($array)
			{
				try{
					$array[0]->loadItemAttributes();
					return $array[0];
				}catch (Exception $e){
					$logger->debug("No attributes found for the item ".$i);
					return $array[0];
				}
			}
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_ITEM_PASSED);
		}
		else
		{
			$logger->debug("Loading from the Cache was successful. returning");

			try {
				$obj->loadItemAttributes();
			} catch (Exception $e) {
				$logger->debug("No attributes found");
			}
			
			return $obj;
		}
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadBySku($org_id, $item_sku)
	{
		global $logger;
		$logger->debug("Loading from based on attr sku");
	
		if(!$item_sku)
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ITEM_ID_NOT_PASSED);
		}
		$cacheKeySku = self::generateCacheKey(InventoryMaster::CACHE_KEY_SKU_PREFIX, $item_sku, $org_id);
		if(!$obj = self::loadFromCache($org_id, $cacheKeySku))
		{
			$logger->debug("Loading from the Cache has failed, fetching from DB now");
	
			$filters = new InventoryMasterLoadFilters();
			$filters->item_sku = $item_sku;
			try{
				$array = self::loadAll($org_id, $filters, 1, 0, true);
			}catch(Exception $e){
				$logger->debug("Load from cache has failed");
			}
			if($array)
			{
				try{
					$array[0]->loadItemAttributes();
				} catch ( Exception $e){
					
				}
				return $array[0];
			}
			throw new ApiInventoryException(ApiInventoryException::FILTER_NON_EXISTING_ITEM_PASSED);
		}
		else
		{
			try {
				$obj->loadItemAttributes();
			} catch (Exception $e) {
				$logger->debug("No attributes found");
			}
			$logger->debug("Loading from the Cache was successful. returning");
			return $obj;
		}
	}
	
	
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0, $include_attrs = false)
	{
		global $logger;
		$logger->debug("Loading based on cf id");

		if(isset($filters) && !($filters instanceof InventoryMasterLoadFilters))
		{
			throw new ApiInventoryException(ApiInventoryException::FILTER_ITEM_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT
		im.id as item_id,
		im.item_sku,
		im.item_ean,
		im.price,
		im.description,
		im.img_url as image_url,
		im.long_description,
		im.added_on,
		im.is_valid as in_stock,
		im.brand_id as brand_id,
		im.size_id as size_id, 
		im.style_id as style_id,
		im.category_id as category_id,
		im.color_pallette as color_pallette
		FROM inventory_masters as im
		WHERE im.org_id = $org_id ";

		if($filters->item_id)
			$sql .= " AND im.id='".$filters->item_id."'";

		if($filters->item_sku)
			$sql .= " AND im.item_sku = '".$filters->item_sku."'";

		$sql .= " ORDER BY im.id desc ";
			
		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 20;

		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;

		$sql = $sql . " LIMIT $offset, $limit";

		$db = new Dbase('product');
		$array = $db->query($sql);

		if($array)
		{
			
			$ret = array();
			foreach($array as $row)
			{
				$obj = InventoryMaster::fromArray($org_id, $row);
				#print_r(InventorySize::loadById($org_id, $row["size_id"]));
				if($row["size_id"]>0)
					$obj->setSize(InventorySize::loadById($org_id, $row["size_id"]));
				if($row["color_id"]>0)
					$obj->setColor(InventoryColor::loadById($org_id, $row["color_id"]));
				if($row["style_id"]>0)
					$obj->setStyle(InventoryStyle::loadById($org_id, $row["style_id"]));
				if($row["category_id"]>0)
					$obj->setCategory(InventoryCategory::loadById($org_id, $row["category_id"]));
				if(strlen($row["color_pallette"])>0)
					$obj->setColor(InventoryColor::loadByPallette($row["color_pallette"]));
				if($row["brand_id"]>0)
					$obj->setBrand(InventoryBrand::loadById($org_id, $row["brand_id"]));
				try {
					if($include_attrs)
						$obj->loadItemAttributes();
				} catch (ApiInventoryException $e) {
					$logger->debug("Inventory attributes loading has failed");
				}
				$ret[] = $obj;
				$string = $obj->toString();
				$cacheKey = self::generateCacheKey(InventoryMaster::CACHE_KEY_PREFIX, $obj->getItemId(), $org_id);
				$cacheKeySku = self::generateCacheKey(InventoryMaster::CACHE_KEY_SKU_PREFIX, $obj->getItemSku(), $org_id);
				$obj->saveToCache($cacheKey, $string);
				$obj->saveToCache($cacheKeySku, $string);
			}
			$logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			return $ret;
		}
		else
		{
			throw new ApiInventoryException(ApiInventoryException::NO_ITEM_MATCHES);
		}

	}

	public function loadItemAttributes( $limit=100, $offset = 0) // $filters = null,
	{
		$this->logger->debug("Get all users based on the filters");
		
		$cacheKey = $this->generateCacheKey(InventoryMaster::CACHE_KEY_ATTR_PREFIX, $this->item_id, $this->current_org_id);

		if($str = self::getFromCache($cacheKey))
		{
			$this->logger->debug("cache has the data");
			$array = $this->decodeFromString($str);
			$ret = array();
				
			foreach($array as $row)
			{
				$obj = InventoryAttribute::fromString($this->current_org_id, $row);
				$ret[] = $obj;
				//$this->logger->debug("data from cache" . $obj->toString());
			}
			$this->setInventoryAttributes($ret);
			//$this->inventoryAttributes = $this->getInventoryAttributes();
			return $this->inventoryAttributes;
		}
		
		$sql = "SELECT
		ii.item_id as item_id,
		iia.id as inventory_attribute_id,
		iia.name as name,
		iav.id as inventory_attribute_value_id,
		iav.value_name as value_name,
		iav.value_code as value_code
		FROM inventory_item_attributes as iia 
		INNER JOIN inventory_items ii 
		ON iia.id = ii.attribute_id and iia.org_id = ii.org_id AND ii.item_id= $this->item_id
		INNER JOIN inventory_item_attribute_values as iav
		ON iav.id = ii.attribute_value_id and iav.org_id = ii.org_id
		WHERE iia.org_id = $this->current_org_id
		";

		//if($filters->item_id)

// 		$sql .= " AND ii.item_id= ".$this->item_id;
// 		if($filters->inventory_attribute_id)
// 			$sql .= " AND iia.id= ".$filters->inventory_attribute_id;
// 		if($filters->inventory_attribute_value_id)
// 			$sql .= " AND iav.id= ".$filters->inventory_attribute_value_id;

		$sql .= " ORDER BY ii.id desc, iia.id asc ";

		if($limit>0 && $limit<1000)
			$limit = intval($limit);
		else
			$limit = 100;

		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;

		$sql = $sql . " LIMIT $offset, $limit";

		// TODO: add more filters here
		$array = $this->db_user->query($sql);
		if($array)
		{

			$ret = array(); $cacheStringArr = array();
			foreach($array as $row)
			{
				$obj = InventoryAttribute::fromArray($this->current_org_id, $row);
				$ret[] = $obj;
				$obj->setInventoryAttributeValue($row);
				$cacheStringArr[$obj->getInventoryAttributeId()] = $obj->toString();
			}
			
			if($cacheStringArr)
			{
				$this->logger->debug("saving the attributes to cache");
				$str = $this->encodeToString($cacheStringArr);
				
				$cacheKey = $this->generateCacheKey(InventoryMaster::CACHE_KEY_ATTR_PREFIX, $this->item_id, $this->current_org_id);
				$this->saveToCache($cacheKey, $str);
			}
			
					
			$this->logger->debug("Successfully loaded the data and returned ". count($array). " rows");
			$this->setInventoryAttributes($ret);
			$this->inventoryAttributes = $this->getInventoryAttributes();
			return $this->inventoryAttributes;

		}
		$this->logger->debug("No matches found");
		
		throw new ApiInventoryException(ApiInventoryException::NO_ATTR_MATCHES);
		
	}

}
