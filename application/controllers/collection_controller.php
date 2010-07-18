<?php

class Collection_Controller extends MY_Controller {

    function __construct() {
        parent::MY_Controller();
        if ($this->auth->isGuest()) {
            $this->notice->error('Du måste vara inloggad för att kunna göra detta.');
            redirect();
        }
        $this->load->model('Collection');
        $this->load->model('Record');
        $this->history->exclude();
    }

    function delete($record = NULL) {
        $this->load->library('form_validation');
        if ($this->input->post('record') !== false) {
            $record = $this->input->post('record');
            $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
            $res = $this->Collection->deleteItem($record, $this->auth->getUserID());
            if ($res == 1) {
                $this->notice->success($this->data['record']->name . ' - ' . $this->data['record']->title . ' har tagits bort.');
            }
            redirect($this->history->pop());
        } else {
            $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
        }
    }

    function comment($record = null) {
        $this->load->library('form_validation');
        $this->load->model('Comment');
        if ($this->input->post('record')) {
            $record = $this->input->post('record');
        }
        $this->data['record'] = $this->Record->get($record, $this->auth->getUserID());
        if($this->input->post('action') == 'delete') {
            $this->Comment->delete($this->auth->getUserID(), $record);
            redirect($this->history->pop());
        } else if ($this->Comment->validateData() !== false) {
            $this->Comment->set($this->auth->getUserID(), $record, $this->input->post('comment'));
            redirect($this->history->pop());
        }
    }

    function record($id = 0) {
        if($id == 0) {
            $id = $this->input->post('id');
        }

        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->form_validation->set_rules('id', 'ID', 'required');
        $this->form_validation->set_rules('artist', 'Artist', 'required|max_length[64]|xss_clean');
        $this->form_validation->set_rules('title', 'Titel', 'required|max_length[150]|xss_clean');
        $this->form_validation->set_rules('year', 'År', 'is_natural_no_zero|exact_length[4]');
        $this->form_validation->set_rules('format', 'Format', 'max_length[30]|xss_clean');
        $this->form_validation->nonce();

        if($id > 0) {
            $this->data['record'] = $this->Record->get($id, $this->auth->getUserID());
        } else {
            $rec->id = 0;
            $rec->name = '';
            $rec->title = '';
            $rec->year = '';
            $rec->format = '';
            $this->data['record'] = $rec;
        }

        $this->data['id'] = $id;

        if ($this->form_validation->run() !== FALSE) { // If validation has completed
            $this->load->model('Artist');
            $this->load->model('Comment');
            if($id > 0) {
                $comment = $this->Comment->fetchOne($id)->comment;
                $this->Collection->deleteItem($id, $this->auth->getUserID());
            } else if($this->input->post('comment')) {
                $comment = $this->input->post('comment');
            }
            $artist_id = $this->Artist->getArtistId($this->input->post('artist'));
            $record_id = $this->Record->getId($artist_id, $this->input->post('title'),
                            $this->input->post('year'), $this->input->post('format'));
            $coll_id = $this->Collection->addItem($this->auth->getUserId(), $record_id);
            if(isset($comment)) {
                $this->Comment->set($this->auth->getUserID(), $coll_id, $comment);
            }
            $this->notice->success($this->input->post('artist') . ' - ' . $this->input->post('title')
                    . ' har ' . (($id == 0) ? 'lagts till' : 'uppdaterats') . '.');
            if($id == 0) {
                redirect('collection/record');
            } else {
                redirect($this->history->pop());
            }
        }
    }

}

/* End of file user.php */
/* Location: ./system/application/controllers/user.php */