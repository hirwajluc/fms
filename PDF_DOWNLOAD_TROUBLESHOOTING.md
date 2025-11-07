# PDF Download & Signature - Troubleshooting Guide

## Issue: "Page Not Found" Error When Downloading PDF

### Cause 1: Routes Not Configured
The routes for the PDF and signature endpoints were missing from the routing configuration.

**Status**: ✅ **FIXED** - Routes have been added to `application/config/routes.php`

Routes added:
```php
$route['downloadTimesheetPDF/(:num)'] = 'fms/downloadTimesheetPDF/$1';
$route['uploadTimesheetSignature'] = 'fms/uploadTimesheetSignature';
```

**Location**: [routes.php:69-70](application/config/routes.php#L69-L70)

---

### Cause 2: Dompdf Library Not Installed or Wrong Version
The PDF generation requires the Dompdf library, but v3.x requires PHP 8.3+.

**Status**: ✅ **FIXED** - Dompdf v2.0.8 has been installed (compatible with PHP 8.2.4)

**Installation Command**:
```bash
composer require dompdf/dompdf:^2.0 --ignore-platform-reqs
```

**Verify Installation**:
```bash
test -f vendor/autoload.php && echo "✓ Composer vendor directory exists"
test -d vendor/dompdf/dompdf && echo "✓ Dompdf v2.0.8 is installed"
php -r "require 'vendor/autoload.php'; new \Dompdf\Dompdf(); echo 'Dompdf loads successfully';"
```

**Version Compatibility**:
- Dompdf v2.0.8 = PHP 7.1+
- Dompdf v3.x = PHP 8.3+ (not compatible with PHP 8.2)

---

### Cause 3: Incorrect Vendor Path
The original code referenced the wrong path for the Dompdf autoloader.

**Status**: ✅ **FIXED** - Path updated in controller

**Before**: `APPPATH . 'libraries/vendor/autoload.php'`
**After**: `APPPATH . '../vendor/autoload.php'`

**Location**: [Fms.php:548](application/controllers/Fms.php#L548)

---

### Cause 4: Missing Database Columns
The signature storage requires database columns that might not exist.

**Status**: ⚠️ **ACTION REQUIRED** - Run the database migration:

```sql
ALTER TABLE timesheets ADD COLUMN IF NOT EXISTS signature_image varchar(255) DEFAULT NULL;
ALTER TABLE timesheets ADD COLUMN IF NOT EXISTS signature_date timestamp NULL DEFAULT NULL;
```

**Migration File**: `database_migration_timesheet_signatures.sql`

---

## Testing the PDF Download

### Step 1: Verify Routes are Loaded
Clear CodeIgniter cache (if enabled):
```bash
rm -rf application/cache/*
```

### Step 2: Test with an Approved Timesheet
1. Navigate to: `Timesheets > View Timesheet`
2. Select a timesheet with status = "Approved"
3. Look for "Download PDF" button (blue button)
4. Click the button

### Step 3: Expected Output
- PDF file should download with filename: `Timesheet-FirstName-LastName-Year-Month.pdf`
- PDF should contain:
  - Project information
  - Employee details
  - All daily time entries
  - Work package summary
  - Signature section (if signed)

### Step 4: If PDF Still Doesn't Download

Check the application logs:
```bash
tail -100 application/logs/log-*.php | grep -A5 -B5 "downloadTimesheet\|PDF\|Dompdf"
```

---

## Common Error Messages and Solutions

### Error: "PDF Library Error: Class not found"
**Cause**: Dompdf not properly installed
**Solution**: Run `composer update` and verify vendor/autoload.php exists

### Error: "Access Denied"
**Cause**: User trying to download someone else's timesheet
**Solution**: Only timesheet owner can download their own timesheet

### Error: "Timesheet not found"
**Cause**: Invalid timesheet ID
**Solution**: Verify the timesheet exists and ID is correct

### Error: "Failed to generate PDF content"
**Cause**: HTML generation failed
**Solution**: Check logs for details about HTML generation failure

---

## Verification Checklist

- [ ] Routes configured in `application/config/routes.php`
- [ ] Dompdf installed: `test -d vendor/dompdf/dompdf`
- [ ] Database columns exist (check with phpMyAdmin)
- [ ] Correct vendor path in Fms.php:548
- [ ] Test with approved timesheet
- [ ] PDF file downloads successfully
- [ ] PDF contains all expected information
- [ ] Signature upload works (add signature to signed PDF)

---

## File Changes Summary

### 1. Routes Configuration
**File**: `application/config/routes.php`
**Lines**: 69-70
**Change**: Added two new routes for PDF download and signature upload

### 2. Controller Methods
**File**: `application/controllers/Fms.php`
**Changes**:
- Line 548: Fixed Dompdf autoloader path
- Lines 550-556: Added try-catch for Dompdf initialization
- Lines 559-577: Added error handling for PDF generation
- Lines 697-756: Already had uploadTimesheetSignature method

### 3. View Files
**File**: `application/views/pages/viewtimesheet.php`
**Changes**:
- Lines 104-122: Added action buttons for PDF download and signature
- Lines 317-346: Added signature modal dialog
- Lines 363-441: Added JavaScript for signature handling

---

## Support

If you still encounter issues:

1. **Check browser console** for JavaScript errors
2. **Check application logs** in `application/logs/`
3. **Verify database connection** using phpMyAdmin
4. **Test Dompdf** directly:
   ```php
   require 'vendor/autoload.php';
   $dompdf = new \Dompdf\Dompdf();
   echo "Dompdf loaded successfully!";
   ```
5. **Clear cache**: Delete `application/cache/*` files

---

## Notes

- PDF generation can be memory-intensive for large timesheets
- Ensure sufficient PHP memory limit: `memory_limit = 256M` (or higher)
- Signature images are stored in `assets/uploads/signatures/`
- PDF files are generated on-the-fly (not cached)
