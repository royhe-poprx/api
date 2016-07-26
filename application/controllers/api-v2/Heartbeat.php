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
class Heartbeat extends REST_Controller {
    var $_data = array();
    function __construct()
    {
        // Construct the parent class
        parent::__construct(); 
        $this->benchmark->mark('code_start');
        $this->_data = $this->post();
        $this->_data['Key']="value";
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
     * Token generate
     * perms unique_device_token string (40) unique per device
     * 
     */
    public function index_post(){
        
        $this->_response["ServiceName"] = "heartbeat/index";
        
        $this->form_validation->set_rules('DeviceType', 'Device type', 
                'trim|required|in_list['.implode($this->app->DeviceTypes,",").']'); 
            
        $DeviceType = safe_array_key($this->_data, "DeviceType", "");
        $DeviceTypeID = array_search($DeviceType, $this->app->DeviceTypes);
        
        if(in_array($DeviceTypeID, array("3", "5", "6"))){
            $this->form_validation->set_rules('DeviceToken', 'Device token', 'trim|required');
            $this->form_validation->set_rules('UniqueDeviceToken', 'Unique device token', 'trim|required');
        }elseif(in_array($DeviceTypeID, array("2", "4"))){
            $this->form_validation->set_rules('DeviceToken', 'Device token', 'trim|required');            
        }
        
        if ($this->form_validation->run() == FALSE){
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"]=$this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response, self::HTTP_FORBIDDEN); 
        }else{
            $this->load->model("heartbeat_model");
            $DeviceToken = safe_array_key($this->_data, "DeviceToken", NULL);
            $UniqueDeviceToken = safe_array_key($this->_data, "UniqueDeviceToken", NULL);
            $Token = $this->heartbeat_model->create($DeviceTypeID, $DeviceToken, $UniqueDeviceToken); 
            $this->_response["Data"] = $Token;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"]=$this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response); 
        }
    }
    
    
    /**
     * Token generate
     * perms unique_device_token string (40) unique per device
     * 
     */
    public function update_temp_data_post(){
        
        $this->_response["ServiceName"] = "heartbeat/update_temp_data";
        
        $this->form_validation->set_rules('TokenGUID', 'Token GUID', 'trim|required'); 
        $this->form_validation->set_rules('TempJsonData', 'Temp Json Data', 'trim'); 
        
        if ($this->form_validation->run() == FALSE){
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"]=$this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response, self::HTTP_FORBIDDEN); 
        }else{
            $this->load->model("heartbeat_model");
            $TokenGUID = safe_array_key($this->_data, "TokenGUID", NULL);
            $TempJsonData = safe_array_key($this->_data, "TempJsonData", NULL);
            $this->heartbeat_model->update_token($TokenGUID, $TempJsonData); 
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"]=$this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response); 
        }
    }
    
    /**
     * Token generate
     * perms unique_device_token string (40) unique per device
     * 
     */
    public function get_temp_data_post(){
        
        $this->_response["ServiceName"] = "heartbeat/get_temp_data";
        
        $this->form_validation->set_rules('TokenGUID', 'Token GUID', 'trim|required');          
        
        if ($this->form_validation->run() == FALSE){
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"]=$this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response, self::HTTP_FORBIDDEN); 
        }else{
            $this->load->model("heartbeat_model");
            $TokenGUID = safe_array_key($this->_data, "TokenGUID", NULL);
            $Token = $this->heartbeat_model->get_token($TokenGUID); 
            $this->_response["Data"] = $Token;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"]=$this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response); 
        }
    }
}
