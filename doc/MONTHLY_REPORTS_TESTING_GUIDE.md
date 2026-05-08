# Monthly Financial Reports - Testing Guide

**Phase 2 Implementation - Testing & Verification**

---

## Prerequisites

### 1. Database Setup
Ensure the database migration has been executed:
```bash
mysql -u user -p database < MONTHLY_REPORTS_MIGRATION.sql
```

Verify tables exist:
```sql
SHOW TABLES LIKE 'monthly%';
```

Expected tables:
- monthly_financial_reports
- monthly_report_items
- monthly_report_summary_wp
- monthly_report_summary_category
- monthly_report_summary_currency

### 2. Model Methods Verified
Model methods should be in [application/models/Fms_model_enhanced.php](application/models/Fms_model_enhanced.php) (Lines 556-791)

### 3. Expenses Required
The system aggregates **approved expenses**. You need:
- At least some approved expenses in the database
- For a specific month/year/partner to test generation

If no approved expenses exist:
1. Go to "New Expense"
2. Fill in expense details
3. Submit expense
4. Go to Expenses admin section
5. Approve the expense
6. Now you can generate reports

---

## Testing Scenarios

### Scenario 1: Access Control Testing

#### Test 1.1: Staff User Cannot Access
**Steps:**
1. Login as Staff user
2. Navigate to `/monthlyReports` directly in URL
3. **Expected:** Error 403 (Access Denied)

#### Test 1.2: Coordinator Can Access Own Partner Reports
**Steps:**
1. Login as Coordinator
2. Go to Monthly Reports menu
3. Should see only their partner's reports
4. Try to filter by different partner (if possible)
5. **Expected:** Can only see own partner, no partner selector dropdown

#### Test 1.3: Admin Can Access All Reports
**Steps:**
1. Login as Admin
2. Go to Monthly Reports menu
3. Should see all partners' reports
4. Partner selector dropdown should be visible
5. Can filter by any partner
6. **Expected:** Can see all reports, has partner filter

---

### Scenario 2: Report Generation Testing

#### Test 2.1: Generate Report with Approved Expenses
**Steps:**
1. Login as Coordinator or Admin
2. Go to Monthly Reports
3. In "Generate New Monthly Report" section:
   - Select Partner (if Admin)
   - Select Month: November
   - Select Year: 2024
   - Click "Generate Report"
4. **Expected:**
   - Success message appears
   - Redirect to new report detail page
   - Report shows generated data with summaries

#### Test 2.2: Generate Report with No Approved Expenses
**Steps:**
1. Login as Coordinator
2. For a partner with no approved expenses
3. Try to generate report for that month
4. **Expected:**
   - Error message: "No approved expenses found for this month/partner"
   - Redirect back to reports list

#### Test 2.3: Generate Duplicate Report
**Steps:**
1. Generate a report for Nov 2024
2. Try to generate another report for the same Nov 2024
3. **Expected:**
   - New report created (allowed - may add validation in future)
   - Or error message if duplicate prevention added

---

### Scenario 3: Report Display Testing

#### Test 3.1: Report List View Displays Correctly
**Steps:**
1. Generate at least 3 reports for different months
2. Go to Monthly Reports list
3. Check:
   - [ ] All reports appear in table
   - [ ] Report names are correct (RP_FinancialReport_2024_NOVEMBER format)
   - [ ] Expense counts display correctly
   - [ ] Currency totals show (RWF, EUR, USD)
   - [ ] Status badges show correct status
   - [ ] Creator names display
4. **Expected:** All information displays correctly

#### Test 3.2: Report Detail View - Complete Information
**Steps:**
1. Click on a report to view details
2. Check all sections:
   - [ ] Report header info (ID, Partner, Period, Status)
   - [ ] Audit trail (Created, Submitted, Approved timestamps)
   - [ ] Executive summary cards (Total expenses, RWF, EUR, USD)
   - [ ] Work package summary table with percentages
   - [ ] Category summary table with percentages
   - [ ] Currency summary table
   - [ ] Detailed expenses table with all columns
3. **Expected:** All sections display with correct data

#### Test 3.3: Empty Report Handling
**Steps:**
1. Create a report with no expenses (edge case)
2. View detail page
3. Check:
   - [ ] Summary cards show 0 values
   - [ ] Empty state messages appear in tables
   - [ ] No errors or crashes
4. **Expected:** Graceful handling of empty data

---

### Scenario 4: Report Workflow Testing

#### Test 4.1: Draft → Submitted → Approved Flow
**Steps:**
1. Create new report (starts in Draft status)
2. In report detail page, click "Submit for Approval" button
3. **Expected:** Status changes to "Submitted", Submit button disappears
4. Login as Admin
5. Go to Monthly Reports
6. Find the submitted report
7. View the report
8. Scroll to "Approval Workflow" section
9. Click "Approve Report"
10. Modal appears - enter optional notes
11. Click "Confirm Approval"
12. **Expected:**
    - Status changes to "Approved"
    - Approval timestamp shown
    - Admin name shown as approver
    - Notes displayed

#### Test 4.2: Draft → Submitted → Rejected → Edit → Resubmit
**Steps:**
1. Create new report (Draft status)
2. Submit for approval (Submitted status)
3. Login as Admin
4. View report
5. Click "Reject Report"
6. Modal appears - enter rejection comments (required)
7. Click "Confirm Rejection"
8. **Expected:**
    - Status changes to "Rejected"
    - Rejection reason displays
    - Rejection timestamp shown
9. Login as Coordinator
10. View report (should have "Submit for Approval" button again)
11. Click "Submit for Approval"
12. **Expected:**
    - Status changes back to "Submitted"
    - Submitted timestamp updated
    - Back in approval workflow

#### Test 4.3: Invalid State Transitions
**Steps:**
1. Try to approve an approved report (shouldn't be possible via normal UI)
2. Try to reject a draft report directly
3. **Expected:** Error messages preventing invalid transitions

---

### Scenario 5: Filter Testing

#### Test 5.1: Filter by Status
**Steps:**
1. Have multiple reports with different statuses
2. Go to Monthly Reports list
3. In Filter section, select Status: "Draft"
4. Click Filter
5. **Expected:** Only draft reports shown
6. Try other statuses (Submitted, Approved, Rejected)
7. **Expected:** Correct filtering for each status

#### Test 5.2: Filter by Month
**Steps:**
1. Have reports for multiple months
2. Select Month: "November"
3. Click Filter
4. **Expected:** Only November reports shown
5. Try other months
6. **Expected:** Correct filtering

#### Test 5.3: Filter by Year
**Steps:**
1. Have reports for different years (if possible)
2. Select Year: "2024"
3. Click Filter
4. **Expected:** Only 2024 reports shown

#### Test 5.4: Combine Filters
**Steps:**
1. Select Status: "Approved" AND Month: "November" AND Year: "2024"
2. Click Filter
3. **Expected:** Only approved reports from November 2024 shown

#### Test 5.5: Reset Filters
**Steps:**
1. Apply any filters
2. Click "Reset" button
3. **Expected:** All filters cleared, all reports shown again

---

### Scenario 6: Data Accuracy Testing

#### Test 6.1: Expense Count Matches
**Steps:**
1. Create report with 10 approved expenses
2. View report detail
3. In executive summary, check "Total Expenses" count
4. Scroll to "Detailed Expenses" table
5. Count expenses manually
6. **Expected:** Count matches (10)

#### Test 6.2: Currency Totals Correct
**Steps:**
1. Create expenses:
   - 2 expenses for 100 RWF each = 200 RWF
   - 1 expense for 50 EUR = 50 EUR
   - 1 expense for 25 USD = 25 USD
2. Approve all expenses
3. Generate report
4. Check totals:
   - RWF: 200
   - EUR: 50
   - USD: 25
5. **Expected:** Totals match exactly

#### Test 6.3: Work Package Aggregation
**Steps:**
1. Create expenses distributed across work packages:
   - WP1: 100 RWF, 100 RWF (total: 200 RWF)
   - WP2: 50 EUR (total: 50 EUR)
   - WP3: 25 USD, 25 USD (total: 50 USD)
2. Generate report
3. Check Work Package summary:
   - WP1: 200 RWF, count: 2
   - WP2: 50 EUR, count: 1
   - WP3: 50 USD, count: 2
4. **Expected:** Correct aggregation

#### Test 6.4: Category Aggregation
**Steps:**
1. Create expenses in different categories
2. Generate report
3. Check Category summary table
4. Verify counts and amounts match
5. **Expected:** Correct aggregation

#### Test 6.5: Percentage Calculations
**Steps:**
1. Generate report
2. Check percentage column in Work Package summary
3. Manually verify one percentage:
   - WP1 amount / Total all amounts * 100
4. **Expected:** Percentage calculated correctly

---

### Scenario 7: User Experience Testing

#### Test 7.1: Responsive Design Mobile
**Steps:**
1. View monthly_reports.php on mobile device (375px width)
2. Check:
   - [ ] Generate form is readable
   - [ ] Filter form is usable
   - [ ] Table scrolls horizontally (not squished)
   - [ ] Buttons are touchable (44px+ height)
3. View monthly_report_detail.php on mobile
4. Check:
   - [ ] Cards stack vertically
   - [ ] Tables scroll horizontally
   - [ ] Modals display properly
5. **Expected:** Mobile-friendly layout

#### Test 7.2: Flash Messages
**Steps:**
1. Generate a report successfully
2. **Expected:** Green success message appears and can be dismissed
3. Try to generate with missing fields
4. **Expected:** Red error message appears
5. Click X button on message
6. **Expected:** Message dismisses

#### Test 7.3: Modal Forms
**Steps:**
1. View report list
2. Click action dropdown on any submitted report
3. Click "Approve" (if admin)
4. **Expected:** Modal appears with notes textarea
5. Enter some notes
6. Click "Confirm Approval"
7. **Expected:** Form submits, modal closes, page refreshes
8. Check approval was recorded

#### Test 7.4: Confirmation Dialogs
**Steps:**
1. On report detail page, click "Submit for Approval" button
2. **Expected:** Confirmation dialog appears
3. Click "Cancel"
4. **Expected:** Dialog closes, no submission
5. Click "Submit for Approval" again
6. Click "OK"
7. **Expected:** Form submits, status changes

---

### Scenario 8: Security Testing

#### Test 8.1: Direct URL Access
**Steps:**
1. Login as Staff user
2. Type in URL: `/viewMonthlyReport/1`
3. **Expected:** Error 403 (Access Denied)

#### Test 8.2: Coordinator Partner Restriction
**Steps:**
1. Login as Coordinator for Partner A
2. Type in URL: `/viewMonthlyReport/[ReportIDFromPartnerB]`
3. **Expected:** Error 403 (Access Denied)

#### Test 8.3: Admin Approval Access
**Steps:**
1. Login as Coordinator
2. Try to directly access approve action
3. Navigate to `/approveMonthlyReport/1`
4. **Expected:** Error 403 (Access Denied)

#### Test 8.4: CSRF Protection
**Steps:**
1. Check network requests when submitting forms
2. **Expected:** CSRF token present in POST requests

#### Test 8.5: XSS Prevention
**Steps:**
1. Try to inject script in rejection comments:
   `<script>alert('XSS')</script>`
2. Submit rejection
3. **Expected:** Script displays as text, not executed

---

### Scenario 9: Error Handling

#### Test 9.1: Invalid Report ID
**Steps:**
1. Navigate to `/viewMonthlyReport/99999` (non-existent ID)
2. **Expected:** Error 404 (Report not found)

#### Test 9.2: Database Error Handling
**Steps:**
1. Simulate database error (optional - stop MySQL briefly)
2. Try to generate report
3. **Expected:** Graceful error message

#### Test 9.3: Session Timeout
**Steps:**
1. Leave page open for longer than session timeout
2. Try to submit form
3. **Expected:** Redirect to login or error message

---

## Test Data Setup

### Quick Setup Script

**Step 1: Create test partner**
```sql
INSERT INTO partners (name, email) VALUES ('Test Partner', 'test@partner.com');
SET @partner_id = LAST_INSERT_ID();
```

**Step 2: Create test expenses**
```sql
INSERT INTO expenses (partner_id, category, work_package, currency, amount,
                     description, expense_date, status, report_month, report_year,
                     created_at, created_by, uploaded_by)
VALUES
(@partner_id, 'Travel', 'WP1', 'RWF', 100000, 'Test Travel Expense November',
 '2024-11-15', 'approved', 11, 2024, NOW(), 1, 1),
(@partner_id, 'Accommodation', 'WP2', 'RWF', 150000, 'Test Accommodation Expense November',
 '2024-11-16', 'approved', 11, 2024, NOW(), 1, 1),
(@partner_id, 'Services for Meetings', 'WP3', 'EUR', 100, 'Test Services November',
 '2024-11-17', 'approved', 11, 2024, NOW(), 1, 1);
```

**Step 3: Verify expenses**
```sql
SELECT * FROM expenses
WHERE partner_id = @partner_id
AND report_year = 2024
AND report_month = 11
AND status = 'approved';
```

---

## Automated Testing Checklist

### All Routes Working
- [ ] /monthlyReports - GET
- [ ] /viewMonthlyReport/1 - GET
- [ ] /generateMonthlyReport - POST
- [ ] /generateMonthlyReport/1/2024/11 - GET
- [ ] /submitMonthlyReport/1 - POST
- [ ] /approveMonthlyReport/1 - POST
- [ ] /rejectMonthlyReport/1 - POST

### All Views Rendering
- [ ] monthly_reports.php renders without errors
- [ ] monthly_report_detail.php renders without errors
- [ ] No PHP warnings or notices
- [ ] No undefined variable errors

### All Database Queries Working
- [ ] Partner lookup queries work
- [ ] User lookup queries work
- [ ] Report retrieval queries work
- [ ] Status update queries work

### All Access Controls Working
- [ ] Staff denied access
- [ ] Coordinator limited to own partner
- [ ] Admin has full access
- [ ] All role checks working

---

## Performance Benchmarks

### Expected Response Times

- **monthlyReports list page:** < 500ms
- **monthlyReports detail page:** < 750ms
- **Generate report:** < 2 seconds
- **Submit/Approve/Reject:** < 500ms

### Test with Load Generator (Optional)

Use Apache Bench:
```bash
ab -n 100 -c 10 http://localhost/fms/monthlyReports
```

---

## Troubleshooting

### Issue: Routes not working
**Solution:** Clear browser cache, reload /monthlyReports
```
Ctrl+Shift+Delete on Windows
Cmd+Shift+Delete on Mac
```

### Issue: Access Denied on valid report
**Solution:** Check session user_id and partner_id
```php
// In controller, add:
echo "User ID: " . $this->session->userdata('fms_user_id');
echo "Partner ID: " . $this->session->userdata('fms_partner_id');
```

### Issue: Empty reports tables
**Solution:** Check if approved expenses exist
```sql
SELECT * FROM expenses WHERE status = 'approved' AND report_month = 11;
```

### Issue: Percentages showing 0%
**Solution:** Verify currency totals are being calculated
```sql
SELECT total_amount_rwf, total_amount_eur, total_amount_usd
FROM monthly_financial_reports LIMIT 1;
```

---

## Success Criteria

All of the following must pass for successful deployment:

- ✅ All 8 controller methods working
- ✅ Both views rendering correctly
- ✅ All 7 routes defined and accessible
- ✅ Access control properly restricting users
- ✅ Report generation aggregating expenses correctly
- ✅ Status workflow enforcing valid transitions
- ✅ Data accuracy verified (counts, totals, percentages)
- ✅ Error handling graceful
- ✅ Mobile responsive design working
- ✅ No JavaScript console errors
- ✅ No PHP warnings or notices
- ✅ Database queries optimized

---

**Testing Guide Created:** November 2024
**For:** Monthly Financial Reports Phase 2
**Status:** Ready for Testing
