<?php 

/**
 * 
 * API Utilities helper class
 * Has the needed static methods & helpers for the API
 * 
 * Independence from cheetah helper - Util.php
 * 
 * @author vimal
 */

class ApiUtil{
	

	private static $api_error_count=0;
	private static $api_success_count=0;
	private static $api_item_count=0;
	private static $api_item_status_codes="";
	private static $batch_item_count=0;
	
	const LUCI_COUPON_CODE_DELIMITER = "__";

	//getters
	public static function getSuccessCount()
	{
		return self::$api_success_count;
	}
	
	public static function getErrorCount()
	{
		return self::$api_error_count;
	}
	
	public static function getItemStatusCodes()
	{
		return self::$api_item_status_codes;
	}
	
	public static function getItemCount()
	{
		return self::$api_item_count;
	}
	
	
	//setters
	public static function setSuccessCount($count=0)
	{
		self::$api_success_count=$count;
	}

	public static function setErrorCount($count=0)
	{
		self::$api_error_count=$count;
	}

	public static function setItemStatusCodes($codes)
	{
		global $gbl_item_status_codes;
		if(is_array($codes))
			$codes=implode(",",$codes);
		$gbl_item_status_codes=$codes;
		self::$api_item_status_codes=$codes;
	}

	public static function setItemCount($count)
	{
		global $gbl_item_count;
		$gbl_item_count=$count;
		self::$api_item_count=$count;
	}
	
	
	//incr-ers
	public static function incrSuccessCount()
	{
		self::$api_success_count++;
	}
	
	public static function incrErrorCount()
	{
		self::$api_error_count++;
	}
	
	public static function incrItemCount()
	{
		self::$api_item_count++;
	}
	
	
	/* start of utilities */
	
	public static function findApiStatusFromResponse($response)
	{
		global $logger;
		
		$tmp=$response;
		foreach($response as $node=>$value)
			if($node!="status")
				continue;

		//nasty hack to find whether batch call, but it works!
		if(count($value)==1)
		{
			$tmp=$value;
			$value2=array_shift($tmp);
			if(isset($value2[0]))
				$value=$value2;
			$value2=array_shift($value2);
			if(is_array($value2) && isset($value2[0]))
				$value=$value2;
			$tmp=$value;
			$value3=array_shift($tmp);
			$value4=array_shift($tmp);
			if(isset($value3['item_status']) && !isset($value4['item_status']))
				$value=$value3;
		
		}
		if(!isset($value[0]))
			$value=array($value);
		$resource_resp=$value;

		
		$item_count=count($resource_resp);
		$success_count=0;
		$fail_count=0;
		$item_status=array();		
		
		foreach($resource_resp as $resp)
		{
			if(isset($resp['item_status']))
			{
				$status=isset($resp['item_status']['status'])?$resp['item_status']['status']:$resp['item_status']['success'];
				if(strtolower($status)=="true" || $status===true || $status==1)
					$success_count++;
				else
					$fail_count++;
				$item_status[]=$resp['item_status']['code'];
			}
		}
		
		if(empty($item_status))
		{
			$item_status[]=$response['status']['code'];
			$item_count=1;
			if($response['status']['code']!=200)
				$fail_count=1;
			else
				$success_count=1;
		}
		
		self::setItemCount($item_count);
		self::setSuccessCount($success_count);
		self::setErrorCount($fail_count);
		self::setItemStatusCodes($item_status);
		
		$logger->debug("
				Calculated API statuses are 
					item_count : $item_count
					success_count : $success_count
					error_count : $fail_count
					item_statuses :".self::getItemStatusCodes()
				);
		
		return array(
				'item_count'=>self::getItemCount(),
				'success_count'=>self::getSuccessCount(),
				'error_count'=>self::getErrorCount(),
				'item_status_codes'=>self::getItemStatusCodes()
				);
		
	}
	
	/**
	 * Get the batch limit from conf or return default
	 * @param number $default
	 * @return number
	 */
	public static function getBatchLimit($default=100)
	{
		global $cfg,$logger;
		if(!isset($cfg['batch_limit']))
		{
			$logger->error("batch limit not available in config. returning default : $default");
			return $default;
		}
		$logger->info("batch limit set is {$cfg['batch_limit']}");
		return $cfg['batch_limit'];
	}
	
	
	/**
	 * Check batch limit reach
	 * throw exception when limit exceeded
	 * makes use of static var batch_item_count to keep track of batch items
	 * 
	 * @param string $throw
	 * @param string $exec
	 * @throws Exception
	 * @return boolean
	 */
	public static function checkBatchLimit($throw=true,$exec='ERR_BATCH_LIMIT_EXCEEDED')
	{
		
		global $logger;
		
		self::$batch_item_count++;
		
		$batch_limit=ApiUtil::getBatchLimit();
		$count=self::$batch_item_count;
		
		if($count>$batch_limit)
		{
			$logger->info("Batch Limit exceeded!! $count > $batch_limit");
			if($throw)
				throw new Exception($exec);
			return false;
		}
		$logger->info("Batch Limit is ok - $count,$batch_limit");
		return true;
		
	}
	
	
	
	
	
	
	
	
	

	/**** mmethods copied from Cheetah Util  *****/
	
	public static function storeAPILogs( $resource, $method, $org_id, $user_id,
			$page_time, $uuid, $version,$query_p, $http_m,
			$user_agent)
	{
		$succ_count=self::getSuccessCount();
		$error_count=self::getErrorCount();
		$status_codes=self::getItemStatusCodes();

        $sql = "INSERT INTO api_hit_table (request_id, apache_req_id, resource, method, org_id, user_id, response_time, hit_Time,".
            "client_ip, success_count, failure_count, api_version, query_params, http_method, user_agent_id, status_codes, server_name)".
            "VALUES ('$uuid', '{$_SERVER['UNIQUE_ID']}', '$resource', '$method',
				$org_id, $user_id, $page_time, NOW(), INET_ATON('" . $_SERVER['REMOTE_ADDR'] . "'), $succ_count,
				$error_count, '$version', '$query_p', '$http_m', '$user_agent_id', '$status_codes',  '{$_SERVER['SERVER_ADDR']}')";

		$data=array();
		$data["request_id"]=$uuid;
		$data["resource"]=$resource;
		$data["method"]=$method;
		$data["org_id"]=$org_id;
		$data["user_id"]=$user_id;
		$data["response_time"]=$page_time;
		$data["hit_time"]=new MongoDate();
		$data["client_ip"]=$_SERVER['REMOTE_ADDR'];
		$data["success_count"]=$succ_count;
		$data["failure_count"]=$error_count;
		$data["api_version"]=$version;
		$data["query_params"]=$query_p;
		$data["http_method"]=$http_m;
		$data["user_agent"]=$user_agent;
		$data["status_codes"]=$status_codes;
		$data["server_name"]=$_SERVER['SERVER_ADDR'];
        self::insert($data);
	}

	 private static function insert($data)
        {
            $mongoConfigs = array();
            $mongoConfigs['user']=$_ENV['API_LOG_MONGO_USER'];
            $mongoConfigs['pass']=$_ENV['API_LOG_MONGO_PASSWORD'];
            $mongoConfigs ['port'] = 27017;
            $mongoConfigs ['rs_mongo_address'] = "intouch-api-logs:27017,intouch-api-logs-slave:27017";
            $mongoConfigs ['dbase'] = "performance_logs";
            $mongo = new MongoDBUtil();
            $mongo->initRS($mongoConfigs);
            $dateValue=time();
            $mongo->selectCollection('api_hit_table_'.(date("Y", $dateValue)).(date("m", $dateValue)));
            $mongo->insert($data);
        }
        
        public static function logRedemption($org_id, $till_id, $user_id, $resource, $method, $points, $skip_validation)
        {
            global $logger;
            $headers = apache_request_headers();
            include_once 'models/RedemptionRequestLog.php';
            $redemptionRequestLogModel = new RedemptionRequestLog();
            $redemptionRequestLogModel->setOrgId($org_id);
            $redemptionRequestLogModel->setTillId($till_id);
            $redemptionRequestLogModel->setUserId($user_id);
            $redemptionRequestLogModel->setClientIp($headers['X-Forwarded-For']);
            $redemptionRequestLogModel->setClientSignature($headers['X-CAP-CLIENT-SIGNATURE']);
            $redemptionRequestLogModel->setRequestScope($resource);
            $redemptionRequestLogModel->setRedeemedItem($points);
            $redemptionRequestLogModel->setRequestType($method);
            $redemptionRequestLogModel->setSkipValidation($skip_validation);
            try {
                $redemptionRequestLogModel->save();
            }
            catch (Exception $e)
            {
                $logger->debug("Failed logging redemption");
            }
            $logger->debug("Logged redemption");
        }

	public static function readfile_chunked($filename, $retbytes = TRUE) {
    	define('CHUNK_SIZE', 10*1024*1024); // Size (in bytes) of tiles chunk
    	global $logger;
		
    	$buffer = '';
    	$cnt = 0;
    	// $handle = fopen($filename, 'rb');
    	$handle = fopen($filename, 'rb');
    	if ($handle === false) {
        	$logger->error("Could not open $filename");
        	return false;
    	}

	    while (!feof($handle)) {
	        $buffer = fread($handle, CHUNK_SIZE);
	        echo $buffer;
	        //ob_flush();
	        flush();
	        if ($retbytes) {
	            $cnt += strlen($buffer);
	        }
	    }

    	$status = fclose($handle);
    	if ($retbytes && $status) {
	        return $cnt; // return num. bytes delivered like readfile() does.
    	}
    	return $status;
	}
	
	public static function transformArrayToXml($array, $node){
		$transformer = DataTransformerFactory::getDataTransformerClass('XML');
		return $transformer->doTransform($array, $node);
	}

    public static function logTransactionTypeChange($org_id, $till_id, $user_id, $type, $old_id, $new_id)
    {
        global $logger;
        $headers = apache_request_headers();
        include_once 'models/TransactionTypeUpdateLog.php';
        $transactionTypeUpdateLogModel = new TransactionTypeUpdateLog();
        $transactionTypeUpdateLogModel->setOrgId($org_id);
        $transactionTypeUpdateLogModel->setTillId($till_id);
        $transactionTypeUpdateLogModel->setUserId($user_id);
        $transactionTypeUpdateLogModel->setClientIp($headers['X-Forwarded-For']);
        $transactionTypeUpdateLogModel->setClientSignature($headers['X-CAP-CLIENT-SIGNATURE']);
        $transactionTypeUpdateLogModel->setChangeType($type);
        $transactionTypeUpdateLogModel->setOldId($old_id);
        $transactionTypeUpdateLogModel->setNewId($new_id);

        try {
            $transactionTypeUpdateLogModel->save();
        }
        catch (Exception $e)
        {
            $logger->debug("Failed logging type update");
        }
        $logger->debug("Logged type update");
    }
	
	public static function getTillsForAdminUser($user_id, $org_id){
		global $logger;
		include_once 'thrift/authenticationservice.php';
		$auth_service =  new AuthenticationServiceClient();
		$role_map_user = $auth_service->getRoleMapForUsers(array($user_id), $org_id);
		try{
			$memcache = MemcacheMgr::getInstance();
		} catch (Exception $e){
			$this->logger->debug("Memcache not present");
		}
			
		
		try{
			$all_tills = json_decode($memcache->get("o".$org_id."_ADMIN_USER_TILLS_".$user_id), true);
		} catch (Exception $e){
			$logger->debug("Tills for admin user not in cache");
		}
		
		if(!$all_tills){
			$all_tills = array();
			foreach($role_map_user as $role){
				foreach($role as $key=>$values){
					if($key == "ORG"){
						throw new Exception("ERR_ORG_USER_ACCESSS");
					}
					$logger->debug("Not org level user, fetching tills");
					if(in_array($key, array("STORE", "CONCEPT", "ZONE"))){
						foreach($values as $value){
							foreach($value->childEntities as $store_entity){
								$org_entities = new ApiEntityController("STORE");
								$tills = $org_entities->getChildrenEntityByType($store_entity->entityId, "TILL");
								$store_servers = $org_entities->getChildrenEntityByType($store_entity->entityId, "STR_SERVER");
								$all_tills = array_merge($all_tills, $tills, $store_servers);
							}
						}
					}
				}
			}
		$logger->debug("fetched all tills");
		try{
			$memcache->set("o".$org_id."_ADMIN_USER_TILLS_".$user_id, json_encode($all_tills), 10800);
		} catch(Exception $e){
			$logger->debug("memcache set key failed for admin user tills");
		}
		}
		return $all_tills;
		
	}

    public static function mcUserUpdateCacheClear($user_id)
	{
		include_once 'helper/CacheKeysPrefix.php';
		
		try{
			$cache = MemcacheMgr::getInstance();
			$all_keys = $cache->getMembersOfSet(CacheKeysPrefix::$mc_admin_user_cache.$user_id);
			if(count($all_keys)>0)
				$cache->deleteMulti($all_keys);
		} catch ( Exception $e){
//			$logger->debug("Error deleting customer profile cache set by mc");
		}
	}

	public static function trimCouponCode($luciCouponCode) {
		$containsLuciDelimiter = strpos($luciCouponCode, ApiUtil::LUCI_COUPON_CODE_DELIMITER);
		if ($containsLuciDelimiter !== false) {
			$temp = explode(ApiUtil::LUCI_COUPON_CODE_DELIMITER, $luciCouponCode, 2);
			$trimmedCouponCode = $temp [0];

			if (! empty($trimmedCouponCode)) {
				$luciCouponCode = $trimmedCouponCode;
			}
		}

		return $luciCouponCode;
	}

	public function newIssueCoupon($orgId, $userIdToIssueCouponTo, $seriesIdToIssueFrom, $storeUnitId) {

		require_once "services/luci/service.php";

		$ls = new LuciService();
		$response = $ls -> issue($orgId, $userIdToIssueCouponTo, $seriesIdToIssueFrom, $storeUnitId);

		return $response;
	}	

	public function luciGetCoupon($orgId, $couponIdType, $couponIdentifiers, $customerId = null) {

		require_once "services/luci/service.php";
		global $logger;

		$customerIds = null;
		if (! empty($customerId)) {
			$customerIds = array($customerId);
		}

		$ls = new LuciService();
		if ($couponIdType == "coupon_code") {
			$response = $ls -> getCouponDetailsByCodes($orgId, $couponIdentifiers, $customerIds);
		} elseif ($couponIdType == "coupon_id") {
			$response = $ls -> getCouponDetailsByIds($orgId, $couponIdentifiers, $customerIds);
		} else {
			$logger -> error("ApiUtil -> luciGetCoupon :: Invalid couponIdType '$couponIdType' ");
			$response = null;
		}

		return $response;
	}	

	public function luciGetCouponSeries($orgId, $couponSeriesId) {

		require_once "services/luci/service.php";

		$ls = new LuciService();
		return $ls -> getCouponSeriesById($orgId, $couponSeriesId);
	}	

	public function luciDateToStr($date, $format = "Y-m-d") {
		$timestamp = $date / 1000;
		
		$dateTimeObj = new DateTime();
		$dateTimeObj -> setTimestamp($timestamp);	

		return $dateTimeObj -> format($format);
	}
}