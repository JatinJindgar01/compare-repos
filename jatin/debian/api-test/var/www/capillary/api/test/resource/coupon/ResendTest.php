<?
require_once('test/resource/coupon/ApiCouponResourceTestBase.php');

class ResendTest extends ApiCouponResourceTestBase
{
    private $currentConfigs = array();

    public function __construct(){

        parent::__construct();
    }

    public function testCouponResendByVoucherId1(){

        $query_params = array(
            'id' => '21344906' );

        $response = $this->couponResourceObj->process("v1.1", "resend", null, $query_params,"GET");
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
