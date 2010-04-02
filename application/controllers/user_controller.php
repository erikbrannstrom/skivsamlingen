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
	
	function delete($record = NULL)
	{
		if($record != NULL && $this->is_digits($record) && $this->auth->isUser() ) {

			$res = $this->db->where('id', $record)
					 		->where('user_id', $this->auth->getUserID())
					 		->delete('records_users');
			$this->notice->success('Skivan har tagits bort.');
			redirect('user/profile/'.$this->auth->getUsername());
		} else {
			$this->notice->error('Skivan kunde inte tas bort.');
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
	
}

/* End of file user.php */
/* Location: ./system/application/controllers/user.php */