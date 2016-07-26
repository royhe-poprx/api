<?php

/**
 * Order_model will use to manage all order related db stuffs
 *
 * @author nitins
 */
class Payment_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 
     * @param type $UserID
     * @param type $PharmacistID
     * @return string
     */
    public function methods($UserID, $PharmacistID) {
        $this->load->model('user_model');
        $Pharmacy = $this->user_model->get_pharmacist_info($PharmacistID, TRUE);
        $PharmacyID = safe_array_key($Pharmacy, 'PharmacyID', NULL);

        $this->db->select('UC.UserCardGUID AS PaymentTypeGUID, CONCAT("Creditcard ending with ", CardLast4) AS PaymentType');
        $this->db->where('UC.UserID', $UserID);
        //$this->db->limit(5);
        $Creditcards = $this->db->get('UserCards AS UC')->result_array();


        $this->db->select('PPT.Message');
        $this->db->select('PT.PaymentTypeGUID, PT.PaymentTypeID, PT.PaymentType');

        $this->db->join('PaymentTypes AS PT', 'PT.PaymentTypeID=PPT.PaymentTypeID');

        $this->db->where('PPT.PharmacyID', $PharmacyID);
        $this->db->where('PPT.IsActive', 1);
        $Results = $this->db->get('PharmacyPaymentTypes AS PPT')->result_array();

        $O = array();
        foreach ($Results as $Result) {
            if ($Result['PaymentTypeID'] == 2) {
                if (!empty($Creditcards)) {
                    foreach ($Creditcards as $Creditcard) {
                        $Creditcard['PaymentMethodType'] = "CC";
                        $Creditcard['Message'] = $Result['Message'];
                        $O[] = $Creditcard;
                    }
                } else {
                    $Result['PaymentMethodType'] = "ADDCARD";
                    unset($Result['PaymentTypeID']);
                    $O[] = $Result;
                }
            }
        }
        foreach ($Results as $Result) {
            if ($Result['PaymentTypeID'] != 2) {
                $Result['PaymentMethodType'] = "CUSTOM";
                unset($Result['PaymentTypeID']);
                $O[] = $Result;
            }
        }
        return $O;
    }

    function get_user_cards($UserID) {
        $this->db->select('UC.UserCardGUID, UC.Name, UC.CardType, UC.CardExpMonth, UC.CardExpYear, UC.CardLast4, UC.IsDefault');
        $this->db->where('UC.UserID', $UserID);
        return $this->db->get(USER_CARDS . ' UC')->result_array();
    }

    function charge($UserID, $OrderGUID, $PaymentTypeGUID) {
        $this->load->model('order_model');
        $UserCardID = $this->order_model->get_payment_type_id_by_guid('CC', $PaymentTypeGUID);
        $Out = [];
        require_once(APPPATH . 'third_party/stripe-php-3.5.0/init.php');
        \Stripe\Stripe::setApiKey(STRIPE_SECRET);
        $User = $this->app->get_row('Users', 'StripeCustomerID', [
            'UserID' => $UserID,
        ]);
        $UserCard = $this->app->get_row('UserCards', 'StripeCardID', [
            'UserCardID' => $UserCardID,
        ]);

        $Order = $this->app->get_row('Orders', 'PharmacyUserID, GrandTotal', [
            'OrderGUID' => $OrderGUID,
        ]);

        $Pharmacy = $this->app->get_row('Pharmacies', 'GatewayMerchantID', [
            'UserID' => $Order['PharmacyUserID'],
        ]);
        try {
            $Token = \Stripe\Token::create([
                        "customer" => $User['StripeCustomerID'],
                        "card" => $UserCard['StripeCardID'],
                            ], [
                        "stripe_account" => $Pharmacy['GatewayMerchantID'],
            ]);

            $ChargeToken = $Token['id'];

            $Charge = \Stripe\Charge::create([
                        'amount' => $Order['GrandTotal'] * 100,
                        'currency' => 'cad',
                        'source' => $ChargeToken,
                            ], [
                        'stripe_account' => $Pharmacy['GatewayMerchantID'],
            ]);
            $Out['PaymentStatus'] = TRUE;
            $Out['PaymentStatusMessage'] = "";
            
        } catch (Exception $ex) {
            $Out['PaymentStatus'] = FALSE;
            $Out['PaymentStatusMessage'] = $ex->getMessage();
        }
        return $Out;
    }

}
