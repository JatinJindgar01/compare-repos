<?php

    require_once "resource.php";

    require_once "apiController/ApiFileController.php";
    /**
     * Store Care summary of organization
     *
     * get: fetches details of tills and store centers
     *
     */

    /**
     * @SWG\Resource(
     *     apiVersion="1.1",
     *     swaggerVersion="1.2",
     *     resourcePath="/store_diagnostics",
     *     basePath="http://{{INTOUCH_ENDPOINT}}/v1.1"
     * )
     */
    class StoreDiagnosticsResource extends BaseResource{

        function __construct()
        {
            parent::__construct();
        }


        public function process( $version, $method, $data, $query_params, $http_method )
        {
            if( !$this->checkVersion( $version ) )
            {
                $this->logger->error( "Unsupported Version : $version" );
                $e = new UnsupportedVersionException( ErrorMessage::$api['UNSUPPORTED_VERSION'], ErrorCodes::$api['UNSUPPORTED_VERSION'] );
                throw $e;
            }

            if( !$this->checkMethod( $method ) ){
                $this->logger->error( "Unsupported Method: $method" );
                $e = new UnsupportedMethodException( ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION'] );
                throw $e;
            }

            $result = array();
            try{

                switch( strtolower( $method ) ){

                    case 'diagnostics_summary':
                        $result = $this->diagnostics_summary( $query_params );
                        break;

                    case 'summary':
                        $result = $this->org_summary( $query_params );
                        break;

                    case 'store_report':
                        $result = $this->reports( $query_params );
                        break;

                    default :
                        $this->logger->error( "Should not be reaching here" );

                }
            }catch( Exception $e ){

                $this->logger->error( "Caught an unexpected exception, Code:" . $e->getCode()
                . " Message: " . $e->getMessage()
                );
                throw $e;
            }

            return $result;
        }
        
        public function org_summary( $query_params )
        {
        	$org_id = $this->currentorg->org_id;
        	
        	$store_controller = new ApiStoreController();
        	
        	// Is the check on query param "filter" really required?
        	if ( isset( $query_params['filter'] ) && ( strtolower($query_params['filter']) == 'zone' ) && isset( $query_params['zone_id'] ) )
        		$zone_id = $query_params['zone_id'];
        	else 
        		$zone_id = 'ALL';
        	
        	/*	// to be used in future 
        	$params = array(
        			'org_id' => $org_id,
        			'filter' => $query_params['filter'],
        			'filter_id' => 
        	); */
        	
        	try{
        		$summary = $store_controller->getSummary( $org_id, $zone_id );
        	} catch ( Exception $e ){
        		$root['status'] = array(
        				'success' => false,
        				'code' => ErrorCodes::$diagnostics['ERR_SUMMARY_GET_FAIL'],
        				'message' => ErrorMessage::$diagnostics['ERR_SUMMARY_GET_FAIL']
        		);
        	}
        	
        	$root['status'] = array(
        							'success' => true,
        							'code' => ErrorCodes::$api['SUCCESS'],
        							'message' => ErrorMessage::$api['SUCCESS']
        						);
        	
        	$root['summary'] = $summary;
        	
        	return $root;
        }
        
        public function diagnostics_summary( $query_params )
        {
        	$statistics = array();
        	$store_controller = new ApiStoreController();
        	$org_id = $this->currentorg->org_id;
        	
        	//set the $params in proper format
        	$params = array(
		        			'org_id' => $org_id,
		        			//'entity_type' => $entity_type,	Set this inside the loop (toggle in each iteration)
		        			'report_type' => strtolower( $query_params['report_type'] ),
		        			'filter' => 'ZONE',
		        			// 'filter_id' => $filter_id,		Set this inside the loop
		        			'start_id' => $query_params['start_id'],
		        			'limit' => $query_params['limit'],
		        			'sort_by' => $query_params['sort_by']
        				);
        	
        	try{
        		$this->logger->debug("Getting zones of current org");
	        	$org_zones = $store_controller->getAllOrgZones( $org_id );
	        	
	        	$this->logger->debug("Fetching distinct sync component types");
	        	$distinct_till_sync_components = $store_controller->getDistinctComponentTypes( 'TILL', 'sync', $org_id );
	        	$distinct_ss_sync_components = $store_controller->getDistinctComponentTypes( 'STR_SERVER', 'sync', $org_id );
	        	
	        	$this->logger->debug("Fetching distinct upload component types");
	        	$distinct_till_upload_components = $store_controller->getDistinctComponentTypes( 'TILL', 'upload', $org_id );
	        	$distinct_ss_upload_components = $store_controller->getDistinctComponentTypes( 'STR_SERVER', 'upload', $org_id );
        	} catch ( Exception $e ) {
        		
        	}
        	
        	$this->logger->debug("Initializing the count for all the component types");
		    $t_count = array(  'completed' => array( 'count' => 0 ),
							   'pending' => array( 'count' => 0 ) 
							);
        	foreach ( $distinct_till_sync_components as $comp )
        	{
        		$i = $comp['sync_type'];
        		
        		$statistics['full_sync_status']['till'][$i] = $t_count;
        		$statistics['delta_sync_status']['till'][$i] = $t_count;
        		
        		$statistics['full_sync_status']['thin_client'][$i] = $t_count;
        		$statistics['delta_sync_status']['thin_client'][$i] = $t_count;
        		
        		/* $statistics['full_sync_status']['till'][$i]['completed']['count'] = 0;
        		$statistics['full_sync_status']['till'][$i]['pending']['count'] = 0;
        		$statistics['delta_sync_status']['till'][$i]['completed']['count'] = 0;
        		$statistics['delta_sync_status']['till'][$i]['pending']['count'] = 0;
        		
        		$statistics['full_sync_status']['thin_client'][$i]['completed']['count'] = 0;
        		$statistics['full_sync_status']['thin_client'][$i]['pending']['count'] = 0;
        		$statistics['delta_sync_status']['thin_client'][$i]['completed']['count'] = 0;
        		$statistics['delta_sync_status']['thin_client'][$i]['pending']['count'] = 0; */
        	}
        	
        	foreach ( $distinct_till_upload_components as $comp )
        	{
        		$m = $comp['upload_type'];
        		
        		$statistics['bulk_upload_status']['till'][$m] = $t_count;
        		$statistics['bulk_upload_status']['thin_client'][$m] = $t_count;
        		
        		/* $statistics['bulk_upload_status']['till'][$m]['completed']['count'] = 0;
        		$statistics['bulk_upload_status']['till'][$m]['pending']['count'] = 0;
        		
        		$statistics['bulk_upload_status']['thin_client'][$m]['completed']['count'] = 0;
        		$statistics['bulk_upload_status']['thin_client'][$m]['pending']['count'] = 0; */
        	}
        	 
        	foreach ( $distinct_ss_sync_components as $comp )
        	{
        		$j = $comp['log_sync_type'];
        		
        		$statistics['full_sync_status']['store_server'][$j] = $t_count;
        		$statistics['delta_sync_status']['store_server'][$j] = $t_count;
        		
	        	/* $statistics['full_sync_status']['store_server'][$j]['completed']['count'] = 0;
	        	$statistics['full_sync_status']['store_server'][$j]['pending']['count'] = 0;
	        	$statistics['delta_sync_status']['store_server'][$j]['completed']['count'] = 0;
	        	$statistics['delta_sync_status']['store_server'][$j]['pending']['count'] = 0; */
        	}
        	
        	foreach ( $distinct_ss_upload_components as $comp )
        	{
        		$n = $comp['upload_type'];
        		
        		$statistics['bulk_upload_status']['store_server'][$n] = $t_count;
        		
        		/* $statistics['bulk_upload_status']['store_server'][$n]['completed']['count'] = 0;
        		$statistics['bulk_upload_status']['store_server'][$n]['pending']['count'] = 0; */
        	}
        	
        	$com_pending = array( 'completed' => 0,	'pending' => 0 );
        	$each_sync = array(	'delta_sync' => $com_pending,
        						'full_sync' => $com_pending,
        						'bulk_upload' => $com_pending
        				);
        	$new_count = array(	'till' => $each_sync,
        						'thin_client' => $each_sync,
        						'store_server' => $each_sync
        				);
        	
        	// Get all till and store_server details in each of these zones and then assimilate the required results
        	$tillDetailsArray = array();
        	$storeServerDetailsArray = array();
        	
        	if ( isset( $query_params['zone_id'] ) )
        	{
        		$this->logger->debug("Fetching diagnostics summary of the zone_id");
        		$params['filter_id']['zone']['id'] = $query_params['zone_id'];
        		
        		$params['entity_type'] = 'till';
        		$tillDetailsArray = $this->getTillReports( $params );
        		
        		$params['entity_type'] = 'store_servers';
        		$storeServerDetailsArray = $this->getStoreServerReports( $params );
        	}

        	else {
        		$this->logger->debug("Zone filtering not required as zone_id isn't passed. Fetching complete diagnostics summary");
        		foreach ( $org_zones as $each_zone )
        		{
        			$params['filter_id']['zone']['id'] = $each_zone;
        			$params['entity_type'] = 'till';
        			$temp_t = $this->getTillReports( $params );
        			$tillDetailsArray = array_merge( $tillDetailsArray, $temp_t );
        			$params['entity_type'] = 'store_servers';
        			$temp_ss = $this->getStoreServerReports( $params );
        			$storeServerDetailsArray = array_merge( $storeServerDetailsArray, $temp_ss );
        		}
        	}
        	
        	$this->logger->debug("Removing duplicate entries because of nesting of zones");
        	$tillDetailsArray = array_map( "unserialize", array_unique( array_map("serialize", $tillDetailsArray) ) );
        	$storeServerDetailsArray = array_map( "unserialize", array_unique( array_map("serialize", $storeServerDetailsArray) ) );
        	
        	$this->logger->debug("Case : till and thin_clients. Determining counts of all the components");
        	foreach ( $tillDetailsArray as $item )
        	{
        		if ( $item['is_thin_client'] == 1 || $item['is_thin_client'] == true)
        		{
        			foreach ( $item['full_sync_status']['component'] as $each_component )
        			{
        				$component_type = $each_component['type'];
        				if ( $each_component['status'] == 'COMPLETED' )
        					$statistics['full_sync_status']['thin_client'][$component_type]['completed']['count'] += 1;
        				else
        					$statistics['full_sync_status']['thin_client'][$component_type]['pending']['count'] += 1;
        			}
        			 
        			foreach ( $item['delta_sync_status']['component'] as $each_component )
        			{
        				$component_type = $each_component['type'];
        				if ( $each_component['status'] == 'COMPLETED' )
        					$statistics['delta_sync_status']['thin_client'][$component_type]['completed']['count'] += 1;
        				else
        					$statistics['delta_sync_status']['thin_client'][$component_type]['pending']['count'] += 1;
        			}
        			
        			foreach ( $item['bulk_upload_status']['component'] as $each_component )
        			{
        				$component_type = $each_component['type'];
        				if ( $each_component['status'] == 'UPLOAD_COMPLETE' )
        					$statistics['bulk_upload_status']['thin_client'][$component_type]['completed']['count'] += 1;
        				else
        					$statistics['bulk_upload_status']['thin_client'][$component_type]['pending']['count'] += 1;
        			}
        			
        			if ( $item['current_till_version'] == $item['available_till_version'] )
        			{
        				if ( ! isset($statistics['client_version']['thin_client']['latest']['count']) )
        					$statistics['client_version']['thin_client']['latest']['count'] = 0;
        				$statistics['client_version']['thin_client']['latest']['count'] += 1;
        			}
        			else 
        			{
        				if ( ! isset($statistics['client_version']['thin_client']['pending']['count']) )
        					$statistics['client_version']['thin_client']['pending']['count'] = 0;
        				$statistics['client_version']['thin_client']['pending']['count'] += 1;
        			}

        			// p_changes start
        			$pending_delta_sync = 0;
        			foreach ( $item['delta_sync_status']['component'] as $vards )
        				if ( $vards['status'] != 'COMPLETED' ){
	        				$pending_delta_sync = 1;
	        				break;
        				}
        			
        			$pending_full_sync = 0;
        			foreach ( $item['full_sync_status']['component'] as $varfs )
        				if ( $varfs['status'] != 'COMPLETED' ) {
        					$pending_full_sync = 1;
        					break;
        				}
        			
        			$pending_bulk_upload = 0;
        			foreach ( $item['bulk_upload_status']['component'] as $varbu )
        				if ( $varbu['status'] != 'COMPLETED' ) {
        					$pending_bulk_upload = 1;
        					break;
        				}
        			
        			if ( $pending_delta_sync == 1 )
        				$new_count['thin_client']['delta_sync']['pending'] += 1;
        			else 
        				$new_count['thin_client']['delta_sync']['completed'] += 1;
        			
        			if ( $pending_full_sync == 1 )
        				$new_count['thin_client']['full_sync']['pending'] += 1;
        			else 
        				$new_count['thin_client']['full_sync']['completed'] += 1;
        			
        			if ( $pending_bulk_upload == 1 )
        				$new_count['thin_client']['bulk_upload']['pending'] += 1;
        			else 
        				$new_count['thin_client']['bulk_upload']['completed'] += 1;
        			// p_change ends
        		}
        		else 
        		{
        			foreach ( $item['full_sync_status']['component'] as $each_component )
        			{
        				$component_type = $each_component['type'];
        				if ( $each_component['status'] == 'COMPLETED' )
        					$statistics['full_sync_status']['till'][$component_type]['completed']['count'] += 1;
        				else
        					$statistics['full_sync_status']['till'][$component_type]['pending']['count'] += 1;
        			}
        			
        			foreach ( $item['delta_sync_status']['component'] as $each_component )
        			{
        				$component_type = $each_component['type'];
        				if ( $each_component['status'] == 'COMPLETED' )
        					$statistics['delta_sync_status']['till'][$component_type]['completed']['count'] += 1;
        				else
        					$statistics['delta_sync_status']['till'][$component_type]['pending']['count'] += 1;
        			}
        			
        			foreach ( $item['bulk_upload_status']['component'] as $each_component )
        			{
        				$component_type = $each_component['type'];
        				if ( $each_component['status'] == 'UPLOAD_COMPLETE' )
        					$statistics['bulk_upload_status']['till'][$component_type]['completed']['count'] += 1;
        				else
        					$statistics['bulk_upload_status']['till'][$component_type]['pending']['count'] += 1;
        			}
        			
        			if ( $item['client_version']['current_till_version'] == $item['client_version']['available_till_version'] )
        			{
        				if ( ! isset($statistics['client_version']['till']['latest']['count']) )
        					$statistics['client_version']['till']['latest']['count'] = 0;
        				$statistics['client_version']['till']['latest']['count'] += 1;
        			}
        			else
        			{
        				if ( ! isset($statistics['client_version']['till']['pending']['count']) )
        					$statistics['client_version']['till']['pending']['count'] = 0;
        				$statistics['client_version']['till']['pending']['count'] += 1;
        			}
        			
        			// p_changes start
        			$pending_delta_sync = 0;
        			foreach ( $item['delta_sync_status']['component'] as $vards )
        				if ( $vards['status'] != 'COMPLETED' ){
        				$pending_delta_sync = 1;
        				break;
        			}
        			 
        			$pending_full_sync = 0;
        			foreach ( $item['full_sync_status']['component'] as $varfs )
        				if ( $varfs['status'] != 'COMPLETED' ) {
        					$pending_full_sync = 1;
        					break;
        				}
        			 
        			$pending_bulk_upload = 0;
        			foreach ( $item['bulk_upload_status']['component'] as $varbu )
        				if ( $varbu['status'] != 'COMPLETED' ) {
        					$pending_bulk_upload = 1;
        					break;
        				}
        			 
        			if ( $pending_delta_sync == 1 )
        				$new_count['till']['delta_sync']['pending'] += 1;
        			else
        				$new_count['till']['delta_sync']['completed'] += 1;
        			 
        			if ( $pending_full_sync == 1 )
        				$new_count['till']['full_sync']['pending'] += 1;
        			else
        				$new_count['till']['full_sync']['completed'] += 1;
        			 
        			if ( $pending_bulk_upload == 1 )
        				$new_count['till']['bulk_upload']['pending'] += 1;
        			else
        				$new_count['till']['bulk_upload']['completed'] += 1;
        			// p_change ends
        		}
        	}
        	
        	$this->logger->debug("Case : store_server. Determining count of each component");
        	foreach ( $storeServerDetailsArray as $item )
        	{
        		foreach ( $item['full_sync_status']['component'] as $each_component )
        		{
        			$component_type = $each_component['type'];
        			if ( $each_component['status'] == 'COMPLETED' )
        			{
        				if ( ! isset($statistics['full_sync_status']['store_server'][$component_type]['completed']['count']) )
        					$statistics['full_sync_status']['store_server'][$component_type]['completed']['count'] = 0;
        				$statistics['full_sync_status']['store_server'][$component_type]['completed']['count'] += 1;
        			}
        			else
        			{
        				if ( ! isset($statistics['full_sync_status']['store_server'][$component_type]['pending']['count']) )
        					$statistics['full_sync_status']['store_server'][$component_type]['pending']['count'] = 0;
        				$statistics['full_sync_status']['store_server'][$component_type]['pending']['count'] += 1;
        			}
        		}
        		
        		foreach ( $item['delta_sync_status']['component'] as $each_component )
        		{
        			$component_type = $each_component['type'];
        			if ( $each_component['status'] == 'COMPLETED' )
        			{
        				if ( ! isset($statistics['delta_sync_status']['store_server'][$component_type]['completed']['count']) )
        					$statistics['delta_sync_status']['store_server'][$component_type]['completed']['count'] = 0;
        				$statistics['delta_sync_status']['store_server'][$component_type]['completed']['count'] += 1;
        			}
        			else 
        			{
        				if ( ! isset($statistics['delta_sync_status']['store_server'][$component_type]['pending']['count']) )
        					$statistics['delta_sync_status']['store_server'][$component_type]['pending']['count'] = 0;
        				$statistics['delta_sync_status']['store_server'][$component_type]['pending']['count'] += 1;
        			}
        		}
        		
        		foreach ( $item['bulk_upload_status']['component'] as $each_component )
        		{
        			$component_type = $each_component['type'];
        			if ( $each_component['status'] == 'UPLOAD_COMPLETE' )
        			{
        				if ( ! isset($statistics['bulk_upload_status']['store_server'][$component_type]['completed']['count']) )
        					$statistics['bulk_upload_status']['store_server'][$component_type]['completed']['count'] = 0;
        				$statistics['bulk_upload_status']['store_server'][$component_type]['completed']['count'] += 1;
        			}
        			else 
        			{
        				if ( ! isset($statistics['bulk_upload_status']['store_server'][$component_type]['pending']['count']) )
        					$statistics['bulk_upload_status']['store_server'][$component_type]['pending']['count'] = 0;
        				$statistics['bulk_upload_status']['store_server'][$component_type]['pending']['count'] += 1;
        			}
        		}
        		
        		if ( $item['client_version']['current_store_server_version'] == $item['client_version']['available_store_server_version'] )
        		{
        			if ( ! isset($statistics['client_version']['store_server']['latest']['count']) )
        				$statistics['client_version']['store_server']['latest']['count'] = 0;
        			$statistics['client_version']['store_server']['latest']['count'] += 1;
        		}
        		else 
        		{
        			if ( ! isset($statistics['client_version']['store_server']['pending']['count']) )
        				$statistics['client_version']['store_server']['pending']['count'] = 0;
        			$statistics['client_version']['store_server']['pending']['count'] += 1;
        		}
        		
        		// p_changes start
        		$pending_delta_sync = 0;
        		foreach ( $item['delta_sync_status']['component'] as $vards )
        			if ( $vards['status'] != 'COMPLETED' ){
        			$pending_delta_sync = 1;
        			break;
        		}
        		
        		$pending_full_sync = 0;
        		foreach ( $item['full_sync_status']['component'] as $varfs )
        			if ( $varfs['status'] != 'COMPLETED' ) {
        				$pending_full_sync = 1;
        				break;
        			}
        		
        		$pending_bulk_upload = 0;
        		foreach ( $item['bulk_upload_status']['component'] as $varbu )
        			if ( $varbu['status'] != 'COMPLETED' ) {
        				$pending_bulk_upload = 1;
        				break;
        			}
        		
        		if ( $pending_delta_sync == 1 )
        			$new_count['store_server']['delta_sync']['pending'] += 1;
        		else
        			$new_count['store_server']['delta_sync']['completed'] += 1;
        		
        		if ( $pending_full_sync == 1 )
        			$new_count['store_server']['full_sync']['pending'] += 1;
        		else
        			$new_count['store_server']['full_sync']['completed'] += 1;
        		
        		if ( $pending_bulk_upload == 1 )
        			$new_count['store_server']['bulk_upload']['pending'] += 1;
        		else
        			$new_count['store_server']['bulk_upload']['completed'] += 1;
        		// p_change ends
        	}
        	
        	$stats = array();
        	foreach ( $statistics as $bus => $body )
        	{
        		if ( $bus == 'client_version' )
        		{
        			foreach ( $statistics['client_version'] as $ent => $rest )
        			{
        				foreach ( $rest as $st => $count )
        					$stats['client_version'][$ent]['status'][] = array(	'status' => $st,
        																		'count' => $count['count']
        																	);
        			}
        		}
        		else
        		{
        			foreach ( $body as $entity => $component )
        			{
        				foreach ( $component as $type => $status )
        				{
        					foreach ( $status as $i => $count )
        						$stats[$bus][$entity]['component_status'][] = array(	'type' => $type,
        																	'status' => $i,
        																	'count' => $count['count']
        																);
        				}
        			}
        		}
        		
        	}
        	
        	// p_change starts
        	$sync_statuses = array( 'full_sync', 'delta_sync', 'bulk_upload' );
        	$entities = array( 'till', 'thin_client', 'store_server' );
        	
        	foreach ( $sync_statuses as $each_sync_status )
        	{
        		foreach ( $entities as $each_entity )
        		{
        			$stats[$each_sync_status.'_status'][$each_entity]['status'][] = array( 'status' => 'COMPLETED',
        																				   'count' => $new_count[$each_entity][$sync_statuses]['completed']
        																				);
        			$stats[$each_sync_status.'_status'][$each_entity]['status'][] = array( 'status' => 'PENDING',
													        							   'count' => $new_count[$each_entity][$sync_statuses]['pending']
													        							);
        		}
        	}
        	// p_change ends
        	
        	$root['status'] = array(
        							'success' => true,
        							'code' => ErrorCodes::$api['SUCCESS'],
        							'message' => ErrorMessage::$api['SUCCESS']
        						);
        	
        	$root['statistics'] = $stats;
        	
        	return $root;
        }
        
        
        public function reports ( $query_params )
        {
        	$org_id = $this->currentorg->org_id;
        	$entity_type = strtolower( $query_params['type'] );
        	
        	$type_set = array( "full_sync", "delta_sync", "bulk_upload" );
        	$query_params['report_type'] = strtolower( $query_params['report_type'] );
        	if ( isset( $query_params['report_type'] ) && in_array( $query_params['report_type'], $type_set ) )
        		$report_type = strtolower( $query_params['report_type']);
        	else 
        		$report_type = "all";
        	
        	$filter = strtolower( $query_params['filter'] );
        	
        	$filter_id = array (
        					'zone' => array( 'id' => $query_params['zone_id'], 'code' => $query_params['zone_code'] ),
        					'concept' => array( 'id' => $query_params['concept_id'], 'code' => $query_params['concept_code'] ),
        					'store' => array( 'id' => $query_params['store_id'], 'code' => $query_params['store_code'] ),
        					'store_server' => array( 'id' => $query_params['store_server_id'], 'code' => $query_params['store_server_code'] )
        					);
        	
        	$params = array(
        					'org_id' => $org_id,
        					'entity_type' => $entity_type,
        					'report_type' => $report_type,
        					'filter' => $filter,
        					'filter_id' => $filter_id,
        					//'start_id' => $query_params['start_id'],
        					//'limit' => $query_params['limit'],
        					'sort_by' => $query_params['sort_by']
        				);
        	
        	if ( isset( $query_params['start_id'] ) )
        		$params['start_id'] = $query_params['start_id'];
        	else 
        		$params['start_id'] = 0;
        	
        	if ( isset( $query_params['limit'] ) )
        		$params['limit'] = $query_params['limit'];
        	else 
        		$params['limit'] = 200;
        	
        	/* if ( isset( $query_params['sort_by'] ) )
        		$params['sort_by'] = $query_params['sort_by'];
        	else 
        		$params['sort_by'] = 'till_id'; */
        	
        	switch ( $entity_type )
        	{
        		case 'till' :
        		case 'tills' :
        			$result = $this->getTillReports( $params );
        			break;
        			
        		case 'store_server' :
        		case 'store_servers' :
        			$result = $this->getStoreServerReports( $params );
        			break;
        			
        		default :
        			// handle this case properly. Remove the return statement
        			$result['tl'] = $this->getTillReports( $params );
        			$result['sc'] = $this->getStoreServerReports( $params );
        	}
        	
        	$root['status'] = array(
        							'success' => true,
        							'code' => ErrorCodes::$api['SUCCESS'],
        							'message' => ErrorMessage::$api['SUCCESS']
        						);
        	
        	if ( $entity_type == 'till' || $entity_type == 'tills' )
        		$root['tills']['till'] = $result;
        	else if ( $entity_type == 'store_server' || $entity_type == 'store_servers' )
        		$root['store_servers']['store_server'] = $result;
        	else 
        	{
        		$root['tills']['till'] = $result['tl'];
        		$root['store_servers']['store_server'] = $result['sc'];
        	}
        	
        	return $root;
        }
        
        private function getTillReports( $params )
        {
        	$store_controller = new ApiStoreController();
        	
        	switch ( $params['filter'] )
        	{
        		case 'zone' :
        		case 'ZONE' :
        			if ( empty($params['filter_id']['zone']['id']) && empty($params['filter_id']['zone']['code']) )
        				// return a proper error message. REMOVE the following comment
        				return false;
        				
        			$tills = $store_controller->getTillsInZone( $params );
        			break;
        				
        		case 'concept' :
        		case 'CONCEPT' :
        			if ( empty($params['filter_id']['concept']['id']) && empty($params['filter_id']['concept']['code']) )
        				// return a proper error message. REMOVE the following comment
        				return false;
        			
        			$tills = $store_controller->getTillsInConcept( $params );
        			break;
        			
        		case 'store' :
        		case 'STORE' :
        			if ( empty($params['filter_id']['store']['id']) && empty($params['filter_id']['store']['code']) )
        				// return a proper error message. REMOVE the following comment
        				return false;
        				
        			$tills = $store_controller->getTillsInStore( $params );
        			break;
        				
        		case 'store_server' :
        		case 'STORE_SERVER' :
        			if ( empty($params['filter_id']['store_server']['id']) && empty($params['filter_id']['store_server']['code']) )
        				// return a proper error message. REMOVE the following comment
        				return false;
        				
        			$tills = $store_controller->getTillsInStoreServer( $params );
        			break;
        			
        		default:
        			$tills = $store_controller->getAllTills( $params );
        	}
        	
        	$unique_tills = array_unique( $tills );		// remove duplicate entries of tills
        	
        	// fetch the array of till details
        	$till_details = $store_controller->getTillDetails( $unique_tills, $params );
        	
        	// make sure the $result is properly formatted before returning
        	return $till_details;
        }
        
        private function getStoreServerReports( $params )
        {
        	$store_controller = new ApiStoreController();
        	 
        	switch ( $params['filter'] )
        	{
        		case 'zone' :
        		case 'ZONE' :
        			if ( empty($params['filter_id']['zone']['id']) && empty($params['filter_id']['zone']['code']) )
        				// return a proper error message. REMOVE the following comment
        				return false;
        			
        			$store_servers = $store_controller->getStoreServersInZone( $params );
        			break;
        			
    			case 'concept' :
    			case 'CONCEPT' :
    				if ( empty($params['filter_id']['concept']['id']) && empty($params['filter_id']['concept']['code']) )
    					// return a proper error message. REMOVE the following comment
    					return false;
					 
					$store_servers = $store_controller->getStoreServersInConcept( $params );
					break;

                case 'store' :
                case 'STORE' :
                    if ( empty($params['filter_id']['store']['id']) && empty($params['filter_id']['store']['code']) )
                        // return a proper error message. REMOVE the following comment
                        return false;
                     
                    $store_servers = $store_controller->getStoreServersInStore( $params );
                    break;
                    
                default :
                	$store_servers = $store_controller->getAllStoreServers( $params );
        	}
        	
        	$unique_store_servers = array_unique( $store_servers );
        	
        	// fetch the store_server details
        	$store_server_details = $store_controller->getStoreServerDetails( $unique_store_servers, $params );

        	// ensure that $store_server_details are properly formatted before returning
        	return $store_server_details;
        }
        

        /**
         * Checks if the system supports the version passed as input
         *
         * @param $version
         */

        public function checkVersion( $version )
        {
            if( in_array( strtolower( $version ), array( 'v1','v1.1' ))){
                return true;
            }
            return false;
        }

        public function checkMethod( $method )
        {
            if( in_array( strtolower( $method ), array(  'diagnostics_summary', 'summary', 'store_report' ) ) )
            {
                return true;
            }
            return false;
        }
    }
