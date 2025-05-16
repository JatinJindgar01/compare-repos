<?php

/*
*
* -------------------------------------------------------
* CLASSNAME:        Voucher
* GENERATION DATE:  05.08.2011
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  voucher
* FOR MYSQL DB:     campaigns
*
*/

//**********************
// CLASS DECLARATION
//**********************

class ApiVoucherModel
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $voucher_id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $org_id;
	protected $voucher_code;
	protected $pin_code;
	protected $created_date;
	protected $issued_to;
	protected $current_user;
	protected $voucher_series_id;
	protected $created_by;
	protected $test;
	protected $amount;
	protected $bill_number;
	protected $loyalty_log_ref_id;
	protected $max_allowed_redemptions;
	protected $group_id;
	protected $issued_at_counter_id;
	protected $rule_map;

	protected $database; // Instance of class database

	protected $table = 'voucher';
	private $current_org_id;

	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function ApiVoucherModel()
	{	
		global $currentorg;
		$this->current_org_id = $currentorg->org_id;
		
		$this->database = new Dbase( 'campaigns' );

	}


	// **********************
	// GETTER METHODS
	// **********************


	function getVoucherId()
	{	
		return $this->voucher_id;
	}

	function getOrgId()
	{	
		return $this->org_id;
	}

	function getVoucherCode()
	{	
		return $this->voucher_code;
	}

	function getPinCode()
	{	
		return $this->pin_code;
	}

	function getCreatedDate()
	{	
		return $this->created_date;
	}

	function getIssuedTo()
	{	
		return $this->issued_to;
	}

	function getCurrentUser()
	{	
		return $this->current_user;
	}

	function getVoucherSeriesId()
	{	
		return $this->voucher_series_id;
	}

	function getCreatedBy()
	{	
		return $this->created_by;
	}

	function getTest()
	{	
		return $this->test;
	}

	function getAmount()
	{	
		return $this->amount;
	}

	function getBillNumber()
	{	
		return $this->bill_number;
	}

	function getLoyaltyLogRefId()
	{	
		return $this->loyalty_log_ref_id;
	}

	function getMaxAllowedRedemptions()
	{	
		return $this->max_allowed_redemptions;
	}

	function getGroupId()
	{	
		return $this->group_id;
	}

	function getIssuedAtCounterId()
	{	
		return $this->issued_at_counter_id;
	}

	function getRuleMap()
	{	
		return $this->rule_map;
	}

	// **********************
	// SETTER METHODS
	// **********************


	function setVoucherId( $voucher_id )
	{
		$this->voucher_id =  $voucher_id;
	}

	function setOrgId( $org_id )
	{
		$this->org_id =  $org_id;
	}

	function setVoucherCode( $voucher_code )
	{
		$this->voucher_code =  $voucher_code;
	}

	function setPinCode( $pin_code )
	{
		$this->pin_code =  $pin_code;
	}

	function setCreatedDate( $created_date )
	{
		$this->created_date =  $created_date;
	}

	function setIssuedTo( $issued_to )
	{
		$this->issued_to =  $issued_to;
	}

	function setCurrentUser( $current_user )
	{
		$this->current_user =  $current_user;
	}

	function setVoucherSeriesId( $voucher_series_id )
	{
		$this->voucher_series_id =  $voucher_series_id;
	}

	function setCreatedBy( $created_by )
	{
		$this->created_by =  $created_by;
	}

	function setTest( $test )
	{
		$this->test =  $test;
	}

	function setAmount( $amount )
	{
		$this->amount =  $amount;
	}

	function setBillNumber( $bill_number )
	{
		$this->bill_number =  $bill_number;
	}

	function setLoyaltyLogRefId( $loyalty_log_ref_id )
	{
		$this->loyalty_log_ref_id =  $loyalty_log_ref_id;
	}

	function setMaxAllowedRedemptions( $max_allowed_redemptions )
	{
		$this->max_allowed_redemptions =  $max_allowed_redemptions;
	}

	function setGroupId( $group_id )
	{
		$this->group_id =  $group_id;
	}

	function setIssuedAtCounterId( $issued_at_counter_id )
	{
		$this->issued_at_counter_id =  $issued_at_counter_id;
	}

	function setRuleMap( $rule_map )
	{
		$this->rule_map =  $rule_map;
	}

	// **********************
	// SELECT METHOD / LOAD
	// **********************

	function load( $id )
	{

		$safe_id = Util::mysqlEscapeString($id);
		$sql =  "SELECT * FROM voucher WHERE voucher_id = '$safe_id'";
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );

	
		$this->voucher_id = $row->voucher_id;
		$this->org_id = $row->org_id;
		$this->voucher_code = $row->voucher_code;
		$this->pin_code = $row->pin_code;
		$this->created_date = $row->created_date;
		$this->issued_to = $row->issued_to;
		$this->current_user = $row->current_user;
		$this->voucher_series_id = $row->voucher_series_id;
		$this->created_by = $row->created_by;
		$this->test = $row->test;
		$this->amount = $row->amount;
		$this->bill_number = $row->bill_number;
		$this->loyalty_log_ref_id = $row->loyalty_log_ref_id;
		$this->max_allowed_redemptions = $row->max_allowed_redemptions;
		$this->group_id = $row->group_id;
		$this->issued_at_counter_id = $row->issued_at_counter_id;
		$this->rule_map = $row->rule_map;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->voucher_id = ""; // clear key for autoincrement

		$sql =  "

			INSERT INTO voucher 
			( 
				org_id,
				voucher_code,
				pin_code,
				created_date,
				issued_to,
				`current_user`,
				voucher_series_id,
				created_by,
				test,
				amount,
				bill_number,
				loyalty_log_ref_id,
				max_allowed_redemptions,
				group_id,
				issued_at_counter_id,
				rule_map 
			) 
			VALUES 
			( 
				'$this->org_id',
				'$this->voucher_code',
				'$this->pin_code',
				'$this->created_date',
				'$this->issued_to',
				'$this->current_user',
				'$this->voucher_series_id',
				'$this->created_by',
				'$this->test',
				'$this->amount',
				'$this->bill_number',
				'$this->loyalty_log_ref_id',
				'$this->max_allowed_redemptions',
				'$this->group_id',
				'$this->issued_at_counter_id',
				'$this->rule_map' 
			)";

		return $this->voucher_id = $this->database->insert( $sql );

	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{


		$sql =  "

			INSERT INTO voucher 
			( 
				voucher_id,
				org_id,
				voucher_code,
				pin_code,
				created_date,
				issued_to,
				current_user,
				voucher_series_id,
				created_by,
				test,
				amount,
				bill_number,
				loyalty_log_ref_id,
				max_allowed_redemptions,
				group_id,
				issued_at_counter_id,
				rule_map 

			) 

			VALUES 
			( 
				'$this->voucher_id',
				'$this->org_id',
				'$this->voucher_code',
				'$this->pin_code',
				'$this->created_date',
				'$this->issued_to',
				'$this->current_user',
				'$this->voucher_series_id',
				'$this->created_by',
				'$this->test',
				'$this->amount',
				'$this->bill_number',
				'$this->loyalty_log_ref_id',
				'$this->max_allowed_redemptions',
				'$this->group_id',
				'$this->issued_at_counter_id',
				'$this->rule_map' 

			)";
		
		return $this->database->update( $sql );


	}
	
	
	/**
	*
	*@param $id
	*/
	function update( $id )
	{

		$safe_id = Util::mysqlEscapeString($id);
		
		$sql = " 
			UPDATE voucher 
			SET  
				org_id = '$this->org_id',
				voucher_code = '$this->voucher_code',
				pin_code = '$this->pin_code',
				created_date = '$this->created_date',
				issued_to = '$this->issued_to',
				`current_user` = '$this->current_user',
				voucher_series_id = '$this->voucher_series_id',
				created_by = '$this->created_by',
				test = '$this->test',
				amount = '$this->amount',
				bill_number = '$this->bill_number',
				loyalty_log_ref_id = '$this->loyalty_log_ref_id',
				max_allowed_redemptions = '$this->max_allowed_redemptions',
				group_id = '$this->group_id',
				issued_at_counter_id = '$this->issued_at_counter_id',
				rule_map = '$this->rule_map' 
			WHERE voucher_id = $safe_id 
			AND org_id = $this->current_org_id ";

		return $result = $this->database->update($sql);

	}

	/**
	*
	*Returns the hash array for the object
	*
	*/
	function getHash(){

		$hash = array();
 
		$hash['voucher_id'] = $this->voucher_id;
		$hash['org_id'] = $this->org_id;
		$hash['voucher_code'] = $this->voucher_code;
		$hash['pin_code'] = $this->pin_code;
		$hash['created_date'] = $this->created_date;
		$hash['issued_to'] = $this->issued_to;
		$hash['current_user'] = $this->current_user;
		$hash['voucher_series_id'] = $this->voucher_series_id;
		$hash['created_by'] = $this->created_by;
		$hash['test'] = $this->test;
		$hash['amount'] = $this->amount;
		$hash['bill_number'] = $this->bill_number;
		$hash['loyalty_log_ref_id'] = $this->loyalty_log_ref_id;
		$hash['max_allowed_redemptions'] = $this->max_allowed_redemptions;
		$hash['group_id'] = $this->group_id;
		$hash['issued_at_counter_id'] = $this->issued_at_counter_id;
		$hash['rule_map'] = $this->rule_map;


		return $hash;
	}
} // class : end

?>
