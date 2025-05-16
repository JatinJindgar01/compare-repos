<?php
include_once ("models/BaseInteraction.php");

class SmsInteraction extends BaseInteraction
{
	private $sender;
	private $receiver;
	private $message;
	private $sent_time;
	private $received_time;
	private $delivered_time;
	private $status;
	
// 	CONST CACHE_KEY_PREFIX_USER_ID = "SMS_INTERACTION_USER_ID#";
// 	CONST CACHE_KEY_PREFIX_MOBILE = "SMS_INTERACTION_MOBILE#";
// 	CONST CACHE_KEY_PREFIX_ID = "SMS_INTERACTION_MESSAGE_ID#";
	
	public function __construct($current_org_id)
	{
		parent::__construct($current_org_id);
	}
	
	public static function setIterableMembers()
	{
		$local_members = array(
				"receiver",
				"sender",
				"message",
				"sent_time",
				"received_time",
				"delivered_time",
				"status"
		);
		
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
		
	}
	
	

	public function getSender()
	{
	    return $this->sender;
	}

	public function setSender($sender)
	{
	    $this->sender = $sender;
	}

	public function getReceiver()
	{
	    return $this->receiver;
	}

	public function setReceiver($receiver)
	{
	    $this->receiver = $receiver;
	}

	public function getMessage()
	{
	    return $this->message;
	}

	public function setMessage($message)
	{
	    $this->message = $message;
	}

	public function getSentTime()
	{
	    return $this->sent_time;
	}

	public function setSentTime($sent_time)
	{
	    $this->sent_time = $sent_time;
	}

	public function getReceivedTime()
	{
	    return $this->received_time;
	}

	public function setReceivedTime($received_time)
	{
	    $this->received_time = $received_time;
	}

	public function getDeliveredTime()
	{
	    return $this->delivered_time;
	}

	public function setDeliveredTime($delivered_time)
	{
	    $this->delivered_time = $delivered_time;
	}

	public function getStatus()
	{
	    return $this->status;
	}

	public function setStatus($status)
	{
	    $this->status = $status;
	}
	
	public static function loadById($org_id, $id)
	{
		$filters = new InteractionLoadFilters();
		$filters->id = $id;
		$objs = self::loadAll($org_id, $filters, 1);
		return count($objs) > 0 ? $objs[0] : NULL;
	}
	
	public static function loadByReceiver($org_id, $mobile)
	{
		$filters = new InteractionLoadFilters();
		$filters->mobile = $mobile;
		$objs = self::loadAll($org_id, $filters);
		
		return $objs;
	}
	
	public static function loadAll($org_id, $filters = null)
	{
		global $logger;
		
		include_once 'thrift/nsadmin.php';
		$nsadmin = new NSAdminThriftClient();
		
		if(isset($filters) && !($filters instanceof InteractionLoadFilters))
		{
			throw new ApiInteractionException(ApiInteractionException::FILTER_INVALID_OBJECT_PASSED);
		}
		
		$mobile = NULL; $messages = NULL;
		if($filters->mobile)
		{
			$mobile = $filters->mobile;
		}
		if($filters->user_id)
		{
			//this will return multiple customer
			$customers = LoyaltyCustomer::loadById($org_id, $filters->user_id);
			$mobile = $customers->getMobile();
		}
		
		if($mobile)
		{
			$logger->debug("Loading based on mobile");
			$messages = $nsadmin->getMessagesByReceiver($org_id, $mobile);
		}
		
		else if($filters->id)
		{
			$logger->debug("Loading based on mobile");
			$messages = $nsadmin->getMessagesById($ids);
		}
		$objs = array();
		foreach ($messages AS $message)
		{
			$obj = self::fromMessage($org_id, $message);
			$objs[] = $obj;
		}
		return $objs;
	}
	
	public static function fromMessage( $org_id, $message )
	{
		$obj = new EmailInteraction($org_id);
	
		$obj->setId($message->messageId);
		$obj->setMessage($message->message);
		$obj->setReceiver($message->receiver);
		$obj->setSender($message->sender);
		$obj->setSentTime(date("Y-m-d h:i:s",$message->sentTimestamp/1000));
		$obj->setReceivedTime(date("Y-m-d h:i:s",$message->receivedTimestamp/1000));
		$obj->setDeliveredTime(date("Y-m-d h:i:s",$message->deliveredTimestamp/1000));
		$obj->setStatus($message->status);
	
		return $obj;
	}
}

?>
