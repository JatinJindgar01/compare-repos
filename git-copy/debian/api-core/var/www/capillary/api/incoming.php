<?php
$SHOPBOOK_PATH = $_ENV['DH_WWW'] . DIRECTORY_SEPARATOR . "cheetah";
set_include_path(get_include_path() .PATH_SEPARATOR . $SHOPBOOK_PATH); // . PATH_SEPARATOR . $MICROSITE_PATH
define('LOG4PHP_CONFIGURATION', "log4php.properties");
include($SHOPBOOK_PATH.'/common.php');

//Performance Log metrics
$memusage_start = memory_get_usage(true)/1000000; //MB
$cpuload_start = Util::ServerLoad(); //percentage

$logger = new ShopbookLogger();
$js = new JS();
$processTimer = new Timer("offline_process");
$masters_db = new Dbase('masters');
$users_db = new Dbase('users');
$campaigns_db = new Dbase('campaigns');	
$perf_log_insert_id = Util::startEntryIntoPerformanceLogs('SMS_IN', 'incoming', 'sms_in');
$from = urldecode($_REQUEST['msisdn']); Util::checkMobileNumber($from);
$to = urldecode($_REQUEST['to']); Util::checkMobileNumber($to);
//$time = $_GET['datetime'];
$refno = urldecode($_REQUEST['refno']);
$msg = urldecode($_REQUEST['msg']);
$whoami = urldecode($_REQUEST['whoami']);
$reply = "";
$remoteIp = $_SERVER['REMOTE_ADDR'];

$url_version = "1.0.0.0"; 

echo "$from $to $msg";

if ($from == "919874400500" || ($from != "919874400500" && stristr(substr($msg,0,4),"test") != false))
	die ("Nothing to do");

$logger->debug("Incoming SMS Request : ".$_SERVER['REQUEST_URI']);
$logger->debug("Incoming GET Request : ".print_r($_GET, true));
$logger->debug("Incoming POST Request : ".print_r($_POST, true));
$logger->debug("Incoming SERVER DUMP : ".print_r($_SERVER, true));
$logger->debug("Incoming REQUEST DUMP : ".print_r($_REQUEST, true));

if(!$from || !$to){
	//Write to performance log
	//rest of the metrics are now calculated within util
	$time = $processTimer->getTotalElapsedTime();
	Util::storePerformanceInfo('SMS_IN', $module, $action, $time, $memusage_start, $cpuload_start, $perf_log_insert_id, array());
	Util::die_with_log("From $from OR To : $to, cannot be empty");
}
	
$insert_log_sql = "INSERT INTO sms_in (`from`, `msg`, `host`, `reply`, `ref_no`, `time`) VALUES ('$from', '$msg', '$to', '', '$ref_no', NOW())";

$users_db->update($insert_log_sql);

$shortcode = $to;
$mobile = $from;
$message = $msg;

$response = explode(" ", $message);

$command = trim($response[0]);

if( strlen($command) > 0 )
	$incoming_sms_command = $command;
else
	$incoming_sms_command = $shortcode;

//if (count($response) > 1)
//	$command = $response[0]." ".$response[1];

if (count($response) > 1)
	$argument = substr ($message, strpos ($message, " ") + 1);
else
	$argument = '';

echo "\nCommand: $command\n";
$logger->debug("\nCommand: $command\n");
	
$sql = "SELECT action_id, org_id, whoami FROM `sms_mapping` WHERE `shortcode` = '$shortcode' AND `command` = '$command'";

$actions = $masters_db->query($sql);

if (!sizeof($actions) > 0) {
	$sql = "SELECT `campaign_id` FROM `assigned_keywords` WHERE `keyword` = '$command'";
	$campaign_id = $campaigns_db->query_scalar($sql);
	if ($campaign_id) {
		$location = "/shopbook-refactor/campaigns/register/".$mobile."/".$message;
		header( 'Location: '.$location ) ;
	}
	//Write to performance log
	//rest of the metrics are now calculated within util
	$time = $processTimer->getTotalElapsedTime();

	Util::storePerformanceInfo('SMS_IN', $module, $action, $time, $memusage_start, $cpuload_start, array(), false );
	Util::die_with_log("Invalid message====");
}

foreach($actions as $result)
{
        if(strlen($result['whoami']) > 0 && (strlen($whoami) == 0 || $whoami != $result['whoami'])){
                //Write to performance log
                //rest of the metrics are now calculated within util
                $time = $processTimer->getTotalElapsedTime();
                Util::storePerformanceInfo('SMS_IN', $module, $action, $time, $memusage_start, $cpuload_start, array());
                //Util::die_with_log("Invalid message==== . No 'whoami' provided when required");
        }

        $action_id = $result['action_id'];
        $org_id = $result['org_id'];

        $store_db = new Dbase('stores');
        $result = $store_db->query_firstrow("SELECT `module_id`, `code` FROM `actions` WHERE `id` = '$action_id'");
        $module_id = $result['module_id'];
        $action = $result['code'];

        $module = $store_db->query_scalar("SELECT `code` FROM `modules` WHERE `id` = '$module_id'");


        $arguments = array ($mobile, $org_id, $argument);
        $currentorg = new OrgProfile ($org_id);

        if( ( strcasecmp( "optout", $action ) != 0 ) && ( strcasecmp( "optin", $action ) != 0 ) ){

        //Set based on a default username for the org...
		$cfm=new ConfigManager();
		$store_id = $cfm->getKey(CONF_LOYALTY_OFFLINE_REDEMPTION_DEFAULT_STORE);
		$store_id=!empty($store_id)?$store_id:false;
		
        //if storeid is not set, send a mail to support and die
        if($store_id == false){

                $error_subject = "CONF_LOYALTY_OFFLINE_REDEMPTION_DEFAULT_STORE not set in configuration for ".$currentorg->name.". Offline Redemption invalid";			
                $error_body = "Please set OFFLINE_REDEMPTION_DEFAULT_STORE for the organisation in configuration. Offline redemption unsuccessful.

                        from  : $from
                        to    : $to
                        refno : $refno
                        msg   : $msg
                        remoteIp : $remoteIp

                        ";
                $recepient = $currentorg->getConfigurationValue(CONF_CLIENT_ERROR_REPORTING_EMAIL, 'support@capillary.co.in');
                $recepients = explode(',', $recepient);
                if(count($recepients) > 1)
                        $recepients_cc = array_splice($recepients, 1);
                else
                        $recepients_cc = null;

                $recepient = $recepients[0];

                Util::sendEmail($recepient, $error_subject, $error_body, $org_id, $recepients_cc);

                //Write to performance log
                //rest of the metrics are now calculated within util
                $time = $processTimer->getTotalElapsedTime();
                Util::storePerformanceInfo('SMS_IN', $module, $action, $time, $memusage_start, $cpuload_start, array());

                Util::die_with_log("Unable to perform offline redemption.");
        }



        $currentuser = StoreProfile::getById($store_id);
        }

        echo @"<pre>Running script:
                User      : $currentuser->username
                Org       : $currentorg->name
                Module    : $module
                Action    : $action
                Arguments : " . print_r($arguments, true)."\n</pre>";

        $logger->debug("<pre>Running script:
                        User      : $currentuser->username
                        Org       : $currentorg->name
                        Module    : $module
                        Action    : $action
                        Arguments : " . print_r($arguments, true)."\n</pre>");

        $m_class = $module."Module";
        $selected_module = new $m_class();

        $processTimer->start();
        $selected_module->route($action, $arguments);
        $layout = $selected_module->getLayout();

        $processTimer->stop();

# Generate Results Page
        ob_start();
        include_once 'module/template/'.$layout;
        echo $logger->fetchLog();
        $output = ob_get_clean();
        file_put_contents("output.html", $output);
        $time = $processTimer->getTotalElapsedTime();
        echo("Success. Time Taken: $time ms");

        //Write to performance log
        //rest of the metrics are now calculated within util
        Util::storePerformanceInfo('SMS_IN', $module, $action, $time, $memusage_start, $cpuload_start, $perf_log_insert_id, array());
}

$output = ob_get_clean();

global $dhiresh_peter_england;
echo $dhiresh_peter_england;
exit(0);
?>
