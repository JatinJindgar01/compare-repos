<?php

ini_set("zend.enable_gc", 0);

ob_start();
define ('REALM', "Capillary Protected Area");
define ('PASSWORD_ERR', 1001);
define ('API_KEY_ERR', 1010);
define ('INVALID_API_CALL', 1020);
define ('INVALID_MAC_ID', 1030);
define('PROFILE_MODE', 1);
define('CHUNK_SIZE', 10*1024*1024); // Size (in bytes) of tiles chunk
define('API_AUTH_KEY_HEADER','X-CAP-API-AUTH-KEY');
define('API_AUTH_ORG_ID','X-CAP-API-AUTH-ORG-ID');
define('API_CLIENT_SIGNATURE','X-CAP-CLIENT-SIGNATURE');

//require_once('common.php');

/** HIGHER MEMORY LIMIT FOR THE API **/
ini_set('memory_limit', '500M');
error_reporting(E_ALL & ~E_NOTICE );

$cheetah_path = $_ENV['DH_WWW']."/cheetah";
set_include_path(get_include_path() .PATH_SEPARATOR . $cheetah_path );
require_once('common.php');
include_once('helper/CacheFileManager.php');

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

    ob_clean();
    die($res);
}


// Read a file and display its content chunk by chunk
function readfile_chunked($filename, $retbytes = TRUE) {
    
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


function echobig($string, $bufferSize = 8192) {
	$splitString = str_split($string, $bufferSize);

	foreach($splitString as $chunk) {
		echo $chunk;
	}
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

global $gbl_item_count, $gbl_item_total_time, $gbl_item_status_codes, $uuid;

$gbl_item_count = 0;
$gbl_item_total_time = 0;
$gbl_item_status_codes = null;

$uuid = Util::getUUID();
#$_SERVER['UNIQUE_ID'] = $uuid;
$logger = new ShopbookLogger();
$url = isset($_GET['url']) ? $_GET['url'] : "";
$logger->debug("Called URL: ".$url);
$urlParser = new UrlParser();
UrlParser::setCallRequestType( 'api' );

$module = $urlParser->getModule();
$action =  $urlParser->getAction();
$url_version = '1.0.0.0';

$memusage_start = memory_get_usage(true)/1000000; //MB
$cpuload_start = 0;//Util::ServerLoad(); //percentage

$logger->debug("Init Memory: $memory_get_usage, init cpu: $cpuload_start");

$pageTimer = new Timer('page_timer');
$pageTimer->start();

if ($cfg['servertype'] == 'dev') {
	$logger->enabled = true;
}

$user = stripslashes($_SERVER['PHP_AUTH_USER']);
$pwd = stripslashes($_SERVER['PHP_AUTH_PW']);

$logger->debug("username $user passwd: $pwd\n");
$a = Auth::getInstance();
$headers = apache_request_headers();
$auth_key=isset($headers[API_AUTH_KEY_HEADER])?trim($headers[API_AUTH_KEY_HEADER]):'';

if(!empty($auth_key))
	$key_based_auth=true;


//add cc cookie flow
//go inside only when not logged in
if( !$a->isLoggedIn() ){
	if ( $a->loginForApi($user,  $pwd, $auth_key) < 0 ){
	
		showError(PASSWORD_ERR, "Wrong username or password");
	}
}


/**
We do not allow admin users to login through the api service
**/
$currentuser = $a->getLoggedInUser(); unset($user); unset($pwd);
if( $currentuser->getType() == 'ADMIN_USER' ){

	showError(PASSWORD_ERR, "Wrong username or password");
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
 */
if($urlParser->isLegacyUrl()){
	$org_id = $currentorg->org_id;
	$logger->debug("Switching the current orgprofile object");
	$currentorg = null;
	$currentorg = new OrgProfile($org_id);
	$logger->debug("class type: " . get_class($currentorg));

	$currentuser = StoreProfile::getById( $currentuser->user_id );
	$logger->debug("class type for the store:".get_class($currentuser));
}

/**
Whether to check for an api key or not
is configurable for an org. Going forward this 
will be true by default
**/


$api_key = $_GET['api_key'];
if($currentorg->getConfigurationValue(CONF_CLIENT_API_KEY_REQUIRED, false)){
	if (!$a->check_api_key($currentuser->org->org_id, $api_key)) {
		//if the configuration doesn't require api key
		$required = $currentorg->getConfigurationValue(CONF_CLIENT_API_KEY_REQUIRED, false);
		if ($required) {
			showError(API_KEY_ERR, "Invalid api key");
			//showError(ERR_RESPONSE_INVALID_API_KEY, "Invalid api key");
		}
	
	}
}
unset($api_key);


//global string for maintaining the time breakup
$time_breakup = "";

//=============================================================================
//checking for the counter and its mac address
$counter_id = isset($_COOKIE['counter_id']) ? $_COOKIE['counter_id'] : -1;
$counter_mac_address = isset($_COOKIE['mac_id']) ? $_COOKIE['mac_id'] : -1;
$logger->debug("Cookies : ".print_r($_COOKIE, true));

if( ($counter_mac_address != -1) && (strlen($counter_mac_address)> 0) ){	
	if($currentorg->getConfigurationValue(CONF_CLIENT_MAC_ADDRESS_CHECKING_ENABLED, false)
	&& !$a->checkIfMacAddressIsValid($currentorg->org_id, $currentuser->user_id, $counter_mac_address))
	{
		showError(INVALID_MAC_ID, "MAC id does not match . Counter ID $counter_id. MAC addr : $counter_mac_address");
	}
}
//=============================================================================

$perf_log_insert_id = Util::startEntryIntoPerformanceLogs('API', $module, $action);
$request_type = 'API';
$urlParser->debugMsg();

unset($a);

/**
 * This contains a list of valid modules/actions pairs that can be used with api.
 * Just added additional checking so that the user can not access any arbitrary function
 * Tags that can be used against the actions are:
 *  - is_cached ==> Specify if the output should be cahced
 *  - cache_time ==> Time for which it should be cached. In seconds
 *  - serialize_with_low_memory ==> Use the low memory XML Serializer
 *  - data_serialized_to_file => in some cases we can't fetch all the data from the database due to memory constraints 
 *             , we fetch them in batches and keep on storing them to a file. 
 *             If CONF_CLIENT_DISABLE_CONTENT_CACHING is FALSE then only this will be used, else data will be kept in memory. 
 *             If data is to be cached, 
 *             value of data['xml_file_name'] will be name of the file to be used for caching 
 */
$valid_api_actions = array (
	'auth' => array(
		'credentials', 
		'mobilelogin'
           ),
	'administration' =>
        array(
            'configuration', 
            'uploadclientlogfile',
            'dataprovidersfile',
            'printertemplatefile', 
            'storereport', 
            'rulepackages', 
            'getlatestruleinfo',
            'inventorydump' => array('is_cached' => true, 'cache_time' => 259200, 'data_serialized_to_file' => true, 'cache-compressed' => true),
            'getservercurrenttime', 
            'reportclienterror', 
            'integrationoutputtemplatefile',
            'downloadfilebyid', 
            'sendemail', 
            'uploadsamplecontentfiles',
            'uploaddataproviderfiles', 
            'getdataprovidersfororg', 
            'getsamplecontentsfororg',
            'getclientcronentries', 
            'legopropertiesfile', 
            'getstoretasks' => array('is_cached' => true, 'cache_time' => 64800, 'cache-compressed'=>false, 'data_serialized_to_file'=>false), 
            'getstoretasksentries', 
            'updatestoretaskentrystatus', 
            'getpurchasedfeatures',
            'scanimportmondriandataandnotifyconquest',
            'tillsunderstoreserver',
            'importdata', 
            'sendsms'
        ),
	'campaigns' => array
        (
		'voucherseries' => array('is_cached' => true, 'cache_time' => 3600, 'cache-compressed' => true), 
		'vouchers' => array('is_cached' => true, 'cache_time' => 64800, 'cache-compressed' => true, 'data_serialized_to_file' => true),
		'vouchersdelta' => array('is_cached' => true, 'cache_time' => 64800, 'cache-compressed' => true, 'data_serialized_to_file' => true),  
		'issuevoucher', 
		'issuedvsvoucher', 
		'issuedvsvouchernew',
		'fetchvouchers', 
		'redeemvoucher', 
		'isvchredeemable', 
		'checkvalidationrequired',
		'campaignreferrals', 
		'campaignreferralsnew', 
		'notissuedoffers', 
		'updateredemptiondetails', 
		'redeemedvoucherdetails', 
		'resendvoucher', 
		'redeemvouchernew'
        ),
	'loyalty' => array(
		'getloyaltycustomerdetails',
		'getcustomers' => array('is_cached' => true, 'cache_time' => 86400, 'cache-compressed' => true, 'data_serialized_to_file' => true), # 18 hours
		'getbillsandredemptions',
		'addbills', 
		'issuevoucherfromdefaultseries', 
		'fetchcustomerinfo', 
		'updatecustomerinfo', 
		'getcustomerinfo',
        'register',
        'redeem', 
        'redeempointsoffline', 
		'getredemptionvch', 
		'checkvalidationcode', 
		'checkvalidationcodenew', 
		'issuevalidationcode', 
		'issuevalidationcodenew', 
		'cancel', 
		'customernotinterested',
		'loyaltytracker', 
		'getcustomfields', 
		'getcustomfieldsdata' => array('is_cached' => true, 'cache_time' => 86400, 'cache-compressed' => true,'data_serialized_to_file' => true), # 18 hours, 
	    'unsubscribe', 
	    'purchasehistory', 
	    'mobilechange', 
	    'returnbill', 
	    'getfraudusers', 
	    'masterconfigurationinfo'=> array(
        								'is_cached' => true, 'cache_time' => 1800, 'cache-compressed' => false, 
        								'data_serialized_to_file'=>false, 'store_cache'=>true, 'mem_cache_enabled' => true
                                        ),
      'updatepointsredemptiondetails',
	  /* 'getcustomerclusterinfo' => array('is_cached' => true, 'cache_time' => 259200, 'data_serialized_to_file' => true, 'cache-compressed' => true), 'getstoreattributes', */
	  'getcustomersdelta' => array('is_cached' => true, 'cache_time' => 64800, 'cache-compressed' => true, 'data_serialized_to_file' => true), # 2 hours
      'getcustomfieldsdatadelta' => array('is_cached' => true, 'cache_time' => 64800, 'cache-compressed' => true,'data_serialized_to_file' => true),
	  'getmissedcallnumbers', 
	  'getcountrydetails',
	  'mlmrefer', 
	  'mlmusers' => array('is_cached' => true, 'cache_time' => 7200),
	  'fetchreferrals',
      'getcustomerbymobile',
      'purchasehistorymobile', 
      'uploadperformanceinfo', 
      'sendvoucherssmsforcustomer', 
      'addcustomerweb',
      'getnumberofregistrationsandbillsbystore', 
      'getloyaltydetailsforcustomer', 
      'generatevalidationcode', 
      'verifycode', 
      'getpurchasehistoryforcustomer', 
      'getcustomerprofile', 
      'addbillswrapper',
      'processexternalrequest', 
      'getstorereportbydaterange', 
      'getmissedcallstatus', 
      'getgiftcardinfo', 
      'rechargegiftcard', 
      'redeemgiftcard', 
      'issuegiftcard'
      ),
	'store' => array
      ('storecustomerfeedback', 
       'ticketdata', 
       'getissue', 
       'getissuebyticketcode',
       'addissue',
       'customerfeedback',
       'getfeedback'
      ),
	'msging' => array ('apiaction'),
	'orgadmin' => array('getcustomfields'), 
	'test' => array('apitest', 'xmlcheck', 'pulkittest', 'testregisterraymond', 'testingconfiguration', 'addconfiguration', 'updateconfiguration',
        			'addconfigurationvalue', 'updateconfigurationvalue', 'testconfiguration', 'searchmodulekeys'),
    'external' => array( 
    					'addcustomer', 'editcustomer', 'loyaltydetails', 'checkin', 'getphotos', 'uploadphoto',
    					'issuestorevoucher', 'stores', 'productdetails', 'getnews', 'getvideo', 'getmainmenuphotos',
        				'getfacebookcheckins', 'addfamily', 'addmemberstofamily', 'getfamilydetails', 'transferpoints',
        				'getfamilies','getfamilymembers', 'removememberfromfamily', 'fetchhariyalifiles', 'cleanhariyalifiles',
                                        'getcustomervisitfrequency', 'getorgmetadata', 'getclientnotesforcustomer', 'getrecommendationsforcustomer',        
                                        'getpreferencesforcustomer', 'getsocialinfoforcustomer' 
     ),
    'clientreports' => array('getreportsmetadata', 'getreports', 'setreportsfororg')     
);


if ($valid_api_actions[$module] == false ||
!(in_array(strtolower($action), $valid_api_actions[$module]) || $valid_api_actions[$module][strtolower($action)])
) {
	$logger->debug("Valid api calls: "+print_r($valid_api_actions, true));
	showError(INVALID_API_CALL, "Invalid Api Call");
	//showError(ERR_RESPONSE_INVALID_API_CALL, "Invalid Api Call");
	//Util::die_with_log(INVALID_API_CALL.": Invalid api call");
}

$data = array();

if ($urlParser->getReturnType() == 'json') {
	Header("Content-type: application/x-javascript");
} else if ($urlParser->getReturnType() == 'xml') {
	header('Content-type: text/xml; charset=utf-8');
}else if ($urlParser->getReturnType() == 'csv'){
       	header('Content-type: text/csv');
}else if ($urlParser->getReturnType() == 'app'){
       	header('Content-type: text/xml; charset=utf-8');
}
header("X-Cap-RequestID: $uuid");

$api_status = array(
	'key' => getResponseErrorKey(ERR_RESPONSE_SUCCESS),
	'message' => getResponseErrorMessage(ERR_RESPONSE_SUCCESS) 
);		
$data['api_status'] = $api_status; //default success

/**
 * Check if the page should be cached and if the cache value exists for this organization and is valid within the caching duration.
 * If yes, return the cache value.
 */

$module = strtolower($module);
$action = strtolower($action);
$cache_time = (isset($valid_api_actions[$module][$action]['cache_time'])) ? $valid_api_actions[$module][$action]['cache_time'] : 0;
$store_cache = (isset($valid_api_actions[$module][$action]['store_cache']))? $valid_api_actions[$module][$action]['store_cache'] : false;
$file_ext = (isset($valid_api_actions[$module][$action]['cache-compressed']) && $valid_api_actions[$module][$action]['cache-compressed'] == true) ? 'xml.gz' : 'xml';

$logger->debug("module: $module action: $action");

$file_manager = new CacheFileManager($module, $action, $cache_time, $store_cache, $file_ext);

$logger->info("cache file manager instantiated");

/**
 
$bg_sync_enabled = $currentorg->getConfigurationValue( CONF_CLIENT_BACKGROUND_SYNCER_ENABLED, false );
if( $bg_sync_enabled ){

	$sync_check_apis = array( 'masterconfigurationinfo' );
}else{
	
	$sync_check_apis = array(  );
}

$mem_cache_mgr = MemcacheMgr::getInstance();
$ttl = CacheKeysTTL::$bgSync;
$mem_cache_key = CacheKeysPrefix::$bgSync.'_'.'_LOYALTY_MASTER_CONFIG_INFO_ORG_ID_'.$org_id;
 
if( $valid_api_actions[$module][$action]['store_cache'] ){

	$mem_cache_key .= '_store_id_'.$currentuser->user_id;
}

*/
 
if( $valid_api_actions[$module][$action]['mem_cache_enabled'] ){

	try{
		$mem_cache_mgr = MemcacheMgr::getInstance();
		$mem_cache_key = $file_manager->getMemcacheKey();
		$cached_xml = $mem_cache_mgr->get( $mem_cache_key );
		$logger->info( 'Returning From MemCache' );
		
		ob_start();
		
		echo $cached_xml;
		
		ob_end_flush();
		ob_flush();
		flush();

		$logger->info("$module $action served from memcache for $currentuser->user_id");
		
		exit();
	}catch( Exception $e ){
		
		$logger->info( 'key '.$mem_cache_key .' does not exists in system ' );
	}
}

$file_path = $file_manager->getFilePath();
$cache_page_contents = false;
if($action=='vouchers' && $urlParser->getParams())
{
	 //unfortunately same module_action is used for getting individual voucher, which should not be cached
	 //such requests have params with them. 
}
else if ($valid_api_actions[$module][$action]['is_cached'] && !$currentorg->getConfigurationValue(CONF_CLIENT_DISABLE_CONTENT_CACHING, false)) 
{
    $cache_page_contents = true;
    $cache_compressed_content = $valid_api_actions[$module][$action]['cache-compressed'];

    if($file_manager->isFileCreated())
    {
        try{
            while($file_manager->isFileLocked()){
                $logger->info("File is locked..sleeping for 10 seconds");
                sleep(10);
            }
            $logger->info("File is unlocked, I am tring to read it");
            $file_path = $file_manager->fetchFile();

            $logger->info("File is available at $file_path");

            if ($cache_compressed_content) {
                $HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
                if( headers_sent() ){
                    $logger->info("Headers have already been sent");			
                    $encoding = false;
                }else if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false ){
                    $logger->info("Sending x-gzip header");
                    $encoding = 'x-gzip';
                }else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false ){
                    $logger->info("Sending gzip header");
                    $encoding = 'gzip';
                }else
                {
                    $encoding = 'gzip';//client code can handle gzip data even though it was not present in teh request header
                    $logger->debug("client $currentuser->username does not accept compressed data  $HTTP_ACCEPT_ENCODING");
                }
                
                ob_clean();
                header('Content-Encoding: '.$encoding);
                //@readfile($compressed_cachefile);
                @readfile_chunked($file_path, true);

            } else{

                if( $valid_api_actions[$module][$action]['mem_cache_enabled'] ){
                    $file_manager->storeInMemcache($file_path);
                }

                @readfile_chunked( $file_path, true );
            }

            //metrics
            $pageTimer->stop();
            $pageTime = $pageTimer->getTotalElapsedTime();
            //Write to performance log
            //rest of the metrics are now calculated within util
            Util::storePerformanceInfo('API', $module, $action, $pageTime, $memusage_start, $cpuload_start, $perf_log_insert_id, array('cache_hit' => 1));

            exit();
        }catch(Exception $e){
            $logger->error("Error in reading file from S3/disk: " . $e->getMessage());
            $logger->error("Normal execution will flow");    
        }     
    }
    
    $logger->info("File has not been created till now, Making a create file entry into db");
    $file_manager->createCacheFileEntry();
    $logger->info("Lock taken for the file in db");
}


try {
	$module_class = $module.'Module';

	//$m_class = $action['module'].'Module';
	$obj = new $module_class();
	//Take in the XML uploaded data
	$input = file_get_contents("php://input");
	$logger->debug("***API*** Calling $module/$action (username: $currentuser->username ) with args:".print_r($urlParser->getParams(), true));
	$obj->setRawInput($input);

	if ($obj == false) throw new InvalidInvocationExeption("Wrong class: $module_class");
	$args = $urlParser->getParams();
	$data = array();
	$api_status = array(
		'key' => getResponseErrorKey(ERR_RESPONSE_SUCCESS),
		'message' => getResponseErrorMessage(ERR_RESPONSE_SUCCESS) 
	);

	$data['api_status'] = $api_status; //default success
	
	$cachefile_path = $file_manager->getFilePath(false) . '.cachexml' ;
 	$data['xml_file_name'] = $cachefile_path ; // to be used when caching is done along with data reading
	$obj->routeApi($action, $args);
	unset($data['xml_file_name']);// Need to clear as it will end up in the final xml

} catch (InvalidInvocationException $e) {

	//TODO: Log this exception
	$logger->error("Invalid number of parameters or invalid action name: "+$e->getMessage());
    showError(INVALID_API_CALL, "Invalid Api Call");

}
$output_till_now = ob_get_clean();
$logger->debug("Output: <pre>$output_till_now</pre>");
$data['log'] = $logger->fetchLog();

ob_start();

//now we need to send back the $data in a suitable format
//default if XML
if ($urlParser->getReturnType() == 'json') {
	$json_data = json_encode($data);
	return $json_data;
}else if($urlParser->getReturnType() == 'csv'){

       $data['log'] = "";
       $csv = new Spreadsheet();
       
       foreach($data as $d){
               if(is_a($d,Table))
                       $csv->loadFromTable($d);        
       }

       $filename = $action;
       $csv->MakeClientCsv($filename);

}else if ($urlParser->getReturnType() == 'xml') {
	$data['log'] = "";
	unset( $data['log'] );
	$xml = new Xml();

	if($valid_api_actions[$module][$action]['data_serialized_to_file'] && 
            $cache_page_contents)
	{

		// for now Customer data is cached into file
		// it is read in te batches and appended to a file
		// file name can be found from data['xml_file_name']

	}
	else if ($valid_api_actions[$module][$action]['serialize_with_low_memory']) {
		$xml->serializeLowMemory($data, $res, true);
		echo $res;
	} else {
		$res= $xml->serialize($data, true);
		echo $res;
	}
	$logger->debug("===~~~DONE~~~===");

} else if ($urlParser->getReturnType() == 'html' && $cfg['servertype'] == 'dev') {
	unset($data['log']);
	print_r($data);
	echo "<h2>LOG:\n</h2>";
	echo $logger->fetchLog();
} else if ($urlParser->getReturnType() == 'app') {
	
	echo $data['external'];
}

if ($cache_page_contents)
{
    // Now the script has run, generate a new cache file
    $logger->info("Page contents have to be cached... value of cache_compressed_content: $cache_compressed_content");

    if($cache_compressed_content) //if contents are to be compressed
    {
        //dealing separately because only comressed file should be sent out for this case
        //it will same as what happens when file is found in the cache
        if($valid_api_actions[$module][$action]['data_serialized_to_file'] )
        {
            $gz = gzopen($file_path,'w9');
            $fh=fopen($cachefile_path, 'r');

            $logger->debug("Writing the gz file");	

            while(!feof($fh))
            {
                gzwrite($gz,fgets($fh,8192));
            }
            //$logger->debug("Cache Compressed File PUT status : ".gzwrite($gz, $file_contents));
            gzclose($gz);
	
            $logger->debug("Writing gz file complete");

            // now rest of the formalities of sending out this compressed file and exit after performance logging
            $HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
            if( headers_sent() ){
                $encoding = false;
            }else if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false ){
                $encoding = 'x-gzip';
            }
            else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false ){
                $encoding = 'gzip';
            }
            else{
                $encoding = false;
            }

            ob_clean();
            header('Content-Encoding: '.$encoding);
             
            $logger->debug("Pushing file in S3");
             
            $file_manager->storeFile($file_path);

            $logger->info("Deleting the temporary xml file we are creating: $cachefile_path");
            @unlink($cachefile_path);	
        
            $logger->debug("$cachefile_path deleted...Unlocking file from in db");
            $file_manager->unlockFile();
            $logger->debug("File unlocked");

            $logger->debug("Reading the chunked file for transfer: $file_path");

            @readfile_chunked($file_path, true);

            $logger->debug("File transfer is complete..winding up");

            $pageTimer->stop();
            $pageTime = $pageTimer->getTotalElapsedTime();
            $memusage_end = 0;//memory_get_usage()/1000000; //MB
            $memusage_peak = 0;//memory_get_peak_usage()/1000000; //MB
            $mem_used = array();
            $mem_used['start'] = $memusage_start;
            $mem_used['end'] = $memusage_end;
            $mem_used['peak'] = $memusage_peak;
            $cpuload_end = 0;//Util::ServerLoad(); //percentage
            $cpu_load = array();
            $cpu_load['start'] = $cpuload_start;
            $cpu_load['end'] = $cpuload_end;
            //Write to performance log
            //rest of the metrics are now calculated within util
            Util::storePerformanceInfo('API', $module, $action, $pageTime, $memusage_start, $cpuload_start, $perf_log_insert_id, array('cache_hit' => 0));

            exit();

        }
        else //contents are compressed but not serialized to a file
        {
            $file_contents = ob_get_contents();
            $logger->info("Writing compressed cache file to $file_path");
            $gz = gzopen($file_path,'w9');
            $logger->debug("Cache Compressed File PUT status : ".gzwrite($gz, $file_contents));
            gzclose($gz);
        }
    }else  //  matching if of compressed_cache_contents
    {
        //$logger->info("Caching contents : ".file_put_contents($cachefile, $file_contents));
        $logger->info("Writing cache file to $file_path");
        $logger->debug("writing cache file $file_path");
        $fh = fopen($file_path, 'w');
        $file_contents = ob_get_contents();
        fwrite($fh, $file_contents);
        fclose($fh);

        if( $valid_api_actions[$module][$action]['mem_cache_enabled'] ){

            try{
                $logger->info("Putting file in memcached");
                $mem_cache_mgr->set( $mem_cache_key, $file_contents, $cache_time );
            }catch( Exception $e ){
                $logger->debug( 'Counld Not Set The Key With Key :'.$mem_cache_key);
            }
        }
    }

    //unlock the write lock
    //Since the file has already been written to disk, we can unlock as multiple 
    //read can happen simultaneously
    $logger->debug("Unlocking file");
    $file_manager->unlockFile();
    $file_manager->storeFile($file_path);

    //die("wrote contents");
    
}else{ //contents are not cached

    $logger->debug("File contents are not compressed");
    $file_manager->unlockFile();
    if( $valid_api_actions[$module][$action]['mem_cache_enabled'] ){

        try{
            	
            $mem_cached_contents = ob_get_contents();
            $mem_cache_mgr = MemcacheMgr::getInstance();
            $mem_cache_mgr->set( $mem_cache_key, $mem_cached_contents, $cache_time );
        }catch( Exception $e ){

            $logger->debug( 'Counld Not Set The Key With Key :'.$mem_cache_key);
        }
    }
}

$logger->debug("Buffering: ".var_export(ob_get_status(true), true));
$logger->info("All stuff is done, flushing out the contents of the buffer");

if (ob_get_length() > 0) {

	$logger->debug("starting flush as final");

	ob_end_flush();
	ob_flush();
	flush();
}
$logger->debug("Peak Memory used: ".(memory_get_peak_usage()/ 1000000)." MB");
$logger->debug("!!!!!OVER!!!!! $module/$action, ".$currentuser->getUsername());

//metrics
$pageTimer->stop();
$pageTime = $pageTimer->getTotalElapsedTime();

//for now taking pageTime as total time for an api call too, TODO: need to verify with piyush.
$gbl_item_total_time = $pageTime;
//Write to performance log
//rest of the metrics are now calculated within util
Util::storePerformanceInfo('API', $module, $action, $pageTime, $memusage_start, $cpuload_start,  $perf_log_insert_id, array('cache_hit' => 0));
Util::saveApacheNote();
?>
