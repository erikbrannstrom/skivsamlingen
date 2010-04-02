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
	
	protected function apply($key, $column)
	{
		if($key !== NULL) {
			if($column !== NULL) {
				$this->_create_object($key, $column);
			} else {
				$this->_create_object($key, $this->primary_key);
			}
		}
	}
	
    private function _create_object($key, $column)
    {
    	if(is_array($column)) {
    		for($i = 0; $i < count($column); $i++)
    			$this->db->where($column[$i], $key[$i]);
    	} else {
    		$this->db->where($column, $key);
    	}
    	$obj = $this->db->get($this->table)->row();
    	foreach(get_object_vars($obj) as $key => $value) {
    		$this->$key = $value;
    	}
    }
	
	/**
	 * Static factory method for generating new model
	 * objects depending on the key.
	 *
	 * REQUIRES PHP 5.3 (late static bidning)
	 *
    public static function get($key)
    {
		$class_name = get_called_class();
    	$result = new $class_name();
    	$obj = get_instance()->db->where(self::primary_key, $get)->get($result->table)->row();
    	foreach(get_object_vars($obj) as $key => $value) {
    		$result->$key = $value;
    	}
    	return $result;
    }
    */

}
