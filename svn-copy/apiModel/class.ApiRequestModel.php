<?php

/**
 * Requests model
 * 
 * 
 * @author vimal
 *
 */
abstract class ApiRequestModel
{
	
	protected $db;
	
	protected $currentorg;
	protected $currentuser;
	protected $logger;
	protected $org_id;
	
	protected $request_id;
	protected $status;
	protected $user_id;
	protected $created_on;
	protected $created_by;
	protected $updated_by;
	protected $updated_on;
	protected $is_one_step_change=0;
	
	protected $type;
	protected $params;
	protected $user_hash;
	
	abstract function setHash($hash);
	abstract protected function validate();
	abstract protected function isBaseTypeValid($base_type);
	
	function __construct()
	{
		global $currentorg,$currentuser,$logger;
		$this->db=new Dbase('member_care');
		
		$this->currentorg=$currentorg;
		$this->currentuser=$currentuser;
		$this->logger=$logger;
		$this->org_id=$currentorg->org_id;
	}

	
	
	public function beginTransaction()
	{
		$this->db->update("SET autocommit = 0;");
		$this->db->update("START TRANSACTION;");
	}
	
	public function commitTransaction()
	{
		$this->db->update("COMMIT");
		$this->db->update("SET autocommit = 1;");
	}
	
	public function rollbackTransaction()
	{
		$this->db = new Dbase('users');
		$this->db->update("ROLLBACK");
		$this->db->update("SET autocommit = 1;");
	}
	
	function getHash()
	{
		return array(
					'request_id'=>$this->request_id,
					'type'=>$this->type,
					'status'=>$this->status,
					'user_id'=>$this->user_id,
					'updated_by'=>$this->updated_by,
					'updated_on'=>$this->updated_on,
					'created_on'=>$this->created_on,
					'created_by'=>$this->created_by,
					'params'=>$this->params
				);
	}
	
	function load($id)
	{
		$this->logger->info("loading requests table row for $id");
		$sql="SELECT * FROM member_care.requests WHERE id='$id' AND org_id='$this->org_id'";
		$row=$this->db->query_firstrow($sql);
		if(empty($row))
		{
			$this->logger->error('reading from requests table failed');
			throw new Exception('request not found');
		}
		
		$this->request_id=$row['id'];
		$this->created_by=$row['created_by'];
		$this->created_on=$row['created_on'];
		$this->params=$row['params'];
		$this->status=$row['status'];
		$this->type=$row['type'];
		$this->user_id=$row['user_id'];
		$this->updated_by=$row['updated_by'];
		$this->updated_on=$row['updated_on'];
		
		return true;
	}
	
	function update()
	{
		$status=$this->status;
		
		$this->logger->info("Changing status of request_id $this->request_id to '$status'");
		$sql="UPDATE member_care.requests SET status='$status', updated_by='{$this->currentuser->user_id}', updated_on=NOW()
				 WHERE id=$this->request_id";
		$ret=$this->db->update($sql);
		if(!$ret)
		{
			$this->logger->error("Status update failed");
			throw new Exception('sql failed');
		}
		return true;
	}
	
	function insert($user_id=false)
	{
		if(!$user_id)
			$user_id=$this->user_id;
		
		$this->logger->info("creating a new request for $user_id");

		$params=addslashes($this->params);
		
		$created_on=$this->created_on?DateUtil::getMysqlDateTime($this->created_on):DateUtil::getMysqlDateTime(false);
		
		try{
			
			include_once 'registrar-php-sdk/RegistrarService.php';
			$registrar = new RegistrarSdk\RegistrarService();
			
			$shardedRequest = new RegistrarSdk\models\MemberCareRequest();
			$shardedRequest->setOrgId($this->org_id);
			$shardedRequest->setIsActive(true);
			$shardedRequest->setAddedOn($created_on);
			$shardedRequest->setAddedBy($this->currentuser->user_id);
			
			$shardedRequestResp=$registrar->getMemberCareRequestIds($this->org_id, array($shardedRequest));
			
		}catch(Exception $e)
		{
			$this->logger->debug("Exception from registrar".$e);
		}
		
		if(!isset($shardedRequestResp[0]) || !method_exists($shardedRequestResp[0], "getId"))
		{
			$this->logger->error("unable to create request id from registrar");
			throw new Exception('registrar failed');
		}
		
		$this->request_id=$shardedRequestResp[0]->getId();
		
		$this->logger->debug("Request id from registrar: $this->request_id");
		
		
		
		$sql="INSERT INTO member_care.requests(id,status,user_id,org_id,created_by,created_on,type,is_one_step_change,params)
				VALUES($this->request_id,'PENDING',$user_id,$this->org_id,{$this->currentuser->user_id},'$created_on','$this->type','$this->is_one_step_change','$params')";
		$ret=$this->db->update($sql);
		if(!$ret)
		{
			$this->logger->error("Insert new request failed");
			throw new Exception('sql failed');
		}
		return $this->request_id;
	}
	
	function get($id=false)
	{
		if(!$id)
			$id=$this->request_id;
		return $this->db->query_firstrow("SELECT * FROM member_care.requests WHERE id='$this->request_id' AND org_id='$this->org_id'");
	}
	
	function isExists($id,$status=null)
	{
		$sql="SELECT count(1) FROM member_care.requests
		WHERE id='$id'";
		if($status)
			$sql=$sql." AND status='$status'";
		
		return $this->db->query_scalar($sql)==1?true:false;
	}
	
	protected function deTemplatize($template,$payload)
	{
		
		$detemp=$template;
		foreach($payload as $key=>$value)
			$detemp=str_ireplace('{{'.$key.'}}', $value, $detemp);
		
		$this->logger->debug("Detemplatize payload : ".print_r($payload,true)."\n\n\n Detemplatize before : $template \n\n\n  Detemplatize after : $detemp");
		
		return $detemp;
		
	}
	
	protected function getSettingsKeyValue($key,$optional=false)
	{
		try{
			$v=$this->settings->getValue($key);
		}catch(Exception $e)
		{
			$v="";
			if(!$optional)
				throw $e;
		}
		return $v;
	}
	
	
	
}