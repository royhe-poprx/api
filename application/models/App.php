<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/*
  APP Model
 */

class App extends CI_Model {

    var $SourceTypes = array();
    var $DeviceTypes = array();
    var $UserTypes = array();

    public function __construct() {
        // Call the CI_Model constructor
        parent::__construct();
        $this->SourceTypes = [
            "1" => "Native",
            "2" => "Facebook",
            "3" => "Twitter",
            "4" => "Google",
        ];

        $this->DeviceTypes = [
            "1" => "Native",
            "2" => "IPhone",
            "3" => "AndroidPhone",
            "4" => "Ipad",
            "5" => "AndroidTablet",
            "6" => "OtherMobileDevice",
        ];

        $this->UserTypes = [
            "1" => "Admin",
            "2" => "Patient",
            "3" => "Pharmacy",
            "4" => "PharmacyGroupAdmin",
            "5" => "Agent",
        ];
    }

    function save_cache_data($UserID, $CacheTokenGUID) {
        $Token = $this->app->get_row('Tokens', 'TempJsonData', [
            'TokenGUID' => $CacheTokenGUID,
        ]);

        $TempJsonDataJson = safe_array_key($Token, 'TempJsonData', "");
        if ($TempJsonDataJson) {
            $TempJsonData = json_decode($Token['TempJsonData'], TRUE);
            $Medications = safe_array_key($TempJsonData, 'Medications', []);
            if (!empty($Medications)) {
                $this->load->model('medication_model');
                foreach ($Medications as $Medication) {
                    // simple add to medication
                    $IsNew = 0;
                    $MedicationName = safe_array_key($Medication, 'MedicationName', NULL);
                    $MedicationIcon = safe_array_key($Medication, 'MedicationIcon', NULL);
                    $Dosage = safe_array_key($Medication, 'Dosage', NULL);
                    $Images = safe_array_key($Medication, 'Images', NULL);
                    $MedicationID = $this->medication_model->create_medication($UserID, $IsNew, $MedicationName, $MedicationIcon, $Dosage, $Images);
                }
            }
        }
        $this->db->update('Tokens', [
            'TempJsonData' => NULL
                ], [
            'TokenGUID' => $CacheTokenGUID,
        ]);
    }

    function get_profile_by_user_id($UserID, $Decrepted = TRUE) {
        $this->db->select('U.Email, U.FirstName, U.LastName');
        $this->db->select('U.UserGUID', FALSE);
        $this->db->select('IFNULL(U.DOB,"") AS DOB', FALSE);
        $this->db->select('IFNULL(U.PhoneNumber,"") AS PhoneNumber', FALSE);
        $this->db->select('IFNULL(U.Gender,"") AS Gender', FALSE);
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.PhinNumber,"") AS PhinNumber', FALSE);
        $this->db->from('Users AS U');
        $this->db->where('U.UserID', $UserID);
        $Profile = $this->db->get()->row_array();
        if ($Decrepted) {
            $Profile = encrypt_decrypt($Profile, 1);
        }
        if (!empty($Profile)) {
            $this->load->model('promo_model');
            $Profile["ReferralCode"] = $this->promo_model->get_referral_code_by_user_id($UserID);
            $Profile['IsOwner'] = "1";
            $Profile['ProfileManagerGUID'] = $Profile['UserGUID'];
            if ($Profile['Email'] == "") {
                //get owner email id
                $Owner = $this->app->get_row('UserDependents', 'UserID', [
                    "DependentUserID" => $UserID,
                ]);
                $OwnerUser = $this->app->get_row('Users', 'UserGUID, FirstName, LastName, Email', [
                    "UserID" => $Owner['UserID'],
                ]);
                $Profile['Email'] = $OwnerUser['Email'];
                $Profile['IsOwner'] = "0";
                $Profile['ProfileManagerGUID'] = $OwnerUser['UserGUID'];
            }
        }
        return $Profile;
    }

    function get_pharmacy_by_user_id($UserID) {
        $this->db->select('U.Email, U.FirstName, U.LastName');
        $this->db->select('P.PharmacyGUID, P.CompanyName, P.PharmacyName, P.PhoneNumber, '
                . 'P.FaxNumber, P.Website, P.PharmacyLicense, P.PharmacyLicenseExp, '
                . 'P.Latitude, P.Longitude, P.AddressLine1, P.AddressLine2, P.City, P.State, '
                . 'P.Country, P.PostalCode, P.GeoSettingType, P.PharmacyAdminID, '
                . 'P.ShowInsuranceCard, P.ShowSTI, P.ShowAllergy, P.ShowMedReview');
        $this->db->select('U.UserGUID AS ProfileGUID', FALSE);
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.ReferralCode,"") AS ReferralCode', FALSE);
        $this->db->select('IFNULL(U.AboutMe,"") AS AboutMe', FALSE);
        $this->db->select('IFNULL(U.ChatMessage,"") AS ChatMessage', FALSE);

        $this->db->join('Pharmacies AS P', 'P.UserID=U.UserID');

        $this->db->from('Users AS U');
        $this->db->where('U.UserID', $UserID);
        $Profile = $this->db->get()->row_array();
        $Profile = encrypt_decrypt($Profile, 1);

        $this->load->model('promo_model');
        if (!empty($Profile)) {
            $Profile["ReferralCode"] = $this->promo_model->get_referral_code_by_user_id($UserID);
            if (!empty($Profile['PharmacyAdminID'])) {
                $PharmacyAdmin = $this->app->get_profile_by_user_id($Profile['PharmacyAdminID']);
                $Profile["PharmacyAdminGUID"] = $PharmacyAdmin['UserGUID'];
            } else {
                $Profile["PharmacyAdminGUID"] = "";
            }
            unset($Profile['PharmacyAdminID']);
        }
        return $Profile;
    }

    /**
     * 
     * @param type $table
     * @param type $fields
     * @param type $where
     * @return type
     */
    public function get_rows($table, $fields = "*", $where = array()) {
        $where = encrypt_decrypt($where);
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->where($where);
        $query = $this->db->get();
        $records = $query->result_array();
        return encrypt_decrypt($records, 1);
    }

    /**
     * 
     * @param type $table
     * @param type $fields
     * @param type $where
     * @return type
     */
    public function get_row($table, $fields = "*", $where = array()) {
        $where = encrypt_decrypt($where);
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->where($where);
        $query = $this->db->get();
        $record = $query->row_array();
        return encrypt_decrypt($record, 1);
    }

    /**
     * 
     * @param type $LoginSessionKey
     * @return type
     */
    public function user_data($LoginSessionKey, $Child = FALSE) {
        $this->db->select('AL.LoginSessionKey');
        $this->db->select('U.UserID, U.UserTypeID, U.Email, U.ForceChangePassword, U.ProfileCompletedPercent, U.IsProfileCompleted');

        if ($Child) {
            $this->db->select('CU.UserGUID, CU.FirstName, CU.LastName, CU.PhoneNumber, CU.Gender, CU.PhinNumber, CU.DOB, "0" AS IsOwner');
            $this->db->select('IFNULL(CU.ProfilePicture,"") AS ProfilePicture', FALSE);
            $this->db->select('IFNULL(CU.PharmacistID,"") AS PharmacistID', FALSE);
        } else {
            $this->db->select('U.UserGUID, U.FirstName, U.LastName, U.PhoneNumber, U.Gender, U.PhinNumber, U.DOB, "1" AS IsOwner');
            $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
            $this->db->select('IFNULL(CU.PharmacistID,"") AS PharmacistID', FALSE);
        }

        $this->db->select('IFNULL(U.LastLoginDate,"") AS LastLoginDate', FALSE);
        $this->db->select('GROUP_CONCAT(UL.SourceID) AS ConnectSources', FALSE);

        $this->db->select('IFNULL(W.Amount,"0.0") AS WalletAmount', FALSE);

        $this->db->from('ActiveLogins AS AL');
        $this->db->join('Users AS U', 'AL.UserID = U.UserID');
        $this->db->join('Users AS CU', 'AL.ActiveUserID = CU.UserID');
        $this->db->join('UserLogins AS UL', 'UL.UserID = U.UserID');
        $this->db->join('Wallet AS W', 'W.UserID = U.UserID', 'left');
        $this->db->where('AL.LoginSessionKey', $LoginSessionKey);
        $this->db->group_by('U.UserID');
        $Query = $this->db->get();
        $User = $Query->row_array();

        //$User['ConnectSources'] = $this->app->map_source_id_to_str($User['ConnectSources']);
        $User = encrypt_decrypt($User, 1);
        $this->load->model('promo_model');
        $User["ReferralCode"] = "";
        unset($User['UserID']);
        return $User;
    }

    /**
     * 
     * @param type $LoginSessionKey
     * @return type
     */
    public function pharmacy_user_data($LoginSessionKey) {
        $this->db->select('AL.LoginSessionKey');
        $this->db->select('U.UserTypeID, U.Email, U.ForceChangePassword, U.ProfileCompletedPercent, U.IsProfileCompleted');

        $this->db->select('U.UserGUID, U.FirstName, U.LastName, U.PhoneNumber, U.Gender, U.PhinNumber, U.DOB');
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);

        $this->db->select('IFNULL(U.LastLoginDate,"") AS LastLoginDate', FALSE);
        $this->db->select('GROUP_CONCAT(UL.SourceID) AS ConnectSources', FALSE);

        $this->db->select('IFNULL(W.Amount,"0.0") AS WalletAmount', FALSE);
        $this->db->select('IFNULL(U.ReferralCode,"") AS ReferralCode', FALSE);

        $this->db->select('IFNULL(P.TaxPercentage,"0.0") AS TaxPercentage', FALSE);
        $this->db->select('IFNULL(P.DispensingFee,"0.0") AS DispensingFee', FALSE);
        $this->db->select('IFNULL(P.PharmacyName,"0.0") AS PharmacyName', FALSE);



        $this->db->from('ActiveLogins AS AL');
        $this->db->join('Users AS U', 'AL.UserID = U.UserID');
        $this->db->join('Pharmacies AS P', 'AL.UserID = P.UserID', 'left');
        $this->db->join('UserLogins AS UL', 'UL.UserID = U.UserID');
        $this->db->join('Wallet AS W', 'W.UserID = U.UserID', 'left');
        $this->db->where('AL.LoginSessionKey', $LoginSessionKey);
        $this->db->group_by('U.UserID');
        $Query = $this->db->get();
        $User = $Query->row_array();

        //$User['ConnectSources'] = $this->app->map_source_id_to_str($User['ConnectSources']);
        if (!empty($User)) {
            $User['Roles'][] = $this->UserTypes[$User['UserTypeID']];
        }
        return encrypt_decrypt($User, 1);
    }

    function map_source_id_to_str($ConnectSources) {
        $ConnectSourcesArray = explode(",", $ConnectSources);
        $ConnectSourcesOut = [];
        foreach ($ConnectSourcesArray as $ConnectSource) {
            $ConnectSourcesOut[] = [
                "SourceID" => $ConnectSource,
                "SourceTypes" => $this->SourceTypes[$ConnectSource],
            ];
        }
        return $ConnectSourcesOut;
    }

    public function get_device_list($UserID) {
        //check if dependent user
        $User = $this->get_row('Users', 'Email', [
            "UserID" => $UserID,
        ]);
        if (empty($User['Email'])) {
            $ParentProfile = $this->get_row('UserDependents', 'UserID', [
                "DependentUserID" => $UserID,
            ]);
            $UserID = (!empty($ParentProfile['UserID'])) ? $ParentProfile['UserID'] : $UserID;
        }

        $Result = array();

        $this->db->select('AL.DeviceTypeID, AL.DeviceID');
        $this->db->where('UserID', $UserID);
        $this->db->group_by('AL.DeviceID');

        $Query = $this->db->get('ActiveLogins AS AL');
        $Result = $Query->result_array();
        return $Result;
    }

    public function send_push_notification($UserID, $Message, $Extra = array()) {
        $Devices = $this->get_device_list($UserID);
        //$Badge = $this->notification_model->getNotificationCount(0,$UserID);
        //$Badge = $this->getNotificationCount(0, $UserID);
        $Badge = 1;

        $User = $this->get_row('Users', 'UserGUID', [
            "UserID" => $UserID,
        ]);

        //push_notification_web($User['UserGUID'], $Message, $Badge, $Extra);
        if (!empty($Devices)) {

            foreach ($Devices as $Device) {
                if ($Device['DeviceID'] != '' && $Device['DeviceID'] != "1") {
                    $DeviceTypeID = $Device['DeviceTypeID'];
                    $DeviceToken = $Device['DeviceID'];
                    $Badge = $Badge;
                    switch ($DeviceTypeID) {
                        case '2':
                            push_notification_iphone($DeviceToken, $Message, $Badge, $Extra);
                            break;
                        case '3':
                            push_notification_android($DeviceToken, $Message, $Badge, $Extra);
                            break;
                    }
                }
            }
        }
    }

    function web_notification($Url, $Data) {
        $this->load->library('curl');
        $this->curl->_simple_call('post', NODE_URL . '/' . $Url, $Data);
    }

    /**
     * 
     * @param type $FromUserID
     * @param type $ToUserID
     * @param type $Type
     * @param type $TypeID
     * @param type $Text
     */
    public function create_notification($FromUserID, $ToUserID, $Type, $TypeID, $Text) {
        $Notification = [
            'NotificationGUID' => guid(),
            'UserID' => $FromUserID,
            'ToUserID' => $ToUserID,
            'Type' => $Type,
            'TypeID' => $TypeID,
            'Text' => $Text,
            'CreatedAt' => DATETIME,
        ];
        $this->db->insert('Notifications', $Notification);
    }

    /**
     * 
     * @param type $UserID
     * @param type $ListType
     * @param type $OnlyNumRows
     * @param type $Limit
     * @param type $Offset
     * @return type
     */
    public function notifications($UserID, $ListType = "FRESH", $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL) {
        if (is_null($OnlyNumRows)) {
            $this->db->select('N.NotificationGUID, N.Status, N.TypeID, N.Type');
            $this->db->select('IFNULL(N.Text,"") Text', FALSE);
        } else {
            $this->db->select('N.NotificationGUID');
        }

        $this->db->group_start();
        $this->db->where('N.ToUserID = "' . $UserID . '" OR N.ToUserID IN ( SELECT DependentUserID FROM UserDependents WHERE IsActive=1 AND UserID = "' . $UserID . '" )', NULL, FALSE);
        $this->db->group_end();

        if ($ListType == "FRESH") {
            $this->db->where_in('N.Status', ['FRESH']);
        } elseif ($ListType == "SEEN") {
            $this->db->where_in('N.Status', ['SEEN']);
        } elseif ($ListType == "READ") {
            $this->db->where_in('N.Status', ['READ']);
        }

        $this->db->order_by('N.CreatedAt', 'DESC');
        $this->db->from('Notifications' . ' AS N');

        if (is_null($OnlyNumRows)) {
            $this->db->limit($Limit, $Offset);
            $Query = $this->db->get();
            $Notifications = $Query->result_array();
            foreach ($Notifications as $Key => $Notification) {
                if ($Notification['Type'] == 'NEW_IMAGE_REQUEST') {
                    $Order = $this->app->get_row('Medications', 'MedicationGUID AS OrderGUID', [
                        'MedicationID' => $Notification['TypeID'],
                    ]);
                } else {
                    $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType, Status', [
                        'OrderID' => $Notification['TypeID'],
                    ]);
                }
                unset($Notifications[$Key]['TypeID']);
                $Notifications[$Key]['Order'] = $Order;
            }
            return encrypt_decrypt($Notifications, 1);
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    /**
     * 
     * @param type $FromUserID
     * @param type $ToUserID
     * @param type $Type
     * @param type $TypeID
     */
    function notify($FromUserID, $ToUserID, $Type, $TypeID) {
        $Variables = [
            'FromUserID' => $FromUserID,
            'ToUserID' => $ToUserID,
            'Type' => $Type,
            'TypeID' => $TypeID,
        ];
        process_in_backgroud("NotificationToClients", $Variables);
    }

    /**
     * 
     * @param type $FromUserID
     * @param type $ToUserID
     * @param type $Type
     * @param type $TypeID
     */
    function cron_notify($FromUserID, $ToUserID, $Type, $TypeID) {
        switch ($Type) {
            case 'NEW_IMAGE_REQUEST':
                $Medication = $this->app->get_row('Medications', 'MedicationGUID, MedicationSID', [
                    'MedicationID' => $TypeID,
                ]);
                $Message = "Pharmacy has requested you to upload new images.";
                $Extra = [
                    "Title" => "New Image Request",
                    "Type" => "Order",
                    "Action" => "NewImageRequestReceived",
                    "Medication" => $Medication,
                ];
                $Text = "Pharmacy has requested you to upload new images.";
                $this->app->create_notification($FromUserID, $ToUserID, 'NEW_IMAGE_REQUEST', $TypeID, $Text);
                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                break;

            case 'CHAT_SEND':
                $Chat = $this->app->get_row('Chats', 'ChatGUID, Text, SenderID, ReceiverID', [
                    'ChatID' => $TypeID,
                ]);
                $Chat['Sender'] = $this->app->get_row('Users', 'UserGUID, FirstName, LastName, ProfilePicture', [
                    'UserID' => $Chat['SenderID'],
                ]);
                unset($Chat['SenderID']);

                $Chat['Receiver'] = $this->app->get_row('Users', 'UserGUID, FirstName, LastName, ProfilePicture', [
                    'UserID' => $Chat['ReceiverID'],
                ]);
                unset($Chat['ReceiverID']);
                $Message = "New chat message received.";
                $Extra = [
                    "ToUserGUID" => $Chat['Receiver']['UserGUID'],
                    "Title" => "Chat",
                    "Type" => "Chat",
                    "Action" => "NewChatReceived",
                    "Chat" => $Chat,
                ];
                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                $this->app->web_notification('chat_update', $Extra);
                break;

            case 'TX_ORDER_PLACED':
                //$FromUserID is Patient
                //$ToUserID is Pharmacy
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "New transfer order " . substr($Order['OrderSID'], -5) . " Received";
                $ToUser = $this->get_row('Users', 'UserGUID, FirstName, Email', [
                    "UserID" => $ToUserID,
                ]);
                $Extra = [
                    "ToUserGUID" => $ToUser['UserGUID'],
                    "Title" => "Transfer Order",
                    "Type" => "Order",
                    "Action" => "NewOrderReceived",
                    "Order" => $Order,
                    "Message" => $Message,
                ];
                //$this->app->send_push_notification($ToUserID, $Message, $Extra);
                $this->app->web_notification('order_update', $Extra);
                $FromUser = $this->get_row('Users', 'UserGUID, FirstName, Email', [
                    "UserID" => $FromUserID,
                ]);
                $this->app->send_new_order_email_notification($FromUser, $ToUser, $Message, $Extra);

                break;

            case 'QX_ORDER_PLACED':
                //$FromUserID is Patient
                //$ToUserID is Pharmacy
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "New quote order " . substr($Order['OrderSID'], -5) . " Received";
                $ToUser = $this->get_row('Users', 'UserGUID, FirstName, Email', [
                    "UserID" => $ToUserID,
                ]);
                $Extra = [
                    "ToUserGUID" => $ToUser['UserGUID'],
                    "Title" => "Quote Order",
                    "Type" => "Order",
                    "Action" => "NewOrderReceived",
                    "Order" => $Order,
                    "Message" => $Message,
                ];
                //$this->app->send_push_notification($ToUserID, $Message, $Extra);
                $this->app->web_notification('order_update', $Extra);
                $FromUser = $this->get_row('Users', 'UserGUID, FirstName, Email', [
                    "UserID" => $FromUserID,
                ]);
                $this->app->send_new_order_email_notification($FromUser, $ToUser, $Message, $Extra);
                break;

            case 'DX_ORDER_PLACED':
                //$FromUserID is Patient
                //$ToUserID is Pharmacy
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "New delivery order " . substr($Order['OrderSID'], -5) . " Received";
                $ToUser = $this->get_row('Users', 'UserGUID, FirstName, Email', [
                    "UserID" => $ToUserID,
                ]);
                $Extra = [
                    "ToUserGUID" => $ToUser['UserGUID'],
                    "Title" => "Delivery Order",
                    "Type" => "Order",
                    "Action" => "NewOrderReceived",
                    "Order" => $Order,
                    "Message" => $Message,
                ];
                //$this->app->send_push_notification($ToUserID, $Message, $Extra);
                $this->app->web_notification('order_update', $Extra);
                $FromUser = $this->get_row('Users', 'UserGUID, FirstName, Email', [
                    "UserID" => $FromUserID,
                ]);
                $this->app->send_new_order_email_notification($FromUser, $ToUser, $Message, $Extra);
                break;

            case 'ORDER_REJECTED':

                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "Order " . substr($Order['OrderSID'], -5) . " has been rejected";
                $Extra = [
                    "Title" => "Order",
                    "Type" => "Order",
                    "Action" => "OrderRejected",
                    "Order" => $Order,
                ];
                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                break;

            case 'ORDER_CANCELLED':

                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "Order " . substr($Order['OrderSID'], -5) . " has been cancelled";
                $ToUser = $this->get_row('Users', 'UserGUID, FirstName, Email', [
                    "UserID" => $ToUserID,
                ]);
                $Extra = [
                    "ToUserGUID" => $ToUser['UserGUID'],
                    "Title" => "Order",
                    "Type" => "Order",
                    "Action" => "OrderCancelled",
                    "Order" => $Order,
                    "Message" => $Message,
                ];
                //$this->app->send_push_notification($ToUserID, $Message, $Extra);
                $this->app->web_notification('order_update', $Extra);
                break;

            case 'TX_ORDER_COMPLETED':
                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "Transfer complete. Want to get a find out how much you would save with PopRx? Click here.";
                $Extra = [
                    "Title" => "Transfer Order",
                    "Type" => "Order",
                    "Action" => "OrderCompleted",
                    "Order" => $Order,
                ];
                $Text = "Your transfer " . substr($Order['OrderSID'], -5) . " is complete. Save money. Click here to review and Get Quote.";
                $this->app->create_notification($FromUserID, $ToUserID, 'TX_ORDER_COMPLETED', $TypeID, $Text);

                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                break;


            case 'QX_ORDER_COMPLETED':
                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "Order " . substr($Order['OrderSID'], -5) . " has been Completed";
                $Extra = [
                    "Title" => "Quote Order",
                    "Type" => "Order",
                    "Action" => "OrderCompleted",
                    "Order" => $Order,
                ];
                $Text = "Your quote " . substr($Order['OrderSID'], -5) . " is complete. Get free same day delivery. Click here to review and Place Order.";
                $this->app->create_notification($FromUserID, $ToUserID, 'TX_ORDER_COMPLETED', $TypeID, $Text);

                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                break;


            case 'DX_ORDER_COMPLETED':
                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "Order " . substr($Order['OrderSID'], -5) . " has been Completed";
                $Extra = [
                    "Title" => "Delivery Order",
                    "Type" => "Order",
                    "Action" => "OrderCompleted",
                    "Order" => $Order,
                ];

                //$Text = "Your de " . $Order['OrderSID'] . " is completed. Click here to review and get quote by pharmacy";
                //$this->app->create_notification($FromUserID, $ToUserID, 'TX_ORDER_COMPLETED', $TypeID, $Text);

                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                break;

            case 'ORDER_COMPLETED':
                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "Order " . substr($Order['OrderSID'], -5) . " has been Completed";
                $Extra = [
                    "Title" => "Order",
                    "Type" => "Order",
                    "Action" => "OrderCompleted",
                    "Order" => $Order,
                ];
                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                break;

            case 'DX_ORDER_RECEIVED':
                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType, RefOrderID', [
                    'OrderID' => $TypeID,
                ]);

                $QOrder = $this->app->get_row('Orders', 'OrderSID', [
                    'OrderID' => $Order['RefOrderID'],
                ]);

                unset($Order['RefOrderID']);

                $Message = "Order " . substr($Order['OrderSID'], -5) . " has been Created";
                $ToUser = $this->get_row('Users', 'UserGUID, FirstName, Email', [
                    "UserID" => $ToUserID,
                ]);
                $Extra = [
                    "ToUserGUID" => $ToUser['UserGUID'],
                    "Title" => "Quote Order",
                    "Type" => "Order",
                    "Action" => "DxOrderCreated",
                    "Order" => $Order,
                ];

                $Text = "Your quote " . substr($QOrder['OrderSID'], -5) . " is complete. Click here to review and Place Order to get free same day delivery";
                $this->app->create_notification($FromUserID, $ToUserID, 'DX_ORDER_RECEIVED', $TypeID, $Text);

                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                $this->app->web_notification('order_update', $Extra);
                break;

            case 'DX_ORDER_PACKED':
                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "Order " . substr($Order['OrderSID'], -5) . " has been Packed";
                $Extra = [
                    "Title" => "Delivery Order",
                    "Type" => "Order",
                    "Action" => "DxOrderPacked",
                    "Order" => $Order,
                ];
                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                break;

            case 'DX_ORDER_ONROUTE':
                //$FromUserID is Pharmacy
                //$ToUserID is Patient
                $Order = $this->app->get_row('Orders', 'OrderGUID, OrderSID, OrderType', [
                    'OrderID' => $TypeID,
                ]);
                $Message = "Order " . substr($Order['OrderSID'], -5) . " is on route";
                $Extra = [
                    "Title" => "Delivery Order",
                    "Type" => "Order",
                    "Action" => "DxOrderOnRoute",
                    "Order" => $Order,
                ];
                $this->app->send_push_notification($ToUserID, $Message, $Extra);
                break;
        }
    }

    public function get_profile_by_id($UserID) {
        $User = $this->get_row('Users', 'UserGUID, FirstName, LastName, Gender, PhoneNumber, ProfilePicture', [
            "UserID" => $UserID,
        ]);
        return $User;
    }

    public function get_pharmacy_id_by_user_id($UserID) {
        $User = $this->get_row('Pharmacies', 'PharmacyID', [
            "UserID" => $UserID,
        ]);
        return $User['PharmacyID'];
    }

    function cron_passwrod_recovery_email($Email, $FirstName, $LastName, $TmpPass) {
        $this->load->library('email');
        $this->email->from('info@poprx.ca', 'PopRx Team', 'info@poprx.ca');
        $this->email->to($Email);
        $this->email->subject(SITE_NAME . ' Password Recovery');
        $Data = [
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'TmpPass' => $TmpPass,
        ];
        $Message = $this->load->view('emailers/recovery-password', $Data, TRUE);
        $this->email->message($Message);
        $this->email->send();
    }

    function cron_new_account_created_email($Email, $FirstName, $LastName, $TmpPass) {
        $this->load->library('email');
        $this->email->from('info@poprx.ca', 'PopRx Team', 'info@poprx.ca');
        $this->email->to($Email);
        $this->email->subject('Your Account At ' . SITE_NAME);
        $Data = [
            'Email' => $Email,
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'TmpPass' => $TmpPass,
        ];
        $Message = $this->load->view('emailers/new-account-created', $Data, TRUE);
        $this->email->message($Message);
        $this->email->send();
    }

    function delete_user($UserID) {
        $DependentUsers = $this->app->get_rows('UserDependents', 'DependentUserID', ["UserID" => $UserID]);
        foreach ($DependentUsers as $DependentUser) {
            $this->delete_user($DependentUser['DependentUserID']);
        }
        $this->db->delete('Users', ["UserID" => $UserID]);
        $this->db->delete('InsuranceCards', ["UserID" => $UserID]);
    }

    function send_new_order_email_notification($FromUser, $ToUser, $Message, $Extra) {
        $Data = [
            'ToUser' => $ToUser,
            'FromUser' => $FromUser,
            'Message' => $Message,
            'Extra' => $Extra,
        ];
        $this->load->library('email');
        $this->email->from('info@poprx.ca', 'PopRx Team', 'info@poprx.ca');
        $this->email->subject(SITE_NAME . ' New Order Received on Poprx 2.0');
        $this->email->to($ToUser['Email']);
        $this->email->cc('adam@poprx.ca');
        //$this->email->cc('pradeep@poprx.ca');
        $MessageBody = $this->load->view('emailers/new-order', $Data, TRUE);
        $this->email->message($MessageBody);
        $this->email->send();
    }

    public function delete_user_by_guid($UserGUID) {
        $User = $this->app->get_row('Users', 'UserID', ['UserGUID' => $UserGUID]);
        $this->app->delete_user($User['UserID']);
        return true;
    }

}
