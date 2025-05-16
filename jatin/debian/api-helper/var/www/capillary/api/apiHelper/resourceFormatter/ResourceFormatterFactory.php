<?php

include_once "apiHelper/resourceFormatter/BaseFormatter.php"; 


class ResourceFormatterFactory{
	
	/**
	 * @param string $type
	 * @return BaseApiFormatter
	 */
	public static function getInstance($type)
	{
		switch (strtolower($type))
		{
			case 'tender':
				include_once 'apiHelper/resourceFormatter/TenderFormatter.php';
				return new TenderFormatter();

			case 'tenderattribute':
				include_once 'apiHelper/resourceFormatter/TenderAttributeFormatter.php';
				return new TenderAttributeFormatter();
				
			case 'orgtender':
				include_once 'apiHelper/resourceFormatter/OrgTenderFormatter.php';
				return new OrgTenderFormatter();
				
			case 'orgtenderattribute':
				include_once 'apiHelper/resourceFormatter/OrgTenderAttributeFormatter.php';
				return new OrgTenderAttributeFormatter();
			
			case 'inventorystyle':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryStyleFormatter.php';
				return new InventoryStyleFormatter();
				
			case 'inventorycategory':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryCategoryFormatter.php';
				return new InventoryCategoryFormatter();
				
			case 'inventorybrand':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryBrandFormatter.php';
				return new InventoryBrandFormatter();
			
			case 'inventorysize':
				include_once 'apiHelper/resourceFormatter/inventory/InventorySizeFormatter.php';
				return new InventorySizeFormatter();
			
			case 'inventorymaster':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryMasterFormatter.php';
				return new InventoryMasterFormatter();
			
			case 'inventorycolor':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryColorFormatter.php';
				return new InventoryColorFormatter();
			
			case 'inventorymetasize':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryMetaSizeFormatter.php';
				return new InventoryMetaSizeFormatter();
			
			case 'inventoryattribute':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryAttributeFormatter.php';
				return new InventoryAttributeFormatter();
			
			case 'inventoryattributevalue':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryAttributeValueFormatter.php';
				return new InventoryAttributeValueFormatter();
			
			case 'inventoryattributegetapi':
				include_once 'apiHelper/resourceFormatter/inventory/InventoryAttributeGetApiFormatter.php';
				return new InventoryAttributeGetApiFormatter();
			case 'orgcurrency':
				include_once 'apiHelper/resourceFormatter/organization/OrganizationCurrencyFormatter.php';
				return new OrganizationCurrencyFormatter(); 
		}
	}
	
}