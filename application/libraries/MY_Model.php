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
	private $_exists = false;
	
	public function MY_Model() {
		$this->__construct();
	}

	public function __construct()
	{
		parent::Model();
	}
	
	public function save() {
		if($this->exists()) {
			if(is_array($this->primary_key)) {
				foreach($this->primary_key as $key) {
					$this->db->where($key, $this->$key);
				}
			} else {
				$this->db->where($this->primary_key, $this->{$this->primary_key});
			}
			$this->db->update($this->table, $this);
		} else {
			$this->db->insert($this->table, $this);
			$this->_exists = true;
		}
	}
	
	protected function apply($column, $key)
	{
		if($column != NULL) {
			if($key == NULL) {
				$key = $column;
				$column = $this->primary_key;
			}
	    	if(is_array($column)) {
	    		for($i = 0; $i < count($column); $i++)
	    			$this->db->where($column[$i], $key[$i]);
	    	} else {
	    		$this->db->where($column, $key);
	    	}
	    	$this->_create_object($this->db->get($this->table)->row());
		}
	}
	
    protected function _create_object($result)
    {
    	if($result != NULL) {
    		foreach(get_object_vars($result) as $key => $value) {
    			$this->$key = $value;
    		}
    		$this->_exists = true;
    	}
    }
    
    public function exists()
    {
    	return $this->_exists;
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
