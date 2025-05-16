<?php

//if(isset($transaction['type']) && strtolower(trim($transaction['type'])) == 'regular' )
//	$t = $transaction_controller->addBills($transaction);

include_once "helper/MongoDBUtil.php";
include_once "controller/ApiLoyalty.php";
include_once "model/loyalty.php";
include_once "apiHelper/LineItem.php";
//TODO: referes to cheetah
include_once "helper/Dbase.php";
include_once "apiController/ApiBaseController.php";
include_once "apiController/ApiEMFServiceController.php";
include_once "apiController/ApiPointsEngineServiceController.php";
include_once 'models/filters/CreditNoteFilters.php';
include_once 'models/CreditNote.php';
include_once 'models/filters/PaymentModeLoadFilters.php';
include_once 'models/PaymentModeDetails.php';
include_once 'models/LoyaltyTransaction.php';
include_once 'models/NotInterestedTransaction.php';
include_once 'models/ReturnedTransaction.php';
include_once 'models/inventory/InventoryMaster.php';
include_once 'models/filters/TransactionLoadFilters.php';
include_once 'apiHelper/Errors.php';
include_once 'models/currencyratio/SupportedCurrency.php';
include_once 'models/currencyratio/CurrencyConversion.php';
include_once 'models/currencyratio/CurrencyRatio.php';
include_once 'models/currencyratio/OrgCurrency.php';
include_once 'apiHelper/eventIngestion/EventIngestionHelper.php';

define('SECONDS_OF_A_DAY', 1 * 24 * 60 * 60);

/**
 * Copied code from loyalty module.
 * Making changes for transaction resource api
 * @author Suganya TS
 *
 */
class ApiTransactionController extends ApiBaseController
{

    var $loyaltyController;
    var $loyaltyModule;
    var $lm;
    private $cm;
    private $mlm;
    protected $currentorg;
    private $db;
    private $return_id_arr = array();

    /* elements of the transaction Object */
    //private $db; //-- Duplicate
    private $user;
    private $id;
    //private $org_id; // -- Present in ApiBaseController
    private $bill_number;
    private $points;
    private $ignore_points;
    private $notes;
    private $redeemed;
    private $date;
    private $bill_amount;
    private $entered_by;
    private $outlier_status;
    private $counter_id;
    private $bill_gross_amount;
    private $bill_discount;
    private $bill_type;
    private $return_type;//Return type's type
    private $customfields = array();
    private $lineitems = array();
    private $error_flag = false;
    private $status_code;
    private $payment_details;
    private $cashier_details;
    private $credit_note;
    private $new_customer; //is user new
    private $dvs_vouchers;
    private $not_interested_reason;
    private $referrerCode;
    private $paymentDetailsObjArr;
    private $loyalty_type;
    private $source;
    private $delivery_status;
    private $is_retro = false;
    /* elements of transaction object END */

    private $campaign_db_name = "campaigns";

    //creating new private member, will be used multiple times
    //while calling NewBillEvent of EMF
    //and while populating params for LoyaltyTransactionEvent
    private $visits_and_bills = array();

    private $return_bills_arr = array();

    public function __construct()
    {
        parent::__construct();
        //This is done to reduce the rewriting.
        $this->currentorg = $this->org_model;
        $this->db = new Dbase('users');

        $this->status_code = "ERR_LOYALTY_SUCCESS";
        $this->loyaltyModule = new LoyaltyModule();
        $this->loyaltyController = new LoyaltyController($this->loyaltyModule);

        $this->new_customer = false;

    }

    /* functions of transaction - Start */

    private function initTransactionElements($data = null)
    {

        if (empty($data) || $data == null) {
            $this->logger->error("Transaction: No data passed");
            throw new Exception('ERR_NO_RECORDS');
        }
        //no need of initializing second time
        //$this->db = new Dbase( 'users' );
        $this->logger->info("Transaction: Initialising values");

        $this->referrerCode = isset($data['referral_code']) ? $data['referral_code'] : "";

        $this->bill_type = strtolower($data['type']);
        $this->return_type = StringUtils::strlower($data['return_type']);

        if ($this->return_type == 'transaction' || $this->return_type == 'full') {
            $this->return_type = TYPE_RETURN_BILL_FULL;
        } else if ($this->return_type == 'amount') {
            $this->return_type = TYPE_RETURN_BILL_AMOUNT;
        } else if ($this->return_type == 'line_item') {
            $this->return_type = TYPE_RETURN_BILL_LINE_ITEM;
        } else {
            //TODO: please verify what should be default return type
            $this->logger->debug("Return type is not valid:($this->return_type), making return type as FULL");
            $this->return_type = TYPE_RETURN_BILL_FULL;
        }

        $this->outlier_status = TRANS_OUTLIER_STATUS_NORMAL;
        $this->bill_number = $data['transaction_number'];
        $this->logger->debug("TransactionController::initTransactionElement Transaction Number: " . $this->bill_number);
        $this->source = $data['source'];
        $this->logger->debug("TransactionController::initTransactionElement Transaction Source: " . $this->source);
        if (isset($data['not_interested_reason']))
            $this->not_interested_reason = $data['not_interested_reason'];
        $this->is_retro = $data["is_retro"] ? true : false;

        /* Date and time restriction begin */
        if (!isset($data['billing_time'])) {
            $data['billing_time'] = $data['returned_time'];
        }
        $this->date = $data['billing_time'] ? Util::deserializeFrom8601($data['billing_time']) : time();

        if (empty($this->date))
            $this->date = time();

        $this->logger->debug("Billing Time: $this->date");

        if ($this->date > (time() + (SECONDS_OF_A_DAY))) //1 day * 24 hour * 60 minutes * 60 secs
        {
            $this->logger->debug("$this->date Date is not Alowed(More than bill boundry)");
            throw new Exception("ERR_NO_TRANSACTION_DATE_MORE_THAN_BOUNDRY");
        }

        $cm = new ConfigManager();

        $min_date = $cm->getKey("CONF_MIN_BILLING_DATE");
        //if min billing date config not found, it will Deserialize the default date ("1995-01-01 00:00:00UTC")
        $min_date = $min_date ? Util::deserializeFrom8601($min_date) : Util::deserializeFrom8601("1995-01-01 00:00:00UTC");

        if ($this->date < $min_date) {
            $this->logger->debug("$this->date Date is not Alowed (Less than date boundry)");
            throw new Exception("ERR_NO_TRANSACTION_DATE_LESS_THAN_BOUNDRY");
        }

        /* Date and time restriction end */

        //$this->identifier_passed = true;
        $this->bill_amount = $data['amount']!=null?round($data['amount'],2):0; //(double)
        $this->bill_discount = $data['discount']!=null?round($data['discount'],2):0; // (double)
        $this->bill_gross_amount = $data['gross_amount']!=null?round($data['gross_amount'],2):0; // (integer)

        if ($this->bill_amount < 0) {
            $this->logger->error("bill amount is negative, Amount: $this->bill_amount");
            throw new Exception('ERR_LOYALTY_BILL_AMOUNT_NEGATIVE');
        }
        if ($this->bill_gross_amount < 0) {
            $this->logger->error("bill gross amount is negative, Gross Amount: $this->gross_bill_amount");
            throw new Exception('ERR_LOYALTY_BILL_GROSS_AMOUNT_NEGATIVE');
        }
        if ($this->bill_discount < 0) {
            $this->logger->error("bill discount is negative, discount: $this->bill_discount");
            throw new Exception('ERR_LOYALTY_BILL_DISCOUNT_NEGATIVE');
        }

        $this->notes = $data['notes'];
        $ignore_points = $data['ignore_points'];
        if (isset($ignore_points) && ((integer)$ignore_points == 1)) {
            $this->logger->info("TransactionController: Ignore points. Setting points to 0");
            $this->points = 0;
            $ignore_points = 1;

        } else
            $ignore_points = 0;

        $this->ignore_points = $ignore_points;

        global $currentorg, $currentuser;
        $this->entered_by = $currentuser->user_id;
        $this->loyalty_type = $currentuser->loyalty_type;
        $this->org_id = $currentorg->org_id;
        $this->lm = new ListenersMgr($currentorg);
        global $counter_id;
        $counter_id = isset($counter_id) ? $counter_id : -1;
        $line_items = $data['lineitems']['lineitem'];

        //to support line items of the new api
        if (empty($line_items) || count($line_items) <= 0) {
            $line_items = $data['line_items'];
            if (empty($line_items['line_item']) || isset($line_items['line_item'][0])) {
                $line_items = $data['line_items']['line_item'];
            }
        }

        $this->logger->debug("lineitems: " . print_r($line_items, true));

        $this->credit_note = $data['credit_note'];
        $this->cashier_details = $data['cashier_details'];

        if (!isset($this->cashier_details) || !is_array($this->cashier_details))
            $this->cashier_details = array();

        $this->setCustomFields($data);
        $this->setLineItems($line_items, $data);
        $this->setPaymentDetails($data);
        if (isset($data["currency_id"])) {
            try {
                $org_model = new OrganizationModelExtension($currentorg->org_id);
                $org_model->load($currentorg->org_id);
                $this->base_currency = SupportedCurrency::loadById($org_model->getBaseCurrency());
                $this->base_currency = $this->base_currency->toArray();
            } catch (Exception $e) {
                Util::addApiWarning("Base currency not set for org");
                $this->logger->debug("Base currency not found");
            }
            try {
                //OrgCurrency::loadBySupportedCurrencyId($currentorg->org_id, $data["currency_id"]);
                $this->transaction_currency = SupportedCurrency::loadById($data["currency_id"]);
                $this->transaction_currency = $this->transaction_currency->toArray();
            } catch (Exception $e) {
                Util::addApiWarning("Transaction currency not found");
                $this->logger->debug("No currency id passed in the request or currency not an org currency");
            }
        }

        $this->setDeliveryStatus($data['delivery_status']);
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setNewCustomer($flag)
    {
        $this->new_customer = $flag;
    }

    public function getNewCustomer()
    {
        return $this->new_customer;
    }

    public function getDeliveryStatus()
    {
        return $this->delivery_status;
    }

    public function setDeliveryStatus($deliveryStatus)
    {
        $this->delivery_status = $deliveryStatus;
    }

    public function saveTransaction()
    {

        try {
            //Acquire the lock for the bill number so that duplicate bill is not enetered
            $mem_cache_mgr = MemcacheMgr::getInstance();
            try {
                $mem_cache_bill_lock_key = $this->currentorg->org_id . "_" . $this->currentuser->user_id . "_" .
                    $this->user->user_id;
                $sendLockMail = 0;
                $mem_cache_bill_number_lock_key = $this->currentorg->org_id . "_" . $this->bill_number;
                while (!$mem_cache_mgr->acquireLock($mem_cache_bill_lock_key, true, 30)) {

                    $sendLockMail++;
                    $this->logger->debug("$mem_cache_bill_lock_key has already aquire lock on some
							other thread. Waiting for a sec");
                    if ($sendLockMail == 1) {
                        $mem_lock_org_id = $this->currentorg->org_id;
                        $mem_lock_store_id = $this->currentuser->user_id;
                        $mem_lock_store_name = $this->currentuser->username;
                        $sendEmailBody = " Duplicate Request For
						Org : $mem_lock_org_id
						Store : $mem_lock_store_id
						Store Name : $mem_lock_store_name
						bill number : $this->bill_number
						user id : " . $this->user->getUserId();

                    }
                    sleep(1);
                }
                //Acquire bill number lock
                while (!$mem_cache_mgr->acquireLock($mem_cache_bill_number_lock_key, true, 20)) {

                    $sendLockMail++;
                    $this->logger->debug("$mem_cache_bill_number_lock_key has already aquire lock on some
							other thread. Waiting for a sec");
                    if ($sendLockMail == 1) {
                        $mem_lock_org_id = $this->currentorg->org_id;
                        $mem_lock_store_id = $this->currentuser->user_id;
                        $mem_lock_store_name = $this->currentuser->username;
                        $sendEmailBody = " Duplicate Request For
						Org : $mem_lock_org_id
						Store : $mem_lock_store_id
						Store Name : $mem_lock_store_name
						bill number : $this->bill_number
						user id : " . $this->user->getUserId();

                        Util::sendEmail('nagios-alerts@dealhunt.in', 'DUPLICATE BILL REQUEST',
                            $sendEmailBody, $this->org_id);
                    }
                    sleep(1);
                }
                $sendLockMail = 0;

            } catch (Exception $e) {
                $this->logger->error("Mem Cache Not Running");
            }

            $this->logger->info("TransactionController: Saving bill");
            if (StringUtils::strlower($this->bill_type) == 'return') {
                $this->logger->debug("Adding transaction for user_id : " . $this->user->getUserId() . " Calling addReturnBill");
                $this->addReturnBill();
            } else if (StringUtils::strlower($this->bill_type) == 'regular') {
                $this->logger->debug("Adding transaction for user user_id : " . $this->user->getUserId() . " loyalty_id: " . $this->user->loyalty_id . " . Calling addRegularBill");
                $this->addRegularBill();
            } else {
                $this->error_flag = true;
                $this->status_code = 'ERR_INVALID_BILL_TYPE';
                $this->logger->error("TransactionController::saveTransaction => Invalid bill type");
                throw new Exception('ERR_INVALID_BILL_TYPE');
            }
            if ($this->transaction_currency && $this->base_currency)
                $this->addCurrencyRatio();
            try {
                $mem_cache_mgr->releaseLock($mem_cache_bill_lock_key);
                $mem_cache_mgr->releaseLock($mem_cache_bill_number_lock_key);
            } catch (Exception $e) {
            }
        } catch (Exception $e) {

            //release mem cache lock
            try {
                $mem_cache_mgr->releaseLock($mem_cache_bill_lock_key);
                $mem_cache_mgr->releaseLock($mem_cache_bill_number_lock_key);
            } catch (Exception $e) {
            }
            throw new Exception($e->getMessage());
        }
        //pushing customer to async->solr to update his purchase/loyalty details
        $this->pushCustomerToSolr($this->user);
    }

    public function update()
    {
        $bill_amount_update = $this->bill_amount != '' ? " bill_amount = '$this->bill_amount', " : "";
        $notes_update = $this->notes != '' ? "notes = CONCAT( '$this->notes', '\n', `notes` ), " : "";
        $date_update = $this->date != '' ? "`date` = '$this->date' , " : "";
        $entered_by_update = $this->entered_by != '' ? "entered_by = '$this->entered_by', " : "";

        if (Util::isPointsEngineActive()) {
            $this->logger->debug("Points engine is active. Setting points to 0 for loyalty_log_id $this->id, bill number: $this->bill_number");
            $this->points = 0;
        }

        //points is being updated last. someone might forget comma above
        if ($bill_amount_update . $notes_update . $date_update . $entered_by_update != "")
            $ret1 = $this->db->update("
					UPDATE loyalty_log
					SET
					$bill_amount_update
					$notes_update
					$date_update
					$entered_by_update
					points='$this->points'
					WHERE `bill_number` = '$this->bill_number' AND id = '$this->id'
					");

        return $ret1;
    }

    private function addRegularBill()
    {
        $this->logger->info("TransactionController: Adding regular bill");
        $error_flag = false;
        $user_id = $this->user->getUserId();

        //TODO : use this->calcpoints then lc's - PANKAJ
        $this->points = $this->loyaltyController->calculatePoints($this->user, $this->getHash());
        $this->logger->debug("Points calculated: $this->points");

        $this->logger->debug("Custom fields for bill: " . print_r($this->customfields, true));
        $this->validate();
        $this->validateIgnorePoints();

        //TODO: this condition should be removed after conformation
        $res = $this->insertBill();
        if (!($res > 0)) {
            //LOYALTY_BILL_ADDITION_FAILED
            $this->error_flag = true;
            $this->status_code = 'ERR_LOYALTY_BILL_ADDITION_FAILED';
            throw new Exception('ERR_LOYALTY_BILL_ADDITION_FAILED');
        } else {
            $this->id = $res;
            if ($this->outlier_status == TRANS_OUTLIER_STATUS_NORMAL)
                ApiLoyaltyTrackerModelExtension::incrementStoreCounterForDay("transactions", $this->org_id, $this->user_id);
            $this->logger->debug("Loyalty Log Id for new Bill : $this->id ");
        }
        $this->saveCustomFields();
        $this->saveBillPaymentDetails();

        if ($this->outlier_status != TRANS_OUTLIER_STATUS_OUTLIER && !$this->updateLoyaltyDetails()) {
            $this->error_flag = true;
            $this->status_code = 'ERR_LOYALTY_PROFILE_UPDATE_FAILED';
            throw new Exception('ERR_LOYALTY_PROFILE_UPDATE_FAILED');
        }

    }

    private function addReturnBill()
    {
        $this->logger->info("Transaction: adding return bill");
        $returned_time = Util::getMysqlDateTime($this->date);

        $returned_items = array();
        foreach ($this->lineitems as $li) {

            $this->logger->debug("Line Items found creating array of line items");
            array_push($returned_items,
                array(
                    'item_code' => $li->getItemCode(),
                    'qty' => $li->getQty(),
                    'rate' => $li->getRate(),

                    // adding the other fields
                    'value' => $li->getValue(),
                    'discount_value' => $li->getDiscountValue(),
                    'serial' => $li->getSerial(),
                    'amount' => $li->getAmount()
                )
            );
        }

        try {
            global $non_loyal;
            if ($this->user->loyalty_type == 'non_loyalty')
                $non_loyal = true;
            else
                $non_loyal = false;
            $outlier_status = TRANS_OUTLIER_STATUS_NORMAL;
            $success = $this->loyaltyController->addReturnBill($this->user->getUserId(), $this->bill_number,
                $this->credit_note, $this->bill_amount, $this->points, $returned_time, $ll_id, $returned_items,
                $this->return_type, $this->delivery_status, $this->notes, $this->id, $outlier_status, null, $non_loyal);

            if (!$ll_id) {
                Util::addApiWarning("The return transaction"
                    . ($lineitems["transaction_number"] ? " with number " . $lineitems["transaction_number"] : "")
                    . ($lineitems["transaction_date"] ? " on " . $lineitems["transaction_date"] : "") . " doesnot exists");
            }

            if ($success && $this->outlier_status == TRANS_OUTLIER_STATUS_NORMAL)
                ApiLoyaltyTrackerModelExtension::incrementStoreCounterForDay("return_transactions", $this->org_id, $this->user_id);
            //Check if manual return bill is enabled, then send out an email
            if ($this->currentorg->getConfigurationValue(CONF_LOYALTY_IS_RETURN_BILL_MANUAL_HANDLING_ENABLED, false)) {
                $this->logger->debug("CONF_LOYALTY_IS_RETURN_BILL_MANUAL_HANDLING_ENABLED is enabled, preparing to send the email");
                $this->loyaltyController->sendReturnBillEmail($this->user, $this->bill_number, $this->bill_amount, $returned_items);
            } else if ($outlier_status != TRANS_OUTLIER_STATUS_OUTLIER && $ll_id) {
                $this->logger->debug("TransactionController::addReturnBill Updating Loyalty Details");
                if (!$this->updateLoyaltyDetails()) {
                    //won't throw Exception here.
                    $this->error_flag = true;
                    $this->status_code = 'ERR_LOYALTY_PROFILE_UPDATE_FAILED';
                    throw new Exception('ERR_LOYALTY_PROFILE_UPDATE_FAILED');
                }
            }

            //populate the supplied data
            $params = array();
            $params['user_id'] = $this->user->user_id;
            $params['entered_by'] = $this->currentuser->user_id;
            $params['date'] = $returned_time;
            //For all the bill amount trackers .. store a negative amount
            $trackermgr = new TrackersMgr($this->currentorg);
            $trackermgr->addDataForAllBillAmountTrackers($params);
        } catch (Exception $e) {
            $this->logger->error("Error while adding Return Bill: " . $e->getMessage());
            $this->error_flag = true;
            $this->status_code = $e->getMessage();
            throw new Exception($this->status_code);
        }
    }

    public function addNotInterestedBill($transaction)
    {
        $mem_cache_mgr = MemcacheMgr::getInstance();
        $transaction_number = $transaction['transaction_number'];
        $mem_cache_bill_number_lock_key = $this->currentorg->org_id . "_" . $transaction_number;
        try {
            $sendLockMail = 0;
            while (!$mem_cache_mgr->acquireLock($mem_cache_bill_number_lock_key, true, 20)) {

                $sendLockMail++;
                $this->logger->debug("$mem_cache_bill_number_lock_key has already aquire lock on some
                                other thread. Waiting for a sec");
                if ($sendLockMail == 1) {
                    $mem_lock_org_id = $this->currentorg->org_id;
                    $mem_lock_store_id = $this->currentuser->user_id;
                    $mem_lock_store_name = $this->currentuser->username;
                    $sendEmailBody = " Duplicate Request For
                            Org : $mem_lock_org_id
                            Store : $mem_lock_store_id
                            Store Name : $mem_lock_store_name
                            bill number : $transaction_number";

                    Util::sendEmail('nagios-alerts@dealhunt.in', 'DUPLICATE BILL REQUEST',
                        $sendEmailBody, $this->org_id);
                }
                sleep(1);
            }

        } catch (Exception $e) {
            $this->logger->error("Mem Cache Not Running");
        }
        try {
            $this->logger->info("TransactionController: adding Not Interested Bill");

            $transaction['transaction_number'] = $transaction['number'];
            $this->initTransactionElements($transaction);

            $this->validate();

            $this->id = $this->insertNotInterestedBill();

            if ($this->id <= 0)
                throw new Exception('ERR_NOT_INTERESTED_ADD_FAIL');

            //ingesting this event as the transaction has been added now.
            global $currentorg;
            $transEventAttributes = array();
            $transEventAttributes["subtype"] = strtoupper($this->bill_type);
            $transEventAttributes["billClientId"] = $transaction['bill_client_id'];
            $transEventAttributes["transactionId"] = $this->id; //$transaction['transaction_id'];
            $transEventAttributes["amount"] = intval($transaction['amount']);
            $transEventAttributes["transactionNumber"] = $transaction['transaction_number'];
            $transEventAttributes["grossAmount"] = $transaction['gross_amount'];
            $transEventAttributes["basketSize"] = sizeof($transaction['line_items']);
            $transEventAttributes["entityId"] = intval($this->currentuser->user_id); // $transaction[""];

            $billingTime = strtotime($transaction['billing_time']);
            EventIngestionHelper::ingestEventAsynchronously(intval($currentorg->org_id), "transaction",
                "Transaction event from the Intouch PHP API's", $this->date , $transEventAttributes);

            if ($this->id) {

                if ($this->outlier_status == TRANS_OUTLIER_STATUS_NORMAL)
                    ApiLoyaltyTrackerModelExtension::incrementStoreCounterForDay("not_interested_transactions", $this->org_id, $this->user_id);
                //Add the line items
                foreach ($this->lineitems as $lineitem) {
                    $this->setLineitemOutlierStatus($lineitem);
                    $this->addNotInterestedBillLineItem($this->id, $lineitem);
                }
            }
            $this->saveBillPaymentDetails();
            if ($this->transaction_currency && $this->base_currency)
                $this->addCurrencyRatio();
            try {
                $mem_cache_mgr->releaseLock($mem_cache_bill_number_lock_key);
            } catch (Exception $e) {
                $this->logger->error("Could not release lock");
            }
        } catch (Exception $e) {
            $mem_cache_mgr->releaseLock($mem_cache_bill_number_lock_key);
            throw $e;
        }
        return $this;
    }

    public function addNotInterestedReturn($transaction)
    {
        $mem_cache_mgr = MemcacheMgr::getInstance();
        $transaction_number = $transaction['transaction_number'];
        $mem_cache_bill_number_lock_key = $this->currentorg->org_id . "_" . $transaction_number;
        try {
            $sendLockMail = 0;
            while (!$mem_cache_mgr->acquireLock($mem_cache_bill_number_lock_key, true, 20)) {

                $sendLockMail++;
                $this->logger->debug("$mem_cache_bill_number_lock_key has already aquire lock on some
                                other thread. Waiting for a sec");
                if ($sendLockMail == 1) {
                    $mem_lock_org_id = $this->currentorg->org_id;
                    $mem_lock_store_id = $this->currentuser->user_id;
                    $mem_lock_store_name = $this->currentuser->username;
                    $sendEmailBody = " Duplicate Request For
                            Org : $mem_lock_org_id
                            Store : $mem_lock_store_id
                            Store Name : $mem_lock_store_name
                            bill number : $transaction_number";

                    Util::sendEmail('nagios-alerts@dealhunt.in', 'DUPLICATE BILL REQUEST',
                        $sendEmailBody, $this->org_id);
                }
                sleep(1);
            }

        } catch (Exception $e) {
            $this->logger->error("Mem Cache Not Running");
        }
        try {
            $this->logger->info("TransactionController: adding Not Interested return Bill");

            $transaction['transaction_number'] = $transaction['number'];
            $this->initTransactionElements($transaction);

            //$this->validate();

            require_once "models/transactions/TransactionNotInterestedReturn.php";

            $transactionNotInterestedReturn = new TransactionNotInterestedReturn($this->currentorg);

            $this->id = $transactionNotInterestedReturn->save($transaction);

            if ($this->id <= 0)
                throw new Exception('ERR_NOT_INTERESTED_ADD_FAIL');

            //ingesting this event as the transaction has been added now.
            global $currentorg;
            $transEventAttributes = array();
            $transEventAttributes["subtype"] = strtoupper($this->bill_type);
            $transEventAttributes["billClientId"] = $transaction['bill_client_id'];
            $transEventAttributes["transactionId"] = $this->id; //$transaction['transaction_id'];
            $transEventAttributes["amount"] = intval($transaction['amount']);
            $transEventAttributes["transactionNumber"] = $transaction['transaction_number'];
            $transEventAttributes["grossAmount"] = $transaction['gross_amount'];
            $transEventAttributes["basketSize"] = sizeof($transaction['line_items']);
            $transEventAttributes["entityId"] = intval($this->currentuser->user_id); // $transaction[""];

            $billingTime = strtotime($transaction['billing_time']);
            EventIngestionHelper::ingestEventAsynchronously(intval($currentorg->org_id), "transaction",
                "Transaction event from the Intouch PHP API's", $this->date, $transEventAttributes);

            if ($this->id && (strtoupper($transaction['return_type']) != strtoupper(TYPE_RETURN_BILL_AMOUNT))) {

                require_once "models/transactions/TransactionNotInterestedReturnLineitem.php";
                $itemCodeMapping = $transactionNotInterestedReturn->getItemCodeToIdMapping();

                if (strtoupper($transaction["return_type"]) == strtoupper(TYPE_RETURN_BILL_FULL)) {
                    $this->logger->debug("FULL return type :: returning all items " . strtoupper($transaction["return_type"]) . " == " . strtoupper(TYPE_RETURN_BILL_FULL));

                    //Add the all line items
                    foreach ($itemCodeMapping as $lineItem) {


                        $lineitemObj = new TransactionNotInterestedReturnLineitem(
                            $this->currentorg,
                            $this->id,
                            $itemCodeMapping
                        );

                        $id = $lineitemObj->save($lineItem);

                    }


                } else {

                    //Add the line items
                    foreach ($transaction["line_items"]["line_item"] as $lineItem) {

                        $lineitemObj = new TransactionNotInterestedReturnLineitem(
                            $this->currentorg,
                            $this->id,
                            $itemCodeMapping
                        );

                        $id = $lineitemObj->save($lineItem);

                    }
                }
            }

            //why this
            /*$this->saveBillPaymentDetails();
            if ($this->transaction_currency && $this->base_currency)
                $this->addCurrencyRatio();*/
            try {
                $mem_cache_mgr->releaseLock($mem_cache_bill_number_lock_key);
            } catch (Exception $e) {
                $this->logger->error("Could not release lock");
            }
        } catch (Exception $e) {
            $mem_cache_mgr->releaseLock($mem_cache_bill_number_lock_key);
            throw $e;
        }
        return $this;
    }


    private function insertNotInterestedBill()
    {
        $safe_bill_number = Util::mysqlEscapeString($this->bill_number);
        $safe_not_interested_reason = Util::mysqlEscapeString($this->not_interested_reason);
        $safe_billing_time = Util::getMysqlDateTime($this->date);
        $org_id = $this->org_id;
        $bill_amount = $this->bill_amount;
        $store_id = $this->currentuser->user_id;
        $safeDeliveryStatus = Util::mysqlEscapeString($this->delivery_status);

        $sql = "INSERT INTO loyalty_not_interested_bills
					(org_id, bill_number, bill_amount, 
					reason, billing_time, entered_by, outlier_status) "
            . " VALUES ($org_id, '$safe_bill_number', '$bill_amount', 
					'$safe_not_interested_reason', '$safe_billing_time', '$store_id', '$this->outlier_status')";
        $not_interested_bill_id = $this->db->insert($sql);

        if (isset($not_interested_bill_id) && $not_interested_bill_id > 0) {
            if (!empty($safeDeliveryStatus)) {

                // Continue to insert into the `transaction_delivery_status` table
                $statusSql = "INSERT INTO `user_management`.`transaction_delivery_status` " .
                    "SET `transaction_id` = " . $not_interested_bill_id . ", " .
                    "`transaction_type` = 'NOT_INTERESTED', " .
                    "`delivery_status` = '" . $safeDeliveryStatus . "', " .
                    "`updated_by` = " . $store_id . " " .
                    "ON DUPLICATE KEY UPDATE " .
                    "`delivery_status` = '" . $safeDeliveryStatus . "', " .
                    "`updated_by` = " . $store_id;

                // Using Dbase -> update() instead of insert() to be able to run ON DUPLICATE KEY UPDATE
                $newDeliveryStatusId = $this->db->update($statusSql);
                $this->logger->debug('Transaction Not-interested: Delivery-status ID: ' . $newDeliveryStatusId);

                if (isset($newDeliveryStatusId) && $newDeliveryStatusId > 0) {

                    // Continue to insert into the `transaction_delivery_status_changelog` table
                    $statusLogSql = "INSERT INTO `user_management`.`transaction_delivery_status_changelog` " .
                        "SET `transaction_id` = " . $not_interested_bill_id . ", " .
                        "`transaction_type` = 'NOT_INTERESTED', " .
                        "`delivery_status` = '" . $safeDeliveryStatus . "', " .
                        "`updated_by` = " . $store_id;
                    $newDeliveryStatusLogId = $this->db->insert($statusLogSql);
                    $this->logger->debug('Transaction Not-interested: Delivery-status-changelog ID: ' . $newDeliveryStatusLogId);
                }
            }
        }

        return $not_interested_bill_id;
    }

    private function addNotInterestedBillLineItem($not_interested_bill_id, $lineitem)
    {
        $org_id = $this->currentorg->org_id;
        $store_id = $this->currentuser->user_id;
        $safe_serial = Util::mysqlEscapeString($lineitem->getSerial());
        $safe_item_code = Util::mysqlEscapeString($lineitem->getItemCode());
        $safe_description = Util::mysqlEscapeString($lineitem->getDescription());
        $rate = $lineitem->getRate();
        $qty = $lineitem->getQty();
        $value = $lineitem->getValue();
        $discount_value = $lineitem->getDiscountValue();
        $amount = $lineitem->getAmount();


        $sql = "INSERT INTO `loyalty_not_interested_bill_lineitems` 
					(`org_id`, `not_interested_bill_id`, 
					`serial`, `item_code`, `description`, 
					`rate`, `qty`, `value`, `discount_value`, `amount`, `store_id`, `outlier_status`) 
					VALUES ('$org_id', '$not_interested_bill_id', 
					'$safe_serial', '$safe_item_code', '$safe_description', 
					'$rate', '$qty', '$value', '$discount_value', '$amount', '$store_id', '" . $lineitem->getOutlierStatus() . "' ) ";
        $lineitem_id = $this->db->insert($sql);
        return $lineitem_id;
    }

    private function setPaymentDetails($transaction)
    {
        $this->logger->info("Transaction: Setting payment details");
        $payment_details = array();
        if (isset($transaction['payment_details']['payment'])
            && (isset($transaction['payment_details']['payment']['mode'])
                || isset($transaction['payment_details']['payment']['name']))
        ) {
            $payment = $transaction['payment_details']['payment'];
            $temp_payment = array('mode' => $payment['name'] ? $payment['name'] : $payment['mode'],
                'id' => $payment['id'] ? $payment['id'] : null,
                'amount' => $payment['value'],
                'notes' => $payment['notes']);
            $temp_payment['attributes'] = array();
            if (isset($payment['attributes']) && isset($payment['attributes']['attribute'])) {
                if (isset($payment['attributes']['attribute']['name'])) {
                    $temp_payment['attributes']['attribute'] = array($payment['attributes']['attribute']);
                } else
                    $temp_payment['attributes']['attribute'] = $payment['attributes']['attribute'];
            }
            array_push($payment_details, $temp_payment);
        } else {
            if (is_array($transaction['payment_details']['payment'])) {
                foreach ($transaction['payment_details']['payment'] as $key => $payment) {
                    $temp_payment = array('mode' => $payment['name'] ? $payment['name'] : $payment['mode'],
                        'amount' => $payment['value'],
                        'notes' => $payment['notes']);
                    $temp_payment['attributes'] = array();

                    if (isset($payment['attributes']) && isset($payment['attributes']['attribute'])) {
                        if (isset($payment['attributes']['attribute']['name'])) {
                            $temp_payment['attributes']['attribute'] =
                                array($payment['attributes']['attribute']);
                        } else
                            $temp_payment['attributes']['attribute'] =
                                $payment['attributes']['attribute'];
                    }

                    array_push($payment_details, $temp_payment);
                }
            }
        }
        $this->logger->debug("Transaction: Payment details = " . print_r($payment_details, true));
        $this->payment_details = $payment_details;
        $this->setPaymentDetailsObjArr();
    }

    //TODO : use this then loyalty controllers - PANKAJ
    private function calculatePoints()
    {
        $this->logger->info("Transaction: Calculating points");
        $cf = new ConfigManager();
        $use_slabs = $cf->getKey('CONF_LOYALTY_ENABLE_SLABS');
        if (!$use_slabs) {
            $this->logger->debug("Transaction: No slabs. Using direct percentage");
            //no slabs .. use direct percentage
            $percent = $cf->getKey('CONF_LOYALTY_RULES_CALCULATION_PERCENT');

        } else {

            //run using slabs
            $this->logger->debug("Transaction: Getting slab information");
            list($slab, $slab_number) = $this->user->getSlabInformation();

            if ($slab == false) {
                $this->logger->debug("Transaction: using default slab");
                $slablist = $cf->getKey('CONF_LOYALTY_SLAB_LIST');

                $this->logger->debug("SLABLIST: " . $slablist);

                $slablist = json_decode(slablist, true);

                $slab = $slablist[0];
            }

            //Check in the seasonal slabs before using the global values
            $percent = 0;
            if (!$this->loyaltyController->getSeasonalSlabPercentage($slab, $percent)) {
                $percentages = $cf->getKeyValueForOrg('CONF_LOYALTY_SLAB_POINTS_PERCENT', $this->org_id);
                $percent = $percentages[$slab];
            }
        }
        $this->logger->debug("Transaction CalculatePoints(): percentage: $percent slab: $slab, Percentages: " . print_r($percentages, true));
        //$this->logger->debug("Transaction CalculatePoints(): Condition [".print_r($percent > 0, true)."]");
        $points = 0;
        if ($percent > 0) {

            $points = $percent * $this->bill_amount / 100;
        }
        $this->logger->debug("Transaction: Calculated points $points");
        return round($points); //points are rounded
    }

    private function saveCustomFields()
    {
        $this->logger->info("Transaction: Saving custom fields");
        $cf = new CustomFields();
        if (count($this->customfields) > 0) {
            return $cf->addMultipleCustomFieldsForAssocId($this->id, $this->customfields, 'loyalty_transaction');
        } else
            return 0;
    }

    private function saveDeliveryStatus($transationId, $deliveryStatus, $updatedBy)
    {

        $this->logger->debug("Transaction :: Updating delivery_status to $deliveryStatus for ID: $transactionId");

        // Continue to insert into the `transaction_delivery_status` table
        $statusSql = "INSERT INTO `user_management`.`transaction_delivery_status` " .
            "SET `transaction_id` = " . $transationId . ", " .
            "`transaction_type` = 'REGULAR', " .
            "`delivery_status` = '" . $deliveryStatus . "', " .
            "`updated_by` = " . $updatedBy . " " .
            "ON DUPLICATE KEY UPDATE " .
            "`delivery_status` = '" . $deliveryStatus . "', " .
            "`updated_by` = " . $updatedBy;

        // Using Dbase -> update() instead of insert() to be able to run ON DUPLICATE KEY UPDATE
        $newDeliveryStatusId = $this->db->update($statusSql);
        $this->logger->debug('Transaction update :: Delivery-status ID: ' . $newDeliveryStatusId);

        if (isset($newDeliveryStatusId) && $newDeliveryStatusId > 0) {
            // Continue to insert into the `transaction_delivery_status_changelog` table
            $statusLogSql = "INSERT INTO `user_management`.`transaction_delivery_status_changelog` " .
                "SET `transaction_id` = " . $transationId . ", " .
                "`transaction_type` = 'REGULAR', " .
                "`delivery_status` = '" . $deliveryStatus . "', " .
                "`updated_by` = " . $updatedBy;
            $newDeliveryStatusLogId = $this->db->insert($statusLogSql);
            $this->logger->debug('Transaction update :: Delivery-status-changelog ID: ' . $newDeliveryStatusLogId);

        }

        return $newDeliveryStatusId;
    }

    private function setCustomFields($transaction)
    {
        $this->logger->debug("Transaction: Setting CustomFields");
        $cf = new CustomFields();
        $cf_data = array();

        if ($transaction['custom_fields']['field'] && $transaction['custom_fields']['field']['name']) {
            $cfd = $transaction['custom_fields']['field'];
            $cf_name = (string)$cfd['name'];
            $cf_value_json = $cfd['value'];
            array_push($cf_data, array('field_name' => $cf_name, 'field_value' => $cf_value_json));
        } else {
            if (is_array($transaction['custom_fields']['field'])) {
                foreach ($transaction['custom_fields']['field'] as $key => $cfd) {
                    $cf_name = (string)$cfd['name'];
                    $cf_value_json = $cfd['value'];
                    array_push($cf_data, array('field_name' => $cf_name, 'field_value' => $cf_value_json));
                }
            }
        }
        $this->logger->debug("Transaction: Setting custom fields value " . print_r($cf_data, true));
        $this->customfields = $cf_data;
    }

    public function getCustomFields()
    {
        return $this->customfields;
    }

    public function getCustomFieldsData()
    {
        $this->logger->info("Transaction: Getting CustomFieldsData");
        $cf = new CustomFields();
        $custom_fields_data = $cf->getCustomFieldValuesByAssocId($this->org_id, 'loyalty_transaction', $this->id);
        $this->logger->debug("Transaction: Custom Fields received = " . print_r($custom_fields_data, true));
        return $custom_fields_data;
    }

    private function validate()
    {
        $cf = new ConfigManager();

        $test_mode = $cf->getKey('CONF_CLIENT_TEST_MODE');
        if ($test_mode === 1) {
            $this->outlier_status = TRANS_OUTLIER_STATUS_TEST;
        }
        //TODO: reject invald bill amount
        if (!is_numeric($this->bill_amount) && ($this->bill_type != 'return' || !isset($this->bill_amount))) {
            throw new Exception('ERR_LOYALTY_INVALID_BILL_AMOUNT');
        }
        if ((isset($this->bill_gross_amount) && !is_numeric($this->bill_gross_amount))
            || (isset($this->bill_discount) && !is_numeric($this->bill_discount))
        ) {
            $this->bill_gross_amount = floatval($this->bill_gross_amount);
            $this->bill_discount = floatval($this->bill_discount);
            Util::addApiWarning("Invalid gross amount and/or discount amount");
        }

        $outlier_bill_start_text = $cf->getKey("MARK_BILLS_OUTLIER_STARTING_WITH");
        $store_ids = $cf->getKey('MARK_BILLS_OUTLIER_FROM_STORE');
        if (($this->bill_amount < $cf->getKey('CONF_LOYALTY_MIN_BILL_AMOUNT')
                || $this->bill_amount > $cf->getKey('CONF_LOYALTY_MAX_BILL_AMOUNT')
                || stripos($this->bill_number, $outlier_bill_start_text) === 0
                || in_array($this->entered_by, $store_ids)) || $this->bill_amount == 0
        ) {
            $this->logger->error("Transaction: Invalid bill amount or bill number or store (Setting status as Outlier)");
            Util::addApiWarning("Transaction is marked as outlier");
            $this->outlier_status = TRANS_OUTLIER_STATUS_OUTLIER;
        }

        //Added check for regular bill as validate() is being used to validate not interested bill as well
        if ($this->bill_type == 'regular') {
            $disallow_fraud_statuses = $cf->getKeyValueForOrg("CONF_FRAUD_STATUS_CHECK_BILLING", $this->org_id);
            $customer_fraud_status = $this->user->getFraudStatus();

            if (count($disallow_fraud_statuses) > 0 && StringUtils::strlength($customer_fraud_status) > 0
                && in_array($customer_fraud_status, $disallow_fraud_statuses)
            ) {
                $this->logger->error("Transaction: Fraud User");
                throw new Exception('ERR_LOYALTY_FRAUD_USER');
            }
        }

        $loyalty_bill_number_required = $cf->getKey('CONF_LOYALTY_IS_BILL_NUMBER_REQUIRED');

        $this->logger->debug("TransactionController::validate() BillNumberRequired: " . $loyalty_bill_number_required);
        $this->logger->debug("TransactionController::validate() BillNumberRequired Bill Number: " . $this->bill_number);

        if ($loyalty_bill_number_required && $this->bill_number == false) {
            $this->logger->error("Transaction: Valid Bill number required.");
            throw new Exception('ERR_LOYALTY_INVALID_BILL_NUMBER');
        }

        $bill_number_count = 0;

        $entered_by = $this->currentuser->user_id;

        $bill_unique = $cf->getKey('CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE');

        $bill_store_unique = $this->currentorg->get(CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_STORE) ? true : false;
        $bill_till_unique = $this->currentorg->get(CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_TILL) ? true : false;

        $trans_num_unique_tills_check = false;
        if ($bill_store_unique) {
            include_once 'apiController/ApiStoreController.php';
            $entity_controller = new ApiEntityController('TILL');
            $store = $entity_controller->getParentEntityByType($entered_by, 'STORE');
            $store_id = array_pop($store);
            $this->logger->debug("parent STORE for TILL ($entered_by) is $store_id");
            $store_controller = new ApiStoreController();
            $this->logger->info("Getting all TILLS of store $store_id");
            $tills = $store_controller->getStoreTerminalsByStoreId($store_id);
            $this->logger->debug("TILLS of store ($store_id) are (" . implode(",", $tills) . ")");
            $trans_num_unique_tills_check = array_merge(array($entered_by), $tills);
        } elseif ($bill_till_unique)
            $trans_num_unique_tills_check = array($entered_by);

        $this->logger->debug("CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_TILL: " . ($bill_till_unique ? "true" : "false"));
        $this->logger->debug("CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_STORE: " . ($bill_store_unique ? "true" : "false"));
        $this->logger->debug("CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE: $bill_unique");
        $this->logger->debug("Checking bill number uniqueness among tills: " . implode(",", $trans_num_unique_tills_check));

        $datetime = Util::getMysqlDateTime($this->date);
        $bill_number_count = $this->loyaltyController->getNumberOfBills($this->bill_number, $trans_num_unique_tills_check, false, true, false, $datetime);

        $this->logger->debug("TransactionController::validate() Bill Number: " . $this->bill_number);
        $this->logger->debug("TransactionController::validate() CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE: " . $bill_unique);
        $this->logger->debug("TransactionController::validate() Bill Number count: " . $bill_number_count);

        if ($this->bill_number != '' &&
            ($bill_unique &&
                $bill_number_count > 0)
        ) {
            $this->logger->error("Duplicate Bill, not adding it again");
            throw new Exception('ERR_LOYALTY_DUPLICATE_BILL_NUMBER');
        }
    }

    private function validateIgnorePoints()
    {
        $flag = false;
        $ignore_points_on_bill_number = $this->currentorg->getConfigurationValue(BILL_NUMBER_WISE_IGNORE_POINTS, false);
        if ($ignore_points_on_bill_number) {

            $starts_with = $this->currentorg->getConfigurationValue(IGNORE_POINTS_FOR_BILL_NUMBER_STARTS_WITH, 'test');
            $this->logger->debug('Starts With  :' . $starts_with);

            $starts_with_array = StringUtils::strexplode(',', $starts_with);
            foreach ($starts_with_array as $s) {
                $flag = Util::StringBeginsWith($this->bill_number, $s);
                $this->logger->info('Checking For:-> ' . $s . ' ,Bill_number :->' . $this->bill_number);
                if ($flag) {
                    $this->logger->debug('Flag Set For Tag  :' . $s);
                    break;
                }
            }
            $this->logger->info('Flag Set To Ignore  :' . $flag);

            if ($flag)
                $this->ignore_points = true;
        }

        $this->logger->info('Ignore  Points:' . $this->ignore_points);
        if ($this->ignore_points)
            $this->points = 0;
    }

    private function getTransactionByNumber($transaction_number, $user_id = null)
    {
        $safe_number = Util::mysqlEscapeString($transaction_number);
        $org_id = $this->currentorg->org_id;

        $user_filter = "";
        if ($user_id != null) {
            $user_id = intval($user_id);
            $user_filter = " AND user_id = $user_id ";
        }

        $sql = "SELECT * FROM `loyalty_log` 
					WHERE bill_number='$safe_number' 
					AND org_id=$org_id 
					$user_filter 
					ORDER BY date DESC";

        $result = $this->db->query_firstrow($sql);

        if (!$result || count($result) <= 0) {
            $this->logger->error("can't find transaction with number: '$safe_number'");
            throw new Exception("ERR_LOYALTY_TRANSACTION_NOT_FOUND_BY_NUMBER");
        }

        return $result;
    }

    private function getTransactionById($id, $user_id = null)
    {
        $id = intval($id);
        $org_id = $this->currentorg->org_id;

        $user_filter = "";
        if ($user_id != null) {
            $user_id = intval($user_id);
            $user_filter = " AND user_id = $user_id ";
        }

        $sql = "SELECT * FROM `loyalty_log` 
					WHERE id = $id 
					AND org_id = $org_id 
					$user_filter 
					ORDER BY date DESC";

        $result = $this->db->query_firstrow($sql);

        if (!$result || count($result) <= 0) {
            $this->logger->error("can't find transaction with id: '$id'");
            throw new Exception("ERR_LOYALTY_TRANSACTION_NOT_FOUND_BY_ID");
        }

        return $result;
    }

    public function setLineItems($line_items, $data = array())
    {
        $this->logger->debug("Transaction: Setting Line Items =" . print_r($line_items, true));

        if ($line_items && is_array($line_items)) {
            foreach ($line_items as $key => $li) {
                if (!(strtolower($li["type"]) == 'return' && strtolower($this->bill_type) == 'regular')) {

                    $this->validateLineitem($li);
                    $lineitem = new BillLineItem();
                    $lineitem->setOrgId($this->org_id);
                    $lineitem->setSerial((integer)$li['serial']);
                    $lineitem->setItemCode((string)$li['item_code']);
                    $lineitem->setDescription((string)$li['description']);
                    $lineitem->setRate(doubleval($li['rate']));
                    $lineitem->setQty(doubleval($li['qty']));
                    $lineitem->setValue(doubleval($li['value']));

                    $possibleTypesArr = array("regular", "return", "not_interested", "not_interested_return");
                    if ($this->bill_type == 'regular')
                        $lineitem->setType($li['type'] && in_array(strtolower($li['type']), $possibleTypesArr) ? $li['type'] : $this->bill_type);

                    if (!isset($li['discount_value']))
                        $li['discount_value'] = $li['discount'];
                    $lineitem->setDiscountValue(doubleval($li['discount_value']));
                    $lineitem->setAmount(doubleval($li['amount']));

                    if (is_array($li['attributes']))
                        $inventory_attributes = $li['attributes']['attribute'];
                    else
                        $inventory_attributes = array();
                    $this->logger->debug("Transaction: Inventory Attributes: " . print_r($inventory_attributes, true));
                    $inventory_info = array();
                    if (!empty($inventory_attributes)) {
                        if (isset($inventory_attributes['name'])) {
                            $inventory_info[$inventory_attributes['name']] = $inventory_attributes['value'];
                        } else {
                            foreach ($inventory_attributes as $ia) {
                                $attribute = (string)$ia['name'];
                                $value = (string)$ia['value'];
                                $inventory_info[$attribute] = $value;
                            }
                        }
                    }
                    $this->logger->debug("Transaction: Inventory_info: " . print_r($inventory_info, true));
                    $lineitem->setInventoryInfo($inventory_info);
                    array_push($this->lineitems, $lineitem);
                    //$this->addCurrencyRatio();
                } else {
                    $bill_identifier = $li["transaction_number"] . "##" . $li["transaction_date"];

                    //get the abs values
                    $li["qty"] = abs(doubleval($li['qty']));
                    $li["amount"] = abs(doubleval($li['amount']));

                    $this->validateLineitem($li);
                    $lineitem = new BillLineItem();
                    $lineitem->setOrgId($this->org_id);
                    $lineitem->setSerial((integer)$li['serial']);
                    $lineitem->setItemCode((string)$li['item_code']);
                    $lineitem->setDescription((string)$li['description']);
                    $lineitem->setRate(doubleval($li['rate']));
                    $lineitem->setQty(doubleval($li['qty']));
                    $lineitem->setValue(doubleval($li['value']));
                    if (!isset($li['discount_value']))
                        $li['discount_value'] = $li['discount_value'];
                    $lineitem->setDiscountValue(doubleval($li['discount_value']));
                    $lineitem->setAmount(doubleval($li['amount']));
                    $this->logger->debug("Transaction: Inventory Attributes: " . print_r($inventory_attributes, true));
                    $this->logger->debug("Transaction: Inventory_info: " . print_r($inventory_info, true));
                    $lineitem->setInventoryInfo($inventory_info);

                    // intialize if required
                    if (!isset($this->return_bills_arr[$bill_identifier]["line_items"]))
                        $this->return_bills_arr[$bill_identifier]["line_items"] = array();
                    // if already a line item is present
                    else if ($this->return_bills_arr[$bill_identifier]["return_type"] != $li["return_type"] && $this->return_bills_arr[$bill_identifier]["return_type"] == TYPE_RETURN_BILL_FULL)
                        throw new Exception(ERR_ALREADY_RETURNED_AND_NEW_TYPE_FULL_OLD_FULL);
                    else if ($this->return_bills_arr[$bill_identifier]["return_type"] != $li["return_type"] && $this->return_bills_arr[$bill_identifier]["return_type"] == TYPE_RETURN_BILL_AMOUNT)
                        throw new Exception(ERR_ALREADY_RETURNED_AND_NEW_TYPE_FULL_OLD_AMOUNT);
                    else if ($this->return_bills_arr[$bill_identifier]["return_type"] != $li["return_type"])
                        throw new Exception(ERR_ALREADY_RETURNED_AND_NEW_TYPE_FULL_OLD_LINEITEM);

                    array_push($this->return_bills_arr[$bill_identifier]["line_items"], $lineitem);

                    $this->return_bills_arr[$bill_identifier]["transaction_number"] = $li["transaction_number"];
                    $this->return_bills_arr[$bill_identifier]["transaction_date"] = $li["transaction_date"];
                    $this->return_bills_arr[$bill_identifier]["return_type"] = strtoupper($li["return_type"]);
                    if (isset($li["currency_id"]))
                        $this->return_bills_arr[$bill_identifier]["currency_id"] = $li["currency_id"];

                    //$this->return_bills_arr["amount"] = $li["return_type"];

                    //$this->return_bills_arr[$bill_identifier][] = $li;

                    // TODO: add the line items proceed from here.
                }
            }
        } else
            $this->logger->debug("No Line Items passed");
    }

    private function validateLineitem($li)
    {
        if (doubleval($li['amount']) < 0) {
            $this->logger->error("Amount is negative, for item " . $li['item_code']);
            throw new Exception('ERR_LOYALTY_LINEITEM_AMOUNT_NEGATIVE');
        }
        if (doubleval($li['value']) < 0) {
            $this->logger->error("Value is negative, for item " . $li['item_code']);
            throw new Exception('ERR_LOYALTY_LINEITEM_VALUE_NEGATIVE');
        }
        if (doubleval($li['rate']) < 0) {
            $this->logger->error("rate  is negative, for item " . $li['item_code']);
            throw new Exception('ERR_LOYALTY_LINEITEM_RATE_NEGATIVE');
        }
        if (doubleval($li['qty']) < 0) {
            $this->logger->error("quantity is negative, throwing Exception");
            throw new Exception('ERR_LOYALTY_LINEITEM_QTY_NEGATIVE');
        }
        if (doubleval($li['discount_value']) < 0 || doubleval($li['discount']) < 0) {
            $this->logger->error("quantity is negative, throwing Exception");
            throw new Exception('ERR_LOYALTY_LINEITEM_DISCOUNT_NEGATIVE');
        }

        if (isset($li['amount']) && !is_numeric($li['amount'])) {
            Util::addApiWarning("Invalid amount passed for " . $li["item_code"]);
            $li['amount'] = floatval($li['amount']);
        }

        if ((isset($li["value"]) && !is_numeric($li["value"]))
            || (isset($li["rate"]) && !is_numeric($li["rate"]))
            || (isset($li["qty"]) && !is_numeric($li["qty"]))
            || (isset($li["discount_value"]) && !is_numeric($li["discount_value"]))
            || (isset($li["discount"]) && !is_numeric($li["discount"]))
        ) {
            Util::addApiWarning("Invalid gross amount, rate, qty and/or discount passed for " . $li["item_code"]);
            $li['value'] = floatval($li['value']);
            $li['rate'] = floatval($li['rate']);
            $li['qty'] = floatval($li['qty']);
            $li['discount_value'] = floatval($li['discount_value']);
            $li['discount'] = floatval($li['discount']);
        }


    }


    // to check the outlier status of the bill
    private function setLineitemOutlierStatus(BillLineItem $li)
    {
        $configMgr = new ConfigManager();
        $min_bill_amount = $configMgr->getKey('CONF_LOYALTY_MIN_BILL_LINEITEM_AMOUNT');
        $max_bill_amount = $configMgr->getKey('CONF_LOYALTY_MAX_BILL_LINEITEM_AMOUNT');
        $outlier_skus = explode(",", $configMgr->getKey('CONF_OUTLIER_ITEM_SKU'));
        foreach ($outlier_skus as $k => $v)
            $outlier_skus[$k] = strtolower($v);

        if ($this->outlier_status != TRANS_OUTLIER_STATUS_NORMAL) {
            $li->setOutlierStatus('OUTLIER');
            $this->logger->debug("The transaction is outlier, so marking as outlier");
        } else if (in_array(strtolower($li->getItemCode()), $outlier_skus)) {
            $li->setOutlierStatus('OUTLIER');
            $this->logger->debug("The item code is outlieried");
        } else if ($li->getAmount() > $max_bill_amount || $li->getAmount() < $min_bill_amount) {
            $li->setOutlierStatus('OUTLIER');
            $this->logger->debug("The amount is not within threshold, so marking as outlier");
        } else if ($li->getAmount() == 0) {
            $li->setOutlierStatus('OUTLIER');
            $this->logger->debug("The amount is nzero, so marking as outlier");
        } else
            $li->setOutlierStatus('NORMAL');
    }

    public function setPoints($points)
    {
        $this->points = $points;
    }

    public function addLineItems()
    {
        $this->logger->info("Transaction: Adding multiple LineItems");

        if ($this->lineitems && count($this->lineitems) <= 0) {
            $this->logger->debug("AddLineItems: No line Items Passed");
            return;
        }

        $this->addMultipleLineItems();

        $this->createReturnInRegularBill();

        $this->loyaltyController->loadInventoryInfoForLineitems($this->lineitems);
        foreach ($this->lineitems as $li) {

            //add inventory
            $this->logger->info("Transaction: Inserting Inventory");
            $inventory_info = $li->getInventoryInfo();
            $this->logger->debug("Inventory Info: " . print_r($inventory_info, true));
            if (!isset($inventory_info) || !count($inventory_info) > 0)
                continue;
            $inventory_info_params = array();
            $inventory_info_params['rate'] = $li->getRate();
            $inventory_info_params['item_code'] = $li->getItemCode();

            $ret = $this->loyaltyController->addInventoryInformation($inventory_info_params, $inventory_info, false, false);
            if (!is_numeric($ret) && !is_array($ret)) {
                $this->logger->error("Transaction Controller: " . $ret);
            }
        }

        //$lineitem_reference_update_timer = new Timer("lineitem_reference_update_timer");
        //$lineitem_reference_update_timer->start();
        //$this->logger->info("Updating lineitem references for the bill");
        //$this->loyaltyController->updateLineItemReferencesForBill($this->id); // making this asynchronous by following call
        //$this->updateLineItemReference();
        //$this->logger->info("After Inventory References Bill : ".$this->id);
        //$lineitem_reference_update_timer->stop();

        //$time_taken_to_update_lineitems = $lineitem_reference_update_timer->getTotalElapsedTime();
        //unset($lineitem_reference_update_timer);
        //$this->logger->debug("Total Time taken to update the LineItem Reference for the bill is: $time_taken_to_update_lineitems");

        //TODO::
        // parse thru all line items and check the type to identify return
        // from line list, remove return type element has to be remove

        // and for each return line item, grouped by txn num, create a new transaction controller obj
        // addReturnTransaction


    }

    private function addReturnedLineItems()
    {
        $this->logger->debug("saving the bill details");

        // back up necessary fields
        foreach ($this->return_bills_arr as $lineitems) {
            $bill_amount = 0;
            foreach ($lineitems["line_items"] as $lineitem) {
                $bill_amount += $lineitem->getAmount();
            }
            //$this->credit_note = "";
            $this->logger->debug("Adding a return bill");

            $returned_time = Util::getMysqlDateTime($this->date);

            $returned_items = array();
            foreach ($lineitems["line_items"] as $li) {

                $this->logger->debug("Line Items found creating array of line items");
                array_push($returned_items, array(
                        'item_code' => $li->getItemCode(),
                        'qty' => $li->getQty(),
                        'rate' => $li->getRate(),
                        'value' => $li->getValue(),
                        'discount_value' => $li->getDiscountValue(),
                        'serial' => $li->getSerial(),
                        'amount' => $li->getAmount()
                    )
                );
            }

            try {
                $outlier_status = TRANS_OUTLIER_STATUS_NORMAL;
//				try {

                $delivery_status = 'RETURNED';
                $success = $this->loyaltyController->addReturnBill($this->user->getUserId(), $lineitems["transaction_number"],
                    "", $bill_amount, $points, $returned_time, $ll_id, $returned_items,
                    $lineitems["return_type"], $delivery_status, $lineitems["notes"], $return_id, $outlier_status, $lineitems["transaction_date"]);
                if (isset($lineitems["currency_id"]))
                    $this->addCurrencyRatioReturnLineItems($return_id, $bill_amount, $returned_time, $lineitems["currency_id"]);

                if (!$ll_id) {
                    Util::addApiWarning("The return transaction"
                        . ($lineitems["transaction_number"] ? " with number " . $lineitems["transaction_number"] : "")
                        . ($lineitems["transaction_date"] ? " on " . $lineitems["transaction_date"] : "") . " doesnot exists");
                }
// 				} catch (Exception $e) {
// 					$this->logger->debug("Adding bill has failed with message - ". $e->getMessage());
// 					Util::addApiWarning("Could not return the transaction with number ".$lineitems["transaction_number"]
// 						.($lineitems["transaction_date"] ? " on ".$lineitems["transaction_date"] : "") ." due to " . $e->getMessage());
// 				}

                if ($return_id > 0)
                    $this->return_id_arr[] = $return_id;

                //Check if manual return bill is enabled, then send out an email
                if ($this->currentorg->getConfigurationValue(CONF_LOYALTY_IS_RETURN_BILL_MANUAL_HANDLING_ENABLED, false)) {
                    $this->logger->debug("CONF_LOYALTY_IS_RETURN_BILL_MANUAL_HANDLING_ENABLED is enabled, preparing to send the email");
                    $this->loyaltyController->sendReturnBillEmail($this->user, $lineitems["transaction_number"], $bill_amount, $returned_items);
                } else if ($outlier_status != TRANS_OUTLIER_STATUS_OUTLIER && $ll_id) {
                    $this->logger->debug("TransactionController::addReturnBill Updating Loyalty Details");
                    $bill_type_temp = $this->bill_type;
                    $points_temp = $this->points;
                    $bill_amount_temp = $this->bill_amount;

                    $this->bill_type = 'return';
                    $this->points = $points;
                    $this->bill_amount = $bill_amount;
                    if (!$this->updateLoyaltyDetails()) {
                        $this->bill_type = $bill_type_temp;
                        $this->points = $points_temp;
                        $this->bill_amount = $bill_amount_temp;

                        //won't throw Exception here.
                        $this->error_flag = true;
                        $this->status_code = 'ERR_LOYALTY_PROFILE_UPDATE_FAILED';
                        throw new Exception('ERR_LOYALTY_PROFILE_UPDATE_FAILED');
                    }
                    $this->bill_type = $bill_type_temp;
                    $this->points = $points_temp;
                    $this->bill_amount = $bill_amount_temp;
                }

                //populate the supplied data
                $params = array();
                $params['user_id'] = $this->user->user_id;
                $params['entered_by'] = $this->currentuser->user_id;
                $params['date'] = $returned_time;
                //For all the bill amount trackers .. store a negative amount
                $trackermgr = new TrackersMgr($this->currentorg);
                $trackermgr->addDataForAllBillAmountTrackers($params);
            } catch (Exception $e) {
                $this->logger->error("Error while adding Return Bill: " . $e->getMessage());
                $this->error_flag = true;
                $this->status_code = $e->getMessage();
                #throw new Exception($this->status_code);
                Util::addApiWarning("Could not return the transaction "
                    . ($lineitems["transaction_number"] ? " with number " . $lineitems["transaction_number"] : "")
                    . ($lineitems["transaction_date"] ? " on " . $lineitems["transaction_date"] : "") . " due to " . ErrorMessage::$transaction[$e->getMessage()]);

                $org_id = $this->currentorg->org_id;
                $user_id = $this->user->user_id;
                $entered_by = $this->currentuser->user_id;
                $lineitemJson = json_encode($returned_items);
                $sql = " INSERT INTO returned_bills_failed_log 
						(org_id, user_id, entered_by, loyalty_log_id, parent_loyalty_log_id, reason, 
						 bill_number, date, type, lineitem_info)
							VALUES
						($org_id,  $user_id, $entered_by, '$ll_id', '$this->id', '" . ErrorMessage::$transaction[$e->getMessage()] . "', 
						'$lineitems[transaction_number]', '$lineitems[transaction_date]', '$lineitems[return_type]', '$lineitemJson'
						)";
                $this->db->insert($sql);
            }

        }
    }

    private function linkReturnTransactionWithLoyaltyLog()
    {
        $this->logger->debug("Link the return transaction with the parent loyalty_log id");
        if (count($this->return_id_arr) > 0 && $this->id > 0) {
            $sql = "UPDATE user_management.returned_bills 
					SET parent_loyalty_log_id = $this->id
					WHERE id in (" . implode(",", $this->return_id_arr) . ")";

            $ret = $this->db->update($sql);

            if (!$ret)
                Util::addApiWarning("Linking the return transaction with the loyalty log has failed");
        }
    }

    private function addMultipleLineItems()
    {
        if (count($this->lineitems) <= 0) {
            $this->logger->info("Transaction: No line items in the bill");
            return array();
        }
        $this->logger->debug("Transaction: Adding multiple line items for bill " . $this->id . ", user:" . $this->user->getUserId() . "\n");
        $org_id = $this->org_id;
        $store_id = $this->entered_by;
        $loyalty_log_id = $this->id;
        $user_id = $this->user->getUserId();
        $sql = "INSERT INTO loyalty_bill_lineitems (loyalty_log_id, serial, user_id, org_id, item_code, description,
		rate, qty, value, discount_value, amount, store_id, outlier_status) VALUES";
        $suffix_sql = "";

        foreach ($this->lineitems as $li) {
            $li->setLoyaltyLogId($this->id);
            $serial = $li->getSerial();
            $item_code = $li->getItemCode();
            $description = $li->getDescription();
            $rate = $li->getRate();
            $qty = $li->getQty();
            $value = $li->getValue();
            $discount_value = doubleval($li->getDiscountValue());
            $amount = $li->getAmount();
            $this->setLineitemOutlierStatus($li);

            $this->logger->debug("TransactionController::AddMultipleLineItems():=> Discount Value: " . $discount_value);

            $safe_serial = Util::mysqlEscapeString($serial);
            $safe_item_code = Util::mysqlEscapeString($item_code);
            $safe_description = Util::mysqlEscapeString($description);
            $safe_rate = Util::mysqlEscapeString($rate);
            $safe_qty = Util::mysqlEscapeString($qty);
            $safe_value = Util::mysqlEscapeString($value);
            $safe_discount_value = Util::mysqlEscapeString($discount_value);
            $safe_amount = Util::mysqlEscapeString($amount);

            $suffix_sql .= "(
						$loyalty_log_id, '$safe_serial', $user_id, $org_id, 
						'$safe_item_code', '$safe_description', '$safe_rate', '$safe_qty',
						'$safe_value', $safe_discount_value, '$safe_amount', $store_id, '" . $li->getOutlierStatus() . "'
			),";
        }

        $sql = $sql . rtrim($suffix_sql, ',');
        $res = $this->db->insert($sql);
        if (!$res) {
            $this->logger->error("Transaction: Error inserting line item");
            throw new Exception('ERR_LINE_ITEM_ADDITION_FAILED');
        }
        $this->logger->debug("Transaction: No of items added: $res");

        $sql = "SELECT * FROM loyalty_bill_lineitems WHERE org_id = $org_id " .
            "AND store_id = $store_id AND loyalty_log_id = $loyalty_log_id ORDER BY id ASC";

        $lineitems = $this->db->query($sql);
        $li_count = count($this->lineitems);
        $temp_count = 0;
        foreach ($lineitems as $li) {
            $curr_lineitem = $this->lineitems[$temp_count++];
            $curr_lineitem->setId($li['id']);
        }

        return $res;
    }

    private function createReturnInRegularBill()
    {

        $returnBillDetailsArr = array();
        //loop thru each lineitems
        foreach ($this->lineitems as $li) {
            // format the object
            if (strtolower($li->getType()) == 'return') {
                //$returnBillDetailsArr[$li->]

            }
        }
    }

    private function insertBill()
    {
        $this->logger->info("Transaction: Inserting regular bill");
        if (Util::isPointsEngineActive()) {
            $this->logger->debug("Transaction: Points engine active for $org_id, setting points to zero");
            $this->points = 0;
        }

        if ($this->outlier_status == TRANS_OUTLIER_STATUS_OUTLIER) {
            $this->logger->debug("Bill is type of Outlier, setting points to zero");
            $this->points = 0;
        }

        //Insert bill
        $loyalty_id = $this->user->getLoyaltyId();
        $user_id = $this->user->getUserId();
        $this->loyalty_type = $this->user->loyalty_type;

        $this->logger->debug("TransactionController::InsertBill() => Date" . $this->date);
        $date = Util::getMysqlDateTime($this->date);

        $safe_points = Util::mysqlEscapeString($this->points);
        $safe_notes = Util::mysqlEscapeString($this->notes);
        $safe_bill_amount = Util::mysqlEscapeString($this->bill_amount);
        $safe_bill_number = Util::mysqlEscapeString($this->bill_number);
        $safe_counter_id = Util::mysqlEscapeString($this->counter_id);
        $safe_bill_gross_amount = Util::mysqlEscapeString($this->bill_gross_amount);
        $safe_bill_discount = Util::mysqlEscapeString($this->bill_discount);
        $safe_loyalty_type = Util::mysqlEscapeString($this->loyalty_type);
        $safeDeliveryStatus = Util::mysqlEscapeString($this->delivery_status);
        $safeSource = Util::mysqlEscapeString($this->source);


        $sql = "INSERT INTO `loyalty_log` (`loyalty_id`, `points`, `date`, `notes`, `bill_amount`, "
            . " `bill_number`, `entered_by`, `org_id`, `user_id`, `counter_id`, `bill_gross_amount`, `bill_discount` ,`outlier_status`,loyalty_type,source) "

            . " VALUES ('$loyalty_id', '$safe_points', '$date', '$safe_notes', '$safe_bill_amount', "
            . " '$safe_bill_number', '$this->entered_by', '$this->org_id', '$user_id', '$safe_counter_id', '$safe_bill_gross_amount', " .
            "'$safe_bill_discount', '$this->outlier_status','$safe_loyalty_type','$safeSource' ) "
            . " ON DUPLICATE KEY UPDATE points = '$safe_points', `bill_amount` = '$safe_bill_amount'," .
            " `notes` = CONCAT(`notes`, '\n', '$safe_notes'), "
            . " `date` = '$date', `entered_by` = '$this->entered_by', `counter_id`  = '$safe_counter_id'";
        $loyalty_log_id = $this->db->insert($sql);
        $this->logger->debug("Transaction: Loyalty log id- $loyalty_log_id");

        if (isset($loyalty_log_id) && $loyalty_log_id > 0) {
            if (!empty($safeDeliveryStatus)) {
                // Continue to insert into the `transaction_delivery_status` table
                $statusSql = "INSERT INTO `user_management`.`transaction_delivery_status` " .
                    "SET `transaction_id` = " . $loyalty_log_id . ", " .
                    "`transaction_type` = 'REGULAR', " .
                    "`delivery_status` = '" . $safeDeliveryStatus . "', " .
                    "`updated_by` = " . $this->entered_by . " " .
                    "ON DUPLICATE KEY UPDATE " .
                    "`delivery_status` = '" . $safeDeliveryStatus . "', " .
                    "`updated_by` = " . $this->entered_by;

                // Using Dbase -> update() instead of insert() to be able to run ON DUPLICATE KEY UPDATE
                $newDeliveryStatusId = $this->db->update($statusSql);
                $this->logger->debug('Transaction add :: Delivery-status ID: ' . $newDeliveryStatusId);

                if (isset($newDeliveryStatusId) && $newDeliveryStatusId > 0) {
                    // Continue to insert into the `transaction_delivery_status_changelog` table
                    $statusLogSql = "INSERT INTO `user_management`.`transaction_delivery_status_changelog` " .
                        "SET `transaction_id` = " . $loyalty_log_id . ", " .
                        "`transaction_type` = 'REGULAR', " .
                        "`delivery_status` = '" . $safeDeliveryStatus . "', " .
                        "`updated_by` = " . $this->entered_by;

                    $newDeliveryStatusLogId = $this->db->insert($statusLogSql);
                    $this->logger->debug('Transaction add :: Delivery-status-changelog ID: ' . $newDeliveryStatusLogId);
                }
            }

            if ($this->outlier_status == TRANS_OUTLIER_STATUS_OUTLIER) {
                $sql = "INSERT INTO `loyalty_log_outliers` (`loyalty_id`, `org_id`, `user_id`, `bill_number` , `points`, `date`,  `notes`, `bill_amount`, `entered_by`, `outlier_status`, `xml`) " .
                    "VALUES ('$loyalty_id', '$this->org_id', '$user_id', '$safe_bill_number', 
							'$safe_points', '$date', '$safe_notes', '$safe_bill_amount', 
							'$this->entered_by', '$this->outlier_status', '' )";
                $this->logger->debug("Transaction: Loyalty Log Outlier Id => $outlier_id");
            }
        }

        return $loyalty_log_id;
    }

    public function getErrorFlag()
    {
        return $this->error_flag;
    }

    public function getEnteredBy()
    {
        return $this->entered_by;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function setErrorFlag($error_flag)
    {
        $this->error_flag = $error_flag;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function setStatusCode($status_code)
    {
        $this->status_code = $status_code;
    }

    public function callPointsEngine()
    {
        if (Util::canCallPointsEngine()) {
            $this->logger->info("Transaction: Can call points engine. Setting Timers");

            // get the points engine details to call the pe
            $payment_details = $this->getPaymentDetailsArrForPointsEngine();

            $points_timer = new Timer('points_engine');
            $points_timer->start();

            try {
                $this->logger->debug("Transaction: Trying to contact event manager for bill event");

                //COMPILE
                $event_client = new EventManagementThriftClient();

                $billDate = Util::getMysqlDateTime($this->date);
                $timeInMillis = strtotime($billDate);
                if ($timeInMillis == -1 || !$timeInMillis) {
                    throw new Exception("Cannot convert '$billing_time' to timestamp", -1, null);
                }
                $timeInMillis = $timeInMillis * 1000;


                if (Util::canCallEMF()) {
                    try {
                        $emf_controller = new EMFServiceController();
                        $commit = Util::isEMFActive();
                        global $non_loyal;
                        if ($this->user->loyalty_type == 'non_loyalty')
                            $non_loyal = true;
                        else
                            $non_loyal = false;
                        $this->logger->debug("Making NewBillEvent call to EMF");
                        $emf_result = $emf_controller->newBillEvent(
                            $this->org_id,
                            $this->user->getUserId(),
                            $this->id,
                            $this->currentuser->user_id,
                            $this->ignore_points,
                            $this->date,
                            $this->bill_amount,
                            ($this->user->lifetime_purchases + $this->bill_amount),
                            $this->user->lifetime_purchases,
                            $this->user->getNumOfVisits('', $billDate),
                            $commit,
                            $this->referrerCode,
                            $payment_details, $non_loyal,
                            ($this->is_retro ? strtotime("now") : null)// retro time
                        );

                        $coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
                        $this->lm->issuedVoucherDetails($coupon_ids);

                        if ($commit && $emf_result !== null) {
                            $pesC = new PointsEngineServiceController();
                            $awardedPoints = $pesC->updateForNewBillTransaction(
                                $this->org_id, $this->user->getUserId(),
                                $this->id, $this->bill_number, $timeInMillis);
                            if (is_object($awardedPoints) && $awardedPoints->points > 0) {
                                $this->setPoints($awardedPoints->points);
                            }
                        }
                    } catch (Exception $e) {
                        $this->logger->error("Error while making NewBillEvent to EMF: " . $e->getMessage());
                        if (Util::isEMFActive()) {
                            $this->logger->error("Rethrowing EMF Exception AS EMF is Active");
                            throw $e;
                        }
                    }
                }
                if (!Util::isEMFActive()) {
                    $result = $event_client->newBillEvent(
                        $this->org_id,
                        $this->user->getUserId(), $this->id,
                        $this->ignore_points,
                        $timeInMillis, $this->new_customer);

                    $evaluation_id = $result->evaluationID;
                    $effects_vec = $result->eventEffects;
                    $this->logger->debug("Transaction: evaluation_id: $evaluation_id, effects: " . print_r($effects_vec, true));

                    //COMMIT
                    if ($result != null && $evaluation_id > 0) {

                        $this->logger->debug("Calling commit on evaluation_id: $evaluation_id");

                        $commit_result = $event_client->commitEvent($result);
                        $this->logger->debug("Commit result on evaluation_id: " . $commit_result->evaluationID);
                        $this->logger->debug("Commit result on effects: " . print_r($commit_result, true));

                        //Update the old tables from the points engine view
                        $pesC = new PointsEngineServiceController();
                        $awardedPoints = $pesC->updateForNewBillTransaction(
                            $this->org_id, $this->user->getUserId(),
                            $this->id, $this->bill_number, $timeInMillis);
                        if (is_object($awardedPoints) && $awardedPoints->points > 0) {
                            $this->setPoints($awardedPoints->points);
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->error("Exception thrown in new bill event, code: " . $e->getCode()
                    . " Message: " . $e->getMessage());
                $errorCode = isset($e->statusCode) ? $e->statusCode : $e->getCode();
                $errorMessage = isset($e->errorMessage) ? $e->errorMessage : $e->getMessage();
                $this->logger->error("Error while Points engine call [Code: $errorCode, Message: $errorMessage]");
                $errorMessage = Util::convertPointsEngineErrorCode($errorCode);
                throw new Exception($errorMessage, $errorCode);
            } // end point engine call

            $points_timer->stop();

            $ef_time += $points_timer->getTotalElapsedTime();
            $this->logger->debug("pigol: addbills timer: " . $points_timer->getTotalElapsedTime());
            unset($points_timer);
        }
    }

    private function raiseNewBillEvent()
    {

        $this->logger->info("Transaction: Can call points engine. Setting Timers");

        // get the points engine details to call the pe
        $payment_details = $this->getPaymentDetailsArrForPointsEngine();

        $points_timer = new Timer('points_engine');
        $points_timer->start();

        try {
            $emf_controller = new EMFServiceController();

            $billDate = Util::getMysqlDateTime($this->date);
            $timeInMillis = strtotime($billDate);
            if ($timeInMillis == -1 || !$timeInMillis) {
                throw new Exception("Cannot convert '$billing_time' to timestamp", -1, null);
            }
            $timeInMillis = $timeInMillis * 1000;

            global $non_loyal;
            if ($this->user->loyalty_type == 'non_loyalty') {
                $non_loyal = true;
            } else {
                $non_loyal = false;
            }

            $this->logger->debug("Making NewBillEvent call to EMF along with v2 profiles");
            $emf_result = $emf_controller->newBillEvent(
                $this->org_id,
                $this->user->getUserId(),
                $this->id,
                $this->currentuser->user_id,
                $this->ignore_points,
                $this->date,
                $this->bill_amount,
                ($this->user->lifetime_purchases + $this->bill_amount),
                $this->user->lifetime_purchases,
                $this->user->getNumOfVisits('', $billDate),
                true,
                $this->referrerCode,
                $payment_details,
                $non_loyal,
                ($this->is_retro ? strtotime("now") : null) // retro time
            );

            $coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
            $this->lm->issuedVoucherDetails($coupon_ids);
            $billPoints = $emf_controller->extractAwardedPoints($emf_result);
            if ($billPoints > 0) {
                $this->setPoints($billPoints);
            }

        } catch (Exception $e) {
            $this->logger->error("Error while making NewBillEvent to EMF: " . $e->getMessage());
            $this->logger->error("Rethrowing EMF Exception AS EMF is Active");
            throw $e;
        }

        $points_timer->stop();

        $ef_time += $points_timer->getTotalElapsedTime();
        $this->logger->debug("pigol: addbills timer: " . $points_timer->getTotalElapsedTime());
        unset($points_timer);
    }

    private function callPointsEngineForTransactionFinishedEvent()
    {
        if (Util::canCallPointsEngine()) {
            $this->logger->debug("Can call Points engine for TransactionFinishedEvent");
            $points_timer = new Timer('points_engine');
            $points_timer->start();

            $payment_details = $this->getPaymentDetailsArrForPointsEngine();

            try {
                $this->logger->debug("Transaction: Trying to contact event manager for transaction finished event");
                //COMPILE
                $event_client = new EventManagementThriftClient();

                $billDate = Util::getMysqlDateTime($this->date);
                $timeInMillis = strtotime($billDate);
                if ($timeInMillis == -1 || !$timeInMillis) {
                    throw new Exception("Cannot convert '$billDate' to timestamp", -1, null);
                }
                $timeInMillis = $timeInMillis * 1000;

                if (Util::canCallEMF()) {
                    try {
                        $emf_controller = new EMFServiceController();
                        $commit = Util::isEMFActive();
                        global $non_loyal;
                        if ($this->user->loyalty_type == 'non_loyalty')
                            $non_loyal = true;
                        else
                            $non_loyal = false;
                        $this->logger->debug("Making TransactionFinishedEvent call to EMF non loyal " . $non_loyal);
                        $emf_result = $emf_controller->transactionFinishedEvent(
                            $this->org_id,
                            $this->user->getUserId(),
                            $this->id,
                            $this->currentuser->user_id,
                            $this->date,
                            $this->bill_amount,
                            ($this->user->lifetime_purchases + $this->bill_amount),
                            $this->user->lifetime_purchases,
                            $this->user->getNumOfVisits('', $billDate),
                            $commit,
                            $payment_details,
                            $non_loyal,
                            ($this->is_retro ? strtotime("now") : null)// retro time
                        );
                        $coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
                        $this->lm->issuedVoucherDetails($coupon_ids);

                        if ($commit && $emf_result !== null) {
                            $pesC = new PointsEngineServiceController();
                            $awardedPoints = $pesC->updateForTransactionFinishedEvent($this->org_id,
                                $this->user->getUserId(), $this->id, $timeInMillis);
                            if (is_object($awardedPoints) && $awardedPoints->points > 0) {
                                $this->setPoints($awardedPoints->points);
                            }
                        }
                    } catch (Exception $e) {
                        $this->logger->error("Error while making TransactionFinishedEvent to EMF: " . $e->getMessage());
                        if (Util::isEMFActive()) {
                            $this->logger->error("Rethrowing EMF Exception AS EMF is Active");
                            throw $e;
                        }
                    }
                }
                if (!Util::isEMFActive()) {
                    $result = $event_client->transactionFinishedEvent(
                        $this->org_id,
                        $this->user->getUserId(),
                        $this->id,
                        $timeInMillis);

                    $evaluation_id = $result->evaluationID;
                    $effects_vec = $result->eventEffects;
                    $this->logger->debug("TransactionController: evaluation_id: $evaluation_id, effects: " . print_r($effects_vec, true));

                    //COMMIT
                    if ($result != null && $evaluation_id > 0) {
                        $this->logger->debug("Calling commit on evaluation_id: $evaluation_id");
                        $commit_result = $event_client->commitEvent($result);
                        $this->logger->debug("Commit result on evaluation_id: " . $commit_result->evaluationID);
                        $this->logger->debug("Commit result on effects: " . print_r($commit_result, true));

                        //Update the old tables from the points engine view
                        $pesC = new PointsEngineServiceController();
                        $awardedPoints = $pesC->updateForTransactionFinishedEvent($this->org_id,
                            $this->user->getUserId(), $this->id, $timeInMillis);
                        if (is_object($awardedPoints) && $awardedPoints->points > 0) {
                            $this->setPoints($awardedPoints->points);
                        }
                    }
                }

            } catch (Exception $e) {
                $this->logger->error("Exception thrown in new bill event, code: " . $e->getCode()
                    . " Message: " . $e->getMessage());
            } // end point engine call

            $points_timer->stop();

            $ef_time += $points_timer->getTotalElapsedTime();

            $this->logger->debug("pigol: addbills timer: " . $points_timer->getTotalElapsedTime());
            unset($points_timer);
        }
    }

    public function getLineItemsData()
    {
        $lineitems = BillLineItem::getByLoyaltyLogId($this->id, $this->org_id);
        return $lineitems;
    }

    function updateLoyaltyDetails()
    {
        # Update points in the main loyalty table
        $loyalty_id = $this->user->getLoyaltyId();

        if ($this->bill_type == 'return') {
            $points_to_add = -1 * $this->points;
            $amount_to_add = -1 * $this->bill_amount;
        } else if ($this->bill_type == 'regular') {
            $points_to_add = $this->points;
            $amount_to_add = $this->bill_amount;
        } else {
            $points_to_add = 0;
            $amount_to_add = 0;
        }

        $sql = "";
        if (Util::isPointsEngineActive()) {
            $this->logger->info("Transaction: Points Engine is active");
            $sql = "
				UPDATE `loyalty`
				SET
				`lifetime_purchases` = `lifetime_purchases` + $amount_to_add,
				`last_updated` = NOW(),
				`last_updated_by` = '$this->entered_by'
                WHERE publisher_id = $this->org_id AND `id` = '$loyalty_id'
                ";
        } else {
            $sql = "
				UPDATE `loyalty`
				SET
				`loyalty_points` = `loyalty_points` + '$points_to_add',
				`lifetime_points` = `lifetime_points` + '$points_to_add',
				`lifetime_purchases` = `lifetime_purchases` + $amount_to_add,
				`last_updated` = NOW(),
				`last_updated_by` = '$this->entered_by'
                WHERE publisher_id = $this->org_id AND `id` = '$loyalty_id'
                   ";
        }

        $this->logger->debug("Transaction: Updating loyalty details");
        $ret = $this->db->update($sql);
        return $ret;
    }

    public function getHash()
    {
        $hash = array();
        $hash['id'] = $this->id;
        $hash['user'] = $this->user;
        $hash['org_id'] = $this->org_id;
        $hash['bill_number'] = $this->bill_number;
        $hash['points'] = $this->points;
        $hash['loyalty_points'] = $this->user->loyalty_points;
        $hash['notes'] = $this->notes;
        $hash['redeemed'] = $this->redeemed;
        $hash['date'] = $this->date;
        $hash['bill_amount'] = $this->bill_amount;
        // for backward compatibility
        $hash['amount'] = $this->bill_amount;
        $hash['entered_by'] = $this->entered_by;
        $hash['bill_discount'] = $this->bill_discount;
        $hash['bill_gross_amount'] = $this->bill_gross_amount;
        $hash['lineitems'] = $this->lineitems;
        $hash['new_registration'] = $this->new_customer;
        $hash['dvs_vouchers'] = $this->dvs_vouchers;
        return $hash;
    }

    public function processDvsVoucherEvents()
    {
        global $currentorg;

        $this->logger->info("Transaction: Fetching listener_id of IssueServerDVSVoucherListener");
        //adding code for calling the server dvs issuing listener call
        $sql = "SELECT id FROM listeners WHERE org_id = " . $this->org_id . " AND listener_name = 'IssueServerDVSVoucherListener'" .
            " AND end_time > NOW() ORDER BY id DESC LIMIT 1";
        $listener_id = $this->db->query_scalar($sql);
        $this->logger->debug("Transaction: listener_id = $listener_id");

        $dvs_vouchers = array();
        if ($listener_id) {  //issuing server side dvs is attached for this org
            $this->logger->debug("Transaction: Running for listener id: " . $listener_id);

            $finished_params['loyalty_log_id'] = $this->id;
            $finished_params['user_id'] = $this->user->user_id;
            $finished_params['bill_amount'] = floatval($this->bill_amount);
            $datetime = Util::getMysqlDateTime($this->date);
            $finished_params['num_of_visits'] = $this->user->getNumOfVisits('', $datetime);

            $this->logger->debug("processDvsVoucherEvents() -> Signalling listener $listener_id: "
                . print_r($finished_params, true));
            $issuedVouchers = $this->lm->signalSingleListener($listener_id, EVENT_ISSUE_SERVER_DVS_VOUCHER, $finished_params);
            $this->logger->debug("processDvsVoucherEvents() Returned vouchers: " . print_r($issuedVouchers, true));

            if (is_array($issuedVouchers) && !empty($issuedVouchers)) {
                $unique_vs = array();
                foreach ($issuedVouchers as $res) {
                    if (!in_array($res['id'], $vs_list)) {
                        array_push($vs_list, $res['id']);
                        array_push($unique_vs, $res);
                    }
                }

                $this->logger->debug("shuffled: " . print_r($unique_vs, true));

                shuffle(&$unique_vs);

                $this->logger->debug("shuffled: " . print_r($unique_vs, true));

                $res = $unique_vs[0];

                $this->logger->info("Transaction: For each issuedVouchers..");
                $vs_id = $res['id'];
                $created_time = '';
                $created_time = $created_time != '' ? Util::deserializeFrom8601($created_time) : '';

                $vch = Voucher::issueVoucher($vs_id, $this->user, $currentorg, $this->entered_by, NULL, $created_time, false, '', (string)$b->bill_number);
                if ($vch != false) {

                    $v = Voucher::getVoucherFromCode($vch, $this->org_id);
                    $vs = $v->getVoucherSeries();
                    $sms_template = $vs->getSMSTemplate();
                    if ($sms_template == "")
                        $sms_template = "Hello {{cust_name}}, your voucher code is {{voucher_code}}";

                    $data = array('voucher_code' => $v->getVoucherCode(), 'cust_name' => $this->user->getName());

                    $dvs_vouchers[] = array('voucher_series_id' => $res['id'], 'discount_code' => $res['discount_code'], 'description' => $res['description'], 'voucher_code' => $v->getVoucherCode(),
                        'info' => $vs->info, 'dvs_items' => $vs->dvs_items
                    );

                    $smstext = Util::templateReplace($sms_template, $data);
                    $this->logger->debug("smstext: $smstext");
                    Util::sendSms($this->user->mobile, $smstext, $this->org_id, MESSAGE_PRIORITY,
                        false, '', false, false, array(), $this->user->user_id, $this->id, 'TRANSACTION');
                }
            } else
                $this->logger->debug("no Vouchers has been issued: Result of signalSingleListener:" . print_r($issuedVouchers, true));
        } //ending listener id
        $this->dvs_vouchers = $dvs_vouchers;
    }

    public function generateErrorMessage($old = false)
    {
        if ($old) {
            $this->logger->debug("Transaction: Generating old api error msg");
            $response = array();
            $item_status = array();
            $status_code = $this->status_code;
            $response_code = Util::convertOldErrorCodes($status_code);
            $this->logger->debug("Transaction: Response Code $response_code");
            $item_status['key'] = ErrorCodes::$transaction[$response_code];
            $item_status['message'] = ErrorMessage::$transaction[$response_code];

            $this->logger->debug("Transaction: Item Status = " . print_r($item_status, true));
            $response = array('bill_number' => (string)$this->bill_number,
                'user_id' => $this->user->getUserId(), 'loyalty_points' => $this->points,
                'mobile' => $this->user->mobile, 'email' => $this->user->email, 'external_id' => $this->user->external_id,
                'lifetime_points' => $this->user->lifetime_points, 'lifetime_purchases' => $this->user->lifetime_purchases,
                'slab_name' => $this->user->slab_name, 'slab_number' => $this->user->slab_number,
                'bill_client_id' => $bill_client_id, 'response_code' => ErrorCodes::$transaction[$response_code],
                'response' => ErrorMessage::$transaction[$response_code], 'item_status' => $item_status,
                'dvs_vouchers' => $dvs_vouchers, 'new_registration' => $this->new_customer);
            $this->logger->debug("Transaction: Response = " . print_r($response, true));
            return $response;
        }
        $item = array();
        $this->logger->info("Transaction: Generating error message");
        $status_code = $this->status_code;
        $this->logger->debug("Transaction: Error Status Code $status_code");
        if (empty($status_code)) {
            $status_code = "ERR_LOYALTY_BILL_ADDITION_FAILED";
        }

        $response_code = Util::convertOldErrorCodes($status_code);
        $this->logger->debug("Transaction: Response Code $response_code");
        $item['transaction_number'] = $this->bill_number;
        $item['type'] = $this->bill_type;

        //$loyalty_details=$this->loyaltyController->getLoyaltyDetailsForLoyaltyID($loyalty_id);
        $this->logger->debug("Transaction: Extrancting Customer details");
        if ($this->user && $this->user->getUserId()) {
            $customer = array();
            $customer["user_id"] = $this->user->user_id;
            $customer['mobile'] = $this->user->mobile;
            $customer['email'] = $this->user->email;
            $customer['external_id'] = $this->user->external_id;
            $item['customer'] = $customer;
        }
        $item['item_status']['success'] = false;
        $item['item_status']['code'] = ErrorCodes::$transaction[$response_code];
        $item['item_status']['message'] = ErrorMessage::$transaction[$response_code];
        $this->logger->debug("TransactionController: error message " . print_r($item, true));
        return $item;
    }

    public function generateResponse()
    {
        $this->logger->info("Transaction: Generating response");
        $item = array();
        $item['transaction_id'] = $this->id;
        $item['transaction_number'] = $this->bill_number;
        $item['type'] = strtoupper($this->bill_type);

        if ($this->bill_type == 'regular' && $this->return_id_arr)
            $item['type'] = 'MIXED';
        //Adding customer info
        $this->logger->debug("Transaction: Extracting customer detials");
        if ($this->bill_type != 'not_interested' && $this->bill_type != 'not_interested_return') {
            $customer = array();
            $customer['user_id'] = $this->user->user_id;
            $customer['mobile'] = $this->user->mobile;
            $customer['email'] = $this->user->email;
            $customer['external_id'] = $this->user->external_id;
            $customer['loyalty_points'] = $this->user->loyalty_points;
            $customer['lifetime_points'] = $this->user->lifetime_points;
            $customer['lifetime_purchases'] = $this->user->lifetime_purchases;
            $customer['current_slab'] = $this->user->slab_name;
            $customer['tier_expiry_date'] = $this->user->slab_expiry_date;
            $customer['type'] = $this->user->loyalty_type;
            $item['customer'] = $customer;
        }

        $item['delivery_status'] = is_null($this->delivery_status) ? 'DELIVERED' : $this->delivery_status;
        $item['source'] = $this->source;
        $item['item_status'] = array('success' => 'true',
            'code' => ErrorCodes::$transaction['ERR_LOYALTY_SUCCESS'],
            'message' => ErrorMessage::$transaction['ERR_LOYALTY_SUCCESS']);
        if ($this->bill_type == 'return') {
            $this->logger->info("Transaction: Return Bill. Extracting point details");
            $item['points_deducted'] = $this->points;
            $item['points_balance'] = $this->user->loyalty_points;
        }
        $this->logger->debug("Transaction: Response " . print_r($item, true));
        return $item;
    }

    /*
	 * this should execute all listeners and call ListenerManager::signalListeners() for
	* EVENT_TRACKER_MILESTONE_NOTIFICATION, EVENT_LOYALTY_TRANSACTION_FINISHED, EVENT_LOYALTY_TRANSACTION_LINEITEMS
	*/
    private function executeTransactionEvents()
    {

        //Signals EVENT_LOYALTY_TRASNACTION
        $transaction_params = $this->getLoyaltyTransactionEventParams();
        $this->logger->debug("TransactionController: EventLoyaltyTransaction");
        $this->lm->signalListeners(EVENT_LOYALTY_TRASNACTION,
            $transaction_params);

        // Signals EVENT_LOYALTY_TRANSACTION_LINEITEMS for each line items
        $all_line_item_params = array();

        $lineitems = $this->lineitems;
        if (is_array($lineitems)) {
            foreach ($lineitems as $li) {
                if ($li->getLoyaltyLogId()) {
                    $this->logger->info("TransactionController: Generating Line Item params");
                    $line_item_params = $this->getLineItemParams($li);
                    //setting the Loyalty_points
                    $line_item_params['loyalty_points'] = $transaction_params['loyalty_points'];
                    $this->logger->debug("Communicator: Line Item Params " . print_r($line_item_params, true));
                    $this->lm->signalListeners(EVENT_LOYALTY_TRANSACTION_LINEITEMS, $line_item_params);
                    array_push($all_line_item_params, $line_item_params);
                    unset($line_item_params);
                }
            }
        }

        //Signals EVENT_TRACKER_MILESTONE_NOTIFICATION
        $this->logger->debug("TransactionController: EventTrackerMilestoneNotification");
        $this->lm->signalListeners(EVENT_TRACKER_MILESTONE_NOTIFICATION,
            $this->getTrackerMilestoreNotificationParams());

        //Everything is complete
        //$this->refreshBillDetails();

        $transaction_finished_params = $transaction_params;
        $transaction_finished_params['user_id'] = $this->user->user_id;
        $transaction_finished_params['bill_points'] = $this->points;


        $transaction_finished_params['bill_line_item_purchased'] = $all_line_item_params;
        //Signals EVENT_LOYALTY_TRANSACTION_FINISHED
        $this->logger->debug("TransactionController: EventLoyaltyTransactionFinished");
        $this->lm->signalListeners(EVENT_LOYALTY_TRANSACTION_FINISHED,
            $transaction_finished_params);

    }

    /**
     * This function will fetch data of Bill from db, and update following fields
     * points
     */
    private function refreshBillDetails()
    {
        $sql = "SELECT * FROM loyalty_log WHERE org_id = $this->org_id AND id = $this->id";
        $row = $this->db->query_firstrow($sql);

        if ($row) {
            $this->setPoints($row['points']);
        }
    }

    private function getTrackerMilestoreNotificationParams()
    {
        $this->logger->info("Transaction: EVENT Tracker Milestone");
        $params = array();
        $params['user_id'] = $this->user->getUserId();
        $params['date'] = Util::getMysqlDateTime($this->getDate());
        $entered_by = $this->getEnteredBy();
        $params['entered_by'] = $entered_by;
        $params['store_id'] = $entered_by;
        $params['user_id'] = $this->user->user_id;
        $params['first_name'] = $this->user->first_name;
        $params['last_name'] = $this->user->last_name;
        $params['fullname'] = $this->user->first_name . ' ' . $this->user->last_name;
        $params['bill_number'] = $this->bill_number;

        return $params;
    }

    private function getLoyaltyTransactionEventParams()
    {
        $this->logger->info("TransactionController: EVENT Loyalty Transaction");
        $params = array();

        $params['user_id'] = $this->user->user_id;
        $params['current_points'] = (double)$this->points;
        $params['bill_points'] = (double)$this->points;
        $params['bill_amount'] = (double)$this->bill_amount;
        $params['bill_number'] = (string)$this->bill_number;
        $params['bill_discount'] = (float)$this->bill_discount;
        $params['bill_gross_amount'] = (double)$this->bill_gross_amount;
        $params['bill_diff_gross_discount'] = (double)$this->bill_gross_amount - (double)$this->bill_discount;
        $params['bill_diff_amount_discount'] = (double)$this->bill_amount - (double)$this->bill_discount;

        $loyalty_details = $this->loyaltyController->getLoyaltyDetailsForLoyaltyID($this->user->loyalty_id);
        $params['total_points'] = $loyalty_details['loyalty_points'] > 0 ? $loyalty_details['loyalty_points'] : 0;
        $params['loyalty_points'] = $loyalty_details['loyalty_points'] > 0 ? $loyalty_details['loyalty_points'] : 0;
        $params['lifetime_purchases'] = $loyalty_details['lifetime_purchases'] > 0 ? $loyalty_details['lifetime_purchases'] : 0;
        $params['lifetime_points'] = $loyalty_details['lifetime_points'] > 0 ? $loyalty_details['lifetime_points'] : 0;

        $shoped_store = StoreProfile::getById($this->currentuser->user_id);

        $datetime = Util::getMysqlDateTime($this->date);
        $params['gross_points'] += $params['total_points'];
        $params['loyalty_log_id'] = $this->id;
        $params['entered_by'] = $this->currentuser->user_id;
        $params['date'] = $datetime;
        $params['num_of_bills'] = $this->user->getNumOfBills('', $datetime);
        $params['num_of_visits'] = $this->user->getNumOfVisits('', $datetime);
        $params['num_of_bills_today'] = $this->user->getNumOfBillsToday('', $datetime);
        $params['num_of_bills_n_days'] = $this->user->getNumOfBillsnDays('', $datetime);
        $params['notes'] = (string)$this->notes;
        $params['ignore_points'] = $this->ignore_points;
        $params['shoped_at_store_name'] = $shoped_store->getFullName();
        $params['cashier_code'] = (string)$this->cashier_details[0]->cashier_code;
        $params['cashier_name'] = (string)$this->cashier_details[0]->cashier_name;
        $params['store_description'] = $shoped_store->getStoreDescription();

        $cf = new CustomFields();
        $custom_fields_data = $cf->getCustomFieldValuesByAssocId(
            $this->org_id, LOYALTY_CUSTOM_TRANSACTION, $this->id);
        foreach ($custom_fields_data AS $name => $value) {

            $temp_value = json_decode($value, true);
            $temp_value = $temp_value !== NULL ? $temp_value : $value;
            $cfNameUgly = Util::uglify($cf->getFieldName($name));

            $params[$cfNameUgly] = is_array($temp_value) && count($temp_value) > 0 ?
                $temp_value[0] : $value;
        }

        $custom_fields_data = $cf->getCustomFieldValuesByAssocId(
            $this->org_id, LOYALTY_CUSTOM_REGISTRATION, $this->user->user_id);
        foreach ($custom_fields_data AS $name => $value) {

            $temp_value = json_decode($value, true);
            $temp_value = $temp_value !== NULL ? $temp_value : $value;
            $cfNameUgly = Util::uglify($cf->getFieldName($name));

            $params[$cfNameUgly] = is_array($temp_value) && count($temp_value) > 0 ?
                $temp_value[0] : $value;
        }

        $org_model = new OrgEntityModelExtension();
        $shoped_store_id = $org_model->getParentsById($shoped_store->user_id, "STORE", $this->org_id);
        $shoped_store_id = $shoped_store_id[0];
        $custom_fields_data = $cf->getCustomFieldValuesByAssocId(
            $this->org_id, STORE_CUSTOM_FIELDS, $shoped_store_id);
        foreach ($custom_fields_data AS $name => $value) {

            $temp_value = json_decode($value, true);
            $temp_value = $temp_value !== NULL ? $temp_value : $value;
            $cfNameUgly = Util::uglify($cf->getFieldName($name));

            $params[$cfNameUgly] = is_array($temp_value) && count($temp_value) > 0 ?
                $temp_value[0] : $value;
        }

        //add slab_name and slab_number
        $cm = new ConfigManager();
        if ($cm->getKey(CONF_LOYALTY_ENABLE_SLABS)) {

            $slab_name = $loyalty_details['slab_name'];
            $slab_number = $loyalty_details['slab_number'];

            //get default slab in case customer does not have any slab
            if (strlen($loyalty_details['slab_name']) == 0) {
                $slablist = $this->loyaltyController->getSlabsForOrganization();
                if (count($slablist) > 0) {
                    $slab_name = $slablist[0];
                    $slab_number = "0";
                }
            }

            //add it to supplied data
            $params['slab_name'] = $slab_name;
            $params['slab_number'] = $slab_number;
        }

        // payment details
        if ($this->paymentDetailsObjArr) {
            $emf_controller = new EMFServiceController();
            $params["tenderDetails"] = $emf_controller->formatPaymentDetailsForThrift($this->getPaymentDetailsArrForPointsEngine());
        }
        return $params;
    }

    /**
     * returns parameters of a lineitem for Signaling the Listener
     * @param unknown_type $li
     */
    private function getLineItemParams($li)
    {
        //Signal the Loyalty Line Items Event
        //Fill the supplied data
        $line_item_params = array();

        $line_item_params['serial'] = $li->getSerial();
        $line_item_params['item_code'] = $li->getItemCode();
        $line_item_params['description'] = $li->getDescription();
        $line_item_params['rate'] = $li->getRate();
        $line_item_params['qty'] = $li->getQty();
        $line_item_params['value'] = $li->getValue();
        $line_item_params['discount_value'] = $li->getDiscountValue();
        $line_item_params['amount'] = $li->getAmount();

        $line_item_params['user_id'] = $this->user->user_id;
        $line_item_params['loyalty_log_id'] = $this->id;
        $line_item_params['date'] = Util::getMysqlDateTime($this->date);
        $line_item_params['line_item_id'] = $li->getId();
        $line_item_params['entered_by'] = $this->currentuser->user_id;
        $line_item_params['store_id'] = $this->currentuser->user_id;
        $line_item_params['sku_amount'] = $li->getAmount();
        $line_item_params['sku_qty'] = $li->getQty();
        $line_item_params['sku_rate'] = $li->getRate();
        $line_item_params['sku_value'] = $li->getValue();
        $line_item_params['sku_discount_value'] = $li->getDiscountValue();

        //actual assignment is done after this function call.
        $line_item_params['loyalty_points'] = 0;

        $line_item_params['bill_number'] = $this->bill_number;
        $line_item_params['bill_amount'] = $this->bill_amount;
        $line_item_params['bill_gross_amount'] = $this->bill_gross_amount;
        $line_item_params['bill_discount'] = $this->bill_discount;
        $line_item_params['cashier_code'] = (string)$cashier_details[0]->cashier_code;
        $line_item_params['cashier_name'] = (string)$cashier_details[0]->cashier_name;

        $this->logger->debug("Transaction Controller: lineitem params " . print_r($line_item_params, true));

        return $line_item_params;
    }


    /**
     *
     * @param unknown_type $query_params needs to be id of the transaction.
     * @throws InvalidInputException
     */
    public function getTransactions($query_params)
    {
        $should_return_user_id = $query_params['user_id'] == 'true' ? true : false;
        $db = new Dbase('users');
        $transaction_array = StringUtils::strexplode(',', $query_params['id']);

        if (sizeof($transaction_array) == 0) {
            $this->logger->debug("No transaction id passed");
            throw new InvalidInputException(ErrorMessage::$transaction['ERR_NO_TRANSACTION_ID'],
                ErrorCodes::$transaction['ERR_NO_TRANSACTION_ID']);
        }

        $error_count = 0;
        $transaction_count = 0;

        $response = array();
        foreach ($transaction_array as $key => $transaction_id) {
            $transaction = array();
            try {
                $error_key = "ERR_GET_SUCCESS";
                ++$transaction_count;
                $transaction = $this->getBills($transaction_id);
                if (!$should_return_user_id)
                    unset($transaction['customer']['user_id']);
            } catch (Exception $e) {
                ++$error_count;
                $error_key = $e->getMessage();

                $transaction['transaction_id'] = $transaction_id;
            }

            $transaction['item_status']['success'] = ($error_key == "ERR_GET_SUCCESS") ? 'true' : 'false';
            $transaction['item_status']['code'] = ErrorCodes::getTransactionErrorCode($error_key);
            $transaction['item_status']['message'] = ErrorMessage::getTransactionErrorMessage($error_key);

            array_push($response, $transaction);

        }

        //Status
        $status = 'SUCCESS';
        if ($transaction_count == $error_count) {
            $status = 'FAIL';
        } else if (($error_count < $transaction_count) && ($error_count > 0)) {
            $status = 'PARTIAL_SUCCESS';
        }

        $root['status']['success'] = ($status == 'SUCCESS' || $status == 'PARTIAL_SUCCESS') ? 'true' : 'false';
        $root['status']['code'] = ErrorCodes::$api[$status];
        $root['status']['message'] = ErrorMessage::$api[$status];
        $root['transactions']['transaction'] = $response;

        $this->logger->debug("Response: " . print_r($root, true));

        return $root;
    }

    /*
	 *note :- If xml is getting changed Please check the compatibility with outlier Bill Submition action.
	*
	*/

    public function addBills($transaction, $register = true)
    {
        global $gbl_api_version;
        $status = true;
        $customer_updated = false;
        try {

            $this->logger->info("Creating transaction object");

            $customer_info = $transaction['customer'];

            if (isset($transaction['billing_time']) && !isset($customer_info['registered_on'])) {
                $this->logger->debug("registered_on is not passed for customer taking transaction->billing_time as customer->registered_on");
                $customer_info['registered_on'] = $transaction['billing_time'];
            }

            if (!empty($transaction['loyalty_id']) && $transaction['loyalty_id'] != 'LOYALTY_ID_NA') {
                $this->logger->debug("TransactionController::addBills() LoyaltyId=>" . $transaction['loyalty_id']);
                $user = UserProfile::getByLoyaltyId($transaction['loyalty_id']);
            }

            if (!$user) {

                $customer_controller = new ApiCustomerController();

                /* Checks, if The User is already registered by either of the three identifier
				 * (Mobile, Email, External Id */
                $is_registered = false;
                try {
                    $user = UserProfile::getByData($customer_info);
                    $user->load(true);
                    $is_registered = true;
                } catch (Exception $e) {
                    $this->logger->debug("Customer Not Found: " . $e->getMessage());
                    $is_registered = false;
                }

                //if( !$res && $register )
                if (!$is_registered && $register) {
                    try {
                        //$res = $user->register();
                        if (isset($transaction['billing_time']) && !isset($customer_info['registered_on'])) {
                            $this->logger->debug("registered_on is not passed for customer taking transaction->billing_time as customer->registered_on");
                            $customer_info['registered_on'] = $transaction['billing_time'];
                        }

                        $customer_info = $customer_controller->validateInputIdentifiers($customer_info);

                        $user = $customer_controller->register($customer_info);

                        $this->logger->debug("Loyalty Id: " . $user->loyalty_id);
                        $user = UserProfile::getByLoyaltyId($user->loyalty_id);
                        $user->load();

                    } catch (Exception $e) {
                        $this->logger->error("Customer Registration Error: " . $e->getMessage());
                        throw $e;
                    }
                    $this->setNewCustomer(true);
                } else if ($gbl_api_version == 'v1.1') {
                    try {
                        $this->logger->debug("Customer is already registered, trying to update customer");
                        $customer_info = $customer_controller->validateInputIdentifiers($customer_info);
                        $user = $customer_controller->updateCustomer($customer_info, $user);
                        $customer_updated = true;
                    } catch (Exception $e) {
                        $this->logger->error("Customer Update failed");
                        Util::addApiWarning("Customer Update Failed [{{ERROR}}]",
                            array("ERROR" => ErrorMessage::$customer[$e->getMessage()]));
                    }
                }
            }

            if (!($this->new_customer || $customer_updated) && $user->user_id) {
                $user->setCustomFields($customer_info);
                $user->saveCustomFields();
            }

            $this->setUser($user);


            $this->initTransactionElements($transaction);

            if (!$user) {
                $this->logger->error("Customer not found from loyalty id");
                throw new Exception("ERR_LOYALTY_USER_NOT_REGISTERED");
            }

            //check the block state
            $this->isTransactionAllowedForUser($user->user_id);

            //for saving custom fields
            if (!($this->new_customer || $customer_updated) && $user->user_id) {
                $user->setCustomFields($customer_info);
                $user->saveCustomFields();
            }

            $this->setUser($user);//sets the User

            $this->logger->debug("saving the regular bill");

            $this->saveTransaction();

            //ingesting this event as the transaction has been added now.
            global $currentorg;
            $transEventAttributes = array();
            $transEventAttributes["subtype"] = strtoupper($this->bill_type);
            $transEventAttributes["billClientId"] = $transaction['bill_client_id'];
            $transEventAttributes["transactionId"] = $this->id; //$transaction['transaction_id'];
            $transEventAttributes["customerId"] = intval($user->user_id);
            $transEventAttributes["amount"] = intval($transaction['amount']);
            $transEventAttributes["transactionNumber"] = $transaction['transaction_number'];
            $transEventAttributes["grossAmount"] = $transaction['gross_amount'];
            $transEventAttributes["basketSize"] = sizeof($transaction['line_items']);
            $transEventAttributes["entityId"] = intval($this->currentuser->user_id); // $transaction[""];

            $billingTime = strtotime($transaction['billing_time']);
            EventIngestionHelper::ingestEventAsynchronously(intval($currentorg->org_id), "transaction",
                "Transaction event from the Intouch PHP API's", $this->date, $transEventAttributes);

            if ($this->bill_type == 'regular')
                $this->addLineItems();
            if ($this->bill_type == 'regular' && $this->return_bills_arr && $this->id) {
                $this->addReturnedLineItems();
            }

            if ($this->credit_note && $this->id > 0) {
                $this->saveCreditNotes();
            }

            if ($this->bill_type == 'regular' && $this->return_id_arr && $this->id) {
                $this->linkReturnTransactionWithLoyaltyLog();
            }

            // Saving Associate Activity
            if (isset($transaction['associate_details'])) {
                $this->saveAssociateActivity($transaction['associate_details']);
            }

            //update the optin status of the customer
            $this->updateOptinStatus();

            //updating memcache entry for OrganizationStatistics
            $lineitem_count_tobe_added = count($this->lineitems);
            Util::increaseNumberOfBillAndLineItemsToMemcache($this->org_id, 1, $lineitem_count_tobe_added);
            Util::increaseTotalTransactionValue($this->org_id, $this->bill_amount);
            $this->logger->debug("ready to redeem vouchers");
            $redeemed_vouchers = $transaction['redeemed_vouchers'];

            if ($redeemed_vouchers && $this->outlier_status != 'OUTLIER') {
                $this->logger->debug("adding the vouchers redeemed on the bill " . $transaction['bill_number']);
                $this->loyaltyController->addRedeemedVouchersForBill($redeemed_vouchers, (string)$transaction['bill_number'],
                    (double)$transaction['bill_amount'], $this->user->user_id, Util::getMysqlDateTime($this->date));
                $this->logger->debug("voucher redemption is completed");
            }

            //Catching exceptions from side effects
            try {

                $promo_details = $transaction['promotional_voucher'];
                if (!empty($promo_details) && $this->outlier_status != TRANS_OUTLIER_STATUS_OUTLIER)
                    $this->loyaltyController->addPromotionalDetails($this->id, $this->user, $promo_details);

                if ($this->outlier_status != TRANS_OUTLIER_STATUS_OUTLIER && Util::canCallPointsEngine()) {
                    try {
                        //$this->callPointsEngine();
                        $this->logger->debug("About to raiseNewBillEvent to EMF");
                        $this->raiseNewBillEvent();
                    } catch (Exception $e) {
                        $this->logger->error("Exception Caught while calling new bill event 
								: [Message: " . $e->getMessage() . ", 
								Code: " . $e->getCode() . "], but not throwing Exception");
                        Util::addApiWarning(ErrorMessage::$transaction[$e->getMessage()]);
                    }
                }

                $this->logger->debug("Going to execute the events");
                if ($this->outlier_status != TRANS_OUTLIER_STATUS_OUTLIER) {
                    try {
                        $this->executeTransactionEvents();
                    } catch (Exception $e) {
                        $this->logger->error("Exception Caught : while transaction trnansaction trackers
								[Message: " . $e->getMessage() . ",
								Code: " . $e->getCode() . "], but not throwing Exception");
                        Util::addApiWarning(ErrorMessage::$transaction[$e->getMessage()]);

                    }

                    try {
                        $this->callPointsEngineForTransactionFinishedEvent();
                    } catch (Exception $e) {
                        $this->logger->error("Exception Caught : while transaction finished event
								[Message: " . $e->getMessage() . ",
								Code: " . $e->getCode() . "], but not throwing Exception");
                        Util::addApiWarning(ErrorMessage::$transaction[$e->getMessage()]);
                    }
                }

                $this->logger->debug("ready to process DVS vouchers");
                //this is must for add Bills.
                if ($this->outlier_status != TRANS_OUTLIER_STATUS_OUTLIER)
                    $this->processDvsVoucherEvents();
            } catch (Exception $e) {
                $this->logger->error("Exception Caught : [Message: " . $e->getMessage() . ", Code: " . $e->getCode() . "]");

                throw new Exception("ERR_TRIGGERING_SIDE_EFFECTS_FAILED");
            }
        } catch (Exception $e) {
            $this->logger->error("TransactionController::addBill => " . $e->getMessage());
            $this->status_code = $e->getMessage();
            throw new Exception($e->getMessage());
        }


        $mem_cache_manager = MemcacheMgr::getInstance();
        $bills_cache_key = "o" . $this->currentuser->org_id . "_" . sprintf("%s_o%d_s%d_d%d", CacheKeysPrefix::$loyaltyStoreBillsCounterKey,
                $this->org_id, $this->currentuser->user_id, date('d'));
        $regs_cache_key = "o" . $this->currentuser->org_id . "_" . sprintf("%s_o%d_s%d_d%d", CacheKeysPrefix::$loyaltyStoreRegsCounterKey,
                $this->org_id, $this->currentuser->user_id, date('d'));

        try {
            $this->logger->debug("trying to update cache NumRegsAndBillsTodayForStore  with inc=1, 1");

            //by default it will take 1
            $mem_cache_manager->increment($bills_cache_key);
            if ($this->new_customer)
                $mem_cache_manager->increment($regs_cache_key);
        } catch (Exception $e) {
            // non existent OR set failed
            $this->logger->debug("couldn't update NumRegsAndBillsTodayForStore");
            $this->logger->error($e->getMessage());
        }
        $this->user->load();
        return $this;
    }

    public function returnBills($transaction)
    {
        $status = true;
        try {
            $this->logger->info("Creating transaction object");

            $customer_info = $transaction['customer'];

            if (!empty($transaction['loyalty_id'])) {
                $this->logger->debug("Fetching User By Loyalty id: " . $transaction['loyalty_id']);
                $user = UserProfile::getByLoyaltyId($transaction['loyalty_id']);
            }

            if (!$user && $transaction['user_id'] > 0) {
                //TODO: here loyalty fields are not loaded in UserProfile object
                $user = UserProfile::getById(intval($transaction['user_id']));
                $this->logger->debug("User loaded with id: " . $user->user_id);
            }

            if (!$user) {
                $user = UserProfile::getByData($customer_info);
                $res = false;
                try {
                    $res = $user->load();
                } catch (Exception $e) {
                    $this->logger->error("Customer Fetching Error");
                    $res = false;
                    throw new Exception("ERR_LOYALTY_USER_NOT_REGISTERED");
                }
            }

            if(isset($user->user_id))
                $this->logger->error("Customer Fetching success userId:".print_r($user->user_id,true));

            $this->setUser($user);
            $this->isTransactionAllowedForUser($user->user_id);

            $this->initTransactionElements($transaction);

            $this->saveTransaction();

            //ingesting this event as the transaction has been added now.
            global $currentorg;
            $transEventAttributes = array();
            $transEventAttributes["subtype"] = strtoupper($this->bill_type);
            $transEventAttributes["billClientId"] = $transaction['bill_client_id'];
            $transEventAttributes["transactionId"] = $this->id; //$transaction['transaction_id'];
            $transEventAttributes["customerId"] = intval($user->user_id);
            $transEventAttributes["amount"] = intval($transaction['amount']);
            $transEventAttributes["transactionNumber"] = $transaction['transaction_number'];
            $transEventAttributes["grossAmount"] = $transaction['gross_amount'];
            $transEventAttributes["basketSize"] = sizeof($transaction['line_items']);
            $transEventAttributes["entityId"] = intval($this->currentuser->user_id); // $transaction[""];

            $billingTime = strtotime($transaction['billing_time']);
            EventIngestionHelper::ingestEventAsynchronously(intval($currentorg->org_id), "transaction",
                "Transaction event from the Intouch PHP API's", $this->date, $transEventAttributes);

            if ($this->id > 0 && $this->credit_note)
                $this->saveCreditNotes();

            // Saving Associate Activity
            if (isset($transaction['associate_details'])) {
                $this->saveAssociateActivity($transaction['associate_details']);
            }
            $this->user->load();
        } catch (Exception $e) {
            $this->logger->error("TransactionController::returnBill => " . $e->getMessage());
            $this->status_code = $e->getMessage();
            throw new Exception($e->getMessage());
        }
        return $this;
    }

    public function filterBills($transaction_number = null, $date = null,
                                $amount = null, $store_codes = null, $till_codes = null,
                                $type = 'REGULAR', $start = false, $batch_size = 100, $should_return_tenders = false,
                                $credit_notes = false, $include_user_id = false)
    {
        $this->logger->debug("@@@@@ $type");
        $type = strtoupper($type);

        if ($type == 'ALL') {
            $start = false;
            $ret = array();
            $types = array('REGULAR', 'RETURN', 'NOT_INTERESTED');

            foreach ($types as $t) {
                try {
                    $trans = $this->filterBills($transaction_number, $date,
                        $amount, $store_codes, $till_codes,
                        $t, $start, $batch_size, $should_return_tenders,
                        $credit_notes, $include_user_id);
                    $ret = array_merge($ret, $trans);
                } catch (Exception $e) {
                    if ($e->getCode() == ErrorCodes::$transaction["ERR_NO_TRANSACTION_RETRIEVED"]) {
                        $this->logger->debug("No transaction retrieved for type $t");
                    } else {
                        throw $e;
                    }
                }
            }
            if (empty($ret)) {
                throw new Exception(ErrorMessage::$transaction["ERR_NO_TRANSACTION_RETRIEVED"], ErrorCodes::$transaction["ERR_NO_TRANSACTION_RETRIEVED"]);
            }

            if (count($ret) > $batch_size) {
                $this->logger->debug("@@@@ Size of array is " . count($ret) . " and batch size is " . $batch_size . " reducing to " . count(array_slice($ret, 0, $batch_size)));
                return array_slice($ret, 0, $batch_size);
            } else {
                return $ret;
            }
        }
        //echo $type;

        if ($type != 'REGULAR' && $type != 'NOT_INTERESTED' && $type != 'RETURN')
            throw new Exception(ErrorMessage::$transaction["ERR_INVALID_TRANSACTION_TYPE"], ErrorCodes::$transaction['ERR_INVALID_TRANSACTION_TYPE']);

        $result = array();

        $transactionFilters = new TransactionLoadFilters();
        if ($amount != null) {
            $cm = new ConfigManager();
            $margin = $cm->getKey('CONF_LOYALTY_TRANSACTION_AMOUNT_MARGIN');
            $transactionFilters->min_transaction_amount = $amount - $margin;
            $transactionFilters->max_transaction_amount = $amount + $margin;
        }
        $transactionFilters->transaction_number = $transaction_number;
        $transactionFilters->min_transaction_date = $date;
        $transactionFilters->max_transaction_date = $date;
        $transactionFilters->start_id = $start;

        $org_model = new OrgEntityModelExtension();
        if (!empty($till_codes))
            $till_ids = array_map(function ($a) {
                return $a['id'];
            }, $org_model->getTillsByCode($till_codes));
        if (!empty($store_codes)) {
            $store_ids = array_map(function ($a) {
                return $a['id'];
            }, $org_model->getStoresByCode($store_codes));
            foreach ($store_ids as $store_id) {
                $org_model->load($store_id);
                foreach ($org_model->getChildrenEntities('TILL') as $child_till) {
                    $till_ids[] = $child_till;
                }
                foreach ($org_model->getChildrenEntities('STR_SERVER') as $child_till) {
                    $till_ids[] = $child_till;
                }
            }
        }
        //die("bye");
        if (!empty($till_ids))
            $transactionFilters->entered_by_ids = $till_ids;
        else if (!empty($till_codes) || !empty($store_codes))
            throw new Exception(ErrorMessage::$transaction["ERR_INVALID_STORE_TILL_CODE"], ErrorCodes::$transaction["ERR_INVALID_STORE_TILL_CODE"]);

        try {
            if ($type == 'REGULAR')
                $transactions = LoyaltyTransaction::loadAll($this->org_id, $transactionFilters, $batch_size);
            else if ($type == 'NOT_INTERESTED')
                $transactions = NotInterestedTransaction::loadAll($this->org_id, $transactionFilters, $batch_size);
            else if ($type == 'RETURN')
                $transactions = ReturnedTransaction::loadAll($this->org_id, $transactionFilters, $batch_size);
        } catch (Exception $e) {
            throw new Exception(ErrorMessage::$transaction["ERR_NO_TRANSACTION_RETRIEVED"], ErrorCodes::$transaction["ERR_NO_TRANSACTION_RETRIEVED"]);
        }
        foreach ($transactions as $transaction) {
            $item = array();
            $item['id'] = $transaction->getTransactionId();
            $transaction_id = $transaction->getTransactionId();
            $item['number'] = $transaction->getTransactionNumber();
            $item['type'] = $type;
            $item['outlier_status'] = $transaction->getOutlierStatus();
            $item['delivery_status'] = !$transaction->getDeliveryStatus() ?
                'DELIVERED' : $transaction->getDeliveryStatus();
            $item['amount'] = $transaction->getTransactionAmount();
            $item['notes'] = $transaction->getNotes();
            $item['billing_time'] = $transaction->getTransactionDate();
            $item['gross_amount'] = $transaction->getGrossAmount();
            $item['discount'] = $transaction->getDiscount();

            $org_model = new OrgEntityModelExtension();
            $org_model->load($transaction->getStoreId());
            $item['store'] = $org_model->getCode();

            $till_id = $transaction->getStoreId();
            $storeTillController = new ApiStoreTillController();
            $store_info = $storeTillController->getInfoDetails($till_id);
            $this->logger->debug("@@@diablo *new store_info* : " . print_r($store_info, true));

            $item['billing_till']['code'] = $store_info[0]['code'];
            $item['billing_till']['name'] = $store_info[0]['name'];
            $item['billing_store']['code'] = $store_info[0]['parent_code'];
            $item['billing_store']['name'] = $store_info[0]['parent_store'];
            // CUSTOMER

            if ($type != "NOT_INTERESTED") {
                $customer = array();
                $transaction->loadCustomer();
                if ($include_user_id)
                    $customer["id"] = $transaction->customer->getUserId();
                $customer["email"] = $transaction->customer->getEmail();
                $customer["mobile"] = $transaction->customer->getMobile();
                $customer["external_id"] = $transaction->customer->getExternalId();
                $customer["first_name"] = $transaction->customer->getFirstName();
                $customer["last_name"] = $transaction->customer->getLastName();
                $item["customer"] = $customer;
            }
            // CUSTOM FIELDS
            $cm = new CustomFields();
            $scope = "loyalty_transaction";
            $custom_fields = $cm->getCustomFieldValuesByAssocId($this->org_id, $scope, $transaction->getTransactionId());
            $new_custom_fields = array();

            if (is_array($custom_fields)) {
                foreach ($custom_fields as $cf_name => $cf_value) {
                    $temp_field = array();
                    $temp_field['name'] = $cf_name;
                    $temp_field['value'] = $cf_value;
                    array_push($new_custom_fields, $temp_field);
                }
            }

            $item['custom_fields']['field'] = $new_custom_fields;

            // Payment details

            if ($credit_notes && $type != 'NOT_INTERESTED') {
                try {
                    $this->logger->debug("Fetching Credit Notes");
                    $credit_note = array();
                    $filter = new CreditNoteFilters();
                    $filter->ref_id = $transaction_id;
                    if ($type == 'REGULAR') {
                        $filter->ref_type = "LOYALTY_LOG";
                    } else if ($type == 'RETURN') {
                        $filter->ref_type = "RETURNED";
                    }
                    $credit_notes = CreditNote::loadAll($this->currentorg->org_id, $filter);

                    $item['credit_notes'] = array();
                    foreach ($credit_notes as $note) {
                        $credit_note = $note->toArray();
                        $item['credit_notes']['credit_note'][] = array('amount' => $credit_note['amount'], 'number' => $credit_note['number'], 'notes' => $credit_note['notes']);
                    }
                    $this->logger->debug("Successfully fetched the credit notes");
                } catch (Exception $e) {
                    $item['credit_notes'] = array();
                    $this->logger->debug("Credit Notes not Found for the transaction");
                }
            }

            if ($should_return_tenders && $type != 'RETURN') {
                try {
                    $this->logger->debug("Successfully fetched the payment details");
                    $filters = new PaymentModeLoadFilters();
                    $filters->ref_type = $type;
                    $filters->ref_id = $transaction_id;
                    $tenders = PaymentModeDetails::loadAll($this->currentorg->org_id, $filters, 100, 0, true);
                    $item['tenders']['tender'] = array();
                    foreach ($tenders as $tender) {
                        try {
                            $tender->loadPaymentModeAttributeValues();
                            foreach ($tender->getPaymentModeAttributeValues() as $attr_values) {
                                $attr_values->getOrgPaymentModeAttributeObj(true);
                            }
                        } catch (Exception $e) {
                            $this->logger->debug("Attribute and Attribute values not found for payment details");
                        }
                        $attr_arr = array();
                        foreach ($tender->getPaymentModeAttributeValues() as $paymentModeAttributeValues) {
                            $attr_arr[] = array('name' => $paymentModeAttributeValues->getOrgPaymentModeAttributeObj()->getName(), 'value' => $paymentModeAttributeValues->getValue());;
                        }
                        $tender_arr = array("name" => $tender->orgPaymentModeObj->getLabel(), "value" => $tender->getAmount());
                        $tender_arr["attributes"]["attribute"] = $attr_arr;
                        $item['tenders']['tender'][] = $tender_arr;
                    }
                    $this->logger->debug("Successfully fetched the payment details");
                } catch (ApiPaymentModeException $e) {
                    $this->logger->debug("Could not find payment details");
                    $item['tenders']['tender'] = array();
                }
            }

            // Line items

            if ($type == 'REGULAR')
                $loyalty_bill_lineitems_row = $this->loyaltyController->getBillAndLineitemDetails($transaction_id);
            else if ($type == 'NOT_INTERESTED')
                $loyalty_bill_lineitems_row = $this->loyaltyController->getNotInterestedBillAndLineitemDetails($transaction_id);
            else if ($type == 'RETURN')
                $loyalty_bill_lineitems_row = $this->loyaltyController->getReturnedBillAndLineitemDetails($transaction_id);
            $inventoryController = new ApiInventoryController();
            $item['line_items']['line_item'] = array();
            if (count($loyalty_bill_lineitems_row) > 0) {
                $item_codes = array();
                foreach ($loyalty_bill_lineitems_row as $key => $row) {
                    $line_item = array();
                    $line_item['type'] = 'REGULAR';
                    $line_item['outlier_status'] = $row['outlier_status'];
                    $line_item['serial'] = $row['serial'];
                    $line_item['item_code'] = $row['item_code'];
                    $line_item['description'] = $row['description'];
                    $line_item['qty'] = $row['qty'];
                    $line_item['rate'] = $row['rate'];
                    $line_item['value'] = $row['value'];
                    $line_item['discount'] = $row['discount_value'];
                    $line_item['amount'] = $row['amount'];
                    try {

                        $productDetails = $inventoryController->getProductBySku("\"" . $row['item_code'] . "\"");
                        $line_item['img_url'] = array("@cdata" => $productDetails['img_url']);
                    } Catch (Exception $e) {

                        $this->logger->error("Exception in fetching inventory information for product with sku: " . $row['item_code']);
                        $line_item['img_url'] = '';
                    }
                    $line_item['attributes']['attribute'] = array();
                    $item_codes[] = "'" . $row['item_code'] . "'";
                    array_push($item['line_items']['line_item'], $line_item);
                }

                $attributes = $this->loyaltyController->getAttributesForItems($item_codes);

                foreach ($item['line_items']['line_item'] as $key => $line_item) {
                    $temp_attr = $attributes[$line_item['item_code']];
                    if (count($temp_attr) > 0) {
                        $item_attributes = array();
                        foreach ($temp_attr as $attr_name => $attr_value) {
                            $item_attributes[] = array('name' => $attr_name, 'value' => $attr_value);
                        }
                        $item['line_items']['line_item'][$key]['attributes']['attribute'] = $item_attributes;
                    }
                }
            }

            if ($type == 'REGULAR') {
                // getting the return items
                $returnTransactionsArr = $this->loyaltyController->getReturnsInLoyaltyTransaction($user->user_id, $transaction_id);
                $currency_ratio = $this->getCurrencyRatio($returnTransactionsArr[0]["return_bill_id"], "RETURN");
                #here get currency ratio
                $currency = null;
                if ($currency_ratio) {
                    $currency = array("ratio" => $currency_ratio["ratio"],
                        "id" => $currency_ratio["transaction_currency"]["supported_currency_id"],
                        "name" => $currency_ratio["transaction_currency"]["name"],
                        "symbol" => $currency_ratio["transaction_currency"]["symbol"],
                    );
                }
                if ($returnTransactionsArr)
                    $item['type'] = 'MIXED';
                foreach ($returnTransactionsArr as $retTransaction) {
                    switch (strtoupper($retTransaction['type'])) {

                        case 'AMOUNT':

                            $line_item = array();
                            $line_item['type'] = 'RETURN';
                            $line_item['return_type'] = strtoupper($retTransaction['type']);
                            $line_item['amount'] = $retTransaction['bill_amount'];
                            $line_item['transaction_number'] = $retTransaction['bill_number'];
                            //Extra added
                            $line_item['serial'] = "";
                            $line_item['item_code'] = "";
                            $line_item['description'] = "[RETURN]";
                            $line_item['qty'] = "";
                            $line_item['rate'] = "";
                            $line_item['value'] = "";
                            $line_item['discount'] = "";
                            $line_item['attributes'] = array("attribute" => "");
                            if ($currency)
                                $line_item["currency"] = $currency;
                            array_push($item['line_items']['line_item'], $line_item);
                            break;

                        case 'FULL':
                            if (count($retTransaction['lineitems']) == 0) {
                                $line_item = array();
                                $line_item['type'] = 'RETURN';
                                $line_item['return_type'] = strtoupper($retTransaction['type']);
                                $line_item['amount'] = $retTransaction['bill_amount'];
                                $line_item['transaction_number'] = $retTransaction['bill_number'];
                                //Extra added elements
                                $line_item['serial'] = "";
                                $line_item['item_code'] = "";
                                $line_item['description'] = "[RETURN]";
                                $line_item['qty'] = "";
                                $line_item['rate'] = "";
                                $line_item['value'] = "";
                                $line_item['discount'] = "";
                                $line_item['attributes'] = array("attribute" => "");
                                if ($currency)
                                    $line_item["currency"] = $currency;
                                array_push($item['line_items']['line_item'], $line_item);
                                break;
                            }

                        case 'LINE_ITEM':

                            foreach ($retTransaction["lineitems"] as $lineitem) {
                                $line_item = array();
                                $line_item['type'] = 'RETURN';
                                $line_item['return_type'] = strtoupper($retTransaction['type']);
                                $line_item['serial'] = $lineitem['serial'];
                                $line_item['discount'] = $lineitem['lineitem_discount_value'];
                                $line_item['amount'] = $lineitem['lineitem_amount'];

                                $line_item['item_code'] = $lineitem['item_code'];
                                $line_item['description'] = "[RETURN] " . $lineitem['description'];
                                $line_item['qty'] = $lineitem['qty'];
                                $line_item['rate'] = $lineitem['rate'];
                                $line_item['value'] = $lineitem['value'];
                                $line_item['transaction_number'] = $retTransaction['bill_number'];
                                if ($currency)
                                    $line_item["currency"] = $currency;
                                array_push($item['line_items']['line_item'], $line_item);
                            }
                            break;
                    }
                }
            }

            $currency_ratio = $this->getCurrencyRatio($item["id"], $item["type"]);
            #here get currency ratio
            if ($currency_ratio) {
                $curreny = array("ratio" => $currency_ratio["ratio"],
                    "id" => $currency_ratio["transaction_currency"]["supported_currency_id"],
                    "name" => $currency_ratio["transaction_currency"]["name"],
                    "symbol" => $currency_ratio["transaction_currency"]["symbol"],
                );
                $item["currency"] = $curreny;
            }

            $item['item_status'] = array(
                "success" => "true",
                "code" => ErrorCodes::$transaction["ERR_GET_SUCCESS"],
                "message" => ErrorMessage::$transaction["ERR_GET_SUCCESS"]
            );
            $result[] = $item;
        }

        return $result;
    }

    public function getBills($transaction_id, $credit_notes = false, $should_return_tenders = false)
    {

        $transaction = array();
        $transaction['transaction_id'] = $transaction_id;

        $loyalty_log_row = $this->loyaltyController->getBill($transaction_id);

        $transaction['transaction_number'] = $loyalty_log_row['bill_number'];

        $transaction['type'] = 'REGULAR';
        $transaction['outlier_status'] = $loyalty_log_row['outlier_status'];
        $transaction['delivery_status'] = is_null($loyalty_log_row['delivery_status']) ?
            'DELIVERED' : $loyalty_log_row['delivery_status'];
        if ($this->isBillReturned($transaction_id, $loyalty_log_row['user_id']))
            $transaction['is_returned'] = true;

        $user = UserProfile::getById($loyalty_log_row['user_id']);
        $user->external_id = UserProfile::getExternalId($loyalty_log_row['user_id']);
        $user_hash = array(
            "user_id" => $user->user_id,
            "email" => $user->email,
            "mobile" => $user->mobile,
            "external_id" => $user->external_id,
            "firstname" => $user->first_name,
            "lastname" => $user->last_name,
            "source" => $user->source
        );
        $transaction['customer'] = $user_hash;
        $transaction['item_status'] = array();
        $transaction['amount'] = $loyalty_log_row['bill_amount'];
        $transaction['notes'] = $loyalty_log_row['notes'];
        $transaction['billing_time'] = $loyalty_log_row['date'];
        $transaction['gross_amount'] = $loyalty_log_row['bill_gross_amount'];
        $transaction['discount'] = $loyalty_log_row['bill_discount'];
        $transaction['store'] = $loyalty_log_row['store'];
        $transaction['source'] = $loyalty_log_row['source'];

        $this->logger->debug("@@@diablo *till_id format* : " . print_r($loyalty_log_row['till_id'], true));
        $till_id = $loyalty_log_row['till_id'];
        $storeTillController = new ApiStoreTillController();
        $store_info = $storeTillController->getInfoDetails($till_id);

        $transaction['billing_till']['code'] = $store_info[0]['code'];
        $transaction['billing_till']['name'] = $store_info[0]['name'];
        $transaction['billing_store']['code'] = $store_info[0]['parent_code'];
        $transaction['billing_store']['name'] = $store_info[0]['parent_store'];

        $cm = new CustomFields();
        $scope = "loyalty_transaction";
        $custom_fields = $cm->getCustomFieldValuesByAssocId($this->org_id, $scope, $loyalty_log_row['id']);
        $new_custom_fields = array();

        if (is_array($custom_fields)) {
            foreach ($custom_fields as $cf_name => $cf_value) {
                $temp_field = array();
                $temp_field['name'] = $cf_name;
                $temp_field['value'] = $cf_value;
                array_push($new_custom_fields, $temp_field);
            }
        }

        $transaction['custom_fields']['field'] = $new_custom_fields;
        if ($credit_notes) {
            try {
                $this->logger->debug("Fetching Credit Notes");
                $credit_note = array();
                $filter = new CreditNoteFilters();
                $filter->ref_id = $transaction_id;
                $credit_notes = CreditNote::loadAll($this->currentorg->org_id, $filter);
                $transaction['credit_note'] = array();
                foreach ($credit_notes as $note) {
                    $transaction['credit_note'][] = $note->toArray();
                }
                $this->logger->debug("Successfully fetched the credit notes");
            } catch (Exception $e) {
                $transaction['credit_notes'] = array();
                $this->logger->debug("Credit Notes not Found for the transaction");
            }
        }

        if ($should_return_tenders) {
            try {
                $this->logger->debug("Successfully fetched the payment details");
                $filters = new PaymentModeLoadFilters();
                $filters->ref_id = $transaction_id;
                $filters -> ref_type = $transaction['type'];
                $tenders = PaymentModeDetails::loadAll($this->currentorg->org_id, $filters, 100, 0, true);
                $transaction['tender'] = array();
                foreach ($tenders as $tender) {
                    try {
                        $tender->loadPaymentModeAttributeValues();
                        foreach ($tender->getPaymentModeAttributeValues() as $attr_values) {
                            $attr_values->getOrgPaymentModeAttributeObj(true);
                        }
                    } catch (Exception $e) {
                        $this->logger->debug("Attribute and Attribute values not found for payment details");
                    }
                    $transaction['tender'][] = $tender->toArray();
                }
                $this->logger->debug("Successfully fetched the payment details");
            } catch (ApiPaymentModeException $e) {
                $this->logger->debug("Could not find payment details");
                $transaction['tenders'] = array();
            }
        }

        if (count($loyalty_log_row) <= 0)
            throw new Exception("ERR_INVALID_ID");
        else {
            $loyalty_bill_lineitems_row = $this->loyaltyController->getBillAndLineitemDetails($transaction_id);
            $inventoryController = new ApiInventoryController();
            $transaction['line_items']['line_item'] = array();
            if (count($loyalty_bill_lineitems_row) > 0) {
                $item_codes = array();
                foreach ($loyalty_bill_lineitems_row as $key => $row) {
                    $line_item = array();
                    $line_item['type'] = 'REGULAR';
                    $line_item['outlier_status'] = $row['outlier_status'];
                    $line_item['serial'] = $row['serial'];
                    $line_item['item_code'] = $row['item_code'];
                    $line_item['description'] = $row['description'];
                    $line_item['qty'] = $row['qty'];
                    $line_item['rate'] = $row['rate'];
                    $line_item['value'] = $row['value'];
                    $line_item['discount'] = $row['discount_value'];
                    $line_item['amount'] = $row['amount'];
                    try {

                        $productDetails = $inventoryController->getProductBySku("\"" . $row['item_code'] . "\"");
                        $line_item['img_url'] = array("@cdata" => $productDetails['img_url']);
                    } Catch (Exception $e) {

                        $this->logger->error("Exception in fetching inventory information for product with sku: " . $row['item_code']);
                        $line_item['img_url'] = '';
                    }
                    $line_item['attributes']['attribute'] = array();
                    $item_codes[] = "'" . $row['item_code'] . "'";
                    array_push($transaction['line_items']['line_item'], $line_item);
                }
            }
            if ($transaction['type'] == 'REGULAR') {
                // getting the return items
                $returnTransactionsArr = $this->loyaltyController->getReturnsInLoyaltyTransaction($user->user_id, $transaction_id);
                $currency_ratio = $this->getCurrencyRatio($returnTransactionsArr[0]["return_bill_id"], "RETURN");
                #here get currency ratio
                $currency = null;
                if ($currency_ratio) {
                    $currency = array("ratio" => $currency_ratio["ratio"],
                        "id" => $currency_ratio["transaction_currency"]["supported_currency_id"],
                        "name" => $currency_ratio["transaction_currency"]["name"],
                        "symbol" => $currency_ratio["transaction_currency"]["symbol"],
                    );
                }
                if ($returnTransactionsArr)
                    $transaction['type'] = 'MIXED';
                foreach ($returnTransactionsArr as $retTransaction) {
                    switch (strtoupper($retTransaction['type'])) {

                        case 'AMOUNT':

                            $line_item = array();
                            $line_item['type'] = 'RETURN';
                            $line_item['return_type'] = strtoupper($retTransaction['type']);
                            $line_item['amount'] = $retTransaction['bill_amount'];
                            $line_item['transaction_number'] = $retTransaction['bill_number'];
                            //Extra added
                            $line_item['serial'] = "";
                            $line_item['item_code'] = "";
                            $line_item['description'] = "[RETURN]";
                            $line_item['qty'] = "";
                            $line_item['rate'] = "";
                            $line_item['value'] = "";
                            $line_item['discount'] = "";
                            $line_item['attributes'] = array("attribute" => "");
                            if ($currency)
                                $line_item["currency"] = $currency;
                            array_push($transaction['line_items']['line_item'], $line_item);
                            break;

                        case 'FULL':
                            if (count($retTransaction['lineitems']) == 0) {
                                $line_item = array();
                                $line_item['type'] = 'RETURN';
                                $line_item['return_type'] = strtoupper($retTransaction['type']);
                                $line_item['amount'] = $retTransaction['bill_amount'];
                                $line_item['transaction_number'] = $retTransaction['bill_number'];
                                //Extra added elements
                                $line_item['serial'] = "";
                                $line_item['item_code'] = "";
                                $line_item['description'] = "[RETURN]";
                                $line_item['qty'] = "";
                                $line_item['rate'] = "";
                                $line_item['value'] = "";
                                $line_item['discount'] = "";
                                $line_item['attributes'] = array("attribute" => "");
                                if ($currency)
                                    $line_item["currency"] = $currency;
                                array_push($transaction['line_items']['line_item'], $line_item);
                                break;
                            }

                        case 'LINE_ITEM':

                            foreach ($retTransaction["lineitems"] as $lineitem) {
                                $line_item = array();
                                $line_item['type'] = 'RETURN';
                                $line_item['return_type'] = strtoupper($retTransaction['type']);
                                $line_item['serial'] = $lineitem['serial'];
                                $line_item['discount'] = $lineitem['lineitem_discount_value'];
                                $line_item['amount'] = $lineitem['lineitem_amount'];

                                $line_item['item_code'] = $lineitem['item_code'];
                                $line_item['description'] = "[RETURN] " . $lineitem['description'];
                                $line_item['qty'] = $lineitem['qty'];
                                $line_item['rate'] = $lineitem['rate'];
                                $line_item['value'] = $lineitem['value'];
                                $line_item['transaction_number'] = $retTransaction['bill_number'];
                                if ($currency)
                                    $line_item["currency"] = $currency;
                                array_push($transaction['line_items']['line_item'], $line_item);
                            }
                            break;
                    }
                }

                $attributes = $this->loyaltyController->getAttributesForItems($item_codes);

                foreach ($transaction['line_items']['line_item'] as $key => $line_item) {
                    $temp_attr = $attributes[$line_item['item_code']];
                    if (count($temp_attr) > 0) {
                        $item_attributes = array();
                        foreach ($temp_attr as $attr_name => $attr_value) {
                            $item_attributes[] = array('name' => $attr_name, 'value' => $attr_value);
                        }
                        $transaction['line_items']['line_item'][$key]['attributes']['attribute'] = $item_attributes;
                    }
                }
            }
        }
        return $transaction;
    }


    public function isBillReturned($transaction_id, $user_id = -1)
    {
        if ($this->user->user_id > 0)
            $user_id = $this->user->user_id;
        if(user_id <= 0)
            return false;
        $sql = "SELECT `id` FROM `returned_bills` WHERE `loyalty_log_id` = '$transaction_id' AND `org_id` = $this->org_id AND `user_id` = " . $user_id;
        return $this->db->query_firstrow($sql);
    }

    public static function isReturnedTransaction($bill_number, $org_id, $user_id)
    {
        //Please Check the query for user_id
        $db = new Dbase("users");
        $sql = "SELECT `id` FROM `returned_bills` WHERE `bill_number` = '$bill_number' AND `org_id` = $org_id AND `user_id` = $user_id";
        return $db->query_firstrow($sql);
    }

    public static function isNotInterestedBillDuplicate($bill_number)
    {

        global $currentorg;
        $org_id = $currentorg->org_id;

        $safe_bill_number = Util::mysqlEscapeString($bill_number);
        $sql = "SELECT COUNT(*) FROM `loyalty_not_interested_bills`
				WHERE bill_number = '$safe_bill_number'
				AND org_id = $org_id";

        $db = new Dbase("users");
        $count = $db->query_scalar($sql);
        return $count > 0;

    }

    public function getIssuedCouponsForTransactions(array $transaction_ids)
    {
        if (!is_array($transaction_ids))
            return null;
        $str_transaction_ids = implode(",", $transaction_ids);
        $org_id = $this->currentorg->org_id;

        $sql = "SELECT ll.id AS transaction_id , v.* FROM loyalty_log AS ll 
			 		JOIN $this->campaign_db_name.voucher AS v 
						ON v.org_id = ll.org_id 
						AND v.bill_number = ll.bill_number
						AND v.issued_to = ll.user_id
					WHERE ll.org_id = $org_id
						AND ll.bill_number IS NOT NULL 
						AND ll.id IN ($str_transaction_ids)";

        $coupons = array();

        $rows = $this->db->query($sql);

        if ($rows && is_array($rows)) {
            foreach ($rows as $row) {
                if (!isset($coupons[$row['transaction_id']])) {
                    $coupons[$row['transaction_id']] = array();
                }

                $coupons[$row['transaction_id']][] = $row;
            }
            return $coupons;
        } else
            return null;
    }

    public function getRedeemedCouponsForTransactions($transaction_ids)
    {
        if (!is_array($transaction_ids))
            return null;
        $str_transaction_ids = implode(",", $transaction_ids);
        $org_id = $this->currentorg->org_id;

        $sql = "SELECT ll.id AS transaction_id , vr.* FROM loyalty_log AS ll
					JOIN $this->campaign_db_name.voucher_redemptions AS vr
						ON vr.org_id = ll.org_id
						AND vr.bill_number = ll.bill_number
						AND vr.used_by = ll.user_id
					WHERE ll.org_id = $org_id
						AND ll.bill_number IS NOT NULL
						AND ll.id IN ($str_transaction_ids)";

        $coupons = array();

        $rows = $this->db->query($sql);

        if ($rows && is_array($rows)) {
            foreach ($rows as $row) {
                if (!isset($coupons[$row['transaction_id']])) {
                    $coupons[$row['transaction_id']] = array();
                }

                $coupons[$row['transaction_id']][] = $row;
            }
            return $coupons;
        } else
            return null;
    }

    /**
     * @param $bill_nos
     * @return array|null - list of transactions by bill numbers
     */
    public function getRedeemedCouponsForTransactionsByBillNos($identifier_type, $identifier_value, $bill_nos)
    {
        $customer_params[$identifier_type] = $identifier_value;
        $user = UserProfile::getByData($customer_params);
        $status = $user->load(true);

        if (!$status || $user->user_id < 0)
            throw new Exception("ERR_USER_NOT_REGISTERED");
        $user_id = $user->user_id;

        $this->logger->debug('getRedeemedCouponsForTransactionsByBillNos input bill_nos: ' . print_r($bill_nos, true));
        if (!is_array($bill_nos) || count($bill_nos) == 0)
            return null;
        $bill_nos = Util::mysqlEscapeArray($bill_nos);
        $str_bill_array = array();
        foreach ($bill_nos as $bill_no)
            array_push($str_bill_array, "'" . $bill_no . "'");
        $str_bill_nos = implode(',', $str_bill_array);
        $org_id = $this->currentorg->org_id;

        $sql = "SELECT ll.id AS transaction_id , ll.bill_number as bill_number,
                vr.org_id, vr.voucher_id, vr.used_at_store as store, vr.used_date, vr.bill_amount,
                vs.id, vs.description, vs.discount_type as type, vs.discount_value as discount
                    FROM loyalty_log AS ll
					JOIN $this->campaign_db_name.voucher_redemptions AS vr
						ON vr.org_id = ll.org_id
						AND vr.bill_number = ll.bill_number
						AND vr.used_by = ll.user_id
					JOIN $this->campaign_db_name.voucher_series as vs
					    ON vr.org_id = vs.org_id
					    AND vr.voucher_series_id = vs.id
					WHERE ll.org_id = $org_id AND ll.user_id = $user_id
						AND ll.bill_number IN ($str_bill_nos)";

        $coupons = array();

        $rows = $this->db->query($sql);

        if ($rows && is_array($rows)) {
            foreach ($rows as $row) {
                if (!isset($coupons[strtolower($row['bill_number'])])) {
                    $coupons[strtolower($row['bill_number'])] = array();
                }
                $series = array('id' => $row['id'],
                    'description' => $row['description'],
                    'type' => $row['type'],
                    'discount' => $row['discount']);
                $coupon = array('transaction_id' => $row['transaction_id'],
                    'org_id' => $row['org_id'],
                    'series' => $series,
                    'voucher_id' => $row['voucher_id'],
                    'store' => $row['store'],
                    'used_date' => $row['used_date'],
                    'bill_amount' => $row['bill_amount']);
                array_push($coupons[strtolower($row['bill_number'])], $coupon);
            }
            return $coupons;
        } else
            return null;
    }

    public function getRedeemedPointsForTxnIds($identifier_type, $identifier_value, $txn_ids)
    {
        $customer_params[$identifier_type] = $identifier_value;

        $user = UserProfile::getByData($customer_params);
        $status = $user->load(true);
        $str_txn_ids = implode(',', $txn_ids);

        if (!$status || $user->user_id < 0)
            throw new Exception("ERR_USER_NOT_REGISTERED");

        $user_id = $user->user_id;
        $sql = "SELECT ll.redeemed AS redeemed_points FROM loyalty_log as ll WHERE ll.org_id = $this->org_id AND
                ll.user_id = $user_id AND ll.id in ($str_txn_ids)";
        $result = $this->db->query_firstrow($sql);
        if ($result) {
            $res = $result['redeemed_points'];
            $this->logger->debug("getRedeemedPointsForTxn : Result : " . $res);
            return $res;
        }
        return null;
    }

    public function getRedeemedPointsForBillNos($identifier_type, $identifier_value, $bill_nos)
    {
        $customer_params[$identifier_type] = $identifier_value;

        $user = UserProfile::getByData($customer_params);
        $status = $user->load(true);

        if (!$status || $user->user_id < 0)
            throw new Exception("ERR_USER_NOT_REGISTERED");

        $user_id = $user->user_id;
        $bill_nos = Util::mysqlEscapeArray($bill_nos);
        $str_bill_array = array();
        foreach ($bill_nos as $bill_no)
            array_push($str_bill_array, $bill_no);
        //$str_bill_nos = implode(',', $str_bill_array);

        $pesC = new PointsEngineServiceController();
        try {
            $redemptionArr = $pesC->getPointsRedemptionOfBillNumber($user->user_id, $bill_no);
        } catch (Exception $e) {
            $this->logger->debug("Failed to get redemption log" . $e->getMessage());
            throw new Exception("ERR_IN_POINTS_ENGINE");
        }

        $transactions = array();
        foreach ($redemptionArr as $redemption) {
            $transactions[$redemptionArr["bill_number"]] = $redemptionArr["points_redeemed"];
        }
        return $transactions;
    }

    /**
     * checks for array of coupons passed if they are redeemable
     * in case is_transactional is true, if any of the coupon is non redeemable, rest all are not checked
     */
    public function areCouponsRedeemable($coupon_info, $is_transactional = false)
    {
        $this->logger->debug("areCouponsRedeemable input : " . print_r($coupon_info, true));
        $this->logger->debug("areCouponsRedeemable isTransactional : " . $is_transactional);
        $coupon_params = array();
        $coupon_params['mobile'] = $coupon_info['mobile'];
        $coupon_params['email'] = $coupon_info['email'];
        $coupon_params['external_id'] = $coupon_info['external_id'];

        $coupon_res = new CouponResource();
        $coupon_list = array();
        $are_coup_redeemable = true; // returns status of all coupons. and is false if ANY of the coupon is non redeemable
        $item_status = array();

        if (isset($coupon_info['coupons']['coupon']['code'])) {
            $coupon_info['coupons']['coupon'] = array($coupon_info['coupons']['coupon']);
        }

        $failed_tx = -1;
        for ($i = 0; $i < sizeof($coupon_info['coupons']['coupon']); $i++) {
            if (!$are_coup_redeemable && $is_transactional > 0) {
                $this->logger->debug('previous txn failed. so setting this items status as fail');
                $item_status = array('status' => false,
                    'code' => ErrorCodes::getTransactionErrorCode('ERR_PREV_COUPONS_NON_REDEEMABLE'),
                    'message' => ErrorMessage::getTransactionErrorMessage('ERR_PREV_COUPONS_NON_REDEEMABLE'));
            } else {
                $coupon_params['code'] = $coupon_info['coupons']['coupon'][$i]['code'];
                $this->logger->debug("checking is reDeemable coupon, input : " . print_r($coupon_params, true));
                $coupon_isredeemable = $coupon_res->process('v1.1', 'isredeemable', '', $coupon_params, '');
                $this->logger->debug("is reDeemable coupon, output : " . print_r($coupon_isredeemable, true));
                $item_status = $coupon_isredeemable['coupons']['redeemable']['item_status'];
            }
            if (($item_status['status'] == false || $item_status['status'] == 'false') && $are_coup_redeemable == true) {
                $are_coup_redeemable = false; // sets in case any one of coupon is non redeemable to figure out processing in txnal case
                $failed_tx = $i;
            }

            $coupon_status = $coupon_info['coupons']['coupon'][$i];
            $coupon_status['item_status'] = $item_status;
            array_push($coupon_list, $coupon_status);
        }

        $this->logger->debug('Failed item : ' . $failed_tx);
        $this->logger->debug('before setting coupon_list : ' . print_r($coupon_list, true));

        if ($are_coup_redeemable == false && $is_transactional > 0) {
            for ($i = 0; $i < $failed_tx; $i++) {
                $this->logger->debug('Setting items status as fail for initial txns');
                $coupon_list[$i]['item_status'] = array('status' => false,
                    'code' => ErrorCodes::getTransactionErrorCode('ERR_PREV_COUPONS_NON_REDEEMABLE'),
                    'message' => ErrorMessage::getTransactionErrorMessage('ERR_PREV_COUPONS_NON_REDEEMABLE'));
            }
        }


        $response['all_redeemable'] = $are_coup_redeemable;
        $response['coupons']['coupon'] = $coupon_list;
        return $response;
    }

    public function redeemMultipleCoupons($coupons, $customer, $bill_no)
    {
        $coupons_res = new CouponResource();
        $coupon_list = array();
        $coupon_redemptions = array();
        $req_count = 0;
        $suc_count = 0;

        if (isset($coupons['coupons']['coupon']['code'])) {
            $coupons['coupons']['coupon'] = array($coupons['coupons']['coupon']);
        }

        for ($i = 0; $i < sizeof($coupons['coupons']['coupon']); $i++) {
            $cd['root']['coupon'][0]['validation_code'] = $coupons['coupons']['coupon'][$i]['validation_code'];
            $cd['root']['coupon'][0]['code'] = $coupons['coupons']['coupon'][$i]['code'];
            $cd['root']['coupon'][0]['customer'] = $customer;
            $cd['root']['coupon'][0]['transaction']['number'] = $bill_no;

            $this->logger->debug('redeemMultipleCoupons redeem input : ' . print_r($cd, true));

            $cr = $coupons_res->process('v1.1', 'redeem', $cd, '', '');

            $this->logger->debug('redeemMultipleCoupons redeem output : ' . print_r($cr, true));;
            if (($cr['status']['success'] == 'false') || ($cr['status']['success'] == false)) {
                $this->logger->debug('this coupon failed');
            } else {
                $this->logger->debug('this coupon successful');
                $suc_count++;
            }
            $req_count++;
            $item_status = array('status' => $cr['status']['success'],
                'code' => $cr['coupons']['coupon']['item_status']['code'],
                'message' => $cr['coupons']['coupon']['item_status']['message']);
            $coupon_status = $coupons['coupons']['coupon'][$i];
            $coupon_status['item_status'] = $item_status;
            array_push($coupon_list, $coupon_status);
        }
        $coupon_redemptions['coupons']['coupon'] = $coupon_list;
        $this->logger->debug('suc_count : ' . $suc_count . ' req_count : ' . $req_count);
        if ($suc_count === $req_count) {
            $coupon_redemptions['all_suc'] = true;
            $coupon_redemptions['some_suc'] = true;
        } else if ($suc_count > 0) {
            $coupon_redemptions['all_suc'] = false;
            $coupon_redemptions['some_suc'] = true;
        } else {
            $coupon_redemptions['all_suc'] = false;
            $coupon_redemptions['some_suc'] = false;
        }
        return $coupon_redemptions;
    }

    /**
     * saves AssociateActivity
     * @param unknown_type $associate_details
     */
    private function saveAssociateActivity($associate_details)
    {
        $associate_code = $associate_details['code'];
        $associate_name = $associate_details['name'];

        $ref_id = $this->id;

        if ($this->bill_type == 'regular') {
            $description = "Added Bill, Bill Number: $this->bill_number";
            $activity_type = ASSOCIATE_ACTIVITY_TRANSACTION_ADD;
        } else {
            $description = "Returned Bill, Bill Number: $this->bill_number";
            $activity_type = ASSOCIATE_ACTIVITY_TRANSACTION_RETURN;
        }

        if (empty($associate_code)) {
            $this->logger->debug("Associate Code is Blank not adding Activity");
            return;
        }

        $associate_model_extension = new ApiAssociateModelExtension();
        $associate_model_extension->loadFromCode($associate_code);
        if ($associate_model_extension->getId()) {
            if ($associate_model_extension->assocBelongsToCurrentStore($associate_model_extension->getId(), $this->currentuser->user_id)) {
                $associate_activity_id = $associate_model_extension->
                saveActivity($activity_type,
                    $description,
                    $ref_id,
                    Util::getMysqlDateTime($this->date));
            } else
                $assoc_activity_id = 0;
        }


        if ($associate_activity_id > 0) {
            $this->logger->debug("Associate Activity Added Successfully");
        } else {
            $this->logger->error("Associate Activity can't be added");
        }
    }

    private function updateLineItemReference()
    {
        return;
    }

    //adds customer to solr index which can later be used to search customer
    private function pushCustomerToSolr($user)
    {
        $doc = array('pkey' => $user->user_id . '_' . $user->org_id, 'user_id' => $user->user_id,
            'org_id' => $user->org_id, 'firstname' => $user->first_name,
            'lastname' => $user->last_name, 'email' => $user->email, 'external_id' => $user->external_id,
            'mobile' => $user->mobile, 'loyalty_points' => $user->loyalty_points,
            'lifetime_purchases' => $user->lifetime_purchases, 'lifetime_points' => $user->lifetime_points,
            'slab' => $user->slab_name, 'registered_store' => $user->registered_by,
            'registered_date' => date('Y-m-d\TH:i:s\Z', strtotime($user->registered_on)), 'last_trans_value' => 0);

        $this->logger->debug("Adding doc to solr ");
        try {
            include_once 'apiHelper/search/CustomerSearchClient.php';
            $search_client = new CustomerSearchClient();
            $search_result = $search_client->addDocument($doc);
            $this->logger->debug("Result of solr add : ");
        } catch (Exception $e) {
            $this->logger->debug("Failed to add document to solr ");
            $this->logger->debug($e->getMessage());
        }

        $this->logger->debug("Adding user : " . $user->user_id . " from org " . $user->org_id . " to beanstalk");
        $input = array('org_id' => $user->org_id, 'user_ids' => array(strval($user->user_id)));
        try {
            $client = new AsyncClient("customer", "customersearchtube");
            $payload = json_encode($input, true);
            //$this->logger->info("payload for job : " . $payload);

            $j = new Job($payload);
            $j->setContextKey("event_class", "customer");
            $job_id = $client->submitJob($j);
        } catch (Exception $e) {
            $this->logger->error("Error submitting job to beanstalk for solr : " . $e->getMessage());
        }
        if ($job_id <= 0)
            $this->logger->error("Failed to submit job to add user to solr. user_id : " . $user->user_id .
                " org_id : " . $user->org_id);
        return $job_id;
    }

    private function saveBillPaymentDetailsOld()
    {

        if (count($this->payment_details) > 0) {
            $this->logger->debug("Going to insert payment details");
            include_once 'apiHelper/payment_mode/PaymentModeFactory.php';
            $paymentModeFactory = new PaymentModeFactory();

            foreach ($this->payment_details AS $payment_detail) {
                try {
                    $paymentMode = $paymentModeFactory->getPaymentModeInstance(
                        $payment_detail['mode'], $payment_detail);
                    $paymentMode->setRefId($this->id);
                    $paymentMode->setRefType(strtoupper($this->bill_type));
                    $paymentMode->save();
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                    Util::addApiWarning($e->getMessage());
                }
            }
        } else {
            $this->logger->debug("No Payment Details found");
        }
    }

    private function setPaymentDetailsObjArr()
    {
        include_once 'models/PaymentModeDetails.php';
        include_once 'models/OrgPaymentModeAttribute.php';
        include_once 'models/PaymentModeAttributeValue.php';

        $this->paymentDetailsObjArr = array();

        if (count($this->payment_details) > 0) {
            foreach ($this->payment_details AS $payment_detail) {
                try {

                    $paymentDetails = new PaymentModeDetails($this->currentorg->org_id);

                    if ($payment_detail['id'] > 0) {
                        $orgPaymentMode = OrgPaymentMode::loadById($this->currentorg->org_id, $payment_detail['id']);
                    } else if ($payment_detail['mode']) {
                        $orgPaymentMode = OrgPaymentMode::loadByLabel($this->currentorg->org_id, $payment_detail['mode']);
                    } else
                        throw new ApiPaymentModeException(ApiPaymentModeException::NO_PAYMENT_MODE_MATCHES);

                    $paymentDetails->setAmount($payment_detail["amount"]);
                    $paymentDetails->setNotes($payment_detail["notes"]);
                    $paymentDetails->setOrgPaymentModeId($orgPaymentMode->getOrgPaymentModeId());
                    $paymentDetails->setPaymentModeId($orgPaymentMode->getPaymentModeId());
                    $paymentDetails->setRefId($this->id);
                    $paymentDetails->setRefType($this->bill_type);

                    if (!$orgPaymentMode->getOrgPaymentModeId())
                        continue;

                    $attrs = array();
                    foreach ($payment_detail["attributes"]["attribute"] as $attributeAssoc) {
                        $attr = OrgPaymentModeAttribute::loadByName($this->currentorg->org_id, $attributeAssoc["name"], $orgPaymentMode->getOrgPaymentModeId());

                        if (!$attr->getOrgPaymentModeAttributeId())
                            continue;

                        $attrValue = new PaymentModeAttributeValue($this->currentorg->org_id);
                        $attrValue->setPaymentModeAttributeId($attr->getPaymentModeAttributeId());
                        $attrValue->setOrgPaymentModeAttributeId($attr->getOrgPaymentModeAttributeId());
                        $attrValue->setPaymentModeId($orgPaymentMode->getPaymentModeId());
                        $attrValue->setOrgPaymentModeId($attr->getOrgPaymentModeId());
                        $attrValue->setValue($attributeAssoc["value"]);

                        try {
                            $orgPossibleValueObj = OrgPaymentModeAttributePossibleValue::loadByValue($this->currentorg->org_id, $attributeAssoc["value"], $attr->getOrgPaymentModeAttributeId());
                            $attrValue->setOrgPaymentModeAttributePossibleValuesId($orgPossibleValueObj->getOrgPaymentModeAttributePossibleValueId());
                            $attrValue->setPaymentModeAttributePossibleValuesId($orgPossibleValueObj->getPaymentModeIdAttributePossibleValueId());
                        } catch (Exception $e) {
                        }

                        $attrs [] = $attrValue;
                    }
                    $paymentDetails->setPaymentModeAttributeValues($attrs);
                } catch (Exception $e) {
                    Util::addApiWarning($payment_detail['mode'] . " - " . $e->getMessage());
                }
                if ($paymentDetails->getOrgPaymentModeId() > 0)
                    $this->paymentDetailsObjArr[] = $paymentDetails;

            }
        }
    }

    private function saveBillPaymentDetails()
    {
        if (count($this->paymentDetailsObjArr) > 0) {
            $this->logger->debug("Going to insert payment details");

            foreach ($this->paymentDetailsObjArr AS $paymentDetails) {
                try {
                    $paymentDetails->setRefId($this->id);
                    $paymentDetails->setRefType($this->bill_type);

                    // validate the payment details
                    $paymentDetails->validate();

                    // save the payment attribute
                    $paymentDetails->save();
                } catch (Exception $e) {
                    Util::addApiWarning($paymentDetails->getName() . " - " . $e->getMessage());
                }
            }
        } else {
            $this->logger->debug("No Payment Details found");
        }

    }

    /**
     * updates transaction details, only custom fields update is supported as of now
     * @param unknown_type $transaction
     */
    public function updateTransaction($transaction)
    {
        //fetching customer's data
        $customer_data = $transaction['customer'];
        $user = UserProfile::getByData($customer_data);
        $user->load(true);
        $this->setUser($user);

        if (isset($transaction['id']) && !empty($transaction['id'])) {
            $id = $transaction['id'];
            $loyalty_log = $this->getTransactionById($id, $user->user_id);
        } else if (isset($transaction['number'])) {
            $number = $transaction['number'];
            $loyalty_log = $this->getTransactionByNumber($number, $user->user_id);
        } else
            throw new Exception('NO_TRANSACTION_ID_OR_NUMBER');
        $this->id = $loyalty_log['id'];
        // outlier status is nit updatable as of now
        $this->outlier_status = $loyalty_log["outlier_status"];

        $this->setCustomFields($transaction);

        $custom_fields_count = count($this->customfields);

        // get the current cf fields
        $currertCustomFieldsArr = $this->getCustomFieldsData();

        // save the custom fields
        $success_count = $this->saveCustomFields();

        if (isset($transaction['delivery_status'])) {
            if ($transaction['delivery_status'] != $loyalty_log['delivery_status']) {

                $updatedBy = $this->currentuser->user_id;
                $result = $this->saveDeliveryStatus($this->id, $transaction['delivery_status'], $updatedBy);

                if ($result) {
                    $loyalty_log['delivery_status'] = $transaction['delivery_status'];
                    Util::addApiWarning("Updated 'delivery_status' Successfully");
                } else {
                    Util::addApiWarning("Could not update 'delivery_status'");
                }
            } else {
                Util::addApiWarning('Not updating delivery_status since current value same as previous value');
            }
        }

        // make a emf call for change
        $updatedCustomFieldsArr = $this->getCustomFieldsData();

        // make emf call with the inputs
        if ($custom_fields_count > 0 && $this->outlier_status == 'NORMAL')
            $this->callEmfForCfChange($currertCustomFieldsArr, $updatedCustomFieldsArr, $user);

        if ($custom_fields_count == 0) {
            Util::addApiWarning("No Custom fields passed, not updating any custom fields");
        } else if ($success_count == -1) {
            $this->logger->error("Can't update custom fields, success_count = $success_count");
            throw new Exception("ERR_CUSTOM_FIELD");
        }
        //TODO: need to return refreshed (after update) transaction details
        $loyalty_log['cf_success_count'] = $success_count;
        $loyalty_log['cf_failure_count'] = $custom_fields_count - $success_count;
        return $loyalty_log;
    }


    /**
     * @param unknown_type $oldCustomFieldsArr
     * @param unknown_type $newCustomFieldsArr
     *
     * Make an emf call for upare if rge custom fields
     */
    private function callEmfForCfChange($oldCustomFieldsArr, $newCustomFieldsArr, $user)
    {
        global $currentorg;
        // get the difference
        $changedCfArr = array();
        foreach ($newCustomFieldsArr as $key => $value) {
            if ($value != $oldCustomFieldsArr[$key]) {
                $changedCfArr[$key] = array(
                    "customFieldName" => $key,
                    "customFieldValue" => $value,
                    "previousCustomFieldValue" => $oldCustomFieldsArr[$key],
                    "assocID" => $this->id,
                );
            }

        }
        $this->logger->debug("Updated cf fields are " . print_r($changedCfArr, true));
// 	    if($changedCfArr)
// 	    {
// 	    	$cf = new CustomFields();
// 	    	$customFields = $cf->getCustomFieldsByScope($this->org_id, "loyalty_transaction");
// 	    	$customFieldsHash = array();
// 	    	foreach ($customFields as $custom_field)
// 	    	{
// 	    		$customFieldsHash[$custom_field["name"]] = $custom_field["id"];
// 	    	}

// 	    	foreach($changedCfArr as &$changedCf)
// 	    	{
// 	    		$changedCf["custom_field_id"] =  $customFieldsHash[$changedCf["custom_field_name"]];
// 	    	}
// 	    }
        //TODO : make actual emf call
        if ($changedCfArr) {
            try {

                $emfController = new EMFServiceController();
                $emf_result = $emfController->transactionUpdateEvent($this->org_id, $user->user_id, $this->id,
                    $this->currentuser->user_id, true, $changedCfArr);

                $coupon_ids = $emfController->extractIssuedCouponIds($emf_result, "PE");
                $lm = new ListenersMgr($currentorg);
                $lm->issuedVoucherDetails($coupon_ids);

            } catch (Exception $e) {
                $this->logger->debug("Emf call has failed with error - " . $e->getMessage());
                $this->logger->debug($e->getTraceAsString());
            }

        } else {
            $this->logger->debug("No cf changes has happened");
        }
        //print_r($changedCfArr);
    }


    private function saveCreditNotes()
    {
        // saving the credit notes
        include_once 'models/CreditNote.php';
        $creditNotesObj = new CreditNote($this->currentorg->org_id, $this->user->getUserId());

        $safe_points = Util::mysqlEscapeString($this->points);
        $safe_notes = Util::mysqlEscapeString($this->notes);
        $safe_bill_amount = Util::mysqlEscapeString($this->bill_amount);
        $safe_bill_number = Util::mysqlEscapeString($this->bill_number);
        $safe_counter_id = Util::mysqlEscapeString($this->counter_id);
        $safe_bill_gross_amount = Util::mysqlEscapeString($this->bill_gross_amount);
        $safe_bill_discount = Util::mysqlEscapeString($this->bill_discount);

        if (is_array($this->credit_note)) {
            $this->logger->debug("Credit note details are provided");

            $added_on = Util::getMysqlDateTime($this->date ? $this->date : "now");
            $validity = Util::getMysqlDateTime(strtotime(date("Y-m-d 23:59:59", strtotime($added_on)) . " + 1 year"));
            $creditNotesObj->setAmount($this->credit_note["amount"]);
            $creditNotesObj->setNumber($this->credit_note["number"]);
            $creditNotesObj->setAddedOn($added_on);
            $creditNotesObj->setReferenceId($this->id);
            $creditNotesObj->setReferenceType((StringUtils::strlower($this->bill_type) == 'return') ? "RETURNED" : "LOYALTY_LOG");
            $creditNotesObj->setNotes($this->credit_note["notes"]);
            $creditNotesObj->setValidity($validity);

            try {
                $ret = $creditNotesObj->save();
            } catch (Exception $e) {
                $ret = false;
            }

            if (!$ret)
                Util::addApiWarning("Could not save the credit notes");
        } else if (is_string($this->credit_note)) {
            $this->logger->debug("Credit note details are provided as a plain text");

            $added_on = date("Y-m-d h:i:s", strtotime("now"));
            $validity = date('Y-m-d', strtotime(date("Y-m-d 23:59:59", strtotime($added_on)) . " + 1 year"));

            // On account of the failed query!
            if (!$this->bill_amount) {
                Util::addApiWarning("Could not save the credit notes");
                return false;
            }
            $creditNotesObj->setAmount($this->bill_amount);
            $creditNotesObj->setNumber("");
            $creditNotesObj->setAddedOn($added_on);
            $creditNotesObj->setReferenceId($this->id);
            $creditNotesObj->setReferenceType((StringUtils::strlower($this->bill_type) == 'return') ? "RETURNED" : "LOYALTY_LOG");
            $creditNotesObj->setNotes($this->credit_note);
            $creditNotesObj->setValidity($validity);

            try {
                $ret = $creditNotesObj->save();
            } catch (Exception $e) {
                $ret = false;
            }

            if (!$ret)
                Util::addApiWarning("Could not save the credit notes");

        }

    }

    /**
     * Updates user optin status to otp-in and last updated to the transaction date
     */
    private function updateOptinStatus()
    {

        $this->logger->debug("Updating users optin status for user : " . $this->user->getUserId());
        $udb = new Dbase('users');
        $res = $udb->query_firstrow("SELECT und.id, und.mobile FROM users_ndnc_status und
    			JOIN masters.organizations o ON (o.id = und.org_id)
    			WHERE und.user_id = " . $this->user->getUserId() . " AND und.org_id = " . $this->org_id . " AND o.optin_active = 1");

        if ($res !== NULL) {

            $this->logger->debug("User's NDNC status found, continue with optin status update");
            $udb->insert("INSERT INTO users_optin_status
    				(ndnc_status_id, last_updated, user_id, org_id, mobile, added_on) VALUES
    				( $res[id], DATE('" . Util::getMysqlDateTime($this->date) . "'), " . $this->user->getUserId() . ", " . $this->org_id . " , $res[mobile], NOW())
    				ON DUPLICATE KEY UPDATE last_updated = GREATEST(last_updated, VALUES(last_updated)), is_active=1");
        }

    }

    /*
     * Sample return
     * Array
	(
	    [0] => Array
	        (
	            [payment_mode_details_id] => 365
	            [ref_type] => regular
	            [ref_id] => 5482739
	            [payment_mode_id] => 2
	            [org_payment_mode_id] => 35
	            [amount] => 100
	            [notes] => just some notes
	            [added_by] =>
	            [added_on] =>
	            [attributes] => Array
	                (
	                    [0] => Array
	                        (
	                            [payment_mode_attribute_value_id] => 255
	                            [org_payment_mode_attribute_id] => 14
	                            [org_payment_mode_id] => 35
	                            [payment_mode_id] => 2
	                            [payment_mode_attribute_id] => 1
	                            [payment_mode_details_id] => 365
	                            [payment_mode_attribute_possible_values_id] =>
	                            [org_payment_mode_attribute_possible_values_id] =>
	                            [value] => abcde12
	                            [added_by] =>
	                            [added_on] =>
	                        )

	                )

	        )

	)
     */
    private function getPaymentDetailsArrForPointsEngine()
    {
        $ret = array();
        foreach ($this->paymentDetailsObjArr as $paymentDetails) {
            if ($paymentDetails->getAmount()) {
                $arr = $paymentDetails->toArray();
                $arr["payment_mode_name"] = $paymentDetails->getOrgPaymentModeObj()->getPaymentModeObj()->getName();
                $arr["org_payment_mode_name"] = $paymentDetails->getOrgPaymentModeObj()->getLabel();
                //$tenderObj->tenderName 	= $payment_details_row["payment_mode_name"];
                //$tenderObj->orgTenderName = $payment_details_row["org_payment_mode_name"];

                $arr["attributes"] = array();
                foreach ($paymentDetails->getPaymentModeAttributeValues() as $value) {
                    if ($value->getPaymentModeAttributeValueId()) {
                        $valueAssoc = $value->toArray();

                        $valueAssoc["org_payment_mode_attribute_name"] = $value->getOrgPaymentModeAttributeObj()->getName();
                        $valueAssoc["data_type"] = $value->getOrgPaymentModeAttributeObj()->getDataType();
                        $arr["attributes"][] = $valueAssoc;
                    }
                }

                if ($paymentDetails->getOrgPaymentModeId() > 0)
                    $ret[] = $arr;
            }
        }

        return $ret;
    }


    public function isTransactionAllowedForUser($user_id)
    {

        $this->logger->info("Checking... is transaction allowed for customer");

        //check mobile realloc request pending state
        include_once 'apiModel/class.ApiChangeIdentifierRequestModelExtension.php';
        $request_model = new ApiChangeIdentifierRequestModelExtension();
        $blocked = $request_model->isMobileReallocPendingForOldCustomer($user_id);
        if ($blocked) {
            $this->logger->info("mobile realloc request pending for user... transaction not allowed");
            throw new Exception('ERR_TRANS_BLOCKED_CUSTOMER');
        }

        $this->logger->info("transaction is allowed");
        return true;

    }

    public function markNotInterestedRegular($id, $customer, $notes = "", $custom_fields = array())
    {
        $newTransactionArray = array();
        try {
            $transactionFilter = new TransactionLoadFilters();
            $transactionFilter->transaction_id = $id;
            $transactionFilter->include_retro = true;
            $notInterestedTransaction = NotInterestedTransaction::loadAll($this->org_id, $transactionFilter);
            $notInterestedTransaction = $notInterestedTransaction[0];
        } catch (Exception $e) {
            throw new Exception("ERR_NO_NI_TRANS");
        }

        try {
            $lineItemsArray = array();
            $notInterestedTransaction->loadLineItems();
            foreach ($notInterestedTransaction->getLineItems() as $lineItem) {
                $item = array();
                $item['qty'] = $lineItem->getQty();
                $item['rate'] = $lineItem->getRate();
                $item['item_code'] = $lineItem->getItemCode();
                $item['amount'] = $lineItem->getTransactionAmount();
                $item['discount'] = $lineItem->getDiscount();
                $item['value'] = $lineItem->getGrossAmount();
                $item['description'] = $lineItem->getDescription();
                $item['serial'] = $lineItem->getSerialNumber();
                $lineItemsArray[] = $item;
            }
            $newTransactionArray["line_items"]["line_item"] = $lineItemsArray;
        } catch (Exception $e) {
            $this->logger->debug("No line items retrieved");
        }

        try {
            $paymentModes = PaymentModeDetails::loadByReference($this->org_id, $id, 'NOT_INTERESTED');
            if ($paymentModes) {
                $paymentModesArray = array();
                foreach ($paymentModes as $paymentMode) {
                    $item = array();
                    $item['mode'] = $paymentMode->getOrgPaymentModeObj()->getLabel();
                    $item['value'] = $paymentMode->getAmount();

                    try {
                        $paymentMode->loadPaymentModeAttributeValues();
                        $attributesArr = array();
                        foreach ($paymentMode->getPaymentModeAttributeValues() as $paymentModeAttrValue) {
                            $attribute = array();
                            $attribute['value'] = $paymentModeAttrValue->getValue();
                            $attribute['name'] = $paymentModeAttrValue->getOrgPaymentModeAttributeObj()->getName();
                            $attributesArr[] = $attribute;
                        }
                        $item["attributes"]["attribute"] = $attributesArr;
                    } catch (Exception $e) {
                        $this->logger->debug("No payment mode attribute retrieved");
                    }
                    $paymentModesArray[] = $item;
                }
                $newTransactionArray['payment_details']['payment'] = $paymentModesArray;
            }
        } catch (Exception $e) {
            $this->logger->debug("No payments retrieved");
        }

        $original_outlier_status = $notInterestedTransaction->getOutlierStatus();
        if ($original_outlier_status == 'RETRO') {
            throw new Exception('ERR_ALREADY_RETRO');
        }


        $newTransactionArray["type"] = "REGULAR";
        $newTransactionArray["is_retro"] = true;
        $newTransactionArray["customer"] = $customer;
        $newTransactionArray["amount"] = $notInterestedTransaction->getTransactionAmount();
        $newTransactionArray["transaction_number"] = $notInterestedTransaction->getTransactionNumber();

        $oldTransactionId = $notInterestedTransaction->getTransactionId();

        $newTransactionArray["billing_time"] = $notInterestedTransaction->getTransactionDate();
        $newTransactionArray["notes"] = "Retrospectively marked REGULAR from not interested transaction id " . $id . ". " . $notes;
        $newTransactionArray["custom_fields"] = $custom_fields;
        $currency = $this->getCurrencyRatio($id, "NOT_INTERESTED");
        if ($currency)
            $newTransactionArray["currency_id"] = $currency["transaction_currency"]["supported_currency_id"];
        include_once "resource/transaction.php";
        $transactionResource = new TransactionResource();
        $notInterestedTransaction->setOutlierStatus('RETRO');
        $notInterestedTransaction->save();
        //die(print_r($newTransactionArray, true));
        $old_id = $id;
        global $currentuser;
        $currentuser_temp = $currentuser;
        $currentuser = StoreProfile::getById($notInterestedTransaction->getStoreId());
        $result = $transactionResource->process('v1.1', 'add', array("root" => array("transaction" => array($newTransactionArray))), array('user_id' => 'true'), 'POST');
        $currentuser = $currentuser_temp;
        if ($result["status"]["code"] != 200) {
            $notInterestedTransaction->setOutlierStatus('NORMAL');
            $notInterestedTransaction->save();
        } else {
            //geting not interested returns
            require_once "models/transactions/TransactionNotInterestedReturn.php";

            $transactionNotInterestedReturn = new TransactionNotInterestedReturn($this->currentorg);

            //will return array of transaction with assoc with line items too
            $notInterestedReturns = $transactionNotInterestedReturn->getByLoyaltyNotInterestedBillId($oldTransactionId);
            $this->logger->debug("transaction need to be migrated :: " . json_encode($notInterestedReturns));

            foreach ($notInterestedReturns as $key => $value) {
                //migrating already returns
                $notInterestedReturnId = $key;
                unset($value['id']);
                $value['type'] = "return";
                $value['customer'] = $customer;
                $this->logger->debug("Migrating :: " . json_encode($value));

                $result = $transactionResource->process('v1.1', 'add', array("root" => array("transaction" => array($value))), array('user_id' => 'true'), 'POST');

                //making then as retro
                $transactionNotInterestedReturn->setTransactionAsRetro($notInterestedReturnId);
            }


        }

        $new_trans_id = $result["transactions"]["transaction"][0]['id'];

        if ($new_trans_id) {
            $customer_id = $result["transactions"]["transaction"][0]['customer']['user_id'];
            global $currentuser;
            ApiUtil::logTransactionTypeChange($this->org_id, $currentuser->getId(), $customer_id, 'RETRO', $old_id, $new_trans_id);
        }
        return $result["transactions"]["transaction"][0];
    }


    private function addCurrencyRatio()
    {
        try {
            include_once 'models/currencyratio/CurrencyRatio.php';
            global $currentorg;
            $currency_ratio = new CurrencyRatio($currentorg->org_id);

            //TODO make private and set currency when the object is created
            //$this->base_currency = SupportedCurrency::loadById($base_currency_id);

            $currency_ratio->setValue($this->bill_amount);
            $currency_ratio->setRefId($this->id);
            $currency_ratio->setBaseCurrency($this->base_currency);
            $currency_ratio->setTransactionCurrency($this->transaction_currency);
            $currency_ratio->setRefType($this->bill_type);
            $currency_ratio->setRatio($this->calculateCurrencyRatio($this->transaction_currency));
            $currency_ratio->setAddedOn(Util::getMysqlDateTime($this->date));
            $currency_ratio->save();
        } catch (Exception $e) {
            Util::addApiWarning("Failed to add currency ratio");
        }
    }

    /*
	 * Calculates the ratio between the base currency and the transaction currency
	 */
    private function calculateCurrencyRatio($transaction_currency = null)
    {
        $transaction_currency_ratio = CurrencyConversion::loadByCurrencyId($transaction_currency["supported_currency_id"]);
        $transaction_currency_ratio = $transaction_currency_ratio->toArray();
        $base_currency_ratio = CurrencyConversion::loadByCurrencyId($this->base_currency["supported_currency_id"]);
        $base_currency_ratio = $base_currency_ratio->toArray();
        $ratio = $transaction_currency_ratio["ratio"] / $base_currency_ratio["ratio"];
        return round($ratio, 3);
    }

    public function getCurrencyRatio($ref_id, $ref_type)
    {
        global $currentorg;
        $currency_ratio = new CurrencyRatio($currentorg->org_id);
        try {
            if ($ref_type == "MIXED")
                $ref_type = "REGULAR";
            $ratio = $currency_ratio->loadByRefIdRefType($currentorg->org_id, $ref_id, $ref_type);
            return $ratio->toArray();
        } catch (Exception $e) {
            $this->logger->debug("No currency ratio found for this transaction");
        }

        return;
    }

    public function addCurrencyRatioReturnLineItems($ref_id, $bill_amount, $returned_time, $transaction_currency)
    {
        try {
            include_once 'models/currencyratio/CurrencyRatio.php';
            global $currentorg;
            $currency_ratio = new CurrencyRatio($currentorg->org_id);

            //TODO make private and set currency when the object is created
            //$this->base_currency = SupportedCurrency::loadById($base_currency_id);

            try {
                //OrgCurrency::loadBySupportedCurrencyId($currentorg->org_id, $data["currency_id"]);
                $transaction_currency = SupportedCurrency::loadById($transaction_currency);
                $transaction_currency = $transaction_currency->toArray();
            } catch (Exception $e) {
                Util::addApiWarning("Transaction currency not found for the line item");
                $this->logger->debug("No currency id passed in the request or currency not an org currency");
            }

            $currency_ratio->setValue($bill_amount);
            $currency_ratio->setRefId($ref_id);
            $currency_ratio->setBaseCurrency($this->base_currency);
            $currency_ratio->setTransactionCurrency($transaction_currency);
            $currency_ratio->setRefType("RETURN");
            $currency_ratio->setRatio($this->calculateCurrencyRatio($transaction_currency));
            $currency_ratio->setAddedOn($returned_time);
            $currency_ratio->save();
        } catch (Exception $e) {
            Util::addApiWarning("Failed to add currency ratio");
        }
    }
    #config ->
}

?>
