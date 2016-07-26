<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';


class Demotest extends CI_Controller{

    public function index()
    {
        //Start index
        $Offset = '0';

        //Range
        $Limit = '3';

        //Fields
        //$this->db->select('*');
        $this->db->select(array('Latitude','Email'));

        //$where is query;
        $where = "UserTypeID = 3";

        $this->db->where($where);

        if (!empty($Limit) && (!empty($Offset) || $Offset == 0)) {

            //setting the limit and offset
            $this->db->limit($Limit, $Offset);
        }

        //target table
        $query = $this->db->get('Users');
        
        $Users = $query->result_array();

        var_dump($Users);
    }

    public function db()
    {
        //load the model
        $this->load->model("user_model");

        //use the model function
        $result = $this->user_model->get_pharmacist_schedule('2');

        var_dump($result);
    }

    public function model_create()
    {
        $this->load->model("roy_model");

        $id = $this->roy_model->create_roy('test_name_1');

        echo $id;
    }

    public function model_update()
    {
        $this->load->model("roy_model");

        $this->roy_model->update_roy('1','jiarong');
    }

    public function model_delete()
    {
        $this->load->model("roy_model");

        $this->roy_model->delete_roy('1');;
    }

    public function model_function(){

        $this->load->model('roy_model');

        $this->roy_model->api_invoke();
    }

    public function test()
    {
        $arr = array(
            'Limit'=>null,
            'Offset'=>10,
            );
        $Limit = safe_array_key($arr,'Limit','123');
        $Offset = safe_array_key($arr,'Offset','123');
        var_dump(compact('Limit','Offset'));
    }
    
    public function demo1(){
        
        $this->db->select('PharmacyDeliveryTimeSlotGUID AS xxx, WorkingDay');
        $this->db->order_by('WorkingDay', 'ASC');
        $this->db->where('PharmacyID', '1');
        $query = $this->db->from('PharmacyDeliveryTimeSlots');
        $results = $query->get()->result_array();
        
        p($results);
    }
    
    public function demo2(){
        
        $UserID = '5';
        $limit = '10';
        $offset = '0';
        
        $this->db->select('UA.UserAllergyGUID,UA.AllergyID');        
        $this->db->select("IFNULL(AM.AllergyText, UA.AllergyText) AS AllergyText,AM.AllergyID AS New", FALSE);        
        $this->db->join('AllergyMaster AM', 'AM.AllergyID=UA.AllergyID', 'LEFT');        
        $this->db->where('UA.UserID', $UserID);        
        if (!empty($limit) && (!empty($offset) || $offset == 0)) {
            $this->db->limit($limit, $offset);
        } 
        $query = $this->db->get('UserAllergies UA');
        $result = $query->result_array();
        
        p($result);
    }
    
    public function demo3(){
        
        $IDRequired = TRUE;
        //where UserID = $PharmacistID
        $PharmacistID = '12';
        
        if ($IDRequired) {
            $this->db->select('P.PharmacyID');
        }
        $this->db->select('U.UserID, U.Email, U.FirstName, U.LastName,U.DOB');
        $this->db->select('P.DispensingFee,P.PharmacyGUID, P.CompanyName, P.PharmacyName, '
                . 'P.PhoneNumber, P.FaxNumber, P.Website, P.AddressLine1, '
                . 'P.AddressLine2, P.City, P.State, P.Country, P.PostalCode, '
                . 'P.ShowInsuranceCard, P.ShowSTI, P.ShowAllergy, P.ShowMedReview');


        $this->db->select('U.UserGUID AS PharmacistGUID', FALSE);

        $this->db->select('IFNULL(P.PharmacyLicense,"") AS PharmacyLicense', FALSE);
        $this->db->select('IFNULL(P.PharmacyLicenseExp,"") AS PharmacyLicenseExp', FALSE);

        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.AboutMe,"") AS AboutMe', FALSE);

        $this->db->select('IFNULL(P.Latitude,"0.0") AS Latitude', FALSE);
        $this->db->select('IFNULL(P.Longitude,"0.0") AS Longitude', FALSE);


        $this->db->join('Pharmacies AS P', 'P.UserID=U.UserID');

        $this->db->from('Users AS U');
        $this->db->where('U.UserID', $PharmacistID);
        $Profile = $this->db->get()->row_array();
        $Profile = encrypt_decrypt($Profile, 1);
        

        
        p($Profile);
    }
    
    public function demo4(){
        
        $ST_UserID = '11';
        
        $this->db->select('UU.LastName,UU.FirstName');
        $this->db->select('ST.Carrier,ST.fulfillmentId');
        
        
        $this->db->select('IFNULL(ST.UserStiCardID,"0.0") AS UserStiCardID', FALSE);
        
        $this->db->join('usersticards as ST','ST.UserID = UU.UserID');
        $this->db->from('users AS UU');
        $query = $this->db->where('UU.UserID',$ST_UserID);
        $result = $query->get()->result_array();
        $result = encrypt_decrypt($result, 1);

        p($result);
    }

    
}