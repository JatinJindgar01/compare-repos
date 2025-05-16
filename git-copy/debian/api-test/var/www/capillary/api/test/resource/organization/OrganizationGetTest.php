<?
require_once('test/resource/organization/ApiOrganizationResourceTestBase.php');

class OrganizationGetTest extends ApiOrganizationResourceTestBase
{
	private $currentConfigs = array();
	
	public function __construct(){
		
		parent::__construct();
	}

	public function testOrganizationGet(){
        $response = $this->organizationResourceObj->process("v1.1", "get", null, null,"GET");
        $this->assertEquals('200', $response['status']['code']);
        $this->assertEquals('732', $response['organization']['id']);
        $this->assertEquals('INR', $response['organization']['currencies']['base_currency']['label']);
        $this->assertNotNull($response['organization']['languages']['base_language']['lang']);
        $this->assertNotNull($response['organization']['languages']['base_language']['locale']);
        $this->assertNotNull($response['organization']['timezones']['base_timezone']['offset']);
        $this->assertNotNull($response['organization']['timezones']['base_timezone']['label']);
        $this->assertNotNull($response['organization']['countries']['base_country']['name']);
        $this->assertNotNull($response['organization']['countries']['base_country']['code']);
    }

    public function testOrgSupportedCountries(){
        $response = $this->organizationResourceObj->process("v1.1", "get", null, null,"GET");
        $this->assertGreaterThan(1, count($response['organization']['countries']['supported_countries']['country']));
        $this->assertNotNull($response['organization']['countries']['supported_countries']['country'][0]['name']);
        $this->assertNotNull($response['organization']['countries']['supported_countries']['country'][0]['code']);
    }

    public function testOrgSupportedCurrencies(){
        $response = $this->organizationResourceObj->process("v1.1", "get", null, null,"GET");
        $this->assertGreaterThan(1, count($response['organization']['currencies']['supported_currencies']['currency']));
        $this->assertNotNull($response['organization']['currencies']['supported_currencies']['currency'][0]['symbol']);
        $this->assertNotNull($response['organization']['currencies']['supported_currencies']['currency'][0]['label']);
    }

    public function testOrgSupportedLanguages(){
        $response = $this->organizationResourceObj->process("v1.1", "get", null, null,"GET");
        $this->assertGreaterThan(1, count($response['organization']['languages']['supported_languages']['language']));
        $this->assertNotNull($response['organization']['languages']['supported_languages']['language'][0]['lang']);
        $this->assertNotNull($response['organization']['languages']['supported_languages']['language'][0]['locale']);
    }

    public function testOrgSupportedTimezones(){
        $response = $this->organizationResourceObj->process("v1.1", "get", null, null,"GET");
        $this->assertGreaterThan(1, count($response['organization']['timezones']['supported_timezones']['timezone']));
        $this->assertNotNull($response['organization']['timezones']['supported_timezones']['timezone'][0]['label']);
        $this->assertNotNull($response['organization']['timezones']['supported_timezones']['timezone'][0]['offset']);
    }
	
	public function setUp(){
		$this->login( "till.005", "123" );
		parent::setUp();
	}
	
	public function tearDown(){
	}
}
?>
