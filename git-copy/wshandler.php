<?php

 /**
  * This class takes the responsibility of delegating the api calls to 
  * the correct resource. Basically all the plumbing work around the 
  * InTouch API's 
  * 
  * @author pigol
  */

//Defining Global Variables for API
global $gbl_api_version;
require_once('apiHelper/Errors.php');
require_once('resource/transaction.php');
require_once('resource/customer.php');
require_once('resource/coupon.php');
require_once('resource/feedback.php');
require_once('resource/points.php');
require_once('resource/product.php');
require_once('resource/organization.php');
require_once('resource/communications.php');
require_once('resource/associate.php');
require_once('resource/stores.php');
require_once('resource/tasks.php');
require_once('resource/request.php');
require_once('resource/triggers.php');
require_once ('apiHelper/FileCachedApis.php');
// cache handler for api
require_once 'apiHelper/ApiCacheHandler.php';
require_once 'apiHelper/ApiOutputHandler.php';
require_once 'cheetah/helper/CacheFileManager.php';

class WSHandler{
	
	private $url_parser;
	private $url; 
	private $resource;
	private $version;
	private $method;
	private $input; //POST body
	private $data; //transformed POST body into an assoc array format
	private $query_params; //the query params in a k=>v format 
	private $logger;  //All logging to happen through this. No global logger
	private $format; //data exchange format, by default it is xml 
	private $http_method;
	
	function __construct($url, $input)
	{
		global $logger, $gbl_api_version;
		$this->url_parser = new UrlParser();
		$this->url = $url;
			
		$this->input = $input;
		$this->logger = $logger;
		$this->http_method = $_SERVER['REQUEST_METHOD'];
		
		list($this->version, $this->resource, $this->method, $this->query_params) = $this->url_parser->parseApiUrl($this->url);

		$this->logger->debug("Version: $this->version
							   Resource : $this->resource
							   Method: $this->method
							   Query_params: " . print_r($this->query_params, true));
		
		$this->format = ($this->query_params['format'] != "") ? $this->query_params['format'] : "xml";
		$gbl_api_version = $this->version;
	//	$this->format = "json";
		$this->api_output_handler = new ApiOutputHandler();
	}	
	
	private function check_cached_file_exists(){
		$this->logger->info("checking if cached file already exists for". $this->resource . "/" . $this->method);
		$cache_time = (isset(FileCachedApis::$file_cached_apis[$this->resource][$this->method]['cache_time'])) ? FileCachedApis::$file_cached_apis[$this->resource][$this->method]['cache_time'] : 0;
		$file_ext = 'xml.gz'; //only compressed files
		
		$this->cache_file_manager = new CacheFileManager($this->resource, $this->method, $cache_time, '', $file_ext, $this->query_params);
		$this->file_path = $this->cache_file_manager->getFilePath();
		if($this->cache_file_manager->isFileCreated())
    	{
	        try{
	            while($this->cache_file_manager->isFileLocked()){
	                $this->logger->info("File is locked..sleeping for 10 seconds");
	                sleep(10);
	            }
	            $this->logger->info("File is unlocked, I am trying to read it");
	            $this->file_path = $this->cache_file_manager->fetchFile();
			
				return $this->file_path;
	            
	        }catch(Exception $e){
	            $this->logger->debug("Error in reading file from S3/disk: " . $e->getMessage());
	            $this->logger->debug("Normal execution will flow");    
	        }     
    	}
	}

	/**
	 * This method will do:-
	 * 
	 * 1) Data transformation for both input and output
	 * 2) Basic Input validations
	 * 3) Request Delegation to the resource
	 */
	public function processRequest()
	{
		//If cached file already exists return it
		if( FileCachedApis::$file_cached_apis[$this->resource][$this->method] ){
			$cached_file_path = $this->check_cached_file_exists();
			if($cached_file_path)
			{
				$this->api_output_handler->set_response_type("FILE_BUFFER");
				$this->api_output_handler->set_file_path($cached_file_path);
				return $this->api_output_handler;
			}
		}
		
		$result = array(
					'status' =>  array('success' => true,'code' => ErrorCodes::$api['SUCCESS'],
					'message' => ErrorMessage::$api['SUCCESS'])
				   );

		try{
			
			
			//Do the transformation only for http post and put methods. 
			//The GET method will use only the query parameters.
			if($this->http_method == 'POST' || $this->http_method == 'PUT')
			{
				$this->transformInputData();
			}

			switch($this->resource)
			{
				case 'customer':

					$customer = new CustomerResource();
					//$response['response'] = $this->data;//array('item' => array(array('a1'=>'a', 'b1'=>'b'), array('a1'=>'a', 'b1'=>'b')));
					$result = $customer->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;	

				case 'transaction' :

					$transaction = new TransactionResource();
					$result = $transaction->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
				
				case 'feedback' :
					
					$feedback = new FeedbackResource();
					$result = $feedback->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
				
				case 'coupon' :

					$coupon = new CouponResource();
					$result = $coupon->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
			
				case 'points' :

					$points = new PointsResource();
					$result = $points->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
					
				case 'product' :
					
					$product =  new ProductResource();
					$result = $product->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
					
				case 'organization':
					
					$organization = new OrganizationResource();
					$result = $organization->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
					
				case 'communications':
				
					$communications = new CommunicationsResource();
					$result = $communications->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
					
				case 'associate':
				
					$associate = new AssociateResource();
					$result = $associate->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
				
				case 'store':
					
					$stores = new StoreResource();
					$result = $stores->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
				
				case 'diagnostics':

					include_once 'resource/diagnostics.php';
					$store_diagnostics = new StoreDiagnosticsResource();
					$result = $store_diagnostics->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
					
				case 'task':
					
					$tasks = new TaskResource();
					$result = $tasks->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
					
				case 'request':
						
					$requests = new RequestResource();
					$result = $requests->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
							
				case 'tenders':
					include_once 'resource/tenders.php';
					$requests = new TendersResource();
					$result = $requests->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
					
                                case 'triggers':
                                        include_once 'resource/triggers.php';
                                        $triggers = new TriggersResource();
                                        $result = $triggers->process($this->version, $this->method, $this->data, $this->query_params, $this->http_method);
					break;
				default :
					//Error dictionary
					$result['status'] = array('success' => 'false','code' => ErrorCodes::$api['UNSUPPORTED_RESOURCE'],
										'message' => ErrorMessage::$api['UNSUPPORTED_RESOURCE']);				
			}
			
		}catch(Exception $e){
			//overload the toString method of exception ???
			$this->logger->error("Caught exception: " . $e->getCode() . " message: " . $e->getMessage());
			$result['status'] = array('success' => 'false','code' => $e->getCode(),'message' => $e->getMessage());
		}
		
		//return file if file exists
		if( FileCachedApis::$file_cached_apis[$this->resource][$this->method] ){
                    if($result instanceof CacheFileManager){
                        $file_path = $this->return_file($result);
			if($file_path)
			{
				$this->api_output_handler->set_response_type("FILE_BUFFER");
				$this->api_output_handler->set_file_path($file_path);
				return $this->api_output_handler;
                        }
                    }
		}
		
		ApiUtil::findApiStatusFromResponse($result);
        $response = $this->transformOutputData($result);
        $this->writeAPIlogs($response);
     
	 	$this->api_output_handler->set_response_type("NORMAL_BUFFER");
		$this->api_output_handler->set_response($response);
		//return $response;
		return $this->api_output_handler;
	}
	
	
	/**
	 * This method will transform the input to Array format
	 * and then invoke the appropriate Data transformer (JSON/XML)
	 * depending on the format specified in the endpoint
	 */
	
	private function transformInputData()
	{
		if($this->format == 'xml' || $this->format == 'XML' || $this->format == 'ajm')
			$transformer = DataTransformerFactory::getDataTransformerClass('XML');
		
		if($this->format == 'json' || $this->format == 'JSON')
			$transformer = DataTransformerFactory::getDataTransformerClass('JSON');

        if($this->format == 'file' || $this->format == 'FILE')
            $transformer = DataTransformerFactory::getDataTransformerClass('FILE');

		try{
			$this->data = $transformer->decode($this->input);	
			$this->logger->info("Decoded input: " . print_r($this->data, true));
			if(!isset($this->data['root']))
			{
				$this->logger->error("Element Named 'root' is missing");
				throw new Exception("Element Named 'root' is missing",400 );
			}
		}catch(Exception $e){
			$this->logger->error("Error in transforming the input data to assoc array");
			throw $e;	
		}
	}

	/**
	 * Transforms the output to desired format.
	 * @param $data 
	 * @return JSON or XML representation of data
	 */
	
	private function transformOutputData($data)
	{
		if($this->format == 'xml' || $this->format == 'XML' || $this->format == 'ajm' ||
            $this->format == 'file' || $this->format == 'FILE')
			$transformer = DataTransformerFactory::getDataTransformerClass('XML');
		
		if($this->format == 'json' || $this->format == 'JSON')
			$transformer = DataTransformerFactory::getDataTransformerClass('JSON');
		
		$node = ($this->format == 'ajm') ? 'root' : 'response';	
		return $transformer->doTransform($data, $node);	
	}

    private function writeAPILogs($response){
        require_once('apiHelper/ApiReqRespLog.php');
        global $uuid;
        $apilogger = new ApiReqRespLog();
        $apilogger->debug("[".date("Y m d H:i:s")."] [$uuid] [REQUEST] [".base64_encode($this->input)."]");
        $apilogger->debug("[".date("Y m d H:i:s")."] [$uuid] [RESPONSE] [".base64_encode($response)."]");
    }
	
	private function return_file($cache_file_manager){

    	$cachefile_path = $cache_file_manager->getCacheFilePath();
        if($cache_file_manager->getConfigFilterEnabled())
            $this->file_path = $cache_file_manager->getFilePath();
    
		if(file_exists($cachefile_path)){
			$gz = gzopen($this->file_path, 'w9');
        	$fh=fopen($cachefile_path, 'r');
			#$this->cache_file_manager->setFileId($file_id);
	        $this->logger->debug("Writing the gz file to : ".$this->file_path);
	
	        while(!feof($fh))
	        {
	            gzwrite($gz,fgets($fh,8192));
	        }
	        //$logger->debug("Cache Compressed File PUT status : ".gzwrite($gz, $file_contents));
	        gzclose($gz);
		
	        $this->logger->debug("Writing gz file complete");
	
			$this->logger->debug("Pushing file in S3");
	             
	        $cache_file_manager->storeFile($this->file_path);
	        $this->logger->info("Deleting the temporary xml file we are creating: $cachefile_path");
	        @unlink($cachefile_path);	
	        
	        $this->logger->debug("$cachefile_path deleted...Unlocking file from in db");
	        $cache_file_manager->unlockFile();
	        $this->logger->debug("File unlocked");
				
			return $this->file_path;
		}
	}
}	

?>
