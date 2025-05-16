<?
include_once "test/models/BaseModelTestFile.php";
include_once 'models/CustomField.php';
include_once 'models/CustomFieldValue.php';

class CustomFieldModelsTest extends ApiTestBase
{

	public function __construct(){

		parent::__construct();
	}

	public function testLoadCustomField()
	{
		global $logger;
		$org_id = 0;
		$limit = 10;
		$cfValue = "Value-".microtime(true);
		$cfvArr = CustomFieldValue::loadAll($org_id, $filter, $limit);
		
		$this->assertEquals(count($cfvArr), $limit);
		
		// check whetehr org has some attribute
		if($cfvArr)
		{
			$this->breaker("Custom Field load fn");
			$cfv = CustomFieldValue::load($org_id, $cfvArr[0]->getCustomFieldId(), $cfvArr[0]->getAssocId());
			$this->assertEquals($cfvArr[0]->getCustomFieldValue(), $cfv->getCustomFieldValue());

			$cfv->setCustomFieldValue($cfValue);
			
			$cfvSave = $cfv->save();
			$this->assertNotNull($cfvSave);
			$cf = CustomField::loadById($org_id, $cfv->getCustomFieldId());
			$this->assertNotNull($cf->getName());

			
			{
				if(substr($fnName, 0, 3) == "get")
					$cf->$fnName();
			}
			
			foreach(get_class_methods($cfv) as $fnName)
			{
				if(substr($fnName, 0, 3) == "get")
					$cfv->$fnName();
			}
				

			// get the details of the cf if
			$filters = new CustomFieldValueLoadFilters();
			$filters->assoc_id = $cfvArr[0]->getAssocId();
			$filters->custom_field_id = $cfvArr[0]->getCustomFieldId();
			$cfWithVal = CustomField::loadAll($org_id, $filters);
			$this->assertEquals($cfValue, $cfWithVal[0]->getCustomFieldValueString());
		}
	} 

	private function breaker($str = "")
	{
		return;
		$length = 74 - floor(strlen($str)/2) - 2 ;
		print "\n".str_repeat("*", $length). "  ".ucwords($str)."  ".str_repeat("*", $length)."\n";
	}
	
}


