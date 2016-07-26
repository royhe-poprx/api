<?php

/**
 * Php Log class
 *
 * Display the php log
 *
 * @category	Log
 * @author		Nicolás Bistolfi
 * @link		https://dl.dropboxusercontent.com/u/20596750/code/php/log.php
 */
class Logs extends CI_Controller {

    private $logPath; //path to the php log

    /**
     * 	Class constructor
     */

    function __construct() {
        parent::__construct();
        $this->logPath = APPPATH . 'logs';
    }

    /**
     * index: Shows the php error log
     * @access public
     */
    public function index() {
        $this->secure();
        $scanned_directory = array_diff(scandir($this->logPath), array('..', '.'));
        if (!empty($scanned_directory)) {
            foreach ($scanned_directory as $sd) {
                if ($sd != "index.html") {
                    if (@is_file($this->logPath . "/" . $sd)) {
                        echo "<h1>$sd <a href=" . site_url('logs/delete/' . $sd) . ">delete</a></h1>";
                        echo nl2br(@file_get_contents($this->logPath . "/" . $sd));
                        echo "<br>";
                    }
                }
            }
        } else {
            echo "No Logs.";
        }
        exit;
    }

    /**
     * index: Shows the php error log
     * @access public
     */
    public function phpinfo() {
        $this->secure();
        echo phpinfo();
        exit;
    }

    public function old_imported_users() {
        $this->secure();
        $Users = $this->app->get_rows('Users', 'UserID, Email, FirstName, LastName', [
            'Email !=' => "",
            'UserTypeID' => 2,
        ]);
        $this->load->library('table');
        $this->table->set_heading('UserID', 'Email', 'FirstName', 'LastName');
        echo $this->table->generate($Users);
    }

    /**
     * delete: Deletes the php error log
     * @access public
     */
    public function delete($sd) {
        $this->secure();
        if (@is_file($this->logPath . "/" . $sd)) {
            if (@unlink($this->logPath . "/" . $sd)) {
                echo 'PHP Error Log deleted';
            } else {
                echo 'There has been an error trying to delete the PHP Error log ' . $this->logPath . "/" . $sd;
            }
        } else {
            echo 'The log cannot be found in the specified route  ' . $this->logPath . "/" . $sd . '.';
        }
        redirect('logs');
    }

    public function login() {
        $message = '<p>Please enter username and password.</p>';
        if ($this->input->post('Username') && $this->input->post('Password')) {
            $Username = $this->input->post('Username');
            $Password = $this->input->post('Password');
            if ($Username == 'admin' && $Password == '123456') {
                $this->session->set_userdata('LogsAllowed', TRUE);
                redirect(site_url('logs'));
                die();
            } else {
                $message = '<p>Invalid Username or Password.</p>';
            }
        }
        echo $message;
        echo '<form action="' . site_url('logs/login') . '" method="post">
                    UserName <input type="text" name="Username"><br/>
                    Password <input type="password" name="Password"><br/>
                    <input type="submit" value="Login"/>
                </form>';
    }

    public function secure() {
        if ($this->session->userdata('LogsAllowed') == false) {
            redirect(site_url('logs/login'));
            die('');
        }
        return TRUE;
    }

}

?>