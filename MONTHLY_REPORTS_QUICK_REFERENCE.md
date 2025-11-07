# Monthly Financial Reports - Quick Reference Guide

## File Locations - Key Files in Codebase

### Current Expenses Implementation

| File | Location | Purpose |
|------|----------|---------|
| Expenses View | `/application/views/pages/expenses.php` | Displays individual expense list |
| New Expense Form | `/application/views/pages/newexpense.php` | Upload expense form |
| Main Controller | `/application/controllers/Fms.php` (Lines 50-67, 986-1207) | Expense controller methods |
| Model | `/application/models/Fms_model_enhanced.php` (Lines 172-237) | Expense database methods |
| Database Schema | `/database_schema.sql` (Lines 101-127) | Expenses table definition |
| Form Validation | `/assets/js/form-expenses.js` | Frontend validation |

### Existing Grouping Functions in Model

| Method | Location | What It Does |
|--------|----------|--------------|
| `get_all_expenses()` | Line 172-193 | Get all expenses with filtering |
| `get_expense_summary()` | Line 499-521 | Group by Currency, Category, WorkPackage |
| `save_expense()` | Line 199-202 | Insert expense into database |
| `create_expense()` | Line 204-207 | Alias for save_expense |
| `approve_expense()` | Line 214-225 | Approve an expense |
| `reject_expense()` | Line 227-237 | Reject an expense |

### Controller Methods for Expenses

| Method | Location | Route | What It Does |
|--------|----------|-------|--------------|
| `expenses()` | Line 50-67 | `/expenses` | Display all expenses |
| `newExpense()` | Line 986-1001 | `/newExpense` | Show expense upload form |
| `saveExpense()` | Line 1003-1172 | POST `/saveExpense` | Validate and save expense |
| `approveExpense()` | Line 1174-1190 | POST `/approveExpense/{id}` | Approve expense |
| `rejectExpense()` | Line 1192-1207 | POST `/rejectExpense/{id}` | Reject expense |

---

## Database Tables

### EXPENSES Table (Current)
```
expense_id (PK) → Key for referencing expense
├─ partner_id → Link to institution
├─ FileName → Original file name
├─ Category → Type of expense
├─ WorkPackage → WP1-WP7 code
├─ Currency → RWF, EUR, USD
├─ Amount → Numeric amount
├─ ShortDescription → Text description
├─ Date → Expense date (NO month/year extracted)
├─ uploaded_by → User ID who uploaded
├─ status → pending/approved/rejected
├─ approved_by → User ID who approved
├─ approved_at → Timestamp of approval
├─ created_at → Upload timestamp
└─ updated_at → Last update timestamp
```

### MONTHLY_FINANCIAL_REPORTS Table (New)
```
report_id (PK)
├─ partner_id (FK) → Institution
├─ year → 2024
├─ month → 1-12
├─ report_name → RP_FinancialReport_2024_AUGUST
├─ total_amount → Sum of all expenses
├─ total_expenses → Count of expenses
├─ status → draft/submitted/approved/rejected
├─ file_path → Path to PDF/XLSX
├─ created_by → User ID
├─ submitted_at → When submitted
├─ approved_by → User ID
├─ approved_at → When approved
├─ created_at → Timestamp
└─ updated_at → Timestamp
```

---

## Expense Categories & Work Packages

### Categories (8 types)
1. Travel
2. Accommodation
3. Subsistence
4. Equipment
5. Consumables
6. Services for Meetings, Seminars
7. Services for communication/promotion/dissemination
8. Other

### Work Packages (7 types)
| Code | Full Name |
|------|-----------|
| WP1 | Management and coordination |
| WP2 | Collaboration design |
| WP3 | Infrastructures |
| WP4 | Curricula design |
| WP5 | Training and coaching |
| WP6 | Transfer methodologies |
| WP7 | Impact and dissemination |

### Currencies (3 types)
- RWF (Rwandan Francs)
- EUR (Euro)
- USD (US Dollar)

---

## Current Data Flow

```
1. Coordinator accesses /newExpense
   └─ Controller: newExpense()
   └─ View: newexpense.php (form)

2. Submits expense via POST /saveExpense
   └─ Controller: saveExpense()
   └─ Validation: Server-side (comprehensive)
   └─ Model: create_expense($data)
   └─ Result: Inserted in DB with status='pending'

3. Admin views /expenses
   └─ Controller: expenses()
   └─ Model: get_all_expenses()
   └─ View: expenses.php (DataTable)

4. Admin approves/rejects
   └─ Controller: approveExpense() or rejectExpense()
   └─ Model: approve_expense() or reject_expense()
   └─ Result: Status updated, approved_by/approved_at set

5. Final Status: approved/rejected
```

---

## How to Structure Monthly Financial Reports

### View Hierarchy
```
/monthlyReports
├─ List all monthly reports
├─ Group by Year → Month
├─ Show status (draft/submitted/approved)
└─ Action buttons (View, Download PDF, Download Excel, Approve/Reject)

/viewMonthlyReport/{report_id}
├─ Header with report metadata
├─ Summary by Work Package (with percentages)
├─ Summary by Category (with percentages)
├─ Summary by Currency
├─ Detailed expense table (all expenses in month)
├─ Approval workflow section
└─ Download PDF/Excel buttons
```

### Data Aggregation Approach

**Option A: Real-time Grouping (Current Approach)**
```php
// In Controller
$expenses = $this->fmsm_enhanced->get_expenses_by_month($partner_id, 2024, 8, 'approved');

// Group in PHP
$by_wp = [];
foreach($expenses as $exp) {
    $wp = $exp['WorkPackage'];
    if(!isset($by_wp[$wp])) {
        $by_wp[$wp] = ['count' => 0, 'total' => 0];
    }
    $by_wp[$wp]['count']++;
    $by_wp[$wp]['total'] += $exp['Amount'];
}

// In View - Loop through grouped data
foreach($by_wp as $wp => $data) {
    echo "WP: $wp, Count: {$data['count']}, Total: {$data['total']}";
}
```

**Option B: Database Aggregation (Better Performance)**
```php
// In Model
public function get_monthly_summary_by_workpackage($report_id) {
    return $this->db->select('
        WorkPackage,
        COUNT(*) as expense_count,
        SUM(Amount) as total_amount,
        GROUP_CONCAT(Currency) as currencies')
        ->where('report_id', $report_id)
        ->group_by('WorkPackage')
        ->get('expenses')->result_array();
}
```

---

## Key Code Snippets for Implementation

### 1. Extract Month/Year from Date (Migration)
```sql
-- Populate month and year columns for all existing expenses
UPDATE expenses 
SET month = MONTH(Date), 
    year = YEAR(Date) 
WHERE month IS NULL OR year IS NULL;
```

### 2. Get Expenses for Specific Month
```php
public function get_expenses_by_month($partner_id, $year, $month, $status = 'approved') {
    $this->db->select('expenses.*,
        partners.name AS partner_name,
        staff.first_name,
        staff.last_name')
        ->join('partners', 'partners.partner_id=expenses.partner_id', 'left')
        ->join('users', 'users.user_id=expenses.uploaded_by', 'left')
        ->join('staff', 'staff.staff_id=users.staff_id', 'left')
        ->where('expenses.partner_id', $partner_id)
        ->where('expenses.year', $year)
        ->where('expenses.month', $month)
        ->where('expenses.status', $status)
        ->order_by('expenses.Date', 'ASC');
    
    return $this->db->get('expenses')->result_array();
}
```

### 3. Create Monthly Report
```php
public function create_monthly_report($partner_id, $year, $month, $created_by) {
    // Get expenses
    $expenses = $this->get_expenses_by_month($partner_id, $year, $month, 'approved');
    
    if(empty($expenses)) {
        return FALSE;
    }
    
    // Calculate totals
    $total_amount = array_sum(array_column($expenses, 'Amount'));
    $total_expenses = count($expenses);
    
    // Get partner info
    $partner = $this->get_partner_by_id($partner_id);
    
    // Build report name
    $months = ['', 'January', 'February', 'March', 'April', 'May', 'June',
               'July', 'August', 'September', 'October', 'November', 'December'];
    $report_name = $partner['short_name'] . '_FinancialReport_' . $year . '_' . strtoupper($months[$month]);
    
    // Insert report
    $data = array(
        'partner_id' => $partner_id,
        'year' => $year,
        'month' => $month,
        'report_name' => $report_name,
        'total_amount' => $total_amount,
        'total_expenses' => $total_expenses,
        'status' => 'draft',
        'created_by' => $created_by,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->insert('monthly_financial_reports', $data);
    return $this->db->insert_id();
}
```

### 4. Generate Report Summary by Work Package
```php
public function get_report_summary_by_workpackage($report_id) {
    $this->db->select('
        WorkPackage,
        COUNT(*) as expense_count,
        SUM(Amount) as total_amount')
        ->where('report_id', $report_id)
        ->where('status', 'approved')
        ->group_by('WorkPackage')
        ->order_by('WorkPackage', 'ASC');
    
    return $this->db->get('expenses')->result_array();
}
```

### 5. Generate Report Summary by Category
```php
public function get_report_summary_by_category($report_id) {
    $this->db->select('
        Category,
        COUNT(*) as expense_count,
        SUM(Amount) as total_amount')
        ->where('report_id', $report_id)
        ->where('status', 'approved')
        ->group_by('Category')
        ->order_by('Category', 'ASC');
    
    return $this->db->get('expenses')->result_array();
}
```

### 6. Controller Method - Generate Monthly Report
```php
public function generateMonthlyReport() {
    // Check authorization
    if(!$this->auth_manager->can_upload_expenses() && !$this->auth_manager->is_admin()){
        show_error('Access Denied', 403);
    }
    
    $year = $this->input->post('year');
    $month = $this->input->post('month');
    
    // Get partner
    if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
        $partner_id = $this->input->post('partner_id');
    } else {
        $partner_id = $this->session->userdata('fms_partner_id');
    }
    
    // Validate inputs
    if(empty($year) || empty($month) || empty($partner_id)){
        $this->session->set_flashdata('error', 'Please select a valid year and month.');
        redirect('monthlyReports');
        return;
    }
    
    // Create report
    $user_id = $this->session->userdata('fms_user_id');
    $report_id = $this->fmsm_enhanced->create_monthly_report($partner_id, $year, $month, $user_id);
    
    if($report_id){
        $this->session->set_flashdata('success', 'Monthly report generated successfully.');
        redirect('viewMonthlyReport/' . $report_id);
    } else {
        $this->session->set_flashdata('error', 'No approved expenses found for this period.');
        redirect('monthlyReports');
    }
}
```

### 7. File Naming Function
```php
function generate_monthly_report_filename($partner_shortname, $year, $month) {
    $months = ['', 'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
               'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];
    
    return $partner_shortname . '_FinancialReport_' . $year . '_' . $months[$month];
}

// Usage:
$filename = generate_monthly_report_filename('RP', 2024, 8); // RP_FinancialReport_2024_AUGUST
```

---

## Performance Considerations

### Indexes to Add
```sql
-- Prevent N+1 queries when grouping by month
CREATE INDEX idx_expenses_partner_month_year ON expenses(partner_id, month, year);

-- Speed up status filtering
CREATE INDEX idx_expenses_status ON expenses(status);

-- Speed up report lookups
CREATE INDEX idx_monthly_reports_partner_year_month ON monthly_financial_reports(partner_id, year, month);
```

### Query Optimization
- Use GROUP BY at database level (not PHP)
- Use proper JOINs to fetch user info in one query
- Limit result sets with LIMIT/OFFSET for pagination
- Cache monthly totals in monthly_financial_reports table

---

## Testing Checklist for Monthly Reports

### Feature Testing
- [ ] Generate report for month with expenses
- [ ] Generate report for month with NO expenses (error handling)
- [ ] Verify totals calculated correctly
- [ ] Verify grouping by work package correct
- [ ] Verify grouping by category correct
- [ ] Verify grouping by currency correct
- [ ] Download report as PDF
- [ ] Download report as Excel
- [ ] Approve monthly report (admin only)
- [ ] Reject monthly report (admin only)

### Security Testing
- [ ] Coordinator can only view their institution's reports
- [ ] Admin can view all reports
- [ ] Only coordinators and admins can generate reports
- [ ] Only admins can approve/reject
- [ ] User cannot modify approved report

### Data Testing
- [ ] Multiple currencies displayed correctly
- [ ] Percentages calculated correctly
- [ ] Totals match sum of individual expenses
- [ ] Month/year filtering works
- [ ] Partner filtering works
- [ ] Status filtering works

### UI/UX Testing
- [ ] Reports list displays in chronological order
- [ ] Status badges show correct colors
- [ ] Buttons are appropriately enabled/disabled
- [ ] Download links work
- [ ] Report details are readable
- [ ] Print layout is clean

---

## Future Enhancements

### Phase 2 Features
- Budget vs Actual comparison
- Trend analysis (month-over-month)
- Export templates for specific formats
- Email notifications when reports are approved
- Multi-currency conversion to base currency
- Budget allocation tracking

### Phase 3 Features
- Annual consolidated reports
- Cross-institution comparison
- Budget variance analysis
- Forecast reports
- Automated monthly report generation
- API for external integrations

---

## Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| Report shows 0 expenses | Month/year filters not set | Verify get_expenses_by_month() parameters |
| Wrong totals | Currency not converted | Check if conversion needed |
| Slow report generation | No indexes on month/year | Run migration to add indexes |
| Access denied | Wrong role check | Verify can_upload_expenses() method |
| PDF fails to generate | DOMPDF not configured | Check dompdf library path |

---

**Last Updated:** November 4, 2024
**Version:** 1.0
**Ready for Implementation:** YES

