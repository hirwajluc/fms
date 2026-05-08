# Timesheet PDF Download & Signature Implementation

## Overview
This document describes the implementation of PDF download and digital signature functionality for the GREATER Project timesheet system.

## Features Implemented

### 1. PDF Download
- **File**: [viewtimesheet.php](application/views/pages/viewtimesheet.php) (lines 105-107)
- **Button**: "Download PDF" (only visible for approved timesheets)
- **Functionality**:
  - Generates a professional PDF document with all timesheet details
  - Includes employee information, daily entries, and work package summary
  - PDF filename: `Timesheet-FirstName-LastName-Year-Month.pdf`
  - Shows signature if already signed

### 2. Signature Upload
- **File**: [viewtimesheet.php](application/views/pages/viewtimesheet.php) (lines 109-112, 317-346)
- **Button**: "Add Signature" (only visible for approved timesheets without signature)
- **Modal Dialog**: Allows users to upload signature image
- **Features**:
  - File validation (JPG, PNG, GIF only)
  - Size limit: 5MB maximum
  - Image preview before upload
  - Stores signature path and upload date

## Database Columns Required

The following columns need to be added to the `timesheets` table:

```sql
ALTER TABLE timesheets ADD COLUMN signature_image varchar(255) DEFAULT NULL COMMENT "Path to signature image file";
ALTER TABLE timesheets ADD COLUMN signature_date timestamp NULL DEFAULT NULL COMMENT "Date when signature was added";
```

**Location**: `/Applications/XAMPP/xamppfiles/htdocs/fms/database_migration_timesheet_signatures.sql`

### How to Apply Migration

**Option 1: Using phpMyAdmin**
1. Go to phpMyAdmin
2. Select your database
3. Click "Import"
4. Upload the `database_migration_timesheet_signatures.sql` file
5. Click "Go"

**Option 2: Using MySQL Command**
```bash
mysql -u root -p database_name < database_migration_timesheet_signatures.sql
```

## Controller Methods

### New Methods in Fms.php

#### 1. downloadTimesheetPDF($timesheet_id)
- **Location**: [Fms.php:529-564](application/controllers/Fms.php#L529-L564)
- **Purpose**: Generate and download timesheet as PDF
- **Access Control**: Users can only download their own timesheets
- **Requirements**: Timesheet must exist
- **Output**: PDF file download

#### 2. generateTimesheetPDF($timesheet, $details, $summary)
- **Location**: [Fms.php:566-695](application/controllers/Fms.php#L566-L695)
- **Purpose**: Generate HTML content for PDF conversion
- **Includes**:
  - Project header information
  - Employee details
  - Daily time entries
  - Work package summary
  - Signature section (if signed)
  - Signature line for employee

#### 3. uploadTimesheetSignature()
- **Location**: [Fms.php:697-756](application/controllers/Fms.php#L697-L756)
- **Purpose**: Handle signature image upload
- **Features**:
  - AJAX request handling
  - File validation (type, size)
  - Creates signature directory if needed
  - Updates database with signature path and date
  - Returns JSON response

## Frontend Features

### View Timesheet Page ([viewtimesheet.php](application/views/pages/viewtimesheet.php))

#### Action Buttons (Lines 105-117)
- **Download PDF**: Downloads timesheet as PDF file
- **Add Signature**: Opens modal for signature upload (if not signed)
- **Signed Status**: Shows green "Signed" badge when signature is present

#### Signature Modal (Lines 317-346)
- **Input**: File upload for signature image
- **Preview**: Shows signature preview after selection
- **Validation**: Client-side validation for file type and size
- **Upload Button**: Submits signature via AJAX

#### JavaScript (Lines 363-441)
- **Image Preview**: Shows selected image before upload
- **File Validation**:
  - Checks file type (JPG, PNG, GIF only)
  - Validates file size (max 5MB)
- **AJAX Upload**: Sends signature to server
- **Success Handler**: Reloads page after successful upload
- **Error Handler**: Displays error messages

## View Updates

### Buttons Only Visible for Approved Timesheets
```php
<?php if($timesheet['status'] == 'approved'): ?>
  <!-- Download PDF button -->
  <!-- Add/View Signature button -->
<?php endif; ?>
```

### Signature Display States
1. **Not Signed**: Shows "Add Signature" button (yellow)
2. **Signed**: Shows "Signed" status button (green, disabled)

## File Upload Structure

### Signature Storage
- **Directory**: `assets/uploads/signatures/`
- **File Format**: PNG (converted during upload)
- **Naming**: `signature-{timesheet_id}-{timestamp}.png`
- **Permissions**: 0755 directory, readable by web server

## External Dependencies

### Dompdf Library
- **Library**: `dompdf/dompdf` v3.1.4
- **Purpose**: Convert HTML to PDF
- **Installation**: `composer require dompdf/dompdf`
- **Location**: `vendor/dompdf/dompdf/`

## Error Handling

### PDF Download Errors
- 404: Timesheet not found
- 403: Access denied (user trying to download someone else's timesheet)

### Signature Upload Errors
- "No file uploaded": No signature file selected
- "File size exceeds 5MB": File too large
- "Invalid file type": Not JPG, PNG, or GIF
- "Failed to save signature": Database update failed

## Testing Checklist

- [ ] Create and approve a timesheet
- [ ] Verify "Download PDF" button appears
- [ ] Download PDF and verify content
- [ ] Verify PDF includes all daily entries
- [ ] Verify work package summary in PDF
- [ ] Click "Add Signature" button
- [ ] Select a signature image
- [ ] Verify preview displays correctly
- [ ] Upload signature
- [ ] Verify signature saved to database
- [ ] Verify "Signed" status shows
- [ ] Download PDF again and verify signature appears
- [ ] Test with invalid file types (should fail)
- [ ] Test with oversized files (should fail)

## Security Considerations

1. **Access Control**: Only owner can download/sign their timesheet
2. **File Validation**: Signature must be image file (type checking)
3. **Size Limits**: Maximum 5MB to prevent abuse
4. **Path Validation**: Uploaded files stored outside web root (best practice)
5. **CSRF**: AJAX requests validated
6. **SQL Injection**: Parameterized queries used

## Future Enhancements

1. **PDF Encryption**: Add password protection to signed PDFs
2. **Digital Signatures**: Use PKI for cryptographic signing
3. **Email Integration**: Email PDF to coordinator on signing
4. **Archive**: Move signed PDFs to archive location
5. **Audit Trail**: Log all signature events
6. **Template Customization**: Allow logo/branding in PDF

## Notes

- Signature image is embedded in the PDF as a visual confirmation
- The implementation uses image upload rather than digital PKI signatures
- Timesheets can only be signed after approval by coordinator
- Signature images are stored as files with database references for easy access
