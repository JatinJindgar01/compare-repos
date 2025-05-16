<?php

/*
*
* -------------------------------------------------------
* CLASSNAME:        IssueTracker
* GENERATION DATE:  08.03.2012
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  issue_tracker
* FOR MYSQL DB:     store_management
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiIssueTrackerModel
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $org_id;
	protected $status;
	protected $priority;
	protected $department;
	protected $assigned_to;
	protected $issue_code;
	protected $issue_name;
	protected $customer_id;
	protected $ticket_code;
	protected $assigned_by;
	protected $due_date;
	protected $created_date;
	protected $reported_by;
	protected $mark_critical_on;
	protected $type;
	protected $resolved_by;
	protected $last_updated;
	protected $is_active;
	protected $created_by;

	protected $database; // Instance of class database

	protected $table = 'issue_tracker';

	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function ApiIssueTrackerModel()
	{	

		$this->database = new Dbase( 'stores' );

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

	function getStatus()
	{	
		return $this->status;
	}

	function getPriority()
	{	
		return $this->priority;
	}

	function getDepartment()
	{	
		return $this->department;
	}

	function getAssignedTo()
	{	
		return $this->assigned_to;
	}

	function getIssueCode()
	{	
		return $this->issue_code;
	}

	function getIssueName()
	{	
		return $this->issue_name;
	}

	function getCustomerId()
	{	
		return $this->customer_id;
	}

	function getTicketCode()
	{	
		return $this->ticket_code;
	}

	function getAssignedBy()
	{	
		return $this->assigned_by;
	}

	function getDueDate()
	{	
		return $this->due_date;
	}

	function getCreatedDate()
	{	
		return $this->created_date;
	}

	function getReportedBy()
	{	
		return $this->reported_by;
	}

	function getMarkCriticalOn()
	{	
		return $this->mark_critical_on;
	}

	function getType()
	{	
		return $this->type;
	}

	function getResolvedBy()
	{	
		return $this->resolved_by;
	}

	function getLastUpdated()
	{	
		return $this->last_updated;
	}

	function getIsActive()
	{	
		return $this->is_active;
	}

	function getCreatedBy()
	{	
		return $this->created_by;
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

	function setStatus( $status )
	{
		$this->status =  $status;
	}

	function setPriority( $priority )
	{
		$this->priority =  $priority;
	}

	function setDepartment( $department )
	{
		$this->department =  $department;
	}

	function setAssignedTo( $assigned_to )
	{
		$this->assigned_to =  $assigned_to;
	}

	function setIssueCode( $issue_code )
	{
		$this->issue_code =  $issue_code;
	}

	function setIssueName( $issue_name )
	{
		$this->issue_name =  $issue_name;
	}

	function setCustomerId( $customer_id )
	{
		$this->customer_id =  $customer_id;
	}

	function setTicketCode( $ticket_code )
	{
		$this->ticket_code =  $ticket_code;
	}

	function setAssignedBy( $assigned_by )
	{
		$this->assigned_by =  $assigned_by;
	}

	function setDueDate( $due_date )
	{
		$this->due_date =  $due_date;
	}

	function setCreatedDate( $created_date )
	{
		$this->created_date =  $created_date;
	}

	function setReportedBy( $reported_by )
	{
		$this->reported_by =  $reported_by;
	}

	function setMarkCriticalOn( $mark_critical_on )
	{
		$this->mark_critical_on =  $mark_critical_on;
	}

	function setType( $type )
	{
		$this->type =  $type;
	}

	function setResolvedBy( $resolved_by )
	{
		$this->resolved_by =  $resolved_by;
	}

	function setLastUpdated( $last_updated )
	{
		$this->last_updated =  $last_updated;
	}

	function setIsActive( $is_active )
	{
		$this->is_active =  $is_active;
	}

	function setCreatedBy( $created_by )
	{
		$this->created_by =  $created_by;
	}

	// **********************
	// SELECT METHOD / LOAD
	// **********************

	function load( $id )
	{

		$sql =  "SELECT * FROM issue_tracker WHERE id = $id";
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->org_id = $row->org_id;
		$this->status = $row->status;
		$this->priority = $row->priority;
		$this->department = $row->department;
		$this->assigned_to = $row->assigned_to;
		$this->issue_code = $row->issue_code;
		$this->issue_name = $row->issue_name;
		$this->customer_id = $row->customer_id;
		$this->ticket_code = $row->ticket_code;
		$this->assigned_by = $row->assigned_by;
		$this->due_date = $row->due_date;
		$this->created_date = $row->created_date;
		$this->reported_by = $row->reported_by;
		$this->mark_critical_on = $row->mark_critical_on;
		$this->type = $row->type;
		$this->resolved_by = $row->resolved_by;
		$this->last_updated = $row->last_updated;
		$this->is_active = $row->is_active;
		$this->created_by = $row->created_by;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement

		$sql =  "

			INSERT INTO issue_tracker 
			( 
				org_id,
				status,
				priority,
				department,
				assigned_to,
				issue_code,
				issue_name,
				customer_id,
				ticket_code,
				assigned_by,
				due_date,
				created_date,
				reported_by,
				mark_critical_on,
				type,
				resolved_by,
				last_updated,
				is_active,
				created_by 
			) 
			VALUES 
			( 
				'$this->org_id',
				'$this->status',
				'$this->priority',
				'$this->department',
				'$this->assigned_to',
				'$this->issue_code',
				'$this->issue_name',
				'$this->customer_id',
				'$this->ticket_code',
				'$this->assigned_by',
				'$this->due_date',
				'$this->created_date',
				'$this->reported_by',
				'$this->mark_critical_on',
				'$this->type',
				'$this->resolved_by',
				'$this->last_updated',
				'$this->is_active',
				'$this->created_by' 
			)";
		
		return $this->id = $this->database->insert( $sql );

	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO issue_tracker 
			( 
				id,
				org_id,
				status,
				priority,
				department,
				assigned_to,
				issue_code,
				issue_name,
				customer_id,
				ticket_code,
				assigned_by,
				due_date,
				created_date,
				reported_by,
				mark_critical_on,
				type,
				resolved_by,
				last_updated,
				is_active,
				created_by 

			) 

			VALUES 
			( 
				'$this->id',
				'$this->org_id',
				'$this->status',
				'$this->priority',
				'$this->department',
				'$this->assigned_to',
				'$this->issue_code',
				'$this->issue_name',
				'$this->customer_id',
				'$this->ticket_code',
				'$this->assigned_by',
				'$this->due_date',
				'$this->created_date',
				'$this->reported_by',
				'$this->mark_critical_on',
				'$this->type',
				'$this->resolved_by',
				'$this->last_updated',
				'$this->is_active',
				'$this->created_by' 

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
			UPDATE issue_tracker 
			SET  
				org_id = '$this->org_id',
				status = '$this->status',
				priority = '$this->priority',
				department = '$this->department',
				assigned_to = '$this->assigned_to',
				issue_code = '$this->issue_code',
				issue_name = '$this->issue_name',
				customer_id = '$this->customer_id',
				ticket_code = '$this->ticket_code',
				assigned_by = '$this->assigned_by',
				due_date = '$this->due_date',
				created_date = '$this->created_date',
				reported_by = '$this->reported_by',
				mark_critical_on = '$this->mark_critical_on',
				type = '$this->type',
				resolved_by = '$this->resolved_by',
				last_updated = '$this->last_updated',
				is_active = '$this->is_active',
				created_by = '$this->created_by' 
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
		$hash['status'] = $this->status;
		$hash['priority'] = $this->priority;
		$hash['department'] = $this->department;
		$hash['assigned_to'] = $this->assigned_to;
		$hash['issue_code'] = $this->issue_code;
		$hash['issue_name'] = $this->issue_name;
		$hash['customer_id'] = $this->customer_id;
		$hash['ticket_code'] = $this->ticket_code;
		$hash['assigned_by'] = $this->assigned_by;
		$hash['due_date'] = $this->due_date;
		$hash['created_date'] = $this->created_date;
		$hash['reported_by'] = $this->reported_by;
		$hash['mark_critical_on'] = $this->mark_critical_on;
		$hash['type'] = $this->type;
		$hash['resolved_by'] = $this->resolved_by;
		$hash['last_updated'] = $this->last_updated;
		$hash['is_active'] = $this->is_active;
		$hash['created_by'] = $this->created_by;


		return $hash;
	}
} // class : end

?>

