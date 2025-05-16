<?php
include_once "test/models/BaseModelTestFile.php";
include_once 'models/inventory/InventoryAttribute.php';
include_once 'models/inventory/InventoryAttributeValue.php';
include_once 'models/inventory/InventoryItem.php';
include_once 'models/inventory/InventoryMaster.php';
include_once 'models/inventory/InventoryCategory.php';


class InventoryModelsTest extends ApiTestBase
{

	public function __construct(){

		parent::__construct();
// 		$memcache_obj = memcache_connect('localhost', 11211);
// 		memcache_flush($memcache_obj);

		$this->login("shuvo_till", "123");
	}

	public function atestLoadInventoryMaster()
	{
		$org_id = 0;
		
		$limit = 10;
		//$im = new InventoryMaster(0);
		$filter = new InventoryMasterLoadFilters();
		$imArr = InventoryMaster::loadAll($org_id, $filter, $limit);
		
		$this->assertNotNull($imArr);

		// check whetehr org has some attribute
		if($imArr)
		{
			$im = InventoryMaster::loadById($org_id, $imArr[0]->getItemId());
			$this->assertEquals($imArr[0]->getItemId(), $im->getItemId());
			$im->loadItemAttributes();
			//print $imArr[0]->getItemId();

			$im = InventoryMaster::loadBySku($org_id, $imArr[1]->getItemSku());
			$this->assertEquals($imArr[1]->getItemSku(), $im->getItemSku());
				
			$imSave = $im->save();
			$this->assertNotNull($imSave);

			$im->setItemId(null);
			$im->setItemSku(microtime());
			$im->setItemEan(microtime());
			$imSave = $im->save();
				
			foreach(get_class_methods($im) as $fnName)
			{
				if(substr($fnName, 0, 3) == "get" && $fnName != 'getInventoryAttributeValue')
					$im->$fnName();
			}
		}

		$filter = new InventoryAttributeLoadFilters;
		$filter->item_id = 7551280; //$imArr[0]->getItemId();
		$iaArr = InventoryAttribute::loadAll($org_id, $filter);

		// check for atleast one attribute is set
		$attrs = "";
		foreach($iaArr as $attr)
			$attrs .= $attr->getInventoryAttributeValue()->getValueName();
		
		$this->assertNotNull($attrs);
	} 

	public function atestLoadInventoryAttr()
	{
		$limit = 2; 
		$org_id= 0;
		$iaArr = InventoryAttribute::loadAll(0, $filter, $limit);
		$this->assertEquals(count($iaArr), $limit);

		$iaArr[0]->setName("attr".microtime(true));
		$iaArr[0]->save();

		$im = InventoryAttribute::loadById($org_id, $iaArr[0]->getInventoryAttributeId());
		$this->assertNotNull($im->getInventoryAttributeId());
		
		foreach(get_class_methods($iaArr[0]) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$iaArr[0]->$fnName();
		}
		
		// from database
		$attrs = InventoryAttribute::loadForOrg($org_id);
		$this->assertNotNull($attrs);
		$this->assertNotNull($attrs[0]->getInventoryAttributeId());
		
		// from cahce
		$attrs = InventoryAttribute::loadForOrg($org_id);
		$this->assertNotNull($attrs);
		$this->assertNotNull($attrs[0]->getInventoryAttributeId());
		
	}

	public function atestSaveInventoryItem()
	{
		$limit = 2;
		$org_id= 0;
		$iaArr = InventoryAttribute::loadAll(0, $filter, $limit);
		$this->assertEquals(count($iaArr), $limit);
	
		$itemCode = "#".microtime(true);
	
		$im = new InventoryMaster($org_id);
		$im->setItemSku($itemCode);
		$im->setItemEan($itemCode);
		
		// load the attr value or create a new value
		try {
			$attrValue = InventoryAttributeValue::loadByAttrIdCode($org_id, $iaArr[0]->getInventoryAttributeId(), $itemCode);
		} catch (Exception $e) {
			$attrValue = new InventoryAttributeValue($org_id);
			$attrValue->setInventoryAttributeId($iaArr[0]->getInventoryAttributeId());
			$attrValue->setValueName($itemCode);
			$attrValue->setValueCode($itemCode);
			$attrValue->save();
			$attrValue = $attrValue->loadById($org_id, $attrValue->getInventoryAttributeValueId());
		}
		
		$attr = new InventoryAttribute($org_id);
		$attr->setInventoryAttributeId($iaArr[0]->getInventoryAttributeId());
		$attr->setInventoryAttributeValue($attrValue);
		
		$im->setInventoryAttributes(array($attr));
		
		$save = $im->save();
		$this->assertNotNull($save);
		
		$im2 = InventoryMaster::loadById($org_id, $im->getItemId());
		$this->assertEquals($im->getItemSku(), $im2->getItemSku());
		
		$attrValueId =  $im2->getInventoryAttributeValue($iaArr[0]->getInventoryAttributeId())->getInventoryAttributeValue()->getInventoryAttributeValueId();
		$this->assertEquals($attrValueId, $attrValue->getInventoryAttributeValueId() );
		
	}
	
	public function atestLoadInventoryAttrValue()
	{
		$limit = 50;
		$ivArr = InventoryAttributeValue::loadAll(0, $filter, $limit);
		$this->assertEquals(count($ivArr), $limit);
		
		$ivArr[0]->setValueCode("valie".microtime(true));
		$val = $ivArr[0]->save();
		$this->assertNotNull($val);
		
		foreach(get_class_methods($ivArr[0]) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$ivArr[0]->$fnName();
		}
		
	}
	
	public function testSaveCategories()
	{
		$org_id= 0;
		$code = "test1";
		$cat = new InventoryCategory($org_id);
		try {
			$c = InventoryCategory::loadByCode($org_id, $code);
			$cat->setInventoryCategoryId($c->getInventoryCategoryId());
		} catch (Exception $e) {
		}
		
		$cat->setCode($code);
		$cat->setDescription("My test".microtime(true));
		$cat->setName("My test");
		$cat->save();
		print InventoryCategory::loadAll($org_id);
		
		$cat2 = new InventoryCategory($org_id);
		$cat2->setCode($code."2");
		$cat2->setDescription("My test#2 ".microtime(true));
		$cat2->setName("My test#2");
		$cat2->setParentCategory($cat);
		$cat2->save();
		
	}
	
	private function abreaker($str = "")
	{
		return;
		$length = 74 - floor(strlen($str)/2) - 2 ;
		print "\n".str_repeat("*", $length). "  ".ucwords($str)."  ".str_repeat("*", $length)."\n";
	}
	
}


