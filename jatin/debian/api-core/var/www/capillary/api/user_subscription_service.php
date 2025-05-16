<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pigol
 * Date: 8/5/13
 * Time: 12:07 AM
 * To change this template use File | Settings | File Templates.
 *
 * Service which handles the users subscription preferences
 * @author pigol
 */

ini_set('display_errors', 'off');
error_reporting('E_NONE');

require_once 'common.php';
require_once 'business_controller/UserSubscriptionController.php';

require_once $GLOBALS["LIB"]. 'thrift_base.php';
require_once $GLOBALS['GEN_DIR'].'/user_subscription_service/UserSubscriptionService.php';
require_once $GLOBALS['GEN_DIR'].'/user_subscription_service/user_subscription_service_types.php';


$logger = new ShopbookLogger();
$logger->debug("User Subscription Service initialized");

$old_level = error_reporting('E_NONE');
error_reporting($old_level);


class UserSubscriptionServiceHandler implements usersubscription_UserSubscriptionServiceIf {

    private $logger;

    function __construct() {

        global $logger;
        $this->logger = $logger;
    }


    public function isUserSubscribed($user_id, $org_id, $msg_channel, $msg_scope, $msg_priority) {

        $this->logger->debug("Checking user subscriptions for user: $user_id, org_id: $org_id, channel: $msg_channel, scope: $msg_scope, priority: $msg_priority");
        $subscription_cntrl = new UserSubscriptionController($org_id);
        try{

            $user_subscription_status = $subscription_cntrl->isUserSubscribed($user_id, $msg_channel, $msg_scope, $msg_priority);
            $this->logger->debug("User subscription status: $user_subscription_status");
            return $user_subscription_status;

        }catch(RuntimeException $e){

            $this->logger->error("Caught runtime exception while fetching user subscription: " . $e->getMessage());
            $ex = new usersubscription_UserSubscriptionServiceException();
            $ex->statusCode = $e->getCode();
            $ex->errorMessage = $e->getMessage();
            throw $ex;

        }catch(Exception $e){

            $this->logger->error("Generic exception found while checking user subscription:" . $e->getMessage());
            $ex = new usersubscription_UserSubscriptionServiceException();
            $ex->statusCode = $e->getCode();
            $ex->errorMessage = $e->getMessage();

        }
    }



    public function areUsersSubscribed($user_ids, $org_id, $msg_channel, $msg_scope, $msg_priority) {

        $this->logger->debug("Checking subscriptions for multiple users, org_id: $org_id, channel: $msg_channel, scope: $msg_scope, priority: $msg_priority");
        $cntrl = new UserSubscriptionController($org_id);

        try{

            $subscriptions = $cntrl->areUsersSubscribed($user_ids, $msg_channel, $msg_priority, $msg_scope);
            $this->logger->debug("Found subscriptions for users");
            return $subscriptions;
        }catch(RuntimeException $e){

            $this->logger->error("Runtime exception from controller: " . $e->getMessage());
            $ex = new usersubcription_UserSubscriptionServiceException();
            $ex->statusCode = $e->getCode();
            $ex->errorMessage = $ex->getMessage();
            throw $ex;

        }catch(Exception $e){

            $this->logger->error("Generic exception from controller: " . $e->getMessage());
            $ex = new usersubcription_UserSubscriptionServiceException();
            $ex->statusCode = $e->getCode();
            $ex->errorMessage = $ex->getMessage();
            throw $ex;
        }

    }


    public function getUserSubscriptions($user_id, $org_id) {

        $this->logger->debug("Getting the subscriptions for user: $user_id, $org_id");
        $cntrl = new UserSubscriptionController($org_id);
        try{

            $subscriptions = $cntrl->getSubscriptionsForUser($user_id);

            $user_subscriptions = new usersubscription_UserSubscriptions();
            $user_subscriptions->user_id = $user_id;
            $user_subscriptions->org_id = $org_id;
            $user_subscriptions->user_subscription_map = $this->createUserSubscriptionsMap($subscriptions);
            return $user_subscriptions;

        }catch(RuntimeException $e){

        }catch(Exception $ex){

        }

    }


    private function createUserSubscriptionsMap($subscriptions)
    {
        $user_subscription_map = array();
        $channel = 'ALL';

        foreach($subscriptions as $s){

            $sub_type = $s['type'];
            $scope = $s['scope'];
            $priorities = $s['priority'];

            if(in_array($sub_type, UserSubscriptionController::$channel_type_map['SMS'])){
                $channel = 'SMS';
            }else if(in_array($sub_type, UserSubscriptionController::$channel_type_map['EMAIL'])){
                $channel = 'EMAIL';
            }else{
                //all channels are supported here
                $channel = 'ALL';
            }

            if(!isset($user_subscription_map[$channel])){
                $user_subscription_map[$channel] = array();
            }

            foreach($priorities as $p){
                if(!isset($user_subscription_map[$channel][$p])){
                    $user_subscription_map[$channel][$p] = array();
                }
                array_push($user_subscription_map[$channel][$p], $scope);
            }
        }

        return $user_subscription_map;
    }


    public function getSubscriptionsForUsers($user_ids, $org_id) {

        $this->logger->debug("Fetching subscriptions for multiple users: org_id: $org_id, count: ".count($user_ids));
        $cntrl = new UserSubscriptionController($org_id);

        try{

            $subscriptions = $cntrl->getSubscriptionsForMultipleUsers($user_ids);
            $this->logger->debug("Fetched the subscriptions");
            $response = array();
            foreach($subscriptions as $u=>$s){

                $user_subscriptions = new usersubscription_UserSubscriptions();
                $user_subscriptions->user_id = $u;
                $user_subscriptions->org_id = $org_id;
                $user_subscriptions->user_subscription_map = $this->createUserSubscriptionsMap($s);
                $response[$u] = $user_subscriptions;
            }

            $this->logger->debug("Returning the list of subscriptions");
            return $response;
        }catch(RuntimeException $e){

        }catch(Exception $e){

        }
    }


    public function getSubscribedUsers($org_id, $msg_Channel, $msg_priority, $scope, $start_id=0, $count=5000) {

    }


    public function __destruct() {
        //$this->transport->close();
    }

}


$logger->debug("Loading usersubscription service handler");
$handler = new UserSubscriptionServiceHandler();

header('Content-Type: application/x-thrift');
$logger->debug("Service Handler loaded");

$processor = new UserSubscriptionServiceProcessor($handler);
$logger->debug("Processor created");

$transport = new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W);

$protocol = new TBinaryProtocol($transport);

$logger->debug("Opening the connection");
$transport->open();

$processor->process($protocol, $protocol); //it takes in input, and output, which happen to be the same in our case
$transport->close();
$logger->debug("Processing done. Closing the connection");
?>
