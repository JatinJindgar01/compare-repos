<?php
/**
 * Creating a new class for generation of a pin code since 
 * the ValidationCode class just issues a code for 15 min or so
 * which might be configurable. Also it does not do any addition
 * to db etc...
 * 
 * @author piyush
 */

class ValidationPin{
	
	public static function generatePin($mobile, $email = '', $org_id = -1, $send_email = true ){
		global $logger, $currentorg, $currentuser;
		$logger->debug("Generating pin for $mobile $email org: " . $currentorg->org_id . " user: " . $currentuser->user_id);
		if(!Util::checkMobileNumber($mobile) && !Util::checkEmailAddress($email)){
			$logger->debug("Both mobile and email look invalid.. not generating any pin code");
			throw new Exception("ERR_BOTH_MOBILE_EMAIL_INVALID");
		}	
	
		$config_manager = new ConfigManager();

		//need to add a check here for pin duplication..only one pin should be valid for the 
		//customer at a time...if 2 requests come then the older pin should be marked as invalid
		
		//fetching the validity period of the pin.. making it a org configuration.. will keep it 30 min 
		//by default	
		$validity = $currentorg->getConfigurationValue('VALIDATION_PIN_VALIDITY_PERIOD', 30);
		$pin = substr(md5(rand(0, 1000000)), 0, 5); //generating a 5 digit random string
		$sql = "INSERT INTO validation_pin(org_id, store_id, mobile, email, pin, created_time, validity, is_valid)
				VALUES(" . $currentorg->org_id . ", " . $currentuser->user_id . ", '$mobile', '$email', '$pin', 
				NOW(), $validity, true)"; 
		$logger->debug("query: $sql");
		$db = new Dbase('masters');
		if(!$db->insert($sql)){
			$logger->error("Error in adding pin to the table...throwing exception");
			throw new Exception("ERR_PIN_GENERATION_ERROR");
		}
		$logger->debug("Generated pin $pin..sending by mobile/email");
		
		/*Adding check for sending org id because in setup assistant initially 
		  when any new org will be created at that time no gateway is set properly 
		  at that time sms and email goes as capillary org
		*/
		$sender_org_id = $org_id;
		if( $org_id == -1 ){
			$sender_org_id = $currentorg->org_id;
		}
			
		if(Util::checkMobileNumber($mobile)){
			$logger->debug("Sending the pin by mobile");
			//$sms = "Please use $pin for verification";   //need to change this to template based
			$sms_template = $config_manager->getKey('SERVER_VALIDATION_PIN_SMS');
			$sms_template = Util::templateReplace( $sms_template, array( 'pin' => $pin ) );
			$logger->debug("@Pin : ".$sms_template);
			Util::sendSms($mobile, $sms_template, $sender_org_id, 1); //sending this as high priority sms
		}else if(Util::checkEmailAddress($email)){
			$logger->debug("Sending the pin by email");
			//$message = "Verification Pin";
			$message = "Please verify the contact email address for your Capillary ID";
			$body_template = $config_manager->getKey('SERVER_VALIDATION_PIN_EMAIL');
			//$body = "Please use $pin for verification";
			$body_template = Util::templateReplace( $body_template, array( 'pin' => $pin , 'email' =>  $email ) );
			if( $send_email  )
				Util::sendEmail($email, $message, $body_template, $sender_org_id, null, 1);
		}else{  //shouldn't happen... 
			$logger->error("Error in the pin logic.. both mobile and email are invalid...sending email to pigol");
			$message = "Verification Pin failed for org_id: " . $currentorg->org_id;
			$body = "Verification Pin failed for org_id: " . $currentorg->org_id;
			Util::sendEmail('piyush.goel@dealhunt.in', $message, $body, $sender_org_id, null, 1); //change this to something else
		}
		
		return $pin;
	}
	
	
	public static function verifyPin($mobile, $email, $pin){
		global $logger, $currentorg, $currentuser;
		$logger->debug("Checking pin validity $mobile $email $pin");
		$validity = $currentorg->getConfigurationValue('VALIDATION_PIN_VALIDITY_PERIOD', 30);

        /**
          Don't associate a time expiry with pin as per the amazing developers of Indian Terain !!  
        **/

		$email_filter = $mobile_filter = false;
		if( strlen( $email ) > 5 ) { $email_filter = " email = '$email'"; }
		if( strlen( $mobile ) > 5 ) { $mobile_filter = " mobile = '$mobile'"; }
		
		$filter = '';
		if( $email_filter && $mobile_filter )
			$filter = $mobile_filter . ' AND ' . $email_filter;
		else
			$filter = ( $email_filter )?( $email_filter ):( $mobile_filter );
		
		$safe_pin = Util::mysqlEscapeString($pin);
		$sql = "SELECT * FROM validation_pin WHERE $filter AND is_valid = TRUE AND pin = '$safe_pin' 
			    ORDER BY id DESC";
		
		$db = new Dbase('masters');    //what about pin timing out ??? 
		$logger->debug("Query: $sql");
		if(!sizeof($db->query($sql, true))){
			return false;
		}else{
			return true;
		}	
	}
	
	/**
	 * It will generate activation link for email verification and send the mail to that email address
	 * @param unknown_type $email
	 * @author nayan
	 */
	public static function generateActivationLinkForEmail( $email ){
		
		global $logger, $currentorg, $currentuser,$sw_config;
		$logger->debug("Generating Activation Key for $email");
		
		$validity = $currentorg->getConfigurationValue('VALIDATION_PIN_VALIDITY_PERIOD', 30);
		
		$database = new Dbase('masters');
		
		$org_id = $currentorg->org_id;
		$activationKey = $org_id.'__'.$email;
		
		$sql = "INSERT INTO validated_email( email, is_valid, last_updated_on, 
					entered_by )
				VALUES('$email', false, NOW(), '$currentuser->user_id' )
				ON DUPLICATE KEY UPDATE `email` = values(`email`),
				`last_updated_on` = NOW(), `entered_by` = values(`entered_by`)";
		
		$logger->debug("query: $sql");
		
		if( !$database->insert( $sql ) ){
			$logger->error("Error in adding activation key in to the table...throwing exception");
			throw new Exception("ERR_PIN_GENERATION_ERROR");
		}
		
		if( Util::checkEmailAddress($email) ){
			
			$logger->debug('Generated Pin is '.$activationKey.' for Email ID: '.$email);
			$act_pin = base64_encode( $activationKey );
			$subject = " !n-touch Email Verification Mail ";
			$message = "<br/>You've entered (".$email.") as the contact email address ". 
						"for your Capillary ID. To complete the process, we just need ". 
						"to verify that this email address belongs to you. Simply ".
						"click the link below to verify your email address: ".
						"<br/>".
						"<a href='".$sw_config['email']['verification_link']."/verify_email.php?h=$act_pin'>".
						"click here".
						"</a><br/><br/>".
						"Wondering why you got this email?".
						"<br/><br/>".
						"It's sent when someone adds or changes a contact email address ".
						"for a Capillary account. If you didn't do this, don't worry. ".
						"Your email address cannot be used as a contact address for ".
						"a Capillary ID without your verification.".
						"<br/>".
						"For more information, get in touch with your Capillary ".
						"Point of Contact.<br/><br/>".
						"Thanks,<br/>Capillary Customer Support";
			
			$logger->debug( 'email format '.$message );
			$status = Util::sendEmail( $email , $subject , $message , 
												$currentorg->org_id , null , 1);
			$result = false;
			if( $status > 0 ){
				$logger->debug("An email has been sent to ".$email." with an ".
						"activation link. <br/>Please click the link for email varification.");
				$result = 'An email has been sent to " '.
							'<span style="color:black;">'.$email.'</span> ".'.
							' Follow the steps on the email to verify.';
			}	
			return $result;
				
		}else 
			return false;
	}
	
	/**
	 * This method is used to verify the email activation key 
	 * and update the email validated field in admin_users
	 * @param string $key 
	 * @author nayan
	 */
	public static function verifyAndUpdateEmail( $email ){
	
		global $logger;
		$logger->debug('Start Verify and Update Email :'.$email);
		
		$database = new Dbase('masters');
		
		$select_sql = "SELECT `is_valid`
						FROM `validated_email`
						WHERE `email`= '$email' ";
		
		if( $database->query_scalar( $select_sql ) )
			throw new Exception('Email already verified');
		
		$update_sql = "UPDATE `validated_email` 
						SET `is_valid`=1,`last_updated_on`=NOW()
						WHERE `email`= '$email' ";
		       
		$res = $database->update( $update_sql );
		if( $res ) return true;
		else return false;
	}
	
	/**
	 * It will check pincode mapping with mobile and pincode and returns mapped addres
	 * From pincode_mapping table of Masters DB
	 * 
	 */
	public static function getMappedAddressByPincode( $pincode ){
		
		global $currentorg , $logger;
		$org_id = $currentorg->org_id;
		
		$logger->debug('Start Mapped Address Pincode with Pincode :'.$pincode." , Phone : ".$phone_number." , Org Id : ".$org_id);
		
		$database = new Dbase('masters');
	
		$sql = "
						SELECT `mobile_number` AS `phone_number` , `address` 
						FROM `pincode_mapping` pm 
		       			WHERE pm.`org_id`= '$org_id' AND pm.`pincode` = '$pincode' ";
		       
		$res = $database->query( $sql );
		
		return $res;
	}
	
}
