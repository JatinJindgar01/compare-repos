<?php

include_once 'apiHelper/search/BaseQueryBuilder.php';

include_once 'apiHelper/search/ProductQueryBuilder.php';
include_once 'apiHelper/search/ProductSearchClient.php';

include_once 'apiHelper/search/CustomerQueryBuilder.php';
include_once 'apiHelper/search/CustomerSearchClient.php';


/**
 * Main search controller class which fires
 * all the search queries
 * 
 * @author pigol
 */

class ApiSearchController extends ApiBaseController{

    public function __construct(){
        parent::__construct();
    }


    public function searchProducts($query, $start = 10, $rows = 10, $is_primary=false, $distinct_filed = null)
    {
        $this->logger->info("Input raw query: $query, distinct: $distinct_filed");
        
        try{
            try {
                $query_builder = new ProductQueryBuilder($query, $is_primary);
                $solr_query = $query_builder->buildSearchQuery($is_primary);
                if($distinct_filed) {
                    $attribute_map = $query_builder->getAttributeMap();
                    $is_valid_attr = false;
                    foreach($attribute_map as $k => $v)
                    {
                        if(strtolower($k) == strtolower($distinct_filed)) {
                            $is_valid_attr = true;
                            if($v["is_dynamic"])
                                $distinct_filed = $k . "_t";
                        }
                    }
                    if(! $is_valid_attr)
                         throw new Exception("Invalid facet field");
                }
            } catch (Exception $e) {
                $this->logger->debug("Failed to build search query : " . print_r($e, true));
                $response = array(
                    'status' => array('success' => 'false', 'code' => ErrorCodes::$api['INVALID_INPUT'],
                        'message' => ErrorMessage::$api['INVALID_INPUT']
                    )
                );
                return $response;
            }
            $this->logger->info("Solr search query: $solr_query");            
            $search_client = new ProductSearchClient();
            $search_results = $search_client->search($solr_query, $start, $rows, $distinct_filed);
            
	    $response = array(
			            'status' =>  array( 'success' => 'true' ,'code' => ErrorCodes::$api[ 'SUCCESS' ],
						'message' => ErrorMessage::$api[ 'SUCCESS' ] 
					       ),
			    		'product' => $search_results
			            );	

	    return $response;
                        
        }catch(Exception $e){
            $this->logger->error("Error in searching the results: " . $e->getMessage());
            throw $e;
        }        
    }
    
    
    private function fallback($query)
    {
    	
    	$this->logger->info("doing mysql fallback for search : $query");
    	
    	$db=new Dbase('users', true);
    	
    	$search_q=explode("|",trim(strtolower($query),"() "));
    	
    	if(count($search_q)!=1)
    	{
    		$this->logger->error("no valid bits");
    		return array(
    				'count'=>0,
    				'start'=>0,
    				'rows'=>0,
    				'results'=>array('item'=>array())
    		);
    	}
    		
    	$bits=explode(":",$search_q[0]);
    	
    	if(!in_array($bits[0],array('email','mobile','external_id')))
    	{
    		$this->logger->error("unsupported identifier");
    		return array(
    				'count'=>0,
    				'start'=>0,
    				'rows'=>0,
    				'results'=>array('item'=>array())
    		);
    	}
    	
    	$this->logger->debug("bits are ".print_r($bits,true));
    		
    	$type=$bits[0];
    	$q=str_replace("%","\%",addslashes($bits[2]));
    	if($bits[1]=="starts")
    		$q=$q."%";
    	
    	//nasty work arounds!!
    	if($type=="email")
    	{
    		$q=str_replace("^2@","@",$q);
    		$q=str_replace("^2","@",$q);
    	}
    	if($type=="mobile")
    		$q=str_replace("*", "%", $q);
    	
    	$this->logger->info("final search like query: $q");
    	
    	if($type=='external_id')
    		$sql="SELECT user_id AS id FROM user_management.loyalty WHERE publisher_id='$this->org_id' AND external_id LIKE '$q'";
    	else
    		$sql="SELECT id FROM user_management.users WHERE org_id='$this->org_id' AND $type LIKE '$q'";
    	
    	$results=$db->query($sql);
    	
    	if(empty($results))
    		return array(
    				'count'=>0,
    				'start'=>0,
    				'rows'=>10,
    				'results'=>array('item'=>array())
    		);

    	$user_ids=array();
    	foreach($results as $res)
    		$user_ids[]=$res['id'];
    	
    	$this->logger->debug("db search found : (".implode(",",$user_ids).")");
    	
    	$users=array();
    	foreach($user_ids as $user_id)
    	{
    		try{
	    		$user=UserProfile::getById($user_id);
	    		$user->load(true);
	    		$hash=array(
	    				'user_id'=>$user->user_id,
	    				'firstname'=>$user->first_name,
	    				'lastname'=>$user->last_name,
    			    	'survivor_account_retrieved'=>'true',
	    				'org_id'=>$this->org_id,
	    				'mobile'=>$user->mobile,
	    				'email'=>$user->email,
	    				'external_id'=>$user->external_id,
	    				'registered_date'=>$user->registered_on,
	    				'loyalty_points'=>$user->loyalty_points,
	    				'lifetime_points'=>$user->lifetime_points,
	    				'lifetime_purchases'=>$user->lifetime_purchases,
	    				'slab'=>$user->slab_name,
	    				'registered_store'=>$user->registered_by,
	    				'last_trans_value'=>0,
	    				'attributes'=>array('attribute'=>array())
	    				);
	    		if(!$user->is_merged)
	    			unset($hash['survivor_account_retrieved']);
	    		$users[]=$hash;
    		}catch(Exception $e)
    		{
    			$this->logger->debug("skipping user: $user_id .Ex:".$e);
    		}
    	}

    	return array(
    			'count'=>count($users),
    			'start'=>0,
    			'rows'=>10,
    			'results'=>array('item'=>$users)
    	);
    	 
    }
    
    public function searchCustomers($query, $start = 0, $rows = 10, $is_primary=false)
    {
        $this->logger->info("Input raw query: $query");
        
        try{
            try {
                $query_builder = new CustomerQueryBuilder($query, $is_primary);
                $solr_query = $query_builder->buildSearchQuery($is_primary);
            } catch (Exception $e) {
                $this->logger->debug("Failed to build search query : " . $e->getMessage());
                $response = array(
                    'status' => array('success' => 'false', 'code' => ErrorCodes::$api['INVALID_INPUT'],
                        'message' => ErrorMessage::$api['INVALID_INPUT']
                    )
                );
                return $response;
            }
            $this->logger->info("Solr search query: $solr_query");            
            $search_client = new CustomerSearchClient();
            $search_results = $search_client->search($solr_query, $start, $rows);
            
            if(empty($search_results['results']['item']))
            	$search_results=$this->fallback($query);

            	
            foreach($search_results['results']['item'] as $i=>$user)
            {
            	$victim=UserProfile::checkVictimAccount($user['user_id'],true);
            	if($victim)
            	{
            		$user=$victim;
            		$user_hash=array(
	    				'user_id'=>$user->user_id,
	    				'firstname'=>$user->first_name,
	    				'lastname'=>$user->last_name,
            			'survivor_account_retrieved'=>'true',
	    				'org_id'=>$this->org_id,
	    				'mobile'=>$user->mobile,
	    				'email'=>$user->email,
            			'external_id'=>UserProfile::getExternalId($user->user_id),
	    				'registered_date'=>$user->registered_on,
	    				'loyalty_points'=>$user->loyalty_points,
	    				'lifetime_points'=>$user->lifetime_points,
	    				'lifetime_purchases'=>$user->lifetime_purchases,
	    				'slab'=>$user->slab_name,
	    				'registered_store'=>$user->registered_by,
	    				'last_trans_value'=>0,
	    				'attributes'=>array('attribute'=>array())
            		);
            		$search_results['results']['item'][$i]=$user_hash;
            	}
            }
            	
            $response = array(
			                'status' =>  array( 'success' => 'true' ,'code' => ErrorCodes::$api[ 'SUCCESS' ],
							'message' => ErrorMessage::$api[ 'SUCCESS' ] 
                                              ),
			    			'customer' => $search_results
                             );
            
	        return $response;
                        
        }catch(Exception $e){
            $this->logger->error("Error in searching the results: " . $e->getMessage());
            throw $e;
        }
    }
}


?>
