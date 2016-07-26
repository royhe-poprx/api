<?php

/**
 * Description of Truncate
 *
 * @author nitins
 */
class Truncate extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->secure();
        $Tables = array(
            'Chats',
            'ChatThread',            
            'CISessions',            
            'InsuranceCards', 
            'Logs',            
            'Medications',            
            'Notifications',            
            'OrderMedications',
            'Orders',            
            'Tokens',            
            'UserAddresses',
            'UserAllergies',
            'UserCards',            
            'Wallet',
            'WalletTransactions',
        );
        
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($Tables as $Table){
            $this->db->truncate($Table);
        }        
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");   
        
        echo "Done";
    }
    
    public function hard() {
        $this->secure();
        $HardTables = array(
            'ActiveLogins',            
            'Chats',
            'ChatThread',            
            'CISessions',            
            'InsuranceCards',
            'Logs',            
            'Medications',            
            'MedicationImages',            
            'Notifications',            
            'OrderMedications',
            'Orders',            
            'Pharmacies',
            'PharmacistRatings',
            'PharmacyDeliveryTimeSlots',
            'PharmacyPaymentTypes',
            'PharmacyWorkArea',
            'PharmacyWorkingHours',  
            'PromoRules',
            'Promos',
            'Tokens',            
            'UserAddresses',
            'UserAllergies',
            'UserCards',
            'UserDependents',
            'UserLogins',
            'UserPromos',
            'Users',  
            'Wallet',
            'WalletTransactions',
        );
        
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($HardTables as $Table){
            $this->db->truncate($Table);
        }   
        $this->db->truncate(IMAGES_DB.".Images");
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");        
        $admin_user = [
            "UserGUID"=>guid(),
            "UserTypeID"=>"1",
            "Email"=>"admin@poprx.ca",
            "FirstName"=>"Poprx",
            "LastName"=>"Admin",  
            "Gender"=>"1",
            "SourceID"=>"1",            
            "DeviceTypeID"=>"1",            
            "CreatedDate"=>  DATETIME,
            "IsProfileCompleted"=>1,                
            "StatusID"=>2,
        ];        
        $admin_user['FirstNameSearch'] = search_encryption($admin_user['FirstName']);
        $admin_user['LastNameSearch'] = search_encryption($admin_user['LastName']);
        $admin_user = encrypt_decrypt($admin_user);
        $this->db->insert('Users', $admin_user);
        $admin_id = $this->db->insert_id();
        $admin_user_login = [
            "UserID"=>$admin_id,
            "LoginKeyword"=>"admin@poprx.ca", 
            "Password"=>  md5("123456"),
            "SourceID"=>  "1",
            "CreatedDate"=>  DATETIME,            
        ];        
        $admin_user_login = encrypt_decrypt($admin_user_login);
        $this->db->insert('UserLogins', $admin_user_login);
        echo "Default Data has been imported";
    }

    public function login() {
        $message = '<p>Please enter username and password.</p>';
        if ($this->input->post('Username') && $this->input->post('Password')) {
            $Username = $this->input->post('Username');
            $Password = $this->input->post('Password');
            if ($Username == 'admin' && $Password == 'e10adc3949ba59abbe56e057f20f883e') {
                $this->session->set_userdata('TrucateAllowed', TRUE);
                redirect(site_url('truncate'));
                die();
            }else{
                $message = '<p>Invalid Username or Password.</p>';
            }
        }
        echo $message;
        echo '<form action="' . site_url('truncate/login') . '" method="post">
                UserName <input type="text" name="Username"><br/>
                Password <input type="password" name="Password"><br/>
                <input type="submit" value="Login"/>
            </form>';
    }

    public function secure() {
        if ($this->session->userdata('TrucateAllowed') == false) {
            redirect(site_url('truncate/login'));
            die('');
        }
        return TRUE;
    }

}
