<?php

/**
 * Order_model will use to manage all order related db stuffs
 *
 * @author nitins
 */
class Order_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 
     * @param type $UserID
     * @param type $ListType
     * @param type $OnlyNumRows
     * @param type $Limit
     * @param type $Offset
     * @return type
     */
    public function orders($UserID, $ListType = "pending", $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL) {

        $UserIDS[] = $UserID;
        $DependentUserIDS = $this->app->get_rows('UserDependents', 'DependentUserID', [
            'UserID' => $UserID,
            'IsDeleted' => 0,
            'IsActive' => 1,
        ]);

        foreach ($DependentUserIDS as $DependentUserID) {
            $UserIDS[] = $DependentUserID['DependentUserID'];
        }

        if (is_null($OnlyNumRows)) {
            $this->db->select('O.UserID');
            $this->db->select('O.OrderGUID, O.OrderSID, O.OrderType, O.Status, IFNULL(O.CancelReason,"") AS CancelReason');
        } else {
            $this->db->select('O.OrderGUID');
        }

        $this->db->group_start();
        $this->db->where_in('O.UserID', $UserIDS);
        $this->db->group_end();

        if ($ListType == "pending") {
            $this->db->group_start();
            $this->db->group_start();
            $this->db->where('O.OrderType', 'TRANSFER_ORDER');
            $this->db->where_in('O.Status', ['DRAFT', 'PLACED']);
            $this->db->group_end();

            $this->db->or_group_start();
            $this->db->where('O.OrderType', 'QUOTE_ORDER');
            $this->db->where_in('O.Status', ['PLACED']);
            $this->db->group_end();

            $this->db->or_group_start();
            $this->db->where('O.OrderType', 'DELIVERY_ORDER');
            $this->db->where_in('O.Status', ['DRAFT', 'PLACED', 'PACKED', 'ONROUTE']);
            $this->db->group_end();
            $this->db->group_end();
        } elseif ($ListType == "completed") {
            $this->db->group_start();
            $this->db->where_in('O.Status', ['COMPLETED', 'REJECTED', 'CANCELLED']);
            $this->db->group_end();
        }

        $this->db->order_by('O.CreatedAt', 'DESC');
        $this->db->from('Orders' . ' AS O');

        if (is_null($OnlyNumRows)) {
            $this->db->limit($Limit, $Offset);
            $Query = $this->db->get();
            $Orders = $Query->result_array();
            $LastQuery = $this->db->last_query();
            foreach ($Orders as $Key => $Order) {
                if ($Order['OrderType'] == 'TRANSFER_ORDER') {
                    $Orders[$Key]['StatusText'] = "TRANSFER ORDER";
                } elseif ($Order['OrderType'] == 'QUOTE_ORDER') {
                    $Orders[$Key]['StatusText'] = "QUOTE ORDER";
                } elseif ($Order['OrderType'] == 'DELIVERY_ORDER') {
                    $Orders[$Key]['StatusText'] = "DELIVERY ORDER";
                }
                $Orders[$Key]['Patient'] = $this->app->get_profile_by_user_id($Order['UserID']);
                unset($Orders[$Key]['UserID']);
            }
            //$Orders['LastQuery'] = $LastQuery;
            return encrypt_decrypt($Orders, 1);
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    /**
     * 
     * @param type $Extra
     * @param type $OnlyNumRows
     * @param type $Limit
     * @param type $Offset
     * @return type
     */
    public function order_live_feed($Extra, $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL) {

        if (is_null($OnlyNumRows)) {
            $this->db->select('O.UserID, O.PharmacyUserID');
            $this->db->select('O.OrderGUID, O.OrderSID, O.OrderType, O.Status, '
                    . 'O.PlacedAt, O.PlacedAt, O.CancelledAt, O.CancelReason, O.RejectedAt, '
                    . 'O.RejectReason, O.OnRouteAt, O.CompletedAt');
        } else {
            $this->db->select('O.OrderGUID');
        }

        $this->db->group_start();
        $this->db->group_start();
        $this->db->where('O.OrderType', 'QUOTE_ORDER');
        $this->db->where_not_in('O.Status', ['DRAFT']);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('O.OrderType', 'DELIVERY_ORDER');
        $this->db->where_not_in('O.Status', ['DRAFT']);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('O.OrderType', 'TRANSFER_ORDER');
        $this->db->group_end();
        $this->db->group_end();

        $this->db->order_by('O.CreatedAt', 'DESC');
        $this->db->from('Orders AS O');

        if (is_null($OnlyNumRows)) {
            if ($Limit != -1) {
                $this->db->limit($Limit, $Offset);
            }
            $Query = $this->db->get();
            $Orders = $Query->result_array();
            $LastQuery = $this->db->last_query();
            foreach ($Orders as $Key => $Order) {
                $Orders[$Key]['Patient'] = $this->app->get_profile_by_user_id($Order['UserID']);
                unset($Orders[$Key]['UserID']);
                $Orders[$Key]['Pharmacy'] = $this->app->get_pharmacy_by_user_id($Order['PharmacyUserID']);
                unset($Orders[$Key]['PharmacyUserID']);
            }
            return encrypt_decrypt($Orders, 1);
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    /**
     * 
     * @param type $UserID
     * @param type $OrderGUID
     * @param type $CancelReason
     */
    public function cancel_order($UserID, $OrderGUID, $CancelReason) {
        $Order = $this->get_order_by_guid($OrderGUID, TRUE);
        $this->db->update('Orders', [
            'Status' => 'CANCELLED',
            'CancelReason' => $CancelReason,
            'CancelledAt' => DATETIME,
                ], [
            'OrderGUID' => $OrderGUID,
        ]);

        //Notification
        $this->app->notify($UserID, $Order['PharmacyUserID'], 'ORDER_CANCELLED', $Order['OrderID']);
    }

    /**
     * 
     * @param type $UserID
     * @param type $OrderGUID
     */
    public function mark_as_complete($UserID, $OrderGUID) {
        $Order = $this->get_order_by_guid($OrderGUID, TRUE);
        $this->db->update('Orders', [
            'Status' => 'COMPLETED',
            'CompletedAt' => DATETIME,
                ], [
            'OrderGUID' => $OrderGUID,
        ]);

        //Notification
        //$this->app->notify($UserID, $Order['PharmacyUserID'], 'ORDER_COMPLETE', $Order['OrderID']);
    }

    /**
     * 
     * @param type $PaymentMethodType
     * @param type $PaymentTypeGUID
     * @return type
     */
    public function get_payment_type_id_by_guid($PaymentMethodType, $PaymentTypeGUID) {
        if ($PaymentMethodType == "CUSTOM") {
            $this->db->select('PaymentTypeID AS ID');
            $this->db->where('PT.PaymentTypeGUID', $PaymentTypeGUID);
            $this->db->from('PaymentTypes AS PT');
        } elseif ($PaymentMethodType == "CC") {
            $this->db->select('UC.UserCardID AS ID');
            $this->db->where('UC.UserCardGUID', $PaymentTypeGUID);
            $this->db->from('UserCards AS UC');
        }
        $data = $this->db->get()->row_array();
        return $data['ID'];
    }

    /**
     * get_delivery_address_by_guid
     * @param type $UserAddressGUID
     * @return type
     */
    public function get_delivery_address_by_guid($UserAddressGUID) {
        $this->db->select('UA.FormattedAddress');
        $this->db->where('UA.UserAddressGUID', $UserAddressGUID);
        $this->db->from('UserAddresses AS UA');
        $data = $this->db->get()->row_array();
        if (!empty($data)) {
            return $data['FormattedAddress'];
        } else {
            return NULL;
        }
    }

    /**
     * 
     * @param type $UserID
     * @param type $OrderGUID
     * @param type $SelfPickUp
     * @param type $UserAddressGUID
     * @param type $PaymentMethodType
     * @param type $PaymentTypeGUID
     * @param type $Deliverget_order_detail_by_guidyDate
     * @param type $DeliveryDateMax
     */
    public function place_order($UserID, $OrderGUID, $SelfPickUp, $UserAddressGUID, $PaymentMethodType, $PaymentTypeGUID, $DeliveryDate, $DeliveryDateMax) {
        $Order = $this->get_order_by_guid($OrderGUID, TRUE);
        $this->db->update('Orders', [
            'IsPickup' => $SelfPickUp,
            'PaymentMethodType' => $PaymentMethodType,
            'PaymentTypeID' => $this->get_payment_type_id_by_guid($PaymentMethodType, $PaymentTypeGUID),
            'DeliveryDate' => $DeliveryDate,
            'DeliveryDateMax' => $DeliveryDateMax,
            'PlacedAt' => DATETIME,
            'Status' => 'PLACED',
            'UpdatedAt' => DATETIME,
            'UpdatedBy' => $UserID,
            'DeliveryAddress' => $this->get_delivery_address_by_guid($UserAddressGUID),
                ], [
            'OrderGUID' => $OrderGUID,
        ]);

        //notification
        $this->app->notify($UserID, $Order['PharmacyUserID'], 'DX_ORDER_PLACED', $Order['OrderID']);
    }

    /**
     * 
     * @param type $OrderGUID
     * @return type
     */
    function get_order_detail_by_guid($OrderGUID) {
        $Order = $this->get_order_by_guid($OrderGUID, TRUE);
        $OrderID = $Order['OrderID'];
        $PharmacyUserID = $Order['PharmacyUserID'];
        unset($Order['OrderID']);
        unset($Order['PharmacyUserID']);

        $PharmacyUser = $this->app->get_row('Users', 'UserGUID, ProfilePicture, FirstName, LastName', [
            'UserID' => $PharmacyUserID,
        ]);

        $PharmacyInfo = $this->app->get_row('Pharmacies', 'PharmacyName, PhoneNumber', [
            'UserID' => $PharmacyUserID,
        ]);

        $Order['Pharmacist'] = [
            'UserGUID' => $PharmacyUser['UserGUID'],
            'ProfilePicture' => $PharmacyUser['ProfilePicture'],
            'FirstName' => $PharmacyUser['FirstName'],
            'LastName' => $PharmacyUser['LastName'],
            'PharmacyName' => $PharmacyUser['FirstName'],
        ];

        if ($Order['OrderType'] == 'TRANSFER_ORDER') {
            if ($Order['Status'] == "PLACED") {
                $Order['PharmacistText'] = "I am currently digitizing your meds. You will receive a notification once its complete.";
            } elseif ($Order['Status'] == "COMPLETED") {
                $Order['PharmacistText'] = "Great! Now your meds are verified and added to your list. To get a free quote Go to Cart and click Get Qoute.";
            } elseif ($Order['Status'] == "REJECTED") {
                $Order['PharmacistText'] = $Order['RejectReason'];
            }

            $Order['PharmacyInfo'] = [
                'From' => [
                    'PharmacyName' => $Order['TPName'],
                    'PharmacyPhone' => $Order['TPPhoneNumber'],
                ],
                'To' => [
                    'PharmacyName' => $PharmacyInfo['PharmacyName'],
                    'PharmacyPhone' => $PharmacyInfo['PhoneNumber'],
                ],
            ];
        } elseif ($Order['OrderType'] == 'QUOTE_ORDER') {
            if ($Order['Status'] == "PLACED") {
                $Order['PharmacistText'] = "I'm finding ways to save $$$. You will soon receive a personalized quote for your meds.";
            } elseif ($Order['Status'] == "COMPLETED") {
                $Order['PharmacistText'] = "Your quote is ready for review.";
            } elseif ($Order['Status'] == "REJECTED") {
                $Order['PharmacistText'] = $Order['RejectReason'];
            }
            $Medications = $this->get_order_medications($OrderID);
            $Order['Medications'] = encrypt_decrypt($Medications, 1);
        } elseif ($Order['OrderType'] == 'DELIVERY_ORDER') {
            if ($Order['Status'] == "PLACED") {
                $Order['PharmacistText'] = "I'm currently packing your meds. You will receive a notification once its complete.";
            } elseif ($Order['Status'] == "PACKED") {
                $Order['PharmacistText'] = "Great! Now your meds are packed.";
            } elseif ($Order['Status'] == "ONROUTE") {
                $Order['PharmacistText'] = "Easy! Your meds are on your way. PopRx delivery service will be at your doorstep with your meds.";
            } elseif ($Order['Status'] == "COMPLETED") {
                $Order['PharmacistText'] = "Hope you liked our service. Please spread the word about us.";
            } elseif ($Order['Status'] == "REJECTED") {
                $Order['PharmacistText'] = $Order['RejectReason'];
            }
            $Medications = $this->get_order_medications($OrderID);
            $Order['Medications'] = encrypt_decrypt($Medications, 1);
        }
        return encrypt_decrypt($Order, 1);
    }

    /**
     * 
     * @param type $OrderID
     * @param type $IDRequired
     * @return type
     */
    function get_order_medications($OrderID, $IDRequired = FALSE) {

        if ($IDRequired) {
            $this->db->select('M.MedicationID');
        }

        $this->db->select('OM.OrderMedicationGUID, M.MedicationName, M.MedicationSID, '
                . 'M.RefillAllowed, M.Strength, M.Quantity, OM.IsPacked');

        $this->db->select('OM.Price, OM.DispensingFee, OM.AdditionalFee, OM.Discount, '
                . 'OM.IsTaxApplicable, OM.AmountDue, IFNULL(OM.SpecialInstructions,"") AS SpecialInstructions', FALSE);
        $this->db->join('Medications AS M', 'M.MedicationID=OM.MedicationID');

        $this->db->from('OrderMedications AS OM');
        $this->db->where('OM.OrderID', $OrderID);
        return $this->db->get()->result_array();
    }

    /**
     * 
     * @param type $UserID
     * @return type
     */
    public function refill_quote_items($UserID) {
        $this->load->model('medication_model');
        $OrderID = $this->medication_model->get_quote_order($UserID);


        $this->db->select('OM.OrderMedicationGUID, IFNULL(M.MedicationName,"") AS MedicationName, '
                . 'M.RefillAllowed, IFNULL(M.Strength,"") AS Strength, IFNULL(M.Quantity,"") AS Quantity, '
                . 'IFNULL(OM.SpecialInstructions,"") AS SpecialInstructions');
        $this->db->select('IFNULL(M.MedicationImages, "") AS MedicationImages', FALSE);
        $this->db->join('Medications M', 'M.MedicationID=OM.MedicationID');
        $this->db->where('M.IsNew', 0);
        $this->db->where('OM.OrderID', $OrderID);
        $this->db->from('OrderMedications' . ' AS OM');

        $Query = $this->db->get();
        $QuoteItems = $Query->result_array();
        $QuoteItems=encrypt_decrypt($QuoteItems, 1);
        return encrypt_decrypt($QuoteItems, 1);
    }

    /**
     * 
     * @param type $UserID
     * @return type
     */
    public function newrx_quote_items($UserID) {
        $this->load->model('medication_model');
        $OrderID = $this->medication_model->get_quote_order($UserID);


        $this->db->select('OM.OrderMedicationGUID, IFNULL(M.MedicationName,"") AS MedicationName, '
                . 'M.RefillAllowed, IFNULL(M.Strength,"") AS Strength, IFNULL(M.Quantity,"") AS Quantity, '
                . 'IFNULL(OM.SpecialInstructions,"") AS SpecialInstructions');
        $this->db->select('IFNULL(M.MedicationImages, "") AS MedicationImages', FALSE);
        $this->db->join('Medications M', 'M.MedicationID=OM.MedicationID');
        $this->db->where('M.IsNew', 1);
        $this->db->where('OM.OrderID', $OrderID);
        $this->db->from('OrderMedications' . ' AS OM');

        $Query = $this->db->get();
        $QuoteItems = $Query->result_array();
        $QuoteItems=encrypt_decrypt($QuoteItems, 1);
        return encrypt_decrypt($QuoteItems, 1);
    }

    /**
     * 
     * @param type $UserID
     * @param type $PharmacyUserID
     * @param type $OrderGUID
     * @return boolean
     */
    public function request_for_quote($UserID, $PharmacyUserID, $OrderGUID) {
        $Order = $this->get_order_by_guid($OrderGUID, TRUE);
        $this->db->update('Orders', [
            'PlacedAt' => DATETIME,
            'Status' => 'PLACED',
            'PharmacyUserID' => $PharmacyUserID,
                ], [
            'OrderGUID' => $OrderGUID,
        ]);

        //notification
        $this->app->notify($UserID, $PharmacyUserID, 'QX_ORDER_PLACED', $Order['OrderID']);
        return TRUE;
    }

    /**
     * 
     * @param type $OrderGUID
     * @param type $IDRequired
     * @return type
     */
    function get_order_by_guid($OrderGUID, $IDRequired = FALSE) {
        if ($IDRequired) {
            $this->db->select('OrderID, PharmacyUserID');
        }
        $this->db->select('OrderGUID, OrderSID, OrderType, ImportAll, IsPickup, Status');

        $this->db->select('IFNULL(TPName,"") AS TPName', FALSE);
        $this->db->select('IFNULL(TPPhoneNumber,"") AS TPPhoneNumber', FALSE);

        $this->db->select('IFNULL(RejectReason,"") AS RejectReason', FALSE);
        $this->db->select('IFNULL(CancelReason,"") AS CancelReason', FALSE);

        $this->db->select('IFNULL(PickUpAddress,"") AS PickUpAddress', FALSE);
        $this->db->select('IFNULL(DeliveryAddress,"") AS DeliveryAddress', FALSE);
        $this->db->select('IFNULL(DeliveryDate,"") AS DeliveryDate', FALSE);
        $this->db->select('IFNULL(DeliveryDateMax,"") AS DeliveryDateMax', FALSE);

        $this->db->select('SubTotal, TaxRate, Tax, DiscountAmount, GrandTotal');
        $this->db->select('IFNULL(DiscountCode,"") AS DiscountCode', FALSE);

        $this->db->select('IFNULL(CompletedAt,"") AS CompletedAt', FALSE);
        $this->db->select('IFNULL(RejectedAt,"") AS RejectedAt', FALSE);

        $this->db->where('OrderGUID', $OrderGUID);
        return $this->db->get('Orders')->row_array();
    }

}
