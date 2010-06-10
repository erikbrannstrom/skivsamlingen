<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Generates a hidden field containing a nonce.
 *
 * @access	public
 * @return	string
 */
if ( ! function_exists('notifications'))
{
	function notifications($user)
	{
        $CI =& get_instance();
        //$CI->load->library('Notification');
		return $CI->notification->getMessages($user);
	}
}