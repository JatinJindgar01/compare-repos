<?
require_once('test/resource/tenders/ApiTendersResourceTestBase.php');

class TendersGetTest extends ApiTendersResourceTestBase
{
	private $currentConfigs = array();
	
	public function __construct(){
		
		parent::__construct();
	}

	public function testGetPaymentTenders(){
		// get the attibutes
        $response = $this->tendersResourceObj->process("v1.1", "get", null, null,"GET");
        $this->assertEquals('200', $response['status']['code']);
        $this->assertNotNull($response['tenders']['tender'][0]['name']);
        $this->assertEquals($response['tenders']['count'] , count($response['tenders']['tender']));
        $name = $response['tenders']['tender'][0]['name'];

        $queryParam = array("name" => $name);
        $response = $this->tendersResourceObj->process("v1.1", "get", null, $queryParam,"GET");
        $this->assertEquals('200', $response['status']['code']);
        $this->assertEquals($response['tenders']['count'] , 1);
        
        // check for attributes
        $queryParam = array("attributes" => 1);
        $response = $this->tendersResourceObj->process("v1.1", "get", null, $queryParam,"GET");
        $this->assertEquals('200', $response['status']['code']);
        foreach($response['tenders']['tender'] as $mode)
        {
        	if($mode["attributes"] && $mode["attributes"]["attribute"])
        	{
        		
        		$this->assertEquals($mode["attributes"]['count'] , count($mode["attributes"]["attribute"]));
        		break;
        	}
        }
        
    }

    public function testGetOrgTendersAttrs(){
    	
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
	    																			"name" => $attrName,
	    																			"canonical_name" => $cattrName,
	    																			),  // custom attr
	    																	1=> array(
	    																			"name" => $attrName."-custom",
	    																			
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
    	
    	$queryParam = array("name" => $name, "options"=>1);
    	$response = $this->organizationResourceObj->process("v1.1", "tenders", null, $queryParam,"GET");
    	$this->assertEquals('200', $response['status']['code']);
    	$this->assertEquals($response['organization']['tenders']['count'] , 1);
    
     	// check for attributes
     	$queryParam = array("attributes" => 1, "name" => $name);
     	$response = $this->organizationResourceObj->process("v1.1", "tenders", null, $queryParam,"GET");
     	
     	$this->assertEquals('200', $response['status']['code']);
     	foreach($response['organization']['tenders']['tender'] as $mode)
     	{
     		if($mode["attributes"] && $mode["attributes"]["attribute"])
     		{
     			$this->assertEquals($mode["attributes"]['count'] , count($mode["attributes"]["attribute"]));
     			break;
     		}
     	}
    
     	$attrCountAfterAdd = $mode["attributes"]['count'];
     	$data["root"]["organization"]["tenders"]["tender"][0]["attributes"]["attribute"][0]["action"] = 'delete';
     	$data["root"]["organization"]["tenders"]["tender"][0]["attributes"]["attribute"][1]["action"] = 'delete';
     	$response = $this->organizationResourceObj->process("v1.1", "tenders", $data, null,"POST");
     	$this->assertEquals('200', $response['status']['code']);
     	$this->assertNotNull($response['organization']['tenders']['tender'][0]['name']);
     	$this->assertEquals($response['organization']['tenders']['count'] , count($response['organization']['tenders']['tender']));
     	$name = $response['organization']['tenders']['tender'][0]['name'];
     	
     	$queryParam = array("attributes" => 1, "name" => $name);
     	$response = $this->organizationResourceObj->process("v1.1", "tenders", null, $queryParam,"GET");
     	$this->assertEquals('200', $response['status']['code']);
     	$this->assertEquals($response['organization']['tenders']['tender'][0]["attributes"]['count'], $attrCountAfterAdd-2);

     	$data["root"]["organization"]["tenders"]["tender"][0]["attributes"]["attribute"][0]["action"] = 'add';
     	$data["root"]["organization"]["tenders"]["tender"][0]["attributes"]["attribute"][1]["action"] = 'add';
     	$response = $this->organizationResourceObj->process("v1.1", "tenders", $data, null,"POST");
     	$this->assertEquals('200', $response['status']['code']);
     	$this->assertNotNull($response['organization']['tenders']['tender'][0]['name']);
     	$this->assertEquals($response['organization']['tenders']['count'] , count($response['organization']['tenders']['tender']));
     	$name = $response['organization']['tenders']['tender'][0]['name'];
     	
     	$queryParam = array("attributes" => 1, "name" => $name);
     	$response = $this->organizationResourceObj->process("v1.1", "tenders", null, $queryParam,"GET");
     	$this->assertEquals('200', $response['status']['code']);
     	$this->assertEquals($response['organization']['tenders']['tender'][0]["attributes"]['count'], $attrCountAfterAdd);
    }
    
    
    public function testAddDeleteOrgTender(){
    	 
    	// get a canonocal name
    	$response = $this->tendersResourceObj->process("v1.1", "get", array("attributes"=>1, "options"=>1), null,"GET");
    	$this->assertEquals('200', $response['status']['code']);
    	$this->assertNotNull($response['tenders']['tender'][0]['name']);
    	$this->assertEquals($response['tenders']['count'] , count($response['tenders']['tender']));
    	$cname = $response['tenders']['tender'][0]['name'];
    
    	// add a new tender
    	$tender_name = "tn".microtime(true);
    	$data = array(
    			"root"=> array(
    					"organization"=> array(
    							"tenders"=> array(
    									"tender"=> array(
    											0=> array(
    													"name" => $tender_name,
    													"canonical_name" => $cname,
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
    	 
    	$queryParam = array("name" => $name, "options"=>1);
    	$response = $this->organizationResourceObj->process("v1.1", "tenders", null, $queryParam,"GET");
    	$this->assertEquals('200', $response['status']['code']);
    	$this->assertEquals($response['organization']['tenders']['count'] , 1);
    
    	$data["root"]["organization"]["tenders"]["tender"][0]["action"] = 'delete';
    	$response = $this->organizationResourceObj->process("v1.1", "tenders", $data, null,"POST");
    	$this->assertEquals('200', $response['status']['code']);
    	$this->assertEquals($name, $response['organization']['tenders']['tender'][0]['name']);
    	$name = $response['organization']['tenders']['tender'][0]['name'];

    	$response = $this->organizationResourceObj->process("v1.1", "tenders", null, $queryParam,"GET");
    	$this->assertEquals('500', $response['status']['code']);
    }
    
    
	public function setUp(){
		$this->login( "till.005", "123" );
		parent::setUp();
	}
	
	public function tearDown(){
	}
}
?>
