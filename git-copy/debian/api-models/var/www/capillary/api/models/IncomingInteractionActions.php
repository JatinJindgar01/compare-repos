<?php
/**
 * Description of SourceMappingActions
 *
 * @author rohit
 */

include_once 'models/BaseModel.php';
include_once 'models/ICacheable.php';
include_once 'exceptions/IncomingInteractionActionsModelException.php';
include_once 'filters/IncomingInteractionActionsFilters.php';

class IncomingInteractionActions extends BaseApiModel implements ICacheable{
    
    protected $id;
    protected $label;
    protected $code;
    protected $type;
    protected $is_valid;
    
    const CACHE_KEY_PREFIX_ID = 'API_INCOMING_ACTION_ID';
    const CACHE_KEY_PREFIX_CODE = 'API_INCOMING_ACTION_CODE';

    
    public static function setIterableMembers() {
        $classname = get_called_class();
        $classname::$iterableMembers = array(
                        "id",
                        "code",
                        "label",
                        "is_valid",
                        "type"
        );
    }
    
    public static function loadAll($filters)
    {
        if(isset($filters) && !($filters instanceof IncomingInteractionActionsFilters))
	{
            throw new IncomingInteractionActionsModelException(IncomingInteractionActionsModelException::FILTER_INVALID_OBJECT_PASSED);
	}
        else
        {
            $sql = "SELECT * 
                    FROM `masters`.`incoming_interaction_actions`
                    WHERE  
                    is_valid = 1 ";
            
            if($filters->id)
                $sql .= "AND id = $filters->id ";
            if($filters->type)
                $sql .= "AND  type = '$filters->type' ";
            if($filters->code)
                $sql .= "AND code = '$filters->code' ";
            
            $db = new Dbase('masters');
            $rows = $db->query($sql);
            
            if($rows)
            {
                $ret = array();
                foreach($rows as $row)
                {
                    $obj = IncomingInteractionActions::fromArray(-1, $row);
                    $cacheKeyId = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID, $obj->getId());
                    $cacheKeyCode = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE, $obj->getCode());
                    self::saveValueToCache($cacheKeyId, $obj->toString());
                    self::saveValueToCache($cacheKeyCode, $obj->toString());
                    $ret[] = $obj;
                }
                return $ret;
            }
            else
            {
                throw new IncomingInteractionActionsModelException(IncomingInteractionActionsModelException::NO_INCOMING_INTERACTION_ACTION_FOUND);
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
            $filters = new IncomingInteractionActionsFilters();
            $filters->id = $id;
            $ret = self::loadAll($filters);
            return $ret[0];
        }
    }
    
    public static function loadByCode($code)
    {
        $cacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE, $code);
        $ret = self::getFromCache($cacheKey);
        if($ret)
        {
            return self::fromString(-1, $ret);
        }
        else
        {
            $filters = new IncomingInteractionActionsFilters();
            $filters->code = $code;
            $ret = self::loadAll($filters);
            return $ret[0];
        }
    }
    
    public function getLabel() {
        return $this->label;
    }

    public function getType() {
        return $this->type;
    }
    
    public function setType($type) {
        $this->type = $type;
    }
    
    public function getCode() {
        return $this->code;
    }

    public function getIsValid() {
        return $this->is_valid;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLabel($label) {
        $this->label = $label;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setIsValid($is_valid) {
        $this->is_valid = $is_valid;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}

