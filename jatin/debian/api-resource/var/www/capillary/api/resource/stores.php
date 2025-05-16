<?php

require_once "resource.php";

require_once "apiController/ApiFileController.php";

/**
 * Exposes all information of a store for an organization
 *
 * get: fetches details of a individual store
 *
 */
class StoreResource extends BaseResource {

    function __construct() {
        parent::__construct();
    }

    public function process($version, $method, $data, $query_params, $http_method) {
        if (!$this->checkVersion($version)) {
            $this->logger->error("Unsupported Version : $version");
            $e = new UnsupportedVersionException(ErrorMessage::$api['UNSUPPORTED_VERSION'], ErrorCodes::$api['UNSUPPORTED_VERSION']);
            throw $e;
        }

        if (!$this->checkMethod($method)) {
            $this->logger->error("Unsupported Method: $method");
            $e = new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
            throw $e;
        }

        $result = array();
        try {

            switch (strtolower($method)) {

                case 'get' :

                    $result = $this->get($query_params);
                    break;

                case 'feedback' :

                    $result = $this->customerFeedBack($data, $query_params = false);
                    break;

                case 'get_feedback_info' :

                    $result = $this->getFeedbackInfo($query_params);
                    break;

                case 'staff':
                    $result = $this->staff($query_params);
                    break;

                case 'login':
                    $result = $this->login($data, $query_params);
                    break;

                case 'logs':
                    $result = $this->logs($query_params, $data);
                    break;

                case 'diagnostics':
                    $result = $this->diagnostics($query_params, $data);
                    break;

                case 'tasks':
                    $result = $this->tasks($query_params);
                    break;

                case 'configurations':
                    $result = $this->configurations($query_params);
                    break;

                case 'files':
                    $result = $this->files($query_params, $data);
                    break;

                case 'reports':
                    $result = $this->reports($data, $query_params, $http_method);
                    break;

                default :
                    $this->logger->error("Should not be reaching here");
            }
        } catch (Exception $e) {

            $this->logger->error("Caught an unexpected exception, Code:" . $e->getCode()
                    . " Message: " . $e->getMessage()
            );
            throw $e;
        }

        return $result;
    }

    /**
     * Returns details of individual store
     * @param array $query_params
     */
    private function get($query_params) {
        $C_storesController = new ApiStoreController();


        $store = $query_params;
        if (isset($store['id']))
            $identifier = 'id';
        else if (isset($store['code']))
            $identifier = 'code';
        else if (isset($store['external_id']))
            $identifier = 'external_id';
        else {
            $error_key = 'ERR_NO_IDENTIFIER';
            $this->logger->error("No Identifier Found for store get");
            throw new Exception(ErrorMessage::$api['INVALID_INPUT'] . ' , ' .
            ErrorMessage::$stores['ERR_NO_IDENTIFIER'], ErrorCodes::$api['INVALID_INPUT']);
        }

        $this->logger->debug("Getting store details");
        $stores_array = StringUtils::strexplode(',', $query_params[$identifier]);

        if (sizeof($stores_array) == 0) {

            $this->logger->error("No Store id passed");
            throw new Exception("ERR_NO_STORE_ID");
        }
        global $error_count, $gbl_item_status_codes;
        $arr_item_status_codes = array();
        $error_count = 0;
        $stores_count = 0;
        $response = array();
        foreach ($stores_array as $key => $store_identifier) {

            try {

                $error_key = "ERR_STORE_GET_SUCCESS";
                ++$stores_count;
                if ($identifier == 'id') {
                    $this->logger->debug("Getting store details for store id :$store_identifier ");
                    $store_info = $C_storesController->getInfoDetails($store_identifier);
                } else if ($identifier == 'code') {
                    $this->logger->debug("Getting store details for store code :$store_identifier ");
                    $store_info = $C_storesController->getInfoDetailsByStoreCode($store_identifier);
                } else if ($identifier == 'external_id') {
                    $this->logger->debug("Getting store details for store external_id :$store_identifier ");
                    $store_info = $C_storesController->getInfoDetailsByExternalId($store_identifier);
                }

                $cfs = array();

                if (!$store_info) {

                    $this->logger->error("Invalid Store id :$store_identifier");
                    throw new Exception("ERR_INVALID_STORE_IDENTIFIER");
                } else {
                    $cfs = $C_storesController->getCustomFieldsData($store_info[0]['id']);
                }

                $store_info = array_pop($store_info);

                $new_store_info = array();
                $new_store_info['id'] = $store_info['id'];
                $new_store_info['name'] = $store_info['store_name'];
                $new_store_info['code'] = $store_info['store_code'];
                $new_store_info['mobile'] = $store_info['mobile'];
                $new_store_info['email'] = $store_info['email'];
                $new_store_info['land_line'] = $store_info['land_line'];
                $new_store_info['external_id'] = $store_info['external_id'];
                $new_store_info['external_id_1'] = $store_info['external_id_1'];
                $new_store_info['external_id_2'] = $store_info['external_id_2'];
                if (!empty($cfs)) {
                    $new_store_info['custom_fields']['field'] = $cfs;
                }
                $new_store_info['location'] = array(
                    'country' => $store_info['country_name'],
                    'state' => $store_info['state_name'],
                    'city' => $store_info['city_name'],
                    'area' => $store_info['area_name'],
                    'coordinates' => array(
                        'latitude' => $store_info['Latitude'],
                        'longitude' => $store_info['Longitude']
                    )
                );

                //ISO changes
                $new_store_info['currencies'] = array(
                    'base_currency' => array('label' => $store_info['currency_code'],
                        'symbol' => $store_info['currency_symbol'])
                );
                $new_store_info['languages'] = array(
                    'base_language' => array('lang' => $store_info['language_code'],
                        'locale' => $store_info['language_locale'])
                );
                $new_store_info['timezones'] = array(
                    'base_timezone' => array('label' => $store_info['timezone_label'],
                        'offset' => $store_info['timezone_offset']
                    )
                );

                //Backward compatible
                $new_store_info['currency'] = array(
                    'name' => $store_info['currency_name'],
                    'symbol' => $store_info['currency_symbol'],
                    'iso_code' => array(
                        'alpha' => $store_info['currency_code_alpha'],
                        'numeric' => $store_info['currency_code_numeric']
                    )
                );
                $new_store_info['language'] = $store_info['language'];
                $new_store_info['time_zone'] = array(
                    'coordinates' => $store_info['timezone_coordinates'],
                    'offset' =>
                    array(
                        'std' => $store_info['std_offset'],
                        'summer' => $store_info['summer_offset']
                    )
                );
                $new_store_info['mobile'] = $store_info['mobile'];
                $new_store_info['email'] = $store_info['email'];
                $new_store_info['land_line'] = $store_info['land_line'];
                $new_store_info['external_id'] = $store_info['external_id'];
                $new_store_info['external_id_1'] = $store_info['external_id_1'];
                $new_store_info['external_id_2'] = $store_info['external_id_2'];

                $new_store_info['countries'] = array(
                    'base_country' => array('name' => $store_info['country_name'],
                        'code' => $store_info['country_code']
                    )
                );

                $new_store_info['state_name'] = $store_info['state_name'];
                $new_store_info['city_name'] = $store_info['city_name'];
                $new_store_info['area_name'] = $store_info['area_name'];
                $new_store_info['template'] = array(
                    'sms' => array(
                        'name' => $store_info['name_template_sms'],
                        'mobile' => $store_info['mobile_template_sms'],
                        'email' => $store_info['email_template_sms'],
                    ),
                    'email' => array(
                        'name' => $store_info['name_template_email'],
                        'mobile' => $store_info['mobile_template_email'],
                        'email' => $store_info['email_template_email'],
                    )
                );
                $store_info = $new_store_info;
            } Catch (Exception $e) {

                ++$error_count;
                $error_key = $e->getMessage();
                $this->logger->error("Caught Exception: " . ErrorMessage::$stores[$e->getMessage()]);

                //adding store_id just in case of exception to know which item status failed
                $store_info = array($identifier => $store_identifier);
            }

            $store_info['item_status']['success'] = ( $error_key == "ERR_STORE_GET_SUCCESS" ) ? 'true' : 'false';
            $store_info['item_status']['code'] = ErrorCodes::$stores[$error_key];
            $store_info['item_status']['message'] = ErrorMessage::$stores[$error_key];
            $arr_item_status_codes[] = $store_info['item_status']['code'];
            array_push($response, $store_info);
        }

        //Status
        $status = 'SUCCESS';
        if ($stores_count == $error_count) {

            $status = 'FAIL';
        } else if (( $error_count < $stores_count ) && ( $error_count > 0 )) {

            $status = 'PARTIAL_SUCCESS';
        }
        $gbl_item_status_codes = implode(",", $arr_item_status_codes);
        $root['status']['success'] = ($status == 'SUCCESS' || $status == 'PARTIAL_SUCCESS') ? 'true' : 'false';
        $root['status']['code'] = ErrorCodes::$api[$status];
        $root['status']['message'] = ErrorMessage::$api[$status];
        $root['stores']['store'] = $response;

        //$this->logger->debug("Response: " . print_r($root, true));

        return $root;
    }

    public function customerFeedBack($data, $query_params) {

        $customer = $data['root']['customer'];

        $this->logger->debug('@@@CUSTOMER FEEDBACK DATA' . print_r($customer, true));

        $store_controller = new ApiStoreController();

        $api_status_code = 'SUCCESS';

        $response = 'Feedback Added Successfully';
        $status = 'SUCCESS';

        $customer_controller = new ApiCustomerController();

        $user_id = -1;
        $mobile = $customer[0]['mobile'];
        $email = $customer[0]['email'];
        $external_id = $customer[0]['external_id'];
        $store_id = $query_params['store_id'];

        $custom = new CustomFields();

        $api_status = $root = array();

        try {

            if ($mobile || $email || $external_id) {

                $user = $this->getUser($mobile, $email, $external_id);

                $user_id = $user->getUserId();

                $customer_data = $customer_controller->getUserData($user->mobile);

                $store_id = $customer_data['store_id'];
            }

            $assoc_id = $store_controller->addStoreFeedback($user_id, $store_id);

            $custom_fields = array();

            foreach ($customer[0]['custom_fields'] as $row) {

                array_push($custom_fields, $row);
            }

            $custom_result = $custom->addCustomFieldDataForAssocIdNewApi($assoc_id, $custom_fields[0]);

            if (!$custom_result) {
                $response = 'Feedback Not Added';
                $status = 'FAIL';
            }

            $root['status']['success'] = ($status == 'SUCCESS' || $status == 'PARTIAL_SUCCESS') ? 'true' : 'false';
            $root['status']['code'] = ErrorCodes::$api[$status];
            $root['status']['message'] = ErrorMessage::$api[$status];
            $root['stores']['feedback'] = $response;
        } catch (Exception $e) {

            $api_status = array(
                "success" => "true",
                "code" => ErrorCodes::$api[$api_status_code],
                "message" => ErrorMessage::$api[$api_status_code],
                "feedback" => 'Feedback Not Added'
            );
        }

        return array_merge($root, $api_status);
    }

    public function getFeedbackInfo($query_params) {

        $mobile = $query_params['mobile'];
        $email = $query_params['email'];
        $external_id = $query_params['external_id'];
        $store_id = $query_params['store_id'];

        $api_status_code = 'SUCCESS';

        $status = 'SUCCESS';
        $api_status = $root = array();
        $customer_controller = new ApiCustomerController();

        try {

            if ($mobile || $email || $external_id) {

                $user = $this->getUser($mobile, $email, $external_id);

                $user_id = $user->getUserId();

                $customer_data = $customer_controller->getUserData($user->mobile);

                $store_id = $customer_data['store_id'];
            }

            $store_controller = new ApiStoreController();

            $result = $store_controller->getStoreFeedbackCount($store_id, 'TODAY');
            $response['total_feedback_today'] = $result['no_of_feedback'];

            $result = $store_controller->getStoreFeedbackCount($store_id, 'WEEKLY');
            $response['total_feedback_this_week'] = $result['no_of_feedback'];

            $result = $store_controller->getStoreFeedbackCount($store_id, 'MONTH');
            $response['total_feedback_this_month'] = $result['no_of_feedback'];

            if (!$result)
                $status = 'FAIL';

            $root['status']['success'] = ($status == 'SUCCESS' || $status == 'PARTIAL_SUCCESS') ? 'true' : 'false';
            $root['status']['code'] = ErrorCodes::$api[$status];
            $root['status']['message'] = ErrorMessage::$api[$status];
            $root['stores']['feedback'] = $response;
        } catch (Exception $e) {

            $api_status = array(
                "success" => "true",
                "code" => ErrorCodes::$api[$api_status_code],
                "message" => ErrorMessage::$api[$api_status_code]
            );
        }

        return array_merge($root, $api_status);
    }

    private function staff($query_params) {
        $api_status_code = 'SUCCESS';

        $start_id = 0;
        $batch_size = 0;

        if (isset($query_params['start_id']))
            $start_id = (integer) $query_params['start_id'];
        if (isset($query_params['batch_size']))
            $start_id = (integer) $query_params['batch_size'];
        $type = isset($query_params['type']) ? strtolower($query_params['type']) : 'associate';

        if ($type == 'manager') {
            //TODO: need to add logic for fetching the managers.
            $staff = null;
        } else {
            $associate_controller = new ApiAssociateController();
            $this->logger->debug("going to fetch all information about associates");
            $staff = $associate_controller->getAllAssociateDetails($start_id, $batch_size);
        }

        if ($staff) {
            $staff = array("staff" => array("user" => $staff));
        } else {
            $staff = null;
        }

        $api_status = array(
            'success' => ErrorCodes::$api[$api_status_code] ==
            ErrorCodes::$api['SUCCESS'] ? true : false,
            'code' => ErrorCodes::$api[$api_status_code],
            'message' => ErrorMessage::$api[$api_status_code]
        );
        return array(
            'status' => $api_status,
            'store' => $staff
        );
    }

    /**
     * uploads performance logs from client to file service by default. If type= storeserverperf then upload store server
     * performance logs
     * @param $data
     * @param $query_params
     * @return array
     */
    private function logs($query_params, $data) {
        $api_status_code = "SUCCESS";

        try {
            if (strcasecmp($query_params['type'], 'storeserverperf') == 0) {
                $this->logger->debug("store server performance log collection");

                if (!is_array($data['root']['store_server_health'])) {
                    throw new Exception(ErrorMessage::$api['FAIL'] . ' , ' .
                    ErrorMessage::$stores['ERR_INVALID_DATA'], ErrorCodes::$stores['ERR_INVALID_DATA']);
                }

                $perf_data = $data['root']['store_server_health'][0];

                $ss_uptime = $perf_data['up_time'];
                $ss_request_processed = $perf_data['request_processed'];
                $ss_os = $perf_data['os'];
                $ss_os_platform = $perf_data['os_platform'];
                $ss_processor = $perf_data['processor'];
                $ss_system_ram = $perf_data['system_ram'];
                $ss_db_size = $perf_data['db_size'];
                $ss_lan_speed = $perf_data['lan_speed'];
                $ss_last_transaction_time = $perf_data['last_transaction_time'];
                $ss_avg_mem = $perf_data['performance_logs']['memory']['avg_memory_usage'];
                $ss_peak_mem = $perf_data['performance_logs']['memory']['peak_memory_usage'];
                $ss_avg_cpu = $perf_data['performance_logs']['cpu']['avg_cpu_usage'];
                $ss_peak_cpu = $perf_data['performance_logs']['cpu']['peak_cpu_usage'];
                $ss_last_txn_to_svr = $perf_data['last_txn_to_svr'];
                $ss_last_regn_to_svr = $perf_data['last_regn_to_svr'];
                $ss_report_generation_time = $perf_data['report_generation_time'];
                $ss_last_login = $perf_data['last_login'];
                $ss_last_fullsync = $perf_data['last_fullsync'];
                $ss_curr_version = $perf_data['current_version'];
                $ss_available_version = $perf_data['available_version'];

                $store_controller = new ApiStoreController();

                //insert top level store server stats
                $status_store_stats = $store_controller->addStoreServerStats($ss_uptime, $ss_request_processed, $ss_os, $ss_os_platform, $ss_processor, $ss_system_ram, $ss_db_size, $ss_lan_speed, $ss_last_transaction_time, $ss_avg_mem, $ss_peak_mem, $ss_avg_cpu, $ss_peak_cpu, $ss_last_txn_to_svr, $ss_last_regn_to_svr, $ss_report_generation_time, $ss_last_login, $ss_last_fullsync, $ss_curr_version, $ss_available_version);

                //insert sync logs
                $sync_data = $data['root']['sync_logs'][0];
                $status_sync_logs = $store_controller->addStoreServerSyncLogs($sync_data, $status_store_stats);

                //insert till reports
                $status_till_reports = true;
                if ($data['root']['till_reports'])
                    if ($data['root']['till_reports'][0]) {
                        if (isset($data['root']['till_reports'][0]['till_report']['username'])) {
                            $data['root']['till_reports'][0]['till_report'] = array($data['root']['till_reports'][0]['till_report']);
                        }
                        if ($data['root']['till_reports'][0]['till_report']) {
                            $till_reports = $data['root']['till_reports'][0]['till_report'];

                            $status_till_reports = $store_controller->addStoreServerTillReports($till_reports, $status_store_stats);
                        }
                    }


                //insert sql_server_health_report
                $status_sql_svr_health = true;
                if ($data['root']['sql_server_health_report'])
                    if ($data['root']['sql_server_health_report'][0]) {
                        $sql_svr_health_report = $data['root']['sql_server_health_report'][0];
                        $status_sql_svr_health = $store_controller->addStoreSvrSQLSvrStats($sql_svr_health_report, $status_store_stats);
                    }

                //insert wcf health report
                $wcf_status = true;
                if ($data['root']['wcf_report'])
                    if ($data['root']['wcf_report'][0]) {
                        $wcf_report = $data['root']['wcf_report'][0];
                        $wcf_status = $store_controller->addStoreSvrWCFStats($wcf_report, $status_store_stats);
                    }

                //insert bulk upload report
                $status_bulk_upload = true;
                if ($data['root']['bulk_upload'])
                    if ($data['root']['bulk_upload'][0]) {
                        $bulk_upload = $data['root']['bulk_upload'][0];
                        $status_bulk_upload = $store_controller->addStoreSvrBulkUpload($bulk_upload, $status_store_stats);
                    }

                $storesController = new ApiStoreController();
                $storesController->pushToStoreCare($data);
                if (($status_store_stats == false) && ($status_sync_logs == false) && ($status_till_reports == false) &&
                        ($status_sql_svr_health == false) && ($wcf_status == false) && ($status_bulk_upload = false)) {
                    throw new Exception(ErrorMessage::$api['FAIL'] . ' , ' .
                    ErrorMessage::$stores['ERR_STORE_REP_FULL_FAIL'], ErrorCodes::$stores['ERR_STORE_REP_FULL_FAIL']);
                } else if (($status_store_stats == false) || ($status_sync_logs == false) || ($status_till_reports == false) || ($status_sql_svr_health == false) || ($wcf_status == false) || ($status_bulk_upload == false)) {
                    throw new Exception(ErrorMessage::$api['FAIL'] . ' , ' .
                    ErrorMessage::$stores['ERR_STORE_REP_PARTIAL_SUC'], ErrorCodes::$stores['ERR_STORE_REP_PARTIAL_SUC']);
                }
            } else {
                $file_name = '/tmp/' . $this->currentorg->org_id . '_' . $this->currentuser->user_id . '_' . time() . '.txt';
                file_put_contents($file_name, file_get_contents('php://input')); //uploaded file in temp.

                $input = array();
                $input['org_id'] = $this->currentorg->org_id;
                $input['user_id'] = $this->currentuser->user_id;

                if (isset($query_params['file_type']))
                    $input['file_type'] = $query_params['file_type'];
                else  //if nothing passed we will assume this is client log
                    $input['file_type'] = 'clientlog';

                if (isset($query_params['upload_type']))
                    $input['upload_type'] = $query_params['upload_type'];
                else  //if nothing passed we will assume this is automatic upload
                    $input['upload_type'] = 'automatic';

                if ($query_params['uploaded_time'])
                    $input['uploaded_time'] = $query_params['uploaded_time'];
                else
                    $input['uploaded_time'] = "NOW()";

                if ($query_params['logged_time'])
                    $input['logged_time'] = $query_params['logged_time'];
                else
                    $input['logged_time'] = "NOW()";

                $_HEADERS = apache_request_headers();
                $input['logfile_name'] = $_HEADERS['X-CAP-CLIENT-LOGFILENAME'];
                $input['client_ip'] = $_SERVER['REMOTE_ADDR'];
                $input['logfile_size'] = $_HEADERS['X-CAP-CLIENT-LOGFILESIZE'];
                $input['logfile_sha1'] = $_HEADERS['X-CAP-CLIENT-FILE-SIGNATURE'];

                $storesController = new ApiStoreController();
                $storesController->addStoreLogFileDetails($file_name, $input);
                @unlink($file_name);
            }
        } catch (Exception $e) {
            throw new Exception(ErrorMessage::$api['FAIL'] . ' , ' .
            ErrorMessage::$stores[$e->getMessage()], ErrorCodes::$stores[$e->getMessage()]);
        }

        $api_status = array(
            'success' => ErrorCodes::$api[$api_status_code] ==
            ErrorCodes::$api['SUCCESS'] ? true : false,
            'code' => ErrorCodes::$api[$api_status_code],
            'message' => ErrorMessage::$api[$api_status_code]
        );
        return array(
            'status' => $api_status
        );
    }

    /**
     * uploads client's diagnostics report, performance logs, deployment version details etc
     * @param $data
     * @param $query_params
     * @return array
     */
    private function diagnostics($query_params, $data) {
        $api_status_code = "SUCCESS";

        if (strcasecmp($query_params['type'], 'tillerrorcodes') == 0) {
            try {

                $this->logger->debug("store client diagnostics log collection : till error codes");

                if (isset($data['root']['till_diagnostics'])) {
                    $error_codes_data = $data['root']['till_diagnostics'][0]['errors']['error'];
                    if (isset($error_codes_data['code'])) {
                        $error_codes_data = array($error_codes_data);
                    }

                    $store_controller = new ApiStoreController();

                    $status_store_stats = $store_controller->addTillErrorReport($error_codes_data);

                    if ($status_store_stats == false) {
                        $api_status_code = "FAIL";
                        $api_status = array(
                            "success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
                            "code" => ErrorCodes::$stores['ERR_STORE_REP_PARTIAL_SUC'],
                            "message" => ErrorMessage::$stores['ERR_STORE_REP_PARTIAL_SUC']
                        );
                        return array(
                            "status" => $api_status
                        );
                    }

                    $api_status = array(
                        "success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
                        "code" => ErrorCodes::$api[$api_status_code],
                        "message" => ErrorMessage::$api[$api_status_code],
                            );
                    return array(
                        "status" => $api_status,
                        "errors" => $data['root']['till_diagnostics'][0]['errors']
                    );
                }
            } catch (Exception $e) {
                $api_status_code = "FAIL";
                $api_status = array("success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
                    "code" => ErrorCodes::$stores[$e->getMessage()],
                    "message" => ErrorMessage::$stores[$e->getMessage()]);
                return array(
                    "status" => $api_status
                );
            }
        } else if (strcasecmp($query_params['type'], 'tillstats') == 0) {
            $this->logger->debug("store client diagnostics log collection : till stats");

            $till_diagnostics = $data['root']['till_diagnostics'][0];

            $from = $till_diagnostics['from'];
            $to = $till_diagnostics['to'];
            $last_login = $till_diagnostics['last_login'];
            $last_fullsync = $till_diagnostics['last_fullsync'];
            $integration_mode = $till_diagnostics['integration_mode'];
            $curr_version = $till_diagnostics['current_version'];
            $available_version = $till_diagnostics['available_version'];
            $update_skip_count = $till_diagnostics['update_skip_count'];
            $last_update_time = $till_diagnostics['last_update_time'];
            $avg_mem_usage = $till_diagnostics['memory']['avg_usage'];
            $peak_mem_usage = $till_diagnostics['memory']['peak_usage'];
            $avg_cpu_usage = $till_diagnostics['cpu']['avg_usage'];
            $peak_cpu_usage = $till_diagnostics['cpu']['peak_usage'];

            $store_controller = new ApiStoreController();

            //insert top level store server stats
            $till_diagnostics_fkey = $store_controller->addTillDiagnostics($from, $to, $last_login, $last_fullsync, $integration_mode, $curr_version, $available_version, $update_skip_count, $last_update_time, $avg_mem_usage, $peak_mem_usage, $avg_cpu_usage, $peak_cpu_usage);

            $bulk_upload = $till_diagnostics['bulk_upload'];
            $status_bulk_upload = $store_controller->addTillDiagnosticsBulkUpload($bulk_upload, $till_diagnostics_fkey);
            
            if (isset($till_diagnostics['sync_logs']['sync_log'][1])) {
                $sync_logs = $till_diagnostics['sync_logs']['sync_log'];
            } else {
                $sync_logs = $till_diagnostics['sync_logs'];
            }
            $sync_logs_status = $store_controller->addTillDiagnosticSyncLogs($sync_logs, $till_diagnostics_fkey);

            $system_details = $till_diagnostics['system_details'];
            $system_details_status = $store_controller->addTillDiagnosticSystemDetails($system_details, $till_diagnostics_fkey);

            $store_controller->pushToStoreCare($data);
            if (($till_diagnostics_fkey == false) && ($status_bulk_upload == false) && ($sync_logs_status == false) &&
                    ($system_details_status == false)) {
                $api_status_code = "FAIL";
                $api_status = array(
                    "success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
                    "code" => ErrorCodes::$stores['ERR_TILL_DIAG_FULL_FAIL'],
                    "message" => ErrorMessage::$stores['ERR_TILL_DIAG_FULL_FAIL']
                );
                return array("status" => $api_status);
            } else if (($till_diagnostics_fkey == false) || ($status_bulk_upload == false) || ($sync_logs_status == false) || ($system_details_status == false)) {
                $api_status_code = "FAIL";
                $api_status = array(
                    "success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
                    "code" => ErrorCodes::$stores['ERR_TILL_DIAG_PARTIAL_SUC'],
                    "message" => ErrorMessage::$stores['ERR_TILL_DIAG_PARTIAL_SUC']
                );
                return array("status" => $api_status);
            }
        }
        $api_status = array(
            "success" => (ErrorCodes::$api[$api_status_code] == 200) ? true : false,
            "code" => ErrorCodes::$api[$api_status_code],
            "message" => ErrorMessage::$api[$api_status_code]
        );
        return array("status" => $api_status);
    }

    private function login($data, $query_data) {

        //=============================================================================
        //checking for the counter and its mac address
        $counter_id = isset($_COOKIE['counter_id']) ? $_COOKIE['counter_id'] : -1;
        $counter_mac_address = isset($_COOKIE['mac_id']) ? $_COOKIE['mac_id'] : -1;
        $this->logger->debug("Cookies : " . print_r($_COOKIE, true));
        $a = Auth::getInstance();
        if (($counter_mac_address != -1) && (strlen($counter_mac_address) > 0)) {
            if ($this->currentorg->getConfigurationValue(CONF_CLIENT_MAC_ADDRESS_CHECKING_ENABLED, false) && !$a->checkIfMacAddressIsValid($this->currentorg->org_id, $this->currentuser->user_id, $counter_mac_address)) {
                $this->logger->error("MAC ID [$counter_mac_address] didn't match.");
                throw new Exception(ErrorMessage::$api['AUTH_FAIL_MAC_ID_MISMATCH'], ErrorCodes::$api['AUTH_FAIL_MAC_ID_MISMATCH']);
            }
        }
        //=============================================================================

        $api_status_code = "SUCCESS";
        $store = StoreProfile::getById($this->currentuser->user_id);
        $ret = array();
        $ret['username'] = $store->username;
        $ret['first_name'] = $store->first_name;
        $ret['last_name'] = $store->last_name;
        $ret['org_name'] = $store->org->name;
        $ret['user_id'] = $store->user_id;
        $ret['type'] = $store->getType();
        $ret['org_id'] = $store->org->org_id;
        $ret['server_time'] = Util::serializeInto8601(time());
        $ret['store_server_prefix'] = $store->getSSPrefix();

        include_once 'helpers/EntityResolver.php';
        $entityResolver = new EntityResolver($this->currentuser->user_id);
        try {
            $p = $entityResolver->getParent("STORE");
            $ret['store_id'] = $p[0];
            $zoneResolver = new EntityResolver($p[0]);
            $p = $zoneResolver->getParent("ZONE");
            $ret['zone_id'] = $p[0];
        } catch (Exception $e) {
            $ret['store_id'] = NULL;
            $ret['zone_id'] = NULL;
        }

        $cm = new ConfigManager();
        $val = $cm->getKey("CONF_ORG_ASSOCIATE_LOGIN_ENABLED");
        $ret['associate_login_enabled'] = $val ? 'true' : 'false';

        $api_status = array(
            "success" => ErrorCodes::$api[$api_status_code] ==
            ErrorCodes::$api["SUCCESS"] ? "true" : "false",
            "code" => ErrorCodes::$api[$api_status_code],
            "message" => ErrorMessage::$api[$api_status_code]
        );

        return array(
            'status' => $api_status,
            'store' =>
            array('user' => $ret)
        );
    }

    private function tasks($query_params) {
        global $gbl_item_status_codes;
        $api_status_code = "SUCCESS";
        $item_status_code = "ERR_TASK_GET_SUCCESS";

        $all = false;
        $batch_size = 50;

        try {
            //query params with case insensitive keys.
            $query_params_ci = array();
            $assoc_ids = array();
            foreach ($query_params as $key => $value) {
                $query_params_ci[strtolower($key)] = $value;
            }
            $query_params = $query_params_ci;

            if (isset($query_params['all'])) {
                $all = (boolean) $query_params['all'];
            }

            if (isset($query_params['start_date'])) {
                $start_date_timestamp = Util::deserializeFrom8601($query_params['start_date']);
                if (!empty($start_date_timestamp))
                    $start_date = Util::getMysqlDateTime($start_date_timestamp);
            }
            if (isset($query_params['end_date'])) {
                $end_date_timestamp = Util::deserializeFrom8601($query_params['end_date']);
                if (!empty($end_date_timestamp))
                    $end_date = Util::getMysqlDateTime($end_date_timestamp);
            }
            if (isset($query_params['status'])) {

                $status = $query_params['status'];
            }
            if (isset($query_params['count'])) {
                $batch_size = (integer) $query_params['count'];
            }
            if (isset($query_params['customer_id']) && $query_params['customer_id'] > 0) {
                $customer_ids = explode(",", $query_params['customer_id']);
            }

            if (isset($query_params['assoc_id']) && strlen($query_params['assoc_id']) > 0) {
                $assoc_ids = explode(",", $query_params['assoc_id']);
            }

            if (isset($query_params['include_completed']) &&
                    strtolower($query_params['include_completed']) == 'true') {
                $include_completed_tasks = true;
            } else
                $include_completed_tasks = false;

            $tasks = array();

            $taskController = new ApiStoreTaskController();
            $this->logger->debug("CustomerIds1:" . print_r($customer_ids, true));
            $temp_tasks = $taskController->getTasks($all, $assoc_ids, $start_date, $end_date, $batch_size, $customer_ids, $status, $include_completed_tasks);


            $tasks = array();

            if ($temp_tasks && is_array($temp_tasks)) {

                foreach ($temp_tasks as $item) {
                    $tasks[] = array(
                        'id' => $item['id'],
                        'type' => $item['type'],
                        'entry_id' => $item['entry_id'],
                        'associate_id' => $item['associate_id'],
                        'associate_name' => $item['associate_name'],
                        'title' => $item['title'],
                        'body' => $item['body'],
                        'created_on' => $item['created_on'],
                        'customer_id' => $item['customer_id'],
                        'store_id' => $item['store_id'],
                        'updated_by_till' => $item['updated_by_till'],
                        'status' => $item['status'],
                        'valid_days_from_create' => $item['valid_days_from_create'],
                        'description' => $item['description']
                    );
                }
            } else {

                $this->logger->debug("Task Get: No Tasks Found");
                throw new Exception("ERR_NO_TASK_FOUND");
            }

            //unset($temp_tasks);
            if ($tasks && count($tasks) > 0)
                $tasks = array('task' => array_values($tasks));
        } catch (Exception $e) {

            $this->logger->error("TasksResource::get() Exception " . $e->getMessage());
            $api_status_code = "FAIL";
            $item_status_code = $e->getMessage();
            $override_error_message = "";

            if (!isset(ErrorCodes::$stores[$item_status_code])) {
                $this->logger->error("$item_status_code is not defined as Error Code making it more generic");
                $override_error_message = $item_status_code;
                $item_status_code = 'ERR_TASK_GET_FAILURE';
            }
            $item['item_status'] = array(
                "success" => ErrorCodes::$stores[$item_status_code] ==
                ErrorCodes::$stores['ERR_TASK_GET_SUCCESS'] ? true : false,
                "code" => ErrorCodes::$stores[$item_status_code],
                "message" => empty($override_error_message) ?
                        ErrorMessage::$stores[$item_status_code] : $override_error_message
            );
            $gbl_item_status_codes = $item['item_status']['code'];
            array_push($tasks, array("task" => $item));
        }

        $api_status = array(
            "success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api['SUCCESS'] ? true : false,
            "code" => ErrorCodes::$api[$api_status_code],
            "message" => ErrorMessage::$api[$api_status_code]
        );

        return array(
            "status" => $api_status,
            "store" => array(
                "tasks" => $tasks
            )
        );
    }

    /**
     * This API will return store's configuration REST version of loyalty/msterconfiguration API.
     * following is sample xml
      <?xml version="1.0" encoding="UTF-8"?>
      <response>
      <status>
      <success>true</success>
      <code>200</code>
      <message>SUCCESS</message>
      </status>
      <store>
      <configurations>
      <last_modified_time>
      <configurations>2013-09-02T08:54:15+05:30</configurations>
      <custom_fields>2013-08-14T14:26:08+05:30</custom_fields>
      <!-- some more tags -->
      </last_modified_time>
      <data_providers_file>-1</data_providers_file>
      <client_log_config_file>-1</client_log_config_file>
      <printer_templates>
      <dvs_coupon>-1</dvs_coupon>
      <transaction>-1</transaction>
      <!-- some more tags -->
      </printer_templates>
      <rule_packages>
      <dvs_issue>
      <version>20</version>
      <file_id>3040</file_id>
      </dvs_issue>
      <dvs_redeem>...</dvs_redeem>
      </rule_packages>
      <inventory_version>0</inventory_version>
      <integration_output_templates>
      <points_redemption>-1</points_redemption>
      <coupon_redemption>-1</coupon_redemption>
      <coupon_issue>-1</coupon_issue>
      <!-- some more tags -->
      </integration_output_templates>
      <integration_post_output_templates>
      <points_redemption>
      <file_id>-1</file_id>
      <file_name>NotPresent</file_name>
      <client_file_monitoring_type>FILE_CHECK</client_file_monitoring_type>
      </points_redemption>
      <coupon_redemption>...</coupon_redemption>
      <coupon_issue>...</coupon_issue>
      <customer_register>...</customer_register>
      <!-- some more tags -->
      </integration_post_output_templates>
      <customer_attributes_version>1</customer_attributes_version>
      <store_server_prefix />
      <time_zone_offset>UTC+05:30</time_zone_offset>
      <store_tasks_max_entries_id>-1</store_tasks_max_entries_id>
      <client_debug_level>0</client_debug_level>
      <client_test_mode>0</client_test_mode>
      <client_upload_logs />
      </configurations>
      </store>
      </response>
     * @param unknown_type $query_params
     */
    private function configurations($query_params) {
        //use file controller
        include_once 'apiController/ApiFileController.php';

        $store_id = $this->currentuser->user_id;

        $output = array();

        //*** start Last Modified Time (for config, custom_fields, countries,
        //		store_attributes, cron_entries, store_tasks, store_task_entries, purchased_feature etc )
        $this->logger->debug("going to fetch last_modified_time for config, custom_fields etc.");
        $output['last_modified_time'] = array();
        //Configuration last updated
        $configurations_time = $this->currentorg->getLastUpdatedTime();
        $output['last_modified_time']['configurations'] = $configurations_time ? Util::serializeInto8601($configurations_time) : '';
        //get the last updated time for custom fields
        $cf_mgr = new CustomFields();
        $custom_fields_modified_time = $cf_mgr->getCustomFieldsLastModified($this->currentorg->org_id);
        $output['last_modified_time']['custom_fields'] = $custom_fields_modified_time ? Util::serializeInto8601($custom_fields_modified_time) : '';

        $organizationController = new ApiOrganizationController();
        $storeController = new ApiStoreController();
        //Countries modified timestamp
        $countries_modified_time = $organizationController->getCountriesLastModifiedDate();
        $output['last_modified_time']['countries'] = $countries_modified_time ? Util::serializeInto8601($countries_modified_time) : '';

        //Store Attributes last modified timestamp
        $store_attributes_modified_time = $storeController->getStoreAttributeLastModifiedDate();
        $output['last_modified_time']['store_attributes'] = $store_attributes_modified_time ? Util::serializeInto8601($store_attributes_modified_time) : '';

        //cron last modified date
        //Set the last modified date
        $clientCronMgr = new ClientCronMgr();
        $cron_entries_modified_time = $clientCronMgr->getLastModifiedCronEntryDate();
        $output['last_modified_time']['cron_entries'] = $cron_entries_modified_time ? Util::serializeInto8601($cron_entries_modified_time) : '';

        //Store Tasks Last Modified
        $storeTasksMgr = new StoreTasksMgr();
        $store_tasks_modified_time = $storeTasksMgr->getStoreTasksLastModified();
        $output['last_modified_time']['store_tasks'] = $store_tasks_modified_time ? Util::serializeInto8601($store_tasks_modified_time) : '';

        //Task Entries Last Modified
        $store_tasks_entries_modified_time = $storeTasksMgr->getStoreTaskEntriesLastModifiedDate($store_id);
        $output['last_modified_time']['store_tasks_entries'] = $store_tasks_entries_modified_time ? Util::serializeInto8601($store_tasks_entries_modified_time) : '';

        //Last modified for the purchased features
        $purchMgr = new PurchasableMgr();
        $purchased_features__modified_time = $purchMgr->getPurchasedFeaturesLastModified();
        $output['last_modified_time']['purchased_features'] = $purchased_features__modified_time ? Util::serializeInto8601($purchased_features__modified_time) : '';
        //*** end of last modified time

        $FileController = new ApiFileController();
        //data providers
        $output['data_providers_file'] = Util::valueOrDefault($FileController->getDataProviderFileId($this->currentuser->user_id), -1);

        //client log config
        $output['client_log_config_file'] = Util::valueOrDefault($FileController->getClientLogConfigFileIdForStore($this->currentuser->user_id), -1);

        $this->logger->debug("Fetching printer template info");
        //printer template
        $printer_templates = array(
            array('tag' => 'dvs_coupon', 'type' => 'dvs_voucher'),
            array('tag' => 'transaction', 'type' => 'bill'),
            array('tag' => 'customer', 'type' => 'customer'),
            array('tag' => 'campaign_coupon', 'type' => 'campaign_voucher'),
            array('tag' => 'points_redemption', 'type' => 'points_redemption'),
            array('tag' => 'customer_search', 'type' => 'customer_search'),
            array('tag' => 'wallet_payment', 'type' => 'wallet_payment'),
            array('tag' => 'gift_card_recharge', 'type' => 'gift_card_recharge'),
            array('tag' => 'gift_card_redemption', 'type' => 'gift_card_redemption')
        );
        $output['printer_templates'] = array();
        foreach ($printer_templates as $row) {

            $tag = $row['tag'];
            $type = $row['type'];
            $output['printer_templates'][$tag] = Util::valueOrDefault($FileController->getPrinterTemplateFileId($this->currentuser->user_id, $type), -1);
        }

        //rules_file
        $rule_packages = array(
            array('tag' => 'dvs_issue', 'type' => STORED_FILE_TAG_ISSUE_RULES_PACKAGE),
            array('tag' => 'dvs_redeem', 'type' => STORED_FILE_TAG_REDEEM_RULES_PACKAGE),
        );

        $this->logger->debug("fetching rule info");
        $am = new AdministrationModule();
        $output['rule_packages'] = array();
        foreach ($rule_packages as $row) {

            $tag = $row['tag'];
            $type = $row['type'];

            list($latest_version, $latest_file_id) = $am->getLatestRuleInfo($type);

            $file_version = $latest_version;

            $output['rule_packages'][$tag]['version'] = Util::valueOrDefault($file_version, -1);
            $output['rule_packages'][$tag]['file_id'] = Util::valueOrDefault($latest_file_id, -1);
        }



        //Inventory Version
        $output['inventory_version'] = $this->currentorg->getConfigurationValue(CONF_INVENTORY_VERSION, 0);

        $this->logger->debug("Fetching integration templates info");
        $output['integration_output_templates'] = array();
        //integration output template
        $integration_templates = $FileController->getIntegrationOutputTemplateTypes();
        $integration_output_templates_new_tags = array(
            'voucher_redemption' => 'coupon_redemption',
            'voucher_issue' => 'coupon_issue',
            'bill_submit' => 'transaction_submit'
        );
        foreach ($integration_templates as $itype) {

            /*
             * 'type' => STORED_FILE_TAG_INTEGRATION_OUTPUT_POINTS_REDEMPTION,
             * 'key' => CONF_CLIENT_INTEGRATION_OUTPUT__POINTS_REDEMPTION_ENABLED
             * */
            $type = STORED_FILE_TAG_INTEGRATION_OUTPUT_TEMPLATE . '_' . strtolower($itype);

            $itype_to_upper = strtoupper($itype);
            $key = 'CONF_CLIENT_INTEGRATION_OUTPUT_' . $itype_to_upper . '_ENABLED';

            $tag = isset($integration_output_templates_new_tags[$itype]) ?
                    $integration_output_templates_new_tags[$itype] : $itype;

            if ($this->currentorg->getConfigurationValue($key, false))
                $output['integration_output_templates'][$tag] = Util::valueOrDefault($FileController->getIntegrationOutputTemplateFileId($this->currentuser->user_id, $type), -1);
            else
                $output['integration_output_templates'][$tag] = -1;
        }

        $this->logger->debug("Fetching integration post output template info");
        $output['integration_post_output_templates'] = array();
        //used to replace tag name like from voucher to coupon
        $integration_output_templates_new_tags = array(
                    'voucher_redemption' => 'coupon_redemption',
                    'voucher_issue' => 'coupon_issue',
                    'bill_submit' => 'transaction_submit',
        );
        //integration post output template
        $integration_post_files = $FileController->getIntegrationPostOutputTypes();
        foreach ($integration_post_files as $itype) {

            /*
             * 'type' => STORED_FILE_TAG_INTEGRATION_POST_OUTPUT_POINTS_REDEMPTION,
             * 'key' => CONF_CLIENT_INTEGRATION_POST_OUTPUT_POINTS_REDEMPTION_ENABLED
             * */

            $type = STORED_FILE_TAG_INTEGRATION_POST_OUTPUT . "_" . $itype;
            $tag = isset($integration_output_templates_new_tags[$itype]) ?
                    $integration_output_templates_new_tags[$itype] : $itype;
            $key = 'CONF_CLIENT_INTEGRATION_POST_OUTPUT_' . strtoupper($itype) . '_ENABLED';

            $file_ids_hash = array();
            if ($this->currentorg->getConfigurationValue($key, false)) {

                $file_ids_hash = $FileController->getPostIntegrationOutputFileIds($this->currentuser->user_id, $type, 'client_file_name');
            }

            if (count($file_ids_hash) == 0)
                $file_ids_hash = array('-1' => array('filename' => 'NotPresent', 'client_file_monitoring_type' => 'FILE_CHECK'));

            $output_data = array();
            foreach ($file_ids_hash as $file_id => $file_info) {

                $file_name = $file_info['filename'];
                $client_file_monitoring_type = $file_info['client_file_monitoring_type'];

                array_push($output_data, array(
                    'file_id' => $file_id,
                    'file_name' => $file_name,
                    'client_file_monitoring_type' => $client_file_monitoring_type
                        )
                );
            }

            $output['integration_post_output_templates'][$tag] = $output_data;
        }

        //customer attributes
        $output['customer_attributes_version'] = $this->currentorg->getConfigurationValue(CONF_CUSTOMER_ATTRIBUTES_VERSION, 1);

        //Store Server Prefix
        $output['store_server_prefix'] = $this->currentuser->getSSPrefix();

        //Time Zone the Store should use
        $output['time_zone_offset'] = $this->currentuser->getStoreTimeZoneOffset();

        //Task Entries Max Id
        $output['store_tasks_max_entries_id'] = $storeTasksMgr->getStoreTaskEntriesMaxId($store_id);

        //Client Test Mode and Debug Level Configuration
        $entity_id = $this->currentuser->user_id;
        $cm = new ConfigManager();
        $output['client_debug_level'] = $cm->getKey('CONF_CLIENT_DEBUG_LEVEL');
        $output['client_test_mode'] = $cm->getKey('CONF_CLIENT_TEST_MODE');
        $output['client_upload_logs'] = $cm->getKey('CONF_CLIENT_UPLOAD_LOGS');
        if ($this->currentuser->getType() == 'STR_SERVER')
            $output['ss_diag_status'] = is_null($cm->getKeyForEntity('CONF_CLIENT_STORE_SERVER_DIAG_STATUS', $this->currentuser->user_id)) ?
                    false : (bool) $cm->getKeyForEntity('CONF_CLIENT_STORE_SERVER_DIAG_STATUS', $this->currentuser->user_id);


        $api_status_key = "SUCCESS";
        $result = array(
            "status" =>
            array(
                "success" => $api_status_key == 'SUCCESS',
                "code" => ErrorCodes::$api[$api_status_key],
                "message" => ErrorMessage::$api[$api_status_key]
            ),
            "store" => array(
                "configurations" => $output
            )
        );
        return $result;
    }

    /**
     * Used to download files of data provider, rulepackage,
     * 		printer template, integration output template,
     *
     * @param unknown_type $query_params
     */
    private function files($query_params) {
        //Possible Values: 'data_provider', 'rule_package',
        //'printer_template','integration_output_template'
        $type = strtolower($query_params['type']);
        $id = $query_params['id'];
        $rule_package_type = strtolower($query_params['rule_package_type']);
        $template_type = strtolower($query_params['template_type']);
        //TODO: throw Error if none of the above inputs are passed
        $fileController = new ApiFileController();
        switch ($type) {
            case 'data_provider':
                $this->logger->debug("Downloading DataProvider File");
                $file_id = $fileController->getDataProviderFileId($this->currentuser->user_id);
                break;
            case 'rule_package':
                $this->logger->debug("Downloading RulePackage with package type of
                                '$rule_package_type'");
                if ($rule_package_type == "dvs_issue") {
                    $tag = STORED_FILE_TAG_ISSUE_RULES_PACKAGE;
                } else if ($rule_package_type == "dvs_redeem") {
                    $tag = STORED_FILE_TAG_REDEEM_RULES_PACKAGE;
                } else {
                    $this->logger->error("Rule Package Type is invalid");
                    throw new Exception(ErrorMessage::$api["INVALID_INPUT"] . ", Rule Package Type is Invalid", ErrorCodes::$api["INVALID_INPUT"]);
                }
                //TODO: need to check if we can use helper/Rules.php or not
                include_once 'helper/Rules.php';
                $rules = new Rules($this->currentorg->org_id);
                list($version, $file_id) = $rules->getLatestRuleInfo($tag);
                break;
            case 'printer_template':
                //TODO: need to add ristriction for template_type
                $file_id = $fileController->getPrinterTemplateFileId(
                        $this->currentuser->user_id, $template_type);
                break;
            case 'integration_output_template':
                //TODO: need to add ristriction for template_type
                $file_id = $fileController->getIntegrationOutputTemplateFileId(
                        $this->currentuser->user_id, $template_type);
                break;
            default:
                $this->logger->debug("no type is passed, downloading file by given id: $id");
                if (empty($id) || $id <= 0) {
                    $this->logger->error("Given Id is not valid, throwing Error");
                    throw new Exception(ErrorMessage::$api["INVALID_INPUT"] . ", id is not passed or its invalid", ErrorCodes::$api["INVALID_INPUT"]);
                }
                $file_id = $id;
                break;
        }
        $fileController->dowloadFile($file_id);
    }

    /**
     *
     * @param unknown_type $query_params
     */
    private function reports($data, $query_params, $http_method) {
        if (strtolower($http_method) == 'get') {
            $result = $this->getReports($query_params);
        } else
            $result = $this->addReports($data);
        return $result;
    }

    //TODO: need to make this rich in order to fetch report for given date
    private function getReports($query_params) {
        $api_status_key = "SUCCESS";
        $loyalty_report = array();
        $redemption_report = array();
        $storeController = new ApiStoreController();
        $types = explode(",", $query_params['type']);
        //if no type is passed then getting all reports
        if (empty($query_params['type'])) {
            $this->logger->debug("Type is Empty: getting report for all types");
            $types = array('LOYALTY', 'REDEMPTION');
        }

        $start_date = $query_params['start_date'];
        $end_date = $query_params['end_date'];
        
        if (!empty($start_date) && !empty($end_date)) {
            $start_timestamp = Util::deserializeFrom8601($start_date);
            $end_timestamp = Util::deserializeFrom8601($end_date);
            if ($start_timestamp > $end_timestamp) {
                throw new Exception(
                ErrorMessage::$api['INVALID_INPUT'] . ", start_date should be less than end_date", ErrorCodes::$api['INVALID_INPUT']
                );
            }
            $start_date = Util::getMysqlDateTime($start_timestamp);
            $end_date = Util::getMysqlDateTime($end_timestamp);
        }

        $loyalty_report = null;
        $redemption_report = null;
        $reports = array();
        foreach ($types as $type) {
            if (strtoupper($type) == 'LOYALTY' && $loyalty_report === null) {
                if (!empty($start_date) && !empty($end_date)) {
                    $loyalty_report = $storeController->getLoyaltyReportForStoreByDate($start_date, $end_date);
                } else {
                    $loyalty_report = $storeController->getLoyaltyReportForStore();
                }
                $reports['loyalty'] = array("row" => $loyalty_report);
            }
            if (strtoupper($type) == 'REDEMPTION' && $redemption_report === null) {
                if(false){
                    if (!empty($start_date) && !empty($end_date)) {
                        $redemption_report_results = $storeController->getRedemptionReportForStore(
                                $start_date, $end_date);
                    } else {
                        $redemption_report_results = $storeController->getRedemptionReportForStore();
                    }
                }
                
                $redemption_report = array();
                foreach ($redemption_report_results as $report) {
                    //TODO: store_name is not being fetched from sql query
                    $redemption_report[] = array(
                        "store_username" => $report['store_username'],
                        "customer" => array(
                            "firstname" => $report['firstname'],
                            "lastname" => $report['lastname'],
                            "mobile" => $report['mobile']),
                        "transaction_number" => $report['transaction_number'],
                        "points_redeemed" => $report['points_redeemed'],
                        "redemption_date" => $report['redemption_date']
                    );
                }
                $reports['redemption'] = array("row" => $redemption_report);
            }
        }

        $api_status = array(
            "success" => ErrorCodes::$api[$api_status_key] == ErrorCodes::$api['SUCCESS'],
            "code" => ErrorCodes::$api[$api_status_key],
            "message" => ErrorMessage::$api[$api_status_key]
        );

        return array(
            "status" => $api_status,
            "report" => $reports
        );
    }

    //Haven't implimented store_code yet
    private function addReports($data) {
        $api_status = "SUCCESS";
        $reports = $data['root']['report'];
        global $error_count, $gbl_item_count, $gbl_item_status_codes;
        $gbl_item_count = count($reports);
        $arr_item_status_codes = array();

        $reports_result = array();
        foreach ($reports as $report) {
            $item_status = 'ERR_STORE_REPORT_ADD_SUCCESS';
            if (!isset($report['type'])) {
                $this->logger->debug("Report type is not passed taking default as 'loyalty'");
                $report_type = 'loyalty';
            } else
                $report_type = $report['type'];
            $report_type = strtolower($report_type);
            try {
                $storeController = new ApiStoreController();
                switch ($report_type) {
                    case 'loyalty':
                        $id = $storeController->saveLoyaltyTrackerReportForStore($report);
                        if ($id <= 0) {
                            $this->logger->error("Insert id is: $id, throwing failure for report addition");
                            throw new Exception("ERR_STORE_REPORT_ADD_FAIL");
                        }
                        break;
                    default:
                        $this->logger->error("Report type is invalid, throwing error");
                        throw new Exception('ERR_STORE_INVALID_REPORT_TYPE');
                }
                $arr_item_status_codes[] = ErrorCodes::$stores[$item_status];
            } catch (Exception $e) {
                $this->logger->error("Error while adding report: " . $e->getMessage());
                $item_status = $e->getMessage();
                $arr_item_status_codes[] = ErrorCodes::$stores[$item_status];
                $error_count++;
            }
            $result = array(
                'type' => $report_type,
                'date' => $report['date'],
                'item_status' => array(
                    "success" => ErrorCodes::$stores[$item_status] ==
                    ErrorCodes::$stores['ERR_STORE_REPORT_ADD_SUCCESS'] ? 'true' : 'false',
                    "code" => ErrorCodes::$stores[$item_status],
                    "message" => ErrorMessage::$stores[$item_status]
                )
            );
            $reports_result [] = $result;
        }
        $gbl_item_status_codes = implode(",", $arr_item_status_codes);
        $api_status = ( $error_count == $gbl_item_count ) ?
                'FAIL' : ( ( $error_count == 0 ) ? 'SUCCESS' : 'PARTIAL_SUCCESS' );
        $api_success = ( $error_count == $gbl_item_count ) ? 'false' : 'true';

        $result = array(
            'status' => array('success' => $api_success,
                'code' => ErrorCodes::$api[$api_status],
                'message' => ErrorMessage::$api[$api_status]),
            'store' => array('report' => $reports_result)
        );
        return $result;
    }

    /**
     * Checks if the system supports the version passed as input
     *
     * @param $version
     */
    public function checkVersion($version) {
        if (in_array(strtolower($version), array('v1', 'v1.1'))) {
            return true;
        }
        return false;
    }

    public function checkMethod($method) {
        if (in_array(strtolower($method), array('get', 'feedback', 'get_feedback_info',
                    'staff', 'login', 'logs', 'diagnostics', 'tasks', 'configurations',
                    'files', 'reports'))) {
            return true;
        }
        return false;
    }

}
