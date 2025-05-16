<?php 

	require_once 'apiModel/class.ApiCommunicationTemplate.php';

	/*
	*
	* -------------------------------------------------------
	* CLASSNAME:        ApiCommunicationTemplateModelExtension extends ApiCommunicationTemplateModel
	* GENERATION DATE:  09.05.2011
	* CREATED BY:       Kartik Gosiya
	* FOR MYSQL TABLE:  communication_templates
	* FOR MYSQL DB:     masters
	*
	*/

	class ApiCommunicationTemplateModelExtension extends ApiCommunicationTemplateModel
	{
		
		private $db_master ;
		private $db_user ;

		//some elements
		private $last_login;
		
		public function __construct()
		{
			
			parent::ApiCommunicationTemplateModel();
			
			$this->db_user = new Dbase( 'users' );
			
		}	
			
		public function getAllTemplates( $org_id , $type = '')
		{
			$type_filter = '';
			if(!empty($type))
			{
				$type = strtoupper($type);
				$type_filter = " AND type = '$type' ";
			}
			
			$sql = "
					SELECT id, type, title, subject, body, 
						CASE 
							is_editable 
							WHEN 1 THEN 'TRUE'
							WHEN 0 THEN 'FALSE'
						END AS is_editable
						FROM communication_templates 
						WHERE org_id = $org_id
						$type_filter 
					";
			$result = $this->database->query($sql);
			if(!$result || count($result) <= 0)
				throw new Exception('ERR_NO_TEMPLATE_FOUND');
			return $result;
		}
		
		
	}
	?>