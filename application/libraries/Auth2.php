<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Auth Class
 *
 * Makes authentication simple
 * 
 */
 
class Auth {
	var $CI = NULL;
	var $cookie_name = 'rc_rem';
	var $username = NULL;
	var $password = NULL;

	function Auth()
	{
		$this->__construct();
	}
	
	function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->session->set_userdata('online', $this->_check());
	}

	/**
	 * Login and sets session variables
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function login($username = '', $password = '', $hashed = FALSE)
	{	
		//Make sure login info was sent
		if($username == '' OR $password == '') {
			return false;
		}

		//Check if already logged in
		if($this->CI->session->userdata('online') === TRUE) {
			return false;
		}
		
		$password = ($hashed) ? $password : sha1($password);
		$this->CI->db->select('usr, id, level')->from('users')->where('usr',$username)->where('psw',$password);
		$query = $this->CI->db->get();
		if($query->num_rows() == 1) {
			$userinfo = $query->row();
			$session_data = array(
				'username'  => $userinfo->usr,
				'uid'     => $userinfo->id,
				'online' => TRUE
			);
			$this->CI->session->set_userdata($session_data);
			$this->username = $username;
			$this->password = $password;
			return true;
		} else {
			return false;
		}
	}
	
	function remember()
	{
		$this->CI->load->helper('cookie');
		set_cookie($this->cookie_name, 'usr='.$this->username.';psw='.$this->password, (60*60*24*14));
	}

	/**
	 * Logout user
	 *
	 * @access	public
	 * @return	boolean
	 */
	function logout()
	{
		$this->CI->load->helper('cookie');
		$this->CI->session->sess_destroy(array('username' => '', 'uid' => ''));
		$this->CI->session->set_userdata('online',FALSE);
		delete_cookie($this->cookie_name);
		return true;
	}
	
	function check()
	{
		return $this->CI->session->userdata('online');
	}

	function _check()
	{
		if($this->CI->session->userdata('online') === TRUE) {
			return true;
		} else {
			$this->CI->load->helper('cookie');
			if(get_cookie('rc_rem')) {
				$values = explode(";", urldecode(get_cookie('rc_rem')));
				$usr = substr($values[0], strpos($values[0], "=") + 1);
				$psw = substr($values[1], strpos($values[1], "=") + 1);
				if(strlen($psw) == 40) {// check the validity
					return $this->login($usr,$psw,TRUE);
				}
			} else {
				return false;
			}
		}
	}
	
}
?>