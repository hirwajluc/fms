# Monthly Financial Reports - V2 Architecture
## File Attachment Model (Like Timesheet System)

**Date:** November 2024
**Status:** Design & Planning Phase
**Comparison:** Similar to how Timesheet system works - ONE record with MANY items

---

## Overview

The V2 model redesigns the monthly reports system to work more like the existing **Timesheet System**, where:
- You **create ONE monthly report** record
- You **upload MULTIPLE evidence files/items** to that report
- Each file/item has its own metadata (amount, category, date, etc)
- The system **automatically aggregates** the files into summaries
- **PDF and Excel** are generated from the report + attachments

---

## Comparison: V1 vs V2

### V1 Architecture (Current Implementation)
```
Approved Expenses → Report Generation → Monthly Report
                                      → Summaries
                                      → PDF/Excel
```
- Aggregates from existing approved expenses table
- Read-only report (cannot add new items)
- Fixed structure based on expenses table
- No file attachments

### V2 Architecture (New Implementation)
```
Create Report → Upload Files/Items → Monthly Report
                                   → Summaries (auto-calculated)
                                   → PDF/Excel
```
- Create empty report, then add items incrementally
- Each item is a file with metadata
- Flexible structure (item_name, amount, category, etc)
- Supports multiple file formats
- Items can be verified before finalization

---

## Database Schema

### 1. monthly_financial_reports_v2 (Main Report)
**Purpose:** Core report record, like a timesheet header

```sql
report_id              INT PRIMARY KEY
partner_id             INT (FK)
report_month           TINYINT(2)    -- 1-12
report_year            INT(4)        -- 2024, 2025
report_name            VARCHAR(255)  -- RP_FinancialReport_2024_NOVEMBER

description            TEXT          -- Report description/summary
total_items            INT           -- Number of attachments

status                 ENUM          -- draft, submitted, approved, rejected
file_path              VARCHAR       -- Path to generated PDF/Excel

created_by             INT (FK)
submitted_by           INT (FK)
approved_by            INT (FK)

created_at             TIMESTAMP
submitted_at           TIMESTAMP
approved_at            TIMESTAMP
rejected_at            TIMESTAMP

approval_notes         TEXT
rejection_comments     TEXT
```

**Similar To:** Timesheet header (contains meta info, status, audit trail)

### 2. monthly_report_attachments (Individual Items)
**Purpose:** Each evidence file, like a timesheet entry row

```sql
attachment_id          INT PRIMARY KEY
report_id              INT (FK)

-- File Info
original_filename      VARCHAR(255)  -- What user uploaded
saved_filename         VARCHAR(255)  -- Sanitized filename
file_path              VARCHAR(500)  -- Full path
file_size              INT           -- Bytes
file_type              VARCHAR(10)   -- pdf, xlsx, doc, etc
mime_type              VARCHAR(100)  -- application/pdf, etc

-- Item Metadata
item_name              VARCHAR(255)  -- Name/title of evidence
item_description       TEXT          -- What this covers
item_type              VARCHAR(100)  -- receipt, invoice, permit, etc

document_date          DATE          -- Date from document
amount                 DECIMAL(15,2) -- If financial (150000)
currency               VARCHAR(3)    -- RWF, EUR, USD
category               VARCHAR(100)  -- Travel, Accommodation, etc
work_package           VARCHAR(10)   -- WP1-WP7

-- Upload Info
uploaded_by            INT (FK)
uploaded_at            TIMESTAMP

-- Verification
verified               BOOLEAN       -- Admin verification
verified_by            INT (FK)
verified_at            TIMESTAMP
verification_notes     TEXT
```

**Similar To:** Timesheet entry row (contains amount, date, category, work package)

### 3. monthly_report_summary (Pre-calculated Totals)
**Purpose:** Fast access to report totals, like timesheet summary

```sql
summary_id             INT PRIMARY KEY
report_id              INT (FK) UNIQUE

total_items            INT           -- Total attachments
total_verified         INT           -- Verified count

total_amount_rwf       DECIMAL(15,2) -- Sum of RWF items
total_amount_eur       DECIMAL(15,2) -- Sum of EUR items
total_amount_usd       DECIMAL(15,2) -- Sum of USD items

categories_count       INT           -- Distinct categories
work_packages_count    INT           -- Distinct WPs

pdf_generated          BOOLEAN       -- PDF exists
excel_generated        BOOLEAN       -- Excel exists
pdf_path               VARCHAR(500)
excel_path             VARCHAR(500)

created_at             TIMESTAMP
updated_at             TIMESTAMP
```

### 4. monthly_report_category_summary
```sql
summary_id             INT PRIMARY KEY
report_id              INT (FK)
category               VARCHAR(100)

item_count             INT           -- How many items in this category
total_amount           DECIMAL(15,2) -- Total amount in this category

UNIQUE(report_id, category)
```

### 5. monthly_report_wp_summary
```sql
summary_id             INT PRIMARY KEY
report_id              INT (FK)
work_package           VARCHAR(10)   -- WP1-WP7

item_count             INT
total_amount           DECIMAL(15,2)

UNIQUE(report_id, work_package)
```

### 6. monthly_report_currency_summary
```sql
summary_id             INT PRIMARY KEY
report_id              INT (FK)
currency               VARCHAR(3)    -- RWF, EUR, USD

total_amount           DECIMAL(15,2)
item_count             INT

UNIQUE(report_id, currency)
```

---

## Workflow

### User Workflow (Coordinator)

```
1. Go to Monthly Reports
   ↓
2. Click "Create New Report"
   - Select Partner, Month, Year
   - Add description
   - Confirm
   ↓
3. Report created (DRAFT status)
   ↓
4. Upload Evidence Files
   - Click "Add File/Item"
   - Upload PDF/Excel/Word doc
   - Fill in metadata:
     * Item Name: "Travel Receipt - Flight"
     * Item Type: "receipt"
     * Amount: 150000
     * Currency: RWF
     * Category: Travel
     * Work Package: WP1
     * Document Date: 2024-11-15
   - Click "Add Item"
   ↓
5. Repeat step 4 for more files (upload multiple items)
   ↓
6. View Report Summary (auto-calculated)
   - Total items: 15
   - Total RWF: 2,500,000
   - Total EUR: 1,500
   - Total USD: 2,000
   - By Category breakdown
   - By Work Package breakdown
   - By Currency breakdown
   ↓
7. Click "Submit for Approval"
   - Status changes to SUBMITTED
   - Send to admin
   ↓
8. Wait for admin approval/rejection
   - If approved: Can export to PDF/Excel
   - If rejected: Can edit items and resubmit
```

### Admin Workflow

```
1. Go to Monthly Reports
   ↓
2. Filter to see SUBMITTED reports
   ↓
3. Click on report to view details
   ↓
4. Review all attachments
   - View file previews if possible
   - Check metadata
   - Verify amounts and categories
   ↓
5. Optional: Verify individual items
   - Click "Verify" on each item
   - Add verification notes
   - Verified count increases
   ↓
6. Either:
   a) Click "Approve Report"
      - Add optional approval notes
      - Status → APPROVED
      - Generate PDF/Excel
      - Available for download/archive

   b) Click "Reject Report"
      - Add required rejection comments
      - Status → REJECTED
      - Send back to coordinator
      - Coordinator can edit/resubmit
```

---

## File Upload Handling

### Supported File Types
- PDF documents
- Excel files (.xlsx, .xls)
- Word documents (.doc, .docx)
- Potentially images (for receipts)

### File Storage
```
assets/uploads/monthly_reports/
├── report_1/
│   ├── attachment_1_1730000000.pdf
│   ├── attachment_2_1730100000.pdf
│   └── attachment_3_1730200000.xlsx
├── report_2/
│   └── ...
```

### File Validation
- Size: Max 10MB per file
- Types: pdf, xlsx, xls, doc, docx
- Filename sanitization
- Virus scan (optional)

---

## Data Aggregation

### Automatic Calculation Flow

```
When item is uploaded:
  ↓
1. Insert into monthly_report_attachments
   ↓
2. Call recalculate_report_summary()
   ↓
   a) Sum all amounts by currency
   b) Group by category (count + total)
   c) Group by work package (count + total)
   d) Group by currency (count + total)
   ↓
3. Update monthly_report_summary
4. Clear and recreate category_summary table
5. Clear and recreate wp_summary table
6. Clear and recreate currency_summary table
   ↓
7. Return to user with updated totals
```

### Example Calculation

If 3 items are uploaded:
```
Item 1: 100,000 RWF, Travel, WP1
Item 2: 200,000 RWF, Accommodation, WP2
Item 3: 500 EUR, Meetings, WP1

Results:
- Total RWF: 300,000
- Total EUR: 500
- Total RWF: 0
- Items: 3

By Category:
- Travel: 1 item, 100,000 RWF
- Accommodation: 1 item, 200,000 RWF
- Meetings: 1 item, 500 EUR

By Work Package:
- WP1: 2 items, 100,500 RWF + 500 EUR
- WP2: 1 item, 200,000 RWF

By Currency:
- RWF: 300,000
- EUR: 500
- USD: 0
```

---

## Model Methods

### Create Report
```php
$report_id = $this->fmsm_enhanced->create_monthly_report_v2(
    $partner_id,    // 1
    $year,          // 2024
    $month,         // 11
    $created_by,    // 1 (user_id)
    $description    // "November 2024 expenses" (optional)
);
```

### Get Report (with attachments)
```php
$report = $this->fmsm_enhanced->get_monthly_report_v2($report_id);
// Returns:
// [
//   'report_id' => 1,
//   'report_name' => 'RP_FinancialReport_2024_NOVEMBER',
//   'status' => 'draft',
//   'attachments' => [
//     {
//       'attachment_id' => 1,
//       'item_name' => 'Travel Receipt',
//       'amount' => 150000,
//       'currency' => 'RWF',
//       'file_path' => '...',
//       ...
//     },
//     ...
//   ],
//   'summary' => { ... },
//   'category_summary' => [ ... ],
//   'wp_summary' => [ ... ],
//   'currency_summary' => [ ... ]
// ]
```

### Add Attachment
```php
$attachment_id = $this->fmsm_enhanced->add_report_attachment(
    $report_id,
    'Receipt_Travel.pdf',        // original_filename
    'attachment_1_1730000000.pdf', // saved_filename
    'assets/uploads/monthly_reports/1/',
    45000,                         // file_size
    'pdf',                         // file_type
    array(
        'item_name' => 'Travel Receipt - Flight',
        'item_description' => 'Flight to Kigali',
        'item_type' => 'receipt',
        'document_date' => '2024-11-15',
        'amount' => 150000,
        'currency' => 'RWF',
        'category' => 'Travel',
        'work_package' => 'WP1'
    ),
    1  // uploaded_by (user_id)
);
// Automatically recalculates summaries
```

### Delete Attachment
```php
$this->fmsm_enhanced->delete_report_attachment($attachment_id, $report_id);
// Automatically recalculates summaries
```

### Update Attachment
```php
$this->fmsm_enhanced->update_report_attachment($attachment_id, array(
    'item_name' => 'Updated name',
    'amount' => 160000,
    'category' => 'Accommodation'
));
// Automatically recalculates summaries
```

### Verify Attachment (Admin)
```php
$this->fmsm_enhanced->verify_report_attachment(
    $attachment_id,
    $admin_user_id,
    'Verified and correct'
);
```

### Submit Report
```php
$this->fmsm_enhanced->submit_monthly_report_v2($report_id, $user_id);
// Status: draft → submitted
```

### Approve Report
```php
$this->fmsm_enhanced->approve_monthly_report_v2(
    $report_id,
    $admin_user_id,
    'Approved - ready for payment'
);
// Status: submitted → approved
```

### Reject Report
```php
$this->fmsm_enhanced->reject_monthly_report_v2(
    $report_id,
    'Please verify amounts and re-upload receipts'
);
// Status: submitted → rejected
```

---

## Controller Methods (Planned)

### Main CRUD
- `monthlyReports()` - List reports
- `viewMonthlyReport($id)` - View detail
- `createMonthlyReport()` - Create new report
- `updateReportDescription()` - Edit description
- `submitMonthlyReport($id)` - Submit for approval

### Attachment Handling
- `uploadReportAttachment($report_id)` - Upload file
- `deleteReportAttachment($attachment_id)` - Remove file
- `updateAttachmentMetadata($attachment_id)` - Edit metadata
- `verifyReportAttachment($attachment_id)` - Admin verify

### Approval Workflow
- `approveMonthlyReport($id)` - Admin approve
- `rejectMonthlyReport($id)` - Admin reject

### Export
- `downloadReportPDF($report_id)` - Generate & download PDF
- `downloadReportExcel($report_id)` - Generate & download Excel

---

## View Structure (Planned)

### monthly_reports.php (List View)
- Create new report form
- Reports table with:
  - Report name
  - Partner
  - Period
  - Item count
  - Status badge
  - Actions (View, Submit, Approve, Reject, Download)

### monthly_report_detail.php (Detail View)
- Report header (ID, Partner, Period, Status)
- Description field
- Attachments table with:
  - Filename
  - Item name
  - Category
  - Work package
  - Amount & currency
  - Document date
  - Verified status
  - Actions (View, Edit, Delete, Verify)
- Summary cards (total items, RWF, EUR, USD)
- Category breakdown table
- Work package breakdown table
- Currency breakdown table
- Approval workflow section (for admin)
- Action buttons (Add Item, Submit, Approve, Reject, Download)

### monthly_report_upload.php (File Upload Form)
- File upload input
- Item metadata form:
  - Item Name
  - Item Type (dropdown)
  - Document Date
  - Amount
  - Currency (dropdown)
  - Category (dropdown)
  - Work Package (dropdown)
- Upload button
- Auto-refresh parent view after upload

---

## Advantages of V2 Over V1

| Aspect | V1 | V2 |
|--------|----|----|
| **Flexibility** | Fixed structure from expenses | Flexible - any file type |
| **Usability** | Must create expenses first | Create report, upload files directly |
| **Control** | Auto-aggregated, user has no control | User adds items, full control |
| **Audit** | No file attachments | Stores original files for proof |
| **Verification** | All-or-nothing | Can verify individual items |
| **Editing** | Cannot add new expenses after report | Can add/edit/delete items anytime (if draft) |
| **Export** | Standard aggregation | Includes actual file attachments |

---

## Implementation Timeline

### Phase 1: Database & Models (Current)
- [x] Design V2 schema (MONTHLY_REPORTS_MIGRATION_V2.sql)
- [x] Create model methods (MONTHLY_REPORTS_MODEL_V2.php)
- [ ] Test model methods

### Phase 2: Controllers & Routes
- [ ] Create controller methods
- [ ] Add routes
- [ ] Test controllers

### Phase 3: Views
- [ ] Create list view
- [ ] Create detail view
- [ ] Create upload form
- [ ] Add file preview functionality

### Phase 4: Export
- [ ] Implement PDF generation
- [ ] Implement Excel generation
- [ ] Add download buttons

### Phase 5: Polish
- [ ] Email notifications
- [ ] File preview (for PDFs)
- [ ] Batch operations
- [ ] Archive functionality

---

## Status & Next Steps

**Current Status:** Design Complete - Ready for Implementation

**What's Provided:**
- ✅ Complete database schema (MONTHLY_REPORTS_MIGRATION_V2.sql)
- ✅ All model methods (MONTHLY_REPORTS_MODEL_V2.php)
- ✅ Architecture documentation (this file)

**Next Steps:**
1. Review and approve the V2 design
2. Execute database migration
3. Add model methods to Fms_model_enhanced.php
4. Implement controller methods
5. Create view templates
6. Implement PDF/Excel generation

---

**Estimated Total Effort:** 20-30 hours
**Recommended:** V2 is better aligned with user needs and existing Timesheet system pattern

---

**Created:** November 2024
**Version:** 2.0 (Design)
**Status:** Ready for Implementation
