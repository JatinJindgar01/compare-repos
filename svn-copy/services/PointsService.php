<?php
include_once 'thrift/pointsengineservicefactory.php';
include_once 'exceptions/ApiPointsServiceException.php';

/**
 *
 * @author Abhilash
 *  The service class is bridge between the api and the points engine/emf
 */

class PointsService{

	private $logger;

	private $C_points_engine_thrift_client;

	public function __construct(){

		global $logger;
		$this->logger = $logger;

		$this->C_points_engine_thrift_client = PointsEngineServiceThriftClientFactory::getPointsEngineServiceThriftClient();
		//$this->cb_client = new PEServiceManager();
	}

	/*
	 * The function to fetch the customer summary details
	 */
	public function getPointsSummaryForCustomer($orgId, $customerId, $retry = false) {
		$this->logger->debug("Fetching summary information from points engine");
		
		$data = array();
		return $this->callPointsEngineService($orgId, $customerId, 'getCustomerPointsSummary', $data, $retry);
	}

	public function getBillPointsDetails($orgId, $customerId, $billId, $retry = false) {

		//getBillPointsDetails
		$this->logger->debug("Fetching bill information from points engine");
		
		$data = array('bill_id' => $billId);
		return $this->callPointsEngineService($orgId, $customerId, 'getBillPointsDetails', $data, $retry);
	}

	public function getSlabUpgradeHistory($orgId, $customerId, $retry = false) {

		//getSlabUpgradeHistory
		$this->logger->debug("Fetching slab upgrade information from points engine");
		
		$data = array();
		return $this->callPointsEngineService($orgId, $customerId, 'getSlabUpgradeHistory', $data, $retry);
	}

	public function getDeductionsForCustomer($orgId, $customerId, $retry = false) {

		//getDeductionsForCustomer
		$this->logger->debug("Fetching point deductions information from points engine");
		
		$data = array();
		return $this->callPointsEngineService($orgId, $customerId, 'getDeductionsForCustomer', $data, $retry);
	}

	/**
	 * points expiry schedule for the customer.
	 * @param unknown_type $org_id
	 * @param unknown_type $customer_id
	 */
	public function getPointsExpiryScheduleForCustomer( $org_id , $customer_id ){
			
		$this->logger->debug( 'Getting Information of points expiry schedule' );

		return $this->callPointsEngineService($org_id, $customer_id, 'getPointsExpiryScheduleForCustomer');
	}


	/**
	 *
	 * @param long $orgId
	 * @return 1 if can't fetch currency ratio from points engine,
	 * 			else return proper currency ratio fetched from points engine.
	 */
	public function getPointsCurrencyRatio( $orgId )
	{
		$this->logger->debug("Fetching points currency ratio from points engine");
		$ratio = 1;
		$mem_cache_manager = MemcacheMgr::getInstance();
		$cache_key = CacheKeysPrefix::$orgPointsCurrencyRatio.$orgId;
		$ttl = CacheKeysTTL::$orgPointsCurrencyRatio;
                $this->logger->debug(" Not Looking cache for p2cr");
                try
                    {
			$this->logger->debug("Currency Ratio not found for org_id: $org_id,
						going to fetch from Points engine");
						$ratio = $this->callPointsEngineService($orgId, null, 'getPointsCurrencyRatio',array());
						$this->logger->debug("Currency Ratio fetched from Points Engine: $ratio");
						$mem_cache_manager->set( $cache_key, $ratio, $ttl );
			}
			catch(Exception $e)
			{
				$this->logger->error("Error while fetching Currency Ratio from Points engine,
						or while setting currency ratio to memcache: ".$e->getMessage());
			}
		
	
		return $ratio;
	}
	
	public function getPurchaseHistoryForCustomer($org_id, $customer_id)
	{
		$result=array();
	
		$this->logger->info("Getting purchase history for customer:$customer_id ");
	
		try{
	
			$result = $this->callPointsEngineService($org_id, $customer_id,
					"getPurchaseHistoryForCustomer");
	
		}catch(Exception $e)
		{
			$this->logger->error("Error while fetching purchase history from PE");
		}
	
		$return=array();
	
		$params=array(
				'loyalty_log_id'=>'pointsSourceId',
				'points'=>'points',
				'points_expired'=>'pointsExpired',
				'points_redeemed'=>'pointsRedeemed',
				'points_returned'=>'pointsReturned',
				'awarded_date'=>'awardedDate',
				'user_id'=>'customerId',
				'expiry_date'=>'expiryDate',
				'category_name'=>'pointCategoryName',
		);
	
		if(isset($result->pa))
			foreach($result->pa as $points_details)
			{
				$single_data=array();
				foreach($params as $key=>$value)
					$single_data[$key]=$points_details->$value;
	
				$return[$points_details->pointsSourceId]=$single_data;
	
			}
	
			$this->logger->debug("Transformed points details :".print_r($return,true));
	
			return $return;
	
	}
	
	/*
	 * Makes all the calls to points engine from here. 
	 */
	private function callPointsEngineService($orgId, $customerId, $callType, $data = array(), $retry = false)
	{
		if(!Util::isPointsEngineActive())
		{
			$this->logger->error("Points engine is not Active, can't fetch any information from Points engine");
			throw new ApiPointsServiceException(ApiPointsServiceException::POINTS_ENGINE_DISABLED);
		}
		
		$max_retries = 0;
		if($retry)
			$max_retries = 3;
		$tries = 0;
		$exception = "";

		do {

			try {

				$tries++;
				$result = "";

				$pesClient = PointsEngineServiceThriftClientFactory::getPointsEngineServiceThriftClient();

				switch($callType) {
					case "getCustomerPointsSummary" :
						$result =$pesClient->getCustomerPointsSummary($orgId, $customerId);
						break;
							
					case "getBillPointsDetails" :
						$result = $pesClient->getBillPointsDetails($orgId, $customerId, $data['bill_id']);
						break;

					case "getSlabUpgradeHistory" :
						$result = $pesClient->getSlabUpgradeHistory($orgId, $customerId);
						break;

					case "getDeductionsForCustomer" :
						$result = $pesClient->getDeductionsForCustomer( $orgId, $customerId);
						break;

					case "getPointsExpiryScheduleForCustomer":
						$result = $pesClient->getPointsExpiryScheduleForCustomer($orgId, $customerId );
						break;
					case "getPointsCurrencyRatio":
						$result = $pesClient->getPointsCurrencyRatio($orgId);
						break;

					case 'getPurchaseHistoryForCustomer':
						$result= $this->C_points_engine_thrift_client->getPurchaseHistoryForCustomer($orgId, $customerId);
						break;

				}
				$this->logger->info("Received : ".print_r($result, true));

				return $result;

			} catch (points_engine_PointsEngineServiceException $pesEx) {
				$this->logger->error("Error during points engine call : " .
						$pesEx->errorMessage . " [" . $pesEx->statusCode) . " ]. Tries : $tries";
				$exception = $pesEx;
			} catch (Exception $ex) {
				$this->logger->error("Error during points engine call " . $ex->getMessage() . ". Tries : $tries");
				$exception = $ex;
			}

		} while ($tries < $max_retries );

		throw $exception;
	}

	public function updateForTrackerConditionTransaction(
			$orgId, $customerId, $tracked_data_ref_type,
			$tracked_data_ref_id, $trackerTimeInMillis)
	{

		//Check if points engine is enabled. Do not perform if its not enabled
		if(!Util::isPointsEngineActive()) {
			$this->logger->error("Points engine service is disabled, should not be called");
			return false;
		}

		//TODO: This should be ITracker::BILL
		if( $tracked_data_ref_type == 1
				&& $tracked_data_ref_id > 0 )
		{
			$this->updateForNewBillTransaction(
					$orgId, $customerId, $tracked_data_ref_id, "", $trackerTimeInMillis);
		} else {
			//Update loyalty
			$this->updateLoyaltyTableFromPointsEngine($orgId, $customerId);

			//$this->checkAndCreateSlabUpgradeEntries($orgId, $customerId, $trackerTimeInMillis);
		}

	}

	public function isProgramPresentForOrg( $orgID ){

		try {
			$request_id = Util::getServerUniqueRequestId();
			$pesClient = new PointsEngineServiceThriftClient();
			$this->logger->debug("Calling isProgramPresentForOrg");
			$result = $pesClient->isProgramPresentForOrg( $orgID , $request_id );
			return $result;

		} catch (points_engine_PointsEngineServiceException $pesEx) {
			$this->logger->error("Error during points engine call : " .
					$pesEx->errorMessage . " [" . $pesEx->statusCode) . " ]";
			$exception = $pesEx;
		} catch (Exception $ex) {
			$this->logger->error("Error during points engine call " . $ex->getMessage());
			$exception = $ex;
		}
	}

	public function getBasicProgramDetails( $orgID ){

		try {
			$request_id = Util::getServerUniqueRequestId();
			$pesClient = new PointsEngineServiceThriftClient();
			$this->logger->debug("Calling isProgramPresentForOrg");
			$result = $pesClient->getBasicProgramDetails( $orgID, $request_id );
			return $result;

		} catch (points_engine_PointsEngineServiceException $pesEx) {
			$this->logger->error("Error during points engine call : " .
					$pesEx->errorMessage . " [" . $pesEx->statusCode) . " ]";
			$exception = $pesEx;
		} catch (Exception $ex) {
			$this->logger->error("Error during points engine call " . $ex->getMessage());
			$exception = $ex;
		}
	}



	/**
	 * Return the deductions of customer
	 * @param unknown_type $orgId
	 * @param unknown_type $customerId
	 */
	public function getPointsDeductionsByType($org_id, $customer_id, $type=null, $group_by_customer=false)
	{

		$this->logger->info("Getting points deductions for $customer_id for type : $type");

		try{
			$deductions=$this->getDeductionsForCustomer($org_id, $customer_id);
			if(empty($type))
				return $deductions->pd;
			$this->logger->debug("Deductions array : ".print_r($deductions->pd,true));
			return $this->getDeductionsByType($deductions->pd, $type,$group_by_customer);
		}catch(Exception $e){

			$this->logger->debug('getPointsDeductionsByType:'.$e);
			return null;
		}
	}

	// map['till_id']['redeemed_time'] = redeemed points
	public function getPointsRedeemedDetails( $org_id, $user_id )
	{

		$this->logger->info("Inside getPointsRedeemedDetails: $org_id, $user_id");

		$points_redeemed_details = array();

		$points_redeemed_data = $this->getPointsDeductionsByType( $org_id , $user_id, "REDEEMED") ;

		foreach ( $points_redeemed_data as $cur_pr ){
			if( $cur_pr->pointsDeducted == 0 )
				continue;

			if( !isset( $points_redeemed_details[ $cur_pr->pointsDeductedById] [ $cur_pr->pointsDeductedOn ]))
				$points_redeemed_details[ $cur_pr->pointsDeductedById ] [ $cur_pr->pointsDeductedOn ]
				= $cur_pr->pointsDeducted;
			else
				$points_redeemed_details[ $cur_pr->pointsDeductedById ] [ $cur_pr->pointsDeductedOn ]
				+= $cur_pr->pointsDeducted;

		}

		return $points_redeemed_details;
	}

	/**
	 * Filters based on the type and group if required
	 * @param unknown_type $deduction
	 * @param unknown_type $key
	 * @param unknown_type $type
	 */
	private function getDeductionsByType(&$deductions,$type,$group_by_customer)
	{
		$deductions_array=array();
		foreach($deductions as $key=>$deduction)
		{
			if($deduction->deductionType === $type)
				array_push($deductions_array,$deduction);
		}
		switch($type)
		{
			case 'REDEEMED':
				return $this->getRedemptions($deductions_array);
				break;
			case 'EXPIRED':
				return $this->getExpiry($deductions_array);
				break;
			default:
				$this->logger->debug('$deductions_array:'.print_r($deductions_array,true));
				return $deductions_array;
		}
	}

	/** Grouping the redemptions based on time and till**/

	private function getRedemptions(&$deductions_array){

		$redemptions_array=array();
		foreach($deductions_array as $key=>$pd)
		{
			$key=date('Y-m-d H:i:s',strtotime($pd->pointsDeductedOn))."_".$pd->pointsDeductedById;
			if(array_key_exists($key, $redemptions_array))
			{
				$new_pd=$redemptions_array[$key];
				$new_pd->pointsDeducted = $new_pd->pointsDeducted+$pd->pointsDeducted;
				$new_pd->pointsDeductedCurrencyValue=$new_pd->pointsDeductedCurrencyValue+$pd->pointsDeductedCurrencyValue;
				$redemptions_array[$key]=$new_pd;
			}
			else
				$redemptions_array[$key]=$pd;
		}
		$this->logger->debug('$redemptions_array:'.print_r($redemptions_array,true));
		return $redemptions_array;
	}

	/**
	 * Group expiry based ontime and till
	 * @param unknown_type $deductions_array
	 */
	private function getExpiry(&$deductions_array){

		$expiry_array=array();
		foreach($deductions_array as $key=>$pd)
		{
			$key=date('Y-m-d H:i:s',strtotime($pd->pointsDeductedOn))."_".$pd->pointsDeductedById;
			if(array_key_exists($key, $redemptions_array))
			{
				$new_pd=$expiry_array[$key];
				$new_pd->pointsDeducted = $new_pd->pointsDeducted+$pd->pointsDeducted;
				$new_pd->pointsDeductedCurrencyValue=$new_pd->pointsDeductedCurrencyValue+$pd->pointsDeductedCurrencyValue;
				$expiry_array[$key]=$new_pd;
			}
			else
				$expiry_array[$key]=$pd;
		}
		$this->logger->debug('$expiry_array:'.print_r($expiry_array,true));
		return $expiry_array;
	}

}

?>