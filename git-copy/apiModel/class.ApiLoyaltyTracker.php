<?php

/*
*
* -------------------------------------------------------
* CLASSNAME:        LoyaltyTracker
* GENERATION DATE:  30.08.2013
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  loyalty_tracker
* FOR MYSQL DB:     user_management
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiLoyaltyTrackerModel
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $org_id;
	protected $store_id;
	protected $num_bills;
	protected $tracker_date;
	protected $sales;
	protected $footfall_count;
	protected $captured_regular_bills;
	protected $captured_not_interested_bills;
	protected $captured_enter_later_bills;
	protected $captured_pending_enter_later_bills;

	protected $database; // Instance of class database
	protected $logger;

	protected $table = 'loyalty_tracker';

	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function __construct()
	{	
		global $logger;
		$this->database = new Dbase( 'users' );
		$this->logger = $logger;
	}


	// **********************
	// GETTER METHODS
	// **********************


	function getId()
	{	
		return $this->id;
	}

	function getOrgId()
	{	
		return $this->org_id;
	}

	function getStoreId()
	{	
		return $this->store_id;
	}

	function getNumBills()
	{	
		return $this->num_bills;
	}

	function getTrackerDate()
	{	
		return $this->tracker_date;
	}

	function getSales()
	{	
		return $this->sales;
	}

	function getFootfallCount()
	{	
		return $this->footfall_count;
	}

	function getCapturedRegularBills()
	{	
		return $this->captured_regular_bills;
	}

	function getCapturedNotInterestedBills()
	{	
		return $this->captured_not_interested_bills;
	}

	function getCapturedEnterLaterBills()
	{	
		return $this->captured_enter_later_bills;
	}

	function getCapturedPendingEnterLaterBills()
	{	
		return $this->captured_pending_enter_later_bills;
	}

	// **********************
	// SETTER METHODS
	// **********************


	function setId( $id )
	{
		$this->id =  $id;
	}

	function setOrgId( $org_id )
	{
		$this->org_id =  $org_id;
	}

	function setStoreId( $store_id )
	{
		$this->store_id =  $store_id;
	}

	function setNumBills( $num_bills )
	{
		$this->num_bills =  $num_bills;
	}

	function setTrackerDate( $tracker_date )
	{
		$this->tracker_date =  $tracker_date;
	}

	function setSales( $sales )
	{
		$this->sales =  $sales;
	}

	function setFootfallCount( $footfall_count )
	{
		$this->footfall_count =  $footfall_count;
	}

	function setCapturedRegularBills( $captured_regular_bills )
	{
		$this->captured_regular_bills =  $captured_regular_bills;
	}

	function setCapturedNotInterestedBills( $captured_not_interested_bills )
	{
		$this->captured_not_interested_bills =  $captured_not_interested_bills;
	}

	function setCapturedEnterLaterBills( $captured_enter_later_bills )
	{
		$this->captured_enter_later_bills =  $captured_enter_later_bills;
	}

	function setCapturedPendingEnterLaterBills( $captured_pending_enter_later_bills )
	{
		$this->captured_pending_enter_later_bills =  $captured_pending_enter_later_bills;
	}

	// **********************
	// SELECT METHOD / LOAD
	// **********************

	function load( $id )
	{

		$sql =  "SELECT * FROM loyalty_tracker WHERE id = $id";
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->org_id = $row->org_id;
		$this->store_id = $row->store_id;
		$this->num_bills = $row->num_bills;
		$this->tracker_date = $row->tracker_date;
		$this->sales = $row->sales;
		$this->footfall_count = $row->footfall_count;
		$this->captured_regular_bills = $row->captured_regular_bills;
		$this->captured_not_interested_bills = $row->captured_not_interested_bills;
		$this->captured_enter_later_bills = $row->captured_enter_later_bills;
		$this->captured_pending_enter_later_bills = $row->captured_pending_enter_later_bills;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement

		$sql =  "

			INSERT IGNORE INTO loyalty_tracker 
			( 
				org_id,
				store_id,
				num_bills,
				tracker_date,
				sales,
				footfall_count,
				captured_regular_bills,
				captured_not_interested_bills,
				captured_enter_later_bills,
				captured_pending_enter_later_bills 
			) 
			VALUES 
			( 
				'$this->org_id',
				'$this->store_id',
				'$this->num_bills',
				'$this->tracker_date',
				'$this->sales',
				'$this->footfall_count',
				'$this->captured_regular_bills',
				'$this->captured_not_interested_bills',
				'$this->captured_enter_later_bills',
				'$this->captured_pending_enter_later_bills' 
			)";
		
		return $this->id = $this->database->insert( $sql );

	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO loyalty_tracker 
			( 
				id,
				org_id,
				store_id,
				num_bills,
				tracker_date,
				sales,
				footfall_count,
				captured_regular_bills,
				captured_not_interested_bills,
				captured_enter_later_bills,
				captured_pending_enter_later_bills 

			) 

			VALUES 
			( 
				'$this->id',
				'$this->org_id',
				'$this->store_id',
				'$this->num_bills',
				'$this->tracker_date',
				'$this->sales',
				'$this->footfall_count',
				'$this->captured_regular_bills',
				'$this->captured_not_interested_bills',
				'$this->captured_enter_later_bills',
				'$this->captured_pending_enter_later_bills' 

			)";
		
		return $this->database->update( $sql );


	}
	
	
	/**
	*
	*@param $id
	*/
	function update( $id )
	{

		$sql = " 
			UPDATE loyalty_tracker 
			SET  
				org_id = '$this->org_id',
				store_id = '$this->store_id',
				num_bills = '$this->num_bills',
				tracker_date = '$this->tracker_date',
				sales = '$this->sales',
				footfall_count = '$this->footfall_count',
				captured_regular_bills = '$this->captured_regular_bills',
				captured_not_interested_bills = '$this->captured_not_interested_bills',
				captured_enter_later_bills = '$this->captured_enter_later_bills',
				captured_pending_enter_later_bills = '$this->captured_pending_enter_later_bills' 
			WHERE id = $id ";

		return $result = $this->database->update($sql);

	}

	/**
	*
	*Returns the hash array for the object
	*
	*/
	function getHash(){

		$hash = array();
 
		$hash['id'] = $this->id;
		$hash['org_id'] = $this->org_id;
		$hash['store_id'] = $this->store_id;
		$hash['num_bills'] = $this->num_bills;
		$hash['tracker_date'] = $this->tracker_date;
		$hash['sales'] = $this->sales;
		$hash['footfall_count'] = $this->footfall_count;
		$hash['captured_regular_bills'] = $this->captured_regular_bills;
		$hash['captured_not_interested_bills'] = $this->captured_not_interested_bills;
		$hash['captured_enter_later_bills'] = $this->captured_enter_later_bills;
		$hash['captured_pending_enter_later_bills'] = $this->captured_pending_enter_later_bills;


		return $hash;
	}
} // class : end

?>