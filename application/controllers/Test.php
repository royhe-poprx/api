<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
    }

    public function index() {
        
    }
    public function android_push(){
        $device_token="APA91bEDn9v7UWx9vM_eqsBZAJmvaJ6-oMM4bMgp_x2gUvYSSk0CDwGZMcFKrEJsgAgeKHZymNL19ntah-wMlPfi09Vr0IZ53h9wNk57dg5RCNePrgja36A";
        $message="Hello";
        $badge=1;
        $extra = [];
        push_notification_android($device_token, $message, $badge, $extra);
    }

    public function push($device_token = "6137eb539a9f0a7c90df263e313765fae57925254d98465b0fb03a9b884c8fe5", $message = "Hiasdfadsf", $type = "OrderStatusUpdate", $type_id = "asdf-asdf-asdf-asdf") {
        $badge = '1';
        $Extra = array(
            'Type' => $type,
            'TypeGUID' => $type_id,
        );
        push_notification_iphone($device_token, $message, $badge, $Extra);
    }

    public function generate_referral_code() {
        $Users = $this->app->get_rows('Users', 'UserID, FirstName, ReferralCode');
        $UpdateUsers = array();
        foreach ($Users as $User) {
            //if(is_null($User['ReferralCode'])){
            $UpdateUsers[] = [
                'ReferralCode' => strtoupper($User['FirstName']) . random_string('nozero', 5),
                'UserID' => $User['UserID'],
            ];
            //}
        }
        if (!empty($UpdateUsers)) {
            $this->db->update_batch('Users', $UpdateUsers, 'UserID');
        }
    }

    public function testt() {
        $Data['email'] = "pradeep@vinfotech.com";
        process_in_backgroud("TestWorker", $Data);
    }

    public function timetext() {
        //$Timezone = "Asia/Calcutta";
        //$Timezone = "America/Toronto";
        $Timezone = "MST";
        $Date = new DateTime();
        $Date->setTimezone(new DateTimeZone($Timezone));
        $TodayDay = strtoupper($Date->format("l"));
        $SecondsCompleted = ConverTimeIntoSeconds($Date->format("H:i"));
        
        $Interval = new DateInterval("P1D"); // 1 month
        $Occurrences = 6;
        $Period = new DatePeriod($Date, $Interval, $Occurrences);
        foreach ($Period as $dt) {
            echo $dt->format("Y-m-d H:i:s") . "-" . $dt->format("l") . "<br>";
        }
    }
    
    function emailtest(){
        $this->load->library('email');      
        $this->email->from('info@poprx.ca', 'PopRx Team', 'info@poprx.ca');
        $this->email->to(array('pradeep@poprx.ca','pradeep@vinfotech.com'));
        $this->email->subject('Email Test');
        $this->email->message('Testing the email class.'.DATETIME);
        $this->email->send();
        echo $this->email->print_debugger();
    }

}
?>

