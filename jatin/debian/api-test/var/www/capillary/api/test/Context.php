<?php namespace Api\UnitTest;

class InvalidContextException extends \Exception
{	
}

class Context
{
	private $namespace;
	private static $contextsArray = array();
	
	public function __construct($namespace = '')
	{
		$this->namespace = $namespace;
	}

    /**
    *
    * @return string namespace
    **/	
	public function getNamespace()
	{
		return $this->namespace;
	}
	
	/**
	 * 
	 * @param string $xpath
     * @param boolean $asArray, if value is returned as an array
	 * @return value of entity or context object
     * @throws InvalidContextException
	 */
	public function get($xpath, $asArray = false)
	{
                try
                {
                    $ret = $this->getSubArrayByXPath($xpath);
                }
                catch(\Api\UnitTest\InvalidContextException $e)
                {
                    return false;
                }
                
		
		if(is_array($ret) && ! $asArray)
			return new Context($this->namespace . "/" . $xpath);
		else
			return $ret;
	}
	
    
    /**
    *
    * @param string $xpath, xpath of entity to be set
    * @param mixed $value, value to be set 
    **/
	public function set($xpath, $value)
	{
		$keyArray = explode('/', $xpath);
		$key = array_pop(&$keyArray);
		$xpath = implode('/', $keyArray);
		$ret = &$this->getSubArrayByXPath($xpath, true);
		$ret[$key] = $value;
	}

    /**
    *
    * @param string $xpath, xpath to subarray
    * @param boolean $createMissing, create missing entities
    * @return array, the subarray requested
    * @throws InvalidContextException
    **/
	private function &getSubArrayByXPath($xpath, $createMissing = false)
	{
		$xpath = $this->namespace . '/' . $xpath;
        $xpath = trim($xpath, '/');
		$keys = explode('/', $xpath);
		$arr = &Context::$contextsArray;
		$keys = explode('/', $xpath);
		foreach($keys as $k)
		{
			if(isset($arr[$k]))
				$arr = &$arr[$k];
			else if($createMissing)
			{
				$arr[$k] = array();
				$arr = &$arr[$k];
			}
			else
				throw new InvalidContextException();	
		}
		return $arr;
	}
}
?>
