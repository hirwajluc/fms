<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth_Manager Library
 * Handles role-based access control for GREATER FMS
 */
#[\AllowDynamicProperties]
class Auth_manager {

    protected $CI;

    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_COORDINATOR = 'institution_coordinator';
    const ROLE_MEMBER = 'member';

    public function __construct(){
        $this->CI =& get_instance();
        $this->CI->load->model('Fms_model_enhanced', 'fmsm_enhanced');
    }

    /**
     * Check if user is logged in
     */
    public function is_logged_in(){
        return $this->CI->session->userdata('logged_in') === TRUE;
    }

    /**
     * Get current user data from session
     */
    public function get_current_user(){
        if(!$this->is_logged_in()){
            return NULL;
        }

        return array(
            'user_id' => $this->CI->session->userdata('fms_user_id'),
            'email' => $this->CI->session->userdata('fms_email'),
            'name' => $this->CI->session->userdata('fms_name'),
            'role' => $this->CI->session->userdata('fms_role_name'),
            'partner_id' => $this->CI->session->userdata('fms_partner_id'),
            'partner' => $this->CI->session->userdata('fms_partner'),
            'level' => $this->CI->session->userdata('fms_level')
        );
    }

    /**
     * Check if user has specific role
     */
    public function has_role($role){
        if(!$this->is_logged_in()){
            return FALSE;
        }

        $current_role = $this->CI->session->userdata('fms_role_name');

        if(is_array($role)){
            return in_array($current_role, $role);
        }

        return $current_role === $role;
    }

    /**
     * Check if user is Super Admin
     */
    public function is_super_admin(){
        return $this->has_role(self::ROLE_SUPER_ADMIN);
    }

    /**
     * Check if user is Admin
     */
    public function is_admin(){
        return $this->has_role(array(self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN));
    }

    /**
     * Check if user is Institution Coordinator
     */
    public function is_coordinator(){
        return $this->has_role(self::ROLE_COORDINATOR);
    }

    /**
     * Check if user is Member
     */
    public function is_member(){
        return $this->has_role(self::ROLE_MEMBER);
    }

    /**
     * Check if user has specific permission
     */
    public function has_permission($permission){
        if(!$this->is_logged_in()){
            return FALSE;
        }

        $user_id = $this->CI->session->userdata('fms_user_id');
        return $this->CI->fmsm_enhanced->check_permission($user_id, $permission);
    }

    /**
     * Check if user can manage users (create, edit, delete)
     */
    public function can_manage_users(){
        return $this->is_admin() || $this->has_permission('manage_members');
    }

    /**
     * Check if user can approve timesheets
     */
    public function can_approve_timesheets(){
        return $this->is_admin() || $this->is_coordinator();
    }

    /**
     * Check if user can upload expenses
     */
    public function can_upload_expenses(){
        return $this->is_admin() || $this->is_coordinator();
    }

    /**
     * Check if user can view all reports
     */
    public function can_view_all_reports(){
        return $this->is_admin();
    }

    /**
     * Check if user can access specific partner data
     */
    public function can_access_partner($partner_id){
        if($this->is_super_admin()){
            return TRUE;
        }

        $user_partner_id = $this->CI->session->userdata('fms_partner_id');
        return $user_partner_id == $partner_id;
    }

    /**
     * Check if user can edit specific timesheet
     */
    public function can_edit_timesheet($timesheet_user_id){
        $current_user_id = $this->CI->session->userdata('fms_user_id');

        // User can edit their own timesheet
        if($current_user_id == $timesheet_user_id){
            return TRUE;
        }

        // Admins and coordinators can edit timesheets from their institution
        if($this->is_admin() || $this->is_coordinator()){
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Require login - redirect if not logged in
     */
    public function require_login(){
        if(!$this->is_logged_in()){
            redirect('login');
        }
    }

    /**
     * Require specific role - show error if user doesn't have role
     */
    public function require_role($role){
        $this->require_login();

        if(!$this->has_role($role)){
            show_error('Access Denied: You do not have permission to access this page.', 403, 'Access Denied');
        }
    }

    /**
     * Require specific permission
     */
    public function require_permission($permission){
        $this->require_login();

        if(!$this->has_permission($permission)){
            show_error('Access Denied: You do not have permission to perform this action.', 403, 'Access Denied');
        }
    }

    /**
     * Set user session after login
     */
    public function set_user_session($user_data){
        $sesdata = array(
            'fms_user_id' => $user_data['user_id'],
            'fms_name' => $user_data['fname'] . ' ' . $user_data['lname'],
            'fms_fname' => $user_data['fname'],
            'fms_lname' => $user_data['lname'],
            'fms_email' => $user_data['email'],
            'fms_partner' => $user_data['thepartner'],
            'fms_partner_id' => $user_data['thepartnerid'],
            'fms_position' => $user_data['theposition'],
            'fms_role' => $user_data['therole'],
            'fms_role_name' => $user_data['role_name'],
            'fms_staff_id' => $user_data['staff_id'],
            'fms_level' => $user_data['level'],
            'logged_in' => TRUE
        );

        $this->CI->session->set_userdata($sesdata);

        // Update last login
        $this->CI->fmsm_enhanced->update_last_login($user_data['user_id']);

        // Log activity
        $this->CI->fmsm_enhanced->log_activity(
            $user_data['user_id'],
            'login',
            'user',
            $user_data['user_id'],
            'User logged in'
        );
    }

    /**
     * Clear user session (logout)
     */
    public function clear_session(){
        $user_id = $this->CI->session->userdata('fms_user_id');

        // Log activity
        if($user_id){
            $this->CI->fmsm_enhanced->log_activity(
                $user_id,
                'logout',
                'user',
                $user_id,
                'User logged out'
            );
        }

        $this->CI->session->sess_destroy();
    }

    /**
     * Get role display name
     */
    public function get_role_display_name($role){
        $role_names = array(
            self::ROLE_SUPER_ADMIN => 'Super Administrator',
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_COORDINATOR => 'Institution Coordinator',
            self::ROLE_MEMBER => 'Member'
        );

        return isset($role_names[$role]) ? $role_names[$role] : ucfirst($role);
    }

    /**
     * Get user's accessible partners
     */
    public function get_accessible_partners(){
        if($this->is_super_admin()){
            return $this->CI->fmsm_enhanced->get_all_partners();
        }

        $partner_id = $this->CI->session->userdata('fms_partner_id');
        if($partner_id){
            $partner = $this->CI->fmsm_enhanced->get_partner_by_id($partner_id);
            return $partner ? array($partner) : array();
        }

        return array();
    }
}
