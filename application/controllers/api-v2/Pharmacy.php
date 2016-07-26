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
class Pharmacy extends REST_Controller {

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

    public function signin_post() {

        $this->_response["ServiceName"] = "pharmacy/signin";

        $this->form_validation->set_rules('SourceType', 'Source type', 'trim|required|in_list[' . implode($this->app->SourceTypes, ",") . ']');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|in_list[' . implode($this->app->DeviceTypes, ",") . ']');

        $SourceType = safe_array_key($this->_data, "SourceType", "");
        $SourceTypeID = array_search($SourceType, $this->app->SourceTypes);

        $DeviceType = safe_array_key($this->_data, "DeviceType", "");
        $DeviceTypeID = array_search($DeviceType, $this->app->DeviceTypes);

        //other conditional required things
        if ($SourceTypeID == "1") {
            $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email');
            $this->form_validation->set_rules('Password', 'Password', 'trim|required');
        } else {
            $this->form_validation->set_rules('SourceTokenID', 'Source Token id', 'trim|required');
        }

        if (in_array($DeviceTypeID, array("3", "5", "6"))) {
            $this->form_validation->set_rules('DeviceToken', 'Device token', 'trim|required');
            $this->form_validation->set_rules('UniqueDeviceToken', 'Unique device token', 'trim|required');
        } elseif (in_array($DeviceTypeID, array("2", "4"))) {
            $this->form_validation->set_rules('DeviceToken', 'Device token', 'trim|required');
        }

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            //$this->set_response($this->_response, self::HTTP_FORBIDDEN); 
            $this->set_response($this->_response);
        } else {
            $check = 0;
            if ($SourceTypeID != "1") {
                //create user login for social
                $LoginKeyword = safe_array_key($this->_data, "SourceTokenID", "");
                $User = $this->db->get_where('UserLogins', [
                            "LoginKeyword" => $LoginKeyword,
                            "SourceID" => $SourceTypeID,
                        ])->row_array();

                if (is_null($User)) {
                    $check = 1;
                }
            } else {
                $Email = $this->_data['Email'];
                $Password = $this->_data['Password'];
                $User = $this->app->get_row('UserLogins', 'UserID', [
                    "LoginKeyword" => strtolower($Email),
                    "Password" => md5($Password),
                    "SourceID" => "1",
                ]);
                if (empty($User)) {
                    $User = $this->app->get_row('UserLogins', 'UserID', [
                        "LoginKeyword" => strtolower($Email),
                        "TmpPass" => $Password,
                        "SourceID" => "1",
                    ]);
                    if (!empty($User)) {
                        $this->db->update('UserLogins', [
                            'Password' => md5($Password),
                            'TmpPass' => NULL,
                            'ModifiedDate' => DATETIME,
                                ], [
                            'UserID' => $User['UserID'],
                            'SourceID' => 1,
                        ]);
                        $this->db->update('Users', [
                            'ForceChangePassword' => 1,
                            'ModifiedDate' => DATETIME,
                                ], [
                            'UserID' => $User['UserID'],
                        ]);
                    }
                }
                if (empty($User)) {
                    $check = 2;
                }
            }
            if ($check == 1) {
                $this->_response["Message"] = "Create new social account with this.";
                $this->_response["StatusCode"] = self::HTTP_ACCEPTED;
                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                $this->set_response($this->_response);
            } elseif ($check == 2) {
                $this->_response["StatusCode"] = self::HTTP_UNPROCESSABLE_ENTITY;
                $this->_response["Message"] = "Email or password do not match";
                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                //$this->set_response($this->_response, self::HTTP_FORBIDDEN);
                $this->set_response($this->_response);
            } else {

                $this->load->model("user_model");


                $Latitude = safe_array_key($this->_data, "Latitude", NULL);
                $Longitude = safe_array_key($this->_data, "Longitude", NULL);
                $IPAddress = safe_array_key($this->_data, "IPAddress", $this->input->ip_address());
                $AppAPIVersion = safe_array_key($this->_data, "AppAPIVersion", "poprx-1.0");

                $DeviceToken = safe_array_key($this->_data, "DeviceToken", NULL);
                $UniqueDeviceToken = safe_array_key($this->_data, "UniqueDeviceToken", NULL);


                $LoginSessionKey = $this->user_model->create_active_login($User['UserID'], $SourceTypeID, $DeviceTypeID, $DeviceToken, $UniqueDeviceToken, $Latitude, $Longitude, $IPAddress, $AppAPIVersion);
                $this->_response["Data"] = $this->app->pharmacy_user_data($LoginSessionKey);

                $this->load->model('wallet_model');
                $this->wallet_model->create_wallet($User['UserID']);

                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                $this->set_response($this->_response);
            }
        }
    }

    public function signout_post() {

        $this->_response["ServiceName"] = "pharmacy/signout";

        $LoginSessionKey = $this->rest->key;
        $this->db->delete('ActiveLogins', [
            "LoginSessionKey" => $LoginSessionKey,
        ]);
        $this->_response["Message"] = "logged out successfully";
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function change_password_post() {

        $this->_response["ServiceName"] = "pharmacy/change_password";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('OldPassword', 'OldPassword', 'trim|required');
        $this->form_validation->set_rules('Password', 'Password', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $OldPassword = safe_array_key($this->_data, "OldPassword", NULL);
            $Password = safe_array_key($this->_data, "Password", NULL);
            $this->db->update('UserLogins', [
                'Password' => md5($Password),
                'TmpPass' => NULL,
                'ModifiedDate' => DATETIME,
                    ], [
                'UserID' => $UserID,
                'Password' => md5($OldPassword),
            ]);
            $this->db->update('Users', [
                'ForceChangePassword' => 0,
                'ModifiedDate' => DATETIME,
                    ], [
                'UserID' => $UserID,
            ]);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function forgotpassword_post() {

        $this->_response["ServiceName"] = "user/forgotpassword";

        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|in_list[' . implode($this->app->DeviceTypes, ",") . ']');

        $DeviceType = safe_array_key($this->_data, "DeviceType", "");
        $DeviceTypeID = array_search($DeviceType, $this->app->DeviceTypes);

        if (in_array($DeviceTypeID, array("3", "5", "6"))) {
            $this->form_validation->set_rules('DeviceToken', 'Device token', 'trim|required');
            $this->form_validation->set_rules('UniqueDeviceToken', 'Unique device token', 'trim|required');
        } elseif (in_array($DeviceTypeID, array("2", "4"))) {
            $this->form_validation->set_rules('DeviceToken', 'Device token', 'trim|required');
        }
        $this->form_validation->set_rules('RecoveryType', 'Recovery Type', 'trim|required|in_list[URL,PASS]');
        $this->form_validation->set_rules('Value', 'Value', 'trim|required|callback__should_exist_email');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            //$this->set_response($this->_response, self::HTTP_FORBIDDEN); 
            $this->set_response($this->_response);
        } else {
            $RecoveryType = safe_array_key($this->_data, "RecoveryType", "");
            $Value = safe_array_key($this->_data, "Value", "");

            if ($RecoveryType == "PASS") {
                $RandomPassword = random_string('alnum', 6);
                $User = $this->app->get_row('Users', 'UserGUID, Email, FirstName, LastName, UserID', [
                    'Email' => strtolower($Value),
                ]);

                if (!empty($User)) {
                    $this->db->update('UserLogins', [
                        'TmpPass' => $RandomPassword,
                        'ModifiedDate' => DATETIME,
                            ], [
                        'UserID' => $User['UserID'],
                        'SourceID' => 1,
                    ]);

                    $Variables = [
                        'UserID' => $User['UserID'],
                        'Email' => $User['Email'],
                        'FirstName' => $User['FirstName'],
                        'LastName' => $User['LastName'],
                        'UserGUID' => $User['UserGUID'],
                        'TmpPass' => $RandomPassword,
                    ];
                    process_in_backgroud("PasswordRecoveryEmail", $Variables);

                    $this->_response["Message"] = "New password has been sent to connected email address.";
                    $this->benchmark->mark('code_end');
                    $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                    $this->set_response($this->_response);
                }
            } elseif ($RecoveryType == "URL") {
                
            }
        }
    }

    public function view_post() {

        $this->_response["ServiceName"] = "pharmacy/view";

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
            $this->load->model("pharmacy_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $User = $this->app->get_row('Users', 'UserID, UserTypeID', [
                'UserGUID' => $ProfileGUID,
            ]);
            if ($User['UserTypeID'] == 3) {
                $Profile = $this->app->get_pharmacy_by_user_id($User['UserID']);
            } else {
                $Profile = $this->app->get_profile_by_user_id($User['UserID']);
                if (!empty($Profile)) {
                    $Profile['ProfileGUID'] = $Profile['UserGUID'];
                }
            }
            $this->_response["Data"] = $Profile;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function get_badge_post() {

        $this->_response["ServiceName"] = "pharmacy/get_badge";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim');


        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("chat_model");
            $Badge = [
                'OrderBadge' => 10,
                'ChatBadge' => $this->chat_model->unread_chat_count($UserID),
            ];
            $this->_response["Data"] = $Badge;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_post() {

        $this->_response["ServiceName"] = "pharmacy/update";

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
            $this->load->model("pharmacy_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $User = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID,
            ]);

            $Profile = $this->app->get_pharmacy_by_user_id($User['UserID']);
            $this->_response["Data"] = $Profile;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_message_post() {

        $this->_response["ServiceName"] = "pharmacy/update_message";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('AboutMe', 'AboutMe', 'trim|required');
        $this->form_validation->set_rules('ChatMessage', 'Chat Message', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $AboutMe = $this->_data['AboutMe'];
            $ChatMessage = $this->_data['ChatMessage'];

            $User = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID,
            ]);

            $this->db->update('Users', [
                'AboutMe' => $AboutMe,
                'ChatMessage' => $ChatMessage,
                    ], [
                'UserGUID' => $ProfileGUID,
            ]);

            $Profile = $this->app->get_pharmacy_by_user_id($User['UserID']);
            $this->_response["Data"] = $Profile;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function patients_post() {

        $this->_response["ServiceName"] = "pharmacy/patients";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('Limit', 'Limit', 'trim|required');
        $this->form_validation->set_rules('Offset', 'Offset', 'trim|required');
        $this->form_validation->set_rules('Extra', 'Extra', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $Extra = safe_array_key($this->_data, "Extra", NULL);
            $this->_response["TotalRecords"] = $this->pharmacy_model->patients($UserID, 1, NULL, NULL, $Extra);
            $Patients = $this->pharmacy_model->patients($UserID, NULL, $Limit, $Offset, $Extra);
            if (!empty($Patients)) {
                $this->_response["Data"] = $Patients;
            } else {
                $this->_response["Message"] = "No Patients(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function patients_for_chat_post() {

        $this->_response["ServiceName"] = "pharmacy/patients_for_chat";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('Limit', 'Limit', 'trim|required');
        $this->form_validation->set_rules('Offset', 'Offset', 'trim|required');
        $this->form_validation->set_rules('Keyword', 'Keyword', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $Keyword = safe_array_key($this->_data, "Keyword", "");
            $this->_response["TotalRecords"] = $this->pharmacy_model->patients_for_chat($UserID, $Keyword, 1);
            $Patients = $this->pharmacy_model->patients_for_chat($UserID, $Keyword, NULL, $Limit, $Offset);
            if (!empty($Patients)) {
                $this->_response["Data"] = $Patients;
            } else {
                $this->_response["Message"] = "No Patients(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function create_patient_post() {

        $this->_response["ServiceName"] = "pharmacy/create_patient";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('FirstName', 'First name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last name', 'trim|required');
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__unique_email');
        $this->form_validation->set_rules('PharmacyGUID', 'PharmacyGUID', 'trim');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");

            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];
            $Email = $this->_data['Email'];
            $PharmacyGUID = safe_array_key($this->_data, "PharmacyGUID", "");
            $Pharmacist = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $PharmacyGUID,
            ]);
            $PharmacistID = safe_array_key($Pharmacist, "UserID", "");
            $ProfileID = $this->pharmacy_model->create_patient($UserID, $PharmacistID, $FirstName, $LastName, $Email);
            $Profile = $this->app->get_profile_by_user_id($ProfileID);
            $this->_response["Data"] = $Profile;
            $this->_response["Message"] = "New patient has been added successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _unique_email($str) {
        $where = [
            "LoginKeyword" => $str,
            "SourceID" => "1",
        ];
        $rows = $this->app->get_rows('UserLogins', 'UserID', $where);
        if (count($rows) > 0) {
            $this->form_validation->set_message('_unique_email', '{field} already exists');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function view_patient_post() {

        $this->_response["ServiceName"] = "pharmacy/view_patient";

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
            $this->load->model("pharmacy_model");
            $ProfileGUID = $this->_data['ProfileGUID'];
            $Profile = $this->pharmacy_model->get_patient_detail_by_guid($ProfileGUID);
            $this->_response["Data"] = $Profile;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_patient_post() {

        $this->_response["ServiceName"] = "pharmacy/delete_patient";

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
            $this->load->model("pharmacy_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $this->pharmacy_model->delete_patient($UserID, $ProfileGUID);

            $this->_response["Message"] = "Patient has been deleted successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function orders_post() {

        $this->_response["ServiceName"] = "pharmacy/orders";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('Filter', 'Filter', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $Extra = safe_array_key($this->_data, "Extra", NULL);
            $Orders = $this->pharmacy_model->orders($UserID, $Extra);
            //print_r($Orders); die();
            $this->_response["TotalRecords"] = count($Orders);
            if (!empty($Orders)) {
                $this->_response["Data"] = $Orders;
            } else {
                $this->_response["Message"] = "No Order(s) pending.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function change_order_status_post() {
        $this->_response["ServiceName"] = "pharmacy/change_order_status";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');
        $this->form_validation->set_rules('OrderMedications', 'Order Medications', 'trim');
        $this->form_validation->set_rules('TargetStatus', 'Target Status', 'trim|required|in_list[DRAFT,REJECTED,ONROUTE,COMPLETED]|callback__is_target_status_ready');

        $TargetStatus = safe_array_key($this->_data, "TargetStatus", NULL);
        $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
        $Order = $this->app->get_row('Orders', 'OrderID, OrderType, TransferWithQuote, Status', [
            'OrderGUID' => $OrderGUID,
        ]);
        $OrderType = safe_array_key($Order, 'OrderType', '');
        $TransferWithQuote = safe_array_key($Order, 'TransferWithQuote', '');
        if ($TargetStatus == 'REJECTED') {
            $this->form_validation->set_rules('RejectReason', 'Reject Reason', 'trim|required');
        } else {
            if ($OrderType == "QUOTE_ORDER" || ($OrderType == "TRANSFER_ORDER" && $TransferWithQuote == 1)) {
                $this->form_validation->set_rules('SubTotal', 'Sub Total', 'trim|required');
                $this->form_validation->set_rules('Tax', 'Tax', 'trim|required');
                $this->form_validation->set_rules('DiscountAmount', 'Discount Amount', 'trim|required');
                $this->form_validation->set_rules('DiscountCode', 'Discount Code', 'trim');
                $this->form_validation->set_rules('GrandTotal', 'Grand Total', 'trim|required');
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

            $this->load->model("pharmacy_model");

            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $OrderMedications = safe_array_key($this->_data, "OrderMedications", []);
            $TargetStatus = safe_array_key($this->_data, "TargetStatus", NULL);
            $RejectReason = safe_array_key($this->_data, "RejectReason", "");

            $SubTotal = safe_array_key($this->_data, "SubTotal", NULL);
            $Tax = safe_array_key($this->_data, "Tax", NULL);
            $DiscountAmount = safe_array_key($this->_data, "DiscountAmount", NULL);
            $DiscountCode = safe_array_key($this->_data, "DiscountCode", NULL);
            $GrandTotal = safe_array_key($this->_data, "GrandTotal", NULL);


            if ($TargetStatus == 'DRAFT') {
                $this->pharmacy_model->save_draft_order($UserID, $OrderGUID, $OrderMedications, $SubTotal, $Tax, $DiscountAmount, $DiscountCode, $GrandTotal);
            } elseif ($TargetStatus == 'REJECTED') {
                $this->pharmacy_model->reject_order($UserID, $OrderGUID, $RejectReason);
            } elseif ($TargetStatus == 'ONROUTE') {
                $this->pharmacy_model->save_draft_order($UserID, $OrderGUID, $OrderMedications, $SubTotal, $Tax, $DiscountAmount, $DiscountCode, $GrandTotal);
                $this->pharmacy_model->onroute_order($UserID, $OrderGUID);
            } elseif ($TargetStatus == 'COMPLETED') {
                $this->pharmacy_model->save_draft_order($UserID, $OrderGUID, $OrderMedications, $SubTotal, $Tax, $DiscountAmount, $DiscountCode, $GrandTotal);
                $this->pharmacy_model->complete_order($UserID, $OrderGUID);
            }

            $Order = $this->pharmacy_model->one_orders($OrderGUID);
            $this->_response["Data"] = $Order;

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _is_target_status_ready($Str) {
        $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
        $Order = $this->app->get_row('Orders', 'OrderID, OrderGUID, OrderType, Status', [
            'OrderGUID' => $OrderGUID,
        ]);

        return TRUE;
    }

    public function delete_order_medication_post() {

        $this->_response["ServiceName"] = "pharmacy/delete_order_medication";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('OrderMedicationGUID', 'Order Medication GUID', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $OrderMedicationGUID = safe_array_key($this->_data, "OrderMedicationGUID", NULL);
            $OrderMedication = $this->app->get_row('OrderMedications', 'OrderID', [
                'OrderMedicationGUID' => $OrderMedicationGUID,
            ]);
            $OrderID = safe_array_key($OrderMedication, "OrderID", NULL);

            $OrderMedications = $this->app->get_rows('OrderMedications', 'OrderMedicationGUID', [
                'OrderID' => $OrderID,
            ]);
            if (count($OrderMedications) > 1) {
                $this->db->delete('OrderMedications', [
                    'OrderMedicationGUID' => $OrderMedicationGUID,
                ]);
            } else {
                $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
                $this->_response["Message"] = "You can not delete all medications from order";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function view_order_post() {
        $this->_response["ServiceName"] = "pharmacy/view_order";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $this->_response["Data"] = $this->pharmacy_model->get_order_detail_by_guid($OrderGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function order_history_post() {

        $this->_response["ServiceName"] = "pharmacy/order_history";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('Extra', 'Extra', 'trim');
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
            $this->load->model("pharmacy_model");
            $Extra = safe_array_key($this->_data, "Extra", NULL);
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $this->_response["TotalRecords"] = $this->pharmacy_model->order_history($UserID, $Extra, 1);
            $OrderHistory = $this->pharmacy_model->order_history($UserID, $Extra, NULL, $Limit, $Offset);
            if (!empty($OrderHistory)) {
                $this->_response["Data"] = $OrderHistory;
            } else {
                $this->_response["Message"] = "No Order(s) history found.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function payment_methods_post() {

        $this->_response["ServiceName"] = "pharmacy/payment_methods";

        $UserID = $this->rest->UserID;
        $PharmacyID = $this->app->get_pharmacy_id_by_user_id($UserID);
        $this->form_validation->set_rules('Filter', 'Filter', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $this->_response["Data"] = $this->pharmacy_model->get_payment_methods($PharmacyID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_payment_methods_post() {

        $this->_response["ServiceName"] = "pharmacy/update_payment_methods";

        $UserID = $this->rest->UserID;
        $PharmacyID = $this->app->get_pharmacy_id_by_user_id($UserID);

        $this->form_validation->set_rules('PaymentMethods', 'PaymentMethods', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $PaymentMethods = safe_array_key($this->_data, "PaymentMethods", []);

            $this->pharmacy_model->update_payment_methods($PharmacyID, $PaymentMethods);
            $this->_response["Data"] = $this->pharmacy_model->get_payment_methods($PharmacyID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function view_update_order_post() {

        $this->_response["ServiceName"] = "pharmacy/view_update_order";

        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("payment_model");
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", "");
            $Order = $this->app->get_row('Orders', 'UserID, PharmacyUserID', [
                'OrderGUID' => $OrderGUID
            ]);
            $Out['payment_methods'] = $this->payment_model->methods($Order['UserID'], $Order['PharmacyUserID']);
            $this->_response["Data"] = $Out;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_order_post() {

        $this->_response["ServiceName"] = "pharmacy/update_order";

        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');
        $this->form_validation->set_rules('SelfPickUp', 'Self Pick Up', 'trim|required|in_list[0,1]');


        $this->form_validation->set_rules('PaymentMethodType', 'Payment Method Type', 'trim|required|in_list[CUSTOM,CC]');
        $this->form_validation->set_rules('PaymentTypeGUID', 'Payment Type GUID', 'trim|required');

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
            $this->load->model("pharmacy_model");
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $SelfPickUp = safe_array_key($this->_data, "SelfPickUp", NULL);
            $PaymentMethodType = safe_array_key($this->_data, "PaymentMethodType", NULL);
            $PaymentTypeGUID = safe_array_key($this->_data, "PaymentTypeGUID", NULL);

            $this->db->update('Orders', [
                'IsPickup' => $SelfPickUp,
                'PaymentMethodType' => $PaymentMethodType,
                'PaymentTypeID' => $this->order_model->get_payment_type_id_by_guid($PaymentMethodType, $PaymentTypeGUID),
                'UpdatedAt' => DATETIME,
                'UpdatedBy' => $UserID,
                    ], [
                'OrderGUID' => $OrderGUID
            ]);

            $Order = $this->pharmacy_model->one_orders($OrderGUID);
            $this->_response["Data"] = $Order;

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_order_delivery_date_post() {

        $this->_response["ServiceName"] = "pharmacy/update_order_delivery_date";

        $UserID = $this->rest->UserID;


        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');

        $this->form_validation->set_rules('DeliveryDate', 'DeliveryDate', 'trim|required');
        $this->form_validation->set_rules('DeliveryDateMax', 'DeliveryDateMax', 'trim|required');

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
            $this->load->model("pharmacy_model");
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $DeliveryDate = safe_array_key($this->_data, "DeliveryDate", NULL);
            $DeliveryDateMax = safe_array_key($this->_data, "DeliveryDateMax", NULL);

            $this->db->update('Orders', [
                'DeliveryDate' => $DeliveryDate,
                'DeliveryDateMax' => $DeliveryDateMax,
                'UpdatedAt' => DATETIME,
                'UpdatedBy' => $UserID,
                    ], [
                'OrderGUID' => $OrderGUID
            ]);

            $Order = $this->pharmacy_model->one_orders($OrderGUID);
            $this->_response["Data"] = $Order;

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function opening_hours_post() {

        $this->_response["ServiceName"] = "pharmacy/opening_hours";

        $UserID = $this->rest->UserID;
        $PharmacyID = $this->app->get_pharmacy_id_by_user_id($UserID);
        $this->form_validation->set_rules('Filter', 'Filter', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $this->_response["Data"] = $this->pharmacy_model->get_opening_hours($PharmacyID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_opening_hours_post() {

        $this->_response["ServiceName"] = "pharmacy/update_opening_hours";

        $UserID = $this->rest->UserID;
        $PharmacyID = $this->app->get_pharmacy_id_by_user_id($UserID);

        $this->form_validation->set_rules('OpeningHours', 'OpeningHours', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $OpeningHours = safe_array_key($this->_data, "OpeningHours", []);

            $this->pharmacy_model->update_opening_hours($PharmacyID, $OpeningHours);
            $this->_response["Data"] = $this->pharmacy_model->get_opening_hours($PharmacyID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delivery_slots_post() {

        $this->_response["ServiceName"] = "pharmacy/delivery_slots";

        $UserID = $this->rest->UserID;
        $PharmacyID = $this->app->get_pharmacy_id_by_user_id($UserID);
        $this->form_validation->set_rules('Filter', 'Filter', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $this->_response["Data"] = $this->pharmacy_model->get_delivery_slots($PharmacyID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_delivery_slots_post() {

        $this->_response["ServiceName"] = "pharmacy/update_delivery_slots";

        $UserID = $this->rest->UserID;
        $PharmacyID = $this->app->get_pharmacy_id_by_user_id($UserID);

        $this->form_validation->set_rules('DeliverySlots', 'DeliverySlots', 'trim');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $DeliverySlots = safe_array_key($this->_data, "DeliverySlots", []);

            $this->pharmacy_model->update_delivery_slots($PharmacyID, $DeliverySlots);
            $this->_response["Data"] = $this->pharmacy_model->get_delivery_slots($PharmacyID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function billing_info_post() {

        $this->_response["ServiceName"] = "pharmacy/billing_info";

        $UserID = $this->rest->UserID;
        $PharmacyID = $this->app->get_pharmacy_id_by_user_id($UserID);
        $this->form_validation->set_rules('Filter', 'Filter', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $this->_response["Data"] = $this->pharmacy_model->get_billing_info($PharmacyID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_billing_info_post() {

        $this->_response["ServiceName"] = "pharmacy/update_billing_info";

        $UserID = $this->rest->UserID;
        $PharmacyID = $this->app->get_pharmacy_id_by_user_id($UserID);

        $this->form_validation->set_rules('TaxPercentage', 'Tax Percentage', 'trim|required');
        $this->form_validation->set_rules('DispensingFee', 'Dispensing Fee', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("pharmacy_model");
            $TaxPercentage = safe_array_key($this->_data, "TaxPercentage", NULL);
            $DispensingFee = safe_array_key($this->_data, "DispensingFee", NULL);

            $this->pharmacy_model->update_billing_info($PharmacyID, $TaxPercentage, $DispensingFee);
            $this->_response["Data"] = $this->pharmacy_model->get_billing_info($PharmacyID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function create_order_post() {

        $this->_response["ServiceName"] = "pharmacy/create_order";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required');
        $this->form_validation->set_rules('OrderType', 'OrderType', 'trim|required|in_list[TRANSFER_ORDER,QUOTE_ORDER,DELIVERY_ORDER]');
        $this->form_validation->set_rules('OrderMedications', 'Order Medications', 'trim');

        $UserGUID = safe_array_key($this->_data, "UserGUID", NULL);
        $Medications = safe_array_key($this->_data, "Medications", []);
        $OrderType = safe_array_key($this->_data, 'OrderType', '');
        $ImportAll = safe_array_key($this->_data, "ImportAll", NULL);
        $TPName = safe_array_key($this->_data, "PharmacyName", NULL);
        $TPPhone = safe_array_key($this->_data, "PharmacyPhone", NULL);


        $SubTotal = safe_array_key($this->_data, "SubTotal", NULL);
        $Tax = safe_array_key($this->_data, "Tax", NULL);
        $DiscountAmount = safe_array_key($this->_data, "DiscountAmount", NULL);
        $DiscountCode = safe_array_key($this->_data, "DiscountCode", NULL);
        $GrandTotal = safe_array_key($this->_data, "GrandTotal", NULL);

        if ($OrderType == "TRANSFER_ORDER") {
            $this->form_validation->set_rules('ImportAll', 'Import All', 'trim|required|in_list[0,1]');
            $this->form_validation->set_rules('PharmacyName', 'Pharmacy Name', 'trim|required');
            $this->form_validation->set_rules('PharmacyPhone', 'Pharmacy Phone', 'trim');
        } elseif ($OrderType == "QUOTE_ORDER") {
            $this->form_validation->set_rules('SubTotal', 'Sub Total', 'trim|required');
            $this->form_validation->set_rules('Tax', 'Tax', 'trim|required');
            $this->form_validation->set_rules('DiscountAmount', 'Discount Amount', 'trim|required');
            $this->form_validation->set_rules('DiscountCode', 'Discount Code', 'trim');
            $this->form_validation->set_rules('GrandTotal', 'Grand Total', 'trim|required');
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
            $this->load->model("pharmacy_model");
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $UserGUID
            ]);
            $PatientUserID = $Profile['UserID'];
            if ($OrderType == 'TRANSFER_ORDER') {
                if ($ImportAll == 1 || ($ImportAll == 0 && count($Medications) > 0)) {
                    $OrderGUID = $this->pharmacy_model->create_tx_order($PatientUserID, $UserID, $TPName, $TPPhone, $ImportAll, $Medications);
                    $Order = $this->pharmacy_model->one_orders($OrderGUID);
                    $this->_response["Data"] = $Order;
                } elseif ($ImportAll == 0 && count($Medications) < 1) {
                    $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
                    $this->_response["Message"] = "Medications are required.";
                }
            } elseif ($OrderType == 'QUOTE_ORDER') {
                $OrderGUID = $this->pharmacy_model->create_qx_order($PatientUserID, $UserID, $Medications);
                $O = $this->app->get_row('Orders', 'OrderID', [
                    'OrderGUID' => $OrderGUID
                ]);
                $OrderMedications = $this->pharmacy_model->get_order_medications($O['OrderID']);
                $this->pharmacy_model->save_draft_order($UserID, $OrderGUID, $OrderMedications, $SubTotal, $Tax, $DiscountAmount, $DiscountCode, $GrandTotal);
                $this->pharmacy_model->complete_order($UserID, $OrderGUID);
                $Order = $this->pharmacy_model->one_orders($OrderGUID);
                $this->_response["Data"] = $Order;
            }

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function request_for_new_image_post() {

        $this->_response["ServiceName"] = "pharmacy/request_for_new_image";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('MedicationGUID', 'Medication GUID', 'trim|required|callback__request_for_new_image');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);

            $Medication = $this->app->get_row('Medications', 'MedicationID, UserID', [
                'MedicationGUID' => $MedicationGUID,
            ]);
            $ToUserID = safe_array_key($Medication, 'UserID', NULL);
            $MedicationID = safe_array_key($Medication, 'MedicationID', NULL);
            if (!empty($Medication)) {
                $User = $this->app->get_row('Users', 'Email', [
                    'UserID' => $ToUserID,
                ]);
                if (empty($User['Email'])) {
                    $ToUser = $this->app->get_row('UserDependents', 'UserID', [
                        'DependentUserID' => $ToUserID,
                    ]);
                    $ToUserID = safe_array_key($ToUser, 'UserID', NULL);
                }
                $this->db->update('Medications', [
                    "NewImageRequest" => 1,
                        ], [
                    "MedicationGUID" => $MedicationGUID,
                ]);
                $this->app->notify($UserID, $ToUserID, "NEW_IMAGE_REQUEST", $MedicationID);
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_medication_record_post() {

        $this->_response["ServiceName"] = "pharmacy/update_medication_record";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('MedicationGUID', 'Medication GUID', 'trim|required');
        $this->form_validation->set_rules('MedicationImages', 'Medication Images', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $MedicationGUID = safe_array_key($this->_data, "MedicationGUID", NULL);
            $MedicationImages = safe_array_key($this->_data, "MedicationImages", NULL);
            $this->db->update('Medications', [
                "MedicationImages" => $MedicationImages,
                    ], [
                "MedicationGUID" => $MedicationGUID,
            ]);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _request_for_new_image($str) {
        $rows = $this->app->get_rows('Medications', 'MedicationID', [
            "MedicationGUID" => $str,
            "NewImageRequest" => 1,
        ]);
        if (count($rows) > 0) {
            $this->form_validation->set_message('_request_for_new_image', '{field} image already requested');
            return FALSE;
        } else {
            return TRUE;
        }
    }

}
