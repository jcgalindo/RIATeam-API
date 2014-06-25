<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

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
    }

    public function index()
    {
        try {

            // Tell the auth server to check the required parameters are in the
            //  query string
            $params = $this->authserver->getGrantType('authorization_code')->checkAuthoriseParams();

            $this->session->set_userdata('client_id', $params['client_id']);
            $this->session->set_userdata('client_details', $params['client_details']);
            $this->session->set_userdata('redirect_uri', $params['redirect_uri']);
            $this->session->set_userdata('response_type', $params['response_type']);
            $this->session->set_userdata('scopes', $params['scopes']);

            // Redirect the user to the sign-in route
            redirect('signin');

            //$this->load->view('oauth/signin');

        } catch (Oauth2\Exception\ClientException $e) {
            echo $e->getMessage();
            // Throw an error here which says what the problem is with the
            //  auth params

        } catch (Exception $e) {
            echo $e->getMessage();
            // Throw an error here which has caught a non-library specific error

        }
    }

    public function signin()
    {
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
                redirect('/oauth2/authorize');
            }
        } else {
            echo form_open('/oauth2/signin');
            echo form_label('Username', 'username');
            echo form_input('username', '');
            echo form_label('Password', 'password');
            echo form_password('password', '');
            echo form_submit('signin', 'Sign In!');
            echo form_close();
        }
    }

    public function authorize()
    {
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
            //$this->signin();
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
        echo form_open('/oauth2/authorize');
        echo form_submit('approve', 'yes');
        echo form_close();
    }

    public function access_token()
    {
        try {

            // Tell the auth server to issue an access token
            $response = $this->authserver->issueAccessToken();

        } catch (League\OAuth2\Server\Exception\ClientException $e) {

            // Throw an exception because there was a problem with the client's request
            $response = array(
                'error' =>  $this->authserver->getExceptionType($e->getCode()),
                'error_description' => $e->getMessage()
            );

            // Set the correct header
            header($this->authserver->getExceptionHttpHeaders($this->authserver->getExceptionType($e->getCode())));

        } catch (Exception $e) {

            // Throw an error when a non-library specific exception has been thrown
            $response = array(
                'error' =>  'undefined_error',
                'error_description' => $e->getMessage()
            );
        }

        header('Content-type: application/json');
        echo json_encode($response);
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */