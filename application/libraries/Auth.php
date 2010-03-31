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
		$this->session =& $this->CI->session;
		$this->db =& $this->CI->db;
		if($this->session->userdata('username') !== false) {
			$this->is_user = true;
		} else {
			$this->is_user = false;
		}
	}

	public function login($username, $password)
	{
		$db_user = $this->db->where('username', $username)->get('users')->row();
		$this->CI->load->model('User');
		// Fix users without salted passwords.
		if($db_user->password == sha1($password)) {
			$this->db->where('id', $db_user->id)->update(array('password' => User::encrypt_password($username, $password)));
			$this->CI->notice->info('Ditt lösenord har blivit säkrare, yay!', 'safer');
		}
		if($db_user->password == User::encrypt_password($username, $password)) {
			$this->session->set_userdata('username',$db_user->username);
			$this->session->set_userdata('user_id',$db_user->id);
			$this->CI->notice->info('Du är inloggad!', 'login');
			return true;
		}
		$this->CI->notice->error('Inloggningen misslyckades.');
		return false;
	}
	
	public function logout()
	{
		if($this->is_user) {
			$this->session->unset_userdata('username');
			$this->session->unset_userdata('user_id');
			$this->CI->notice->info('Du är utloggad!','logout');
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
	
	public function isUser()
	{
		return $this->is_user;
	}
	
	public function isGuest()
	{
		return !$this->is_user;
	}

}