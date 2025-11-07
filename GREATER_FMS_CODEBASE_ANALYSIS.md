# GREATER FMS Codebase Analysis & Monthly Financial Reports Implementation Guide

**Date:** November 4, 2024  
**Project:** GREATER - Growing Rwanda Energy Awareness Through highER education  
**System:** Financial Management System (FMS)  
**Codebase:** CodeIgniter 3.x based system

---

## Table of Contents

1. [Current System Architecture](#current-system-architecture)
2. [Expenses Module Structure](#expenses-module-structure)
3. [Database Schema for Expenses](#database-schema-for-expenses)
4. [Current View Implementation](#current-view-implementation)
5. [File Naming Patterns](#file-naming-patterns)
6. [Existing Grouping & Report Logic](#existing-grouping--report-logic)
7. [Restructuring for Monthly Financial Reports](#restructuring-for-monthly-financial-reports)
8. [Implementation Steps](#implementation-steps)

---

## Current System Architecture

### Technology Stack
- **Framework:** CodeIgniter 3.x
- **Database:** MySQL
- **Frontend:** Bootstrap 5 with DataTables
- **PDF Generation:** DOMPDF library (for timesheets)
- **Excel Support:** PhpSpreadsheet (for timesheet parsing)
- **File Upload:** Native CodeIgniter file upload handling

### Project Structure
```
/Applications/XAMPP/xamppfiles/htdocs/fms/
├── application/
│   ├── controllers/
│   │   ├── Fms.php (Main controller)
│   │   └── Login.php
│   ├── models/
│   │   ├── Fms_model.php (Legacy model)
│   │   └── Fms_model_enhanced.php (Current active model)
│   ├── views/
│   │   └── pages/
│   │       ├── expenses.php (Current expenses list view)
│   │       ├── newexpense.php (Expense upload form)
│   │       ├── timesheets.php
│   │       ├── navbar.php
│   │       └── menu.php
│   ├── libraries/
│   │   └── Auth_manager.php
│   └── config/
└── assets/
    ├── uploads/ (File storage)
    ├── vendor/ (Composer packages)
    └── js/
        └── form-expenses.js
```

### Role-Based Access Control
- **Super Admin (role_id=1):** Full system access, all data
- **Admin (role_id=2):** System configuration, view all data
- **Institution Coordinator (role_id=3):** Upload expenses, approve timesheets, manage institution
- **Member (role_id=4):** Submit timesheets, view own data

---

## Expenses Module Structure

### Expense Flow
```
Coordinator/Admin
    ↓
(newExpense form) → Input expense details + file upload
    ↓
(saveExpense) → Server-side validation
    ↓
(create_expense) → Database INSERT with status='pending'
    ↓
[Expense Table] → Displays all expenses
    ↓
Admin Review → Approve/Reject
    ↓
Status updated to 'approved' or 'rejected'
```

### Expense Record Fields
From database schema (expenses table):

| Field | Type | Purpose |
|-------|------|---------|
| `expense_id` | INT | Primary key |
| `partner_id` | INT | Institution identifier |
| `FileName` | VARCHAR(255) | File name pattern |
| `Category` | VARCHAR(100) | Expense category |
| `WorkPackage` | VARCHAR(50) | WP1-WP7 work package code |
| `Currency` | VARCHAR(10) | RWF, EUR, USD |
| `Amount` | DECIMAL(15,2) | Expense amount |
| `ShortDescription` | TEXT | Description (50-500 chars) |
| `Date` | DATE | Expense date |
| `uploaded_by` | INT | User ID of uploader |
| `status` | ENUM | pending/approved/rejected |
| `approved_by` | INT | Approver user ID |
| `approved_at` | TIMESTAMP | Approval timestamp |
| `created_at` | TIMESTAMP | Upload timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

### Valid Categories
- Travel
- Accommodation
- Subsistence
- Equipment
- Consumables
- Services for Meetings, Seminars
- Services for communication/promotion/dissemination
- Other

### Valid Work Packages (GREATER Project)
- WP1: Management and coordination
- WP2: Collaboration design
- WP3: Infrastructures
- WP4: Curricula design
- WP5: Training and coaching
- WP6: Transfer methodologies
- WP7: Impact and dissemination

---

## Database Schema for Expenses

### Current Expenses Table Definition

```sql
CREATE TABLE IF NOT EXISTS `expenses` (
  `expense_id` int(11) NOT NULL AUTO_INCREMENT,
  `Partner` varchar(255) DEFAULT NULL,
  `partner_id` int(11) DEFAULT NULL,
  `FileName` varchar(255) NOT NULL,
  `Category` varchar(100) DEFAULT NULL,
  `WorkPackage` varchar(50) DEFAULT NULL,
  `Currency` varchar(10) DEFAULT 'EUR',
  `Amount` decimal(15,2) DEFAULT NULL,
  `ShortDescription` text DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL COMMENT 'user_id of uploader',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL COMMENT 'user_id of approver',
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`expense_id`),
  KEY `partner_id` (`partner_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Recommended Extensions for Monthly Reports

```sql
-- Add columns for better reporting
ALTER TABLE expenses ADD COLUMN month TINYINT(2) AFTER Date;
ALTER TABLE expenses ADD COLUMN year INT(4) AFTER month;
ALTER TABLE expenses ADD COLUMN report_id INT(11) AFTER year;
ALTER TABLE expenses ADD COLUMN report_status ENUM('draft','submitted','approved') DEFAULT 'draft' AFTER status;

-- Create monthly_reports table
CREATE TABLE IF NOT EXISTS `monthly_financial_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` tinyint(2) NOT NULL COMMENT '1-12',
  `report_name` varchar(255) NOT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `total_expenses` int(11) DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `file_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `unique_monthly_report` (`partner_id`, `year`, `month`),
  KEY `partner_id` (`partner_id`),
  KEY `year_month` (`year`, `month`),
  KEY `status` (`status`),
  CONSTRAINT `monthly_reports_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create report_expenses junction table
CREATE TABLE IF NOT EXISTS `report_expenses` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `expense_id` int(11) NOT NULL,
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `report_expense` (`report_id`, `expense_id`),
  KEY `expense_id` (`expense_id`),
  CONSTRAINT `report_expenses_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `monthly_financial_reports` (`report_id`) ON DELETE CASCADE,
  CONSTRAINT `report_expenses_ibfk_2` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

---

## Current View Implementation

### File Path
`/Applications/XAMPP/xamppfiles/htdocs/fms/application/views/pages/expenses.php`

### Current Display Features

**Displays as:** Individual expense records in a DataTable

**Columns Shown:**
1. Checkbox (for bulk operations)
2. File Name (clickable link to download)
3. Partner Name
4. Date
5. Amount
6. Currency
7. Category
8. Work Package
9. Uploaded By
10. Upload Date (created_at)
11. Description
12. Status (badge: pending/approved/rejected)
13. Actions (Approve/Reject buttons for admin if pending)

**Current Status Badge Colors:**
- `bg-success` → Approved (green)
- `bg-danger` → Rejected (red)
- `bg-warning` → Pending (yellow)

### Key JavaScript Functions
```javascript
function approveExpense(expenseId) {
    // Creates form and submits to approveExpense controller action
    // Accepts optional approval comments
}

function rejectExpense(expenseId) {
    // Creates form and submits to rejectExpense controller action
    // Requires rejection comments
}
```

---

## File Naming Patterns

### Existing Pattern: RP_FincialReport_2024_AUGUST_2024821

**Analysis of Pattern:**
```
RP_FincialReport_2024_AUGUST_2024821
│   │           │    │     │  │    │
│   │           │    │     │  │    └─ Random timestamp? (2024821)
│   │           │    │     │  └────── Month (AUGUST)
│   │           │    │     └───────── Month number? (2024 = malformed)
│   │           │    └──────────────── Year (2024)
│   │           └─────────────────────Type (FincialReport - typo for Financial)
│   └───────────────────────────────── Institution code (RP = Rwanda Polytechnic)
└───────────────────────────────────── Prefix (RP = Rwanda Polytechnic)
```

**Current newexpense.php Pattern:**
```php
$FileName = $this->session->userdata("fms_partner") . "-FS-" . $uid;
// Output: "PartnerName-FS-12345"
// Example: "Rwanda Polytechnic-FS-45823"
```

### Recommended Pattern for Monthly Reports
```
RP_FinancialReport_2024_AUGUST.pdf
│   │                │    │
│   │                │    └──── Month name (JANUARY-DECEMBER)
│   │                └───────── Year (YYYY)
│   └──────────────────────── Report type
└──────────────────────────── Institution code
```

**PHP Implementation:**
```php
$month_name = ['', 'JANUARY', 'FEBRUARY', ..., 'DECEMBER'][$month];
$filename = $partner_shortname . '_FinancialReport_' . $year . '_' . $month_name;
// Output: "RP_FinancialReport_2024_AUGUST.pdf"
```

---

## Existing Grouping & Report Logic

### 1. Expense Summary Method
**Location:** `Fms_model_enhanced.php` (Lines 499-521)

```php
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
```

**Use Case:** Groups expenses by Currency, Category, and WorkPackage

### 2. Timesheet Summary Method
**Location:** `Fms_model_enhanced.php` (Lines 523-540)

```php
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
```

**Use Case:** Groups timesheets by status and month

### 3. Work Package Summary (Timesheets)
**Location:** `Fms_model_enhanced.php` (Lines 356-364)

```php
public function get_timesheet_work_package_summary($timesheet_id){
    $this->db->select('work_package, SUM(hours) as total_hours')
            ->where('timesheet_id', $timesheet_id)
            ->group_by('work_package')
            ->order_by('work_package', 'ASC');
    
    return $this->db->get('timesheet_details')->result_array();
}
```

---

## Restructuring for Monthly Financial Reports

### Phase 1: View Level Changes

**Current View:** Individual expense list (expenses.php)
**New View:** Group by Month → Category/WorkPackage hierarchy

#### New Report View Structure

```
Monthly Financial Report - August 2024
├── Header Information
│   ├── Institution: Rwanda Polytechnic
│   ├── Period: August 2024
│   ├── Total Expenses: 45
│   ├── Total Amount: 12,450.00 EUR
│   └── Status: Approved
├── Summary by Work Package
│   ├── WP1 - Management and coordination
│   │   ├── Count: 8
│   │   ├── Amount: 2,100.00 EUR
│   │   └── % of total: 16.9%
│   ├── WP2 - Collaboration design
│   │   ├── Count: 12
│   │   ├── Amount: 3,200.00 EUR
│   │   └── % of total: 25.7%
│   └── [Other WPs...]
├── Summary by Category
│   ├── Travel
│   │   ├── Count: 15
│   │   ├── Amount: 4,500.00 EUR
│   │   └── % of total: 36.1%
│   ├── Accommodation
│   │   ├── Count: 10
│   │   ├── Amount: 3,000.00 EUR
│   │   └── % of total: 24.1%
│   └── [Other categories...]
├── Summary by Currency
│   ├── EUR: 8,450.00
│   ├── RWF: 2,500,000.00
│   └── USD: 1,200.00
└── Detailed Expense Table
    ├── Date | Amount | Category | WorkPackage | Description | Status
    └── [50 expense records...]
```

### Phase 2: Controller Level Changes

**New Controller Methods Needed:**

```php
public function monthlyReports() {
    // List all monthly financial reports
    // Filter by year, month, partner, status
}

public function generateMonthlyReport($year, $month, $partner_id = NULL) {
    // Generate report for specific month/year/partner
    // Group all approved expenses by month
    // Create report record
}

public function downloadMonthlyReportPDF($report_id) {
    // Generate PDF of monthly report
    // Using DOMPDF like timesheets
}

public function downloadMonthlyReportExcel($report_id) {
    // Generate Excel of monthly report
    // Using PhpSpreadsheet
}

public function approveMonthlyReport($report_id) {
    // Approve entire monthly report
    // Only admin
}

public function rejectMonthlyReport($report_id) {
    // Reject entire monthly report
}
```

### Phase 3: Model Level Changes

**New Model Methods Needed:**

```php
// Get expenses grouped by month
public function get_expenses_by_month($partner_id, $year, $month) {
    // Filter expenses where Date between month start/end
    // Return grouped by category/workpackage
}

// Get monthly report with all aggregations
public function get_monthly_report_data($report_id) {
    // Get report header
    // Get all expenses in report with proper grouping
    // Calculate totals and percentages
}

// Create monthly report
public function create_monthly_report($data) {
    // Insert into monthly_financial_reports table
}

// Get monthly report summary for dashboard
public function get_monthly_report_summary($partner_id, $year) {
    // Return all months of year with status
}

// Get expense breakdown for monthly report
public function get_monthly_expense_breakdown($report_id) {
    // By work package
    // By category
    // By currency
}
```

### Phase 4: Database Changes

**Add indexes for performance:**
```sql
CREATE INDEX idx_expenses_date ON expenses(Date);
CREATE INDEX idx_expenses_month_year ON expenses(month, year);
CREATE INDEX idx_monthly_reports_partner ON monthly_financial_reports(partner_id);
CREATE INDEX idx_monthly_reports_year_month ON monthly_financial_reports(year, month);
```

---

## Information That Should Be in Monthly Financial Report

### Header Section
- Report Title: "Monthly Financial Report - [Month] [Year]"
- Institution/Partner Name
- Report Period: [Month 1-31, Year]
- Report Generated Date
- Generated By: [User Name, Role]
- Report Status: Draft/Submitted/Approved/Rejected
- Approval Information (if approved)

### Executive Summary
- Total Number of Expenses: [Count]
- Total Amount (by currency):
  - EUR: [Amount]
  - RWF: [Amount]
  - USD: [Amount]
- Total Converted to Base Currency (if conversion rates available)
- Variance from Budget (if budget data available)
- Period-over-Period Comparison (previous month, year-to-date)

### Summary by Work Package
- WP1 through WP7 breakdown:
  - Count of expenses
  - Total amount
  - Percentage of total
  - Top categories within WP

### Summary by Category
- Travel
- Accommodation
- Subsistence
- Equipment
- Consumables
- Services for Meetings
- Services for Communication
- Other

For each: Count, Amount, % of total, Top work packages

### Summary by Currency
- Display total for each currency used
- Show conversion if needed
- Display exchange rate used (if applicable)

### Detailed Expense Table
Columns:
- Date
- Amount
- Currency
- Category
- Work Package
- Description
- File Name (reference)
- Uploaded By (user name)
- Upload Date

Filter options:
- By Status (Approved/Pending/Rejected)
- By Category
- By Work Package

### Approval Workflow Section
- Submitted By: [User Name, Date, Time]
- Submitted To: [Distribution List]
- Approval Chain:
  - Institution Coordinator: [Status, Date]
  - Central Admin: [Status, Date]
  - Finance Officer: [Status, Date]
- Comments/Approval Notes for each stage
- Digital Signature/Approval Timestamps

### Footer Information
- Report Confidentiality Level
- Print/Digital Version Indicator
- Page Numbers
- Report ID/Reference Number
- Generated System: "GREATER FMS v1.0"

---

## Implementation Steps

### Step 1: Database Migration (CRITICAL)

```sql
-- Execute these to prepare database for monthly reports
USE Sql1800295_2;

-- Add month/year fields to expenses
ALTER TABLE expenses ADD COLUMN month TINYINT(2) AFTER Date;
ALTER TABLE expenses ADD COLUMN year INT(4) AFTER month;

-- Create monthly_financial_reports table
CREATE TABLE IF NOT EXISTS monthly_financial_reports (
  report_id INT(11) NOT NULL AUTO_INCREMENT,
  partner_id INT(11) NOT NULL,
  year INT(4) NOT NULL,
  month TINYINT(2) NOT NULL,
  report_name VARCHAR(255) NOT NULL,
  total_amount DECIMAL(15,2) DEFAULT NULL,
  total_expenses INT(11) DEFAULT NULL,
  status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
  file_path VARCHAR(255) DEFAULT NULL,
  created_by INT(11) NOT NULL,
  submitted_at TIMESTAMP NULL,
  approved_by INT(11) DEFAULT NULL,
  approved_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (report_id),
  UNIQUE KEY unique_monthly_report (partner_id, year, month),
  KEY partner_id (partner_id),
  KEY year_month (year, month),
  KEY status (status),
  CONSTRAINT monthly_reports_ibfk_1 FOREIGN KEY (partner_id) REFERENCES partners (partner_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Populate month/year for existing expenses
UPDATE expenses SET month = MONTH(Date), year = YEAR(Date) WHERE month IS NULL;

-- Add indexes for performance
CREATE INDEX idx_expenses_date ON expenses(Date);
CREATE INDEX idx_expenses_month_year ON expenses(month, year);
CREATE INDEX idx_monthly_reports_partner ON monthly_financial_reports(partner_id);
CREATE INDEX idx_monthly_reports_status ON monthly_financial_reports(status);
```

### Step 2: Model Enhancement

Add to `Fms_model_enhanced.php`:

```php
// Get expenses for a specific month
public function get_expenses_by_month($partner_id, $year, $month, $status = NULL) {
    $this->db->select('expenses.*,
        partners.name AS partner_name,
        users.email AS uploader_email,
        staff.first_name,
        staff.last_name')
        ->join('partners', 'partners.partner_id=expenses.partner_id', 'left')
        ->join('users', 'users.user_id=expenses.uploaded_by', 'left')
        ->join('staff', 'staff.staff_id=users.staff_id', 'left')
        ->where('expenses.partner_id', $partner_id)
        ->where('expenses.year', $year)
        ->where('expenses.month', $month);
    
    if($status){
        $this->db->where('expenses.status', $status);
    }
    
    $this->db->order_by('expenses.Date', 'ASC');
    return $this->db->get('expenses')->result_array();
}

// Get monthly report summary
public function get_monthly_report($report_id) {
    return $this->db->select('monthly_financial_reports.*,
        partners.name AS partner_name,
        creator.first_name AS creator_first_name,
        creator.last_name AS creator_last_name,
        approver.first_name AS approver_first_name,
        approver.last_name AS approver_last_name')
        ->join('partners', 'partners.partner_id=monthly_financial_reports.partner_id')
        ->join('staff AS creator', 'creator.staff_id=monthly_financial_reports.created_by', 'left')
        ->join('staff AS approver', 'approver.staff_id=monthly_financial_reports.approved_by', 'left')
        ->where('report_id', $report_id)
        ->get('monthly_financial_reports')->row_array();
}

// Create monthly report
public function create_monthly_report($data) {
    $this->db->insert('monthly_financial_reports', $data);
    return $this->db->insert_id();
}

// Get summary grouped by work package
public function get_monthly_summary_by_workpackage($report_id) {
    // Get all expenses in this report grouped by work package
}

// Get summary grouped by category
public function get_monthly_summary_by_category($report_id) {
    // Get all expenses in this report grouped by category
}
```

### Step 3: Controller Methods

Add to `Fms.php`:

```php
public function monthlyReports() {
    if(!$this->auth_manager->can_upload_expenses() && !$this->auth_manager->is_admin()){
        show_error('Access Denied', 403);
    }
    
    $this->data["title"] = "FMS - Monthly Financial Reports";
    
    // Get reports based on role
    if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
        $this->data['reports'] = $this->fmsm_enhanced->get_all_monthly_reports();
    } else {
        $partner_id = $this->session->userdata('fms_partner_id');
        $this->data['reports'] = $this->fmsm_enhanced->get_monthly_reports_by_partner($partner_id);
    }
    
    $this->load->view('pages/monthly_reports', $this->data);
}

public function generateMonthlyReport($year = NULL, $month = NULL) {
    // Validate inputs
    $year = $year ?: date('Y');
    $month = $month ?: date('n');
    
    // Get partner
    if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
        $partner_id = $this->input->post('partner_id');
    } else {
        $partner_id = $this->session->userdata('fms_partner_id');
    }
    
    // Get all approved expenses for this month
    $expenses = $this->fmsm_enhanced->get_expenses_by_month($partner_id, $year, $month, 'approved');
    
    if(empty($expenses)){
        $this->session->set_flashdata('error', 'No approved expenses found for this period.');
        redirect('monthlyReports');
        return;
    }
    
    // Create report record
    $user_id = $this->session->userdata('fms_user_id');
    $partner = $this->fmsm_enhanced->get_partner_by_id($partner_id);
    
    $months = ['', 'January', 'February', ..., 'December'];
    $report_name = $partner['short_name'] . '_FinancialReport_' . $year . '_' . strtoupper($months[$month]);
    
    $report_data = array(
        'partner_id' => $partner_id,
        'year' => $year,
        'month' => $month,
        'report_name' => $report_name,
        'total_amount' => array_sum(array_column($expenses, 'Amount')),
        'total_expenses' => count($expenses),
        'status' => 'draft',
        'created_by' => $user_id
    );
    
    $report_id = $this->fmsm_enhanced->create_monthly_report($report_data);
    
    $this->session->set_flashdata('success', 'Monthly report generated successfully.');
    redirect('viewMonthlyReport/' . $report_id);
}

public function downloadMonthlyReportPDF($report_id) {
    // Check authorization
    // Get report data
    // Generate PDF using DOMPDF
    // Return PDF file
}
```

### Step 4: View Creation

Create `application/views/pages/monthly_reports.php` to display:
- List of monthly reports with filters
- Option to generate new report
- View/Edit/Download buttons for each report
- Status indicators

Create `application/views/pages/monthly_report_view.php` to display:
- Full report with all sections outlined above
- Download PDF/Excel buttons
- Approve/Reject buttons (for admins)

### Step 5: Update Menu Navigation

Add link to menu to access monthly reports view.

---

## Summary

### Key Findings

1. **Current System:** Individual expense tracking with approval workflow
2. **Strengths:**
   - Solid role-based access control
   - File upload with validation
   - Approval workflow implemented
   - Database relationships established
3. **Gaps for Monthly Reports:**
   - No grouping by month in expenses view
   - No monthly report generation functionality
   - No PDF/Excel export for reports
   - No report approval workflow
   - Missing indexes for performance

### Restructuring Summary

To convert from individual expenses list to monthly financial reports:

1. **Data Organization:** Group expenses by Month/Year/Partner
2. **Reporting Level:** Add aggregation queries by Category and WorkPackage
3. **Report Object:** Create monthly_financial_reports table and hierarchy
4. **Presentation:** New view showing hierarchical breakdown with summaries
5. **Export:** Generate PDF/Excel with DOMPDF and PhpSpreadsheet
6. **Workflow:** Add report-level approval separate from individual expenses

### File Naming Convention

Adopt: `{PARTNER_CODE}_FinancialReport_{YEAR}_{MONTH_NAME}.{ext}`

Example: `RP_FinancialReport_2024_AUGUST.pdf`

---

**Status:** Ready for implementation
**Estimated Effort:** 20-30 hours development
**Priority:** High - Critical for financial management

