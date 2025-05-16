<?php

/**
 * All resources in the system should be extending this abstract class
 * 
 * @author pigol
 */

abstract class BaseResource{
	
	protected $logger;
	protected $currentorg;
	protected $currentuser;
	
	function __construct(){
		global $logger, $currentorg, $currentuser; 
		
		$this->logger = $logger;  //we may instantiate a different logger for the apis
		$this->currentorg = $currentorg;
		$this->currentuser = $currentuser;
	}
	
	/**
	 * The entry points to any resource
	 * 
	 * @param $version : Resource version
	 * @param $method : Method
	 * @param $data : The POST/PUT data
	 * @param $query_params : Query parameters
	 * @param $http_method : http method (post/put/get)
	 * 
	 * @author pigol
	 * 
	 */
	
	public abstract function process($version, $method, $data, $query_params, $http_method);

	
	/*
	 * Checks if the resource supports the version supplied or not
	 */
	protected abstract function checkVersion($version);
	
	/*
	 * Checks if the requested method is supported the resource 
	 * in the supplied version or not. We may deprecate some methods
	 * when we upgrade the api versions.
	 */
	protected abstract function checkMethod($method);

	/**
	 *Check if user is fraud or not
	 */	
	public static function isFraudUser( $user_id ){
                      
		global $currentorg;                                                                                                
                $org_id = $currentorg->org_id;
                
                $db = new Dbase('users');
                              
                $sql = "                                                                                              
                        SELECT id                                                                                     
                        FROM user_management.fraud_users                                                              
                        WHERE `org_id` = '$org_id' AND `user_id` = '$user_id'                                         
                                AND `status` IN ( 'CONFIRMED','RECONFIRMED', 'INTERNAL' )                             
                ";                                                                                                    
                                                                                                                      
                return $db->query_scalar( $sql );                                                               
        }             
	
	/**
	 * Retrieves the user object from the mobile, email, external_id
	 * Shouldn't this be in Util ??
	 * 
	 * @param $mobile
	 * @param $email
	 * @param $external_id
	 * @throws UserNotFoundException
	 */
	
	protected function getUser($mobile, $email, $external_id, $load_profile = true, $skip_fraud_check = false,$customerId=-1)
	{
        $customerController = new ApiCustomerController();
        $ids = array('mobile' => $mobile, 'email' => $email, 'external_id' => $external_id,'id' => $customerId);
        $identifier = $this->getCustomerIdentifierType($ids);
        
        try {
            $user = $customerController->getCustomers(array($identifier => $ids[$identifier]), $load_profile);
        }
        catch(Exception $e)
        {
            throw new UserNotFoundException( 'ERR_USER_NOT_FOUND' );
        }
		//user not found
        if( $user && !($skip_fraud_check)){
            if( self::isFraudUser( $user->user_id ) ){

                throw new Exception( 'ERR_LOYALTY_FRAUD_USER' );
            }
        }

		if(!$user){
			$this->logger->debug("User not found for $mobile, $email, $external_id,$customerId");
			throw new UserNotFoundException( 'ERR_USER_NOT_FOUND' );
		}
		
		$this->logger->debug("user found with id: " . $user->user_id);
		return $user;

	}

	//@TODO :need to be finally done from UserProfile.php
	protected function getNonLoyaltyUser($mobile, $email, $external_id , $include_fraud= false,$id=-1)
	{
        $ids = array('mobile' => $mobile, 'email' => $email, 'external_id' => $external_id,'id' =>$id);
        $identifier = $this->getCustomerIdentifierType($ids);

        $user = null;
		switch ($identifier)
		{
			case 'mobile':
				$user = UserProfile::getByMobile($mobile);
				break;
			case 'external_id':
				$user = UserProfile::getByExternalId($external_id);
				break;
			case 'email':
				//ADD check for mobile duplicate
				$user = UserProfile::getByEmail($email);
				break;
			case 'id':
                $user = UserProfile::getById($id);
				break;
        }
		
		if(!$user)
        {
            throw new UserNotFoundException( 'ERR_USER_NOT_FOUND' );
        }
        
		//user not found
        if( $user && !$include_fraud){
            if( self::isFraudUser( $user->user_id ) ){

                throw new Exception( 'ERR_LOYALTY_FRAUD_USER' );
            }
        }

		$this->logger->debug("user found with id: " . $user->user_id);
		return $user;

	}
	
	/**
	 * Enter description here ...
	 * @param array $customer
	 * @throws Exception
	 * @return get the type of the customer identfier if multiple are passed
	 */
	protected function getCustomerIdentifierType(array $customer = array())
	{
		if(($customer['id']) && ($customer['id'] != -1))
			return 'id';
		
		$cm = new ConfigManager();
		$primary_identifier = $cm->getKey('CONF_REGISTRATION_PRIMARY_KEY');
		
		switch ($primary_identifier)
		{
			case REGISTRATION_IDENTIFIER_MOBILE:
				$primary_identifier = "mobile"; break;
			case REGISTRATION_IDENTIFIER_EMAIL:
				$primary_identifier = "email"; break;
			case REGISTRATION_IDENTIFIER_EXTERNAL_ID:
				$primary_identifier = "external_id"; break;
			default:$primary_identifier = "mobile"; break;
		}
		
		if($customer[$primary_identifier])
			return $primary_identifier;
		
		if(($customer[ 'mobile' ] ) )
			$identifier = 'mobile';
		elseif(($customer[ 'external_id' ]) )
			$identifier = 'external_id';
		elseif(($customer[ 'email' ]) )
			$identifier = 'email';
		else
		{
			$error_key = 'ERR_NO_IDENTIFIER';
			throw new Exception( ErrorMessage::$api['INVALID_INPUT'] .", ". ErrorMessage::$customer['ERR_NO_IDENTIFIER'], ErrorCodes::$api['INVALID_INPUT']);
		}
		
		return $identifier;
		
	}
	
}

?>
