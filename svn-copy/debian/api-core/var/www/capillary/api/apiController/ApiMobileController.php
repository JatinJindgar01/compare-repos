<?php

include_once 'apiModel/class.ApiPassbookModelExtension.php';

/**
 * Mobile Controller
 *
 *
 * @author pv
 */
class ApiMobileController extends ApiBaseController{

	private $C_passbook_model;
	private $C_file_controller;
	
	public function __construct(){

		parent::__construct();
		
		$this->C_file_controller = new ApiFileController();
		$this->C_passbook_model = new ApiPassbookModelExtension();
	}

	/**
	 * The function to add the passbook configurations
	 * @param $file_params CONTRACT(
	 * 	back_image => Array(
	 * 						name => file_name
	 * 						type => file_type
	 * 						tmp_name => temp_file_name
	 * 						error => Error_info
	 * 						size => file_size
	 * 					   )
	 *  b_logo1 => Array(
	 *  				  same as Above
	 *  				)
	 *  b_logo2 => Array(
	 *   				  same as Above
	 *  				)
	 *  icon1 => Array(
	 *    				  same as Above
	 *  			  )
	 *  icon2 => Array(
	 *    				  same as Above
	 *  			  )
	 *  
	 *  $params => Array(
	 *  		back_color => VALUE
	 *  		for_color => VALUE
	 *  		tnc => Terms & Condition
	 *  	)
	 * )
	 */
	
	public function selectPassbookConfig( $type ){
		
		$res = $this->C_passbook_model->selectPassbookUiConfig( $type );
		return $res;
		
	}
	
	public function selectPassbookImageFileName( $id ){
		
		return $this->C_file_controller->getPassbookImageFileName( $id );
		
	}
		
	public function addPassbookConfig( $file_params , $params, $update=false ){
		
		//File tag for separate file
		$file_tag = array( 
						   'PASSBOOK_CONFIG_BACKGROUND_IMAGE' , 
						   'PASSBOOK_CONFIG_BACKGROUND_IMAGE_2x' ,
						   'PASSBOOK_CONFIG_BRAND_LOGO' ,
						   'PASSBOOK_CONFIG_BRAND_LOGO_2x' ,
						   'PASSBOOK_CONFIG_ICON' ,
						   'PASSBOOK_CONFIG_ICON_2x',
						   'PASSBOOK_MANIFEST_FILE',
						   'PASSBOOK_PASS_FILE',
						 );
		$cnt = 0;
		$file_id = array();
		
		//Loop for inserting all the file in uploaded files.
		$this->logger->debug("Uploading image, manifest and pass files:");
		foreach( $file_params as $files ){
			foreach ($files as $filekey => $file){
				$file_contents = file_get_contents( $file['tmp_name'] );
				$file_name = explode( '.', $file['name'] );
				
				if( !( empty( $file['name'] ) ) )
				{
					$return_id  = $this->C_file_controller->uploadPassbookBackImage(
																		$file_contents, 
																		$file_tag[$cnt++], 
																		$file_name[0], 
																		$file_name[1]
																	);
				}
				else 
					$return_id=0;
				
				$file_id[$filekey]=$return_id;
			}
		}
		$this->logger->debug("File ids of uploaded files: ".print_r($file_id,true));
		
		//Converting hexadecimal color value into decimal rgb.
		$this->logger->debug("Converting hex color values to RGB values");
		$hex = str_replace( '#', '', $params['back_color'].$params['for_color'].$params['label_color'] );
		$arr = str_split( $hex , 2 );
		$cnt = 0;
		foreach( $arr as $a ){
		
			$back_color[$cnt] = hexdec( $a );
			$cnt++;
		}
		
		$back = array_chunk( $back_color, 3 );
		$params['back_color'] = 'RGB('.implode( ',', $back[0] ).')';
		$params['for_color'] = 'RGB('.implode( ',', $back[1] ).')';
		$params['label_color'] = 'RGB('.implode( ',', $back[2] ).')';
		
		//joining file ids and color params.
		unset( $params['is_form_submitted'] );
		$insert_params = array_merge( $file_id , $params );
		
		if( !$update ){
			
			$this->logger->debug("Config Not Present for org, Inserting new Pass Configs");
			return $this->C_passbook_model->addPassbookUiConfig( $insert_params );
		}
		else{
			
			$this->logger->debug("Config already Present for org, Updating old Configs");
			return $this->C_passbook_model->updatePassbookUiConfig( $insert_params );
		}
	}	
	
	//Converts RGB decimal into Hex
	public function getHexValue( $colorValue ){
	
		preg_match_all( '/([\d]+)/',$colorValue,$match );
		$hexcolor = array();
	
		foreach( $match[0] as $color ){
			if( strlen($color)<2 ){
				array_push( $hexcolor,'0'.dechex($color) );
			}
			else
				array_push( $hexcolor,dechex($color) );
		}
		$hexcolor = implode( "",$hexcolor );
		return $hexcolor;
	}
	
	//returns the 2x size each image in the parameter array passed
	public function get2ximage( $image_files ){
	
		$file_info = array();
		foreach( $image_files as $files ){
			foreach($files as $file){
				if( !( empty( $file['name'] ) ) ){
					$image = new Imagick($file['tmp_name']);
					$height = $image->getimageheight();
					$width = $image->getimagewidth();
					$image->scaleimage($width*2, $height*2, true);
					$tmp_name = $file['tmp_name'].'@2x';
					$name = explode( ".", $file['name'] );
					$name = $name[0]."@2x.".$name[1];
					$image->writeimage( $tmp_name );
					$image2x = new Imagick( $tmp_name );
					$key = array_keys( $files );
					$file2x = array(
							$key[0].'2x' => array(
									'name' => $name,
									'type' => $file['type'],
									'tmp_name' => $tmp_name,
									'error' => 0,
									'size' => $image2x->getimagesize()
							)
					);
					array_push( $file_info, array( $key[0] => $file ) );
					array_push( $file_info, $file2x );
				}
				else{
					$key = array_keys( $files );
					$file2x = array(
							$key[0].'2x' => array(
									'name' => $file['name'],
									'type' => $file['type'],
									'tmp_name' => $file['tmp_name'],
									'error' => $file['error'],
									'size' => $file['size']
							)
					);
					array_push( $file_info, array( $key[0] => $file ) );
					array_push( $file_info, $file2x );
				}
			}
		}
		return $file_info;
	}
}
?>
