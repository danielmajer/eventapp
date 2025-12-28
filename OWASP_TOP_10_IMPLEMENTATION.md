# OWASP Top 10 (2021) - Security Implementation

This document outlines the security measures implemented in this application to address the OWASP Top 10 security risks.

---

## A01:2021 – Broken Access Control

### ✅ Implemented

**1. Authentication Required**
- All protected routes use `auth:sanctum` middleware
- Token-based authentication via Laravel Sanctum
- No anonymous access to sensitive endpoints

**2. Authorization Policies**
- **EventPolicy**: Enforces users can only view/update/delete their own events
  - `view()`: Checks `$user->id === $event->user_id`
  - `update()`: Checks `$user->id === $event->user_id`
  - `delete()`: Checks `$user->id === $event->user_id`
- **HelpdeskChatPolicy**: Controls chat access
  - Users can view their own chats
  - Helpdesk agents can view any chat

**3. Role-Based Access Control (RBAC)**
- Custom gate: `act-as-helpdesk-agent`
- Middleware: `EnsureHelpdeskAgent` - restricts helpdesk routes to `helpdesk_agent` and `admin` roles
- User roles stored in database: `user`, `helpdesk_agent`, `admin`

**4. Resource Scoping**
- Events automatically filtered by `user_id` in queries:
  ```php
  Event::where('user_id', $request->user()->id)->get()
  ```
- Helpdesk chats filtered by `user_id` for regular users
- Policy checks before any update/delete operations

**5. Authorization Checks**
- `$this->authorize('update', $event)` in controllers
- `$this->authorize('view', $chat)` for chat access
- Explicit authorization before sensitive operations

---

## A02:2021 – Cryptographic Failures

### ✅ Implemented

**1. TLS/HTTPS Enforcement**
- **ForceHttps Middleware**: Enforces HTTPS in production
  - Rejects HTTP requests in production environment
  - Supports proxy headers (`X-Forwarded-Proto`)
  - Applied to all API routes

**2. Security Headers**
- **HSTS**: `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- **X-Content-Type-Options**: `nosniff`
- **X-Frame-Options**: `DENY`
- **X-XSS-Protection**: `1; mode=block`
- **Referrer-Policy**: `strict-origin-when-cross-origin`

**3. Password Hashing**
- Passwords hashed using Laravel's `Hash::make()` (bcrypt/argon2)
- Password cast: `'password' => 'hashed'` in User model
- Password reset uses secure token generation

**4. Field-Level Encryption**
- **FieldEncryptionService**: Encrypts sensitive database fields
- Encrypted fields:
  - User: `email`, `mfa_secret`
  - Event: `description`
  - HelpdeskMessage: `content`
- Uses `DB_FIELD_ENCRYPTION_KEY` for encryption
- AES-256-CBC encryption algorithm
- Automatic encryption on save, decryption on retrieve

**5. MFA Secrets**
- MFA secrets stored encrypted in database
- TOTP-based 2FA using Google Authenticator
- Secrets never exposed in API responses

**6. API Keys Protection**
- Gemini API key stored in config, not hardcoded
- Logged values are masked (only first 4 chars shown)

---

## A03:2021 – Injection

### ✅ Implemented

**1. SQL Injection Protection**
- **Eloquent ORM**: All database queries use Eloquent (parameterized queries)
- **Query Builder**: Uses prepared statements automatically
- No raw SQL queries with user input
- Example: `Event::where('user_id', $request->user()->id)` - automatically parameterized

**2. Input Validation**
- All endpoints use Laravel's validation:
  ```php
  $request->validate([
      'email' => 'required|email',
      'password' => 'required|string|min:8|confirmed',
      'title' => 'required|string|max:255',
  ])
  ```
- Validation rules prevent malicious input
- Type checking and format validation

**3. Parameter Binding**
- Eloquent automatically uses parameterized queries
- User input never directly concatenated into SQL
- All queries use Laravel's query builder or Eloquent

**4. Command Injection Protection**
- No `exec()`, `system()`, or `shell_exec()` with user input
- External API calls use Laravel's HTTP client (sanitized)

**5. XSS Protection (Frontend)**
- React automatically escapes content
- No `dangerouslySetInnerHTML` with user input
- JSON responses (no HTML injection)

---

## A04:2021 – Insecure Design

### ✅ Implemented

**1. Secure Authentication Flow**
- No registration endpoint (users must be provisioned)
- Password reset with secure tokens
- MFA support for additional security layer
- Token-based API authentication (Sanctum)

**2. Principle of Least Privilege**
- Users can only access their own resources
- Helpdesk agents have limited, role-specific access
- Admin role for administrative functions

**3. Secure Defaults**
- Passwords must be hashed (enforced by model cast)
- HTTPS enforced in production
- Security headers set by default
- Field encryption enabled for sensitive data

**4. Secure Data Storage**
- Sensitive fields encrypted at rest
- Passwords never stored in plaintext
- MFA secrets encrypted

---

## A05:2021 – Security Misconfiguration

### ✅ Implemented

**1. Environment Configuration**
- Sensitive data in `.env` file (not committed)
- Separate config for development/production
- `APP_DEBUG=false` in production (should be set)

**2. Security Headers**
- All security headers set via `ForceHttps` middleware
- HSTS, X-Content-Type-Options, X-Frame-Options, etc.

**3. Error Handling**
- Generic error messages in production
- No stack traces exposed to users
- Detailed errors only in logs

**4. CORS Configuration**
- CORS handled by Laravel (fruitcake/laravel-cors)
- Configurable allowed origins

**5. Database Security**
- SQLite with field-level encryption
- Database file permissions should be restricted
- Encryption keys stored securely

**6. Default Credentials**
- No hardcoded users
- Initial user created via seeder (should be changed)
- Password complexity requirements

---

## A06:2021 – Vulnerable and Outdated Components

### ✅ Implemented

**1. Dependency Management**
- Composer for PHP dependencies
- `composer.json` with version constraints
- Regular updates recommended

**2. Framework Security**
- Laravel 12.0 (latest version)
- Laravel Sanctum for authentication
- Regular security updates via Composer

**3. Known Vulnerabilities**
- No known vulnerable packages in current setup
- Dependencies should be regularly updated:
  ```bash
  composer update
  composer audit
  ```

**4. Version Pinning**
- Specific versions in `composer.json`
- `prefer-stable: true` to avoid unstable packages

---

## A07:2021 – Identification and Authentication Failures

### ✅ Implemented

**1. Strong Authentication**
- Email + password authentication
- Password hashing (bcrypt/argon2)
- Token-based API authentication (Sanctum)

**2. Multi-Factor Authentication (MFA)**
- TOTP-based 2FA using Google Authenticator
- QR code generation for setup
- MFA verification required for login if enabled
- MFA can be enabled/disabled by users

**3. Password Reset**
- Secure password reset flow
- Token-based reset links
- Password complexity: minimum 8 characters, confirmed
- Password reset tokens expire

**4. Session Management**
- Token-based (no traditional sessions for API)
- Tokens can be revoked on logout
- Per-device tokens (Sanctum)

**5. Account Protection**
- No registration endpoint (prevents account enumeration)
- Generic error messages ("Invalid credentials")
- **Rate limiting implemented**: 5 attempts per 15 minutes on authentication endpoints
- Failed login attempts logged for monitoring

**6. Password Requirements**
- Minimum 8 characters
- Password confirmation required
- Passwords hashed before storage

---

## A08:2021 – Software and Data Integrity Failures

### ✅ Implemented

**1. Dependency Integrity**
- Composer lock file (`composer.lock`) ensures consistent dependencies
- Version constraints prevent unexpected updates

**2. Data Integrity**
- Database transactions for critical operations
- Eloquent ORM ensures data consistency
- Foreign key constraints enabled

**3. Input Validation**
- All user input validated before processing
- Type checking and format validation
- Prevents malformed data

**4. Secure Updates**
- Dependencies managed via Composer
- Version pinning prevents breaking changes
- Regular security updates recommended

---

## A09:2021 – Security Logging and Monitoring Failures

### ✅ Implemented

**1. Enhanced Logging**
- Laravel logging configured with dedicated security channel
- Security log file: `storage/logs/security.log` (90-day retention)
- Separate log channel for security events
- All security events logged with context (IP, user agent, timestamps)

**2. Authentication Event Logging**
- **Login attempts**: Success and failure logged
- **MFA events**: Setup, enable, disable, verification (success/failure)
- **Password reset**: Requests and completions logged
- **Logout**: User logout events tracked
- **Rate limiting**: Failed attempts and rate limit violations logged

**3. Audit Logging System**
- **AuditLogService**: Centralized service for audit logging
- **Database audit trail**: `audit_logs` table stores all sensitive operations
- **Automatic logging**: `Auditable` trait automatically logs create/update/delete on models
- **Manual logging**: Controllers log additional context for operations
- **Logged operations**:
  - Event creation, update, deletion
  - Authentication events (login, logout, MFA, password reset)
  - Access denied attempts
  - Helpdesk agent actions

**4. Rate Limiting**
- **ThrottleAuth Middleware**: Custom rate limiting for authentication endpoints
- **5 attempts per 15 minutes** on:
  - `/api/auth/login`
  - `/api/auth/password/email`
  - `/api/auth/password/reset`
  - `/api/auth/mfa/verify`
- Rate limit violations logged with IP and email
- Returns `429 Too Many Requests` with retry-after header

**5. Security Monitoring**
- **SecurityMonitoringService**: Detects suspicious patterns
- **Security alerts** for:
  - Multiple failed login attempts from same IP (≥5 in 1 hour)
  - Unusual access patterns (same user from multiple IPs)
  - Multiple access denied attempts (≥3 in 1 hour)
- **Security statistics**: Command to view security metrics
- **Artisan command**: `php artisan security:monitor` for monitoring

**6. Access Denied Logging**
- All access denied attempts logged in policies
- Includes user, resource, and reason for denial
- Helps detect unauthorized access attempts

**7. Audit Log Database Schema**
- `audit_logs` table with indexes for fast queries
- Stores: action, user_id, user_email, resource_type, resource_id, IP, user_agent, metadata
- Indexed on: user_id, action, resource_type, created_at, (resource_type, resource_id)

**Recommendations for Production:**
- Set up centralized logging (e.g., ELK stack, CloudWatch, Splunk)
- Configure log aggregation and analysis
- Set up automated alerts for high-severity security events
- Schedule `security:monitor` command via cron for regular checks
- Consider integrating with SIEM (Security Information and Event Management) systems

---

## A10:2021 – Server-Side Request Forgery (SSRF)

### ✅ Protected

**1. External API Calls**
- Only controlled API calls to Google Gemini
- No user-controlled URLs in HTTP requests
- API endpoints are hardcoded, not user-provided

**2. No SSRF Vulnerabilities**
- No functionality that makes HTTP requests based on user input
- Gemini API endpoint is fixed: `https://generativelanguage.googleapis.com/...`
- No URL fetching from user input

**3. Input Validation**
- User messages sent to Gemini are validated as strings
- No URL parsing or fetching from user input

---

## Summary

### ✅ Fully Implemented (10/10)
1. **A01 - Broken Access Control**: Policies, gates, role-based access, access denied logging
2. **A02 - Cryptographic Failures**: TLS, encryption, hashing, security headers
3. **A03 - Injection**: Eloquent ORM, input validation, parameterized queries
4. **A04 - Insecure Design**: Secure defaults, least privilege
5. **A05 - Security Misconfiguration**: Security headers, environment config
6. **A06 - Vulnerable Components**: Dependency management, version control
7. **A07 - Authentication Failures**: MFA, password hashing, secure auth, rate limiting
8. **A08 - Data Integrity**: Dependency integrity, data validation
9. **A09 - Logging and Monitoring**: ✅ **FULLY IMPLEMENTED** - Audit logging, security monitoring, rate limiting, alerts
10. **A10 - SSRF**: No user-controlled URLs

---

## Implementation Details

### Rate Limiting
- **Middleware**: `App\Http\Middleware\ThrottleAuth`
- **Configuration**: 5 attempts per 15 minutes (configurable)
- **Endpoints**: All authentication endpoints
- **Response**: 429 Too Many Requests with retry-after header
- **Logging**: All rate limit violations logged

### Audit Logging
- **Service**: `App\Services\AuditLogService`
- **Trait**: `App\Traits\Auditable` (auto-logs model events)
- **Database**: `audit_logs` table
- **Log Channel**: `security` (separate log file)
- **Retention**: 90 days (configurable via `LOG_SECURITY_DAYS`)

### Security Monitoring
- **Service**: `App\Services\SecurityMonitoringService`
- **Command**: `php artisan security:monitor`
- **Features**:
  - Failed login detection
  - Unusual access pattern detection
  - Access denied pattern detection
  - Security statistics
  - Automated alerting

### Usage Examples

**Run security monitoring:**
```bash
php artisan security:monitor --stats    # Show statistics
php artisan security:monitor --alerts   # Check for alerts
php artisan security:monitor           # Both
```

**View audit logs:**
```bash
tail -f storage/logs/security.log
```

**Query audit logs in database:**
```sql
SELECT * FROM audit_logs 
WHERE action = 'auth.login_failed' 
ORDER BY created_at DESC 
LIMIT 10;
```

