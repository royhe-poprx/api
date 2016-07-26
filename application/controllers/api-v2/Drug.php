<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Example
 * This Class used for REST API
 * (All THE API CAN BE USED THROUGH POST METHODS)
 * @package		CodeIgniter
 * @subpackage	Rest Server
 * @category	Controller
 * @author		Phil Sturgeon
 * @link		http://philsturgeon.co.uk/code/
 */
// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require_once APPPATH . '/libraries/REST_Controller.php';

class Drug extends REST_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index_get() {
        $str = $this->input->get('keyword');
        $Return['ResponseCode'] = 200;
        $Return['Message'] = 'Success';
        $Return['Data'] = array();
        $Return['ServiceName'] = 'drug';

        if (!empty($str)) {
            $this->db->select('MEDICINE_NAME as name, RXSTRING');
            //$this->db->select('RXSTRING as name');
            $this->db->like('MEDICINE_NAME', $str, 'after');
            $this->db->or_like('RXSTRING', $str, 'after');
            $this->db->select('PROD_MEDICINES_PRIKEY as code');
            $this->db->limit(10);
            $Return['Data'] = $this->db->get(PILLBOX)->result_array();
            //$Return['Query'] = $this->db->last_query();
        }
        $this->response($Return);
    }

    public function index_post() {
        if(WEB_PORTAL==FALSE)
            $this->post_data = JsonDecryption($this->post(),TRUE);
        else
            $this->post_data = $this->post();
        
        /* Gather Inputs - ENDS */
        $this->log_id = $this->app->log_service($this->post_data);
        
        $str = isset($this->post_data['keyword']) ? $this->post_data['keyword'] : NULL;
        $Return['ResponseCode'] = 200;
        $Return['Message'] = 'Success';
        $Return['Data'] = array();
        $Return['ServiceName'] = 'drug';

        if (!empty($str)) {
            $this->db->select('MEDICINE_NAME as name, RXSTRING');
            //$this->db->select('RXSTRING as name');
            $this->db->like('MEDICINE_NAME', $str, 'after');
            $this->db->or_like('RXSTRING', $str, 'after');
            $this->db->select('PROD_MEDICINES_PRIKEY as code');
            $this->db->limit(10);
            $Return['Data'] = $this->db->get(PILLBOX)->result_array();
            //$Return['Query'] = $this->db->last_query();
        }
        $this->app->update_log($this->log_id, $Return);
        $this->response($Return);
    }

}
