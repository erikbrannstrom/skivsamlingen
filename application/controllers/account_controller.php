<?php

class Account_Controller extends MY_Controller {

	function __construct()
	{
		parent::MY_Controller();
        if($this->auth->isGuest() && $this->uri->segment(2) != 'login' && $this->uri->segment(2) != 'register') {
            // Guests need to login first
            redirect('account/login');
        }
		$this->load->model('User');
	}

	function register()
	{
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->form_validation->set_rules('passconf', 'Lösenordskontroll', 'required|matches[password]');
		if ($this->User->validate() === true) { // If validation has completed
			$username = $this->input->post('username', TRUE);
            $password = $this->input->post('password');
			$data['password'] = $this->User->encrypt_password($username, $password);
            $this->User->create($data);
            $this->auth->login($username, $password);
			$this->notice->success('Välkommen till Skivsamlingen ' . $username . '!');
			redirect('welcome');
		}
	}

	function unregister()
	{
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->view = 'account/edit';
        $user = $this->auth->getUser();
        $this->data['user'] = $user;
        $this->form_validation->set_rules('rem_password', 'Lösenord', 'required');
        $this->form_validation->set_rules('rem_confirm', 'Bekräktelse', 'required|strtolower|equals[ta bort]');
        $password = $this->User->encrypt_password($user->username, $this->input->post('rem_password'));

        if($password != $user->password) {
            $this->form_validation->set_error('rem_password', 'Lösenordet är felaktigt.');
        }
		if ($this->form_validation->run()) { // If validation has completed
            $user = $this->auth->getUser();
            $this->auth->logout();
            // Remove all records from user
            $this->load->model('Collection');
            $removed_num = $this->Collection->delete(array('user_id' => $user->id));
            // Remove user
            $this->load->model('User');
            $this->User->deleteOne($user->id);
            // Redirect
            $this->session->set_flashdata('action', 'unregistered');
            $this->session->set_flashdata('email', $user->email);
            $this->session->set_flashdata('name', $user->name);
            $this->session->set_flashdata('username', $user->username);
            redirect('home/unregistered');
		}
	}

    function message()
    {
        $this->_pass();
        $this->load->library('notification');
        $this->notification->createMessage('Det är starkt rekommenderat att ange sin e-postadress då det är enda sättet att återställa sitt lösenord om det kommer bort. Du kan göra detta under <a href="'.site_url('account/edit').'">dina inställningar</a>.'
                , "email IS NULL");
        echo "Notification created.";
    }

    function edit()
    {
        $user = $this->auth->getUser();
        $this->data['user'] = $user;
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		if ($this->User->validateData() === true) { // If validation has completed
            $this->User->update($user->id);
			$this->notice->success('Dina uppgifter har uppdaterats.');
			redirect('account/edit');
		}
    }

    function password()
    {
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->view = 'account/edit';
        $user = $this->auth->getUser();
        $this->data['user'] = $user;
        $this->form_validation->set_rules('ch_newpass', 'Nytt lösenord', 'required|min_length[6]');
        $this->form_validation->set_rules('ch_newpassconf', 'Lösenordskontroll', 'required|matches[ch_newpass]');
        $this->form_validation->set_rules('ch_oldpass', 'Lösenord', 'required');
        $old_password = $this->User->encrypt_password($user->username, $this->input->post('ch_oldpass'));

        if($old_password != $user->password) {
            $this->form_validation->set_error('ch_oldpass', 'Ditt nuvarande lösenord var felaktigt.');
        }
		if ($this->form_validation->run()) { // If validation has completed
            $data['password'] = $this->User->encrypt_password($user->username, $this->input->post('ch_newpass'));
            $this->User->update($user->id, $data, false);
            $this->notice->success('Ditt lösenord är ändrat.');
            redirect('account/edit');
		}
    }

	function login()
	{
		if($this->auth->isUser())
			redirect('user/profile/'.$this->auth->getUsername());
		$username = $this->input->post('username', TRUE);
		if( $username !== FALSE ) {
			if($this->auth->login($username, $this->input->post('password'))) {
				$this->notice->success('Du är inloggad!', 'login');
				redirect('users/'.$this->auth->getUsername());
			} else {
				$this->notice->error('Felaktiga användaruppgifter.');
				redirect('account/login');
			}
		}
	}

	function logout()
	{
		$this->auth->logout();
		redirect('');
	}

}

/* End of file user.php */
/* Location: ./system/application/controllers/account_controller.php */