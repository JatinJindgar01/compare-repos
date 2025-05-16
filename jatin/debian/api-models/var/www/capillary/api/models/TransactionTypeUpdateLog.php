<?php

/**
 * Description of IncomingInteractionActionLog
 *
 * @author rohit
 */
include_once 'models/BaseModel.php';

class TransactionTypeUpdateLog extends BaseAPiModel{
    
    protected $id;
    protected $org_id;
    protected $till_id;
    protected $change_type;
    protected $user_id;
    protected $client_ip;
    protected $old_id;
    protected $new_id;
    protected $request_time;
    protected $client_signature;

    public function getId() {
        return $this->id;
    }

    public function getOrgId() {
        return $this->org_id;
    }

    public function getTillId() {
        return $this->till_id;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getClientIp() {
        return $this->client_ip;
    }

    public function getRequestTime() {
        return $this->request_time;
    }

    public function getClientSignature() {
        return $this->client_signature;
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

    public function setUserId($user_id) {
        $this->user_id = $user_id;
    }

    /**
     * @param mixed $change_type
     */
    public function setChangeType($change_type)
    {
        $this->change_type = $change_type;
    }

    /**
     * @return mixed
     */
    public function getChangeType()
    {
        return $this->change_type;
    }

    /**
     * @param mixed $new_id
     */
    public function setNewId($new_id)
    {
        $this->new_id = $new_id;
    }

    /**
     * @return mixed
     */
    public function getNewId()
    {
        return $this->new_id;
    }

    /**
     * @param mixed $old_id
     */
    public function setOldId($old_id)
    {
        $this->old_id = $old_id;
    }

    /**
     * @return mixed
     */
    public function getOldId()
    {
        return $this->old_id;
    }

    public function setClientIp($client_ip) {
        $this->client_ip = $client_ip;
    }

    public function setRequestTime($request_time) {
        $this->request_time = $request_time;
    }

    public function setClientSignature($client_signature) {
        $this->client_signature = $client_signature;
    }

    public function save()
    {
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
            if(isset($this->old_id))
                $columns['`old_id`'] = $this->old_id;
            if(isset($this->new_id))
                $columns['`new_id`'] = $this->new_id;
            if(isset($this->change_type))
                $columns['`change_type`'] = "'" . $this->change_type . "'";
            if(isset($this->client_signature))
                $columns['`client_signature`'] = "'" .$this->client_signature . "'";
            if(isset($this->redeemed_item))
                $columns['`redeemed_item`'] = $this->redeemed_item;
            if(isset($this->skip_validation))
                $columns['`skip_validation`'] = $this->skip_validation;
            $columns['`request_time`'] = "NOW()";
            
            $sql = "INSERT INTO  transaction_type_update_log ";
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