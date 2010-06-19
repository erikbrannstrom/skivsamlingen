<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Auth Class
 *
 * Makes authentication simple.
 * 
 */
 
class Auth {
	private $is_user;
	private $CI = NULL;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->library('session');
        $this->CI->load->library('notice');
        $this->CI->load->database();
		$this->CI->load->model('User');
        $this->CI->load->helper('cookie');
        $this->User = new User();
		$this->session =& $this->CI->session;
        $this->db =& $this->CI->db;
		if($this->session->userdata('username') !== false) {
			$this->is_user = true;
		} elseif(get_cookie('skiv_remember') !== false) {
            $this->is_user = $this->validateCookie();
        } else {
			$this->is_user = false;
		}
	}

	public function login($username, $password)
	{
		$db_user = $this->User->fetchOne(array('username' => $username));
		if(!$db_user)
			return false;
		// Fix users without salted passwords.
		if($db_user->password == sha1($password)) {
			$data['password'] = $this->User->encrypt_password($username, $password);
			$this->User->update(array('username' => $username), $data);
			$this->CI->notice->info('Ditt lösenord har blivit säkrare!', 'safer');
            $this->session->set_userdata('username',$db_user->username);
			$this->session->set_userdata('user_id',$db_user->id);
			return true;
		} else if($db_user->password == $this->User->encrypt_password($username, $password)) {
			$this->session->set_userdata('username',$db_user->username);
			$this->session->set_userdata('user_id',$db_user->id);
			return true;
		}
		return false;
	}
	
	public function logout()
	{
		if($this->isUser()) {
			$this->session->unset_userdata('username');
			$this->session->unset_userdata('user_id');
            if(get_cookie('skiv_remember')) {
                $persist = explode(';', get_cookie('skiv_remember'));
                delete_cookie('skiv_remember');
                $this->db->where('user_id', $persist[0])
                     ->where('series', $persist[1])
                     ->delete('persistent_logins');
            }
			return true;
		} else {
			return false;
		}
	}
    
    public function remember()
    {
        $token = mt_rand() + mt_rand();
        $persist = array(
            'user_id' => $this->getUserID(),
            'series' => sha1(mt_rand()),
            'token' => $token
        );
        $this->db->insert('persistent_logins', $persist);
        set_cookie('skiv_remember', implode(';', $persist), 60*60*24*30);
    }

    private function validateCookie()
    {
        $persist = explode(';', get_cookie('skiv_remember'));
        $result = $this->db->where('user_id', $persist[0])
                           ->where('series', $persist[1])
                           ->get('persistent_logins')
                           ->row();
        if(!$result) {
            return false;
        } elseif($result->token == $persist[2]) {
            // Set login session
            $db_user = $this->User->fetchOne($persist[0]);
            $this->session->set_userdata('username',$db_user->username);
			$this->session->set_userdata('user_id',$db_user->id);
            // Update persistent login
            $token = mt_rand() + mt_rand();
            $this->db->set('token', $token)
                     ->where('user_id', $persist[0])
                     ->where('series', $persist[1])
                     ->update('persistent_logins');
            delete_cookie('skiv_remember');
            $persist[2] = $token;
            set_cookie('skiv_remember', implode(';', $persist), 60*60*24*30);
            return true;
        } else {
            $this->CI->notice->error('Ett försök att hacka detta konto har upptäckts. För din säkerhet har du nu loggats ut.', 'attack');
            $this->db->where('user_id', $persist[0])
                     ->delete('persistent_logins');
            delete_cookie('skiv_remember');
            $this->logout();
            return false;
        }
    }
	
	public function getUsername()
	{
		return $this->session->userdata('username');
	}

	public function getUserID()
	{
		return $this->session->userdata('user_id');
	}
	
	public function getUser()
	{
		$user = $this->User->fetchOne($this->getUserID());
		if($user)
			return $user;
		else
			return NULL;
	}
	
	public function isUser()
	{
		return $this->is_user;
	}
	
	public function isGuest()
	{
		return !$this->is_user;
	}

}