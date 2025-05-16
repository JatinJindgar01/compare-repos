<?php
include_once ("models/BaseInteraction.php");

/**
 * @author cj
 * 
 * The class involves all the mail interactions
 * 
 *  @uses: The cache is not implemented for the class expecting the call to be less
 */ 
class EmailInteraction extends BaseInteraction
{
	private $receiver;
	private $sender;
	private $message;
	private $body;
	private $sent_time;
	private $received_time;
	private $delivered_time;
	private $attachments;
	private $cc_list;
	private $bcc_list;
	private $status;
	private $subject;
	private $description;
	
// 	CONST CACHE_KEY_PREFIX_USER_ID = "EMAIL_INTERACTION_USER_ID#";
// 	CONST CACHE_KEY_PREFIX_EMAIL = "EMAIL_INTERACTION_EMAIL#";
// 	CONST CACHE_KEY_PREFIX_ID = "EMAIL_INTERACTION_MESSAGE_ID#";
	
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
				"body",
				"sent_time",
				"received_time",
				"delivered_time",
				"attachments",
				"cc_list",
				"bcc_list",
				"status",
				"subject",
				"description"
		);
		parent::setIterableMembers();
		self::$iterableMembers = array_unique(array_merge(parent::$iterableMembers, $local_members));
		
	}
	
	public function getReceiver()
	{
	    return $this->receiver;
	}

	public function setReceiver($receiver)
	{
	    $this->receiver = $receiver;
	}

	public function getSender()
	{
	    return $this->sender;
	}

	public function setSender($sender)
	{
	    $this->sender = $sender;
	}

	public function getMessage()
	{
	    return $this->message;
	}

	public function setMessage($message)
	{
	    $this->message = $message;
	}

	public function getBody()
	{
	    return $this->body;
	}

	public function setBody($body)
	{
	    $this->body = $body;
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

	public function getAttachments()
	{
	    return $this->attachments;
	}

	public function setAttachments($attachments)
	{
	    $this->attachments = $attachments;
	}

	public function getCcList()
	{
	    return $this->cc_list;
	}

	public function setCcList($cc_list)
	{
	    $this->cc_list = $cc_list;
	}

	public function getBccList()
	{
	    return $this->bcc_list;
	}

	public function setBccList($bcc_list)
	{
	    $this->bcc_list = $bcc_list;
	}

	public function getStatus()
	{
	    return $this->status;
	}

	public function setStatus($status)
	{
	    $this->status = $status;
	}

	public function getSubject()
	{
		return $this->subject;
	}
	
	public function setSubject($subject)
	{
		$this->subject = $subject;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function setDescription($desc)
	{
		$this->description = $desc;
	}
	
	public static function loadById($org_id, $id)
	{
		$filters = new InteractionLoadFilters();
		$filters->id = $id;
		$objs = self::loadAll($org_id, $filters, 1);
		$obj = count($objs) > 0 ? $objs[0] : NULL;
		
		return $obj;
	}
	
	public static function loadByReceiver($org_id, $receiver_email)
	{
		$filters = new InteractionLoadFilters();
		$filters->email = $receiver_email;
		$objs = self::loadAll($org_id, $filters);
		return $objs;
	}
	
	public static function loadAll($org_id, $filters = null, $limit=100, $offset = 0)
	{
		global $logger;
		include_once 'thrift/nsadmin.php';
		$nsadmin = new NSAdminThriftClient();
		
		if(isset($filters) && !($filters instanceof InteractionLoadFilters))
		{
			throw new ApiInteractionException(ApiInteractionException::FILTER_INVALID_OBJECT_PASSED);
		}
		$email = NULL;
		$messages = NULL;
		
		if($filters->email)
		{
			$email = $filters->email;
		}
		
		else if($filters->user_id)
		{
			//this will return multiple customer
			$customers = LoyaltyCustomer::loadAllById($org_id, $filters->user_id);
			$email = $customers->getEmail();
		}
		
		if($email)
		{
			$logger->debug("trying to load by email");
			$messages = $nsadmin->getMessagesByReceiver($org_id, $email);
		}
		else if($filters->id)
		{
			$logger->debug("trying to load by id");
			$messages = $nsadmin->getMessagesById(array($filters->id));
		}

		$objs = array();$array = array();
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
		$obj->setBody($message->body);
		$obj->setSentTime(date("Y-m-d h:i:s",$message->sentTimestamp/1000));
		$obj->setReceivedTime(date("Y-m-d h:i:s",$message->receivedTimestamp/1000));
		$obj->setDeliveredTime(date("Y-m-d h:i:s",$message->deliveredTimestamp/1000));
		$obj->setAttachments($message->attachmentId);
		$obj->setCcList($message->ccList);
		$obj->setBccList($message->bccList);
		$obj->setStatus($message->status);
		$obj->setSubject($message->subject);
		$obj->setDescription($message->description);
		return $obj;
	}
}

?>
