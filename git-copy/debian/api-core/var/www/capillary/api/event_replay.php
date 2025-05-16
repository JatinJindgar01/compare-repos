<?php

ob_start();
include_once 'common.php';
include_once 'model_extension/class.OrganizationModelExtension.php';
define('X_CAP_REQUEST_ID_PARAM','X-CAP-REQUEST-ID');
define('API_AUTH_KEY_HEADER','X-CAP-API-AUTH-KEY');
define('API_AUTH_ORG_ID','X-CAP-API-AUTH-ORG-ID');
header("Content-Type:application/json; charset=utf-8");
ini_set('memory_limit', '500M');
set_time_limit(3600);
$logger = new ShopbookLogger();

global $uuid;
$uuid = Util::getUUID();

$a = Auth::getInstance();
$headers = apache_request_headers();
$auth_key=isset($headers[API_AUTH_KEY_HEADER])?trim($headers[API_AUTH_KEY_HEADER]):'';
$_SERVER['UNIQUE_ID'] = !$headers[X_CAP_REQUEST_ID_PARAM] ?
                                                $_SERVER['UNIQUE_ID'] : $headers[X_CAP_REQUEST_ID_PARAM];

header('X-CAP-REQUEST-ID:'.$_SERVER['UNIQUE_ID']);

$user = stripslashes($_SERVER['PHP_AUTH_USER']);
$pwd = stripslashes($_SERVER['PHP_AUTH_PW']);
$currentorgId = stripslashes($headers[API_AUTH_ORG_ID]);
$currentorgId =$currentorgId?$currentorgId:0;
$currentorg = new OrganizationModelExtension();
$currentorg->load($currentorgId);



if(!empty($auth_key))
    $key_based_auth=true;
$parsedFile = parse_ini_file("/etc/capillary/api/api-config.ini");
$validKeys = $parsedFile["authenticated_api_keys"];
$inValidKey = !($key_based_auth &&in_array($auth_key, $validKeys));
$currentuser = new DummyUser();
$GLOBALS['currentuser'] = $currentuser;
$GLOBALS['currentorg'] = $currentorg;
if($inValidKey){
    if ( $a->loginForApi($user,  $pwd, $auth_key) < 0 ){
	$response["status"] = false;
	$response["message"] = "Authentication failed";
	die(json_encode($response));
    }
    $currentuser = $a->getLoggedInUser(); unset($user); unset($pwd);
    $currentorg = $currentuser->org;
    $currentorgId = $currentorg->org_id;
}

#if( $currentuser->getType() != 'ADMIN_USER' ){
#	$response["status"] = false;
#	$response["message"] = "Authentication failed";
#	die(json_encode($response));
#}


$response = array();
$response["status"] = false;
$response["message"] = "Failed to get the data";


include_once 'svc_mgrs/EmfServiceManager.php';


$action = strtolower($_REQUEST["action"]);

switch ($action) {
	case "org_report":
		$emfServiceManager = new EmfServiceManager($currentorgId);
		$orgIdArr = isset($_GET['org_id']) ? explode(",", $_GET['org_id']) : null;
		$startDate = $_GET['start_date'];
		$endDate = $_GET['end_date'];
		$response["report"] = $emfServiceManager->getPendingEventsCountForOrg($orgIdArr, $startDate, $endDate);
		$response["report"] = $response["report"] ? $response["report"] : array();
		$response["status"] = true;
		$response["message"] = "Fetched the data successfully";
		break;

	case 'pending_users_list':
		$orgId = $_GET['org_id'] ||  $_GET['org_id'] === "0" ? $_GET['org_id'] : null;
		if(!isset($orgId) )
		{
			$response["status"] = false;
			$response["message"] = "org_id is not passed";
		}
		else
		{
			$startDate = $_GET['start_date'];
			$endDate = $_GET['end_date'];
			$user_ids = isset($_GET['user_id']) ? $_GET['user_id'] : null;
			$emfServiceManager = new EmfServiceManager($currentorgId);
			$response["report"] = $emfServiceManager->getPendingEventsCountForUsers($orgId, $userIdsArr, $startDate, $endDate);
			$response["status"] = true;
			$response["report"] = $response["report"] ? $response["report"] : array();
			$response["message"] = "Fetched the data successfully";
		}
		break;

	case 'replay_event':
		$count = replayEvents();
		if($count["success"] > 0 )
		{
			$response["status"] = true;
			$response["message"] = "Replayed events successfully";
			$response["success_count"] = $count["success"]+0;
			$response["fail_count"] = $count["fail"]+0;
		}
		else {
			$response["status"] = false;
			if($count["success"] == -1 )
				$response["message"] = "Event replay already in progress";
			else
				$response["message"] = "No event could be replayed";
			$response["success_count"] = 0;
			$response["fail_count"] = $count["fail"]+0;
		}
		$logger->info("Replay event result".print_r($response, true));
		break;
		
	case 'reset_status':
		$emfServiceManager = new EmfServiceManager($currentorgId);
		$emfServiceManager->forceResetCircuitStatus();
		$response["status"] = true;
		$response["message"] = "EMF service status is reset";
		break;
		
	case 'replay_logs':
		$emfServiceManager = new EmfServiceManager($currentorgId);
		$logs = $emfServiceManager->getReplayLogs();
		
		$memcacheKey = "oa_EMF_REPLAY_PROGRESS";
		$cache = MemcacheMgr::getInstance();
		$running = false;
		$running = array();
		try {
			$running = json_decode($cache->get($memcacheKey), true);
		} catch (Exception $e) {
		}

		if($logs)
		{
			foreach ($logs as &$log)
			{
				$log["status"] = array_key_exists( $log["unique_id"], $running) ? "Running" : "Completed";				
			}
			
			$response["status"] = true;
			$response["message"] = "Fetched the data successfully";
			$response["report"] = $logs;
		}
		else {
			$response["status"] = false;
			$response["message"] = "Failed to get data";
			$response["report"] = array();
		}
			
		break;
	default:
		;
		break;
}

ob_clean();
ob_start();

echo json_encode($response);

/**
 * Replays all the failed emf requests
 *  	- if a replay of a customer is failed, it wont be replayed
 *  	- if there are 1000 fails, it wont be retried further
 *
 *  return the count of successful replays
 */
function replayEvents()
{
	global $logger, $currentorg;
	
//	$memcacheKey = "oa_EMF_REPLAY_PROGRESS";
	$cache = MemcacheMgr::getInstance();
	$maxJob = 1;

//	$running = array();
//	try {
//		$running = json_decode($cache->get($memcacheKey), true);
//	} catch (Exception $e) {
//	}
//	 
//	if(count($running) >= $maxJob)
//	{
//		return array( "success" => -1 , "fail" => 0);
//	}
		
	$filterArr = array();
	$filterArr["org_id"] = isset($_GET['org_id']) ? $_GET['org_id'] : null;
	$filterArr["endDate"] = $_GET['end_date'];
	if(isset($_GET['user_id']))
		$filterArr["user_id"] = $_GET['user_id'];
	$filterArr["startDate"] = $_GET['start_date'];
	$filterArr["limit"] = 999;
	$filterArr["status"] = implode("', '", array('FAIL', 'IN_PROGRESS'));
	$unique_id = microtime(true)."#".rand(1000000, 9999999);

	$failedUserIds = array();
	$successInLoop = 0; $totalSuccess = 0; $totalFail = 0;$sikpped=0;
		
	$emfServiceManager = new EmfServiceManager($currentorgId);
	try {
		$emfServiceManager->logReplay($unique_id, $totalSuccess + $totalFail, $totalFail);
	} catch (Exception $e) {
	}
	
	$cacheLastUpdateTime = time();
	$cacheUpdateThreshold = 180; // 3 minutes
	$cacheTTL  =  900;//15mins
	$running[$unique_id] = $filterArr["org_id"] ? $filterArr["org_id"] : "ALL"; 
	
//	try {
//		$cache->set($memcacheKey, json_encode($running), $cacheTTL);
//	} catch (Exception $e) {
//	}
	// to hold org sepecifiv event manager
	$emfServiceManagerArr = array();
	do
	{
		$successInLoop = 0;
		$pendingEvents = $emfServiceManager->getAllEvents($filterArr);
		//
		if($pendingEvents)
		{
			include_once "business_controller/emf/EMFServiceController.php";
			$emfController = new EMFServiceController();
			foreach($pendingEvents as $event)
			{

				if($event["event_name"]=="transactionFinished" || $event["event_name"]=="trackerConditionSuccess"   ){

					continue;
				}
				if(!$emfServiceManagerArr[$event["org_id"]])
					$emfServiceManagerArr[$event["org_id"]] = new EmfServiceManager($event["org_id"]);
					
				try {
					// dont trigger a call if there is already a failure for the user
					if(!in_array($event["user_id"], $failedUserIds))
					{
                                            $userId = $event["user_id"];
                                            $memcacheKey = "oa_EMF_REPLAY_PROGRESS_".$userId;
                                            $running = false;
                                            try{
                                                $running = json_decode($cache->get($memcacheKey), true);
                                            }
                                            catch(Exception $e){
                                            }
                                            if(!$running){
                                                try{
                                                    $cache->set($memcacheKey,1, $cacheTTL);
                                                }
                                                catch(Exception $e){
                                                    return array( "success" => -1 , "fail" => 0);
                                                }
						$logger->debug("Calling the event ".$event["event_name"] . " = unqiue_id -> " .$event["unique_id"]);
						$emfController->callEMF($event["event_name"], $event["org_id"],
							$event["user_id"], 1, json_decode($event["params"], true), 1, $event["unique_id"]);
						try{
                                                $cache->delete($memcacheKey);
                                                }
                                                catch(Exception $e){
                                                    
                                                }
                                                $successInLoop++; $totalSuccess++;
                                            }
                                            else{
                                                $skipped++;
                                            }
					}
				} catch (Exception $e) {
                                        $memcacheKey = "oa_EMF_REPLAY_PROGRESS_".$event["user_id"];
                                        try{
                                        $cache->delete($memcacheKey);
                                        }
                                        catch(Exception $e){
                                            
                                        }
					$failedUserIds[] = $event["user_id"];
					$emfServiceManagerArr[$event["org_id"]]->logEvent($event["event_name"], $event["user_id"], $event["transaction_id"], $event["unique_id"], "FAIL", null);
					$totalFail ++;
				}

//				// update more frequent for being safe
//				try {
//					if($cacheLastUpdateTime + $cacheUpdateThreshold <= time() )
//					{	
//						$cacheLastUpdateTime = time();
//						$cache->set($memcacheKey, json_encode($running), $cacheTTL);
//					}
//				} catch (Exception $e) {
//				}

			}
		}
		$logger->debug("Status of emf replay : $totalSuccess + $totalFail");
		try {
			$emfServiceManager->logReplay($unique_id, $totalSuccess + $totalFail, $totalFail);
			$emfServiceManager->closeCircuit();
		} catch (Exception $e) {
		}
	}while($pendingEvents && $successInLoop > 0 && $totalFail + $totalSuccess < 100000);

//	try {
//		$running = json_decode($cache->get($memcacheKey), true);
//	} catch (Exception $e) {
//	}
//
//	unset($running[$unique_id]);
//	try {
//		if($running)
//			$cache->set($memcacheKey, json_encode($running), $cacheTTL);
//		else 
//			$cache->delete($memcacheKey);
//	} catch (Exception $e) {
//	}
	
	return array( "success" => $totalSuccess, "fail" => $totalFail,"skipped"=>skipped);
        
        
        
       
        
        
        

}


class DummyUser{
            public $user_id;
            public $org_id;
            public function DummyUser() {
                $this->user_id = -1;
                $this->org_id = 0;
            }
}



