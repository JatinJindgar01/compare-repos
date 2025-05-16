<?
include_once "test/models/BaseModelTestFile.php";
include_once 'models/PaymentMode.php';
include_once 'models/OrgPaymentMode.php';
include_once 'models/PaymentModeAttribute.php';
include_once 'models/OrgPaymentModeAttribute.php';
include_once 'models/PaymentModeAttributePossibleValue.php';
include_once 'models/OrgPaymentModeAttributePossibleValue.php';
include_once 'models/PaymentModeDetails.php';

class PaymentModeTest extends ApiTestBase
{

	public function __construct(){

		parent::__construct();
	}

	public function testPaymentMode()
	{
		global $logger;
		$pmArr = PaymentMode::loadAll();
		$this->assertNotNull(count($pmArr));
		
		$pm = next($pmArr);
		$paymentAttrId = $pm->getPaymentModeId();
		$this->assertNotNull($paymentAttrId);
		
		// check load by id is working or now
		$pm1 = PaymentMode::loadById($paymentAttrId);
		$this->assertEquals($pm->getPaymentModeId(), $pm1->getPaymentModeId());
	} 

	public function testOrgPaymentMode()
	{
		global $logger;
		$limit = 10;
		$org_id = 0 ;
		$opmArr = OrgPaymentMode::loadAll($org_id);
		$this->assertNotNull(count($opmArr));
	
		$opm = $opmArr[0];
		$orgpaymentAttrId = $opm->getOrgPaymentModeId();
		$orgpaymentAttrlabel = $opm->getLabel();
		$this->assertNotNull($orgpaymentAttrId);
	
		
		// check load by id is working or now
		$opm1 = OrgPaymentMode::loadById($org_id, $orgpaymentAttrId);
		$this->assertEquals($opm->getPaymentModeId(), $opm1->getPaymentModeId());
		
		// check load by id is working or now
		$opm2 = OrgPaymentMode::loadByLabel($org_id, $orgpaymentAttrlabel);
		$this->assertEquals($opm->getPaymentModeId(), $opm2->getPaymentModeId());
		
	}

	public function testPaymentModeAttribute()
	{
		global $logger;
		$pmArr = PaymentModeAttributeModel::loadAll();
		$this->assertNotNull(count($pmArr));
	
		$pm = next($pmArr);
		$paymentAttrId = $pm->getPaymentModeAttributeId();
		$paymentModeId = $pm->getPaymentModeId();
		$paymentName = $pm->getName();
		$this->assertNotNull($paymentAttrId);
	
		// check load by id is working or now
		$pm1 = PaymentModeAttributeModel::loadById($paymentAttrId);
		$this->assertEquals($pm->getName(), $pm1->getName());

		$pm2 = PaymentModeAttributeModel::loadByName($paymentName, $paymentModeId);
		$this->assertEquals($pm->getName(), $pm2->getName());
		
	}

	public function testOrgPaymentModeAttribute()
	{
		global $logger;
		$org_id = 0;
		$pmArr = OrgPaymentModeAttribute::loadAll($org_id);
		$this->assertNotNull(count($pmArr));
	
		//foreach($pm as $a) print $a."---\n";
		$pm = $pmArr[0];
		
		$paymentAttrId = $pm->getOrgPaymentModeAttributeId();
		$orgpaymentModeId = $pm->getOrgPaymentModeId();
		$paymentName = $pm->getName();
		$this->assertNotNull($orgpaymentModeId);
	
		// check load by id is working or now
		$pm1 = OrgPaymentModeAttribute::loadById($org_id, $paymentAttrId);
		
		$this->assertEquals($pm->getName(), $pm1->getName());
	
		$pm2 = OrgPaymentModeAttribute::loadByName($org_id, $paymentName, $orgpaymentModeId);
		$this->assertEquals($pm->getName(), $pm2->getName());
	
	}

	public function testPaymentModeAttributePossibleValue()
	{
		global $logger;
		$org_id = 0;
		$pmArr = PaymentModeAttributePossibleValue::loadAll(null);
		$this->assertNotNull(count($pmArr));
		
		$pm = $pmArr[0];
		$valueId = $pm->getPaymentModeAttributePossibleValueId();
		
		$pm1 = PaymentModeAttributePossibleValue::loadById($org_id, $valueId);
		$this->assertEquals($pm->getValue(), $pm1->getValue());
	}
	
	public function testOrgPaymentModeAttributePossibleValue()
	{
		global $logger;
		$org_id = 0;
		$pmArr = OrgPaymentModeAttributePossibleValue::loadAll($org_id);
		$this->assertNotNull(count($pmArr));
		$pm = $pmArr[0];
		$valueId = $pm->getOrgPaymentModeAttributePossibleValueId();
		
		$pm1 = OrgPaymentModeAttributePossibleValue::loadById($org_id, $valueId);
		$this->assertEquals($pm->getValue(), $pm1->getValue());
	}

	public function testPaymentModeDetails()
	{
		global $logger;
		$org_id = 0;
		$pmArr = PaymentModeDetails::loadAll($org_id);
		$this->assertNotNull(count($pmArr));
	
		$pm = $pmArr[0];
		$valueId = $pm->getPaymentModeDetailsId();
	
		$pm1 = PaymentModeDetails::loadById($org_id, $valueId);
		$this->assertEquals($pm->getRefId(), $pm1->getRefId());

		$pmArr2 = PaymentModeDetails::loadByReference($org_id, $pm1->getRefId(), $pm1->getRefType());
		$this->assertNotNull(count($pmArr2));
		$pm3 = $pmArr2[0];
		$this->assertEquals($pm->getRefId(), $pm3->getRefId());
	}
	
}


