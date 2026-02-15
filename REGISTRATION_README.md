# Self-Registration System Implementation

This implementation adds a complete self-registration system to MRBS (Meeting Room Booking System) that allows users to independently register for an account with email verification and admin approval.

## Overview

The self-registration system implements a secure, multi-step registration workflow:

1. **User Registration** - User fills out registration form
2. **Email Verification** - User confirms email address via link
3. **Admin Notification** - Admin receives email with registration details
4. **Admin Approval** - Admin approves or rejects the registration
5. **User Notification** - User receives confirmation and can log in

## Features

✅ **Complete Registration Workflow**
- Self-service registration form
- Email verification required
- Admin approval required
- Automatic account creation upon approval
- Email notifications at each step

✅ **Security**
- Password hashing with bcrypt
- Token-based email/approval verification
- SQL injection prevention (parameterized queries)
- XSS prevention (output escaping)
- Input validation and sanitization
- No login without approval

✅ **User Experience**
- Clear instructions at each step
- Error messages in German
- Password policy enforcement
- Username format validation
- Integration with existing login page

✅ **Admin Experience**
- Email notifications for new registrations
- Web-based approval interface
- Complete registration details displayed
- Approve or reject with one click
- Tracking of who approved/rejected

## Installation

### For New Installations

The registration system is included in the database schema. Simply run the normal MRBS installation:

```bash
# MySQL
mysql -u username -p database_name < tables.my.sql

# PostgreSQL
psql -U username -d database_name -f tables.pg.sql
```

### For Existing Installations

Run the upgrade script for your database:

```bash
# MySQL
mysql -u username -p database_name < web/upgrade/83/mysql.sql

# PostgreSQL
psql -U username -d database_name -f web/upgrade/83/pgsql.sql
```

### Configuration

Add to your `config.inc.php`:

```php
// Email address for registration approval notifications
$registration_approval_email = "admin@your-organization.de";
```

If not set, it will fall back to `$mail_settings['recipients']`.

## File Structure

### New Files

```
web/
├── register.php                    # Registration form
├── register_handler.php            # Registration form handler
├── register_success.php            # Success page after registration
├── verify_email.php                # Email verification handler
├── approve_registration.php        # Admin approval page/handler
└── upgrade/83/
    ├── mysql.sql                   # Database migration for MySQL
    └── pgsql.sql                   # Database migration for PostgreSQL

Documentation:
├── REGISTRATION_SECURITY.md        # Security documentation
└── REGISTRATION_GUIDE_DE.md        # German admin guide
```

### Modified Files (Minimal Changes)

```
web/
├── systemdefaults.inc.php          # Added $registration_approval_email config
├── lang/lang.de                    # Added German translations
└── lib/MRBS/Session/
    └── SessionWithLogin.php        # Added registration link to login form

tables.my.sql                       # Added mrbs_registration_requests table
tables.pg.sql                       # Added mrbs_registration_requests table
```

## Database Schema

### New Table: mrbs_registration_requests

```sql
CREATE TABLE mrbs_registration_requests
(
  id                      int NOT NULL auto_increment,
  username                varchar(30) NOT NULL,
  display_name            varchar(191) NOT NULL,
  email                   varchar(75) NOT NULL,
  organization            varchar(255) NOT NULL,    -- Verein
  role                    varchar(255) NOT NULL,    -- Funktion im Verein
  password_hash           varchar(255) NOT NULL,
  email_verification_token varchar(64) DEFAULT NULL,
  email_verified          tinyint DEFAULT 0 NOT NULL,
  email_verified_at       int DEFAULT NULL,
  approval_token          varchar(64) DEFAULT NULL,
  approved                tinyint DEFAULT 0 NOT NULL,
  approved_at             int DEFAULT NULL,
  approved_by             varchar(30) DEFAULT NULL,
  rejected                tinyint DEFAULT 0 NOT NULL,
  rejected_at             int DEFAULT NULL,
  rejected_by             varchar(30) DEFAULT NULL,
  rejection_reason        text DEFAULT NULL,
  created_at              int NOT NULL,
  updated_at              int DEFAULT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username),
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Usage

### For Users

1. Navigate to the MRBS login page
2. Click "Neues Konto registrieren" (Register New Account)
3. Fill out the registration form:
   - Username (letters, numbers, underscores only)
   - Display name (full name)
   - Email address
   - Organization (Verein)
   - Role (Funktion im Verein)
   - Password (must meet policy requirements)
4. Submit the form
5. Check email for verification link
6. Click the verification link
7. Wait for admin approval
8. Receive approval email
9. Log in with your username and password

### For Administrators

1. Receive email notification when user verifies email
2. Click the approval link in the email
3. Review the registration details
4. Click "Genehmigen" (Approve) or "Ablehnen" (Reject)
5. User receives confirmation email if approved

### Manual Approval (if needed)

Query pending registrations:
```sql
SELECT * FROM mrbs_registration_requests 
WHERE email_verified = 1 AND approved = 0 AND rejected = 0;
```

Get approval link:
```sql
SELECT CONCAT('https://your-mrbs.de/approve_registration.php?token=', approval_token)
FROM mrbs_registration_requests
WHERE email = 'user@example.com';
```

## Security

### Implemented Security Measures

1. **Authentication**
   - No login without admin approval
   - Token-based email verification
   - Token-based admin approval

2. **Password Security**
   - BCrypt hashing (password_hash with PASSWORD_DEFAULT)
   - Password policy enforcement
   - No plaintext storage

3. **Input Validation**
   - Server-side validation of all fields
   - Email format validation
   - Username pattern validation
   - Duplicate prevention

4. **SQL Injection Prevention**
   - All queries use parameterized statements
   - No string concatenation in SQL

5. **XSS Prevention**
   - All output escaped with htmlspecialchars()
   - Form validation prevents malicious input

6. **CSRF Protection**
   - Token-based URLs
   - Single-use verification tokens

See `REGISTRATION_SECURITY.md` for detailed security documentation.

## Language Support

Currently supports German (de) with all interface strings translated. The system uses MRBS's existing vocabulary system (`get_vocab()`).

### Translation Keys

All registration-related strings start with `register_` prefix in `web/lang/lang.de`:

- `register_link` - Link text on login page
- `register_account` - Form title
- `register_instructions` - Instructions text
- `register_organization` - Organization field label
- `register_role` - Role field label
- And many more...

## Troubleshooting

### User doesn't receive verification email

1. Check mail configuration in `config.inc.php`
2. Verify mail server settings (SMTP, etc.)
3. Check spam folder
4. Verify SPF/DKIM/DMARC configuration

### Admin doesn't receive notification

1. Verify `$registration_approval_email` is set
2. Check that email verification was successful
3. Check error logs for mail sending errors

### Database errors

1. Verify table exists:
   ```sql
   SHOW TABLES LIKE 'mrbs_registration_requests';
   ```
2. Check database version:
   ```sql
   SELECT * FROM mrbs_variables WHERE variable_name = 'db_version';
   ```
3. Should be version 83 or higher

### Token invalid or expired

- Tokens can only be used once
- Tokens become invalid after registration is processed
- User must register again if token is lost

## Maintenance

### Cleanup Old Registrations

Periodically clean up processed registrations:

```sql
-- Delete approved/rejected registrations older than 90 days
DELETE FROM mrbs_registration_requests 
WHERE (approved = 1 OR rejected = 1) 
AND created_at < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 90 DAY));
```

### Monitor Pending Registrations

Check for pending approvals:

```sql
SELECT username, email, organization, role,
       FROM_UNIXTIME(created_at) as registered
FROM mrbs_registration_requests
WHERE email_verified = 1 AND approved = 0 AND rejected = 0
ORDER BY created_at DESC;
```

## Testing

See the testing checklist in `REGISTRATION_SECURITY.md`.

Key tests:
- [ ] Registration form submission
- [ ] Email verification
- [ ] Admin approval
- [ ] User can log in after approval
- [ ] User cannot log in before approval
- [ ] Duplicate username/email rejection
- [ ] Password policy enforcement

## Compliance

### MRBS Standards
- ✅ Follows MRBS coding conventions
- ✅ Uses namespace `MRBS`
- ✅ Strict typing enabled
- ✅ Consistent with existing patterns

### Database Compatibility
- ✅ MySQL 5.5.3+
- ✅ PostgreSQL 8.2+
- ✅ Separate migration scripts for each

### Security Best Practices
- ✅ OWASP guidelines followed
- ✅ Input validation
- ✅ Output encoding
- ✅ Secure password handling
- ✅ Protection against common attacks

## Future Enhancements

Potential improvements for future versions:

1. **Token Expiration** - Add time-based expiration for tokens
2. **Rate Limiting** - Prevent abuse through rate limiting
3. **CAPTCHA** - Add CAPTCHA to registration form
4. **Multi-language** - Support for additional languages
5. **Admin Dashboard** - Web interface to manage pending registrations
6. **Email Templates** - Customizable HTML email templates
7. **Audit Log** - Detailed audit trail of all registration actions

## Support

For issues or questions:
1. Check the documentation files
2. Review error logs
3. Consult MRBS community forums
4. Contact your system administrator

## License

This implementation follows the same license as MRBS.

## Credits

Implemented for MRBS (Meeting Room Booking System)
Following requirements for secure self-registration with admin approval.
