# OAuth Sprinkle Installation and Configuration Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation-steps)
3. [OAuth Provider Setup](#oauth-provider-setup)
4. [Configuration](#configuration)
5. [Testing the Installation](#testing-the-installation)
6. [Troubleshooting](#troubleshooting)

## Prerequisites

Before installing the OAuth sprinkle, ensure you have:

- UserFrosting 6.x installed and running
- PHP 8.1 or higher
- Composer installed
- Access to your application's `.env` file
- Accounts with OAuth providers you want to use

## Installation Steps

### Step 1: Install via Composer

```bash
composer require ssnukala/sprinkle-oauth
```

### Step 2: Register the Sprinkle

Edit your `app/sprinkles.php` file and add the OAuth sprinkle:

```php
<?php

return [
    \UserFrosting\Sprinkle\Core\Core::class,
    \UserFrosting\Sprinkle\Account\Account::class,
    \UserFrosting\Sprinkle\Admin\Admin::class,
    // Add the OAuth sprinkle here
    \UserFrosting\Sprinkle\OAuth\OAuth::class,
    // Your custom sprinkles below
];
```

### Step 3: Run Database Migration

Run the migration to create the `oauth_connections` table:

```bash
php bakery migrate
```

You should see output confirming the migration was successful.

### Step 4: Install Frontend Assets (If Using Vue Components)

If your UserFrosting app uses Vue.js and you want to use the OAuth sprinkle's frontend components, the assets are automatically available after installation. 

The sprinkle publishes its frontend assets to npm as `@ssnukala/sprinkle-oauth`, and they will be available in `node_modules` when you run:

```bash
npm install
```

Your app's build process (Vite) will automatically compile these assets along with other sprinkle assets. No additional configuration is needed.

To verify the package is available:

```bash
npm list @ssnukala/sprinkle-oauth
```

## OAuth Provider Setup

### Google OAuth Setup

1. **Go to Google Cloud Console**
   - Visit: https://console.cloud.google.com/

2. **Create or Select a Project**
   - Click on the project dropdown
   - Create a new project or select an existing one

3. **Enable Google+ API**
   - Go to "APIs & Services" > "Library"
   - Search for "Google+ API"
   - Click "Enable"

4. **Create OAuth Credentials**
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth client ID"
   - Choose "Web application"
   - Add name for your OAuth client

5. **Configure Redirect URIs**
   Add the following authorized redirect URI:
   ```
   https://yourdomain.com/oauth/google/callback
   ```
   For local development:
   ```
   http://localhost/oauth/google/callback
   ```

6. **Copy Credentials**
   - Copy the Client ID
   - Copy the Client Secret
   - Save these for configuration

### Facebook OAuth Setup

1. **Go to Facebook Developers**
   - Visit: https://developers.facebook.com/

2. **Create App**
   - Click "My Apps" > "Create App"
   - Select "Consumer" as the app type
   - Fill in app details

3. **Add Facebook Login**
   - In your app dashboard, click "Add Product"
   - Find "Facebook Login" and click "Set Up"

4. **Configure OAuth Redirect URIs**
   - Go to "Facebook Login" > "Settings"
   - Add Valid OAuth Redirect URIs:
   ```
   https://yourdomain.com/oauth/facebook/callback
   ```

5. **Get App Credentials**
   - Go to "Settings" > "Basic"
   - Copy the App ID
   - Copy the App Secret (click "Show")

6. **Make App Live**
   - Toggle the app status from "Development" to "Live" when ready

### LinkedIn OAuth Setup

1. **Go to LinkedIn Developers**
   - Visit: https://www.linkedin.com/developers/

2. **Create App**
   - Click "Create app"
   - Fill in required information
   - Agree to LinkedIn's terms

3. **Configure OAuth Settings**
   - Go to "Auth" tab
   - Add Redirect URLs:
   ```
   https://yourdomain.com/oauth/linkedin/callback
   ```

4. **Request Products**
   - Go to "Products" tab
   - Request access to "Sign In with LinkedIn"
   - Wait for approval (usually instant for basic profile access)

5. **Get Credentials**
   - Go to "Auth" tab
   - Copy the Client ID
   - Copy the Client Secret

### Microsoft OAuth Setup

1. **Go to Azure Portal**
   - Visit: https://portal.azure.com/

2. **Register Application**
   - Navigate to "Azure Active Directory"
   - Click "App registrations" > "New registration"
   - Enter application name
   - Select supported account types
   - Add Redirect URI:
   ```
   Web: https://yourdomain.com/oauth/microsoft/callback
   ```

3. **Create Client Secret**
   - Go to "Certificates & secrets"
   - Click "New client secret"
   - Add description and expiry
   - Copy the secret value (shown only once!)

4. **Note Application ID**
   - Go to "Overview"
   - Copy the "Application (client) ID"

5. **Configure API Permissions**
   - Go to "API permissions"
   - Verify these permissions are added:
     - Microsoft Graph > User.Read
     - Microsoft Graph > OpenId permissions

## Configuration

### Environment Variables

Copy `.env.example` to your main application's `.env` file or add these lines:

```env
# Google OAuth
OAUTH_GOOGLE_CLIENT_ID=your-google-client-id-here
OAUTH_GOOGLE_CLIENT_SECRET=your-google-client-secret-here

# Facebook OAuth
OAUTH_FACEBOOK_CLIENT_ID=your-facebook-app-id-here
OAUTH_FACEBOOK_CLIENT_SECRET=your-facebook-app-secret-here

# LinkedIn OAuth
OAUTH_LINKEDIN_CLIENT_ID=your-linkedin-client-id-here
OAUTH_LINKEDIN_CLIENT_SECRET=your-linkedin-client-secret-here

# Microsoft OAuth
OAUTH_MICROSOFT_CLIENT_ID=your-microsoft-client-id-here
OAUTH_MICROSOFT_CLIENT_SECRET=your-microsoft-client-secret-here
```

**Important**: Only add credentials for providers you want to enable. Providers without credentials will not appear on the login page.

### Custom Configuration (Optional)

You can override default OAuth configuration by creating a config file in your app sprinkle:

```php
// app/config/oauth.php
return [
    'oauth' => [
        'google' => [
            'clientId' => getenv('OAUTH_GOOGLE_CLIENT_ID'),
            'clientSecret' => getenv('OAUTH_GOOGLE_CLIENT_SECRET'),
            'scopes' => ['openid', 'email', 'profile'], // Custom scopes
        ],
        // ... other providers
    ],
];
```

## Testing the Installation

### 1. Clear Cache

```bash
php bakery clear-cache
```

### 2. Test OAuth Login Page

Navigate to: `http://yourdomain.com/oauth/login`

You should see:
- Traditional login form
- Buttons for each enabled OAuth provider
- Provider buttons should have correct branding

### 3. Test OAuth Flow

1. Click on an OAuth provider button (e.g., "Sign in with Google")
2. You should be redirected to the provider's login page
3. Log in with your provider account
4. Authorize the application
5. You should be redirected back and logged into UserFrosting
6. A new user account should be created automatically

### 4. Test Multiple Providers

1. Log in with one provider (e.g., Google)
2. Go to user settings
3. Find the OAuth connections component
4. Click "Connect" for another provider (e.g., LinkedIn)
5. Verify both connections are linked to your account

## Troubleshooting

### Common Issues

#### 1. OAuth Buttons Don't Appear

**Problem**: No OAuth buttons show on login page

**Solution**:
- Verify environment variables are set correctly
- Check `.env` file has valid credentials
- Clear cache: `php bakery clear-cache`
- Verify sprinkle is registered in `app/sprinkles.php`

#### 2. Redirect URI Mismatch

**Problem**: Error like "redirect_uri_mismatch" or "Invalid redirect URI"

**Solution**:
- Verify redirect URIs match exactly in provider settings
- Include trailing slashes consistently
- Check protocol (http vs https)
- For Google: `https://yourdomain.com/oauth/google/callback`
- For Facebook: `https://yourdomain.com/oauth/facebook/callback`
- etc.

#### 3. Invalid Client ID/Secret

**Problem**: "Invalid client" or authentication errors

**Solution**:
- Double-check credentials copied correctly
- Verify no extra spaces in `.env` file
- Regenerate credentials if needed
- For Facebook: Ensure app is in "Live" mode, not "Development"

#### 4. State Mismatch / CSRF Error

**Problem**: "Invalid OAuth state" error

**Solution**:
- Clear browser cookies and sessions
- Verify sessions are working correctly
- Check session configuration in UserFrosting

#### 5. User Not Created Automatically

**Problem**: OAuth succeeds but user not created

**Solution**:
- Check database permissions
- Verify `users` table exists
- Check application logs for errors
- Ensure email from OAuth provider is valid

#### 6. LinkedIn Connection Fails

**Problem**: LinkedIn OAuth not working

**Solution**:
- Verify "Sign In with LinkedIn" product is approved
- Check LinkedIn app has correct redirect URIs
- LinkedIn requires HTTPS in production

### Debug Mode

To enable debug logging for OAuth:

1. Set UserFrosting debug mode in `.env`:
```env
UF_MODE=debug
```

2. Check logs in `app/logs/` directory

3. Look for OAuth-related error messages

### Support Resources

- [UserFrosting Documentation](https://learn.userfrosting.com/)
- [OAuth 2.0 Documentation](https://oauth.net/2/)
- [GitHub Issues](https://github.com/ssnukala/sprinkle-oauth/issues)

## Next Steps

After successful installation:

1. **Customize Templates**: Modify `app/templates/pages/oauth-login.html.twig` to match your design
2. **Add to Navigation**: Link to `/oauth/login` from your navigation menu
3. **User Settings**: Include OAuth connections component in user profile/settings page
4. **Test All Providers**: Verify each configured provider works correctly
5. **Production Setup**: Ensure HTTPS is enabled for production use
6. **Monitor Usage**: Check database for oauth_connections to verify proper functionality

## Security Best Practices

1. **Always use HTTPS** in production
2. **Rotate secrets periodically**
3. **Store credentials securely** (use `.env`, never commit to git)
4. **Limit OAuth scopes** to minimum required
5. **Monitor OAuth connections** in database
6. **Keep dependencies updated**: Run `composer update` regularly
7. **Review OAuth app permissions** in provider dashboards

## Advanced Configuration

### Custom Scopes

To request additional data from providers, modify scopes in `app/config/oauth.php`:

```php
'google' => [
    'scopes' => ['openid', 'email', 'profile', 'https://www.googleapis.com/auth/calendar.readonly'],
],
```

### Custom User Data Extraction

Extend `OAuthAuthenticationService` to customize how user data is extracted from providers.

### Webhooks and Token Refresh

For advanced use cases, implement token refresh logic in your application to maintain long-lived access to provider APIs.
