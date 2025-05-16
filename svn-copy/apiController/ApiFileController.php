<?php 

include_once 'apiController/ApiBaseController.php';
/**
 * 
 * @author prakhar
 * 
 *File Controller is responsible for uploading/downloading
 *of all the files that is going in our system
 *
 *has Composite relationship with StoredFiles
 */
class ApiFileController extends ApiBaseController{
	
	private $StoreFiles;
	private $config_mgr;

	public function __construct(){
		
		parent::__construct();
		
		$this->StoreFiles = new StoredFiles( $this->org_model );
		$this->config_mgr = new ConfigManager();
	}
	
	/**
	 * Returns DataProviderFile
	 */
	public function &getDataProviderFile() {
		
		 return $this->StoreFiles->listFilesByTag( STORED_FILE_TAG_DATAPROVIDER );
	}

	/**
	 * Returns DataProviderFile As Options
	 */
	public function &getDataProviderFileAsOptions() {
		
		 return $this->StoreFiles->getFilesByTagAsOptions( STORED_FILE_TAG_DATAPROVIDER );
	}

	/**
	 * Returns Bill Template 
	 */

	public function &getBillTemplateFileAsOptions() {

		return $this->StoreFiles->getTemplatesByTagAsOptions( STORED_FILE_TAG_BILL_TEMPLATE );
	}
	
	/*
	 * Returns the default value for Edit option
	 */
	public function getSubject($id){
		global $currentorg;
		$row = $this->StoreFiles->retrieveContents($id);
		$subject = $row[file_name];
		$subject = substr($subject, strpos($subject, '-')+1);
		return $subject;
	}
	/*
	 * Return the default value for Edit option for email body
	 */
	public function getEmailBody($id){
		global $currentorg;
		$row = $this->StoreFiles->retrieveContents($id);
		$email_body = stripslashes($row['file_contents']);
		return $email_body;
	}
	public function getDataProviderFileId( $store_till_id ) {
		
		 return $this->StoreFiles->getDataProvidersFileIdForStore( $store_till_id );
	}

	/**
	 * Returns LegoPropertiesFile
	 */
	public function &getLegoPropertiesFile() {
		
		 return $this->StoreFiles->listFilesByTag( STORED_FILE_TAG_LEGO_PROPERTIES );
	}

	/**
	 * Returns LegoPropertiesFile AS options
	 */
	public function &getLegoPropertiesFileAsOptions() {
		
		 return $this->StoreFiles->getFilesByTagAsOptions( STORED_FILE_TAG_LEGO_PROPERTIES );
	}
	
	/**
	 * 
	 * @param unknown_type $store_till_id
	 */
	public function getLegoFileId( $store_till_id ){
		
		return $this->StoreFiles->getLegoPropertiesFileIdForStore( $store_till_id );
	}
	
	/**
	 * returns the valid printer options
	 */
	public function getPrinterOptions(){
		
		return array( 'bill', 'customer', 'dvs_voucher', 'points_redemption', 'campaign_voucher', 'gift_card', 'customer_search' );
	}
	
	/**
	 *Return Files As Options  
	 */
	public function &getPrinterFilesAsOptions() {
		
		return $this->StoreFiles->getFilesByTagAsOptions( STORED_FILE_TAG_PRINTER_TEMPLATE, -1 );
	}
	
	/**
	 * Returns PrinterTemplateFile
	 */
	public function &getPrinterTemplateFile() {
		
		 return $this->StoreFiles->listFilesByTag( STORED_FILE_TAG_PRINTER_TEMPLATE );
	}

	/**
	 * 
	 * @param unknown_type $store_till_id
	 */
	public function getPrinterTemplateFileId( $store_till_id, $templateType ){
		
		return $this->StoreFiles->getPrinterTemplateIdForStore( $store_till_id, $templateType );

	}
	
	/**
	 * PostEventIntegrationFile
	 */
	public function &getPostEventIntegrationFile() {
		
		return $this->StoreFiles->listFilesByTag( STORED_FILE_TAG_INTEGRATION_POST_OUTPUT, true, true );
	}

	/**
	 * IntegrationTemplateFile
	 */
	public function &getIntegrationTemplateFile() {
		
		return $this->StoreFiles->listFilesByTag( STORED_FILE_TAG_INTEGRATION_OUTPUT_TEMPLATE, true );
	}

	/**
	 * IntegrationTemplateFile
	 */
	public function &getIntegrationTemplateFileAsOptions( $type, $default ) {
		
		return $this->StoreFiles->getFilesByTagAsOptions( $type, $default );
	}
	
	/**
	 * 
	 * @param unknown_type $store_till_id
	 * @param unknown_type $template_type
	 */
	public function getIntegrationOutputTemplateFileId( $store_till_id, $template_type ){
		
		return $this->StoreFiles->getIntegrationOutputTemplateFileIdForStore( $store_till_id, $template_type );
	}
	
	/**
	 * 
	 * @param unknown_type $store_id
	 * @param unknown_type $templateType
	 * @param unknown_type $file_name_column
	 */
	public function getPostIntegrationOutputFileIds( $store_till_id, $templateType, $file_name_column = 'file_name' ){
		
		return $this->StoreFiles->getPostIntegrationOutputFileIdsForStore( $store_till_id, $templateType, $file_name_column);
	}
	
	/**
	 * IntegrationTemplateFile
	 */
	public function &getPostIntegrationTemplateFileAsOptions( $type, $default ) {
		
		return $this->StoreFiles->getFilesByTagAsOptions( $type, $default );
	}
	
	/**
	 * Uploads printer template file
	 */
	public function uploadDataProviderFile( $contents, $tag ) {
		
		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		return $this->StoreFiles->storeFile( 
											STORED_FILE_TAG_DATAPROVIDER, 
											$safe_contents, 
											"DataProviders-".$tag, 
											"xml", 
											$this->user_id
										);
	}
	
	public function updateEmailTemplateFile( $file_id , $contents , $tag ){
		
		//$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		return $this->StoreFiles->updateFile ($file_id,$tag, $contents);
		
	}
	
	public function uploadEmailTemplateFile( $contents, $tag , $file_id = false ) {
		
		//$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		
		if( !$file_id ){
			
				$result = $this->StoreFiles->getFileByName( 'EmailTemplate-'.$tag );

				if( $result )
					return false;
				else{
					return $this->StoreFiles->storeFile( 
													STORED_FILE_TAG_EMAIL_TEMPLATE, 
													$contents, 
													"EmailTemplate-".$tag, "html", 
													$this->user_id
												);
				}
		}else{
			return $this->StoreFiles->updateFile( 
												  $file_id, 
												  "EmailTemplate-".$tag, 
												   $contents 
												 );
		}
	}

	public function uploadEmailAttachmentTemplateFile( $contents, $tag, $extension = 'csv', $file_name = 'EmailAttachmentTemplate-' ) {
		
		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		return $this->StoreFiles->storeFile( 
											STORED_FILE_TAG_EMAIL_ATTACHMENT_TEMPLATE, 
											$safe_contents, 
											$file_name.'-'.$tag, $extension, 
											$this->user_id
										);
	}
	
	public function uploadfileTemplateFile( $contents, $tag ) {
		global $logger;
		$logger->debug($tag.'This is the tag area');
		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );

		return $this->StoreFiles->storeFile( STORED_FILE_TAG_EMAIL_TEMPLATE, 
											$safe_contents, 
											"EmailTemplate-".$tag, "html", 
											$this->user_id
										);
	}
	
	/***
	 * Uploads printer template file
	 */
	public function uploadPrinterTemplateFile( $contents, $tag ) {
		
		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		return $this->StoreFiles->storeFile( 
											STORED_FILE_TAG_PRINTER_TEMPLATE, 
											$safe_contents, 
											"PrinterTemplate-".$tag, 
											"xml", 
											$this->user_id
										);
	}
	
	/**
	 * Loads the file 
	 * @param  $contents
	 * @param  $tag
	 * @param  $type
	 * @param  $client_file_name
	 * @param  $extension
	 */
	
	public function uploadIntegrationPostEventFile( $contents, $tag, $type, $client_file_name, $client_file_monitoring_type, $extension ){

		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		$file_tag = STORED_FILE_TAG_INTEGRATION_POST_OUTPUT."_".$type;
		
		$file_id = $this->StoreFiles->storeFile( 
												$file_tag, 
												$safe_contents, 
												$file_tag.'_'.$tag, 
												$extension, 
												$this->user_id, '', 
												$client_file_name,
												$client_file_monitoring_type
												);

		return $file_id;
	}
	
	public function uploadIntegrationTemplateFile( $contents, $tag, $type ){

		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		$file_tag = STORED_FILE_TAG_INTEGRATION_OUTPUT_TEMPLATE."_".$type;
		
		$file_id = $this->StoreFiles->storeFile( 
												$file_tag, 
												$safe_contents, 
												$file_tag.'_'.$tag, 
												'txt', 
												$this->user_id
												);

		return $file_id;
		
	}
	
	
	public function uploadClientCronFile($contents, $tag, $extension, $client_file_name)
	{
		$safe_contents = $this->org_model->realEscapeString( $contents );
		//$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		$tag = $tag == "" ? "" : "_".$tag; 
		$file_tag = STORED_FILE_TAG_CLIENT_CRON;
		
		$file_id = $this->StoreFiles->storeFile( $file_tag, $safe_contents, $file_tag.$tag, $extension, $this->user_id, '', $client_file_name);

		return $file_id;
	}
	
	/* 
	 * For uploading image on the ftp
	 */	
	function FtpFileupload( $file_name , $file ){
		
		global $logger,$campaign_cfg;
		
		$ftp_server = $campaign_cfg['ftp']['host'];
		$logger->debug('Please print the ftp server'.$ftp_server);
		$ftp_user_name = $campaign_cfg['ftp']['username'];
		$logger->debug('Please print the ftp uname'.$ftp_user_name);
		$ftp_user_pass = $campaign_cfg['ftp']['password'];
		$logger->debug('Please print the ftp password'.$ftp_user_pass);
		$ftp_dir = $campaign_cfg['ftp-image-uploads']['absolute_path'];
		$logger->debug('Please print the ftp image path'.$ftp_dir);
		$ftp_folder = $campaign_cfg['ftp-image-uploads']['relative_path'];
		$logger->debug('Please print the ftp folder name'.$ftp_folder);
		
		$file_name = str_replace( " ", "_", $file_name ); 
		
		$result = $this->StoreFiles->isDuplicateFileName( $ftp_folder , $file_name );
		$this->logger->debug('@@@@@@@@@2This is the user id@@@@@@'.$this->user_id);
		
		if( $result ){
			throw new Exception( 'File with this Name already exists. Please try with different Name.' );
		}else{
			if($ftp_server && $ftp_user_name && $ftp_user_pass && $ftp_dir && $ftp_folder){
	
				$localfile = $file['tmp_name'];
				$fp = fopen($localfile, 'r');
				$ftp_file_name = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), '', stripslashes( $file_name ));
				$url='ftp://'.$ftp_user_name.':'.$ftp_user_pass.'@'.$ftp_server.$ftp_dir.$ftp_file_name;
		 		
				$ch = curl_init();
		 		curl_setopt($ch, CURLOPT_URL,$url );
		 		curl_setopt($ch, CURLOPT_UPLOAD, 1);
		 		curl_setopt($ch, CURLOPT_INFILE, $fp);
		 		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localfile));
		 		curl_exec ($ch);
		 		$error_no = curl_errno($ch);
		 		$logger->debug('curl_url'.$url);
		 		$logger->debug('@@@@@@@@@@@@@@@@@@22'.$error_no);
		 		curl_close ($ch);
			        
		 		if ($error_no == 0) {
			       	$this->StoreFiles->InsertFTPMapping( $file_name , $file , $this->user_id);
			       	return 'File Uploaded Succesfully.';
			    } else {
			    	
			    	$username = $this->currentuser->username;
			    	$orgname = $this->currentorg->name;
			    	$msg = "$orgname( $this->org_id ) </br> An Error occured while $username ( $this->user_id ) 
			    			is trying to upload image file using ftp. </br>Error code is $error_no";
			    	
			    	Util::sendEmail('apps-dev@capillarytech.com', 'Ftp Image Upload Failed', $msg, $this->org_id );
					//Util::sendSms('919900519451', "Error in Image Upload for $orgname ($this->org_id). Error code $error_no", $this->org_id );			    	

					throw new Exception( 'Error While File upload.' );
			    }
			}
			else
				throw new Exception( "Your FTP Account is Not Configured Properly." );
		}
		
	}

	/**
	 * download all the uploaded files by file id
	 */
	public function dowloadFile( $file_id ){
		
		$this->StoreFiles->downloadFile( $file_id );
	}
	
	/**
	 * delete all the uploaded files by file id
	 */
	public function deleteFile( $file_id ){
		$this->StoreFiles->deleteFile( $file_id );
	}
	
	/**
	 * Returns the options for the post event integration
	 * files 
	 */
	public function getIntegrationPostOutputTypes(){
		
		return 
			array(
				'points_redemption',
				'voucher_redemption',
				'voucher_issue',
				'customer_register',
				'customer_update',
				'customer_search',
				'bill_submit',
				'auto_configure',
	       		'nightly_sync',
	        	'eod_sync',
	        	'pre_auto_configure',
	       		'pre_nightly_sync',
	        	'pre_eod_sync',
				'os_startup',
				'gift_card',
                'wcf_deployment',
				'wallet_payment',
				'gift_card_recharge',
				'gift_card_redemption'
			);
	}

	/**
	 * Returns the options for the integration template
	 * files 
	 */
	public function getIntegrationOutputTemplateTypes()
	{	
		//DO NOT CHANGE	
		return 
			array(
				'points_redemption',
				'voucher_redemption',
				'voucher_issue',
				'customer_register',
				'customer_update',
				'customer_search',
				'bill_submit',
				'gift_card',
				'wallet_payment',
				'gift_card_recharge',
				'gift_card_redemption'
			);
	}
	
	public function getClientFileMonitoringTypes()
	{
		return array(
			'FILE_CHECK',
			'PROCESS_CHECK'
		);		
	} 
	
	/**
	 * returns allowed extension for the integration post event files 
	 */
	public function getIntegrationExtensions(){
		
		$extensions = $this->StoreFiles->mime_types_by_extension;
		
		return array_keys( $extensions );
	}
	
	/**
	 * CONTRACT :
	 * array(
	 * 
	 * 	'data_provider_file' => value,
	 *  'lego_properties_file' => value,
	 *  'printer_template_'* => value,
	 *  'STORED_FILE_TAG_INTEGRATION_OUTPUT_TEMPLATE_'** => value
	 *  
	 * )
	 * * = array('bill', 'customer', 'dvs_voucher', 'points_redemption', 'campaign_voucher')
	 * 
	 * ** = array('points_redemption', 'voucher_redemption', 'voucher_issue', 'customer_register', 'customer_update','bill_submit' );
	 * 
	 * *** = array('points_redemption','voucher_redemption','voucher_issue','customer_register','customer_update','bill_submit','auto_configure','nightly_sync','eod_sync','pre_auto_configure','pre_nightly_sync','pre_eod_sync',		);
	 * 
	 * @param unknown_type $params
	 */
	public function loadDeployedFileForStoreTill( $store_till_id, $params ){
		
		if ( $params['data_provider_file'] != NULL ){
			
			$this->StoreFiles->storeDataProvidersFileMappingForStore( $store_till_id, $params['data_provider_file'] );
		}
		
		//lego properties file
		if( Util::isStore21Org( $this->org_id ) && $params['lego_properties_file'] != NULL ){	
			
			$this->StoreFiles->storeLegoPropertiesFileMappingForStore( $store_till_id, $params['lego_properties_file'] );				
		}
		
		//Printer Template Mapping
		$printer_options = $this->getPrinterOptions();
		foreach( $printer_options as $row ){
			
			$templateType = $row;
			$file_id = $params['printer_template_'.$row];
			$this->StoreFiles->storePrinterTemplateFileMappingForStore( $store_till_id, $file_id, $templateType );					
		}
		
		$configManager = new ConfigManager();
		
		//Integration Output
		$integration_output_array = $this->getIntegrationOutputTemplateTypes();
		foreach($integration_output_array as $itype)
		{

			/*
			 * 'type' => STORED_FILE_TAG_INTEGRATION_OUTPUT_POINTS_REDEMPTION,
			 * 'key' => CONF_CLIENT_INTEGRATION_OUTPUT_POINTS_REDEMPTION_ENABLED
			 * */
			
			$type = STORED_FILE_TAG_INTEGRATION_OUTPUT_TEMPLATE.'_'.strtolower($itype);
		
			$itype_to_upper = strtoupper($itype);			
			$key = 'CONF_CLIENT_INTEGRATION_OUTPUT_'.$itype_to_upper.'_ENABLED';
			
			
			if( !$configManager->getKey( $key) ) continue;
				
			$file_id = $params[$type];
			$this->StoreFiles->storeIntegrationOutputTemplateFileMappingForStore( $store_till_id, $file_id, $type );
		}
		
		//Integration Post  Output
		$integration_post_output_array = $this->getIntegrationPostOutputTypes();
		foreach($integration_post_output_array as $itype)
		{	

			/*
	 		* 'type' => STORED_FILE_TAG_INTEGRATION_POST_OUTPUT_POINTS_REDEMPTION,
	 		* 'key' => CONF_CLIENT_INTEGRATION_POST_OUTPUT_POINTS_REDEMPTION_ENABLED
	 		* 
	 		* */

			$type = STORED_FILE_TAG_INTEGRATION_POST_OUTPUT."_".$itype;
			$key = 'CONF_CLIENT_INTEGRATION_POST_OUTPUT_'.strtoupper($itype).'_ENABLED';

			if( !$configManager->getKey( $key) ) continue;
			
			$file_ids = $params[$type];
			$this->StoreFiles->storePostIntegrationOutputFileMappingForStore( $store_till_id, array( $file_ids ), $type );						
		}		
	}
	
	/**
	 * 
	 * @param unknown_type $tag
	 * @param unknown_type $send_content
	 */
	public function returnFilesByTag( $tag, $send_content ) {

        $files = $this->StoreFiles->listFilesByTag( $tag, false, true, true );

        $response = array();
        foreach ($files as $file) {
        	
            array_push($response,
                array(
                    'file_id' => $file['id'],
                    'name' => $file['file_name'],
                    'contents' => $send_content ? $file['file_contents'] : ""
                )
            );
        }

        return  $response;
    }
    
    /**
     * Get Client Log Files
     * 
     * @param $store_id
     */
	public function getClientLogConfigFileIdForStore( $store_id ){
		
		return $this->StoreFiles->getClientLogConfigFileIdForStore( $store_id );
	}
    
	/**
	 * Lego Properties
	 * @param $store_id
	 */
	public function getLegoPropertiesFileIdForUser( $store_id ){
		
		return $this->StoreFiles->getLegoPropertiesFileIdForStore( $store_id );
	}
	
	/**
	 * Upload Client Log Config File
	 * @param  $contents
	 * @param  $tag
	 * @return $file_id
	 */
	
	public function uploadClientLogConfigFile( $contents, $tag ){
		
		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		$file_id = $this->StoreFiles->storeFile(
												STORED_FILE_TAG_CLIENT_LOG_CONFIG, 
												$safe_contents, 
												"ClientLogConfig-".$tag, 
												"xml", 
												$this->user_id
												);

		return $file_id;
	}

	/**
	 * Upload Client Log Config File
	 * @param  $contents
	 * @param  $tag
	 * @return $file_id
	 */
	
	public function uploadClientLogFile( $contents, $tag ){
		
		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		$file_id = $this->StoreFiles->storeFile(
												STORED_FILE_MAPPING_CLIENT_LOG_FILE, 
												$safe_contents, 
												"ClientLogConfig-".$tag, 
												"txt", 
												$this->user_id
												);

		return $file_id;
	}
	
	/**
	 * ClientLogConfigFile
	 */
	public function &getClientLogConfigFile() {
		return $this->StoreFiles->listFilesByTag( STORED_FILE_TAG_CLIENT_LOG_CONFIG );
	}
	
	/**
	 * Client Config File Mapping
	 */
	public function &getClientLogConfigFileMappingTable(){
		return $this->StoreFiles->getClientLogConfigFileMappingTable();
	}
	
	/**
	 * Upload lego Properties File
	 * @param $contents
	 * @param $tag
	 * @return file_id
	 */
	public function uploadLegoPropertiesFile( $contents , $tag ){
		
		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
		
		$file_id = $this->StoreFiles->storeFile(
												STORED_FILE_TAG_LEGO_PROPERTIES, 
												$safe_contents, 
												"LegoProperties-".$tag, 
												"properties", 
												$this->user_id
												);

		return $file_id;
	}
		
	/**
	 * Get LegoFile Mapping
	 */
	public function &getLegoFileMappingTable(){
		
		return $this->StoreFiles->getLegoPropertiesFileMappingTable();
	}
	
	/**
	 * 
	 * @param unknown_type $file_id
	 */
	public function updateClientStoreFileMapping( $file_id ){
		
		return $this->StoreFiles->storeClientLogConfigFileMappingForStore( $this->user_id, $file_id );
	}

	/**
	 * 
	 * @param unknown_type $file_id
	 */
	public function updateClientLogStoreFileMapping( $file_id ){
		
		return $this->StoreFiles->storeClientLogFileMappingForStore( $this->user_id, $file_id );
	}
	
	/**
	 * 
	 * @param unknown_type $start_date
	 * @param unknown_type $end_date
	 * @param unknown_type $stores
	 */
	public function getClientLogFilesTable( $start_date, $end_date, $store_tills ){
		
		return $this->StoreFiles->getClientLogFiles( $start_date, $end_date, $store_tills );
	}
	
/**
	 * 
	 * @param unknown_type $start_date
	 * @param unknown_type $end_date
	 * @param unknown_type $stores
	 */
	public function removeClientLogFiles( $start_date, $end_date, $store_tills ){
		
		return $this->StoreFiles->removeClientLogFiles( $start_date, $end_date, $store_tills );
	}
		
	//removeClientLogFiles($start_date, $end_date, $stores = '')
	
	public function getFilesByTagAsOptions($tag, $not_present_value = NULL, $extension = false) {
		
		return	$this->StoreFiles->getFilesByTagAsOptions($tag);
		
	}
	
	/*
	 * Adds new gift cards in the system
	 */
	public function addGiftCards($file_name, $lines_ignore = 1, $amount = 0) 
	{
		$this->logger->debug("Uploading gift cards for file $file_name");
		$ss = new Spreadsheet();
		$ss->LoadCSV($file_name);
		$gc_data = &$ss->getData();
		$this->logger->debug("Gift cards count: " . count($gc_data));
		
		$count = 0;
		$org_id = $this->currentorg->org_id;
		$user_id = $this->currentuser->user_id;
		
		$this->logger->debug("gc_data: " . print_r($gc_data, true));
		
		$skip_count = 0;
		$sql = "INSERT INTO gc_base(card_no, encoded_card_no, org_id, added_on, added_by, current_value, lifetime_value) VALUES ";
		foreach($gc_data as $k=>$row){
			
			if($skip_count < $lines_ignore)
			{
				++$skip_count;
				
				$this->logger->debug("$skip_count $lines_ignore");
				
				continue;
			}
			
			++$skip_count;
			
			$card_no = trim($row['Column 0']);
			$encoded_card_no = trim($row['Column 1']);
			if($card_no == "" || $encoded_card_no == ""){
				continue;
			}
			$r_sql = "('$card_no', '$encoded_card_no', $org_id, NOW(), $user_id, $amount, $amount),";
			$sql .= $r_sql;
			++$count;
		}			
		
		$sql = rtrim($sql, ',') . " ON DUPLICATE KEY UPDATE card_no = VALUES(card_no)";
		$db = new Dbase('users');
		$db->insert($sql);
		
		$rows_affected = $db->getAffectedRows();
		$this->logger->debug("uploaded cards: $count, added: $rows_affected");
		
		
		$sql = "INSERT INTO gc_transaction_log(card_id, org_id, type, amount, added_on, store_id, prev_value, bill_no)
					SELECT id, org_id, 'CREDIT', $amount, added_on, added_by, 0, '' FROM gc_base 
					WHERE added_on >= NOW() - INTERVAL 10 SECOND
		
				";
		
		$db->insert($sql);
		
		return $rows_affected;
	}
	
	/**
	 * Return the File Contents by file id
	 * @param unknown_type $id
	 */
	public function getFileContentsById( $file_id ){
		
		$row = $this->StoreFiles->retrieveContents( $file_id );
		
//		if( !$row )
//			throw new Exception('File content is not present');
		
		return $row;
	}
	/**
	 * This method saves the bill template into masters->ebill_service_template table.
	 */
	public function uploadBillTemplate( $contents , $template_name ) {
	
		$safe_contents = $this->org_model->realEscapeString( $contents );
		$this->logger->debug( "Contents : $contents, SafeContents : $safe_contents" );
	
		return $this->StoreFiles->storeOrganizationBillFile(
				$safe_contents,
				$template_name,
				$this->user_id
		);
	}
	
	/**
	 * This method allows to update the bill template.
	 * @param unknown_type $id
	 * @param unknown_type $params
	 */
	public function updateBillTemplate( $id , $params ){
		
		return $this->StoreFiles->updateBillTemplate($id, $params, $this->org_id);
		
	}
	
	//========================= passbook configuration ==============================

	public function uploadPassbookBackImage( $file_contents , $tag , $name, $extension ){
		
		$file_contents = $this->org_model->realEscapeString( $file_contents );
						
		return $this->StoreFiles->storeFile
								(
									$tag, 
									$file_contents, 
									$name, 
									$extension, 
									$this->user_id
								);
	}
	
	public function getPassbookImageFileName($id){
		$res=$this->StoreFiles->retrieveContents($id);
		return $res['client_file_name'];
	}
	
	public function getPassbookFile($id){
		
		$res = $this->StoreFiles->retrieveContents($id);
		return $res; 
	}
	/**
	 * 
	 * Uploading Communication Templates.
	 * @param unknown_type $params
	 */
	public function uploadCommTemplateFiles( $params ){
		
		$type = $params['type'].'_contents';
		if( !$this->StoreFiles->getFileByName( $params['file_name'] ) ){
			
				$file_id = $this->StoreFiles->storeFile( STORED_FILE_TAG_COMMUNICATION_TEMPLATE_FILE, 
												 $params[$type], 
									  			 $params['file_name'], 
									  			 'html', 
									  			 $this->user_id 
									 		  );
									 
			  $id = $this->StoreFiles->insertCommTemplate( $file_id, $params['type'] );
			  if( $id )
			  	return array( 'status' => true , 'msg' => 'Communication Template Created Successfully');
			  else
			  	return array( 'status' => false , 'msg' => 'Communication Template could not be created !');
			
		}else {
			return array( 'status' => false , 'msg' => 'Template With Same Name already Exists !');			
		}
	}

	/**
	 * 
	 * Getting file by name.
	 * @param unknown_type $name
	 */
	public function getFileIdByName( $name ){
		
		return $this->StoreFiles->getFileByName( $name );
	}
}
?>
