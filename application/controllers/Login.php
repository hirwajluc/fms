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

	private function _mailer(){
		if(!isset($this->fms_mailer)){
			$this->load->library('fms_mailer');
		}
	}

	public function forgotPassword(){
		$this->data['title'] = 'Forgot Password – GREATER FMS';
		$this->load->view('forgot_password', $this->data);
	}

	public function processForgotPassword(){
		$email = trim($this->input->post('email', TRUE));

		if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
			$this->session->set_flashdata('msg', 'Please enter a valid email address.');
			redirect('forgotPassword');
			return;
		}

		// Look up user
		$user = $this->fmsm_enhanced->get_user_by_email($email);
		if(!$user){
			// Don't reveal if email exists — show generic success message
			$this->session->set_flashdata('success', 'If that email is registered, a new password has been sent.');
			redirect('forgotPassword');
			return;
		}

		// Generate a random password
		$chars       = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
		$new_password = '';
		for($i = 0; $i < 10; $i++){
			$new_password .= $chars[random_int(0, strlen($chars) - 1)];
		}

		// Save hashed password — force password change on next login
		$this->fmsm_enhanced->update_user_password($user['user_id'], sha1($new_password), TRUE);

		// Send email
		$full_name = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
		$this->fms_mailer->password_reset($email, trim($full_name), $new_password);

		$this->session->set_flashdata('success', 'A new password has been sent to your email address.');
		redirect('forgotPassword');
	}
}