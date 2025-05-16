<?php
require_once('test/resource/transaction/ApiTransactionResourceTestBase.php');

class TransactionMixedTest extends ApiTransactionResourceTestBase
{
	private $preservedOrgConfigsData = array();
	public function __construct()
	{
		parent::__construct();
	}

	public function testTransactionReturnLineItemNonExisting()
	{
		$rand_number = rand(10000, 99999);
		$time = microtime(). substr(__FUNCTION__, -20);
		$customer = array(
				"email" => $rand_number."1customer@capillarytech.com",
				"mobile" => "9188672".$rand_number,
				"external_id" => "EXT_2".$rand_number,
				"firstname" => "Customer"
		);
	
		$lineitems = array();
	
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "FULL",
				"amount"=>400,
				"transaction_number" => $time."- NE - LI #1",
				"transaction_date" => date('Y-m-d h:i:s', strtotime("20 seconds")),
				"notes" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 1,
				"rate"	=> 500,
				"value"	=> 500,
				"discount_value" => 100,
		);
		$lineitems []= $lineitem;
	
		$transaction2 = array(
				"number" => $time . "#2",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
	
		$transactions = array();
		$transactions[] = $transaction2;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
	
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		#print_r($transaction_return_response['transactions']['transaction'][0]);
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		
		//Duplication of full return
		$request_arr["root"]["transaction"][0]["number"] = $time."#3";
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertContains('Could not return the transaction  with number', $transaction_return_response['transactions']['transaction'][0]['item_status']['message']);
	}

	
	public function testTransactionReturnLineItemNoTxnNumber()
	{
		$time = microtime(). substr(__FUNCTION__, -20);
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."1customer@capillarytech.com",
				"mobile" => "9188672".$rand_number,
				"external_id" => "EXT_2".$rand_number,
				"firstname" => "Customer"
		);
	
		$lineitems = array();
	
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "LINE_ITEM",
				"amount"=>400,
				"notes" => "sample test item - no txn number",
				"item_code"=> "sample-001 - no parent",
				"qty"	=> 1,
				"rate"	=> 500,
				"value"	=> 500,
				"discount_value" => 100,
		);
		$lineitems []= $lineitem;
	
		$transaction2 = array(
				"number" => $time . "#LL 2",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
	
		$transactions = array();
		$transactions[] = $transaction2;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
	
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
	}
	public function testTransactionReturnLineItem()
	{
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."1customer@capillarytech.com",
				"mobile" => "9188672".$rand_number,
				"external_id" => "EXT_2".$rand_number,
				"firstname" => "Customer"
		);
		
		$lineitems = array();
		$lineitem = array(
				"serial" => 1,
				"type"	=> "REGULAR",
				"amount"=>2000,
				"description" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 2,
				"rate"	=> 1100,
				"value"	=> 2200,
				"discount_value" => 200,
				);
		$lineitems []= $lineitem;
		
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		//adding customer --- finished
		$time = microtime(). substr(__FUNCTION__, -20);
		$transaction1 = array(
				"number" => $time . "#1",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
		
		$transactions = array();
		$transactions[] = $transaction1;
		
		$request_arr = array("root" => array(
						"transaction" => $transactions
				));
		$transaction_add_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_add_response['status']['code']);
		$ids = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($ids, $query_params);
		$this->assertEquals(200, $transaction_get_response['status']['code']);
		
		## retrun an item in mixed tranx
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "LINE_ITEM",
				"amount"=>-400,
				"transaction_number" => $time."#1",
				"notes" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> -1,
				//"rate"	=> 500,
				//"value"	=> 500,
				"discount_value" => 100,
		);
		$lineitems []= $lineitem;

		$lineitem = array(
				"serial" => 2,
				"type"	=> "RETURN",
				"return_type"=> "LINE_ITEM",
				"amount"=> -600,
				"transaction_number" => $time."#1",
				"notes" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> -1,
				//"rate"	=> 700,
				//"value"	=> 700,
				"discount_value" => 100,
		);
		
		$lineitems []= $lineitem;
		#$lineitems []= $lineitem;
		
		$transaction2 = array(
				"number" => $time . "#2",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);

		$transactions = array();
		$transactions[] = $transaction2;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		#print_r($transaction_return_response);
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		
		#####
		#print "\nCalling the direct return \n";
		$transaction_return = array(
				"number" => $time . "#1",
				"amount" => 1000,
				"type" => "RETURN",
				"return_type" => "line_item",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> array($lineitem))
		);
		$transactions = array();
		$transactions[] = $transaction_return;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		#print_r($transaction_return_response); return;
		$this->assertEquals(500, $transaction_return_response['status']['code']);
		#$this->assertEquals(626, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		
		#####
		// more than available line items 
		$lineitems = array();
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "LINE_ITEM",
				"amount"=>400,
				"transaction_number" => $time."#1",
				"notes" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 1,
				//"rate"	=> 500,
				//"value"	=> 500,
				"discount_value" => 100,
		);
		$lineitems []= $lineitem;
		
		$lineitem = array(
				"serial" => 2,
				"type"	=> "RETURN",
				"return_type"=> "LINE_ITEM",
				"amount"=>600,
				"transaction_number" => $time."#1",
				"notes" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 2,
				//"rate"	=> 700,
				//"value"	=> 700,
				"discount_value" => 100,
		);
		
		$lineitems []= $lineitem;
		#$lineitems []= $lineitem;
		
		$transaction3 = array(
		"number" => $time . "#3",
		"amount" => 2000,
		"type" => "REGULAR",
		"customer" => $customer,
		"lineitems" => array("lineitem"=> $lineitems),
		);
		
		$transactions = array();
		$transactions[] = $transaction3;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		#print_r($transaction_return_response);
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		
	}

	public function testTransactionReturnFull()
	{
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."1customer@capillarytech.com",
				"mobile" => "9188672".$rand_number,
				"external_id" => "EXT_2".$rand_number,
				"firstname" => "Customer"
		);
	
		$lineitems = array();
		$lineitem = array(
				"serial" => 1,
				"type"	=> "REGULAR",
				"amount"=>2000,
				"description" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 2,
				"rate"	=> 1100,
				"value"	=> 2200,
				"discount_value" => 200,
		);
		$lineitems []= $lineitem;
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		//adding customer --- finished
		$time = microtime(). substr(__FUNCTION__, -20);
		$transaction1 = array(
				"number" => $time . "#1",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
	
		$transactions = array();
		$transactions[] = $transaction1;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		$transaction_add_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_add_response['status']['code']);
		$ids = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($ids, $query_params);
		$this->assertEquals(200, $transaction_get_response['status']['code']);
	
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "FULL",
				"amount"=>1000,
				"transaction_number" => $time."#1",
				"notes" => "sample test full",
		);
		$lineitems []= $lineitem;
	
		$transaction2 = array(
				"number" => $time . "#2",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
	
		$transactions = array();
		$transactions[] = $transaction2;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
	
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
	}
	

	public function testTransactionReturnFullWithoutLineItems()
	{
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."1customer@capillarytech.com",
				"mobile" => "9188672".$rand_number,
				"external_id" => "EXT_2".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		//adding customer --- finished
		$time = microtime(). substr(__FUNCTION__, -20);
		$transaction1 = array(
				"number" => $time . "#1",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
		);
	
		$transactions = array();
		$transactions[] = $transaction1;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		$transaction_add_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_add_response['status']['code']);
		$ids = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($ids, $query_params);
		$this->assertEquals(200, $transaction_get_response['status']['code']);
		$this->assertEquals(2000, $transaction_add_response['transactions']['transaction'][0]['customer']['lifetime_purchases']);
	
		$lineitems = array();
		
		$lineitem = array(
				"serial" => 1,
				"type"	=> "REGULAR",
				"amount"=>2000,
				"description" => "sample test item",
				"item_code"=> "item-002",
				"qty"	=> 2,
				"rate"	=> 1000,
				"value"	=> 2000,
				"discount_value" => 0,
		);
		$lineitems []= $lineitem;
		
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "FULL",
				"amount"=>1000,
				"transaction_number" => $time."#1",
				"notes" => "sample test full",
		);
		$lineitems []= $lineitem;
	
		$transaction2 = array(
				"number" => $time . "#2",
				"amount" => 3400,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
	
		$transactions = array();
		$transactions[] = $transaction2;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
	
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		#print_r($transaction_return_response['transactions']['transaction'][0]['customer']['lifetime_purchases']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		#print_r($transaction_return_response['transactions']['transaction']);
		#$this->assertEquals(3400, $transaction_return_response['transactions']['transaction'][0]['customer']['lifetime_purchases']);
	}
	
	public function testTransactionReturnAmount()
	{
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."1customer@capillarytech.com",
				"mobile" => "9188672".$rand_number,
				"external_id" => "EXT_2".$rand_number,
				"firstname" => "Customer"
		);
	
		$lineitems = array();
		$lineitem = array(
				"serial" => 1,
				"type"	=> "REGULAR",
				"amount"=>2000,
				"description" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 2,
				"rate"	=> 1100,
				"value"	=> 2200,
		);
		$lineitems []= $lineitem;
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		//adding customer --- finished
		$time = microtime(). substr(__FUNCTION__, -20);
		$transaction1 = array(
				"number" => $time . "#1",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
	
		$transactions = array();
		$transactions[] = $transaction1;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
 		$transaction_add_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
 		$this->assertEquals(200, $transaction_add_response['status']['code']);
 		$ids = $transaction_add_response['transactions']['transaction'][0]['id'];
 		$transaction_get_response = $this->getTransaction($ids, $query_params);
 		$this->assertEquals(200, $transaction_get_response['status']['code']);
	
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "AMOUNT",
				"amount"=>1000,
				"transaction_number" => $time."#1",
				"notes" => "sample test full",
		);
		$lineitems []= $lineitem;
	
		$transaction2 = array(
				"number" => $time . "#2",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
	
		$transactions = array();
		$transactions[] = $transaction2;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
	
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
	}
	
	
	public function testTransactionReturnLineItemMoreAmt()
	{
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."1customer@capillarytech.com",
				"mobile" => "9188672".$rand_number,
				"external_id" => "EXT_2".$rand_number,
				"firstname" => "Customer"
		);
	
		$lineitems = array();
		$lineitem1 = array(
				"serial" => 1,
				"type"	=> "REGULAR",
				"amount"=>500,
				"description" => "sample test item",
				"item_code"=> "SampLE-001",
				"qty"	=> 50,
				"rate"	=> 10,
				"value"	=> 500,
				"discount_value" => 0,
		);
		$lineitems []= $lineitem1;
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		//adding customer --- finished
		$time = microtime(). substr(__FUNCTION__, -20);
		$transaction1 = array(
				"number" => $time . "#1",
				"amount" => 500,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
	
		$transactions = array();
		$transactions[] = $transaction1;
	
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		$transaction_add_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->assertEquals(200, $transaction_add_response['status']['code']);
		$ids = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($ids, $query_params);
		$this->assertEquals(200, $transaction_get_response['status']['code']);

		
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "LINE_ITEM",
				"amount"=>200,
				"transaction_number" => $time."#1",
				"notes" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 10,
				"rate"	=> 20,
				"value"	=> 200,
				//"discount_value" => 100,
		);
		$lineitems = array();
		$lineitems []= $lineitem1;
		$lineitems []= $lineitem;
		
		$transaction2 = array(
		"number" => $time . "#2",
		"amount" => 2000,
		"type" => "REGULAR",
		"customer" => $customer,
		"lineitems" => array("lineitem"=> $lineitems),
		);
		
		$transactions = array();
		$transactions[] = $transaction2;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$message = $transaction_return_response['transactions']['transaction'][0]['item_status']['message'];
		#print $message. "\n\n";
		
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);

		
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "LINE_ITEM",
				"amount"=>300,
				"transaction_number" => $time."#1",
				"notes" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 10,
				"rate"	=> 30,
				"value"	=> 300,
				//"discount_value" => 100,
		);
		$lineitems = array();
		$lineitems []= $lineitem1;
		$lineitems []= $lineitem;
		
		$transaction2 = array(
				"number" => $time . "#3",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
		
		$transactions = array();
		$transactions[] = $transaction2;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		#print_r($transaction_return_response);
		$message = $transaction_return_response['transactions']['transaction'][0]['item_status']['message'];
		#print $message. "\n\n";
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);

		
		$lineitem = array(
				"serial" => 1,
				"type"	=> "RETURN",
				"return_type"=> "LINE_ITEM",
				"amount"=>200,
				"transaction_number" => $time."#1",
				"notes" => "sample test item",
				"item_code"=> "sample-001",
				"qty"	=> 20,
				"rate"	=> 10,
				"value"	=> 200,	
				//"discount_value" => 100,
		);
		$lineitems = array();
		$lineitems []= $lineitem1;
		$lineitems []= $lineitem;
		
		$transaction3 = array(
				"number" => $time . "#4",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"lineitems" => array("lineitem"=> $lineitems),
		);
		
		$transactions = array();
		$transactions[] = $transaction3;
		
		$request_arr = array("root" => array(
				"transaction" => $transactions
		));
		
		$transaction_return_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		#print_r($transaction_return_response);
		$this->assertEquals(200, $transaction_return_response['status']['code']);
		$this->assertEquals(600, $transaction_return_response['transactions']['transaction'][0]['item_status']['code']);
		$message = $transaction_return_response['transactions']['transaction'][0]['item_status']['message'];
		#print $message . "\n\n";
		$this->assertGreaterThan(1, strpos($message, " Amount of returned item is more than purchased item") );
	}

	public function setUp()
	{
		#$this->login("zoneentity5", "123");
		$this->login("till.005", "123");
		parent::setUp();
		$peContext = new \Api\UnitTest\Context('pointsengine');
		$peContext->set("response/constant", true);
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