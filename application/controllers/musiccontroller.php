<?php

class MusicController extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();	
	}
	
	function index()
	{
		if($this->auth->isUser()) {
			echo "is user!";
		} else {
			echo "is not user!";
		}
		exit;
	}

	function artist($name = NULL)
	{
		if($name == NULL) {
			redirect('welcome');
		}
		
		$artist = Doctrine_Query::create()->select('a.id, a.name, r.title, r.year, r.format, COUNT(r.id) as num_records')->from('Artist a, a.Records r')->where('a.url_name = ?', $name)->execute();
		
		$xml = simplexml_load_file('http://ws.audioscrobbler.com/2.0/artist/'. $artist[0]->name .'/info.xml');
		echo '<pre>';
		/*foreach($xml->children() as $child) {
			if($child->getName() == 'image') {
				$info->image = $child;
			}
			//echo $child->getName() . ': ' . $child . '<br />';
		}*/
		$sum = $xml->xpath("/artist/bio");
		$info->summary = $sum[0]->summary;
		print_r($info);
		exit;
		
		$this->data['page_title'] = 'Local: Skivsamlingen - '.$artist->name;
		
		$this->data['artist'] = $artist;
	}
	
	function fix_urls()
	{
		$artists = Doctrine_Query::create()->from('Artist a')->limit(0)->offset(10000)->execute();
		foreach($artists as $artist) {
			//echo "$artist->name = " . $this->fixer($artist->name) . "<br />";
			$artist->url_name = $this->fixer($artist->name);
			if($artist->url_name == '')
				$artist->url_name = $artist->id;
			$artist->save();
		}
		echo "done!";
		exit;
	}
	
	private function fixer($str)
	{
        $separator = '-';
        $str = strtolower(htmlentities($str, ENT_COMPAT, 'UTF-8'));
        $str = preg_replace('/&(.)(acute|cedil|circ|grave|ring|tilde|uml);/', "$1", $str);
        $str = preg_replace('/([^a-z0-9]+)/', $separator, html_entity_decode($str, ENT_COMPAT, 'UTF-8'));
        $str = trim($str, $separator);
        return $str;
    }
	
	private function fixer2($str)
	{
		$search		= '_';
		$replace	= '-';

		$trans = array(
						$search								=> $replace,
						"\s+"								=> $replace,
						"[^a-z0-9".$replace."]"				=> '',
						$replace."+"						=> $replace,
						$replace."$"						=> '',
						"^".$replace						=> ''
					   );

		$str = strip_tags(strtolower($str));
	
		foreach ($trans as $key => $val)
		{
			$str = preg_replace("#".$key."#", $val, $str);
		}
	
		return trim(stripslashes($str));
	}
	
}

/* End of file user.php */
/* Location: ./system/application/controllers/user.php */