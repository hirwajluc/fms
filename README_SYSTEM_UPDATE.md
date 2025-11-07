# GREATER FMS - System Update Documentation

## Project Overview

The GREATER FMS (Financial Management System) is a comprehensive web-based application designed to streamline the management of financial records and timesheet files across multiple partner institutions involved in collaborative projects.

---

## System Architecture

### Technology Stack
- **Framework:** CodeIgniter 3.1.13
- **PHP Version:** 8.4.7
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **Server:** XAMPP (Apache + MySQL)

---

## User Roles & Permissions

### 1. Super Admin (Central Coordinator)
**Role Name:** `super_admin`

**Capabilities:**
- Full system access across all institutions
- View and generate institution-wide reports
- Manage all system users and settings
- Access all partner data
- System configuration and oversight

**Permissions:**
```json
{"all": true}
```

### 2. Admin
**Role Name:** `admin`

**Capabilities:**
- Create and manage institution coordinator accounts
- Oversee system configuration
- Institutional setup and management
- View comprehensive reports
- Cannot modify super admin accounts

**Permissions:**
```json
{
  "manage_coordinators": true,
  "system_config": true,
  "view_all_reports": true
}
```

### 3. Institution Coordinator
**Role Name:** `institution_coordinator`

**Capabilities:**
- Upload and manage financial expense files for their institution
- Review and approve/reject timesheets from members
- Create and manage user/member accounts within their institution
- View institution-specific reports
- Limited to own institution data

**Permissions:**
```json
{
  "upload_expenses": true,
  "approve_timesheets": true,
  "manage_members": true,
  "view_institution_reports": true
}
```

### 4. Member/User
**Role Name:** `member`

**Capabilities:**
- Submit individual timesheets for approval
- View own timesheet submissions and approval status
- View own expense records
- Limited to personal data only

**Permissions:**
```json
{
  "submit_timesheet": true,
  "view_own_data": true
}
```

---

## Database Schema

### Core Tables

#### 1. partners
Stores partner institution information
- `partner_id` (PK)
- `name`, `short_name`, `country`
- `contact_person`, `contact_email`, `contact_phone`
- `status` (active/inactive)

#### 2. roles
Defines system roles and permissions
- `role_id` (PK)
- `role_name`, `role_description`
- `permissions` (JSON)

#### 3. staff
Employee/staff information
- `staff_id` (PK)
- `first_name`, `last_name`, `email`
- `partner_id` (FK to partners)
- `position`, `department`
- `greater_role` (project-specific role)

#### 4. users
System user accounts
- `user_id` (PK)
- `staff_id` (FK to staff)
- `email`, `password` (SHA1 hashed)
- `role_id` (FK to roles)
- `level` (access level 1-5)
- `last_login`, `login_attempts`, `locked_until`

#### 5. expenses
Financial expense records
- `expense_id` (PK)
- `partner_id` (FK to partners)
- `FileName`, `Category`, `WorkPackage`
- `Currency`, `Amount`
- `uploaded_by` (FK to users)
- `status` (pending/approved/rejected)
- `approved_by`, `approved_at`

#### 6. timesheets
Monthly timesheet submissions
- `timesheet_id` (PK)
- `user_id` (FK to users) - Member submitting
- `partner_id` (FK to partners)
- `month`, `year`, `total_hours`
- `work_package`, `description`
- `file_path` - Uploaded timesheet file
- `status` (draft/submitted/approved/rejected)
- `approved_by` (FK to users) - Coordinator
- `approval_stamp_path` - Stamped PDF with "Approved"

#### 7. timesheet_details
Daily breakdown of timesheet hours
- `detail_id` (PK)
- `timesheet_id` (FK to timesheets)
- `date`, `hours`, `activity_description`

#### 8. audit_log
System activity tracking
- `log_id` (PK)
- `user_id` (FK to users)
- `action`, `entity_type`, `entity_id`
- `description`, `ip_address`, `user_agent`

#### 9. notifications
User notifications
- `notification_id` (PK)
- `user_id` (FK to users)
- `title`, `message`, `type`
- `is_read`, `read_at`

#### 10. system_settings
System configuration
- `setting_id` (PK)
- `setting_key`, `setting_value`, `setting_type`

---

## Key Features

### 1. File Management

**Expense Files:**
- Upload PDF/Excel files for expense documentation
- Naming convention: `{Partner}-FS-{UniqueID}.pdf`
- Maximum file size: 10MB (configurable)
- Allowed types: PDF, XLSX, XLS, DOC, DOCX
- Storage location: `/assets/uploads/`

**Timesheet Files:**
- Monthly timesheet submission
- PDF format with automatic stamping on approval
- Approved stamp includes: "APPROVED" watermark, date, approver name
- Storage: `/assets/uploads/timesheets/`

### 2. Workflow

**Expense Workflow:**
1. Institution Coordinator uploads expense file
2. File stored with metadata (category, work package, amount)
3. Status: Pending → Approved/Rejected (by admin/super admin)
4. Email notification to uploader

**Timesheet Workflow:**
1. Member creates/submits timesheet
2. Institution Coordinator reviews submission
3. Coordinator approves/rejects with optional comments
4. On approval: System generates stamped PDF
5. Email notification to member
6. Approved timesheets available for reporting

### 3. Reporting System

**Financial Reports (PDF/Excel):**
- By institution/partner
- By work package
- By date range
- By category
- Summary totals by currency

**Timesheet Reports (PDF/Excel):**
- By member
- By institution
- By month/year
- Total hours per work package
- Approval status overview

**Consolidated Reports:**
- Institution-wide summary
- Cross-partner comparison
- Budget vs. actual analysis

### 4. Security Features

**Authentication:**
- SHA1 password hashing (Note: Upgrade to bcrypt recommended)
- Session-based authentication
- Account lockout after 5 failed attempts (30 min lock)
- Last login tracking

**Authorization:**
- Role-based access control (RBAC)
- Permission-level granularity
- Institution data isolation
- Audit logging for all actions

**Data Privacy:**
- Members only see own data
- Coordinators see institution data only
- Super admin has full visibility
- Audit trail for compliance

---

## Installation & Setup

### Prerequisites
- XAMPP with Apache and MySQL
- PHP 8.4.7 or higher
- MySQL 5.7 or higher

### Installation Steps

1. **Database Setup:**
```bash
# Import the database schema
mysql -u root -p < database_schema.sql
```

2. **Configuration:**
Edit `/application/config/database.php`:
```php
'hostname' => 'localhost',
'username' => 'root',
'password' => '',
'database' => 'Sql1800295_2',
```

Edit `/application/config/config.php`:
```php
$config['base_url'] = 'http://localhost/fms/';
```

3. **File Permissions:**
```bash
chmod -R 777 application/logs/
chmod -R 777 assets/uploads/
chmod -R 777 system/session/
```

4. **Default Accounts:**
- **Super Admin:** admin@greater.org / password
- **Coordinator:** john.doe@rp.ac.rw / password
- **Member:** jane.smith@rp.ac.rw / password

---

## Usage Guide

### For Super Admin / Admin

**Dashboard:**
- Access: `http://localhost/fms/`
- View system-wide statistics
- Recent activities across all institutions

**User Management:**
- Create new coordinators
- Manage all user accounts
- Assign roles and permissions
- Lock/unlock accounts

**Reports:**
- Generate institution-wide reports
- Export to PDF/Excel
- Filter by date, partner, work package

### For Institution Coordinator

**Expense Management:**
1. Navigate to "New Expense"
2. Fill in expense details (category, work package, amount)
3. Upload PDF receipt
4. System generates filename: `{Partner}-FS-{ID}`
5. Track approval status

**Timesheet Approval:**
1. View pending timesheets from members
2. Review submitted hours and activities
3. Approve or reject with comments
4. Approved timesheets auto-stamped

**Member Management:**
1. Create member accounts for your institution
2. Reset passwords
3. View member timesheet summaries

### For Members

**Timesheet Submission:**
1. Navigate to "New Timesheet"
2. Select month/year
3. Enter daily hours and activities
4. Upload supporting document (optional)
5. Submit for approval
6. Track approval status

**View Submissions:**
- View all personal timesheets
- Download approved stamped PDFs
- Check rejection reasons

---

## File Structure

```
fms/
├── application/
│   ├── config/
│   │   ├── database.php          # Database configuration
│   │   ├── config.php            # Application configuration
│   │   └── routes.php            # URL routing
│   ├── controllers/
│   │   ├── Fms.php              # Main controller
│   │   └── Login.php            # Authentication controller
│   ├── models/
│   │   ├── Fms_model.php        # Original model (legacy)
│   │   └── Fms_model_enhanced.php  # Enhanced model with new features
│   ├── libraries/
│   │   └── Auth_manager.php     # Role-based access control
│   ├── views/
│   │   └── pages/               # View templates
│   └── logs/                    # Application logs
├── assets/
│   ├── uploads/                 # Uploaded files
│   ├── css/                     # Stylesheets
│   └── js/                      # JavaScript files
├── system/                      # CodeIgniter core (modified for PHP 8.4)
├── database_schema.sql          # Complete database schema
└── README_SYSTEM_UPDATE.md      # This file
```

---

## API Endpoints (Internal)

### Authentication
- `POST /login/login_pro` - User login
- `GET /logout` - User logout

### Expenses
- `GET /expenses` - List all expenses (filtered by role)
- `GET /newExpense` - New expense form
- `POST /saveExpense` - Save expense record
- `POST /approveExpense/{id}` - Approve expense
- `POST /rejectExpense/{id}` - Reject expense

### Timesheets
- `GET /timesheets` - List timesheets (filtered by role)
- `GET /newTimesheet` - New timesheet form
- `POST /saveTimesheet` - Save timesheet
- `POST /submitTimesheet/{id}` - Submit for approval
- `POST /approveTimesheet/{id}` - Approve timesheet
- `POST /rejectTimesheet/{id}` - Reject timesheet

### Users
- `GET /users` - List users (filtered by role)
- `POST /createUser` - Create new user
- `POST /updateUser/{id}` - Update user
- `POST /deleteUser/{id}` - Deactivate user

### Reports
- `GET /reports/expenses` - Expense report
- `GET /reports/timesheets` - Timesheet report
- `GET /reports/export/pdf` - Export to PDF
- `GET /reports/export/excel` - Export to Excel

---

## Customization

### Adding New Work Packages
Edit expense form in `/application/views/pages/newexpense.php`:
```php
<option value="WP8">WP8 - New Work Package</option>
```

### Modifying Expense Categories
Update categories in database or view file:
```php
$categories = array('Travel', 'Accommodation', 'Equipment', ...);
```

### Changing File Size Limits
Update in `/application/config/config.php` or system_settings table:
```sql
UPDATE system_settings SET setting_value = '20' WHERE setting_key = 'max_file_size_mb';
```

---

## Troubleshooting

### Common Issues

**1. "Creation of dynamic property" errors**
- **Fixed:** Added `#[AllowDynamicProperties]` to 19 CodeIgniter core classes
- Compatible with PHP 8.4

**2. Database connection failed**
- Check `/application/config/database.php`
- Ensure MySQL is running in XAMPP
- Verify credentials

**3. File upload fails**
- Check permissions on `/assets/uploads/`
- Verify max file size in PHP settings
- Check disk space

**4. Session errors**
- Verify `/system/session/` has write permissions
- Check session configuration in config.php

---

## Maintenance

### Regular Tasks

**Daily:**
- Monitor audit logs
- Check failed login attempts
- Review pending approvals

**Weekly:**
- Review system logs `/application/logs/`
- Check disk space for uploads
- Backup database

**Monthly:**
- Archive old timesheets
- Generate compliance reports
- Update system settings

### Backup Procedure

```bash
# Database backup
mysqldump -u root Sql1800295_2 > backup_$(date +%Y%m%d).sql

# File backup
tar -czf uploads_$(date +%Y%m%d).tar.gz assets/uploads/
```

---

## Future Enhancements

### Recommended Improvements

1. **Security:**
   - Migrate from SHA1 to bcrypt password hashing
   - Enable CSRF protection
   - Implement 2FA (Two-Factor Authentication)
   - Add encryption key

2. **Features:**
   - Email notifications for all workflow steps
   - Real-time dashboard updates
   - Mobile responsive views
   - Bulk operations (approve multiple timesheets)
   - Advanced search and filters

3. **Reporting:**
   - Interactive charts and graphs
   - Custom report builder
   - Scheduled automated reports
   - Export to more formats (CSV, JSON)

4. **Integration:**
   - API for external systems
   - Calendar integration for timesheets
   - Accounting software integration
   - Cloud storage for files

---

## Support & Contact

For technical support or questions:
- **Documentation:** This file and inline code comments
- **Issues:** Check `/application/logs/` for error details
- **Database:** Refer to `database_schema.sql` for schema details

---

## Version History

### Version 2.0 (Current)
- Added role-based access control
- Implemented timesheet management
- Enhanced reporting system
- Added audit logging
- Fixed PHP 8.4 compatibility
- Improved security features

### Version 1.0 (Legacy)
- Basic expense management
- User authentication
- Simple file uploads

---

## License

This system is developed for the GREATER project. All rights reserved.

---

**Last Updated:** October 2025
**System Version:** 2.0
**Documentation Version:** 1.0
