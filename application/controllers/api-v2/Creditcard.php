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
class Creditcard extends REST_Controller {

    var $_data = array();

    /**
     * 
     */
    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->benchmark->mark('code_start');
        $this->_data = $this->post();
        $this->_data['Key'] = "value";
        $this->_response = [
            "Status" => TRUE,
            "StatusCode" => self::HTTP_OK,
            "ServiceName" => "creditcard",
            "Message" => "Success",
            "Errors" => (object) [],
            "Data" => (object) [],
            "ElapsedTime" => "",
        ];
        $this->load->library('form_validation');
        $this->form_validation->set_data($this->_data);
    }

    /**
     * 
     */
    public function index_post() {

        $this->_response["ServiceName"] = "creditcard/index";

        $UserID = $this->rest->UserID;
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        if (!is_null($ProfileGUID)) {
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            if (!empty($Profile)) {
                $UserID = $Profile['UserID'];
            }
        }

        $this->load->model("creditcard_model");
        $Creditcards = $this->creditcard_model->creditcards($UserID);
        if (!empty($Creditcards)) {
            $this->_response["Data"] = $Creditcards;
        } else {
            $this->_response["Message"] = "No Creditcard(s) added.";
        }
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    /**
     * 
     */
    public function create_post() {

        $this->_response["ServiceName"] = "creditcard/create";

        $UserID = $this->rest->UserID;
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        if (!is_null($ProfileGUID)) {
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            if (!empty($Profile)) {
                $UserID = $Profile['UserID'];
            }
        }

        $this->form_validation->set_rules('StripeToken', 'Stripe Token', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("creditcard_model");
            $StripeToken = $this->_data['StripeToken'];
            $Creditcard = $this->creditcard_model->create_creditcard($UserID, $StripeToken);
            if ($Creditcard['CreditcardID']) {
                $this->_response["Data"] = $this->creditcard_model->get_creditcard_by_id($Creditcard['CreditcardID']);
            }
            $this->_response["Message"] = $Creditcard['Message'];
            $this->_response["StatusCode"] = $Creditcard['StatusCode'];
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    /**
     * 
     */
    public function delete_post() {

        $this->_response["ServiceName"] = "creditcard/delete";

        $this->form_validation->set_rules('CreditcardGUID', 'Creditcard GUID', 'trim|required|callback__can_do_action[delete]');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("creditcard_model");
            $CreditcardGUID = safe_array_key($this->_data, "CreditcardGUID", NULL);
            $Creditcard = $this->creditcard_model->delete_creditcard($CreditcardGUID);
            $this->_response["Message"] = $Creditcard['Message'];
            $this->_response["StatusCode"] = $Creditcard['StatusCode'];
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    /*
     * 
     */

    public function _can_do_action($CreditcardGUID, $Action) {
        $UserID = $this->rest->UserID;
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        if (!is_null($ProfileGUID)) {
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            if (!empty($Profile)) {
                $UserID = $Profile['UserID'];
            }
        }
        $return = TRUE; 
        switch ($Action) {
            case 'delete':
                $this->load->model("creditcard_model");
                $Creditcard = $this->creditcard_model->get_creditcard_by_guid($CreditcardGUID);
                if(is_null($Creditcard)){
                    $this->form_validation->set_message('_can_do_action', 'Creditcard you want to delete, is not added with poprx.');
                    $return = FALSE;
                }elseif($Creditcard['UserID']!=$UserID){
                    $this->form_validation->set_message('_can_do_action', 'You are not owner of selected Creditcard you want to delete.');
                    $return = FALSE;
                }                
                break;
            default :
                $return = TRUE;
        }
        return $return;
    }

    /**
     * 
     */
    public function mark_default_post() {

        $this->_response["ServiceName"] = "creditcard/mark_default";

        $this->form_validation->set_rules('CreditcardGUID', 'Creditcard GUID', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("creditcard_model");
            $CreditcardGUID = safe_array_key($this->_data, "CreditcardGUID", NULL);
            $Creditcard = $this->creditcard_model->mark_default_creditcard($CreditcardGUID);
            $this->_response["Message"] = $Creditcard['Message'];
            $this->_response["StatusCode"] = $Creditcard['StatusCode'];
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
