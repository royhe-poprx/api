<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter 
 */
class Admin_model extends CI_Model {

    public function __construct() {
        // Call the CI_Model constructor
        parent::__construct();
    }

    /**
     * pharmacies
     * @param type $UserID
     * @param type $OnlyNumRows
     * @param type $Limit
     * @param type $Offset
     * @return type
     */
    public function pharmacies($UserID, $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL, $Extra = array()) {
        $UserType = $this->app->get_row('Users', 'UserTypeID', ['UserID' => $UserID]);
        if (is_null($OnlyNumRows)) {
            $this->db->select('U.UserGUID, U.UserID, U.Email, U.FirstName, U.LastName');
            $this->db->select('P.PharmacyGUID, P.CompanyName, P.PharmacyName, P.PhoneNumber, P.FaxNumber, P.Website, P.PharmacyLicense, P.PharmacyLicenseExp');
            $this->db->select('U.UserGUID AS ProfileGUID', FALSE);
            $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
            $this->db->select('IFNULL(U.LastLoginDate,"") AS LastLoginDate', FALSE);
            $this->db->select('IFNULL(U.CreatedDate,"") AS RegisteredDate', FALSE);
        } else {
            $this->db->select('U.UserGUID');
        }
        
        $SearchFilterType = safe_array_key($Extra, 'SearchFilterType', '');
        $OrderTypeFilter = safe_array_key($Extra, 'OrderTypeFilter', '');
        $SearchFilterKeyword = safe_array_key($Extra, 'SearchFilterKeyword', '');
        $SortByColomn = safe_array_key($Extra, 'SortByColomn', '');
        $SortByOrder = safe_array_key($Extra, 'SortByOrder', '');
        
        if (in_array($SearchFilterType, ["PharmacyName", "FirstLastName", "PhoneNumber", "Email"])) {
            if ($SearchFilterType == "PharmacyName") {
                $this->db->where('( P.PharmacyName like "%' . $SearchFilterKeyword . '%" )', NULL, FALSE);
            } elseif ($SearchFilterType == "FirstLastName") {
                $encode_search_keyword = search_encryption(str_replace(" ", "", $SearchFilterKeyword));
                $this->db->where('( CONCAT(U.FirstNameSearch, U.LastNameSearch) like "%' . $encode_search_keyword . '%" )', NULL, FALSE);
            } elseif ($SearchFilterType == "PhoneNumber") {
                $encode_search_keyword = StringEncryption($SearchFilterKeyword);
                $this->db->where('U.PhoneNumber', $SearchFilterKeyword);
            } elseif ($SearchFilterType == "Email") {
                $this->load->helper('email');
                if (valid_email($SearchFilterKeyword)) {
                    $encode_search_keyword = StringEncryption($SearchFilterKeyword);
                    $this->db->where('U.Email', $SearchFilterKeyword);
                }
            }
        }

        if ($UserType['UserTypeID'] == 4) {
            $this->db->where('P.PharmacyAdminID', $UserID);
        }

        $this->db->where('U.UserTypeID', 3);

        $this->db->join('Pharmacies AS P', 'P.UserID=U.UserID');

        $this->db->from('Users' . ' AS U');

        if (is_null($OnlyNumRows)) {
            //$this->db->limit($Limit, $Offset);
            if ($Limit != "-1") {
                $this->db->limit($Limit, $Offset);
            }
            $Query = $this->db->get();
            $Users = $Query->result_array();
            $Users = encrypt_decrypt($Users, 1);
            $this->load->model('wallet_model');
            $this->load->model('promo_model');
            foreach ($Users as $Key => $User) {
                $Users[$Key]['WalletAmount'] = "0.0";
                $Users[$Key]["ReferralCode"] = "";
                $Wallet = $this->wallet_model->get_user_wallet($User['UserID']);
                if (empty(($Wallet))) {
                    $this->wallet_model->create_wallet($User['UserID']);
                    $Users[$Key]['WalletAmount'] = "0.0";
                } else {
                    $Users[$Key]['WalletAmount'] = $Wallet['Amount'];
                }
                $Users[$Key]["ReferralCode"] = $this->promo_model->get_referral_code_by_user_id($User['UserID']);
                unset($Users[$Key]['UserID']);
            }
            return $Users;
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    /**
     * 
     * 
     */
    public function pharmacy_list() {
        $this->db->select('P.PharmacyGUID, P.CompanyName, P.PharmacyName, P.PhoneNumber, P.FaxNumber, P.Website, P.PharmacyLicense, P.PharmacyLicenseExp');
        $this->db->select('U.UserGUID AS ProfileGUID', FALSE);

        $this->db->where('U.UserTypeID', 3);
        $this->db->where('P.GeoSettingType', 'GEO_REFERRAL');

        $this->db->join('Pharmacies AS P', 'P.UserID=U.UserID');

        $this->db->from('Users' . ' AS U');
        $Query = $this->db->get();
        $Users = $Query->result_array();
        $Users = encrypt_decrypt($Users, 1);
        return $Users;
    }

    /**
     * 
     * 
     */
    public function assign_pharmacy_work_area($PharmacyGUID, $AreaPoints) {
        $Pharmacy = $this->app->get_row('Pharmacies', 'PharmacyID', [
            'PharmacyGUID' => $PharmacyGUID,
        ]);
        $PharmacyID = $Pharmacy['PharmacyID'];
        $dataStr = safe_array_key($AreaPoints, 'dataStr', "");
        $dataArray = safe_array_key($AreaPoints, 'dataArray', []);
        $dataStr = $dataStr . str_replace(",", "", $dataArray[0]);
        $dataArr = safe_array_key($AreaPoints, 'dataArr', []);
        $latlng = $dataArr[0];
        if (!empty($dataArray)) {
            $this->db->where('PharmacyID', $PharmacyID)->delete('PharmacyWorkArea');
            $Query = "INSERT INTO PharmacyWorkArea (PharmacyWorkAreaGUID, PharmacyID, lat, lng, AreaStr, AreaStrGeo, AreaPoints) ";
            $Query .= "VALUES('" . guid() . "', '" . $PharmacyID . "', '" . $latlng['lat'] . "', '" . $latlng['lng'] . "', '" . $dataStr . "', PolygonFromText('POLYGON((" . $dataStr . "))'), '" . json_encode($dataArray) . "');";
            $this->db->query($Query);
        }
    }

    /**
     * 
     * 
     */
    public function pharmacy_work_area_list() {
        $this->db->select('PWA.PharmacyWorkAreaGUID, PWA.lat, PWA.lng, PWA.AreaStr, PWA.AreaPoints');
        $this->db->select('P.PharmacyGUID, P.CompanyName, P.PharmacyName, P.PhoneNumber, P.FaxNumber, P.Website, P.PharmacyLicense, P.PharmacyLicenseExp');
        $this->db->select('U.UserGUID AS ProfileGUID', FALSE);

        $this->db->join('Pharmacies AS P', 'P.PharmacyID=PWA.PharmacyID');
        $this->db->join('Users AS U', 'U.UserID=P.UserID');

        $this->db->from('PharmacyWorkArea' . ' AS PWA');
        $Query = $this->db->get();
        $Users = $Query->result_array();
        $Users = encrypt_decrypt($Users, 1);
        return $Users;
    }

    /**
     * 
     * @param type $UserID
     * @param type $GeoSettingType
     * @param type $PharmacyAdminID
     * @param type $FirstName
     * @param type $LastName
     * @param type $Email
     * @param type $CompanyName
     * @param type $PharmacyName
     * @param type $PhoneNumber
     * @param type $FaxNumber
     * @param type $Website
     * @param type $PharmacyLicenceNumber
     * @param type $PharmacyExp
     * @param type $Latitude
     * @param type $Longitude
     * @param type $AddressLine1
     * @param type $AddressLine2
     * @param type $City
     * @param type $State
     * @param type $Country
     * @param type $PostalCode
     * @param type $ShowInsuranceCard
     * @param type $ShowSTI
     * @param type $ShowAllergy
     * @param type $ShowMedReview
     * @return type
     */
    function create_pharmacy($UserID, $GeoSettingType, $PharmacyAdminID, $FirstName, $LastName, $Email, $CompanyName, $PharmacyName, $PhoneNumber, $FaxNumber, $Website, $PharmacyLicenceNumber, $PharmacyExp, $Latitude, $Longitude, $AddressLine1, $AddressLine2, $City, $State, $Country, $PostalCode, $ShowInsuranceCard, $ShowSTI, $ShowAllergy, $ShowMedReview) {
        $User = [
            "UserGUID" => guid(),
            "UserTypeID" => 3,
            "FirstName" => $FirstName,
            "FirstNameSearch" => search_encryption($FirstName),
            "LastName" => $LastName,
            "LastNameSearch" => search_encryption($LastName),
            "Email" => strtolower($Email),
            "SourceID" => 1,
            "DeviceTypeID" => 1,
            "StatusID" => 2,
            "CreatedDate" => DATETIME,
            "CreatedBy" => $UserID,
            "ForceChangePassword" => 1,
        ];

        $User = encrypt_decrypt($User);

        $this->db->insert('Users', $User);
        $User['UserID'] = $this->db->insert_id();


        $Password = random_string('alnum', '6');
        $UserLogin = [
            "UserID" => $User['UserID'],
            "LoginKeyword" => strtolower($Email),
            "SourceID" => "1",
            "CreatedDate" => DATETIME,
            "ModifiedDate" => DATETIME,
            "Password" => md5($Password),
            "TmpPass" => $Password,
        ];

        $this->db->insert('UserLogins', encrypt_decrypt($UserLogin));


        $Variables = [
            'Email' => $Email,
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'TmpPass' => $Password,
        ];
        process_in_backgroud("NewAccountCreated", $Variables);

        $Pharmacy = [
            "PharmacyGUID" => guid(),
            "UserID" => $User['UserID'],
            "GeoSettingType" => $GeoSettingType,
            "PharmacyAdminID" => $PharmacyAdminID,
            "CompanyName" => $CompanyName,
            "PharmacyName" => $PharmacyName,
            "Latitude" => $Latitude,
            "Longitude" => $Longitude,
            "AddressLine1" => $AddressLine1,
            "AddressLine2" => $AddressLine2,
            "City" => $City,
            "State" => $State,
            "Country" => $Country,
            "PostalCode" => $PostalCode,
            "PhoneNumber" => $PhoneNumber,
            "FaxNumber" => $FaxNumber,
            "Website" => $Website,
            "PharmacyLicense" => $PharmacyLicenceNumber,
            "PharmacyLicenseExp" => $PharmacyExp,
            "ShowInsuranceCard" => $ShowInsuranceCard,
            "ShowSTI" => $ShowSTI,
            "ShowAllergy" => $ShowAllergy,
            "ShowMedReview" => $ShowMedReview,
        ];
        $this->db->insert('Pharmacies', $Pharmacy);
        return $User['UserID'];
    }

    /**
     * 
     * @param type $ProfileGUID
     * @param type $PharmacyGUID
     * @param type $GeoSettingType
     * @param type $PharmacyAdminID
     * @param type $FirstName
     * @param type $LastName
     * @param type $Email
     * @param type $CompanyName
     * @param type $PharmacyName
     * @param type $PhoneNumber
     * @param type $FaxNumber
     * @param type $Website
     * @param type $PharmacyLicenceNumber
     * @param type $PharmacyExp
     * @param type $Latitude
     * @param type $Longitude
     * @param type $AddressLine1
     * @param type $AddressLine2
     * @param type $City
     * @param type $State
     * @param type $Country
     * @param type $PostalCode
     * @param type $ShowInsuranceCard
     * @param type $ShowSTI
     * @param type $ShowAllergy
     * @param type $ShowMedReview
     * @return boolean
     */
    function update_pharmacy($ProfileGUID, $PharmacyGUID, $GeoSettingType, $PharmacyAdminID, $FirstName, $LastName, $Email, $CompanyName, $PharmacyName, $PhoneNumber, $FaxNumber, $Website, $PharmacyLicenceNumber, $PharmacyExp, $Latitude, $Longitude, $AddressLine1, $AddressLine2, $City, $State, $Country, $PostalCode, $ShowInsuranceCard, $ShowSTI, $ShowAllergy, $ShowMedReview) {
        $User = [
            "FirstName" => $FirstName,
            "FirstNameSearch" => search_encryption($FirstName),
            "LastName" => $LastName,
            "LastNameSearch" => search_encryption($LastName),
            "ModifiedDate" => DATETIME,
        ];

        $User = encrypt_decrypt($User);

        $this->db->update('Users', $User, [
            'UserGUID' => $ProfileGUID,
        ]);

        $Pharmacy = [
            "GeoSettingType" => $GeoSettingType,
            "PharmacyAdminID" => $PharmacyAdminID,
            "CompanyName" => $CompanyName,
            "PharmacyName" => $PharmacyName,
            "Latitude" => $Latitude,
            "Longitude" => $Longitude,
            "AddressLine1" => $AddressLine1,
            "AddressLine2" => $AddressLine2,
            "City" => $City,
            "State" => $State,
            "Country" => $Country,
            "PostalCode" => $PostalCode,
            "PhoneNumber" => $PhoneNumber,
            "FaxNumber" => $FaxNumber,
            "Website" => $Website,
            "PharmacyLicense" => $PharmacyLicenceNumber,
            "PharmacyLicenseExp" => $PharmacyExp,            
            "ShowInsuranceCard" => $ShowInsuranceCard,
            "ShowSTI" => $ShowSTI,
            "ShowAllergy" => $ShowAllergy,
            "ShowMedReview" => $ShowMedReview,
        ];
        $this->db->update('Pharmacies', $Pharmacy, [
            "PharmacyGUID" => $PharmacyGUID,
        ]);

        return TRUE;
    }

    /**
     * $UserId
     */
    function delete_pharmacy($UserGUID) {
        $this->db->delete('Users', [
            'UserGUID' => $UserGUID,
        ]);
        return true;
    }

    /**
     * group_admins
     * @param type $UserID
     * @param type $OnlyNumRows
     * @param type $Limit
     * @param type $Offset
     * @param type $Extra
     * @return type
     */
    public function group_admins($UserID, $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL, $Extra = array()) {
        if (is_null($OnlyNumRows)) {
            $this->db->select('U.UserID, U.UserGUID, U.FirstName, U.LastName, U.Email, U.Gender');
            $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        } else {
            $this->db->select('U.UserGUID');
        }

        $SearchFilterType = safe_array_key($Extra, 'SearchFilterType', '');
        $OrderTypeFilter = safe_array_key($Extra, 'OrderTypeFilter', '');
        $SearchFilterKeyword = safe_array_key($Extra, 'SearchFilterKeyword', '');
        $SortByColomn = safe_array_key($Extra, 'SortByColomn', '');
        $SortByOrder = safe_array_key($Extra, 'SortByOrder', '');

        if (in_array($SearchFilterType, ["FirstLastName", "PhoneNumber", "Email"])) {
            if ($SearchFilterType == "FirstLastName") {
                $encode_search_keyword = search_encryption(str_replace(" ", "", $SearchFilterKeyword));
                $this->db->where('( CONCAT(U.FirstNameSearch, U.LastNameSearch) like "%' . $encode_search_keyword . '%" )', NULL, FALSE);
            } elseif ($SearchFilterType == "PhoneNumber") {
                $encode_search_keyword = StringEncryption($SearchFilterKeyword);
                $this->db->where('U.PhoneNumber', $SearchFilterKeyword);
            } elseif ($SearchFilterType == "Email") {
                $this->load->helper('email');
                if (valid_email($SearchFilterKeyword)) {
                    $encode_search_keyword = StringEncryption($SearchFilterKeyword);
                    $this->db->where('U.Email', $SearchFilterKeyword);
                }
            }
        }

        $this->db->where('U.UserTypeID', 4);

        $this->db->order_by('U.CreatedDate', 'DESC');
        $this->db->from('Users' . ' AS U');

        if (is_null($OnlyNumRows)) {
            if ($Limit != "-1") {
                $this->db->limit($Limit, $Offset);
            }
            $Query = $this->db->get();
            $Users = $Query->result_array();
            $Users = encrypt_decrypt($Users, 1);
            $this->load->model('wallet_model');
            $this->load->model('promo_model');
            foreach ($Users as $Key => $User) {
                $Users[$Key]['WalletAmount'] = "0.0";
                $Users[$Key]["ReferralCode"] = "";
                $Wallet = $this->wallet_model->get_user_wallet($User['UserID']);
                if (empty(($Wallet))) {
                    $this->wallet_model->create_wallet($User['UserID']);
                    $Users[$Key]['WalletAmount'] = "0.0";
                } else {
                    $Users[$Key]['WalletAmount'] = $Wallet['Amount'];
                }
                $Users[$Key]["ReferralCode"] = $this->promo_model->get_referral_code_by_user_id($User['UserID']);
                unset($Users[$Key]['UserID']);
            }
            return $Users;
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    /**
     * create_group_admin
     * @param type $UserID
     * @param type $FirstName
     * @param type $LastName
     * @param type $Email
     * @return type
     */
    function create_group_admin($UserID, $FirstName, $LastName, $Email) {
        $User = [
            "UserGUID" => guid(),
            "UserTypeID" => 4,
            "FirstName" => $FirstName,
            "FirstNameSearch" => search_encryption($FirstName),
            "LastName" => $LastName,
            "LastNameSearch" => search_encryption($LastName),
            "Email" => strtolower($Email),
            "SourceID" => 1,
            "DeviceTypeID" => 1,
            "StatusID" => 2,
            "CreatedDate" => DATETIME,
            "ForceChangePassword" => 1,
            "CreatedBy" => $UserID,
        ];
        $User = encrypt_decrypt($User);

        $this->db->insert('Users', $User);
        $User['UserID'] = $this->db->insert_id();
        $Password = random_string('alnum', '6');
        $UserLogin = [
            "UserID" => $User['UserID'],
            "LoginKeyword" => strtolower($Email),
            "SourceID" => "1",
            "Password" => md5($Password),
            "TmpPass" => $Password,
            "CreatedDate" => DATETIME,
            "ModifiedDate" => DATETIME,
        ];
        $this->db->insert('UserLogins', encrypt_decrypt($UserLogin));

        $Variables = [
            'Email' => strtolower($Email),
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'TmpPass' => $Password,
        ];
        process_in_backgroud("NewAccountCreated", $Variables);
        return $User['UserID'];
    }

    /**
     * update_group_admin
     * @param type $ProfileGUID
     * @param type $FirstName
     * @param type $LastName
     * @return boolean
     */
    function update_group_admin($ProfileGUID, $FirstName, $LastName) {
        $User = [
            "FirstName" => $FirstName,
            "FirstNameSearch" => search_encryption($FirstName),
            "LastName" => $LastName,
            "LastNameSearch" => search_encryption($LastName),
            "ModifiedDate" => DATETIME,
        ];

        $this->db->update('Users', encrypt_decrypt($User), [
            'UserGUID' => $ProfileGUID,
        ]);
        return TRUE;
    }

    /**
     * agents
     * @param type $UserID
     * @param type $OnlyNumRows
     * @param type $Limit
     * @param type $Offset
     * @param type $Extra
     * @return type
     */
    public function agents($UserID, $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL, $Extra = array()) {
        if (is_null($OnlyNumRows)) {
            $this->db->select('U.UserID, U.UserGUID, U.FirstName, U.LastName, U.Email, U.Gender');
            $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        } else {
            $this->db->select('U.UserGUID');
        }

        $SearchFilterType = safe_array_key($Extra, 'SearchFilterType', '');
        $OrderTypeFilter = safe_array_key($Extra, 'OrderTypeFilter', '');
        $SearchFilterKeyword = safe_array_key($Extra, 'SearchFilterKeyword', '');
        $SortByColomn = safe_array_key($Extra, 'SortByColomn', '');
        $SortByOrder = safe_array_key($Extra, 'SortByOrder', '');

        if (in_array($SearchFilterType, ["FirstLastName", "PhoneNumber", "Email"])) {
            if ($SearchFilterType == "FirstLastName") {
                $encode_search_keyword = search_encryption(str_replace(" ", "", $SearchFilterKeyword));
                $this->db->where('( CONCAT(U.FirstNameSearch, U.LastNameSearch) like "%' . $encode_search_keyword . '%" )', NULL, FALSE);
            } elseif ($SearchFilterType == "PhoneNumber") {
                $encode_search_keyword = StringEncryption($SearchFilterKeyword);
                $this->db->where('U.PhoneNumber', $SearchFilterKeyword);
            } elseif ($SearchFilterType == "Email") {
                $this->load->helper('email');
                if (valid_email($SearchFilterKeyword)) {
                    $encode_search_keyword = StringEncryption($SearchFilterKeyword);
                    $this->db->where('U.Email', $SearchFilterKeyword);
                }
            }
        }

        $this->db->where('U.UserTypeID', 5);

        $this->db->order_by('U.CreatedDate', 'DESC');
        $this->db->from('Users' . ' AS U');

        if (is_null($OnlyNumRows)) {
            if ($Limit != "-1") {
                $this->db->limit($Limit, $Offset);
            }
            $Query = $this->db->get();
            $Users = $Query->result_array();
            $Users = encrypt_decrypt($Users, 1);
            $this->load->model('wallet_model');
            $this->load->model('promo_model');
            foreach ($Users as $Key => $User) {
                $Users[$Key]['WalletAmount'] = "0.0";
                $Users[$Key]["ReferralCode"] = "";
                $Wallet = $this->wallet_model->get_user_wallet($User['UserID']);
                if (empty(($Wallet))) {
                    $this->wallet_model->create_wallet($User['UserID']);
                    $Users[$Key]['WalletAmount'] = "0.0";
                } else {
                    $Users[$Key]['WalletAmount'] = $Wallet['Amount'];
                }
                $Users[$Key]["ReferralCode"] = $this->promo_model->get_referral_code_by_user_id($User['UserID']);
                unset($Users[$Key]['UserID']);
            }
            return $Users;
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    /**
     * create_agent
     * @param type $UserID
     * @param type $FirstName
     * @param type $LastName
     * @param type $Email
     * @return type
     */
    function create_agent($UserID, $FirstName, $LastName, $Email) {
        $User = [
            "UserGUID" => guid(),
            "UserTypeID" => 5,
            "FirstName" => $FirstName,
            "FirstNameSearch" => search_encryption($FirstName),
            "LastName" => $LastName,
            "LastNameSearch" => search_encryption($LastName),
            "Email" => strtolower($Email),
            "SourceID" => 1,
            "DeviceTypeID" => 1,
            "StatusID" => 2,
            "CreatedDate" => DATETIME,
            "ForceChangePassword" => 1,
            "CreatedBy" => $UserID,
        ];
        $User = encrypt_decrypt($User);

        $this->db->insert('Users', $User);
        $User['UserID'] = $this->db->insert_id();
        $Password = random_string('alnum', '6');
        $UserLogin = [
            "UserID" => $User['UserID'],
            "LoginKeyword" => strtolower($Email),
            "SourceID" => "1",
            "Password" => md5($Password),
            "TmpPass" => $Password,
            "CreatedDate" => DATETIME,
            "ModifiedDate" => DATETIME,
        ];
        $this->db->insert('UserLogins', encrypt_decrypt($UserLogin));

        $Variables = [
            'Email' => strtolower($Email),
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'TmpPass' => $Password,
        ];
        process_in_backgroud("NewAccountCreated", $Variables);
        return $User['UserID'];
    }

    /**
     * update_agent
     * @param type $ProfileGUID
     * @param type $FirstName
     * @param type $LastName
     * @return boolean
     */
    function update_agent($ProfileGUID, $FirstName, $LastName) {
        $User = [
            "FirstName" => $FirstName,
            "FirstNameSearch" => search_encryption($FirstName),
            "LastName" => $LastName,
            "LastNameSearch" => search_encryption($LastName),
            "ModifiedDate" => DATETIME,
        ];

        $this->db->update('Users', encrypt_decrypt($User), [
            'UserGUID' => $ProfileGUID,
        ]);
        return TRUE;
    }

}
