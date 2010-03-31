<?php

class User_Controller extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();	
	}
	
	function index()
	{
		redirect('welcome');
	}

	function profile($username = NULL)
	{
		if($username == NULL) {
			redirect('welcome');
		}
		$this->data['page_title'] = 'Local: Skivsamlingen - '.$username;
		
		$user = $this->db->where('username', $username)
						 ->get('users')->row();
						 
		$q_num_records = $this->db->select('COUNT(*) AS num')
				 ->from('records_users ru')
				 ->join('users u', 'ru.user_id = u.id')
				 ->where('u.username', $username)
				 ->group_by('u.id')
				 ->get();
		$this->data['num_records'] = $q_num_records->row()->num;
						 
		$this->load->library('pagination');

		$config['base_url'] = base_url() . 'user/profile/'. $username.'/';
		$config['total_rows'] = $this->data['num_records'];
		$config['per_page'] = 20;
		$config['uri_segment'] = 4;
		
		$this->pagination->initialize($config);
		
		$start_record = $this->uri->segment(4, 0);
		
		$this->data['pagination'] = $this->pagination->create_links(); 
		
		$this->db->select('r.title, r.year, r.format, a.name, a.id AS artist_id, ru.id')
				 ->from('records_users ru')
				 ->join('records r', 'r.id = ru.record_id', 'left')
				 ->join('artists a', 'r.artist_id = a.id', 'left')
				 ->where('ru.user_id', $user->id)
				 ->order_by('a.name ASC, r.title ASC, r.year DESC')
				 ->limit(20, $start_record);
		$this->data['q_records'] = $this->db->get();
		
		$this->firephp->log($this->db->last_query());
		
		$this->data['q_records_per_artist'] = $this->db
				 ->select('a.id, a.name, COUNT(r.id) AS num')
				 ->from('artists a')
				 ->join('records r', 'a.id = r.artist_id', 'left')
				 ->join('records_users ru', 'r.id = ru.record_id', 'left')
				 ->join('users u', 'u.id = ru.user_id', 'left')
				 ->where('u.id', $user->id)
				 ->group_by('a.id')
				 ->order_by('a.name ASC')
				 ->get();
		$this->data['user'] = $user;
	}
	
	function search($query = NULL)
	{
		if($query == NULL) {
			$query = $this->input->post('query');
		}
		if($query === FALSE) {
			redirect('welcome');
		}
		$users = Doctrine_Query::create()->from('User u')->where('u.username LIKE ? OR u.name LIKE ?', array('%'.$query.'%', '%'.$query.'%'))->execute();
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
		
		$this->auth->login('erik.brannstrom','hvlmki');
		redirect('welcome');
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