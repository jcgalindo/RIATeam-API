<?php


class Authorize extends CI_Controller {

    function index()
    {

        $this->load->library('session');
        $this->load->helper(array('url', 'form'));



        // Initiate the request handler which deals with $_GET, $_POST, etc
        $request = new League\OAuth2\Server\Util\Request();

        // Initiate a new database connection
        $db = new League\OAuth2\Server\Storage\PDO\Db('mysql://root:genomadna@localhost/api1.0');

        // Create the auth server, the three parameters passed are references
        //  to the storage models
        $this->authserver = new League\OAuth2\Server\Authorization(
            new League\OAuth2\Server\Storage\PDO\Client($db),
            new League\OAuth2\Server\Storage\PDO\Session($db),
            new League\OAuth2\Server\Storage\PDO\Scope($db)
        );

        // Enable the authorization code grant type
        $this->authserver->addGrantType(new League\OAuth2\Server\Grant\AuthCode($this->authserver));

        // init auto_approve for default value
        $params['client_details']['auto_approve'] = 0;

        // Retrieve the auth params from the user's session
        $params['client_id'] = $this->session->userdata('client_id');
        $params['client_details'] = $this->session->userdata('client_details');
        $params['redirect_uri'] = $this->session->userdata('redirect_uri');
        $params['response_type'] = $this->session->userdata('response_type');
        $params['scopes'] = $this->session->userdata('scopes');

        // Check that the auth params are all present
        foreach ($params as $key=>$value) {
            if ($value === null) {
                // Throw an error because an auth param is missing - don't
                //  continue any further
                // echo "stop";
                // exit;
            }
        }

        // Get the user ID
        $params['user_id'] = $this->session->userdata('user_id');

        // User is not signed in so redirect them to the sign-in route (/oauth/signin)
        if ($params['user_id'] == null) {
            redirect('signin');
        }

        // init autoApprove if in database, value is 0
        $params['client_details']['auto_approve'] = isset($params['client_details']['auto_approve']) ? $params['client_details']['auto_approve'] : 0;

        // Check if the client should be automatically approved
        $autoApprove = ($params['client_details']['auto_approve'] == '1') ? true : false;

        // Process the authorise request if the user's has clicked 'approve' or the client
        if ($this->input->post('approve') == 'yes' || $autoApprove === true) {

            // Generate an authorization code
            $code = $this->authserver->getGrantType('authorization_code')->newAuthoriseRequest('user',   $params['user_id'], $params);

            // Redirect the user back to the client with an authorization code
            $redirect_uri = League\OAuth2\Server\Util\RedirectUri::make(
                $params['redirect_uri'],
                array(
                    'code'  =>  $code,
                    'state' =>  isset($params['state']) ? $params['state'] : ''
                )
            );
            redirect($redirect_uri);
        }

        // If the user has denied the client so redirect them back without an authorization code
        if($this->input->get('deny') != null) {
            $redirect_uri = League\OAuth2\Server\Util\RedirectUri::make(
                $params['redirect_uri'],
                array(
                    'error' =>  'access_denied',
                    'error_message' =>  $this->authserver->getExceptionMessage('access_denied'),
                    'state' =>  isset($params['state']) ? $params['state'] : ''
                )
            );
            redirect($redirect_uri);
        }

        // The client shouldn't automatically be approved and the user hasn't yet
        //  approved it so show them a form
        echo form_open('authorize');
        echo form_submit('approve', 'yes');
        echo form_close();

    }
}