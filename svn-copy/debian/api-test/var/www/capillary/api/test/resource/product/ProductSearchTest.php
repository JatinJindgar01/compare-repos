<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ProductSearch
 *
 * @author capillary
 */
class ProductSearchTest extends ApiProductResourceTestBase{
    
    public function testSearchProduct()
    {
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/product/constant', true);
        $this->productResourceObj = new ProductResource();
        $ret = $this->productResourceObj->process('v1.1', 'search', array(), array('q' => 'test', ));
        
        $this->assertEquals($ret['status']['code'], 200);
        $this->assertEquals($ret['product']['results']['item'][0]['id'], 111);
        $this->assertEquals($ret['product']['results']['item'][0]['sku'], 'Test SKU');
        $this->assertTrue($ret['product']['results']['item'][0]['in_stock']);
        $this->assertEquals($ret['product']['results']['item'][0]['price'], 100);
        $this->assertEquals($ret['product']['results']['item'][0]['description'], 'Test Description');
        $this->assertEquals($ret['product']['results']['item'][0]['img_url'], 'http://testing.capillary.in/images/trouser.jpg');
        $this->assertTrue(in_array(array('name' => 'Test Attr', 'value' => 'Test Value'), $ret['product']['results']['item'][0]['attributes']['attribute']));
        $this->assertEquals($ret['product']['count'], 100);
        $this->assertEquals($ret['product']['start'], 0);
        $this->assertEquals($ret['product']['rows'], 10);
    }
    
    public function testHttpErrorFromSolr()
    {
        $context = new \Api\UnitTest\Context('solrservice');
        $context->set('response/product/httperror', true);
        $this->productResourceObj = new ProductResource();
        $ret = $this->productResourceObj->process('v1.1', 'search', array(), array('q' => 'test', ));
    }
    
    public function setUp()
    {
        $this->login('till.005', '123');
    }
}
