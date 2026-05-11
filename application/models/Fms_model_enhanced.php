<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fms_model_enhanced extends CI_Model{

    // ============================================
    // USER AUTHENTICATION & MANAGEMENT
    // ============================================

    public function checkUser($email, $password, $ekey="users.email", $pkey="users.password"){
        $res = $this->db->select("users.*,
            roles.role_name,
            roles.permissions,
            staff.first_name AS fname,
            staff.last_name AS lname,
            staff.department AS thedepartment,
            staff.position AS theposition,
            staff.greater_role AS therole,
            partners.name AS thepartner,
            partners.partner_id AS thepartnerid")
            ->where($ekey, $email)
            ->where($pkey, $password)
            ->where('users.status', 'active')
            ->join("staff", "staff.staff_id=users.staff_id", "inner")
            ->join("roles", "roles.role_id=users.role_id", "left")
            ->join("partners", "staff.partner_id=partners.partner_id", "left")
            ->get("users");
        return $res;
    }

    public function update_last_login($user_id){
        $this->db->where('user_id', $user_id);
        $this->db->update('users', array('last_login' => date('Y-m-d H:i:s')));
    }

    public function increment_login_attempts($email){
        $this->db->where('email', $email);
        $this->db->set('login_attempts', 'login_attempts+1', FALSE);
        $this->db->update('users');
    }

    public function reset_login_attempts($user_id){
        $this->db->where('user_id', $user_id);
        $this->db->update('users', array('login_attempts' => 0, 'locked_until' => NULL));
    }

    public function lock_account($email, $minutes = 30){
        $lock_until = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
        $this->db->where('email', $email);
        $this->db->update('users', array('locked_until' => $lock_until));
    }

    public function is_account_locked($email){
        $user = $this->db->where('email', $email)
                         ->where('locked_until >', date('Y-m-d H:i:s'))
                         ->get('users')->row();
        return $user !== NULL;
    }

    // ============================================
    // USER & STAFF MANAGEMENT
    // ============================================

    public function get_all_users($partner_id = NULL){
        $this->db->select("users.*,
            roles.role_name,
            staff.first_name,
            staff.last_name,
            staff.email AS staff_email,
            staff.position,
            staff.partner_id,
            partners.name AS partner_name,
            partners.short_name AS partner_short_name")
            ->join("staff", "staff.staff_id=users.staff_id", "inner")
            ->join("roles", "roles.role_id=users.role_id", "left")
            ->join("partners", "staff.partner_id=partners.partner_id", "left");

        if($partner_id){
            $this->db->where('staff.partner_id', $partner_id);
        }

        $query = $this->db->get("users");
        return $query->result_array();
    }

    public function get_all_staff($partner_id = NULL){
        $this->db->select("staff.*,
            partners.name AS partner_name,
            partners.short_name AS partner_short_name")
            ->join("partners", "staff.partner_id=partners.partner_id", "left");

        if($partner_id){
            $this->db->where('staff.partner_id', $partner_id);
        }

        $query = $this->db->get("staff");
        return $query->result_array();
    }

    public function get_user_by_email($email){
        return $this->db
            ->select('users.*, staff.first_name, staff.last_name, staff.email AS staff_email')
            ->join('staff', 'staff.staff_id = users.staff_id', 'left')
            ->where('users.email', $email)
            ->get('users')->row_array();
    }

    public function update_user_password($user_id, $hashed_password, $force_change = FALSE){
        return $this->db->where('user_id', $user_id)
                        ->update('users', [
                            'password'              => $hashed_password,
                            'force_password_change' => $force_change ? 1 : 0,
                        ]);
    }

    public function clear_force_password_change($user_id){
        return $this->db->where('user_id', $user_id)
                        ->update('users', ['force_password_change' => 0]);
    }

    /** Return emails of all super admins and admins */
    public function get_admin_emails(){
        $rows = $this->db->select('users.email')
                         ->where_in('users.role_id', [1, 2])
                         ->where('users.status', 'active')
                         ->get('users')->result_array();
        return array_column($rows, 'email');
    }

    /** Return emails of coordinators for a given partner */
    public function get_coordinator_emails($partner_id){
        $rows = $this->db->select('users.email')
                         ->join('staff', 'staff.staff_id = users.staff_id', 'inner')
                         ->where('users.role_id', 3)
                         ->where('staff.partner_id', $partner_id)
                         ->where('users.status', 'active')
                         ->get('users')->result_array();
        return array_column($rows, 'email');
    }

    public function get_user_by_id($user_id){
        return $this->db->select("users.*,
            roles.role_name,
            staff.*,
            partners.name AS partner_name")
            ->join("staff", "staff.staff_id=users.staff_id", "inner")
            ->join("roles", "roles.role_id=users.role_id", "left")
            ->join("partners", "staff.partner_id=partners.partner_id", "left")
            ->where('users.user_id', $user_id)
            ->get("users")->row_array();
    }

    public function get_staff_by_id($staff_id){
        return $this->db->select("staff.*,
            partners.name AS partner_name,
            partners.short_name AS partner_short_name")
            ->join("partners", "staff.partner_id=partners.partner_id", "left")
            ->where('staff.staff_id', $staff_id)
            ->get("staff")->row_array();
    }

    public function create_staff($staff_data){
        $this->db->insert('staff', $staff_data);
        return $this->db->insert_id();
    }

    public function update_staff($staff_id, $staff_data){
        $this->db->where('staff_id', $staff_id);
        return $this->db->update('staff', $staff_data);
    }

    public function delete_staff($staff_id){
        $this->db->where('staff_id', $staff_id);
        return $this->db->delete('staff');
    }

    public function create_user($user_data, $staff_data = NULL){
        if($staff_data){
            $this->db->trans_start();

            // Insert staff record
            $this->db->insert('staff', $staff_data);
            $staff_id = $this->db->insert_id();

            // Insert user record
            $user_data['staff_id'] = $staff_id;
            $this->db->insert('users', $user_data);
            $user_id = $this->db->insert_id();

            $this->db->trans_complete();

            return $this->db->trans_status() ? $user_id : FALSE;
        } else {
            // Just create user record (staff_id must be in user_data)
            $this->db->insert('users', $user_data);
            return $this->db->insert_id();
        }
    }

    public function update_user($user_id, $user_data){
        $this->db->where('user_id', $user_id);
        return $this->db->update('users', $user_data);
    }

    public function delete_user($user_id){
        // Get user to find staff_id
        $user = $this->get_user_by_id($user_id);

        if($user){
            $this->db->trans_start();

            // Delete user record
            $this->db->where('user_id', $user_id);
            $this->db->delete('users');

            // Delete associated staff record
            $this->db->where('staff_id', $user['staff_id']);
            $this->db->delete('staff');

            $this->db->trans_complete();

            return $this->db->trans_status();
        }

        return FALSE;
    }

    public function get_coordinators_by_partner($partner_id){
        return $this->db->select("users.*, staff.*")
            ->join("staff", "staff.staff_id=users.staff_id")
            ->join("roles", "roles.role_id=users.role_id")
            ->where('staff.partner_id', $partner_id)
            ->where('roles.role_name', 'institution_coordinator')
            ->get("users")->result_array();
    }

    // ============================================
    // EXPENSE MANAGEMENT
    // ============================================

    public function get_all_expenses($partner_id = NULL, $status = NULL){
        $this->db->select('expenses.*,
            partners.name AS partner_name,
            users.email AS uploaded_by_email,
            approver.email AS approved_by_email,
            (SELECT rwf_per_eur FROM forex_rates WHERE rate_date <= expenses.Date ORDER BY rate_date DESC LIMIT 1) AS forex_rate', FALSE)
            ->join('partners', 'partners.partner_id=expenses.partner_id', 'left')
            ->join('users', 'users.user_id=expenses.uploaded_by', 'left')
            ->join('users AS approver', 'approver.user_id=expenses.approved_by', 'left');

        if($partner_id){
            $this->db->where('expenses.partner_id', $partner_id);
        }

        if($status){
            $this->db->where('expenses.status', $status);
        }

        $this->db->order_by('expenses.created_at', 'DESC');

        $query = $this->db->get('expenses');
        return $query->result_array();
    }

    public function get_expense_by_id($expense_id){
        return $this->db->where('expense_id', $expense_id)->get('expenses')->row_array();
    }

    public function get_expenses_for_report($from_date, $to_date, $partner_id = NULL, $status = NULL){
        $this->db->select('expenses.*, partners.name as Partner,
            (SELECT rwf_per_eur FROM forex_rates WHERE rate_date <= expenses.Date ORDER BY rate_date DESC LIMIT 1) AS forex_rate', FALSE);
        $this->db->from('expenses');
        $this->db->join('partners', 'expenses.partner_id = partners.partner_id', 'left');
        $this->db->where('expenses.Date >=', $from_date);
        $this->db->where('expenses.Date <=', $to_date);

        if(!empty($partner_id)){
            $this->db->where('expenses.partner_id', $partner_id);
        }

        if(!empty($status)){
            $this->db->where('expenses.status', $status);
        }

        $this->db->order_by('expenses.Date', 'ASC');
        return $this->db->get()->result_array();
    }

    public function save_expense($data){
        $this->db->insert('expenses', $data);
        return $this->db->insert_id();
    }

    // Alias for save_expense (called from controller)
    public function create_expense($data){
        return $this->save_expense($data);
    }

    public function update_expense($expense_id, $data){
        $this->db->where('expense_id', $expense_id);
        return $this->db->update('expenses', $data);
    }

    public function approve_expense($expense_id, $approver_id, $comments = '', $signature_data = null){
        $data = array(
            'status' => 'approved',
            'approved_by' => $approver_id,
            'approved_at' => date('Y-m-d H:i:s')
        );
        // Add comments if provided and field exists in table
        if(!empty($comments)){
            $data['approval_comments'] = $comments;
        }
        // Add signature data if provided
        if(!empty($signature_data)){
            if(isset($signature_data['signature_name'])){
                $data['approver_signature_name'] = $signature_data['signature_name'];
            }
            if(isset($signature_data['position'])){
                $data['approver_signature_position'] = $signature_data['position'];
            }
            if(isset($signature_data['organization'])){
                $data['approver_signature_organization'] = $signature_data['organization'];
            }
            if(isset($signature_data['signature_file'])){
                $data['approver_signature_file'] = $signature_data['signature_file'];
            }
        }
        return $this->update_expense($expense_id, $data);
    }

    public function reject_expense($expense_id, $rejection_comments = ''){
        $data = array(
            'status' => 'rejected',
            'rejected_at' => date('Y-m-d H:i:s')
        );
        // Add rejection comments if provided
        if(!empty($rejection_comments)){
            $data['rejection_comments'] = $rejection_comments;
        }
        return $this->update_expense($expense_id, $data);
    }

    // ============================================
    // TIMESHEET MANAGEMENT
    // ============================================

    public function get_all_timesheets($user_id = NULL, $partner_id = NULL, $status = NULL){
        $this->db->select('timesheets.*,
            users.email AS member_email,
            staff.first_name,
            staff.last_name,
            partners.name AS partner_name,
            approver.email AS approved_by_email')
            ->join('users', 'users.user_id=timesheets.user_id')
            ->join('staff', 'staff.staff_id=users.staff_id')
            ->join('partners', 'partners.partner_id=timesheets.partner_id', 'left')
            ->join('users AS approver', 'approver.user_id=timesheets.approved_by', 'left');

        if($user_id){
            $this->db->where('timesheets.user_id', $user_id);
        }

        if($partner_id){
            $this->db->where('timesheets.partner_id', $partner_id);
        }

        if($status){
            $this->db->where('timesheets.status', $status);
        }

        $this->db->order_by('timesheets.year', 'DESC');
        $this->db->order_by('timesheets.month', 'DESC');

        $query = $this->db->get('timesheets');
        return $query->result_array();
    }

    public function get_timesheets_for_report($from_year, $from_month, $to_year, $to_month, $partner_id = NULL, $status = NULL){
        $from_num = (int)$from_year * 100 + (int)$from_month;
        $to_num   = (int)$to_year   * 100 + (int)$to_month;

        $this->db->select('timesheets.*,
            staff.first_name,
            staff.last_name,
            partners.name AS partner_name')
            ->join('users',   'users.user_id = timesheets.user_id')
            ->join('staff',   'staff.staff_id = users.staff_id')
            ->join('partners','partners.partner_id = timesheets.partner_id', 'left');

        $this->db->where("(timesheets.year * 100 + timesheets.month) >= " . $from_num);
        $this->db->where("(timesheets.year * 100 + timesheets.month) <= " . $to_num);

        if($partner_id){
            $this->db->where('timesheets.partner_id', $partner_id);
        }
        if($status){
            $this->db->where('timesheets.status', $status);
        }

        $this->db->order_by('timesheets.year',  'ASC');
        $this->db->order_by('timesheets.month', 'ASC');
        $this->db->order_by('staff.last_name',  'ASC');

        return $this->db->get('timesheets')->result_array();
    }

    public function get_timesheet_by_id($timesheet_id){
        return $this->db->select('timesheets.*,
            users.email,
            staff.first_name,
            staff.last_name,
            partners.name AS partner_name')
            ->join('users', 'users.user_id=timesheets.user_id')
            ->join('staff', 'staff.staff_id=users.staff_id')
            ->join('partners', 'partners.partner_id=timesheets.partner_id', 'left')
            ->where('timesheet_id', $timesheet_id)
            ->get('timesheets')->row_array();
    }

    public function create_timesheet($data){
        $this->db->insert('timesheets', $data);
        return $this->db->insert_id();
    }

    public function save_timesheet($data){
        $this->db->insert('timesheets', $data);
        return $this->db->insert_id();
    }

    public function get_timesheet_by_month($user_id, $year, $month){
        return $this->db->where('user_id', $user_id)
                       ->where('year', $year)
                       ->where('month', $month)
                       ->get('timesheets')->row_array();
    }

    public function update_timesheet($timesheet_id, $data){
        $this->db->where('timesheet_id', $timesheet_id);
        return $this->db->update('timesheets', $data);
    }

    public function submit_timesheet($timesheet_id){
        $data = array(
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s')
        );
        return $this->update_timesheet($timesheet_id, $data);
    }

    public function approve_timesheet($timesheet_id, $approver_id, $comments = NULL, $signature_data = null){
        $data = array(
            'status' => 'approved',
            'approved_by' => $approver_id,
            'approved_at' => date('Y-m-d H:i:s')
        );

        if($comments){
            $data['comments'] = $comments;
        }

        // Add signature data if provided
        if(!empty($signature_data)){
            if(isset($signature_data['signature_name'])){
                $data['approver_signature_name'] = $signature_data['signature_name'];
            }
            if(isset($signature_data['position'])){
                $data['approver_signature_position'] = $signature_data['position'];
            }
            if(isset($signature_data['organization'])){
                $data['approver_signature_organization'] = $signature_data['organization'];
            }
            if(isset($signature_data['signature_file'])){
                $data['approver_signature_file'] = $signature_data['signature_file'];
            }
        }

        return $this->update_timesheet($timesheet_id, $data);
    }

    public function reject_timesheet($timesheet_id, $comments){
        $data = array(
            'status' => 'rejected',
            'comments' => $comments,
            'rejected_at' => date('Y-m-d H:i:s')
        );
        return $this->update_timesheet($timesheet_id, $data);
    }

    public function get_timesheet_details($timesheet_id){
        return $this->db->where('timesheet_id', $timesheet_id)
                       ->order_by('date', 'ASC')
                       ->get('timesheet_details')->result_array();
    }

    public function save_timesheet_detail($data){
        $this->db->insert('timesheet_details', $data);
        return $this->db->insert_id();
    }

    public function delete_timesheet_details($timesheet_id){
        return $this->db->where('timesheet_id', $timesheet_id)
                       ->delete('timesheet_details');
    }

    public function get_timesheet_work_package_summary($timesheet_id){
        // Get summary of hours per work package
        $this->db->select('work_package, SUM(hours) as total_hours')
                ->where('timesheet_id', $timesheet_id)
                ->group_by('work_package')
                ->order_by('work_package', 'ASC');

        return $this->db->get('timesheet_details')->result_array();
    }

    public function calculate_timesheet_total_hours($timesheet_id){
        // Calculate total hours from all daily entries
        $result = $this->db->select('SUM(hours) as total')
                          ->where('timesheet_id', $timesheet_id)
                          ->get('timesheet_details')
                          ->row_array();

        return $result['total'] ? $result['total'] : 0;
    }

    // ============================================
    // PARTNERS/INSTITUTIONS MANAGEMENT
    // ============================================

    public function get_all_partners($status = 'active'){
        $this->db->select('*');
        if($status){
            $this->db->where('status', $status);
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get('partners')->result_array();
    }

    public function get_partner_by_id($partner_id){
        return $this->db->where('partner_id', $partner_id)->get('partners')->row_array();
    }

    public function save_partner($data){
        $this->db->insert('partners', $data);
        return $this->db->insert_id();
    }

    public function update_partner($partner_id, $data){
        $this->db->where('partner_id', $partner_id);
        return $this->db->update('partners', $data);
    }

    // ============================================
    // ROLES & PERMISSIONS
    // ============================================

    public function get_all_roles(){
        return $this->db->get('roles')->result_array();
    }

    public function get_role_by_id($role_id){
        return $this->db->where('role_id', $role_id)->get('roles')->row_array();
    }

    public function check_permission($user_id, $permission){
        $user = $this->get_user_by_id($user_id);
        if(!$user) return FALSE;

        $role = $this->get_role_by_id($user['role_id']);
        if(!$role) return FALSE;

        $permissions = json_decode($role['permissions'], TRUE);

        // Super admin has all permissions
        if(isset($permissions['all']) && $permissions['all'] === TRUE){
            return TRUE;
        }

        return isset($permissions[$permission]) && $permissions[$permission] === TRUE;
    }

    // ============================================
    // AUDIT LOG
    // ============================================

    public function log_activity($user_id, $action, $entity_type = NULL, $entity_id = NULL, $description = NULL){
        $data = array(
            'user_id' => $user_id,
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'description' => $description,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        );
        $this->db->insert('audit_log', $data);
    }

    public function get_audit_logs($user_id = NULL, $limit = 100){
        $this->db->select('audit_log.*, users.email')
                 ->join('users', 'users.user_id=audit_log.user_id', 'left')
                 ->order_by('created_at', 'DESC')
                 ->limit($limit);

        if($user_id){
            $this->db->where('audit_log.user_id', $user_id);
        }

        return $this->db->get('audit_log')->result_array();
    }

    // ============================================
    // NOTIFICATIONS
    // ============================================

    public function create_notification($user_id, $title, $message, $type = 'info', $entity_type = NULL, $entity_id = NULL){
        $data = array(
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id
        );
        $this->db->insert('notifications', $data);
        return $this->db->insert_id();
    }

    public function get_unread_notifications($user_id){
        return $this->db->where('user_id', $user_id)
                       ->where('is_read', 0)
                       ->order_by('created_at', 'DESC')
                       ->get('notifications')->result_array();
    }

    public function mark_notification_read($notification_id){
        $data = array(
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('notification_id', $notification_id);
        return $this->db->update('notifications', $data);
    }

    // ============================================
    // REPORTS & STATISTICS
    // ============================================

    public function get_expense_summary($partner_id = NULL, $start_date = NULL, $end_date = NULL){
        $this->db->select('
            COUNT(*) as total_expenses,
            SUM(Amount) as total_amount,
            Currency,
            Category,
            WorkPackage')
            ->group_by(array('Currency', 'Category', 'WorkPackage'));

        if($partner_id){
            $this->db->where('partner_id', $partner_id);
        }

        if($start_date){
            $this->db->where('Date >=', $start_date);
        }

        if($end_date){
            $this->db->where('Date <=', $end_date);
        }

        return $this->db->get('expenses')->result_array();
    }

    public function get_timesheet_summary($partner_id = NULL, $year = NULL){
        $this->db->select('
            COUNT(*) as total_timesheets,
            SUM(total_hours) as total_hours,
            status,
            month')
            ->group_by(array('status', 'month'));

        if($partner_id){
            $this->db->where('partner_id', $partner_id);
        }

        if($year){
            $this->db->where('year', $year);
        }

        return $this->db->get('timesheets')->result_array();
    }

    // ============================================
    // SYSTEM SETTINGS
    // ============================================

    public function get_setting($key){
        $setting = $this->db->where('setting_key', $key)->get('system_settings')->row_array();
        return $setting ? $setting['setting_value'] : NULL;
    }

    public function update_setting($key, $value){
        $this->db->where('setting_key', $key);
        return $this->db->update('system_settings', array('setting_value' => $value));
    }

    // ============================================
    // MONTHLY FINANCIAL REPORTS
    // ============================================

    /**
     * Create a monthly financial report
     * @param int $partner_id
     * @param int $year
     * @param int $month
     * @param int $created_by User creating the report
     * @return int Report ID
     */
    public function create_monthly_report($partner_id, $year, $month, $created_by){
        // Check if report already exists for this month
        $existing = $this->db->select('report_id')
            ->where('partner_id', $partner_id)
            ->where('report_month', $month)
            ->where('report_year', $year)
            ->get('monthly_financial_reports')
            ->row_array();

        if($existing){
            return $existing['report_id']; // Return existing report ID
        }

        // Get partner info
        $partner = $this->db->select('name')->where('partner_id', $partner_id)->get('partners')->row_array();
        $report_name = 'RP_FinancialReport_' . $year . '_' . $this->get_month_name($month);

        // Create new report record in draft status
        $data = array(
            'partner_id' => $partner_id,
            'report_month' => $month,
            'report_year' => $year,
            'report_name' => $report_name,
            'description' => '',
            'total_items' => 0,
            'status' => 'draft',
            'created_by' => $created_by,
            'created_at' => date('Y-m-d H:i:s')
        );

        if($this->db->insert('monthly_financial_reports', $data)){
            $report_id = $this->db->insert_id();

            // Create summary record
            $summary = array(
                'report_id' => $report_id,
                'total_items' => 0,
                'total_verified' => 0
            );
            $this->db->insert('monthly_report_summary', $summary);

            return $report_id;
        }

        return FALSE;
    }

    /**
     * Add expenses to monthly report
     */
    private function add_expenses_to_report($report_id, $expenses){
        foreach($expenses as $expense){
            $item = array(
                'report_id' => $report_id,
                'expense_id' => $expense['expense_id'],
                'category' => $expense['Category'],
                'work_package' => $expense['WorkPackage'],
                'currency' => $expense['Currency'],
                'amount' => $expense['Amount'],
                'description' => $expense['ShortDescription'],
                'expense_date' => $expense['Date'],
                'uploaded_by' => $expense['uploaded_by']
            );
            $this->db->insert('monthly_report_items', $item);
        }
    }

    /**
     * Calculate summary data for report
     */
    private function calculate_report_summaries($report_id){
        // Get report details
        $report = $this->db->where('report_id', $report_id)->get('monthly_financial_reports')->row_array();
        $total_amount = $report['total_amount_rwf'] + $report['total_amount_eur'] + $report['total_amount_usd'];

        // Summary by Work Package
        $wp_summary = $this->db->select('work_package, COUNT(*) as expense_count, SUM(amount) as total_amount')
            ->where('report_id', $report_id)
            ->group_by('work_package')
            ->get('monthly_report_items')
            ->result_array();

        foreach($wp_summary as $wp){
            $summary = array(
                'report_id' => $report_id,
                'work_package' => $wp['work_package'],
                'expense_count' => $wp['expense_count'],
                'total_amount' => $wp['total_amount'],
                'percentage' => ($total_amount > 0) ? ($wp['total_amount'] / $total_amount * 100) : 0
            );
            $this->db->insert('monthly_report_summary_wp', $summary);
        }

        // Summary by Category
        $cat_summary = $this->db->select('category, COUNT(*) as expense_count, SUM(amount) as total_amount')
            ->where('report_id', $report_id)
            ->group_by('category')
            ->get('monthly_report_items')
            ->result_array();

        foreach($cat_summary as $cat){
            $summary = array(
                'report_id' => $report_id,
                'category' => $cat['category'],
                'expense_count' => $cat['expense_count'],
                'total_amount' => $cat['total_amount'],
                'percentage' => ($total_amount > 0) ? ($cat['total_amount'] / $total_amount * 100) : 0
            );
            $this->db->insert('monthly_report_summary_category', $summary);
        }

        // Summary by Currency (already in main report table)
        $currency_summary = array(
            array('report_id' => $report_id, 'currency' => 'RWF', 'total_amount' => $report['total_amount_rwf']),
            array('report_id' => $report_id, 'currency' => 'EUR', 'total_amount' => $report['total_amount_eur']),
            array('report_id' => $report_id, 'currency' => 'USD', 'total_amount' => $report['total_amount_usd'])
        );

        foreach($currency_summary as $curr){
            if($curr['total_amount'] > 0){
                $this->db->insert('monthly_report_summary_currency', $curr);
            }
        }
    }

    /**
     * Get monthly report with all details
     */
    public function get_monthly_report($report_id){
        $report = $this->db->where('report_id', $report_id)->get('monthly_financial_reports')->row_array();
        if(!$report) return FALSE;

        // Get expenses in report
        $report['expenses'] = $this->db->where('report_id', $report_id)->get('monthly_report_items')->result_array();

        // Get work package summary
        $report['wp_summary'] = $this->db->where('report_id', $report_id)->get('monthly_report_summary_wp')->result_array();

        // Get category summary
        $report['category_summary'] = $this->db->where('report_id', $report_id)->get('monthly_report_summary_category')->result_array();

        // Get currency summary
        $report['currency_summary'] = $this->db->where('report_id', $report_id)->get('monthly_report_summary_currency')->result_array();

        return $report;
    }

    /**
     * Get all monthly reports for a partner
     */
    public function get_partner_monthly_reports($partner_id, $status = NULL){
        $query = $this->db->where('partner_id', $partner_id);
        if($status){
            $query = $query->where('status', $status);
        }
        return $query->order_by('report_year DESC, report_month DESC')->get('monthly_financial_reports')->result_array();
    }

    /**
     * Submit monthly report for approval
     */
    public function submit_monthly_report($report_id, $submitted_by){
        $data = array(
            'status' => 'submitted',
            'submitted_by' => $submitted_by,
            'submitted_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('report_id', $report_id);
        return $this->db->update('monthly_financial_reports', $data);
    }

    /**
     * Approve monthly report
     */
    public function approve_monthly_report($report_id, $approved_by, $notes = ''){
        $data = array(
            'status' => 'approved',
            'approved_by' => $approved_by,
            'approved_at' => date('Y-m-d H:i:s'),
            'notes' => $notes
        );
        $this->db->where('report_id', $report_id);
        return $this->db->update('monthly_financial_reports', $data);
    }

    /**
     * Reject monthly report
     */
    public function reject_monthly_report($report_id, $rejection_comments = ''){
        $data = array(
            'status' => 'rejected',
            'rejection_comments' => $rejection_comments,
            'rejected_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('report_id', $report_id);
        return $this->db->update('monthly_financial_reports', $data);
    }

    /**
     * Helper: Get currency totals from expenses
     */
    private function get_currency_totals($expenses){
        $totals = array('RWF' => 0, 'EUR' => 0, 'USD' => 0);
        foreach($expenses as $expense){
            $currency = $expense['Currency'];
            if(isset($totals[$currency])){
                $totals[$currency] += $expense['Amount'];
            }
        }
        return $totals;
    }

    /**
     * Helper: Convert month number to name
     */
    private function get_month_name($month){
        $months = array(
            1 => 'JANUARY', 2 => 'FEBRUARY', 3 => 'MARCH', 4 => 'APRIL',
            5 => 'MAY', 6 => 'JUNE', 7 => 'JULY', 8 => 'AUGUST',
            9 => 'SEPTEMBER', 10 => 'OCTOBER', 11 => 'NOVEMBER', 12 => 'DECEMBER'
        );
        return $months[$month] ?? 'UNKNOWN';
    }

    /**
     * Add attachment to monthly report
     */
    public function add_report_attachment($report_id, $original_filename, $saved_filename, $file_path, $file_size, $file_type, $item_data, $uploaded_by){
        $attachment = array(
            'report_id' => $report_id,
            'original_filename' => $original_filename,
            'saved_filename' => $saved_filename,
            'file_path' => $file_path,
            'file_size' => $file_size,
            'file_type' => $file_type,
            'item_name' => isset($item_data['item_name']) ? $item_data['item_name'] : '',
            'item_description' => isset($item_data['item_description']) ? $item_data['item_description'] : '',
            'item_type' => isset($item_data['item_type']) ? $item_data['item_type'] : '',
            'document_date' => isset($item_data['document_date']) ? $item_data['document_date'] : NULL,
            'amount' => isset($item_data['amount']) ? $item_data['amount'] : 0,
            'currency' => isset($item_data['currency']) ? $item_data['currency'] : 'RWF',
            'category' => isset($item_data['category']) ? $item_data['category'] : '',
            'work_package' => isset($item_data['work_package']) ? $item_data['work_package'] : '',
            'uploaded_by' => $uploaded_by,
            'uploaded_at' => date('Y-m-d H:i:s')
        );
        if($this->db->insert('monthly_report_attachments', $attachment)){
            $attachment_id = $this->db->insert_id();
            $this->recalculate_report_summary($report_id);
            return $attachment_id;
        }
        return FALSE;
    }

    /**
     * Delete attachment from report
     */
    public function delete_report_attachment($attachment_id, $report_id){
        if($this->db->delete('monthly_report_attachments', array('attachment_id' => $attachment_id))){
            $this->recalculate_report_summary($report_id);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Update attachment metadata
     */
    public function update_report_attachment($attachment_id, $update_data){
        $att = $this->db->select('report_id')->where('attachment_id', $attachment_id)->get('monthly_report_attachments')->row_array();
        if(!$att) return FALSE;
        if($this->db->update('monthly_report_attachments', $update_data, array('attachment_id' => $attachment_id))){
            $this->recalculate_report_summary($att['report_id']);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Verify attachment (admin)
     */
    public function verify_report_attachment($attachment_id, $verified_by, $notes = ''){
        $att = $this->db->select('report_id')->where('attachment_id', $attachment_id)->get('monthly_report_attachments')->row_array();
        if(!$att) return FALSE;
        $data = array(
            'verified' => TRUE,
            'verified_by' => $verified_by,
            'verified_at' => date('Y-m-d H:i:s'),
            'verification_notes' => $notes
        );
        if($this->db->update('monthly_report_attachments', $data, array('attachment_id' => $attachment_id))){
            $this->recalculate_report_summary($att['report_id']);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Unverify attachment
     */
    public function unverify_report_attachment($attachment_id){
        $att = $this->db->select('report_id')->where('attachment_id', $attachment_id)->get('monthly_report_attachments')->row_array();
        if(!$att) return FALSE;
        $data = array(
            'verified' => FALSE,
            'verified_by' => NULL,
            'verified_at' => NULL,
            'verification_notes' => NULL
        );
        if($this->db->update('monthly_report_attachments', $data, array('attachment_id' => $attachment_id))){
            $this->recalculate_report_summary($att['report_id']);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Recalculate report summary (auto-totals)
     */
    public function recalculate_report_summary($report_id){
        $attachments = $this->db->select('*')->where('report_id', $report_id)->get('monthly_report_attachments')->result_array();

        $totals = array(
            'total_items' => count($attachments),
            'total_verified' => 0,
            'total_amount_rwf' => 0,
            'total_amount_eur' => 0,
            'total_amount_usd' => 0
        );

        $category_breakdown = array();
        $wp_breakdown = array();
        $currency_breakdown = array('RWF' => 0, 'EUR' => 0, 'USD' => 0);

        foreach($attachments as $att){
            if($att['verified']) $totals['total_verified']++;

            if($att['currency'] && $att['amount'] > 0){
                $key = 'total_amount_' . strtolower($att['currency']);
                if(isset($totals[$key])) $totals[$key] += $att['amount'];
                $currency_breakdown[$att['currency']] += $att['amount'];
            }

            if($att['category']){
                if(!isset($category_breakdown[$att['category']])){
                    $category_breakdown[$att['category']] = array('count' => 0, 'total' => 0);
                }
                $category_breakdown[$att['category']]['count']++;
                $category_breakdown[$att['category']]['total'] += $att['amount'];
            }

            if($att['work_package']){
                if(!isset($wp_breakdown[$att['work_package']])){
                    $wp_breakdown[$att['work_package']] = array('count' => 0, 'total' => 0);
                }
                $wp_breakdown[$att['work_package']]['count']++;
                $wp_breakdown[$att['work_package']]['total'] += $att['amount'];
            }
        }

        $this->db->update('monthly_report_summary', $totals, array('report_id' => $report_id));

        $this->db->delete('monthly_report_category_summary', array('report_id' => $report_id));
        foreach($category_breakdown as $category => $data){
            $this->db->insert('monthly_report_category_summary', array(
                'report_id' => $report_id,
                'category' => $category,
                'item_count' => $data['count'],
                'total_amount' => $data['total']
            ));
        }

        $this->db->delete('monthly_report_wp_summary', array('report_id' => $report_id));
        foreach($wp_breakdown as $wp => $data){
            $this->db->insert('monthly_report_wp_summary', array(
                'report_id' => $report_id,
                'work_package' => $wp,
                'item_count' => $data['count'],
                'total_amount' => $data['total']
            ));
        }

        $this->db->delete('monthly_report_currency_summary', array('report_id' => $report_id));
        foreach($currency_breakdown as $currency => $total){
            if($total > 0){
                $this->db->insert('monthly_report_currency_summary', array(
                    'report_id' => $report_id,
                    'currency' => $currency,
                    'total_amount' => $total,
                    'item_count' => 0
                ));
            }
        }

        return TRUE;
    }

    /**
     * Generate PDF for report
     */
    public function generate_report_pdf($report_id){
        $report = $this->get_monthly_report($report_id);
        if(!$report) return FALSE;
        return 'assets/uploads/reports/' . $report['report_name'] . '.pdf';
    }

    /**
     * Generate Excel for report
     */
    public function generate_report_excel($report_id){
        $report = $this->get_monthly_report($report_id);
        if(!$report) return FALSE;
        return 'assets/uploads/reports/' . $report['report_name'] . '.xlsx';
    }

    // ==================== SIGNATURE MANAGEMENT ====================

    /**
     * Get admin users who can approve reports (for signature configuration)
     */
    public function get_admin_users_for_signatures(){
        $this->db->select('users.user_id, staff.first_name, staff.last_name, staff.position, roles.role_name');
        $this->db->from('users');
        $this->db->join('staff', 'users.staff_id = staff.staff_id', 'left');
        $this->db->join('roles', 'users.role_id = roles.role_id', 'left');
        $this->db->where_in('roles.role_id', [1, 2, 3]); // Super Admin, Admin, Coordinator
        $this->db->where('users.status', 'active');
        $this->db->order_by('staff.first_name', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Save or update a system setting
     */
    public function save_setting($key, $value, $type = 'string', $description = ''){
        // Check if setting exists
        $this->db->where('setting_key', $key);
        $existing = $this->db->get('system_settings')->row_array();

        if($existing){
            // Update existing setting
            $data = [
                'setting_value' => $value,
                'setting_type' => $type,
                'description' => $description
            ];
            $this->db->where('setting_key', $key);
            return $this->db->update('system_settings', $data);
        } else {
            // Insert new setting
            $data = [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_type' => $type,
                'description' => $description
            ];
            return $this->db->insert('system_settings', $data);
        }
    }

    /**
     * Get a system setting with JSON decoding support
     */
    public function get_setting_with_type($key){
        $this->db->where('setting_key', $key);
        $result = $this->db->get('system_settings')->row_array();

        if($result){
            // Decode JSON if setting type is json
            if($result['setting_type'] == 'json'){
                $result['value_decoded'] = json_decode($result['setting_value'], true);
            }
            return $result;
        }

        return null;
    }

    /**
     * Get all configured signatures
     */
    public function get_all_signatures(){
        $this->db->like('setting_key', 'report_signature_', 'after');
        $settings = $this->db->get('system_settings')->result_array();

        $signatures = [];
        foreach($settings as $setting){
            $sig_data = json_decode($setting['setting_value'], true);
            if($sig_data && isset($sig_data['user_id'])){
                // Get user name
                $user = $this->get_user_by_id($sig_data['user_id']);
                $sig_data['user_name'] = $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Unknown';
                $sig_data['updated_at'] = $setting['updated_at'];
                $signatures[] = $sig_data;
            }
        }

        return $signatures;
    }

    /**
     * Get signature for a specific user
     */
    public function get_signature_by_user_id($user_id){
        $key = 'report_signature_' . $user_id;
        $setting = $this->get_setting_with_type($key);

        if($setting && isset($setting['value_decoded'])){
            return $setting['value_decoded'];
        }

        return null;
    }

    // ==================== END SIGNATURE MANAGEMENT ====================

    // ============================================================
    // OTHER FILES – WORK PACKAGES, FILES & VERSIONS
    // ============================================================

    /**
     * Return all 7 work packages, annotated with total file count
     * and the partner's file count when partner_id is supplied.
     */
    public function get_work_packages($partner_id = NULL, $uploaded_by = NULL){
        $wps = $this->db->order_by('wp_id', 'ASC')->get('work_packages')->result_array();

        foreach($wps as &$wp){
            $this->db->where('other_files.wp_id', $wp['wp_id']);
            if($partner_id){
                $this->db->where('other_files.partner_id', $partner_id);
            }
            if($uploaded_by){
                $this->db->where('other_files.uploaded_by', $uploaded_by);
            }
            $wp['file_count'] = $this->db->count_all_results('other_files');
        }
        unset($wp);

        return $wps;
    }

    /**
     * Return a single work package by id.
     */
    public function get_work_package_by_id($wp_id){
        return $this->db->where('wp_id', $wp_id)->get('work_packages')->row_array();
    }

    /**
     * Return files (one row per file group) for a given WP.
     * - Super Admin / Admin → all files for all partners
     * - Coordinator  → only files uploaded by anyone at their partner
     * - Filter by uploaded_by to restrict to own uploads
     */
    public function get_files_by_wp($wp_id, $partner_id = NULL, $uploaded_by = NULL){
        $wp_id  = (int)$wp_id;
        $where  = 'WHERE ofl.wp_id = ' . $wp_id;

        if($partner_id){
            $where .= ' AND ofl.partner_id = ' . (int)$partner_id;
        }
        if($uploaded_by){
            $where .= ' AND ofl.uploaded_by = ' . (int)$uploaded_by;
        }

        $sql = "
            SELECT
                ofl.*,
                p.short_name  AS partner_short_name,
                p.name        AS partner_name,
                CONCAT(s.first_name,' ',s.last_name) AS uploader_name,
                ofv.version_number AS latest_version,
                ofv.stored_name    AS latest_stored_name,
                ofv.created_at     AS latest_upload_at,
                ofv.version_id     AS latest_version_id
            FROM other_files ofl
            LEFT JOIN partners p  ON p.partner_id  = ofl.partner_id
            LEFT JOIN users u     ON u.user_id      = ofl.uploaded_by
            LEFT JOIN staff s     ON s.staff_id     = u.staff_id
            LEFT JOIN other_file_versions ofv
                ON ofv.version_id = (
                    SELECT v2.version_id FROM other_file_versions v2
                    WHERE v2.file_id = ofl.file_id
                    ORDER BY v2.version_number DESC LIMIT 1
                )
            {$where}
            ORDER BY ofl.created_at DESC
        ";

        return $this->db->query($sql)->result_array();
    }

    /**
     * Return a single file record by id.
     */
    public function get_other_file_by_id($file_id){
        return $this->db
            ->select("other_files.*, partners.short_name AS partner_short_name")
            ->join('partners', 'partners.partner_id = other_files.partner_id', 'left')
            ->where('file_id', $file_id)
            ->get('other_files')
            ->row_array();
    }

    /**
     * Return all versions for a file, newest first, with uploader name.
     */
    public function get_file_versions($file_id){
        return $this->db
            ->select("other_file_versions.*, CONCAT(s.first_name,' ',s.last_name) AS uploader_name")
            ->from('other_file_versions')
            ->join('users u', 'u.user_id = other_file_versions.uploaded_by', 'left')
            ->join('staff s', 's.staff_id = u.staff_id', 'left')
            ->where('other_file_versions.file_id', $file_id)
            ->order_by('other_file_versions.version_number', 'DESC')
            ->get()
            ->result_array();
    }

    /**
     * Return a single version row by version_id.
     */
    public function get_version_by_id($version_id){
        return $this->db->where('version_id', $version_id)
                        ->get('other_file_versions')
                        ->row_array();
    }

    /**
     * Return the next version number for a file (1 if first upload).
     */
    public function get_next_version_number($file_id){
        $row = $this->db
            ->select_max('version_number', 'max_v')
            ->where('file_id', $file_id)
            ->get('other_file_versions')
            ->row_array();
        return isset($row['max_v']) && $row['max_v'] !== NULL ? (int)$row['max_v'] + 1 : 1;
    }

    /**
     * Insert a new file record; returns insert id.
     */
    public function create_other_file($data){
        $this->db->insert('other_files', $data);
        return $this->db->insert_id();
    }

    /**
     * Insert a new version record; returns insert id.
     */
    public function create_file_version($data){
        $this->db->insert('other_file_versions', $data);
        return $this->db->insert_id();
    }

    /**
     * Delete a file and all its versions (cascade handles version rows).
     */
    public function delete_other_file($file_id){
        $this->db->where('file_id', $file_id)->delete('other_files');
        return $this->db->affected_rows() > 0;
    }

    // ==================== END OTHER FILES ====================

    // ============================================================
    // OTHER FILE COMMENTS
    // ============================================================

    public function get_comments_by_file($file_id){
        return $this->db
            ->select("other_file_comments.*, CONCAT(s.first_name,' ',s.last_name) AS commenter_name, roles.role_name")
            ->from('other_file_comments')
            ->join('users u',  'u.user_id  = other_file_comments.user_id', 'left')
            ->join('staff s',  's.staff_id = u.staff_id',                  'left')
            ->join('roles',    'roles.role_id = u.role_id',                'left')
            ->where('other_file_comments.file_id', (int)$file_id)
            ->order_by('other_file_comments.created_at', 'ASC')
            ->get()->result_array();
    }

    public function add_file_comment($data){
        $this->db->insert('other_file_comments', $data);
        return $this->db->insert_id();
    }

    public function delete_file_comment($comment_id){
        $this->db->where('comment_id', (int)$comment_id)->delete('other_file_comments');
        return $this->db->affected_rows() > 0;
    }

    // ==================== END COMMENTS ====================

    // ============================================================
    // DASHBOARD CHART QUERIES
    // ============================================================

    /** Expense counts grouped by status */
    public function get_expenses_by_status($partner_id = NULL){
        $sql = "SELECT status, COUNT(*) AS cnt FROM expenses";
        if($partner_id) $sql .= " WHERE partner_id = " . (int)$partner_id;
        $sql .= " GROUP BY status";
        $rows = $this->db->query($sql)->result_array();
        $out = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach($rows as $r) $out[$r['status']] = (int)$r['cnt'];
        return $out;
    }

    /** Expense counts grouped by partner (Super Admin) */
    public function get_expenses_by_partner(){
        $rows = $this->db->query("
            SELECT p.short_name, COUNT(e.expense_id) AS cnt
            FROM partners p
            LEFT JOIN expenses e ON e.partner_id = p.partner_id
            GROUP BY p.partner_id, p.short_name
            ORDER BY p.short_name
        ")->result_array();
        return $rows;
    }

    /** Expense counts grouped by Work Package */
    public function get_expenses_by_wp($partner_id = NULL){
        $sql = "SELECT WorkPackage, COUNT(*) AS cnt FROM expenses WHERE WorkPackage IS NOT NULL";
        if($partner_id) $sql .= " AND partner_id = " . (int)$partner_id;
        $sql .= " GROUP BY WorkPackage ORDER BY WorkPackage";
        return $this->db->query($sql)->result_array();
    }

    /** Timesheet counts grouped by status */
    public function get_timesheets_by_status($partner_id = NULL, $user_id = NULL){
        $sql = "SELECT status, COUNT(*) AS cnt FROM timesheets WHERE 1=1";
        if($partner_id) $sql .= " AND partner_id = " . (int)$partner_id;
        if($user_id)    $sql .= " AND user_id = "    . (int)$user_id;
        $sql .= " GROUP BY status";
        $rows = $this->db->query($sql)->result_array();
        $out = ['draft' => 0, 'submitted' => 0, 'approved' => 0, 'rejected' => 0];
        foreach($rows as $r) if(isset($out[$r['status']])) $out[$r['status']] = (int)$r['cnt'];
        return $out;
    }

    /** Other-file counts grouped by WP (Super Admin / Coordinator) */
    public function get_other_files_by_wp($partner_id = NULL, $uploaded_by = NULL){
        $sql = "
            SELECT wp.code, COUNT(ofl.file_id) AS cnt
            FROM work_packages wp
            LEFT JOIN other_files ofl ON ofl.wp_id = wp.wp_id";
        $where = [];
        if($partner_id)  $where[] = "ofl.partner_id  = " . (int)$partner_id;
        if($uploaded_by) $where[] = "ofl.uploaded_by = " . (int)$uploaded_by;
        if($where) $sql .= " AND " . implode(' AND ', $where);
        $sql .= " GROUP BY wp.wp_id, wp.code ORDER BY wp.wp_id";
        return $this->db->query($sql)->result_array();
    }

    /** Total Other Files count */
    public function count_other_files($partner_id = NULL, $uploaded_by = NULL){
        $sql = "SELECT COUNT(*) AS cnt FROM other_files WHERE 1=1";
        if($partner_id)  $sql .= " AND partner_id  = " . (int)$partner_id;
        if($uploaded_by) $sql .= " AND uploaded_by = " . (int)$uploaded_by;
        $row = $this->db->query($sql)->row_array();
        return (int)($row['cnt'] ?? 0);
    }

    // ==================== END DASHBOARD CHARTS ====================

    // ============================================
    // FOREX EXCHANGE RATES
    // ============================================

    public function get_all_forex_rates(){
        return $this->db->query("
            SELECT f.*, CONCAT(u.first_name,' ',u.last_name) AS added_by_name
            FROM forex_rates f
            LEFT JOIN users u2 ON u2.user_id = f.created_by
            LEFT JOIN staff u ON u.staff_id = u2.staff_id
            ORDER BY f.rate_date DESC
        ")->result_array();
    }

    public function get_forex_rate_by_date($date){
        return $this->db->query(
            "SELECT * FROM forex_rates WHERE rate_date = ?",
            [$date]
        )->row_array();
    }

    public function save_forex_rate($date, $rate, $user_id){
        $existing = $this->get_forex_rate_by_date($date);
        if($existing){
            return $this->db->query(
                "UPDATE forex_rates SET rwf_per_eur=?, created_by=?, created_at=NOW() WHERE rate_date=?",
                [(float)$rate, (int)$user_id, $date]
            );
        }
        return $this->db->query(
            "INSERT INTO forex_rates (rate_date, rwf_per_eur, created_by) VALUES (?,?,?)",
            [$date, (float)$rate, (int)$user_id]
        );
    }

    public function bulk_save_forex_rates($rows, $user_id){
        $inserted = 0; $updated = 0; $errors = [];
        foreach($rows as $row){
            $date = $row['date'];
            $rate = $row['rate'];
            if(!$date || !is_numeric($rate) || $rate <= 0){ $errors[] = $date ?: '(empty date)'; continue; }
            $existing = $this->get_forex_rate_by_date($date);
            if($existing){
                $this->db->query("UPDATE forex_rates SET rwf_per_eur=?, created_by=?, created_at=NOW() WHERE rate_date=?", [(float)$rate, (int)$user_id, $date]);
                $updated++;
            } else {
                $this->db->query("INSERT INTO forex_rates (rate_date, rwf_per_eur, created_by) VALUES (?,?,?)", [$date, (float)$rate, (int)$user_id]);
                $inserted++;
            }
        }
        return ['inserted' => $inserted, 'updated' => $updated, 'errors' => $errors];
    }

    public function delete_forex_rate($id){
        return $this->db->query("DELETE FROM forex_rates WHERE id=?", [(int)$id]);
    }

    public function get_forex_stats(){
        $row = $this->db->query("
            SELECT COUNT(*) AS total,
                   MIN(rate_date) AS earliest,
                   MAX(rate_date) AS latest,
                   (SELECT rwf_per_eur FROM forex_rates ORDER BY rate_date DESC LIMIT 1) AS last_rate
            FROM forex_rates
        ")->row_array();
        return $row;
    }

    // ==================== END FOREX ====================
}
