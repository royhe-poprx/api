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
class Address extends REST_Controller {

    var $_data = array();
    protected $methods = array(
        'index_post' => array('level' => 10),
        'create_post' => array('level' => 10),
        'update_post' => array('level' => 10),
        'delete_post' => array('level' => 10),
    );

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

        $this->_response["ServiceName"] = "address/index";

//        login session key convert to user id
        $UserID = $this->rest->UserID;
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        if (!is_null($ProfileGUID)) {
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            if (!empty($Profile)) {
                $UserID = $Profile['UserID'];
            }
        }

        $this->load->model("address_model");
        $Addresses = $this->address_model->addresses($UserID);
        if (!empty($Addresses)) {
            $this->_response["Data"] = $Addresses;
        } else {
            $this->_response["Message"] = "No Address(es) added.";
        }
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function create_post() {

        $this->_response["ServiceName"] = "address/create";

        $UserID = $this->rest->UserID;
        
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        if (!is_null($ProfileGUID)) {
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            if (!empty($Profile)) {
                $UserID = $Profile['UserID'];
            }
        }

        $this->form_validation->set_rules('AddressType', 'AddressType', 'trim');
        $this->form_validation->set_rules('FormattedAddress', 'Formatted Address', 'trim|required');
        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim|required');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim|required');
        $this->form_validation->set_rules('StreetNumber', 'Street Number', 'trim|required');
        $this->form_validation->set_rules('Route', 'Route', 'trim');
        $this->form_validation->set_rules('City', 'City', 'trim|required');
        $this->form_validation->set_rules('State', 'State', 'trim|required');
        $this->form_validation->set_rules('Country', 'Country', 'trim|required');
        $this->form_validation->set_rules('PostalCode', 'PostalCode', 'trim|required');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {

            $AddressType = safe_array_key($this->_data, "AddressType", NULL);
            $FormattedAddress = safe_array_key($this->_data, "FormattedAddress", NULL);
            $Latitude = safe_array_key($this->_data, "Latitude", NULL);
            $Longitude = safe_array_key($this->_data, "Longitude", NULL);
            $StreetNumber = safe_array_key($this->_data, "StreetNumber", NULL); //street 1
            $Route = safe_array_key($this->_data, "Route", NULL);
            $City = safe_array_key($this->_data, "City", NULL);
            $State = safe_array_key($this->_data, "State", NULL);
            $Country = safe_array_key($this->_data, "Country", NULL);
            $PostalCode = safe_array_key($this->_data, "PostalCode", NULL);

            $this->load->model("address_model");
            $AddressID = $this->address_model->create_address($UserID, $AddressType, $FormattedAddress, $Latitude, $Longitude, $StreetNumber, $Route, $City, $State, $Country, $PostalCode);
            $this->_response["Message"] = "New address has been added successfully";
            $this->_response["Data"] = $this->address_model->get_address_by_id($AddressID);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_post() {

        $this->_response["ServiceName"] = "address/update";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('UserAddressGUID', 'User Address GUID', 'trim|required');
        $this->form_validation->set_rules('AddressType', 'AddressType', 'trim');
        $this->form_validation->set_rules('FormattedAddress', 'Formatted Address', 'trim|required');
        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim|required');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim|required');
        $this->form_validation->set_rules('StreetNumber', 'Street Number', 'trim|required');
        $this->form_validation->set_rules('Route', 'Route', 'trim');
        $this->form_validation->set_rules('City', 'City', 'trim|required');
        $this->form_validation->set_rules('State', 'State', 'trim|required');
        $this->form_validation->set_rules('Country', 'Country', 'trim|required');
        $this->form_validation->set_rules('PostalCode', 'PostalCode', 'trim|required');


        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {

            $UserAddressGUID = safe_array_key($this->_data, "UserAddressGUID", NULL);
            $AddressType = safe_array_key($this->_data, "AddressType", NULL);
            $FormattedAddress = safe_array_key($this->_data, "FormattedAddress", NULL);
            $Latitude = safe_array_key($this->_data, "Latitude", NULL);
            $Longitude = safe_array_key($this->_data, "Longitude", NULL);
            $StreetNumber = safe_array_key($this->_data, "StreetNumber", NULL); //street 1
            $Route = safe_array_key($this->_data, "Route", NULL);
            $City = safe_array_key($this->_data, "City", NULL);
            $State = safe_array_key($this->_data, "State", NULL);
            $Country = safe_array_key($this->_data, "Country", NULL);
            $PostalCode = safe_array_key($this->_data, "PostalCode", NULL);

            $this->load->model("address_model");
            $this->address_model->update_address($UserAddressGUID, $AddressType, $FormattedAddress, $Latitude, $Longitude, $StreetNumber, $Route, $City, $State, $Country, $PostalCode);
            $this->_response["Message"] = "Address has been update successfully";

            $this->_response["Data"] = $this->address_model->get_address_by_guid($UserAddressGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_post() {

        $this->_response["ServiceName"] = "address/delete";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('UserAddressGUID', 'User Address GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {

            $UserAddressGUID = safe_array_key($this->_data, "UserAddressGUID", NULL);

            $this->load->model("address_model");
            $this->address_model->delete_address($UserAddressGUID);
            $this->_response["Message"] = "Address has been delete successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
