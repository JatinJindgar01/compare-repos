<?php

/**
 * Class for generating the Solr query
 * from the intermediate query syntax
 * 
 * Would be subclassed by the product/customer
 * for now.
 * 
 * @author pigol
 */

include_once 'apiHelper/search/QueryCondition.php';    


abstract class BaseQueryBuilder{

    public static $QUERY_TOKENIZERS = array('|' => ' AND ', '$' => ' OR ', '(' => ' ( ', ')' => ' ) ');// |->AND, #->OR

    const CONDITION_SEPARATOR = '|';
    
    protected $raw_query;
    
    protected $query;
    
    //contains the individual query conditions
    protected $query_conditions = array();

    protected $query_solrq_map = array();
    
    protected $logger;
    
    protected $org_id;

    public static $numeric_operators = array('RANGE', 'LESS', 'GREATER', 'IN', 'EQUALS');
    
    public static $string_operators = array('IN', 'EXACT', 'STARTS', 'ENDS', 'EQUALS');
    
    public static $date_operators = array('RANGE', 'ON', 'LESS', 'GREATER');

    public static $PRIMARY_SEARCH = 'KEY';
    
    public function __construct($query){
                
        global $logger, $currentorg;
        
        $this->logger = $logger;
        $this->raw_query = $query;
        $this->query = trim(urldecode($this->raw_query)); //stripping whitespaces along with urldecode
        $this->logger->info("Input Query: $this->raw_query\n Decoded Query: $this->query");
        $this->org_id = $currentorg->org_id;        
    }

    /**
     * tokenize input query around (, ), |, # characters
     * returns array of conditions by tokenizing query. eg: (C1|C2)#C3#C4 => array[C1,C2,C3,C4]
     */
    protected  function tokenize()    {
        $conditions = array();
        $start = $end = -1;
        for ($i = 0; $i < strlen($this->query); $i++) {
            if ($start >= 0) {
                if (BaseQueryBuilder::isToken($this->query[$i])) {
                    $end = $i;
                    $curr_str = substr($this->query, $start, $end - $start);
                    $curr_str = trim($curr_str);
                    array_push($conditions, $curr_str);
                    $start = $end = -1;
                }
            } else if (!BaseQueryBuilder::isToken($this->query[$i])) {
                $start = $i;
            }
        }
        return $conditions;
    }

    /**
     * @param $char
     * @return bool - TRUE in case passed character is in tokenizer list else false
     */
    private static function isToken($char)
    {
        $result = false;
        foreach (BaseQueryBuilder::$QUERY_TOKENIZERS as $token => $val)
            $result = $result || ($char == $token);
        return $result;
    }
    
    protected function parseQuery($is_primary = false)
    {
        $conditions_array = $this->tokenize();
        $this->logger->debug("Conditions Array: " . print_r($conditions_array, true));

        if($is_primary)
            $conditions_array = $this->processSearchById($conditions_array);


        foreach ($conditions_array as $cond){
            try{
                
                $condition_class = new QueryCondition($cond);
                $this->setAttributeType($condition_class);
                $this->setDynamicFieldType($condition_class);           
                $this->setCustomFieldType($condition_class);
                $this->query_conditions[$cond] = $condition_class;
            }catch(Exception $e){
                $this->logger->error("Error in parsing condition");
                throw new Exception($e->getMessage());
            }
        }        
    }

    private function processSearchById($conditions)
    {
        $result_conditions = array();
        foreach($conditions as $condition){
            $condition = trim($condition);
            if(StringUtils::strriposition($condition, BaseQueryBuilder::$PRIMARY_SEARCH) === 0){
                foreach( $this->getIds() as $id){
                    array_push($result_conditions, str_ireplace(BaseQueryBuilder::$PRIMARY_SEARCH, $id, $condition));
                }
            }
        }

        $this->logger->debug('Parsed result conditions : ' . print_r($result_conditions, true));
        return $result_conditions;
    }

    /**
     * @return mixed - list of primary identifiers based on query builder implementation. eg for product it will
     * return id, sku
     */
    protected abstract function getIds();
    
    /**
     * Validates the individual conditions for the attributes
     * and the value types that have been passed
     * 
     * Returns true or throws an exception with 
     * proper error message
     * 
     * @author pigol
     */
    
    protected abstract function validateConditions();
    
    protected abstract function setAttributeType(QueryCondition &$condition);
    
    protected abstract function setDynamicFieldType(QueryCondition &$condition);
    
    protected abstract function setCustomFieldType(QueryCondition &$condition);
    
    public function buildSearchQuery($is_primary = false)
    {
       try{
           $this->validateConditions();
       }catch(Exception $e){
           $this->logger->error("Error in validating the query conditions: " . $e->getMessage());
           throw $e;
       }


       
       foreach($this->query_conditions as $basic_query => $condition)
       {
           if(is_a($condition, 'QueryCondition')){
               
               $condition_string = $condition->buildConditionString();               
               /**
                * Our default operator is AND

               $query_string .= $condition_string . " AND ";  */
               $this->query_solrq_map[$basic_query] = $condition_string;
                                                      
           }else{
               $this->logger->error("This shouldn't have happened");
           }
       }

       $query_string = $this->transformToSolrQuery($is_primary);
       $query_string = "( $query_string )";
       $this->logger->info("The query string is: $query_string");
       return $query_string;
    }

    private function transformToSolrQuery($is_primary = false){
        $solr_query = '';
        if($is_primary === false) {
            $solr_query = $this->query;

            foreach ($this->query_solrq_map as $q => $solr_q) {
                $solr_query = str_replace($q, $solr_q, $solr_query);
            }

            $solr_query = str_replace('|', ' AND ', $solr_query);
            $solr_query = str_replace('$', ' OR ', $solr_query);

        } else {
            foreach($this->query_solrq_map as $q => $solr_q){
                $solr_query .= "$solr_q OR ";
            }
            $solr_query = rtrim($solr_query, 'OR ');
        }
        $this->logger->debug('Transformed solr query : ' . $solr_query);
        return $solr_query;
    }
}





?>
