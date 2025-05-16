<?php
include_once 'apiModel/class.ApiOrgEntityModelExtension.php';
include_once 'apiController/ApiBaseController.php';
//include_once '../cheetah/business_controller/AdminUserController.php';

/**
 * The zone manager is reponsible for adding,
 * editing, fetching of all the stores.
 *
 * @author prakhar
 *
 */
class ApiEntityController extends ApiBaseController{

	private $EntityResolver;
	private $custom_entity_type;
	
	protected $entity_type;
	protected $OrgEntityModelExtension;
	protected $C_storeModel;

	public function __construct( $entity_type, &$error_responses = false, &$error_keys = false ){

		//global $logger ;

		parent::__construct( $error_responses, $error_keys );
		$this->entity_type = $entity_type;
		$this->isEntityTypeExists( );
		$this->OrgEntityModelExtension = new ApiOrgEntityModelExtension( $entity_type );
		$this->C_storeModel = new ApiStoreModelExtension();
	}

	/**
	 * returns the id of the loaded class object
	 */
	public function getId( ){

		return $this->OrgEntityModelExtension->getId();
	}

	protected function setEntityType( $entity_type ){

		$this->isEntityTypeExists();
		$this->entity_type = $entity_type;
	}

	/**
	 * checks if the entity on which operation has to be made exists or not
	 */
	private function isEntityTypeExists(){

		$this->entity_type = ucfirst( $this->entity_type );

		if( !in_array( $this->entity_type, array( 'STORE', 'STR_SERVER', 'ZONE', 'CONCEPT', 'TILL', 'ADMIN_USER' ) ) )
		throw new Exception( 'Entity Does Not Exists' );
	}

	/**
	 * add the new entity
	 *
	 * @param array $entity_details
	 *
	 * CONTRACT
	 * array(
	 * 'code' => value,
	 * 'name' => value,
	 * 'description' => value,
	 * 'time_zone_id' => value,
	 * 'currency_id' => value,
	 * 'language_id' => value,
	 * )
	 */
	public function add( array $entity_details , $parent_id = false , $parent_type = false , $validate_code = true ){

		$this->logger->debug( 'Adding new Entity With Details .'. print_r( $entity_details ,  true ) );

		extract( $entity_details );

		try{

			if( $validate_code ){
				
				$this->validateEntityCode( $code );
				$this->validateEntityName( $name );
			}
				
			$this->setEntityDetails( $entity_details );
			$this->OrgEntityModelExtension->setIsActive( 1 );
			$id = $this->OrgEntityModelExtension->insert();
				
			$this->logger->debug("This is the add function for entity");
				
			$this->logger->debug( 'Entity Added With Id .'.$id );
			if( $id ) {

				$status = $this->OrgEntityModelExtension->updateEntityModelWithCreatedId( $id, $this->org_id );

				$this->logger->debug( 'Update Status For The Entities .:-> '.$status );
				$this->addStoreUnitParents( $parent_id, $parent_type, $id );
				if( $status) return 'SUCCESS';
			}
				
			return 'FAILURE';
				
		}catch( Exception $e ){
				
			$this->logger->error( "Exception Caught :-> ".$e->getMessage()."
									While Adding Entity Type. :-> ".$this->entity_type );
			return $e->getMessage();
		}
	}

	/**
	 * Adding the parents
	 * @param unknown_type $parent_id
	 * @param unknown_type $parent_type
	 * @param unknown_type $id
	 * @param unknown_type $code
	 */
	public function addStoreUnitParents( $parent_id, $parent_type, $id ){

		if( $parent_id && $parent_type ){
				
			$this->logger->debug( 'Adding Parent For The Entities .:-> parent id'. $parent_id .' parent type '.$parent_type );
			$parent_added = $this->addParentEntity( $parent_id, $parent_type,$id, $this->entity_type );
			$this->logger->debug( 'Add Parent Status For The Entities .:-> '.$parent_added );
		}
	}
	/**
	 * @param array $zone_details
	 *
	 * CONTRACT
	 * array(
	 * 'code' => value,
	 * 'name' => value,
	 * 'admin_type' => value,
	 * 'description' => value,
	 * 'time_zone_id' => value,
	 * 'currency_id' => value,
	 * 'language_id' => value,
	 * )
	 */
	private function setEntityDetails( $entity_details ){

		extract( $entity_details );
		if( !$admin_type )
			$admin_type = 'GENERAL';
		$this->OrgEntityModelExtension->setCode( Util::uglify( $code ) );
		$this->OrgEntityModelExtension->setName( $name );
		$this->OrgEntityModelExtension->setOrgId( $this->org_id );
		$this->OrgEntityModelExtension->setAdminType( $admin_type );
		$this->OrgEntityModelExtension->setDescription( $description );
		$this->OrgEntityModelExtension->setTimeZoneId( $time_zone_id );
		$this->OrgEntityModelExtension->setCurrencyId( $currency_id );
		$this->OrgEntityModelExtension->setLanguageId( $language_id );
		$this->OrgEntityModelExtension->setLastUpdatedBy( $this->user_id );
		$this->OrgEntityModelExtension->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
	}

	/**
	 * update entity details
	 *
	 * @param array $entity_details
	 *
	 * CONTRACT
	 * array(
	 * 'code' => value,
	 * 'name' => value,
	 * 'description' => value,
	 * 'time_zone_id' => value,
	 * 'currency_id' => value,
	 * 'language_id' => value,
	 * )
	 */
	public function update( $entity_id, array $entity_details ){

		extract( $entity_details );

		try{
				
			$this->logger->debug( 'Trying To Update Entity of type( '.$this->entity_type.' ).
									with id : $entity_id and details :-> '.var_dump( $entity_details ) );

			$this->OrgEntityModelExtension->load( $entity_id );
			$existing_code_value = $this->OrgEntityModelExtension->getCode();
				
			$this->validateEntityCode( $code, $entity_id, $existing_code_value );
			$this->validateEntityName( $name, $entity_id );

			$this->setEntityDetails( $entity_details );
			$status = $this->OrgEntityModelExtension->update( $entity_id );
				
			$this->logger->debug( 'Entity Updated With Status . :-> '.$status );
			if( $status )
			return 'SUCCESS';
				
			return 'FAILURE';
				
		}catch( Exception $e ){
				
			$this->logger->error( "Exception Caught :-> ".$e->getMessage()."
									While Updating Entity Type. ( ".$this->entity_type ." ) With id :-> $entity_id");
			return $e->getMessage();
		}


	}

	/**
	 * Returns the entity as option for using in select box
	 * For the Ids Passed Inside
	 *
	 * @return array( name => entity_id )
	 */
	public function getEntityByIdsAsOptions( $entity_ids = array() ){

		$entity_options = array();

		$this->logger->debug( 'Fetching Entities ( ############'.$this->entity_type.'######## ) As Options . :-> ' );
		$entities = $this->org_model->getAllEntitiesByIds( $entity_ids, $this->entity_type );

		foreach( $entities as $entity_details ){
				
			$name = $entity_details['name'];
			$entity_options[$name] = $entity_details['id'];
				
			$this->logger->debug('Fetching name and id of each zone'.$name.$entity_options);
		}

		return $entity_options;
	}

	/**
	 * Returns the entity as option for using in select box
	 *
	 * @return array( name => entity_id )
	 */
	public function getEntityAsOptions( $entity_ids = array() ){

		$entity_options = array();

		$this->logger->debug( 'Fetching Entities ( ############'.$this->entity_type.'######## ) As Options . :-> ' );
		$entities = $this->org_model->getAllEntities( $this->entity_type );

		foreach( $entities as $entity_details ){
				
			$name = $entity_details['name'];
			$entity_options[$name] = $entity_details['id'];
				
			$this->logger->debug('Fetching name and id of each zone'.$name.$entity_options);
		}

		$this->logger->debug('Awesome'.$entity_options);
		
		if( count( $entity_ids ) > 0 ){
				
			$entity_options = array_flip( $entity_options );

			foreach( $entity_options as $key => $value ){

				if( !in_array( $key, $entity_ids ) )
				unset( $entity_options[$key] );
			}
				
			$entity_options = array_flip( $entity_options );
		}

		$this->logger->debug( 'Fetched Entities ( '.$this->entity_type.' ) As Options . :-> '.print_r( $entity_options , true ) );

		return $entity_options;
	}

	/**
	 *
	 * Returns all the children entities of the required type...
	 *
	 * @param $entity_id
	 * @param $child_entitity_type
	 *
	 * @return array $entity_ids
	 */

	public function getChildrenEntityByType( $entity_id, $child_entitity_type ){

		$this->logger->debug( 'Trying To load the children For the Entity '.
		$this->entity_type. ' For child type : '.$child_entitity_type .'
								And Entity Id '.$entity_id );

		$this->OrgEntityModelExtension->load( $entity_id );

		$children = $this->OrgEntityModelExtension->getChildrenEntities( $child_entitity_type );

		$this->logger->debug( 'Fetched Children ::'. print_r( $children, true ) );

		return $children;
	}

	/**
	 * returns childrens details
	 */
	public function getChildersByTypeAsOption( $entity_ids , $child_type , $include_inactive = true ){
		$entity_ids = implode(',',$entity_ids);

		return $this->OrgEntityModelExtension->getEntityDetailsByIds($this->org_id,$entity_ids,$child_type,$include_inactive);
	}

	/**
	 * returns childers from parent ids and child type
	 */
	public function getChildrensByTypeFromParent( $entity_ids , $child_type ){
		$entity_ids = implode(',',$entity_ids);

		return $this->OrgEntityModelExtension->getAllChildrensByType( $this->org_id , $entity_ids , $child_type );
	}

	/**
	 * 
	 * @param unknown_type $entity_id
	 * @param unknown_type $entity_type
	 * @param unknown_type $org_id
	 */
	public function getParentsById($entity_id, $entity_type, $org_id ){
		
		return $this->OrgEntityModelExtension->getParentsById( $entity_id, $entity_type, $org_id );
	}
	
	/**
	 *
	 * Returns all the parents...
	 *
	 * @param $entity_id
	 * @param $parent_entitity_type
	 *
	 * @return array $entity_ids
	 */

	public function getParentEntitiesByType( $entity_id, $parent_entitity_type ){

		$this->logger->debug( 'Trying To load the parent For the Entity '.
		$this->entity_type. ' For parent type : '.$parent_entitity_type .'
								And Entity Id '.$entity_id );
		
		$this->OrgEntityModelExtension->load( $entity_id );

		$parent = $this->OrgEntityModelExtension->getParentEntities( $parent_entitity_type );
		
		$this->logger->debug( 'Fetched Parent ::'.print_r( $parent,true ) );

		return $parent;
	}
	
	/**
	 * Return all parents for a list of entities
	 * @param $entity_ids
	 * @params $parent_entity_type
	 * @params $type_of_entites
	 * 
	 * @return array list of entity_id and parent entity id
	 */
	
	public function getParentEntitiesByTypeBulk( $entity_ids, $parent_entitity_type, $type_of_entities = 'TILL' ){
	
		$this->logger->debug( 'Loading parents for each entity in  \n'.print_r($entity_ids,true)."\n".
				$this->entity_type. ' For parent type : '.$parent_entitity_type .'
								And Entity Id '.$entity_id );
	
		//$this->OrgEntityModelExtension->load( $entity_ids[0] );
	
		$parents = 
			OrgEntityModelExtension::getParentEntitiesInBulk($entity_ids, $parent_entitity_type, $type_of_entities );
	
		$this->logger->debug( 'Fetched Parent ::'.print_r( $parents,true ) );
	
		return $parents;
	}

	/**
	 *
	 * Returns all the parents...
	 *
	 * @param $entity_id
	 * @param $child_entitity_type
	 *
	 * @return array $entity_ids
	 */

	public function getParentEntityByType( $entity_id, $parent_entitity_type ){

		if( !$entity_id ) return false;

		$this->logger->debug( 'Fetching All Parents For Entity Type : '.$this->entity_type.
						' of Parent Entity type : '.$parent_entitity_type.' 
						 & For Entity Id : '.$entity_id );

		$this->OrgEntityModelExtension->load( $entity_id );

		$parent = $this->OrgEntityModelExtension->getParentEntity( $parent_entitity_type );

		$this->logger->debug( 'The Parent Entity Type : '.$parent_entitity_type.'
								For Entity Id : '.$entity_id . ' result :' . print_r( $parent, true ) );
		return $parent;
	}

	/**
	 * validates the zone code
	 */
	public function validateEntityCode( $code, $entity_id = false, $existing_code_value = false ){

		$this->logger->debug( 'Validating the code : prev : '.$code .' after uglify '.Util::uglify( $code ).'For Entity :' .$this->entity_type . ' & Id : .'.$entity_id );

		$code = Util::uglify( $code );
		$existing_code_value = Util::uglify( $existing_code_value );

		if( !$entity_id && $code == 'root' )
		throw new Exception( 'ROOT is a reserved keyword and can not be used a code' );
		elseif( $entity_id && $code == 'root' && $code != $existing_code_value )
		throw new Exception( 'ROOT is a reserved keyword and can not be used a code' );
			
		$code = $this->OrgEntityModelExtension->codeExists( $this->org_id, $code, $entity_id );

		if( $code )
		throw new Exception( $this->entity_type.' With Code ( '.$code.' ) Name Already Exists' );
	}

	/**
	 * validating name for the entity name
	 *
	 * @param unknown_type $name
	 * @param unknown_type $entity_id
	 */
	public function validateEntityName( $name, $entity_id = false ){

		$this->logger->debug( 'Validating the name : prev : '.$name .'For Entity :' .$this->entity_type . ' & Id : .'.$entity_id );

		$name = $this->OrgEntityModelExtension->isEntityNameExists( $this->org_id, $name, $entity_id );

		if( $name )
		throw new Exception( $this->entity_type.' With Name ( '.$name.' ) Already Exists' );

	}

	/**
	 * Returns the details in array class
	 * to widget to load the table
	 */
	public function getDetailsById( $entity_id ){

		$this->logger->debug( 'Loads Entity Details For The type :'.
		$this->entity_type . ' And Id : '.$entity_id );

		$this->OrgEntityModelExtension->load( $entity_id );

		return $this->OrgEntityModelExtension->getHash();
	}

	/**
	 * Return the orgEntity Object
	 * @param unknown_type $entity_id
	 */
	public function getInfoDetails( $entity_id ){

		$this->logger->debug( 'Extracting The Info Details For The type :'.
		$this->entity_type . ' And Id : '.$entity_id );

		return $this->OrgEntityModelExtension->getInfoDetails( $this->org_id, $entity_id );
	}

	/**
	 * Returns the managers for the entities
	 * @param unknown_type $entity_id
	 */
	public function getManagers( $entity_id ){

		$this->logger->debug( 'loads manager For The type :'.
		$this->entity_type . ' And Id : '.$entity_id );

		$adminUser = new AdminUserController();
		return $adminUser->getUsersByEntityId( $entity_id );
	}

	/**
	 * 
	 * @param unknown_type $org_id
	 * @param array $entity_ids
	 */
	protected static function getUsersEmailMapByEntityIds( $org_id, array $entity_ids ){
		
		return AdminUserController::getUsersEmailMapByEntityIds( $org_id, $entity_ids );
	}
	
	/**
	 * Sets the active/inactive status of the entities.
	 *
	 * @param unknown_type $entity_id
	 * @param unknown_type $status
	 */
	public function setActiveStatus( $entity_id, $status ){

		$this->OrgEntityModelExtension->load( $entity_id );
		$this->OrgEntityModelExtension->setIsActive( $status );

		$status = $this->OrgEntityModelExtension->update( $entity_id );

		if( $status )
		return 'SUCCESS';

		return 'FAILURE';
	}

	/**
	 *
	 * @param unknown_type $parent_id
	 * @param unknown_type $parent_type
	 * @param unknown_type $child_id
	 * @param unknown_type $child_type
	 */
	public function addParentEntity( $parent_id, $parent_type, $child_id, $child_type ){

		$this->logger->debug( 'Adding Parent For The Parent Id :' .$parent_id .
								' Parent type : '.$parent_type .' Child Id '. $child_id . 
								' Child Type '. $child_type
		);

		if( $parent_id == $child_id )
		throw new Exception( 'A '.$this->entity_type.' Can Not Be The Parent of his own...' );
			
		if( $parent_id < 1 && $parent_type != $child_type )
		throw new Exception( ' Please Provide A Parent ' );
			
		$id = $this->OrgEntityModelExtension->setParent( $this->org_id, $parent_id, $parent_type, $child_id, $child_type );

		if( !$id )
		throw new Exception( 'Parent Could Not Be Added!!!' );
	}
	
	
	
	/**
	 *add parents in bulk
	 * @param unknown_type $entity_details
	 * 
	 */
	public function addBulkParentEntity( $entity_details ){

		$this->logger->debug( "Adding Parent For The Parent Id : $entity_details");

		$id = $this->org_model->setBulkParent( $entity_details);

		$this->logger->debug( "Added details");
	}
	
	public function getEntityIds( $codes, $type ){

		return  $this->org_model->getEntityIds( $this->org_id, $codes, $type );

	}

	/**
	 *
	 * @param unknown_type $entity_type
	 */
	public function getAll( $entity_type , $include_inactive = false , $admin_type = false ){

		return $this->OrgEntityModelExtension->getAll( $this->org_id, $entity_type, $include_inactive , $admin_type );
		 
		
	}

	/**
	 *
	 * @param unknown_type $entity_type
	 */
	public function getAllInActive( $entity_type ){

		return $this->OrgEntityModelExtension->getAllInActive( $this->org_id , $entity_type );
	}

	/**
	 *
	 * @param $entity_ids
	 * @param $entity_type
	 * @param $include_inactive
	 */
	public function getByIds( $entity_ids, $entity_type, $include_inactive = false ){

		$entity_ids = Util::joinForSql( $entity_ids );
		return $this->OrgEntityModelExtension->getEntityDetailsByIds( $this->org_id, $entity_ids, $entity_type, $include_inactive );
	}

	/**
	 * Returns the default entity
	 */
	public function getDefault(){
	
		return $this->OrgEntityModelExtension->getDefaultByType( $this->org_id, $this->entity_type );
	}

    /**
     * @param $id
     * @param $org_id
     * @return returns timezone of given entity @id
     */
    public function getEntityTimeZone($id, $org_id){
        $org_entity_model_Extension = new ApiOrgEntityModelExtension();
        return $org_entity_model_Extension->getEntityTimeZone($id, $org_id);
    }

    /**
     * @param $id
     * @param $org_id
     * @return language for @id entity
     */
    public function getEntityLanguage($id, $org_id){
        $org_entity_model_Extension = new ApiOrgEntityModelExtension();
        return $org_entity_model_Extension->getEntityLanguage($id, $org_id);
    }

    /**
     * @param $id
     * @param $org_id
     * @return currency for @id entity
     */
    public function getEntityCurrency($id, $org_id){
        $org_entity_model_Extension = new ApiOrgEntityModelExtension();
        return $org_entity_model_Extension->getEntityCurrency($id, $org_id);
    }

    /**
     * @param unknown_type $entity_id
     * @param unknown_type $org_id
     * @return mixed|NULL
     *  returns the timezone, currency and country
     */
    protected function getEntityDetailsFromCache($entity_id, $org_id)
    {
    	$cachekey = 'o'.$org_id.'_'.CacheKeysPrefix::$orgEntityDetails.$entity_id;
    	try
    	{
    		$memcache = MemcacheMgr::getInstance();
    		$string = $memcache->get($cachekey);
    		return json_decode($string, true);
    	}
    	catch(Exception $e)
    	{
    		return array();
    	}
    }
    
    protected function setEntityDetailsToCache($entity_id, $org_id, $assoc_array)
    {
    	$cachekey = 'o'.$org_id.'_'.CacheKeysPrefix::$orgEntityDetails.$entity_id;

    	// get the current details
    	$currentArr = $this->getEntityDetailsFromCache($entity_id, $org_id);
    	
    	foreach($assoc_array as $key=>$value)
    		$currentArr[$key] = $value;
    	
    	$string = json_encode($currentArr);
    	
    	try
    	{
    		$memcache = MemcacheMgr::getInstance();
    		$string = $memcache->set($cachekey, $string, CacheKeysTTL::$orgEntityDetails);
    		return $currentArr;
    	}catch(Exception $e)
    	{
    		return null;
    	}
    }
}

?>
