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
class Patient extends REST_Controller {

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

    public function update_email_post() {

        $this->_response["ServiceName"] = "patient/update_email";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|callback__unique_email_update');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Email = safe_array_key($this->_data, "Email", NULL);
            $User = $this->app->get_row('Users', 'UserID, UserGUID', [
                'UserGUID' => $ProfileGUID,
            ]);

            $UserID = safe_array_key($User, 'UserID', NULL);

            $this->db->update('Users', encrypt_decrypt(array('Email' => $Email)), array('UserID' => $UserID));
            $this->db->update('UserLogins', encrypt_decrypt(array('LoginKeyword' => $Email)), array('UserID' => $UserID, 'SourceID' => 1));

            $this->_response["Message"] = "Email has been updated.";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    function _unique_email_update($Str) {
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        $Email = safe_array_key($this->_data, "Email", NULL);
        $User = $this->app->get_row('Users', 'UserID, UserGUID', [
            'Email' => $Email,
        ]);
        if (!empty($User)) {
            if ($User['UserGUID'] == $ProfileGUID) {
                $this->form_validation->set_message('_unique_email_update', 'This Email is already connected with this user.');
            } else {
                $this->form_validation->set_message('_unique_email_update', 'Email already exist.');
            }
            return FALSE;
        }
        return TRUE;
    }

    public function medications_post() {

        $this->_response["ServiceName"] = "patient/medications";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');

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
            $Medications = $this->medication_model->medications($UserID, 'all', NULL, "-1", NULL);
            if (!empty($Medications)) {
                $this->_response["Data"] = encrypt_decrypt($Medications, 1);
            } else {
                $this->_response["Message"] = "No Medication(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function medication_history_post() {

        $this->_response["ServiceName"] = "patient/medication_history";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');

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
            $Medications = $this->medication_model->medications($UserID, 'all', NULL, "-1", NULL);
            if (!empty($Medications)) {
                foreach ($Medications as $Key => $Medication) {
                    $Medication['History'] = $this->medication_model->medication_history($Medication['MedicationGUID']);
                    $Medications[$Key] = $Medication;
                }
                $this->_response["Data"] = $Medications;
            } else {
                $this->_response["Message"] = "No Medication(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_medications_post() {

        $this->_response["ServiceName"] = "patient/update_medications";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('Medications', 'Medications', 'trim');

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
            $Medications = safe_array_key($this->_data, "Medications", []);
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $UserID = $Profile['UserID'];
            $this->medication_model->update_medications($UserID, $Medications);
            $Medications = $this->medication_model->medications($UserID, 'all', NULL, "-1", NULL);
            if (!empty($Medications)) {
                $this->_response["Data"] = $Medications;
            }
            $this->_response["Message"] = "Medication(s) list has been updated.";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_pharmacy_post() {

        $this->_response["ServiceName"] = "patient/update_pharmacy";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('PharmacyGUID', 'Pharmacy GUID', 'trim|required');

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
            $PharmacyGUID = safe_array_key($this->_data, "PharmacyGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $UserID = $Profile['UserID'];

            $Pharmacy = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $PharmacyGUID
            ]);
            $PharmacyID = $Pharmacy['UserID'];
            $this->db->update('Users', ['PharmacistID' => $PharmacyID], ['UserID' => $UserID]);
            $this->_response["Message"] = "Pharmacy updated for patient.";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_medication_post() {

        $this->_response["ServiceName"] = "patient/delete_medication";

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
            $this->db->delete('Medications', [
                "MedicationGUID" => $MedicationGUID,
            ]);
            $this->_response["Message"] = "Medication(s) list has been deleted.";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function insurances_post() {

        $this->_response["ServiceName"] = "patient/insurances";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("insurance_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $UserID = $Profile['UserID'];

            $Insurances = $this->insurance_model->insurances($UserID);
            if (!empty($Insurances)) {
                $this->_response["Data"] = $Insurances;
            } else {
                $this->_response["Message"] = "No Insurance(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function allergies_post() {

        $this->_response["ServiceName"] = "patient/allergies";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("allergy_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $UserID = $Profile['UserID'];
            $Allergies = $this->allergy_model->allergies($UserID);
            if (!empty($Allergies)) {
                $this->_response["Data"] = $Allergies;
            } else {
                $this->_response["Message"] = "No Allergies added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function hostory_post() {

        $this->_response["ServiceName"] = "patient/hostory";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("allergy_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $UserID = $Profile['UserID'];
            $Allergies = $this->allergy_model->allergies($UserID);
            if (!empty($Allergies)) {
                $this->_response["Data"] = $Allergies;
            } else {
                $this->_response["Message"] = "No Allergies added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
