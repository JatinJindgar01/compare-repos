<?
include_once "test/models/BaseModelTestFile.php";
class ReturnModelsTest extends ApiTestBase
{

	public function __construct(){

		parent::__construct();
	}

	public function testLoadCustomer()
	{
		include_once 'models/TransactionReturnCustomer.php';
		
		$org_id = 0;
		$limit = 10;
		$custArr = TransactionReturnCustomer::loadAll($org_id, $filter, $limit);

		// loaded 5 records
		$this->assertEquals($limit, count($custArr));

		$this->breaker("$limit rows return");
		
		$custArr2 = TransactionReturnCustomer::loadById($org_id, $custArr[2]->getUserId());
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
				$c = TransactionReturnCustomer::loadByMobile($org_id, $custArr[$i]->getMobile());
				$this->assertEquals($custArr[$i]->getUserId(), $c->getUserId());
				break;
			}
		}

		for($i=0; $i<count($custArr); $i++)
		{
			if($custArr[$i]->getEmail())
			{
				$this->breaker("Test for email");
				$c = TransactionReturnCustomer::loadByEmail($org_id, $custArr[$i]->getEmail());
				$this->assertEquals($custArr[$i]->getUserId(), $c->getUserId());
				
				break;
			}
		}

		for($i=0; $i<count($custArr); $i++)
		{
			if($custArr[$i]->getExternalId())
			{
				$this->breaker("Test for External Id");
				$c = TransactionReturnCustomer::loadByExternalId($org_id, $custArr[$i]->getExternalId());
				$this->assertEquals($custArr[$i]->getUserId(), $c->getUserId());
				break;
			}
		}

	}


	public function testReturnTransaction()
	{
		include_once 'models/ReturnedTransaction.php';

		$org_id =0;$limit = 2;
		$objArr = ReturnedTransaction::loadAll($org_id, null, $limit);
		
		$this->assertEquals(count($objArr), $limit);
		
		foreach(get_class_methods($objArr[0]) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$objArr[0]->$fnName();
		}
	}
	
	public function testReturnLineitem()
	{
		include_once 'models/ReturnedLineitem.php';
	
		$org_id =0;$limit = 2;
		$objArr = ReturnedLineitem::loadAll($org_id, null, $limit);
	
		$this->assertEquals(count($objArr), $limit);
	
		foreach(get_class_methods($objArr[0]) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$objArr[0]->$fnName();
		}
		
	}
	
	private function breaker($str = "")
	{
		return;
		$length = 74 - floor(strlen($str)/2) - 2 ;
		print "\n".str_repeat("*", $length). "  ".ucwords($str)."  ".str_repeat("*", $length)."\n";
	}
	
}


