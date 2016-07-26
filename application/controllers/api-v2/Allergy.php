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
class Allergy extends REST_Controller {

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

    /**
     * allergy/index
     * @param type ProfileGUID  required
     */
    public function index_post() {
        $this->_response["ServiceName"] = "allergy/index";

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
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ProfileID = safe_array_key($Profile, "UserID", NULL);

            $this->load->model("allergy_model");
            $Allergies = $this->allergy_model->allergies($ProfileID);
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

    public function master_list_post() {
        $this->_response["ServiceName"] = "allergy/master_list";
        $this->load->model("allergy_model");
        $Allergies = $this->allergy_model->master_allergies();
        if (!empty($Allergies)) {
            $this->_response["Data"] = $Allergies;
        } else {
            $this->_response["Message"] = "No Allergies added.";
        }
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function create_post() {

        $this->_response["ServiceName"] = "allergy/create";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('AllergyText', 'Allergy Text', 'trim|required|callback__unique_allergy');

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
            $ProfileID = safe_array_key($Profile, "UserID", NULL);

            $AllergyText = $this->_data['AllergyText'];
            $Allergy = $this->allergy_model->create_allergy($ProfileID, $AllergyText);
            $this->_response["Message"] = "New Allergy has been added successfully";
            $this->_response["Data"] = $Allergy;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _unique_allergy($AllergyText) {
        $this->load->model("allergy_model");
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        $Profile = $this->app->get_row('Users', 'UserID', [
            "UserGUID" => $ProfileGUID
        ]);
        $ProfileID = safe_array_key($Profile, "UserID", NULL);
        $RowCount = $this->allergy_model->is_allergy_added($ProfileID, $AllergyText);
        if ($RowCount) {
            $this->form_validation->set_message('_unique_allergy', 'Allergy already exists in the list');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function delete_post() {

        $this->_response["ServiceName"] = "allergy/delete";

        $this->form_validation->set_rules('UserAllergyGUID', 'User Allergy GUID', 'trim|required');

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
            $UserAllergyGUID = $this->_data['UserAllergyGUID'];
            $this->allergy_model->delete_allergy($UserAllergyGUID);
            $this->_response["Message"] = "Allergy has been deleted successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
