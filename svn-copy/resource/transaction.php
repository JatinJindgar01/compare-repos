<?php

require_once "resource.php";
require_once 'apiController/ApiTransactionController.php';
require_once 'models/currencyratio/SupportedCurrency.php';
require_once 'models/OrderDeliverStatuses.php';
include_once("apiController/ApiStoreTillController.php");
/**
 * Handles all Transaction related api calls.
 * @author pigol
 */

/**
 * @SWG\Resource(
 *     apiVersion="1.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/transaction",
 *     basePath="http://{{INTOUCH_ENDPOINT}}/v1.1"
 * )
 */
class TransactionResource extends BaseResource{
	
	private $config_mgr;
	private $listener_mgr;
	private $transactionController;
    private $base_currency;
    private $transaction_currency;

	function __construct()
	{
		parent::__construct();
		$this->config_mgr = new ConfigManager($this->currentorg->org_id);
		$this->listener_mgr = new ListenersMgr($this->currentorg);
		$this->transactionController=new ApiTransactionController();
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
				case 'add' :
                    #throw new Exception(ErrorMessage::$api["FAIL"] . ", " . " ratelimit ", ErrorCodes::$api["FAIL"]);
					$result = $this->add($version, $data, $query_params);
					break;

                case 'add_with_local_currency' :
                    #throw new Exception(ErrorMessage::$api["FAIL"] . ", " . " ratelimit ", ErrorCodes::$api["FAIL"]);
                    $result = $this->add_with_local_currency($version, $data, $query_params);
                    break;

				case 'get' :
					
					$result = $this->get($version, $query_params);
					break;

                case 'redemptions' :
                    $result = $this->redemptions($data, $query_params, $http_method);
                    break;
                    
                case 'update' :
                    throw new Exception(ErrorMessage::$api["FAIL"] . ", " . " ratelimit ", ErrorCodes::$api["FAIL"]);
                	$result = $this->update($version, $data, $query_params);
                	break;

				default :
					$this->logger->error("Should not be reaching here");
					
			}
		}catch(Exception $e){ //We will be catching a hell lot of exceptions as this stage
			$this->logger->error("Caught an unexpected exception, Code:" . $e->getCode() 
								 . " Message: " . $e->getMessage()
								);
			throw $e;	
		}	
			
		return $result;
	}

    /**
     * Does redemption if $http_method(HTTP Method) is POST,
     * or redemption details if $http_method(HTTP Method) is GET
     * @param array $data - Post Data
     * @param array $query_params - Get Data (Query Parameters)
     * @param unknown_type $http_method - HTTP Method (GET, POST)
     */
	
	/**
	 * @SWG\Api(
	 * path="/transaction/redemptions.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get coupon and points redeemed in this transaction (transaction_number as query param)",
	 *     nickname = "Get Transaction redemptions",
	 *    @SWG\Parameter(name = "identifier_type",type = "string",paramType = "query", description = "Customer identifier type (email, mobile, id, external_id)" ),
	 *    @SWG\Parameter(name = "identifier_name",type = "string",paramType = "query", description = "Value of the customer identifier" ),
	 *    @SWG\Parameter(name = "transaction_number",type = "string",paramType = "query", description = "List of comma-separated transaction numbers" )
	 *    )
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "Coupon",
	 * @SWG\Property( name = "code", type = "float" ),
	 * @SWG\Property( name = "validation_code", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Points",
	 * @SWG\Property( name = "redeem", type = "RedeemArr" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "RedeemArr",
	 * @SWG\Property( name = "points_redeemed", type = "float" ),
	 * @SWG\Property( name = "notes", type = "string" ),
	 * @SWG\Property( name = "validation_code", type = "string" ),
	 * @SWG\Property( name = "redemption_time", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CustomerDetails",
	 * @SWG\Property( name = "mobile", type = "string", description = "Mobile of the customer" ),
	 * @SWG\Property( name = "email", type = "string", description = "Customer email id" ),
	 * @SWG\Property( name = "external_id", type = "string", description = "External_id of the customer" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Redemptions",
	 * @SWG\Property( name = "transaction_number", type = "string" ),
	 * @SWG\Property( name = "customer", type = "CustomerDetails", description = "Customer identifiers" ),
	 * @SWG\Property( name = "points", type = "Points" ),
	 * @SWG\Property( name = "coupons", type = "array", items = "$ref:Coupon" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "RedemptionRoot",
	 * @SWG\Property( name = "root", type = "Redemptions" )
	 * )
	 */
	/**
     * @SWG\Api(
     * path="/transaction/redemptions.{format}",
     * @SWG\Operation(
     *     method="POST", summary="Requests coupon and points redemption",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="RedemptionRoot")
     * ))
     */
    private function redemptions( $data,  $query_params, $http_method)
    {
        $result = NULL;


        $http_method = strtolower($http_method);

        //gets redemption details if the HTTP method is GET or get
        if($http_method == "get")
            $result = $this->getRedemptions($query_params);

        //does coupon/point redemption if the HTTP method is POST or post
        else if ($http_method == "post")
            $result = $this->doRedemptions($data);

        return $result;
    }

    private function getRedemptions($query_params){
        $this->logger->debug("Txn numbers to be fetched : ".$query_params['id']);

        $txn_nos = explode(",", $query_params['transaction_number']);

        $customer = $query_params;
        if (isset($txn_nos)) {
            if (isset($customer['mobile']))
                $identifier = 'mobile';
            elseif (isset($customer['external_id']))
                $identifier = 'external_id';
            elseif (isset($customer['email']))
                $identifier = 'email';
            else if (isset($customer['id'])) {
                $identifier = 'id';
            }
            else {
                throw new Exception( ErrorMessage::$api['INVALID_INPUT'] .", ". ErrorMessage::$transaction['ERR_NO_IDENTIFIER'], ErrorCodes::$api['INVALID_INPUT']);
            }
        }
        else
        {
            throw new Exception( ErrorMessage::$api['INVALID_INPUT'] .", ". ErrorMessage::$transaction['ERR_NO_TRANSACTION_ID'], ErrorCodes::$api['INVALID_INPUT']);
        }


        $transaction_controller = $this->transactionController;
        try {
        	$new_identifier = ($identifier == 'id')? 'user_id' : $identifier;
        	Util::saveLogForApiInputDetails( array( $new_identifier => $customer[$identifier] ) );
            $redeemed_coupons = $transaction_controller->getRedeemedCouponsForTransactionsByBillNos($identifier, $customer[$identifier], $txn_nos);
            $this->logger->debug('getRedemptions : redeemed_coupons : '.print_r($redeemed_coupons, true));
            $points_redeemed = $transaction_controller->getRedeemedPointsForBillNos($identifier, $customer[$identifier], $txn_nos);
            $this->logger->debug('getRedemptions : points_redeemed : '.print_r($points_redeemed , true));
        } catch (Exception $te) {
            $status_code = $te->getMessage();
            if ($status_code == 'ERR_USER_NOT_REGISTERED'){
                $result['status'] = array(
                    'success' => 'false',
                    'code' => ErrorCodes::getTransactionErrorCode('ERR_USER_NOT_FOUND'),
                    'message' => ErrorMessage::getTransactionErrorMessage('ERR_USER_NOT_FOUND'),
                );
                $result['redemptions'] = array('customer' => array('mobile' => $customer['mobile'],
                                               'email' => $customer['email'], 'external_id' => $customer['external_id']));
                return $result;
            }
        }

        //set result status as success in case both coupons AND points are found for all txn id's passed else partial failure
        if ((count($redeemed_coupons) == count($txn_nos)) && (count($points_redeemed) == count($txn_nos)))
            $result['status'] = array(
                'success' => 'true',
                'code' => ErrorCodes::$api['SUCCESS'],
                'message' => ErrorMessage::$api['SUCCESS'],
            );
        else if((count($redeemed_coupons) == 0) && (count($points_redeemed) == 0))
            $result['status'] = array(
                'success' => 'false',
                'code' => ErrorCodes::$api['FAIL'],
                'message' => ErrorMessage::$api['FAIL'],
            );
        else
            $result['status'] = array(
                'success' => 'true',
                'code' => ErrorCodes::$api['PARTIAL_SUCCESS'],
                'message' => ErrorMessage::$api['PARTIAL_SUCCESS']
            );
        $transactions = array();
        foreach($txn_nos as $txn_no){
            //item status is successful in case either coupon or points are redeemed
            if(isset($points_redeemed[strtolower($txn_no)]) || isset($redeemed_coupons[strtolower($txn_no)])){
                $item_status = array(
                        'success' => 'true',
                        'code' => ErrorCodes::getTransactionErrorCode( 'ERR_GET_SUCCESS' ) ,
                        'message' => ErrorMessage::getTransactionErrorMessage( 'ERR_GET_SUCCESS' )
                    );
            } else {
                $item_status = array(
                    'success' => 'false',
                    'code' => ErrorCodes::getTransactionErrorCode( 'ERR_NO_COUPON_POINTS' ) ,
                    'message' => ErrorMessage::getTransactionErrorMessage( 'ERR_NO_COUPON_POINTS' )
                );
            }

            /* No coupon code in the response of this API
            foreach ($redeemed_coupons as $rCoupon) {
                $rCoupon['code'] = ApiUtil::trimCouponCode($rCoupon['code']);
            }*/
            $transaction = array(
                'bill_no' => $txn_no,
                'item_status' => $item_status,
                'coupons' => array('coupon' => $redeemed_coupons[strtolower($txn_no)]),
                'points' => array(
                    'redeemed' => $points_redeemed[strtolower($txn_no)]
                ));
            array_push($transactions, $transaction);
        }
        $result['redemptions']['customer'] = array('mobile' => $customer['mobile'], 'email' => $customer['email'], 'external_id' => $customer['external_id']);
        $result['redemptions']['redemption'] = $transactions;
        return $result;
    }

    private function doRedemptions($data){
    	
        $result=array();
        $result['status'] = array();

        $customer = $data['root']['redemptions'][0]['customer'];
        $bill_no = $data['root']['redemptions'][0]['transaction_number'];

        if (isset($data['root']['redemptions'][0]['points']['redeem'])) {
            $points_info = array();
            $points_info['mobile'] = $customer['mobile'];
            $points_info['email'] = $customer['email'];
            $points_info['external_id'] = $customer['external_id'];
            $points_info['points'] = $data['root']['redemptions'][0]['points']['redeem']['points_redeemed'];
            $points_info['validation_code'] = $data['root']['redemptions'][0]['points']['redeem']['validation_code'];
        }

        if( !isset( $data['root']['redemptions'] ) || 
        		( !isset( $data['root']['redemptions'][0]['points'] )
        				&& !isset( $data['root']['redemptions'][0]['coupons'] ) ) ){
        	
        	$result['status'] = array(
                    'success' => 'false',
                    'code' => ErrorCodes::$api['INVALID_INPUT'],
                    'message' => ErrorMessage::$api['INVALID_INPUT']
                );
        	return $result; 
        }
        
        global $gbl_country_code;
        if(isset($customer['country_code']))
        {
        	$gbl_country_code = $customer['country_code'];
        }
        else
        {
        	$gbl_country_code = '';
        }
        
        Util::saveLogForApiInputDetails(
        		array(
        				'mobile' => $customer['mobile'],
        				'email' => $customer['email'],
        				'external_id' => $customer['external_id']
        		) );
        if (isset($points_info['points'])) {
            require_once "resource/points.php";
            $points = new PointsResource();
            $this->logger->debug("going for isRedeemable, points_info : " . print_r($points_info, true));
            $points_isredeemable = $points->process('v1.1', 'isredeemable', '', $points_info, '');
            $this->logger->debug("points isRedeemable : " . print_r($points_isredeemable, true));

            //in case of points non redeemable AND redemption behaviour = transactional, return from here itself with success=false
            if (($points_isredeemable['points']['redeemable']['is_redeemable'] == 'false')) {
                $result['status'] = array(
                    'success' => 'false',
                    'code' => ErrorCodes::$api['FAIL'],
                    'message' => ErrorMessage::$api['FAIL']
                );
                $item_status = array(
                    'success' => 'false',
                    'code' => $points_isredeemable['points']['redeemable']['item_status']['code'],
                    'message' => $points_isredeemable['points']['redeemable']['item_status']['message']
                );
                $points_status = $data['root']['redemptions'][0]['points']['redeem'];
                $points_status['item_status'] = $item_status;
                if ($this->config_mgr->getKey('CONF_TRANSACTION_REDEMPTION_TRANSACTIONAL') > 0) {
                    $result['redemptions'] = array('points' => $points_status, 'customer' => $customer);
                    $this->logger->debug("points are not redeemable and behaviour is transactional. Hence returning.");
                    return $result;
                }
            }
        }

        $coupon_info = array();
        $coupon_info['mobile'] = $customer['mobile'];
        $coupon_info['email'] = $customer['email'];
        $coupon_info['external_id'] = $customer['external_id'];
        $coupon_info['coupons'] = $data['root']['redemptions'][0]['coupons'];
        $transaction_controller = $this->transactionController;

        if (isset($coupon_info['coupons']) && is_array($coupon_info['coupons']['coupon'])) {
            $this->logger->debug("Going for areCouponsRedeemable, coupon_info : " . print_r($coupon_info, true));
            $are_coup_redeemable = $transaction_controller->areCouponsRedeemable($coupon_info, $this->config_mgr->getKey('CONF_TRANSACTION_REDEMPTION_TRANSACTIONAL'));
            $this->logger->debug("result for areCouponsRedeemable, are_coup_redeemable : " . print_r($are_coup_redeemable, true));
            $this->logger->debug("Points and coupon redemption behaviour transactional : " . $this->config_mgr->getKey('CONF_TRANSACTION_REDEMPTION_TRANSACTIONAL'));

            if(($are_coup_redeemable['all_redeemable'] == false) && ($this->config_mgr->getKey('CONF_TRANSACTION_REDEMPTION_TRANSACTIONAL') > 0) ){
                $result['status'] = array(
                    'success' => 'false',
                    'code' => ErrorCodes::$api['FAIL'],
                    'message' => ErrorMessage::$api['FAIL']
                );
                $result['redemptions'] = array('coupons' => $are_coup_redeemable['coupons'], 'customer' => $customer);
                $this->logger->debug("all coupons are not redeemable and behaviour is transactional. Hence returning.");
                return $result;
            }
        }

        if ($this->config_mgr->getKey('CONF_TRANSACTION_REDEMPTION_TRANSACTIONAL') > 0) {
            $this->logger->debug("Starting transaction for points+coupon redemption");
            Util::beginTransaction();
        }

        //redeeming points
        if (isset($points_info['points'])) {
            $pr = array();
            $pr['root']['redeem'][0] = $data['root']['redemptions'][0]['points']['redeem'];
            $pr['root']['redeem'][0]['transaction_number'] = $data['root']['redemptions'][0]['transaction_number'];
            $pr['root']['redeem'][0]['customer'] = $data['root']['redemptions'][0]['customer'];
            try {
                $this->logger->debug("Going for points redemption, input pr : " . print_r($pr, true));
                $points_redemption = $points->process('v1', 'redeem', $pr, '', '');
                $this->logger->debug("Result points redemption, output points_redemption : " . print_r($points_redemption, true));
            } catch (Exception $te) {
                if ($this->config_mgr->getKey('CONF_TRANSACTION_REDEMPTION_TRANSACTIONAL') > 0) {
                    $this->logger->debug("rolling back txn");
                    Util::rollbackTransaction();
                    $points_redemption['status']['success'] == 'false';
                }
                $this->logger->error("Exception thrown by points redemption call");
            }
        }

        if (isset($coupon_info['coupons']) && is_array($coupon_info['coupons']['coupon'])) {
            try {
                $this->logger->debug("Going for coupon redemption, input coupons : " . print_r($coupon_info['coupons']['coupon'], true));
                $this->logger->debug("input customer : " . print_r($customer, true));
                $this->logger->debug("input bill_no : " . $bill_no);
                $coupon_redemption = $transaction_controller->redeemMultipleCoupons($coupon_info, $customer, $bill_no);
                $this->logger->debug("Result coupon redemption, output coupon_redemption : " . print_r($coupon_redemption, true));
            } catch (Exception $te) {
                if ($this->config_mgr->getKey('CONF_TRANSACTION_REDEMPTION_TRANSACTIONAL') > 0) {
                    $this->logger->debug("rolling back txn");
                    Util::rollbackTransaction();
                    $coupon_redemption['all_suc'] = false;
                }
                $this->logger->error("Exception thrown by points redemption call");
            }
        }


        if ($this->config_mgr->getKey('CONF_TRANSACTION_REDEMPTION_TRANSACTIONAL') > 0){
            $this->logger->debug('points_redemption[status][success] : '.print_r($points_redemption['status']['success'], true));
            $this->logger->debug('coupon_redemption[all_suc] : '.print_r($coupon_redemption['all_suc'], true));
            if (($points_redemption['status']['success'] == 'true' && $coupon_redemption['all_suc'] == true) ||
                ( !isset($points_info['points']) && $coupon_redemption['all_suc'] == true ) ||
                ( !isset($coupon_info['coupons']) && $points_redemption['status']['success'] == 'true') ||
                ( !isset($coupon_info['coupons']) && !isset($points_info['points']) )) {
                $this->logger->debug("Commiting transaction for points+coupon redemption");
                Util::commitTransaction();
                $result['status'] = array(
                    'success' => 'true',
                    'code' => ErrorCodes::$api['SUCCESS'],
                    'message' => ErrorMessage::$api['SUCCESS']
                );
                $this->logger->debug("All coupons and points redemptions successfull in transactional case");
                $result['redemptions'] = array('coupons' => $coupon_redemption['coupons'],
                                                'points' => $points_redemption['responses']['points'], 'customer' => $customer);
            } else {
                $this->logger->debug("Points and coupon redemption failed");
                $this->logger->debug("Rolling back transaction for points+coupon redemption");
                Util::rollbackTransaction();
                $result['status'] = array(
                    'success' => 'false',
                    'code' => ErrorCodes::$api['FAIL'],
                    'message' => ErrorMessage::$api['FAIL']
                );
                //populate coupon/points level failure. Even if some of coupon status is successful, none is redeemed since behaviour is transactional
                if($points_redemption['status']['success'] == 'false'){
                    $result['redemptions'] = array('points' => $points_redemption['responses']['points'], 'customer' => $customer);
                } else {
                    $result['redemptions'] = array('coupons' => $coupon_redemption['coupons'], 'customer' => $customer);
                }

                $this->logger->debug("All coupons and points redemptions failed in transactional case");
                //send email in case only either one is successful. everything fine at API end but PE may have inconsistent data
                if ($points_redemption['status']['success'] == 'true')
                    Util::sendEmail('api-dev@capillarytech.com', 'points engine call worked for points. coupons redemption failed', '', $this->currentorg->org_id);
                else if ($coupon_redemption['some_suc'] == 'true')
                    Util::sendEmail('api-dev@capillarytech.com', 'points engine call worked for coupons. points redemption failed', '', $this->currentorg->org_id);
            }
        } else {
            if (($points_redemption['status']['success'] == 'true' && $coupon_redemption['all_suc'] == true) ||
                ( !isset($points_info['points']) && $coupon_redemption['all_suc'] == true ) ||
                ( !isset($coupon_info['coupons']) && $points_redemption['status']['success'] == 'true') ||
                ( !isset($coupon_info['coupons']) && !isset($points_info['points']) )
               ) {
                $result['status'] = array(
                    'success' => 'true',
                    'code' => ErrorCodes::$api['SUCCESS'],
                    'message' => ErrorMessage::$api['SUCCESS']
                );

                $this->logger->debug("All coupons and points redemptions successful in non transactional case");

                $result['redemptions'] = array('coupons' => $coupon_redemption['coupons'],
                    'points' => $points_redemption['responses']['points'], 'customer' => $customer);
            }else if ($points_redemption['status']['success'] == 'true' || $coupon_redemption['some_suc'] == true) {
                $result['status'] = array(
                    'success' => 'true',
                    'code' => ErrorCodes::$api['PARTIAL_SUCCESS'],
                    'message' => ErrorMessage::$api['PARTIAL_SUCCESS']
                );
                $this->logger->debug("Some coupons and points redemptions successful in non transactional case");
                $result['redemptions'] = array('coupons' => $coupon_redemption['coupons'],
                    'points' => $points_redemption['responses']['points'], 'customer' => $customer);
            } else {
                $result['status'] = array(
                    'success' => 'false',
                    'code' => ErrorCodes::$api['FAIL'],
                    'message' => ErrorMessage::$api['FAIL']
                );
                $this->logger->debug("All coupons and points redemptions failed in non transactional case");
                $result['redemptions'] = array('coupons' => $coupon_redemption['coupons'],
                    'points' => $points_redemption['responses']['points'], 'customer' => $customer);
            }
        }

        return $result;
    }

    /**
     * Get the local and base currency for a give store
     */
    private function get_currencies()
    {
        $org_model = new OrganizationModelExtension($this->currentorg->org_id);
        $org_model->load($this->currentorg->org_id);
        $this->base_currency = SupportedCurrency::loadById($org_model->getBaseCurrency());

        $storeTillController = new ApiStoreTillController();
        $this->transaction_currency = $storeTillController->getTillCurrencyFromHierarchy($this->currentuser->user_id);
    }

    /**
     * @return bool
     */
    private function isBaseCurrencySameAsTransactionCurrency()
    {
        $this->logger->debug("mc_add: transaction currency: " . print_r($this->transaction_currency, 1));
        $this->logger->debug("mc_add: base currency: " . $this->base_currency);
        $transaction_currency_id = $this->transaction_currency["currency_id"];
        $base_currency_id = $this->base_currency->getSupportedCurrencyId();
        $this->logger->debug("mc_add: transaction currency id: " . $transaction_currency_id);
        $this->logger->debug("mc_add: base currency id: " . $base_currency_id);
        if (strcmp($transaction_currency_id, $base_currency_id) == 0) {
            return true;
        }
        return false;
    }

    private function getTransactionToBaseCurrencyRatio()
    {
        $transaction_currency_id = $this->transaction_currency["currency_id"];
        $base_currency_id = $this->base_currency->getSupportedCurrencyId();
        $this->logger->debug("mc_add: get ratio for transaction currency id: " . $transaction_currency_id);
        $transaction_currency_ratio = CurrencyConversion::loadByCurrencyId($transaction_currency_id)->toArray();
        $this->logger->debug("mc_add: get ratio for base currency id: " . $base_currency_id);
        $base_currency_ratio = CurrencyConversion::loadByCurrencyId($base_currency_id)->toArray();
        $this->logger->debug("mc_add: base currency ratio: " . print_r($base_currency_ratio, 1));
        $this->logger->debug("mc_add: transaction currency ratio: " . print_r($transaction_currency_ratio, 1));
        $ratio = $transaction_currency_ratio["ratio"] / $base_currency_ratio["ratio"];
        $this->logger->debug("mc_add: conversion ratio: " . $ratio);
        return $ratio;
    }

    private function convertValuesToBaseCurrency(&$data, $ratio)
    {
        $transactions = &$data['root']['transaction'];
        $this->logger->debug("mc_add: Converting using ratio: " . $ratio);

        if (empty($transactions)) {
            $this->logger->debug("mc_add: No bills passed");
            throw new InvalidInputException(ErrorMessage::$transaction['ERR_NO_RECORDS'], ErrorCodes::$transaction['ERR_NO_RECORDS']);
        }


        foreach ($transactions as &$transaction) {
            // convert the amount to base currency
            $transaction["amount"] = $transaction["amount"] * $ratio;
            $transaction["discount_value"] = $transaction["discount_value"] * $ratio;
            $transaction["gross_amount"] = $transaction["gross_amount"] * $ratio;
            $transaction["currency_id"] = $this->transaction_currency["currency_id"];
            foreach ($transaction["line_items"]["line_item"] as &$line_item) {
                $line_item["value"] = $line_item["value"] * $ratio;
                $line_item["amount"] = $line_item["amount"] * $ratio;
                $line_item["rate"] = $line_item["rate"] * $ratio;
                $line_item["discount_value"] = $line_item["discount_value"] * $ratio;
            }
            foreach ($transaction["payment_details"]["payment"] as &$payment) {
                $payment["value"] = $payment["value"] * $ratio;
            }
        }
    }

    private function fix_transaction_data_format(&$data) {
        $transactions = &$data['root']['transaction'];
        foreach ($transactions as &$transaction) {
            if (isset($transaction["line_items"]["line_item"]) && !isset($transaction["line_items"]["line_item"][0])) {
                $transaction["line_items"]["line_item"] = array($transaction["line_items"]["line_item"]);
            }
            if (isset($transaction["payment_details"]["payment"]) && !isset($transaction["payment_details"]["payment"][0])) {
                $transaction["payment_details"]["payment"] = array($transaction["payment_details"]["payment"]);
            }
        }
    }

    /**
     * Wrapper endpoint over transaction/add to support multiple currencies.
     *
     * This endpoint accepts all the values in the local currency. It converts them to base currency and calls the add() function
     *
     * @param $version
     * @param $data
     * @param $query_params
     * @return array
     * @see add($version, $data, $query_params)
     * @throws InvalidInputException
     */
     private function add_with_local_currency($version, $data, $query_params)
    {
        $curr_enable_val = $this->config_mgr->getKey('CONF_CURRENCY_CONVERSION_ENABLE');
        if($curr_enable_val === 'true' || $curr_enable_val === true || $curr_enable_val === 1 || $curr_enable_val === '1' )
                {
                $this->fix_transaction_data_format($data);
                $this->get_currencies();
                $this->logger->debug("Starting addition of bills in multi currency add: txn count: " . sizeof($data['root']['transaction']));
                if ($this->isBaseCurrencySameAsTransactionCurrency()) {
                    return $this->add($version, $data, $query_params);
                }
                $ratio = $this->getTransactionToBaseCurrencyRatio();

                $this->logger->debug("multi currency add: ratio: " . sizeof($ratio));
                $this->convertValuesToBaseCurrency($data, $ratio);
                return $this->add($version, $data, $query_params);
                } else{
                        return $this->add($version, $data, $query_params);
                }
    }


    /**
	 * Adds a new transaction(s) in the system
	 *
	 * @param $data
	 * @param $query_params
	 */
	
         /**
         * @SWG\Model(
         * id = "Transaction",
         * @SWG\Property( name = "type",enum ="['REGULAR', 'NOT_INTERESTED', 'RETURN', 'NOT_INTERESTED_RETURN']" , required = false, description = "Type of transaction being added. If not specified, it will go as REGULAR" ),
         * @SWG\Property( name = "return_type",enum ="['AMOUNT', 'FULL', 'LINE_ITEM']" , required = false, description = "Applies for RETURN transaction. AMOUNT - returning a specific amount from transaction, LINE_ITEM - Returning some specific line item, FULL - Returning all the items purchased" ),
         * @SWG\Property( name = "number", type = "string", description = "Transaction number is mandatory for most organizations"),
         * @SWG\Property( name = "amount", type = "float", description = "Transaction amount"),
         * @SWG\Property( name = "gross_amount", type = "float", description = "Gross bill amount before discount"),
         * @SWG\Property( name = "discount", type = "float", description = "Discounts provided by the org"),
         * @SWG\Property( name = "billing_time",type = "string", description = "Time of billing; If not passed, current time is considered as billing time"),
         * @SWG\Property( name = "purchase_time",type = "string", description = "Time of old parchase bill; If not passed, system will not be able to map with old bill"),
		 * @SWG\Property( name = "notes",type = "string", description = "Notes regarding the transaction"),
		 * @SWG\Property(name = "customer", type = "Customer", required = false, description = "Required for REGULAR and RETURN types" ),
		 * @SWG\Property(name = "line_items", type = "array", items =  "$ref:LineItemList"),
		 * @SWG\Property(name = "payment_details", type = "array", items =  "$ref:PaymentList"),
		 * @SWG\Property(name = "custom_fields", type = "array", items =  "$ref:CustomFieldsList"),
         * @SWG\Property(name = "credit_note", type = "CreditNote", description = "Can be used to capture credit note if there is some returns")
         * )
         */
    
    	/**
         * @SWG\Model(
         * id = "LineItemList",
         * @SWG\Property(name = "line_item", type = "LineItem", description = "Captures each and every line items" )
         * )
         * */

    	/**
	     * @SWG\Model(
	     * id = "LineItem",
		 * @SWG\Property( name = "item_code", type = "string", description = "Item sku of the purchased item"),
	     * @SWG\Property( name = "amount", type = "float", description = "Transaction amount"),
		 * @SWG\Property( name = "qty", type = "float", description = "Quantity"),
		 * @SWG\Property( name = "rate", type = "float", description = "Cost per unit item"),
		 * @SWG\Property( name = "value", type = "float", description = "Amount before discount, can be considered as rate*qty"),
		 * @SWG\Property( name = "discount", type = "float", description = "Discount given on the line item"),
		 * @SWG\Property( name = "description", type = "string" ),
		 * @SWG\Property( name = "serial", type = "integer", description = "Sequence number of item in the transaction"),
		 * @SWG\Property( name = "type", enum = "['REGULAR', 'RETURN']", description = "Applies only for regular transaction; Can be used to return some items along with new item purchase - mixed transaction"),
         * @SWG\Property( name = "return_type",enum ="['AMOUNT', 'FULL', 'LINE_ITEM']" , required = false, description = "Applies for RETURN transaction. AMOUNT - returning a specific amount from transaction, LINE_ITEM - Returning some specific line item, FULL - Returning all the items purchased. Only for mixed transaction" ),
		 * @SWG\Property( name = "transaction_number", type = "string", description = "The number of purchase transaction which is getting returned now" ),
         * @SWG\Property( name = "billing_time", type = "string", description = "The time of purchase transaction which is getting returned now" ),
		 * @SWG\Property( name = "attributes", type = "array", items =  "$ref:InventoryAttributeList")
	     * )
	     * */

        /**
         * @SWG\Model(
         * id = "PaymentList",
         * @SWG\Property(name = "payment", type = "PaymentTender", description = "Captures the payment details" )
         * )
         * */
    	     
    	/** @SWG\Model(
	     * id = "PaymentTender",
		 * @SWG\Property( name = "name", type = "string", description = "Payment tender name"),
	     * @SWG\Property( name = "value", type = "float", description = "Amount being paid using the tender"),
	     * @SWG\Property( name = "notes", type = "string"),
	     * @SWG\Property( name = "attributes", type = "array", items =  "$ref:TenderAttributeList")
	     * )
    	*/
    
        	/**
         * @SWG\Model(
         * id = "TenderAttributeList",
         * @SWG\Property(name = "attribute", type = "TenderAttribute", description = "Tender attributes to be saved" )
         * )
         * */

		/**
         * @SWG\Model(
         * id = "TenderAttribute",
		 * @SWG\Property(name = "name", type = "string", required = true ),
		 * @SWG\Property(name = "value", type = "string", required = true )
         * )
         * */

    	/**
         * @SWG\Model(
         * id = "TransactionRequest",
         * @SWG\Property(name = "transaction",type = "array", items = "$ref:Transaction")
         * )
         */

    	/**
         * @SWG\Model(
         * id = "CreditNote",
         * @SWG\Property( name = "number", type = "string" , description = "Credit not number" ),
         * @SWG\Property( name = "notes",type = "string" ),
         * @SWG\Property( name = "amount",type = "string", description = "Amount for which credit note is provided" )
         * )*/

    	/**
         * @SWG\Model(
         * id = "InventoryAttributeList",
         * @SWG\Property(name = "attribute", type = "InventoryAttribute", description = "Inventory attributes to be saved in case of new items" )
         * )
         * */

		/**
         * @SWG\Model(
         * id = "InventoryAttribute",
		 * @SWG\Property(name = "name", type = "string", required = true ),
		 * @SWG\Property(name = "value", type = "string", required = true )
         * )
         * */

        /**
         * @SWG\Model(
         * id = "TransactionRoot",
         * @SWG\Property(name = "root", type = "TransactionRequest" )
         * )
         */
        
        /**
         * @SWG\Api(
         * path="/transaction/add.{format}",
         * @SWG\Operation(
         *     method="POST",
         *     summary="Add bill",
         * 	@SWG\Parameter( name = "request", paramType="body",type="TransactionRoot")
         * )
         * )
         */
	private function add($version, $data, $query_params){

        $transactionSubmitTime = microtime();
        $lastTransactionNumber = '';
        
        $should_return_user_id = $query_params['user_id'] == 'true'?true:false;

        /* Added to some single item in an array*/
        $this->fix_transaction_data_format($data);
        
        $transactions = $data['root']['transaction'];
        $this->logger->debug("Starting addition of bills : txn count: " . sizeof($data['root']['transaction']));

		if(empty($transactions))
		{
			$this->logger->debug("No bills passed");
			throw new InvalidInputException(ErrorMessage::$transaction['ERR_NO_RECORDS'], ErrorCodes::$transaction['ERR_NO_RECORDS']);
		}
		$result=array();
		$result['status'] = array();
		$item = array();

        global $error_count;
        $error_count = 0;
		global $gbl_item_count, $gbl_item_status_codes;
		$gbl_item_count = count($transactions);

        $arr_item_status_codes = array();
        $transaction_count = 0;
		
		foreach ($transactions as $k => $transaction)
		{
			/************************************************/
			try{
				
				Util::resetApiWarnings();
				
				
				//modifying inputs as per version.
				if($version == 'v1.1')
				{
					if(isset($transaction['number']))
						$transaction['transaction_number'] = $transaction['number'];
				}
				++$transaction_count;
				$not_interested_bill = (isset($transaction['type']) && strtolower($transaction['type']) == 'not_interested' );

	           $not_interested_return_bill = (isset($transaction['type']) && strtolower($transaction['type']) == 'not_interested_return' );
                
                if(!$not_interested_bill && !$not_interested_return_bill)
                			{
					$name = $transaction['customer']['name'];
					global $gbl_country_code;
					if(isset($transaction['customer']['country_code']))
					{
						$gbl_country_code = $transaction['customer']['country_code'];
					}
					else
					{
						$gbl_country_code = '';
					}
				}
				
				if(isset($name))
				{
					$arr = StringUtils::strexplode(" ", $name);
					
					if(count($arr) >= 2)
					{ 
						$transaction['customer']['firstname'] =substr($name, 0, strrpos($name, " "));
						$transaction['customer']['lastname'] = $arr[count($arr) -1];
					}
					else 
						$transaction['customer']['firstname'] = $name;
				}

                if(isset($transaction['source']))
                {
                    $reg_source=strtolower($transaction['source']);
                    if($reg_source != "e-comm" && $reg_source !="newsletter")
                        $transaction['source']='instore';
                }
                else{
                    $transaction['source']='instore';
                }

				$transaction_controller = new ApiTransactionController();
				Util::saveLogForApiInputDetails(
						array(
								'mobile' => $transaction['customer']['mobile'],
								'email' => $transaction['customer']['email'],
								'external_id' => $transaction['customer']['external_id'],
								'transaction_number' => $transaction['transaction_number']
						) );

				//check batch limit
				ApiUtil::checkBatchLimit();

                if (isset($transaction['delivery_status']) && !empty($transaction['delivery_status'])) {
                    $orderStatus = strtoupper($transaction['delivery_status']);
                    if (! OrderDeliverStatuses::isValidName($orderStatus)) {
                        throw new Exception('ERR_INVALID_TRANSACTION_STATUS');
                    }
                } 

				if(isset($transaction['type']) && strtolower($transaction['type']) == 'return' )
				{
					if(!isset($transaction['return_type']))
					{
						$transaction['return_type'] = TYPE_RETURN_BILL_FULL;
					}
					$t = $transaction_controller->returnBills($transaction);
				}
				else if( $not_interested_bill )
					$t = $transaction_controller->addNotInterestedBill($transaction);
				else if( $not_interested_return_bill )
                    $t = $transaction_controller->addNotInterestedReturn($transaction);
                else if(isset($transaction['type']) && strtolower(trim($transaction['type'])) == 'regular' )
					$t = $transaction_controller->addBills($transaction);
				else
				{
                    $status_code = 'ERR_INVALID_BILL_TYPE';
                    $transaction_controller->setStatusCode($status_code);
                    throw new Exception($status_code);  
                }
                
                $item = $t->generateResponse();
                $lastTransactionNumber = $item['transaction_number'];
                $transactionId = $item ['transaction_id'];
                Util::saveLogForApiInputDetails(
                        array(
                                'mobile' => $item['customer']['mobile'],
                                'email' => $item['customer']['email'],
                                'external_id' => $item['customer']['external_id'],
                                'transaction_number' => $item['transaction_number'],
                                'transaction_id' => $item['transaction_id'],
                                'user_id' => $item['customer']['user_id']
                        ) );
                                ApiCacheHandler::triggerEvent("transaction_update", $item['customer']['user_id']);
                                ApiUtil::mcUserUpdateCacheClear($item['customer']['user_id']);
                if(!$should_return_user_id)
                    unset($item['customer']['user_id']);
                //unsetting some of the fields that don't need to populate in response xml for return bill
                if(strtolower($transaction['type']) == 'return'&& $version == 'v1')
                {
                    unset($item['transaction_id']);
                }
                if( strtolower($transaction['type']) != 'not_interested'
                        && strtolower($transaction['type']) != 'not_interested_return'
                        && isset($GLOBALS[ 'listener' ]))
                {
                    $item['side_effects'] = array();
                    $item['side_effects']['effect'] = $GLOBALS[ 'listener' ];
                    $GLOBALS[ 'listener' ] = array();
                }
            }catch(Exception $te){
                if($transaction_controller->getNewCustomer())
                {
                    Util::addApiWarning("new customer registered");
                }
                ++$error_count;
                $status_code = $te->getMessage();
                $transaction_controller->setStatusCode($status_code);
                $this->logger->debug("TransactionController: Exception Message- ".$te->getMessage());
                $item = $transaction_controller->generateErrorMessage();
                
                if(!$item["number"])
                    $item["number"] = $transaction["number"];
                //$item = $this->generateErrorMessage($status_code,$user,$transaction);
            }
            
			//validates and construct output tags as per the version number.
			if($version == 'v1')
			{
				if(isset($item['side_effects']['effect']))
				{
					$effects = $item['side_effects']['effect'];
					if(count($effects) > 0)
					{
						$new_effects = array();
						foreach ($effects as $effect)
						{
							$temp_effect = $effect;
							if( $temp_effect['type'] == SIDE_EFFECT_TYPE_SLAB ){
									
								continue;
							}
							if(isset($temp_effect['discount_value']))
								unset($temp_effect['discount_value']);
							$new_effects[] = $temp_effect;
						}
						$item['side_effects']['effect'] = $new_effects;
					}
				}
			}
			else if($version == 'v1.1')
			{
				
				$temp_item = array( 
								"id" => $item['transaction_id'], 
								"number" => $item["transaction_number"]
							);
				if(isset($transaction['bill_client_id']))
					$temp_item['bill_client_id'] = $transaction['bill_client_id'];
				
				unset($item['transaction_number']);
				unset($item['transaction_id']);
				$item = array_merge($temp_item, $item);

				if(isset($item['side_effects']['effect']))
				{
					$effects = $item['side_effects']['effect'];
					if(count($effects) > 0)
					{
						$new_effects = array();
						foreach ($effects as $effect)
						{
							$temp_effect = array();
							if(isset($effect['type']))
							{
								if($effect['type'] == SIDE_EFFECT_TYPE_VOUCHER)
								{
									$temp_effect['type'] = SIDE_EFFECT_RESPONSE_TYPE_COUPON;
									$temp_effect['coupon_type'] = $effect['coupon_type'];
									$temp_effect['coupon_code'] = ApiUtil::trimCouponCode($effect['coupon_code']);
									$temp_effect['description'] = $effect['description'];
									$temp_effect['valid_till'] = $effect['valid_till'];
									$temp_effect['id'] = $effect['coupon_id'];
								}
								else if( $effect['type'] == SIDE_EFFECT_TYPE_DVS)
								{
									$temp_effect['type'] = SIDE_EFFECT_RESPONSE_TYPE_COUPON;
                                    $temp_effect['coupon_type'] = SIDE_EFFECT_RESPONSE_TYPE_DVS;
                                    $temp_effect['coupon_code'] = ApiUtil::trimCouponCode($effect['coupon_code']);
									$temp_effect['discount_code'] = $effect['discount_code'];
									$temp_effect['discount_value'] = $effect['discount_value'];
                                    $temp_effect['description'] = $effect['description'];
                                    $temp_effect['valid_till'] = $effect['valid_till'];
                                    $temp_effect['id'] = $effect['id'];
								}
								else if( $effect['type'] == SIDE_EFFECT_TYPE_POINTS)
								{
									$temp_effect['type'] = SIDE_EFFECT_RESPONSE_TYPE_POINTS;
									$temp_effect['awarded_points'] = $effect['awarded_points'];
									$temp_effect['total_points'] = $effect['total_points'];
								}
								else if( $effect['type'] == SIDE_EFFECT_TYPE_SLAB )
								{
									$temp_effect['type'] = SIDE_EFFECT_RESPONSE_TYPE_SLAB;
									$temp_effect['previous_slab_name'] = $effect['previous_slab_name'];
									$temp_effect['previous_slab_number'] = $effect['previous_slab_number'];
									$temp_effect['upgraded_slab_name'] = $effect['upgraded_slab_name'];
									$temp_effect['upgraded_slab_number'] = $effect['upgraded_slab_number'];
				    				}
							}
							$new_effects[] = $temp_effect;
						}
						$item['side_effects']['effect'] = $new_effects;
					}
				}

			}
			$strWarnings = Util::getApiWarnings();
			if($strWarnings !== null)
			{
				$item['item_status']['message'] .= ", $strWarnings";
			}
			/************************************************/
			//add each bill as necessary
 			if ( ! $item['customer'] 
                && ( strtolower($data['root']['transaction']['type']) != "not_interested" ) 
                && ( strtolower($data['root']['transaction']['type']) != "not_interested_return" ) )
 			{
 				foreach ( $data['root']['transaction'] as $trans )
 				{
 					if ( $item['number'] == $trans['number'] )
 							$item['customer'] = $trans['customer'];
 				}
 			}
			
			$result['transactions']['transaction'][] = $item;
			if(isset($transaction_controller))
				unset($transaction_controller);
			$arr_item_status_codes[] = $item['item_status']['code'];
		}
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array();
		$status = 'SUCCESS';
		if($transaction_count == $error_count){
			$status = 'FAIL';
		}else if(($error_count < $transaction_count) && $error_count > 0){
			$status = 'PARTIAL_SUCCESS';
		} 
		
		$userAgent = trim(strtolower($_SERVER['HTTP_USER_AGENT']));
		$special_user_agents = array(
				"clienteling_net_v5.5.3", "clienteling_net_v5.5.4", "clienteling_net_v5.5.5", 
				"clienteling_net_v5.5.6", "clienteling_net_v5.5.7", "clienteling_net_v5.6.0",
				"clienteling_net_v5.6.1", "clienteling_net_v5.6.2", "clienteling_net_v5.6.3", 
				"clienteling_net_v5.6.4", "clienteling_net_v5.5.6.5",
				"storecenter_net_v1.0.6.2",	"storecenter_net_v1.0.6.3", "storecenter_net_v1.0.6.4",	
				"storecenter_net_v1.0.6.5", "storecenter_net_v1.0.7.0",	"storecenter_net_v1.0.7.1",
				"storecenter_net_v1.0.7.2",	"storecenter_net_v1.0.7.3", "storecenter_net_v1.0.7.4",	
				"storecenter_net_v1.0.7.5", "storecenter_net_v1.0.7.6",
		);
		if(in_array( $userAgent, $special_user_agents)) {
			$api_status['success'] = true;
			
		}
		else 
			$api_status['success'] = ($status == 'SUCCESS') ? 'true' : 'false';
		$api_status['code'] = ErrorCodes::$api[$status];
		$api_status['message'] = ErrorMessage::$api[$status];
		

        // On success, push data to store-care
        if ($api_status['success'] && $lastTransactionNumber) {
            require_once "apiController/ApiStoreController.php";

            list($timestampInMicroSec, $timestampInSec) = explode(' ', $transactionSubmitTime);

            $data['lastTransactionTime'] = "$timestampInSec";
            $data['lastTransactionNumber'] = "$lastTransactionNumber";
            $data['lastTransactionSyncTime'] = "$timestampInSec";

            $storeController = new ApiStoreController();
            $storeController -> pushRecentRequestsAsynchronously($data);
        }

		$result['status'] = $api_status;
		return $result;
		
	}
	
	
	/**
	 * Fetches the details of a transaction
	 *
	 * @param $query_params
	 */
        
        /**
         * @SWG\Api(
         * path="/transaction/get.{format}",
        * @SWG\Operation(
        *     method="GET", summary="Get bill details",
         * @SWG\Parameter(
         * name = "id",
         * type = "integer",
         * required = false,
         * paramType="query"),
         * @SWG\Parameter(
         * name = "number",
         * type = "string",
         * required = false,
         * paramType="query"),
         * @SWG\Parameter(
         * name = "amount",
         * type = "float",
         * required = false,
         * paramType="query"),
         * @SWG\Parameter(
         * name = "store_code",
         * type = "string",
         * required = false,
         * paramType="query"),
         * @SWG\Parameter(
         * name = "till_code",
         * type = "string",
         * required = false,
         * paramType="query"),
         * @SWG\Parameter(
         * name = "type",
         * type = "string",
         * required = false,
         * enum="['REGULAR', 'NOT_INTERESTED', 'RETURN']",
         * paramType="query"),
         * @SWG\Parameter(
         * name = "date",
         * type = "string",
         * required = false,
         * paramType="query"),
         * @SWG\Parameter(
         * name = "start_id",
         * type = "integer",
         * required = false,
         * paramType="query"),
         * @SWG\Parameter(
         * name = "limit",
         * type = "integer",
         * required = false,
         * paramType="query"),
         * @SWG\Parameter(
         * name = "user_id",
         * type = "string",
         * required = false,
         * enum="['true', 'false']",
         * paramType="query"),
         * @SWG\Parameter(
         * name = "credit_notes",
         * type = "string",
         * required = false,
         * enum="['true', 'false']",
         * paramType="query"),
         * @SWG\Parameter(
         * name = "tenders",
         * type = "string",
         * required = false,
         * enum="['true', 'false']",
         * paramType="query")
         * ))
        */
        
	private function get($version, $query_params)
	{
		$should_return_user_id = $query_params['user_id'] == 'true'? true : false;
		$should_return_credit_notes = $query_params['credit_notes'] == 'true'? true: false;
		$should_return_tenders = $query_params['tenders'] == 'true'? true: false;
		$get_tran_timer = new Timer('get_transaction_timer');
		$get_tran_timer->start();
		$transactionController = $this->transactionController;
		
		////START
		global $gbl_item_count, $gbl_item_status_codes;
		$transaction_array = StringUtils::strexplode( ',' , $query_params[ 'id' ] );
		$gbl_item_count = count($transaction_array);
		$arr_item_status_codes = array();

        global $error_count;
        $error_count = 0;
		$transaction_count = 0;

        $response = array();

        if(!isset( $query_params['id']) && (isset($query_params["amount"])
                || isset($query_params["date"]) || isset($query_params["number"])
                || isset($query_params["till_code"]) || isset($query_params["store_code"])
                || isset($query_params["type"])))
        {
            $this->logger->debug("Fetching transaction with filters " . print_r($query_params,true));

            $amount = isset($query_params["amount"]) ? $query_params["amount"] : false;
            $date = isset($query_params["date"]) ? $query_params["date"] : false;
            $transaction_number= isset($query_params["number"]) ? $query_params["number"] : false;
            $till_code = isset($query_params["till_code"]) ? $query_params["till_code"] : false;
            $store_code = isset($query_params["store_code"]) ? $query_params["store_code"] : false;
            $type = isset($query_params["type"]) ? $query_params["type"] : 'REGULAR';
            $start = isset($query_params["start_id"]) && is_numeric($query_params["start_id"]) ? $query_params["start_id"] : 0;
            $batch_size = isset($query_params["limit"]) &&
                            is_numeric($query_params["limit"]) &&
                            intval($query_params["limit"]) <= 100 &&
                            intval($query_params["limit"]) > 0
                            ? $query_params["limit"] : 100;

            if(empty($transaction_number) && empty($store_code) && empty($till_code) && empty($date))
            {
                $transactions = array(array("item_status" =>
                    array("success" => "false",
                        "code" => ErrorCodes::$transaction["ERR_INSUFFICIENT_PARAMETERS"],
                        "message" => ErrorMessage::$transaction["ERR_INSUFFICIENT_PARAMETERS"]
                    )
                )
                );

                return array("status" => array("success" => "false",
                    "code" => ErrorCodes::$api['FAIL'],
                    "message" => ErrorMessage::$api["FAIL"]),
                    "transactions" => array("transaction" => $transactions));
            }

            try {
                $transactions = $transactionController->filterBills($transaction_number, $date, $amount, $store_code,
                    $till_code, $type, $start, $batch_size, $should_return_tenders, $should_return_credit_notes, $should_return_user_id
                );
            }
            catch(Exception $e)
            {
                $transactions = array(array("item_status" =>
                                           array("success" => "false",
                                                 "code" => $e->getCode(),
                                                 "message" => $e->getMessage()
                                           )
                                      )
                                );
                return array("status" => array("success" => "false",
                    "code" => ErrorCodes::$api["FAIL"],
                    "message" => ErrorMessage::$api["FAIL"]),
                    "transactions" => array("transaction" => $transactions));
            }
            return array("status" => array("success" => "true",
                                           "code" => ErrorCodes::$api["SUCCESS"],
                                           "message" => ErrorMessage::$api["SUCCESS"]),
                         "transactions" => array("count"=> count($transactions),"transaction" => $transactions));
        }

        else if(!isset( $query_params['id'] ) || $gbl_item_count == 0 ){
            $this->logger->debug("No transaction id passed");
            $gbl_item_status_codes = ErrorCodes::$transaction['ERR_NO_TRANSACTION_ID'];


            return array(
                "status" => array(
                    "success" => "false",
                    "code" => ErrorCodes::$api['FAIL'],
                    "message" => ErrorMessage::$api['FAIL']
                ),
                "transactions" =>
                    array("transaction" => array(
                        "item_status" => array(
                            "success" => "false",
                            "code" => ErrorCodes::$transaction['ERR_NO_TRANSACTION_ID'],
                            "message" => ErrorMessage::$transaction['ERR_NO_TRANSACTION_ID']
                        )
                    )
                    )
            );
        }

		foreach ( $transaction_array as $key => $transaction_id )
		{
			$transaction = array();
			try{
				$error_key = "ERR_GET_SUCCESS";
				++$transaction_count;
				Util::saveLogForApiInputDetails( array( 'transaction_id' => $transaction_id ) );
				$transaction = $transactionController->getBills($transaction_id, $should_return_credit_notes , $should_return_tenders );
				Util::saveLogForApiInputDetails(
						array(
								'mobile' => $transaction['customer']['mobile'],
								'email' => $transaction['customer']['email'],
								'external_id' => $transaction['customer']['external_id'],
								'transaction_number' => $transaction['transaction_number'],
								'transaction_id' => $transaction['transaction_id'],
								'user_id' => $transaction['customer']['user_id']
						) );
				if(!$should_return_user_id)
					unset($transaction['customer']['user_id']);
			}
			catch(Exception $e)
			{
				++$error_count;
				$error_key = $e->getMessage();
		
				$transaction['transaction_id'] = $transaction_id;
			}
			
			if($version == 'v1.1')
			{
				$currency_ratio = $transactionController->getCurrencyRatio($transaction["transaction_id"], $transaction["type"]);
				#here get currency ratio
				if($currency_ratio){
					$curreny = array("ratio"=>$currency_ratio["ratio"],
					"id" => $currency_ratio["transaction_currency"]["supported_currency_id"],
					"name" => $currency_ratio["transaction_currency"]["name"],
					"symbol" => $currency_ratio["transaction_currency"]["symbol"],
					);
					$transaction["currency"] = $curreny;
				}
				
				$temp_transaction = array(
									'id' => $transaction['transaction_id'],
									'number' => $transaction['transaction_number']
								);
				unset($transaction['transaction_id']);
				unset($transaction['transaction_number']);
				unset($transaction['is_returned']);
				$transaction = array_merge($temp_transaction, $transaction);
				if($should_return_tenders){
					try{
						$tenders = $transaction['tender'];
						$tenders_arr = array();
						foreach($tenders as $tender){
							$details = array('name' => $tender['orgPaymentModeObj']['label'], 'value' => $tender['amount']);
							foreach($tender['paymentModeAttributeValuesArr'] as $payment_mode_attr){
								$attribute = array('name' => $payment_mode_attr->getOrgPaymentModeAttributeObj()->getName(), 'value' => $payment_mode_attr->getValue() );
								$details['attributes']['attribute'][] = $attribute;
							}
							$tenders_arr[] = $details;
						}
						unset($transaction['tender']);
						$transaction['tenders'] = array();
						if(!empty($tenders_arr))
							$transaction['tenders']['tender']= $tenders_arr;
					} catch (Exception $e){
						$this->logger->debug("Could not find any tenders");
						$transaction['tenders'] = array();
					}
				}
				if($should_return_credit_notes){
					$transaction['credit_notes'] = array();
					$credit_notes_arr = array();
					foreach($transaction['credit_note'] as $credit_note){
						$details = array('amount' => $credit_note['amount'], 'number'=> $credit_note['number'], 'notes'=> $credit_note['notes']);
						$credit_notes_arr[] =  $details;
					}
					if(!empty($credit_notes_arr))
						$transaction['credit_notes']['credit_note'] = $credit_notes_arr;
					unset($transaction['credit_note']);
				}
			}else{
				//unsetting item image url tag in v1
				$new_lineitems = array();
				foreach( $transaction['line_items']['line_item'] as $line_item )
				{
					//unset($line_item['img_url']);
					//Populating only required fields that is needed in v1.0
					if(strtolower($line_item['type']) != 'regular')
						continue;
					$temp_lineitem = array();
					$temp_lineitem['serial'] =  $line_item['serial'];
					$temp_lineitem['item_code'] =  $line_item['item_code'];
					$temp_lineitem['description'] =  $line_item['description'];
					$temp_lineitem['qty'] =  $line_item['qty'];
					$temp_lineitem['rate'] =  $line_item['rate'];
					$temp_lineitem['value'] =  $line_item['value'];
					$temp_lineitem['discount'] =  $line_item['discount'];
					$temp_lineitem['amount'] =  $line_item['amount'];
					$temp_lineitem['attributes'] =  $line_item['attributes'];
                    $temp_lineitem['type'] = $temp_lineitem['type'];
                    $temp_lineitem['outlier_status'] = $line_item['outlier_status'];
					$new_lineitems[] = $temp_lineitem;
				}
				$transaction['line_items']['line_item'] = $new_lineitems;
				if($transaction['is_returned'])
				{
					$transaction['type'] = 'RETURNED';
				}
				else
					$transaction['type'] = 'REGULAR';
			}
			unset($transaction['is_returned']);
		
			$transaction[ 'item_status' ][ 'success' ] = ( $error_key == "ERR_GET_SUCCESS" ) ? 'true' : 'false' ;
			$transaction[ 'item_status' ][ 'code' ] = ErrorCodes::getTransactionErrorCode( $error_key );
			$transaction[ 'item_status' ][ 'message' ] = ErrorMessage::getTransactionErrorMessage( $error_key ) ;
			$arr_item_status_codes[] = $transaction[ 'item_status' ][ 'code' ];
			array_push( $response , $transaction);

		}
		
		//Status
		$status = 'SUCCESS';
		if($transaction_count == $error_count){
			$status = 'FAIL';
		}else if(($error_count < $transaction_count) && ($error_count > 0)){
			$status = 'PARTIAL_SUCCESS';
		}
		
		$result[ 'status' ][ 'success' ] = ($status == 'SUCCESS' || $status == 'PARTIAL_SUCCESS') ? 'true' : 'false';
		$result[ 'status' ][ 'code' ] = ErrorCodes::$api[$status];
		$result[ 'status' ][ 'message' ] = ErrorMessage::$api[$status];
		$result[ 'transactions' ]['transaction'] = $response;
			
		$this->logger->debug("Response: " . print_r($result, true));
		
		////END
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		return 	$result;
		
	}
	
	/**
	 * updates transaction details,
	 * as of now only custom fields update is supported
	 * @param unknown_type $version
	 * @param unknown_type $data
	 * @param unknown_type $query_params
	 * @throws InvalidInputException
	 */
	
	/**
	 * @SWG\Model(
	 * id = "CustomerModelNew",
	 * @SWG\Property( name = "id", type = "string" ),
	 * @SWG\Property( name = "mobile", type = "string" ),
	 * @SWG\Property( name = "email", type = "string" ),
	 * @SWG\Property( name = "external_id", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "TransactionElem",
	 * @SWG\Property( name = "id", type = "string", description = "Transaction id" ),
	 * @SWG\Property( name = "number", type = "string", description = "Bill number" ),
	 * @SWG\Property( name = "type", type = "string", description = "Transaction type" ),
	 * @SWG\Property( name = "customer", type = "CustomerModelNew" ),
	 * @SWG\Property( name = "custom_fields", type = "CustomFields" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "TransactionNew",
	 * @SWG\Property( name = "transaction", type = "TransactionElem" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "UpdateScopeRoot",
	 * @SWG\Property( name = "root", type = "TransactionNew" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/transaction/update.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Custom fields scope updated from loyalty_transaction to regular",
	 *     nickname = "Transaction update scope",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="UpdateScopeRoot")
	 * ))
	 */
	
	/**
	 * @SWG\Model(
	 * id = "Field",
	 * @SWG\Property( name = "name", type = "string" ),
	 * @SWG\Property( name = "value", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CustomFields",
	 * @SWG\Property( name = "field", type = "Field" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CustomerModel",
	 * @SWG\Property( name = "mobile", type = "string", description = "Transaction identifier" ),
	 * @SWG\Property( name = "email", type = "string", description = "Old transaction type" ),
	 * @SWG\Property( name = "external_id", type = "string", description = "New transaction type" ),
	 * @SWG\Property( name = "custom_fields", type = "CustomFields" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "TransactionElement",
	 * @SWG\Property( name = "id", type = "string", description = "Transaction identifier" ),
	 * @SWG\Property( name = "old_type", type = "string", description = "Old transaction type" ),
	 * @SWG\Property( name = "new_type", type = "string", description = "New transaction type" ),
	 * @SWG\Property( name = "customer", type = "CustomerModel" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "TransactionForUpdate",
	 * @SWG\Property( name = "transaction", type = "TransactionElement" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "UpdateTypeRoot",
	 * @SWG\Property( name = "root", type = "TransactionForUpdate" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/transaction/update.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Marking a not interested transaction as regular",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="UpdateTypeRoot")
	 * ))
	 */
	private function update($version, $data, $query_params)
	{
        $headers = apache_request_headers();
		if(!isset($data['root']) 
				|| !isset($data['root']['transaction']) 
				|| empty($data['root']['transaction']))
		{
			$this->logger->debug("No bills passed");
			throw new InvalidInputException(ErrorMessage::$api['INVALID_INPUT'].", ".
					ErrorMessage::$transaction['ERR_NO_RECORDS'], ErrorCodes::$api['INVALID_INPUT']);
		}
		
		$should_return_user_id = $query_params['user_id'] == 'true'?true:false;
		$transactions = $data['root']['transaction'];
		$this->logger->debug("Starting updation of bills : txn count: " . sizeof($data['root']['transaction']));
		
		$result=array();
		$result['status'] = array();
		$result['transactions'] = array();
		$result['transactions']['transaction'] = array();
		
		global $gbl_item_count, $gbl_item_status_codes, $error_count;
		$error_count = 0;
		$gbl_item_count = count($transactions);
		
		$arr_item_status_codes = array();
		$transaction_count = 0;

		foreach ($transactions as $k => $transaction)
		{
            $transactionController = new ApiTransactionController();
            $item_status_key = "ERR_TRANSACTION_UPDATE_SUCCESS";
            Util::resetApiWarnings();
            ++$transaction_count;

			/************************************************/
            try
            {
                if (isset($transaction['delivery_status'])) {
                    if (! empty($transaction['delivery_status'])) {
                        $orderStatus = strtoupper($transaction['delivery_status']);
                        if (! OrderDeliverStatuses::isValidName($orderStatus)) 
                            throw new Exception('ERR_INVALID_TRANSACTION_STATUS'); 
                    } 
                }

                if(isset($transaction['old_type']))
                {
                	if (isset($transaction['new_type']))
                	{
                		$org_retro_transaction_enabled = $this->config_mgr->getKey('CONF_RETRO_TRANSACTION_ENABLE');
                		if ($org_retro_transaction_enabled != 1)
                			throw new Exception("ERR_RETRO_TRANSACTION_DISABLED");
                	}
                    if(! isset($transaction['new_type']))
                    {
                        throw new Exception("ERR_NO_TARGET_TYPE");
                    }
                    else if(strtoupper($transaction['old_type']) != 'NOT_INTERESTED')
                    {
                        throw new Exception("ERR_UNSUPPORTED_TYPE_CHANGE");
                    }
                    else if(strtoupper($transaction['new_type']) != 'REGULAR')
                    {
                        throw new Exception("ERR_UNSUPPORTED_TYPE_CHANGE");
                    }
                    else if(empty($headers['X-CAP-CLIENT-SIGNATURE']))
                    {
                        throw new Exception("ERR_CLIENT_SIGNATURE_MANDATORY");
                    }
                    else if(!isset($transaction["customer"]))
                    {
                        throw new Exception();
                    }
                    else if(!(isset($transaction["customer"]["mobile"]) ||
                           isset($transaction["customer"]["email"]) ||
                           isset($transaction["customer"]["external_id"])))
                    {
                        throw new Exception("ERR_NO_IDENTIFIER");
                    }
                    else if(!isset($transaction["id"]))
                    {
                        throw new Exception("ERR_NO_TRANSACTION_ID");
                    }
                    else
                    {
                        $item_status_key = "ERR_RETRO_TRANSACTION_UPDATE_SUCCESS";
                        $retro_transaction_result = $transactionController->markNotInterestedRegular($transaction["id"], $transaction["customer"], $transaction["notes"], $transaction["custom_fields"]);
                        $retro_transaction_result['old_id'] = $transaction["id"];
                        $retro_transaction_result['old_type'] = 'NOT_INTERESTED';
                    }
                }

                else if(!isset($transaction['old_type']) && !isset($transaction['new_type']))
                {
                    if(!$not_interested_bill)
                    {
                        $name = $transaction['customer']['name'];
                        global $gbl_country_code;
                        if(isset($transaction['customer']['country_code']))
                        {
                            $gbl_country_code = $transaction['customer']['country_code'];
                        }
                        else
                        {
                            $gbl_country_code = '';
                        }
                    }
                    //TODO: need to change when we support transaction update for not_interested as well
                    $transaction_type = TRANS_TYPE_REGULAR;

                    $transactionController = new ApiTransactionController();
                    $transaction_result = array();
                    $transaction_result = $transactionController->updateTransaction($transaction);
                }
            }
			catch(Exception $e)
			{
				$this->logger->error("Error While Transaction Update: ".$e->getMessage());
				++$error_count;
				$item_status_key = $e->getMessage();
			}
			
			$item_status = array(
						"success" => ErrorCodes::$transaction[$item_status_key] 
										== ErrorCodes::$transaction["ERR_TRANSACTION_UPDATE_SUCCESS"] ||
                                    ErrorCodes::$transaction[$item_status_key]
                                        == ErrorCodes::$transaction["ERR_RETRO_TRANSACTION_UPDATE_SUCCESS"]
                                ? "true" : "false",
						"code" => ErrorCodes::$transaction[$item_status_key],
						"message" => ErrorMessage::$transaction[$item_status_key]
					);

			$return_item = array();
			$customer = array();
			if($transactionController->getUser())
			{
				$user = $transactionController->getUser();
				if($should_return_user_id)
				{
					$customer['user_id'] = $user->user_id;
				}
				ApiCacheHandler::triggerEvent("transaction_update", $user->user_id);
				ApiUtil::mcUserUpdateCacheClear($user->user_id);
				$customer['mobile'] = $user->mobile;
				$customer['email'] = $user->email;
				$customer['external_id'] = $user->external_id;
				$customer['firstname'] = $user->first_name;
				$customer['lastname'] = $user->last_name;
				$customer['loyalty_points'] = $user->loyalty_points;
				$customer['lifetime_points'] = $user->lifetime_points;
				$customer['lifetime_purchases'] = $user->lifetime_purchases;
				$customer['current_slab'] = $user->slab_name;
				$customer['tier_expiry_date'] = $user->slab_expiry_date;
			}
			else
			{
				if($should_return_user_id)
				{
					$customer['user_id'] = $transaction['customer']['id'];
				}
				$customer['mobile'] = $transaction['customer']['mobile'];
				$customer['email'] = $transaction['customer']['email'];
				$customer['external_id'] = $transaction['customer']['external_id'];
			}
			
			if(count($transaction_result) > 0)
			{
				$return_item['id'] = $transaction_result['id'];
				$return_item['number'] = $transaction_result['bill_number'];
				$return_item['type'] = $transaction_type;
				$return_item['customer'] = $customer;
				$return_item['amount'] = $transaction_result['bill_amount'];
				$return_item['gross_amount'] = $transaction_result['bill_gross_amount'];
				$return_item['discount'] = $transaction_result['bill_discount'];
				$return_item['billing_time'] = $transaction_result['date'];
				$return_item['notes'] = $transaction_result['notes'];
                $return_item['delivery_status'] = is_null($transaction_result['delivery_status']) ? 
                                                    'DELIVERED' : $transaction_result['delivery_status'];

				try{
					$storeController = new ApiStoreController();
					$base_store_id = $storeController->getBaseStoreId();
					$store_details = $storeController->getInfoDetails($base_store_id);
					$base_store_name = $store_details[0]['store_name'];
				}catch(Exception $e)
				{
					$this->logger->error("BaseStore not found, sending as blank");
					$base_store_name = "";
				}
				//TODO: return proper base store name
				$return_item['store'] = $base_store_name;
				
				//TODO: return all lineitems
				$line_items = $transactionController->
									loyaltyController->getBillAndLineitemDetails($transaction_result['id']);
				if(count($line_items) > 0)
				{
					$return_item['line_items'] = array();
					$return_item['line_items']['line_item'] = array();
					
					$item_codes = array();
					foreach($line_items AS $item)
					{
						$temp_item = array();
						$temp_item['serial'] = $item['serial'];
						$temp_item['item_code'] = $item['item_code'];
						$temp_item['description'] = $item['description'];
						$temp_item['qty'] = $item['qty'];
						$temp_item['value'] = $item['value'];
						$temp_item['discount'] = $item['discount'];
						$temp_item['amount'] = $item['amount'];
						$temp_item['img_url'] = $item['img_url'];
						$temp_item['rate'] = $item['rate'];
						$temp_item['attributes'] = array();
						$temp_item['attributes']['attribute'] = array();
						$return_item['line_items']['line_item'][] =$temp_item;
						$item_codes[] = "'".$item['item_code']."'";
					}
					
					if(count($item_codes) > 0)
						$attributes = $transactionController->
										loyaltyController->getAttributesForItems($item_codes);
					
					if(count($attributes) > 0)
					{
						foreach($return_item['line_items']['line_item'] as $key => $line_item)
						{
							$temp_attr = $attributes[$line_item['item_code']];
							if(count($temp_attr) > 0)
							{
								$item_attributes = array();
								foreach($temp_attr as $attr_name => $attr_value)
								{
									$item_attributes[] = array( 'name' => $attr_name , 'value' => $attr_value );
								}
								$return_item['line_items']['line_item']
									[$key]['attributes']['attribute'] = $item_attributes;
							}
						}
					}
				}
				$return_item['custom_fields'] = array();
				$return_item['custom_fields']['success_count'] = $transaction_result['cf_success_count'];
				$return_item['custom_fields']['failure_count'] = $transaction_result['cf_failure_count'];
				$cf = new CustomFields();
				$custom_fields = $cf->getCustomFieldValuesByAssocId(
						$this->currentorg->org_id, 'loyalty_transaction', $return_item['id']);
				if(count($custom_fields) > 0)
				{
					$return_item['custom_fields']['field'] = array();
					foreach($custom_fields AS $name => $value)
					{
						$field = array();
						$field['name'] = $name;
						$field['value'] = $value;
						$return_item['custom_fields']['field'][] = $field;
					}
				}
			}
            else if(!empty($retro_transaction_result))
            {
                $return_item =  $retro_transaction_result;
            }
			else
			{
				$return_item['id'] = $transaction['id'];
				$return_item['number'] = $transaction['number'];
				$return_item['type'] = $transaction_type;
				$return_item['customer'] = $customer;
			}
			
			$strWarnings = Util::getApiWarnings();

            if($retro_transaction_result)
            {
                $item_status['message'] = $item_status['message'] . ", " . $return_item['item_status']['message'];
                $item_status['success'] = $return_item['item_status']['success'];
                $return_item['item_status'] = $item_status;
            }
            else{
                if($strWarnings !== null)
                    $item_status['message'] .= ", $strWarnings";
                $return_item['item_status'] = $item_status;
            }
			/************************************************/
			//add each bill as necessary
			$result['transactions']['transaction'][] = $return_item;
			if(isset($transactionController))
				unset($transactionController);
			$arr_item_status_codes[] = $item_status['code'];
		}
		
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array();
		$status = 'SUCCESS';
		if($transaction_count == $error_count){
			$status = 'FAIL';
		}else if(($error_count < $transaction_count) && $error_count > 0){
			$status = 'PARTIAL_SUCCESS';
		}
		$this->listener_mgr->getSideEffectsAsResponse($GLOBALS['listener']);
		$result['side_effects'] = $this->listener_mgr->getSideEffectsAsResponse($GLOBALS['listener']);
		$GLOBALS['listener'] = array();
		
		$api_status['success'] = ($status == 'SUCCESS') ? 'true' : 'false';
		$api_status['code'] = ErrorCodes::$api[$status];
		$api_status['message'] = ErrorMessage::$api[$status];
		
		$result['status'] = $api_status;
		return $result;
	}
	
	/**
	 * Checks if the system supports the version passed as input
	 *
	 * @param $version
	 */
	
	public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1', 'v1.1'))){
			return true;
		}
		return false;
	}
	
	public function checkMethod($method)
	{
		if(in_array(strtolower($method), array('add', 'add_with_local_currency', 'get', 'redemptions', 'update'))){
			return true;
		}
		return false;
	}
}
