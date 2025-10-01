# API Reference - OAuth Sprinkle

## Classes and Components

### Core Components

#### `OAuth` (Main Sprinkle Class)

The main sprinkle recipe class that registers the OAuth functionality.

**Location**: `app/src/OAuth.php`

**Methods**:
- `getName(): string` - Returns "OAuth Sprinkle"
- `getPath(): string` - Returns sprinkle directory path
- `getRoutes(): array` - Returns route definition files
- `getSprinkles(): array` - Returns dependencies (Core sprinkle)
- `getServices(): array` - Returns service providers

---

### Entities

#### `OAuthConnection`

Eloquent model representing a connection between a user and an OAuth provider.

**Location**: `app/src/Entity/OAuthConnection.php`

**Properties**:
```php
int $id                    // Primary key
int $user_id              // UserFrosting user ID
string $provider          // Provider name (google, facebook, linkedin, microsoft)
string $provider_user_id  // OAuth provider's user ID
string $access_token      // OAuth access token (hidden from arrays)
string $refresh_token     // OAuth refresh token (nullable, hidden)
DateTime $expires_at      // Token expiration timestamp
array $user_data          // JSON data from provider
DateTime $created_at      // Record creation timestamp
DateTime $updated_at      // Record update timestamp
```

**Relationships**:
- `user()` - BelongsTo relationship to UserFrosting User model

**Table**: `oauth_connections`

---

### Repositories

#### `OAuthConnectionRepository`

Data access layer for OAuth connections.

**Location**: `app/src/Repository/OAuthConnectionRepository.php`

**Methods**:

```php
/**
 * Find OAuth connection by provider and provider user ID
 */
public function findByProviderAndProviderUserId(
    string $provider, 
    string $providerUserId
): ?OAuthConnection

/**
 * Find all OAuth connections for a user
 */
public function findByUserId(int $userId): Collection

/**
 * Find OAuth connection by user and provider
 */
public function findByUserIdAndProvider(
    int $userId, 
    string $provider
): ?OAuthConnection

/**
 * Create a new OAuth connection
 */
public function create(array $data): OAuthConnection

/**
 * Update an existing OAuth connection
 */
public function update(OAuthConnection $connection, array $data): bool

/**
 * Delete an OAuth connection
 */
public function delete(OAuthConnection $connection): bool
```

---

### Services

#### `OAuthService`

Factory service for creating and managing OAuth providers.

**Location**: `app/src/Service/OAuthService.php`

**Constructor**:
```php
public function __construct(array $config, string $baseUrl)
```

**Methods**:

```php
/**
 * Get OAuth provider instance
 * @throws InvalidArgumentException if provider not configured
 */
public function getProvider(string $providerName): AbstractProvider

/**
 * Get authorization URL for a provider
 */
public function getAuthorizationUrl(
    string $providerName, 
    array $options = []
): string

/**
 * Get OAuth state for CSRF protection
 */
public function getState(string $providerName): string

/**
 * Get list of enabled providers
 */
public function getEnabledProviders(): array
```

**Supported Providers**:
- `google` - Google OAuth
- `facebook` - Facebook OAuth
- `linkedin` - LinkedIn OAuth
- `microsoft` - Microsoft OAuth

---

#### `OAuthAuthenticationService`

Handles authentication, user creation, and OAuth connection management.

**Location**: `app/src/Service/OAuthAuthenticationService.php`

**Constructor**:
```php
public function __construct(OAuthConnectionRepository $connectionRepository)
```

**Methods**:

```php
/**
 * Find or create user from OAuth provider data
 * @return array ['user' => User, 'connection' => OAuthConnection, 'isNewUser' => bool]
 */
public function findOrCreateUser(
    string $provider, 
    array $providerUserData
): array

/**
 * Link OAuth provider to existing user
 */
public function linkProvider(
    int $userId, 
    string $provider, 
    array $providerUserData
): OAuthConnection
```

**Provider User Data Format**:
```php
[
    'id' => 'provider-user-id',
    'email' => 'user@example.com',
    'given_name' => 'John',
    'family_name' => 'Doe',
    'token' => [
        'access_token' => 'access-token-string',
        'refresh_token' => 'refresh-token-string',
        'expires' => 1234567890,
    ],
    // ... additional provider-specific fields
]
```

---

### Controllers

#### `OAuthController`

Handles OAuth authentication flows and HTTP requests.

**Location**: `app/src/Controller/OAuthController.php`

**Constructor**:
```php
public function __construct(
    OAuthService $oauthService,
    OAuthAuthenticationService $authService,
    Twig $view
)
```

**Methods**:

```php
/**
 * Redirect to OAuth provider for authentication
 * Route: GET /oauth/{provider}
 */
public function redirect(
    Request $request, 
    Response $response, 
    array $args
): Response

/**
 * Handle OAuth callback
 * Route: GET /oauth/{provider}/callback
 */
public function callback(
    Request $request, 
    Response $response, 
    array $args
): Response

/**
 * Display login page with OAuth options
 * Route: GET /oauth/login
 */
public function loginPage(
    Request $request, 
    Response $response
): Response

/**
 * Link OAuth provider to current user's account
 * Route: GET /oauth/link/{provider}
 */
public function linkProvider(
    Request $request, 
    Response $response, 
    array $args
): Response

/**
 * Disconnect OAuth provider from current user's account
 * Route: POST /oauth/disconnect/{provider}
 */
public function disconnect(
    Request $request, 
    Response $response, 
    array $args
): Response
```

---

### Routes

All routes are defined in `routes/oauth.php`:

| Method | Route | Handler | Name | Description |
|--------|-------|---------|------|-------------|
| GET | `/oauth/login` | `loginPage` | `oauth.login` | Display OAuth login page |
| GET | `/oauth/{provider}` | `redirect` | `oauth.redirect` | Redirect to OAuth provider |
| GET | `/oauth/{provider}/callback` | `callback` | `oauth.callback` | Handle OAuth callback |
| GET | `/oauth/link/{provider}` | `linkProvider` | `oauth.link` | Link provider to account |
| POST | `/oauth/disconnect/{provider}` | `disconnect` | `oauth.disconnect` | Disconnect provider |

**Route Parameters**:
- `{provider}`: OAuth provider name (google, facebook, linkedin, microsoft)

---

### Service Providers

#### `OAuthServicesProvider`

Registers services in the DI container.

**Location**: `app/src/ServicesProvider/OAuthServicesProvider.php`

**Registered Services**:
- `OAuthConnectionRepository`
- `OAuthService`
- `OAuthAuthenticationService`

#### `OAuthControllerProvider`

Registers controllers in the DI container.

**Location**: `app/src/ServicesProvider/OAuthControllerProvider.php`

**Registered Services**:
- `OAuthController`

---

## Templates

### `oauth-login.html.twig`

Login page with OAuth provider buttons.

**Location**: `templates/pages/oauth-login.html.twig`

**Variables**:
- `enabledProviders` (array): List of enabled provider names

**Example Usage**:
```twig
{% extends "pages/abstract/base.html.twig" %}
```

### `oauth-connections.html.twig`

Component for managing OAuth connections in user settings.

**Location**: `templates/components/oauth-connections.html.twig`

**Variables**:
- `userConnections` (array): User's current OAuth connections indexed by provider

**Example Usage**:
```twig
{% include 'components/oauth-connections.html.twig' with {
    'userConnections': userOAuthConnections
} %}
```

---

## Configuration

### Default Configuration

**Location**: `app/config/default.php`

**Structure**:
```php
[
    'oauth' => [
        '{provider}' => [
            'clientId' => 'your-client-id',
            'clientSecret' => 'your-client-secret',
            // Provider-specific options...
        ]
    ]
]
```

### Environment Variables

All OAuth credentials should be stored in `.env`:

```env
OAUTH_GOOGLE_CLIENT_ID=...
OAUTH_GOOGLE_CLIENT_SECRET=...
OAUTH_FACEBOOK_CLIENT_ID=...
OAUTH_FACEBOOK_CLIENT_SECRET=...
OAUTH_LINKEDIN_CLIENT_ID=...
OAUTH_LINKEDIN_CLIENT_SECRET=...
OAUTH_MICROSOFT_CLIENT_ID=...
OAUTH_MICROSOFT_CLIENT_SECRET=...
```

---

## Database Schema

### `oauth_connections` Table

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Primary key |
| user_id | INT UNSIGNED | INDEX, FOREIGN KEY | UserFrosting user ID |
| provider | VARCHAR(50) | INDEX | Provider name |
| provider_user_id | VARCHAR(255) | INDEX | Provider's user ID |
| access_token | TEXT | | OAuth access token |
| refresh_token | TEXT | NULLABLE | OAuth refresh token |
| expires_at | TIMESTAMP | NULLABLE | Token expiration |
| user_data | JSON | NULLABLE | Provider user data |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Indexes**:
- PRIMARY KEY: `id`
- INDEX: `user_id`
- INDEX: `provider`
- INDEX: `provider_user_id`
- UNIQUE: `user_id, provider, provider_user_id`

**Foreign Keys**:
- `user_id` → `users.id` (CASCADE on delete)

---

## Translations

### Locale Files

**Location**: `app/locale/{locale}/oauth.php`

**Available Locales**:
- `en_US` (English - US)

**Translation Keys**:
```php
'OAUTH.LOGIN.TITLE'
'OAUTH.LOGIN.SUBTITLE'
'OAUTH.LOGIN.OR'
'OAUTH.LOGIN.GOOGLE'
'OAUTH.LOGIN.FACEBOOK'
'OAUTH.LOGIN.LINKEDIN'
'OAUTH.LOGIN.MICROSOFT'
'OAUTH.CONNECTIONS.TITLE'
'OAUTH.CONNECTIONS.DESCRIPTION'
'OAUTH.CONNECTIONS.CONNECTED'
'OAUTH.CONNECTIONS.CONNECT'
'OAUTH.CONNECTIONS.DISCONNECT'
'OAUTH.CONNECTIONS.DISCONNECT_CONFIRM'
'OAUTH.CONNECTIONS.ERROR'
```

---

## Events and Hooks

Currently, the sprinkle does not emit custom events. This may be added in future versions for extensibility.

**Potential Future Events**:
- `oauth.user.created` - When a new user is created via OAuth
- `oauth.connection.created` - When a new OAuth connection is created
- `oauth.connection.deleted` - When an OAuth connection is deleted
- `oauth.callback.success` - When OAuth callback succeeds
- `oauth.callback.failure` - When OAuth callback fails

---

## Error Handling

### Session Flash Messages

The controller uses UserFrosting's session alerts:

```php
$_SESSION['alerts']['success'][] = 'Success message';
$_SESSION['alerts']['danger'][] = 'Error message';
$_SESSION['alerts']['warning'][] = 'Warning message';
$_SESSION['alerts']['info'][] = 'Info message';
```

### Exceptions

Common exceptions thrown:
- `InvalidArgumentException` - Provider not configured or invalid
- Generic `Exception` - OAuth flow errors

---

## Extension Points

### Adding Custom Providers

Extend `OAuthService` and add case in `getProvider()` method:

```php
case 'custom-provider':
    return new CustomProvider($providerConfig);
```

### Customizing User Creation

Extend `OAuthAuthenticationService` and override `createUserFromOAuth()`:

```php
protected function createUserFromOAuth(
    string $provider, 
    array $providerUserData, 
    string $email
) {
    // Custom user creation logic
}
```

### Custom Scopes

Override in configuration:

```php
'oauth' => [
    'google' => [
        'scopes' => ['openid', 'email', 'profile', 'calendar.readonly'],
    ],
]
```

---

## Version Information

- **Current Version**: 1.0.0
- **UserFrosting Compatibility**: 6.x
- **PHP Requirement**: 8.1+

---

## Dependencies

### OAuth Client Libraries

- `league/oauth2-client` ^2.7
- `league/oauth2-google` ^4.0
- `league/oauth2-facebook` ^2.2
- `league/oauth2-linkedin` ^4.0
- `stevenmaguire/oauth2-microsoft` ^2.2

### UserFrosting Dependencies

- `userfrosting/framework` ^5.1

---

## Support

- **Documentation**: See README.md, INSTALL.md, QUICKSTART.md
- **Issues**: https://github.com/ssnukala/sprinkle-oauth/issues
- **Contributing**: See CONTRIBUTING.md
- **Security**: See SECURITY.md
