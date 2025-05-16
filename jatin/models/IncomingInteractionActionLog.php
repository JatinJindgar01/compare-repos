<?php

/**
 * Description of IncomingInteractionActionLog
 *
 * @author rohit
 */
include_once 'models/BaseModel.php';

class IncomingInteractionActionLog extends BaseAPiModel{
    
    protected $id;
    protected $org_id;
    protected $interaction_id;
    protected $mapping_id;
    protected $action_id;
    protected $api_success;
    protected $api_item_code;
    protected $api_item_success;
    protected $api_item_message;
    protected $added_on;
    protected $added_by;
    
    public function getId() {
        return $this->id;
    }

    public function getOrgId() {
        return $this->org_id;
    }

    public function getInteractionId() {
        return $this->interaction_id;
    }

    public function getMappingId() {
        return $this->mapping_id;
    }

    public function getActionId() {
        return $this->action_id;
    }

    public function getApiSuccess() {
        return $this->api_success;
    }

    public function getApiItemCode() {
        return $this->api_item_code;
    }

    public function getApiItemStatus() {
        return $this->api_item_staus;
    }

    public function getApiItemMessage() {
        return $this->api_item_message;
    }

    public function getAddedOn() {
        return $this->added_on;
    }

    public function getAddedBy() {
        return $this->added_by;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setOrgId($org_id) {
        $this->org_id = $org_id;
    }

    public function setInteractionId($interaction_id) {
        $this->interaction_id = $interaction_id;
    }

    public function setMappingId($mapping_id) {
        $this->mapping_id = $mapping_id;
    }

    public function setActionId($action_id) {
        $this->action_id = $action_id;
    }

    public function setApiSuccess($api_success) {
        $this->api_success = $api_success;
    }

    public function setApiItemCode($api_item_code) {
        $this->api_item_code = $api_item_code;
    }

    public function setApiItemSuccess($api_item_success) {
        $this->api_item_success = $api_item_success;
    }

    public function setApiItemMessage($api_item_message) {
        $this->api_item_message = $api_item_message;
    }

    public function setAddedOn($added_on) {
        $this->added_on = $added_on;
    }

    public function setAddedBy($added_by) {
        $this->added_by = $added_by;
    }

    public function save()
    {
        $db_um = new Dbase('users');
        
        if($this->id)
        {   
            throw new Exception("NOT IMPLEMENTED");
        }
        else 
        {
            if(isset($this->org_id))
                $columns['`org_id`'] = $this->org_id;
            if(isset($this->mapping_id))
                $columns['`mapping_id`'] = $this->mapping_id;
            if(isset($this->interaction_id))
                $columns['`interaction_id`'] = $this->interaction_id;
            if(isset($this->action_id))
                $columns['`action_id`'] = $this->action_id;
            if(isset($this->api_success))
                $columns['`api_success`'] = $this->api_success;
            if(isset($this->api_item_success))
                $columns['`api_item_success`'] = $this->api_item_success;
            if(isset($this->api_item_code))
                $columns['`api_item_code`'] = "'" .$this->api_item_code . "'";
            if(isset($this->api_item_message))
                $columns['`api_item_message`'] = "'" .$this->api_item_message . "'";
            if(isset($this->added_by))
                $columns['`added_by`'] = $this->added_by;
            
            $columns['added_on'] = "NOW()";
            
            $sql = "INSERT INTO  incoming_interaction_action_log ";
            $sql .= "\n (". implode(",", array_keys($columns)).") ";
            $sql .= "\n VALUES ";
            $sql .= "\n (". implode(",", $columns).") ;";
            
            $sql=substr($sql,0,-2);
            $db = new Dbase('users');
            $newId = $db->insert($sql);
            
            if(! $newId)
            {
                throw new Exception("Logging failed");
            }
        }
        return $newId;
    }

}