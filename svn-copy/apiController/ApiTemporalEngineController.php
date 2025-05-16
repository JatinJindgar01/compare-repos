<?php
//TODO: referes to cheetah
require_once 'model_extension/TemporalEngineModel.php';
//TODO: referes to cheetah
include_once 'thrift/segmentation.php';
/**
 * 
 * @author ketaki
 *
 */

class ApiTemporalEngineController extends ApiBaseController{
	
	protected $temporalEngineModel;
	protected $ruleset_elements;
	protected $actionTemplate_elements; 
	protected $org_name;
	private $org_config_id;
	
	public function ApiTemporalEngineController( $org_config_id = ''){
		
		parent::__construct();
		
		global $currentorg, $currentuser;
		
		$this->org_name = $currentorg->name;
		
		$this->temporalEngineModel = new TemporalEngineModel($org_config_id);
		$this->org_config_id = $org_config_id;
	}
	
	public function getActivityGroupWiseReportData($data){
		$this->logger->debug('Inside getActivityGroupWiseReportData');
		$org_id = $this->org_id;
		$start_date = $data['start_date'];
		$end_date = $data['end_date'];
		$return_data = $this->temporalEngineModel->getActivityGroupWiseCustomers($org_id, $start_date, $end_date);
		$this->logger->debug('Leaving getActivityGroupWiseReportData');
		return $return_data;
	}
	
	public function getContactFreqWiseReportData($data){
		$this->logger->debug('Inside getContactFreqWiseReportData');
		$org_id = $this->org_id;
		$start_date = $data['start_date'];
		$end_date = $data['end_date'];
		$contactFreq_Slabs = explode("\n", $data['contact_frequency']);
		
		$return_data = $this->temporalEngineModel->getContactFreqWiseCustomers($org_id, $start_date, $end_date, $contactFreq_Slabs);
		
		$this->logger->debug('Leaving getContactFreqWiseReportData');
		
		return $return_data;
	}
	
	public function getTimelineJumpReportData($data){
		$this->logger->debug('Inside getTimelineJumpReportData');
		$org_id = $this->org_id;
		$start_date = $data['start_date'];
		$end_date = $data['end_date'];
		
		$return_data = $this->temporalEngineModel->getTimelineJumps($org_id, $start_date, $end_date);
		$this->logger->debug('Leaving getTimelineJumpReportData');
		return $return_data;
	}
	
	public function checkIfTimelineExists($timeline_name, $org_config_name) {
		$org_id = $this->org_id;
		$return_data = $this->temporalEngineModel->getTimelineId($org_id, $this->org_config_id, $timeline_name);
		
		if ($return_data == NULL)
			return false;
		else
			return true;
	}
	
	public function checkIfPhaseExists($timeline_name, $phase_name, $org_config_name) {
		$org_id = $this->org_id;
		$timeline_id = $this->temporalEngineModel->getTimelineId($org_id, $this->org_config_id, $timeline_name); 
		$return_data = $this->temporalEngineModel->getPhaseId($org_id, $this->org_config_id, $timeline_id, $phase_name);
		
		if ($return_data == NULL)
			return false;
		else
			return true;
	}
	
	public function checkIfMilestoneExists($milestone_name, $timeline_name, $phase_name, $org_config_name) {
		$org_id = $this->org_id;
		$return_data = $this->temporalEngineModel->getMilestoneId($org_id, $this->org_config_id, $milestone_name, $timeline_name, $phase_name, $org_config_name);
		
		if ($return_data == NULL)
			return false;
		else
			return true;
	}
	
	public function enterTimelinePhase($data, $org_config_name){
		$this->logger->debug('Inside enterTimelinePhase');
		$org_id = $this->org_id;
		$return_data = $this->temporalEngineModel->enterTimelinePhase($org_id, $this->org_config_id, $org_config_name, 
										$data['timeline_name'],
										$data['timeline_description'],
										$data['phase_name'],
										$data['phase_description'],
										$data['phase_analyzer_ruleset'],
										$data['state_analyzer_ruleset'] );
		$this->logger->debug('Leaving enterTimelinePhase');
	}
	
	public function getMilestones($timeline, $phase){
		$this->logger->debug('Inside getMilestones');
		$org_id = $this->org_id;
		$return_data = $this->temporalEngineModel->getMilestones($org_id, $this->org_config_id, $timeline, $phase);
		$this->logger->debug('Leaving getMilestones');
		return $return_data;
	}
	
	public function getMilestonesInfoByPhase($phase_id) {
		$return_data = $this->temporalEngineModel->getMilestonesInfoByPhase($this->org_id, $this->org_config_id, $phase_id);
		return $return_data;
	}
	
	public function parseRulesXML($xml = '', $org_config_name=''){
		$this->logger->debug('Inside parseRulesXML');
		$org_id = $this->org_id;
		if ($xml != '')
			$return_data = $xml;
		else
			$return_data = $this->temporalEngineModel->getRulesXML($org_id, $this->org_config_id, $org_config_name);
		
		$this->logger->debug("hsjajgjagsjg".$return_data);
		$element = simplexml_load_string($return_data);
		if ($element == false)
			$this->logger->debug('xml object not created');
		else
			$this->ruleset_elements = $element->xpath('/rulesets/ruleset');
		
		$this->logger->debug('Leaving parseRulesXML  : ');
	}
	
	public function parseActionsXML( $org_config_name ){
		$this->logger->debug('Inside parseActionsXML');
		$org_id = $this->org_id;
		$return_data = $this->temporalEngineModel->getActionsXML($org_id, $this->org_config_id, $org_config_name);
		
		$element = simplexml_load_string($return_data);
		if ($element == false)
			$this->logger->debug('xml object not created');
		$this->actionTemplate_elements = $element->xpath('/actions-templates/action-template');

		$this->logger->debug('Leaving parseActionsXML ');
	}
	
	public function getActionPropertiesOfMilestone($milestone_id){
		$this->logger->debug('Inside getActionPropertiesOfMilestone');
		$res = $this->temporalEngineModel->getActionPropertiesOfMilestone($this->org_id, $this->org_config_id, $milestone_id);
		
		$final_action_prop_arr = array();
		foreach ($res as $row) {
			$final_action_prop_arr[$row['action_id']][$row['action_name']][$row['action_prop_name']] = $row['action_prop_value'];
		}
		$this->logger->debug('Final action properties array :'. print_r($final_action_prop_arr, true));
		return $final_action_prop_arr;
	}
	
	public function getXMLForRuleset($ruleset_name, $org_config_name) {
		$this->parseRulesXML('', $org_config_name);
		$ret = "";
		foreach($this->ruleset_elements as $ruleset){
			$res = $ruleset->xpath('@name');
			$rule_name = $res[0]['name'][0];
			if ($rule_name == $ruleset_name) {
				$ret = $ruleset->asXML();
				break;
			}
		} 
		return $ret;
	}
	
	public function getRulesetNameDescriptionFromXML($xml = '', $org_config_name=''){
		$this->logger->debug('Inside getRulesetNameDescriptionFromXML');
		
		$this->parseRulesXML($xml, $org_config_name);
		$return_array = array();
		
		foreach($this->ruleset_elements as $ruleset){
			$res = $ruleset->xpath('@name');
			$rule_name = $res[0]['name'][0];
			$res = $ruleset->xpath('@description');
			$rule_description = $res[0]['description'][0];
			
			$return_array["$rule_name"] = "$rule_description";
		} 
		$this->logger->debug('Leaving getRulesetNameDescriptionFromXML');
		return $return_array;
	}
	
	public function getActionsForRuleset($rulesetName, $org_config_name){
		$this->logger->debug('Inside getActionsForRuleset');
		
		$this->parseRulesXML('', $org_config_name);
		$finalRulesArr = array();
		
		foreach($this->ruleset_elements as $ruleset){
			$res = $ruleset->xpath('@name');
			$ruleset_name = $res[0]['name'][0];
			
			if ($ruleset_name == $rulesetName){
				$rules = $ruleset->xpath('rule');
				foreach ($rules as $rule){
					$expression = (string) $rule->expression;
					$cases = $rule->xpath('case');
					$resDefault = $rule->xpath('default/execute');
					$casesArr = array();
					foreach ($cases as $case){
						$res = $case->xpath('@value');
						$value = (string) $res[0]['value'][0];
						
						$actionRelated = array();
						$res = $case->xpath('execute/@actionname');
						if (count($res) > 0){
							$actionName = (string)  $res[0]['actionname'][0];
							$res = $case->xpath('execute/@actionid');
							$actionId = (string) $res[0]['actionid'][0];
							array_push($actionRelated, $actionName, $actionId);
						}
						
						$casesArr[$value] = $actionRelated;
					}
					
					if (count($resDefault) > 0){
						$actionRelated = array();
						$res = $resDefault[0]->xpath('@actionname');
						$actionName = (string) $res[0]['actionname'][0];
						$res = $resDefault[0]->xpath('@actionid');
						$actionId = (string)  $res[0]['actionid'][0];
						array_push($actionRelated, $actionName, $actionId);
						$casesArr['default'] = $actionRelated;
					}
					
					$finalRulesArr[$expression] = $casesArr;
				}
				break;
			}
		}
		$this->logger->debug('Leaving getActionsForRuleset');
		return $finalRulesArr;
	}
	
	public function getActionProperties($actionName, $org_config_name){
		$this->logger->debug('Inside getActionProperties');
		
		$this->parseActionsXML($org_config_name);
		$proprties_Arr = array();
		
		foreach ($this->actionTemplate_elements as $action_template){
			$res =  $action_template->xpath('@name');
			$action_name = (string) $res[0]['name'][0];
			if ($action_name == $actionName){
				$properties = $action_template->xpath('property');
				foreach($properties as $property){
					$res =  $property->xpath('@key');
					$key = (string) $res[0]['key'][0];
					$res =  $property->xpath('@value');
					$value = (string) $res[0]['value'][0];
					$proprties_Arr[$key] = $value;;
				}
				break;
			}
		}
		
		$this->logger->debug('Leaving getActionProperties');
		return $proprties_Arr;
	}
	
	public function insertMilestone($data, $org_config_name){
		$this->logger->debug('Inside insertMilestone');
		
		$timeline_name = $data['timeline_name'];
		$phase_name = $data['phase_name'];
		$index = $data['index'];
		$milestone_name = $data['milestone_name'];
		$milestone_desc = $data['milestone_desc'];
		$start_ruleset = $data['start_ruleset'];
		
		$org_id = $this->org_id;
		
		$timeline_id = $this->temporalEngineModel->getTimelineId($org_id, $this->org_config_id, $timeline_name);
		$phase_id = $this->temporalEngineModel->getPhaseId($org_id, $this->org_config_id, $timeline_id, $phase_name);
		
		$milestone_id = $this->temporalEngineModel->insertNewMilestone($org_id, $this->org_config_id, $org_config_name, 
													$milestone_name, $milestone_desc, $index, $start_ruleset, $phase_id);
		
		$this->logger->debug('Leaving insertMilestone');
		return $milestone_id;
	}
	
	public function insertPhase($data, $org_config_name){
		$timeline_name = $data['timeline_name'];
		$phase_name = $data['phase_name'];
		$phase_desc = $data['phase_description'];
		$index = $data['phase_index'];
		$phase_analyzer_ruleset = $data['phase_analyzer_ruleset'];
		$state_analyzer_ruleset = $data['state_analyzer_ruleset'];
		
		$org_id = $this->org_id;
		$timeline_id = $this->temporalEngineModel->getTimelineId($org_id, $this->org_config_id, $timeline_name);
		
		$phase_id = $this->temporalEngineModel->insertNewPhase($org_id, $this->org_config_id, $org_config_name, 
															$phase_name, $phase_desc, $index, $phase_analyzer_ruleset, 
															$state_analyzer_ruleset, $timeline_id);
		return $phase_id;
	}
	
	public function insertActionProperties($org_config_name, $actionName, $action_id, $property_name, $property_val, $milestone_id){
		$this->logger->debug('Inside insertActionProperties');
		
		$org_id = $this->org_id;
		$this->temporalEngineModel->insertActionProperty($org_id, $this->org_config_id, $org_config_name, 
															$actionName, $action_id, $property_name, $property_val, 
															$milestone_id);
		
		$this->logger->debug('Leaving insertActionProperties');
	}
	
	//Thrift calls start here
	public function areTimelinesRunning() {
		$client = new TemporalEngineThriftClient();
		return $client->areTimelinesRunning($this->org_id, $this->org_config_id);
	}
	
	public function startTimelines($org_config_name) {
		$client = new TemporalEngineThriftClient();
		if (! $client->areTimelinesRunning($this->org_id, $this->org_config_id)) {
			$this->temporalEngineModel->startTimelinesForAudit($org_config_name);
			return $client->startTimelines($this->org_id, $this->org_config_id);
		}
	}
	
	public function stopTimelines($org_config_name) {
		$client = new TemporalEngineThriftClient();
		if ($client->areTimelinesRunning($this->org_id, $this->org_config_id)) {
			$this->temporalEngineModel->stopTimelinesForAudit($org_config_name);
			return $client->stopTimelines($this->org_id, $this->org_config_id);
		}
	}
	
	public function registerOrgConfig($orgConfigName) {
		$client = new TemporalEngineThriftClient();
		$this->temporalEngineModel->registerOrgConfigForAudit($orgConfigName);
		$client->registerOrgConfig($this->org_id, $this->org_config_id);
	}
	
	public function notifyClustersUploaded($orgConfigName) {
		$org_id = $this->org_id;
		$client = new TemporalEngineThriftClient();
		if ($client->areTimelinesRunning($org_id, $this->org_config_id)) {
			$this->temporalEngineModel->notifyClustersUploadedForAudit($orgConfigName);
			return $client->notifyClustersUploaded($org_id, $this->org_config_id);
		}
	}
	
	public function getNotifyClusterUploadedStatus($orgConfigName) {
		$org_id = $this->org_id;
		$client = new TemporalEngineThriftClient();
		return $client->getNotifyClustersUploadedStatus($org_id, $this->org_config_id);
	}
	
	public function initializeTimelines($orgConfigName) {
		$org_id = $this->org_id;
		$client = new TemporalEngineThriftClient();
		if ($client->areTimelinesRunning($org_id, $this->org_config_id)) {
			$this->temporalEngineModel->initializeTimelinesForAudit($orgConfigName);
			return $client->initializeTimelines($org_id, $this->org_config_id);
		}
	}
	
	public function getInitializeTimelineStatus($orgConfigName) {
		$org_id = $this->org_id;
		$client = new TemporalEngineThriftClient();
		return $client->getInitializeTimelineStatus($org_id, $this->org_config_id);
	}
	
	public function cancelTimelinesInitialization($orgConfigName) {
		$client = new TemporalEngineThriftClient();
		if ($client->areTimelinesRunning($this->org_id, $this->org_config_id)) {
			$this->temporalEngineModel->cancelTimelinesInitializationForAudit($orgConfigName);
			return $client->cancelTimelinesInitialization($this->org_id, $this->org_config_id);
		}
	}
	
	public function reInitializeTimeline($orgConfigName, $timeline_name) {
		$this->logger->debug("Timeline to reinitialize : ". $timeline_name);
		$org_id = $this->org_id;
		$client = new TemporalEngineThriftClient();
		if (! $client->areTimelinesRunning($org_id, $this->org_config_id)) {
			$this->temporalEngineModel->reInitializeTimelineForAudit($orgConfigName, $timeline_name);
			return $client->reinitializeTimeline($org_id, $this->org_config_id, $timeline_name);
		}
	}
	
	public function getReinitializeTimelineStatus($orgConfigName, $timeline_name) {
		$org_id = $this->org_id;
		$client = new TemporalEngineThriftClient();
		return $client->getReinitializeTimelineStatus($org_id, $this->org_config_id, $timeline_name);
	}
	
	//Thrift calls end here
	
	public function getCitiesAsOptions(){
	
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getCitiesAsOptions($org_id);
	}
	
	public function getStoresAsOptions(){
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getStoresAsOptions($org_id);
	}
	
	public function getTimelinesAsOptions($org_config_name){
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getTimelinesAsOptions($org_id, $this->org_config_id);
	}
	
	public function getPhasesForTimelineAsOptions($timeline_id){
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getPhasesOfTimelineAsOptions($org_id, $this->org_config_id, $timeline_id);
	}
	
	public function getMilestonesForPhaseAsOptions($phase_id){
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getMilestonesOfPhaseAsOptions($org_id, $this->org_config_id, $phase_id);
	}
	
	public function getMilestoneById($milestone_id){
		return $this->temporalEngineModel->getMilestoneById($this->org_id, $this->org_config_id, $milestone_id);
	}
	
	public function getPhaseById($phase_id){
		return $this->temporalEngineModel->getPhaseById($this->org_id, $this->org_config_id, $phase_id);
	}
	
	public function getTimelineById($timeline_id){
		return $this->temporalEngineModel->getTimelineById($this->org_id, $this->org_config_id, $timeline_id);
	}
	
	public function getOrganization(){
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getOrganization($org_id);
	}
	
	public function getOrgnizationSkipIds($org_config_name) {
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getOrgnizationSkipIds($org_id, $this->org_config_id);
	}
	
	public function getStores(){
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getStores($org_id);
	}
	
	public function getStoreSkipIds($org_config_name) {
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getStoreSkipIds($org_id, $this->org_config_id);
	}
	
	public function getCities() {
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getCities($org_id);
	}
	
	public function getCitySkipIds($org_config_name) {
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getCitySkipIds($org_id, $this->org_config_id);
	}
	
	public function getTimelines($org_config_name) {
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getTimelines($org_id, $this->org_config_id);
	}
	
	public function getTimelineSkipIds($org_config_name) {
		$org_id = $this->org_id;
		return $this->temporalEngineModel->getTimelineSkipIds($org_id, $this->org_config_id);
	}
	
	public function addToSkipCriterian($ids_to_add, $skip_level_type, $org_config_name) {
		$org_id = $this->org_id;
		$this->temporalEngineModel->addToSkipCriterian($org_id, $this->org_config_id, $org_config_name, 
														$ids_to_add, $skip_level_type);
	}
	
	public function deleteFromSkipCriterian($ids_to_delete, $skip_level_type, $org_config_name) {
		$org_id = $this->org_id;
		$comma_separated_ids = implode(",", $ids_to_delete);
		$this->temporalEngineModel->deleteFromSkipCriterian($org_id, $this->org_config_id, $org_config_name, 
															$comma_separated_ids, $skip_level_type);
	}
	
	
	public function getClusterValsForCluster($cluster_name) {
		$clusters = $this->getClusters();
		$clusterVals = array();
		foreach ($clusters as $cluster) {
			if ($cluster->name == $cluster_name) {
				foreach($cluster->values as $clusterVal) {
					$clusterVals[] = $clusterVal->name;
				}
			}
		}
		return $clusterVals;
	}
	
	public function getSegmentValsForSegment($segment_name) {
		$clusters = $this->getSegments();
		$clusterVals = array();
		foreach ($clusters as $cluster) {
			if ($cluster->name == $segment_name) {
				foreach($cluster->values as $clusterVal) {
					$clusterVals[] = $clusterVal->name;
				}
			}
		}
		return $clusterVals;
	}
	
	public function insertOrgConfig($config_name, $action_template_xml, $rule_xml, $user_init_ruleset) {
		$org_id = $this->org_id;
		$this->temporalEngineModel->insertOrgConfig($org_id, $config_name, $action_template_xml, $rule_xml, $user_init_ruleset);
	}
	
	public function insertStateTimelineMapping($org_config_name, $state_timeline_mapping) {
		$org_id = $this->org_id;
		$this->temporalEngineModel->insertStateTimelineMapping($org_id, $this->org_config_id, 
																$org_config_name, $state_timeline_mapping);
	}
	
	public function areTimelinesCreated( $org_config_name ) {
		
		$org_id = $this->org_id;
		return $this->temporalEngineModel->areTimelinesCreated( $org_id, $this->org_config_id );
	}
	
	public function isOrgConfigured( $org_config_name ) {
		$org_id = $this->org_id;
		return $this->temporalEngineModel->isOrgConfigured($org_id, $org_config_name);
	}
	
	public function getClusters() {
		return $this->temporalEngineModel->getClustersForOrg($this->org_id);
	}
	
	public function getSegments() {
		$org_id = $this->org_id;
		$segmentsThriftClient = new SegmentationEngineThriftClient();
		$session_id = $segmentsThriftClient->createSessionId($_SERVER[UNIQUE_ID], 
														$this->user_id, $org_id);
		$simpleSegments = $segmentsThriftClient->getAllSimpleSegments($org_id, $session_id);
		$microSegments = $segmentsThriftClient->getAllMicroSegments($org_id, $session_id);
		return array_merge($simpleSegments, $microSegments);
	}
	
	
	public function getStartingRuleset($milestone_id) {
		$res = $this->temporalEngineModel->getStartingRuleset($this->org_id, $this->org_config_id, $milestone_id);
		return $res[0]['starting_ruleset'];
	}
	
	public function changeStartRulesetForMilestone($org_config_name, $milestone_id, $ruleset_name) {
		$this->temporalEngineModel->changeStartRulesetForMilestone($this->org_id, $this->org_config_id, $org_config_name, 
																		$milestone_id, $ruleset_name);
	}
	
	public function getOrgConfiguration( $org_config_name ) {
		$org_id = $this->org_id;
		$res = $this->temporalEngineModel->getOrgConfiguration( $org_id, $org_config_name );
		return $res;
	}
	
		
	public function insertCategoryAttrs($categoryAttrs) {
		$org_id = $this->org_id;
		$this->logger->debug("insert category");
		$this->temporalEngineModel->insertCategoryAttrs($this->org_id, $this->org_config_id, $categoryAttrs);
	}
	
	public function getAllStartingRulesets() {
		$org_id = $this->org_id;
		$res = $this->temporalEngineModel->getAllStartingRulesets($org_id, $this->org_config_id);
		if (count($res) > 0) {
			$startingRuesetsArr = array();
			foreach ($res as $row) {
				array_push($startingRuesetsArr, $row['starting_ruleset']);
			}
			return $startingRuesetsArr;
		} else
			return array();
	}
	
	
	public function getStateTimelineMappingJson() {
		$org_id = $this->org_id;
		$res = $this->temporalEngineModel->getStateTimelineMappingJson($org_id, $this->org_config_id);
		if (count($res) > 0) {
			return $res[0]['state_timeline_mapping'];
		}
	}
	
	public function getAllPhasesForTimeline($timeline_name, $org_config_name) {
		$org_id = $this->org_id;
		$timeline_id = $this->getTimelineIdByName($timeline_name, $org_config_name);
		$res = $this->temporalEngineModel->getAllPhasesForTimeline($this->org_id, $this->org_config_id, $timeline_id);
		return $res;
	}
	
	public function getTimelineIdByName($timeline_name, $org_config_name) {
		$org_id = $this->org_id;
		$res = $this->temporalEngineModel->getTimelineIdByName($org_id, $this->org_config_id, $timeline_name);
		return $res[0]['id'];
	}
	
	public function changeStateAnalyzerRulesetForPhase($org_config_name, $phase_id, $ruleset_name) {
		$this->temporalEngineModel->changeStateAnalyzerRulesetForPhase($this->org_id, $this->org_config_id, 
																		$org_config_name, $phase_id, $ruleset_name);
	}
	
	public function changePhaseChangerRulesetForPhase($org_config_name, $phase_id, $ruleset_name) {
		$this->temporalEngineModel->changePhaseChangerRulesetForPhase($this->org_id, $this->org_config_id, 
																		$org_config_name, $phase_id, $ruleset_name);
	}
	
	public function changePhaseOrder($org_config_name, $phase_id, $curr_index, $new_index, $timeline_id) {
		$this->temporalEngineModel->changePhaseOrder($this->org_id, $this->org_config_id, $org_config_name, 
														$phase_id, $curr_index, $new_index, $timeline_id);
	}
	
	public function changeMilestoneOrder($org_config_name, $milestone_id, $curr_index, $new_index, $phase_id) {
		$this->logger->debug("curr idx :".$curr_index.'**new idx '.$new_index.'**milid'.$milestone_id.'**phid'.$phase_id);
		$this->temporalEngineModel->changeMilestoneOrder($this->org_id, $this->org_config_id, $org_config_name,
															$milestone_id, $curr_index, $new_index, $phase_id);
	}
	
	public function getPhaseIdByNameAndTimeline($phase_name, $timeline_name, $org_config_name) {
		$org_id = $this->org_id;
		$res = $this->temporalEngineModel->getTimelineIdByName($org_id, $this->org_config_id, $timeline_name);
		$timeline_id = $res[0]['id'];
		return $this->temporalEngineModel->getPhaseId($org_id, $this->org_config_id, $timeline_id, $phase_name);
	}
	
	public function checkIfMilestoneExistsForAllPhases($timeline_name) {
		$org_id = $this->org_id;
		$res = $this->temporalEngineModel->getTimelineIdByName($org_id, $this->org_config_id, $timeline_name);
		$timeline_id = $res[0]['id'];
		
		$res = $this->temporalEngineModel->getAllPhasesForTimeline($org_id, $this->org_config_id, $timeline_id);
		
		$flag = true;
		if (count($res) > 0) {
			foreach ($res as $row) {
				$phase_id = $row['id'];
				$milestones = $this->temporalEngineModel->getMilestonesInfoByPhase($org_id, $this->org_config_id, $phase_id);
				if (count($milestones) <= 0) {
					$flag = false;
					break;
				}
			}
		}
		
		return $flag;
	}
	
	public function checkIfTimelineIsRun($timeline_name, $org_config_name) {
		$org_id = $this->org_id;
		$timeline_id = $this->temporalEngineModel->getTimelineId($org_id, $this->org_config_id, $timeline_name);
		return $this->temporalEngineModel->checkIfTimelineIsRun($org_id, $this->org_config_id, $timeline_id);
	}
	
	public function deletePhase($org_config_name, $phase_id, $timeline_id) {
		$this->temporalEngineModel->deletePhase($this->org_id, $this->org_config_id, $org_config_name, $phase_id, $timeline_id);
	}
	
	public function deleteMilestone($org_config_name, $milestone_id, $phase_id) {
		$this->temporalEngineModel->deleteMilestone($this->org_id, $this->org_config_id, $org_config_name, 
														$milestone_id, $phase_id);
	}
	
	public function createCopyOfTimeline($new_timeline_name, $timeline_description, $copied_timeline_id, $org_config_name) {
		$org_id = $this->org_id;
		$new_timeline_id = $this->temporalEngineModel->insertNewTimeline($org_id, $this->org_config_id, $org_config_name, $new_timeline_name, $timeline_description);
		
		$resPhases = $this->temporalEngineModel->getAllPhasesForTimeline($org_id, $this->org_config_id, $copied_timeline_id);
		foreach ($resPhases as $phase) {
			$new_phase_id = $this->temporalEngineModel->insertNewPhase($org_id, 
														$this->org_config_id,
														$org_config_name,
														$phase['name'], 
														$phase['description'], $phase['idx'], 
														$phase['phase_changer_ruleset'], 
														$phase['state_analyzer_ruleset'], $new_timeline_id);
														
			$resMilestones = $this->temporalEngineModel->getMilestonesInfoByPhase($org_id, 
														$this->org_config_id, $phase['id']);														
			foreach ($resMilestones as $milestone) {
				$new_milestone_id =	$this->temporalEngineModel->insertNewMilestone($org_id,
																	$this->org_config_id, 
																	$org_config_name,
																	$milestone['name'], 
																	$milestone['description'], $milestone['idx'], 
																	$milestone['starting_ruleset'], $new_phase_id);
																	
				$resActionProps = $this->temporalEngineModel->getActionPropertiesOfMilestone($org_id,
																	$this->org_config_id, $milestone['id']);
				foreach ($resActionProps as $actionProperty) {
					$this->temporalEngineModel->insertActionProperty($org_id, 
																	$this->org_config_id,
																	$org_config_name,
																	$actionProperty['action_name'], 
																	$actionProperty['action_id'], $actionProperty['action_prop_name'], 
																	$actionProperty['action_prop_value'], $new_milestone_id);
				}
			}
		}
	}
	
	public function deleteActionProperties($org_config_name, $milestone_id) {
		$this->temporalEngineModel->deleteActionProperties($this->org_id, $this->org_config_id, $org_config_name, 
															$milestone_id);
	}
	
	public function convertHrMinToMinutes($hours, $minutes) {
		$total_minutes = ($hours*60)+$minutes;
		return $total_minutes;
	}
	
	public function convertMinutesToHrMin($minutes) {
		$arr = array();
		$arr['hours'] = floor($minutes/60);
		$arr['minutes'] = $minutes%60;
		return $arr;
	}
	
	public function insertGlobalTimePreference($org_config_name, $start_minute, $end_minute) {
		$this->temporalEngineModel->insertGlobalTimePreference($this->org_id, $this->org_config_id, $org_config_name, $start_minute, $end_minute);
	}
	
	public function getGlobalTimePreference( $config_name ){
		return $this->temporalEngineModel->getGlobalTimePreference($this->org_id, $this->org_config_id);
	}
	
	public function getClusterValEntriesFromTimePref($org_config_name, $cluster_val) {
		$res = $this->temporalEngineModel->getClusterValEntriesFromTimePref($this->org_id, $this->org_config_id, $cluster_val);
		return $res;
	}
	
	public function deleteAllTimePrefForClusterVal($org_config_name, $cluster_val) {
		$this->temporalEngineModel->deleteAllTimePrefForClusterVal($this->org_id, $this->org_config_id, $org_config_name, $cluster_val);
	}
	
	public function insertTimePreference($org_config_name, $start_minute, $end_minute, $scope, $weight) {
		$this->temporalEngineModel->insertTimePreference($this->org_id, $this->org_config_id, $org_config_name, $start_minute, $end_minute, $scope, $weight);
	}
	
	public function getOrgConfigPropVal( $org_config_name, $prop_name ) {
		return $this->temporalEngineModel->getOrgConfigPropVal( $this->org_id, $this->org_config_id, $prop_name );
	}
	
	public function getAllOrgConfigProps( $org_config_id ) {
		return $this->temporalEngineModel->getAllOrgConfigProps( $this->org_id, $org_config_id );
	}
	
	public function getOrgConfigNames() {
		return $this->temporalEngineModel->getOrgConfigNamesAsOptions($this->org_id);
	}
	
	public function getOrgConfigIdByName($org_config_name) {
		return $this->temporalEngineModel->getOrgConfigIdByName($this->org_id, $org_config_name);
	}
	
	public function insertOrgConfigProp($prop_name, $prop_val, $org_config_name) {
		$org_config_id = $this->getOrgConfigIdByName($org_config_name);
		if ($org_config_id != -1) {
			$this->temporalEngineModel->insertOrgConfigProp($this->org_id, $this->org_config_id, $prop_name, $prop_val);
		}
	}
	
	public function getCategoryAttributes() {
		$res = $this->temporalEngineModel->getCategoryAttributes($this->org_id, $this->org_config_id);
		$category_attrs = array();
		foreach ($res as $row) {
			$category_attrs[] = $row['name'];
		}
		return $category_attrs;
	}
	
	public function getOffersAsOption() {
		$res = $this->temporalEngineModel->getOffersAsOption($this->org_id, $this->org_config_id);
		$offers_arr = array();
		foreach ($res as $row) {
			$offers_arr[$row['name']] = $row['id'];
		}
		return $offers_arr;
	}
	
	public function getOfferNameById($offer_id) {
		return $this->temporalEngineModel->getOfferNameById($this->org_id, $this->org_config_id, $offer_id);
	}
}
?>
