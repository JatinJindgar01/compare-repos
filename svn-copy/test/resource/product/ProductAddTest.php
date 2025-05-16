<?php
include_once 'ApiProductResourceTestBase.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ProductAdd
 *
 * @author capillary
 */
class ProductAddTest extends ApiProductResourceTestBase{
    
    public function testAddProduct()
    {
        
        $this->productResourceObj = new ProductResource();
		$rand_number = rand(10000, 99999);
		$data =  array("root"=>array("product" => array(0=>array("item_sku" => "unititem".$rand_number, "item_ean" => "unitean".$rand_number, "price" => "123", "description" => "something", "img_url"=>"http://something",attributes => array("attribute"=>array(0=>array("name"=>"","value"=>"")))))));
        $ret = $this->productResourceObj->process('v1.1', 'add', $data, $query_params);
        $this->assertEquals($ret['status']['code'], 200);
        $this->assertEquals($ret['product'][0]['item_sku'], $data['root']['product'][0]['item_sku']);
        $this->assertEquals($ret['product'][0]['item_ean'], $data['root']['product'][0]['item_ean']);
        $this->assertEquals($ret['product'][0]['price'], $data['root']['product'][0]['price']);
        $this->assertEquals($ret['product'][0]['description'], $data['root']['product'][0]['description']);
        $this->assertEquals($ret['product'][0]['img_url'], $data['root']['product'][0]['img_url']);
		$this->assertEquals(9100, $ret['product'][0]['item_status']['code']);
    }

	public function testAddProductNoSku()
    {
        $this->productResourceObj = new ProductResource();
		$rand_number = rand(10000, 99999);
		$data =  array("root"=>array("product" => array(0=>array("item_sku" => "", "item_ean" => "unitean".$rand_number, "price" => "123", "description" => "something", "img_url"=>"http://something",attributes => array("attribute"=>array(0=>array("name"=>"","value"=>"")))))));
        $ret = $this->productResourceObj->process('v1.1', 'add', $data, $query_params);
        $this->assertEquals($ret['status']['code'], 500);
		$this->assertEquals(9102, $ret['product'][0]['item_status']['code']);
    }
	
	public function testAddProductNoPrice()
    {
        $this->productResourceObj = new ProductResource();
		$rand_number = rand(10000, 99999);
		$data =  array("root"=>array("product" => array(0=>array("item_sku" => "123123", "item_ean" => "unitean".$rand_number, "price" => "", "description" => "something", "img_url"=>"http://something",attributes => array("attribute"=>array(0=>array("name"=>"","value"=>"")))))));
        $ret = $this->productResourceObj->process('v1.1', 'add', $data, $query_params);
        $this->assertEquals($ret['status']['code'], 500);
		$this->assertEquals(9103, $ret['product'][0]['item_status']['code']);
    }
	
	public function testAddProductPriceNotNumeric()
    {
        $this->productResourceObj = new ProductResource();
		$rand_number = rand(10000, 99999);
		$data =  array("root"=>array("product" => array(0=>array("item_sku" => "1234", "item_ean" => "unitean".$rand_number, "price" => "12a3", "description" => "something", "img_url"=>"http://something",attributes => array("attribute"=>array(0=>array("name"=>"","value"=>"")))))));
        $ret = $this->productResourceObj->process('v1.1', 'add', $data, $query_params);
        $this->assertEquals($ret['status']['code'], 500);
		$this->assertEquals(9106, $ret['product'][0]['item_status']['code']);
    }
    
    public function setUp()
    {
        $this->login('till.005', '123');
    }
}
