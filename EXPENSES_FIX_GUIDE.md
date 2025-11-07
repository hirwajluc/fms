# Expenses Module - Quick Fix Guide

## Issue 1: SQL Syntax Error on ALTER TABLE

### Error Message
```
#1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version
for the right syntax to use near 'AFTER approved_at,
  rejection_comments TEXT AFTER status,'
```

### Root Cause
The original SQL syntax used `ADD COLUMN (...)` with parentheses, which is **not valid** in MySQL/MariaDB. The parentheses syntax works in some databases but not MySQL.

### Solution
Use the correct MySQL/MariaDB syntax without parentheses:

**Option 1: One column at a time**
```sql
ALTER TABLE expenses ADD COLUMN approval_comments TEXT AFTER approved_at;
ALTER TABLE expenses ADD COLUMN rejection_comments TEXT AFTER status;
ALTER TABLE expenses ADD COLUMN rejected_at TIMESTAMP NULL AFTER approved_at;
```

**Option 2: All columns in one statement (Recommended)**
```sql
ALTER TABLE expenses
ADD COLUMN approval_comments TEXT AFTER approved_at,
ADD COLUMN rejection_comments TEXT AFTER status,
ADD COLUMN rejected_at TIMESTAMP NULL AFTER approved_at;
```

### How to Execute
1. Open phpMyAdmin or MySQL client
2. Select your database
3. Copy and paste the SQL from Option 2 above
4. Click "Go" or press Enter

### Verification
After running the SQL, verify the columns were added:
```sql
DESCRIBE expenses;
```

You should see:
- `approval_comments` (TEXT)
- `rejection_comments` (TEXT)
- `rejected_at` (TIMESTAMP)

---

## Issue 2: "Invalid date format" Error on Expense Submit

### Error Message
```
Invalid date format.
```

### Root Cause
The date validation was checking for `YYYY-MM-DD` format (with dashes), but the flatpickr date picker was sending `YYYY/MM/DD` format (with slashes).

### Solution
The validation code has been updated to handle both formats:
- `YYYY/MM/DD` (flatpickr default)
- `YYYY-MM-DD` (alternative format)

**Updated Code Location:** `application/controllers/Fms.php` (Lines 1047-1069)

### What Changed
```php
// OLD - Only accepted YYYY-MM-DD
$date_obj = DateTime::createFromFormat('Y-m-d', $date);

// NEW - Accepts both YYYY/MM/DD and YYYY-MM-DD
$date_obj = DateTime::createFromFormat('Y/m/d', $date);
if(!$date_obj){
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
}

// NEW - Converts to database format
$date = $date_obj->format('Y-m-d');
```

### How to Test
1. Go to "New Expense" page
2. Click the date field (it should open a calendar picker)
3. Select a date from the calendar
4. Submit the form - it should now work without "Invalid date format" error

### Date Formats Accepted
- ✅ Using the calendar picker (auto-formats correctly)
- ✅ Manual entry: `2024/11/04` (YYYY/MM/DD with slashes)
- ✅ Manual entry: `2024-11-04` (YYYY-MM-DD with dashes)
- ❌ Manual entry: `11/04/2024` (US format - not supported)
- ❌ Manual entry: `04/11/2024` (EU format - not supported)

---

## Verification Steps

### 1. Test Expense Upload
```
1. Login as Coordinator or Admin
2. Go to "New Expense"
3. Fill in all fields:
   - File Name: "Test Report 2024" (6-30 chars)
   - Category: Select one
   - Work Package: Select WP1-WP7
   - Currency: Select RWF/EUR/USD
   - Amount: 1000
   - Description: "This is a test expense description for testing purposes." (50+ chars)
   - Date: Use date picker or enter 2024/11/04
   - File: Upload PDF/Excel/Word doc
4. Click Submit
5. Should see success message
```

### 2. Test Validation
```
Try submitting with invalid data:
- Amount: "abc" → Should error: "Amount must be a positive number"
- Date: "2025/12/31" (future) → Should error: "Expense date cannot be in the future"
- Amount: "-100" → Should error: "Amount must be a positive number"
- Description: "Short" → Should error: "Description must be between 50 and 500 characters"
```

### 3. Test Approval
```
1. Login as Admin
2. Go to Expenses
3. Find pending expense
4. Click "Approve" button
5. Add comment (optional) and submit
6. Should see success message
7. Expense status should change to "Approved"
```

---

## Common Issues & Solutions

### Issue: Still getting "Invalid date format" error
**Solution:**
- Clear browser cache (Ctrl+Shift+Del on Windows, Cmd+Shift+Del on Mac)
- Make sure you uploaded the updated `Fms.php` controller
- Try using the date picker (calendar) instead of typing manually
- Use format `YYYY/MM/DD` if typing manually

### Issue: SQL error still appears
**Solution:**
- Make sure you're using the corrected SQL syntax (no parentheses)
- Try executing each ALTER TABLE command separately (Option 1)
- If you still get errors, check your MariaDB/MySQL version compatibility

### Issue: File upload still fails
**Solution:**
- Check that upload directory is writable: `chmod 755 assets/uploads/`
- Make sure file is under 10MB
- Check allowed file types: pdf, xlsx, xls, doc, docx
- Try uploading a different file to test

### Issue: All fields required message
**Solution:**
- Make sure you filled in ALL fields including:
  - File Name (required, 6-30 chars)
  - Category (required)
  - Work Package (required)
  - Currency (required)
  - Amount (required, positive number)
  - Description (required, 50-500 chars)
  - Date (required, cannot be in future)
  - File (required, must select a file)

---

## Files That Were Fixed

### 1. `application/controllers/Fms.php`
- **What:** Fixed date format validation
- **Lines:** 1047-1069
- **What changed:** Now accepts both YYYY/MM/DD and YYYY-MM-DD formats

### 2. `EXPENSES_MODULE_IMPROVEMENTS.md`
- **What:** Corrected SQL syntax in documentation
- **Lines:** 234-247 and 392-396
- **What changed:** Removed invalid parentheses syntax from ALTER TABLE

---

## Summary

Both issues have been fixed:

✅ **SQL Syntax Issue** - Corrected documentation with proper MySQL/MariaDB syntax
✅ **Date Format Issue** - Updated validation to accept flatpickr's date format

The expense module should now work correctly. If you encounter any other errors, check:
1. Browser console (F12) for JavaScript errors
2. Application logs in `application/logs/`
3. Database logs for SQL errors

---

**Last Updated:** November 2024
**Status:** Issues Identified and Fixed
**Ready for Testing:** Yes
