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
class User extends REST_Controller {

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

    public function signup_post() {

        $this->_response["ServiceName"] = "user/signup";

        $this->form_validation->set_rules('UserType', 'User type', 'trim|required|in_list[' . implode($this->app->UserTypes, ",") . ']');
        $this->form_validation->set_rules('SourceType', 'Source type', 'trim|required|in_list[' . implode($this->app->SourceTypes, ",") . ']');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|in_list[' . implode($this->app->DeviceTypes, ",") . ']');

        $UserType = safe_array_key($this->_data, "UserType", "");
        $UserTypeID = array_search($UserType, $this->app->UserTypes);

        $SourceType = safe_array_key($this->_data, "SourceType", "");
        $SourceTypeID = array_search($SourceType, $this->app->SourceTypes);

        $DeviceType = safe_array_key($this->_data, "DeviceType", "");
        $DeviceTypeID = array_search($DeviceType, $this->app->DeviceTypes);

        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__unique_email');



        //other conditional required things
        if ($SourceTypeID == "1") {
            $this->form_validation->set_rules('Password', 'Password', 'trim|required');
        } else {
            $this->form_validation->set_rules('SourceTokenID', 'Source Token id', 'trim|required|callback__unique_source_token_id[' . $SourceTypeID . ']');
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
            $this->set_response($this->_response);
        } else {
            $this->load->model("user_model");

            $Email = $this->_data['Email'];
            $FirstName = safe_array_key($this->_data, "FirstName", NULL);
            $LastName = safe_array_key($this->_data, "LastName", NULL);
            $PhoneNumber = safe_array_key($this->_data, "PhoneNumber", NULL);
            $DOB = safe_array_key($this->_data, "DOB", NULL);
            $Gender = safe_array_key($this->_data, "Gender", NULL);
            $PhinNumber = safe_array_key($this->_data, "PhinNumber", NULL);
            $ProfilePicture = safe_array_key($this->_data, "ProfilePicture", NULL);

            $SourceTokenID = safe_array_key($this->_data, "SourceTokenID", NULL);
            $Password = safe_array_key($this->_data, "Password", NULL);

            $Latitude = safe_array_key($this->_data, "Latitude", NULL);
            $Longitude = safe_array_key($this->_data, "Longitude", NULL);
            $IPAddress = safe_array_key($this->_data, "IPAddress", $this->input->ip_address());
            $AppAPIVersion = safe_array_key($this->_data, "AppAPIVersion", "poprx-2.0");

            $DeviceToken = safe_array_key($this->_data, "DeviceToken", NULL);
            $UniqueDeviceToken = safe_array_key($this->_data, "UniqueDeviceToken", NULL);


            $User = $this->user_model->create_user($UserTypeID, $Email, $FirstName, $LastName, $PhoneNumber, $DOB, $Gender, $PhinNumber, $ProfilePicture, $SourceTypeID, $SourceTokenID, $Password, $DeviceTypeID, $Latitude, $Longitude);


            //create active login session

            $LoginSessionKey = $this->user_model->create_active_login($User['UserID'], $SourceTypeID, $DeviceTypeID, $DeviceToken, $UniqueDeviceToken, $Latitude, $Longitude, $IPAddress, $AppAPIVersion);

            $this->load->model('wallet_model');
            $this->wallet_model->create_wallet($User['UserID']);

            $CacheTokenGUID = safe_array_key($this->_data, "CacheTokenGUID", "");
            $this->app->save_cache_data($User['UserID'], $CacheTokenGUID);

            $this->_response["Data"] = $this->app->user_data($LoginSessionKey);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _unique_email($str) {
        $where = [
            "LoginKeyword" => strtolower($str),
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

    public function _valid_referral_code($Str) {
        $UserID = $this->rest->UserID;
        if ($Str) {
            $Row = $this->app->get_row('Promos', 'PromoID, AssignTo, PromoGUID, IsActive', [
                "Code" => $Str,
                "PromoType" => "REFERRAL",
            ]);
            if (!empty($Row)) {
                if ($UserID == $Row['AssignTo']) {
                    $this->form_validation->set_message('_valid_referral_code', 'You can not use your own referral code.');
                    return FALSE;
                } elseif ($Row['IsActive'] != 1) {
                    $this->form_validation->set_message('_valid_referral_code', 'Referral code you are using is desabled by Admin.');
                    return FALSE;
                }

                $Sql = "SELECT UserPromoGUID FROM UserPromos WHERE UserID='" . $UserID . "' AND PromoID IN (SELECT PromoID FROM Promos WHERE PromoType='REFERRAL')";
                $Query = $this->db->query($Sql);
                $UserPromos = $Query->row_array();
                if ($Query->num_rows() > 0) {
                    $this->form_validation->set_message('_valid_referral_code', 'You have already used Referral.');
                    return FALSE;
                }
            } else {
                $this->form_validation->set_message('_valid_referral_code', '{field} is expired or not valid.');
                return FALSE;
            }
        }
        return TRUE;
    }

    public function _unique_source_token_id($str, $SourceTypeID) {

        $where = [
            "LoginKeyword" => strtolower($str),
            "SourceID" => $SourceTypeID,
        ];
        $rows = $this->app->get_rows('UserLogins', 'UserID', $where);
        if (count($rows) > 0) {
            $this->form_validation->set_message('_unique_source_token_id', ucfirst(strtolower($this->app->SourceTypes[$SourceTypeID])) . ' account already connected with some other user account');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function signin_post() {

        $this->_response["ServiceName"] = "user/signin";

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
//                $User = $this->db->get_where('UserLogins', [
//                            "LoginKeyword" => strtolower($LoginKeyword),
//                            "SourceID" => $SourceTypeID,
//                        ])->row_array();                
                $User = $this->app->get_row('UserLogins', 'UserID', [
                    "LoginKeyword" => $LoginKeyword,
                    "SourceID" => $SourceTypeID,
                ]);
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
                        $this->db->delete('UserLogins', [
                            'UserID' => $User['UserID'],
                            'SourceID' => 4,
                        ]);
                    }
                }
                if (empty($User)) {
                    $check = 2;
                    //old Google user check
                    $UsersWithEmail = $this->app->get_row('UserLogins', 'UserID', [
                        "LoginKeyword" => strtolower($Email),
                    ]);
                    if (!empty($UsersWithEmail)) {
                        // user found with email
                        $UsersWithEmailUserID = $UsersWithEmail['UserID'];
                        $UsersWithGPlus = $this->app->get_row('UserLogins', 'UserID', [
                            "UserID" => $UsersWithEmailUserID,
                            'SourceID' => 4,
                        ]);
                        if (!empty($UsersWithGPlus)) {
                            $check = 3;
                        }
                    }
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
            } elseif ($check == 3) {
                $this->_response["StatusCode"] = self::HTTP_UNPROCESSABLE_ENTITY;
                $this->_response["Message"] = "Oops... We no longer work with Google + login. Please click Forget Password to reset your password and Login again. Sorry for the inconvenience caused";
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
                $UniqueDeviceToken = safe_array_key($this->_data, "UniqueDeviceToken", "");


                $LoginSessionKey = $this->user_model->create_active_login($User['UserID'], $SourceTypeID, $DeviceTypeID, $DeviceToken, $UniqueDeviceToken, $Latitude, $Longitude, $IPAddress, $AppAPIVersion);
                $this->_response["Data"] = $this->app->user_data($LoginSessionKey);

                $this->load->model('wallet_model');
                $this->wallet_model->create_wallet($User['UserID']);

                $CacheTokenGUID = safe_array_key($this->_data, "CacheTokenGUID", "");
                $this->app->save_cache_data($User['UserID'], $CacheTokenGUID);

                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                $this->set_response($this->_response);
            }
        }
    }

    public function signout_post() {

        $this->_response["ServiceName"] = "user/signout";

        $LoginSessionKey = $this->rest->LoginSessionKey;
        $this->db->delete('ActiveLogins', [
            "LoginSessionKey" => $LoginSessionKey,
        ]);
        $this->_response["Message"] = "logged out successfully";
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function change_password_post() {

        $this->_response["ServiceName"] = "user/change_password";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('OldPassword', 'OldPassword', 'trim|required|callback__check_old_password');
        $this->form_validation->set_rules('Password', 'Password', 'trim|required|min_length[6]');

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

    public function _check_old_password($str) {
        $UserID = $this->rest->UserID;
        $where = [
            "UserID" => $UserID,
            "Password" => md5($str),
            "SourceID" => "1",
        ];
        $rows = $this->app->get_rows('UserLogins', 'UserID', $where);
        if (count($rows) < 1) {
            $this->form_validation->set_message('_check_old_password', 'Old password does not match.');
            return FALSE;
        } else {
            $Password = safe_array_key($this->_data, "Password", NULL);
            if ($str == $Password) {
                $this->form_validation->set_message('_check_old_password', 'Old password & New password can not be same.');
                return FALSE;
            }
            return TRUE;
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

    public function _should_exist_email($str) {
        $where = [
            "Email" => strtolower($str),
            "UserTypeID" => 2,
        ];
        $Rows = $this->app->get_rows('Users', 'UserID', $where);
        if (count($Rows) < 1) {
            $this->form_validation->set_message('_should_exist_email', 'Email is not registered with us.');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function update_device_token_post() {

        $this->_response["ServiceName"] = "user/update_device_token";

        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|in_list[' . implode($this->app->DeviceTypes, ",") . ']');

        $DeviceType = safe_array_key($this->_data, "DeviceType", "");
        $DeviceTypeID = array_search($DeviceType, $this->app->DeviceTypes);

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
            $this->set_response($this->_response);
        } else {

            $DeviceToken = safe_array_key($this->_data, "DeviceToken", NULL);
            $UniqueDeviceToken = safe_array_key($this->_data, "UniqueDeviceToken", NULL);

            $LoginSessionKey = $this->rest->LoginSessionKey;
            $this->db->update('ActiveLogins', [
                "DeviceID" => $DeviceToken,
                "DeviceToken" => $DeviceToken,
                "UniqueDeviceToken" => $UniqueDeviceToken,
                    ], [
                "LoginSessionKey" => $LoginSessionKey,
            ]);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_post() {

        $this->_response["ServiceName"] = "user/update";

        $LoginSessionKey = $this->rest->LoginSessionKey;

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('UserType', 'User type', 'trim|required|in_list[' . implode($this->app->UserTypes, ",") . ']');

        $UserType = safe_array_key($this->_data, "UserType", "");
        $UserTypeID = array_search($UserType, $this->app->UserTypes);

        if ($UserTypeID == "2") {
            $this->form_validation->set_rules('FirstName', 'First name', 'trim|required');
            $this->form_validation->set_rules('LastName', 'Last name', 'trim|required');
            $this->form_validation->set_rules('PhoneNumber', 'Phone Number', 'trim|required');
            $this->form_validation->set_rules('DOB', 'DOB', 'trim|required');
            $this->form_validation->set_rules('Gender', 'Gender', 'trim|required');
            $this->form_validation->set_rules('PhinNumber', 'Phin Number', 'trim');
        } elseif ($UserTypeID == "3") {
            $this->form_validation->set_rules(time(), 'Special', 'trim|required');
        } elseif ($UserTypeID == "1") {
            $this->form_validation->set_rules(time(), 'Special', 'trim|required');
        }

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("user_model");

            if ($UserTypeID == "2") {
                $FirstName = $this->_data['FirstName'];
                $LastName = $this->_data['LastName'];
                $PhoneNumber = $this->_data['PhoneNumber'];
                $Gender = $this->_data['Gender'];
                $DOB = $this->_data['DOB'];
                $PhinNumber = safe_array_key($this->_data, "PhinNumber", NULL);
                $ProfilePicture = safe_array_key($this->_data, "ProfilePicture", NULL);
                $this->user_model->update_user($UserID, $FirstName, $LastName, $PhoneNumber, $Gender, $DOB, $PhinNumber, $ProfilePicture);
            } elseif ($UserTypeID == "3") {
                
            } elseif ($UserTypeID == "1") {
                
            }
            $this->_response["Message"] = "Profile has been updated successfully";
            $this->_response["Data"] = $this->app->user_data($LoginSessionKey);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    /**
     * {"ReferralCode":"promoformobj11","ProfileGUID":"5d3103f4-5c3a-2b7d-a6f9-c4b49f1305fa","ReferedFrom":"MY_PHARMACY"}
     */
    public function apply_referral_post() {

        $this->_response["ServiceName"] = "user/apply_referral";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('ReferedFrom', 'Refered From', 'trim|required|in_list[MY_DOCTOR,MY_PHARMACY,MY_FRIEND,OTHER]');
        $this->form_validation->set_rules('ReferralCode', 'Referral Code', 'trim|callback__valid_referral_code');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("promo_model");
            $this->load->model("wallet_model");
            $ReferedFrom = $this->_data['ReferedFrom'];
            $PromoCode = safe_array_key($this->_data, 'ReferralCode', NULL);
            $ApplyReferralSkip = "0";
            if (!empty($PromoCode)) {
                $Promo = $this->app->get_row('Promos', '*', [
                    "Code" => $PromoCode,
                ]);
                $PromoID = $this->promo_model->apply_promo($UserID, $PromoCode, $ReferedFrom, 'APPROVED');
                $this->wallet_model->add_funds($UserID, $Promo['Amount'], $PromoID);
                if ($Promo['AssignTo']) {
                    $AssignUser = $this->app->get_row('Users', 'UserTypeID', [
                        "UserID" => $Promo['AssignTo'],
                    ]);
                    $this->wallet_model->add_funds($Promo['AssignTo'], $Promo['AssignToAmount'], $PromoID);
                    if ($AssignUser['UserTypeID'] == 3) {
                        $this->db->update('Users', ["PharmacistID" => $Promo['AssignTo']], ["UserID" => $UserID]);
                        $ApplyReferralSkip = "1";
                    }
                }
                $this->_response["Message"] = "Refferral code has been applied. Wallet Balance has been updated successfully.";
            }
            $this->_response["Data"] = [
                "ApplyReferralSkip" => $ApplyReferralSkip,
            ];
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function various_count_post() {

        $this->_response["ServiceName"] = "user/various_count";

        $UserID = $this->rest->ActiveUserID;
        $UserID1 = $this->rest->UserID;

        //Wallet Code Start
        $this->load->model('wallet_model');
        $Wallet = $this->wallet_model->get_user_wallet($UserID1);
        if (is_null($Wallet)) {
            $this->wallet_model->create_wallet($UserID1);
            $WalletAmount = 0.0;
        } else {
            $WalletAmount = $Wallet['Amount'];
        }
        //Wallet Code End
        //Cart Code Start
        $this->load->model('medication_model');
        $OrderID = $this->medication_model->get_quote_order($UserID);
        $OrderItems = $this->app->get_rows('OrderMedications', 'OrderMedicationGUID', [
            'OrderID' => $OrderID,
        ]);
        //Cart Code End

        $this->load->model("chat_model");
        $this->load->model("promo_model");

        $this->_response["Data"] = [
            "WalletAmount" => $WalletAmount,
            "CartCount" => count($OrderItems),
            "UnReadChatCount" => $this->chat_model->unread_chat_count($UserID),
            "ReferralCode" => $this->promo_model->get_referral_code_by_user_id($UserID1),
            "NotificationUnreadCount" => $this->app->notifications($UserID, 'SEEN', 1),
            "NotificationUnseenCount" => $this->app->notifications($UserID, 'FRESH', 1),
        ];

        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function footer_post() {

        $this->_response["ServiceName"] = "user/footer";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');


        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("user_model");
            $Response[] = [
                "Title" => "Dashboard",
                "IsDefault" => FALSE,
            ];
            //Pharmacist Tab
            $ProfileGUID = $this->_data['ProfileGUID'];
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $PharmacistID = $Profile['PharmacistID'];
            $Pharmacist = "";
            if ($PharmacistID) {
                $Pharmacist = $this->user_model->get_pharmacist_info($PharmacistID);
            }
            $Response[] = [
                "Title" => "Pharmacist",
                "IsDefault" => TRUE,
            ];

            $Response[] = [
                "Title" => "Rx List",
                "IsDefault" => FALSE,
            ];

            if ($Pharmacist != "") {
                $Response[] = [
                    "Title" => "Orders",
                    "IsDefault" => FALSE,
                ];
            }
            $this->_response["Data"] = $Response;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function anonymous_footer_post() {

        $this->_response["ServiceName"] = "user/anonymous_footer";

        $Response[] = [
            "Title" => "Dashboard",
            "IsDefault" => FALSE,
        ];

        $Response[] = [
            "Title" => "Pharmacist",
            "IsDefault" => TRUE,
        ];

        $Response[] = [
            "Title" => "Rx List",
            "IsDefault" => FALSE,
        ];

        $Response[] = [
            "Title" => "Orders",
            "IsDefault" => FALSE,
        ];

        $this->_response["Data"] = $Response;
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function dashboard_post() {

        $this->_response["ServiceName"] = "user/dashboard";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("user_model");
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function pharmacist_post() {

        $this->_response["ServiceName"] = "user/pharmacist";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("user_model");
            $ProfileGUID = $this->_data['ProfileGUID'];
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $PharmacistID = $Profile['PharmacistID'];
            $Pharmacist = $this->user_model->get_pharmacist_info($PharmacistID);
            if (!empty($Pharmacist)) {
                $this->_response["Data"] = $Pharmacist;
            } else {
                $this->_response["Message"] = "No Pharmacist assign to you.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function orders_post() {

        $this->_response["ServiceName"] = "user/orders";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('Timezone', 'Timezone', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("user_model");
            $this->load->model("search_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $PharmacistID = $Profile['PharmacistID'];
            $UserID = $Profile['UserID'];

            $Timezone = $this->_data['Timezone'];

            $Pharmacist = $this->user_model->get_pharmacist_info($PharmacistID, TRUE);
            $PharmacyID = $Pharmacist['PharmacyID'];

            $Notifications = [];
            $Orders = $this->user_model->orders($UserID);
            $LiveStatus = $this->search_model->pharmacy_live_status($PharmacyID, $Timezone, NULL);

            $this->_response["Data"] = [
                'Notifications' => $Notifications,
                'Orders' => $Orders,
                'LiveStatus' => $LiveStatus,
            ];
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function pharmacist_connect_post() {

        $this->_response["ServiceName"] = "user/pharmacist_connect";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim|required');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("search_model");
            $this->load->model("user_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ProfileID = $Profile['UserID'];

            $Latitude = $this->_data['Latitude'];
            $Longitude = $this->_data['Longitude'];

            $Pharmacy = $this->search_model->find_pharmacy($Latitude, $Longitude);
            if ($Pharmacy) {
                $this->user_model->set_pharmacist($ProfileID, $Pharmacy['UserID']);
            } else {
                $this->_response["StatusCode"] = 404;
                $this->_response["Message"] = "Oops! We aren't in your area yet. We will notify you when open near you. Keep using the Rx List to manage your meds.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function pharmacist_connect_v2_post() {

        $this->_response["ServiceName"] = "user/pharmacist_connect_v2";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('PharmacyGUID', 'PharmacyGUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("user_model");
            $ProfileGUID = $this->_data['ProfileGUID'];
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ProfileID = $Profile['UserID'];
            $PharmacyGUID = $this->_data['PharmacyGUID'];
            $Pharmacy = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $PharmacyGUID
            ]);
            $PharmacyID = $Pharmacy['UserID'];
            $this->user_model->set_pharmacist($ProfileID, $PharmacyID);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function anonymous_pharmacist_connect_post() {

        $this->_response["ServiceName"] = "user/anonymous_pharmacist_connect";


        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim|required');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("search_model");
            $this->load->model("user_model");

            $Latitude = $this->_data['Latitude'];
            $Longitude = $this->_data['Longitude'];

            $Pharmacy = $this->search_model->find_pharmacy($Latitude, $Longitude);
            if ($Pharmacy) {
                $Pharmacist = $this->user_model->get_pharmacist_info($Pharmacy['UserID']);
                $this->_response["Data"] = $Pharmacist;
            } else {
                $this->_response["StatusCode"] = 404;
                $this->_response["Message"] = "Oops! We aren't in your area yet. We will notify you when open near you. Keep using the Rx List to manage your meds.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_notification_post() {
        $this->_response["ServiceName"] = "user/update_notification";

        $this->form_validation->set_rules('NotificationGUID', 'Notification GUID', 'trim|required');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|in_list[SEEN,READ,DELETE]');


        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {

            $NotificationGUID = $this->_data['NotificationGUID'];
            $Status = $this->_data['Status'];

            if ($Status == "DELETE") {
                $this->db->delete('Notifications', [
                    'NotificationGUID' => $NotificationGUID,
                ]);
            } else {
                $this->db->update('Notifications', [
                    'Status' => $Status,
                        ], [
                    'NotificationGUID' => $NotificationGUID,
                ]);
            }

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function pharmacy_schedule_post() {

        $this->_response["ServiceName"] = "user/pharmacy_schedule";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("user_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $PharmacistID = $Profile['PharmacistID'];

            $PharmacySchedule = $this->user_model->get_pharmacist_schedule($PharmacistID, TRUE);

            $this->_response["Data"] = $PharmacySchedule;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function search_services_to_connect_post() {

        $this->_response["ServiceName"] = "user/search_services_to_connect";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required|callback__search_required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("search_model");
            $Out = [];
            $UserID = $this->rest->ActiveUserID;
            $ReferralCode = safe_array_key($this->_data, 'ReferralCode', "");
            $ZipCode = safe_array_key($this->_data, 'ZipCode', "");
            $CurrentLocation = safe_array_key($this->_data, 'CurrentLocation', "");
            if (!empty($ReferralCode)) {
                $Row = $this->app->get_row('Promos', 'PromoID, AssignTo, PromoGUID, IsActive', [
                    "Code" => $ReferralCode,
                    "PromoType" => "REFERRAL",
                ]);
                $Temp = $this->app->get_row('Users', 'UserID, UserGUID, UserTypeID', [
                    "UserID" => $Row['AssignTo'],
                ]);
                $TempTypeID = safe_array_key($Temp, 'UserTypeID', NULL);
                $TempUserID = safe_array_key($Temp, 'UserID', NULL);
                if ($TempTypeID == 3) {
                    //pharmacy
                    $PreferedPharmacies = [];
                    $Pharmacists = $this->search_model->pharmacies_by_user_id($TempUserID);
                    $Out[] = [
                        "GroupTitle" => "Preferred Pharmacy",
                        "Items" => $PreferedPharmacies,
                    ];
                    $Out[] = [
                        "GroupTitle" => "List of Pharmacists",
                        "Items" => $Pharmacists,
                    ];
                } elseif ($TempTypeID == 4) {
                    //groups
                    $PreferedPharmacies = [];
                    $Pharmacists = $this->search_model->pharmacies_by_group_admin($TempUserID);
                    $Out[] = [
                        "GroupTitle" => "Preferred Pharmacy",
                        "Items" => $PreferedPharmacies,
                    ];
                    $Out[] = [
                        "GroupTitle" => "List of Pharmacists",
                        "Items" => $Pharmacists,
                    ];
                }
            } elseif (!empty($ZipCode)) {
                $url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($ZipCode) . "&sensor=false";
                $details = file_get_contents($url);
                $Result = json_decode($details, true);
                $Location = $Result['results'][0]['geometry']['location'];
                $Latitude = safe_array_key($Location, 'lat', '0.0');
                $Longitude = safe_array_key($Location, 'lng', '0.0');
                $PreferedPharmacies = [];
                $Pharmacists = $this->search_model->pharmacies_by_lat_lng($Latitude, $Longitude);
                $Out[] = [
                    "GroupTitle" => "Preferred Pharmacy",
                    "Items" => $PreferedPharmacies,
                ];
                $Out[] = [
                    "GroupTitle" => "List of Pharmacists",
                    "Items" => $Pharmacists,
                ];
            } elseif (!empty($CurrentLocation)) {
                list($Latitude, $Longitude) = explode(",", $CurrentLocation);
                $PreferedPharmacies = [];
                $Pharmacists = $this->search_model->pharmacies_by_lat_lng($Latitude, $Longitude);
                $Out[] = [
                    "GroupTitle" => "Preferred Pharmacy",
                    "Items" => $PreferedPharmacies,
                ];
                $Out[] = [
                    "GroupTitle" => "List of Pharmacists",
                    "Items" => $Pharmacists,
                ];
            }

            $this->_response["Data"] = $Out;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _search_required($Str) {
        $this->load->model("search_model");
        $UserID = $this->rest->ActiveUserID;
        $ReferralCode = safe_array_key($this->_data, 'ReferralCode', "");
        $ZipCode = safe_array_key($this->_data, 'ZipCode', "");
        $CurrentLocation = safe_array_key($this->_data, 'CurrentLocation', "");
        if (!empty($ReferralCode)) {
            $Row = $this->app->get_row('Promos', 'PromoID, AssignTo, PromoGUID, IsActive', [
                "Code" => $ReferralCode,
                "PromoType" => "REFERRAL",
            ]);
            if (!empty($Row)) {
                if ($UserID == $Row['AssignTo']) {
                    $this->form_validation->set_message('_search_required', 'You can not use your own referral code.');
                    return FALSE;
                } elseif ($Row['IsActive'] != 1) {
                    $this->form_validation->set_message('_search_required', 'Referral code you are using is desabled by Admin.');
                    return FALSE;
                }
                $Temp = $this->app->get_row('Users', 'UserID, UserGUID, UserTypeID', [
                    "UserID" => $Row['AssignTo'],
                ]);
                $TempTypeID = safe_array_key($Temp, 'UserTypeID', NULL);
                if (!in_array($TempTypeID, [3, 4])) {
                    $this->form_validation->set_message('_search_required', 'Referral code is not belongs to any pharmacy or group.');
                    return FALSE;
                }
            } else {
                $this->form_validation->set_message('_search_required', 'Referral code is expired or not valid.');
                return FALSE;
            }
        } elseif (!empty($ZipCode)) {
            $url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($ZipCode) . "&sensor=false";
            $details = file_get_contents($url);
            $Result = json_decode($details, true);
            if (isset($Result['results'][0]['geometry']['location'])) {
                $Location = $Result['results'][0]['geometry']['location'];
                $Latitude = safe_array_key($Location, 'lat', '0.0');
                $Longitude = safe_array_key($Location, 'lng', '0.0');
                $Pharmacy = $this->search_model->find_pharmacy($Latitude, $Longitude);
                if (empty($Pharmacy)) {
                    $this->form_validation->set_message('_search_required', "We currently don't work in your location.");
                    return FALSE;
                }
            } else {
                $this->form_validation->set_message('_search_required', "ZipCode not valid.");
                return FALSE;
            }
        } elseif (!empty($CurrentLocation)) {
            list($Latitude, $Longitude) = explode(",", $CurrentLocation);
            $Pharmacy = $this->search_model->find_pharmacy($Latitude, $Longitude);
            if (empty($Pharmacy)) {
                $this->form_validation->set_message('_search_required', "We currently don't work in your location.");
                return FALSE;
            }
        } else {
            $this->form_validation->set_message('_search_required', 'Please provide at least one of these three: ReferralCode,ZipCode,CurrentLocation');
            return FALSE;
        }
        return TRUE;
    }

}
