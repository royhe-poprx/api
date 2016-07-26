<?php

/*
  |--------------------------------------------------------------------------
  |   String Encryption
  |--------------------------------------------------------------------------
 */

function StringEncryption($data, $iv = AES_IV, $key = AES_SECRET_STRING) {
    $decryptedstringdata = trim($data);
    $hex_cipher_text = '';
    if ($decryptedstringdata != '') {
        $cipher_text = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
        $hex_cipher_text = bin2hex($cipher_text);
    }
    //echo  $hex_cipher_text.'<br>';
    return $hex_cipher_text;
}

/*
  |--------------------------------------------------------------------------
  |   String Decryption
  |--------------------------------------------------------------------------
 */

function StringDecryption($data, $key = AES_SECRET_STRING, $iv = AES_IV) {
    if($data!='' && $data!=NULL && !is_numeric($data)){
    $len = strlen($data);
    if ($len % 2==0 &&  strspn($data, '0123456789abcdefABCDEF') == $len) {
            $Str = @hex2bin($data);		
            if($Str!=''){
                    $data = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $Str, MCRYPT_MODE_CBC, $iv);	
            }	
    }
    }
    return trim($data);
}

/*
  |--------------------------------------------------------------------------
  | Search Encryption
  |--------------------------------------------------------------------------
 */

function search_encryption($plain_text, $key = AES_SECRET_STRING, $iv = AES_IV) {
    if ($plain_text != '') {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        // Tokenizer
        $token_pt = str_split(strtolower($plain_text), 1);
        // Encrypt Data
        $final_ct = "";
        foreach ($token_pt as $char) {
            mcrypt_generic_init($td, $key, $iv);
            $c_t = mcrypt_generic($td, $char);
            // Split as 8 and choose the first 1st array [16 bytes]
            $token_ct = str_split($c_t, 4);
            $final_ct = $final_ct . $token_ct[0] . $token_ct[2];
            // print_r "Token array-" . $char . ":" . $token_ct . "<br />";
        }
        return bin2hex($final_ct);
    } else {
        return NULL;
    }
}

/*
  |--------------------------------------------------------------------------
  | JSON Encryption
  |--------------------------------------------------------------------------
 */

function JsonEncryption($str) {
    if(!defined('AES_SECRET_JSON')){        
        return $str;
    }
    $str=padString($str);
    //$td = mcrypt_module_open('rijndael-128', '', 'cbc', AES_IV_JSON);	  
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, AES_IV_JSON);

    mcrypt_generic_init($td, AES_SECRET_JSON, AES_IV_JSON);
    $encrypted = mcrypt_generic($td, $str);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return base64_encode($encrypted);
}

/*
  |--------------------------------------------------------------------------
  | JSON Decryption
  |--------------------------------------------------------------------------
 */

function padString($source) {
    $paddingChar = ' ';
    $size = 16;
    $x = (strlen($source) % $size);
    $padLength = $size - $x;
    for ($i = 0; $i < $padLength; $i++) {
        $source = $source . $paddingChar;
    }
    return $source;
}

function JsonDecryption($api, $opt = '', $segment = '') {
    $obj = & get_instance();

    if ($segment == '') {
        $APIName = $obj->uri->segment(2);
    } else {
        $APIName = $segment;
    }

    if ($obj->input->post($APIName)) { /* for web */
        $JSONInput = strip_tags($obj->post($APIName));

        if (ENCRYPTION && $obj->uri->segment(3) != 'UploadSingleImage' && $obj->uri->segment(3) != 'UploadSingleFile') {
            @$obj->db->insert('jsondata', array('DataJ' => $JSONInput));
        }
    } else {
        /* for other device */
        $JSONInput = fgets(fopen('php://input', 'r'));
        if (ENCRYPTION && !$_POST && $obj->uri->segment(3) != 'Generate' && defined('AES_SECRET_JSON') && defined('AES_IV_JSON')) {
            $key = AES_SECRET_JSON;
            $iv = AES_IV_JSON;
            if (ENCRYPTION && $obj->uri->segment(2) != 'UploadSingleImage' && $obj->uri->segment(3) != 'UploadSingleFile') {
                @$obj->db->insert('jsondata', array('DataJ' => $JSONInput, 'KeyJ' => $key, 'IvJ' => $iv));
            }
            $JSONInput = base64_decode($JSONInput);
            $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $iv);
            mcrypt_generic_init($td, $key, $iv);
            $decrypted = @mdecrypt_generic($td, $JSONInput);            
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            $JSONInput = strip_tags(utf8_encode(trim($decrypted)));
        } else {
            if (ENCRYPTION && $obj->uri->segment(3) != 'UploadSingleImage' && $obj->uri->segment(3) != 'UploadSingleFile') {
                @$obj->db->insert('jsondata', array('DataJ' => $JSONInput));
            }
        }
    }
    

    if ($opt == '') {
        $JSONInput = @json_decode($JSONInput);
    } else {
        $JSONInput = @json_decode($JSONInput, true);
    }


    //if ($JSONInput != NULL && (is_object($JSONInput) && isset($JSONInput->$api)) || (is_array($JSONInput) && isset($JSONInput[$api]))) {
    if ($JSONInput != NULL && is_object($JSONInput) || is_array($JSONInput)) {
        return $JSONInput;
    } else {
        $Return['ResponseCode'] = '519';
        $Return['Message'] = $obj->app->getError('519');
        $Outputs[$APIName] = $Return;
        $Outputs['ResponseCode'] = '519';
        $Outputs['Message'] = $obj->app->getError('519');
        $obj->response($Outputs);
    }
}

/**
 * String Output Encryption and Decryption
 * @param mix $Data
 * @param int $action | 0=Encrypt, 1=Decrypt
 * @return mix
 */
function encrypt_decrypt($data, $action = 0) {    
    if (ENCRYPTION == TRUE) {
        $array = array(
            /* ------TABLE ActiveLogin START------ */
            'IPAddress',
            'Latitude',
            'Longitude',
            /* ------TABLE ActiveLogin END ---- */            
            /* ------Break ---- */
            
            /* ------TABLE InsuranceCards START------ */
            'InsuranceProvider',
            'EmployerGroupName',
            'ContractPlanNumber',
            'GroupNumber',
            'EmployeeStudentNumber',
            'Comment',
            /* ------TABLE InsuranceCards END ---- */            
            /* ------Break ---- */
            
            /* ------TABLE Medications START------ */
            'MedicationName',
            'RefillAllowed',
            'RefillLeft',
            'Strength',
            'Unit',
            'Quantity',
            'QuantityUnit',
            /* ------TABLE Medications END ---- */            
            /* ------Break ---- */
            
            /* ------TABLE MPOrders START------ */
            'DeliveryAddress',
            'PickUpAddress',
            'DeliveryInstruction',
            /* ------TABLE MPOrders END ---- */            
            /* ------Break ---- */
            
            /* ------TABLE MPOrdersProducts START------ */
            'RefillAllowed',
            'Strength',
            'Unit',
            'Quantity',
            'Notes',
            /* ------TABLE MPOrdersProducts END ---- */            
            /* ------Break ---- */
            
            /* ------TABLE MPProducts START------ */
            'Title',
            'Description',
            /* ------TABLE MPProducts END ---- */            
            /* ------Break ---- */
            
            /* ------TABLE PrescriptionDoctorInfo START------ */
            'DoctorName',
            'Address',
            'Phone',
            'Fax',
            /* ------TABLE PrescriptionDoctorInfo END ---- */            
            /* ------Break ---- */
            
            /* ------TABLE Prescriptions START------ */
            'Name',
            'Notes',            
            /* ------TABLE PrescriptionDoctorInfo END ---- */            
            /* ------Break ---- */
            
            /* ------TABLE Users START------ */
            'Email',
            'FirstName',
            'LastName',
            'PhoneNumber',
            'DOB',
            'Gender',
            'PhinNumber', 
            /* ------TABLE users END ---- */
            
            /* ------Break ---- */
            
            /* ------TABLE UserLogins START------ */
            'LoginKeyword',
            /* ------TABLE UserLogins END ---- */
            
        );
        $data = recursive_arr_find_replace($data, $array, $action);
    }
    return $data;
}

/*
  |--------------------------------------------------------------------------
  |
  |--------------------------------------------------------------------------
 */

function recursive_arr_find_replace($arr, $find, $action /* 0=Encrypt, 1=Decrypt */) {
    if (is_array($arr)) {
        foreach ($arr as $key => $val) {
            if (is_array($arr[$key])) {
                $arr[$key] = recursive_arr_find_replace($arr[$key], $find, $action);
            } else {
                if (in_array($key, $find)) {
                    if ($action == 0) {
                        if (strtolower($val) == 'null') {
                            $val = '';
                        }
                        $arr[$key] = StringEncryption($val);
                    } else {
                        $arr[$key] = StringDecryption($val);
                    }
                }
            }
        }
    }
    return $arr;
}

function decrypt_raw_to_json($raw, $key, $iv){
    $raw_1 = base64_decode($raw);
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $iv);
    mcrypt_generic_init($td, $key, $iv);
    $decrypted = @mdecrypt_generic($td, $raw_1);            
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return strip_tags(utf8_encode(trim($decrypted)));
}

function encrypt_json_to_row($str, $key, $iv) {
    $str=padString($str);
    //$td = mcrypt_module_open('rijndael-128', '', 'cbc', AES_IV_JSON);	  
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $iv);
    mcrypt_generic_init($td, $key, $iv);
    $encrypted = mcrypt_generic($td, $str);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return base64_encode($encrypted);
}
