<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class MY_Validation extends CI_Validation {

	function My_Validation()
	{
		parent::CI_Validation();
	}
	
	function set_default_values($data, $value = null)
	{
		if (is_array($data) == TRUE) {
			foreach($data as $field => $value) {
				$this->$field   = $value;
				//$_POST[$field]  = $value;
			}
		} else {
			$this->$data    = $value;
			//$_POST[$data]   = $value;
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Alpha-numeric with underscores, dashes and dots
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function alpha_dash_dot($str)
	{
		return ( ! preg_match("/^([-a-z0-9\._-])+$/i", $str)) ? FALSE : TRUE;
	}
	
}