<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Description of prescription_model
 *
 * @author nitins
 */
class Prescription_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 
     * @param type $prescription
     * @return boolean
     */
    public function create_prescription_with_images($UserID, $RxName, $Notes, $Images) {
        $Prescription = [
            "PrescriptionGUID"=>guid(),
            "UserID"=>$UserID,
            "IsDigitized"=>0,
            "IsDeleted"=>0,
            "IsEditable"=>'0',
            "IsTransfer"=>0,
            "CreatedAt"=>DATETIME,
            "UpdatedAt"=>DATETIME,            
        ];
        
        if(!is_null($Notes)){
            $Prescription['Notes']=$Notes;
        }
        
        $this->db->insert('Prescriptions', $Prescription);
        $PrescriptionID = $this->db->insert_id();
        
        $Medications[] = [
            'MedicationGUID' => guid(),
            'MedicationSID' => $this->create_medication_sid(),
            'PrescriptionID' => $PrescriptionID,
            'ProductID'=>0,
            'MedicationName' => $RxName,
            'RefillAllowed' => 0,
            'IsDeleted' => 0,
            'CreatedAt' => DATETIME,
        ];        
        $this->db->insert_batch('Medications', $Medications);
        
        foreach ($Images as $ImageGUID) {
            $PrescriptionImages[] = [
                'PrescriptionImageGUID' => guid(),
                'ImageGUID' => $ImageGUID,
                'PrescriptionID' => $PrescriptionID,
                'IsDeleted' => 0
            ];
        }
        $this->db->insert_batch('PrescriptionImages', $PrescriptionImages);
        return $PrescriptionID;
    }
    
    
    
    public function create_prescription_by_transfer($UserID, $PharmacyID, $PharmacyName, $PharmacyAddress, $PharmacyPhone, $ImportAll, $Medications) {
        $Prescription = [
            "PrescriptionGUID"=>guid(),
            "UserID"=>$UserID,
            "IsDigitized"=>1,
            "IsDeleted"=>0,
            "IsEditable"=>'0',
            "IsTransfer"=>1,
            "CreatedAt"=>DATETIME,
            "UpdatedAt"=>DATETIME,            
        ];
        
        $Prescription = encrypt_decrypt($Prescription);
        $this->db->insert('Prescriptions', $Prescription);
        $PrescriptionID = $this->db->insert_id();
        
        if($ImportAll==1){
            $Product = [
                'ProductGUID' => guid(),
                'DrugGUID' => random_string('numeric',8),
                'Title' => "All My Meds",
                'CreatedAt' => DATETIME,
            ];
            
            $Product = encrypt_decrypt($Product);
            $this->db->insert('MPProducts', $Product);
            $ProductID = $this->db->insert_id();
            
            $Meds[] = [
                'MedicationGUID' => guid(),
                'MedicationSID' => $this->create_medication_sid(),
                'PrescriptionID' => $PrescriptionID,
                'ProductID'=>$ProductID,
                'MedicationName' => "All My Meds",
                'RefillAllowed' => 0,
                'IsDeleted' => 0,
                'CreatedAt' => DATETIME,
            ];
        }else{
            foreach ($Medications as $Key=>$Medication) {
                $MedicationName = safe_array_key($Medication, "MedicationName", "Unknown drug-$Key");
                
                $Product = [
                    'ProductGUID' => guid(),
                    'DrugGUID' => random_string('numeric',8),
                    'Title' => "All My Meds",
                    'CreatedAt' => DATETIME,
                ];
                
                $Product = encrypt_decrypt($Product);
                $this->db->insert('MPProducts', $Product);
                $ProductID = $this->db->insert_id();            
            
                $Meds[] = [
                    'MedicationGUID' => guid(),
                    'MedicationSID' => $this->create_medication_sid(),
                    'PrescriptionID' => $PrescriptionID,
                    'ProductID'=>$ProductID,
                    'MedicationName' => $MedicationName,
                    'RefillAllowed' => 0,
                    'IsDeleted' => 0,
                    'CreatedAt' => DATETIME,
                ];
            }            
        }
        
        $Meds = encrypt_decrypt($Meds);
        $this->db->insert_batch('Medications', $Meds); 
        
        //Register transfer
        $ExternalTransfer = [
            'ExternalTransfersGUID' => guid(),
            'UserID' => $UserID,
            'IsDeleted' => '0',
            'PrescriptionID' => $PrescriptionID,
            'PharmacyName' => $PharmacyName,
            'Address' => $PharmacyAddress,
            'ContactNo' => $PharmacyPhone,
            'Status' => 'Placed',
        ];
        $this->db->insert('ExternalTransfers', $ExternalTransfer); 
        $ExternalTransferID = $this->db->insert_id();
        
        
        $PharmacyExternalTransfer = [
            'PharmacyExternalTransfersGUID' => guid(),
            'PharmacyID' => $PharmacyID,
            'ExternalTransfersID' => $ExternalTransferID,
            'CreatedAt' => DATETIME,
        ];
        
        $this->db->insert('PharmaciesExternalTransfers', $PharmacyExternalTransfer);
        $PharmacyExternalTransferID = $this->db->insert_id();
        
        $Pharmacy = $this->app->get_row('Pharmacies', 'UserID', [
            "PharmacyID"=>$PharmacyID,
        ]);
        
        $NotificationData = array(
            'NotificationGuID' => guid(),
            'NotificationTypeID' => 13, //Transfer Request  Place
            'UserID' => $UserID,
            'ToUserID' => $Pharmacy['UserID'],
            'RefrenceID' => $PharmacyExternalTransferID,
            'StatusID' => 0,
            'CreatedDate' => DATETIME
        );
        $this->db->insert('Notifications', $NotificationData);
        
        
        
        //Send Email to pharmacy
        //$this->sent_new_transfer_email($Pharmacy);
        return $PrescriptionID;
    }
    
    function create_medication_sid() {
        return 'RX_' + random_string('numeric', 2) + random_string('numeric', 2) + random_string('numeric', 2) + random_string('numeric', 2);
    }
    
    
    /**
     * 
     * @param type $prescription
     * @return boolean
     */
    public function save_prescription($prescription = array()) {
        if (!empty($prescription)) {
            if (!isset($prescription['PrescriptionGUID'])) {
                $prescription['PrescriptionGUID'] = get_guid();
            }
            $this->db->insert(PRESCRIPTIONS, $prescription);
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * UPdate prescription
     * @param type $prescription
     * @return boolean
     */
    public function update_prescription($prescription = array()) {
        if (!empty($prescription)) {
            $this->db->where('PrescriptionGUID', $prescription['PrescriptionGUID']);
            $this->db->update(PRESCRIPTIONS, $prescription);
            return TRUE;
        }
        return false;
    }

    /**
     * Return Card in basis of Id
     * @param type $InsuranceCardID
     * @return type
     */
    public function get_prescription_by_id($PrescriptionID) {
        $this->db->select('*');
        $this->db->where('PrescriptionID', $PrescriptionID);
        return $this->db->get('Prescriptions')->row_array();
    }

    /**
     * Return Prescription on basis of GUId
     * @param type $PrescriptionGUID
     * @return type
     */
    public function get_prescription_by_guid($PrescriptionGUID) {
        $this->db->select('P.PrescriptionGUID, P.Name, P.Notes, P.IsDigitized, P.PrescriptionID, P.IsTransfer, P.PrescriptionID');
        $this->db->select('U.UserGUID, U.Firstname, U.Lastname,U.UserID');
        $this->db->join(USERS . ' U', "P.UserID=U.UserID");
        $this->db->where('P.PrescriptionGUID', $PrescriptionGUID);
        $this->db->where('P.IsDeleted', 0);
        //$this->db->or_where("P.UserID IN(SELECT DependentUserID FROM ".USERDEPENDENTS." WHERE UserID='".$UserID."')", NULL, FALSE);
        $this->db->from(PRESCRIPTIONS . ' AS P');
        $query = $this->db->get();
        return $query->row_array();
    }

    public function get_prescription_order_status($PrescriptionId) {
        $this->db->select('O.StatusID, OP.OrderID, O.OrderGUID');
        $this->db->select("IFNULL(O.DeliveryDate, '') NextDeliveryDate", FALSE);
        $this->db->select("IFNULL(O.DeliveryDateMax, '') NextDeliveryDateMax", FALSE);
        $this->db->where('OP.IsDeleted', 0);
        $this->db->where('OP.PrescriptionID', $PrescriptionId);
        $this->db->join(MPORDERS . ' O', 'OP.OrderID=O.OrderID', 'LEFT');
        $this->db->order_by('OP.OrderPrescriptionID');
        $Query = $this->db->get(ORDERPRESCRIPTIONS . ' OP');
        return $Query->row_array();
    }
    
    public function get_prescription_order_deliverd_once($PrescriptionId) {
        $this->db->select('O.StatusID');
        $this->db->select("IFNULL(O.DeliveryDate, '') LastDeliveryDate", FALSE);
        $this->db->where('OP.IsDeleted', 0);
        $this->db->where('O.StatusID', 11);
        $this->db->where('OP.PrescriptionID', $PrescriptionId);
        $this->db->join(MPORDERS . ' O', 'OP.OrderID=O.OrderID', 'LEFT');
        $this->db->order_by('OP.OrderPrescriptionID');
        $Query = $this->db->get(ORDERPRESCRIPTIONS . ' OP');
        return $Query->result_array();
    }
    
    /**
     * Retrive Cards of user and dependent
     * @param type $UserID
     * @return type
     */
    public function prescription_list($UserID, $limit = NULL, $offset = 0) {
        //Logic to show only new order
        //$this->db->join(MPORDERSPRODCUTS . ' PRD');
        //Logic to show only new order end
        $this->db->group_by('P.PrescriptionID');
        //$this->db->select("IFNULL(O.StatusID,'') OrderStatusID", FALSE);
        $this->db->select("IF(OP.IsDeleted>0, '', IFNULL(O.StatusID,'')) OrderStatusID", FALSE);

        $this->db->select("IFNULL(IF(O.StatusID=11,'1','0'),'0') DeliveredOnce", FALSE);
        //$this->db->where('OP.IsDeleted', 0);
        $this->db->join(ORDERPRESCRIPTIONS . ' OP', 'OP.PrescriptionID=P.PrescriptionID', 'LEFT');
        $this->db->join(MPORDERS . ' O', 'OP.OrderID=O.OrderID', 'LEFT');

        $this->db->select("IFNULL(O.OrderID, '') OrderID", FALSE);
        $this->db->select('P.PrescriptionID ,P.PrescriptionGUID, P.Name, P.Notes, P.IsDigitized, P.IsEditable, P.IsDeleted,P.IsTransfer');
        $this->db->select('IFNULL(( SELECT `ET`.`Status` as `TransferStatus` FROM `PharmaciesExternalTransfers` `ET` WHERE ET.ExternalTransfersID=(SELECT E.ExternalTransfersID FROM ExternalTransfers E WHERE E.PrescriptionID=P.PrescriptionID ) ORDER BY `ET`.`CreatedAt` DESC LIMIT 0,1 ),\'\') TransferStatus', FALSE);
        $this->db->select('IFNULL(( SELECT E.Status FROM ExternalTransfers E WHERE E.PrescriptionID=P.PrescriptionID ORDER BY `E`.`CreatedAt` DESC LIMIT 0,1 ),\'\') TransferStatus2', FALSE);
        //$this->db->select('U.UserGUID, U.Firstname, U.Lastname');
        //$this->db->join(USERS . ' U', "P.UserID=U.UserID");
        $this->db->where('P.UserID', $UserID);
        $this->db->where('P.IsDeleted', 0);
        //Uncommect below line in case card of dependent also needed
        //$this->db->or_where("P.UserID IN(SELECT DependentUserID FROM ".USERDEPENDENTS." WHERE UserID='".$UserID."')", NULL, FALSE);
        $this->db->from(PRESCRIPTIONS . ' AS P');
        //if (!empty($limit) && !empty($offset)) {
        if (!empty($limit) && (!empty($offset) || $offset == 0)) {
            $this->db->limit($limit, $offset);
            //$this->db->limit($offset,$limit);
        }
        $this->db->order_by('P.CreatedAt', 'DESC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function prescription_list_count($UserID) {
        $this->db->select('P.PrescriptionID ,P.PrescriptionGUID, P.Name, P.Notes, P.IsDigitized, P.IsEditable, P.IsDeleted ');
        $this->db->where('P.UserID', $UserID);
        $this->db->where('P.IsDeleted', 0);
        $this->db->from(PRESCRIPTIONS . ' AS P');
        $query = $this->db->get();
        return $query->num_rows();
    }
    
    
    
    /**
     * Retrive Cards of user and dependent
     * @param type $UserID
     * @return type
     */
    public function prescription_custom_list($UserID, $limit = NULL, $offset = 0) {
        //Logic to show only new order
        //$this->db->join(MPORDERSPRODCUTS . ' PRD');
        //Logic to show only new order end
        $this->db->group_by('P.PrescriptionID');
        //$this->db->select("IFNULL(O.StatusID,'') OrderStatusID", FALSE);
        $this->db->select("IF(OP.IsDeleted>0, '', IFNULL(O.StatusID,'')) OrderStatusID", FALSE);

        $this->db->select("IFNULL(IF(O.StatusID=11,'1','0'),'0') DeliveredOnce", FALSE);
        //$this->db->where('OP.IsDeleted', 0);
        $this->db->join(ORDERPRESCRIPTIONS . ' OP', 'OP.PrescriptionID=P.PrescriptionID', 'LEFT');
        $this->db->join(MPORDERS . ' O', 'OP.OrderID=O.OrderID', 'LEFT');

        $this->db->select("IFNULL(O.OrderID, '') OrderID", FALSE);
        $this->db->select('P.PrescriptionID ,P.PrescriptionGUID, P.Name, P.Notes, P.IsDigitized, P.IsTransfer');
        $this->db->select('IFNULL(( SELECT `ET`.`Status` as `TransferStatus` FROM `PharmaciesExternalTransfers` `ET` WHERE ET.ExternalTransfersID=(SELECT E.ExternalTransfersID FROM ExternalTransfers E WHERE E.PrescriptionID=P.PrescriptionID ) ORDER BY `ET`.`CreatedAt` DESC LIMIT 0,1 ),\'\') TransferStatus', FALSE);
        $this->db->select('IFNULL(( SELECT E.Status FROM ExternalTransfers E WHERE E.PrescriptionID=P.PrescriptionID ORDER BY `E`.`CreatedAt` DESC LIMIT 0,1 ),\'\') TransferStatus2', FALSE);
                
//$this->db->select('U.UserGUID, U.Firstname, U.Lastname');
        //$this->db->join(USERS . ' U', "P.UserID=U.UserID");
        $this->db->where('P.UserID', $UserID);
        $this->db->where('P.IsDeleted', 0);
        //Uncommect below line in case card of dependent also needed
        //$this->db->or_where("P.UserID IN(SELECT DependentUserID FROM ".USERDEPENDENTS." WHERE UserID='".$UserID."')", NULL, FALSE);
        $this->db->from(PRESCRIPTIONS . ' AS P');
        //if (!empty($limit) && !empty($offset)) {
        if (!empty($limit) && (!empty($offset) || $offset == 0)) {
            $this->db->limit($limit, $offset);
            //$this->db->limit($offset,$limit);
        }
        $this->db->order_by('P.CreatedAt', 'DESC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function prescription_custom_list_count($UserID) {
        $this->db->select('P.PrescriptionID ,P.PrescriptionGUID, P.Name, P.Notes, P.IsDigitized, P.IsEditable, P.IsDeleted ');
        $this->db->where('P.UserID', $UserID);
        $this->db->where('P.IsDeleted', 0);
        $this->db->from(PRESCRIPTIONS . ' AS P');
        $query = $this->db->get();
        return $query->num_rows();
    }

    /** Prescription Image related function are below * */

    /**
     * 
     * @param type $images
     * @return boolean
     */
    public function save_prescription_images($images = array()) {//print_r($images);die;
        if (!empty($images)) {
            $this->db->insert_batch(PRESCRIPTIONIMAGES, $images);
            return TRUE;
        }
        return false;
    }

    /**
     * 
     * @param type $prescriptionId
     * @return boolean
     */
    public function delete_prescription_images($prescriptionId) {//print_r($images);die;
        $this->db->where('PrescriptionID', $prescriptionId);
        $this->db->update(PRESCRIPTIONIMAGES, array('IsDeleted' => 1));

        return TRUE;
    }

    /**
     * 
     * @param type $prescriptionId
     * @return boolean
     */
    public function get_prescription_images($prescriptionId) {//print_r($images);die;
        //$this->db->select('ImageGUID, PrescriptionImageGUID');
        $this->db->select('ImageGUID');
        $this->db->where('PrescriptionID', $prescriptionId);
        $this->db->where('IsDeleted', 0);
        $query = $this->db->get(PRESCRIPTIONIMAGES);
        return $query->result_array();
    }

    /**
     * Check that GUID of images should exist in system
     * @param type $Images
     * @return boolean
     */
    public function valid_images($Images) {
        if (!is_array($Images) || empty($Images)) {
            return FALSE;
        }
        $query = $this->db->where_in('ImageGUID', $Images)->select('ImageID')->get(IMAGES);
        return ($query->num_rows() == count($Images));
    }

    /** ORDER Prescription * */
    public function save_order_prescription($Data) {
        if (!isset($Data['OrderPrescriptionGUID'])) {
            $Data['OrderPrescriptionGUID'] = get_guid();
        }
        $this->db->insert(ORDERPRESCRIPTIONS, $Data);
        return $this->db->insert_id();
    }

    /**
     * Get medication list for prescription
     * @param type $PrescriptionID
     * @return type
     */
    public function get_prescription_medication($PrescriptionID, $RefillId = array(), $OrderId=FALSE) {
        $this->db->select('M.MedicationGUID, M.MedicationSID, M.RefillAllowed, M.Strength, M.Unit, MP.DrugGUID, M.Quantity, M.QuantityUnit');
        $this->db->select('MP.ProductGUID, MP.Title as ProductTitle');
        $this->db->join(MPPRODUCTS . " MP", 'M.ProductID=MP.ProductID');
        $this->db->where('M.PrescriptionID', $PrescriptionID);
        $this->db->where('M.IsDeleted', 0);
        if (!empty($RefillId)) {
            $this->db->where_in('M.ProductID', $RefillId);
        }
        
//        if($OrderId){
//            $this->db->select('MPO.Price, MPO.DiscountedPrice');
//            $this->db->where('MPO.OrderID', $OrderId);
//            $this->db->join(MPORDERSPRODCUTS . " MPO", 'M.ProductID=MPO.ProductID');
//        }
        
        $query = $this->db->get(MEDICATIONS . ' M');
        return $query->result_array();
    }

    /**
     * Update order prescription
     * @param type $Data
     * @param type $PrescriptionID
     * @param type $OrderID
     * @return boolean
     */
    public function update_order_prescription($Data, $PrescriptionID, $OrderID) {
        if (!empty($Data)) {
            $this->db->where('OrderID', $OrderID);
            $this->db->where('PrescriptionID', $PrescriptionID);
            $this->db->update(ORDERPRESCRIPTIONS, $Data);
            return TRUE;
        }
        return false;
    }

    /**
     * Get prescription details
     * @param type $PrescriptionGUID
     * @param type $OrderId
     * @return type
     */
    public function PrescriptionDetails($PrescriptionGUID, $OrderId = NULL) {
        $this->db->select('P.PrescriptionID,P.PrescriptionGUID,P.Name,P.Notes,P.UserID,P.IsDigitized');
        $this->db->where('P.PrescriptionGUID', $PrescriptionGUID);
        $this->db->select('OP.IsPayRequested, OP.IsAcceptReady');
        if (!empty($OrderId)) {
            $this->db->where('OrderId', $OrderId);
        }
        $this->db->join(ORDERPRESCRIPTIONS . " OP", 'P.PrescriptionID=OP.PrescriptionID');
        return $this->db->get(PRESCRIPTIONS . ' P')->row_array();
    }

    /**
     * Get prescription doctor info
     * @param type $PrescriptionID
     * @return type
     */
    public function get_prescription_doctor($PrescriptionID) {
        $this->db->select('DoctorName, Address, Phone, Fax');
        $this->db->where('PrescriptionID', $PrescriptionID);
        return $this->db->get(PRESCRIPTIONDOCTORINFO)->row_array();
    }

    /**
     * Get history that how many time a prescription orderd
     * @param type $PrescriptionID
     * @return type
     */
    public function get_prescription_order_history($PrescriptionID) {
        $this->db->group_by("O.OrderID");
        $this->db->select("O.OrderID,O.StatusID, DATE_FORMAT(O.CreatedAt, '%d-%m-%Y %l:%i %p') CreatedAt", FALSE);
        $this->db->where('OP.PrescriptionID', $PrescriptionID);
        $this->db->order_by('OrderPrescriptionID', 'DESC');
        $this->db->join(MPORDERS . " O", 'O.OrderID=OP.OrderID');
        //$this->db->select('B.TotalAmount')->join(BILLINGS. " B", 'OP.OrderID=B.BillingForID', 'LEFT');
        return $this->db->get(ORDERPRESCRIPTIONS . ' OP')->result_array();
    }

    /**
     * Get prescription details
     * @param type $PrescriptionGUID
     * @param type $OrderId
     * @return type
     */
    public function TransferPrescriptionDetails($PrescriptionGUID, $OrderId = NULL) {
        $this->db->select('P.PrescriptionID,P.PrescriptionGUID,P.Name,P.Notes,P.UserID,P.IsDigitized');
        $this->db->where('P.PrescriptionGUID', $PrescriptionGUID);
        return $this->db->get(PRESCRIPTIONS . ' P')->row_array();
    }

    public function get_prescription_order_in_process($PrescriptionID) {
        $this->db->select("O.OrderGUID,O.StatusID, DATE_FORMAT(O.CreatedAt, '%d-%m-%Y %l:%i %p') CreatedAt", FALSE);
        $this->db->where('OP.PrescriptionID', $PrescriptionID);
        $this->db->where_not_in('O.StatusID', array(11, 5, 2));
        $this->db->where('OP.IsDeleted', 0);
        $this->db->order_by('OrderPrescriptionID', 'DESC');
        $this->db->join(MPORDERS . " O", 'O.OrderID=OP.OrderID');
        $this->db->select("(SELECT IsCancelled From ". PHARMACIESORDERS." POS Where POS.OrderID=OP.OrderID ORDER BY PharmacyOrderID DESC) As IsCancelled" , FALSE);
        //$this->db->select('B.TotalAmount')->join(BILLINGS. " B", 'OP.OrderID=B.BillingForID', 'LEFT');
        return $this->db->get(ORDERPRESCRIPTIONS . ' OP')->row_array();
    }

    public function get_order_product_in_process($PrescriptionID, $ProductID) {
        $this->db->join(MPORDERSPRODCUTS . " OPS", 'OPS.OrderID=OP.OrderID');
        $this->db->where('OPS.ProductID', $ProductID);
        $this->db->where('OPS.PrescriptionID', $PrescriptionID);
        $this->db->where('OPS.IsDeleted', 0);

        $this->db->select("O.OrderGUID,O.StatusID, DATE_FORMAT(O.CreatedAt, '%d-%m-%Y %l:%i %p') CreatedAt", FALSE);
        $this->db->where('OP.PrescriptionID', $PrescriptionID);
        $this->db->where_not_in('O.StatusID', array(11, 5));
        $this->db->order_by('OrderPrescriptionID', 'DESC');
        $this->db->join(MPORDERS . " O", 'O.OrderID=OP.OrderID');
        //$this->db->select('B.TotalAmount')->join(BILLINGS. " B", 'OP.OrderID=B.BillingForID', 'LEFT');
        return $this->db->get(ORDERPRESCRIPTIONS . ' OP')->row_array();
    }

}
