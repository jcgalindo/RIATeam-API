<?php

class Signin extends CI_Controller {

    function index()
    {

        $this->load->library('session');
        $this->load->helper(array('url', 'form'));


        // Retrieve the auth params from the user's session
        $params['client_id'] = $this->session->userdata('client_id');
        $params['client_details'] = $this->session->userdata('client_details');
        $params['redirect_uri'] = $this->session->userdata('redirect_uri');
        $params['response_type'] = $this->session->userdata('response_type');
        $params['scopes'] = $this->session->userdata('scopes');

// Check that the auth params are all present
        foreach ($params as $key=>$value) {
            if ($value == null) {
                // Throw an error because an auth param is missing - don't
                //  continue any further
                // echo "stop";
                // exit;
            }
        }
        echo "ddddd" . $this->input->post('signin');
// Process the sign-in form submission
        if ($this->input->post('signin') != null) {
            try {

                // Get username
                $u = $this->input->post('username');
                if ($u == null || trim($u) == '') {
                    throw new Exception('please enter your username.');
                }

                // Get password
                $p = $this->input->post('password');
                if ($p == null || trim($p) == '') {
                    throw new Exception('please enter your password.');
                }

                // Verify the user's username and password
                // Set the user's ID to a session
                if($u == 'test' && $p == 'test') {
                    $this->session->set_userdata('user_id', 'test');
                }

            } catch (Exception $e) {
                $params['error_message'] = $e->getMessage();

            }

            // Get the user's ID from their session
            $params['user_id'] = $this->session->userdata('user_id');

            // User is signed in
            if ($params['user_id'] != null) {
                // Redirect the user to /oauth/authorise route
                redirect('authorize');
            }
        } else {
            echo form_open('signin');
            echo form_label('Username', 'username');
            echo form_input('username', '');
            echo form_label('Password', 'password');
            echo form_password('password', '');
            echo form_submit('signin', 'Sign In!');
            echo form_close();
        }
    }
}
?>