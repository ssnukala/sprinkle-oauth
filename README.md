# OAuth Sprinkle for UserFrosting 6

OAuth authentication sprinkle for UserFrosting 6, enabling OAuth login using Google, Meta (Facebook, Instagram), Microsoft (Outlook), and LinkedIn.

**Uses Official Vendor SDKs** for better compatibility and up-to-date OAuth implementations.

## Features

- 🔐 **Multiple OAuth Providers**: Support for Google, Facebook, LinkedIn, and Microsoft
- 👤 **Auto User Creation**: Automatically creates UserFrosting accounts from OAuth authentication
- 🔗 **Multiple Provider Support**: Users can link multiple OAuth providers to a single account
- 🎨 **Login Screen**: Beautiful login page with OAuth buttons
- ⚡ **Easy Configuration**: Simple environment-based configuration
- 🛡️ **Secure**: CSRF protection and secure token handling
- 📦 **Official SDKs**: Uses official vendor packages for reliability

## Requirements

- UserFrosting 6.x
- PHP 8.1 or higher
- Composer

## Installation

1. Install the sprinkle via Composer:

```bash
composer require ssnukala/sprinkle-oauth
```

2. Add the sprinkle to your UserFrosting application in `app/sprinkles.php`:

```php
return [
    // ... other sprinkles
    \UserFrosting\Sprinkle\OAuth\OAuth::class,
];
```

3. Run migrations to create the OAuth connections table:

```bash
php bakery migrate
```

## Configuration

### OAuth Provider Setup

You need to register your application with each OAuth provider you want to use:

#### Google OAuth

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable Google+ API
4. Create OAuth 2.0 credentials (Web application)
5. Add authorized redirect URI: `https://yourdomain.com/oauth/google/callback`
6. Copy Client ID and Client Secret

#### Facebook OAuth

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app or select an existing one
3. Add "Facebook Login" product
4. Configure OAuth redirect URI: `https://yourdomain.com/oauth/facebook/callback`
5. Copy App ID and App Secret

#### LinkedIn OAuth

1. Go to [LinkedIn Developers](https://www.linkedin.com/developers/)
2. Create a new app
3. Add OAuth 2.0 redirect URL: `https://yourdomain.com/oauth/linkedin/callback`
4. Request access to "Sign In with LinkedIn" product
5. Copy Client ID and Client Secret

#### Microsoft OAuth

1. Go to [Azure Portal](https://portal.azure.com/)
2. Register a new application in Azure AD
3. Add redirect URI: `https://yourdomain.com/oauth/microsoft/callback`
4. Create a client secret
5. Copy Application (client) ID and Client Secret

### Environment Configuration

Add your OAuth credentials to your `.env` file:

```env
# Google OAuth
OAUTH_GOOGLE_CLIENT_ID=your-google-client-id
OAUTH_GOOGLE_CLIENT_SECRET=your-google-client-secret

# Facebook OAuth
OAUTH_FACEBOOK_CLIENT_ID=your-facebook-app-id
OAUTH_FACEBOOK_CLIENT_SECRET=your-facebook-app-secret

# LinkedIn OAuth
OAUTH_LINKEDIN_CLIENT_ID=your-linkedin-client-id
OAUTH_LINKEDIN_CLIENT_SECRET=your-linkedin-client-secret

# Microsoft OAuth
OAUTH_MICROSOFT_CLIENT_ID=your-microsoft-client-id
OAUTH_MICROSOFT_CLIENT_SECRET=your-microsoft-client-secret
```

Only providers with valid credentials will be shown on the login page.

## Usage

### Login with OAuth

Navigate to `/oauth/login` to see the OAuth login page with all configured providers.

Users can:
- Sign in with any configured OAuth provider
- Create a new account automatically via OAuth
- Use traditional username/password login alongside OAuth

### Linking OAuth Providers

Logged-in users can link additional OAuth providers to their account:

1. Navigate to user settings
2. Use the OAuth connections component
3. Click "Connect" for any provider
4. Authorize the connection

Include the OAuth connections component in your settings template:

```twig
{% include 'components/oauth-connections.html.twig' with {'userConnections': userConnections} %}
```

### Routes

The sprinkle provides the following routes:

- `GET /oauth/login` - OAuth login page
- `GET /oauth/{provider}` - Redirect to OAuth provider
- `GET /oauth/{provider}/callback` - OAuth callback handler
- `GET /oauth/link/{provider}` - Link provider to existing account

## Database Schema

The sprinkle creates an `oauth_connections` table:

| Column | Type | Description |
|--------|------|-------------|
| id | int | Primary key |
| user_id | int | UserFrosting user ID |
| provider | string | Provider name (google, facebook, linkedin, microsoft) |
| provider_user_id | string | User ID from the OAuth provider |
| access_token | text | OAuth access token |
| refresh_token | text | OAuth refresh token (nullable) |
| expires_at | timestamp | Token expiration time |
| user_data | json | Additional user data from provider |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

## Architecture

### Frontend Assets

This sprinkle includes Vue.js components and TypeScript assets that are deployed to `node_modules` when installed via npm, following the UserFrosting 6 Admin Sprinkle pattern.

#### For End Users

When you install this sprinkle via Composer in your UserFrosting app:
1. The frontend assets are automatically available in `node_modules/@userfrosting/sprinkle-oauth`
2. Your app's Vite build process will compile these assets along with other sprinkle assets
3. No additional configuration is needed - assets are consumed as TypeScript source files

#### For Sprinkle Developers

To develop the frontend assets within this sprinkle:

```bash
# Install dependencies
npm install

# Run type checking
npm run typecheck

# Build the library (for testing)
npm run build

# Start development server (if you create a dev entry point)
npm run dev
```

The package exports multiple entry points that can be imported in a UserFrosting app:

```typescript
// Import the full plugin
import OAuthSprinkle from '@userfrosting/sprinkle-oauth'

// Import individual components
import { OAuthLoginView } from '@userfrosting/sprinkle-oauth/views'
import { OAuthConnections } from '@userfrosting/sprinkle-oauth/components'

// Import composables
import { useOAuth } from '@userfrosting/sprinkle-oauth/composables'

// Import routes
import { oauthRoutes } from '@userfrosting/sprinkle-oauth/routes'
```

See [app/assets/README.md](app/assets/README.md) for detailed frontend documentation.

### Key Components

- **Model**: `OAuthConnection` - Eloquent model for OAuth connections (in `Database/Models`)
- **Repository**: `OAuthConnectionRepository` - Data access layer
- **Services**: 
  - `OAuthService` - OAuth provider factory and management
  - `OAuthAuthenticationService` - User creation and linking logic
- **Controller**: `OAuthController` - Handles OAuth flow
- **Migration**: `CreateOAuthConnectionsTable` - Database schema

### OAuth Flow

1. User clicks OAuth provider button
2. Redirected to provider's authorization page
3. User authorizes the application
4. Provider redirects back to callback URL
5. Application exchanges code for access token
6. User information is retrieved from provider
7. User is created or linked based on email
8. User is logged into UserFrosting

## Security

- CSRF protection via state parameter
- Secure token storage
- Foreign key constraints on user relationships
- Unique constraints to prevent duplicate connections
- OAuth tokens are hidden from API responses

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/ssnukala/sprinkle-oauth).
