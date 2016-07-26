<?php

/**
 * Order_model will use to manage all order related db stuffs
 *
 * @author nitins
 */
class Allergy_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 
     * @param type $UserID
     * @param type $AllergyText
     * @return type
     */
    public function create_allergy($UserID, $AllergyText) {
        
        $Allergy = $this->allergy_model->find_allergy_by_text($AllergyText);
        $UserAllergyGUID = guid();
        if(!is_null($Allergy)){
            $NewAllergy =[
                'UserAllergyGUID'=> $UserAllergyGUID,
                'UserID' => $UserID,  
                'AllergyID' => $Allergy['AllergyID'], 
            ];
        }else{
            $NewAllergy =[
                'UserAllergyGUID'=> $UserAllergyGUID,
                'UserID' => $UserID,  
                'AllergyText' => $AllergyText,  
            ];          
        }        
        $this->db->insert('UserAllergies', $NewAllergy);
        return [
            'AllergyText'=>$AllergyText,
            'UserAllergyGUID'=>$UserAllergyGUID,
        ];        
    }

    /**
     * UPdate Allergy
     * @param type $Allergy
     * @return boolean
     */
    public function update_allergy($UserAllergyGUID, $AllergyText) {        
        $Allergy = $this->allergy_model->find_allergy_by_text($AllergyText);
        if(!is_null($Allergy)){
            $NewAllergy =[
                'AllergyID' => $Allergy['AllergyID'], 
            ];
        }else{
            $NewAllergy =[
                'AllergyText' => $AllergyText,  
            ];          
        }        
        $this->db->update('UserAllergies', $NewAllergy, [
            'UserAllergyGUID'=>$UserAllergyGUID
        ]);        
        return TRUE;        
    }
    
    
    /**
     * UPdate Allergy
     * @param type $Allergy
     * @return boolean
     */
    public function delete_allergy($UserAllergyGUID) {        
        $this->db->delete('UserAllergies', [
            'UserAllergyGUID'=>$UserAllergyGUID
        ]);        
        return TRUE;        
    }
    
    
    /**
     * Get master allergy List
     * @return array
     */
    function master_allergies() {        
        $this->db->select('AM.AllergyID, AM.AllergyText');
        $this->db->order_by('AM.AllergyText', 'ASC');
        $query = $this->db->get('AllergyMaster AM');
        $result = $query->result_array();
        return $result;
    }

    /**
     * Get allergy List
     * @param type $UserID
     * @param type $limit
     * @param type $offset
     * @return array
     */
    function allergies($UserID, $limit = NULL, $offset = 0) {        
        $this->db->select('UA.UserAllergyGUID');        
        $this->db->select("IFNULL(AM.AllergyText, UA.AllergyText) AS AllergyText", FALSE);        
        $this->db->join('AllergyMaster AM', 'AM.AllergyID=UA.AllergyID', 'LEFT');        
        $this->db->where('UA.UserID', $UserID);        
        if (!empty($limit) && (!empty($offset) || $offset == 0)) {
            $this->db->limit($limit, $offset);
        } 
        $query = $this->db->get('UserAllergies UA');
        $result = $query->result_array();
        return $result;
    }
    
    /**
     * find allergy by text
     * @param type $allergy_text
     * @return type
     */
    function find_allergy_by_text($AllergyText) {
        $this->db->select('AllergyID, AllergyText');
        $this->db->where('AllergyText', strtolower($AllergyText));
        return $this->db->get('AllergyMaster')->row_array();
    }
    
    
    function is_allergy_added($UserID, $AllergyText){
        $this->db->select('UA.UserAllergyID');        
        $this->db->where('UA.UserID', $UserID);
        $this->db->where("("
                . "(UA.AllergyText='".$AllergyText."') "
                . "OR (UA.AllergyID IN("
                . "SELECT AllergyID FROM AllergyMaster WHERE AllergyText='".$AllergyText."'"
                . ")"
                . ")"
                . ")", NULL, TRUE);
        $query = $this->db->get('UserAllergies UA');
        $result = $query->num_rows(); 
        return $result;            
    }
    
    

}
