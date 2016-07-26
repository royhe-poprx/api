<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter 
 */
class Search_model extends CI_Model {

    public function __construct() {
        // Call the CI_Model constructor
        parent::__construct();
    }

    function find_pharmacy_by_zipcode_or_name($Keyword) {
        $this->db->select('P.PharmacyName,P.PhoneNumber,P.CompanyName,U.AptAddress');
        $this->db->select('P.PharmacyID, P.LocationID, U.Email, L.FormattedAddress, L.Latitude, L.Longitude, L.StreetNumber, L.Route, L.City, L.State, L.Country, L.PostalCode,P.UserID');
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select("U.FirstName,  U.LastName", FALSE);
        $this->db->join('Users U', 'U.UserID=P.UserID');
        $this->db->join('Locations L', 'P.LocationID=L.LocationID');
        //$this->db->where('P.PharmacyID', '4');        
        $query = $this->db->get('Pharmacies P');
        //echo $this->db->last_query();die;
        return $query->result_array();
    }

    function find_pharmacy($Latitude, $Longitude) {
        $this->db->select('U.UserID, P.PharmacyID');
        $this->db->select('U.UserGUID AS PharmacyGUID', FALSE);
        $this->db->join('Users U', 'U.UserID=P.UserID');
        $this->db->where('P.GeoSettingType', 'GEO_REFERRAL');
        $this->db->where("P.PharmacyID IN(SELECT PharmacyID from PharmacyWorkArea WHERE ST_WITHIN(GeomFromText('POINT($Latitude $Longitude)'), `AreaStrGeo`) = 1)", NULL, true);
        $query = $this->db->get('Pharmacies P');
        //echo $this->db->last_query();
        return $query->row_array();
    }

    /**
     * Search For valid pharmacy
     * @return int
     */
    function get_pharmacy_by_id($PharmacyID) {
        $this->db->select('P.PharmacyName,P.PhoneNumber,P.CompanyName,U.AptAddress');
        $this->db->select('P.PharmacyID, P.LocationID, U.Email, L.FormattedAddress, L.Latitude, L.Longitude, L.StreetNumber, L.Route, L.City, L.State, L.Country, L.PostalCode,P.UserID');
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select("U.Firstname,  U.Lastname", FALSE);
        $this->db->join('Users U', 'U.UserID=P.UserID');
        $this->db->join('Locations L', 'P.LocationID=L.LocationID');
        //$this->db->where('P.PharmacyID', '4');
        $this->db->where("P.PharmacyID", $PharmacyID);
        $query = $this->db->get('Pharmacies P');
        //echo $this->db->last_query();die;
        return $query->row_array();
    }

    /**
     * Search For valid pharmacy
     * @return int
     */
    function find_pharmacy_by_lat_lng($Latitude, $Longitude) {
        $this->db->select('P.PharmacyName,P.PhoneNumber,P.CompanyName,U.AptAddress');
        $this->db->select('P.PharmacyID, P.LocationID, U.Email, L.FormattedAddress, L.Latitude, L.Longitude, L.StreetNumber, L.Route, L.City, L.State, L.Country, L.PostalCode,P.UserID');
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select("U.Firstname,  U.Lastname", FALSE);
        $this->db->join('Users U', 'U.UserID=P.UserID');
        $this->db->join('Locations L', 'P.LocationID=L.LocationID');
        //$this->db->where('P.PharmacyID', '4');
        $this->db->where("P.PharmacyID IN(select PharmacyID from PharmacyWorkArea WHERE ST_WITHIN(GeomFromText('POINT($Latitude $Longitude)'), `AreaStrGeo`) = 1)", NULL, true);
        $query = $this->db->get('Pharmacies P');
        //echo $this->db->last_query();die;
        return $query->row_array();
    }

    /**
     * Search For valid pharmacy
     * @return int
     */
    function find_secondary_pharmacy_by_lat_lng($Latitude, $Longitude) {
        $this->db->select('P.PharmacyName,P.PhoneNumber,P.CompanyName,U.AptAddress');
        $this->db->select('P.PharmacyID, P.LocationID, U.Email,L.FormattedAddress, L.Latitude, L.Longitude, L.StreetNumber, L.Route, L.City, L.State, L.Country, L.PostalCode,P.UserID');
        //$this->db->select("CONCAT(U.Firstname, ' ', U.Lastname) as Name", FALSE);
        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select("U.Firstname,  U.Lastname", FALSE);
        $this->db->join('Users U', 'U.UserID=P.UserID');
        $this->db->join('Locations L', 'P.LocationID=L.LocationID');
        //$this->db->where('P.PharmacyID', '4');
        $this->db->where("P.PharmacyID IN(select PharmacyID from PharmacyWorkArea WHERE ST_WITHIN(GeomFromText('POINT($Latitude $Longitude)'), `SecondaryAreaStrGeo`) = 1)", NULL, true);
        $query = $this->db->get('Pharmacies P');
        //echo $this->db->last_query();
        return $query->row_array();
    }

    /**
     * 
     * @param type $OrderTime
     * @param type $Day
     * @param type $PharmacyID
     * @return array
     */
    public function get_delivery_time($OrderTime, $Day, $PharmacyID) {
        //print_r($OrderTime);print_r($Day);print_r($PharmacyID);die(' '.__LINE__);
        $result = array();
        $time = ConverTimeIntoSeconds($OrderTime);
        //make sure pharmacy open atleast once
        $this->db->select('PharmacyWorkingHourID')->where('PharmacyID', $PharmacyID)->where('IsClosed', '0');
        $WorkingTime = $this->db->get('PharmacyWorkingHours')->result_array();
        //echo $this->db->last_query();
        if (count($WorkingTime) <= 0) {
            return $result;
        }
        //Check if exist in start to end time 
        $this->db->select('PharmacyWorkingHourID,WorkingDay,OpensAt,ClosesAt,IsClosed');
        $this->db->where('PharmacyID', $PharmacyID)->where('WorkingDay', strtoupper($Day))->where('IsClosed', '0');
        $this->db->where('PharmacyID', $PharmacyID)->where('IsClosed', '0');
        $WorkingTime = $this->db->get('PharmacyWorkingHours')->row_array();
        //print_r($WorkingTime);die;
        //check if order time is before Start time then alot first slot
        //if (!empty($WorkingTime) && isset($WorkingTime['OpensAt']) && $WorkingTime['OpensAt'] <= $time && $WorkingTime['ClosesAt'] >= $time) {
        if (!empty($WorkingTime)) {
            $this->db->select('WorkingDay, PharmacyDeliveryTimeSlotID, PharmacyDeliveryTimeSlotGUID, SlotStartTime, SlotEndTime, CutOffTime');
            $this->db->from('PharmacyDeliveryTimeSlots');
            $this->db->where('CutOffTime >=', $time);
            $this->db->where('PharmacyID', $PharmacyID);
            $this->db->where('WorkingDay', strtoupper($Day));
            $this->db->order_by('CutOffTime', 'DESC');
            $Query = $this->db->get(); //echo $this->db->last_query();die;
            $result = $Query->result_array();
            //print_r($result);die;
            //return $result;
            $index = count($result) - 1;
            $result = (!empty($result) && isset($result[$index])) ? $result[$index] : array();
        }
        if (empty($result)) {
            //try to find next available slot
            $result = $this->getNextSlot(strtoupper($Day), $PharmacyID);
        }

        return $result;
    }

    function getNextSlot($Day, $PharmacyID) {
        $result = array();
        $NextDay = $this->getNextDay($Day);
        $this->db->select('PharmacyWorkingHourID,WorkingDay,OpensAt,ClosesAt,IsClosed');
        $this->db->where('PharmacyID', $PharmacyID)->where('WorkingDay', strtoupper($NextDay))->where('IsClosed', '0');
        $WorkingTime = $this->db->get('PharmacyWorkingHours')->row_array();
        if (empty($WorkingTime)) {
            $result = $this->getNextSlot($NextDay, $PharmacyID);
        } else {
            $this->db->select('WorkingDay, PharmacyDeliveryTimeSlotID, PharmacyDeliveryTimeSlotGUID, SlotStartTime, SlotEndTime, CutOffTime');
            $this->db->from('PharmacyDeliveryTimeSlots');
            $this->db->where('PharmacyID', $PharmacyID);
            $this->db->where('WorkingDay', strtoupper($NextDay));
            $this->db->order_by('SlotStartTime');
            $Query = $this->db->get(); //echo $this->db->last_query();die;
            $result = $Query->row_array();
            //var_dump($result,$PharmacyID, $WorkingTime);die;
            if (empty($result)) {
                $result = $this->getNextSlot($NextDay, $PharmacyID);
            }
        }
        return $result;
    }

    function getNextDay($Day) {
        $DaysList = array('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY');
        $key = array_search($Day, $DaysList);
        $key = ($key == 6) ? 0 : $key + 1;
        return $DaysList[$key];
    }

    /**
     * 
     * @param type $OrderTime
     */
    public function get_all_delivery_slots($PharmacyID) {
        $this->db->select('PharmacyDeliveryTimeSlotGUID, WorkingDay');
        $this->db->select("FROM_UNIXTIME(SlotStartTime, '%l:%i %p') SlotStartTime", FALSE);
        $this->db->select("FROM_UNIXTIME(SlotEndTime, '%l:%i %p') SlotEndTime", FALSE);
        $this->db->select("FROM_UNIXTIME(CutOffTime, '%l:%i %p') CutOffTime", FALSE);
        $this->db->order_by('WorkingDay', 'ASC');
        $this->db->order_by('SlotStartTime', 'ASC');
        $this->db->where('PharmacyID', $PharmacyID);
        $result = $this->db->get('PharmacyDeliveryTimeSlots')->result_array();
        //print_r($result);die;
        return $result;
    }

    public function pharmacy_delivery_timeslot_by_day($PharmacyID, $Day, $SecondsCompleted = NULL) {
        $this->db->select('*');
        $this->db->order_by('CutOffTime', 'ASC');
        $this->db->where('PharmacyID', $PharmacyID);
        $this->db->where('WorkingDay', $Day);
        $this->db->where('NoDelivery', 0);
        if (!is_null($SecondsCompleted)) {
            $this->db->where('CutOffTime >', $SecondsCompleted);
        }
        $result = $this->db->get('PharmacyDeliveryTimeSlots')->row_array();
        return $result;
    }

    public function pharmacy_open_close_status($PharmacyID, $Day, $SecondsCompleted = NULL) {
        $this->db->select('*');
        $this->db->order_by('OpensAt', 'ASC');
        $this->db->where('PharmacyID', $PharmacyID);
        $this->db->where('WorkingDay', $Day);
        $this->db->where('IsClosed', 0);
        if (!is_null($SecondsCompleted)) {
            $this->db->where("$SecondsCompleted BETWEEN OpensAt AND ClosesAt", NULL, FALSE);
        }
        $result = $this->db->get('PharmacyWorkingHours')->num_rows();
        //echo $this->db->last_query(); die();
        return $result > 0 ? TRUE : FALSE;
    }

    /**
     * pharmacy_live_status
     * @param type $PharmacyID
     * @param type $Timezone
     * @param type $Time
     * @return string
     */
    public function pharmacy_live_status($PharmacyID, $Timezone, $Time) {
        if (is_null($Time)) {
            $Date = new DateTime();
            $Date->setTimezone(new DateTimeZone($Timezone));
        } else {
            $Date = new DateTime($Time, new DateTimeZone($Timezone));
            //$Date->setTimezone(new DateTimeZone($Timezone));
        }
        //$Date->setTimezone(new DateTimeZone($Timezone));
        $TodayDay = strtoupper($Date->format("l"));
        $SecondsCompleted = ConverTimeIntoSeconds($Date->format("H:i"));

        $Interval = new DateInterval("P1D"); // 1 day
        $Occurrences = 6;
        $Period = new DatePeriod($Date, $Interval, $Occurrences);
        foreach ($Period as $Key => $Dt) {
            $IDay = strtoupper($Dt->format("l"));
            if ($IDay == $TodayDay) {
                $PharmacyDeliveryTimeslot = $this->pharmacy_delivery_timeslot_by_day($PharmacyID, $IDay, $SecondsCompleted);
                $OpenCloseStatus = $this->pharmacy_open_close_status($PharmacyID, $TodayDay, $SecondsCompleted);
                if (!empty($PharmacyDeliveryTimeslot)) {
                    $Text = "Want it delivered Today between " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotStartTime']) . " - " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotEndTime']);
                    $T = $Dt->format("l, j M") . " between " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotStartTime']) . " - " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotEndTime']);
                    $SecondsLeft = $PharmacyDeliveryTimeslot['CutOffTime'] - $SecondsCompleted;
                    if ($SecondsLeft > 3600) {
                        $Text2 = "Order in the next " . gmdate("G:i", $SecondsLeft) . " hours";
                    } elseif ($SecondsLeft > 60) {
                        $Text2 = "Order in the next " . gmdate("i:s", $SecondsLeft) . " minutes";
                    } else {
                        $Text2 = "Order in the next " . gmdate("s", $SecondsLeft) . " seconds";
                    }
                    if ($OpenCloseStatus) {
                        $Out['LiveStatus'] = "2";
                        $Out['Title'] = "Open Now";
                    } else {
                        $Out['LiveStatus'] = "3";
                        $Out['Title'] = "Closed Now";
                    }

                    $Out['Text'] = $Text;
                    $Out['T'] = $T;
                    $Out['Text2'] = $Text2;
                    $Out['CutOffTime'] = $Dt->format("Y-m-d") . " " . gmdate("H:i:s", $PharmacyDeliveryTimeslot['CutOffTime']);
                    $Out['SlotStart'] = $Dt->format("Y-m-d") . " " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotStartTime']);
                    $Out['SlotEnd'] = $Dt->format("Y-m-d") . " " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotEndTime']);
                    break;
                }
            } else {
                $PharmacyDeliveryTimeslot = $this->pharmacy_delivery_timeslot_by_day($PharmacyID, $IDay);
                $OpenCloseStatus = $this->pharmacy_open_close_status($PharmacyID, $TodayDay, $SecondsCompleted);
                if (!empty($PharmacyDeliveryTimeslot)) {
                    $Text = "Want it delivered " . $Dt->format("l, j M") . " between " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotStartTime']) . " - " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotEndTime']);
                    $T = $Dt->format("l, j M") . " between " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotStartTime']) . " - " . gmdate("g:i a", $PharmacyDeliveryTimeslot['SlotEndTime']);
                    $SecondsLeft = $PharmacyDeliveryTimeslot['CutOffTime'] + (86400 * $Key);
                    if ($SecondsLeft > 86400 && $SecondsLeft < (86400 * 2)) {
                        $Text2 = "Order today or tomorrow till " . gmdate("G:i", $PharmacyDeliveryTimeslot['CutOffTime']);
                    } elseif ($SecondsLeft > 86400) {
                        $Text2 = "Order in the next " . ($Key - 1) . " day(s) & " . gmdate("G:i", $PharmacyDeliveryTimeslot['CutOffTime']) . " hours";
                    } elseif ($SecondsLeft > 3600) {
                        $Text2 = "Order in the next " . gmdate("G:i", $SecondsLeft) . " hours";
                    } elseif ($SecondsLeft > 60) {
                        $Text2 = "Order in the next " . gmdate("i:s", $SecondsLeft) . " minutes";
                    } else {
                        $Text2 = "Order in the next " . gmdate("s", $SecondsLeft) . " seconds";
                    }
                    if ($OpenCloseStatus) {
                        $Out['LiveStatus'] = "2";
                        $Out['Title'] = "Open Now";
                    } else {
                        $Out['LiveStatus'] = "3";
                        $Out['Title'] = "Closed Now";
                    }
                    $Out['Text'] = $Text;
                    $Out['T'] = $T;
                    $Out['Text2'] = $Text2;
                    $Out['CutOffTime'] = $Dt->format("Y-m-d") . " " . gmdate("H:i:s", $PharmacyDeliveryTimeslot['CutOffTime']);
                    $Out['SlotStart'] = $Dt->format("Y-m-d") . " " . gmdate("H:i:s", $PharmacyDeliveryTimeslot['SlotStartTime']);
                    $Out['SlotEnd'] = $Dt->format("Y-m-d") . " " . gmdate("H:i:s", $PharmacyDeliveryTimeslot['SlotEndTime']);
                    break;
                }
            }
        }
        if (!empty($Out)) {
            return $Out;
        } else {
            $Out['LiveStatus'] = "2";
            $Out['Title'] = "";
            $Out['Text'] = "No Delivery time set by this pharmacy";
            $Out['Text2'] = "";
            return $Out;
        }
    }

    /**
     * pharmacies_by_group_admin
     * @param type $PharmacyAdminID
     * @return type
     */
    public function pharmacies_by_group_admin($PharmacyAdminID) {
        $this->db->select('U.UserGUID AS PharmacyGUID, U.Email, U.FirstName, U.LastName');
        $this->db->select('P.CompanyName, P.PharmacyName, '
                . 'P.PhoneNumber, P.FaxNumber, P.Website, P.AddressLine1, '
                . 'P.AddressLine2, P.City, P.State, P.Country, P.PostalCode, '
                . 'P.ShowInsuranceCard, P.ShowSTI, P.ShowAllergy, P.ShowMedReview');
        
        $this->db->select('IFNULL(P.PharmacyLicense,"") AS PharmacyLicense', FALSE);
        $this->db->select('IFNULL(P.PharmacyLicenseExp,"") AS PharmacyLicenseExp', FALSE);


        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.AboutMe,"") AS AboutMe', FALSE);

        $this->db->select('IFNULL(P.Latitude,"0.0") AS Latitude', FALSE);
        $this->db->select('IFNULL(P.Longitude,"0.0") AS Longitude', FALSE);

        $this->db->where('P.PharmacyAdminID', $PharmacyAdminID);

        $this->db->where('U.UserTypeID', 3);

        $this->db->join('Pharmacies AS P', 'P.UserID=U.UserID');

        $this->db->from('Users' . ' AS U');
        $Query = $this->db->get();
        $Users = $Query->result_array();
        $Users = encrypt_decrypt($Users, 1);
        return $Users;
    }

    /**
     * pharmacies_by_user_id
     * @param type $PharmacyUserID
     * @return type
     */
    public function pharmacies_by_user_id($PharmacyUserID) {
        $this->db->select('U.UserGUID AS PharmacyGUID, U.Email, U.FirstName, U.LastName');
        $this->db->select('P.CompanyName, P.PharmacyName, '
                . 'P.PhoneNumber, P.FaxNumber, P.Website, P.AddressLine1, '
                . 'P.AddressLine2, P.City, P.State, P.Country, P.PostalCode, '
                . 'P.ShowInsuranceCard, P.ShowSTI, P.ShowAllergy, P.ShowMedReview');
        
        $this->db->select('IFNULL(P.PharmacyLicense,"") AS PharmacyLicense', FALSE);
        $this->db->select('IFNULL(P.PharmacyLicenseExp,"") AS PharmacyLicenseExp', FALSE);


        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.AboutMe,"") AS AboutMe', FALSE);

        $this->db->select('IFNULL(P.Latitude,"0.0") AS Latitude', FALSE);
        $this->db->select('IFNULL(P.Longitude,"0.0") AS Longitude', FALSE);

        $this->db->where('P.UserID', $PharmacyUserID);

        $this->db->where('U.UserTypeID', 3);

        $this->db->join('Pharmacies AS P', 'P.UserID=U.UserID');

        $this->db->from('Users' . ' AS U');
        $Query = $this->db->get();
        $Users = $Query->result_array();
        $Users = encrypt_decrypt($Users, 1);
        return $Users;
    }

    function pharmacies_by_lat_lng($Latitude, $Longitude) {
        $this->db->select('U.UserGUID AS PharmacyGUID, U.Email, U.FirstName, U.LastName');
        $this->db->select('P.CompanyName, P.PharmacyName, '
                . 'P.PhoneNumber, P.FaxNumber, P.Website, P.AddressLine1, '
                . 'P.AddressLine2, P.City, P.State, P.Country, P.PostalCode, '
                . 'P.ShowInsuranceCard, P.ShowSTI, P.ShowAllergy, P.ShowMedReview');
        
        $this->db->select('IFNULL(P.PharmacyLicense,"") AS PharmacyLicense', FALSE);
        $this->db->select('IFNULL(P.PharmacyLicenseExp,"") AS PharmacyLicenseExp', FALSE);


        $this->db->select('IFNULL(U.ProfilePicture,"") AS ProfilePicture', FALSE);
        $this->db->select('IFNULL(U.AboutMe,"") AS AboutMe', FALSE);

        $this->db->select('IFNULL(P.Latitude,"0.0") AS Latitude', FALSE);
        $this->db->select('IFNULL(P.Longitude,"0.0") AS Longitude', FALSE);

        $this->db->where('U.UserTypeID', 3);
        $this->db->where("P.PharmacyID IN(SELECT PharmacyID from PharmacyWorkArea WHERE ST_WITHIN(GeomFromText('POINT($Latitude $Longitude)'), `AreaStrGeo`) = 1)", NULL, true);

        $this->db->join('Pharmacies AS P', 'P.UserID=U.UserID');

        $this->db->from('Users' . ' AS U');
        $Query = $this->db->get();
        $Users = $Query->result_array();
        $Users = encrypt_decrypt($Users, 1);
        return $Users;
    }

}
