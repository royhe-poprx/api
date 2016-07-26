<?php

/**
 * Order_model will use to manage all order related db stuffs
 *
 * @author nitins
 */
class Address_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 
     * @param type $UserID
     * @param type $FormattedAddress
     * @param type $Latitude
     * @param type $Longitude
     * @param type $StreetNumber
     * @param type $Route
     * @param type $City
     * @param type $State
     * @param type $Country
     * @param type $PostalCode
     * @return boolean
     */
    public function create_address($UserID, $AddressType, $FormattedAddress, $Latitude, $Longitude, 
            $StreetNumber, $Route, $City, $State, $Country, $PostalCode) {       
        $Address = [
            "UserAddressGUID"=>guid(),
            "UserID"=>$UserID,
            "AddressType"=>!empty($AddressType)?$AddressType:NULL,
            "FormattedAddress"=>$FormattedAddress,
            "Latitude"=>$Latitude,
            "Longitude"=>$Longitude,
            "StreetNumber"=>$StreetNumber,
            "Route"=>$Route,                
            "City"=>$City,                
            "State"=>$State,                
            "Country"=>$Country,                
            "PostalCode"=>$PostalCode,                
            "CreatedAt"=>DATETIME,                
        ];        
        $this->db->insert('UserAddresses', $Address);
        $AddressID = $this->db->insert_id();
        return $AddressID;
    }

    /**
     * 
     * @param type $UserAddressGUID
     * @param type $FormattedAddress
     * @param type $Latitude
     * @param type $Longitude
     * @param type $StreetNumber
     * @param type $Route
     * @param type $City
     * @param type $State
     * @param type $Country
     * @param type $PostalCode
     */
    public function update_address($UserAddressGUID, $AddressType, $FormattedAddress, $Latitude, $Longitude, 
            $StreetNumber, $Route, $City, $State, $Country, $PostalCode) {       
        $Address = [
            "AddressType"=>!empty($AddressType)?$AddressType:NULL,
            "FormattedAddress"=>$FormattedAddress,
            "Latitude"=>$Latitude,
            "Longitude"=>$Longitude,
            "StreetNumber"=>$StreetNumber,
            "Route"=>$Route,                
            "City"=>$City,                
            "State"=>$State,                
            "Country"=>$Country,                
            "PostalCode"=>$PostalCode,                
            "UpdatedAt"=>DATETIME,                
        ];        
        $this->db->update('UserAddresses', $Address, [
            'UserAddressGUID' => $UserAddressGUID 
        ]);
    }
    
    
    /**
     * 
     * @param type $UserAddressID
     * @return boolean
     */
    public function delete_address($UserAddressGUID) {
        $this->db->where('UserAddressGUID',$UserAddressGUID);
        $this->db->delete('UserAddresses');
        return TRUE;
    }
    
    
    /**
     * get_address_list
     * @param type $UserID
     * @param type $limit
     * @param type $offset
     * @return array
     */
    function addresses($UserID, $limit = NULL, $offset = 0) {        
        $this->db->select('UserAddressGUID, IFNULL(AddressType,"") AS AddressType, FormattedAddress, Latitude, Longitude, StreetNumber, Route, City, State, Country, PostalCode');        
        $this->db->where('UserID', $UserID);        
        if (!empty($limit) && (!empty($offset) || $offset == 0)) {
            $this->db->limit($limit, $offset);
        } 
        $query = $this->db->get('UserAddresses');
        $result = $query->result_array();
        return $result;
    }    
    
    /**
     * 
     * @param type $UserAddressID
     * @return type
     */
    function get_address_by_id($UserAddressID) {        
        $this->db->select('UserAddressGUID, IFNULL(AddressType,"") AS AddressType, FormattedAddress, Latitude, Longitude, StreetNumber, Route, City, State, Country, PostalCode');        
        $this->db->where('UserAddressID', $UserAddressID);        
        $query = $this->db->get('UserAddresses');
        $result = $query->row_array();
        return $result;
    }
    
    /**
     * 
     * @param type $UserAddressID
     * @return type
     */
    function get_address_by_guid($UserAddressGUID) {        
        $this->db->select('UserAddressGUID, IFNULL(AddressType,"") AS AddressType, FormattedAddress, Latitude, Longitude, StreetNumber, Route, City, State, Country, PostalCode');        
        $this->db->where('UserAddressGUID', $UserAddressGUID);        
        $query = $this->db->get('UserAddresses');
        $result = $query->row_array();
        return $result;
    }
}
