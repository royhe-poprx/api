<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Order extends REST_Controller {

    var $_data = array();

    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->benchmark->mark('code_start');
        $this->_data = $this->post();
        $this->_data['Key'] = "value";
        $this->_response = [
            "Status" => TRUE,
            "StatusCode" => self::HTTP_OK,
            "ServiceName" => "",
            "Message" => "Success",
            "Errors" => (object) [],
            "Data" => (object) [],
            "ElapsedTime" => "",
        ];
        $this->load->library('form_validation');
        $this->form_validation->set_data($this->_data);
    }

    public function index_post() {

        $this->_response["ServiceName"] = "order/index";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim');
        $this->form_validation->set_rules('ListType', 'ListType', 'trim|required');
        $this->form_validation->set_rules('Limit', 'Limit', 'trim');
        $this->form_validation->set_rules('Offset', 'Offset', 'trim');

        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        $ListType = safe_array_key($this->_data, "ListType", "pending");
        $Limit = safe_array_key($this->_data, "Limit", NULL);
        $Offset = safe_array_key($this->_data, "Offset", NULL);
        if (!is_null($ProfileGUID)) {
            $Profile = $this->app->get_row('Users', 'UserID', [
                "UserGUID" => $ProfileGUID
            ]);
            if (!empty($Profile)) {
                $UserID = $Profile['UserID'];
            }
        }

        $this->load->model("order_model");

        $this->_response["TotalRecords"] = $this->order_model->orders($UserID, $ListType, 1);
        $Orders = $this->order_model->orders($UserID, $ListType, NULL, $Limit, $Offset);
        if (!empty($Orders)) {
            $this->_response["Data"] = $Orders;
        } else {
            $this->_response["Message"] = "No Order(s) placed yet.";
        }
        $this->benchmark->mark('code_end');
        $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->set_response($this->_response);
    }

    public function request_for_quote_post() {

        $this->_response["ServiceName"] = "order/request_for_quote";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('GetQuoteReady', 'GetQuoteReady', 'callback__is_get_quote_ready');
        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("order_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ActiveUserID = $Profile['UserID'];
            $PharmacyUserID = $Profile['PharmacistID'];
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $this->order_model->request_for_quote($ActiveUserID, $PharmacyUserID, $OrderGUID);
            $this->_response["Data"] = [
                "OrderGUID" => $OrderGUID,
            ];
            $this->_response["Message"] = "Get ready for huge savings! Your pharmacist will quote you shortly.";
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _is_get_quote_ready() {
        $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
        $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
            "UserGUID" => $ProfileGUID
        ]);
        if (!empty($Profile) && !is_null($Profile['PharmacistID'])) {
            return TRUE;
        } else {
            $this->form_validation->set_message('_is_get_quote_ready', 'It seems you are not allocated to any Pharmacy please go to dashbaord and set your zipcode.');
            return FALSE;
        }
    }

    public function cancel_post() {

        $this->_response["ServiceName"] = "order/cancel";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');
        $this->form_validation->set_rules('CancelReason', 'Cancel Reason', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("order_model");
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $CancelReason = safe_array_key($this->_data, "CancelReason", NULL);
            $this->order_model->cancel_order($UserID, $OrderGUID, $CancelReason);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function mark_as_complete_post() {

        $this->_response["ServiceName"] = "order/mark_as_complete";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("order_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ActiveUserID = $Profile['UserID'];
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);

            $this->order_model->mark_as_complete($ActiveUserID, $OrderGUID);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function hide_from_dashboard_post() {

        $this->_response["ServiceName"] = "order/hide_from_dashboard";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("order_model");
            $ProfileGUID = safe_array_key($this->_data, "ProfileGUID", NULL);
            $Profile = $this->app->get_row('Users', 'UserID, PharmacistID', [
                "UserGUID" => $ProfileGUID
            ]);
            $ActiveUserID = $Profile['UserID'];
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);

            //$this->order_model->mark_as_complete($ActiveUserID, $OrderGUID);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function place_post() {

        $this->_response["ServiceName"] = "order/place";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required|callback__is_order_place_ready');
        $this->form_validation->set_rules('SelfPickUp', 'Self Pick Up', 'trim|required|in_list[0,1]');
        $SelfPickUp = safe_array_key($this->_data, "SelfPickUp", "0");
        if ($SelfPickUp == 0) {
            $this->form_validation->set_rules('UserAddressGUID', 'User Address GUID', 'trim|required');
            $this->form_validation->set_rules('DeliveryDate', 'DeliveryDate', 'trim|required');
            $this->form_validation->set_rules('DeliveryDateMax', 'DeliveryDateMax', 'trim|required');
        }

        $this->form_validation->set_rules('PaymentMethodType', 'Payment Method Type', 'trim|required|in_list[CUSTOM,CC]');
        $this->form_validation->set_rules('PaymentTypeGUID', 'Payment Type GUID', 'trim|required');


        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("order_model");

            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $SelfPickUp = safe_array_key($this->_data, "SelfPickUp", "0");
            $UserAddressGUID = safe_array_key($this->_data, "UserAddressGUID", NULL);
            $PaymentMethodType = safe_array_key($this->_data, "PaymentMethodType", NULL);
            $PaymentTypeGUID = safe_array_key($this->_data, "PaymentTypeGUID", NULL);
            $DeliveryDate = safe_array_key($this->_data, "DeliveryDate", NULL);
            $DeliveryDateMax = safe_array_key($this->_data, "DeliveryDateMax", NULL);

            if ($PaymentMethodType != "CC") {
                if ($PaymentTypeGUID == "707d9a0a-1503-467f-9e4c-e580eb77849d") {
                    // payment from wallet  
                    //charge to wallet
                    $this->load->model('wallet_model');
                    $OrderDetail = $this->app->get_row('Orders', 'OrderID, GrandTotal', [
                        'OrderGUID' => $OrderGUID
                    ]);
                    $this->wallet_model->pay_using_wallet($UserID, $OrderDetail['GrandTotal'], $OrderDetail['OrderID']);
                    $this->db->update('Orders', [
                        'PaymentStatus' => 'PAID',
                            ], [
                        'OrderGUID' => $OrderGUID,
                    ]);
                } elseif ($PaymentTypeGUID == "567c2ea4-3dec-9ee9-485a-2ab34f517714") {
                    //paid by insurance
                    $this->db->update('Orders', [
                        'PaymentStatus' => 'PAID',
                            ], [
                        'OrderGUID' => $OrderGUID,
                    ]);
                } else {
                    $this->db->update('Orders', [
                        'PaymentStatus' => 'PENDING',
                            ], [
                        'OrderGUID' => $OrderGUID,
                    ]);
                }
                $CanPlaced = TRUE;
            } else {
                $this->load->model('payment_model');
                $PaymentChargeObj = $this->payment_model->charge($UserID, $OrderGUID, $PaymentTypeGUID);
                if ($PaymentChargeObj['PaymentStatus'] == TRUE) {
                    $this->db->update('Orders', [
                        'PaymentStatus' => 'PAID',
                        'PaidAt' => DATETIME,
                            ], [
                        'OrderGUID' => $OrderGUID,
                    ]);
                    $CanPlaced = TRUE;
                } else {
                    $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
                    $this->_response["Message"] = $PaymentChargeObj['PaymentStatusMessage'];
                }
            }

            if ($CanPlaced) {
                $this->order_model->place_order($UserID, $OrderGUID, $SelfPickUp, $UserAddressGUID, $PaymentMethodType, $PaymentTypeGUID, $DeliveryDate, $DeliveryDateMax);
                $this->_response["Message"] = "Way to go! Your order has been sent to your PopRx Pharmacy.";
                $this->_response["Data"] = [
                    "OrderGUID" => $OrderGUID,
                ];
            }

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    function _is_order_place_ready($Str) {
        $Order = $this->app->get_row('Orders', 'Status', [
            'OrderGUID' => $Str,
            'Status' => 'DRAFT',
            'OrderType' => 'DELIVERY_ORDER',
        ]);
        if (empty($Order)) {
            $this->form_validation->set_message('_is_order_place_ready', 'Order is in process.');
            return FALSE;
        }
        return TRUE;
    }

    public function view_post() {

        $this->_response["ServiceName"] = "order/view";

        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim|required');
        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("order_model");
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $this->_response["Data"] = $this->order_model->get_order_detail_by_guid($OrderGUID);
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function rate_pharmacist_post() {

        $this->_response["ServiceName"] = "order/rate_pharmacist";

        $UserID = $this->rest->UserID;

        $this->form_validation->set_rules('OrderGUID', 'Order GUID', 'trim|required|callback__check_already_rated');
        $this->form_validation->set_rules('Rating', 'Rating', 'trim|required|in_list[0,1,2,3,4,5,6,7,8,9,10]');
        $this->form_validation->set_rules('Feedback', 'Feedback', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("order_model");
            $OrderGUID = safe_array_key($this->_data, "OrderGUID", NULL);
            $Rating = safe_array_key($this->_data, "Rating", NULL);
            $Feedback = safe_array_key($this->_data, "Feedback", "");

            $Order = $this->app->get_row('Orders', 'OrderID, PharmacyUserID', [
                'OrderGUID' => $OrderGUID,
            ]);

            $this->db->insert('PharmacistRatings', [
                'UserID' => $UserID,
                'PharmacistID' => $Order['PharmacyUserID'],
                'OrderID' => $Order['OrderID'],
                'Rating' => $Rating,
                'Feedback' => $Feedback,
                'CreatedAt' => DATETIME,
            ]);

            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    public function _check_already_rated($Str) {
        $UserID = $this->rest->UserID;
        $Order = $this->app->get_row('Orders', 'OrderID, PharmacyUserID', [
            'OrderGUID' => $Str,
        ]);
        $Rating = $this->app->get_rows('PharmacistRatings', '', [
            'UserID' => $UserID,
            'OrderID' => $Order['OrderID'],
        ]);
        if (count($Rating) > 0) {
            $this->form_validation->set_message('_check_already_rated', 'It seems you already rated for this order.');
            return FALSE;
        }
        return TRUE;
    }

    public function live_feed_post() {

        $this->_response["ServiceName"] = "order/live_feed";
        $UserID = $this->rest->UserID;
        $this->form_validation->set_rules('Limit', 'Limit', 'trim|required');
        $this->form_validation->set_rules('Offset', 'Offset', 'trim|required');
        $this->form_validation->set_rules('Extra', 'Extra', 'trim');

        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("order_model");
            $Limit = safe_array_key($this->_data, "Limit", NULL);
            $Offset = safe_array_key($this->_data, "Offset", NULL);
            $Extra = safe_array_key($this->_data, "Extra", NULL);
            $this->_response["TotalRecords"] = $this->order_model->order_live_feed($Extra, 1);
            $Orders = $this->order_model->order_live_feed($Extra, NULL, $Limit, $Offset);
            if (!empty($Orders)) {
                $this->_response["Data"] = $Orders;
            } else {
                $this->_response["Message"] = "No order(s) added.";
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

}
