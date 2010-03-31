<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Model Class
 *
 * Model containing some basic functionality.
 *
 * @category	Libraries
 * @author		Erik BrŠnnstršm
 */

class MY_Model extends Model
{
	protected $primary_key = 'id';
	protected $table = '';
	
	public function MY_Model() {
		$this->__construct();
	}

	public function __construct()
	{
		parent::Model();
	}
	
	/*protected function __set($name, $value)
	{
		echo "Set $name to $value<br/>";
		if(method_exists($this, '_set_'.$name)) {
			$method = '_set_'.$name;
			$this->$method($value);
		} else {
			$this->$name = $value;
		}
	}*/

}
