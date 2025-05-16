<?php

include_once 'apiController/ApiBaseController.php';
include_once "apiHelper/Errors.php";
//TODO: referes to cheetah
include_once "helper/Util.php";
include_once "apiModel/class.ApiCommunicationTemplateModelExtension.php";

class ApiCommunicationsController extends ApiBaseController
{
	private $sender_org_id;
	private $db;
    private $nsadmin;

	public function __construct()
	{
		parent::__construct();

		$this->db = new Dbase("nsadmin");
	}

	/**
	 *
	 * @param unknown_type $to - array of Strings that contains mobile number as each string.
	 * @param unknown_type $message
	 * @param unknown_type $sender_org_id
	 * @param unknown_type $sms_type - Type of the SMS . Use MESSAGE_% definitions -> 0 - Personalized, 1-Priority, 2 - Bulk SMS
	 * @param boolean $truncate - should be message be truncated
	 * @param unknown_type $scheduled_time -
	 * @param unknown_type $override_gateway - Set to override the default gateway selection logic of nsadmin
	 * @param unknown_type $is_immediate - set to select the sms immediately irrespective of the time
	 * @param unknown_type $tags
	 * @return array of the
	 */
	public function sendSms($to, $message, $sender_org_id, $sms_type = 0, $truncate = false, $scheduled_time='', $override_gateway = false, $is_immediate = false, $tags = array(), $user_id=-1)
	{
		$success = false;
		$nsadmin_id = -1;


		$nsadmin_id = Util::sendSms($to, $message, $sender_org_id, $sms_type, $truncate, $scheduled_time, $override_gateway, $is_immediate, $tags, $user_id, -1, 'GENERAL');

		if($nsadmin_id > 0)
			$success = true;

		if(!$success)
			throw new Exception("ERR_SMS_SEND_FAIL");

		return $nsadmin_id;
	}

	/**
	 *
	 * @param array $to - The recepient email address
	 * @param unknown_type $message
	 * @param unknown_type $body
	 * @param unknown_type $sender_org_id - OrgID of the sender org. Their credits are reduced and SENDER ID is used
	 * @param array $cc
	 * @param unknown_type $email_type - Type of the EMAIL . Use MESSAGE_% definitions -> 0 - Personalized, 1-Priority, 2 - Bulk Email
	 * @param unknown_type $attached_file_id
	 * @param unknown_type $tags
	 * @param unknown_type $schdeuled_time
	 * @param unknown_type $html_decode
	 *
	 * @return nsadmin id of the sent mail.
	 */
	/*public function sendEmail($to, $message, $body, $sender_org_id, $cc = null, $email_type = 0, $attached_file_id = -1, $tags = array(), $schdeuled_time = 0, $html_decode = true)
	{
		$success = false;
		$message = "ERR_EMAIL_SEND_SUCCESS";

		$nsadmin_id  = Util::sendEmail($to, $message, $body, $sender_org_id, $cc, $email_type, $attached_file_id , $tags, $schdeuled_time , $html_decode);
		$success = $nsadmin_id > 0 ? true: false;

		if(! $success)
			throw new Exception("ERR_EMAIL_SEND_FAIL");

		return $nsadmin_id;
	}*/

	public function sendEmail($to, $message, $body, $sender_org_id, $cc = null, $email_type = 0, &$attachment = array(), $tags = array(), $schdeuled_time = 0, $html_decode = true, $user_id = -1)
	{
				$success = false;

			//saving attachment first
			if(isset($attachment) && is_array($attachment))
			{
				$this->logger->debug("Decoding of the attachment from base64");
				$decoded_data = base64_decode($attachment['file_data']);

				if(!$decoded_data)
					$this->logger->error("Decoding from Base64 failed: skiping saving of that file");
				else
				{
					$this->logger->debug("Done Decoding from base64");
					$attached_file_id = $this->saveEmailAttachment($attachment['file_name'], $attachment['file_type'], $decoded_data);
				}
			}
		$nsadmin_id  = Util::sendEmail($to, $message, $body, $sender_org_id, $cc, $email_type, $attached_file_id , $tags, $schdeuled_time , $html_decode, $user_id, -1, 'GENERAL');
		$success = $nsadmin_id > 0 ? true: false;

		if(! $success)
			throw new Exception("ERR_EMAIL_SEND_FAIL");

		return $nsadmin_id;
	}

	/**
	 *
	 * @param unknown_type $id - nsadmin id of the Email that has been already sent.
	 * @return returns array of the email hash.
	 */
	public function getEmailInfo($id)
	{
		$email_hash = array();

//		$sql = "SELECT * FROM `nsadmin`.`messages` WHERE
//				`id` = $id AND
//				`sending_org_id` = $this->org_id AND
//				`message_class` = 'EMAIL'";
//
//		$result = $this->db->query_firstrow($sql);
        $ids = array();
        array_push($ids, $id);
        if($this->nsadmin === null)
            $this->nsadmin = new NSAdminThriftClient();
        $result = $this->nsadmin->getMessagesById($ids);

		if( isset($result) && count($result) >= 0 )
		{
            if($result[0]->messageClass == 'EMAIL'){
			    $email_hash['id'] = $id;
			    $email_hash['to'] = $result[0]->receiver;
			    $email_hash['cc'] = $result[0]->ccList;
			    $email_hash['status'] = $result[0]->status;
                $email_hash['subject'] = $result[0]->message;
                $email_hash['description'] = $result[0]->body;
			    $email_hash['sent_time'] = date('d-m-Y H:i:s::T', $result[0]->sentTimestamp/1000);
            } else
                throw new Exception("ERR_EMAIL_NOT_FOUND");
		}
		else
			throw new Exception("ERR_EMAIL_NOT_FOUND");

		return $email_hash;
	}

    public function getSmsInfo($id)
    {
        $sms_hash = array();

//        $sql = "SELECT `id`, `receiver`, `status`, `sent_time`, `body`, `sender` FROM `nsadmin`.`messages` WHERE
//					`id` = $id AND
//					`sending_org_id` = $this->org_id AND
//					`message_class` = 'SMS'";
//        $result = $this->db->query_firstrow($sql);

        $ids = array();
        array_push($ids, $id);
        if($this->nsadmin === null)
            $this->nsadmin = new NSAdminThriftClient();
        $result = $this->nsadmin->getMessagesById($ids);

        if( isset($result) && count($result) >= 0 )
        {
            if($result[0]->messageClass == 'SMS'){
                $sms_hash['id'] = $id;
                $sms_hash['to'] = $result[0]->receiver;
                $sms_hash['status'] = $result[0]->status;
                $sms_hash['sent_time'] = date('d-m-Y H:i:s::T', $result[0]->sentTimestamp/1000);
                $sms_hash['message'] = $result[0]->message;
                $sms_hash['sender'] = $result[0]->sender;
            } else
                throw new Exception("ERR_SMS_NOT_FOUND");
        }
        else
            throw new Exception("ERR_SMS_NOT_FOUND");

        return $sms_hash;
    }

	/**
	 *
	 * @param unknown_type $file_name
	 * @param unknown_type $file_type
	 * @param unknown_type $data - actual data of the file
	 * 				- if data is in base64, then it should be in decoded from base64. TODO: ask if the data needs to be in base64 format or not?
	 * @return file_id - File Id of the table.
	 */
	public function saveEmailAttachment($file_name, $file_type, &$data)
	{
		$stored_files = new StoredFiles($this->currentorg);

		$file_tag = "Email Attachment added by Communication Resource";
		$created_by = -1;
		if(isset($this->currentuser) && is_object($this->currentuser))
			$created_by = $this->currentuser->user_id;

		$this->logger->info("Saving the Email Attachment: [FileName: $file_name, FileType: $file_type]");
		return $stored_files->storeFile($file_tag, $data, $file_name, $file_type, $created_by );
	}

	public function addTemplate($type, $title, $subject, $body, $is_editable = 1)
	{
		$type = strtoupper($type);
		if($type != 'SMS' && $type != 'EMAIL')
			throw new Exception('ERR_INVALID_TEMPLATE_TYPE');

		$last_updated_by = $this->currentuser->user_id;
		$last_updated_on = Util::getCurrentTimeForStore($this->currentuser->user_id);

		$communicationTemplateModel = new ApiCommunicationTemplateModelExtension();
		$communicationTemplateModel->setType($type);
		$communicationTemplateModel->setTitle($title);
		if($type == 'EMAIL')
			$communicationTemplateModel->setSubject($subject);
		$communicationTemplateModel->setBody($body);
		$communicationTemplateModel->setIsEditable($is_editable);
		//setting date and some other thigns.

		$store_controller = new ApiStoreController();
		$store_id = $store_controller->getBaseStoreId();

		$communicationTemplateModel->setOrgId($this->org_id);
		$communicationTemplateModel->setStoreId($store_id);
		$communicationTemplateModel->setLastUpdatedBy($last_updated_by);
		$communicationTemplateModel->setLastUpdatedOn($last_updated_on);

		$id = $communicationTemplateModel->insert();
		if( $id <= 0 )
			throw new Exception('ERR_ADD_TEMPLATE_FAILED');

		return $communicationTemplateModel->getHash();
	}

	/**
	 * updates Template Information, template type can't be changed
	 * @param unknown_type $id
	 * @param unknown_type $title
	 * @param unknown_type $subject
	 * @param unknown_type $body
	 * @throws Exception
	 */
	public function updateTemplate($id, $title, $subject, $body, $is_editable = 1)
	{
		$type = strtoupper($type);
		$id = (integer) $id;

		if($id <= 0)
			throw new Exception('ERR_INVALID_TEMPLATE_ID');

		$last_updated_by = $this->currentuser->user_id;
		$last_updated_on = Util::getCurrentTimeForStore($this->currentuser->user_id);

		$communicationTemplateModel = new ApiCommunicationTemplateModelExtension();
		$communicationTemplateModel->load($id);

		if($id != $communicationTemplateModel->getId() ||
				$this->org_id != $communicationTemplateModel->getOrgId())
			throw new Exception("ERR_INVALID_TEMPLATE_ID");
		if(!empty($title))
			$communicationTemplateModel->setTitle($title);
		if($communicationTemplateModel->getType() == 'EMAIL' && !empty($subject))
			$communicationTemplateModel->setSubject($subject);
		if(!empty($body))
			$communicationTemplateModel->setBody($body);
		if(!empty($is_editable))
			$communicationTemplateModel->setIsEditable($is_editable);
		//setting date and some other thigns.
		$communicationTemplateModel->setLastUpdatedBy($last_updated_by);
		$communicationTemplateModel->setLastUpdatedOn($last_updated_on);

		$new_id = $communicationTemplateModel->update($id);
		if( $new_id <= 0 )
			throw new Exception('ERR_UPDATE_TEMPLATE_FAILED');

		return $communicationTemplateModel->getHash();
	}

	public function getTemplates( $type = '')
	{
		$communicationTemplateModel = new ApiCommunicationTemplateModelExtension();
		return $communicationTemplateModel->getAllTemplates($this->org_id, $type);
	}
}
?>