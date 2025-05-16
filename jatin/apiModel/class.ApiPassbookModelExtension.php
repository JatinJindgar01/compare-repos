<?php 

/**
 * 
 * @author pv
 *
 */
class ApiPassbookModelExtension{
	
	private $db;
	private $org_id;
			
	public function __construct(){
		
		global $currentorg, $currentuser;
		
		$this->currentorg = $currentorg;
		$this->currentuser = $currentuser;
		$this->org_id = $currentorg->getId();
		$this->db = new Dbase( 'passbook' );
	}
	
	//Gets the Passbook Config from the database for the current org_id and its type
	public function selectPassbookUiConfig( $type ){
		global $logger; 
		
		$sql="
				SELECT * FROM passbook.`passbook_ui_configs` 
						 WHERE `org_id` = $this->org_id 
						 AND `type` = '$type'";
		
		$logger->debug( $sql );
		
		return $this->db->query_firstrow( $sql );
		
	}
	
	//Update Passbook Config if config exists
	public function updatePassbookUiConfig( $update_params ) {
		global $logger;
		
		$type=$update_params['select'];

		//update only those fields that are entered
		$sql = "
				UPDATE passbook.`passbook_ui_configs` SET ";
					$sql.= ( $update_params['back_image']!=0 ) ? "`background_img_1` = ".$update_params['back_image']."," : "";
					$sql.= ( $update_params['back_image2x']!=0 ) ? "`background_img_2` = ".$update_params['back_image2x']."," : "";
					$sql.= ( $update_params['b_log']!=0 ) ? "`brand_logo_1` = ". $update_params['b_log']."," : "";
					$sql.= ( $update_params['b_log2x']!=0 ) ? "`brand_logo_2` = ". $update_params['b_log2x']."," : "";
					$sql.= ( $update_params['icon']!=0 ) ? "`icon_id_1` = ". $update_params['icon']."," : "";
					$sql.= ( $update_params['icon2x']!=0 ) ? "`icon_id_2` = ". $update_params['icon2x']."," : "";
					//$sql.= ( $update_params['manifest_file']!=0 ) ? "`manifest_id` = ".$update_params['manifest_file']."," : "";
					$sql.= ( $update_params['pass_file']!=0 ) ? "`pass_id` = ".$update_params['pass_file']."," : "";
					$sql.= ( $update_params['back_color']!=null ) ? "`background_color` = \"".$update_params['back_color']."\",":"";
					$sql.= ( $update_params['for_color']!=null ) ? "`foreground_color` = \"". $update_params['for_color']."\",":"";
					$sql.= ( $update_params['label_color']!=null ) ? "`label_color` = \"". $update_params['label_color']."\",":"";
					//$sql.= ( $update_params['tnc']!=null )? "`tnc` = \"".$update_params['tnc']."\",":"";
					$sql = substr($sql, 0, -1);
					$sql.= " WHERE `org_id`= $this->org_id AND `type` = \"$type\"";
					
			$logger->debug( $sql );
					
			return $this->db->update( $sql );
	}
	
	/**
	 * Adds to the database
	 */
	public function addPassbookUiConfig( $insert_params ){
		
		$join = Util::joinForSql( $insert_params );
		
		//insert all parameters
		$sql = "
				INSERT INTO passbook.`passbook_ui_configs`
														( 
														   `org_id`, 
														   `background_img_1`,
														   `background_img_2`,
														   `brand_logo_1`,
														   `brand_logo_2`,
														   `icon_id_1`,
														   `icon_id_2`,".
														   /* `manifest_id`,
														   `pass_id`, */ //commenting because not needed further
														   "`background_color`,
														   `foreground_color`,
														   `label_color`,".
														   //`tnc`,
														   "`type`
														  )
												VALUES(
														$this->org_id,
														$join
													  )";
		return $this->db->insert( $sql );
	}
}
?>
