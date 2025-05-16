<?php

/**
 * 
 * @author Suryajith
 *
 */
class clientReportsController extends BaseController {
	
	var $testing;

	public $clientReportsModel;
	
	public function __construct(clientReportsModule $client_reports_module, $currentuser = false, $testing = false) {

		parent::__construct('users');

		if($currentuser != false){
			$this->currentuser = $currentuser;
			$this->currentorg = $this->currentuser->getProxyOrg();
		}
		
		$this->client_reports_module = $client_reports_module;

		$this->testing = $testing;
		$this->clientReportsModel = new clientReportsModel($this, $testing);
	}
	
	/**
	 * @deprecated
	 * @param unknown_type $params
	 */
	function setReportsBase($params){
		
		$this->clientReportsModel->InsertResult();
		return $this->clientReportsModel->createNewReports($params['report_name'], $params['report_desc']);
		
	}
	
	public function getExistingReportsConfiguredForOrg(){
		
		$this->clientReportsModel->getHashResult('report_name', 'id');
		return $this->clientReportsModel->getExistingReportsAsOptions();
		
	}
	
	function configureReportsForOrg($params){
		
		global $currentorg;
		$org_id = $currentorg->org_id;
		
		
		$reports_selected = $params['reports_selected'];
		$query = array();
		
		foreach($reports_selected as $id)
			array_push($query, "('$org_id', '$id')");
		
		$query = implode(',', $query);
		
		$this->clientReportsModel->updateTable();
		$this->clientReportsModel->DeleteReportsForOrg();
		
		$this->clientReportsModel->InsertResult();
		$this->clientReportsModel->createReportsForOrg($query);
		
		list($attr, $custom, $base) = $this->setUpReportsForOrg();
		
		//Util::redirect('administration', 'clientreportconfiguration', false, 'The reports have been configured.');
		
	}
	
	function setUpReportsForOrg(){
		
		$attr = $this->getAttributesAndValues();
		
		$custom = $this->customFieldsAsOptions();
		
		$base = $this->createBaseFieldsForReports();
		 
		return array($attr, $custom, $base);
		
	}
	
	function getReportsFormData(){
		
		global $currentorg;
		$org_id = $currentorg->org_id;
		
		
		$this->clientReportsModel->getQueryResult();
		$return_reports = $this->clientReportsModel->getAvailableReports();
		
		$available_reports = array();
		
		foreach($return_reports as $report){
			$report_id = $report['id'];
			$this->clientReportsModel->getQueryResult();
			$field_mapping = $this->clientReportsModel->getFieldMapping($report_id);
			$mod_report = $report;
			$mod_report['report_fields'] = $field_mapping;
			array_push($available_reports, $mod_report);
		}
		
		return $available_reports;
	}
	
	function beautifyReport($reports){
		//return $report;
		global $currentorg, $currentuser;
		
		$user_id = $currentuser->user_id;
		$org_id = $currentorg->org_id;
		
		$widgets = array();
		
		foreach($reports as $name => $report){
			
			$name = $name." Report";
			
			$widget = array(
					'widget_name' => $name,
					'widget_code' => $user_id.$org_id,
					'widget_data' => $report
			);
			
			array_push($widgets, $widget);
			
		}
		
		$am = new AdministrationModule();
		$str = $am->createWidgetToSendMail($widgets);		
		$this->logger->debug("Result of MIS before return from Beautify ". $str);
		return $str;
		
	}
	
	
	function dealWithInput($report_id, $params, $start_date, $end_date){
		
		$this->clientReportsModel->getHashResult('id', 'report_code');
		$reports_hash = $this->clientReportsModel->getReportsHash();
		
		$report_type = $reports_hash[$report_id];
		
		$function = 'get'.$report_type.'Reports';
		
		$date_filter = $this->getDateFilter($start_date, $end_date);
		
		$this->logger->debug("This is being re routed to ".$function);
		
		$report = $this->$function($params, $date_filter);
		
		return $report;
	}
	
	function getMisReports($params, $date_filter){
		
		global $currentuser;
		
		$report = new Table();
		
		$widgets = array();
		
		$count = $params['count']; 
		$vouchers_count = $params['voucher_count'];
		
		$count = true;
		$vouchers_count = true;
		
		$ll_outlier_filter = Util::includeOnlyBillsWithStatus('ll');
		
		$data_array = array();
		
		$this->clientReportsModel->getFirstColumnResult();
		$fields_selected = $this->clientReportsModel->getSelectedFieldsByOrg();
		
		if(in_array('new_customers', $fields_selected)){
			if($count) $this->clientReportsModel->getScalarResult();
			else $this->clientReportsModel->getFirstColumnResult();
			$count = $this->clientReportsModel->getNewlyRegisteredCustomersCount($date_filter);
			array_push($data_array, array('field' => 'Newly Registered Customers', 'value' => $count));
		}
		
		
		if(in_array('recorded_bills', $fields_selected)){
			$this->clientReportsModel->getFirstRowResult();
			$result = $this->clientReportsModel->getRecordedBillsCount($date_filter);
			
			//$data_array .= "<tr><th> Recorded Bills </th><td>$count</td></tr>";
			array_push($data_array, array('field' => 'Recorded Bills', 'value' => $result['count']));
			array_push($data_array, array('field' => 'Total Loyalty Sales', 'value' => $result['total_sales']));
			array_push($data_array, array('field' => 'Total Points', 'value' => $result['total_points']));
		}

		if(in_array('points_redeemed', $fields_selected)){
			$this->clientReportsModel->getScalarResult();
			$count = $this->clientReportsModel->getPointsRedeemed($date_filter);
			array_push($data_array, array('field' => 'Total Points Redeemed', 'value' => $count));
		}
		
		if(in_array('total_non_loyalty_bills', $fields_selected)){
			$this->clientReportsModel->getFirstRowResult();
			$result = $this->clientReportsModel->getTotalNonLoyaltySales($date_filter);
			array_push($data_array, array('field' => 'Non Loyalty Bills', 'value' => $result['count']));
			array_push($data_array, array('field' => 'Total Non Loyalty Sales', 'value' => $result['total_sales']));
		}
		
		if(in_array('repeat_bills', $fields_selected)){
			$this->clientReportsModel->getFirstRowResult();
			$result = $this->clientReportsModel->getRepeatBillsCount($date_filter);
			array_push($data_array, array('field' => 'Repeat Bills', 'value' => $result['count']));
			array_push($data_array, array('field' => 'Repeat Bill Sales', 'value' => $result['total_sales']));
		}
		
		
		if(in_array('tracked_bills', $fields_selected)){
			$this->clientReportsModel->getFirstRowResult();
			$result = $this->clientReportsModel->getTrackedBillsCount($date_filter);
			array_push($data_array, array('field' => 'Tracked Bills', 'value' => $result['no_of_bills']));
			array_push($data_array, array('field' => 'Tracked Sales', 'value' => $result['total_sales']));
			array_push($data_array, array('field' => 'Tracker Footfall Count', 'value' => $result['footfall']));
			array_push($data_array, array('field' => 'Distinct Tracked Bill Dates', 'value' => $result['tracker_dates']));
		}
		
		if(in_array('vouchers_issued', $fields_selected)){
			$this->clientReportsModel->getScalarResult();
			$count = $this->clientReportsModel->getVouchersIssuedCount($date_filter);
			array_push($data_array, array('field' => 'Vouchers Issued', 'value' => $count));
		}
		
		if(in_array('vouchers_redeemed', $fields_selected)){
			if($vouchers_count) $this->clientReportsModel->getScalarResult();
			else $this->clientReportsModel->getFirstColumnResult();
			$count = $this->clientReportsModel->getVouchersRedeemedCount($date_filter);
			array_push($data_array, array('field' => 'Vouchers Redeemed', 'value' => $count));
		}
		
		$this->logger->debug("Result of MIS ".print_r($data_array, true));
		$report->importArray($data_array);
		$report = $this->beautifyReport(array('MIS' => $report));
		$this->logger->debug("Result of MIS before return ". $report);
		return $report;
		
	}
	
	/**
	 * If sku is selected then the report is genereated for a particular sku else by category. 
	 * @param unknown_type $sku
	 */
	
	function getSalesReports($params, $date_filter){
		
		/*$sku = $params['item_sku'];
		//$sku = false;
		//$attribute_hash_map = array(259 => 307817, 260 => 307818, 261 => 307814);
		
		//Get attribute list and the corresponding values.
		if($sku){
			list($customer_list, $number_of_purchases, $total_sale) = $this->getCustomerListBySku($sku, $date_filter);
			
		}
		else{
			*/
			list($attributes_hash, $attribute_values_hash) = $this->getAttributesAndAttributeValueHash();
			
			$attribute_hash_map = array();
			
			foreach($params as $attribute => $attribute_value )	
				$attribute_hash_map[$attributes_hash[$attribute]] = $attribute_values_hash[$attribute_value];
			
			list($customer_list, $number_of_purchases, $total_sale) = $this->getCustomerListByCategory($attribute_hash_map, $date_filter);
			
		//}

		$table = new Table();
		$table->importArray(array(array('field' => 'Number of customers who have bought this category', 'value' => $number_of_purchases),
										array('field' => 'Total sales', 'value' => $total_sale)));
		
		$return = $this->beautifyReport(array('Sales' => $table, 'Customer List ' => $customer_list));
		return $return;
	}
	
	function getCustomerReports($params, $date_filter){
		
		$criterion = $params['criterion'];
		
		//Get top 100 customers
		$return = $this->getTopCustomers($criterion, $date_filter);
		return $this->beautifyReport(array("Top Customers Filtered By $criterion" => $return ));
	}
	
	function getcustomfieldsReports($params, $date_filter){
		
		$customer_list_total = array();
		$is_first = true;
		//$params = array('citibank' => 'n', 'fav_sport' => 'cricket');
		
		foreach($params as $custom_field_name => $custom_field_value){
			$customer_list = $this->getCustomersByCustomFieldValues($custom_field_name, $custom_field_value);
			///$this->logger->debug("After intersection :".print_r($customer_list_total, true));
			//$this->logger->debug("Data digged :".print_r($customer_list, true));
			
			if($is_first)
				$customer_list_total = $customer_list;
			else
				$customer_list_total = array_intersect($customer_list_total, $customer_list);
			//$this->logger->debug("After intersection :".print_r($customer_list_total, true)); 
		}
		
		//$changes_made = $this->getCustomersModifiedByStore();
		
		return $this->beautifyReport(array('Customers Matching the Custom Field Value' => $customer_list_total));
		
		 
	}
	
	function getDndReports($params, $date_filter){
		
		$this->clientReportsModel->getQueryTableResult();
		$customer_list = $this->clientReportsModel->getDndCustomerList();
		
		return $this->beautifyReport(array('Customers in DND list who registered at this store' => $customer_list));
	}
	
	function getFiltersBasedReports($params, $date_filter){
		
		$filter = $params['filter'];
		$lower_bound = $params['lower_bound'];
		$upper_bound = $params['upper_bound'];

		$function = "getCustomersBy".$filter."Filter";
		$this->logger->debug("Routing to ".$function);
		$return = $this->$function($lower_bound, $upper_bound, $date_filter);
		
		return $this->beautifyReport(array("Filtering Customers By $filter" => $return ));
		
	}	
	
	/***************** By Category ********************/
	
	function getCustomerListByCategory($attribute_hash_map, $date_filter){
		
		$is_first = true;
		$i = 0;
		$query = "";
		
		$query .= "SELECT im.item_sku FROM `inventory_masters` im ";
		
		foreach($attribute_hash_map as $attribute => $attribute_value){
			$i++;
			$table_alias = 't'."$i";
			
			$query .= "INNER JOIN `inventory_items` $table_alias 
						ON $table_alias.`attribute_id` = $attribute 
						AND $table_alias.`attribute_value_id` = $attribute_value 
						AND $table_alias.`org_id` = `im`.`org_id` 
						AND $table_alias.`item_id` = `im`.`id`";
		}
		$query .= " WHERE im.`org_id` = $this->org_id";
		
		$this->clientReportsModel->getQueryTableResult();
		$customer_list = $this->clientReportsModel->getCustomerListByCategory($query, $date_filter);
		
		$query = "lbl.`item_code` IN ($query)";
		
		$this->clientReportsModel->getFirstRowResult();
		$total_sales = $this->clientReportsModel->getCategorySalesDetails($query, $date_filter);
		
		$number_of_purchases = $total_sales['number_of_purchases'];
		$total_sale = $total_sales['total_sales'];
		
		return array($customer_list, $number_of_purchases, $total_sale);
	}
	
	function getAttributesAndAttributeValueHash(){
		
		$this->clientReportsModel->getQueryResult();
		$attr = $this->clientReportsModel->getAttributesAndAttributeValues();
		
		$attributes_hash = array();
		$attribute_values_hash = array();
		
		foreach($attr as $row){
			if(!isset($attributes_hash[$row['attribute_name']]))
				$attributes_hash[$row['attribute_name']] = $row['attribute_id'];
			
			if(!isset($attribute_values_hash[$row['attribute_value_name']]))
				$attribute_values_hash[$row['attribute_value_name']] = $row['attribute_value_id']; 
		}
		
		return array($attributes_hash, $attribute_values_hash);
		
	}
	
	function getCustomerListBySku($sku, $date_filter){
		
		$this->clientReportsModel->getQueryTableResult();
		$customer_list = $this->clientReportsModel->getCustomerListBySku($sku, $date_filter);
		
		$filter = "lbl.`item_code` = '$sku'";
		
		$this->logger->debug("Getting sales report for $sku");
		$this->clientReportsModel->getFirstRowResult();
		$total_sales = $this->clientReportsModel->getCategorySalesDetails($filter, $date_filter);
		
		
		$number_of_purchases = $total_sales['number_of_purchases'];
		$total_sale = $total_sales['total_sales'];
		
		return array($customer_list, $number_of_purchases, $total_sale);
	}
	
	/***************** Top Customers ********************/
	
	function getTopCustomers($criterion = 'PURCHASES', $date_filter){
		
		$criterion_map = array('PURCHASES' => 'total_purchases', 'VISITS' => 'no_of_visits');
		$order_by_filter = "ORDER BY ".$criterion_map[$criterion]." DESC ";
		
		$limit = "LIMIT 100";
		
		$this->clientReportsModel->getQueryTableResult();
		return $this->clientReportsModel->getTopCustomers($order_by_filter, $limit, $date_filter);
		
	}
	
	
	/****************** filters ********************/
	
	
	public function getCustomersByAvgBillValueFilter($avg_bill_lower_bound, $avg_bill_upper_bound, $date_filter){
		
		if(!$avg_bill_lower_bound && !$avg_bill_upper_bound)
			return false;
		if($avg_bill_lower_bound && $avg_bill_upper_bound)
			$filter = "AVG(ll.bill_amount) BETWEEN $avg_bill_lower_bound AND $avg_bill_upper_bound ";
		else{
			$filter = ($avg_bill_lower_bound) ? "AVG(ll.bill_amount) > $avg_bill_lower_bound" : "";
			$filter .= ($avg_bill_upper_bound) ? "AVG(ll.bill_amount)  < $avg_bill_upper_bound" : "";
		}
		
		
		$response = " CONCAT(e.firstname, ' ', e.lastname) as name, IFNULL(avg(ll.bill_amount), 0) as avg_bill ";
		$order_by = " ORDER BY AVG(ll.bill_amount) DESC ";
		
		$this->clientReportsModel->getQueryTableResult();
		return $this->clientReportsModel->getCustomersByFilter($response, $filter, $date_filter, $order_by);
		
	}
	
	
	public function getCustomersByTotalPointsFilter($points_lower_bound, $points_upper_bound, $date_filter){
		
		
		if(!$points_lower_bound && !$points_upper_bound)
			return false;
			
		$this->clientReportsModel->updateTable();
		$this->clientReportsModel->dropTempTableForPoints();
		
		$this->clientReportsModel->updateTable();
		$this->clientReportsModel->createTempTableForPoints();
		
		$this->clientReportsModel->updateTable();
		$this->clientReportsModel->getCustomerPointsFromAwardedPointsLog($filter, $date_filter);
		
		$this->clientReportsModel->updateTable();
		$this->clientReportsModel->getCustomerPointsFromLoyaltyLog($filter, $date_filter);
		
		if($points_lower_bound && $points_upper_bound)
			$filter = "`points` BETWEEN $points_lower_bound AND $points_upper_bound ";
		else{
			$filter = ($points_lower_bound) ? "`points` > $points_lower_bound" : "";
			$filter .= ($points_upper_bound) ? "`points` < $points_upper_bound" : "";
		}
		$this->clientReportsModel->getQueryTableResult();
		$return_data = $this->clientReportsModel->getCustomersByTotalPointFilter($filter);
		
		$this->clientReportsModel->updateTable();
		$this->clientReportsModel->dropTempTableForPoints();
		
		
		return $return_data;
	}
	
	
	public function getCustomersByTotalPurchasesFilter($amount_lower_bound = false, $amount_upper_bound = false, $date_filter){
		
		if(!$amount_lower_bound && !$amount_upper_bound)
			return false;
		
		if($amount_lower_bound && $amount_upper_bound)
			$amount_filter = " SUM(`bill_amount`) BETWEEN $amount_lower_bound AND $amount_upper_bound ";
		else{
			$amount_filter = ($amount_lower_bound) ? "SUM(`bill_amount`) > $amount_lower_bound" : "";
			$amount_filter .= ($amount_upper_bound) ? "SUM(`bill_amount`) < $amount_upper_bound" : "";
		}
		
		$response = "CONCAT(e.firstname, ' ', e.lastname) as name, (SUM(`bill_amount`), 0) as total_purchases ";
		$order_by = " ORDER BY SUM(`bill_amount`) DESC ";
		
		$this->clientReportsModel->getQueryTableResult();
		return $this->clientReportsModel->getCustomersByFilter($response, $amount_filter, $date_filter, $order_by);
	}
	
	/*********** Custom fields ***************/
	
	public function getCustomersByCustomFieldValues($cf_name, $value){
		
		$customFieldClass = new CustomFields();
		
		$cf_hash = $customFieldClass->getCustomFields($org_id, 'query_hash', 'name', 'id');
		$cf_id = $cf_hash[$cf_name];
		
		$custom_field_data = $customFieldClass->getCustomFieldById($org_id, $cf_id);
		
		if($custom_field_data['type'] == 'datepicker')
			$users = $customFieldClass->getCustomFieldUserByValuesForDatePicker($value, $cf_id, 'query');
		else
			$users = $customFieldClass->getCustomFieldUserByValues($value, $cf_id, 'exact', 'query');
		
		$customers = array();
		foreach($users as $row)
			array_push($customers, $row['assoc_id']);
		
		$this->clientReportsModel->getQueryTableResult();
		$customer_list = $this->clientReportsModel->getNamesFromIds($customers);
		
		return $customer_list;
		
	}
	
	public function getCustomersModifiedByStore(){
		
		$customFieldClass = new CustomFields();
					
		$users = $customFieldClass->getCustomFieldValuesStoreWiseCountForSimpleReport($org_id, false, $start_date, $end_date, false, -1, $store_id);
		
		$table = new Table();
		$table->importArray($users);
		
		return $table;
	}
	
	/*********** Generic fns ***************/
	private function getDateFilter($start_date, $end_date = false){
		if(!$start_date)
			return false;
		return ($end_date) ? "BETWEEN DATE( '$start_date' ) AND DATE( '$end_date' )" : "> DATE($start_date)";
	}
	
	
	public function getAttributesAndValues(){
		
		$this->clientReportsModel->getQueryResult();
		$attr = $this->clientReportsModel->getAttributesAndAttributeValues();
		
		$attr_array = array();
		
		foreach($attr as $row){	
			$attribute = $row['attribute_name'];
			$attribute_value = $row['attribute_value_name'];
			
			if(!isset($attr_array[$attribute]))
				$attr_array[$attribute] = array();
			array_push($attr_array[$attribute], $attribute_value);
		}
		
		foreach($attr_array as $attribute_name => $attribute_values){
			
			$json_array = array();
			foreach($attribute_values as $av)
				$json_array[$av] = $av;
				
			$json_values = json_encode($json_array);
			
			$this->clientReportsModel->InsertResult();
			$id = $this->clientReportsModel->createCustomFieldsFromAttributes($attribute_name, $json_values);
			
			$this->clientReportsModel->InsertResult();
			$id = $this->clientReportsModel->createCustomFieldsForReports(2, $id, $attribute_name);
		}
		
		return $id;
		
	}
	
	public function customFieldsAsOptions(){
		
		/*$json_array = array();
		foreach($custom_fields as $cf)
			$json_array[$cf] = $cf;
				
		$json_values = json_encode($json_array);
		
		$this->clientReportsModel->InsertResult();
		$id = $this->clientReportsModel->createCustomFieldsFromAttributes('custom_field_name', $json_values);*/
		
		$this->clientReportsModel->InsertResult();
		$id = $this->clientReportsModel->copyLoyaltyRegistrationFieldsIntoReports();
		
		return $id;
		
	}
	
	public function createBaseFieldsForReports(){
		
		$configs = array(
					array('name' => 'criterion', 'report' => 3, 'type' => 'radio', 'datatype' => 'String', 'label' => 'Criterion For Reports', 'attrs' => "{\"VISITS\":\"VISITS\",\"PURCHASES\":\"PURCHASES\"}"),
					array('name' => 'custom_field_value', 'report' => 4, 'type' => 'text', 'datatype' => 'String', 'label' => 'Custom Field Value', 'attrs' => 'NULL'),
					array('name' => 'filter', 'report' => 6, 'type' => 'radio', 'datatype' => 'String', 'label' => 'Key Metric', 'attrs' => "{\"AvgBillValue\":\"AvgBillValue\",\"TotalPurchases\":\"TotalPurchases\",\"TotalPoints\":\"TotalPoints\"}"),
					array('name' => 'lowerbound', 'report' => 6, 'type' => 'text', 'datatype' => 'Integer', 'label' => 'Value Between', 'attrs' => 'NULL'),
					array('name' => 'upperbound', 'report' => 6, 'type' => 'text', 'datatype' => 'Integer', 'label' => 'And', 'attrs' => 'NULL')
					);
		//array('name' => 'item_sku', 'report' => 2, 'type' => 'text', 'datatype' => 'String', 'label' => 'ITEM SKU', 'attrs' => 'NULL'),
		foreach($configs as $field){
			$this->clientReportsModel->InsertResult();
			$id = $this->clientReportsModel->createCustomFields($field['name'], $field['type'], $field['datatype'], $field['label'], $field['attrs']);
			
			$this->clientReportsModel->InsertResult();
			$id = $this->clientReportsModel->createCustomFieldsForReports($field['report'], $id, $field['name']);
		}
		
		return $id;
	}
	
	
}
?>
