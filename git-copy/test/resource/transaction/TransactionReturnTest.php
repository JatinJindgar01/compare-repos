<?php
require_once('test/resource/transaction/ApiTransactionResourceTestBase.php');
class TransactionReturnTest extends ApiTransactionResourceTestBase
{
	private $preservedOrgConfigsData = array();
	private $customer;
	public function __construct()
	{
		parent::__construct();
		
	}
	
	public function testTransactionReturnLineItemNoLIPassed()
	{
		$this->setAllTypeReturnsStatus(1);
		//$this->setUp();
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($this->customer, $query_params);
		//adding customer --- finished
		
		
		$transaction1 = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "RETURN",
				"return_type"=> "full",
				"customer" => $this->customer,
		);
		
		$transactions = array();
		$transactions[] = $transaction1;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		$transaction_add_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		
		$this->assertEquals(200, $transaction_add_response['status']['code']);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction1['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		$this->assertEquals(1, count($transaction_add_response['transactions']['transaction']));
		
		$ids = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($ids, $query_params);

	}
	public function testTransactionReturn()
	{
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($this->customer, $query_params, false);
		//adding customer --- finished
		
		//adding new transaction --- start
		$transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "5%"
						)
				)
		);
		
		$transaction1 = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $this->customer,
				"custom_fields" => $transaction_custom_fields
		);
		
		$transactions = array();
		$transactions[] = $transaction1;
		
		$request_arr = array("root" => array(
						"transaction" => $transactions
				));
		$transaction_add_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_add_response['status']['code']);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction1['number'], $transaction_add_response['transactions']['transaction'][0]['number']);		
		$this->assertEquals(1, count($transaction_add_response['transactions']['transaction']));
		
		$ids = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($ids, $query_params);
		
		$this->assertEquals(200, $transaction_get_response['status']['code']);
		$this->assertEquals(600, $transaction_get_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction_add_response['transactions']['transaction'][0]['id'], $transaction_get_response['transactions']['transaction'][0]['id']);
		$this->assertEquals($transaction_add_response['transactions']['transaction'][0]['number'], $transaction_get_response['transactions']['transaction'][0]['number']);
		
		$transaction_return = array(
				"number" => $transaction1['number'],
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "amount",
				"customer" => $this->customer
		);
		$transactions = array();
		$transactions[] = $transaction_return;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		//$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		//$this->assertEquals(200, $transaction_return_response['status']['code']);
		//$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
	}

	public function testTransactionReturnDisabling()
	{
 		$bill_prefix = "returntxn".microtime(true);				
		$transaction_return_amt = array(
				"number" => $bill_prefix."amount",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "amount",
				"customer" => $this->customer
		);
		$transaction_return_lineitem = array(
				"number" => $bill_prefix."full",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "full",
				"customer" => $this->customer
		);
		$transaction_return_full = array(
				"number" => $bill_prefix."li",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "",
				"customer" => $this->customer,
				"lineitems" => array(
							"lineitem" => array(
							"serial" => 1,
							"return_type"=> "LINE_ITEM",
							"amount"=>400,
							"notes" => "sample test item - no txn number",
							"item_code"=> "sample-001 - no parent",
							"qty"	=> 1,
							"rate"	=> 500,
							"value"	=> 500,
							"discount_value" => 100,
							),
						),
		);
		
		$transactions = array();
		$transactions[] = $transaction_return_amt;
		$transactions[] = $transaction_return_lineitem;
		$transactions[] = $transaction_return_full;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_SUPPORTED", 0);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_AMOUNT_SUPPORTED", 0);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_FULL_SUPPORTED", 0);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_LINE_ITEM_SUPPORTED", 0);
			
		$cm = new ConfigManager();
		
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		//print_r($transaction_return_response);
		
		$this->assertEquals(500, $transaction_return_response['status']['code']);
		$this->assertEquals(673, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals(673, $transaction_return_response['transactions']['transaction'][1]['item_status']['code']);
		$this->assertEquals(673, $transaction_return_response['transactions']['transaction'][2]['item_status']['code']);
		
	}

	public function testEnableAllReturn()
	{
		//$this->setAllTypeReturnsStatus(1);
		
		$bill_prefix = "returntxn".microtime(true);
		$transaction_return_amt = array(
				"number" => $bill_prefix."amount",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "amount",
				"customer" => $this->customer
		);
		$transaction_return_lineitem = array(
				"number" => $bill_prefix."full",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "full",
				"customer" => $this->customer
		);
		$transaction_return_full = array(
				"number" => $bill_prefix."li",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "",
				"customer" => $this->customer,
				"lineitems" => array(
						"lineitem" => array(
								"serial" => 1,
								"return_type"=> "LINE_ITEM",
								"amount"=>400,
								"notes" => "sample test item - no txn number",
								"item_code"=> "sample-001 - no parent",
								"qty"	=> 1,
								"rate"	=> 500,
								"value"	=> 500,
								"discount_value" => 100,
						),
				),
		);
		
		$transactions = array();
		$transactions[] = $transaction_return_amt;
		$transactions[] = $transaction_return_lineitem;
		$transactions[] = $transaction_return_full;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_SUPPORTED", 1);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_AMOUNT_SUPPORTED", 1);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_FULL_SUPPORTED", 1);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_LINE_ITEM_SUPPORTED", 1);
		
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		//print_r($transaction_return_response); 
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][1]['item_status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][2]['item_status']['code']);
	}

	public function testDisableSelectiveReturn()
	{
		//$this->setAllTypeReturnsStatus(1);
	
		$bill_prefix = "returntxn".microtime(true);
		$transaction_return_amt = array(
				"number" => $bill_prefix."amount",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "amount",
				"customer" => $this->customer
		);
		$transaction_return_lineitem = array(
				"number" => $bill_prefix."full",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "full",
				"customer" => $this->customer
		);
		$transaction_return_full = array(
				"number" => $bill_prefix."li",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "",
				"customer" => $this->customer,
				"lineitems" => array(
						"lineitem" => array(
								"serial" => 1,
								"return_type"=> "LINE_ITEM",
								"amount"=>400,
								"notes" => "sample test item - no txn number",
								"item_code"=> "sample-001 - no parent",
								"qty"	=> 1,
								"rate"	=> 500,
								"value"	=> 500,
								"discount_value" => 100,
						),
				),
		);
	
		$transactions = array();
		$transactions[] = $transaction_return_amt;
		$transactions[] = $transaction_return_lineitem;
		$transactions[] = $transaction_return_full;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
	
	
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_SUPPORTED", 1);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_AMOUNT_SUPPORTED", 0);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_FULL_SUPPORTED", 0);
		$this->setConfigKeyValue("CONF_LOYALTY_IS_RETURN_TRANSACTION_LINE_ITEM_SUPPORTED", 0);
	
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		//print_r($transaction_return_response);
		$this->assertEquals(500, $transaction_return_response['status']['code']);
		$this->assertEquals(675, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals(676, $transaction_return_response['transactions']['transaction'][1]['item_status']['code']);
		$this->assertEquals(676, $transaction_return_response['transactions']['transaction'][2]['item_status']['code']);
	}
	
	public function setUp()
	{
		global $currentorg; unset($currentorg);
		$this->login("till.005", "123");
		parent::setUp();
		$peContext = new \Api\UnitTest\Context('pointsengine');
		$peContext->set("response/constant", true);
		// add customer
		$this->customer = array(
				"email" => "returnTestcustomer@capillarytech.com",
				"mobile" => "918869721100",
				//"external_id" => "EXT_2918869721100",
				"firstname" => "Customer"
		);
		$this->cm = new ConfigManager($this->currentorg->org_id);
		
		$customer_add_response = $this->addCustomerTest($this->customer, $query_params);
		$this->setAllTypeReturnsStatus(1);
	}

	public function setAllTypeReturnsStatus($active = 1)
	{
		$newOrgConfigData = array(
				
				"CONF_LOYALTY_IS_RETURN_BILL_NUMBER_REQUIRED" => 0,
				"CONF_LOYALTY_IS_RETURN_TRANSACTION_AMOUNT_SUPPORTED" => $active,
				"CONF_LOYALTY_IS_RETURN_TRANSACTION_FULL_SUPPORTED" => $active,
				"CONF_LOYALTY_IS_RETURN_TRANSACTION_LINE_ITEM_SUPPORTED" => $active,
				"CONF_LOYALTY_IS_RETURN_TRANSACTION_SUPPORTED" => $active,
				"CONF_LOYALTY_IS_RETURN_TRANSACTION_WITHOUT_TXN_NUMBER" => 1,
				"CONF_POINTS_RETURN_ENABLED" => 0
				
		);
	
		
		$cm = new ConfigManager();
		foreach($newOrgConfigData AS $key => $value)
		{
			$current_value = $cm->getKeyValueForOrg($key, $this->currentorg->org_id);
			//$current_value = $cf->getConfigKey($key);
			if(!isset($this->preservedOrgConfigsData[$key]))
				$this->preservedOrgConfigsData[$key] = $current_value;
			
			$this->setConfigKeyValue($key, $value);
		}
	
	}
	
	public function tearDown()
	{
		//reseting
		$cm = new ConfigManager();
		foreach($this->preservedOrgConfigsData AS $name => $value)
			$this->setConfigKeyValue($name, $value);
		
	}
	
	public function setConfigKeyValue($key, $value)
	{
		global $currentorg;
		$currentorg = $this->currentorg;
	
		$key_value=array();
		$key_value['scope']='ORG';
		$key_value['entity_id']=$this->currentorg->org_id;
		$key_value['value']=$value;
		$this->cm->setKeyValue($key, $key_value);
	
	}
	
}
?>
