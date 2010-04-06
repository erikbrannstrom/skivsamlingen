<?php

class Artist extends MY_Model
{
	
	public function __construct($column = NULL, $key = NULL)
	{
		parent::__construct();
		$this->table = 'artists';
		$this->apply($column, $key);
	}
	
	public static function getArtistID($name)
	{
		$res = get_instance()->db->select('id')
						->where('name', $name)
						->get('artists')
						->row();
		if($res != NULL) {
			return $res->id;
		} else {
			get_instance()->db->insert('artists', array('name' => $name));
			return get_instance()->db->insert_id();
		}
	}	

}