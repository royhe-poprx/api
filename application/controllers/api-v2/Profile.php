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
class Profile extends REST_Controller {

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

        $this->_response["ServiceName"] = "profile/index";

        $UserID = $this->rest->UserID;

        $this->load->model("profile_model");
        $Profiles = $this->profile_model->profiles($UserID);
        $this->_response["Data"] = $Profiles;
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function create_post() {

        $this->_response["ServiceName"] = "profile/create";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('FirstName', 'First name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last name', 'trim|required');
        $this->form_validation->set_rules('DOB', 'DOB', 'trim|required');
        $this->form_validation->set_rules('Gender', 'Gender', 'trim|required');
        $this->form_validation->set_rules('PhinNumber', 'Phin Number', 'trim');
        $this->form_validation->set_rules('PhoneNumber', 'Phone Number', 'trim');
        $this->form_validation->set_rules('ProfilePicture', 'Profile Picture', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("profile_model");

            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];
            $Gender = $this->_data['Gender'];
            $DOB = $this->_data['DOB'];
            $PhinNumber = safe_array_key($this->_data, "PhinNumber", NULL);
            $PhoneNumber = safe_array_key($this->_data, "PhoneNumber", NULL);
            $ProfilePicture = safe_array_key($this->_data, "ProfilePicture", NULL);

            $ProfileID = $this->profile_model->create_profile($UserID, $FirstName, $LastName, $Gender, $DOB, $PhinNumber, $PhoneNumber, $ProfilePicture);
            $Profile = $this->profile_model->get_profile_by_user_id($ProfileID);
            $this->_response["Data"] = $Profile;
            $this->_response["Message"] = "New profile has been added successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_post() {

        $this->_response["ServiceName"] = "profile/update";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('FirstName', 'First name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last name', 'trim|required');
        $this->form_validation->set_rules('DOB', 'DOB', 'trim|required');
        $this->form_validation->set_rules('Gender', 'Gender', 'trim|required');
        $this->form_validation->set_rules('PhinNumber', 'Phin Number', 'trim');
        $this->form_validation->set_rules('PhoneNumber', 'Phone Number', 'trim|callback__required_for_owner');
        $this->form_validation->set_rules('ProfilePicture', 'Profile Picture', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("profile_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $User = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID,
            ]);

            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];
            $Gender = $this->_data['Gender'];
            $DOB = $this->_data['DOB'];
            $PhinNumber = safe_array_key($this->_data, "PhinNumber", NULL);
            $PhoneNumber = safe_array_key($this->_data, "PhoneNumber", NULL);
            $ProfilePicture = safe_array_key($this->_data, "ProfilePicture", NULL);

            $this->profile_model->update_profile($User['UserID'], $FirstName, $LastName, $Gender, $DOB, $PhinNumber, $PhoneNumber, $ProfilePicture);
            $Profile = $this->profile_model->get_profile_by_user_id($User['UserID']);
            $this->_response["Data"] = $Profile;
            $this->_response["Message"] = "Profile has been updated successfully";

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    function _required_for_owner($Str) {
        $ProfileGUID = $this->_data['ProfileGUID'];
        $User = $this->app->get_row('Users', 'UserID', [
            'UserGUID' => $ProfileGUID,
        ]);
        if ($User['UserID'] == $this->rest->UserID && empty($Str)) {
            $this->form_validation->set_message('_required_for_owner', 'The {field} field is required.');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function view_post() {

        $this->_response["ServiceName"] = "profile/view";

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
            $this->load->model("profile_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $User = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID,
            ]);
            $Profile = $this->profile_model->get_profile_by_user_id($User['UserID']);
            $this->_response["Data"] = $Profile;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_post() {

        $this->_response["ServiceName"] = "profile/delete";

        $UserID = $this->rest->UserID;


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
            $this->load->model("profile_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $User = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID,
            ]);
            if ($User['UserID'] != $UserID) {
                $this->db->delete('Users', [
                    'UserID' => $User['UserID'],
                ]);

                $this->db->delete('UserDependents', [
                    'DependentUserID' => $User['UserID'],
                ]);
                $this->_response["Message"] = "Profile has been deleted successfully";
            } else {
                $this->_response["Message"] = "You can not delete master profile";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function switch_post() {

        $this->_response["ServiceName"] = "profile/switch";

        $LoginSessionKey = $this->rest->LoginSessionKey;
        $UserID = $this->rest->UserID;

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
            $this->load->model("profile_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $User = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID
            ]);

            if (!is_null($User)) {
                $this->db->update('ActiveLogins', [
                    'ActiveUserID' => $User['UserID']
                        ], [
                    'LoginSessionKey' => $LoginSessionKey
                ]);
                $this->_response["Data"] = $this->app->user_data($LoginSessionKey, TRUE);
                $this->_response["Message"] = "Profile has been switched successfully";
            } else {
                $this->_response["Message"] = "Profile has been deleted or not active";
            }


            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function quote_items_post() {

        $this->_response["ServiceName"] = "profile/quote_items";

        $UserID = $this->rest->ActiveUserID;
        
        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {

            $this->load->model("order_model");
            $this->load->model("medication_model");
            $NewRxItems = $this->order_model->newrx_quote_items($UserID);
            $RefillItems = $this->order_model->refill_quote_items($UserID);
            $QuoteOrder = $this->medication_model->find_draft_order_by_type($UserID, 'QUOTE_ORDER');
            $TotalItems = count($NewRxItems) + count($RefillItems);
            if ($TotalItems > 0) {
                $this->_response["Data"] = [
                    'OrderGUID' => $QuoteOrder['OrderGUID'],
                    'NewRx' => $NewRxItems,
                    'Refills' => $RefillItems,
                ];
                $this->_response["TotalRecords"] = $TotalItems;
            } else {
                $this->_response["Message"] = "Cart is empty.";
                $this->_response["TotalRecords"] = 0;
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
