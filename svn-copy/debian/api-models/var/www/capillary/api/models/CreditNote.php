<?php
/**
 * class CreditNote
 *
 */
require_once 'exceptions/ApiCreditNoteException.php';

class CreditNote extends BaseApiModel
{

	protected static $iterableMembers;

	/**
	 *
	 * @access protected
	 */
	protected $id;

	/**
	 *
	 * @access protected
	 */
	protected $org_id;

	/**
	 *
	 * @access protected
	 */
	protected $user_id;

	/**
	 *
	 * @access protected
	 */
	protected $number;

	/**
	 *
	 * @access protected
	 */
	protected $reference_type;

	/**
	 *
	 * @access protected
	 */
	protected $reference_id;

	/**
	 *
	 * @access protected
	 */
	protected $amount;

	/**
	 *
	 * @access protected
	 */
	protected $notes;

	/**
	 * date
	 * @access protected
	 */
	protected $validity;

	/**
	 *
	 * @access protected
	 */
	protected $added_on;

	protected $entered_by;

	protected $currentuser, $current_user_id, $logger;

	protected $db_user;
	
	public function __construct($org_id, $user_id = null )
	{
		global $logger, $currentuser;
		
		$this->currentuser = &$currentuser;
		$this->current_user_id = $currentuser->user_id;
		$this->logger = $logger;
		
		$this->db_user = new Dbase( 'users' );
		
		$this->org_id = $org_id;
		
		if($user_id)
			$this->user_id = $user_id;
		
		$classname = get_called_class();
		$classname::setIterableMembers();
	}
	
	public static function setIterableMembers()
	{
		$classname = get_called_class();
		$classname::$iterableMembers = array(
				"id",
				"org_id",
				"user_id",
				"amount",
				"number",
				"added_on",
				"reference_type",
				"reference_id",
				"notes",
				"validity",
				"entered_by"
		);
	}

	/**
	 *
	 *
	 * @return long
	 * @access public
	 */
	public function save( ) {
		
		//$this->validate();		
		
		if($this->id)
		{
			//UPDATE
			$sql = "UPDATE credit_notes 
					SET amount = $this->amount, notes = '$this->notes', 
						validity = '$this->validity' WHERE id = $this->id";
			$success = $this->db_user->update($sql);
			return $success;
		}
		else
		{
			//INSERT
			$sql = "INSERT INTO credit_notes 
						(org_id, user_id, amount, number, added_on, 
						reference_type, reference_id, notes, validity, entered_by) 
					VALUES 
						('$this->org_id', '$this->user_id', '$this->amount', '$this->number', '$this->added_on', 
						'$this->reference_type', '$this->reference_id', '$this->notes', '$this->validity', '$this->current_user_id')";
			$id = $this->db_user->insert($sql);
			if($id > 0)
			{
				$this->id = $id;
				return true;
			}
			else
			{
				return false;
			}
		}
		
	} // end of member function save
	
	/**
	 * Validates Credit Note data before saving
	 */
	public function validate()
	{
		//throw new ApiCreditNoteException(ApiCreditNoteException::VALIDATION_FAILED);
		return true;
	}

	/**
	 *
	 *
	 * @return CreditNote[]
	 * @static
	 * @access public
	 */
	public static function loadAll( $org_id, $filters ) {
		if(isset($filters) && !($filters instanceof CreditNoteFilters))
		{
			throw new ApiCreditNoteException(ApiCreditNoteException::FILTER_INVALID_OBJECT_PASSED);
		}
		
		$sql = "SELECT * FROM credit_notes WHERE org_id = $org_id";
		if($filters->id)
			$sql .= " AND id = $filters->id";
		if($filters->user_id)
			$sql .= " AND user_id = $filters->user_id";
		if($filters->ref_id)
			$sql .= " AND reference_id = $filters->ref_id";
		if($filters->ref_type)
			$sql .= " AND reference_type = '".$filters->ref_type."'";
		
		$sql .= " ORDER BY id desc ";
		
		if($filters->batch_size)
			$sql .= " LIMIT $batch_size";
		else
		{
			//DEFAULT batch size is 10
			$sql .= " LIMIT 10";
		}
		$db_users = new Dbase('users');
		$rows = $db_users->query($sql);
			
		if($rows)
		{
			$ret = array();
			foreach($rows AS $row)
			{
				$tmp_credit_note = CreditNote::fromArray($org_id, $row);
				$ret[] = $tmp_credit_note;
			}
			return $ret;
		}
		throw new ApiCreditNoteException(ApiCreditNoteException::NO_CREDIT_NOTES_MATCHED);
	} // end of member function loadAll

	/**
	 *
	 *
	 * @return CreditNote[]
	 * @static
	 * @access public
	 */
	public static function loadById( $org_id, $id ) {
		$filters = new CreditNoteFilters();
		$filters->id = $id;
		
		$credit_notes = CreditNote::loadAll($org_id, $filters);
		
		return $credit_notes[0];
	} // end of member function loadById

	/**
	 *
	 *
	 * @return CreditNote[]
	 * @static
	 * @access public
	 */
	public static function loadByUserId( $org_id, $user_id ) {
		$filters = new CreditNoteFilters();
		$filters->user_id = $user_id;
		
		$credit_notes = CreditNote::loadAll($org_id, $filters);
		
		return $credit_notes; 
	} // end of member function loadByUserId

	/**
	 *
	 *
	 * @return CreditNote[]
	 * @static
	 * @access public
	 */
	public static function loadByRefId( $org_id, $ref_type, $ref_id ) {
		$filters = new CreditNoteFilters();
		$filters->ref_id = $ref_id;
		$filters->ref_type = $ref_type;
		
		$credit_notes = CreditNote::loadAll($org_id, $filters);
		return $credit_notes;
	} // end of member function loadByLoyaltyLogId

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getOrgId()
	{
		return $this->org_id;
	}

	public function setOrgId($org_id)
	{
		$this->org_id = $org_id;
	}

	public function getUserId()
	{
		return $this->user_id;
	}

	public function setUserId($user_id)
	{
		$this->user_id = $user_id;
	}

	public function getNumber()
	{
		return $this->number;
	}

	public function setNumber($number)
	{
		$this->number = $number;
	}

	public function getReferenceType()
	{
		return $this->reference_type;
	}

	public function setReferenceType($reference_type)
	{
		$this->reference_type = $reference_type;
	}

	public function getReferenceId()
	{
		return $this->reference_id;
	}

	public function setReferenceId($reference_id)
	{
		$this->reference_id = $reference_id;
	}

	public function getAmount()
	{
		return $this->amount;
	}

	public function setAmount($amount)
	{
		$this->amount = $amount;
	}

	public function getNotes()
	{
		return $this->notes;
	}

	public function setNotes($notes)
	{
		$this->notes = $notes;
	}

	public function getValidity()
	{
		return $this->validity;
	}

	public function setValidity($validity)
	{
		$this->validity = $validity;
	}

	public function getAddedOn()
	{
		return $this->added_on;
	}

	public function setAddedOn($added_on)
	{
		$this->added_on = $added_on;
	}
	
	public function getEnteredBy()
	{
		return $this->entered_by;
	}
	
	public function setEnteredBy($entered_by)
	{
		$this->entered_by = $entered_by;
	}
	

} // end of CreditNote
?>
