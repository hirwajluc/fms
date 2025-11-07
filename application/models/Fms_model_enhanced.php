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

    public function create_staff($staff_data){
        $this->db->insert('staff', $staff_data);
        return $this->db->insert_id();
    }

    public function update_staff($staff_id, $staff_data){
        $this->db->where('staff_id', $staff_id);
        return $this->db->update('staff', $staff_data);
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
            approver.email AS approved_by_email')
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

    public function approve_expense($expense_id, $approver_id, $comments = ''){
        $data = array(
            'status' => 'approved',
            'approved_by' => $approver_id,
            'approved_at' => date('Y-m-d H:i:s')
        );
        // Add comments if provided and field exists in table
        if(!empty($comments)){
            $data['approval_comments'] = $comments;
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

    public function approve_timesheet($timesheet_id, $approver_id, $comments = NULL){
        $data = array(
            'status' => 'approved',
            'approved_by' => $approver_id,
            'approved_at' => date('Y-m-d H:i:s')
        );

        if($comments){
            $data['comments'] = $comments;
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
}
