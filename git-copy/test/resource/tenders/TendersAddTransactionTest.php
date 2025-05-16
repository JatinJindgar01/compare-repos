<?
require_once('test/resource/tenders/ApiTendersResourceTestBase.php');

class TendersAddTransactionTest extends ApiTendersResourceTestBase
{
	private $currentConfigs = array();
	
	public function __construct(){
		
		parent::__construct();
	}


    public function testAddTransactionWithAttrs(){
    	
    	// get a canonocal name
    	$response = $this->tendersResourceObj->process("v1.1", "get", null, array("attributes"=>1, "options"=>1),"GET");
    	$this->assertEquals('200', $response['status']['code']);
    	$k = 0; 
    	for($i =0 ; $i < 20 && $response['tenders']['tender'][0] ; $i++)
    	{
    		if($response['tenders']['tender'][0]["attributes"]["count"] ==  0 )
    			$k = array_shift($response['tenders']['tender']);
    		else
    			break;
    	}
    	
    	$this->assertNotNull($response['tenders']['tender'][0]['name']);
    	$this->assertEquals($response['tenders']['count']-$i, count($response['tenders']['tender']));
    	$cname = $response['tenders']['tender'][0]['name'];
    	$cattrName = $response['tenders']['tender'][0]["attributes"]["attributes"]['name'];
    	
    	// add a new tender
    	$tender_name = "tn".microtime(true);
    	$attrName = "attr";
    	$data = array(
    			"root"=> array(
    					"organization"=> array(
    							"tenders"=> array(
    									"tender"=> array(
    											0=> array(
    													"name" => $tender_name,
    													"canonical_name" => $cname,
    													"attributes" => array(
    															"attribute" => array(
    																	0=> array(
    																			"name" => "ut_test",
    																			"data_type" => "TYPED",
    																			"options" => array(
    																					"option" => array(
    																							0 => array( "value" => "value1"),
    																							1 => array( "value" => "value2"),
    																							2 => array( "value" => "value3")
    																					)
    																			)
    																	),  // custom attr
    															)
    													)
    											)
    									)
    							)
    					)
    			)
    	);
    	$response = $this->organizationResourceObj->process("v1.1", "tenders", $data, null,"POST");
    	$this->assertEquals('200', $response['status']['code']);
    	$this->assertNotNull($response['organization']['tenders']['tender'][0]['name']);
    	$this->assertEquals($response['organization']['tenders']['count'] , count($response['organization']['tenders']['tender']));
    	$name = $response['organization']['tenders']['tender'][0]['name'];
    	
    }
    
    
    
	public function setUp(){
		$this->login( "till.005", "123" );
		parent::setUp();
	}
	
	public function tearDown(){
	}
}
?>
