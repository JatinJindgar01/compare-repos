<?php
/**
 * @author cj
 * 
 * The interface defines all the functionalities to cache any model object.
 * Currently its going to be run with memcache in the underlying system 
 *
 */
interface ICacheable{
	
	/*
	 * save the data to cache with key and value passed
	 */
	public function saveToCache($key, $value);
	
	/*
	 * Load from cache when the key is passed
	 */
	public static function loadFromCache($org_id, $key);
	
	/*  
	 * Saving of the data will be done using save to cache. 
	 * The method accepts key-value pair so that it can save multiple values 
	 */ 
	public function saveToCacheMulti($keyValuePairArray = array());

	/*
	 *  The function loads the data from the cache 
	 */
	public static function loadFromCacheMulti($keyArr = array());

} 