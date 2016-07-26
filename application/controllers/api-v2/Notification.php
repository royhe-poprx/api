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
class Notification extends REST_Controller {

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

        $this->_response["ServiceName"] = "notification/index";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('ListType', 'ListType', 'trim|required|in_list[ALL,FRESH,SEEN,READ]');
        $this->form_validation->set_rules('Limit', 'Limit', 'trim|required');
        $this->form_validation->set_rules('Offset', 'Offset', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {

            $ListType = safe_array_key($this->_data, "ListType", "ALL");
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);

            $this->_response["TotalRecords"] = $this->app->notifications($UserID, $ListType, 1);
            $Notifications = $this->app->notifications($UserID, $ListType, NULL, $Limit, $Offset);
            if (!empty($Notifications)) {
                $this->_response["Data"] = $Notifications;
            } else {
                $this->_response["Message"] = "No Notification(s) yet.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function seen_post() {
        $this->_response["ServiceName"] = "notification/seen";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('NotificationGUID', 'Notification GUID', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $NotificationGUID = safe_array_key($this->_data, 'NotificationGUID', "");
            if (!empty($NotificationGUID)) {
                $this->db->update('Notifications', [
                    'Status' => 'SEEN',
                        ], [
                    'NotificationGUID' => $NotificationGUID,
                    'Status' => 'FRESH',
                ]);
            } else {
                $this->db->update('Notifications', [
                    'Status' => 'SEEN',
                        ], [
                    'ToUserID' => $UserID,
                    'Status' => 'FRESH',
                ]);
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function read_post() {
        $this->_response["ServiceName"] = "notification/read";

        $this->form_validation->set_rules('NotificationGUID', 'Notification GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $NotificationGUID = safe_array_key($this->_data, 'NotificationGUID', NULL);
            $this->db->update('Notifications', [
                'Status' => 'READ',
                    ], [
                'NotificationGUID' => $NotificationGUID,
            ]);
            $this->_response["Message"] = $this->db->last_query();
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_post() {
        $this->_response["ServiceName"] = "notification/delete";

        $this->form_validation->set_rules('NotificationGUID', 'Notification GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $NotificationGUID = safe_array_key($this->_data, 'NotificationGUID', NULL);
            $this->db->delete('Notifications', [
                'NotificationGUID' => $NotificationGUID,
            ]);
            $this->_response["Message"] = $this->db->last_query();
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
