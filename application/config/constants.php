<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE')  OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE')   OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE')  OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ')                           OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESCTRUCTIVE') OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE')                   OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS')        OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code


/**
 * Encryption
 */
defined('ENCRYPTION')      OR define('ENCRYPTION', TRUE); // DB Encrypt is True always
define('AES_IV', '4Zr1eUbNz4kjJiYR'); /* 16 bytesclass AESClass */
define('AES_SECRET_STRING', 'q7tdurYrpMVjOK3QSwt0qre51InylKiT'); /* 32 bytes 256 bit key */


define('UPLOAD_TEMP',"uploads/temp/");
define('DATETIME', gmdate("Y-m-d H:i:s"));

if (defined('ENVIRONMENT')) {
    switch (ENVIRONMENT) {
        case 'development':
            
            define('IMAGES_DB', 'poprx_2_image');

            //Constants for used in email templates
            define('STRIPE_CLIENT_ID', 'ca_5uS5Lno527nze5xtX2hPDlPjjPVDT3Dz');
            define('STRIPE_PUBLISHABLE_KEY', 'pk_test_mn6mWz5srgsyVHoUyWP8MKgp');
            define('STRIPE_SECRET', 'sk_test_8rPRIjRoSavWMBXOvU7LkVQL');
            define('TOKEN_URI', 'https://connect.stripe.com/oauth/token');
            define('AUTHORIZE_URI', 'https://connect.stripe.com/oauth/authorize');
            
            define('SEND_PUSH', TRUE);
            define('POST_TO_MAILCHIMP', FALSE);
            define('NODE_URL', 'https://staging-api.poprx.ca:5001');
            define('SITE_NAME', "PopRx Dev");
            break;
        case 'staging':
            
            define('IMAGES_DB', 'poprx_2_image');

            //Constants for used in email templates
            define('STRIPE_CLIENT_ID', 'ca_5uS5Lno527nze5xtX2hPDlPjjPVDT3Dz');
            define('STRIPE_PUBLISHABLE_KEY', 'pk_test_mn6mWz5srgsyVHoUyWP8MKgp');
            define('STRIPE_SECRET', 'sk_test_8rPRIjRoSavWMBXOvU7LkVQL');
            define('TOKEN_URI', 'https://connect.stripe.com/oauth/token');
            define('AUTHORIZE_URI', 'https://connect.stripe.com/oauth/authorize');
            
            define('SEND_PUSH', TRUE);
            define('POST_TO_MAILCHIMP', FALSE);
            define('NODE_URL', 'https://staging-api.poprx.ca:5001');
            define('SITE_NAME', "PopRx Staging");
            break;

        case 'production':
            define('IMAGES_DB', 'live_poprx_2_image');
            
            define('STRIPE_CLIENT_ID', 'ca_5uS5LuC8GjM8xjRBthBjKFr1pcDiZEAJ');
            define('STRIPE_PUBLISHABLE_KEY', 'pk_live_0Y9LaxMs77PQAP4WO1Y5Ezkz');
            define('STRIPE_SECRET', 'sk_live_RWfLToium4oHMwcEIXQGVFfq');
            define('TOKEN_URI', 'https://connect.stripe.com/oauth/token');
            define('AUTHORIZE_URI', 'https://connect.stripe.com/oauth/authorize');
            
            define('SEND_PUSH', TRUE);
            define('POST_TO_MAILCHIMP', TRUE);
            define('NODE_URL', 'https://api.poprx.ca:5000');
            define('SITE_NAME', "PopRx Dev");
            break;

        default:
            exit('The application environment is not set correctly.');
    }
}
