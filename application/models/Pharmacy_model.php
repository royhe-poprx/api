<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter 
 */
class Pharmacy_model extends CI_Model {

    public function __construct() {
        // Call the CI_Model constructor
        parent::__construct();
    }

    /**
     * 
     * @param type $LoginSessionKey
     * @return boolean
     */
    public function patients($UserID, $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL, $Extra = array()) {
        $UserType = $this->app->get_row('Users', 'UserTypeID', ['UserID' => $UserID]);
        if ($UserType['UserTypeID'] == 4) {
            $PharmacistArray = $this->app->get_rows('Pharmacies', 'UserID', ['PharmacyAdminID' => $UserID]);
            $UserIDs = [];
            foreach ($PharmacistArray as $Pharmacist) {
                $UserIDs[] = $Pharmacist['UserID'];
            }
        }
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

        $this->db->where('U.UserTypeID', 2);
        //$this->db->where('U.Email !=', NULL);
        if ($UserType['UserTypeID'] == 3) {
            $this->db->where('U.PharmacistID', $UserID);
        } elseif ($UserType['UserTypeID'] == 4) {
            if (!empty($UserIDs)) {
                $this->db->where_in('U.PharmacistID', $UserIDs);
            } else {
                $this->db->where('U.PharmacistID', "X");
            }
        }
        $this->db->order_by('U.UserID', 'DESC');
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
                $Users[$Key]['IsOwner'] = "1";
                $Users[$Key]['ProfileManagerGUID'] = $User['UserGUID'];
                $Users[$Key]["ReferralCode"] = "";
                if ($User['Email'] == "") {
                    //get owner email id
                    $Owner = $this->app->get_row('UserDependents', 'UserID', [
                        "DependentUserID" => $User['UserID'],
                    ]);
                    $OwnerUser = $this->app->get_row('Users', 'UserGUID, FirstName, LastName, Email', [
                        "UserID" => $Owner['UserID'],
                    ]);
                    $Users[$Key]['Email'] = $OwnerUser['Email'];
                    $Users[$Key]['IsOwner'] = "0";
                    $Users[$Key]['ProfileManagerGUID'] = $OwnerUser['UserGUID'];
                } else {
                    $Wallet = $this->wallet_model->get_user_wallet($User['UserID']);
                    if (empty(($Wallet))) {
                        $this->wallet_model->create_wallet($User['UserID']);
                        $Users[$Key]['WalletAmount'] = "0.0";
                    } else {
                        $Users[$Key]['WalletAmount'] = $Wallet['Amount'];
                    }
                    $Users[$Key]["ReferralCode"] = $this->promo_model->get_referral_code_by_user_id($User['UserID']);
                }
                unset($Users[$Key]['UserID']);
            }
            return $Users;
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    public function patients_for_chat($UserID, $Keyword = "", $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL) {
        $UserType = $this->app->get_row('Users', 'UserTypeID', ['UserID' => $UserID]);
        if (is_null($OnlyNumRows)) {
            $this->db->select('U.UserGUID, U.FirstName, U.LastName, U.Email, U.Gender');
            $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
            $this->db->select('IFNULL((SELECT Text FROM Chats WHERE (SenderID=U.UserID OR (SenderID="' . $UserID . '" AND ReceiverID=U.UserID)) ORDER BY CreatedAt DESC LIMIT 1),"") AS LatestChatText', FALSE);
            $this->db->select('IFNULL((SELECT CreatedAt FROM Chats WHERE (SenderID=U.UserID OR (SenderID="' . $UserID . '" AND ReceiverID=U.UserID)) ORDER BY CreatedAt DESC LIMIT 1),"") AS ChatTextCreatedAt', FALSE);
            $this->db->select('IFNULL((SELECT COUNT(ChatID) FROM Chats WHERE SenderID=U.UserID AND ReceiverID="' . $UserID . '" AND ReceiverStatus="DRAFT"),0) AS ChatBadge', FALSE);
        } else {
            $this->db->select('U.UserGUID');
            $this->db->select('IFNULL((SELECT Text FROM Chats WHERE (SenderID=U.UserID OR (SenderID="' . $UserID . '" AND ReceiverID=U.UserID)) ORDER BY CreatedAt DESC LIMIT 1),"") AS LatestChatText', FALSE);
            $this->db->select('IFNULL((SELECT CreatedAt FROM Chats WHERE (SenderID=U.UserID OR (SenderID="' . $UserID . '" AND ReceiverID=U.UserID)) ORDER BY CreatedAt DESC LIMIT 1),"") AS ChatTextCreatedAt', FALSE);
        }

        $this->db->where('U.UserTypeID', 2);
        if ($Keyword) {
            $encode_keyword = search_encryption(str_replace(" ", "", $Keyword));
            $this->db->like('U.FirstNameSearch', $encode_keyword, 'both');
        } else {
            $this->db->having('LatestChatText !=', "");
        }
        //$this->db->where('U.UserTypeID', 2);
        //$this->db->where('U.Email !=', NULL);
        if ($UserType['UserTypeID'] != 1) {
            $this->db->where('U.PharmacistID', $UserID);
        }

        $this->db->group_by('U.UserID');
        $this->db->order_by('ChatTextCreatedAt', 'DESC');

        $this->db->from('Users' . ' AS U');
        //echo $this->db->last_query();
        if (is_null($OnlyNumRows)) {
            $this->db->limit($Limit, $Offset);
            $Query = $this->db->get();
            $Users = $Query->result_array();
            return encrypt_decrypt($Users, 1);
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
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
    function create_patient($UserID, $PharmacistID, $FirstName, $LastName, $Email) {
        $UserType = $this->app->get_row('Users', 'UserTypeID', ['UserID' => $UserID]);
        $User = [
            "UserGUID" => guid(),
            "UserTypeID" => 2,
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
        if ($UserType['UserTypeID'] == 3) {
            $User['PharmacistID'] = $UserID;
        } elseif ($UserType['UserTypeID'] == 4) {
            $User['PharmacistID'] = $PharmacistID;
        }

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
     * $UserId
     */
    function delete_patient($UserID, $UserGUID) {
        $UserType = $this->app->get_row('Users', 'UserTypeID', ['UserID' => $UserID]);
        if ($UserType['UserTypeID'] == 3 || $UserType['UserTypeID'] == 4) {
            $Data['PharmacistID'] = NULL;
            $this->db->where('UserGUID', $UserGUID);
            $this->db->update('Users', $Data);
        } elseif ($UserType['UserTypeID'] == 1) {
            $this->app->delete_user_by_guid($UserGUID);
        }
        return true;
    }

    function get_patient_detail_by_guid($UserGUID) {
        $this->db->select('U.UserID, U.PharmacistID, U.Email, U.FirstName, U.LastName, U.DOB, U.Gender, U.PhoneNumber');
        $this->db->select('U.UserGUID', FALSE);
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.PhinNumber,"") AS PhinNumber', FALSE);
        $this->db->from('Users AS U');
        $this->db->where('U.UserGUID', $UserGUID);
        $Profile = $this->db->get()->row_array();
        $Profile = encrypt_decrypt($Profile, 1);
        $Profile['ProfileType'] = "Owner";
        $Profile['ProfileManager'] = "Self";
        if ($Profile['Email'] == "") {
            //get owner email id
            $Owner = $this->app->get_row('UserDependents', 'UserID', [
                "DependentUserID" => $Profile['UserID'],
            ]);
            $OwnerUser = $this->app->get_row('Users', 'UserGUID, FirstName, LastName, Email', [
                "UserID" => $Owner['UserID'],
            ]);
            $Profile['Email'] = $OwnerUser['Email'];
            $Profile['ProfileType'] = "Child";
            $Profile['ProfileManager'] = $OwnerUser['FirstName'] . " " . $OwnerUser['LastName'];
            $Profile['ProfileManagerGUID'] = $OwnerUser['UserGUID'];
        }

        $Profile['Addresses'] = $this->app->get_rows('UserAddresses', 'UserAddressGUID, FormattedAddress, Latitude, Longitude, StreetNumber, Route, City, State, Country, PostalCode', [
            'UserID' => $Profile['UserID'],
        ]);

        $Profile['Medications'] = $this->app->get_rows('Medications', 'MedicationGUID, MedicationSID, MedicationName, RefillAllowed, Strength, Quantity, VerifyStatus, AutoRefill', [
            'UserID' => $Profile['UserID'],
        ]);

        $Profile['History'] = $this->app->get_rows('Medications', 'MedicationGUID, MedicationSID, MedicationName, RefillAllowed, Strength, Quantity, VerifyStatus, AutoRefill', [
            'UserID' => $Profile['UserID'],
        ]);

        $this->load->model('allergy_model');
        $Profile['Allergies'] = $this->allergy_model->allergies($Profile['UserID']);

        $Pharmacy = $this->get_pharmacy_info_by_user_id($Profile['PharmacistID']);
        unset($Pharmacy['PharmacyID']);
        $Profile['Pharmacy'] = $Pharmacy;

        $Pharmacist = $this->app->get_profile_by_user_id($Profile['PharmacistID']);
        $Profile['Pharmacist'] = $Pharmacist;

        unset($Profile['PharmacistID']);
        unset($Profile['UserID']);
        return $Profile;
    }

    function get_patient_detail_by_id($UserID) {
        $this->db->select('U.UserID, U.PharmacistID, U.Email, U.FirstName, U.LastName, U.DOB, U.Gender, U.PhoneNumber');
        $this->db->select('U.UserGUID', FALSE);
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.PhinNumber,"") AS PhinNumber', FALSE);
        $this->db->from('Users AS U');
        $this->db->where('U.UserID', $UserID);
        $Profile = $this->db->get()->row_array();
        $Profile = encrypt_decrypt($Profile, 1);
        $Profile['ProfileType'] = "Owner";
        $Profile['ProfileManager'] = "Self";

        $Profile['Addresses'] = $this->app->get_rows('UserAddresses', 'UserAddressGUID, FormattedAddress, Latitude, Longitude, StreetNumber, Route, City, State, Country, PostalCode', [
            'UserID' => $Profile['UserID'],
        ]);

        $Profile['Medications'] = $this->app->get_rows('Medications', 'MedicationGUID, MedicationSID, MedicationName, RefillAllowed, Strength, Quantity, VerifyStatus, AutoRefill', [
            'UserID' => $Profile['UserID'],
        ]);

        $Profile['History'] = $this->app->get_rows('Medications', 'MedicationGUID, MedicationSID, MedicationName, RefillAllowed, Strength, Quantity, VerifyStatus, AutoRefill', [
            'UserID' => $Profile['UserID'],
        ]);

        $this->load->model('allergy_model');
        $Profile['Allergies'] = $this->allergy_model->allergies($Profile['UserID']);

        $Pharmacy = $this->get_pharmacy_info_by_user_id($Profile['PharmacistID']);
        unset($Pharmacy['PharmacyID']);
        $Profile['Pharmacy'] = $Pharmacy;

        $Pharmacist = $this->app->get_profile_by_user_id($Profile['PharmacistID']);
        $Profile['Pharmacist'] = $Pharmacist;

        unset($Profile['PharmacistID']);
        unset($Profile['UserID']);
        return $Profile;
    }

    function get_pharmacy_info_by_user_id($UserID) {
        $this->db->select('P.PharmacyID, P.PharmacyGUID, P.PharmacyName');
        $this->db->from('Pharmacies AS P');
        $this->db->where('P.UserID', $UserID);
        return $this->db->get()->row_array();
    }

    function orders($UserID, $Extra = NULL) {

        $this->db->select('O.OrderID, O.UserID, O.PaymentMethodType, O.PaymentTypeID');
        $this->db->select('O.OrderGUID, O.OrderType, O.TransferWithQuote, O.ImportAll, O.OrderSID, O.IsDraft, '
                . 'O.PlacedAt, 1 as NumberOfProfiles, O.Status, O.IsPickup, '
                . 'O.PickUpAddress, O.DeliveryAddress, O.DeliveryDate, O.DeliveryDateMax, '
                . 'O.PackedAt, O.OnRouteAt, O.PaidAT, O.PaymentStatus, O.CancelReason');

        $SearchFilterType = safe_array_key($Extra, 'SearchFilterType', '');
        $OrderTypeFilter = safe_array_key($Extra, 'OrderTypeFilter', '');
        $SearchFilterKeyword = safe_array_key($Extra, 'SearchFilterKeyword', '');
        $SortByColomn = safe_array_key($Extra, 'SortByColomn', '');
        $SortByOrder = safe_array_key($Extra, 'SortByOrder', '');

        if (in_array($SearchFilterType, ["FirstLastName", "PhoneNumber", "Email"])) {
            $this->db->join('Users U', 'U.UserID=O.UserID', 'LEFT');
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
        } elseif (in_array($SearchFilterType, ["OrderSID"])) {
            $this->db->where('O.OrderSID', $SearchFilterKeyword);
        }

        if (!empty($OrderTypeFilter)) {
            $this->db->where_in('O.OrderType', $OrderTypeFilter);
        }

        $this->db->where('O.PharmacyUserID', $UserID);
        $this->db->group_start();

        $this->db->group_start();
        $this->db->where('O.OrderType', 'TRANSFER_ORDER');
        $this->db->where('O.Status', 'PLACED');
        $this->db->or_where('O.Status', 'CANCELLED');        
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('O.OrderType', 'QUOTE_ORDER');
        $this->db->where('O.Status', 'PLACED');
        $this->db->or_where('O.Status', 'CANCELLED');    
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('O.OrderType', 'DELIVERY_ORDER');
        $this->db->group_start();
        $this->db->where('O.Status', 'PLACED');
        $this->db->or_where('O.Status', 'PACKED');
        $this->db->or_where('O.Status', 'ONROUTE');
        $this->db->or_where('O.Status', 'CANCELLED');    
        $this->db->group_end();
        $this->db->group_end();
        $this->db->group_end();

        $this->db->order_by('O.PlacedAT', 'DESC');
        $this->db->from('Orders AS O');

        //$this->db->group_by('O.OrderID');
        $Query = $this->db->get();
        $Orders = $Query->result_array();
        //echo $this->db->last_query(); die();

        foreach ($Orders as $Key => $Order) {
            $Orders[$Key]['Patient'] = $this->app->get_profile_by_user_id($Order['UserID'], FALSE);
            $Orders[$Key]['NewImageRequestUpdated'] = $this->get_new_image_request_status($Order['OrderID']);
            $Orders[$Key]['Attachments'] = $this->get_order_attachments($Order['OrderID']);
            $PaymentType = $this->get_payment_type_by_id($Order['PaymentMethodType'], $Order['PaymentTypeID']);
            $Orders[$Key]['PaymentType'] = is_null($PaymentType) ? (object) [] : $PaymentType;
            unset($Orders[$Key]['PaymentTypeID']);
            unset($Orders[$Key]['PaymentMethodType']);
            unset($Orders[$Key]['UserID']);
            unset($Orders[$Key]['OrderID']);
        }
        return encrypt_decrypt($Orders, 1);
    }

    function order_history($UserID, $Extra = NULL, $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL) {

        if (is_null($OnlyNumRows)) {
            $this->db->select('O.OrderID, O.UserID, O.PaymentMethodType, O.PaymentTypeID');
            $this->db->select('O.OrderGUID, O.OrderType, O.ImportAll, O.OrderSID, O.IsDraft, '
                    . 'O.PlacedAt, 1 as NumberOfProfiles, O.Status, O.IsPickup, '
                    . 'O.PickUpAddress, O.DeliveryAddress, O.DeliveryDate, O.DeliveryDateMax, '
                    . 'O.PackedAt, O.OnRouteAt, O.CancelledAt, O.CancelReason, O.RejectedAt, O.RejectReason, O.CompletedAt, O.PaidAT, O.PaymentStatus');
        } else {
            $this->db->select('O.OrderGUID');
        }

        $SearchFilterType = safe_array_key($Extra, 'SearchFilterType', '');
        $OrderTypeFilter = safe_array_key($Extra, 'OrderTypeFilter', '');
        $SearchFilterKeyword = safe_array_key($Extra, 'SearchFilterKeyword', '');
        $SortByColomn = safe_array_key($Extra, 'SortByColomn', '');
        $SortByOrder = safe_array_key($Extra, 'SortByOrder', '');

        if (in_array($SearchFilterType, ["FirstLastName", "PhoneNumber", "Email"])) {
            $this->db->join('Users U', 'U.UserID=O.UserID', 'LEFT');
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
        } elseif (in_array($SearchFilterType, ["OrderSID"])) {
            $this->db->where('O.OrderSID', $SearchFilterKeyword);
        }

        if (!empty($OrderTypeFilter)) {
            $this->db->where_in('O.OrderType', $OrderTypeFilter);
        }

        $this->db->where('O.PharmacyUserID', $UserID);

        $this->db->where_in('O.Status', [
            'REJECTED',
            'CANCELLED',
            'COMPLETED',
        ]);

        $this->db->order_by('O.UpdatedAt', 'DESC');
        $this->db->from('Orders AS O');

        if (is_null($OnlyNumRows)) {
            if ($Limit != -1) {
                $this->db->limit($Limit, $Offset);
            }
            $Query = $this->db->get();
            $Orders = $Query->result_array();
            foreach ($Orders as $Key => $Order) {
                $Orders[$Key]['Patient'] = $this->app->get_profile_by_user_id($Order['UserID'], FALSE);
                $Orders[$Key]['Attachments'] = $this->get_order_attachments($Order['OrderID']);
                $PaymentType = $this->get_payment_type_by_id($Order['PaymentMethodType'], $Order['PaymentTypeID']);
                $Orders[$Key]['PaymentType'] = is_null($PaymentType) ? (object) [] : $PaymentType;
                unset($Orders[$Key]['PaymentTypeID']);
                unset($Orders[$Key]['PaymentMethodType']);
                unset($Orders[$Key]['UserID']);
                unset($Orders[$Key]['OrderID']);
            }
            return encrypt_decrypt($Orders, 1);
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    function one_orders($OrderGUID) {

        $this->db->select('O.OrderID, O.UserID, O.PaymentMethodType, O.PaymentTypeID');
        $this->db->select('O.OrderGUID, O.OrderType, O.TransferWithQuote, O.ImportAll, O.OrderSID, O.IsDraft, '
                . 'O.PlacedAt, 1 as NumberOfProfiles, O.Status, O.IsPickup, '
                . 'O.PickUpAddress, O.DeliveryAddress, O.DeliveryDate, O.DeliveryDateMax, '
                . 'O.PackedAt, O.OnRouteAt, O.PaidAT, O.PaymentStatus, O.CancelReason');


        $this->db->where('O.OrderGUID', $OrderGUID);
        $this->db->from('Orders AS O');

        //$this->db->group_by('O.OrderID');
        $Query = $this->db->get();
        $Order = $Query->row_array();
        //echo $this->db->last_query(); die();


        $Order['Patient'] = $this->app->get_profile_by_user_id($Order['UserID'], FALSE);
        $Order['Attachments'] = $this->get_order_attachments($Order['OrderID']);
        $PaymentType = $this->get_payment_type_by_id($Order['PaymentMethodType'], $Order['PaymentTypeID']);
        $Order['PaymentType'] = is_null($PaymentType) ? (object) [] : $PaymentType;
        unset($Order['PaymentTypeID']);
        unset($Order['PaymentMethodType']);
        unset($Order['UserID']);
        unset($Order['OrderID']);

        return encrypt_decrypt($Order, 1);
    }

    function get_payment_type_by_id($PaymentMethodType, $PaymentTypeID) {
        if ($PaymentMethodType == 'CC') {
            return ["PaymentType" => "CC"];
        } else {
            $this->db->select('PaymentTypeGUID, PaymentType');
            $this->db->where('PaymentTypeID', $PaymentTypeID);
            $this->db->from('PaymentTypes');
            $Query = $this->db->get();
            return $Query->row_array();
        }
    }

    function get_new_image_request_status($OrderID) {
        $this->db->select('M.NewImageRequest');
        $this->db->join('Medications AS M', 'M.MedicationID=OM.MedicationID');
        $this->db->from('OrderMedications AS OM');
        $this->db->where('OM.OrderID', $OrderID);
        $this->db->where('M.NewImageRequest', 2);
        $R = $this->db->get()->row_array();
        if (!empty($R)) {
            return "1";
        } else {
            return "0";
        }
    }

    function get_payment_methods($PharmacyID) {
        $this->db->select('PT.PaymentTypeID, PT.PaymentType');
        $this->db->select('IFNULL(PPT.Message,"") AS Message', FALSE);
        $this->db->select('IFNULL(PPT.IsActive,"0") AS IsActive', FALSE);

        $this->db->select('IFNULL(P.GatewayMerchantID,"") AS GatewayMerchantID', FALSE);
        $this->db->select('IFNULL(P.GatewayPublicKey,"") AS GatewayPublicKey', FALSE);
        $this->db->select('IFNULL(P.GatewayPrivateKey,"") AS GatewayPrivateKey', FALSE);

        $this->db->join('PharmacyPaymentTypes AS PPT', 'PPT.PaymentTypeID=PT.PaymentTypeID AND PPT.PharmacyID="' . $PharmacyID . '"', 'LEFT');
        $this->db->join('Pharmacies AS P', 'P.PharmacyID=PPT.PharmacyID AND PPT.PaymentTypeID=2', 'LEFT');
        $this->db->from('PaymentTypes AS PT');
        $this->db->where('PT.PaymentTypeID !=', 4);
        $this->db->where('PT.PaymentTypeID !=', 5);
        $this->db->order_by('PT.Order');
        $Query = $this->db->get();
        return $Query->result_array();
    }

    function update_payment_methods($PharmacyID, $PaymentMethods) {

        $this->db->delete('PharmacyPaymentTypes', [
            'PharmacyID' => $PharmacyID,
        ]);

        foreach ($PaymentMethods as $PaymentMethod) {
            $this->db->insert('PharmacyPaymentTypes', [
                'PharmacyID' => $PharmacyID,
                'PaymentTypeID' => $PaymentMethod['PaymentTypeID'],
                'Message' => $PaymentMethod['Message'],
                'IsActive' => $PaymentMethod['IsActive'],
            ]);
        }
    }

    function get_opening_hours($PharmacyID) {
        $Days = [
            'MONDAY',
            'TUESDAY',
            'WEDNESDAY',
            'THURSDAY',
            'FRIDAY',
            'SATURDAY',
            'SUNDAY'
        ];
        $Out = [];
        foreach ($Days as $Day) {
            $this->db->select('PWH.PharmacyWorkingHourGUID AS OpeningHourGUID, PWH.WorkingDay, PWH.OpensAt, PWH.ClosesAt, PWH.IsClosed');
            $this->db->from('PharmacyWorkingHours AS PWH');
            $this->db->where('PWH.PharmacyID', $PharmacyID);
            $this->db->where('PWH.WorkingDay', $Day);
            $this->db->order_by('PWH.WorkingDay');
            $Query = $this->db->get();
            $Hour = $Query->row_array();
            if (!empty($Hour)) {
                $Out[$Day] = $Hour;
            } else {
                $Out[$Day] = (object) [];
            }
        }
        return $Out;
    }

    function update_opening_hours($PharmacyID, $OpeningHours) {
        foreach ($OpeningHours as $OpeningHour) {
            if ($OpeningHour['OpeningHourGUID']) {
                $this->db->update('PharmacyWorkingHours', [
                    'WorkingDay' => $OpeningHour['WorkingDay'],
                    'OpensAt' => $OpeningHour['OpensAt'],
                    'ClosesAt' => $OpeningHour['ClosesAt'],
                    'IsClosed' => $OpeningHour['IsClosed'],
                        ], [
                    'PharmacyWorkingHourGUID' => $OpeningHour['OpeningHourGUID'],
                ]);
            } else {
                $this->db->insert('PharmacyWorkingHours', [
                    'PharmacyWorkingHourGUID' => guid(),
                    'PharmacyID' => $PharmacyID,
                    'WorkingDay' => $OpeningHour['WorkingDay'],
                    'OpensAt' => $OpeningHour['OpensAt'],
                    'ClosesAt' => $OpeningHour['ClosesAt'],
                    'IsClosed' => $OpeningHour['IsClosed'],
                ]);
            }
        }
    }

    function get_delivery_slots($PharmacyID) {
        $Days = [
            'MONDAY',
            'TUESDAY',
            'WEDNESDAY',
            'THURSDAY',
            'FRIDAY',
            'SATURDAY',
            'SUNDAY'
        ];
        $Out = [];
        foreach ($Days as $Day) {
            $this->db->select('PDTS.PharmacyDeliveryTimeSlotGUID AS SlotGUID, PDTS.WorkingDay, PDTS.SlotStartTime, PDTS.SlotEndTime, PDTS.CutOffTime, PDTS.NoDelivery');
            $this->db->from('PharmacyDeliveryTimeSlots AS PDTS');
            $this->db->where('PDTS.PharmacyID', $PharmacyID);
            $this->db->where('PDTS.WorkingDay', $Day);
            $this->db->order_by('PDTS.CutOffTime');
            $Query = $this->db->get();
            $Slots = $Query->result_array();
            if (!empty($Slots)) {
                foreach ($Slots as $Slot) {
                    $SlotTmp = [
                        'SlotGUID' => $Slot['SlotGUID'],
                        'Day' => $Slot['WorkingDay'],
                        'ThresholdTime' => $Slot['CutOffTime'],
                        'StartTime' => $Slot['SlotStartTime'],
                        'EndTime' => $Slot['SlotEndTime'],
                        'NoDelivery' => $Slot['NoDelivery'],
                    ];
                    $Out[$Day][] = $SlotTmp;
                }
            } else {
                $Out[$Day] = [];
            }
        }
        return $Out;
    }

    function update_delivery_slots($PharmacyID, $DeliverySlots) {
        foreach ($DeliverySlots as $WorkingDay => $DeliverySlotArray) {
            foreach ($DeliverySlotArray as $DeliverySlot) {
                if (array_key_exists('SlotGUID', $DeliverySlot)) {
                    if ($DeliverySlot['SlotGUID']) {
                        if ($DeliverySlot['IsDelete']) {
                            $this->db->delete('PharmacyDeliveryTimeSlots', [
                                'PharmacyDeliveryTimeSlotGUID' => $DeliverySlot['SlotGUID'],
                            ]);
                        } else {
                            $this->db->update('PharmacyDeliveryTimeSlots', [
                                'WorkingDay' => $WorkingDay,
                                'SlotStartTime' => $DeliverySlot['StartTime'],
                                'SlotEndTime' => $DeliverySlot['EndTime'],
                                'CutOffTime' => $DeliverySlot['ThresholdTime'],
                                'NoDelivery' => $DeliverySlot['NoDelivery'],
                                    ], [
                                'PharmacyDeliveryTimeSlotGUID' => $DeliverySlot['SlotGUID'],
                            ]);
                        }
                    } else {
                        if (!$DeliverySlot['IsDelete']) {
                            $this->db->insert('PharmacyDeliveryTimeSlots', [
                                'PharmacyDeliveryTimeSlotGUID' => guid(),
                                'PharmacyID' => $PharmacyID,
                                'WorkingDay' => $WorkingDay,
                                'SlotStartTime' => $DeliverySlot['StartTime'],
                                'SlotEndTime' => $DeliverySlot['EndTime'],
                                'CutOffTime' => $DeliverySlot['ThresholdTime'],
                                'NoDelivery' => $DeliverySlot['NoDelivery'],
                            ]);
                        }
                    }
                }
            }
        }
    }

    function get_billing_info($PharmacyID) {
        $this->db->select('IFNULL(P.TaxPercentage,"0.0") AS TaxPercentage, IFNULL(P.DispensingFee,"0.0") AS DispensingFee', FALSE);
        $this->db->from('Pharmacies AS P');
        $this->db->where('P.PharmacyID', $PharmacyID);
        $Query = $this->db->get();
        return $Query->row_array();
    }

    function update_billing_info($PharmacyID, $TaxPercentage, $DispensingFee) {
        $this->db->update('Pharmacies', [
            'TaxPercentage' => $TaxPercentage,
            'DispensingFee' => $DispensingFee,
                ], [
            'PharmacyID' => $PharmacyID,
        ]);
    }

    function get_order_attachments($OrderID) {
        $this->db->select('M.MedicationGUID, M.MedicationName, IFNULL(OM.SpecialInstructions,"") AS SpecialInstructions, M.MedicationImages');
        $this->db->join('Medications AS M', 'M.MedicationID=OM.MedicationID');
        $this->db->where('OM.OrderID', $OrderID);
        $this->db->where('M.MedicationImages !=', NULL);
        $this->db->where('M.MedicationImages !=', "");
        $this->db->from('OrderMedications AS OM');
        $Query = $this->db->get();
        $Meds = $Query->result_array();
        foreach ($Meds as $Key => $Med) {
            if (!empty($Med['MedicationImages'])) {
                $Med['MedicationImages'] = explode(",", $Med['MedicationImages']);
            }
            $Meds[$Key] = $Med;
        }
        return $Meds;
    }

    function get_order_patient_info($OrderGUID) {
        return array();
    }

    function get_order_medications($OrderID, $IDRequired = FALSE) {

        if ($IDRequired) {
            $this->db->select('M.MedicationID');
        }

        $this->db->select('OM.OrderMedicationGUID, M.MedicationName, M.MedicationSID, M.RefillAllowed, M.Strength, M.Quantity, M.IsNew, OM.IsPacked');

        $this->db->select('OM.Price, OM.DispensingFee, OM.AdditionalFee, OM.Discount, OM.IsTaxApplicable, OM.AmountDue, IFNULL(OM.SpecialInstructions,"") AS SpecialInstructions', FALSE);
        $this->db->join('Medications AS M', 'M.MedicationID=OM.MedicationID');

        $this->db->from('OrderMedications AS OM');
        $this->db->where('OM.OrderID', $OrderID);
        return $this->db->get()->result_array();
    }

    function save_draft_order($UserID, $OrderGUID, $OrderMedications, $SubTotal, $Tax, $DiscountAmount, $DiscountCode, $GrandTotal) {

        //
        $this->db->update('Orders', [
            'IsDraft' => '1',
            'SubTotal' => $SubTotal,
            'Tax' => $Tax,
            'DiscountAmount' => $DiscountAmount,
            'DiscountCode' => $DiscountCode,
            'GrandTotal' => $GrandTotal,
            'UpdatedAt' => DATETIME,
            'UpdatedBy' => $UserID,
                ], [
            'OrderGUID' => $OrderGUID,
        ]);

        //

        $Order = $this->get_order_by_guid($OrderGUID);
        if ($Order['OrderType'] == 'TRANSFER_ORDER' || $Order['OrderType'] == 'QUOTE_ORDER') {
            if (!empty($OrderMedications)) {
                foreach ($OrderMedications as $OrderMedication) {
                    $OrderMedicationGUID = safe_array_key($OrderMedication, 'OrderMedicationGUID', '');
                    if ($OrderMedicationGUID != "") {
                        //update
                        $OrderMedicationDetail = $this->get_order_medication_by_guid($OrderMedicationGUID);
                        $Medication = [
                            'MedicationName' => $OrderMedication['MedicationName'],
                            'RefillAllowed' => $OrderMedication['RefillAllowed'],
                            'MedicationSID' => $OrderMedication['MedicationSID'],
                            'Strength' => safe_array_key($OrderMedication, 'Strength', NULL),
                            'Quantity' => safe_array_key($OrderMedication, 'Quantity', NULL),
                            'UpdatedAt' => DATETIME,
                        ];
                        $this->db->update('Medications', encrypt_decrypt($Medication), [
                            'MedicationID' => $OrderMedicationDetail['MedicationID'],
                        ]);

                        //update order medication
                        $OMedication = [
                            'Price' => safe_array_key($OrderMedication, 'Price', NULL),
                            'DispensingFee' => safe_array_key($OrderMedication, 'DispensingFee', 0.0),
                            'AdditionalFee' => safe_array_key($OrderMedication, 'AdditionalFee', 0.0),
                            'Discount' => safe_array_key($OrderMedication, 'Discount', 0.0),
                            'IsTaxApplicable' => safe_array_key($OrderMedication, 'IsTaxApplicable', 0),
                            'AmountDue' => safe_array_key($OrderMedication, 'AmountDue', 0.0),
                        ];
                        $this->db->update('OrderMedications', encrypt_decrypt($OMedication), [
                            'OrderMedicationGUID' => $OrderMedicationGUID,
                        ]);
                    } elseif ($OrderMedicationGUID == "") {
                        //insert
                        //insert medication
                        $Medication = [
                            'MedicationGUID' => guid(),
                            'MedicationSID' => $OrderMedication['MedicationSID'],
                            'UserID' => $Order['UserID'],
                            'MedicationName' => $OrderMedication['MedicationName'],
                            'RefillAllowed' => $OrderMedication['RefillAllowed'],
                            'Strength' => safe_array_key($OrderMedication, 'Strength', NULL),
                            'Quantity' => safe_array_key($OrderMedication, 'Quantity', NULL),
                            'VerifyStatus' => 'VERIFYING',
                            'InProcess' => 1,
                            'CreatedAt' => DATETIME,
                            'UpdatedAt' => DATETIME,
                        ];
                        $this->db->insert('Medications', encrypt_decrypt($Medication));
                        $MedicationID = $this->db->insert_id();
                        //update order medication
                        $OMedication = [
                            'OrderMedicationGUID' => guid(),
                            'OrderID' => $Order['OrderID'],
                            'MedicationID' => $MedicationID,
                            'Price' => safe_array_key($OrderMedication, 'Price', NULL),
                            'DispensingFee' => safe_array_key($OrderMedication, 'DispensingFee', 0.0),
                            'AdditionalFee' => safe_array_key($OrderMedication, 'AdditionalFee', 0.0),
                            'Discount' => safe_array_key($OrderMedication, 'Discount', 0.0),
                            'IsTaxApplicable' => safe_array_key($OrderMedication, 'IsTaxApplicable', 0),
                            'AmountDue' => safe_array_key($OrderMedication, 'AmountDue', 0.0),
                        ];
                        $this->db->insert('OrderMedications', encrypt_decrypt($OMedication));
                    }
                }
            }
        } elseif ($Order['OrderType'] == 'DELIVERY_ORDER') {
            if (!empty($OrderMedications)) {
                foreach ($OrderMedications as $OrderMedication) {
                    $OrderMedicationGUID = safe_array_key($OrderMedication, 'OrderMedicationGUID', '');
                    if ($OrderMedicationGUID) {
                        $OMedication = [
                            'IsPacked' => safe_array_key($OrderMedication, 'IsPacked', 0),
                        ];
                        $this->db->update('OrderMedications', encrypt_decrypt($OMedication), [
                            'OrderMedicationGUID' => $OrderMedicationGUID,
                        ]);
                    }
                }
            }
        }
    }

    function complete_order($PharmacyUserID, $OrderGUID) {
        $Order = $this->get_order_by_guid($OrderGUID);
        $OrderID = $Order['OrderID'];
        $OrderType = $Order['OrderType'];
        $TransferWithQuote = $Order['TransferWithQuote'];
        if ($OrderType == 'TRANSFER_ORDER') {
            $this->db->update('Orders', [
                'Status' => 'COMPLETED',
                'CompletedAt' => DATETIME,
                'UpdatedAt' => DATETIME,
                'UpdatedBy' => $PharmacyUserID,
                    ], [
                'OrderGUID' => $OrderGUID,
            ]);

            $this->mark_medication_verified($OrderID);
            if ($TransferWithQuote == 1) {
                $this->load->model('medication_model');
                $Medications = $this->get_order_medications($OrderID, TRUE);
                $this->mark_medication_verified($OrderID);
                $PatientID = $Order['UserID'];
                $DxOrderID = $this->medication_model->create_delivery_order($PharmacyUserID, $PatientID, $Medications, $OrderID);
                //notification
                $this->app->notify($PharmacyUserID, $PatientID, 'DX_ORDER_RECEIVED', $DxOrderID);
            } else {
                $this->add_medication_to_cart($OrderID);

                //notification
                $PatientID = $Order['UserID'];
                $this->app->notify($PharmacyUserID, $PatientID, 'TX_ORDER_COMPLETED', $OrderID);
            }
        } elseif ($OrderType == 'QUOTE_ORDER') {
            $this->db->update('Orders', [
                'Status' => 'COMPLETED',
                'CompletedAt' => DATETIME,
                'UpdatedAt' => DATETIME,
                'UpdatedBy' => $PharmacyUserID,
                    ], [
                'OrderGUID' => $OrderGUID,
            ]);

            $this->load->model('medication_model');
            $Medications = $this->get_order_medications($OrderID, TRUE);
            $this->mark_medication_verified($OrderID);
            $PatientID = $Order['UserID'];
            $DxOrderID = $this->medication_model->create_delivery_order($PharmacyUserID, $PatientID, $Medications, $OrderID);

            //notification
            $this->app->notify($PharmacyUserID, $PatientID, 'DX_ORDER_RECEIVED', $DxOrderID);
        } elseif ($OrderType == 'DELIVERY_ORDER') {
            //non
        }
    }

    function pack_order($PharmacyUserID, $OrderGUID) {
        $Order = $this->get_order_by_guid($OrderGUID);
        $OrderID = $Order['OrderID'];
        $OrderType = $Order['OrderType'];

        if ($OrderType == 'TRANSFER_ORDER') {
            
        } elseif ($OrderType == 'QUOTE_ORDER') {
            
        } elseif ($OrderType == 'DELIVERY_ORDER') {
            $this->db->update('Orders', [
                'Status' => 'PACKED',
                'PackedAt' => DATETIME,
                'UpdatedAt' => DATETIME,
                'UpdatedBy' => $PharmacyUserID,
                    ], [
                'OrderGUID' => $OrderGUID,
            ]);

            //notification
            $PatientID = $Order['UserID'];
            $this->app->notify($PharmacyUserID, $PatientID, 'DX_ORDER_PACKED', $OrderID);
        }
    }

    function onroute_order($PharmacyUserID, $OrderGUID) {
        $Order = $this->get_order_by_guid($OrderGUID);
        $OrderID = $Order['OrderID'];
        $OrderType = $Order['OrderType'];

        if ($OrderType == 'TRANSFER_ORDER') {
            
        } elseif ($OrderType == 'QUOTE_ORDER') {
            
        } elseif ($OrderType == 'DELIVERY_ORDER') {
            $this->db->update('Orders', [
                'Status' => 'ONROUTE',
                'OnRouteAt' => DATETIME,
                'UpdatedAt' => DATETIME,
                'UpdatedBy' => $PharmacyUserID,
                    ], [
                'OrderGUID' => $OrderGUID,
            ]);
            $this->mark_medication_free($OrderID);
            //$this->update_medication_refill_count($OrderID);
            //notification
            $PatientID = $Order['UserID'];
            $this->app->notify($PharmacyUserID, $PatientID, 'DX_ORDER_ONROUTE', $OrderID);
        }
    }

    function reject_order($UserID, $OrderGUID, $RejectReason) {
        $Order = $this->get_order_by_guid($OrderGUID);
        $OrderID = $Order['OrderID'];
        $this->db->update('Orders', [
            'Status' => 'REJECTED',
            'RejectReason' => $RejectReason,
            'RejectedAt' => DATETIME,
                ], [
            'OrderGUID' => $OrderGUID,
        ]);

        if ($Order['OrderType'] == "TRANSFER_ORDER") {
            $this->mark_medication_un_verified($OrderID);
            if ($Order['ImportAll'] == 1) {
                $this->delete_import_all_order_medication($OrderID);
            }
        }

        //notification
        $PatientID = $Order['UserID'];
        $this->app->notify($UserID, $PatientID, 'ORDER_REJECTED', $OrderID);
    }

    function mark_medication_verified($OrderID) {
        $OrderMedications = $this->app->get_rows('OrderMedications', 'MedicationID', [
            'OrderID' => $OrderID,
        ]);
        foreach ($OrderMedications as $OrderMedication) {
            $this->db->update('Medications', [
                'IsNew' => '0',
                'VerifyStatus' => 'VERIFIED',
                'InProcess' => 0,
                'UpdatedAt' => DATETIME,
                    ], [
                'MedicationID' => $OrderMedication['MedicationID'],
            ]);
        }
    }

    function add_medication_to_cart($OrderID) {
        $Order = $this->app->get_row('Orders', 'UserID', [
            'OrderID' => $OrderID,
        ]);
        $OrderMedications = $this->app->get_rows('OrderMedications', 'MedicationID', [
            'OrderID' => $OrderID,
        ]);
        $this->load->model('medication_model');
        foreach ($OrderMedications as $OrderMedication) {
            $Medication = $this->app->get_row('Medications', 'MedicationGUID', [
                'MedicationID' => $OrderMedication['MedicationID'],
            ]);
            $this->medication_model->add_medication_to_quote($Order['UserID'], $Medication['MedicationGUID']);
        }
    }

    function mark_medication_free($OrderID) {
        $OrderMedications = $this->app->get_rows('OrderMedications', 'MedicationID', [
            'OrderID' => $OrderID,
        ]);
        foreach ($OrderMedications as $OrderMedication) {
            $this->db->update('Medications', [
                'InProcess' => 0,
                'UpdatedAt' => DATETIME,
                    ], [
                'MedicationID' => $OrderMedication['MedicationID'],
            ]);
        }
    }

    function update_medication_refill_count($OrderID) {
        $OrderMedications = $this->app->get_rows('OrderMedications', 'MedicationID', [
            'OrderID' => $OrderID,
        ]);
        foreach ($OrderMedications as $OrderMedication) {
            $Medication = $this->app->get_row('Medications', 'RefillAllowed', [
                'MedicationID' => $OrderMedication['MedicationID'],
            ]);
            $this->db->update('Medications', encrypt_decrypt([
                'RefillAllowed' => $Medication['RefillAllowed'] - 1,
                    ]), [
                'MedicationID' => $OrderMedication['MedicationID'],
            ]);
        }
    }

    function mark_medication_un_verified($OrderID) {
        $OrderMedications = $this->app->get_rows('OrderMedications', 'MedicationID', [
            'OrderID' => $OrderID,
        ]);
        foreach ($OrderMedications as $OrderMedication) {
            $this->db->update('Medications', [
                'VerifyStatus' => 'UN_VERIFIED',
                'InProcess' => 0,
                'UpdatedAt' => DATETIME,
                    ], [
                'MedicationID' => $OrderMedication['MedicationID'],
            ]);
        }
    }

    function delete_import_all_order_medication($OrderID) {
        $OrderMedications = $this->app->get_rows('OrderMedications', '*', [
            'OrderID' => $OrderID,
        ]);
        foreach ($OrderMedications as $OrderMedication) {
            $this->db->delete('Medications', [
                'MedicationID' => $OrderMedication['MedicationID'],
            ]);
        }
    }

    function get_order_medication_by_guid($OrderMedicationGUID) {
        $this->db->select('OM.OrderMedicationGUID, OM.MedicationID');
        $this->db->from('OrderMedications AS OM');
        $this->db->where('OM.OrderMedicationGUID', $OrderMedicationGUID);
        return $this->db->get()->row_array();
    }

    function get_order_by_guid($OrderGUID) {
        $this->db->select('O.OrderGUID, O.OrderID, O.UserID, O.OrderType, O.TransferWithQuote, O.ImportAll');
        $this->db->join('OrderMedications AS OM', 'O.OrderID=OM.OrderID');
        $this->db->from('Orders AS O');
        $this->db->where('O.OrderGUID', $OrderGUID);
        return $this->db->get()->row_array();
    }

    function delete_order_medication_by_guid($OrderMedicationGUID) {
        
    }

    function get_order_detail_by_guid($OrderGUID) {
        $OrderDetail = [];
        $this->db->select('O.OrderID, O.UserID');
        $this->db->select('O.OrderGUID, O.OrderType, O.OrderSID, O.PlacedAt, O.TransferWithQuote');
        $this->db->select('O.SubTotal, O.Tax, O.DiscountAmount, O.DiscountCode, O.GrandTotal');
        $this->db->from('Orders AS O');
        $this->db->where('O.OrderGUID', $OrderGUID);
        $Order = $this->db->get()->row_array();

        if ($Order['OrderType'] == 'TRANSFER_ORDER') {
            $OrderDetail['Billing'] = (object) [];
            $OrderDetail['PharmacyInfo'] = $this->get_tranfered_pharmacy_info($OrderGUID);
        } elseif ($Order['OrderType'] == 'QUOTE_ORDER') {

            $Billing['SubTotal'] = $Order['SubTotal'];
            $Billing['Tax'] = $Order['Tax'];
            $Billing['DiscountAmount'] = $Order['DiscountAmount'];
            $Billing['DiscountCode'] = $Order['DiscountCode'];
            $Billing['GrandTotal'] = $Order['GrandTotal'];

            $OrderDetail['Billing'] = $Billing;
            $OrderDetail['PharmacyInfo'] = (object) [];
        } elseif ($Order['OrderType'] == 'DELIVERY_ORDER') {
            $Billing['SubTotal'] = $Order['SubTotal'];
            $Billing['Tax'] = $Order['Tax'];
            $Billing['DiscountAmount'] = $Order['DiscountAmount'];
            $Billing['DiscountCode'] = $Order['DiscountCode'];
            $Billing['GrandTotal'] = $Order['GrandTotal'];

            $OrderDetail['Billing'] = $Billing;
            $OrderDetail['PharmacyInfo'] = (object) [];
        }
        $OrderDetail['OrderType'] = $Order['OrderType'];
        $OrderDetail['OrderSID'] = $Order['OrderSID'];
        $OrderDetail['OrderGUID'] = $Order['OrderGUID'];
        $OrderDetail['PlacedAt'] = $Order['PlacedAt'];
        $OrderDetail['Patient'] = $this->app->get_profile_by_user_id($Order['UserID'], FALSE);
        $Medications = $this->get_order_medications($Order['OrderID']);
        $OrderDetail['Medications'] = encrypt_decrypt($Medications, 1);

        return encrypt_decrypt($OrderDetail, 1);
    }

    function get_tranfered_pharmacy_info($OrderGUID) {
        $this->db->select('O.TPName, O.TPPhoneNumber');
        $this->db->from('Orders AS O');
        $this->db->where('O.OrderGUID', $OrderGUID);
        $Order = $this->db->get()->row_array();
        //return $this->db->last_query();
        return encrypt_decrypt($Order, 1);
    }

    /**
     * 
     * @param type $UserID
     * @param type $PharmacyUserID
     * @param type $TPName
     * @param type $TPPhone
     * @param type $ImportAll
     * @param type $Medications
     * @return type
     */
    public function create_tx_order($UserID, $PharmacyUserID, $TPName, $TPPhone, $ImportAll, $Medications) {
        $this->load->model('medication_model');
        $Meds = [];
        if ($ImportAll == 1) {
            $Med = [
                'MedicationGUID' => guid(),
                'MedicationSID' => $this->medication_model->create_medication_sid(""),
                'UserID' => $UserID,
                'MedicationName' => '',
                'RefillAllowed' => '0',
                'VerifyStatus' => 'VERIFYING',
                'InProcess' => 1,
                'CreatedAt' => DATETIME,
            ];
            $Med = encrypt_decrypt($Med);
            $this->db->insert('Medications', $Med);
            $Meds[] = $this->db->insert_id();
        } else {
            foreach ($Medications as $Key => $Medication) {
                $MedicationName = safe_array_key($Medication, "MedicationName", "Unknown drug-$Key");
                $Med = [
                    'MedicationGUID' => guid(),
                    'MedicationSID' => $this->medication_model->create_medication_sid($MedicationName),
                    'UserID' => $UserID,
                    'MedicationName' => $MedicationName,
                    'RefillAllowed' => '0',
                    'VerifyStatus' => 'VERIFYING',
                    'InProcess' => 1,
                    'CreatedAt' => DATETIME,
                ];
                $Med = encrypt_decrypt($Med);
                $this->db->insert('Medications', $Med);
                $Meds[] = $this->db->insert_id();
            }
        }
        $Order = [
            'OrderGUID' => guid(),
            'OrderSID' => $this->medication_model->create_order_sid("TX"),
            'UserID' => $UserID,
            'PharmacyUserID' => $PharmacyUserID,
            'OrderType' => 'TRANSFER_ORDER',
            'ImportAll' => $ImportAll,
            'TPName' => $TPName,
            'TPPhoneNumber' => $TPPhone,
            'Status' => 'PLACED',
            'CreatedAt' => DATETIME,
            'PlacedAt' => DATETIME,
            'UpdatedAt' => DATETIME,
            'UpdatedBy' => $UserID,
        ];
        $this->db->insert('Orders', $Order);
        $OrderID = $this->db->insert_id();
        if (!empty($Meds)) {
            foreach ($Meds as $MedicationID) {
                $MedicationDetail = $this->medication_model->get_medication_by_id($MedicationID, TRUE);
                $OrderMedication = [
                    'OrderMedicationGUID' => guid(),
                    'OrderID' => $OrderID,
                    'UserID' => $MedicationDetail['UserID'],
                    'MedicationID' => $MedicationDetail['MedicationID'],
                ];
                $this->db->insert('OrderMedications', encrypt_decrypt($OrderMedication));
            }
        }

        $OrderGUID = $Order['OrderGUID'];
        //notification
        //$this->app->notify($UserID, $PharmacyUserID, 'TX_ORDER_PLACED', $OrderID);
        return $OrderGUID;
    }

    /**
     * create_qx_order
     * @param type $UserID
     * @param type $PharmacyUserID
     * @return type
     */
    public function create_qx_order($UserID, $PharmacyUserID, $Medications) {
        $this->load->model('medication_model');
        $OrderGUID = guid();
        $Order = [
            'OrderGUID' => $OrderGUID,
            'OrderSID' => $this->medication_model->create_order_sid("QX"),
            'PharmacyUserID' => $PharmacyUserID,
            'UserID' => $UserID,
            'OrderType' => 'QUOTE_ORDER',
            'Status' => 'PLACED',
            'CreatedAt' => DATETIME,
            'PlacedAt' => DATETIME,
            'UpdatedAt' => DATETIME,
            'UpdatedBy' => $UserID,
        ];
        $this->db->insert('Orders', $Order);
        $OrderID = $this->db->insert_id();
        foreach ($Medications as $Medication) {
            $MedicationGUID = safe_array_key($Medication, 'MedicationGUID', '');
            if (!empty($MedicationGUID)) {
                $Med = $this->app->get_row('Medications', 'MedicationID', ['MedicationGUID' => $MedicationGUID]);
                $MedicationID = $Med['MedicationID'];
                //update order medication
                $OMedication = [
                    'OrderMedicationGUID' => guid(),
                    'OrderID' => $OrderID,
                    'MedicationID' => $MedicationID,
                    'Price' => safe_array_key($Medication, 'Price', NULL),
                    'DispensingFee' => safe_array_key($Medication, 'DispensingFee', 0.0),
                    'AdditionalFee' => safe_array_key($Medication, 'AdditionalFee', 0.0),
                    'Discount' => safe_array_key($Medication, 'Discount', 0.0),
                    'IsTaxApplicable' => safe_array_key($Medication, 'IsTaxApplicable', 0),
                    'AmountDue' => safe_array_key($Medication, 'AmountDue', 0.0),
                ];
                $this->db->insert('OrderMedications', encrypt_decrypt($OMedication));
            } else {
                //insert
                //insert medication
                $M = [
                    'MedicationGUID' => guid(),
                    'MedicationSID' => $Medication['MedicationSID'],
                    'UserID' => $UserID,
                    'MedicationName' => $Medication['MedicationName'],
                    'RefillAllowed' => $Medication['RefillAllowed'],
                    'Strength' => safe_array_key($Medication, 'Strength', NULL),
                    'Quantity' => safe_array_key($Medication, 'Quantity', NULL),
                    'VerifyStatus' => 'VERIFYING',
                    'InProcess' => 1,
                    'CreatedAt' => DATETIME,
                    'UpdatedAt' => DATETIME,
                ];
                $this->db->insert('Medications', encrypt_decrypt($M));
                $MedicationID = $this->db->insert_id();
                //update order medication
                $OMedication = [
                    'OrderMedicationGUID' => guid(),
                    'OrderID' => $OrderID,
                    'MedicationID' => $MedicationID,
                    'Price' => safe_array_key($Medication, 'Price', NULL),
                    'DispensingFee' => safe_array_key($Medication, 'DispensingFee', 0.0),
                    'AdditionalFee' => safe_array_key($Medication, 'AdditionalFee', 0.0),
                    'Discount' => safe_array_key($Medication, 'Discount', 0.0),
                    'IsTaxApplicable' => safe_array_key($Medication, 'IsTaxApplicable', 0),
                    'AmountDue' => safe_array_key($Medication, 'AmountDue', 0.0),
                ];
                $this->db->insert('OrderMedications', encrypt_decrypt($OMedication));
            }
        }
        return $OrderGUID;
    }

}
