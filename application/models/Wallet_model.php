<?php

/**
 * Description of wallet_model
 *
 * @author nitins
 */
class Wallet_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 
     * @param type $UserID
     * @param type $OnlyNumRows
     * @param type $Limit
     * @param type $Offset
     * @return type
     */
    public function transactions($UserID, $OnlyNumRows=NULL, $Limit=NULL, $Offset=NULL){
        $Wallet = $this->get_user_wallet($UserID, TRUE);
        $WalletID = $Wallet['WalletID'];
        
        if(is_null($OnlyNumRows)){
            $this->db->select('WT.TransactionGUID, WT.TransactionSID, WT.Amount, WT.Type, WT.CreatedAt');  
            $this->db->select('IFNULL(P.Code, "") AS PromoCode', FALSE);  
            $this->db->select('IFNULL(P.PromoType, "") AS PromoType', FALSE);  
        }else{
            $this->db->select('WT.TransactionGUID');
        }
        
        $this->db->join('Promos AS P', 'P.PromoID=WT.PromoID', 'LEFT');
        
        $this->db->where('WT.ToWallet', $WalletID);   
        
        $this->db->from('WalletTransactions' .' AS WT');        
        
        if (is_null($OnlyNumRows)) {
            $this->db->limit($Limit, $Offset);
            $Query = $this->db->get();        
            $Transactions = $Query->result_array();
            return $Transactions;
        }else{
            $Query = $this->db->get();        
            return $Query->num_rows();
        } 
    }

    /**
     * Get details of users'wallet
     * @param type $user_id
     * @return type
     */
    function get_user_wallet($UserID, $IDRequired = FALSE) {
        if($IDRequired){
            $this->db->select('WalletID');
        }
        $this->db->select('WalletGUID, Amount, LastTransactionAt');
        $this->db->where('UserID', $UserID);
        $Query = $this->db->get('Wallet');
        $Wallet = $Query->row_array();
        return $Wallet;
    }

    /**
     * Create a new wallet for given user
     * @param type $user_id
     * @return type
     */
    public function create_wallet($UserID) {
        $WalletID = 0;
        //check if user has wallet
        $Wallet = $this->get_user_wallet($UserID, TRUE);
        if (is_null($Wallet)) {
            $Wallet = array(
                'WalletGUID' => guid(),
                'UserID' => $UserID,
                'Amount' => 0.0,
                'CreatedAt' => DATETIME,
            );
            $this->db->insert('Wallet', $Wallet);
            $WalletID = $this->db->insert_id();
        } else {
            $WalletID = $Wallet['WalletID'];
        }
        return $WalletID;
    }

    /**
     * Create Transactopm
     * @param type $ToWallet | To wallet id 
     * @param type $FromWallet | from wallet id
     * @param type $Amount
     * @param type $Type | 'CREDITED' OR 'DEBITED'
     * @param type $ActivityType | 'FUND_ADDED'
     * @param type $PromoID
     * @return type
     */
    function create_transaction($ToWallet, $FromWallet, $Amount, $Type, $ActivityType, $PromoID=NULL) {
        //perform credit and debit
        $this->db->set('Amount', "Amount-'" . $Amount . "'", FALSE);
        $this->db->set('LastTransactionAt', DATETIME);
        $this->db->where('WalletID', $FromWallet);
        $this->db->update('Wallet');

        //add money to $from_wallet wallet
        //incase of admin we are passing 0 as to_wallet so no wallet will be affected

        $this->db->set('Amount', "Amount+'" . $Amount . "'", FALSE);
        $this->db->set('LastTransactionAt', DATETIME);
        $this->db->where('WalletID', $ToWallet);
        $this->db->update('Wallet');

        //make a log for to wallet
        $res = $this->db->where('DATE_FORMAT(CreatedAt, \'%Y\')=', gmdate('Y'), FALSE)
                ->where('DATE_FORMAT(CreatedAt, \'%m\')=', gmdate('m'), FALSE)
                ->count_all_results('WalletTransactions');
        $sid = transaction_sid($res + 1);
        
        $WalletTransaction = array(
            'TransactionGUID' => guid(),
            'TransactionSID' => $sid,
            'ToWallet' => $ToWallet,
            'FromWallet' => $FromWallet,
            'Amount' => $Amount,
            'Type' => $Type,
            'ActivityType' => $ActivityType,
            'PromoID' => $PromoID,
            'CreatedAt' => DATETIME,
        );
        $this->db->insert('WalletTransactions', $WalletTransaction);

        //make a log for from wallet
        $res = $this->db->where('DATE_FORMAT(CreatedAt, \'%Y\')=', gmdate('Y'), FALSE)
                ->where('DATE_FORMAT(CreatedAt, \'%m\')=', gmdate('m'), FALSE)
                ->count_all_results('WalletTransactions');
        $sid = transaction_sid($res + 1);
        
        $WalletTransaction = array(
            'TransactionGUID' => guid(),
            'TransactionSID' => $sid,
            'ToWallet' => $FromWallet,
            'FromWallet' => $ToWallet,
            'Amount' => $Amount,
            'Type' => ($Type == 'CREDITED') ? 'DEBITED' : 'CREDITED',
            'ActivityType' => $ActivityType,
            'PromoID' => $PromoID,
            'CreatedAt' => DATETIME,
        );
        $this->db->insert('WalletTransactions', $WalletTransaction);
    }

    /**
     * Get transaction history
     * @param type $wallet_id
     * @param type $search
     * @param type $sort
     * @param type $order
     * @param type $page_no
     * @param type $page_size
     * @return type
     */
    function transaction_history($wallet_id, $search = "", $sort = "", $order = 'DESC', $page_no = 1, $page_size = 10) {
        $return = array();
        //$page_no = ($page_no - 1) * $page_size;
        $this->db->select('T.TransactionGUID, T.TransactionSID, T.ToWallet, T.FromWallet, T.Amount, T.Type, T.Status, T.ModuleID, T.ModuleEntityID, T.ActivityType, T.CreatedDate');
        $this->db->where('ToWallet', $wallet_id);
        if ($sort != '' && $order != '') {
            $this->db->order_by('T.' . $sort, $order);
        } else {
            $this->db->order_by('T.CreatedDate', "DESC");
            $this->db->order_by('T.ModuleEntityID', "DESC");
        }

        //get to user details
        $this->db->join(WALLET . ' TW', 'T.ToWallet=TW.WalletID');
        $this->db->select('TW.UserID, TU.UserGUID as ToUserGUID, CONCAT(TU.FirstName, \' \', TU.LastName) as ToName', FALSE);
        $this->db->join(USERS . ' TU', 'TW.UserID=TU.UserID');

        //from user details
        $this->db->join(WALLET . ' FW', 'T.FromWallet=FW.WalletID');
        $this->db->select('FW.UserID, FU.UserGUID as FromUserGUID, CONCAT(FU.FirstName, \' \', FU.LastName) as FromName', FALSE);
        $this->db->join(USERS . ' FU', 'FW.UserID=FU.UserID');

        $this->db->from(TRANSACTION_HISTORY . ' T');

        $tempdb = clone $this->db;
        $temp_q = $tempdb->get();
        $return['total_records'] = $temp_q->num_rows();

        $this->db->limit($page_size, $page_no);
        $query = $this->db->get();
        //echo $this->db->last_query();die;
        $return['result'] = $query->result_array();
        return $return;
    }

    /**
     * 
     * @param type $UserID
     * @param type $Amount
     * @param type $PromoID
     */
    function add_funds($UserID, $Amount, $PromoID=NULL) {
        
        $WalletID = $this->create_wallet($UserID);
        
        $this->db->set('Amount', 'Amount+' . $Amount, FALSE);
        $this->db->set('LastTransactionAt', DATETIME);
        $this->db->where('WalletID', $WalletID);
        $this->db->update('Wallet');

        //make a transaction log for to wallet
        $res = $this->db->where('DATE_FORMAT(CreatedAt, \'%Y\')=', gmdate('Y'), FALSE)
                ->where('DATE_FORMAT(CreatedAt, \'%m\')=', gmdate('m'), FALSE)
                ->count_all_results('WalletTransactions');
        $sid = transaction_sid($res + 1);
        
        $WalletTransaction = array(
            'TransactionGUID' => guid(),
            'TransactionSID' => $sid,
            'ToWallet' => $WalletID,
            'FromWallet' => $WalletID,
            'Amount' => $Amount,
            'Type' => 'CREDITED',
            'ActivityType' => 'FUND_ADDED',
            'Status' => 'APPROVED',
            'PromoID'=>$PromoID,
            'CreatedAt' => DATETIME,
        );
        $this->db->insert('WalletTransactions', $WalletTransaction);
    }
    
    /**
     * 
     * @param type $UserID
     * @param type $Amount
     * @param type $PromoID
     */
    function remove_funds($UserID, $Amount, $PromoID=NULL) {
        
        $WalletID = $this->create_wallet($UserID);
        
        $this->db->set('Amount', 'Amount-' . $Amount, FALSE);
        $this->db->set('LastTransactionAt', DATETIME);
        $this->db->where('WalletID', $WalletID);
        $this->db->update('Wallet');

        //make a transaction log for to wallet
        $res = $this->db->where('DATE_FORMAT(CreatedAt, \'%Y\')=', gmdate('Y'), FALSE)
                ->where('DATE_FORMAT(CreatedAt, \'%m\')=', gmdate('m'), FALSE)
                ->count_all_results('WalletTransactions');
        $sid = transaction_sid($res + 1);
        
        $WalletTransaction = array(
            'TransactionGUID' => guid(),
            'TransactionSID' => $sid,
            'ToWallet' => $WalletID,
            'FromWallet' => $WalletID,
            'Amount' => $Amount,
            'Type' => 'DEBITED',
            'ActivityType' => 'FUND_REMOVED',
            'Status' => 'APPROVED',
            'PromoID'=>$PromoID,
            'CreatedAt' => DATETIME,
        );
        $this->db->insert('WalletTransactions', $WalletTransaction);
    }
    
    
    /**
     * 
     * @param type $UserID
     * @param type $Amount
     * @param type $PromoID
     */
    function pay_using_wallet($UserID, $Amount, $PromoID=NULL) {
        
        $WalletID = $this->create_wallet($UserID);
        
        $this->db->set('Amount', 'Amount-' . $Amount, FALSE);
        $this->db->set('LastTransactionAt', DATETIME);
        $this->db->where('WalletID', $WalletID);
        $this->db->update('Wallet');

        //make a transaction log for to wallet
        $res = $this->db->where('DATE_FORMAT(CreatedAt, \'%Y\')=', gmdate('Y'), FALSE)
                ->where('DATE_FORMAT(CreatedAt, \'%m\')=', gmdate('m'), FALSE)
                ->count_all_results('WalletTransactions');
        $sid = transaction_sid($res + 1);
        
        $WalletTransaction = array(
            'TransactionGUID' => guid(),
            'TransactionSID' => $sid,
            'ToWallet' => $WalletID,
            'FromWallet' => $WalletID,
            'Amount' => $Amount,
            'Type' => 'DEBITED',
            'ActivityType' => 'PURCHASE',
            'Status' => 'APPROVED',
            'PromoID'=>$PromoID,
            'CreatedAt' => DATETIME,
        );
        $this->db->insert('WalletTransactions', $WalletTransaction);
    }
}
