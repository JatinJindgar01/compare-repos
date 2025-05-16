<? 

require_once('test/resource/transaction/ApiTransactionResourceTestBase.php');

class TransactionNumDuplicateTest extends ApiTransactionResourceTestBase
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
	public function testAddDoubleSumbission()
	{
		$this->setUpConfigForAddDuplicateTransaction();
		
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
		
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(604, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response2['transactions']['transaction'][0]['number']);	
		
	}
	
	
	/**
	 * 
	 */
	public function testAddDuplicateInNdays()
	{
		$this->setUpConfigForAddDuplicateTransaction();
	
		//adding new transaction --- start
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"line_items" => array("line_item" => $lineitems),
				"billing_time" => date('Y-m-d', strtotime("-31 days"))
		);
	
		// new txn
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	
		// within n days forward; duplicate
		$transaction['billing_time'] = date('Y-m-d', strtotime("-2 days"));
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(604, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);

		// after n days forward; allow
		$transaction['billing_time'] = date('Y-m-d', strtotime("now"));
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(600, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
		
		// with in n days backward ; duplicate
		$transaction['billing_time'] = date('Y-m-d', strtotime("-35 days"));
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(604, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);

		// with in n days backward ; duplicate
		// TODO: fix code
		$transaction['billing_time'] = date('Y-m-d', strtotime("-65 days"));
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
	}

	/**
	 *
	 */
	public function atestAddDuplicateInNdaysAcrossStores()
	{
		$this->setUpConfigForAddDuplicateAcrossStores();
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"line_items" => array("line_item" => $lineitems),
				"billing_time" => date('Y-m-d', strtotime("-31 days"))
		);
	
		// new txn
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	
		$this->login("mb.tn.chn.navalur2", "123");
		
		// within n days forward; duplicate
		$transaction['billing_time'] = date('Y-m-d', strtotime("-2 days"));
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(604, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
	
		// after n days forward; allow
		$transaction['billing_time'] = date('Y-m-d', strtotime("now"));
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(600, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
	
		// with in n days backward ; duplicate
		$transaction['billing_time'] = date('Y-m-d', strtotime("-35 days"));
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(604, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
	
		// with in n days backward ; duplicate
		// TODO: fix code
		$transaction['billing_time'] = date('Y-m-d', strtotime("-65 days"));
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
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
	
	public function setUpConfigForAddDuplicateTransaction()
	{
		$newOrgConfigData = array(
				"CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE" => 1,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_STORE" => 0,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_TILL" => 1,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_IN_DAYS" => 30
		);
	
		$cm = new ConfigManager();
		foreach($newOrgConfigData AS $key => $value)
		{
			$current_value = $cm->getKeyValueForOrg($key, $this->currentorg->org_id);
			//$current_value = $cf->getConfigKey($key);
			$this->preservedOrgConfigsData[$key] = $current_value;
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=$this->currentorg->org_id;
			$key_value['value']=$value;
	
			$cm->setKeyValue($key, $key_value);
		}
	}
	
	private function setUpConfigForAddDuplicateAcrossStores()
	{
		$newOrgConfigData = array(
				"CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE" => 1,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_STORE" => 0,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_TILL" => 0,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_IN_DAYS" => 30
		);
		
		$cm = new ConfigManager();
		foreach($newOrgConfigData AS $key => $value)
		{
			$current_value = $cm->getKeyValueForOrg($key, $this->currentorg->org_id);
			//$current_value = $cf->getConfigKey($key);
			$this->preservedOrgConfigsData[$key] = $current_value;
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=$this->currentorg->org_id;
			$key_value['value']=$value;
		
			$cm->setKeyValue($key, $key_value);
		}
		
	}
	public function setUpConfigForMinTime()
	{
		$newOrgConfigData = array(
				"CONF_MIN_BILLING_DATE" => "2013-01-01"
		);
	
		$cm = new ConfigManager();
		foreach($newOrgConfigData AS $key => $value)
		{
			$current_value = $cm->getKeyValueForOrg($key, $this->currentorg->org_id);
			//$current_value = $cf->getConfigKey($key);
			$this->preservedOrgConfigsData[$key] = $current_value;
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=$this->currentorg->org_id;
			$key_value['value']=$value;
	
			$cm->setKeyValue($key, $key_value);
		}
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
?>
