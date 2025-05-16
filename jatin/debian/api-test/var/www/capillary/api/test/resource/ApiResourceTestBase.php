<?
include_once('test/ApiTestBase.php');
include_once('resource/customer.php');
include_once('resource/transaction.php');
include_once('resource/points.php');
include_once('resource/coupon.php');
include_once('resource/organization.php');
include_once('resource/organization.php');
include_once('resource/associate.php');
include_once('resource/communications.php');
include_once('resource/product.php');
include_once('resource/stores.php');
include_once('resource/tasks.php');
include_once('resource/feedback.php');
include_once('resource/request.php');

class ApiResourceTestBase extends ApiTestBase
{
    protected $transactionResourceObj;
	protected $customerResourceObj;
	protected $pointsResourceObj;
	protected $couponResourceObj;
    protected $organizationResourceObj;
    protected $storeResourceObj;
    protected $associateResourceObj;
    protected $feedbackResourceObj;
    protected $communicationsResourceObj;
    protected $tasksResourceObj;
    protected $requestResourceObj;

	
	public function __construct()
	{
		parent::__construct();
	}
	
	protected function addCustomerTest($customer, $query_params, $assert_success = true)
	{	
		$arr_request_customers = array();
		$arr_request_customers[] = $customer;
		$request_arr = array("root" =>
				array("customer" => $arr_request_customers)
		);
		$this->logger->debug("Going to add new customer");
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		
		$response = $this->customerResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		
		unset($gbl_api_version);
		$this->logger->debug("Customer Add Response: ".print_r($response, true));
		if($assert_success)
		{
			$this->assertEquals(200, $response['status']['code']);
			$this->assertEquals(1000, $response['customers']['customer'][0]['item_status']['code']);
		}
		else 
		{
			$this->logger->debug("addCustomerTest => skiping assert");
		}
	
		return $response;
	}
	
	protected function getCustomerTest($query_params)
	{
		$this->logger->debug("Going to get customers");
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		
		$response = $this->customerResourceObj->process("v1.1", "get", null, $query_params, "GET");
		$this->logger->debug("Customer Get Response: ".print_r($response, true));
		
		unset($gbl_api_version);
		return $response;
	}
	
	protected function addTransactionTest($transaction, $query_params, $assert_success = true)
	{
		$arr_request_transactions = array();
		$arr_request_transactions[] = $transaction;
		$request_arr = array("root" =>
				array("transaction" => $arr_request_transactions));
		$this->logger->debug("Going to add new transaction");
		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		
		$response = $this->transactionResourceObj->process("v1.1", "add", $request_arr, $query_params, "POST");
		$this->logger->debug("Transaction Add Response: ".print_r($response, true));
		
		unset($gbl_api_version);
		if($assert_success)
		{
			$this->assertEquals(200, $response['status']['code']);
			$this->assertEquals(600, $response['transactions']['transaction'][0]['item_status']['code']);
		}
		else 
		{
			$this->logger->debug("addTransactionTest => skiping assert");
		}
		return $response;
	}
	
	protected function getTransaction($id, $query_params)
	{
		$request_query_params = array("id" => $id);
		$request_query_params = array_merge($query_params, $request_query_params);

		global $gbl_api_version;
		$gbl_api_version = "v1.1";
		$response = $this->transactionResourceObj->process("v1.1", "get", null,	 $request_query_params, "GET");
		
		unset($gbl_api_version);
		return $response;
	}

    protected function getStore($id, $query_params)
    {
        $request_query_params = array("id" => $id);
        $request_query_params = array_merge($query_params, $request_query_params);
        $response = $this->storeResourceObj->process("v1.1", "get", null, $request_query_params,"GET");
        return $response;
    }

    protected function getOrganization()
    {
        $response = $this->organizationResourceObj->process("v1.1", "get", null, null,"GET");
        return $response;
    }
	
	protected function checkAndCreateInventoryAttributes($attributes)
	{
		include_once 'submodule/inventory.php';
		$inventory = new InventorySubModule();
		foreach($attributes AS $attribute)
		{
			$result = $inventory->getAttributeByName($attribute['name']);
			if(!$result || count($result) == 0)
			{
				$result = $inventory->createAttribute($attribute['name'], 'false', NULL, NULL, 'String', 1, 1);
			}
		}
	}
	
	/**
	 * This will create new customfields if given custom fields are not exists in organization
	 * @param unknown_type $custom_fields_name
	 * @param unknown_type $scope
	 */
	protected function checkAndCreateCustomFields($custom_fields_name, $scope)
	{
		$cf = new CustomFields();
		$arr_cf = $cf->getCustomFieldsByScope($this->currentorg->org_id, $scope);
		$new_arr_cf = array();
		foreach ($arr_cf AS $temp_cf)
		{
			$new_arr_cf[strtolower($temp_cf['name'])] = $temp_cf;
		}
		$arr_new_cf_name  = array();
		foreach ($custom_fields_name AS $cf_name)
		{
			if(!isset($new_arr_cf[strtolower($cf_name)]))
			{
				$arr_new_cf_name[] = $cf_name;
			}
		}
		$this->logger->debug("Custom Fields that needs to be created: ".print_r($arr_new_cf_name, true));
		foreach($arr_new_cf_name AS $temp_name)
		{
			$temp_param = array();
			$temp_param['f_attrs'] = "";
			$temp_param['f_default'] = "";
			$temp_param['f_scope'] = "loyalty_transaction";
			$temp_param['f_name'] = $temp_name;
			$temp_param['f_type'] = "text";
			$temp_param['f_datatype'] = "String";
			$temp_param['f_label'] = $temp_name;
			$temp_param['f_phase'] = 1;
			$temp_param['f_position'] = 1;
			$temp_param['f_rule'] = "";
			$temp_param['f_server_rule'] = "";
			$temp_param['f_regex'] = "";
			$temp_param['f_helptext'] = "";
			$temp_param['f_error'] = "";
			$temp_param['f_is_disabled'] = "0";
			$temp_param['f_is_compulsory'] = "1";
			$temp_param['f_disable_at_server'] = "0";
			$cf->processCustomFieldCreationForm($this->currentorg->org_id, false,false, $temp_param);
		}
	}
	
	protected function setUp()
	{
		$this->customerResourceObj = new CustomerResource();
		$this->transactionResourceObj = new TransactionResource();
		$this->pointsResourceObj = new PointsResource();
		$this->couponResourceObj = new CouponResource();
        $this->organizationResourceObj = new OrganizationResource();
        $this->storeResourceObj = new StoreResource();
        $this->associateResourceObj = new AssociateResource();
        $this->feedbackResourceObj = new FeedbackResource();
        $this->communicationsResourceObj = new CommunicationsResource();
        $this->tasksResourceObj = new TaskResource();
        $this->requestResourceObj=new RequestResource();
	}
}

class ApiUnitCfg{
	
	private static $mock_mode=true;
	private static $cfg;

	public static function getMode()
	{
		return self::$mock_mode;
	}
	
	public static function getCfg()
	{
		return self::$cfg;
	}
	
	public static function setCfg($cfg)
	{
		global $cfg;
		self::$cfg=$cfg;
	}
	
}

ApiUnitCfg::setCfg($cfg);

?>
