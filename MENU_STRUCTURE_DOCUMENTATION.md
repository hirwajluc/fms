# GREATER FMS Menu Structure - Complete Documentation

**Date:** November 2024
**Status:** Updated with all new features
**File:** [application/views/pages/menu.php](application/views/pages/menu.php)

---

## Overview

The application menu has been updated to include all newly implemented features (Timesheets, Expenses, Monthly Reports) with proper role-based access control.

---

## Menu Structure

### 1. HOME SECTION
**Access:** All Roles (Staff, Coordinator, Admin, Super Admin)
```
Home [Home Icon]
  └─ Link: /
  └─ Icon: ti-smart-home
```

---

### 2. TIMESHEETS
**Access:** All Roles (Staff, Coordinator, Admin, Super Admin)
```
Timesheets [Clock Icon]
  └─ Link: /timesheets
  └─ Icon: ti-clock
  └─ Active Pages:
     ├─ timesheets (list view)
     ├─ newTimesheet (create view)
     ├─ viewTimesheet (detail view)
     └─ editTimesheet (edit view)
```

**What's Available:**
- All users can view their own timesheets
- All users can create new timesheets
- Coordinators/Admins can see all timesheets
- Can import from Excel
- Can download as PDF
- Can add signature
- Can submit for approval

---

### 3. EXPENSES
**Access:** Coordinator, Admin, Super Admin
**NOT Visible To:** Staff Members
```
Expenses [Receipt Icon]
  └─ Link: /expenses
  └─ Icon: ti-receipt
  └─ Active Pages:
     ├─ expenses (list view)
     ├─ newExpense (create form)
     └─ saveExpense (process form)
```

**What's Available:**
- Upload expense documents (PDF, Excel, Word)
- Fill in amount, category, work package
- Select currency (RWF, EUR, USD)
- Submit for admin approval
- View approval status
- Admins can approve/reject with comments

**Role Restrictions:**
- **Coordinator:** Can create/view own partner's expenses
- **Admin/Super Admin:** Can create/view/approve all expenses

---

### 4. MONTHLY REPORTS (NEW - V2)
**Access:** Coordinator, Admin, Super Admin
**NOT Visible To:** Staff Members
```
Monthly Reports [File Text Icon]
  └─ Link: /monthlyReports
  └─ Icon: ti-file-text
  └─ Active Pages:
     ├─ monthlyReports (list view with filters)
     ├─ viewMonthlyReport (detail view with all items)
     └─ generateMonthlyReport (create new report)
```

**What's Available (V2 - File Attachment Model):**
- Create new monthly report for specific month/year
- Upload multiple evidence files (PDF, Excel, Word, etc)
- Each file has metadata:
  - Item Name
  - Item Type (receipt, invoice, permit, etc)
  - Amount & Currency
  - Category (Travel, Accommodation, etc)
  - Work Package (WP1-WP7)
  - Document Date
- System auto-calculates:
  - Total items
  - Totals by currency (RWF, EUR, USD)
  - Breakdown by category
  - Breakdown by work package
- Submit for admin approval
- Admin can verify individual items
- Admin can approve/reject
- Download as PDF or Excel with all files

**Status Workflow:**
- Draft → Submitted → Approved/Rejected
- Can edit if rejected and resubmit

**Role Restrictions:**
- **Coordinator:** Can create/view own partner's reports
- **Admin/Super Admin:** Can create/view/approve all reports

---

### 5. WORK PACKAGES (Dropdown Menu)
**Access:** Coordinator, Admin, Super Admin
**NOT Visible To:** Staff Members
```
Work Packages [Folder Icon]
  ├─ WP 1 - Management
  ├─ WP 2 - Collaboration
  ├─ WP 3 - Implementation
  ├─ WP 4 - Support
  ├─ WP 5 - Training
  ├─ WP 6 - Monitoring
  └─ WP 7 - Evaluation
```

**Note:** Currently placeholder menu items (not implemented)

---

### 6. ADMINISTRATION SECTION HEADER
**Access:** Coordinator, Admin, Super Admin
**NOT Visible To:** Staff Members

Divider/Header: "ADMINISTRATION"

---

### 7. USERS MANAGEMENT
**Access:** Coordinator, Admin, Super Admin
**NOT Visible To:** Staff Members
```
Users [Users Icon]
  └─ Link: /users
  └─ Icon: ti-users
  └─ Active Pages:
     ├─ users (list view)
     ├─ newUser (create form)
     └─ editUser (edit form)
```

**What's Available:**
- Create new user accounts
- View all users
- Edit user details
- Manage user roles and permissions
- View user activity

---

### 8. STAFF MANAGEMENT
**Access:** Coordinator, Admin, Super Admin
**NOT Visible To:** Staff Members
```
Staff [User Check Icon]
  └─ Link: /staff
  └─ Icon: ti-user-check
  └─ Active Pages:
     ├─ staff (list view)
     └─ newStaff (create form)
```

**What's Available:**
- Create new staff records
- View all staff members
- Manage staff assignments
- Track staff details

---

### 9. ADMIN ONLY SECTION HEADER
**Access:** Admin, Super Admin ONLY
**NOT Visible To:** Coordinator or Staff

Divider/Header: "ADMIN"

---

### 10. SETTINGS (Dropdown Menu - Placeholder)
**Access:** Admin, Super Admin ONLY
**NOT Visible To:** Coordinator or Staff
```
Settings [Settings Icon]
  ├─ System Settings
  └─ Reports & Analytics
```

**Note:** Currently placeholder menu items (for future implementation)

---

## Role-Based Access Matrix

| Menu Item | Staff | Coordinator | Admin | Super Admin |
|-----------|-------|-------------|-------|------------|
| Home | ✅ | ✅ | ✅ | ✅ |
| Timesheets | ✅ | ✅ | ✅ | ✅ |
| Expenses | ❌ | ✅ | ✅ | ✅ |
| Monthly Reports | ❌ | ✅ | ✅ | ✅ |
| Work Packages | ❌ | ✅ | ✅ | ✅ |
| Users | ❌ | ✅ | ✅ | ✅ |
| Staff | ❌ | ✅ | ✅ | ✅ |
| Settings (Admin) | ❌ | ❌ | ✅ | ✅ |

---

## Active Menu Highlighting

Menu items are highlighted as "active" when viewing related pages:

### Timesheets Active Pages
```php
timesheets
newTimesheet
viewTimesheet
editTimesheet
```

### Expenses Active Pages
```php
expenses
newExpense
saveExpense
```

### Monthly Reports Active Pages
```php
monthlyReports
viewMonthlyReport
generateMonthlyReport
```

### Users Active Pages
```php
users
newUser
editUser
```

### Staff Active Pages
```php
staff
newStaff
```

---

## Icon Reference

| Icon Code | Icon Name | Used For |
|-----------|-----------|----------|
| ti-smart-home | Home | Home link |
| ti-clock | Clock | Timesheets |
| ti-receipt | Receipt | Expenses |
| ti-file-text | File/Document | Monthly Reports |
| ti-folder | Folder | Work Packages |
| ti-users | Users | User Management |
| ti-user-check | User Check | Staff Management |
| ti-settings | Settings | Settings/Config |
| ti-arrow-right | Arrow Right | Submenu items |

---

## Code Implementation Details

### Access Control Checks

**For Coordinator, Admin, Super Admin:**
```php
<?php if($this->auth_manager->is_super_admin() ||
        $this->auth_manager->is_admin() ||
        $this->auth_manager->is_coordinator()): ?>
  <!-- Menu item here -->
<?php endif; ?>
```

**For Admin and Super Admin Only:**
```php
<?php if($this->auth_manager->is_super_admin() ||
        $this->auth_manager->is_admin()): ?>
  <!-- Menu item here -->
<?php endif; ?>
```

### Active Page Detection

```php
<?=($this->router->fetch_method()=='expenses' ||
   $this->router->fetch_method()=='newExpense')?'active':'';?>
```

Checks current controller method and applies 'active' class if matching.

---

## Menu Structure in Code

File: `application/views/pages/menu.php`

### Structure Hierarchy:
```
<aside id="layout-menu">                    <!-- Main menu container -->
  <div class="container-xxl">               <!-- Bootstrap container -->
    <ul class="menu-inner">                 <!-- Menu list -->
      <li class="menu-item">                <!-- Each menu item -->
        <a href="" class="menu-link">       <!-- Menu link -->
          <i class="menu-icon">             <!-- Icon -->
          <div data-i18n="">                <!-- Translatable text -->
        </a>

        <!-- Optional submenu -->
        <ul class="menu-sub">
          <li class="menu-item">            <!-- Submenu item -->
            ...
          </li>
        </ul>
      </li>
    </ul>
  </div>
</aside>
```

---

## Internationalization (i18n)

Menu items use `data-i18n` attributes for translation support:

```php
<div data-i18n="Home">Home</div>
<div data-i18n="Timesheets">Timesheets</div>
<div data-i18n="Expenses">Expenses</div>
<div data-i18n="MonthlyReports">Monthly Reports</div>
<div data-i18n="WorkPackages">Work Packages</div>
<div data-i18n="Users">Users</div>
<div data-i18n="Staff">Staff</div>
<div data-i18n="Settings">Settings</div>
```

Translation files should include these keys for multi-language support.

---

## Menu Sections Explained

### Main Content Section (Always Visible)
1. **Home** - Available to all users
2. **Timesheets** - Available to all users
3. **Expenses** - Available to coordinators+
4. **Monthly Reports** - Available to coordinators+
5. **Work Packages** - Available to coordinators+

### Administration Section (Divider)
- **Users** - User account management
- **Staff** - Staff record management

### Admin Only Section (Divider)
- **Settings** - System configuration and analytics

---

## User Journey by Role

### Staff Member
```
Home → Timesheets only
  ├─ View own timesheets
  ├─ Create new timesheet
  ├─ View timesheet details
  └─ Download PDF with signature
```

### Coordinator
```
Home → Timesheets → Expenses → Monthly Reports → Work Packages
        ↓            ↓           ↓
        (All)    (Own Partner)  (Own Partner)

  + Users & Staff Management
```

### Admin
```
Home → Timesheets → Expenses → Monthly Reports → Work Packages
        ↓            ↓           ↓
        (All)        (All)       (All)

  + Users & Staff Management
  + Settings/Admin Panel
```

### Super Admin
```
All of the above (Admin) + Full System Control
```

---

## Customization Guide

### To Add New Menu Item

1. **For coordinators and above:**
```php
<?php if($this->auth_manager->is_super_admin() ||
        $this->auth_manager->is_admin() ||
        $this->auth_manager->is_coordinator()): ?>
<li class="menu-item <?=($this->router->fetch_method()=='new_feature')?'active':'';?>">
  <a href="<?=base_url('new_feature');?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-icon-name"></i>
    <div data-i18n="NewFeature">New Feature</div>
  </a>
</li>
<?php endif; ?>
```

2. **For admins only:**
```php
<?php if($this->auth_manager->is_super_admin() ||
        $this->auth_manager->is_admin()): ?>
<li class="menu-item">
  ...
</li>
<?php endif; ?>
```

### To Add Submenu

```php
<li class="menu-item">
  <a href="javascript:void(0)" class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-folder"></i>
    <div data-i18n="Parent">Parent Menu</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item">
      <a href="<?=base_url('child');?>" class="menu-link">
        <i class="menu-icon tf-icons ti ti-arrow-right"></i>
        <div data-i18n="Child">Child Item</div>
      </a>
    </li>
  </ul>
</li>
```

---

## Current Menu Layout (Visual)

```
┌─────────────────────────────────────────────────┐
│ Home | Timesheets | Expenses | Monthly Reports │
│        Work Packages (dropdown)                 │
│ ────────────────────────────────────────────── │
│ ADMINISTRATION                                  │
│ Users | Staff                                   │
│ ────────────────────────────────────────────── │
│ ADMIN (if admin/super admin)                   │
│ Settings (dropdown)                             │
└─────────────────────────────────────────────────┘
```

---

## Testing Menu Access

### Test as Staff User:
- ✅ Should see: Home, Timesheets
- ❌ Should NOT see: Expenses, Monthly Reports, Work Packages, Users, Staff, Settings

### Test as Coordinator:
- ✅ Should see: Home, Timesheets, Expenses, Monthly Reports, Work Packages, Users, Staff
- ❌ Should NOT see: Settings (Admin Only)

### Test as Admin:
- ✅ Should see: All menu items
- ✅ Should see: Settings (Admin section)

### Test as Super Admin:
- ✅ Should see: All menu items
- ✅ Should see: Settings (Admin section)

---

## Summary

**Total Menu Items:** 10
- **Always Visible:** 2 (Home, Timesheets)
- **Coordinator+:** 5 (Expenses, Monthly Reports, Work Packages, Users, Staff)
- **Admin+:** 1 (Settings)
- **Dropdown/Submenus:** 2 (Work Packages, Settings)

**Role-Based Sections:** 3
1. Main Content (visible to all roles)
2. Administration (visible to coordinators+)
3. Admin Only (visible to admins+)

**Implementation Date:** November 2024
**Status:** Complete and tested

---

**File Location:** `application/views/pages/menu.php`
**Last Updated:** November 2024
**Version:** 1.0 (Complete restructure with role-based access)
