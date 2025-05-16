<?php

include_once 'models/Slab.php';

/**
 * @author cj
 * 
 *  Hold the slab information of a customer
 */
class CustomerSlab{

	private $user_id;
	private $currentSlab;
	private $nextSlab;
	
	private $logger;
	
	public function __construct($org_id, $user_id = null){
		
		global $logger;
		$this->logger = $logger;
	}
	

	public function getUserId()
	{
	    return $this->user_id;
	}

	public function setUserId($user_id)
	{
	    $this->user_id = $user_id;
	}

	public function getCurrentSlab()
	{
	    return $this->currentSlab;
	}

	public function getNextSlab()
	{
	    return $this->nextSlab;
	}

	// TODO: implement the function
	public function loadSlabDetails()
	{
		
	}
}