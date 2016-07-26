<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';


class Wstest extends REST_Controller{

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



    public function index_post()
    {
        $this->_response["ServiceName"] = "wstest/index";

        //CI form_validation
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
            //insert your code..

            //return the JSON
            $this->set_response($this->_response);
        }
    }



    public function user_list_post()
    {

        $this->_response["ServiceName"] = "wstest/user_list";

        //Receive the JSON Post data
        //$data = $this->_data

        //login session key convert to user id
        $UserID = $this->rest->UserID;


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
            //helper function : safe_array_key
            //remove the unexpected key
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);

//            $Users = $this->app->get_rows('Users','*',array("UserTypeID"=>"2"));

            $this->db->select('*');
            $this->db->where('UserTypeID', '2');
            if (!empty($Limit) && (!empty($Offset) || $Offset == 0)) {
                $this->db->limit($Limit, $Offset);
            }
            $query = $this->db->get('Users');
            $Users = $query->result_array();


            if (!empty($Users)) {
                //return value
                $this->_response["Data"] = $Users;
            } else {
                $this->_response["Message"] = "No users found";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->_response["UserID"] = $UserID;

            //return the JSON result
            $this->set_response($this->_response);
        }
    }
}