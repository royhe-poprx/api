<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Description of prescription_model
 *
 * @author nitins
 */
class Medication_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 
     * @param type $StartWith
     * @return type
     */
    function create_order_sid($StartWith = "O") {
        $this->db->where('DATE_FORMAT(CreatedAt, \'%Y\')=', gmdate('Y'), FALSE);
        $this->db->where('DATE_FORMAT(CreatedAt, \'%m\')=', gmdate('m'), FALSE);
        $res = $this->db->count_all_results('Orders');
        return order_sid($StartWith, $res + 1);
    }

    /**
     * 
     * @param type $MedicationName
     * @return type
     */
    function create_medication_sid($MedicationName = "") {
        $MedicationSID = random_string('numeric', 6);
        if ($MedicationName) {
            $this->db->select('DinNumber');
            $this->db->where('DrugName', $MedicationName);
            $Drug = $this->db->get('CanMedDB')->row_array();
            if (!empty($Drug)) {
                $MedicationSID = $Drug['DinNumber'];
            }
        }
        return $MedicationSID;
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
    public function medications($UserID, $ListType = 'all', $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL) {
        if (is_null($OnlyNumRows)) {
            $this->db->select('M.MedicationID, M.MedicationGUID, M.MedicationSID, M.MedicationName, '
                    . 'M.RefillAllowed, M.VerifyStatus, M.AutoRefill, M.InProcess, M.IsNew, M.NewImageRequest');
            $this->db->select('IFNULL(M.MedicationIcon, "") AS MedicationIcon', FALSE);
            $this->db->select('IFNULL(M.Dosage, "") AS Dosage', FALSE);
            $this->db->select('IFNULL(M.MedicationImages, "") AS MedicationImages', FALSE);
            $this->db->select('IFNULL(M.Strength, "") AS Strength', FALSE);
            $this->db->select('IFNULL(M.Quantity, "") AS Quantity', FALSE);

            $this->db->select('IFNULL(M.PharmacistID, "") AS PharmacistID', FALSE);
            $this->db->select('IFNULL(M.TPPhoneNumber, "") AS TPPhoneNumber', FALSE);
            $this->db->select('IFNULL(M.TPName, "") AS TPName', FALSE);
        } else {
            $this->db->select('M.MedicationGUID');
        }

        $this->db->where('M.UserID', $UserID);

        if ($ListType == 'verified') {
            $this->db->where('M.VerifyStatus', 'VERIFIED');
            $this->db->where('M.InProcess', 0);
        } elseif ($ListType == 'unverified') {
            $this->db->where('M.VerifyStatus', 'UN_VERIFIED');
            $this->db->where('M.InProcess', 0);
        } elseif ($ListType == 'verified-unverified') {
            $this->db->where_in('M.VerifyStatus', ['VERIFIED', 'UN_VERIFIED']);
        } else {
            $this->db->where('M.IsNew', 0);
        }

        $this->db->order_by('CreatedAt', 'DESC');

        $this->db->from('Medications' . ' AS M');

        if (is_null($OnlyNumRows)) {
            if ($Limit != "-1") {
                $this->db->limit($Limit, $Offset);
            }
            $Query = $this->db->get();
            $Medications = $Query->result_array();
            $Medications = encrypt_decrypt($Medications, 1);
            foreach ($Medications as $Key => $Medication) {
                if ($Medication['PharmacistID'] != "") {
                    $Pharmacist = $this->app->get_pharmacy_by_user_id($Medication['PharmacistID']);
                    $Medication['Pharmacy'] = [
                        'IsPoprxPharmacy' => "1",
                        'TPName' => $Pharmacist['PharmacyName'],
                        'TPPhoneNumber' => $Pharmacist['PhoneNumber'],
                        'OtherInfo' => $Pharmacist,
                    ];
                    unset($Medication['PharmacistID']);
                    unset($Medication['TPName']);
                    unset($Medication['TPPhoneNumber']);
                } elseif ($Medication['TPName'] != "") {
                    $Medication['Pharmacy'] = [
                        'IsPoprxPharmacy' => "0",
                        'TPName' => $Medication['TPName'],
                        'TPPhoneNumber' => $Medication['TPPhoneNumber'],
                    ];
                    unset($Medication['PharmacistID']);
                    unset($Medication['TPName']);
                    unset($Medication['TPPhoneNumber']);
                }else{
                    $Medication['Pharmacy'] = (object)[];
                    unset($Medication['PharmacistID']);
                    unset($Medication['TPName']);
                    unset($Medication['TPPhoneNumber']);
                }
                $Medication['Dosage'] = !empty($Medication['Dosage']) ? json_decode($Medication['Dosage'], TRUE) : "";
                $Medication['MedicationIcon'] = !empty($Medication['MedicationIcon']) ? json_decode($Medication['MedicationIcon'], TRUE) : "";
                $Medication['IsImportAll'] = 0;
                $Medication['IsImportAllMessageTitle'] = "";
                $Medication['IsImportAllMessage'] = "";
                $Medication['OrderMedicationGUID'] = "";
                $MedicationID = $Medication['MedicationID'];
                if ($Medication['InProcess'] == 1) {
                    // in order
                    $this->db->select('O.OrderGUID, O.OrderSID, O.TPName');
                    $this->db->join('Orders AS O', 'O.OrderID=OM.OrderID');
                    $this->db->where('O.OrderType', 'TRANSFER_ORDER');
                    $this->db->where('O.ImportAll', '1');
                    $this->db->where('OM.MedicationID', $MedicationID);
                    $this->db->where_not_in('O.Status', ['COMPLETED', 'REJECTED']);
                    $this->db->from('OrderMedications AS OM');
                    $this->db->order_by('CreatedAt', 'DESC');
                    $Query = $this->db->get();
                    $Or = $Query->row_array();
                    if (!empty($Or)) {
                        $Medication['IsImportAll'] = 1;
                        $Medication['IsImportAllMessageTitle'] = "Importing Medications";
                        $Medication['IsImportAllMessage'] = "Our pharmacist is creating a list for you by pulling your records from {$Or['TPName']}. We will inform you once it's done.";
                    }
                }

                // in order
                $this->db->select('O.OrderGUID, O.OrderSID, OM.OrderMedicationGUID');
                $this->db->join('Orders AS O', 'O.OrderID=OM.OrderID');
                $this->db->where('O.OrderType', 'QUOTE_ORDER');
                $this->db->where('OM.MedicationID', $MedicationID);
                $this->db->where('O.Status', 'DRAFT');
                $this->db->from('OrderMedications AS OM');
                $Query = $this->db->get();
                $Or1 = $Query->row_array();
                if (!empty($Or1)) {
                    $Medication['OrderMedicationGUID'] = $Or1['OrderMedicationGUID'];
                }
                if (!empty($Medication['MedicationImages'])) {
                    $Medication['Records'] = explode(",", $Medication['MedicationImages']);
                } else {
                    $Medication['Records'] = array();
                }
                unset($Medication['MedicationID']);
                $Medications[$Key] = $Medication;
            }
            return encrypt_decrypt($Medications, 1);
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    /**
     * 
     * @param type $MedicationGUID
     * @return type
     */
    public function medication_history($MedicationGUID) {
        $this->db->select('O.OrderGUID, O.OrderType, O.ImportAll, O.OrderSID, O.IsDraft, '
                . 'O.PlacedAt, 1 as NumberOfProfiles, O.Status, O.IsPickup, '
                . 'O.PickUpAddress, O.DeliveryAddress, O.DeliveryDate, O.DeliveryDateMax, '
                . 'O.PackedAt, O.OnRouteAt');

        $this->db->join('OrderMedications AS OM', 'OM.MedicationID=M.MedicationID');
        $this->db->join('Orders AS O', 'O.OrderID=OM.OrderID');

        $this->db->where('M.MedicationGUID', $MedicationGUID);

        $this->db->from('Medications AS M');

        $this->db->order_by('O.CreatedAt', 'DESC');
        $Query = $this->db->get();
        $Query->result_array();
        return $Query->result_array();
    }

    /**
     * 
     * @param type $UserID
     * @param type $IsNew
     * @param type $MedicationName
     * @param type $MedicationIcon
     * @param type $Dosage
     * @param type $Images
     * @return type
     */
    public function create_medication($UserID, $IsNew, $MedicationName, $MedicationIcon, $Dosage, $Images) {
        $Medication = [
            'MedicationGUID' => guid(),
            'MedicationSID' => $this->create_medication_sid($MedicationName),
            'UserID' => $UserID,
            'IsNew' => $IsNew,
            'MedicationIcon' => !empty($MedicationIcon) ? json_encode($MedicationIcon) : "",
            'MedicationName' => $MedicationName,
            'Dosage' => !empty($Dosage) ? json_encode($Dosage) : "",
            'RefillAllowed' => '0',
            'VerifyStatus' => 'UN_VERIFIED',
            'MedicationImages' => $Images,
            'InProcess' => 0,
            'CreatedAt' => DATETIME,
        ];
        $this->db->insert('Medications', encrypt_decrypt($Medication));
        $MedicationID = $this->db->insert_id();
        return $MedicationID;
    }

    /**
     * 
     * @param type $MedicationGUID
     * @param type $IsNew
     * @param type $MedicationName
     * @param type $MedicationIcon
     * @param type $Dosage
     * @param type $Images
     */
    public function update_medication($MedicationGUID, $IsNew, $MedicationName, $MedicationIcon, $Dosage, $Images, $NewImageRequest = 0) {

        $Medication = [
            'MedicationIcon' => !empty($MedicationIcon) ? json_encode($MedicationIcon) : "",
            'MedicationName' => $MedicationName,
            'MedicationSID' => $this->create_medication_sid($MedicationName),
            'Dosage' => !empty($Dosage) ? json_encode($Dosage) : "",
            'MedicationImages' => $Images,
            'UpdatedAt' => DATETIME,
        ];
        if ($NewImageRequest == 1) {
            $Medication['NewImageRequest'] = 2;
        }
        $this->db->update('Medications', encrypt_decrypt($Medication), [
            'MedicationGUID' => $MedicationGUID,
        ]);
    }

    /**
     * 
     * @param type $MedicationGUID
     * @param type $IDRequired
     * @return type
     */
    public function get_medication_by_guid($MedicationGUID, $IDRequired = FALSE) {
        if ($IDRequired) {
            $this->db->select('M.MedicationID, M.UserID');
        }
        $this->db->select('M.MedicationGUID, M.MedicationSID, M.IsNew, '
                . 'M.MedicationName, M.Dosage, M.RefillAllowed, M.InProcess, M.VerifyStatus, '
                . 'M.AutoRefill, M.NewImageRequest');

        $this->db->select('IFNULL(M.MedicationIcon, "") AS MedicationIcon', FALSE);
        $this->db->select('IFNULL(M.Strength, "") AS Strength', FALSE);
        $this->db->select('IFNULL(M.Quantity, "") AS Quantity', FALSE);
        $this->db->select('IFNULL(M.MedicationImages, "") AS MedicationImages', FALSE);
        $this->db->where('M.MedicationGUID', $MedicationGUID);
        $this->db->from('Medications' . ' AS M');
        $query = $this->db->get();
        $Medication = $query->row_array();
        $Medication['Dosage'] = !empty($Medication['Dosage']) ? json_decode($Medication['Dosage'], TRUE) : "";
        $Medication['MedicationIcon'] = !empty($Medication['MedicationIcon']) ? json_decode($Medication['MedicationIcon'], TRUE) : "";
        return encrypt_decrypt($Medication, 1);
    }

    /**
     * 
     * @param type $MedicationID
     * @param type $IDRequired
     * @return type
     */
    public function get_medication_by_id($MedicationID, $IDRequired = FALSE) {
        if ($IDRequired) {
            $this->db->select('M.MedicationID, M.UserID');
        }
        $this->db->select('M.MedicationGUID, M.MedicationSID, M.IsNew, '
                . 'M.MedicationName, M.Dosage, M.RefillAllowed, M.InProcess, M.VerifyStatus, '
                . 'M.AutoRefill, M.NewImageRequest');

        $this->db->select('IFNULL(M.MedicationIcon, "") AS MedicationIcon', FALSE);
        $this->db->select('IFNULL(M.Strength, "") AS Strength', FALSE);
        $this->db->select('IFNULL(M.Quantity, "") AS Quantity', FALSE);
        $this->db->select('IFNULL(M.MedicationImages, "") AS MedicationImages', FALSE);
        $this->db->where('M.MedicationID', $MedicationID);
        $this->db->from('Medications' . ' AS M');
        $query = $this->db->get();
        $Medication = $query->row_array();
        $Medication['Dosage'] = !empty($Medication['Dosage']) ? json_decode($Medication['Dosage'], TRUE) : "";
        $Medication['MedicationIcon'] = !empty($Medication['MedicationIcon']) ? json_decode($Medication['MedicationIcon'], TRUE) : "";
        return encrypt_decrypt($Medication, 1);
    }

    /**
     * 
     * @param type $MedicationGUID
     * @return boolean
     */
    public function update_auto_refill($MedicationGUID) {
        $this->db->set('AutoRefill', '!AutoRefill', FALSE);
        $this->db->where('MedicationGUID', $MedicationGUID);
        $this->db->update('Medications');
        return TRUE;
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
    public function create_transfer($UserID, $PharmacyUserID, $TPName, $TPPhone, $ImportAll, $Medications) {
        $Meds = [];
        if ($ImportAll == 1) {
            $Med = [
                'MedicationGUID' => guid(),
                'MedicationSID' => $this->create_medication_sid(""),
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
                    'MedicationSID' => $this->create_medication_sid($MedicationName),
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
            'OrderSID' => $this->create_order_sid("TX"),
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
                $MedicationDetail = $this->get_medication_by_id($MedicationID, TRUE);
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
        $this->app->notify($UserID, $PharmacyUserID, 'TX_ORDER_PLACED', $OrderID);
        return $OrderGUID;
    }

    /**
     * 
     * @param type $UserID
     * @param type $PharmacyUserID
     * @param type $TPName
     * @param type $TPPhone
     * @param type $Medications
     * @return type
     */
    public function verify_medication($UserID, $PharmacyUserID, $TPName, $TPPhone, $Medications) {

        $Meds = [];
        foreach ($Medications as $Key => $Medication) {
            $MedicationDetail = $this->get_medication_by_guid($Medication['MedicationGUID'], TRUE);

            $this->db->update('Medications', [
                'VerifyStatus' => 'VERIFYING',
                'InProcess' => 1,
                'UpdatedAt' => DATETIME,
                    ], [
                'MedicationGUID' => $Medication['MedicationGUID'],
            ]);
            $Meds[] = $MedicationDetail['MedicationID'];
        }

        $OrderGUID = guid();
        $Order = [
            'OrderGUID' => $OrderGUID,
            'OrderSID' => $this->create_order_sid("QX"),
            'PharmacyUserID' => $PharmacyUserID,
            'UserID' => $UserID,
            'OrderType' => 'QUOTE_ORDER',
            'TransferWithQuote' => 1,
            'ImportAll' => 0,
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
                $MedicationDetail = $this->get_medication_by_id($MedicationID, TRUE);
                $OrderMedication = [
                    'OrderMedicationGUID' => guid(),
                    'OrderID' => $OrderID,
                    'UserID' => $MedicationDetail['UserID'],
                    'MedicationID' => $MedicationDetail['MedicationID'],
                ];
                $this->db->insert('OrderMedications', encrypt_decrypt($OrderMedication));
            }
        }
        //notification
        $this->app->notify($UserID, $PharmacyUserID, 'QX_ORDER_PLACED', $OrderID);
        return $OrderGUID;
    }

    /**
     * 
     * @param type $UserID
     * @param type $MedicationGUID
     * @param type $TPName
     * @param type $TPPhone
     * @return boolean
     */
    public function verify_medication_new_1($UserID, $MedicationGUID, $TPName, $TPPhone) {
        $MedicationDetail = $this->get_medication_by_guid($MedicationGUID, TRUE);
        $this->db->update('Medications', [
            'VerifyStatus' => 'VERIFYING',
            'InProcess' => 1,
            'TPName' => $TPName,
            'TPPhoneNumber' => $TPPhone,
            'UpdatedAt' => DATETIME,
                ], [
            'MedicationGUID' => $MedicationGUID,
        ]);
        $OrderID = $this->get_quote_order($UserID);
        $OrderMedication = [
            'OrderMedicationGUID' => guid(),
            'OrderID' => $OrderID,
            'UserID' => $MedicationDetail['UserID'],
            'MedicationID' => $MedicationDetail['MedicationID'],
        ];
        $this->db->insert('OrderMedications', encrypt_decrypt($OrderMedication));
        return TRUE;
    }

    /**
     * 
     * @param type $MedicationGUID
     * @return boolean
     */
    public function delete_medication_by_guid($MedicationGUID) {
        $this->db->delete('Medications', [
            'MedicationGUID' => $MedicationGUID,
            'InProcess' => 0,
        ]);
        return true;
    }

    /**
     * 
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

    /**
     * 
     * @param type $UserID
     * @param type $MedicationGUID
     * @param type $SpecialInstructions
     * @param type $MarkAsNew
     * @return type
     */
    function add_medication_to_quote($UserID, $MedicationGUID, $SpecialInstructions = NULL, $MarkAsNew = 0) {

        $Medication = $this->get_medication_by_guid($MedicationGUID, TRUE);
        $OrderID = $this->get_quote_order($UserID);
        $OrderMedicationGUID = guid();
        $OrderMedication = [
            'OrderMedicationGUID' => $OrderMedicationGUID,
            'OrderID' => $OrderID,
            'MedicationID' => $Medication['MedicationID'],
            'UserID' => $Medication['UserID'],
            'SpecialInstructions' => $SpecialInstructions,
        ];
        $this->db->insert('OrderMedications', encrypt_decrypt($OrderMedication));
        if ($Medication['VerifyStatus'] == "UN_VERIFIED" && $MarkAsNew == 1) {
            $this->db->update('Medications', [
                'IsNew' => 1,
                'UpdatedAt' => DATETIME,
                    ], [
                'MedicationID' => $Medication['MedicationID'],
            ]);
            $Medication['IsNew'] = 1;
        }
        if ($Medication['IsNew'] == 1) {
            $this->db->update('Medications', [
                'VerifyStatus' => 'VERIFYING',
                'InProcess' => 1,
                'UpdatedAt' => DATETIME,
                    ], [
                'MedicationID' => $Medication['MedicationID'],
            ]);
        } else {
            $this->db->update('Medications', [
                'InProcess' => 1,
                'UpdatedAt' => DATETIME,
                    ], [
                'MedicationID' => $Medication['MedicationID'],
            ]);
        }
        return $OrderMedicationGUID;
    }

    /**
     * 
     * @param type $OrderMedicationGUID
     */
    function remove_medication_from_quote($OrderMedicationGUID) {
        $OrderMedication = $this->app->get_row('OrderMedications', 'MedicationID', [
            'OrderMedicationGUID' => $OrderMedicationGUID,
        ]);
        $Medication = $this->app->get_row('Medications', 'MedicationID, IsNew, MedicationImages', [
            'MedicationID' => $OrderMedication['MedicationID'],
        ]);
        if ($Medication['IsNew'] == 1) {
            if ($Medication['MedicationImages'] != "") {
                $this->load->model("upload_model");
                $Images = explode(",", $Medication['MedicationImages']);
                foreach ($Images as $Image) {
                    $this->upload_model->delete_image_by_guid($Image);
                }
            }
            $this->db->delete('Medications', [
                'MedicationID' => $Medication['MedicationID'],
            ]);
        } else {
            $this->db->update('Medications', [
                'InProcess' => 0,
                'UpdatedAt' => DATETIME,
                    ], [
                'MedicationID' => $Medication['MedicationID'],
            ]);
        }
        $this->db->where('OrderMedicationGUID', $OrderMedicationGUID);
        $this->db->delete('OrderMedications');
    }

    /**
     * 
     * @param type $UserID
     * @param type $Medications
     * @return type
     */
    function create_quote_order($UserID, $Medications = array()) {
        $OrderID = $this->get_quote_order($UserID);
        if (!empty($Medications)) {
            foreach ($Medications as $MedicationID) {
                $MedicationDetail = $this->get_medication_by_id($MedicationID, TRUE);
                $OrderMedication = [
                    'OrderMedicationGUID' => guid(),
                    'OrderID' => $OrderID,
                    'UserID' => $MedicationDetail['UserID'],
                    'MedicationID' => $MedicationDetail['MedicationID'],
                ];
                $this->db->insert('OrderMedications', encrypt_decrypt($OrderMedication));
            }
        }
        return $OrderID;
    }

    /**
     * 
     * @param type $UserID
     * @return string
     */
    function get_quote_order($UserID) {
        $Order = $this->find_draft_order_by_type($UserID, 'QUOTE_ORDER', TRUE);
        $User = $this->app->get_row('Users', 'PharmacistID', ['UserID' => $UserID]);
        if (is_null($Order)) {
            $OrderGUID = guid();
            $Order = [
                'OrderGUID' => $OrderGUID,
                'OrderSID' => $this->create_order_sid("QX"),
                'PharmacyUserID' => $User['PharmacistID'],
                'UserID' => $UserID,
                'OrderType' => 'QUOTE_ORDER',
                'CreatedAt' => DATETIME,
                'Status' => 'DRAFT',
            ];
            $this->db->insert('Orders', $Order);
            $OrderID = $this->db->insert_id();
        } else {
            $OrderID = $Order['OrderID'];
        }
        return $OrderID;
    }

    /**
     * 
     * @param type $PharmacyUserID
     * @param type $PatientID
     * @param type $Medications
     * @param type $RefOrderID
     * @return type
     */
    function create_delivery_order($PharmacyUserID, $PatientID, $Medications = array(), $RefOrderID = NULL) {

        $QXOrder = $this->app->get_row('Orders', '*', ['OrderID' => $RefOrderID]);

        $OrderGUID = guid();
        $Order = [
            'OrderGUID' => $OrderGUID,
            'OrderSID' => $this->create_order_sid("DX"),
            'RefOrderID' => $RefOrderID,
            'OrderType' => 'DELIVERY_ORDER',
            'CreatedAt' => DATETIME,
            'CreatedBy' => $PharmacyUserID,
            'UpdatedAt' => DATETIME,
            'UpdatedBy' => $PharmacyUserID,
            'Status' => 'DRAFT',
            'UserID' => $QXOrder['UserID'],
            'PharmacyUserID' => $QXOrder['PharmacyUserID'],
            'SubTotal' => $QXOrder['SubTotal'],
            'TaxRate' => $QXOrder['TaxRate'],
            'Tax' => $QXOrder['Tax'],
            'DiscountAmount' => $QXOrder['DiscountAmount'],
            'DiscountCode' => $QXOrder['DiscountCode'],
            'GrandTotal' => $QXOrder['GrandTotal'],
        ];
        $this->db->insert('Orders', $Order);
        $OrderID = $this->db->insert_id();

        if (!empty($Medications)) {
            foreach ($Medications as $MedicationDetail) {
                $OrderMedication = [
                    'OrderMedicationGUID' => guid(),
                    'OrderID' => $OrderID,
                    'MedicationID' => $MedicationDetail['MedicationID'],
                    'Price' => $MedicationDetail['Price'],
                    'DispensingFee' => $MedicationDetail['DispensingFee'],
                    'AdditionalFee' => $MedicationDetail['AdditionalFee'],
                    'Discount' => $MedicationDetail['Discount'],
                    'IsTaxApplicable' => $MedicationDetail['IsTaxApplicable'],
                    'AmountDue' => $MedicationDetail['AmountDue'],
                ];
                $this->db->insert('OrderMedications', encrypt_decrypt($OrderMedication));
            }
        }
        return $OrderID;
    }

    /**
     * 
     * @param type $UserID
     * @param type $Type
     * @param type $IDRequired
     * @return type
     */
    public function find_draft_order_by_type($UserID, $Type, $IDRequired = FALSE) {
        if ($IDRequired) {
            $this->db->select('O.OrderID');
        }
        $this->db->select('O.OrderGUID, O.OrderSID, O.OrderType');
        $this->db->where('O.OrderType', $Type);
        $this->db->where('O.UserID', $UserID);
        $this->db->where('O.Status', 'DRAFT');
        $this->db->from('Orders' . ' AS O');
        $Query = $this->db->get();
        $Order = $Query->row_array();
        return $Order;
    }

    /**
     * 
     * @param type $UserID
     * @param type $Medications
     * @return boolean
     */
    public function update_medications($UserID, $Medications) {

        foreach ($Medications as $Medication) {
            $MedicationGUID = safe_array_key($Medication, 'MedicationGUID', "");
            $MedicationName = safe_array_key($Medication, 'MedicationName', "");
            $MedicationSID = safe_array_key($Medication, 'MedicationSID', NULL);
            $RefillAllowed = safe_array_key($Medication, 'RefillAllowed', "0");
            $Strength = safe_array_key($Medication, 'Strength', "");
            $Quantity = safe_array_key($Medication, 'Quantity', "");

            if ($MedicationGUID) {
                //update
                $Medication = [
                    'MedicationSID' => $MedicationSID,
                    'MedicationName' => $MedicationName,
                    'RefillAllowed' => $RefillAllowed,
                    'Strength' => $Strength,
                    'Quantity' => $Quantity,
                    'VerifyStatus' => 'VERIFIED',
                    'UpdatedAt' => DATETIME,
                ];
                $this->db->update('Medications', encrypt_decrypt($Medication), [
                    'MedicationGUID' => $MedicationGUID,
                ]);
            } else {
                //insert
                $Medication = [
                    'MedicationGUID' => guid(),
                    'MedicationSID' => $MedicationSID,
                    'UserID' => $UserID,
                    'MedicationName' => $MedicationName,
                    'RefillAllowed' => $RefillAllowed,
                    'Strength' => $Strength,
                    'Quantity' => $Quantity,
                    'VerifyStatus' => 'VERIFIED',
                    'InProcess' => 0,
                    'CreatedAt' => DATETIME,
                    'UpdatedAt' => DATETIME,
                ];
                $this->db->insert('Medications', encrypt_decrypt($Medication));
            }
        }
        return TRUE;
    }

}
