<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Description of insurancecard_mode
 *
 * @author nitins
 */
class Insurance_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    /**
     * create_insurance
     * @param type $ActiveUserID
     * @param type $IsImageType
     * @param type $FrontImage
     * @param type $BackImage
     * @param type $InsuranceProvider
     * @param type $EmployerGroupName
     * @param type $ContractPlanNumber
     * @param type $GroupNumber
     * @param type $EmployeeStudentNumber
     * @param type $Comment
     * @return boolean
     */
    
    public function create_insurance($ActiveUserID, $IsImageType, $FrontImage, $BackImage, 
            $InsuranceProvider, $EmployerGroupName, $ContractPlanNumber, $GroupNumber, 
            $EmployeeStudentNumber, $Comment){
        if($IsImageType==1){
            $Insurance = [
                "InsuranceCardGUID"=>guid(),
                "UserID"=>$ActiveUserID,
                "IsImage"=>1,
                "FrontImageGUID"=>$FrontImage,
                "BackImageGUID"=>$BackImage,
                "IsDeleted"=>0,
                "IsActive"=>1,                
            ];
        }else{
            $Insurance = [
                "InsuranceCardGUID"=>guid(),
                "UserID"=>$ActiveUserID,
                "IsImage"=>0,                
                "IsDeleted"=>0,
                "IsActive"=>1,                
            ];
            
            if(!is_null($InsuranceProvider)){
                $Insurance['InsuranceProvider'] = $InsuranceProvider;                
            }
            if(!is_null($EmployerGroupName)){
                $Insurance['EmployerGroupName'] = $EmployerGroupName;                
            }
            if(!is_null($ContractPlanNumber)){
                $Insurance['ContractPlanNumber'] = $ContractPlanNumber;                
            }
            if(!is_null($GroupNumber)){
                $Insurance['GroupNumber'] = $GroupNumber;                
            }
            if(!is_null($EmployeeStudentNumber)){
                $Insurance['EmployeeStudentNumber'] = $EmployeeStudentNumber;                
            }
            if(!is_null($Comment)){
                $Insurance['Comment'] = $Comment;                
            }
        }        
        $Insurance = encrypt_decrypt($Insurance);
        $this->db->insert('InsuranceCards', $Insurance);
        return $Insurance['InsuranceCardGUID'];
    }
    
    /**
     * update_insurance
     * @param type $InsuranceCardGUID
     * @param type $IsImageType
     * @param type $FrontImage
     * @param type $BackImage
     * @param type $InsuranceProvider
     * @param type $EmployerGroupName
     * @param type $ContractPlanNumber
     * @param type $GroupNumber
     * @param type $EmployeeStudentNumber
     * @param type $Comment
     * @return boolean
     */
    public function update_insurance($InsuranceCardGUID, $IsImageType, $FrontImage, $BackImage, 
            $InsuranceProvider, $EmployerGroupName, $ContractPlanNumber, $GroupNumber, 
            $EmployeeStudentNumber, $Comment){
        if($IsImageType==1){
            $Insurance = [
                "IsImage"=>1,
                "FrontImageGUID"=>$FrontImage,
                "BackImageGUID"=>$BackImage,                               
            ];
        }else{
            $Insurance = [
                "IsImage"=>0,
            ];
            
            if(!is_null($InsuranceProvider)){
                $Insurance['InsuranceProvider'] = $InsuranceProvider;                
            }
            if(!is_null($EmployerGroupName)){
                $Insurance['EmployerGroupName'] = $EmployerGroupName;                
            }
            if(!is_null($ContractPlanNumber)){
                $Insurance['ContractPlanNumber'] = $ContractPlanNumber;                
            }
            if(!is_null($GroupNumber)){
                $Insurance['GroupNumber'] = $GroupNumber;                
            }
            if(!is_null($EmployeeStudentNumber)){
                $Insurance['EmployeeStudentNumber'] = $EmployeeStudentNumber;                
            }
            if(!is_null($Comment)){
                $Insurance['Comment'] = $Comment;                
            }
        }        
        $Insurance = encrypt_decrypt($Insurance);
        $this->db->update('InsuranceCards', $Insurance, array(
            'InsuranceCardGUID'=>$InsuranceCardGUID,
        ));
        return $InsuranceCardGUID;
    }
    
    /**
     * get_insurance_by_id
     * @param type $InsuranceCardID
     * @return type
     */
    public function get_insurance_by_id($InsuranceCardID) {
        $this->db->select('I.InsuranceCardGUID, I.IsImage, I.FrontImageGUID, I.BackImageGUID, I.InsuranceProvider, I.EmployerGroupName, I.ContractPlanNumber, I.GroupNumber, I.EmployeeStudentNumber');
        $this->db->select('IFNULL(I.Comment,"") AS Comment', FALSE);
        //$this->db->join('Users' .' U', "I.UserID=U.UserID");
        $this->db->where('I.InsuranceCardID', $InsuranceCardID);
        $this->db->where('I.IsDeleted', '0');
        $this->db->where('I.IsActive', '1');
        $this->db->from('InsuranceCards' .' AS I');
        $query = $this->db->get();
        $Insurance = $query->row_array();
        $Insurance = encrypt_decrypt($Insurance, 1);
        return $Insurance;
    }

    /**
     * get_insurance_by_guid
     * @param type $InsuranceCardGUID
     * @return type
     */
    public function get_insurance_by_guid($InsuranceCardGUID) {
        $this->db->select('I.InsuranceCardGUID, I.IsImage, I.FrontImageGUID, I.BackImageGUID, I.InsuranceProvider, I.EmployerGroupName, I.ContractPlanNumber, I.GroupNumber, I.EmployeeStudentNumber');
        $this->db->select('IFNULL(I.Comment,"") AS Comment', FALSE);
        //$this->db->join('Users' .' U', "I.UserID=U.UserID");
        $this->db->where('I.InsuranceCardGUID', $InsuranceCardGUID);
        $this->db->where('I.IsDeleted', '0');
        $this->db->where('I.IsActive', '1');
        $this->db->from('InsuranceCards' .' AS I');
        $query = $this->db->get();
        $Insurance = $query->row_array();
        $Insurance = encrypt_decrypt($Insurance, 1);
        return $Insurance;
    }
    
    /**
     * insurances
     * @param type $UserID
     * @return type
     */
    public function insurances($UserID){
        $this->db->select('I.InsuranceCardGUID, I.IsImage, I.FrontImageGUID, I.BackImageGUID, I.InsuranceProvider, I.EmployerGroupName, I.ContractPlanNumber, I.GroupNumber, I.EmployeeStudentNumber');
        $this->db->select('IFNULL(I.Comment,"") AS Comment', FALSE);
        //$this->db->join('Users' .' U', "I.UserID=U.UserID");
        $this->db->where('I.UserID', $UserID);
        $this->db->where('I.IsDeleted', '0');
        $this->db->where('I.IsActive', '1');
        //Uncommect below line in case card of dependent also needed
        //$this->db->or_where("I.UserID IN(SELECT DependentUserID FROM ".USERDEPENDENTS." WHERE UserID='".$UserID."')", NULL, FALSE);
        $this->db->from('InsuranceCards' .' AS I');
        $query = $this->db->get();
        //return $query->result_array();
        $Insurances = $query->result_array();
        $Insurances = encrypt_decrypt($Insurances, 1);
        return $Insurances;
    }
    
    
    /**
     * delete_insurance
     * @param type $InsuranceCardGUID
     * @return boolean
     */
    public function delete_insurance($InsuranceCardGUID){
        $where = [
            "InsuranceCardGUID"=>$InsuranceCardGUID,                                
        ];        
        $this->db->delete('InsuranceCards', $where);
        return TRUE;
    }
}
