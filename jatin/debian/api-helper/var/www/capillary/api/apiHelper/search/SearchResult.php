<?php

/**
 * Represents the results returned after a search query.
 * Each document is an assoc array containing the deserialized
 * doc. 
 * 
 * @author pigol
 */


class SearchResult{
    
   private $count = 0; 
    
   private $documents = array(); 
   
   private $errorMsg = "";
    
   private $success = true;
   
   private $logger;

   private $raw_data;

   public function __construct($solr_response)
   {
      global $logger;
      $this->logger = $logger;
      if(!is_a($solr_response, 'Apache_Solr_Response'))	
      {
	$this->logger->error("Incorrect response object passed");
	throw new Exception("Incorrect response object");
      }
     		
      if(intval($solr_response->getHttpStatus()) === 200)	
      {
         $this->logger->debug("Successfull response");
	 $this->success = true;
      }else{
	 $this->logger->debug("Error in response");
	 $this->success = false;
	 $this->errorMsg = $solr_response->getHttpStatusMessage(); 	
      }	    	

      $this->raw_data = $solr_response->getRawResponse();		
      $this->parseData();
   }
    
 
   private function parseData()   
   {
      //chinese uncle ka kya hoga idhar????	
      $data = json_decode($this->raw_data,true);
      $this->setCount($data['response']['numFound']);      
      $this->documents = $data['response']['docs'];	 
   }

   private function setCount($count=0){
       $this->count = $count;
   }
   
   
   public function getCount(){
       return $this->count;      
   }
   
   
   private function addDocument($doc)
   {
       array_push($this->documents, $doc);
       $this->count++;
   }
   
  
   public function getDocuments(){
       return $this->documents;
   }
   

   private function setSuccess($success=true){
       $this->success = $success;
   }
   
   
   public function getSuccess(){
       return $this->success;
   } 
   
   
   private function setErrorMsg($msg){
       $this->errorMsg = $msg;
   }

   
   public function getErrorMsg(){
       return $this->errorMsg;
   } 
   
   
}


?>
