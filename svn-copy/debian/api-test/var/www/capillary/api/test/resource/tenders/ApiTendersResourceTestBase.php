<?
include_once('test/resource/ApiResourceTestBase.php');
include_once('resource/tenders.php');

class ApiTendersResourceTestBase extends ApiResourceTestBase
{
	protected $tendersResourceObj;

	public function __construct()
	{
		$this->tendersResourceObj = new TendersResource();
		parent::__construct();
	}
}

?>
