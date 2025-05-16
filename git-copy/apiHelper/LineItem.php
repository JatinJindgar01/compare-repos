<?php

/*
*
* -------------------------------------------------------
* CLASSNAME:        LineItem
* GENERATION DATE:  01.06.2012
* CREATED BY:      vishnu 
* FOR MYSQL TABLE:  loyalty_bill_lineitems
* FOR MYSQL DB:     user_management
*
*/

//**********************
// CLASS DECLARATION
//**********************

class BillLineItem
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	private $id;   // KEY ATTR. WITH AUTOINCREMENT

	private $loyalty_log_id;
	private $user_id;
	private $org_id;
	private $serial;
	private $item_code;
	private $description;
	private $rate;
	private $qty;
	private $value;
	private $discount_value;
	private $amount;
	private $store_id;
	private $inventory_item_id;
	private $inventory;
	private $inventory_info;
	private $db; // Instance of class database
	private $logger;
	private $outlier_status = 'NORMAL';
	private $table = 'loyalty_bill_lineitems';
	
	private $type ;
	private $subtype;

	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	public function __construct()
	{	

		$this->db = new Dbase( 'users' );
		$this->inventory = new InventorySubModule();
		global $logger;
		$this->logger = $logger;
	}


	// **********************
	// GETTER METHODS
	// **********************


	public function getId()
	{	
		return $this->id;
	}

	public function getLoyaltyLogId()
	{	
		return $this->loyalty_log_id;
	}

	public function getUserId()
	{	
		return $this->user_id;
	}

	public function getOrgId()
	{	
		return $this->org_id;
	}

	public function getSerial()
	{	
		return $this->serial;
	}

	public function getItemCode()
	{	
		return $this->item_code;
	}

	public function getDescription()
	{	
		return $this->description;
	}

	public function getRate()
	{	
		return $this->rate;
	}

	public function getQty()
	{	
		return $this->qty;
	}

	public function getValue()
	{	
		return $this->value;
	}

	public function getDiscountValue()
	{	
		return $this->discount_value;
	}

	public function getAmount()
	{	
		return $this->amount;
	}

	public function getStoreId()
	{	
		return $this->store_id;
	}

	public function getOutlierStatus()
	{	
		return $this->outlier_status;
	}
	
	public function getInventoryItemId()
	{	
		return $this->inventory_item_id;
	}
	
	public function getInventoryInfo(){
		return $this->inventory_info;
	}

	public function getType(){
		return $this->type;
	}
	
	public function getSubtype(){
		return $this->subtype;
	}
	
	// **********************
	// SETTER METHODS
	// **********************


	public function setId( $id )
	{
		$this->id =  $id;
	}

	public function setLoyaltyLogId( $loyalty_log_id )
	{
		$this->loyalty_log_id =  $loyalty_log_id;
	}

	public function setUserId( $user_id )
	{
		$this->user_id =  $user_id;
	}

	public function setOrgId( $org_id )
	{
		$this->org_id =  $org_id;
	}

	public function setSerial( $serial )
	{
		$this->serial =  $serial;
	}

	public function setItemCode( $item_code )
	{
		$this->item_code =  $item_code;
	}

	public function setDescription( $description )
	{
		$this->description =  $description;
	}

	public function setRate( $rate )
	{
		$this->rate =  $rate;
	}

	public function setQty( $qty )
	{
		$this->qty =  $qty;
	}

	public function setValue( $value )
	{
		$this->value =  $value;
	}

	public function setDiscountValue( $discount_value )
	{
		$this->discount_value =  $discount_value;
	}

	public function setAmount( $amount )
	{
		$this->amount =  $amount;
	}

	public function setStoreId( $store_id )
	{
		$this->store_id =  $store_id;
	}
	
	public function setOutlierStatus($outlier_status)
	{	
		$this->outlier_status = $outlier_status;
	}
	
	public function setInventoryItemId( $inventory_item_id )
	{
		$this->inventory_item_id =  $inventory_item_id;
	}
	
	public function setInventoryInfo($inventory_info)
	{
		$this->inventory_info =$inventory_info;
	}

	public function setType($type)
	{
		$this->type =$type;
	}

	public function setSubtype($subtype)
	{
		$this->type =$subtype;
	}
	
	// **********************
	// SELECT METHOD / LOAD
	// **********************

	public function load( $id )
	{

		$sql =  "SELECT * FROM loyalty_bill_lineitems WHERE id = $id";
		$row =  $this->db->query_scalar( $sql );
		
		//$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		//$row[0] = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row['id'];
		$this->loyalty_log_id = $row['loyalty_log_id'];
		$this->user_id = $row['user_id'];
		$this->org_id = $row['org_id'];
		$this->serial = $row['serial'];
		$this->item_code = $row['item_code'];
		$this->description = $row['description'];
		$this->rate = $row['rate'];
		$this->qty = $row['qty'];
		$this->value = $row['value'];
		$this->discount_value = $row['discount_value'];
		$this->amount = $row['amount'];
		$this->store_id = $row['store_id'];
		$this->inventory_item_id = $row['inventory_item_id'];
	}
	
	// **********************
	// INSERT
	// **********************

	public function insert()
	{

		$this->id = ""; // clear key for autoincrement

		$sql =  "

			INSERT INTO loyalty_bill_lineitems 
			( 
				loyalty_log_id,
				user_id,
				org_id,
				serial,
				item_code,
				description,
				rate,
				qty,
				value,
				discount_value,
				amount,
				store_id,
				inventory_item_id,
				outlier_status
			) 
			VALUES 
			( 
				'$this->loyalty_log_id',
				'$this->user_id',
				'$this->org_id',
				'$this->serial',
				'$this->item_code',
				'$this->description',
				'$this->rate',
				'$this->qty',
				'$this->value',
				'$this->discount_value',
				'$this->amount',
				'$this->store_id',
				'$this->inventory_item_id',
				'$this->outlier_status' 
			)";
		
		return $this->id = $this->db->insert( $sql );

	}
	
	// **********************
	// INSERT With Id
	// **********************


	public function insertWithId()
	{


		$sql =  "

			INSERT INTO loyalty_bill_lineitems 
			( 
				id,
				loyalty_log_id,
				user_id,
				org_id,
				serial,
				item_code,
				description,
				rate,
				qty,
				value,
				discount_value,
				amount,
				store_id,
				inventory_item_id 

			) 

			VALUES 
			( 
				'$this->id',
				'$this->loyalty_log_id',
				'$this->user_id',
				'$this->org_id',
				'$this->serial',
				'$this->item_code',
				'$this->description',
				'$this->rate',
				'$this->qty',
				'$this->value',
				'$this->discount_value',
				'$this->amount',
				'$this->store_id',
				'$this->inventory_item_id' 

			)";
		
		return $this->db->update( $sql );


	}
	
	
	/**
	*
	*@param $id
	*/
	public function update( $id )
	{

		$sql = " 
			UPDATE loyalty_bill_lineitems 
			SET  
				loyalty_log_id = '$this->loyalty_log_id',
				user_id = '$this->user_id',
				org_id = '$this->org_id',
				serial = '$this->serial',
				item_code = '$this->item_code',
				description = '$this->description',
				rate = '$this->rate',
				qty = '$this->qty',
				value = '$this->value',
				discount_value = '$this->discount_value',
				amount = '$this->amount',
				store_id = '$this->store_id',
				inventory_item_id = '$this->inventory_item_id' 
			WHERE id = $id ";

		return $result = $this->db->update($sql);

	}

	/**
	*
	*Returns the hash array for the object
	*
	*/
	public function getHash(){

		$hash = array();
 
		$hash['id'] = $this->id;
		$hash['loyalty_log_id'] = $this->loyalty_log_id;
		$hash['user_id'] = $this->user_id;
		$hash['org_id'] = $this->org_id;
		$hash['serial'] = $this->serial;
		$hash['item_code'] = $this->item_code;
		$hash['description'] = $this->description;
		$hash['rate'] = $this->rate;
		$hash['qty'] = $this->qty;
		$hash['value'] = $this->value;
		$hash['discount_value'] = $this->discount_value;
		$hash['amount'] = $this->amount;
		$hash['store_id'] = $this->store_id;
		$hash['inventory_item_id'] = $this->inventory_item_id;


		return $hash;
	}
	
	public function insertInventory(){

		$this->logger->debug("LineItem: Adding inventory information for the line item");
		$line_item = array();
		$line_item['price'] = $this->rate;
		$line_item['item_code'] = $this->item_code;
		$line_item['inventory_info'] = $this->inventory_info;
		$this->logger->debug("LineItem: Inventory Info, ".print_r($line_item,true));
		return $this->inventory->addItemToInventory($line_item);
	}
	
	public function getByLoyaltyLogId($loyalty_log_id,$org_id)
	{
			$this->logger->debug("Getting line Items for $loyalty_log_id");
			$sql = "SELECT lbl.id, lbl.serial, lbl.item_code, lbl.description, lbl.qty, lbl.rate, lbl.value,
					lbl.discount_value as discount, lbl.amount 
					FROM loyalty_bill_lineitems lbl
					WHERE lbl.org_id = '$org_id'  AND lbl.loyalty_log_id = $loyalty_log_id";
			$lbl = $db->query( $sql );
			foreach( $lbl as $key=>$li )
				{
					$sql = "SELECT iia.name , iiav.value_name as value
					FROM loyalty_bill_lineitems lbl
					LEFT JOIN inventory_masters ims ON ( `ims`.`item_sku` = `lbl`.`item_code` AND `ims`.`org_id` = '$this->org_id' ) 
					LEFT JOIN `inventory_items` `ii` ON ( `ii`.`item_id` = `ims`.`id` AND `ii`.`org_id` = `ims`.`org_id` ) 
					LEFT JOIN `inventory_item_attributes` `iia` ON ( `ii`.`attribute_id` = `iia`.`id` AND `ii`.`org_id` = `iia`.`org_id` ) 
					LEFT JOIN `inventory_item_attribute_values` `iiav` ON ( `ii`.`attribute_value_id` = `iiav`.`id` AND `iiav`.`org_id` = `ii`.`org_id` )
					WHERE lbl.id = " . $li[ 'id' ];
						
					$inventory_items = $db->query( $sql );
					unset($lbl[$key]['id']);
					$lbl[ $key ][ 'attributes' ][ 'attribute' ] = $inventory_items;
				}
			return $lbl;
	}
	
	function destruct(){
		
	}
}

?>
