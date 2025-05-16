<?
require_once('test/resource/coupon/ApiCouponResourceTestBase.php');

class IsRedeemableTest extends ApiCouponResourceTestBase
{
    private $currentConfigs = array();

    public function __construct(){

        parent::__construct();
    }

    public function testCouponIsRedeemableBad(){

        $query_params = array(
            'mobile' => '21344906' );

        $response = $this->couponResourceObj->process("v1.1", "isredeemable", null, $query_params,"GET");
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
