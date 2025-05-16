<?php

/**
 * Description of triggers
 *
 * @author rohit
 */

class TriggersResource extends BaseResource{

	function __construct()
	{
		parent::__construct();
	}


	public function process($version, $method, $data, $query_params, $http_method)
	{
		if(!$this->checkVersion($version))
		{
			$this->logger->error("Unsupported Version : $version");
			$e = new UnsupportedVersionException(ErrorMessage::$api['UNSUPPORTED_VERSION'], ErrorCodes::$api['UNSUPPORTED_VERSION']);
			throw $e;
		}

		if(!$this->checkMethod($method)){
			$this->logger->error("Unsupported Method: $method");
			$e = new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
			throw $e;
		}
		
		$result = array();
		try{
	
			switch(strtolower($method)){

				case 'actions' :
					$result = $this->actions($query_params);
                                        break;
				default :
					$this->logger->error("Should not be reaching here");
						
			}
		}catch(Exception $e){
			$this->logger->error("Caught an unexpected exception, Code:" . $e->getCode()
			. " Message: " . $e->getMessage()
			);
			throw $e;
		}
			
		return $result;
	}
        
        public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1', 'v1.1'))){
			return true;
		}
		return false;
	}

	public function checkMethod($method)
	{
		if(in_array(strtolower($method), array('actions')))
		{
			return true;
		}
		return false;
	}
        
        private function actions($query_params)
        {
            $orgController = new ApiOrganizationController();
            if(isset($query_params['code']))
                $actions = $orgController->getSupportedIncomingInteractionActions($query_params['code']);
            else {
                $actions = $orgController->getSupportedIncomingInteractionActions();
            }
            
            $response = array(
                "status" => array(
                    "code" => ErrorCodes::$api["SUCCESS"],
                    "success" => true,
                    "message" => ErrorMessage::$api['SUCCESS']
                    ),
                "actions" => $actions
                );
            return $response;
        }
        
}