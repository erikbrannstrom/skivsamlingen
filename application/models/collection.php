<?php

class Collection extends MY_Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'records_users';
    }

    public function deleteItem($key, $user_id) {
        $this->db->where('id', $key)->where('user_id', $user_id)->delete('records_users');
        return $this->db->affected_rows();
    }

    public function deleteAll($user_id) {
        $this->db->where('user_id', $user_id)->delete('records_users');
        return $this->db->affected_rows();
    }

    public function addItem($user_id, $record_id) {
        $this->create(array('user_id' => $user_id, 'record_id' => $record_id), false);
    }

}