<?php

include_once('apiHelper/search/BaseSearchClient.php');
include_once('apiHelper/search/SearchResult.php');

/**
 * Search client for customers
 * 
 * @author pigol
 */

class CustomerSearchClient extends BaseSearchClient{

    private static $valid_keys =  array ('pkey', 'user_id', 'org_id', 'firstname', 'lastname', 'email', 'external_id',
                                    'mobile', 'loyalty_points', 'lifetime_purchases', 'lifetime_points',
                                    'slab', 'registered_store', 'last_trans_value', 'registered_date');
    
    public function __construct(){
        parent::__construct('customer');
    } 
    
    
    public function search($query, $start, $rows)
    {
        try{
            $results = parent::search($query, $start, $rows);
            $items = &$results['results']['item'];

            foreach($items as &$item){
                if(in_array(strtolower($item['external_id']), BaseSearchClient::$null_words) || !(isset($item['external_id']))){
                    $item['external_id'] = '';
                }
                if(in_array(strtolower($item['email']), BaseSearchClient::$null_words) || !(isset($item['email']))){
                    $item['email'] = '';
                }
                if(in_array(strtolower($item['mobile']), BaseSearchClient::$null_words) || !(isset($item['mobile']))){
                    $item['mobile'] = '';
                }
            }

            return $results;
        }catch(Exception $e){
            $this->logger->error("Exception in searching customers: " . $e->getMessage());
            throw $e;
        }     
    }
 


   protected function cleanDocuments(&$documents)
   {
         $cleaned_docs = array();
         foreach($documents as $doc)
         {
             $clean_doc = array();
             $attributes = array();
             foreach($doc as $k=>$v)
             {
                 //move this to a config file
                 $key = $k;                 
                 if(preg_match('/_cf$/', $k)){
                     
                     $key = $this->cleanAttribute($k);
                     $attributes[] = array ('name' => $key, 'value' => $v);
                     
                 }else{
                     $clean_doc[$key] = $v;
                 }
             }
                          
             $clean_doc['attributes'] = array('attribute' => $attributes); 
             $cleaned_docs[] = $clean_doc;
         }
	
         unset($documents);
         return $cleaned_docs;
   }

    /**
     * validates if key passed in is valid key for indexing document
     * @param $key
     * @return bool
     */
    protected function validateKey($key){
       if( in_array( $key, CustomerSearchClient::$valid_keys) )
           return true;
       return false;
   }
   
}

?>