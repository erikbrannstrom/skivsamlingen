<?php

class Collection_Controller extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();
		if($this->auth->isGuest()) {
			$this->notice->error('Du måste vara inloggad för att kunna göra detta.');
			redirect('welcome');
		}
		$this->load->model('Collection');
	}
	
	function index()
	{
		redirect('welcome');
	}

	function delete($record = NULL)
	{
		if($record != NULL && $this->is_digits($record) ) {
			$this->load->model('Collection');
			$res = Collection::deleteItem($record, $this->auth->getUserID());
			if($res == 1) {
				$this->notice->success('Skivan har tagits bort.');
				redirect('user/profile/'.$this->auth->getUsername());
			}
		}
		$this->notice->error('Skivan kunde inte tas bort.');
		redirect('welcome');
	}
	
	function add()
	{
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->form_validation->set_rules('artist', 'Artist', 'required|max_length[100]|xss_clean');
		$this->form_validation->set_rules('title', 'Titel', 'required|max_length[150]|xss_clean');
		$this->form_validation->set_rules('year', 'År', 'is_natural_no_zero|exact_length[4]');
		$this->form_validation->set_rules('format', 'Format', 'xss_clean|max_length[30]');

		if ($this->form_validation->run() !== FALSE) { // If validation has completed
			$this->load->model('Record');
			$this->load->model('Artist');
			$this->load->model('Collection');
			$rec = new Record();
			$rec->artist_id = Artist::getArtistID($this->input->post('artist'));
			$rec->title = $this->input->post('title');

			if($var = $this->input->post('year'))
				$rec->year = $var;
			if($var = $this->input->post('format'))
				$rec->format = $var;
			Collection::addItem($this->auth->getUserID(), $rec->getID());
			$this->notice->success("{$rec->title} har lagts till.");
			redirect('user/profile/'.$this->auth->getUsername());
		}
	}
	
	private function is_digits($element) {
		return !preg_match ("/[^0-9]/", $element);
	}
	
}

/* End of file user.php */
/* Location: ./system/application/controllers/user.php */