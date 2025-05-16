<?
require_once('test/resource/feedback/ApiFeedbackResourceTestBase.php');

class FeedbackGetTest extends ApiFeedbackResourceTestBase
{
	private $currentConfigs = array();
	
	public function __construct(){
		
		parent::__construct();
	}

	public function testBadFeedbackGet(){
        $response = $this->feedbackResourceObj->process("v1", "get", null, null,"GET");
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
