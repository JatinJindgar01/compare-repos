<?php
include_once ("models/IInteraction.php");
include_once ("models/ICacheable.php");
include_once ("models/BaseModel.php");
include_once ("models/filters/InteractionLoadFilters.php");

/**
 * 
 * @author Kartik
 * Base class for interaction
 *
 */
abstract class BaseInteraction extends BaseApiModel implements IInteraction, ICacheable{
	
	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;
	
	//message id for nsadmin
	protected $id;
	
	//TODO: need to check if we can cache this information
	CONST CACHE_TTL = 3600; // 1 hour
	protected static $iterableMembers = array();
	
	public function __construct($current_org_id)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
	
		$this->logger = $logger;
	
		// current org
		$this->current_org_id = $current_org_id;
	
		// db connection
		$this->db_user = new Dbase( 'users' );
	
		$className = get_called_class();
		$className::setIterableMembers();
	}
	
	public static function setIterableMembers()
	{
		//TODO: need to add few more members
		self::$iterableMembers = array(
				"id"
		);
	}
	
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $id;
	}
	

}

?>
