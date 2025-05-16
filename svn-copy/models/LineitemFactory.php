<?php

define ("API_LINEITEM_TYPE_LOYALTY", 		1);
define ("API_LINEITEM_TYPE_RETURN", 		2);
define ("API_LINEITEM_TYPE_NOT_INTERESTED", 3);

/**
 * @author cj
 *
 * The factory class for the line item
 */
class LineitemFactory{

	/**
	 * The function returns an instance of the requested object 
	 *
	 * @param string $type - the type of line item that need to returned
	 * @param int $org_id - the linked org id 
	 * @return string
	 */
	public static function getLineitemClass($type, $org_id){
		
		global $logger;
		$logger->debug("Requested line item type is " . $type);
		
		switch ($type){
			case API_LINEITEM_TYPE_LOYALTY:
				include_once 'models/LoyaltyLineitem.php';
				$classname =  "LoyaltyLineitem";
		
			case API_LINEITEM_TYPE_RETURN:
				include_once 'models/ReturnedLineitem.php';
				$classname = "ReturnedLineitem";
		
			case API_LINEITEM_TYPE_NOT_INTERESTED:
				include_once 'models/NotInterestedLineitem.php';
				$classname = "NotInterestedLineitem";
					
			default:
				throw new ApiLineitemException(ApiLineitemException::INVALID_LINE_ITEM_TYPE);
		}
		
		return new $classname($org_id);
	}
}