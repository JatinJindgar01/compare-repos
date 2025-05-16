<?php

//TODO: referes to cheetah
include_once "helper/AuditManager.php";
//TODO: referes to cheetah
include_once "health_dashboard/EntityHealthTracker.php";
//TODO: referes to cheetah
include_once "health_dashboard/IHealthAuditable.php";

//<!-- begin of generated class -->
/*
*
* -------------------------------------------------------
* CLASSNAME:        Store
* GENERATION DATE:  01.06.2011
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  stores
* FOR MYSQL DB:     masters
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiStoreModel implements IHealthAuditable
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $org_id;
	protected $city_id;
	protected $area_id;
	protected $mobile;
	protected $land_line;
	protected $email;
	protected $is_active;
	protected $lat;
	protected $long;
	protected $external_id;
	protected $external_id_1;
	protected $external_id_2;
	protected $last_updated_by;
	protected $last_updated_on;

	protected $database; // Instance of class database

	protected $table = 'stores';

	protected $mem_cache_manager;
	protected $logger;
	
	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function ApiStoreModel()
	{	

		global $logger;
		$this->database = new Dbase( 'masters' );
		$this->logger = &$logger;

		$this->mem_cache_manager = MemcacheMgr::getInstance();
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

	function getCityId()
	{	
		return $this->city_id;
	}

	function getAreaId()
	{	
		return $this->area_id;
	}

	function getMobile()
	{	
		return $this->mobile;
	}

	function getLandLine()
	{	
		return $this->land_line;
	}

	function getEmail()
	{	
		return $this->email;
	}

	function getIsActive()
	{	
		return $this->is_active;
	}

	function getLat()
	{	
		return $this->lat;
	}

	function getLong()
	{	
		return $this->long;
	}

	function getExternalId()
	{	
		return $this->external_id;
	}

	function getExternalId1()
	{	
		return $this->external_id_1;
	}

	function getExternalId2()
	{	
		return $this->external_id_2;
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

	function setCityId( $city_id )
	{
		$this->city_id =  $city_id;
	}

	function setAreaId( $area_id )
	{
		$this->area_id =  $area_id;
	}

	function setMobile( $mobile )
	{
		$this->mobile =  $mobile;
	}

	function setLandLine( $land_line )
	{
		$this->land_line =  $land_line;
	}

	function setEmail( $email )
	{
		$this->email =  $email;
	}

	function setIsActive( $is_active )
	{
		$this->is_active =  $is_active;
	}

	function setLat( $lat )
	{
		$this->lat =  $lat;
	}

	function setLong( $long )
	{
		$this->long =  $long;
	}

	function setExternalId( $external_id )
	{
		$this->external_id =  $external_id;
	}

	function setExternalId1( $external_id_1 )
	{
		$this->external_id_1 =  $external_id_1;
	}

	function setExternalId2( $external_id_2 )
	{
		$this->external_id_2 =  $external_id_2;
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

		global $currentorg;
		$org_id = $currentorg->org_id;
		
		$cache_key = 'o'.$org_id.'_' . CacheKeysPrefix::$storeKey.'BASE_LOAD_ARRAY_'.$id;
		$ttl = CacheKeysTTL::$storeKey;
		try{
			
			$json_result = $this->mem_cache_manager->get( $cache_key );
			$result = json_decode( $json_result, true );
		}catch( Exception $e ){

			try{
				
				$sql =  "SELECT * FROM stores WHERE id = $id";
				$result =  $this->database->query( $sql );
							
				//set to mem cache
				if( !$result || !$id ){

					throw new Exception( 'Not Caching as store unit does not exists' );
				}

				$json_result = json_encode( $result );
				$this->mem_cache_manager->set( $cache_key, $json_result, $ttl );
				
			}catch( Exception $e ){
				
				$this->logger->error("Error while setting the key");
			}
		}
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->id = $row->id;
		$this->org_id = $row->org_id;
		$this->city_id = $row->city_id;
		$this->area_id = $row->area_id;
		$this->mobile = $row->mobile;
		$this->land_line = $row->land_line;
		$this->email = $row->email;
		$this->is_active = $row->is_active;
		$this->lat = $row->lat;
		$this->long = $row->long;
		$this->external_id = $row->external_id;
		$this->external_id_1 = $row->external_id_1;
		$this->external_id_2 = $row->external_id_2;
		$this->last_updated_by = $row->last_updated_by;
		$this->last_updated_on = $row->last_updated_on;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement

		$sql =  "

			INSERT INTO stores 
			( 
				org_id,
				city_id,
				area_id,
				mobile,
				land_line,
				email,
				is_active,
				lat,
				`long`,
				external_id,
				external_id_1,
				external_id_2,
				last_updated_by,
				last_updated_on 
			) 
			VALUES 
			( 
				'$this->org_id',
				'$this->city_id',
				'$this->area_id',
				'$this->mobile',
				'$this->land_line',
				'$this->email',
				'$this->is_active',
				'$this->lat',
				'$this->long',
				'$this->external_id',
				'$this->external_id_1',
				'$this->external_id_2',
				'$this->last_updated_by',
				'$this->last_updated_on' 
			)";
		
		$this->id = $this->database->insert( $sql );
		$changes = $this->getChangesToTrack('insert');
		$this->raiseEvent($changes);

	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO stores 
			( 
				id,
				org_id,
				city_id,
				area_id,
				mobile,
				land_line,
				email,
				is_active,
				lat,
				`long`,
				external_id,
				external_id_1,
				external_id_2,
				last_updated_by,
				last_updated_on 

			) 

			VALUES 
			( 
				'$this->id',
				'$this->org_id',
				'$this->city_id',
				'$this->area_id',
				'$this->mobile',
				'$this->land_line',
				'$this->email',
				'$this->is_active',
				'$this->lat',
				'$this->long',
				'$this->external_id',
				'$this->external_id_1',
				'$this->external_id_2',
				'$this->last_updated_by',
				'$this->last_updated_on' 

			)";
		
		$status = $this->database->update( $sql );
		$changes = $this->getChangesToTrack('insert');
		$this->raiseEvent($changes);
		
		if( !$status ) return $status;
		
		$cache_key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$storeKey.'BASE_LOAD_ARRAY_'.$this->id;
		try{
			
			$this->mem_cache_manager->delete( $cache_key );
		}catch( Exception $e ){

			$this->logger->error( 'Key '.$key.' Could Not Be Deleted .' );
		}
		
		return $status;
	}
	
	
	/**
	*
	*@param $id
	*/
	function update( $id )
	{

		$sql = " 
			UPDATE stores 
			SET  
				org_id = '$this->org_id',
				city_id = '$this->city_id',
				area_id = '$this->area_id',
				mobile = '$this->mobile',
				land_line = '$this->land_line',
				email = '$this->email',
				is_active = '$this->is_active',
				lat = '$this->lat',
				`long` = '$this->long',
				external_id = '$this->external_id',
				external_id_1 = '$this->external_id_1',
				external_id_2 = '$this->external_id_2',
				last_updated_by = '$this->last_updated_by',
				last_updated_on = '$this->last_updated_on' 
			WHERE id = $id 
			AND org_id = $this->org_id ";

		$changes = $this->getChangesToTrack('update');

		$status = $this->database->update( $sql );
		$this->raiseEvent($changes);
		
		if( !$status ) return $status;
		
		$cache_key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$storeKey.'BASE_LOAD_ARRAY_'.$id;
		try{
			
			$this->mem_cache_manager->delete( $cache_key );
		}catch( Exception $e ){

			$this->logger->error( 'Key '.$key.' Could Not Be Deleted .' );
		}
		
		return $status;
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
		$hash['city_id'] = $this->city_id;
		$hash['area_id'] = $this->area_id;
		$hash['mobile'] = $this->mobile;
		$hash['land_line'] = $this->land_line;
		$hash['email'] = $this->email;
		$hash['is_active'] = $this->is_active;
		$hash['lat'] = $this->lat;
		$hash['long'] = $this->long;
		$hash['external_id'] = $this->external_id;
		$hash['external_id_1'] = $this->external_id_1;
		$hash['external_id_2'] = $this->external_id_2;
		$hash['last_updated_by'] = $this->last_updated_by;
		$hash['last_updated_on'] = $this->last_updated_on;


		return $hash;
	}
	
	function getChanges(){
		return $this->changes;
	}	
	
	
	function getAuditEntity(){
		return "StoreModel";	
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
		if($type == 'insert')
		{
			$changes = $this->getHash();
		}
		else if($type == 'update')
		{
			$store = new ApiStoreModel();
			$store->load($this->id);
			$old = $store->getHash();
			$new = $this->getHash();
			$changes = array();
			foreach($old as $key => $val)
			{
				if($new[$key] != $val)
					$changes[$key] = $new[$key];
			}
			$changes['id'] = $this->id;
			$changes['type'] = 'STORE';
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
		$obj->process('STORE',$fin);
	}
} // class : end
//<!-- end of generated class -->
?>