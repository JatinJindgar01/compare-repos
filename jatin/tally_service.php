<?php

/*
 * 
 * All Tally requests go through this
 * Tally is unable to send the authentication headers currently
 * We will extract the store credentials and set it to the server php auth user and pw
 * The api service will take over from there on
 */

function sendErrorResponse($code) {
    	
	global $tally_logger, $logger;
	
	$api_status = array(
		'key' => getResponseErrorKey($code),
		'message' => getResponseErrorMessage($code)
	);
	$resp_data = array();
	$resp_data['api_status'] = $api_status;

	$logger = $tally_logger;
	$xml = new Xml();
	$res = $xml->serialize($resp_data);

	$tally_logger->debug("Response For Tally : $res");
	
    die($res);
}
$cheetah_path = $_ENV['DH_WWW']."/cheetah";
set_include_path(get_include_path() .PATH_SEPARATOR . $cheetah_path );
require_once('common.php');

$tally_logger = new ShopbookLogger();

//Try and extract the store credentials
$input_post_data = file_get_contents("php://input");
$tally_logger->debug("Tally Input data : $input_post_data");

//Verify the xml strucutre
if(Util::checkIfXMLisMalformed($input_post_data)){		
	//Respond will incorrect xml
	sendErrorResponse(ERR_RESPONSE_BAD_XML_STRUCTURE);
}

//XML is fine, extract the store credentials
$element = Xml::parse($input_post_data);
$store_username = "";
$store_pwd = "";
$store_credentials = $element->xpath('/root/store_credentials');
foreach ($store_credentials as $sc) 
{
	$store_username = $sc->store_username;
	$store_pwd = $sc->store_pwd; //Not the md5 hash as of now	
	break;
}

$tally_logger->debug("Uname : $store_username Pwd : $store_pwd");

//Check if username and password is sent
if(strlen($store_username) == 0 || strlen($store_pwd) == 0)
{
	//Both are required
	//Return will invalid store credentials data
	sendErrorResponse(ERR_RESPONSE_INVALID_CREDENTIALS);
}

//Set the username. Set the password as the md5 hash
$_SERVER['PHP_AUTH_USER'] = $store_username;
$_SERVER['PHP_AUTH_PW'] = md5($store_pwd);

//Include api service as the credentials have been set
require_once ('api_service.php');

?>
