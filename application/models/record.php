<?php

class Record extends MY_Model
{
	
	public function __construct($column = NULL, $key = NULL)
	{
		parent::__construct();
		$this->table = 'records';
		$this->apply($column, $key);
	}
	
	public function getID()
	{
		
		$this->db->select('id')
				 ->where('artist_id', $this->artist_id)
		  		 ->where('title COLLATE utf8_bin = ', $this->title);
		if(property_exists($this, 'year'))
			$this->db->where('year', $this->year);
		else
			$this->db->where('year', NULL);
		if(property_exists($this, 'format'))
			$this->db->where('format', $this->format);
		else
			$this->db->where('format', NULL);
		$res = $this->db->get($this->table)->row();
		if($res != NULL) {
			return $res->id;
		} else {
			$this->save();
			return $this->db->insert_id();
		}
	}	

}