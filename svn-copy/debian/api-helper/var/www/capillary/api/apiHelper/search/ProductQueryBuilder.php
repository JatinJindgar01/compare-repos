<?php

/**
 * Class for building the product search solr query
 * 
 * @author pigol
 */

class ProductQueryBuilder extends BaseQueryBuilder{
    
    private $inventory_controller;

    private $attribute_map;
        
    public function getAttributeMap() {
       return $this->attribute_map;
    }
    public function __construct($query, $is_primary){
        
        try{
            parent::__construct($query);
            $this->inventory_controller = new ApiInventoryController('users');
            $this->attribute_map = array();
            $this->loadAttributeMap();            
	        $this->parseQuery($is_primary);
        }catch(Exception $e){
            $this->logger->error("Error in query");
            throw $e;
        }        
    }
    
    
    //TODO move this to memcached
    private function loadAttributeMap()
    {
        $attributes = $this->inventory_controller->getInventoryAttributesForSearch();

        foreach($attributes as $attrib)
        {
            $name = strtolower($attrib['name']);
            $type = in_array($attrib['type'], array('Int', 'Double')) ? 'NUMERIC' : 'STRING';
            $this->attribute_map[$name] = array('type' => $type, 'is_dynamic' => true);                      
        }
        
        $fixed_attributes = array(
                                    'id' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'org_id' => array('type' => 'NUMERIC', 'is_dynamic' => false),
                                    'sku' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'price' => array('type' => 'NUMERIC', 'is_dynamic' => false),
                                    'added_on' => array('type' => 'DATE', 'is_dynamic' => false),
                                    'key' => array('type' => 'STRING', 'is_dynamic' => false)
                                 );
        $this->attribute_map = array_merge($this->attribute_map, $fixed_attributes);                                        
	$this->logger->debug("Attribute map: " . print_r($this->attribute_map, true));
    }
    
    
    /**
     * implementation of the abstract function
     * @see BaseQueryBuilder::validateConditions()
     */
    public function validateConditions()
    {
        
       $valid_attributes = array_keys($this->attribute_map);
	$this->logger->debug("set of valid attributes: " . print_r($valid_attributes, true));

       foreach($this->query_conditions as $condition)
       {           
           $attribute = strtolower($condition->getAttribute());          
           //check if the attribute is actually searcheable or not
           if(!in_array($attribute, $valid_attributes)){
               $this->logger->error("This attribute is not searchable");
               throw new Exception("Invalid search attribute: $attribute");
           }
           
           //check if the operator and attribute type are compatible
           //can't use numeric operators with string attributes and vice-versa           
           $operator = $condition->getOperator();
           $type = $this->attribute_map[$attribute]['type'];
           
           if((!in_array($operator, self::$numeric_operators) && $type == 'NUMERIC') 
              || (!in_array($operator, self::$string_operators) && $type == 'STRING') 
                   || (!in_array($operator, self::$date_operators) && $type == 'DATE') 
              )
              {
                  $this->logger->error("Invalid operator for attribute");
                  throw new Exception("Invalid operator $operator for attribute: $attribute");
              }   
       }                    
    }
    
    
    public function setAttributeType(QueryCondition &$condition)
    {
        $attribute = $condition->getAttribute();
	
	$this->logger->debug("Input attributes: $attribute");

        $type = $this->attribute_map[$attribute]['type'];        

	$this->logger->debug("type: $type");

        $condition->setAttributeType($type);        
    }
    
    
    public function setDynamicFieldType(QueryCondition &$condition){
        
        $attribute = $condition->getAttribute();
        $is_dynamic = $this->attribute_map[$attribute]['is_dynamic'];
        if($is_dynamic){
            $condition->setDynamicField();
        }        
    }    
    
    /**
     * No custom fields for product search
     */
    
    public function setCustomFieldType(QueryCondition &$condition){
        return ;
    }

    public function getIds(){
        return array('sku', 'id');
    }
    
}

?>
