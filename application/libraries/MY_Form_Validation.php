<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class MY_Form_Validation extends CI_Form_Validation {

    private $use_nonce = false;

    function __construct()
    {
        parent::__construct();
    }

    function set_error($field, $message)
    {
        $this->_field_data[$field]['error'] = $message;

        if (!isset($this->_error_array[$field])) {
            $this->_error_array[$field] = $message;
        }
    }

    /**
     * Create a new unique nonce, save it to the current session and return it.
     *
     * @return string
     */
    public function create_nonce()
    {
        $nonce = md5(rand() . $this->CI->input->ip_address() . microtime());
        $this->CI->session->set_userdata('nonce', $nonce);
        return $nonce;
    }

    public function has_nonce()
    {
        return $this->use_nonce;
    }

    public function run($group = '')
    {
        $result = parent::run($group);
        if($result === true) {
            $this->save_nonce();
        }
        return $result;
    }

    /**
     * Mark the nonce sent from the form as already used.
     */
    private function save_nonce()
    {
        $this->CI->session->set_userdata('old_nonce', $this->set_value('nonce'));
    }

    /**
     * Set form validation rules for the nonce.
     */
    function nonce()
    {
        $this->use_nonce = true;
        $this->set_rules('nonce', 'Nonce', 'required|valid_nonce');
    }

    // --------------------------------------------------------------------

	function validate($str, $rules)
	{
		if(!is_array($rules))
			$rules = explode('|', $rules);

		if ( ! in_array('required', $rules) && is_null($str))
			return TRUE;

		foreach($rules as $rule) {
			// Strip the parameter (if exists) from the rule
			// Rules can contain a parameter: max_length[5]
			$param = FALSE;
			if (preg_match("/(.*?)\[(.*?)\]/", $rule, $match)) {
				$rule	= $match[1];
				$param	= $match[2];
			}

			if ( ! method_exists($this, $rule)) {
				// If our own wrapper function doesn't exist we see if a native PHP function does.
				// Users can use any native PHP function call that has one param.
				if (function_exists($rule)) {
					return $rule($str);
				}
				continue;
			} else {
				return $this->$rule($str, $param);
			}
		}
	}

    /**
     * Alpha-numeric with underscores, dashes and dots
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    function alpha_dash_dot($str)
    {
        return (!preg_match("/^([-a-z0-9\._-])+$/i", $str)) ? FALSE : TRUE;
    }

    /**
     * Validate date according to the specified format.
	 * PHP 5.3 ONLY!
     *
     * @access	public
     * @param	string
     * @param	date format
     * @return	bool
     */
    function valid_date($str, $format)
    {
        $date = date_parse_from_format($format, $str);
        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * Check that a value does not exist in the database table.
     * Param should be on the formt table.field (e.g. users.username)
     *
     * http://net.tutsplus.com/tutorials/php/6-codeigniter-hacks-for-the-masters/
     *
     * @access	public
     * @param	string
     * @param	table and field
     * @return	bool
     */
    function unique($value, $params)
    {
        $CI = & get_instance();
        $CI->load->database();

        list($table, $field) = explode(".", $params, 2);

        $query = $CI->db->select($field)->from($table)
                        ->where($field, $value)->limit(1)->get();

        if ($query->row()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Value must be numeric and less than or equal to the given parameter.
     *
     * @access	public
     * @param	value
     * @param	max value
     * @return	bool
     */
    function numeric_max($str, $max)
    {
        if ($this->numeric($str) && $this->numeric($max))
            return $str <= $max;
        else
            return false;
    }

    /**
     * Value must be numeric and higher than or equal to the given parameter.
     *
     * @access	public
     * @param	value
     * @param	min value
     * @return	bool
     */
    function numeric_min($str, $min)
    {
        if ($this->numeric($str) && $this->numeric($min))
            return $str <= $min;
        else
            return false;
    }

    /**
     * Value must be in the comma separated set of values.
     *
     * @access	public
     * @param	string
     * @param	set of valid values
     * @return	bool
     */
    function in_list($str, $list)
    {
        $set = explode(',', $list);
        return in_array($str, $set);
    }

    /**
     * Equal to a value.
     *
     * @access	public
     * @param	string
     * @param	set of valid values
     * @return	bool
     */
    function equals($str, $comp)
    {
        return ($str == $comp);
    }

    /**
     * Make sure the nonce is valid.
     *
     * @access	public
     * @param	string
     * @param	last used nonce
     * @return	bool
     */
    function valid_nonce($str)
    {
        return ($str == $this->CI->session->userdata('nonce') &&
                $str != $this->CI->session->userdata('old_nonce'));
    }

}