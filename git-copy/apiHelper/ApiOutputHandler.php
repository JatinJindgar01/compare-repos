<?php
include_once('cheetah/helper/Util.php');

class ApiOutputHandler{
	private $file_path;
	private $response;
	private $response_type;
	
	public function __construct(){
		global $logger;
		$this->logger = $logger; 
		
	}
	
	public function set_response_type($type){
		$this->response_type = $type;	
	}
	
	public function set_file_path($file_path){
		$this->file_path = $file_path;
	}
	
	public function set_response($response){
		$this->response = $response;
	}
	
	public function buffer_output(){
		if($this->response_type == "FILE_BUFFER"){
			if($this->file_path){
				 $HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
			    if( headers_sent() ){
			        $encoding = 'gzip';
			    }else if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false ){
			        $encoding = 'x-gzip';
				
			    }
			    else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false ){
			        $encoding = 'gzip';
			    }
			    else{
			        $encoding = 'gzip';
			    }
			
			    header('Content-Encoding: '.$encoding);
		        ApiUtil::readfile_chunked($this->file_path);		
			}
		}
		elseif($this->response_type == "NORMAL_BUFFER"){
			ob_clean();
			ob_start();
			$this->logger->info("***** Result: $this->response");
			echo trim($this->response);
	
			$this->logger->debug("Buffering: " . var_export(ob_get_status(true), true));
			
			if (ob_get_length() > 0) {
			
				$this->logger->debug("flushing out content");
				ob_end_flush();
				ob_flush();
				flush();
			}
		}
	}
}
?>
