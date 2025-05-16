<?php

/**
 * Class for building the customer search solr query
 * 
 * @author pigol
 */

//TODO: referes to cheetah
include_once('helper/CustomFields.php');


class CustomerQueryBuilder extends BaseQueryBuilder{
    
    private $cf;
    
    private $attribute_map;
        
    public function __construct($query, $is_primary = false){
        
        global $currentorg;
        try{
            parent::__construct($query);
            $this->cf = new CustomFields();
            $this->attribute_map = array();
            $this->loadAttributeMap();            
	        $this->parseQuery($is_primary);
        }catch(Exception $e){
            $this->logger->error("Error in query");
            throw $e;
        }        
    }
    
    /**
     * Loads the custom fields of the
     * loyalty_registration scope only
     * for now
     */    
    private function loadCustomFields()
    {
        $fields = $this->cf->getCustomFieldsByScope($this->org_id, LOYALTY_CUSTOM_REGISTRATION);        
        $fields_array = array();    
        if(count($fields) > 0){    
            foreach($fields as $f){            
                $fields_array[$f['name']] = array(
                                                    'type' => 'STRING',
                                                    'is_custom_field' => true
                                                 );
            }
        
        }
        $this->logger->debug("Set of custom fields: " . print_r($fields_array, true));
        return $fields_array;
    }
    
        
    //TODO move this to memcached
    private function loadAttributeMap()
    {
        $this->attribute_map = $this->loadCustomFields();        
/**
<add>
<doc>
  <field name="user_id">1231121</field>  
  <field name="firstname">Piyush</field>
  <field name="lastname">Goel</field>
  <field name="org_id">29</field>
  <field name="mobile">919980616752</field>  
  <field name="email">goel.piyush84@gmail.com</field>
  <field name="external_id">ABBC3333333</field>
  <field name="registered_date">2012-05-06T12:34:44Z</field>
  <field name="loyalty_points">1000</field>  
  <field name="lifetime_points">345</field>
  <field name="lifetime_purchases">45000</field>
  <field name="slab">GOLD</field>
  <field name="registered_store">pe.mgroad.blr</field>
  <field name="last_trans_value">31000</field>
  <field name="age_cf">21</field>
  <field name="fav_sport_cf">Soccer, Tennis, Cricket</field>
</doc>
</add>
**/

        $fixed_attributes = array(                                    
                                    'user_id' => array('type' => 'NUMERIC', 'is_dynamic' => false),
        							'org_id' => array('type' => 'NUMERIC', 'is_dynamic' => false),                                    
                                    'firstname' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'lastname' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'email' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'external_id' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'mobile' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'loyalty_points' => array('type'=> 'NUMERIC', 'is_dynamic' => false),
                                    'lifetime_purchases' => array('type'=>'NUMERIC', 'is_dynamic'=>false),
                                    'lifetime_points' => array('type'=>'NUMERIC', 'is_dynamic'=>false),
                                    'slab' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'registered_store' => array('type' => 'STRING', 'is_dynamic' => false),
                                    'last_trans_value' => array('type'=>'NUMERIC', 'is_dynamic'=>false),
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
    
    
    public function setCustomFieldType(QueryCondition &$condition)
    {
        $attribute = $condition->getAttribute();
        $is_custom_field = $this->attribute_map[$attribute]['is_custom_field'];
        if($is_custom_field == true){
            $condition->setCustomField();
        }
    }

    /**
     * @return array of primary identifiers for Customer data type
     */
    public function getIds(){
        return array('mobile', 'email', 'external_id');
    }
    
    
}

?>
