<?php

class Home_Controller extends MY_Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        $this->load->library('mp_cache');
        $stats = $this->mp_cache->get('statistics');
        $this->data['page_title'] = 'Skivsamlingen';
        $this->data['news'] = $this->db->limit(1)->order_by('posted DESC')->get('news');
        if ($stats === FALSE) {
            $this->load->model('User');
            $stats['latest_users'] = $this->User->getNewUsers();
            $stats['toplist'] = $this->User->getTopList();
            $stats['members'] = $this->User->getMemberStatistics();
            $stats['total_recs'] = $this->User->getNumberOfRecords();
            $stats['popular_artists'] = $this->User->getTopArtists(10);
            $stats['popular_albums'] = $this->User->getPopularAlbums(10);
            $this->mp_cache->write($stats, 'statistics', 3600);
        }
        $this->load->model('Record');
        $stats['latest_records'] = $this->Record->getLatestRecords(5);
        foreach ($stats as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    function unregistered() {
        $this->history->exclude();
        if ($this->session->flashdata('action') == 'unregistered') {
            $this->data['email'] = $this->session->flashdata('email');
            $this->data['name'] = $this->session->flashdata('name');
            $this->data['username'] = $this->session->flashdata('username');
            $this->data['news'] = $this->db->limit(1)->order_by('posted DESC')->get('news');
            $this->load->library('form_validation');
            $this->form_validation->set_rules('name', 'Namn', 'required|max_length[60]');
            $this->form_validation->set_rules('email', 'E-post', 'required|valid_email');
            $this->form_validation->set_rules('message', 'Meddelande', 'required|max_length[4000]');
            if ($this->form_validation->run() !== false) {
                $this->load->library('email');

                $this->email->from($this->input->post('email'), $this->input->post('name'));
                $this->email->to('erik.brannstrom@skivsamlingen.se');

                $this->email->subject('Avslutat konto: ' . $this->input->post('name'));
                $this->email->message($this->input->post('message'));

                $this->email->send();
                redirect();
            } else {
                $this->session->keep_flashdata('action');
            }
        } else {
            redirect();
        }
    }

    function about()
    {

    }

}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */