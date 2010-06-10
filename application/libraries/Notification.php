<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Notification Class
 *
 * Makes notification messages simple.
 *
 */

class Notification {
	private $CI = NULL;

	public function __construct()
	{
		$this->CI =& get_instance();
        $this->CI->load->model('Message');
        $this->CI->load->model('User');
	}

	public function createMessage($message, $where = null)
	{
		if($where == null) {
            $message_id = $this->CI->Message->create(array('message' => $message));
        } else {
            $message_id = $this->CI->Message->create(array('message' => $message), false, $where);
        }
	}

    public function getMessages($user)
    {
        $messages = $this->CI->Message->getUserMessages($user);
        $output = '';
        foreach($messages as $message) {
            $output .= '<div class="notification">' . $message->message . '</div>\n';
        }
        return $output;
    }

    public function markAsRead($user)
    {
        $this->CI->Message->markAsRead($user);
    }

}