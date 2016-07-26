<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter 
 */
class User_model extends CI_Model {

    public function __construct() {
        // Call the CI_Model constructor
        parent::__construct();
    }

    /**
     * 
     * @param type $UserTypeID
     * @param type $Email
     * @param type $FirstName
     * @param type $LastName
     * @param type $PhoneNumber
     * @param type $DOB
     * @param type $Gender
     * @param type $PhinNumber
     * @param type $ProfilePicture
     * @param type $SourceTypeID
     * @param type $SourceTokenID
     * @param type $Password
     * @param type $DeviceTypeID
     * @param type $Latitude
     * @param type $Longitude
     * @return type
     */
    public function create_user($UserTypeID, $Email, $FirstName, $LastName, $PhoneNumber, $DOB, $Gender, $PhinNumber, $ProfilePicture, $SourceTypeID, $SourceTokenID, $Password, $DeviceTypeID, $Latitude, $Longitude) {
        $User = [
            "UserGUID" => guid(),
            "UserTypeID" => $UserTypeID,
            "Email" => strtolower($Email),
            "SourceID" => $SourceTypeID,
            "DeviceTypeID" => $DeviceTypeID,
            "StatusID" => 2,
            "CreatedDate" => DATETIME,
        ];

        if ($FirstName) {
            $User['FirstName'] = $FirstName;
            $User['FirstNameSearch'] = search_encryption($FirstName);
        }
        if ($LastName) {
            $User['LastName'] = $LastName;
            $User['LastNameSearch'] = search_encryption($LastName);
        }
        if ($PhoneNumber) {
            $User['PhoneNumber'] = $PhoneNumber;
        }
        if ($DOB) {
            $User['DOB'] = $DOB;
        }
        if ($Gender) {
            $User['Gender'] = $Gender;
        }
        if ($PhinNumber) {
            $User['PhinNumber'] = $PhinNumber;
        }
        if ($ProfilePicture) {
            $User['ProfilePicture'] = $ProfilePicture;
        }
        if ($Latitude) {
            $User['Latitude'] = $Latitude;
        }
        if ($Longitude) {
            $User['Longitude'] = $Longitude;
        }

        $User = encrypt_decrypt($User);

        $this->db->insert('Users', $User);
        $User['UserID'] = $this->db->insert_id();



        if ($SourceTypeID != "1") {
            $UserLoginSource = [
                "UserID" => $User['UserID'],
                "LoginKeyword" => $SourceTokenID,
                "SourceID" => $SourceTypeID,
                "CreatedDate" => DATETIME,
                "ModifiedDate" => DATETIME,
            ];
            $UserLoginSource = encrypt_decrypt($UserLoginSource);
            $this->db->insert('UserLogins', $UserLoginSource);
        }

        $UserLogin = [
            "UserID" => $User['UserID'],
            "LoginKeyword" => strtolower($Email),
            "SourceID" => "1",
            "CreatedDate" => DATETIME,
            "ModifiedDate" => DATETIME,
        ];

        if ($Password) {
            $UserLogin['Password'] = md5($Password);
        }

        $UserLogin = encrypt_decrypt($UserLogin);
        $this->db->insert('UserLogins', $UserLogin);

        $User = encrypt_decrypt($User, 1);
        return $User;
    }

    /**
     * 
     * @param type $UserID
     * @param type $FirstName
     * @param type $LastName
     * @param type $PhoneNumber
     * @param type $Gender
     * @param type $DOB
     * @param type $PhinNumber
     * @param type $ProfilePicture
     * @return boolean
     */
    public function update_user($UserID, $FirstName, $LastName, $PhoneNumber, $Gender, $DOB, $PhinNumber, $ProfilePicture) {
        $User = [
            "FirstName" => $FirstName,
            "LastName" => $LastName,
            "PhoneNumber" => $PhoneNumber,
            "Gender" => $Gender,
            "DOB" => $DOB,
            "IsProfileCompleted" => "1",
            "ModifiedDate" => DATETIME,
        ];

        if ($PhinNumber) {
            $User['PhinNumber'] = $PhinNumber;
        }

        if ($ProfilePicture) {
            $User['ProfilePicture'] = $ProfilePicture;
        }
        
        $User = encrypt_decrypt($User);
        $User['FirstNameSearch'] = search_encryption($FirstName);
        $User['LastNameSearch'] = search_encryption($LastName);
        $this->db->update('Users', $User, array(
            'UserID' => $UserID,
        ));
        
        $UserD = $this->app->get_profile_by_user_id($UserID);
        process_in_backgroud("PostToMailChimpList", [
            "Email" => $UserD['Email'],
            "FirstName" => $UserD['FirstName'],
            "LastName" => $UserD['LastName'],
            "PhoneNumber" => $UserD['PhoneNumber'],
            "DeviceType" => "IPhone",
        ]);
        return TRUE;
    }

    /**
     * 
     * @param type $UserID
     * @param type $SourceTypeID
     * @param type $DeviceTypeID
     * @param type $DeviceToken
     * @param type $UniqueDeviceToken
     * @param type $Latitude
     * @param type $Longitude
     * @param type $IPAddress
     * @param type $AppAPIVersion
     * @return type
     */
    public function create_active_login($UserID, $SourceTypeID, $DeviceTypeID, $DeviceToken, $UniqueDeviceToken, $Latitude, $Longitude, $IPAddress, $AppAPIVersion) {
        if (empty($UniqueDeviceToken)) {
            $UniqueDeviceToken = $DeviceToken;
        }

        $ActiveLogin = [
            "LoginSessionKey" => guid(),
            "UserID" => $UserID,
            "ActiveUserID" => $UserID,
            "LoginSourceID" => $SourceTypeID,
            "DeviceTypeID" => $DeviceTypeID,
            "DeviceID" => $DeviceToken,
            "DeviceToken" => $DeviceToken,
            "UniqueDeviceToken" => $UniqueDeviceToken,
            "CreatedDate" => DATETIME,
            "AppAPIVersion" => $AppAPIVersion
        ];

        if (!is_null($Latitude)) {
            $ActiveLogin['Latitude'] = $Latitude;
        }

        if (!is_null($Longitude)) {
            $ActiveLogin['Longitude'] = $Longitude;
        }

        if (!is_null($IPAddress)) {
            $ActiveLogin['IPAddress'] = $IPAddress;
        }
        
        if ($DeviceTypeID == 3) {
            $this->db->delete('ActiveLogins', [
                'UniqueDeviceToken' => $UniqueDeviceToken,
            ]);
        } elseif ($DeviceTypeID == 2) {
            $this->db->delete('ActiveLogins', [
                'DeviceID' => $DeviceToken,
            ]);
        }
        $this->db->insert('ActiveLogins', $ActiveLogin);

        //update user last_login_at
        $this->db->set('LastLoginDate', 'CurrentLoginDate', FALSE);
        $this->db->set('CurrentLoginDate', DATETIME);
        $this->db->where('UserID', $UserID);
        $this->db->update('Users');

        return $ActiveLogin['LoginSessionKey'];
    }

    /**
     * 
     * @param type $LoginSessionKey
     * @return boolean
     */
    public function delete_user_active_login($LoginSessionKey) {
        $where = [
            "LoginSessionKey" => $LoginSessionKey,
        ];
        $this->db->delete('ActiveLogins', $where);
        return TRUE;
    }

    public function get_pharmacist_info_1($PharmacistID, $IDRequired = FALSE) {
        if ($IDRequired) {
            $this->db->select('P.PharmacyID');
        }
        $this->db->select('U.UserID, U.Email, U.FirstName, U.LastName');
        $this->db->select('P.PharmacyGUID, P.CompanyName, P.PharmacyName, '
                . 'P.PhoneNumber, P.FaxNumber, P.Website, P.PharmacyLicense, P.PharmacyLicenseExp,'
                . 'P.PharmacistLicense, P.PharmacistLicenseExp');
        $this->db->select('U.UserGUID AS PharmacistGUID', FALSE);
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.AboutMe,"") AS AboutMe', FALSE);

        $this->db->select('IFNULL(P.Latitude,"0.0") AS Latitude', FALSE);
        $this->db->select('IFNULL(P.Longitude,"0.0") AS Longitude', FALSE);


        $this->db->join('Pharmacies AS P', 'P.UserID=U.UserID');

        $this->db->from('Users AS U');
        $this->db->where('U.UserID', $PharmacistID);
        $Profile = $this->db->get()->row_array();
        $Profile = encrypt_decrypt($Profile, 1);
        return $Profile;
    }

    public function get_pharmacist_info($PharmacistID, $IDRequired = FALSE) {
        if ($IDRequired) {
            $this->db->select('P.PharmacyID');
        }
        $this->db->select('U.UserID, U.Email, U.FirstName, U.LastName');
        $this->db->select('P.PharmacyGUID, P.CompanyName, P.PharmacyName, '
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
        if (!empty($Profile)) {
            $Profile = encrypt_decrypt($Profile, 1);
            $Profile['OpenCloseSchedule'] = $this->get_pharmacist_open_close_schedule($Profile['UserID']);
        }
        return $Profile;
    }

    public function get_pharmacist_open_close_schedule($PharmacistID) {
        $Pharmacy = $this->get_pharmacist_info_1($PharmacistID, TRUE);
        $PharmacyID = safe_array_key($Pharmacy, 'PharmacyID', NULL);
        $this->db->select('PharmacyWorkingHourGUID AS OpenCloseGUID, WorkingDay, OpensAt, ClosesAt, IsClosed');
        $this->db->order_by('WorkingDay', 'ASC');
        $this->db->order_by('OpensAt', 'ASC');
        $this->db->where('PharmacyID', $PharmacyID);
        $results = $this->db->get('PharmacyWorkingHours')->result_array();
        $O = array();
        foreach ($results as $result) {
            $O[$result['WorkingDay']] = $result;
        }
        return $O;
    }

    public function get_pharmacist_schedule($PharmacistID, $IDRequired = FALSE) {
        $Pharmacy = $this->get_pharmacist_info($PharmacistID, TRUE);
        $PharmacyID = safe_array_key($Pharmacy, 'PharmacyID', NULL);

        $this->db->select('PharmacyDeliveryTimeSlotGUID AS DeliveryTimeSlotGUID, WorkingDay, SlotStartTime, SlotEndTime, CutOffTime');
        $this->db->order_by('WorkingDay', 'ASC');
        $this->db->order_by('CutOffTime', 'ASC');
        $this->db->where('PharmacyID', $PharmacyID);
        $results = $this->db->get('PharmacyDeliveryTimeSlots')->result_array();
        $O = array();
        foreach ($results as $result) {
            $O[$result['WorkingDay']][] = $result;
        }
        return $O;
    }

    public function set_pharmacist($UserID, $PharmacistID) {
        $this->db->update('Users', [
            'PharmacistID' => $PharmacistID,
                ], [
            'UserID' => $UserID,
        ]);
    }

    public function orders($UserID) {

        $UserIDS[] = $UserID;
        $DependentUserIDS = $this->app->get_rows('UserDependents', 'DependentUserID', [
            'UserID' => $UserID,
            'IsDeleted' => 0,
            'IsActive' => 1,
        ]);

        foreach ($DependentUserIDS as $DependentUserID) {
            $UserIDS[] = $DependentUserID['DependentUserID'];
        }

        $this->db->select('O.OrderGUID, O.OrderSID, O.OrderType, O.UserID, O.Status');

        $this->db->group_start();
        $this->db->where_in('O.UserID', $UserIDS);
        $this->db->where_in('O.OrderType', ['QUOTE_ORDER', 'DELIVERY_ORDER']);
        $this->db->group_end();


        $this->db->group_start();
        $this->db->or_group_start();
        $this->db->where('O.OrderType', 'QUOTE_ORDER');
        $this->db->where_in('O.Status', ['PLACED']);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('O.OrderType', 'DELIVERY_ORDER');
        $this->db->where_in('O.Status', ['DRAFT', 'PLACED', 'PACKED', 'ONROUTE']);
        $this->db->group_end();
        $this->db->group_end();

        $this->db->order_by('O.CreatedAt', 'DESC');
        $this->db->from('Orders' . ' AS O');

        $Query = $this->db->get();
        $Orders = $Query->result_array();
        //$LastQuery = $this->db->last_query();
        foreach ($Orders as $Key => $Order) {
            if ($Order['OrderType'] == 'QUOTE_ORDER') {
                $Orders[$Key]['StatusText'] = "QUOTE ORDER";
                $Orders[$Key]['RightText'] = "IN PROCESS";
                $Orders[$Key]['StatusText1'] = "Get ready for huge savings! Your pharmacist will quote you shortly.";
            } elseif ($Order['OrderType'] == 'DELIVERY_ORDER') {
                $Orders[$Key]['StatusText'] = "DELIVERY ORDER";
                if ($Order['Status'] == "DRAFT") {
                    $Orders[$Key]['RightText'] = "GET STARTED";
                    $Orders[$Key]['StatusText1'] = "Click here to place the order.";
                } elseif ($Order['Status'] == "PLACED") {
                    $Orders[$Key]['RightText'] = "PLACED";
                    $Orders[$Key]['StatusText1'] = "Way to go! Your order has been sent to your PopRx Pharmacy.";
                } elseif ($Order['Status'] == "PACKED") {
                    $Orders[$Key]['RightText'] = "PACKED";
                    $Orders[$Key]['StatusText1'] = "We have packed your meds and its ready for delivery.";
                } elseif ($Order['Status'] == "ONROUTE") {
                    $Orders[$Key]['RightText'] = "ENROUTE";
                    $Orders[$Key]['StatusText1'] = "Woohoo! Meds are on your way.";
                }
            }
            $Orders[$Key]['Patient'] = $this->app->get_profile_by_user_id($Order['UserID']);
            unset($Orders[$Key]['UserID']);
        }
        //$Orders['LastQuery'] = $LastQuery;
        return encrypt_decrypt($Orders, 1);
    }

}
