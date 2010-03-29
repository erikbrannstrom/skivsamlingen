<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ------------------------------------------------------------------------

/**
 * Static URL
 *
 * Returns the url for a static item in the chosen path
 *
 * @access	public
 * @return	string
 */	
if ( ! function_exists('static_url'))
{
	function static_url($path)
	{
		$CI =& get_instance();
		return base_url().'static/'.$path;
	}
}


/* End of file url_helper.php */
/* Location: ./system/helpers/url_helper.php */