<?php
/**
 *
 * @author rohit
 */

include_once 'models/BaseModel.php';
include_once 'models/ICacheable.php';
include_once 'exceptions/IncomingInteractionActionParamsModelException.php';
include_once 'filters/IncomingInteractionActionParamsFilters.php';

class IncomingInteractionActionParams extends BaseApiModel implements ICacheable{
    
    protected $id;
    protected $action_id;
    protected $label;
    protected $code;
    protected $is_mandatory;
    protected $is_valid;
    
    const CACHE_KEY_PREFIX_ID = 'API_INCOMING_ACTION_PARAM_ID';
    const CACHE_KEY_PREFIX_ACTION_CODE = 'API_INCOMING_ACTION_PARAM_ACTION_ID_CODE_';
    
    public static function setIterableMembers() {
        $classname = get_called_class();
        $classname::$iterableMembers = array(
                        "id",
                        "action_id",
                        "label",
                        "is_mandatory",
                        "is_valid",
                        "type",
                        "code"
        );
    }
    
    public static function loadAll($filters)
    {
        if(isset($filters) && !($filters instanceof IncomingInteractionActionParamsFilters))
	{
            throw new IncomingInteractionActionParamsModelException(IncomingInteractionActionParamsModelException::FILTER_INVALID_OBJECT_PASSED);
	}
        else
        {
            $sql = "SELECT * 
                    FROM `masters`.`incoming_interaction_action_params`
                    WHERE  
                    is_valid = 1 ";
            
            if($filters->id)
                $sql .= "AND id = $filters->id ";
            if($filters->action_id)
                $sql .= "AND  action_id = $filters->action_id ";
            if($filters->code)
                $sql .= "AND code = '$filters->code' ";
            if($filters->is_mandatory != null)
                $sql .= "AND is_mandatory = $filters->is_mandatory ";
            
            $db = new Dbase('masters');
            
            $rows = $db->query($sql);
            
            if($rows)
            {
                $ret = array();
                foreach($rows as $row)
                {
                    $obj = IncomingInteractionActionParams::fromArray(-1, $row);
                    $cacheKeyId = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID, $obj->getId());
                    $cacheKeyCode = self::generateCacheKey(self::CACHE_KEY_PREFIX_ACTION_CODE, $obj->getActionId() . "##" . $obj->getCode());
                    self::saveValueToCache($cacheKeyId, $obj->toString());
                    self::saveValueToCache($cacheKeyCode, $obj->toString());
                    $ret[] = $obj;
                }
                return $ret;
            }
            else
            {
                throw new IncomingInteractionActionParamsModelException(IncomingInteractionActionParamsModelException::NO_INCOMING_INTERACTION_ACTION_PARAM_FOUND);
            }
        }
    }
    
    public static function loadById($id)
    {
        $cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID, $id);
        $ret = self::getFromCache($cacheKey);
        if($ret)
        {
            return self::fromString(-1, $ret);
        }
        else
        {
            $filters = new IncomingInteractionActionParamsFilters();
            $filters->id = $id;
            $ret = self::loadAll($filters);
            return $ret[0];
        }
    }
    
    public static function loadByActionIdCode($action_id, $code)
    {
        $cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_ACTION_CODE, $action_id . "##" . $code);
        $ret = self::getFromCache($cacheKey);
        if($ret)
        {
            return self::fromString(-1, $ret);
        }
        else
        {
            $filters = new IncomingInteractionActionParamsFilters();
            $filters->action_id = $action_id;
            $filters->code = $code;
            $ret = self::loadAll($filters);
            return $ret[0];
        }
    }
    
    public static function loadByActionId($action_id)
    {
        $filters = new IncomingInteractionActionParamsFilters();
        $filters->action_id = $action_id;
        $ret = self::loadAll($filters);
        return $ret;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getActionId() {
        return $this->action_id;
    }

    public function getLabel() {
        return $this->label;
    }

    public function getCode() {
        return $this->code;
    }

    public function getIsMandatory() {
        return $this->is_mandatory;
    }

    public function getIsValid() {
        return $this->is_valid;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setActionId($action_id) {
        $this->action_id = $action_id;
    }

    public function setLabel($label) {
        $this->label = $label;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setIsMandatory($is_mandatory) {
        $this->is_mandatory = $is_mandatory;
    }

    public function setIsValid($is_valid) {
        $this->is_valid = $is_valid;
    }

}

