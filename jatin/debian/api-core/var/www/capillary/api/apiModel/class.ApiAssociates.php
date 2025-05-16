<?php

/*
*
* -------------------------------------------------------
* CLASSNAME:        Associates
* GENERATION DATE:  08.09.2012
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  associates
* FOR MYSQL DB:     masters
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiAssociatesModel
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $org_id;
	protected $associate_code;
	protected $firstname;
	protected $lastname;
	protected $mobile;
	protected $email;
	protected $store_id;
	protected $store_code;
	protected $updated_by;
	protected $updated_on;
	protected $added_on;
	protected $added_by;
	protected $is_active;

	protected $database; // Instance of class database

	protected $table = 'associates';

	protected $logger;
	protected $currentuser, $currentorg;
	private $current_org_id;
	
	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function ApiAssociatesModel()
	{	
		global $currentorg, $currentuser, $logger;
		$this->database = new Dbase( 'masters' );

		$this->org_id = $currentorg->org_id;
		$this->currentorg = $currentorg;
		$this->currentuser = $currentuser;
		$this->current_org_id = $currentorg->org_id;
		$this->logger = &$logger;
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

	function getAssociateCode()
	{	
		return $this->associate_code;
	}

	function getFirstname()
	{	
		return $this->firstname;
	}

	function getLastname()
	{	
		return $this->lastname;
	}

	function getMobile()
	{	
		return $this->mobile;
	}

	function getEmail()
	{	
		return $this->email;
	}

	function getStoreId()
	{	
		return $this->store_id;
	}

	function getStoreCode()
	{	
		return $this->store_code;
	}

	function getUpdatedBy()
	{	
		return $this->updated_by;
	}

	function getUpdatedOn()
	{	
		return $this->updated_on;
	}

	function getAddedOn()
	{	
		return $this->added_on;
	}

	function getAddedBy()
	{	
		return $this->added_by;
	}

	function getIsActive()
	{	
		return $this->is_active;
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

	function setAssociateCode( $associate_code )
	{
		$this->associate_code =  $associate_code;
	}

	function setFirstname( $firstname )
	{
		$this->firstname =  $firstname;
	}

	function setLastname( $lastname )
	{
		$this->lastname =  $lastname;
	}

	function setMobile( $mobile )
	{
		$this->mobile =  $mobile;
	}

	function setEmail( $email )
	{
		$this->email =  $email;
	}

	function setStoreId( $store_id )
	{
		$this->store_id =  $store_id;
	}

	function setStoreCode( $store_code )
	{
		$this->store_code =  $store_code;
	}

	function setUpdatedBy( $updated_by )
	{
		$this->updated_by =  $updated_by;
	}

	function setUpdatedOn( $updated_on )
	{
		$this->updated_on =  $updated_on;
	}

	function setAddedOn( $added_on )
	{
		$this->added_on =  $added_on;
	}

	function setAddedBy( $added_by )
	{
		$this->added_by =  $added_by;
	}

	function setIsActive( $is_active )
	{
		$this->is_active =  $is_active;
	}

	// **********************
	// SELECT METHOD / LOAD
	// **********************

	function load( $id )
	{

		$sql =  "SELECT * FROM associates WHERE id = $id";
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->org_id = $row->org_id;
		$this->associate_code = $row->associate_code;
		$this->firstname = $row->firstname;
		$this->lastname = $row->lastname;
		$this->mobile = $row->mobile;
		$this->email = $row->email;
		$this->store_id = $row->store_id;
		$this->store_code = $row->store_code;
		$this->updated_by = $row->updated_by;
		$this->updated_on = $row->updated_on;
		$this->added_on = $row->added_on;
		$this->added_by = $row->added_by;
		$this->is_active = $row->is_active;
	}
	
	/**
	 * 
	 * @param unknown_type $code
	 */
	function loadFromCode($code)
	{
		$safe_code = Util::mysqlEscapeString($code);
		$sql =  "SELECT * FROM associates WHERE associate_code = '$safe_code' AND org_id = ".$this->currentorg->org_id;
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->org_id = $row->org_id;
		$this->associate_code = $row->associate_code;
		$this->firstname = $row->firstname;
		$this->lastname = $row->lastname;
		$this->mobile = $row->mobile;
		$this->email = $row->email;
		$this->store_id = $row->store_id;
		$this->store_code = $row->store_code;
		$this->updated_by = $row->updated_by;
		$this->updated_on = $row->updated_on;
		$this->added_on = $row->added_on;
		$this->added_by = $row->added_by;
		$this->is_active = $row->is_active;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement

		$sql =  "

			INSERT INTO associates 
			( 
				org_id,
				associate_code,
				firstname,
				lastname,
				mobile,
				email,
				store_id,
				store_code,
				updated_by,
				updated_on,
				added_on,
				added_by,
				is_active 
			) 
			VALUES 
			( 
				'$this->org_id',
				'$this->associate_code',
				'$this->firstname',
				'$this->lastname',
				'$this->mobile',
				'$this->email',
				'$this->store_id',
				'$this->store_code',
				'$this->updated_by',
				'$this->updated_on',
				'$this->added_on',
				'$this->added_by',
				'$this->is_active' 
			)";
		
		return $this->id = $this->database->insert( $sql );

	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO associates 
			( 
				id,
				org_id,
				associate_code,
				firstname,
				lastname,
				mobile,
				email,
				store_id,
				store_code,
				updated_by,
				updated_on,
				added_on,
				added_by,
				is_active 

			) 

			VALUES 
			( 
				'$this->id',
				'$this->org_id',
				'$this->associate_code',
				'$this->firstname',
				'$this->lastname',
				'$this->mobile',
				'$this->email',
				'$this->store_id',
				'$this->store_code',
				'$this->updated_by',
				'$this->updated_on',
				'$this->added_on',
				'$this->added_by',
				'$this->is_active' 

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
			UPDATE associates 
			SET  
				org_id = '$this->org_id',
				firstname = '$this->firstname',
				lastname = '$this->lastname',
				mobile = '$this->mobile',
				store_id = '$this->store_id',
				store_code = '$this->store_code',
				updated_by = '$this->updated_by',
				updated_on = '$this->updated_on',
				is_active = '$this->is_active' 
			WHERE id = $id 
			AND org_id = $this->current_org_id ";

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
		$hash['associate_code'] = $this->associate_code;
		$hash['firstname'] = $this->firstname;
		$hash['lastname'] = $this->lastname;
		$hash['mobile'] = $this->mobile;
		$hash['email'] = $this->email;
		$hash['store_id'] = $this->store_id;
		$hash['store_code'] = $this->store_code;
		$hash['updated_by'] = $this->updated_by;
		$hash['updated_on'] = $this->updated_on;
		$hash['added_on'] = $this->added_on;
		$hash['added_by'] = $this->added_by;
		$hash['is_active'] = $this->is_active;


		return $hash;
	}
	
	/**
	 * 
	 * Getting Associate Activity Based On Associate Id.
	 * @param unknown_type $assoc_id
	 */
	public function getAssociateActivityById( $assoc_id ){
		
		$sql = "SELECT * FROM user_management.`assoc_activity` 
				WHERE `assoc_id` = '$assoc_id' 
				AND `org_id` = $this->org_id";
		
		return $this->database->query( $sql );
	}
	
	public function getAssociateByEmail( $email ){
		
		$sql = "SELECT * FROM masters.`associates` 
				WHERE `email` = '$email' 
				AND `org_id` = '$this->org_id'";
		
		return $this->database->query( $sql );
	}
} // class : end
?>