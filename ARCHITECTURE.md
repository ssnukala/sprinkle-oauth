# Architecture and UserFrosting 6 Patterns

This document explains how the OAuth Sprinkle follows UserFrosting 6 architectural patterns and conventions.

## Folder Structure

The OAuth sprinkle follows the standard UserFrosting 6 folder structure, consistent with `userfrosting/sprinkle-core` and `userfrosting/sprinkle-admin`:

```
app/
├── config/                  # Configuration files
│   ├── default.php         # Default OAuth configuration
│   └── oauth.example.php   # Example configuration
├── locale/                  # Translations/i18n
│   └── en_US/
│       └── oauth.php       # English translations
├── src/                     # PHP source code
│   ├── Controller/          # HTTP controllers
│   │   └── OAuthController.php
│   ├── Database/            # Database layer
│   │   ├── Migrations/      # Database migrations
│   │   │   └── CreateOAuthConnectionsTable.php
│   │   └── Models/          # Eloquent models (Entities)
│   │       └── OAuthConnection.php
│   ├── Repository/          # Data access layer (Repository pattern)
│   │   └── OAuthConnectionRepository.php
│   ├── Routes/              # Route definitions
│   │   └── OAuthRoutes.php
│   ├── Service/             # Business logic services
│   │   ├── OAuthService.php
│   │   └── OAuthAuthenticationService.php
│   ├── ServicesProvider/    # Dependency injection configuration
│   │   ├── OAuthServicesProvider.php
│   │   └── OAuthControllerProvider.php
│   └── OAuth.php           # Main sprinkle class
└── templates/               # Twig templates
    ├── pages/
    │   └── oauth-login.html.twig
    └── components/
        └── oauth-connections.html.twig
```

## Key Architectural Patterns

### 1. Database/Models Pattern

Following UserFrosting 6 conventions, database entities are located in `Database/Models/` namespace:

```php
UserFrosting\Sprinkle\OAuth\Database\Models\OAuthConnection
```

This matches the pattern used in UserFrosting Account sprinkle:

```php
UserFrosting\Sprinkle\Account\Database\Models\User
```

**Why this pattern?**
- Organizes database-related code under `Database/` namespace
- Separates Models from Migrations
- Consistent with Eloquent conventions
- Matches UserFrosting core sprinkles

### 2. Repository Pattern

The repository layer provides an abstraction over data access:

```php
UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository
```

**Benefits:**
- Decouples business logic from data access
- Makes code more testable
- Allows easy swapping of data sources
- Centralizes query logic

**Example:**
```php
class OAuthConnectionRepository
{
    public function findByProviderAndProviderUserId(
        string $provider, 
        string $providerUserId
    ): ?OAuthConnection {
        return OAuthConnection::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }
}
```

### 3. Service Pattern

Business logic is encapsulated in service classes:

```php
UserFrosting\Sprinkle\OAuth\Service\OAuthService
UserFrosting\Sprinkle\OAuth\Service\OAuthAuthenticationService
```

**Services handle:**
- OAuth provider configuration
- Token exchange
- User information retrieval
- User creation and linking logic

**Example:**
```php
class OAuthService
{
    public function getAuthorizationUrl(string $provider): string
    {
        // Business logic for generating OAuth URLs
    }
    
    public function getAccessToken(string $provider, string $code): array
    {
        // Business logic for token exchange
    }
}
```

### 4. Controller Pattern

Controllers handle HTTP requests and responses:

```php
UserFrosting\Sprinkle\OAuth\Controller\OAuthController
```

**Responsibilities:**
- Route handling
- Request validation
- Response formatting
- Delegating to services

**Example:**
```php
class OAuthController
{
    public function __construct(
        protected OAuthService $oauthService,
        protected OAuthAuthenticationService $authService,
        protected Twig $view
    ) {}
    
    public function redirect(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'];
        $url = $this->oauthService->getAuthorizationUrl($provider);
        return $response->withHeader('Location', $url)->withStatus(302);
    }
}
```

### 5. Dependency Injection

Services are registered through service providers:

```php
UserFrosting\Sprinkle\OAuth\ServicesProvider\OAuthServicesProvider
```

**Example:**
```php
class OAuthServicesProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            OAuthService::class => function (ContainerInterface $c) {
                return new OAuthService(
                    $c->get(Config::class),
                    $c->get('settings')['site']['uri']['public']
                );
            },
        ];
    }
}
```

## Extending Patterns

### Extending Models (Like PDOStorage Pattern)

Similar to how UserFrosting provides extendable classes like `UserFrosting\Sprinkle\Account\Rememberme\PDOStorage`, you can extend OAuth components:

```php
namespace UserFrosting\Sprinkle\YourSprinkle\Database\Models;

use UserFrosting\Sprinkle\OAuth\Database\Models\OAuthConnection as BaseOAuthConnection;

class CustomOAuthConnection extends BaseOAuthConnection
{
    // Add custom fields
    protected $fillable = [
        ...parent::$fillable,
        'custom_field',
    ];
    
    // Add custom methods
    public function isTokenExpired(): bool
    {
        return $this->expires_at < now();
    }
}
```

### Extending Services

```php
namespace UserFrosting\Sprinkle\YourSprinkle\Service;

use UserFrosting\Sprinkle\OAuth\Service\OAuthService as BaseOAuthService;

class CustomOAuthService extends BaseOAuthService
{
    public function getAuthorizationUrl(string $providerName): string
    {
        $url = parent::getAuthorizationUrl($providerName);
        // Add custom logic
        return $url . '&custom_param=value';
    }
}
```

### Extending Repositories

```php
namespace UserFrosting\Sprinkle\YourSprinkle\Repository;

use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository as BaseRepository;

class CustomOAuthConnectionRepository extends BaseRepository
{
    public function findExpiredTokens(): Collection
    {
        return OAuthConnection::where('expires_at', '<', now())->get();
    }
}
```

### Registering Extended Classes

```php
namespace UserFrosting\Sprinkle\YourSprinkle\ServicesProvider;

class CustomOAuthServicesProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            // Override default implementations
            OAuthService::class => function (ContainerInterface $c) {
                return new CustomOAuthService(
                    $c->get(Config::class),
                    $c->get('settings')['site']['uri']['public']
                );
            },
        ];
    }
}
```

## Comparison with UserFrosting Core Sprinkles

### Account Sprinkle Pattern

```
userfrosting/sprinkle-account/
└── src/
    ├── Controller/
    ├── Database/
    │   ├── Migrations/
    │   └── Models/         # User, Role, Permission models
    ├── Repository/
    └── Rememberme/         # PDOStorage and other utilities
```

### OAuth Sprinkle Pattern (This Package)

```
ssnukala/sprinkle-oauth/
└── src/
    ├── Controller/
    ├── Database/
    │   ├── Migrations/
    │   └── Models/         # OAuthConnection model
    ├── Repository/
    └── Service/            # OAuth business logic
```

## Benefits of This Architecture

1. **Consistency**: Matches UserFrosting 6 core patterns
2. **Maintainability**: Clear separation of concerns
3. **Testability**: Easy to unit test individual components
4. **Extensibility**: Simple to extend and customize
5. **Readability**: Predictable file locations
6. **Scalability**: Easy to add new features

## Migration from Entity to Database/Models

Previous versions used `Entity/` namespace. This has been updated to `Database/Models/` for consistency:

**Before (v1.0.0 - v1.1.0):**
```php
UserFrosting\Sprinkle\OAuth\Entity\OAuthConnection
```

**After (v1.1.1+):**
```php
UserFrosting\Sprinkle\OAuth\Database\Models\OAuthConnection
```

If you extended the old namespace, update your imports:

```php
// Old
use UserFrosting\Sprinkle\OAuth\Entity\OAuthConnection;

// New
use UserFrosting\Sprinkle\OAuth\Database\Models\OAuthConnection;
```

## Best Practices

1. **Keep Controllers Thin**: Business logic belongs in services
2. **Use Repositories**: Abstract database queries
3. **Follow Namespace Conventions**: Match UserFrosting patterns
4. **Extend, Don't Modify**: Use inheritance and DI for customization
5. **Document Extensions**: Keep track of customizations

## References

- [UserFrosting Documentation](https://learn.userfrosting.com/)
- [UserFrosting Sprinkles](https://github.com/userfrosting)
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)
- [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)
- [Service Layer Pattern](https://martinfowler.com/eaaCatalog/serviceLayer.html)
