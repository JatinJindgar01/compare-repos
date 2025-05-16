<?
include_once "test/models/BaseModelTestFile.php";

class LineItemModelTest extends ApiTestBase
{

	public function __construct(){

		global $currentuser;
		parent::__construct();
	}

	public function testInsertLineItem()
	{
	
		include_once 'models/LoyaltyLineitem.php';
		$org_id = 0 ;
	
		$filter = new TransactionLoadFilters();
		$txn = LoyaltyTransaction::loadAll($org_id, $filter, $limit, $offset);
		$txn = $txn[0];
		//$txn->loadById($txn->getTransactionId());
		$this->breaker("Bill collected");
		
		$limit = 50;
		$li = new LoyaltyLineitem(0);
		$li->setDescription('Line item from UT');
		$li->setDiscount(10);
		$li->setGrossAmount(100);
		$li->setTransactionAmount(90);
		$li->setRate(50);
		$li->setQty(2);
		$li->setItemCode(microtime(true));
		$li->setTransactionId($txn->getTransactionId());
		$li->setUserId($txn->getUserId());
		
		$liId = $li->save();
		$this->assertNotNull($li->getLineitemId());
		
		$this->breaker("Inserted loyalty bill line item");
		$li2 = LoyaltyLineitem::loadById($org_id, $li->getLineitemId() );
		
		$this->assertEquals($li2->getLineitemId(), $li->getLineitemId());
	
	}
	
	public function testLoadLineItem()
	{

		include_once 'models/LoyaltyLineitem.php';
		global $logger;
		$org_id = 0;
		
		$limit = 10;
		$filter = new LineItemLoadFilters();
		$liArr = LoyaltyLineitem::loadAll($org_id, $filter, $limit, $offset);
		
		$this->assertNotNull($liArr);
		
		if($liArr)
		{
			$li = LoyaltyLineitem::loadById($org_id, $liArr[0]->getLineitemId());
			$this->assertEquals($liArr[0]->getUserId(), $li->getUserId());
		}
		
		foreach(get_class_methods($liArr[0]) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$liArr[0]->$fnName();
		}
		
	} 

		
	private function breaker($str = "")
	{
		return;
		$length = 74 - floor(strlen($str)/2) - 2 ;
		print "\n".str_repeat("*", $length). "  ".ucwords($str)."  ".str_repeat("*", $length)."\n";
	}
	
}


