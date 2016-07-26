<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->load->view('home');
    }

    public function stripe_redirect() {
        error_reporting(E_ALL);
        ini_set('display_errors',1);
        $StripeConnectCode = $this->input->get('code');
        $state = $this->input->get('state');
        list($PharmacyGUID, $HostOrigin) = explode("$$", $state);
        $User = $this->app->get_row('Users', 'UserID', ['UserGUID' => $PharmacyGUID]);
        $this->load->library('curl');
        $TokenRequestBody = array(
            'client_secret' => STRIPE_SECRET,
            'grant_type' => 'authorization_code',
            'client_id' => STRIPE_CLIENT_ID,
            'code' => $StripeConnectCode,
        );
        $this->curl->ssl(FALSE);
        $Response = $this->curl->_simple_call('post', TOKEN_URI, $TokenRequestBody);
        $Response = json_decode($Response, TRUE);
        //print_r($Response);die();
        $GatewayPrivateKey = safe_array_key($Response, 'access_token', '');
        $GatewayPublicKey = safe_array_key($Response, 'stripe_publishable_key', '');
        $GatewayMerchantID = safe_array_key($Response, 'stripe_user_id', '');

        if ($GatewayPrivateKey && $GatewayPublicKey && $GatewayMerchantID) {
            $this->db->update('Pharmacies', [
                'GatewayMerchantID' => $GatewayMerchantID,
                'GatewayPublicKey' => $GatewayPublicKey,
                'GatewayPrivateKey' => $GatewayPrivateKey,
                    ], [
                'UserID' => safe_array_key($User, 'UserID', 'NULL')
            ]);
            redirect($HostOrigin.'/#/stripe-redirect');
        }else{
            redirect($HostOrigin.'/#/stripe-redirect?error=1');
        }        
    }

}
?>

