# Security Configuration Guide

This document outlines the security measures implemented in the Training Management System and provides guidance for secure deployment and maintenance.

## Table of Contents

1. [Security Features](#security-features)
2. [Production Deployment Checklist](#production-deployment-checklist)
3. [Cookie Security](#cookie-security)
4. [HTTP Security Headers](#http-security-headers)
5. [Secrets Management](#secrets-management)
6. [Error Handling](#error-handling)
7. [Security Testing](#security-testing)

---

## Security Features

### Implemented Security Measures

✅ **Session Security**
- Session encryption enabled by default
- HttpOnly cookies to prevent XSS attacks
- SameSite=Lax to mitigate CSRF attacks
- Configurable Secure flag for HTTPS environments

✅ **HTTP Security Headers**
- X-Frame-Options: Prevent clickjacking
- X-Content-Type-Options: Prevent MIME-sniffing
- Content-Security-Policy: XSS protection
- Strict-Transport-Security: Enforce HTTPS (production only)
- Referrer-Policy: Control referrer information
- Permissions-Policy: Restrict browser features

✅ **CSRF Protection**
- Laravel's built-in CSRF middleware
- @csrf tokens in all forms

✅ **SQL Injection Protection**
- Eloquent ORM for database queries
- Parameterized queries for raw SQL

✅ **Secure Error Handling**
- Stack traces logged server-side only
- User-friendly error messages
- No sensitive information exposure

---

## Production Deployment Checklist

### Required Environment Variables

When deploying to production, ensure the following environment variables are properly configured:

```env
# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# HTTPS/SSL Configuration
APP_HTTPS=true

# Session Security (CRITICAL)
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true
SESSION_DRIVER=database  # Recommended for production
```

### Pre-Deployment Steps

1. **Enable HTTPS/SSL**
   - Obtain SSL certificate (Let's Encrypt, commercial CA)
   - Configure web server (Nginx/Apache) for HTTPS
   - Set `APP_HTTPS=true`

2. **Session Configuration**
   - Change `SESSION_DRIVER` from `file` to `database` or `redis`
   - Run: `php artisan session:table && php artisan migrate`
   - Enable `SESSION_SECURE_COOKIE=true`

3. **Security Headers**
   - Verify `SecurityHeaders` middleware is registered
   - Test headers with: `curl -I https://yourdomain.com`

4. **Error Handling**
   - Set `APP_DEBUG=false`
   - Configure proper logging: `LOG_CHANNEL=daily` or `stack`

5. **Cache Configuration**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## Cookie Security

### Configuration Details

**File:** `config/session.php`

| Setting | Development | Production | Purpose |
|---------|-------------|------------|---------|
| `secure` | `false` | `true` | Only send cookies over HTTPS |
| `http_only` | `true` | `true` | Prevent JavaScript access to cookies |
| `same_site` | `lax` | `lax` or `strict` | CSRF protection |
| `encrypt` | `true` | `true` | Encrypt session data |

### Development vs Production

**Development (HTTP):**
```env
SESSION_SECURE_COOKIE=false
APP_HTTPS=false
```

**Production (HTTPS):**
```env
SESSION_SECURE_COOKIE=true
APP_HTTPS=true
```

### Testing Cookie Flags

```bash
# Check cookie settings
curl -I -c cookies.txt https://yourdomain.com/login
cat cookies.txt

# Expected flags: Secure; HttpOnly; SameSite=Lax
```

---

## HTTP Security Headers

### Implemented Headers

The `SecurityHeaders` middleware (`app/Http/Middleware/SecurityHeaders.php`) adds the following headers:

#### 1. X-Frame-Options: SAMEORIGIN
**Purpose:** Prevent clickjacking attacks
**Value:** `SAMEORIGIN` - Only allow framing from same origin

#### 2. X-Content-Type-Options: nosniff
**Purpose:** Prevent MIME-sniffing attacks
**Value:** `nosniff` - Force browsers to respect declared Content-Type

#### 3. Referrer-Policy: strict-origin-when-cross-origin
**Purpose:** Control information sent in Referer header
**Value:** Send origin only for cross-origin requests

#### 4. X-XSS-Protection: 1; mode=block
**Purpose:** Legacy XSS filter for older browsers
**Value:** `1; mode=block` - Enable filter and block page if XSS detected

#### 5. Content-Security-Policy
**Purpose:** Comprehensive XSS and injection protection

Current policy:
```
default-src 'self';
script-src 'self' 'unsafe-inline' 'unsafe-eval';
style-src 'self' 'unsafe-inline';
img-src 'self' data: https:;
font-src 'self' data:;
connect-src 'self';
frame-ancestors 'self';
base-uri 'self';
form-action 'self';
```

**Note:** `unsafe-inline` and `unsafe-eval` are currently required for Alpine.js and inline scripts. Consider implementing CSP nonces in the future for stricter policy.

#### 6. Strict-Transport-Security (HSTS)
**Purpose:** Force HTTPS connections
**Value:** `max-age=31536000; includeSubDomains; preload`

**IMPORTANT:** HSTS is only enabled when:
- `APP_HTTPS=true` (in `.env`)
- `APP_ENV=production`

### Customizing Security Headers

Edit `app/Http/Middleware/SecurityHeaders.php` to modify headers:

```php
// Example: Stricter CSP for admin routes
if ($request->is('admin/*')) {
    $response->headers->set('Content-Security-Policy', "default-src 'self'");
}
```

### Testing Headers

```bash
# Test all security headers
curl -I https://yourdomain.com

# Use online scanner
# Visit: https://securityheaders.com/?q=https://yourdomain.com
```

---

## Secrets Management

### Current Issues

⚠️ **WARNING:** The `.env` file currently contains sensitive credentials in plaintext:
- Database passwords
- API keys (AI_MAIN_API_KEY, JWT_SECRET)
- Email passwords
- S3 credentials
- GitHub tokens

### Recommendations

#### Option 1: Encrypted Environment Files (Quick Solution)

**For smaller teams:**

```bash
# Encrypt .env file
php artisan env:encrypt --env=production

# This creates .env.production.encrypted
# Share encrypted file via secure channel

# On production server, decrypt:
php artisan env:decrypt --env=production
```

#### Option 2: Secret Management Service (Enterprise Solution)

**For production deployments:**

1. **AWS Secrets Manager**
   ```bash
   composer require aws/aws-sdk-php
   ```

2. **Azure Key Vault**
   ```bash
   composer require azure/azure-key-vault
   ```

3. **HashiCorp Vault**
   ```bash
   composer require hashicorp/vault-php
   ```

**Implementation example:**
```php
// config/database.php
'password' => env('DB_PASSWORD') ?: resolve(SecretsManager::class)->get('db_password'),
```

#### Option 3: Environment-Specific Approach

**Best Practice:**

1. **Never commit `.env` to repository**
   - Ensure `.env` is in `.gitignore`

2. **Use `.env.example` as template**
   ```env
   # .env.example
   DB_PASSWORD=
   VIETTEL_S3_SECRET=
   MAIL_PASSWORD=
   ```

3. **Store production secrets separately**
   - Use server environment variables
   - Use deployment platform's secret manager (Heroku Config Vars, Laravel Forge)

4. **Rotate secrets regularly**
   - Database passwords: Every 90 days
   - API keys: Every 180 days
   - SSL certificates: Before expiration

### Secrets to Protect

| Secret | Location | Risk Level | Action Required |
|--------|----------|------------|-----------------|
| `DB_PASSWORD` | Line 16 | 🔴 CRITICAL | Move to secret manager |
| `VIETTEL_S3_SECRET` | Line 105 | 🔴 CRITICAL | Move to secret manager |
| `MAIL_PASSWORD` | Line 98 | 🔴 HIGH | Move to secret manager |
| `AI_MAIN_API_KEY` | Line 85 | 🟡 MEDIUM | Move to secret manager |
| `JWT_SECRET` | Line 80 | 🔴 CRITICAL | Rotate and move |
| `REGISTRY` (GitHub PAT) | Line 90 | 🔴 CRITICAL | Use GitHub Secrets |

---

## Error Handling

### Secure Error Handling

**Implemented:** Stack traces are now logged server-side only, not displayed to users.

**Before (INSECURE):**
```php
catch (\Throwable $e) {
    return back()->with('error', 'Lỗi: '.$e->getMessage().'<br><pre>'.$e->getTraceAsString().'</pre>');
}
```

**After (SECURE):**
```php
catch (\Throwable $e) {
    \Log::error('Failed to create training schedule', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => auth()->id(),
    ]);

    return back()->with('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
}
```

### Logging Best Practices

1. **Use structured logging**
   ```php
   \Log::error('Operation failed', [
       'operation' => 'create_schedule',
       'user_id' => auth()->id(),
       'error' => $e->getMessage(),
   ]);
   ```

2. **Configure log rotation**
   ```env
   LOG_CHANNEL=daily
   ```

3. **Monitor logs**
   - Use `php artisan pail` for real-time monitoring
   - Set up log aggregation (ELK stack, Papertrail)

---

## Security Testing

### Manual Testing

```bash
# 1. Test cookie flags
curl -I -c cookies.txt http://localhost/login
cat cookies.txt

# 2. Test security headers
curl -I http://localhost/

# 3. Test CSRF protection
curl -X POST http://localhost/training-schedules -d "name=test"
# Should return 419 (CSRF token mismatch)
```

### Automated Security Scanning

```bash
# Install security checker
composer require --dev enlightn/security-checker

# Run security audit
php artisan security:check

# Check for vulnerable dependencies
composer audit
```

### Online Security Scanners

1. **Security Headers**
   - https://securityheaders.com
   - Check HTTP headers configuration

2. **SSL Labs**
   - https://www.ssllabs.com/ssltest/
   - Verify SSL/TLS configuration

3. **OWASP ZAP**
   - https://www.zaproxy.org/
   - Automated vulnerability scanning

### Penetration Testing Checklist

- [ ] SQL Injection (test with sqlmap)
- [ ] XSS (test with XSStrike)
- [ ] CSRF (verify tokens required)
- [ ] Clickjacking (verify X-Frame-Options)
- [ ] Session hijacking (verify Secure flag on HTTPS)
- [ ] Brute force protection (consider rate limiting)
- [ ] File upload vulnerabilities
- [ ] Authentication bypass

---

## Incident Response

### In Case of Security Breach

1. **Immediate Actions**
   ```bash
   # Invalidate all sessions
   php artisan session:clear

   # Clear caches
   php artisan cache:clear
   php artisan config:clear

   # Rotate application key
   php artisan key:generate
   ```

2. **Investigate**
   - Check logs: `storage/logs/laravel.log`
   - Review database access logs
   - Check for unauthorized file modifications

3. **Notify**
   - Inform affected users
   - Report to relevant authorities (if required by GDPR/local laws)

4. **Prevent**
   - Patch vulnerabilities
   - Update dependencies
   - Review access controls

---

## Compliance

### OWASP Top 10 (2021)

| Risk | Status | Mitigation |
|------|--------|------------|
| A01:2021 - Broken Access Control | ✅ | RBAC with Spatie Permission |
| A02:2021 - Cryptographic Failures | ✅ | Session encryption, HTTPS |
| A03:2021 - Injection | ✅ | Eloquent ORM, prepared statements |
| A04:2021 - Insecure Design | ⚠️ | Ongoing review |
| A05:2021 - Security Misconfiguration | ✅ | Security headers, proper config |
| A06:2021 - Vulnerable Components | ⚠️ | Run `composer audit` regularly |
| A07:2021 - Authentication Failures | ✅ | Laravel Sanctum/session auth |
| A08:2021 - Software/Data Integrity | ⚠️ | Verify file uploads |
| A09:2021 - Logging Failures | ✅ | Structured logging implemented |
| A10:2021 - SSRF | ✅ | No user-controlled URLs |

### GDPR Compliance

- ✅ Data encryption (session encryption)
- ✅ Secure data transmission (HTTPS)
- ⚠️ Data retention policies (to be implemented)
- ⚠️ Right to erasure (to be implemented)

---

## Support & Questions

For security-related questions or to report vulnerabilities:

1. **Do NOT** create public GitHub issues for security vulnerabilities
2. Email: [Add security contact email]
3. For urgent issues: [Add emergency contact]

---

## Changelog

### 2025-12-13
- ✅ Implemented SecurityHeaders middleware
- ✅ Enabled session encryption by default
- ✅ Added cookie security configuration
- ✅ Fixed error handling to prevent information disclosure
- ✅ Created security documentation

---

**Last Updated:** 2025-12-13
**Next Review:** 2026-01-13 (Monthly security review recommended)
