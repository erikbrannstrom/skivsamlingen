<?php

class WelcomeController extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();
	}
	
	function index()
	{
		$this->data['page_title'] = 'Skivsamlingen';
		$this->data['news'] = $q = Doctrine_Query::create()->from('News')->orderBy('posted desc')->limit(1)->execute();
		
		$num = Doctrine_Query::create()->select('COUNT(*) AS num')->from('User')->groupBy('id')->execute();
		$this->data['stats_num_members'] = $num['num'];
	}
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */