<?php

/**
 * Order_model will use to manage all order related db stuffs
 *
 * @author nitins
 */
class Creditcard_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        require_once(APPPATH . 'third_party/stripe-php-3.5.0/init.php');
        \Stripe\Stripe::setApiKey(STRIPE_SECRET);
    }

    /**
     * 
     * @param type $UserID
     * @param type $StripeToken
     * @return type
     */
    function create_creditcard($UserID, $StripeToken) {
        $Profile = $this->app->get_row('Users', 'UserID, FirstName, LastName, Email, StripeCustomerID', [
            "UserID" => $UserID
        ]);
        $StripeCustomerID = $Profile['StripeCustomerID'];
        //customer not added now add customer and first card                        
        try {
            if (empty($StripeCustomerID)) {
                $Response = \Stripe\Customer::create(array(
                            "description" => "Customer for " . $Profile['FirstName'] . " " . $Profile['LastName'],
                            "source" => $StripeToken,
                            "email" => $Profile['Email'],
                ));
                $StripeCustomerID = $Response['id'];
                $this->db->where('UserID', $UserID);
                $this->db->update('Users', array(
                    'StripeCustomerID' => $StripeCustomerID
                ));
                $this->db->insert('UserCards', array(
                    'UserCardGUID' => guid(),
                    'UserID' => $UserID,
                    'StripeCardID' => $Response['sources']['data'][0]['id'],
                    'Name' => is_null($Response['sources']['data'][0]['name']) ? "" : $Response['sources']['data'][0]['name'],
                    'CardType' => $Response['sources']['data'][0]['brand'],
                    'CardExpMonth' => $Response['sources']['data'][0]['exp_month'],
                    'CardExpYear' => $Response['sources']['data'][0]['exp_year'],
                    'CardLast4' => $Response['sources']['data'][0]['last4'],
                    'OtherData' => json_encode($Response),
                    'IsDefault' => "1",
                    'CreatedAt' => DATETIME,
                    'UpdatedAt' => DATETIME,
                ));
            } else {
                $Customer = \Stripe\Customer::retrieve($StripeCustomerID);
                $Response = $Customer->sources->create(array("source" => $StripeToken));
                $Creditcards = $this->creditcard_model->creditcards($UserID);
                $this->db->insert('UserCards', array(
                    'UserCardGUID' => guid(),
                    'UserID' => $UserID,
                    'StripeCardID' => $Response['id'],
                    'Name' => is_null($Response['name']) ? "" : $Response['name'],
                    'CardType' => $Response['brand'],
                    'CardExpMonth' => $Response['exp_month'],
                    'CardExpYear' => $Response['exp_year'],
                    'CardLast4' => $Response['last4'],
                    'OtherData' => json_encode($Response),
                    'IsDefault' => (count($Creditcards) == 0) ? "1" : "0",
                    'CreatedAt' => DATETIME,
                    'UpdatedAt' => DATETIME,
                ));
            }
            $CreditcardID = $this->db->insert_id();
            $StatusCode = 200;
            $Message = "Card has been added for future payments.";
        } catch (Exception $ex) {
            $CreditcardID = 0;
            $StatusCode = 403;
            $Message = $ex->getMessage();
        }
        return [
            "StatusCode" => $StatusCode,
            "Message" => $Message,
            "CreditcardID" => $CreditcardID,
        ];
    }

    /**
     * 
     * @param type $CreditcardGUID
     * @return type
     */
    function delete_creditcard($CreditcardGUID) {
        $Creditcard = $this->app->get_row('UserCards', 'StripeCardID, UserID, IsDefault', [
            "UserCardGUID" => $CreditcardGUID
        ]);

        $Profile = $this->app->get_row('Users', 'UserID, FirstName, LastName, Email, StripeCustomerID', [
            "UserID" => $Creditcard['UserID'],
        ]);

        $StripeCustomerID = $Profile['StripeCustomerID'];
        //customer not added now add customer and first card                        
        try {
            $Customer = \Stripe\Customer::retrieve($StripeCustomerID);
            $Response = $Customer->sources->retrieve($Creditcard['StripeCardID'])->delete();
            $Customer = \Stripe\Customer::retrieve($StripeCustomerID);
            $this->db->where('UserID', $Creditcard['UserID']);
            $this->db->update('UserCards', array('IsDefault' => "0"));

            $this->db->where('StripeCardID', $Customer->default_source);
            $this->db->update('UserCards', array('IsDefault' => "1"));

            $this->db->where('UserCardGUID', $CreditcardGUID);
            $this->db->delete('UserCards');
            $StatusCode = 200;
            $Message = "Card has been deleted.";
        } catch (Exception $ex) {
            $StatusCode = 403;
            $Message = $ex->getMessage();
        }
        return [
            "StatusCode" => $StatusCode,
            "Message" => $Message,
        ];
    }

    /**
     * 
     * @param type $CreditcardGUID
     * @return type
     */
    function mark_default_creditcard($CreditcardGUID) {
        $Creditcard = $this->app->get_row('UserCards', 'StripeCardID, UserID', [
            "UserCardGUID" => $CreditcardGUID
        ]);

        $Profile = $this->app->get_row('Users', 'UserID, FirstName, LastName, Email, StripeCustomerID', [
            "UserID" => $Creditcard['UserID'],
        ]);

        $StripeCustomerID = $Profile['StripeCustomerID'];
        //customer not added now add customer and first card                        
        try {
            $Customer = \Stripe\Customer::retrieve($StripeCustomerID);
            $Customer->default_source = $Creditcard['StripeCardID'];
            $Customer->save();
            $this->db->where('UserID', $Creditcard['UserID']);
            $this->db->update('UserCards', array('IsDefault' => "0"));

            $this->db->where('UserCardGUID', $CreditcardGUID);
            $this->db->update('UserCards', array('IsDefault' => "1"));

            $StatusCode = 200;
            $Message = "Card has been marked default for future payments.";
        } catch (Exception $ex) {
            $StatusCode = 403;
            $Message = $ex->getMessage();
        }
        return [
            "StatusCode" => $StatusCode,
            "Message" => $Message,
        ];
    }

    /**
     * 
     * @param type $UserID
     * @return type
     */
    function creditcards($UserID) {
        $this->db->select('UC.UserCardGUID AS CreditcardGUID, UC.Name, UC.CardType, UC.CardExpMonth, UC.CardExpYear, UC.CardLast4, UC.IsDefault');
        $this->db->where('UC.UserID', $UserID);
        return $this->db->get('UserCards AS UC')->result_array();
    }

    /**
     * 
     * @param type $CreditcardID
     * @return type
     */
    function get_creditcard_by_id($CreditcardID) {
        $this->db->select('UC.UserCardGUID AS CreditcardGUID, UC.Name, UC.CardType, UC.CardExpMonth, UC.CardExpYear, UC.CardLast4, UC.IsDefault');
        $this->db->where('UC.UserCardID', $CreditcardID);
        return $this->db->get('UserCards AS UC')->row_array();
    }

    /**
     * 
     * @param type $CreditcardGUID
     * @return type
     */
    function get_creditcard_by_guid($CreditcardGUID) {
        $this->db->select('UC.UserCardGUID AS CreditcardGUID, UC.Name, UC.UserID, UC.CardType, UC.CardExpMonth, UC.CardExpYear, UC.CardLast4, UC.IsDefault');
        $this->db->where('UC.UserCardGUID', $CreditcardGUID);
        return $this->db->get('UserCards AS UC')->row_array();
    }
}
