<?php
include_once 'common.php';
class OrganizationThriftClient extends BaseThriftClient{

	function __construct()
	{
		parent::__construct();
		
		$this->include_file('organization-service/OrganizationService.php');
		$this->include_file('organization-service/organization-service_types.php');
		$recvtimeout = 50000;
		try {

			$this->logger->debug( "Organization Service - Getting organization thrift client
					with timeout $recvtimeout " );
			$this->get_client('organization', $recvtimeout);
		} catch (Exception $e) {
			$this->logger->error("Exception caught while trying to connect");
		}
	}
	
	public function getAllOrgs( ){
		
		$this->logger->debug( "organization Service Client: ".
				"Calling getAllOrgs " );
		try{
			
			$this->transport->open();
			$result = $this->client->getAllOrgs();
			$this->logger->debug( "organization Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
			
			$this->logger->error("Exception in calling organization service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function getOrgInfo( $org_id ){
	
		$this->logger->debug( "organization Service Client: ".
				"Calling getOrgInfo with params - OrgId : $org_id" );
		try{
				
			$this->transport->open();
			$result = $this->client->getOrgInfo( $org_id );
			$this->logger->debug( "organization Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
				
			$this->logger->error("Exception in calling organization service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function getOrgInfoList( $org_id_list ){
	
		$this->logger->debug( "organization Service Client: ".
				"Calling getOrgInfoList with params - OrgIds: $org_id_list" );
		try{
	
			$this->transport->open();
			$result = $this->client->getOrgInfoList( $org_id_list );
			$this->logger->debug( "organization Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling organization service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function getConfigKeyValue( $org_id, $name, $entity_id, $scope ){
	
		$this->logger->debug( "organization Service Client: ".
				"Calling getConfigKeyValue with params - OrgId: $org_id, Name: $name, EntityId: $entity_id, Scope: $scope" );
		try{
	
			$this->transport->open();
			$result = $this->client->getConfigKeyValue( $org_id, $name, $entity_id, $scope );
			$this->logger->debug( "organization Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling organization service: $e");
			$this->transport->close();
			return false;
		}
	}

    public function getEntities( ){

        $this->logger->debug( "organization Service Client: ".
            "Calling getEntities " );
        try{

            $this->transport->open();
            $result = $this->client->getEntities( "72", "STORE" );
            $this->logger->debug( "organization Service Client: Returning back with data - ".print_r( $result, true ) );
            $this->transport->close();
            return $result;
        }catch( Exception $e ){

            $this->logger->error("Exception in calling organization service: $e");
            $this->transport->close();
            return false;
        }
    }
}
?>