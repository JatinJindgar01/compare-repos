<?php

/**
 * Description of IncomingInteractionActionLog
 *
 * @author rohit
 */
include_once 'models/BaseModel.php';

class RedemptionRequestLog extends BaseAPiModel{
    
    protected $id;
    protected $org_id;
    protected $till_id;
    protected $mapping_id;
    protected $user_id;
    protected $client_ip;
    protected $request_scope;
    protected $request_type;
    protected $request_time;
    protected $client_signature;
    protected $redeemed_item;
    protected $skip_validation;
    
    public function getId() {
        return $this->id;
    }

    public function getOrgId() {
        return $this->org_id;
    }

    public function getTillId() {
        return $this->till_id;
    }

    public function getMappingId() {
        return $this->mapping_id;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getClientIp() {
        return $this->client_ip;
    }

    public function getRequestScope() {
        return $this->request_scope;
    }

    public function getRequestType() {
        return $this->request_type;
    }

    public function getRequestTime() {
        return $this->request_time;
    }

    public function getClientSignature() {
        return $this->client_signature;
    }

    public function getRedeemedItem() {
        return $this->redeemed_item;
    }
    
    public function getSkipValidation()
    {
        return $this->skip_validation;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setOrgId($org_id) {
        $this->org_id = $org_id;
    }

    public function setTillId($till_id) {
        $this->till_id = $till_id;
    }

    public function setMappingId($mapping_id) {
        $this->mapping_id = $mapping_id;
    }

    public function setUserId($user_id) {
        $this->user_id = $user_id;
    }

    public function setClientIp($client_ip) {
        $this->client_ip = $client_ip;
    }

    public function setRequestScope($request_scope) {
        $this->request_scope = $request_scope;
    }

    public function setRequestType($request_type) {
        $this->request_type = $request_type;
    }

    public function setRequestTime($request_time) {
        $this->request_time = $request_time;
    }

    public function setClientSignature($client_signature) {
        $this->client_signature = $client_signature;
    }

    public function setRedeemedItem($redeemed_item) {
        $this->redeemed_item = $redeemed_item;
    }

    public function setSkipValidation($skip_validation)
    {
        $this->skip_validation = $skip_validation;
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
            if(isset($this->till_id))
                $columns['`till_id`'] = $this->till_id;
            if(isset($this->user_id))
                $columns['`user_id`'] = $this->user_id;
            if(isset($this->client_ip))
                $columns['`client_ip`'] = "INET_ATON('" . $this->client_ip . "')";
            if(isset($this->request_scope))
                $columns['`request_scope`'] = "'" . $this->request_scope . "'";
            if(isset($this->request_type))
                $columns['`request_type`'] = "'" . $this->request_type . "'";
            if(isset($this->client_signature))
                $columns['`client_signature`'] = "'" .$this->client_signature . "'";
            if(isset($this->redeemed_item))
                $columns['`redeemed_item`'] = $this->redeemed_item;
            if(isset($this->skip_validation))
                $columns['`skip_validation`'] = $this->skip_validation;
            $columns['`request_time`'] = "NOW()";
            
            $sql = "INSERT INTO  redemption_request_log ";
            $sql .= "\n (". implode(",", array_keys($columns)).") ";
            $sql .= "\n VALUES ";
            $sql .= "\n (". implode(",", $columns).") ;";
            
            $sql=substr($sql,0,-2);
            $db = new Dbase('users');
            $newId = $db->insert($sql);
            
            if(! $newId)
            {
                throw new Exception("Insert failed");
            }
        }
        return $newId;
    }

}