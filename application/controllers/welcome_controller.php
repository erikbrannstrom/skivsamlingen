<?php

class Welcome_Controller extends MY_Controller {

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
			$stats['latest_users'] = $this->getNewUsers()->result();
			$stats['toplist'] = $this->getTopList()->result();
			$stats['sex'] = $this->getSexPercentage();
			$stats['members'] = $this->getMemberStats();
			$stats['total_recs'] = $this->getNumRecords();
			$stats['popular_artists'] = $this->getPopularArtist(10)->result();
			$stats['popular_albums'] = $this->getPopularAlbums(10)->result();
			$this->mp_cache->write($stats, 'statistics', 3600);
		}
		foreach($stats as $key => $value) {
			$this->data[$key] = $value;
		}
	}
	
	function getMemberStats()
	{
		$data['this_week'] = $this->db->select('id, registered')->having('YEARWEEK(registered, 3) =','YEARWEEK(NOW(), 3)', false)->get('users')->num_rows();
		$data['last_week'] = $this->db->select('id, registered')->having('YEARWEEK(registered, 3) =','YEARWEEK(NOW(), 3)-1', false)->get('users')->num_rows();
		$data['total'] = $this->db->select('id')->get('users')->num_rows();
		return $data;
	}
	
	function getNewUsers($num = 5)
	{
		$this->db->order_by('registered','desc')->limit($num);
		return $this->db->get('users');
	}
	
	function getTopList($num = 10)
	{
		$this->db->select('u.username, COUNT(c.id) AS recs')->from('users u, records_users c')->where('u.id = c.user_id')->group_by('c.user_id')->order_by('recs','desc')->limit($num);
		return $this->db->get();
	}
	
	function getSexPercentage()
	{
		
		$male = $this->db->where('sex','m')->get('users')->num_rows();
		$female = $this->db->where('sex','f')->get('users')->num_rows();
		$unknown = $this->db->where('sex','x')->get('users')->num_rows();
		$total = $male + $female + $unknown;
		return array(
				'male' => $male,
				'male_percent' => round(($male / $total)*100),
				'female' => $female,
				'female_percent' => round(($female / $total)*100),
				'unknown' => $unknown,
				'unknown_percent' => round(($unknown / $total)*100)
			);
	}
	
	function getNumRecords($uid = NULL)
	{
		if(is_numeric($uid)) $this->db->where('uid',$uid);
		$this->db->select('COUNT(id) AS recs')->from('records_users');
		$query = $this->db->get()->row();
		return $query->recs;
	}
	
	function getPopularArtist($num = 5, $uid = false)
	{
		if($uid) $this->db->where('ru.user_id',$uid);
		$this->db->select('a.name, COUNT(a.id) AS records')
				 ->from('artists a, records r, records_users ru')
				 ->where("a.id = r.artist_id AND r.id = ru.record_id AND a.name NOT IN ('Various', 'V/A')")
				 ->group_by('a.id')
				 ->order_by('records','desc')->order_by('a.name','asc')->limit($num);
		return $this->db->get();
	}
	
	function getPopularAlbums($num = 5)
	{
		$this->db->select('r.title, a.name, COUNT(*) AS records')
				 ->from('records_users ru')
				 ->join('records r','r.id = ru.record_id')
				 ->join('artists a','a.id = r.artist_id')
				 ->group_by('r.title, a.name')
				 ->order_by('records','desc')
				 ->order_by('a.name', 'asc')
				 ->order_by('r.title', 'asc')
				 ->limit($num);
		$res = $this->db->get();
		return $res;
	}
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */