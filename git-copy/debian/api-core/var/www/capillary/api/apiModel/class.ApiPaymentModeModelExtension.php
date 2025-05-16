<?php
include_once 'apiModel/class.ApiPaymentMode.php';
class ApiPaymentModeModelExtension extends ApiPaymentModeModel
{
	function savePaymentModeDetails( $org_id, $ref_id, $ref_type, 
			$org_payment_mode_id, $payment_mode_id,
			$amount, $added_by, $notes = '')
	{
		$notes = Util::mysqlEscapeString($notes);
		$sql = "INSERT INTO payment_mode_details
					( org_id, ref_id, ref_type,	
					org_payment_mode_id, payment_mode_id,
					amount, notes, added_by, added_on )
				VALUES( $org_id, $ref_id, '$ref_type', 
						$org_payment_mode_id, $payment_mode_id,
						$amount, '$notes', $added_by, NOW() ) ";
		
		$id = $this->users_db->insert($sql);
		return $id; 
	}
	
	function savePaymentModeAttributes( $org_id, $org_payment_mode_id, $payment_mode_id,
				 $payment_mode_details_id, $added_by, $attributes)
	{
		$sql = "INSERT INTO payment_mode_attribute_values (
					org_id, org_payment_mode_id, org_payment_mode_attribute_id,
					payment_mode_id, payment_mode_attribute_id,
					value, payment_mode_details_id, added_by, added_on ) ";
		$arr_values = array();
		foreach($attributes AS $attribute)
		{
			$value = $attribute['value'];
			$org_payment_mode_attribute_id = $attribute['org_payment_mode_attribute_id'];
			$payment_mode_attribute_id = $attribute['payment_mode_attribute_id'];
			$safe_value = Util::mysqlEscapeString($value);
			$arr_values[] = " ( $org_id, $org_payment_mode_id, $org_payment_mode_attribute_id, 
								$payment_mode_id, $payment_mode_attribute_id,
								'$safe_value', $payment_mode_details_id, $added_by, NOW() ) ";
		}
		
		$str_values = implode(",", $arr_values);
		$sql = $sql ." VALUES " . $str_values;
		$last_inserted_id = $this->users_db->insert($sql);
		
		return $last_inserted_id;
	}
	
	function getPaymentModeFromLabel($org_id, $label, $is_valid = true)
	{
		$safe_label = Util::mysqlEscapeString($label);
		$sql = "SELECT opm.id, opm.label, opm.payment_mode_id, pm.type, pm.description,
					opm.is_valid AS opm_is_valid, pm.is_valid AS pm_is_valid
					FROM org_payment_modes AS opm
					JOIN payment_mode AS pm
						ON opm.payment_mode_id = pm.id
					WHERE opm.org_id = $org_id
						AND opm.label = '$safe_label'";
		
		
		$cache_key = 'o'.$org_id.'_'.CacheKeysPrefix::$orgPaymentModeKey.$org_id.'_'.strtolower($label);
		$ttl = CacheKeysTTL::$orgPaymentModeKey;
		
		$mem_cache_manager = MemcacheMgr::getInstance();
		try{
		
			$json_result = $mem_cache_manager->get( $cache_key );
		
			$result = json_decode( $json_result, true );
		
		}catch( Exception $e )
		{
			$this->logger->debug("couldn't find CacheKey: $cache_key going for db retrival");
			try{
		
				$result = $this->database->query_firstrow($sql);
					
				//set to mem cache
				if( !$result  ){
		
					throw new Exception( "Not Caching as Payment mode does not exists for label : $label" );
				}
		
				$json_result = json_encode( $result );
				$mem_cache_manager->set( $cache_key, $json_result, $ttl );
		
			}catch( Exception $e ){
		
				$this->logger->error("Error while setting the key : $cache_key => ".$e->getMessage());
			}
		}
		//moved is_active validation in code rather than in SQL, to maintain memcache entry
		if($is_valid && !($result['opm_is_valid'] && $result['pm_is_valid']))
		{
			$this->logger->debug("payment_mode '$label' is not valid, not returning any payment mode");
			return array();
		}
		
		return $result; 
	}
	
	function getPaymentModeAttributes($org_id, $payment_mode_id)
	{
		$sql = "SELECT  opma.label, pma.name,
						opma.id AS org_payment_mode_attribute_id,
						opma.payment_mode_attribute_id,
						opm.id AS org_payment_mode_id,
						opm.payment_mode_id,
						opma.org_id
					FROM payment_mode_attributes AS pma 
					JOIN org_payment_mode_attributes AS opma
						ON opma.payment_mode_attribute_id = pma.id    
					JOIN org_payment_modes AS opm
						ON opm.org_id = opma.org_id
						AND opm.payment_mode_id = pma.payment_mode_id
				WHERE opma.org_id = $org_id 
					AND pma.payment_mode_id = $payment_mode_id
					AND pma.is_valid = TRUE
					AND opma.is_valid = TRUE
					AND opm.is_valid = TRUE";
		
		$cache_key = 'o'.$org_id.'_'.CacheKeysPrefix::$orgPaymentModeAttributeKey.$org_id.'_'.$payment_mode_id;
		$ttl = CacheKeysTTL::$orgPaymentModeAttributeKey;
		
		$mem_cache_manager = MemcacheMgr::getInstance();
		try{
		
			$json_result = $mem_cache_manager->get( $cache_key );
		
			$result = json_decode( $json_result, true );
		
		}catch( Exception $e )
		{
			$this->logger->debug("couldn't find CacheKey: $cache_key going for db retrival");
			try{
		
				$values = array('label', 'name', 'org_payment_mode_attribute_id', 'payment_mode_attribute_id',
								'org_payment_mode_id', 'payment_mode_id', 'org_id');
				$result = $this->database->query_hash($sql, 'label', $values);
					
				//set to mem cache
				if( !$result  ){
		
					throw new Exception( 
							"Not Caching as Payment mode attribute does not exists for payment_id : $payment_mode_id" );
				}
		
				$json_result = json_encode( $result );
				$mem_cache_manager->set( $cache_key, $json_result, $ttl );
		
			}catch( Exception $e ){
		
				$this->logger->error("Error while setting the key : $cache_key => ".$e->getMessage());
			}
		}
		return $result;
	}
	
	public function getAllPaymentModeForOrg( $org_id )
	{
		$sql = "SELECT opm.id, opm.label, opm.payment_mode_id, pm.type, pm.description,
					opm.is_valid AS opm_is_valid, pm.is_valid AS pm_is_valid
					FROM org_payment_modes AS opm
					JOIN payment_mode AS pm
						ON opm.payment_mode_id = pm.id
					WHERE opm.org_id = $org_id";
		
		$cache_key = 'o'.$org_id.'_'.CacheKeysPrefix::$orgPaymentModeKey.$org_id;
		$ttl = CacheKeysTTL::$orgPaymentModeKey;
		
		$mem_cache_manager = MemcacheMgr::getInstance();
		try{
		
			$json_result = $mem_cache_manager->get( $cache_key );
		
			$result = json_decode( $json_result, true );
		
		}catch( Exception $e )
		{
			$this->logger->debug("couldn't find CacheKey: $cache_key going for db retrival");
			try{
		
				$result = $this->database->query($sql);
					
				//set to mem cache
				if( !$result  ){
		
					throw new Exception( "Not Caching as Payment mode does not exists for org id: $org_id" );
				}
		
				$json_result = json_encode( $result );
				$mem_cache_manager->set( $cache_key, $json_result, $ttl );
		
			}catch( Exception $e ){
		
				$this->logger->error("Error while setting the key : $cache_key => ".$e->getMessage());
			}
		}
		
		return $result;
	}
	
	function getAllPaymentModeAttributesForOrg($org_id)
	{
		$sql = "SELECT  opma.label, pma.name,
					opma.id AS org_payment_mode_attribute_id,
					opma.payment_mode_attribute_id,
					opm.id AS org_payment_mode_id,
					opm.payment_mode_id,
					opma.org_id,
					pma.is_valid AS pma_is_valid,
					opma.is_valid AS opma_is_valid,
					opm.is_valid AS opm_is_valid,
					pm.is_valid AS pm_is_valid
				FROM payment_mode_attributes AS pma
				JOIN org_payment_mode_attributes AS opma
					ON opma.payment_mode_attribute_id = pma.id
				JOIN org_payment_modes AS opm
					ON opm.org_id = opma.org_id
					AND opm.payment_mode_id = pma.payment_mode_id
				JOIN payment_mode AS pm 
					ON pm.id = pma.payment_mode_id
				WHERE opma.org_id = $org_id";
	
		$cache_key = 'o'.$org_id.'_'.CacheKeysPrefix::$orgPaymentModeAttributeKey.$org_id;
		$ttl = CacheKeysTTL::$orgPaymentModeAttributeKey;
	
		$mem_cache_manager = MemcacheMgr::getInstance();
		try{
	
			$json_result = $mem_cache_manager->get( $cache_key );
		
			$result = json_decode( $json_result, true );
	
		}catch( Exception $e )
		{
			$this->logger->debug("couldn't find CacheKey: $cache_key going for db retrival");
			try{
		
			$result = $this->database->query($sql);
			//set to mem cache
			if( !$result  ){
	
				throw new Exception(
					"Not Caching as Payment mode attribute does not exists for payment_id : $payment_mode_id" );
			}
	
			$json_result = json_encode( $result );
			$mem_cache_manager->set( $cache_key, $json_result, $ttl );
	
			}catch( Exception $e ){
	
				$this->logger->error("Error while setting the key : $cache_key => ".$e->getMessage());
			}
		}
		return $result;
	}
}
?>
