<?
include_once('test/resource/ApiResourceTestBase.php');
include_once('resource/request.php');

class ApiRequestResourceTestBase extends ApiResourceTestBase
{

	public function __construct()
	{
		parent::__construct();
	}
	
	protected function setAutoApprove($on=true)
	{
		if($on)
		{
			$_GET['client_auto_approve']=$_REQUEST['client_auto_approve']="true";
			return array('client_auto_approve'=>'true');
		}else
		{
			$_GET['client_auto_approve']=$_REQUEST['client_auto_approve']="false";
			return array('client_auto_approve'=>'false');
		}
	}
	
	protected function addCustomer()
	{
	
		$rand_number = rand(1100, 9999);
		$customer = array(
				"email" => $rand_number."vimal@capillarytech.com",
				"mobile" => "91974079".$rand_number,
				"external_id" => "VIM_".$rand_number,
				"firstname" => "UT",
				"lastname" => 'Customer'
		);
	
		$query_params = array("user_id" => "true");
		global $gbl_api_version;
		$gbl_api_version = "v1";
		$customer_add_response = $this->addCustomerTest($customer, $query_params);
		$customer['id']=$customer_add_response['customers']['customer'][0]['user_id'];
	
		return $customer;
	
	}
	
}



class req_data_cache
{

	static $cache;

	static function get($key)
	{
		return self::$cache[$key];
	}

	static function set($key,$value)
	{
		self::$cache[$key]=$value;
	}

}



?>
