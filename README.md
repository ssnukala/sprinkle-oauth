# OAuth Sprinkle for UserFrosting 6

[![Tests](https://github.com/ssnukala/sprinkle-oauth/actions/workflows/tests.yml/badge.svg)](https://github.com/ssnukala/sprinkle-oauth/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

OAuth authentication sprinkle for UserFrosting 6, enabling OAuth login using Google, Meta (Facebook, Instagram), Microsoft (Outlook), and LinkedIn.

**Uses Official Vendor SDKs** for better compatibility and up-to-date OAuth implementations.

## Features

- **Multiple OAuth Providers**: Support for Google, Facebook, LinkedIn, and Microsoft
- **PKCE Support**: Full OAuth 2.1 PKCE (Proof Key for Code Exchange) for defense-in-depth security
- **Auto User Creation**: Automatically creates UserFrosting accounts from OAuth authentication
- **Multiple Provider Support**: Users can link multiple OAuth providers to a single account
- **Popup OAuth Flow**: postMessage-based popup window flow — no page redirect needed
- **Google Sheets Integration**: Read from and write to Google Sheets via authenticated API
- **Login Screen**: Login page with OAuth buttons and traditional login
- **Easy Configuration**: Simple environment-based configuration
- **Secure**: CSRF protection, PKCE, and secure token handling
- **Official SDKs**: Uses official vendor packages for reliability

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

**OAuth Authentication:**
- `GET /oauth/login` - OAuth login page
- `GET /oauth/{provider}` - Redirect to OAuth provider (supports `?popup=1` for popup mode)
- `GET /oauth/{provider}/callback` - OAuth callback handler
- `GET /oauth/link/{provider}` - Link provider to existing account
- `GET /api/oauth/connections` - Get current user's OAuth connections (JSON)

**Google Sheets:**
- `GET /api/oauth/sheets/read` - Read data from a Google Sheet
- `POST /api/oauth/sheets/append` - Append rows to a Google Sheet

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

### Design Decision: Separate OAuth Table

This sprinkle uses a separate `oauth_connections` table rather than integrating with UserFrosting's `persistences` table. This design decision was made for the following reasons:

**Different Purposes:**
- `persistences` table manages session tokens and remember-me functionality
- `oauth_connections` table links users to external OAuth provider accounts

**Different Data Models:**
- Persistence stores simple token pairs with expiration
- OAuth stores provider name, provider user ID, access tokens, refresh tokens, and provider-specific user data

**Different Lifecycles:**
- Persistence tokens expire and are recreated frequently during user sessions
- OAuth connections persist long-term with token refresh capability

**Multiple Providers:**
- Users can link multiple OAuth providers simultaneously (e.g., Google + Facebook + LinkedIn)
- Each provider connection is a separate record with its own tokens and metadata

This approach follows UserFrosting 6's pattern of using separate tables for separate concerns (similar to how roles, permissions, and activities each have their own tables).

### Frontend Assets

This sprinkle includes Vue.js components and TypeScript assets that are deployed to `node_modules` when installed via npm, following the UserFrosting 6 Admin Sprinkle pattern.

#### For End Users

When you install this sprinkle via Composer in your UserFrosting app:
1. The frontend assets are automatically available in `node_modules/@ssnukala/sprinkle-oauth`
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
import OAuthSprinkle from '@ssnukala/sprinkle-oauth'

// Import individual components
import { OAuthLoginView } from '@ssnukala/sprinkle-oauth/views'
import { OAuthConnections } from '@ssnukala/sprinkle-oauth/components'

// Import composables
import { useOAuth } from '@ssnukala/sprinkle-oauth/composables'
import { useGoogleSheets } from '@ssnukala/sprinkle-oauth/composables'

// Import TypeScript interfaces
import type { OAuthProvider, OAuthConnection, OAuthResult } from '@ssnukala/sprinkle-oauth/interfaces'
import type { SheetsReadResponse, SheetsAppendRequest } from '@ssnukala/sprinkle-oauth/interfaces'

// Import routes
import { oauthRoutes } from '@ssnukala/sprinkle-oauth/routes'
```

See [app/assets/README.md](app/assets/README.md) for detailed frontend documentation.

### Key Components

Following UserFrosting 6 patterns and standards:

- **Model Layer**:
  - `OAuthConnection` - Extends `UserFrosting\Sprinkle\Core\Database\Models\Model`
  - `OAuthConnectionInterface` - Defines contract with scopes: `notExpired()`, `forProvider()`, `joinUser()`
  - Follows same patterns as UserFrosting's `Persistence`, `Activity`, and other core models

- **Repository Layer**:
  - `OAuthConnectionRepository` - Data access for OAuth connections
  - Type-safe methods for finding, creating, updating, and deleting connections
  - Follows UserFrosting repository pattern conventions

- **Authenticator Layer**:
  - `OAuthAuthenticator` - Handles OAuth user authentication flow
  - Creates new users from OAuth data with proper type declarations
  - Links OAuth providers to existing users
  - Uses dependency injection for UserInterface

- **Controller Layer**:
  - `OAuthController` - HTTP request handling for OAuth flows
  - Redirect to provider authorization
  - OAuth callback processing
  - Provider linking/unlinking

- **Service Providers**:
  - `OAuthServicesProvider` - Registers all OAuth services and controllers in DI container
  - Follows `ServicesProviderInterface` pattern

- **Google Sheets**:
  - `GoogleSheetsService` - Read/write Google Sheets with automatic token refresh
  - `GoogleSheetsController` - REST endpoints for Sheets operations

- **Routes**:
  - `OAuthRoutes` - Implements `RouteDefinitionInterface`
  - RESTful route definitions for OAuth endpoints

- **Database**:
  - `CreateOAuthConnectionsTable` - Migration extending UserFrosting's Migration class
  - Proper foreign keys, unique constraints, and indexes

### OAuth Flow

1. User clicks OAuth provider button
2. PKCE code_verifier + code_challenge generated and stored in session
3. Redirected to provider's authorization page (with code_challenge)
4. User authorizes the application
5. Provider redirects back to callback URL (or sends postMessage in popup mode)
6. Application exchanges code for access token (with code_verifier for PKCE verification)
7. User information is retrieved from provider
8. User is created or linked based on email
9. User is logged into UserFrosting

## PKCE (Proof Key for Code Exchange)

This sprinkle implements full PKCE support per OAuth 2.1 specification for defense-in-depth security:

1. During redirect, a cryptographically secure `code_verifier` + `code_challenge` (SHA-256) are generated
2. The `code_challenge` is sent with the authorization request
3. The `code_verifier` is stored in the session
4. During callback, the `code_verifier` is included in the token exchange request
5. The provider verifies the challenge, preventing authorization code interception attacks

PKCE is automatically applied to all providers (Google, Facebook, Microsoft, LinkedIn) — no configuration required.

## Popup OAuth Flow

OAuth authentication can run in a popup window instead of a full-page redirect:

```typescript
import { useOAuth } from '@ssnukala/sprinkle-oauth/composables'

const { openOAuthPopup } = useOAuth()

// Opens centered popup window, receives result via postMessage
const result = await openOAuthPopup('google')
if (result.success) {
    // User authenticated
}
```

The popup flow:
1. Opens a centered browser popup (600x700)
2. OAuth flow runs inside the popup
3. On completion, result is sent via `window.postMessage()` to the parent window
4. Popup auto-closes after sending result
5. Fallback: if popup blocked, falls back to redirect mode

## Google Sheets Integration

Read from and write to Google Sheets using the authenticated user's Google OAuth connection.

### Prerequisites

- Google OAuth configured with Sheets scope (`https://www.googleapis.com/auth/spreadsheets`)
- User has connected their Google account with offline access (`access_type: offline`)

### Backend API

**Read a Google Sheet:**
```
GET /api/oauth/sheets/read?spreadsheet_id=abc123&range=Sheet1!A1:D10

Response:
{
  "headers": ["Name", "Email", "Grade", "Status"],
  "rows": [
    {"Name": "John", "Email": "john@example.com", "Grade": "K", "Status": "Active"},
    {"Name": "Jane", "Email": "jane@example.com", "Grade": "1st", "Status": "Active"}
  ]
}
```

**Append rows to a Sheet:**
```
POST /api/oauth/sheets/append
{
  "spreadsheet_id": "abc123",
  "range": "Sheet1",
  "rows": [
    ["Alice", "alice@example.com", "2nd", "Active"],
    ["Bob", "bob@example.com", "3rd", "Active"]
  ]
}
```

### Frontend Composable

```typescript
import { useGoogleSheets } from '@ssnukala/sprinkle-oauth/composables'

const { readSheet, appendToSheet, importAsCSV, exportRows, loading, error } = useGoogleSheets()

// Read sheet data
const data = await readSheet('spreadsheet-id-or-url', 'Sheet1!A1:D10')

// Convert to CSV for DataChat import
const csvText = await importAsCSV('spreadsheet-id-or-url', 'Sheet1')

// Export rows to sheet
await exportRows('spreadsheet-id-or-url', 'Sheet1', rows)

// Append data
await appendToSheet('spreadsheet-id-or-url', 'Sheet1', [
    ['Alice', 'alice@example.com', '2nd', 'Active']
])
```

### GoogleSheetsService (PHP)

```php
use UserFrosting\Sprinkle\OAuth\Services\GoogleSheetsService;

// Read sheet data
$data = $sheetsService->readSheet($spreadsheetId, $range);
// Returns: ['headers' => [...], 'rows' => [{...}, ...]]

// Append rows
$result = $sheetsService->appendRows($spreadsheetId, $range, $rows);

// Extract spreadsheet ID from URL
$id = $sheetsService->extractSpreadsheetId($sheetUrl);
```

The service automatically handles OAuth token refresh when the access token expires.

## Security

- CSRF protection via state parameter
- **PKCE** (Proof Key for Code Exchange) for OAuth 2.1 compliance
- Secure token storage
- Foreign key constraints on user relationships
- Unique constraints to prevent duplicate connections
- OAuth tokens are hidden from API responses
- Origin validation for postMessage popup communication

## Testing

This sprinkle includes a comprehensive test suite using PHPUnit. Tests follow UserFrosting 6 patterns and include:

- **Integration Tests**: Testing service and repository layers
- **Controller Tests**: Testing HTTP endpoints and OAuth flows
- **Factory Tests**: Testing OAuth provider factory

### Running Tests

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run with coverage report
vendor/bin/phpunit --coverage-html coverage/

# Run specific test
vendor/bin/phpunit --filter testMethodName
```

### Continuous Integration

Tests run automatically via GitHub Actions on:
- Pull requests
- Pushes to main/develop branches

See [app/tests/README.md](app/tests/README.md) for detailed testing documentation.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests for new features
4. Ensure all tests pass: `vendor/bin/phpunit`
5. Submit a Pull Request

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

## License

MIT License

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/ssnukala/sprinkle-oauth).
