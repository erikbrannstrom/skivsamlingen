<?php

class Home_Controller extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();
	}
	
	function index()
	{	
		$this->load->library('mp_cache');
		$stats = $this->mp_cache->get('statistics');
		$this->data['page_title'] = 'Skivsamlingen';
		$this->data['news'] = $this->db->limit(1)->order_by('posted DESC')->get('news');
		if($stats === FALSE) {
            $this->load->model('User');
			$stats['latest_users'] = $this->User->getNewUsers();
			$stats['toplist'] = $this->User->getTopList();
			$stats['sex'] = $this->User->getSexes();
			$stats['members'] = $this->User->getMemberStatistics();
			$stats['total_recs'] = $this->User->getNumberOfRecords();
			$stats['popular_artists'] = $this->User->getTopArtists(10);
			$stats['popular_albums'] = $this->User->getPopularAlbums(10);
			$this->mp_cache->write($stats, 'statistics', 3600);
		}
		foreach($stats as $key => $value) {
			$this->data[$key] = $value;
		}
	}
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */