<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * FMS_Mailer — centralised email notifications for GREATER FMS.
 *
 * Usage (from any controller):
 *   $this->fms_mailer->account_created($to_email, $full_name, $plain_password);
 *   $this->fms_mailer->timesheet_submitted($ts, $submitter_name, $recipient_emails);
 *   … etc.
 */
class Fms_mailer {

    protected $CI;

    public function __construct(){
        $this->CI =& get_instance();
        $this->CI->load->library('email');
        $this->CI->config->load('email', TRUE);
        $this->CI->email->initialize($this->CI->config->item('email'));
    }

    // ─────────────────────────────────────────────────────────────────
    // PUBLIC NOTIFICATION METHODS
    // ─────────────────────────────────────────────────────────────────

    /** New account welcome email — temporary password, must change on first login */
    public function account_created($to_email, $full_name, $plain_password, $role_name = 'Member'){
        $subject  = 'Welcome to GREATER FMS – Your Account is Ready';
        $app_url  = $this->_app_url();

        $body = $this->_wrap(
            'Welcome to GREATER FMS!',
            '#4CAF50',
            '✓ Account Created',
            "Hello <strong>" . htmlspecialchars($full_name) . "</strong>,<br><br>
             Your account has been created on the <strong>GREATER Financial Management System</strong>.
             Use the temporary password below to log in — you will be asked to set a new password immediately.",
            $this->_info_table([
                'Full Name'         => htmlspecialchars($full_name),
                'Email'             => htmlspecialchars($to_email),
                'Temporary Password'=> '<span style="font-family:monospace;font-size:15px;font-weight:700;letter-spacing:2px;background:#f5f5ff;padding:3px 10px;border-radius:4px;">' . htmlspecialchars($plain_password) . '</span>',
                'Role'              => htmlspecialchars($role_name),
            ]) .
            $this->_alert_box('⚠ This is a temporary password. You must change it on your first login. Do not share it with anyone.', '#FF9800'),
            'Login &amp; Set Your Password',
            $app_url . 'login'
        );

        return $this->_send($to_email, $full_name, $subject, $body);
    }

    /** Password changed voluntarily from profile */
    public function password_changed($to_email, $full_name){
        $subject = 'Your GREATER FMS Password Was Changed';

        $body = $this->_wrap(
            'Password Changed',
            '#696cff',
            '🔒 Security Notice',
            "Hello <strong>" . htmlspecialchars($full_name) . "</strong>,<br><br>
             Your GREATER FMS password was successfully changed on <strong>" . date('d M Y') . " at " . date('H:i') . "</strong>.",
            $this->_alert_box('If you did not make this change, please contact your system administrator immediately and reset your password.', '#e53935'),
            'Go to FMS',
            $this->_app_url() . 'login'
        );

        return $this->_send($to_email, $full_name, $subject, $body);
    }

    /** Timesheet submitted — notify coordinator / admin */
    public function timesheet_submitted($timesheet, $submitter_name, $recipient_emails){
        $subject = 'New Timesheet Submitted – ' . $submitter_name;
        $app_url  = $this->_app_url();
        $period   = $this->_month_name($timesheet['month']) . ' ' . $timesheet['year'];

        $body = $this->_wrap(
            'Timesheet Submitted',
            '#696cff',
            '📋 Timesheet Awaiting Review',
            "A timesheet has been submitted and is awaiting your review.",
            $this->_info_table([
                'Staff Member'   => htmlspecialchars($submitter_name),
                'Period'         => $period,
                'Total Hours'    => number_format($timesheet['total_hours'], 1) . ' hrs',
                'Staff Category' => htmlspecialchars($timesheet['staff_category'] ?? '—'),
                'Submitted On'   => date('d M Y, H:i'),
            ]),
            'Review Timesheet',
            $app_url . 'viewTimesheet/' . $timesheet['timesheet_id']
        );

        foreach((array)$recipient_emails as $email){
            if(filter_var($email, FILTER_VALIDATE_EMAIL)){
                $this->_send($email, '', $subject, $body);
            }
        }
        return true;
    }

    /** Timesheet approved — notify the staff member */
    public function timesheet_approved($timesheet, $staff_email, $staff_name){
        $subject = 'Your Timesheet Has Been Approved – ' . $this->_month_name($timesheet['month']) . ' ' . $timesheet['year'];
        $period  = $this->_month_name($timesheet['month']) . ' ' . $timesheet['year'];

        $body = $this->_wrap(
            'Timesheet Approved',
            '#4CAF50',
            '✓ Timesheet Approved',
            "Hello <strong>" . htmlspecialchars($staff_name) . "</strong>,<br><br>
             Great news! Your timesheet has been <strong style='color:#4CAF50;'>approved</strong>.",
            $this->_info_table([
                'Period'      => $period,
                'Total Hours' => number_format($timesheet['total_hours'], 1) . ' hrs',
                'Status'      => '<span style="color:#4CAF50;font-weight:700;">Approved</span>',
                'Approved On' => date('d M Y, H:i'),
            ]),
            'View Timesheet',
            $this->_app_url() . 'viewTimesheet/' . $timesheet['timesheet_id']
        );

        return $this->_send($staff_email, $staff_name, $subject, $body);
    }

    /** Timesheet rejected — notify the staff member */
    public function timesheet_rejected($timesheet, $staff_email, $staff_name, $reason){
        $subject = 'Your Timesheet Requires Revision – ' . $this->_month_name($timesheet['month']) . ' ' . $timesheet['year'];
        $period  = $this->_month_name($timesheet['month']) . ' ' . $timesheet['year'];

        $body = $this->_wrap(
            'Timesheet Rejected',
            '#e53935',
            '✗ Timesheet Needs Revision',
            "Hello <strong>" . htmlspecialchars($staff_name) . "</strong>,<br><br>
             Your timesheet has been <strong style='color:#e53935;'>returned for revision</strong>.
             Please review the comments below and resubmit.",
            $this->_info_table([
                'Period'      => $period,
                'Total Hours' => number_format($timesheet['total_hours'], 1) . ' hrs',
                'Status'      => '<span style="color:#e53935;font-weight:700;">Returned for Revision</span>',
                'Comments'    => '<em>' . htmlspecialchars($reason) . '</em>',
            ]),
            'Edit &amp; Resubmit',
            $this->_app_url() . 'editTimesheet/' . $timesheet['timesheet_id']
        );

        return $this->_send($staff_email, $staff_name, $subject, $body);
    }

    /** Expense submitted — notify admin / super admin */
    public function expense_submitted($expense, $submitter_name, $recipient_emails){
        $subject = 'New Expense Submitted – ' . htmlspecialchars($expense['FileName'] ?? 'Expense');

        $body = $this->_wrap(
            'Expense Submitted',
            '#696cff',
            '🧾 Expense Awaiting Approval',
            "A new expense record has been submitted and requires your review.",
            $this->_info_table([
                'Submitted By' => htmlspecialchars($submitter_name),
                'File'         => htmlspecialchars($expense['FileName'] ?? '—'),
                'Amount'       => number_format($expense['Amount'], 2) . ' ' . strtoupper($expense['Currency'] ?? ''),
                'Category'     => htmlspecialchars($expense['Category'] ?? '—'),
                'Work Package' => htmlspecialchars($expense['WorkPackage'] ?? '—'),
                'Date'         => !empty($expense['Date']) ? date('d M Y', strtotime($expense['Date'])) : '—',
            ]),
            'Review Expense',
            $this->_app_url() . 'expenses'
        );

        foreach((array)$recipient_emails as $email){
            if(filter_var($email, FILTER_VALIDATE_EMAIL)){
                $this->_send($email, '', $subject, $body);
            }
        }
        return true;
    }

    /** Expense approved — notify the coordinator who submitted it */
    public function expense_approved($expense, $coordinator_email, $coordinator_name){
        $subject = 'Your Expense Has Been Approved';

        $body = $this->_wrap(
            'Expense Approved',
            '#4CAF50',
            '✓ Expense Approved',
            "Hello <strong>" . htmlspecialchars($coordinator_name) . "</strong>,<br><br>
             Your expense submission has been <strong style='color:#4CAF50;'>approved</strong>.",
            $this->_info_table([
                'File'         => htmlspecialchars($expense['FileName'] ?? '—'),
                'Amount'       => number_format($expense['Amount'], 2) . ' ' . strtoupper($expense['Currency'] ?? ''),
                'Category'     => htmlspecialchars($expense['Category'] ?? '—'),
                'Work Package' => htmlspecialchars($expense['WorkPackage'] ?? '—'),
                'Status'       => '<span style="color:#4CAF50;font-weight:700;">Approved</span>',
                'Approved On'  => date('d M Y, H:i'),
            ]),
            'View Expenses',
            $this->_app_url() . 'expenses'
        );

        return $this->_send($coordinator_email, $coordinator_name, $subject, $body);
    }

    /** Expense rejected — notify the coordinator who submitted it */
    public function expense_rejected($expense, $coordinator_email, $coordinator_name, $reason){
        $subject = 'Your Expense Has Been Returned for Revision';

        $body = $this->_wrap(
            'Expense Rejected',
            '#e53935',
            '✗ Expense Needs Revision',
            "Hello <strong>" . htmlspecialchars($coordinator_name) . "</strong>,<br><br>
             Your expense submission has been <strong style='color:#e53935;'>returned for revision</strong>.",
            $this->_info_table([
                'File'         => htmlspecialchars($expense['FileName'] ?? '—'),
                'Amount'       => number_format($expense['Amount'], 2) . ' ' . strtoupper($expense['Currency'] ?? ''),
                'Work Package' => htmlspecialchars($expense['WorkPackage'] ?? '—'),
                'Status'       => '<span style="color:#e53935;font-weight:700;">Returned for Revision</span>',
                'Comments'     => '<em>' . htmlspecialchars($reason) . '</em>',
            ]),
            'View Expenses',
            $this->_app_url() . 'expenses'
        );

        return $this->_send($coordinator_email, $coordinator_name, $subject, $body);
    }

    /** Monthly report submitted — notify admin / super admin */
    public function monthly_report_submitted($report, $submitter_name, $recipient_emails){
        $subject = 'Monthly Report Submitted – ' . $this->_month_name($report['month']) . ' ' . $report['year'];
        $period  = $this->_month_name($report['month']) . ' ' . $report['year'];

        $body = $this->_wrap(
            'Monthly Report Submitted',
            '#696cff',
            '📊 Monthly Report Awaiting Approval',
            "A monthly financial report has been submitted and requires your review.",
            $this->_info_table([
                'Submitted By' => htmlspecialchars($submitter_name),
                'Period'       => $period,
                'Partner'      => htmlspecialchars($report['partner_name'] ?? '—'),
                'Submitted On' => date('d M Y, H:i'),
            ]),
            'Review Report',
            $this->_app_url() . 'viewMonthlyReport/' . $report['report_id']
        );

        foreach((array)$recipient_emails as $email){
            if(filter_var($email, FILTER_VALIDATE_EMAIL)){
                $this->_send($email, '', $subject, $body);
            }
        }
        return true;
    }

    /** Monthly report approved — notify the coordinator */
    public function monthly_report_approved($report, $coordinator_email, $coordinator_name){
        $subject = 'Monthly Report Approved – ' . $this->_month_name($report['month']) . ' ' . $report['year'];
        $period  = $this->_month_name($report['month']) . ' ' . $report['year'];

        $body = $this->_wrap(
            'Monthly Report Approved',
            '#4CAF50',
            '✓ Monthly Report Approved',
            "Hello <strong>" . htmlspecialchars($coordinator_name) . "</strong>,<br><br>
             Your monthly report for <strong>" . $period . "</strong> has been <strong style='color:#4CAF50;'>approved</strong>.",
            $this->_info_table([
                'Period'      => $period,
                'Partner'     => htmlspecialchars($report['partner_name'] ?? '—'),
                'Status'      => '<span style="color:#4CAF50;font-weight:700;">Approved</span>',
                'Approved On' => date('d M Y, H:i'),
            ]),
            'View Report',
            $this->_app_url() . 'viewMonthlyReport/' . $report['report_id']
        );

        return $this->_send($coordinator_email, $coordinator_name, $subject, $body);
    }

    /** Monthly report rejected — notify the coordinator */
    public function monthly_report_rejected($report, $coordinator_email, $coordinator_name, $reason){
        $subject = 'Monthly Report Returned for Revision – ' . $this->_month_name($report['month']) . ' ' . $report['year'];
        $period  = $this->_month_name($report['month']) . ' ' . $report['year'];

        $body = $this->_wrap(
            'Monthly Report Rejected',
            '#e53935',
            '✗ Monthly Report Needs Revision',
            "Hello <strong>" . htmlspecialchars($coordinator_name) . "</strong>,<br><br>
             Your monthly report for <strong>" . $period . "</strong> has been <strong style='color:#e53935;'>returned for revision</strong>.",
            $this->_info_table([
                'Period'   => $period,
                'Partner'  => htmlspecialchars($report['partner_name'] ?? '—'),
                'Status'   => '<span style="color:#e53935;font-weight:700;">Returned for Revision</span>',
                'Comments' => '<em>' . htmlspecialchars($reason) . '</em>',
            ]),
            'Edit Report',
            $this->_app_url() . 'viewMonthlyReport/' . $report['report_id']
        );

        return $this->_send($coordinator_email, $coordinator_name, $subject, $body);
    }

    /**
     * WP file uploaded — notify uploader (confirmation) and super admins.
     *
     * @param string $uploader_email
     * @param string $uploader_name
     * @param array  $file     keys: display_name, wp_name, wp_code, partner_name, version_number, original_filename
     * @param array  $sa_emails  list of super admin email addresses
     */
    public function file_uploaded($uploader_email, $uploader_name, $file, $sa_emails = []){
        $wp_label = htmlspecialchars(($file['wp_code'] ?? '') . ' – ' . ($file['wp_name'] ?? ''));
        $is_new   = ($file['version_number'] == 1);
        $action   = $is_new ? 'New File Uploaded' : 'New Version Uploaded (v' . $file['version_number'] . ')';
        $colour   = $is_new ? '#4CAF50' : '#696cff';

        $info = $this->_info_table([
            'File Name'      => htmlspecialchars($file['display_name']),
            'Original File'  => htmlspecialchars($file['original_filename'] ?? $file['display_name']),
            'Work Package'   => $wp_label,
            'Partner'        => htmlspecialchars($file['partner_name'] ?? '—'),
            'Version'        => 'v' . $file['version_number'],
            'Uploaded On'    => date('d M Y, H:i'),
            'Uploaded By'    => htmlspecialchars($uploader_name),
        ]);

        // ── Confirmation to the uploader ──────────────────────────────
        $uploader_body = $this->_wrap(
            'File Uploaded Successfully',
            $colour,
            '✓ ' . $action,
            "Hello <strong>" . htmlspecialchars($uploader_name) . "</strong>,<br><br>
             Your file has been <strong>uploaded successfully</strong> to the GREATER FMS document repository.",
            $info,
            'View Work Package',
            $this->_app_url() . 'otherFiles'
        );
        $this->_send($uploader_email, $uploader_name, 'File Uploaded – ' . htmlspecialchars($file['display_name']), $uploader_body);

        // ── Notification to each super admin ──────────────────────────
        if(!empty($sa_emails)){
            $admin_body = $this->_wrap(
                'New WP File Uploaded',
                $colour,
                '📂 ' . $action,
                "A file has been uploaded to the GREATER FMS document repository.",
                $info,
                'View Work Package',
                $this->_app_url() . 'otherFiles'
            );
            foreach((array)$sa_emails as $email){
                if(filter_var($email, FILTER_VALIDATE_EMAIL)){
                    $this->_send($email, '', 'New WP File – ' . htmlspecialchars($file['display_name']), $admin_body);
                }
            }
        }

        return true;
    }

    /** Password reset — send new temporary password */
    public function password_reset($to_email, $full_name, $new_password){
        $subject = 'Your GREATER FMS Password Has Been Reset';

        $body = $this->_wrap(
            'Password Reset',
            '#FF9800',
            '🔑 Password Reset',
            "Hello <strong>" . htmlspecialchars($full_name) . "</strong>,<br><br>
             Your password has been reset. Use the temporary password below to log in —
             you will be asked to set a new password immediately.",
            $this->_info_table([
                'Email'             => htmlspecialchars($to_email),
                'Temporary Password'=> '<span style="font-family:monospace;font-size:15px;font-weight:700;letter-spacing:2px;background:#fff8f0;padding:3px 10px;border-radius:4px;">' . htmlspecialchars($new_password) . '</span>',
            ]) .
            $this->_alert_box('⚠ If you did not request this reset, please contact your administrator immediately.', '#e53935'),
            'Login &amp; Set New Password',
            $this->_app_url() . 'login'
        );

        return $this->_send($to_email, $full_name, $subject, $body);
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────

    private function _send($to_email, $to_name, $subject, $body){
        // Buffer any stray PHP output (warnings from fsockopen etc.) so they
        // never corrupt a JSON response in the caller.
        ob_start();
        try {
            $this->CI->email->initialize($this->CI->config->item('email'));
            $this->CI->email->clear();
            $this->CI->email->from('no_reply@greaterproject.eu', 'GREATER FMS');
            $this->CI->email->to($to_email);
            $this->CI->email->subject($subject);
            $this->CI->email->message($body);
            $result = @$this->CI->email->send(FALSE);
            ob_end_clean();
            if(!$result){
                $debug = $this->CI->email->print_debugger(['smtp_log']);
                log_message('error', 'FMS_Mailer: send to ' . $to_email . ' failed. SMTP log: ' . $debug);
                $this->_last_error = $debug;
            }
            return $result;
        } catch(Exception $e){
            ob_end_clean();
            log_message('error', 'FMS_Mailer exception: ' . $e->getMessage());
            $this->_last_error = $e->getMessage();
            return false;
        }
    }

    /** Last SMTP error/debug output — readable after a failed send. */
    public $_last_error = '';

    private function _app_url(){
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'greaterproject.eu') . '/fms/';
    }

    private function _month_name($month){
        $names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                  7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
        return $names[(int)$month] ?? $month;
    }

    private function _alert_box($message, $color = '#FF9800'){
        return '<div style="background:' . $color . '18;border-left:4px solid ' . $color . ';
                            border-radius:4px;padding:12px 16px;margin:16px 0;
                            font-size:12.5px;color:#333;line-height:1.6;">'
               . $message . '</div>';
    }

    private function _info_table($rows){
        $html = '<table style="width:100%;border-collapse:collapse;margin:18px 0;">';
        foreach($rows as $label => $value){
            $html .= '<tr>
                <td style="padding:9px 14px;font-size:13px;font-weight:600;color:#555;
                           background:#f8f8ff;border:1px solid #e8e8f5;width:38%;white-space:nowrap;">'
                       . $label . '</td>
                <td style="padding:9px 14px;font-size:13px;color:#222;
                           border:1px solid #e8e8f5;">'
                       . $value . '</td>
              </tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Master HTML email template.
     *
     * @param string $title       Header title text
     * @param string $accent      Hex colour for header bar and button
     * @param string $badge       Badge/pill text in header
     * @param string $intro       Intro paragraph HTML
     * @param string $content     Main content HTML (info table, etc.)
     * @param string $btn_label   CTA button label
     * @param string $btn_url     CTA button URL
     */
    private function _wrap($title, $accent, $badge, $intro, $content, $btn_label, $btn_url){
        $year = date('Y');
        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin:0;padding:0;background:#f0f0f7;font-family:Arial,Helvetica,sans-serif;">

<!-- Outer wrapper -->
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f7;padding:32px 0;">
<tr><td align="center">

  <!-- Card -->
  <table width="600" cellpadding="0" cellspacing="0"
         style="background:#ffffff;border-radius:12px;overflow:hidden;
                box-shadow:0 4px 24px rgba(105,108,255,.12);max-width:600px;width:100%;">

    <!-- ── Header bar ── -->
    <tr>
      <td style="background:' . $accent . ';padding:32px 40px 28px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <div style="font-size:11px;font-weight:700;letter-spacing:2px;
                          text-transform:uppercase;color:rgba(255,255,255,.75);
                          margin-bottom:8px;">ERASMUS+ GREATER PROJECT</div>
              <div style="font-size:26px;font-weight:800;color:#ffffff;
                          letter-spacing:-.3px;line-height:1.2;">' . $title . '</div>
            </td>
            <td align="right" valign="top">
              <div style="background:rgba(255,255,255,.2);border-radius:20px;
                          padding:6px 16px;font-size:12px;font-weight:700;
                          color:#ffffff;white-space:nowrap;">' . $badge . '</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- ── Accent stripe ── -->
    <tr>
      <td style="height:4px;background:linear-gradient(90deg,' . $accent . ',#a78bfa);"></td>
    </tr>

    <!-- ── Body ── -->
    <tr>
      <td style="padding:36px 40px 28px;">

        <!-- Intro -->
        <p style="font-size:14px;color:#444;line-height:1.7;margin:0 0 6px;">' . $intro . '</p>

        <!-- Dynamic content (info table, etc.) -->
        ' . $content . '

        <!-- CTA button -->
        <table cellpadding="0" cellspacing="0" style="margin:28px 0 8px;">
          <tr>
            <td style="border-radius:8px;background:' . $accent . ';">
              <a href="' . $btn_url . '"
                 style="display:inline-block;padding:13px 32px;font-size:14px;
                        font-weight:700;color:#ffffff;text-decoration:none;
                        letter-spacing:.3px;">' . $btn_label . ' →</a>
            </td>
          </tr>
        </table>

        <p style="font-size:11px;color:#aaa;margin:16px 0 0;">
          Or copy this link: <a href="' . $btn_url . '" style="color:#696cff;word-break:break-all;">' . $btn_url . '</a>
        </p>

      </td>
    </tr>

    <!-- ── Divider ── -->
    <tr><td style="height:1px;background:#eeeeee;"></td></tr>

    <!-- ── Footer ── -->
    <tr>
      <td style="padding:24px 40px;background:#fafafe;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <div style="font-size:13px;font-weight:700;color:#696cff;
                          letter-spacing:.5px;">GREATER FMS</div>
              <div style="font-size:11px;color:#999;margin-top:4px;line-height:1.6;">
                Financial Management System &nbsp;·&nbsp; ERASMUS+ GREATER Project<br>
                This is an automated notification — please do not reply to this email.
              </div>
            </td>
            <td align="right" valign="bottom">
              <div style="font-size:10px;color:#ccc;">&copy; ' . $year . ' GREATER Project</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>

  </table>
  <!-- /Card -->

</td></tr>
</table>
<!-- /Outer wrapper -->

</body>
</html>';
    }
}
