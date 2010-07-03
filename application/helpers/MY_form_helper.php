<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Form Declaration
 *
 * Creates the opening portion of the form. Automatically adds a hidden
 * field containing a nonce if it's been enabled.
 *
 * @access	public
 * @param	string	the URI segments of the form destination
 * @param	array	a key/value pair of attributes
 * @param	array	a key/value pair hidden data
 * @return	string
 */
function form_open($action = '', $attributes = '', $hidden = array())
{
    $CI = & get_instance();

    if ($attributes == '') {
        $attributes = 'method="post"';
    }

    $action = ( strpos($action, '://') === FALSE) ? $CI->config->site_url($action) : $action;

    $form = '<form action="' . $action . '"';
    $form .= _attributes_to_string($attributes, TRUE);
    $form .= '>';

    if($CI->form_validation->has_nonce()) {
        $value = set_value('nonce');
        if($value == '')
            $value = $CI->form_validation->create_nonce();
        $hidden['nonce'] = set_value('nonce', $value);
    }

    if (is_array($hidden) && count($hidden) > 0) {
        $form .= form_hidden($hidden);
    }

    return $form;
}