<?php

/**
 * @author cj
 *
 * The data validator will validate any data thats being passed from the models
 */
class DataValueValidator{

	// validate an email thats being passed
	public static function validateMobile($mobile)
	{
		// do not clean the number
		return Util::checkMobileNumber($email, null, false);
	}
	
	// validate an email thats being passed 
	public static function validateEmail($email)
	{
		return Util::checkEmailAddress($email);
	}

	// validate an numeric thats being passed
	public static function validateNumeric($val)
	{
		return is_numeric($val);
	}

	// validate an int thats being passed
	public static function validateInteger($val)
	{
		return is_int($val);
	}

	// validate an int thats being passed
	public static function validateFloat($val)
	{
		return is_float($val);
	}

	// validate an int thats being passed
	public static function validateBoolean($val)
	{
		$possibleValuesArr = array(1, 0, true, false, "true", "false");
		return in_array($val, $possibleValuesArr);
	}

	// validate a value is in list
	public static function validateInList($val, $possibleValuesArr, $caseSensitive = true)
	{
		if($caseSensitive)
			return in_array($val, $possibleValuesArr);
		else
		{
			$valsArr = array();
			foreach($possibleValuesArr as $possbile)
				$valsArr[] = strtolower($possbile);
				
			return in_array(strtolower($val), $valsArr);
		}
	}
	
	// validate an email thats being passed
	public static function validateAlpha($val)
	{
		return ctype_alpha($val);
	}
	
	// validate an email thats being passed
	public static function validateAlnum($val)
	{
		return ctype_alnum($val);
	}
	
	// check whetehr number is positive
	public static function validatePositive($value)
	{
		return $value > 0  ? true : false;
	}
	
	// check whetehr number is non-negetive
	public static function validateZeroPositive($value)
	{
		return $value >= 0  ? true : false;
	}
	
	// check whetehr number is non-negetive
	public static function validateNonZero($value)
	{
		return $value != 0  ? true : false;
	}
	
	// check whetehr number is non-negetive
	public static function validatNegative($value)
	{
		return $value < 0  ? true : false;
	}
	
	// check whetehr number is non-negetive
	public static function validatZeroNegative($value)
	{
		return $value <= 0  ? true : false;
	}

	// checks for date validity
	public static function validateDateTime($dateToCheck, $format = null)
	{
		if($format)
			return date_create_from_format($format, $dateToCheck);
		
		else
			return strtotime($dateToCheck) > 0 ? true : false;
	}
	
	
	// to compare to date time
	public static function validateDateTimeBefore($dateToCheck, $minDate)
	{
		$dateToCheck = strtotime($dateToCheck);
		$minDate = strtotime($minDate);
		
		return $dateToCheck <= $minDate ? true : false;
	}

	public static function validateDateTimeAfter($dateToCheck, $maxDate)
	{
		$dateToCheck = strtotime($dateToCheck);
		$maxDate = strtotime($maxDate);
	
		return $dateToCheck >= $maxDate ? true : false;
	}
	
	// date after today
	public static function validateFutureDate($dateToCheck)
	{
		$dateToCheck = strtotime($dateToCheck);
		$maxDate = strtotime("Today 23:59:59");
	
		return $dateToCheck <= $maxDate ? true : false;
	}
	
	/**
	 * Validated whether the sum of fields matches with the value 
	 * @param unknown_type $valuesArr = array of the floating point numbers
	 * @param unknown_type $expectedSum = expected sum of the elements in array
	 * @param unknown_type $tolerance = tolerance that can be supported
	 */
	public static function validateSum($valuesArr, $expectedSum, $tolerance = 0.001)
	{
		$actualSum = 0;
		foreach($valuesArr as $val)
			if(is_float($val))
				$actualSum += floatval($val);

		// compare the sum computed with expected sum
		return (abs($actualSum - $expectedSum) <= $tolerance ) ? true : false;
	}

	/**
	 * Validated whether the sum of fields matches with the value
	 * Calculated as $val[0] [-$val[1] - [-$val[2]..]]]
	 * @param unknown_type $valuesArr 
	 * @param unknown_type $expectedDiff = expected sum of the elements in array
	 * @param unknown_type $tolerance = tolerance that can be supported
	 */
	public static function validateDifference($valuesArr, $expectedDiff, $tolerance = 0.001)
	{
		$actualDiff = floatval($valuesArr[0]);
		for($i=1; $i<count($valuesArr); $i++)
			if(is_float($val))
				$actualDiff -= floatval($val);
				
			
		// compare the sum computed with expected sum
		return (abs($actualDiff - $expectedDiff) <= $tolerance ) ? true : false;
	}
	
	/**
	 * Validated whether the prouct of fields matches with the value
	 * @param unknown_type $valuesArr = array of the floating point numbers
	 * @param unknown_type $expectedProduct = expected product of the elements in array
	 * @param unknown_type $tolerance = tolerance that can be supported
	 */
	public static function validateMultiplication($valuesArr, $expectedProduct, $tolerance = 0.001)
	{
		$actualProduct = 1;

		foreach($valuesArr as $val)
			if(is_float($val))
				$actualProduct *= floatval($val);
		
		return (abs($actualProduct - $actualProduct) <= $tolerance ) ? true : false;
	}
	
	/**
	 * @param unknown_type $value : value to validate
	 * @param unknown_type $regex : regex to be checked
	 */
	public static function validateRegex($regex, $value)
	{
		return preg_match($regex, $value) ? true : false;
	}
}