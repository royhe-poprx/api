<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Description of image_model
 *
 * @author nitins
 */
class Upload_model extends CI_Model {

    private $imgdb = null;

    public function __construct() {
        parent::__construct();
        $this->imgdb = $this->db;
    }

    /**
     * Save image
     * @param type $image
     * @return boolean
     */
    public function save_image($image = array()) {
        if (!empty($image)) {
            if (!isset($image['ImageGUID'])) {
                $image['ImageGUID'] = guid();
            }
            if (!isset($image['ImageName'])) {
                $image['ImageName'] = guid();
            }
            $this->imgdb->insert(IMAGES_DB . ".Images", $image);
            $id = $this->imgdb->insert_id();
            return $this->get_image_by_id($id);
        }
        return false;
    }

    /**
     * Return Image in basis of Image Id
     * @param type $ImageID
     * @return type
     */
    public function get_image_by_id($ImageID) {
        $this->imgdb->select('ImageGUID, ImageName, ImageType, ImageData');
        $this->imgdb->where('ImageID', $ImageID);
        $Query = $this->imgdb->get(IMAGES_DB . ".Images");
        //print_r($this->imgdb);die;
        return $Query->row_array();
    }

    /**
     * Return Image in basis of Image GUId
     * @param type $ImageGUID
     * @return type
     */
    public function get_image_by_guid($ImageGUID, $ImageSize) {
        $field_name = 'ImageData' . $ImageSize;
        if ($this->imgdb->field_exists($field_name, IMAGES_DB . ".Images")) {
            $this->imgdb->select($field_name . ' as ImageData');
        } else {
            $this->imgdb->select('ImageData');
        }
        $this->imgdb->select('ImageGUID, ImageName, ImageType');
        $this->imgdb->where('ImageGUID', $ImageGUID);
        return $this->imgdb->get(IMAGES_DB . ".Images")->row_array();
    }

    /**
     * Save batch image
     * @param type $image
     * @return boolean
     */
    public function save_batch_image($images = array()) {
        $data = array();
        if (!empty($images)) {
            foreach ($images as $image) {
                if (!isset($image['ImageGUID'])) {
                    $image['ImageGUID'] = get_guid();
                }
                if (!isset($image['ImageName'])) {
                    $image['ImageName'] = get_guid();
                }
                $data[] = $image;
            }
            $this->imgdb->insert_batch(IMAGES_DB . ".Images", $data);
            return $this->imgdb->affected_rows();
        }
        return false;
    }

    /**
     * Return Image in basis of Image GUId
     * @param type $ImageGUID
     * @return type
     */
    public function delete_image_by_guid($ImageGUID) {
        $this->imgdb->where('ImageGUID', $ImageGUID);
        return $this->imgdb->delete(IMAGES_DB . ".Images");
    }

    public function save_image_from_old($ProfilePicture) {
        if (!empty($ProfilePicture) && is_array($ProfilePicture)) {
            $UserProfilePicture = [
                "ImageGUID" => $ProfilePicture['ImageGUID'],
                "ImageName" => $ProfilePicture['ImageName'],
                "ImageType" => $ProfilePicture['ImageType'],
                "ImageData" => $ProfilePicture['ImageData'],
                "ImageData72X72" => $ProfilePicture['ImageData72X72'],
                "ImageData90X90" => $ProfilePicture['ImageData90X90'],
                "ImageData200X200" => $ProfilePicture['ImageData200X200'],
                "ImageData250X250" => $ProfilePicture['ImageData250X250'],
                "ImageData560X660" => $ProfilePicture['ImageData560X660'],
                "ImageData126X133" => $ProfilePicture['ImageData126X133'],
                "ImageData133X133" => $ProfilePicture['ImageData133X133'],
                "ImageData197X150" => $ProfilePicture['ImageData197X150'],
                "IsDeleted" => $ProfilePicture['IsDeleted'],
            ];
            $this->save_image($UserProfilePicture);
            $Img = $ProfilePicture['ImageGUID'];
        } elseif (!empty($User['ProfilePicture'])) {
            $Img = $ProfilePicture;
        } else {
            $Img = "";
        }
        return $Img;
    }

}
