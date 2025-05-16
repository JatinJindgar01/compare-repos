<?php

/**
 * @author cj
 *
 * The base class for all the customers.
 * The data will be flowing to user_management.users, user_management.extd_user_profile (deprecated)
 *
*/

abstract class BaseApiModel{

	protected $logger;
	protected $current_org_id;
	private static $ttlArr;
	
	protected static $iterableMembers;
	
	public function __construct($current_org_id = -1)
	{
		global $logger;

		$this->logger = $logger;
		
		$this->current_org_id = $current_org_id;
	}

	public function __toString()
	{
		return $this->toString();
	}
	
	public static function setIterableMembers(){}
	
	/*
	 * Saving of the data will be done using save to cache.
	* The method accepts key-value pair so that it can save multiple values
	*/
	public function saveToCache($key, $value){

		if(is_object($value))
			$value = $value->toString();
		
		$classname = get_called_class();
		return self::saveValueToCache($key, $value);
	}

	public static function saveValueToCache($key, $value)
	{
                global $logger;
                
		if(!class_exists('MemcacheMgr') || !class_exists('Memcache'))
		{
                    
			$logger->debug("Memcache is not available");
			return false;
		}
		
		$ttl = self::getTTL($key);
		
		//memcache is available
		$memcache = MemcacheMgr::getInstance();
		try
                {
                    $ret = $memcache->set($key, $value, $ttl);
                    return $ret;
                }
                catch (Exception $e)
                {
                    $logger->debug("Failed writing to cache");
                }
		
	}
	public static function deleteValueFromCache($key)
	{
		if(!class_exists('MemcacheMgr') || !class_exists('Memcache'))
		{
			$this->logger->debug("Memcache is not available");
			return false;
		}

		//memcache is available
		try {
			$memcache = MemcacheMgr::getInstance();
			return $memcache->delete($key);
		} catch (Exception $e) {
		}
	}
	// implement the cache mechanism
	public static function loadFromCache($org_id, $key)
	{
		global $logger;

		$string = self::getFromCache($key);	
		if($string)
		{
			$classname = get_called_class();

			$ret = $classname::fromString($org_id, $string);
			return $ret;
		}
			
		return false;
	}

	public static function getFromCache($key)
	{
		global $logger;
		if(!class_exists('MemcacheMgr') || !class_exists('Memcache'))
		{
			$logger->debug("Memcache is not available");
			return false;
		}
		
		try{
			$memcache = MemcacheMgr::getInstance();
			$string = $memcache->get($key);
		}catch (Exception $e){
			$logger->debug("Cache has no data");
		}
		//$logger->debug("Fetched the data from cache for key $key");
		
		return $string;
		
	}
	
	/*
	 * set the array from an array received from the select query
	*/
	public static function fromArray($org_id, $array, $include_deteled=false){

		$classname = get_called_class();
		$obj = new $classname($org_id);
		$classname::setIterableMembers();

		foreach($classname::$iterableMembers as $key)
		{
			if(isset($array[$key]))
			{
				$obj->$key = $array[$key];
				$setFunction = "set".str_replace(" ", "", ucwords(str_replace("_", " ", $key)));
				if(method_exists($obj, $setFunction))
				{	if($include_deteled)
						$obj->$setFunction($array[$key], $include_deteled);
					else
						$obj->$setFunction($array[$key]);
				}
			}
		}
		
		return $obj;
	}

	public function toArray()
	{
		$array = array();
		$classname = get_called_class();
		$classname::setIterableMembers();
		
		foreach($classname::$iterableMembers as $key)
		{
			if(is_object($this->$key) && method_exists($this->$key, "toArray"))
				$array[$key] = $this->$key->toArray();
			else if(is_array($this->$key) && current($this->$key))
			{
				$array[$key] = array();
				foreach($this->$key as $i)
					$array[$key][] = $i->toArray();
			}
				
			else
				$array[$key] = $this->$key;
		}
	
		return $array;
	}
	
	/*
		* all the iterable objects need to have the set method in camel case
	*/
	public function toJson()
	{
		$array = array();
		$classname = get_called_class(); 
		foreach($classname::$iterableMembers as $key)
		{
			if(is_object($this->$key) && method_exists($this->$key, "toJson"))
				$array[$key] = $this->$key->toJson();
			else
				$array[$key] = $this->$key;
		}
			
		return json_encode($array);
	}
	
	// saves a key value list of values 
	public function saveToCacheMulti($keyValuePairArray = array()){
		return false;
	}

	// TODO : implement the cache mechanism
	public static function loadFromCacheMulti($keyArr = array())
	{
		return false;
	}

	/*
	 * Formatting
	* TODO: implement the function
	*
	*/
	public static function fromXml($org_id, $string)
	{
		return "";
	}

	public function toXml()
	{
		return "";
	}

	public static function fromJson($org_id, $string)
	{

		$array = json_decode($string, true);
		return self::fromArray($org_id, $array);
	}
	/*
		* To convert an object to string, for saving in cache
	*/
	public function toString()
	{
		$array = array();
		$classname = get_called_class();
		foreach($classname::$iterableMembers as $key)
		{
			if(is_object($this->$key) && method_exists($this->$key, "toString"))
				$array[$key] = $this->$key->toString();
			else
				$array[$key] = $this->$key;
		}
		return $this->encodeToString($array);
	}

	/*
	 * to return an object formedfrom string
	 */
	public static function fromString($org_id, $string)
	{
		#echo $string;
		return self::fromArray($org_id, self::decodeFromString($string));
	}

	protected static function encodeToString($val)
	{
		if(function_exists('msgpack_pack'))
			return msgpack_pack($val);
		
		else
			return json_encode($val);
	}
	
	protected static function decodeFromString($val)
	{
		if(function_exists('msgpack_unpack'))
			return msgpack_unpack($val);
	
		else
			return json_decode($val, true);
	}

	protected static function generateCacheKey($key, $entity_id, $org_id=-1)
	{
		// global
		if($org_id < 0 || $org_id === NULL )
			return "oa_".$key."##".$entity_id;
		// org specific
		else
			return "o".$org_id."_".$key."##".$entity_id;
	}
	
	protected static function getTTL($key)
	{
		if(!self::$ttlArr)
			self::$ttlArr = parse_ini_file("/etc/capillary/api/api-cache-ttl.ini");
		
		// remove the prefix of org_id
		$key = substr($key, strpos($key, "_")+1);
		// remove the trailing part
		$key = substr($key, 0, strrpos($key, "##"));
		
		if(isset(self::$ttlArr[$key]))
			return self::$ttlArr[$key];
		else
			return self::$ttlArr["default"] ? self::$ttlArr["default"] : 3600;
	}

	public function __call( $name , $arguments )
	{
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
	}
	
	public static function __callstatic( $name , $arguments )
	{
		throw new ApiException(ApiException::FUNCTION_NOT_IMPLEMENTED);
	}
}