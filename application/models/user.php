<?php

class User extends MY_Model {

    public function __construct($column = NULL, $key = NULL) {
        parent::__construct();
        $this->table = 'users';
        $this->fields = array(
            array('username', 'Användarnamn', 'required|xss_clean|min_length[3]|max_length[24]|alpha_dash_dot|unique[users.username]'),
            array('password', 'Lösenord', 'required|min_length[6]'),
            array('email', 'E-post', 'valid_email|max_length[64]'),
            array('public_email', 'E-post', 'in_list[0,1]'),
            array('name', 'Namn', 'max_length[50]|xss_clean'),
            array('birth', 'Födelsedag', 'valid_date[Y-m-d]'),
            array('about', 'Om mig', 'max_length[3000]|strip_tags|xss_clean'),
            array('sex', 'Kön', 'in_list[f,m,x]'),
            array('per_page', 'Skivor per sida', 'is_natural_no_zero|numeric_max[100]')
        );
    }

    function isSupporter($uid) {
        $ONE_YEAR_AGO = time() - 365 * 24 * 60 * 60;
        $DONATION_LIMIT = 100;
        $donations = $this->db->where('user_id', $uid)
                ->where('donated_at >=', date('Y-m-d', $ONE_YEAR_AGO))
                ->where('amount >=', $DONATION_LIMIT)
                ->from('donations')
                ->count_all_results();
        return $donations > 0;
    }

    function getNewUsers($num = 5) {
        $this->db->order_by('registered', 'desc')->limit($num);
        return $this->db->get('users')->result();
    }

    function getTopList($num = 10) {
        $this->db->select('u.username, COUNT(c.id) AS recs')->from('users u, records_users c')->where('u.id = c.user_id')->group_by('c.user_id')->order_by('recs', 'desc')->limit($num);
        return $this->db->get()->result();
    }

    public function getTopArtists($limit = 10, $uid = NULL) {
        $this->db->select('a.name, COUNT(a.id) AS records')
                ->from('artists a, records r, records_users ru')
                ->where("a.id = r.artist_id AND r.id = ru.record_id AND a.name NOT IN ('Various', 'V/A')")
                ->group_by('a.id')
                ->order_by('records', 'desc')
                ->order_by('a.name', 'asc')
                ->limit($limit);
        if ($uid)
            $this->db->where('ru.user_id', $uid);
        return $this->db->get()->result();
    }

    public function getLatestRecords($uid, $limit = 10) {
        $records = $this->db->select('r.title, a.name')->from('records_users ru, records r, artists a')->where('ru.user_id', $uid)->where('ru.record_id = r.id')->where('r.artist_id = a.id')->order_by('r.id DESC')->limit($limit)->get();
        return $records->result();
    }

    public function getNumberOfRecords($uid = false) {
        if ($uid) {
            $row = $this->db->select('COUNT(*) AS num')
                            ->from('records_users ru')
                            ->join('users u', 'ru.user_id = u.id')
                            ->where('u.id', $uid)
                            ->group_by('u.id')
                            ->get()->row();
        } else {
            $row = $this->db->select('COUNT(*) AS num')
                            ->from('records_users ru')
                            ->get()->row();
        }
        if ($row == NULL)
            return 0;
        else
            return $row->num;
    }

    public function getRecords($uid, $limit = 0, $offset = 0, $order = 'artist', $dir = 'ASC') {
        $this->db->select('r.title, r.year, r.format, a.name, a.id AS artist_id, ru.id, ru.comment,
            (SELECT COUNT(r2.id) FROM records_users ru2, records r2
            WHERE ru2.user_id = ru.user_id AND ru2.record_id = r2.id AND r2.artist_id = a.id
            GROUP BY r2.artist_id) AS num_records')
                ->from('records_users ru')
                ->join('records r', 'r.id = ru.record_id', 'left')
                ->join('artists a', 'r.artist_id = a.id', 'left')
                ->where('ru.user_id', $uid);

        if (strtolower($dir) != "asc") {
            $dir = "DESC";
        }
        switch ($order) {
            case 'format':
                $this->db->order_by("r.format $dir, TRIM(LEADING 'The ' FROM a.name) $dir, r.title $dir, r.year $dir");
                break;
            case 'year':
                $this->db->order_by("r.year $dir, TRIM(LEADING 'The ' FROM a.name) $dir, r.title $dir");
                break;
            default:
                $this->db->order_by("TRIM(LEADING 'The ' FROM a.name) $dir, r.year ASC, r.title $dir");
        }
        if ($limit > 0)
            $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function search($query, $limit = 20) {
        $result = $this->db->from('users')
                        ->like('username', $query)
                        ->or_like('name', $query)
                        ->order_by('username ASC')
                        ->limit($limit)
                        ->get()->result();
        return $result;
    }

    public function encrypt_password($username, $password) {
        $presalt = substr(md5($username), 0, 12);
        return hash('sha256', $presalt . $password . $this->config->item('global_salt'));
    }

    function getMemberStatistics() {
        $data['this_week'] = $this->db->select('COUNT(id) AS num')->where('YEARWEEK(registered, 3) =', 'YEARWEEK(NOW(), 3)', false)->get('users')->row()->num;
        $data['last_week'] = $this->db->select('COUNT(id) AS num')->where('YEARWEEK(registered, 3) =', 'YEARWEEK(NOW(), 3)-1', false)->get('users')->row()->num;
        $data['total'] = $this->db->select('COUNT(id) AS num')->get('users')->row()->num;
        return $data;
    }

    function getSexes() {
        $male = $this->db->where('sex', 'm')->get('users')->num_rows();
        $female = $this->db->where('sex', 'f')->get('users')->num_rows();
        $unknown = $this->db->where('sex', 'x')->get('users')->num_rows();
        $total = $male + $female + $unknown;
        if ($total == 0)
            $total = 1;
        return array(
            'male' => $male,
            'male_percent' => round(($male / $total) * 100),
            'female' => $female,
            'female_percent' => round(($female / $total) * 100),
            'unknown' => $unknown,
            'unknown_percent' => round(($unknown / $total) * 100)
        );
    }

    function getPopularAlbums($num = 5) {
        $this->db->select('r.title, a.name, COUNT(*) AS records')
                ->from('records_users ru')
                ->join('records r', 'r.id = ru.record_id')
                ->join('artists a', 'a.id = r.artist_id')
                ->group_by('r.title, a.name')
                ->order_by('records', 'desc')
                ->order_by('a.name', 'asc')
                ->order_by('r.title', 'asc')
                ->limit($num);
        return $this->db->get()->result();
    }

}