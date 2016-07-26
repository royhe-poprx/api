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
class Sti extends REST_Controller {

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

    public function create_post() {

        $this->_response["ServiceName"] = "sti/create";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('IsAuto', 'Is Auto', 'trim|required|in_list[0,1]');
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        $Profile = $this->app->get_row('Users', 'UserID', [
            "UserGUID" => $ProfileGUID
        ]);
        $UserID = $Profile['UserID'];
        $IsAuto = safe_array_key($this->_data, "IsAuto", 0);
        if ($IsAuto == 0) {
            $this->form_validation->set_rules('Carrier', 'Carrier', 'trim|required');
            $this->form_validation->set_rules('Group', 'Group', 'trim|required');
            $this->form_validation->set_rules('STI', 'STI', 'trim|required');
        } else {
            $this->form_validation->set_rules('FirstName', 'FirstName', 'trim|required');
            $this->form_validation->set_rules('LastName', 'LastName', 'trim|required');
            $this->form_validation->set_rules('AddressLine1', 'AddressLine1', 'trim|required');
            $this->form_validation->set_rules('City', 'City', 'trim|required');
            $this->form_validation->set_rules('Province', 'Province', 'trim|required');
            $this->form_validation->set_rules('PostalCode', 'PostalCode', 'trim|required');
            $this->form_validation->set_rules('Gender', 'Gender', 'trim|required');
            $this->form_validation->set_rules('DateOfBirth', 'DateOfBirth', 'trim|required');
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

            $this->load->model("sti_model");
            if ($IsAuto == 1) {
                $FirstName = safe_array_key($this->_data, "FirstName", "");
                $LastName = safe_array_key($this->_data, "LastName", "");
                $AddressLine1 = safe_array_key($this->_data, "AddressLine1", "");
                $City = safe_array_key($this->_data, "City", "");
                $Province = safe_array_key($this->_data, "Province", "");
                $PostalCode = safe_array_key($this->_data, "PostalCode", "");
                $Gender = safe_array_key($this->_data, "Gender", "");
                $DateOfBirth = safe_array_key($this->_data, "DateOfBirth", "");
                $UserStiCardGUID = $this->sti_model->create_auto_sti($UserID, $FirstName, $LastName, $AddressLine1, $City, $Province, $PostalCode, $Gender, $DateOfBirth);
            } else {
                $Carrier = safe_array_key($this->_data, "Carrier", "");
                $Group = safe_array_key($this->_data, "Group", "");
                $STI = safe_array_key($this->_data, "STI", "");
                $UserStiCardGUID = $this->sti_model->create_sti($UserID, $Carrier, $Group, $STI);
            }
            if (is_array($UserStiCardGUID)) {
                $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
                $this->_response["Message"] = $UserStiCardGUID['Content'];
            }else{
                $this->_response["Data"] = $this->sti_model->get_sti_by_guid($UserStiCardGUID); 
            }            
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_post() {

        $this->_response["ServiceName"] = "sti/update";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('UserStiCardGUID', 'User Sti Card GUID', 'trim|required');

        $this->form_validation->set_rules('Carrier', 'Carrier', 'trim|required');
        $this->form_validation->set_rules('Group', 'Group', 'trim|required');
        $this->form_validation->set_rules('STI', 'STI', 'trim|required');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {

            $this->load->model("sti_model");
            $UserStiCardGUID = safe_array_key($this->_data, "UserStiCardGUID", "");
            $Carrier = safe_array_key($this->_data, "Carrier", "");
            $Group = safe_array_key($this->_data, "Group", "");
            $STI = safe_array_key($this->_data, "STI", "");
            $this->sti_model->update_sti($UserStiCardGUID, $Carrier, $Group, $STI);
            $this->_response["Data"] = $this->sti_model->get_sti_by_guid($UserStiCardGUID);
            $this->_response["Message"] = "STI card has been update successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
