# Frequently Asked Questions (FAQ)

## General Questions

### Q: What is the OAuth Sprinkle?
**A:** The OAuth Sprinkle is a UserFrosting 6 extension that adds OAuth 2.0 authentication support, allowing users to sign in using Google, Facebook, LinkedIn, or Microsoft accounts.

### Q: Do I need to use all four providers?
**A:** No! You can enable only the providers you want. Simply configure credentials for the providers you wish to use, and leave others empty in your `.env` file.

### Q: Can users still use traditional username/password login?
**A:** Yes! The OAuth Sprinkle adds OAuth options alongside traditional login. Users can choose either method.

### Q: Is it free to use?
**A:** Yes! The sprinkle is open-source and free to use under the MIT License.

---

## Installation & Setup

### Q: What UserFrosting version is required?
**A:** UserFrosting 6.x is required. The sprinkle is not compatible with UserFrosting 4 or 5.

### Q: What PHP version do I need?
**A:** PHP 8.1 or higher is required.

### Q: Do I need to pay for OAuth provider accounts?
**A:** No! All supported OAuth providers (Google, Facebook, LinkedIn, Microsoft) offer free OAuth applications for most use cases.

### Q: How long does setup take?
**A:** Basic setup with one provider (e.g., Google) takes about 5-10 minutes. Adding all four providers takes 20-30 minutes total.

### Q: Can I test OAuth on localhost?
**A:** Yes! All providers support localhost redirect URIs for development. Use `http://localhost/oauth/{provider}/callback` as your redirect URI.

---

## Configuration

### Q: Where do I store OAuth credentials?
**A:** Store credentials in your `.env` file using the format:
```env
OAUTH_GOOGLE_CLIENT_ID=your-id
OAUTH_GOOGLE_CLIENT_SECRET=your-secret
```

### Q: What if I don't want to use environment variables?
**A:** You can override OAuth configuration in your app's config file:
```php
// app/config/oauth.php
return [
    'oauth' => [
        'google' => [
            'clientId' => 'your-id',
            'clientSecret' => 'your-secret',
        ],
    ],
];
```
However, environment variables are recommended for security.

### Q: How do I disable a provider?
**A:** Simply remove or leave empty its credentials in the `.env` file. Providers without valid credentials won't appear on the login page.

### Q: Can I customize OAuth scopes?
**A:** Yes! See the `app/config/oauth.example.php` file for examples of customizing scopes for each provider.

---

## User Management

### Q: What happens when a user signs in with OAuth for the first time?
**A:** A new UserFrosting account is automatically created using:
- Email from OAuth provider
- First and last name from OAuth provider
- Auto-generated username based on email
- Random password (user won't need it)

### Q: Can existing users link OAuth accounts?
**A:** Yes! Users can link multiple OAuth providers to their existing account from the settings page.

### Q: What if a user tries to sign up with OAuth using an email that already exists?
**A:** The system will:
1. Find the existing user by email
2. Link the OAuth provider to that user
3. Log them in to their existing account

### Q: Can one user have multiple OAuth providers?
**A:** Yes! A single user can link Google, Facebook, LinkedIn, and Microsoft all to one account.

### Q: What username is generated for OAuth users?
**A:** The username is generated from the email address (part before @). If it exists, a number is appended (e.g., `john`, `john1`, `john2`).

### Q: Can OAuth users also set a password?
**A:** Yes! Users can use the "forgot password" feature to set a password, allowing them to also log in with traditional username/password.

---

## Security

### Q: Is OAuth secure?
**A:** Yes! OAuth 2.0 is an industry-standard protocol. The sprinkle implements CSRF protection using state parameters.

### Q: Where are OAuth tokens stored?
**A:** Access tokens are stored in the `oauth_connections` database table. Consider encrypting sensitive data at rest for high-security applications.

### Q: What happens if someone gains access to my OAuth client secret?
**A:** Immediately regenerate your OAuth credentials in the provider's console. Update your `.env` file with new credentials, and revoke the old ones.

### Q: Can I use OAuth over HTTP (not HTTPS)?
**A:** For development on localhost, yes. For production, you MUST use HTTPS. Most OAuth providers require HTTPS for production redirect URIs.

### Q: How are passwords handled for OAuth users?
**A:** OAuth users get a random 32-byte password that they don't know. They can only log in via OAuth or by resetting their password.

---

## Troubleshooting

### Q: OAuth buttons don't appear on the login page
**A:** Check these items:
1. Are credentials set in `.env`?
2. Did you run `php bakery clear-cache`?
3. Is the sprinkle registered in `app/sprinkles.php`?
4. Are environment variables being loaded?

### Q: I get "redirect_uri_mismatch" error
**A:** The redirect URI in your OAuth provider settings doesn't match exactly. Verify:
- Protocol: `http://` vs `https://`
- Domain: exact match including subdomains
- Path: `/oauth/{provider}/callback`
- No trailing slashes where not expected

### Q: I get "Invalid OAuth state" error
**A:** This usually indicates:
1. Session issues - verify sessions are working
2. Cookies blocked - check browser settings
3. Session timeout - try again immediately

Clear your browser cookies and try again.

### Q: Google OAuth works but Facebook doesn't
**A:** Check these Facebook-specific issues:
- Is your Facebook app in "Live" mode (not "Development")?
- Did you add Facebook Login product to your app?
- Are redirect URIs exactly correct?
- Is the Facebook app secret correct?

### Q: LinkedIn returns "Invalid scope" error
**A:** LinkedIn requires you to request and be approved for products:
1. Go to your LinkedIn app
2. Click "Products" tab
3. Request "Sign In with LinkedIn"
4. Wait for approval (usually instant)

### Q: Users are created but can't log in again
**A:** Check:
1. Are OAuth connections being saved? Check `oauth_connections` table
2. Are there any database errors in logs?
3. Is the user account enabled? (`flag_enabled = 1`)

### Q: After OAuth login, I get redirected to a 404 page
**A:** The redirect URL might be incorrect. Check:
1. Is there a valid route for `/dashboard`?
2. Check `$_SESSION['redirect_after_login']` if set
3. Verify UserFrosting's default redirect configuration

---

## Features & Functionality

### Q: Can I customize the login page design?
**A:** Yes! Copy `templates/pages/oauth-login.html.twig` to your app sprinkle and modify it.

### Q: Can I get the user's profile picture from OAuth?
**A:** Yes! Profile pictures are stored in the `user_data` JSON field of `oauth_connections` table. See EXAMPLES.md for code samples.

### Q: Can I access the user's Google Calendar or other provider APIs?
**A:** Yes! Access tokens are stored in `oauth_connections`. You can use them to make API calls. See EXAMPLES.md for implementation.

### Q: Do tokens expire?
**A:** Yes! Access tokens expire after a certain time (usually 1 hour). The `expires_at` field tracks expiration. Implement token refresh for long-term API access.

### Q: How do I implement token refresh?
**A:** This is not currently implemented in the sprinkle but can be added. You would:
1. Check if `expires_at` is past
2. Use `refresh_token` to get new access token
3. Update `access_token` and `expires_at` in database

### Q: Can I add GitHub or other OAuth providers?
**A:** Yes! See CONTRIBUTING.md for a guide on adding new providers. You'll need to:
1. Add the OAuth2 client library
2. Update `OAuthService`
3. Add configuration
4. Update templates

---

## Database

### Q: What tables does this sprinkle create?
**A:** Only one table: `oauth_connections`

### Q: Can I see what OAuth data is stored?
**A:** Yes! Query the `oauth_connections` table:
```sql
SELECT * FROM oauth_connections WHERE user_id = YOUR_USER_ID;
```

### Q: What if I want to remove all OAuth connections?
**A:** Truncate the table (WARNING: removes all connections):
```sql
TRUNCATE TABLE oauth_connections;
```
Or remove for specific user:
```sql
DELETE FROM oauth_connections WHERE user_id = YOUR_USER_ID;
```

### Q: Does deleting a user delete their OAuth connections?
**A:** Yes! The foreign key constraint has `ON DELETE CASCADE`, so connections are automatically deleted when a user is deleted.

---

## Performance

### Q: Does OAuth slow down my application?
**A:** OAuth login involves external API calls, so it's slightly slower than traditional login. However, this only affects the login process, not general application performance.

### Q: How many OAuth connections can a user have?
**A:** Technically unlimited, but typically users have 1-4 connections (one per provider).

### Q: Are there rate limits with OAuth providers?
**A:** Yes! Each provider has rate limits:
- Google: 10,000 requests/day (can request increase)
- Facebook: Varies by app and status
- LinkedIn: 500 requests/day for basic apps
- Microsoft: Varies by plan

---

## Advanced Topics

### Q: Can I use OAuth for API authentication?
**A:** The sprinkle is designed for web login. For API authentication, you would need to implement OAuth2 token authentication separately.

### Q: Can I restrict OAuth to specific email domains?
**A:** Yes! Extend `OAuthAuthenticationService` and add domain validation:
```php
if (!str_ends_with($email, '@yourdomain.com')) {
    throw new Exception('Only @yourdomain.com emails allowed');
}
```

### Q: Can I use OAuth with multi-tenancy?
**A:** Yes, but you'll need custom logic to:
1. Determine tenant from OAuth data
2. Create user in correct tenant
3. Manage cross-tenant OAuth connections

### Q: Can I migrate existing users to OAuth?
**A:** Yes! Users can link OAuth accounts to existing accounts by:
1. Logging in with username/password
2. Going to settings
3. Clicking "Connect" for desired provider

---

## Best Practices

### Q: Should I enable all four providers?
**A:** Enable providers that your users actually use. Start with 1-2 popular ones (Google, Microsoft) and add more based on demand.

### Q: How often should I rotate OAuth secrets?
**A:** Rotate every 6-12 months, or immediately if compromised.

### Q: Should I allow both OAuth and traditional login?
**A:** Yes! Give users options. Some prefer OAuth, others prefer username/password.

### Q: What should I do before going to production?
**A:** Checklist:
- [ ] Switch to HTTPS
- [ ] Update redirect URIs to production URLs
- [ ] Verify OAuth apps are in "Live/Production" mode
- [ ] Test all enabled providers
- [ ] Review security settings
- [ ] Set up monitoring/logging
- [ ] Document OAuth setup for team

---

## Support & Community

### Q: Where can I get help?
**A:** 
1. Check documentation: README.md, INSTALL.md, FLOW.md
2. Search existing GitHub issues
3. Open a new issue on GitHub
4. Join UserFrosting community forums

### Q: How do I report a bug?
**A:** Open an issue on GitHub with:
- Steps to reproduce
- Expected vs actual behavior
- Environment details (PHP version, UF version, provider)
- Error messages or logs

### Q: Can I contribute?
**A:** Yes! See CONTRIBUTING.md for guidelines. Contributions welcome for:
- Bug fixes
- New features
- Documentation improvements
- New OAuth provider support
- Translations

### Q: Is commercial support available?
**A:** Contact the maintainer directly for commercial support options.

---

## Upgrading

### Q: How do I upgrade to a new version?
**A:**
```bash
composer update ssnukala/sprinkle-oauth
php bakery migrate
php bakery clear-cache
```

### Q: Will upgrades affect existing OAuth connections?
**A:** No! The `oauth_connections` table schema is stable. Existing connections continue to work.

### Q: What's the upgrade policy?
**A:** 
- Patch versions (1.0.x): Bug fixes, no breaking changes
- Minor versions (1.x.0): New features, backward compatible
- Major versions (x.0.0): Breaking changes, migration guide provided

---

## Licensing

### Q: Can I use this in a commercial project?
**A:** Yes! The MIT License allows commercial use.

### Q: Do I need to credit the OAuth Sprinkle?
**A:** Not required by the license, but appreciated!

### Q: Can I modify the code?
**A:** Yes! The MIT License allows modification.

### Q: Can I redistribute the sprinkle?
**A:** Yes! The MIT License allows redistribution with attribution.

---

Still have questions? Open an issue on GitHub or check the documentation files!
