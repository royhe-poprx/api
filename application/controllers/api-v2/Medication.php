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
class Medication extends REST_Controller {

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

    public function index_post() {

        $this->_response["ServiceName"] = "medication/index";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('Limit', 'Limit', 'trim|required');
        $this->form_validation->set_rules('Offset', 'Offset', 'trim|required');
        $this->form_validation->set_rules('ListType', 'ListType', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $ListType = safe_array_key($this->_data, "ListType", 'all');
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $UserID = $Profile['UserID'];
            $this->_response["TotalRecords"] = $this->medication_model->medications($UserID, $ListType, 1);
            $Medications = $this->medication_model->medications($UserID, $ListType, NULL, $Limit, $Offset);
            if (!empty($Medications)) {
                $this->_response["Data"] = $Medications;
            } else {
                $this->_response["Message"] = "No Medication(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function create_new_rx_post() {

        $this->_response["ServiceName"] = "medication/create_new_rx";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('Images', 'Images', 'trim|required');
        $this->form_validation->set_rules('SpecialInstructions', 'Special Instructions', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $UserID = $Profile['UserID'];
            $SpecialInstructions = safe_array_key($this->_data, "SpecialInstructions", "");
            $Images = safe_array_key($this->_data, "Images", NULL);

            $MedicationID = $this->medication_model->create_medication($UserID, 1, NULL, NULL, NULL, $Images);
            $Medication = $this->medication_model->get_medication_by_id($MedicationID);
            $MedicationGUID = $Medication['MedicationGUID'];

            if ($IsNew == 1) {
                $this->medication_model->add_medication_to_quote($UserID, $MedicationGUID, $SpecialInstructions);
            }

            $this->_response["Data"] = $Medication;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function create_post() {

        $this->_response["ServiceName"] = "medication/create";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('MedicationName', 'Medication Name', 'trim|required');
        $this->form_validation->set_rules('Dosage', 'Dosage', 'trim');
        $this->form_validation->set_rules('IsNew', 'Is New', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('SpecialInstructions', 'Special Instructions', 'trim');

        $IsNew = safe_array_key($this->_data, "IsNew", NULL);
        if ($IsNew == 1) {
            $this->form_validation->set_rules('Images', 'Images', 'trim|required');
            $this->form_validation->set_rules('MedicationIcon', 'Medication Icon', 'trim');
        } else {
            $this->form_validation->set_rules('MedicationIcon', 'Medication Icon', 'trim');
            $this->form_validation->set_rules('Images', 'Images', 'trim');
        }


        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $UserID = $Profile['UserID'];
            $MedicationIcon = safe_array_key($this->_data, "MedicationIcon", []);
            $MedicationName = safe_array_key($this->_data, "MedicationName", "");
            $Dosage = safe_array_key($this->_data, "Dosage", "");
            $SpecialInstructions = safe_array_key($this->_data, "SpecialInstructions", "");
            $Images = safe_array_key($this->_data, "Images", NULL);

            $MedicationID = $this->medication_model->create_medication($UserID, $IsNew, $MedicationName, $MedicationIcon, $Dosage, $Images);
            $Medication = $this->medication_model->get_medication_by_id($MedicationID);
            $MedicationGUID = $Medication['MedicationGUID'];

            if ($IsNew == 1) {
                $this->medication_model->add_medication_to_quote($UserID, $MedicationGUID, $SpecialInstructions);
            }

            $this->_response["Data"] = $Medication;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_post() {

        $this->_response["ServiceName"] = "medication/update";

        $this->form_validation->set_rules('MedicationGUID', 'MedicationGUID', 'trim|required');
        $this->form_validation->set_rules('MedicationName', 'Medication Name', 'trim|required');
        $this->form_validation->set_rules('Dosage', 'Dosage', 'trim');
        $this->form_validation->set_rules('IsNew', 'Is New', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('NewImageRequest', 'New Image Request', 'trim');

        $IsNew = safe_array_key($this->_data, "IsNew", NULL);
        if ($IsNew == 1) {
            $this->form_validation->set_rules('Images', 'Images', 'trim|required');
            $this->form_validation->set_rules('MedicationIcon', 'Medication Icon', 'trim');
            $this->form_validation->set_rules('SpecialInstructions', 'Special Instructions', 'trim');
        } else {
            $this->form_validation->set_rules('Images', 'Images', 'trim');
            $this->form_validation->set_rules('MedicationIcon', 'Medication Icon', 'trim');
        }


        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", "");
            $NewImageRequest = safe_array_key($this->_data, "NewImageRequest", 0);
            $MedicationIcon = safe_array_key($this->_data, "MedicationIcon", []);
            $MedicationName = safe_array_key($this->_data, "MedicationName", "");
            $Dosage = safe_array_key($this->_data, "Dosage", "");
            $SpecialInstructions = safe_array_key($this->_data, "SpecialInstructions", "");
            $Images = safe_array_key($this->_data, "Images", NULL);

            $this->medication_model->update_medication($MedicationGUID, $IsNew, $MedicationName, $MedicationIcon, $Dosage, $Images, $NewImageRequest);
            $Medication = $this->medication_model->get_medication_by_guid($MedicationGUID);

            $this->_response["Data"] = $Medication;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function transfer_post() {

        $this->_response["ServiceName"] = "medication/transfer";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('TransferReady', 'TransferReady', 'callback__is_transfer_ready');
        $this->form_validation->set_rules('ImportAll', 'Import All', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('PharmacyName', 'Pharmacy Name', 'trim|required');
        $this->form_validation->set_rules('PharmacyPhone', 'Pharmacy Phone', 'trim');
        $this->form_validation->set_rules('Medications', 'Medications', 'trim');

        $ImportAll = safe_array_key($this->_data, "ImportAll", NULL);
        $Medications = safe_array_key($this->_data, "Medications", []);

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } elseif ($ImportAll == 0 && empty(array_filter($Medications))) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = ["Medications" => "Medications field must have atleast one medication."];
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ActiveUserID = $Profile['UserID'];
            $PharmacyUserID = $Profile['PharmacistID'];
            $TPName = safe_array_key($this->_data, "PharmacyName", NULL);
            $TPPhone = safe_array_key($this->_data, "PharmacyPhone", NULL);
            $Medications = safe_array_key($this->_data, "Medications", []);

            //create prescription and medications under it
            $OrderGUID = $this->medication_model->create_transfer($ActiveUserID, $PharmacyUserID, $TPName, $TPPhone, $ImportAll, $Medications);
            $this->_response["Data"] = [
                "OrderGUID" => $OrderGUID,
            ];
            $this->_response["Message"] = "Get ready for huge savings! Your pharmacist will quote you shortly.";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _is_transfer_ready() {
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
            "UserGUID" => $ProfileGUID
        ]);
        if (!empty($Profile) && !is_null($Profile['PharmacistID'])) {
            return TRUE;
        } else {
            $this->form_validation->set_message('_is_transfer_ready', 'It seems you are not allocated to any Pharmacy please go to dashboard and set your zipcode.');
            return FALSE;
        }
    }

    public function verify_post() {

        $this->_response["ServiceName"] = "medication/verify";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('TransferReady', 'TransferReady', 'callback__is_transfer_ready');
        $this->form_validation->set_rules('PharmacyName', 'Pharmacy Name', 'trim|required');
        $this->form_validation->set_rules('PharmacyPhone', 'Pharmacy Phone', 'trim');
        $this->form_validation->set_rules('Medications', 'Medications', 'trim');


        $Medications = safe_array_key($this->_data, "Medications", []);

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } elseif (empty(array_filter($Medications))) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = ["Medications" => "Medications field must have atleast one medication."];
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ActiveUserID = $Profile['UserID'];
            $PharmacyUserID = $Profile['PharmacistID'];
            $TPName = safe_array_key($this->_data, "PharmacyName", NULL);
            $TPPhone = safe_array_key($this->_data, "PharmacyPhone", NULL);
            $OrderGUID = $this->medication_model->verify_medication($ActiveUserID, $PharmacyUserID, $TPName, $TPPhone, $Medications);
            $this->_response["Data"] = [
                "OrderGUID" => $OrderGUID,
            ];
            $this->_response["Message"] = "Medication(s) has been transferred to your preferred pharmacy successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function verify_new_post() {

        $this->_response["ServiceName"] = "medication/verify_new";
        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('TransferReady', 'TransferReady', 'callback__is_transfer_ready');
        $this->form_validation->set_rules('MedicationGUID', 'MedicationGUID', 'trim|required');
        $this->form_validation->set_rules('PharmacyName', 'Pharmacy Name', 'trim');
        $this->form_validation->set_rules('PharmacyPhone', 'Pharmacy Phone', 'trim');


        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ActiveUserID = $Profile['UserID'];
            $PharmacyUserID = $Profile['PharmacistID'];
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);
            $TPName = safe_array_key($this->_data, "PharmacyName", NULL);
            $TPPhone = safe_array_key($this->_data, "PharmacyPhone", NULL);
            $Medications[] = [
                "MedicationGUID" => $MedicationGUID,
            ];
            $OrderGUID = $this->medication_model->verify_medication($ActiveUserID, $PharmacyUserID, $TPName, $TPPhone, $Medications);
            $this->_response["Data"] = [
                "OrderGUID" => $OrderGUID,
            ];
            $this->_response["Message"] = "Medication(s) has been transferred to your preferred pharmacy successfully";
            
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }
    
    public function verify_new_1_post() {
        $this->_response["ServiceName"] = "medication/verify_new_1";
        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('TransferReady', 'TransferReady', 'callback__is_transfer_ready');
        $this->form_validation->set_rules('MedicationGUID', 'MedicationGUID', 'trim|required');
        $this->form_validation->set_rules('PharmacyName', 'Pharmacy Name', 'trim');
        $this->form_validation->set_rules('PharmacyPhone', 'Pharmacy Phone', 'trim');
        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ActiveUserID = $Profile['UserID'];
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);
            $TPName = safe_array_key($this->_data, "PharmacyName", NULL);
            $TPPhone = safe_array_key($this->_data, "PharmacyPhone", NULL);
            $this->medication_model->verify_medication_new_1($ActiveUserID, $MedicationGUID, $TPName, $TPPhone);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function view_post() {

        $this->_response["ServiceName"] = "medication/view";

        $this->form_validation->set_rules('MedicationGUID', 'Medication GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);
            $Medication = $this->medication_model->get_medication_by_guid($MedicationGUID);
            $this->_response["Data"] = $Medication;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_post() {

        $this->_response["ServiceName"] = "medication/delete";

        $this->form_validation->set_rules('MedicationGUID', 'Medication GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);
            $Medication = $this->medication_model->delete_medication_by_guid($MedicationGUID);
            $this->_response["Data"] = $Medication;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function toggle_auto_refill_post() {

        $this->_response["ServiceName"] = "medication/toggle_auto_refill";

        $this->form_validation->set_rules('MedicationGUID', 'Medication GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);
            $this->medication_model->update_auto_refill($MedicationGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function add_to_quote_post() {

        $this->_response["ServiceName"] = "medication/add_to_quote";
        $UserID = $this->rest->ActiveUserID;
        //$this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('MedicationGUID', 'Medication GUID', 'trim|required');
        $this->form_validation->set_rules('MarkAsNew', 'Mark As New', 'trim|in_list[0,1]');
        $this->form_validation->set_rules('AddToQuoteReady', 'AddToQuoteReady', 'callback__is_add_to_quote_ready');

        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);
            $MarkAsNew = safe_array_key($this->_data, "MarkAsNew", 0);
            $SpecialInstructions = NULL;
            $OrderMedicationGUID = $this->medication_model->add_medication_to_quote($UserID, $MedicationGUID, $SpecialInstructions, $MarkAsNew);
            $this->_response["Data"] = [
                "OrderMedicationGUID" => $OrderMedicationGUID,
            ];
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _is_add_to_quote_ready($Str) {
        $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);
        $this->load->model("medication_model");
        $Medication = $this->medication_model->get_medication_by_guid($MedicationGUID, TRUE);
        if ($Medication['InProcess'] == 1) {
            $this->form_validation->set_message('_is_add_to_quote_ready', 'Medication already in process.');
            return FALSE;
        }
        return TRUE;
    }

    public function remove_from_quote_post() {

        $this->_response["ServiceName"] = "medication/remove_from_quote";


        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('OrderMedicationGUID', 'Order Medication GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("medication_model");
            $OrderMedicationGUID = safe_array_key($this->_data, "OrderMedicationGUID", NULL);

            $this->medication_model->remove_medication_from_quote($OrderMedicationGUID);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
