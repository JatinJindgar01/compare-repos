<?php

include_once ("models/BaseModel.php");
include_once ("models/filters/CustomFieldValueLoadFilters.php");
include_once ("exceptions/ApiCustomFieldException.php");
include_once 'models/CustomFieldValue.php';

/**
 * @author class
 *
 * The defines all the custome fields key available in the system
 * The linked table is user_management.custom_fields
 */
class CustomField extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $custom_field_id;
	protected $name;
	protected $label;
	protected $type;
	protected $datatype;
	protected $scope;
	protected $default;
	protected $phase;
	protected $position;
	protected $helptext;
	protected $rule;
	protected $server_rule;
	protected $regex;
	protected $error;
	protected $attrs;
	protected $is_disabled;
	protected $disabled_at_server;
	protected $is_compulsory;
	protected $validationErrorArr;

	CONST CACHE_KEY_PREFIX = "CF_ID#";
	CONST CACHE_KEY_PREFIX_SCOPE_NAME = "CF_SCOPE_NAME#";
	
	public $customFieldValue;

	protected static $iterableMembers = array();

	public function __construct($current_org_id, $custom_field_id = null, $scope = null, $name = null)
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		// setting the loggers
		$this->logger = &$logger;

		// current org
		$this->current_org_id = $current_org_id;
		if($custom_field_id>0)
			$this->custom_field_id = $custom_field_id;
		if($scope)
			$this->scope = $scope;
		if($name)
			$this->name = $name;

		// db connection
		$this->db_user = new Dbase( 'users' );
		
		$classname = get_called_class();
		$classname::setIterableMembers();
	}

	public static function setIterableMembers()
	{

		self::$iterableMembers = array(
				"custom_field_id",
				"name",
				"label",
				"type",
				"datatype",
				"scope",
				"default",
				"phase",
				"position",
				"helptext",
				"rule",
				"server_rule",
				"regex",
				"error",
				"attrs",
				"is_disabled",
				"disabled_at_server",
				"is_compulsory",
				"customFieldValue",
		);
	}

	/**
	 *
	 * @return
	 */
	public function getCustomFieldId()
	{
		return $this->custom_field_id;
	}

	/**
	 *
	 * @param $custom_field_id
	 */
	public function setCustomFieldId($custom_field_id)
	{
		$this->custom_field_id = $custom_field_id;
	}

	/**
	 *
	 * @return
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *
	 * @param $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 *
	 * @return
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 *
	 * @param $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}

	/**
	 *
	 * @return
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 *
	 * @param $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 *
	 * @return
	 */
	public function getDatatype()
	{
		return $this->datatype;
	}

	/**
	 *
	 * @param $datatype
	 */
	public function setDatatype($datatype)
	{
		$this->datatype = $datatype;
	}

	/**
	 *
	 * @return
	 */
	public function getScope()
	{
		return $this->scope;
	}

	/**
	 *
	 * @param $scope
	 */
	public function setScope($scope)
	{
		$this->scope = $scope;
	}

	/**
	 *
	 * @return
	 */
	public function getDefault()
	{
		return $this->default;
	}

	/**
	 *
	 * @param $default
	 */
	public function setDefault($default)
	{
		$this->default = $default;
	}

	/**
	 *
	 * @return
	 */
	public function getPhase()
	{
		return $this->phase;
	}

	/**
	 *
	 * @param $phase
	 */
	public function setPhase($phase)
	{
		$this->phase = $phase;
	}

	/**
	 *
	 * @return
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 *
	 * @param $position
	 */
	public function setPosition($position)
	{
		$this->position = $position;
	}

	/**
	 *
	 * @return
	 */
	public function getHelptext()
	{
		return $this->helptext;
	}

	/**
	 *
	 * @param $helptext
	 */
	public function setHelptext($helptext)
	{
		$this->helptext = $helptext;
	}

	/**
	 *
	 * @return
	 */
	public function getRule()
	{
		return $this->rule;
	}

	/**
	 *
	 * @param $rule
	 */
	public function setRule($rule)
	{
		$this->rule = $rule;
	}

	/**
	 *
	 * @return
	 */
	public function getServerRule()
	{
		return $this->server_rule;
	}

	/**
	 *
	 * @param $server_rule
	 */
	public function setServerRule($server_rule)
	{
		$this->server_rule = $server_rule;
	}

	/**
	 *
	 * @return
	 */
	public function getRegex()
	{
		return $this->regex;
	}

	/**
	 *
	 * @param $regex
	 */
	public function setRegex($regex)
	{
		$this->regex = $regex;
	}

	/**
	 *
	 * @return
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 *
	 * @param $error
	 */
	public function setError($error)
	{
		$this->error = $error;
	}

	/**
	 *
	 * @return
	 */
	public function getAttrs()
	{
		return $this->attrs;
	}

	/**
	 *
	 * @param $attrs
	 */
	public function setAttrs($attrs)
	{
		$this->attrs = $attrs;
	}

	/**
	 *
	 * @return
	 */
	public function getIsDisabled()
	{
		return $this->is_disabled;
	}

	/**
	 *
	 * @param $is_disabled
	 */
	public function setIsDisabled($is_disabled)
	{
		$this->is_disabled = $is_disabled;
	}

	/**
	 *
	 * @return
	 */
	public function getDisabledAtServer()
	{
		return $this->disabled_at_server;
	}

	/**
	 *
	 * @param $disabled_at_server
	 */
	public function setDisabledAtServer($disabled_at_server)
	{
		$this->disabled_at_server = $disabled_at_server;
	}

	/**
	 *
	 * @return
	 */
	public function getIsCompulsory()
	{
		return $this->is_compulsory;
	}

	/**
	 *
	 * @param $is_compulsory
	 */
	public function setIsCompulsory($is_compulsory)
	{
		$this->is_compulsory = $is_compulsory;
	}

	public function getCustomFieldValue()
	{
		return $this->customFieldValue;
	}

	public function getCustomFieldValueString()
	{
		if($this->customFieldValue instanceof  CustomFieldValue)
		{
			return $this->customFieldValue->getCustomFieldValue();
		}
		return null;
	}
	
	// it set the custom field, can be passed as string, object or string
	public function setCustomFieldValue($obj)
	{
		if($obj instanceof CustomFieldValue)
			$this->customFieldValue = $obj;
		else if(is_array($obj))
			$this->customFieldValue = CustomFieldValue::fromArray($this->current_org_id, $obj);
		else if(is_string($obj))
			$this->customFieldValue = CustomFieldValue::fromString($this->current_org_id, $obj);
	}
	
	
	
	public function getValidationErrorArr()
	{
		return $this->validationErrorArr;
	}

	public function save()
	{
// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			throw new ApiCustomFieldException(ApiCustomFieldException::VALIDATION_FAILED);
// 		}

		if(isset($this->name))
			$columns["name"]= "'".$this->name."'";
		if(isset($this->label))
			$columns["label"]= "'".$this->label."'";
		if(isset($this->type))
			$columns["type"]= "'".$this->type."'";
		if(isset($this->datatype))
			$columns["datatype"]= "'".$this->datatype."'";
		if(isset($this->default))
			$columns["`default`"]= "'".$this->default."'";
		if(isset($this->scope))
			$columns["scope"]= "'".$this->scope."'";
		if(isset($this->phase))
			$columns["phase"]= "'".$this->phase."'";
		if(isset($this->position))
			$columns["position"]= "'".$this->position."'";
		if(isset($this->helptext))
			$columns["helptext"]= "'".$this->helptext."'";
		if(isset($this->rule))
			$columns["server_rule"]= "'".$this->server_rule."'";
		if(isset($this->regex))
			$columns["regex"]= "'".$this->regex."'";
		if(isset($this->error))
			$columns["error"]= "'".$this->error."'";
		if(isset($this->attrs))
			$columns["attrs"]= "'".$this->attrs."'";
		if(isset($this->is_disabled))
			$columns["is_disabled"]= "'".$this->is_disabled."'";
		if(isset($this->disabled_at_server))
			$columns["disable_at_server"]= "'".$this->disabled_at_server."'";
		if(isset($this->is_compulsory))
			$columns["is_compulsory"]= "'".$this->is_compulsory."'";

		$columns["last_modified"]= $this->current_user_id;
		$columns["modified_by"]= "'".Util::getMysqlDateTime('now')."'";

		// new user
		if(!$this->custom_field_id)
		{
			$this->logger->debug("CF id is not set, so its going to be an insert query");

			$columns["org_id"]= $this->current_org_id;

			$sql = "INSERT INTO user_management.custom_fields ";
			$sql .= "\n (". implode(",", array_keys($columns)).") ";
			$sql .= "\n VALUES ";
			$sql .= "\n (". implode(",", $columns).") ;";
			$newId = $this->db_user->insert($sql);

			$this->logger->debug("Return of saving the new cf is $newId");

			if($newId > 0)
				$this->custom_field_id = $newId;

		}
		else
		{
			$this->logger->debug("CF id is set, so its going to be an update query");
			$sql = "UPDATE user_management.custom_fields SET ";

			// formulate the update query
			foreach($columns as $key=>$value)
				$sql .= " $key = $value, ";

			// remove the extra comma
			$sql=substr($sql,0,-2);

			$sql .= "WHERE id = $this->custom_field_id";
			$newId = $this->db_user->update($sql);
		}

		if($newId)
		{
			$cacheKey = CustomField::generateCacheKey(CustomField::CACHE_KEY_PREFIX, $this->custom_field_id, $this->current_org_id);
			CustomField::saveToCache($cacheKey, "");
			$obj = CustomField::loadById($this->current_org_id, $this->custom_field_id);
				
			return true;
		}
		else
		{
			throw new ApiCustomFieldException(ApiCustomFieldException::SAVING_DATA_FAILED);
		}
	}

	/*
	 * Validate ann the saves and updates.
	* TODO: add the validators
	*/
	public function validate()
	{
		return true;
	}

	public static function loadById($org_id, $cf_id)
	{
		global $logger;
		$logger->debug("Loading based on cf id");
		if(!$cf_id)
		{
			$logger->debug("Cf id not set yet");
			throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_CF_ID_NOT_PASSED);
		}

		$cacheKey = self::generateCacheKey(CustomField::CACHE_KEY_PREFIX, $cf_id,  $org_id);
		if($obj = self::loadFromCache($org_id, $cacheKey))
		{
			$logger->debug("Reading from cache was successful");
			return $obj; 
		}
		
		$filters = new CustomFieldValueLoadFilters();
		$filters->custom_field_id = $cf_id;
		try{
			$array = CustomField::loadAll($org_id, $filters, 1);
		}catch(Exception $e){
			$logger->debug("Loading from db has failed");
			throw new ApiCustomFieldException(ApiCustomFieldException::NO_CUSTOM_FIELD_VALUE_MATCHES);
		}

		if($array)
		{
			return $array[0];
		}
		throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_NON_EXISTING_CF_ID_PASSED);

	}

	/*
	 * Loads a custom fields by name and scope 
	 */
	public static function loadByName($org_id, $name , $scope )
	{
		global $logger;
		$filters = new CustomFieldValueLoadFilters();
		if($name)
			$filters->custom_field_name = $name;
		if($scope)
			$filters->custom_field_scope = $scope;
	
		if((!$filters->custom_field_scope || !$filters->custom_field_name))
			throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_INVALID_OBJECT_PASSED);
	
		$cacheKeyName = self::generateCacheKey(CustomField::CACHE_KEY_PREFIX_SCOPE_NAME, $scope."#".$name, $org_id);
		if($obj = self::loadFromCache($org_id, $cacheKeyName))
		{
			$logger->debug("Reading from cache was successful");
			return $obj;
		}
		
		try{
			$array = CustomField::loadAll($org_id, $filters, 1);
		}catch(Exception $e){
			$logger->debug("Loading from db has failed");
			throw new ApiCustomFieldException(ApiCustomFieldException::NO_CUSTOM_FIELD_VALUE_MATCHES);
		}
	
		if($array)
		{
			return $array[0];
		}
	
		throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_NON_EXISTING_CF_ID_PASSED);
	}
	
	public static function loadForAssocId($org_id, $assoc_id, $scope = null, $cf_id = null)
	{
		global $logger;
		$filters = new CustomFieldValueLoadFilters();
		if($assoc_id > 0)
			$filters->assoc_id = $assoc_id;
		if($assoc_id > 0)
			$filters->custom_field_id = $cf_id;
		$filters->custom_field_scope = $scope;

		if(!($filters->assoc_id || $filters->custom_field_id))
			throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_INVALID_CF_OBJECT_PASSED);
		
		try{
			$array = CustomField::loadAll($org_id, $filters, 1);
		}catch(Exception $e){
			$logger->debug("Loading from db has failed");
			throw new ApiCustomFieldException(ApiCustomFieldException::NO_CUSTOM_FIELD_VALUE_MATCHES);
		}
		
		if($array)
		{
			return $array;
		}
		
		throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_NON_EXISTING_CF_ID_PASSED);
		
		
	}
	
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof CustomFieldValueLoadFilters))
		{
			throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_INVALID_CF_OBJECT_PASSED);
		}
		$includeCFData = false;
		if($filters && ($filters->custom_field_value || $filters->custom_field_data_id || $filters->assoc_id))
			$includeCFData = true;

		$sql = "SELECT
		cf.id as custom_field_id,
		cf.name,
		cf.label,
		cf.type,
		cf.datatype,
		cf.scope,
		cf.default,
		cf.phase,
		cf.position,
		cf.helptext,
		cf.rule,
		cf.server_rule,
		cf.regex,
		cf.error,cf.attrs,
		cf.is_disabled,
		cf.disable_at_server as disabled_at_server,
		cf.is_compulsory ";
		
		if($includeCFData)
		{
			$sql .=" ,cfd.id as custom_field_value_id,
					cfd.assoc_id as assoc_id,
					cfd.value as custom_field_value
					";
		}
		$sql .= " FROM user_management.custom_fields as cf ";
		
		if($includeCFData)
		{
			$sql .= " INNER JOIN user_management.custom_fields_data AS cfd
				ON cfd.org_id = cf.org_id AND cf.id = cfd.cf_id ";
		}
		
		$sql .= " WHERE cf.org_id = $org_id ";

		if($filters->custom_field_id)
			$sql .= " AND cf.id = ".$filters->custom_field_id;
		if($filters->custom_field_scope)
			$sql .= " AND cf.scope = '".$filters->custom_field_scope."'";
		if($filters->custom_field_name)
			$sql .= " AND cf.name = '".$filters->custom_field_name."'";
		if($filters->custom_field_value) 
			$sql .= " AND cfd.value = '".$filters->custom_field_value."'";
		if($filters->custom_field_data_id)
			$sql .= " AND cfd.id = ".$filters->custom_field_data_id."";
		if($filters->assoc_id)
			$sql .= " AND cfd.assoc_id = '".$filters->assoc_id."'";
		

		$sql .= " ORDER BY cf.position asc ";
			
		if($limit>0 && $limit<100)
			$limit = intval($limit);
		else
			$limit = 20;

		if($offset>0 )
			$offset = intval($offset);
		else
			$offset = 0;

		$sql = $sql . " LIMIT $offset,$limit ";
		$db = new Dbase( 'users' );
		$array = $db->query($sql);

		if($array)
		{
			$ret = array();
			foreach($array as $row)
			{
				$obj = CustomField::fromArray($org_id, $row);
				if($includeCFData)
				{
					$obj->setCustomFieldValue($row);
				}
				$cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX, $obj->getCustomFieldId(), $org_id);
				$cacheKeyName = self::generateCacheKey(self::CACHE_KEY_PREFIX_SCOPE_NAME, $obj->getScope()."#".$obj->getName(), $org_id);
				
				if(!CustomField::loadFromCache($org_id, $cacheKey))
				{
					$obj->saveToCache($cacheKey, $obj->toString());
					$obj->saveToCache($cacheKeyName, $obj->toString());
				}
				$ret[] = $obj;
			}
			
			return $ret;
		}

		return false;

	}
}