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
        $this->load->library('form_validation');
        $this->load->model('Collection');
        $this->load->model('Record');
        if($this->input->post('record') !== false) {
            $record = $this->input->post('record');
            $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
			$res = $this->Collection->deleteItem($record, $this->auth->getUserID());
			if($res == 1) {
				$this->notice->success($this->data['record']->name.' - '.$this->data['record']->title.' har tagits bort.');
			}
            redirect('users/'.$this->auth->getUsername());
        } else {
            $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
        }
        
        /*
		if($record != NULL && $this->is_digits($record) ) {
			$this->load->model('Collection');
			$res = Collection::deleteItem($record, $this->auth->getUserID());
			if($res == 1) {
				$this->notice->success('Skivan har tagits bort.');
				redirect('user/profile/'.$this->auth->getUsername());
			}
		}
		$this->notice->error('Skivan kunde inte tas bort.');
		redirect('welcome');*/
	}

    function comment($record = null)
    {
        $this->load->library('form_validation');
        $this->load->model('Comment');
        $this->load->model('Record');
        if($this->input->post('record')) {
            $record = $this->input->post('record');
        }
        $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
        $this->form_validation->set_rules('comment', 'Kommentar', 'max_length[255]');
        if($this->form_validation->run() !== false) {
            if($this->input->post('action') == 'edit') {
                $this->Comment->set($this->auth->getUserID(), $record, $this->input->post('comment'));
            } else {
                $this->Comment->delete($this->auth->getUserID(), $record);
            }
            redirect('users/'.$this->auth->getUsername());
        }
    }
	
	function add()
	{
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->form_validation->set_rules('artist', 'Artist', 'required|max_length[100]|xss_clean');
		$this->form_validation->set_rules('title', 'Titel', 'required|max_length[150]|xss_clean');
		$this->form_validation->set_rules('year', 'År', 'is_natural_no_zero|exact_length[4]');
		$this->form_validation->set_rules('format', 'Format', 'xss_clean|max_length[30]');
        $this->form_validation->nonce();

		if ($this->form_validation->run() !== FALSE) { // If validation has completed
            $this->form_validation->save_nonce();
			$this->load->model('Record');
			$this->load->model('Artist');
			$this->load->model('Collection');
            $artist_id = $this->Artist->getArtistId($this->input->post('artist'));
            $record_id = $this->Record->getId($artist_id, $this->input->post('title'),
                    $this->input->post('year'), $this->input->post('format'));
            $this->Collection->addItem($this->auth->getUserId(), $record_id);
			$this->notice->success('Skiva tillagd.');
			redirect('collection/add');
		}
	}
	
}

/* End of file user.php */
/* Location: ./system/application/controllers/user.php */