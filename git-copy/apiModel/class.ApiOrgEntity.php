<?php
//<!-- begin of generated class -->
/*
*
* -------------------------------------------------------
* CLASSNAME:        OrgEntity
* GENERATION DATE:  19.05.2011
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  org_entities
* FOR MYSQL DB:     masters
*
*/

//**********************
// CLASS DECLARATION
//**********************
//TODO: referes to cheetah
include_once "helper/AuditManager.php";
//TODO: referes to cheetah
include_once "health_dashboard/EntityHealthTracker.php";
//TODO: referes to cheetah
include_once "health_dashboard/IHealthAuditable.php";
class ApiOrgEntityModel implements IHealthAuditable
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $org_id;
	protected $type;
	protected $is_active;
	protected $admin_type;
	protected $code;
	protected $name;
	protected $description;
	protected $time_zone_id;
	protected $currency_id;
	protected $language_id;
	protected $last_updated_by;
	protected $last_updated_on;

	protected $database; // Instance of class database

	protected $table = 'org_entities';

	private $changes;
	private $current_org_id;
	
	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function ApiOrgEntityModel()
	{	
		global $currentorg;
		$this->current_org_id = $currentorg->org_id;

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

	function getType()
	{	
		return $this->type;
	}

	function getIsActive()
	{	
		return $this->is_active;
	}

	function getAdminType()
	{
		return $this->admin_type;
	}
	
	function getCode()
	{	
		return $this->code;
	}

	function getName()
	{	
		return $this->name;
	}

	function getDescription()
	{	
		return $this->description;
	}

	function getTimeZoneId()
	{	
		return $this->time_zone_id;
	}

	function getCurrencyId()
	{	
		return $this->currency_id;
	}

	function getLanguageId()
	{	
		return $this->language_id;
	}

	function getLastUpdatedBy()
	{	
		return $this->last_updated_by;
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

	function setOrgId( $org_id )
	{
		$this->org_id =  $org_id;
	}

	function setType( $type )
	{
		$this->type =  $type;
	}

	function setIsActive( $is_active )
	{
		$this->is_active =  $is_active;
	}

	function setAdminType( $admin_type )
	{
		$this->admin_type = $admin_type;	
	}
	
	function setCode( $code )
	{
		$this->code =  $code;
	}

	function setName( $name )
	{
		$this->name =  $name;
	}

	function setDescription( $description )
	{
		$this->description =  $description;
	}

	function setTimeZoneId( $time_zone_id )
	{
		$this->time_zone_id =  $time_zone_id;
	}

	function setCurrencyId( $currency_id )
	{
		$this->currency_id =  $currency_id;
	}

	function setLanguageId( $language_id )
	{
		$this->language_id =  $language_id;
	}

	function setLastUpdatedBy( $last_updated_by )
	{
		$this->last_updated_by =  $last_updated_by;
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

		if( !$id ) return;
		
		$sql =  "SELECT * FROM org_entities WHERE org_id = $this->current_org_id AND id = $id";
		$result =  $this->database->query( $sql );
		
		if( !$result ) return;//in case no data is returned get back where u came from
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->org_id = $row->org_id;
		$this->type = $row->type;
		$this->is_active = $row->is_active;
		$this->admin_type = $row->admin_type;
		$this->code = $row->code;
		$this->name = $row->name;
		$this->description = $row->description;
		$this->time_zone_id = $row->time_zone_id;
		$this->currency_id = $row->currency_id;
		$this->language_id = $row->language_id;
		$this->last_updated_by = $row->last_updated_by;
		$this->last_updated_on = $row->last_updated_on;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement
		if( !$this->admin_type )
			$this->admin_type = 'GENERAL';
		$sql =  "

			INSERT INTO org_entities 
			( 
				org_id,
				type,
				is_active,
				admin_type,
				code,
				name,
				description,
				time_zone_id,
				currency_id,
				language_id,
				last_updated_by,
				last_updated_on 
			) 
			VALUES 
			( 
				'$this->org_id',
				'$this->type',
				'$this->is_active',
				'$this->admin_type',
				'$this->code',
				'$this->name',
				'$this->description',
				'$this->time_zone_id',
				'$this->currency_id',
				'$this->language_id',
				'$this->last_updated_by',
				'$this->last_updated_on' 
			)";
			
		
	   $this->id = $this->database->insert( $sql );
	   
	   //getting changes and raising event
	   $changes = $this->getChangesToTrack('insert');
	   $this->raiseEvent($changes);
		
	   if($this->id){ 
			global $currentuser;
			$this->changes = "$currentuser->user_id added a new $this->type with id $this->id, name $this->name";
			$am = new AuditManager();
			$am->addToAuditTrail($this);
		}
		return $this->id;
	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO org_entities 
			( 
				id,
				org_id,
				type,
				is_active,
				code,
				name,
				description,
				time_zone_id,
				currency_id,
				language_id,
				last_updated_by,
				last_updated_on 

			) 

			VALUES 
			( 
				'$this->id',
				'$this->org_id',
				'$this->type',
				'$this->is_active',
				'$this->code',
				'$this->name',
				'$this->description',
				'$this->time_zone_id',
				'$this->currency_id',
				'$this->language_id',
				'$this->last_updated_by',
				'$this->last_updated_on' 

			)";
		
		$db_id = $this->database->update( $sql );
		if($db_id > 0){ //populate the changes 
			global $currentuser;
			$this->changes = "$currentuser->user_id added a new $this->type with id $this->id, name $this->name";
			$am = new AuditManager();
			$am->addToAuditTrail($this);	
		}
			//getting changes and raising event
		$changes = $this->getChangesToTrack('insert');
		$this->raiseEvent($changes);
		return $db_id;
	}
	
	
	/**
	*
	*@param $id
	*/
	function update( $id )
	{

		$sql = " 
			UPDATE org_entities 
			SET  
				org_id = '$this->org_id',
				type = '$this->type',
				is_active = '$this->is_active',
				admin_type = '$this->admin_type', 
				code = '$this->code',
				name = '$this->name',
				description = '$this->description',
				time_zone_id = '$this->time_zone_id',
				currency_id = '$this->currency_id',
				language_id = '$this->language_id',
				last_updated_by = '$this->last_updated_by',
				last_updated_on = '$this->last_updated_on' 
			WHERE id = $id 
			AND org_id = $this->org_id ";

		//getting changes and raising event
		$changes = $this->getChangesToTrack('update');
		
	    $result = $this->database->update($sql);
		if($result){ 
			global $currentuser;
			//not able to add extended trail here... difference between old and new
			$this->changes = "$currentuser->user_id updated $this->type with id $this->id, name $this->name";
			$am = new AuditManager();
			$am->addToAuditTrail($this);
			$this->raiseEvent($changes);	
		}
		return $result;
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
		$hash['type'] = $this->type;
		$hash['is_active'] = $this->is_active;
		$hash['code'] = $this->code;
		$hash['name'] = $this->name;
		$hash['description'] = $this->description;
		$hash['time_zone_id'] = $this->time_zone_id;
		$hash['currency_id'] = $this->currency_id;
		$hash['language_id'] = $this->language_id;
		$hash['last_updated_by'] = $this->last_updated_by;
		$hash['last_updated_on'] = $this->last_updated_on;
		$hash['admin_type'] = $this->admin_type;

		return $hash;
	}
	
	
	function getChanges(){
		return $this->changes;
	}	
	
	
	function getAuditEntity(){
		return "OrgEntityModel";	
	}
	
	
	function getAuditEntityId(){
		return $this->id;
	}
	
	
	function getReadableDesc($changes){
		return $changes;	
	}
	
	
	function revert($changes){
		
	}
	
	
	function revertToState($state_id){
		
	}
	
	public function getChangesToTrack($type){
		global $cfg;
		if($cfg['health_dashboard'] == 'disabled')
			return;
		if($type == 'insert')
		{
			$changes = $this->getHash();
		}
		else if($type == 'update')
		{
			$org = new ApiOrgEntityModel();
			$org->load($this->id);
			$old = $org->getHash();
			$new = $this->getHash();
			$changes = array();
			foreach($old as $key => $val)
			{
				if($new[$key] != $val)
					$changes[$key] = $new[$key];
			}
			//update require type and id (for ref_id)
			$changes['id'] = $this->id;
			$changes['type'] = $this->type;
		}
		return $changes;
		
	}
	
	public function raiseEvent($data)
	{
		global $cfg;
		if($cfg['health_dashboard'] == 'disabled')
			return;
		$fin[0] = $data;
		$obj  = new EntityHealthTracker();
		switch($data['type'])
		{
			case 'ZONE': $entity_type = 'ZONE';break;
			case 'TILL': $entity_type = 'TILL';break;
			case 'CONCEPT': $entity_type = 'CONCEPT';break;
			case 'STORE': $entity_type = 'STORE';break;
			case 'ADMIN_USER': return;
		}
		$obj->process($entity_type,$fin);
	}
	
	
} // class : end
//<!-- end of generated class -->
?>