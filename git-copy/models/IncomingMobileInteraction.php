<?php

/**
 * Description of IncomingMobileInteraction
 *
 * @author capillary
 */

include_once 'models/BaseInteraction.php';
include_once 'exceptions/ApiInteractionException.php';
include_once 'filters/IncomingMobileInteractionFilters.php';

class IncomingMobileInteraction extends BaseInteraction {
    
    protected $from;
    protected $msg;
    protected $to;
    protected $time;
    protected $is_used;
    protected $triggered_mappings;
    protected $org_id;


    public static function setIterableMembers() {
        $classname = get_called_class();
        $classname::$iterableMembers = array(
                        "id",
                        "from",
                        "to",
                        "time",
                        "is_used",
                        "triggered_mappings",
                        "msg",
                        "org_id"
        );
    }
    
    public static function loadAll($org_id = -1, $filters = null) { 
	if(isset($filters) && !($filters instanceof IncomingMobileInteractionFilters))
	{
            throw new ApiInteractionException(ApiInteractionException::FILTER_INVALID_OBJECT_PASSED);
	}
        
        $sql = "SELECT
                si.id as `id`,
                si.from as `from`,
                si.host as `to`,
                si.time as `time`,
                si.is_used as `is_used`,
                si.triggered_mappings as triggered_mappings
                FROM sms_in AS si WHERE 1 ";
        if ($filters->id)     
        {
            $sql .= "AND
                    si.id = $filters->id ";
        }
        if ($filters->from) 
        {
            $sql .= "AND
                    si.from = '$filters->from' ";
        }
        if ($filters->to) 
        {
            $sql .= "AND
                    si.host = '$filters->to' ";
        }
        if ($filters->is_used !== null)
        {
            $sql .= "AND
                    si.is_used = $filters->is_used ";
        }
        if($filters->interval)
        {
            $sql .= "AND
                    si.time > DATE_SUB(NOW(), INTERVAL $filters->interval MINUTE) ";
        }
        $db_um = new Dbase('users');
        //die(print_r($db_um, true));
        $rows = $db_um->query($sql);
        
        if($rows)
	{
            $ret = array();
            foreach($rows AS $row)
            {
                $obj = IncomingMobileInteraction::fromArray(-1, $row);
                $ret[] = $obj;
            }
	    return $ret;
        }
        else
        {
            throw new ApiInteractionException(ApiInteractionException::NO_INTERACTION_FOUND);
        }
    }
    
    public static function loadByReceiver($org_id, $reciver_identifier) {
        $filters = new IncomingMobileInteractionFilters();
        $filters->to = $reciver_identifier;
        return $this->loadAll(-1, $filters);   
    }
    
    public static function loadById($org_id, $id) {
        $filters = new IncomingMobileInteractionFilters();
        $filters->id = $id;
        return $this->loadAll(-1, $filters);
    }
    
    public static function loadUnusedFromIntervalBySenderInInterval($from, $interval) {
        $filters = new IncomingMobileInteractionFilters();
        $filters->from = $from;
        $filters->interval = $interval;
        $filters->is_used = 0;
        return self::loadAll(-1, $filters);   
    }
    
    public function getFrom() {
        return $this->from;
    }

    public function getMsg() {
        return $this->msg;
    }

    public function getTo() {
        return $this->to;
    }

    public function getTime() {
        return $this->time;
    }

    public function getIsUsed() {
        return $this->is_used;
    }

    public function getTriggeredMappings() {
        return $this->triggered_mappings;
    }

    public function setFrom($from) {
        $this->from = $from;
    }

    public function setMsg($msg) {
        $this->msg = $msg;
    }

    public function setTo($to) {
        $this->to = $to;
    }

    public function setTime($time) {
        $this->time = $time;
    }

    public function setIsUsed($is_used) {
        $this->is_used = $is_used;
    }

    public function setTriggeredMappings($triggered_mappings) {
        $this->triggered_mappings = $triggered_mappings;
    }
    
    public function setOrgId($org_id)
    {
        $this->org_id = $org_id;
    }
    
    public function getOrgId()
    {
        return $this->org_id;
    }
    
    public function save()
    {
        $db_um = new Dbase('users');
        
        if($this->id)
        {
            $sql = "UPDATE sms_in SET ";
            $columns['is_used'] = $this->is_used;
            
            foreach($columns as $key=>$value)
            {
		$sql .= " $key = $value, ";
            }
            
            $sql=substr($sql,0,-2);
            
            $sql .= " WHERE id = $this->id";
            $newId = $db_um->update($sql);
            
            if(! $newId)
            {
                throw new ApiInteractionException(ApiInteractionException::INTERATION_UPDATE_FAILED);
            }
        }
        else 
        {
            $sql = "INSERT INTO sms_mapping ";
            if(isset($this->from))
                $columns['`from`'] = "'". $this->from . "'";
            if(isset($this->to))
                $columns['`host`'] = $this->to;
            if(isset($this->triggered_mappings))
                $columns['`triggered_mappings`'] = "'" .$this->triggered_mappings . "'";
            if(isset($this->msg))
                $columns['`msg`'] = "'" . $this->msg . "'";
            $columns['time'] = "NOW()";
            $columns['org_id'] = $this->org_id;
            
            $sql = "INSERT INTO  sms_in ";
            $sql .= "\n (". implode(",", array_keys($columns)).") ";
            $sql .= "\n VALUES ";
            $sql .= "\n (". implode(",", $columns).") ;";
            
            $sql=substr($sql,0,-2);
            $db = new Dbase('users');
            $newId = $db->insert($sql);
            
            if(! $newId)
            {
                throw new ApiInteractionException(ApiInteractionException::INTERACTION_INSERT_FAILED);
            }
        }
        return $newId;
    }
}