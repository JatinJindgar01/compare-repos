<?php
include_once 'common.php';
class AuthenticationThriftClient extends BaseThriftClient{

	function __construct()
	{
		parent::__construct();
		
		$this->include_file('conquest_service/conquest_service_types.php');
		$this->include_file('conquest_service/AuthenticationService.php');
		$recvtimeout = 50000;
		try {
			
			$this->logger->debug( "Authentication Service - Getting authentication thrift client
					with timeout $recvtimeout " );
			$this->get_client('authenticationservice', $recvtimeout);
		} catch (Exception $e) {
			$this->logger->error("Exception caught while trying to connect");
		}
	}
	
	public function authenticateUser( $username, $password, $scope ){
		
		$this->logger->debug( "Authentication Service Client: ".
				"Calling authenticateUser with params - username: $username, ".
				"password: $password, scope: $scope" );
		try{
			
			$this->transport->open();
			$result = $this->client->authenticateUser( $username, $password, $scope );
			$this->logger->debug( "Authentication Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
			
			$this->logger->error("Exception in calling authentication service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function authenticateUserWithCookie( $cookie, $scope ){
	
		$this->logger->debug( "Authentication Service Client: ".
				"Calling authenticateUserWithCookie with params - cookie: $cookie, scope: $scope" );
		try{
				
			$this->transport->open();
			$result = $this->client->authenticateUserWithCookie( $cookie, $scope  );
			$this->logger->debug( "Authentication Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
				
			$this->logger->error("Exception in calling authentication service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function getLoggableUsersForOrg( $org_id, $admin_users ){
	
		$this->logger->debug( "Authentication Service Client: ".
				"Calling getLoggableUsersForOrg with params - OrgId: $org_id, AdminUsers - $admin_users" );
		try{
	
			$this->transport->open();
			$result = $this->client->getLoggableUsersForOrg( $org_id, $admin_users );
			$this->logger->debug( "Authentication Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling authentication service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function getCapUsersForOrg( $org_id ){
	
		$this->logger->debug( "Authentication Service Client: ".
				"Calling getCapUsersForOrg with params - OrgId: $org_id" );
		try{
	
			$this->transport->open();
			$result = $this->client->getCapUsersForOrg( $org_id );
			$this->logger->debug( "Authentication Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling authentication service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function getUsersInfo( $user_ids ){
	
		$this->logger->debug( "Authentication Service Client: ".
				"Calling getUsersInfo with params - UserIds: ".print_r( $user_ids, true ) );
		try{
	
			$this->transport->open();
			$result = $this->client->getUsersInfo( $user_ids );
			$this->logger->debug( "Authentication Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling authentication service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function getRoleMapForUsers( $user_ids, $org_id ){
	
		$this->logger->debug( "Authentication Service Client: ".
				"Calling getRoleMapForUsers with params - OrgId: $org_id, UserIds: ".print_r( $user_ids, true ) );
		try{
	
			$this->transport->open();
			$result = $this->client->etRoleMapForUsers( $user_ids, org_id );
			$this->logger->debug( "Authentication Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling authentication service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function changePassword( $userId, $oldPasswordHash, $newPasswordHash ){
	
		$this->logger->debug( "Authentication Service Client: ".
				"Calling changePassword with params - userId: $userId, 
				oldPasswordHash: $oldPasswordHash, newPasswordHash: $newPasswordHash " );
		try{
	
			$this->transport->open();
			$result = $this->client->changePassword( $userId, $oldPasswordHash, $newPasswordHash );
			$this->logger->debug( "Authentication Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling authentication service: $e");
			$this->transport->close();
			return false;
		}
	}
}

$test = new AuthenticationThriftClient();
?>