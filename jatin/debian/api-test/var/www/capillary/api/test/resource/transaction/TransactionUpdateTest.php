<?
require_once('test/resource/transaction/ApiTransactionResourceTestBase.php');

class TransactionUpdateTest extends ApiTransactionResourceTestBase
{
	private $preservedOrgConfigsData = array();
	
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * update 1 custom field with mobile number and transaction number
	 */
	public function testTransactionUpdate_1()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
		
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
		
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
		
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$transaction['customer'] = array("mobile" => $customer['mobile']);
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * update 1 custom field with mobile number and transaction number
	 */
	public function testTransactionUpdate_2()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$transaction['customer'] = array("email" => $customer['email']);
	
		$response = $this->updateTransaction($transaction, $customer, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 3, update 1 custom field with external_id and transaction number
	 */
	public function testTransactionUpdate_3()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$transaction['customer'] = array("external_id" => $customer['external_id']);
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 4, update 1 custom field with user_id and transaction number
	 */
	public function testTransactionUpdate_4()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$transaction['customer'] = array("id" => $customer_add_response['customers']['customer'][0]['user_id']);
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 5, update 1 custom field with mobile number and transaction id
	 */
	//TODO: check for mobile
	public function testTransactionUpdate_5()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		//ERROR is coming: Customer registration successful, Mobile is invalid and primary key is not mobile, ignoring it, Failed to get point details, Failed to get point details
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		
		$transaction['customer'] = array("mobile" => $customer['mobile']);
		$transaction['id'] = $transaction_add_response['transactions']['transaction'][0]['id'];
		unset($transaction['number']);
		
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 6, update 1 custom field with email and transaction id
	 */
	public function testTransactionUpdate_6()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
	
		$transaction['customer'] = array("email" => $customer['email']);
		$transaction['id'] = $transaction_add_response['transactions']['transaction'][0]['id'];
		unset($transaction['number']);
	
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	
	/**
	 * 7, update 1 custom field with external_id and transaction id
	 */
	public function testTransactionUpdate_7()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
	
		$transaction['customer'] = array("external_id" => $customer['external_id']);
		$transaction['id'] = $transaction_add_response['transactions']['transaction'][0]['id'];
		unset($transaction['number']);
	
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 8, update 1 custom field with user_id and transaction id
	 */
	public function testTransactionUpdate_8()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
	
		$transaction['customer'] = array("id" => $customer_add_response['customers']['customer'][0]['user_id']);
		$transaction['id'] = $transaction_add_response['transactions']['transaction'][0]['id'];
		unset($transaction['number']);
	
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * update multiple custom fields with proper identifiers (all four Identifiers)
	 */
	public function testTransactionUpdate_9()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT", "HomeDelivery");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
		global $logger, $cfg, $currentuser, $currentorg;

		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
								),
								array(
									"name" => "HomeDelivery",
									"value" => "false"
								)
							)
					);
		$transaction = array(
				"number" => microtime(),
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
		
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished

		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						),
						array(
								"name" => "HomeDelivery",
								"value" => "true"
						)
				)
		);

		$transaction['custom_fields'] = $new_transaction_custom_fields;

		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(2, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 10, update custom field with invalid transaction number
	 */
	public function testTransactionUpdate_10()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT", "HomeDelivery");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
		global $logger, $cfg, $currentuser, $currentorg;
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		//adding customer --- finished
		$transaction = array(
				"number" => microtime(),
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer
		);
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
	
		$response = $this->updateTransaction($transaction, $query_params, false);
		$this->assertEquals(500, $response['status']['code']);
		$this->assertEquals(668, $response['transactions']['transaction'][0]['item_status']['code']);
		//updating transaction --- finished
	}
	
	/**
	 * 11, update custom field with invalid transaction id
	 */
	public function testTransactionUpdate_11()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT", "HomeDelivery");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
		global $logger, $cfg, $currentuser, $currentorg;
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		//adding customer --- finished
		$transaction = array(
				"id" => -1,
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer
		);
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
	
		$response = $this->updateTransaction($transaction, $query_params, false);
		$this->assertEquals(500, $response['status']['code']);
		$this->assertEquals(667, $response['transactions']['transaction'][0]['item_status']['code']);
		//updating transaction --- finished
	}
	
	/**
	 * 12, update custom field with invalid customer identifier (either with mobile, email, external_id or user id)
	 */
	public function testTransactionUpdate_12()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
		);

		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
	
		$transaction['customer'] = array("id" => -1);
		$transaction['id'] = $transaction_add_response['transactions']['transaction'][0]['id'];
		unset($transaction['number']);
	
		$response = $this->updateTransaction($transaction, $query_params, false);
		$this->assertEquals(500, $response['status']['code']);
		$this->assertEquals(656, $response['transactions']['transaction'][0]['item_status']['code']);
		//updating transaction --- finished
	}
	
	/**
	 * 13, update 1 custom field with invalid custom field name
	 */
	public function testTransactionUpdate_13()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT1",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$transaction['customer'] = array("external_id" => $customer['external_id']);
		$response = $this->updateTransaction($transaction, $query_params,false);
		$this->assertEquals(500, $response['status']['code']);
		$this->assertEquals(669, $response['transactions']['transaction'][0]['item_status']['code']);
		/*$this->assertEquals(0, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['failure_count']);*/
		//updating transaction --- finished
	}
	
	/**
	 * 14, update 5 custom field with 3 invalid custom field name and 2 valid custom field
	 */
	public function testTransactionUpdate_14()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT", "HomeDelivery");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
						),
						array(
								"name" => "HomeDelivery",
								"value" => "true"
						)
				)
		);
		$transaction = array(
				"number" => microtime(),
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						),
						array(
								"name" => "HomeDelivery",
								"value" => "false"
						),
						array(
								"name" => "HomeDelivery2",
								"value" => "false"
						),
						array(
								"name" => "HomeDelivery3",
								"value" => "false"
						),
						array(
								"name" => "HomeDelivery4",
								"value" => "false"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$transaction['customer'] = array("external_id" => $customer['external_id']);
		$response = $this->updateTransaction($transaction, $query_params,false);
		$this->assertEquals(200, $response['status']['code']);
		$this->assertEquals(600, $response['transactions']['transaction'][0]['item_status']['code']);
		$this->assertEquals(2, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		$this->assertEquals(3, $response['transactions']['transaction'][0]['custom_fields']['failure_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 15, try to update transaction without giving <transaction> tag
	 */
	public function testTransactionUpdate_15()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		$transaction = array(
				"number" => microtime(),
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
		);
	
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
	
		$transaction['customer'] = array("id" => -1);
		$transaction['id'] = $transaction_add_response['transactions']['transaction'][0]['id'];
		unset($transaction['number']);
	
		$request_arr = array("root" => null);
		try{
			$response = $this->transactionResourceObj->process("v1.1", "update", $request_arr, $query_params, "POST");
		}catch(Exception $e)
		{
			$this->assertEquals(400, $e->getCode());
		}
		
		//updating transaction --- finished
	}
	
	/**
	 * 16, try to update transaction for blank transaction number which is valid
	 */
	public function testTransactionUpdate_16()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$this->setUpConfigForTransactionUpdate_16();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"number" => "",
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$response = $this->updateTransaction($transaction, $query_params, false);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 17, try to update transaction with duplicate transaction number for different user
	 */
	public function testTransactionUpdate_17()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$this->setUpConfigForTransactionUpdate_17();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer1 = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer1, $query_params);
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
		$transaction_number= microtime();
		$transaction1 = array(
				"number" => $transaction_number,
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer1,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response1 = $this->addTransactionTest($transaction1, $query_params);
		$id1 = $transaction_add_response1['transactions']['transaction'][0]['id'];
		//adding new transaction --- finished
	
		$rand_number = rand(10000, 99999);
		$customer2 = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
				"firstname" => "Customer"
		);
		
		$query_params = array("user_id" => "true");
		$customer_add_response2 = $this->addCustomerTest($customer2, $query_params);
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
		$transaction2 = array(
				"number" => $transaction_number,
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer2,
				"custom_fields" => $transaction_custom_fields
		);
		
		$transaction_add_response2 = $this->addTransactionTest($transaction2, $query_params);
		$id2 = $transaction_add_response2['transactions']['transaction'][0]['id'];
		//adding new transaction --- finished
		
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "15%"
						)
				)
		);
	
		$transaction1['custom_fields'] = $new_transaction_custom_fields;
		$response1 = $this->updateTransaction($transaction1, $query_params);
		
		$transaction2['custom_fields'] = $new_transaction_custom_fields;
		$response2 = $this->updateTransaction($transaction2, $query_params);
		
		//TODO: assert with transaction id
		$this->assertEquals($id1, $response1['transactions']['transaction'][0]['id']);
		$this->assertEquals($id2, $response2['transactions']['transaction'][0]['id']);
		$this->assertEquals(1, $response1['transactions']['transaction'][0]['custom_fields']['success_count']);
		$this->assertEquals(1, $response2['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 18, try to update transaction with duplicate transaction number for same user 
	 */
	public function testTransactionUpdate_18()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$this->setUpConfigForTransactionUpdate_17();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer1 = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
				"firstname" => "Customer"
		);
	
		$query_params = array("user_id" => "true");
		$customer_add_response = $this->addCustomerTest($customer1, $query_params);
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
		$transaction_number= microtime();
		$transaction1 = array(
				"number" => $transaction_number,
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer1,
				"custom_fields" => $transaction_custom_fields
		);
		$transaction1['billing_time'] = '2013-11-22 12:55:00';
	
		$transaction_add_response1 = $this->addTransactionTest($transaction1, $query_params);
		$id1 = $transaction_add_response1['transactions']['transaction'][0]['id'];
		//adding new transaction --- finished
	
		//adding new transaction --- start
		$transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "5%"
						)
				)
		);
		$transaction2 = array(
				"number" => $transaction_number,
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer1,
				"custom_fields" => $transaction_custom_fields
		);
		
		$transaction2['billing_time'] = '2013-11-22 13:55:00';
	
		$transaction_add_response2 = $this->addTransactionTest($transaction2, $query_params);
		$id2 = $transaction_add_response2['transactions']['transaction'][0]['id'];
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "15%"
						)
				)
		);
	
		$transaction1['custom_fields'] = $new_transaction_custom_fields;
		$response1 = $this->updateTransaction($transaction1, $query_params, false);
	
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "16%"
						)
				)
		);
		$transaction2['custom_fields'] = $new_transaction_custom_fields;
		unset($transaction2['billing_time']);
		$response2 = $this->updateTransaction($transaction2, $query_params, false);
		
		//TODO: assert with transaction id
		$this->assertEquals($id2, $response1['transactions']['transaction'][0]['id']);
		$this->assertEquals($id2, $response2['transactions']['transaction'][0]['id']);
		$this->assertEquals(1, $response1['transactions']['transaction'][0]['custom_fields']['success_count']);
		$this->assertEquals(1, $response2['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 19, passing blank transaction id 
	 */
	public function testTransactionUpdate_19()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$transaction['customer'] = array("mobile" => $customer['mobile']);
		$transaction['id'] = "";
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(1, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 20, passing blank number and id
	 */
	public function testTransactionUpdate_20()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = $new_transaction_custom_fields;
		$transaction['customer'] = array("mobile" => $customer['mobile']);
		$transaction['id'] = "";
		$transaction['number'] = "";
		$response = $this->updateTransaction($transaction, $query_params, false);
		$this->assertEquals(668, $response['transactions']['transaction'][0]['item_status']['code']);
		//updating transaction --- finished
	}
	
	/**
	 * 21 sending blank custom fields 
	 */
	public function testTransactionUpdate_21()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
				"amount" => 2001,
				"type" => "REGULAR",
				"customer" => $customer,
				"custom_fields" => $transaction_custom_fields
		);
	
		$transaction_add_response = $this->addTransactionTest($transaction, $query_params);
		//adding new transaction --- finished
	
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = array();
		$transaction['customer'] = array("mobile" => $customer['mobile']);
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(0, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	/**
	 * 22, transaction add and update with lineitems
	 */
	public function testTransactionUpdate_22()
	{
		$this->setUpConfigForTransactionUpdate_1();
		$custom_fields_name = array("VAT");
		$scope = "loyalty_transaction";
		$this->checkAndCreateCustomFields($custom_fields_name, $scope);
		$attributes = array(array("name" => "brand"), array("name" => "size"));
		$this->checkAndCreateInventoryAttributes($attributes);
	
		//adding customer --- start
		$rand_number = rand(10000, 99999);
		$customer = array(
				"email" => $rand_number."customer@capillarytech.com",
				"mobile" => "9188677".$rand_number,
				"external_id" => "EXT_".$rand_number,
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
		
		$lineitems = array(
					array(
							"serial" => 1,
							"amount" => 1000,
							"description" => "nothing much",
							"item_code"=>"$rand_number"."SJDUJK23L1",
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
							"item_code"=>"$rand_number"."SJDUJK23L2",
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
		//adding new transaction --- finished
		
		//updating new transaction --- start
		$new_transaction_custom_fields = array(
				"field" => array(
						array(
								"name" => "VAT",
								"value" => "10%"
						)
				)
		);
	
		$transaction['custom_fields'] = array();
		$transaction['customer'] = array("mobile" => $customer['mobile']);
		$response = $this->updateTransaction($transaction, $query_params);
		$this->assertEquals(2, count($response['transactions']['transaction'][0]['line_items']['line_item']));
		$this->assertEquals(2, count($response['transactions']['transaction'][0]['line_items']['line_item'][0]['attributes']['attribute']));
		$this->assertEquals(2, count($response['transactions']['transaction'][0]['line_items']['line_item'][1]['attributes']['attribute']));
		$this->assertEquals(0, $response['transactions']['transaction'][0]['custom_fields']['success_count']);
		//updating transaction --- finished
	}
	
	protected function updateTransaction($transaction, $query_params, $should_assert = true)
	{
		
		$arr_request_transactions = array();
		$arr_request_transactions[] = $transaction;
		$request_arr = array("root" =>
				array("transaction" => $arr_request_transactions));
		$this->logger->debug("Going to update transaction");
		$response = $this->transactionResourceObj->process("v1.1", "update", $request_arr, $query_params, "POST");
		$this->logger->debug("Transaction Update Response: ".print_r($response, true));
		
		if($should_assert)
		{
			$this->assertEquals(200, $response['status']['code']);
			$this->assertEquals(600, $response['transactions']['transaction'][0]['item_status']['code']);
		}
		else
		{
			$this->logger->debug("updateTransaction => skiping assert");
		}
		return $response;
	}
	
	public function setUpConfigForTransactionUpdate_1()
	{
		$newOrgConfigData = array(
					"CONF_CLIENT_EXTERNAL_ID_MIN_LENGTH" => 6,
					"CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH" => 11
				);
		
		$cm = new ConfigManager();
		foreach($newOrgConfigData AS $key => $value)
		{
			$current_value = $cm->getKeyValueForOrg($key, $this->currentorg->org_id);
			//$current_value = $cf->getConfigKey($key);
			$this->preservedOrgConfigsData[$key] = $current_value;
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=-1;
			$key_value['value']=$value;
			
			$cm->setKeyValue($key, $key_value);
		}
	}
	
	public function setUpConfigForTransactionUpdate_16()
	{
		$newOrgConfigData = array(
				"CONF_LOYALTY_IS_BILL_NUMBER_REQUIRED" => 0,
				"CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE" => 0
		);
	
		$cm = new ConfigManager();
		foreach($newOrgConfigData AS $key => $value)
		{
			$current_value = $cm->getKeyValueForOrg($key, $this->currentorg->org_id);
			//$current_value = $cf->getConfigKey($key);
			$this->preservedOrgConfigsData[$key] = $current_value;
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=-1;
			$key_value['value']=$value;
				
			$cm->setKeyValue($key, $key_value);
		}
	}
	
	public function setUpConfigForTransactionUpdate_17()
	{
		$newOrgConfigData = array(
				"CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE" => 0
		);
	
		$cm = new ConfigManager();
		foreach($newOrgConfigData AS $key => $value)
		{
			$current_value = $cm->getKeyValueForOrg($key, $this->currentorg->org_id);
			//$current_value = $cf->getConfigKey($key);
			$this->preservedOrgConfigsData[$key] = $current_value;
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=-1;
			$key_value['value']=$value;
	
			$cm->setKeyValue($key, $key_value);
		}
	}

	public function setUp()
	{
		//$this->login("store.server", "1234");
		$this->login("till.005", "123");
		parent::setUp();
		$peContext = new \Api\UnitTest\Context('pointsengine');
		$peContext->set("response/constant", true);
		//reseting 
		$this->preservedOrgConfigsData = array();
	}

	//this will reset configs
	public function tearDown()
	{
		//reseting 
		$cm = new ConfigManager();
		foreach($this->preservedOrgConfigsData AS $name => $value)
		{
			$key_value=array();
			$key_value['scope']='ORG';
			$key_value['entity_id']=-1;
			$key_value['value']=$value;
			
			$cm->setKeyValue($name, $key_value);
		}
	}
	
}
?>
