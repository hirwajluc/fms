# GREATER FMS — Financial Management System

A CodeIgniter 3 web application for managing timesheets, expenses, staff, and monthly reports for the ERASMUS+ GREATER project.

## Environments

| Environment | URL | Server |
|---|---|---|
| Development | http://86.48.7.218/fms/ | This server (current) |
| Production | https://greaterproject.eu/fms | greaterproject.eu |

## Tech Stack

- PHP 8.1 / CodeIgniter 3
- MySQL (database: `Sql1800295_2`)
- Apache 2.4
- Bootstrap 5 + DataTables + Flatpickr + Dompdf (PDF generation)

## Project Structure

```
fms/
├── application/
│   ├── controllers/Fms.php          # Main controller (all routes)
│   ├── models/Fms_model_enhanced.php # All DB queries
│   ├── views/pages/                 # All page views
│   ├── libraries/Auth_manager.php   # Role-based auth
│   └── config/
│       ├── config.php               # Base URL, session, etc.
│       ├── database.php             # DB connection
│       └── routes.php               # URL routes
├── assets/
│   ├── uploads/                     # Expense files
│   ├── signatures/                  # Timesheet signatures
│   ├── reports/                     # Generated PDF reports
│   └── otherfiles/                  # WP other files
├── db/                              # Database exports (SQL dumps)
└── doc/                             # Internal documentation & guides
```

## Roles

| Role | ID | Access |
|---|---|---|
| Super Admin | 1 | Full access |
| Admin | 2 | Timesheets, Expenses, Users |
| Institution Coordinator | 3 | Own timesheets, Expenses, Other Files |
| Member / Staff | 4 | Own timesheets only |

## Deployment Workflow

### Development (this server)
Make changes, test, then push:
```bash
git add .
git commit -m "your message"
git push origin master
```

### Production (greaterproject.eu)
SSH into the production server and pull:
```bash
cd /path/to/fms
git pull origin master
```

> **Note:** After pulling on production, ensure `assets/uploads/`, `assets/signatures/`, `assets/reports/`, and `assets/otherfiles/` are writable by the web server, and that the `application/config/database.php` points to the production database.

## Database

Exports are stored in `db/`. To restore:
```bash
mysql -u <user> -p <database_name> < db/fms_YYYYMMDD.sql
```

## Setup (fresh install)

1. Clone the repo into your web root
2. Configure `application/config/config.php` — set `$config['base_url']`
3. Configure `application/config/database.php` — set credentials
4. Import a dump from `db/` into MySQL
5. Ensure these folders are writable by the web server:
   - `assets/uploads/`
   - `assets/signatures/`
   - `assets/reports/`
   - `assets/otherfiles/`
6. Enable `mod_rewrite` on Apache and allow `.htaccess` overrides
