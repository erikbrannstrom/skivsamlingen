<?php

class Collection extends MY_Model
{
	
	public function __construct($column = NULL, $key = NULL)
	{
		parent::__construct();
		$this->table = 'records_users';
		//$this->apply($column, $key);
	}
		
	public static function deleteItem($key, $user_id)
	{
		get_instance()->db->where('id', $key)->where('user_id', $user_id)->delete('records_users');
		return get_instance()->db->affected_rows();
	}
	
	public static function addItem($user_id, $record_id)
	{
		get_instance()->db->insert('records_users', array('user_id' => $user_id, 'record_id' => $record_id));
	}

}