# GitHub Copilot Instructions for OAuth Sprinkle

## UserFrosting 6 Framework Compliance

Any code modifications or refactoring in this project **must** consider the UserFrosting 6 framework standards and patterns. This sprinkle is built to extend UserFrosting 6 and should adhere to its architectural patterns and conventions.

## Reference Repositories

When making code changes, refer to the following official UserFrosting 6 repositories for guidance on patterns, standards, and reusable components:

### Core Framework & Sprinkles
- **UserFrosting Core Sprinkle**: https://github.com/userfrosting/sprinkle-core/tree/6.0
  - Reference for core sprinkle structure, base entities, repositories, and services
  - Patterns for authentication, authorization, and user management
  
- **UserFrosting Admin Sprinkle**: https://github.com/userfrosting/sprinkle-admin/tree/6.0
  - Reference for admin functionality, CRUD operations, and UI patterns
  - Examples of controllers, forms, and data tables
  
- **UserFrosting Framework**: https://github.com/userfrosting/framework/tree/6.0
  - Core framework components and interfaces
  - Service providers, dependency injection patterns
  - Configuration, routing, and middleware patterns

- **Pink Cupcake Theme**: https://github.com/userfrosting/theme-pink-cupcake/tree/6.0
  - Reference for Twig templates, UI components
  - Front-end patterns and styling conventions

## Development Guidelines

### 1. Extend and Reuse Core Components

**Always look for existing framework components** before creating new ones:
- Extend existing entities from `sprinkle-core` (e.g., `User`, `Role`, `Permission`)
- Use existing repositories pattern from the framework
- Leverage service providers and dependency injection containers
- Reuse existing Twig templates and components

### 2. Follow UserFrosting Patterns

**Architectural patterns to follow:**
- **Sprinkle Recipe Pattern**: Implement `SprinkleRecipe` interface for sprinkle definition
- **Service Provider Pattern**: Use `ServicesProviderInterface` for dependency injection
- **Repository Pattern**: Create repositories extending framework base repositories
- **Entity Pattern**: Use Eloquent models following UserFrosting conventions
- **Controller Pattern**: Extend from framework base controllers where applicable
- **Route Definition**: Implement `RouteDefinitionInterface` for route registration

### 3. Code Standards

**Follow these standards consistently:**
- **PSR-12** coding standards for PHP
- **PSR-4** autoloading conventions
- **PHPDoc** comments for all classes and public methods
- **Type declarations** for all method parameters and return types
- **Dependency injection** over service locators or global state

### 4. Configuration & Environment

**Configuration management:**
- Use UserFrosting's `Config` service for all configuration
- Store sensitive data in `.env` files (never commit credentials)
- Follow the configuration schema patterns from `sprinkle-core`
- Use environment-based configuration loading

### 5. Database & Migrations

**Database best practices:**
- Create migrations following UserFrosting conventions
- Use Eloquent ORM for database operations
- Follow naming conventions for tables, columns, and relationships
- Implement proper foreign keys and cascade rules
- Use repository pattern for data access

### 6. Templates & UI

**Twig template guidelines:**
- Extend base templates from `theme-pink-cupcake` or `sprinkle-core`
- Use template inheritance and include patterns
- Follow naming conventions for templates
- Leverage existing UI components and macros
- Ensure responsive design using framework patterns

### 7. Translation & Localization

**Internationalization:**
- Store all user-facing strings in locale files
- Follow the locale structure from `sprinkle-core`
- Use UserFrosting's translation service (`Translator`)
- Support multiple languages from the start

### 8. Security

**Security considerations:**
- Use CSRF protection for state-changing operations
- Follow authentication patterns from `sprinkle-core`
- Implement proper authorization checks using permissions
- Validate and sanitize all user input
- Use parameterized queries (via Eloquent)
- Store sensitive data (tokens, secrets) securely

### 9. Testing

**Testing approach:**
- Write tests following UserFrosting testing patterns (see [app/tests/README.md](../app/tests/README.md))
- Use PHPUnit for unit and integration tests
- All tests extend `OAuthTestCase` which configures the test environment
- Mock dependencies appropriately (especially external OAuth provider APIs)
- Test both success and failure scenarios
- Maintain test coverage above 80%

**Test Structure:**
```
app/tests/
├── OAuthTestCase.php          # Base test case
├── Controller/                # Controller tests
├── Integration/               # Service & repository tests
└── README.md                  # Testing guide
```

**Running Tests:**
```bash
# Run all tests
vendor/bin/phpunit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Run specific test
vendor/bin/phpunit --filter testMethodName
```

**Writing Tests:**
- Use factories for test data (e.g., `User::factory()->create()`)
- Access services via DI container: `$this->ci->get(ServiceClass::class)`
- Set test config: `$this->ci->get('config')->set('oauth.google', [...])`
- Mock external APIs: Never make real OAuth API calls in tests
- Use `refreshDatabase()` for clean database state

**Test Examples:**
```php
// Test service from container
public function testServiceFromContainer(): void
{
    $factory = $this->ci->get(OAuthProviderFactory::class);
    $this->assertInstanceOf(OAuthProviderFactory::class, $factory);
}

// Test repository operations
public function testCanCreateConnection(): void
{
    $user = User::factory()->create();
    $repo = $this->ci->get(OAuthConnectionRepository::class);
    
    $connection = $repo->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_user_id' => '123',
        'access_token' => 'token',
    ]);
    
    $this->assertNotNull($connection->id);
}

// Test HTTP endpoints
public function testLoginPageLoads(): void
{
    $request = $this->createRequest('GET', '/oauth/login');
    $response = $this->handleRequest($request);
    
    $this->assertEquals(200, $response->getStatusCode());
}
```

### 10. Documentation

**Documentation requirements:**
- Add inline PHPDoc comments for all classes and methods
- Update README.md for user-facing changes
- Add examples for new features
- Document configuration options
- Include upgrade guides for breaking changes

## Project-Specific Context

### OAuth Sprinkle Structure

This sprinkle provides OAuth authentication for UserFrosting 6. Key components:

- **Model Layer**: `OAuthConnection` model implements `OAuthConnectionInterface`
  - Extends `UserFrosting\Sprinkle\Core\Database\Models\Model`
  - Manages OAuth provider connections (separate from session persistence)
  - Supports multiple OAuth providers per user (Google, Facebook, LinkedIn, Microsoft)
- **Interface Layer**: `OAuthConnectionInterface` defines the contract for OAuth connections
  - Follows UserFrosting 6 interface patterns (similar to `PersistenceInterface`)
  - Includes scopes: `notExpired()`, `forProvider()`, `joinUser()`
- **Repository Layer**: `OAuthConnectionRepository` manages OAuth connection data access
  - Provides CRUD operations for OAuth connections
  - Implements repository pattern following UserFrosting conventions
- **Authenticator Layer**: `OAuthAuthenticator` handles OAuth user flow
  - Creates new users from OAuth data
  - Links OAuth providers to existing users
  - Manages OAuth tokens and refresh logic
- **Controller Layer**: `OAuthController` handles HTTP requests for OAuth flows
  - Redirect to provider authorization
  - Handle OAuth callbacks
  - Link/unlink providers
- **Routes**: `OAuthRoutes` defines OAuth-specific routes
  - `/oauth/{provider}` - Redirect to OAuth provider
  - `/oauth/{provider}/callback` - OAuth callback handler
  - `/oauth/link/{provider}` - Link provider to existing account

### Architecture Decision: Separate OAuth Table

**Why OAuth connections are stored in a separate table, not in the `persistences` table:**

1. **Different Purpose**: 
   - `persistences` table: Session tokens and remember-me functionality
   - `oauth_connections` table: OAuth provider account linking and tokens

2. **Different Data Models**:
   - Persistence: Simple token pairs with expiration
   - OAuth: Provider, provider_user_id, access_token, refresh_token, provider-specific user_data

3. **Different Lifecycles**:
   - Persistence: Tokens expire and are recreated frequently
   - OAuth: Long-term connections with token refresh capability

4. **UserFrosting 6 Pattern**: Follows the same separation pattern as roles, permissions, activities

5. **Multiple Providers**: Users can link multiple OAuth providers (Google + Facebook + LinkedIn simultaneously)

This design decision maintains clean separation of concerns and follows UserFrosting 6 architectural patterns.

### Integration Points

When modifying this sprinkle, ensure compatibility with:
- UserFrosting's `User` entity and authentication system
- UserFrosting's `Model` base class and DI container patterns
- Existing route definitions and middleware
- Core sprinkle's service providers
- The bakery CLI command system
- UserFrosting's alert/notification system

### OAuth-Specific Patterns

**Model Pattern:**
```php
class OAuthConnection extends Model implements OAuthConnectionInterface
{
    // Use UserFrosting base Model, not Laravel's
    // Implement interface with scopes
    // Follow proper relationship patterns
}
```

**Scopes Pattern:**
```php
// Filter expired connections
OAuthConnection::notExpired()->get();

// Filter by provider
OAuthConnection::forProvider('google')->get();

// Join with user table
OAuthConnection::joinUser()->get();
```

**Repository Pattern:**
```php
class OAuthConnectionRepository
{
    public function findByProviderAndProviderUserId(string $provider, string $providerUserId): ?OAuthConnection
    public function findByUserId(int $userId)
    public function create(array $data): OAuthConnection
}
```

## Before Making Changes

1. **Review the framework code** in the reference repositories to understand existing patterns
2. **Check for existing components** that can be reused or extended
3. **Run existing tests** to establish baseline: `vendor/bin/phpunit`
4. **Follow the established patterns** rather than creating new approaches
5. **Write tests for new features** before or alongside implementation
6. **Test integration** with UserFrosting core functionality
7. **Update documentation** to reflect changes
8. **Run tests again** to ensure nothing broke: `vendor/bin/phpunit`

## Common Pitfalls to Avoid

- ❌ Don't reinvent components that exist in the framework
- ❌ Don't violate PSR-12 or framework coding standards
- ❌ Don't hardcode configuration values
- ❌ Don't bypass the dependency injection container
- ❌ Don't create routes without using `RouteDefinitionInterface`
- ❌ Don't access database directly without using repositories
- ❌ Don't forget to add translations for user-facing text
- ❌ Don't ignore error handling and validation
- ❌ Don't skip writing tests for new features
- ❌ Don't make real API calls in tests (always mock external services)
- ❌ Don't commit code without running the test suite

## Resources

- **UserFrosting Documentation**: https://learn.userfrosting.com/
- **UserFrosting Chat**: https://chat.userfrosting.com/
- **UserFrosting GitHub**: https://github.com/userfrosting
- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **Testing Guide**: [app/tests/README.md](../app/tests/README.md)

## Development Workflow Example

**Adding a new OAuth provider (e.g., GitHub):**

1. **Write the test first** (TDD approach):
   ```php
   // app/tests/Integration/OAuthProviderFactoryTest.php
   public function testCanCreateGitHubProviderWhenConfigured(): void
   {
       $this->ci->get('config')->set('oauth.github', [...]);
       $factory = $this->ci->get(OAuthProviderFactory::class);
       $provider = $factory->create('github');
       $this->assertInstanceOf(GitHub::class, $provider);
   }
   ```

2. **Run test** (should fail): `vendor/bin/phpunit --filter testCanCreateGitHubProviderWhenConfigured`

3. **Implement the feature**:
   - Add dependency to `composer.json`
   - Update `OAuthProviderFactory` to support GitHub
   - Add configuration in `app/config/default.php`
   - Update routes if needed

4. **Run test again** (should pass): `vendor/bin/phpunit --filter testCanCreateGitHubProviderWhenConfigured`

5. **Run full test suite**: `vendor/bin/phpunit`

6. **Update documentation**: README.md, INSTALL.md, etc.

7. **Commit with meaningful message**:
   ```
   Add GitHub OAuth provider support
   
   - Add GitHub provider to OAuthProviderFactory
   - Add configuration for GitHub credentials
   - Add tests for GitHub provider creation
   - Update documentation
   ```

---

**Remember**: The goal is to create a seamless extension of UserFrosting 6 that feels like a natural part of the framework, not a separate system bolted on.
