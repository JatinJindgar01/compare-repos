<?
include_once "test/models/BaseModelTestFile.php";
class CustomerModelTest extends ApiTestBase
{

	public function __construct(){

		parent::__construct();
	}

	public function testLoadLoyaltyCustomer()
	{
		include_once 'models/LoyaltyCustomer.php';
		
		$org_id = 0;
		$limit = 10;
		$custArr = LoyaltyCustomer::loadAll($org_id, $filter, $limit);

		// loaded 5 records
		$this->assertEquals($limit, count($custArr));

		$this->breaker("$limit rows return");
		$this->breaker("Test for load by id");
		
		//$custArr2 = new LoyaltyCustomer(0);
		//$custArr2->loadById($custArr[2]->getUserId());
		//print ($custArr[0]->toString());
		
		$custArr2 = LoyaltyCustomer::loadById($org_id, $custArr[2]->getUserId());
		$this->assertNotNull($custArr2->toString());
		
		$this->assertNotNull($custArr2->toArray());
		
		$this->assertEquals($custArr[2]->getUserId(), $custArr2->getUserId());
		
		//print "Transaction - ".count($custArr2->loadTransactions());
		//print "CFs - ".count($custArr2->loadCustomFieldsValues());

		for($i=0; $i<count($custArr); $i++)
		{
			if($custArr[$i]->getMobile())
			{
				$this->breaker("Test for mobile");
				$c = LoyaltyCustomer::loadByMobile($org_id, $custArr[$i]->getMobile());
				$this->assertEquals($custArr[$i]->getUserId(), $c->getUserId());
				break;
			}
		}

		for($i=0; $i<count($custArr); $i++)
		{
			if($custArr[$i]->getEmail())
			{
				$this->breaker("Test for email");
				$c = LoyaltyCustomer::loadByEmail($org_id, $custArr[$i]->getEmail());
				$this->assertEquals($custArr[$i]->getUserId(), $c->getUserId());
				
				break;
			}
		}

		for($i=0; $i<count($custArr); $i++)
		{
			if($custArr[$i]->getExternalId())
			{
				$this->breaker("Test for External Id");
				$c = LoyaltyCustomer::loadByExternalId($org_id, $custArr[$i]->getExternalId());
				$this->assertEquals($custArr[$i]->getUserId(), $c->getUserId());
				break;
			}
		}

	}

	/**
	 * @expectedException     ApiException
	 * @expectedExceptionCode 20011
	 */
	public function testLoadLoyaltyCustomerException()
	{
		include_once 'models/LoyaltyCustomer.php';
		$obj = LoyaltyCustomer::loadById(4, 0);
		
		foreach(get_class_methods($obj) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$obj->$fnName();
		}
		
		
	}
	public function testInsertLoyaltyCustomer()
	{
		include_once 'models/LoyaltyCustomer.php';
		
		$limit = 50;
		$org_id = 0;
		
		while($limit-- > 0)
		{
			$externalId = microtime(true);
			$filter = new CustomerLoadFilters();
			$filter->external_id = $externalId;
			try{
				$custArr2 = LoyaltyCustomer::loadAll(0, $filter, 1, 0);
			}catch(Exception $e){}
			usleep(500);
			if(!$custArr2)
				break;
		}
		
		
		if($limit <= 0)
			return;
		$cust = new LoyaltyCustomer($org_id);
		$cust->setEmail("$externalId@ut.com");
		$cust->setExternalId($externalId);
		$cust->setMobile(floor(microtime(true)*1000));
		$cust->setFirstname("First");
		$cust->setLastname("Last");
		$cust->save();

		$cust->save();
		
		$this->breaker("Validation the insertion was successful");
		$this->assertLessThan($cust->getUserId(), 0);
		$this->assertLessThan($cust->getLoyaltyId(), 0);

		$this->breaker("Reading from cache");
		LoyaltyCustomer::loadById(0,$cust->getUserId());
		
		
		/*$this->breaker("Validation the insertion failed due to email validation");

		$cust = new LoyaltyCustomer(0, 0,  $logger);
		$cust->setEmail("$externalId@ut.com");
		$cust->setExternalId($externalId);
		$cust->setFirstname("First");
		$cust->setLastname("Last");
		$cust->save();
		//$this->assertLessThan($cust->getUserId(), 0);
		$this->assertNull($cust->getLoyaltyId());
		*/
		
	}

	private function breaker($str = "")
	{
		return;
		$length = 74 - floor(strlen($str)/2) - 2 ;
		print "\n".str_repeat("*", $length). "  ".ucwords($str)."  ".str_repeat("*", $length)."\n";
	}
	
}


