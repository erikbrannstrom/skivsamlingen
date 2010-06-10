<?php

class Record extends MY_Model
{
	
	public function __construct()
	{
		parent::__construct();
		$this->table = 'records';
	}
	
	public function getId($artist_id, $title, $year = null, $format = null)
	{
		$res = $this->fetchOne(array(
            'artist_id' => $artist_id,
            'title COLLATE utf8_bin = ' => $title,
            'year' => $year,
            'format' => $format
        ));
		if($res) {
			return $res->id;
		} else {
            return $this->create(array(
                'artist_id' => $artist_id,
                'title' => $title,
                'year' => $year,
                'format' => $format
            ), false);
		}
	}	

}