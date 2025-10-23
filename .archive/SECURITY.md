# Security Policy

## Supported Versions

Currently supported versions with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

We take security seriously. If you discover a security vulnerability, please follow these steps:

### Do NOT:
- Open a public GitHub issue
- Discuss the vulnerability publicly
- Exploit the vulnerability

### Do:
1. **Email the maintainer** at the email listed in composer.json
2. **Provide details** including:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)
3. **Wait for response** - we aim to respond within 48 hours

### What to Expect

1. **Acknowledgment**: We'll confirm receipt of your report within 48 hours
2. **Assessment**: We'll investigate and assess the severity
3. **Fix**: We'll develop and test a fix
4. **Release**: We'll release a security patch
5. **Disclosure**: We'll publicly disclose after fix is available
6. **Credit**: You'll be credited (unless you prefer to remain anonymous)

## Security Best Practices

### For Users

1. **Always use HTTPS** in production
2. **Keep dependencies updated**:
   ```bash
   composer update
   ```
3. **Secure credentials**:
   - Store OAuth credentials in `.env` file
   - Never commit `.env` to version control
   - Use strong, unique client secrets
4. **Rotate secrets regularly**:
   - Rotate OAuth client secrets every 6-12 months
   - Revoke unused OAuth applications
5. **Monitor OAuth connections**:
   - Regularly review `oauth_connections` table
   - Remove unused connections
6. **Validate redirect URIs**:
   - Keep redirect URIs up to date in provider settings
   - Use exact matches, not wildcards
7. **Enable logging**:
   - Monitor OAuth-related errors
   - Review access logs regularly

### For Developers

1. **Input Validation**:
   - Never trust user input
   - Validate and sanitize all OAuth data
2. **State Parameter**:
   - Always use state parameter for CSRF protection
   - Verify state on callback
3. **Token Storage**:
   - Store tokens securely
   - Encrypt sensitive data
   - Never log tokens
4. **Scope Limitation**:
   - Request only necessary OAuth scopes
   - Follow principle of least privilege
5. **Error Handling**:
   - Don't expose sensitive data in errors
   - Log errors securely
   - Show generic errors to users
6. **Dependencies**:
   - Keep OAuth libraries updated
   - Review security advisories
   - Use `composer audit` regularly

## Known Security Considerations

### OAuth State Parameter
The sprinkle uses PHP sessions to store OAuth state for CSRF protection. Ensure:
- Sessions are configured securely
- Session cookies use `httponly` and `secure` flags
- Session timeout is appropriate

### Token Storage
OAuth tokens are stored in the database:
- Tokens are stored as text (not encrypted by default)
- Access tokens are hidden from API responses
- Consider encrypting tokens at rest for high-security applications

### Password Generation
When users are auto-created via OAuth:
- A random password is generated
- User cannot log in with password without reset
- Consider implementing OAuth-only accounts flag

## Security Headers

Recommended security headers for production:

```nginx
# HTTPS only
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

# Prevent clickjacking
add_header X-Frame-Options "SAMEORIGIN" always;

# XSS protection
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;

# Content Security Policy
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
```

## Third-Party Dependencies

This sprinkle uses these security-critical dependencies:

- `league/oauth2-client` - OAuth 2.0 client library
- `league/oauth2-google` - Google provider
- `league/oauth2-facebook` - Facebook provider
- `league/oauth2-linkedin` - LinkedIn provider
- `stevenmaguire/oauth2-microsoft` - Microsoft provider

Keep these updated:
```bash
composer update league/oauth2-client league/oauth2-google league/oauth2-facebook league/oauth2-linkedin stevenmaguire/oauth2-microsoft
```

## Vulnerability Disclosure Timeline

We follow this disclosure timeline:

1. **Day 0**: Vulnerability reported
2. **Day 2**: Initial response sent
3. **Day 7**: Assessment complete
4. **Day 30**: Fix developed and tested
5. **Day 45**: Security release published
6. **Day 60**: Public disclosure

Timeline may be adjusted based on severity and complexity.

## Security Updates

Security updates are released as:
- **Critical**: Immediate patch release
- **High**: Patch within 1 week
- **Medium**: Patch in next minor release
- **Low**: Patch in next release

## Contact

For security issues: Use email listed in composer.json
For general issues: GitHub Issues

Thank you for helping keep OAuth Sprinkle secure!
