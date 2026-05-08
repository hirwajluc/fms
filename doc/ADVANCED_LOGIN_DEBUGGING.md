# Advanced Login Debugging Guide

## When to Use This Guide
Use this if login still fails even after the POST/GET fallback fix has been implemented.

## Step 1: Browser Developer Tools Inspection

### Open DevTools
- **Windows/Linux**: Press `F12` or `Ctrl+Shift+I`
- **Mac**: Press `Cmd+Option+I` or go to Safari → Develop → Show Web Inspector

### Check Console Tab
```javascript
// You should see one of these messages:

// If POST worked:
// (Nothing logged - normal operation)

// If fallback triggered:
// "Submitting via GET method due to hosting restrictions"
// "POST request error, trying GET as fallback"
```

### Check Network Tab
1. Click **Network** tab
2. Clear previous requests (if any)
3. Attempt login
4. Look for request to `login/login_pro`

#### Verify Request Details:
- **Request URL**: Should show email and password in GET, or empty for POST
- **Request Method**: Should show POST or GET
- **Status Code**:
  - `302` = Redirect (success, user being sent to dashboard)
  - `200` = OK but no redirect (might be error page)
  - `403` = Forbidden (server rejected)
  - `500` = Server error

#### Form Data (POST requests):
- Should show email and password fields
- If empty, POST data wasn't sent (server restriction)

#### Query String (GET requests):
- Should show: `?email=user@example.com&password=hashed`

### Check Application Tab (Cookies/Storage)
1. Click **Application** (Chrome) or **Storage** (Firefox)
2. Look for **Cookies**
3. After successful login, should see session cookie:
   - Name: `ci_session` or similar
   - Value: Session ID

## Step 2: Server-Side Logging

### View Application Logs
```bash
# SSH into your server or use hosting file manager
cd /path/to/fms
tail -100 application/logs/log-*.php
```

### Look for These Messages

#### Successful Login:
```
[2024-11-04 10:30:45] [INFO] Successful login for user: user@example.com
[2024-11-04 10:30:45] [INFO] User session set for user_id: 1
```

#### POST Fallback Triggered:
```
[2024-11-04 10:30:45] [WARNING] Login using GET method - possible POST restriction on hosting
[2024-11-04 10:30:45] [INFO] Successful login for user: user@example.com
```

#### Failed Login Attempts:
```
[2024-11-04 10:30:45] [WARNING] Failed login attempt for: user@example.com
[2024-11-04 10:30:45] [WARNING] Login attempt on locked account: user@example.com
[2024-11-04 10:30:45] [ERROR] Login attempt with empty credentials
```

### Enable Detailed Logging
If you don't see helpful messages, increase logging:

1. Edit `application/config/config.php`
2. Find: `$config['log_threshold']`
3. Change to: `$config['log_threshold'] = 1;` (log everything)
4. Try login again
5. Check logs - should be much more verbose

## Step 3: Database Verification

### Check if Credentials Exist
```sql
-- SSH/Terminal or phpMyAdmin
USE your_database_name;

-- Check users table structure
DESCRIBE users;

-- Find your test user
SELECT user_id, email, password, status FROM users WHERE email = 'your@email.com';

-- Check if password hash is correct
-- Get hash from database
-- Compare with: SHA1('your_password')

-- Example in PHP/MySQL:
-- SELECT SHA1('testpassword123');
-- Should match the password field in users table
```

### Check Account Status
```sql
SELECT user_id, email, status, role_id FROM users WHERE email = 'your@email.com';
```

Status should be: `'active'` (not 'inactive' or 'locked')

### Check Account Lock
```sql
-- View login attempts
SELECT email, failed_attempts, locked_until FROM users
WHERE email = 'your@email.com';

-- If locked, unlock:
UPDATE users
SET failed_attempts = 0, locked_until = NULL
WHERE email = 'your@email.com';
```

## Step 4: Test with curl (Command Line)

### Test POST Request
```bash
# Try POST (may fail if hosting blocks POST)
curl -X POST \
  -d "email=your@email.com&password=yourpassword" \
  https://yoursite.com/login/login_pro \
  -i

# Check response:
# - Look for "Location:" header with redirect
# - Should redirect to dashboard (/)
```

### Test GET Request
```bash
# Try GET (fallback method)
curl -X GET \
  "https://yoursite.com/login/login_pro?email=your@email.com&password=yourpassword" \
  -i

# Same as above, should redirect on success
```

### Expected Response
```
HTTP/1.1 302 Found
Location: https://yoursite.com/
Set-Cookie: ci_session=...

<html><body><p>The page has moved</p></body></html>
```

## Step 5: Hosting Configuration Check

### Ask Your Hosting Provider

**For Shared Hosting:**
- Is mod_security enabled?
- Are there rules blocking POST requests?
- Are there PHP-FPM restrictions?
- Can they whitelist my login endpoint?

**For cPanel Hosting:**
- Check: Security → ModSecurity
- Look for rules matching `/login/` or `login_pro`
- May need to disable for that directory

**For Plesk Hosting:**
- Check: Security → Web Application Firewall
- Look for rules blocking POST to login

### Create .htaccess Override
If hosting supports .htaccess:

```apache
# .htaccess in /path/to/fms/
<IfModule mod_security.c>
    <FilesMatch "login_pro">
        SecRuleEngine Off
        SecAuditEngine Off
    </FilesMatch>
</IfModule>
```

## Step 6: Test Account Creation

### Create Fresh Test User
```sql
-- Create new test user
INSERT INTO staff (first_name, last_name, email, partner_id, status)
VALUES ('Test', 'User', 'test@example.com', 1, 'active');

-- Get staff_id (should be auto-incremented)
SELECT LAST_INSERT_ID() as staff_id;

-- Insert user account (use the staff_id from above)
INSERT INTO users (staff_id, email, password, role_id, status)
VALUES (
  LAST_INSERT_ID(),
  'test@example.com',
  SHA1('testpass123'),  -- Password is 'testpass123'
  4,                      -- Role 4 = Member
  'active'
);
```

### Test Login
1. Try to login with: `test@example.com` / `testpass123`
2. Check if it works

### Reset Failed Attempts
If user is locked after failed attempts:
```sql
UPDATE users
SET failed_attempts = 0, locked_until = NULL
WHERE email = 'test@example.com';
```

## Step 7: Session Verification

### Check Session Configuration
File: `application/config/config.php`

Important settings:
```php
$config['sess_driver'] = 'files';  // or 'database', 'redis'
$config['sess_cookie_name'] = 'ci_session';
$config['sess_expiration'] = 7200;
$config['sess_save_path'] = './application/cache'; // or 'ci_sessions'
```

### Verify Session Directory Exists
```bash
# Check if session directory is writable
ls -la application/cache/
# Should show files like: ci_session_*

# If directory doesn't exist, create it:
mkdir -p application/cache
chmod 755 application/cache
```

### Check Session File (File-based sessions)
```bash
# List session files
ls -la application/cache/ | grep ci_session

# View session content (after successful login)
cat application/cache/ci_session_<session_id>

# Should contain user data:
# CI_SESSION:a:6:{s:9:"fms_user_id";i:1;s:11:"fms_partner_id";i:1;...}
```

## Step 8: Email Case Sensitivity

### Check Database
```sql
-- Email matching might be case-sensitive
SELECT email, COLLATE(email, 'utf8mb4_general_ci')
FROM users
WHERE email LIKE '%your@email.com%';

-- Make sure collation is case-insensitive
ALTER TABLE users
MODIFY COLUMN email VARCHAR(255)
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### Test
- Database email: `User@Example.Com`
- Login attempt: `user@example.com`
- Should work (case-insensitive)

## Step 9: Method-Specific Issues

### If Only POST Works (GET Fails)
```php
// Edit Login controller to only use POST
$email = $this->input->post('email', TRUE);
$password = $this->input->post('password', TRUE);

// Remove GET fallback temporarily for testing
```

### If Only GET Works (POST Fails)
This is the hosting restriction scenario - the fix handles this automatically.

### If Neither Works
```php
// Try raw input as last resort
$email = $_REQUEST['email'] ?? '';
$password = $_REQUEST['password'] ?? '';

// But this bypasses CodeIgniter's input filtering
// Only use for testing/debugging
```

## Step 10: Check for Errors

### PHP Errors in Logs
```bash
# Check PHP error log (varies by hosting)
tail -100 /var/log/php-fpm/www-error.log
# or
tail -100 /var/log/apache2/error.log
# or
tail -100 /var/log/nginx/error.log
```

### CodeIgniter Errors
File: `application/logs/log-*.php`

Look for:
- `[ERROR]` messages
- Database connection issues
- Class not found errors
- Function call errors

## Troubleshooting Flowchart

```
Login Fails
    ↓
Check Browser Console (F12)
    ├─ No errors → Check Network tab
    │   ├─ Status 302 → Session issue (Step 7)
    │   ├─ Status 200 → Check server logs (Step 2)
    │   ├─ Status 403/500 → Database/credentials issue (Step 3)
    │   └─ No request → JavaScript error (fix JavaScript)
    │
    └─ Error shown → Fix JavaScript error

Check Application Logs (Step 2)
    ├─ "Successful login" → Session not persisting (Step 7)
    ├─ "Empty credentials" → POST/GET not reaching server
    ├─ "Wrong email/password" → Verify credentials (Step 3)
    └─ No entry → Request not reaching controller

Check Database (Step 3)
    ├─ User doesn't exist → Create test user (Step 6)
    ├─ Password doesn't match → Reset with SHA1
    ├─ User locked → Unlock account (Step 4)
    └─ User inactive → Set status='active'

Check Hosting (Step 5)
    ├─ mod_security blocking → Ask to whitelist or disable
    ├─ POST disabled → GET fallback should handle
    └─ Session directory → Ensure writable (Step 7)
```

## Common Issues and Solutions

### Issue: "Email or Password is Wrong" Always
**Cause**: Password hashing mismatch
**Solution**:
```sql
-- Verify correct hash
SELECT SHA1('yourpassword');
-- Copy hash and update user:
UPDATE users SET password = 'correct_hash' WHERE email = 'your@email.com';
```

### Issue: Login works but redirects back to login
**Cause**: Session not being created
**Solution**:
```bash
# Check session directory exists and is writable
ls -la application/cache/
chmod 755 application/cache
```

### Issue: "Account is locked" after 3 attempts
**Cause**: Account lockout security feature
**Solution**:
```sql
-- Unlock account
UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE email = 'your@email.com';
```

### Issue: GET works but POST doesn't
**Cause**: Hosting POST restriction
**Solution**: Fix automatically handles this now - no action needed!

### Issue: Neither POST nor GET works
**Cause**: Multiple issues - check all steps
**Solution**: Follow troubleshooting flowchart above

## Contact Hosting Support

When contacting your hosting provider, provide:

1. **Error message**: Exact text from error page
2. **Logs**: Copy relevant entries from application logs
3. **Network request**: Screenshot of Network tab showing request
4. **Questions to ask**:
   - Do you have mod_security enabled?
   - Are there rules blocking POST to /login/ endpoint?
   - Can you whitelist this application or endpoint?
   - What is your PHP-FPM configuration?
   - Are there any WAF (Web Application Firewall) rules?

## Testing Checklist

- [ ] Browser console shows no errors
- [ ] Network tab shows 302 response
- [ ] Session cookie is created after login
- [ ] Application logs show successful login
- [ ] Database user exists and is active
- [ ] Password hash is correct (SHA1 match)
- [ ] Session directory is writable
- [ ] Not locked out (failed_attempts < 3)
- [ ] GET fallback works if POST blocked
- [ ] Dashboard loads after login

---

**Last Updated**: November 2024
**Applies To**: CodeIgniter 3.1.13 with GREATER FMS v1.0
