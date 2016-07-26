<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter 
 */
class Heartbeat_model extends CI_Model {

    public function __construct() {
        // Call the CI_Model constructor
        parent::__construct();
    }

    /**
     * 
     * @param type $DeviceTypeID
     * @param type $DeviceToken
     * @param type $UniqueDeviceToken
     * @return type
     */
    public function create($DeviceTypeID, $DeviceToken, $UniqueDeviceToken) {

        $Token = $this->app->get_row('Tokens', 'TokenGUID, EncryptionKey, EncryptionIV, IFNULL(TempJsonData,"") AS TempJsonData', [
            'DeviceTypeID' => $DeviceTypeID,
            'UniqueDeviceToken' => $UniqueDeviceToken
        ]);
        if (empty($Token)) {
            $Token = [
                "TokenGUID" => guid(),
                "EncryptionKey" => random_string('alnum', 16),
                "EncryptionIV" => random_string('alnum', 16),
                "DeviceTypeID" => $DeviceTypeID,
                "CreatedDate" => gmdate("Y-m-d H:i:s"),
                "TempJsonData" => "",
            ];

            if (!is_null($DeviceToken)) {
                $Token["DeviceID"] = $DeviceToken;
                $Token["DeviceToken"] = $DeviceToken;
            }

            if (!is_null($UniqueDeviceToken)) {
                $Token["UniqueDeviceToken"] = $UniqueDeviceToken;
            }
            $this->db->insert('Tokens', $Token);
        }

        $Return = [
            'TokenGUID' => $Token['TokenGUID'],
            'EncryptionKey' => $Token['EncryptionKey'],
            'EncryptionIV' => $Token['EncryptionIV'],
            'StripePublishableKey' => STRIPE_PUBLISHABLE_KEY,
            'TempJsonData' => $Token['TempJsonData']!=""?json_decode($Token['TempJsonData'],TRUE):"",
        ];
        return $Return;
    }

    function update_token($TokenGUID, $TempJsonData) {
        $this->db->update('Tokens', [
            "TempJsonData" => json_encode($TempJsonData),
                ], [
            "TokenGUID" => $TokenGUID,        
        ]);
    }
    
    function get_token($TokenGUID) {
        $Token = $this->app->get_row('Tokens', 'TokenGUID, EncryptionKey, EncryptionIV, TempJsonData', [
            'TokenGUID' => $TokenGUID,
        ]);
        
        $Return = [
            'TokenGUID' => $Token['TokenGUID'],
            'EncryptionKey' => $Token['EncryptionKey'],
            'EncryptionIV' => $Token['EncryptionIV'],
            'StripePublishableKey' => STRIPE_PUBLISHABLE_KEY,
            'TempJsonData' => is_null($Token['TempJsonData'])?"":json_decode($Token['TempJsonData']),
        ];
        return $Return;
    }

}
