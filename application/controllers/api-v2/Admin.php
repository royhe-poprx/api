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
class Admin extends REST_Controller {

    var $_data = array();
    protected $methods = array(
        'signin_post' => array('level' => 10),
        'signout_post' => array('level' => 10),
        'patients_post' => array('level' => 10),
        'create_patient_post' => array('level' => 10),
        'view_patient_post' => array('level' => 10),
        'delete_patient_post' => array('level' => 10),
        'pharmacies_post' => array('level' => 10),
        'create_pharmacy_post' => array('level' => 10),
        'update_pharmacy_post' => array('level' => 10),
        'view_pharmacy_post' => array('level' => 10),
        'delete_pharmacy_post' => array('level' => 10),
        'pharmacy_list_post' => array('level' => 10),
        'pharmacy_work_area_list_post' => array('level' => 10),
        'assign_pharmacy_work_area_post' => array('level' => 10),
        'block_pharmacy_post' => array('level' => 10),
        'unblock_pharmacy_post' => array('level' => 10),
        'users_post' => array('level' => 10),
        'update_user_wallet_post' => array('level' => 10),
        'order_live_feed_post' => array('level' => 10),
        'update_order_pharmacy_post' => array('level' => 10),
        'promos_post' => array('level' => 10),
        'create_promo_post' => array('level' => 10),
        'update_promo_post' => array('level' => 10),
        'delete_promo_post' => array('level' => 10),
        'view_promo_post' => array('level' => 10),
        'history_promo_post' => array('level' => 10),
        'update_user_promo_status_post' => array('level' => 10),
        'toggle_promo_status_post' => array('level' => 10),
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

    public function pharmacies_post() {

        $this->_response["ServiceName"] = "admin/pharmacies";
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
            $this->load->model("admin_model");
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $Extra = safe_array_key($this->_data, "Extra", NULL);
            $this->_response["TotalRecords"] = $this->admin_model->pharmacies($UserID, 1, NULL, NULL, $Extra);
            $Pharmacies = $this->admin_model->pharmacies($UserID, NULL, $Limit, $Offset, $Extra);
            if (!empty($Pharmacies)) {
                $this->_response["Data"] = $Pharmacies;
            } else {
                $this->_response["Message"] = "No Patients(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function create_pharmacy_post() {

        $this->_response["ServiceName"] = "admin/create_pharmacy";

        $UserID = $this->rest->UserID;


        $this->form_validation->set_rules('GeoSettingType', 'GeoSettingType', 'trim|required|in_list[GEO_REFERRAL,REFERRAL]');
        $this->form_validation->set_rules('PharmacyAdminGUID', 'Pharmacy Admin GUID', 'trim');
        $this->form_validation->set_rules('FirstName', 'First Name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last Name', 'trim|required');
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__unique_email');

        $this->form_validation->set_rules('CompanyName', 'Company Name', 'trim|required');
        $this->form_validation->set_rules('PharmacyName', 'Pharmacy Name', 'trim|required');
        $this->form_validation->set_rules('Phone', 'Phone', 'trim|required');
        $this->form_validation->set_rules('Fax', 'Fax', 'trim|required');
        $this->form_validation->set_rules('Website', 'Website', 'trim|required');
        $this->form_validation->set_rules('PharmacyLicenceNumber', 'Pharmacy Licence Number', 'trim|required');
        $this->form_validation->set_rules('PharmacyExp', 'Pharmacy Exp.', 'trim|required');

        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim|required');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim|required');
        $this->form_validation->set_rules('AddressLine1', 'Address Line 1', 'trim|required');
        $this->form_validation->set_rules('AddressLine2', 'Address Line 2', 'trim');
        $this->form_validation->set_rules('City', 'City', 'trim|required');
        $this->form_validation->set_rules('State', 'State', 'trim|required');
        $this->form_validation->set_rules('Country', 'Country', 'trim|required');
        $this->form_validation->set_rules('PostalCode', 'Postal Code', 'trim|required');


        $this->form_validation->set_rules('ShowInsuranceCard', 'Show Insurance Card', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('ShowSTI', 'Show STI', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('ShowAllergy', 'Show Allergy', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('ShowMedReview', 'Show Med Review', 'trim|required|in_list[0,1]');


        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("admin_model");

            $GeoSettingType = $this->_data['GeoSettingType'];
            $PharmacyAdminGUID = safe_array_key($this->_data, "PharmacyAdminGUID", "");
            $PharmacyAdminID = NULL;
            if (!empty($PharmacyAdminGUID)) {
                $PharmacyAdmin = $this->app->get_row('Users', 'UserID', ['UserGUID' => $PharmacyAdminGUID]);
                $PharmacyAdminID = safe_array_key($PharmacyAdmin, "UserID", NULL);
            }

            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];
            $Email = $this->_data['Email'];

            $CompanyName = $this->_data['CompanyName'];
            $PharmacyName = $this->_data['PharmacyName'];
            $PhoneNumber = $this->_data['Phone'];
            $FaxNumber = $this->_data['Fax'];
            $Website = $this->_data['Website'];
            $PharmacyLicenceNumber = $this->_data['PharmacyLicenceNumber'];
            $PharmacyExp = $this->_data['PharmacyExp'];

            $Latitude = $this->_data['Latitude'];
            $Longitude = $this->_data['Longitude'];
            $AddressLine1 = $this->_data['AddressLine1'];
            $AddressLine2 = $this->_data['AddressLine2'];
            $City = $this->_data['City'];
            $State = $this->_data['State'];
            $Country = $this->_data['Country'];
            $PostalCode = $this->_data['PostalCode'];

            $ShowInsuranceCard = $this->_data['ShowInsuranceCard'];
            $ShowSTI = $this->_data['ShowSTI'];
            $ShowAllergy = $this->_data['ShowAllergy'];
            $ShowMedReview = $this->_data['ShowMedReview'];


            $ProfileID = $this->admin_model->create_pharmacy($UserID, $GeoSettingType, $PharmacyAdminID, $FirstName, $LastName, $Email, $CompanyName, $PharmacyName, $PhoneNumber, $FaxNumber, $Website, $PharmacyLicenceNumber, $PharmacyExp, $Latitude, $Longitude, $AddressLine1, $AddressLine2, $City, $State, $Country, $PostalCode, $ShowInsuranceCard, $ShowSTI, $ShowAllergy, $ShowMedReview);
            $Pharmacy = $this->app->get_pharmacy_by_user_id($ProfileID);
            $this->_response["Data"] = $Pharmacy;
            $this->_response["Message"] = "New Pharmacy has been added successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_pharmacy_post() {

        $this->_response["ServiceName"] = "admin/update_pharmacy";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('PharmacyGUID', 'Pharmacy GUID', 'trim|required');

        $this->form_validation->set_rules('GeoSettingType', 'GeoSettingType', 'trim|required|in_list[GEO_REFERRAL,REFERRAL]');
        $this->form_validation->set_rules('PharmacyAdminGUID', 'Pharmacy Admin GUID', 'trim');

        $this->form_validation->set_rules('FirstName', 'First Name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last Name', 'trim|required');
        //$this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__unique_email');

        $this->form_validation->set_rules('CompanyName', 'Company Name', 'trim|required');
        $this->form_validation->set_rules('PharmacyName', 'Pharmacy Name', 'trim|required');
        $this->form_validation->set_rules('Phone', 'Phone', 'trim|required');
        $this->form_validation->set_rules('Fax', 'Fax', 'trim|required');
        $this->form_validation->set_rules('Website', 'Website', 'trim|required');
        $this->form_validation->set_rules('PharmacyLicenceNumber', 'Pharmacy Licence Number', 'trim|required');
        $this->form_validation->set_rules('PharmacyExp', 'Pharmacy Exp.', 'trim|required');

        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim|required');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim|required');
        $this->form_validation->set_rules('AddressLine1', 'Address Line 1', 'trim|required');
        $this->form_validation->set_rules('AddressLine2', 'Address Line 2', 'trim');
        $this->form_validation->set_rules('City', 'City', 'trim|required');
        $this->form_validation->set_rules('State', 'State', 'trim|required');
        $this->form_validation->set_rules('Country', 'Country', 'trim|required');
        $this->form_validation->set_rules('PostalCode', 'Postal Code', 'trim|required');

        $this->form_validation->set_rules('ShowInsuranceCard', 'Show Insurance Card', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('ShowSTI', 'Show STI', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('ShowAllergy', 'Show Allergy', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('ShowMedReview', 'Show Med Review', 'trim|required|in_list[0,1]');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("admin_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $PharmacyGUID = $this->_data['PharmacyGUID'];

            $GeoSettingType = $this->_data['GeoSettingType'];
            $PharmacyAdminGUID = safe_array_key($this->_data, "PharmacyAdminGUID", "");
            $PharmacyAdminID = NULL;
            if (!empty($PharmacyAdminGUID)) {
                $PharmacyAdmin = $this->app->get_row('Users', 'UserID', ['UserGUID' => $PharmacyAdminGUID]);
                $PharmacyAdminID = safe_array_key($PharmacyAdmin, "UserID", NULL);
            }

            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];
            $Email = ""; //$this->_data['Email'];

            $CompanyName = $this->_data['CompanyName'];
            $PharmacyName = $this->_data['PharmacyName'];
            $PhoneNumber = $this->_data['Phone'];
            $FaxNumber = $this->_data['Fax'];
            $Website = $this->_data['Website'];
            $PharmacyLicenceNumber = $this->_data['PharmacyLicenceNumber'];
            $PharmacyExp = $this->_data['PharmacyExp'];

            $Latitude = $this->_data['Latitude'];
            $Longitude = $this->_data['Longitude'];
            $AddressLine1 = $this->_data['AddressLine1'];
            $AddressLine2 = $this->_data['AddressLine2'];
            $City = $this->_data['City'];
            $State = $this->_data['State'];
            $Country = $this->_data['Country'];
            $PostalCode = $this->_data['PostalCode'];

            $ShowInsuranceCard = $this->_data['ShowInsuranceCard'];
            $ShowSTI = $this->_data['ShowSTI'];
            $ShowAllergy = $this->_data['ShowAllergy'];
            $ShowMedReview = $this->_data['ShowMedReview'];

            $this->admin_model->update_pharmacy($ProfileGUID, $PharmacyGUID, $GeoSettingType, $PharmacyAdminID, $FirstName, $LastName, $Email, $CompanyName, $PharmacyName, $PhoneNumber, $FaxNumber, $Website, $PharmacyLicenceNumber, $PharmacyExp, $Latitude, $Longitude, $AddressLine1, $AddressLine2, $City, $State, $Country, $PostalCode, $ShowInsuranceCard, $ShowSTI, $ShowAllergy, $ShowMedReview);

            $this->_response["Message"] = "Pharmacy has been updated successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function view_pharmacy_post() {

        $this->_response["ServiceName"] = "admin/view_pharmacy";

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
            $ProfileGUID = $this->_data['ProfileGUID'];
            $User = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID,
            ]);
            $Pharmacy = $this->app->get_pharmacy_by_user_id($User['UserID']);
            $this->_response["Data"] = $Pharmacy;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_pharmacy_post() {

        $this->_response["ServiceName"] = "admin/delete_pharmacy";

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
            $this->load->model("admin_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $this->admin_model->delete_pharmacy($ProfileGUID);

            $this->_response["Message"] = "Patient has been deleted successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function change_pharmacy_password_post() {

        $this->_response["ServiceName"] = "admin/change_pharmacy_password";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('Password', 'Password', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Password = safe_array_key($this->_data, "Password", NULL);

            $PharmacyUser = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID,
            ]);
            $PharmacyUserID = safe_array_key($PharmacyUser, 'UserID', NULL);

            $this->db->update('UserLogins', [
                'Password' => md5($Password),
                'ModifiedDate' => DATETIME,
                    ], [
                'UserID' => $PharmacyUserID,
                'SourceID' => '1',
            ]);
            $this->_response["Message"] = "Password has been changed successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function pharmacy_list_post() {
        $this->_response["ServiceName"] = "admin/pharmacy_list";
        $this->load->model("admin_model");
        $Pharmacies = $this->admin_model->pharmacy_list();
        array_unshift($Pharmacies, [
            "PharmacyGUID" => "",
            "CompanyName" => "All Pharmacy",
            "PharmacyName" => "All Pharmacy",
        ]);
        $this->_response["Data"] = $Pharmacies;
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function pharmacy_work_area_list_post() {
        $this->_response["ServiceName"] = "admin/pharmacy_work_area_list";
        $this->load->model("admin_model");
        $WorkAreaList = $this->admin_model->pharmacy_work_area_list();
        if (!empty($WorkAreaList)) {
            $this->_response["Data"] = $WorkAreaList;
        } else {
            $this->_response["Message"] = "No work area(s) added.";
        }
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function assign_pharmacy_work_area_post() {
        $this->_response["ServiceName"] = "admin/assign_pharmacy_work_area";

        $this->form_validation->set_rules('PharmacyGUID', 'Pharmacy GUID', 'trim|required');
        $this->form_validation->set_rules('AreaPoints', 'AreaPoints', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("admin_model");

            $PharmacyGUID = safe_array_key($this->_data, "PharmacyGUID", NULL);
            $AreaPoints = safe_array_key($this->_data, "AreaPoints", NULL);
            $this->admin_model->assign_pharmacy_work_area($PharmacyGUID, $AreaPoints);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function block_pharmacy_post() {

        $this->_response["ServiceName"] = "admin/block_pharmacy";

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
            $this->load->model("admin_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $this->admin_model->block_pharmacy($ProfileGUID);

            $this->_response["Message"] = "Pharmacy has been blocked successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function unblock_pharmacy_post() {

        $this->_response["ServiceName"] = "admin/unblock_pharmacy";

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
            $this->load->model("admin_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $this->admin_model->unblock_pharmacy($ProfileGUID);

            $this->_response["Message"] = "Pharmacy has been blocked successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function users_post() {

        $this->_response["ServiceName"] = "admin/users";

        $ListType = safe_array_key($this->_data, "ListType", "");
        if ($ListType == "GROUP_ADMIN") {
            $Users = $this->app->get_rows('Users', 'UserID, UserGUID, UserTypeID, FirstName, LastName', [
                'StatusID' => 2,
                'UserTypeID' => 4,
            ]);
        } else {
            $Users = $this->app->get_rows('Users', 'UserID, UserGUID, UserTypeID, FirstName, LastName', [
                'StatusID' => 2,
                'UserTypeID !=' => 1,
                'Email !=' => NULL,
            ]);
        }

        $this->load->model('wallet_model');
        foreach ($Users as $Key => $User) {
            $Wallet = $this->wallet_model->get_user_wallet($User['UserID']);
            if (is_null($Wallet)) {
                $this->wallet_model->create_wallet($User['UserID']);
                $WalletAmount = 0.0;
            } else {
                $WalletAmount = $Wallet['Amount'];
            }
            $Users[$Key]['WalletAmount'] = $WalletAmount;
            unset($Users[$Key]['UserID']);
        }
        $this->_response["Data"] = $Users;
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function update_user_wallet_post() {

        $this->_response["ServiceName"] = "admin/update_user_wallet";

        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required');
        $this->form_validation->set_rules('Amount', 'Amount', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $UserGUID = safe_array_key($this->_data, "UserGUID", NULL);
            $Amount = safe_array_key($this->_data, "Amount", NULL);
            $User = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $UserGUID
            ]);
            $UserID = safe_array_key($User, 'UserID', NULL);
            $this->db->update('Wallet', [
                "Amount" => $Amount
                    ], [
                "UserID" => $UserID
            ]);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_order_pharmacy_post() {

        $this->_response["ServiceName"] = "admin/update_order_pharmacy";

        $this->form_validation->set_rules('OrderGUID', 'OrderGUID', 'trim|required');
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
            $this->load->model("promo_model");
            $OrderGUID = safe_array_key($this->_data, 'OrderGUID', NULL);
            $ProfileGUID = safe_array_key($this->_data, 'ProfileGUID', NULL);
            $Pharmacy = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID
            ]);
            $this->db->update('Orders', [
                'PharmacyUserID' => $Pharmacy['UserID'],
                'UpdatedAt' => DATETIME,
                'UpdatedBy' => $this->rest->UserID,
                    ], [
                'OrderGUID' => $OrderGUID
            ]);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_referral_code_post() {

        $this->_response["ServiceName"] = "admin/update_referral_code";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('ReferralCode', 'Referral Code', 'trim|required|callback__update_referral_code_unique');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("promo_model");
            $ProfileGUID = safe_array_key($this->_data, 'ProfileGUID', NULL);
            $ReferralCode = safe_array_key($this->_data, 'ReferralCode', NULL);
            $Profile = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $ProfileGUID,
            ]);
            $UserID = safe_array_key($Profile, 'UserID', NULL);
            $this->promo_model->update_referral_code($UserID, $ReferralCode);
            $this->_response["Message"] = "Referral Code has been updated successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function promos_post() {

        $this->_response["ServiceName"] = "admin/promos";
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
            $this->load->model("promo_model");
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $this->_response["TotalRecords"] = $this->promo_model->promos(1);
            $Promos = $this->promo_model->promos(NULL, $Limit, $Offset);
            if (!empty($Promos)) {
                $this->_response["Data"] = $Promos;
            } else {
                $this->_response["Message"] = "No Promo(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function create_promo_post() {

        $this->_response["ServiceName"] = "admin/create_promo";

        $this->form_validation->set_rules('Name', 'Name', 'trim|required');
        $this->form_validation->set_rules('Description', 'Description', 'trim');
        $this->form_validation->set_rules('Code', 'Code', 'trim|required|callback__unique_promo');
        $this->form_validation->set_rules('PromoType', 'Promo Type', 'trim|required|in_list[FREE_MONEY,REFERRAL]');
        $this->form_validation->set_rules('Amount', 'Amount', 'trim|required');
        $PromoType = safe_array_key($this->_data, 'PromoType', NULL);
        if ($PromoType == 'REFERRAL') {
            $this->form_validation->set_rules('AssignToGUID', 'Assign To GUID', 'trim|required|callback__unique_referrel_promo');
            $this->form_validation->set_rules('AssignToAmount', 'Assign To Amount', 'trim|required');
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
            $this->load->model("promo_model");

            $Name = safe_array_key($this->_data, 'Name', NULL);
            $Description = safe_array_key($this->_data, 'Description', NULL);
            $Code = safe_array_key($this->_data, 'Code', NULL);
            $PromoType = safe_array_key($this->_data, 'PromoType', NULL);
            $Amount = safe_array_key($this->_data, 'Amount', NULL);
            $AssignToGUID = safe_array_key($this->_data, 'AssignToGUID', NULL);
            $AssignToAmount = safe_array_key($this->_data, 'AssignToAmount', NULL);
            $AssignTo = $this->app->get_row('Users', 'UserID', [
                'UserGUID' => $AssignToGUID
            ]);
            $AssignToUserID = safe_array_key($AssignTo, 'UserID', NULL);

            $PromoID = $this->promo_model->create_promo($Name, $Description, $Code, $PromoType, $Amount, $AssignToUserID, $AssignToAmount);
            $Promo = $this->promo_model->get_promo_by_id($PromoID);

            $this->_response["Data"] = $Promo;
            $this->_response["Message"] = "Promo has been added successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_promo_post() {

        $this->_response["ServiceName"] = "admin/update_promo";

        $this->form_validation->set_rules('PromoGUID', 'Promo GUID', 'trim|required');
        $this->form_validation->set_rules('Name', 'Name', 'trim|required');
        $this->form_validation->set_rules('Description', 'Description', 'trim');
        $this->form_validation->set_rules('Code', 'Code', 'trim|required|callback__update_unique_promo');
        $this->form_validation->set_rules('Amount', 'Amount', 'trim|required');

        $PromoGUID = safe_array_key($this->_data, 'PromoGUID', NULL);
        $Promo = $this->app->get_row('Promos', 'PromoType', [
            'PromoGUID' => $PromoGUID
        ]);
        $PromoType = safe_array_key($Promo, 'PromoType', NULL);
        if ($PromoType == 'REFERRAL') {
            $this->form_validation->set_rules('AssignToAmount', 'Assign To Amount', 'trim|required');
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
            $this->load->model("promo_model");
            $PromoGUID = safe_array_key($this->_data, 'PromoGUID', NULL);
            $Name = safe_array_key($this->_data, 'Name', NULL);
            $Description = safe_array_key($this->_data, 'Description', NULL);
            $Code = safe_array_key($this->_data, 'Code', NULL);
            $Amount = safe_array_key($this->_data, 'Amount', NULL);
            $AssignToAmount = safe_array_key($this->_data, 'AssignToAmount', NULL);

            $this->promo_model->update_promo($PromoGUID, $Name, $Description, $Code, $Amount, $AssignToAmount);
            $Promo = $this->promo_model->get_promo_by_guid($PromoGUID);

            $this->_response["Data"] = $Promo;
            $this->_response["Message"] = "Promo has been updated successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_promo_post() {

        $this->_response["ServiceName"] = "admin/delete_promo";

        $this->form_validation->set_rules('PromoGUID', 'Promo GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("promo_model");
            $PromoGUID = safe_array_key($this->_data, 'PromoGUID', NULL);
            $this->promo_model->delete_promo($PromoGUID);
            $this->_response["Message"] = "Promo has been delete successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function view_promo_post() {

        $this->_response["ServiceName"] = "admin/view_promo";

        $this->form_validation->set_rules('PromoGUID', 'Promo GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("promo_model");
            $PromoGUID = safe_array_key($this->_data, 'PromoGUID', NULL);
            $Promo = $this->promo_model->get_promo_by_guid($PromoGUID);
            $this->_response["Data"] = $Promo;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function history_promo_post() {

        $this->_response["ServiceName"] = "admin/history_promo";

        $this->form_validation->set_rules('PromoGUID', 'Promo GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("promo_model");
            $PromoGUID = safe_array_key($this->_data, 'PromoGUID', NULL);
            $Promo = $this->app->get_row('Promos', 'PromoID, PromoGUID, Code, Name, Description', [
                'PromoGUID' => $PromoGUID,
            ]);
            $Promo['History'] = $this->promo_model->get_history_by_promo_id($Promo['PromoID']);
            unset($Promo['PromoID']);
            $this->_response["Data"] = $Promo;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_user_promo_status_post() {

        $this->_response["ServiceName"] = "admin/update_user_promo_status";

        $this->form_validation->set_rules('UserPromoGUID', 'User Promo GUID', 'trim|required');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|in_list[APPROVED,REJECTED]');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("promo_model");
            $this->load->model("wallet_model");
            $UserPromoGUID = safe_array_key($this->_data, 'UserPromoGUID', NULL);
            $Status = safe_array_key($this->_data, 'Status', NULL);

            $UserPromo = $this->app->get_row('UserPromos', 'UserID, PromoID, Status', [
                'UserPromoGUID' => $UserPromoGUID,
            ]);
            $OldUserPromoStatus = safe_array_key($UserPromo, 'Status', "");
            $OldUserPromoID = safe_array_key($UserPromo, 'PromoID', "");
            $OldUserID = safe_array_key($UserPromo, 'UserID', "");
            $Promo = $this->app->get_row('Promos', 'PromoType, Amount, AssignTo, AssignToAmount', [
                'PromoID' => $OldUserPromoID,
            ]);

            $PromoType = safe_array_key($Promo, 'PromoType', "");
            if ($PromoType == 'REFERRAL') {
                $AssignTo = safe_array_key($Promo, 'AssignTo', '');
                $Amount = safe_array_key($Promo, 'Amount', 0.0);
                $AssignToAmount = safe_array_key($Promo, 'AssignToAmount', 0.0);
                if ($OldUserPromoStatus == 'PENDING') {
                    if ($Status == 'APPROVED') {
                        $this->wallet_model->add_funds($OldUserID, $Amount, $OldUserPromoID);
                        $this->wallet_model->add_funds($AssignTo, $AssignToAmount, $OldUserPromoID);
                        $this->db->update('UserPromos', [
                            'Status' => $Status,
                            'UpdatedAt' => DATETIME,
                                ], [
                            'UserPromoGUID' => $UserPromoGUID
                        ]);
                    } elseif ($Status == 'REJECTED') {
                        $this->db->update('UserPromos', [
                            'Status' => $Status,
                            'UpdatedAt' => DATETIME,
                                ], [
                            'UserPromoGUID' => $UserPromoGUID
                        ]);
                    }
                } elseif ($OldUserPromoStatus == 'REJECTED') {
                    if ($Status == 'APPROVED') {
                        $this->wallet_model->add_funds($OldUserID, $Amount, $OldUserPromoID);
                        $this->wallet_model->add_funds($AssignTo, $AssignToAmount, $OldUserPromoID);
                        $this->db->update('UserPromos', [
                            'Status' => $Status,
                            'UpdatedAt' => DATETIME,
                                ], [
                            'UserPromoGUID' => $UserPromoGUID
                        ]);
                    }
                } elseif ($OldUserPromoStatus == 'APPROVED') {
                    $this->wallet_model->remove_funds($OldUserID, $Amount, $OldUserPromoID);
                    $this->wallet_model->remove_funds($AssignTo, $AssignToAmount, $OldUserPromoID);
                    if ($Status == 'REJECTED') {
                        $this->db->update('UserPromos', [
                            'Status' => $Status,
                            'UpdatedAt' => DATETIME,
                                ], [
                            'UserPromoGUID' => $UserPromoGUID
                        ]);
                    }
                }
            } elseif ($PromoType == 'FREE_MONEY') {
                $Amount = safe_array_key($Promo, 'Amount', 0.0);
                if ($OldUserPromoStatus == 'PENDING') {
                    if ($Status == 'APPROVED') {
                        $this->wallet_model->add_funds($OldUserID, $Amount, $OldUserPromoID);
                        $this->db->update('UserPromos', [
                            'Status' => $Status,
                            'UpdatedAt' => DATETIME,
                                ], [
                            'UserPromoGUID' => $UserPromoGUID
                        ]);
                    } elseif ($Status == 'REJECTED') {
                        $this->db->update('UserPromos', [
                            'Status' => $Status,
                            'UpdatedAt' => DATETIME,
                                ], [
                            'UserPromoGUID' => $UserPromoGUID
                        ]);
                    }
                } elseif ($OldUserPromoStatus == 'REJECTED') {
                    if ($Status == 'APPROVED') {
                        $this->wallet_model->add_funds($OldUserID, $Amount, $OldUserPromoID);
                        $this->db->update('UserPromos', [
                            'Status' => $Status,
                            'UpdatedAt' => DATETIME,
                                ], [
                            'UserPromoGUID' => $UserPromoGUID
                        ]);
                    }
                } elseif ($OldUserPromoStatus == 'APPROVED') {
                    $this->wallet_model->remove_funds($OldUserID, $Amount, $OldUserPromoID);
                    if ($Status == 'REJECTED') {
                        $this->db->update('UserPromos', [
                            'Status' => $Status,
                            'UpdatedAt' => DATETIME,
                                ], [
                            'UserPromoGUID' => $UserPromoGUID
                        ]);
                    }
                }
            }

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _unique_referrel_promo($str) {
        $User = $this->app->get_row('Users', 'UserID', [
            "UserGUID" => $str,
        ]);
        $UserPormo = $this->app->get_row('Promos', 'PromoID', [
            "AssignTo" => $User['UserID'],
        ]);
        if (count($UserPormo) > 0) {
            $this->form_validation->set_message('_unique_referrel_promo', 'Referrel code for this user is already exists, try editing.');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function _unique_promo($str) {
        $rows = $this->app->get_rows('Promos', 'PromoGUID', [
            "Code" => $str,
        ]);
        if (count($rows) > 0) {
            $this->form_validation->set_message('_unique_promo', '{field} already exists');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function _update_unique_promo($str) {
        $PromoGUID = safe_array_key($this->_data, 'PromoGUID', NULL);
        $rows = $this->app->get_rows('Promos', 'PromoGUID', [
            "Code" => $str,
            "PromoGUID !=" => $PromoGUID,
        ]);
        if (count($rows) > 0) {
            $this->form_validation->set_message('_update_unique_promo', '{field} already exists');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function _update_referral_code_unique($str) {
        $ProfileGUID = safe_array_key($this->_data, 'ProfileGUID', NULL);
        $Profile = $this->app->get_row('Users', 'UserID', [
            'UserGUID' => $ProfileGUID,
        ]);
        $UserID = safe_array_key($Profile, 'UserID', NULL);
        $rows = $this->app->get_rows('Promos', 'PromoGUID', [
            "Code" => $str,
            "AssignTo !=" => $UserID,
        ]);
        if (count($rows) > 0) {
            $this->form_validation->set_message('_update_referral_code_unique', '{field} already exists');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function toggle_promo_status_post() {

        $this->_response["ServiceName"] = "admin/toggle_promo_status";

        $this->form_validation->set_rules('PromoGUID', 'Promo GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("promo_model");
            $PromoGUID = safe_array_key($this->_data, "PromoGUID", NULL);
            $this->promo_model->toggle_status($PromoGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function group_admins_post() {

        $this->_response["ServiceName"] = "admin/group_admins";
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
            $this->load->model("admin_model");
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $Extra = safe_array_key($this->_data, "Extra", NULL);
            $this->_response["TotalRecords"] = $this->admin_model->group_admins($UserID, 1, NULL, NULL, $Extra);
            $GroupAdmins = $this->admin_model->group_admins($UserID, NULL, $Limit, $Offset, $Extra);
            if (!empty($GroupAdmins)) {
                $this->_response["Data"] = $GroupAdmins;
            } else {
                $this->_response["Message"] = "No Group Admin(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function create_group_admin_post() {

        $this->_response["ServiceName"] = "admin/create_group_admin";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('FirstName', 'First name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last name', 'trim|required');
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__unique_email');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("admin_model");

            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];
            $Email = $this->_data['Email'];
            $ProfileID = $this->admin_model->create_group_admin($UserID, $FirstName, $LastName, $Email);
            $Profile = $this->app->get_profile_by_user_id($ProfileID);
            $this->_response["Data"] = $Profile;
            $this->_response["Message"] = "New group admin has been added successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_group_admin_post() {

        $this->_response["ServiceName"] = "admin/update_group_admin";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('FirstName', 'First name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last name', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("admin_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];

            $this->admin_model->update_group_admin($ProfileGUID, $FirstName, $LastName);
            $this->_response["Message"] = "Pharmacy Group User has been updated successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function view_group_admin_post() {

        $this->_response["ServiceName"] = "admin/view_group_admin";

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

    public function delete_group_admin_post() {

        $this->_response["ServiceName"] = "admin/delete_group_admin";

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
            $this->load->model("admin_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            //$this->admin_model->delete_patient($UserID, $ProfileGUID);

            $this->_response["Message"] = "Pharmacy Group has been deleted successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function agents_post() {

        $this->_response["ServiceName"] = "admin/agents";
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
            $this->load->model("admin_model");
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $Extra = safe_array_key($this->_data, "Extra", NULL);
            $this->_response["TotalRecords"] = $this->admin_model->agents($UserID, 1, NULL, NULL, $Extra);
            $Agents = $this->admin_model->agents($UserID, NULL, $Limit, $Offset, $Extra);
            if (!empty($Agents)) {
                $this->_response["Data"] = $Agents;
            } else {
                $this->_response["Message"] = "No Agent(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function create_agent_post() {

        $this->_response["ServiceName"] = "admin/create_agent";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('FirstName', 'First name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last name', 'trim|required');
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email|callback__unique_email');

        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("admin_model");

            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];
            $Email = $this->_data['Email'];
            $ProfileID = $this->admin_model->create_agent($UserID, $FirstName, $LastName, $Email);
            $Profile = $this->app->get_profile_by_user_id($ProfileID);
            $this->_response["Data"] = $Profile;
            $this->_response["Message"] = "New Agent has been added successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function update_agent_post() {

        $this->_response["ServiceName"] = "admin/update_agent";

        $this->form_validation->set_rules('ProfileGUID', 'Profile GUID', 'trim|required');
        $this->form_validation->set_rules('FirstName', 'First name', 'trim|required');
        $this->form_validation->set_rules('LastName', 'Last name', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("admin_model");

            $ProfileGUID = $this->_data['ProfileGUID'];
            $FirstName = $this->_data['FirstName'];
            $LastName = $this->_data['LastName'];

            $this->admin_model->update_agent($ProfileGUID, $FirstName, $LastName);
            $this->_response["Message"] = "Agent User has been updated successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function view_agent_post() {

        $this->_response["ServiceName"] = "admin/view_agent";

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

    public function delete_agent_post() {

        $this->_response["ServiceName"] = "admin/delete_agent";

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
            $this->load->model("admin_model");

            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            //$this->admin_model->delete_patient($UserID, $ProfileGUID);

            $this->_response["Message"] = "Pharmacy Group has been deleted successfully";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function prefered_pharmacies_post() {

        $this->_response["ServiceName"] = "admin/prefered_pharmacies";

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
            $States = $this->app->get_rows('States');
            $Out = [];
            foreach ($States as $State) {
                $Temp = [];
                $Temp['StateID'] = $State['StateID'];
                $Temp['Name'] = $State['Name'];
                $Temp['Abbr'] = $State['Abbr'];
                $PreferedPharmacies = $this->app->get_rows('PreferedPharmacies', 'PreferedPharmacyGUID, StateID, PharmacyUserID', [
                    "StateID" => $State['StateID']
                ]);
                foreach ($PreferedPharmacies as $Key => $PreferedPharmacy) {
                    $PreferedPharmacies[$Key]['Pharmacy'] = $this->app->get_pharmacy_by_user_id($PreferedPharmacy['PharmacyUserID']);
                    unset($PreferedPharmacies[$Key]['PharmacyUserID']);
                }
                $Temp['Pharmacies'] = $PreferedPharmacies;
                $Out[] = $Temp;
            }

            $this->_response["Data"] = $Out;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function add_prefered_pharmacies_post() {

        $this->_response["ServiceName"] = "admin/add_prefered_pharmacies";

        $this->form_validation->set_rules('StateID', 'State ID', 'trim|required');
        $this->form_validation->set_rules('Pharmacies', 'Pharmacies', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $StateID = safe_array_key($this->_data, "StateID", NULL);
            $Pies = safe_array_key($this->_data, "Pharmacies", []);
            $Pharmacies = explode(",", $Pies);
            foreach ($Pharmacies as $PharmacyUserGUID) {
                $PharmacyUser = $this->app->get_row('Users', 'UserID', [
                    'UserGUID' => $PharmacyUserGUID
                ]);
                $PharmacyUserID = safe_array_key($PharmacyUser, 'UserID', NULL);
                $PreferedPharmacies = $this->app->get_rows('PreferedPharmacies', 'PreferedPharmacyGUID, StateID, PharmacyUserID', [
                    "StateID" => $StateID,
                    "PharmacyUserID" => $PharmacyUserID,
                ]);
                if (empty($PreferedPharmacies)) {
                    $this->db->insert('PreferedPharmacies', [
                        'PreferedPharmacyGUID' => guid(),
                        'StateID' => $StateID,
                        'PharmacyUserID' => $PharmacyUserID,
                    ]);
                }
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function delete_prefered_pharmacies_post() {

        $this->_response["ServiceName"] = "admin/delete_prefered_pharmacies";

        $this->form_validation->set_rules('PreferedPharmacyGUID', 'Prefered Pharmacy GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $PreferedPharmacyGUID = safe_array_key($this->_data, "PreferedPharmacyGUID", NULL);
            $this->db->delete('PreferedPharmacies', [
                'PreferedPharmacyGUID' => $PreferedPharmacyGUID,
            ]);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
