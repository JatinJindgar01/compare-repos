<?php

/**
 * controller class for Referrals endpoint
 * 
 * @author vimal
 *
 */

//TODO: referes to cheetah
require_once 'thrift/referral.php';


class ApiReferralController extends ApiBaseController {
	
	private $referral_obj;
	
	function __construct()
	{
		parent::__construct();
		$this->logger->info("inside ApiReferralController, initializing ReferralThriftClient");
		$this->referral_obj=new ReferralThriftClient();
		$this->logger->info("ReferralThriftClient instance created");		
	}
	
	function getStoreCode($store_id)
	{
		
	}
	
	/**
	 * Translates the exception occured to API recognizable error code
	 * 
	 * @param Exception $e
	 * @throws Exception
	 */
	private function translateException($e)
	{
		switch($e->getCode())
		{
			case 1001:
				$exe="ERR_REFERRAL_INVALID_CAMPAIGN_TOKEN";
				break;
			case 1002:
				$exe="ERR_REFERRAL_CAMPAIGN_NOT_IN_ORG";
				break;
			case 1003:
				$exe="ERR_REFERRAL_REFER_FAILED";
				break;
			case 1004:
				$exe="ERR_REFERRAL_INVALID_REFERRER";
				break;
			case 1005:
				$exe="ERR_REFERRAL_INVALID_TYPE";
				break;
			case 0:
			default:
				$exe="ERR_REFERRAL_SYS_ERROR";
		}
		$this->logger->info("Translated exception for code (".$e->getCode().") is $exe ");
		throw new Exception($exe);
	} 
	
	/**
	 * Invite the referees
	 * Transfer the payload of invitees to referral client 
	 * 
	 * 
	 * @param unknown $identifier_type
	 * @param unknown $identifier_value
	 * @param unknown $payload
	 * @param string $campaign_token
	 * @throws Exception
	 * @return multitype:multitype:NULL Ambigous <unknown, string>  multitype:multitype:NULL
	 */
	function invite($identifier_type, $identifier_value, $i_payload, $campaign_token="-1")
	{
		global $currentuser;
		
		$customer_inp[$identifier_type]=$identifier_value;
		
		$user=UserProfile::getByData($customer_inp);
		$status = $user->load(true);
		if(!$status || $user->user_id < 0)
			throw new Exception("ERR_USER_NOT_REGISTERED");
		
		$user_id=$user->user_id;
		
		$store_code=$currentuser->username;
		
		$tmp_payload=$i_payload;
		$payload=array();
		$err_payload=array();
		
		foreach($tmp_payload as $pl)
		{
			if(!in_array(strtoupper($pl['type']), $this->referral_obj->getReferralTypes()))
				throw new Exception('ERR_REFERRAL_INVALID_TYPE');
			
			$valid=true;
			$id=$pl['identifier'];
			if(strtolower($pl['type'])=="mobile")
				$valid=Util::checkMobileNumberNew($id);
			elseif(strtolower($pl['type'])=="email")
				$valid=Util::checkEmailAddress($id);
			
			if($valid)
				$payload[]=$pl;
			else
				$err_payload[]=array_merge($pl,array('success'=>false,'code'=>1701,'message'=>'Invalid Identifier for the type'));
			
		}
		
		$this->logger->debug("Err-ed payload : ".print_r($err_payload,true));
		
		$successful_invitees=array();
		if(!empty($payload))
		{
			try{
				$successful_invitees=$this->referral_obj->inviteReferrals($user_id, $campaign_token, $payload, $store_code);
			}catch(Exception $e)
			{
				$this->translateException($e);
			}
		}else
			$this->logger->info("Referral call is not made, as no valid payload available!");
		
		$customer=array(
				'firstname'=>$user->first_name,
				'lastname'=>$user->last_name,
				'mobile'=>$user->mobile,
				'email'=>$user->email,
				'external_id'=>$user->external_id,
				'id'=>$user->user_id
				);
		
		return array('customer'=>$customer, 'invitees'=>array_merge(
																$successful_invitees,$err_payload
															)
				);
	}
	
	/**
	 * Get statistical data for the referrals made
	 * 
	 * 
	 * @param unknown $identifier_type
	 * @param unknown $identifier_value
	 * @param string $campaign_token
	 * @param string $start_date
	 * @param string $end_date
	 * @param string $store_code
	 * @param string $get_all_stores
	 * @param string $get_only_referral_code
	 * @throws Exception
	 * @return multitype:unknown NULL Ambigous <string, unknown> Ambigous <multitype:multitype: , multitype:NULL >
	 */
	function getStats($identifier_type, $identifier_value, $campaign_token="-1", $start_date=null,$end_date=null, $store_code=null, $get_all_stores=false, $get_only_referral_code=false)
	{

		global $currentuser;
		
		if(!$store_code)
			$store_code=$currentuser->username;
		
		$customer_inp[$identifier_type]=$identifier_value;
		$user=UserProfile::getByData($customer_inp);
		$status = $user->load(true);
		if(!$status || $user->user_id < 0)
			throw new Exception("ERR_USER_NOT_REGISTERED");

		$user_id=$user->user_id;
		
		$ret['customer']=array(
				'id'=>$user_id,
				'email'=>$user->email,
				'mobile'=>$user->mobile,
				'external_id'=>$user->external_id,
				'firstname'=>$user->first_name,
				'lastname'=>$user->last_name
		);
		
		
		try{
		
			$referral_code=$this->referral_obj->getReferralCode($user_id, $campaign_token);
			
			if(!$get_only_referral_code)
				$stats=$this->referral_obj->getUserStatistics($user_id, $store_code, $campaign_token, $start_date, $end_date);
		
		}catch(Exception $e)
		{
			$this->translateException($e);
		}
		
		$ret['stats']=$stats;
		
		$ret['referral_code']=$referral_code;

		return $ret;
		
	}
	
}
