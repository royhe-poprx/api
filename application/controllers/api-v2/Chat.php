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
class Chat extends REST_Controller {

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

        $this->_response["ServiceName"] = "chat/index";

        $UserID = $this->rest->ActiveUserID;

        $this->form_validation->set_rules('PatientUserGUID', 'Patient User GUID', 'trim|required');
        $this->form_validation->set_rules('PharmacyUserGUID', 'Pharmacy User GUID', 'trim|required');


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
            $PatientUserGUID = safe_array_key($this->_data, "PatientUserGUID", NULL);
            $PharmacyUserGUID = safe_array_key($this->_data, "PharmacyUserGUID", NULL);
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);

            $PatientUser = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $PatientUserGUID
            ]);
            $PatientUserID = $PatientUser['UserID'];
            $PharmacyUser = $this->app->get_row('Users', 'UserID, FirstName, LastName, ChatMessage', [
                "UserGUID" => $PharmacyUserGUID
            ]);
            $PharmacyUserID = $PharmacyUser['UserID'];
            $this->_response["TotalRecords"] = $this->chat_model->chats($UserID, $PatientUserID, $PharmacyUserID, 1);
            $Chats = $this->chat_model->chats($UserID, $PatientUserID, $PharmacyUserID, NULL, $Limit, $Offset);
            if (!empty($Chats)) {
                $this->_response["Data"] = $Chats;
            } else {
                if ($UserID != $PharmacyUserID) {
                    if (!empty($PharmacyUser['ChatMessage'])) {
                        $CustomChat[] = $PharmacyUser['ChatMessage'];
                    } else {
                        $CustomChat[] = "Hey i am ".$PharmacyUser['FirstName']." How can I help you?";
                    }
                    $this->_response["Data"] = $CustomChat;
                }
                $this->_response["Message"] = "No chat(s).";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function send_post() {

        $this->_response["ServiceName"] = "chat/send";

        $this->form_validation->set_rules('SenderGUID', 'Sender GUID', 'trim|required');
        $this->form_validation->set_rules('ReceiverGUID', 'Receiver GUID', 'trim|required');
        $this->form_validation->set_rules('ChatText', 'Chat Text', 'trim|required');


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

            $SenderGUID = safe_array_key($this->_data, "SenderGUID", NULL);
            $ReceiverGUID = safe_array_key($this->_data, "ReceiverGUID", NULL);
            $ChatText = safe_array_key($this->_data, "ChatText", NULL);

            $Sender = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $SenderGUID
            ]);
            $SenderID = $Sender['UserID'];
            $Receiver = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ReceiverGUID
            ]);
            $ReceiverID = $Receiver['UserID'];
            $ChatID = $this->chat_model->create_chat($SenderID, $ReceiverID, $ChatText, 'MANUAL');
            $Chat = $this->chat_model->get_chat_by_id($ChatID);

            $this->_response["Data"] = $Chat;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function view_post() {

        $this->_response["ServiceName"] = "chat/view";

        $this->form_validation->set_rules('ChatGUID', 'Chat GUID', 'trim|required');

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
            $ChatGUID = safe_array_key($this->_data, "ChatGUID", NULL);
            $Chat = $this->chat_model->get_chat_by_guid($ChatGUID);
            $this->_response["Data"] = $Chat;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_post() {

        $this->_response["ServiceName"] = "chat/delete";

        $this->form_validation->set_rules('ChatGUID', 'Chat GUID', 'trim|required');

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
            $ChatGUID = safe_array_key($this->_data, "ChatGUID", NULL);
            $this->chat_model->delete_chat_by_guid($ChatGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function mark_seen_post() {

        $this->_response["ServiceName"] = "chat/mark_seen";

        $this->form_validation->set_rules('ChatGUID', 'Chat GUID', 'trim|required');

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
            $ChatGUID = safe_array_key($this->_data, "ChatGUID", NULL);
            $this->chat_model->chat_mark_seen_by_guid($ChatGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function mark_read_post() {

        $this->_response["ServiceName"] = "chat/mark_read";

        $this->form_validation->set_rules('ChatGUID', 'Chat GUID', 'trim|required');

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
            $ChatGUID = safe_array_key($this->_data, "ChatGUID", NULL);
            $this->chat_model->chat_mark_read_by_guid($ChatGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function bulk_mark_seen_post() {

        $this->_response["ServiceName"] = "chat/bulk_mark_seen";

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
            $ChatGUID = safe_array_key($this->_data, "ChatGUID", NULL);
            $this->chat_model->chat_bulk_mark_seen_by_guid($ChatGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function bulk_mark_read_post() {

        $this->_response["ServiceName"] = "chat/bulk_mark_read";

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
            $ChatGUID = safe_array_key($this->_data, "ChatGUID", NULL);
            $this->chat_model->chat_bulk_mark_read_by_guid($ChatGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
