<?php

/*
*
* -------------------------------------------------------
* CLASSNAME:        CommunicationTemplate
* GENERATION DATE:  14.10.2012
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  communication_templates
* FOR MYSQL DB:     masters
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiCommunicationTemplateModel
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $org_id;
	protected $store_id;
	protected $title;
	protected $type;
	protected $subject;
	protected $body;
	protected $is_editable;
	protected $last_updated_by;
	protected $last_updated_on;

	protected $database; // Instance of class database

	protected $table = 'communication_templates';

	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function ApiCommunicationTemplateModel()
	{	

		$this->database = new Dbase( 'masters' );

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

	function getTitle()
	{	
		return $this->title;
	}

	function getType()
	{	
		return $this->type;
	}

	function getSubject()
	{	
		return $this->subject;
	}

	function getBody()
	{	
		return $this->body;
	}

	function getLastUpdatedBy()
	{	
		return $this->last_updated_by;
	}

	function getLastUpdatedOn()
	{	
		return $this->last_updated_on;
	}
	
	function getIsEditable()
	{
		return $this->is_editable;
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

	function setTitle( $title )
	{
		$this->title =  $title;
	}

	function setType( $type )
	{
		$this->type =  $type;
	}

	function setSubject( $subject )
	{
		$this->subject =  $subject;
	}

	function setBody( $body )
	{
		$this->body =  $body;
	}

	function setLastUpdatedBy( $last_updated_by )
	{
		$this->last_updated_by =  $last_updated_by;
	}

	function setLastUpdatedOn( $last_updated_on )
	{
		$this->last_updated_on =  $last_updated_on;
	}
	
	function setIsEditable( $is_editable )
	{
		$this->is_editable = $is_editable;
	}

	// **********************
	// SELECT METHOD / LOAD
	// **********************

	function load( $id )
	{

		$sql =  "SELECT * FROM communication_templates WHERE id = $id";
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->org_id = $row->org_id;
		$this->store_id = $row->store_id;
		$this->title = $row->title;
		$this->type = $row->type;
		$this->subject = $row->subject;
		$this->body = $row->body;
		$this->last_updated_by = $row->last_updated_by;
		$this->last_updated_on = $row->last_updated_on;
		$this->is_editable = $row->is_editable;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement

		$sql =  "

			INSERT INTO communication_templates 
			( 
				org_id,
				store_id,
				title,
				type,
				subject,
				body,
				last_updated_by,
				last_updated_on,
				is_editable 
			) 
			VALUES 
			( 
				'$this->org_id',
				'$this->store_id',
				'$this->title',
				'$this->type',
				'$this->subject',
				'$this->body',
				'$this->last_updated_by',
				'$this->last_updated_on',
				'$this->is_editable' 
			)";
		
		return $this->id = $this->database->insert( $sql );

	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO communication_templates 
			( 
				id,
				org_id,
				store_id,
				title,
				type,
				subject,
				body,
				last_updated_by,
				last_updated_on, 
				is_editible
			) 

			VALUES 
			( 
				'$this->id',
				'$this->org_id',
				'$this->store_id',
				'$this->title',
				'$this->type',
				'$this->subject',
				'$this->body',
				'$this->last_updated_by',
				'$this->last_updated_on',
				'$this->is_editable' 

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
			UPDATE communication_templates 
			SET  
				org_id = '$this->org_id',
				store_id = '$this->store_id',
				title = '$this->title',
				type = '$this->type',
				subject = '$this->subject',
				body = '$this->body',
				last_updated_by = '$this->last_updated_by',
				last_updated_on = '$this->last_updated_on',
				is_editable = '$this->is_editable' 
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
		$hash['title'] = $this->title;
		$hash['type'] = $this->type;
		$hash['subject'] = $this->subject;
		$hash['body'] = $this->body;
		$hash['last_updated_by'] = $this->last_updated_by;
		$hash['last_updated_on'] = $this->last_updated_on;
		$hash['is_editable'] = $this->is_editable;

		return $hash;
	}
} // class : end

?>