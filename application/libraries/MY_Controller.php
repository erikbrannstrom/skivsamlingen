<?php

/**
 * A base controller that provides clever model 
 * loading, view loading and layout support.
 *
 * Edit has removed the model loading and made a slight change to the view rendering.
 *
 * @package CodeIgniter
 * @subpackage MY_Controller
 * @license GPLv3 <http://www.gnu.org/licenses/gpl-3.0.txt>
 * @link http://github.com/jamierumbelow/codeigniter-base-controller
 * @version 1.1.1
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @copyright Copyright (c) 2009, Jamie Rumbelow <http://jamierumbelow.net>
 */
class MY_Controller extends Controller {

	/**
	 * The view to load, only set if you want
	 * to bypass the autoload magic.
	 *
	 * @var string
	 */
	protected $view;
	
	/**
	 * The data to pass to the view, where
	 * the keys are the names of the variables
	 * and the values are the values.
	 *
	 * @var array
	 */
	protected $data = array('page_title' => 'DEV: Skivsamlingen');
	
	protected $controller_suffix = '_Controller';
	
	/**
	 * The layout to load the view into. Only
	 * set if you want to bypass the magic.
	 *
	 * @var string
	 */
	protected $layout;
	
	/**
	 * An array of asides. The key is the name
	 * to reference by and the value is the file.
	 * The class will loop through these, parse them 
	 * and push them via a variable to the layout. 
	 * 
	 * This allows any number of asides like sidebars,
	 * footers etc. 
	 *
	 * @var array
	 * @since 1.1.0
	 */
	protected $asides = array();
	
	/**
	 * The directory to store partials in.
	 *
	 * @var string
	 */
	protected $partial = 'partials';
	
	/**
	 * The prerendered data for output buffering
	 * and the render() method. Generally left blank.
	 *
	 * @since 1.1.1
	 * @var string
	 */
	protected $prerendered_data = '';
	
	/**
	 * The class constructor, loads the models
	 * from the $this->models array.
	 *
	 * Can't extend the default controller as it
	 * can't load the default libraries due to __get()
	 *
	 * @author Jamie Rumbelow
	 */
	public function MY_Controller() {
		parent::Controller();
		$this->output->enable_profiler(false);
	}
	
	/**
	 * Called by CodeIgniter instead of the action
	 * directly, automatically loads the views.
	 *
	 * @param string $method The method to call
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function _remap($method) {
		if (method_exists($this, $method)) {
			call_user_func_array(array($this, $method), array_slice($this->uri->rsegments, 2));
		} else {
			if (method_exists($this, '_404')) {
				call_user_func_array(array($this, '_404'), array($method));	
			} else {
				show_404(strtolower(get_class($this)).'/'.$method);
			}
		}
		
		$this->_load_view();
	}
	
	/**
	 * Loads the view by figuring out the
	 * controller, action and conventional routing.
	 * Also takes into account $this->view, $this->layout
	 * and $this->sidebar.
	 *
	 * Updated to render a view even if no layout exists.
	 * Also removes the specified controller suffix.
	 *
	 * @return void
	 * @access private
	 * @author Jamie Rumbelow
	 * @editor Erik Brännström
	 */
	private function _load_view() {
		if ($this->view !== FALSE) {
			if (!isset($this->layout)) {
				if (file_exists(APPPATH . 'views/layouts/' . $this->router->class . '.php')) {
					$this->load->view('layouts/' . $this->router->class . '.php', $this->_load_view_partials());
				} elseif (file_exists(APPPATH . 'views/layouts/application.php')) {
					$this->load->view('layouts/application.php', $this->_load_view_partials());
				} else {
				
					$view = ($this->view !== null) ? $this->view . '.php' : $this->router->directory . str_ireplace($this->controller_suffix, '', $this->router->class) . '/' . $this->router->method . '.php';
					$this->load->view($view, $this->data);
				}
			} else {
				$this->load->view('layouts/' . $this->layout . '.php', $this->_load_view_partials());
			}
		}
	}
	
	private function _load_view_partials() {
		$view = ($this->view !== null) ? $this->view . '.php' : $this->router->directory . str_ireplace($this->controller_suffix, '', $this->router->class) . '/' . $this->router->method . '.php';
		$data['yield'] =  $this->prerendered_data;
		$data['yield'] .= $this->load->view($view, $this->data, TRUE);

		if (!empty($this->asides)) {
			foreach ($this->asides as $name => $file) {
				$data['yield_'.$name] = $this->load->view($file, $this->data, TRUE);
			}
		}

		return array_merge($this->data, $data);
	}
	
	/**
	 * A helper method for controller actions to stop
	 * from loading any views.
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _pass() {
		$this->view = FALSE;
	}
	
	/**
	 * A helper method to check if a request has been
	 * made through XMLHttpRequest (AJAX) or not 
	 *
	 * @return bool
	 * @author Jamie Rumbelow
	 */
	protected function is_ajax() {
		return ($this->input->server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') ? TRUE : FALSE;
	}
	
	/**
	 * Renders the current view and adds it to the 
	 * output buffer. Useful for rendering more than one
	 * view at once.
	 *
	 * @return void
	 * @since 1.0.5
	 * @author Jamie Rumbelow
	 */
	protected function render() {
	  $this->prerendered_data .= $this->load->view($this->view, $this->data, TRUE);
	}
	
	/**
	 * Partial rendering method, generally called via the helper.
	 * renders partials and returns the result. Pass it an optional 
	 * data array and an optional loop boolean to loop through a collection.
	 *
	 * @param string $name The partial name
	 * @param array $data The data or collection to pass through
	 * @param boolean $loop Whether or not to loop through a collection
	 * @return string
	 * @since 1.1.0
	 * @author Jamie Rumbelow and Jeremy Gimbel
	 */
	public function partial($name, $data = null, $loop = TRUE) {
		$partial = '';
		$name = $this->partial . '/' . $name;
		
		if (!isset($data)) {
			$partial = $this->load->view($name, array(), TRUE);
		} else {
			if ($loop == TRUE) {
				foreach ($data as $row) {
					$partial.= $this->load->view($name, (array)$row, TRUE);
				}
			} else {
				$partial.= $this->load->view($name, $data, TRUE);
			}
		}
		
		return $partial;
	}
	
}
