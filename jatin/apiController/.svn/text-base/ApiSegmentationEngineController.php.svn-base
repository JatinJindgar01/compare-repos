<?php
//TODO: referes to cheetah
require_once 'model_extension/SegmentationEngineModel.php';
//TODO: referes to cheetah
include_once 'thrift/segmentation.php';
/**
 * 
 * @author ketaki
 *
 */

class ApiSegmentationEngineController extends ApiBaseController{

	private $thrift_client;
	protected $segmentationEngineModel;
		
	public function ApiSegmentationEngineController(){

		parent::__construct();
		
		global $currentorg, $currentuser;
		
		$this->segmentationEngineModel = new SegmentationEngineModel();
		$this->org_id = $currentorg->org_id;
		$this->user_id = $currentuser->user_id;
        $this->thrift_client = new SegmentationEngineThriftClient(1000);
	}

    /**
     * @param $user_id
     * @return mixed
     */
    public function getUserSegmentMapping( $user_id ){
		
		$session_id = $this->thrift_client->createSessionId( Util::getServerUniqueRequestId(),
                                                             $this->user_id,
															 $this->org_id
														   );
		$session_id->moduleName = 'API';
        $customer_segment = $this->thrift_client->getUserMapping( $this->org_id, $user_id , $session_id );
//         $this->logger->debug("Segmentation engine call result: " . print_r($customer_segment, true));
        return $customer_segment;
	}
}
?>
