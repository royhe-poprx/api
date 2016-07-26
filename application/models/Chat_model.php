<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Description of prescription_model
 *
 * @author nitins
 */
class chat_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 
     * @param type $UserID
     * @param type $Limit
     * @param type $Offset
     * @return type
     */
    public function chats($ActiveUserID, $PatientUserID, $PharmacyUserID, $OnlyNumRows = NULL, $Limit = NULL, $Offset = NULL) {
        $ChatThreadID = $this->get_chat_thread_id($PatientUserID, $PharmacyUserID);

        // Mark chat as read
        $this->db->update('Chats', [
            'ReceiverStatus' => 'READ'
                ], [
            'ChatThreadID' => $ChatThreadID,
            'ReceiverID' => $ActiveUserID,
        ]);

        if (is_null($OnlyNumRows)) {
            $this->db->select('ChatGUID, SenderID, ReceiverID, Text, Type, CreatedAt, ReceiverStatus');
        } else {
            $this->db->select('ChatGUID');
        }

        $this->db->where('ChatThreadID', $ChatThreadID);

        $this->db->from('Chats');

        $this->db->order_by('ChatID', 'DESC');

        if (is_null($OnlyNumRows)) {
            $this->db->limit($Limit, $Offset);
            $Query = $this->db->get();
            $Chats = $Query->result_array();
            foreach ($Chats as $Key => $Chat) {
                $Chats[$Key]['Sender'] = $this->app->get_profile_by_id($Chat['SenderID']);
                $Chats[$Key]['Receiver'] = $this->app->get_profile_by_id($Chat['ReceiverID']);
                unset($Chats[$Key]['SenderID']);
                unset($Chats[$Key]['ReceiverID']);
            }
            return $Chats;
        } else {
            $Query = $this->db->get();
            return $Query->num_rows();
        }
    }

    public function create_chat($SenderID, $ReceiverID, $Text, $Type = "AUTO") {
        $ChatThreadID = $this->get_chat_thread_id($SenderID, $ReceiverID);
        $Chat = [
            'ChatGUID' => guid(),
            'ChatThreadID' => $ChatThreadID,
            'SenderID' => $SenderID,
            'ReceiverID' => $ReceiverID,
            'Text' => $Text,
            'Type' => $Type,
            'CreatedAt' => DATETIME,
            'ReceiverStatus' => 'DRAFT'
        ];
        $this->db->insert('Chats', $Chat);
        $ChatID = $this->db->insert_id();

        $this->app->notify($SenderID, $ReceiverID, 'CHAT_SEND', $ChatID);
        return $ChatID;
    }

    public function get_chat_thread_id($SenderID, $ReceiverID) {
        $this->db->select('ThreadID');

        $this->db->group_start();
        $this->db->where('UserID1', $SenderID);
        $this->db->where('UserID2', $ReceiverID);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('UserID2', $SenderID);
        $this->db->where('UserID1', $ReceiverID);
        $this->db->group_end();

        $this->db->from('ChatThread');

        $Query = $this->db->get();
        $ChatThread = $Query->row_array();

        if (is_null($ChatThread)) {
            $ChatThread = [
                'UserID1' => $SenderID,
                'UserID2' => $ReceiverID,
            ];
            $this->db->insert('ChatThread', $ChatThread);
            $ChatThreadID = $this->db->insert_id();
        } else {
            $ChatThreadID = $ChatThread['ThreadID'];
        }
        return $ChatThreadID;
    }

    public function get_chat_by_id($ChatID) {
        $this->db->select('ChatGUID, SenderID, ReceiverID, Text, Type, CreatedAt, ReceiverStatus');
        $this->db->where('ChatID', $ChatID);
        $this->db->from('Chats');
        $Query = $this->db->get();
        $Chat = $Query->row_array();

        $Chat['Sender'] = $this->app->get_profile_by_id($Chat['SenderID']);

        $Chat['Receiver'] = $this->app->get_profile_by_id($Chat['ReceiverID']);
        unset($Chat['SenderID']);
        unset($Chat['ReceiverID']);
        return $Chat;
    }
    
    
    /**
     * 
     * @param type $UserID
     * @param type $Limit
     * @param type $Offset
     * @return type
     */
    public function unread_chat_count($UserID) {
        $this->db->select('ChatGUID');
        $this->db->where('ReceiverID', $UserID);
        $this->db->where('ReceiverStatus', 'DRAFT');
        $this->db->from('Chats');
        $this->db->order_by('ChatID', 'DESC');
        $Query = $this->db->get();
        return $Query->num_rows();
    }

}
