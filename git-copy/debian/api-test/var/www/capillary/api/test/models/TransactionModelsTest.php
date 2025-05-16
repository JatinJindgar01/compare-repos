<?
include_once "test/models/BaseModelTestFile.php";

class TransactionModelTest extends ApiTestBase
{

	public function __construct(){

		parent::__construct();
	}

	public function testInsertLoyaltyTxn()
	{
	
		include_once 'models/LoyaltyTransaction.php';
		include_once 'models/LoyaltyLineitem.php';
		include_once 'models/LoyaltyCustomer.php';
	
		$org_id = 0 ;
		$filter = new CustomerLoadFilters();
		$filter->limit = 1; $filter->offset = 1;
		$cust = LoyaltyCustomer::loadAll($org_id, $filter, $limit, $offset);
		$cust = $cust[0];
		$cust = LoyaltyCustomer::loadById($org_id, $cust->getUserId());
		
		$this->breaker("User collected");
		
		$limit = 50;
		$txn = new LoyaltyTransaction(0);
		$txn->setDiscount(10);
		$txn->setGrossAmount(100);
		$txn->setTransactionAmount(90);
		$txn->setNotes("Sample Bill from UT");
		$txn->setOutlierStatus('NORMAL');
		$txn->setTransactionDate("-24 hours");
		$txn->setTransactionNumber(microtime(true));
		$txn->setUserId($cust->getUserId());
		
		$li = new LoyaltyLineitem(0);
		$li->setDescription('Line item from UT');
		$li->setDiscount(10);
		$li->setGrossAmount(100);
		$li->setTransactionAmount(90);
		$li->setRate(50);
		$li->setQty(2);
		$li->setItemCode(microtime(true));
		$txn->attachLineItems($li);
		$txnId = $txn->save();
				
		$this->breaker("Inserted loyalty bill");
		$this->assertNotNull($txn);
		//$this->assertEquals($txn->customer->getUserId(), $cust->getUserId());
	
	}
	
	public function testLoadLoyaltyTxn()
	{

		include_once 'models/LoyaltyTransaction.php';
		$org_id =0 ;
		
		$limit = 10;
		$filter = new TransactionLoadFilters();
		$filter->limit = $limit; $filter->offset = 1;
		$txnArr = LoyaltyTransaction::loadAll($org_id,$filter, $limit, $offset);
		
		$this->assertNotNull($txnArr);
		$txnId = $txnArr[0]->getTransactionId();
		 
		$txn = LoyaltyTransaction::loadById($org_id, $txnId);
		$this->assertEquals($txn->getTransactionId(), $txnId);
		
		foreach(get_class_methods($txn) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$txn->$fnName();
		}
		
	} 

		
	private function breaker($str = "")
	{
		//return;
		$length = 74 - floor(strlen($str)/2) - 2 ;
		print "\n".str_repeat("*", $length). "  ".ucwords($str)."  ".str_repeat("*", $length)."\n";
	}
	
}


