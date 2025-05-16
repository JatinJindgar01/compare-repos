<?php

require_once "apiModel/class.ApiRequestModel.php";

/**
 * Goodwill request model
 * 
 * @author vimal
 *
 */
class ApiGoodwillRequestModel extends ApiRequestModel
{
	
	protected $id;
	protected $base_type;
	protected $reason;
	protected $comments;
	
	protected $assoc_id;
	protected $approved_value;
	protected $updated_comments;
	protected $base_status;
	
	
	function __construct()
	{
		parent::__construct();
		$this->type='GOODWILL';
	}
	
	function getHash()
	{
		return array_merge(array(
					'id'=>$this->id,
					'base_type'=>$this->base_type,
					'approved_value'=>$this->approved_value,
					'updated_comments'=>$this->updated_comments,
					'assoc_id'=>$this->assoc_id,
					'reason'=>$this->reason,
					'comments'=>$this->comments,
					'base_status'=>$this->base_status,
				),parent::getHash());
	}
	
	function load($request_id)
	{
		parent::load($request_id);
		
		$sql="SELECT * FROM member_care.goodwill_requests WHERE org_id=$this->org_id AND request_id=$request_id";
		
		$row=$this->db->query_firstrow($sql);
		if(empty($row))
		{
			$this->logger->error('reading from goodwill_requests table failed');
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		}
		
		$this->base_type=$row['type'];
		$this->base_status=$row['status'];
		$this->reason=$row['reason'];
		$this->comments=$row['comments'];
		$this->assoc_id=$row['assoc_id'];
		$this->approved_value=$row['approved_value'];
		$this->updated_comments=$row['updated_comments'];
		
		return $this->getHash();
	}
	
	
	function setHash($hash)
	{
		
		if(isset($_REQUEST['one_step_change']) && strtolower($_REQUEST['one_step_change'])=="true")
		{
			$this->logger->info("its a one step change.. setting the property");
			$this->is_one_step_change=1;
		}
		
		foreach(array('user_id','base_type','reason','comments') as $hash_key)
			$this->$hash_key=$hash[$hash_key];
		
		if(isset($hash['requested_on']) && !empty($hash['requested_on']))
			$this->created_on=DateUtil::deserializeFrom8601($hash['requested_on'])?DateUtil::deserializeFrom8601($hash['requested_on']):null;
		$this->validate();
	
	}
	
	protected function validate()
	{
		
		$this->base_type=strtoupper($this->base_type);
		
		if(!in_array($this->base_type, array('COUPON','POINTS','TIER')))
			throw new Exception('ERR_INVALID_REQUEST_BASE_TYPE');
		
	}
	
	
	function insert()
	{
		
		$this->logger->info("inserting new goodwill request");
		
		parent::insert();
		
		$comments=addslashes($this->comments);
		$reason=addslashes($this->reason);
		
		$sql="INSERT INTO member_care.goodwill_requests(request_id,org_id,type,status,reason,comments,program_id)
				VALUES($this->request_id,$this->org_id,'$this->base_type','PENDING','$reason','$comments','$program_id')";
		
		$ret=$this->db->insert($sql);
		if(!$ret)
		{
			$this->logger->error("insert into goodwill_requests failed");
			throw new Exception('sql failed');
		}
		
		return $ret;
		
	}
	
	function update($status,$updated_comments,$assoc_id,$approved_value)
	{
		
		$this->status=$status;
		$this->updated_comments=addslashes($updated_comments);
		$this->assoc_id=$assoc_id;
		$this->approved_value=$approved_value;
		
		parent::update();
		
		$sql="UPDATE member_care.goodwill_requests SET status='$status',updated_comments='" . $this -> updated_comments . "',
				approved_value='$this->approved_value',
				assoc_id='$this->assoc_id'
				WHERE request_id='$this->request_id'";
		
		$ret=$this->db->update($sql);
		if(!$ret)
		{
			$this->logger->error('update goodwill_requests failed');
			throw new Exception('sql failed');
		}
		
		return true;
		
	}
	
	protected function isBaseTypeValid($base_type)
	{
		if(!in_array(strtoupper($base_type),
				array('POINTS','COUPON')
		))
			throw new Exception('ERR_INVALID_REQUEST_BASE_TYPE');
	}
	
}
