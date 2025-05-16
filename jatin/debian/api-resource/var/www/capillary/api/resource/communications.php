<?php

require_once "resource.php";
require_once "apiController/ApiCommunicationsController.php";
/**
 * Used for sending emails/sms and fetching templates etc.
 * - email
 * - sms
 * - templates : communications templates for the organization
 *
 * @author pigol
 */
/**
 * @SWG\Resource(
 *     apiVersion="1.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/communications",
 *     basePath="http://{{INTOUCH_ENDPOINT}}/v1.1"
 * )
 */
//TODO: need to add item level status
class CommunicationsResource extends BaseResource{

	function __construct()
	{
		parent::__construct();
	}


	public function process($version, $method, $data, $query_params, $http_method)
	{
		//$this->logger->("POST data: "+$data);
		if(!$this->checkVersion($version))
		{
			$this->logger->error("Unsupported Version : $version");
			$e = new UnsupportedVersionException(ErrorMessage::$api['UNSUPPORTED_VERSION'], ErrorCodes::$api['UNSUPPORTED_VERSION']);
			throw $e;
		}

		if(!$this->checkMethod($method)){
			$this->logger->error("Unsupported Method: $method");
			$e = new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
			throw $e;
		}
		
		$result = array();
		try{
	
			switch(strtolower($method)){

				case 'email' :
						
					$result = $this->email($data, $query_params, $http_method);
					break;
				
				case 'sms' :
						
					$result = $this->sms($data, $query_params, $http_method);
					break;
					
				case 'template' :

					$result = $this->templates($data, $query_params, $http_method);
					break;				

				default :
					$this->logger->error("Should not be reaching here");
						
			}
		}catch(Exception $e){ //We will be catching a hell lot of exceptions as this stage
			$this->logger->error("Caught an unexpected exception, Code:" . $e->getCode()
			. " Message: " . $e->getMessage()
			);
			throw $e;
		}
			
		return $result;
	}
	
	/**
	 * @SWG\Api(
	 * path="/communications/email.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get email details",
	 *     nickname = "Get email",
	 *    @SWG\Parameter(name = "id",type = "string",paramType = "query", description = "message id returned while sending the email" )
	 *    )
	 * )
	 */

	/**
	 * Sends Email if $http_method(HTTP Method) is POST,
	 * or Fetches Email if $http_method(HTTP Method) is GET
	 * @param array $data - Post Data
	 * @param array $query_params - Get Data (Query Parameters)
	 * @param unknown_type $http_method - HTTP Method (GET, POST)
	 */
	
	/**
	 * @SWG\Model(
	 * id = "Attachment",
	 * @SWG\Property( name = "file_name", type = "string" ),
	 * @SWG\Property( name = "file_type", type = "string" ),
	 * @SWG\Property( name = "file_data", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Email",
	 * @SWG\Property( name = "to", type = "string" ),
	 * @SWG\Property( name = "cc", type = "string" ),
	 * @SWG\Property( name = "bcc", type = "string" ),
	 * @SWG\Property( name = "from", type = "string" ),
	 * @SWG\Property( name = "subject", type = "string" ),
	 * @SWG\Property( name = "body", type = "string" ),
	 * @SWG\Property( name = "attachments", type = "array", items = "$ref:Attachment" ),
	 * @SWG\Property( name = "scheduled_time", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "EmailRoot",
	 * @SWG\Property( name = "root", type = "Email" )
	 * )
	 */
	/**
     * @SWG\Api(
     * path="/communications/email.{format}",
     * @SWG\Operation(
     *     method="POST", summary="Sends emails and returns details",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="EmailRoot")
     * ))
     */
	private function email( $data,  $query_params, $http_method)
	{
		$result = NULL;
		
		 
		$http_method = strtolower($http_method);
		
		//gets Email if the HTTP method is GET or get
		if($http_method == "get")
			$result = $this->getEmail($query_params);
		
		//sends Email if the HTTP method is POST or post
		else if ($http_method == "post")
			$result = $this->sendEmail($data);
		
		return $result;
	}
	
	/**
	 * Extracts Information of the Email and sends that email.
	 * @param array $data - array representation of the xml defined below
	 * 
	 * <?xml version="1.0" encoding="UTF-8"?>		
		<root>
			<email>
				<to>piyush.goel@capillary.co.in</to>
				<cc>piyush.goel@outlook.com, kk@capillary.co.in</cc>
				<bcc>piyushgoel@gmail.com</cc>
				<from>abc@levis.com</from>  -- optional
				<subject><![CDATA[Testing email]]>
				<body><![CDATA[Dear Krishna,
		
					Thanks for the treat. Looking forward to more treats ! :D 
					]]>
				</body>	
				<attachment>
					<file_name>asda.pdf</file_name>
					<file_type>pdf</file_type>
					<file_data><![CDATA[              -- base64_encoded file contents
						adsdsadd21121dasd12123123assdad1212123
						234234234234234234
						24234234sdafsafsfsdfdsfsfsfsd
						asfwsfsdfsdffdf
						]]>
					</file_data>	
				</attachment>
				<scheduled_time>2012-08-05 22:00::IST</scheduled_time>
			</email>	
	   </root>			
	 */
	//TODO: need to populate the response, still not done.
	private function sendEmail( $data){
	        	
		$api_status_code = "SUCCESS";
		
		// elements extraction
		if(isset($data['root']) && isset($data['root']['email']))
			$emails = $data['root']['email'];
		else
		{
			$api_status_code = "FAIL";
			return array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
				); 
		}
		//for now taking first email request.
		$response_email_arr = array();
		$success_count = 0;
		//$email = $emails[0];
		foreach($emails as $email)
		{
			$communication_status_code = "ERR_EMAIL_SEND_SUCCESS";

			//have to be validated!!
			$to = $email['to']; 
			$cc = $email['cc'];
			$bcc = $email['bcc'];

			$from = $email['from'];
			$subject = $email['subject'];
			$body = $email['body'];
			$scheduled_time = $email['scheduled_time'];
			$attachment = $email['attachment']; // TODO: need to know weather it can be more than one or not.
			$attachment_id = -1;
			
			try{
			$this->logger->info("exploding the fields and validating");
			foreach(array("to","cc","bcc") as $vvar)
			{
				$to_payload[$vvar]= array_map('trim',explode(",", $$vvar));
				foreach($to_payload[$vvar] as $i=>$val)
				{
					if(empty($val))
						unset($to_payload[$vvar][$i]);
					else if(preg_match(UtilRegExPattern::$email_pattern,$val)!=1)
						throw new Exception('ERR_EMAIL_INVALID');
				}
			}
			$this->logger->debug("to_payload after cleaning: ".print_r($to_payload,true));
			
			
 			$to_validated = implode(",",$to_payload['to']);
 			$cc_validated = implode(",",$to_payload['cc']);
 			$bcc_validated = implode(",",$to_payload['bcc']);
			
			//if bcc is not empty the concatinate it with cc.
			$cc_and_bcc = $cc_validated;
			if(!empty($bcc_validated))
				$cc_and_bcc = $cc_validated.",".$bcc_validated;
			
			$this->logger->info("CommunicationResource => Initializing CommunicationsController");
			$communication_controller = new ApiCommunicationsController();
			
			$user_id=-1;
			if(count($to_payload['to'])==1)
			{
				$reg_user=UserProfile::getByEmail($to_validated);
				if($reg_user)
				{
					$user_id=$reg_user->user_id;
					$to=$to_validated=$reg_user->email;
				}
			}
                        ApiCacheHandler::triggerEvent("otp",$user_id);

			$this->logger->info("CommunicationsResource => Sending the Email");
			$nsadmin_id = $communication_controller->sendEmail($to_validated, $subject, $body, $this->currentorg->org_id, 
					$cc_and_bcc, MESSAGE_PERSONALIZED, $attachment, array(), $scheduled_time, true, $user_id);
			
			$email = array(
					"id" => $nsadmin_id,
					"to" => $to,
					"cc" => $cc,
					"bcc" => $bcc,
					"status" => "Queued",
					"scheduled_time" => $scheduled_time,
                    "subject" => $subject,
                    "description" => $body,
					"item_status" => array(
								"status" => (ErrorCodes::$communications[$communication_status_code] == 
										ErrorCodes::$communications["ERR_EMAIL_SEND_SUCCESS"]) ? true : false,
								"code" => ErrorCodes::$communications[$communication_status_code],
								"message" => ErrorMessage::$communications[$communication_status_code]
							)
				);
				$success_count++;
			}
			catch(Exception $e)
			{
				$this->logger->error("Error: ". $e->getMessage());
				$communication_status_code = $e->getMessage();
				
				$email = array(
						"id" => -1,
						"to" => $to,
						"cc" => $cc,
						"bcc" => $bcc,
						"status" => "",
						"scheduled_time" => $scheduled_time,
						"item_status" => array(
								"status" => (ErrorCodes::$communications[$communication_status_code] == 
										ErrorCodes::$communications["ERR_EMAIL_SEND_SUCCESS"]) ? true : false,
								"code" => ErrorCodes::$communications[$communication_status_code],
								"message" => ErrorMessage::$communications[$communication_status_code]
						)
					);
			}
			array_push($response_email_arr, $email);
		}
		
		if($success_count <= 0)
			$api_status_code = "FAIL";
		else if($success_count < count($emails))
			$api_status_code = "PARTIAL_SUCCESS";
		
		$status = array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		return 	array(
						"status" => $status,
						"email" => $response_email_arr
				);
	}
		
	/**
	 * gets Email from the System that has already been sent.
	 * @param array $query_params
	 */
	private function getEmail( $query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = "SUCCESS";
		
		$ids = explode( "," , $query_params['id'] );
	
		$emails = array();
		$email_success_count = 0;
		$communication_controller =  new ApiCommunicationsController();

		foreach ($ids as $id)
		{
			$communication_status_code = "ERR_EMAIL_FETCH_SUCCESS";
			try{
				$email = $communication_controller->getEmailInfo($id);
				$item_status = array(
							"status" => (ErrorCodes::$communications[$communication_status_code] ==
									ErrorCodes::$communications["ERR_EMAIL_FETCH_SUCCESS"]) ? true : false,
							"code" => ErrorCodes::$communications[$communication_status_code],
							"message" => ErrorMessage::$communications[$communication_status_code]
					);
				$email['item_status'] = $item_status;
				$arr_item_status_codes[] = $item_status['code'];
				$email_success_count++;
			}
			catch(Exception $e)
			{
				$this->logger->error("Error: ".$e->getMessage());
				$communication_status_code = $e->getMessage();
				$email = array(
					"id" => $id,
					"to" => "",
					"cc" => "",
					"status" => "",
					"sent_time" => "",
					"item_status" => array(
							"status" => (ErrorCodes::$communications[$communication_status_code] ==
									ErrorCodes::$communications["ERR_EMAIL_FETCH_SUCCESS"]) ? true : false,
							"code" => ErrorCodes::$communications[$communication_status_code],
							"message" => ErrorMessage::$communications[$communication_status_code]
					)
				);
				$arr_item_status_codes[] = $email['item_status']['code'];
			}
			array_push($emails, $email);
		}
		
		if($email_success_count == 0)
			$api_status_code = "FAIL";
		else if($email_success_count < count($ids))
			$api_status_code = "PARTIAL_SUCCESS";
			
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);		
		
		return 
				array(
						"status" => $api_status,
						"email" => $emails
				);
	}
	
	/**
	 * Sends SMS if $http_method(HTTP Method) is POST,
	 * or Fetches SMS if $http_method(HTTP Method) is GET
	 * @param array $data - Post Data
	 * @param array $query_params - Get Data (Query Parameters)
	 * @param unknown_type $http_method - HTTP Method (GET, POST)
	 */
	/**
	 * @SWG\Api(
	 * path="/communications/sms.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get sms details",
	 *     nickname = "Get sms",
	 *    @SWG\Parameter(name = "id",type = "string",paramType = "query", description = "message id returned while sending the sms" )
	 *    )
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "Sms",
	 * @SWG\Property( name = "to", type = "string" ),
	 * @SWG\Property( name = "body", type = "string" ),
	 * @SWG\Property( name = "scheduled_time", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "SmsRoot",
	 * @SWG\Property( name = "root", type = "Sms" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/communications/sms.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Sends sms and returns details",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="SmsRoot")
	 * ))
	 */
	private function sms( $data,  $query_params, $http_method)
	{
		$result = NULL;
		
		$http_method = strtolower($http_method);
		//gets Email if the HTTP method is GET or get
		if($http_method == "get" )
			$result = $this->getSms($query_params);
		
		//sends Email if the HTTP method is POST or post
		else if ($http_method == "post" )
			$result = $this->sendSms($data);
		
		return $result;
	}
	
	/**
	 * Extracts Information of the SMS and sends that sms.
	 * @param  $data - array representation of the xml format defined below 
	 * 	<?xml version="1.0" encoding="UTF-8"?>		
		<root>
			<sms>
				<to>919980616752</to>	
				<body>Hi, how about dinner tonight</body>
				<scheduled_time>2012-08-05 22:00:00IST</scheduled_time>
				<sender>LM-Levis</sender>
			</sms>	
	   	</root>	
	 * 
	 * TODO: ask what is purpose of sender in xml
	 */
	private function sendSms( $data){
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		
		$api_status_code = "SUCCESS";
		
		// elements extraction
		if(isset($data['root']) && isset($data['root']['sms']))
			$sms_arr = $data['root']['sms'];
		else
		{
			$api_status_code = "FAIL";
			return array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
				); 
		}
		
		//$sms = $sms_arr[0];
		$response_sms_arr = array();
		$success_count = 0;
		foreach($sms_arr as $sms)
		{
			$communication_status_code = "ERR_SMS_SEND_SUCCESS";
			$to = $sms['to'];
			$body = $sms['body'];
			$scheduled_time = $sms['scheduled_time'];
			$sender = $sms['sender'];
			
			$isOtp = trim($sms['is_otp']) == 1 ? true: false;
			$tags = array();
			if ($isOtp) {
				$tags = array('otp');
			}
			
			try
			{
				$communication_controller = new ApiCommunicationsController();
				
				$to_user_id=-1;
				try{
					$to_user=UserProfile::getByMobile($to);
					if($to_user)
					{
						$to_user_id=$to_user->getUserId();
						$to=$to_user->mobile;
                                        }
                                ApiCacheHandler::triggerEvent("otp",$to_user_id);

				}catch(Exception $e)
				{
					//ignore, continue with -1
				}

				$nsadmin_id = $communication_controller->sendSms($to, $body, $this->currentorg->org_id, MESSAGE_PERSONALIZED, false, $scheduled_time , false, false, $tags, $to_user_id );
				
				$sms = array(
					"id" => $nsadmin_id,
					"to" => $to,
					"status" => "Queued",
					"body" => $body,
					"scheduled_time" => $scheduled_time,
					"item_status" => array(
							"status" => (ErrorCodes::$communications[$communication_status_code] == 
									ErrorCodes::$communications["ERR_SMS_SEND_SUCCESS"]) ? true : false,
							"code" => ErrorCodes::$communications[$communication_status_code],
							"message" => ErrorMessage::$communications[$communication_status_code]
					)
				); 
				$arr_item_status_codes[] = $sms['item_status']['status']; 
				$success_count++;
			}
			catch(Exception $e)
			{
				$this->logger->error("Error: ".$e->getMessage());
				$communication_status_code = $e->getMessage();
				$sms = array(
					"id" => -1,
					"to" => $to,
					"status" => "",
					"body" => $body,
					"scheduled_time" => $scheduled_time,
					"item_status" => array(
							"status" => (ErrorCodes::$communications[$communication_status_code] ==
									ErrorCodes::$communications["ERR_SMS_SEND_SUCCESS"]) ? true : false,
							"code" => ErrorCodes::$communications[$communication_status_code],
							"message" => ErrorMessage::$communications[$communication_status_code]
					)
				); 
				$arr_item_status_codes[] = $sms['item_status']['status'];
			}
			array_push($response_sms_arr, $sms);
			
		}
		
		/*$sms = array(
				"id" => "23423443",
				"to" => "918867702348",
				"status" => "QueuedSendRequest",
				"scheduled_time" => "2012-08-05 22:00::IST"
		);*/
		
		if($success_count <= 0)
			$api_status_code = "FAIL";
		else if($success_count < count($sms_arr))
			$api_status_code = "PARTIAL_SUCCESS";
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == 200 ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		return 	array(
						"status" => $api_status,
						"sms" => $response_sms_arr
				);
		
	}
	
	/**
	 * gets SMS from the System that has already been sent.
	 * @param array $query_params
	 */
	private function getSms( $query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = "SUCCESS";
		
		$ids = explode( "," , $query_params['id'] );

		$sms_arr = array();
		$sms_success_count = 0;
		$communication_controller =  new ApiCommunicationsController();
		
		foreach ($ids as $id)
		{
			try
			{
				$communication_status_code = "ERR_SMS_FETCH_SUCCESS";
				$sms = $communication_controller->getSmsInfo($id);
				$item_status =array(
						"status" => (ErrorCodes::$communications[$communication_status_code] ==
								ErrorCodes::$communications["ERR_SMS_FETCH_SUCCESS"]) ? true : false,
						"code" => ErrorCodes::$communications[$communication_status_code],
						"message" => ErrorMessage::$communications[$communication_status_code]
				);
				$sms['item_status'] = $item_status;
				$arr_item_status_codes[] = $item_status['code']; 
				$sms_success_count++;
			}
			catch(Exception $e)
			{
				$this->logger->error("Error: ". $e->getMessage());
				$communication_status_code = $e->getMessage();
				$sms = array(
						"id" => $id,
						"to" => "",
						"status" => "",
						"sent_time" => "",
						"item_status" => array(
							"status" => (ErrorCodes::$communications[$communication_status_code] ==
									ErrorCodes::$communications["ERR_SMS_FETCH_SUCCESS"]) ? true : false,
							"code" => ErrorCodes::$communications[$communication_status_code],
							"message" => ErrorMessage::$communications[$communication_status_code]
						)
				);
				$arr_item_status_codes[] = $sms['item_status']['code'];
			}
			array_push($sms_arr, $sms);
		}
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		if($sms_success_count == 0)
			$api_status_code = "FAIL";
		else if($sms_success_count < count($ids))
			$api_status_code = "PARTIAL_SUCCESS";
		
		
		$api_status = array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		return 	array(
						"status" => $api_status,
						"sms" => $sms_arr
				);
	}
	
	/**
	 * Saves Template if $http_method(HTTP Method) is POST,
	 * or Fetches Template if $http_method(HTTP Method) is GET
	 * @param array $data - Post Data
	 * @param array $query_params - Get Data (Query Parameters)
	 * @param unknown_type $http_method - HTTP Method (GET, POST)
	 */
	
	/**
	 * @SWG\Api(
	 * path="/communications/templates.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get template details for email/sms",
	 *     nickname = "Get Templates",
	 *    @SWG\Parameter(name = "type",type = "string",paramType = "query", description = "Required template type (email/sms)" )
	 *    )
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "Template",
	 * @SWG\Property( name = "id", type = "string" ),
	 * @SWG\Property( name = "type", type = "string", description = "EMAIL/SMS" ),
	 * @SWG\Property( name = "title", type = "string" ),
	 * @SWG\Property( name = "subject", type = "string" ),
	 * @SWG\Property( name = "body", type = "string" ),
	 * @SWG\Property( name = "is_editable", type = "string", description = "TRUE/FALSE" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "TemplateRoot",
	 * @SWG\Property( name = "root", type = "array", items = "$ref:Template" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/communications/templates.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Adds/updates template. Batch support available",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="TemplateRoot")
	 * ))
	 */
	private function templates( $data,  $query_params, $http_method)
	{
		$result = NULL;
		
		$http_method = strtolower($http_method);
		
		//gets Template if the HTTP method is GET or get
		if($http_method == "get")
			$result = $this->getTemplates($query_params);
		
		//saves Template if the HTTP method is POST or post
		else if ($http_method == "post")
			$result = $this->saveTemplates($data);
		
		return $result;
	}		
	
	private function saveTemplates( $data ){
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		
		$templates = $data['root']['template'];
		if(isset($templates['type']))
		{
			$templates = array($templates);
		}
		$api_status_code = 'SUCCESS';
		global $error_count;
        $error_count = 0;
		$response_items = array();
		if(is_array($templates))
		{
			$communicationController = new ApiCommunicationsController();
			foreach($templates as $template)
			{
				$item = array();
				try
				{
					$update = isset($template['id']) && $template['id'] > 0 ? true : false;
					
					$type = '';
					$title = '';
					$subject ='';
					$body ='';
					$id = -1;
					$is_editable = "1";
					if(isset($template['id']))
						$id =$template['id'];
					if(isset($template['type']))
						$type =$template['type'];
					if(isset($template['title']))
						$title =$template['title'];
					if(isset($template['subject']))
					{
						$subject = mysql_escape_string( $template['subject'] );
					}
					if(isset($template['body']))
					{
						$body = mysql_escape_string( $template['body'] );
					}
					if(isset($template['is_editable']))
					{
						$is_editable = strtolower( $template['is_editable'] ) == "true" ? 1 : 0 ;
					}
					
					if($update)
					{
						$this->logger->debug("going to update the Template id: ".$template['id']);
						$item_status_code = "ERR_UPDATE_TEMPLATE_SUCCESS";
						$item_hash = $communicationController->updateTemplate($id, $title, $subject, $body, $is_editable);
						$item['id'] = $item_hash['id'];
						$item['type'] = $item_hash['type'];
						$item['title'] = $item_hash['title'];
						if(!empty($item_hash['subject']))
							$item['subject'] = $item_hash['subject'];
						$item['body'] = array( "@cdata" => $item_hash['body']);
						$item['is_editable'] = $item_hash['is_editable'] == 1 ? 'TRUE' : 'FALSE';
					}
					else 
					{
						$this->logger->debug("going to add the template");
						$item_status_code = "ERR_ADD_TEMPLATE_SUCCESS";
						$item_hash = $communicationController->addTemplate($type, $title, $subject, $body, $is_editable);
						
						$item['id'] = $item_hash['id'];
						$item['type'] = $item_hash['type'];
						$item['title'] = $item_hash['title'];
						if(!empty($item_hash['subject']))
							$item['subject'] = $item_hash['subject'];
						$item['body'] = array( "@cdata" => $item_hash['body']) ;
						$item['is_editable'] = $item_hash['is_editable'] == 1 ? 'TRUE' : 'FALSE';
					}
				}
				catch(Exception $e)
				{
					$item['id'] = -1;
					if($update) 
						$item['id'] = $id;
					$item['type'] = $template['type'];
					$item['title'] = $template['title'];
					if(!empty($template['subject']))
						$item['subject'] = $template['subject'];
					$item['body'] = array( "@cdata" => $template['body']) ;
					$item['is_editable'] = $template['is_editable'];
					$this->logger->error("CommunicationResource::saveTemplate()=> ".$e->getMessage());
					$item_status_code = $e->getMessage();
					$error_count++;
				}
				$item['item_status'] = array(
											"status" => (ErrorCodes::$communications[$item_status_code] ==
													ErrorCodes::$communications["ERR_ADD_TEMPLATE_SUCCESS"]) ? true : false,
											"code" => ErrorCodes::$communications[$item_status_code],
											"message" => ErrorMessage::$communications[$item_status_code]
										);
				$arr_item_status_codes[] = $item['item_status']['code'];
				array_push($response_items, $item);
			}
		}
		
		if($error_count == count($templates))
			$api_status_code = "FAIL";
		else if($error_count > 0)
			$api_status_code = "PARTIAL_SUCCESS";
		
		
		$api_status = array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		
		
		if(count($response_items) > 0)
		{
			$response_items = array("templates" => 
										array("template" => 
														$response_items
											)
								);
		}
		else
			$response_items = array();
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		return 	array(
						"status" => $api_status,
						"communications" => $response_items
				);
	}
	
	private function getTemplates( $query_params)
	{
		$api_status_code = "SUCCESS";
		
		try {
			$type ='';
			if(isset($query_params['type']))
			{
				$type = $query_params['type']; 
			}
			
			$communicationController = new ApiCommunicationsController();
			$response_items = $communicationController->getTemplates($type);
			foreach($response_items as &$item)
			{
				$item['body'] = array( "@cdata" => $item['body'] ); 
			}
		}
		catch(Exception $e)
		{
			$this->logger->error("CommunicationResource::getTemplates()=> ".$e->getMessage());
			$api_status_code = "FAIL";
			$error_message = ErrorMessage::$api[$api_status_code].", ". ErrorMessage::$communications[$e->getMessage()]; 
		}
		
		$api_status = array(
				"success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => isset($error_message)? $error_message : ErrorMessage::$api[$api_status_code]
		);
		
		if(count($response_items) > 0)
		{
			$response_items = array("templates" =>
											array("template" =>
													$response_items
											)
									);
		}
		else
			$response_items = array();
		return 	array(
				"status" => $api_status,
				"communications" => $response_items
		);
	}
	
	/**
	 * Checks if the system supports the version passed as input
	 *
	 * @param $version
	 */

	public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1', 'v1.1'))){
			return true;
		}
		return false;
	}

	public function checkMethod($method)
	{
		if(in_array(strtolower($method), array('email', 'sms', 'template' )))
		{
			return true;
		}
		return false;
	}

}
