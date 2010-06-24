<?php

class Notice {

    var $CI;

    function Notice() {
        $this->__construct();
    }

    function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->library('session');
    }

    function set($content, $name = 'flash', $type = 'notice') {
        $this->CI->session->set_flashdata($name, array('type' => $type, 'content' => $content));
    }

    function info($content, $name = 'flash') {
        $this->set($content, $name, 'notice');
    }

    function success($content, $name = 'flash') {
        $this->set($content, $name, 'success');
    }

    function error($content, $name = 'flash') {
        $this->set($content, $name, 'error');
    }

    function keep($name = 'flash') {
        $this->CI->session->keep_flashdata($name);
    }

    function get($name = 'flash') {
        $flash = $this->CI->session->flashdata($name);
        if ($flash !== false)
            return '<div class="' . $flash['type'] . '">' . $flash['content'] . '</div>';
        else
            return '';
    }

    function getAllKeys() {
        $userdata = array();
        foreach ($this->CI->session->all_userdata() as $key => $value) {
            if (strpos($key, 'flash') !== false) {
                $userdata[] = substr($key, 10);
            }
        }
        return $userdata;
    }

}