<?php

/**

Class for wrapping the client to the asynchronous job 
queue. Exposes the utility functions for submitting
jobs to queues, creating queues, removing jobs from queues
archiving them.

Uses the Job class defined in the same folder 

@author pigol, gaurav

**/
require_once('Job.php');
//TODO: referes to cheetah
require_once('common.php');
require_once('Pheanstalk/pheanstalk_init.php');

class AsyncClient{


  const DEFAULT_HOST = '127.0.0.1';

  const DEFAULT_PORT = 11300;        

  const DEFAULT_QUEUE = 'default';

  const DEFAULT_DELAY = 0;

  private $_name;

  private $_host;

  private $_port;

  private $_queue;

  //beanstalkd client or client object
  //to our queue framework
  private $_queue_client;

  public function __construct($name, $queue = self::DEFAULT_QUEUE, $host = self::DEFAULT_HOST, $port = self::DEFAULT_PORT)
  {      
      //$logger =  new ShopbookLogger();
     global $cfg, $logger;   
     if(!$name)   
     {
        throw new Exception("Client name cannot be empty, locate queue, host, port from name only");
     }      

     $this->_name = $name;        
     $this->_queue = (!$queue) ? $cfg['async']['default']['queue'] : $queue;
     $this->_host = (!$host) ? $cfg['async']['default']['host'] : $host;
     $this->_port = (!$port) ? $cfg['async']['default']['port'] : $port;           

     if(isset($cfg['async'][$name])){
             $this->_queue = $cfg['async'][$name]['queue'] ;
             $this->_host = $cfg['async'][$name]['host'] ;
             $this->_port = $cfg['async'][$name]['port'] ;           
     }

     $logger->debug("Queue config : ".$this->_host.":".$this->_port."/".$this->_queue);

     $this->_queue_client = new Pheanstalk($this->_host, $this->_port);
     $this->_queue_client->useTube($this->_queue);   
  }

  /**
    Add a job in the queue
  **/
  public function submitJob($job, $delay = self::DEFAULT_DELAY)
  {
       if(!get_class($job) == 'Job'){ 
          throw new Exception("Please pass class of Job input");
       }       

       $job->setContextKey("submitted_by", $this->_name);
       $job->setContextKey("submission_time", date('Y-m-d H:i:s'));
        
       return $this->_queue_client->put($job->getData(), $job->getPriority(), $delay, $job->getTTR()); 
  }      

 /**
   Archives a job for future use
 **/ 
  public function archiveJob($job, $priority = self::DEFAULT_PRIORITY)      
  {
       return $this->_queue_client->bury(new Pheanstalk_Job($job->getId(), $job->getData()), $priority); 
  }      

  /**
    Deletes a job
  **/
  public function deleteJob($job)        
  {
      return $this->_queue_client->delete(new Pheanstalk_Job($job->getId(), $job->getData()));        
  }
 
 /**
   Polls the queue for a job. For a blocking call don't pass the timeout
 **/
  public function getJob($timeout = null)      
  {      
       $j = $this->_queue_client->reserve($timeout);
       if($j){ 
               $job = Job::parseJob($j);  
               $job->setContextKey("picked_time", date('Y-m-d H:i:s'));
               return $job; 
        }
        else{
           throw new Exception("No job found");     
        } 
  }

  public function getJobStats($job)
  {
     return $this->_queue_client->statsJob(new Pheanstalk_Job($job->getId(), $job->getData()));  
  }
  
  public function getQueueStats($queue)            
  { 
     return $this->_queue_client->statsTube($queue);   
  }      

  /**
   Reschedules a job in the queue. 
  **/
  public function reschedule($job, $priority = self::DEFAULT_PRIORITY, $delay = self::DEFAULT_DELAY)
  {

      $this->_queue_client->release(new Pheanstalk_Job($job->getId(), $job->getData()), $priority, $delay);  
  }      

  
  public function getQueues()
  {
     return $this->_queue_client->listTubes();    
  }

}


?>
