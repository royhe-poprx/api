<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Media extends CI_Controller {

        public function __construct(){
            parent::__construct();
            $this->load->helper(array('form', 'url'));
            	
        }

        public function index(){
            $this->load->view('media-upload-view');
        } 
        
        public function do_upload_v1(){
            try {
                if (isset($_POST)) {
                    if($_FILES['userfile']['size'] > 0) { 

                        $photo['filename'] = $_FILES['userfile']['name'];
                        $photo['filesize'] = $_FILES['userfile']['size'];
                        $photo['filetype'] = $_FILES['userfile']['type'];
                        $photo['filetype'] = 'image/jpeg';

                        $tmpName  = $_FILES['userfile']['tmp_name'];

                        $fp      = fopen($tmpName, 'r');
                        $content = fread($fp, filesize($tmpName));
                        //$content = addslashes($content);
                        //$content = $content; //Code Igniter already has addslashes
                        fclose($fp);

                        if(!get_magic_quotes_gpc()) {
                            $photo['filename'] = addslashes($photo['filename']);
                        }

                        // get originalsize of image
                        $im = imagecreatefromstring($content);
                        $width = imagesx($im);
                        $height = imagesy($im);            

                        // Set thumbnail-height to 180 pixels
                        $imgh = 300;                                          
                        // calculate thumbnail-height from given width to maintain aspect ratio
                        $imgw = $width / $height * $imgh;                                          
                        // create new image using thumbnail-size
                        $thumb=imagecreatetruecolor($imgw,$imgh);                  
                        // copy original image to thumbnail
                        imagecopyresampled($thumb,$im,0,0,0,0,$imgw,$imgh,ImageSX($im),ImageSY($im)); //makes thumb

                        /*
                        imagejpeg($thumb, $photo['filename'], 80);  //imagejpeg($resampled, $fileName, $quality);            
                        $instr = fopen($photo['filename'],"rb");  //need to move this to a safe directory
                        $image = fread($instr,filesize($photo['filename']));

                        $photo['filecontent']  = $image;
                        */
                        //------
                        $thumbsdir = ini_get('upload_tmp_dir') ;
                        imagejpeg($thumb, $thumbsdir.$photo['filename'], 80);  //imagejpeg($resampled, $fileName, $quality);            
                        $instr = fopen($thumbsdir.$photo['filename'],"rb");  //need to move this to a safe directory
                        $image = fread($instr,filesize($thumbsdir.$photo['filename']));

                        $photo['filecontent']  = $image;

                        unlink($thumbsdir.$photo['filename']);

                    }
                }
            } catch (Exception $e) {
            
        }
        }
}
?>

