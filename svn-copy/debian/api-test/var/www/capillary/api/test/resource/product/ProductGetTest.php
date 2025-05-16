<?php
include_once('test/resource/product/ApiProductResourceTestBase.php');
include_once "test/models/BaseModelTestFile.php";
include_once 'models/inventory/InventoryAttribute.php';
include_once 'models/inventory/InventoryAttributeValue.php';
include_once 'models/inventory/InventoryItem.php';
include_once 'models/inventory/InventoryMaster.php';

$currentorg = new OrgProfile(1);

class ProductGetTest extends ApiProductResourceTestBase
{
    public function testGetProductById()
    {
        $this->productResourceObj = new ProductResource();
        $ret = $this->productResourceObj->process('v1.1', 'get', array(), array('id' => $this->item_id),'GET');
        $this->assertEquals($ret['status']['code'], '200');
        $this->assertEquals($ret['product']['item'][0]['id'], $this->item_id);
        $this->assertEquals($ret['product']['item'][0]['sku'], $this->item_code);
        $this->assertEquals($ret['product']['item'][0]['attributes']['attribute'][0]['name'], $this->attribute);
        $this->assertEquals($ret['product']['item'][0]['attributes']['attribute'][0]['value'], $this->attribute_value);
    }
    
    public function testGetProductBySKU()
    {
        $this->productResourceObj = new ProductResource();
        $ret = $this->productResourceObj->process('v1.1', 'get', array(), array('sku' => $this->item_code),'GET');
        $this->assertEquals($ret['status']['code'], '200');
        //$this->assertEquals($ret['product']['item'][0]['id'], $this->item_id);
        $this->assertEquals($ret['product']['item'][0]['sku'], $this->item_code);
        $this->assertEquals($ret['product']['item'][0]['attributes']['attribute'][0]['name'], $this->attribute);
        $this->assertEquals($ret['product']['item'][0]['attributes']['attribute'][0]['value'], $this->attribute_value);
    }
    
    public function testAttributes()
    {
        $this->productResourceObj = new ProductResource();
        $ret = $this->productResourceObj->process('v1.1', 'attributes', array(), array(),'GET');
        $this->assertEquals($ret['status']['code'], '200');
        $this->assertTrue(in_array(array('name' => $this->attribute, 'label' => $this->attribute, 'type' => $this->attribute_type), $ret['product']['attributes']['attribute']));
    }
    
    public function setUp()
    {
        $this->login('till.005', '123');
        global  $currentuser, $currentorg;
        // Add Inventory
        $iaArr = InventoryAttribute::loadAll($currentorg->org_id, $filter, $limit);
        $itemCode = "#".microtime(true);
        $this->item_code = $itemCode;
        $im = new InventoryMaster($currentorg->org_id);
        $im->setItemSku($itemCode);
        $im->setItemEan($itemCode);

        // load the attr value or create a new value
        try {
                $attrValue = InventoryAttributeValue::loadByAttrIdCode(0, $iaArr[0]->getInventoryAttributeId(), $itemCode);
        } catch (Exception $e) {
                $attrValue = new InventoryAttributeValue($currentorg->org_id);
                $attrValue->setInventoryAttributeId($iaArr[0]->getInventoryAttributeId());
                $this->attribute = $iaArr[0]->getName();
                $this->attribute_type = $iaArr[0]->getType();
                $this->attribute_value = "#".microtime(true)."attrv";
                $attrValue->setValueName($this->attribute_value);
                $attrValue->setValueCode($this->attribute_value);
                $attrValue->save();
                $attrValue = $attrValue->loadById($currentorg->org_id, $attrValue->getInventoryAttributeValueId());
        }

        $attr = new InventoryAttribute($currentorg->org_id);
        $attr->setInventoryAttributeId($iaArr[0]->getInventoryAttributeId());
        $attr->setInventoryAttributeValue($attrValue);
        $im->setInventoryAttributes(array($attr));
        $save = $im->save();
        $this->item_id = $im->getItemId();
        
    }

}

?>
