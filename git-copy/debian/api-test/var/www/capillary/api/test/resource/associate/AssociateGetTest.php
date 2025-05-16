<?
require_once('test/resource/associate/ApiAssociateResourceTestBase.php');

class AssociateGetTest extends ApiAssociateResourceTestBase
{
	private $currentConfigs = array();
	
	public function __construct(){
		
		parent::__construct();
	}

	public function testBadAssociateGet(){
        $query_params = array('id' => '-1');
        $response = $this->associateResourceObj->process("v1.1", "get", null, $query_params,"GET");
        $this->assertEquals('500', $response['status']['code']);
    }

    public function testAssociateGet1(){

        $query_params = array(
            'id' => '328' );

        $response = $this->associateResourceObj->process("v1.1", "get", null, $query_params,"GET");
        $this->assertEquals('200', $response['status']['code']);
    }

    public function testAssociateGet2(){

        $query_params = array(
            'id' => '328,326' );

        $response = $this->associateResourceObj->process("v1.1", "get", null, $query_params,"GET");
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
