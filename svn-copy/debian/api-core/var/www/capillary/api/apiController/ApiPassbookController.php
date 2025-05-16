<?php

include_once('apiController/ApiBaseController.php');
//TODO: referes to cheetah
include_once('helper/passbook/Passbook.php');
//TODO: referes to cheetah
include_once('helper/passbook/CouponPassbook.php');
//TODO: referes to cheetah
include_once('helper/passbook/LoyaltyPassbook.php');
include_once('apiHelper/async/Job.php');
include_once('apiHelper/async/AsyncClient.php');
//TODO: referes to cheetah
include_once('helper/signature.php');

define(PASSBOOK_CONFIG_BACKGROUND_IMAGE , 'PASSBOOK_CONFIG_BACKGROUND_IMAGE');
define(PASSBOOK_CONFIG_BACKGROUND_IMAGE_2x , 'PASSBOOK_CONFIG_BACKGROUND_IMAGE_2x');
define(PASSBOOK_CONFIG_BRAND_LOGO, 'PASSBOOK_CONFIG_BRAND_LOGO');
define(PASSBOOK_CONFIG_BRAND_LOGO_2x, 'PASSBOOK_CONFIG_BRAND_LOGO_2x');
define(PASSBOOK_CONFIG_ICON, 'PASSBOOK_CONFIG_ICON');
define(PASSBOOK_CONFIG_ICON_2x, 'PASSBOOK_CONFIG_ICON_2x');

/**
 * Provides all utility functions for 
 * managing passbook related features
 * 
 * @author pigol
 */

define(CONF_LOYALTY_PASS_IDENTIFIER, 'CONF_LOYALTY_PASS_IDENTIFIER');
define(CONF_COUPON_PASS_IDENTIFIER, 'CONF_COUPON_PASS_IDENTIFIER');

define(CONF_LOYALTY_PASS_SIGNATURE, 'CONF_LOYALTY_PASS_SIGNATURE');
define(CONF_COUPON_PASS_SIGNATURE, 'CONF_COUPON_PASS_SIGNATURE');

//used by american govt for encrypting secret documents
//our coupon code is confidential enough !! :)
define(ENCRYPTION_METHOD, 'AES-256-CBC');

class ItemNotFoundException extends Exception{}
class AuthorizationFailureException extends Exception{}
class UserDeviceMappingException extends Exception{}

class ApiPassbookController extends ApiBaseController{
        
    private $db;         
   
    public function __construct(){
        global $logger, $currentorg;
        $this->logger = $logger;
        $this->db = new Dbase('passbook');        
        $this->currentorg = $currentorg;
    }
        
    public function registerNewDevice($device_id, $pass_identifier, $serial_no, $payload, $headers)
    {
        $auth_token = $this->getAuthHeader($headers);
                
        $this->logger->debug("Registering a device: $device_id, $pass_identifier, $serial_no, $payload, $auth_token");
        
        $passbook = LoyaltyPassbook::getBySerialNo($serial_no);
        if(!$passbook){
        	
        	$this->logger->error("Passbook does not exist..returning pass does not exist");
        	throw new ItemNotFoundException("Passbook not found");
        }
        
        if(strtolower($auth_token) != strtolower($passbook->getAuthToken())){
        	
            $this->logger->error("Token authentication failure");
            throw new AuthorizationFailureException("Authorization Failure");
        }
        
        if(!$this->addUserDeviceMapping($passbook->getUserId(), $device_id, $passbook->getOrgId(), 'APPLE')){
        	
            $this->logger->error("Error in adding the user device mapping");
            throw new UserDeviceMappingException("User Device Mapping Exception");
        }
        
        $payload = json_decode($payload, true);
        $push_token = $payload['pushToken'];        
        $passbook->setPushToken($push_token);        
        $passbook->update();                 

        if(!$this->updatePassbookLog($passbook->getId())){
        	
            $this->logger->error("Error in updating passbook activity log");
        }
        return 201;
    }
        
    private function addUserDeviceMapping($user_id, $device_id, $org_id, $type = 'APPLE')
    {
        $sql = "INSERT INTO passbook.user_device_mapping(user_id, device_id, org_id, device_type)
        		VALUES ($user_id, '$device_id', $org_id, '$type')
        	   ";
        return $this->db->insert($sql);
    }
    
    private function updatePassbookLog($passbook_id, $ref_id = -1, $activity_type = 'DEVICE_REGISTRATION', $added_on = '', $notes = '')
    {
        $sql = "INSERT INTO passbook.passbook_log(passbook_id, ref_id, activity_type, added_on, notes)
        		VALUES ($passbook_id, $ref_id, '$activity_type', now(), '$notes')
        	   ";
        return $this->db->insert($sql);
    }
    
    public function getPassesForDevice($device_id, $pass_identifier, $headers, $query_params)
    {
        $auth_token = $this->getAuthHeader($headers);  
        
        $passes_updated_since = date('Y-m-d H:i:s', $query_params['passesUpdatedSince']);        
        $this->logger->info("Fetching the passes for $device_id, $pass_identifier, $auth_token, $passes_updated_since");
                       
        $sql = "SELECT p.serial_no, p.updated_on 
        		FROM passbook p 
        		JOIN user_device_mapping udm 
        		ON p.user_id = udm.user_id  
        		WHERE p.updated_on > '$passes_updated_since'
        	    AND p.pass_identifier = '$pass_identifier' AND udm.device_id = '$device_id'
        	    AND p.is_active = 1
        	    ORDER BY p.updated_on DESC  
        	   ";
        
        $result = $this->db->query($sql);
        
        if(!$result){
        	
        	$this->logger->error("Passbook not found for sql ".$sql);
        	throw new ItemNotFoundException("Passbook Not Found");
        }
              
        $response = array();
        $count = 0;
        foreach($result as $row)
        {
        	
            if($count == 0){
            	
                $response['lastUpdatedTag'] = time($row['updated_on']);
                $response['serialNumbers'][] = $row['serial_no'];                
            }
        }
        $this->logger->info("Response: " . print_r($response, true));
        return $response;
    }
    
    
    public function unRegisterDevice($device_identifier, $pass_identifier, $headers){
    	
      $auth_token = $this->getAuthHeader($headers);
      $passbook = LoyaltyPassbook::getByPassAndDeviceId($pass_identifier, $device_identifier);
      if($passbook == null){
      	
          $this->logger->error("passbook not found with pass '$pass_identifier' and device '$device_identifier'");
          throw new ItemNotFoundException("Passbook Not Found");
      }
      
      $passbook->setUpdatedOn(date('Y-m-d H:i:s'));
      $passbook->setIsActive(false);
      $passbook->update();
      
      $this->updatePassbookLog($passbook->getId());
      $this->logger->debug("Device Unregistered Successfully");
      return 200;
    }
    
    
    private function getAuthHeader($headers){
    	
        $auth_header = $headers['Authorization'];
        $auth_token = substr($auth_header, stripos($auth_header, 'ApplePass') +10);
        return $auth_token; 
    }
    
    
    public function sendNotification($passbook_id, $ref_id = -1, $type = 'TRANSACTION'){
        
        $passbook = LoyaltyPassbook::getById($passbook_id);
        if($passbook == null){
        	
            $this->logger->debug("Passbook not found");
            throw new Exception("Passbook not found exception");
        }                              
                
        $context_array = array(
                                'push_token' => $passbook->getPushToken(),
                                'auth_token' => $passbook->getAuthToken(),
                                'pass_identifier' => $passbook->getPassIdentifier()                                 
                              );
        $payload = "";
        $push_job = new Job($payload, Priority::URGENT);
        $push_job->setContext($context_array);        
        $client = new AsyncClient("apns");
        $job_id = $client->submitJob($push_job);
       
        $this->logger->debug("Job queued with id: $job_id");        
        if(!$job_id > 0) {
        	
            $this->logger->error("Error in queuing up the job for passbook: $passbook_id");    
        }                

        $notes = "Notification sent for $type: $ref_id, job_id: $job_id";
        $this->updatePassbookLog($passbook_id, $ref_id, $type, date('Y-m-d H:i:s'), $notes);       
        
        return $job_id;
    }
    
   
    //creates the pkpass file and returns the path to the file 
    //passbook_service will return the file as a binary stream
    public function getLatestLoyaltyPass($pass_identifier, $serial_no, $header)        
    {
        $passbook = LoyaltyPassbook::getBySerialNo($serial_no);
        if(!$passbook){
        	
            $this->logger->debug("Passbook not found with serial no ".$serial_no);
            throw new ItemNotFoundException("Passbook not found");
        }       
        
        global $currentorg;
        $currentorg = new OrgProfile($passbook->getOrgId());
        $this->currentorg = $currentorg;
		
        $file_ids = $this->getFileIdsForPass('LOYALTY');
        $sf = new StoredFiles($this->currentorg);
        
        $background_img_row = $sf->retrieveContents($file_ids['background_img_1']);
        $background_img_2x = $sf->retrieveContents($file_ids['background_img_2']);
        $logo_1_row = $sf->retrieveContents($file_ids['brand_logo_1']);
        $logo_2_row = $sf->retrieveContents($file_ids['brand_logo_2']);
        $icon_1_row = $sf->retrieveContents($file_ids['icon_id_1']);
        $icon_2_row = $sf->retrieveContents($file_ids['icon_id_2']);
        $pass_row = $sf->retrieveContents($file_ids['pass_id']);
        
        $pass_json_string = $passbook->getJSON($pass_row['file_contents']);
        $this->currentorg = new OrgProfile($passbook->getOrgId());                                     
        
         $manifest_array = array(
                                'pass.json' => sha1($pass_json_string),
                                'strip.png' => sha1($background_img_row['file_contents']),
								'strip@2x.png' =>  sha1($background_img_2x['file_contents']),
                                'icon.png' => sha1($icon_1_row['file_contents']),
                                'icon@2x.png' => sha1($icon_2_row['file_contents']),
                                'logo.png' => sha1($logo_1_row['file_contents']),
                                'logo@2x.png' => sha1($logo_2_row['file_contents'])        
                               );
 


       
        $file = tempnam("tmp", $prefix);
        
        $pkfile = new ZipArchive();
        if(!$pkfile->open($file, ZipArchive::OVERWRITE)){
        	
            $this->logger->error("Error in creating the zip archive");
        }
        
        $pkfile->addFromString('strip.png', $background_img_row['file_contents']);
		$pkfile->addFromString('strip@2x.png', $background_img_2x['file_contents']);
        $pkfile->addFromString('icon.png', $icon_1_row['file_contents']);
        $pkfile->addFromString('icon@2x.png', $icon_2_row['file_contents']);
        $pkfile->addFromString('logo.png', $logo_1_row['file_contents']);
        $pkfile->addFromString('logo@2x.png', $logo_2_row['file_contents']);        
		$pkfile->addFromString('pass.json', $pass_json_string);
        $pkfile->addFromString('manifest.json', json_encode($manifest_array, true));
        
        $signature = "";        
        $certificatePath = "file://" . realpath('/usr/share/php/cert.pem');
        $pem_file = "file://" . realpath('/usr/share/php/private_key.pem');
        $wwdrca_file = "/usr/share/php/WWDRCA.pem";

		file_put_contents('/tmp/manifest.json', json_encode($manifest_array));

        $signature = Signature::PKCS7('/tmp/manifest.json', $signature, $certificatePath, array($pem_file, "deal20hunt"), array(), $wwdrca_file);

	$pattern = "/.*?Content-Disposition: attachment; filename=\".*?\"(.*?)-----.*?/sm";
	preg_match_all($pattern, $signature, $matchResult);
	$signature = base64_decode($matchResult[1][0]);

	/*
	$sign_parts = explode("\n", $signature);
	$signature = implode("\n", array_slice($sign_parts, 5, sizeof($sign_parts) - 1)); 
	*/

	$this->logger->debug("signature: " . $signature);
	
        $pkfile->addFromString('signature', $signature);
              
        $pkfile->close();        

     
        return $file;
                
    }
    
    //Creates the pkpass for the coupon and returns the path to the file
    //to passbook_service which returns the file as a binary stream
    public function getCouponPass($serial_no)
    {
    	
        $passbook = CouponPassbook::getBySerialNo($serial_no);
        if($passbook == null){
        	
            $this->logger->error("Passbook not found with serial no ".$serial_no );
            throw new ItemNotFoundException("Passbook Not Found");
        }       

        $user_id = $passbook->getUserId();
        $coupon_code = $passbook->getCouponCode();        
        $this->currentorg = new OrgProfile($passbook->getOrgId());
        $this->logger->debug("Coupon code: $coupon_code");
        
        $pass_json_string = $passbook->getJSON($pass_row['file_contents']);
        $file_ids = $this->getFileIdsForPass('COUPON');
        
        $sf = new StoredFiles($this->currentorg);        
        
        $background_img_row = $sf->retrieveContents($file_ids['background_img_1']);
        $background_img_2x = $sf->retrieveContents($file_ids['background_img_2']);
        $logo_1_row = $sf->retrieveContents($file_ids['brand_logo_1']);
        $logo_2_row = $sf->retrieveContents($file_ids['brand_logo_2']);
        $icon_1_row = $sf->retrieveContents($file_ids['icon_id_1']);
        $icon_2_row = $sf->retrieveContents($file_ids['icon_id_2']);   
     
		$this->logger->debug("file contents: " . print_r($background_img_row, true));
   
	

        $manifest_array = array(
                                'pass.json' => sha1($pass_json_string),
                                'strip.png' => sha1($background_img_row['file_contents']),
								'strip@2x.png' =>  sha1($background_img_2x['file_contents']),
                                'icon.png' => sha1($icon_1_row['file_contents']),
                                'icon@2x.png' => sha1($icon_2_row['file_contents']),
                                'logo.png' => sha1($logo_1_row['file_contents']),
                                'logo@2x.png' => sha1($logo_2_row['file_contents'])        
                               );
                
        $file = tempnam("tmp", $prefix);
        
        $pkfile = new ZipArchive();
        if(!$pkfile->open($file, ZipArchive::OVERWRITE)){
        	
            $this->logger->error("Error in creating the zip archive");
        }
        
        $pkfile->addFromString('strip.png', $background_img_row['file_contents']);
        $pkfile->addFromString('strip@2x.png', $background_img_2x['file_contents']);
        $pkfile->addFromString('icon.png', $icon_1_row['file_contents']);
        $pkfile->addFromString('icon@2x.png', $icon_2_row['file_contents']);
        $pkfile->addFromString('logo.png', $logo_1_row['file_contents']);
        $pkfile->addFromString('logo@2x.png', $logo_2_row['file_contents']);        
        $pkfile->addFromString('pass.json', $pass_json_string);
        $pkfile->addFromString('manifest.json', json_encode($manifest_array, true));
        
        $signature = "";        
        $certificatePath = "file://" . realpath('/usr/share/php/cert.pem');
        $pem_file = "file://" . realpath('/usr/share/php/private_key.pem');
        $wwdrca_file = "/usr/share/php/WWDRCA.pem";

		file_put_contents('/tmp/manifest.json', json_encode($manifest_array));

        $signature = Signature::PKCS7('/tmp/manifest.json', $signature, $certificatePath, array($pem_file, "deal20hunt"), array(), $wwdrca_file);


		$pattern = "/.*?Content-Disposition: attachment; filename=\".*?\"(.*?)-----.*?/sm";
		preg_match_all($pattern, $signature, $matchResult);
		$signature = base64_decode($matchResult[1][0]);

		/*
		$sign_parts = explode("\n", $signature);
		$signature = implode("\n", array_slice($sign_parts, 5, sizeof($sign_parts) - 1)); 
		*/

		$this->logger->debug("signature: " . $signature);
	
        $pkfile->addFromString('signature', $signature);
              
        $pkfile->close();        
        
        return $file;        
    }
    
    private function getFileIdsForPass($type)
    {
        $sql = "SELECT * FROM passbook_ui_configs WHERE org_id = " . $this->currentorg->org_id
        	. " AND type = '$type' ORDER BY id DESC LIMIT 1";
        
        $row = $this->db->query_firstrow($sql);
        
        return array(
                      'background_img_1' => $row['background_img_1'],
        			  'background_img_2' => $row['background_img_2'],
                      'brand_logo_1' => $row['brand_logo_1'],
                      'brand_logo_2' => $row['brand_logo_2'],
                      'icon_id_1' => $row['icon_id_1'],
                      'icon_id_2' => $row['icon_id_2'],
        			  'pass_id' => $row['pass_id']          
                    );              
    }
    
        
    public function createCouponPass($coupon_code, $user_id)
    {
       $cm = new ConfigManager(); 
       $pass_identifier = $cm->getKey(CONF_COUPON_PASS_IDENTIFIER);
       $pass_signature = $cm->getKey(CONF_COUPON_PASS_SIGNATURE); 
        
       $org_id = $this->currentorg->org_id; 
       /** 
        combination of the user_id and coupon code should be unique
        over time 
       **/  

       $secret_hash = base64_encode($user_id);         
       $serial_no = openssl_encrypt($coupon_code, ENCRYPTION_METHOD, $secret_hash); 
       $coupon_pass = CouponPassbook::getBySerialNo($serial_no);
       
       if(!$coupon_pass){

       	   $coupon_pass = new CouponPassbook();
	       $coupon_pass->setOrgId($org_id);
	       $coupon_pass->setUserId($user_id);
	       $coupon_pass->setPassIdentifier($pass_identifier);
	       $coupon_pass->setCreatedDate(date('Y-m-d H:i:s'));
	       $coupon_pass->setSerialNo($serial_no);
	       $coupon_pass->setFormat(1);
	       $coupon_pass->setAuthToken('');
	       $coupon_pass->setPushToken('');
	       $coupon_pass->setUpdatedOn(date('Y-m-d H:i:s'));
	       $coupon_pass->setLastSynced(date('Y-m-d H:i:s'));
	       $coupon_pass->setIsActive(true);
		   $passbook_id = $coupon_pass->save();
		   $this->logger->debug("new passbook created with id: $passbook_id");
       }else
       		$passbook_id = $coupon_pass->getId(); 
        
       return $passbook_id;  
       
    }
 

    public function createLoyaltyPass($user_id)        
    {
    	    
       $cm = new ConfigManager(); 
       $pass_identifier = $cm->getKey(CONF_LOYALTY_PASS_IDENTIFIER);
       $pass_signature = $cm->getKey(CONF_LOYALTY_PASS_SIGNATURE); 
        
       $org_id = $this->currentorg->org_id; 
       $serial_no = md5($user_id); 
       $auth_token = md5($user_id.$org_id);
         
       $loyalty_pass = new LoyaltyPassbook();
       $loyalty_pass->setOrgId($org_id);
       $loyalty_pass->setUserId($user_id);
       $loyalty_pass->setPassIdentifier($pass_identifier);
       $loyalty_pass->setCreatedDate(date('Y-m-d H:i:s'));
       $loyalty_pass->setSerialNo($serial_no);
       $loyalty_pass->setFormat(1);
       $loyalty_pass->setAuthToken($auth_token);
       $loyalty_pass->setPushToken('');
       $loyalty_pass->setUpdatedOn(date('Y-m-d H:i:s'));
       $loyalty_pass->setLastSynced(date('Y-m-d H:i:s'));
       $loyalty_pass->setIsActive(true);
                      
       $passbook_id = $loyalty_pass->save(); 
        
       $this->logger->debug("new passbook created with id: $passbook_id"); 
       return $passbook_id;  

    }
    
    public function generateCouponPass( $coupon_code, $user_id )
    {
    	$this->logger->debug("Creating a Coupon Pass for user: $user_id and coupon: $coupon_code");
    	$passbook_id = $this->createCouponPass($coupon_code, $user_id);
    	$coupon_pass = CouponPassbook::getById($passbook_id);
    	$loyalty_pass = LoyaltyPassbook::getByUserId($user_id, $this->currentorg->org_id);
    	$coupon_pass->setAuthToken($loyalty_pass->getAuthToken());
    	$coupon_pass->setPushToken($loyalty_pass->getPushToken());
    	$coupon_pass->update();
    	return $coupon_pass->getSerialNo();
    }
    
    public function generateLoyaltyPass( $user_id )
    {
    	$this->logger->debug("Creating a Loyalty Pass for user: $user_id ");
    	$passbook_id = $this->createLoyaltyPass($user_id);
    	$passbook = LoyaltyPassbook::getById($passbook_id);
    	return $passbook->getSerialNo();
    }
    
    public function __destruct(){
        
    }
      
}

?>
