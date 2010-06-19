<?php

class News extends MY_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->table = 'news';
	}

	public function get($limit = 0, $offset = 0) {
        $this->db->order_by('posted DESC');
        if($limit > 0)
            $this->db->limit($limit, $offset);
		return $this->read();
	}

	public function allTitles() {
        $this->db->order_by('posted DESC');
        $this->db->select('title');
		return $this->read();
	}

    public function countAll()
    {
        return $this->db->count_all($this->table);
    }

}