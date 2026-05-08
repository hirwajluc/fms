# Model Integration Guide

## How to Add Monthly Reports Methods to Your Application

### Step 1: Locate Your Model File

Open: `application/models/Fms_model_enhanced.php`

### Step 2: Find the End of the Class

Scroll to the bottom of the file and find the last method.

### Step 3: Copy and Paste Methods

Open `MONTHLY_REPORTS_MODEL.php` and copy **ALL** methods starting from line 19 (the first `public function create_monthly_report...`)

Paste them at the end of your `Fms_model_enhanced.php` model class, **before** the closing brace `}`.

### Step 4: Verify Structure

Your model file should look like this:

```php
<?php
class Fms_model_enhanced extends CI_Model {

    // ... existing methods ...

    // ==================== MONTHLY REPORTS METHODS ====================

    // All 15 methods from MONTHLY_REPORTS_MODEL.php pasted here

    public function create_monthly_report($partner_id, $year, $month, $created_by, $description = '') {
        // ... method code ...
    }

    public function get_monthly_report($report_id) {
        // ... method code ...
    }

    // ... continue for all 15 methods ...

} // END OF CLASS
?>
```

### Step 5: Test the Integration

Test each method by checking if they're accessible from your controller:

```php
// In your Fms controller, test:
$this->load->model('Fms_model_enhanced');
$report_id = $this->Fms_model_enhanced->create_monthly_report(1, 2024, 11, 1, 'Test Report');
echo "Report created: " . $report_id;
```

---

## Available Methods Summary

After integration, you'll have these methods available:

### Report Management
1. **`create_monthly_report($partner_id, $year, $month, $created_by, $description)`**
   - Creates a new monthly report in draft status
   - Returns: Report ID or FALSE

2. **`get_monthly_report($report_id)`**
   - Retrieves complete report with all attachments and summaries
   - Returns: Full report array

3. **`get_partner_monthly_reports($partner_id, $status = null)`**
   - Lists all reports for a partner
   - Optional status filter
   - Returns: Array of reports

### Attachment Management
4. **`add_report_attachment($report_id, $original_filename, $saved_filename, $file_path, $file_size, $file_type, $item_data, $uploaded_by)`**
   - Adds a file to a report
   - Returns: Attachment ID or FALSE

5. **`delete_report_attachment($attachment_id, $report_id)`**
   - Removes an attachment
   - Returns: TRUE or FALSE

6. **`update_report_attachment($attachment_id, $update_data)`**
   - Updates attachment metadata
   - Returns: TRUE or FALSE

### Verification
7. **`verify_report_attachment($attachment_id, $verified_by, $notes = '')`**
   - Marks attachment as verified (admin)
   - Returns: TRUE or FALSE

8. **`unverify_report_attachment($attachment_id)`**
   - Marks attachment as unverified
   - Returns: TRUE or FALSE

### Summary & Calculations
9. **`recalculate_report_summary($report_id)`**
   - Recalculates all totals, categories, work packages, currencies
   - Called automatically when attachments change
   - Returns: TRUE or FALSE

### Workflow
10. **`submit_monthly_report($report_id, $submitted_by)`**
    - Submits report for approval
    - Returns: TRUE or FALSE

11. **`approve_monthly_report($report_id, $approved_by, $notes = '')`**
    - Approves a submitted report
    - Returns: TRUE or FALSE

12. **`reject_monthly_report($report_id, $comments)`**
    - Rejects a submitted report
    - Returns: TRUE or FALSE

### Export
13. **`generate_report_pdf($report_id)`**
    - Generates PDF export (placeholder)
    - Returns: Path to PDF

14. **`generate_report_excel($report_id)`**
    - Generates Excel export (placeholder)
    - Returns: Path to Excel file

---

## Usage Examples

### Create a New Report
```php
$report_id = $this->Fms_model_enhanced->create_monthly_report(
    1,                          // partner_id
    2024,                       // year
    11,                         // month (November)
    1,                          // created_by user_id
    'November 2024 Financial Report'  // description
);
```

### Upload an Attachment
```php
$item_data = array(
    'item_name' => 'Travel Receipt',
    'item_description' => 'Flight to Kigali',
    'item_type' => 'receipt',
    'document_date' => '2024-11-15',
    'amount' => 150000,
    'currency' => 'RWF',
    'category' => 'Travel',
    'work_package' => 'WP1'
);

$attachment_id = $this->Fms_model_enhanced->add_report_attachment(
    $report_id,                     // report_id
    'Receipt_Travel.pdf',           // original filename
    'attachment_1_1730000000.pdf',  // saved filename
    'assets/uploads/reports/1/',    // file path
    45000,                          // file size
    'pdf',                          // file type
    $item_data,                     // item metadata
    1                               // uploaded_by user_id
);
```

### Get Full Report
```php
$report = $this->Fms_model_enhanced->get_monthly_report($report_id);

// Access report data
echo $report['report_name'];           // RP_FinancialReport_2024_NOVEMBER
echo $report['status'];                // draft, submitted, approved, rejected
echo $report['attachment_count'];      // Number of files

// Access summaries
echo $report['summary']['total_amount_rwf'];    // Total RWF
echo $report['summary']['total_amount_eur'];    // Total EUR
echo $report['summary']['total_amount_usd'];    // Total USD

// Access attachments
foreach($report['attachments'] as $file) {
    echo $file['item_name'];
    echo $file['amount'] . ' ' . $file['currency'];
}

// Access category breakdown
foreach($report['category_summary'] as $cat) {
    echo $cat['category'] . ': ' . $cat['item_count'] . ' items';
}
```

### Submit Report
```php
$this->Fms_model_enhanced->submit_monthly_report($report_id, 1); // user_id 1
```

### Approve Report
```php
$this->Fms_model_enhanced->approve_monthly_report(
    $report_id,
    1,                              // admin user_id
    'Approved. All documents verified.'  // notes
);
```

### Reject Report
```php
$this->Fms_model_enhanced->reject_monthly_report(
    $report_id,
    'Missing receipts for expense category. Please resubmit.'
);
```

---

## Important Notes

1. **Auto-Calculation**: Whenever you add, delete, or update an attachment, the summary is automatically recalculated. You don't need to manually update summaries.

2. **Multi-Currency**: Each attachment can have a different currency (RWF, EUR, USD). Totals are tracked separately per currency.

3. **Verification**: Files can be marked as verified by administrators. The summary tracks total verified vs. total items.

4. **Table Names**: All methods use standard table names (no `_v2` suffix):
   - `monthly_financial_reports`
   - `monthly_report_attachments`
   - `monthly_report_summary`
   - `monthly_report_category_summary`
   - `monthly_report_wp_summary`
   - `monthly_report_currency_summary`

5. **Error Handling**: Methods return `FALSE` on error. Always check return values:
   ```php
   $report_id = $this->Fms_model_enhanced->create_monthly_report(...);
   if($report_id === FALSE) {
       // Handle error
       $this->session->set_flashdata('error', 'Failed to create report');
   }
   ```

---

## Common Integration Tasks

### Add to Existing Method
If you already have monthly report code in your controller, replace all calls like:
- `$this->Fms_model_enhanced->create_monthly_report_v2(...)`
- With: `$this->Fms_model_enhanced->create_monthly_report(...)`

### Update Existing Code
Replace any references to old table names:
- `monthly_financial_reports_v2` → `monthly_financial_reports`
- Method calls remove `_v2` suffix

### Test in Browser
1. Go to: `http://localhost/fms/monthlyReports`
2. Click "Generate New Report"
3. Fill in details and create
4. Try uploading files
5. Submit for approval
6. Login as admin and approve/reject

---

## Debugging

If methods aren't working:

1. **Check Database**: Verify tables exist
   ```sql
   SHOW TABLES LIKE 'monthly%';
   ```

2. **Check Model Loading**: In your controller
   ```php
   $this->load->model('Fms_model_enhanced');
   ```

3. **Check Method Names**: No `_v2` suffix
   ```php
   // Wrong
   $this->Fms_model_enhanced->create_monthly_report_v2(...);

   // Correct
   $this->Fms_model_enhanced->create_monthly_report(...);
   ```

4. **Check Error Log**: Look in `application/logs/`

---

**Integration Time**: ~5 minutes
**Testing Time**: ~10 minutes
**Total Time**: ~15 minutes to be fully operational

---

Last Updated: November 2024
