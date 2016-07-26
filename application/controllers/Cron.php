<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->input->is_cli_request())
            show_error('Access denied', 403);
        $this->load->helper(array('form', 'url'));
    }

    public function index() {
        
    }

    public function process_notifications() {
        $Worker = new GearmanWorker();
        $Worker->addServer();
        
        
        
        $Worker->addFunction("NotificationToClients", function(GearmanJob $Job) {
            $InputData = json_decode($Job->workload(), true);
            
            $FromUserID = safe_array_key($InputData, 'FromUserID', NULL);
            $ToUserID = safe_array_key($InputData, 'ToUserID', NULL);
            $Type = safe_array_key($InputData, 'Type', NULL);
            $TypeID = safe_array_key($InputData, 'TypeID', NULL);            
            if (!empty($FromUserID) && !empty($ToUserID) && !empty($Type) && !empty($TypeID)) {
                $this->app->cron_notify($FromUserID, $ToUserID, $Type, $TypeID);
            }
        });

        
        $Worker->addFunction("PasswordRecoveryEmail", function(GearmanJob $Job) {
            $InputData = json_decode($Job->workload(), true);
            
            $Email = safe_array_key($InputData, 'Email', "");
            $FirstName = safe_array_key($InputData, 'FirstName', "There");
            $LastName = safe_array_key($InputData, 'LastName', "");
            $TmpPass = safe_array_key($InputData, 'TmpPass', "");

            if (!empty($Email)) {
                $this->app->cron_passwrod_recovery_email($Email, $FirstName, $LastName, $TmpPass);
            }
        });

        
        $Worker->addFunction("NewAccountCreated", function(GearmanJob $Job) {
            $InputData = json_decode($Job->workload(), true);
            
            $Email = safe_array_key($InputData, 'Email', "");
            $FirstName = safe_array_key($InputData, 'FirstName', "");
            $LastName = safe_array_key($InputData, 'LastName', "");
            $TmpPass = safe_array_key($InputData, 'TmpPass', "");

            if (!empty($Email)) {
                $this->app->cron_new_account_created_email($Email, $FirstName, $LastName, $TmpPass);
            }
        });
        
        $Worker->addFunction("PostToMailChimpList", function(GearmanJob $Job) {
            $Data = json_decode($Job->workload(), true);
            $mailchimp_post = array(
                "email_address" => $Data['Email'],
                "status" => "subscribed",
                "merge_fields" => array(
                    "FNAME" => $Data['FirstName'],
                    "LNAME" => $Data['LastName'],
                    "MOBILE" => $Data['PhoneNumber'],
                    "DEVICETYPE" => $Data['DeviceType']
                )
            );
            post_to_mailchimp_list($mailchimp_post);
        });
        
        
        $Worker->addFunction("TestWorker", function(GearmanJob $Job) {
            $Data = json_decode($Job->workload(), true);
            $this->load->library('email');
            $this->email->from('info@poprx.ca', 'PopRx Team', 'info@poprx.ca');
            $this->email->to(array('pradeep@poprx.ca'));
            $this->email->subject('Email Test-' . DATETIME);
            $this->email->message('Testing the email class.');
            $this->email->send();
        });
        
        while ($Worker->work());
    }

}
?>

