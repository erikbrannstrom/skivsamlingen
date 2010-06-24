<?php

class Comment extends MY_Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'records_users';
        $this->fields = array(
            array('comment', 'Kommentar', 'max_length[255]|xss_clean')
        );
    }

    public function set($user_id, $record_id, $text) {
        $this->update(array('user_id' => $user_id, 'id' => $record_id),
                array('comment' => $text), false);
    }

    public function delete($user_id, $record_id) {
        $this->set($user_id, $record_id, null);
    }

}