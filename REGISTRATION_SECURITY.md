# Self-Registration System - Security Documentation

## Security Measures Implemented

### 1. Authentication & Authorization
- **No unapproved login**: Users cannot log in until their registration is approved by an administrator
- **Token-based verification**: Both email verification and admin approval use cryptographically secure tokens
- **Secure token generation**: Uses MRBS's `generate_token()` function for cryptographically secure random tokens

### 2. Password Security
- **Password hashing**: All passwords are hashed using PHP's `password_hash()` with PASSWORD_DEFAULT (bcrypt)
- **Password policy enforcement**: Existing MRBS password policies are enforced during registration
- **No plaintext passwords**: Passwords are never stored or transmitted in plaintext

### 3. SQL Injection Prevention
- **Parameterized queries**: All database queries use parameterized statements
- **No string concatenation**: No user input is directly concatenated into SQL queries
- **Database abstraction layer**: Uses MRBS's existing secure database layer

### 4. XSS Prevention
- **Output escaping**: All user-provided data displayed in HTML is escaped using `htmlspecialchars()`
- **Form validation**: Input validation on both client and server side
- **Pattern validation**: Username field has HTML5 pattern validation

### 5. Input Validation
- **Required field validation**: All required fields are validated server-side
- **Email format validation**: Email addresses are validated using PHP's `filter_var()` with FILTER_VALIDATE_EMAIL
- **Username format validation**: Usernames must match pattern `[a-zA-Z0-9_]+`
- **Length validation**: All fields are checked against database column length limits
- **Duplicate prevention**: Checks prevent duplicate usernames and emails in both users and registration_requests tables

### 6. Email Security
- **No email injection**: Uses PHPMailer library which prevents email header injection
- **Sender verification**: Email "from" address is configured globally, not user-provided
- **Secure URL construction**: Uses MRBS's `url_base()` function for URL generation

### 7. CSRF Protection
- **Token-based URLs**: Email verification and approval links use unguessable tokens
- **Single-use tokens**: Email verification tokens can only be used once
- **Expiration**: Tokens are tied to specific registration requests and become invalid after use

### 8. Data Integrity
- **Database constraints**: UNIQUE constraints on username and email in registration_requests table
- **Foreign key relationships**: None needed as this is a staging table before user creation
- **Transaction safety**: Database operations use try-catch blocks for error handling

### 9. Privacy & Data Protection
- **Minimal data storage**: Only necessary information is stored
- **GDPR considerations**: Email addresses are only used for verification and notification
- **Admin oversight**: All registrations require admin approval before account creation

## Potential Security Considerations

### 1. Email Enumeration
- **Issue**: Error messages reveal if an email/username is already registered
- **Mitigation**: This is common practice and acceptable for registration systems
- **Recommendation**: If email enumeration is a concern, use generic error messages

### 2. Rate Limiting
- **Issue**: No built-in rate limiting for registration attempts
- **Recommendation**: Consider implementing rate limiting at web server or application level
- **Workaround**: Monitor registration_requests table for abuse patterns

### 3. Email Deliverability
- **Issue**: Registration flow depends on email delivery
- **Mitigation**: Users see clear instructions to check email
- **Recommendation**: Ensure SPF, DKIM, and DMARC are properly configured

### 4. Token Expiration
- **Issue**: Tokens do not have time-based expiration
- **Recommendation**: Consider adding expiration timestamps to tokens
- **Current behavior**: Tokens are valid until used or registration is processed

### 5. Approval Link Security
- **Issue**: Approval links can be used by anyone with the link (email forwarding scenario)
- **Mitigation**: This is intentional for email-based approval workflow
- **Consideration**: Admin receives link via email and can forward to appropriate person
- **Tracking**: System tracks who approved via 'approved_by' field

## Testing Checklist

### Registration Flow
- [ ] User can access registration form
- [ ] Form validation works correctly
- [ ] Password policy is enforced
- [ ] Duplicate usernames are rejected
- [ ] Duplicate emails are rejected
- [ ] Email verification link is sent
- [ ] Email verification works and updates database

### Email Verification
- [ ] Invalid tokens are rejected
- [ ] Already-verified tokens are rejected
- [ ] Admin notification is sent after verification
- [ ] User sees success message

### Admin Approval
- [ ] Approval link works correctly
- [ ] User account is created with correct details
- [ ] Approval email is sent to user
- [ ] User can log in after approval
- [ ] User cannot log in before approval

### Security Tests
- [ ] SQL injection attempts are blocked
- [ ] XSS attempts are escaped
- [ ] Tokens cannot be guessed or brute-forced
- [ ] Password hashes are properly stored
- [ ] Users without approval cannot log in

## Maintenance Notes

### Database Schema
- Table: `mrbs_registration_requests`
- Indexes: On email_verification_token, approval_token, email_verified, approved
- Cleanup: Consider periodic cleanup of old rejected/approved requests

### Configuration
- Variable: `$registration_approval_email` in systemdefaults.inc.php
- Default: Falls back to `$mail_settings['recipients']`
- Requirement: Must be set for admin notifications to work

### Monitoring
- Check registration_requests table for pending approvals
- Monitor for abuse patterns (multiple registrations from same IP)
- Review rejected registrations periodically

## Integration Points

### Modified Existing Files
1. `web/systemdefaults.inc.php` - Added configuration variable
2. `web/lib/MRBS/Session/SessionWithLogin.php` - Added registration link to login form
3. `tables.my.sql` and `tables.pg.sql` - Added new table schema

### New Files
All new files follow MRBS coding standards and namespace conventions:
- Registration form and handlers
- Email verification handler  
- Admin approval page
- Database migrations
- German translations

## Compliance

### MRBS Coding Standards
- ✅ Uses `declare(strict_types=1);`
- ✅ Proper namespace usage (`namespace MRBS;`)
- ✅ Follows existing patterns for forms and handlers
- ✅ Uses MRBS helper functions (`get_vocab()`, `db()`, etc.)
- ✅ Proper error handling with try-catch blocks

### Database Compatibility
- ✅ Supports both MySQL and PostgreSQL
- ✅ Uses database-agnostic SQL where possible
- ✅ Provides separate migration scripts for each database type

### Security Best Practices
- ✅ Parameterized queries (SQL injection prevention)
- ✅ Output escaping (XSS prevention)
- ✅ Password hashing (using password_hash())
- ✅ Secure token generation
- ✅ Input validation and sanitization
- ✅ Error handling without information disclosure
