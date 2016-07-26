<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter 
 */

class Profile_model extends CI_Model {

    public function __construct(){
            // Call the CI_Model constructor
            parent::__construct();            
    }

    /**
     * 
     * @param type $UserID
     * @param type $FirstName
     * @param type $LastName
     * @param type $Gender
     * @param type $DOB
     * @param type $PhinNumber
     * @param type $ProfilePicture
     * @return type
     */
    function create_profile($UserID, $FirstName, $LastName, $Gender, $DOB, $PhinNumber, $PhoneNumber, $ProfilePicture) {
        
        $Profile = [
            "UserGUID"=>guid(),
            "UserTypeID"=>2,
            "FirstName"=>$FirstName,
            "LastName"=>$LastName,
            "Gender"=>$Gender,
            "DOB"=>$DOB,
            "FirstNameSearch"=>search_encryption($FirstName),
            "LastNameSearch"=>search_encryption($LastName),
            "SourceID"=>"1",
            "DeviceTypeID"=>"1",
            "StatusID"=>2,
            "CreatedDate"=>DATETIME,
        ];        
        
        if(!is_null($PhinNumber)){
            $Profile['PhinNumber'] = $PhinNumber;
        }        
        if(!is_null($PhoneNumber)){
            $Profile['PhoneNumber'] = $PhoneNumber;
        }        
        if(!is_null($ProfilePicture)){
            $Profile['ProfilePicture'] = $ProfilePicture;
        }      
        
        $Profile = encrypt_decrypt($Profile);  
        
        $this->db->insert('Users', $Profile);
        $ProfileUserID = $Profile['UserID'] = $this->db->insert_id();        
        $ProfileUser = array(
            'UserDependentGUID' =>guid(),
            'UserID' => $UserID,
            'DependentUserID' => $ProfileUserID,
            'IsDeleted' => 0,
            'IsActive' => 1
        );
        $this->db->insert('UserDependents', $ProfileUser);        
        return $ProfileUserID;
    }

    /**
     * Get Patient Dependent Profile
     * @param type $UserID
     */
    public function profiles($UserID) {
        
        $this->db->select('U.FirstName, U.LastName, U.DOB, U.Gender, U.PhinNumber, U.PhoneNumber');
        $this->db->select('U.UserGUID AS ProfileGUID', FALSE);
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(D.IsActive,"1") AS IsActive', FALSE);
        $this->db->select('(CASE WHEN (ISNULL(D.IsActive)) THEN "1" ELSE "0" END) as IsOwner', FALSE);
        
        $this->db->join('UserDependents AS D', 'U.UserID=D.DependentUserID','left');
        $this->db->from('Users AS U');
        $this->db->where('D.UserID', $UserID);
        $this->db->or_where('U.UserID', $UserID);
        $this->db->order_by('U.CreatedDate');
        $Profiles=$this->db->get()->result_array();
        //echo $this->db->last_query();
        $Profiles = encrypt_decrypt($Profiles, 1);
        return $Profiles; 
    }

    /**
     * Get User Notification Count
     * @param type $UserID
     */
    public function getUserNotificationCount($UserID, $status) {
        $status = join(', ', $status);
        $SQL = "SELECT Count(NotificationID) as NotificationCount FROM " . NOTIFICATIONS . " WHERE ToUserID='" . $UserID . "' AND StatusID IN('" . $status . "')";
        $Notification = $this->db->query($SQL)->row_array();
        return $Notification['NotificationCount'];
    }

    /**
     * 
     * @param type $UserID
     * @param type $FirstName
     * @param type $LastName
     * @param type $Gender
     * @param type $DOB
     * @param type $PhinNumber
     * @param type $ProfilePicture
     * @return type
     */
    function update_profile($UserID, $FirstName, $LastName, $Gender, $DOB, $PhinNumber, $PhoneNumber, $ProfilePicture) {
        
        $Profile = [
            "FirstName"=>$FirstName,
            "LastName"=>$LastName,
            "Gender"=>$Gender,
            "DOB"=>$DOB,
            "FirstNameSearch"=>search_encryption($FirstName),
            "LastNameSearch"=>search_encryption($LastName),
            "ModifiedDate"=>DATETIME,
        ];        
        
        if(!is_null($PhinNumber)){
            $Profile['PhinNumber'] = $PhinNumber;
        }        
        if(!is_null($PhoneNumber)){
            $Profile['PhoneNumber'] = $PhoneNumber;
        }        
        if(!is_null($ProfilePicture)){
            $Profile['ProfilePicture'] = $ProfilePicture;
        }
        $Profile = encrypt_decrypt($Profile);          
        $this->db->update('Users', $Profile, array('UserID'=>$UserID));
        return $UserID;
    }

    /**
     * $UserId
     */
    function delete_profile($userID, $DependentUserID) {
        $data = array('IsDeleted' => '1', 'IsActive' => '0');
        $this->db->where('DependentUserID', $DependentUserID)->where('UserID', $userID)->update(USERDEPENDENTS, $data);
        return true;
    }

    function get_profile_by_user_id($UserID) {
        $this->db->select('U.FirstName, U.LastName, U.DOB, U.Gender');
        $this->db->select('U.UserGUID AS ProfileGUID', FALSE);
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.PhinNumber,"") AS PhinNumber', FALSE);
        $this->db->select('IFNULL(U.PhoneNumber,"") AS PhoneNumber', FALSE);
        $this->db->select('IFNULL(D.IsActive,"1") AS IsActive', FALSE);
        $this->db->select('(CASE WHEN (ISNULL(D.IsActive)) THEN "1" ELSE "0" END) as IsOwner', FALSE);
        $this->db->join('UserDependents AS D', 'U.UserID=D.DependentUserID','left');
        $this->db->from('Users AS U');
        $this->db->where('U.UserID', $UserID);
        $Profile=$this->db->get()->row_array();
        $Profile = encrypt_decrypt($Profile, 1);
        return $Profile;  
    }
    
    public function manage_profiles($Dependents) {
        if (!empty($Dependents)) {
            //First Parse Array and get UserId for GUID
            $GUIDS = array_column($Dependents, 'UserGUID');
            $UsersList = $this->db->select('UserID, UserGUID')->where_in('UserGUID', $GUIDS)->get(USERS)->result_array();
            $Users = array();
            if (!empty($UsersList)) {
                foreach ($UsersList as $user) {
                    $Users[$user['UserID']] = $user['UserGUID'];
                }
            }

            $UpdateData = array();
            foreach ($Dependents as $dependent) {
                if(in_array($dependent['UserGUID'], $Users)){
                    $id= array_search($dependent['UserGUID'], $Users);
                    $UpdateData[] = array('DependentUserID'=>  $id, 'IsActive'=>$dependent['IsActive']);
                }
            }
            //print_r($UpdateData);die;
            $this->db->update_batch(USERDEPENDENTS, $UpdateData, 'DependentUserID'); 
            return TRUE;
        }
        return FALSE;
    }
}
