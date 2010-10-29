<?php

class Account_Controller extends MY_Controller {

    function __construct() {
        parent::MY_Controller();
        $guests_allowed = array('login', 'register', 'forgot', 'recover');
        if ($this->auth->isGuest() && !in_array($this->uri->segment(2), $guests_allowed)) {
            // Guests need to login first
            redirect('account/login');
        }
        $this->load->model('User');
        $this->history->exclude();
    }

    function register() {
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->form_validation->set_rules('passconf', 'Lösenordskontroll', 'required|matches[password]');
        if ($this->User->validate() === true) { // If validation has completed
            $username = $this->input->post('username');
            $password = $this->input->post('password');
            $data['password'] = $this->User->encrypt_password($username, $password);
            $this->User->create($data);
            $this->auth->login($username, $password);
            $this->notice->success('Välkommen till Skivsamlingen ' . $username . '!');
            redirect();
        }
    }

    function forgot()
    {
        $this->load->model('User');
        $username = $this->input->post('username', true);
        if($username !== false) {
            if(strpos($username, '@') === FALSE)
                $user = $this->User->fetchOne(array('username' => $this->input->post('username', true)));
            else
                $user = $this->User->fetchOne(array('email' => $this->input->post('username', true)));

            if(!$user) {
                $this->notice->error('Användarnamnet eller e-postadressen kunde inte hittas.');
                redirect('account/forgot');
            }

            $already_sent = $this->db->where('username', $user->username)->get('password_recovery')->row();
            if($already_sent) {
                $this->notice->error('Ett mail för återställning har redan skickats.');
                redirect();
            }

            if ($user->email) {
                $this->load->library('email');

                $random_hash = sha1(uniqid(rand(), true));

                $this->email->from('no-reply@skivsamlingen.se', 'Skivsamlingen');
                $this->email->to($user->email);
                $this->email->subject('Skivsamlingen: Återställ lösenord');
                $this->email->message('Hej!

En anmälan om att återställa ditt lösenord har skickats till Skivsamlingen. För att genomföra återställningen går du till följande adress: ' . site_url('account/recover/' . $user->username) . '/' . $random_hash . '. Länken är bara giltig i 48 timmar.

Detta mail är skickat automatiskt från http://skivsamlingen.se/. Om du inte begärde återställningen kan du ignorera detta mail.');

                if($this->email->send()) {
                    $this->db->set('username', $user->username)->set('hash', $random_hash)->set('created_on', mktime())->insert('password_recovery');
                    $this->notice->success('Ett mail har skickats till din registrerade e-postadress. Använd länken i mailet för att återställa lösenordet.');
                } else {
                    log_message('error', $this->email->print_debugger());
                    $this->notice->error('Vi ber om ursäkt! Ett problem uppstod när mailet skulle skickas. Var god försök igen senare.');
                }
                redirect();
            }
        }
    }

    function recover($username, $hash)
    {
        $this->db->where('created_on <', mktime() - 60*60*48)->delete('password_recovery');
        if ($this->db->where('username', $username)->where('hash', $hash)->get('password_recovery')->result()) {
            $this->load->model('User');
            $this->form_validation->set_rules('passconf', 'Kontrollfältet', 'matches[password]');

            if ($this->User->validateData() !== false) {
                $this->User->update(
                        array('username' => $username),
                        array('password' => $this->User->encrypt_password($username, $this->input->post('password'))),
                        false
                );
                $this->db->where('username', $username)->delete('password_recovery');
                $this->notice->success('Ditt lösenord är uppdaterat.');
                redirect('account/login');
            }
        } else {
            $this->notice->error('Länken är inte giltig.');
            redirect('account/forgot');
        }
    }

    function unregister() {
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->view = 'account/edit';
        $user = $this->auth->getUser();
        $this->data['user'] = $user;
        $this->form_validation->set_rules('rem_password', 'Lösenord', 'required');
        $this->form_validation->set_rules('rem_confirm', 'Bekräktelse', 'required|strtolower|equals[ta bort]');
        $password = $this->User->encrypt_password($user->username, $this->input->post('rem_password'));

        if ($password != $user->password) {
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

    function message() {
        $this->_pass();
        $this->load->library('notification');
        $this->notification->createMessage('Det är starkt rekommenderat att ange sin e-postadress då det är enda sättet att återställa sitt lösenord om det kommer bort. Du kan göra detta under <a href="' . site_url('account/edit') . '">dina inställningar</a>.'
                , "email IS NULL");
        echo "Notification created.";
    }

    function edit() {
        $user = $this->auth->getUser();
        $this->data['user'] = $user;
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        if ($this->User->validateData() === true) { // If validation has completed
            $this->User->update($user->id);
            $this->notice->success('Dina uppgifter har uppdaterats.');
            redirect('account/edit');
        }
    }

    function password() {
        $this->view = 'account/edit';
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $user = $this->auth->getUser();
        $this->data['user'] = $user;
        $this->form_validation->set_rules('ch_newpass', 'Nytt lösenord', 'required|min_length[6]');
        $this->form_validation->set_rules('ch_newpassconf', 'Lösenordskontroll', 'required|matches[ch_newpass]');
        $this->form_validation->set_rules('ch_oldpass', 'Lösenord', 'required');
        $old_password = $this->User->encrypt_password($user->username, $this->input->post('ch_oldpass'));

        if ($old_password != $user->password) {
            $this->form_validation->set_error('ch_oldpass', 'Ditt nuvarande lösenord var felaktigt.');
        }
        if ($this->form_validation->run()) { // If validation has completed
            $data['password'] = $this->User->encrypt_password($user->username, $this->input->post('ch_newpass'));
            $this->User->update($user->id, $data, false);
            $this->notice->success('Ditt lösenord är ändrat.');
            redirect('account/edit');
        }
    }

    function login() {
        if ($this->auth->isUser())
            redirect('users/' . $this->auth->getUsername());
        $username = $this->input->post('username', TRUE);
        if ($username !== FALSE) {
            if ($this->auth->login($username, $this->input->post('password'))) {
                if ($this->input->post('remember_me')) {
                    $this->auth->remember();
                }
                $this->notice->success('Du är inloggad!', 'login');
                redirect($this->history->pop());
            } else {
                $this->notice->error('Felaktiga användaruppgifter.');
                redirect('account/login');
            }
        }
    }

    function logout() {
        $this->auth->logout();
        redirect();
    }

}

/* End of file user.php */
/* Location: ./system/application/controllers/account_controller.php */