<?php

class Message extends MY_Model
{
	
	public function __construct($column = NULL, $key = NULL)
	{
		parent::__construct();
		$this->table = 'messages';
        $this->fields = array(
            array('message', 'Meddelande', 'required|max_length[255]')
        );
	}

    public function create($data = null, $post = true, $users = null)
    {
        $message_id = parent::create($data, $post);
        $query = "INSERT INTO messages_users (message_id, user_id) "
            . "SELECT $message_id, id FROM users";
        if($users) {
            $query .= " WHERE $users";
        }
        $this->db->query($query);
        return $message_id;
    }

    public function getUserMessages($user)
    {
        return $this->db->where('user_id', $user)
                 ->from('messages_users mu')
                 ->join('messages m', 'mu.message_id = m.id')
                 ->get()
                 ->result();
    }

    public function markAsRead($user, $message)
    {
        $this->db->set('read', 1)
                 ->where('user_id', $user)
                 ->update('messages_users');
    }

}