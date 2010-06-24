<?php

class Collection_Controller extends MY_Controller {

    function __construct() {
        parent::MY_Controller();
        if ($this->auth->isGuest()) {
            $this->notice->error('Du måste vara inloggad för att kunna göra detta.');
            redirect();
        }
        $this->load->model('Collection');
    }

    function index() {
        redirect();
    }

    function delete($record = NULL) {
        $this->load->library('form_validation');
        $this->load->model('Collection');
        $this->load->model('Record');
        if ($this->input->post('record') !== false) {
            $record = $this->input->post('record');
            $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
            $res = $this->Collection->deleteItem($record, $this->auth->getUserID());
            if ($res == 1) {
                $this->notice->success($this->data['record']->name . ' - ' . $this->data['record']->title . ' har tagits bort.');
            }
            redirect('users/' . $this->auth->getUsername());
        } else {
            $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
        }
    }

    function comment($record = null) {
        $this->load->library('form_validation');
        $this->load->model('Comment');
        $this->load->model('Record');
        if ($this->input->post('record')) {
            $record = $this->input->post('record');
        }
        $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
        if($this->input->post('action') == 'delete') {
            $this->Comment->delete($this->auth->getUserID(), $record);
            redirect('users/' . $this->auth->getUsername());
        } else if ($this->Comment->validateData() !== false) {
            $this->Comment->set($this->auth->getUserID(), $record, $this->input->post('comment'));
            redirect('users/' . $this->auth->getUsername());
        }
    }

    function add() {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->form_validation->set_rules('artist', 'Artist', 'required|max_length[100]|xss_clean');
        $this->form_validation->set_rules('title', 'Titel', 'required|max_length[150]|xss_clean');
        $this->form_validation->set_rules('year', 'År', 'is_natural_no_zero|exact_length[4]');
        $this->form_validation->set_rules('format', 'Format', 'max_length[30]|xss_clean');
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