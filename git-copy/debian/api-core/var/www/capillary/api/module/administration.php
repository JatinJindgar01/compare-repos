<?php
include_once 'apiController/ApiImportController.php';
//TODO: referes to cheetah
include_once "health_dashboard/EntityHealthTracker.php";
include_once "apiController/ApiStoreController.php";
/**
 * Central administration module for the Client
 * @author kmehra
 *
 */
class AdministrationModule extends BaseModule {

	private $db;
	public $inventory;

	private $import_op_success = false;

	function __construct() {
		parent::__construct();
		$this->db = new Dbase('users');
		$this->inventory = new InventorySubModule();

	}

	/**
	 * Get the groups of the org as an array for the dropdown
	 * @return array
	 */
	public function getGroupsAsOptions() {
		$org_id = $this->currentorg->org_id;
		$sql = "SELECT group_name, group_id FROM `store_management`.`groups` WHERE org_id = '$org_id'";
		$rtn = array();

		foreach ($this->db->query($sql) as $row) {
			$rtn[$row['group_name']] = $row['group_id'];
		}
		ksort($rtn);
		return $rtn;
	}

	function getCapillaryActiveStoresAsOption(){

		$org_id = 0;

		$inactive_filter = "";
		if(!$include_inactive)
			$inactive_filter = " AND is_inactive = 0 ";
			
		$sql = "SELECT store_id, username FROM `stores` WHERE org_id = '$org_id' and `tag` = 'org' $inactive_filter";
		$rtn = array();
		foreach ($this->db->query($sql) as $row) {
			$rtn[$row['username']] = $row['store_id'];
		}
		ksort($rtn);
		return $rtn;
	}

	function getInactiveStoreDetails(){

		$org_id = $this->currentorg->org_id;

		$sql = "
		SELECT `store_id`, `username`, `replace_inactive_by`
		FROM `stores`
		WHERE `org_id` = '$org_id' AND `tag` = 'org' AND `is_inactive` = 1
		ORDER BY `username` ASC";


		return $this->db->query( $sql );

	}

	function getStoresAsOptions($include_inactive = false, $tags = array('org', 'ctr'), $use_username = true ){

		$org_id = $this->currentorg->org_id;
		$inactive_filter = "";
		if(!$include_inactive)
			$inactive_filter = " AND is_inactive = 0 ";
			
		$sql = "SELECT *
		FROM `stores`
		WHERE org_id = '$org_id'
		AND `tag` IN (".Util::joinForSql($tags).")
		$inactive_filter";
		$rtn = array();
		foreach ($this->db->query($sql) as $row) {

			if( $use_username )
				$key = 'username';
			else
				//$key = CONCAT( 'firstname', ' ', 'lastname' );
				$key = 'firstname'.' '.'lastname' ;

			$rtn[$row[$key]] = $row['store_id'];
		}
		ksort($rtn);
		return $rtn;

	}

	function getStores_ZonesAsOptions(){
		$org_id = $this->currentorg->org_id;
		$sql = "SELECT zone_code, zone_id FROM `zones_hierarchy` WHERE org_id = '$org_id'";
		$rtn = array();
		$rtn['Root'] = -1;
		foreach ($this->db->query($sql) as $row) {
			$rtn[$row['zone_code']] = $row['zone_id'];
		}
		ksort($rtn);
		return $rtn;
	}

	/**
	 * @param $zones All the zones as options array
	 * @param $index The name for the FullReport Zone
	 * @return unknown_type
	 */
	function addFullReportZone($zones, $index = ''){
		if($index == '')
			$index = 'FullReport';
		$zones[$index] = -2;
		return $zones;
	}

	/**
	 * @param $zone_id The 'zone_id' for which all the zones falling under it should be retrieved
	 * @return An array of zone_id falling under 'zone_id' including 'zone_id' as well.
	 */
	function getSubZonesForZone($zone_id){

		$org_id = $this->currentorg->org_id;
		$subzones = array();

		$parents = array($zone_id);

		while(count($parents) > 0){

			//create a filter using parents
			$filter = " AND parent_id IN ( ".join(",", $parents)." )";

			//add parents to sub zones
			foreach($parents as $p)
				array_push($subzones, $p);

			//flush parents array
			$parents = array();

			//get all children of the parent zones
			$children_sql = "SELECT zone_id FROM `zones_hierarchy` WHERE org_id = '$org_id'".$filter;
			foreach ($this->db->query($children_sql) as $row) {
				array_push($parents, $row['zone_id']);
			}
		}

		sort($subzones);
		return $subzones;
	}

	/**
	 * Create a zone filter based on the name of the field that maps to a store_id.
	 * Note: Remember to add a 'stores_zone AS z' in the FROM segment of the SQL.
	 * @param $zone_selected The zone selected. Output of the select field
	 * @return A filter for addition of zone level restriction
	 */
	function createZoneFilter($zone_selected){

		$use_zones = true;
		if($zone_selected == -2){
			$use_zones = false;
		}

		$filter = " ";
		if($use_zones){
			$tempoptions = $this->getSubZonesForZone($zone_selected);
			//$filter = " AND $store_id_fieldname = z.store_id AND z.org_id = ".$this->currentorg->org_id." AND z.zone_id IN (".join(',', $tempoptions).")";
			$filter = " AND {{store_id_fieldname}} = `z`.`store_id` AND `z`.`org_id` = ".$this->currentorg->org_id." AND `z`.`zone_id` IN (".join(',', $tempoptions).")";
		}

		return $filter;
	}

	/**
	 * @param $store_id_fieldname The name of the field of the source table which will be mapped on to a store_id
	 * @param $filter The filter that was created using the zone filter
	 * @return The modified filter which puts in $store_id_fieldname in filter
	 */

	function getStoresForOrg($org_id, $add_ignore = true){

		$store = $this->db->query("SELECT `z`.`store_id` AS store,`u`.`code` AS name" .
				" FROM `user_management`.`stores_zone` AS `z`" .
				" JOIN `masters`.`org_entities` AS `u` ON `u`.`id` = `z`.`store_id` 
						AND u.is_active = 0 AND z.org_id = u.org_id and u.type = 'TILL'" .
				" WHERE `z`.`org_id` = '$org_id'" .
				" ORDER BY `u`.`code`");

		if($add_ignore)
			$store_filter['All Within Selected Zone'] = NULL;
		foreach($store as $s){

			$store_filter[$s['name']] = $s['store'];

		}

		return ($store_filter);


	}


	function getModifiedZoneFilter($store_id_fieldname, $filter){
		return str_replace("{{store_id_fieldname}}", $store_id_fieldname, $filter);
	}

	function getModifiedStoreFilter($store_id,$store_param){

		return ( " AND $store_param = '$store_id' ");

	}

	function restrictZonesForUser(&$zone_options, $user_id = ''){

		$org_id = $this->currentorg->org_id;
		if($user_id == '')
			$user_id = $this->currentuser->user_id;

		//show all zones in case user is from admin org
		if($this->currentuser->org->isAdminOrg())
			return;

		//show all zones in case user is from admin org
		$is_admin_group_user = false;
		$sql = " SELECT * FROM `store_management`.`groups` g "
				." JOIN `store_management`.`memberships` m ON g.group_id = m.group_id "
						." WHERE m.user_id = '$user_id' AND g.org_id = '$org_id'";
		$groups = $this->db->query($sql);

		//if the user is an admin for his org, s/he has full access
		foreach ($groups as $row) {
			if (strtolower($row['group_name']) == 'admin')
				$is_admin_group_user = true;
		}

		if($is_admin_group_user)
			return;


		//get the zone id for this user..
		$users_zone_id = $this->db->query_scalar("SELECT zone_id FROM stores_zone WHERE org_id = $org_id AND store_id = $user_id");

		//restrict the zones only for the subzones
		if($users_zone_id){

			//get subzones for this zone..
			$subzones = $this->getSubZonesForZone($users_zone_id);

			$zone_options_flipped = array_flip($zone_options);
			$zone_options_final = array();

			foreach($zone_options_flipped as $zone_id => $zone_code){

				//include only if the zone id is present in the sub zones
				if(in_array($zone_id, $subzones))
					$zone_options_final[$zone_code] = $zone_id;
			}

			$zone_options = $zone_options_final;

		}else{
			//user is not from admin org or in admin group or has no zones associated
			$zone_options = array();
		}
	}

	private function _generate() {

		$chars = "023456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '' ;

		while ($i <= 20) {

			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}

		return base64_encode($pass);
	}

	public static function getMonthOptions(){

		$options = array(
				'January' => '1',
				'February' => '2',
				'March' => '3',
				'April'=> '4',
				'May' => '5',
				'June' => '6',
				'July' => '7',
				'August' => '8',
				'September' => '9',
				'October' => '10',
				'November' => '11',
				'December' => '12'
		);

		return $options;

	}

	public static function getMonthOptionsTillCurrentMonth($options = false){

		$curr_month = date("F");

		if($options == false)
			$options = administrationModule::getMonthOptions();

		//allow only months till this month
		$options = array_slice($options, 0, $options[$curr_month]);

		return $options;
	}




	function inactivestoresAction(){

		$org_id = $this->currentorg->org_id;

		$users_table = $this->db->query_table("SELECT * FROM `users` WHERE `org_id` = '$org_id' AND tag = 'org' AND is_inactive = 1 order by username asc");
		$users_table->createLink('Edit', Util::genUrl('administration', 'adduser/{0}'), 'Edit', array(0 => 'id'));
		$users_table->reorderColumns(array('username', 'mobile', 'firstname', 'lastname', 'email', 'edit', 'last_login'));

		$this->data['users'] = $users_table;

	}

    ///////////////////////////////

	function createWidgetToSendMail($report_widgets){

		$STYLE = $_ENV['DH_WWW']. DIRECTORY_SEPARATOR . "cheetah" . DIRECTORY_SEPARATOR . "style" . DIRECTORY_SEPARATOR;

		$str = "<style type = 'text/css'>";
		$str .= file_get_contents("$STYLE/table_style_cheetah.css", true);
		$str .= "</style>";

		foreach ($report_widgets as $w) {

			if ($w['widget_code'] == false)
				continue;
			$str .= "<div id='widget-$w[widget_code]'>\n";
			$str .= "  <h2>$w[widget_name]</h2>\n";
			$str .= "  <div id='w-$w[widget_code]-content'>\n";
			if ($w['widget_data'] instanceof Table) {
				$w['widget_data']->extra_table_attrs = "border='1' ";
			}
			$whtml = Util::html($w['widget_data'], true);
			$str .= "     ".$whtml."\n";
			$str .= "  </div> \n";
			$str .= "</div>";
		}
		return $str;
	}



    ////////////////////////////////////








	function storereportApiAction(){

		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;

		$xml_string = <<<EOXML
<root>
	<current_version_info>
		<current_version>http://capillary.co.in/deploy_pe:1.0.0.60</current_version>
		<compile_time>15-04-2010 15:49:23</compile_time>
		<svn_revision>3975</svn_revision>
	</current_version_info>
</root>
EOXML;

		//$element = Xml::parse($xml_string);
		$xml_string = $this->getRawInput();
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		$element = Xml::parse($xml_string);
		$current_version_info = $element->xpath('/root/current_version_info');
		foreach ($current_version_info as $cvi){

			$version_num = (string) $cvi->current_version;
			$compile_time = (string) $cvi->compile_time;
			$svn_revision = (string) $cvi->svn_revision;

			$sql = "
			INSERT INTO `masters`.`store_units` (`org_id`,`id`,`client_version_num`,`last_updated_on`, `compile_time`, `svn_revision`)
			VALUES($org_id, $store_id, '$version_num', NOW(), '$compile_time', '$svn_revision')
			ON DUPLICATE KEY
			UPDATE
			`client_version_num` = '$version_num',
			`last_updated_on` = NOW(),
			`compile_time` = '$compile_time',
			`svn_revision` = '$svn_revision'
			";
			$this->db->update($sql);
		}


		$mlm_enabled = $this->getConfigurationValue(CONF_MLM_ENABLED, false);

		//compute actual dates
		$today = DateUtil::getDateAsString(strtotime("now"));
		$yesterday = DateUtil::getDateAsString(strtotime("-1 day"));
		$two_days_ago = DateUtil::getDateAsString(strtotime("-2 day"));
		
		$first_day_of_week = DateUtil::getMysqlDateTime(strtotime("last sunday"));
		$first_day_of_month = DateUtil::getMysqlDateTime(strtotime(date('m/01/y')));
		$current_date_with_start_time = DateUtil::getCurrentDateWithStartTime();
		$current_date_with_end_time = DateUtil::getCurrentDateWithEndTime();
		
		$clauses = array(
				"$today" => " {{d}} >= '$current_date_with_start_time' ".
							"AND {{d}} <= '$current_date_with_end_time' ",
				"$yesterday" => "{{d}} >= '". DateUtil::getDateByDays(true, 1, $current_date_with_start_time,"%Y-%m-%d %H:%M:%S")."'".
							"AND {{d}} <= '". DateUtil::getDateByDays(true, 1, $current_date_with_end_time,"%Y-%m-%d %H:%M:%S")."'",
				"$two_days_ago" => "{{d}} >= '". DateUtil::getDateByDays(true, 2, $current_date_with_start_time,"%Y-%m-%d %H:%M:%S")."'".
							"AND {{d}} <= '". DateUtil::getDateByDays(true, 2, $current_date_with_end_time,"%Y-%m-%d %H:%M:%S")."'",
				'this_week'	=> "{{d}} >= '$first_day_of_week' AND {{d}} >= '$first_day_of_month'", 
				'this_month' => " {{d}} >= '$first_day_of_month'"
		);

		$queries = array(
				array(
						'query' => "SELECT COUNT(*) AS count FROM loyalty l WHERE {{clause}} AND l.publisher_id = '$org_id' AND l.registered_by = $store_id",
						'd' => 'joined',
						'row_name' => '# new customers',
						'db' => 'users'),

				array (
						'query' => "SELECT COUNT(*) FROM loyalty_log l WHERE {{clause}} AND l.org_id = '$org_id' AND l.entered_by = $store_id AND l.outlier_status = 'NORMAL'",
						'd' => '`date`',
						'row_name' => '# of recorded bills',
						'db' => 'users'),

				array(
						'query'=>" SELECT  COUNT(*)
						FROM loyalty_not_interested_bills l
						WHERE l.org_id = $org_id AND l.entered_by = $store_id AND {{clause}} " ,
						'd' => '`billing_time`',
						'row_name' => '# of not interested bills',
						'db' => 'users'),


				array (
						'query' => "SELECT IFNULL(SUM(`points`),0) FROM loyalty_log l WHERE {{clause}} AND l.org_id = '$org_id' AND l.entered_by = $store_id",
						'd' => '`date`',
						'row_name' => '# Points Issued',
						'db' => 'users'),

				array (

						'query' => "SELECT IFNULL(SUM(`points_redeemed`),0) " .
						" FROM `points_redemption_summary` AS `lr` " .
						" INNER JOIN `org_participation` as op on op.org_id = lr.org_id and lr.program_id = op.program_id ".
						" WHERE {{clause}} AND `lr`.`org_id` = '$org_id' AND `lr`.`till_id` = '$store_id'",
						'd' => '`lr`.`redemption_time`',
						'row_name' => '# Points Redeemed',
						'db' => 'warehouse'),

		);


		$table_data = array();
		foreach ($queries as $query) {

			//skip if mlm is disabled
			if(!$mlm_enabled && ($query['row_name'] == '# Refs Sent' || $query['row_name'] == '# Refs Joined'))
				continue;

			$row = array('row_name' => $query['row_name']);
			$db = new Dbase($query['db'], true);
			foreach ($clauses as $int => $cl) {
				$final_query = str_replace("{{clause}}", $cl, $query['query']);
				$final_query = str_replace("{{d}}", $query['d'], $final_query);
				$res = $db->query_scalar($final_query);
				$row[$int] = $res;
			}
			array_push($table_data, $row);


		}

		$table = new Table();
		$table->importArray($table_data);
		$table->reorderColumns(array_merge(array('row_name'), array_keys($clauses)));

		//redemption details by store id and org id
		$sql = "SELECT lr.customer_id as user_id, `lr`.`bill_number` AS `redemption_bill_number`,`lr`.`points_redeemed`,`lr`.`redemption_time` AS `redemption_date`" .
				" FROM `points_redemption_summary` AS `lr` " .
				" INNER JOIN `org_participation` as op on op.org_id = lr.org_id and lr.program_id = op.program_id ".
				" WHERE `lr`.`till_id` = '$store_id' AND `lr`.`org_id` = '$org_id' AND DATE(`lr`.`redemption_time`) = DATE(NOW())";

		$warehouseDb = new Dbase("warehouse");
		$redemption_records = $warehouseDb->query($sql);
		
		if($redemption_records)
		{
			$user_ids = array();
			foreach($redemption_records  as $redemption)
				$user_ids[] = $redemption["user_id"];
				
			$sql = "SELECT `u`.`username` AS `store_login_name`, u.id as user_id,
			 		CONCAT(`u`.`firstname`, ' ',`u`.`lastname`) AS `customer_name`,`u`.`mobile` AS `customer_mobile_number`
					FROM `users` AS `u`  
					WHERE u.org_id = $org_id and id in (".implode(",", $user_ids).")";
			$userDetailsArr = $this->db->query_hash($sql, "user_id", array("customer_name", "customer_mobile_number", "store_login_name"));
			
			$data = array();
			$redemption_table = new Table($name);
			$headersSet = 0 ; 
			foreach($redemption_records  as $key=>$redemption)
			{
				$redemption_records[$key]["customer_name"] = $userDetailsArr[$redemption["user_id"]]["customer_name"];
				$redemption_records[$key]["customer_mobile_number"] = $userDetailsArr[$redemption["user_id"]]["customer_mobile_number"];
				$redemption_records[$key]["store_login_name"] = $userDetailsArr[$redemption["user_id"]]["store_login_name"];
				unset($redemption_records[$key]["user_id"]);
				
				if($headersSet == 0)
				{
					foreach ($redemption_records[$key] as $header => $value)
						$redemption_table->addHeader($header);
					
					$headersSet = 1;
				}
				array_push($data, $row);
				
			}
			
			$redemption_table->addData($data);
				
		}
		else
			$redemption_table = new Table(); 
		

		$report_widgets = array(
				'report-table'	=> array('widget_name' => "Reports Summary, ".date("F 'y"), 'widget_code' => 'report_table', 'widget_data' => $table),
				'redemption-table'	=> array('widget_name' => "Redemption Summary For, ".date("F 'y"), 'widget_code' => 'redemption_table', 'widget_data' => $redemption_table)
		);

		$this->data['output'] = $this->createWidgetToSendMail($report_widgets);

	}

	private function getConfigurationValue($conf_key, $default) {
		return $this->currentorg->getConfigurationValue($conf_key, $default);
	}

	/**
	 * Get all the settings of the organization as an XML file
	 * @return $data
	 */
	function configurationApiAction() {
		//<root>
		//<configuration>
		//  <item>
		//    <key>CONF_SOME_CONFIG</key>
		//    <value>true</value>
		//  </item>
		//</configuration>
		//</root>
		$arr = $this->currentorg->getAllConfigData('CONF');
		$country_code_config_present = false;
		$country_code_supported_config_present = false;


		foreach ($arr as $index => $kv) {
			if ( ($kv['key'] == CONF_MLM_SLAB_POINTS_PERCENTAGE)
					|| ($kv['key'] == CONF_REPORT_EMAIL_HEADERS)
					|| ($kv['key'] == CONF_SUMMARY_REPORT_EMAIL_HEADERS)
					|| ($kv['key'] == CONF_BILL_IMPORT_FIELD_MAPPING)
					|| ($kv['key'] == CONF_BILL_IMPORT_FIELD_NAME_MAPPING)
					|| ($kv['key'] == CONF_BILL_IMPORT_STORE_MAPPING)
					|| ($kv['key'] == CONF_INVENTORY_FIELD_MAPPING)
					|| ($kv['key'] == CONF_INVENTORY_FIELD_NAME_MAPPING)
					|| ($kv['key'] == CONF_MONDRIAN_STORE_DIMENSION_ROOT)
					|| ($kv['key'] == CONF_ANALYSIS_PRODUCT_CATEGORY_ATTRIBUTE)
					|| ($kv['key'] == CONF_LOYALTY_POINTS_EXPIRY_REMINDER_SMS_TEMPLATE)
					|| ($kv['key'] == CONF_TRACKER_REPORTING_EMAIL)
					|| ($kv['key'] == CONF_TRACKER_PRIORITY_REPORTING_EMAIL)
			)
				$arr[$index]['value'] = '';
			
			if($kv['key'] == CONF_CLIENT_POINT_TO_CURRENCY_RATIO)
				unset($arr[$index]);
			
			if($kv['key'] == CONF_CLIENT_DEFAULT_COUNTRY)
				$country_code_config_present = true;

			if($kv['key'] == CONF_CLIENT_INTERNATIONAL_COUNTRIES_SUPPORTED)
				$country_code_supported_config_present = true;
			
			if($kv['key'] == CONF_LOYALTY_IS_REDEMPTION_VALIDATION_REQUIRED)
			{
				$ConfigManager = new ConfigManager();
				$arr[$index]['value'] = $ConfigManager->getKey(CONF_LOYALTY_IS_REDEMPTION_VALIDATION_REQUIRED, true);
			}
		}


		//Send 91 by default for country code
		if(!$country_code_config_present)
			array_push($arr, array('key' => CONF_CLIENT_DEFAULT_COUNTRY, 'value' => '1'));
		//Send India as default supported country code
		if(!$country_code_supported_config_present)
			array_push($arr, array('key' => CONF_CLIENT_INTERNATIONAL_COUNTRIES_SUPPORTED, 'value' => json_encode(array('1'))));

		//points to currency ratio
		try{
                        $this->logger->debug("Fetching points to currency ratio from points engine ");
                        $key = 'o'.$this->currentorg->org_id.'_'.CacheKeysPrefix::$pointsToCurrencyRatio.$this->currentorg->org_id;	
                        include_once "business_controller/points/PointsEngineServiceController.php";
                        $pointsEngineServiceController = new PointsEngineServiceController();
			$points_to_currency_ratio = $pointsEngineServiceController->getPointsCurrencyRatio($this->currentorg->org_id);
                        $this->logger->debug("Fetching points to currency ratio from points engine fetched".print_r($points_to_currency_ratio,true));
                        array_push( $arr, array(   'key' => CONF_CLIENT_POINT_TO_CURRENCY_RATIO,
                                                'value' => $points_to_currency_ratio ) );
                        
		} catch (Exception $e){
                        $this->logger->debug("Fetching points to currency ratio from points engine failed");	
		}
		
		//this will return extra configs like current store name and id
		global $currentuser;
		$storeController = new ApiStoreController();
		$base_store_id = $storeController->getBaseStoreId();
		$store_details = $storeController->getInfoDetails($base_store_id);

		//Sending base store name
		array_push($arr, array('key' => CONF_CLIENT_STORE_NAME, 'value' => $store_details[0]['store_name'] ));

		//Sending base store id
		array_push($arr, array('key' => CONF_CLIENT_STORE_ID, 'value' => $base_store_id ));
		
		$this->data['configuration'] = $arr;
	}

	function downloadFileAction($id) {
		$sf = new StoredFiles($this->currentorg);
		$sf->downloadFile($id);
	}

	function getDataProvidersFileIdForUser($user_id)
	{
		$sf = new StoredFiles($this->currentorg);
		return $sf->getDataProvidersFileIdForStore($user_id);
	}

	function dataProvidersFileApiAction() {
		$this->downloadFileAction($this->getDataProvidersFileIdForUser($this->currentuser->user_id));
	}


	function getPrinterTemplateIdForuser($user_id,$templateType){

		$sf = new StoredFiles($this->currentorg);
		return $sf->getPrinterTemplateIdForStore($user_id, $templateType);
	}

	//=============
	//Client Log Config

	function getClientLogConfigFileIdForStore($store_id)
	{
		$sf = new StoredFiles($this->currentorg);
		return $sf->getClientLogConfigFileIdForStore($store_id);
	}

	//=============

	//=============
	//Lego Properties

	function getLegoPropertiesFileIdForUser($user_id)
	{
		$sf = new StoredFiles($this->currentorg);
		return $sf->getLegoPropertiesFileIdForStore($user_id);
	}

	function legopropertiesfileAction() {

		$org_id = $this->currentorg->org_id;

		$sf = new StoredFiles($this->currentorg);

		$uploadForm = new Form('file_upload', 'post');
		$uploadForm->addField('text', 'tag', 'Tag For File', '', '', '/\w+/', 'Only Alphanumeric allowed');
		$uploadForm->addField('file', 'lego_properties_file', 'Lego Properties file');
		if ($uploadForm->isValidated()) {
			$params = $uploadForm->parse();
			$file = $uploadForm->getFile('lego_properties_file');
			$tmp_name = $file['tmp_name'];
			$contents = file_get_contents($tmp_name);
			$safe_contents = $this->db->realEscapeString($contents);
			$uid = $this->currentuser->user_id;
			$this->logger->debug("File Name : $tmp_name, Contents : $contents, SafeContents : $safe_contents");
			$file_id = $sf->storeFile(STORED_FILE_TAG_LEGO_PROPERTIES, $safe_contents, "LegoProperties-".$params['tag'], "properties", $this->currentuser->user_id);
		}

		$files_table = $sf->listFilesByTag(STORED_FILE_TAG_LEGO_PROPERTIES);
		$files_table->createLink('Download', Util::genUrl('administration', "downloadFile/{0}"), 'download', array(0 => 'id'));
		$files_table->createLink('Delete', Util::genUrl('administration', "deleteFile/{0}/legopropertiesfile"), 'delete', array(0 => 'id'));

		$file_mapping_table = $sf->getLegoPropertiesFileMappingTable();

		$form = new Form('download_details');
		$form->addField('checkbox','download','Download Lego Properties Table');
		if($form->isValidated()){
			$params = $form->parse();
			$download = $params['download'];
			if($download){

				$spreadsheet = new Spreadsheet();
				$spreadsheet->loadFromTable($file_mapping_table)->download("lego_properties_info", 'csv');

			}
		}

		$this->data['download_form'] = $form;
		$this->data['upload_form'] = $uploadForm;
		$this->data['files_table'] = $files_table;
		$this->data['file_mapping_table'] = $file_mapping_table;
	}


	function getPrinterTemplateFileIdForUser($user_id,$templateType)
	{
		$sf = new StoredFiles($this->currentorg);
		return $sf->getPrinterTemplateIdForStore($user_id, $templateType);
	}

	function printerTemplateFileApiAction($templateType) {

		$this->downloadFileAction($this->getPrinterTemplateFileIdForUser($this->currentuser->user_id,$templateType));

	}

	//---end printer

	function integrationoutputdashboardAction()
	{
		$org_id = $this->currentorg->org_id;

		$links = array(
				'Integration Output Handling' => '<a href='.Util::genUrl('administration', 'integrationoutputtemplatefile').'> Click Here </a>',
				'Post Integration Output Handling' => '<a href='.Util::genUrl('administration', 'integrationpostoutputfile').'> Click Here </a>'
		);
		$links_table = new Table('table');
		$links_table->importFromKeyValuePairs($links,'Links','Link');
		$this->data['links'] = $links_table;
	}


	//start - integration output

	public function getintegrationoutputtemplatetypes()
	{
		//DO NOT CHANGE
		return
		array(
				'points_redemption',
				'voucher_redemption',
				'voucher_issue',
				'customer_register',
				'customer_update',
				'bill_submit'
		);
	}

	function integrationoutputtemplatefileAction() {


		$org_id = $this->currentorg->org_id;

		$sf = new StoredFiles($this->currentorg);

		$uploadForm = new Form('file_upload', 'post');
		//DO NOT CHANGE
		$type_options = $this->getintegrationoutputtemplatetypes();
		$uploadForm->addField('select', 'type', 'Type', '', array('list_options' => $type_options));
		$uploadForm->addField('text', 'tag', 'Tag For File', '', '', '/\w+/', 'Only Alphanumeric allowed');
		$uploadForm->addField('file', 'i_file', 'Integration Output Template file');

		if ($uploadForm->isValidated()) {

			$params = $uploadForm->parse();
			$file = $uploadForm->getFile('i_file');
			$tmp_name = $file['tmp_name'];
			$contents = file_get_contents($tmp_name);
			$safe_contents = $this->db->realEscapeString($contents);
			$uid = $this->currentuser->user_id;
			$this->logger->debug("File Name : $tmp_name, Contents : $contents, SafeContents : $safe_contents");
			$file_tag = STORED_FILE_TAG_INTEGRATION_OUTPUT_TEMPLATE."_".$params['type'];
			$file_id = $sf->storeFile($file_tag, $safe_contents, $file_tag."_".$params['tag'], "txt", $this->currentuser->user_id);

		}

		$files_table = $sf->listFilesByTag(STORED_FILE_TAG_INTEGRATION_OUTPUT_TEMPLATE, true);
		$files_table->createLink('Download', Util::genUrl('administration', "downloadFile/{0}"), 'download', array(0 => 'id'));
		$files_table->createLink('Delete', Util::genUrl('administration', "deleteFile/{0}/integrationoutputtemplatefile"), 'delete', array(0 => 'id'));

		$file_mapping_table = $sf->getIntegrationOutputTemplateFileMappingTable();

		$this->data['upload_form'] = $uploadForm;
		$this->data['files_table'] = $files_table;
		$this->data['file_mapping_table'] = $file_mapping_table;
	}

	function getIntegrationOutputTemplateFileIdForUser($user_id, $templateType)
	{
		$sf = new StoredFiles($this->currentorg);
		return $sf->getIntegrationOutputTemplateFileIdForStore($user_id, $templateType);
	}

	function IntegrationOutputTemplateFileApiAction($templateType) {

		$this->downloadFileAction($this->getIntegrationOutputTemplateFileIdForUser($this->currentuser->user_id,$templateType));

	}

	//end - integration output


	//start - integration post output

	public function getintegrationpostoutputtypes()
	{
		return
		array(
				'points_redemption',
				'voucher_redemption',
				'voucher_issue',
				'customer_register',
				'customer_update',
				'bill_submit',
				'auto_configure',
				'nightly_sync',
				'eod_sync',
				'pre_auto_configure',
				'pre_nightly_sync',
				'pre_eod_sync',
				'os_startup'
		);
	}

	public function getclientfilemonitoringtypes()
	{
		return array(
				'FILE_CHECK',
				'PROCESS_CHECK'
		);

	}

	function getIntegrationPostOutputFileIdsForUser($store_id, $templateType, $file_name_column = 'file_name')
	{

		$sf = new StoredFiles($this->currentorg);
		return $sf->getPostIntegrationOutputFileIdsForStore($store_id, $templateType, $file_name_column);
	}

	//end - integration output


	//start client cron

	function clientcrondashboardAction()
	{
		$org_id = $this->currentorg->org_id;

		$links = array(
				'Client Cron Files' => '<a href='.Util::genUrl('administration', 'clientcronsyncfiles').'> Click Here </a>',
				'Client Cron Entries' => '<a href='.Util::genUrl('administration', 'clientcronentries').'> Click Here </a>'
		);
		$links_table = new Table('table');
		$links_table->importFromKeyValuePairs($links,'Links','Link');
		$this->data['links'] = $links_table;
	}

	function clientcronsyncfilesAction()
	{

		$org_id = $this->currentorg->org_id;

		$sf = new StoredFiles($this->currentorg);

		$uploadForm = new Form('file_upload', 'post');
		$uploadForm->addField('text', 'tag', 'Tag For File', '', '', '/\w*/', 'Only Alphanumeric allowed');
		$uploadForm->addField('text', 'client_file_name', 'File Name at client', '', array('help_text' => 'This is the filename with which the client will save (Leave out the extension)'), '/\w+/', 'Only Alphanumeric allowed');
		$filetypes = array_keys($sf->mime_types_by_extension);
		$uploadForm->addField('select', 'extension', 'Extension', 'exe', array('list_options' => $filetypes));
		$uploadForm->addField('file', 'c_file', 'Client Cron file');

		if ($uploadForm->isValidated()) {

			$params = $uploadForm->parse();
			$file = $uploadForm->getFile('c_file');
			$tmp_name = $file['tmp_name'];
			$contents = file_get_contents($tmp_name);
			$safe_contents = $this->db->realEscapeString($contents);
			$uid = $this->currentuser->user_id;
			$this->logger->debug("File Name : $tmp_name, Contents : $contents, SafeContents : $safe_contents");

			$tag = "_".$params['tag'];

			if(strlen($params['tag']) == 0)
				$tag = "";

			$client_file_name = $params['client_file_name'];

			$file_tag = STORED_FILE_TAG_CLIENT_CRON;

			$file_id = $sf->storeFile($file_tag, $safe_contents, $file_tag.$tag, $params['extension'], $this->currentuser->user_id, '', $client_file_name);
		}

		$files_table = $sf->listFilesByTag(STORED_FILE_TAG_CLIENT_CRON, false, true);
		$files_table->createLink('Download', Util::genUrl('administration', "downloadFile/{0}"), 'download', array(0 => 'id'));
		$files_table->createLink('Delete', Util::genUrl('administration', "deleteFile/{0}/clientcronsyncfiles"), 'delete', array(0 => 'id'));

		$this->data['upload_form'] = $uploadForm;
		$this->data['files_table'] = $files_table;

	}

	function clientcronaddentryAction($cron_type, $cron_entry_id = false)
	{
		$addForm = new Form('add_client_cron', 'post');

		$clientCronMgr = new ClientCronMgr();
		$clientCronMgr->getClientCronForm($addForm, $cron_type, $cron_entry_id);


		if($addForm->isValidated()){

			$params = $addForm->parse();

			$cron_id = $clientCronMgr->addOrUpdateClientCronEntryFromFormParams($params);

			$status = $cron_id ? "Added" : "Error";

			Util::redirect('administration', 'clientcronentries', false, $status);
		}

		$this->data['add_form'] = $addForm;
	}

	function clientcronentriesAction()
	{

		//add form
		$addForm = new Form('add_client_cron', 'post');
		$addForm->addField('select', 'client_cron_type', 'Select Client Cron Type', '', array('list_options' => ClientCronMgr::getAllowedClientCronTypes()));
		if($addForm->isValidated()){

			$params = $addForm->parse();
			Util::redirect('administration', 'clientcronaddentry/'.$params['client_cron_type']);

		}
		$this->data['add_form'] = $addForm;



		//Show the cron entries table
		$clientCronMgr = new ClientCronMgr();
		$cronentries_table = $clientCronMgr->getClientCronEntriesTable();
		$cronentries_table->createLink('Edit', Util::genUrl('administration', 'clientcronaddentry/{0}/{1}'), 'edit', array( 0 => 'cron_type', 1 => 'id'));
		$cronentries_table->removeHeader('org_id');
		$cronentries_table->removeHeader('enabled_at_stores_json');
		$this->data['cron_entries_table'] = $cronentries_table;
	}

	function getclientcronentriesApiAction()
	{

		$clientCronMgr = new ClientCronMgr();

		//Set the last modified date
		$this->data['cron_entries_last_modified'] = $clientCronMgr->getLastModifiedCronEntryDate();

		//Add the cron entries to the output
		$this->data['client_cron_entries'] = array();

		//For each of the cron entries,
		$cron_entries_data = $clientCronMgr->getClientCronEntries();

		if(!$cron_entries_data || count($cron_entries_data) == 0)
			return;

		foreach($cron_entries_data as $cron_entry_data)
		{

			//Add xml fragments requierd
			$clientCronType = ClientCronMgr::createClientCronType($cron_entry_data['cron_type']);
			$clientCronType->addXmlFragmentsForApi($cron_entry_data);

			//unset unwanted fields
			unset($cron_entry_data['org_id']);
			unset($cron_entry_data['cron_params']);
			unset($cron_entry_data['created_by']);
			unset($cron_entry_data['created_on']);
			unset($cron_entry_data['last_updated_by']);
			unset($cron_entry_data['last_updated_on']);

			//Add it to the output
			array_push($this->data['client_cron_entries'], $cron_entry_data);
		}
	}

	//end client cron




	//Download File
	function downloadfilebyidApiAction($file_id){
		$sf = new StoredFiles($this->currentorg);
		$sf->downloadFile($file_id);
	}

	//Download
	function legopropertiesfileApiAction()
	{
		$file_id = $this->getLegoPropertiesFileIdForUser($this->currentuser->user_id);
		$this->logger->debug("Found file id: $file_id");
		$this->downloadfilebyidApiAction($file_id);
	}

	/**
	 * Log file Upload
	 *
	 */
	function uploadClientLogFileApiAction(){

		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;

		$xml_string = <<<EOXML
<root>
	<log_details>
		<log_text>
			the text for the client log file will be here .
		</log_text>
	</log_details>
</root>
EOXML;
		//$element = Xml::parse($this->getRawInput());
		$element = Xml::parse($xml_string);

		$client_error_log = $element->xpath( '/root/log_details' );
		foreach( $client_error_log as $client_err_log ){

			$error_log = (string)$client_err_log->log_text ;
		}

		include_once 'apiController/ApiFileController.php';
		$FileController = new ApiFileController();

		$contents = $error_log;

		//file & mapping id
		$file_id = $FileController->uploadClientLogFile( $contents, ' : client' );
		$mapping_id = $FileController->updateClientLogStoreFileMapping( $file_id );

		$api_status= array();
		$upload_log_file_status = "";

		if( $file_id ){

			$upload_log_file_status = 'SUCCESS';
			$api_status = array(

					'key' => getResponseErrorKey( ERR_RESPONSE_SUCCESS ),
					'message' => getResponseErrorMessage( ERR_RESPONSE_SUCCESS )
			);
		}else{

			$upload_log_file_status = 'FAILURE';
			$api_status = array(

					'key' => getResponseErrorKey( ERR_RESPONSE_FAILURE ),
					'message' => getResponseErrorMessage( ERR_RESPONSE_FAILURE )
			);
		}

		$this->data['api_status'] = $api_status;
		$this->data['upload_log_file_status'] = $upload_log_file_status;
	}


	function getDataprovidersForOrgApiAction() {

		include_once 'apiController/ApiFileController.php';
		$FileController = new ApiFileController( );

		$this->data['responses'] = $FileController->returnFilesByTag( STORED_FILE_TAG_DATAPROVIDER, false );
	}



	function getLatestRuleInfo($tag = ''){

		if($tag == '') return array();

		$org_id = $this->currentorg->org_id;

		$sql = "SELECT version, file_id FROM rules_version WHERE org_id = $org_id AND file_tag = '$tag' ORDER BY id DESC LIMIT 0,1";
		$res = $this->db->query_firstrow($sql);
		if(!$res) return array();

		$out = array();
		array_push($out, $res['version']);
		array_push($out, $res['file_id']);
		return $out;
	}

	function rulePackagesAction () {

		$org_id = $this->currentorg->org_id;
		$sf = new StoredFiles($this->currentorg);

		$form = new Form('package', 'post');
		$form->addField('text', 'voucher_issue', 'Voucher Issue Rules package URL', $this->currentorg->get(RULES_PACKAGE_ISSUE), array('size' => 100), '', '');
		$form->addField('file', 'voucher_issue_file', 'Voucher Issue Rules package FILE');
		$form->addField('text', 'voucher_redeem', 'Voucher Redeem Rules package URL', $this->currentorg->get(RULES_PACKAGE_REDEEM), array('size' => 100), '', '');
		$form->addField('file', 'voucher_redeem_file', 'Voucher Redeem Rules package FILE');
		if ($form->isValidated()) {
			$params = $form->parse();

			$file = $form->getFile('voucher_issue_file');
			$tmp_name = $file['tmp_name'];
			if ($tmp_name)
				$safe_contents = $this->db->realEscapeString(file_get_contents($tmp_name));
			else
				$safe_contents = $this->db->realEscapeString(file_get_contents($params['voucher_issue'], FILE_BINARY));
			$file_id = $sf->storeFile(STORED_FILE_TAG_ISSUE_RULES_PACKAGE, $safe_contents, "RulesPackage-issue-".$this->currentorg->org_id, "pkg", $this->currentuser->user_id);
			//store the latest version info
			$file_tag = STORED_FILE_TAG_ISSUE_RULES_PACKAGE;
			list($curr_latest_version, $curr_latest_file_id) = $this->getLatestRuleInfo($file_tag);
			$version = $curr_latest_version + 1;
			$sql = "INSERT INTO `rules_version` (org_id, file_tag, version, file_id) VALUES ($org_id, '$file_tag', $version, $file_id)";
			if(!$this->db->insert($sql)){
				$this->flash("Error updating latest version info for $file_tag, version : $version");
				return;
			}
			$status = "$file_tag : version $version, file_id $file_id .";


			$file = $form->getFile('voucher_redeem_file');
			$tmp_name = $file['tmp_name'];
			if ($tmp_name)
				$safe_contents = $this->db->realEscapeString(file_get_contents($tmp_name));
			else
				$safe_contents = $this->db->realEscapeString(file_get_contents($params['voucher_redeem']));
			$file_id = $sf->storeFile(STORED_FILE_TAG_REDEEM_RULES_PACKAGE, $safe_contents, "RulesPackage-redeem-".$this->currentorg->org_id, "pkg", $this->currentuser->user_id);
			//store the latest version info
			$file_tag = STORED_FILE_TAG_REDEEM_RULES_PACKAGE;
			list($curr_latest_version, $curr_latest_file_id) = $this->getLatestRuleInfo($file_tag);
			$version = $curr_latest_version + 1;
			$sql = "INSERT INTO `rules_version` (org_id, file_tag, version, file_id) VALUES ($org_id, '$file_tag', $version, $file_id)";
			if(!$this->db->insert($sql)){
				$this->flash("Error updating latest version info for $file_tag, version : $version");
				return;
			}
			$status .= "$file_tag : version $version, file_id $file_id .";

			$ConfigManager = new ConfigManager();
			$ConfigManager->setKeyValue( RULES_PACKAGE_ISSUE, array( 'entity_id' => -1, 'value' => $params['voucher_issue'] ) );
			$ConfigManager->setKeyValue( RULES_PACKAGE_REDEEM, array( 'entity_id' => -1, 'value' => $params['voucher_redeem'] ) );

			$this->flash("Successfully saved. $status");
		}
		//$this->data['packages_form'] = $form;

		$table = new Table('dvs');
		$data = $sf->listFilesByTag(STORED_FILE_TAG_RULES_SEARCH);
		$table->importArray( $data );
		$table->createLink('Download', Util::genUrl('administration', 'downloadfile/{0}'), 'Download', array(0 => 'id'));
		$this->data['packages_links'] = $table;
	}

	/**
	 * Get the rule package to be used for various purposes
	 * @param $type - DVS_ISSUE | DVS_REDEEM
	 * @return download file
	 */
	function rulePackagesApiAction($type) {
		$sf = new StoredFiles($this->currentorg);
		if ($type == "DVS_ISSUE") {
			$tag = STORED_FILE_TAG_ISSUE_RULES_PACKAGE;
		} else if ($type == "DVS_REDEEM") {
			$tag = STORED_FILE_TAG_REDEEM_RULES_PACKAGE;
		} else {
			die("Invalid Tag");
		}

		list($latest_version, $latest_file_id) = $this->getLatestRuleInfo($tag);

		//$file_id = $sf->getFileByTag($tag);
		//send the latest version
		$file_id = $latest_file_id;

		$sf->downloadFile($file_id);
	}



	function inventoryaddattributeAction($id = ''){

		$org_id = $this->currentorg->org_id;
		$name = '';
		$is_enum = false;
		$use_in_dump = true;
		$extraction_rule_type = "";
		$extraction_rule_data_json = "";
		$datatype = 'String';
		$is_soft_enum = false;

		$extractionrules_options = $this->inventory->allowedRules();
		$typeoptions = array('Boolean' => 'Boolean', 'Int' => 'Int', 'Double' => 'Double', 'String' => 'String');

		if($id != '' ){
			$res = $this->inventory->getAttributeById($id);
			$name = $res['name'];
			$is_enum = $res['is_enum'];
			$use_in_dump = $res['use_in_dump'];
			$extraction_rule_type = $res['extraction_rule_type'];
			$extraction_rule_data_json = $res['extraction_rule_data'];
			$datatype = $res['type'];
			$is_soft_enum = $res['is_soft_enum'];
		}

		$form = new Form('add_attribute', 'post');
		$form->addField('text', 'name', 'Name', $name, array('help_text' => 'Only alphanumeric, no Spaces'));
		$form->addField('checkbox', 'is_enum', 'Is Enum valued ?', $is_enum, array('help_text' => 'Does not allow new attribute value creation, provides selection box while searching'));
		$form->addField('checkbox', 'is_soft_enum', 'Is Soft Enum ?', $is_soft_enum, array('help_text' => 'Allows creation of non existent attribute values, but provides selection box while searching ( Is Enum should be unchecked )'));
		$form->addField('checkbox', 'use_in_dump', 'Use in InventoryDump / Sync to Client ?', $use_in_dump, array('help_text' => 'Send to Client / Used in DVS ? Include this attribute for Inventory Dump ?'));
		$form->addField('select', 'type', 'Type', $datatype, array('list_options' => $typeoptions));
		$form->addField('select', 'extraction_rule_type', 'Extraction Rule Type', $extraction_rule_type, array('list_options' => $extractionrules_options));

		//Get default value if in attribute creation
		if($id == ''){
			$form->addField('text', 'default_value_name', 'Default Value Name');
			$form->addField('text', 'default_value_code', 'Default Value Code');
		}

		$this->data['add_attribute_form'] = $form;

		$customizeForm = new Form('attribute_extraction', 'post');

		if($form->isValidated()){
			$params = $form->parse();

			$type = $params['extraction_rule_type'];

			$extraction_rule_data = ($id != '') ? json_decode($extraction_rule_data_json, true) : $extraction_rule_data_json;

			//add the config options
			$this->inventory->addConfigOptionsToForm($customizeForm, $type, $extraction_rule_data);

			//add the params as a hidden field in customize form
			$params['name'] = preg_replace('/\s/', '', $params['name']);
			$customizeForm->addField('text', 'name', 'Name', $params['name'], array('readonly' => 'readonly'));
			$customizeForm->addField('text', 'is_enum', 'Is Enum valued', $params['is_enum'], array('readonly' => 'readonly'));
			$customizeForm->addField('text', 'is_soft_enum', 'Is Soft Enum', $params['is_soft_enum'], array('readonly' => 'readonly'));
			$customizeForm->addField('text', 'use_in_dump', 'Use in InventoryDump', $params['use_in_dump'], array('readonly' => 'readonly'));
			$customizeForm->addField('text', 'type', 'Type', $params['type'], array('readonly' => 'readonly'));
			$customizeForm->addField('text', 'extraction_rule_type', 'RuleType ', $params['extraction_rule_type'], array('readonly' => 'readonly'));

			if($id == ''){
				$customizeForm->addField('text', 'default_value_name', 'Default Value Name', $params['default_value_name'], array('readonly' => 'readonly'));
				$customizeForm->addField('text', 'default_value_code', 'Default Value Code', $params['default_value_code'], array('readonly' => 'readonly'));
			}

			$this->data['customize_form'] = $customizeForm;

			$form->disableSubmit();
		}

		if($customizeForm->isValidated()){

			$cust_params = $customizeForm->parse();

			$name = $cust_params['name'];
			$is_enum = $cust_params['is_enum'];
			$is_soft_enum = $cust_params['is_soft_enum'];
			$use_in_dump = $cust_params['use_in_dump'];
			$datatype = $cust_params['type'];
			$extraction_rule_type = $cust_params['extraction_rule_type'];


			$extraction_rule_data = $this->inventory->processConfigOptionsFromForm($cust_params, $extraction_rule_type);

			//validate the rules
			$res = $this->inventory->validateInput($extraction_rule_data, $extraction_rule_type);
			if(strlen($res) > 0){
				Util::redirect('administration', 'inventoryaddattribute', false, $res);
			}

			$res = "";
			if($id == ''){

				$res = $this->inventory->getAttributeByName($name);

				if($res)
					Util::redirect('administration', 'inventoryaddattribute', false, "Attribute $name already exists");

				$val_name = $cust_params['default_value_name'];
				$val_code = $cust_params['default_value_code'];

				$res = $this->inventory->createAttribute($name, $is_enum, $extraction_rule_type, $extraction_rule_data, $datatype, $is_soft_enum, $use_in_dump);

				if(!$res)
					Util::redirect('administration', 'inventoryaddattribute', false, "Unable to add Attribute $name");

				//Insert the default value and update
				$val_id = $this->inventory->createAttributeValue($res, $val_name, $val_code);

				if(!$val_id)
					Util::redirect('administration', 'inventoryaddattributevalue/'.$res.'/1', false, "Unable to add default value for Attribute $name");

				//mark as default
				$this->inventory->setAttributeValueasDefault($res, $val_id);

			}else{
				$res = $this->inventory->updateAttribute($id, $name, $is_enum, $extraction_rule_type, $extraction_rule_data, $datatype, $is_soft_enum, $use_in_dump);
			}

			$action = ($id == '') ? "Adding" : "Updation";
			$status = (!$res) ? "Failed" : "Success";
			Util::redirect('administration', 'inventoryaddattribute', false, "$action $status");
		}


		function printruledesc(array $row, $params){
			return $params['inventory']->getPrintableDesc(json_decode($row['extraction_rule_data'], true), $row['extraction_rule_type']);
		}

		function editlink(array $row, $params){
			$url = Util::genUrl('administration', 'inventoryaddattribute/'.$row['id']);
			if($row['extraction_rule_type'] == 'SWITCH')
				$url = Util::genUrl('administration', 'editswitchattribute/'.$row['id']);
			return '<a href="'.$url.'">edit</a>';
		}

		$table = $this->inventory->getAttributes('query_table');
		$table->humanifyColumn('is_enum');
		$table->humanifyColumn('is_soft_enum');
		$table->removeHeader('use_in_dump');
		$table->humanifyColumn('sync_to_client');
		$table->addFieldByMap('Description', 'printruledesc', array('inventory' => $this->inventory), 'rule_description');
		$table->createLink('Values', Util::genUrl('administration', 'inventoryaddattributevalue/{0}/0'), 'values', array('0' => 'id'));
		$table->addFieldByMap('Edit', 'editlink', array(), 'edit');
		$table->createLink('Delete', Util::genUrl('administration', 'inventorydelattribute/{0}'), 'delete', array('0' => 'id'));
		$table->removeHeader('id');
		$table->removeHeader('extraction_rule_data');
		$this->data['attributes_table'] = $table;
	}

	function inventorydelattributeAction($id = ''){

		if($id == '')
			Util::redirect('administration', 'inventoryaddattribute', false, "Invalid Attribute");

		$org_id = $this->currentorg->org_id;

		//check if such an attribute id exists
		$check = $this->inventory->getAttributeById($id);
		if(!$check){
			Util::redirect('administration', 'inventoryaddattribute', false, "Invalid attribute");
			return;
		}

		$form = new Form('del_attribute', 'post');
		$form->addField('checkbox', 'drop', 'Drop Attribute "'.$check['name'].'" ?', false, array('help_text' => 'Removes all the attribute values. Deletes the attribute entries from each item as well'));
		$this->data['drop_form'] = $form;

		if($form->isValidated()){

			$params = $form->parse();

			if(!$params['drop']){
				Util::redirect('administration', 'inventoryaddattribute', false, "Drop not selected");
			}else{

				$this->inventory->removeAttributeById($id);

				Util::redirect('administration', 'inventoryaddattribute', false, "Attribute removed");
			}
		}
	}

	function inventoryaddattributevalueAction($attribute_id = '', $mark_as_default = 0, $id = ''){

		$org_id = $this->currentorg->org_id;

		if($attribute_id == ''){
			$this->flash("Invalid attribute");
			return;
		}

		//check if such an attribute id exists
		$check = $this->inventory->getAttributeById($attribute_id);
		if(!$check){
			Util::redirect('administration', 'inventoryaddattribute', false, "Invalid attribute");
			return;
		}

		$val_name = '';
		$val_code = '';

		if($id != '' ){
			$res = $this->inventory->getAttributeValueById($id);
			if(!$res){
				Util::redirect('administration', 'inventoryaddattribute', false, "Invalid attribute Value");
				return;
			}
			$val_name = $res['value_name'];
			$val_code = $res['value_code'];
		}

		$form = new Form('package', 'post');
		$form->addField('text', 'val_name', 'Value Name', $val_name);
		$form->addField('text', 'val_code', 'Value Code', $val_code);

		$this->data['add_attribute_value_form'] = $form;

		if($form->isValidated()){
			$params = $form->parse();
			$val_name = $params['val_name'];
			$val_code = $params['val_code'];

			//val name and code cannot be empty
			if($val_code == '' || $val_name == ''){
				Util::redirect('administration', "inventoryaddattributevalue/$attribute_id/$mark_as_default", false, "Attribute value/name cannot be empty");
			}

			if($id == ''){

				$res = $this->inventory->getAttributeValueByValueCode($attribute_id, $val_code);

				if(!$res)
					$id = $this->inventory->createAttributeValue($attribute_id, $val_name, $val_code);
				else
					Util::redirect('administration', "inventoryaddattributevalue/$attribute_id/$mark_as_default", false, "Attribute value code already exits");
			}else{
				$this->inventory->updateAttributeValue($id, $val_name, $val_code);
			}

			$status = "Added/Updated Successfully";

			//check if it has to be marked as default..
			if($mark_as_default){

				$this->inventory->setAttributeValueasDefault($attribute_id, $id);

				$status .= ". Marked as Default";
			}

			Util::redirect('administration', "inventoryaddattributevalue/$attribute_id", false, $status);

		}

		$table = $this->inventory->getAttributeValuesTableForAttribute($attribute_id);
		$table->humanifyColumn('is_attribute_enum');
		$table->humanifyColumn('is_default');
		$table->createLink('Edit', Util::genUrl('administration', 'inventoryaddattributevalue/{0}/0/{1}'), 'edit', array('0' => 'attrib_id', '1' => 'attribval_id'));
		$table->createLink('Mark Default', Util::genUrl('administration', 'inventoryaddattributevalue/{0}/1/{1}'), 'make default', array('0' => 'attrib_id', '1' => 'attribval_id'));
		$table->createLink('Delete', Util::genUrl('administration', 'inventorydelattributevalue/{0}/{1}'), 'delete', array('0' => 'attrib_id', '1' => 'attribval_id'));
		$table->reorderColumns(array('attribval_id', 'value_name', 'value_code', 'is_attribute_enum', 'is_default', 'attribute_name', 'edit', 'mark_default', 'delete'));


		//add checkbox for selection
		$table->addCheckbox('attribval_id', false, false);
		$this->data['attributesvalues_table'] = $table;

		//create a form for the updation of value
		$move_form = new Form('move_form');
		if($table->checkboxSubmitted()){

			$selected_attrib_vals = $table->getCheckedValues();

			$attribute_value_options = "";
			$res = $this->inventory->getAttributeValuesForAttribute($attribute_id, 'query');
			foreach($res as $row){
				$attribute_value_options[$row['value_name']] = $row['id'];
			}

			$move_form->addField('select', 'move_to_attrib_val', 'Move to Value', $attribute_value_options);
			$move_form->addField('text', 'selected_attrib_vals_csv', 'SelectedAttribVals', implode(',', $selected_attrib_vals), array('readonly' => true));

			$this->data['move_form'] = $move_form;
		}

		if($move_form->isValidated()){

			$params = $move_form->parse();

			//get the select attribute values..
			$selected_attrib_vals_csv = $params['selected_attrib_vals_csv'];

			//get the final attribute values
			$new_attrib_val = $params['move_to_attrib_val'];

			//update all the items with the selected attribvals to the new one
			$this->inventory->changeAttributeValue($attribute_id, explode(',', $selected_attrib_vals_csv), $new_attrib_val);

		}

		//form to remove unused values
		$clean_form = new Form('clean_unused', 'post');
		$clean_form->addField('checkbox', 'clean_sure', 'Remove all attribute values which have no values ?', false, array('help_text' => 'Removes all attribute selected values which do not have any item associated with them'));
		//get the unused values
		$unused_attrib_vals = $this->inventory->getUnusedAttributeValuesForAttribute($attribute_id);
		$unused_attribute_value_options = array();

		foreach($unused_attrib_vals as $row){
			$unused_attribute_value_options[$row['value_name']] = $row['id'];
		}
		$clean_form->addField('select', 'remove_attrib_vals', 'Select Unused Attribute Values to remove', $unused_attribute_value_options, array('list_options' => $unused_attribute_value_options, 'multiple' => true));
		if(count($unused_attribute_value_options) > 0)
			$this->data['clean_form'] = $clean_form;
		if($clean_form->isValidated()){
			$params = $clean_form->parse();
			if($params['clean_sure']){
				if(count($params['remove_attrib_vals']) < 1)
					$this->flash("No Attribute Values removed");
				else{
					$this->inventory->removeAttributeValues($attribute_id, $params['remove_attrib_vals']);
					Util::redirect('administration', 'inventoryaddattributevalue/'.$attribute_id, false, "Removed ".count($params['remove_attrib_vals'])." Unused Attribute Values");
				}
			}else
				$this->flash("No Attribute Values removed");
		}
	}

	function inventorydelattributevalueAction($attribute_id = '', $id = ''){

		if($attribute_id == '' || $id == ''){
			Util::redirect('administration', 'inventoryaddattribute', false, "Invalid delete value call");
			return;
		}

		$org_id = $this->currentorg->org_id;

		//check if such an attribute id exists
		$check = $this->inventory->getAttributeById($attribute_id);
		if(!$check){
			Util::redirect('administration', 'inventoryaddattribute', false, "Invalid attribute");
			return;
		}

		//check if such an attribute value id exists
		$check = $this->inventory->getAttributeValueById($id, $attribute_id);
		if(!$check){
			Util::redirect('administration', 'inventoryaddattribute', false, "Invalid attribute value");
			return;
		}

		$form = new Form('del_attribute_value', 'post');
		$form->addField('checkbox', 'drop', 'Drop Attribute Value "'.$check['value_name'].'" ?', false, array('help_text' => 'Removes the attribute value. Deletes the attribute value entries from each item as well'));
		$this->data['drop_form'] = $form;

		if($form->isValidated()){

			$params = $form->parse();

			if(!$params['drop']){
				Util::redirect('administration', "inventoryaddattributevalue/$attribute_id", false, "Drop not selected");
			}else{

				$this->inventory->removeAttributeValueById($attribute_id, $id);

				Util::redirect('administration', "inventoryaddattributevalue/$attribute_id", false, "Attribute value removed");
			}
		}
	}

	function inventoryautosetheadermappingAction(){

		$org_id = $this->currentorg->org_id;

		$form = new Form('select_form', 'post');

		//field => Name in upload file
		$mapping = $this->currentorg->getConfigurationValue(CONF_INVENTORY_FIELD_NAME_MAPPING, false);
		if(!$mapping)
			$mapping = array();
		else
			$mapping = json_decode($mapping, true);

		$upload_fields = $this->inventory->getFieldsForUpload();

		foreach($upload_fields as $field){
			$help_text = "Name of the column in the upload file. CSV of column names for multiple";
			$form->addField('text', Util::uglify($field), Util::beautify($field), $mapping[$field], array('help_text' => $help_text));
		}

		$this->data['columnsmappingform'] = $form;

		//create an upload form for providing header file to set the attribute name mapping
		$uploadform = new Form('upload_form', 'post');


		if($form->isValidated()){
			$params = $form->parse();

			$mapping = array();
			foreach($upload_fields as $field){
				if(!$params[Util::uglify($field)])
					Util::redirect('administration', 'inventoryautosetheadermapping', false, 'Invalid Column Name for '.Util::beautify($field));
				$mapping[$field] = $params[Util::uglify($field)];
			}

			$json = json_encode($mapping);

			$this->currentorg->set(CONF_INVENTORY_FIELD_NAME_MAPPING, $json);

			//Now create the form to upload the header column, to set the attribute column indexes
			$uploadform->addField('file', 'csvFile', 'Upload Column Header CSV');

			$this->data['setattributemappings_form'] = $uploadform;

		}


		if($uploadform->isValidated()){


			$spreadsheet = new Spreadsheet();
			$spreadsheet->Upload($uploadform->getFile('csvFile'));
			$uploaded_data = $spreadsheet->getData();

			$headerrow = $uploaded_data[0];

			//field => fieldname
			$cols_mapping = json_decode($this->getConfigurationValue(CONF_INVENTORY_FIELD_MAPPING, array()), true);

			//Now for each field to be imported, find out the index and set it in CONF_INVENTORY_FIELD_MAPPING
			//field => nameInUploadFile for the import columns
			$cols_name_mapping = json_decode($this->getConfiguration(CONF_INVENTORY_FIELD_NAME_MAPPING), true);

			foreach($upload_fields as $col){

				$setOfFieldsForCol = $cols_name_mapping[$col];
				//$cols_name_mapping[$col] = Column1,Column2..
				//eg: We have to automatically set the index for an attribute say Color
				//$cols_name_mapping[Color] = MyColor    (MyColor is the name of a column in the upload file)
				//$cols_name_mapping[Description] = Short Desc,Dept,MyColor    (MyColor is the name of a column in the upload file)
				//$headerrow[Column 0] = Date
				//$headerrow[Column 1] = Short Desc
				//$headerrow[Column 2] = MyColor
				//$headerrow[Column 3] = Style
				//$headerrow[Column 4] = Dept
				//$final_index for Color should be equal to 2
				//$final_index for Description should be equal to 1,4,2

				$final_index = array();

				$setOfFieldsSplits = explode(',', $setOfFieldsForCol);

				foreach($setOfFieldsSplits as $f){
					for($i = 0; $i < count($headerrow); $i++){
						if($headerrow["Column $i"] == $f && $f != ''){
							array_push($final_index, $i);
							break;
						}
					}
				}

				//Set the final index into col mapping CONF_INVENTORY_FIELD_MAPPING
				$cols_mapping[$col] = implode(',', $final_index);
				if($cols_mapping[$col] == '')
					$cols_mapping[$col] = -1; //set as not found
			}

			//write back the cols mapping
			$cols_mapping = json_encode($cols_mapping);
			$this->currentorg->set(CONF_INVENTORY_FIELD_MAPPING, $cols_mapping);
		}
	}

	function inventoryheadermappingAction(){

		$org_id = $this->currentorg->org_id;

		$form = new Form('select_form', 'post');

		$mapping = $this->currentorg->getConfigurationValue(CONF_INVENTORY_FIELD_MAPPING, false);
		if(!$mapping)
			$mapping = array();
		else
			$mapping = json_decode($mapping, true);

		//add the header and footer rows fields
		$form->addField('text', 'header_rows_ignore', 'Number of rows from the top to remove', $mapping['header_rows_ignore']);
		$form->addField('text', 'footer_rows_ignore', 'Number of rows from the bottom to remove', $mapping['footer_rows_ignore']);

		$upload_fields = $this->inventory->getFieldsForUpload();

		foreach($upload_fields as $field){

			//custom help text for sku / ean
			$help_text = "";
			switch($field){
				case 'sku'	:	$help_text = "Will be used to match lineitems. Should be the same what capture through client<br>";
				break;
				case 'ean'	:	$help_text = "Must be unique for each item being uploaded<br>";
				break;
			}

			$form->addField('text', Util::uglify($field), Util::beautify($field), $mapping[$field], array('help_text' => $help_text.'Column number in the file (Start from 0, -1 in case doesnt exist)'));
		}

		$this->data['select_form'] = $form;

		if($form->isValidated()){
			$params = $form->parse();

			foreach($upload_fields as $field){
				$mapping[$field] = $params[Util::uglify($field)];
			}

			$mapping['header_rows_ignore'] = $params['header_rows_ignore'];
			$mapping['footer_rows_ignore'] = $params['footer_rows_ignore'];

			$json = json_encode($mapping);

			$this->currentorg->set(CONF_INVENTORY_FIELD_MAPPING, $json);
		}

		$uploadform = new Form('upload_form', 'post');
		$uploadform->addField('file', 'csvFile', 'Upload Csv/Excel File');
		$this->data['upload_form'] = $uploadform;

		if($uploadform->isValidated()){

			//Use the low memory batch csv reading

			$spreadsheet = new Spreadsheet();
			$tabledata = array();
			$settings = array();
			$settings['header_rows_ignore'] = $mapping['header_rows_ignore'];
			$settings['footer_rows_ignore'] = $mapping['footer_rows_ignore'];
			$batch_size = 50;

			//in case its zero, it will overwrite some column index = 0
			unset($mapping['header_rows_ignore']);
			unset($mapping['footer_rows_ignore']);

			$file = $uploadform->getFile('csvFile');
			//Allow only csv files
			$file_parts = pathinfo($file['name']);
			if($file_parts['extension'] != 'csv' && $file_parts['extension'] != 'CSV'){
				Util::redirect('administration', 'inventoryheadermapping', false, 'Upload only CSV');
			}
			$filename = $file['tmp_name'];

			while( ($tablerows = $spreadsheet->LoadCSVLowMemory($filename, $batch_size, false, $mapping, $settings)) != false ){
				foreach($tablerows as $row){
					array_push($tabledata, $row);
				}

				if(count($tabledata) > 100){ //display a preview of 100 items at max
					break;
				}
			}

			$tab = new Table();
			$tab->importArray($tabledata, $upload_fields);
			$this->data['read_data'] = $tab;

		}
	}


	/**
	 * Generate an xml for each item in the inventory
	 * @return An XML of the inventory dump
	 */
	function inventorydumpApiAction(){

		$org_id = $this->currentorg->org_id;

		$attributes = $this->inventory->getInventorygetAttributesForDump();
		//convert attribute array in the form for xml
		/* Color => String   to
		<attribute>
		<name>Color</name>
		<type>String</type>
		</attribute>
		*/
		$final_attributes = array();
		foreach($attributes as $attrib_name => $attrib_type){
			array_push($final_attributes, array('attribute' => array('name' => $attrib_name, 'type' => $attrib_type)));
		}

		//$this->data['inventory_metadata'] = $final_attributes;

		//$this->inventory->getinventorydump($this->data['inventory_items'], 'api', array(), false);


		//Batch the retrieval of inventory data and creating the xml

		//File name will loaded in api_service.php
		$file_name = $this->data['xml_file_name'];

		//Write the headers to the file
		$this->logger->debug("Using file for Batchwise XML Serialization for inventory dump: $file_name");

		//Write the headers and the open the root tag
		$fh = fopen($file_name,'w');
		fwrite($fh,"<?xml version='1.0' encoding='ISO-8859-1'?>\n<root>\n");

		//Generate the xml for inventory metadata and write it in to the file
		//Write the opening inventory metadata tag
		fwrite($fh,"<inventory_metadata>\n");
		//Write the attribute information to the file
		Util::serializeXMLToFile($final_attributes, $fh);
		//Write the closing inventory metadata tag
		fwrite($fh,"</inventory_metadata>\n");


		//Write the opening inventory items tag
		fwrite($fh,"<inventory_items>\n");

		//check if the dvs is enabled, no need to send anything if not enabled
		$all_item_ids = array();
		if(
				$this->currentorg->getConfigurationValue(CONF_CLIENT_IS_PRE_BILL_DYNAMIC_VOUCHERING_ENABLED, false)
				|| $this->currentorg->getConfigurationValue(CONF_CLIENT_IS_DYNAMIC_VOUCHERING_ENABLED, false)
				|| $this->currentorg->getConfigurationValue(CONF_CLIENT_IS_TICKET_BILL_DYNAMIC_VOUCHERING_ENABLED, false)
				|| $this->currentorg->getConfigurationValue(CONF_CLIENT_IS_CUSTOMER_DYNAMIC_VOUCHERING_ENABLED, false)
		)
		{
			//Retrieve the items in batches and convert to xml also in batches
			$all_item_ids = $this->inventory->getItemIds();
		}

		$batch_size = 30000;
		$count = 0;
		$total = count($all_item_ids);
		$batch_item_ids = array();
		$batch_data = array();

		foreach($all_item_ids as $item_id){
			$count++;
			array_push($batch_item_ids, $item_id);

			if( $count % $batch_size == 0 || $count == $total){

				//get the batch data for the collected item ids
				$this->inventory->getinventorydump($batch_data, 'api', $batch_item_ids, false);

				//Write the batch to file
				Util::serializeXMLToFile($batch_data, $fh);

				$this->logger->debug("Serialized upto $count items. Batch Size : ".count($batch_data).". Batch number : ".floor($count / $batch_size));

				//clear the batch item ids
				$batch_item_ids = array();
				$batch_data = array();
			}
		}

		//Write the closing inventory items tag
		fwrite($fh,"</inventory_items>\n");

		//All the data has been generated now close the xml root
		fwrite($fh,"\n</root>");
		fclose($fh);

	}

	/**
	 * Used by client to synchronize time
	 * @return Server time in 8601 format
	 */
	function getservercurrenttimeApiAction(){
		$ret = array();
		$ret['server_time'] = Util::serializeInto8601(time());
		$this->data['response'] = $ret;
	}
	/**
	 * Report the errors that happen at the clients
	 * @param $error_msg
	 * @param $error_desc
	 */
	function reportclienterrorApiAction($error_msg = '', $error_desc = ''){

		$xml_string = <<<EOXML
<root>
 <error_details>
  <error_subject>Error Subject In Store</error_subject>
  <error_message>Error details In The Store</error_message>
  <version>Not_Deployed</version>
  <compile_time>2010-05-20T13:04:16.5156250+05:30</compile_time>
  <svnrevision>1.0.0.3907</svnrevision>
 </error_details>
</root>
EOXML;

		$xml_string = $this->getRawInput();
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		
		//$element = Xml::parse($xml_string);
		$element = Xml::parse($xml_string);
		$client_error_msgs = $element->xpath('/root/error_details');

		foreach($client_error_msgs as $client_err_msg){

			$error_msg = ($error_msg == '') ? (string)$client_err_msg->error_subject : $error_msg;
			$error_desc = ($error_desc == '') ? (string)$client_err_msg->error_message : $error_desc;
			$client_version = ($client_version == '') ? (string)$client_err_msg->version : $client_version;
			$compile_time = date('Y-m-d',strtotime(($compile_time == '')?(string)$client_err_msg->compile_time : $compile_time));
			$svn_revision = ($svn_revision == '')?(string)$client_err_msg->svnrevision : $svn_revision;
			//ignore some of the messages
			$ignore_msg_patterns = array(
					'/Mobile number already exists/',
					'/Bill number [A-Za-z0-9\-\\/_]+ already exists/',
					'/Bill number is required/',
					'/Bill Amount is not entered or is negative/'
			);
				
			$skip = false;
			foreach($ignore_msg_patterns AS $ignore){
				if(preg_match($ignore, $error_msg) == 1){
					$skip = true;
					break;
				}
			}
			if($skip)
				continue;
				
			$org_id = $this->currentorg->org_id;
			$store_id = $this->currentuser->user_id;
			$store_name = $this->currentuser->getName() .' ( '.$this->currentuser->username.' )';
			$error_subject = "Error at $store_name : $error_msg ";
			$error_body = nl2br("$error_subject  \n\n Error Description : \n $error_desc \n\n Client Version : $client_version \n Compile Timen : $compile_time \n Svn revision : $svn_revision ");
			$recepient = $this->getConfigurationValue(CONF_CLIENT_ERROR_REPORTING_EMAIL, 'support@capillary.co.in');
			$recepients = explode(',', $recepient);
			if(count($recepients) > 1)
				$recepients_cc = array_splice($recepients, 1);
			else
				$recepients_cc = null;
				
			$recepient = $recepients[0];

			//create db entry...
			$error_description_sql = " INSERT INTO `error_description`(`info`,`description`,`version`,`compile_time`,`svn_revision`)" .
					" VALUES ('$error_msg','$error_desc','$client_version','$compile_time','$svn_revision')";

			$error_id = $this->db->insert($error_description_sql);
				
			if($error_id){
				$store_error_sql = " INSERT INTO `store_error` (`org_id`,`store_id`,`error_id`,`last_updated`)" .
						" VALUES ('$org_id','$store_id','$error_id',NOW())";
				$this->db->insert($store_error_sql);
			}
				
			Util::sendEmail($recepient, $error_subject, $error_body, $org_id, $recepients_cc);
		}
	}

	/*
	 * API to send an email.
	* https://docs.google.com/a/dealhunt.in/document/d/1_Jsl_clyVC5cwr07dROKPJYl6-k89D80Sqn29gpA2gA/edit#
	*/
	function sendemailApiAction() {

		$xml_string = <<<EOXML
<root>
  <emails>
	<email>
        <recipient>pulkit@capillary.co.in, prakhar@capillary.co.in</recipient>
		<subject>Test Email</subject>
		<message>Test Email message</message>
		<recipients_cc>prakhar@dealhunt.in,pulkit@dealhunt.in</recipients_cc>
		<email_type>text</email_type>
	</email>
  </emails>
</root>
EOXML;

		$this->logger->info("Parsing XML input. Send Email action.");

		$xml_string = $this->getRawInput();
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		
		// 			  $element = Xml::parse($xml_string);
		$element = Xml::parse($xml_string);
		$emails = $element->xpath('/root/emails/email');
		$responses = array();

		foreach ($emails as $email) {

			$recipient = $email->recipient;
			$subject = $email->subject;
			$message = $email->message;
			$recipients_cc = $email->recipients_cc;
			$email_type = $email->email_type;
			$org_id = $this->currentorg->org_id;

			$ret = Util::sendEmail($recipient, $subject, $message,
					$org_id, $recipients_cc, $email_type);

			$this->logger->info("NS Admin returns".$ret);

			if ($ret > 0) {
				$response = array(
						'key' => 'ERR_ADMINISTRATION_SUCCESS',
						'message' => 'Operation Successful.'
				);
			}
			else {
				$response = array(
						'key' => 'ERR_ADMINISTRATION_EMAIL_SEND_FAILED',
						'message' => 'Sending of Email failed.'
				);
			}

			array_push($responses,
			array('message-id' => $ret, 'item_status' => $response));
		}

		$this->data['responses'] = $responses;
	}

	/**
	 * Send SMS API
	 */
	function sendsmsApiAction(){

		$xml_string = <<<EOXML
		<root>
			<messages>
				<sms>
					<to>919904567807</to>
					<msg>Thank you for shopping with us</msg>
					<sender_gsm></sender_gsm>
					<sender_cdma></sender_cdma>
					<scheduled_time></scheduled_time>
				</sms>
				<sms>
					<to>919538784652</to>
					<msg>hi</msg>
					<sender_gsm></sender_gsm>
					<sender_cdma></sender_cdma>
					<scheduled_time>dfds</scheduled_time>
				</sms>
			</messages>
	   </root>
EOXML;
		$this->logger->debug('parsing XML Input. Send SMS API');

		// 		$element = Xml::parse( $xml_string );

		$xml_string = $this->getRawInput();

		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$element = Xml::parse( $xml_string );

		$messages = $element->xpath('/root/messages/sms');
		$responses = array();

		foreach ( $messages as $sms ) {

			$mob = (string)$sms->to;
			$msg = trim($sms->msg);
			if ( Util::checkMobileNumber( $sms->to ) ){

				if( !empty( $msg ) ){
						
					$this->logger->debug('sachin'.$sms->to);
					$send_gsm = $sms->sender_gsm;
					$send_cdma = $sms->sender_cdma;
					$schedule_time = strtotime(trim($sms->scheduled_time));
					$org_id = $this->currentorg->org_id;

					$result = Util::sendSms( $mob, $msg, $org_id, 0, false, $schedule_time );

					$this->logger->info("NS Admin returns".$result);

					if ($result > 0) {
						$response = array(
								'key' => 'ERR_ADMINISTRATION_SUCCESS',
								'message' => 'Operation Successful.'
						);
					}
					else {
						$response = array(
								'key' => 'ERR_ADMINISTRATION_SMS_SEND_FAILED',
								'message' => 'Sending of SMS failed.'
						);
					}
						
					array_push($responses,
					array( 'mobile' => $mob, 'message_id' => $result, 'item_status' => $response));
				}else{
						
					$this->logger->debug('Message Text is empty'.$sms->msg);

					$response = array(
							'key' => 'ERR_ADMINISTRATION_SMS_SEND_FAILED',
							'message' => 'Message text is empty.'
					);
					array_push($responses,
					array( 'mobile' => $mob,'message_id' => $result, 'item_status' => $response));
				}
			}else{

				$this->logger->debug('Invalid or not Registered Mobile'.$mob);

				$response = array(
						'key' => 'ERR_ADMINISTRATION_SMS_SEND_FAILED',
						'message' => 'Either Mobile number is invalid or not registered with us.'
				);
				array_push($responses,
				array('mobile' => $mob, 'message_id' => $result, 'item_status' => $response));
			}
		}

		$this->data['responses'] = $responses;

	}


	private function billimportgetattributedatatypes(){
		return array('String','Int','Boolean','Double');
	}

	private function billimportgetattributeextractiontypes(){
		return array(
				'UPLOADED value from the file' => 'UPLOAD'
		);
	}


	//==========================================================================================
	//Store Tasks
	function storetasksdashboardAction()
	{

		$org_id = $this->currentorg->org_id;

		$links = array(
				'Store Tasks' => '<a href='.Util::genUrl('administration', 'storetasks').'> Click Here </a>'
		);
		$links_table = new Table('table');
		$links_table->importFromKeyValuePairs($links,'Links','Link');
		$this->data['links'] = $links_table;

	}

	function storetasksAction($enabledTasks = true, $alreadyCreatedTasks = false)
	{

		$storeTaskMgr = new StoreTasksMgr();

		//Add a new store task form
		$addForm = new Form('add_store_task');
		$addForm->addField('select', 'store_task_type', 'Add Store Task Type', '', array('list_options' => StoreTasksMgr::getSupportedStoreTasksType()));
		if($addForm->isValidated())
		{
			$params = $addForm->parse();
			Util::redirect('administration', 'storetaskaddoredit/'.$params['store_task_type'], false);
		}
		$this->data['add_store_task_form'] = $addForm;


		$filterForm = new Form('filter_form', 'post');
		$filterForm->addField('checkbox', 'enabled_tasks', 'Enabled Tasks ? ', $enabledTasks);
		$filterForm->addField('checkbox', 'created_tasks', 'Created Tasks ? ', $alreadyCreatedTasks);
		if($filterForm->isValidated())
		{
			$params = $filterForm->parse();
			$enabledTasks = $params['enabled_tasks'];
			$alreadyCreatedTasks = $params['created_tasks'];
			Util::redirect('administration', 'storetasks/'.$enabledTasks.'/'.$alreadyCreatedTasks, false);
		}
		$this->data['filter_form'] = $filterForm;

		//Table of enabled store tasks / Not yet completed
		$tasks_table = $storeTaskMgr->getStoreTasksTable( $enabledTasks, $alreadyCreatedTasks , 'table' , true );
		$tasks_table->createLink('Edit', Util::genUrl('administration', "storetaskaddoredit/{0}/{1}"), 'edit', array(0 => 'task_target_type', 1 => 'id'));

		if($alreadyCreatedTasks)
			$tasks_table->createLink('Task Entries', Util::genUrl('administration', "storetaskentries/{0}"), 'task entries', array(0 => 'id'));
		else
			$tasks_table->createLink('Generate', Util::genUrl('administration', "storetaskgeneratetaskentries/{0}"), 'generate', array(0 => 'id'));

		$this->data['store_tasks_table'] = $tasks_table;

	}

	function storetaskaddoreditAction($store_task_type, $store_task_id = '')
	{
		global $currentorg;

		$addForm = new Form('add_task_form', 'post');
		$storeTaskMgr = new StoreTasksMgr();
		$storeTaskMgr->addStoreTaskForm($addForm, $store_task_type, $store_task_id);

		if($addForm->isValidated())
		{
			$ret = $storeTaskMgr->addOrUpdateStoreTaskFromForm($addForm);
				
			if( $ret )
				Util::removeCacheFile( $currentorg->org_id , 'administration' , 'getstoretasks' );

			Util::redirect('administration', 'storetasks', false, "Returned $ret");
		}

		$this->data['add_task_form'] = $addForm;
	}

	function storetaskgeneratetaskentriesAction($store_task_id)
	{
		$storeTaskMgr = new StoreTasksMgr();
		$numTaskEntries = $storeTaskMgr->createStoreTaskEntries($store_task_id);

		Util::redirect('administration', 'storetasks', false, "Created $numTaskEntries Entries for Task Id : $store_task_id");
	}

	function editstoretaskentriesAction( $ste_id , $task_id )
	{
		$storeTaskMgr = new StoreTasksMgr();

		$editForm = new Form('edit_form', 'post');
		$status_options = $storeTaskMgr->getStatusAsOptions( $ste_id );

		$editForm->addField('select', 'status', "Set status for id : $ste_id", array(), array('list_options' => $status_options ));
		$editForm->addField('text', 'reason', "Reason for status change :" );

		if( $editForm->isValidated() )
		{
			$params = $editForm->parse();
			$status = $params[ 'status' ];
			$storeTaskMgr->updateStoreTaskStatus( $params , $ste_id , $task_id );
			Util::redirect('administration', "storetaskentries/$task_id", false, "Modified Task_id : $ste_id and set status to : $status ");

		}

		$this->data['edit_form'] = $editForm;

	}
	function storetaskentriesAction($task_id)
	{
		//Display Store Task Entires
		$filterForm = new Form('filter_form', 'post');
		$store_options = $this->getStoresAsOptions();
		$filterForm->addField('multiplebox', 'stores', 'Filter Stores', array(), array('list_options' => $store_options, 'multiple' => true, 'help_text' => '<font color="red"><b>To search for all stores, do not select anything</b></font>'));
		$filterForm->addField('datetimepicker', 'task_updated_after', 'Status Updated After', '');
		$filterForm->addField('datetimepicker', 'task_updated_before', 'Status Updated Before', '');
		$filterForm->addField('checkbox', 'is_completed', 'Is Completed', false);

		$storeTasksMgr = new StoreTasksMgr();
		$storeTask = $storeTasksMgr->getStoreTaskById($task_id);
		$status_options = explode(',', $storeTask->task_status_options);
		$filterForm->addField('select', 'task_entries_with_status', 'Status Options', '', array('list_options' => $status_options));
		$filterForm->addField('text', 'max_num_entries', 'Max Number Of Entries', 100);
		if($filterForm->isValidated())
		{
			$params = $filterForm->parse();

			$selected_stores = $params['stores'];
			$task_updated_after = $params['task_updated_after'];
			$task_updated_before = $params['task_updated_before'];
			$is_completed = $params['is_completed'];
			$tasks_with_status = $params['task_entries_with_status'];
			$max_num_entries = $params['max_num_entries'];

			$store_task_table_object = $storeTasksMgr->getStoreTaskEntriesTable($task_id,
					$selected_stores, $task_updated_after, $task_updated_before, $is_completed, $tasks_with_status,
					$max_num_entries);
			$store_task_table_object->createLink( 'Edit', Util::genUrl('administration', 'editstoretaskentries')."/{0}/$task_id" ,
					'edit' , array( '0' => 'id' ) );

			$this->data[ 'store_task_entries_table' ] = $store_task_table_object;
		}

		$this->data['filter_form'] = $filterForm;
	}


	function getstoretasksApiAction()
	{
		$storeTasksMgr = new StoreTasksMgr();
		$storeTasks = $storeTasksMgr->getStoreTasksForApi();
		$this->data['store_tasks'] = $storeTasks;
	}

	function getstoretasksentriesApiAction()
	{

		$xml_string = <<<EOXML
<root>
	<get_store_task_entries>
		<after_task_entry_id>233</after_task_entry_id>
		<task_entry_last_modified>2011-05-21T09:52Z</task_entry_last_modified>
	</get_store_task_entries>
</root>
EOXML;

		//modified_after_task_entry_date Is the new one based on which the client gets new entries

		$xml_string = $this->getRawInput();

		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		//extract the min id
		$element = Xml::parse($xml_string);
		$elems = $element->xpath('/root/get_store_task_entries');
		$min_task_entry_id = "";
		$last_modified = "";
		foreach ($elems as $e)
		{
			$min_task_entry_id = (string) $e->after_task_entry_id;
			$last_modified = $e->task_entry_last_modified ? Util::deserializeFrom8601((string) $e->task_entry_last_modified) : '';
			break;
		}

		//TODO Batch create xml in case it becomes too huge for a single store.
		$storeTasksMgr = new StoreTasksMgr();
		$store_id = $this->currentuser->user_id;
		if($last_modified != '') //Do it based on the date if its sent
			$min_task_entry_id = '';
		$storeTaskEntries = $storeTasksMgr->getStoreTaskEntriesForStore($store_id, $min_task_entry_id, $last_modified);
		$this->data['store_task_entries'] = $storeTaskEntries;
	}

	function updatestoretaskentrystatusApiAction()
	{
		$xml_string = <<<EOXML
<root>
	<store_task_entry>
		<task_id>2</task_id>
		<task_entry_id>1</task_entry_id>
		<task_entry_status>DONE</task_entry_status>
		<task_entry_notes>Customer is happy</task_entry_notes>
		<is_task_completed>1</is_task_completed>
		<task_updated_on>2011-05-21T09:52Z</task_updated_on>
	</store_task_entry>
</root>
EOXML;

		$xml_string = $this->getRawInput();

		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$responses = array();

		//extract the min id
		$element = Xml::parse($xml_string);
		$elems = $element->xpath('/root/store_task_entry');
		$task_id = "";
		$task_entry_id = "";
		$updated_task_status = "";
		$updated_task_notes = "";
		$is_task_completed = "";
		$task_updated_time = "";

		$storeTasksMgr = new StoreTasksMgr();

		foreach ($elems as $e)
		{
			$task_id = (string) $e->task_id;
			$task_entry_id = (string) $e->task_entry_id;
			$updated_task_status = (string) $e->task_entry_status;
			$updated_task_notes	= (string) $e->task_entry_notes;
			$is_task_completed = (string) $e->is_task_completed;
			$task_updated_time = $e->task_updated_on ? Util::deserializeFrom8601((string) $e->task_updated_on) : time();
			$task_updated_time = Util::getMysqlDateTime($task_updated_time);

			$storeTasksMgr->updateTaskEntryStatusById($task_id, $task_entry_id, $updated_task_status,
					$updated_task_notes, $is_task_completed, $task_updated_time);

			$response = array(
					'task_id' => $task_id,
					'task_entry_id' => $task_entry_id,
					'task_status' => $updated_task_status
			);

			array_push($responses, $response);
		}

		$this->data['responses'] = $responses;
	}

	//==========================================================================================

	/**
	 * It will return tills usernames which are under store server
	 * @author nayan
	 */
	function tillsUnderStoreserverApiAction(){

		$xml_string = <<<XML
<root>
     <store_server_username>xyz</store_server_username>
</root>
XML;
		global $url_version;
		$url_version = '1.0.0.1';
		$xml_string = $this->getRawInput();
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		
		$elements= Xml::parse( $xml_string );
		$elems = $elements->xpath('/root');

		$this->logger->debug("Start tillsUnderStoreserver api action");
		$e = $elems[0];
		$store_server = $e->store_server_username;

		$OrgController = new ApiOrganizationController();

		//$sql = "SELECT `ref_id` FROM `masters`.`loggable_users` WHERE `type` = 'STR_SERVER'
		//AND `username` = '$store_server' AND `org_id` = '".$this->currentorg->org_id."'";

		$sql = "SELECT id as `ref_id` FROM `masters`.`org_entities` WHERE `type` = 'STR_SERVER'
			AND `code` = '$store_server' AND `org_id` = '".$this->currentorg->org_id."'";
		
		
		$store_server_id = $this->db->query_scalar( $sql );

		$key = 'ERR_RESPONSE_FAILURE';
		$results = array();

		if( $store_server_id ){

			$tills = $OrgController->StoreServerController->getStoreTerminalsByStoreServerId( $store_server_id );

			if( $tills ){
				$till_string = implode( ',' , $tills );
					
				//$sql = "SELECT `username` FROM `masters`.`loggable_users` WHERE `type` = 'TILL'
				// AND `ref_id` IN ( $till_string ) AND `org_id` = '".$this->currentorg->org_id."'";
					
				$sql = "SELECT code as `username` FROM `masters`.`org_entities` WHERE `type` = 'TILL'
				AND `id` IN ( $till_string ) AND `org_id` = '".$this->currentorg->org_id."'";
				
				$result = $this->db->query( $sql );
					
				foreach( $result as $till ){
					array_push($results,$till['username']);
				}
				$key = 'ERR_RESPONSE_SUCCESS';
				$message = 'Operational Successful';

			}else{
				$message = 'Tills are not present under this Store Server';
			}
		}else{
			$message = 'The given store is not a store server';
		}

		$url_version = '1.0.0.0';

		$response = array(	'userName' => $results ,
				'item_status'=>array(
						'key' => $key,
						'message' => $message
				));

		$this->data['result'] = $response;
	}

	//=================================================================================================

	function getAllowedFraudStatuses(){
		$status_options = array('MARKED','CONFIRMED','RECONFIRMED','NOT_FRAUD', 'INTERNAL');
		return $status_options;
	}

}
?>
