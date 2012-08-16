<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2006, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * Pagination Class
 *
 * This is the standard Pagination class from the CI library with some
 * slight changes. These are the following:
 *
 *   - The Output is always ul-based
 *   - Instead of changing HTML for the output, user edits classes
 *   - No First and Last links, instead a user defined number of links for
 *     the first and last pages are always shown
 *   - Possibility to pass extra data in the URI (Thanks ofDan of CI Wiki)
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Pagination
 * @author		ExpressionEngine Dev Team
 * @modified	Erik Brännström
 * @link		http://codeigniter.com/user_guide/libraries/pagination.html
 */
class MY_Pagination extends CI_Pagination {

    var $post_url = '';
 // URL for posted data
    var $num_links = 2;
 // Number of "digit" links to show before/after the currently viewed page
    var $num_links_end = 1;
 // Number of "digit" links to show at the very ends of the list (e.g. first and last pages)
    var $cur_page = 0;
 // The current page being viewed
    var $next_link = '&gt;';
    var $prev_link = '&lt;';
    var $title = '';
    var $class_container = '';
    var $class_step = '';
    var $class_active = '';
    var $class_inactive = '';

    /**
     * Constructor
     *
     * @access	public
     * @param	array	initialization parameters
     */
    function __construct($params = array()) {
        parent::__construct($params);
        log_message('debug', "MY Pagination Class Initialized");
    }

    // --------------------------------------------------------------------

    function _add_classes($classes) {
        if (!isset($classes)) {
            return '';
        } else {
            $output = ' class="';
            if (is_array($classes)) {
                foreach ($classes as $class) {
                    $output .= $class . ' ';
                }
                $output = substr($output, 0, -1) . '"';
            } else {
                $output .= $classes . '"';
            }
            return $output;
        }
    }

    function get_num_pages() {
        return ceil($this->total_rows / $this->per_page);
    }

    /**
     * Generate the pagination links
     *
     * @access	public
     * @return	string
     */
    function create_links() {
        // If our item count or per-page total is zero there is no need to continue.
        if ($this->total_rows == 0 || $this->per_page == 0) {
            return '';
        }

        // Calculate the total number of pages
        $num_pages = $this->get_num_pages();

        // Is there only one page? Hm... nothing more to do here then.
        if ($num_pages == 1) {
            return '';
        }

        // Make sure there is a slash at the beginning of the posted data
        $this->post_url = '/' . ltrim($this->post_url, '/');

        // Determine the current page number.
        $CI = & get_instance();
        if ($CI->uri->segment($this->uri_segment) != 0) {
            $this->cur_page = $CI->uri->segment($this->uri_segment);

            // Prep the current page - no funny business!
            $this->cur_page = (int) $this->cur_page;
        }

        $this->num_links = (int) $this->num_links;

        if ($this->num_links < 1) {
            show_error('Your number of links must be a positive number.');
        }

        if (!is_numeric($this->cur_page)) {
            $this->cur_page = 0;
        }

        // Is the page number beyond the result range?
        // If so we show the last page
        if ($this->cur_page > $this->total_rows) {
            $this->cur_page = ($num_pages - 1) * $this->per_page;
        }

        $uri_page_number = $this->cur_page;
        $this->cur_page = floor(($this->cur_page / $this->per_page) + 1);

        // Add a trailing slash to the base URL if needed
        $this->base_url = rtrim($this->base_url, '/') . '/';

        // And here we go...
        $output = '';

        // Render the "previous" link
        if ($this->cur_page != 1) {
            $i = $uri_page_number - $this->per_page;
            $output .= '<li' . $this->_add_classes($this->class_step) . '><a href="' . $this->base_url . $i . $this->post_url . '">' . $this->prev_link . '</a></li>';
        } else {
            $output .= '<li' . $this->_add_classes(array($this->class_step, $this->class_inactive)) . '>' . $this->prev_link . '</li>';
        }

        // Calculate the start and end numbers. These determine
        // which number to start and end the digit links with
        $start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - $this->num_links : 1;
        $end = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

        // Write the very first links
        if (($start - $this->num_links_end) <= 2) {
            $start = 1;
        } else {
            for ($i = 0; $i < $this->num_links_end; $i++) {
                $output .= '<li><a href="' . $this->base_url . ($i * $this->per_page) . $this->post_url . '">' . ($i + 1) . '</a></li>';
            }
            $output .= '<li' . $this->_add_classes($this->class_inactive) . '>...</li>';
        }

        // See if the very last links need to be written separetly
        if (($end + $this->num_links_end) >= $num_pages - 1) {
            $end = $num_pages;
        }

        // Write the digit links
        for ($loop = $start; $loop <= $end; $loop++) {
            $i = ($loop * $this->per_page) - $this->per_page;

            if ($i >= 0) {
                if ($this->cur_page == $loop) {
                    $output .= '<li' . $this->_add_classes($this->class_active) . '>' . $loop . '</li>'; // Current page
                } else {
                    $output .= '<li><a href="' . $this->base_url . $i . $this->post_url . '">' . $loop . '</a></li>';
                }
            }
        }

        if ($end != $num_pages) {
            $output .= '<li' . $this->_add_classes($this->class_inactive) . '>...</li>';
            for ($i = $num_pages - $this->num_links_end; $i < $num_pages; $i++) {
                $output .= '<li><a href="' . $this->base_url . ($i * $this->per_page) . $this->post_url . '">' . ($i + 1) . '</a></li>';
            }
        }

        // Render the "next" link
        if ($this->cur_page < $num_pages) {
            $output .= '<li' . $this->_add_classes($this->class_step) . '><a href="' . $this->base_url . ($this->cur_page * $this->per_page) . $this->post_url . '">' . $this->next_link . '</a></li>';
        } else {
            $output .= '<li' . $this->_add_classes(array($this->class_step, $this->class_inactive)) . '>' . $this->next_link . '</li>';
        }

        // Render the title, if one is set
        if ($this->title != '') {
            $output .= '<li class="title">' . $this->title . '</li>';
        }

        // Kill double slashes.  Note: Sometimes we can end up with a double slash
        // in the penultimate link so we'll kill all double slashes.
        $output = preg_replace("#([^:])//+#", "\\1/", $output);

        // Add the wrapper HTML if exists
        $output = '<ul' . $this->_add_classes($this->class_container) . '>' . $output . '</ul>';

        return $output;
    }

}

// END Pagination Class

/* End of file Pagination.php */
/* Location: ./system/libraries/Pagination.php */