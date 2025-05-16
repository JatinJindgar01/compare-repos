<?php
	
	namespace inventoryservice;
	
	/**
	*  Thrift server for inventory-service 
	*  @author: Jessy James<jessy.james@capillarytech.com>
	*/

	set_include_path(get_include_path() . ':' . $_ENV['DH_WWW'] . '/api');

	$GLOBALS['THRIFT_ROOT'] = '/usr/share/php/capillary-libs/thrift-0.8/';
	require_once $GLOBALS['THRIFT_ROOT'] . '/Thrift.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TPhpStream.php';
	require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/packages/inventory-service/InventoryQueryService.php';
	require_once 'helper/ShopbookLogger.php';
	require_once 'resources/AttributeResource.php';
	require_once 'resources/AttributeValueResource.php';
	require_once 'resources/BrandResource.php';
	require_once 'resources/CategoryResource.php';
	require_once 'resources/ColorResource.php';
	require_once 'resources/MetaSizeResource.php';
	require_once 'resources/SizeResource.php';
	require_once 'resources/StyleResource.php';
	require_once 'resources/ProductResource.php';

	use \inventoryservice\resources as resources;

	$logger = new \ShopbookLogger();

	class InventoryQueryServiceHandler implements InventoryQueryServiceIf {
		
		private $brand;

		function __construct() {
			$this -> attribute = new resources\AttributeResource;
			$this -> attributeValue = new resources\AttributeValueResource;
			$this -> brand = new resources\BrandResource;
			$this -> category = new resources\CategoryResource;
			$this -> color = new resources\ColorResource;
			$this -> metaSize = new resources\MetaSizeResource;
			$this -> size = new resources\SizeResource;
			$this -> style = new resources\StyleResource;
			$this -> product = new resources\ProductResource;
		}

		public function isAlive() {
			global $logger;
			$logger -> debug("InventoryQueryServiceHandler -> isAlive(): Alive and kicking!" );
			return true;
		}

		public function getAttributesByIds($orgId, $ids, $uniqueId, $includeIds, $includeChildren, $childrenFilters) {
			return $this -> attribute -> getByIds($orgId, $ids, $uniqueId, $includeIds, $includeChildren, $childrenFilters);
		}
		public function getAttributesByNames($orgId, $names, $uniqueId, $includeIds, $includeChildren, $childrenFilters) {
			return $this -> attribute -> getByNames($orgId, $names, $uniqueId, $includeIds, $includeChildren, $childrenFilters);
		}
		public function getAllAttributes($orgId, $uniqueId, $limitFilters, $includeIds, $includeChildren, $childrenFilters) {
			return $this -> attribute -> getAll($orgId, $uniqueId, $limitFilters, $includeIds, $includeChildren, $childrenFilters);
		}

		public function getAttributeValuesByIdsAndAttributeIds($orgId, $ids, $attributeIds, $uniqueId, $includeIds) {
			return $this -> attributeValue -> getByIdsAndParentIds($orgId, $ids, $attributeIds, $uniqueId, $includeIds);
		}
		public function getAttributeValuesByIdsAndAttributeNames($orgId, $ids, $attributeNames, $uniqueId, $includeIds) {
			return $this -> attributeValue -> getByIdsAndParentNames($orgId, $ids, $attributeNames, $uniqueId, $includeIds);
		}
		public function getAttributeValuesByNamesAndAttributeIds($orgId, $names, $attributeIds, $uniqueId, $includeIds) {
			return $this -> attributeValue -> getByNamesAndParentIds($orgId, $names, $attributeIds, $uniqueId, $includeIds);
		}
		public function getAttributeValuesByNamesAndAttributeNames($orgId, $names, $attributeNames, $uniqueId, $includeIds) {
			return $this -> attributeValue -> getByNamesAndParentIds($orgId, $names, $attributeNames, $uniqueId, $includeIds);
		}
		public function getAllAttributeValues($orgId, $uniqueId, $filters, $includeIds) {
			return $this -> attributeValue -> getAll($orgId, $uniqueId, $filters, $includeIds);
		}

		public function getBrandsByIds($orgId, $ids, $uniqueId, $includeIds, $includeHierarchy) {
			return $this -> brand -> getByIds($orgId, $ids, $uniqueId, $includeIds, $includeHierarchy);
		}
		public function getBrandsByNames($orgId, $names, $uniqueId, $includeIds, $includeHierarchy) {
			return $this -> brand -> getByNames($orgId, $names, $uniqueId, $includeIds, $includeHierarchy);
		}
		public function getAllBrands($orgId, $uniqueId, $filters, $includeIds, $includeHierarchy) {
			return $this -> brand -> getAll($orgId, $uniqueId, $filters, $includeIds, $includeHierarchy);
		}

		public function getCategoriesByIds($orgId, $ids, $uniqueId, $includeIds, $includeChildren, $includeHierarchy) {
			return $this -> category -> getByIds($orgId, $ids, $uniqueId, $includeIds, $includeChildren, $includeHierarchy);
		}
		public function getCategoriesByNames($orgId, $names, $uniqueId, $includeIds, $includeChildren, $includeHierarchy) {
			return $this -> category -> getByNames($orgId, $names, $uniqueId, $includeIds, $includeChildren, $includeHierarchy);
		}
		public function getAllCategories($orgId, $uniqueId, $filters, $includeIds, $includeChildren, $includeHierarchy) {
			return $this -> category -> getAll($orgId, $uniqueId, $filters, $includeIds, $includeChildren, $includeHierarchy);
		}

		public function getColorByPalette($orgId, $palette, $uniqueId, $includeId) {
			return $this -> color -> getByPalette($orgId, $palette, $uniqueId, $includeId);
		}
		public function getAllColors($orgId, $uniqueId, $filters, $includeIds) {
			return $this -> color -> getAll($orgId, $uniqueId, $filters, $includeIds);
		}

		public function getMetaSizesByIds($orgId, $ids, $uniqueId, $includeIds) {
			return $this -> metaSize -> getByIds($orgId, $ids, $uniqueId, $includeIds);
		}
		public function getAllMetaSizes($orgId, $uniqueId, $filters, $includeIds) {
			return $this -> metaSize -> getAll($orgId, $uniqueId, $filters, $includeIds);
		}

		public function getSizesByIds($orgId, $ids, $uniqueId, $includeIds) {
			return $this -> size -> getByIds($orgId, $ids, $uniqueId, $includeIds);
		}
		public function getSizeByName($orgId, $name, $uniqueId, $includeId) {
			return $this -> size -> getByName($orgId, $name, $uniqueId, $includeId);
		}
		public function getAllSizes($orgId, $uniqueId, $filters, $includeIds) {
			return $this -> size -> getAll($orgId, $uniqueId, $filters, $includeIds);
		}

		public function getStylesByIds($orgId, $ids, $uniqueId, $includeIds) {
			return $this -> style -> getByIds($orgId, $ids, $uniqueId, $includeIds);
		}
		public function getStylesByNames($orgId, $names, $uniqueId, $includeIds) {
			return $this -> style -> getByNames($orgId, $names, $uniqueId, $includeIds);
		}
		public function getAllStyles($orgId, $uniqueId, $filters, $includeIds) {
			return $this -> style -> getAll($orgId, $uniqueId, $filters, $includeIds);
		}

		public function getProductsByIds($orgId, $ids, $uniqueId, $includeIds, $includeHierarchy) {
			return $this -> product -> getByIds($orgId, $ids, $uniqueId, $includeIds, $includeHierarchy);
		}
		public function getProductsBySkus($orgId, $skus, $uniqueId, $includeIds, $includeHierarchy) {
			return $this -> product -> getBySkus($orgId, $skus, $uniqueId, $includeIds, $includeHierarchy);
		}
	}

	header('Content-Type', 'application/x-thrift');

	$handler = new InventoryQueryServiceHandler();
	$processor = new InventoryQueryServiceProcessor($handler);
	
	$transport = new \TBufferedTransport(new \TPhpStream(\TPhpStream::MODE_R | \TPhpStream::MODE_W));
	$protocol = new \TBinaryProtocol($transport, true, true);

	$transport -> open();
	//Takes in input and output, both of which happen to be the same in our case
	$processor -> process($protocol, $protocol); 
	$transport -> close();
