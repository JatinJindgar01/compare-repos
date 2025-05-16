<?php
include_once ("models/ICustomer.php");
include_once ("models/ICacheable.php");
include_once ("models/filters/CustomerLoadFilters.php");
include_once ("exceptions/ApiCustomerException.php");
include_once ("models/BaseModel.php");

/**
 * @author cj
 *
 * The base class for all the customers.
 * The data will be flowing to user_management.users, user_management.extd_user_profile (deprecated)
 *
*/

abstract class BaseCustomer extends BaseApiModel implements  ICustomer, ICacheable{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;
	protected $cache_key_prefix;
	protected $validationErrorArr;
	protected $configMgr;
	
	public $slab;
	public $customerPointsSummary;
	
	/*
	 * The variables to uses table
	*/

	protected $user_id;
	protected $firstname;
	protected $lastname;
	protected $email;
	protected $mobile;
	protected $last_login;
	
	CONST CACHE_TTL = 3600; // 1 hour

	protected static $iterableMembers = array();

	/**
	 * @param unknown_type $current_org_id - org id for which operation is done
	 * @param unknown_type $user_id - primary key of the user
	*/
	public function __construct($current_org_id, $user_id = null)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		$this->logger = $logger;

		// current org
		$this->current_org_id = $current_org_id;

		// set the user if is passed
		$user_id > 0 ? $this->user_id = $user_id : null;

		// db connection
		$this->db_user = new Dbase( 'users' );

		$classname = get_called_class();
		$classname::setIterableMembers();
	}

	public static function setIterableMembers()
	{
		self::$iterableMembers = array(
				"user_id",
				"firstname",
				"lastname",
				"email",
				"mobile",
				"last_login",
		);

	}

	/**
	 *
	 * @return
	 */
	public function getUserId()
	{
		return $this->user_id;
	}

	/**
	 *
	 * @param $user_id
	 */
	public function setUserId($user_id)
	{
		$this->user_id = $user_id;
	}

	/**
	 *
	 * @return
	 */
	public function getFirstname()
	{
		return $this->firstname;
	}

	/**
	 *
	 * @param $firstname
	 */
	public function setFirstname($firstname)
	{
		$this->firstname = $firstname;
	}

	/**
	 *
	 * @return
	 */
	public function getLastname()
	{
		return $this->lastname;
	}

	/**
	 *
	 * @param $lastname
	 */
	public function setLastname($lastname)
	{
		$this->lastname = $lastname;
	}

	/**
	 *
	 * @return
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 *
	 * @param $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 *
	 * @return
	 */
	public function getMobile()
	{
		return $this->mobile;
	}

	/**
	 *
	 * @param $mobile
	 */
	public function setMobile($mobile)
	{
		$this->mobile = $mobile;
	}

	/**
	 *
	 * @return
	 */
	public function getLastLogin()
	{
		return $this->last_login;
	}

	public function getValidationErrorArr()
	{
		return $this->validationErrorArr;
	}

	public function getCustomerPointsSummary()
	{
		return $this->customerPointsSummary;
	}
	
	public function getSlab()
	{
		return $this->slab;
	}
	
	/*
	 *  The function saves the data in to DB or any other data source,
	*  all the values need to be set using the corresponding setter methods.
	*  This can update the existing record if the id is already set.
	*
	*  TODO: campaign user already created
	*/
	public function save()
	{

// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			throw new ApiException(ApiException::VALIDATION_FAILED);
// 		}

		$colNames = array(); $colValues = array();

		if(isset($this->firstname))
			$columns["firstname"]= "'".$this->firstname."'";

		if(isset($this->firstname))
			$columns["lastname"]= "'".$this->lastname."'";

		if(isset($this->email))
			$columns["email"]= "'".$this->email."'";

		if(isset($this->mobile))
			$columns["mobile"]= "'".$this->mobile."'";

		$columns["last_login"]= "NOW()";

		// new user
		if(!$this->user_id)
		{
			$this->logger->debug("User id is not set, so its going to be an insert query");

			$columns["org_id"]= $this->current_org_id;
				
			$sql = "INSERT INTO user_management.users ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);
				
			$this->logger->debug("Return of saving the new user is $newId");
				
			if($newId > 0)
				$this->user_id = $newId;
				
		}
		else
		{
			$this->logger->debug("User id is set, so its going to be an update query");
			$sql = "UPDATE user_management.users SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";
				
			$sql=substr($sql,0,-2);
				
			$sql .= " WHERE id = $this->user_id";
			$newId = $this->db_user->update($sql);
		}

		if($newId)
		{
			return true;
		}
		else
		{
			throw new ApiException(ApiException::SAVING_DATA_FAILED);
		}
	}

	/*
	 * Validate the data before saving to DB/ for insert and update
	*/
	public function validate()
	{
		$this->logger->debug("Validating the save to users table");

		//TODO: fill the validators here

		// new user validate accordingly
		if(!$this->user_id)
		{
			$this->logger->debug("New user");
				
			return true;
		}
			
		//
		else
		{
			$this->logger->debug("Existing user");
				
			return true;
		}
	}

	/*
	 *  The function loads the data linked to the object, based on the id set using setter method
	*/
	public static function loadById($org_id, $user_id)
	{
		global $logger;

		if(!$user_id)
		{
			$logger->debug("User id not set yet");
			throw new ApiException(ApiException::FILTER_NOT_PASSED);
		}

		$sql = "SELECT
		id as user_id,
		firstname,
		lastname,
		email,
		mobile,
		last_login
		FROM user_management.users AS u
		WHERE u.id = $user_id
		AND u.org_id = $org_id";
		$db = new Dbase('users');
		$array = $db->query_firstrow($sql);
			
		if($array)
		{
			return self::fromArray($array);
		}

		throw new ApiException(ApiException::NON_EXISTING_ID_PASSED);
	}

	/*
	 * Load all the data into object based on the filters being passed.
	* It should optionally decide whether entire dependency tree is required or not
	*/
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0){
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
	}

	/*
	 * Loads all the transactions of the customer to object.
	* The setter method has to be used prior to set the customer id
	*/
	public function loadTransactions($limit=100, $offset = 0){
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
	}

	/**
	 * initiate the respective class on demand
	 * @param $memberName - the object need to be initialized
	 */
	protected function initiateDependentObject($memberName)
	{
		$this->logger->debug("Lazy loading the $memberName object");
		switch(strtolower($memberName))
		{
			case 'configmgr':
				if(!$this->configMgr instanceof ConfigManager)
				{
					$this->configMgr = new ConfigManager($this->current_org_id);
					$this->logger->debug("Loaded config manager");
				}
				break;

			case 'customerpointssummary':
				include_once 'models/CustomerPointsSummary.php';
				if(!$this->customerPointsSummary instanceof CustomerPointsSummary)
				{
					$this->customerPointsSummary = new CustomerPointsSummary($this->current_org_id, $this->user_id);
					$this->logger->debug("Loaded config manager");
				}
				break;


			case 'slab':
				include_once 'models/CustomerSlab.php';
				if(!$this->slab instanceof CustomerSlab)
				{
					$this->slab = new CustomerSlab($this->current_org_id, $this->user_id);
					$this->logger->debug("Loaded slab object");
				}
				break;

		}
	}

}
