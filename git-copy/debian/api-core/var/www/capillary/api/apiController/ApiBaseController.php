<?php 
/**
 * 
 * ApiBaseController has all the global objects defined in here.
 * 
 * currentuser INSTANCE OF LoggableUserModelExtension
 * currentorg INSTANCE OF ApiOrganizationModelExtension
 * logged_in_user INSTANCE OF AdminUser or StoreUnit
 * logger INSTANCE OF ShopBookLogger
 *  
 * Auth INSTANCE OF Auth
 * @author prakhar
 *
 */
class ApiBaseController{

	protected $logger;
	protected $org_model;
	protected $currentorg;
	protected $currentuser;
	protected $logged_in_user;
	
	public $org_id;
	public $user_id;
	
	protected $Auth;
	
	private $error_keys;
	private $error_responses;
	public function __construct( &$error_responses = false, &$error_keys = false ){
		
		global $currentorg, $currentuser, $logger;
		
		$this->currentorg = $this->org_model = &$currentorg;
		$this->org_id = $currentorg->org_id;
		
		$this->currentuser = &$currentuser;
		$this->user_id = $currentuser->user_id;	

		$this->Auth = Auth::getInstance();
		$this->logged_in_user = $this->Auth->getLoggedInUser();
		
		$this->logger = &$logger;
		
		if( $error_responses && $error_keys ){
			
			$this->error_responses = $error_responses; 
			$this->error_keys = $error_keys;
		}
					
	}
	
	/**
	 * Returns the message attached to the code
	 * @param unknown_type $err_code
	 */
	public function getResponseErrorMessage( $err_code ) {
		
		if ($err_code > 0) 
			return "SUCCESS";
			
		return $this->error_responses[$err_code];
	}
	
	/**
	 * Returns the key for the error
	 * 
	 * @param unknown_type $err_code
	 */
	public function getResponseErrorKey( $err_code ){
		
		if ($err_code > 0) 
			$err_code = SUCCESS;
			
		return $this->error_keys[$err_code];
	}	
	
	/**
	 * @return $OrganizationModelExtension
	 */
	public function getOrganizationModel(){
		
		return $this->org_model;
	}
	
	/**
	 * The exception raised by the model need to be converted 
	 * according to the contract exposed by the business controller 
	 */
	private function responseDataConversionByContract(){}
	
}

?>
