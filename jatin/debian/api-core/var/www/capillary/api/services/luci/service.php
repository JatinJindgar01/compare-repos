<?php

	include_once 'luci-php-sdk/LuciClient.php' ;
	include_once 'helper/LuciExceptionMapping.php' ;

	class LuciResponse {		
		public $success;
		public $coupon;
		public $couponSeries;
		public $exceptionCode;
		public $luciExceptionCode;
	}

	class LuciBatchResponse {
		public $success;
		public $coupons;
		public $exceptionCode;
	}

	class LuciService {

		protected $client;
		protected $logger;

		function __construct() {
			global $logger;
			$this -> logger = $logger;

			$this -> client = new LuciSdk\LuciClient();
		}

		private function translateToApiExceptionCode($luciExceptionCode) {

			$cheetahExceptionCode = null;
			if (! empty($luciExceptionCode)) {
				$cheetahExceptionCode = LuciExceptionMapping::getAPIExceptionCode($luciExceptionCode);
			}

			if ($cheetahExceptionCode == -2237 || empty($cheetahExceptionCode)) {
				$cheetahExceptionCode = VOUCHER_ERR_UNKNOWN;
			}
			$apiExceptionCode = Voucher::getResponseErrorKey($cheetahExceptionCode);
			
			$this -> logger -> debug ("TRANSLATING ===> Luci code: '$luciExceptionCode' -> Cheetah code: '$cheetahExceptionCode' -> API Error code: '$apiExceptionCode'");
			return $apiExceptionCode;
		}

		private function printLuciResponse($luciResponse) {
			$newResponse = array();

			foreach ($luciResponse as $response) {
				// Since a huge dump comes in this attribute!
				$resp = json_decode(json_encode($response), true);
				if (isset($resp['ex']['trace'])) {
					unset($resp['ex']['trace']);
				}

				$newResponse[] = $resp;
			}
			return print_r($newResponse, true);
		}

		private function getCouponDetails(luci_CouponDetailsRequest $couponDetailsRequest) {
			$batchResponse = new LuciBatchResponse();
			
			try {
				$couponDetails = $this -> client -> getCouponDetails($couponDetailsRequest);
				$this -> logger -> debug("getCouponDetails :: Response: " . $this -> printLuciResponse($couponDetails));

				if (!empty($couponDetails)) {
					$coupons = array();
					
					foreach ($couponDetails as $coupon) {
						$response = new LuciResponse();

						if (!empty($coupon -> couponCode) && empty($coupon -> ex -> errorCode)) {
							$response -> success = true;
							$response -> coupon = $coupon;
						} else {
							$response -> success = false;
							$response -> exceptionCode = 
								$this -> translateToApiExceptionCode($coupon -> ex -> errorCode);
						}
						$coupons [] = $response;
					}

					$batchResponse -> success = true;
					$batchResponse -> coupons = $coupons;
				} else {
					$batchResponse -> success = false;
					//$batchResponse -> exceptionCode = $this -> translateToApiExceptionCode(NULL);
					$batchResponse -> exceptionCode = 'ERR_INVALID_INPUT';
				}
			} catch (luci_LuciThriftException $lx) {
				$code = $lx -> errorCode;
				$message = $lx -> errorMsg;
				$response -> luciExceptionCode = $code;
				$this -> logger -> error("getCouponDetails :: LuciThriftException code = '$code' & message = '$message'");

				$batchResponse -> success = false;
				$batchResponse -> exceptionCode = $this -> translateToApiExceptionCode($code);
			} catch (TException $tx) {
				$code = $tx -> getCode();
				$message = $tx -> getMessage();
				$this -> logger -> error("getCouponDetails :: ThriftException code = '$code' and message = '$message'");

				$batchResponse -> success = false;
				//$batchResponse -> exceptionCode = $code;
				$batchResponse -> exceptionCode = 'VOUCHER_ERR_UNKNOWN';
			} catch (Exception $ex) {
				$code = $ex -> getCode();
				$message = $ex -> getMessage();
				$this -> logger -> error("getCouponDetails :: Exception code = '$code' and message = '$message'");

				$batchResponse -> success = false;
				//$batchResponse -> exceptionCode = $code;
				$batchResponse -> exceptionCode = 'VOUCHER_ERR_UNKNOWN';
			}

			return $batchResponse;
		}

		public function getCouponDetailsByIds($orgId, $couponIds, $customerIds) {
			$requestId = Util::getServerUniqueRequestId();

			$couponDetailsRequest = new luci_CouponDetailsRequest();
			$couponDetailsRequest -> requestId = $requestId;
			$couponDetailsRequest -> orgId = $orgId;
			if (! empty($couponIds)) {
				$couponDetailsRequest -> couponIdFilter = $couponIds;
			}
			if (! empty($customerIds)) {
				$couponDetailsRequest -> issuedToIdFilter = $customerIds;
			}

			$this -> logger -> debug("getCouponDetailsByIds :: Request payload: " . 
					print_r($couponDetailsRequest, true));
			return $this -> getCouponDetails($couponDetailsRequest);
		}

		public function getCouponDetailsByCodes($orgId, $couponCodes, $customerIds) {
			$requestId = Util::getServerUniqueRequestId();

			$couponDetailsRequest = new luci_CouponDetailsRequest();
			$couponDetailsRequest -> requestId = $requestId;
			$couponDetailsRequest -> orgId = $orgId;
			if (! empty($couponCodes)) {
				$couponDetailsRequest -> couponCodeFilter = $couponCodes;
			}
			if (! empty($customerIds)) {
				$couponDetailsRequest -> issuedToIdFilter = $customerIds;
			}

			$this -> logger -> debug("getCouponDetailsByCodes :: Request payload: " . 
					print_r($couponDetailsRequest, true));
			return $this -> getCouponDetails($couponDetailsRequest);
		}

		public function getCouponDetailsById($orgId, $couponId, $customerId = null) {
			$customerIds = null;
			if (! empty($customerId)) {
				$customerIds = array($customerId);
			}

			$batchResponse = $this -> getCouponDetailsByIds($orgId, array($couponId), $customerIds);

			$response = new LuciResponse();
			if ($batchResponse -> success) {
				$coupons = $batchResponse -> coupons;

				$response = $coupons [0];
			} else {
				$response -> success = $batchResponse -> success;
				$response -> exceptionCode = $batchResponse -> exceptionCode;
			}
		}

		public function getCouponDetailsByCode($orgId, $couponCode, $customerId = null) {
			$customerIds = null;
			if (! empty($customerId)) {
				$customerIds = array($customerId);
			}

			$batchResponse = $this -> getCouponDetailsByCodes($orgId, array($couponCode), array($customerId));

			$response = new LuciResponse();
			if ($batchResponse -> success) {
				$coupons = $batchResponse -> coupons;

				$response = $coupons [0];
			} else {
				$response -> success = $batchResponse -> success;
				$response -> exceptionCode = $batchResponse -> exceptionCode;
			}
		}


		private function getCouponSeries(luci_GetCouponConfigRequest $getCouponConfigRequest) {
			$response = new LuciResponse();

			try {
				$couponSeriesDetails = $this -> client -> getCouponConfiguration($getCouponConfigRequest);
				$this -> logger -> debug("getCouponSeries :: Response: " . 
					$this -> printLuciResponse($couponSeriesDetails));

				if (!empty($couponSeriesDetails)) {
					$couponSeries = $couponSeriesDetails[0];

					if (!empty($couponSeries -> id)) {
						$response -> success = true;
						$response -> couponSeries = $couponSeries;
					} else {
						$errorCode = $couponSeries -> ex -> errorCode;
						$response -> success = false;
						$response -> exceptionCode = $this -> translateToApiExceptionCode($errorCode);
					}
				} else {
					return VOUCHER_ERR_UNKNOWN;
					$errorCode = NULL;
					$response -> success = false;
					$response -> exceptionCode = $this -> translateToApiExceptionCode($errorCode);
				}
			} catch (luci_LuciThriftException $lx) {
				$code = $lx -> errorCode;
				$message = $lx -> errorMsg;
				$response -> luciExceptionCode = $code;
				$this -> logger -> error("getCouponSeries :: LuciThriftException code = '$code' & message = '$message'");

				$response -> success = false;
				$response -> exceptionCode = $this -> translateToApiExceptionCode($code);
			} catch (TException $tx) {
				$code = $tx -> getCode();
				$message = $tx -> getMessage();
				$this -> logger -> error("getCouponSeries :: ThriftException code = '$code' and message = '$message'");

				$response -> success = false;
				//$response -> exceptionCode = $code;
				$response -> exceptionCode = 'VOUCHER_ERR_UNKNOWN';
			} catch (Exception $ex) {
				$code = $ex -> getCode();
				$message = $ex -> getMessage();
				$this -> logger -> error("getCouponSeries :: Exception code = '$code' and message = '$message'");

				$response -> success = false;
				//$response -> exceptionCode = $code;
				$response -> exceptionCode = 'VOUCHER_ERR_UNKNOWN';
			}

			return $response;
		}

		public function getCouponSeriesById($orgId, $couponSeriesId, $loadProductInfo = false) {
			$requestId = Util::getServerUniqueRequestId();

			$getCouponConfigRequest = new luci_GetCouponConfigRequest();
			$getCouponConfigRequest -> requestId = $requestId;
			$getCouponConfigRequest -> orgId = $orgId;
			$getCouponConfigRequest -> couponSeriesId = $couponSeriesId;
			$getCouponConfigRequest -> includeProductInfo = $loadProductInfo;

			$this -> logger -> debug("getCouponSeriesById :: Request payload: " . 
					print_r($getCouponConfigRequest, true));
			return $this -> getCouponSeries($getCouponConfigRequest);
		}


		private function redeemCoupons(luci_RedeemCouponsRequest $redeemCouponRequest) {
			$response = new LuciResponse();

			try {
				$couponDetails = $this -> client -> redeemCoupons($redeemCouponRequest);
				$this -> logger -> debug("redeemCoupons :: Response: " . $this -> printLuciResponse($couponDetails));

				if (!empty($couponDetails)) {
					$coupon = $couponDetails[0];

					if (!empty($coupon -> couponCode) && empty($coupon -> ex -> errorCode)) {
						$response -> success = true;
						$response -> coupon = $coupon;
					} else {
						$luciCode = $coupon -> ex -> errorCode;
						$response -> success = false;
						$response -> exceptionCode = $this -> translateToApiExceptionCode($luciCode);
					}
				} else {
					$response -> success = false;
					$response -> exceptionCode = $this -> translateToApiExceptionCode(NULL);
				}
			} catch (luci_LuciThriftException $lx) {
				$code = $lx -> errorCode;
				$message = $lx -> errorMsg;
				$response -> luciExceptionCode = $code;
				$this -> logger -> error("redeemCoupons :: LuciThriftException code = '$code' and message = '$message'");

				$response -> success = false;
				$response -> exceptionCode = $this -> translateToApiExceptionCode($code);
			} catch (TException $tx) {
				$code = $tx -> getCode();
				$message = $tx -> getMessage();
				$this -> logger -> error("redeemCoupons :: ThriftException code = '$code' and message = '$message'");

				$response -> success = false;
				//$response -> exceptionCode = $code;
				$response -> exceptionCode = 'VOUCHER_ERR_UNKNOWN';
			} catch (Exception $ex) {
				$code = $ex -> getCode();
				$message = $ex -> getMessage();
				$this -> logger -> error("redeemCoupons :: Exception code = '$code' and message = '$message'");

				$response -> success = false;
				//$response -> exceptionCode = $code;
				$response -> exceptionCode = 'VOUCHER_ERR_UNKNOWN';
			}

			return $response;
		}

		public function isRedeemable($orgId, $couponCode, $storeUnitId, $userId) {
			$isRedeemable = false;

			$requestId = Util::getServerUniqueRequestId();
			$milliseconds = round(microtime(true) * 1000);

			$redeemCoupon = new luci_RedeemCoupon();
			$redeemCoupon -> orgId = $orgId;
			$redeemCoupon -> couponCode = $couponCode;
			$redeemCoupon -> storeUnitId = $storeUnitId;
			$redeemCoupon -> eventTimeInMillis = $milliseconds;
			$redeemCoupon -> userId = $userId;

			$redeemCouponRequest = new luci_RedeemCouponsRequest();
			$redeemCouponRequest -> requestId 		= $requestId;
			$redeemCouponRequest -> orgId 			= $orgId;
			$redeemCouponRequest -> redeemCoupons 	= array($redeemCoupon);
			$redeemCouponRequest -> commit 			= false;

			$this -> logger -> debug("isRedeemable :: Request payload: " . 
					print_r($redeemCouponRequest, true));
			return $this -> redeemCoupons($redeemCouponRequest);
		}

		public function redeem($orgId, $couponCode, $redemptionTime, 
			$storeUnitId, $userId, $transactionNo, $transactionAmt) {

			$requestId = Util::getServerUniqueRequestId();

			$eventDate = Util::getMysqlDateTime($redemptionTime);
			$eventTime = strtotime($eventDate);
			if ($eventTime == -1 || !$eventTime) {
				throw new Exception("Cannot convert redemptionTime '$redemptionTime' to timestamp", -1, null);
			}
			$milliseconds = $eventTime * 1000;

			$redeemCoupon = new luci_RedeemCoupon();
			$redeemCoupon -> orgId = $orgId;
			$redeemCoupon -> couponCode = $couponCode;
			$redeemCoupon -> storeUnitId = $storeUnitId;
			$redeemCoupon -> eventTimeInMillis = $milliseconds;
			$redeemCoupon -> userId = $userId;
			$redeemCoupon -> billNumber = $transactionNo;
			$redeemCoupon -> billAmount = $transactionAmt;
			//@TODO Set transaction ID, if available

			$redeemCouponRequest = new luci_RedeemCouponsRequest();
			$redeemCouponRequest -> requestId 		= $requestId;
			$redeemCouponRequest -> orgId 			= $orgId;
			$redeemCouponRequest -> redeemCoupons 	= array($redeemCoupon);
			$redeemCouponRequest -> commit 			= true;

			$this -> logger -> debug("redeem :: Request payload: " . 
				print_r($redeemCouponRequest, true));
			return $this -> redeemCoupons($redeemCouponRequest);
		}


		private function issueCoupon(luci_IssueCouponRequest $issueCouponRequest) {
			$response = new LuciResponse();

			try {
				$coupon = $this -> client -> issueCoupon($issueCouponRequest);
				$this -> logger -> debug("issueCoupon :: Response: " . $this -> printLuciResponse($coupon));

				if (!empty($coupon -> couponCode) && empty($coupon -> ex -> errorCode)) {
					$response -> success = true;
					$response -> coupon = $coupon;
				} else {
					$luciCode = $coupon -> ex -> errorCode;
					$response -> success = false;
					$response -> exceptionCode = $this -> translateToApiExceptionCode($luciCode);
				}
			} catch (luci_LuciThriftException $lx) {
				$code = $lx -> errorCode;
				$message = $lx -> errorMsg;
				$response -> luciExceptionCode = $code;
				$this -> logger -> error("issueCoupon :: LuciThriftException code = '$code' and message = '$message'");

				$response -> success = false;
				$response -> exceptionCode = $this -> translateToApiExceptionCode($code);
			} catch (TException $tx) {
				$code = $tx -> getCode();
				$message = $tx -> getMessage();
				$this -> logger -> error("issueCoupon :: ThriftException code = '$code' and message = '$message'");

				$response -> success = false;
				//$response -> exceptionCode = $code;
				$response -> exceptionCode = 'VOUCHER_ERR_UNKNOWN';
			} catch (Exception $ex) {
				$code = $ex -> getCode();
				$message = $ex -> getMessage();
				$this -> logger -> error("issueCoupon :: Exception code = '$code' and message = '$message'");

				$response -> success = false;
				//$response -> exceptionCode = $code;
				$response -> exceptionCode = 'VOUCHER_ERR_UNKNOWN';
			}

			return $response;
		}

		public function issue($orgId, $userIdToIssueCouponTo, $seriesIdToIssueFrom, 
			$storeUnitId, $transactionId = null, $criterionId = null) {

			$requestId = Util::getServerUniqueRequestId();
			$milliseconds = round(microtime(true) * 1000);

			$issueCouponRequest = new luci_IssueCouponRequest();
			$issueCouponRequest -> requestId 		= $requestId;
			$issueCouponRequest -> orgId 			= $orgId;
			$issueCouponRequest -> couponSeriesId	= $seriesIdToIssueFrom;
			$issueCouponRequest -> storeUnitId 		= $storeUnitId;
			$issueCouponRequest -> userId 			= $userIdToIssueCouponTo;
			$issueCouponRequest -> eventTimeInMillis= $milliseconds;
			if (! empty($transactionId))
				$issueCouponRequest -> billId 		= $transactionId;
			if (! empty($criteriaId))
				$issueCouponRequest -> criteriaId 	= $criterionId;

			$this -> logger -> debug("issue :: Request payload: " . 
				print_r($issueCouponRequest, true));
			return $this -> issueCoupon($issueCouponRequest);
		}
	}