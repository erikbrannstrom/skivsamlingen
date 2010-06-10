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
		$this->CI->load->model('User');
        $this->User = new User();
		$this->session =& $this->CI->session;
		if($this->session->userdata('username') !== false) {
			$this->is_user = true;
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
			return true;
		} else {
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