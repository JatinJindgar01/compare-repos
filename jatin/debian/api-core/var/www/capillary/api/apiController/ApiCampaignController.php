<?php

include_once 'helper/Timer.php';
include_once 'helper/Voucher.php';
include_once 'model/campaign.php';
include_once 'helper/Util.php';
include_once 'helper/FTPManager.php';
include_once 'base_model/class.OrgSmsCredit.php';
include_once 'helper/coupons/CouponManager.php';
include_once 'model_extension/class.OrganizationModelExtension.php';
include_once 'model_extension/campaigns/class.CampaignModelExtension.php';
include_once 'business_controller/campaigns/emails/OpenRateTracking.php';
include_once 'helper/scheduler/CampaignGroupCallBack.php';
include_once 'model_extension/campaigns/class.CampaignGroupModelExtension.php';
include_once 'business_controller/campaigns/message/impl/BulkMessageValidatorFactory.php';
include_once 'business_controller/campaigns/message/api/BulkMessageTypes.php';
include_once 'business_controller/campaigns/message/api/ValidationStep.php';
include_once 'base_model/campaigns/class.BulkMessage.php';
include_once 'business_controller/campaigns/audience/impl/UploaderFactory.php';
include_once 'business_controller/campaigns/audience/impl/CampaignStatus.php';
include_once 'business_controller/campaigns/DeDupManager.php';

/**
 * Campaign Controller
 *
 *
 * @author Ankit Govil
 */
class CampaignController extends BaseController{

	private $ConfigManager;
	private $CouponManager;
	private $campaignsModel;
	private $OrgController;

	private $C_open_rate_tracking;
	
	private $upload_audience_path;
	private $ftp_audience_path;
	private $ftp_copy_path;
	private $timer_prev;
	private $timer_curr;
	private $org_sms_credit_model;
	
	private $FtpManager;
	public  $campaign_model;
	public 	$campaign_model_extension;
	private $CampaignGroup;
	private $C_BulkMessage;
	private $C_dedup_manager;
	
	public  $memcache_mgr;
	
	public function __construct(){

		parent::__construct();
		$this->upload_audience_path = "/mnt/campaigns/uploads/";
		$this->CouponManager = new CouponManager();
		$this->ftp_audience_path = "/home/capillary/campaigns/";
		$this->ftp_copy_path = "/mnt/campaigns/import/org_{{org-id}}/campaign_{{campaign-id}}/group_{{group-name}}/";
		$this->ConfigManager = new ConfigManager();
		$this->campaignsModel = new CampaignModel();
		$this->OrgController = new OrganizationController();
		$this->C_open_rate_tracking = new OpenRateTracking( );
		$this->campaign_model_extension = new CampaignModelExtension();
		$this->CampaignGroup = new CampaignGroupModelExtension();
		
		$this->campaign_model = $this->campaign_model_extension;
		$this->DownloadManager = new DownloadManager();

		$this->timer_prev = new Timer("timer_prev");
		$this->timer_curr = new Timer("timer_curr");

		$this->org_sms_credit_model = new OrgSmsCreditModel();
		$this->C_BulkMessage = new BulkMessage();
		$this->memcache_mgr = MemcacheMgr::getInstance();
		
		$this->C_dedup_manager = new DeDupManager();
	}

	/**
	 *
	 * @param unknown_type $groups_ids: csv of group ids as a string
	 */
	public function getGroupDetailsbyGroupIds( $groups_ids ){
		return $this->campaign_model_extension->getGroupDetailsbyGroupIds( $groups_ids );

	}

	public function getAllCampainRunningStatus(){

		return $this->campaign_model_extension->getAllCampainRunningStatus();
	}
	
	public function getCampaignRunningStatus( $campaign_type = 'outbound' ){
		return $this->campaign_model_extension->getCampainRunningStatus( $campaign_type );
	}
	
	/**
	 * 
	 * @param $group_id
	 */
	public function getGroupDetails( $group_id ){
		
		return $this->campaign_model_extension->getGroupDetails( $group_id );
	}
	
	/*
	 * fetches group details based on the campaign id
	 */
	public function getCampaignGroupsByCampaignIds( $campaignids  ){

		return $this->campaign_model_extension->getCampaignGroupsByCampaignIds( $campaignids );
	}

	/**
	 * @param unknown_type $groupId
	 */
	public function getCountForGroup( $groupId ){

		return $this->campaign_model->getCountForGroup( $groupId );
	}	
		
	public function deDupUsingBitMasking( $campaign_id, $id_labels_map, $create_new_groups = false ){
		
		//If new groups required, check for duplicate group names
		if( $create_new_groups ){
				
			$label_copy = $id_labels_map;
			array_shift( $label_copy );
			
			foreach( $label_copy as $group_id => $group_label ){
				
				$this->campaign_model_extension->isGroupNameExists( $group_label, $campaign_id );
			}
		}

		//DeDuplication
		try{
			
			$this->C_dedup_manager->deDuplicate( $campaign_id, $id_labels_map, $create_new_groups );
		}catch( Exception $e ){
			
			throw $e;
		}
	}
	
	public function getTypesOfCampaign( $org_id ){
		
		return $this->campaign_model_extension->getTypesOfCampaign( $org_id );
		
	}
	
	/**
	 * @param unknown_type $where_filter
	 * @return multitype:multitype:string unknown
	 */
	public function createTableContentForHomePage ( $where_filter, $search_filter ){

		$org_id = $this->org_id;
		$table_content = array();
		$type_id = array();
		$per_campaign_data = array();
		
		//To get Campaign Details for Outbound Campaigns
		
		$this->results = 
			$this->campaign_model_extension->
			getDataForHomePageOutbound( $org_id, $where_filter, $search_filter );
		
		//View Page Url	
		$url = new UrlCreator();
		$url->setNameSpace('campaign/v2/base');
		$url->setPage( 'Info' );
		
		//Coupon Url
		$c_url = new UrlCreator();
		$c_url->setNameSpace( 'campaign/v2/coupons' );
		$c_url->setPage( 'CreateOutBoundCoupons' );
		
		//customer list url
		
		$cust_url = new UrlCreator();
		$cust_url->setNameSpace( 'campaign/audience' );
		$cust_url->setPage( 'Home' );
		
		//Reports url
		
		$r_url = new UrlCreator();
		$r_url->setNameSpace( 'campaign/roi' );
		$r_url->setPage( 'Home' );
		
		foreach( $this->results as $key => $value ){
			
			$url->setPageParams( array( 'campaign_id' => $value['id'] ));
			$view_url = $url->generateUrl();
			
			$c_url->setPageParams( array( 'campaign_id' => $value['id'] ) );
			$coupon_url = $c_url->generateUrl();

			$cust_url->setPageParams( array( 'campaign_id' => $value['id' ] ) );
			$cust_list = $cust_url->generateUrl();
			
			$r_url->setPageParams( array( 'campaign_id' => $value['id'] ) );
			$report_url = $r_url->generateUrl();
			
			$group_label_count = array();
			$sample_group_labels = explode( ",", $value[ 'audience' ] );
			$sample_group_count = explode(  ",", $value[ 'audience_count' ] );
			
			if( $value['type'] == 'action' )
					$icon = "<i class='icon-exclamation-sign icon-large center font-green margin-right'></i>";
			else if( $value['type'] == 'referral' )
				$icon = "<i class='icon-group icon-large center font-green margin-right'></i>";
			else
				$icon = "<i class='icon-bullhorn icon-large center font-green margin-right'></i>";
				
			for($i = 0; $i<2; $i++ ){
				
				if( isset( $sample_group_labels[ $i ] ) ){
						
					//$group_label_count[ $sample_group_labels[ $i ] ] = intval( $sample_group_count[ $i ] );
					$group_label_count[ $i ] = $sample_group_labels[ $i ]." : <span class=font-green> ".intval( $sample_group_count[ $i ] )."</span>";
		
				}
			}
				
			$audience = $value[ 'audience_overall' ] ?
 						"<div class='text-overflow'>".$group_label_count[ 0 ]."</div>
 						 <div class='text-overflow'>".$group_label_count[ 1 ]."</div>" :
						'';
			$this->logger->debug( "homepageaudience".$value[ 'id' ]."=>".print_r( $audience, true ) );
			
			//Short campaign name if it exceeds 22 char.
			if( strlen( $value['campaign_name'] ) > 20 )
				$campaign_name = substr( $value['campaign_name'], 0 , 18 ).'...';
			else
				$campaign_name = $value['campaign_name'];
				
			$table_content[ $value[ 'id' ] ] =
			array(
					'campaign_name'			=> "<h4 class='uppercase' tooltip='".$value['campaign_name']."'> $icon"
												.(
													Util::beautify( $campaign_name )?
													Util::beautify( $campaign_name ):
													'campaign name not provided'
												 )
												."</h4>".
												"<h5 style='margin-left:12%;' class='font-grey'> From : ".date( 'dS F, Y', strtotime( $value[ 'start_date' ] ) ).
												"<br /> To : ".date( 'dS F, Y', strtotime( $value[ 'end_date' ] ) )." </h5>",
					
					'campaign_type'			=> '<h4>Customer List</h4>'
												."<h5 class='font-grey'>Total Customers : <span class='font-green'>"
												.( 
												   $value[ 'audience_overall' ] ? 
												   $value[ 'audience_overall' ] : 
												   0
												 )
												."</span><br/>".$audience,

					'description_of_coupon'	=> "<h4 class='uppercase'>".( 
												 Util::beautify( $value[ 'description' ] )? 
												 Util::beautify( $value[ 'description' ] )."<h5 class=font-grey> Total Coupons Attached : 1 </h5>" : 
												'No Coupons Attached' 
												),
											/*."</h3><h5 class='font-grey'>Total Coupons Attached :<span class=font-green> "
											.( $value[ 'description' ]? 1 : 0 )
											."</span></h5>", */
										
					'coupon'  				=> $value[ 'description' ] ?
											( 
												"<h4>Total Redeemed : <span class=font-green>".$value[ 'num_redeemed' ]
												."</span></h4><h4>Total Issued : <span class=font-green>".$value[ 'num_issued' ]."</span></h4>"
											) :
											(
												"<h4>Total Redeemed : <span class=font-green>0</span></h4>"
												."<h4>Total Issued : <span class=font-green>0</span></h4>"		
											),

					'view/edit'				=> '
												<div>
													<div class="home-btn-primary" onclick=window.location.href="'.$view_url.'">
														<i class="icon-edit"></i> View </div>
														
													<div class="home-btn-primary" onclick=window.location.href="'.$report_url.'">
														<i class="icon-bar-chart"></i> Reports</div>
														
													<div class="home-btn-primary" onclick=window.location.href="'.$cust_list.'">
														<i class="icon-list"></i>  List</div>
														
													<div class="home-btn-primary" onclick=window.location.href="'.$coupon_url.'">
														<i class="icon-certificate"></i> Coupons</div>
												</div>				
											   ',
					
					'id'					=> $value[ 'id' ]
											
			);
			
		}

		
		//To get Campaign Details for Action Based Campaigns and Ref Based Campaigns
				
		$campaigns_data = 
			$this->campaign_model_extension->
			getCampaignDetailsForActionBased( $org_id, $where_filter, $search_filter );
		
		$this->logger->debug( 'fishies'.print_r( $campaigns_data , true ) );
		
		$mapping_campid_vouchers_action = array();
		foreach( $campaigns_data as $key => $value ){
		
			if( isset( $value[ 'voucher_series_id' ] ) && $value[ 'voucher_series_id' ]!= -1 ){

				$json_decoded = json_decode( $value[ 'voucher_series_id' ], true );
				
				if( $value[ 'type' ] == 'action' ){

					$mapping_campid_vouchers_action[ $value[ 'id' ] ] = $json_decoded;
					$voucher_ids = implode( ",", $json_decoded );
									
				}
				else{
					
					$mapping_campid_vouchers_action[ $value[ 'id' ] ] = array_values( $json_decoded );
					$voucher_ids = implode( ",", array_values( $json_decoded ) );
						
				}

				$decode_values .= $voucher_ids;
				$decode_values .=",";				
				
			}
				
		}
		
		$decode_values = substr_replace($decode_values, "", -1 );
		$this->logger->debug( "reached here for voucherdata ". $decode_values, true );
		
		$voucher_data = array();
		$mapping_voucherid_description = array();
		if( strlen( $decode_values ) > 0 ){

			$voucher_data = $this->campaign_model_extension->getVoucherDataForHomePageActionBased( $org_id, $decode_values );
				
		}
		
		$this->logger->debug( 'fishies'.print_r( $voucher_data , true ) );
		
		foreach( $voucher_data as $row ){
				
			$mapping_voucherid_description[ $row[ 'id' ] ] = array(
					'desc' => $row[ 'description' ],
					'issued' => intval( $row[ 'num_issued' ] ),
					'redeemed' => intval( $row[ 'num_redeemed' ] )
			);
			
		}

		
		$count_for_campaign = array();
		$coupon = array();
		
		foreach( $mapping_campid_vouchers_action as $key => $value ){
		
			$count_of_voucher_series = 0;
			$total_issued = 0;
			$total_redeemed = 0;
			$first_voucher_series_id = $mapping_campid_vouchers_action[ $key ][ 0 ];
		
			foreach( $value as $voucher_id ){
		
				$total_issued += $mapping_voucherid_description[ $voucher_id ][ 'issued' ];
				$total_redeemed += $mapping_voucherid_description[ $voucher_id ][ 'redeemed' ];
				$count_of_voucher_series++;
		
			}
				
			$coupon[ $key ] = array(
						
					'first_info' => array(
							'desc'		=> $mapping_voucherid_description[ $first_voucher_series_id ][ 'desc' ],
							'redemption'=> $mapping_voucherid_description[ $first_voucher_series_id ][ 'redeemed' ],
							'issued'	=> $mapping_voucherid_description[ $first_voucher_series_id ][ 'issued' ]
					),
		
					'overall' => array(
							'redemption'=> $total_redeemed,
							'issued'	=> $total_issued
					)
						
			);
			
			$count_for_campaign[ $key ] = $count_of_voucher_series;
			
		}
		
		$this->logger->debug( "reached here" );
		$this->logger->debug( "reached here".print_r( $coupon , true ) );
		
		$action = new UrlCreator();
		$action->setNameSpace( 'campaign/trackers' );
		$action->setPage( 'Home' );
		
		foreach( $campaigns_data as $key => $value ){

			$url->setPageParams( array( 'campaign_id' => $value['id'] ));
			$view_url = $url->generateUrl();
			
			$c_url->setPageParams( array( 'campaign_id' => $value['id'] ) );
			$coupon_url = $c_url->generateUrl();
			
			$r_url->setPageParams( array( 'campaign_id' => $value['id'] ) );
			$report_url = $r_url->generateUrl();

			$action->setPageParams( array( 'campaign_id' => $value['id'] ) );
			$action_url = $action->generateUrl();

			if( $value['type'] == 'action' ){
				
				$view_url = "/campaign/rules/basic_config/NewRule?campaign_id=".$value['id']."&mode=create";
				$report_url = "/campaign/rules/basic_config/Reports?campaign_id=".$value['id'];
				$action_url =  "/campaign/rules/basic_config/NewRule?campaign_id=".$value['id']."&mode=create";
				$coupon_url = "/campaign/v2/coupons/CreateBounceBackCoupons?campaign_id=".$value['id'];
			}else if( $value['type'] == 'referral' ){
				$coupon_url = '/campaign/coupons/Create?q=a&campaign_id='.$value['id'];
			}
			
			if( $value['type'] == 'action' )
					$icon = "<i class='icon-exclamation-sign icon-large center font-green margin-right'></i>";
			else if( $value['type'] == 'referral' )
				$icon = "<i class='icon-group icon-large center font-green margin-right'></i>";
			else
				$icon = "<i class='icon-bullhorn icon-large center font-green margin-right'></i>";
			
							//Short campaign name if it exceeds 22 char.
			if( strlen( $value['name'] ) > 20 )
				$campaign_name = substr( $value['name'], 0 , 18 ).'...';
			else
				$campaign_name = $value['name'];
			
			if( $value['type'] == 'action' )
				$campaign_type = 'Bounceback campaigns';
			else
				$campaign_type = $value['type']." campaign";
								
			$table_content[ $value[ 'id' ] ] =
			array(
					
						'campaign_name'	=> "
											<h4 class='uppercase' tooltip='".$value['name']."'> $icon"
										   .
											(
										   		Util::beautify( $campaign_name )?
												Util::beautify( $campaign_name ):
												'campaign name not provided'
										   	)	
											."</h4>"
										   ."<h5 class='font-grey' style='margin-left:12%;'> From : ".date( 'dS F, Y', strtotime( $value[ 'start_date' ] ) ).
										   " <br />To : ".date( 'dS F, Y', strtotime( $value[ 'end_date' ] ) )."</h5>
										   ",

						'campaign_type'	=> "<h4 class='uppercase'>".$campaign_type."</h3>" ,
					
						'description_of_coupon'	=> $coupon[ $value[ 'id' ] ] ?
												(
														"<h4 class='uppercase'>"
														.Util::beautify( $coupon[ $value[ 'id' ] ][ 'first_info' ][ 'desc' ] )
														."</h3><h5 class=font-grey>Total Coupons Attached : <span class=font-green>".
														(
																$count_for_campaign[ $value[ 'id' ] ] ?
																$count_for_campaign[ $value[ 'id' ] ] :
																0
														)
														."</span></h5>"
														
												) :
												(
														'<h4>No Coupons Attached</h4>'
												),
					
						'coupon' 		=> $coupon[ $value[ 'id' ] ] ?
												(

													"<h4>Total Redeemed : <span class=font-green>".$coupon[ $value[ 'id' ] ][ 'overall' ][ 'redemption' ]
													."</span></h4><h4>Total Issued : <span class=font-green>".$coupon[ $value[ 'id' ] ][ 'overall' ][ 'issued' ] 
													."</span></h4>"	
												) :
												(
											"<h4>Total Redeemed : <span class=font-green>0</span></h4>"
											."<h4>Total Issued : <span class=font-green>0</span></h4>"
												),
					

						'view/edit'		 =>   '<div>
													<div class="home-btn-primary" onclick=window.location.href="'.$view_url.'">
														<i class="icon-edit"></i> View </div>
														
													<div class="home-btn-primary" onclick=window.location.href="'.$report_url.'">
														<i class="icon-bar-chart"></i> Reports</div>
														
													<div class="home-btn-primary" onclick=window.location.href="'.$action_url.'">
														<i class="icon-exclamation-sign"></i>  Actions</div>
														
													<div class="home-btn-primary" onclick=window.location.href="'.$coupon_url.'">
														<i class="icon-certificate"></i> Coupons</div>
												</div>
												',
												
						'id'			=> $value[ 'id' ]
				);
		}

		krsort( $table_content );
		
		//var_dump($table_content);
		//$this->logger->debug("homepage_table".print_r($table_content, true) );
		
		return $table_content;
		
	}
	
	
	/*
	 * fetches group details based on the campaign id
	 */
	public function getCampaignGroupsByCampaignIdAsOptions( $campaignid ){

		return $this->getGroupsAsOptionByCampaignId( $campaignid );
	}

	public function validatedate( $date, $msg='' ){

//		if(strtotime( $date ) < strtotime(date('Y-m-d'))){
//			throw new Exception("Please select correct $msg date");
//		}
		//checking one day lesser limit for expiry
		if(strtotime( $date ) < strtotime(date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))))){
			throw new Exception("Please select correct $msg date");
		}
		
	}

	public function getUsersFromGroup( $group_id, $tag_array ){}

	/**
	 *
	 * @param unknown_type $group_id
	 * @param unknown_type $campaign_id
	 * @param unknown_type $msg
	 * @param unknown_type $msg_subject
	 */
	public function getUsersPreviewTable($group_id, $campaign_id, $msg, $msg_subject = false , $queue_type = 'SMS' ){
	
		$this->logger->debug('@@In validate '.$campaign_id);
		$params = array( 'message' => $msg , 'subject' => $msg_subject );
		$params = json_encode( $params );
	
		//validating data
		$C_bulk_message = new BulkMessage();
		$C_bulk_message->setCampaignId( $campaign_id );
		$C_bulk_message->setGroupId( $group_id );
		$C_bulk_message->setParams( $params );
	
		$C_bulk_validator = BulkMessageValidatorFactory::getValidator( BulkMessageTypes::valueOf( $queue_type ) );
	
		$messages_list = $C_bulk_validator->validate( $C_bulk_message , ValidationStep::$PREVIEW );
	
		return $messages_list;
	}
	
	public function groupDirectMailDetails( $tags_template, $group_id, $campaign_id){}
	
	/**
	 *
	 * @param unknown_type $group_id
	 * @param unknown_type $campaign_id
	 * @param unknown_type $msg
	 * @param unknown_type $msg_subject
	 */
	public function getStoreTaskStoresPreviewTable( $msg, $stores ){

		$messages_list = array();

		$count = 0;
		foreach( $stores as $store_id ){

			$count++;
			$profile = StoreProfile::getById( $store_id );

			$template_arguments['store_name'] = $profile->first_name .' '.$profile->last_name;

			$message = Util::templateReplace( $msg, $template_arguments, array( "{{NA}}" ) );
			$message = stripslashes( $message );

			$this->logger->debug( "msg=$msg. Args: ".print_r($template_arguments, true)."\nResult:$message" );

			array_push(
			$messages_list,
			array(
					"to"=>$profile->mobile, 
					"msg"=>$message, 
					'chars' => strlen( $message ) 
			)
			);

			if( $count == 9 ) break;
		}
		return $messages_list;
	}

	/**
	 * @param unknown $org_id
	 * @param unknown $params
	 * @return string
	 */
	public function prepareCampaignSettingsValues( $org_id , $params ){
		
		$values = array();
		foreach( $params as $key => $value ){
			if( !in_array( $key, array( 'is_form_submitted' , 'random_key_generator' ) ))
				array_push( $values, "  ( '$org_id','$key','$value' ) " );
		}

		return implode(",", $values);
	}
	
	/**
	 *
	 * @param unknown_type $values: set of values for organization to be inserted
	 */
	function insertMsgingDefaultValues( $values ){

		return $this->campaign_model_extension->insertMsgingDefaultValues( $values );

	}

	/**
	 *
	 * @param unknown_type $status: status of message
	 * @param unknown_type $type : type of campaign
	 */
	public function getQueuedMessages( $status, $type ){

		return $this->campaign_model_extension->getQueuedMessages( $this->org_id, $status, $type);
	}


	/**
	 *
	 * @param $status
	 * @param $type
	 */

	public function getQueuedEmailMessages( $status, $type ){

		return $this->campaign_model_extension->getQueuedEmailMessages( $status, $type);
	}

	/**
	 *
	 * @param unknown_type $msg_id: message for which data has to be fetched
	 */
	public function getMessageScheduleType( $msg_id ){

		return $this->campaign_model_extension->getMessageScheduleType( $msg_id );

	}

	/*
	 * It makes a call to thrift to send message to user
	 */

	public function sendMessageToUsers($campaign_id,$group_id,$sent_msg,$bulk_credits,
			$default_arguments, $queue_id, $guid ){

		$msg_id = Util::sendBulkSMSToGroupsnew( $sent_msg, $this->org_id, $group_id, 
				$default_arguments, $queue_id, $guid );

		return $msg_id;
	}

	/*
	 * it fetches user
	 */
	public function getUsersByQueueId( $id , $type){

		$md = $this->campaign_model_extension->getQueuedMessageDetailsById($id);
		$params = json_decode($md['params']);

		$group_id = $md['group_id'];
		$campaign_id = $md['campaign_id'];
		$msg = $params->message;

		$queue_type = 'SMS';
		
		if ( strtolower( $type ) == 'email' || strtolower( $type ) == 'email_reminder'){
			
			$msg_subject = $params->subject;
			//Comment this line for new flow
			//$msg = html_entity_decode($params->message);
			$msg = $params->message;
			$queue_type = 'EMAIL';
			
		}else if( strtolower( $type ) == 'customer_task' ){
			
			$queue_type = 'CUSTOMER_TASK';
			$msg = stripcslashes( rawurldecode( $params->store_task_display_text ) );
			$msg_subject = stripcslashes( rawurldecode( $params->store_task_display_title ) );
		}
		else
			$msg_subject = false;
		
		return $this->getUsersPreviewTable( $group_id, $campaign_id, $msg , $msg_subject , $queue_type );
	}
	
	/**
	 * Message id whose status is to be change
	 * @param unknown_type $message_id
	 */
	public function reQueueMessage( $message_id ){

		return $this->campaign_model_extension->reQueueMessage( $message_id, $this->user_id );
	}

	/**
	 * Approve message
	 * @param unknown_type $message_id : update message id
	 */

	public function approveMessage( $message_id ){

		return $this->campaign_model_extension->approveMessage( $message_id, $this->user_id );
	}

	/**
	 * get the message count for particular campaign and group
	 * @param $campaign_id
	 * @param $group_id
	 */
	public function getMessageCount( $campaign_id, $group_id ){

		return $this->campaign_model_extension->getMessageCount( $campaign_id, $group_id );

	}

	public function getDefaultValuesbyMessageId( $message_id ){

		$values = array();
		
		if ( $message_id ){

			$this->C_BulkMessage->load( $message_id ) ;
			
			$default_values = $this->C_BulkMessage->getHash();

			$default_arguments = json_decode( $default_values['default_arguments'] , true );
			
			if( count($default_arguments) > 0 )
				$values = array_merge( $values , $default_arguments );
			
			$params = json_decode( $default_values['params']);
			$values['message'] = $params->message;
			$values['subject'] = $params->subject;
			$values['group_id'] = $default_values['group_id'];
			$values['signature'] = $params->signature;
			$values['signature_value'] = $params->signature_value;
			
			if ( $default_values['scheduled_type'] == 'IMMEDIATELY'){

				$values['send_when'] = 'IMMEDIATE';
			}else if ( $default_values['scheduled_type'] == 'SCHEDULED'){

				$values['send_when'] = 'SCHEDULE';
				$values['date_time'] = explode( ' ', $this->campaign_model->getDateFromReminder( $message_id ) );
					
				//get minutes
				$values['cron_minutes'] = $values['date_time'][0];

				//get hours
				$values['cron_hours'] = $values['date_time'][1];

				//get days of month
				$values['cron_days_month'] = explode(',',$values['date_time'][2]);
				$values['cron_months'] = explode(',', $values['date_time'][3]);
				$values['cron_week'] = explode(',', $values['date_time'][4]);
			}else{

				$values['send_when'] = 'PARTICULAR_DATE';

				$values['scheduled_on'] = explode(' ' , $default_values['scheduled_on'] );
				$values['date']	= $values['scheduled_on'][0];
				$values['group_id'] = $default_values['group_id'];
				$values['time'] = explode(':', $values['scheduled_on'][1]);
				$values['hours'] = $values['time'][0];
				$values['minutes'] = $values['time'][1];
			}

		}else{

			$values['message'] = "Dear {{fullname}}, {type the rest of your message here}" ;
			$values['send_when'] = 'IMMEDIATE';

			//cron default values to be put in
			$values['cron_day'] = '*';
			$values['cron_week'] = '*';
			$values['cron_month'] = '*';

			$values['hours'] = '10';
			$values['minutes'] = '00';
			$values['cron_hours'] = '10';
			$values['cron_minutes'] = '00';
			$values['date'] =  date( 'Y-m-d' );
		}
	
		return  $values;
	}

	public function getDefaultFieldValue(){

		return $this->campaign_model_extension->getDefaultFieldValue( $this->org_id );
	}

	public function reduceBulkCredits( $group_id ){

		return $this->campaign_model_extension->reduceBulkCredits( $this->org_id, $group_id );
	}

	public function getCampaignIdByGroupId ( $group_id ){

		return $this->campaign_model_extension->getCampaignIdByGroupId( $group_id );
	}


	public function addFieldsToBulkSmsCampaign( $campaign_id,$msg_id,$group_id,$queue_id ){

		$this->campaign_model_extension->addFieldsToBulkSmsCampaign( $campaign_id,$msg_id,
				$group_id, $queue_id );

	}

	public function updateLastSentDateForGroup( $group_id ){

		$this->campaign_model_extension->updateLastSentDateForGroup( $group_id );
	}


	/**
	 * @param unknown_type $campaign_id :
	 * @param unknown_type $group_id : group_id group
	 */
	public function getAudienceGroupByCampaignIdAndGroupId( $campaign_id, $group_id ){

		return $this->campaign_model_extension->getAudienceGroupByCampaignIdAndGroupId( $campaign_id, $group_id );
	}

	/**
	 * @param unknown_type $campaign_id :
	 * @param unknown_type $group_id : group_id group
	 */
	public function getAudienceDetailsByGroupId( $campaign_id, $group_id ){

		return $this->campaign_model_extension->getAudienceDetailsByGroupId( $campaign_id, $group_id );
	}

	public function getReminderIdBytaskId( $id ){

		return $this->campaign_model_extension->getReminderIdBytaskId( $id );

	}

	public function getDateFromRemidner( $id ){

		return $this->campaign_model_extension->getDateFromReminder( $id );


	}

	public function getReminderByReferenceId( $id ){

		return $this->campaign_model_extension->getReminderByReferenceId( $id );
	}

	public function getCampaignsMessageDetails( $campaign_id, $type = 'query', $approved_filter = '' ){

		return $this->campaign_model_extension->getCampaignsMessageDetails( $campaign_id, $type , $approved_filter );
	}

	/**
	 *
	 * @param unknown_type $campaign_id : campaign_id
	 */
	public function load( $campaign_id ){

		$this->campaign_model_extension->load( $campaign_id );
	}

	/**
	 * It is used to generate form for Upload Audience CSV page and also for Sticky group
	 * Common Method use for both
	 * @param $upload_form
	 * @param $sticky_group
	 */

	public function createUploadSubscribersForm( &$upload_form , $sticky_group = false ){


		$csvFile = new FileFieldInput( $upload_form , 'csvFile' , 'Upload Csv File' );
		$csvFile->setHelpText( 'Columns : email or mobile , user_name ' );
		$upload_form->addInputField( $csvFile );


		if( !$sticky_group ) {

			$custom_tag = new ButtonFieldInput( $upload_form , 'add_custom_tag' , ' ', '+ Add Custom Tag');
			$custom_tag->triggerClickToCloneCustomField( $upload_form->getFormName() );
			$upload_form->addInputField( $custom_tag );

			$custom_field_count = new HiddenFieldInput( $upload_form , 'custom_tag_count' , 'Custom Tag Count' , '0' );
			$upload_form->addInputField( $custom_field_count );
			
		}

		$group_name = new TextFieldInput( $upload_form , 'group_name' , 'Give list name' );
		$group_name->setDefaultValue( ($sticky_group) ? 'Default Manager Group' : 'Default Customer Management Group' );
		$group_name->setValidationRegEx( array('NON_EMPTY') );
		$group_name->setMandatory();
		$upload_form->addInputField( $group_name );

		$confirm = new CheckBoxFieldInput( $upload_form , 'confirm' , 'Confirm' );
		$upload_form->addInputField( $confirm );

	}
		
	private function getFailureUploadAudienceFileHandle($str)
	{
		$file = "";
		$file = $file .$this->upload_audience_path ."failure/". $str . ".csv";
		$file_handle = fopen($file,"a");
		$header_values = "Mobile_Email \t   Name \n";
		fwrite($file_handle, $header_values);

		return $file_handle;
	}

	private function makeUploadAudienceFilePath($group_name,$group_id)
	{	
		$this->logger->debug(" Inside make upload ");
		$file_group = Util::uglify($group_name);
		$file_group_id = Util::uglify($group_id);
		$file_str = $file_group_id."_".$file_group;
		return $file_str;
						
	}

	private function getSuccessUploadAudienceFileHandle($str)
	{
		$file = "";
		$file = $file .$this->upload_audience_path ."success/". $str . ".csv";
		$file_handle = fopen($file,"a");
		$header_values = "Mobile_Email ,\t   Name \n";
		fwrite($file_handle, $header_values);
		return $file_handle;
	}
	
	/**
	 * Refactored part
	 * 
	 */
	
	public function prepareViaCsv($params, $file, $campaign_id, $upload_type='campaign_users', $import_type = 'mobile')
	{
		try{
			$file_parts = pathinfo( $file['name'] );
			
			if($file_parts['extension'] != 'csv' && $file_parts['extension'] != 'CSV')
			{
				throw new Exception( 'Upload Only CSV File!' );
			}
			else
			{
				$filename = $file['tmp_name'];
				$file_id = $this->prepare($params, $filename, $campaign_id, $upload_type, $import_type);
	
				return $file_id;
			}
		}
		catch(Exception $e)
		{
			$this->logger->error("Caught exception in prepare via csv");
			throw new Exception($e->getMessage());
		}
		
	}
	
	/**
	 * @param unknown $params
	 * @param unknown $file
	 * @param unknown $campaign_id
	 * @param string $type
	 * @param string $import_type
	 * 
	 * @return array of preview data
	 */
	
	function prepare($params , $filename , $campaign_id , $type = 'campaign_users',$import_type = 'mobile' )
	{
//		try
	//	{
			$this->campaign_model_extension->isGroupNameExists( $params['group_name'], $campaign_id );
			//init
			$this->logger->debug("Import type = $import_type");
			$uploader = UploaderFactory::getUploaderClass($import_type);
			$uploader->initColumnMappings($params);
			$uploader->setUploadType($type);
			$group_name = trim($params['group_name']);
			$uploader->setGroupName($group_name);
			$uploader->setCampaignId($campaign_id);
			$uploader->setFileName($filename);
			$token = $params['token_field'];
			$uploader->setCampaignStatus($token);
			
			//purges data into campaign_files_history table
			$file_id = $uploader->purge();

			if($file_id)
			{
				$this->logger->debug("File Id = $file_id");
			}
			else
			{
				$this->logger->debug("Purge data failed. Throwing exception");
				throw new Exception("Error purging upload details");
			}
			//$campaignStatusMgr = new CampaignStatus($file_id);
			
			$uploader->prepare();
			$uploader->validate();
			
			$valid_count = $uploader->getValidRecordsCount();
			if($type == 'sticky_group' && $valid_count == 0)
			{
				throw new Exception("No valid records to import");
			}		
		//}
//		catch(Exception $e)
	//	{
		//	$this->logger->error("Caught exception in prepare = ".$e->getMessage());
			//throw new Exception("Failed preparing the import");
	//	}			
		//$preview_data = $uploader->preview(100);
		return $file_id;
	}
	
	/**
	 * 
	 * preview 
	 */
	
	public function preview($file_id,$limit = 10)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		return $uploader->preview($limit);
	}
	
	public function upload($file_id)
	{
		try{
			$uploader = $this->loadUsingFileDetails($file_id);
			$group_name = $uploader->getGroupName();
			$campaign_id = $uploader->getCampaignId();
			$type = $uploader->getUploadType();
			$customer_count = $uploader->getValidRecordsCount();
			$group_id = $this->campaign_model_extension->insertGroupDetails( $campaign_id , $group_name , $type ,$customer_count);
			//$group_id = 1;
			$uploader->upload($group_id);
			$provider_type = 'uploadSubscribers';
			
			$audience_group_id =
			$this->campaign_model_extension->
			insertAudienceGroups( $campaign_id , $provider_type , $group_id );
			$status = $this->CampaignGroup->updateGroupMetaInfo( $group_id );
			//$this->generateErrorReport($uploader, $group_id);
		}catch(Exception $e)
		{
			$this->logger->error("Caught exception while uploading. error = ".$e->getMessage());
			throw new Exception($e->getMessage());
			
		}
		return true;
	}
	
	
	private function generateErrorReport($uploader, $group_id)
	{
		$group_name = $uploader->getGroupName();
		$file_group = Util::uglify($group_name);
		$file_group_id = Util::uglify($group_id);
		$str = $file_group."_".$file_group_id;
		//$campaign_id = $uploader->getCampaignId();
		$error_handle = $this->getFailureUploadAudienceFileHandle($str);
		while(TRUE)
		{
			$error_records = $uploader->getErrorRecords();
			if(empty($error_records))
			{
				break;
			}
			foreach($error_records as $key=>$val)
			{
				$mobile_email = $val['input'];
				$name = $val['name'];
				//$this->logger->debug("ERROR is: ".print_r($error,true));
				$error_line = "$mobile_email,$name";
				$this->logger->debug("Error line : $error_line");
				fwrite( $error_handle , $error_line."\n" );
			}
			unset($error_records);
			unset($error_line);
		}
		fclose($error_handle);
	}
	/**
	 * 
	 * @param unknown $file_id
	 * @return mixed $status array
	 * $status = array(FILE_READ => array(), TEMPDB => array());
	 */
	public function getStatus($token)
	{
		$campaignStatus = new CampaignStatus($token);
		$status = $campaignStatus->get();
		return $status;
	}
	
	public function setStatus($token, $status_key, $status_value)
	{
		$campaignStatus = new CampaignStatus($token);
		$status = $campaignStatus->get();
		$campaignStatus->set($status_key, $status_value);
	}
	
	public function getFileIdFromToken($token)
	{
		$db = new Dbase('campaigns');
		$sql = "
				SELECT id FROM campaigns.upload_files_history
				WHERE token = '$token'
				";
		$file_id = $db->query_scalar($sql);
		return $file_id;
	}
	
	/**
	 * @return AudienceUploader
	 */
	
	private function loadUsingFileDetails($file_id)
	{
		$file_details = $this->getUploadDetails($file_id);
		$upload_type = $file_details['upload_type'];
		$import_type = $file_details['import_type'];
		$campaign_id = $file_details['campaign_id'];
		$group_id = $file_details['group_id'];
		$group_name = $file_details['group_name'];
		$token = $file_details['token'];
		$params = $file_details['params'];
		$temp_table_name = $file_details['temp_table_name'];
		$this->logger->debug("Import Type = $import_type");
		$uploader = UploaderFactory::getUploaderClass($import_type);
		
		//init
		$uploader->setCampaignId($campaign_id);
		$uploader->setGroupId($group_id);
		$uploader->setGroupName($group_name);
		$uploader->setUploadType($upload_type);
		$uploader->setTempTableName($temp_table_name);
		$params = $uploader->getParams();
		$uploader->initColumnMappings($params);
		$uploader->setCampaignStatus($token);
		
		return $uploader;
	}
	
	private function getUploadDetails($file_id)
	{
		$db = new Dbase('campaigns');
		$sql = "
				SELECT * FROM campaigns.upload_files_history
				WHERE id = $file_id
				";
		$result = $db->query($sql);
		return $result[0];
	}
	
	public function getValidRecordsCount($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		return $uploader->getValidRecordsCount();
	}
	
	public function getErrorRecordsCount($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		return $uploader->getErrorRecordsCount();
	}
	
	public function getDetailsForDownload($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		$table = $uploader->getTempTableName();
		$sql = "
				SELECT * FROM $table
				WHERE status = 0
				";
		return array('temp_table'=>$table,'sql'=>$sql,'database'=>'Temp');
	}
	
	/**
	 * This method is used to upload audiences
	 */
	function uploadSubscribers( $params , $filename , $campaign_id , $type = 'campaign_users' ){}

	/**
	 * 
	 * @param unknown_type $batch_emails
	 * @param unknown_type $batch_mobiles
	 * @param unknown_type $batch_custom_tags
	 */
	private function processBatchesForUploadAudience( $batch_emails , 
		$batch_mobiles , $batch_custom_tags , $group_id , $with_custom_tag ){
			
			$this->logger->debug( " Process Batches for Upload Audience " );
			if( count($batch_emails) > 0 )
			{	
				$batch_email = $this->campaign_model_extension->checkUsersByEmail( $batch_emails );
			}
			if( count($batch_mobiles) > 0 )
			{
				$batch_mobile = $this->campaign_model_extension->checkUsersByMobileForBatch( $batch_mobiles );
			}
			
			$this->logger->debug(' Batch Email :'.print_r( $batch_email , true ) );
			$this->logger->debug('@Batch Mobile :'.print_r( $batch_mobile , true ) );
			
			$batch = array();
			if( !empty($batch_email) && !empty($batch_mobile) )
				$batch = $batch_email + $batch_mobile;
			else if ( !empty($batch_email) && empty($batch_mobile) )
				$batch = $batch_email;
			else if ( !empty($batch_mobile) && empty($batch_email) )
				$batch = $batch_mobile;
			
			if( !empty($batch) ){
				$this->logger->debug('@@Batch :'.print_r( $batch , true ) );	
				$final = array();
				foreach( $batch as $key => $value ){
					
					$is_email_exists = (int) $value['is_email_exists'];
					$is_mobile_exists = (int) $value['is_mobile_exists'];
					
					$customer_name = $value['firstname'] . ' ' . $value['lastname'];
					$this->logger->debug( " Customer name " . $customer_name );
					$customer_name =  
						$this->campaign_model_extension->
						database_conn->realEscapeString( $customer_name );
					
					$this->logger->debug( " Customer name " . $customer_name );
					if( $batch_custom_tags[$key] && $with_custom_tag ){
						
						$insert = 
							"( '$group_id' , '".$value['user_id']."' , 
								'$customer_name' , 'customer' , 
								'".$batch_custom_tags[$key]."', 
								$is_mobile_exists, $is_email_exists )";
					}else{
						
						$insert = 
							"( '$group_id' , '".$value['user_id']."' , 
									'$customer_name' , 'customer' , '', 
									$is_mobile_exists, $is_email_exists )";
					}
					array_push( $final , $insert );
				}
				
				return $final;	
			}else{
				return ;
			}
	} 
	
	/**
	 * Selection_filter table is populated by the filter params and is updated
	 * if we have the id of primary key by the customer count
	 * @param interger $id i.e;aud_group_id
	 * @param string $filter_type
	 * @param string $filter_params
	 * @param string $filter_explaination
	 * @param integer $customers ;by default = 0
	 */
	function setSelectionFilter( $id , $filter_type , $filter_params , $filter_explaination = '' , $customers = 0 , $custom_ids = 0 ){

		return $this->campaign_model_extension->setSelectionFilter( $id , $filter_type , $filter_params , $filter_explaination , $customers , $custom_ids );
	}

	/**
	 * Get audience data from campaing id
	 * @param unknown_type $campaign_id
	 */
	public function getGroupDetailsForCampaignId( $campaign_id, $favourite = false, $search_filter = false ){

		return $this->campaign_model_extension->getAudienceDataByCampaignID( $campaign_id, $favourite, $search_filter );
	}

	/**
	 * Get group label by group id
	 */
	public function getGroupLabel( $group_id ){
		return $this->campaign_model_extension->getGroupLabel( $group_id );
	}

	public function getGroupsDetailsByGroupID ( $group_id ){
		
		$params = $this->campaign_model_extension->getCampaignGroupsByGroupId( $group_id , 'query');
		$this->logger->debug( " Group Details Params : " . print_r( $params , true ) );
		return $params;
	}

	public function getVoucherSeriesDetailsByOrg( $vch_series_id ){

		return $this->campaign_model->getVoucherSeriesDetailsByOrg( $vch_series_id );
	}
	
	public function getVoucherSeriesCodeById( $voucher_series_ids ){
	
		return $this->campaign_model->getVoucherSeriesCodeById( $voucher_series_ids );
	}

	/**
	 * get the filter details from audience group id when it is called from template selection
	 *
	 */
	public function getFilterDataByAudienceId( $audience_group_id ){
		return $this->campaign_model_extension->getFilterDetailsByAudienceGroupId( $audience_group_id );
	}

	/**
	 * Returns the hash map for the campaigns
	 */
	public function getDetails(){

		return $this->campaign_model_extension->getHash();
	}

	/**
	 * Returns the campaign over view details
	 */
	public function getOverViewDetails( ){

		return $this->campaign_model_extension->getOverViewDetails( );
	}

	/**
	 * get All the campaigns
	 */
	public function getAll(){

		return $this->campaign_model_extension->getAll();
	}

	/**
	 * Checks if the campaign name exists
	 * @param $name
	 * @param $campaign_id
	 */
	private function isNameExists( $name, $campaign_id = false ){

		$count = $this->campaign_model_extension->isNameExists( $name, $campaign_id );

		if( $count > 0 ) throw new Exception( 'Campaigns Name Already Exists' );
	}

	/**
	 * Is date range valid
	 *
	 * @param $start_date
	 * @param $end_date
	 */
	private function isDateRangeValid( $start_date, $end_date ){

		if( $end_date < $start_date )
		throw new Exception( 'End Date Has To Be Greater Than Start Date' );
	}

	/**
	 *
	 * CONTRACT(
	 *
	 * 	'name' => $name,
	 *  'start_date' => $start_date,
	 *  'end_date' => $end_date
	 * )
	 *
	 * @param $campaign_type
	 */
	public function create( $params, $campaign_type , $return_id = false ){

		try{

			$this->isNameExists( $params['name'] );
			$this->isDateRangeValid( $params['start_date'], $params['end_date'] );

			$this->campaign_model_extension->setName( $params['name'] );
			$this->campaign_model_extension->setDescription( $params['desc'] );
			$this->campaign_model_extension->setType( $campaign_type );
			$this->campaign_model_extension->setStartDate( $params['start_date'] );
			$this->campaign_model_extension->setEndDate( $params['end_date'] );
			$this->campaign_model_extension->setActive( true );
			$this->campaign_model_extension->setOrgId( $this->org_id );
			$this->campaign_model_extension->setCreated( date( 'Y-m-d' ) );
			$this->campaign_model_extension->setCreatedBy( $this->user_id );
			$this->campaign_model_extension->setVoucherSeriesId( -1 );
			
			$campaign_id = $this->campaign_model_extension->insert();
		}catch( Exception $e ){

			return $e->getMessage();
		}

		if( $return_id )
			return $campaign_id;
			
		return 'SUCCESS';
	}

	/**
	 *
	 * CONTRACT(
	 *
	 * 	'name' => $name,
	 *  'start_date' => $start_date,
	 *  'end_date' => $end_date
	 * )
	 *
	 * @param $campaign_type
	 */
	public function update( $params, $campaign_id ){

		try{

			$this->isNameExists( $params['name'], $campaign_id );
			$this->isDateRangeValid( $params['start_date'], $params['end_date'] );

			$this->campaign_model_extension->load( $campaign_id );

			$this->campaign_model_extension->setName( $params['name'] );
			$this->campaign_model_extension->setDescription( $params['desc'] );
			$this->campaign_model_extension->setStartDate( $params['start_date'] );
			$this->campaign_model_extension->setEndDate( $params['end_date'] );

			$this->campaign_model_extension->update( $campaign_id );
		}catch( Exception $e ){

			return $e->getMessage();
		}

		return 'SUCCESS';
	}


	//////////////////////////////////////////// REMINDER RELATED LOGIC /////////////////////////////////////////////////////////

	/**
	 * Get the conditions for reminders from audience groups of type CAMPAIGN
	 * @param $audience_group_ids
	 * @param $state
	 * @deprecated
	 */
	public function getReminderByCondition( $campaign_id , $state = 'CAMPAIGN' ){

		if( !$result )
			throw new Exception(' Reminder Details is not available for this campaign');

		return $result;
	}

	/**
	 * get the conditions for audience group id and
	 */
	public function getMessageQueueDetails( $campaign_id , $group_id ){

		return $this->campaign_model_extension->getMessageQueueDetails( $campaign_id , $group_id );
	}

	/**
	 * Check if the reminder is exist by the audience group id and group id
	 */
	public function isReminderExists( $audience_group_id , $group_id ){

		return $this->campaign_model_extension->isReminderExists( $audience_group_id , $group_id );
	}

	/**
	 * Getting all reminders based on audience group ids and group ids.
	 * @param unknown $audience_group_ids
	 * @param unknown $group_ids
	 * @return Ambigous <multitype:, boolean>
	 */
	public function getAllReminderByIds( $audience_group_ids, $group_ids ){
		
		if( is_array( $audience_group_ids ))
			$audience_group_ids = implode(',',$audience_group_ids );
		
		if( is_array( $group_ids ))
			$group_ids = implode(',', $group_ids );
		
		return $this->campaign_model_extension->getAllRemindersByIds( $audience_group_ids, $group_ids );
	}
	
	/**
	 * Returns the array once passsed the group details
	 *
	 * @param $campaign group users
	 * @param $camp_groups
	 */
	private function getGroupsAsOptions( &$group_details, &$camp_groups, $type = 'customer' ){

		if( !is_array( $group_details ) )
		return array();
			
		foreach( $group_details as $row ){

			if( $type == 'customer' )
				$key = $row['group_label'];
			else
				$key = $row['group_label'] ." ( sticky ) ";

			$camp_groups[$key] = $row['group_id'];
		}

		return $camp_groups;
	}

	/**
	 * Returns the campaign id with customer count
	 *
	 * @param $campaign_id
	 */
	function getGroupsAsOptionByCampaignId( $campaign_id, $inlude_stciky_groups = true ){

		$camp_groups_details = $this->getCampaignGroupsByCampaignIds( $campaign_id );

		$camp_groups = array();
		$camp_groups = $this->getGroupsAsOptions( $camp_groups_details, $camp_groups );

		if( $inlude_stciky_groups ){

			$sticky_users = array();

			//The stciky group has convention of passing campaign id as -20
			$sticky_groups = $this->getCampaignGroupsByCampaignIds( -20 );

			$sticky_users = $this->getGroupsAsOptions( $sticky_groups, $sticky_users, 'sticky' );
			$sticky_users = $this->getGroupsAsOptions( $sticky_groups, $sticky_users, 'sticky_group' );

			if( is_array( $sticky_groups ) )
			$camp_groups = array_merge( $camp_groups , $sticky_users );
		}

		return $camp_groups;
	}

	public function getRevertOptionsForFilters( $audience_group_id , $campaign_id ){

		$result = $this->campaign_model_extension->getRevertOptionsForFilters( $audience_group_id , $campaign_id );


		$list_options = array();
		foreach($result as $row){
			$list_options[$row['time']] = $row['id'];
		}

		return $list_options;
	}

	public function getBulkCampaignDetailsByGroupsIds( $campaign_id , $gd ){
		return $this->campaignsModel->getBulkCampaignDetailsByGroupsIds( $campaign_id , $gd );
	}

	public function revertSelectionFilterSet( $change_id , $audience_group_id , $campaign_id ){

		$this->campaignsModel->revertSelectionFilterSet( $change_id , $audience_group_id , $campaign_id );
	}

	/**
	 *
	 * @param unknown_type $vch_series_id
	 */
	public function getCampaignNameByVchId( $vch_series_id ){

		$campaign_list = $this->campaign_model_extension->getCampaignNameByVchId( $vch_series_id, $this->org_id );

		foreach ( $campaign_list as $campagin ){

			switch (strtolower($campagin['type'])) {
				
				case 'outbound':
					if ( $vch_series_id == $campagin['voucher_series_id'])
						$campaign_name = $campagin['name'];
					break;
				
				case 'referral':
					
					$vch_series_ids = json_decode( $campagin['voucher_series_id'], true );
					if( $vch_series_ids->referee == $vch_series_id || $vch_series_ids->referer == $vch_series_id ){
							
					$campaign_name = $campagin['name'];
					}						
					break;
				
				case 'action':
					$vch_series_ids = json_decode( $campagin['voucher_series_id'], true );
					if($value = in_array( $vch_series_id, $vch_series_ids))
						$campaign_name = $campagin['name'];
					
					break;
				
			}
		}
		
		return $campaign_name;
	
	}
	
	/**
	 *
	 * @param unknown_type $vch_series_id
	 */
	public function getCampaignIdByVchId( $vch_series_id ){

		$campaign_list = $this->campaign_model_extension->getCampaignNameByVchId( $vch_series_id, $this->org_id );

		foreach ( $campaign_list as $campaign ){

			switch (strtolower($campaign['type'])) {
				
				case 'outbound':
					if ( $vch_series_id == $campaign['voucher_series_id'])
						$campaign_id = $campaign['id'];
					break;
				
				case 'referral':
					
					$vch_series_ids = json_decode( $campaign['voucher_series_id'], true );
					if( $vch_series_ids->referee == $vch_series_id || $vch_series_ids->referer == $vch_series_id ){
							
						$campaign_id = $campaign['id'];
					}						
					break;
				
				case 'action':
					$vch_series_ids = json_decode( $campaign['voucher_series_id'], true );
					if($value = in_array( $vch_series_id, $vch_series_ids))
						$campaign_id = $campaign['id'];
					
					break;
				
			}
		}
		
		return $campaign_id;
	
	}

	/**
	 * return all the campaign for a particular organization
	 *
	 */
	public function getCampaignAsOptions( $type = false ){

		$campaigns = $this->campaign_model_extension->getAll();
		$camp_array = array();
		
		if( $type ){
			
			foreach( $campaigns as $camp  ){
				
				if( strtolower( $type ) == strtolower( $camp['type'] ) ){
					$name = $camp['name'];
					$camp_array[$name] = $camp['id'];
				}
			}
		}else{
			
			foreach( $campaigns as $camp  ){
				$name = $camp['name'];
				$camp_array[$name] = $camp['id'];
			}
		}
		
		return $camp_array;
	}

	//////////////////////////////////////////////////////// START MISCELLENEOUS LOGIC///////////////////////////////////////////////////////

	/**
	 * @param int $voucher_series_id
	 */
	public function offlineCouponProcessing( $voucher_series_id ) {

		$results = $this->campaign_model_extension->getCouponRedemptionDetailsForOffline( $voucher_series_id );

		// reset the sales amount, in case this is a repeat processing.
		$previous_id = -1;
		$sales_nextbill = 0;
		$sales_sameday = 0;

		foreach ($results as $row) {
			$id = $row['id'];
			if ($previous_id == -1) $previous_id = $id;
			if ($id != $previous_id) {
				$res = $this->campaign_model_extension->updateCouponRedemptionSales($sales_nextbill, $sales_sameday, $previous_id);
				$sales_nextbill = 0;
				$sales_sameday = 0;
			}
			$sales_sameday += $row['bill_amount'];
			if ($sales_nextbill == 0 && $row['bill_date'] > $row['used_date']) $sales_nextbill = $row['bill_amount'];
		}

		#do the last row
		if ($id) {
			$res = $this->campaign_model_extension->updateCouponRedemptionSales($sales_nextbill, $sales_sameday, $id);
		}

		$table = $this->campaign_model_extension->getCouponRedemptionDetailsForOffline( $voucher_series_id , 'query' );

		return $table;
	}

	/**
	 * //sms_template === message
	 * //email_subject === message
	 * //email_body === subject
	 *
	 * @param $litener_id INT
	 * @param $params CONTRACT(
	 *
	 * 		'event_name' => STRING,
	 * 		'litener_name' => STRING,
	 * 		'zone' => INT,
	 * 		'stores' => CSV,
	 * 		'message' => VARCHAR,
	 * 		'subject' => VARCHAR,
	 * 		'type' => ENUM( SMS, EMAIL, VOUCHER_ISSUAL ),
	 * 		'start_date' => DATE,
	 * 		'end_date' => DATE,
	 * 		'voucher_series_id' => INT,
	 * 		'template_file_id' => INT,
	 * 		'tracker_id' => INT
	 * )
	 */
	public function createListenerForReferrals( $params, $listener_id = false, $condition_id = false, $C_tracker_mgr = false ){

		$listener_params = array();
		$lm = new ListenersMgr( $this->currentorg );

		//init listener
		if( !$listener_id )
		$regn_id = $lm->registerListener( $params['event_name'], $params['listener_name'],
		$this->user_id, $params['voucher_series_id']
		);
		else
		$regn_id = $listener_id;

		if( !$regn_id ) throw new Exception( 'Listeners could not be created ');

		//construct params for the listeners
		if( $params['tracker_id'] )
		$listener_params['tracker_id'] = $params['tracker_id'];
			
		$listener_params['_regn_id'] = $regn_id;
		$listener_params['_event_name'] = $params['event_name'];
		$listener_params['_listener_name'] = $params['listener_name'];
		$listener_params['_execution_condition'] = null;
		$listener_params['execution_order'] = 0;
		$listener_params['zone'] = $params['zone'];
		$listener_params['stores'] = explode( ',' , $params['stores'] );
		$listener_params['listener_start_time'] = $params['start_date'];
		$listener_params['listener_end_time'] = $params['end_date'];

		//The fields in the SmsSending & EmailSending & IssueVoucher Listeners
		//Yeah Yeah I know its pretty bad way but dont want to write a
		//whole new logic when it is goin to deprecate in any cases
		if( $params['type'] == 'SMS' ){

			$listener_params['sms_template'] = $params['message'];
		}elseif( $params['type'] == 'EMAIL'){

			$listener_params['email_body'] = $params['message'];
			$listener_params['email_subject'] = $params['subject'];
			$listener_params['template_file_id'] = $params['template_file_id'];
		}elseif( $params['type'] == 'VOUCHER_ISSUAL' ){

			$listener_params['sms_template'] = $params['message'];
			$listener_params['voucher_series_id'] = $params['voucher_series_id'];
				
		}else{

			throw new Exception( 'Listener type not supported' );
		}

		//create listener
		$status = $lm->processCustomizeForm( false, $listener_params );
		if( !$status ) throw new Exception( 'Listener could not be updated' );

		if( $C_tracker_mgr && $condition_id )
		$C_tracker_mgr->setListenerForCondition( $condition_id, $regn_id );

		return 'SUCCESS';
	}

	/**
	 * Replace php tags which is replaced and sent to the 
	 * msging in the desired format 
	 */
	public function replacePhpTags( $subject ){
		
		global $campaign_cfg;
				
		$view_url = "{{domain}}/business_controller/campaigns/emails/links/view.php?utrack={{user_id_b64}}&mtrack={{outbox_id_b64}}";
				
		$view_url = Util::templateReplace( $view_url , array('domain'=>$campaign_cfg['track-url-prefix']['view-in-browser'] ) );
		
		$link = '<a href="'.$view_url.'" style = "text-decoration: underline;color: #369;" target="_blank">View it in your browser</a>';
		
		$tags = array(
						'adv' => '<ADV>',
				        'view_in_browser' => $link 
		);
		
		return Util::templateReplace( $subject, $tags );
	}	

	/**
	 * To view the queued msg or email we need
	 * to process some selected fields.
	 *
	 * Otherwise original is retained.
	 *
	 * @param unknown_type $params
	 * CONTRACT(
	 *
	 * 	'send_when' : Type of selections ENUM [ IMMEDIATE | PARTICULAR_DATE | SCHEDULE ]
	 * 	'hours' : hour selection
	 * 	'minutes' : minute selection
	 * 	'cron_day' : cron days
	 * 	'cron_week' : cron weeks
	 * 	'cron_month' : cron months
	 * )
	 */
	public function getProcessedParams( $params ){

		if( $params['send_when'] == 'IMMEDIATE' ) {

			$params['date_field'] = date( 'Y-m-d ' );
			$params['hours'] = date( 'H' );
			$params['minutes'] = date( 'i' );
		}

		$params['cron_day'] = implode( ',', $params['cron_day'] );
		$params['cron_week'] = implode( ',', $params['cron_week'] );
		$params['cron_month'] = implode( ',', $params['cron_month'] );

		return $params;
	}

	//////////////////////////////////////////////////////////// Charting Related Logic ///////////////////////////////////////////////////////////

	/**
	 * @param $tracker_params CONTRACT(
	 *
	 * 	'start_date' => VALUE [ start date ],
	 * 	'end_date' => VALUE [ goes in tracker formulation for start ]
	 * 	'stores' => VALUE [ The stores for which the trackers will be executed ]
	 * 	'zone' => VALUE [ The zone for which the trackers will be executed ]
	 * 	'period_days' => VALUE [ Number of days to track for ]
	 * 	'threshold' => VALUE [ Minimum number of redemption required to trigger tracker ]
	 * 	'message' => [ The message to shoot out for SMS/EMAIL ]
	 * 	'subject' => [ The subject to shoot out for EMAIL ]
	 * 	'template_file_id' => [ File Id To Be Used For Email ]
	 * 	'type' => [ SMS | EMAIL ]
	 * )
	 *
	 * @param $tracker_id
	 */
	public function addReferralTracker( $tracker_params, $tracker_id = false ){

		$issue_voucher_listener_for_tracker = false;
		$this->logger->info( 'The tracker params : '.
		print_r( $tracker_params, true ).' tracker id passed : '.$tracker_id
		);

		//get the campaign details
		$campaign_details = $this->getDetails();

		//get vouchet series id
		$voucher_series = json_decode( $campaign_details['voucher_series_id'], true );
		$referer_series_id = ( int ) $voucher_series['referer'];
		$referee_series_id = ( int ) $voucher_series['referee'];

		if( !$referer_series_id )
		throw new Exception( 'No Referer Series Is Attached ');
			
		//Step 1 : Create the tracker manager with referral event
		$C_tracker_mgr = new TrackersMgr( $this->currentorg );

		//Event : CampaignReferralRedemptionsTracker
		$params['entity'] = 'num_redemptions';
		$params['max_success_signal'] = '1000';
		$params['expires_on'] = $tracker_params['end_date'];
		$params['tracker_name'] = 'CampaignReferralRedemptionsTracker';
		$params['custom_name'] = $campaign_details['name'].' -- Referral Event';

		//The tracker on which tracker will be triggered
		$cust_params['exec_for_voucher_series_id'] = $referer_series_id;

		$this->logger->info( 'Adding/Updating Tracker With Params: '.print_r( $params, true ) );

		//create the tracker.
		if( $tracker_id ){

			$C_tracker_mgr->loadById( $tracker_id );

			$status =
			$C_tracker_mgr->updateTracker($params['max_success_signal'],
			$cust_params , $params['custom_name'], $params['expires_on']
			);
		}else{

			$tracker_id =
			$C_tracker_mgr->addTracker( $params['entity'], $params['tracker_name'],
			$params['max_success_signal'], $cust_params, $params['custom_name'],
			$params['expires_on'], $params['send_milestone'],
			$params['milestone_not_found_template']
			);

			$C_tracker_mgr->loadById( $tracker_id );
		}

		if( !$tracker_id ) throw new Exception( 'The Tracker data could not be uploaded!!!' );
		$this->logger->info( 'Tracker Updated Successfully ' );

		//tracker_id is event_reference_id for the
		// TrackerExecutingListener For With Event CampaignRefereeRedeemEvent
		$C_listener_mgr = new ListenersMgr( $this->currentorg );

		$listeners = $C_listener_mgr->getRegisteredListeners( $tracker_id, CampaignRefereeRedeemEvent, TrackerExecutingListener );
		$this->logger->info( 'Listener attached with the tracker : '.print_r( $listeners, true ) );

		//if already a listener is attached to the tracker
		//the listener id is loaded so that listener can be edited
		if( count( $listeners ) > 0 ){

			$listener_id = $listeners[0]['id'];
			$this->logger->info( 'Listener Id : '.$listener_id );
		}

		$listener_params['event_name'] = CampaignRefereeRedeemEvent;
		$listener_params['listener_name'] = TrackerExecutingListener;
		$listener_params['stores'] = $tracker_params['stores'];
		$listener_params['zone'] = $tracker_params['zone'];
		$listener_params['tracker_id'] = $tracker_id;
		$listener_params['start_date'] = $tracker_params['start_date'];
		$listener_params['end_date'] = $tracker_params['end_date'];
		$listener_params['voucher_series_id'] = $tracker_id;
		$listener_params['type'] = $tracker_params['type'];

		//add or edit the TrackerExecutingListener for the event
		//CampaignRefereeRedeemEvent
		$this->logger->info( 'Creating Listener With Params : '.print_r( $listener_params, true ) );
		$this->createListenerForReferrals( $listener_params, $listener_id );

		//agg_func : SUM
		//operator : '>='
		//threshold : threshold
		//period_days : period_days
		$condition_params['rank'] = 0;
		$condition_params['agg_func'] = 'SUM';
		$condition_params['operator'] = '>=';
		$condition_params['min_threshold_check'] = 0;
		$condition_params['success_signal_limit'] = 1;
		$condition_params['threshold'] = $tracker_params['threshold'];
		$condition_params['period_days'] = $tracker_params['period_days'];

		//check if a condition has already been applied
		//at max two condition might be there check the EmailSendingListener/SmsSendingListener

		if( $tracker_params['type'] == 'SMS' && strpos( $tracker_params['message'], '{{voucher_code}}' ) ){

			$listener_name = 'IssueVoucherListener';

			if( !$referee_series_id ){

				throw new Exception( 'No Referee Series Attached To Issue Voucher For Referers' );
			}

			$issue_voucher_listener_for_tracker = true;
			$listener_params['type'] = 'VOUCHER_ISSUAL';
			$listener_params['voucher_series_id'] = $referee_series_id;

		}elseif( $tracker_params['type'] == 'SMS' ){

			$listener_name = 'SmsSendingListener';

		}elseif( $tracker_params['type'] == 'EMAIL' ){

			$listener_name = 'EmailSendingListener';
		}

		$tracker_conditions = $C_tracker_mgr->getTrackerConditionByTrackerId();
		$condition_id = false;
		foreach( $tracker_conditions as $tc ){

			$listener_data =
			$C_listener_mgr->getRegisteredListeners( $tc['id'], 'TrackerSuccessEvent', $listener_name );

			if( count( $listener_data ) > 0 ){

				$condition_id = $tc['id'];
				break;
			}
		}

		$condition_id_insert =
		$C_tracker_mgr->processTrackerConditionForm( false, $condition_id, $condition_params );

		if( !$condition_id )
		$condition_id = $condition_id_insert;

		//get the condition attached to tracker
		$tracker_condition_details = $C_tracker_mgr->getConditions( $condition_id );

		//It gives back the listener id if something is attached to tracker conditions
		$listener_id = ( int ) $tracker_condition_details['listener_id'];
		$this->logger->info( 'Listener attached with the tracker condition id : '.$condition_id.' listener id : '.$listener_id );

		//If the voucher code is configured the issue voucher listener
		//will be added
		$listener_params['event_name'] = TrackerSuccessEvent;
		$listener_params['listener_name'] = $listener_name;
		$listener_params['stores'] = $tracker_params['stores'];
		$listener_params['zone'] = $tracker_params['zone'];
		$listener_params['start_date'] = $tracker_params['start_date'];
		$listener_params['end_date'] = $tracker_params['end_date'];
		$listener_params['voucher_series_id'] = $condition_id;
		$listener_params['type'] = ( $issue_voucher_listener_for_tracker )?( 'VOUCHER_ISSUAL' ):( $tracker_params['type'] );

		//other parameters
		$listener_params['message'] = $tracker_params['message'];
		$listener_params['subject'] = $tracker_params['subject'];
		$listener_params['template_file_id'] = $tracker_params['template_file_id'];

		//add the voucher series id in here.

		//add or edit the TrackerExecutingListener for the event
		//CampaignRefereeRedeemEvent
		$this->logger->info( 'Creating Final Listener With Params : '.print_r( $listener_params, true ) );
		$this->createListenerForReferrals( $listener_params, $listener_id, $condition_id, $C_tracker_mgr );

		//update the tracker condition with the listener id
		$this->logger->info( '...DONE...' );
	}

	/**
	 *
	 * @param $group_id
	 * @param $devide_grp_array
	 * @param $total_customer
	 */
	public function testControlRandomGroupCreation( $campaign_id , $group_id , 
			$devide_grp_array , $total, $mapping_required = true ){

		$start_id = -1;
		$batch_size = 5000;
		$label = $this->campaign_model->getGroupLabel( $group_id );
		$C_parent_group_handler = new CampaignGroupBucketHandler($group_id);
		$count_array = array();

		$group_handler = array( );
		$db = new Dbase('msging');
		while( $total > 0 ){

			$details = 
				$C_parent_group_handler->getCustomerListByLimit($start_id, $batch_size);
			$max = count( $details );
			if( $max < 1 ) break;

			$start_id = $details[$max-1]['id'];
			shuffle( $details );

			$col = 0;
			$grp_row = 1;
			$success = true;
			foreach( $devide_grp_array as $grp_dump ){

				$batch_group_id = $grp_dump['group_id'];
				if( !isset( $group_handler[$batch_group_id] ) ){
					
					$group_handler[$batch_group_id] = 
						new CampaignGroupBucketHandler($batch_group_id);
				}
								
				$batch_data = array();
				$rowcount = 1;
				$devide_cust = $grp_dump['percentage'];

				$count = round( ($max * $devide_cust) / 100 );

				for( $row = 0 ; $row < $count ; $row++,$col++,$rowcount++ ){
				
					if( $details[$col]['user_id'] ){
				
						$insert_subscribers =
						'(
								"'.$grp_dump['group_id'].'",
								"'.$details[$col]['user_id'].'",
								"'.$db->realEscapeString( $details[$col]['customer_name'] ).'",
								"'.$details[$col]['user_type'].'",
								"'.$db->realEscapeString( $details[$col]['custom_tags'] ).'",
								"'.$details[$col]['is_mobile_exists'].'",
								"'.$details[$col]['is_email_exists'].'"
							 )';
				
						array_push($batch_data, $insert_subscribers);
					}
				
					if( count( $batch_data ) > 0 && $rowcount == $count || $rowcount % 5000 == 0 ){
				
						$insert_users = implode(',', $batch_data);
						$batch_data = array();
				
						$success =
							$this->campaign_model_extension->
							addSubscriberInGroupInBatches( $insert_users, false,
									false, $group_handler[$batch_group_id] );
						
						$count_array[$grp_row] += $rowcount;
					}
				}
				
				if( !$success )
					throw new Exception("Group Id : " .
							$grp_dump['group_id'] ." is not successfully dumped. Please try again.");

				$grp_row++;
			}
			$total -= $max;
		}

		foreach( $devide_grp_array as $grp_dump ){

			$this->campaign_model_extension->updateGroupMetaInfo( $grp_dump['group_id'] );
		}
		
		if( !$mapping_required ) return;
		
		$grp_row = 1;
		foreach( $devide_grp_array as $grp_dump ){

			$provider_type = 'test_control';

			$audience_group_id = 
				$this->campaign_model_extension->insertAudienceGroups( $campaign_id , 
						$provider_type , $grp_dump['group_id'] );

			//insert in selection filter for customer admin details
			$group_desc = addslashes( "Split From {{css_start}}$label{{css_end}} 
							with $grp_dump[percentage]% probability.");
			
			$this->setSelectionFilter( $audience_group_id , 
					'test_control' , ' ' , $group_desc, $count_array[$grp_row] );
			$grp_row++;
		}
	}

	
	public function getRunningCampaigns()
	{
		$sql = "SELECT name, id FROM campaigns_base WHERE org_id = $this->org_id AND start_date < NOW() AND end_date > NOW() AND active = 1";
		$db = new Dbase('campaigns');
		$l = $db->query($sql);
		$list = array();
		foreach($l as $row)
		{
			$list[$row['name']]  = $row['id'];
		}
		
		return $list;
	}
	
	/**
	 * To Get Archived Group List for particular campaign.
	 * @param unknown_type $campaign_id
	 */
	public function getArchiveGroupDetailsForCampaignId( $campaign_id = false ){
		
		return $this->campaign_model->getArchiveGroupDetailsForCampaignId( $campaign_id );
	}
	
	
	/**
	* Calls uploadSubscriber for a file that is uploaded via ftp 
	*/
	public function ftpUpload( $params )
	{
		$this->logger->debug( ' Inside ftpUpload ' . print_r( $params , true ) );
		$org_id = $params[ 'org_id' ];
		$campaign_id = $params[ 'campaign_id' ];
		$user_id = $params[ 'last_updated_by' ];
		$download_id = $params[ 'id' ];
		$status = $params[ 'status' ];
		$group_name = $params[ 'group_name' ];
		$file_name = $params[ 'file' ];
		$import_type = $params['import_type'];
		$this->logger->debug( ' Ftp Audience upload status : ' . $status );
		
		if( $status == 'COPIED' )
		{
		
			$append_org_id = $org_id;
			
			if( $org_id == 0)
			{
				$append_org_id = 'zero'	;				
			}
			$path = Util::templateReplace($this->ftp_copy_path, array( 'org-id' => $append_org_id ,
																	   'campaign-id' => $campaign_id , 
																	   'group-name' => $group_name ) ) ;
			$path = $path . "/"	. $file_name ;
				
			$this->logger->debug( " File Path from inside ftp upload : " . $path );
			if($path)
			{
				try{
					$custom_tags = $params[ 'custom_tags' ];

					$custom_tags = json_decode( $custom_tags , true );
					//$number_of_tags = count( $custom_tags );
					$number_of_tags = $params[ 'custom_tag_count' ];
					//$params[ 'custom_tag_count' ] = $number_of_tags ;



					if( $number_of_tags != 0 )
					{
						for( $i = 1 ; $i <= $number_of_tags ; $i++ )
						{
							$key = 'custom_tag_'.$i;
							$params[ $key ] = ( $custom_tags[ $key ] );
						}
					}
					//$status = $this->uploadSubscribers( $params , $path , $campaign_id , 'campaign_users' );
					$file_id = $this->prepare($params, $path, $campaign_id,'campaign_users',$import_type);
					$this->upload($file_id);
					$this->logger->debug("Ftp Upload subscriber status : " . $status );

					return $status;
						

				}
				catch(Exception $e){
					$this->logger->debug( "Ftp Upload Exception : " . $e->getMessage() ) ;
					throw $e ;
				}
			}
			
		}	 				
	}
	
	public function ftpDbInsert( $params , $campaign_id , $org_id , $user_id )
	{ 
		$this->logger->debug( ' Ftp Insert ' );
		$group_name = $params[ 'group_name' ] ;
		$group_name = Util::uglify( $group_name );
		$params[ 'group_name' ] = $group_name ;
		$this->logger->debug( ' FTP values : ' . print_r( $params , true ) );
		$number_of_tags = $params[ 'custom_tag_count' ];
    		
    	$custom_tag = array();
    	$custom_tags = 0;
    	if( $number_of_tags != 0 )
    	{
    		for( $i = 1 ; $i <= $number_of_tags ; $i++ )
    		{
				$key = 'custom_tag_'.$i;
				$custom_tag[ $key ] = ( $params[ $key ] );
			}
			
			$custom_tags = json_encode( $custom_tag );
    	}
    	
    	$params[ 'custom_tags' ] = $custom_tags;
		$status = $this->campaign_model->insertFtpDb( $params , $campaign_id , $org_id , $user_id );
		return $status;
	}
	
	
	
	public function ftpConnect( $org_id , $passive = false )
	{
		$settings = $this->campaign_model_extension->getFtpSettings( $org_id );
		$this->logger->debug( " Ftp Settings : " . print_r( $settings , true ) );
		$server_name = $settings[0][ 'server_name' ];
		$port = $settings[0][ 'port' ];
		$user_name = $settings[0][ 'user_name' ];
		$password = $settings[0][ 'password' ];
		$password = base64_decode( $password );
		$this->logger->debug( " Ftp Settings again : " . $server_name . " : " . $port . " : " . $user_name . " : " .  $password );
		try
		{
			$this->FtpManager = new FTPManager( $server_name , $port , $user_name , $password , $passive );
		}catch( Exception $e )
		{
			$this->logger->debug( " Ftp connect could not connect " . $e->getMessage() );
			throw $e;
		}
		
	}
	
	/**
	 * Copies file from ftp server to local server
	 * @param $params
	 */
	public function getFtpFile( $params )
	{
		$org_id = $params[ 'org_id' ];
		$campaign_id = $params[ 'campaign_id' ]; 
		$user_id = $params[ 'last_updated_by' ];
		$folder = $params[ 'folder' ];
		$file_name = $params[ 'file' ];
		$group_name = $params[ 'group_name' ];
		$custom_tags = $params[ 'custom_tags' ];
		
		$this->ftpConnect( $org_id , true ); //Change passive mode here
				
		$this->logger->debug( " Ftp Params Values from Get Ftp File : " . print_r( $params , true ) );
		$this->logger->debug( " Custom Tags : " . print_r( $custom_tags , true ) );
		
		/* Change HERE if you want to change path to get the file */
				
		/*$file_path = "org_" . $org_id 
					. "/campaign_" . $campaign_id . "/" . $folder ; */
		$file_path = $folder ;
		
		$this->logger->debug( " Current Directory to change to : " . $file_path );
		
		/* change current directory before copying */
		$cur_dir = $this->FtpManager->setCurrentDir( $file_path ) ;
		
		$this->logger->debug( " Changed Current Directory to :  " . $cur_dir );
		
		
		/* path to put file from ftp */
		$append_org_id = $org_id ;
		
		if( $org_id == 0 )
		{
			$append_org_id = "zero";
		}

		$copy_path = Util::templateReplace($this->ftp_copy_path, array( 'org-id' => $append_org_id ,
																	   'campaign-id' => $campaign_id , 
																	   'group-name' => $group_name ) ) ;
		
		 /* Set transfer mode to ASCII */
		
		$this->FtpManager->setMode();
		
		$this->logger->debug( " File Path : " . $copy_path . " : " . $file_name );
		
		$status = $this->FtpManager->get( $file_name , $copy_path );
		
		return $status;
	}
	
	/**
	 * update bulk sms credit for organization
	 */
	public function updateBulkSMSCredit( $credit ){

		try{
	
			$this->logger->debug('@@@INSIDE BULK UPDATE' );
			//Loading organization sms bulk credit. 
			$this->org_sms_credit_model->load( $this->org_id );
			
			$bulk_credit = $this->org_sms_credit_model->getBulkSmsCredits();
			$this->logger->debug('@@@BHAVESH');
			
			if( !$bulk_credit ){
				
				$this->logger->debug('@@@GOOGLE'.$this->org_id.$this->user_id);
				$this->org_sms_credit_model->setOrgId( $this->org_id );
				$this->org_sms_credit_model->setValueSmsCredits( 0 );
				$this->org_sms_credit_model->setBulkSmsCredits( $credit );
				$this->org_sms_credit_model->setUserCredits( 0 );
				$this->org_sms_credit_model->setCreatedBy( $this->user_id );
				$this->org_sms_credit_model->setLastUpdatedBy( $this->user_id );
				$this->org_sms_credit_model->setLastUpdated( date('Y-m-d H:i:s') );
				return  $this->org_sms_credit_model->insertWithId();
				
			}else{
	
				$this->logger->debug('@@@DATA LOADED' );

        	                //Setting new Bulk credit.
        	                $new_credit = $bulk_credit + $credit;
                	        $this->org_sms_credit_model->setBulkSmsCredits( $new_credit );
        	        
                	        //Updating new Credit.
	                        return $this->org_sms_credit_model->update( $this->org_id );  
			}					

		}catch( Exception $e ){
			return $e->getMessage();
		}
	}
	
	/**
	 * getting bulk sms credit for the campaign home display.
	 */
	public function getBulkSmsCredit(){
		$this->org_sms_credit_model->load( $this->org_id );
				
		return $this->org_sms_credit_model->getBulkSmsCredits();
	}
	
	/**
	 * Getting campaign data.
	 * @param unknown_type $limit
	 * @param unknown_type $where_filter
	 */
	public function getCampaignData( $limit, $where_filter ){
		
		return $this->campaign_model_extension->getCurrentCampaign( $limit, $where_filter );
	}
	
	/**
	 * Getting data with where condition for campaign data table.
	 * @param unknown_type $where
	 */
	public function getCampaignDataWithWhere( $where ){
	
		return $this->campaign_model_extension->getCurrentCampaignWithWhere( $where );
	}
	
	
	/**
	 * Returns the message details given group id
	 */
	public function getMsgDetailsByGroupId( $group_id ){

		return $this->campaign_model->getMsgDetailsByGroupId( $group_id );
	}
	
	/**
	 * Propogates the exception
	 * @param unknown_type $group_label
	 */
	public function isGroupNameExists( $group_label, $campaign_id ){
		
		$this->campaign_model_extension->isGroupNameExists( $group_label, $campaign_id );
	}
	
	public function getFtpFileStatus( $campaign_id )
	{
		$status_values = $this->campaign_model_extension->getFtpFileStatus( $campaign_id );
		
		$this->logger->debug( " Ftp File Status : " . print_r( $status_values , true ) );
		
		return $status_values;
	}
	
	public function selectFromFtp( $status )
	{
		$params = $this->campaign_model_extension->selectFromFtp( $status);
		
		return $params;
	}
	/*
	 *  status[OPEN , COPYING , COPIED , PROCESSING , EXECUTED , ERROR ]
	 */
	public function setFtpStatus( $status , $id )
	{
		$status = $this->campaign_model_extension->setFtpStatus( $status , $id );
		
		return $status ;
	}
	
	public function ftpFileExists( $file_name , $folder_name , $org_id )
	{
		try
		{ 
			$this->ftpConnect( $org_id , true ); // <---- THIS IS THE ONLY CHANGE
		}
		catch( Exception $e )
		{
			$this->logger->debug( " Could not connect in fto file exists" . $e->getMessage() );
			throw $e;
		}
				
		$status = $this->FtpManager->ftpFileExists( $file_name , $folder_name );
		
		$this->logger->debug( " Ftp Exist status : " . $status );
		return $status ;
		
	}
	
	public function getGroupsByCampaignId( $campaign_id )
	{
		return $this->campaign_model_extension->getGroupsByCampaignId( $campaign_id );
	}
	
	public function getOverallEmailGroup( $campaign_id , $limit )
	{
		return $this->CampaignGroup->getOverallEmailGroup( $campaign_id , $limit );
	}
	
	public function getUserEmailCount( $campaign_id , $group_id , $limit )
	{
		return $this->CampaignGroup->getUserEmailCount( $campaign_id , $group_id , $limit );
	}
	
	public function getUserDateEmailCount( $campaign_id ,  $start_date , $end_date , $limit )
	{
		return $this->CampaignGroup->getUserDateEmailCount( $campaign_id ,$start_date , $end_date , $limit );
	}
	
	public function getOverallEmailLinkGroup( $campaign_id , $org_id , $limit )
	{
		return $this->CampaignGroup->getOverallEmailLinkGroup( $campaign_id , $org_id , $limit );
	}
	
	public function getUserEmailLinkCount( $campaign_id , $org_id , $limit )
	{
		return $this->CampaignGroup->getUserEmailLinkCount( $campaign_id , $org_id , $limit );
	}
	
	public function getEmailLinkUserStats( $campaign_id , $org_id , $limit )
	{
		return $this->CampaignGroup->getEmailLinkUserStats( $campaign_id , $org_id , $limit );
	}
	
	public function getEmailStatsForBarChart( $campaign_id , $org_id )
	{
		$limit = " LIMIT 10 ";
		return $this->CampaignGroup->getEmailStatsForBarChart( $campaign_id , $org_id , $limit );
		
	}
	
	public function getLinksForBarChart( $campaign_id , $org_id )
	{
		$limit = " LIMIT 10 ";
		return $this->CampaignGroup->getLinksForBarChart( $campaign_id , $org_id , $limit );
	}
	
	public function getOverallEmailForPieChart( $campaign_id , $org_id )
	{
		return $this->CampaignGroup->getOverallEmailForPieChart( $campaign_id , $org_id );
	}
	
	public function getSkippedUsersBarChart( $campaign_id , $org_id )
	{
		$limit = " LIMIT 10 ";
		return $this->CampaignGroup->getSkippedUsersBarChart( $campaign_id , $org_id , $limit );
	}
	
	/**
	 * Getting Voucher Series Details. for outbound and action.
	 * if it is outbound campaign it will return description and num_of_issued
	 * and num_of_redeemed if campaign is action based it will return only num_of_issued
	 * and num_of_redeemed.
	 * @param unknown_type $voucher_id
	 */
	public function getVoucherSeriesDetailsByVoucherId( $voucher_id ){
		
		$description = '';
		
		if( is_array( $voucher_id ) )
			$voucher_id = implode(',', $voucher_id);
		else
			$description = ', description ';
	
		return $this->campaign_model->getVoucherSeriesDetailsByVoucherId
											( 
											    $voucher_id , 
												$description 
											);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $group_id
	 */
	public function changeFavouriteTypeForGroup( $group_id ){
		
		$this->campaign_model_extension->changeFavouriteTypeForGroup( $group_id );
	}
	
	/**
	 * 
	 * @param unknown_type $campaign_id
	 */
	public function getOrgIdByCampaignId( $campaign_id ){
		
		$this->campaign_model_extension->load( $campaign_id );
		return $this->campaign_model_extension->getOrgId();
	}
	
	public function getVoucherSeriesDetailsByCampaignId( $campaign_id ){
		
		$this->campaign_model_extension->load( $campaign_id );
		$voucher_id = $this->campaign_model_extension->getVoucherSeriesId();
		$vch_id = json_decode( $voucher_id );

		return  $this->campaign_model_extension->getVoucherSeriesDetailsByVoucherId( implode(',', $vch_id ) );
	}
	
	public function getVoucherSeriesIdsByCampaignId($campaign_id)
	{
		$this->campaign_model_extension->load( $campaign_id );
		$voucher_id = $this->campaign_model_extension->getVoucherSeriesId();
		$vch_id = json_decode( $voucher_id );
		
		return  implode(',', $vch_id );
	}
	
	/**
	 * 
	 * Upload Audience Paste Audience List Upload Function.
	 * @param unknown_type $params
	 */
	public function pasteAudienceList( $params , $campaign_id , $type = 'campaign_users' , $return_group_id = false ){
		
		global $currentorg;
		
		$data = explode("\n" , $params['csv_content'] );
		$header = explode(',', $data[0] );
		
		$final_data = array();
		for( $i = 1; $i < count($data); $i++ ){
			array_push($final_data, explode(',' , $data[$i] ));
		}
		
		if( count( $final_data ) < 1 )
			throw new Exception( 'Data not inserted!');

		if( strtolower( $header[0] ) != 'email' && strtolower( $header[0] ) != 'mobile' )
			throw new Exception( 'Incorrect header names,it should be email or mobile,name!');
	
		try{
			$this->campaign_model_extension->isGroupNameExists( $params['group_name'], $campaign_id );
		}catch( Exception $e ){
			throw new Exception( $e->getMessage(), 111 );	
		}
		
		if( count( $data ) > 51 )
			throw new Exception( 'Maximum 50 customer allowed to upload.' );
								
		$col_mapping['name'] = 1 ; //stripslashes(trim($params['user_name']));
		$col_mapping['email_mobile'] = 0 ; //$params['email_mobile'];
			
		$number_of_tags = $params['custom_tag_count'];
		
		//upload custom tags to processed data
		for( $i = 1 ; $i <= $number_of_tags ; $i++ ){
			$custom_field_col = $i;
			$key = 'custom_tag_'.$i;
			$col_mapping[$key] = $custom_field_col+1;
		}
		
		$group_created_flag = 0;
		$batch_data_email = array();
		$batch_data_mobile = array();
		$group_name = $params['group_name'];
		$total_customer = count( $data );
		$C_campaign_group_handler = '';
		
		$country_array = $currentorg->getCountryDetailsDumpForMobileCheck();
		
		foreach ( $final_data as $row ){
						
			$email_mobile = trim( $row[0] );
			list($first_name, $last_name) = Util::parseName( $row[1] );
			
			$first_name = $this->campaign_model_extension->database_conn->realEscapeString( $first_name );
			$last_name = $this->campaign_model_extension->database_conn->realEscapeString( $last_name );
			
			$pwd = substr( $email_mobile , -4 );
			$passwordHash = md5( $pwd );
			$original_email_mobile=$email_mobile;
			$this->logger->debug('@@@'.$email_mobile);
				
			if ( Util::checkEmailAddress( $email_mobile ) ){
	
				$insert_users = "('$this->org_id','$email_mobile','$first_name','$last_name','$passwordHash')";
									
				array_push( $batch_data_email , $insert_users );
				$this->logger->debug( 'Inside Email Address' );
				$this->logger->debug('@@@@WITHIN CHECK EMAIL ADDRESS');
					
				if( !$group_created_flag ){
						
					$group_id = 
						$this->campaign_model_extension->
							insertGroupDetails( $campaign_id , $group_name , $type, $total_customer );
					
					if( !$group_id )
						throw new Exception( 'Group could not be created!' );
								
					$C_campaign_group_handler = new CampaignGroupBucketHandler($group_id);
					$group_created_flag = 1;
				}
			}
				
			if( Util::checkMobileNumberNew( $email_mobile , $country_array ) ){
								
				$insert_users = "('$this->org_id','$email_mobile','$first_name','$last_name','$passwordHash')";
				
				array_push( $batch_data_mobile , $insert_users );
				$this->logger->debug('@@@@WITHIN CHECK MOBILE ');
						
				if( !$group_created_flag )
				{
					$group_id = 
						$this->campaign_model_extension->
						insertGroupDetails( $campaign_id , $group_name , $type, $total_customer );
					
					if( !$group_id )
						throw new Exception( 'Group could not be created!' );
								
					$C_campaign_group_handler = new CampaignGroupBucketHandler($group_id);
					$group_created_flag = 1;
				}
			}else{
						
				unset( $row['email_mobile'] );
				$this->logger->debug('@@@@WITHIN ELSE ');
			}
			
			$auth = Auth::getInstance();
	
			if( count($batch_data_email) > 0 ){
				
				$auth->registerAutomaticallyByEmailInBatches( implode(',', $batch_data_email) );
				$this->logger->debug( " Registered 10k email people " );
			}
				
			if( count($batch_data_mobile) > 0 ){
	
				$auth->registerAutomaticallyByMobileInBatches( implode(',', $batch_data_mobile) );
				$this->logger->debug( " Registered 10k mobile people " );
			}
			$batch_data_email = array();
			$batch_data_mobile = array();
		}
				
		$rowcount = 0;
		$batch_data = array();
		$batch_emails = array();
	 	$batch_mobiles = array();
		$batch_custom_tags = array();
					
		foreach ($final_data as $row) {
				 
			$rowcount++;
			 $email_mobile = trim( $row[0] );
					
			 if ( Util::checkEmailAddress( $email_mobile ) )
			 	array_push( $batch_emails , $email_mobile );
			 else if( Util::checkMobileNumberNew( $email_mobile , $country_array ) )
			 	array_push( $batch_mobiles , $email_mobile );
			 else{
				if( $rowcount < $max )
				continue; //# skip invalid email or skip invalid mobile
			 }
			 
			 if( $number_of_tags >= 1 ){
			 	
				//Get out the custom tags
				$custom_tags = array();
				for( $i = 1 ; $i <= $number_of_tags ; $i++ ){


					$key = 'custom_tag_'.$i;
					$custom_tags[$key] = ( string ) stripcslashes( trim( $row[1+$i] ) );
				}
				
				$custom_tags_filter = json_encode( $custom_tags );
				$custom_tags_filter = 
					$this->campaign_model_extension->
					database_conn->realEscapeString( $custom_tags_filter );
				
				if( $email_mobile )
					$batch_custom_tags[$email_mobile] = $custom_tags_filter;
			}
		}
		
		$is_custom_tag = false;
		if( $number_of_tags >= 1 )
				$is_custom_tag = true;
					
		$this->logger->debug('@@Custom Tags :'.print_r( $batch_custom_tags , true ) );

			
		$batch_data = 
			$this->processBatchesForUploadAudience( $batch_emails , 
				$batch_mobiles , $batch_custom_tags , $group_id , $is_custom_tag );
				
		//now add this user to the campaign group users table
		$this->logger->debug( " Batch data returned from processBatches " . print_r( $batch_data , true ) );
		$batch_count = count( $batch_data );
		$insert_users = implode(',', $batch_data);
		
						
		$success = 
			$this->campaign_model_extension->
			addSubscriberInGroupInBatches( $insert_users, false, false, $C_campaign_group_handler );
							

		if( $batch_count )
			$user_count += $batch_count;
		
		$this->logger->debug( " User Count : " . $user_count );
		$batch_emails = array();
		$batch_mobiles = array();
		$batch_custom_tags = array();
		$batch_data = array();
		$row = false;
	
		if( $user_count > 0){
			
			$msg = " List created successfully with $user_count customers";
			$this->logger->debug( $msg ); 
			//store group_id as json
			$json = json_encode(array('group_id' => "$group_id"));

			$this->logger->debug( " Upload Subscribers Group Id : " . $group_id . " Json " . $json );
			$provider_type = 'uploadSubscribers';
				
			$audience_group_id = 
				$this->campaign_model_extension->
				insertAudienceGroups( $campaign_id , $provider_type , $group_id );

			$status = $this->CampaignGroup->updateGroupMetaInfo( $group_id );
			
		}else
			throw new Exception('Please check your list again. It seems records may be invalid.');
		
		if( $return_group_id ) 
			return $group_id; 
		else 
			return $msg;
	}	
	
	public function uploadAudienceViaCSV( $file , $params , $campaign_id ){

		$this->campaign_model_extension->isGroupNameExists( $params['group_name'], $campaign_id );
		
		$file_parts = pathinfo( $file['name'] );
		
		if($file_parts['extension'] != 'csv' && $file_parts['extension'] != 'CSV'){
				throw new Exception( 'Upload Only CSV File!' );
		}else{
			$filename = $file['tmp_name'];
			try{
					$status = $this->uploadSubscribers( 
														$params , 
														$filename , 
														$campaign_id, 
														'campaign_users' 
													  );
				return $status;
			}catch(Exception $e){
				throw new Exception($e->getMessage, 111);
				//$this->publishOn( 'iframe_refresh', array( 'refresh' => false, 'flash' => $this->getFlashMessage() ) );
			}
		}
	}
	
	public function uploadAudienceViaFtp( $params, $campaign_id ){
		
			$file_name = $params[ 'ftpfile' ];
   			$folder_name = $params[ 'ftpfolder' ];
   			$could_not_connect = false;
   			
   			/* START HERE  check ftp_nlist */
   			
   			try{
   				$file_exists = $this->ftpFileExists( $file_name , $folder_name , $this->org_id );
   			}catch( Exception $e ){
   				$this->logger->debug( " upload audience widget could not connect " . $e->getMessage() );
   				$file_exists = false;
   				$could_not_connect = true ;
   			}
	  		
   			if( $file_exists ){
   				
   				$group_name = $params[ 'group_name' ];
   				$status = false;
   				$this->logger->debug( " Group Name in Ftp : " . $group_name );
   				try{
   					$this->campaign_model_extension->isGroupNameExists( $group_name, $campaign_id ); //spaces are not replaced by underscores in group details
   					
   					$group_name = Util::uglify( $group_name ); //spaces are underscores in ftp audiences upload
   					
   					$this->campaign_model_extension->isFtpGroupNameExists( $group_name );
   					
   					$this->logger->debug( " Succesful check " );
   					$status = true ;
   				}
   				catch( Exception $e ){
   					$this->logger->debug( " Unsuccessful " . $e->getMessage() );
   					throw new Exception( $e->getMessage() , 111 );
   					$status = false ;
   				}
   				if( $status )
   					$status = $this->ftpDbInsert
   									 ( 
   										$params , 
   										$campaign_id , 
   										$this->org_id , 
   										$this->user_id
   									);
  				return $status;
   			}else{
   				if( !$could_not_connect )
   					throw new Exception( " Sorry your file does not exist in your folder ." , 111 );
   				else
   					throw new Exception( " Could not connect to ftp server " , 111 );
   			}
	}

	/**
	 * 
	 * Retrieving list of supported social platforms.
	 */
	public function getSupportedSocial(){
		
		return $this->campaign_model_extension->getSupportedSocialPlatform();
	}

	/**
	 *getting supported social platform info for org. 
	 */
	public function getSupportedSocialPlatform(){

		$this->org_model->load( $this->org_id );
		
		$social_data = $this->org_model->getHash();
		
		$this->logger->debug('@@@Organization detail hash'.print_r( $social , true ));
		
		$social = json_decode( $social_data['social_platforms'] ,true );
		
		$this->logger->debug('@@@Social platforms array'.print_r( $social , true ));
		
		return $social;
	}
	
	/**
	 * 
	 * Check if campaign is expired or not.
	 * @param unknown_type $campaign_id
	 * @throws InvalidInputException
	 */
	public function isCampaignExpired( $campaign_id ){
		
		$this->campaign_model_extension->load( $campaign_id );

		//check if campaign is already expired
		$end_date = $this->campaign_model_extension->getEndDate();
		$campaign_end_date = $this->campaign_model_extension->getEndDate( );
		$campagn_end_date_timestamp = strtotime( $campaign_end_date );
		$current_timestamp = time( );
		
		if( $current_timestamp > $campagn_end_date_timestamp )
			return "Your campaign expired on $end_date. 
					Please modify end date to re-schedule the campaign.";
		
		//check if campaign is active or not
		$campaign_start_date = $this->campaign_model_extension->getStartDate();
		$campagn_start_date_timestamp = strtotime( $campaign_start_date );
		$current_timestamp = time( );
		
		if( $current_timestamp < $campagn_start_date_timestamp )
			return "Your campaign has not started yet. 
					It will be active from ".$campaign_start_date;
								
		return false;
	}
	
	
   public function convertDatetoMillis($date){
		
		$timeInMillis = strtotime($date);
		if($timeInMillis == -1 || !$timeInMillis )
		{
			throw new Exception("Cannot convert '$date' to timestamp", -1, null);
		}
		$timeInMillis = $timeInMillis * 1000;
		return $timeInMillis;
	}
	
	/**
	 * 
	 * Sending Test Email And SMS to group.
	 * @param unknown_type $params
	 * @param unknown_type $campaign_id
	 * @throws Exception
	 */
	public function PrepareAndSendMessages( $params , $campaign_id , $msg_type ){
				
		$users_email = array();
		$params['send_when'] = 'IMMEDIATE';
		$params['camp_group'] = $params['list'];
		$send_campaign_id = $campaign_id;
		try{
			$params['message'] = rawurldecode( $params['message'] );
			$params['subject'] = rawurldecode( $params['subject'] );
			
			if( $params['choose_list'] ){
					$status = $this->sendTestMessages( $campaign_id, $params , $msg_type );
			}else{
				
				$params['custom_tag_count'] = 0;
				
				if( !$params['on_off'] ){
					$params['group_name'] = 'test_group_'.strtotime( Util::getCurrentDateTime() );
					$type = 'test_group';
					$campaign_id = -30;
				}else{
					$type = 'sticky_group';
					$campaign_id = -20;
					if( !$params['group_name'] )
						throw new Exception( 'Please provide appropiate list name!' );
				} 
				
				$group_id = $this->pasteAudienceList( $params, $campaign_id , $type, true );
				$this->logger->debug('@@@Group created with group id:'.$group_id );
				$params['camp_group'] = $group_id;
									
				$status = $this->sendTestMessages( $send_campaign_id, $params , $msg_type );
			}
		}catch ( Exception $e ){
			
			$this->logger->debug( '@@@ERROR:'.$e->getMessage() );
			throw new Exception( $e->getMessage() );
		}
		return $status;
	}

	
	/**
	 * 
	 * Sending Test Email And SMS.
	 * @param unknown_type $campaign_id
	 * @param unknown_type $params
	 * @param unknown_type $type
	 * @throws Exception
	 */
	public function sendTestMessages($campaign_id , $params , $blast_type = 'EMAIL'){
		
		 //validating data
        $C_bulk_message = new BulkMessage();
        $C_bulk_message->setCampaignId( $campaign_id );
        $C_bulk_message->setGroupId( $params['camp_group'] );
        $C_bulk_message->setScheduledType( $params['send_when'] );
        $C_bulk_message->setMessage( $params['message'] );
        $C_bulk_message->setParams( $params );
        $C_bulk_message->setType( $blast_type );
       
        $C_bulk_validator = BulkMessageValidatorFactory::getValidator( BulkMessageTypes::valueOf( $blast_type ) );
       
        $queue_id = $C_bulk_validator->validate( $C_bulk_message , ValidationStep::$QUEUE );
			
		$this->logger->debug('@@@QUEUE_ID:'.$queue_id );	        

        $json_params = json_encode( $params );

        $C_bulk_message->load( $queue_id );

        $C_bulk_message->setParams( $json_params );
	        
        include_once 'business_controller/campaigns/message/impl/BulkMessageSenderFactory.php';

        $C_bulk_sender = BulkMessageSenderFactory::getSender( BulkMessageTypes::valueOf( $blast_type ) );
	           
       return $C_bulk_sender->send( $C_bulk_message );
	}
	
	/**
	 * Validates the Sms is allowed to be queued
	 *
	 * or not
	 * @param $params
	 * CONTRACT(
	 * 	'camp_group' : group_id,
	 * 	'send_when' : Type of selections ENUM [ IMMEDIATE | PARTICULAR_DATE | SCHEDULE ]
	 * 	'hours' : hour selection
	 * 	'minutes' : minute selection
	 * 	'cron_day' : cron days
	 * 	'cron_week' : cron weeks
	 * 	'cron_month' : cron months
	 *  'org_credits' : used for sms
	 *  'message' : message
	 * )
	 */
	public function validateBulkQueue( $campaign_id, &$params, $queue_type = 'SMS' ){
	
		$this->logger->debug('@@In validate '.$campaign_id);
		//validating data
		$C_bulk_message = new BulkMessage();
		$C_bulk_message->setCampaignId( $campaign_id );
		$C_bulk_message->setGroupId( $params['camp_group'] );
		$C_bulk_message->setScheduledType( $params['send_when'] );
		$C_bulk_message->setHours( $params['hours'] );
		$C_bulk_message->setMinutes( $params['minutes'] );
		$C_bulk_message->setDay( $params['cron_day'] );
		$C_bulk_message->setMonth( $params['cron_month'] );
		$C_bulk_message->setWeek( $params['cron_week'] );
		$C_bulk_message->setMessage( $params['message'] );
		$C_bulk_message->setCronHours( $params['cron_hours'] );
		$C_bulk_message->setCronMinutes( $params['cron_minutes'] );
		$C_bulk_message->setOrgCredits( $params['org_credits'] );
		$C_bulk_message->setDateField( $params['date_field'] );
		$C_bulk_message->setType( $queue_type );
	
		$C_bulk_validator = BulkMessageValidatorFactory::getValidator( BulkMessageTypes::valueOf( $queue_type ) );
	
		$C_bulk_validator->validate( $C_bulk_message , ValidationStep::$VALIDATE );
	}
	
	public function queueBulkBlast( $campaign_id, $message_id, $params, $default_arguments, $blast_type = 'SMS' ){
		
		$blast_type = strtoupper( $blast_type );
		
		//validating data
		$C_bulk_message = new BulkMessage();
		$C_bulk_message->setId( $message_id );
		$C_bulk_message->setCampaignId( $campaign_id );
		$C_bulk_message->setGroupId( $params['camp_group'] );
		$C_bulk_message->setScheduledType( $params['send_when'] );
		$C_bulk_message->setMessage( $params['message'] );
		$C_bulk_message->setDateField( $params['date_field'] );
		$C_bulk_message->setHours( $params['hours'] );
		$C_bulk_message->setMinutes( $params['minutes'] );
		$C_bulk_message->setDay( $params['cron_day'] );
		$C_bulk_message->setMonth( $params['cron_month'] );
		$C_bulk_message->setWeek( $params['cron_week'] );
		$C_bulk_message->setCronHours( $params['cron_hours'] );
		$C_bulk_message->setCronMinutes( $params['cron_minutes'] );
		$C_bulk_message->setDefaultArguments( $default_arguments );
		$C_bulk_message->setParams( $params );
		$C_bulk_message->setType( $blast_type );
		
		if( $blast_type == 'CUSTOMER_TASK' ){
			
			$C_bulk_message->setId( $params['store_task_msg_queue_id'] );
			$C_bulk_message->setGroupId( $params['customer_store_task_audience'] );
			$C_bulk_message->setScheduledType( 'PARTICULAR_DATE' );
			$C_bulk_message->setMessage( $params['store_task_display_text'] );
			$C_bulk_message->setScheduledOn( $params['store_task_start_date'] );
			
			$this->logger->debug('@@Customer Widget Params111111 : '.print_r( $C_bulk_message->getHash() , true ) );
		}
	
		$C_bulk_validator = BulkMessageValidatorFactory::getValidator( BulkMessageTypes::valueOf( $blast_type ) );
	
		$queue_id = $C_bulk_validator->validate( $C_bulk_message , ValidationStep::$QUEUE );
	
		if( $queue_id > 0 )
			return 'SUCCESS';
	}
	
	/**
	 *
	 * It will return hash array with the bulk message details
	 * @param int $msg_id
	 */
	public function getBulkMessageDetails( $msg_id ){
	
		$this->C_BulkMessage->load( $msg_id );
		
		return $this->C_BulkMessage;
	}
	
	/**
	 * depending on the type of campaign returns the date
	 * @param unknown_type $data
	 * CONTRACT(
	 *
	 * 	'send_when' : Type of selections ENUM [ IMMEDIATE | PARTICULAR_DATE | SCHEDULE ]
	 * 	'hours' : hour selection
	 * 	'minutes' : minute selection
	 * 	'cron_day' : cron days
	 * 	'cron_week' : cron weeks
	 * 	'cron_month' : cron months
	 * )
	 */
	public function getDateBySendingTypeForBulkBlast( $data, $readable = true ){
	
		$send_type = $data['send_when'];
		$this->logger->info( 'Send When : '.$send_type );
		switch( $send_type ){
	
			case 'SCHEDULE' :
	
				return 'RECURRING';
	
			case 'PARTICULAR_DATE' :
	
				if( $readable )
					$date = date( 'dS M Y' , strtotime( $data['date_field'] ) );
				else
					$date = $data['date_field'];
					
				$this->logger->info( 'date selected '.$date );
				//time details
				$hour = $data['hours'];
				$minute = $data['minutes'];
				$seconds = date( 's' );
	
				$calculated_date = $date.' '.$hour.':'.$minute.':'.$seconds;
				$this->logger->info( 'date calculated '.$calculated_date );
	
				return $calculated_date;
	
			case 'IMMEDIATE' :
	
				return date( 'Y-m-d H:i:s' );
		}
	}

	/**
	 * 
	 * It will return the supported tags for the BulkMessage msg_type 
	 * like SMS,EMAIL,STORE_TASK
	 * @param string $msg_type
	 */
	public function getSupportedTagsByType( $msg_type = 'SMS' ){
		
		$C_bulk_validator = BulkMessageValidatorFactory::getValidator( BulkMessageTypes::valueOf( $msg_type ) );
		
		return $C_bulk_validator->getTags();
	}
	
	/**
	 * @param unknown $campaign_id
	 */
	public function getMsgingUsageReport( $campaign_id ){

		return $this->campaign_model_extension->getMsgingUsageReport( $campaign_id );
	}
	
	/**
	 * get email list for campaign overview
	 * @param unknown_type $campaign_id
	 */
	public function getEmailCampaignList( $campaign_id ){
		
		return $this->campaign_model_extension->getEmailCampaignList( $campaign_id );
	}
	
	/**
	 * It returns the info for email overview report
	 * @param unknown_type $campaign_id
	 */
	public function getEmailOverviewDetails( $campaign_id , $message_id ){
		
		return $this->campaign_model_extension->getEmailOverviewDetails( $campaign_id , 
						$message_id );
	}
	
	/**
	 * returns all active campaigns for all org
	 */
	public function getActiveCampaignForAllOrg(){
		
		return $this->campaign_model_extension->getActiveCampaignForAllOrg();
	}

	public function getInboxSkippedMessageCount($start_date, $end_date, $campaign_id, $org_id) {
		return $this->campaign_model_extension->getInboxSkippedMessageCount(date('Y-m-d',($start_date/1000)),
		 date('Y-m-d', ($end_date/1000)), $campaign_id, $org_id);
	}
	
	public function getOrgSkippedMessageCount($start_date, $end_date, $org_id) {
		return $this->campaign_model_extension->getOrgSkippedMessageCount(date('Y-m-d',($start_date/1000)),
		 date('Y-m-d', ($end_date/1000)), $org_id);
	}
	
	public function getControlGroupsByCampaignID( $campaign_id,$favourite, $search_filter ){
		return $this->campaign_model_extension->getControlGroupsByCampaignID( $campaign_id ,$favourite, $search_filter );
		
	}
	
	/**
	 * Get Campaign Report data for Forward to friend log.
	 * @param unknown $campaign_id
	 * @return Ambigous <multitype:, boolean>
	 */
	public function GetForwardToFriendsDataByCampaignId( $campaign_id , $message_id ){
		return $this->campaign_model->getForwardToFriendByCampaignId( $campaign_id ,$message_id, $this->org_id );
	}

	/**
	 * Returns Campaign Email as Option With Group Label Appended to it.
	 * @param unknown $campaign_id
	 * @return unknown
	 */
	public function getCampaignEmailAsOption( $campaign_id ){
		
		$all_groups = $this->campaign_model_extension->getGroupsByCampaignId( $campaign_id );
		
		foreach ( $all_groups as $group ){
			$groups[$group['group_id']] = $group['group_label'];
		}	
		
		$message_list = $this->campaign_model_extension->getEmailCampaignListAsOptions( $campaign_id );

		foreach ( $message_list as $message  ){
			$key = $message['subject'].'--'.$groups[$message['categoryIds']];
			$email_options[ $key ] = $message['messageId'];
		}
		
		return $email_options;
	}
	
	/**
	 * Getting all the email forwarded by particular sender.
	 * @param unknown $outbox_id
	 * @param unknown $campaign_id
	 */
	public function getForwardedByOutBoxId( $sender , $campaign_id ){
		return $this->campaign_model_extension->getForwardedByOutBoxId( $sender, $campaign_id, $this->org_id );
	}
}
?>