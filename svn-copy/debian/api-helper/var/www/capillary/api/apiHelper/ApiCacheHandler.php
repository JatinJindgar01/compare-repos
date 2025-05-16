<?php

include_once 'helper/member_care/MemberCareCacheMgr.php';

class ApiCacheHandler
{
	
	protected static $handlers=array();
	protected $logger;
	
	protected function loadHandlers()
	{
		if(class_exists("MemberCareCacheMgr"))
			self::$handlers[]=new MemberCareCacheMgr(); 
	}
	
	static function triggerEvent($event,$entity_id)
	{
		global $logger;
		
		if(empty(self::$handlers))
			self::loadHandlers();
		
		$logger->info("triggering cache clear event ($event) from API for entity id : $entity_id");
		if(empty($entity_id) || empty($event))
		{
			$logger->error("no input for event triggering");
			return;
		}
		
		foreach(self::$handlers as $handler)
		{
			if(method_exists($handler, 'triggerEvent'))
				$handler->triggerEvent($event,$entity_id);
		}
		
	}
	
}
