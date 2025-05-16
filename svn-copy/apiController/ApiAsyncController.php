<?php
//TODO: referes to cheetah
require_once 'common.php';
//TODO: referes to cheetah
require_once 'thrift/async.php';
//TODO: referes to cheetah
require_once 'helper/ShopbookLogger.php';

class ApiAsyncController extends ApiBaseController{
	
	private $C_async_client; 
	
	public function __construct(){
		
		parent::__construct();
		
		$this->C_async_client = new AsyncThriftClient();
	}
	
	public function getConsumers(){
		
		try{
			
			return $this->C_async_client->getConsumers();
		}catch( Exception $e ){
			
			return null;
		}
	}
	
	public function getQueues(){
		try{
			
			return $this->C_async_client->getQueues();
		}catch( Exception $e ){
			
			return null;
		}
	}
	
	public function getQueueStats( $queue ){
		try{
			
			return $this->C_async_client->getQueueStats( $queue );
		}catch( Exception $e ){
			
			return null;
		}
	}
	
	public function reloadConsumers(){
		try{
			
			return $this->C_async_client->reloadConsumers();
		}catch( Exception $e ){
			
			return null;
		}
	}
	
	public function clearQueue( $queue ){
		try{
			
			return $this->C_async_client->clearQueue( $queue );
		}catch( Exception $e ){
			
			return null;
		}
	}
}