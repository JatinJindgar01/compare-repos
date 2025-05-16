<?php
	
	$GLOBALS['THRIFT_ROOT'] = '/usr/share/php/capillary-libs/thrift-0.8/';
	require_once $GLOBALS['THRIFT_ROOT'] . '/Thrift.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/transport/THttpClient.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/packages/inventory-service/InventoryQueryService.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/packages/inventory-service/inventory-service_types.php';


	$socket    = new THttpClient('10.10.10.253', 80, 'inventory-service');
  	$transport = new TBufferedTransport($socket, 1024, 1024);
  	$protocol  = new TBinaryProtocol($transport);

	$transport -> open();

	try {	
		$client = new \inventoryservice\InventoryQueryServiceClient($protocol);

		$requestId = $_SERVER['UNIQUE_ID'];
		echo "<pre> UNIQUE ID: '"; print_r($requestId); echo("'--<br>");
		
		$isAlive = $client -> isAlive();
		echo "<pre> Mike check? '"; print_r($isAlive); echo("'--<br>");

		/* Attribute entity queries -- BEGIN *
		
		$childrenFilters = new \inventoryservice\ChildrenFilters();

		$attributes = $client -> getAttributesByIds(50038, array(332), $requestId, true, true, $childrenFilters);
		echo "<pre> Attribute :: Single ID: '"; print_r($attributes); echo("'--<br>");

		$childrenFilters -> childrenLimit = 5;
		$childrenFilters -> childrenOffset = 5;

		$attributes = $client -> getAttributesByIds(50038, array(332, 333), $requestId, true, true, $childrenFilters);
		echo "<pre> Attribute :: Multiple IDs: '"; print_r($attributes); echo("'--<br>");

		$attributes = $client -> getAttributesByNames(50038, array('firstName0_265467'), $requestId, true, true, $childrenFilters);
		echo "<pre> Attribute :: Single Name: '"; print_r($attributes); echo("'--<br>");
		
		$attributes = $client -> getAttributesByNames(50038, array('吞玻璃而傷', 'firstName0_265467'), $requestId, true, true, $childrenFilters);
		echo "<pre> Attribute :: Multiple Names: '"; print_r($attributes); echo("'--<br>");

		$filters = new \inventoryservice\LimitFilters();

		//$attributes = $client -> getAllAttributes(50038, $requestId, $filters, true, true, $childrenFilters);
		//echo "<pre> Attribute :: ALL: '"; print_r($attributes); echo("'--<br>");

		$filters -> limit = 2;
		$attributes = $client -> getAllAttributes(0, $requestId, $filters, true, true, $childrenFilters);
		echo "<pre> Attribute :: ALL With Limit Filter: '"; print_r($attributes); echo("'--<br>");
		
		$filters -> offset = 0;
		$attributes = $client -> getAllAttributes(50038, $requestId, $filters, true, true, $childrenFilters);
		echo "<pre> Attribute :: ALL With Offset Filter: '"; print_r($attributes); echo("'--<br>");

		/* Attribute entity queries -- END */

		/* AttributeValue entity queries -- BEGIN *

		$attributeValues = $client -> getAttributeValuesByIdsAndAttributeIds(
			0, array(1184995, 1184994), array(337, 338), $requestId, true);
		echo "<pre> AttributeValue :: Value IDs and Attribute IDs: '"; print_r($attributeValues); echo("'--<br>");

		$attributeValues = $client -> getAttributeValuesByIdsAndAttributeNames(
			0, array(1184995, 1184994), array('test_item', 'saurabh'), $requestId, true);
		echo "<pre> AttributeValue :: Value IDs and Attribute Names: '"; print_r($attributeValues); echo("'--<br>");

		$attributeValues = $client -> getAttributeValuesByNamesAndAttributeIds(
			0, array('SMALL', 'six'), array(337, 338), $requestId, true);
		echo "<pre> AttributeValue :: Value Names and Attribute IDs: '"; print_r($attributeValues); echo("'--<br>");

		$attributeValues = $client -> getAttributeValuesByNamesAndAttributeNames(
			0, array('SMALL', 'six'), array('test_item', 'saurabh'), $requestId, true);
		echo "<pre> AttributeValue :: Value Names and Attribute Names: '"; print_r($attributeValues); echo("'--<br>");

		$filters = new \inventoryservice\LimitFilters();

		$attributeValues = $client -> getAllAttributeValues(0, $requestId, $filters, true);
		echo "<pre> AttributeValue :: ALL: '"; print_r($attributeValues); echo("'--<br>");

		$filters -> limit = 5;
		$attributeValues = $client -> getAllAttributeValues(50038, $requestId, $filters, true);
		echo "<pre> AttributeValue :: ALL With Limit Filter: '"; print_r($attributeValues); echo("'--<br>");
		
		$filters -> offset = 0;
		$attributeValues = $client -> getAllAttributeValues(50038, $requestId, $filters, true);
		echo "<pre> AttributeValue :: ALL With Offset Filter: '"; print_r($attributeValues); echo("'--<br>");

		/* AttributeValue entity queries -- END */

		/* Brand entity queries -- BEGIN *

		$brands = $client -> getBrandsByIds(50038, array(11), $requestId, true, true);
		echo "<pre> Brand :: Single ID: '"; print_r($brands); echo("'--<br>");

		$brands = $client -> getBrandsByIds(50038, array(10, 11, 13), $requestId, true, true);
		echo "<pre> Brand :: Multiple IDs: '"; print_r($brands); echo("'--<br>");

		$brands = $client -> getBrandsByNames(50038, array('firstName1_792362'), $requestId, true, true);
		echo "<pre> Brand :: Single Name: '"; print_r($brands); echo("'--<br>");
		
		$brands = $client -> getBrandsByNames(50038, array('brands', 'firstName1_792362'), $requestId, true, true);
		echo "<pre> Brand :: Multiple Names: '"; print_r($brands); echo("'--<br>");

		$filters = new \inventoryservice\BrandFilters();

		$brands = $client -> getAllBrands(50038, $requestId, $filters, true, true);
		echo "<pre> Brand :: ALL: '"; print_r($brands); echo("'--<br>");

		$filters -> parentId = 10;
		$brands = $client -> getAllBrands(50038, $requestId, $filters, true, true);
		echo "<pre> Brand :: ALL With ParentId Filter: '"; print_r($brands); echo("'--<br>");

		$filters -> parentNames = array('firstName0_617963');
		$brands = $client -> getAllBrands(50038, $requestId, $filters, true, true);
		echo "<pre> Brand :: ALL With ParentNames Filter: '"; print_r($brands); echo("'--<br>");

		$filters -> catalog = array('firstName0_617963');
		$brands = $client -> getAllBrands(50038, $requestId, $filters, true, true);
		echo "<pre> Brand :: ALL With Catalog Filter: '"; print_r($brands); echo("'--<br>");

		$filters -> limit = 5;
		$brands = $client -> getAllBrands(50038, $requestId, $filters, true, true);
		echo "<pre> Brand :: ALL With Limit Filter: '"; print_r($brands); echo("'--<br>");
		
		$filters -> offset = 0;
		$brands = $client -> getAllBrands(50038, $requestId, $filters, true, true);
		echo "<pre> Brand :: ALL With Offset Filter: '"; print_r($brands); echo("'--<br>");

		/* Brand entity queries -- END */

		/* Category entity queries -- BEGIN *

		$categories = $client -> getCategoriesByIds(50038, array(10), $requestId, true, true, true);
		echo "<pre> Category ::  Single ID: '"; print_r($categories); echo("'--<br>");

		$categories = $client -> getCategoriesByIds(50038, array(8, 9, 10), $requestId, true, true, true);
		echo "<pre> Category ::  Multiple IDs: '"; print_r($categories); echo("'--<br>");

		$categories = $client -> getCategoriesByNames(50038, array('firstName1_015454'), $requestId, true, true, true);
		echo "<pre> Category ::  Single Name: '"; print_r($categories); echo("'--<br>");
		
		$categories = $client -> getCategoriesByNames(50038, array('firstName1_015454', 'firstName'), $requestId, true, true, true);
		echo "<pre> Category ::  Multiple Names: '"; print_r($categories); echo("'--<br>");

		$filters = new \inventoryservice\CategoryFilters();

		$categories = $client -> getAllCategories(50038, $requestId, $filters, true, true, true);
		echo "<pre> Category ::  ALL: '"; print_r($categories); echo("'--<br>");

		$filters -> parentId = 9;
		$categories = $client -> getAllCategories(50038, $requestId, $filters, true, true, true);
		echo "<pre> Category ::  ALL With ParentId Filter: '"; print_r($categories); echo("'--<br>");

		$filters -> parentNames = array('firstName0_738794');
		$categories = $client -> getAllCategories(50038, $requestId, $filters, true, true, true);
		echo "<pre> Category ::  ALL With ParentNames Filter: '"; print_r($categories); echo("'--<br>");

		$filters -> catalog = array('firstName0_738794');
		$categories = $client -> getAllCategories(50038, $requestId, $filters, true, true, true);
		echo "<pre> Category ::  ALL With Catalog Filter: '"; print_r($categories); echo("'--<br>");

		$filters -> limit = 5;
		$categories = $client -> getAllCategories(50038, $requestId, $filters, true, true, true);
		echo "<pre> Category ::  ALL With Limit Filter: '"; print_r($categories); echo("'--<br>");
		
		$filters -> offset = 0;
		$categories = $client -> getAllCategories(50038, $requestId, $filters, true, true, true);
		echo "<pre> Category ::  ALL With Offset Filter: '"; print_r($categories); echo("'--<br>");

		/* Category entity queries -- END */

		/* Color entity queries -- BEGIN *

		$color = $client -> getColorByPalette(50038, 16776960, $requestId, true);
		echo "<pre> Color ::  Palette number: '"; print_r($color); echo("'--<br>");

		$filters = new \inventoryservice\LimitFilters();

		$colors = $client -> getAllColors(50038, $requestId, $filters, true);
		echo "<pre> Color ::  ALL: '"; print_r($colors); echo("'--<br>");

		$filters -> limit = 5;
		$colors = $client -> getAllColors(50038, $requestId, $filters, true);
		echo "<pre> Color ::  ALL With Limit Filter: '"; print_r($colors); echo("'--<br>");
		
		$filters -> offset = 0;
		$colors = $client -> getAllColors(50038, $requestId, $filters, true);
		echo "<pre> Color ::  ALL With Offset Filter: '"; print_r($colors); echo("'--<br>");

		/* Color entity queries -- END */

		/* MetaSize entity queries -- BEGIN *

		$metaSizes = $client -> getMetaSizesByIds(50038, array(16), $requestId, true);
		echo "<pre> MetaSize :: Single ID: '"; print_r($metaSizes); echo("'--<br>");

		$metaSizes = $client -> getMetaSizesByIds(50038, array(11, 12, 13, 14), $requestId, true);
		echo "<pre> MetaSize :: Multiple IDs: '"; print_r($metaSizes); echo("'--<br>");

		$filters = new \inventoryservice\MetaSizeFilters();

		$metaSizes = $client -> getAllMetaSizes(50038, $requestId, $filters, true);
		echo "<pre> MetaSize :: ALL: '"; print_r($metaSizes); echo("'--<br>");

		$filters -> type = 'Mens Shirt';
		$metaSizes = $client -> getAllMetaSizes(50038, $requestId, $filters, true);
		echo "<pre> MetaSize :: ALL With Type Filter: '"; print_r($metaSizes); echo("'--<br>");

		$filters -> sizeFamily = 'US';
		$metaSizes = $client -> getAllMetaSizes(50038, $requestId, $filters, true);
		echo "<pre> MetaSize :: ALL With SizeFamily Filter: '"; print_r($metaSizes); echo("'--<br>");

		$filters -> limit = 5;
		$metaSizes = $client -> getAllMetaSizes(50038, $requestId, $filters, true);
		echo "<pre> MetaSize :: ALL With Limit Filter: '"; print_r($metaSizes); echo("'--<br>");
		
		$filters -> offset = 0;
		$metaSizes = $client -> getAllMetaSizes(50038, $requestId, $filters, true);
		echo "<pre> MetaSize :: ALL With Offset Filter: '"; print_r($metaSizes); echo("'--<br>");

		/* MetaSize entity queries -- END */

		/* Size entity queries -- BEGIN *

		$sizes = $client -> getSizesByIds(50038, array(10), $requestId, true);
		echo "<pre> Size :: Single ID: '"; print_r($sizes); echo("'--<br>");

		$sizes = $client -> getSizesByIds(50038, array(2, 3, 4), $requestId, true);
		echo "<pre> Size :: Multiple IDs: '"; print_r($sizes); echo("'--<br>");

		$size = $client -> getSizeByName(50038, 'abc', $requestId, true);
		echo "<pre> Size :: Single Name: '"; print_r($size); echo("'--<br>");

		$filters = new \inventoryservice\SizeFilters();

		$sizes = $client -> getAllSizes(50038, $requestId, $filters, true);
		echo "<pre> Size :: ALL: '"; print_r($sizes); echo("'--<br>");

		$filters -> canonicalName = 'M'; // Canonical name is code of parent (meta-size) in the DB
		$sizes = $client -> getAllSizes(50038, $requestId, $filters, true);
		echo "<pre> Size :: ALL With CanonicalName Filter: '"; print_r($sizes); echo("'--<br>");

		$filters -> type = 'MENS'; // Type is type of parent (meta-size) in the DB
		$sizes = $client -> getAllSizes(50038, $requestId, $filters, true);
		echo "<pre> Size :: ALL With Type Filter: '"; print_r($sizes); echo("'--<br>");

		$filters -> sizeFamily = 'US'; // Size-family is size-family of parent (meta-size) in the DB
		$sizes = $client -> getAllSizes(50038, $requestId, $filters, true);
		echo "<pre> Size :: ALL With SizeFamily Filter: '"; print_r($sizes); echo("'--<br>");

		$filters -> name = 'abc'; // Name is code of size in the DB
		$sizes = $client -> getAllSizes(50038, $requestId, $filters, true);
		echo "<pre> Size :: ALL With Name Filter: '"; print_r($sizes); echo("'--<br>");

		$filters -> limit = 25;
		$sizes = $client -> getAllSizes(50038, $requestId, $filters, true);
		echo "<pre> Size :: ALL With Limit Filter: '"; print_r($sizes); echo("'--<br>");
		
		$filters -> offset = 0;
		$sizes = $client -> getAllSizes(50038, $requestId, $filters, true);
		echo "<pre> Size :: ALL With Offset Filter: '"; print_r($sizes); echo("'--<br>");

		/* Size entity queries -- END */

		/* Style entity queries -- BEGIN *

		$styles = $client -> getStylesByIds(50038, array(63), $requestId, true);
		echo "<pre> Style :: Single ID: '"; print_r($styles); echo("'--<br>");

		$styles = $client -> getStylesByIds(50038, array(62, 63, 64), $requestId, true);
		echo "<pre> Style :: Multiple IDs: '"; print_r($styles); echo("'--<br>");

		$styles = $client -> getStylesByNames(50038, array('firstName0_178907'), $requestId, true);
		echo "<pre> Style :: Single Name: '"; print_r($styles); echo("'--<br>");
		
		$styles = $client -> getStylesByNames(50038, array('919806725683', 'firstName0_178907'), $requestId, true);
		echo "<pre> Style :: Multiple Names: '"; print_r($styles); echo("'--<br>");

		$filters = new \inventoryservice\LimitFilters();

		$styles = $client -> getAllStyles(50038, $requestId, $filters, true);
		echo "<pre> Style :: ALL: '"; print_r($styles); echo("'--<br>");

		$filters -> limit = 10;
		$styles = $client -> getAllStyles(50038, $requestId, $filters, true);
		echo "<pre> Style :: ALL With Limit Filter: '"; print_r($styles); echo("'--<br>");
		
		$filters -> offset = 0;
		$styles = $client -> getAllStyles(50038, $requestId, $filters, true);
		echo "<pre> Style :: ALL With Offset Filter: '"; print_r($styles); echo("'--<br>");

		/* Style entity queries -- END */

		/* Product entity queries -- BEGIN */

		$products = $client -> getProductsByIds(50038, array(705872), $requestId, true, true);
		echo "<pre> Product :: Single ID: '"; print_r($products); echo("'--<br>");

		/*$products = $client -> getProductsByIds(50038, array(538867, 705872), $requestId, true, true);
		echo "<pre> Product :: Multiple IDs: '"; print_r($products); echo("'--<br>");

		$products = $client -> getProductsBySkus(50038, array('SKUIT2'), $requestId, true, true);
		echo "<pre> Product :: Single SKUs: '"; print_r($products); echo("'--<br>");
		
		$products = $client -> getProductsBySkus(50038, array('SKUIT4', 'SKUIT2'), $requestId, true, true);
		echo "<pre> Product :: Multiple SKUs: '"; print_r($products); echo("'--<br>");

		/* Product entity queries -- END */

		$transport -> close();

	} catch(Exception $e) {
		echo "<pre>'"; print_r($e); die("'--");
		$transport -> close();
	}