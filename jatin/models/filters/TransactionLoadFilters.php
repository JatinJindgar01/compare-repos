<?php
/**
 * @author cj
 *
 * Class define all the filter avail
 */
class TransactionLoadFilters
{
	public $user_id;
	public $loyalty_id;
	public $store_id;
	public $transaction_id;
	public $transaction_number;
	public $max_transaction_date;
	public $min_transaction_date;
	public $outlier_status;
	public $parent_transaction_id; // the id whihc the the transaction happened
	public $orginal_loyalty_log_id; // the id which the transaction is linked. for return bill, it can loyalty log id
    public $min_transaction_amount;
    public $max_transaction_amount;
    public $entered_by_id;
    public $entered_by_ids;
    public $start_id;
    public $include_retro;

}