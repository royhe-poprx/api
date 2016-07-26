<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Description of prescription_model
 *
 * @author nitins
 */
class Promo_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 
     * @param type $OnlyNumRows
     * @param type $Limit
     * @param type $Offset
     * @return type
     */
    public function promos($OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL) {
        if (is_null($OnlyNumRows)) {
            $this->db->select('PromoID, Name, Description, PromoGUID, PromoType, Code, IFNULL(Amount, "") AS Amount, IFNULL(AssignTo, "") AS AssignTo, IFNULL(AssignToAmount, "") AS AssignToAmount,  IsActive, CreatedAt, UpdatedAt');
        } else {
            $this->db->select('PromoGUID');
        }

        $this->db->order_by('CreatedAt', 'DESC');
        $this->db->from('Promos');

        if (is_null($OnlyNumRows)) {
            if ($Limit != -1) {
                $this->db->limit($Limit, $Offset);
            }
            $Query = $this->db->get();
            $Promos = $Query->result_array();
            foreach ($Promos as $Key => $Promo) {
                if ($Promo['AssignTo'] != "") {
                    $Promos[$Key]['AssignTo'] = $this->app->get_profile_by_user_id($Promo['AssignTo']);
                }
                unset($Promos[$Key]['PromoID']);
            }
            return $Promos;
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    function get_history_by_promo_id($PromoID) {
        $PromoHistory = $this->app->get_rows('UserPromos', 'UserPromoGUID, UserID, IFNULL(ReferredFrom, "") AS ReferredFrom, Status, CreatedAt', [
            'PromoID' => $PromoID,
        ]);
        foreach ($PromoHistory as $Key => $Promo) {
            $PromoHistory[$Key]['User'] = $this->app->get_profile_by_user_id($Promo['UserID']);
            unset($PromoHistory[$Key]['UserID']);
        }
        return $PromoHistory;
    }

    /**
     * 
     * @param type $Name
     * @param type $Description
     * @param type $Code
     * @param type $PromoType
     * @param type $Ammout
     * @param type $AssignTo
     * @param type $AssignToAmount
     * @return type
     */
    public function create_promo($Name, $Description, $Code, $PromoType, $Ammout, $AssignTo, $AssignToAmount) {
        $Promo = [
            'PromoGUID' => guid(),
            'PromoType' => $PromoType,
            'Code' => $Code,
            'Amount' => $Ammout,
            'AssignTo' => $AssignTo,
            'AssignToAmount' => $AssignToAmount,
            'Name' => $Name,
            'Description' => $Description,
            'IsActive' => 1,
            'CreatedAt' => DATETIME,
            'UpdatedAt' => DATETIME,
        ];
        $this->db->insert('Promos', $Promo);
        return $this->db->insert_id();
    }

    /**
     * 
     * @param type $PromoGUID
     * @param type $Name
     * @param type $Description
     * @param type $Code
     * @param type $Ammout
     * @param type $AssignToAmmout
     * @return type
     */
    public function update_promo($PromoGUID, $Name, $Description, $Code, $Amount, $AssignToAmount) {
        $Promo = [
            'Code' => $Code,
            'Amount' => $Amount,
            'AssignToAmount' => $AssignToAmount,
            'Name' => $Name,
            'Description' => $Description,
            'UpdatedAt' => DATETIME,
        ];
        $this->db->update('Promos', $Promo, [
            'PromoGUID' => $PromoGUID
        ]);
        return $PromoGUID;
    }

    /**
     * 
     * @param type $PromoGUID
     * @return boolean
     */
    public function toggle_status($PromoGUID) {
        $this->db->set('IsActive', '!IsActive', FALSE);
        $this->db->where('PromoGUID', $PromoGUID);
        $this->db->update('Promos');
        return TRUE;
    }

    /**
     * 
     * @param type $PromoID
     * @return type
     */
    public function get_promo_by_id($PromoID) {
        $this->db->select('PromoGUID, Name, Description, PromoType, Code, Amount, AssignTo, AssignToAmount, IsActive, CreatedAt, UpdatedAt');
        $this->db->from('Promos');
        $this->db->where('PromoID', $PromoID);
        $Query = $this->db->get();

        $Promo = $Query->row_array();
        $Promo['AssignTo'] = $this->app->get_profile_by_user_id($Promo['AssignTo']);
        return $Promo;
    }

    /**
     * 
     * @param type $PromoGUID
     * @return type
     */
    public function get_promo_by_guid($PromoGUID) {
        $this->db->select('PromoGUID, Name, Description, PromoType, Code, Amount, AssignTo, AssignToAmount, IsActive, CreatedAt, UpdatedAt');
        $this->db->from('Promos');
        $this->db->where('PromoGUID', $PromoGUID);
        $Query = $this->db->get();

        $Promo = $Query->row_array();
        $Promo['AssignTo'] = $this->app->get_profile_by_user_id($Promo['AssignTo']);
        return $Promo;
    }

    /**
     * 
     * @param type $PromoGUID
     * @return boolean
     */
    public function delete_promo($PromoGUID) {
        $this->db->where('PromoGUID', $PromoGUID);
        $this->db->delete('Promos');
        return TRUE;
    }

    public function get_referral_code_by_user_id($UserID) {
        $UserReferralPromo = $this->app->get_row('Promos', 'Code', [
            "PromoType" => "REFERRAL",
            "AssignTo" => $UserID,
        ]);
        if (!empty($UserReferralPromo)) {
            $Code = $UserReferralPromo['Code'];
        } else {
            $User = $this->app->get_row('Users', 'FirstName, UserTypeID', [
                "UserID" => $UserID,
            ]);
            if (!empty($User)) {
                if ($User['UserTypeID'] == 3) {
                    $Pharmacy = $this->app->get_row('Pharmacies', 'PharmacyName', [
                        "UserID" => $UserID,
                    ]);
                    $FirstCode = strtoupper($Pharmacy['PharmacyName']);
                } else {
                    $FirstCode = strtoupper($User['FirstName']);
                }
                $FirstCode = preg_replace("/[^A-Z]+/", "", $FirstCode);
                if (empty($FirstCode)) {
                    $FirstCode = random_string('alpha', 5);
                    $FirstCode = strtoupper($FirstCode);
                    $FirstCode = preg_replace("/[^A-Z]+/", "", $FirstCode);
                }
                $Code = $this->recursive_get_referral_code($FirstCode);
                $this->create_promo("Auto Referral Code", "", $Code, "REFERRAL", 5, $UserID, 5);
            }
        }
        return $Code;
    }

    function recursive_get_referral_code($String) {
        $Promos = $this->app->get_rows('Promos', 'PromoID', [
            'Code' => $String,
            'PromoType' => 'REFERRAL'
        ]);
        if (!empty($Promos)) {
            $Code = random_string('nozero', 3);
            $String = $String . $Code;
            $String = $this->recursive_get_referral_code($String);
        }
        return $String;
    }

    /**
     * 
     * @param type $UserID
     * @return boolean
     */
    public function apply_promo($UserID, $ReferralCode, $ReferedFrom = NULL, $Status = "PENDING") {
        $Promo = $this->app->get_row('Promos', 'PromoID', [
            'Code' => $ReferralCode,
        ]);
        $this->db->insert('UserPromos', [
            'UserPromoGUID' => guid(),
            'UserID' => $UserID,
            'ReferredFrom' => $ReferedFrom,
            'PromoID' => $Promo['PromoID'],
            'CreatedAt' => DATETIME,
            'UpdatedAt' => DATETIME,
            'Status' => $Status,
        ]);
        return $this->db->insert_id();
    }

    public function update_referral_code($UserID, $ReferralCode) {
        $Promo = [
            'Code' => $ReferralCode,
            'UpdatedAt' => DATETIME,
        ];
        $this->db->update('Promos', $Promo, [
            'AssignTo' => $UserID,
            'PromoType' => 'REFERRAL'
        ]);
    }

}
