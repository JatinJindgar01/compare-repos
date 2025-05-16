<?php
include_once 'common.php';
class ShopbookThriftClient extends BaseThriftClient{

	function __construct()
	{
		parent::__construct();
		
		$this->include_file('shopbook/ShopbookService.php');
		$this->include_file('shopbook/shopbook_types.php');
		$recvtimeout = 50000;
		try {

			$this->logger->debug( "shopbook Service - Getting shopbook thrift client
					with timeout $recvtimeout " );
			$this->get_client('shopbook', $recvtimeout);
		} catch (Exception $e) {
			$this->logger->error("Exception caught while trying to connect");
		}
	}
	
	public function remindScheduledTask( $task_id, $arguments ){
		
		$this->logger->debug( "shopbook Service Client: ".
				"Calling remindScheduledTask with params - TaskId : $task_id, Arguments: $arguments" );
		try{
			
			$this->transport->open();
			$result = $this->client->remindScheduledTask( $task_id, $arguments );
			$this->logger->debug( "shopbook Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
			
			$this->logger->error("Exception in calling shopbook service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function issueVoucher( $voucher_series_id, $user_id, $org_id, 
			$current_id, $amount=0, $max_redemptions=0, $transaction_number = '' ){
	
		$this->logger->debug( "shopbook Service Client: ".
				"Calling issueVoucher with params -  Coupon Series: $voucher_series_id, ".
				 									"UserId: $user_id, ".
													"OrgId: $org_id, ".
													"CurrentId: $current_id, ".
													"Amount: $amount, ".
													"Max redemptions: $max_redemptions, ".
													"Transaction number: $transaction_number " );
		try{
				
			$this->transport->open();
			$result = $this->client->issueVoucher( 
					$voucher_series_id, 
					$user_id, 
					$org_id, 
					$current_id, 
					$amount, 
					$max_redemptions, 
					$transaction_number );
			$this->logger->debug( "shopbook Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
				
			$this->logger->error("Exception in calling shopbook service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function issueMultipleVouchersNew( $voucher_series_id, $user_ids, $org_id, $current_id, $group_id ){
	
		$this->logger->debug( "shopbook Service Client: ".
				"Calling issueMultipleVouchersNew with params - Coupon series: $voucher_series_id, ".
																"UserId: $user_ids, ".
																"OrgId: $org_id, ".
																"CurrentId: $current_id, ".
																"GroupId: $group_id" );
		try{
	
			$this->transport->open();
			$result = $this->client->issueMultipleVouchersNew( $voucher_series_id, $user_ids, $org_id, $current_id, $group_id );
			$this->logger->debug( "shopbook Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling shopbook service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function issueMultipleVouchers( $voucher_series_id, $user_ids, $org_id, $current_id, $group_id ){
	
		$this->logger->debug( "shopbook Service Client: ".
				"Calling issueMultipleVouchers with params - Coupon series: $voucher_series_id, ".
																"UserId: $user_ids, ".
																"OrgId: $org_id, ".
																"CurrentId: $current_id, ".
																"GroupId: $group_id" );
		try{
	
			$this->transport->open();
			$result = $this->client->issueMultipleVouchers( $voucher_series_id, $user_ids, $org_id, $current_id, $group_id );
			$this->logger->debug( "shopbook Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling shopbook service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function trackerMilestoneReminder( $org_id, $tracker_id, $user_id ){
	
		$this->logger->debug( "shopbook Service Client: ".
				"Calling trackerMilestoneReminder with params -  OrgId: $org_id, ".
																"TrackerId: $tracker_id, ".
																"UserId: $user_id ");
		try{
	
			$this->transport->open();
			$result = $this->client->trackerMilestoneReminder( $org_id, $tracker_id, $user_id );
			$this->logger->debug( "shopbook Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling shopbook service: $e");
			$this->transport->close();
			return false;
		}
	}
	
	public function pointsExpiryInfoForUsers( $user_ids, $org_id ){
	
		$this->logger->debug( "shopbook Service Client: ".
				"Calling pointsExpiryInfoForUsers with params - UserIds: $user_ids, OrgId: $org_id" );
		try{
	
			$this->transport->open();
			$result = $this->client->pointsExpiryInfoForUsers( $user_ids, $org_id );
			$this->logger->debug( "shopbook Service Client: Returning back with data - ".print_r( $result, true ) );
			$this->transport->close();
			return $result;
		}catch( Exception $e ){
	
			$this->logger->error("Exception in calling shopbook service: $e");
			$this->transport->close();
			return false;
		}
	}
}
?>