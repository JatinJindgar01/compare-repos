<?php

/**
 * @author cj
 *
 * The structure for a slab. 
 * This should be used mostly from pe 
 */
class Slab{
	
	private $name;
	private $serial_number;
	private $description;
	
	

	public function getName()
	{
	    return $this->name;
	}

	public function setName($name)
	{
	    $this->name = $name;
	}

	public function getSerialNumber()
	{
	    return $this->serial_number;
	}

	public function setSerialNumber($serial_number)
	{
	    $this->serial_number = $serial_number;
	}

	public function getDescription()
	{
	    return $this->description;
	}

	public function setDescription($description)
	{
	    $this->description = $description;
	}
}