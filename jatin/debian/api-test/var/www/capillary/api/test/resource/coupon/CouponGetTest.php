<?
require_once('test/resource/coupon/ApiCouponResourceTestBase.php');

class CouponGetTest extends ApiCouponResourceTestBase
{
	private $currentConfigs = array();
	
	public function __construct(){
		
		parent::__construct();
	}

	public function testBadCouponGet(){
        $query_params = array('id' => '-1');
        $response = $this->couponResourceObj->process("v1.1", "get", null, $query_params,"GET");
        $this->assertEquals('500', $response['status']['code']);
    }

    public function testCouponGet1(){

        $query_params = array(
            'id' => '21344906' );

        $response = $this->couponResourceObj->process("v1.1", "get", null, $query_params,"GET");
        $this->assertEquals('200', $response['status']['code']);
    }

    public function testCouponGet2(){

        $query_params = array(
            'id' => '21344906,21344933' );

        $response = $this->couponResourceObj->process("v1.1", "get", null, $query_params,"GET");
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
