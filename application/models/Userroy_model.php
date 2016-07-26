<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter 
 */
class Userroy_model extends CI_Model {

    public function __construct() {
        // Call the CI_Model constructor
        parent::__construct();
    }
    
    
    /**
     * 
     * @param type $FirstName
     * @param type $LastName
     * @param type $Email
     * @param type $Password
     * @return type
     */
     public function create_user($FirstName,$LastName,$Email,$Password){

        /*
         * password validation OR email existence checking,
         * do it in the API
         */
        $Password = md5($Password);
        $User = [
            'Email' => strtolower($Email),
            'CreatedDate' => DATETIME,
        ];
        if ($FirstName) {
            $User['FirstName'] = $FirstName;
        }
        if ($LastName) {
            $User['LastName'] = $LastName;
        }
        // 0: encrypt; 1: decrypt
        $User = encrypt_decrypt($User, 0);
        
        // Create a user
        $this->db->insert('users-roy', $User);
        $UserID = $this->db->insert_id();
        // Create a user password for login
        $Data = [
            'UserID' => $UserID,
            'Password' => $Password,
        ];
        $this->db->insert('userlogins-roy', $Data);

        $User = encrypt_decrypt($User, 1);
        return $User;
    }
    
    
    /**
     * 
     * @param type $FirstName
     * @param type $LastName
     * @param type $Email
     * @return string
     */
    public function update_user($FirstName,$LastName,$Email){
        
        // Base on the Email to update user profile
        $where = [
            'Email' => strtolower($Email),
        ];
        
        $User = array();
        if($FirstName) {
            $User['FirstName'] = $FirstName;
        }
        if ($LastName) {
            $User['LastName'] = $LastName;
        }
        
        $User = encrypt_decrypt($User);
        $where = encrypt_decrypt($where);
        
        if($User != NULL){
            $this->db->update('users-roy', $User, $where);
            return 'user update profile';
        }
    }

    
    /**
     * 
     * @param type $Email
     * @param type $Password
     * @return string
     */    
    public function user_login($Email,$Password){
        
        // User login with Email & Password
        $rows = $this->app->get_rows('users-roy', 'UserID', ['Email'=>strtolower($Email)]);
        $UserId = $rows[0]['UserID'];
        
        $Password = md5($Password);        
        $Data = [
            'UserID'=>$UserId,
            'Password'=> $Password,
        ];
          
        $rows = $this->app->get_rows('userlogins-roy', '*', $Data);
        
        if(count($rows)){
            $this->db->update('users-roy',['LastLoginDate'=>DATETIME],['UserID'=>$UserId]);
            return 'user login ok';
        }
    }
    
    
    /**
     * 
     * @param type $Email
     * @param type $OldPassword
     * @param type $NewPassword
     * @return string
     */
    public function change_passwords($Email,$OldPassword,$NewPassword){
            
        // User change password by Email & OldPassword
        $where = encrypt_decrypt(['Email'=>strtolower($Email)]);
        $Row = $this->db
                ->where($where)
                ->get('users-roy')->row_array();
  
        $UserID = $Row['UserID'];
        
        $where = [
            'UserID'=>$UserID,
            'Password'=>  md5($OldPassword)
            ];
        $this->db->update('userlogins-roy', ['Password'=> md5($NewPassword)], $where);
        
        return 'changed password';
    }
    
    
    /**
     * 
     * @param type $Email
     * @param type $NewPassWord
     * @return string
     */
    public function reset_passwords_1($Email,$NewPassword){
        
        // User reset the password by Email
        $where = encrypt_decrypt(['Email'=>strtolower($Email)]);
        $Row = $this->db
                ->where($where)
                ->get('users-roy')->row_array();
  
        $UserID = $Row['UserID'];
        
        $data = [
            'Password'=>md5($NewPassword)
        ];
        $this->db->update('userlogins-roy', $data, ['UserID'=>$UserID]);
        
        return 'password reset by Method 1';
    }
    
    
    /**
     * 
     * @param type $Email
     * @param type $NewPassWord
     * @return string
     */
    public function reset_passwords_2($Email,$NewPassword){

        // User reset the password by Email        
        $where = encrypt_decrypt(['Email'=>strtolower($Email)]);
        $Row = $this->db
                ->where($where)
                ->get('users-roy')->row_array();
  
        $UserID = $Row['UserID'];
        
        $data = [
            'Password'=>md5($NewPassword)
        ];
        $this->db->update('userlogins-roy', $data, ['UserID'=>$UserID]);
        
        return 'password reset by Method 2';
    }

    /**
     * 
     * @param type $image
     * @param type $Email
     * @return string
     */
    public function upload_image($image,$Email){
        
        if (!empty($image)) {
            
            if (!isset($image['ImageName'])) {
                $image['ImageName'] = guid();
            }
            $where = encrypt_decrypt(['Email'=>strtolower($Email)]);
            $Row = $this->db
                            ->where($where)
                            ->get('users-roy')->row_array();

            if($Row['ImageGUID'] == NULL){
                
                $ImageGUID = guid();
                $image['ImageGUID'] = $ImageGUID;
                // First time upload image
                $this->db->update('users-roy', ['ImageGUID'=>$ImageGUID], $where);
                $this->db->insert("images-roy", $image);
                $id = $this->db->insert_id();
                return $id;
            }else{
                
                $image['ImageGUID'] = $Row['ImageGUID'];
                $rows = $this->app->get_rows('images-roy', '*', ['ImageGUID'=>$Row['ImageGUID']]);
                if(count($rows)){
                    
                    // Update the image
                    $this->db->update('images-roy',$image,['ImageGUID'=>$Row['ImageGUID']]);
                    return $rows[0]['ImageID'];
                }else{
                    
                    // If the original has been modified, Create new image
                    $this->db->insert("images-roy", $image);
                    $id = $this->db->insert_id();
                    return $id;   
                }
            }
        }
    }
    
    
    
    
}