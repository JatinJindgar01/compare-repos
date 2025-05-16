<?php
include_once 'apiModel/class.ApiLoyaltyTracker.php';

// TODO: remove the file from API
class ApiLoyaltyTrackerModelExtension extends ApiLoyaltyTrackerModel
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Returns loyalty details/report for a store for given last number of days
	 * @param unknown_type $number_of_days
	 * @param string $end_date - days counting will end till end_date
	 * @param : if $reports is empty, all reports are fetched, otherwise speccific report
	 */
	public function getLoyaltyDetailsForStore($org_id, $store_till_id, $number_of_days = null,
	$start_date = null, $end_date = null, $reportNames = array())
	{
		$this->logger->debug("Store Report for ($start_date to $end_date) or $number_of_days days");
		if($number_of_days === null)
			$number_of_days = 0;
		$result = array();
		$memcache = MemcacheMgr::getInstance();

		if($start_date != null && $end_date != null)
		{
			$start_date = $date_with_start_time = DateUtil::getDateWithStartTime($start_date);
			$end_date = $date_with_end_time = DateUtil::getDateWithEndTime($end_date);
		}
		else if($number_of_days !== NULL)
		{
			$start_date = $date_with_start_time = DateUtil::getDateByDays(true, $number_of_days, $start_date,"%Y-%m-%d 00:00:00");
			$end_date = $date_with_end_time = DateUtil::getDateByDays(true, $number_of_days, $end_date,"%Y-%m-%d 23:59:59");			
		}
		else
		{
			$start_date = $date_with_start_time = DateUtil::getCurrentDateWithStartTime();
			$end_date = $date_with_end_time = DateUtil::getCurrentDateWithEndTime();
		}
 
		global $currentorg, $cfg;
		$param = "org_id=$org_id&store_id=".$store_till_id;
		if($start_date)	{ $start_date = strtotime($start_date." midnight");	$param .= "&start_date=".date('Y-m-d', $start_date); }
		if($end_date)	{ $end_date = strtotime($end_date );		$param .= "&end_date=".date('Y-m-d', $end_date); }
		$currentDayStart = strtotime("midnight");

		$storeReportKey = 'o'.$org_id.'_'.CacheKeysPrefix::$storeReport.$store_till_id.("#".$start_date."#".$end_date);
		$conquestRequestKey = 'o'.$org_id.'_'.CacheKeysPrefix::$storeReportDailyCounter.$store_till_id.("#".$start_date."#".$end_date);
		try
		{
			$result = $memcache->get($storeReportKey);
			$this->logger->debug("Fetched the report from cache");
			$value = json_decode($result, true);
			
			$nullPresent = false;
			foreach ($value as $k => $v)
			{
				if($v === null && !$nullPresent)
				{
					$nullPresent = true;
					$this->logger->debug("Value in cache is null for $k");
				}
			}
			if(!$nullPresent && $value)
			{
				$value["customers"] +=0;
				$value["transactions"] +=0;
				$value["not_interested_transactions"] +=0;
				$value["points_issued"] +=0;
				$value["points_redeemed"] +=0;
				return $value;
			}
			else
				$result = array();
				
		}
		catch(Exception $e){
			$this->logger->debug("Failed to get report from cache");
		}

		$callConquest = true; $includeToday = false;
		if($end_date && $end_date >= $currentDayStart)
		{
			$this->logger->debug("End date greater tahn today; ");
			$includeToday = true;
		}
		else if($start_date && $start_date >= $currentDayStart )
		{
			$this->logger->debug("Report requested from today");
			$includeToday = true;
		}
		
		// to avoid current date
		if($start_date && $start_date >= $currentDayStart )// && $start_date < $end_date)
			$callConquest = false;

		$conquestFailed = false;
		if($callConquest)
		{
			try {
				$res = $memcache->get($conquestRequestKey);
				$till_stats = json_decode($res, true);
				
				$result["customers"] = $till_stats["registrations"];
				$result["transactions"] = $till_stats["loyaltyTransactions"];
				$result["not_interested_transactions"] = $till_stats["notInterestedTransactions"];
				$result["points_issued"] = $till_stats["pointsAwarded"];
				$result["points_redeemed"] = $till_stats["pointsRedeemed"];

				foreach ($value as $k => $v)
				{
					if($v === null && !$nullPresent)
					{
						$nullPresent = true;
						$this->logger->debug("Value in cache is null for $k");
					}
				}
				if(!$nullPresent && $value)
				{
					$value["customers"] +=0;
					$value["transactions"] +=0;
					$value["not_interested_transactions"] +=0;
					$value["points_issued"] +=0;
					$value["points_redeemed"] +=0;
					return $value;
				}
				else
					throw new Exception("Bad Cache Data");

				$conquestFailed = false;
			} catch (Exception $e) {
				$cm = new CurlManager();
				$this->logger->debug("Making call to conquest api url is :".$cfg["conquest_base_url"]."store/getStatistics?key=".$cfg['conquest_api_key']."&$param");
				try {
					$res = $cm->get($cfg["conquest_base_url"]."store/getStatistics?key=".$cfg['conquest_api_key']."&$param",0, 1);
				} catch (Exception $e) {
					$res  = false;
					$conquestFailed = true;
				}

				if(!$res )
				{
					$this->logger->debug("Failed to get the details from conquest");
					$res = false;
					$conquestFailed = true;
				}

				$this->logger->debug("Conquest api response: " . $res);
				$conquest_api_response = json_decode($res, true);
				$this->logger->debug(print_r($conquest_api_response, true));


				// Fix this; first from cache ; then call the api
				if($res && $conquest_api_response["status"]["message"] == "Success")
				{
					$till_stats = $conquest_api_response["storeStatistics"]["storesInfo"][0];

					$this->logger->debug("Conquest API call successful");

					$result["customers"] = $till_stats["registrations"];
					$result["transactions"] = $till_stats["loyaltyTransactions"];
					$result["not_interested_transactions"] = $till_stats["notInterestedTransactions"];
					$result["points_issued"] = $till_stats["pointsAwarded"];
					$result["points_redeemed"] = $till_stats["pointsRedeemed"];
					try
					{
						$memcache->set($conquestRequestKey, json_encode($result), CacheKeysTTL::$storeReportDailyCounter);
					}catch(Exception $e){
						$this->logger->debug("saving the $key to cache failed");
					}
				}
				else
				$conquestFailed = true;
			}
		}
                
                $this->logger->debug("No Need to query the DB/cache to fetch the data.Whatever got from conquest,returning it.");
                return $result;
			
		if($includeToday || $conquestFailed)
		{
			$this->logger->debug("Need to query the DB/cache to fetch the data");

			$queries = array(
			array(
							'query' => "SELECT COUNT(*) AS count FROM loyalty l WHERE {{clause}} AND l.publisher_id = $org_id AND l.registered_by = $store_till_id",
							'd' => 'joined',
							'row_name' => 'customers',
							'db' => 'users'),

			array (
							'query' => "SELECT COUNT(*) FROM loyalty_log l WHERE {{clause}} AND l.org_id = $org_id AND l.entered_by = $store_till_id AND l.outlier_status = 'NORMAL'",
							'd' => '`date`',
							'row_name' => 'transactions',
							'db' => 'users'),

			array(
							'query'=>" SELECT  COUNT(*)
							FROM loyalty_not_interested_bills l
							WHERE l.org_id = $org_id AND l.entered_by = $store_till_id AND {{clause}} " ,
							'd' => '`billing_time`',
							'row_name' => 'not_interested_transactions',
							'db' => 'users'),


			array (
							'query' => "SELECT IFNULL(SUM(`points`),0) FROM loyalty_log l WHERE {{clause}} AND l.org_id = $org_id AND l.entered_by = $store_till_id",
							'd' => '`date`',
							'row_name' => 'points_issued',
							'db' => 'users'),

			array (

							'query' => "SELECT IFNULL(SUM(`points_redeemed`),0) " .
							" FROM `points_redemption_summary` AS `lr` " .
							" INNER JOIN `org_participation` as op on op.org_id = lr.org_id and lr.program_id = op.program_id ".
							" WHERE {{clause}} AND `lr`.`org_id` = $org_id AND `lr`.`till_id` = $store_till_id",
							'd' => '`lr`.`redemption_time`',
							'row_name' => 'points_redeemed',
							'db' => 'warehouse'),
			);

			foreach ($queries as $query) {
				if($reportNames && !in_array($query['row_name'], $reportNames))
				{
					$this->logger->debug("skip the query");
					continue;
				}

				if($callConquest && $conquestFailed)
				{
					$validEndDate = min(DateUtil::getDateByDays(true, 0, $date_with_end_time,"%Y-%m-%d %H:%M:%S"), date('Y-m-d 00:00:00', strtotime("today")));
					$clause = "{{d}} >= '". DateUtil::getDateByDays(true, 0, $date_with_start_time,"%Y-%m-%d %H:%M:%S")."'".
								" AND {{d}} <= '". $validEndDate."'";
					$key = "o".$org_id."_".strtolower($query['row_name'])."_".$store_till_id. "#".date("Ymd", strtotime($validEndDate)."#".strtotime($date_with_start_time));
					try {
						$result[$query['row_name']] = $memcache->get($key);
					} catch (Exception $e) {
						//Using slave
						$db = new Dbase($query['db'], true);
						//$clause = " SUBDATE(DATE($end_date), INTERVAL $number_of_days DAY ) = DATE({{d}})";
						$final_query = str_replace("{{clause}}", $clause, $query['query']);
						$final_query = str_replace("{{d}}", $query['d'], $final_query);
						$res = $db->query_scalar($final_query);
						$result[$query['row_name']] = $res;
					}
					try
					{
						$memcache->set($conquestRequestKey, json_encode($result), CacheKeysTTL::$storeReportDailyCounter);
					}catch(Exception $e){
						$this->logger->debug("saving the $key to cache failed");
					}
				}
				if($includeToday)
				{
					$key = "o".$org_id."_".CacheKeysPrefix::$storeReportDailyCounter.strtolower($query['row_name'])."_".$store_till_id. "#".date("Ymd", strtotime("today"));
					//$key = "o".$org_id."_".strtolower($query['row_name'])."_".$store_till_id. "#".date("Ymd", strtotime("today"));
					try {
						$result[$query['row_name']] += $memcache->get($key);
						continue;
					} catch (Exception $e) {
						//Using slave
						$db = new Dbase($query['db'], true);
						$clause = "{{d}} >= '". DateUtil::getCurrentDateWithStartTime()."'".
								" AND {{d}} <= '". DateUtil::getDateByDays(true, 0, $date_with_end_time,"%Y-%m-%d %H:%M:%S")."'";
						//$clause = " SUBDATE(DATE($end_date), INTERVAL $number_of_days DAY ) = DATE({{d}})";
						$final_query = str_replace("{{clause}}", $clause, $query['query']);
						$final_query = str_replace("{{d}}", $query['d'], $final_query);
						$res = $db->query_scalar($final_query);
						$result[$query['row_name']] = $res + $result[$query['row_name']] ;

					}
				}
			}
		}

		try
		{
			$memcache->set($storeReportKey, json_encode($result), CacheKeysTTL::$storeReport);
		}
		catch(Exception $e){
			$this->logger->debug("saving the $storeReportKey to cache failed");
		}

		$result["customers"] +=0;
		$result["transactions"] +=0;
		$result["not_interested_transactions"] +=0;
		$result["points_issued"] +=0;
		$result["points_redeemed"] +=0;
		return $result;
	}

	public static function incrementStoreCounterForDay($type, $org_id, $store_till_id, $value =1)
	{
		$cache = MemcacheMgr::getInstance();
		$key = "o".$org_id."_".CacheKeysPrefix::$storeReportDailyCounter.strtolower($type)."_".$store_till_id. "#".date("Ymd", strtotime("today"));
		try{
			$cache->increment($key);
			return;
		}catch (Exception $e){
			$obj = new ApiLoyaltyTrackerModelExtension();
			$date = date("Y-m-d", strtotime("today"));
			$values = $obj->getLoyaltyDetailsForStore($org_id, $store_till_id, null, $date, $date, array($type));
			try {
				$cache->set($key, $values[$type], CacheKeysTTL::$storeReportDailyCounter);
			} catch (Exception $e) {
			}
		}
	}
	/**
	 *
	 * @param unknown_type $month
	 */
	public function getLoyaltyDetailsForStoreForLastWeekOrMonth($org_id, $store_till_id, $month = false)
	{

		if($month)
			$start_date = date('Y-m-01 00:00:00');
		else
			$start_date = max(date('Y-m-d 00:00:00',strtotime("last sunday")), date('Y-m-01 00:00:00'));

		$end_date = DateUtil::getCurrentDateWithEndTime();

		return $this->getLoyaltyDetailsForStore($org_id, $store_till_id, null,$start_date , $end_date );
			

		/*$queries = array(
		 array(
		 'query' => "SELECT COUNT(*) AS count FROM loyalty l WHERE {{clause}} AND l.publisher_id = $org_id AND l.registered_by = $store_till_id",
		 'd' => 'joined',
		 'row_name' => 'customers',
		 'db' => 'users'),

		 array (
		 'query' => "SELECT COUNT(*) FROM loyalty_log l WHERE {{clause}} AND l.org_id = $org_id AND l.entered_by = $store_till_id AND l.outlier_status = 'NORMAL'",
		 'd' => '`date`',
		 'row_name' => 'transactions',
		 'db' => 'users'),

		 array(
		 'query'=>" SELECT  COUNT(*)
		 FROM loyalty_not_interested_bills l
		 WHERE l.org_id = $org_id AND l.entered_by = $store_till_id AND {{clause}} " ,
		 'd' => '`billing_time`',
		 'row_name' => 'not_interested_transactions',
		 'db' => 'users'),


		 array (
		 'query' => "SELECT IFNULL(SUM(`points`),0) FROM loyalty_log l WHERE {{clause}} AND l.org_id = $org_id AND l.entered_by = $store_till_id",
		 'd' => '`date`',
		 'row_name' => 'points_issued',
		 'db' => 'users'),

		 array (

		 'query' => "SELECT IFNULL(SUM(`points_redeemed`),0) " .
		 " FROM `points_redemption_summary` AS `lr` " .
		 " INNER JOIN `org_participation` as op on op.org_id = lr.org_id and lr.program_id = op.program_id ".
		 " WHERE {{clause}} AND `lr`.`org_id` = $org_id AND `lr`.`till_id` = $store_till_id",
		 'd' => '`lr`.`redemption_time`',
		 'row_name' => 'points_redeemed',
		 'db' => 'warehouse'),
		 );
		 $result = array();
		 foreach ($queries as $query) {

			//TODO: check for MLM
			//skip if mlm is disabled
			//if(!$mlm_enabled && ($query['row_name'] == '# Refs Sent' || $query['row_name'] == '# Refs Joined'))
			//	continue;

			//Using slave
			$db = new Dbase($query['db'], true);
			$first_day_of_week = DateUtil::getMysqlDateTime(strtotime("last sunday"));
			$first_day_of_month = DateUtil::getMysqlDateTime(strtotime(date('m/01/y')));
			$current_date_with_end_time = DateUtil::getCurrentDateWithEndTime();
			if(!$month)
			{
			$clause = " {{d}} >= '$first_day_of_week' AND {{d}} >= '$first_day_of_month' AND {{d}} <= '$current_date_with_end_time' ";
			//$clause = " WEEK(NOW()) = WEEK({{d}}) AND (YEAR(NOW())=YEAR({{d}}) AND MONTH(NOW())=MONTH({{d}}))";
			}
			else
			{
			$clause = " {{d}} >= '$first_day_of_month' AND {{d}} <= '$current_date_with_end_time'  ";
			//$clause = " MONTH(NOW()) = MONTH({{d}}) AND (YEAR(NOW())=YEAR({{d}}))";
			}

			$final_query = str_replace("{{clause}}", $clause, $query['query']);
			$final_query = str_replace("{{d}}", $query['d'], $final_query);
			$res = $db->query_scalar($final_query);
			$result[$query['row_name']] = $res;
			}
			return $result;
			*/
	}

	public function getRedemptionDetailsForStore($org_id, $store_till_id, $start_date = null, $end_date = null)
	{
		//if start_date or end_date is null then it will take today's report
		if($start_date == null || $end_date == null)
		{
			$start_date = DateUtil::getCurrentDateWithStartTime();
			$end_date = DateUtil::getCurrentDateWithEndTime();
		}

		$date_filter = " AND lr.redemption_time >= '$start_date' AND lr.redemption_time <= '$end_date' ";
		$sql = "SELECT
					`lr`.customer_id as user_id,
					`lr`.`bill_number` AS `transaction_number`,
					`lr`.`points_redeemed`,
					`lr`.`points_redemption_time` AS `redemption_date`" .
				" FROM `points_redemption_summary` AS `lr` " .
				" INNER JOIN `org_participation` as op on op.org_id = lr.org_id and lr.program_id = op.program_id ".
				" WHERE `lr`.`till_id` = $store_till_id AND `lr`.`org_id` = $org_id ".
		$date_filter;
		$db = new Dbase('warehouse', true);
		$redemption_records = $db->query($sql);

		if($redemption_records)
		{

			$db_masters = new Dbase('masters', true);
			$tillName = $db_masters->query_firstrow("select oe.code from masters.org_entities AS oe where oe.id = $store_till_id and oe.org_id = $org_id");
			$tillName = $tillName["code"];

			$user_ids = array();
			foreach($redemption_records  as $redemption)
			$user_ids[] = $redemption["user_id"];

			$sql = "SELECT u.id as user_id, `u`.`firstname`, `u`.`lastname` ,`u`.`mobile` AS `mobile`
			FROM `users` AS `u`
			WHERE u.org_id = $org_id and id in ( ".implode(",", $user_ids)." )";
			$db_users = new Dbase('users', true);
			$userDetailsArr = $db_users->query_hash($sql, "user_id", array("firstname", "lastname", "mobile"));

			foreach($redemption_records  as $key=>$redemption)
			{
				$redemption_records[$key]["firstname"] = $userDetailsArr[$redemption["user_id"]]["firstname"];
				$redemption_records[$key]["lastname"] = $userDetailsArr[$redemption["user_id"]]["lastname"];
				$redemption_records[$key]["store_login_name"] =  $tillName;
				$redemption_records[$key]["mobile"] = $userDetailsArr[$redemption["user_id"]]["mobile"];
				unset($redemption_records[$key]["user_id"]);
			}
		}

		//Using slave
		return $redemption_records;
	}
	
	public function getOrgPerformance($orgId, $storeIds = array(), $start_date = null, $end_date = null){
	
		$this->logger->debug("Need to query the DB/cache to fetch the data");
	
		$start_date = DateUtil::getCurrentDateWithStartTime();
		$end_date = DateUtil::getCurrentDateWithEndTime();
		$result = array();
		$queries = array(
				array(
						'table' => "loyalty",
						'd' => 'joined',
						"columns" => array("customers" => "count(t.id)"),
						'org_id' => 'publisher_id',
						'db' => 'users'),
	
				array (
						'table' => "loyalty_log",
						'd' => 'date',
						'org_id' => 'org_id',
						"columns" => array("transactions" => "count(t.id)", "transaction_amount" => "round(ifnull(sum(bill_amount),0),2)"),
						'db' => 'users'),
	
				array(
						'table' => "loyalty_not_interested_bills",
						'd' => '`billing_time`',
						'org_id' => 'org_id',
						"columns" => array("not_interested_transactions" => "count(t.id)", "not_interested_transactions_amount" => "round(ifnull(sum(bill_amount),0),2)"),
						'db' => 'users'),
		);
	
		$result[$orgId] = array();
	
		foreach ($queries as $query) {
			$column_csv = array();
			foreach($query["columns"] as $k=>$v){
				$column_csv[] = "$v AS $k" ;
			}
			$column_csv = implode(",", $column_csv);
	
			$sql = "SELECT $column_csv FROM $query[table] as t
			WHERE $query[d] >= '$start_date'
			AND $query[d] <= '$end_date'
			AND $query[org_id] = $orgId ";
				
			$db = new Dbase($query["db"]);
			$result[$orgId]= array_merge($result[$orgId], $db->query_firstrow($sql));
		}
		return $result;
	}
	
}
?>
