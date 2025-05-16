<?php 

	require_once 'apiModel/class.ApiAssociates.php';

	/*
	*
	* -------------------------------------------------------
	* CLASSNAME:        ApiAssociateModelExtension extends ApiAssociatesModel
	* GENERATION DATE:  09.05.2011
	* CREATED BY:       Kartik Gosiya
	* FOR MYSQL TABLE:  associates
	* FOR MYSQL DB:     masters
	*
	*/

	class ApiAssociateModelExtension extends ApiAssociatesModel{
		
		private $db_master ;
		private $db_user ;

		//some elements
		private $last_login;
		
		public function __construct(){
			
			parent::ApiAssociatesModel();
			
			$this->db_user = new Dbase( 'users' );
		}	
		
		public function getLastLogin()
		{
			return $this->last_login;
		}
		
		/**
		 * 
		 * @param unknown_type $username will accept parameter either username, associate code or email 
		 * @param unknown_type $password will accept password
		 */
		public function login($username, $password)
		{
			
			$status = false;
			try {
				
			$lumeObj = new LoggableUserModelExtension();
			$status = $lumeObj->login($username, $password);
			
			// check by associate code
			if(!$status)
			{
				if($secondary = $this->getByAssociateCode($username))
				{
					$status = $lumeObj->login($secondary["username"], $password);
				}
			}
			
			// check by email code
			if(!$status)
			{
				if($secondary = $this->getByEmail($username))
				{
					$status = $lumeObj->login($secondary["username"], $password);
				}
			}
			} catch (Exception $e) {
				$this->logger->debug("Login failed");
			}
				
			
// 			$this->logger->debug("trying to fetch associate by username ($username)");
// 			$result = $this->getByUserName($username);
// 			if(isset($result) && count($result) > 0)
// 				$status = true;
			
// 			if(!$status)
// 			{
// 				$this->logger->debug("trying to fetch associate by AssociateCode ($username)");
// 				$result = $this->getByAssociateCode($username);
// 				if(isset($result) && count($result) > 0)
// 					$status = true;
// 			}
			
// 			if(!$status)
// 			{
// 				$this->logger->debug("trying to fetch associate by Email ($username)");
// 				$result = $this->getByEmail($username);
// 				if(isset($result) && count($result) > 0)
// 					$status = true;
// 			}
			
			
// 			$this->logger->debug("Username matching: ".($result['username'] == $username));
// 			$this->logger->debug("Password matching: ".($result['password'] == $password));
// 			if($status && $result['username'] == $username && $result['password'] == $password)
// 			{
// 				$status = true;
// 				$this->load($result['associate_id']);
// 				$this->updateLastLogin();
// 			}
// 			else
// 				$status = false;
			
			return $status;
		}
		
		private function updateLastLogin()
		{
// 			$timezone = StoreProfile::getById($this->currentuser->user_id)->getStoreTimeZoneLabel();
// 			$currenttime = Util::getCurrentTimeInTimeZone($timezone, null);
// 			$this->last_login = empty($currenttime)? date('Y-m-d H:M:s') : $currenttime;
// 			$currenttime = empty($currenttime)? " NOW() " : "'$currenttime'";
			
// 			$sql = "UPDATE loggable_users SET last_login = $currenttime WHERE ref_id = $this->id AND org_id = $this->org_id";
// 			$this->database->update($sql);
						
			$obj = new LoggableUserModelExtension();
			$obj->loadDetailsByRefId( $this->id, 'ASSOCIATE' );
			$obj->updateLastLogin(); 
		}
		
		// TODO: load by username/password
		private function getByUserName($username)
		{
			$obj = new LoggableUserModelExtension();
			$obj->loadDetailsByUsername( $ref_id, $loggable_type = 'ASSOCIATE' );
			return $obj;
			
// 			$till_id = $this->currentuser->user_id;
// 			$sql = "SELECT 	lu.id AS loggable_user_id, 
// 							lu.username AS username, 
// 							lu.password AS password, 
// 							a.id AS associate_id
// 						FROM loggable_users AS lu
// 						JOIN associates AS a
// 							ON a.org_id = lu.org_id 
// 							AND a.id = lu.ref_id
// 						JOIN org_entity_relations AS oer
// 							ON  oer.org_id = lu.org_id
// 							AND a.store_id = oer.parent_entity_id
// 						WHERE lu.username = '$username'
// 							AND lu.org_id = $this->org_id
// 							AND oer.child_entity_id = $till_id";
			
// 			return $this->database->query_firstrow($sql);			
		}
		
		private function getByAssociateCode($associate_code)
		{			
			$till_id = $this->currentuser->user_id;
			$sql = "SELECT 	a.associate_code AS username,
							a.id AS associate_id
						FROM associates AS a
						JOIN org_entity_relations AS oer
							ON  oer.org_id = a.org_id
							AND a.store_id = oer.parent_entity_id
						WHERE a.associate_code = '$associate_code'
							AND a.org_id = $this->org_id
							AND oer.child_entity_id = $till_id";
			
			return $this->database->query_firstrow($sql);
		}
		
		private function getByEmail($email)
		{
			$till_id = $this->currentuser->user_id;
			$sql = "SELECT 	
							a.email AS username,
							a.id AS associate_id
						FROM associates AS a
						JOIN org_entity_relations AS oer
							ON  oer.org_id = a.org_id
							AND a.store_id = oer.parent_entity_id
						WHERE a.email = '$email'
							AND a.org_id = $this->org_id
							AND oer.child_entity_id = $till_id";
			return $this->database->query_firstrow($sql);
		}
		
		public function getActivities(	$store_id = null, $type = null, 
										$start_date = null, $end_date = null,
										$start_id = null, $end_id = null, 
										$limit = 10)
		{
			$order_by_filter = " ORDER BY `added_on` DESC ";
			if($store_id != null && !empty($store_id))
			{
				$store_id_filter = " AND store_id = $store_id ";
			}
			if($type != null && !empty($type))
			{
				$type_filter = " AND type = '$type' ";
			}
			if($start_date != null && !empty($start_date))
			{
				$start_date_filter = " AND added_on > '$start_date' ";
			}
			if($end_date != null && !empty($end_date))
			{
				$end_date_filter = " AND added_on < '$end_date' ";
			}
			if($start_id != null && !empty($start_id))
			{
				$start_id_filter = " AND id > $start_id ";
				$order_by_filter = " ORDER BY id ASC ";
			}
			else if( $end_id != null && !empty($end_id))
			{
				$end_id_filter = " AND id < $end_id ";
				$order_by_filter = " ORDER BY id DESC ";
			}

			if( $limit != null && !empty($limit) )
				$limit_filter = " LIMIT $limit";
			
			
			$sql = "SELECT `id`, `type`, `added_on` AS `date`, `description` 
							FROM `assoc_activity` WHERE `org_id` = $this->org_id 
							AND `assoc_id` = $this->id 
							$type_filter
							$start_date_filter
							$end_date_filter
							$store_id_filter
							$start_id_filter
							$end_id_filter
							$order_by_filter
							$limit_filter";
			
			if($start_id != null && !empty($start_id))
				$sql = "SELECT * FROM ($sql) AS temp_table ORDER BY temp_table.id DESC";
			
			return $this->db_user->query($sql);
		}
		
		public function saveActivity($type, $description, $ref_id, $time)
		{
			$safe_description = Util::mysqlEscapeString($description);
			$safe_type = Util::mysqlEscapeString($type);
			
			if($this->id <= 0)
			{
				$this->logger->error("Associate Id is not valid, please load the Associate First");
				return -1;
			}
			$till_id = $this->currentuser->user_id;
			

			$sql = "INSERT INTO assoc_activity(
						org_id, assoc_id, type, store_id,
						till_id, added_on, description, ref_id)
					VALUES(
						$this->org_id, $this->id, '$safe_type', $this->store_id,
						$till_id, '$time', '$safe_description', $ref_id
					) ";
			return $this->db_user->insert($sql);
		}

		public function assocBelongsToCurrentStore($assoc_id, $till_id)
		{
			//check whether user belong to the org
			$associate_model_extension = new ApiAssociateModelExtension();
			$associate_model_extension->load($assoc_id);
			$till_id = $this->currentuser->user_id ;
			$store_id = OrgEntityModelExtension::getParentStoresWithTills( $this->org_id ,array($till_id), 'STORE');
			$store_id = $store_id[$till_id];
		
			// the associate login is not proper
			if($store_id['parent_store_code'] != $associate_model_extension->store_code)
			{
				return false;
			}
			
			return true;
		}
		/**
		 * 
		 * Inserting for csv file upload
		 * @param unknown_type $assoc_details
		 */
		public function insertAssociates( $assoc_details ){
			
			$sql = "INSERT INTO masters.`associates`
													   ( 
														`org_id` ,`associate_code` ,`firstname`,`lastname`,`mobile`,`email`,
														`store_id`,`store_code`,`updated_by`,`updated_on`,`added_on`,`added_by`,`is_active`
														) VALUES 
						";
			
			$sql .= $assoc_details;

			return $this->database->insert( $sql );	
		}
		
		/**
		 * 
		 * Getting Associate User Profile by mobile or assoc code.
		 * @param unknown_type $mobile
		 * @param unknown_type $assoc_code
		 */
		public function getAssociateUserProfileByMobileOrAssocCode( $mobile , $assoc_code , $org_id ){
			
			$sql = "SELECT *  FROM masters.`associates` 
					WHERE `mobile` = '$mobile' OR `associate_code` = '$assoc_code'
					AND `org_id` = $org_id 
					";
			
			return $this->database->query( $sql );
		}
		
		public function storeCodeByStoreId( $store_id , $org_id ){
			
			$sql = "SELECT `code` , `is_active` FROM masters.`org_entities` 
					WHERE `id` = '$store_id' 
					AND `org_id` = $this->org_id";
			
			return $this->database->query( $sql ); 
		}
		
		public function checkAssocDuplicateStoreId( $assoc_code , $store_id ){
			
			$sql = "SELECT `store_id` FROM masters.`associates` 
					WHERE `associate_code` = '$assoc_code' 
					AND `org_id` = $this->org_id AND `store_id` = '$store_id'";
			
			return $this->database->query( $sql );
		}
		
		public function getAssociatesByStoreId($org_id, $store_id, $start_id, $batch_size)
		{
			if ( (empty($org_id) && $org_id != 0) || empty($store_id) )
			{
				$this->logger->debug("store id or org id is blank, returning false");
				return false;
			}
			if (empty($start_id) && $start_id <= 0)
			{
				$start_id = 0;
			}
			if (empty($batch_size) || $batch_size <= 0)
			{
				$batch_size = 10;
			}
			
			$sql = "
						SELECT 	associates.id, associate_code, 
								firstname, lastname, mobile, 
								email, store_id, store_code 
							FROM associates  
							JOIN org_entities
							ON associates.store_id = org_entities.id
							AND associates.org_id = org_entities.org_id
						WHERE associates.org_id = $org_id
							AND associates.store_id = $store_id
							AND associates.id > $start_id
							AND org_entities.type = 'STORE'
							ORDER BY associates.id
							LIMIT $batch_size
					";
			
			return $this->database->query($sql);
		}
	
		
		public function getAllAssociates($org_id, $start_id = 0, $batch_size = 0, $store_id = null )
		{
			if ( empty($org_id) && $org_id != 0)
			{
				$this->logger->debug("org id is blank, returning false");
				return false;
			}
			if (empty($start_id) && $start_id <= 0)
			{
				$start_id = 0;
			}
			if (empty($batch_size) || $batch_size <= 0)
			{
				$batch_size_filter = '';
			}
			else
			{
				$batch_size_filter = " LIMIT $batch_size ";
			}
				
			if($store_id !== null)
			{
				$store_filter = " AND a.store_id = $store_id ";
			}
			$sql = "
						SELECT 	a.id, 'associate' as type, a.associate_code as code, 
								a.firstname, a.lastname, a.mobile, 
								a.email, store_id, oe.name as store_name
							FROM associates as a 
							JOIN org_entities as oe
								ON a.store_id = oe.id
								AND a.org_id = oe.org_id
						WHERE a.org_id = $org_id
							AND a.id > $start_id
							AND oe.type = 'STORE'
							$store_filter
							ORDER BY a.id
							$batch_size_filter
					";
			
			$ret = $this->database->query($sql);

			//TODO : for each add username also; not used as of now
			
			return $ret;
		}
		
		public function getAssociatesByIds($org_id, $ids)
		{
			
			if ( empty($org_id) )
			{
				$this->logger->debug("org id is blank, returning false");
				return false;
			}
			if( empty($ids) )
			{
				$this->logger->debug("ids are blank, returning false");
				return false;
			}
			if(!is_array($ids) || count($ids) == 0 )
			{
				$this->logger->debug("ids is not array, 
						or passed array does not have enough items, returning false");
				return false;
			}
			$ids_filter = implode(",", $ids);
				
			$sql = "
				SELECT 	a.id, 'associate' as type, a.associate_code as code,
						a.firstname, a.lastname, a.mobile,
						a.email, store_id, oe.name as store_name
					FROM associates as a
					JOIN org_entities as oe
						ON a.store_id = oe.id
						AND a.org_id = oe.org_id
					WHERE a.org_id = $org_id
					AND a.id IN ($ids_filter)
					AND oe.type = 'STORE'
					ORDER BY a.id
			";
				
			$ret = $this->database->query_hash($sql, "id", 
					array("id", "type", "code", 
							"firstname", "lastname", "mobile", 
							"email", "store_id", "store_name",));
			
			//TODO : for each add username also
			return $ret;
		}
		
	
		
		public function getAssociateByOrg(){
			
			$sql = "SELECT assoc.`id` ,`associate_code` ,assoc.`firstname` , assoc.`lastname`,
						   assoc.`mobile`, assoc.`email`, assoc.`store_code`,
						   assoc.`updated_on`, assoc.`added_on`,assoc.`is_active` 
					FROM `associates` assoc 
					WHERE  assoc.`org_id` = $this->org_id
					";
			//INNER JOIN `loggable users` lu ON assoc.`id` = lu.`ref_id` AND lu .`type` = 'ASSOCIATE'
			return $this->database->query( $sql );
		}
		
		/**
		 * It will insert the assoc login details for brakes
		 */
		public function trackAssocLoginDetails( $username , $password ){
			
			$sql = "INSERT INTO `brakes`.`assoc_logins` (`username`, `password`, `login_time`) 
						VALUES ('$username', '$password', '".date('Y-m-d H:i:s')."')";
			
			return $this->database->insert( $sql );
		}

		/**
		 * 
		 * Checking Mobile number if it exist for other user or not.
		 * @param unknown_type $mobile
		 * @param unknown_type $assoc_id
		 */
		public function checkDuplicateMobile( $mobile , $assoc_id ){

			$sql = "SELECT * FROM masters.`associates` 
					WHERE `mobile` = '$mobile' 
					AND `id` != '$assoc_id' 
					AND `org_id` = '$this->org_id'";
			
			return $this->database->query( $sql );
		}
		
		/**
		 * 
		 * Check if email is exist for other user not.
		 * @param unknown_type $email
		 * @param unknown_type $assoc_id
		 */
		public function checkDuplicateEmail( $email , $assoc_id ){

			$sql = "SELECT * FROM masters.`associates` 
					WHERE `email` = '$email' 
					AND `id` != '$assoc_id' 
					AND `org_id` = '$this->org_id'";
			
			return $this->database->query( $sql );
		}
	}
	?>
