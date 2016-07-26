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
class Search extends REST_Controller {

    var $_data = array();
    protected $methods = array(
        'pharmacy_by_lat_lng_post' => array('level' => 10),
        'pharmacy_by_zipcode_or_name_post' => array('level' => 10),
        'drug_post' => array('level' => 10),
        'maple_post' => array('level' => 1),
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

        $this->method['maple']['level'] = 10;
    }

    public function pharmacy_by_lat_lng_post() {

        $this->_response["ServiceName"] = "search/pharmacy_by_lat_lng";

        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|in_list[' . implode($this->app->DeviceTypes, ",") . ']');

        $DeviceType = safe_array_key($this->_data, "DeviceType", "");
        $DeviceTypeID = array_search($DeviceType, $this->app->DeviceTypes);

        //other conditional required things
        $this->form_validation->set_rules('PharmacyID', 'Pharmacy ID', 'trim');
        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim|required');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim|required');
        $this->form_validation->set_rules('Timezone', 'Timezone', 'trim|required');

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
            $this->load->model("search_model");
            $Response['FooterActions'] = array();
            $Response['Pharmacy'] = (object) [];

            $Latitude = $this->_data['Latitude'];
            $Longitude = $this->_data['Longitude'];
            $Timezone = safe_array_key($this->_data, 'Timezone', 'Asia/Calcutta');
            $PharmacyID = safe_array_key($this->_data, 'PharmacyID', NULL);

            if (!empty($PharmacyID)) {
                $Pharmacy = $this->search_model->get_pharmacy_by_id($PharmacyID);
                $Pharmacy['AreaType'] = "PRIMARY";
            } else {
                $Pharmacy = $this->search_model->find_pharmacy_by_lat_lng($Latitude, $Longitude);
                $Pharmacy['AreaType'] = "PRIMARY";
                //if no primary than found secondary
                if (empty($Pharmacy)) {
                    $Pharmacy = $this->search_model->find_secondary_pharmacy_by_lat_lng($Latitude, $Longitude);
                    $Pharmacy['AreaType'] = "SECONDRAY";
                }
            }
            if (!empty($Pharmacy)) {
                $PharmacyID = $Pharmacy['PharmacyID'];
                $Pharmacy['LiveStatus'] = $this->search_model->pharmacy_live_status($PharmacyID, $Timezone, NULL);

                unset($Pharmacy['LocationID']);
                unset($Pharmacy['UserID']);
                $Response['Pharmacy'] = $Pharmacy;
                $Response['FooterActions'][] = [
                    "Title" => "Order"
                ];
            }


            $Response['FooterActions'][] = [
                "Title" => "Manage"
            ];
            $Response['FooterActions'][] = [
                "Title" => "Services"
            ];
            $this->_response["Data"] = $Response;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function pharmacy_by_zipcode_or_name_post() {

        $this->_response["ServiceName"] = "search/pharmacy_by_zipcode_or_name";

        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|in_list[' . implode($this->app->DeviceTypes, ",") . ']');

        $DeviceType = safe_array_key($this->_data, "DeviceType", "");
        $DeviceTypeID = array_search($DeviceType, $this->app->DeviceTypes);

        //other conditional required things
        $this->form_validation->set_rules('Keyword', 'Keyword', 'trim|required');


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
            $this->load->model("search_model");
            $Keyword = $this->_data['Keyword'];
            $Pharmacies = $this->search_model->find_pharmacy_by_zipcode_or_name($Keyword);
            if (!empty($Pharmacies)) {
                $this->_response["Data"] = $Pharmacies;
            } else {
                $this->_response["Message"] = "No Pharmacy found.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function drug_post() {

        $this->_response["ServiceName"] = "search/drug";

        $this->form_validation->set_rules('Keyword', 'Keyword', 'trim|required');

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
            $Keyword = $this->_data['Keyword'];

            $this->db->select('DrugName AS MedicationName, DinNumber, DrugType');
            $this->db->like('DrugName', $Keyword, 'after');
            $this->db->or_like('DinNumber', $Keyword, 'after');
            $this->db->limit(10);
            $this->db->order_by('DrugName');
            $Drugs = $this->db->get('CanMedDB')->result_array();
            if (!empty($Drugs)) {
                $this->_response["Data"] = $Drugs;
            } else {
                $this->_response["Message"] = "No Drug(s) found.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function maple_post() {

        $this->_response["ServiceName"] = "search/maple";

        $this->form_validation->set_rules('ZipCode', 'ZipCode', 'trim|required');
        $this->form_validation->set_rules('Timezone', 'Timezone', 'trim|required');
        $this->form_validation->set_rules('Time', 'Timezone', 'trim');

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
            $ZipCode = $this->_data['ZipCode'];
            $Timezone = safe_array_key($this->_data, 'Timezone', 'Asia/Calcutta');
            $Time = safe_array_key($this->_data, 'Time', NULL);
            $Location = [];
            $url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($ZipCode) . "&sensor=false";
            $details = file_get_contents($url);
            $Result = json_decode($details, true);
            if (!empty($Result) && count($Result) > 0) {
                if(isset($Result['results'][0]['geometry']['location'])){
                    $Location = $Result['results'][0]['geometry']['location'];
                }                
            }
            if (!empty($Location)) {
                $Latitude = safe_array_key($Location, 'lat', '0.0');
                $Longitude = safe_array_key($Location, 'lng', '0.0');
                $Pharmacy = $this->search_model->find_pharmacy($Latitude, $Longitude);
                if ($Pharmacy) {
                    $PhamacyInfo = $this->user_model->get_pharmacist_info($Pharmacy['UserID']);
                    $Out['PhamacyInfo'] = [
                        "PharmacyName" => $PhamacyInfo['PharmacyName'],
                        "AddressLine1" => $PhamacyInfo['AddressLine1'],
                        "AddressLine2" => $PhamacyInfo['AddressLine2'],
                        "City" => $PhamacyInfo['City'],
                        "State" => $PhamacyInfo['State'],
                        "Country" => $PhamacyInfo['Country'],
                        "PostalCode" => $PhamacyInfo['PostalCode'],
                    ];
                    $LiveStatus = $this->search_model->pharmacy_live_status($Pharmacy['PharmacyID'], $Timezone, $Time);
                    $Out['LiveStatus'] = [
                        "LiveStatus" => $LiveStatus['LiveStatus'],
                        "Title" => $LiveStatus['Title'],
                        "Text" => $LiveStatus['T'],
                        "CutOffTime" => $LiveStatus['CutOffTime'],
                        "SlotStart" => $LiveStatus['SlotStart'],
                        "SlotEnd" => $LiveStatus['SlotEnd'],
                    ];
                    $this->_response["Data"] = $Out;
                } else {
                    $this->_response["StatusCode"] = 404;
                    $this->_response["Message"] = "We currently don't work in your location";
                }
            } else {
                $this->_response["StatusCode"] = 404;
                $this->_response["Message"] = "We currently don't work in your location";
            }
            $InputData = $this->_data;
            unset($InputData['Key']);
            $this->_response["InputData"] = $InputData;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
