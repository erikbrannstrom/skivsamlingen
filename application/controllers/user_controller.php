<?php

class User_Controller extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();
		$this->load->model('User');
	}
	
	function index()
	{
		redirect('welcome');
	}

	function profile($username = NULL)
	{
		$user = new User('username', $username);
		if(!$user->exists()) {
			$this->notice->error('Användaren kunde inte hittas.');
			redirect('welcome');
		}
		$this->data['page_title'] = 'Local: Skivsamlingen - '.$username;
						 
		$this->load->library('pagination');
		$config['base_url'] = base_url() . 'user/profile/'. $username.'/';
		$config['total_rows'] = $user->getNumberOfRecords();
		$config['per_page'] = 20;
		$config['uri_segment'] = 4;
		$this->pagination->initialize($config);
		$offset = $this->uri->segment(4, 0);

		$this->data['user'] = $user;
		$this->data['num_records'] = $config['total_rows'];
		$this->data['pagination'] = $this->pagination->create_links(); 
		$this->data['records'] = $user->getRecords(20, $offset);
	}
	
	function search($query = NULL)
	{
		if($query == NULL) {
			$query = $this->input->post('query');
		}
		if($query === FALSE) {
			redirect('welcome');
		}
		$users = User::search($query);
		$this->data['query'] = $query;
		$this->data['users'] = $users;
	}
	
	function register()
	{		
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->form_validation->set_rules('username', 'Användarnamn', 'required|xss_clean|min_length[3]|max_length[20]|callback_alpha_dash_dot');
		$this->form_validation->set_rules('password', 'Lösenord', 'required|min_length[6]|matches[passconf]');
		$this->form_validation->set_rules('passconf', 'Lösenordsbekräftelse', 'required');
		$this->form_validation->set_rules('email', 'E-post', 'valid_email|max_length[80]');
		$this->form_validation->set_rules('name', 'Namn', 'max_length[50]');
		$this->form_validation->set_rules('birth', 'Födelsedag', 'callback_date_check');
		$this->form_validation->set_rules('sex', 'Kön', 'callback_valid_sex');
				
		if ($this->form_validation->run() !== FALSE) { // If validation has completed
			$this->load->model('User');
			$user = new User();
			$user->username = $this->input->post('username', TRUE);
			$user->password = User::encrypt_password($user->username, $this->input->post('password'));
			if($var = $this->input->post('email'))
				$user->email = $var;
			if($var = $this->input->post('name'))
				$user->name = $var;
			if($var = $this->input->post('sex'))
				$user->sex = $var;
			if($var = $this->input->post('birth'))
				$user->birth = $var;
			$user->save();
			$this->notice->success('Korrekt!');
			redirect('welcome');
		}
	}
	
	function login()
	{
		if($this->auth->isUser())
			redirect('user/profile/'.$this->auth->getUsername());
		$username = $this->input->post('username', TRUE);
		if( $username !== FALSE ) {
			if($this->auth->login($username, $this->input->post('password'))) {
				$this->notice->success('Du är inloggad!', 'login');
				redirect('user/profile/'.$this->auth->getUsername());
			} else {
				$this->notice->error('Felaktiga användaruppgifter.');
				redirect('user/login');
			}
		}
	}
	
	function logout()
	{
		$this->auth->logout();
		redirect('welcome');
	}
	
	private function is_digits($element) {
		return !preg_match ("/[^0-9]/", $element);
	}
	
	function username_check($str)
	{
		if ($str == 'test')
		{
			$this->form_validation->set_message('username_check', 'The %s field can not be the word "test"');
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	function valid_sex($str)
	{
		if ($str == 'x' || $str == 'm' || $str == 'f')
		{
			return TRUE;
		}
		else
		{
			$this->form_validation->set_message('valid_sex', '%s måste vara man, kvinna eller hemligt.');
			return FALSE;
		}
	}
	
	/**
	 * Alpha-numeric with underscores, dashes and dots
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function alpha_dash_dot($str)
	{
		return ( ! preg_match("/^([-a-z0-9\._-])+$/i", $str)) ? FALSE : TRUE;
	}
	
	function date_check($date) {
		$ddmmyyy='(19|20)[0-9]{2}[- \/.](0[1-9]|1[012])[- \/.](0[1-9]|[12][0-9]|3[01])';
		if(preg_match("/$ddmmyyy$/", $date)) {
			return true;
		} else {
			$this->form_validation->set_message('date_check', '%s är felformaterad. Använd ÅÅÅÅ-MM-DD.');
			return false;
		}
	} 
	
}

/* End of file user.php */
/* Location: ./system/application/controllers/user.php */