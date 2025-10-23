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
- Write tests following UserFrosting testing patterns
- Use PHPUnit for unit tests
- Mock dependencies appropriately
- Test both success and failure scenarios

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

- **Service Layer**: `OAuthService`, `OAuthAuthenticationService` handle OAuth provider logic
- **Repository Layer**: `OAuthConnectionRepository` manages OAuth connection data
- **Entity Layer**: `OAuthConnection` represents OAuth provider connections
- **Controller Layer**: `OAuthController` handles HTTP requests for OAuth flows
- **Routes**: `OAuthRoutes` defines OAuth-specific routes

### Integration Points

When modifying this sprinkle, ensure compatibility with:
- UserFrosting's `User` entity and authentication system
- Existing route definitions and middleware
- Core sprinkle's service providers
- The bakery CLI command system
- UserFrosting's alert/notification system

## Before Making Changes

1. **Review the framework code** in the reference repositories to understand existing patterns
2. **Check for existing components** that can be reused or extended
3. **Follow the established patterns** rather than creating new approaches
4. **Test integration** with UserFrosting core functionality
5. **Update documentation** to reflect changes

## Common Pitfalls to Avoid

- ❌ Don't reinvent components that exist in the framework
- ❌ Don't violate PSR-12 or framework coding standards
- ❌ Don't hardcode configuration values
- ❌ Don't bypass the dependency injection container
- ❌ Don't create routes without using `RouteDefinitionInterface`
- ❌ Don't access database directly without using repositories
- ❌ Don't forget to add translations for user-facing text
- ❌ Don't ignore error handling and validation

## Resources

- **UserFrosting Documentation**: https://learn.userfrosting.com/
- **UserFrosting Chat**: https://chat.userfrosting.com/
- **UserFrosting GitHub**: https://github.com/userfrosting

---

**Remember**: The goal is to create a seamless extension of UserFrosting 6 that feels like a natural part of the framework, not a separate system bolted on.
