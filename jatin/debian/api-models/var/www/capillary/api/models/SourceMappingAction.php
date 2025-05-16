<?php

/**
 * Description of SourceMappingActions
 *
 * @author rohit
 */

include_once 'models/BaseModel.php';
include_once 'models/ICacheable.php';
include_once 'exceptions/ApiSourceMappingActionModelException.php';
include_once 'filters/SourceMappingActionFilters.php';

class SourceMappingAction extends BaseApiModel implements ICacheable{
    
    protected $id;
    protected $name;
    protected $code;
    protected $is_valid;
    
    const CACHE_KEY_PREFIX_ID = 'API_SM_ACTION_ID';
    const CACHE_KEY_PREFIX_CODE = 'API_SM_ACTION_CODE';
    
    public static function setIterableMembers() {
        $classname = get_called_class();
        $classname::$iterableMembers = array(
                        "id",
                        "code",
                        "name",
                        "offline_supported",
                        "offline_source",
                        "is_active",
                        "module_id"
        );
    }
    
    public static function loadAll($filters)
    {
        if(isset($filters) && !($filters instanceof SourceMappingActionFilters))
	{
            throw new ApiSourceMappingActionModelException(ApiSourceMappingActionModelException::FILTER_INVALID_OBJECT_PASSED);
	}
        else
        {
            $sql = "SELECT * 
                    FROM `store_management`.`actions`
                    WHERE 
                    offline_supported = 1 
                    AND is_active = 1 ";
            
            if($filters->id)
                $sql .= "AND id = $filters->id ";
            if($filters->module_id)
                $sql .= "AND  module_id = $filters->module_id ";
            if($filters->code)
                $sql .= "AND code = '$filters->code' ";
            
            $db_sm = new Dbase('stores');
            $rows = $db_sm->query($sql);
            if($rows)
            {
                $ret = array();
                foreach($rows as $row)
                {
                    $obj = SourceMappingAction::fromArray(-1, $row);
                    $cacheKeyCode = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE, $obj->getModuleId().'##'.$obj->getCode());
                    $cacheKeyId = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID, $obj->getId());
                    self::saveValueToCache($cacheKeyCode, $obj->toString());
                    self::saveValueToCache($cacheKeyId, $obj->toString());
                    $ret[] = $obj;
                }
                return $ret;
            }
            else
            {
                throw new ApiSourceMappingActionModelException(ApiSourceMappingActionModelException::NO_SOURCE_MAPPING_ACTION_FOUND);
            }
        }
    }
    
    public static function loadByModuleAndCode($module_id, $code)
    {
        
        $cacheKeyCode = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE, $module_id.'##'.$code);
        $ret = self::getFromCache($cacheKeyCode);
        if($ret)
        {
            return self::fromString(-1, $ret);
        }
        else
        {
            $filters = new SourceMappingActionFilters();
            $filters->module_id = $module_id;
            $filters->code = $code;
            $ret = self::loadAll($filters);
            return $ret[0];
        }
    }
    
    public function getName() {
        return $this->name;
    }

    public function getCode() {
        return $this->code;
    }

    public function getOfflineSupported() {
        return $this->offline_supported;
    }

    public function getOfflineSource() {
        return $this->offline_source;
    }

    public function getIsActive() {
        return $this->is_active;
    }

    public function getModuleId() {
        return $this->module_id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setOfflineSupported($offline_supported) {
        $this->offline_supported = $offline_supported;
    }

    public function setOfflineSource($offline_source) {
        $this->offline_source = $offline_source;
    }

    public function setIsActive($is_active) {
        $this->is_active = $is_active;
    }

    public function setModuleId($module_id) {
        $this->module_id = $module_id;
    }
        
    public function setId($id)
    {
        $this->id = $id;
    }
}
