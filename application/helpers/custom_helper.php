<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


/**
 * Create guid
 * @return string
 */
if (!function_exists('guid')) {

    function guid() {
        mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
        return strtolower($uuid);
    }

}

/**
 * Create guid
 * @return string
 */
if (!function_exists('safe_array_key')) {

    function safe_array_key($array, $key, $default = "") {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}

/*
  |--------------------------------------------------------------------------
  |
  |--------------------------------------------------------------------------
 */

function check_base64_image($base64) {
    if (base64_decode($base64, true)) {
        return true;
    } else {
        return false;
    }
}

/*
  |--------------------------------------------------------------------------
  |
  |--------------------------------------------------------------------------
 */

function base64_encode_image($filename = '', $filetype = '') {
    if ($filename) {
        $imgbinary = @fread(@fopen($filename, "r"), @filesize($filename));
        return base64_encode($imgbinary);
    }
}

function ConverTimeIntoSeconds($time) {
    $newTime = gmdate('H:i', strtotime($time));
    //$newTime = split(':', $newTime);
    $newTime = preg_split('/:/', $newTime);
    $result = mktime($newTime[0], $newTime[1], 0, 1, 1, 1970);
    return (int) $result;
}

function order_sid($start, $id) {
    $prefix = $start . "-" . gmdate("y") . "-" . gmdate("m") . "-";
    $format = '%1$05s';
    return $prefix . sprintf($format, $id);
}

function push_notification_iphone($device_token = '', $message = '', $badge = '1', $extra = array()) {
    if (SEND_PUSH) {
        try {
            if (defined('ENVIRONMENT')) {
                switch (ENVIRONMENT) {
                    case 'production':
                        $ctx = stream_context_create();
                        stream_context_set_option($ctx, 'ssl', 'passphrase', '123456');
                        stream_context_set_option($ctx, "ssl", "local_cert", 'ck.pem');

                        $fp = NULL;
                        $errno = NULL;
                        $errstr = NULL;
                        $fp = stream_socket_client("tls://gateway.push.apple.com:2195", $errno, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
                        break;
                    default:
                        $ctx = stream_context_create();
                        stream_context_set_option($ctx, 'ssl', 'passphrase', '123456');
                        stream_context_set_option($ctx, "ssl", "local_cert", 'dev-ck.pem');

                        $fp = NULL;
                        $errno = NULL;
                        $errstr = NULL;
                        $fp = stream_socket_client("tls://gateway.sandbox.push.apple.com:2195", $errno, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
                }
            }
            if ($fp === FALSE) {
                exit($errstr . "-asdf");
            }
            $content = array("aps" => array("alert" => $message, "badge" => 1, "sound" => 'default', "code" => 200, "extra" => $extra));
            $data = json_encode($content);
            $msg = chr(0) . pack("n", 32) . @pack("H*", $device_token) . @pack("n", strlen($data)) . $data;
            fwrite($fp, $msg);
            fflush($fp);
            fclose($fp);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
        }
    }
}

function push_notification_android($device_token = '', $message = '', $badge = '1', $extra = array()) {
    if (SEND_PUSH) {
        try {

            $badge = (int) $badge;
            $apiKey = "AIzaSyCau4H4A7pbgVYZwRT_YPFUlo8Iyae7qU8";
            // Set POST variables
            $url = 'https://android.googleapis.com/gcm/send';

//                $notification_type  = 'push';
//                $id                 = "721260039790";

            $fields = array(
                'registration_ids' => array($device_token),
                'data' => array(
                    "message" => rawurldecode($message),
                    "badge" => $badge,
                    "extra" => $extra,
                ),
            );

            $headers = array(
                'Authorization: key=' . $apiKey,
                'Content-Type: application/json'
            );
            // Open connection
            $ch = curl_init();
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // allow https verification if true
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // check common name and verify with host name
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            // Execute post
            $result = curl_exec($ch);
            // Close connection
            curl_close($ch);
            return $result;
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
        }
    }
}

if (!function_exists('transaction_sid')) {

    function transaction_sid($id) {
        $prefix = "TR-" . gmdate("y") . "-" . gmdate("m") . "-";
        $format = '%1$05s';
        return $prefix . sprintf($format, $id);
    }

}

if (!function_exists('utf8ize')) {

    function utf8ize($d) {
        $convertedArray = array();
        foreach ($d as $key => $value) {
            if (!mb_check_encoding($key, 'UTF-8'))
                $key = utf8_encode($key);
            if (is_array($value))
                $value = utf8ize($value);
            $convertedArray[$key] = $value;
        }
        return $convertedArray;
//        if (is_array($d)) {
//            foreach ($d as $k => $v) {
//                $d[$k] = utf8ize($v);
//            }
//        } else if (is_string($d)) {
//            return utf8_encode($d);
//        }
//        return $d;
    }

}

if (!function_exists('post_to_mailchimp_list')) {

    function post_to_mailchimp_list($post, $list_id = "7ab7e8d016") {
        if (POST_TO_MAILCHIMP) {
            $post_data = json_encode($post);
            $service_url = "https://us12.api.mailchimp.com/3.0/lists/" . $list_id . "/members";
            try {
                $ch = curl_init();
                $username = "poprx";
                $password = "6b95014d4bc0e79df83d446ed5e72e5f-us12";
                if (FALSE === $ch)
                    throw new Exception('failed to initialize');
                curl_setopt($ch, CURLOPT_URL, $service_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Connection: Keep-Alive'
                ));
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
                curl_setopt($ch, CURLOPT_PROXY, '');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $content = curl_exec($ch);
                if (FALSE === $content)
                    throw new Exception(curl_error($ch), curl_errno($ch));
            } catch (Exception $e) {
                
            }
        }
    }

}

if (!function_exists('process_in_backgroud')) {

    function process_in_backgroud($Function, $InputData) {
        if (function_exists("gearman_version")) {
            $Client = new GearmanClient();
            $Client->addServer();
            $Client->doBackground($Function, json_encode($InputData));
        }
    }

}

/**
 * 
 * @param type $to
 * @param type $subject
 * @param type $message
 * @param type $from_mail
 * @param type $from_title
 * @return boolean
 */
function email_send($to, $subject, $message, $from_mail = FROM_EMAIL, $from_title = FROM_EMAIL_TITLE) {
    //Set the hostname of the mail server
    $config['smtp_host'] = SMTP_HOST;

    //Username to use for SMTP authentication
    $config['smtp_user'] = SMTP_USER;

    //Password to use for SMTP authentication
    $config['smtp_pass'] = SMTP_PASS;

    //Set the SMTP port number - likely to be 25, 465 or 587
    $config['smtp_port'] = SMTP_PORT;

    //Set the SMTP PROTOCOL
    $config['protocol'] = PROTOCOL;

    //Set the other configuration for Mail
    $config['mailpath'] = MAILPATH;
    $config['mailtype'] = MAILTYPE;

    //Create a new CIMailer instance
    $email = new CI_Email();
    $email->initialize($config);
    $email->set_newline("\r\n");
    $email->clear();

    //Set who the message is to be sent from
    $email->from($from_mail, $from_title);
    $email->to(trim($to));

    $email->bcc('pradeep@vinfotech.com');
    $email->subject($subject);
    $email->reply_to(NO_REPLY_EMAIL, NO_REPLY_EMAIL_TITLE);
    $email->message($message);
    //Send Email

    $sent = $email->send();
    return true;
}









if (!function_exists('p')) {

    function p($arr) {
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }

}






