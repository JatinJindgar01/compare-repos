<?php
require_once('common.php');
$logger = new ShopbookLogger();

$shortcode = $_POST['shortcode'];
$mobile = $_POST['mobile'];
$message = $_POST['message'];

$response = explode(" ", $message);

$command = $response[0];
$argument = $response[1];

$sql = "SELECT action_id, org_id FROM `sms_mapping` WHERE `shortcode` = '$shortcode' AND `command` = '$command'";
$db = new Dbase ("users");
$result = $db->query_firstrow($sql);
if (!$result) {
	$sql = "SELECT `campaign_id` FROM `assigned_keywords` WHERE `keyword` = '$command'";
	$db = new Dbase("campaigns");
	$campaign_id = $db->query_scalar($sql);
	if ($campaign_id) {
		$location = "http://localhost/shopbook/campaigns/register/".$mobile."/".$message;
		header( 'Location: '.$location ) ;
	}
	die ("Invalid message.");
}

$action_id = $result['action_id'];
$org_id = $result['org_id'];

$sql = "SELECT `module_id`, `code` FROM `actions` WHERE `id` = '$action_id'";
$db = new Dbase ("users");
$result = $db->query_firstrow($sql);
$module_id = $result['module_id'];
$action = $result['code'];

$module = $db->query_scalar ("SELECT `code` FROM `modules` WHERE `id` = '$module_id'");



$location =  "http://localhost/shopbook/".$module."/".$action."/".$mobile."/".$org_id."/".$argument;
header( 'Location: '.$location ) ;

?>
