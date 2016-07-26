<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';


class Usertest extends REST_Controller{

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
   
    // Check signup email unique
    public function _unique_email($str) {
        
        $where = [
            "Email" => strtolower($str),
        ];

        $rows = $this->app->get_rows('users-roy', 'UserID', $where);
        if (count($rows) > 0) {
            $this->form_validation->set_message('_unique_email', '{field} already exists');
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    // Check signup email existence
    public function _exist_email($str) {
        
        $where = [
            "Email" => strtolower($str),
        ];

        $rows = $this->app->get_rows('users-roy', 'UserID', $where);
        if (count($rows) > 0) {
            return TRUE;
        } else {
            //function name & return message
            $this->form_validation->set_message('_exist_email', '{field} not found');
            return FALSE;
        }
    }
    
    // Check old password match and difference between new password
    public function _check_old_password($str,$Email) {
        
        $rows = $this->app->get_rows('users-roy','UserID',['Email'=>strtolower($Email)]);
        if($rows == NULL){
            $this->form_validation->set_message('_check_old_password', 'Email not found.');
            return FALSE;
        }
        $UserID = $rows[0]['UserID'];
        $where = [
            "UserID" => $UserID,
            "Password" => md5($str),
        ];
        $rows = $this->app->get_rows('userlogins-roy', 'UserID', $where);
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

    
    public function signup_post()
    {
        $this->_response["ServiceName"] = "usertest/signup";

        $this->form_validation->set_rules('UserType', 'User type', 'trim|required|in_list[' . implode($this->app->UserTypes, ",") . ']');
        $this->form_validation->set_rules('SourceType', 'Source type', 'trim|required|in_list[' . implode($this->app->SourceTypes, ",") . ']');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|in_list[' . implode($this->app->DeviceTypes, ",") . ']');
       
        //required fields
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__unique_email');
        $this->form_validation->set_rules('Password', 'Password', 'trim|required|min_length[6]');
        
        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            //insert your code..
            $this->load->model('userroy_model');
            $Email = safe_array_key($this->_data, "Email", NULL);
            $FirstName = safe_array_key($this->_data, "FirstName", NULL);
            $LastName = safe_array_key($this->_data, "LastName", NULL);
            $Password = safe_array_key($this->_data, "Password", NULL);

            $User = $this->userroy_model->create_user($FirstName,$LastName,$Email,$Password);
            
            $this->_response["Data"] = $User;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            //return the JSON
            $this->set_response($this->_response);
        }
    }
    
    
    public function signin_post()
    {
        $this->_response["ServiceName"] = "usertest/signin";
        $this->form_validation->set_rules('SourceType', 'Source type', 'trim|required|in_list[' . implode($this->app->SourceTypes, ",") . ']');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|in_list[' . implode($this->app->DeviceTypes, ",") . ']');
        
        //required fields
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__exist_email');
        $this->form_validation->set_rules('Password', 'Password', 'trim|required|min_length[6]');
        
        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            //insert your code..
            $this->load->model('userroy_model');
            $Email = safe_array_key($this->_data, "Email", NULL);
            $Password = safe_array_key($this->_data, "Password", NULL);
            
            $Result = $this->userroy_model->user_login($Email,$Password);   
            
            $this->_response["Data"] = $Result;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            //return the JSON
            $this->set_response($this->_response);
        }
    }
    
    
    public function update_user_post()
    {
        $this->_response["ServiceName"] = "usertest/update_user";

        //required fields
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__exist_email');
        
        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            //insert your code..
            $this->load->model('userroy_model');
            $Email = safe_array_key($this->_data, "Email", NULL);
            $FirstName = safe_array_key($this->_data, "FirstName", NULL);
            $LastName = safe_array_key($this->_data, "LastName", NULL);

            $Result = $this->userroy_model->update_user($FirstName,$LastName,$Email);               
            $this->_response["Data"] = $Result;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            //return the JSON
            $this->set_response($this->_response);
        }
    }
    
    
    public function change_password_post()
    {
        $this->_response["ServiceName"] = "usertest/change_password";
        
        /**
         * "UserID depends on the login-session-key"
         * "set up in the Rest_Controller"
         * $UserID = $this->rest->UserID;
         */
        $Email = $this->_data['Email'];
        
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__exist_email');
        $this->form_validation->set_rules('OldPassword', 'OldPassword', 'trim|required|callback__check_old_password['.$Email.']');
        $this->form_validation->set_rules('Password', 'Password', 'trim|required|min_length[6]');
     
        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            //insert your code..
            $this->load->model('userroy_model');
            $Email = safe_array_key($this->_data, "Email", NULL);            
            $Password = safe_array_key($this->_data, "Password", NULL);
            $OldPassword = safe_array_key($this->_data, "OldPassword", NULL);
            
            $Result = $this->userroy_model->change_passwords($Email,$OldPassword,$Password);
            
            $this->_response["Data"] = $Result;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            //return the JSON
            $this->set_response($this->_response);
        }
    }
    
    
    public function reset_password_post()
    {
        $this->_response["ServiceName"] = "usertest/reset_password";
        
        //required fields
        $this->form_validation->set_rules('RecoveryType', 'Recovery Type', 'trim|required|in_list[METHOD_1,METHOD_2]');
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__exist_email');
        $this->form_validation->set_rules('Password', 'Password', 'trim|required|min_length[6]');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            //insert code here..
            $this->load->model('userroy_model');
            $RecoveryType = safe_array_key($this->_data, "RecoveryType", "");
            $Email = safe_array_key($this->_data, "Email", NULL);
            $Password = safe_array_key($this->_data, "Password", NULL);
            
            //Depends on the RecoveryType
            if($RecoveryType == 'METHOD_1'){
                $Result = $this->userroy_model->reset_passwords_1($Email,$Password);
            }else if($RecoveryType == 'METHOD_2'){
                $Result = $this->userroy_model->reset_passwords_2($Email,$Password);                
            }
            
            $this->_response["Data"] = $Result;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            //return the JSON
            $this->set_response($this->_response);
        }
    }
    
    
    public function upload_pic_post()
    {
        
        $this->_response["ServiceName"] = "usertest/upload_pic";
        //required fields
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__exist_email');
        $this->form_validation->set_rules('ImageString', 'Image String', 'trim|required');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $Thumbs = array(
                "72X72",
                "250X250",
            );
            if (!is_dir(UPLOAD_TEMP)) {
                mkdir(UPLOAD_TEMP, 0777, TRUE);
            }
//            $ImageString = base64_encode_image('./uploads/temp/1.jpg', 'jpg');
            $ImageString = $this->_data['ImageString'];;
            $ImageName = guid() . ".jpg";
            $Attachment = base64_decode($ImageString);
            $TempPath = UPLOAD_TEMP . $ImageName;
            file_put_contents($TempPath, $Attachment);

            $Image["ImageType"] = "jpg";
            $Image["ImageData"] = $ImageString;
            $Errors = array();

            $this->load->library('image_lib');
            foreach ($Thumbs as $ThumbSize) {
                $Thumb = UPLOAD_TEMP . $ThumbSize . "_" . $ImageName;
                $ThumbFileString = "";
                list($w, $h) = explode("X", $ThumbSize);

                $config['image_library'] = 'gd2';
                $config['source_image'] = $TempPath;
                $config['create_thumb'] = FALSE;
                $config['maintain_ratio'] = TRUE;
                $config['width'] = $w;
                $config['height'] = $h;
                $config['new_image'] = $Thumb;
                $this->image_lib->initialize($config);
                if ($this->image_lib->resize()) {
                    $ThumbFileString = base64_encode_image($Thumb);
                    unlink($Thumb);
                } else {
                    $Errors[$ThumbSize] = $this->image_lib->display_errors('', '');
                }
                $Image["ImageData" . $ThumbSize] = $ThumbFileString;
            }
            unlink($TempPath);
            if (!empty($Errors)) {
                $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
                $this->_response["Message"] = current($Errors);
                $this->_response["Errors"] = $Errors;
                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                $this->set_response($this->_response);
            } else {
                //insert code here..
                $this->load->model('userroy_model');
                $Email = safe_array_key($this->_data, "Email", NULL);
                $ImageID = $this->userroy_model->upload_image($Image, $Email);
                
                $this->_response["Data"] = 'ImageID is '.$ImageID;
                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                //return the JSON
                $this->set_response($this->_response);
            }
        }
    }
}