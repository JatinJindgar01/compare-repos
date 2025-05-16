<? 

require_once('test/resource/transaction/ApiTransactionResourceTestBase.php');

class TransactionOutlierTest extends ApiTransactionResourceTestBase
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
	public function testAddOutlierBillAmount()
	{
	
		//adding new transaction --- start
		// less than threshold
		$transaction = array(
				"number" => microtime(),
				"amount" => 1,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		$this->assertContains('Transaction is marked as outlier', $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);

		
		// above threshold
		$transaction = array(
				"number" => microtime(),
				"amount" => 60000,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"line_items" => array("line_item" => $lineitems)
		);
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		$this->assertContains('Transaction is marked as outlier', $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
		
	}

	/**
	 * will check for duplicate transaction
	 */
	public function testAddOutlierBillNumber()
	{
	
		//adding new transaction --- start
	
		// based on bill number
		$transaction = array(
				"number" => "test".microtime(),
				"amount" => 1000,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
	
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		$this->assertContains('Transaction is marked as outlier', $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
	
	}

	/**
	 * will check for duplicate transaction
	 */
	public function testAddOutlierLineItemAmount()
	{
	
		//adding new transaction --- start
		$lineitems = array(
				array(
						"serial" => 1,
						"amount" => 200,
						"description" => "nothing much",
						"item_code"=> "soap",
						"qty" => 2,
						"rate" => 100,
						"value" => 200,
						"discount" => 0,
				),
				array(
						"serial" => 1,
						"amount" => 20000,
						"description" => "nothing much",
						"item_code"=> "soap",
						"qty" => 2,
						"rate" => 10000,
						"value" => 20000,
						"discount" => 0,
				),
				
				array(
						"serial" => 1,
						"amount" => 2,
						"description" => "nothing much",
						"item_code"=> "soap",
						"qty" => 2,
						"rate" => 1,
						"value" => 2,
						"discount" => 0,
				),
				
		);
		// based on bill number
		$transaction = array(
				"number" => microtime(),
				"amount" => 200,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
	
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		$id = $transaction_add_response['transactions']['transaction'][0]['id'];
		$this->assertNotNull($id);
		
		// check the db for outlier status
		$sql = "select count(*) as outlier_count 
				from user_management.loyalty_log as ll 
				inner join loyalty_bill_lineitems as lbl 
				on lbl.loyalty_log_id = ll.id and ll.org_id = lbl.org_id
				WHERE lbl.outlier_status = 'OUTLIER' 
					AND ll.outlier_status = 'NORMAL' and ll.id = $id";
		$db = new DBase("users");
		$outlier_count = $db->query_firstrow($sql);
		$this->assertEquals($outlier_count["outlier_count"], 2);
		
		//$this->assertContains('Transaction is marked as outlier', $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
	
	}

	/**
	 * will check for duplicate transaction
	 */
	public function testAddOutlierLineItemCode()
	{
	
		//adding new transaction --- start
		$lineitems = array(
				array(
						"serial" => 1,
						"amount" => 200,
						"description" => "nothing much",
						"item_code"=> "test_item",
						"qty" => 2,
						"rate" => 100,
						"value" => 200,
						"discount" => 0,
				),
				array(
						"serial" => 1,
						"amount" => 200,
						"description" => "nothing much",
						"item_code"=> "soap",
						"qty" => 2,
						"rate" => 100,
						"value" => 200,
						"discount" => 0,
				),
					
		);
		// based on bill number
		$transaction = array(
				"number" => microtime(),
				"amount" => 200,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
	
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		$id = $transaction_add_response['transactions']['transaction'][0]['id'];
		$this->assertNotNull($id);
	
		// check the db for outlier status
		$sql = "select count(*) as outlier_count
		from user_management.loyalty_log as ll
		inner join loyalty_bill_lineitems as lbl
		on lbl.loyalty_log_id = ll.id and ll.org_id = lbl.org_id
		WHERE lbl.outlier_status = 'OUTLIER'
		AND ll.outlier_status = 'NORMAL' and ll.id = $id";
		$db = new DBase("users");
		$outlier_count = $db->query_firstrow($sql);
		$this->assertEquals($outlier_count["outlier_count"], 1);
		//$this->assertContains('Transaction is marked as outlier', $transaction_add_response['transactions']['transaction'][0]['item_status']['message']);
	
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
		
		$newOrgConfigData = array(
				"CONF_LOYALTY_MAX_BILL_AMOUNT" => 50000,
				"CONF_LOYALTY_MIN_BILL_AMOUNT" => 5,
				"CONF_LOYALTY_MAX_BILL_LINEITEM_AMOUNT" => 1000,
				"CONF_LOYALTY_MIN_BILL_LINEITEM_AMOUNT" => 5,
				//"MARK_BILLS_OUTLIER_FROM_STORE" => "0,1",
				"MARK_BILLS_OUTLIER_STARTING_WITH" => "test",
				"CONF_OUTLIER_ITEM_SKU" => "test_item"
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
?>
