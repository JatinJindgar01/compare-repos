<?

//Shutdown handler

function handleShutdown()
{
    $e = error_get_last();
    if($e["type"]===E_ERROR){
    print_r($e);
 #   print_r(get_included_files());
}
}
register_shutdown_function(handleShutdown);

// Set environment

$_ENV["DH_WWW"] = "/var/www/capillary";
$cheetah_path = "/var/www/capillary/cheetah";
$_GET['api_version'] = 'v1.1';
set_include_path(get_include_path() .PATH_SEPARATOR . $cheetah_path );

// Common includes
require_once('helper/async/AsyncClient.php');
require_once('helper/async/Job.php');
include_once("test/Context.php");
include_once('common.php');
$GLOBALS['cfg'] = $cfg;
$logger = new ShopbookLogger();
$GLOBALS['logger'] = $logger;
include_once('helper/CacheFileManager.php');
include_once("apiController/ApiBaseController.php");
include_once("apiHelper/Errors.php");
include_once("apiHelper/ApiUtil.php");
include_once 'apiController/ApiInventoryController.php';
include_once 'apiHelper/APIWarning.php';
include_once('test/mock/apache_fns.php');

// Set mock mode to true
$GLOBALS['cfg']['mock_mode'] = true;
class ApiTestBase extends PHPUnit_Framework_TestCase
{
	protected $logger, $currentuser, $currentorg;
    public function __construct()
    {
    	parent::__construct();
		global $logger;
        global $cfg;
        $this->logger = $logger;

    }
    
    /**
     * 
     * @param unknown_type $username - username of TILL
     * @param unknown_type $password - password will be in plain test
     */
    protected function login($username, $password)
    {
    	global  $currentuser, $currentorg;
    	$a = Auth::getInstance();
    	$a->login($username, $password);
    	
    	$currentuser = $a->getLoggedInUser();
    	$currentuser = StoreProfile::getById( $currentuser->user_id );
    	$currentorg = $currentuser->org;
    	$GLOBALS['currentuser'] = $currentuser;
    	$GLOBALS['currentorg'] = $currentorg;
    	$this->currentorg = $currentorg;
    	$this->currentuser = $currentuser;
    }
    
}
