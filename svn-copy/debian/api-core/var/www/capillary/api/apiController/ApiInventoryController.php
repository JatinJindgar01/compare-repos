<?php 

include_once 'apiController/ApiBaseController.php';
include_once 'base_model/class.OrgRole.php';
include_once 'apiModel/class.ApiOrganizationModelExtension.php';
include_once 'submodule/inventory.php';
require_once 'models/inventory/InventoryMaster.php';
require_once 'models/inventory/InventoryMetaSize.php';
require_once 'models/inventory/InventoryAttribute.php';
require_once 'models/inventory/InventoryAttributeValue.php';

/**
 * The inventory controller. Does all handling of the inventory
 * information. 
 * 
 * @author pigol
 */
class ApiInventoryController extends ApiBaseController{
	
	private $inventory_module; //inventory sub module getting included here	
	
	public function __construct(){

		parent::__construct();
		$this->inventory_module = new InventorySubModule();
	}

	public function load(){
		
	}
	
	/**
	 * Returns the Inventory Attributes configured for the 
	 * organization
	 * 
	 * @params nil
	 * @return Array of Inventory Item Details
	 * @author pigol
	 */
	
	public function getInventoryAttributesForDisplay(){
		$inventory = new InventorySubModule();
		return $inventory->getNoOfAttributeValues();		
	}
	
	/**
	 * Returns the details of inventory item imports
	 * 
	 * @params nil
	 * @return Array of InventoryItemImportDetails
	 * @author pigol
	 * 
	 */
	
	public function getInventoryItemImportDetails(){
		$inventory = new InventorySubModule();
		return $inventory->getNoOfInventoryItemImportsByDate();
	}

	/**
	 * Returns the details of bill line item stats
	 * 
	 * @params nil
	 * @return array of inventory line item stats
	 * @author pigol	
	 * 
	 */

	public function getInventoryBillLineItemStats(){
		$inventory = new InventorySubModule();
		return $inventory->getBillLineItemsRefferenceStats();
	}


	public function syncInventoryWithLineItems()
	{
		$org_id = $this->currentorg->org_id;
		$this->logger->debug("Doing Inventory Sync for $org_id");
				
		$temp_table = "temp_lbl_" . $org_id . "_" . time();
		
		$this->logger->debug("Creating a temporary table $temp_table");

		$sql = "
				  CREATE TABLE $temp_table(	
						lbl_id BIGINT NOT NULL,
						org_id INT NOT NULL,
						description varchar(255) NOT NULL,
						inv_item_code varchar(30) NOT NULL,
						PRIMARY KEY (lbl_id),
						INDEX `desc_index` (`description`)	
				  )  ENGINE=InnoDB DEFAULT CHARSET=utf8;	
			   ";
		
		$db = new Dbase('users');
		$db->update($sql);
		
		$this->logger->debug("Temporary table $temp_table created");
		
		$sql = "
				  INSERT INTO $temp_table(lbl_id, org_id, description) 	
				  SELECT lbl.id, lbl.org_id, lbl.description FROM loyalty_bill_lineitems lbl, loyalty_log ll
				  WHERE lbl.org_id = ll.org_id AND lbl.loyalty_log_id = ll.id AND lbl.org_id = $org_id 
				  AND (item_code IS NULL OR item_code = ''); 
				";
	
		$this->logger->debug("Inserting records with null item codes");
		$db->insert($sql);
		
		$sql = "
				 UPDATE $temp_table t, inventory_masters ims SET t.inv_item_code = ims.item_sku WHERE ims.org_id = t.org_id
				 AND ims.description = t.description AND ims.org_id = $org_id
			   ";
		
		$this->logger->debug("Updating the item code for the bill lineitems");
		
		$db->update($sql);
		
		$sql = "
				  UPDATE loyalty_bill_lineitems lbl, $temp_table t 
				  SET lbl.item_code = t.inv_item_code 
				  WHERE lbl.id = t.lbl_id AND lbl.org_id = t.org_id		
			   ";
		
		$this->logger->debug("Updating the item codes from the temporary table");
		
		$db->update($sql);
		
		$rows_affected = $db->getAffectedRows();
		
		$this->logger->debug("Number of rows affected: $rows_affected, Dropping the temporary table");
		
		$sql = "DROP TABLE $temp_table";
		
		$db->update($sql);
		
		return $rows_affected;
		
	}	
	
	/**
	 * sets the top item for the org with index $index 
	 * as invalid
	 */
	public function updateValidityOfTopItem( $index ){
		
		$org_id = $this->currentorg->org_id;
		$this->logger->debug("Updating  $org_id");
		
		$sql = "
				  UPDATE org_top_items
				  SET
				      org_top_items.is_valid = 0
				  WHERE org_top_items.id = $index
				";

		$db = new Dbase('users');
		$result = $db->update($sql);
		$this->logger->debug("Number of rows affected: $rows_affected");
		return $result;
	}
	
	/*
	 * Checks whether the Item belongs to current organization
	 * and return the id from inventory_masters
	 */
	public function getItemIdForSku($item_Sku){
		
		$db = new Dbase('users');
		$user_id = $this->currentuser->user_id;
		$org_id = $this->org_id;
		
		$sql = "
				SELECT id FROM
				inventory_masters WHERE
				inventory_masters.item_sku = '$item_Sku'
				AND inventory_masters.org_id = '$org_id'
				";
		
		$result = $db->query_firstrow($sql);
		return $result;
	}
	
	/**
	 * Inserts the top items for the org in the org_top_items
	 * table and returns the top items after that. 
	 * 
	 * @param unknown_type $item_sku
	 * @param unknown_type $priority
	 * @return boolean|multitype:
	 */
	
	public function getTopForOrg($item_sku, $priority){
		
		$db = new Dbase('users');
		$user_id = $this->currentuser->user_id;
		$org_id = $this->org_id;
		$this->logger->debug("Getting top items for $org_id");
		$is_valid = 1;
		
		$timezone = StoreProfile::getById($this->currentuser->user_id)->getStoreTimeZoneLabel();
		$currenttime = Util::getCurrentTimeInTimeZone($timezone, null);
		$currenttime = empty($currenttime)? " NOW() " : "'$currenttime'";
		
		$result = $this->getItemIdForSku($item_sku);
		
		$item_id = $result["id"];
		
		$sql = "
				INSERT INTO org_top_items(org_id,item_id,priority,added_by,added_on,is_valid) 
				VALUES ($org_id,$item_id,$priority,$user_id,$currenttime,$is_valid)
				";
		
		$insert = $db->insert($sql);
		
		if( $insert!= false)
		{
			$attributes = $this->getForOrg();
		}	
		
	    else
	    {
			return false;
	    }
		
		return $attributes;
	}
	
	/**
	 * Returns the current top items configured for the org 
	 * @return multitype:
	 */
	
	public function getForOrg(){
		
		$db = new Dbase('users');
		$org_id = $this->org_id;
		
		$sql = "
					SELECT * FROM org_top_items
					WHERE org_top_items.is_valid = 1
					AND org_top_items.org_id = $org_id
					";
		$result = $db->query($sql);
		$attributes = array();
		foreach ($result as $message_data)
		{
			$item_id = $message_data['item_id'];
			
		 	$sql = " SELECT IIA.`name`, IIAV.`value_name` as `value`
			FROM `inventory_items` AS II
			JOIN `inventory_item_attributes` AS IIA ON
			(II.`attribute_id` = IIA.`id` AND II.`org_id` = IIA.`org_id`)
			JOIN `inventory_item_attribute_values` AS IIAV ON
			(II.`attribute_value_id` = IIAV.`id` AND II.`org_id` = IIAV.`org_id`)
			WHERE II.`item_id` = $item_id AND II.`org_id` = $org_id
			";
			
			$results = $db->query($sql);
			
			if(is_array($results))
			{
				
				foreach($results as $att)
				{
					$attribute .= $att['name'].":".$att['value']."&nbsp&nbsp&nbsp&nbsp";
				}
		
				$attr = array();
				$attr['id'] = $message_data['id'];
				$attr['org_id'] = $message_data['org_id'];
				$attr['item_id'] = $message_data['item_id'];
				$attr['priority'] = $message_data['priority'];
				$attr['added_by'] = $message_data['added_by'];
				$attr['added_on'] = $message_data['added_on'];
				$attr['is_valid'] = $message_data['is_valid'];
				$attr['attributes'] = $attribute;
				array_push($attributes, $attr);
				
			}	
		}
		
		return $attributes;
		
	}
	
	/**
	 * @return array of All inventory Attributes of the current org. 
	 */
	public function getInventoryAttributes($filters)
	{
		$res = array();
		$objArr = array();
		if($filters["id"]) 
		    $objArr = array(InventoryAttribute::loadById($this->currentorg->org_id, $filters["id"]));
		    
		else if($filters["code"]) 
		    $objArr = array(InventoryAttribute::loadByName($this->currentorg->org_id, $filters["code"]));
		else{
		    $objArr = InventoryAttribute::loadAll($this->currentorg->org_id, null, $filters["limit"], $filters["offset"]);
		}
		//if attribute values are required
		if($filters["values"]){
			foreach($objArr as $attr)
	        {
	            $arr = array();
	            $attr_arr = $attr->toArray();
	            $filter = new InventoryAttributeValueLoadFilters();
	            $filter->inventory_attribute_id = $attr_arr['inventory_attribute_id'];
	            try{
	                $attribute_val = InventoryAttributeValue::loadAll($this->currentorg->org_id, $filter, $filters["value_limit"],$filters["value_offset"]);             
	                $arr["possible_values"] = array();
	                foreach($attribute_val as $attr_val)
	                {
	                    $arr["possible_values"][] = $attr_val->toArray();
	                }
	            } catch( Exception $e)
	            {
	            }
				if($attr_arr["default_attribute_value_id"]){
					$default_attribute_value_name = array();
					try{
						$default_attribute_value_name = InventoryAttributeValue::loadById($this->currentorg->org_id, $attr_arr["default_attribute_value_id"]);
						$default_attribute_value_name = $default_attribute_value_name->toArray();
					} catch( Exception $e){
						$this->logger->debug("Inventory attribute values not found for value id".  $arr["attribute"]["default_attribute_value_id"]);
					}
				$arr["default_attribute_value_name"] = $default_attribute_value_name["value_name"];
				}
				$arr["attribute"] = $attr_arr;
	            $res[] = $arr;
			}
		}
		else{
			foreach($objArr as $obj)
			{
			    $arr = array();
				$arr["attribute"] = $obj->toArray();
				if($arr["attribute"]["default_attribute_value_id"]){
					$default_attribute_value_name = array();
					try{
						$default_attribute_value_name = InventoryAttributeValue::loadById($this->currentorg->org_id, $arr["attribute"]["default_attribute_value_id"]);
						$default_attribute_value_name = $default_attribute_value_name->toArray();
					} catch( Exception $e){
						$this->logger->debug("Inventory attribute values not found for value id".  $arr["attribute"]["default_attribute_value_id"]);
					}
				$arr["default_attribute_value_name"] = $default_attribute_value_name["value_name"];
				}
		        $res[] = $arr;
			}
		}
		
		

		return $res;
		/*
		$sql = "SELECT `name`, `name` as `label`, `type` FROM `inventory_item_attributes` WHERE
				`org_id` = $this->org_id ";

		$db = new Dbase( 'product' );
		$arr = $db->query($sql);
		*/
	}

	/**
	 * 
	 * @param unknown_type $sku
	 */
	public function getProductBySku($sku, $includeIds=false,$includeProductHierarchy = false)
	{
		$formatter = ResourceFormatterFactory::getInstance('inventorymaster');

		if ($includeIds)
			$formatter->setIncludedFields("id");

		$this->logger->debug("ApiInventoryController: Include hierarchy :" . $includeProductHierarchy);

		$item = InventoryMaster::loadBySku($this->currentorg->org_id, $sku);
		$item = $item->toArray();
		if ($includeProductHierarchy) {
			$categoryId = $item["category"]["inventory_category_id"];
			$this->logger->debug("ApiInventoryController category id is" . $categoryId);
			if($categoryId>0) {
				$cat = $this->getCategories(array("id" => $categoryId));
				$item["category"]["category_hierarchy"] = $cat[0]["category_hierarchy"];
			}
			$brandId=$item["brand"]["inventory_brand_id"];
			if($brandId > 0) {
				$this->logger->debug("ApiInventoryController brand id is" . $brandId);
				$brand = $this->getBrands(array("id" => $brandId));
				$item["brand"]["brand_hierarchy"] = $brand[0]["brand_hierarchy"];
			}
		}

		$item = $formatter->generateOutput($item,$includeProductHierarchy);
		return $item;
		/*
        $safe_sku = Util::mysqlEscapeString($sku);
		$sql = "SELECT * FROM `inventory_masters` WHERE `item_sku` = '$safe_sku' and `org_id` = $this->org_id";
		$this->db = new Dbase( 'users' );
		$result = $this->db->query_firstrow($sql);
		
		$item = array();
		
		if(isset($result) && is_array($result))
		{
			$item['sku'] = $result['item_sku'];
			$item['price'] = $result['price'];
			$item['img_url'] = $result['img_url']; 
			//$item['img_url'] = "http://img5.flixcart.com/www/prod/images/new-on-fk/mini-roborover-1d4cd9bb.jpg";
			//$item['in_stock'] = $result['in_stock']; //not getting populated yet
			$item['in_stock'] = true;
			$item['description'] = $result['description'];
				
			$attributes = $this->getAttributeOfProduct($result['id']);
				
			$item['attributes'] = array("attribute" => $attributes);
		}
		else
			throw new Exception("ERR_PRODUCT_FAILURE");
		return $item;
		*/
	}
	
	
	public function getTopItems()
	{
		$sql = "SELECT `item_id`,`priority` FROM `org_top_items` WHERE
				`org_id` = $this->org_id AND
				`is_valid` = 1";
		$this->db = new Dbase( 'users' );
		$result = $this->db->query($sql);
		$arr_ids = array();
		$priorities = array();
		
		//extracting item_id and priority
		foreach($result as $item)
		{
			$arr_ids[] = $item['item_id'];
			$priorities[$item['item_id']] = $item['priority'];
			
		}
		$ids = join(",", $arr_ids);
		
		//if no id is there in table it will return empty array.
		if(empty($ids))
			return array();
		
		$sql = "SELECT * FROM `inventory_masters` WHERE `id` IN ($ids)";
		$result = $this->db->query($sql);
		$items = array();
		
		$attributes = $this->getAttributeOfProducts($arr_ids);
		
		foreach($result as $temp_item)
		{
			$item = array();
			$item['id'] = $temp_item['id'];
			$item['sku'] = $temp_item['item_sku'];
			$item['price'] = $temp_item['price'];
			//uncomment below line if you want to return priority of that item
			//$item['priority'] = $priorities[$item['id']];
			$item['img_url'] = $temp_item['img_url'];
			//$item['img_url'] = "http://img5.flixcart.com/www/prod/images/new-on-fk/mini-roborover-1d4cd9bb.jpg";
			//$item['in_stock'] = $result['in_stock']; //not getting populated yet
			$item['in_stock'] = true;
			$item['description'] = $temp_item['description'];
				
			$item['attributes'] = array("attribute" => $attributes[$item['id']]);
			array_push($items, $item);
		}		
		
		return $items;
	}
	
	/**
	 * 
	 * @param unknown_type $id
	 */
	public function getProductById($id, $includeIds,$includeProductHierarchy = false)
	{
        //$safe_id = Util::mysqlEscapeString($id);
		$formatter = ResourceFormatterFactory::getInstance('inventorymaster');
			
		if($includeIds)
			$formatter->setIncludedFields("id");
			
		$item = InventoryMaster::loadById($this->currentorg->org_id, $id);
		$item = $item->toArray();
		if ($includeProductHierarchy) {
			$categoryId = $item["category"]["inventory_category_id"];
			if($categoryId >0) {
				$this->logger->debug("ApiInventoryController category id is" . $categoryId);
				$cat = $this->getCategories(array("id" => $categoryId));
				$item["category"]["category_hierarchy"] = $cat[0]["category_hierarchy"];
			}
			$brandId = $item["brand"]["inventory_brand_id"];
			if($brandId >0) {
				$this->logger->debug("ApiInventoryController brand id is" . $brandId);
				$brand = $this->getBrands(array("id" => $brandId));
				$item["brand"]["brand_hierarchy"] = $brand[0]["brand_hierarchy"];
			}
		}
		$item = $formatter->generateOutput($item,$includeProductHierarchy);
		return $item;
		//fetching the Inventory.
		/*
		$sql = "SELECT * FROM `inventory_masters` WHERE `id` = $safe_id AND org_id = $this->org_id";
		$this->db = new Dbase( 'users' );
		$result = $this->db->query_firstrow($sql);
		
		$item = array();
		
		if(isset($result) && is_array($result))
		{
			$item['id'] = $safe_id;
			$item['sku'] = $result['item_sku'];
			$item['price'] = $result['price'];
			$item['img_url'] = $result['img_url']; 
			//$item['img_url'] = "http://img5.flixcart.com/www/prod/images/new-on-fk/mini-roborover-1d4cd9bb.jpg";
			//$item['in_stock'] = $result['in_stock']; //not getting populated yet
			$item['in_stock'] = true;
			$item['description'] = $result['description'];
			
			$attributes = $this->getAttributeOfProduct($safe_id);
			
			$item['attributes'] = array("attribute" => $attributes); 
		}
		else 
			throw new Exception("ERR_PRODUCT_FAILURE");
		return $item;
		 */
	}
	
	/**
	 * returns attributes of single product.
	 * @param unknown_type $id
	 */
	private function getAttributeOfProduct($id)
	{
		$attributes = array();
		
		$sql = "SELECT IIA.`name`, IIAV.`value_name` as `value` 
				FROM `inventory_items` AS II 
				JOIN `inventory_item_attributes` AS IIA ON 
						(II.`attribute_id` = IIA.`id` AND II.`org_id` = IIA.`org_id`) 
				JOIN `inventory_item_attribute_values` AS IIAV ON 
						(II.`attribute_value_id` = IIAV.`id` AND II.`org_id` = IIAV.`org_id`)
				WHERE II.`item_id` = $id AND II.`org_id` = $this->org_id";
		
		if(!isset($this->db) || !is_object($this->db))
			$this->db = new Dbase( 'users' );
		
		$result = $this->db->query($sql);
		
		if(is_array($result))
		{
			
			foreach($result as $att)
			{
				$attribute = array();
				
				$attribute['name'] = $att['name'];
				$attribute['value'] = $att['value']; 
				
				array_push($attributes, $attribute);
			}
		}
		
		return $attributes;
	}
		
	/**
	 * @return array of All inventory Attributes of the current org that are searcheable 
	 */
	public function getInventoryAttributesForSearch()
	{
		$sql = "SELECT name, type FROM `inventory_item_attributes` WHERE
				`org_id` = $this->org_id 
				";

		$db = new Dbase( 'product' );
		$arr = $db->query($sql);
		
		return $arr;
	}
	

	/**
	 * returns attributes of multiple attributes.
	 * @param array $ids 
	 * @return multitype:
	 */
	private function getAttributeOfProducts(array $ids)
	{
		$attributes = array();
		if(count($ids) <= 0)
			return array();
		
		$ids_str = implode(",", $ids);
		
		$sql = "SELECT II.item_id AS item_id,  IIA.`name`, IIAV.`value_name` as `value` 
				FROM `inventory_items` AS II 
				JOIN `inventory_item_attributes` AS IIA ON 
						(II.`attribute_id` = IIA.`id` AND II.`org_id` = IIA.`org_id`) 
				JOIN `inventory_item_attribute_values` AS IIAV ON 
						(II.`attribute_value_id` = IIAV.`id` AND II.`org_id` = IIAV.`org_id`)
				WHERE II.`item_id` IN ( $ids_str ) AND II.`org_id` = $this->org_id";
		
		if(!isset($this->db) || !is_object($this->db))
			$this->db = new Dbase( 'users' );
		
		$result = $this->db->query($sql);
		
		if(is_array($result))
		{
			
			foreach($result as $att)
			{
				$attribute = array();
				$item_id = $att['item_id'];
				$attribute['name'] = $att['name'];
				$attribute['value'] = $att['value'];
				
				if(!isset($attributes[$item_id]))
					$attributes[$item_id] = array();
				
				array_push($attributes[$item_id], $attribute);
			}
		}
		
		return $attributes;
	}
	
	public function pushProductToSolr($product)
	{
		return -1;
        $this->logger->debug("Adding product : " . $product['item_id'] . " from org " . $this->currentorg->org_id . " to beanstalk");
        $input = array('org_id' => $this->currentorg->org_id, 'item_ids' => array($product['item_id']));
        try {
            $client = new AsyncClient("product", "searchtube");
            $payload = json_encode($input, true);
            $this->logger->debug("payload for job : " . $payload);

            $j = new Job($payload);
            $j->setContextKey("event_class", "product");
            $job_id = $client->submitJob($j);
        } catch (Exception $e) {
            $this->logger->error("Error submitting job to beanstalk for solr : " . $e->getMessage());
        }
        if ($job_id <= 0)
            $this->logger->error("Failed to submit job to add product to solr. item_id : " . $product['item_id'] .
                " org_id : " . $currentorg->org_id);
			$this->logger->info("job id:".$job_id);
        return $job_id;
    }
    
	public function addProductToInventory($product){
		$inventory = new InventoryMaster($this->currentorg->org_id);
		
		if(isset($product['sku']) && $product['sku'])
			$inventory->setItemSku($product['sku']);
		else 
			throw new Exception('ERR_PRODUCT_NO_ITEM_SKU');
			
		if(isset($product['price']) && $product['price']){
			if(is_numeric($product['price']))
				$inventory->setPrice($product['price']);
			else
				throw new Exception('ERR_PRODUCT_PRICE_NON_NUMERIC');
		}
		else 
			throw new Exception('ERR_PRODUCT_NO_PRICE');
		
		if(isset($product['ean']) && $product['ean'])
			$inventory->setItemEan($product['ean']);
		
		if(isset($product['description']) && $product['description'])
			$inventory->setDescription($product['description']);
		
		if(isset($product['img_url']) && $product['img_url'])
			$inventory->setImageUrl($product['img_url']);
		
		if(isset($product["long_description"]) && $product["long_description"])
			$inventory->setLongDescription($product["long_description"]);
		
		if(isset($product['size']['name']) && $product['size']['name'] && isset($product['size']['type']) && $product['size']['type'] ){
			try{
				$size = InventorySize::loadByCodeType($this->currentorg->org_id, $product['size']['name'], $product['size']['type']);
				$inventory->setSize($size);
			} catch (Exception $e){
				
			}
		}
		
		if(isset($product['style']['name']) && $product['style']['name']){
			try{
				$style = InventoryStyle::loadByCode($this->currentorg->org_id, $product['style']['name']);
				$inventory->setStyle($style);
			} catch (Exception $e){
				
			}
		}
		
		if(isset($product['brand']['name']) && $product['brand']['name']){
			try{
				$brand = InventoryBrand::loadByCode($this->currentorg->org_id, $product['brand']['name']);
				$inventory->setBrand($brand);
			} catch ( Exception $e){
				
			}
		}	
		
		if(isset($product['color']['pallette'])){
			try{
				$color = InventoryColor::loadByPallette(hexdec($product['color']['pallette']));
				$inventory->setColor($color);
			} catch ( Exception $e ){
				
			}
		}
		
		if(isset($product['category']['name']) && $product['category']['name']){
			try{
				$category = InventoryCategory::loadByCode($this->currentorg->org_id, $product['category']['name']);
				$inventory->setCategory($category);
			} catch ( Exception $e ){
				
			}
		}
		
		if(isset($product['attributes'])){
			$inventory_attributes = array();
			
			$attributes = array_key_exists(0, $product['attributes']['attribute']) ? $product['attributes']['attribute'] : array($product['attributes']['attribute']);
			
			foreach($attributes as $attr){
				$this->logger->debug("Fetching inventory attribute with name", $attr['name']);
				try{
					$inventory_attribute = InventoryAttribute::loadByName($this->currentorg->org_id, $attr['name']);
					$inventory_attribue_value = new InventoryAttributeValue($this->currentorg->org_id, NULL);
					$inventory_attribute_id = $inventory_attribute->getInventoryAttributeId();
					try {
						$this->logger->debug("Fetching inventory attribute value by attribute id:".$inventory_attribute_id. "and code:". $attr['value']);
						$inventory_attribue_value = $inventory_attribue_value->loadByAttrIdName($this->currentorg->org_id, $inventory_attribute_id, $attr['value']);
					}catch (Exception $e){
						$this->logger->debug("Creating the value for the attribute id:". $inventory_attribute_id);
						$inventory_attribue_value->setValueName($attr['value']);
						$inventory_attribue_value->setValueCode($attr['value']);
						$inventory_attribue_value->setInventoryAttributeId($inventory_attribute->getInventoryAttributeId());
						$inventory_attribue_value->save();
					}
					$inventory_attribute->setInventoryAttributeValue($inventory_attribue_value);
					$inventory_attributes[] = $inventory_attribute;
			   }catch (Exception $e){
			   	$this->logger->debug("Attribute not found with name". $attr['name']);
			   }
			}
			$inventory->setInventoryAttributes($inventory_attributes);
		}

		try{
			if(isset($product['ean']) && $product['ean'])
			{
				$item_ean = $product['ean'];
				$sql = "SELECT * FROM `inventory_masters` WHERE `item_ean` = '$item_ean' and `org_id` = $this->org_id";
				$db = new Dbase( 'product' );
				$result = $db->query_firstrow($sql);
			}
			$obj = $inventory->loadBySku($this->currentorg->org_id, $product['sku']);
			if($obj){
				if($result['item_sku']){
					if($result['item_sku'] != $product['sku']){
						throw new Exception("ERR_DUPLICATE_ITEM_EAN");
					}	
				}
				$inventory->setItemId($obj->getItemId());
				
				if($obj->getItemEan() && !$product['ean']){
					$inventory->setItemEan($obj->getItemEan());
				}
				else if(!$product['ean']){
					$inventory->setItemEan($product['sku']);
				}
				
				if($obj->getDescription() && !$product['description']){
					$inventory->setDescription($obj->getDescription());
				}
				
				if($obj->getLongDescription() && !$product['long_description']){
					$inventory->setLongDescription($obj->getLongDescription());
				}
				
				if($obj->getCategory() && !$product['category']['name']){
					$inventory->setCategory($obj->getCategory());
				}
				
				if($obj->getBrand() && !$product['brand']['name']){
					$inventory->setBrand($obj->getBrand());
				}
				
				if($obj->getStyle() && !$product['style']['name']){
					$inventory->setStyle($obj->getStyle());
				}
				
				if($obj->getSize() && !$product['size']['name'] && !$product['size']['type']){
					$inventory->setSize($obj->getSize());
				}
				
				if($obj->getColor() && !isset($product['color']['pallette'])){
					$inventory->setColor($obj->getColor());
				}
				
				
				if($obj->getImageUrl() && !$product['img_url']){
					$inventory->setImageUrl($obj->getImageUrl());
				}
				
				$inventory->setItemId($obj->getItemId());
				$this->logger->debug("Going for product update for sku:". $product['sku']);
			}
		} catch (Exception $e){
			if(!$product['ean']){
					$inventory->setItemEan($product['sku']);
			}
			
			if($result['item_sku'])
				throw new Exception("ERR_DUPLICATE_ITEM_EAN");
			$this->logger->debug("Could not find the product by Item sku:". $product['sku']);
		}
		//print_r($inventory->toArray());
		$inventory = $inventory->save();
		$product = $inventory->toArray();
		$this->pushProductToSolr($product);
		return $product;
	}
	
	public function saveStyleToInventory($style)
	{
		global $currentorg;
		try{
			$obj = InventoryStyle::loadByCode($currentorg->org_id, $style['code']);
			$obj->setDescription($style['description']);
			$obj->setName($style["name"]);
			$obj->save();
			return $obj->toArray();
		} catch (Exception $e){
		}
		
		$style_obj = new InventoryStyle($currentorg->org_id);
		$style_obj->setCode($style['code']);
		$style_obj->setDescription($style['description']);
		$style_obj->setName($style['name']);
		$style_obj->validate();
		$style_obj->save();
		return $style_obj->toArray();
	}
	

	public function getInventoryStyles($filters)
	{
		$ret = array();
		$objArr = array();
		if($filters["id"]) 
			$objArr = array(InventoryStyle::loadById($this->currentorg->org_id, $filters["id"]));
			
		else if($filters["code"]) 
			$objArr = array(InventoryStyle::loadByCode($this->currentorg->org_id, $filters["code"]));
 		else{
 			$objArr = InventoryStyle::loadAll($this->currentorg->org_id, null, $filters["limit"], $filters["offset"]);
 		}
		foreach($objArr as $obj)
		{
			$objAssoc = $obj->toArray();
			$ret [] = $objAssoc;
		}
		
		return $ret;
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $filters
	 * @return multitype:ArrayObject(InventoryCategory)
	 */
	public function getCategories($filters = array(), $includeValues = false)
	{
		include_once 'models/inventory/InventoryCategory.php';
		$categoriesArr = array();

		if($filters["id"]) 
			$categoriesObjArr = array(InventoryCategory::loadById($this->currentorg->org_id, $filters["id"]));
			
		else if($filters["code"]) 
			$categoriesObjArr = array(InventoryCategory::loadByCode($this->currentorg->org_id, $filters["code"]));
		
		else
		{
			$filterObj = new InventoryAttributeGenericLoadFilters();
			if($filters["parent_id"]) $filterObj->parent_id = $filters["parent_id"];
			
			else if($filters["parent_code"]) 
			{
				$filters["parent_id"]= array(-1);
				
				foreach(explode(",", $filters["parent_code"]) as $code)
				{
					if(strtolower($code) == "root")
						$filters["parent_id"][] = 0;
					else
					{
							
						try {
							$filters["parent_id"][] = InventoryCategory::loadByCode($this->currentorg->org_id, $code)->getInventoryCategoryId();
						} catch (Exception $e) {
						}
					}
				}
				$filterObj->parent_id = implode(",", $filters["parent_id"]);
			}
			$categoriesObjArr = InventoryCategory::loadAll($this->currentorg->org_id, $filterObj,$filters["limit"], $filters["offset"]);
		}
		
		foreach($categoriesObjArr as $cat)
		{
			$catAssoc = $cat->toArray();
			
			if($includeValues)
			{
				try {
					
					$valuesArr = $cat->getCategoryValues();
					foreach ($valuesArr as $value)
					{
						$catAssoc["values"][] = $value->toArray();
					}
				} catch (Exception $e) {
				}
				
			}

                        $parent_heirarchy = array();
                        $parent_h = $cat->getParentCategoryHeirarchy();
                        $level = count($parent_h);
                        foreach($parent_h as $parent_node)
                        {
                            $parent = $parent_node->toArray();
                            $parent["level"] = $level;
                            $parent_heirarchy[] = $parent;
                            $level--;
                        }
                        $catAssoc["category_hierarchy"] = $parent_heirarchy;
			$categoriesArr [] = $catAssoc;
		}
			
		return $categoriesArr;
	}


	public function saveCategory($categoryDetails)
	{
		include_once 'models/inventory/InventoryCategory.php';
		$catObj = InventoryCategory::fromArray($this->currentorg->org_id, $categoryDetails);
		try {
				// check if id is already available
				$idObj = InventoryCategory::loadByCode($this->currentorg->org_id, $catObj->getCode());
				$catObj->setInventoryCategoryId($idObj->getInventoryCategoryId());
			} catch (Exception $e) {
		}

		// throws exception if invalid parent passed
		if($categoryDetails["parentCategory"]["code"])
		{
			//set the parent id possable
			$catObj->setParentCategory(InventoryCategory::loadByCode($this->currentorg->org_id, $categoryDetails["parentCategory"]["code"]));
				
		}
		if($categoryDetails["values"])
		{
			foreach($categoryDetails["values"] as $value)
			{
				$valueObj = InventoryCategoryValue::fromArray($this->currentorg->org_id, $value);
				$valueObjArr[] = $valueObj;
			}
			$catObj->setCategoryValues($valueObjArr);
			//print_r( $catObj->getCategoryValues()); die();
		}
		$catObj->save();
		
		return $catObj->toArray();
	}
	
		/**
	 * Enter description here ...
	 * @param unknown_type $filters
	 * @return multitype:ArrayObject(InventoryBrand)
	 */
	public function getBrands($filters = array())
	{
		$ret = array();

		if($filters["id"]) 
			$objArr = array(InventoryBrand::loadById($this->currentorg->org_id, $filters["id"]));
			
		else if($filters["code"]) 
			$objArr = array(InventoryBrand::loadByCode($this->currentorg->org_id, $filters["code"]));

		else
		{
			$filterObj = new InventoryAttributeGenericLoadFilters();
			if($filters["parent_id"]) $filterObj->parent_id = $filters["parent_id"];
			
			else if($filters["parent_code"]) 
			{
				$filters["parent_id"]= array(-1);
				foreach(explode(",", $filters["parent_code"]) as $code)
				{
					if(strtolower($code) == "root")
						$filters["parent_id"][] = 0;
					else 
					{
						try {
							$filters["parent_id"][] = InventoryBrand::loadByCode($this->currentorg->org_id, $code)->getInventoryBrandId();
						} catch (Exception $e) {
						}
					}
				}
				$filterObj->parent_id = implode(",", $filters["parent_id"]);
			}
			
			$objArr = InventoryBrand::loadAll($this->currentorg->org_id, $filterObj, $filters["limit"], $filters["offset"]);
		}
		
		foreach($objArr as $obj)
		{
			$objAssoc = $obj->toArray();
                        $parent_heirarchy = array();
                        $parent_h = $obj->getParentBrandHeirarchy();
                        $level = count($parent_h);
                        foreach($parent_h as $parent_node)
                        {
			    $parent = $parent_node->toArray();
                            $parent["level"] = $level;
                            $parent_heirarchy[] = $parent;
                            $level--;
                        }
                        $objAssoc["brand_hierarchy"] = $parent_heirarchy;
			$ret[] = $objAssoc;
		}
			
		return $ret;
	}
	
	public function saveSizeToInventory($size){
		if(!$size["meta_size"]["code"])
			throw new Exception("ERR_NO_META_SIZE_CODE");
		if(!$size["meta_size"]["size_family"])
			throw new Exception("ERR_NO_META_SIZE_FAMILY");
		if(!$size["meta_size"]["type"])
			throw new Exception("ERR_NO_META_SIZE_TYPE");
		
		$meta_filter = new InventoryAttributeGenericLoadFilters();	
		
		$meta_filter->code = $size["meta_size"]["code"];
		$meta_filter->size_family = $size["meta_size"]["size_family"];
		$meta_filter->type = $size["meta_size"]["type"];
		$meta_size = InventoryMetaSize::loadAll($meta_filter, $filters["limit"], $filters["offset"]);
		
		try{
			$size_obj = InventorySize::loadByCodeType($this->currentorg->org_id, $size["code"], $size["meta_size"]["type"]);
			
		} catch ( Exception $e ){
			$size_obj = new InventorySize($this->currentorg->org_id);	
			$size_obj->setCode($size["code"]);
		}
		$size_obj->setName($size["name"]);
		$size_obj->setMetaSize($meta_size[0]);
		$size_obj->validate();
		$size_obj->save();
		
		return $size_obj->toArray();
	}


	public function saveBrand($brandDetails)
	{
		$obj = InventoryBrand::fromArray($this->currentorg->org_id, $brandDetails);
		try {
				// check if id is already available
				$idObj = InventoryBrand::loadByCode($this->currentorg->org_id, $obj->getCode());
				$obj->setInventoryBrandId($idObj->getInventoryBrandId());
			} catch (Exception $e) {
		}

		// throws exception if invalid parent passed
		if($brandDetails["parentBrand"]["code"])
		{
			//set the parent id possable
			$obj->setParentBrand(InventoryBrand::loadByCode($this->currentorg->org_id, $brandDetails["parentBrand"]["code"]));
				
		}
		$obj->save();
		
		return $obj->toArray();
	}
	
	public function getSizes($filters)
	{
		$ret = array();
		$objArr = array();
		if($filters["id"]) 
			$objArr = array(InventorySize::loadById($this->currentorg->org_id, $filters["id"]));
			
		//else if($filters["code"]) 
		//	$objArr = array(InventorySize::loadByCode($this->currentorg->org_id, $filters["code"]));

		else
		{
			if(array_key_exists("canonical_name", $filters) || array_key_exists("type", $filters) || array_key_exists("size_family", $filters)){
				$filterObj = new InventoryAttributeGenericLoadFilters();
				
				if($filters["canonical_name"]) 
					$filterObj->code = $filters["canonical_name"];
	
				if($filters["type"])
					$filterObj->type = $filters["type"];
				
				if($filters["size_family"])
					$filterObj->size_family = $filters["size_family"];
				$metaObjArr = InventoryMetaSize::loadAll($filterObj, 200, 0);
				$sizeObjArr = array();
				foreach($metaObjArr as $metaObj){
					$meta_size = $metaObj->toArray();
					$filter = new InventoryAttributeGenericLoadFilters();
					if($filters["name"])
						$filter->code = $filters["name"];
					$filter->meta_size_id = $meta_size["inventory_meta_size_id"];
					try{
						$sizeObjArr = array_merge($sizeObjArr, InventorySize::loadAll($this->currentorg->org_id, $filter, $filters["limit"], $filters["offset"]));
					} catch (Exception $e){
						$this->logger->debug("No sizes for the meta size id:".$meta_size["inventory_meta_size_id"]);
					}
				}
				if(count($sizeObjArr)==0)
						throw new ApiInventoryException(ApiInventoryException::NO_SIZE_MATCHES);
			}
			elseif (array_key_exists("name", $filters)) {
				$filter = new InventoryAttributeGenericLoadFilters();
					if($filters["name"])
						$filter->code = $filters["name"];
					$sizeObjArr = InventorySize::loadAll($this->currentorg->org_id, $filter, $filters["limit"], $filters["offset"]);
			}
			else{
				$sizeObjArr = InventorySize::loadAll($this->currentorg->org_id, $filter, $filters["limit"], $filters["offset"]);
			}
			
			foreach($sizeObjArr as $sizeObj){
						$objArr[] = $sizeObj;
					}
			
		}
		
		foreach($objArr as $obj)
		{
			$objAssoc = $obj->toArray();
			$ret [] = $objAssoc;
		}
		
		return $ret;
	}

	public function getColors($filters)
	{
		$ret = array();
		$objArr = array();
		if($filters["pallette"]) 
			$objArr = array(InventoryColor::loadByPallette($filters["pallette"]));
		else
		{
			
			$filterObj = new InventoryAttributeGenericLoadFilters();
			
			$colorObjArr = InventoryColor::loadAll($filterObj, $filters["limit"], $filters["offset"]);
			foreach($colorObjArr as $colorObj){
				$objArr[] = $colorObj;
			}
		}
		foreach($objArr as $obj)
		{
			$objAssoc = $obj->toArray();
			$ret [] = $objAssoc;
		}
		
		return $ret;
	}
	
	public function getMetaSizes($filters)
	{
		$ret = array();
		$objArr = array();
		if($filters["id"]) 
			$objArr = array(InventoryMetaSize::loadById($filters["id"]));
		/*
		else if($filters["code"]) 
			$objArr = array(InventorySize::loadByCode($this->currentorg->org_id, $filters["code"]));
		*/
		else
		{
			
			$filterObj = new InventoryAttributeGenericLoadFilters();
			if($filters["name"]) 
				$filterObj->code = $filters["name"];
			
			if($filters["type"])
				$filterObj->type = $filters["type"];
			
			if($filters["size_family"])
				$filterObj->size_family = $filters["size_family"];
			
			$metaObjArr = InventoryMetaSize::loadAll($filterObj, $filters["limit"], $filters["offset"]);
			foreach($metaObjArr as $metaObj){
				$objArr[] = $metaObj;
			}
		}
		
		foreach($objArr as $obj)
		{
			$objAssoc = $obj->toArray();
			$ret [] = $objAssoc;
		}
		
		return $ret;
	}
	
	public function getAttributeValues($filters=null)
	{
		$res = array();
		$ret = array();
		
		if(array_key_exists("id", $filters) || array_key_exists("code", $filters)){
			if($filters["attribute_id"]) 
				$objAttrArr = InventoryAttribute::loadById($this->currentorg->org_id, $filters["attribute_id"]);
			
			else if($filters["code"]) 
				$objAttrArr = InventoryAttribute::loadByName($this->currentorg->org_id, $filters["code"]);
			
			$attr_arr = $objAttrArr->toArray();
			$filter = new InventoryAttributeValueLoadFilters();
			$filter->inventory_attribute_id = $attr_arr['inventory_attribute_id'];
			if($filters["name"]){
				$names = explode(",", $filters["name"]);
				$arr = array();
				$arr["possible_values"] = array();
				foreach($names as $name){
					try{
						$filter->value_name = $name;
						$attribute_values = InventoryAttributeValue::loadAll($this->currentorg->org_id, $filter, $filters["value_limit"], $filters["value_offset"]);
						if($attribute_values)
						{
							foreach($attribute_values as $attr_val)
							{
								$arr["possible_values"][] = $attr_val->toArray();
							}				
						}
					} catch (Exception $e)
					{	
					}
				}
			}
			elseif($filters["id"]){
				$ids = explode(",", $filters["id"]);
				$arr = array();
				$arr["possible_values"] = array();
				foreach($ids as $id){
					try{
						$filter->id = $id;
						$filter->inventory_attribute_value_id = $id;
						if($id)
							$attribute_values = InventoryAttributeValue::loadAll($this->currentorg->org_id, $filter, $filters["value_limit"], $filters["value_offset"]);
						if($attribute_values)
						{
							foreach($attribute_values as $attr_val)
							{
								$arr["possible_values"][] = $attr_val->toArray();
							}				
						}
					} catch (Exception $e)
					{	
					}
				}
			}
			else{
				try{
					$attribute_values = InventoryAttributeValue::loadAll($this->currentorg->org_id, $filter, $filters["value_limit"], $filters["value_offset"]);
					if($attribute_values)
					{
						$arr = array();
						$arr["possible_values"] = array();
						foreach($attribute_values as $attr_val)
						{
							$arr["possible_values"][] = $attr_val->toArray();
						}				
					}
				}catch ( Exception $e){
					
				}
			}
			$arr["attribute"] = $attr_arr;
			$res[] = $arr;
		}
		
		else
		{
			$attributes = InventoryAttribute::loadAll($this->currentorg->org_id,null,$filters["limit"], $filters["offset"]);
			
			foreach($attributes as $attr)
			{
				$arr = array();
				$attr_arr = $attr->toArray();
				$filter = new InventoryAttributeValueLoadFilters();
				$filter->inventory_attribute_id = $attr_arr['inventory_attribute_id'];
				try{
					//if($filters["value_name"])
					//	$filter->value_name = $filters["value_name"];
					$attribute_val = InventoryAttributeValue::loadAll($this->currentorg->org_id, $filter, $filters["value_limit"], $filters["value_offset"]);
					if($attribute_val){				
						$arr["possible_values"] = array();
						foreach($attribute_val as $attr_val)
						{
							$arr["possible_values"][] = $attr_val->toArray();
						}
					$arr["attribute"] = $attr_arr;
					$res[] = $arr;
					}
				} catch( Exception $e)
				{
				}
			}
		}
//print_r($res);
		/*
		foreach($res as $obj)
		{
			$objAssoc = $obj->toArray();
			$ret [] = $objAssoc;
		}
		*/
		return $res;
	}

	public function saveAttributes($attribute)
	{
		$obj = InventoryAttribute::fromArray($this->currentorg->org_id, $attribute);
		try {
			// check if id is already available
			$idObj = InventoryAttribute::loadByName($this->currentorg->org_id, $obj->getName());
			$obj->setInventoryAttributeId($idObj->getInventoryAttributeId());
			if(!$obj->getLabel())
				$obj->setLabel($idObj->getLabel());
			if(!$obj->getExtractionRuleType())
				$obj->setExtractionRuleType($idObj->getExtractionRuleType());
			if(!$obj->getExtractionRuleData())
				$obj->setExtractionRuleData($idObj->getExtractionRuleData());
			if(!$obj->getType())
				$obj->setType($idObj->getType());
			if(!$obj->getIsSoftEnum())
				$obj->setIsSoftEnum($idObj->getIsSoftEnum());
			if(!$obj->getDefaultAttributeValueId())
				$obj->setDefaultAttributeValueId($idObj->getDefaultAttributeValueId());
			if(!$obj->getUseInDump())
				$obj->setUseInDump($idObj->getUseInDump());
		} catch (Exception $e) {
		}
		$obj->save();
		try{
			if($attribute["default_attribute_value_name"]){
				$default_value_obj = InventoryAttributeValue::loadByAttrIdName($this->currentorg->org_id, $obj->getInventoryAttributeId(), $attribute["default_attribute_value_name"]);
				$obj->setDefaultAttributeValueId($default_value_obj->getInventoryAttributeValueId());
				$obj->save();
				$res["default_attribute_value_name"] = $default_value_obj->getValueName();
			}
		} catch(Exception $e)
		{
		}
		
		if($attribute["values"]["value"]){
			$attribute_values_arr = array();
			$attribute_values = isset($attribute['values']['value'][0])?$attribute['values']['value']:array($attribute['values']['value']);
			foreach ($attribute_values as $attribute_value){
				try{
					$val_obj = InventoryAttributeValue::loadByAttrIdName($this->currentorg->org_id, $obj->getInventoryAttributeId(), $attribute_value["name"]);
					if(isset($attribute_value['label']) && $attribute_value['label'])
						$val_obj->setValueCode($attribute_value["label"]);
					$val_obj->save();
				} catch(Exception $e){
					//$val_obj = InventoryAttributeValue::fromArray($this->currentorg->org_id, $attribute_value);
					$val_obj = new InventoryAttributeValue($this->currentorg->org_id);
					$val_obj->setValueName($attribute_value["name"]);
					if(isset($attribute_value['label']) && $attribute_value['label'])
						$val_obj->setValueCode($attribute_value["label"]);
					else {
						$val_obj->setValueCode($attribute_value["name"]);
					}
					$val_obj->setInventoryAttributeId($obj->getInventoryAttributeId());
					$val_obj->save();
				}
				$attribute_values_arr[]=$val_obj->toArray();
			}
		}
		
		$res["attribute"] = $obj->toArray();
		$res["attribute_values"] = $attribute_values_arr;
		
		return $res;
		/*
		$obj = InventoryBrand::fromArray($this->currentorg->org_id, $brandDetails);
		try {
				// check if id is already available
				$idObj = InventoryBrand::loadByCode($this->currentorg->org_id, $obj->getCode());
				$obj->setInventoryBrandId($idObj->getInventoryBrandId());
			} catch (Exception $e) {
		}

		// throws exception if invalid parent passed
		if($brandDetails["parentBrand"]["code"])
		{
			//set the parent id possable
			$obj->setParentBrand(InventoryBrand::loadByCode($this->currentorg->org_id, $brandDetails["parentBrand"]["code"]));
				
		}
		$obj->save();
		
		return $obj->toArray();
		*/
	}
}
?>
