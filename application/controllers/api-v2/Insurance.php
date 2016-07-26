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
class Insurance extends REST_Controller {

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
            "ServiceName" => "insurance",
            "Message" => "Success",
            "Errors" => (object) [],
            "Data" => (object) [],
            "ElapsedTime" => "",
        ];
        $this->load->library('form_validation');
        $this->form_validation->set_data($this->_data);
    }

    public function index_post() {

        $this->_response["ServiceName"] = "insurance/index";

        $ActiveUserID = $this->rest->UserID;

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        if (!is_null($ProfileGUID)) {
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            if (!empty($Profile)) {
                $ActiveUserID = $Profile['UserID'];
            }
        }

        $this->load->model("insurance_model");
        $this->load->model("sti_model");
        $Insurances = $this->insurance_model->insurances($ActiveUserID);
        $Sti = $this->sti_model->sti($ActiveUserID);
        if (!empty($Insurances)) {
            $this->_response["Data"] = $Insurances;
        } else {
            $this->_response["Message"] = "No Insurance(s) added.";
        }
        $this->_response["Sti"] = $Sti;
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function create_post() {

        $this->_response["ServiceName"] = "insurance/create";

        $ActiveUserID = $this->rest->UserID;

        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        if (!is_null($ProfileGUID)) {
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            if (!empty($Profile)) {
                $ActiveUserID = $Profile['UserID'];
            }
        }

        $this->form_validation->set_rules('IsImageType', 'Is Image Type', 'trim|required|in_list[0,1]');

        $this->form_validation->set_rules('InsuranceProvider', 'Insurance Provider', 'trim');
        $this->form_validation->set_rules('EmployerGroupName', 'Employer Group Name', 'trim');
        $this->form_validation->set_rules('ContractPlanNumber', 'Contract Plan Number', 'trim');
        $this->form_validation->set_rules('GroupNumber', 'Group Number', 'trim');
        $this->form_validation->set_rules('EmployeeStudentNumber', 'Employee Student Number', 'trim');

        $IsImageType = safe_array_key($this->_data, "IsImageType", "0");

        $FrontImage = safe_array_key($this->_data, "FrontImage", "");
        $BackImage = safe_array_key($this->_data, "BackImage", "");

        $InsuranceProvider = safe_array_key($this->_data, "InsuranceProvider", NULL);
        $EmployerGroupName = safe_array_key($this->_data, "EmployerGroupName", NULL);
        $ContractPlanNumber = safe_array_key($this->_data, "ContractPlanNumber", NULL);
        $GroupNumber = safe_array_key($this->_data, "GroupNumber", NULL);
        $EmployeeStudentNumber = safe_array_key($this->_data, "EmployeeStudentNumber", NULL);
        $Comment = safe_array_key($this->_data, "Comment", NULL);

        if ($IsImageType == 1 && ($FrontImage == "" && $BackImage == "")) {
            $this->form_validation->set_rules('FrontImage', 'Front Image', 'trim|required');
            $this->form_validation->set_rules('BackImage', 'Back Image', 'trim');
        } else {
            if ($IsImageType == 0 && is_null($InsuranceProvider) && is_null($EmployerGroupName) && is_null($ContractPlanNumber) && is_null($GroupNumber) && is_null($EmployeeStudentNumber)) {
                $this->form_validation->set_rules('Comment', 'Comment', 'trim|required');
            }
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

            $this->load->model("insurance_model");
            $InsuranceGUID = $this->insurance_model->create_insurance($ActiveUserID, $IsImageType, $FrontImage, $BackImage, $InsuranceProvider, $EmployerGroupName, $ContractPlanNumber, $GroupNumber, $EmployeeStudentNumber, $Comment);
            $this->_response["Data"] = $this->insurance_model->get_insurance_by_guid($InsuranceGUID);
            $this->_response["Message"] = "New insurance added successfully.";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_post() {

        $this->_response["ServiceName"] = "insurance/update";

        $this->form_validation->set_rules('InsuranceCardGUID', 'Insurance Card GUID', 'trim|required');
        $this->form_validation->set_rules('IsImageType', 'Is Image Type', 'trim|required|in_list[0,1]');

        $this->form_validation->set_rules('InsuranceProvider', 'Insurance Provider', 'trim');
        $this->form_validation->set_rules('EmployerGroupName', 'Employer Group Name', 'trim');
        $this->form_validation->set_rules('ContractPlanNumber', 'Contract Plan Number', 'trim');
        $this->form_validation->set_rules('GroupNumber', 'Group Number', 'trim');
        $this->form_validation->set_rules('EmployeeStudentNumber', 'Employee Student Number', 'trim');

        $InsuranceCardGUID = safe_array_key($this->_data, "InsuranceCardGUID", NULL);
        $IsImageType = safe_array_key($this->_data, "IsImageType", "0");

        $FrontImage = safe_array_key($this->_data, "FrontImage", "");
        $BackImage = safe_array_key($this->_data, "BackImage", "");

        $InsuranceProvider = safe_array_key($this->_data, "InsuranceProvider", NULL);
        $EmployerGroupName = safe_array_key($this->_data, "EmployerGroupName", NULL);
        $ContractPlanNumber = safe_array_key($this->_data, "ContractPlanNumber", NULL);
        $GroupNumber = safe_array_key($this->_data, "GroupNumber", NULL);
        $EmployeeStudentNumber = safe_array_key($this->_data, "EmployeeStudentNumber", NULL);
        $Comment = safe_array_key($this->_data, "Comment", NULL);


        if ($IsImageType == 1 && ($FrontImage == "" && $BackImage == "")) {
            $this->form_validation->set_rules('FrontImage', 'Front Image', 'trim|required');
            $this->form_validation->set_rules('BackImage', 'Back Image', 'trim');
        } else {
            if ($IsImageType == 0 && is_null($InsuranceProvider) && is_null($EmployerGroupName) && is_null($ContractPlanNumber) && is_null($GroupNumber) && is_null($EmployeeStudentNumber)) {
                $this->form_validation->set_rules('Comment', 'Comment', 'trim|required');
            }
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
            $this->load->model("insurance_model");

            $InsuranceGUID = $this->insurance_model->update_insurance($InsuranceCardGUID, $IsImageType, $FrontImage, $BackImage, $InsuranceProvider, $EmployerGroupName, $ContractPlanNumber, $GroupNumber, $EmployeeStudentNumber, $Comment);

            $this->_response["Data"] = $this->insurance_model->get_insurance_by_guid($InsuranceGUID);
            $this->_response["Message"] = "Insurance updated successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_post() {

        $this->_response["ServiceName"] = "insurance/delete";

        $this->form_validation->set_rules('InsuranceCardGUID', 'Insurance Card GUID', 'trim|required');

        $InsuranceCardGUID = safe_array_key($this->_data, "InsuranceCardGUID", NULL);

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
            $this->insurance_model->delete_insurance($InsuranceCardGUID);
            $this->_response["Message"] = "Insurance deleted successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
