<?php
/**
 * Monthly Financial Reports Model
 * File Attachment Model (like Timesheet System)
 *
 * Add these methods to: application/models/Fms_model_enhanced.php
 *
 * Each report can have multiple file attachments (evidence).
 * Files are uploaded with metadata (amount, category, work package, date).
 * System auto-calculates totals and summaries.
 */

// ==================== CREATE REPORT ====================
/**
 * Create a new monthly financial report (draft status)
 * @param int $partner_id - Partner institution ID
 * @param int $year - Report year
 * @param int $month - Report month (1-12)
 * @param int $created_by - User creating the report
 * @param string $description - Report description
 * @return int - Report ID or FALSE
 */
public function create_monthly_report($partner_id, $year, $month, $created_by, $description = '') {
    // Get partner name
    $partner = $this->db->select('name')
        ->where('partner_id', $partner_id)
        ->get('partners')
        ->row_array();

    if(!$partner) return FALSE;

    // Generate report name
    $months = array(1=>'JANUARY', 2=>'FEBRUARY', 3=>'MARCH', 4=>'APRIL', 5=>'MAY', 6=>'JUNE',
                   7=>'JULY', 8=>'AUGUST', 9=>'SEPTEMBER', 10=>'OCTOBER', 11=>'NOVEMBER', 12=>'DECEMBER');
    $report_name = 'RP_FinancialReport_' . $year . '_' . $months[$month];

    // Create report
    $data = array(
        'partner_id' => $partner_id,
        'report_month' => $month,
        'report_year' => $year,
        'report_name' => $report_name,
        'description' => $description,
        'status' => 'draft',
        'created_by' => $created_by,
        'created_at' => date('Y-m-d H:i:s')
    );

    if($this->db->insert('monthly_financial_reports', $data)) {
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

// ==================== GET REPORT ====================
/**
 * Get complete report with all attachments and summaries
 * @param int $report_id - Report ID
 * @return array - Report with attachments, summaries, etc
 */
public function get_monthly_report($report_id) {
    // Get main report
    $report = $this->db->select('*')
        ->where('report_id', $report_id)
        ->get('monthly_financial_reports')
        ->row_array();

    if(!$report) return FALSE;

    // Get all attachments
    $attachments = $this->db->select('*')
        ->where('report_id', $report_id)
        ->order_by('uploaded_at', 'ASC')
        ->get('monthly_report_attachments')
        ->result_array();

    // Get summary
    $summary = $this->db->select('*')
        ->where('report_id', $report_id)
        ->get('monthly_report_summary')
        ->row_array();

    // Get category breakdown
    $category_summary = $this->db->select('*')
        ->where('report_id', $report_id)
        ->get('monthly_report_category_summary')
        ->result_array();

    // Get work package breakdown
    $wp_summary = $this->db->select('*')
        ->where('report_id', $report_id)
        ->get('monthly_report_wp_summary')
        ->result_array();

    // Get currency breakdown
    $currency_summary = $this->db->select('*')
        ->where('report_id', $report_id)
        ->get('monthly_report_currency_summary')
        ->result_array();

    // Combine everything
    $report['attachments'] = $attachments;
    $report['summary'] = $summary;
    $report['category_summary'] = $category_summary;
    $report['wp_summary'] = $wp_summary;
    $report['currency_summary'] = $currency_summary;
    $report['attachment_count'] = count($attachments);

    return $report;
}

// ==================== LIST REPORTS ====================
/**
 * Get all reports for a partner
 * @param int $partner_id - Partner ID
 * @param string $status - Filter by status (optional)
 * @return array - List of reports
 */
public function get_partner_monthly_reports($partner_id, $status = null) {
    $this->db->select('r.*, p.name as partner_name, u.name as created_by_name, s.total_items')
        ->from('monthly_financial_reports r')
        ->join('partners p', 'p.partner_id = r.partner_id')
        ->join('users u', 'u.user_id = r.created_by')
        ->join('monthly_report_summary s', 's.report_id = r.report_id', 'left')
        ->where('r.partner_id', $partner_id);

    if($status) {
        $this->db->where('r.status', $status);
    }

    $reports = $this->db->order_by('r.created_at', 'DESC')
        ->get()
        ->result_array();

    return $reports;
}

// ==================== ADD ATTACHMENT ====================
/**
 * Add a file attachment to a report
 * @param int $report_id - Report ID
 * @param string $original_filename - Original filename
 * @param string $saved_filename - Saved filename
 * @param string $file_path - Full file path
 * @param int $file_size - File size in bytes
 * @param string $file_type - File extension (pdf, xlsx, etc)
 * @param array $item_data - Item details (name, description, amount, currency, etc)
 * @param int $uploaded_by - User uploading file
 * @return int - Attachment ID or FALSE
 */
public function add_report_attachment($report_id, $original_filename, $saved_filename, $file_path,
                                     $file_size, $file_type, $item_data, $uploaded_by) {

    // Verify report exists
    $report = $this->db->select('report_id')
        ->where('report_id', $report_id)
        ->get('monthly_financial_reports')
        ->row_array();

    if(!$report) return FALSE;

    // Prepare attachment data
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

    if($this->db->insert('monthly_report_attachments', $attachment)) {
        $attachment_id = $this->db->insert_id();

        // Update report summary
        $this->recalculate_report_summary($report_id);

        return $attachment_id;
    }

    return FALSE;
}

// ==================== DELETE ATTACHMENT ====================
/**
 * Delete an attachment from a report
 * @param int $attachment_id - Attachment ID
 * @param int $report_id - Report ID (for summary recalc)
 * @return bool - Success or failure
 */
public function delete_report_attachment($attachment_id, $report_id) {
    if($this->db->delete('monthly_report_attachments', array('attachment_id' => $attachment_id))) {
        // Recalculate summary
        $this->recalculate_report_summary($report_id);
        return TRUE;
    }
    return FALSE;
}

// ==================== RECALCULATE SUMMARY ====================
/**
 * Recalculate all report summaries (totals, categories, etc)
 * @param int $report_id - Report ID
 * @return bool
 */
public function recalculate_report_summary($report_id) {
    // Get all attachments for this report
    $attachments = $this->db->select('*')
        ->where('report_id', $report_id)
        ->get('monthly_report_attachments')
        ->result_array();

    // Initialize totals
    $totals = array(
        'total_items' => count($attachments),
        'total_verified' => 0,
        'total_amount_rwf' => 0,
        'total_amount_eur' => 0,
        'total_amount_usd' => 0
    );

    // Category breakdown
    $category_breakdown = array();
    // WP breakdown
    $wp_breakdown = array();
    // Currency breakdown
    $currency_breakdown = array('RWF' => 0, 'EUR' => 0, 'USD' => 0);

    // Process attachments
    foreach($attachments as $att) {
        if($att['verified']) {
            $totals['total_verified']++;
        }

        // Add to currency totals
        if($att['currency'] && $att['amount'] > 0) {
            $key = 'total_amount_' . strtolower($att['currency']);
            if(isset($totals[$key])) {
                $totals[$key] += $att['amount'];
            }
            $currency_breakdown[$att['currency']] += $att['amount'];
        }

        // Add to category breakdown
        if($att['category']) {
            if(!isset($category_breakdown[$att['category']])) {
                $category_breakdown[$att['category']] = array('count' => 0, 'total' => 0);
            }
            $category_breakdown[$att['category']]['count']++;
            $category_breakdown[$att['category']]['total'] += $att['amount'];
        }

        // Add to WP breakdown
        if($att['work_package']) {
            if(!isset($wp_breakdown[$att['work_package']])) {
                $wp_breakdown[$att['work_package']] = array('count' => 0, 'total' => 0);
            }
            $wp_breakdown[$att['work_package']]['count']++;
            $wp_breakdown[$att['work_package']]['total'] += $att['amount'];
        }
    }

    // Update main summary
    $this->db->update('monthly_report_summary', $totals, array('report_id' => $report_id));

    // Delete and recreate category summaries
    $this->db->delete('monthly_report_category_summary', array('report_id' => $report_id));
    foreach($category_breakdown as $category => $data) {
        $this->db->insert('monthly_report_category_summary', array(
            'report_id' => $report_id,
            'category' => $category,
            'item_count' => $data['count'],
            'total_amount' => $data['total']
        ));
    }

    // Delete and recreate WP summaries
    $this->db->delete('monthly_report_wp_summary', array('report_id' => $report_id));
    foreach($wp_breakdown as $wp => $data) {
        $this->db->insert('monthly_report_wp_summary', array(
            'report_id' => $report_id,
            'work_package' => $wp,
            'item_count' => $data['count'],
            'total_amount' => $data['total']
        ));
    }

    // Delete and recreate currency summaries
    $this->db->delete('monthly_report_currency_summary', array('report_id' => $report_id));
    foreach($currency_breakdown as $currency => $total) {
        if($total > 0) {
            $this->db->insert('monthly_report_currency_summary', array(
                'report_id' => $report_id,
                'currency' => $currency,
                'total_amount' => $total,
                'item_count' => 0  // Will be calculated from category breakdown
            ));
        }
    }

    return TRUE;
}

// ==================== SUBMIT REPORT ====================
/**
 * Submit report for approval
 * @param int $report_id - Report ID
 * @param int $submitted_by - User submitting
 * @return bool
 */
public function submit_monthly_report($report_id, $submitted_by) {
    $data = array(
        'status' => 'submitted',
        'submitted_by' => $submitted_by,
        'submitted_at' => date('Y-m-d H:i:s')
    );
    return $this->db->update('monthly_financial_reports', $data, array('report_id' => $report_id));
}

// ==================== APPROVE REPORT ====================
/**
 * Approve submitted report
 * @param int $report_id - Report ID
 * @param int $approved_by - Admin user ID
 * @param string $notes - Approval notes
 * @return bool
 */
public function approve_monthly_report($report_id, $approved_by, $notes = '') {
    $data = array(
        'status' => 'approved',
        'approved_by' => $approved_by,
        'approved_at' => date('Y-m-d H:i:s'),
        'approval_notes' => $notes
    );
    return $this->db->update('monthly_financial_reports', $data, array('report_id' => $report_id));
}

// ==================== REJECT REPORT ====================
/**
 * Reject submitted report
 * @param int $report_id - Report ID
 * @param string $comments - Rejection reason
 * @return bool
 */
public function reject_monthly_report($report_id, $comments) {
    $data = array(
        'status' => 'rejected',
        'rejected_at' => date('Y-m-d H:i:s'),
        'rejection_comments' => $comments
    );
    return $this->db->update('monthly_financial_reports', $data, array('report_id' => $report_id));
}

// ==================== VERIFY ATTACHMENT ====================
/**
 * Mark attachment as verified (admin only)
 * @param int $attachment_id - Attachment ID
 * @param int $verified_by - Admin user ID
 * @param string $notes - Verification notes
 * @return bool
 */
public function verify_report_attachment($attachment_id, $verified_by, $notes = '') {
    // Get attachment to find report_id
    $att = $this->db->select('report_id')
        ->where('attachment_id', $attachment_id)
        ->get('monthly_report_attachments')
        ->row_array();

    if(!$att) return FALSE;

    $data = array(
        'verified' => TRUE,
        'verified_by' => $verified_by,
        'verified_at' => date('Y-m-d H:i:s'),
        'verification_notes' => $notes
    );

    if($this->db->update('monthly_report_attachments', $data, array('attachment_id' => $attachment_id))) {
        // Recalculate summary
        $this->recalculate_report_summary($att['report_id']);
        return TRUE;
    }

    return FALSE;
}

// ==================== UNVERIFY ATTACHMENT ====================
/**
 * Mark attachment as unverified
 * @param int $attachment_id - Attachment ID
 * @return bool
 */
public function unverify_report_attachment($attachment_id) {
    $att = $this->db->select('report_id')
        ->where('attachment_id', $attachment_id)
        ->get('monthly_report_attachments')
        ->row_array();

    if(!$att) return FALSE;

    $data = array(
        'verified' => FALSE,
        'verified_by' => NULL,
        'verified_at' => NULL,
        'verification_notes' => NULL
    );

    if($this->db->update('monthly_report_attachments', $data, array('attachment_id' => $attachment_id))) {
        $this->recalculate_report_summary($att['report_id']);
        return TRUE;
    }

    return FALSE;
}

// ==================== UPDATE ATTACHMENT DETAILS ====================
/**
 * Update attachment metadata
 * @param int $attachment_id - Attachment ID
 * @param array $update_data - Data to update
 * @return bool
 */
public function update_report_attachment($attachment_id, $update_data) {
    // Get report_id
    $att = $this->db->select('report_id')
        ->where('attachment_id', $attachment_id)
        ->get('monthly_report_attachments')
        ->row_array();

    if(!$att) return FALSE;

    if($this->db->update('monthly_report_attachments', $update_data, array('attachment_id' => $attachment_id))) {
        $this->recalculate_report_summary($att['report_id']);
        return TRUE;
    }

    return FALSE;
}

// ==================== GENERATE PDF ====================
/**
 * Generate PDF for report with all attachments
 * @param int $report_id - Report ID
 * @return string - Path to generated PDF or FALSE
 */
public function generate_report_pdf($report_id) {
    $report = $this->get_monthly_report($report_id);
    if(!$report) return FALSE;

    // PDF generation logic here (will implement with DOMPDF)
    // This is a placeholder
    return 'assets/uploads/reports/' . $report['report_name'] . '.pdf';
}

// ==================== GENERATE EXCEL ====================
/**
 * Generate Excel for report with all attachments
 * @param int $report_id - Report ID
 * @return string - Path to generated Excel or FALSE
 */
public function generate_report_excel($report_id) {
    $report = $this->get_monthly_report($report_id);
    if(!$report) return FALSE;

    // Excel generation logic here (will implement with PhpSpreadsheet)
    // This is a placeholder
    return 'assets/uploads/reports/' . $report['report_name'] . '.xlsx';
}
