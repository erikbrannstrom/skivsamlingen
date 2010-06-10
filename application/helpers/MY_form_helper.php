<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Generates a hidden field containing a nonce.
 *
 * @access	public
 * @return	string
 */
if ( ! function_exists('form_nonce'))
{
	function form_nonce()
	{
        $CI =& get_instance();
        $CI->load->library('form_validation');
		$field = '<input type="hidden" name="nonce" value="'
            . $CI->form_validation->set_value('nonce', $CI->form_validation->create_nonce())
            . '" />';
        return $field;
	}
}