<?php
require_once('test/resource/transaction/ApiTransactionResourceTestBase.php');

class TransactionRedemptionTest extends ApiTransactionResourceTestBase
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setUp()
	{
		$this->login("till.005", "123");
		parent::setUp();
		$peContext = new \Api\UnitTest\Context('pointsengine');
		$peContext->set("response/constant", true);
	}
	
	/**
	 * Test case for invalid input
	 */
	public function testTransactionRedemptions_1(){
		
		$points_redeemed = rand( 0, 1000 );
		$rand_mobile_suffix = rand( 10000, 99999 );
		$mobile = "9190369".$rand_mobile_suffix;
		$transaction = array(
				"root" => array(
						"redeem" => array( 
								"points_redeemed" => $points_redeemed,
								"number" => $points_redeemed,
								"customer" => array( "mobile" => $mobile ),
								)
						)
				);
		$transaction_redemption = $this->transactionResourceObj->process( 
				'v1.1', 'redemptions', $transaction, null, 'POST' );
		$this->assertEquals( 'false' , $transaction_redemption['status']['success'] );
		$this->assertEquals( 400 , $transaction_redemption['status']['code'] );
	}
	
	/**
	 * Test case for invalid input
	 */
	public function testTransactionRedemptions_2(){
	
		$points_redeemed = rand( 0, 1000 );
		$rand_mobile_suffix = rand( 10000, 99999 );
		$mobile = "9190369".$rand_mobile_suffix;
		$transaction = array(
				"root" => array(
						"redemptions" => array(
								"redeem" => array(
										"points_redeemed" => $points_redeemed,
										"number" => $points_redeemed,
										"customer" => array( "mobile" => $mobile ),
								)
						)
				)
		);
		$transaction_redemption = $this->transactionResourceObj->process(
				'v1.1', 'redemptions', $transaction, null, 'POST' );
		$this->assertEquals( 'false' , $transaction_redemption['status']['success'] );
		$this->assertEquals( 400 , $transaction_redemption['status']['code'] );
	}
}
	