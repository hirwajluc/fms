# GREATER FMS - Complete Migration Guide

**Date:** November 2024
**Status:** Ready for Production Migration
**Version:** Final - Includes Timesheets, Expenses, and Monthly Reports V2

---

## Overview

This guide provides step-by-step instructions for migrating your GREATER FMS database to the latest complete schema. The migration includes:

1. ✅ **Base Tables**: Partners, Roles, Staff, Users
2. ✅ **Expenses System**: Complete expense management
3. ✅ **Timesheets System**: Timesheet creation with daily entries
4. ✅ **Monthly Reports**: File attachment model for evidence documentation

---

## Available Migration Files

| File | Purpose | Status |
|------|---------|--------|
| `GREATER_FMS_COMPLETE_MIGRATION.sql` | **RECOMMENDED** - Complete schema for new projects | ✅ Ready |
| `MIGRATION_GUIDE.md` | This guide | ✅ You are here |

---

## Quick Start (3 Steps)

### Step 1: Backup (if existing database)
```bash
mysqldump -u root -p Sql1800295_2 > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Run Migration
```bash
# Option A: Command line
mysql -u root -p < /Applications/XAMPP/xamppfiles/htdocs/fms/GREATER_FMS_COMPLETE_MIGRATION.sql

# Option B: phpMyAdmin
# Go to Import → Select GREATER_FMS_COMPLETE_MIGRATION.sql → Click Go
```

### Step 3: Verify
```bash
mysql -u root -p -e "USE Sql1800295_2; SHOW TABLES;"
```

You should see 13 tables ✅

---

## Database Tables (Complete List)

### Core (5 tables)
- partners
- roles
- staff
- users
- expenses

### Timesheets (2 tables)
- timesheets
- timesheet_entries

### Monthly Reports (6 tables)
- monthly_financial_reports
- monthly_report_attachments
- monthly_report_summary
- monthly_report_category_summary
- monthly_report_wp_summary
- monthly_report_currency_summary

---

## Features Included

✅ **User Management** - Roles, permissions, access control
✅ **Expenses System** - File uploads, approval workflow, multi-currency
✅ **Timesheets System** - Daily entries, PDF export, signatures
✅ **Monthly Reports** - Evidence files, auto-calculated summaries, approval

---

## Troubleshooting

**Q: "Access denied for user 'root'"?**
A: Use -p flag: `mysql -u root -p < migration.sql`

**Q: "Unknown database"?**
A: The script creates it. Make sure MySQL is running.

**Q: "Table already exists"?**
A: Drop first: `DROP TABLE IF EXISTS table_name;`

---

## Support Files

- `database_schema.sql` - Original base schema
- `MONTHLY_REPORTS_MIGRATION_V2.sql` - Migration details (reference)
- `MONTHLY_REPORTS_MODEL_V2.php` - Model methods to integrate

---

**Status:** ✅ PRODUCTION READY
**Last Updated:** November 2024

