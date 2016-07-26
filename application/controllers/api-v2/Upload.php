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
class Upload extends REST_Controller {

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

    public function index_get() {
        $data = [
            "StatusCode" => 100,
            "Message" => "",
        ];
        $this->set_response($data);
    }

    /*
      |--------------------------------------------------------------------------
      | Use to upload single image
      | @Inputs: LoginSessionKey, ImageString, ModuleName, Latitude, Logitude, RefrenceID, ImageName, MIME, ProfilePicture
      |--------------------------------------------------------------------------
     */

    function singleimage_post() {
        $DesiredExt = 'jpg';
        /* Gather Inputs - starts */

        $this->_response["ServiceName"] = "upload/singleimage";

        $this->form_validation->set_rules('ImageString', 'Image String', 'trim|required');
        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {

            $Thumbs = array(
                "72X72",
                "90X90",
                "200X200",
                "250X250",
                "560X660",
                "126X133",
                "133X133",
                "197X150",
            );
            if (!is_dir(UPLOAD_TEMP)) {
                mkdir(UPLOAD_TEMP, 0777, TRUE);
            }
            $ImageString = $this->_data['ImageString'];
            $ImageName = guid() . ".jpg";
            $Attachment = base64_decode($ImageString);
            $TempPath = UPLOAD_TEMP . $ImageName;
            file_put_contents($TempPath, $Attachment);

            $Image["ImageType"] = "jpg";
            $Image["ImageData"] = $ImageString;
            $Image["IsDeleted"] = "0";
            $Errors = array();

            $this->load->library('image_lib');
            //thumb needs to be create
            foreach ($Thumbs as $ThumbSize) {
                $Thumb = UPLOAD_TEMP . $ThumbSize . "_" . $ImageName;
                $ThumbFileString = "";
                list($w, $h) = explode("X", $ThumbSize);

                $config['image_library'] = 'gd2';
                $config['source_image'] = $TempPath;
                $config['create_thumb'] = FALSE;
                $config['maintain_ratio'] = TRUE;
                $config['width'] = $w;
                $config['height'] = $h;
                $config['new_image'] = $Thumb;
                $this->image_lib->initialize($config);
                if ($this->image_lib->resize()) {
                    $ThumbFileString = base64_encode_image($Thumb);
                    unlink($Thumb);
                } else {
                    $Errors[$ThumbSize] = $this->image_lib->display_errors('', '');
                }
                $Image["ImageData" . $ThumbSize] = $ThumbFileString;
            }
            unlink($TempPath);
            if (!empty($Errors)) {
                $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
                $this->_response["Message"] = current($Errors);
                $this->_response["Errors"] = $Errors;
                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                $this->set_response($this->_response);
            } else {
                $this->load->model("upload_model");
                $NewImage = $this->upload_model->save_image($Image);
                $this->_response["Data"] = array(
                    "ImageGUID" => $NewImage['ImageGUID'],
                );

                $ProfileGUID = safe_array_key($this->_data, 'ProfileGUID', "");
                $ModuleName = safe_array_key($this->_data, 'ModuleName', "");
                if ($ModuleName == 'ProfilePicture' && $ProfileGUID != "") {
                    $this->db->update('Users', [
                        'ProfilePicture' => $NewImage['ImageGUID'],
                            ], [
                        'UserGUID' => $ProfileGUID,
                    ]);
                }

                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                $this->set_response($this->_response);
            }
        }
    }

    /*
      |--------------------------------------------------------------------------
      | Use to delete images
      | @Inputs: Images
      |--------------------------------------------------------------------------
     */

    function delete_images_post() {
        $this->_response["ServiceName"] = "upload/delete_images";
        $this->form_validation->set_rules('Images', 'Images', 'trim');
        if ($this->form_validation->run() == FALSE) {

            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            $this->load->model("upload_model");
            $Images = safe_array_key($this->_data, 'Images', []);
            foreach ($Images as $Image) {
                $this->upload_model->delete_image_by_guid($Image['ImageGUID']);
            }
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        }
    }

    function file_streem_post() {

        $this->_response["ServiceName"] = "upload/file_streem";
        $this->form_validation->set_rules('ModuleName', 'ModuleName', 'trim|required|in_list[ProfilePicture,Prescription,Insurancecard]');
        $this->form_validation->set_rules('ProfileGUID', 'ProfileGUID', 'trim');
        if ($this->form_validation->run() == FALSE) {
            $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
            $errors = $this->form_validation->error_array();
            $this->_response["Message"] = current($errors);
            $this->_response["Errors"] = $errors;
            $this->benchmark->mark('code_end');
            $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->set_response($this->_response);
        } else {
            if (!is_dir(UPLOAD_TEMP)) {
                mkdir(UPLOAD_TEMP, 0777, TRUE);
            }
            $config['upload_path'] = './' . UPLOAD_TEMP;
            $config['allowed_types'] = 'gif|jpg|png';
            $config['max_size'] = 100000;
            $config['max_width'] = 102400;
            $config['max_height'] = 76800;
            $config['encrypt_name'] = TRUE;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('FileField')) {
                $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
                $errors['FileField'] = $this->upload->display_errors("", "");
                $this->_response["Message"] = current($errors);
                $this->_response["Errors"] = $errors;
                $this->benchmark->mark('code_end');
                $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                $this->set_response($this->_response);
            } else {
                $upload_data = $this->upload->data();
                $full_path = $upload_data['full_path'];
                $file_name = $upload_data['file_name'];
                $Thumbs = array(
                    "72X72",
                    "90X90",
                    "200X200",
                    "250X250",
                    "560X660",
                    "126X133",
                    "133X133",
                    "197X150",
                );
                $Attachment = file_get_contents($full_path);

                $Image["ImageType"] = "jpg";
                $Image["ImageData"] = base64_encode($Attachment);
                $Image["IsDeleted"] = "0";
                $Errors = array();

                $this->load->library('image_lib');
                //thumb needs to be create
                foreach ($Thumbs as $ThumbSize) {
                    $Thumb = UPLOAD_TEMP . $ThumbSize . "_" . $file_name;
                    $ThumbFileString = "";
                    list($w, $h) = explode("X", $ThumbSize);

                    $config['image_library'] = 'gd2';
                    $config['source_image'] = $full_path;
                    $config['create_thumb'] = FALSE;
                    $config['maintain_ratio'] = TRUE;
                    $config['width'] = $w;
                    $config['height'] = $h;
                    $config['new_image'] = $Thumb;
                    $this->image_lib->initialize($config);
                    if ($this->image_lib->resize()) {
                        $ThumbFileString = base64_encode_image($Thumb);
                        unlink($Thumb);
                    } else {
                        $Errors[$ThumbSize] = $this->image_lib->display_errors('', '');
                    }
                    $Image["ImageData" . $ThumbSize] = $ThumbFileString;
                }
                unlink($full_path);
                if (!empty($Errors)) {
                    $this->_response["StatusCode"] = self::HTTP_FORBIDDEN;
                    $this->_response["Message"] = current($Errors);
                    $this->_response["Errors"] = $Errors;
                    $this->benchmark->mark('code_end');
                    $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                    $this->set_response($this->_response);
                } else {
                    $this->load->model("upload_model");
                    $NewImage = $this->upload_model->save_image($Image);
                    $this->_response["Data"] = array(
                        "ImageGUID" => $NewImage['ImageGUID'],
                    );

                    $ProfileGUID = safe_array_key($this->_data, 'ProfileGUID', "");
                    $ModuleName = safe_array_key($this->_data, 'ModuleName', "");
                    if ($ModuleName == 'ProfilePicture' && $ProfileGUID != "") {
                        $this->db->update('Users', [
                            'ProfilePicture' => $NewImage['ImageGUID'],
                                ], [
                            'UserGUID' => $ProfileGUID,
                        ]);
                    }

                    $this->benchmark->mark('code_end');
                    $this->_response["ElapsedTime"] = $this->benchmark->elapsed_time('code_start', 'code_end');
                    $this->set_response($this->_response);
                }
            }
        }
    }

}
