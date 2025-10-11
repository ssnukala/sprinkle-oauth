# Testing Guide - OAuth Sprinkle

This document describes the testing infrastructure and guidelines for the OAuth Sprinkle.

## Overview

The OAuth Sprinkle uses PHPUnit for automated testing, following the UserFrosting 6 testing patterns. Tests are organized into integration tests that verify the sprinkle works correctly as a UserFrosting component.

## Test Structure

```
app/tests/
├── OAuthTestCase.php          # Base test case for all OAuth tests
├── Controller/                # Controller integration tests
│   └── OAuthControllerTest.php
├── Integration/               # Service and repository integration tests
│   ├── OAuthProviderFactoryTest.php
│   └── OAuthConnectionRepositoryTest.php
└── README.md                  # This file
```

## Running Tests

### Prerequisites

1. Install dependencies:
   ```bash
   composer install
   ```

2. Ensure your test database is configured (uses SQLite in-memory by default)

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Run controller tests only
vendor/bin/phpunit app/tests/Controller

# Run integration tests only
vendor/bin/phpunit app/tests/Integration

# Run a specific test file
vendor/bin/phpunit app/tests/Integration/OAuthProviderFactoryTest.php

# Run a specific test method
vendor/bin/phpunit --filter testCanCreateGoogleProviderWhenConfigured
```

### Run with Coverage

```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html coverage/

# View coverage in browser
open coverage/index.html
```

## Writing Tests

### Test Case Structure

All tests should extend `OAuthTestCase`:

```php
<?php

namespace UserFrosting\Sprinkle\OAuth\Tests\Integration;

use UserFrosting\Sprinkle\OAuth\Tests\OAuthTestCase;

class MyTest extends OAuthTestCase
{
    public function testSomething(): void
    {
        // Your test code here
        $this->assertTrue(true);
    }
}
```

### Testing with the Container

Access services from the dependency injection container:

```php
public function testServiceFromContainer(): void
{
    $service = $this->ci->get(MyService::class);
    
    $this->assertInstanceOf(MyService::class, $service);
}
```

### Testing with Configuration

Set configuration values for testing:

```php
public function testWithConfig(): void
{
    $this->ci->get('config')->set('oauth.google', [
        'clientId' => 'test-id',
        'clientSecret' => 'test-secret',
    ]);
    
    // Test code that uses this configuration
}
```

### Testing with Database

Use the database in tests:

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Refresh database for clean state
    $this->refreshDatabase();
}

public function testDatabaseOperation(): void
{
    // Create test data
    $user = User::factory()->create();
    
    // Test your code
    $this->assertNotNull($user->id);
}
```

### Testing HTTP Requests

Test controller endpoints:

```php
public function testHttpEndpoint(): void
{
    $request = $this->createRequest('GET', '/oauth/login');
    
    $response = $this->handleRequest($request);
    
    $this->assertEquals(200, $response->getStatusCode());
}
```

## Test Coverage Goals

- **Controllers**: Test all public methods and route handlers
- **Services**: Test business logic and error handling
- **Repositories**: Test CRUD operations and queries
- **Integration**: Test OAuth flows end-to-end (where possible with mocks)

## Mocking External Services

When testing OAuth flows, mock external OAuth provider API calls:

```php
use Mockery;

public function testOAuthFlow(): void
{
    $mockProvider = Mockery::mock(GoogleClient::class);
    $mockProvider->shouldReceive('fetchAccessTokenWithAuthCode')
        ->once()
        ->andReturn(['access_token' => 'test-token']);
    
    // Inject mock into container or service
    // ... test code
}
```

## Continuous Integration

Tests run automatically on:
- Pull requests
- Commits to main branch
- Tagged releases

See `.github/workflows/` for CI configuration.

## Best Practices

1. **Isolate Tests**: Each test should be independent and not rely on other tests
2. **Clean State**: Use `setUp()` and `tearDown()` to ensure clean state
3. **Descriptive Names**: Test method names should describe what they test
4. **One Assertion Per Test**: Focus each test on a single behavior
5. **Use Factories**: Use model factories for creating test data
6. **Mock External APIs**: Never make real API calls in tests
7. **Test Edge Cases**: Include tests for error conditions and edge cases

## Common Test Patterns

### Testing Repository CRUD

```php
public function testCanCreateEntity(): void
{
    $repository = $this->ci->get(MyRepository::class);
    
    $entity = $repository->create(['field' => 'value']);
    
    $this->assertNotNull($entity->id);
    $this->assertEquals('value', $entity->field);
}
```

### Testing Service Logic

```php
public function testServiceBusinessLogic(): void
{
    $service = $this->ci->get(MyService::class);
    
    $result = $service->doSomething('input');
    
    $this->assertEquals('expected', $result);
}
```

### Testing Route Registration

```php
public function testRouteIsRegistered(): void
{
    $router = $this->ci->get('router');
    $routes = $router->getRoutes();
    
    $patterns = array_map(fn($r) => $r->getPattern(), $routes);
    
    $this->assertContains('/oauth/login', $patterns);
}
```

### Testing Exception Handling

```php
public function testThrowsExceptionOnError(): void
{
    $this->expectException(MyException::class);
    $this->expectExceptionMessage('Expected error message');
    
    $service = $this->ci->get(MyService::class);
    $service->methodThatThrows();
}
```

## Debugging Tests

### Run with Verbose Output

```bash
vendor/bin/phpunit --verbose
```

### Debug Single Test

```bash
vendor/bin/phpunit --debug --filter testMethodName
```

### Print Debug Information

```php
public function testSomething(): void
{
    $value = $this->calculateValue();
    
    // Temporary debug output
    var_dump($value);
    
    $this->assertEquals('expected', $value);
}
```

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [UserFrosting Testing Guide](https://learn.userfrosting.com/testing)
- [Mockery Documentation](http://docs.mockery.io/)
- [Laravel Factories](https://laravel.com/docs/database-testing) (used by UserFrosting)

## Contributing

When adding new features:
1. Write tests first (TDD approach)
2. Ensure all tests pass before submitting PR
3. Maintain test coverage above 80%
4. Update this documentation if adding new test patterns

## Troubleshooting

### Database Connection Errors

If you see database connection errors, check:
- PHPUnit configuration in `phpunit.xml.dist`
- Test database is using SQLite `:memory:` by default
- Migrations are running in `setUp()`

### Service Not Found in Container

If a service isn't available in tests:
- Ensure service provider is registered in `OAuth.php`
- Check service is properly bound in service provider
- Verify you're extending `OAuthTestCase`

### OAuth Provider Mocking Issues

If OAuth provider mocks aren't working:
- Use Mockery for complex mocking
- Ensure mock is properly bound in container
- Check method expectations match actual calls

---

**Happy Testing!** 🧪
