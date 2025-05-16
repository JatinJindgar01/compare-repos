<?php

include_once 'apiController/ApiBaseController.php';
include_once "apiHelper/Errors.php";
//TODO: referes to cheetah
include_once "helper/Util.php";
include_once 'apiModel/class.ApiAssociates.php';
include_once 'apiModel/class.ApiAssociateModelExtension.php';
//TODO: referes to cheetah
include_once 'business_controller/enum_types/LoggableType.php';

class ApiAssociateController extends ApiBaseController
{
	private $assoc_model_extension;
	private $C_organization_controller;
		
	public function __construct(){
		
		parent::__construct();
		$this->assoc_model_extension = new ApiAssociateModelExtension();
		$this->C_organization_controller = new ApiOrganizationController();
	}

	
	public function login($username, $password)
	{
		$result = array();
		
		$associate_model_extension = new ApiAssociateModelExtension();
		
		$a = Auth::getInstance();
		$login_result = $a->verifyAssociateCredentials($username,  $password);
		//if($associate_model_extension->login($username, $password))
		if($login_result)
		{
			$user = LoggableUserModelExtension::loadDetailsByUsername($username);
			$associate_model_extension->load($user->getRefId());
			$result = $associate_model_extension->getHash();
			$result['last_login'] = $user->getLastLogin();
		}
		else
		{
			throw new Exception("ERR_ASSOC_LOGIN_FAIL");
		}
		
		//check whether user belong to the org
		$store_id = OrgEntityModelExtension::getParentStoresWithTills( $this->org_id ,array($this->currentuser->user_id), 'STORE');
		$store_id = $store_id[$this->currentuser->user_id];
		
		// the associate login is not proper
		if($store_id['parent_store_code'] != $result["store_code"])
		{
			throw new Exception("ERR_ASSOC_LOGIN_FAIL");
		}
		
		return $result;
	}
	
	public function getActivities($username, $password, 
								$type, $store_id,  
								$start_date, $end_date, 
								$start_id, $end_id, $limit = 10)
	{
		$result = array();
		
		$associate_model_extension = new ApiAssociateModelExtension();
		
		if($this->login($username, $password))
		{
			$result['id'] = $associate_model_extension->getId();
			$result['code'] = $associate_model_extension->getAssociateCode();
			$result['activities']['activity'] = $associate_model_extension->getActivities(
															$store_id, $type, 
															$start_date, $end_date, 
															$start_id, $end_id, $limit);
		}
		else
			throw new Exception("ERR_ASSOC_LOGIN_FAIL");
		
		return $result;
	}


	public function update($username,$password, $data)
	{
		$result = array();
		$associate_model_extension = new ApiAssociateModelExtension();
		$assoc = $data['root']['associate'][0];
		
		$assoc_id = null;
		$assoc_code = null;
		$firstname = null;
		$lastname = null;
		$mobile = null;
		$email = null;
		$store_id = null;
		$store_code = null;
		$is_active = null;
		
		if( isset($assoc['id']) )
			$assoc_id = $assoc['id'];
		if( isset($assoc['code']) )
			$assoc_code = $assoc['code'];
		if( isset($assoc['firstname']) )
			$firstname = $assoc['firstname'];
		if( isset($assoc['lastname']) )
			$lastname = $assoc['lastname'];
		if( isset($assoc['mobile']) )
			$mobile = $assoc['mobile'];
		if( isset($assoc['email']) )
			$email = $assoc['email'];
		if( isset($assoc['store_id']) )
			$store_id = $assoc['store_id'];
		if( isset($assoc['store_name']))
			$store_code = $assoc['store_name'];
		if( isset($assoc['is_active']))
			$is_active = $assoc['is_active'];
	
		if($this->login($username, $password))
		{
			$result['id'] = $associate_model_extension->getId();
			$result['code'] = $associate_model_extension->getAssociateCode();
		
			$associate_model_extension->setFirstname($firstname);
			$associate_model_extension->setLastname($lastname);
			$associate_model_extension->setMobile($mobile);
			$associate_model_extension->setStoreId($store_id);
			$associate_model_extension->setStoreCode($store_code);
			$associate_model_extension->setIsActive($is_active);
		
			$updated = $associate_model_extension->update($result['id']);
			if($updated)
			{
				$result['firstname'] = $firstname;
				$result['lastname'] = $lastname;
				$result['mobile'] = $mobile; 
				$result['email'] = $associate_model_extension->getEmail();
				$result['store_id'] = $store_id;
				$result['store_code'] = $store_code;
				$result['is_active'] = $is_active;	
			}
			else {
				throw new Exception("ERR_ASSOC_UPDATE_FAILURE");
			}
		}
		else
			throw new Exception("ERR_ASSOC_LOGIN_FAIL");
	
		return $result;
	
	}	

	public function saveAssociateActivity($assoc_id, $type, $description, $org_id, $store_id, $till_id, $time)
	{
		if(empty($time))
			$time = ' NOW() ';
		else 
			$time = "'$time'";
		
		$number_of_rows = ApiAssociateModelExtension::saveAssociateActivity($assoc_id, $type, $description, $org_id, $store_id, $till_id, $time);
		
		return $number_of_rows;
	}
		
	/**
	 * 
	 * Uploading associates details.
	 * @param unknown_type $params
	 */
	
	public function uploadAssociates( $params , $filename ){
		
		$file_batch_size = 100;	
		$col_mapping = array();

		//create the settings
		$settings['header_rows_ignore'] = 0;
		$spreadsheet = new Spreadsheet();
		$error_occured = array();
		$headers = $spreadsheet->getHeaders( $filename , ',');
		
		while( ( $processed_data = $spreadsheet->LoadCSVLowMemory( $filename, $file_batch_size, false , $headers , $settings ) ) != false ){

				unset( $processed_data[0] );
				$bulk_assoc_details = array();

				foreach ($processed_data as &$row) {
					
					$rowcount++;
					
					$assoc_code = $row['associate_code'];
					$first_name = $row['first_name'];
					$last_name = $row['last_name'];
					$mobile = $row['mobile'];
					$email = $row['email'];
					$store_id = $row['store_id'];
					
					$error['associate_code'] = trim($assoc_code);
					$error['first_name'] = trim($first_name);
					$error['last_name'] = trim($last_name);
					$error['mobile'] = trim($mobile);
					$error['email'] = trim($email);
					$error['store_id'] = trim($store_id);
					
					if( !$assoc_code ){
							
							$error['status'] = 'FAILED';
							$error['msg'] = 'Associate Code Does Not Exists';
							array_push( $error_occured, $error);
							continue;
					}
					
					$store  = $this->assoc_model_extension->storeCodeByStoreId( $store_id );
					$store_code = $store[0]['code'];
					$is_active = $store[0]['is_active'];
					
					if( !$store_code ){
							
							$error['status'] = 'FAILED';
							$error['msg'] = 'Store Does not Exists';
							array_push( $error_occured, $error);
							continue;
					}			
					
					$status = Util::checkMobileNumber( $mobile );
					$assoc_status = $this->assoc_model_extension->checkAssocDuplicateStoreId( $assoc_code , $store_id );
					
					if( $assoc_status ){
							
							$error['status'] = 'FAILED';
							$error['msg'] = 'Duplicate Entry';
							array_push( $error_occured, $error);
							continue;
					}
					
					if( !$status ){
							
							$error['status'] = 'FAILED';	
							$error['mobile_status'] = 'Invalid Mobile';
							array_push( $error_occured, $error);
							continue;
					}
						
					$email_status = Util::checkEmailAddress( $email );
						
					if( !$email_status ){
							
						$error['status'] = 'FAILED';
						$error['email_status'] = 'Invalid Email Address';
						array_push( $error_occured, $error);
						continue;
					}
					$current_date_time = Util::getCurrentDateTime();
					
					$insert_users = "( '$this->org_id' , '$assoc_code' , '$first_name' ,'$last_name' , 
									   '$mobile' ,'$email','$store_id' , '$store_code' ,'$this->user_id' , 
									   '$current_date_time' ,'$current_date_time', '$this->user_id' , $is_active 
									 )";
									
					array_push( $bulk_assoc_details, $insert_users );
				}
				$assoc_details = implode(',', $bulk_assoc_details );
				$this->logger->debug( '@@@ASSOC'.print_r($assoc_details , true ) );
			}

			$insert_status = $this->assoc_model_extension->insertAssociates( $assoc_details );
			
			return array('insert_status' => $insert_status , 'error_data' => $error_occured );
	}
		
		/**
		 * 
		 * Getting Associate user details by mobile or assoc_code
		 * @param unknown_type $mobile
		 * @param unknown_type $assoc_code
		 */
		public function getAssocUserByMobileOrAssocCode( $mobile = false , $assoc_code = false ){
			
			return $this->assoc_model_extension->getAssociateUserProfileByMobileOrAssocCode( $mobile , $assoc_code , $this->org_id );
		}

		/**
		 * 
		 * Getting Associate User Details.
		 * @param unknown_type $assoc_id
		 */
		public function getAssociateDetailById( $assoc_id ){
			
			$this->assoc_model_extension->load( $assoc_id );
			
			return $this->assoc_model_extension->getHash();
		}
		
		/**
		 * Returns all Associate of the organizaiton, depending on start_id and batch size
		 * @param unknown_type $start_id  
		 * @param unknown_type $batch_size
		 */
		public function getAllAssociateDetails($start_id = 0, $batch_size = 0){
			
			$C_storeController = new ApiStoreController();
			$store_id = $C_storeController->getBaseStoreId();
			
			$this->logger->debug("getting associates having base store_id = $store_id");
			
			$result = $this->assoc_model_extension->getAllAssociates($this->org_id, $start_id, $batch_size, $store_id);
			
			return $result;
		}
		
		/**
		 * returns multiple associates depending on ids
		 * @param unknown_type $ids
		 */
		public function getAssociateDetailsByIds( $ids )
		{
			$result = $this->assoc_model_extension->getAssociatesByIds( $this->org_id, $ids );
			
			return $result;
		}
		
		/**
		 * 
		 * Update Associate User.
		 * @param unknown_type $params = 
		 * 		CONTRACT(
		 * 			associate_code => $params['assoc_code']
		 * 			firstname => $params['first_name']
		 * 			lastname => $params['last_name']
		 * 			mobile => $params['mobile']
		 * 			email => $params['email']
		 * 			store_code => $params['store_code']
		 * 		)
		 */
		
		public function updateAssociate( $params ){

			$this->assoc_model_extension->load( $params['id'] );
			
			if( !$params['store'] )
				$store = $this->assoc_model_extension->getStoreId();
			else
				$store = $params['store'];
			
			$store_details = $this->C_organization_controller->StoreController->getDetails( $store );
			
			$this->assoc_model_extension->setOrgId( $this->org_id );
			$this->assoc_model_extension->setAssociateCode( $params['assoc_code']);
			$this->assoc_model_extension->setFirstname( $params['first_name'] );
			$this->assoc_model_extension->setLastname( $params['last_name'] );
			$this->assoc_model_extension->setMobile( $params['mobile'] );
			$this->assoc_model_extension->setStoreId( $store );
			$this->assoc_model_extension->setEmail( $params['email'] );
			$this->assoc_model_extension->setStoreCode( $store_details['code'] );
			$this->assoc_model_extension->setUpdatedBy( $this->currentuser->user_id );
			$this->assoc_model_extension->setUpdatedOn( Util::getCurrentDateTime() );
			$this->assoc_model_extension->setIsActive( $this->assoc_model_extension->getIsActive() );
			
			return $this->assoc_model_extension->update( $params['id'] );
		}
		
		public function getAssociateActivityBYAssocId( $assoc_id ){
			
			return $this->assoc_model_extension->getAssociateActivityById( $assoc_id );
		}
		
		/**
		 * 
		 * @param unknown_type $store_id
		 * @param unknown_type $start_id
		 * @param unknown_type $batch_size
		 * @return 	This will return, null in case of no record found. 
		 * 			on success it will return array of associates.  
		 */
		public function getAssociatesByStoreId($store_id, $start_id, $batch_size)
		{
			$associate_model_extension = new ApiAssociateModelExtension();
			$result = $associate_model_extension->getAssociatesByStoreId($this->org_id, $store_id, $start_id, $batch_size);
			if( $result )
				return $result;
			else
				return null;
		}
		
		/**
		 * 
		 * Add Associate User.
		 * @param unknown_type $params
		 */
		public function addAssociate( $params ){
			
			$store = $this->C_organization_controller->StoreController->getDetails( $params['store'] );
			$store_code = $store['code'];
			
			$this->assoc_model_extension->setOrgId( $this->currentorg->org_id );
			$this->assoc_model_extension->setAssociateCode( $params['assoc_code'] );
			$this->assoc_model_extension->setFirstname( $params['firstname']);
			$this->assoc_model_extension->setLastname( $params['lastname'] );
			$this->assoc_model_extension->setMobile( $params['mobile'] );
			$this->assoc_model_extension->setEmail( $params['email'] );
			$this->assoc_model_extension->setStoreId( $params['store'] );
			$this->assoc_model_extension->setStoreCode( $store_code );
			$this->assoc_model_extension->setAddedBy( $this->currentuser->user_id );
			$this->assoc_model_extension->setUpdatedBy( $this->currentuser->user_id );
			$this->assoc_model_extension->setUpdatedOn( Util::getCurrentDateTime() );
			$this->assoc_model_extension->setAddedOn( Util::getCurrentDateTime() );
			$this->assoc_model_extension->setIsActive( '1' );
			
			return $this->assoc_model_extension->insert();
		}
		
		/**
		 * 
		 * Add Associate User Login Credentials.
		 * @param unknown_type $params
		 */
		public function addAssociateLogin( $params ){
			
			$loggable_model = new LoggableUserModelExtension();
			$success = true;
			$status = '';
			
			try{
				$loggable_model->validateUserName( $params['username'] );
				$loggable_model->validatePassword( $params['password'], $params['confirm'] );
												
				$loggable_model->setOrgId( $this->currentorg->org_id );
				$loggable_model->setType( 'ASSOCIATE' );
				$loggable_model->setRefId( $params['assoc_id'] );
				$loggable_model->setUsername( $params['username'] );
				$loggable_model->setPassword( md5( $params['password'] ) );
				$loggable_model->setEmail( $params['email'] );
				$loggable_model->setSecretQuestion( $params['question'] );
				$loggable_model->setSecretAnswer( $params['answer'] );
				$loggable_model->setIsActive( '1' );
				$loggable_model->setLastLogin( Util::getCurrentDateTime() );
				$loggable_model->setLastUpdatedBy( $this->currentuser->user_id );
				$loggable_model->setLastUpdatedOn( Util::getCurrentDateTime() );

				$status = $loggable_model->insert();
					
				if( $status ){
					
					$success = true;
					$status = 'Login Credentails Added Succcessfully';					
				}else{
					$success = false;
					$status = 'Login Credentails Not Added';
				}
				 
			}catch( Exception $e ){
				
				$success = false;
				$status = $e->getMessage();
			}			
			return array('status' => $success , 'message' => $status );	
		}
		
		/**
		 * Give List of Associate User for particular organizations. 
		 */
		public function getAssociateUserByOrg(){
			
			return $this->assoc_model_extension->getAssociateByOrg();
		}
		
		/**
		 * 
		 * Get Associate User Info by Assoc id.
		 * @param unknown_type $assoc_id
		 */
		public function getAssociateLogginByRefId( $assoc_id ){
			
			$loggable_model = new LoggableUserModelExtension();
			
			$id = $loggable_model->getIdByRefId( $assoc_id, LoggableType::ASSOCIATE );
			$loggable_model->load( $id );
			
			return $loggable_model->getHash();
		}
		
		public function changeAssociatePassword( $params ){
		
			$loggable_model = new LoggableUserModelExtension();
			
			try{
				
				$status = array('status' => true , 'message' => "Password Change Successfully" );
								
				$loggable_model->loadByRefId( $params['loggable_id'], LoggableType::ASSOCIATE );
				
				$loggable_model->setPassword( md5( $params['new_pass'] ) ); 
				$loggable_model->setLastUpdatedBy( $this->user_id );
				$loggable_model->setLastUpdatedOn( date( 'Y-m-d H:m:s' ) );
				
				$password_validity = Util::getDateByDays( false, 30 , date( 'Y-m-d' ) );
				
				$loggable_model->setPasswordValidity( $password_validity );

				$username = $loggable_model->getUsername();
				$loggable_model->setUsername( $username );
				
				$email = $loggable_model->getEmail();
				$loggable_model->setEmail( $email );

				if( !$params['question'] ) 
					$secrete_q = $loggable_model->getSecretQuestion();
				else 
					$secrete_q = $params['question'];
				
				if( !$params['answer'] )
					$secrete_a = $loggable_model->getSecretAnswer();
				else
					$secrete_a = $params['answer'];
				
				$loggable_model->setSecretAnswer( $secrete_a );
				$loggable_model->setSecretQuestion( $secrete_q );
				
				$loggable_model->setIsActive( 1 );
				
				$id = $loggable_model->getId();
				
				$stat = $loggable_model->update( $id );					
					
				if( $stat )
					$status = array('status' => true , 'message' => "password updated successfully" );
				else
					$status = array('status' => false , 'message' => "password not updated" );
					
			}catch( Exception $e ){
				
				$status = array( 'status' => false, 'message' =>  $e->getMessage() );
			}
			
			return $status;
		}
		
		public function checkDuplicateMobileOrEmail( $mobile = false , $email = false ){
			
			if( $mobile ){
				
				$is_mobile_exist = $this->getAssocUserByMobileOrAssocCode( $mobile );
				if( $is_mobile_exist )
					throw new Exception('Mobile number already registered', 111 );
			}
			if( $email ){
				
				$is_email_exist = $this->assoc_model_extension->getAssociateByEmail( $email );
				if( $is_email_exist )
					throw new Exception('Email id already registered', 111 );
			}
			return 'SUCCESS';
		}
		
		public function getAllAssociate(){
			
			return $this->assoc_model_extension->getAllAssociates( $this->org_id );
		}

	/**
	 * 
	 * Add or update Associate user
	 * @param unknown_type $params
	 */
	public function addOrUpdateAssociate( $params ){

		try{
			
			if( Util::checkMobileNumber( $params['mobile'] )){
				
				if( !$params['id'] ){

					if( $this->getAssocUserByMobileOrAssocCode( false , $params['assoc_code'] ) )
							throw new Exception('Duplicate Associate Code', 111 );
							
					$assoc = $this->checkDuplicateMobileOrEmail( $params['mobile'] , $params['email'] );
					
					if( $assoc == 'SUCCESS' )
						$status = $this->addAssociate( $params );
				}else{
					
					$this->checkDuplicateMobileForAssociate( $params['mobile'], $params['id'] );
					$this->checkDuplicateEmailForAssociate( $params['email'], $params['id'] );
										
					$params['first_name'] = $params['firstname'];
					$params['last_name'] = $params['lastname'];
					$status = $this->updateAssociate( $params );
				}
				
			}else 
				throw new Exception('Invalid mobile number', 111 );
								
		}catch( Exception $e ){
				throw new Exception( $e->getMessage(), 111 );
		}
		return $status;
	}
	
	public function checkDuplicateMobileForAssociate( $mobile , $assoc_id ){
		
		$status = $this->assoc_model_extension->checkDuplicateMobile( $mobile, $assoc_id );
		if( $status )
			throw new Exception('Mobile number already registered', 111 );
		
		return true;
	}
	
	public function checkDuplicateEmailForAssociate( $email , $assoc_id ){
		
		$status = $this->assoc_model_extension->checkDuplicateEmail( $email, $assoc_id );
		if( $status )
			throw new Exception('Email already registered', 111 );
		
		return true;
	}
	
}
?>
