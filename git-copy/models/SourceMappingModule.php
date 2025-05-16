<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SourceMappingModule
 *
 * @author capillary
 */

include_once 'models/BaseModel.php';
include_once 'models/ICacheable.php';
include_once 'exceptions/ApiSourceMappingModuleModelException.php';
include_once 'filters/SourceMappingModuleFilters.php';

class SourceMappingModule extends BaseApiModel implements ICacheable
{
    protected $code;
    protected $id;
    
    const CACHE_KEY_PREFIX_ID = 'API_SM_MODULE_ID';
    const CACHE_KEY_PREFIX_CODE = 'API_SM_MODULE_CODE';
    
    public static function setIterableMembers() {
        $classname = get_called_class();
        $classname::$iterableMembers = array(
                        "id",
                        "code"
        );
    }
    public static function loadAll($filters)
    {   
        if(isset($filters) && !($filters instanceof SourceMappingModuleFilters))
	{
            throw new ApiSourceMappingModuleModelException(ApiSourceMappingModuleModelException::NO_SOURCE_MAPPING_MODULE_FOUND);
	}
        
        $sql = "SELECT
                m.id as id,
                m.code as code
                FROM `store_management`.`modules` as m
                WHERE 1 ";
        if($filters->id)
            $sql .= " AND m.id = $filters->id ";
        if($filters->code)
            $sql .= " AND m.code = '$filters->code'";
        $sm_db = new Dbase('stores');
        $rows = $sm_db->query($sql);
        
        $ret = array();
        if($rows)
        {
            foreach($rows as $row)
            {
                $obj = self::fromArray(-1, $row);
                $ret[] = $obj;
                $codeCacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE, $obj->getCode());
                $idCacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_ID, $obj->getId());
                self::saveValueToCache($codeCacheKey, $obj->toString());
                self::saveValueToCache($idCacheKey, $obj->toString());
            }
            
            return $ret;
        }
        else 
        {
            throw new ApiSourceMappingModuleModelException(ApiSourceMappingModuleModelException::NO_SOURCE_MAPPING_MODULE_FOUND);
        }
    }
    
    public static function loadByCode($code)
    {
        $codeCacheKey = self::generateCacheKey(self::CACHE_KEY_PREFIX_CODE, $code);
        $ret = self::getFromCache($codeCacheKey);
        if($ret)
        {
            return self::fromString(-1, $ret);
        }
        else 
        {
            $filters = new SourceMappingModuleFilters();
            $filters->code = $code;
            $ret = self::loadAll($filters);
            return $ret[0];
        }
    }
    public function getCode() {
        return $this->code;
    }

    public function getId() {
        return $this->id;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setId($id) {
        $this->id = $id;
    }


    
    
    
}
