<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Wallet extends REST_Controller {

    var $_data = array();

    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->benchmark->mark('code_start');
        $this->_data = $this->post();
        $this->_data['Key'] = "value";
        $this->_response = [
            "Status" => TRUE,
            "StatusCode" => self::HTTP_OK,
            "ServiceName" => "",
            "Message" => "Success",
            "Errors" => (object) [],
            "Data" => (object) [],
            "ElapsedTime" => "",
        ];
        $this->load->library('form_validation');
        $this->form_validation->set_data($this->_data);
    }

    public function transactions_post() {

        $this->_response["ServiceName"] = "wallet/transactions";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('Limit', 'Limit', 'trim|required');
        $this->form_validation->set_rules('Offset', 'Offset', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
            
        } else {

            $this->load->model("wallet_model");

            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);

            $this->_response["TotalRecords"] = $this->wallet_model->transactions($UserID, 1);
            $Transactions = $this->wallet_model->transactions($UserID, NULL, $Limit, $Offset);
            if (!empty($Transactions)) {
                $this->_response["Data"] = $Transactions;
            } else {
                $this->_response["Message"] = "No Transaction(s) done.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }
    
    public function apply_promo_post(){
        
        $this->_response["ServiceName"]="wallet/apply_promo";
        
        $UserID= $this->rest->UserID;    
        
        $this->form_validation->set_rules('PromoCode', 'Promo Code', 'trim|required|callback__validate_promo_code');
        
        if ($this->form_validation->run() == FALSE){
            
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;            
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"]=$this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response); 
            
        }else{
            
            $PromoCode = safe_array_key($this->_data, "PromoCode", NULL);
            $this->load->model("promo_model");  
            $this->load->model("wallet_model");  
            $Promo = $this->app->get_row('Promos', '*',[
                "Code"=>$PromoCode,
            ]);
            
            $PromoType = safe_array_key($Promo, "PromoType", "");
            
            if($PromoType=='REFERRAL'){
                $PromoID = $this->promo_model->apply_promo($UserID, $PromoCode, NULL, 'APPROVED');
                $this->wallet_model->add_funds($UserID, $Promo['Amount'], $PromoID);    
                $this->wallet_model->add_funds($Promo['AssignTo'], $Promo['AssignToAmount'], $PromoID);    
                $this->_response["Message"] = "Refferral code has been applied. Wallet Balance has been updated successfully.";
            }elseif($PromoType=='FREE_MONEY'){
                $PromoID = $this->promo_model->apply_promo($UserID, $PromoCode, NULL, 'APPROVED');
                $this->load->model("wallet_model");
                $this->wallet_model->add_funds($UserID, $Promo['Amount'], $PromoID);            
                $this->_response["Message"] = "Wallet Balance has been updated successfully.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"]=$this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }
    
    public function _validate_promo_code($Str) {
        $UserID = $this->rest->UserID;
        if ($Str) {
            $Row = $this->app->get_row('Promos', 'PromoID, PromoType, AssignTo, PromoGUID, IsActive', [
                "Code" => $Str
            ]);
            if (!empty($Row)) {
                if($Row['PromoType']=='REFERRAL'){
                    if ($UserID == $Row['AssignTo']) {
                        $this->form_validation->set_message('_validate_promo_code', 'You can not use your own referral code.');
                        return FALSE;
                    } elseif ($Row['IsActive'] != 1) {
                        $this->form_validation->set_message('_validate_promo_code', 'Referral code you are using is desabled by Admin.');
                        return FALSE;
                    }
                    $Sql = "SELECT UserPromoGUID FROM UserPromos WHERE UserID='".$UserID."' AND PromoID IN (SELECT PromoID FROM Promos WHERE PromoType='REFERRAL')";
                    $Query = $this->db->query($Sql);
                    $UserPromos = $Query->row_array();
                    if ($Query->num_rows() > 0) {
                        $this->form_validation->set_message('_validate_promo_code', 'You have already used Referral.');
                        return FALSE;
                    }
                }elseif($Row['PromoType']=='FREE_MONEY'){
                    $Sql = "SELECT UserPromoGUID FROM UserPromos WHERE UserID='".$UserID."' AND PromoID ='".$Row['PromoID']."'";
                    $Query = $this->db->query($Sql);
                    $UserPromos = $Query->row_array();
                    if ($Query->num_rows() > 0) {
                        $this->form_validation->set_message('_validate_promo_code', 'You have already used this code.');
                        return FALSE;
                    }
                }                
            } else {
                $this->form_validation->set_message('_validate_promo_code', '{field} is expired or not valid.');
                return FALSE;
            }
        }
        return TRUE;
    }

}
