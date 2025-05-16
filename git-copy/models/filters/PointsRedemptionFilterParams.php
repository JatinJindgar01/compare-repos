<?php
class PointsRedemptionFilterParams
{
	private $bill_ids;
	private $include_points_deductions;

	public function getBillIds()
	{
		return $this->bill_ids;
	}

	public function setBillIds($bill_ids)
	{
		$this->bill_ids = $bill_ids;
	}


	public function getincludePointsDeductions()
	{
		return $this->include_points_deductions;
	}

	public function setincludePointsDeductions($include_points_deductions)
	{
		$this->include_points_deductions = $include_points_deductions;
	}
}
?>