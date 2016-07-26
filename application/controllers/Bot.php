<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Bot extends CI_Controller {

        public function __construct(){
            parent::__construct();
            $this->load->helper(array('form', 'url'));
            	
        }

        public function index(){
            
        } 
        
        public function log(){
           $Post = $this->input->post();
           log_message('error', json_encode($Post));
           echo "Done";
        }      
        
}
?>

