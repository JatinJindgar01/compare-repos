<?php

	namespace inventoryservice\resources;

	/* Adding all these imports here so that I don't have to do so in the resource-extension classes */
	require_once $GLOBALS['THRIFT_ROOT'] . '/packages/inventory-service/inventory-service_types.php';
	require_once 'apiHelper/resourceFormatter/ResourceFormatterFactory.php';
	require_once 'apiHelper/Errors.php';
	require_once 'helper/Base.php';	
	require_once 'helper/Table.php';	
	require_once 'helper/Timer.php';	
	require_once 'helper/Dbase.php';
	require_once 'helper/Auth.php';	
	require_once 'helper/enum.php';	
	require_once 'apiController/ApiInventoryController.php';

	/**
	*  Parent class for all resources
	*  @author: Jessy <jessy.james@capillarytech.com>
	*/
	abstract class Resource {
		
		protected $logger;

		function __construct() {
			$this -> logger = new \ShopbookLogger();
		}

		abstract protected function get($orgId, $filters, $includeIds, $includeHierarchy, $includeChildren);
    	/*abstract protected function save($orgId, $data);*/
	}