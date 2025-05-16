<?php

include_once('apiHelper/search/BaseSearchClient.php');
include_once('apiHelper/search/SearchResult.php');

/**
 * Search client for the products
 * 
 * @author pigol
 */

class ProductSearchClient extends BaseSearchClient{

    private static $valid_keys = array('pkey', 'id', 'org_id', 'sku', 'price', 'added_on');
    
    public function __construct(){
        parent::__construct('product');
    } 

 
   public function search($params, $start, $rows, $distinct){
       
       try{
 	   $this->logger->info("Input query: $params, distinct: $distinct");	          
           $results = parent::search($params, $start, $rows, $distinct);
           $items = &$results['results']['item'];

           foreach($items as &$item){
               if(in_array(strtolower($item['sku']), BaseSearchClient::$null_words) || !(isset($item['sku']))){
                   $item['sku'] = '';
               }
               if(in_array(strtolower($item['ean']), BaseSearchClient::$null_words) || !(isset($item['ean']))){
                   $item['ean'] = '';
               }
               if(in_array(strtolower($item['description']), BaseSearchClient::$null_words) || !(isset($item['description']))){
                   $item['description'] = '';
               }
           }
           return $results;
       }catch(Exception $e){
           $this->logger->debug("Exception in product search: " . $e->getMessage());
           throw $e;    
       }       
   } 
    
 
   protected function createFacetDocuments(&$documents)
   {
         $cleaned_docs = array();
         $item_ids = array();
         foreach($documents as $doc)
         {
             if(preg_match('/_t$|_s$/', $doc["key"])){
                     
                     $doc["key"] = $this->cleanAttribute($doc["key"]);
             }

             $cleaned_docs[] = $doc;
         }
		 
         unset($documents);
         return $cleaned_docs;
   } 
    
    
   protected function cleanDocuments(&$documents)
   {
   		 $db = new Dbase("product");
         $cleaned_docs = array();
		 $item_ids = array();
         foreach($documents as $doc)
         {
             $clean_doc = array();
             $attributes = array();
             foreach($doc as $k=>$v)
             {
                 //move this to a config file
                 $key = $k;                 
                 if(preg_match('/_t$|_s$/', $k)){
                     
                     $key = $this->cleanAttribute($k);
                     $attributes[] = array ('name' => $key, 'value' => $v);
                     
                 }else{
                 	if($key == "inStock")
                 		$key = "in_stock";
                     $clean_doc[$key] = $v;
                 }
             }
			 if(is_array($clean_doc) && isset($clean_doc['id']) && isset($clean_doc['org_id'])){
			 	$item_ids[] = $clean_doc['id'];
			 }

             $clean_doc['attributes'] = array('attribute' => $attributes); 
             $cleaned_docs[] = $clean_doc;
         }
		 //setting the img_url
		 $item_ids = implode(',', $item_ids);
		 
		 global $currentorg;
		 //if(count($item_ids)>0){ This is causing the failed query! $item_ids is a string and count(str) returns 1
		 if (! empty($item_ids)) {
		 	$sql = "SELECT id,img_url FROM product_management.inventory_masters WHERE id IN ({$item_ids}) AND org_id = {$currentorg->org_id} ";
		 	$result = $db->query_hash($sql, 'id', 'img_url');
		 }
		 else
		 	$result = array();
		 
		 foreach ($cleaned_docs as $key => $cleaned_doc) {
		 	if($result[$cleaned_doc["id"]])
			 	$cleaned_docs[$key]["img_url"] = $result[$cleaned_doc["id"]];
			else
				$cleaned_docs[$key]["img_url"] = '';
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
        if( in_array( $key, ProductSearchClient::$valid_keys) )
            return true;
        return false;
    }
   
}

?>
