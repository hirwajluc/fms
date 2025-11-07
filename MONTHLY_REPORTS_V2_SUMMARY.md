# Monthly Reports V2 - Summary & Comparison

**Date:** November 2024
**Status:** Architecture & Design Complete - Ready for Implementation

---

## What Changed?

You said: "No the thing I want is almost like that of timesheet, you create one thing and add many evidence files like the file I told you that is in the root there is its pdf and excel versions"

**Translation:** Create ONE monthly report, then upload MULTIPLE evidence files to it, similar to how the Timesheet system works (create ONE timesheet, add MANY entry rows).

---

## V1 vs V2 Comparison

### V1 (Current Implementation)
```
Workflow:
  Create Report → Aggregate from Approved Expenses → View Report

Files:
  - No file attachments
  - Just aggregated data from expenses table
  - Read-only

Structure:
  - monthly_financial_reports (main)
  - monthly_report_items (aggregated expenses)
  - Summaries: wp, category, currency

Example:
  Report shows totals from expenses table
  Cannot upload additional files
```

### V2 (New Design - Like Timesheet)
```
Workflow:
  Create Report → Upload Evidence Files → Add Metadata → View Report

Files:
  - Multiple file attachments per report
  - Each file has its own metadata
  - Files stored on disk
  - Original files available for download

Structure:
  - monthly_financial_reports_v2 (main report header)
  - monthly_report_attachments (individual files - like timesheet rows)
  - Summaries: wp, category, currency, main summary
  - Summary: overall totals

Example:
  1. Create monthly report (DRAFT)
  2. Upload 15 files:
     - Receipt_Travel.pdf → Travel, WP1, 150,000 RWF
     - Hotel_Invoice.pdf → Accommodation, WP2, 450,000 RWF
     - Permit_Document.xlsx → Other, WP3, 1,000,000 RWF
     - ... more files ...
  3. System auto-calculates:
     - Total: 2,500,000 RWF + 1,500 EUR + 2,000 USD
     - By Category: Travel (150K), Accommodation (450K), ...
     - By Work Package: WP1 (150K), WP2 (450K), WP3 (1M), ...
  4. Submit for approval
  5. Admin verifies files
  6. Approve → Generate PDF/Excel with all files
  7. Download as PDF_2024_NOVEMBER.pdf or Excel_2024_NOVEMBER.xlsx
```

---

## Key Files Created for V2

### 1. Database Migration
**File:** `MONTHLY_REPORTS_MIGRATION_V2.sql`
- 6 tables (vs 5 in V1)
- File attachment table
- Pre-calculated summary tables
- Ready to execute in MySQL

### 2. Model Methods
**File:** `MONTHLY_REPORTS_MODEL_V2.php`
- 15 new methods for V2 operations
- File attachment management
- Automatic summary recalculation
- Verification workflow
- Ready to add to Fms_model_enhanced.php

### 3. Architecture Documentation
**File:** `MONTHLY_REPORTS_V2_ARCHITECTURE.md`
- Complete system design
- Database schema detailed
- Workflow diagrams
- Method signatures
- Example code

---

## Database Changes

### New Tables in V2

```
1. monthly_financial_reports_v2
   - Like timesheet header
   - Basic report info (partner, month, year)
   - Status (draft, submitted, approved, rejected)
   - Audit trail (created, submitted, approved timestamps)

2. monthly_report_attachments
   - Like timesheet entry rows
   - File info (filename, path, size)
   - Metadata (amount, category, work package, date)
   - Verification status
   - Can have 100+ rows per report

3. monthly_report_summary
   - Pre-calculated totals
   - Fast access to overall numbers
   - PDF/Excel generation status

4. monthly_report_category_summary
   - Total items and amount per category

5. monthly_report_wp_summary
   - Total items and amount per work package

6. monthly_report_currency_summary
   - Total amount per currency
```

### Removed from V1
```
- monthly_report_items (was for aggregated expenses)
- No longer needed because attachments table replaces it
```

---

## Model Methods (V2)

All these are included in `MONTHLY_REPORTS_MODEL_V2.php`:

### Create & Get
- `create_monthly_report_v2()` - Create new report (DRAFT)
- `get_monthly_report_v2()` - Get report with all attachments + summaries
- `get_partner_monthly_reports_v2()` - List all reports for partner

### File Management
- `add_report_attachment()` - Upload file + metadata
- `delete_report_attachment()` - Remove file
- `update_report_attachment()` - Edit file metadata
- `verify_report_attachment()` - Admin verification

### Workflow
- `submit_monthly_report_v2()` - Submit for approval
- `approve_monthly_report_v2()` - Admin approve
- `reject_monthly_report_v2()` - Admin reject

### Calculations
- `recalculate_report_summary()` - Auto-update totals
- `generate_report_pdf()` - Create PDF with files
- `generate_report_excel()` - Create Excel with files

---

## User Workflow (V2)

### Coordinator/Staff
```
1. Go to "Monthly Reports"
   ↓
2. Click "Create New Report"
   - Select: Partner, Month (Nov), Year (2024)
   - Add description: "November monthly expenses"
   - Click "Create"
   ↓ Status: DRAFT

3. Upload Evidence Files (one at a time)
   a) Click "Add File/Item"
   b) Select PDF: "Receipt_Travel.pdf"
   c) Fill metadata:
      - Item Name: "Flight Receipt - Kigali"
      - Item Type: "receipt"
      - Amount: 150,000
      - Currency: "RWF"
      - Category: "Travel"
      - Work Package: "WP1"
      - Document Date: "2024-11-15"
   d) Click "Upload Item"

4. Repeat for more files (15 total files uploaded)
   ↓ Report auto-totals as you add files

5. View Report Summary (auto-calculated):
   - Total Items: 15
   - Total RWF: 2,500,000
   - Total EUR: 1,500
   - Total USD: 2,000
   - By Category breakdown
   - By Work Package breakdown

6. Click "Submit for Approval"
   ↓ Status: SUBMITTED

7. Wait for admin action:
   - If APPROVED: Can download PDF/Excel
   - If REJECTED: Can edit/re-upload files and resubmit
```

### Admin Review
```
1. Go to "Monthly Reports"
   ↓
2. Filter: Status = "Submitted"
   ↓
3. Click on report to view
   ↓
4. See all 15 uploaded files in a table:
   - File name
   - Amount
   - Category
   - Work Package
   - Upload date
   - Verified status

5. Can verify individual items:
   - Click "Verify" on Receipt
   - Verified count increases (1/15)

6. Either:
   a) Click "Approve Report"
      - Add optional notes
      - Status → APPROVED
      - PDF/Excel generated
      - Ready to download

   b) Click "Reject Report"
      - Add required feedback
      - Status → REJECTED
      - Coordinator gets it back
```

---

## Example Report (What Gets Generated)

### PDF: RP_FinancialReport_2024_NOVEMBER.pdf

```
┌─────────────────────────────────────────┐
│ GREATER FMS Monthly Financial Report   │
│                                         │
│ Report: RP_FinancialReport_2024_NOVEMBER│
│ Partner: ABC Institution                │
│ Period: November 1-30, 2024             │
│ Status: APPROVED                        │
│                                         │
├─────────────────────────────────────────┤
│ SUMMARY                                 │
│ Total Items: 15                         │
│ Total RWF: 2,500,000                   │
│ Total EUR: 1,500                       │
│ Total USD: 2,000                       │
└─────────────────────────────────────────┘

BY WORK PACKAGE
┌──────────┬────────┬─────────────┐
│ Work Pkg │ Count  │ Amount      │
├──────────┼────────┼─────────────┤
│ WP1      │ 3      │ 500,000 RWF │
│ WP2      │ 5      │ 750,000 RWF │
│ WP3      │ 4      │ 900,000 RWF │
│ WP4      │ 3      │ 350,000 RWF │
└──────────┴────────┴─────────────┘

BY CATEGORY
┌────────────────┬────────┬─────────────┐
│ Category       │ Count  │ Amount      │
├────────────────┼────────┼─────────────┤
│ Travel         │ 4      │ 400,000 RWF │
│ Accommodation  │ 6      │ 900,000 RWF │
│ Meals          │ 3      │ 200,000 RWF │
│ Other          │ 2      │ 1,000,000   │
└────────────────┴────────┴─────────────┘

DETAILED ITEMS
┌────┬──────────────────┬──────────┬─────────┬──────┐
│ # │ Item Name        │ Amount   │ Category│ Date │
├────┼──────────────────┼──────────┼─────────┼──────┤
│ 1  │ Flight Receipt   │ 150,000  │ Travel  │ 11/15│
│ 2  │ Hotel Invoice    │ 450,000  │ Accomm  │ 11/16│
│ 3  │ Meal Receipts    │ 50,000   │ Meals   │ 11/17│
│ 4  │ Permit Document  │ 1,000,000│ Other   │ 11/18│
│ 5  │ ...              │ ...      │ ...     │ ... │
│ 15 │ ...              │ ...      │ ...     │ ... │
└────┴──────────────────┴──────────┴─────────┴──────┘

APPROVAL
Submitted by: John Doe (Nov 15, 2024)
Approved by: Jane Admin (Nov 16, 2024)
Approval Notes: "Verified and correct. Ready for processing"

ATTACHMENTS
[PDF file includes all 15 uploaded documents in appendix]
Page 1-5: Receipts and invoices
Page 6-10: Permits and documents
Page 11-37: Supporting evidence
```

### Excel: RP_FinancialReport_2024_NOVEMBER.xlsx

```
Sheet 1: Summary
┌─────────────────────────────────┐
│ Report Summary                  │
├─────────────────────────────────┤
│ Partner: ABC Institution        │
│ Period: November 2024           │
│ Total Items: 15                 │
│ Total RWF: 2,500,000           │
│ Total EUR: 1,500               │
│ Total USD: 2,000               │
└─────────────────────────────────┘

Sheet 2: Items
Item# | Name | Amount | Currency | Category | WP | Date | File
1     | Flight | 150000 | RWF | Travel | WP1 | 11/15 | Receipt_Travel.pdf
2     | Hotel | 450000 | RWF | Accom | WP2 | 11/16 | Hotel_Invoice.pdf
...

Sheet 3: By Category
Category | Count | Total
Travel | 4 | 400000
Accommodation | 6 | 900000
...

Sheet 4: By Work Package
WP | Count | Total
WP1 | 3 | 500000
WP2 | 5 | 750000
...

Sheet 5: By Currency
Currency | Total
RWF | 2500000
EUR | 1500
USD | 2000
```

---

## Implementation Path

### Step 1: Database (Ready to Execute)
```bash
mysql -u user -p database < MONTHLY_REPORTS_MIGRATION_V2.sql
```
**Files Created:**
- MONTHLY_REPORTS_MIGRATION_V2.sql

### Step 2: Model Methods (Ready to Add)
Copy all methods from `MONTHLY_REPORTS_MODEL_V2.php` into:
- `application/models/Fms_model_enhanced.php`

**Files Provided:**
- MONTHLY_REPORTS_MODEL_V2.php

### Step 3: Controllers (To Do)
Create/update methods in `application/controllers/Fms.php`:
- monthlyReports() - List
- viewMonthlyReport() - Detail
- createMonthlyReport() - Create
- uploadReportAttachment() - File upload
- deleteReportAttachment() - File delete
- submitMonthlyReport() - Submit
- approveMonthlyReport() - Approve
- rejectMonthlyReport() - Reject
- downloadReportPDF() - PDF export
- downloadReportExcel() - Excel export

### Step 4: Views (To Do)
Create view files:
- application/views/pages/monthly_reports.php (list)
- application/views/pages/monthly_report_detail.php (detail)
- application/views/pages/monthly_report_upload_form.php (upload modal)

### Step 5: Routes (To Do)
Add to `application/config/routes.php`:
```php
$route['monthlyReports'] = 'fms/monthlyReports';
$route['viewMonthlyReport/(:num)'] = 'fms/viewMonthlyReport/$1';
// ... etc
```

### Step 6: PDF/Excel Generation (To Do)
Implement in controller:
- Generate PDF with all attached files
- Generate Excel with multiple sheets
- Using existing DOMPDF and PhpSpreadsheet libraries

---

## Timeline

| Phase | Task | Hours | Status |
|-------|------|-------|--------|
| 1 | Database Schema (V2) | 2 | ✅ Done |
| 2 | Model Methods (V2) | 3 | ✅ Done |
| 3 | Architecture Doc | 2 | ✅ Done |
| 4 | Controller Methods | 6 | ⏳ Todo |
| 5 | View Templates | 8 | ⏳ Todo |
| 6 | File Upload Handling | 4 | ⏳ Todo |
| 7 | PDF Generation | 5 | ⏳ Todo |
| 8 | Excel Generation | 4 | ⏳ Todo |
| 9 | Testing | 4 | ⏳ Todo |

**Total Effort:** ~30-40 hours
**Current Progress:** 7 hours done, 23-33 hours remaining

---

## Files Ready to Use

### 1. MONTHLY_REPORTS_MIGRATION_V2.sql
**Status:** Ready to execute
**Action:** Run in MySQL to create database tables
**Contents:** 6 tables with all relationships and indexes

### 2. MONTHLY_REPORTS_MODEL_V2.php
**Status:** Ready to add to Fms_model_enhanced.php
**Action:** Copy all methods into model file
**Contents:** 15 model methods for full CRUD + calculations

### 3. MONTHLY_REPORTS_V2_ARCHITECTURE.md
**Status:** Complete documentation
**Action:** Reference for implementation
**Contents:** Full architecture, workflows, examples

---

## Important Notes

### Unlike V1:
- ❌ NOT aggregating from expenses table
- ❌ NOT auto-generating from existing data
- ✅ USER manually creates report and uploads files
- ✅ FILES stored on disk with metadata
- ✅ METADATA includes amounts, categories, work packages
- ✅ SYSTEM auto-calculates totals as items added
- ✅ FLEXIBLE - can upload any file type (PDF, Excel, Word, etc)
- ✅ COMPARABLE to Timesheet system architecture

### Like Timesheet System:
- ✅ Create ONE main record (Report or Timesheet)
- ✅ Add MANY items/entries to it (Files or TimeRows)
- ✅ Each item has metadata (amounts, dates, categories)
- ✅ System auto-calculates totals
- ✅ Submit for approval workflow
- ✅ Admin can verify individual items
- ✅ Generate PDF/Excel from complete record
- ✅ Supports multiple currencies/categories

---

## Ready to Proceed?

**What's been delivered:**
1. ✅ Complete V2 database schema
2. ✅ All model methods (15 methods)
3. ✅ Complete architecture documentation
4. ✅ Detailed comparison with V1
5. ✅ Example workflows and outputs

**What's next:**
- Choose: Do you want to proceed with V2 implementation?
- Review the architecture and provide feedback
- Approve the design before implementation starts

---

**Created:** November 2024
**Status:** Design Complete - Awaiting Approval
**Version:** V2.0 (File Attachment Model)
