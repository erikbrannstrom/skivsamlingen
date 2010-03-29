<?php

class UserController extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();	
	}
	
	function index()
	{
		if($this->auth->isUser()) {
			echo "is user!";
		} else {
			echo "is not user!";
		}
		exit;
	}

	function profile($username = NULL)
	{
		if($username == NULL) {
			redirect('welcome');
		}
		$this->data['page_title'] = 'Local: Skivsamlingen - '.$username;
		
		$user = Doctrine_Query::create()->select('u.username, u.name, r.title, r.year, r.format, a.id, a.name')->from('User u, u.Records r, r.Artist a')->where('u.username = ?', $username)->orderBy('a.name ASC, r.title ASC, r.year DESC');
		//$user = Doctrine_Query::create()->select('u.username, u.name, r.title, r.year, r.format')->from('User u, u.Records r')->where('u.username = ?', $username)->orderBy('r.title ASC, r.year DESC');
		$this->firephp->log($user->getSqlQuery());

		$num_records = Doctrine_Query::create()->select('r.id, a.name, COUNT(r.id) as num')->from('Artist a INDEXBY a.id, a.Records r, r.Users u')->where('u.username = ?', $username)->groupBy('a.id')->setHydrationMode(Doctrine::HYDRATE_ARRAY);
		
		$user = $user->execute();
		$this->data['user'] = $user[0];
		$this->data['num_records'] = $num_records->execute();
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
	
}

/* End of file user.php */
/* Location: ./system/application/controllers/user.php */