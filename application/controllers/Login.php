<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	/**
	 * Index Page for this controller.
	 */
	protected $data = array();

    public function index(){
		$this->data["title"] = "Login - GREATER FMS";
		$this->load->view('login', $this->data);
	}

	public function login_pro(){
		// Support both POST and GET methods (fallback for restrictive hosting)
		// Prefer POST but fallback to GET if POST data is empty
		$email = $this->input->post('email', TRUE);
		$password_raw = $this->input->post('password', TRUE);

		// If POST data is empty, try GET (fallback for hosting with POST restrictions)
		if(empty($email) || empty($password_raw)){
			$email = $this->input->get('email', TRUE);
			$password_raw = $this->input->get('password', TRUE);
			log_message('warning', 'Login using GET method - possible POST restriction on hosting');
		}

		// Validate that credentials are provided
		if(empty($email) || empty($password_raw)){
			log_message('error', 'Login attempt with empty credentials');
			$this->session->set_flashdata('msg','Email and Password are required');
			redirect('login?status=error');
			return;
		}

		// Hash the password
		$password = sha1($password_raw);

		// Check if account is locked
		if($this->fmsm_enhanced->is_account_locked($email)){
			log_message('warning', 'Login attempt on locked account: ' . $email);
			$this->session->set_flashdata('msg','Account is locked due to multiple failed login attempts. Please try again later.');
			redirect('login?status=locked');
			return;
		}

		// Try enhanced authentication
		$validate = $this->fmsm_enhanced->checkUser($email, $password);

		if($validate->num_rows() > 0){
			$data = $validate->row_array();

			// Reset login attempts
			$this->fmsm_enhanced->reset_login_attempts($data['user_id']);

			// Set session using Auth Manager
			$this->auth_manager->set_user_session($data);

			log_message('info', 'Successful login for user: ' . $email);

			// Redirect to dashboard
			redirect('/');
		}else{
			// Increment login attempts
			$this->fmsm_enhanced->increment_login_attempts($email);

			log_message('warning', 'Failed login attempt for: ' . $email);

			$this->session->set_flashdata('msg','Email or Password is Wrong');
			redirect('login?status=error');
		}
	}
}