<?php

class APIWarning
{
	private $strWarning;
	private $arrTemplateParams;
	
	public function __construct($strWarning, array $arrTemplateParams = null) 
	{
		$this->strWarning = $strWarning;
		$this->arrTemplateParams = $arrTemplateParams;
	}
	
	public function getWarning()
	{
		if($this->arrTemplateParams === null)
			return $this->strWarning;
		else
			return Util::templateReplace($this->strWarning, $this->arrTemplateParams);
	}
}

class APIWarningList
{
	//Array of APIWarning object
	private $arrApiWarning;
	
	public function __construct()
	{
		$this->arrApiWarning = array();
	}
	
	public function addWarning($strWarning, array $arrTemplateParams = null)
	{
		$this->arrApiWarning[] = new APIWarning($strWarning, $arrTemplateParams);
	}
	
	public function getWarnings()
	{
		$arrWarning = array();
		
		foreach($this->arrApiWarning as $apiWarning)
		{
			$warning = trim($apiWarning->getWarning());
			if($warning)
				$arrWarning [] = $warning;
		}
		$arrWarning = array_unique($arrWarning);
		
		if(count($arrWarning) > 0)
			return implode( ", " , $arrWarning);
		else
			return null;
		
	}
}
?>