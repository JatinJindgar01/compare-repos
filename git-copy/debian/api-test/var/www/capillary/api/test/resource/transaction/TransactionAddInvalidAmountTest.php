<? 

require_once('test/resource/transaction/ApiTransactionResourceTestBase.php');

class TransactionInvalidAmountTest extends ApiTransactionResourceTestBase
{
	private $preservedOrgConfigsData = array();
	private $customer = array();
    public function __construct()
    {
        parent::__construct();
    }

	/**
	 * will check for duplicate transaction
	 */
	public function testInvaldAmountRegularBills()
	{
		
		$lineitems = array(
				array(
						"serial" => 1,
						"amount" => "1000a",
						"description" => "nothing much",
						"item_code"=> "item -001",
						"qty" => 2,
						"rate" => 500,
						"value" => 1000,
						"discount" => 0,
				),
		);
		//adding new transaction --- start
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"line_items" => array("line_item" => $lineitems)
		);
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		
		$transaction["number"] = microtime();
		$transaction["amount"] = "a2000";
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(601, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);

		$transaction["number"] = microtime();
		$transaction["amount"] = "2000";
		$transaction["discount"] = "a2000";
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertContains("Invalid gross amount and/or discount amount", $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
		$this->assertContains("Invalid amount passed for item -001", $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
		
		$transaction["number"] = microtime();
		$transaction["amount"] = "2000";
		$transaction["gross_amount"] = "2000";
		$transaction["line_items"]["line_item"][0]["amount"] = "2000";
		$transaction["line_items"]["line_item"][0]["rate"] = "a";
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertContains("Invalid gross amount, rate, qty and/or discount passed for item -001", $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
		
	}
	
	/**
	 * will check for duplicate transaction
	 */
	public function testInvaldAmountNIBills()
	{
		$lineitems = array(
				array(
						"serial" => 1,
						"amount" => "1000a",
						"description" => "nothing much",
						"item_code"=> "item -001",
						"qty" => 2,
						"rate" => 500,
						"value" => 1000,
						"discount" => 0,
				),
		);
		//adding new transaction --- start
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "NOT_INTERESTED",
				"line_items" => array("line_item" => $lineitems)
		);
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		
		$transaction["number"] = microtime();
		$transaction["amount"] = "a2000";
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(601, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);

		$transaction["number"] = microtime();
		$transaction["amount"] = "2000";
		$transaction["discount"] = "a2000";
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertContains("Invalid gross amount and/or discount amount", $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
		$this->assertContains("Invalid amount passed for item -001", $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
		
		$transaction["number"] = microtime();
		$transaction["amount"] = "2000";
		$transaction["gross_amount"] = "2000";
		$transaction["line_items"]["line_item"][0]["amount"] = "2000";
		$transaction["line_items"]["line_item"][0]["rate"] = "a";
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertContains("Invalid gross amount, rate, qty and/or discount passed for item -001", $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
					
	}
	
	public function setUp()
	{
		$this->login("till.005", "123");
		parent::setUp();
		$peContext = new \Api\UnitTest\Context('pointsengine');
		$peContext->set("response/constant", true);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$this->customer = array(
				"email" => "0customer@capillarytech.com",
				"mobile" => "918867198309",
				"external_id" => "EXT_0918867198309",
				"firstname" => "Customer"
		);
	
		$customer_add_response = $this->addCustomerTest($this->customer, $query_params);
		//adding customer --- finished
	
	}
	
	
	public function tearDown()
	{
		//reseting
		$cm = new ConfigManager();
		foreach($this->preservedOrgConfigsData AS $name => $value)
		{
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=$this->currentorg->org_id;
			$key_value['value']=$value;
	
			$cm->setKeyValue($name, $key_value);
		}
	}
}
