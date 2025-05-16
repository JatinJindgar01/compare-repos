<?php

/**
 * 
 * @author prakhar
 *
 *
 */
class UrlParser extends Base {

	private $m_url;
	private $m_container;
	private $m_flash;
	private $m_from;
	
	private $page;
	private $m_nameSpace;
	private $resource_id;
	private $m_module = 'businessProcesses';
	private $m_action = 'index';
	
	private $m_params = array();
	
	private $m_return_type = "html";
	private $m_version;
	
	private $logger;
	private $explode_param = '/';
	
	private static $m_call_type = 'web';
	private $mem_cache_manager;
	public function UrlParser( $url = false ){
		
		global $logger;
		
		$this->logger = &$logger;
		$this->mem_cache_manager = MemcacheMgr::getInstance();
		
		if( !$url )
			$this->m_container = $_GET;
		else
			$this->m_container = $url;
		
		$this->setFlash();
		$this->setFrom();
		
		$this->setUrl();
		$this->parseUrl();
		$this->setReturnType();
	}	

	 function getUrl(){
		
		return $this->m_url;
	}
	
	function getNameSpace(){
		
		return $this->m_nameSpace;
	}
		
	function getPage(){
		
		if( !$this->page )
			return 'index';
			
		return $this->page;
	}

	function getFlashMessage() {

		return $this->m_flash;
	}
	
	function getFrom(){
		
		return $this->m_from;
	}	
	
	function getParams() {
		
		return $this->m_params;
	}
	
	function getReturnType() {
		
		return strtolower( $this->m_return_type );
	}
	
	function getModule(){
		return $this->m_module;
	}	

	function getAction(){
		return $this->m_action;
	}

	function getVersion(){
		return $this->m_version;
	}
	
	function getResourceId(){
		
		return $this->resource_id;
	}
	
	function setLegacyVersion(){
		
		$this->m_version = '1.0.0.0';
	}
	
	private function setUrl(){
		
		$this->m_url = isset( $this->m_container['url'] ) ? $this->m_container['url'] : null;
	}
	
	private function setReturnType(){
		
		if( isset( $_GET['return_type'] ) )
			$this->m_return_type = strtolower( $this->m_container['return_type'] );
	}
	
	private function setNameSpace( $count, $path ){
		
		$name_space_path = $this->getNameSpacePath( $count, $path );
		$this->m_nameSpace = implode( $this->explode_param, $name_space_path );
	}

	private function setResourceId( ){
		
		$cache_key = 'oa_'.CacheKeysPrefix::$urlParserKey.'_RESOURCE_NAMESPACE_'.$this->m_nameSpace.'_CODE_'.$this->page;
		$ttl = CacheKeysTTL::$urlParserKey;
		try{
			
			$this->resource_id = $this->mem_cache_manager->get( $cache_key );
						
		}catch( Exception $e ){

			//set to mem cache
			try{

				$sql = "
				
						SELECT resource_id
						FROM actions
						WHERE namespace = '$this->m_nameSpace' AND code = '$this->page' 
				";	
			
				$this->resource_id = $db->query_scalar( $sql );
			
				//set to mem cache
				$this->mem_cache_manager->set( $cache_key, $this->resource_id, $ttl );

			}catch( Exception $inner_e ){
				
				$this->logger->error( 'Key Could Not Be Set' );
			}
		}
	}
	
	private function setPage( $count, $path ){
		
		$page_index = $this->getPageIndex( $count );
		$this->page = $path[ $page_index ];
	}
	
	private function setFlash(){
		
		$this->m_flash = strip_tags($this->m_container['flash']);
	}
	
	private function setFrom(){
		
		$this->m_from = $this->m_container['from'];	
	}
	
	private function setParams(){
		
		unset( $this->m_container['url'] );
		unset( $this->m_container['from'] );
		unset( $this->m_container['flash'] );
		
		$this->m_params = $this->m_container;
		
	}
	
	private function getPageIndex( $count ){
		
		return $count - 1;
	}
	
	private function getNameSpacePath( $count, $path ){
		
		$page_index = $this->getPageIndex( $count );
		
		unset( $path[ $page_index ]);
		
		return $path;
	}
	
	private function moduleActionUrlParser( $params ){
		
		$i = 1;
		$action = isset($params[$i]) ? $params[$i++] : '';
		if( $action == '' ) return ;
		
		$pos_of_dot = strpos( $action, "." );
		if ( $pos_of_dot > 0 ){
		
			$this->m_return_type = substr($action, $pos_of_dot+1);
			$action = substr($action, 0, $pos_of_dot);
		}
		$this->m_action = $action;
		$this->page = $action;
		
		for ( ; $i < count( $params ); $i++){
			
			$this->logger->info( 'params :- '.$params[$i] );
			array_push( $this->m_params, $params[$i] );
		}
		
	}
	
	private function getModuleVersion( $module_code ){
		
		$cache_key = 'oa_'.CacheKeysPrefix::$urlParserKey.'_MODULE_VERSION_CODE_'.$module_code;
		$ttl = CacheKeysTTL::$urlParserKey;
		try{
			
			$version = $this->mem_cache_manager->get( $cache_key );
						
		}catch( Exception $e ){

			//set to mem cache
			try{

				$db = new Dbase('stores');
		
				$sql = " SELECT `version` FROM modules WHERE code = '$module_code' ";
				
				$version = $db->query_scalar( $sql );
				$this->mem_cache_manager->set( $cache_key, $version, $ttl );
			}catch( Exception $inner_e ){
				
				$this->logger->error( 'Key Could Not Be Set' );
			}
		}
		
		return $version;
	}
	
	private function parseUrl(){
		
		$this->logger->debug("Parsing url: ".$this->m_url);
		
		$path = explode( "/", $this->m_url );
		$depth = count( $path );

		$module_code = ( $path[0] != '' ) ?  $path[0] : 'home';
		$this->m_module = $module_code;
		
		//get version by module code
		$version_number = $this->getModuleVersion( $module_code );
		$this->m_version = $version_number;
		
		if( $version_number == '1.0.0.0' )
			$this->moduleActionUrlParser( $path );
		elseif( $version_number == '1.0.0.1' ){
				
			$this->setPage( $depth, $path );
			$this->setNameSpace( $depth, $path );
			//$this->setResourceId( );
			$this->setParams();	
		}else
			$this->moduleActionUrlParser( $path );
	}
	
	function getUrlHashString(OrgProfile  $org) {
		$str = $org->org_id . '/' . $this->getModule() . '/' . $this->getAction() . '/' . implode('::', $this->getParams());
		return $str;
	}
	
	function debugMsg() {
		global $logger;
		$logger->debug("URL DEBUG: Module: $this->m_module, Action= $this->m_action, Return: $this->m_return_type, Params: ".implode(', ', $this->m_params));
	}
	
	public function isLegacyUrl(){
		if($this->m_version == '1.0.0.0'){
			return true;
		}
		return false;
	}	
	
	/**
	 * Parses the url for the new InTouch API's
	 * @param $url http://intouch.capillary.co.in/v1/customer/get?query=sfsdf
	 * 
	 * @return array(version, resource, method, array(query_params))
	 * 		version: api version
	 * 		resource
	 *      method: method on the resource not HTTP Method
	 *      query_params
	 */
	
	public function parseApiUrl($url){
		global $logger;
		
		$logger->debug("Parsing Api url: $url");
		if($url == ""){
			return array();
		}
		
		$url_parts = explode("/", $url);
		$version = $url_parts[1];
		$resource = $url_parts[2];
		$pos = strpos($url_parts[3], '?') ? strpos($url_parts[3], '?') : strlen($url_parts[3]);
		$method = substr($url_parts[3], 0, $pos);
		
		$extension = "";
		$method_arr = explode(".",$method);
		$method = $method_arr[0];
		
		if(count($method_arr) >= 2)
			$extension = $method_arr[1];
		
		$query_params = array();
		$query_string = urldecode( $_SERVER['QUERY_STRING'] );
		$query_parts = explode('&', $query_string);
		foreach($query_parts as $q){
			$parts = explode('=', $q);
			$query_params[$parts[0]] = $parts[1];
		}
		if(!empty($extension))
		{
			$extension = strtolower($extension);
			if($extension != 'xml' && $extension != 'json' && $extension != 'file')
				$extension = 'xml';
			$query_params['format'] = $extension;
		}	
		
		return array($version, $resource, $method, $query_params);
	}

	public static function setCallRequestType( $call_type ){
		
		self::$m_call_type = $call_type;
	}
	
    public static function isApiRequest( ){
	
    	if( strtolower( self::$m_call_type ) == 'api' )
    		return true;
    		
        return false;
    }
    
    public static function isAuthenticationRequest(){
    	
    	if( strtolower( self::$m_call_type ) == 'authentication_service' )
    		return true;
    	
    	return false;
    }
}
	
?>