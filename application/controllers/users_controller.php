<?php

class Users_Controller extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();
		$this->load->model('User');
	}

	function profile($username = NULL)
	{
		$user = $this->User->fetchOne(array('username' => $username));
		if(!$user) {
			redirect('users/search/'.$username);
		}
		$this->data['page_title'] = 'Local: Skivsamlingen - '.$username;
        $offset = $this->uri->segment(3, 0);
		$order = $this->uri->segment(4,'artist');
		$direction = $this->uri->segment(5,'asc');
		 
		$this->load->library('pagination');
		$config['base_url'] = base_url() . 'users/'. $username;
		$config['total_rows'] = $this->User->getNumberOfRecords($user->id);
		$config['per_page'] = ($this->auth->isUser() ? $this->auth->getUser()->per_page : 20);
		$config['uri_segment'] = 3;
        $config['post_url'] = "$order/$direction";
        //$config['title'] = 'Visar ' . ($offset+1).' - '.( (($offset+1+$config['per_page']) > $config['total_rows']) ? $config['total_rows'] : ($offset+1+$config['per_page']) ).' av '.$config['total_rows'].' skivor';
		$this->pagination->initialize($config);

        // Create sort links
		$sorts = array('Artist' => 'artist',
						'Format' => 'format',
						'År' => 'year'
		);
		foreach($sorts as $key => $sort) {
			if($order == $sort) {
                $new_dir = ($direction == 'asc') ? 'desc' : 'asc';
                $arr = ($direction == 'desc') ? '&darr;' : '&uarr;';
				$sorts[$key] = '<li class="active">'.anchor('users/'.$user->username.'/'.$offset.'/'.$sort.'/'.$new_dir, $key . " $arr").'</li>';
			} else {
				$sorts[$key] = '<li>'.anchor('users/'.$user->username.'/'.$offset.'/'.$sort.'/'.$direction, $key).'</li>';
			}
		}

		$directions = array('Stigande' => 'asc',
						'Fallande' => 'desc'/*,
						'Slumpad' => 'random'*/
		);
		foreach($directions as $key => $dir) {
			if($direction == $dir) {
				$directions[$key] = '<li class="active">'.$key.'</li>';
			} else {
				$directions[$key] = '<li>'.anchor('users/'.$user->username.'/'.$offset.'/'.$order.'/'.$dir, $key).'</li>';
			}
		}

		$this->data['sort_links'] = $sorts;
		$this->data['order_links'] = $directions;

        switch ($user->sex) {
            case 'f':
                $user->sex = 'Kvinna'; break;
            case 'm':
                $user->sex = 'Man'; break;
            default:
                $user->sex = null; break;
        }

		$this->data['user'] = $user;
		$this->data['num_records'] = $config['total_rows'];
		$this->data['pagination'] = $this->pagination->create_links(); 
		$this->data['records'] = $this->User->getRecords($user->id, $config['per_page'], $offset, $order, $direction);
        $this->data['top_artists'] = $this->User->getTopArtists(5, $user->id);
        $this->data['latest_records'] = $this->User->getLatestRecords($user->id, 5);
	}

	function export($username = NULL)
	{
		$user = $this->User->fetchOne(array('username' => $username));
		if(!$user) {
			redirect('users/search/'.$username);
		}
		$this->load->helper('xml');
		$this->load->helper('download');
		$name = 'skivsamling-'.date('Ymd').'.xml';
		$num = $this->User->getNumberOfRecords($user->id);
		$records = $this->User->getRecords($user->id);
		$data = '<?xml version="1.0" encoding="utf-8"?>'.'
<collection user="'.$this->session->userdata('username').'">';
		foreach($records as $record):
		  $data .= '    <record>
        <artist>'.xml_convert($record->name).'</artist>
        <title>'.xml_convert($record->title).'</title>
        <year>'.xml_convert($record->year).'</year>
        <format>'.xml_convert($record->format).'</format>
    </record>
		  ';
		endforeach;
		$data .= '</collection>';
		force_download($name, $data);
	}


    function printview($username = NULL)
    {
		$user = $this->User->fetchOne(array('username' => $username));
		if(!$user) {
			redirect('users/search/'.$username);
		}
        $this->_pass(); // Don't autoload layout

        $this->data['user'] = $user;
        $this->data['num_records'] = $this->User->getNumberOfRecords($user->id);
        $this->data['records'] = $this->User->getRecords($user->id);
        $this->load->view('users/print', $this->data);
    }


    function search($query = NULL)
	{
		if($query == NULL) {
			$query = $this->input->post('query');
		}
		if($query === FALSE) {
			redirect('welcome');
		}
		$users = $this->User->search($query);
        foreach($users as $user) {
            $user->num_records = $this->User->getNumberOfRecords($user->id);
        }
		$this->data['query'] = $query;
		$this->data['users'] = $users;
	}
	
}

/* End of file user.php */
/* Location: ./system/application/controllers/user.php */