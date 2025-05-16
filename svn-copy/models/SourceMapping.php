<?php

/**
 * Description of SourceMapping
 *
 * @author rohit
 */

include_once 'models/BaseModel.php';
include_once 'exceptions/ApiSourceMappingModelException.php';
include_once 'filters/SourceMappingFilters.php';

class SourceMapping extends BaseApiModel {
    protected $id;
    protected $shortcode;
    protected $type;
    protected $command;
    protected $org_prefix_id;
    protected $org_prefix;
    protected $notes;
    protected $params;
    protected $whoami;
    protected $action_id;
    protected $org_id;
    protected $till_id;
    
    public static function setIterableMembers() {
        $classname = get_called_class();
        $classname::$iterableMembers = array(
                        "id",
                        "shortcode",
                        "type",
                        "command",
                        'org_prefix_id', 
                        'org_prefix', 
                        "notes",
                        "params",
                        "whoami",
                        "action_id",
                        "org_id",
                        "till_id"
        );
    }
    
    public static function isCommandAnOrgPrefix($command) {
        $sql = "SELECT k.`id`, k.`name`, v.`org_id`
                FROM `config_keys` AS k, `config_key_values` AS v
                WHERE k.`name` = 'CONF_MOBILE_TRIGGERS_ORG_PREFIX' AND v.`value` = '$command'";

        $rows = ShardedDbase::queryAllShards('masters', $sql, false);

        if ($rows) 
            return true;
        else 
            return false;
    }
    
    public static function loadAll($org_id, $filters)
    {
        if(isset($filters) && !($filters instanceof SourceMappingFilters))
	{
            throw new ApiSourceMappingModelException(ApiSourceMappingModelException::NO_SOURCE_MAPPING_MODULE_FOUND);
	}
        
        $sql = "SELECT
                sm.id as id,
                sm.shortcode as shortcode,
                sm.type as type,
                sm.command as command,
                sm.org_prefix_id as org_prefix_id, 
                op.prefix as org_prefix, 
                sm.notes as notes,
                sm.params as params,
                sm.whoami as whoami,
                sm.till_id as till_id,
                sm.action_id as action_id,
                sm.org_id as org_id
                FROM masters.sms_mapping as sm 
                LEFT OUTER JOIN masters.incoming_interaction_org_prefix as op ON sm.org_prefix_id = op.id 
                WHERE sm.is_active = 1 ";
        
        if(isset($org_id))
        {
            $sql .= "AND sm.org_id = $org_id ";
        }
            
        if(isset($filters))
        {
            if($filters->id)
            {
                $sql .= " AND sm.id = $filters->id";
            }
            if($filters->shortcode)
            {
                $sql .= " AND sm.shortcode = '$filters->shortcode'";
            }
            if($filters->command || $filters->command === '')
            {
                $sql .= " AND sm.command = '$filters->command'";
            }
            if ($filters -> org_prefix_id) {
                $sql .= " AND sm.org_prefix_id = " . $filters -> org_prefix_id;
            }
            if ($filters -> org_prefix) {
                $sql .= " AND op.prefix = '" . $filters -> org_prefix . "'";
            }
            if($filters->action_id)
            {
                $sql .= " AND sm.action_id = $filters->action_id";
            }
        }
        $sql .= " ORDER BY id DESC";
        //$db_master = new Dbase('masters');
        $rows = ShardedDbase::queryAllShards('masters', $sql, false);
        
        if($rows)
        {
            foreach($rows as $row)
            {
                $obj = self::fromArray($org_id, $row);
                $ret[] = $obj;
            }
            return $ret;
        }
        else
        {
            throw new ApiSourceMappingModelException(ApiSourceMappingModelException::NO_SOURCE_MAPPING_FOUND);
        }
    }
    
    public static function loadByShortcodeCommandAction($org_id, $shortcode, $command, $action_id)
    {
        $filters = new SourceMappingFilters();
        $filters->shortcode = $shortcode;
        $filters->command = $command;
        $filters->action_id = $action_id;
        $ret = self::loadAll($org_id, $filters);
        return $ret[0];
    }
    
    public static function loadByShortcodeCommand($shortcode, $command)
    {
        $filters = new SourceMappingFilters();
        $filters->shortcode = $shortcode;
        $filters->command = $command;
        $ret = self::loadAll($org_id, $filters);
        return $ret;
    }

    public static function loadByCommand($command) {
        $filters = new SourceMappingFilters();
        $filters -> command = $command;
        return self::loadAll(null, $filters);
    }

    public static function loadByShortcodeCommandOrgprefix($shortcode, $command, $orgPrefixId) {
        $filters = new SourceMappingFilters();
        $filters -> shortcode = $shortcode;
        $filters -> command = $command;
        $filters -> org_prefix_id = $orgPrefixId;
        return self::loadAll(null, $filters);
    }

    public static function loadByAction($org_id, $action_id)
    {
        $filters = new SourceMappingFilters();
        $filters->action_id = $action_id;
        $ret = self::loadAll($org_id, $filters);
        return $ret;
    }
    
    public static function loadById($org_id, $id)
    {
        $filters = new SourceMappingFilters();
        $filters->id = $id;
        $ret = self::loadAll($org_id, $filters);
        return $ret[0];
    }
    
    public function getId() {
        return $this->id;
    }

    public function getShortcode() {
        return $this->shortcode;
    }

    public function getType() {
        return $this->type;
    }

    public function getCommand() {
        return $this->command;
    }

    public function getOrgPrefixId() {
        return $this -> org_prefix_id;
    }

    public function getOrgPrefix() {
        return $this -> org_prefix;
    }

    public function getNotes() {
        return $this->notes;
    }

    public function getParams() {
        return $this->params;
    }

    public function getWhoami() {
        return $this->whoami;
    }

    public function getActionId() {
        return $this->action_id;
    }

    public function getOrgId() {
        return $this->org_id;
    }

    public function getTillId() {
        return $this->till_id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setShortcode($shortcode) {
        $this->shortcode = $shortcode;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function setCommand($command) {
        $this->command = $command;
    }

    public function setOrgPrefixId($id) {
        $this -> org_prefix_id = $id;
    } 

    public function setOrgPrefix($str) {
        $this -> org_prefix = $str;
    } 

    public function setNotes($notes) {
        $this->notes = $notes;
    }

    public function setParams($params) {
        $this->params = $params;
    }

    public function setWhoami($whoami) {
        $this->whoami = $whoami;
    }

    public function setActionId($action_id) {
        $this->action_id = $action_id;
    }

    public function setOrgId($org_id) {
        $this->org_id = $org_id;
    }
    public function setTillId($till_id) {
        $this->till_id = $till_id;
    }

    public function save()
    {
        $db = new Dbase('masters');
        
        if($this->id)
        {
            $sql = "UPDATE sms_mapping SET ";
            if(isset($this->type))
                $columns['type'] = "'". $this->type . "'";
            if(isset($this->action_id))
                $columns['action_id'] = $this->action_id;
            if(isset($this->command))
                $columns['command'] = "'" .$this->command . "'";
            if (isset($this -> org_prefix_id))
                $columns['org_prefix_id'] = $this -> org_prefix_id;
            if(isset($this->notes))
                $columns['notes'] = "'" . $this->notes . "'";
            if(isset($this->shortcode))
                $columns['shortcode'] = "'" . $this->shortcode . "'";
            if(isset($this->till_id))
                $columns['till_id'] = $this->till_id;
            if(isset($this->org_id))
                $columns['org_id'] = $this->org_id;
            if(isset($this->whoami))
                $columns['whoami'] = "'" . $this->whoami . "'";
            if(isset($this->params))
                $columns['params'] = "'" . $this->params . "'";
            
            foreach($columns as $key=>$value)
            {
		$sql .= " $key = $value, ";
            }
            
            $sql=substr($sql,0,-2);
            $sql .= " WHERE id = $this->id";
            $newId = $db->update($sql);
            
            if(! $newId)
            {
                throw new ApiSourceMappingModelException(ApiSourceMappingModelException::MAPPING_UPDATE_FAILED);
            }
            
        }
        else 
        {
            $sql = "INSERT INTO sms_mapping ";
            if(isset($this->type))
                $columns['type'] = "'". $this->type . "'";
            if(isset($this->action_id))
                $columns['action_id'] = $this->action_id;
            if(isset($this->command))
                $columns['command'] = "'" .$this->command . "'";
            if (isset($this -> org_prefix_id))
                $columns['org_prefix_id'] = $this -> org_prefix_id;
            if(isset($this->notes))
                $columns['notes'] = "'" . $this->notes . "'";
            if(isset($this->shortcode))
                $columns['shortcode'] = "'" . $this->shortcode . "'";
            if(isset($this->till_id))
                $columns['till_id'] = $this->till_id;
            if(isset($this->org_id))
                $columns['org_id'] = $this->org_id;
            if(isset($this->whoami))
                $columns['whoami'] = "'" . $this->whoami . "'";
            if(isset($this->params))
                $columns['params'] = "'" . $this->params . "'";
            $sql = "INSERT INTO  sms_mapping ";
            $sql .= "\n (". implode(",", array_keys($columns)).") ";
            $sql .= "\n VALUES ";
            $sql .= "\n (". implode(",", $columns).") ;";
            
            $sql=substr($sql,0,-2);
            $newId = $db->insert($sql);         
            if(! $newId)
            {
                throw new ApiSourceMappingModelException(ApiSourceMappingModelException::MAPPING_INSERT_FAILED);
            }
        }
        return $newId;
    }
}
