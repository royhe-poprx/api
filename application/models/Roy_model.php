<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 */
class Roy_model extends CI_Model
{

    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
    }

    public function create_roy($RoyName) {
        $Roy = [
            "RoyName"=>$RoyName,
        ];
        $this->db->insert('roy', $Roy);
        $ID = $this->db->insert_id();
        return $ID;
    }

    public function update_roy($RoyID,$RoyName) {
        $Roy = [
            "RoyName"=>$RoyName,
        ];
        $this->db->update('roy', $Roy, [
            'RoyID' => $RoyID,
        ]);
    }

    public function delete_roy($RoyID) {
        $this->db->where('RoyID',$RoyID);
        $this->db->delete('roy');
        return TRUE;
    }

    function api_invoke() {

        //function invoke in API
        //do the logic for API needs
        $this->_self_use_function();
        return null;
    }

    function _self_use_function(){

        //class internal user function
        echo '123';
    }
}

?>