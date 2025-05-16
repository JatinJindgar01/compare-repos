<?
require_once("test/Context.php");
$_ENV["DH_WWW"] = "/home/capillary/coderoot/www";
$_ENV["DH_LIB"] = "/home/capillary/coderoot/lib";
$_ENV["DH_HOME"] = "/home/capillary/coderoot";
$cheetah_path = "/home/capillary/coderoot/www/cheetah";
set_include_path(get_include_path() .PATH_SEPARATOR . $cheetah_path );
require_once('common.php');
$GLOBALS['cfg'] = $cfg;
$logger = new ShopbookLogger();
$GLOBALS['logger'] = $logger;
include_once('helper/CacheFileManager.php');
include_once("apiController/ApiBaseController.php");
include_once("apiHelper/Errors.php");
include_once 'apiController/ApiInventoryController.php';
include_once 'apiHelper/APIWarning.php';
$cfg['mock_mode'] = true;

function apache_request_headers()
{
	return null;
}
?>
