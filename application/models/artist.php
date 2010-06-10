<?php

class Artist extends MY_Model
{
	
	public function __construct()
	{
		parent::__construct();
		$this->table = 'artists';
	}
	
	public function getArtistId($name)
	{
		$res = $this->fetchOne(array(
                'name' => $name
        ));
		if($res) {
			return $res->id;
		} else {
			return $this->create(array('name' => $name), false);
		}
	}	

}