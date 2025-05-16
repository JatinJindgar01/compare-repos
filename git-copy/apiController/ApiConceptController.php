<?php 
include_once 'apiController/ApiBaseController.php';
/**
 * It inherits entity controller
 * all the concept operation is done here .
 * At the back it contacts the entity controller to fetch the required values
 * 
 * @author prakhar
 *
 */
class  ApiConceptController extends ApiEntityController{
	
	public function  __construct(){
		
		parent::__construct( 'CONCEPT' );
	}
	
	/**
	 * add the new entity
	 * 
	 * @param array $concept_details
	 * 
	 * CONTRACT
	 * array(
	 * 'code' => value,
	 * 'name' => value,
	 * 'description' => value,
	 * 'time_concept_id' => value,
	 * 'currency_id' => value,
	 * 'language_id' => value,
	 * )
	 */
	public function add( $concept_details , $parent_id = false , $parent_type = false , $validate_code = true ){
		
		return parent::add( $concept_details , $parent_id , $parent_type , $validate_code );
	}

	/**
	 * update entity details
	 * 
	 * @param int $concept_id
	 * @param array $concept_details
	 * 
	 * CONTRACT
	 * array(
	 * 'code' => value,
	 * 'name' => value,
	 * 'description' => value,
	 * 'time_concept_id' => value,
	 * 'currency_id' => value,
	 * 'language_id' => value,
	 * )
	 */	
	public function update( $concept_id, $concept_details ){
		
		return parent::update( $concept_id, $concept_details );
	}
	
	/**
	 * Returns the concept as option for using in select box
	 * 
	 * @return array( name => concept_id )
	 */
	public function getConceptsAsOptions( $concept_id = false ){
		
		 $concepts = $this->getEntityAsOptions();
		 
		 if( $concept_id ){
		 	
		 	$concepts = array_flip( $concepts );
		 	unset ( $concepts[$concept_id] );
		 	
		 	if( $concept_id ){

		 		$children = $this->getChildrenEntityByType( $concept_id, 'CONCEPT' );
			 	foreach( $children as $c )
			 		unset( $concepts[$c] );
		 	}	
		 	
		 	$concepts = array_flip( $concepts );
		 }
		 
		 return $concepts;
	}
	
	/**
	 * Returns store ids
	 * 
	 * @return array( store_ids  )
	 */
	public function getStoresByConceptId( $concept_id ){
		
		return $this->getChildrenEntityByType( $concept_id, 'STORE');
	}

	/**
	 * Returns till_ids
	 * 
	 * @return array( till_ids  )
	 */
	public function getStoreTerminalsByConceptId( $concept_id ){
		
		return $this->getChildrenEntityByType( $concept_id, 'TILL');
	}

	/**
	 * Returns store server ids
	 * 
	 * @return array( store_server_ids )
	 */
	public function getStoreServerByConceptId( $concept_id ){
		
		return $this->getChildrenEntityByType( $concept_id, 'STR_SERVER');
	}
	
	/**
	 * Return hash
	 * @param unknown_type $concept_id
	 */
	public function getDetails( $concept_id ){
		
		return $this->getDetailsById( $concept_id );
	}
	
	/**
	 * Return hash
	 * @param unknown_type $concept_id
	 */
	public function getInfoDetails( $concept_id ){
		
		return parent::getInfoDetails( $concept_id );
	}
	
	/**
	 * 
	 * @param unknown_type $concept_id
	 * @param unknown_type $status
	 */
	public function setActiveStatus( $concept_id, $status ){
		
		$this->logger->debug("Set concept active status update : " . $concept_id);
		
		$result = parent::setActiveStatus( $concept_id, $status );
		
		if( $result == 'SUCCESS' && $status != 1 ){
			
			$child_stores = $this->getChildrenEntityByType( $concept_id, 'STORE');
			if( count( $child_stores ) > 0 ){
				$this->logger->debug("update child stores status : " .print_r( $child_stores ,true ) );
				$this->C_storeModel->updateChildStatus( 'STORE' , $child_stores , $this->org_id , $status );
			}
			
			$child_tills = $this->getChildrenEntityByType( $concept_id, 'TILL');
			if( count( $child_tills ) > 0 ){
				$this->logger->debug("update child tills status  : " .print_r( $child_tills ,true ) );
				$this->C_storeModel->updateChildStatus( 'TILL' , $child_tills , $this->org_id , $status );
			}
			
			$child_store_servers = $this->getChildrenEntityByType( $concept_id, 'STR_SERVER');
			if( count( $child_store_servers ) > 0 ){
				$this->logger->debug("update child store server status  : " .print_r( $child_store_servers ,true ) );
				$this->C_storeModel->updateChildStatus( 'STR_SERVER' , $child_store_servers , $this->org_id , $status );
			}
		}
		return $result;
	}
	
	/**
	 * Assigns the parent concept for the widget
	 * @param unknown_type $parent_concept_id
	 * @param unknown_type $parent_concept_id
	 */
	public function addParentConcept( $parent_concept_id, $child_concept_id ){

		try{

			$this->addParentEntity( $parent_concept_id, 'CONCEPT', $child_concept_id, 'CONCEPT' );
			
			return 'SUCCESS';
		}catch( Exception $e ){
			
			return $e->getMessage();
		}
	}
	
	/**
	 * Returns the parent zone
	 * 
	 * @param unknown_type $child_entity_type
	 * @param unknown_type $entity_id
	 */
	public function getParentConcept( $entity_id ){
		
		return $this->getParentEntityByType( $entity_id, 'CONCEPT' );
	}

	/**
	 * @param unknown_type $org_id
	 * @param array $entity_ids
	 */
	public static function getUsersEmailMapByConceptIds( $org_id, array $entity_ids ){
		
		return ApiEntityController::getUsersEmailMapByEntityIds( $org_id, $entity_ids );
	}
	
	/**
	 * retuns the managers assigned to the concept
	 * 
	 * @param $concept_id
	 * @return array( 
	 * 					array( 'admin_users_id' => value ),
	 * 					array( 'admin_users_id' => value ) 
	 * 			)
	 */
	public function getUsersById( $concept_id ){
		
		$AdminUser = new AdminUserController();
		return $AdminUser->getUserIdsByEntityId( $concept_id );
	}	
	
	/**
	 * retuns the managers assigned to the zone
	 * 
	 * @return array( 
	 * 					array( 'admin_users_id' => value ),
	 * 					array( 'admin_users_id' => value ) 
	 * 			)
	 */
	public function getUsersByType(  ){
		
		$AdminUser = new AdminUserController();
		return $AdminUser->getUserIdsByEntityType( $this->entity_type );
	}
	
	/**
	 * Returns SUCCESS/FAILURE
	 * 
	 * add the manager to concept 
	 * 
	 * @param $concept_id
	 * @param $manager_id
	 */
	public function assignManager( $concept_id, $manager_ids ){
		
		$AdminUser = new AdminUserController();
		return $AdminUser->addUserToEntity( $concept_id, $manager_ids, 'CONCEPT' );
	}

	/**
	 * 
	 * @param unknown_type $entity_type
	 */
	public function getAll( $include_inactive = false ){
		
		return parent::getAll( 'CONCEPT', $include_inactive );
	}
	
	/**
	 * get all the inactive concepts for particular organization
	 */
	public function getAllInActive(){
		return parent::getAllInActive( 'CONCEPT' );
	}
	
	/**
	 * 
	 * @param $concept_ids
	 * @param $include_inactive
	 */
	public function getByIds( $concept_ids, $include_inactive = false ){
		
		return parent::getByIds( $concept_ids, 'CONCEPT', $include_inactive );
	}
	
	/**
	 * Returns the managers for the entities
	 * @param unknown_type $entity_id
	 */
	public function getManagers( $entity_id ){
		
		return parent::getManagers( $entity_id );
	}
	
	/**
	 * get default zone
	 */
	public function getDefault(){
		
		return parent::getDefault();
	}
	
	/**
	 * 
	 * It will update the timezone of all the concepts under this organization
	 * @param int $time_zone_id
	 */
	public function updateConceptsTimezone( $time_zone_id ){
		
		$this->logger->debug("Start of concepts timezone updation org_id: $this->org_id, entity type: $this->entity_type");
		
		return $this->C_storeModel->updateTimeZonesByEntityType( $this->entity_type , $this->org_id , $time_zone_id );
	}
}

?>