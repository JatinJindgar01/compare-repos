<?php

include_once ("models/BaseModel.php");
include_once ("models/filters/CustomFieldValueLoadFilters.php");
include_once ("exceptions/ApiCustomFieldException.php");

/**
 * @author class
 *
 * The defines all the custome fields value actions
 * The table associated is user_management.custom_fields and user_management.custom_fields_value
*/
class CustomFieldValue extends BaseApiModel{

	protected $db_user;
	protected $logger;
	protected $current_user_id;
	protected $current_org_id;

	protected $custom_field_id;
	protected $custom_field_value;
	protected $custom_field_value_id;
	protected $assoc_id;

	protected $validationErrorArr;
	protected static $iterableMembers = array();

	/*
	 * Object to get the custom fields info
	*/
	public $customField;

	public function __construct($current_org_id, $cf_id =null, $assoc_id =null )
	{
		global $logger, $currentuser;
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;

		// setting the loggers
		$this->logger = &$logger;

		// setting the loggers
		$this->logger = &$logger;

		if($cf_id > 0)
			$this->custom_field_id = $cf_id;
		if($assoc_id>0)
			$this->assoc_id = $assoc_id;

		// current org
		$this->current_org_id = $current_org_id;

		// db connection
		$this->db_user = new Dbase( 'users' );
		
		$classname = get_called_class();
		$classname::setIterableMembers();
		
	}

	public static function setIterableMembers()
	{

		self::$iterableMembers = array(
				"custom_field_id",
				"custom_field_value",
				"custom_field_value_id",
				"assoc_id",
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
	public function getCustomFieldValue()
	{
		return $this->custom_field_value;
	}

	/**
	 *
	 * @param $name
	 */
	public function setCustomFieldValue($custom_field_value)
	{
		$this->custom_field_value = $custom_field_value;
	}

	/**
	 *
	 * @return
	 */
	public function getAssocId()
	{
		return $this->assoc_id;
	}

	/**
	 *
	 * @param $name
	 */
	public function setAssocId($assoc_id)
	{
		$this->assoc_id = $assoc_id;
	}

	/**
	 *
	 */
	public function getCustomFieldValueId()
	{
		return $this->custom_field_value_id;
	}

	public function getValidationErrorArr()
	{
		return $this->validationErrorArr;
	}
	
	/*
	 * save the custom fields
	* TODO: add any validation if required
	*/
	public function save()
	{
// 		if(!$this->validate())
// 		{
// 			$this->logger->debug("Validation has failed, returning now");
// 			throw new ApiCustomFieldException(ApiCustomFieldException::VALIDATION_FAILED);
// 		}
		$columns["cf_id"]= $this->custom_field_id;
		$columns["assoc_id"]= $this->assoc_id;
		$columns["value"]= "'".$this->custom_field_value."'";
		$columns["entered_by"]= $this->current_user_id;
		$columns["modified"]= "'".Util::getMysqlDateTime('now')."'";
		$columns["org_id"]= $this->current_org_id;

		$this->logger->debug("Ready to run the query");

		// 		foreach($columns as &$column)
		// 			$column = addcslashes($column, '\\');

		$sql = "INSERT INTO user_management.custom_fields_data ";
		$sql .= "\n (". implode(",", array_keys($columns)).") ";
		$sql .= "\n VALUES ";
		$sql .= "\n (". implode(",", $columns).") ";
		$sql .= "\n ON DUPLICATE KEY UPDATE
				value = VALUES(value), entered_by= values(entered_by), modified= values(modified)";
		$newId = $this->db_user->update($sql);
		$this->logger->debug("Return of saving the new cf is $newId");

		if($newId)
		{
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

	static public function load($org_id, $cf_id =null, $assoc_id = null)
	{
		global $logger;
		$logger->debug("Loading based on cf id and assoc id");

		if(!$assoc_id || !$cf_id)
		{
			throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_CF_ID_ASSOC_ID_NOT_PASSED);
			$logger->debug("Cf id or assoc id is not set yet");
		}

		$filters = new CustomFieldValueLoadFilters();
		$filters->custom_field_id = $cf_id;
		$filters->assoc_id = $assoc_id;
		try{
			$array = self::loadAll($org_id, $filters, 1);
		}catch(Exception $e){
			$logger->debug("Loading from db has failed");
		}

		if($array)
		{
			return $array[0];
		}
		throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_NON_EXISTING_CF_ID_ASSOC_ID_PASSED);
	}

	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		if(isset($filters) && !($filters instanceof CustomFieldValueLoadFilters))
		{
			throw new ApiCustomFieldException(ApiCustomFieldException::FILTER_INVALID_OBJECT_PASSED);
		}

		$sql = "SELECT
		cf.id as custom_field_id,
		cf.name,cf.label,
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
		cf.error,
		cf.attrs,
		cf.is_disabled,
		cf.disable_at_server as disabled_at_server,
		cf.is_compulsory,
		cfd.id as custom_field_value_id,
		cfd.assoc_id as assoc_id,
		cfd.value as custom_field_value
		FROM user_management.custom_fields as cf
		INNER JOIN user_management.custom_fields_data AS cfd
		ON cfd.org_id = cf.org_id AND cf.id = cfd.cf_id
		WHERE cf.org_id = $org_id ";

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

		$sql = $sql . " LIMIT  $offset, $limit";

		$db = new Dbase( 'users' );
		$array = $db->query($sql);

		if($array)
		{

			foreach ($array as $row)
			{
				$ret[] = CustomFieldValue::fromArray($org_id, $row);
			}
			return $ret;

		}

		throw new ApiCustomFieldException(ApiCustomFieldException::NO_CUSTOM_FIELD_VALUE_MATCHES);
	}

	/**
	 * initiate the respective class on demand
	 * @param $memberName - the object need to be initialized
	 */
	protected function initiateDependentObject($memberName)
	{
		switch(strtolower($memberName))
		{

			case 'customfield':
				if(!$this->customField instanceof CustomField)
				{
					include_once 'models/CustomField.php';
					$this->customField = new CustomField();
				}
				break;

			default:
				$this->logger->debug("Requested member could not be resolved");
					
		}

	}

}