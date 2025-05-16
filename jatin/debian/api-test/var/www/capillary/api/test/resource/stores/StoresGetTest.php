<?
require_once('test/resource/stores/ApiStoresResourceTestBase.php');

class StoresGetTest extends ApiStoresResourceTestBase
{
	private $currentConfigs = array();
	
	public function __construct(){
		
		parent::__construct();
	}

	public function testOrganizationGet(){
        $store = array(
            'code' => 'store123'
        );

        $response = $this->storeResourceObj->process('v1.1', 'get', null, $store, 'GET');
        $this->assertEquals('200', $response['status']['code']);
    }
	
	public function setUp(){
		$this->login( "till.005", "123" );
		parent::setUp();
	}
	
	public function tearDown(){
	}
}
?>
