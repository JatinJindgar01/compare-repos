<?php
ob_start();

define (REALM, "Capillary Protected Area");
define (PASSWORD_ERR, 1001);
define (API_KEY_ERR, 1010);
define (INVALID_API_CALL, 1020);
define (INVALID_MAC_ID, 1030);

define('API_AUTH_KEY_HEADER','X-CAP-API-AUTH-KEY');
define('API_AUTH_ORG_ID','X-CAP-API-AUTH-ORG-ID');
define('X_CAP_REQUEST_ID_PARAM','X-CAP-REQUEST-ID');

$key_based_auth=false;
$headers = apache_request_headers();
$_SERVER['UNIQUE_ID'] = !$headers[X_CAP_REQUEST_ID_PARAM] ?
						$_SERVER['UNIQUE_ID'] : $headers[X_CAP_REQUEST_ID_PARAM];


//require_once('common.php');

/** HIGHER MEMORY LIMIT FOR THE API **/
ini_set('memory_limit', '500M');
/*
 * For older versions, the version is not set. So, failing with an ERR code
 * without an XML. For version 2, we die with a proper XML structured error.
 * http://docs.google.com/a/dealhunt.in/Doc?docid=0AccTfn1dfntYZHduZnZ2cV80ZnRuOGNjaGs&hl=en
 */
function showError($code, $msg) {
	global $logger;

    $api_version = $_GET['api_version'];

    if ($api_version == '2') {
    	
    	//Convert the Older Code to the new Code
    	   	
    	switch($code){
    		case PASSWORD_ERR :    							
    			$code = ERR_RESPONSE_INVALID_CREDENTIALS;
    			break;
    							
    		case API_KEY_ERR : 
    			$code = ERR_RESPONSE_INVALID_API_KEY;
    			break;
    							
    		case INVALID_API_CALL :
    			$code = ERR_RESPONSE_INVALID_API_CALL;
    			break;

    		case INVALID_MAC_ID :
    			$code = ERR_RESPONSE_MAC_ID_MISMATCH;
    			break;
    			
    		default : 
    			$code = ERR_RESPONSE_FAILURE;
    			break;
    	}
    	
        $api_status = array(
                'key' => getResponseErrorKey($code),
                'message' => getResponseErrorMessage($code)
        );
        $resp_data = array();
        $resp_data['api_status'] = $api_status;

		$logger->debug("New Client Error for $code : ".print_r($resp_data, true));

		$xml = new Xml();
        $res = $xml->serialize($resp_data);

    }
    else
    	$res = ("ERR".$code.": $msg");

    die($res);
}

//adding a shutdown handler to find all bad requests
function shutdownHandler(){

    $error = error_get_last();
    if(!is_null($error) && stripos(php_sapi_name(), "apache") > 0){
        $request_id = $_SERVER['UNIQUE_ID'];
        error_log("Error in request: $request_id: " . print_r($error, true));
    }
}


/***************************************
 * Actual Code starts here
 ***************************************/

register_shutdown_function('shutdownHandler');


if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="'.$realm.'"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Our API needs authentication. Please read the documentation at http://capillary.co.in';
	exit;
}

$cheetah_path = $_ENV['DH_WWW']."/cheetah";
include_once("helper/ShopbookLogger.php");
set_include_path(get_include_path() .PATH_SEPARATOR . $cheetah_path );
$logger = new ShopbookLogger();
require_once('common.php');
global $uuid;
$uuid = Util::getUUID();
#$_SERVER['UNIQUE_ID'] = $uuid;

require_once("apiHelper/ApiUtil.php");
require_once('wshandler.php');

global $gbl_item_count, $gbl_item_total_time, $gbl_item_status_codes, $uuid;

global $apiWarnings;
require_once 'helper/APIWarning.php';
$apiWarnings = new APIWarningList();

$gbl_item_count = 0;
$gbl_item_total_time = 0;
$gbl_item_status_codes = null;

$url = $_SERVER['REQUEST_URI'];

$urlParser = new UrlParser();
UrlParser::setCallRequestType( 'api' );

list($version, $resource, $method, $query_params) = $urlParser->parseApiUrl($url);

$logger->debug("Called URL: ".$url);
$logger->debug("version: $version, resource: $resource, method: $method, query_params: " . print_r($query_params, true));

$memusage_start = memory_get_usage(true)/1000000; //MB
$cpuload_start = Util::ServerLoad(); //percentage

$logger->debug("Init Memory: $memory_get_usage, init cpu: $cpuload_start");

$pageTimer = new Timer('page_timer');
$pageTimer->start();

if ($cfg['servertype'] == 'dev') {
	$logger->enabled = true;
}

$return_type = ($query_params['format'] == "") ? 'xml' : strtolower($query_params['format']);

$user = stripslashes($_SERVER['PHP_AUTH_USER']);
$pwd = stripslashes($_SERVER['PHP_AUTH_PW']);

$logger->debug("username $user passwd: $pwd\n");
$a = Auth::getInstance();
$auth_key=isset($headers[API_AUTH_KEY_HEADER])?trim($headers[API_AUTH_KEY_HEADER]):'';

if(!empty($auth_key))
	$key_based_auth=true;

if ( $a->loginForApi($user,  $pwd, $auth_key) < 0 ){
	$response = array('status'=>array('success'=>'false', 'code' => ErrorCodes::$api['AUTHENTICATION_FAILURE'], 'message'=>ErrorMessage::$api['AUTHENTICATION_FAILURE']));
	if($return_type == 'json'){
		ob_clean(); ob_start();
		die(json_encode(array("response" => $response)));
	}else{
		$xml = Array2XML::createXML('response', $response);
		ob_clean(); ob_start();
		die($xml->saveXML());
	}
}
$currentuser = $a->getLoggedInUser(); unset($user); unset($pwd);
if( $currentuser->getType() == 'ADMIN_USER' && !$key_based_auth){

	$response = array('status'=>array('success'=>'false', 'code' => ErrorCodes::$api['AUTHENTICATION_FAILURE'], 'message'=>ErrorMessage::$api['AUTHENTICATION_FAILURE']));
	if($return_type == 'json'){
		ob_clean(); ob_start();
		die(json_encode(array("response" => $response)));
	}else{
		$xml = Array2XML::createXML('response', $response);
		ob_clean(); ob_start();
		die($xml->saveXML());
	}
}


$currentorg = $currentuser->org;

if($key_based_auth && isset($headers[API_AUTH_ORG_ID]) && $headers[API_AUTH_ORG_ID]!="-1" && !empty($headers[API_AUTH_ORG_ID]))
{
	$logger->info(API_AUTH_KEY_HEADER." is available part of the header!! org_id: ".$headers[API_AUTH_ORG_ID]);
	$org_id = $headers[API_AUTH_ORG_ID];
	if(strlen((int)$org_id)==strlen($org_id))
		$org_pass_check=new OrgProfile($org_id);
	if(!isset($org_pass_check->org_id) || $org_pass_check->org_id==-1 || !isset($org_pass_check->name))
	{
		$logger->error("Passed header org id is invalid : $org_id; using the currentorg org id: {$currentorg->org_id}");
		$org_id=$currentorg->org_id;
		if(isset($currentorg))
			unset($currentorg);
	}else
		$logger->info("header org id is valid, using $org_id");
}
else
	$org_id=$currentorg->org_id; 



/**
 * switch the currentorg objects to OrgProfile
 
if($urlParser->isLegacyUrl()){
	$org_id = $currentorg->org_id;
	$logger->debug("Switching the current orgprofile object");
*/
	$currentorg = null;
	$currentorg = new OrgProfile($org_id);
	$logger->debug("class type: " . get_class($currentorg));
	
if($key_based_auth)
{
	$logger->info("replacing org profile in currentuser to the passed header org profile");
	$currentuser->org=$currentorg;
}

	$currentuser = StoreProfile::getById( $currentuser->user_id );
	$logger->debug("class type for the store:".get_class($currentuser));
//}

$perf_log_insert_id = Util::startEntryIntoPerformanceLogs('API', $resource, $method);
$request_type = 'API';

$return_type = ($query_params['format'] == "") ? 'xml' : $query_params['format'];
if ($return_type == 'json') {
	Header("Content-type: application/json; charset=utf-8");
} else {
	header("Content-type: text/xml; charset=utf-8");
}
header("X-Cap-RequestID: $uuid");
$input = file_get_contents("php://input");
$ws_handler = new WSHandler($url, $input);
$result = $ws_handler->processRequest();
ob_clean();
$result->buffer_output();

/*
ob_clean();
ob_start();
#$logger->info("***** Result: $result");
echo trim($result);


$logger->debug("Buffering: " . var_export(ob_get_status(true), true));

if (ob_get_length() > 0) {

	$logger->debug("flushing out content");
	ob_end_flush();
	ob_flush();
	flush();
}
*/
$logger->debug("Peak Memory used: ". (memory_get_peak_usage()/ 1000000)." MB");
$logger->debug("!!!!!OVER!!!!! $module/$action, ".$currentuser->getUsername());

//metrics
$pageTimer->stop();
$pageTime = $pageTimer->getTotalElapsedTime();

//for now taking pageTime as total time for an api call too, TODO: need to verify with piyush.
$gbl_item_total_time = $pageTime;
//Write to performance log
//rest of the metrics are now calculated within util
Util::storePerformanceInfo('API', $resource, $method, $pageTime, $memusage_start, $cpuload_start,  $perf_log_insert_id, array('cache_hit' => 0));
Util::saveApacheNote();

$logger->debug("writing perf logs to db ");
$headers = apache_request_headers();

ApiUtil::storeAPILogs($resource, $method, $currentorg->org_id, $currentuser->user_id, $pageTime, $uuid, $version, 
					base64_encode(json_encode($query_params)), $_SERVER['REQUEST_METHOD'], $headers['User-Agent']);

?>
