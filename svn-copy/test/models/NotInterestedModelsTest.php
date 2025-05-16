<?
include_once "test/models/BaseModelTestFile.php";
class NotInterestedModelsTest extends ApiTestBase
{

	public function __construct(){

		parent::__construct();
	}


	public function testNotInterestedTransaction()
	{
		include_once 'models/NotInterestedTransaction.php';

		$org_id =0;$limit = 2;
		$objArr = NotInterestedTransaction::loadAll($org_id, null, $limit);
		
		$this->assertEquals(count($objArr), $limit);

		$this->assertNotNull($objArr[0]->getTransactionNumber());
		
		foreach(get_class_methods($objArr[0]) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$objArr[0]->$fnName();
		}
		
	}
	
	public function testNotInterestedLineitem()
	{
		include_once 'models/NotInterestedLineitem.php';
	
		$org_id =0; $limit = 2;
		$objArr = NotInterestedLineitem::loadAll($org_id, null, $limit);
	
		$this->assertEquals(count($objArr), $limit);
		
		$this->assertNotNull($objArr[0]->getItemCode());
		
		foreach(get_class_methods($objArr[0]) as $fnName)
		{
			if(substr($fnName, 0, 3) == "get")
				$objArr[0]->$fnName();
		}
		
	}
	
	private function breaker($str = "")
	{
		return;
		$length = 50 - floor(strlen($str)/2) - 2 ;
		print "\n".str_repeat("*", $length). "  ".ucwords($str)."  ".str_repeat("*", $length)."\n";
	}
	
}


