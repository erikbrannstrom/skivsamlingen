<?php

class Record extends MY_Model
{
	
	public function __construct()
	{
		parent::__construct();
		$this->table = 'records';
	}

    public function get($id, $user_id) {
        $this->db->select('r.title, r.year, r.format, a.name, ru.id, ru.comment')
				 ->from('records_users ru')
				 ->join('records r', 'r.id = ru.record_id', 'left')
				 ->join('artists a', 'r.artist_id = a.id', 'left')
				 ->where('ru.user_id', $user_id)
                 ->where('ru.id', $id);
        $result = $this->db->get()->row();
        return $result;
    }

	public function getLatestRecords($limit = 10)
	{
		$records = $this->db->select('u.username, r.title, a.name')->from('records_users ru, records r, artists a, users u')->where('ru.record_id = r.id')->where('r.artist_id = a.id')->where('ru.user_id = u.id')->orderBy('r.id DESC')->limit($limit)->get();
		return $records->result();
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