<?php

/*
*
* -------------------------------------------------------
* CLASSNAME:        PaymentMode
* GENERATION DATE:  10.09.2013
* CREATED BY:       Kartik Gosiya
* FOR MYSQL TABLE:  payment_type
* FOR MYSQL DB:     masters
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiPaymentModeModel
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $type;
	protected $description;
	protected $added_on;
	protected $added_by;
	protected $last_updated_on;

	protected $database; // Instance of class database
	protected $users_db;

	protected $table = 'payment_type';
	protected $logger ;

	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function ApiPaymentModeModel()
	{	

		$this->database = new Dbase( 'masters' );
		$this->users_db = new Dbase( 'users' );

		global $logger;
		$this->logger = $logger;
	}


	// **********************
	// GETTER METHODS
	// **********************


	function getId()
	{	
		return $this->id;
	}

	function getType()
	{	
		return $this->type;
	}

	function getDescription()
	{	
		return $this->description;
	}

	function getAddedOn()
	{	
		return $this->added_on;
	}

	function getAddedBy()
	{	
		return $this->added_by;
	}

	function getLastUpdatedOn()
	{	
		return $this->last_updated_on;
	}

	// **********************
	// SETTER METHODS
	// **********************


	function setId( $id )
	{
		$this->id =  $id;
	}

	function setType( $type )
	{
		$this->type =  $type;
	}

	function setDescription( $description )
	{
		$this->description =  $description;
	}

	function setAddedOn( $added_on )
	{
		$this->added_on =  $added_on;
	}

	function setAddedBy( $added_by )
	{
		$this->added_by =  $added_by;
	}

	function setLastUpdatedOn( $last_updated_on )
	{
		$this->last_updated_on =  $last_updated_on;
	}

	// **********************
	// SELECT METHOD / LOAD
	// **********************

	function load( $id )
	{

		$sql =  "SELECT * FROM payment_type WHERE id = $id";
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->type = $row->type;
		$this->description = $row->description;
		$this->added_on = $row->added_on;
		$this->added_by = $row->added_by;
		$this->last_updated_on = $row->last_updated_on;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement

		$sql =  "

			INSERT INTO payment_type 
			( 
				type,
				description,
				added_on,
				added_by,
				last_updated_on 
			) 
			VALUES 
			( 
				'$this->type',
				'$this->description',
				'$this->added_on',
				'$this->added_by',
				'$this->last_updated_on' 
			)";
		
		return $this->id = $this->database->insert( $sql );

	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO payment_type 
			( 
				id,
				type,
				description,
				added_on,
				added_by,
				last_updated_on 

			) 

			VALUES 
			( 
				'$this->id',
				'$this->type',
				'$this->description',
				'$this->added_on',
				'$this->added_by',
				'$this->last_updated_on' 

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
			UPDATE payment_type 
			SET  
				type = '$this->type',
				description = '$this->description',
				added_on = '$this->added_on',
				added_by = '$this->added_by',
				last_updated_on = '$this->last_updated_on' 
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
		$hash['type'] = $this->type;
		$hash['description'] = $this->description;
		$hash['added_on'] = $this->added_on;
		$hash['added_by'] = $this->added_by;
		$hash['last_updated_on'] = $this->last_updated_on;


		return $hash;
	}
} // class : end

?>