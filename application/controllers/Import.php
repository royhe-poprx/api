<?php

/**
 * Php Log class
 *
 * Display the php log
 *
 * @category	Log
 * @author		NicolÃ¡s Bistolfi
 * @link		https://dl.dropboxusercontent.com/u/20596750/code/php/log.php
 */
class Import extends CI_Controller {

    /**
     * 	Class constructor
     */
    function __construct() {
        parent::__construct();
        $this->load->model('upload_model');
        $this->load->model('allergy_model');
        $this->load->model('profile_model');
        $this->load->model('insurance_model');
    }

    /**
     * index: Shows the php error log
     * @access public
     */
    public function index($Offset = 0, $UserGUID = NULL) {
//        if ($UserGUID) {
//            $Content = file_get_contents("https://old.poprx.ca/cexport/patients/$Offset/JSON/$UserGUID");
//        } else {
//            $Content = file_get_contents("https://old.poprx.ca/cexport/patients/$Offset/JSON");
//        }
//        $ContentArray = json_decode($Content, TRUE);
//        foreach ($ContentArray as $User) {
//            $U = $this->app->get_row('Users', 'UserID', ['Email' => $User['Email']]);
//            if(!empty($U)){
//                $this->app->delete_user($U['UserID']);
//            }
//            //Insert User table
//            $NewUser = [
//                "UserGUID" => $User['UserGUID'],
//                "UserTypeID" => $User['UserTypeID'],
//                "Email" => strtolower($User['Email']),
//                "SourceID" => $User['SourceID'],
//                "DeviceTypeID" => $User['DeviceTypeID'],
//                "StatusID" => $User['StatusID'],
//                "CreatedDate" => $User['CreatedDate'],
//                "FirstName" => $User['FirstName'],
//                "FirstNameSearch" => search_encryption($User['FirstName']),
//                "LastName" => $User['LastName'],
//                "LastNameSearch" => search_encryption($User['LastName']),
//                "PhoneNumber" => $User['PhoneNumber'],
//                "DOB" => $User['DOB'],
//                "Gender" => $User['Gender'],
//                "PhinNumber" => $User['PhinNumber'],
//                "Latitude" => $User['Latitude'],
//                "Longitude" => $User['Longitude'],
//                "StripeCustomerID" => $User['StripeCustomerID'],
//                "IsProfileCompleted" => "1",
//                "ProfilePicture" => $this->upload_model->save_image_from_old($User['ProfilePicture']),
//            ];
//            $NewUser = encrypt_decrypt($NewUser);
//            $this->db->insert('Users', $NewUser);
//            $NewUserID = $this->db->insert_id();
//
//            foreach ($User['UserLogins'] as $UserLoginRow) {
//                $UserLogin = [
//                    "UserID" => $NewUserID,
//                    "LoginKeyword" => $UserLoginRow['LoginKeyword'],
//                    "SourceID" => $UserLoginRow['SourceID'],
//                    "Password" => $UserLoginRow['Password'],
//                ];
//                $UserLogin = encrypt_decrypt($UserLogin);
//                $this->db->insert('UserLogins', $UserLogin);
//            }
//
//            foreach ($User['UserCards'] as $UserCardRow) {
//                $this->db->insert('UserCards', array(
//                    'UserCardGUID' => $UserCardRow['UserCardGUID'],
//                    'UserID' => $NewUserID,
//                    'StripeCardID' => $UserCardRow['StripeCardID'],
//                    'Name' => $UserCardRow['Name'],
//                    'CardType' => $UserCardRow['CardType'],
//                    'CardExpMonth' => $UserCardRow['CardExpMonth'],
//                    'CardExpYear' => $UserCardRow['CardExpYear'],
//                    'CardLast4' => $UserCardRow['CardLast4'],
//                    'OtherData' => "",
//                    'IsDefault' => $UserCardRow['IsDefault'],
//                    'CreatedAt' => DATETIME,
//                    'UpdatedAt' => DATETIME,
//                ));
//            }
//
//
//            foreach ($User['UserAllergies'] as $UserAllergyRow) {
//                $this->allergy_model->create_allergy($NewUserID, $UserAllergyRow['AllergyText']);
//            }
//
//            foreach ($User['InsuranceCards'] as $InsuranceCardRow) {
//                $FImg = $this->upload_model->save_image_from_old($InsuranceCardRow['FrontImageGUID']);
//                $BImg = $this->upload_model->save_image_from_old($InsuranceCardRow['BackImageGUID']);
//                $this->insurance_model->create_insurance($NewUserID, $InsuranceCardRow['IsImage'], $FImg, $BImg, $InsuranceCardRow['InsuranceProvider'], $InsuranceCardRow['EmployerGroupName'], $InsuranceCardRow['ContractPlanNumber'], $InsuranceCardRow['GroupNumber'], $InsuranceCardRow['EmployeeStudentNumber'], $InsuranceCardRow['Comment']);
//            }
//
//            foreach ($User['Medications'] as $Medication) {
//                $MedImg = [];
//                foreach ($Medication['MedicationImages'] as $MedicationImage) {
//                    $MedImg[] = $this->upload_model->save_image_from_old($MedicationImage['ImageGUID']);
//                }
//                if (!empty($MedImg)) {
//                    $MedicationImages = implode(",", $MedImg);
//                } else {
//                    $MedicationImages = "";
//                }
//                $Med = [
//                    'UserID' => $NewUserID,
//                    'MedicationGUID' => guid(),
//                    'MedicationSID' => $Medication['MedicationSID'],
//                    'MedicationName' => $Medication['MedicationName'],
//                    'RefillAllowed' => $Medication['RefillAllowed'],
//                    'Strength' => $Medication['Strength'],
//                    'Quantity' => $Medication['Quantity'],
//                    'VerifyStatus' => 'VERIFIED',
//                    'MedicationImages' => $MedicationImages,
//                    'CreatedAt' => DATETIME,
//                ];
//                $this->db->insert('Medications', encrypt_decrypt($Med));
//            }
//
//            foreach ($User['UserDependents'] as $UserDependentRow) {
//                $Img = $this->upload_model->save_image_from_old($UserDependentRow['ProfilePicture']);
//                $ProfileID = $this->profile_model->create_profile($NewUserID, $UserDependentRow['FirstName'], $UserDependentRow['LastName'], $UserDependentRow['Gender'], $UserDependentRow['DOB'], $UserDependentRow['PhinNumber'], $UserDependentRow['PhoneNumber'], $Img);
//                foreach ($UserDependentRow['UserAllergies'] as $UserAllergyRow) {
//                    $this->allergy_model->create_allergy($ProfileID, $UserAllergyRow['AllergyText']);
//                }
//                foreach ($UserDependentRow['InsuranceCards'] as $InsuranceCardRow) {
//                    $FImg = $this->upload_model->save_image_from_old($InsuranceCardRow['FrontImageGUID']);
//                    $BImg = $this->upload_model->save_image_from_old($InsuranceCardRow['BackImageGUID']);
//                    $this->insurance_model->create_insurance($ProfileID, $InsuranceCardRow['IsImage'], $FImg, $BImg, $InsuranceCardRow['InsuranceProvider'], $InsuranceCardRow['EmployerGroupName'], $InsuranceCardRow['ContractPlanNumber'], $InsuranceCardRow['GroupNumber'], $InsuranceCardRow['EmployeeStudentNumber'], $InsuranceCardRow['Comment']);
//                }
//                foreach ($UserDependentRow['Medications'] as $Medication) {
//                    $MedImg = [];
//                    foreach ($Medication['MedicationImages'] as $MedicationImage) {
//                        $MedImg[] = $this->upload_model->save_image_from_old($MedicationImage['ImageGUID']);
//                    }
//                    if (!empty($MedImg)) {
//                        $MedicationImages = implode(",", $MedImg);
//                    } else {
//                        $MedicationImages = "";
//                    }
//                    $Med = [
//                        'UserID' => $ProfileID,
//                        'MedicationGUID' => guid(),
//                        'MedicationSID' => $Medication['MedicationSID'],
//                        'MedicationName' => $Medication['MedicationName'],
//                        'RefillAllowed' => $Medication['RefillAllowed'],
//                        'Strength' => $Medication['Strength'],
//                        'Quantity' => $Medication['Quantity'],
//                        'VerifyStatus' => 'VERIFIED',
//                        'MedicationImages' => $MedicationImages,
//                        'CreatedAt' => DATETIME,
//                    ];
//                    $this->db->insert('Medications', encrypt_decrypt($Med));
//                }
//            }
//            echo "User:".$User['FirstName']."|".$User['LastName']."|".$User['Email']."|".$User['UserID']."|";
//            echo "<br>";
//        }
//        echo "Imported " . count($ContentArray) . " Users from old";
    }

    function update_medication_pharmacist_id() {
        $Medications = $this->app->get_rows('Medications', 'MedicationID, UserID');
        //print_r($Medications);die();
        foreach ($Medications as $Medication) {
            $User = $this->app->get_row('Users', 'PharmacistID', ['UserID' => $Medication['UserID']]);
            $PharmacistID = safe_array_key($User, 'PharmacistID', NULL);
            $this->db->update('Medications', [
                "PharmacistID" => $PharmacistID,
                    ], [
                "MedicationID" => $Medication['MedicationID'],
            ]);
        }
    }

}

?>