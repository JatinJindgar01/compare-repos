<?php

/**

 Class for representing an asynchronous job. 
 It may be a Beasnstalk job/SQS job or any other 
 job. This will be the abstraction on top of that

 @author pigol, gaurav  
        
**/
global $cfg;
require_once('common.php');
require_once('Pheanstalk/pheanstalk_init.php');

class Priority{

 //job without a delay
 const URGENT = 0;

 //job with default priority
 const NORMAL = 1024;
 
 //job with no urgency, can be processed at its own pace
 const LOW = 2048;

}


class Job{

  private $_ttr;
  private $_priority;
  private $_payload;
  private $_id;

  //hashmap for string to string for carrying the context of the job
  private $_context_map;
        
  const DEFAULT_TTR = 3600;

  const DEFAULT_PRIORITY = Priority::LOW;

  public function __construct($payload, $priority = self::DEFAULT_PRIORITY, $ttr = self::DEFAULT_TTR)
  {  
       $this->_payload = $payload;
       $this->_priority = $priority;
       $this->_ttr = $ttr;
     /*  $this->_context_map = array('submission_time' => date('Y-m-d H:i:s'), 
                                  'submitted_by' => $submitted_by
                                 ); 
     */   
  }            

  public function getId()
  {
     return $this->_id;   
  }      
 
  public function setId($job_id)
  {
     $this->_id = $job_id;     
  }      

  public function getPriority()
  {
     return $this->_priority;   
  }      

  public function getTTR()
  {
     return $this->_ttr;   
  }      

  public function getPayload()
  {
     return $this->_payload;   
  }      

  public function getContext()      
  {
     return $this->_context_map;   
  }      

  public function getContextKey($key)
  {      
     return isset($this->_context_map[$key]) ? $this->_context_map[$key] : "";    
  }

  public function setContextKey($key, $value)      
  {
     $this->_context_map[$key] = $value;    
  }

 
  public function setContext($context_map)
  {
     if(!is_array($context_map)){
        throw new Exception("Context is not a map");
     }      

     $this->_context_map = $context_map;   

  }

  public function getData()      
  {      
     return json_encode(array('context' => $this->_context_map, 'payload' => $this->_payload));   
  }

  /**        
     Input is Pheanstalk_Job class
     and return type is the instance of this job   
  **/      
  public static function parseJob($job)      
  {
      if(!get_class($job) == 'Pheanstalk_Job'){
        throw Exception("Input not of type Pheanstalk_Job");
      }       
        
      if(!$job){  
        throw new Exception("Invalid input"); 
      }  

      $data = json_decode($job->getData(), true);              
      $id = $job->getId();

      $j = new Job($data['payload']);
      $j->setId($id);
      $j->setContextMap($data['context']);    
        
      return $j;  
  }      

  public function __destruct(){
        
  } 

}

?>
