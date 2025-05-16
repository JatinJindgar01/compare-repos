<?php
 
require_once('test/resource/customer/ApiCustomerResourceTestBase.php');

class CustomerGetTest extends ApiCustomerResourceTestBase
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setUp()
	{
		$this->login("ss.till.capillary", "123");
		parent::setUp();
	}
	
	/**
	 * Test case for coupon created date in Customer/get v1.1
	 */
	public function testCustomerGetCouponCreatedDatev1_1(){
		
		$mobile = "919036984366"; //Assuming customer with this mobile number exists and has coupons
		$query_params = array( 
				'mobile' => $mobile );
		$response = $this->customerResourceObj->process( 
				'v1.1', 'get', null, $query_params, 'GET' );
		$coupons = $response['customers']['customer'][0]['coupons']['coupon'];
// 		$date_match = true;
		foreach ( $coupons as $coupon ){
			
			$actual_coupon_created_date[$coupon['code']] = $coupon['created_date'];
			$coupon_query_params = array(
					'code' => $coupon['code']
					);
			$expected_coupon = $this->couponResourceObj->process( 
					'v1.1', 'get', null, $coupon_query_params, 'GET' );
			$expected_coupon_created_date[$coupon['code']] = $expected_coupon['coupons']['coupon'][0]['issued_on'];
			$this->assertEquals( $expected_coupon_created_date[$coupon['code']], 
					$actual_coupon_created_date[$coupon['code']] );
			/* if( $actual_coupon_created_date[$coupon['code']] !=
					$expect_coupon_created_date[$coupon['code']] ){
				
				$date_match = false;
				break;
			} */
		}
// 		$this->assertEquals('true', $date_match );
	}
	
	/**
	 * Test case for coupon created date in Customer/get v1
	 */
	public function testCustomerGetCouponCreatedDatev1_2(){
	
		$mobile = "919036984366"; //Assuming customer with this mobile number exists and has coupons
		$query_params = array(
				'mobile' => $mobile );
		$response = $this->customerResourceObj->process(
				'v1', 'get', null, $query_params, 'GET' );
		$coupons = $response['customers']['customer'][0]['coupons']['coupon'];
		foreach ( $coupons as $coupon ){
				
			$actual_coupon_created_date[$coupon['code']] = $coupon['created_date'];
			$this->assertNull( $actual_coupon_created_date[$coupon['code']] );
		}
	}
	
	/**
	 * Test case for coupon valid till date in Customer/get v1.1
	 */
	public function testCustomerGetCouponValidTillDatev1_3(){
	
		$mobile = "919036984366"; //Assuming customer with this mobile number exists and has coupons
		$query_params = array(
				'mobile' => $mobile );
		$response = $this->customerResourceObj->process(
				'v1.1', 'get', null, $query_params, 'GET' );
		$coupons = $response['customers']['customer'][0]['coupons']['coupon'];
		// 		$date_match = true;
		foreach ( $coupons as $coupon ){
				
			$i++;
			$actual_coupon_valid_till_date[$coupon['code']] = $coupon['valid_till'];
			
			//Get coupon series info for valid till date and valid days
			$series_query_params = array(
					'id' => $coupon['series_id']
			);
			$coupon_series = $this->couponResourceObj->process(
					'v1.1', 'series', null, $series_query_params, 'GET' );
			$series_valid_till_date = $coupon_series['series']['items']['item'][0]['valid_till_date'];
			$series_valid_days = $coupon_series['series']['items']['item'][0]['valid_days_from_create'];
			
			//Get coupon from coupon code to verify valid till date
			$coupon_query_params = array( 
					'code' => $coupon['code'] );
			$expected_coupon = $this->couponResourceObj->process(
					'v1.1', 'get', null, $coupon_query_params, 'GET' );
			$coupon_issued_on_date = $expected_coupon['coupons']['coupon'][0]['issued_on'];
			
			//Calculate valid till date
			$calculated_date = date( 'Y-m-d', 
						strtotime ( $coupon_issued_on_date.' +'.$series_valid_days.' days' ) );
			$expected_coupon_valid_till_date = 
				$series_valid_till_date < $calculated_date ? $series_valid_till_date : $calculated_date;
			
			//Assert that actual valid till is equal to expected date
			$this->assertEquals( $expected_coupon_valid_till_date,
					$actual_coupon_valid_till_date[$coupon['code']] );
		}
	}
	
	/**
	 * Test case for last_updated time in customer/get 
	 */
	public function testCustomerGetLastUpdatedDatev1_4(){
		
		$mobile = "919036984366"; //Assuming customer with this mobile number exists 
		$query_params = array(
				'mobile' => $mobile,
				'user_id' => 'true');
		$response = $this->customerResourceObj->process(
				'v1.1', 'get', null, $query_params, 'GET' );
		
		$store_timezone = $this->currentuser->getStoreTimeZoneLabel();
		$user = UserProfile::getById( $response['customers']['customer'][0]['user_id'] );
		$user->load( true );
		$last_updated = $user->updated_on;
		$expected_last_updated_time = Util::convertOneTimezoneToAnotherTimezone( 
				$last_updated, 
				date_default_timezone_get(),
				$store_timezone );
		$actual_last_updated_time = $response['customers']['customer'][0]['updated_on'];
		
		$this->assertEquals( $expected_last_updated_time, $actual_last_updated_time );
	}
}