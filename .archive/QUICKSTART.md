# Quick Start Guide - OAuth Sprinkle

Get up and running with OAuth authentication in 5 minutes!

## Prerequisites
- UserFrosting 6 installed
- Composer available
- At least one OAuth provider account (Google recommended for testing)

## Installation

### 1. Install the Sprinkle

```bash
composer require ssnukala/sprinkle-oauth
```

### 2. Register in `app/sprinkles.php`

```php
return [
    // ... existing sprinkles
    \UserFrosting\Sprinkle\OAuth\OAuth::class,
];
```

### 3. Run Migration

```bash
php bakery migrate
```

## Quick Setup with Google OAuth

### Get Google Credentials (2 minutes)

1. Go to https://console.cloud.google.com/
2. Create a new project
3. Enable Google+ API
4. Create OAuth credentials (Web application)
5. Add redirect URI: `http://localhost/oauth/google/callback`
6. Copy Client ID and Client Secret

### Configure Environment

Add to your `.env` file:

```env
OAUTH_GOOGLE_CLIENT_ID=your-client-id-here
OAUTH_GOOGLE_CLIENT_SECRET=your-client-secret-here
```

### Test It!

1. Clear cache: `php bakery clear-cache`
2. Navigate to: `http://localhost/oauth/login`
3. Click "Sign in with Google"
4. Log in with your Google account
5. Done! You're now logged into UserFrosting

## What's Next?

- [Full Installation Guide](INSTALL.md) - Complete setup for all providers
- [README](README.md) - Full documentation
- Add more providers (Facebook, LinkedIn, Microsoft)
- Customize the login template
- Add OAuth connections to user settings

## Common First-Time Issues

**Issue**: "OAuth provider not configured"
- **Fix**: Check `.env` has valid credentials

**Issue**: "Redirect URI mismatch"
- **Fix**: Verify URI in Google Console matches exactly: `http://localhost/oauth/google/callback`

**Issue**: No OAuth buttons showing
- **Fix**: Run `php bakery clear-cache` and refresh page

## Quick Testing Checklist

- [ ] Can access `/oauth/login` page
- [ ] See "Sign in with Google" button
- [ ] Clicking button redirects to Google
- [ ] After Google login, redirected back to app
- [ ] New user created automatically
- [ ] Can log in with same Google account again

## Need Help?

- Check [INSTALL.md](INSTALL.md) for detailed troubleshooting
- Open an issue on GitHub
- Review error logs in `app/logs/`

---

**Tip**: Start with Google OAuth first - it's the easiest to set up and test!
