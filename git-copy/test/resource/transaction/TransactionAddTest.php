<? 

require_once('test/resource/transaction/ApiTransactionResourceTestBase.php');

class TransactionAddTest extends ApiTransactionResourceTestBase
{
	private $preservedOrgConfigsData = array();
    public function __construct()
    {
        parent::__construct();
    }

	public function testAddTransaction()
	{
		global $logger, $cfg, $currentuser, $currentorg;
		
		$customer = array(
				"email" => "test@example.com",
				"mobile" => "1111111",
				"name" => "test man",
				);
		
		$transaction = array(
				"number" => microtime(),
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer
				);
		
		$data = array(
				"root" => array("transaction" => array($transaction))
				);
		
		$ret = $this->transactionResourceObj->process('v1.1', 'add', $data, null, 'POST');
		$this->assertEquals(200, $ret['status']['code']);
		$this->assertEquals(1000, $ret['transactions']['transaction'][0]['customer']['lifetime_points']);
	}
	
	public function testAddTransactionInBatch_1()
	{
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
		
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
		
		$transaction2 = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
		
		$transactions[] = $transaction1;
		$transactions[] = $transaction2;
		
		$request_arr = array("root" => array(
						"transaction" => $transactions
				));
		
		$transaction_add_response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		
		$this->assertEquals(200, $transaction_add_response['status']['code']);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][1]['item_status']['code']);
		$this->assertEquals($transaction1['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		$this->assertEquals($transaction2['number'], $transaction_add_response['transactions']['transaction'][1]['number']);
		
		$this->assertEquals(2, count($transaction_add_response['transactions']['transaction']));
		
		$ids = $transaction_add_response['transactions']['transaction'][0]['id'].",".$transaction_add_response['transactions']['transaction'][1]['id'];
		$transaction_get_response = $this->getTransaction($ids, $query_params);
		
		$this->assertEquals(200, $transaction_get_response['status']['code']);
		
		$this->assertEquals(600, $transaction_get_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals(600, $transaction_get_response['transactions']['transaction'][1]['item_status']['code']);
		
		$this->assertEquals($transaction_add_response['transactions']['transaction'][0]['id'], $transaction_get_response['transactions']['transaction'][0]['id']);
		$this->assertEquals($transaction_add_response['transactions']['transaction'][1]['id'], $transaction_get_response['transactions']['transaction'][1]['id']);
		
		$this->assertEquals($transaction_add_response['transactions']['transaction'][0]['number'], $transaction_get_response['transactions']['transaction'][0]['number']);
		$this->assertEquals($transaction_add_response['transactions']['transaction'][1]['number'], $transaction_get_response['transactions']['transaction'][1]['number']);
	}
	
	/**
	 * testing transaction add with less than min billing date
	 */
	public function testAddTransactionForMinBillingDate()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
		$this->setUpConfigForMinTime();
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
		
		$item_code1 = "$rand_number"."SJDUJK23L1";
		$item_code2 = "$rand_number"."SJDUJK23L2";
		$lineitems = array(
					array(
							"serial" => 1,
							"amount" => 1000,
							"description" => "nothing much",
							"item_code"=> $item_code1,
							"qty" => 2,
							"rate" => 500,
							"value" => 1000,
							"discount" => 0,
							"attributes" => array(
								"attribute" => array(
										array("name" => "brand", "value" => "Levis"),
										array("name" => "size", "value" => "M")
										)
									)
							),
					array("serial" => 2,
							"amount" => 1000,
							"description" => "not anything",
							"item_code"=> $item_code2,
							"qty" => 2,
							"rate" => 600,
							"value" => 1200,
							"discount" => 200,
							"attributes" => array(
								"attribute" => array(
										array("name" => "brand", "value" => "PUMA"),
										array("name" => "size", "value" => "XXL")
										)
									)
							)
				);
		
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"billing_time" => "2012-12-31 17:00:00",
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(644, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
	}
	
	/**
	 * transaction add with very old time
	 */
	public function testAddTransactionWithLineitem_1()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
	
		$item_code1 = "$rand_number"."SJDUJK23L1";
		$item_code2 = "$rand_number"."SJDUJK23L2";
		$lineitems = array(
				array(
						"serial" => 1,
						"amount" => 1000,
						"description" => "nothing much",
						"item_code"=> $item_code1,
						"qty" => 2,
						"rate" => 500,
						"value" => 1000,
						"discount" => 0,
						"attributes" => array(
								"attribute" => array(
										array("name" => "brand", "value" => "Levis"),
										array("name" => "size", "value" => "M")
								)
						)
				),
				array("serial" => 2,
						"amount" => 1000,
						"description" => "not anything",
						"item_code"=> $item_code2,
						"qty" => 2,
						"rate" => 600,
						"value" => 1200,
						"discount" => 200,
						"attributes" => array(
								"attribute" => array(
										array("name" => "brand", "value" => "PUMA"),
										array("name" => "size", "value" => "XXL")
								)
						)
				)
		);
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$id = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($id, $query_params);
	
		$this->assertEquals($id, $transaction_get_response['transactions']['transaction'][0]['id']);
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item']));
		$this->assertEquals($item_code1, $transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][0]['item_code']);
		$this->assertEquals($item_code2, $transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][1]['item_code']);
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][0]['attributes']['attribute']));
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][1]['attributes']['attribute']));
	}
	
	/**
	 * added transaction with invalid inventory attribute name
	 */
	public function testAddTransactionWithLineitem_2()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
	
		$item_code1 = "$rand_number"."SJDUJK23L1";
		$item_code2 = "$rand_number"."SJDUJK23L2";
		$lineitems = array(
				array(
						"serial" => 1,
						"amount" => 1000,
						"description" => "nothing much",
						"item_code"=> $item_code1,
						"qty" => 2,
						"rate" => 500,
						"value" => 1000,
						"discount" => 0,
						"attributes" => array(
								"attribute" => array(
										array("name" => "brand", "value" => "Levis"),
										array("name" => "size", "value" => "M")
								)
						)
				),
				array("serial" => 2,
						"amount" => 1000,
						"description" => "not anything",
						"item_code"=> $item_code2,
						"qty" => 2,
						"rate" => 600,
						"value" => 1200,
						"discount" => 200,
						"attributes" => array(
								"attribute" => array(
										array("name" => "brand.01", "value" => "PUMA"),
										array("name" => "size", "value" => "XXL")
								)
						)
				)
		);
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$id = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($id, $query_params);
	
		$this->assertEquals($id, $transaction_get_response['transactions']['transaction'][0]['id']);
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item']));
		$this->assertEquals($item_code1, $transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][0]['item_code']);
		$this->assertEquals($item_code2, $transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][1]['item_code']);
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][0]['attributes']['attribute']));
		$this->assertEquals(1, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][1]['attributes']['attribute']));
	}
	
	/**
	 * added transaction with all invalid inventory attribute name
	 */
	public function testAddTransactionWithLineitem_3()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
	
		$item_code1 = "$rand_number"."SJDUJK23L1";
		$item_code2 = "$rand_number"."SJDUJK23L2";
		$lineitems = array(
				array(
						"serial" => 1,
						"amount" => 1000,
						"description" => "nothing much",
						"item_code"=> $item_code1,
						"qty" => 2,
						"rate" => 500,
						"value" => 1000,
						"discount" => 0,
						"attributes" => array(
								"attribute" => array(
										array("name" => "brand007", "value" => "Levis"),
										array("name" => "size007", "value" => "M")
								)
						)
				),
				array("serial" => 2,
						"amount" => 1000,
						"description" => "not anything",
						"item_code"=> $item_code2,
						"qty" => 2,
						"rate" => 600,
						"value" => 1200,
						"discount" => 200,
						"attributes" => array(
								"attribute" => array(
										array("name" => "brand.01", "value" => "PUMA"),
										array("name" => "size.02", "value" => "XXL")
								)
						)
				)
		);
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$id = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($id, $query_params);
	
		$this->assertEquals($id, $transaction_get_response['transactions']['transaction'][0]['id']);
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item']));
		$this->assertEquals($item_code1, $transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][0]['item_code']);
		$this->assertEquals($item_code2, $transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][1]['item_code']);
		$this->assertEquals(0, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][0]['attributes']['attribute']));
		$this->assertEquals(0, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][1]['attributes']['attribute']));
	}
	
	/**
	 * This will asserts following things for inventory attributes:
	 * 1, should not create new attribute values for existing item code with existing values
	 * 2, should create new attributes for new attribute values 
	 */
	public function testAddTransactionWithLineitem_4()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
			
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
	
		$item_code1 = "$rand_number"."SJDUJK23L1";
		$item_code2 = "$rand_number"."SJDUJK23L2";
		
		$attr_value1 = "Levis=>".$rand_number;
		$attr_value2 = "M=>".$rand_number;
		
		$attr_value3 = "Levis=>1".$rand_number;
		$attr_value4 = "M=>1".$rand_number;
		
		$lineitems = array(
				array(
						"serial" => 1,
						"amount" => 1000,
						"description" => "nothing much",
						"item_code"=> $item_code1,
						"qty" => 2,
						"rate" => 500,
						"value" => 1000,
						"discount" => 0,
						"attributes" => array(
								"attribute" => array(
										array("name" => "brand", "value" => "$attr_value1"),
										array("name" => "size", "value" => "$attr_value2")
								)
						)
				),
				array("serial" => 2,
						"amount" => 1000,
						"description" => "not anything",
						"item_code"=> $item_code2,
						"qty" => 2,
						"rate" => 600,
						"value" => 1200,
						"discount" => 200,
						"attributes" => array(
								"attribute" => array(
										array("name" => "brand", "value" => "$attr_value3"),
										array("name" => "size", "value" => "$attr_value4")
								)
						)
				)
		);
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		include_once 'submodule/inventory.php';
		$inventory = new InventorySubModule();
		
		$attributes_info = array();
		$attributes_values = array();
		
		foreach($attributes AS $attribute)
		{
			$attributes_info[$attribute['name']] = $inventory->getAttributeByName($attribute['name']);
			$values = $inventory->getAttributeValuesForAttribute($attributes_info[$attribute['name']][0]['id']);
			$attributes_values[$attribute['name']] =$values;
		}
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		
		
		$attributes_info1 = array();
		$attributes_values1 = array();
		//asserts if new attribute values are being added
		foreach($attributes AS $attribute)
		{
			$attributes_info1[$attribute['name']] = $inventory->getAttributeByName($attribute['name']);
			$values = $inventory->getAttributeValuesForAttribute($attributes_info1[$attribute['name']][0]['id']);
			$this->assertEquals(count($attributes_values[$attribute['name']]) + 2, count($values));
			$attributes_values1[$attribute['name']] =$values;
		}
		
		$result = $inventory->getAttributeByName($attribute['name']);
		
		//This will add different transaction with same item code
		$transaction['number'] = $transaction['number']."2";
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		
		
		$attributes_info2 = array();
		//checking if no new attribute values are added
		foreach($attributes AS $attribute)
		{
			$attributes_info2[$attribute['name']] = $inventory->getAttributeByName($attribute['name']);
			$values = $inventory->getAttributeValuesForAttribute($attributes_info2[$attribute['name']][0]['id']);
			
			$this->assertEquals(count($attributes_values1[$attribute['name']]), count($values));
		}
		
		$id = $transaction_add_response['transactions']['transaction'][0]['id'];
		$transaction_get_response = $this->getTransaction($id, $query_params);
	
		$this->assertEquals($id, $transaction_get_response['transactions']['transaction'][0]['id']);
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item']));
		$this->assertEquals($item_code1, $transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][0]['item_code']);
		$this->assertEquals($item_code2, $transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][1]['item_code']);
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][0]['attributes']['attribute']));
		$this->assertEquals(2, count($transaction_get_response['transactions']['transaction'][0]['line_items']['line_item'][1]['attributes']['attribute']));
	}
	
	/**
	 * will check for duplicate transaction
	 */
	public function testAddDuplicateTransaction_1()
	{
		$this->setUpConfigForAddDuplicateTransactionAdd_1();
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
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
	public function testAddDuplicateTransaction_2()
	{
		$this->setUpConfigForAddDuplicateTransactionAdd_1();
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems),
				"billing_time" => "2013-10-01 11:11:11"
		);
	
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	
		$transaction['billing_time'] = "2013-11-02 11:11:11";
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response2['transactions']['transaction'][0]['number']);
	
	}
	
	public function testAddTransactionAmountNegative()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		//adding customer --- finishedold
	
		//adding new transaction --- start
		$transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "5%"
						)
				)
		);
	
		$transaction = array(
				"number" => microtime(),
				"amount" => -2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems),
				"billing_time" => "2013-10-01 11:11:11"
		);
	
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(631, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);	
	}
	
	public function testAddTransactionDiscountNegative()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"discount" => -100,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems),
				"billing_time" => "2013-10-01 11:11:11"
		);
	
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(666, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	}
	
	public function testAddTransactionGrossAmountNegative()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
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
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"discount" => 100,
				"gross_amount" => -2100,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems),
				"billing_time" => "2013-10-01 11:11:11"
		);
	
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(665, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	}
	
	/**
	 * will check for duplicate transaction
	 */
	public function testAddTransactionForCustomerUpdate_1()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer",
				"registered_on" => "2013-12-05 11:11:11"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		$customer_get_response1 = $this->getCustomerTest(array("mobile"=> $customer['mobile']));
		$this->assertEquals(200, $customer_get_response1['status']['code']);
		$this->assertEquals(1000, $customer_get_response1['customers']['customer'][0]['item_status']['code']);
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
	
		$customer['firstname'] = "Firstname";
		$customer['lastname'] = "Lastname";
		
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"billing_time" => "2013-12-06 11:11:11",
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	
		$customer_get_response2 = $this->getCustomerTest(array("mobile"=> $customer['mobile']));
		$this->assertEquals(200, $customer_get_response2['status']['code']);
		$this->assertEquals(1000, $customer_get_response2['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($customer['firstname'], $customer_get_response2['customers']['customer'][0]['firstname']);
		$this->assertEquals($customer['lastname'], $customer_get_response2['customers']['customer'][0]['lastname']);
		$this->assertEquals($customer['email'], $customer_get_response2['customers']['customer'][0]['email']);
		$this->assertEquals($customer['external_id'], $customer_get_response2['customers']['customer'][0]['external_id']);
	}

	/**
	 * Auto registration check
	 */
	/**
	 * will check for duplicate transaction
	 */
	public function testAddTransactionAutoRegister()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//set customer --- start
		$rand_number = substr(microtime(true), -9, 6);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "918867".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer",
				"registered_on" => "2013-12-05 11:11:11"
		);
	
		$customer['firstname'] = "Firstname";
		$customer['lastname'] = "Lastname";
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"billing_time" => "2013-12-06 11:11:11",
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
		$this->assertEquals($customer['email'], $transaction_add_response['transactions']['transaction'][0]['customer']['email']);
		$this->assertEquals($customer['external_id'], $transaction_add_response['transactions']['transaction'][0]['customer']['external_id']);
		$this->assertEquals($customer['mobile'], $transaction_add_response['transactions']['transaction'][0]['customer']['mobile']);
	}
	
	/**
	 * will check for duplicate transaction
	 */
	public function testAddTransactionForCustomerUpdate_2()
	{
		$custom_fields_name = array("gender", "dob");
		$this->checkAndCreateCustomFields($custom_fields_name, "loyalty_registration");
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer",
				"registered_on" => "2013-12-06 11:11:11",
				"billing_time" => "2013-12-07 11:11:11",
				"custom_fields" => array(
						"field" => array(
									array("name" => "gender", "value" => "M"),
									array("name" => "dob", "value" => "1990-08-16")
								) 
						)
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		$customer_get_response1 = $this->getCustomerTest(array("mobile"=> $customer['mobile']));
		$this->assertEquals(200, $customer_get_response1['status']['code']);
		$this->assertEquals(1000, $customer_get_response1['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($customer['custom_fields']['field'][0]['name'], $customer_get_response1['customers']['customer'][0]['custom_fields']['field'][0]['name']);
		$this->assertEquals($customer['custom_fields']['field'][0]['value'], $customer_get_response1['customers']['customer'][0]['custom_fields']['field'][0]['value']);
		$this->assertEquals($customer['custom_fields']['field'][1]['name'], $customer_get_response1['customers']['customer'][0]['custom_fields']['field'][1]['name']);
		$this->assertEquals($customer['custom_fields']['field'][1]['value'], $customer_get_response1['customers']['customer'][0]['custom_fields']['field'][1]['value']);
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
	
		$customer['firstname'] = "Firstname";
		$customer['lastname'] = "Lastname";
		$customer['custom_fields']['field'] = array(
					array("name" => "gender", "value" => "Male"),
					array("name" => "dob", "value" => "1991-08-16")
				);
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	
		$customer_get_response2 = $this->getCustomerTest(array("mobile"=> $customer['mobile']));
		$this->assertEquals(200, $customer_get_response2['status']['code']);
		$this->assertEquals(1000, $customer_get_response2['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($customer['firstname'], $customer_get_response2['customers']['customer'][0]['firstname']);
		$this->assertEquals($customer['lastname'], $customer_get_response2['customers']['customer'][0]['lastname']);
		$this->assertEquals($customer['email'], $customer_get_response2['customers']['customer'][0]['email']);
		$this->assertEquals($customer['external_id'], $customer_get_response2['customers']['customer'][0]['external_id']);
		$this->assertEquals($customer['custom_fields']['field'][0]['name'], $customer_get_response2['customers']['customer'][0]['custom_fields']['field'][0]['name']);
		$this->assertEquals($customer['custom_fields']['field'][0]['value'], $customer_get_response2['customers']['customer'][0]['custom_fields']['field'][0]['value']);
		$this->assertEquals($customer['custom_fields']['field'][1]['name'], $customer_get_response2['customers']['customer'][0]['custom_fields']['field'][1]['name']);
		$this->assertEquals($customer['custom_fields']['field'][1]['value'], $customer_get_response2['customers']['customer'][0]['custom_fields']['field'][1]['value']);
	}
	
	/**
	 * test customer data should not update in v1 API in transaction/add
	 */
	public function testAddTransactionForV1CustomerDataUpdate()
	{
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer",
				"registered_on" => "2013-12-05 11:11:11"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		$customer_get_response1 = $this->getCustomerTest(array("mobile"=> $customer['mobile']));
		$this->assertEquals(200, $customer_get_response1['status']['code']);
		$this->assertEquals(1000, $customer_get_response1['customers']['customer'][0]['item_status']['code']);
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
	
		$customer2 = $customer;
		$customer2['firstname'] = "Firstname";
		$customer2['lastname'] = "Lastname";
	
		$transaction = array(
				"transaction_number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer2,
				"billing_time" => "2013-12-06 11:11:11",
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		global $gbl_api_version;
		$gbl_api_version = "v1";
		
		$arr_request_transactions = array();
		$arr_request_transactions[] = $transaction;
		$request_arr = array("root" =>
				array("transaction" => $arr_request_transactions));
		
		$transaction_add_response = $this->transactionResourceObj->process("v1", "add", $request_arr, $query_params, "POST");
		$this->logger->debug("Transaction Add Response: ".print_r($response, true));
		
		unset($gbl_api_version);
		
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	
		$customer_get_response2 = $this->getCustomerTest(array("mobile"=> $customer['mobile']));
		$this->assertEquals(200, $customer_get_response2['status']['code']);
		$this->assertEquals(1000, $customer_get_response2['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($customer['firstname'], $customer_get_response2['customers']['customer'][0]['firstname']);
		$this->assertEquals($customer['lastname'], $customer_get_response2['customers']['customer'][0]['lastname']);
		$this->assertEquals($customer['email'], $customer_get_response2['customers']['customer'][0]['email']);
		$this->assertEquals($customer['external_id'], $customer_get_response2['customers']['customer'][0]['external_id']);
	}
	
	/**
	 * tests loyalty_registration custom fields update in transaction add for v1 APIs
	 */
	public function testAddTransactionForV1CustomerCustomFieldsUpdate()
	{
		$custom_fields_name = array("gender", "dob");
		$this->checkAndCreateCustomFields($custom_fields_name, "loyalty_registration");
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."0customer@capillarytech.com",
				"mobile" => "9188671".$rand_number,
				"external_id" => "EXT_0".$rand_number,
				"firstname" => "Customer",
				"registered_on" => "2013-12-06 11:11:11",
				"billing_time" => "2013-12-07 11:11:11",
				"custom_fields" => array(
						"field" => array(
								array("name" => "gender", "value" => "M"),
								array("name" => "dob", "value" => "1990-08-16")
						)
				)
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		$customer_get_response1 = $this->getCustomerTest(array("mobile"=> $customer['mobile']));
		$this->assertEquals(200, $customer_get_response1['status']['code']);
		$this->assertEquals(1000, $customer_get_response1['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($customer['custom_fields']['field'][0]['name'], $customer_get_response1['customers']['customer'][0]['custom_fields']['field'][0]['name']);
		$this->assertEquals($customer['custom_fields']['field'][0]['value'], $customer_get_response1['customers']['customer'][0]['custom_fields']['field'][0]['value']);
		$this->assertEquals($customer['custom_fields']['field'][1]['name'], $customer_get_response1['customers']['customer'][0]['custom_fields']['field'][1]['name']);
		$this->assertEquals($customer['custom_fields']['field'][1]['value'], $customer_get_response1['customers']['customer'][0]['custom_fields']['field'][1]['value']);
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
	
		$customer2 = $customer;
		$customer2['firstname'] = "Firstname";
		$customer2['lastname'] = "Lastname";
		$customer2['custom_fields']['field'] = array(
				array("name" => "gender", "value" => "Male"),
				array("name" => "dob", "value" => "1991-08-16")
		);
	
		$transaction = array(
				"transaction_number" => microtime(),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer2,
				"custom_fields" => $transaction_custom_fields,
				"line_items" => array("line_item" => $lineitems)
		);
	
		global $gbl_api_version;
		$gbl_api_version = "v1";
		
		$arr_request_transactions = array();
		$arr_request_transactions[] = $transaction;
		$request_arr = array("root" =>
				array("transaction" => $arr_request_transactions));
		
		$transaction_add_response = $this->transactionResourceObj->process("v1", "add", $request_arr, $query_params, "POST");
		$this->logger->debug("Transaction Add Response: ".print_r($response, true));
		
		unset($gbl_api_version);
		$this->assertEquals(600, $transaction_add_response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response['transactions']['transaction'][0]['number']);
	
		$customer_get_response2 = $this->getCustomerTest(array("mobile"=> $customer['mobile']));
		$this->assertEquals(200, $customer_get_response2['status']['code']);
		$this->assertEquals(1000, $customer_get_response2['customers']['customer'][0]['item_status']['code']);
		$this->assertEquals($customer['firstname'], $customer_get_response2['customers']['customer'][0]['firstname']);
		$this->assertEquals($customer['lastname'], $customer_get_response2['customers']['customer'][0]['lastname']);
		$this->assertEquals($customer['email'], $customer_get_response2['customers']['customer'][0]['email']);
		$this->assertEquals($customer['external_id'], $customer_get_response2['customers']['customer'][0]['external_id']);
		$this->assertEquals($customer2['custom_fields']['field'][0]['name'], $customer_get_response2['customers']['customer'][0]['custom_fields']['field'][0]['name']);
		$this->assertEquals($customer2['custom_fields']['field'][0]['value'], $customer_get_response2['customers']['customer'][0]['custom_fields']['field'][0]['value']);
		$this->assertEquals($customer2['custom_fields']['field'][1]['name'], $customer_get_response2['customers']['customer'][0]['custom_fields']['field'][1]['name']);
		$this->assertEquals($customer2['custom_fields']['field'][1]['value'], $customer_get_response2['customers']['customer'][0]['custom_fields']['field'][1]['value']);
	}
	
	/**
	 * will check for duplicate transaction
	 */
	public function testTransactionDateLimit()
	{
		$this->setUpConfigForAddDuplicateTransactionAdd_1();
	
		$customer = array(
				"email" => "test@example.com",
				"mobile" => "1111111",
				"name" => "test man",
		);
		
		//adding new transaction --- start
	
		$transaction = array(
				"number" => microtime(),
				"billing_time" => "1994-12-31",
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"line_items" => array("line_item" => $lineitems)
		);
	
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(644, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response2['transactions']['transaction'][0]['number']);
		$this->assertContains('Transaction date too behind in past', $transaction_add_response2['transactions']['transaction'][0]['item_status']['message']);
		
		// future date
		$transaction = array(
				"number" => microtime(),
				"billing_time" => date('Y-m-d', strtotime("+2day")),
				"amount" => 2000,
				"type" => "REGULAR",
				"customer" => $customer,
				"line_items" => array("line_item" => $lineitems)
		);
		
		$transaction_add_response2 = $this->addTransactionTest($transaction, $query_params, false);
		$this->assertEquals(643, $transaction_add_response2['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals($transaction['number'], $transaction_add_response2['transactions']['transaction'][0]['number']);
		$this->assertContains('Transaction date too ahead in the future', $transaction_add_response2['transactions']['transaction'][0]['item_status']['message']);
		
	}
	
	public function setUp()
	{
		$this->login("till.005", "123");
		parent::setUp();
		$peContext = new \Api\UnitTest\Context('pointsengine');
		$peContext->set("response/constant", true);
	}
	
	public function setUpConfigForAddDuplicateTransactionAdd_1()
	{
		$newOrgConfigData = array(
				"CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE" => 1,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_STORE" => 0,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_TILL" => 1,
				"CONF_LOYALTY_BILL_NUMBER_UNIQUE_IN_DAYS" => 30,
				"CONF_MIN_BILLING_DATE" => '1995-01-01',
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
