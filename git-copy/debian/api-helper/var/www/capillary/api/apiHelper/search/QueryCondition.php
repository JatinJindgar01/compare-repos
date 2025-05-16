<?php

/**
 * Class for representing the individual
 * query condition 
 *
 * @author pigol
 */

class QueryCondition{
    
    const CLAUSE_SEPARATOR = ':';
    
    const VALUE_SEPARATOR = ';';
    
    const SOLR_SEPARATOR = ':';
           
    //t stands for text_general	
    const SOLR_STRING_DYNAMIC_SUFFIX = '_t';
// removed since not used anywhere
//    const SOLR_NUMERIC_DYNAMIC_SUFFIX = '_tf';
    
    const SOLR_CUSTOM_FIELD_SUFFIX = '_cf';
    
    public static $valid_operators = array('IN', 'RANGE', 'STARTS', 'ENDS', 'EQUALS', 'GREATER', 'LESS', 'EXACT', 'ON'); 
    
    public static $valid_attribute_types = array('NUMERIC', 'STRING', 'DATE');
    
    private $attribute;
    
    private $operator;
    
    private $value;
    
    private $values = array();
    
    private $is_multi_valued = false;
    
    private $is_dynamic = false;
    
    private $is_custom_field = false;
    
    private $logger;
    
    private $attribute_type = "STRING";
    
    public function __construct($condition_string){
        
       global $logger;
       $this->logger = $logger;
       
       if(strlen($condition_string) == 0){
           $this->logger->error("Empty condition string");
           throw new Exception("Empty condition string passed");
       }
       
       $this->buildCondition($condition_string); 
    }

    //populates condition object
    private function buildCondition($condition_string)
    {
        $cleaned_condition_string = rtrim(ltrim($condition_string, '('), ')');
        $this->logger->info("After char cleaning string: $cleaned_condition_string");
        
        $parts = StringUtils::strexplode(self::CLAUSE_SEPARATOR, $cleaned_condition_string);
        if(count($parts) != 3){
            $this->logger->error("Incorrect condition string: $condition_string");
            throw new Exception("Incorrect condition string: $cleaned_condition_string");           
        }
        
        $this->attribute = strtolower($parts[0]);	      
        $this->operator = $parts[1];
        
        if(!in_array($this->operator, self::$valid_operators)){
            $this->logger->error("Incorrect operator: $this->operator");
            throw new Exception("Incorrect operator: $this->operator");
        }
        
        if($this->operator === 'RANGE' || $this->operator === 'IN'){
            $this->logger->info("Multi valued operator: $this->operator");
            $this->values = StringUtils::strexplode(self::VALUE_SEPARATOR, $parts[2]);
            $this->is_multi_valued = true;
        }else{
            $this->value = $parts[2];
            $this->values = array();
            $this->is_multi_valued = false;            
        }        
    }
    
    
    public function getOperator(){
        return $this->operator;
    }
    
    
    public function getAttribute(){
        return $this->attribute;
    }
    
    
    public function getValues(){
        if($this->is_multi_valued){
            return $this->values;
        }else{
            return $this->value;
        }
    }
    
    public function isMultiValued(){
        return $this->is_multi_valued;
    }
    
    //TODO add type checking for integer columns etc
    public function buildConditionString()
    {
        $condition_string = "";
        $solr_attribute = $this->attribute;
        
        if($this->is_dynamic)
        {
//            $solr_attribute = ($this->getAttributeType() === 'STRING') ? $solr_attribute.self::SOLR_STRING_DYNAMIC_SUFFIX : $solr_attribute.self::SOLR_NUMERIC_DYNAMIC_SUFFIX;
            $solr_attribute = $solr_attribute.self::SOLR_STRING_DYNAMIC_SUFFIX;
        }

        if($this->is_custom_field)
        {
            $solr_attribute = $solr_attribute . self::SOLR_CUSTOM_FIELD_SUFFIX ;
        }
        
        if($this->attribute_type == 'DATE')
        {
            if($this->operator == 'RANGE')
            {
                foreach($this->values as &$v)
                {
                    $v = DateUtil::getDateTimeForSolr($v);
                }
            }
            
            else if($this->operator == 'ON')
            {
                $this->logger->debug("Setting operator to RANGE");
                $this->operator = 'RANGE';
                $this->is_multi_valued = true;
                
                $this->values[] = DateUtil::getDayStartDateTimeForSolr($this->value);
                $this->values[] = DateUtil::getDayEndDateTimeForSolr($this->value);
            }
            
            else 
            {
                $this->value = DateUtil::getDateTimeForSolr($this->value);
            }
        }
        
        if($this->operator == 'RANGE'){
            
            $start = $this->values[0];
            $end = $this->values[1];
            $quote = '';
            if($this->isStringAttribute()){
                $quote = "'";
            }
            $condition_string = $solr_attribute . self::SOLR_SEPARATOR . "[$quote$start$quote TO $quote$end$quote]";

        }else if($this->operator == 'IN'){

            $values_string = "";
            foreach($this->values as $v){
                $values_string .= "$v ";
            }
            $values_string = rtrim($values_string, ' ');
            $condition_string = $solr_attribute . self::SOLR_SEPARATOR . "($values_string)";

        }else{

            switch($this->operator)
            {
                case 'EQUALS' :
                    $condition_string  = $solr_attribute . self::SOLR_SEPARATOR . $this->value;
                    break;
 
                case 'EXACT' :
                    $condition_string  = $solr_attribute . self::SOLR_SEPARATOR . '"'. $this->value . '"';
                    break;
                 
                case 'GREATER' :
                    $condition_string = $solr_attribute . self::SOLR_SEPARATOR  . "[$this->value TO *]";
                    break;                    
                    
                case 'LESS' :
                    $condition_string = $solr_attribute . self::SOLR_SEPARATOR . "[* TO $this->value]";
                    break;

                case 'STARTS' :
                    $condition_string = $solr_attribute . self::SOLR_SEPARATOR . "$this->value*";                    
                    break;
                    
                case 'ENDS' :
                    $condition_string = $solr_attribute . self::SOLR_SEPARATOR . "*$this->value";    
                    break;
                
                default : 
                    $this->logger->error("Shouldn't reach here");
                    $condition_string = "";
                    
            }
        }    
        
        $this->logger->info("Returning the condition string: $condition_string");
        return $condition_string;    
    }    
    
    
    public function getAttributeType(){
        return $this->attribute_type;
    }
    
    public function isStringAttribute()
    {
    	return ($this->getAttributeType() === 'STRING');
    }    	

    public function setAttributeType($type){
        
        if(!in_array($type, self::$valid_attribute_types)){
            $this->logger->error("Invalid attribute type : $type..setting default to string");
            $this->attribute_type = 'STRING';
        }
        
        $this->attribute_type = $type;
    }
        
    public function setDynamicField(){
        $this->is_dynamic = true;
    }
    
    public function setCustomField(){
        $this->is_custom_field = true;
    }
    
}

?>
