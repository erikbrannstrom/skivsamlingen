<?php

class News_Controller extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('News');
    }

    function index() {
        $offset = $this->uri->segment(2, 0);

        $this->load->library('pagination');
        $config['base_url'] = base_url() . 'news';
        $config['total_rows'] = $this->News->countAll();
        $config['per_page'] = 5;
        $config['uri_segment'] = 2;
        $this->pagination->initialize($config);

        $this->data['page_title'] = 'Skivsamlingen - Nyheter';
        $this->data['pagination'] = $this->pagination->create_links();
        $this->data['news'] = $this->News->get($config['per_page'], $offset);
        $this->data['titles'] = $this->News->allTitles();
    }

    function entry() {
        $this->history->exclude();
        redirect('news');
    }

    function rss() {
        $this->history->exclude();
        $this->load->helper('xml');
        $data['encoding'] = 'utf-8';
        $data['feed_name'] = 'Skivsamlingen';
        $data['feed_url'] = 'http://skivsamlingen.se/';
        $data['page_description'] = 'Skivsamlingen - musik är en livsstil.';
        $data['page_language'] = 'sv-se';
        $data['creator_email'] = 'erik.brannstrom@skivsamlingen.se';
        $data['posts'] = $this->News->get(5);
        header("Content-Type: application/rss+xml");
    }

}

/* End of file news_controller.php */
/* Location: ./system/application/controllers/news_controller.php */