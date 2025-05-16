<?php

require_once 'apiModel/class.ApiRequestModel.php';
require_once 'cheetah/thrift/pointsengineservice.php';

/**
 * Change identifier model for requests
 * 
 * @author vimal
 *
 */
class ApiChangeIdentifierRequestModel extends ApiRequestModel
{
	
	protected $cir_id;
	protected $old_value;
	protected $new_value;
	protected $base_type;
	protected $updated_comments;
	protected $entity;
	protected $sec_user_id;
	
	function __construct()
	{
		parent::__construct();
		$this->type='CHANGE_IDENTIFIER';
	}
	
	function getHash()
	{
		return array_merge(array(
				'old_value'=>$this->old_value,
				'new_value'=>$this->new_value,
				'base_type'=>$this->base_type,
				'updated_comments'=>$this->updated_comments,
		),parent::getHash());
	}
	
	function load($request_id)
	{
		
		parent::load($request_id);
		
		$sql="SELECT * FROM member_care.change_identifier_requests 
				WHERE org_id=$this->org_id AND request_id=$request_id";
		
		$row=$this->db->query_firstrow($sql);
		if(empty($row))
		{
			$this->logger->error('reading from change_identifier_requests table failed');
			throw new Exception('ERR_REQUEST_NOT_FOUND');
		}
		
		$this->old_value=$row['old_value'];
		$this->new_value=$row['new_value'];
		$this->base_type=$row['type'];
		$this->updated_comments=$row['updated_comments'];
		$this->sec_user_id=$row['sec_user_id'];
		$this->entity=$row['entity'];
		
		return $this->getHash();
	}
	
	function setHash($hash)
	{
		
		if(isset($_REQUEST['one_step_change']) && strtolower($_REQUEST['one_step_change'])=="true")
		{
			$this->logger->info("its a one step change.. setting the property");
			$this->is_one_step_change=1;
		}
		
		foreach(array('user_id','base_type','old_value','new_value') as $hash_key)
			$$hash_key=$hash[$hash_key];
		
		$this->user_id=$user_id;
		$this->base_type=$base_type;
		$this->old_value=$old_value;
		$this->new_value=$new_value;
		
		if(isset($hash['requested_on']) && !empty($hash['requested_on']))
			$this->created_on=DateUtil::deserializeFrom8601($hash['requested_on'])?DateUtil::deserializeFrom8601($hash['requested_on']):null;
		
		$this->validate(true);
		
	}

	
	protected function validate($pending=false)
	{
		$this->logger->info("Validating the new value");
		
		if(empty($this->new_value) && $this->base_type!='MOBILE_REALLOC')
			throw new Exception('ERR_CIR_INVALID_NEW_VALUE');
			
		if($this->new_value==$this->old_value && $this->base_type=='MERGE')
			throw new Exception('ERR_CIR_VICTIM_SURVIVOR_SAME');
		else if($this->new_value==$this->old_value)
			throw new Exception('ERR_CIR_OLD_NEW_VALUE_SAME');

		$this->user_hash = UserProfile::getByData(array('id'=>$this->user_id));
		
		switch($this->base_type)
		{
			case 'EMAIL':
				$this->user_hash->load(true);
				if(!Util::checkEmailAddress($this->new_value))
					throw new Exception( 'ERR_CIR_INVALID_EMAIL' );
	
				if(!$this->user_hash || $this->user_hash->email!=$this->old_value)
					throw new Exception('ERR_CIR_INVALID_OLD_VALUE');
				
				$user_by_email = UserProfile::getByEmail($this->new_value);
                                if( !empty( $user_by_email ) && $user_by_email->is_merged && $user_by_email->user_id == $this->user_id)
                                        $this->logger->info("Change request is raised to fetch identifier from the merge victim");
                                else if(!empty( $user_by_email ) && $user_by_email->getLoyaltyId() == -1)
                                        $this->logger->info("Change request is raised to fetch identifier from campaign user");
				else if( !empty( $user_by_email ) )
					throw new Exception( 'ERR_CIR_DUPLICATE_EMAIL' );
				if($this->old_value == $this->new_value)
					throw new Exception( 'ERR_CIR_DUPLICATE_EMAIL' );
	
				break;
	
			case 'EXTERNAL_ID':
				$this->user_hash->load(true);
				if(!$this->user_hash || $this->user_hash->external_id!=$this->old_value)
					throw new Exception('ERR_CIR_INVALID_OLD_VALUE');
				
				$user=UserProfile::getByExternalId($this->new_value);
				if( !empty( $user ) && $user->is_merged && $user->user_id == $this->user_id)
                                        $this->logger->info("Change request is raised to fetch identifier from the merge victim");
				
				else if($user)
					throw new Exception('ERR_CIR_DUPLICATE_EXTERNAL_ID');
	
				break;
	
			case 'MOBILE':
				$this->user_hash->load(true);
				if(!Util::checkMobileNumber($this->new_value))
					throw new Exception('ERR_CIR_LOYALTY_INVALID_MOBILE');
				
				Util::checkMobileNumber($this->old_value);
				
				if(!$this->user_hash || $this->user_hash->mobile!=$this->old_value)
					throw new Exception('ERR_CIR_INVALID_OLD_VALUE');
				
				$user_by_mobile = UserProfile::getByMobile($this->new_value);
                                if( !empty( $user_by_mobile ) && $user_by_mobile->is_merged && $user_by_mobile->user_id == $this->user_id)
                                        $this->logger->info("Change request is raised to fetch identifier from the merge victim");
                                else if(!empty( $user_by_mobile ) && $user_by_mobile->getLoyaltyId() == -1)
                                        $this->logger->info("Change request is raised to fetch identifier from campaign user");
				else if( !empty( $user_by_mobile ) )
					throw new Exception('ERR_CIR_DUPLICATE_MOBILE');
				
				$alloc_request=$this->db->query_firstrow("SELECT 1 FROM member_care.change_identifier_requests ci
						JOIN member_care.requests r
						ON r.org_id='$this->org_id' AND r.status='PENDING' AND r.id=ci.request_id
						WHERE ci.old_value='$this->user_id' AND ci.type='MOBILE_REALLOC' AND ci.org_id='$this->org_id'
						");
				if(!empty($alloc_request))
					throw new Exception('ERR_REQUEST_REALLOC_EXISTS');
				
				break;
				
			case 'MERGE':
				
				$this->user_hash->load(true);
				$merge_exists=$this->db->query_firstrow("SELECT 1 FROM member_care.change_identifier_requests ci JOIN member_care.requests r ON r.id=ci.request_id 
															WHERE (ci.old_value='$this->old_value' OR ci.new_value='$this->old_value') AND ci.type='MERGE' AND 
															(r.status='PENDING' OR ci.status='PROCESSING')
															AND r.id!='{$this->request_id}'");
				if(!empty($merge_exists))
					throw new Exception('ERR_REQUEST_MERGE_EXISTS_VICTIM');
				
				$merge_exists=$this->db->query_firstrow("SELECT 1 FROM member_care.change_identifier_requests ci JOIN member_care.requests r ON r.id=ci.request_id 
															WHERE (ci.old_value='$this->new_value' OR ci.new_value='$this->new_value') AND ci.type='MERGE' AND  
															(r.status='PENDING' OR ci.status='PROCESSING')
															AND r.id!='{$this->request_id}'");
				if(!empty($merge_exists))
					throw new Exception('ERR_REQUEST_MERGE_EXISTS_SURVIVOR');
				
				$merge_exists=$this->db->query_firstrow("SELECT 1 FROM member_care.merge_requests WHERE victim_user_id='$this->new_value'");
				if(!empty($merge_exists))
					throw new Exception('ERR_REQUEST_SURVIVOR_MERGED');
				
				$merge_exists=$this->db->query_firstrow("SELECT 1 FROM member_care.merge_requests WHERE victim_user_id='$this->old_value'");
				if(!empty($merge_exists))
					throw new Exception('ERR_REQUEST_VICTIM_MERGED');
				
				$alloc_request=$this->db->query_firstrow("SELECT 1 FROM member_care.change_identifier_requests ci 
															JOIN member_care.requests r 
																ON r.org_id='$this->org_id' AND r.status='PENDING' AND r.id=ci.request_id
															WHERE (ci.old_value='$this->old_value' OR ci.old_value='$this->new_value') AND ci.type='MOBILE_REALLOC' AND ci.org_id='$this->org_id'
															");
				if(!empty($alloc_request))
					throw new Exception('ERR_REQUEST_REALLOC_EXISTS');

				break;
				
				
			case 'MOBILE_REALLOC':
				
				if($pending)
				{
					$this->user_hash->load(true);
					Util::checkMobileNumber($this->old_value);
					if(!$this->user_hash || $this->user_hash->mobile!=$this->old_value)
						throw new Exception('ERR_CIR_INVALID_OLD_VALUE');
				}
				
				$alloc_request=$this->db->query_firstrow("SELECT 1 FROM member_care.change_identifier_requests ci 
															JOIN member_care.requests r 
																ON r.org_id='$this->org_id' AND r.status='PENDING' AND r.id=ci.request_id
															WHERE ci.old_value='$this->user_id' AND ci.type='MOBILE_REALLOC' AND ci.org_id='$this->org_id'
															");
				if(!empty($alloc_request))
					throw new Exception('ERR_REQUEST_REALLOC_EXISTS');
				
				break;
				
			default:
				throw new Exception('ERR_INVALID_REQUEST_BASE_TYPE');
	
		}
		
		
		if($this->base_type!='MERGE')
		{
			$sql="SELECT 1 FROM member_care.change_identifier_requests cr
					JOIN member_care.requests r ON r.id=cr.request_id 
					WHERE r.org_id='$this->org_id' AND cr.new_value='$this->new_value' 
						AND cr.request_id!='$this->request_id'
						AND cr.org_id='$this->org_id' AND r.status='PENDING'
						AND cr.type='$this->base_type'
						";	
			$exists=$this->db->query_firstrow($sql);
			if(!empty($exists))
				throw new Exception('ERR_REQUEST_NEW_VALUE_DUPLICATE');
		}
		
		
	}
	

	function insert()
	{
		
		$this->logger->info("inserting new change identifier request. payload: ".implode(" ," ,func_get_args()));
		
		$request_id=parent::insert();
		
		$old_value=addslashes($this->old_value);
		$new_value=addslashes($this->new_value);
		
		$entity=$this->entity?$this->entity:"";
		$sec_user_id=$this->sec_user_id?$this->sec_user_id:"";
	
		$sql="INSERT INTO member_care.change_identifier_requests(request_id,org_id,old_value,new_value,type,sec_user_id,entity)
				VALUES($this->request_id,$this->org_id,'$old_value','$new_value','$this->base_type','$sec_user_id','$entity')";
	
		$ret=$this->db->insert($sql);
		if(!$ret)
		{
			$this->logger->error("insert into change_identifier_requests failed");
			throw new Exception('sql failed');
		}
		
		$this->cir_id=$ret;
	
		return $request_id;
	
	}
	
	function update($status, $updated_comments="")
	{
	
		$this->status=$status;
		$this->updated_comments=addslashes($updated_comments);
	
		parent::update();
		
		$sql="UPDATE member_care.change_identifier_requests SET updated_comments='{$this->updated_comments}'
				WHERE request_id='$this->request_id'";
		
		$ret=$this->db->update($sql);
		if(!$ret)
		{
			$this->logger->error('update change_identifier_requests failed');
			throw new Exception('sql failed');
		}
		
		return true;
	
	}
	
	protected function isBaseTypeValid($base_type)
	{
		if(!in_array(strtoupper($base_type),
				array('MOBILE','EMAIL','EXTERNAL_ID','MERGE','MOBILE_REALLOC')
		))
			throw new Exception('ERR_INVALID_REQUEST_BASE_TYPE');
	}
	
	
}
