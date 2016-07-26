<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Description of insurancecard_mode
 *
 * @author nitins
 */
class Sti_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    public function sti($UserID) {
        $this->db->select('UserStiCardGUID, IsAuto, Carrier, Group, STI');
        $this->db->where('UserID', $UserID);
        $this->db->from('UserStiCards');
        $Query = $this->db->get();
        $Sti = $Query->row_array();
        if (!empty($Sti)) {
            return $Sti;
        } else {
            return "";
        }
    }

    /**
     * create_auto_sti
     * @param type $UserID
     * @param type $FirstName
     * @param type $LastName
     * @param type $AddressLine1
     * @param type $City
     * @param type $Province
     * @param type $PostalCode
     * @param type $Gender
     * @param type $DateOfBirth
     * @return type
     */
    public function create_auto_sti($UserID, $FirstName, $LastName, $AddressLine1, $City, $Province, $PostalCode, $Gender, $DateOfBirth) {
        if (ENVIRONMENT == 'production') {
            $Url = "https://poprxapi:hxbyb5jc8egbkyz@webapp.smartsti.com/api/fulfillment";
            $ProgramID = "9342";
        } else {
            $Url = "https://poprxapi:hxbyb5jc8egbkyz@staging.smartsti.com/api/fulfillment";
            $ProgramID = "6951";
        }
        $PostData = [
            'programId' => $ProgramID,
            'recipient' => [
                'recipientType' => "CUSTOMER",
                'firstName' => $FirstName,
                'lastName' => $LastName,
                'address1' => $AddressLine1,
                'city' => $City,
                'province' => $Province,
                'postalCode' => $PostalCode,
                'language' => "E",
                'gender' => $Gender,
                'dateOfBirth' => $DateOfBirth,
            ],
        ];
        $Res = $this->post_using_curl($Url, $PostData);
        if ($Res['StatusCode'] == "200") {
            $Response = json_decode($Res['Content'], 1);
            $Cards = safe_array_key($Response, 'cards', array());
            $Card = safe_array_key($Cards, 'card', array());
            $UserStiCard = [
                "UserStiCardGUID" => guid(),
                "UserID" => $UserID,
                "IsAuto" => 1,
                "Carrier" => $Card['carrier'],
                "Group" => $Card['group'],
                "STI" => $Card['uci'],
            ];
            $this->db->insert('UserStiCards', $UserStiCard);
            return $UserStiCard['UserStiCardGUID'];
        } else {
            return $Res;
        }
    }

    function post_using_curl($Url, $PostData) {
        $ch = curl_init();
        $username = "poprxapi";
        $password = "hxbyb5jc8egbkyz";
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
        ));
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_PROXY, '');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($PostData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'StatusCode' => $httpcode,
            'Content' => $content,
        ];
    }

    /**
     * create_sti
     * @param type $UserID
     * @param type $Carrier
     * @param type $Group
     * @param type $STI
     * @return type
     */
    public function create_sti($UserID, $Carrier, $Group, $STI) {
        $UserStiCard = [
            "UserStiCardGUID" => guid(),
            "UserID" => $UserID,
            "IsAuto" => 0,
            "Carrier" => $Carrier,
            "Group" => $Group,
            "STI" => $STI,
        ];
        $this->db->insert('UserStiCards', $UserStiCard);
        return $UserStiCard['UserStiCardGUID'];
    }

    /**
     * update_sti
     * @param type $UserStiCardGUID
     * @param type $Carrier
     * @param type $Group
     * @param type $STI
     */
    public function update_sti($UserStiCardGUID, $Carrier, $Group, $STI) {
        $UserStiCard = [
            "Carrier" => $Carrier,
            "Group" => $Group,
            "STI" => $STI,
        ];
        $this->db->update('UserStiCards', $UserStiCard, array(
            'UserStiCardGUID' => $UserStiCardGUID,
            'IsAuto' => 0,
        ));
    }

    /**
     * get_sti_by_id
     * @param type $UserStiCardID
     * @return type
     */
    public function get_sti_by_id($UserStiCardID) {
        $this->db->select('UserStiCardGUID, IsAuto, Carrier, Group, STI');
        $this->db->where('UserStiCardID', $UserStiCardID);
        $this->db->from('UserStiCards');
        $Query = $this->db->get();
        return $Query->row_array();
    }

    /**
     * get_sti_by_guid
     * @param type $UserStiCardGUID
     * @return type
     */
    public function get_sti_by_guid($UserStiCardGUID) {
        $this->db->select('UserStiCardGUID, IsAuto, Carrier, Group, STI');
        $this->db->where('UserStiCardGUID', $UserStiCardGUID);
        $this->db->from('UserStiCards');
        $Query = $this->db->get();
        return $Query->row_array();
    }

}
