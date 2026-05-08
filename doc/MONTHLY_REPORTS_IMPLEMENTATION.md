# Monthly Financial Reports - Implementation Guide

## Overview

The GREATER FMS now supports **Monthly Financial Reports** - a new system for aggregating expenses by month and generating comprehensive financial reports for each partner institution.

Instead of viewing individual expenses, users can now generate and manage monthly financial reports that summarize all approved expenses for a given month and partner.

---

## System Architecture

### Database Schema

Four new tables support monthly reports:

#### 1. **monthly_financial_reports** (Main Report Table)
```
report_id (PK)
├─ partner_id (FK)
├─ report_month (1-12)
├─ report_year (2024, 2025, etc)
├─ report_name (RP_FinancialReport_2024_NOVEMBER)
├─ status (draft, submitted, approved, rejected)
├─ total_expenses_count
├─ total_amount_rwf
├─ total_amount_eur
├─ total_amount_usd
├─ file_path
├─ created_by (FK to users)
├─ submitted_by (FK to users)
├─ approved_by (FK to users)
├─ rejection_comments
└─ Timestamps (created_at, submitted_at, approved_at, rejected_at)
```

#### 2. **monthly_report_items** (Expense Items in Report)
```
item_id (PK)
├─ report_id (FK)
├─ expense_id (FK)
├─ category
├─ work_package
├─ currency
├─ amount
├─ description
├─ expense_date
└─ uploaded_by (FK)
```

#### 3. **monthly_report_summary_wp** (Summary by Work Package)
```
summary_id (PK)
├─ report_id (FK)
├─ work_package
├─ expense_count
├─ total_amount
└─ percentage
```

#### 4. **monthly_report_summary_category** (Summary by Category)
```
summary_id (PK)
├─ report_id (FK)
├─ category
├─ expense_count
├─ total_amount
└─ percentage
```

#### 5. **monthly_report_summary_currency** (Summary by Currency)
```
summary_id (PK)
├─ report_id (FK)
├─ currency
└─ total_amount
```

### Enhanced expenses Table
Two new columns added to existing `expenses` table:
```sql
ALTER TABLE expenses
ADD COLUMN report_month TINYINT(2) AFTER created_at,
ADD COLUMN report_year INT(4) AFTER report_month;
```

---

## Data Flow

### Creating a Monthly Report

```
1. User clicks "Generate Monthly Report" for November 2024
                    ↓
2. System finds all APPROVED expenses for:
   - Partner: XYZ Institution
   - Month: November (11)
   - Year: 2024
                    ↓
3. Creates monthly_financial_reports record
   - Status: draft
   - Total expenses count: 15
   - Totals by currency (RWF, EUR, USD)
                    ↓
4. Copies expenses to monthly_report_items
   - Preserves all expense details
   - Stores with report_id reference
                    ↓
5. Calculates summaries:
   - By Work Package (WP1-WP7)
   - By Category (8 types)
   - By Currency (3 types)
                    ↓
6. Stores summary data in dedicated tables
   - Enables fast report generation
   - Allows percentage calculations
```

### Report Workflow

```
DRAFT
  ↓ (User clicks Submit)
SUBMITTED
  ├─ Admin approves
  │   ↓
  │  APPROVED
  │   ↓
  │  (Can export PDF/Excel)
  │
  └─ Admin rejects
      ↓
    REJECTED
      ↓
    (User can edit and resubmit)
```

---

## Model Methods

### Creating Reports

```php
// Create a new monthly report
$report_id = $this->fmsm_enhanced->create_monthly_report(
    $partner_id,  // 1
    $year,        // 2024
    $month,       // 11 (November)
    $user_id      // Current user creating the report
);
```

**What it does:**
1. Finds all approved expenses for that month/partner
2. Creates report record with totals
3. Adds all expenses as report items
4. Calculates summaries by WP, category, currency
5. Returns report_id on success, FALSE on failure

### Retrieving Reports

```php
// Get complete report with all details
$report = $this->fmsm_enhanced->get_monthly_report($report_id);

// Returns:
array(
    'report_id' => 1,
    'partner_id' => 1,
    'report_month' => 11,
    'report_year' => 2024,
    'report_name' => 'RP_FinancialReport_2024_NOVEMBER',
    'total_expenses_count' => 15,
    'total_amount_rwf' => 1500000,
    'total_amount_eur' => 2500,
    'total_amount_usd' => 3000,
    'status' => 'draft',

    // Related data
    'expenses' => [...],        // All items in report
    'wp_summary' => [...],      // Summary by work package
    'category_summary' => [...], // Summary by category
    'currency_summary' => [...]  // Summary by currency
)
```

```php
// Get all reports for a partner
$reports = $this->fmsm_enhanced->get_partner_monthly_reports(
    $partner_id,
    $status = 'draft'  // Optional filter
);

// Returns array of reports ordered by date (newest first)
```

### Managing Reports

```php
// Submit for approval
$this->fmsm_enhanced->submit_monthly_report($report_id, $user_id);

// Approve report
$this->fmsm_enhanced->approve_monthly_report(
    $report_id,
    $approved_by_user_id,
    $notes = 'Approved for processing'
);

// Reject report
$this->fmsm_enhanced->reject_monthly_report(
    $report_id,
    $rejection_comments = 'Please verify expense amounts'
);
```

---

## View Structure

### Monthly Reports List (`monthly_reports.php`)

**For Coordinators/Staff:**
- View their own monthly reports
- Statuses: Draft, Submitted, Approved, Rejected
- Actions: Edit (draft), Submit, View, Download

**For Admins:**
- View all partner reports
- Filter by: Partner, Month, Year, Status
- Actions: View, Approve, Reject, Download

**Table Columns:**
```
Partner | Month/Year | # Expenses | RWF Total | EUR Total | USD Total | Status | Actions
```

### Monthly Report Detail View (`monthly_report_detail.php`)

**Header Section:**
```
Report: RP_FinancialReport_2024_NOVEMBER
Partner: XYZ Institution
Period: November 1-30, 2024
Generated: 2024-11-15 by John Doe
Status: Submitted
```

**Executive Summary:**
```
Total Expenses: 15
Total in RWF: 1,500,000
Total in EUR: 2,500
Total in USD: 3,000
```

**Summary Tables (3 sections):**

1. **By Work Package**
   ```
   WP | Count | Amount | % of Total
   WP1| 3     | 500,000| 20%
   WP2| 5     | 750,000| 30%
   ...
   ```

2. **By Category**
   ```
   Category         | Count | Amount | % of Total
   Travel           | 4     | 400,000| 16%
   Accommodation    | 6     | 900,000| 36%
   ...
   ```

3. **By Currency**
   ```
   Currency | Total
   RWF      | 1,500,000
   EUR      | 2,500
   USD      | 3,000
   ```

**Detailed Expenses Table:**
```
Date | Amount | Currency | Category | WP | Description | Uploaded By | Upload Date
```

**Approval Section (Admin Only):**
```
Status: [Submitted]
Submitted by: John Doe on 2024-11-15
[Approve] [Reject]
[Notes field]
[Comments]
```

---

## Implementation Steps

### Step 1: Database Migration
```bash
# Execute SQL migration
mysql -u user -p database < MONTHLY_REPORTS_MIGRATION.sql

# Verify tables created
SHOW TABLES LIKE 'monthly%';
```

### Step 2: Model Methods
✅ Already added to `application/models/Fms_model_enhanced.php` (Lines 556-791)

### Step 3: Create Controller Methods
Add to `application/controllers/Fms.php`:

```php
// List monthly reports
public function monthlyReports() {
    if(!$this->auth_manager->is_super_admin() &&
       !$this->auth_manager->is_admin() &&
       !$this->auth_manager->is_coordinator()) {
        show_error('Access Denied', 403);
    }

    $partner_id = ($this->auth_manager->is_coordinator())
        ? $this->session->userdata('fms_partner_id')
        : $this->input->get('partner_id');

    $reports = $this->fmsm_enhanced->get_partner_monthly_reports($partner_id);

    $this->data['reports'] = $reports;
    $this->data['title'] = 'Monthly Financial Reports';
    $this->load->view('pages/monthly_reports', $this->data);
}

// View report details
public function viewMonthlyReport($report_id) {
    $report = $this->fmsm_enhanced->get_monthly_report($report_id);

    // Check access
    if(!$this->can_access_report($report)) {
        show_error('Access Denied', 403);
    }

    $this->data['report'] = $report;
    $this->data['title'] = 'Financial Report - ' . $report['report_name'];
    $this->load->view('pages/monthly_report_detail', $this->data);
}

// Generate report for month
public function generateMonthlyReport($partner_id, $year, $month) {
    if(!$this->auth_manager->is_coordinator() &&
       !$this->auth_manager->is_admin()) {
        show_error('Access Denied', 403);
    }

    $report_id = $this->fmsm_enhanced->create_monthly_report(
        $partner_id,
        $year,
        $month,
        $this->session->userdata('fms_user_id')
    );

    if($report_id) {
        $this->session->set_flashdata('success', 'Monthly report generated successfully');
        redirect('monthlyReports');
    } else {
        $this->session->set_flashdata('error', 'No approved expenses for this month');
        redirect('monthlyReports');
    }
}

// Submit report for approval
public function submitMonthlyReport($report_id) {
    if(!$this->auth_manager->is_coordinator() &&
       !$this->auth_manager->is_admin()) {
        show_error('Access Denied', 403);
    }

    $this->fmsm_enhanced->submit_monthly_report(
        $report_id,
        $this->session->userdata('fms_user_id')
    );

    $this->session->set_flashdata('success', 'Report submitted for approval');
    redirect('viewMonthlyReport/' . $report_id);
}

// Approve report (Admin only)
public function approveMonthlyReport($report_id) {
    if(!$this->auth_manager->is_admin() &&
       !$this->auth_manager->is_super_admin()) {
        show_error('Access Denied', 403);
    }

    $notes = $this->input->post('notes');

    $this->fmsm_enhanced->approve_monthly_report(
        $report_id,
        $this->session->userdata('fms_user_id'),
        $notes
    );

    $this->session->set_flashdata('success', 'Report approved');
    redirect('viewMonthlyReport/' . $report_id);
}

// Reject report (Admin only)
public function rejectMonthlyReport($report_id) {
    if(!$this->auth_manager->is_admin() &&
       !$this->auth_manager->is_super_admin()) {
        show_error('Access Denied', 403);
    }

    $comments = $this->input->post('rejection_comments');

    $this->fmsm_enhanced->reject_monthly_report($report_id, $comments);

    $this->session->set_flashdata('success', 'Report rejected');
    redirect('viewMonthlyReport/' . $report_id);
}

// Helper method
private function can_access_report($report) {
    $user_partner = $this->session->userdata('fms_partner_id');
    return $this->auth_manager->is_admin() ||
           $this->auth_manager->is_super_admin() ||
           $report['partner_id'] == $user_partner;
}
```

### Step 4: Add Routes
Add to `application/config/routes.php`:

```php
$route['monthlyReports'] = 'fms/monthlyReports';
$route['viewMonthlyReport/(:num)'] = 'fms/viewMonthlyReport/$1';
$route['generateMonthlyReport/(:num)/(:num)/(:num)'] = 'fms/generateMonthlyReport/$1/$2/$3';
$route['submitMonthlyReport/(:num)'] = 'fms/submitMonthlyReport/$1';
$route['approveMonthlyReport/(:num)'] = 'fms/approveMonthlyReport/$1';
$route['rejectMonthlyReport/(:num)'] = 'fms/rejectMonthlyReport/$1';
```

### Step 5: Create Views
Create `application/views/pages/monthly_reports.php` and `monthly_report_detail.php`

### Step 6: Update Menu Navigation
Add link to "Monthly Reports" in navigation menu

---

## File Naming Convention

Reports follow the pattern:
```
RP_FinancialReport_{YEAR}_{MONTH_NAME}.pdf
Example: RP_FinancialReport_2024_NOVEMBER.pdf
```

Generated by: `$this->get_month_name($month)` helper (JANUARY, FEBRUARY, etc.)

---

## Features

### ✅ Implemented
- Database schema for monthly reports
- Model methods for CRUD operations
- Report generation from approved expenses
- Automatic summary calculations
- Report status workflow (draft → submitted → approved/rejected)
- Multi-currency support

### 🔄 To Be Implemented
- Web interface (views)
- Controller methods
- PDF export functionality
- Excel export functionality
- Email notifications
- Report archive/history
- Comparisons between months
- Variance analysis

---

## Security & Authorization

**Access Control:**
- Coordinators: Can only view/manage their own institution's reports
- Admins: Can view/manage all reports
- Members: No access

**Approval Flow:**
- Only admins can approve/reject reports
- Submitted reports cannot be edited
- Rejected reports can be corrected and resubmitted

---

## Performance Considerations

**Optimizations:**
- Summary tables pre-calculated (not computed on-the-fly)
- Indexes on frequently queried columns
- Separate tables for different summary types

**Database Indexes:**
```sql
CREATE INDEX idx_expenses_month_year ON expenses(report_year, report_month, partner_id);
CREATE INDEX idx_monthly_reports_partner ON monthly_financial_reports(partner_id);
CREATE INDEX idx_monthly_reports_status ON monthly_financial_reports(status);
```

---

## Testing Checklist

- [ ] Database migration executed successfully
- [ ] All new tables created
- [ ] Model methods tested:
  - [ ] create_monthly_report()
  - [ ] get_monthly_report()
  - [ ] get_partner_monthly_reports()
  - [ ] submit/approve/reject methods
- [ ] Controller methods work
- [ ] Views display correctly
- [ ] Role-based access works
- [ ] PDF export generates correctly
- [ ] Excel export generates correctly
- [ ] Monthly report appears in menu
- [ ] Reports calculate totals correctly
- [ ] Summaries are accurate

---

## Status

**Completed:**
- ✅ Database schema design and migration scripts
- ✅ Model methods implementation
- ✅ Report creation and management logic

**In Progress:**
- 🔄 Controller methods
- 🔄 View implementation
- 🔄 PDF/Excel export

**Planned:**
- 📅 Navigation integration
- 📅 Email notifications
- 📅 Advanced reporting features

---

**Last Updated:** November 2024
**Version:** 1.0 (Foundation)
**Status:** Ready for controller and view implementation
