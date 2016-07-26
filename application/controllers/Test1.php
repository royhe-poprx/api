<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';


class Test1 extends CI_Controller{
    
    public function upload_image(){
        
//        $ImageString = file_get_contents('./uploads/temp/1.jpg');
//        $Data = base64_encode_image('./uploads/temp/1.jpg','jpg');
//        $Image = base64_decode($Data);        
//        file_put_contents('./uploads/temp/3.jpg', $Data);
        
        $Thumbs = array(
            "72X72",
            "250X250",
        );
        if (!is_dir(UPLOAD_TEMP)) {
            mkdir(UPLOAD_TEMP, 0777, TRUE);
        }
        $ImageString = base64_encode_image('./uploads/temp/1.jpg','jpg');
        $ImageName = guid() . ".jpg";
        $Attachment = base64_decode($ImageString);
        $TempPath = UPLOAD_TEMP . $ImageName;
        file_put_contents($TempPath, $Attachment);

        $Image["ImageType"] = "jpg";
        $Image["ImageData"] = $ImageString;
        $Errors = array();
        
        $this->load->library('image_lib');
        foreach ($Thumbs as $ThumbSize) {
            $Thumb = UPLOAD_TEMP . $ThumbSize . "_" . $ImageName;
            $ThumbFileString = "";
            list($w, $h) = explode("X", $ThumbSize);

            $config['image_library'] = 'gd2';
            $config['source_image'] = $TempPath;
            $config['create_thumb'] = FALSE;
            $config['maintain_ratio'] = TRUE;
            $config['width'] = $w;
            $config['height'] = $h;
            $config['new_image'] = $Thumb;
            $this->image_lib->initialize($config);
            if ($this->image_lib->resize()) {
                $ThumbFileString = base64_encode_image($Thumb);
                unlink($Thumb);
            } else {
                $Errors[$ThumbSize] = $this->image_lib->display_errors('', '');
            }
            $Image["ImageData" . $ThumbSize] = $ThumbFileString;
        }
        unlink($TempPath);
        
        $image = $Image;
        if (!empty($image)) {
            if (!isset($image['ImageGUID'])) {
                $image['ImageGUID'] = guid();
            }
            if (!isset($image['ImageName'])) {
                $image['ImageName'] = guid();
            }
//            $this->db->insert("images", $image);
//            $id = $this->db->insert_id(); 
        }
        p($image);
        $pic = $image['ImageData72X72'];
        file_put_contents('./uploads/temp/2.jpg',base64_decode($pic));
    }






//
//    public function check_users(){
//        
//        $this->db->select('*');
//        $this->db->from('users-roy');
//        $this->db->where('UserID <=','3');
//        $query = $this->db->get();
//        $records = $query->result_array();
//        
//        p($records);
//    }
//    
//    public function check_user_logins(){
//        
//        $UserID = '1';
//        
//        $this->db->select('*');
//        $this->db->join('userlogins-roy as UL','U.UserID = UL.UserID');
//        $this->db->from('users-roy AS U');
//        $this->db->where('U.UserID',$UserID);
//        $query = $this->db->get();
//        $records = $query->result_array();
//        
//        p($records);
//    }
    
   




    public function create_user(){
        
        $FirstName = 'jiarong';
        $LastName = 'he';
        $Email = 'jiarong@he4.com';
        $Password = '123456';
        
        $Password = md5($Password);
        
        $where = [
            'Email'=>encrypt_decrypt($Email),
        ];
        $rows = $this->app->get_rows('users-roy', 'UserID', $where);
        if(!count($rows)){
            
            $User = [
                'FirstName' => $FirstName,
                'LastName' => $LastName,
                'Email' => $Email,
                'CreatedDate' => DATETIME,
            ];
            // 0: encrypt; 1: decrypt
            $User = encrypt_decrypt($User,0);
            
            $this->db->insert('users-roy',$User);
            $UserID =  $this->db->insert_id();
            
            $Data = [
                'UserID' => $UserID,
                'Password' => $Password,
            ];
            $this->db->insert('userlogins-roy',$Data);
            echo 'user created';
        }else{
            
            echo 'email already existed';
        }
    }
    
    
    public function update_user(){
        
        $FirstName = 'jiarong';
        $LastName = 'he';
        $Email = 'jiarong@he3.com';
                
        $where = [
            'Email'=>$Email,
        ];
        $rows = $this->app->get_rows('users-roy', 'UserID', $where);
        if(count($rows)){
            
            $User = [
            'FirstName'=>$FirstName,
            'LastName'=>$LastName,
            'Email'=>$Email,
            ];
            $User = encrypt_decrypt($User);
            $this->db->update('users-roy', $User, $where);
            
            echo 'updated';
        }else{
            echo 'not found';
        }   
    }
    
    public function delete_user(){
        
        $Email = 'jiarong@he4.com';
        $where = [
            'Email'=>$Email,
        ];
        $rows = $this->app->get_rows('users-roy', 'UserID', $where);
        
        if(count($rows)){
            $UserID = $rows[0]['UserID'];
            $this->db->delete('userlogins-roy',['UserID'=>$UserID]);
            $this->db->delete('users-roy', ['UserID'=>$UserID]);
            echo 'deleted';
        }else{
            echo 'not found';
        }
    }
    
            

    public function user_login(){
        $Email = 'jiarong@he4.com';
        $Password = '123456';
        
        $Password = md5($Password);
        
        $rows = $this->app->get_rows('users-roy', 'UserID', ['Email'=>$Email]);
        $UserId = $rows[0]['UserID'];
        
        $Data = [
            'UserID'=>$UserId,
            'Password'=> $Password,
        ];
          
        $rows = $this->app->get_rows('userlogins-roy', '*', $Data);
        
        if(count($rows)){
            $this->db->update('users-roy',['LastLoginDate'=>DATETIME],['UserID'=>$UserId]);
            echo 'user login';
        }else{
            echo 'not found';
        }
    }
    
    
    public function change_passwords(){
       
        //Use the current password to change itself 
        $Email = 'jiarong@he4.com';
        $OldPassword = '123456';
        $NewPassword = '123456';
        
        $rows = $this->app->get_rows('users-roy','UserID',['Email'=>$Email]);
        $UserID = $rows[0]['UserID'];
        
        $where = [
            'UserID' => $UserID,
            'Password' => md5($OldPassword),
        ];
        $data = [
            'Password' => md5($NewPassword),
        ];

        $rows = $this->app->get_rows('userlogins-roy', '*', $where);
        if (count($rows)) {

            $this->db->update('userlogins-roy', $data, $where);
            echo 'changed passwords';
        } else {
            echo 'not found';
        }
    }
    
    public function reset_passwords(){
        
        //Use the email to reset the passwords
        $Email = 'jiarong@he4.com';
        $NewPassword = '123456';
        
        $rows = $this->app->get_rows('users-roy','*', ['Email'=>$Email]);
        
        if (count($rows)) {
            $UserID = $rows[0]['UserID'];
        }else{
            echo 'not found';
            return;
        }
        
        $rows = $this->app->get_rows('userlogins-roy','*',['UserID'=>$UserID]);
        
        if (count($rows)) {
            $this->db->update('userlogins-roy', ['Password'=>md5($NewPassword)], ['UserID'=>$UserID]);
            echo 'reset passwords';
        } else {
            echo 'not found';
        }
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}





















